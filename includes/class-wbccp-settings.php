<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WBCCP_Settings {
	const OPTION_GROUP = 'wbccp_settings_group';
	const OPTION_NAME  = 'wbccp_settings';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_demo_actions_from_get' ) );
		add_action( 'admin_post_wbccp_generate_demo', array( __CLASS__, 'handle_generate_demo' ) );
		add_action( 'admin_post_wbccp_delete_demo', array( __CLASS__, 'handle_delete_demo' ) );
	}

	public static function add_menu() {
		add_options_page(
			__( 'WB Community Calendar', 'wb-community-calendar-pro' ),
			__( 'WB Community Calendar', 'wb-community-calendar-pro' ),
			'manage_options',
			'wbccp-settings',
			array( __CLASS__, 'render_page' )
		);
	}

	public static function register_settings() {
		register_setting(
			self::OPTION_GROUP,
			self::OPTION_NAME,
			array( __CLASS__, 'sanitize_settings' )
		);

		add_settings_section(
			'wbccp_general',
			__( 'General Settings', 'wb-community-calendar-pro' ),
			'__return_false',
			'wbccp-settings'
		);

		add_settings_field(
			'allow_member_events',
			__( 'Allow Members to Create Events', 'wb-community-calendar-pro' ),
			array( __CLASS__, 'field_allow_members' ),
			'wbccp-settings',
			'wbccp_general'
		);

		add_settings_field(
			'allow_sitewide_events',
			__( 'Allow Sitewide Events', 'wb-community-calendar-pro' ),
			array( __CLASS__, 'field_allow_sitewide' ),
			'wbccp-settings',
			'wbccp_general'
		);

		add_settings_field(
			'moderation_required',
			__( 'Require Approval for Member Events', 'wb-community-calendar-pro' ),
			array( __CLASS__, 'field_moderation_required' ),
			'wbccp-settings',
			'wbccp_general'
		);

		add_settings_field(
			'default_timezone',
			__( 'Default Timezone', 'wb-community-calendar-pro' ),
			array( __CLASS__, 'field_timezone' ),
			'wbccp-settings',
			'wbccp_general'
		);

		add_settings_field(
			'show_viewer_timezone',
			__( 'Show Viewer Local Time', 'wb-community-calendar-pro' ),
			array( __CLASS__, 'field_show_viewer_timezone' ),
			'wbccp-settings',
			'wbccp_general'
		);

		add_settings_section(
			'wbccp_demo',
			__( 'Demo Data', 'wb-community-calendar-pro' ),
			'__return_false',
			'wbccp-settings'
		);

		add_settings_field(
			'wbccp_demo_actions',
			__( 'Demo Content', 'wb-community-calendar-pro' ),
			array( __CLASS__, 'field_demo_actions' ),
			'wbccp-settings',
			'wbccp_demo'
		);

		add_settings_section(
			'wbccp_notifications',
			__( 'Notifications', 'wb-community-calendar-pro' ),
			'__return_false',
			'wbccp-settings'
		);

		add_settings_field(
			'notify_create',
			__( 'Notify on Event Creation', 'wb-community-calendar-pro' ),
			array( __CLASS__, 'field_notify_create' ),
			'wbccp-settings',
			'wbccp_notifications'
		);

		add_settings_field(
			'notify_update',
			__( 'Notify on Event Update', 'wb-community-calendar-pro' ),
			array( __CLASS__, 'field_notify_update' ),
			'wbccp-settings',
			'wbccp_notifications'
		);

		add_settings_field(
			'notify_cancel',
			__( 'Notify on Event Cancel', 'wb-community-calendar-pro' ),
			array( __CLASS__, 'field_notify_cancel' ),
			'wbccp-settings',
			'wbccp_notifications'
		);

		add_settings_field(
			'notify_rsvp',
			__( 'Notify on RSVP', 'wb-community-calendar-pro' ),
			array( __CLASS__, 'field_notify_rsvp' ),
			'wbccp-settings',
			'wbccp_notifications'
		);

		add_settings_field(
			'notify_rsvp_recipients',
			__( 'RSVP Notification Recipients', 'wb-community-calendar-pro' ),
			array( __CLASS__, 'field_notify_rsvp_recipients' ),
			'wbccp-settings',
			'wbccp_notifications'
		);

		add_settings_field(
			'notify_reminder_day',
			__( 'Send 24-hour Reminder', 'wb-community-calendar-pro' ),
			array( __CLASS__, 'field_notify_reminder_day' ),
			'wbccp-settings',
			'wbccp_notifications'
		);

		add_settings_field(
			'notify_reminder_hour',
			__( 'Send 1-hour Reminder', 'wb-community-calendar-pro' ),
			array( __CLASS__, 'field_notify_reminder_hour' ),
			'wbccp-settings',
			'wbccp_notifications'
		);

		add_settings_field(
			'reminder_include_maybe',
			__( 'Include Maybe in Reminders', 'wb-community-calendar-pro' ),
			array( __CLASS__, 'field_reminder_include_maybe' ),
			'wbccp-settings',
			'wbccp_notifications'
		);
	}

	public static function sanitize_settings( $input ) {
		$output = array();
		$output['allow_member_events'] = empty( $input['allow_member_events'] ) ? 0 : 1;
		$output['moderation_required'] = empty( $input['moderation_required'] ) ? 0 : 1;
		$output['allow_sitewide_events'] = empty( $input['allow_sitewide_events'] ) ? 0 : 1;

		$tz = isset( $input['default_timezone'] ) ? sanitize_text_field( $input['default_timezone'] ) : '';
		if ( $tz && ! in_array( $tz, timezone_identifiers_list(), true ) ) {
			$tz = '';
		}
		$output['default_timezone'] = $tz;
		$output['show_viewer_timezone'] = empty( $input['show_viewer_timezone'] ) ? 0 : 1;
		$output['notify_create'] = empty( $input['notify_create'] ) ? 0 : 1;
		$output['notify_update'] = empty( $input['notify_update'] ) ? 0 : 1;
		$output['notify_cancel'] = empty( $input['notify_cancel'] ) ? 0 : 1;
		$output['notify_rsvp'] = empty( $input['notify_rsvp'] ) ? 0 : 1;
		$allowed_rsvp_recipients = array( 'admins_mods', 'event_author', 'group_members' );
		$rsvp_recipients = array();
		if ( ! empty( $input['notify_rsvp_recipients'] ) && is_array( $input['notify_rsvp_recipients'] ) ) {
			$rsvp_recipients = array_intersect(
				$allowed_rsvp_recipients,
				array_map( 'sanitize_text_field', $input['notify_rsvp_recipients'] )
			);
		}
		$output['notify_rsvp_recipients'] = array_values( $rsvp_recipients );
		$output['notify_reminder_day'] = empty( $input['notify_reminder_day'] ) ? 0 : 1;
		$output['notify_reminder_hour'] = empty( $input['notify_reminder_hour'] ) ? 0 : 1;
		$output['reminder_include_maybe'] = empty( $input['reminder_include_maybe'] ) ? 0 : 1;

		return $output;
	}

	public static function get_settings() {
		$defaults = array(
			'allow_member_events' => 1,
			'allow_sitewide_events' => 0,
			'default_timezone'    => wp_timezone_string(),
			'show_viewer_timezone' => 1,
			'moderation_required' => 0,
			'notify_create'       => 1,
			'notify_update'       => 1,
			'notify_cancel'       => 1,
			'notify_rsvp'         => 0,
			'notify_rsvp_recipients' => array( 'admins_mods' ),
			'notify_reminder_day' => 1,
			'notify_reminder_hour' => 1,
			'reminder_include_maybe' => 1,
		);

		$settings = get_option( self::OPTION_NAME, array() );

		return wp_parse_args( $settings, $defaults );
	}

	public static function field_allow_members() {
		$settings = self::get_settings();
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[allow_member_events]" value="1" <?php checked( 1, $settings['allow_member_events'] ); ?> />
			<?php esc_html_e( 'Members (not just admins/mods) can create events in groups.', 'wb-community-calendar-pro' ); ?>
		</label>
		<?php
	}

	public static function field_allow_sitewide() {
		$settings = self::get_settings();
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[allow_sitewide_events]" value="1" <?php checked( 1, $settings['allow_sitewide_events'] ); ?> />
			<?php esc_html_e( 'Allow events that are not tied to a BuddyPress group (sitewide).', 'wb-community-calendar-pro' ); ?>
		</label>
		<?php
	}

	public static function field_moderation_required() {
		$settings = self::get_settings();
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[moderation_required]" value="1" <?php checked( 1, $settings['moderation_required'] ); ?> />
			<?php esc_html_e( 'Events created by members require approval before publishing.', 'wb-community-calendar-pro' ); ?>
		</label>
		<?php
	}

	public static function field_timezone() {
		$settings = self::get_settings();
		?>
		<input type="text" class="regular-text" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[default_timezone]" value="<?php echo esc_attr( $settings['default_timezone'] ); ?>" />
		<p class="description">
			<?php esc_html_e( 'Use a valid PHP timezone string, e.g. Europe/Berlin.', 'wb-community-calendar-pro' ); ?>
		</p>
		<?php
	}

	public static function field_show_viewer_timezone() {
		$settings = self::get_settings();
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[show_viewer_timezone]" value="1" <?php checked( 1, $settings['show_viewer_timezone'] ); ?> />
			<?php esc_html_e( 'Show the viewer’s local time on event pages and lists.', 'wb-community-calendar-pro' ); ?>
		</label>
		<?php
	}

	public static function field_notify_create() {
		$settings = self::get_settings();
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[notify_create]" value="1" <?php checked( 1, $settings['notify_create'] ); ?> />
			<?php esc_html_e( 'Email group members when a new event is created.', 'wb-community-calendar-pro' ); ?>
		</label>
		<?php
	}

	public static function field_notify_update() {
		$settings = self::get_settings();
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[notify_update]" value="1" <?php checked( 1, $settings['notify_update'] ); ?> />
			<?php esc_html_e( 'Email group members when an event is updated.', 'wb-community-calendar-pro' ); ?>
		</label>
		<?php
	}

	public static function field_notify_cancel() {
		$settings = self::get_settings();
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[notify_cancel]" value="1" <?php checked( 1, $settings['notify_cancel'] ); ?> />
			<?php esc_html_e( 'Email group members when an event is canceled.', 'wb-community-calendar-pro' ); ?>
		</label>
		<?php
	}

	public static function field_notify_rsvp() {
		$settings = self::get_settings();
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[notify_rsvp]" value="1" <?php checked( 1, $settings['notify_rsvp'] ); ?> />
			<?php esc_html_e( 'Email group members when someone RSVPs.', 'wb-community-calendar-pro' ); ?>
		</label>
		<?php
	}

	public static function field_notify_rsvp_recipients() {
		$settings = self::get_settings();
		$selected = ! empty( $settings['notify_rsvp_recipients'] ) ? (array) $settings['notify_rsvp_recipients'] : array();
		$options = array(
			'admins_mods'  => __( 'Group admins and moderators', 'wb-community-calendar-pro' ),
			'event_author' => __( 'Event author', 'wb-community-calendar-pro' ),
			'group_members' => __( 'All group members', 'wb-community-calendar-pro' ),
		);
		foreach ( $options as $value => $label ) :
			?>
			<label style="display:block;margin:4px 0;">
				<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[notify_rsvp_recipients][]" value="<?php echo esc_attr( $value ); ?>" <?php checked( in_array( $value, $selected, true ) ); ?> />
				<?php echo esc_html( $label ); ?>
			</label>
		<?php endforeach; ?>
		<p class="description">
			<?php esc_html_e( 'Choose who receives RSVP notification emails. If none are selected, no RSVP emails will be sent.', 'wb-community-calendar-pro' ); ?>
		</p>
		<?php
	}

	public static function field_notify_reminder_day() {
		$settings = self::get_settings();
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[notify_reminder_day]" value="1" <?php checked( 1, $settings['notify_reminder_day'] ); ?> />
			<?php esc_html_e( 'Email attendees 24 hours before the event.', 'wb-community-calendar-pro' ); ?>
		</label>
		<?php
	}

	public static function field_notify_reminder_hour() {
		$settings = self::get_settings();
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[notify_reminder_hour]" value="1" <?php checked( 1, $settings['notify_reminder_hour'] ); ?> />
			<?php esc_html_e( 'Email attendees 1 hour before the event.', 'wb-community-calendar-pro' ); ?>
		</label>
		<?php
	}

	public static function field_reminder_include_maybe() {
		$settings = self::get_settings();
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[reminder_include_maybe]" value="1" <?php checked( 1, $settings['reminder_include_maybe'] ); ?> />
			<?php esc_html_e( 'Send reminders to members who RSVP’d “Maybe”.', 'wb-community-calendar-pro' ); ?>
		</label>
		<?php
	}

	public static function render_page() {
		$demo = get_option( 'wbccp_demo_data', array() );
		$has_demo = ! empty( $demo['events'] ) || ! empty( $demo['groups'] ) || ! empty( $demo['pages'] ) || ! empty( $demo['attachments'] ) || ! empty( $demo['users'] ) || ! empty( $demo['menu_items'] );
		$status = isset( $_GET['wbccp_demo'] ) ? sanitize_text_field( wp_unslash( $_GET['wbccp_demo'] ) ) : '';
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WB Community Calendar Settings', 'wb-community-calendar-pro' ); ?></h1>
			<?php if ( $status ) : ?>
				<?php if ( 'generated' === $status ) : ?>
					<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Demo data generated successfully.', 'wb-community-calendar-pro' ); ?></p></div>
				<?php elseif ( 'deleted' === $status ) : ?>
					<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Demo data deleted successfully.', 'wb-community-calendar-pro' ); ?></p></div>
				<?php elseif ( 'no-demo' === $status ) : ?>
					<div class="notice notice-warning is-dismissible"><p><?php esc_html_e( 'No demo data found to delete.', 'wb-community-calendar-pro' ); ?></p></div>
				<?php elseif ( 'exists' === $status ) : ?>
					<div class="notice notice-warning is-dismissible"><p><?php esc_html_e( 'Demo data already exists. Please delete it before generating again.', 'wb-community-calendar-pro' ); ?></p></div>
				<?php elseif ( 'error' === $status ) : ?>
					<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Demo action failed. Please check server logs.', 'wb-community-calendar-pro' ); ?></p></div>
				<?php endif; ?>
			<?php endif; ?>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( 'wbccp-settings' );
				submit_button();
				?>
			</form>
			<hr />
			<h2><?php esc_html_e( 'Demo Data', 'wb-community-calendar-pro' ); ?></h2>
			<p><?php esc_html_e( 'Generate sample BuddyPress groups and events for testing. You can delete them anytime.', 'wb-community-calendar-pro' ); ?></p>
			<p><strong><?php esc_html_e( 'Demo login:', 'wb-community-calendar-pro' ); ?></strong> eventmember / EventMember123!</p>
			<?php
			$action_url = admin_url( 'admin-post.php' );
			?>
			<p>
				<form method="post" action="<?php echo esc_url( $action_url ); ?>" style="display:inline-block;margin-right:8px;">
					<input type="hidden" name="action" value="wbccp_generate_demo" />
					<?php wp_nonce_field( 'wbccp_generate_demo', 'wbccp_demo_nonce' ); ?>
					<button type="submit" class="button button-secondary"><?php esc_html_e( 'Generate Demo Data', 'wb-community-calendar-pro' ); ?></button>
				</form>
				<form method="post" action="<?php echo esc_url( $action_url ); ?>" style="display:inline-block;">
					<input type="hidden" name="action" value="wbccp_delete_demo" />
					<?php wp_nonce_field( 'wbccp_delete_demo', 'wbccp_demo_nonce' ); ?>
					<button type="submit" class="button button-secondary<?php echo $has_demo ? '' : ' disabled'; ?>"<?php echo $has_demo ? '' : ' disabled'; ?>><?php esc_html_e( 'Delete Demo Data', 'wb-community-calendar-pro' ); ?></button>
				</form>
			</p>
		</div>
		<?php
	}

	public static function field_demo_actions() {
		echo '<p>' . esc_html__( 'Use the Demo Data section below to generate or delete sample content.', 'wb-community-calendar-pro' ) . '</p>';
	}

	public static function handle_generate_demo() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'wb-community-calendar-pro' ) );
		}

		if ( empty( $_POST['wbccp_demo_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['wbccp_demo_nonce'] ), 'wbccp_generate_demo' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'wb-community-calendar-pro' ) );
		}

		self::generate_demo_data();
	}

	private static function generate_demo_data() {
		$existing_demo = get_option( 'wbccp_demo_data', array() );
		if ( ! empty( $existing_demo['events'] ) || ! empty( $existing_demo['groups'] ) || ! empty( $existing_demo['pages'] ) || ! empty( $existing_demo['attachments'] ) || ! empty( $existing_demo['users'] ) || ! empty( $existing_demo['menu_items'] ) ) {
			wp_safe_redirect( add_query_arg( 'wbccp_demo', 'exists', admin_url( 'options-general.php?page=wbccp-settings' ) ) );
			exit;
		}

		$demo = array(
			'groups'      => array(),
			'events'      => array(),
			'attachments' => array(),
			'pages'       => array(),
			'users'       => array(),
			'menu_items'  => array(),
			'terms'       => array(),
		);

		$settings = self::get_settings();
		$timezone = $settings['default_timezone'];
		$current_user = get_current_user_id();

		$groups_config = array(
			array(
				'name'        => __( 'Community Ambassadors', 'wb-community-calendar-pro' ),
				'slug'        => 'wbccp-ambassadors',
				'description' => __( 'Welcome group for new members and ambassadors.', 'wb-community-calendar-pro' ),
				'status'      => 'public',
			),
			array(
				'name'        => __( 'Makers & Creators', 'wb-community-calendar-pro' ),
				'slug'        => 'wbccp-makers',
				'description' => __( 'Workshops and co-working sessions for creators.', 'wb-community-calendar-pro' ),
				'status'      => 'public',
			),
			array(
				'name'        => __( 'Wellness Circle', 'wb-community-calendar-pro' ),
				'slug'        => 'wbccp-wellness',
				'description' => __( 'Weekly check-ins and wellness activities.', 'wb-community-calendar-pro' ),
				'status'      => 'public',
			),
			array(
				'name'        => __( 'Leadership Forum', 'wb-community-calendar-pro' ),
				'slug'        => 'wbccp-leadership',
				'description' => __( 'Monthly town halls and leadership Q&A.', 'wb-community-calendar-pro' ),
				'status'      => 'private',
			),
			array(
				'name'        => __( 'Events Crew', 'wb-community-calendar-pro' ),
				'slug'        => 'wbccp-events-crew',
				'description' => __( 'Internal planning and event ops group.', 'wb-community-calendar-pro' ),
				'status'      => 'hidden',
			),
		);

		$demo_user_data = self::ensure_demo_user( 'eventmember', 'eventmember@example.com' );
		$demo_user = 0;
		if ( ! empty( $demo_user_data['id'] ) ) {
			$demo_user = (int) $demo_user_data['id'];
			if ( ! empty( $demo_user_data['created'] ) ) {
				$demo['users'][] = (int) $demo_user_data['id'];
			}
		}

		$group_ids = array();
		$group_meta_map = array();
		if ( function_exists( 'groups_create_group' ) ) {
			foreach ( $groups_config as $group_config ) {
				$group_id = groups_create_group(
					array(
						'creator_id'  => $current_user,
						'name'        => $group_config['name'],
						'slug'        => $group_config['slug'] . '-' . time(),
						'description' => $group_config['description'],
						'status'      => $group_config['status'],
					)
				);

				if ( $group_id ) {
					$group_ids[] = (int) $group_id;
					$group_meta_map[ (int) $group_id ] = $group_config;
					$demo['groups'][] = (int) $group_id;
					if ( function_exists( 'groups_join_group' ) ) {
						groups_join_group( $group_id, $current_user );
					}
					if ( $demo_user && function_exists( 'groups_join_group' ) ) {
						groups_join_group( $group_id, $demo_user );
					}
				}
			}
		}

		$event_templates = array(
			array(
				'title'       => __( 'Community Meetup', 'wb-community-calendar-pro' ),
				'description' => __( 'An open meetup to connect, share updates, and welcome new members.', 'wb-community-calendar-pro' ),
				'location'    => __( 'Community Hub', 'wb-community-calendar-pro' ),
			),
			array(
				'title'       => __( 'Workshop Session', 'wb-community-calendar-pro' ),
				'description' => __( 'Hands-on learning session with a guest host and guided exercises.', 'wb-community-calendar-pro' ),
				'location'    => __( 'Workshop Room', 'wb-community-calendar-pro' ),
			),
			array(
				'title'       => __( 'Weekly Check-in', 'wb-community-calendar-pro' ),
				'description' => __( 'Short sync to share progress, blockers, and upcoming goals.', 'wb-community-calendar-pro' ),
				'location'    => __( 'Online', 'wb-community-calendar-pro' ),
			),
		);

		$category_names = array(
			__( 'Meetup', 'wb-community-calendar-pro' ),
			__( 'Workshop', 'wb-community-calendar-pro' ),
			__( 'Wellness', 'wb-community-calendar-pro' ),
			__( 'Town Hall', 'wb-community-calendar-pro' ),
		);
		$tag_names = array( 'community', 'online', 'social', 'training', 'planning' );
		$category_term_ids = array();
		foreach ( $category_names as $name ) {
			$term = term_exists( $name, WBCCP_CPT::TAX_CATEGORY );
			if ( empty( $term ) ) {
				$term = wp_insert_term( $name, WBCCP_CPT::TAX_CATEGORY );
			}
			if ( ! is_wp_error( $term ) ) {
				$term_id = (int) ( is_array( $term ) ? $term['term_id'] : $term );
				if ( $term_id ) {
					$category_term_ids[] = $term_id;
					$demo['terms'][] = array(
						'taxonomy' => WBCCP_CPT::TAX_CATEGORY,
						'term_id'  => $term_id,
					);
				}
			}
		}
		$tag_term_ids = array();
		foreach ( $tag_names as $name ) {
			$term = term_exists( $name, WBCCP_CPT::TAX_TAG );
			if ( empty( $term ) ) {
				$term = wp_insert_term( $name, WBCCP_CPT::TAX_TAG );
			}
			if ( ! is_wp_error( $term ) ) {
				$term_id = (int) ( is_array( $term ) ? $term['term_id'] : $term );
				if ( $term_id ) {
					$tag_term_ids[] = $term_id;
					$demo['terms'][] = array(
						'taxonomy' => WBCCP_CPT::TAX_TAG,
						'term_id'  => $term_id,
					);
				}
			}
		}

		$sitewide_templates = array(
			array(
				'title'       => __( 'Sitewide Town Hall', 'wb-community-calendar-pro' ),
				'description' => __( 'Quarterly all-hands meeting with announcements and Q&A.', 'wb-community-calendar-pro' ),
				'location'    => __( 'Main Auditorium', 'wb-community-calendar-pro' ),
			),
			array(
				'title'       => __( 'Community AMA', 'wb-community-calendar-pro' ),
				'description' => __( 'Ask us anything session with the community team.', 'wb-community-calendar-pro' ),
				'location'    => __( 'Live Stream', 'wb-community-calendar-pro' ),
			),
			array(
				'title'       => __( 'Open House', 'wb-community-calendar-pro' ),
				'description' => __( 'Open house for all members to explore upcoming initiatives.', 'wb-community-calendar-pro' ),
				'location'    => __( 'Community Lounge', 'wb-community-calendar-pro' ),
			),
		);

		$base_time = current_time( 'timestamp' );
		$day_offsets = array( 2, 6, 11, 16, 21, 27, 33, 39, 45, 52, 60, 68, 75, 82, 90 );

		foreach ( $group_ids as $group_index => $group_id ) {
			$group_meta = isset( $group_meta_map[ $group_id ] ) ? $group_meta_map[ $group_id ] : array();
			$group_name = isset( $group_meta['name'] ) ? $group_meta['name'] : '';
			for ( $i = 0; $i < 3; $i++ ) {
				$template = $event_templates[ $i % count( $event_templates ) ];
				$offset_index = ( $group_index * 3 ) + $i;
				$days = isset( $day_offsets[ $offset_index ] ) ? $day_offsets[ $offset_index ] : ( 2 + ( $offset_index * 3 ) );
				$start_ts = $base_time + ( DAY_IN_SECONDS * $days ) + ( HOUR_IN_SECONDS * 2 );
				$end_ts = $start_ts + ( 2 * HOUR_IN_SECONDS );

				$event_title = trim( $template['title'] . ( $group_name ? ' - ' . $group_name : '' ) );
				$event_body = $template['description'];
				if ( $group_name ) {
					$event_body .= "\n\n" . sprintf(
						/* translators: %s: group name */
						__( 'Hosted by the %s group.', 'wb-community-calendar-pro' ),
						wp_strip_all_tags( $group_name )
					);
				}

				$event_id = wp_insert_post(
					array(
						'post_type'    => WBCCP_CPT::CPT,
						'post_status'  => 'publish',
						'post_title'   => $event_title,
						'post_content' => $event_body,
						'post_author'  => $current_user,
					)
				);

				if ( $event_id ) {
					update_post_meta( $event_id, 'wbccp_group_id', $group_id );
					update_post_meta( $event_id, 'wbccp_start', $start_ts );
					update_post_meta( $event_id, 'wbccp_end', $end_ts );
					update_post_meta( $event_id, 'wbccp_timezone', $timezone );
					update_post_meta( $event_id, 'wbccp_location', $template['location'] );
					update_post_meta( $event_id, 'wbccp_link', 'https://example.com' );
					update_post_meta( $event_id, 'wbccp_capacity', 25 + ( $i * 10 ) );

					if ( ! empty( $category_term_ids ) ) {
						$category_id = $category_term_ids[ $offset_index % count( $category_term_ids ) ];
						wp_set_post_terms( $event_id, array( $category_id ), WBCCP_CPT::TAX_CATEGORY, false );
					}
					if ( ! empty( $tag_term_ids ) ) {
						$tag_id = $tag_term_ids[ $offset_index % count( $tag_term_ids ) ];
						wp_set_post_terms( $event_id, array( $tag_id ), WBCCP_CPT::TAX_TAG, false );
					}

					$image_index = ( $offset_index % 5 ) + 1;
					$image_name = sprintf( 'demo-%02d.png', $image_index );
					$attachment_id = self::create_demo_image( $template['title'], $image_name );
					if ( $attachment_id ) {
						set_post_thumbnail( $event_id, $attachment_id );
						$demo['attachments'][] = (int) $attachment_id;
					}

					$demo['events'][] = (int) $event_id;
				}
			}
		}

		$sitewide_offsets = array( 1, 9, 18 );
		foreach ( $sitewide_templates as $index => $template ) {
			$days = isset( $sitewide_offsets[ $index ] ) ? $sitewide_offsets[ $index ] : ( 5 + ( $index * 7 ) );
			$start_ts = $base_time + ( DAY_IN_SECONDS * $days ) + ( HOUR_IN_SECONDS * 3 );
			$end_ts = $start_ts + ( 2 * HOUR_IN_SECONDS );

			$post_status = 0 === $index ? 'publish' : 'pending';
			$event_id = wp_insert_post(
				array(
					'post_type'    => WBCCP_CPT::CPT,
					'post_status'  => $post_status,
					'post_title'   => $template['title'],
					'post_content' => $template['description'],
					'post_author'  => $current_user,
				)
			);

			if ( $event_id ) {
				update_post_meta( $event_id, 'wbccp_group_id', 0 );
				update_post_meta( $event_id, 'wbccp_start', $start_ts );
				update_post_meta( $event_id, 'wbccp_end', $end_ts );
				update_post_meta( $event_id, 'wbccp_timezone', $timezone );
				update_post_meta( $event_id, 'wbccp_location', $template['location'] );
				update_post_meta( $event_id, 'wbccp_link', 'https://example.com' );
				update_post_meta( $event_id, 'wbccp_capacity', 40 );

				if ( ! empty( $category_term_ids ) ) {
					$category_id = $category_term_ids[ $index % count( $category_term_ids ) ];
					wp_set_post_terms( $event_id, array( $category_id ), WBCCP_CPT::TAX_CATEGORY, false );
				}
				if ( ! empty( $tag_term_ids ) ) {
					$tag_id = $tag_term_ids[ $index % count( $tag_term_ids ) ];
					wp_set_post_terms( $event_id, array( $tag_id ), WBCCP_CPT::TAX_TAG, false );
				}

				$image_index = ( $index % 5 ) + 1;
				$image_name = sprintf( 'demo-%02d.png', $image_index );
				$attachment_id = self::create_demo_image( $template['title'], $image_name );
				if ( $attachment_id ) {
					set_post_thumbnail( $event_id, $attachment_id );
					$demo['attachments'][] = (int) $attachment_id;
				}

				$demo['events'][] = (int) $event_id;
			}
		}

		$page_id = 0;
		$existing_page = get_page_by_title( __( 'Sitewide Events', 'wb-community-calendar-pro' ) );
		if ( $existing_page && ! empty( $existing_page->ID ) ) {
			$page_id = (int) $existing_page->ID;
		} else {
			$page_id = wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => __( 'Sitewide Events', 'wb-community-calendar-pro' ),
					'post_content' => '[wbccp_calendar scope="sitewide" allow_submit="yes"]',
					'post_author'  => $current_user,
				)
			);
			if ( $page_id ) {
				$demo['pages'][] = (int) $page_id;
			}
		}

		if ( $page_id ) {
			$menu_item_id = self::add_page_to_primary_menu( $page_id );
			if ( $menu_item_id ) {
				$demo['menu_items'][] = (int) $menu_item_id;
			}
		}

		$user_ids = get_users(
			array(
				'number' => 5,
				'fields' => 'ID',
			)
		);

		$user_ids = array_merge( $user_ids, array( $current_user ) );
		if ( $demo_user ) {
			$user_ids[] = $demo_user;
		}
		$user_ids = array_unique( array_filter( $user_ids ) );

		$statuses = array( 'attending', 'maybe', 'cant' );
		foreach ( $demo['events'] as $event_id ) {
			foreach ( $user_ids as $i => $user_id ) {
				$status = $statuses[ $i % count( $statuses ) ];
				WBCCP_RSVP::set_status( $event_id, $user_id, $status );
			}
		}

		update_option( 'wbccp_demo_data', $demo );

		wp_safe_redirect( add_query_arg( 'wbccp_demo', 'generated', admin_url( 'options-general.php?page=wbccp-settings' ) ) );
		exit;
	}

	public static function handle_delete_demo() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'wb-community-calendar-pro' ) );
		}

		if ( empty( $_POST['wbccp_demo_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['wbccp_demo_nonce'] ), 'wbccp_delete_demo' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'wb-community-calendar-pro' ) );
		}

		self::delete_demo_data();
	}

	private static function delete_demo_data() {
		$demo = get_option( 'wbccp_demo_data', array() );

		if ( empty( $demo['events'] ) && empty( $demo['groups'] ) && empty( $demo['attachments'] ) && empty( $demo['pages'] ) && empty( $demo['users'] ) && empty( $demo['menu_items'] ) ) {
			wp_safe_redirect( add_query_arg( 'wbccp_demo', 'no-demo', admin_url( 'options-general.php?page=wbccp-settings' ) ) );
			exit;
		}

		if ( ! empty( $demo['events'] ) ) {
			foreach ( $demo['events'] as $event_id ) {
				wp_delete_post( (int) $event_id, true );
			}
			WBCCP_RSVP::delete_by_event_ids( $demo['events'] );
		}

		if ( ! empty( $demo['terms'] ) ) {
			foreach ( $demo['terms'] as $term_data ) {
				if ( empty( $term_data['term_id'] ) || empty( $term_data['taxonomy'] ) ) {
					continue;
				}
				wp_delete_term( (int) $term_data['term_id'], $term_data['taxonomy'] );
			}
		}

		if ( ! empty( $demo['groups'] ) && function_exists( 'groups_delete_group' ) ) {
			foreach ( $demo['groups'] as $group_id ) {
				groups_delete_group( (int) $group_id );
			}
		}

		if ( ! empty( $demo['attachments'] ) ) {
			foreach ( $demo['attachments'] as $attachment_id ) {
				wp_delete_attachment( (int) $attachment_id, true );
			}
		}

		if ( ! empty( $demo['pages'] ) ) {
			foreach ( $demo['pages'] as $page_id ) {
				wp_delete_post( (int) $page_id, true );
			}
		}

		if ( ! empty( $demo['menu_items'] ) ) {
			foreach ( $demo['menu_items'] as $menu_item_id ) {
				wp_delete_post( (int) $menu_item_id, true );
			}
		}

		if ( ! empty( $demo['users'] ) ) {
			if ( ! function_exists( 'wp_delete_user' ) ) {
				require_once ABSPATH . 'wp-admin/includes/user.php';
			}
			foreach ( $demo['users'] as $user_id ) {
				if ( (int) $user_id !== (int) get_current_user_id() ) {
					wp_delete_user( (int) $user_id );
				}
			}
		}

		delete_option( 'wbccp_demo_data' );

		wp_safe_redirect( add_query_arg( 'wbccp_demo', 'deleted', admin_url( 'options-general.php?page=wbccp-settings' ) ) );
		exit;
	}

	public static function handle_demo_actions_from_get() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( empty( $_GET['wbccp_demo_action'] ) || empty( $_GET['wbccp_demo_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( wp_unslash( $_GET['wbccp_demo_nonce'] ), 'wbccp_demo_action' ) ) {
			wp_safe_redirect( add_query_arg( 'wbccp_demo', 'error', admin_url( 'options-general.php?page=wbccp-settings' ) ) );
			exit;
		}

		$action = sanitize_text_field( wp_unslash( $_GET['wbccp_demo_action'] ) );
		if ( 'generate' === $action ) {
			self::generate_demo_data();
		}

		if ( 'delete' === $action ) {
			self::delete_demo_data();
		}
	}

	private static function ensure_demo_user( $login, $email ) {
		$user = get_user_by( 'login', $login );
		if ( $user ) {
			return array(
				'id'      => (int) $user->ID,
				'created' => false,
			);
		}

		$user = get_user_by( 'email', $email );
		if ( $user ) {
			return array(
				'id'      => (int) $user->ID,
				'created' => false,
			);
		}

		$user_id = wp_insert_user(
			array(
				'user_login'   => $login,
				'user_email'   => $email,
				'display_name' => __( 'Event Member', 'wb-community-calendar-pro' ),
				'user_pass'    => 'EventMember123!',
				'role'         => 'subscriber',
			)
		);

		if ( is_wp_error( $user_id ) ) {
			return array(
				'id'      => 0,
				'created' => false,
			);
		}

		return array(
			'id'      => (int) $user_id,
			'created' => true,
		);
	}

	private static function add_page_to_primary_menu( $page_id ) {
		if ( ! $page_id ) {
			return 0;
		}

		$locations = get_nav_menu_locations();
		if ( empty( $locations ) ) {
			return 0;
		}

		$preferred = array( 'primary', 'menu-1', 'main', 'header', 'top' );
		$menu_id = 0;
		foreach ( $preferred as $location ) {
			if ( ! empty( $locations[ $location ] ) ) {
				$menu_id = (int) $locations[ $location ];
				break;
			}
		}

		if ( ! $menu_id ) {
			$menu_id = (int) reset( $locations );
		}

		if ( ! $menu_id ) {
			return 0;
		}

		$existing_items = wp_get_nav_menu_items( $menu_id );
		if ( ! empty( $existing_items ) ) {
			foreach ( $existing_items as $item ) {
				if ( isset( $item->object_id ) && (int) $item->object_id === (int) $page_id ) {
					return 0;
				}
			}
		}

		$menu_item_id = wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-object-id' => $page_id,
				'menu-item-object'    => 'page',
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
			)
		);

		if ( is_wp_error( $menu_item_id ) ) {
			return 0;
		}

		return (int) $menu_item_id;
	}

	private static function create_demo_image( $title, $image_name = '' ) {
		$asset_path = $image_name ? self::get_demo_asset_path( $image_name ) : '';
		if ( $asset_path ) {
			$attachment_id = self::import_demo_image( $title, $asset_path );
			if ( $attachment_id ) {
				return $attachment_id;
			}
		}

		if ( ! function_exists( 'imagecreatetruecolor' ) ) {
			return 0;
		}

		$upload = wp_upload_dir();
		if ( empty( $upload['path'] ) ) {
			return 0;
		}

		$filename = 'wbccp-demo-' . wp_generate_uuid4() . '.png';
		$filepath = trailingslashit( $upload['path'] ) . $filename;

		$image = imagecreatetruecolor( 1200, 800 );
		if ( ! $image ) {
			return 0;
		}

		$bg = imagecolorallocate( $image, 245, 248, 255 );
		$text_color = imagecolorallocate( $image, 34, 57, 94 );
		$accent = imagecolorallocate( $image, 56, 132, 255 );
		imagefilledrectangle( $image, 0, 0, 1200, 800, $bg );
		imagefilledrectangle( $image, 0, 0, 1200, 60, $accent );

		$text = strtoupper( sanitize_text_field( $title ) );
		imagestring( $image, 5, 40, 120, $text, $text_color );
		imagestring( $image, 3, 40, 160, 'WB Community Calendar Pro', $text_color );

		imagepng( $image, $filepath );
		imagedestroy( $image );

		$filetype = wp_check_filetype( $filename, null );
		$attachment = array(
			'post_mime_type' => $filetype['type'],
			'post_title'     => sanitize_text_field( $title ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		$attach_id = wp_insert_attachment( $attachment, $filepath );
		if ( ! $attach_id ) {
			return 0;
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		$attach_data = wp_generate_attachment_metadata( $attach_id, $filepath );
		wp_update_attachment_metadata( $attach_id, $attach_data );

		return (int) $attach_id;
	}

	private static function get_demo_asset_path( $image_name ) {
		$image_name = sanitize_file_name( $image_name );
		if ( ! $image_name ) {
			return '';
		}

		$path = trailingslashit( WBCCP_PATH ) . 'assets/demo-images/' . $image_name;
		if ( ! file_exists( $path ) ) {
			return '';
		}

		return $path;
	}

	private static function import_demo_image( $title, $source_path ) {
		$upload = wp_upload_dir();
		if ( empty( $upload['path'] ) ) {
			return 0;
		}

		wp_mkdir_p( $upload['path'] );

		$filename = wp_unique_filename( $upload['path'], wp_basename( $source_path ) );
		$destination = trailingslashit( $upload['path'] ) . $filename;

		if ( ! copy( $source_path, $destination ) ) {
			return 0;
		}

		$filetype = wp_check_filetype( $filename, null );
		$attachment = array(
			'post_mime_type' => $filetype['type'],
			'post_title'     => sanitize_text_field( $title ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		$attach_id = wp_insert_attachment( $attachment, $destination );
		if ( ! $attach_id ) {
			return 0;
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		$attach_data = wp_generate_attachment_metadata( $attach_id, $destination );
		wp_update_attachment_metadata( $attach_id, $attach_data );

		return (int) $attach_id;
	}
}
