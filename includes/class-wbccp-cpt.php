<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WBCCP_CPT {
	const CPT = 'wb_community_event';
	const TAX_CATEGORY = 'wbccp_event_category';
	const TAX_TAG = 'wbccp_event_tag';
	private static $validation_errors = array();

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_cpt' ) );
		add_action( 'init', array( __CLASS__, 'register_taxonomies' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_meta_boxes' ) );
		add_action( 'save_post_' . self::CPT, array( __CLASS__, 'save_meta' ) );
		add_action( 'before_delete_post', array( __CLASS__, 'handle_delete' ) );
		add_action( 'admin_init', array( __CLASS__, 'remove_default_editor' ) );
		add_action( 'edit_form_top', array( __CLASS__, 'render_event_header' ) );
		add_filter( 'admin_body_class', array( __CLASS__, 'add_event_body_class' ) );
		add_filter( 'enter_title_here', array( __CLASS__, 'filter_title_placeholder' ), 10, 2 );
		add_filter( 'redirect_post_location', array( __CLASS__, 'add_validation_query_args' ), 10, 2 );
		add_action( 'admin_notices', array( __CLASS__, 'render_admin_notices' ) );
		add_filter( 'single_template', array( __CLASS__, 'load_single_template' ) );
		add_filter( 'post_row_actions', array( __CLASS__, 'add_row_actions' ), 10, 2 );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_export_ical' ) );
	}

	public static function register_cpt() {
		$labels = array(
			'name'               => __( 'Community Events', 'wb-community-calendar-pro' ),
			'singular_name'      => __( 'Community Event', 'wb-community-calendar-pro' ),
			'add_new'            => __( 'Add New', 'wb-community-calendar-pro' ),
			'add_new_item'       => __( 'Add New Event', 'wb-community-calendar-pro' ),
			'edit_item'          => __( 'Edit Event', 'wb-community-calendar-pro' ),
			'new_item'           => __( 'New Event', 'wb-community-calendar-pro' ),
			'view_item'          => __( 'View Event', 'wb-community-calendar-pro' ),
			'view_items'         => __( 'View Events', 'wb-community-calendar-pro' ),
			'search_items'       => __( 'Search Events', 'wb-community-calendar-pro' ),
			'not_found'          => __( 'No events found.', 'wb-community-calendar-pro' ),
			'not_found_in_trash' => __( 'No events found in Trash.', 'wb-community-calendar-pro' ),
			'all_items'          => __( 'Community Events', 'wb-community-calendar-pro' ),
			'menu_name'          => __( 'Community Events', 'wb-community-calendar-pro' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'exclude_from_search' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'menu_position'      => 25,
			'menu_icon'          => 'dashicons-calendar-alt',
			'show_in_rest'       => true,
			'capability_type'    => 'post',
			'hierarchical'       => false,
			'supports'           => array( 'title', 'editor', 'thumbnail' ),
			'has_archive'        => false,
			'rewrite'            => array(
				'slug'       => 'community-event',
				'with_front' => false,
			),
		);

		register_post_type( self::CPT, $args );
	}

	public static function register_taxonomies() {
		$category_labels = array(
			'name'              => __( 'Event Categories', 'wb-community-calendar-pro' ),
			'singular_name'     => __( 'Event Category', 'wb-community-calendar-pro' ),
			'search_items'      => __( 'Search Event Categories', 'wb-community-calendar-pro' ),
			'all_items'         => __( 'All Event Categories', 'wb-community-calendar-pro' ),
			'parent_item'       => __( 'Parent Event Category', 'wb-community-calendar-pro' ),
			'parent_item_colon' => __( 'Parent Event Category:', 'wb-community-calendar-pro' ),
			'edit_item'         => __( 'Edit Event Category', 'wb-community-calendar-pro' ),
			'update_item'       => __( 'Update Event Category', 'wb-community-calendar-pro' ),
			'add_new_item'      => __( 'Add New Event Category', 'wb-community-calendar-pro' ),
			'new_item_name'     => __( 'New Event Category Name', 'wb-community-calendar-pro' ),
			'menu_name'         => __( 'Event Categories', 'wb-community-calendar-pro' ),
		);

		register_taxonomy(
			self::TAX_CATEGORY,
			array( self::CPT ),
			array(
				'hierarchical'      => true,
				'labels'            => $category_labels,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array( 'slug' => 'community-event-category' ),
			)
		);

		$tag_labels = array(
			'name'                       => __( 'Event Tags', 'wb-community-calendar-pro' ),
			'singular_name'              => __( 'Event Tag', 'wb-community-calendar-pro' ),
			'search_items'               => __( 'Search Event Tags', 'wb-community-calendar-pro' ),
			'popular_items'              => __( 'Popular Event Tags', 'wb-community-calendar-pro' ),
			'all_items'                  => __( 'All Event Tags', 'wb-community-calendar-pro' ),
			'edit_item'                  => __( 'Edit Event Tag', 'wb-community-calendar-pro' ),
			'update_item'                => __( 'Update Event Tag', 'wb-community-calendar-pro' ),
			'add_new_item'               => __( 'Add New Event Tag', 'wb-community-calendar-pro' ),
			'new_item_name'              => __( 'New Event Tag Name', 'wb-community-calendar-pro' ),
			'separate_items_with_commas' => __( 'Separate tags with commas', 'wb-community-calendar-pro' ),
			'add_or_remove_items'        => __( 'Add or remove tags', 'wb-community-calendar-pro' ),
			'choose_from_most_used'      => __( 'Choose from the most used tags', 'wb-community-calendar-pro' ),
			'menu_name'                  => __( 'Event Tags', 'wb-community-calendar-pro' ),
		);

		register_taxonomy(
			self::TAX_TAG,
			array( self::CPT ),
			array(
				'hierarchical'      => false,
				'labels'            => $tag_labels,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array( 'slug' => 'community-event-tag' ),
			)
		);
	}

	public static function register_meta_boxes() {
		add_meta_box(
			'wbccp_event_details',
			__( 'Event Details', 'wb-community-calendar-pro' ),
			array( __CLASS__, 'render_meta_box' ),
			self::CPT,
			'normal',
			'default'
		);

		add_meta_box(
			'wbccp_event_description',
			__( 'Event Description', 'wb-community-calendar-pro' ),
			array( __CLASS__, 'render_description_meta_box' ),
			self::CPT,
			'normal',
			'default'
		);

		add_meta_box(
			'wbccp_event_publish',
			__( 'Publish Event', 'wb-community-calendar-pro' ),
			array( __CLASS__, 'render_publish_meta_box' ),
			self::CPT,
			'side',
			'high'
		);
	}

	public static function render_meta_box( $post ) {
		wp_nonce_field( 'wbccp_save_event', 'wbccp_event_nonce' );

		$group_id = (int) get_post_meta( $post->ID, 'wbccp_group_id', true );
		$start    = get_post_meta( $post->ID, 'wbccp_start', true );
		$end      = get_post_meta( $post->ID, 'wbccp_end', true );
		$timezone = get_post_meta( $post->ID, 'wbccp_timezone', true );
		$location = get_post_meta( $post->ID, 'wbccp_location', true );
		$link     = get_post_meta( $post->ID, 'wbccp_link', true );
		$capacity = (int) get_post_meta( $post->ID, 'wbccp_capacity', true );
		$recur_enabled = (int) get_post_meta( $post->ID, 'wbccp_recur_enabled', true );
		$recur_freq = get_post_meta( $post->ID, 'wbccp_recur_freq', true );
		$recur_interval = (int) get_post_meta( $post->ID, 'wbccp_recur_interval', true );
		$recur_byday = get_post_meta( $post->ID, 'wbccp_recur_byday', true );
		$recur_bymonthday = (int) get_post_meta( $post->ID, 'wbccp_recur_bymonthday', true );
		$recur_bysetpos = (int) get_post_meta( $post->ID, 'wbccp_recur_bysetpos', true );
		$recur_byweekday = get_post_meta( $post->ID, 'wbccp_recur_byweekday', true );
		$recur_count = (int) get_post_meta( $post->ID, 'wbccp_recur_count', true );
		$recur_until = get_post_meta( $post->ID, 'wbccp_recur_until', true );
		if ( ! is_array( $recur_byday ) ) {
			$recur_byday = $recur_byday ? array( $recur_byday ) : array();
		}

		if ( ! $timezone && class_exists( 'WBCCP_Settings' ) ) {
			$settings = WBCCP_Settings::get_settings();
			$timezone = $settings['default_timezone'];
		}
		$settings = class_exists( 'WBCCP_Settings' ) ? WBCCP_Settings::get_settings() : array(
			'allow_sitewide_events' => 0,
		);

		$groups = array();
		if ( function_exists( 'groups_get_groups' ) ) {
			$groups = groups_get_groups(
				array(
					'per_page' => 100,
					'page'     => 1,
				)
			);
		}

		$category_terms = get_terms(
			array(
				'taxonomy'   => self::TAX_CATEGORY,
				'hide_empty' => false,
			)
		);
		$selected_categories = wp_get_post_terms(
			$post->ID,
			self::TAX_CATEGORY,
			array( 'fields' => 'ids' )
		);
		$selected_tags = wp_get_post_terms(
			$post->ID,
			self::TAX_TAG,
			array( 'fields' => 'names' )
		);
		$tag_value = $selected_tags ? implode( ', ', $selected_tags ) : '';
		?>
		<div class="wbccp-admin-card wbccp-admin-wrap">
			<div class="wbccp-admin-grid">
				<div class="wbccp-admin-field">
					<label for="wbccp_group_id"><?php esc_html_e( 'Group ID', 'wb-community-calendar-pro' ); ?></label>
					<?php if ( ! empty( $groups['groups'] ) ) : ?>
						<input type="text" id="wbccp-group-filter" placeholder="<?php esc_attr_e( 'Search group name...', 'wb-community-calendar-pro' ); ?>" />
						<select id="wbccp_group_id" name="wbccp_group_id">
							<option value=""><?php esc_html_e( 'Select a group', 'wb-community-calendar-pro' ); ?></option>
							<?php if ( ! empty( $settings['allow_sitewide_events'] ) || 0 === $group_id ) : ?>
								<option value="0" <?php selected( 0, $group_id ); ?>>
									<?php esc_html_e( 'Sitewide (no group)', 'wb-community-calendar-pro' ); ?>
								</option>
							<?php endif; ?>
							<?php foreach ( $groups['groups'] as $group ) : ?>
								<option value="<?php echo esc_attr( $group->id ); ?>" <?php selected( $group_id, $group->id ); ?>>
									<?php echo esc_html( $group->name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					<?php else : ?>
						<input type="number" id="wbccp_group_id" name="wbccp_group_id" value="<?php echo esc_attr( $group_id ); ?>" />
					<?php endif; ?>
					<div class="wbccp-admin-note">
						<?php
						if ( ! empty( $settings['allow_sitewide_events'] ) ) {
							esc_html_e( 'Attach to a BuddyPress group, or choose Sitewide to publish across the community.', 'wb-community-calendar-pro' );
						} else {
							esc_html_e( 'Attach this event to a BuddyPress group.', 'wb-community-calendar-pro' );
						}
						?>
					</div>
				</div>
				<div class="wbccp-admin-field">
					<label for="wbccp_timezone"><?php esc_html_e( 'Timezone', 'wb-community-calendar-pro' ); ?></label>
					<input type="text" id="wbccp_timezone" name="wbccp_timezone" value="<?php echo esc_attr( $timezone ); ?>" />
					<div class="wbccp-admin-note"><?php esc_html_e( 'Example: Europe/Berlin', 'wb-community-calendar-pro' ); ?></div>
				</div>
				<div class="wbccp-admin-field">
					<label for="wbccp_start"><?php esc_html_e( 'Start', 'wb-community-calendar-pro' ); ?></label>
					<input type="datetime-local" id="wbccp_start" name="wbccp_start" value="<?php echo esc_attr( $start ? gmdate( 'Y-m-d\\TH:i', (int) $start ) : '' ); ?>" />
				</div>
				<div class="wbccp-admin-field">
					<label for="wbccp_end"><?php esc_html_e( 'End', 'wb-community-calendar-pro' ); ?></label>
					<input type="datetime-local" id="wbccp_end" name="wbccp_end" value="<?php echo esc_attr( $end ? gmdate( 'Y-m-d\\TH:i', (int) $end ) : '' ); ?>" />
				</div>
				<div class="wbccp-admin-field">
					<label for="wbccp_location"><?php esc_html_e( 'Location', 'wb-community-calendar-pro' ); ?></label>
					<input type="text" id="wbccp_location" name="wbccp_location" value="<?php echo esc_attr( $location ); ?>" />
				</div>
				<div class="wbccp-admin-field">
					<label for="wbccp_link"><?php esc_html_e( 'Meeting Link', 'wb-community-calendar-pro' ); ?></label>
					<input type="url" id="wbccp_link" name="wbccp_link" value="<?php echo esc_attr( $link ); ?>" />
				</div>
				<div class="wbccp-admin-field">
					<label for="wbccp_capacity"><?php esc_html_e( 'Capacity', 'wb-community-calendar-pro' ); ?></label>
					<input type="number" id="wbccp_capacity" name="wbccp_capacity" min="0" value="<?php echo esc_attr( $capacity ); ?>" />
					<div class="wbccp-admin-note"><?php esc_html_e( 'Maximum attendees (Attending + Maybe). Use 0 for unlimited.', 'wb-community-calendar-pro' ); ?></div>
				</div>
				<div class="wbccp-admin-field">
					<label for="wbccp_categories"><?php esc_html_e( 'Event Categories', 'wb-community-calendar-pro' ); ?></label>
					<select id="wbccp_categories" name="wbccp_categories[]" multiple>
						<?php if ( ! empty( $category_terms ) && ! is_wp_error( $category_terms ) ) : ?>
							<?php foreach ( $category_terms as $term ) : ?>
								<option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( in_array( $term->term_id, $selected_categories, true ) ); ?>>
									<?php echo esc_html( $term->name ); ?>
								</option>
							<?php endforeach; ?>
						<?php endif; ?>
					</select>
				</div>
				<div class="wbccp-admin-field">
					<label for="wbccp_tags"><?php esc_html_e( 'Event Tags', 'wb-community-calendar-pro' ); ?></label>
					<input type="text" id="wbccp_tags" name="wbccp_tags" value="<?php echo esc_attr( $tag_value ); ?>" />
					<div class="wbccp-admin-note"><?php esc_html_e( 'Comma-separated tags (e.g., meetup, online).', 'wb-community-calendar-pro' ); ?></div>
				</div>
			</div>
			<hr />
			<h3><?php esc_html_e( 'Recurrence', 'wb-community-calendar-pro' ); ?></h3>
			<div class="wbccp-recurrence-fields">
				<div class="wbccp-admin-grid">
				<div class="wbccp-admin-field">
					<label>
						<input type="checkbox" class="wbccp-recur-enabled" name="wbccp_recur_enabled" value="1" <?php checked( 1, $recur_enabled ); ?> />
						<?php esc_html_e( 'Repeat this event', 'wb-community-calendar-pro' ); ?>
					</label>
				</div>
				</div>
				<div class="wbccp-recur-details">
					<div class="wbccp-admin-grid">
				<div class="wbccp-admin-field">
					<label for="wbccp_recur_freq"><?php esc_html_e( 'Frequency', 'wb-community-calendar-pro' ); ?></label>
					<select id="wbccp_recur_freq" class="wbccp-recur-freq" name="wbccp_recur_freq">
						<option value=""><?php esc_html_e( 'Select', 'wb-community-calendar-pro' ); ?></option>
						<option value="DAILY" <?php selected( $recur_freq, 'DAILY' ); ?>><?php esc_html_e( 'Daily', 'wb-community-calendar-pro' ); ?></option>
						<option value="WEEKLY" <?php selected( $recur_freq, 'WEEKLY' ); ?>><?php esc_html_e( 'Weekly', 'wb-community-calendar-pro' ); ?></option>
						<option value="MONTHLY" <?php selected( $recur_freq, 'MONTHLY' ); ?>><?php esc_html_e( 'Monthly', 'wb-community-calendar-pro' ); ?></option>
						<option value="YEARLY" <?php selected( $recur_freq, 'YEARLY' ); ?>><?php esc_html_e( 'Yearly', 'wb-community-calendar-pro' ); ?></option>
					</select>
				</div>
				<div class="wbccp-admin-field">
					<label for="wbccp_recur_interval"><?php esc_html_e( 'Repeat every', 'wb-community-calendar-pro' ); ?></label>
					<input type="number" class="wbccp-recur-interval" min="1" id="wbccp_recur_interval" name="wbccp_recur_interval" value="<?php echo esc_attr( $recur_interval ? $recur_interval : 1 ); ?>" />
					<div class="wbccp-admin-note"><?php esc_html_e( 'Interval of the frequency (e.g., every 2 weeks).', 'wb-community-calendar-pro' ); ?></div>
				</div>
				<div class="wbccp-admin-field wbccp-recur-weekly">
					<label><?php esc_html_e( 'Repeat on (weekdays)', 'wb-community-calendar-pro' ); ?></label>
					<div class="wbccp-admin-inline">
						<?php
						$weekdays = array( 'MO' => __( 'Mon', 'wb-community-calendar-pro' ), 'TU' => __( 'Tue', 'wb-community-calendar-pro' ), 'WE' => __( 'Wed', 'wb-community-calendar-pro' ), 'TH' => __( 'Thu', 'wb-community-calendar-pro' ), 'FR' => __( 'Fri', 'wb-community-calendar-pro' ), 'SA' => __( 'Sat', 'wb-community-calendar-pro' ), 'SU' => __( 'Sun', 'wb-community-calendar-pro' ) );
						foreach ( $weekdays as $code => $label ) :
							?>
							<label>
								<input type="checkbox" class="wbccp-recur-byday" name="wbccp_recur_byday[]" value="<?php echo esc_attr( $code ); ?>" <?php checked( in_array( $code, $recur_byday, true ) ); ?> />
								<?php echo esc_html( $label ); ?>
							</label>
						<?php endforeach; ?>
					</div>
				</div>
				<div class="wbccp-admin-field wbccp-recur-monthly wbccp-recur-bymonthday-field">
					<label for="wbccp_recur_bymonthday"><?php esc_html_e( 'Day of month', 'wb-community-calendar-pro' ); ?></label>
					<input type="number" class="wbccp-recur-bymonthday" min="1" max="31" id="wbccp_recur_bymonthday" name="wbccp_recur_bymonthday" value="<?php echo esc_attr( $recur_bymonthday ); ?>" />
					<div class="wbccp-admin-note"><?php esc_html_e( 'Use for monthly/yearly repeats (e.g., 15).', 'wb-community-calendar-pro' ); ?></div>
				</div>
				<div class="wbccp-admin-field wbccp-recur-monthly wbccp-recur-nth">
					<label><?php esc_html_e( 'Or nth weekday', 'wb-community-calendar-pro' ); ?></label>
					<div class="wbccp-admin-inline">
						<select name="wbccp_recur_bysetpos" class="wbccp-recur-bysetpos">
							<option value=""><?php esc_html_e( 'Select', 'wb-community-calendar-pro' ); ?></option>
							<option value="1" <?php selected( $recur_bysetpos, 1 ); ?>><?php esc_html_e( 'First', 'wb-community-calendar-pro' ); ?></option>
							<option value="2" <?php selected( $recur_bysetpos, 2 ); ?>><?php esc_html_e( 'Second', 'wb-community-calendar-pro' ); ?></option>
							<option value="3" <?php selected( $recur_bysetpos, 3 ); ?>><?php esc_html_e( 'Third', 'wb-community-calendar-pro' ); ?></option>
							<option value="4" <?php selected( $recur_bysetpos, 4 ); ?>><?php esc_html_e( 'Fourth', 'wb-community-calendar-pro' ); ?></option>
							<option value="-1" <?php selected( $recur_bysetpos, -1 ); ?>><?php esc_html_e( 'Last', 'wb-community-calendar-pro' ); ?></option>
						</select>
						<select name="wbccp_recur_byweekday" class="wbccp-recur-byweekday">
							<option value=""><?php esc_html_e( 'Weekday', 'wb-community-calendar-pro' ); ?></option>
							<?php foreach ( $weekdays as $code => $label ) : ?>
								<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $recur_byweekday, $code ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
				<div class="wbccp-admin-field">
					<label for="wbccp_recur_count"><?php esc_html_e( 'Occurrences (count)', 'wb-community-calendar-pro' ); ?></label>
					<input type="number" class="wbccp-recur-count" min="1" id="wbccp_recur_count" name="wbccp_recur_count" value="<?php echo esc_attr( $recur_count ); ?>" />
				</div>
				<div class="wbccp-admin-field">
					<label for="wbccp_recur_until"><?php esc_html_e( 'Repeat until', 'wb-community-calendar-pro' ); ?></label>
					<input type="date" class="wbccp-recur-until" id="wbccp_recur_until" name="wbccp_recur_until" value="<?php echo esc_attr( $recur_until ); ?>" />
				</div>
			</div>
			</div>
		</div>
		<?php
	}

	public static function render_description_meta_box( $post ) {
		wp_editor(
			$post->post_content,
			'wbccp_event_description',
			array(
				'textarea_name' => 'wbccp_description',
				'textarea_rows' => 8,
				'media_buttons' => false,
				'editor_class'  => 'wbccp-editor',
			)
		);
	}

	public static function render_publish_meta_box( $post ) {
		$status = $post->post_status;
		$button_label = 'publish' === $status ? __( 'Update Event', 'wb-community-calendar-pro' ) : __( 'Publish Event', 'wb-community-calendar-pro' );

		echo '<div class="wbccp-publish-box">';
		echo '<p><strong>' . esc_html__( 'Status:', 'wb-community-calendar-pro' ) . '</strong> ' . esc_html( ucfirst( $status ) ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Visibility:', 'wb-community-calendar-pro' ) . '</strong> ' . esc_html__( 'Public', 'wb-community-calendar-pro' ) . '</p>';
		echo '<p>';
		submit_button( $button_label, 'primary', 'publish', false );
		echo ' ';
		submit_button( __( 'Save Draft', 'wb-community-calendar-pro' ), 'secondary', 'save', false );
		echo '</p>';
		echo '</div>';
	}

	public static function remove_default_editor() {
		remove_post_type_support( self::CPT, 'editor' );
	}

	public static function add_event_body_class( $classes ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && self::CPT === $screen->post_type ) {
			$classes .= ' wbccp-event-editor';
		}

		return $classes;
	}

	public static function render_event_header( $post ) {
		if ( ! $post || self::CPT !== $post->post_type ) {
			return;
		}

		echo '<div class="wbccp-event-header">';
		echo '<div class="wbccp-event-header__icon"><span class="dashicons dashicons-calendar-alt"></span></div>';
		echo '<div class="wbccp-event-header__content">';
		echo '<h1>' . esc_html__( 'Create Community Event', 'wb-community-calendar-pro' ) . '</h1>';
		echo '<p>' . esc_html__( 'Add the essentials, set the schedule, and publish to your group calendar.', 'wb-community-calendar-pro' ) . '</p>';
		echo '</div>';
		echo '</div>';
	}

	public static function filter_title_placeholder( $title, $post ) {
		if ( self::CPT === $post->post_type ) {
			$title = __( 'Add event title', 'wb-community-calendar-pro' );
		}

		return $title;
	}

	public static function save_meta( $post_id ) {
		if ( ! isset( $_POST['wbccp_event_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( wp_unslash( $_POST['wbccp_event_nonce'] ), 'wbccp_save_event' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$original_status = get_post_status( $post_id );

		$group_id = isset( $_POST['wbccp_group_id'] ) ? (int) $_POST['wbccp_group_id'] : 0;
		update_post_meta( $post_id, 'wbccp_group_id', $group_id );

		$timezone = isset( $_POST['wbccp_timezone'] ) ? sanitize_text_field( wp_unslash( $_POST['wbccp_timezone'] ) ) : '';
		if ( $timezone && ! in_array( $timezone, timezone_identifiers_list(), true ) ) {
			$timezone = '';
		}
		if ( ! $timezone && class_exists( 'WBCCP_Settings' ) ) {
			$settings = WBCCP_Settings::get_settings();
			$timezone = $settings['default_timezone'];
		}

		$start_raw = isset( $_POST['wbccp_start'] ) ? sanitize_text_field( wp_unslash( $_POST['wbccp_start'] ) ) : '';
		$end_raw   = isset( $_POST['wbccp_end'] ) ? sanitize_text_field( wp_unslash( $_POST['wbccp_end'] ) ) : '';

		$start_ts = self::parse_datetime_to_utc( $start_raw, $timezone );
		$end_ts   = self::parse_datetime_to_utc( $end_raw, $timezone );

		update_post_meta( $post_id, 'wbccp_start', $start_ts );
		update_post_meta( $post_id, 'wbccp_end', $end_ts );

		update_post_meta( $post_id, 'wbccp_timezone', $timezone );

		$location = isset( $_POST['wbccp_location'] ) ? sanitize_text_field( wp_unslash( $_POST['wbccp_location'] ) ) : '';
		update_post_meta( $post_id, 'wbccp_location', $location );

		$link = isset( $_POST['wbccp_link'] ) ? esc_url_raw( wp_unslash( $_POST['wbccp_link'] ) ) : '';
		update_post_meta( $post_id, 'wbccp_link', $link );

		$capacity = isset( $_POST['wbccp_capacity'] ) ? absint( $_POST['wbccp_capacity'] ) : 0;
		update_post_meta( $post_id, 'wbccp_capacity', $capacity );

		self::save_recurrence_meta( $post_id, $_POST );
		self::save_taxonomies_from_request( $post_id, $_POST );

		self::$validation_errors = array();
		$settings = class_exists( 'WBCCP_Settings' ) ? WBCCP_Settings::get_settings() : array( 'allow_sitewide_events' => 0 );
		if ( empty( $group_id ) && empty( $settings['allow_sitewide_events'] ) ) {
			self::$validation_errors[] = 'missing_group';
		}
		if ( empty( $start_ts ) ) {
			self::$validation_errors[] = 'missing_start';
		}

		if ( ! empty( self::$validation_errors ) ) {
			remove_action( 'save_post_' . self::CPT, array( __CLASS__, 'save_meta' ) );
			wp_update_post(
				array(
					'ID'          => $post_id,
					'post_status' => 'draft',
				)
			);
			add_action( 'save_post_' . self::CPT, array( __CLASS__, 'save_meta' ) );
		} elseif ( 'draft' === $original_status && 'publish' === get_post_status( $post_id ) ) {
			// Leave published posts as-is when validation passes.
		}

		if ( isset( $_POST['wbccp_description'] ) ) {
			$description = wp_kses_post( wp_unslash( $_POST['wbccp_description'] ) );
			remove_action( 'save_post_' . self::CPT, array( __CLASS__, 'save_meta' ) );
			wp_update_post(
				array(
					'ID'           => $post_id,
					'post_content' => $description,
				)
			);
			add_action( 'save_post_' . self::CPT, array( __CLASS__, 'save_meta' ) );
		}

		if ( empty( self::$validation_errors ) && class_exists( 'WBCCP_Notifications' ) ) {
			$settings = WBCCP_Settings::get_settings();
			$current_status = get_post_status( $post_id );
			if ( 'publish' === $current_status && 'publish' !== $original_status && ! empty( $settings['notify_create'] ) ) {
				WBCCP_Notifications::send_event_created( $post_id );
			} elseif ( 'publish' === $current_status && 'publish' === $original_status && ! empty( $settings['notify_update'] ) ) {
				WBCCP_Notifications::send_event_updated( $post_id );
			}
			if ( 'publish' === $current_status ) {
				WBCCP_Notifications::schedule_event_reminders( $post_id );
			} else {
				WBCCP_Notifications::clear_scheduled_reminders( $post_id );
			}
		}
	}

	public static function handle_delete( $post_id ) {
		if ( self::CPT !== get_post_type( $post_id ) ) {
			return;
		}

		if ( class_exists( 'WBCCP_RSVP' ) ) {
			WBCCP_RSVP::delete_by_event_ids( array( (int) $post_id ) );
		}
		if ( class_exists( 'WBCCP_Notifications' ) ) {
			WBCCP_Notifications::clear_scheduled_reminders( (int) $post_id );
		}
	}

	public static function add_validation_query_args( $location, $post_id ) {
		if ( get_post_type( $post_id ) !== self::CPT ) {
			return $location;
		}

		if ( empty( self::$validation_errors ) ) {
			return $location;
		}

		foreach ( self::$validation_errors as $error ) {
			$location = add_query_arg( array( 'wbccp_error[]' => $error ), $location );
		}

		return $location;
	}

	public static function load_single_template( $template ) {
		if ( is_singular( self::CPT ) ) {
			$plugin_template = trailingslashit( WBCCP_PATH ) . 'templates/single-wb-community-event.php';
			if ( file_exists( $plugin_template ) ) {
				return $plugin_template;
			}
		}

		return $template;
	}

	public static function add_row_actions( $actions, $post ) {
		if ( self::CPT !== $post->post_type ) {
			return $actions;
		}

		if ( empty( $actions['view'] ) ) {
			$view_link = get_permalink( $post );
			if ( $view_link ) {
				$actions['wbccp_view'] = '<a href="' . esc_url( $view_link ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'View', 'wb-community-calendar-pro' ) . '</a>';
			}
		}

		$group_id = (int) get_post_meta( $post->ID, 'wbccp_group_id', true );
		$group_link = self::get_group_calendar_url( $group_id, $post->ID );
		if ( $group_link ) {
			$actions['wbccp_view_group'] = '<a href="' . esc_url( $group_link ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'View in Group', 'wb-community-calendar-pro' ) . '</a>';
		}

		return $actions;
	}

	public static function maybe_export_ical() {
		if ( empty( $_GET['wbccp_ical'] ) ) {
			return;
		}

		$event_id = isset( $_GET['event_id'] ) ? absint( $_GET['event_id'] ) : 0;
		$group_id = isset( $_GET['group_id'] ) ? absint( $_GET['group_id'] ) : 0;

		if ( $event_id ) {
			$event = get_post( $event_id );
			if ( ! $event || self::CPT !== $event->post_type ) {
				wp_die( esc_html__( 'Event not found.', 'wb-community-calendar-pro' ) );
			}
			$group_id = (int) get_post_meta( $event_id, 'wbccp_group_id', true );
			if ( ! self::can_view_group_calendar( $group_id ) ) {
				wp_die( esc_html__( 'Unauthorized.', 'wb-community-calendar-pro' ) );
			}
			self::output_ical( array( $event ), 'wbccp-event-' . $event_id . '.ics' );
		}

		if ( $group_id ) {
			if ( ! self::can_view_group_calendar( $group_id ) ) {
				wp_die( esc_html__( 'Unauthorized.', 'wb-community-calendar-pro' ) );
			}

			$query = new WP_Query(
				array(
					'post_type'      => self::CPT,
					'post_status'    => 'publish',
					'posts_per_page' => 200,
					'meta_query'     => array(
						array(
							'key'   => 'wbccp_group_id',
							'value' => (int) $group_id,
						),
					),
					'orderby'        => 'meta_value',
					'order'          => 'ASC',
					'meta_key'       => 'wbccp_start',
				)
			);

			self::output_ical( $query->posts, 'wbccp-group-' . $group_id . '.ics' );
		}

		wp_die( esc_html__( 'Missing calendar parameters.', 'wb-community-calendar-pro' ) );
	}

	public static function save_taxonomies_from_request( $post_id, $data ) {
		if ( ! taxonomy_exists( self::TAX_CATEGORY ) || ! taxonomy_exists( self::TAX_TAG ) ) {
			return;
		}

		$categories = array();
		if ( isset( $data['wbccp_categories'] ) ) {
			$categories = array_map( 'absint', (array) $data['wbccp_categories'] );
			$categories = array_filter( $categories );
		}
		wp_set_post_terms( $post_id, $categories, self::TAX_CATEGORY, false );

		$tags = array();
		if ( isset( $data['wbccp_tags'] ) ) {
			$raw_tags = sanitize_text_field( wp_unslash( $data['wbccp_tags'] ) );
			if ( $raw_tags ) {
				$tags = array_map( 'trim', explode( ',', $raw_tags ) );
				$tags = array_filter( $tags );
			}
		}
		wp_set_post_terms( $post_id, $tags, self::TAX_TAG, false );
	}

	public static function get_tax_query_from_request() {
		$tax_query = array();

		$category = isset( $_GET['wbccp_category'] ) ? absint( $_GET['wbccp_category'] ) : 0;
		if ( $category ) {
			$tax_query[] = array(
				'taxonomy' => self::TAX_CATEGORY,
				'field'    => 'term_id',
				'terms'    => array( $category ),
			);
		}

		$tag = isset( $_GET['wbccp_tag'] ) ? sanitize_text_field( wp_unslash( $_GET['wbccp_tag'] ) ) : '';
		if ( $tag ) {
			$tax_query[] = array(
				'taxonomy' => self::TAX_TAG,
				'field'    => 'slug',
				'terms'    => array( sanitize_title( $tag ) ),
			);
		}

		if ( count( $tax_query ) > 1 ) {
			$tax_query['relation'] = 'AND';
		}

		return $tax_query;
	}

	public static function can_view_group_calendar( $group_id ) {
		if ( ! $group_id ) {
			return true;
		}

		if ( ! function_exists( 'groups_get_group' ) ) {
			return true;
		}

		$group = groups_get_group( array( 'group_id' => $group_id ) );
		if ( empty( $group ) || empty( $group->id ) ) {
			return false;
		}

		if ( 'public' === $group->status ) {
			return true;
		}

		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		return is_user_logged_in() && function_exists( 'groups_is_user_member' ) && groups_is_user_member( get_current_user_id(), $group_id );
	}

	private static function output_ical( $events, $filename ) {
		if ( empty( $events ) ) {
			wp_die( esc_html__( 'No events available.', 'wb-community-calendar-pro' ) );
		}

		$lines = array(
			'BEGIN:VCALENDAR',
			'VERSION:2.0',
			'PRODID:-//WB Community Calendar Pro//EN',
			'CALSCALE:GREGORIAN',
			'METHOD:PUBLISH',
		);

		foreach ( $events as $event ) {
			$event_id = $event instanceof WP_Post ? $event->ID : (int) $event;
			$start_ts = (int) get_post_meta( $event_id, 'wbccp_start', true );
			if ( ! $start_ts ) {
				continue;
			}
			$end_ts = (int) get_post_meta( $event_id, 'wbccp_end', true );
			$location = get_post_meta( $event_id, 'wbccp_location', true );
			$link = get_post_meta( $event_id, 'wbccp_link', true );
			$rrule = get_post_meta( $event_id, 'wbccp_rrule', true );

			$lines[] = 'BEGIN:VEVENT';
			$lines[] = 'UID:' . self::escape_ical_text( $event_id . '@' . wp_parse_url( home_url(), PHP_URL_HOST ) );
			$lines[] = 'DTSTAMP:' . gmdate( 'Ymd\\THis\\Z' );
			$lines[] = 'DTSTART:' . gmdate( 'Ymd\\THis\\Z', $start_ts );
			if ( $end_ts ) {
				$lines[] = 'DTEND:' . gmdate( 'Ymd\\THis\\Z', $end_ts );
			}
			$lines[] = 'SUMMARY:' . self::escape_ical_text( get_the_title( $event_id ) );
			$lines[] = 'DESCRIPTION:' . self::escape_ical_text( wp_strip_all_tags( get_post_field( 'post_content', $event_id ) ) );
			if ( $location ) {
				$lines[] = 'LOCATION:' . self::escape_ical_text( $location );
			}
			if ( $link ) {
				$lines[] = 'URL:' . esc_url_raw( $link );
			}
			if ( $rrule ) {
				$lines[] = 'RRULE:' . self::escape_ical_text( $rrule );
			}
			$lines[] = 'END:VEVENT';
		}

		$lines[] = 'END:VCALENDAR';

		nocache_headers();
		header( 'Content-Type: text/calendar; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . sanitize_file_name( $filename ) );
		echo implode( "\r\n", $lines );
		exit;
	}

	private static function escape_ical_text( $text ) {
		$text = (string) $text;
		$text = str_replace( '\\', '\\\\', $text );
		$text = str_replace( ';', '\\;', $text );
		$text = str_replace( ',', '\\,', $text );
		$text = str_replace( "\r\n", '\\n', $text );
		$text = str_replace( "\n", '\\n', $text );
		$text = str_replace( "\r", '\\n', $text );
		return $text;
	}

	public static function save_recurrence_meta( $event_id, $data ) {
		$enabled = ! empty( $data['wbccp_recur_enabled'] ) ? 1 : 0;
		update_post_meta( $event_id, 'wbccp_recur_enabled', $enabled );

		$freq = isset( $data['wbccp_recur_freq'] ) ? sanitize_text_field( wp_unslash( $data['wbccp_recur_freq'] ) ) : '';
		$allowed_freq = array( 'DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY' );
		if ( ! in_array( $freq, $allowed_freq, true ) ) {
			$freq = '';
		}
		update_post_meta( $event_id, 'wbccp_recur_freq', $freq );

		$interval = isset( $data['wbccp_recur_interval'] ) ? absint( $data['wbccp_recur_interval'] ) : 1;
		if ( $interval < 1 ) {
			$interval = 1;
		}
		update_post_meta( $event_id, 'wbccp_recur_interval', $interval );

		$byday = array();
		if ( ! empty( $data['wbccp_recur_byday'] ) && is_array( $data['wbccp_recur_byday'] ) ) {
			$byday = array_map( 'sanitize_text_field', wp_unslash( $data['wbccp_recur_byday'] ) );
		}
		update_post_meta( $event_id, 'wbccp_recur_byday', $byday );

		$bymonthday = isset( $data['wbccp_recur_bymonthday'] ) ? absint( $data['wbccp_recur_bymonthday'] ) : 0;
		update_post_meta( $event_id, 'wbccp_recur_bymonthday', $bymonthday );

		$bysetpos = isset( $data['wbccp_recur_bysetpos'] ) ? (int) $data['wbccp_recur_bysetpos'] : 0;
		if ( ! in_array( $bysetpos, array( -1, 1, 2, 3, 4 ), true ) ) {
			$bysetpos = 0;
		}
		update_post_meta( $event_id, 'wbccp_recur_bysetpos', $bysetpos );

		$byweekday = isset( $data['wbccp_recur_byweekday'] ) ? sanitize_text_field( wp_unslash( $data['wbccp_recur_byweekday'] ) ) : '';
		update_post_meta( $event_id, 'wbccp_recur_byweekday', $byweekday );

		$count = isset( $data['wbccp_recur_count'] ) ? absint( $data['wbccp_recur_count'] ) : 0;
		update_post_meta( $event_id, 'wbccp_recur_count', $count );

		$until = isset( $data['wbccp_recur_until'] ) ? sanitize_text_field( wp_unslash( $data['wbccp_recur_until'] ) ) : '';
		if ( $until && ! preg_match( '/^\\d{4}-\\d{2}-\\d{2}$/', $until ) ) {
			$until = '';
		}
		update_post_meta( $event_id, 'wbccp_recur_until', $until );

		$rrule = $enabled ? self::build_rrule_from_data( array(
			'freq'        => $freq,
			'interval'    => $interval,
			'byday'       => $byday,
			'bymonthday'  => $bymonthday,
			'bysetpos'    => $bysetpos,
			'byweekday'   => $byweekday,
			'count'       => $count,
			'until'       => $until,
		) ) : '';

		if ( $rrule ) {
			update_post_meta( $event_id, 'wbccp_rrule', $rrule );
		} else {
			delete_post_meta( $event_id, 'wbccp_rrule' );
		}
	}

	private static function build_rrule_from_data( $data ) {
		if ( empty( $data['freq'] ) ) {
			return '';
		}

		$parts = array( 'FREQ=' . $data['freq'] );

		if ( ! empty( $data['interval'] ) && $data['interval'] > 1 ) {
			$parts[] = 'INTERVAL=' . (int) $data['interval'];
		}

		if ( ! empty( $data['bysetpos'] ) && ! empty( $data['byweekday'] ) ) {
			$parts[] = 'BYDAY=' . (int) $data['bysetpos'] . sanitize_text_field( $data['byweekday'] );
		} elseif ( ! empty( $data['byday'] ) ) {
			$parts[] = 'BYDAY=' . implode( ',', array_map( 'sanitize_text_field', (array) $data['byday'] ) );
		}

		if ( ! empty( $data['bymonthday'] ) ) {
			$parts[] = 'BYMONTHDAY=' . (int) $data['bymonthday'];
		}

		if ( ! empty( $data['count'] ) ) {
			$parts[] = 'COUNT=' . (int) $data['count'];
		}

		if ( ! empty( $data['until'] ) ) {
			$tz = wp_timezone();
			try {
				$until_dt = new DateTimeImmutable( $data['until'] . ' 23:59:59', $tz );
				$parts[] = 'UNTIL=' . $until_dt->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Ymd\\THis\\Z' );
			} catch ( Exception $e ) {
				// Ignore invalid date.
			}
		}

		return implode( ';', array_unique( $parts ) );
	}

	private static function parse_rrule( $rrule ) {
		$rule = array();
		if ( ! $rrule ) {
			return $rule;
		}

		$parts = explode( ';', $rrule );
		foreach ( $parts as $part ) {
			$pair = explode( '=', $part, 2 );
			if ( count( $pair ) !== 2 ) {
				continue;
			}
			$rule[ strtoupper( $pair[0] ) ] = strtoupper( $pair[1] );
		}

		if ( ! empty( $rule['BYDAY'] ) ) {
			$rule['BYDAY'] = array_map( 'trim', explode( ',', $rule['BYDAY'] ) );
		}

		if ( ! empty( $rule['BYMONTHDAY'] ) ) {
			$rule['BYMONTHDAY'] = array_map( 'intval', explode( ',', $rule['BYMONTHDAY'] ) );
		}

		if ( ! empty( $rule['BYMONTH'] ) ) {
			$rule['BYMONTH'] = array_map( 'intval', explode( ',', $rule['BYMONTH'] ) );
		}

		if ( ! empty( $rule['COUNT'] ) ) {
			$rule['COUNT'] = (int) $rule['COUNT'];
		}

		if ( ! empty( $rule['INTERVAL'] ) ) {
			$rule['INTERVAL'] = max( 1, (int) $rule['INTERVAL'] );
		} else {
			$rule['INTERVAL'] = 1;
		}

		if ( ! empty( $rule['UNTIL'] ) ) {
			$until = $rule['UNTIL'];
			try {
				if ( false !== strpos( $until, 'T' ) ) {
					$rule['UNTIL_TS'] = DateTimeImmutable::createFromFormat( 'Ymd\\THis\\Z', $until, new DateTimeZone( 'UTC' ) )->getTimestamp();
				} else {
					$rule['UNTIL_TS'] = DateTimeImmutable::createFromFormat( 'Ymd', $until, new DateTimeZone( 'UTC' ) )->getTimestamp();
				}
			} catch ( Exception $e ) {
				$rule['UNTIL_TS'] = 0;
			}
		}

		return $rule;
	}

	public static function get_recurrence_summary( $event_id ) {
		$rrule = get_post_meta( $event_id, 'wbccp_rrule', true );
		if ( ! $rrule ) {
			return '';
		}

		$rule = self::parse_rrule( $rrule );
		if ( empty( $rule['FREQ'] ) ) {
			return '';
		}

		$freq_map = array(
			'DAILY'   => __( 'Daily', 'wb-community-calendar-pro' ),
			'WEEKLY'  => __( 'Weekly', 'wb-community-calendar-pro' ),
			'MONTHLY' => __( 'Monthly', 'wb-community-calendar-pro' ),
			'YEARLY'  => __( 'Yearly', 'wb-community-calendar-pro' ),
		);
		$label = isset( $freq_map[ $rule['FREQ'] ] ) ? $freq_map[ $rule['FREQ'] ] : $rule['FREQ'];

		if ( ! empty( $rule['INTERVAL'] ) && $rule['INTERVAL'] > 1 ) {
			$label = sprintf( __( 'Every %d %s', 'wb-community-calendar-pro' ), (int) $rule['INTERVAL'], strtolower( $label ) );
		}

		$details = array();

		if ( ! empty( $rule['BYDAY'] ) ) {
			$weekday_map = array( 'MO' => __( 'Mon', 'wb-community-calendar-pro' ), 'TU' => __( 'Tue', 'wb-community-calendar-pro' ), 'WE' => __( 'Wed', 'wb-community-calendar-pro' ), 'TH' => __( 'Thu', 'wb-community-calendar-pro' ), 'FR' => __( 'Fri', 'wb-community-calendar-pro' ), 'SA' => __( 'Sat', 'wb-community-calendar-pro' ), 'SU' => __( 'Sun', 'wb-community-calendar-pro' ) );
			$days = array();
			foreach ( (array) $rule['BYDAY'] as $day_code ) {
				$pos = '';
				if ( preg_match( '/^([+-]?\\d)([A-Z]{2})$/', $day_code, $matches ) ) {
					$pos_map = array(
						'1'  => __( '1st', 'wb-community-calendar-pro' ),
						'2'  => __( '2nd', 'wb-community-calendar-pro' ),
						'3'  => __( '3rd', 'wb-community-calendar-pro' ),
						'4'  => __( '4th', 'wb-community-calendar-pro' ),
						'-1' => __( 'Last', 'wb-community-calendar-pro' ),
					);
					$pos = isset( $pos_map[ $matches[1] ] ) ? $pos_map[ $matches[1] ] . ' ' : '';
					$day_code = $matches[2];
				}
				$days[] = $pos . ( $weekday_map[ $day_code ] ?? $day_code );
			}
			if ( $days ) {
				$details[] = implode( ', ', $days );
			}
		}

		if ( ! empty( $rule['BYMONTHDAY'] ) ) {
			$details[] = sprintf( __( 'Day %s', 'wb-community-calendar-pro' ), implode( ', ', array_map( 'intval', (array) $rule['BYMONTHDAY'] ) ) );
		}

		if ( ! empty( $rule['COUNT'] ) ) {
			$details[] = sprintf( __( 'For %d occurrences', 'wb-community-calendar-pro' ), (int) $rule['COUNT'] );
		}

		if ( ! empty( $rule['UNTIL'] ) ) {
			$details[] = sprintf( __( 'Until %s', 'wb-community-calendar-pro' ), $rule['UNTIL'] );
		}

		return trim( $label . ( $details ? ' (' . implode( '; ', $details ) . ')' : '' ) );
	}

	public static function get_occurrences_for_range( $event_id, $range_start, $range_end ) {
		$start_ts = (int) get_post_meta( $event_id, 'wbccp_start', true );
		if ( ! $start_ts ) {
			return array();
		}

		$rrule = get_post_meta( $event_id, 'wbccp_rrule', true );
		if ( ! $rrule ) {
			return ( $start_ts >= $range_start && $start_ts <= $range_end ) ? array( $start_ts ) : array();
		}

		$rule = self::parse_rrule( $rrule );
		if ( empty( $rule['FREQ'] ) ) {
			return array();
		}

		$occurrences = array();
		$limit = 500;
		$count = 0;
		$interval = ! empty( $rule['INTERVAL'] ) ? (int) $rule['INTERVAL'] : 1;
		$until_ts = ! empty( $rule['UNTIL_TS'] ) ? (int) $rule['UNTIL_TS'] : 0;
		$max_count = ! empty( $rule['COUNT'] ) ? (int) $rule['COUNT'] : 0;

		$start_dt = ( new DateTimeImmutable( '@' . $start_ts ) )->setTimezone( new DateTimeZone( 'UTC' ) );
		$range_end_dt = ( new DateTimeImmutable( '@' . $range_end ) )->setTimezone( new DateTimeZone( 'UTC' ) );

		$add_occurrence = function( DateTimeImmutable $dt ) use ( &$occurrences, $range_start, $range_end, $until_ts, $max_count, &$count, $limit ) {
			$ts = $dt->getTimestamp();
			if ( $until_ts && $ts > $until_ts ) {
				return false;
			}
			if ( $ts >= $range_start && $ts <= $range_end ) {
				$occurrences[] = $ts;
			}
			$count++;
			if ( $max_count && $count >= $max_count ) {
				return false;
			}
			return $count < $limit;
		};

		switch ( $rule['FREQ'] ) {
			case 'DAILY':
				$current = $start_dt;
				while ( $current <= $range_end_dt ) {
					if ( ! $add_occurrence( $current ) ) {
						break;
					}
					$current = $current->modify( '+' . $interval . ' days' );
				}
				break;
			case 'WEEKLY':
				$byday = ! empty( $rule['BYDAY'] ) ? $rule['BYDAY'] : array();
				if ( empty( $byday ) ) {
					$current = $start_dt;
					while ( $current <= $range_end_dt ) {
						if ( ! $add_occurrence( $current ) ) {
							break;
						}
						$current = $current->modify( '+' . $interval . ' weeks' );
					}
					break;
				}

				$week_start = $start_dt->modify( 'monday this week' );
				$weekday_map = array( 'MO' => 1, 'TU' => 2, 'WE' => 3, 'TH' => 4, 'FR' => 5, 'SA' => 6, 'SU' => 7 );
				while ( $week_start <= $range_end_dt ) {
					foreach ( $byday as $day_code ) {
						if ( ! isset( $weekday_map[ $day_code ] ) ) {
							continue;
						}
						$occurrence = $week_start->modify( '+' . ( $weekday_map[ $day_code ] - 1 ) . ' days' );
						if ( $occurrence < $start_dt ) {
							continue;
						}
						if ( ! $add_occurrence( $occurrence ) ) {
							break 2;
						}
					}
					$week_start = $week_start->modify( '+' . $interval . ' weeks' );
				}
				break;
			case 'MONTHLY':
				$current = $start_dt;
				while ( $current <= $range_end_dt ) {
					$year = (int) $current->format( 'Y' );
					$month = (int) $current->format( 'n' );
					$time = $current->format( 'H:i:s' );
					$days_added = false;

					if ( ! empty( $rule['BYMONTHDAY'] ) ) {
						foreach ( (array) $rule['BYMONTHDAY'] as $day ) {
							if ( $day < 1 || $day > 31 ) {
								continue;
							}
							$occ = self::build_month_day( $year, $month, $day, $time );
							if ( $occ && ! $add_occurrence( $occ ) ) {
								break 3;
							}
							$days_added = true;
						}
					}

					if ( ! empty( $rule['BYDAY'] ) ) {
						foreach ( (array) $rule['BYDAY'] as $day_code ) {
							$pos = 0;
							if ( preg_match( '/^([+-]?\\d)([A-Z]{2})$/', $day_code, $matches ) ) {
								$pos = (int) $matches[1];
								$day_code = $matches[2];
							}
							if ( $pos ) {
								$occ = self::build_nth_weekday( $year, $month, $day_code, $pos, $time );
								if ( $occ && ! $add_occurrence( $occ ) ) {
									break 3;
								}
							} else {
								$occurrences_in_month = self::build_weekday_list( $year, $month, $day_code, $time );
								foreach ( $occurrences_in_month as $occ ) {
									if ( ! $add_occurrence( $occ ) ) {
										break 4;
									}
								}
							}
							$days_added = true;
						}
					}

					if ( ! $days_added ) {
						$occ = self::build_month_day( $year, $month, (int) $start_dt->format( 'j' ), $time );
						if ( $occ && ! $add_occurrence( $occ ) ) {
							break;
						}
					}

					$current = $current->modify( 'first day of +' . $interval . ' month' );
				}
				break;
			case 'YEARLY':
				$current = $start_dt;
				$months = ! empty( $rule['BYMONTH'] ) ? $rule['BYMONTH'] : array( (int) $start_dt->format( 'n' ) );
				while ( $current <= $range_end_dt ) {
					$year = (int) $current->format( 'Y' );
					$time = $current->format( 'H:i:s' );
					foreach ( $months as $month ) {
						if ( $month < 1 || $month > 12 ) {
							continue;
						}
						if ( ! empty( $rule['BYMONTHDAY'] ) ) {
							foreach ( (array) $rule['BYMONTHDAY'] as $day ) {
								$occ = self::build_month_day( $year, $month, $day, $time );
								if ( $occ && ! $add_occurrence( $occ ) ) {
									break 3;
								}
							}
							continue;
						}

						if ( ! empty( $rule['BYDAY'] ) ) {
							foreach ( (array) $rule['BYDAY'] as $day_code ) {
								$pos = 0;
								if ( preg_match( '/^([+-]?\\d)([A-Z]{2})$/', $day_code, $matches ) ) {
									$pos = (int) $matches[1];
									$day_code = $matches[2];
								}
								if ( $pos ) {
									$occ = self::build_nth_weekday( $year, $month, $day_code, $pos, $time );
									if ( $occ && ! $add_occurrence( $occ ) ) {
										break 4;
									}
								} else {
									$occurrences_in_month = self::build_weekday_list( $year, $month, $day_code, $time );
									foreach ( $occurrences_in_month as $occ ) {
										if ( ! $add_occurrence( $occ ) ) {
											break 5;
										}
									}
								}
							}
							continue;
						}
					}

					$current = $current->modify( '+' . $interval . ' years' );
				}
				break;
		}

		sort( $occurrences );
		return array_values( array_unique( $occurrences ) );
	}

	private static function build_month_day( $year, $month, $day, $time ) {
		if ( $day < 1 || $day > 31 ) {
			return null;
		}
		$date = sprintf( '%04d-%02d-%02d %s', $year, $month, $day, $time );
		$dt = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $date, new DateTimeZone( 'UTC' ) );
		if ( ! $dt || (int) $dt->format( 'n' ) !== (int) $month ) {
			return null;
		}
		return $dt;
	}

	private static function build_nth_weekday( $year, $month, $weekday_code, $pos, $time ) {
		$weekday_map = array( 'MO' => 1, 'TU' => 2, 'WE' => 3, 'TH' => 4, 'FR' => 5, 'SA' => 6, 'SU' => 7 );
		if ( ! isset( $weekday_map[ $weekday_code ] ) ) {
			return null;
		}
		$weekday = $weekday_map[ $weekday_code ];
		$first_day = new DateTimeImmutable( sprintf( '%04d-%02d-01 %s', $year, $month, $time ), new DateTimeZone( 'UTC' ) );
		if ( $pos > 0 ) {
			$first_weekday = (int) $first_day->format( 'N' );
			$offset = ( $weekday - $first_weekday + 7 ) % 7;
			$day = 1 + $offset + ( ( $pos - 1 ) * 7 );
			return self::build_month_day( $year, $month, $day, $time );
		}

		$last_day = $first_day->modify( 'last day of this month' );
		$last_weekday = (int) $last_day->format( 'N' );
		$offset = ( $last_weekday - $weekday + 7 ) % 7;
		$day = (int) $last_day->format( 'j' ) - $offset + ( ( $pos + 1 ) * 7 );
		return self::build_month_day( $year, $month, $day, $time );
	}

	private static function build_weekday_list( $year, $month, $weekday_code, $time ) {
		$weekday_map = array( 'MO' => 1, 'TU' => 2, 'WE' => 3, 'TH' => 4, 'FR' => 5, 'SA' => 6, 'SU' => 7 );
		if ( ! isset( $weekday_map[ $weekday_code ] ) ) {
			return array();
		}
		$weekday = $weekday_map[ $weekday_code ];
		$first_day = new DateTimeImmutable( sprintf( '%04d-%02d-01 %s', $year, $month, $time ), new DateTimeZone( 'UTC' ) );
		$days_in_month = (int) $first_day->format( 't' );
		$occurrences = array();
		for ( $day = 1; $day <= $days_in_month; $day++ ) {
			$dt = self::build_month_day( $year, $month, $day, $time );
			if ( ! $dt ) {
				continue;
			}
			if ( (int) $dt->format( 'N' ) === $weekday ) {
				$occurrences[] = $dt;
			}
		}
		return $occurrences;
	}

	public static function get_group_occurrences( $group_id, $range_start, $range_end, $scope = 'all', $tax_query = array() ) {
		$meta_query = array();
		if ( $group_id ) {
			$meta_query[] = array(
				'key'   => 'wbccp_group_id',
				'value' => (int) $group_id,
			);
		} elseif ( 'sitewide' === $scope ) {
			$meta_query[] = array(
				'key'   => 'wbccp_group_id',
				'value' => 0,
				'type'  => 'NUMERIC',
			);
		}

		$query = new WP_Query(
			array(
				'post_type'      => self::CPT,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'meta_query'     => $meta_query,
				'tax_query'      => $tax_query,
			)
		);

		$items = array();
		foreach ( $query->posts as $event ) {
			$event_id = $event->ID;
			$duration = (int) get_post_meta( $event_id, 'wbccp_end', true ) - (int) get_post_meta( $event_id, 'wbccp_start', true );
			if ( $duration < 0 ) {
				$duration = 0;
			}
			$occurrences = self::get_occurrences_for_range( $event_id, $range_start, $range_end );
			foreach ( $occurrences as $occurrence_ts ) {
				$items[] = array(
					'event_id' => $event_id,
					'start'    => $occurrence_ts,
					'end'      => $duration ? $occurrence_ts + $duration : 0,
				);
			}
		}

		usort(
			$items,
			function( $a, $b ) {
				return $a['start'] <=> $b['start'];
			}
		);

		return $items;
	}

	public static function format_occurrence_datetime( $event_id, $timestamp, $format = '' ) {
		if ( ! $timestamp ) {
			return '';
		}

		$timezone = get_post_meta( $event_id, 'wbccp_timezone', true );
		if ( ! $timezone && class_exists( 'WBCCP_Settings' ) ) {
			$settings = WBCCP_Settings::get_settings();
			$timezone = $settings['default_timezone'];
		}

		$format = $format ? $format : ( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );

		try {
			$tz = new DateTimeZone( $timezone ? $timezone : 'UTC' );
			return wp_date( $format, $timestamp, $tz );
		} catch ( Exception $e ) {
			return wp_date( $format, $timestamp );
		}
	}

	public static function get_date_parts_for_timestamp( $timestamp, $timezone = '' ) {
		if ( ! $timestamp ) {
			return array();
		}

		if ( ! $timezone && class_exists( 'WBCCP_Settings' ) ) {
			$settings = WBCCP_Settings::get_settings();
			$timezone = $settings['default_timezone'];
		}

		try {
			$tz = new DateTimeZone( $timezone ? $timezone : 'UTC' );
			$dt = ( new DateTimeImmutable( '@' . $timestamp ) )->setTimezone( $tz );
			return array(
				'year'  => (int) $dt->format( 'Y' ),
				'month' => (int) $dt->format( 'n' ),
				'day'   => (int) $dt->format( 'j' ),
			);
		} catch ( Exception $e ) {
			return array();
		}
	}

	public static function get_group_calendar_url( $group_id, $event_id = 0 ) {
		if ( ! $group_id || ! function_exists( 'groups_get_group' ) || ! function_exists( 'bp_get_group_permalink' ) ) {
			return '';
		}

		$group = groups_get_group( array( 'group_id' => $group_id ) );
		if ( empty( $group ) || empty( $group->id ) ) {
			return '';
		}

		$url = trailingslashit( bp_get_group_permalink( $group ) ) . 'community-calendar/';
		if ( $event_id ) {
			$url = add_query_arg( 'wbccp_event', (int) $event_id, $url );
		}

		return $url;
	}

	public static function render_admin_notices() {
		if ( empty( $_GET['post_type'] ) && empty( $_GET['post'] ) ) {
			return;
		}

		$errors = isset( $_GET['wbccp_error'] ) ? (array) $_GET['wbccp_error'] : array();
		if ( empty( $errors ) ) {
			return;
		}

		$messages = array();
		if ( in_array( 'missing_group', $errors, true ) ) {
			$settings = class_exists( 'WBCCP_Settings' ) ? WBCCP_Settings::get_settings() : array( 'allow_sitewide_events' => 0 );
			if ( ! empty( $settings['allow_sitewide_events'] ) ) {
				$messages[] = __( 'Please select a BuddyPress group or choose Sitewide.', 'wb-community-calendar-pro' );
			} else {
				$messages[] = __( 'Please select a BuddyPress group for this event.', 'wb-community-calendar-pro' );
			}
		}
		if ( in_array( 'missing_start', $errors, true ) ) {
			$messages[] = __( 'Please set an event start date/time.', 'wb-community-calendar-pro' );
		}

		if ( empty( $messages ) ) {
			return;
		}

		echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html( implode( ' ', $messages ) ) . '</p></div>';
	}

	public static function parse_datetime_to_utc( $value, $timezone ) {
		if ( ! $value ) {
			return 0;
		}

		try {
			$tz = new DateTimeZone( $timezone ? $timezone : 'UTC' );
			$dt = DateTimeImmutable::createFromFormat( 'Y-m-d\\TH:i', $value, $tz );
			if ( ! $dt ) {
				return 0;
			}
			return $dt->getTimestamp();
		} catch ( Exception $e ) {
			return 0;
		}
	}

	public static function format_event_datetime( $event_id ) {
		$start    = (int) get_post_meta( $event_id, 'wbccp_start', true );
		$timezone = get_post_meta( $event_id, 'wbccp_timezone', true );

		if ( ! $start ) {
			return '';
		}

		if ( ! $timezone && class_exists( 'WBCCP_Settings' ) ) {
			$settings = WBCCP_Settings::get_settings();
			$timezone = $settings['default_timezone'];
		}

		try {
			$tz = new DateTimeZone( $timezone ? $timezone : 'UTC' );
			return wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $start, $tz );
		} catch ( Exception $e ) {
			return wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $start );
		}
	}

	public static function format_event_time( $event_id ) {
		$start    = (int) get_post_meta( $event_id, 'wbccp_start', true );
		$timezone = get_post_meta( $event_id, 'wbccp_timezone', true );

		if ( ! $start ) {
			return '';
		}

		if ( ! $timezone && class_exists( 'WBCCP_Settings' ) ) {
			$settings = WBCCP_Settings::get_settings();
			$timezone = $settings['default_timezone'];
		}

		try {
			$tz = new DateTimeZone( $timezone ? $timezone : 'UTC' );
			return wp_date( get_option( 'time_format' ), $start, $tz );
		} catch ( Exception $e ) {
			return wp_date( get_option( 'time_format' ), $start );
		}
	}

	public static function get_event_date_parts( $event_id ) {
		$start = (int) get_post_meta( $event_id, 'wbccp_start', true );
		if ( ! $start ) {
			return array();
		}

		$timezone = get_post_meta( $event_id, 'wbccp_timezone', true );
		if ( ! $timezone && class_exists( 'WBCCP_Settings' ) ) {
			$settings = WBCCP_Settings::get_settings();
			$timezone = $settings['default_timezone'];
		}

		try {
			$tz = new DateTimeZone( $timezone ? $timezone : 'UTC' );
			$dt = ( new DateTimeImmutable( '@' . $start ) )->setTimezone( $tz );
			return array(
				'year'  => (int) $dt->format( 'Y' ),
				'month' => (int) $dt->format( 'n' ),
				'day'   => (int) $dt->format( 'j' ),
			);
		} catch ( Exception $e ) {
			return array();
		}
	}

	public static function get_group_events( $group_id, $args = array() ) {
		$defaults = array(
			'post_type'      => self::CPT,
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			'meta_query'     => array(
				array(
					'key'   => 'wbccp_group_id',
					'value' => (int) $group_id,
				),
			),
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'meta_key'       => 'wbccp_start',
		);

		$query_args = wp_parse_args( $args, $defaults );

		return new WP_Query( $query_args );
	}
}
