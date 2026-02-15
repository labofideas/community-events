<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	$wbccp_event_id = get_the_ID();
	$wbccp_start_ts = (int) get_post_meta( $wbccp_event_id, 'wbccp_start', true );
	$wbccp_end_ts   = (int) get_post_meta( $wbccp_event_id, 'wbccp_end', true );
	$wbccp_timezone = get_post_meta( $wbccp_event_id, 'wbccp_timezone', true );
	$wbccp_settings = class_exists( 'WBCCP_Settings' ) ? WBCCP_Settings::get_settings() : array(
		'default_timezone'    => wp_timezone_string(),
		'show_viewer_timezone' => 0,
	);
	if ( ! $wbccp_timezone && ! empty( $wbccp_settings['default_timezone'] ) ) {
		$wbccp_timezone = $wbccp_settings['default_timezone'];
	}
	$wbccp_show_viewer_timezone = ! empty( $wbccp_settings['show_viewer_timezone'] );
	$wbccp_tz = null;
	try {
		$wbccp_tz = new DateTimeZone( $wbccp_timezone ? $wbccp_timezone : 'UTC' );
	} catch ( Exception $e ) {
		$wbccp_tz = null;
	}

	$wbccp_start_display = $wbccp_start_ts ? ( $wbccp_tz ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $wbccp_start_ts, $wbccp_tz ) : wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $wbccp_start_ts ) ) : '';
	$wbccp_end_display = $wbccp_end_ts ? ( $wbccp_tz ? wp_date( get_option( 'time_format' ), $wbccp_end_ts, $wbccp_tz ) : wp_date( get_option( 'time_format' ), $wbccp_end_ts ) ) : '';

	$wbccp_location = get_post_meta( $wbccp_event_id, 'wbccp_location', true );
	$wbccp_link     = get_post_meta( $wbccp_event_id, 'wbccp_link', true );
	$wbccp_group_id = (int) get_post_meta( $wbccp_event_id, 'wbccp_group_id', true );
	$wbccp_group_name = '';
	if ( $wbccp_group_id && function_exists( 'groups_get_group' ) ) {
		$wbccp_group = groups_get_group( array( 'group_id' => $wbccp_group_id ) );
		if ( ! empty( $wbccp_group ) && ! empty( $wbccp_group->name ) ) {
			$wbccp_group_name = $wbccp_group->name;
		}
	}
	$wbccp_group_link = WBCCP_CPT::get_group_calendar_url( $wbccp_group_id, $wbccp_event_id );
	$wbccp_chip_label = $wbccp_group_id ? __( 'Group Event', 'wb-community-calendar-pro' ) : __( 'Sitewide Event', 'wb-community-calendar-pro' );
	$wbccp_scope_label = $wbccp_group_id ? '' : __( 'Sitewide', 'wb-community-calendar-pro' );
	$wbccp_has_media = has_post_thumbnail();
	$wbccp_timezone_label = $wbccp_timezone ? $wbccp_timezone : '';
	$wbccp_category_names = wp_get_post_terms( $wbccp_event_id, WBCCP_CPT::TAX_CATEGORY, array( 'fields' => 'names' ) );
	$wbccp_tag_names = wp_get_post_terms( $wbccp_event_id, WBCCP_CPT::TAX_TAG, array( 'fields' => 'names' ) );
	$wbccp_calendar_start = $wbccp_start_ts ? gmdate( 'Ymd\\THis\\Z', $wbccp_start_ts ) : '';
	$wbccp_calendar_end = $wbccp_end_ts ? gmdate( 'Ymd\\THis\\Z', $wbccp_end_ts ) : ( $wbccp_start_ts ? gmdate( 'Ymd\\THis\\Z', $wbccp_start_ts + HOUR_IN_SECONDS ) : '' );
	$wbccp_calendar_details = wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', $wbccp_event_id ) ), 30, '...' );
	$wbccp_google_calendar_url = '';
	if ( $wbccp_calendar_start && $wbccp_calendar_end ) {
		$wbccp_google_calendar_url = add_query_arg(
			array(
				'action'  => 'TEMPLATE',
				'text'    => get_the_title( $wbccp_event_id ),
				'dates'   => $wbccp_calendar_start . '/' . $wbccp_calendar_end,
				'details' => $wbccp_calendar_details,
				'location'=> $wbccp_location,
			),
			'https://calendar.google.com/calendar/render'
		);
	}
	$wbccp_counts = class_exists( 'WBCCP_RSVP' ) ? WBCCP_RSVP::get_counts_for_events( array( $wbccp_event_id ) ) : array();
	$wbccp_count_data = isset( $wbccp_counts[ $wbccp_event_id ] ) ? $wbccp_counts[ $wbccp_event_id ] : array(
		'attending' => 0,
		'maybe'     => 0,
		'cant'      => 0,
	);
	$wbccp_capacity = (int) get_post_meta( $wbccp_event_id, 'wbccp_capacity', true );
	$wbccp_spots_left = $wbccp_capacity ? max( 0, $wbccp_capacity - ( (int) $wbccp_count_data['attending'] + (int) $wbccp_count_data['maybe'] ) ) : 0;
	$wbccp_user_status = '';
	if ( is_user_logged_in() && class_exists( 'WBCCP_RSVP' ) ) {
		$wbccp_statuses = WBCCP_RSVP::get_statuses_for_user( array( $wbccp_event_id ), get_current_user_id() );
		$wbccp_user_status = isset( $wbccp_statuses[ $wbccp_event_id ] ) ? $wbccp_statuses[ $wbccp_event_id ] : '';
	}
	$wbccp_can_rsvp = is_user_logged_in();
	if ( $wbccp_can_rsvp && $wbccp_group_id && function_exists( 'groups_is_user_member' ) ) {
		$wbccp_can_rsvp = groups_is_user_member( get_current_user_id(), $wbccp_group_id ) || current_user_can( 'manage_options' );
	}
	?>
	<div class="wbccp-event-single">
		<header class="wbccp-event-header<?php echo $wbccp_has_media ? ' has-media' : ''; ?>">
			<?php if ( $wbccp_has_media ) : ?>
				<div class="wbccp-event-media">
					<?php the_post_thumbnail( 'large' ); ?>
				</div>
			<?php endif; ?>
			<div class="wbccp-event-header__content">
				<span class="wbccp-event-chip"><?php echo esc_html( $wbccp_chip_label ); ?></span>
				<h1 class="wbccp-event-title"><?php the_title(); ?></h1>
				<div class="wbccp-event-meta-grid">
					<?php if ( $wbccp_start_display ) : ?>
						<div class="wbccp-event-meta-item">
							<span><?php esc_html_e( 'Date', 'wb-community-calendar-pro' ); ?></span>
							<strong><?php echo esc_html( $wbccp_start_display ); ?><?php echo $wbccp_end_display ? ' - ' . esc_html( $wbccp_end_display ) : ''; ?></strong>
						</div>
					<?php endif; ?>
					<?php if ( $wbccp_show_viewer_timezone && $wbccp_start_ts ) : ?>
						<div class="wbccp-event-meta-item wbccp-event-meta-local">
							<span><?php esc_html_e( 'Your time', 'wb-community-calendar-pro' ); ?></span>
							<strong class="wbccp-event-local-time" data-start-ts="<?php echo esc_attr( $wbccp_start_ts ); ?>" data-end-ts="<?php echo esc_attr( $wbccp_end_ts ); ?>"></strong>
						</div>
					<?php endif; ?>
					<?php if ( $wbccp_timezone_label ) : ?>
						<div class="wbccp-event-meta-item">
							<span><?php esc_html_e( 'Timezone', 'wb-community-calendar-pro' ); ?></span>
							<strong><?php echo esc_html( $wbccp_timezone_label ); ?></strong>
						</div>
					<?php endif; ?>
					<?php if ( $wbccp_location ) : ?>
						<div class="wbccp-event-meta-item">
							<span><?php esc_html_e( 'Location', 'wb-community-calendar-pro' ); ?></span>
							<strong><?php echo esc_html( $wbccp_location ); ?></strong>
						</div>
					<?php endif; ?>
					<?php if ( $wbccp_group_name ) : ?>
						<div class="wbccp-event-meta-item">
							<span><?php esc_html_e( 'Group', 'wb-community-calendar-pro' ); ?></span>
							<strong><?php echo esc_html( $wbccp_group_name ); ?></strong>
						</div>
					<?php endif; ?>
					<?php if ( ! empty( $wbccp_category_names ) && ! is_wp_error( $wbccp_category_names ) ) : ?>
						<div class="wbccp-event-meta-item">
							<span><?php esc_html_e( 'Category', 'wb-community-calendar-pro' ); ?></span>
							<strong><?php echo esc_html( implode( ', ', $wbccp_category_names ) ); ?></strong>
						</div>
					<?php endif; ?>
					<?php if ( ! empty( $wbccp_tag_names ) && ! is_wp_error( $wbccp_tag_names ) ) : ?>
						<div class="wbccp-event-meta-item">
							<span><?php esc_html_e( 'Tags', 'wb-community-calendar-pro' ); ?></span>
							<strong><?php echo esc_html( implode( ', ', $wbccp_tag_names ) ); ?></strong>
						</div>
					<?php endif; ?>
					<?php if ( $wbccp_scope_label ) : ?>
						<div class="wbccp-event-meta-item">
							<span><?php esc_html_e( 'Scope', 'wb-community-calendar-pro' ); ?></span>
							<strong><?php echo esc_html( $wbccp_scope_label ); ?></strong>
						</div>
					<?php endif; ?>
					<?php if ( $wbccp_link ) : ?>
						<div class="wbccp-event-meta-item">
							<span><?php esc_html_e( 'Meeting Link', 'wb-community-calendar-pro' ); ?></span>
							<a class="wbccp-event-link" href="<?php echo esc_url( $wbccp_link ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $wbccp_link ); ?></a>
						</div>
					<?php endif; ?>
					<?php if ( class_exists( 'WBCCP_CPT' ) ) : ?>
						<?php $wbccp_recurrence = WBCCP_CPT::get_recurrence_summary( $wbccp_event_id ); ?>
						<?php if ( $wbccp_recurrence ) : ?>
							<div class="wbccp-event-meta-item">
								<span><?php esc_html_e( 'Repeats', 'wb-community-calendar-pro' ); ?></span>
								<strong><?php echo esc_html( $wbccp_recurrence ); ?></strong>
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
					<?php if ( $wbccp_can_rsvp ) : ?>
						<form method="post" class="wbccp-event-rsvp-actions" data-event-id="<?php echo esc_attr( $wbccp_event_id ); ?>">
							<input type="hidden" name="wbccp_action" value="rsvp" />
							<input type="hidden" name="wbccp_event_id" value="<?php echo esc_attr( $wbccp_event_id ); ?>" />
							<?php wp_nonce_field( 'wbccp_rsvp', 'wbccp_nonce' ); ?>
							<button type="submit" name="wbccp_status" value="attending" class="button wbccp-rsvp-button<?php echo 'attending' === $wbccp_user_status ? ' is-active' : ''; ?>" data-status="attending"><?php esc_html_e( 'I am attending', 'wb-community-calendar-pro' ); ?></button>
							<button type="submit" name="wbccp_status" value="maybe" class="button wbccp-rsvp-button<?php echo 'maybe' === $wbccp_user_status ? ' is-active' : ''; ?>" data-status="maybe"><?php esc_html_e( 'Maybe', 'wb-community-calendar-pro' ); ?></button>
							<button type="submit" name="wbccp_status" value="cant" class="button wbccp-rsvp-button<?php echo 'cant' === $wbccp_user_status ? ' is-active' : ''; ?>" data-status="cant"><?php esc_html_e( 'Cannot attend', 'wb-community-calendar-pro' ); ?></button>
						</form>
					<?php elseif ( is_user_logged_in() ) : ?>
						<p class="wbccp-event-note"><?php esc_html_e( 'You must be a group member to RSVP to this event.', 'wb-community-calendar-pro' ); ?></p>
					<?php else : ?>
						<p class="wbccp-event-note"><?php esc_html_e( 'Please log in to RSVP.', 'wb-community-calendar-pro' ); ?></p>
					<?php endif; ?>
					<div class="wbccp-event-rsvp-message" role="status" aria-live="polite"></div>
						<div class="wbccp-event-rsvp-counts" data-event-id="<?php echo esc_attr( $wbccp_event_id ); ?>">
							<?php /* translators: %d: attendees count. */ ?>
							<span data-count="attending"><?php echo esc_html( sprintf( __( 'Attending: %d', 'wb-community-calendar-pro' ), (int) $wbccp_count_data['attending'] ) ); ?></span>
							<?php /* translators: %d: maybe count. */ ?>
							<span data-count="maybe"><?php echo esc_html( sprintf( __( 'Maybe: %d', 'wb-community-calendar-pro' ), (int) $wbccp_count_data['maybe'] ) ); ?></span>
							<?php /* translators: %d: cannot attend count. */ ?>
							<span data-count="cant"><?php echo esc_html( sprintf( __( "Can't: %d", 'wb-community-calendar-pro' ), (int) $wbccp_count_data['cant'] ) ); ?></span>
						</div>
						<?php if ( $wbccp_capacity ) : ?>
							<div class="wbccp-event-capacity" data-event-id="<?php echo esc_attr( $wbccp_event_id ); ?>" data-capacity="<?php echo esc_attr( $wbccp_capacity ); ?>">
								<?php /* translators: %d: event capacity. */ ?>
								<?php echo esc_html( sprintf( __( 'Capacity: %d', 'wb-community-calendar-pro' ), $wbccp_capacity ) ); ?>
							</div>
						<div class="wbccp-event-spots" data-event-id="<?php echo esc_attr( $wbccp_event_id ); ?>" data-capacity="<?php echo esc_attr( $wbccp_capacity ); ?>">
							<?php
								echo esc_html(
									$wbccp_spots_left
										/* translators: %d: remaining available spots. */
										? sprintf( __( 'Spots left: %d', 'wb-community-calendar-pro' ), $wbccp_spots_left )
										: __( 'Event is full.', 'wb-community-calendar-pro' )
								);
							?>
						</div>
					<?php endif; ?>
				</div>
				<div class="wbccp-event-card wbccp-event-actions">
					<h3><?php esc_html_e( 'Event Links', 'wb-community-calendar-pro' ); ?></h3>
					<div class="wbccp-single-actions">
						<?php if ( $wbccp_group_link ) : ?>
							<a href="<?php echo esc_url( $wbccp_group_link ); ?>"><?php esc_html_e( 'View in Group Calendar', 'wb-community-calendar-pro' ); ?></a>
						<?php endif; ?>
						<a href="<?php echo esc_url( add_query_arg( array( 'wbccp_ical' => 1, 'event_id' => $wbccp_event_id ), home_url( '/' ) ) ); ?>"><?php esc_html_e( 'Add to Calendar', 'wb-community-calendar-pro' ); ?></a>
						<?php if ( $wbccp_google_calendar_url ) : ?>
							<a href="<?php echo esc_url( $wbccp_google_calendar_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Add to Google Calendar', 'wb-community-calendar-pro' ); ?></a>
						<?php endif; ?>
					</div>
				</div>
			</aside>
		</div>
	</div>
	<?php
endwhile;

get_footer();
