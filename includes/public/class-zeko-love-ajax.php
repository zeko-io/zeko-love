<?php
/**
 * File doc comment.
 *
 * @package Zeko_ZEKO_LOVE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Class Zeko_Love_Ajax. */
class Zeko_Love_Ajax {

	/**
	 * Db.
	 *
	 * @var Zeko_Love_DB Db.
	 */
	private Zeko_Love_DB $db;

	/**
	 * Sanitize rich-text (Quill) content with the narrow ecosystem
	 * allow-list; falls back to $this->sanitize_rich() when zeko-core is absent.
	 *
	 * @param mixed $html Html.
	 */
	private function sanitize_rich( $html ): string {
		if ( class_exists( 'Zeko_Core_Sanitize' ) ) {
			return Zeko_Core_Sanitize::rich_text( (string) $html );
		}
		return $this->sanitize_rich( $html );
	}

	/**
	 * Construct.
	 *
	 * @param Zeko_Love_DB $db Db.
	 */
	public function __construct( Zeko_Love_DB $db ) {
		$this->db = $db;
		$this->init_hooks();
	}

	/**
	 * Init hooks.
	 */
	private function init_hooks(): void {
		add_action( 'wp_ajax_zeko_love_save_profile', array( $this, 'handle_save_profile' ) );
		add_action( 'wp_ajax_zeko_love_get_profile', array( $this, 'handle_get_profile' ) );
		add_action( 'wp_ajax_zeko_love_search_profiles', array( $this, 'handle_search_profiles' ) );
		add_action( 'wp_ajax_zeko_love_upload_photo', array( $this, 'handle_upload_photo' ) );
		add_action( 'wp_ajax_zeko_love_delete_photo', array( $this, 'handle_delete_photo' ) );
		add_action( 'wp_ajax_zeko_love_set_primary_photo', array( $this, 'handle_set_primary_photo' ) );
		add_action( 'wp_ajax_zeko_love_save_interests', array( $this, 'handle_save_interests' ) );
		add_action( 'wp_ajax_zeko_love_like_user', array( $this, 'handle_like_user' ) );
		add_action( 'wp_ajax_zeko_love_super_like_user', array( $this, 'handle_super_like_user' ) );
		add_action( 'wp_ajax_zeko_love_pass_user', array( $this, 'handle_pass_user' ) );
		add_action( 'wp_ajax_zeko_love_accept_match', array( $this, 'handle_accept_match' ) );
		add_action( 'wp_ajax_zeko_love_get_matches', array( $this, 'handle_get_matches' ) );
		add_action( 'wp_ajax_zeko_love_get_compatibility', array( $this, 'handle_get_compatibility' ) );
		add_action( 'wp_ajax_zeko_love_get_conversations', array( $this, 'handle_get_conversations' ) );
		add_action( 'wp_ajax_zeko_love_get_thread_messages', array( $this, 'handle_get_thread_messages' ) );
		add_action( 'wp_ajax_zeko_love_get_thread', array( $this, 'handle_get_thread_messages' ) );
		add_action( 'wp_ajax_zeko_love_send_message', array( $this, 'handle_send_message' ) );
		add_action( 'wp_ajax_zeko_love_propose_date', array( $this, 'handle_propose_date' ) );
		add_action( 'wp_ajax_zeko_love_respond_to_date', array( $this, 'handle_respond_to_date' ) );
		add_action( 'wp_ajax_zeko_love_get_dates', array( $this, 'handle_get_dates' ) );
		add_action( 'wp_ajax_zeko_love_block_user', array( $this, 'handle_block_user' ) );
		add_action( 'wp_ajax_zeko_love_unblock_user', array( $this, 'handle_unblock_user' ) );
		add_action( 'wp_ajax_zeko_love_report_user', array( $this, 'handle_report_user' ) );
		add_action( 'wp_ajax_zeko_love_get_blocked_users', array( $this, 'handle_get_blocked_users' ) );
		add_action( 'wp_ajax_zeko_love_boost_profile', array( $this, 'handle_boost_profile' ) );
		add_action( 'wp_ajax_zeko_love_send_gift', array( $this, 'handle_send_gift' ) );
		add_action( 'wp_ajax_zeko_love_get_settings', array( $this, 'handle_get_settings' ) );
		add_action( 'wp_ajax_zeko_love_save_settings', array( $this, 'handle_save_settings' ) );
		add_action( 'wp_ajax_zeko_love_save_payout', array( $this, 'handle_save_payout' ) );
		add_action( 'wp_ajax_zeko_love_withdraw_funds', array( $this, 'handle_withdraw_funds' ) );
		add_action( 'wp_ajax_zeko_love_send_withdrawal_otp', array( $this, 'handle_send_withdrawal_otp' ) );
		add_action( 'wp_ajax_zeko_love_delete_profile', array( $this, 'handle_delete_profile' ) );
	}

	/**
	 * Post fields.
	 */
	private function post_fields(): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify_request() (called at top of every AJAX handler) before this helper is reached.
		if ( isset( $_POST['fields'] ) && is_array( $_POST['fields'] ) ) {
			return (array) wp_unslash( $_POST['fields'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Raw payload; each value is sanitized per-key by the calling handler.
		}
		return wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Raw payload; each value is sanitized per-key by the calling handler.
	}

	/**
	 * Verify request.
	 */
	private function verify_request(): bool {
		$nonce = $_POST['nonce'] ?? ( $_POST['_wpnonce'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce compared via wp_verify_nonce(); nonces are not unslashed/sanitized before verification.
		if ( ! wp_verify_nonce( $nonce, 'zeko_love_nonce' ) ) {
			$this->json_error( __( 'Security check failed.', 'zeko-love' ) );
			return false;
		}
		if ( ! $this->check_rate_limit() ) {
			$this->json_error( __( 'Too many requests. Please slow down.', 'zeko-love' ) );
			return false;
		}
		return true;
	}

	/**
	 * Resolve the target user id from the posted payload.
	 * Accepts 'target_id' (legacy) or 'profile_id' (front-end contract).
	 */
	private function resolve_target_id(): int {
		return absint( $_POST['target_id'] ?? ( $_POST['profile_id'] ?? 0 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify_request() (called at top of every AJAX handler).
	}

	/**
	 * Check rate limit.
	 */
	private function check_rate_limit(): bool {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return true;
		}
		$action   = sanitize_text_field( wp_unslash( $_POST['action'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify_request() (called at top of every AJAX handler) before rate limiting runs.
		$key      = 'zeko_love_ratelimit_' . $action . '_' . $user_id;
		$existing = get_transient( $key );
		if ( false !== $existing ) {
			return false;
		}
		set_transient( $key, 1, 1 );
		return true;
	}

	/**
	 * Json error.
	 *
	 * @param string $message Message.
	 */
	private function json_error( string $message ): void {
		wp_send_json_error( array( 'message' => $message ) );
	}

	/**
	 * Resolve a user's dating display name.
	 *
	 * @param int $user_id User id.
	 */
	private function user_name( int $user_id ): string {
		$profile = $this->db->get_profile( $user_id );
		if ( $profile && ! empty( $profile['display_name'] ) ) {
			return $profile['display_name'];
		}
		$user = get_userdata( $user_id );
		return $user ? $user->display_name : '';
	}

	/**
	 * Notify + log a like/super-like received by $target_id from $actor_id.
	 *
	 * @param int    $target_id Target id.
	 * @param int    $actor_id Actor id.
	 * @param string $type Type.
	 */
	private function notify_like( int $target_id, int $actor_id, string $type ): void {
		$actor_name = $this->user_name( $actor_id );

		if ( 'super_like' === $type ) {
			$this->db->add_notification(
				$target_id,
				'super_like',
				/* translators: %s: actor display name */
				sprintf( __( '%s super liked you!', 'zeko-love' ), $actor_name ),
				$actor_id,
				0,
				'profile'
			);
			/* translators: %s: actor display name */
			$this->db->log_activity( $target_id, 'super_like_received', sprintf( __( '%s super liked your profile', 'zeko-love' ), $actor_name ) );
			return;
		}

		$this->db->add_notification(
			$target_id,
			'like',
			/* translators: %s: actor display name */
			sprintf( __( '%s liked your profile', 'zeko-love' ), $actor_name ),
			$actor_id,
			0,
			'profile'
		);
		/* translators: %s: actor display name */
		$this->db->log_activity( $target_id, 'like_received', sprintf( __( '%s liked your profile', 'zeko-love' ), $actor_name ) );
	}

	/**
	 * Notify + log a new mutual match for both users.
	 *
	 * @param int   $user1_id User1 id.
	 * @param int   $user2_id User2 id.
	 * @param int   $match_id Match id.
	 * @param float $score Score.
	 */
	private function notify_match( int $user1_id, int $user2_id, int $match_id, float $score ): void {
		$name1 = $this->user_name( $user1_id );
		$name2 = $this->user_name( $user2_id );

		if ( ! $this->db->has_notification( $user1_id, 'match', $match_id ) ) {
			/* translators: %s: matched user name */
			$this->db->add_notification( $user1_id, 'match', sprintf( __( 'You matched with %s!', 'zeko-love' ), $name2 ), $user2_id, $match_id, 'match' );
		}
		if ( ! $this->db->has_notification( $user2_id, 'match', $match_id ) ) {
			/* translators: %s: matched user name */
			$this->db->add_notification( $user2_id, 'match', sprintf( __( 'You matched with %s!', 'zeko-love' ), $name1 ), $user1_id, $match_id, 'match' );
		}

		/* translators: %s: matched user name */
		$this->db->log_activity( $user1_id, 'match', sprintf( __( 'You matched with %s', 'zeko-love' ), $name2 ), $match_id, array( 'score' => $score ) );
		/* translators: %s: matched user name */
		$this->db->log_activity( $user2_id, 'match', sprintf( __( 'You matched with %s', 'zeko-love' ), $name1 ), $match_id, array( 'score' => $score ) );
	}

	/**
	 * Handle save profile.
	 */
	public function handle_save_profile(): void {
		if ( ! $this->verify_request() ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			$this->json_error( __( 'Please log in.', 'zeko-love' ) );
			return;
		}

		$user_id = get_current_user_id();
		$fields  = $this->post_fields();

		$this->db->save_profile(
			$user_id,
			array(
				'display_name'      => sanitize_text_field( $fields['display_name'] ?? '' ),
				'bio'               => $this->sanitize_rich( $fields['bio'] ?? '' ),
				'dob'               => sanitize_text_field( $fields['dob'] ?? '' ),
				'gender'            => sanitize_text_field( $fields['gender'] ?? '' ),
				'interested_in'     => sanitize_text_field( $fields['interested_in'] ?? '' ),
				'location_lat'      => sanitize_text_field( $fields['location_lat'] ?? '' ),
				'location_lng'      => sanitize_text_field( $fields['location_lng'] ?? '' ),
				'relationship_goal' => sanitize_text_field( $fields['relationship_goal'] ?? '' ),
				'height_cm'         => absint( $fields['height_cm'] ?? 0 ),
				'occupation'        => sanitize_text_field( $fields['occupation'] ?? '' ),
				'education'         => sanitize_text_field( $fields['education'] ?? '' ),
				'smoking'           => sanitize_text_field( $fields['smoking'] ?? '' ),
				'drinking'          => sanitize_text_field( $fields['drinking'] ?? '' ),
				'has_children'      => 'yes' === ( $fields['has_children'] ?? '' ) ? 1 : 0,
				'wants_children'    => 'yes' === ( $fields['wants_children'] ?? '' ) ? 1 : 0,
				'religion'          => sanitize_text_field( $fields['religion'] ?? '' ),
				'ethnicity'         => sanitize_text_field( $fields['ethnicity'] ?? '' ),
			)
		);

		if ( isset( $fields['interests'] ) && is_array( $fields['interests'] ) ) {
			$interest_map = array();
			foreach ( $fields['interests'] as $category ) {
				$category                  = sanitize_text_field( $category );
				$interest_map[ $category ] = array( $category );
			}
			$this->db->save_interests( $user_id, $interest_map );
		}

		$this->maybe_earn_credit( 'profile_complete' );

		do_action( 'zeko_love_profile_saved', $user_id );

		wp_send_json_success( array( 'message' => __( 'Profile saved.', 'zeko-love' ) ) );
	}

	/**
	 * Grant a one-time earned credit to the current user (guarded + no-op
	 * when the wallet system is inactive).
	 *
	 * @param string $key Reward key from zeko_love_get_earn_config().
	 */
	private function maybe_earn_credit( string $key ): void {
		if ( ! Zeko_Love_Pay::active() || ! is_user_logged_in() ) {
			return;
		}
		$config = zeko_love_get_earn_config();
		if ( empty( $config[ $key ] ) ) {
			return;
		}
		Zeko_Love_Pay::credit(
			get_current_user_id(),
			(float) $config[ $key ]['amount'],
			$key,
			(string) $config[ $key ]['label']
		);
	}

	/**
	 * Handle get profile.
	 */
	public function handle_get_profile(): void {
		if ( ! $this->verify_request() ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			$this->json_error( __( 'Please log in.', 'zeko-love' ) );
			return;
		}

		$user_id   = absint( $_POST['user_id'] ?? get_current_user_id() ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify_request() (called at top of every AJAX handler).
		$viewer_id = get_current_user_id();
		$profile   = $this->db->get_profile( $user_id );

		if ( ! $profile ) {
			$this->json_error( __( 'Profile not found.', 'zeko-love' ) );
			return;
		}

		// Discovery/consent gate: blocks, search opt-out, and match state.
		if ( ! $this->db->can_view_profile( $user_id, $viewer_id ) ) {
			$this->json_error( __( 'Profile not available.', 'zeko-love' ) );
			return;
		}

		$dto              = Zeko_Love_REST::public_profile_dto( $profile, $this->can_view_sensitive( $user_id, $viewer_id ) );
		$dto['photos']    = $this->db->get_profile_photos( $user_id );
		$dto['interests'] = $this->db->get_interests( $user_id );

		wp_send_json_success( array( 'profile' => $dto ) );
	}

	/**
	 * Whether the current viewer may see sensitive profile fields (DOB and
	 * exact GPS coordinates). Only the owner, site administrators, and
	 * mutually matched users qualify.
	 *
	 * @return bool True when sensitive fields may be disclosed.
	 * @param int $profile_user_id Owner of the profile being viewed.
	 * @param int $viewer_id Current user.
	 */
	private function can_view_sensitive( int $profile_user_id, int $viewer_id ): bool {
		if ( $profile_user_id === $viewer_id || current_user_can( 'manage_options' ) ) {
			return true;
		}

		$match = $this->db->get_match( $profile_user_id, $viewer_id );
		return $match && 'matched' === (string) $match['status'];
	}

	/**
	 * Handle search profiles.
	 */
	public function handle_search_profiles(): void {
		if ( ! $this->verify_request() ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			$this->json_error( __( 'Please log in.', 'zeko-love' ) );
			return;
		}

		$filters = json_decode( wp_unslash( $_POST['filters'] ?? '{}' ), true ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified by verify_request(); decoded values are allow-list sanitized below before querying.
		if ( ! is_array( $filters ) ) {
			$filters = array();
		}

		// Sanitize the known JSON filter keys; the DB layer also validates each value.
		$filter_keys = array( 'name', 'search', 'gender', 'interested_in', 'age_min', 'age_max', 'location_lat', 'location_lng', 'radius_km', 'relationship_goal', 'has_children', 'wants_children', 'smoking', 'drinking', 'religion', 'ethnicity', 'orderby', 'order', 'page' );
		foreach ( $filter_keys as $filter_key ) {
			if ( isset( $filters[ $filter_key ] ) && is_string( $filters[ $filter_key ] ) ) {
				$filters[ $filter_key ] = sanitize_text_field( wp_unslash( $filters[ $filter_key ] ) );
			}
		}

		// Back-compat: legacy form-field POST (name/gender/age_min/...).
		foreach ( array( 'name', 'search', 'gender', 'interested_in', 'age_min', 'age_max', 'relationship_goal', 'orderby', 'order' ) as $k ) {
			if ( isset( $_POST[ $k ] ) && ! isset( $filters[ $k ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify_request() (called at top of every AJAX handler).
				$filters[ $k ] = sanitize_text_field( wp_unslash( $_POST[ $k ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify_request() (called at top of every AJAX handler).
			}
		}
		if ( ! empty( $filters['name'] ) && empty( $filters['search'] ) ) {
			$filters['search'] = $filters['name'];
		}
		unset( $filters['name'] );

		$page     = max( 1, absint( $_POST['page'] ?? ( $filters['page'] ?? 1 ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify_request() (called at top of every AJAX handler).
		$per_page = min( 50, max( 1, absint( $_POST['per_page'] ?? 20 ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify_request() (called at top of every AJAX handler).

		$profiles = $this->db->search_profiles( $filters, $page, $per_page );
		$total    = $this->db->count_profiles( $filters );

		$html = '';
		$love = Zeko_Love::instance();
		if ( $love && method_exists( $love->get_public(), 'render_profile_cards' ) ) {
			$html = $love->get_public()->render_profile_cards( $profiles );
		}

		wp_send_json_success(
			array(
				'html'     => $html,
				'total'    => $total,
				'pages'    => max( 1, (int) ceil( $total / $per_page ) ),
				'page'     => $page,
				'per_page' => $per_page,
			)
		);
	}

	/**
	 * Handle upload photo.
	 */
	public function handle_upload_photo(): void {
		if ( ! $this->verify_request() ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			$this->json_error( __( 'Please log in.', 'zeko-love' ) );
			return;
		}

		if ( empty( $_FILES['photo'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify_request() (called at top of every AJAX handler).
			$this->json_error( __( 'No file uploaded.', 'zeko-love' ) );
			return;
		}

		$file = $_FILES['photo']; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Uploaded file validated below (Zeko_Core_Upload::validate_file or manual type/size checks); files must not be run through sanitize_text_field.

		if ( class_exists( 'Zeko_Core_Upload' ) ) {
			$valid = Zeko_Core_Upload::validate_file( $file, 'image', 5 * MB_IN_BYTES );
			if ( is_wp_error( $valid ) ) {
				$this->json_error( $valid->get_error_message() );
				return;
			}
		} else {
			$allowed_types = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
			$allowed_ext   = array( 'jpg', 'jpeg', 'png', 'gif', 'webp' );

			if ( ! in_array( $file['type'], $allowed_types, true ) ) {
				$this->json_error( __( 'Invalid file type. Allowed: jpg, png, gif, webp.', 'zeko-love' ) );
				return;
			}

			$ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
			if ( ! in_array( $ext, $allowed_ext, true ) ) {
				$this->json_error( __( 'Invalid file extension.', 'zeko-love' ) );
				return;
			}

			if ( $file['size'] > 5 * MB_IN_BYTES ) {
				$this->json_error( __( 'File too large. Maximum 5MB.', 'zeko-love' ) );
				return;
			}

			if ( UPLOAD_ERR_OK !== $file['error'] ) {
				$this->json_error( __( 'Upload error.', 'zeko-love' ) );
				return;
			}
		}

		$upload = wp_handle_upload( $file, array( 'test_form' => false ) );

		if ( ! empty( $upload['error'] ) ) {
			$this->json_error( $upload['error'] );
			return;
		}

		$user_id    = get_current_user_id();
		$photo_url  = $upload['url'];
		$existing   = $this->db->get_profile_photos( $user_id );
		$is_primary = empty( $existing );

		$photo_id = $this->db->save_photo( $user_id, $photo_url, $is_primary );

		wp_send_json_success(
			array(
				'message'    => __( 'Photo uploaded.', 'zeko-love' ),
				'photo_id'   => $photo_id,
				'photo_url'  => $photo_url,
				'is_primary' => $is_primary,
			)
		);
	}

	/**
	 * Handle delete photo.
	 */
	public function handle_delete_photo(): void {
		if ( ! $this->verify_request() ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			$this->json_error( __( 'Please log in.', 'zeko-love' ) );
			return;
		}

		$photo_id = absint( $_POST['photo_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify_request() (called at top of every AJAX handler).
		$user_id  = get_current_user_id();

		if ( ! $photo_id ) {
			$this->json_error( __( 'Invalid photo.', 'zeko-love' ) );
			return;
		}

		$deleted = $this->db->delete_photo( $photo_id, $user_id );

		if ( ! $deleted ) {
			$this->json_error( __( 'Could not delete photo.', 'zeko-love' ) );
			return;
		}

		wp_send_json_success( array( 'message' => __( 'Photo deleted.', 'zeko-love' ) ) );
	}

	/**
	 * Handle set primary photo.
	 */
	public function handle_set_primary_photo(): void {
		if ( ! $this->verify_request() ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			$this->json_error( __( 'Please log in.', 'zeko-love' ) );
			return;
		}

		$photo_id = absint( $_POST['photo_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify_request() (called at top of every AJAX handler).
		$user_id  = get_current_user_id();

		if ( ! $photo_id ) {
			$this->json_error( __( 'Invalid photo.', 'zeko-love' ) );
			return;
		}

		$result = $this->db->set_primary_photo( $photo_id, $user_id );

		if ( ! $result ) {
			$this->json_error( __( 'Could not set primary photo.', 'zeko-love' ) );
			return;
		}

		wp_send_json_success( array( 'message' => __( 'Primary photo updated.', 'zeko-love' ) ) );
	}

	/**
	 * Handle save interests.
	 */
	public function handle_save_interests(): void {
		if ( ! $this->verify_request() ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			$this->json_error( __( 'Please log in.', 'zeko-love' ) );
			return;
		}

		$user_id   = get_current_user_id();
		$interests = json_decode( wp_unslash( $_POST['interests'] ?? '{}' ), true ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified by verify_request(); each category/value is sanitized by save_interests() before insert.

		if ( ! is_array( $interests ) ) {
			$this->json_error( __( 'Invalid interests data.', 'zeko-love' ) );
			return;
		}

		$this->db->save_interests( $user_id, $interests );

		wp_send_json_success( array( 'message' => __( 'Interests saved.', 'zeko-love' ) ) );
	}

	/**
	 * Handle like user.
	 */
	public function handle_like_user(): void {
		if ( ! $this->verify_request() ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			$this->json_error( __( 'Please log in.', 'zeko-love' ) );
			return;
		}

		$user_id   = get_current_user_id();
		$target_id = $this->resolve_target_id();

		if ( ! $target_id || $target_id === $user_id ) {
			$this->json_error( __( 'Invalid user.', 'zeko-love' ) );
			return;
		}

		if ( $this->db->is_blocked( $user_id, $target_id ) ) {
			$this->json_error( __( 'Cannot interact with this user.', 'zeko-love' ) );
			return;
		}

		$this->db->log_interaction( $user_id, $target_id, 'like' );

		$this->maybe_earn_credit( 'first_like' );

		$mutual = false;
		$match  = null;

		$their_interaction = $this->db->get_interaction( $target_id, $user_id );
		if ( $their_interaction && in_array( $their_interaction['type'], array( 'like', 'super_like' ), true ) ) {
			$mutual   = true;
			$matching = new Zeko_Love_Matching( $this->db );
			$score    = $matching->calculate_compatibility( $user_id, $target_id );
			$match_id = $this->db->create_match( $user_id, $target_id, $score );

			if ( $match_id ) {
				$match = array(
					'match_id' => $match_id,
					'score'    => $score,
				);
				$this->notify_match( $user_id, $target_id, $match_id, $score );
				do_action( 'zeko_love_new_match', $match_id, $user_id, $target_id, $score );
				$this->maybe_earn_credit( 'first_match' );
			}
		}

		$this->notify_like( $target_id, $user_id, 'like' );

		wp_send_json_success(
			array(
				'message' => __( 'Liked!', 'zeko-love' ),
				'mutual'  => $mutual,
				'match'   => $match,
			)
		);
	}

	/**
	 * Handle super like user.
	 */
	public function handle_super_like_user(): void {
		if ( ! $this->verify_request() ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			$this->json_error( __( 'Please log in.', 'zeko-love' ) );
			return;
		}

		$user_id   = get_current_user_id();
		$target_id = $this->resolve_target_id();

		if ( ! $target_id || $target_id === $user_id ) {
			$this->json_error( __( 'Invalid user.', 'zeko-love' ) );
			return;
		}

		if ( $this->db->is_blocked( $user_id, $target_id ) ) {
			$this->json_error( __( 'Cannot interact with this user.', 'zeko-love' ) );
			return;
		}

		$existing = $this->db->get_interaction( $user_id, $target_id );
		if ( $existing && in_array( $existing['type'], array( 'super_like' ), true ) ) {
			$this->json_error( __( 'You have already super liked this profile.', 'zeko-love' ) );
			return;
		}

		$pricing = zeko_love_get_pricing();
		$charge  = $this->charge_user( $user_id, (float) $pricing['super_like_price'], 'super_like', $target_id );

		if ( ! $charge['success'] ) {
			$this->json_error( $charge['message'] );
			return;
		}

		$this->db->log_interaction( $user_id, $target_id, 'super_like' );

		$mutual = false;
		$match  = null;

		$their_interaction = $this->db->get_interaction( $target_id, $user_id );
		if ( $their_interaction && in_array( $their_interaction['type'], array( 'like', 'super_like' ), true ) ) {
			$mutual   = true;
			$matching = new Zeko_Love_Matching( $this->db );
			$score    = $matching->calculate_compatibility( $user_id, $target_id );
			$match_id = $this->db->create_match( $user_id, $target_id, $score );

			if ( $match_id ) {
				$match = array(
					'match_id' => $match_id,
					'score'    => $score,
				);
				$this->notify_match( $user_id, $target_id, $match_id, $score );
				do_action( 'zeko_love_new_match', $match_id, $user_id, $target_id, $score );
			}
		}

		$this->notify_like( $target_id, $user_id, 'super_like' );

		wp_send_json_success(
			array(
				'message' => __( 'Super liked!', 'zeko-love' ),
				'mutual'  => $mutual,
				'match'   => $match,
			)
		);
	}

	/**
	 * Handle pass user.
	 */
	public function handle_pass_user(): void {
		if ( ! $this->verify_request() ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			$this->json_error( __( 'Please log in.', 'zeko-love' ) );
			return;
		}

		$user_id   = get_current_user_id();
		$target_id = $this->resolve_target_id();

		if ( ! $target_id || $target_id === $user_id ) {
			$this->json_error( __( 'Invalid user.', 'zeko-love' ) );
			return;
		}

		$this->db->log_interaction( $user_id, $target_id, 'pass' );

		wp_send_json_success( array( 'message' => __( 'Passed.', 'zeko-love' ) ) );
	}

	/**
	 * Handle accept match.
	 */
	public function handle_accept_match(): void {
		if ( ! $this->verify_request() ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			$this->json_error( __( 'Please log in.', 'zeko-love' ) );
			return;
		}

		$match_id = absint( $_POST['match_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify_request() (called at top of every AJAX handler).
		if ( ! $match_id ) {
			$this->json_error( __( 'Invalid match.', 'zeko-love' ) );
			return;
		}

		$match = $this->db->get_match_by_id( $match_id );
		if ( ! $match ) {
			$this->json_error( __( 'Match not found.', 'zeko-love' ) );
			return;
		}

		$user_id = get_current_user_id();
		if ( (int) $match['user1_id'] !== $user_id && (int) $match['user2_id'] !== $user_id ) {
			$this->json_error( __( 'You are not part of this match.', 'zeko-love' ) );
			return;
		}

		$this->db->update_match( $match_id, 'accepted' );

		$this->maybe_earn_credit( 'first_match' );

		$match = $this->db->get_match_by_id( $match_id );
		if ( $match ) {
			$other = ( (int) $match['user1_id'] === $user_id ) ? (int) $match['user2_id'] : (int) $match['user1_id'];
			$this->notify_match( $user_id, $other, $match_id, (float) $match['score'] );
		}

		do_action( 'zeko_love_match_responded', $match_id, $user_id, 'accepted' );

		wp_send_json_success( array( 'message' => __( 'Match accepted!', 'zeko-love' ) ) );
	}

	/**
	 * Handle get matches.
	 */
	public function handle_get_matches(): void {
		if ( ! $this->verify_request() ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			$this->json_error( __( 'Please log in.', 'zeko-love' ) );
			return;
		}

		$user_id = get_current_user_id();
		$tab     = sanitize_text_field( wp_unslash( $_POST['tab'] ?? 'matches' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify_request() (called at top of every AJAX handler); switch below keys it to an allow-list.
		$limit   = absint( $_POST['limit'] ?? 20 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify_request() (called at top of every AJAX handler).

		switch ( $tab ) {
			case 'likes-sent':
				$rows = $this->db->get_like_interactions( $user_id, 'sent', $limit );
				$html = $this->render_like_list( $rows );
				break;

			case 'likes-received':
				$rows = $this->db->get_like_interactions( $user_id, 'received', $limit );
				$html = $this->render_like_list( $rows );
				break;

			case 'matches':
			default:
				$matches = $this->db->get_matches( $user_id, '', $limit );
				$html    = $this->render_match_list( $matches );
				break;
		}

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * Render a list of match cards for the matches page.
	 *
	 * @param array $matches Matches.
	 */
	private function render_match_list( array $matches ): string {
		if ( empty( $matches ) ) {
			return '<div class="zl-empty-state"><span class="dashicons dashicons-heart"></span><h3>' . esc_html__( 'No matches yet.', 'zeko-love' ) . '</h3></div>';
		}

		ob_start();
		foreach ( $matches as $match ) {
			$photo       = ! empty( $match['other_photo_url'] )
				? esc_url( $match['other_photo_url'] )
				: get_avatar_url( (int) $match['other_user_id'], array( 'size' => 160 ) );
			$profile_url = Zeko_Love::instance()->get_public()->profile_view_url( (int) $match['other_user_id'] );
			?>
			<div class="zl-match-card">
				<img src="<?php echo esc_url( $photo ); ?>" alt="" width="80" height="80" loading="lazy" />
				<h4><?php echo esc_html( $match['other_user_name'] ); ?></h4>
				<span class="zl-score-badge"><?php echo esc_html( round( (float) $match['score'] ) ); ?>%</span>
				<?php if ( ! empty( $match['other_bio'] ) ) : ?>
					<p class="zl-match-bio"><?php echo esc_html( wp_trim_words( $match['other_bio'], 18 ) ); ?></p>
				<?php endif; ?>
				<div class="zl-actions">
					<a class="zl-btn zl-btn-secondary zl-btn-sm" href="<?php echo esc_url( $profile_url ); ?>">
						<?php echo esc_html__( 'View Profile', 'zeko-love' ); ?>
					</a>
					<button type="button" class="zl-btn zl-btn-primary zl-btn-sm zl-propose-date" data-match-id="<?php echo esc_attr( $match['match_id'] ); ?>">
						<?php echo esc_html__( 'Propose Date', 'zeko-love' ); ?>
					</button>
				</div>
			</div>
			<?php
		}
		return ob_get_clean();
	}

	/**
	 * Render a list of sent/received likes for the matches page.
	 *
	 * @param array $rows Rows.
	 */
	private function render_like_list( array $rows ): string {
		if ( empty( $rows ) ) {
			return '<div class="zl-empty-state"><span class="dashicons dashicons-heart"></span><h3>' . esc_html__( 'Nothing here yet.', 'zeko-love' ) . '</h3></div>';
		}

		ob_start();
		foreach ( $rows as $row ) {
			$photo       = ! empty( $row['other_photo_url'] )
				? esc_url( $row['other_photo_url'] )
				: get_avatar_url( (int) $row['other_user_id'], array( 'size' => 160 ) );
			$profile_url = Zeko_Love::instance()->get_public()->profile_view_url( (int) $row['other_user_id'] );
			?>
			<div class="zl-match-card">
				<img src="<?php echo esc_url( $photo ); ?>" alt="" width="80" height="80" loading="lazy" />
				<h4><?php echo esc_html( $row['other_user_name'] ); ?></h4>
				<span class="zl-score-badge"><?php echo 'super_like' === $row['type'] ? esc_html__( 'Super Like', 'zeko-love' ) : esc_html__( 'Like', 'zeko-love' ); ?></span>
				<div class="zl-actions">
					<a class="zl-btn zl-btn-secondary zl-btn-sm" href="<?php echo esc_url( $profile_url ); ?>">
						<?php echo esc_html__( 'View Profile', 'zeko-love' ); ?>
					</a>
				</div>
			</div>
			<?php
		}
		return ob_get_clean();
	}

	/**
	 * Handle get compatibility.
	 */
	public function handle_get_compatibility(): void {
		if ( ! $this->verify_request() ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			$this->json_error( __( 'Please log in.', 'zeko-love' ) );
			return;
		}

		$user_id   = get_current_user_id();
		$target_id = $this->resolve_target_id();

		if ( ! $target_id || $target_id === $user_id ) {
			$this->json_error( __( 'Invalid user.', 'zeko-love' ) );
			return;
		}

		// No compatibility probing of hidden, blocked, or opted-out users.
		if ( ! $this->db->can_view_profile( (int) $target_id, $user_id ) ) {
			$this->json_error( __( 'Profile not available.', 'zeko-love' ) );
			return;
		}

		$matching = new Zeko_Love_Matching( $this->db );
		$score    = $matching->calculate_compatibility( $user_id, $target_id );
		$profile  = $this->db->get_profile( $target_id );

		$mutual_interests = array();
		$my_interests     = $this->db->get_interests( $user_id );
		$their_interests  = $this->db->get_interests( $target_id );

		$my_values = array_unique(
			array_map(
				function ( $i ) {
					return $i['category'] . ':' . $i['value'];
				},
				$my_interests
			)
		);

		foreach ( $their_interests as $ti ) {
			$key = $ti['category'] . ':' . $ti['value'];
			if ( in_array( $key, $my_values, true ) ) {
				$mutual_interests[] = $ti;
			}
		}

		wp_send_json_success(
			array(
				'score'            => $score,
				'mutual_interests' => $mutual_interests,
				'target_name'      => $profile ? ( $profile['display_name'] ?? '' ) : '',
			)
		);
	}

	/**
	 * Handle get conversations.
	 */
	public function handle_get_conversations(): void {
		if ( ! $this->verify_request() ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			$this->json_error( __( 'Please log in.', 'zeko-love' ) );
			return;
		}

		$user_id       = get_current_user_id();
		$limit         = absint( $_POST['limit'] ?? 50 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify_request() (called at top of every AJAX handler).
		$conversations = $this->db->get_conversations( $user_id, $limit );
		$photos        = $this->db->get_primary_photos( wp_list_pluck( $conversations, 'other_user_id' ) );

		ob_start();
		if ( empty( $conversations ) ) {
			echo '<div class="zl-empty-state"><p>' . esc_html__( 'No conversations yet.', 'zeko-love' ) . '</p></div>';
		} else {
			foreach ( $conversations as $conv ) {
				$other_id = (int) $conv['other_user_id'];
				$photo    = $photos[ $other_id ] ?? array();
				$avatar   = ! empty( $photo['photo_url'] )
					? $photo['photo_url']
					: get_avatar_url( $other_id, array( 'size' => 48 ) );
				$time     = $this->format_time( $conv['last_message_at'] ?? $conv['created_at'] ?? '' );
				?>
				<div class="zl-conv-item" data-conv-id="<?php echo esc_attr( (int) $conv['conversation_id'] ); ?>">
					<img src="<?php echo esc_url( $avatar ); ?>" alt="" loading="lazy" />
					<div class="zl-conv-info">
						<h4><?php echo esc_html( $conv['other_user_name'] ?? '' ); ?></h4>
						<p><?php echo esc_html( $conv['last_message'] ?? '' ); ?></p>
					</div>
					<?php if ( $time ) : ?>
						<span class="zl-conv-time"><?php echo esc_html( $time ); ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $conv['unread_count'] ) ) : ?>
						<span class="zl-unread-badge"><?php echo esc_html( (int) $conv['unread_count'] ); ?></span>
					<?php endif; ?>
				</div>
				<?php
			}
		}
		$html = ob_get_clean();

		wp_send_json_success(
			array(
				'html'          => $html,
				'conversations' => $conversations,
			)
		);
	}

	/**
	 * Handle get thread messages.
	 */
	public function handle_get_thread_messages(): void {
		if ( ! $this->verify_request() ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			$this->json_error( __( 'Please log in.', 'zeko-love' ) );
			return;
		}

		$user_id         = get_current_user_id();
		$conversation_id = absint( $_POST['conversation_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify_request() (called at top of every AJAX handler).

		if ( ! $conversation_id ) {
			$this->json_error( __( 'Missing conversation.', 'zeko-love' ) );
			return;
		}

		if ( ! $this->db->is_conversation_participant( $conversation_id, $user_id ) ) {
			$this->json_error( __( 'Unauthorized.', 'zeko-love' ) );
			return;
		}

		$conv  = $this->db->get_conversation( $conversation_id );
		$other = $conv ? ( ( (int) $conv['user1_id'] === $user_id ) ? (int) $conv['user2_id'] : (int) $conv['user1_id'] ) : 0;

		if ( ! $other ) {
			$this->json_error( __( 'Conversation not found.', 'zeko-love' ) );
			return;
		}

		$this->db->mark_conversation_read( $conversation_id, $user_id );
		$messages = $this->db->get_messages( $conversation_id, 100 );

		ob_start();
		$this->render_thread( $user_id, $other, $messages );
		$html = ob_get_clean();

		wp_send_json_success(
			array(
				'html'            => $html,
				'conversation_id' => $conversation_id,
				'other_id'        => $other,
				'other_name'      => $this->user_name( $other ),
			)
		);
	}

	/**
	 * Render a message thread (header, message list, icebreakers, composer).
	 *
	 * @param int   $user_id User id.
	 * @param int   $other_id Other id.
	 * @param array $messages Messages.
	 */
	private function render_thread( int $user_id, int $other_id, array $messages ): void {
		$profile = $this->db->get_profile( $other_id );
		$name    = $profile ? ( $profile['display_name'] ?? '' ) : '';
		if ( '' === $name ) {
			$name = $this->user_name( $other_id );
		}

		$photos = $this->db->get_primary_photos( array( $other_id ) );
		$photo  = $photos[ $other_id ] ?? array();
		$avatar = ! empty( $photo['photo_url'] )
			? $photo['photo_url']
			: get_avatar_url( $other_id, array( 'size' => 48 ) );

		$online      = $this->is_online( $profile['last_active'] ?? '' );
		$icebreakers = array_slice( zeko_love_get_icebreakers(), 0, 4 );
		?>
		<div class="zl-thread-header">
			<img src="<?php echo esc_url( $avatar ); ?>" alt="" />
			<div class="zl-thread-name">
				<strong><?php echo esc_html( $name ); ?></strong>
				<?php if ( $online ) : ?>
					<span class="zl-status-badge online"><?php esc_html_e( 'Online now', 'zeko-love' ); ?></span>
				<?php endif; ?>
			</div>
		</div>

		<div class="zl-message-list">
			<?php if ( empty( $messages ) ) : ?>
				<div class="zl-empty-state">
					<p><?php esc_html_e( 'Say hi — start the conversation!', 'zeko-love' ); ?></p>
				</div>
			<?php else : ?>
				<?php foreach ( $messages as $m ) : ?>
					<?php $is_mine = (int) $m['sender_id'] === $user_id; ?>
					<div class="zl-message-bubble <?php echo $is_mine ? 'zl-message-mine' : 'zl-message-theirs'; ?>">
						<?php echo esc_html( $m['message'] ); ?>
						<span class="zl-msg-time"><?php echo esc_html( $this->format_time( $m['created_at'] ?? '' ) ); ?></span>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>

		<?php if ( ! empty( $icebreakers ) ) : ?>
			<div class="zl-icebreakers">
				<?php foreach ( $icebreakers as $icebreaker ) : ?>
					<button type="button" class="zl-icebreaker-chip" data-icebreaker="<?php echo esc_attr( $icebreaker ); ?>">
						<?php echo esc_html( $icebreaker ); ?>
					</button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<div class="zl-send-form">
			<textarea class="zl-input zl-message-input" rows="1" placeholder="<?php esc_attr_e( 'Type a message...', 'zeko-love' ); ?>"></textarea>
			<button type="button" class="zl-btn zl-btn-primary zl-send-btn"><?php esc_html_e( 'Send', 'zeko-love' ); ?></button>
		</div>
		<?php
	}

	/**
	 * Is the user online right now (active within the last 15 minutes)?
	 *
	 * @param string $last_active Last active.
	 */
	private function is_online( string $last_active ): bool {
		if ( '' === $last_active || '0000-00-00 00:00:00' === $last_active ) {
			return false;
		}
		$ts = strtotime( $last_active . ' UTC' );
		return false !== $ts && ( time() - $ts ) < 15 * MINUTE_IN_SECONDS;
	}

	/**
	 * Compact relative time for list/thread timestamps.
	 *
	 * @param string $datetime Datetime.
	 */
	private function format_time( string $datetime ): string {
		if ( '' === $datetime || '0000-00-00 00:00:00' === $datetime ) {
			return '';
		}
		$ts = strtotime( $datetime );
		if ( false === $ts ) {
			return '';
		}
		return sprintf(
			/* translators: %s: human-readable time difference. */
			__( '%s ago', 'zeko-love' ),
			human_time_diff( $ts, time() )
		);
	}

	/**
	 * Handle send message.
	 */
	public function handle_send_message(): void {
		if ( ! $this->verify_request() ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			$this->json_error( __( 'Please log in.', 'zeko-love' ) );
			return;
		}

		$sender_id       = get_current_user_id();
		$conversation_id = absint( $_POST['conversation_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify_request() (called at top of every AJAX handler).
		$receiver_id     = absint( $_POST['receiver_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify_request() (called at top of every AJAX handler).
		$message         = $this->sanitize_rich( wp_unslash( $_POST['message'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified by verify_request(); rich content sanitized (allow-listed tags) by sanitize_rich() before insert.

		if ( '' === $message ) {
			$this->json_error( __( 'Message cannot be empty.', 'zeko-love' ) );
			return;
		}

		if ( $conversation_id ) {
			if ( ! $this->db->is_conversation_participant( $conversation_id, $sender_id ) ) {
				$this->json_error( __( 'Unauthorized.', 'zeko-love' ) );
				return;
			}
			$conv = $this->db->get_conversation( $conversation_id );
			if ( ! $conv ) {
				$this->json_error( __( 'Conversation not found.', 'zeko-love' ) );
				return;
			}
			$receiver_id = ( (int) $conv['user1_id'] === $sender_id ) ? (int) $conv['user2_id'] : (int) $conv['user1_id'];
		} else {
			if ( ! $receiver_id || $receiver_id === $sender_id ) {
				$this->json_error( __( 'Invalid user.', 'zeko-love' ) );
				return;
			}
			// A new conversation may only be opened with an existing match.
			$match = $this->db->get_match( $sender_id, $receiver_id );
			if ( ! $match || 'matched' !== (string) $match['status'] ) {
				$this->json_error( __( 'You can only message your matches.', 'zeko-love' ) );
				return;
			}
			$conversation_id = $this->db->create_conversation( $sender_id, $receiver_id );
			if ( ! $conversation_id ) {
				$this->json_error( __( 'Could not create conversation.', 'zeko-love' ) );
				return;
			}
		}

		if ( $this->db->is_blocked( $sender_id, $receiver_id ) ) {
			$this->json_error( __( 'Cannot message this user.', 'zeko-love' ) );
			return;
		}

		$message_id = $this->db->send_message( $conversation_id, $sender_id, $message );

		if ( ! $message_id ) {
			$this->json_error( __( 'Could not send message.', 'zeko-love' ) );
			return;
		}

		do_action( 'zeko_love_new_message', $receiver_id, $sender_id, $message );

		$this->db->add_notification(
			$receiver_id,
			'message',
			/* translators: %s: sender name */
			sprintf( __( '%s sent you a message', 'zeko-love' ), $this->user_name( $sender_id ) ),
			$sender_id,
			$message_id,
			'message'
		);
		/* translators: %s: sender name */
		$this->db->log_activity( $receiver_id, 'message_received', sprintf( __( '%s sent you a message', 'zeko-love' ), $this->user_name( $sender_id ) ), $message_id );

		ob_start();
		?>
		<div class="zl-message-bubble zl-message-mine">
			<?php echo esc_html( $message ); ?>
			<span class="zl-msg-time"><?php esc_html_e( 'Just now', 'zeko-love' ); ?></span>
		</div>
		<?php
		$html = ob_get_clean();

		wp_send_json_success(
			array(
				'message'         => __( 'Message sent.', 'zeko-love' ),
				'html'            => $html,
				'message_id'      => $message_id,
				'conversation_id' => $conversation_id,
			)
		);
	}

	/**
	 * Handle propose date.
	 */
	public function handle_propose_date(): void {
		if ( ! $this->verify_request() ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			$this->json_error( __( 'Please log in.', 'zeko-love' ) );
			return;
		}

		$user_id   = get_current_user_id();
		$target_id = $this->resolve_target_id();
		$match_id  = absint( $_POST['match_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify_request() (called at top of every AJAX handler).

		if ( $match_id ) {
			$match = $this->db->get_match_by_id( $match_id );
			// A match id may only be used by one of the two matched users.
			if ( ! $match || ( (int) $match['user1_id'] !== $user_id && (int) $match['user2_id'] !== $user_id ) ) {
				$this->json_error( __( 'Invalid match.', 'zeko-love' ) );
				return;
			}
			if ( 'matched' !== (string) $match['status'] ) {
				$this->json_error( __( 'This match is no longer active.', 'zeko-love' ) );
				return;
			}
			$target_id = ( (int) $match['user1_id'] === $user_id ) ? (int) $match['user2_id'] : (int) $match['user1_id'];
		}

		if ( ! $target_id || $target_id === $user_id ) {
			$this->json_error( __( 'Invalid user.', 'zeko-love' ) );
			return;
		}

		// A date may only be proposed to an existing, active match.
		$match = $this->db->get_match( $user_id, $target_id );
		if ( ! $match || 'matched' !== (string) $match['status'] ) {
			$this->json_error( __( 'You can only schedule dates with your matches.', 'zeko-love' ) );
			return;
		}

		$proposed_date = sanitize_text_field( wp_unslash( $_POST['proposed_date'] ?? ( $_POST['date'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify_request() (called at top of every AJAX handler).
		$proposed_time = sanitize_text_field( wp_unslash( $_POST['proposed_time'] ?? ( $_POST['time'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify_request() (called at top of every AJAX handler).

		if ( ! $proposed_date || ! $proposed_time ) {
			$this->json_error( __( 'Date and time are required.', 'zeko-love' ) );
			return;
		}

		$schedule_id = $this->db->create_date(
			array(
				'user1_id'         => $user_id,
				'user2_id'         => $target_id,
				'proposed_date'    => $proposed_date,
				'proposed_time'    => $proposed_time,
				'duration_minutes' => absint( $_POST['duration_minutes'] ?? 60 ), // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify_request() (called at top of every AJAX handler).
				'location_name'    => sanitize_text_field( wp_unslash( $_POST['location_name'] ?? ( $_POST['location'] ?? '' ) ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify_request() (called at top of every AJAX handler).
				'location_address' => sanitize_textarea_field( wp_unslash( $_POST['location_address'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify_request() (called at top of every AJAX handler).
				'location_lat'     => isset( $_POST['location_lat'] ) ? (float) sanitize_text_field( wp_unslash( $_POST['location_lat'] ) ) : null, // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify_request() (called at top of every AJAX handler).
				'location_lng'     => isset( $_POST['location_lng'] ) ? (float) sanitize_text_field( wp_unslash( $_POST['location_lng'] ) ) : null, // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify_request() (called at top of every AJAX handler).
				'notes'            => sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify_request() (called at top of every AJAX handler).
				'created_by'       => $user_id,
			)
		);

		if ( ! $schedule_id ) {
			$this->json_error( __( 'Could not schedule date.', 'zeko-love' ) );
			return;
		}

		do_action( 'zeko_love_date_scheduled', $schedule_id );

		$this->db->add_notification(
			$target_id,
			'date_proposed',
			/* translators: %s: proposer name */
			sprintf( __( '%s proposed a date with you', 'zeko-love' ), $this->user_name( $user_id ) ),
			$user_id,
			$schedule_id,
			'date'
		);
		/* translators: %s: target user name */
		$this->db->log_activity( $user_id, 'date_scheduled', sprintf( __( 'You proposed a date with %s', 'zeko-love' ), $this->user_name( $target_id ) ), $schedule_id );

		wp_send_json_success(
			array(
				'message'     => __( 'Date proposed!', 'zeko-love' ),
				'schedule_id' => $schedule_id,
			)
		);
	}

	/**
	 * Handle respond to date.
	 */
	public function handle_respond_to_date(): void {
		if ( ! $this->verify_request() ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			$this->json_error( __( 'Please log in.', 'zeko-love' ) );
			return;
		}

		$schedule_id = absint( $_POST['schedule_id'] ?? ( $_POST['date_id'] ?? 0 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify_request() (called at top of every AJAX handler).
		$status_raw  = sanitize_text_field( wp_unslash( $_POST['status'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify_request() (called at top of every AJAX handler).
		$status      = $status_raw;

		if ( 'confirmed' === $status_raw ) {
			$status = 'accepted';
		} elseif ( 'cancelled' === $status_raw ) {
			$status = 'declined';
		}

		if ( ! $schedule_id || ! in_array( $status, array( 'accepted', 'declined', 'reschedule' ), true ) ) {
			$this->json_error( __( 'Invalid parameters.', 'zeko-love' ) );
			return;
		}

		$date = $this->db->get_date( $schedule_id );

		if ( ! $date ) {
			$this->json_error( __( 'Date not found.', 'zeko-love' ) );
			return;
		}

		$user_id = get_current_user_id();
		if ( (int) $date['user1_id'] !== $user_id && (int) $date['user2_id'] !== $user_id ) {
			$this->json_error( __( 'Unauthorized.', 'zeko-love' ) );
			return;
		}

		$this->db->update_date( $schedule_id, array( 'status' => $status ) );

		$other_id     = ( (int) $date['user1_id'] === $user_id ) ? (int) $date['user2_id'] : (int) $date['user1_id'];
		$status_label = array(
			'accepted'   => __( 'accepted', 'zeko-love' ),
			'declined'   => __( 'declined', 'zeko-love' ),
			'reschedule' => __( 'asked to reschedule', 'zeko-love' ),
		);

		$this->db->add_notification(
			$other_id,
			'date_' . $status,
			/* translators: 1: responder name. 2: response status (accepted or declined) */
			sprintf( __( '%1$s %2$s your date proposal', 'zeko-love' ), $this->user_name( $user_id ), $status_label[ $status ] ?? $status ),
			$user_id,
			$schedule_id,
			'date'
		);
		/* translators: 1: responder name. 2: response status (accepted or declined) */
		$this->db->log_activity( $other_id, 'date_responded', sprintf( __( '%1$s %2$s your date proposal', 'zeko-love' ), $this->user_name( $user_id ), $status_label[ $status ] ?? $status ), $schedule_id );

		wp_send_json_success(
			array(
				'message' => __( 'Response recorded.', 'zeko-love' ),
			)
		);
	}

	/**
	 * Handle get dates.
	 */
	public function handle_get_dates(): void {
		if ( ! $this->verify_request() ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			$this->json_error( __( 'Please log in.', 'zeko-love' ) );
			return;
		}

		$user_id = get_current_user_id();
		$status  = sanitize_text_field( wp_unslash( $_POST['status'] ?? ( $_POST['tab'] ?? 'upcoming' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify_request() (called at top of every AJAX handler).
		$limit   = absint( $_POST['limit'] ?? 20 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify_request() (called at top of every AJAX handler).

		$dates = $this->db->get_user_dates( $user_id, $status, $limit );

		$html = '';
		foreach ( $dates as $date ) {
			$html .= $this->render_date_card( $date );
		}

		if ( '' === $html ) {
			$html = '<div class="zl-empty-state"><span class="dashicons dashicons-calendar"></span><h3>' . esc_html__( 'No dates scheduled.', 'zeko-love' ) . '</h3></div>';
		}

		wp_send_json_success(
			array(
				'dates' => $dates,
				'html'  => $html,
			)
		);
	}

	/**
	 * Render a single date card for the dates page.
	 *
	 * @param array $date Date.
	 */
	private function render_date_card( array $date ): string {
		$status_labels = array(
			'pending'    => __( 'Pending', 'zeko-love' ),
			'accepted'   => __( 'Confirmed', 'zeko-love' ),
			'declined'   => __( 'Cancelled', 'zeko-love' ),
			'reschedule' => __( 'Reschedule requested', 'zeko-love' ),
		);
		$status        = $date['status'] ?? 'pending';
		$avatar        = get_avatar_url( (int) $date['other_user_id'], array( 'size' => 48 ) );

		$datetime = sprintf(
			'%s %s',
			$date['proposed_date'] ?? '',
			! empty( $date['proposed_time'] ) ? date_i18n( get_option( 'time_format' ), strtotime( $date['proposed_time'] ) ) : ''
		);

		ob_start();
		?>
		<div class="zl-date-card">
			<span class="zl-date-status <?php echo esc_attr( $status ); ?>"></span>
			<div class="zl-date-body">
				<h4><?php echo esc_html( $date['other_user_name'] ); ?></h4>
				<p class="zl-date-datetime"><?php echo esc_html( trim( $datetime ) ); ?></p>
				<?php if ( ! empty( $date['location_name'] ) ) : ?>
					<p class="zl-date-location"><?php echo esc_html( $date['location_name'] ); ?></p>
				<?php endif; ?>
				<p class="zl-date-state">
					<span class="zl-status-badge zl-status-<?php echo esc_attr( $status ); ?>">
						<?php echo esc_html( $status_labels[ $status ] ?? $status ); ?>
					</span>
				</p>
				<?php if ( in_array( $status, array( 'pending' ), true ) ) : ?>
					<div class="zl-date-actions">
						<button type="button" class="zl-btn zl-btn-primary zl-btn-sm zl-date-accept" data-date-id="<?php echo esc_attr( $date['schedule_id'] ); ?>">
							<?php echo esc_html__( 'Accept', 'zeko-love' ); ?>
						</button>
						<button type="button" class="zl-btn zl-btn-danger zl-btn-sm zl-date-cancel" data-date-id="<?php echo esc_attr( $date['schedule_id'] ); ?>">
							<?php echo esc_html__( 'Decline', 'zeko-love' ); ?>
						</button>
					</div>
				<?php elseif ( in_array( $status, array( 'accepted' ), true ) ) : ?>
					<div class="zl-date-actions">
						<button type="button" class="zl-btn zl-btn-danger zl-btn-sm zl-date-cancel" data-date-id="<?php echo esc_attr( $date['schedule_id'] ); ?>">
							<?php echo esc_html__( 'Cancel', 'zeko-love' ); ?>
						</button>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Handle block user.
	 */
	public function handle_block_user(): void {
		if ( ! $this->verify_request() ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			$this->json_error( __( 'Please log in.', 'zeko-love' ) );
			return;
		}

		$user_id   = get_current_user_id();
		$target_id = $this->resolve_target_id();

		if ( ! $target_id || $target_id === $user_id ) {
			$this->json_error( __( 'Invalid user.', 'zeko-love' ) );
			return;
		}

		$this->db->log_interaction( $user_id, $target_id, 'block' );

		wp_send_json_success( array( 'message' => __( 'User blocked.', 'zeko-love' ) ) );
	}

	/**
	 * Handle report user.
	 */
	public function handle_report_user(): void {
		if ( ! $this->verify_request() ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			$this->json_error( __( 'Please log in.', 'zeko-love' ) );
			return;
		}

		$user_id   = get_current_user_id();
		$target_id = $this->resolve_target_id();
		$reason    = sanitize_textarea_field( wp_unslash( $_POST['reason'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify_request() (called at top of every AJAX handler).

		if ( ! $target_id || $target_id === $user_id ) {
			$this->json_error( __( 'Invalid user.', 'zeko-love' ) );
			return;
		}

		if ( empty( $reason ) ) {
			$this->json_error( __( 'Please provide a reason for the report.', 'zeko-love' ) );
			return;
		}

		$this->db->log_interaction( $user_id, $target_id, 'report', $reason );

		wp_send_json_success( array( 'message' => __( 'User reported.', 'zeko-love' ) ) );
	}

	/**
	 * Handle get blocked users.
	 */
	public function handle_get_blocked_users(): void {
		if ( ! $this->verify_request() ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			$this->json_error( __( 'Please log in.', 'zeko-love' ) );
			return;
		}

		$user_id       = get_current_user_id();
		$blocked_ids   = $this->db->get_blocked_users( $user_id );
		$blocked_users = array();

		foreach ( $blocked_ids as $id ) {
			$user = get_userdata( (int) $id );
			if ( $user ) {
				$blocked_users[] = array(
					'user_id'      => (int) $id,
					'display_name' => $user->display_name,
					'avatar'       => get_avatar_url( (int) $id, array( 'size' => 48 ) ),
				);
			}
		}

		wp_send_json_success( array( 'blocked_users' => $blocked_users ) );
	}

	/**
	 * Handle unblock user.
	 */
	public function handle_unblock_user(): void {
		if ( ! $this->verify_request() ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			$this->json_error( __( 'Please log in.', 'zeko-love' ) );
			return;
		}

		$user_id   = get_current_user_id();
		$target_id = $this->resolve_target_id();

		if ( ! $target_id || $target_id === $user_id ) {
			$this->json_error( __( 'Invalid user.', 'zeko-love' ) );
			return;
		}

		$this->db->unblock_user( $user_id, $target_id );

		wp_send_json_success( array( 'message' => __( 'User unblocked.', 'zeko-love' ) ) );
	}

	/**
	 * Load per-user privacy/notification settings (used by the Settings page).
	 */
	public function handle_get_settings(): void {
		if ( ! $this->verify_request() ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			$this->json_error( __( 'Please log in.', 'zeko-love' ) );
			return;
		}

		$settings = get_user_meta( get_current_user_id(), 'zeko_love_user_settings', true );
		$settings = is_array( $settings ) ? $settings : array();
		$settings = wp_parse_args(
			$settings,
			array(
				'show_in_search'       => 1,
				'verified_only'        => 0,
				'notify_match'         => 1,
				'notify_message'       => 1,
				'notify_date_reminder' => 1,
				'notify_call_reminder' => 1,
			)
		);

		wp_send_json_success( array( 'fields' => array_map( 'intval', $settings ) ) );
	}

	/**
	 * Save per-user privacy/notification settings.
	 */
	public function handle_save_settings(): void {
		if ( ! $this->verify_request() ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			$this->json_error( __( 'Please log in.', 'zeko-love' ) );
			return;
		}

		$user_id = get_current_user_id();
		$fields  = $this->post_fields();

		$allowed = array(
			'show_in_search',
			'verified_only',
			'notify_match',
			'notify_message',
			'notify_date_reminder',
			'notify_call_reminder',
		);

		$current = get_user_meta( $user_id, 'zeko_love_user_settings', true );
		$current = is_array( $current ) ? $current : array();

		foreach ( $allowed as $key ) {
			if ( array_key_exists( $key, $fields ) ) {
				$current[ $key ] = ! empty( $fields[ $key ] ) ? 1 : 0;
			}
		}

		update_user_meta( $user_id, 'zeko_love_user_settings', $current );

		wp_send_json_success( array( 'message' => __( 'Settings saved.', 'zeko-love' ) ) );
	}

	/**
	 * Save the user's payout method + account details.
	 */
	public function handle_save_payout(): void {
		if ( ! $this->verify_request() ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			$this->json_error( __( 'Please log in.', 'zeko-love' ) );
			return;
		}

		$user_id = get_current_user_id();
		$fields  = $this->post_fields();

		$method = sanitize_text_field( $fields['payout_method'] ?? 'paypal' );
		if ( ! in_array( $method, array( 'paypal', 'crypto', 'bank' ), true ) ) {
			$this->json_error( __( 'Invalid payout method.', 'zeko-love' ) );
			return;
		}

		$payout = array(
			'method'              => $method,
			'paypal_email'        => is_email( $fields['paypal_email'] ?? '' ) ? sanitize_email( $fields['paypal_email'] ) : '',
			'crypto_address'      => sanitize_text_field( $fields['crypto_address'] ?? '' ),
			'crypto_network'      => sanitize_text_field( $fields['crypto_network'] ?? '' ),
			'bank_name'           => sanitize_text_field( $fields['bank_name'] ?? '' ),
			'bank_account_name'   => sanitize_text_field( $fields['bank_account_name'] ?? '' ),
			'bank_account_number' => sanitize_text_field( $fields['bank_account_number'] ?? '' ),
			'bank_routing'        => sanitize_text_field( $fields['bank_routing'] ?? '' ),
			'updated_at'          => current_time( 'mysql', true ),
		);

		update_user_meta( $user_id, 'zeko_love_payout', $payout );

		wp_send_json_success( array( 'message' => __( 'Payout method saved.', 'zeko-love' ) ) );
	}

	/**
	 * Request a withdrawal from the user's wallet via Zeko Pay.
	 */
	public function handle_withdraw_funds(): void {
		if ( ! $this->verify_request() ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			$this->json_error( __( 'Please log in.', 'zeko-love' ) );
			return;
		}

		if ( ! class_exists( 'Zeko_Pay_Ledger' ) ) {
			$this->json_error( __( 'Wallet system is not available.', 'zeko-love' ) );
			return;
		}

		$user_id = get_current_user_id();
		$fields  = $this->post_fields();

		$amount = max( 0.0, (float) ( $fields['amount'] ?? 0 ) );
		if ( $amount <= 0 ) {
			$this->json_error( __( 'Enter a valid amount.', 'zeko-love' ) );
			return;
		}

		$otp_code = isset( $fields['otp_code'] ) ? sanitize_text_field( wp_unslash( $fields['otp_code'] ) ) : '';

		$payout = get_user_meta( $user_id, 'zeko_love_payout', true );
		$payout = is_array( $payout ) ? $payout : array();
		$method = sanitize_text_field( $payout['method'] ?? 'paypal' );

		$account_details = array();
		if ( 'paypal' === $method ) {
			$account_details['paypal_email'] = $payout['paypal_email'] ?? '';
		} elseif ( 'crypto' === $method ) {
			$account_details['wallet_address'] = $payout['crypto_address'] ?? '';
			if ( ! empty( $payout['crypto_network'] ) ) {
				$account_details['crypto_type'] = $payout['crypto_network'];
			}
		} elseif ( 'bank' === $method ) {
			$account_details = array(
				'bank_name'      => $payout['bank_name'] ?? '',
				'account_name'   => $payout['bank_account_name'] ?? '',
				'account_number' => $payout['bank_account_number'] ?? '',
				'routing_number' => $payout['bank_routing'] ?? '',
				'iban'           => $payout['bank_iban'] ?? '',
			);
		}

		try {
			$result = Zeko_Pay_Ledger::instance()->request_withdrawal( $user_id, (string) $amount, $method, $account_details, '', $otp_code );
		} catch ( \Exception $e ) {
			$result = array(
				'success' => false,
				'message' => __( 'Withdrawal request failed.', 'zeko-love' ),
			);
		}

		if ( empty( $result['success'] ) ) {
			$this->json_error( $result['message'] ?? __( 'Withdrawal request failed.', 'zeko-love' ) );
			return;
		}

		wp_send_json_success(
			array(
				'message' => __( 'Withdrawal requested. It will be reviewed by the team.', 'zeko-love' ),
				'tx_id'   => $result['tx_id'] ?? 0,
			)
		);
	}

	/**
	 * Email a large-withdrawal verification code via Zeko Pay Trust.
	 * Mirrors the wallet dashboard's OTP flow so withdrawals from the dating
	 * settings page satisfy the same Trust & Safety gate.
	 */
	public function handle_send_withdrawal_otp(): void {
		if ( ! $this->verify_request() ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			$this->json_error( __( 'Please log in.', 'zeko-love' ) );
			return;
		}

		$fields = $this->post_fields();
		$amount = max( 0.0, (float) ( $fields['amount'] ?? 0 ) );
		if ( $amount <= 0 ) {
			$this->json_error( __( 'Enter a valid amount.', 'zeko-love' ) );
			return;
		}

		if ( ! class_exists( 'Zeko_Pay_Trust' ) ) {
			$this->json_error( __( 'Verification codes are not available.', 'zeko-love' ) );
			return;
		}

		$trust = Zeko_Pay_Trust::instance();
		if ( ! $trust->requires_withdrawal_otp( (string) $amount ) ) {
			wp_send_json_success(
				array(
					'message' => __( 'A verification code is not required for this amount.', 'zeko-love' ),
					'otp'     => false,
				)
			);
			return;
		}

		wp_send_json( $trust->issue_otp( get_current_user_id(), 'withdrawal' ) );
	}

	/**
	 * Send a wallet credit gift to another member from the profile view.
	 */
	public function handle_send_gift(): void {
		if ( ! $this->verify_request() ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			$this->json_error( __( 'Please log in.', 'zeko-love' ) );
			return;
		}

		$from_id = get_current_user_id();
		$to_id   = $this->resolve_target_id();
		$amount  = max( 0.01, (float) sanitize_text_field( wp_unslash( $_POST['amount'] ?? 0 ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify_request() (called at top of every AJAX handler).
		$message = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify_request() (called at top of every AJAX handler).

		if ( ! $to_id || $to_id === $from_id ) {
			$this->json_error( __( 'Invalid user.', 'zeko-love' ) );
			return;
		}
		if ( $this->db->is_blocked( $from_id, $to_id ) || $this->db->is_blocked( $to_id, $from_id ) ) {
			$this->json_error( __( 'Cannot gift this user.', 'zeko-love' ) );
			return;
		}
		if ( ! Zeko_Love_Pay::active() ) {
			$this->json_error( __( 'Wallet system is not available.', 'zeko-love' ) );
			return;
		}
		if ( bccomp( Zeko_Love_Pay::balance( $from_id ), (string) $amount, 2 ) < 0 ) {
			$this->json_error( __( 'Insufficient balance.', 'zeko-love' ) );
			return;
		}

		$result = Zeko_Love_Pay::transfer_gift( $from_id, $to_id, $amount, $message );

		if ( empty( $result['success'] ) ) {
			$this->json_error( $result['message'] ?? __( 'Gift could not be sent.', 'zeko-love' ) );
			return;
		}

		wp_send_json_success(
			array(
				/* translators: %s: gifted amount */
				'message' => sprintf( __( 'You gifted %s!', 'zeko-love' ), Zeko_Pay_Utils::format_currency( (string) $amount ) ),
				'balance' => Zeko_Love_Pay::balance( $from_id ),
			)
		);
	}

	/**
	 * Charge user.
	 *
	 * @return array{success: bool, message?: string, tx_id?: string, tx_code?: string}
	 * @param int    $user_id User id.
	 * @param float  $amount Amount.
	 * @param string $feature Feature.
	 * @param int    $target_id Target id.
	 */
	private function charge_user( int $user_id, float $amount, string $feature, int $target_id = 0 ): array {
		if ( $amount <= 0 ) {
			return array( 'success' => true );
		}
		if ( ! class_exists( 'Zeko_Pay_SDK' ) ) {
			return array(
				'success' => false,
				'message' => __( 'Payments are not available right now.', 'zeko-love' ),
			);
		}

		try {
			$sdk    = new Zeko_Pay_SDK();
			$charge = $sdk->charge(
				$user_id,
				$amount,
				sprintf( 'Zeko Love — %s', $feature ),
				array(
					'feature'   => $feature,
					'target_id' => $target_id,
				)
			);

			return is_array( $charge ) && isset( $charge['success'] )
				? $charge
				: array(
					'success' => false,
					'message' => __( 'Payment could not be processed.', 'zeko-love' ),
				);
		} catch ( Throwable $e ) {
			return array(
				'success' => false,
				'message' => __( 'Payment could not be processed.', 'zeko-love' ),
			);
		}
	}

	/**
	 * Handle boost profile.
	 */
	public function handle_boost_profile(): void {
		if ( ! $this->verify_request() ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			$this->json_error( __( 'Please log in.', 'zeko-love' ) );
			return;
		}

		$user_id = get_current_user_id();

		if ( $this->db->is_boosted( $user_id ) ) {
			$this->json_error( __( 'Your profile is already boosted.', 'zeko-love' ) );
			return;
		}

		$pricing = zeko_love_get_pricing();
		$amount  = (float) $pricing['boost_price'];
		$hours   = max( 1, (int) $pricing['boost_duration_hours'] );

		$charge = $this->charge_user( $user_id, $amount, 'boost' );
		if ( ! $charge['success'] ) {
			$this->json_error( $charge['message'] );
			return;
		}

		$until = gmdate( 'Y-m-d H:i:s', time() + ( $hours * HOUR_IN_SECONDS ) );
		$this->db->boost_profile( $user_id, $until );
		/* translators: %d: number of boost hours */
		$this->db->log_activity( $user_id, 'profile_boosted', sprintf( __( 'You boosted your profile for %d hours', 'zeko-love' ), $hours ) );

		wp_send_json_success(
			array(
				/* translators: %d: number of boost hours */
				'message'       => sprintf( __( 'Profile boosted for %d hours!', 'zeko-love' ), $hours ),
				'boosted_until' => $until,
				'boosted'       => true,
			)
		);
	}

	/**
	 * Self-service account deletion. Removes the user from every love table
	 * and deletes the WP user account (same effect as the plugin's own delete
	 * form, now wired). All hooks fire as if wp_delete_user() ran directly.
	 */
	public function handle_delete_profile(): void {
		if ( ! $this->verify_request() ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			$this->json_error( __( 'Please log in.', 'zeko-love' ) );
			return;
		}

		$user_id = get_current_user_id();

		$this->db->delete_user_data( $user_id );
		wp_delete_user( $user_id );

		wp_send_json_success( array( 'message' => __( 'Your dating profile has been permanently deleted.', 'zeko-love' ) ) );
	}
}
