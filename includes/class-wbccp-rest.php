<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WBCCP_REST {
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes() {
		register_rest_route(
			'wbccp/v1',
			'/occurrences',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_occurrences' ),
				'permission_callback' => array( __CLASS__, 'can_access' ),
				'args'                => array(
					'group_id' => array(
						'type'    => 'integer',
						'required' => false,
					),
					'start' => array(
						'type'    => 'integer',
						'required' => false,
					),
					'end' => array(
						'type'    => 'integer',
						'required' => false,
					),
					'category' => array(
						'type'    => 'integer',
						'required' => false,
					),
					'tag' => array(
						'type'    => 'string',
						'required' => false,
					),
				),
			)
		);
	}

	public static function can_access( WP_REST_Request $request ) {
		$group_id = (int) $request->get_param( 'group_id' );
		if ( $group_id ) {
			return WBCCP_CPT::can_view_group_calendar( $group_id );
		}

		return true;
	}

	public static function get_occurrences( WP_REST_Request $request ) {
		$group_id = (int) $request->get_param( 'group_id' );
		$start = (int) $request->get_param( 'start' );
		$end = (int) $request->get_param( 'end' );
		$category = (int) $request->get_param( 'category' );
		$tag = sanitize_text_field( (string) $request->get_param( 'tag' ) );

		$now = current_time( 'timestamp' );
		if ( ! $start ) {
			$start = $now - DAY_IN_SECONDS;
		}
		if ( ! $end ) {
			$end = $now + ( 30 * DAY_IN_SECONDS );
		}

		$tax_query = array();
		if ( $category ) {
			$tax_query[] = array(
				'taxonomy' => WBCCP_CPT::TAX_CATEGORY,
				'field'    => 'term_id',
				'terms'    => array( $category ),
			);
		}
		if ( $tag ) {
			$tax_query[] = array(
				'taxonomy' => WBCCP_CPT::TAX_TAG,
				'field'    => 'slug',
				'terms'    => array( sanitize_title( $tag ) ),
			);
		}
		if ( count( $tax_query ) > 1 ) {
			$tax_query['relation'] = 'AND';
		}

		$occurrences = WBCCP_CPT::get_group_occurrences( $group_id, $start, $end, 'all', $tax_query );
		$payload = array();
		foreach ( $occurrences as $occurrence ) {
			$event_id = (int) $occurrence['event_id'];
			$categories = wp_get_post_terms( $event_id, WBCCP_CPT::TAX_CATEGORY, array( 'fields' => 'names' ) );
			$tags = wp_get_post_terms( $event_id, WBCCP_CPT::TAX_TAG, array( 'fields' => 'names' ) );
			$payload[] = array(
				'event_id'  => $event_id,
				'title'     => get_the_title( $event_id ),
				'start'     => (int) $occurrence['start'],
				'end'       => (int) $occurrence['end'],
				'group_id'  => (int) get_post_meta( $event_id, 'wbccp_group_id', true ),
				'location'  => get_post_meta( $event_id, 'wbccp_location', true ),
				'link'      => get_post_meta( $event_id, 'wbccp_link', true ),
				'timezone'  => get_post_meta( $event_id, 'wbccp_timezone', true ),
				'capacity'  => (int) get_post_meta( $event_id, 'wbccp_capacity', true ),
				'categories'=> $categories ? array_values( $categories ) : array(),
				'tags'      => $tags ? array_values( $tags ) : array(),
				'permalink' => get_permalink( $event_id ),
				'rrule'     => get_post_meta( $event_id, 'wbccp_rrule', true ),
			);
		}

		return rest_ensure_response( $payload );
	}
}
