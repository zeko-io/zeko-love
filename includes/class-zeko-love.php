<?php
/**
 * Core singleton orchestrator for Zeko Love.
 *
 * Wires all subsystems: DB, public, admin, AJAX, ecosystem, matching, REST, emails.
 *
 * @package Zeko_Love
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Class Zeko_Love. */
final class Zeko_Love {

	/**
	 * Instance.
	 *
	 * @var ?self Instance.
	 */
	private static ?self $instance = null;

	/**
	 * Db.
	 *
	 * @var Zeko_Love_DB Db.
	 */
	private Zeko_Love_DB $db;

	/**
	 * Public.
	 *
	 * @var Zeko_Love_Public Public.
	 */
	private Zeko_Love_Public $public;

	/**
	 * Admin.
	 *
	 * @var Zeko_Love_Admin Admin.
	 */
	private Zeko_Love_Admin $admin;

	/**
	 * Ajax.
	 *
	 * @var Zeko_Love_Ajax Ajax.
	 */
	private Zeko_Love_Ajax $ajax;

	/**
	 * Ecosystem.
	 *
	 * @var Zeko_Love_Ecosystem Ecosystem.
	 */
	private Zeko_Love_Ecosystem $ecosystem;

	/**
	 * Matching.
	 *
	 * @var Zeko_Love_Matching Matching.
	 */
	private Zeko_Love_Matching $matching;

	/**
	 * Rest.
	 *
	 * @var Zeko_Love_REST Rest.
	 */
	private Zeko_Love_REST $rest;

	/**
	 * Emails.
	 *
	 * @var Zeko_Love_Emails Emails.
	 */
	private Zeko_Love_Emails $emails;

	/**
	 * Calls.
	 *
	 * @var Zeko_Love_Calls Calls.
	 */
	private Zeko_Love_Calls $calls;

	/**
	 * Calls ajax.
	 *
	 * @var Zeko_Love_Calls_Ajax Calls ajax.
	 */
	private Zeko_Love_Calls_Ajax $calls_ajax;

	/**
	 * Singleton accessor.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor — use instance().
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Load all class files.
	 */
	private function load_dependencies(): void {
		$base = ZEKO_LOVE_PLUGIN_PATH . 'includes/';

		// Core.
		require_once $base . 'db/class-zeko-love-db.php';
		require_once $base . 'class-zeko-love-pay.php';

		// Subsystems.
		require_once $base . 'public/class-zeko-love-public.php';
		require_once $base . 'admin/class-zeko-love-admin.php';
		require_once $base . 'public/class-zeko-love-ajax.php';
		require_once $base . 'class-zeko-love-ecosystem.php';
		require_once $base . 'class-zeko-love-matching.php';
		require_once $base . 'class-zeko-love-rest.php';
		require_once $base . 'class-zeko-love-emails.php';
		require_once $base . 'calls/class-zeko-love-calls.php';
		require_once $base . 'calls/class-zeko-love-calls-ajax.php';

		// Instantiate with shared DB layer.
		$this->db         = new Zeko_Love_DB();
		$this->public     = new Zeko_Love_Public( $this->db );
		$this->admin      = new Zeko_Love_Admin( $this->db );
		$this->ajax       = new Zeko_Love_Ajax( $this->db );
		$this->ecosystem  = new Zeko_Love_Ecosystem( $this->db );
		$this->matching   = new Zeko_Love_Matching( $this->db );
		$this->rest       = new Zeko_Love_REST( $this->db );
		$this->emails     = new Zeko_Love_Emails( $this->db );
		$this->calls      = new Zeko_Love_Calls( $this->db );
		$this->calls_ajax = new Zeko_Love_Calls_Ajax( $this->db, $this->calls );
	}

	/**
	 * Register core hooks.
	 */
	private function init_hooks(): void {
		add_action( 'init', 'zeko_love_maybe_create_pages' );
		add_action( 'init', array( $this, 'track_last_active' ) );

		add_action( 'wp_enqueue_scripts', array( $this->public, 'enqueue_assets' ) );
		add_action( 'admin_menu', array( $this->admin, 'add_admin_menu' ) );
		add_action( 'rest_api_init', array( $this->rest, 'register_routes' ) );

		// Ecosystem hooks (nav menus, admin bar, notification assets).
		$this->ecosystem->init_hooks();

		add_filter( 'zeko_dashboard_tabs', array( $this, 'add_dashboard_tab' ) );
		add_action( 'zeko_dashboard_tab_content_dating', array( $this, 'render_dating_tab_content' ) );
		add_filter( 'zeko_activity_feed_items', array( $this, 'append_activity_feed_items' ) );

		// Daily stale-match cleanup (scheduled on activation).
		add_action( 'zeko_love_match_cleanup', array( $this, 'cleanup_stale_matches' ) );

		// Nav items registry.
		add_filter( 'zeko_nav_items', array( $this, 'register_nav_items' ) );

		// Notification source registry.
		add_filter( 'zeko_register_notification_sources', array( $this, 'register_notification_source' ) );
	}

	/**
	 * Register Love as a notification source for the core bell.
	 *
	 * @param array $sources Sources.
	 */
	public function register_notification_source( array $sources ): array {
		$sources['love'] = array(
			'table'       => $this->db->get_table_notifications(),
			'pk_column'   => 'notification_id',
			'type_column' => 'type',
			'has_object'  => true,
			'has_message' => true,
			'icon'        => 'heart',
			'label'       => __( 'Community', 'zeko-love' ),
		);
		return $sources;
	}

	/**
	 * Cron: remove stale/unresponsive matches on the daily schedule.
	 */
	public function cleanup_stale_matches(): void {
		$this->db->cleanup_stale_matches();
	}

	/**
	 * Add the "Dating" tab to the Zeko dashboard.
	 *
	 * @return array
	 * @param array $tabs Existing tabs.
	 */
	public function add_dashboard_tab( array $tabs ): array {
		$tabs['dating'] = __( 'Dating', 'zeko-love' );
		return $tabs;
	}

	/**
	 * Register Community nav items via the core registry.
	 *
	 * @return array
	 * @param array $locations keyed by location slug.
	 */
	public function register_nav_items( array $locations ): array {
		$locations['primary'][] = array(
			'title'    => __( 'Community', 'zeko-love' ),
			'url'      => home_url( '/activity/' ),
			'order'    => 5,
			'children' => array(
				array(
					'title' => __( 'Activity Feed', 'zeko-love' ),
					'url'   => home_url( '/activity/' ),
				),
				array(
					'title' => __( 'Messages', 'zeko-love' ),
					'url'   => home_url( '/messages/' ),
				),
				array(
					'title' => __( 'Friends', 'zeko-love' ),
					'url'   => home_url( '/friends/' ),
				),
			),
		);
		return $locations;
	}

	/**
	 * Keep profiles.last_active fresh for logged-in users (throttled).
	 */
	public function track_last_active(): void {
		if ( ! is_user_logged_in() ) {
			return;
		}
		$this->db->touch_last_active( get_current_user_id() );
	}

	/**
	 * Append Zeko Love activity to the shared dashboard feed.
	 * Merges recent dating events (matches, likes received, dates, messages)
	 * into the items injected by other modules instead of wiping them.
	 *
	 * @return array
	 * @param array $items Items collected by earlier `zeko_activity_feed_items` filters.
	 */
	public function append_activity_feed_items( array $items ): array {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return $items;
		}

		$love_items = $this->db->get_activity_items( $user_id, 5 );
		if ( ! empty( $love_items ) ) {
			$items = array_merge( $items, $love_items );
		}

		return $items;
	}

	/**
	 * Render the content of the "Dating" dashboard tab.
	 */
	public function render_dating_tab_content(): void {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			echo '<p>' . esc_html__( 'Please log in to use the dating module.', 'zeko-love' ) . '</p>';
			return;
		}

		$profile = $this->db->get_profile( $user_id );
		$matches = $this->db->get_matches( $user_id, 'accepted', 5 );
		$unread  = $this->db->count_unread_messages( $user_id );

		if ( ! $profile ) {
			echo '<div class="zeko-dating-tab" style="padding:24px 0;">';
			echo '<p style="margin:0 0 12px;">' . esc_html__( 'Create your dating profile to start finding matches.', 'zeko-love' ) . '</p>';
			echo '<p style="margin:0;"><a class="btn btn-primary" style="background:#e11d48;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;display:inline-block;" href="'
				. esc_url( zeko_love_page_url( 'dating-profile' ) ) . '">' . esc_html__( 'Set up my profile', 'zeko-love' ) . '</a></p>';
			echo '</div>';
			return;
		}

		$match_total = $this->db->get_match_count( $user_id );
		$like_total  = $this->db->get_received_like_count( $user_id );

		echo '<div class="zeko-dating-tab" style="padding:8px 0;">';
		echo '<div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:20px;">';
		foreach ( array(
			'matches' => array( (string) $match_total, __( 'Matches', 'zeko-love' ) ),
			'likes'   => array( (string) $like_total, __( 'Likes', 'zeko-love' ) ),
			'unread'  => array( (string) $unread, __( 'Unread messages', 'zeko-love' ) ),
		) as $stat_key => $stat ) {
			echo '<div style="flex:1;min-width:120px;background:var(--color-surface,#fff);border:1px solid var(--color-border,#e2e8f0);border-radius:12px;padding:16px;text-align:center;">';
			echo '<div style="font-size:26px;font-weight:700;color:#e11d48;">' . esc_html( $stat[0] ) . '</div>';
			echo '<div style="font-size:13px;color:var(--color-text-secondary,#64748b);">' . esc_html( $stat[1] ) . '</div>';
			echo '</div>';
		}
		echo '</div>';

		echo '<div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;">';
		foreach ( array(
			'dating-browse'   => __( 'Browse', 'zeko-love' ),
			'dating-matches'  => __( 'My Matches', 'zeko-love' ),
			'dating-messages' => __( 'Messages', 'zeko-love' ),
			'dating-schedule' => __( 'Dates', 'zeko-love' ),
			'dating-calls'    => __( 'Calls', 'zeko-love' ),
			'dating-settings' => __( 'Settings', 'zeko-love' ),
		) as $slug => $label ) {
			echo '<a class="zeko-dating-quicklink" style="background:var(--color-surface,#fff);border:1px solid var(--color-border,#e2e8f0);padding:8px 16px;border-radius:20px;text-decoration:none;color:#e11d48;font-size:13px;font-weight:600;" href="'
				. esc_url( zeko_love_page_url( $slug ) ) . '">' . esc_html( $label ) . '</a>';
		}
		echo '</div>';

		if ( ! empty( $this->calls ) ) {
			echo $this->calls->shortcode_calls_strip( array( 'limit' => 4 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		if ( empty( $matches ) ) {
			echo '<p style="color:var(--color-text-secondary,#64748b);font-size:14px;">'
				. esc_html__( 'No accepted matches yet. Keep browsing — matches are created when you both like each other.', 'zeko-love' )
				. '</p>';
			echo '</div>';
			return;
		}

		echo '<h4 style="margin:0 0 12px;font-size:15px;color:var(--color-text,#111827);">' . esc_html__( 'Recent Matches', 'zeko-love' ) . '</h4>';
		echo '<div style="display:flex;gap:12px;flex-wrap:wrap;">';
		foreach ( $matches as $match ) {
			$avatar = ! empty( $match['other_photo_url'] )
				? $match['other_photo_url']
				: get_avatar_url( (int) $match['other_user_id'], array( 'size' => 64 ) );
			echo '<div style="width:90px;text-align:center;">';
			echo '<a href="' . esc_url( zeko_love_page_url( 'dating-messages' ) ) . '">';
			echo '<img src="' . esc_url( $avatar ) . '" alt="" style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid #e11d48;">';
			echo '</a>';
			echo '<div style="font-size:12px;margin-top:6px;color:var(--color-text,#111827);">' . esc_html( $match['other_user_name'] ?? '' ) . '</div>';
			echo '</div>';
		}
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Get the DB layer.
	 */
	public function get_db(): Zeko_Love_DB {
		return $this->db;
	}

	/**
	 * Get the public frontend handler.
	 */
	public function get_public(): Zeko_Love_Public {
		return $this->public;
	}

	/**
	 * Get the AJAX handler.
	 */
	public function get_ajax(): Zeko_Love_Ajax {
		return $this->ajax;
	}

	/**
	 * Get the matching engine.
	 */
	public function get_matching(): Zeko_Love_Matching {
		return $this->matching;
	}

	/**
	 * Get the ecosystem integration handler.
	 */
	public function get_ecosystem(): Zeko_Love_Ecosystem {
		return $this->ecosystem;
	}

	/**
	 * Get the REST API handler.
	 */
	public function get_rest(): Zeko_Love_REST {
		return $this->rest;
	}

	/**
	 * Get the email notification handler.
	 */
	public function get_emails(): Zeko_Love_Emails {
		return $this->emails;
	}

	/**
	 * Get the calls addon handler.
	 */
	public function get_calls(): Zeko_Love_Calls {
		return $this->calls;
	}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization.
	 *
	 * @throws \LogicException When an error occurs.
	 */
	public function __wakeup() {
		throw new \LogicException( 'Cannot unserialize singleton.' );
	}
}
