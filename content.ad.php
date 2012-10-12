<?php
/*
 * Plugin Name: Content.Ad
 * Plugin URI: http://content.ad/
 * Description: Content.Ad enables blog owners to display ads or related blog posts (from their own blog) in a "lead me to more content" section. The ads are sourced dynamically from the Content.Ad system and can be a source of revenue for the blog owner.
 * Version:  1.1.0
 * Author: BroadSpring
 * Author URI: http://content.ad/
 * Developer: NewClarity LLC
 * Developer URI: http://newclarity.net
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

define( 'CONTENTAD_VERSION', '1.1.0' );
define( 'CONTENTAD_FILE', __FILE__ );
define( 'CONTENTAD_PATH', plugin_dir_path( __FILE__ ) );
define( 'CONTENTAD_URL', plugins_url( '', __FILE__ ) );
define( 'CONTENTAD_NAME', __('Content.Ad', 'contentad') );
define( 'CONTENTAD_SLUG', 'contentad' );
define( 'CONTENTAD_API_URL', 'http://api.content.ad/api.svc' );
define( 'CONTENTAD_REMOTE_URL', 'https://www.content.ad/' );

/**
 * By default, error logging is disabled.  Define this constant in your wp-config.php file
 * and set to true in order to override this default.
 */
if( ! defined('CONTENTAD_ERROR_LOGGING') ) {
	define( 'CONTENTAD_ERROR_LOGGING', false );
}

/**
 * PHP 5.2+ is required.  Disable this plugin with a friendly message if this requirement
 * is not met in order to avoid cryptic error messages.  All PHP 5 syntax must be avoided
 * in this file to prevent parse errors before this code is run.
 */
if ( version_compare( PHP_VERSION, '5.2', '<' ) ) {
	if ( is_admin() && ( ! defined('DOING_AJAX') || ! DOING_AJAX ) ) {
		require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		deactivate_plugins( __FILE__ );
	    wp_die( printf( __(
			'%s requires PHP version 5.2 or later. You are currently running version %s.
			This plugin has now disabled itself.
			Please contact your web host regarding upgrading your PHP version.', 'contentad'
		), CONTENTAD_NAME, PHP_VERSION ) );
	}
}

/**
 * Autoload classes so that only what we need is run exactly when we need it.
 *
 * @param $class
 */
function ContentAd_Autoloader( $class ) {
	$replace = array( '#contentad#' => '', '#__#' => DIRECTORY_SEPARATOR, '#_#' => '-' );
	$path = preg_replace( array_keys( $replace ), array_values( $replace ), strtolower( $class ) );
	if( file_exists( dirname( __FILE__ ) . "{$path}.class.php" ) )
		include( dirname( __FILE__ ) . "$path.class.php" );
}
spl_autoload_register( 'ContentAd_Autoloader' );

/**
 * Takes a message and appends it to the error log.
 *
 * @param $text
 */
function contentAd_append_to_log( $text ){
	if( CONTENTAD_ERROR_LOGGING ) {
		$file_handle = fopen( CONTENTAD_PATH . '/debug.log', 'a' );
		fwrite( $file_handle, $text . PHP_EOL );
		fclose( $file_handle );
	}
}

// Initialize plugin
ContentAd__Includes__Init::on_load();
