<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	$event_id = get_the_ID();
	$start_ts = (int) get_post_meta( $event_id, 'wbccp_start', true );
	$end_ts   = (int) get_post_meta( $event_id, 'wbccp_end', true );
	$timezone = get_post_meta( $event_id, 'wbccp_timezone', true );
	$settings = class_exists( 'WBCCP_Settings' ) ? WBCCP_Settings::get_settings() : array(
		'default_timezone'    => wp_timezone_string(),
		'show_viewer_timezone' => 0,
	);
	if ( ! $timezone && ! empty( $settings['default_timezone'] ) ) {
		$timezone = $settings['default_timezone'];
	}
	$show_viewer_timezone = ! empty( $settings['show_viewer_timezone'] );
	$tz = null;
	try {
		$tz = new DateTimeZone( $timezone ? $timezone : 'UTC' );
	} catch ( Exception $e ) {
		$tz = null;
	}

	$start_display = $start_ts ? ( $tz ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $start_ts, $tz ) : wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $start_ts ) ) : '';
	$end_display = $end_ts ? ( $tz ? wp_date( get_option( 'time_format' ), $end_ts, $tz ) : wp_date( get_option( 'time_format' ), $end_ts ) ) : '';

	$location = get_post_meta( $event_id, 'wbccp_location', true );
	$link     = get_post_meta( $event_id, 'wbccp_link', true );
	$group_id = (int) get_post_meta( $event_id, 'wbccp_group_id', true );
	$group_name = '';
	if ( $group_id && function_exists( 'groups_get_group' ) ) {
		$group = groups_get_group( array( 'group_id' => $group_id ) );
		if ( ! empty( $group ) && ! empty( $group->name ) ) {
			$group_name = $group->name;
		}
	}
	$group_link = WBCCP_CPT::get_group_calendar_url( $group_id, $event_id );
	$chip_label = $group_id ? __( 'Group Event', 'wb-community-calendar-pro' ) : __( 'Sitewide Event', 'wb-community-calendar-pro' );
	$scope_label = $group_id ? '' : __( 'Sitewide', 'wb-community-calendar-pro' );
	$has_media = has_post_thumbnail();
	$timezone_label = $timezone ? $timezone : '';
	$category_names = wp_get_post_terms( $event_id, WBCCP_CPT::TAX_CATEGORY, array( 'fields' => 'names' ) );
	$tag_names = wp_get_post_terms( $event_id, WBCCP_CPT::TAX_TAG, array( 'fields' => 'names' ) );
	$calendar_start = $start_ts ? gmdate( 'Ymd\\THis\\Z', $start_ts ) : '';
	$calendar_end = $end_ts ? gmdate( 'Ymd\\THis\\Z', $end_ts ) : ( $start_ts ? gmdate( 'Ymd\\THis\\Z', $start_ts + HOUR_IN_SECONDS ) : '' );
	$calendar_details = wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', $event_id ) ), 30, '...' );
	$google_calendar_url = '';
	if ( $calendar_start && $calendar_end ) {
		$google_calendar_url = add_query_arg(
			array(
				'action'  => 'TEMPLATE',
				'text'    => get_the_title( $event_id ),
				'dates'   => $calendar_start . '/' . $calendar_end,
				'details' => $calendar_details,
				'location'=> $location,
			),
			'https://calendar.google.com/calendar/render'
		);
	}
	$counts = class_exists( 'WBCCP_RSVP' ) ? WBCCP_RSVP::get_counts_for_events( array( $event_id ) ) : array();
	$count_data = isset( $counts[ $event_id ] ) ? $counts[ $event_id ] : array(
		'attending' => 0,
		'maybe'     => 0,
		'cant'      => 0,
	);
	$capacity = (int) get_post_meta( $event_id, 'wbccp_capacity', true );
	$spots_left = $capacity ? max( 0, $capacity - ( (int) $count_data['attending'] + (int) $count_data['maybe'] ) ) : 0;
	$user_status = '';
	if ( is_user_logged_in() && class_exists( 'WBCCP_RSVP' ) ) {
		$statuses = WBCCP_RSVP::get_statuses_for_user( array( $event_id ), get_current_user_id() );
		$user_status = isset( $statuses[ $event_id ] ) ? $statuses[ $event_id ] : '';
	}
	$can_rsvp = is_user_logged_in();
	if ( $can_rsvp && $group_id && function_exists( 'groups_is_user_member' ) ) {
		$can_rsvp = groups_is_user_member( get_current_user_id(), $group_id ) || current_user_can( 'manage_options' );
	}
	?>
	<div class="wbccp-event-single">
		<header class="wbccp-event-header<?php echo $has_media ? ' has-media' : ''; ?>">
			<?php if ( $has_media ) : ?>
				<div class="wbccp-event-media">
					<?php the_post_thumbnail( 'large' ); ?>
				</div>
			<?php endif; ?>
			<div class="wbccp-event-header__content">
				<span class="wbccp-event-chip"><?php echo esc_html( $chip_label ); ?></span>
				<h1 class="wbccp-event-title"><?php the_title(); ?></h1>
				<div class="wbccp-event-meta-grid">
					<?php if ( $start_display ) : ?>
						<div class="wbccp-event-meta-item">
							<span><?php esc_html_e( 'Date', 'wb-community-calendar-pro' ); ?></span>
							<strong><?php echo esc_html( $start_display ); ?><?php echo $end_display ? ' - ' . esc_html( $end_display ) : ''; ?></strong>
						</div>
					<?php endif; ?>
					<?php if ( $show_viewer_timezone && $start_ts ) : ?>
						<div class="wbccp-event-meta-item wbccp-event-meta-local">
							<span><?php esc_html_e( 'Your time', 'wb-community-calendar-pro' ); ?></span>
							<strong class="wbccp-event-local-time" data-start-ts="<?php echo esc_attr( $start_ts ); ?>" data-end-ts="<?php echo esc_attr( $end_ts ); ?>"></strong>
						</div>
					<?php endif; ?>
					<?php if ( $timezone_label ) : ?>
						<div class="wbccp-event-meta-item">
							<span><?php esc_html_e( 'Timezone', 'wb-community-calendar-pro' ); ?></span>
							<strong><?php echo esc_html( $timezone_label ); ?></strong>
						</div>
					<?php endif; ?>
					<?php if ( $location ) : ?>
						<div class="wbccp-event-meta-item">
							<span><?php esc_html_e( 'Location', 'wb-community-calendar-pro' ); ?></span>
							<strong><?php echo esc_html( $location ); ?></strong>
						</div>
					<?php endif; ?>
					<?php if ( $group_name ) : ?>
						<div class="wbccp-event-meta-item">
							<span><?php esc_html_e( 'Group', 'wb-community-calendar-pro' ); ?></span>
							<strong><?php echo esc_html( $group_name ); ?></strong>
						</div>
					<?php endif; ?>
					<?php if ( ! empty( $category_names ) && ! is_wp_error( $category_names ) ) : ?>
						<div class="wbccp-event-meta-item">
							<span><?php esc_html_e( 'Category', 'wb-community-calendar-pro' ); ?></span>
							<strong><?php echo esc_html( implode( ', ', $category_names ) ); ?></strong>
						</div>
					<?php endif; ?>
					<?php if ( ! empty( $tag_names ) && ! is_wp_error( $tag_names ) ) : ?>
						<div class="wbccp-event-meta-item">
							<span><?php esc_html_e( 'Tags', 'wb-community-calendar-pro' ); ?></span>
							<strong><?php echo esc_html( implode( ', ', $tag_names ) ); ?></strong>
						</div>
					<?php endif; ?>
					<?php if ( $scope_label ) : ?>
						<div class="wbccp-event-meta-item">
							<span><?php esc_html_e( 'Scope', 'wb-community-calendar-pro' ); ?></span>
							<strong><?php echo esc_html( $scope_label ); ?></strong>
						</div>
					<?php endif; ?>
					<?php if ( $link ) : ?>
						<div class="wbccp-event-meta-item">
							<span><?php esc_html_e( 'Meeting Link', 'wb-community-calendar-pro' ); ?></span>
							<a class="wbccp-event-link" href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $link ); ?></a>
						</div>
					<?php endif; ?>
					<?php if ( class_exists( 'WBCCP_CPT' ) ) : ?>
						<?php $recurrence = WBCCP_CPT::get_recurrence_summary( $event_id ); ?>
						<?php if ( $recurrence ) : ?>
							<div class="wbccp-event-meta-item">
								<span><?php esc_html_e( 'Repeats', 'wb-community-calendar-pro' ); ?></span>
								<strong><?php echo esc_html( $recurrence ); ?></strong>
							</div>
						<?php endif; ?>
					<?php endif; ?>
				</div>
			</div>
		</header>
		<div class="wbccp-event-body">
			<div class="wbccp-event-description">
				<?php the_content(); ?>
			</div>
			<aside class="wbccp-event-sidebar">
				<div class="wbccp-event-card wbccp-event-rsvp">
					<h3><?php esc_html_e( 'RSVP', 'wb-community-calendar-pro' ); ?></h3>
					<?php if ( $can_rsvp ) : ?>
						<form method="post" class="wbccp-event-rsvp-actions" data-event-id="<?php echo esc_attr( $event_id ); ?>">
							<input type="hidden" name="wbccp_action" value="rsvp" />
							<input type="hidden" name="wbccp_event_id" value="<?php echo esc_attr( $event_id ); ?>" />
							<?php wp_nonce_field( 'wbccp_rsvp', 'wbccp_nonce' ); ?>
							<button type="submit" name="wbccp_status" value="attending" class="button wbccp-rsvp-button<?php echo 'attending' === $user_status ? ' is-active' : ''; ?>" data-status="attending"><?php esc_html_e( 'I am attending', 'wb-community-calendar-pro' ); ?></button>
							<button type="submit" name="wbccp_status" value="maybe" class="button wbccp-rsvp-button<?php echo 'maybe' === $user_status ? ' is-active' : ''; ?>" data-status="maybe"><?php esc_html_e( 'Maybe', 'wb-community-calendar-pro' ); ?></button>
							<button type="submit" name="wbccp_status" value="cant" class="button wbccp-rsvp-button<?php echo 'cant' === $user_status ? ' is-active' : ''; ?>" data-status="cant"><?php esc_html_e( 'Cannot attend', 'wb-community-calendar-pro' ); ?></button>
						</form>
					<?php elseif ( is_user_logged_in() ) : ?>
						<p class="wbccp-event-note"><?php esc_html_e( 'You must be a group member to RSVP to this event.', 'wb-community-calendar-pro' ); ?></p>
					<?php else : ?>
						<p class="wbccp-event-note"><?php esc_html_e( 'Please log in to RSVP.', 'wb-community-calendar-pro' ); ?></p>
					<?php endif; ?>
					<div class="wbccp-event-rsvp-message" role="status" aria-live="polite"></div>
					<div class="wbccp-event-rsvp-counts" data-event-id="<?php echo esc_attr( $event_id ); ?>">
						<span data-count="attending"><?php echo esc_html( sprintf( __( 'Attending: %d', 'wb-community-calendar-pro' ), (int) $count_data['attending'] ) ); ?></span>
						<span data-count="maybe"><?php echo esc_html( sprintf( __( 'Maybe: %d', 'wb-community-calendar-pro' ), (int) $count_data['maybe'] ) ); ?></span>
						<span data-count="cant"><?php echo esc_html( sprintf( __( "Can't: %d", 'wb-community-calendar-pro' ), (int) $count_data['cant'] ) ); ?></span>
					</div>
					<?php if ( $capacity ) : ?>
						<div class="wbccp-event-capacity" data-event-id="<?php echo esc_attr( $event_id ); ?>" data-capacity="<?php echo esc_attr( $capacity ); ?>">
							<?php echo esc_html( sprintf( __( 'Capacity: %d', 'wb-community-calendar-pro' ), $capacity ) ); ?>
						</div>
						<div class="wbccp-event-spots" data-event-id="<?php echo esc_attr( $event_id ); ?>" data-capacity="<?php echo esc_attr( $capacity ); ?>">
							<?php
							echo esc_html(
								$spots_left
									? sprintf( __( 'Spots left: %d', 'wb-community-calendar-pro' ), $spots_left )
									: __( 'Event is full.', 'wb-community-calendar-pro' )
							);
							?>
						</div>
					<?php endif; ?>
				</div>
				<div class="wbccp-event-card wbccp-event-actions">
					<h3><?php esc_html_e( 'Event Links', 'wb-community-calendar-pro' ); ?></h3>
					<div class="wbccp-single-actions">
						<?php if ( $group_link ) : ?>
							<a href="<?php echo esc_url( $group_link ); ?>"><?php esc_html_e( 'View in Group Calendar', 'wb-community-calendar-pro' ); ?></a>
						<?php endif; ?>
						<a href="<?php echo esc_url( add_query_arg( array( 'wbccp_ical' => 1, 'event_id' => $event_id ), home_url( '/' ) ) ); ?>"><?php esc_html_e( 'Add to Calendar', 'wb-community-calendar-pro' ); ?></a>
						<?php if ( $google_calendar_url ) : ?>
							<a href="<?php echo esc_url( $google_calendar_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Add to Google Calendar', 'wb-community-calendar-pro' ); ?></a>
						<?php endif; ?>
					</div>
				</div>
			</aside>
		</div>
	</div>
	<?php
endwhile;

get_footer();
