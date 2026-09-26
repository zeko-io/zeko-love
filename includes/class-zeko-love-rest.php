<?php
/**
 * REST API handler for Zeko Love.
 *
 * @package Zeko_Love
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Class Zeko_Love_REST. */
class Zeko_Love_REST {

	/**
	 * Db.
	 *
	 * @var Zeko_Love_DB Db.
	 */
	private Zeko_Love_DB $db;

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
	public function init_hooks(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Routes.
	 */
	public function register_routes(): void {
		$namespace = 'zeko-love/v1';

		register_rest_route(
			$namespace,
			'/profiles',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_profiles' ),
					'permission_callback' => 'is_user_logged_in',
					'args'                => array(
						'search'   => array(
							'type'    => 'string',
							'default' => '',
						),
						'gender'   => array(
							'type'    => 'string',
							'default' => '',
						),
						'min_age'  => array(
							'type'    => 'integer',
							'default' => 18,
						),
						'max_age'  => array(
							'type'    => 'integer',
							'default' => 99,
						),
						'orderby'  => array(
							'type'    => 'string',
							'default' => '',
						),
						'order'    => array(
							'type'    => 'string',
							'default' => '',
						),
						'page'     => array(
							'type'    => 'integer',
							'default' => 1,
							'minimum' => 1,
						),
						'per_page' => array(
							'type'    => 'integer',
							'default' => 20,
							'minimum' => 1,
							'maximum' => 100,
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_profile' ),
					'permission_callback' => 'is_user_logged_in',
				),
			)
		);

		register_rest_route(
			$namespace,
			'/profiles/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_profile' ),
				'permission_callback' => 'is_user_logged_in',
			)
		);

		register_rest_route(
			$namespace,
			'/profiles/(?P<id>\d+)/photos',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_photos' ),
					'permission_callback' => 'is_user_logged_in',
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'upload_photo' ),
					'permission_callback' => 'is_user_logged_in',
				),
			)
		);

		register_rest_route(
			$namespace,
			'/matches',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_matches' ),
					'permission_callback' => 'is_user_logged_in',
					'args'                => array(
						'status'   => array(
							'type'    => 'string',
							'default' => '',
						),
						'page'     => array(
							'type'    => 'integer',
							'default' => 1,
							'minimum' => 1,
						),
						'per_page' => array(
							'type'    => 'integer',
							'default' => 20,
							'minimum' => 1,
							'maximum' => 100,
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_match' ),
					'permission_callback' => 'is_user_logged_in',
				),
			)
		);

		register_rest_route(
			$namespace,
			'/matches/(?P<id>\d+)/respond',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'respond_match' ),
				'permission_callback' => 'is_user_logged_in',
			)
		);

		register_rest_route(
			$namespace,
			'/compatibility/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_compatibility' ),
				'permission_callback' => 'is_user_logged_in',
			)
		);

		register_rest_route(
			$namespace,
			'/messages',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_conversations' ),
					'permission_callback' => 'is_user_logged_in',
					'args'                => array(
						'page'     => array(
							'type'    => 'integer',
							'default' => 1,
							'minimum' => 1,
						),
						'per_page' => array(
							'type'    => 'integer',
							'default' => 20,
							'minimum' => 1,
							'maximum' => 100,
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'send_message' ),
					'permission_callback' => 'is_user_logged_in',
				),
			)
		);

		register_rest_route(
			$namespace,
			'/messages/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_messages' ),
				'permission_callback' => 'is_user_logged_in',
				'args'                => array(
					'page'     => array(
						'type'    => 'integer',
						'default' => 1,
						'minimum' => 1,
					),
					'per_page' => array(
						'type'    => 'integer',
						'default' => 50,
						'minimum' => 1,
						'maximum' => 200,
					),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/dates',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_dates' ),
					'permission_callback' => 'is_user_logged_in',
					'args'                => array(
						'status'   => array(
							'type'    => 'string',
							'default' => '',
						),
						'page'     => array(
							'type'    => 'integer',
							'default' => 1,
							'minimum' => 1,
						),
						'per_page' => array(
							'type'    => 'integer',
							'default' => 20,
							'minimum' => 1,
							'maximum' => 100,
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'propose_date' ),
					'permission_callback' => 'is_user_logged_in',
				),
			)
		);

		register_rest_route(
			$namespace,
			'/dates/(?P<id>\d+)/respond',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'respond_date' ),
				'permission_callback' => 'is_user_logged_in',
			)
		);

		register_rest_route(
			$namespace,
			'/interactions/block',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'block_user' ),
				'permission_callback' => 'is_user_logged_in',
			)
		);

		register_rest_route(
			$namespace,
			'/interactions/report',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'report_user' ),
				'permission_callback' => 'is_user_logged_in',
			)
		);

		register_rest_route(
			$namespace,
			'/calls/listings',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_call_listings' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'search'    => array(
						'type'    => 'string',
						'default' => '',
					),
					'call_type' => array(
						'type'    => 'string',
						'default' => '',
					),
					'orderby'   => array(
						'type'    => 'string',
						'default' => 'created_at',
					),
					'page'      => array(
						'type'    => 'integer',
						'default' => 1,
						'minimum' => 1,
					),
					'per_page'  => array(
						'type'    => 'integer',
						'default' => 12,
						'minimum' => 1,
						'maximum' => 100,
					),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/calls/listings/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_call_listing' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			$namespace,
			'/calls/plans',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_call_plans' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Logged in.
	 */
	public function is_logged_in(): bool {
		return is_user_logged_in();
	}

	/**
	 * Compatibility score between the current user and another user.
	 *
	 * @param int $user_id User id.
	 * @param int $target_id Target id.
	 */
	private function compatibility_score( int $user_id, int $target_id ): float {
		$matching = Zeko_Love::instance()->get_matching();
		return $matching ? (float) $matching->calculate_compatibility( $user_id, $target_id ) : 0.0;
	}

	// ─── Profiles ─────────────────────────────────────────────.

	/**
	 * Allow-listed public profile DTO.
	 * Never returns a full database row. Exact DOB and precise GPS coordinates
	 * are replaced by a coarse age band and are only re-attached when the
	 * caller may view sensitive data (owner, admin, mutual match).
	 *
	 * @return array
	 * @param array $profile Raw zeko_love_profiles row.
	 * @param bool  $sensitive Whether to include DOB and exact coordinates.
	 */
	public static function public_profile_dto( array $profile, bool $sensitive ): array {
		$dto = array(
			'profile_id'        => (int) ( $profile['profile_id'] ?? 0 ),
			'user_id'           => (int) ( $profile['user_id'] ?? 0 ),
			'display_name'      => (string) ( $profile['display_name'] ?? '' ),
			'bio'               => (string) ( $profile['bio'] ?? '' ),
			'gender'            => (string) ( $profile['gender'] ?? '' ),
			'interested_in'     => (string) ( $profile['interested_in'] ?? '' ),
			'relationship_goal' => (string) ( $profile['relationship_goal'] ?? '' ),
			'occupation'        => (string) ( $profile['occupation'] ?? '' ),
			'education'         => (string) ( $profile['education'] ?? '' ),
			'height_cm'         => (int) ( $profile['height_cm'] ?? 0 ),
			'smoking'           => (string) ( $profile['smoking'] ?? '' ),
			'drinking'          => (string) ( $profile['drinking'] ?? '' ),
			'has_children'      => isset( $profile['has_children'] ) ? (int) $profile['has_children'] : 0,
			'wants_children'    => isset( $profile['wants_children'] ) ? (int) $profile['wants_children'] : 0,
			'religion'          => (string) ( $profile['religion'] ?? '' ),
			'ethnicity'         => (string) ( $profile['ethnicity'] ?? '' ),
			'is_verified'       => isset( $profile['is_verified'] ) ? (int) $profile['is_verified'] : 0,
			'is_active'         => isset( $profile['is_active'] ) ? (int) $profile['is_active'] : 1,
			'last_active'       => (string) ( $profile['last_active'] ?? '' ),
			'age'               => self::coarse_age( (string) ( $profile['dob'] ?? '' ) ),
		);

		if ( $sensitive ) {
			$dto['dob']          = (string) ( $profile['dob'] ?? '' );
			$dto['location_lat'] = isset( $profile['location_lat'] ) ? (float) $profile['location_lat'] : null;
			$dto['location_lng'] = isset( $profile['location_lng'] ) ? (float) $profile['location_lng'] : null;
		} else {
			$dto['location_lat'] = null;
			$dto['location_lng'] = null;
		}

		if ( isset( $profile['photos'] ) ) {
			$dto['photos'] = $profile['photos'];
		}

		return $dto;
	}

	/**
	 * Coarse age (whole years) from a DOB. Exact birth dates are never exposed
	 * to non-sensitive viewers, so elsewhere callers only get this integer.
	 *
	 * @param string $dob Dob.
	 */
	private static function coarse_age( string $dob ): int {
		$ts = strtotime( $dob );
		if ( false === $ts ) {
			return 0;
		}
		$diff = time() - $ts;
		return (int) floor( $diff / 31556952 );
	}

	/**
	 * Whether the current viewer may see sensitive profile fields handler
	 * (DOB + exact coordinates). Mirrors the AJAX handler: owner, admin, or a
	 * mutually matched user.
	 *
	 * @param int $profile_user_id Profile user id.
	 * @param int $viewer_id Viewer id.
	 */
	private function can_view_sensitive( int $profile_user_id, int $viewer_id ): bool {
		if ( $profile_user_id === $viewer_id || current_user_can( 'manage_options' ) ) {
			return true;
		}
		return $this->db->is_mutual_match( $profile_user_id, $viewer_id );
	}

	/**
	 * Profiles.
	 *
	 * @param mixed $request Request.
	 */
	public function get_profiles( $request ) {
		$page     = max( 1, (int) $request->get_param( 'page' ) );
		$per_page = min( 100, max( 1, (int) $request->get_param( 'per_page' ) ) );

		$filters = array(
			'search'  => sanitize_text_field( $request->get_param( 'search' ) ),
			'gender'  => sanitize_text_field( $request->get_param( 'gender' ) ),
			'age_min' => (int) $request->get_param( 'min_age' ),
			'age_max' => (int) $request->get_param( 'max_age' ),
			'orderby' => sanitize_text_field( $request->get_param( 'orderby' ) ),
			'order'   => sanitize_text_field( $request->get_param( 'order' ) ),
		);

		$profiles = $this->db->search_profiles( $filters, $page, $per_page );
		$total    = $this->db->count_profiles( $filters );

		$viewer_id = get_current_user_id();
		$profiles  = array_map(
			static function ( array $row ) use ( $viewer_id ): array {
				return self::public_profile_dto( $row, false );
			},
			$profiles
		);

		$response = new WP_REST_Response( $profiles, 200 );
		$response->header( 'X-WP-Total', $total );
		$response->header( 'X-WP-TotalPages', (int) ceil( $total / $per_page ) );

		return $response;
	}

	/**
	 * Profile.
	 *
	 * @param mixed $request Request.
	 */
	public function get_profile( $request ) {
		$user_id   = absint( $request['id'] );
		$viewer_id = get_current_user_id();
		$profile   = $this->db->get_profile( $user_id );

		if ( ! $profile ) {
			return new WP_Error( 'rest_not_found', 'Profile not found.', array( 'status' => 404 ) );
		}

		// Discovery/consent gate: blocks, search opt-out, and match state.
		if ( ! $this->db->can_view_profile( $user_id, $viewer_id ) ) {
			return new WP_Error( 'rest_forbidden', 'Profile not available.', array( 'status' => 403 ) );
		}

		$dto           = self::public_profile_dto( $profile, $this->can_view_sensitive( $user_id, $viewer_id ) );
		$dto['photos'] = $this->db->get_profile_photos( $user_id );

		return new WP_REST_Response( $dto, 200 );
	}

	/**
	 * Create profile.
	 *
	 * @param mixed $request Request.
	 */
	public function create_profile( $request ) {
		$params  = $request->get_json_params();
		$user_id = get_current_user_id();

		$existing = $this->db->get_profile( $user_id );

		$data = array(
			'display_name'      => sanitize_text_field( $params['display_name'] ?? '' ),
			'bio'               => sanitize_textarea_field( $params['bio'] ?? '' ),
			'dob'               => sanitize_text_field( $params['dob'] ?? ( $params['birth_date'] ?? '' ) ),
			'gender'            => sanitize_text_field( $params['gender'] ?? '' ),
			'interested_in'     => sanitize_text_field( $params['interested_in'] ?? '' ),
			'location_lat'      => isset( $params['location_lat'] ) ? (float) $params['location_lat'] : null,
			'location_lng'      => isset( $params['location_lng'] ) ? (float) $params['location_lng'] : null,
			'relationship_goal' => sanitize_text_field( $params['relationship_goal'] ?? '' ),
			'height_cm'         => absint( $params['height_cm'] ?? 0 ),
			'occupation'        => sanitize_text_field( $params['occupation'] ?? '' ),
			'education'         => sanitize_text_field( $params['education'] ?? '' ),
			'smoking'           => sanitize_text_field( $params['smoking'] ?? '' ),
			'drinking'          => sanitize_text_field( $params['drinking'] ?? '' ),
			'has_children'      => isset( $params['has_children'] ) && '' !== $params['has_children'] ? $params['has_children'] : 0,
			'wants_children'    => isset( $params['wants_children'] ) && '' !== $params['wants_children'] ? $params['wants_children'] : 0,
			'religion'          => sanitize_text_field( $params['religion'] ?? '' ),
			'ethnicity'         => sanitize_text_field( $params['ethnicity'] ?? '' ),
		);

		$this->db->save_profile( $user_id, $data );
		$result = $this->db->get_profile( $user_id );

		do_action( 'zeko_love_profile_saved', $user_id );

		return new WP_REST_Response( $result, $existing ? 200 : 201 );
	}

	// ─── Photos ───────────────────────────────────────────────.

	/**
	 * Photos.
	 *
	 * @param mixed $request Request.
	 */
	public function get_photos( $request ) {
		$user_id   = absint( $request['id'] );
		$viewer_id = get_current_user_id();

		if ( ! $this->db->get_profile( $user_id ) ) {
			return new WP_Error( 'rest_not_found', 'Profile not found.', array( 'status' => 404 ) );
		}

		// Photos are subject to the same discovery/consent gate as the profile.
		if ( ! $this->db->can_view_profile( $user_id, $viewer_id ) ) {
			return new WP_Error( 'rest_forbidden', 'Profile not available.', array( 'status' => 403 ) );
		}

		return new WP_REST_Response( $this->db->get_profile_photos( $user_id ), 200 );
	}

	/**
	 * Upload photo.
	 *
	 * @param mixed $request Request.
	 */
	public function upload_photo( $request ) {
		$user_id = get_current_user_id();
		$owner   = absint( $request['id'] );

		if ( ! $this->db->get_profile( $owner ) ) {
			return new WP_Error( 'rest_not_found', 'Profile not found.', array( 'status' => 404 ) );
		}

		if ( $owner !== $user_id ) {
			return new WP_Error( 'rest_forbidden', 'You can only upload photos to your own profile.', array( 'status' => 403 ) );
		}

		if ( empty( $_FILES['photo'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- REST endpoint; nonce/cookie auth handled by WP core via permission_callback, file validated below (Zeko_Core_Upload::validate_file) and via media_handle_upload().
			return new WP_Error( 'rest_missing_file', 'No photo file provided.', array( 'status' => 400 ) );
		}

		if ( class_exists( 'Zeko_Core_Upload' ) ) {
			$valid = Zeko_Core_Upload::validate_file( $_FILES['photo'], 'image' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- REST endpoint; nonce/cookie auth handled by WP core via permission_callback, file validated here by Zeko_Core_Upload::validate_file.
			if ( is_wp_error( $valid ) ) {
				return new WP_Error( 'rest_invalid_file', $valid->get_error_message(), array( 'status' => 400 ) );
			}
		}

		$attachment_id = media_handle_upload( 'photo', 0 );

		if ( is_wp_error( $attachment_id ) ) {
			return new WP_Error( 'rest_upload_failed', 'Failed to upload photo.', array( 'status' => 500 ) );
		}

		$photo_id = $this->db->save_photo( $user_id, wp_get_attachment_url( $attachment_id ), false );
		$photos   = $this->db->get_profile_photos( $user_id );

		foreach ( $photos as $photo ) {
			if ( (int) $photo['photo_id'] === $photo_id ) {
				return new WP_REST_Response( $photo, 201 );
			}
		}

		return new WP_REST_Response( array( 'photo_id' => $photo_id ), 201 );
	}

	// ─── Matches ──────────────────────────────────────────────.

	/**
	 * Matches.
	 *
	 * @param mixed $request Request.
	 */
	public function get_matches( $request ) {
		$user_id  = get_current_user_id();
		$status   = sanitize_text_field( $request->get_param( 'status' ) );
		$per_page = min( 100, max( 1, (int) $request->get_param( 'per_page' ) ) );

		return new WP_REST_Response( $this->db->get_matches( $user_id, $status, $per_page ), 200 );
	}

	/**
	 * Create match.
	 *
	 * @param mixed $request Request.
	 */
	public function create_match( $request ) {
		$params    = $request->get_json_params();
		$user_id   = get_current_user_id();
		$target_id = absint( $params['target_user_id'] ?? 0 );

		if ( ! $target_id ) {
			return new WP_Error( 'rest_missing_param', 'target_user_id is required.', array( 'status' => 400 ) );
		}

		if ( $target_id === $user_id ) {
			return new WP_Error( 'rest_invalid_param', 'You cannot match with yourself.', array( 'status' => 400 ) );
		}

		$this->db->log_interaction( $user_id, $target_id, 'like' );

		$mutual     = false;
		$reciprocal = $this->db->get_interaction( $target_id, $user_id );
		$match      = $this->db->get_match( $user_id, $target_id );

		if ( $reciprocal && 'like' === $reciprocal['type'] && ! $match ) {
			$score    = $this->compatibility_score( $user_id, $target_id );
			$match_id = $this->db->create_match( $user_id, $target_id, $score );
			$match    = $match_id ? $this->db->get_match_by_id( $match_id ) : null;
			$mutual   = true;
		}

		if ( $mutual ) {
			do_action( 'zeko_love_match_created', (int) $match['match_id'], $user_id, $target_id );
		}

		return new WP_REST_Response(
			array(
				'mutual' => $mutual,
				'match'  => $match,
			),
			201
		);
	}

	/**
	 * Respond match.
	 *
	 * @param mixed $request Request.
	 */
	public function respond_match( $request ) {
		$match_id = absint( $request['id'] );
		$user_id  = get_current_user_id();
		$params   = $request->get_json_params();
		$action   = sanitize_text_field( $params['action'] ?? '' );

		if ( ! in_array( $action, array( 'accepted', 'rejected' ), true ) ) {
			return new WP_Error( 'rest_invalid_param', 'Action must be "accepted" or "rejected".', array( 'status' => 400 ) );
		}

		$match = $this->db->get_match_by_id( $match_id );

		if ( ! $match ) {
			return new WP_Error( 'rest_not_found', 'Match not found.', array( 'status' => 404 ) );
		}

		if ( (int) $match['user1_id'] !== $user_id && (int) $match['user2_id'] !== $user_id ) {
			return new WP_Error( 'rest_forbidden', 'You are not part of this match.', array( 'status' => 403 ) );
		}

		$this->db->update_match( $match_id, $action );

		do_action( 'zeko_love_match_responded', $match_id, $user_id, $action );

		return new WP_REST_Response( array( 'message' => 'Match ' . $action . '.' ), 200 );
	}

	// ─── Compatibility ────────────────────────────────────────.

	/**
	 * Compatibility.
	 *
	 * @param mixed $request Request.
	 */
	public function get_compatibility( $request ) {
		$user_id   = get_current_user_id();
		$target_id = absint( $request['id'] );

		if ( $target_id === $user_id ) {
			return new WP_Error( 'rest_invalid_param', 'Cannot check compatibility with yourself.', array( 'status' => 400 ) );
		}

		// No compatibility probing of hidden, blocked, or opted-out users.
		if ( ! $this->db->can_view_profile( $target_id, $user_id ) ) {
			return new WP_Error( 'rest_forbidden', 'Profile not available.', array( 'status' => 403 ) );
		}

		return new WP_REST_Response( array( 'score' => $this->compatibility_score( $user_id, $target_id ) ), 200 );
	}

	// ─── Messages ─────────────────────────────────────────────.

	/**
	 * Conversations.
	 *
	 * @param mixed $request Request.
	 */
	public function get_conversations( $request ) {
		$user_id  = get_current_user_id();
		$per_page = min( 100, max( 1, (int) $request->get_param( 'per_page' ) ) );

		return new WP_REST_Response( $this->db->get_conversations( $user_id, $per_page ), 200 );
	}

	/**
	 * Messages.
	 *
	 * @param mixed $request Request.
	 */
	public function get_messages( $request ) {
		$conversation_id = absint( $request['id'] );
		$user_id         = get_current_user_id();
		$per_page        = min( 200, max( 1, (int) $request->get_param( 'per_page' ) ) );

		$conversation = $this->db->get_conversation( $conversation_id );

		if ( ! $conversation ) {
			return new WP_Error( 'rest_not_found', 'Conversation not found.', array( 'status' => 404 ) );
		}

		if ( ! $this->db->is_conversation_participant( $conversation_id, $user_id ) ) {
			return new WP_Error( 'rest_forbidden', 'You are not part of this conversation.', array( 'status' => 403 ) );
		}

		return new WP_REST_Response( $this->db->get_messages( $conversation_id, $per_page ), 200 );
	}

	/**
	 * Send message.
	 *
	 * @param mixed $request Request.
	 */
	public function send_message( $request ) {
		$params      = $request->get_json_params();
		$user_id     = get_current_user_id();
		$receiver_id = absint( $params['receiver_id'] ?? 0 );
		$content     = sanitize_textarea_field( $params['content'] ?? '' );

		if ( ! $receiver_id ) {
			return new WP_Error( 'rest_missing_param', 'receiver_id is required.', array( 'status' => 400 ) );
		}

		if ( '' === $content ) {
			return new WP_Error( 'rest_missing_param', 'content is required.', array( 'status' => 400 ) );
		}

		if ( $this->db->is_blocked( $user_id, $receiver_id ) ) {
			return new WP_Error( 'rest_forbidden', 'You cannot message this user.', array( 'status' => 403 ) );
		}

		$match = $this->db->get_match( $user_id, $receiver_id );
		if ( ! $match || 'matched' !== (string) $match['status'] ) {
			return new WP_Error( 'rest_forbidden', 'You can only message your matches.', array( 'status' => 403 ) );
		}

		$conversation_id = $this->db->create_conversation( $user_id, $receiver_id );
		$message_id      = $this->db->send_message( $conversation_id, $user_id, $content );

		do_action( 'zeko_love_message_sent', $message_id, $user_id, $receiver_id );

		return new WP_REST_Response(
			array(
				'message_id'      => $message_id,
				'conversation_id' => $conversation_id,
			),
			201
		);
	}

	// ─── Dates ────────────────────────────────────────────────.

	/**
	 * Dates.
	 *
	 * @param mixed $request Request.
	 */
	public function get_dates( $request ) {
		$user_id  = get_current_user_id();
		$status   = sanitize_text_field( $request->get_param( 'status' ) );
		$per_page = min( 100, max( 1, (int) $request->get_param( 'per_page' ) ) );

		return new WP_REST_Response( $this->db->get_user_dates( $user_id, $status, $per_page ), 200 );
	}

	/**
	 * Propose date.
	 *
	 * @param mixed $request Request.
	 */
	public function propose_date( $request ) {
		$params    = $request->get_json_params();
		$user_id   = get_current_user_id();
		$target_id = absint( $params['target_user_id'] ?? 0 );
		$date_time = sanitize_text_field( $params['date_time'] ?? '' );

		if ( ! $target_id || ! $date_time ) {
			return new WP_Error( 'rest_missing_param', 'target_user_id and date_time are required.', array( 'status' => 400 ) );
		}

		if ( $target_id === $user_id ) {
			return new WP_Error( 'rest_invalid_param', 'You cannot propose a date with yourself.', array( 'status' => 400 ) );
		}

		$match = $this->db->get_match( $user_id, $target_id );
		if ( ! $match || 'matched' !== (string) $match['status'] ) {
			return new WP_Error( 'rest_forbidden', 'You can only schedule dates with your matches.', array( 'status' => 403 ) );
		}

		$parts         = array_map( 'trim', preg_split( '/[\sT]+/', $date_time, 2 ) );
		$proposed_date = $parts[0] ?? '';
		$proposed_time = $parts[1] ?? '';

		$date_id = $this->db->create_date(
			array(
				'user1_id'         => $user_id,
				'user2_id'         => $target_id,
				'proposed_date'    => $proposed_date,
				'proposed_time'    => $proposed_time,
				'duration_minutes' => absint( $params['duration_minutes'] ?? 60 ),
				'location_name'    => sanitize_text_field( $params['location'] ?? '' ),
				'location_address' => sanitize_textarea_field( $params['location_address'] ?? '' ),
				'notes'            => sanitize_textarea_field( $params['description'] ?? '' ),
				'status'           => 'pending',
				'created_by'       => $user_id,
			)
		);

		do_action( 'zeko_love_date_proposed', $date_id, $user_id, $target_id );

		return new WP_REST_Response( $this->db->get_date( $date_id ), 201 );
	}

	/**
	 * Respond date.
	 *
	 * @param mixed $request Request.
	 */
	public function respond_date( $request ) {
		$date_id = absint( $request['id'] );
		$user_id = get_current_user_id();
		$params  = $request->get_json_params();
		$action  = sanitize_text_field( $params['action'] ?? '' );

		if ( ! in_array( $action, array( 'accepted', 'rejected', 'rescheduled' ), true ) ) {
			return new WP_Error( 'rest_invalid_param', 'Action must be "accepted", "rejected", or "rescheduled".', array( 'status' => 400 ) );
		}

		$date = $this->db->get_date( $date_id );

		if ( ! $date ) {
			return new WP_Error( 'rest_not_found', 'Date not found.', array( 'status' => 404 ) );
		}

		if ( (int) $date['user1_id'] !== $user_id && (int) $date['user2_id'] !== $user_id ) {
			return new WP_Error( 'rest_forbidden', 'You are not part of this date proposal.', array( 'status' => 403 ) );
		}

		$update = array( 'status' => $action );

		if ( 'rescheduled' === $action && isset( $params['date_time'] ) ) {
			$parts                   = array_map( 'trim', preg_split( '/[\sT]+/', sanitize_text_field( $params['date_time'] ), 2 ) );
			$update['proposed_date'] = $parts[0] ?? '';
			$update['proposed_time'] = $parts[1] ?? '';
		}

		$this->db->update_date( $date_id, $update );

		do_action( 'zeko_love_date_responded', $date_id, $user_id, $action );

		return new WP_REST_Response( array( 'message' => 'Date ' . $action . '.' ), 200 );
	}

	// ─── Interactions ─────────────────────────────────────────.

	/**
	 * Block user.
	 *
	 * @param mixed $request Request.
	 */
	public function block_user( $request ) {
		$params    = $request->get_json_params();
		$user_id   = get_current_user_id();
		$target_id = absint( $params['target_user_id'] ?? 0 );

		if ( ! $target_id ) {
			return new WP_Error( 'rest_missing_param', 'target_user_id is required.', array( 'status' => 400 ) );
		}

		if ( $target_id === $user_id ) {
			return new WP_Error( 'rest_invalid_param', 'You cannot block yourself.', array( 'status' => 400 ) );
		}

		$this->db->log_interaction( $user_id, $target_id, 'block' );

		do_action( 'zeko_love_user_blocked', $user_id, $target_id );

		return new WP_REST_Response( array( 'message' => 'User blocked.' ), 201 );
	}

	/**
	 * Report user.
	 *
	 * @param mixed $request Request.
	 */
	public function report_user( $request ) {
		$params    = $request->get_json_params();
		$user_id   = get_current_user_id();
		$target_id = absint( $params['target_user_id'] ?? 0 );
		$reason    = sanitize_textarea_field( $params['reason'] ?? '' );

		if ( ! $target_id || ! $reason ) {
			return new WP_Error( 'rest_missing_param', 'target_user_id and reason are required.', array( 'status' => 400 ) );
		}

		$report_id = $this->db->log_interaction( $user_id, $target_id, 'report', $reason );

		do_action( 'zeko_love_user_reported', $report_id, $user_id, $target_id );

		return new WP_REST_Response(
			array(
				'message'   => 'User reported.',
				'report_id' => $report_id,
			),
			201
		);
	}

	// ─── Calls & Availability ─────────────────────────────────.

	/**
	 * Call listings.
	 *
	 * @param mixed $request Request.
	 */
	public function get_call_listings( $request ) {
		$args = array(
			'status'    => 'active',
			'search'    => sanitize_text_field( $request->get_param( 'search' ) ),
			'call_type' => sanitize_text_field( $request->get_param( 'call_type' ) ),
			'orderby'   => sanitize_text_field( $request->get_param( 'orderby' ) ),
			'page'      => (int) $request->get_param( 'page' ),
			'per_page'  => (int) $request->get_param( 'per_page' ),
		);

		$listings = $this->db->get_call_listings( $args );
		$total    = $this->db->count_call_listings( array( 'status' => 'active' ) );
		$calls    = Zeko_Love::instance()->get_calls();

		$enriched = array();
		foreach ( $listings as $listing ) {
			$enriched[] = $calls->enrich_listing( $listing );
		}

		return new WP_REST_Response(
			array(
				'listings' => $enriched,
				'total'    => $total,
				'page'     => $args['page'],
				'pages'    => max( 1, (int) ceil( $total / $args['per_page'] ) ),
			),
			200
		);
	}

	/**
	 * Call listing.
	 *
	 * @param mixed $request Request.
	 */
	public function get_call_listing( $request ) {
		$listing_id = absint( $request['id'] );
		$listing    = $this->db->get_call_listing( $listing_id );

		if ( ! $listing || 'active' !== $listing['status'] ) {
			return new WP_Error( 'rest_not_found', 'Listing not found.', array( 'status' => 404 ) );
		}

		$calls            = Zeko_Love::instance()->get_calls();
		$data             = $calls->enrich_listing( $listing );
		$data['slots']    = $this->db->get_call_slots( $listing_id );
		$data['reviews']  = $this->db->get_call_reviews( (int) $listing['user_id'] );
		$data['bookable'] = $calls->listing_bookable( $listing );

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Call plans.
	 *
	 * @param mixed $request Request.
	 */
	public function get_call_plans( $request ) {
		unset( $request );
		return new WP_REST_Response( $this->db->get_call_plans( true ), 200 );
	}
}
