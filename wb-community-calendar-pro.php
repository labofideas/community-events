<?php
/**
 * Plugin Name: WB Community Calendar Pro
 * Description: Lightweight BuddyPress/BuddyBoss community calendar with group events.
 * Version: 0.1.0
 * Author: WB
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
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

if ( ! defined( 'WBCCP_BASENAME' ) ) {
	define( 'WBCCP_BASENAME', plugin_basename( __FILE__ ) );
}

require_once WBCCP_PATH . 'includes/class-wbccp-plugin.php';

register_activation_hook( __FILE__, array( 'WBCCP_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WBCCP_Plugin', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'WBCCP_Plugin', 'init' ) );

function wbccp_plugin_action_links( $links ) {
	$settings_url = admin_url( 'options-general.php?page=wbccp-settings' );
	$settings_link = '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings', 'wb-community-calendar-pro' ) . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}

add_filter( 'plugin_action_links_' . WBCCP_BASENAME, 'wbccp_plugin_action_links' );
