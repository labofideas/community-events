<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WBCCP_Notifications {
	public static function init() {
		add_action( 'wbccp_send_reminder', array( __CLASS__, 'handle_reminder' ), 10, 2 );
	}

	public static function send_event_created( $event_id ) {
		self::send_event_notification( $event_id, 'created' );
	}

	public static function send_event_updated( $event_id ) {
		self::send_event_notification( $event_id, 'updated' );
	}

	public static function send_event_deleted( $event_id, $group_id, $title ) {
		$recipients = self::get_recipients( $group_id, get_current_user_id() );
		if ( empty( $recipients ) ) {
			return;
		}

		$subject = sprintf( __( 'Event canceled: %s', 'wb-community-calendar-pro' ), $title );
		$body = sprintf( __( 'The event "%s" has been canceled.', 'wb-community-calendar-pro' ), $title );

		self::send_mail( $recipients, $subject, $body, $event_id );
	}

	public static function send_rsvp( $event_id, $user_id, $status ) {
		$recipients = self::get_rsvp_recipients( $event_id, $user_id );
		if ( empty( $recipients ) ) {
			return;
		}

		$user = get_user_by( 'id', $user_id );
		$name = $user ? $user->display_name : __( 'A member', 'wb-community-calendar-pro' );
		$event_title = get_the_title( $event_id );
		$status_map = array(
			'attending' => __( 'is attending', 'wb-community-calendar-pro' ),
			'maybe'     => __( 'might attend', 'wb-community-calendar-pro' ),
			'cant'      => __( 'cannot attend', 'wb-community-calendar-pro' ),
		);
		$status_text = isset( $status_map[ $status ] ) ? $status_map[ $status ] : $status;

		$subject = sprintf( __( 'RSVP update: %s', 'wb-community-calendar-pro' ), $event_title );
		$body = sprintf( __( '%1$s %2$s the event "%3$s".', 'wb-community-calendar-pro' ), $name, $status_text, $event_title );

		self::send_mail( $recipients, $subject, $body, $event_id );
	}

	public static function schedule_event_reminders( $event_id ) {
		$event_id = (int) $event_id;
		if ( ! $event_id ) {
			return;
		}

		$start = (int) get_post_meta( $event_id, 'wbccp_start', true );
		if ( ! $start ) {
			return;
		}

		$settings = class_exists( 'WBCCP_Settings' ) ? WBCCP_Settings::get_settings() : array();
		$send_day = ! empty( $settings['notify_reminder_day'] );
		$send_hour = ! empty( $settings['notify_reminder_hour'] );

		self::clear_scheduled_reminders( $event_id );

		$now = current_time( 'timestamp' );
		if ( $send_day ) {
			$ts = $start - DAY_IN_SECONDS;
			if ( $ts > $now ) {
				wp_schedule_single_event( $ts, 'wbccp_send_reminder', array( $event_id, 'day' ) );
			}
		}
		if ( $send_hour ) {
			$ts = $start - HOUR_IN_SECONDS;
			if ( $ts > $now ) {
				wp_schedule_single_event( $ts, 'wbccp_send_reminder', array( $event_id, 'hour' ) );
			}
		}
	}

	public static function clear_scheduled_reminders( $event_id ) {
		$event_id = (int) $event_id;
		if ( ! $event_id ) {
			return;
		}

		wp_clear_scheduled_hook( 'wbccp_send_reminder', array( $event_id, 'day' ) );
		wp_clear_scheduled_hook( 'wbccp_send_reminder', array( $event_id, 'hour' ) );
	}

	public static function handle_reminder( $event_id, $type ) {
		$event_id = (int) $event_id;
		if ( ! $event_id ) {
			return;
		}

		$event = get_post( $event_id );
		if ( ! $event || WBCCP_CPT::CPT !== $event->post_type || 'publish' !== $event->post_status ) {
			return;
		}

		$start = (int) get_post_meta( $event_id, 'wbccp_start', true );
		if ( ! $start || $start < current_time( 'timestamp' ) ) {
			return;
		}

		$settings = class_exists( 'WBCCP_Settings' ) ? WBCCP_Settings::get_settings() : array();
		$include_maybe = ! empty( $settings['reminder_include_maybe'] );
		$statuses = $include_maybe ? array( 'attending', 'maybe' ) : array( 'attending' );

		$recipients = class_exists( 'WBCCP_RSVP' ) ? WBCCP_RSVP::get_user_emails_by_status( $event_id, $statuses ) : array();
		if ( empty( $recipients ) ) {
			return;
		}

		$event_title = get_the_title( $event_id );
		$event_link = get_permalink( $event_id );
		$when = WBCCP_CPT::format_event_datetime( $event_id );
		$location = get_post_meta( $event_id, 'wbccp_location', true );

		$label = 'day' === $type ? __( 'tomorrow', 'wb-community-calendar-pro' ) : __( 'in one hour', 'wb-community-calendar-pro' );
		$subject = sprintf( __( 'Reminder: %1$s starts %2$s', 'wb-community-calendar-pro' ), $event_title, $label );

		$lines = array(
			sprintf( __( 'This is a reminder that "%1$s" starts %2$s.', 'wb-community-calendar-pro' ), $event_title, $label ),
		);
		if ( $when ) {
			$lines[] = sprintf( __( 'When: %s', 'wb-community-calendar-pro' ), $when );
		}
		if ( $location ) {
			$lines[] = sprintf( __( 'Where: %s', 'wb-community-calendar-pro' ), $location );
		}
		if ( $event_link ) {
			$lines[] = sprintf( __( 'View event: %s', 'wb-community-calendar-pro' ), $event_link );
		}

		$body = implode( "\n", $lines );
		self::send_mail( $recipients, $subject, $body, $event_id );
	}

	private static function send_event_notification( $event_id, $type ) {
		$group_id = (int) get_post_meta( $event_id, 'wbccp_group_id', true );
		$recipients = self::get_recipients( $group_id, get_current_user_id() );
		if ( empty( $recipients ) ) {
			return;
		}

		$event_title = get_the_title( $event_id );
		$event_link = get_permalink( $event_id );
		$start = WBCCP_CPT::format_event_datetime( $event_id );
		$location = get_post_meta( $event_id, 'wbccp_location', true );

		$action_text = 'created' === $type ? __( 'created', 'wb-community-calendar-pro' ) : __( 'updated', 'wb-community-calendar-pro' );
		$subject = sprintf( __( 'Event %1$s: %2$s', 'wb-community-calendar-pro' ), $action_text, $event_title );

		$lines = array(
			sprintf( __( 'The event "%s" was %s.', 'wb-community-calendar-pro' ), $event_title, $action_text ),
		);
		if ( $start ) {
			$lines[] = sprintf( __( 'When: %s', 'wb-community-calendar-pro' ), $start );
		}
		if ( $location ) {
			$lines[] = sprintf( __( 'Where: %s', 'wb-community-calendar-pro' ), $location );
		}
		if ( $event_link ) {
			$lines[] = sprintf( __( 'View event: %s', 'wb-community-calendar-pro' ), $event_link );
		}

		$body = implode( "\n", $lines );
		self::send_mail( $recipients, $subject, $body, $event_id );
	}

	private static function get_recipients( $group_id, $exclude_user_id = 0 ) {
		if ( ! $group_id || ! function_exists( 'groups_get_group_members' ) ) {
			return array();
		}

		$members = groups_get_group_members(
			array(
				'group_id' => $group_id,
				'per_page' => 200,
				'page'     => 1,
			)
		);

		$emails = array();
		if ( ! empty( $members['members'] ) ) {
			foreach ( $members['members'] as $member ) {
				if ( $exclude_user_id && (int) $member->ID === (int) $exclude_user_id ) {
					continue;
				}
				if ( ! empty( $member->user_email ) ) {
					$emails[] = $member->user_email;
				}
			}
		}

		return array_values( array_unique( $emails ) );
	}

	private static function get_rsvp_recipients( $event_id, $exclude_user_id = 0 ) {
		$settings = class_exists( 'WBCCP_Settings' ) ? WBCCP_Settings::get_settings() : array();
		$selected = ! empty( $settings['notify_rsvp_recipients'] ) ? (array) $settings['notify_rsvp_recipients'] : array();
		if ( empty( $selected ) ) {
			return array();
		}

		$emails = array();
		$event_id = (int) $event_id;
		$group_id = (int) get_post_meta( $event_id, 'wbccp_group_id', true );

		if ( in_array( 'event_author', $selected, true ) ) {
			$author_id = (int) get_post_field( 'post_author', $event_id );
			if ( $author_id && (int) $author_id !== (int) $exclude_user_id ) {
				$author = get_user_by( 'id', $author_id );
				if ( $author && ! empty( $author->user_email ) ) {
					$emails[] = $author->user_email;
				}
			}
		}

		if ( $group_id ) {
			if ( in_array( 'admins_mods', $selected, true ) ) {
				$emails = array_merge( $emails, self::get_group_admin_emails( $group_id, $exclude_user_id ) );
			}
			if ( in_array( 'group_members', $selected, true ) ) {
				$emails = array_merge( $emails, self::get_recipients( $group_id, $exclude_user_id ) );
			}
		}

		return array_values( array_unique( array_filter( $emails ) ) );
	}

	private static function get_group_admin_emails( $group_id, $exclude_user_id = 0 ) {
		if ( ! $group_id ) {
			return array();
		}

		$emails = array();
		if ( function_exists( 'groups_get_group_admins' ) ) {
			$admins = groups_get_group_admins( $group_id );
			foreach ( (array) $admins as $admin ) {
				if ( $exclude_user_id && (int) $admin->user_id === (int) $exclude_user_id ) {
					continue;
				}
				$user = get_user_by( 'id', $admin->user_id );
				if ( $user && ! empty( $user->user_email ) ) {
					$emails[] = $user->user_email;
				}
			}
		}

		if ( function_exists( 'groups_get_group_mods' ) ) {
			$mods = groups_get_group_mods( $group_id );
			foreach ( (array) $mods as $mod ) {
				if ( $exclude_user_id && (int) $mod->user_id === (int) $exclude_user_id ) {
					continue;
				}
				$user = get_user_by( 'id', $mod->user_id );
				if ( $user && ! empty( $user->user_email ) ) {
					$emails[] = $user->user_email;
				}
			}
		}

		return array_values( array_unique( $emails ) );
	}

	private static function send_mail( $recipients, $subject, $body, $event_id ) {
		$recipients = apply_filters( 'wbccp_notification_recipients', $recipients, $event_id );
		if ( empty( $recipients ) ) {
			return;
		}

		$subject = apply_filters( 'wbccp_notification_subject', $subject, $event_id );
		$body = apply_filters( 'wbccp_notification_body', $body, $event_id );

		foreach ( (array) $recipients as $email ) {
			wp_mail( $email, $subject, $body );
		}
	}
}
