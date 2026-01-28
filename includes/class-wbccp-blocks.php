<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WBCCP_Blocks {
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_blocks' ) );
	}

	public static function register_blocks() {
		$editor_script = 'wbccp-calendar-block-editor';
		$editor_style  = 'wbccp-calendar-block-editor';

		wp_register_script(
			$editor_script,
			WBCCP_URL . 'blocks/community-calendar/editor.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render' ),
			WBCCP_VERSION,
			true
		);

		wp_register_style(
			$editor_style,
			WBCCP_URL . 'blocks/community-calendar/editor.css',
			array( 'wp-edit-blocks' ),
			WBCCP_VERSION
		);

		register_block_type(
			WBCCP_PATH . 'blocks/community-calendar',
			array(
				'render_callback' => array( __CLASS__, 'render_calendar_block' ),
			)
		);
	}

	public static function render_calendar_block( $attributes ) {
		$atts = array(
			'group_id' => isset( $attributes['groupId'] ) ? (int) $attributes['groupId'] : 0,
			'limit'    => isset( $attributes['limit'] ) ? (int) $attributes['limit'] : 10,
			'view'     => isset( $attributes['view'] ) ? sanitize_text_field( $attributes['view'] ) : 'list',
		);

		return WBCCP_Shortcode::render_calendar( $atts );
	}
}
