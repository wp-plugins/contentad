<?php
/*
 * Plugin Name: Content.Ad
 * Plugin URI: http://content.ad/
 * Description: Content.Ad enables blog owners to display ads or related blog posts (from their own blog) in a "lead me to more content" section. The ads are sourced dynamically from the Content.Ad system and can be a source of revenue for the blog owner.
 * Version:  1.0.3
 * Author: BroadSpring
 * Author URI: http://content.ad/
 * Text Domain: contentad
 * License: GPLv2
 *
 *  Copyright 2012 BroadSpring (info@content.ad)
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License, version 2, as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

$contentad_file = __FILE__;

/*if ( isset( $plugin ) ) {
	$contentad_file = $plugin;
} else if ( isset( $mu_plugin ) ) {
	$contentad_file = $mu_plugin;
} else if ( isset( $network_plugin ) ) {
	$contentad_file = $network_plugin;
}*/

define( 'CONTENTAD_VERSION', '1.0.3' );
define( 'CONTENTAD_FILE', $contentad_file );
define( 'CONTENTAD_PATH', plugin_dir_path( $contentad_file ) );
define( 'CONTENTAD_URL', plugins_url( '', $contentad_file ) );
define( 'CONTENTAD_NAME', 'Content.Ad' );
define( 'CONTENTAD_SLUG', 'contentad' );
define( 'CONTENTAD_MIN_PHP', '5.2' );
define( 'CONTENTAD_API_URL', 'http://api.content.ad/api.svc' );
define( 'CONTENTAD_REMOTE_URL', 'https://www.content.ad/' );

if( ! defined('CONTENTAD_ERROR_LOGGING') ) {
	define( 'CONTENTAD_ERROR_LOGGING', false );
}
function append_to_log( $text ){
	if( CONTENTAD_ERROR_LOGGING ) {
		$file_handle = fopen( CONTENTAD_PATH . '/debug.log', 'a' );
		fwrite( $file_handle, $text . PHP_EOL );
		fclose( $file_handle );
	}
}

include( CONTENTAD_PATH . 'includes/custom-post-type.php' );
include( CONTENTAD_PATH . 'includes/contentad-api.php' );
include( CONTENTAD_PATH . 'includes/widget.php' );

if( is_admin() ) {
	include( CONTENTAD_PATH . 'admin/admin.php' );
}

if ( ! class_exists( 'ContentAd_Init' ) ) {

	class ContentAd_Init {

		static function on_load() {
			add_action( 'init', array( __CLASS__, 'init' ) );
			add_action( 'widgets_init', array( __CLASS__, 'widgets_init' ) );
			add_action( 'wp_ajax_delete_contentad_widget', array( __CLASS__, 'ajax_delete_widget' ) );
			register_activation_hook( CONTENTAD_FILE, array( __CLASS__, 'activate' ) );
			register_deactivation_hook( CONTENTAD_FILE, array( __CLASS__, 'deactivate' ) );
			register_uninstall_hook( CONTENTAD_FILE, array( __CLASS__, 'uninstall' ) );
		}

		static function init() {
			load_plugin_textdomain( 'contentad', false, basename( dirname( CONTENTAD_FILE ) ) . '/languages' );
			add_action( 'wp_head', array( __CLASS__, 'wp_head' ) );
			add_filter( 'the_content', array( __CLASS__, 'the_content' ), 0 );
		}

		static function widgets_init() {
			register_widget('ContentAd_Widget');
		}

		function activate() {
			if ( version_compare( PHP_VERSION, CONTENTAD_MIN_PHP, '<' ) ) {
				deactivate_plugins( basename( CONTENTAD_FILE ) );
				trigger_error( printf( __( '%1s requires PHP %2s or later. You are currently running version %3s. Please contact your web host to upgrade.', 'contentad' ), CONTENTAD_NAME,
					 CONTENTAD_MIN_PHP, PHP_VERSION ), E_USER_ERROR
				);
			}
		}

		function deactivate() {
			$widgets = get_posts( array(
				'post_type'   => 'content_ad_widget',
				'numberposts' => -1,
				'post_status' => 'publish', //get_post_stati(),
			) );
			foreach ( $widgets as $widget ) {
				self::delete_local_widget( $widget->ID );
			}
		}

		function uninstall() {
			delete_option( 'contentad_api_key' );
			delete_option( 'contentad_install_key' );
		}

		static function the_content( $content ) {
			if ( is_single() ) {
				$before = ContentAd_API::get_ad_code( 'before_post_content' );
				$after = ContentAd_API::get_ad_code( 'after_post_content' );
				$content = $before . $content . $after;
			}
			return $content;
		}

		static function get_local_widgets() {
			append_to_log( PHP_EOL . 'FETCHING LOCAL WIDGETS' );
			$local_widgets = get_posts( array(
				'post_type' => 'content_ad_widget',
				'post_status' => 'publish', //get_post_stati(),
				'numberposts' => -1,
			) );
			append_to_log( 'LOCAL WIDGETS FOUND: ' . count( $local_widgets ) );
			$widgets_indexed_by_id = array();
			if ( $local_widgets ) {
				foreach ( $local_widgets as $key => $widget ) {
					$local_widgets[$key]->adunit_id = get_post_meta( $widget->ID, '_widget_id', true );
					$local_widgets[$key]->adunit_name = get_post_meta( $widget->ID, '_widget_type', true );
					$local_widgets[$key]->aw_guid = get_post_meta( $widget->ID, '_widget_guid', true );
					$local_widgets[$key]->placement = get_post_meta( $widget->ID, 'placement', true );
					append_to_log( PHP_EOL . 'RESULT ' . ($key + 1) . ': LOCAL WIDGET ' . $widget->ID );
					append_to_log( '    ADUNIT ID: ' . $local_widgets[$key]->adunit_id );
					append_to_log( '    ADUNIT NAME: ' . $local_widgets[$key]->adunit_name );
					append_to_log( '    AD GUID: ' . $local_widgets[$key]->aw_guid );
					append_to_log( '    AD PLACEMENT: ' . $local_widgets[$key]->placement );
					$widgets_indexed_by_id[$widget->ID] = $local_widgets[$key];
				}
			}
			return $widgets_indexed_by_id;
		}

		static function get_local_widget_id_by_adunit_id( $adunit_id ) {
			append_to_log( PHP_EOL . 'SEARCHING FOR LOCAL MATCH TO REMOTE AD WIDGET: ' . $adunit_id );
			global $wpdb;
			$sql = sprintf(
				"SELECT ID FROM %s LEFT JOIN %s ON ID = post_id AND meta_key = '_widget_id' WHERE meta_value = '%s' LIMIT 1",
				$wpdb->posts,
				$wpdb->postmeta,
				$adunit_id
			);
			$post_id = $wpdb->get_col( $sql );
			$post_id = $post_id[0];
			if( isset( $post_id[0] ) ) {
				append_to_log( '    FOUND LOCAL WIDGET ('.$post_id.') AS A MATCH FOR REMOTE WIDGET ('.$adunit_id.')' );
				return $post_id;
			} else {
				append_to_log( '    NO MATCH FOR REMOTE WIDGET ('.$adunit_id.')' );
				return false;
			}
		}

		static function create_local_widget( $title, $adunit_id, $adunit_name, $aw_guid ) {
			append_to_log( 'CREATING LOCAL WIDGET: ' . $title . ' ( REMOTE ID: ' . $adunit_id . ')' );
			self::update_local_widget( $post_id = false, $title, $adunit_id, $adunit_name, $aw_guid );
		}

		static function update_local_widget( $post_id = false, $title, $adunit_id, $adunit_name, $aw_guid ) {
			if( $post_id ) {
				append_to_log( 'UPDATING LOCAL WIDGET: ' . $title . ' ( LOCAL ID: ' . $post_id . ' REMOTE ID: '.$adunit_id.' )' );
			}
			$postdata = array(
				'ID' => $post_id,
				'post_title' => $title,
				'post_status' => 'publish',
				'post_type' => 'content_ad_widget',
				'ping_status' => false,
				'to_ping' => false,
			);
			$post_id = wp_insert_post( $postdata );
			if ( $post_id ) {
				if( update_post_meta( $post_id, '_widget_id', $adunit_id, true ) ) {
					append_to_log( '    UPDATED ADUNIT ID FOR LOCAL WIDGET ('.$post_id.') TO: ' . $adunit_id );
				}
				if( update_post_meta( $post_id, '_widget_type', $adunit_name, true ) ) {
					append_to_log( '    UPDATED WIDGET TYPE ID FOR LOCAL WIDGET ('.$post_id.') TO: ' . $adunit_name );
				}
				if( update_post_meta( $post_id, '_widget_guid', $aw_guid, true ) ) {
					append_to_log( '    UPDATED WIDGET GUID FOR LOCAL WIDGET ('.$post_id.') TO: ' . $aw_guid );
				}
				append_to_log( '    CHECKING IF "PLACEMENT" POSTMETA VALUE EXISTS' );
				if( ! $placement = get_post_meta( $post_id, 'placement', true ) ) {
					append_to_log( '        POSTMETA VALUE "PLACEMENT" IS EMPTY FOR LOCAL WIDGET ('.$post_id.')' );
					if( update_post_meta( $post_id, 'placement', 'after_post_content', true ) ) {
						append_to_log( '        UPDATED WIDGET PLACEMENT FOR LOCAL WIDGET ('.$post_id.') TO: ' . 'after_post_content' );
					}
				} else {
					append_to_log( '        POSTMETA VALUE "PLACEMENT" FOR LOCAL WIDGET ('.$post_id.'): ' . $placement );
				}
			}
			return $post_id;
		}

		static function delete_local_widget( $post_id ) {
			append_to_log( 'DELETING LOCAL WIDGET: ' . $post_id );
			wp_delete_post( $post_id, true );
		}

		static function ajax_delete_widget() {
			append_to_log( PHP_EOL . 'AJAX CALL - DELETE WIDGET' );
			$response = array(
				'status' => 'error',
				'message' => 'Invalid AJAX call',
			);
			if( defined('DOING_AJAX') && DOING_AJAX ){ // This constant ensures that we are doing ajax
				$response['message'] = 'Invalid nonce';
				if( isset( $_POST['nonce'] ) && wp_verify_nonce( $_POST['nonce'], 'delete_contentad_widget' ) ){
					append_to_log( '    AJAX NONCE IS VALID' );
					$post_id = ( isset( $_POST['post_id'] ) && is_int( (int) $_POST['post_id'] ) ) ? (int) $_POST['post_id']: 0;
					append_to_log( '    POST_ID: ' . $post_id );
					if( $post_id ) {
						$adunit_id = get_post_meta( $post_id, '_widget_id', true );
						append_to_log( '    ADUNIT_ID: ' . $adunit_id );
						if( $adunit_id ) {
							ContentAd_API::delete_ad( $adunit_id );
							self::delete_local_widget( $post_id );
							$response['status'] = 'success';
							$response['post_id'] = $post_id;
							$response['adunit_id'] = $adunit_id;
							$response['message'] = 'Widget deleted successfully';
						} else {
							$response['message'] = 'Adunit ID not set';
						}
					} else {
						$response['message'] = 'Post ID not set';
					}
				}
			}
			header( 'Content: application/json' );
			echo json_encode( $response );
			die;
		}

		static function get_widgets() {
			$ad_units = ContentAd_API::get_ad_units();
			if ( is_array( $ad_units ) ) {
				$local_widgets = self::get_local_widgets();
				foreach( $ad_units as $widget ) {
					if ( $post_id = self::get_local_widget_id_by_adunit_id( $widget->adunit_id ) ) {
						self::update_local_widget( $post_id, $widget->description, $widget->adunit_id, $widget->adunit_name, $widget->aw_guid );
						unset( $local_widgets[$post_id] );
					} else {
						self::create_local_widget( $widget->description, $widget->adunit_id, $widget->adunit_name, $widget->aw_guid );
					}
				}
				if( ! empty( $local_widgets ) ) {
					foreach( $local_widgets as $widget ) {
						self::delete_local_widget( $widget->ID );
					}
				}
			}
		}

		static function wp_head() {
			if( is_single() ) {
				global $post;
				echo '<meta name="ca_title" content="'. esc_attr( strip_tags( $post->post_title ) ).'" />';
				if( $thumnail_id = get_post_meta( $post->ID, '_thumbnail_id', true ) ) {
					$image_src = wp_get_attachment_thumb_url( $thumnail_id );
					if( $image_src ) {
						echo '<meta name="ca_image" content="'. esc_attr( $image_src ).'" />';
					}
				}
			}
		}

	}
	ContentAd_Init::on_load();
}
