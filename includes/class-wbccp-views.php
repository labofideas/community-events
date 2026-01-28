<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WBCCP_Views {
	public static function get_view() {
		$view = isset( $_GET['wbccp_view'] ) ? sanitize_text_field( wp_unslash( $_GET['wbccp_view'] ) ) : 'list';
		return in_array( $view, array( 'list', 'month' ), true ) ? $view : 'list';
	}

	public static function get_month_year() {
		$year  = isset( $_GET['wbccp_year'] ) ? absint( $_GET['wbccp_year'] ) : 0;
		$month = isset( $_GET['wbccp_month'] ) ? absint( $_GET['wbccp_month'] ) : 0;

		if ( ! $year ) {
			$year = (int) wp_date( 'Y' );
		}

		if ( ! $month || $month < 1 || $month > 12 ) {
			$month = (int) wp_date( 'n' );
		}

		return array( $year, $month );
	}

	public static function get_month_bounds_utc( $year, $month ) {
		$tz = wp_timezone();
		$start_local = new DateTimeImmutable( sprintf( '%04d-%02d-01 00:00:00', $year, $month ), $tz );
		$end_local   = $start_local->modify( 'last day of this month 23:59:59' );

		$utc = new DateTimeZone( 'UTC' );
		$start_utc = $start_local->setTimezone( $utc );
		$end_utc   = $end_local->setTimezone( $utc );

		return array( $start_utc->getTimestamp(), $end_utc->getTimestamp() );
	}

	public static function get_events_in_range( $group_id, $start_ts, $end_ts ) {
		$meta_query = array(
			array(
				'key'     => 'wbccp_start',
				'value'   => array( $start_ts, $end_ts ),
				'compare' => 'BETWEEN',
				'type'    => 'NUMERIC',
			),
		);

		if ( $group_id ) {
			$meta_query[] = array(
				'key'   => 'wbccp_group_id',
				'value' => (int) $group_id,
			);
		}

		return new WP_Query(
			array(
				'post_type'      => WBCCP_CPT::CPT,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'meta_query'     => $meta_query,
				'orderby'        => 'meta_value_num',
				'order'          => 'ASC',
				'meta_key'       => 'wbccp_start',
			)
		);
	}

	public static function render_view_toggle( $base_url, $context_id = '' ) {
		$current = self::get_view();
		$context_id = $context_id ? sanitize_key( $context_id ) : uniqid( 'wbccp', false );
		$list_panel_id = 'wbccp-view-' . $context_id . '-list';
		$month_panel_id = 'wbccp-view-' . $context_id . '-month';
		$filter_args = array();
		if ( isset( $_GET['wbccp_category'] ) ) {
			$filter_args['wbccp_category'] = absint( $_GET['wbccp_category'] );
		}
		if ( isset( $_GET['wbccp_tag'] ) ) {
			$filter_args['wbccp_tag'] = sanitize_text_field( wp_unslash( $_GET['wbccp_tag'] ) );
		}
		$list_url  = add_query_arg( array_merge( $filter_args, array( 'wbccp_view' => 'list' ) ), $base_url );
		$month_url = add_query_arg( array_merge( $filter_args, array( 'wbccp_view' => 'month' ) ), $base_url );

		echo '<div class="wbccp-view-toggle" role="tablist" aria-label="' . esc_attr__( 'Calendar view', 'wb-community-calendar-pro' ) . '">';
		echo '<a class="wbccp-view-toggle__tab' . ( 'list' === $current ? ' is-active' : '' ) . '" role="tab" aria-selected="' . ( 'list' === $current ? 'true' : 'false' ) . '" aria-current="' . ( 'list' === $current ? 'page' : 'false' ) . '" aria-controls="' . esc_attr( $list_panel_id ) . '" data-view="list" href="' . esc_url( $list_url ) . '">';
		echo '<span class="wbccp-view-toggle__title">' . esc_html__( 'Agenda', 'wb-community-calendar-pro' ) . '</span>';
		echo '<span class="wbccp-view-toggle__desc">' . esc_html__( 'List of events', 'wb-community-calendar-pro' ) . '</span>';
		echo '</a>';
		echo '<a class="wbccp-view-toggle__tab' . ( 'month' === $current ? ' is-active' : '' ) . '" role="tab" aria-selected="' . ( 'month' === $current ? 'true' : 'false' ) . '" aria-current="' . ( 'month' === $current ? 'page' : 'false' ) . '" aria-controls="' . esc_attr( $month_panel_id ) . '" data-view="month" href="' . esc_url( $month_url ) . '">';
		echo '<span class="wbccp-view-toggle__title">' . esc_html__( 'Calendar', 'wb-community-calendar-pro' ) . '</span>';
		echo '<span class="wbccp-view-toggle__desc">' . esc_html__( 'Month view', 'wb-community-calendar-pro' ) . '</span>';
		echo '</a>';
		echo '</div>';

		return array(
			'list'  => $list_panel_id,
			'month' => $month_panel_id,
		);
	}

	public static function render_month_view( $group_id, $base_url, $can_create = false, $scope = 'all', $tax_query = array() ) {
		list( $year, $month ) = self::get_month_year();
		list( $start_ts, $end_ts ) = self::get_month_bounds_utc( $year, $month );

		$occurrences = WBCCP_CPT::get_group_occurrences( $group_id, $start_ts, $end_ts, $scope, $tax_query );

		$events_by_day = array();
		$has_events = false;
		$event_ids = array();
		foreach ( $occurrences as $occurrence ) {
			$event_id = (int) $occurrence['event_id'];
			$parts = WBCCP_CPT::get_date_parts_for_timestamp( $occurrence['start'], get_post_meta( $event_id, 'wbccp_timezone', true ) );
			if ( empty( $parts ) ) {
				continue;
			}
			if ( (int) $parts['year'] === (int) $year && (int) $parts['month'] === (int) $month ) {
				$day = (int) $parts['day'];
				if ( ! isset( $events_by_day[ $day ] ) ) {
					$events_by_day[ $day ] = array();
				}
				$events_by_day[ $day ][] = $occurrence;
				$has_events = true;
				$event_ids[] = $event_id;
			}
		}

		$start_of_week = (int) get_option( 'start_of_week', 0 );
		$first_day = new DateTimeImmutable( sprintf( '%04d-%02d-01', $year, $month ), new DateTimeZone( 'UTC' ) );
		$days_in_month = (int) $first_day->format( 't' );
		$first_weekday = (int) $first_day->format( 'w' );
		$pad = ( $first_weekday - $start_of_week + 7 ) % 7;
		$total_cells = $pad + $days_in_month;
		$rows = (int) ceil( $total_cells / 7 );

		$prev = (new DateTimeImmutable( sprintf( '%04d-%02d-01', $year, $month ), new DateTimeZone( 'UTC' ) ))->modify( '-1 month' );
		$next = (new DateTimeImmutable( sprintf( '%04d-%02d-01', $year, $month ), new DateTimeZone( 'UTC' ) ))->modify( '+1 month' );

		$filter_args = array();
		if ( isset( $_GET['wbccp_category'] ) ) {
			$filter_args['wbccp_category'] = absint( $_GET['wbccp_category'] );
		}
		if ( isset( $_GET['wbccp_tag'] ) ) {
			$filter_args['wbccp_tag'] = sanitize_text_field( wp_unslash( $_GET['wbccp_tag'] ) );
		}

		$prev_url = add_query_arg(
			array_merge(
				$filter_args,
				array(
					'wbccp_view'  => 'month',
					'wbccp_year'  => (int) $prev->format( 'Y' ),
					'wbccp_month' => (int) $prev->format( 'n' ),
				)
			),
			$base_url
		);
		$next_url = add_query_arg(
			array_merge(
				$filter_args,
				array(
					'wbccp_view'  => 'month',
					'wbccp_year'  => (int) $next->format( 'Y' ),
					'wbccp_month' => (int) $next->format( 'n' ),
				)
			),
			$base_url
		);
		$today = wp_date( 'Y-m-d' );

		echo '<div class="wbccp-calendar-header">';
		echo '<div class="wbccp-calendar-nav">';
		echo '<a class="button wbccp-calendar-prev" href="' . esc_url( $prev_url ) . '">' . esc_html__( 'Prev', 'wb-community-calendar-pro' ) . '</a>';
		echo '</div>';
		echo '<div class="wbccp-calendar-title">' . esc_html( wp_date( 'F Y', $first_day->getTimestamp(), wp_timezone() ) ) . '</div>';
		echo '<div class="wbccp-calendar-actions">';
		if ( $can_create ) {
			echo '<a class="button button-primary wbccp-add-event" href="' . esc_url( $base_url ) . '#wbccp-create">' . esc_html__( 'Add Event', 'wb-community-calendar-pro' ) . '</a>';
		}
		echo '<a class="button wbccp-calendar-next" href="' . esc_url( $next_url ) . '">' . esc_html__( 'Next', 'wb-community-calendar-pro' ) . '</a>';
		echo '</div>';
		echo '</div>';

		$counts_map = WBCCP_RSVP::get_counts_for_events( array_unique( $event_ids ) );

		if ( ! $has_events ) {
			echo self::render_empty_state( __( 'No events this month yet.', 'wb-community-calendar-pro' ) );
		}

		echo '<div class="wbccp-calendar-wrap">';
		echo '<table class="wbccp-calendar">';
		echo '<thead><tr>';
		$weekday_labels = array( __( 'Sun', 'wb-community-calendar-pro' ), __( 'Mon', 'wb-community-calendar-pro' ), __( 'Tue', 'wb-community-calendar-pro' ), __( 'Wed', 'wb-community-calendar-pro' ), __( 'Thu', 'wb-community-calendar-pro' ), __( 'Fri', 'wb-community-calendar-pro' ), __( 'Sat', 'wb-community-calendar-pro' ) );
		for ( $i = 0; $i < 7; $i++ ) {
			$label_index = ( $start_of_week + $i ) % 7;
			echo '<th>' . esc_html( $weekday_labels[ $label_index ] ) . '</th>';
		}
		echo '</tr></thead>';
		echo '<tbody>';

		$day = 1;
		for ( $row = 0; $row < $rows; $row++ ) {
			echo '<tr>';
			for ( $col = 0; $col < 7; $col++ ) {
				$cell_index = ( $row * 7 ) + $col;
				if ( $cell_index < $pad || $day > $days_in_month ) {
					echo '<td class="wbccp-calendar-empty"></td>';
					continue;
				}

				$day_date = sprintf( '%04d-%02d-%02d', $year, $month, $day );
				$day_classes = 'wbccp-calendar-day';
				if ( $day_date === $today ) {
					$day_classes .= ' is-today';
				}
				echo '<td class="' . esc_attr( $day_classes ) . '">';
				echo '<div class="wbccp-calendar-date">' . esc_html( $day ) . '</div>';

					if ( ! empty( $events_by_day[ $day ] ) ) {
						echo '<ul class="wbccp-calendar-events">';
						foreach ( $events_by_day[ $day ] as $occurrence ) {
							$event_id = (int) $occurrence['event_id'];
							$title = get_the_title( $event_id );
							$event_link = get_permalink( $event_id );
							$time = WBCCP_CPT::format_occurrence_datetime( $event_id, $occurrence['start'], get_option( 'time_format' ) );
							$counts = isset( $counts_map[ $event_id ] ) ? $counts_map[ $event_id ] : array(
								'attending' => 0,
								'maybe'     => 0,
								'cant'      => 0,
							);
							echo '<li class="wbccp-calendar-event-item">';
							if ( $event_link ) {
								echo '<a class="wbccp-calendar-event" href="' . esc_url( $event_link ) . '">' . esc_html( $title ) . '</a>';
							} else {
								echo esc_html( $title );
							}
							if ( $time ) {
								echo ' <span class="wbccp-calendar-time">' . esc_html( $time ) . '</span>';
							}
							echo '<span class="wbccp-calendar-rsvp">';
							echo esc_html__( 'A', 'wb-community-calendar-pro' ) . ':' . esc_html( $counts['attending'] );
							echo ' ' . esc_html__( 'M', 'wb-community-calendar-pro' ) . ':' . esc_html( $counts['maybe'] );
							echo ' ' . esc_html__( 'C', 'wb-community-calendar-pro' ) . ':' . esc_html( $counts['cant'] );
							echo '</span>';
							echo '</li>';
						}
						echo '</ul>';
					}

				echo '</td>';
				$day++;
			}
			echo '</tr>';
		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';
	}

	public static function render_empty_state( $message = '' ) {
		$text = $message ? $message : __( 'No events yet. Create the first one!', 'wb-community-calendar-pro' );

		return '<div class="wbccp-empty-state">'
			. '<div class="wbccp-empty-icon" aria-hidden="true">'
			. '<svg viewBox="0 0 64 64" role="img" focusable="false"><path d="M20 6a2 2 0 0 1 2 2v4h20V8a2 2 0 1 1 4 0v4h8a2 2 0 0 1 2 2v40a4 4 0 0 1-4 4H12a4 4 0 0 1-4-4V14a2 2 0 0 1 2-2h8V8a2 2 0 0 1 2-2Zm-8 14v34h40V20H12Zm10 8h20a2 2 0 1 1 0 4H22a2 2 0 1 1 0-4Zm0 10h12a2 2 0 1 1 0 4H22a2 2 0 1 1 0-4Z"/></svg>'
			. '</div>'
			. '<div class="wbccp-empty-text">' . esc_html( $text ) . '</div>'
			. '</div>';
	}
}
