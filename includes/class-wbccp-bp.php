<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WBCCP_BP {
	public static function init() {
		add_action( 'bp_init', array( __CLASS__, 'register_group_extension' ) );
		add_action( 'bp_setup_nav', array( __CLASS__, 'register_member_nav' ) );
		add_action( 'init', array( __CLASS__, 'handle_frontend_actions' ) );
		add_action( 'wp_ajax_wbccp_rsvp', array( __CLASS__, 'handle_rsvp_ajax' ) );
		add_action( 'wp_ajax_nopriv_wbccp_rsvp', array( __CLASS__, 'handle_rsvp_ajax' ) );
	}

	public static function register_member_nav() {
		if ( ! function_exists( 'bp_core_new_nav_item' ) || ! is_user_logged_in() ) {
			return;
		}

		bp_core_new_nav_item(
			array(
				'name'                => __( 'My Events', 'wb-community-calendar-pro' ),
				'slug'                => 'my-events',
				'position'            => 65,
				'screen_function'     => array( __CLASS__, 'member_events_screen' ),
				'default_subnav_slug' => 'my-events',
				'item_css_id'         => 'wbccp-my-events',
			)
		);
	}

	public static function member_events_screen() {
		add_action( 'bp_template_content', array( __CLASS__, 'render_member_events' ) );
		bp_core_load_template( 'members/single/plugins' );
	}

	private static function get_rsvp_lists_for_user( $user_id ) {
		$event_ids = WBCCP_RSVP::get_event_ids_for_user( $user_id );
		if ( empty( $event_ids ) ) {
			return array(
				'attending' => array(),
				'maybe'     => array(),
				'cant'      => array(),
			);
		}

		$statuses = WBCCP_RSVP::get_statuses_for_user( $event_ids, $user_id );
		$lists = array(
			'attending' => array(),
			'maybe'     => array(),
			'cant'      => array(),
		);

		foreach ( $statuses as $event_id => $status ) {
			if ( isset( $lists[ $status ] ) ) {
				$lists[ $status ][] = (int) $event_id;
			}
		}

		return $lists;
	}

	public static function format_rsvp_label( $status ) {
		$labels = array(
			'attending' => __( 'Attending', 'wb-community-calendar-pro' ),
			'maybe'     => __( 'Maybe', 'wb-community-calendar-pro' ),
			'cant'      => __( "Can't", 'wb-community-calendar-pro' ),
		);

		return isset( $labels[ $status ] ) ? $labels[ $status ] : '';
	}

	public static function render_member_events() {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			echo esc_html__( 'Please log in to view your events.', 'wb-community-calendar-pro' );
			return;
		}

		$lists = self::get_rsvp_lists_for_user( $user_id );
		$attending = $lists['attending'];
		$maybe = $lists['maybe'];
		$cant = $lists['cant'];

		$organizing_query = new WP_Query(
			array(
				'post_type'      => WBCCP_CPT::CPT,
				'post_status'    => array( 'publish', 'pending' ),
				'posts_per_page' => 20,
				'author'         => $user_id,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		echo '<div class="wbccp-my-events">';
		echo '<h2>' . esc_html__( 'My Events', 'wb-community-calendar-pro' ) . '</h2>';
		self::render_event_list( $attending, __( 'Attending', 'wb-community-calendar-pro' ) );
		self::render_event_list( $maybe, __( 'Maybe', 'wb-community-calendar-pro' ) );
		self::render_event_list( $cant, __( "Can't Attend", 'wb-community-calendar-pro' ) );

		if ( $organizing_query->have_posts() ) {
			echo '<div class="wbccp-my-events-section">';
			echo '<h3>' . esc_html__( 'Organizing', 'wb-community-calendar-pro' ) . '</h3>';
			echo '<ul class="wbccp-events-list">';
			while ( $organizing_query->have_posts() ) {
				$organizing_query->the_post();
				$event_id = get_the_ID();
				$status = get_post_status( $event_id );
				$start_display = WBCCP_CPT::format_event_datetime( $event_id );
				echo '<li class="wbccp-event-item">';
				echo '<strong>' . esc_html( get_the_title() ) . '</strong>';
				if ( $start_display ) {
					echo ' <span class="wbccp-event-date">' . esc_html( $start_display ) . '</span>';
				}
				if ( 'pending' === $status ) {
					echo ' <span class="wbccp-event-group wbccp-event-scope">' . esc_html__( 'Pending approval', 'wb-community-calendar-pro' ) . '</span>';
				}
				$event_link = get_permalink( $event_id );
				if ( $event_link ) {
					echo ' <a class="wbccp-event-view" href="' . esc_url( $event_link ) . '">' . esc_html__( 'View', 'wb-community-calendar-pro' ) . '</a>';
				}
				echo '</li>';
			}
			echo '</ul>';
			echo '</div>';
			wp_reset_postdata();
		} else {
			echo '<p>' . esc_html__( 'You are not organizing any events yet.', 'wb-community-calendar-pro' ) . '</p>';
		}
		echo '</div>';
	}

	private static function render_event_list( $event_ids, $title ) {
		echo '<div class="wbccp-my-events-section">';
		echo '<h3>' . esc_html( $title ) . '</h3>';
		if ( empty( $event_ids ) ) {
			echo '<p>' . esc_html__( 'No events yet.', 'wb-community-calendar-pro' ) . '</p>';
			echo '</div>';
			return;
		}

		$query = new WP_Query(
			array(
				'post_type'      => WBCCP_CPT::CPT,
				'post_status'    => 'publish',
				'post__in'       => $event_ids,
				'orderby'        => 'post__in',
				'posts_per_page' => 20,
			)
		);

		if ( $query->have_posts() ) {
			echo '<ul class="wbccp-events-list">';
			while ( $query->have_posts() ) {
				$query->the_post();
				$event_id = get_the_ID();
				$start_display = WBCCP_CPT::format_event_datetime( $event_id );
				echo '<li class="wbccp-event-item">';
				echo '<strong>' . esc_html( get_the_title() ) . '</strong>';
				if ( $start_display ) {
					echo ' <span class="wbccp-event-date">' . esc_html( $start_display ) . '</span>';
				}
				$event_link = get_permalink( $event_id );
				if ( $event_link ) {
					echo ' <a class="wbccp-event-view" href="' . esc_url( $event_link ) . '">' . esc_html__( 'View', 'wb-community-calendar-pro' ) . '</a>';
				}
				echo '</li>';
			}
			echo '</ul>';
			wp_reset_postdata();
		} else {
			echo '<p>' . esc_html__( 'No events yet.', 'wb-community-calendar-pro' ) . '</p>';
		}
		echo '</div>';
	}

	public static function register_group_extension() {
		if ( ! class_exists( 'BP_Group_Extension' ) ) {
			return;
		}

		bp_register_group_extension( 'WBCCP_Group_Extension' );
	}

	public static function handle_frontend_actions() {
		if ( empty( $_POST['wbccp_action'] ) ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			return;
		}

		$action = sanitize_text_field( wp_unslash( $_POST['wbccp_action'] ) );

		if ( 'create_event' === $action ) {
			self::handle_create_event();
		}

		if ( 'rsvp' === $action ) {
			self::handle_rsvp();
		}

		if ( 'delete_event' === $action ) {
			self::handle_delete_event();
		}

		if ( 'update_event' === $action ) {
			self::handle_update_event();
		}

		if ( 'approve_event' === $action ) {
			self::handle_approve_event();
		}

		if ( 'reject_event' === $action ) {
			self::handle_reject_event();
		}
	}

	private static function handle_create_event() {
		if ( empty( $_POST['wbccp_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['wbccp_nonce'] ), 'wbccp_create_event' ) ) {
			return;
		}

		$group_id = isset( $_POST['wbccp_group_id'] ) ? (int) $_POST['wbccp_group_id'] : 0;
		if ( ! $group_id ) {
			return;
		}

		if ( ! self::can_user_create_event( get_current_user_id(), $group_id ) ) {
			self::redirect_with_notice( $group_id, 'no-permission' );
			return;
		}

		$title   = isset( $_POST['wbccp_title'] ) ? sanitize_text_field( wp_unslash( $_POST['wbccp_title'] ) ) : '';
		$content = isset( $_POST['wbccp_description'] ) ? wp_kses_post( wp_unslash( $_POST['wbccp_description'] ) ) : '';

		if ( ! $title ) {
			self::redirect_with_notice( $group_id, 'missing-title' );
			return;
		}

		$timezone = isset( $_POST['wbccp_timezone'] ) ? sanitize_text_field( wp_unslash( $_POST['wbccp_timezone'] ) ) : '';
		if ( ! $timezone ) {
			$settings = WBCCP_Settings::get_settings();
			$timezone = $settings['default_timezone'];
		}

		$start_raw = isset( $_POST['wbccp_start'] ) ? sanitize_text_field( wp_unslash( $_POST['wbccp_start'] ) ) : '';
		$end_raw   = isset( $_POST['wbccp_end'] ) ? sanitize_text_field( wp_unslash( $_POST['wbccp_end'] ) ) : '';

		$start_ts = WBCCP_CPT::parse_datetime_to_utc( $start_raw, $timezone );
		$end_ts   = WBCCP_CPT::parse_datetime_to_utc( $end_raw, $timezone );
		if ( ! $start_ts ) {
			self::redirect_with_notice( $group_id, 'missing-start' );
			return;
		}
		if ( $end_ts && $end_ts < $start_ts ) {
			self::redirect_with_notice( $group_id, 'invalid-time' );
			return;
		}

		$location = isset( $_POST['wbccp_location'] ) ? sanitize_text_field( wp_unslash( $_POST['wbccp_location'] ) ) : '';
		$link     = isset( $_POST['wbccp_link'] ) ? esc_url_raw( wp_unslash( $_POST['wbccp_link'] ) ) : '';
		$capacity = isset( $_POST['wbccp_capacity'] ) ? absint( $_POST['wbccp_capacity'] ) : 0;

		$settings = WBCCP_Settings::get_settings();
		$requires_moderation = ! empty( $settings['moderation_required'] ) && ! groups_is_user_admin( get_current_user_id(), $group_id ) && ! groups_is_user_mod( get_current_user_id(), $group_id ) && ! current_user_can( 'manage_options' );
		$post_status = $requires_moderation ? 'pending' : 'publish';

		$event_id = wp_insert_post(
			array(
				'post_type'    => WBCCP_CPT::CPT,
				'post_status'  => $post_status,
				'post_title'   => $title,
				'post_content' => $content,
				'post_author'  => get_current_user_id(),
			),
			true
		);

		if ( is_wp_error( $event_id ) ) {
			return;
		}

		update_post_meta( $event_id, 'wbccp_group_id', $group_id );
		update_post_meta( $event_id, 'wbccp_start', $start_ts );
		update_post_meta( $event_id, 'wbccp_end', $end_ts );
		update_post_meta( $event_id, 'wbccp_timezone', $timezone );
		update_post_meta( $event_id, 'wbccp_location', $location );
		update_post_meta( $event_id, 'wbccp_link', $link );
		update_post_meta( $event_id, 'wbccp_capacity', $capacity );

		if ( class_exists( 'WBCCP_CPT' ) ) {
			WBCCP_CPT::save_recurrence_meta( $event_id, $_POST );
			WBCCP_CPT::save_taxonomies_from_request( $event_id, $_POST );
		}

		$upload_result = self::handle_event_image_upload( $event_id );
		if ( is_wp_error( $upload_result ) ) {
			self::redirect_with_notice( $group_id, $upload_result->get_error_code() );
			return;
		}

		if ( class_exists( 'WBCCP_Notifications' ) && 'publish' === $post_status ) {
			if ( ! empty( $settings['notify_create'] ) ) {
				WBCCP_Notifications::send_event_created( $event_id );
			}
			WBCCP_Notifications::schedule_event_reminders( $event_id );
		}

		self::redirect_with_notice( $group_id, 'created' );
		exit;
	}

	private static function handle_rsvp() {
		if ( empty( $_POST['wbccp_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['wbccp_nonce'] ), 'wbccp_rsvp' ) ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			return;
		}

		$event_id = isset( $_POST['wbccp_event_id'] ) ? (int) $_POST['wbccp_event_id'] : 0;
		$status   = isset( $_POST['wbccp_status'] ) ? sanitize_text_field( wp_unslash( $_POST['wbccp_status'] ) ) : '';

		if ( ! $event_id || ! $status ) {
			return;
		}

		$group_id = (int) get_post_meta( $event_id, 'wbccp_group_id', true );
		if ( $group_id && ! groups_is_user_member( get_current_user_id(), $group_id ) && ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$allowed = array( 'attending', 'maybe', 'cant' );
		if ( ! in_array( $status, $allowed, true ) ) {
			return;
		}

		$current_status = WBCCP_RSVP::get_status( $event_id, get_current_user_id() );
		$capacity_check = self::check_capacity_for_status( $event_id, $current_status, $status );
		if ( ! $capacity_check['allowed'] ) {
			if ( $group_id ) {
				self::redirect_with_notice( $group_id, 'full' );
			}
			$referer = wp_get_referer();
			if ( $referer ) {
				wp_safe_redirect( add_query_arg( 'wbccp_notice', 'full', $referer ) );
				exit;
			}
			return;
		}

		WBCCP_RSVP::set_status( $event_id, get_current_user_id(), $status );

		if ( $current_status !== $status && class_exists( 'WBCCP_Notifications' ) ) {
			$settings = WBCCP_Settings::get_settings();
			if ( ! empty( $settings['notify_rsvp'] ) ) {
				WBCCP_Notifications::send_rsvp( $event_id, get_current_user_id(), $status );
			}
		}

		$redirect = '';
		if ( $group_id ) {
			$redirect = bp_get_group_permalink( groups_get_group( $group_id ) ) . 'community-calendar/';
		}

		if ( ! $redirect ) {
			$referer = wp_get_referer();
			$redirect = $referer ? $referer : get_permalink( $event_id );
		}

		if ( $redirect ) {
			wp_safe_redirect( $redirect );
			exit;
		}
	}

	public static function handle_rsvp_ajax() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error(
				array( 'message' => __( 'Please log in to RSVP.', 'wb-community-calendar-pro' ) ),
				401
			);
		}

		if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'wbccp_rsvp_ajax' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Security check failed.', 'wb-community-calendar-pro' ) ),
				403
			);
		}

		$event_id = isset( $_POST['event_id'] ) ? (int) $_POST['event_id'] : 0;
		$status   = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';

		if ( ! $event_id || ! $status ) {
			wp_send_json_error(
				array( 'message' => __( 'Missing RSVP data.', 'wb-community-calendar-pro' ) ),
				400
			);
		}

		$group_id = (int) get_post_meta( $event_id, 'wbccp_group_id', true );
		if ( $group_id && ! groups_is_user_member( get_current_user_id(), $group_id ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You must be a group member to RSVP.', 'wb-community-calendar-pro' ) ),
				403
			);
		}

		$allowed = array( 'attending', 'maybe', 'cant' );
		if ( ! in_array( $status, $allowed, true ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Invalid RSVP status.', 'wb-community-calendar-pro' ) ),
				400
			);
		}

		$current_status = WBCCP_RSVP::get_status( $event_id, get_current_user_id() );
		$capacity_check = self::check_capacity_for_status( $event_id, $current_status, $status );
		if ( ! $capacity_check['allowed'] ) {
			$counts = WBCCP_RSVP::get_counts( $event_id );
			wp_send_json_error(
				array(
					'message'  => $capacity_check['message'],
					'counts'   => $counts,
					'capacity' => $capacity_check['capacity'],
				),
				409
			);
		}

		WBCCP_RSVP::set_status( $event_id, get_current_user_id(), $status );

		if ( $current_status !== $status && class_exists( 'WBCCP_Notifications' ) ) {
			$settings = WBCCP_Settings::get_settings();
			if ( ! empty( $settings['notify_rsvp'] ) ) {
				WBCCP_Notifications::send_rsvp( $event_id, get_current_user_id(), $status );
			}
		}

		$counts = WBCCP_RSVP::get_counts( $event_id );
		$capacity = (int) get_post_meta( $event_id, 'wbccp_capacity', true );

		wp_send_json_success(
			array(
				'message' => $current_status === $status ? __( 'RSVP already set.', 'wb-community-calendar-pro' ) : __( 'RSVP updated.', 'wb-community-calendar-pro' ),
				'status'  => $status,
				'counts'  => $counts,
				'capacity' => $capacity,
			)
		);
	}

	private static function check_capacity_for_status( $event_id, $current_status, $new_status ) {
		$capacity = (int) get_post_meta( $event_id, 'wbccp_capacity', true );
		$counted = array( 'attending', 'maybe' );

		if ( $capacity <= 0 ) {
			return array(
				'allowed'  => true,
				'message'  => '',
				'capacity' => $capacity,
			);
		}

		if ( ! in_array( $new_status, $counted, true ) ) {
			return array(
				'allowed'  => true,
				'message'  => '',
				'capacity' => $capacity,
			);
		}

		if ( in_array( $current_status, $counted, true ) ) {
			return array(
				'allowed'  => true,
				'message'  => '',
				'capacity' => $capacity,
			);
		}

		$counts = WBCCP_RSVP::get_counts( $event_id );
		$total = (int) $counts['attending'] + (int) $counts['maybe'];

		if ( $total >= $capacity ) {
			return array(
				'allowed'  => false,
				'message'  => __( 'This event is full.', 'wb-community-calendar-pro' ),
				'capacity' => $capacity,
			);
		}

		return array(
			'allowed'  => true,
			'message'  => '',
			'capacity' => $capacity,
		);
	}

	private static function handle_delete_event() {
		if ( empty( $_POST['wbccp_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['wbccp_nonce'] ), 'wbccp_delete_event' ) ) {
			return;
		}

		$event_id = isset( $_POST['wbccp_event_id'] ) ? (int) $_POST['wbccp_event_id'] : 0;
		if ( ! $event_id || ! current_user_can( 'delete_post', $event_id ) ) {
			return;
		}

		$group_id = (int) get_post_meta( $event_id, 'wbccp_group_id', true );
		if ( $group_id && ! groups_is_user_member( get_current_user_id(), $group_id ) && ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$title = get_the_title( $event_id );
		if ( class_exists( 'WBCCP_Notifications' ) ) {
			WBCCP_Notifications::clear_scheduled_reminders( $event_id );
		}
		wp_delete_post( $event_id, true );
		WBCCP_RSVP::delete_by_event_ids( array( $event_id ) );

		if ( class_exists( 'WBCCP_Notifications' ) ) {
			$settings = WBCCP_Settings::get_settings();
			if ( ! empty( $settings['notify_cancel'] ) ) {
				WBCCP_Notifications::send_event_deleted( $event_id, $group_id, $title );
			}
		}

		if ( $group_id ) {
			self::redirect_with_notice( $group_id, 'deleted' );
			exit;
		}
	}

	private static function handle_update_event() {
		if ( empty( $_POST['wbccp_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['wbccp_nonce'] ), 'wbccp_update_event' ) ) {
			return;
		}

		$event_id = isset( $_POST['wbccp_event_id'] ) ? (int) $_POST['wbccp_event_id'] : 0;
		if ( ! $event_id || ! current_user_can( 'edit_post', $event_id ) ) {
			return;
		}

		$group_id = (int) get_post_meta( $event_id, 'wbccp_group_id', true );
		if ( ! $group_id && ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( $group_id && ! self::can_user_create_event( get_current_user_id(), $group_id ) ) {
			self::redirect_with_notice( $group_id, 'no-permission' );
			return;
		}

		$title   = isset( $_POST['wbccp_title'] ) ? sanitize_text_field( wp_unslash( $_POST['wbccp_title'] ) ) : '';
		$content = isset( $_POST['wbccp_description'] ) ? wp_kses_post( wp_unslash( $_POST['wbccp_description'] ) ) : '';

		if ( ! $title ) {
			self::redirect_with_notice( $group_id, 'missing-title' );
			return;
		}

		wp_update_post(
			array(
				'ID'           => $event_id,
				'post_title'   => $title,
				'post_content' => $content,
			)
		);

		$timezone = isset( $_POST['wbccp_timezone'] ) ? sanitize_text_field( wp_unslash( $_POST['wbccp_timezone'] ) ) : '';
		if ( ! $timezone ) {
			$settings = WBCCP_Settings::get_settings();
			$timezone = $settings['default_timezone'];
		}

		$start_raw = isset( $_POST['wbccp_start'] ) ? sanitize_text_field( wp_unslash( $_POST['wbccp_start'] ) ) : '';
		$end_raw   = isset( $_POST['wbccp_end'] ) ? sanitize_text_field( wp_unslash( $_POST['wbccp_end'] ) ) : '';

		$start_ts = WBCCP_CPT::parse_datetime_to_utc( $start_raw, $timezone );
		$end_ts   = WBCCP_CPT::parse_datetime_to_utc( $end_raw, $timezone );
		if ( ! $start_ts ) {
			self::redirect_with_notice( $group_id, 'missing-start' );
			return;
		}
		if ( $end_ts && $end_ts < $start_ts ) {
			self::redirect_with_notice( $group_id, 'invalid-time' );
			return;
		}

		update_post_meta( $event_id, 'wbccp_start', $start_ts );
		update_post_meta( $event_id, 'wbccp_end', $end_ts );
		update_post_meta( $event_id, 'wbccp_timezone', $timezone );

		$location = isset( $_POST['wbccp_location'] ) ? sanitize_text_field( wp_unslash( $_POST['wbccp_location'] ) ) : '';
		update_post_meta( $event_id, 'wbccp_location', $location );

		$link = isset( $_POST['wbccp_link'] ) ? esc_url_raw( wp_unslash( $_POST['wbccp_link'] ) ) : '';
		update_post_meta( $event_id, 'wbccp_link', $link );

		$capacity = isset( $_POST['wbccp_capacity'] ) ? absint( $_POST['wbccp_capacity'] ) : 0;
		update_post_meta( $event_id, 'wbccp_capacity', $capacity );

		if ( class_exists( 'WBCCP_CPT' ) ) {
			WBCCP_CPT::save_recurrence_meta( $event_id, $_POST );
			WBCCP_CPT::save_taxonomies_from_request( $event_id, $_POST );
		}

		$upload_result = self::handle_event_image_upload( $event_id );
		if ( is_wp_error( $upload_result ) ) {
			self::redirect_with_notice( $group_id, $upload_result->get_error_code() );
			return;
		}

		if ( class_exists( 'WBCCP_Notifications' ) ) {
			$settings = WBCCP_Settings::get_settings();
			if ( ! empty( $settings['notify_update'] ) ) {
				WBCCP_Notifications::send_event_updated( $event_id );
			}
			WBCCP_Notifications::schedule_event_reminders( $event_id );
		}

		if ( $group_id ) {
			self::redirect_with_notice( $group_id, 'updated' );
			exit;
		}

		$redirect = wp_get_referer();
		if ( $redirect ) {
			wp_safe_redirect( $redirect );
			exit;
		}
	}

	private static function handle_approve_event() {
		if ( empty( $_POST['wbccp_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['wbccp_nonce'] ), 'wbccp_approve_event' ) ) {
			return;
		}

		$event_id = isset( $_POST['wbccp_event_id'] ) ? (int) $_POST['wbccp_event_id'] : 0;
		if ( ! $event_id ) {
			return;
		}

		$group_id = (int) get_post_meta( $event_id, 'wbccp_group_id', true );
		if ( ! self::can_user_moderate_group( get_current_user_id(), $group_id ) ) {
			return;
		}

		wp_update_post(
			array(
				'ID'          => $event_id,
				'post_status' => 'publish',
			)
		);

		if ( class_exists( 'WBCCP_Notifications' ) ) {
			$settings = WBCCP_Settings::get_settings();
			if ( ! empty( $settings['notify_create'] ) ) {
				WBCCP_Notifications::send_event_created( $event_id );
			}
			WBCCP_Notifications::schedule_event_reminders( $event_id );
		}

		if ( $group_id ) {
			self::redirect_with_notice( $group_id, 'approved' );
			exit;
		}
	}

	private static function handle_reject_event() {
		if ( empty( $_POST['wbccp_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['wbccp_nonce'] ), 'wbccp_reject_event' ) ) {
			return;
		}

		$event_id = isset( $_POST['wbccp_event_id'] ) ? (int) $_POST['wbccp_event_id'] : 0;
		if ( ! $event_id ) {
			return;
		}

		$group_id = (int) get_post_meta( $event_id, 'wbccp_group_id', true );
		if ( ! self::can_user_moderate_group( get_current_user_id(), $group_id ) ) {
			return;
		}

		wp_delete_post( $event_id, true );

		if ( $group_id ) {
			self::redirect_with_notice( $group_id, 'rejected' );
			exit;
		}
	}

	public static function can_user_create_event( $user_id, $group_id ) {
		if ( groups_is_user_admin( $user_id, $group_id ) || groups_is_user_mod( $user_id, $group_id ) ) {
			return true;
		}

		$settings = WBCCP_Settings::get_settings();
		if ( ! empty( $settings['allow_member_events'] ) && groups_is_user_member( $user_id, $group_id ) ) {
			return true;
		}

		return false;
	}

	public static function can_user_moderate_group( $user_id, $group_id ) {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		return $group_id && ( groups_is_user_admin( $user_id, $group_id ) || groups_is_user_mod( $user_id, $group_id ) );
	}

	private static function handle_event_image_upload( $event_id ) {
		if ( empty( $_FILES['wbccp_image'] ) || empty( $_FILES['wbccp_image']['name'] ) ) {
			return true;
		}

		if ( ! current_user_can( 'upload_files' ) ) {
			return new WP_Error( 'upload-permission', __( 'You do not have permission to upload files.', 'wb-community-calendar-pro' ) );
		}

		if ( ! empty( $_FILES['wbccp_image']['error'] ) ) {
			return new WP_Error( 'upload-error', __( 'There was an error uploading the image.', 'wb-community-calendar-pro' ) );
		}

		if ( ! empty( $_FILES['wbccp_image']['size'] ) && (int) $_FILES['wbccp_image']['size'] > 5 * MB_IN_BYTES ) {
			return new WP_Error( 'upload-size', __( 'Image is too large. Maximum size is 5MB.', 'wb-community-calendar-pro' ) );
		}

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$file = $_FILES['wbccp_image'];
		$upload = wp_handle_upload( $file, array( 'test_form' => false ) );
		if ( empty( $upload['file'] ) ) {
			return new WP_Error( 'upload-error', __( 'There was an error uploading the image.', 'wb-community-calendar-pro' ) );
		}

		$allowed_mimes = array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'png'          => 'image/png',
			'gif'          => 'image/gif',
			'webp'         => 'image/webp',
		);
		$check = wp_check_filetype_and_ext( $upload['file'], $file['name'], $allowed_mimes );
		if ( empty( $check['type'] ) || empty( $check['ext'] ) ) {
			wp_delete_file( $upload['file'] );
			return new WP_Error( 'upload-type', __( 'Only image files are allowed.', 'wb-community-calendar-pro' ) );
		}

		$filetype = wp_check_filetype( $upload['file'], $allowed_mimes );
		$attachment = array(
			'post_mime_type' => $filetype['type'],
			'post_title'     => sanitize_file_name( wp_basename( $upload['file'] ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		$attach_id = wp_insert_attachment( $attachment, $upload['file'], $event_id );
		if ( ! $attach_id ) {
			return new WP_Error( 'upload-error', __( 'Could not save the uploaded image.', 'wb-community-calendar-pro' ) );
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		$attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
		wp_update_attachment_metadata( $attach_id, $attach_data );

		set_post_thumbnail( $event_id, $attach_id );
		return true;
	}

	private static function redirect_with_notice( $group_id, $notice ) {
		if ( ! $group_id ) {
			return;
		}

		$base_url = bp_get_group_permalink( groups_get_group( $group_id ) ) . 'community-calendar/';
		wp_safe_redirect( add_query_arg( 'wbccp_notice', sanitize_key( $notice ), $base_url ) );
		exit;
	}

	public static function render_taxonomy_fields( $selected_categories = array(), $tag_value = '' ) {
		if ( ! class_exists( 'WBCCP_CPT' ) ) {
			return;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => WBCCP_CPT::TAX_CATEGORY,
				'hide_empty' => false,
			)
		);

		if ( ! is_array( $selected_categories ) ) {
			$selected_categories = array();
		}

		echo '<p><label>' . esc_html__( 'Event Categories', 'wb-community-calendar-pro' ) . '</label><br />';
		echo '<select name="wbccp_categories[]" multiple class="wbccp-taxonomy-select">';
		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$selected = in_array( $term->term_id, $selected_categories, true ) ? ' selected' : '';
				echo '<option value="' . esc_attr( $term->term_id ) . '"' . $selected . '>' . esc_html( $term->name ) . '</option>';
			}
		}
		echo '</select></p>';

		echo '<p><label>' . esc_html__( 'Event Tags', 'wb-community-calendar-pro' ) . '</label><br />';
		echo '<input type="text" name="wbccp_tags" class="regular-text" value="' . esc_attr( $tag_value ) . '" />';
		echo '<small class="wbccp-form-note">' . esc_html__( 'Comma-separated tags (e.g., meetup, online).', 'wb-community-calendar-pro' ) . '</small>';
		echo '</p>';
	}

	public static function render_recurrence_fields( $data = array() ) {
		$defaults = array(
			'enabled'    => 0,
			'freq'       => '',
			'interval'   => 1,
			'byday'      => array(),
			'bymonthday' => 0,
			'bysetpos'   => 0,
			'byweekday'  => '',
			'count'      => 0,
			'until'      => '',
		);
		$data = wp_parse_args( $data, $defaults );
		if ( ! is_array( $data['byday'] ) ) {
			$data['byday'] = $data['byday'] ? array( $data['byday'] ) : array();
		}
		if ( empty( $data['bymonthday'] ) || (int) $data['bymonthday'] < 1 ) {
			$data['bymonthday'] = '';
		}
		if ( empty( $data['count'] ) || (int) $data['count'] < 1 ) {
			$data['count'] = '';
		}

		$weekdays = array( 'MO' => __( 'Mon', 'wb-community-calendar-pro' ), 'TU' => __( 'Tue', 'wb-community-calendar-pro' ), 'WE' => __( 'Wed', 'wb-community-calendar-pro' ), 'TH' => __( 'Thu', 'wb-community-calendar-pro' ), 'FR' => __( 'Fri', 'wb-community-calendar-pro' ), 'SA' => __( 'Sat', 'wb-community-calendar-pro' ), 'SU' => __( 'Sun', 'wb-community-calendar-pro' ) );

		echo '<fieldset class="wbccp-recurrence-fields">';
		echo '<legend>' . esc_html__( 'Recurrence', 'wb-community-calendar-pro' ) . '</legend>';
		echo '<p><label><input type="checkbox" class="wbccp-recur-enabled" name="wbccp_recur_enabled" value="1"' . checked( 1, $data['enabled'], false ) . ' /> ' . esc_html__( 'Repeat this event', 'wb-community-calendar-pro' ) . '</label></p>';
		echo '<div class="wbccp-recur-details">';
		echo '<p><label>' . esc_html__( 'Frequency', 'wb-community-calendar-pro' ) . '</label><br />';
		echo '<select name="wbccp_recur_freq" class="wbccp-recur-freq">';
		echo '<option value="">' . esc_html__( 'Select', 'wb-community-calendar-pro' ) . '</option>';
		echo '<option value="DAILY"' . selected( $data['freq'], 'DAILY', false ) . '>' . esc_html__( 'Daily', 'wb-community-calendar-pro' ) . '</option>';
		echo '<option value="WEEKLY"' . selected( $data['freq'], 'WEEKLY', false ) . '>' . esc_html__( 'Weekly', 'wb-community-calendar-pro' ) . '</option>';
		echo '<option value="MONTHLY"' . selected( $data['freq'], 'MONTHLY', false ) . '>' . esc_html__( 'Monthly', 'wb-community-calendar-pro' ) . '</option>';
		echo '<option value="YEARLY"' . selected( $data['freq'], 'YEARLY', false ) . '>' . esc_html__( 'Yearly', 'wb-community-calendar-pro' ) . '</option>';
		echo '</select></p>';
		echo '<p><label>' . esc_html__( 'Repeat every', 'wb-community-calendar-pro' ) . '</label><br /><input type="number" class="wbccp-recur-interval" min="1" name="wbccp_recur_interval" value="' . esc_attr( $data['interval'] ? $data['interval'] : 1 ) . '" /></p>';
		echo '<div class="wbccp-recur-weekly">';
		echo '<p><label>' . esc_html__( 'Repeat on', 'wb-community-calendar-pro' ) . '</label><br />';
		foreach ( $weekdays as $code => $label ) {
			echo '<label style="margin-right:8px;"><input type="checkbox" class="wbccp-recur-byday" name="wbccp_recur_byday[]" value="' . esc_attr( $code ) . '"' . checked( in_array( $code, $data['byday'], true ), true, false ) . ' /> ' . esc_html( $label ) . '</label>';
		}
		echo '</p>';
		echo '</div>';
		echo '<div class="wbccp-recur-monthly">';
		echo '<p class="wbccp-recur-bymonthday-field"><label>' . esc_html__( 'Day of month', 'wb-community-calendar-pro' ) . '</label><br /><input type="number" class="wbccp-recur-bymonthday" min="1" max="31" name="wbccp_recur_bymonthday" value="' . esc_attr( $data['bymonthday'] ) . '" /></p>';
		echo '<p class="wbccp-recur-nth"><label>' . esc_html__( 'Or nth weekday', 'wb-community-calendar-pro' ) . '</label><br />';
		echo '<select name="wbccp_recur_bysetpos" class="wbccp-recur-bysetpos">';
		echo '<option value="">' . esc_html__( 'Select', 'wb-community-calendar-pro' ) . '</option>';
		echo '<option value="1"' . selected( $data['bysetpos'], 1, false ) . '>' . esc_html__( 'First', 'wb-community-calendar-pro' ) . '</option>';
		echo '<option value="2"' . selected( $data['bysetpos'], 2, false ) . '>' . esc_html__( 'Second', 'wb-community-calendar-pro' ) . '</option>';
		echo '<option value="3"' . selected( $data['bysetpos'], 3, false ) . '>' . esc_html__( 'Third', 'wb-community-calendar-pro' ) . '</option>';
		echo '<option value="4"' . selected( $data['bysetpos'], 4, false ) . '>' . esc_html__( 'Fourth', 'wb-community-calendar-pro' ) . '</option>';
		echo '<option value="-1"' . selected( $data['bysetpos'], -1, false ) . '>' . esc_html__( 'Last', 'wb-community-calendar-pro' ) . '</option>';
		echo '</select> ';
		echo '<select name="wbccp_recur_byweekday" class="wbccp-recur-byweekday">';
		echo '<option value="">' . esc_html__( 'Weekday', 'wb-community-calendar-pro' ) . '</option>';
		foreach ( $weekdays as $code => $label ) {
			echo '<option value="' . esc_attr( $code ) . '"' . selected( $data['byweekday'], $code, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select></p>';
		echo '</div>';
		echo '<div class="wbccp-recur-yearly"></div>';
		echo '<p><label>' . esc_html__( 'Occurrences (count)', 'wb-community-calendar-pro' ) . '</label><br /><input type="number" class="wbccp-recur-count" min="1" name="wbccp_recur_count" value="' . esc_attr( $data['count'] ) . '" /></p>';
		echo '<p><label>' . esc_html__( 'Repeat until', 'wb-community-calendar-pro' ) . '</label><br /><input type="date" class="wbccp-recur-until" name="wbccp_recur_until" value="' . esc_attr( $data['until'] ) . '" /></p>';
		echo '</div>';
		echo '</fieldset>';
	}
}

class WBCCP_Group_Extension extends BP_Group_Extension {
	public function __construct() {
		$args = array(
			'slug'              => 'community-calendar',
			'name'              => __( 'Events', 'wb-community-calendar-pro' ),
			'nav_item_position' => 35,
			'enable_create_step'=> false,
			'enable_edit_item'  => false,
			'show_tab'          => true,
		);

		parent::init( $args );
	}

	public function display( $group_id = null ) {
		if ( ! $group_id && function_exists( 'bp_get_current_group_id' ) ) {
			$group_id = bp_get_current_group_id();
		}

		if ( ! $group_id ) {
			echo esc_html__( 'Group not found.', 'wb-community-calendar-pro' );
			return;
		}

		if ( ! groups_is_user_member( get_current_user_id(), $group_id ) && ! current_user_can( 'manage_options' ) ) {
			echo esc_html__( 'You must be a group member to view events.', 'wb-community-calendar-pro' );
			return;
		}

		$current_event_id = isset( $_GET['wbccp_event'] ) ? absint( $_GET['wbccp_event'] ) : 0;
		$base_url = bp_get_group_permalink( groups_get_group( $group_id ) ) . 'community-calendar/';
		$can_create = WBCCP_BP::can_user_create_event( get_current_user_id(), $group_id );
		$view = WBCCP_Views::get_view();
		$settings = WBCCP_Settings::get_settings();
		$show_viewer_timezone = ! empty( $settings['show_viewer_timezone'] );
		echo '<div class="wbccp-group-events wbccp-view-container" data-default-view="' . esc_attr( $view ) . '">';
		echo '<div class="wbccp-events-top">';
		echo '<div class="wbccp-events-heading">';
		echo '<h2>' . esc_html__( 'Group Events', 'wb-community-calendar-pro' ) . '</h2>';
		echo '</div>';
		echo '<div class="wbccp-events-actions">';
		$ical_url = add_query_arg(
			array(
				'wbccp_ical' => 1,
				'group_id'   => $group_id,
			),
			home_url( '/' )
		);
		echo '<a class="button wbccp-ical-link" href="' . esc_url( $ical_url ) . '">' . esc_html__( 'Export iCal', 'wb-community-calendar-pro' ) . '</a>';
		if ( $can_create ) {
			echo '<a class="button button-primary wbccp-create-shortcut" href="' . esc_url( $base_url ) . '#wbccp-create">' . esc_html__( 'Create Event', 'wb-community-calendar-pro' ) . '</a>';
		}
		echo '</div>';
		echo '</div>';

		$notice = isset( $_GET['wbccp_notice'] ) ? sanitize_key( wp_unslash( $_GET['wbccp_notice'] ) ) : '';
		if ( $notice ) {
			$messages = array(
				'created'         => __( 'Event created successfully.', 'wb-community-calendar-pro' ),
				'updated'         => __( 'Event updated successfully.', 'wb-community-calendar-pro' ),
				'deleted'         => __( 'Event deleted.', 'wb-community-calendar-pro' ),
				'approved'        => __( 'Event approved.', 'wb-community-calendar-pro' ),
				'rejected'        => __( 'Event rejected.', 'wb-community-calendar-pro' ),
				'missing-title'   => __( 'Please enter an event title.', 'wb-community-calendar-pro' ),
				'missing-start'   => __( 'Please provide a start date/time.', 'wb-community-calendar-pro' ),
				'invalid-time'    => __( 'End time must be after the start time.', 'wb-community-calendar-pro' ),
				'upload-error'    => __( 'There was an error uploading the image.', 'wb-community-calendar-pro' ),
				'upload-type'     => __( 'Only image files are allowed.', 'wb-community-calendar-pro' ),
				'upload-size'     => __( 'Image is too large. Maximum size is 5MB.', 'wb-community-calendar-pro' ),
				'upload-permission' => __( 'You do not have permission to upload files.', 'wb-community-calendar-pro' ),
				'no-permission'   => __( 'You do not have permission to perform this action.', 'wb-community-calendar-pro' ),
				'full'            => __( 'This event is full.', 'wb-community-calendar-pro' ),
			);
			if ( isset( $messages[ $notice ] ) ) {
				$success = in_array( $notice, array( 'created', 'updated', 'deleted', 'approved', 'rejected' ), true );
				$notice_class = $success ? 'wbccp-form-notice wbccp-form-notice--success' : 'wbccp-form-notice wbccp-form-notice--error';
				echo '<div class="' . esc_attr( $notice_class ) . '" data-wbccp-notice data-dismissible="true">';
				echo '<span>' . esc_html( $messages[ $notice ] ) . '</span>';
				echo '<button type="button" class="wbccp-notice-dismiss" aria-label="' . esc_attr__( 'Dismiss notice', 'wb-community-calendar-pro' ) . '">×</button>';
				echo '</div>';
			}
		}

		echo '<div class="wbccp-view-toggle-wrap">';
		$panel_ids = WBCCP_Views::render_view_toggle( $base_url );
		echo '</div>';
		$edit_event_id = isset( $_GET['wbccp_edit'] ) ? absint( $_GET['wbccp_edit'] ) : 0;
		$editing_event = $edit_event_id && current_user_can( 'edit_post', $edit_event_id ) ? get_post( $edit_event_id ) : null;

		ob_start();
		if ( $can_create ) {
			echo '<details class="wbccp-accordion wbccp-event-form" id="wbccp-create">';
			echo '<summary class="wbccp-accordion__summary">';
			echo '<span>' . esc_html__( 'Create Event', 'wb-community-calendar-pro' ) . '</span>';
			echo '<span class="wbccp-accordion__hint">' . esc_html__( 'Add details for your next event', 'wb-community-calendar-pro' ) . '</span>';
			echo '</summary>';
			echo '<div class="wbccp-accordion__content">';
			echo '<h3>' . esc_html__( 'Event Details', 'wb-community-calendar-pro' ) . '</h3>';
			echo '<form method="post" enctype="multipart/form-data">';
			echo '<input type="hidden" name="wbccp_action" value="create_event" />';
			echo '<input type="hidden" name="wbccp_group_id" value="' . esc_attr( $group_id ) . '" />';
			echo '<p><label>' . esc_html__( 'Title', 'wb-community-calendar-pro' ) . '</label><br /><input type="text" name="wbccp_title" class="regular-text" required /></p>';
			echo '<p><label>' . esc_html__( 'Description', 'wb-community-calendar-pro' ) . '</label><br /><textarea name="wbccp_description" rows="4" class="large-text"></textarea></p>';
			echo '<p><label>' . esc_html__( 'Start', 'wb-community-calendar-pro' ) . '</label><br /><input type="datetime-local" name="wbccp_start" required /></p>';
			echo '<p><label>' . esc_html__( 'End', 'wb-community-calendar-pro' ) . '</label><br /><input type="datetime-local" name="wbccp_end" /></p>';
			echo '<p><label>' . esc_html__( 'Timezone', 'wb-community-calendar-pro' ) . '</label><br /><input type="text" name="wbccp_timezone" value="' . esc_attr( $settings['default_timezone'] ) . '" class="regular-text" /></p>';
			echo '<p><label>' . esc_html__( 'Location', 'wb-community-calendar-pro' ) . '</label><br /><input type="text" name="wbccp_location" class="regular-text" /></p>';
			echo '<p><label>' . esc_html__( 'Meeting Link', 'wb-community-calendar-pro' ) . '</label><br /><input type="url" name="wbccp_link" class="regular-text" /></p>';
			echo '<p><label>' . esc_html__( 'Capacity', 'wb-community-calendar-pro' ) . '</label><br /><input type="number" name="wbccp_capacity" min="0" class="small-text" /></p>';
			WBCCP_BP::render_taxonomy_fields();
			echo '<p><label>' . esc_html__( 'Event Image', 'wb-community-calendar-pro' ) . '</label><br /><input type="file" name="wbccp_image" accept="image/*" /></p>';
			WBCCP_BP::render_recurrence_fields();
			wp_nonce_field( 'wbccp_create_event', 'wbccp_nonce' );
			echo '<p><button type="submit" class="button button-primary">' . esc_html__( 'Create Event', 'wb-community-calendar-pro' ) . '</button></p>';
			echo '</form>';
			echo '</div>';
			echo '</details>';
		}

		if ( $editing_event && $can_create ) {
			$edit_start = get_post_meta( $editing_event->ID, 'wbccp_start', true );
			$edit_end = get_post_meta( $editing_event->ID, 'wbccp_end', true );
			$edit_timezone = get_post_meta( $editing_event->ID, 'wbccp_timezone', true );
			$edit_location = get_post_meta( $editing_event->ID, 'wbccp_location', true );
			$edit_link = get_post_meta( $editing_event->ID, 'wbccp_link', true );
			$edit_capacity = (int) get_post_meta( $editing_event->ID, 'wbccp_capacity', true );
			$edit_categories = wp_get_post_terms( $editing_event->ID, WBCCP_CPT::TAX_CATEGORY, array( 'fields' => 'ids' ) );
			$edit_tags = wp_get_post_terms( $editing_event->ID, WBCCP_CPT::TAX_TAG, array( 'fields' => 'names' ) );
			$edit_tag_value = $edit_tags ? implode( ', ', $edit_tags ) : '';
			if ( ! $edit_timezone ) {
				$edit_timezone = $settings['default_timezone'];
			}

			echo '<div class="wbccp-event-form wbccp-event-edit-form" id="wbccp-edit">';
			echo '<h3>' . esc_html__( 'Edit Event', 'wb-community-calendar-pro' ) . '</h3>';
			echo '<form method="post" enctype="multipart/form-data">';
			echo '<input type="hidden" name="wbccp_action" value="update_event" />';
			echo '<input type="hidden" name="wbccp_event_id" value="' . esc_attr( $editing_event->ID ) . '" />';
			echo '<p><label>' . esc_html__( 'Title', 'wb-community-calendar-pro' ) . '</label><br /><input type="text" name="wbccp_title" class="regular-text" value="' . esc_attr( $editing_event->post_title ) . '" required /></p>';
			echo '<p><label>' . esc_html__( 'Description', 'wb-community-calendar-pro' ) . '</label><br /><textarea name="wbccp_description" rows="4" class="large-text">' . esc_textarea( $editing_event->post_content ) . '</textarea></p>';
			echo '<p><label>' . esc_html__( 'Start', 'wb-community-calendar-pro' ) . '</label><br /><input type="datetime-local" name="wbccp_start" value="' . esc_attr( $edit_start ? gmdate( 'Y-m-d\\TH:i', (int) $edit_start ) : '' ) . '" required /></p>';
			echo '<p><label>' . esc_html__( 'End', 'wb-community-calendar-pro' ) . '</label><br /><input type="datetime-local" name="wbccp_end" value="' . esc_attr( $edit_end ? gmdate( 'Y-m-d\\TH:i', (int) $edit_end ) : '' ) . '" /></p>';
			echo '<p><label>' . esc_html__( 'Timezone', 'wb-community-calendar-pro' ) . '</label><br /><input type="text" name="wbccp_timezone" value="' . esc_attr( $edit_timezone ) . '" class="regular-text" /></p>';
			echo '<p><label>' . esc_html__( 'Location', 'wb-community-calendar-pro' ) . '</label><br /><input type="text" name="wbccp_location" class="regular-text" value="' . esc_attr( $edit_location ) . '" /></p>';
			echo '<p><label>' . esc_html__( 'Meeting Link', 'wb-community-calendar-pro' ) . '</label><br /><input type="url" name="wbccp_link" class="regular-text" value="' . esc_attr( $edit_link ) . '" /></p>';
			echo '<p><label>' . esc_html__( 'Capacity', 'wb-community-calendar-pro' ) . '</label><br /><input type="number" name="wbccp_capacity" min="0" class="small-text" value="' . esc_attr( $edit_capacity ) . '" /></p>';
			WBCCP_BP::render_taxonomy_fields( $edit_categories, $edit_tag_value );
			echo '<p><label>' . esc_html__( 'Event Image', 'wb-community-calendar-pro' ) . '</label><br /><input type="file" name="wbccp_image" accept="image/*" /></p>';
			WBCCP_BP::render_recurrence_fields(
				array(
					'enabled'    => (int) get_post_meta( $editing_event->ID, 'wbccp_recur_enabled', true ),
					'freq'       => get_post_meta( $editing_event->ID, 'wbccp_recur_freq', true ),
					'interval'   => (int) get_post_meta( $editing_event->ID, 'wbccp_recur_interval', true ),
					'byday'      => get_post_meta( $editing_event->ID, 'wbccp_recur_byday', true ),
					'bymonthday' => (int) get_post_meta( $editing_event->ID, 'wbccp_recur_bymonthday', true ),
					'bysetpos'   => (int) get_post_meta( $editing_event->ID, 'wbccp_recur_bysetpos', true ),
					'byweekday'  => get_post_meta( $editing_event->ID, 'wbccp_recur_byweekday', true ),
					'count'      => (int) get_post_meta( $editing_event->ID, 'wbccp_recur_count', true ),
					'until'      => get_post_meta( $editing_event->ID, 'wbccp_recur_until', true ),
				)
			);
			wp_nonce_field( 'wbccp_update_event', 'wbccp_nonce' );
			echo '<p><button type="submit" class="button button-primary">' . esc_html__( 'Update Event', 'wb-community-calendar-pro' ) . '</button></p>';
			echo '</form>';
			echo '</div>';
		}

		$can_moderate = WBCCP_BP::can_user_moderate_group( get_current_user_id(), $group_id );
		if ( $can_moderate ) {
			$pending_events = new WP_Query(
				array(
					'post_type'      => WBCCP_CPT::CPT,
					'post_status'    => 'pending',
					'posts_per_page' => 20,
					'meta_query'     => array(
						array(
							'key'   => 'wbccp_group_id',
							'value' => (int) $group_id,
						),
					),
				)
			);

			if ( $pending_events->have_posts() ) {
				echo '<div class="wbccp-pending-events">';
				echo '<h3>' . esc_html__( 'Pending Approval', 'wb-community-calendar-pro' ) . '</h3>';
				echo '<ul>';
				while ( $pending_events->have_posts() ) {
					$pending_events->the_post();
					$pending_id = get_the_ID();
					$author = get_user_by( 'id', get_post_field( 'post_author', $pending_id ) );
					$author_name = $author ? $author->display_name : esc_html__( 'Member', 'wb-community-calendar-pro' );
					echo '<li>';
					echo '<strong>' . esc_html( get_the_title() ) . '</strong>';
					echo ' <span class="wbccp-pending-author">' . esc_html( $author_name ) . '</span>';
					echo '<div class="wbccp-pending-actions">';
					echo '<form method="post" style="display:inline-block;margin-right:8px;">';
					echo '<input type="hidden" name="wbccp_action" value="approve_event" />';
					echo '<input type="hidden" name="wbccp_event_id" value="' . esc_attr( $pending_id ) . '" />';
					wp_nonce_field( 'wbccp_approve_event', 'wbccp_nonce' );
					echo '<button type="submit" class="button button-primary">' . esc_html__( 'Approve', 'wb-community-calendar-pro' ) . '</button>';
					echo '</form>';
					echo '<form method="post" style="display:inline-block;">';
					echo '<input type="hidden" name="wbccp_action" value="reject_event" />';
					echo '<input type="hidden" name="wbccp_event_id" value="' . esc_attr( $pending_id ) . '" />';
					wp_nonce_field( 'wbccp_reject_event', 'wbccp_nonce' );
					echo '<button type="submit" class="button">' . esc_html__( 'Reject', 'wb-community-calendar-pro' ) . '</button>';
					echo '</form>';
					echo '</div>';
					echo '</li>';
				}
				echo '</ul>';
				echo '</div>';
				wp_reset_postdata();
			}
		}

		$deferred_sections = ob_get_clean();

		$tax_query = WBCCP_CPT::get_tax_query_from_request();

		echo $deferred_sections;
		echo '<div class="wbccp-view-panels">';

		echo '<div class="wbccp-view-panel wbccp-view-panel--month' . ( 'month' === $view ? ' is-active' : '' ) . '" data-view="month" id="' . esc_attr( $panel_ids['month'] ) . '">';
		WBCCP_Views::render_month_view( $group_id, $base_url, $can_create, 'group', $tax_query );
		echo '</div>';

		echo '<div class="wbccp-view-panel wbccp-view-panel--list' . ( 'list' === $view ? ' is-active' : '' ) . '" data-view="list" id="' . esc_attr( $panel_ids['list'] ) . '">';
		$filter = isset( $_GET['wbccp_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['wbccp_filter'] ) ) : 'upcoming';
		if ( ! in_array( $filter, array( 'upcoming', 'past', 'all' ), true ) ) {
			$filter = 'upcoming';
		}
			$search = isset( $_GET['wbccp_search'] ) ? sanitize_text_field( wp_unslash( $_GET['wbccp_search'] ) ) : '';
			$page = isset( $_GET['wbccp_page'] ) ? max( 1, absint( $_GET['wbccp_page'] ) ) : 1;
			$per_page = 10;

			$category_terms = get_terms(
				array(
					'taxonomy'   => WBCCP_CPT::TAX_CATEGORY,
					'hide_empty' => false,
				)
			);
			$selected_category = isset( $_GET['wbccp_category'] ) ? absint( $_GET['wbccp_category'] ) : 0;
			$selected_tag = isset( $_GET['wbccp_tag'] ) ? sanitize_text_field( wp_unslash( $_GET['wbccp_tag'] ) ) : '';

			echo '<form class="wbccp-filters" method="get">';
			echo '<input type="hidden" name="wbccp_view" value="list" />';
			echo '<input type="hidden" name="wbccp_page" value="1" />';
			echo '<select name="wbccp_filter">';
			echo '<option value="upcoming"' . selected( $filter, 'upcoming', false ) . '>' . esc_html__( 'Upcoming', 'wb-community-calendar-pro' ) . '</option>';
			echo '<option value="past"' . selected( $filter, 'past', false ) . '>' . esc_html__( 'Past', 'wb-community-calendar-pro' ) . '</option>';
			echo '<option value="all"' . selected( $filter, 'all', false ) . '>' . esc_html__( 'All', 'wb-community-calendar-pro' ) . '</option>';
			echo '</select> ';
			echo '<select name="wbccp_category">';
			echo '<option value="">' . esc_html__( 'All Categories', 'wb-community-calendar-pro' ) . '</option>';
			if ( ! empty( $category_terms ) && ! is_wp_error( $category_terms ) ) {
				foreach ( $category_terms as $term ) {
					echo '<option value="' . esc_attr( $term->term_id ) . '"' . selected( $selected_category, $term->term_id, false ) . '>' . esc_html( $term->name ) . '</option>';
				}
			}
			echo '</select> ';
			echo '<input type="text" name="wbccp_tag" value="' . esc_attr( $selected_tag ) . '" placeholder="' . esc_attr__( 'Tag', 'wb-community-calendar-pro' ) . '" /> ';
			echo '<input type="search" name="wbccp_search" value="' . esc_attr( $search ) . '" placeholder="' . esc_attr__( 'Search events...', 'wb-community-calendar-pro' ) . '" /> ';
			echo '<button type="submit" class="button">' . esc_html__( 'Filter', 'wb-community-calendar-pro' ) . '</button>';
			echo '</form>';

			$now = current_time( 'timestamp' );
			$range_start = $now - DAY_IN_SECONDS;
			$range_end = $now + ( 90 * DAY_IN_SECONDS );
			if ( 'past' === $filter || 'all' === $filter ) {
				$range_start = $now - ( 365 * DAY_IN_SECONDS );
			}
			if ( 'all' === $filter ) {
				$range_end = $now + ( 365 * DAY_IN_SECONDS );
			}
			$occurrences = WBCCP_CPT::get_group_occurrences( $group_id, $range_start, $range_end, 'group', $tax_query );

			if ( 'past' === $filter ) {
				$occurrences = array_filter(
					$occurrences,
					function( $occurrence ) use ( $range_start ) {
						return $occurrence['start'] < $range_start;
					}
				);
			} elseif ( 'upcoming' === $filter ) {
				$occurrences = array_filter(
					$occurrences,
					function( $occurrence ) use ( $range_start ) {
						return $occurrence['start'] >= $range_start;
					}
				);
			}

			if ( $search ) {
				$occurrences = array_filter(
					$occurrences,
					function( $occurrence ) use ( $search ) {
						$title = get_the_title( $occurrence['event_id'] );
						$location = get_post_meta( $occurrence['event_id'], 'wbccp_location', true );
						return false !== stripos( $title, $search ) || ( $location && false !== stripos( $location, $search ) );
					}
				);
			}

			$total_items = count( $occurrences );
			$total_pages = max( 1, (int) ceil( $total_items / $per_page ) );
			if ( $page > $total_pages ) {
				$page = $total_pages;
			}
			$occurrences = array_slice( $occurrences, ( $page - 1 ) * $per_page, $per_page );

			if ( ! empty( $occurrences ) ) {
				$event_ids = array_unique( wp_list_pluck( $occurrences, 'event_id' ) );
				$statuses = WBCCP_RSVP::get_statuses_for_user( $event_ids, get_current_user_id() );
				$counts_map = WBCCP_RSVP::get_counts_for_events( $event_ids );
				echo '<ul class="wbccp-events-list">';
				foreach ( $occurrences as $occurrence ) {
					$event_id = (int) $occurrence['event_id'];
					$start_display = WBCCP_CPT::format_occurrence_datetime( $event_id, $occurrence['start'] );
					$status = isset( $statuses[ $event_id ] ) ? $statuses[ $event_id ] : '';
					$location = get_post_meta( $event_id, 'wbccp_location', true );
					$link = get_post_meta( $event_id, 'wbccp_link', true );
					$counts = isset( $counts_map[ $event_id ] ) ? $counts_map[ $event_id ] : array(
						'attending' => 0,
						'maybe'     => 0,
						'cant'      => 0,
					);
					$can_edit = current_user_can( 'edit_post', $event_id );
					$highlight = $current_event_id && $current_event_id === (int) $event_id ? ' wbccp-event-highlight' : '';
					echo '<li class="wbccp-event-item' . esc_attr( $highlight ) . '">';
					echo '<div class="wbccp-event-header">';
					echo '<strong>' . esc_html( get_the_title( $event_id ) ) . '</strong>';
					$view_link = get_permalink( $event_id );
					if ( $view_link ) {
						echo ' <a class="wbccp-event-view" href="' . esc_url( $view_link ) . '">' . esc_html__( 'View', 'wb-community-calendar-pro' ) . '</a>';
					}
					if ( $can_edit ) {
						$edit_link = add_query_arg( 'wbccp_edit', $event_id, $base_url );
						echo ' <a class="wbccp-event-edit" href="' . esc_url( $edit_link ) . '#wbccp-edit">' . esc_html__( 'Edit', 'wb-community-calendar-pro' ) . '</a>';
						echo '<form method="post" class="wbccp-event-delete-form">';
						echo '<input type="hidden" name="wbccp_action" value="delete_event" />';
						echo '<input type="hidden" name="wbccp_event_id" value="' . esc_attr( $event_id ) . '" />';
						wp_nonce_field( 'wbccp_delete_event', 'wbccp_nonce' );
						echo '<button type="submit" class="wbccp-event-delete" onclick="return confirm(\'' . esc_js( __( 'Delete this event?', 'wb-community-calendar-pro' ) ) . '\');">' . esc_html__( 'Delete', 'wb-community-calendar-pro' ) . '</button>';
						echo '</form>';
					}
				echo '</div>';
				if ( $start_display ) {
					echo ' <span class="wbccp-event-date">' . esc_html( $start_display ) . '</span>';
					if ( $show_viewer_timezone ) {
						echo ' <span class="wbccp-event-local-time" data-start-ts="' . esc_attr( $occurrence['start'] ) . '" data-end-ts="' . esc_attr( $occurrence['end'] ) . '"></span>';
					}
				}
				$status_label = $status ? WBCCP_BP::format_rsvp_label( $status ) : '';
				if ( $status_label ) {
					echo ' <span class="wbccp-event-rsvp" data-event-id="' . esc_attr( $event_id ) . '">' . esc_html( $status_label ) . '</span>';
				} else {
					echo ' <span class="wbccp-event-rsvp" data-event-id="' . esc_attr( $event_id ) . '"></span>';
				}
				if ( $location || $link ) {
					echo '<div class="wbccp-event-meta">';
					if ( $location ) {
						echo '<span class="wbccp-event-location">' . esc_html( $location ) . '</span>';
					}
					if ( $link ) {
						echo ' <a class="wbccp-event-link" href="' . esc_url( $link ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Join', 'wb-community-calendar-pro' ) . '</a>';
					}
					echo '</div>';
				}
				echo '<div class="wbccp-event-rsvp-actions" data-event-id="' . esc_attr( $event_id ) . '">';
				echo '<form method="post" class="wbccp-rsvp-form" style="display:inline-block;margin-right:6px;" data-event-id="' . esc_attr( $event_id ) . '">';
				echo '<input type="hidden" name="wbccp_action" value="rsvp" />';
				echo '<input type="hidden" name="wbccp_event_id" value="' . esc_attr( $event_id ) . '" />';
				echo '<input type="hidden" name="wbccp_status" value="attending" />';
				wp_nonce_field( 'wbccp_rsvp', 'wbccp_nonce' );
				echo '<button type="submit" class="button wbccp-rsvp-button' . ( 'attending' === $status ? ' is-active' : '' ) . '" data-status="attending">' . esc_html__( "I'm attending", 'wb-community-calendar-pro' ) . '</button>';
				echo '</form>';
				echo '<form method="post" class="wbccp-rsvp-form" style="display:inline-block;margin-right:6px;" data-event-id="' . esc_attr( $event_id ) . '">';
				echo '<input type="hidden" name="wbccp_action" value="rsvp" />';
				echo '<input type="hidden" name="wbccp_event_id" value="' . esc_attr( $event_id ) . '" />';
				echo '<input type="hidden" name="wbccp_status" value="maybe" />';
				wp_nonce_field( 'wbccp_rsvp', 'wbccp_nonce' );
				echo '<button type="submit" class="button wbccp-rsvp-button' . ( 'maybe' === $status ? ' is-active' : '' ) . '" data-status="maybe">' . esc_html__( "I don't know yet", 'wb-community-calendar-pro' ) . '</button>';
				echo '</form>';
				echo '<form method="post" class="wbccp-rsvp-form" style="display:inline-block;" data-event-id="' . esc_attr( $event_id ) . '">';
				echo '<input type="hidden" name="wbccp_action" value="rsvp" />';
				echo '<input type="hidden" name="wbccp_event_id" value="' . esc_attr( $event_id ) . '" />';
				echo '<input type="hidden" name="wbccp_status" value="cant" />';
				wp_nonce_field( 'wbccp_rsvp', 'wbccp_nonce' );
				echo '<button type="submit" class="button wbccp-rsvp-button' . ( 'cant' === $status ? ' is-active' : '' ) . '" data-status="cant">' . esc_html__( "I can't", 'wb-community-calendar-pro' ) . '</button>';
				echo '</form>';
				echo '</div>';
				echo '<div class="wbccp-event-rsvp-message" role="status" aria-live="polite"></div>';
				echo '<div class="wbccp-event-rsvp-counts" data-event-id="' . esc_attr( $event_id ) . '">';
				echo '<span data-count="attending">' . esc_html__( 'Attending:', 'wb-community-calendar-pro' ) . ' ' . esc_html( $counts['attending'] ) . '</span>';
				echo '<span data-count="maybe">' . esc_html__( 'Maybe:', 'wb-community-calendar-pro' ) . ' ' . esc_html( $counts['maybe'] ) . '</span>';
				echo '<span data-count="cant">' . esc_html__( "Can't:", 'wb-community-calendar-pro' ) . ' ' . esc_html( $counts['cant'] ) . '</span>';
				echo '</div>';
				$capacity = (int) get_post_meta( $event_id, 'wbccp_capacity', true );
				if ( $capacity ) {
					$spots_left = max( 0, $capacity - ( (int) $counts['attending'] + (int) $counts['maybe'] ) );
					echo '<div class="wbccp-event-capacity" data-event-id="' . esc_attr( $event_id ) . '" data-capacity="' . esc_attr( $capacity ) . '">' . esc_html( sprintf( __( 'Capacity: %d', 'wb-community-calendar-pro' ), $capacity ) ) . '</div>';
					echo '<div class="wbccp-event-spots" data-event-id="' . esc_attr( $event_id ) . '" data-capacity="' . esc_attr( $capacity ) . '">' . esc_html( $spots_left ? sprintf( __( 'Spots left: %d', 'wb-community-calendar-pro' ), $spots_left ) : __( 'Event is full.', 'wb-community-calendar-pro' ) ) . '</div>';
				}
				echo '</li>';
			}
			echo '</ul>';
		} else {
			echo WBCCP_Views::render_empty_state();
		}

		if ( $total_pages > 1 ) {
			$query_args = array(
				'wbccp_view'   => 'list',
				'wbccp_filter' => $filter,
			);
			if ( $search ) {
				$query_args['wbccp_search'] = $search;
			}
			if ( $selected_category ) {
				$query_args['wbccp_category'] = $selected_category;
			}
			if ( $selected_tag ) {
				$query_args['wbccp_tag'] = $selected_tag;
			}
			echo '<div class="wbccp-pagination">';
			if ( $page > 1 ) {
				echo '<a class="button" href="' . esc_url( add_query_arg( array_merge( $query_args, array( 'wbccp_page' => $page - 1 ) ), $base_url ) ) . '">' . esc_html__( 'Prev', 'wb-community-calendar-pro' ) . '</a> ';
			}
			echo '<span>' . esc_html( sprintf( __( 'Page %1$d of %2$d', 'wb-community-calendar-pro' ), $page, $total_pages ) ) . '</span>';
			if ( $page < $total_pages ) {
				echo ' <a class="button" href="' . esc_url( add_query_arg( array_merge( $query_args, array( 'wbccp_page' => $page + 1 ) ), $base_url ) ) . '">' . esc_html__( 'Next', 'wb-community-calendar-pro' ) . '</a>';
			}
			echo '</div>';
		}
		echo '</div>';

		echo '</div>';
		echo '</div>';
	}
}
