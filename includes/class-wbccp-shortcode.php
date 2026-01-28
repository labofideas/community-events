<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WBCCP_Shortcode {
	public static function init() {
		add_shortcode( 'wbccp_calendar', array( __CLASS__, 'render_calendar' ) );
		add_shortcode( 'wbccp_my_events', array( __CLASS__, 'render_my_events' ) );
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

	public static function render_my_events() {
		if ( ! is_user_logged_in() ) {
			return '<p>' . esc_html__( 'Please log in to view your events.', 'wb-community-calendar-pro' ) . '</p>';
		}

		$user_id = get_current_user_id();
		$lists = self::get_rsvp_lists_for_user( $user_id );
		$attending = $lists['attending'];
		$maybe = $lists['maybe'];
		$cant = $lists['cant'];

		ob_start();
		echo '<div class="wbccp-my-events">';
		echo '<h3>' . esc_html__( 'My Events', 'wb-community-calendar-pro' ) . '</h3>';
		self::render_event_list( $attending, __( 'Attending', 'wb-community-calendar-pro' ) );
		self::render_event_list( $maybe, __( 'Maybe', 'wb-community-calendar-pro' ) );
		self::render_event_list( $cant, __( "Can't Attend", 'wb-community-calendar-pro' ) );
		echo '</div>';
		return ob_get_clean();
	}

	public static function render_calendar( $atts ) {
		$atts = shortcode_atts(
			array(
				'group_id' => 0,
				'limit'    => 10,
				'view'     => '',
				'scope'    => '',
				'allow_submit' => '',
			),
			$atts,
			'wbccp_calendar'
		);

		$view = $atts['view'] ? $atts['view'] : WBCCP_Views::get_view();
		if ( ! in_array( $view, array( 'list', 'month' ), true ) ) {
			$view = 'list';
		}

		$group_id = (int) $atts['group_id'];
		$scope = $atts['scope'] ? sanitize_text_field( $atts['scope'] ) : '';
		if ( $group_id ) {
			$scope = 'group';
		}
		if ( ! in_array( $scope, array( 'group', 'sitewide', 'all' ), true ) ) {
			$scope = 'all';
		}
		$limit    = (int) $atts['limit'];

		$settings = class_exists( 'WBCCP_Settings' ) ? WBCCP_Settings::get_settings() : array( 'allow_sitewide_events' => 0 );
		$allow_sitewide = ! empty( $settings['allow_sitewide_events'] );
		$allow_submit = self::normalize_bool( $atts['allow_submit'] );
		if ( '' === $atts['allow_submit'] ) {
			$allow_submit = ( 'sitewide' === $scope );
		}
		$submission_notice = '';
		$submission_class = '';
		$messages = self::get_notice_messages();
		if ( isset( $_GET['wbccp_notice'] ) ) {
			$notice = sanitize_key( wp_unslash( $_GET['wbccp_notice'] ) );
			if ( isset( $messages[ $notice ] ) ) {
				$success = in_array( $notice, array( 'submitted', 'published' ), true );
				$submission_notice = $messages[ $notice ];
				$submission_class = $success ? 'wbccp-form-notice--success' : 'wbccp-form-notice--error';
			}
		}
		if ( $allow_submit && $allow_sitewide && 'POST' === $_SERVER['REQUEST_METHOD'] && ! empty( $_POST['wbccp_action'] ) ) {
			$action = sanitize_text_field( wp_unslash( $_POST['wbccp_action'] ) );
			if ( 'submit_sitewide' === $action ) {
				$result = self::handle_sitewide_submission();
				if ( ! empty( $result['message'] ) ) {
					$submission_notice = $result['message'];
					$submission_class = ! empty( $result['success'] ) ? 'wbccp-form-notice--success' : 'wbccp-form-notice--error';
				}
			}
		}

		$range_start = current_time( 'timestamp' ) - DAY_IN_SECONDS;
		$range_end   = current_time( 'timestamp' ) + ( 180 * DAY_IN_SECONDS );
		$scope_for_query = 'sitewide' === $scope ? 'sitewide' : 'all';
		$tax_query = WBCCP_CPT::get_tax_query_from_request();
		$occurrences = WBCCP_CPT::get_group_occurrences( $group_id, $range_start, $range_end, $scope_for_query, $tax_query );
		if ( $limit > 0 ) {
			$occurrences = array_slice( $occurrences, 0, $limit );
		}

		ob_start();
		echo '<div class="wbccp-calendar-shortcode wbccp-view-container" data-default-view="' . esc_attr( $view ) . '">';
		echo '<div class="wbccp-events-top">';
		if ( 'sitewide' === $scope ) {
			echo '<h3>' . esc_html__( 'Sitewide Events', 'wb-community-calendar-pro' ) . '</h3>';
		} else {
			echo '<h3>' . esc_html__( 'Community Events', 'wb-community-calendar-pro' ) . '</h3>';
		}
		echo '</div>';
		echo '<div class="wbccp-view-toggle-wrap">';
		$panel_ids = WBCCP_Views::render_view_toggle( get_permalink() );
		echo '</div>';
		if ( $submission_notice ) {
			echo '<div class="wbccp-form-notice ' . esc_attr( $submission_class ) . '" data-wbccp-notice data-dismissible="true">';
			echo '<span>' . esc_html( $submission_notice ) . '</span>';
			echo '<button type="button" class="wbccp-notice-dismiss" aria-label="' . esc_attr__( 'Dismiss notice', 'wb-community-calendar-pro' ) . '">×</button>';
			echo '</div>';
		}

		if ( $allow_submit && $allow_sitewide && is_user_logged_in() && 'sitewide' === $scope ) {
			echo '<details class="wbccp-accordion wbccp-event-form wbccp-sitewide-form" id="wbccp-submit">';
			echo '<summary class="wbccp-accordion__summary">';
			echo '<span>' . esc_html__( 'Submit Event', 'wb-community-calendar-pro' ) . '</span>';
			echo '<span class="wbccp-accordion__hint">' . esc_html__( 'Share a new sitewide event', 'wb-community-calendar-pro' ) . '</span>';
			echo '</summary>';
			echo '<div class="wbccp-accordion__content">';
			echo '<form method="post" enctype="multipart/form-data">';
			echo '<input type="hidden" name="wbccp_action" value="submit_sitewide" />';
			echo '<p><label>' . esc_html__( 'Title', 'wb-community-calendar-pro' ) . '</label><br /><input type="text" name="wbccp_title" class="regular-text" required /></p>';
			echo '<p><label>' . esc_html__( 'Description', 'wb-community-calendar-pro' ) . '</label><br /><textarea name="wbccp_description" rows="4" class="large-text"></textarea></p>';
			echo '<p><label>' . esc_html__( 'Start', 'wb-community-calendar-pro' ) . '</label><br /><input type="datetime-local" name="wbccp_start" required /></p>';
			echo '<p><label>' . esc_html__( 'End', 'wb-community-calendar-pro' ) . '</label><br /><input type="datetime-local" name="wbccp_end" /></p>';
			echo '<p><label>' . esc_html__( 'Timezone', 'wb-community-calendar-pro' ) . '</label><br /><input type="text" name="wbccp_timezone" value="' . esc_attr( $settings['default_timezone'] ) . '" class="regular-text" /></p>';
			echo '<p><label>' . esc_html__( 'Location', 'wb-community-calendar-pro' ) . '</label><br /><input type="text" name="wbccp_location" class="regular-text" /></p>';
			echo '<p><label>' . esc_html__( 'Meeting Link', 'wb-community-calendar-pro' ) . '</label><br /><input type="url" name="wbccp_link" class="regular-text" /></p>';
			echo '<p><label>' . esc_html__( 'Capacity', 'wb-community-calendar-pro' ) . '</label><br /><input type="number" name="wbccp_capacity" min="0" class="small-text" /></p>';
			if ( class_exists( 'WBCCP_BP' ) ) {
				WBCCP_BP::render_taxonomy_fields();
			}
			echo '<p><label>' . esc_html__( 'Event Image', 'wb-community-calendar-pro' ) . '</label><br /><input type="file" name="wbccp_image" accept="image/*" /></p>';
			WBCCP_BP::render_recurrence_fields();
			wp_nonce_field( 'wbccp_submit_sitewide', 'wbccp_nonce' );
			echo '<p><button type="submit" class="button button-primary">' . esc_html__( 'Submit Event', 'wb-community-calendar-pro' ) . '</button></p>';
			echo '</form>';
			echo '</div>';
			echo '</details>';
		} elseif ( $allow_submit && $allow_sitewide && 'sitewide' === $scope ) {
			echo '<p class="wbccp-event-note">' . esc_html__( 'Please log in to submit a sitewide event.', 'wb-community-calendar-pro' ) . '</p>';
		}

		echo '<div class="wbccp-view-panels">';
		echo '<div class="wbccp-view-panel wbccp-view-panel--list' . ( 'list' === $view ? ' is-active' : '' ) . '" data-view="list" id="' . esc_attr( $panel_ids['list'] ) . '">';
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
		echo '<select name="wbccp_category">';
		echo '<option value="">' . esc_html__( 'All Categories', 'wb-community-calendar-pro' ) . '</option>';
		if ( ! empty( $category_terms ) && ! is_wp_error( $category_terms ) ) {
			foreach ( $category_terms as $term ) {
				echo '<option value="' . esc_attr( $term->term_id ) . '"' . selected( $selected_category, $term->term_id, false ) . '>' . esc_html( $term->name ) . '</option>';
			}
		}
		echo '</select> ';
		echo '<input type="text" name="wbccp_tag" value="' . esc_attr( $selected_tag ) . '" placeholder="' . esc_attr__( 'Tag', 'wb-community-calendar-pro' ) . '" /> ';
		echo '<button type="submit" class="button">' . esc_html__( 'Filter', 'wb-community-calendar-pro' ) . '</button>';
		echo '</form>';
		if ( ! empty( $occurrences ) ) {
			echo '<ul class="wbccp-events-list">';
			foreach ( $occurrences as $occurrence ) {
				$event_id = (int) $occurrence['event_id'];
				$start_display = WBCCP_CPT::format_occurrence_datetime( $event_id, $occurrence['start'] );
				$group_id = (int) get_post_meta( $event_id, 'wbccp_group_id', true );
				$group_name = '';
				$scope_label = '';
				$location = get_post_meta( $event_id, 'wbccp_location', true );
				$link = get_post_meta( $event_id, 'wbccp_link', true );
				if ( ! $atts['group_id'] && $group_id && function_exists( 'groups_get_group' ) ) {
					$group = groups_get_group( $group_id );
					if ( ! empty( $group->name ) ) {
						$group_name = $group->name;
					}
				}
				if ( ! $group_id ) {
					$scope_label = __( 'Sitewide', 'wb-community-calendar-pro' );
				}
				$event_link = get_permalink( $event_id );
				echo '<li class="wbccp-event-item">';
				if ( $event_link ) {
					echo '<a class="wbccp-event-title" href="' . esc_url( $event_link ) . '">' . esc_html( get_the_title( $event_id ) ) . '</a>';
				} else {
					echo '<strong>' . esc_html( get_the_title( $event_id ) ) . '</strong>';
				}
				if ( $start_display ) {
					echo ' <span class="wbccp-event-date">' . esc_html( $start_display ) . '</span>';
					if ( ! empty( $settings['show_viewer_timezone'] ) ) {
						echo ' <span class="wbccp-event-local-time" data-start-ts="' . esc_attr( $occurrence['start'] ) . '" data-end-ts="' . esc_attr( $occurrence['end'] ) . '"></span>';
					}
				}
				if ( $group_name ) {
					echo ' <span class="wbccp-event-group">' . esc_html( $group_name ) . '</span>';
				} elseif ( $scope_label ) {
					echo ' <span class="wbccp-event-group wbccp-event-scope">' . esc_html( $scope_label ) . '</span>';
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
				$capacity = (int) get_post_meta( $event_id, 'wbccp_capacity', true );
				if ( $capacity ) {
					echo '<div class="wbccp-event-capacity" data-event-id="' . esc_attr( $event_id ) . '" data-capacity="' . esc_attr( $capacity ) . '">' . esc_html( sprintf( __( 'Capacity: %d', 'wb-community-calendar-pro' ), $capacity ) ) . '</div>';
				}
				echo '</li>';
			}
			echo '</ul>';
		} else {
			echo WBCCP_Views::render_empty_state();
		}
		echo '</div>';
		echo '<div class="wbccp-view-panel wbccp-view-panel--month' . ( 'month' === $view ? ' is-active' : '' ) . '" data-view="month" id="' . esc_attr( $panel_ids['month'] ) . '">';
		$month_scope = 'sitewide' === $scope ? 'sitewide' : ( $group_id ? 'group' : 'all' );
		WBCCP_Views::render_month_view( $group_id, get_permalink(), false, $month_scope, $tax_query );
		echo '</div>';
		echo '</div>';

		echo '</div>';

		return ob_get_clean();
	}

	private static function normalize_bool( $value ) {
		if ( '' === $value || null === $value ) {
			return false;
		}
		$value = is_string( $value ) ? strtolower( $value ) : $value;
		return in_array( $value, array( 1, '1', true, 'true', 'yes', 'on' ), true );
	}

	private static function render_event_list( $event_ids, $title ) {
		echo '<div class="wbccp-my-events-section">';
		echo '<h4>' . esc_html( $title ) . '</h4>';
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

	private static function handle_sitewide_submission() {
		if ( ! is_user_logged_in() ) {
			return array( 'success' => false, 'message' => __( 'Please log in to submit an event.', 'wb-community-calendar-pro' ) );
		}

		if ( empty( $_POST['wbccp_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['wbccp_nonce'] ), 'wbccp_submit_sitewide' ) ) {
			return array( 'success' => false, 'message' => __( 'Security check failed.', 'wb-community-calendar-pro' ) );
		}

		$settings = class_exists( 'WBCCP_Settings' ) ? WBCCP_Settings::get_settings() : array( 'allow_sitewide_events' => 0, 'default_timezone' => wp_timezone_string() );
		if ( empty( $settings['allow_sitewide_events'] ) ) {
			return self::redirect_with_notice( 'sitewide-disabled', false );
		}

		$title   = isset( $_POST['wbccp_title'] ) ? sanitize_text_field( wp_unslash( $_POST['wbccp_title'] ) ) : '';
		$content = isset( $_POST['wbccp_description'] ) ? wp_kses_post( wp_unslash( $_POST['wbccp_description'] ) ) : '';
		if ( ! $title ) {
			return self::redirect_with_notice( 'missing-title', false );
		}

		$timezone = isset( $_POST['wbccp_timezone'] ) ? sanitize_text_field( wp_unslash( $_POST['wbccp_timezone'] ) ) : '';
		if ( ! $timezone ) {
			$timezone = $settings['default_timezone'];
		}

		$start_raw = isset( $_POST['wbccp_start'] ) ? sanitize_text_field( wp_unslash( $_POST['wbccp_start'] ) ) : '';
		$end_raw   = isset( $_POST['wbccp_end'] ) ? sanitize_text_field( wp_unslash( $_POST['wbccp_end'] ) ) : '';
		$start_ts = WBCCP_CPT::parse_datetime_to_utc( $start_raw, $timezone );
		$end_ts   = WBCCP_CPT::parse_datetime_to_utc( $end_raw, $timezone );
		if ( ! $start_ts ) {
			return self::redirect_with_notice( 'missing-start', false );
		}
		if ( $end_ts && $end_ts < $start_ts ) {
			return self::redirect_with_notice( 'invalid-time', false );
		}
		if ( $end_ts && $end_ts < $start_ts ) {
			return array( 'success' => false, 'message' => __( 'End time must be after the start time.', 'wb-community-calendar-pro' ) );
		}

		$location = isset( $_POST['wbccp_location'] ) ? sanitize_text_field( wp_unslash( $_POST['wbccp_location'] ) ) : '';
		$link     = isset( $_POST['wbccp_link'] ) ? esc_url_raw( wp_unslash( $_POST['wbccp_link'] ) ) : '';
		$capacity = isset( $_POST['wbccp_capacity'] ) ? absint( $_POST['wbccp_capacity'] ) : 0;

		$post_status = current_user_can( 'manage_options' ) ? 'publish' : 'pending';
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
			return array( 'success' => false, 'message' => __( 'Could not create the event.', 'wb-community-calendar-pro' ) );
		}

		update_post_meta( $event_id, 'wbccp_group_id', 0 );
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
			return self::redirect_with_notice( $upload_result->get_error_code(), false );
		}

		if ( 'publish' === $post_status && class_exists( 'WBCCP_Notifications' ) ) {
			$settings = WBCCP_Settings::get_settings();
			if ( ! empty( $settings['notify_create'] ) ) {
				WBCCP_Notifications::send_event_created( $event_id );
			}
			WBCCP_Notifications::schedule_event_reminders( $event_id );
		}

		if ( 'publish' === $post_status ) {
			return self::redirect_with_notice( 'published', true );
		}

		return self::redirect_with_notice( 'submitted', true );
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

	private static function redirect_with_notice( $notice, $success = false ) {
		$messages = self::get_notice_messages();
		$notice_key = sanitize_key( $notice );
		$message = isset( $messages[ $notice_key ] ) ? $messages[ $notice_key ] : '';

		if ( ! headers_sent() ) {
			$base_url = get_permalink();
			$target = add_query_arg( 'wbccp_notice', $notice_key, $base_url );
			wp_safe_redirect( $target );
			exit;
		}

		return array(
			'success' => (bool) $success,
			'message' => $message,
		);
	}

	private static function get_notice_messages() {
		return array(
			'submitted'         => __( 'Thanks! Your event is pending approval.', 'wb-community-calendar-pro' ),
			'published'         => __( 'Event published successfully.', 'wb-community-calendar-pro' ),
			'missing-title'     => __( 'Please enter an event title.', 'wb-community-calendar-pro' ),
			'missing-start'     => __( 'Please provide a start date/time.', 'wb-community-calendar-pro' ),
			'invalid-time'      => __( 'End time must be after the start time.', 'wb-community-calendar-pro' ),
			'upload-error'      => __( 'There was an error uploading the image.', 'wb-community-calendar-pro' ),
			'upload-type'       => __( 'Only image files are allowed.', 'wb-community-calendar-pro' ),
			'upload-size'       => __( 'Image is too large. Maximum size is 5MB.', 'wb-community-calendar-pro' ),
			'upload-permission' => __( 'You do not have permission to upload files.', 'wb-community-calendar-pro' ),
			'sitewide-disabled' => __( 'Sitewide events are disabled.', 'wb-community-calendar-pro' ),
		);
	}
}
