<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WBCCP_RSVP {
	const TABLE = 'wbccp_event_rsvps';
	const VERSION = '1.0';

	public static function init() {
		// Placeholder for REST routes/actions.
	}

	public static function maybe_create_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . self::TABLE;
		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			$sql = "CREATE TABLE {$table_name} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				event_id bigint(20) unsigned NOT NULL,
				user_id bigint(20) unsigned NOT NULL,
				status varchar(20) NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY event_user (event_id, user_id),
				KEY event_id (event_id),
				KEY user_id (user_id)
			) {$charset_collate};";

		dbDelta( $sql );
		update_option( 'wbccp_rsvp_db_version', self::VERSION );
	}

	public static function get_status( $event_id, $user_id ) {
		global $wpdb;

		if ( ! $event_id || ! $user_id ) {
			return '';
		}

		$table = $wpdb->prefix . self::TABLE;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- lightweight RSVP lookup query.
		$status = $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is plugin-controlled.
				"SELECT status FROM {$table} WHERE event_id = %d AND user_id = %d",
				$event_id,
				$user_id
			)
		);

		return $status ? $status : '';
	}

	public static function set_status( $event_id, $user_id, $status ) {
		global $wpdb;

		if ( ! $event_id || ! $user_id || ! $status ) {
			return false;
		}

		$table = $wpdb->prefix . self::TABLE;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- write operation for RSVP upsert flow.
		$wpdb->delete(
			$table,
			array(
				'event_id' => (int) $event_id,
				'user_id'  => (int) $user_id,
			),
			array( '%d', '%d' )
		);

		$data = array(
			'event_id'   => $event_id,
			'user_id'    => $user_id,
			'status'     => $status,
			'updated_at' => current_time( 'mysql' ),
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- write operation for RSVP upsert flow.
		return (bool) $wpdb->replace(
			$table,
			$data,
			array( '%d', '%d', '%s', '%s' )
		);
	}

	public static function get_counts( $event_id ) {
		global $wpdb;

		$event_id = (int) $event_id;
		if ( ! $event_id ) {
			return array();
		}

		$table = $wpdb->prefix . self::TABLE;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- aggregate counts from RSVP table.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is plugin-controlled.
				"SELECT status, COUNT(*) as total FROM {$table} WHERE event_id = %d GROUP BY status",
				$event_id
			),
			ARRAY_A
		);

		$counts = array(
			'attending' => 0,
			'maybe'     => 0,
			'cant'      => 0,
		);

		if ( $rows ) {
			foreach ( $rows as $row ) {
				$status = isset( $row['status'] ) ? $row['status'] : '';
				if ( isset( $counts[ $status ] ) ) {
					$counts[ $status ] = (int) $row['total'];
				}
			}
		}

		return $counts;
	}

	public static function get_statuses_for_user( $event_ids, $user_id ) {
		global $wpdb;

		$user_id = (int) $user_id;
		if ( empty( $event_ids ) || ! $user_id ) {
			return array();
		}

		$event_ids = array_map( 'absint', (array) $event_ids );
		$event_ids = array_filter( $event_ids );
		if ( empty( $event_ids ) ) {
			return array();
		}

		$table = $wpdb->prefix . self::TABLE;
		$placeholders = implode( ',', array_fill( 0, count( $event_ids ), '%d' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- batched RSVP status lookup.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table and placeholders are generated safely.
				"SELECT event_id, status FROM {$table} WHERE user_id = %d AND event_id IN ({$placeholders})",
				array_merge( array( $user_id ), $event_ids )
			),
			ARRAY_A
		);

		$statuses = array();
		if ( $rows ) {
			foreach ( $rows as $row ) {
				$event_id = (int) $row['event_id'];
				$statuses[ $event_id ] = $row['status'];
			}
		}

		return $statuses;
	}

	public static function get_counts_for_events( $event_ids ) {
		global $wpdb;

		if ( empty( $event_ids ) ) {
			return array();
		}

		$event_ids = array_map( 'absint', (array) $event_ids );
		$event_ids = array_filter( $event_ids );
		if ( empty( $event_ids ) ) {
			return array();
		}

		$table = $wpdb->prefix . self::TABLE;
		$placeholders = implode( ',', array_fill( 0, count( $event_ids ), '%d' ) );

			$query = $wpdb->prepare(
				"SELECT event_id, status, COUNT(*) as total FROM {$table} WHERE event_id IN ({$placeholders}) GROUP BY event_id, status", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- table and placeholders are generated safely from controlled values.
				...$event_ids
			);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- batched RSVP count aggregation.
		$rows = $wpdb->get_results(
			$query, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- query is prepared above.
			ARRAY_A
		);

		$counts = array();
		foreach ( $event_ids as $event_id ) {
			$counts[ $event_id ] = array(
				'attending' => 0,
				'maybe'     => 0,
				'cant'      => 0,
			);
		}

		if ( $rows ) {
			foreach ( $rows as $row ) {
				$event_id = (int) $row['event_id'];
				$status = $row['status'];
				if ( isset( $counts[ $event_id ][ $status ] ) ) {
					$counts[ $event_id ][ $status ] = (int) $row['total'];
				}
			}
		}

		return $counts;
	}

	public static function get_event_ids_for_user( $user_id, $statuses = array() ) {
		global $wpdb;

		$user_id = (int) $user_id;
		if ( ! $user_id ) {
			return array();
		}

		$table = $wpdb->prefix . self::TABLE;
		$sql = "SELECT event_id FROM {$table} WHERE user_id = %d";
		$params = array( $user_id );

		if ( ! empty( $statuses ) ) {
			$statuses = array_map( 'sanitize_text_field', (array) $statuses );
			$statuses = array_filter( $statuses );
			if ( ! empty( $statuses ) ) {
				$placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );
				$sql .= " AND status IN ({$placeholders})";
				$params = array_merge( $params, $statuses );
			}
		}

		$prepared_sql = $wpdb->prepare( $sql, $params ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared -- table and placeholders are generated safely before execution.
		$rows = $wpdb->get_col( $prepared_sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- batched lookup with prepared query.
		$rows = array_map( 'absint', (array) $rows );
		return array_values( array_unique( array_filter( $rows ) ) );
	}

	public static function get_user_emails_by_status( $event_id, $statuses ) {
		global $wpdb;

		$event_id = (int) $event_id;
		if ( ! $event_id ) {
			return array();
		}

		$statuses = array_map( 'sanitize_text_field', (array) $statuses );
		$statuses = array_filter( $statuses );
		if ( empty( $statuses ) ) {
			return array();
		}

		$table = $wpdb->prefix . self::TABLE;
		$placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- batched RSVP user lookup.
		$user_ids = $wpdb->get_col(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table and placeholders are generated safely.
				"SELECT user_id FROM {$table} WHERE event_id = %d AND status IN ({$placeholders})",
				array_merge( array( $event_id ), $statuses )
			)
		);

		if ( empty( $user_ids ) ) {
			return array();
		}

		$emails = array();
		foreach ( $user_ids as $user_id ) {
			$user = get_user_by( 'id', (int) $user_id );
			if ( $user && ! empty( $user->user_email ) ) {
				$emails[] = $user->user_email;
			}
		}

		return array_values( array_unique( $emails ) );
	}

	public static function delete_by_event_ids( $event_ids ) {
		global $wpdb;

		if ( empty( $event_ids ) || ! is_array( $event_ids ) ) {
			return;
		}

		$event_ids = array_map( 'absint', $event_ids );
		$event_ids = array_filter( $event_ids );

		if ( empty( $event_ids ) ) {
			return;
		}

		$table = $wpdb->prefix . self::TABLE;
		$placeholders = implode( ',', array_fill( 0, count( $event_ids ), '%d' ) );

		$delete_query = $wpdb->prepare(
			"DELETE FROM {$table} WHERE event_id IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- table and placeholders are generated safely from controlled values.
			...$event_ids
		);
		$wpdb->query( $delete_query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- batched cleanup with prepared query.
	}
}
