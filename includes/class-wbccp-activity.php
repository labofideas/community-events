<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WBCCP_Activity {
	public static function init() {
		add_action( 'bp_init', array( __CLASS__, 'register_actions' ) );
		add_action( 'transition_post_status', array( __CLASS__, 'handle_publish' ), 10, 3 );
	}

	public static function register_actions() {
		if ( function_exists( 'bp_activity_set_action' ) ) {
			bp_activity_set_action(
				'activity',
				'wbccp_sitewide_event',
				__( 'Sitewide event created', 'wb-community-calendar-pro' )
			);
			bp_activity_set_action(
				'groups',
				'wbccp_group_event',
				__( 'Group event created', 'wb-community-calendar-pro' )
			);
		}
	}

	public static function handle_publish( $new_status, $old_status, $post ) {
		if ( empty( $post->ID ) || WBCCP_CPT::CPT !== $post->post_type ) {
			return;
		}

		if ( 'publish' !== $new_status || 'publish' === $old_status ) {
			return;
		}

		if ( get_post_meta( $post->ID, '_wbccp_activity_id', true ) ) {
			return;
		}

		if ( ! function_exists( 'bp_activity_add' ) ) {
			return;
		}

		$user_id = (int) $post->post_author;
		$event_link = get_permalink( $post->ID );
		$title = get_the_title( $post->ID );
		$user_link = function_exists( 'bp_core_get_userlink' ) ? bp_core_get_userlink( $user_id ) : '';

		$group_id = (int) get_post_meta( $post->ID, 'wbccp_group_id', true );
		if ( $group_id && function_exists( 'groups_record_activity' ) ) {
			$action = $user_link
				? sprintf( __( '%1$s created a new group event: %2$s', 'wb-community-calendar-pro' ), $user_link, '<a href="' . esc_url( $event_link ) . '">' . esc_html( $title ) . '</a>' )
				: sprintf( __( 'New group event: %s', 'wb-community-calendar-pro' ), '<a href="' . esc_url( $event_link ) . '">' . esc_html( $title ) . '</a>' );

			$activity_id = groups_record_activity(
				array(
					'user_id'           => $user_id,
					'action'            => $action,
					'component'         => 'groups',
					'type'              => 'wbccp_group_event',
					'item_id'           => $group_id,
					'secondary_item_id' => $post->ID,
					'primary_link'      => $event_link,
					'content'           => $post->post_content,
				)
			);
		} else {
			$action = $user_link
				? sprintf( __( '%1$s created a sitewide event: %2$s', 'wb-community-calendar-pro' ), $user_link, '<a href="' . esc_url( $event_link ) . '">' . esc_html( $title ) . '</a>' )
				: sprintf( __( 'New sitewide event: %s', 'wb-community-calendar-pro' ), '<a href="' . esc_url( $event_link ) . '">' . esc_html( $title ) . '</a>' );

			$activity_id = bp_activity_add(
				array(
					'user_id'      => $user_id,
					'action'       => $action,
					'component'    => 'activity',
					'type'         => 'wbccp_sitewide_event',
					'primary_link' => $event_link,
					'content'      => $post->post_content,
				)
			);
		}

		if ( ! empty( $activity_id ) ) {
			update_post_meta( $post->ID, '_wbccp_activity_id', (int) $activity_id );
		}
	}
}
