<?php
/**
 * Plugin Name: WB Community Calendar Pro
 * Description: Lightweight BuddyPress-native community calendar with group events.
 * Version: 0.1.0
 * Author: WB
 * Text Domain: wb-community-calendar-pro
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WBCCP_VERSION' ) ) {
	define( 'WBCCP_VERSION', '0.1.0' );
}

if ( ! defined( 'WBCCP_PATH' ) ) {
	define( 'WBCCP_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'WBCCP_URL' ) ) {
	define( 'WBCCP_URL', plugin_dir_url( __FILE__ ) );
}

require_once WBCCP_PATH . 'includes/class-wbccp-plugin.php';

register_activation_hook( __FILE__, array( 'WBCCP_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WBCCP_Plugin', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'WBCCP_Plugin', 'init' ) );
