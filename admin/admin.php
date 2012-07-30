<?php

global $wp_version;
if( version_compare( $wp_version, '3.1', '<' ) ) {
	class Content_Ad_WP30_Menu_Fix {
		static function on_load() {
			add_filter( 'parent_file', array( __CLASS__, 'parent_file' ) );
			add_action( 'adminmenu', array( __CLASS__, 'adminmenu' ) );
			add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		}
		static function  parent_file( $parent_file ) {
			ob_start();
			return $parent_file;
		}
		static function adminmenu() {
			$html = ob_get_clean();
			$html = preg_replace('#(\<a[^\>]*wp-has-submenu[^\>]*\>)Widgets(\<\/a\>)#', '${1}Content.Ad${2}', $html);
			echo $html;
		}
		static function admin_menu() {
			global $submenu;
			$menu_slug = 'edit.php?post_type=content_ad_widget';
			$submenu_slug = 'post-new.php?post_type=content_ad_widget';
			if ( !isset( $submenu[$menu_slug] ) ) {
				return false;
			}
			foreach ( $submenu[$menu_slug] as $i => $item ) {
				if ( $submenu_slug == $item[2] ) {
					unset( $submenu[$menu_slug][$i] );
					return $item;
				}
			}
		}
	}
	Content_Ad_WP30_Menu_Fix::on_load();
}

if ( ! class_exists( 'ContentAd_Admin' ) ) {

	class ContentAd_Admin {

		function __construct() {
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			add_action( 'admin_init', array( $this, 'admin_init' ) );
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		}

		function admin_menu() {
			global $contentad_settings_page, $wp_version;
			if( version_compare( $wp_version, '3.1', '>=' ) ) {
				add_menu_page(
					$page_title = __( 'Content.Ad', 'contentad' ),
					$menu_title = __( 'Content.Ad', 'contentad' ),
					$capability = 'manage_options',
					$menu_slug = CONTENTAD_SLUG ,
					$callback = array( $this, 'menu_page_settings' ),
					$icon_url = plugins_url( 'images/' , CONTENTAD_FILE ) . 'ca_icon.png',
					$position = 56
				);
				$contentad_settings_page = add_submenu_page(
					$parent_slug = CONTENTAD_SLUG,
					$page_title = __( 'Settings', 'contentad' ),
					$menu_title = __( 'Settings', 'contentad' ) ,
					$capability = 'manage_options',
					$menu_slug = CONTENTAD_SLUG . '-settings', array( $this, 'menu_page_settings' )
				);
			} else {
				$contentad_settings_page = add_submenu_page(
					$parent_slug = 'edit.php?post_type=content_ad_widget',
					$page_title = __( 'Settings', 'contentad' ),
					$menu_title = __( 'Settings', 'contentad' ) ,
					$capability = 'manage_options',
					$menu_slug = CONTENTAD_SLUG . '-settings', array( $this, 'menu_page_settings' )
				);
			}
			add_action( 'load-'.$contentad_settings_page, array( $this, 'load' ) );
			add_action( 'load-edit.php', array( $this, 'load' ) );
		}

		function menu_page_settings(){
			ContentAd_API::validate_installation_key();
			settings_errors( 'contentad_settings' ); ?>
			<div class="wrap contentad">
				<div class="icon32 icon32-contentad-settings" id="icon-broadpsring-ca" style="width: 178px;height:45px;">
					<?php echo '<img src="'.plugins_url( 'images/', CONTENTAD_FILE ).'ca_logo.png" />' ?>
				</div>
				<h2><?php _e('Settings','contentad'); ?></h2>
				<form name="contentad_settings" action="<?php echo admin_url('options.php'); ?>" method="post">
					<?php settings_fields( 'contentad_settings' ); ?>
					<?php do_settings_sections(CONTENTAD_SLUG); ?><br />
					<span class="contentad_instructions_h2">Need help?  Join the <a href="https://getsatisfaction.com/contentad" target="_blank">Content.Ad community</a> at Get Satisfaction or post a question at the WordPress repository forum.</span>
				</form>
			</div><?php
		}

		function load() {
			// TODO: Rename (prefix) handles for our scripts and styles
			global $contentad_settings_page, $current_screen;
			$screen = $current_screen;
			if ( ( $screen->id == $contentad_settings_page ) || ( 'edit-content_ad_widget' == $screen->id ) ) {
				if( $screen->id == $contentad_settings_page ) {
					wp_enqueue_script( 'settings.js', plugins_url( 'js/', CONTENTAD_FILE ).'settings.js', array('jquery','thickbox'), '0.6' );
					wp_enqueue_script( 'easyXDM.debug.js', plugins_url( 'js/easyxdm/', CONTENTAD_FILE ).'easyXDM.debug.js', array('jquery','json2'), null );
				}
				if( 'edit-content_ad_widget' == $screen->id ) {
					$query = http_build_query( array(
						'installKey' => ContentAd_API::get_installation_key(),
						'aid' => ContentAd_API::get_api_key(),
						'new' => 1,
						'TB_iframe' => 'true',
						'height' => '85%',
						'width' => '950',
					) );
					$new_widget_url = CONTENTAD_REMOTE_URL . "widget/multipost.aspx?{$query}";
					$query = http_build_query( array(
						'installKey' => ContentAd_API::get_installation_key(),
						'aid' => ContentAd_API::get_api_key(),
						'TB_iframe' => 'true',
						'height' => '85%',
						'width' => '950',
					) );
					$report_url = CONTENTAD_REMOTE_URL . "widget/report.aspx?{$query}";
					$settings_url = CONTENTAD_REMOTE_URL . "Account/Details.aspx?{$query}";
					wp_enqueue_script( 'admin.js', plugins_url( 'js/', CONTENTAD_FILE ).'admin.js', array('jquery','thickbox'), '0.6' );
					wp_localize_script( 'admin.js', 'ContentAd', array(
						'action' => 'delete_contentad_widget',
						'nonce' => wp_create_nonce( 'delete_contentad_widget' ),
						'newWidgetCall' => $new_widget_url,
						'reportName' => __('View Statistics'),
						'reportCall' => $report_url,
						'settingsLinkText' => __('Account Settings'),
						'settingsCall' => $settings_url,
					) );
				}
				wp_enqueue_style( 'admin.css', plugins_url( 'css/', CONTENTAD_FILE ).'admin.css', array('thickbox') );
			}
			if ( $screen->id == 'edit-content_ad_widget' ) {
				ContentAd_Init::get_widgets();
			}
		}

		function admin_init(){
			register_setting( 'contentad_settings', 'contentad_api_key', array( $this, 'sanitize_api_key' ) );
			add_settings_section( 'contentad_api', __('', 'contentad'), array( $this, 'settings_section_api' ), CONTENTAD_SLUG );
			add_settings_field( 'contentad_account_creation', '<span id="contentad_step_one"></span>', array( $this, 'settings_account_creation' ), CONTENTAD_SLUG , 'contentad_api' );
			add_settings_field( 'contentad_account_connection', '<span id="contentad_step_two"></span>', array( $this, 'settings_account_connection' ), CONTENTAD_SLUG , 'contentad_api' );
			add_settings_field( 'contentad_widgets_create', '<span id="contentad_step_three"></span>', array( $this, 'settings_widget_create' ), CONTENTAD_SLUG , 'contentad_api' );
		}

		function sanitize_api_key( $dirty ) {
			$clean = preg_replace( '/[^a-z0-9-]/i', '', $dirty );
			return $clean;
		}

		function settings_section_api() {}

		function settings_account_creation() {
			$query = http_build_query( array(
				'email' => get_bloginfo('admin_email'),
				'domain' => home_url(),
				'cb' => CONTENTAD_URL . '/includes/tbclose.php',
				'installKey' => ContentAd_API::get_installation_key(),
				'TB_iframe' => 'true',
				'height' => '85%',
				'width' => '950',
			) );
			$url = CONTENTAD_REMOTE_URL . 'Register.aspx?' . $query;
			// BEFORE CAS echo '<p>Do you have a Content.Ad account?  If not, <a href="' . $url . '" class="thickbox">create one.</a> It\'s 100% free.</p>';
			
			echo '<span class="contentad_instructions_h">Do you have a Content.Ad account? </span><span class="contentad_instructions_h2"> If not, <a href="' . $url . '" class="thickbox">create one.</a> It\'s 100% free.</span>';

		}

		function settings_account_connection() {
			$api_key = ContentAd_API::get_api_key();
			$is_valid = ContentAd_API::validate_api_key( $api_key );

			$query = http_build_query( array(
				'installKey' => ContentAd_API::get_installation_key(),
				'aid' => ContentAd_API::get_api_key(),
				'TB_iframe' => 'true',
				'height' => '85%',
				'width' => '950',
			) );
			$settings_link_url = CONTENTAD_REMOTE_URL . 'Account/Details.aspx?' . $query;

			?>
			
			<span class="contentad_instructions_h2">
				<?php printf('Connect your site to Content.Ad. %sFind your API key here%s.', '<a class="thickbox" href="'.$settings_link_url.'">', '</a>'); ?></span>
				
			<p>
				<input id="contentad_api_key" name="contentad_api_key" size="40" type="text" value="<?php echo esc_attr( $api_key ); ?>" />
				<?php if( $api_key ): ?>
					<span id="contentad_api_key_valid" class="<?php echo $is_valid ? 'success': 'error'; ?>"></span>
				<?php endif; ?>
			</p>
		<p><input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e( 'Remember and Verify API key', 'contentad' ); ?>" /></p><?php
		}

		function settings_widget_create() {
			$query = http_build_query( array(
				'installKey' => ContentAd_API::get_installation_key(),
				'aid' => ContentAd_API::get_api_key(),
				'new' => 1,
				'TB_iframe' => 'true',
				'height' => '85%',
				'width' => '950',
			) );
			$url = CONTENTAD_REMOTE_URL . "widget/multipost.aspx?{$query}"; ?>
			<span class="contentad_instructions_h2">Create your first <a href="<?php echo $url; ?>" class="thickbox">content.Ad widget</a></span><?php
		}

		function admin_notices() {
			global $contentad_settings_page, $current_screen;
			$api_key = ContentAd_API::get_api_key();
			if ( ! $api_key ) {
				$screen = $current_screen;
				if ( current_user_can( 'manage_options' ) && ! ( $screen->id == $contentad_settings_page ) ) {
					echo '<div class="error"><p>Your Content.Ad plugin is almost ready. Please <a href="admin.php?page=contentad-settings">register it</a> to get started.</p></div>';
				}
			}
		}

	}

	new ContentAd_Admin();

}