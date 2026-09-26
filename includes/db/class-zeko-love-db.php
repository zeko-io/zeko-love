<?php
/**
 * Database schema and query layer for Zeko Love (dating plugin).
 *
 * 8 custom tables for profiles, photos, interests, matches, interactions,
 * date scheduling, conversations, and messages.
 *
 * @package Zeko_Love
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Class Zeko_Love_DB. */
class Zeko_Love_DB {

	/**
	 * Db version.
	 *
	 * @var string Db version.
	 */
	private string $db_version = '1.5.0';

	/**
	 * Wpdb.
	 *
	 * @var \wpdb Wpdb.
	 */
	private \wpdb $wpdb;

	/**
	 * Sanitize rich-text (Quill) content with the narrow ecosystem
	 * allow-list; falls back to wp_kses_post() when zeko-core is absent.
	 *
	 * @param mixed $html Html.
	 */
	private static function sanitize_rich( $html ): string {
		if ( class_exists( 'Zeko_Core_Sanitize' ) ) {
			return Zeko_Core_Sanitize::rich_text( (string) $html );
		}
		return wp_kses_post( (string) $html );
	}

	/**
	 * Table profiles.
	 *
	 * @var string Table profiles.
	 */
	private string $table_profiles;
	/**
	 * Table photos.
	 *
	 * @var string Table photos.
	 */
	private string $table_photos;
	/**
	 * Table interests.
	 *
	 * @var string Table interests.
	 */
	private string $table_interests;
	/**
	 * Table matches.
	 *
	 * @var string Table matches.
	 */
	private string $table_matches;
	/**
	 * Table interactions.
	 *
	 * @var string Table interactions.
	 */
	private string $table_interactions;
	/**
	 * Table date scheduling.
	 *
	 * @var string Table date scheduling.
	 */
	private string $table_date_scheduling;
	/**
	 * Table conversations.
	 *
	 * @var string Table conversations.
	 */
	private string $table_conversations;
	/**
	 * Table messages.
	 *
	 * @var string Table messages.
	 */
	private string $table_messages;
	/**
	 * Table notifications.
	 *
	 * @var string Table notifications.
	 */
	private string $table_notifications;

	/**
	 * Table call plans.
	 *
	 * @var string Table call plans.
	 */
	private string $table_call_plans;
	/**
	 * Table call listings.
	 *
	 * @var string Table call listings.
	 */
	private string $table_call_listings;
	/**
	 * Table call slots.
	 *
	 * @var string Table call slots.
	 */
	private string $table_call_slots;
	/**
	 * Table call bookings.
	 *
	 * @var string Table call bookings.
	 */
	private string $table_call_bookings;
	/**
	 * Table call reviews.
	 *
	 * @var string Table call reviews.
	 */
	private string $table_call_reviews;
	/**
	 * Table call usage.
	 *
	 * @var string Table call usage.
	 */
	private string $table_call_usage;

	/**
	 * Construct.
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;

		$p                           = $wpdb->prefix . 'zeko_love_';
		$this->table_profiles        = $p . 'profiles';
		$this->table_photos          = $p . 'photos';
		$this->table_interests       = $p . 'interests';
		$this->table_matches         = $p . 'matches';
		$this->table_interactions    = $p . 'interactions';
		$this->table_date_scheduling = $p . 'date_scheduling';
		$this->table_conversations   = $p . 'conversations';
		$this->table_messages        = $p . 'messages';
		$this->table_notifications   = $p . 'notifications';

		$this->table_call_plans    = $p . 'call_plans';
		$this->table_call_listings = $p . 'call_listings';
		$this->table_call_slots    = $p . 'call_slots';
		$this->table_call_bookings = $p . 'call_bookings';
		$this->table_call_reviews  = $p . 'call_reviews';
		$this->table_call_usage    = $p . 'call_usage';
	}

	/**
	 * Create or upgrade all tables via dbDelta.
	 */
	public function create_tables(): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $this->get_charset_collate();
		$sql     = array();

		// ─── Profiles ─────────────────────────────────────────────.

		$sql[] = "CREATE TABLE {$this->table_profiles} (
            profile_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            display_name varchar(100) NOT NULL DEFAULT '',
            bio text DEFAULT NULL,
            dob date DEFAULT NULL,
            gender varchar(20) NOT NULL DEFAULT '',
            interested_in varchar(20) NOT NULL DEFAULT '',
            location_lat decimal(10,8) DEFAULT NULL,
            location_lng decimal(11,8) DEFAULT NULL,
            relationship_goal varchar(50) NOT NULL DEFAULT '',
            height_cm int(11) NOT NULL DEFAULT 0,
            occupation varchar(100) NOT NULL DEFAULT '',
            education varchar(100) NOT NULL DEFAULT '',
            smoking varchar(20) NOT NULL DEFAULT '',
            drinking varchar(20) NOT NULL DEFAULT '',
            has_children tinyint(1) NOT NULL DEFAULT 0,
            wants_children tinyint(1) NOT NULL DEFAULT 0,
            religion varchar(50) NOT NULL DEFAULT '',
            ethnicity varchar(50) NOT NULL DEFAULT '',
            profile_views int(11) NOT NULL DEFAULT 0,
            is_verified tinyint(1) NOT NULL DEFAULT 0,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            last_active datetime DEFAULT NULL,
            boosted_until datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (profile_id),
            UNIQUE KEY user_id (user_id),
            KEY gender_interested_active (gender, interested_in, is_active),
            KEY profile_views (profile_views),
            KEY created_at (created_at)
        ) {$charset};";

		// ─── Photos ───────────────────────────────────────────────.

		$sql[] = "CREATE TABLE {$this->table_photos} (
            photo_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            photo_url varchar(255) NOT NULL DEFAULT '',
            is_primary tinyint(1) NOT NULL DEFAULT 0,
            verification_status varchar(20) NOT NULL DEFAULT 'pending',
            admin_note text DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (photo_id),
            KEY user_id (user_id),
            KEY verification_status (verification_status)
        ) {$charset};";

		// ─── Interests ───────────────────────────────────────────.

		$sql[] = "CREATE TABLE {$this->table_interests} (
            interest_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            category varchar(50) NOT NULL DEFAULT '',
            value varchar(100) NOT NULL DEFAULT '',
            PRIMARY KEY (interest_id),
            KEY user_id (user_id),
            KEY category (category)
        ) {$charset};";

		// ─── Matches ─────────────────────────────────────────────.

		$sql[] = "CREATE TABLE {$this->table_matches} (
            match_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user1_id bigint(20) unsigned NOT NULL DEFAULT 0,
            user2_id bigint(20) unsigned NOT NULL DEFAULT 0,
            score decimal(5,2) NOT NULL DEFAULT 0.00,
            status varchar(20) NOT NULL DEFAULT 'pending',
            matched_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            responded_at datetime DEFAULT NULL,
            PRIMARY KEY (match_id),
            UNIQUE KEY user_pair (user1_id, user2_id),
            KEY user2_id (user2_id),
            KEY status (status)
        ) {$charset};";

		// ─── Interactions ─────────────────────────────────────────.

		$sql[] = "CREATE TABLE {$this->table_interactions} (
            interaction_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            target_id bigint(20) unsigned NOT NULL DEFAULT 0,
            type varchar(30) NOT NULL DEFAULT '',
            report_reason text DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (interaction_id),
            KEY user_target (user_id, target_id),
            KEY type (type)
        ) {$charset};";

		// ─── Date Scheduling ─────────────────────────────────────.

		$sql[] = "CREATE TABLE {$this->table_date_scheduling} (
            schedule_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user1_id bigint(20) unsigned NOT NULL DEFAULT 0,
            user2_id bigint(20) unsigned NOT NULL DEFAULT 0,
            proposed_date date DEFAULT NULL,
            proposed_time time DEFAULT NULL,
            duration_minutes int(11) NOT NULL DEFAULT 60,
            location_name varchar(200) NOT NULL DEFAULT '',
            location_address text DEFAULT NULL,
            location_lat decimal(10,8) DEFAULT NULL,
            location_lng decimal(11,8) DEFAULT NULL,
            notes text DEFAULT NULL,
            status varchar(30) NOT NULL DEFAULT 'pending',
            created_by bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (schedule_id),
            KEY status (status),
            KEY user1_id (user1_id),
            KEY user2_id (user2_id)
        ) {$charset};";

		// ─── Conversations ────────────────────────────────────────.

		$sql[] = "CREATE TABLE {$this->table_conversations} (
            conversation_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user1_id bigint(20) unsigned NOT NULL DEFAULT 0,
            user2_id bigint(20) unsigned NOT NULL DEFAULT 0,
            last_message_at datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (conversation_id),
            UNIQUE KEY user_pair (user1_id, user2_id),
            KEY user2_id (user2_id),
            KEY last_message_at (last_message_at)
        ) {$charset};";

		// ─── Messages ────────────────────────────────────────────.

		$sql[] = "CREATE TABLE {$this->table_messages} (
            message_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            conversation_id bigint(20) unsigned NOT NULL DEFAULT 0,
            sender_id bigint(20) unsigned NOT NULL DEFAULT 0,
            message text NOT NULL,
            is_read tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (message_id),
            KEY conversation_id (conversation_id),
            KEY is_read (is_read),
            KEY created_at (created_at)
        ) {$charset};";

		// ─── Notifications ───────────────────────────────────────.

		$sql[] = "CREATE TABLE {$this->table_notifications} (
            notification_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            type varchar(50) NOT NULL DEFAULT '',
            object_id bigint(20) unsigned NOT NULL DEFAULT 0,
            object_type varchar(50) NOT NULL DEFAULT '',
            actor_id bigint(20) unsigned NOT NULL DEFAULT 0,
            is_read tinyint(1) NOT NULL DEFAULT 0,
            message text DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (notification_id),
            KEY user_id (user_id),
            KEY user_unread (user_id, is_read),
            KEY is_read (is_read),
            KEY created_at (created_at)
        ) {$charset};";

		// ─── Calls & Availability ─────────────────────────────────.

		$sql[] = "CREATE TABLE {$this->table_call_plans} (
            plan_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL DEFAULT '',
            price decimal(10,2) NOT NULL DEFAULT 0.00,
            duration_days int(11) NOT NULL DEFAULT 30,
            max_usage_minutes int(11) NOT NULL DEFAULT 0,
            max_listings int(11) NOT NULL DEFAULT 1,
            features text DEFAULT NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (plan_id),
            KEY is_active (is_active)
        ) {$charset};";

		$sql[] = "CREATE TABLE {$this->table_call_listings} (
            listing_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            title varchar(150) NOT NULL DEFAULT '',
            description text DEFAULT NULL,
            call_type varchar(20) NOT NULL DEFAULT 'video',
            provider varchar(20) NOT NULL DEFAULT 'google_meet',
            price_per_minute decimal(10,2) NOT NULL DEFAULT 0.00,
            min_duration int(11) NOT NULL DEFAULT 15,
            max_duration int(11) NOT NULL DEFAULT 60,
            currency varchar(10) NOT NULL DEFAULT 'USD',
            cover_photo_url varchar(255) NOT NULL DEFAULT '',
            intro_video_url varchar(255) NOT NULL DEFAULT '',
            youtube_url varchar(255) NOT NULL DEFAULT '',
            vimeo_url varchar(255) NOT NULL DEFAULT '',
            whatsapp_phone varchar(30) NOT NULL DEFAULT '',
            join_url varchar(255) NOT NULL DEFAULT '',
            plan_id bigint(20) unsigned NOT NULL DEFAULT 0,
            plan_ends_at datetime DEFAULT NULL,
            usage_minutes int(11) NOT NULL DEFAULT 0,
            status varchar(20) NOT NULL DEFAULT 'draft',
            views int(11) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (listing_id),
            KEY user_id (user_id),
            KEY status (status),
            KEY call_type (call_type)
        ) {$charset};";

		$sql[] = "CREATE TABLE {$this->table_call_slots} (
            slot_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            listing_id bigint(20) unsigned NOT NULL DEFAULT 0,
            day_of_week tinyint(4) NOT NULL DEFAULT 0,
            start_time time DEFAULT NULL,
            end_time time DEFAULT NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (slot_id),
            KEY listing_id (listing_id),
            KEY listing_day (listing_id, day_of_week)
        ) {$charset};";

		$sql[] = "CREATE TABLE {$this->table_call_bookings} (
            booking_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            listing_id bigint(20) unsigned NOT NULL DEFAULT 0,
            seller_id bigint(20) unsigned NOT NULL DEFAULT 0,
            buyer_id bigint(20) unsigned NOT NULL DEFAULT 0,
            start_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            duration_minutes int(11) NOT NULL DEFAULT 15,
            amount decimal(10,2) NOT NULL DEFAULT 0.00,
            currency varchar(10) NOT NULL DEFAULT 'USD',
            status varchar(20) NOT NULL DEFAULT 'pending',
            tx_id bigint(20) unsigned NOT NULL DEFAULT 0,
            payout_ref varchar(100) NOT NULL DEFAULT '',
            completed_at datetime DEFAULT NULL,
            cancelled_by bigint(20) unsigned NOT NULL DEFAULT 0,
            cancel_reason varchar(255) NOT NULL DEFAULT '',
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (booking_id),
            KEY seller_id (seller_id),
            KEY buyer_id (buyer_id),
            KEY listing_id (listing_id),
            KEY status (status)
        ) {$charset};";

		$sql[] = "CREATE TABLE {$this->table_call_reviews} (
            review_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            booking_id bigint(20) unsigned NOT NULL DEFAULT 0,
            reviewer_id bigint(20) unsigned NOT NULL DEFAULT 0,
            reviewee_id bigint(20) unsigned NOT NULL DEFAULT 0,
            rating tinyint(4) NOT NULL DEFAULT 5,
            comment text DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (review_id),
            UNIQUE KEY booking_reviewer (booking_id, reviewer_id),
            KEY reviewee_id (reviewee_id)
        ) {$charset};";

		$sql[] = "CREATE TABLE {$this->table_call_usage} (
            usage_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            listing_id bigint(20) unsigned NOT NULL DEFAULT 0,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            usage_minutes int(11) NOT NULL DEFAULT 0,
            billing_date date NOT NULL,
            PRIMARY KEY (usage_id),
            UNIQUE KEY listing_date (listing_id, billing_date)
        ) {$charset};";

		// Execute all queries.
		foreach ( $sql as $query ) {
			dbDelta( $query );
		}

		$this->maybe_upgrade();
	}

	/**
	 * Run schema version upgrades.
	 */
	private function maybe_upgrade(): void {
		$installed = get_option( 'zeko_love_db_version', '0' );

		if ( version_compare( $installed, '1.2.0', '<' ) ) {
			$this->add_last_active_column();
		}

		if ( version_compare( $installed, '1.3.0', '<' ) ) {
			$this->add_boosted_until_column();
		}

		if ( version_compare( $installed, '1.5.0', '<' ) ) {
			$this->add_index_if_missing( $this->table_matches, 'user2_id', '`user2_id`' );
			$this->add_index_if_missing( $this->table_conversations, 'user2_id', '`user2_id`' );
			$this->add_index_if_missing( $this->table_notifications, 'user_unread', '`user_id`, `is_read`' );
		}

		if ( version_compare( $installed, $this->db_version, '<' ) ) {
			update_option( 'zeko_love_db_version', $this->db_version );
		}
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Add the profiles.last_active column (used for online status badges).
	 * Guarded so it is safe to run repeatedly on older installs.
	 */
	private function add_last_active_column(): void {
		$column = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SHOW COLUMNS FROM {$this->table_profiles} LIKE %s",
				'last_active'
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		if ( ! $column ) {
			$this->wpdb->query(
				"ALTER TABLE {$this->table_profiles} ADD COLUMN last_active datetime DEFAULT NULL AFTER updated_at"
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		}
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Add the profiles.boosted_until column (paid profile boost expiry).
	 * Guarded so it is safe to run repeatedly on older installs.
	 */
	private function add_boosted_until_column(): void {
		$column = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SHOW COLUMNS FROM {$this->table_profiles} LIKE %s",
				'boosted_until'
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		if ( ! $column ) {
			$this->wpdb->query(
				"ALTER TABLE {$this->table_profiles} ADD COLUMN boosted_until datetime DEFAULT NULL AFTER last_active"
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		}
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Add an index if it doesn't already exist.
	 *
	 * @param string $table Table.
	 * @param string $index_name Index name.
	 * @param string $columns Columns.
	 */
	private function add_index_if_missing( string $table, string $index_name, string $columns ): void {
		$exists = $this->wpdb->get_var(
			$this->wpdb->prepare(
				'SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = %s AND index_name = %s',
				$table,
				$index_name
			)
		);
		if ( ! $exists ) {
			$this->wpdb->query(
				"ALTER TABLE {$table} ADD KEY `{$index_name}` ({$columns})" // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		}
	}

	/**
	 * Drop all tables on uninstall.
	 */
	public function drop_tables(): void {
		$tables = array(
			$this->table_profiles,
			$this->table_photos,
			$this->table_interests,
			$this->table_matches,
			$this->table_interactions,
			$this->table_date_scheduling,
			$this->table_conversations,
			$this->table_messages,
			$this->table_notifications,
			$this->table_call_plans,
			$this->table_call_listings,
			$this->table_call_slots,
			$this->table_call_bookings,
			$this->table_call_reviews,
			$this->table_call_usage,
		);
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		foreach ( $tables as $table ) {
			$this->wpdb->query( "DROP TABLE IF EXISTS {$table}" );
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		}
	}

	// ─── Table Getters ───────────────────────────────────────────.

	/**
	 * Table profiles.
	 */
	public function get_table_profiles(): string {
		return $this->table_profiles; }
	/**
	 * Table photos.
	 */
	public function get_table_photos(): string {
		return $this->table_photos; }
	/**
	 * Table interests.
	 */
	public function get_table_interests(): string {
		return $this->table_interests; }
	/**
	 * Table matches.
	 */
	public function get_table_matches(): string {
		return $this->table_matches; }
	/**
	 * Table interactions.
	 */
	public function get_table_interactions(): string {
		return $this->table_interactions; }
	/**
	 * Table dates.
	 */
	public function get_table_dates(): string {
		return $this->table_date_scheduling; }
	/**
	 * Table conversations.
	 */
	public function get_table_conversations(): string {
		return $this->table_conversations; }
	/**
	 * Table messages.
	 */
	public function get_table_messages(): string {
		return $this->table_messages; }
	/**
	 * Table notifications.
	 */
	public function get_table_notifications(): string {
		return $this->table_notifications; }

	/**
	 * Table call plans.
	 */
	public function get_table_call_plans(): string {
		return $this->table_call_plans; }
	/**
	 * Table call listings.
	 */
	public function get_table_call_listings(): string {
		return $this->table_call_listings; }
	/**
	 * Table call slots.
	 */
	public function get_table_call_slots(): string {
		return $this->table_call_slots; }
	/**
	 * Table call bookings.
	 */
	public function get_table_call_bookings(): string {
		return $this->table_call_bookings; }
	/**
	 * Table call reviews.
	 */
	public function get_table_call_reviews(): string {
		return $this->table_call_reviews; }
	/**
	 * Table call usage.
	 */
	public function get_table_call_usage(): string {
		return $this->table_call_usage; }

	// ─── Charset ─────────────────────────────────────────────────.

	/**
	 * Charset collate.
	 */
	public function get_charset_collate(): string {
		return $this->wpdb->get_charset_collate();
	}

	// ═══════════════════════════════════════════════════════════════.
	// PROFILE QUERIES.
	// ═══════════════════════════════════════════════════════════════.

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Get a dating profile by user ID.
	 *
	 * @param int $user_id User id.
	 */
	public function get_profile( int $user_id ): ?array {
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$this->table_profiles} WHERE user_id = %d LIMIT 1", $user_id ),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return $row ?: null;
	}

	/**
	 * Whether a user opted out of search/discovery (show_in_search = 0).
	 *
	 * @return bool True when the user is not discoverable.
	 * @param int $user_id Profile owner.
	 */
	public function is_opted_out( int $user_id ): bool {
		$settings = get_user_meta( $user_id, 'zeko_love_user_settings', true );
		$settings = is_array( $settings ) ? $settings : array();
		$show     = isset( $settings['show_in_search'] ) ? (int) $settings['show_in_search'] : 1;
		return 0 === $show;
	}

	/**
	 * Whether two users are mutually matched.
	 *
	 * @return bool True when a row exists with status 'matched'.
	 * @param int $a User A.
	 * @param int $b User B.
	 */
	public function is_mutual_match( int $a, int $b ): bool {
		$match = $this->get_match( $a, $b );
		return $match && 'matched' === (string) $match['status'];
	}

	/**
	 * Central discovery/consent gate for viewing a dating profile, its photos,
	 * or a compatibility score.
	 * Rules:
	 * - Owners and site admins always pass.
	 * - A block in either direction always fails.
	 * - A user who opted out of search is only visible to a mutual match
	 * (their explicit consent extends discoverability to matches).
	 * - Everyone else is allowed by default (public discovery model).
	 *
	 * @return bool True when the viewer may see the content.
	 * @param int $owner_id Profile owner being viewed.
	 * @param int $viewer_id Current viewer (0 when anonymous).
	 */
	public function can_view_profile( int $owner_id, int $viewer_id ): bool {
		if ( $owner_id === $viewer_id ) {
			return true;
		}
		if ( 0 === $viewer_id || current_user_can( 'manage_options' ) ) {
			return current_user_can( 'manage_options' );
		}
		if ( $this->is_blocked( $owner_id, $viewer_id ) || $this->is_blocked( $viewer_id, $owner_id ) ) {
			return false;
		}
		if ( $this->is_opted_out( $owner_id ) && ! $this->is_mutual_match( $owner_id, $viewer_id ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Permanently remove a user from every love table (WP personal-data eraser,
	 * self-service profile deletion, and admin user deletion all route here).
	 * Deleting a conversation removes the whole thread (both sides), because a
	 * chat thread cannot meaningfully exist once one participant's data is gone.
	 *
	 * @return bool True when any rows were removed.
	 * @param int $user_id User to purge.
	 */
	public function delete_user_data( int $user_id ): bool {
		$user_id      = absint( $user_id );
		$removed_rows = 0;

		$simple_user = array(
			array( $this->table_profiles, 'user_id' ),
			array( $this->table_photos, 'user_id' ),
			array( $this->table_interests, 'user_id' ),
			array( $this->table_call_usage, 'user_id' ),
		);
		foreach ( $simple_user as $target ) {
			list( $table, $col ) = $target;
			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
			$removed_rows += $this->wpdb->query(
				$this->wpdb->prepare( "DELETE FROM {$table} WHERE {$col} = %d", $user_id )
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		foreach ( array( $this->table_interactions, $this->table_date_scheduling ) as $table ) {
			$removed_rows += $this->wpdb->query(
				$this->wpdb->prepare( "DELETE FROM {$table} WHERE user_id = %d OR target_id = %d", $user_id, $user_id )
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		// Messages: direct sender, or part of any conversation the user was in.
		$removed_rows += $this->wpdb->query(
			$this->wpdb->prepare(
				"DELETE FROM {$this->table_messages} WHERE sender_id = %d OR conversation_id IN (SELECT conversation_id FROM {$this->table_conversations} WHERE user1_id = %d OR user2_id = %d)",
				$user_id,
				$user_id,
				$user_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		// Matches + conversations involve either side.
		foreach ( array( $this->table_matches, $this->table_conversations ) as $table ) {
			$removed_rows += $this->wpdb->query(
				$this->wpdb->prepare( "DELETE FROM {$table} WHERE user1_id = %d OR user2_id = %d", $user_id, $user_id )
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		// Notifications by recipient or by actor.
		$removed_rows += $this->wpdb->query(
			$this->wpdb->prepare( "DELETE FROM {$this->table_notifications} WHERE user_id = %d OR actor_id = %d", $user_id, $user_id )
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		// Call bookings + reviews involving either party.
		$removed_rows += $this->wpdb->query(
			$this->wpdb->prepare( "DELETE FROM {$this->table_call_bookings} WHERE seller_id = %d OR buyer_id = %d", $user_id, $user_id )
		);
		$removed_rows += $this->wpdb->query(
			$this->wpdb->prepare( "DELETE FROM {$this->table_call_reviews} WHERE reviewer_id = %d OR reviewee_id = %d", $user_id, $user_id )
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		// Call listings owned by the user + their availability slots.
		$removed_rows += $this->wpdb->query(
			$this->wpdb->prepare( "DELETE FROM {$this->table_call_slots} WHERE listing_id IN (SELECT listing_id FROM {$this->table_call_listings} WHERE user_id = %d)", $user_id )
		);
		$removed_rows += $this->wpdb->query(
			$this->wpdb->prepare( "DELETE FROM {$this->table_call_listings} WHERE user_id = %d", $user_id )
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		// Love-owned user meta (settings, payout, credit flags).
		$this->wpdb->query(
			$this->wpdb->prepare( "DELETE FROM {$this->wpdb->usermeta} WHERE user_id = %d AND meta_key LIKE %s", $user_id, 'zeko_love_%' )
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

		return $removed_rows > 0;
	}

	/**
	 * Set a profile's boost expiry (UTC datetime string).
	 *
	 * @param int    $user_id User id.
	 * @param string $boosted_until Boosted until.
	 */
	public function boost_profile( int $user_id, string $boosted_until ): bool {
		$updated = $this->wpdb->update(
			$this->table_profiles,
			array( 'boosted_until' => $boosted_until ),
			array( 'user_id' => $user_id ),
			array( '%s' ),
			array( '%d' )
		);
		return false !== $updated;
	}

	/**
	 * Whether a profile is currently boosted (boost not yet expired).
	 *
	 * @param int $user_id User id.
	 */
	public function is_boosted( int $user_id ): bool {
		$until = $this->get_boost_until( $user_id );
		if ( ! $until ) {
			return false;
		}
		return strtotime( $until . ' UTC' ) > time();
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Get a profile's boosted_until value (UTC), if set.
	 *
	 * @param int $user_id User id.
	 */
	public function get_boost_until( int $user_id ): ?string {
		$until = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT boosted_until FROM {$this->table_profiles} WHERE user_id = %d LIMIT 1",
				$user_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return $until ? (string) $until : null;
	}

	/**
	 * Create or update a dating profile.
	 *
	 * @param int   $user_id User id.
	 * @param array $data Data.
	 */
	public function save_profile( int $user_id, array $data ): bool {
		$existing = $this->get_profile( $user_id );

		$save_data = array(
			'user_id'           => $user_id,
			'display_name'      => sanitize_text_field( $data['display_name'] ?? '' ),
			'bio'               => self::sanitize_rich( $data['bio'] ?? '' ),
			'dob'               => sanitize_text_field( $data['dob'] ?? '' ),
			'gender'            => sanitize_text_field( $data['gender'] ?? '' ),
			'interested_in'     => sanitize_text_field( $data['interested_in'] ?? '' ),
			'location_lat'      => isset( $data['location_lat'] ) ? (float) $data['location_lat'] : null,
			'location_lng'      => isset( $data['location_lng'] ) ? (float) $data['location_lng'] : null,
			'relationship_goal' => sanitize_text_field( $data['relationship_goal'] ?? '' ),
			'height_cm'         => absint( $data['height_cm'] ?? 0 ),
			'occupation'        => sanitize_text_field( $data['occupation'] ?? '' ),
			'education'         => sanitize_text_field( $data['education'] ?? '' ),
			'smoking'           => sanitize_text_field( $data['smoking'] ?? '' ),
			'drinking'          => sanitize_text_field( $data['drinking'] ?? '' ),
			'has_children'      => ! empty( $data['has_children'] ) ? 1 : 0,
			'wants_children'    => ! empty( $data['wants_children'] ) ? 1 : 0,
			'religion'          => sanitize_text_field( $data['religion'] ?? '' ),
			'ethnicity'         => sanitize_text_field( $data['ethnicity'] ?? '' ),
			'updated_at'        => current_time( 'mysql', true ),
		);

		$format = array( '%d', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s' );

		if ( $existing ) {
			return (bool) $this->wpdb->update(
				$this->table_profiles,
				$save_data,
				array( 'user_id' => $user_id ),
				$format,
				array( '%d' )
			);
		}

		$save_data['created_at'] = current_time( 'mysql', true );
		return (bool) $this->wpdb->insert(
			$this->table_profiles,
			$save_data,
			$format
		);
	}

	/**
	 * Search profiles with advanced filters.
	 * Excludes blocked users and already-interacted profiles.
	 *
	 * @param array $filters Filters.
	 * @param int   $page Page.
	 * @param int   $per_page Per page.
	 */
	public function search_profiles( array $filters, int $page = 1, int $per_page = 20 ): array {
		$where  = array( 'p.is_active = 1' );
		$values = array();

		// Honor the per-user "show in search" privacy setting. The setting is.
		// stored as a serialized array in usermeta, so match the serialized.
		// key/value pair for the opted-out flag.
		$where[] = "NOT EXISTS (
            SELECT 1 FROM {$this->wpdb->usermeta} zl_meta
            WHERE zl_meta.user_id = p.user_id
              AND zl_meta.meta_key = 'zeko_love_user_settings'
              AND zl_meta.meta_value LIKE '%\"show_in_search\";i:0%'
        )";

		if ( ! empty( $filters['gender'] ) ) {
			$where[]  = 'p.gender = %s';
			$values[] = $filters['gender'];
		}

		if ( ! empty( $filters['interested_in'] ) ) {
			$where[]  = 'p.interested_in = %s';
			$values[] = $filters['interested_in'];
		}

		if ( ! empty( $filters['age_min'] ) ) {
			$where[]  = 'TIMESTAMPDIFF(YEAR, p.dob, CURDATE()) >= %d';
			$values[] = (int) $filters['age_min'];
		}

		if ( ! empty( $filters['age_max'] ) ) {
			$where[]  = 'TIMESTAMPDIFF(YEAR, p.dob, CURDATE()) <= %d';
			$values[] = (int) $filters['age_max'];
		}

		if ( ! empty( $filters['location_lat'] ) && ! empty( $filters['location_lng'] ) && ! empty( $filters['radius_km'] ) ) {
			$lat      = (float) $filters['location_lat'];
			$lng      = (float) $filters['location_lng'];
			$rad      = (float) $filters['radius_km'];
			$where[]  = '( 6371 * acos( cos( radians(%f) ) * cos( radians( p.location_lat ) ) * cos( radians( p.location_lng ) - radians(%f) ) + sin( radians(%f) ) * sin( radians( p.location_lat ) ) ) ) <= %f';
			$values[] = $lat;
			$values[] = $lng;
			$values[] = $lat;
			$values[] = $rad;
		}

		if ( ! empty( $filters['relationship_goal'] ) ) {
			$where[]  = 'p.relationship_goal = %s';
			$values[] = $filters['relationship_goal'];
		}

		if ( isset( $filters['has_children'] ) && '' !== $filters['has_children'] ) {
			$where[]  = 'p.has_children = %d';
			$values[] = (int) $filters['has_children'];
		}

		if ( isset( $filters['wants_children'] ) && '' !== $filters['wants_children'] ) {
			$where[]  = 'p.wants_children = %d';
			$values[] = (int) $filters['wants_children'];
		}

		if ( ! empty( $filters['smoking'] ) ) {
			$where[]  = 'p.smoking = %s';
			$values[] = $filters['smoking'];
		}

		if ( ! empty( $filters['drinking'] ) ) {
			$where[]  = 'p.drinking = %s';
			$values[] = $filters['drinking'];
		}

		if ( ! empty( $filters['religion'] ) ) {
			$where[]  = 'p.religion = %s';
			$values[] = $filters['religion'];
		}

		if ( ! empty( $filters['ethnicity'] ) ) {
			$where[]  = 'p.ethnicity = %s';
			$values[] = $filters['ethnicity'];
		}

		if ( ! empty( $filters['search'] ) ) {
			$where[]  = '(p.display_name LIKE %s OR p.bio LIKE %s)';
			$values[] = '%' . $this->wpdb->esc_like( $filters['search'] ) . '%';
			$values[] = '%' . $this->wpdb->esc_like( $filters['search'] ) . '%';
		}

		// Exclude blocked users (both directions).
		$blocked_subquery  = "SELECT target_id FROM {$this->table_interactions} WHERE type = 'block' AND ( user_id = %d OR target_id = %d )";
		$blocked_subquery2 = "SELECT user_id FROM {$this->table_interactions} WHERE type = 'block' AND ( user_id = %d OR target_id = %d )";

		// Exclude already-interacted users (like, pass).
		$interacted_subquery = "SELECT target_id FROM {$this->table_interactions} WHERE user_id = %d AND type IN ('like', 'pass')";

		$current_user_id = get_current_user_id();
		if ( $current_user_id ) {
			$where[]  = "p.user_id NOT IN ( {$blocked_subquery} )";
			$values[] = $current_user_id;
			$values[] = $current_user_id;

			$where[]  = "p.user_id NOT IN ( {$blocked_subquery2} )";
			$values[] = $current_user_id;
			$values[] = $current_user_id;

			$where[]  = "p.user_id NOT IN ( {$interacted_subquery} )";
			$values[] = $current_user_id;

			// Exclude self.
			$where[]  = 'p.user_id != %d';
			$values[] = $current_user_id;
		}

		$allowed_orderby = array( 'updated_at', 'created_at', 'profile_views' );
		$orderby         = in_array( $filters['orderby'] ?? '', $allowed_orderby, true ) ? 'p.' . $filters['orderby'] : 'p.updated_at';
		$order           = 'ASC' === strtoupper( $filters['order'] ?? 'DESC' ) ? 'ASC' : 'DESC';

		$offset = ( max( 1, $page ) - 1 ) * $per_page;
		$limit  = absint( $per_page );

		$where_sql = implode( ' AND ', $where );
		$prepare   = array_merge( $values, array( $limit, $offset ) );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return $this->wpdb->get_results(
			$this->wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
				"SELECT p.*, u.display_name AS user_display_name
                FROM {$this->table_profiles} p
                LEFT JOIN {$this->wpdb->users} u ON p.user_id = u.ID
                WHERE {$where_sql}
                ORDER BY ( p.boosted_until > UTC_TIMESTAMP() ) DESC, {$orderby} {$order}
                LIMIT %d OFFSET %d",
				...$prepare
			),
			ARRAY_A
		) ?: array();
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Count profiles matching search filters.
	 *
	 * @param array $filters Filters.
	 */
	public function count_profiles( array $filters ): int {
		$where  = array( 'p.is_active = 1' );
		$values = array();

		// Mirror the search filter: exclude users who opted out of search.
		$where[] = "NOT EXISTS (
            SELECT 1 FROM {$this->wpdb->usermeta} zl_meta
            WHERE zl_meta.user_id = p.user_id
              AND zl_meta.meta_key = 'zeko_love_user_settings'
              AND zl_meta.meta_value LIKE '%\"show_in_search\";i:0%'
        )";

		if ( ! empty( $filters['gender'] ) ) {
			$where[]  = 'p.gender = %s';
			$values[] = $filters['gender'];
		}

		if ( ! empty( $filters['interested_in'] ) ) {
			$where[]  = 'p.interested_in = %s';
			$values[] = $filters['interested_in'];
		}

		if ( ! empty( $filters['age_min'] ) ) {
			$where[]  = 'TIMESTAMPDIFF(YEAR, p.dob, CURDATE()) >= %d';
			$values[] = (int) $filters['age_min'];
		}

		if ( ! empty( $filters['age_max'] ) ) {
			$where[]  = 'TIMESTAMPDIFF(YEAR, p.dob, CURDATE()) <= %d';
			$values[] = (int) $filters['age_max'];
		}

		if ( ! empty( $filters['location_lat'] ) && ! empty( $filters['location_lng'] ) && ! empty( $filters['radius_km'] ) ) {
			$lat      = (float) $filters['location_lat'];
			$lng      = (float) $filters['location_lng'];
			$rad      = (float) $filters['radius_km'];
			$where[]  = '( 6371 * acos( cos( radians(%f) ) * cos( radians( p.location_lat ) ) * cos( radians( p.location_lng ) - radians(%f) ) + sin( radians(%f) ) * sin( radians( p.location_lat ) ) ) ) <= %f';
			$values[] = $lat;
			$values[] = $lng;
			$values[] = $lat;
			$values[] = $rad;
		}

		if ( ! empty( $filters['relationship_goal'] ) ) {
			$where[]  = 'p.relationship_goal = %s';
			$values[] = $filters['relationship_goal'];
		}

		if ( isset( $filters['has_children'] ) && '' !== $filters['has_children'] ) {
			$where[]  = 'p.has_children = %d';
			$values[] = (int) $filters['has_children'];
		}

		if ( isset( $filters['wants_children'] ) && '' !== $filters['wants_children'] ) {
			$where[]  = 'p.wants_children = %d';
			$values[] = (int) $filters['wants_children'];
		}

		if ( ! empty( $filters['smoking'] ) ) {
			$where[]  = 'p.smoking = %s';
			$values[] = $filters['smoking'];
		}

		if ( ! empty( $filters['drinking'] ) ) {
			$where[]  = 'p.drinking = %s';
			$values[] = $filters['drinking'];
		}

		if ( ! empty( $filters['religion'] ) ) {
			$where[]  = 'p.religion = %s';
			$values[] = $filters['religion'];
		}

		if ( ! empty( $filters['ethnicity'] ) ) {
			$where[]  = 'p.ethnicity = %s';
			$values[] = $filters['ethnicity'];
		}

		if ( ! empty( $filters['search'] ) ) {
			$where[]  = '(p.display_name LIKE %s OR p.bio LIKE %s)';
			$values[] = '%' . $this->wpdb->esc_like( $filters['search'] ) . '%';
			$values[] = '%' . $this->wpdb->esc_like( $filters['search'] ) . '%';
		}

		$blocked_subquery    = "SELECT target_id FROM {$this->table_interactions} WHERE type = 'block' AND ( user_id = %d OR target_id = %d )";
		$blocked_subquery2   = "SELECT user_id FROM {$this->table_interactions} WHERE type = 'block' AND ( user_id = %d OR target_id = %d )";
		$interacted_subquery = "SELECT target_id FROM {$this->table_interactions} WHERE user_id = %d AND type IN ('like', 'pass')";

		$current_user_id = get_current_user_id();
		if ( $current_user_id ) {
			$where[]  = "p.user_id NOT IN ( {$blocked_subquery} )";
			$values[] = $current_user_id;
			$values[] = $current_user_id;

			$where[]  = "p.user_id NOT IN ( {$blocked_subquery2} )";
			$values[] = $current_user_id;
			$values[] = $current_user_id;

			$where[]  = "p.user_id NOT IN ( {$interacted_subquery} )";
			$values[] = $current_user_id;

			$where[]  = 'p.user_id != %d';
			$values[] = $current_user_id;
		}

		$where_sql = implode( ' AND ', $where );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		if ( ! empty( $values ) ) {
			return (int) $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT COUNT(*) FROM {$this->table_profiles} p LEFT JOIN {$this->wpdb->users} u ON p.user_id = u.ID WHERE {$where_sql}", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
					...$values
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return (int) $this->wpdb->get_var(
			"SELECT COUNT(*) FROM {$this->table_profiles} p WHERE {$where_sql}"
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// ═══════════════════════════════════════════════════════════════.
	// PHOTO QUERIES.
	// ═══════════════════════════════════════════════════════════════.

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Get all photos for a user.
	 *
	 * @param int $user_id User id.
	 */
	public function get_profile_photos( int $user_id ): array {
		return $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_photos} WHERE user_id = %d ORDER BY is_primary DESC, photo_id ASC",
				$user_id
			),
			ARRAY_A
		) ?: array();
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Get the best (primary-first) photo for each user in one query.
	 *
	 * @return array<string,array> Map of user_id => photo row.
	 * @param array $user_ids * @return array<string,array> Map of user_id => photo row.
	 */
	public function get_primary_photos( array $user_ids ): array {
		$ids = array_values( array_filter( array_map( 'absint', $user_ids ) ) );
		if ( empty( $ids ) ) {
			return array();
		}

		$placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$rows = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT user_id, photo_id, photo_url, verification_status, is_primary
                FROM {$this->table_photos}
                WHERE user_id IN ( {$placeholders} )
                ORDER BY is_primary DESC, photo_id ASC", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
				...$ids
			),
			ARRAY_A
		) ?: array();
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

		$map = array();
		foreach ( $rows as $row ) {
			$uid = (int) $row['user_id'];
			if ( ! isset( $map[ $uid ] ) ) {
				$map[ $uid ] = $row;
			}
		}

		return $map;
	}

	/**
	 * Save a photo for a user.
	 *
	 * @param int    $user_id User id.
	 * @param string $url Url.
	 * @param bool   $is_primary Is primary.
	 */
	public function save_photo( int $user_id, string $url, bool $is_primary = false ): int {
		$this->wpdb->insert(
			$this->table_photos,
			array(
				'user_id'    => $user_id,
				'photo_url'  => esc_url_raw( $url ),
				'is_primary' => $is_primary ? 1 : 0,
				'created_at' => current_time( 'mysql', true ),
			),
			array( '%d', '%s', '%d', '%s' )
		);

		$photo_id = (int) $this->wpdb->insert_id;

		if ( $is_primary && $photo_id ) {
			$this->set_primary_photo( $photo_id, $user_id );
		}

		return $photo_id;
	}

	/**
	 * Delete a photo.
	 *
	 * @param int $photo_id Photo id.
	 * @param int $user_id User id.
	 */
	public function delete_photo( int $photo_id, int $user_id ): bool {
		$result = (bool) $this->wpdb->delete(
			$this->table_photos,
			array(
				'photo_id' => $photo_id,
				'user_id'  => $user_id,
			),
			array( '%d', '%d' )
		);

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		if ( $result ) {
			// If the deleted photo was primary, set another as primary.
			$remaining = $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT photo_id FROM {$this->table_photos} WHERE user_id = %d ORDER BY photo_id ASC LIMIT 1",
					$user_id
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
			if ( $remaining ) {
				$this->wpdb->update(
					$this->table_photos,
					array( 'is_primary' => 1 ),
					array( 'photo_id' => (int) $remaining ),
					array( '%d' ),
					array( '%d' )
				);
			}
		}

		return $result;
	}

	/**
	 * Set a photo as the primary photo (unsets others first).
	 *
	 * @param int $photo_id Photo id.
	 * @param int $user_id User id.
	 */
	public function set_primary_photo( int $photo_id, int $user_id ): bool {
		// Unset all primary for this user.
		$this->wpdb->update(
			$this->table_photos,
			array( 'is_primary' => 0 ),
			array( 'user_id' => $user_id ),
			array( '%d' ),
			array( '%d' )
		);

		// Set the chosen one.
		return (bool) $this->wpdb->update(
			$this->table_photos,
			array( 'is_primary' => 1 ),
			array(
				'photo_id' => $photo_id,
				'user_id'  => $user_id,
			),
			array( '%d' ),
			array( '%d', '%d' )
		);
	}

	// ═══════════════════════════════════════════════════════════════.
	// PRESENCE / ONLINE STATUS.
	// ═══════════════════════════════════════════════════════════════.

	/**
	 * Touch a user's last_active timestamp (throttled to once per 5 minutes).
	 *
	 * @param int $user_id User id.
	 */
	public function touch_last_active( int $user_id ): void {
		if ( ! $user_id ) {
			return;
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$this->wpdb->query(
			$this->wpdb->prepare(
				"UPDATE {$this->table_profiles}
            SET last_active = %s
            WHERE user_id = %d AND ( last_active IS NULL OR last_active < %s )",
				current_time( 'mysql', true ),
				$user_id,
				gmdate( 'Y-m-d H:i:s', time() - 5 * MINUTE_IN_SECONDS )
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// ═══════════════════════════════════════════════════════════════.
	// INTEREST QUERIES.
	// ═══════════════════════════════════════════════════════════════.

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Get all interests for a user.
	 *
	 * @param int $user_id User id.
	 */
	public function get_interests( int $user_id ): array {
		return $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_interests} WHERE user_id = %d ORDER BY category ASC, interest_id ASC",
				$user_id
			),
			ARRAY_A
		) ?: array();
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Save interests for a user (replaces all existing).
	 *
	 * @param int   $user_id User id.
	 * @param array $categories_and_values Categories and values.
	 */
	public function save_interests( int $user_id, array $categories_and_values ): bool {
		// Delete existing.
		$this->wpdb->delete(
			$this->table_interests,
			array( 'user_id' => $user_id ),
			array( '%d' )
		);

		if ( empty( $categories_and_values ) ) {
			return true;
		}

		$inserted = 0;
		foreach ( $categories_and_values as $category => $values ) {
			$cat = sanitize_text_field( $category );
			if ( is_array( $values ) ) {
				foreach ( $values as $value ) {
					$this->wpdb->insert(
						$this->table_interests,
						array(
							'user_id'  => $user_id,
							'category' => $cat,
							'value'    => sanitize_text_field( $value ),
						),
						array( '%d', '%s', '%s' )
					);
					if ( $this->wpdb->insert_id ) {
						++$inserted;
					}
				}
			} else {
				$this->wpdb->insert(
					$this->table_interests,
					array(
						'user_id'  => $user_id,
						'category' => $cat,
						'value'    => sanitize_text_field( $values ),
					),
					array( '%d', '%s', '%s' )
				);
				if ( $this->wpdb->insert_id ) {
					++$inserted;
				}
			}
		}

		return $inserted > 0;
	}

	// ═══════════════════════════════════════════════════════════════.
	// MATCH QUERIES.
	// ═══════════════════════════════════════════════════════════════.

	/**
	 * Create a match between two users. Returns match_id or null on duplicate.
	 *
	 * @param int   $user1_id User1 id.
	 * @param int   $user2_id User2 id.
	 * @param float $score Score.
	 */
	public function create_match( int $user1_id, int $user2_id, float $score ): ?int {
		// Normalize ordering to avoid duplicate pairs.
		$u1 = min( $user1_id, $user2_id );
		$u2 = max( $user1_id, $user2_id );

		$result = $this->wpdb->insert(
			$this->table_matches,
			array(
				'user1_id'   => $u1,
				'user2_id'   => $u2,
				'score'      => $score,
				'status'     => 'pending',
				'matched_at' => current_time( 'mysql', true ),
			),
			array( '%d', '%d', '%f', '%s', '%s' )
		);

		if ( false === $result ) {
			return null;
		}

		return (int) $this->wpdb->insert_id;
	}

	/**
	 * Update match status.
	 *
	 * @param int    $match_id Match id.
	 * @param string $status Status.
	 */
	public function update_match( int $match_id, string $status ): bool {
		return (bool) $this->wpdb->update(
			$this->table_matches,
			array( 'status' => $status ),
			array( 'match_id' => $match_id ),
			array( '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Get matches for a user with profile and photo data.
	 *
	 * @param int    $user_id User id.
	 * @param string $status Status.
	 * @param int    $limit Limit.
	 */
	public function get_matches( int $user_id, string $status = '', int $limit = 20 ): array {
		$where  = '(m.user1_id = %d OR m.user2_id = %d)';
		$values = array( $user_id, $user_id );

		if ( $status ) {
			$where   .= ' AND m.status = %s';
			$values[] = $status;
		}

		$values[] = $limit;

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return $this->wpdb->get_results(
			$this->wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
				"SELECT m.*,
                    CASE WHEN m.user1_id = %d THEN m.user2_id ELSE m.user1_id END AS other_user_id,
                    CASE WHEN m.user1_id = %d THEN u2.display_name ELSE u1.display_name END AS other_user_name,
                    p.bio AS other_bio,
                    ph.photo_url AS other_photo_url
                FROM {$this->table_matches} m
                LEFT JOIN {$this->wpdb->users} u1 ON m.user1_id = u1.ID
                LEFT JOIN {$this->wpdb->users} u2 ON m.user2_id = u2.ID
                LEFT JOIN {$this->table_profiles} p ON ( CASE WHEN m.user1_id = %d THEN m.user2_id ELSE m.user1_id END ) = p.user_id
                LEFT JOIN {$this->table_photos} ph ON ( CASE WHEN m.user1_id = %d THEN m.user2_id ELSE m.user1_id END ) = ph.user_id AND ph.is_primary = 1
                WHERE {$where}
                ORDER BY m.score DESC, m.matched_at DESC
                LIMIT %d",
				...array_merge( array( $user_id, $user_id, $user_id, $user_id ), $values )
			),
			ARRAY_A
		) ?: array();
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Get a match between two users.
	 *
	 * @param int $user1_id User1 id.
	 * @param int $user2_id User2 id.
	 */
	public function get_match( int $user1_id, int $user2_id ): ?array {
		$u1 = min( $user1_id, $user2_id );
		$u2 = max( $user1_id, $user2_id );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_matches} WHERE user1_id = %d AND user2_id = %d LIMIT 1",
				$u1,
				$u2
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return $row ?: null;
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Get a match row by its ID.
	 *
	 * @param int $match_id Match id.
	 */
	public function get_match_by_id( int $match_id ): ?array {
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_matches} WHERE match_id = %d LIMIT 1",
				$match_id
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return $row ?: null;
	}

	/**
	 * Delete stale matches: pending ones older than $pending_days and
	 * rejected ones older than $rejected_days. matched_at is stored in
	 * UTC, so it is compared against UTC_TIMESTAMP(). Returns the number
	 * of rows removed.
	 *
	 * @param int $pending_days Pending days.
	 * @param int $rejected_days Rejected days.
	 */
	public function cleanup_stale_matches( int $pending_days = 90, int $rejected_days = 30 ): int {
		$pending_days  = max( 1, absint( $pending_days ) );
		$rejected_days = max( 1, absint( $rejected_days ) );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$this->wpdb->query(
			$this->wpdb->prepare(
				"DELETE FROM {$this->table_matches}
                WHERE ( status = 'pending' AND matched_at < UTC_TIMESTAMP() - INTERVAL %d DAY )
                   OR ( status = 'rejected' AND matched_at < UTC_TIMESTAMP() - INTERVAL %d DAY )",
				$pending_days,
				$rejected_days
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

		return (int) $this->wpdb->rows_affected;
	}

	// ═══════════════════════════════════════════════════════════════.
	// INTERACTION QUERIES.
	// ═══════════════════════════════════════════════════════════════.

	/**
	 * Log an interaction (like, super_like, pass, block, report).
	 *
	 * @param int     $user_id User id.
	 * @param int     $target_id Target id.
	 * @param string  $type Type.
	 * @param ?string $reason Reason.
	 */
	public function log_interaction( int $user_id, int $target_id, string $type, ?string $reason = null ): int {
		$this->wpdb->insert(
			$this->table_interactions,
			array(
				'user_id'       => $user_id,
				'target_id'     => $target_id,
				'type'          => sanitize_text_field( $type ),
				'report_reason' => $reason ? sanitize_textarea_field( $reason ) : null,
				'created_at'    => current_time( 'mysql', true ),
			),
			array( '%d', '%d', '%s', '%s', '%s' )
		);

		return (int) $this->wpdb->insert_id;
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Get the latest interaction between two users.
	 *
	 * @param int $user_id User id.
	 * @param int $target_id Target id.
	 */
	public function get_interaction( int $user_id, int $target_id ): ?array {
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_interactions} WHERE user_id = %d AND target_id = %d ORDER BY created_at DESC LIMIT 1",
				$user_id,
				$target_id
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return $row ?: null;
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Count how many distinct reporters have flagged each user.
	 * Surfaces a trust signal for admin report moderation: a target that keeps
	 * being reported by different users is more likely a repeat offender.
	 *
	 * @return array<int,int> Map of target user ID => distinct reporter count.
	 */
	public function get_report_counts(): array {
		$rows = $this->wpdb->get_results(
			"SELECT target_id, COUNT(DISTINCT user_id) AS reporter_count
            FROM {$this->table_interactions}
            WHERE type = 'report'
            GROUP BY target_id",
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

		$counts = array();
		foreach ( (array) $rows as $row ) {
			$counts[ (int) $row['target_id'] ] = (int) $row['reporter_count'];
		}
		return $counts;
	}

	/**
	 * Get like/super-like interactions sent to or received from other users.
	 *
	 * @return array
	 * @param int    $user_id Current user.
	 * @param string $direction 'sent' (likes I gave) or 'received' (likes I got).
	 * @param int    $limit Max rows.
	 */
	public function get_like_interactions( int $user_id, string $direction, int $limit = 20 ): array {
		if ( 'sent' === $direction ) {
			$other = 'i.target_id';
			$where = 'i.user_id = %d';
		} else {
			$other = 'i.user_id';
			$where = 'i.target_id = %d';
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return $this->wpdb->get_results(
			$this->wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
				"SELECT i.*, {$other} AS other_user_id,
                    COALESCE( NULLIF( p.display_name, '' ), u.display_name ) AS other_user_name,
                    p.is_verified,
                    ph.photo_url AS other_photo_url
                FROM {$this->table_interactions} i
                LEFT JOIN {$this->wpdb->users} u ON {$other} = u.ID
                LEFT JOIN {$this->table_profiles} p ON {$other} = p.user_id
                LEFT JOIN {$this->table_photos} ph ON {$other} = ph.user_id AND ph.is_primary = 1
                WHERE {$where} AND i.type IN ( 'like', 'super_like' )
                ORDER BY i.created_at DESC
                LIMIT %d",
				$user_id,
				$limit
			),
			ARRAY_A
		) ?: array();
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Check if either user has blocked the other.
	 *
	 * @param int $user_id User id.
	 * @param int $target_id Target id.
	 */
	public function is_blocked( int $user_id, int $target_id ): bool {
		$count = (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table_interactions}
                WHERE type = 'block'
                AND ( ( user_id = %d AND target_id = %d ) OR ( user_id = %d AND target_id = %d ) )
                LIMIT 1",
				$user_id,
				$target_id,
				$target_id,
				$user_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return $count > 0;
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Get all users blocked by a user.
	 *
	 * @param int $user_id User id.
	 */
	public function get_blocked_users( int $user_id ): array {
		return $this->wpdb->get_col(
			$this->wpdb->prepare(
				"SELECT target_id FROM {$this->table_interactions} WHERE user_id = %d AND type = 'block'",
				$user_id
			)
		) ?: array();
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Remove a block placed by a user.
	 *
	 * @param int $user_id User id.
	 * @param int $target_id Target id.
	 */
	public function unblock_user( int $user_id, int $target_id ): bool {
		$deleted = $this->wpdb->delete(
			$this->table_interactions,
			array(
				'user_id'   => $user_id,
				'target_id' => $target_id,
				'type'      => 'block',
			),
			array( '%d', '%d', '%s' )
		);
		return false !== $deleted;
	}

	// ═══════════════════════════════════════════════════════════════.
	// DATE SCHEDULING QUERIES.
	// ═══════════════════════════════════════════════════════════════.

	/**
	 * Create a date schedule.
	 *
	 * @param array $data Data.
	 */
	public function create_date( array $data ): int {
		$this->wpdb->insert(
			$this->table_date_scheduling,
			array(
				'user1_id'         => absint( $data['user1_id'] ),
				'user2_id'         => absint( $data['user2_id'] ),
				'proposed_date'    => sanitize_text_field( $data['proposed_date'] ?? '' ),
				'proposed_time'    => sanitize_text_field( $data['proposed_time'] ?? '' ),
				'duration_minutes' => absint( $data['duration_minutes'] ?? 60 ),
				'location_name'    => sanitize_text_field( $data['location_name'] ?? '' ),
				'location_address' => sanitize_textarea_field( $data['location_address'] ?? '' ),
				'location_lat'     => isset( $data['location_lat'] ) ? (float) $data['location_lat'] : null,
				'location_lng'     => isset( $data['location_lng'] ) ? (float) $data['location_lng'] : null,
				'notes'            => sanitize_textarea_field( $data['notes'] ?? '' ),
				'status'           => sanitize_text_field( $data['status'] ?? 'pending' ),
				'created_by'       => absint( $data['created_by'] ),
				'created_at'       => current_time( 'mysql', true ),
				'updated_at'       => current_time( 'mysql', true ),
			),
			array( '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%f', '%f', '%s', '%s', '%d', '%s', '%s' )
		);

		return (int) $this->wpdb->insert_id;
	}

	/**
	 * Update a date schedule.
	 *
	 * @param int   $schedule_id Schedule id.
	 * @param array $data Data.
	 */
	public function update_date( int $schedule_id, array $data ): bool {
		$update = array( 'updated_at' => current_time( 'mysql', true ) );
		$format = array( '%s' );

		$allowed = array( 'proposed_date', 'proposed_time', 'duration_minutes', 'location_name', 'location_address', 'location_lat', 'location_lng', 'notes', 'status' );
		foreach ( $allowed as $field ) {
			if ( array_key_exists( $field, $data ) ) {
				$update[ $field ] = $data[ $field ];
				if ( 'duration_minutes' === $field ) {
					$format[] = '%d';
				} elseif ( in_array( $field, array( 'location_lat', 'location_lng' ), true ) ) {
					$format[] = '%f';
				} else {
					$format[] = '%s';
				}
			}
		}

		return (bool) $this->wpdb->update(
			$this->table_date_scheduling,
			$update,
			array( 'schedule_id' => $schedule_id ),
			$format,
			array( '%d' )
		);
	}

	/**
	 * Get dates for a user.
	 *
	 * @param int    $user_id User id.
	 * @param string $status Status.
	 * @param int    $limit Limit.
	 */
	public function get_user_dates( int $user_id, string $status = '', int $limit = 20 ): array {
		$where  = '(user1_id = %d OR user2_id = %d)';
		$values = array( $user_id, $user_id );

		switch ( $status ) {
			case 'upcoming':
				$where .= " AND d.status IN ( 'pending', 'accepted', 'reschedule' ) AND ( d.proposed_date > CURDATE() OR ( d.proposed_date = CURDATE() AND d.proposed_time >= CURTIME() ) )";
				break;
			case 'past':
				$where .= ' AND ( d.proposed_date < CURDATE() OR ( d.proposed_date = CURDATE() AND d.proposed_time < CURTIME() ) )';
				break;
			case 'cancelled':
				$where .= " AND d.status = 'declined'";
				break;
			case '':
				break;
			default:
				$where   .= ' AND d.status = %s';
				$values[] = $status;
		}

		$values[] = $limit;

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return $this->wpdb->get_results(
			$this->wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
				"SELECT d.*,
                    CASE WHEN d.user1_id = %d THEN d.user2_id ELSE d.user1_id END AS other_user_id,
                    CASE WHEN d.user1_id = %d THEN u2.display_name ELSE u1.display_name END AS other_user_name
                FROM {$this->table_date_scheduling} d
                LEFT JOIN {$this->wpdb->users} u1 ON d.user1_id = u1.ID
                LEFT JOIN {$this->wpdb->users} u2 ON d.user2_id = u2.ID
                WHERE {$where}
                ORDER BY d.proposed_date DESC, d.proposed_time DESC
                LIMIT %d",
				...array_merge( array( $user_id, $user_id ), $values )
			),
			ARRAY_A
		) ?: array();
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Get a single date schedule.
	 *
	 * @param int $schedule_id Schedule id.
	 */
	public function get_date( int $schedule_id ): ?array {
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_date_scheduling} WHERE schedule_id = %d LIMIT 1",
				$schedule_id
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return $row ?: null;
	}

	/**
	 * Confirmed dates starting within the next $lead_minutes.
	 *
	 * @return array<int,array>
	 * @param int $lead_minutes Reminder lead window.
	 */
	public function get_upcoming_dates( int $lead_minutes = 1440 ): array {
		$from = current_time( 'mysql' );
		$to   = gmdate( 'Y-m-d H:i:s', time() + ( get_option( 'gmt_offset', 0 ) * HOUR_IN_SECONDS ) + ( max( 1, $lead_minutes ) * MINUTE_IN_SECONDS ) );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_date_scheduling}
                 WHERE status = 'confirmed'
                   AND proposed_date IS NOT NULL
                   AND TIMESTAMP(proposed_date, proposed_time) BETWEEN %s AND %s
                 ORDER BY proposed_date ASC, proposed_time ASC",
				$from,
				$to
			),
			ARRAY_A
		) ?: array();
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// ═══════════════════════════════════════════════════════════════.
	// CONVERSATION QUERIES.
	// ═══════════════════════════════════════════════════════════════.

	/**
	 * Create a conversation (insert or ignore, returns ID).
	 *
	 * @param int $user1_id User1 id.
	 * @param int $user2_id User2 id.
	 */
	public function create_conversation( int $user1_id, int $user2_id ): int {
		$u1 = min( $user1_id, $user2_id );
		$u2 = max( $user1_id, $user2_id );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$this->wpdb->query(
			$this->wpdb->prepare(
				"INSERT IGNORE INTO {$this->table_conversations} (user1_id, user2_id, created_at) VALUES (%d, %d, %s)",
				$u1,
				$u2,
				current_time( 'mysql', true )
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT conversation_id FROM {$this->table_conversations} WHERE user1_id = %d AND user2_id = %d LIMIT 1",
				$u1,
				$u2
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Get conversations for a user with last message preview and unread count.
	 *
	 * @param int $user_id User id.
	 * @param int $limit Limit.
	 */
	public function get_conversations( int $user_id, int $limit = 50 ): array {
		return $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT c.*,
                    CASE WHEN c.user1_id = %d THEN c.user2_id ELSE c.user1_id END AS other_user_id,
                    CASE WHEN c.user1_id = %d
                        THEN COALESCE( NULLIF( p2.display_name, '' ), u2.display_name )
                        ELSE COALESCE( NULLIF( p1.display_name, '' ), u1.display_name )
                    END AS other_user_name,
                    ( SELECT message FROM {$this->table_messages} WHERE conversation_id = c.conversation_id ORDER BY created_at DESC LIMIT 1 ) AS last_message,
                    ( SELECT COUNT(*) FROM {$this->table_messages} WHERE conversation_id = c.conversation_id AND is_read = 0 AND sender_id != %d ) AS unread_count
                FROM {$this->table_conversations} c
                LEFT JOIN {$this->wpdb->users} u1 ON c.user1_id = u1.ID
                LEFT JOIN {$this->wpdb->users} u2 ON c.user2_id = u2.ID
                LEFT JOIN {$this->table_profiles} p1 ON c.user1_id = p1.user_id
                LEFT JOIN {$this->table_profiles} p2 ON c.user2_id = p2.user_id
                WHERE c.user1_id = %d OR c.user2_id = %d
                ORDER BY c.last_message_at DESC, c.created_at DESC
                LIMIT %d",
				$user_id,
				$user_id,
				$user_id,
				$user_id,
				$user_id,
				$limit
			),
			ARRAY_A
		) ?: array();
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// ═══════════════════════════════════════════════════════════════.
	// MESSAGE QUERIES.
	// ═══════════════════════════════════════════════════════════════.

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Get a conversation row by ID.
	 *
	 * @param int $conversation_id Conversation id.
	 */
	public function get_conversation( int $conversation_id ): ?array {
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_conversations} WHERE conversation_id = %d LIMIT 1",
				$conversation_id
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return $row ?: null;
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Check whether a user is a participant in a conversation.
	 *
	 * @param int $conversation_id Conversation id.
	 * @param int $user_id User id.
	 */
	public function is_conversation_participant( int $conversation_id, int $user_id ): bool {
		$count = (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table_conversations}
                WHERE conversation_id = %d AND ( user1_id = %d OR user2_id = %d )",
				$conversation_id,
				$user_id,
				$user_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return $count > 0;
	}

	/**
	 * Send a message in a conversation.
	 *
	 * @param int    $conversation_id Conversation id.
	 * @param int    $sender_id Sender id.
	 * @param string $message Message.
	 */
	public function send_message( int $conversation_id, int $sender_id, string $message ): int {
		$this->wpdb->insert(
			$this->table_messages,
			array(
				'conversation_id' => $conversation_id,
				'sender_id'       => $sender_id,
				'message'         => self::sanitize_rich( $message ),
				'is_read'         => 0,
				'created_at'      => current_time( 'mysql', true ),
			),
			array( '%d', '%d', '%s', '%d', '%s' )
		);

		$message_id = (int) $this->wpdb->insert_id;

		// Update last_message_at on conversation.
		if ( $message_id ) {
			$this->wpdb->update(
				$this->table_conversations,
				array( 'last_message_at' => current_time( 'mysql', true ) ),
				array( 'conversation_id' => $conversation_id ),
				array( '%s' ),
				array( '%d' )
			);
		}

		return $message_id;
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Get messages for a conversation.
	 *
	 * @param int $conversation_id Conversation id.
	 * @param int $limit Limit.
	 */
	public function get_messages( int $conversation_id, int $limit = 100 ): array {
		return $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT m.*, u.display_name AS sender_name
                FROM {$this->table_messages} m
                LEFT JOIN {$this->wpdb->users} u ON m.sender_id = u.ID
                WHERE m.conversation_id = %d
                ORDER BY m.created_at ASC
                LIMIT %d",
				$conversation_id,
				$limit
			),
			ARRAY_A
		) ?: array();
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Mark all messages in a conversation as read for a user.
	 * Marks only messages the user RECEIVED (sender_id != user_id).
	 *
	 * @param int $conversation_id Conversation id.
	 * @param int $user_id User id.
	 */
	public function mark_conversation_read( int $conversation_id, int $user_id ): bool {
		return (bool) $this->wpdb->query(
			$this->wpdb->prepare(
				"UPDATE {$this->table_messages} SET is_read = 1
                WHERE conversation_id = %d AND sender_id != %d AND is_read = 0",
				$conversation_id,
				$user_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Count all unread messages across all conversations for a user.
	 *
	 * @param int $user_id User id.
	 */
	public function count_unread_messages( int $user_id ): int {
		return (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table_messages} m
                INNER JOIN {$this->table_conversations} c ON m.conversation_id = c.conversation_id
                WHERE ( c.user1_id = %d OR c.user2_id = %d ) AND m.sender_id != %d AND m.is_read = 0",
				$user_id,
				$user_id,
				$user_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// ═══════════════════════════════════════════════════════════════.
	// NOTIFICATION QUERIES.
	// ═══════════════════════════════════════════════════════════════.

	/**
	 * Create an in-app notification for a user.
	 *
	 * @param int    $user_id User id.
	 * @param string $type Type.
	 * @param string $message Message.
	 * @param int    $actor_id Actor id.
	 * @param int    $object_id Object id.
	 * @param string $object_type Object type.
	 */
	public function add_notification( int $user_id, string $type, string $message = '', int $actor_id = 0, int $object_id = 0, string $object_type = '' ): int {
		$this->wpdb->insert(
			$this->table_notifications,
			array(
				'user_id'     => $user_id,
				'type'        => sanitize_text_field( $type ),
				'message'     => $message ? self::sanitize_rich( $message ) : null,
				'actor_id'    => $actor_id,
				'object_id'   => $object_id,
				'object_type' => sanitize_text_field( $object_type ),
				'is_read'     => 0,
				'created_at'  => current_time( 'mysql', true ),
			),
			array( '%d', '%s', '%s', '%d', '%d', '%s', '%d', '%s' )
		);

		return (int) $this->wpdb->insert_id;
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Get recent notifications for a user.
	 *
	 * @param int $user_id User id.
	 * @param int $limit Limit.
	 */
	public function get_notifications( int $user_id, int $limit = 20 ): array {
		return $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_notifications} WHERE user_id = %d ORDER BY created_at DESC LIMIT %d",
				$user_id,
				$limit
			),
			ARRAY_A
		) ?: array();
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Count unread notifications for a user.
	 *
	 * @param int $user_id User id.
	 */
	public function count_unread_notifications( int $user_id ): int {
		return (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table_notifications} WHERE user_id = %d AND is_read = 0",
				$user_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Check if a notification of a given type/object already exists for a user.
	 *
	 * @param int    $user_id User id.
	 * @param string $type Type.
	 * @param int    $object_id Object id.
	 */
	public function has_notification( int $user_id, string $type, int $object_id = 0 ): bool {
		if ( $object_id > 0 ) {
			return (bool) $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT COUNT(*) FROM {$this->table_notifications} WHERE user_id = %d AND type = %s AND object_id = %d",
					$user_id,
					$type,
					$object_id
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return (bool) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table_notifications} WHERE user_id = %d AND type = %s",
				$user_id,
				$type
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Mark a single notification as read.
	 *
	 * @param int $notification_id Notification id.
	 * @param int $user_id User id.
	 */
	public function mark_notification_read( int $notification_id, int $user_id ): bool {
		return (bool) $this->wpdb->update(
			$this->table_notifications,
			array( 'is_read' => 1 ),
			array(
				'notification_id' => $notification_id,
				'user_id'         => $user_id,
			),
			array( '%d' ),
			array( '%d', '%d' )
		);
	}

	/**
	 * Mark all notifications as read for a user.
	 *
	 * @param int $user_id User id.
	 */
	public function mark_notifications_read( int $user_id ): bool {
		return (bool) $this->wpdb->update(
			$this->table_notifications,
			array( 'is_read' => 1 ),
			array(
				'user_id' => $user_id,
				'is_read' => 0,
			),
			array( '%d' ),
			array( '%d', '%d' )
		);
	}

	// ═══════════════════════════════════════════════════════════════.
	// ACTIVITY / STATS HELPERS.
	// ═══════════════════════════════════════════════════════════════.

	/**
	 * Log an event to the global Zeko activity table (wp_zeko_user_activity).
	 *
	 * @param int    $user_id User the activity belongs to.
	 * @param string $type Activity type slug.
	 * @param string $content Human-readable description.
	 * @param int    $item_id Related love object id (match/interaction/message/schedule).
	 * @param array  $meta Extra data (stored as JSON).
	 */
	public function log_activity( int $user_id, string $type, string $content, int $item_id = 0, array $meta = array() ): int {
		$table = $this->wpdb->prefix . 'zeko_user_activity';
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		if ( $this->wpdb->get_var( $this->wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return 0;
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		}

		$this->wpdb->insert(
			$table,
			array(
				'user_id'          => $user_id,
				'activity_type'    => sanitize_text_field( $type ),
				'activity_module'  => 'love',
				'activity_item_id' => $item_id,
				'activity_content' => self::sanitize_rich( $content ),
				'activity_meta'    => empty( $meta ) ? null : wp_json_encode( $meta ),
				'activity_date'    => current_time( 'mysql' ),
				'activity_ip'      => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
				'activity_status'  => 'published',
			),
			array( '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		return (int) $this->wpdb->insert_id;
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Count accepted matches for a user.
	 *
	 * @param int $user_id User id.
	 */
	public function get_match_count( int $user_id ): int {
		return (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table_matches}
                WHERE status = 'accepted' AND ( user1_id = %d OR user2_id = %d )",
				$user_id,
				$user_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Count likes received by a user (excluding passes/blocks).
	 *
	 * @param int $user_id User id.
	 */
	public function get_received_like_count( int $user_id ): int {
		return (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table_interactions}
                WHERE target_id = %d AND type IN ('like', 'super_like')",
				$user_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Build activity-feed items (module = 'love') for a user.
	 * Item shape matches the `zeko_activity_feed_items` filter contract:
	 * action, module, item_id, message, timestamp.
	 *
	 * @param int $user_id User id.
	 * @param int $limit Limit.
	 */
	public function get_activity_items( int $user_id, int $limit = 5 ): array {
		$items = array();

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$matches = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT m.match_id, m.matched_at,
                    CASE WHEN m.user1_id = %d THEN m.user2_id ELSE m.user1_id END AS other_user_id,
                    CASE WHEN m.user1_id = %d THEN u2.display_name ELSE u1.display_name END AS other_user_name
                FROM {$this->table_matches} m
                LEFT JOIN {$this->wpdb->users} u1 ON m.user1_id = u1.ID
                LEFT JOIN {$this->wpdb->users} u2 ON m.user2_id = u2.ID
                WHERE ( m.user1_id = %d OR m.user2_id = %d ) AND m.status = 'accepted'
                ORDER BY m.matched_at DESC
                LIMIT %d",
				$user_id,
				$user_id,
				$user_id,
				$user_id,
				$limit
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		foreach ( $matches as $match ) {
			$items[] = array(
				'action'    => 'match',
				'module'    => 'love',
				'item_id'   => (int) $match['match_id'],
				'message'   => sprintf(
					/* translators: %s: other user's name */
					__( 'You matched with %s', 'zeko-love' ),
					$match['other_user_name'] ?? ''
				),
				'timestamp' => $match['matched_at'],
			);
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$likes = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT i.interaction_id, i.created_at, u.display_name AS actor_name
                FROM {$this->table_interactions} i
                LEFT JOIN {$this->wpdb->users} u ON i.user_id = u.ID
                WHERE i.target_id = %d AND i.type IN ('like', 'super_like')
                ORDER BY i.created_at DESC
                LIMIT %d",
				$user_id,
				$limit
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		foreach ( $likes as $like ) {
			$items[] = array(
				'action'    => 'like_received',
				'module'    => 'love',
				'item_id'   => (int) $like['interaction_id'],
				'message'   => sprintf(
					/* translators: %s: liker's name */
					__( '%s liked your profile', 'zeko-love' ),
					$like['actor_name'] ?? ''
				),
				'timestamp' => $like['created_at'],
			);
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$dates = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT d.schedule_id, d.proposed_date, d.created_at,
                    CASE WHEN d.user1_id = %d THEN d.user2_id ELSE d.user1_id END AS other_user_id,
                    CASE WHEN d.user1_id = %d THEN u2.display_name ELSE u1.display_name END AS other_user_name
                FROM {$this->table_date_scheduling} d
                LEFT JOIN {$this->wpdb->users} u1 ON d.user1_id = u1.ID
                LEFT JOIN {$this->wpdb->users} u2 ON d.user2_id = u2.ID
                WHERE ( d.user1_id = %d OR d.user2_id = %d )
                ORDER BY d.created_at DESC
                LIMIT %d",
				$user_id,
				$user_id,
				$user_id,
				$user_id,
				$limit
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		foreach ( $dates as $date ) {
			$items[] = array(
				'action'    => 'date_scheduled',
				'module'    => 'love',
				'item_id'   => (int) $date['schedule_id'],
				'message'   => sprintf(
					/* translators: %1$s: other user's name, %2$s: date */
					__( 'Date with %1$s on %2$s', 'zeko-love' ),
					$date['other_user_name'] ?? '',
					$date['proposed_date'] ? mysql2date( get_option( 'date_format' ), $date['proposed_date'] ) : ''
				),
				'timestamp' => $date['created_at'],
			);
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$messages = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT m.message_id, m.created_at, m.sender_id,
                    CASE WHEN c.user1_id = %d THEN c.user2_id ELSE c.user1_id END AS other_user_id,
                    ( SELECT u.display_name FROM {$this->wpdb->users} u WHERE u.ID = m.sender_id ) AS sender_name
                FROM {$this->table_messages} m
                INNER JOIN {$this->table_conversations} c ON m.conversation_id = c.conversation_id
                WHERE ( c.user1_id = %d OR c.user2_id = %d ) AND m.sender_id != %d
                ORDER BY m.created_at DESC
                LIMIT %d",
				$user_id,
				$user_id,
				$user_id,
				$user_id,
				$limit
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		foreach ( $messages as $message ) {
			$items[] = array(
				'action'    => 'message_received',
				'module'    => 'love',
				'item_id'   => (int) $message['message_id'],
				'message'   => sprintf(
					/* translators: %s: sender's name */
					__( '%s sent you a message', 'zeko-love' ),
					$message['sender_name'] ?? ''
				),
				'timestamp' => $message['created_at'],
			);
		}

		usort(
			$items,
			function ( $a, $b ) {
				return strtotime( $b['timestamp'] ) - strtotime( $a['timestamp'] );
			}
		);

		return array_slice( $items, 0, $limit );
	}

	// =====================================================================.
	// Calls & Availability.
	// =====================================================================.

	/**
	 * Get listing subscription plans.
	 *
	 * @param bool $active_only Active only.
	 */
	public function get_call_plans( bool $active_only = false ): array {
		$where = $active_only ? 'WHERE is_active = 1' : '';
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return $this->wpdb->get_results(
			"SELECT * FROM {$this->table_call_plans} {$where} ORDER BY price ASC, plan_id ASC",
			ARRAY_A
		) ?: array();
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Call plan.
	 *
	 * @param int $plan_id Plan id.
	 */
	public function get_call_plan( int $plan_id ): ?array {
		if ( $plan_id <= 0 ) {
			return null;
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		}
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$this->table_call_plans} WHERE plan_id = %d LIMIT 1", $plan_id ),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return $row ?: null;
	}

	/**
	 * Save call plan.
	 *
	 * @param array $data Data.
	 * @param int   $plan_id Plan id.
	 */
	public function save_call_plan( array $data, int $plan_id = 0 ): int {
		$save_data = array(
			'name'              => sanitize_text_field( $data['name'] ?? '' ),
			'price'             => isset( $data['price'] ) ? number_format( (float) $data['price'], 2, '.', '' ) : '0.00',
			'duration_days'     => absint( $data['duration_days'] ?? 30 ),
			'max_usage_minutes' => absint( $data['max_usage_minutes'] ?? 0 ),
			'max_listings'      => absint( $data['max_listings'] ?? 1 ),
			'features'          => sanitize_textarea_field( $data['features'] ?? '' ),
			'is_active'         => ! empty( $data['is_active'] ) ? 1 : 0,
		);

		if ( $plan_id > 0 ) {
			$this->wpdb->update( $this->table_call_plans, $save_data, array( 'plan_id' => $plan_id ) );
			return $plan_id;
		}

		$save_data['created_at'] = current_time( 'mysql' );
		$this->wpdb->insert( $this->table_call_plans, $save_data );
		return (int) $this->wpdb->insert_id;
	}

	/**
	 * Delete call plan.
	 *
	 * @param int $plan_id Plan id.
	 */
	public function delete_call_plan( int $plan_id ): bool {
		return (bool) $this->wpdb->delete( $this->table_call_plans, array( 'plan_id' => $plan_id ), array( '%d' ) );
	}

	/**
	 * Search call listings with filters.
	 *
	 * @return array
	 * @param array $args status|user_id|call_type|search|per_page|page|orderby|order.
	 */
	public function get_call_listings( array $args = array() ): array {
		$per_page = max( 1, absint( $args['per_page'] ?? 12 ) );
		$page     = max( 1, absint( $args['page'] ?? 1 ) );
		$offset   = ( $page - 1 ) * $per_page;

		$where  = array( '1=1' );
		$values = array();

		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'status = %s';
			$values[] = $args['status'];
		}
		if ( ! empty( $args['user_id'] ) ) {
			$where[]  = 'user_id = %d';
			$values[] = (int) $args['user_id'];
		}
		if ( ! empty( $args['call_type'] ) ) {
			$where[]  = 'call_type = %s';
			$values[] = $args['call_type'];
		}
		if ( ! empty( $args['search'] ) ) {
			$where[]  = '(title LIKE %s OR description LIKE %s)';
			$like     = '%' . $this->wpdb->esc_like( $args['search'] ) . '%';
			$values[] = $like;
			$values[] = $like;
		}

		$orderby = array(
			'created_at' => 'created_at DESC',
			'price'      => 'price_per_minute ASC',
			'views'      => 'views DESC',
			'usage'      => 'usage_minutes DESC',
		);
		$order   = $orderby[ $args['orderby'] ?? 'created_at' ] ?? 'created_at DESC';

		$values[] = $per_page;
		$values[] = $offset;

		$sql = "SELECT * FROM {$this->table_call_listings} WHERE " . implode( ' AND ', $where )
			. " ORDER BY {$order} LIMIT %d OFFSET %d";

		return $this->wpdb->get_results( $this->wpdb->prepare( $sql, $values ), ARRAY_A ) ?: array(); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Count call listings.
	 *
	 * @param array $args Args.
	 */
	public function count_call_listings( array $args = array() ): int {
		$where  = array( '1=1' );
		$values = array();

		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'status = %s';
			$values[] = $args['status'];
		}
		if ( ! empty( $args['user_id'] ) ) {
			$where[]  = 'user_id = %d';
			$values[] = (int) $args['user_id'];
		}

		$sql = "SELECT COUNT(*) FROM {$this->table_call_listings} WHERE " . implode( ' AND ', $where );
		return (int) ( $values ? $this->wpdb->get_var( $this->wpdb->prepare( $sql, $values ) ) : $this->wpdb->get_var( $sql ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Call listing.
	 *
	 * @param int $listing_id Listing id.
	 */
	public function get_call_listing( int $listing_id ): ?array {
		if ( $listing_id <= 0 ) {
			return null;
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		}
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$this->table_call_listings} WHERE listing_id = %d LIMIT 1", $listing_id ),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return $row ?: null;
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * User call listings.
	 *
	 * @param int $user_id User id.
	 */
	public function get_user_call_listings( int $user_id ): array {
		return $this->wpdb->get_results(
			$this->wpdb->prepare( "SELECT * FROM {$this->table_call_listings} WHERE user_id = %d ORDER BY created_at DESC", $user_id ),
			ARRAY_A
		) ?: array();
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Save call listing.
	 *
	 * @param array $data Data.
	 * @param int   $listing_id Listing id.
	 */
	public function save_call_listing( array $data, int $listing_id = 0 ): int {
		$save_data = array(
			'user_id'          => absint( $data['user_id'] ?? get_current_user_id() ),
			'title'            => sanitize_text_field( $data['title'] ?? '' ),
			'description'      => self::sanitize_rich( $data['description'] ?? '' ),
			'call_type'        => in_array( $data['call_type'] ?? '', array( 'video', 'voice' ), true ) ? $data['call_type'] : 'video',
			'provider'         => in_array( $data['provider'] ?? '', array( 'google_meet', 'whatsapp', 'other' ), true ) ? $data['provider'] : 'google_meet',
			'price_per_minute' => isset( $data['price_per_minute'] ) ? number_format( max( 0.01, (float) $data['price_per_minute'] ), 2, '.', '' ) : '0.01',
			'min_duration'     => max( 1, absint( $data['min_duration'] ?? 15 ) ),
			'max_duration'     => max( 1, absint( $data['max_duration'] ?? 60 ) ),
			'currency'         => sanitize_text_field( $data['currency'] ?? 'USD' ),
			'cover_photo_url'  => esc_url_raw( $data['cover_photo_url'] ?? '' ),
			'intro_video_url'  => esc_url_raw( $data['intro_video_url'] ?? '' ),
			'youtube_url'      => esc_url_raw( $data['youtube_url'] ?? '' ),
			'vimeo_url'        => esc_url_raw( $data['vimeo_url'] ?? '' ),
			'whatsapp_phone'   => sanitize_text_field( $data['whatsapp_phone'] ?? '' ),
			'join_url'         => esc_url_raw( $data['join_url'] ?? '' ),
			'status'           => in_array( $data['status'] ?? '', array( 'draft', 'active', 'paused', 'expired' ), true ) ? $data['status'] : 'draft',
			'updated_at'       => current_time( 'mysql' ),
		);

		if ( isset( $data['plan_id'] ) ) {
			$save_data['plan_id'] = absint( $data['plan_id'] );
		}
		if ( isset( $data['plan_ends_at'] ) ) {
			$save_data['plan_ends_at'] = $data['plan_ends_at'];
		}
		if ( isset( $data['usage_minutes'] ) ) {
			$save_data['usage_minutes'] = absint( $data['usage_minutes'] );
		}

		if ( $listing_id > 0 ) {
			$this->wpdb->update( $this->table_call_listings, $save_data, array( 'listing_id' => $listing_id ), null, array( '%d' ) );
			return $listing_id;
		}

		$save_data['created_at'] = current_time( 'mysql' );
		$save_data['updated_at'] = current_time( 'mysql' );
		$this->wpdb->insert( $this->table_call_listings, $save_data );
		return (int) $this->wpdb->insert_id;
	}

	/**
	 * Delete call listing.
	 *
	 * @param int $listing_id Listing id.
	 */
	public function delete_call_listing( int $listing_id ): bool {
		$this->wpdb->delete( $this->table_call_slots, array( 'listing_id' => $listing_id ), array( '%d' ) );
		return (bool) $this->wpdb->delete( $this->table_call_listings, array( 'listing_id' => $listing_id ), array( '%d' ) );
	}

	/**
	 * Update call listing status.
	 *
	 * @param int    $listing_id Listing id.
	 * @param string $status Status.
	 */
	public function update_call_listing_status( int $listing_id, string $status ): bool {
		return (bool) $this->wpdb->update(
			$this->table_call_listings,
			array( 'status' => $status ),
			array( 'listing_id' => $listing_id ),
			array( '%s' ),
			array( '%d' )
		);
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Increment listing views.
	 *
	 * @param int $listing_id Listing id.
	 */
	public function increment_listing_views( int $listing_id ): void {
		$this->wpdb->query(
			$this->wpdb->prepare(
				"UPDATE {$this->table_call_listings} SET views = views + 1 WHERE listing_id = %d",
				$listing_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Call slots.
	 *
	 * @param int $listing_id Listing id.
	 */
	public function get_call_slots( int $listing_id ): array {
		return $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_call_slots} WHERE listing_id = %d ORDER BY day_of_week ASC, start_time ASC",
				$listing_id
			),
			ARRAY_A
		) ?: array();
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Replace all weekly slots for a listing.
	 *
	 * @return bool
	 * @param int   $listing_id Listing id.
	 * @param array $slots Each: ['day_of_week'=>0-6,'start_time'=>'HH:MM','end_time'=>'HH:MM'].
	 */
	public function save_call_slots( int $listing_id, array $slots ): bool {
		$this->wpdb->delete( $this->table_call_slots, array( 'listing_id' => $listing_id ), array( '%d' ) );

		foreach ( $slots as $slot ) {
			if ( empty( $slot['start_time'] ) || empty( $slot['end_time'] ) ) {
				continue;
			}
			$this->wpdb->insert(
				$this->table_call_slots,
				array(
					'listing_id'  => $listing_id,
					'day_of_week' => absint( $slot['day_of_week'] ?? 0 ),
					'start_time'  => sanitize_text_field( $slot['start_time'] ),
					'end_time'    => sanitize_text_field( $slot['end_time'] ),
					'is_active'   => 1,
				),
				array( '%d', '%d', '%s', '%s', '%d' )
			);
		}

		return true;
	}

	// ----- Bookings -----.

	/**
	 * Create call booking.
	 *
	 * @param array $data Data.
	 */
	public function create_call_booking( array $data ): int {
		$this->wpdb->insert(
			$this->table_call_bookings,
			array(
				'listing_id'       => absint( $data['listing_id'] ),
				'seller_id'        => absint( $data['seller_id'] ),
				'buyer_id'         => absint( $data['buyer_id'] ),
				'start_at'         => sanitize_text_field( $data['start_at'] ),
				'duration_minutes' => absint( $data['duration_minutes'] ),
				'amount'           => number_format( (float) $data['amount'], 2, '.', '' ),
				'currency'         => sanitize_text_field( $data['currency'] ?? 'USD' ),
				'status'           => sanitize_text_field( $data['status'] ?? 'pending' ),
				'tx_id'            => absint( $data['tx_id'] ?? 0 ),
				'created_at'       => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%d', '%s' )
		);
		return (int) $this->wpdb->insert_id;
	}

	/**
	 * Call booking.
	 *
	 * @param int $booking_id Booking id.
	 */
	public function get_call_booking( int $booking_id ): ?array {
		if ( $booking_id <= 0 ) {
			return null;
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		}
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$this->table_call_bookings} WHERE booking_id = %d LIMIT 1", $booking_id ),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return $row ?: null;
	}

	/**
	 * Bookings where the user is buyer (or seller).
	 *
	 * @param int    $user_id User id.
	 * @param string $role 'buyer'|'seller'.
	 * @param array  $statuses Optional status filter.
	 */
	public function get_call_bookings( int $user_id, string $role = 'buyer', array $statuses = array() ): array {
		$column = 'buyer' === $role ? 'buyer_id' : 'seller_id';
		$where  = "{$column} = %d";
		$values = array( $user_id );

		if ( ! empty( $statuses ) ) {
			$placeholders = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );
			$where       .= " AND status IN ({$placeholders})";
			$values       = array_merge( $values, array_values( $statuses ) );
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return $this->wpdb->get_results(
			$this->wpdb->prepare( "SELECT * FROM {$this->table_call_bookings} WHERE {$where} ORDER BY start_at DESC", $values ), // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
			ARRAY_A
		) ?: array();
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Confirmed call bookings starting within the next $lead_minutes,
	 * joined with their listing title + join link.
	 *
	 * @return array<int,array>
	 * @param int $lead_minutes Reminder lead window.
	 */
	public function get_upcoming_call_bookings( int $lead_minutes = 1440 ): array {
		$from = current_time( 'mysql' );
		$to   = gmdate( 'Y-m-d H:i:s', time() + ( get_option( 'gmt_offset', 0 ) * HOUR_IN_SECONDS ) + ( max( 1, $lead_minutes ) * MINUTE_IN_SECONDS ) );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT b.*, l.title AS listing_title, l.join_url
                 FROM {$this->table_call_bookings} b
                 LEFT JOIN {$this->table_call_listings} l ON l.listing_id = b.listing_id
                 WHERE b.status = 'confirmed'
                   AND b.start_at BETWEEN %s AND %s
                 ORDER BY b.start_at ASC",
				$from,
				$to
			),
			ARRAY_A
		) ?: array();
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Listing bookings.
	 *
	 * @param int   $listing_id Listing id.
	 * @param array $statuses Statuses.
	 */
	public function get_listing_bookings( int $listing_id, array $statuses = array() ): array {
		$where  = 'listing_id = %d';
		$values = array( $listing_id );

		if ( ! empty( $statuses ) ) {
			$placeholders = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );
			$where       .= " AND status IN ({$placeholders})";
			$values       = array_merge( $values, array_values( $statuses ) );
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return $this->wpdb->get_results(
			$this->wpdb->prepare( "SELECT * FROM {$this->table_call_bookings} WHERE {$where} ORDER BY start_at DESC", $values ), // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
			ARRAY_A
		) ?: array();
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Whether a requested window overlaps any existing booking on the listing.
	 *
	 * @param int    $listing_id Listing id.
	 * @param string $start_at 'Y-m-d H:i:s' (site time).
	 * @param int    $duration Minutes.
	 */
	public function call_booking_overlaps( int $listing_id, string $start_at, int $duration ): bool {
		$end_at = gmdate( 'Y-m-d H:i:s', strtotime( $start_at ) + $duration * MINUTE_IN_SECONDS );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$count = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table_call_bookings}
                 WHERE listing_id = %d AND status IN ('pending','confirmed')
                 AND start_at < %s AND DATE_ADD(start_at, INTERVAL duration_minutes MINUTE) > %s",
				$listing_id,
				$end_at,
				$start_at
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return (int) $count > 0;
	}

	/**
	 * Update call booking.
	 *
	 * @param int   $booking_id Booking id.
	 * @param array $data Data.
	 */
	public function update_call_booking( int $booking_id, array $data ): bool {
		$allowed = array( 'status', 'tx_id', 'payout_ref', 'completed_at', 'cancelled_by', 'cancel_reason' );
		$update  = array();
		foreach ( $data as $key => $value ) {
			if ( in_array( $key, $allowed, true ) ) {
				$update[ $key ] = $value;
			}
		}
		if ( empty( $update ) ) {
			return false;
		}
		return (bool) $this->wpdb->update( $this->table_call_bookings, $update, array( 'booking_id' => $booking_id ), null, array( '%d' ) );
	}

	// ----- Reviews -----.

	/**
	 * Add call review.
	 *
	 * @param array $data Data.
	 */
	public function add_call_review( array $data ): int {
		$this->wpdb->insert(
			$this->table_call_reviews,
			array(
				'booking_id'  => absint( $data['booking_id'] ),
				'reviewer_id' => absint( $data['reviewer_id'] ),
				'reviewee_id' => absint( $data['reviewee_id'] ),
				'rating'      => max( 1, min( 5, absint( $data['rating'] ?? 5 ) ) ),
				'comment'     => sanitize_textarea_field( $data['comment'] ?? '' ),
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%d', '%d', '%s', '%s' )
		);
		return (int) $this->wpdb->insert_id;
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Booking review.
	 *
	 * @param int $booking_id Booking id.
	 * @param int $reviewer_id Reviewer id.
	 */
	public function get_booking_review( int $booking_id, int $reviewer_id ): ?array {
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_call_reviews} WHERE booking_id = %d AND reviewer_id = %d LIMIT 1",
				$booking_id,
				$reviewer_id
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return $row ?: null;
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Call reviews.
	 *
	 * @param int $user_id User id.
	 */
	public function get_call_reviews( int $user_id ): array {
		return $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT r.*, b.listing_id FROM {$this->table_call_reviews} r
                 JOIN {$this->table_call_bookings} b ON b.booking_id = r.booking_id
                 WHERE r.reviewee_id = %d ORDER BY r.created_at DESC",
				$user_id
			),
			ARRAY_A
		) ?: array();
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Average rating + review count for a user's listings.
	 *
	 * @return array{avg:float,count:int}
	 * @param int $user_id Seller user id.
	 */
	public function get_call_review_stats( int $user_id ): array {
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT AVG(r.rating) AS avg_rating, COUNT(r.review_id) AS review_count
                 FROM {$this->table_call_reviews} r
                 WHERE r.reviewee_id = %d",
				$user_id
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return array(
			'avg'   => $row ? (float) $row['avg_rating'] : 0.0,
			'count' => $row ? (int) $row['review_count'] : 0,
		);
	}

	// ----- Usage metering -----.

	/**
	 * Log call usage.
	 *
	 * @param int $listing_id Listing id.
	 * @param int $user_id User id.
	 * @param int $minutes Minutes.
	 */
	public function log_call_usage( int $listing_id, int $user_id, int $minutes ): void {
		$date = current_time( 'Y-m-d' );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$exists = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT usage_id FROM {$this->table_call_usage} WHERE listing_id = %d AND billing_date = %s",
				$listing_id,
				$date
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		if ( $exists ) {
			$this->wpdb->query(
				$this->wpdb->prepare(
					"UPDATE {$this->table_call_usage} SET usage_minutes = usage_minutes + %d WHERE usage_id = %d",
					$minutes,
					(int) $exists
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		} else {
			$this->wpdb->insert(
				$this->table_call_usage,
				array(
					'listing_id'    => $listing_id,
					'user_id'       => $user_id,
					'usage_minutes' => $minutes,
					'billing_date'  => $date,
				),
				array( '%d', '%d', '%d', '%s' )
			);
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$this->wpdb->query(
			$this->wpdb->prepare(
				"UPDATE {$this->table_call_listings} SET usage_minutes = usage_minutes + %d WHERE listing_id = %d",
				$minutes,
				$listing_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Listing usage.
	 *
	 * @param int $listing_id Listing id.
	 */
	public function get_listing_usage( int $listing_id ): int {
		return (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT usage_minutes FROM {$this->table_call_listings} WHERE listing_id = %d",
				$listing_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}
}
