<?php

if ( ! class_exists( 'ContentAd__Includes__Admin__Admin' ) ) {

	class ContentAd__Includes__Admin__Admin {

		function __construct() {
			global $wp_version;
			if( version_compare( $wp_version, '3.1', '<' ) ) {
				ContentAd__Includes__Admin__WP3_Menu_Fix::on_load();
			}
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			add_action( 'admin_init', array( $this, 'admin_init' ) );
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		}

		function admin_menu() {
			global $contentad_settings_page, $wp_version;
			if( version_compare( $wp_version, '3.1', '>=' ) ) {
				add_menu_page(
					$page_title = __( 'Content.ad', 'contentad' ),
					$menu_title = __( 'Content.ad', 'contentad' ),
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
			ContentAd__Includes__API::validate_installation_key();
			settings_errors( 'contentad_settings' ); ?>
			<div class="wrap settings-container">
				<div class="menu-masthead">
					<div class="icon32 icon32-contentad-settings" id="icon-broadpsring-ca">
						<?php echo '<a href="https://www.content.ad/" target="_blank"><img src="'.plugins_url( 'images/', CONTENTAD_FILE ).'ca_logo.png" /></a>' ?>
					</div>
					<h2 class="menu-header"><?php _e('Settings','contentad'); ?></h2>
				</div>
				<form name="contentad_settings" action="<?php echo admin_url('options.php'); ?>" method="post">
					<?php settings_fields( 'contentad_settings' ); ?>
					<?php do_settings_sections(CONTENTAD_SLUG); ?><br />
					<span class="contentad_instructions_help">Need help?  Join the <a href="http://help.content.ad" target="_blank" title="help.Content.ad">Help.Content.ad</a> community or post a question at our <a href="http://wordpress.org/support/plugin/contentad" target="_blank" title="Content.ad Plugin Page">WordPress page</a>.</span>
				</form>
			</div><?php
		}

		function load() {
			global $contentad_settings_page, $current_screen;
			$screen = $current_screen;
			if ( ( $screen->id == $contentad_settings_page ) || ( 'edit-content_ad_widget' == $screen->id ) ) {
				if( $screen->id == $contentad_settings_page ) {
					wp_enqueue_script( 'contentad.settings.js', plugins_url( 'js/', CONTENTAD_FILE ).'settings.js', array('jquery','thickbox'), '0.6' );
				}
				if( 'edit-content_ad_widget' == $screen->id ) {
					$query = http_build_query( array(
						'installKey' => ContentAd__Includes__API::get_installation_key(),
						'aid' => ContentAd__Includes__API::get_api_key(),
						'new' => 1,
						'TB_iframe' => 'true',
						'height' => '85%',
						'width' => '950',
					) );
					$new_widget_url = CONTENTAD_REMOTE_URL . "widget/multipost.aspx?{$query}";
					$query = http_build_query( array(
						'installKey' => ContentAd__Includes__API::get_installation_key(),
						'aid' => ContentAd__Includes__API::get_api_key(),
						'TB_iframe' => 'true',
						'height' => '85%',
						'width' => '950',
					) );
					$report_url = CONTENTAD_REMOTE_URL . "widget/report.aspx?{$query}";
					$settings_url = CONTENTAD_REMOTE_URL . "Account/Details.aspx?{$query}";
					wp_enqueue_script( 'contentad.admin.js', plugins_url( 'js/', CONTENTAD_FILE ).'admin.js', array('jquery','thickbox'), '0.6' );
					wp_localize_script( 'contentad.admin.js', 'ContentAd', array(
						'action' => 'edit_contentad_widget',
						'nonce' => wp_create_nonce( 'edit_contentad_widget' ),
						'pauseTranslation' => __( 'Pause', 'contentad' ),
						'activateTranslation' => __( 'Activate', 'contentad' ),
						'newWidgetCall' => $new_widget_url,
						'reportName' => __('View Statistics', 'contentad' ),
						'reportCall' => $report_url,
						'settingsLinkText' => __('Account Settings', 'contentad' ),
						'settingsCall' => $settings_url,
					) );
				}
				wp_enqueue_style( 'contentad.admin.css', plugins_url( 'css/', CONTENTAD_FILE ).'admin.css', array('thickbox') );
			}
			if ( $screen->id == 'edit-content_ad_widget' ) {
				ContentAd__Includes__Init::get_widgets();
			}
		}

		function admin_init(){
			register_setting( 'contentad_settings', 'contentad_api_key', array( $this, 'sanitize_api_key' ) );
			add_settings_section( 'contentad_api', __('', 'contentad'), array( $this, 'settings_section_api' ), CONTENTAD_SLUG );
			add_settings_field( 'contentad_account_creation', '<span class="contentad_step"><div class="number">1</div></span>', array( $this, 'settings_account_creation' ), CONTENTAD_SLUG , 'contentad_api' );
			add_settings_field( 'contentad_account_connection', '<span class="contentad_step"><div class="number">2</div></span>', array( $this, 'settings_account_connection' ), CONTENTAD_SLUG , 'contentad_api' );
			add_settings_field( 'contentad_widgets_create', '<span class="contentad_step"><div class="number">3</div></span>', array( $this, 'settings_widget_create' ), CONTENTAD_SLUG , 'contentad_api' );
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
				'installKey' => ContentAd__Includes__API::get_installation_key(),
				'TB_iframe' => 'true',
				'height' => '85%',
				'width' => '950',
			) );
			$url = CONTENTAD_REMOTE_URL . 'Register.aspx?' . $query;
			// BEFORE CAS echo '<p>Do you have a Content.ad account?  If not, <a href="' . $url . '" class="thickbox">create one.</a> It\'s 100% free.</p>';

			echo '<span class="contentad_instructions_h2">Do you have a Content.ad account? If not, <a href="' . $url . '" class="thickbox">create one</a> - it\'s <strong>100% free</strong>.</span>';

		}

		function settings_account_connection() {
			$api_key = ContentAd__Includes__API::get_api_key();
			$is_valid = ContentAd__Includes__API::validate_api_key( $api_key );

			$query = http_build_query( array(
				'installKey' => ContentAd__Includes__API::get_installation_key(),
				'aid' => ContentAd__Includes__API::get_api_key(),
				'TB_iframe' => 'true',
				'height' => '85%',
				'width' => '950',
			) );
			$settings_link_url = CONTENTAD_REMOTE_URL . 'Account/Details.aspx?' . $query;

			?>

			<span class="contentad_instructions_h2">
				<?php printf('Connect your site to Content.ad. %sFind your API key here%s.', '<a class="thickbox" href="'.$settings_link_url.'">', '</a>'); ?></span>

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
				'installKey' => ContentAd__Includes__API::get_installation_key(),
				'aid' => ContentAd__Includes__API::get_api_key(),
				'new' => 1,
				'TB_iframe' => 'true',
				'height' => '85%',
				'width' => '950',
			) );
			$url = CONTENTAD_REMOTE_URL . "widget/multipost.aspx?{$query}"; ?>
			<span class="contentad_instructions_h2">Create your first <a href="<?php echo $url; ?>" class="thickbox">Content.ad widget</a></span><?php
		}

		function admin_notices() {
			global $contentad_settings_page, $current_screen;
			$api_key = ContentAd__Includes__API::get_api_key();
			if ( ! $api_key ) {
				$screen = $current_screen;
				if ( current_user_can( 'manage_options' ) && ! ( $screen->id == $contentad_settings_page ) ) {
					echo '<div class="error"><p>Your Content.ad plugin is almost ready. Please <a href="admin.php?page=contentad-settings">register it</a> to get started.</p></div>';
				}
			}
		}

	}

}
