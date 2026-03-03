<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WBCCP_Plugin {
	public static function init() {
		require_once WBCCP_PATH . 'includes/class-wbccp-cpt.php';
		require_once WBCCP_PATH . 'includes/class-wbccp-bp.php';
		require_once WBCCP_PATH . 'includes/class-wbccp-shortcode.php';
		require_once WBCCP_PATH . 'includes/class-wbccp-rsvp.php';
		require_once WBCCP_PATH . 'includes/class-wbccp-settings.php';
		require_once WBCCP_PATH . 'includes/class-wbccp-views.php';
		require_once WBCCP_PATH . 'includes/class-wbccp-blocks.php';
		require_once WBCCP_PATH . 'includes/class-wbccp-notifications.php';
		require_once WBCCP_PATH . 'includes/class-wbccp-rest.php';
		require_once WBCCP_PATH . 'includes/class-wbccp-activity.php';

		WBCCP_CPT::init();
		WBCCP_Shortcode::init();
		WBCCP_RSVP::init();
		WBCCP_Settings::init();
		WBCCP_Blocks::init();
		WBCCP_REST::init();
		WBCCP_Notifications::init();

		if ( self::is_social_platform_active() ) {
			WBCCP_BP::init();
			WBCCP_Activity::init();
		}

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_block_editor_assets' ) );
	}

	public static function load_textdomain() {
		// Since WordPress 4.6, plugin text domains are loaded automatically.
	}

	public static function is_social_platform_active() {
		return function_exists( 'buddypress' )
			|| function_exists( 'bp_is_active' )
			|| defined( 'BUDDYBOSS_PLATFORM_VERSION' );
	}

	public static function activate() {
		require_once WBCCP_PATH . 'includes/class-wbccp-cpt.php';
		require_once WBCCP_PATH . 'includes/class-wbccp-rsvp.php';

		WBCCP_CPT::register_cpt();
		WBCCP_RSVP::maybe_create_table();

		flush_rewrite_rules();
	}

	public static function deactivate() {
		flush_rewrite_rules();
	}

	public static function enqueue_assets() {
		wp_enqueue_style(
			'wbccp-frontend',
			WBCCP_URL . 'assets/css/wbccp.css',
			array(),
			WBCCP_VERSION
		);

		$settings = class_exists( 'WBCCP_Settings' ) ? WBCCP_Settings::get_settings() : array();
		$brand_color = ! empty( $settings['brand_color'] ) ? sanitize_hex_color( $settings['brand_color'] ) : '';
		if ( ! $brand_color ) {
			$brand_color = '#2563eb';
		}
		$brand_rgb = self::hex_to_rgb( $brand_color );
		$inline_css = ':root{--wbccp-accent:' . esc_attr( $brand_color ) . ';--wbccp-accent-rgb:' . esc_attr( $brand_rgb ) . ';}';
		wp_add_inline_style( 'wbccp-frontend', $inline_css );

		wp_enqueue_script(
			'wbccp-frontend',
			WBCCP_URL . 'assets/js/wbccp-frontend.js',
			array(),
			WBCCP_VERSION,
			true
		);

		wp_localize_script(
			'wbccp-frontend',
			'wbccpData',
			array(
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'wbccp_rsvp_ajax' ),
				'showViewerTime' => class_exists( 'WBCCP_Settings' ) ? (int) WBCCP_Settings::get_settings()['show_viewer_timezone'] : 0,
					'messages' => array(
						'success' => __( 'RSVP updated.', 'wb-community-calendar-pro' ),
						'error'   => __( 'Could not save RSVP. Please try again.', 'wb-community-calendar-pro' ),
						'login'   => __( 'Please log in to RSVP.', 'wb-community-calendar-pro' ),
					),
			)
		);
	}

	private static function hex_to_rgb( $hex ) {
		$hex = ltrim( (string) $hex, '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		if ( 6 !== strlen( $hex ) ) {
			return '37,99,235';
		}

		return implode(
			',',
			array(
				hexdec( substr( $hex, 0, 2 ) ),
				hexdec( substr( $hex, 2, 2 ) ),
				hexdec( substr( $hex, 4, 2 ) ),
			)
		);
	}

	public static function enqueue_admin_assets( $hook ) {
		if ( 'post-new.php' !== $hook && 'post.php' !== $hook ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || WBCCP_CPT::CPT !== $screen->post_type ) {
			return;
		}

		wp_enqueue_style(
			'wbccp-admin',
			WBCCP_URL . 'assets/css/wbccp-admin.css',
			array(),
			WBCCP_VERSION
		);

		wp_enqueue_script(
			'wbccp-admin',
			WBCCP_URL . 'assets/js/wbccp-admin.js',
			array(),
			WBCCP_VERSION,
			true
		);
	}

	public static function enqueue_block_editor_assets() {
		wp_enqueue_style(
			'wbccp-admin',
			WBCCP_URL . 'assets/css/wbccp-admin.css',
			array(),
			WBCCP_VERSION
		);

		wp_enqueue_script(
			'wbccp-admin',
			WBCCP_URL . 'assets/js/wbccp-admin.js',
			array(),
			WBCCP_VERSION,
			true
		);
	}
}
