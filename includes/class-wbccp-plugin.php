<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WBCCP_Plugin {
	public static function init() {
		self::load_textdomain();

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

		if ( function_exists( 'buddypress' ) ) {
			WBCCP_BP::init();
			WBCCP_Activity::init();
		}

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_block_editor_assets' ) );
	}

	public static function load_textdomain() {
		load_plugin_textdomain(
			'wb-community-calendar-pro',
			false,
			dirname( plugin_basename( WBCCP_PATH . 'wb-community-calendar-pro.php' ) ) . '/languages'
		);
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
					'error'   => __( 'Unable to save RSVP right now.', 'wb-community-calendar-pro' ),
					'login'   => __( 'Please log in to RSVP.', 'wb-community-calendar-pro' ),
				),
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
