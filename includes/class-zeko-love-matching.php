<?php
/**
 * Matching engine for Zeko Love.
 *
 * Calculates compatibility scores and finds potential matches
 * using weighted dimensional analysis.
 *
 * @package Zeko_Love
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Class Zeko_Love_Matching. */
class Zeko_Love_Matching {

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
	private function init_hooks(): void {
		add_action( 'wp_ajax_zeko_love_calculate_compatibility', array( $this, 'handle_calculate_compatibility' ) );
	}

	/**
	 * Calculate compatibility.
	 *
	 * @param int $user1_id User1 id.
	 * @param int $user2_id User2 id.
	 */
	public function calculate_compatibility( int $user1_id, int $user2_id ): float {
		$profile1 = $this->db->get_profile( $user1_id );
		$profile2 = $this->db->get_profile( $user2_id );

		if ( ! $profile1 || ! $profile2 ) {
			return 0.0;
		}

		$interests1 = $this->db->get_interests( $user1_id );
		$interests2 = $this->db->get_interests( $user2_id );

		$total  = 0.30 * $this->calculate_interest_overlap( $interests1, $interests2 );
		$total += 0.15 * $this->calculate_age_preference( $profile1, $profile2 );
		$total += 0.20 * $this->calculate_goal_match( $profile1, $profile2 );
		$total += 0.20 * $this->calculate_lifestyle_compatibility( $profile1, $profile2 );
		$total += 0.15 * $this->calculate_location_proximity( $profile1, $profile2 );

		return round( $total, 2 );
	}

	/**
	 * Calculate interest overlap.
	 *
	 * @param array $interests1 Interests1.
	 * @param array $interests2 Interests2.
	 */
	private function calculate_interest_overlap( array $interests1, array $interests2 ): float {
		$values1 = array_unique(
			array_map(
				function ( $i ) {
					return $i['category'] . ':' . $i['value'];
				},
				$interests1
			)
		);
		$values2 = array_unique(
			array_map(
				function ( $i ) {
					return $i['category'] . ':' . $i['value'];
				},
				$interests2
			)
		);

		if ( empty( $values1 ) && empty( $values2 ) ) {
			return 50.0;
		}

		$intersection = count( array_intersect( $values1, $values2 ) );
		$union        = count( $values1 ) + count( $values2 );

		if ( 0 === $union ) {
			return 50.0;
		}

		return ( 2 * $intersection / $union ) * 100;
	}

	/**
	 * Calculate age preference.
	 *
	 * @param array $profile1 Profile1.
	 * @param array $profile2 Profile2.
	 */
	private function calculate_age_preference( array $profile1, array $profile2 ): float {
		$age1 = $this->calculate_age( $profile1['dob'] ?? '' );
		$age2 = $this->calculate_age( $profile2['dob'] ?? '' );

		if ( $age1 < 0 || $age2 < 0 ) {
			return 50.0;
		}

		$range1_min = isset( $profile1['age_range_min'] ) ? (int) $profile1['age_range_min'] : null;
		$range1_max = isset( $profile1['age_range_max'] ) ? (int) $profile1['age_range_max'] : null;
		$range2_min = isset( $profile2['age_range_min'] ) ? (int) $profile2['age_range_min'] : null;
		$range2_max = isset( $profile2['age_range_max'] ) ? (int) $profile2['age_range_max'] : null;

		$has_range1 = null !== $range1_min && null !== $range1_max;
		$has_range2 = null !== $range2_min && null !== $range2_max;

		if ( ! $has_range1 && ! $has_range2 ) {
			return 50.0;
		}

		$in_range1 = $has_range1 ? ( $age2 >= $range1_min && $age2 <= $range1_max ) : true;
		$in_range2 = $has_range2 ? ( $age1 >= $range2_min && $age1 <= $range2_max ) : true;

		if ( $in_range1 && $in_range2 ) {
			return 100.0;
		}

		if ( $in_range1 || $in_range2 ) {
			return 50.0;
		}

		return 0.0;
	}

	/**
	 * Calculate goal match.
	 *
	 * @param array $profile1 Profile1.
	 * @param array $profile2 Profile2.
	 */
	private function calculate_goal_match( array $profile1, array $profile2 ): float {
		$goal1 = strtolower( trim( $profile1['relationship_goal'] ?? '' ) );
		$goal2 = strtolower( trim( $profile2['relationship_goal'] ?? '' ) );

		if ( '' === $goal1 || '' === $goal2 ) {
			return 50.0;
		}

		if ( $goal1 === $goal2 ) {
			return 100.0;
		}

		$compatible = array(
			'marriage'  => array( 'long-term', 'long term' ),
			'long-term' => array( 'marriage' ),
			'long term' => array( 'marriage' ),
		);

		if ( isset( $compatible[ $goal1 ] ) && in_array( $goal2, $compatible[ $goal1 ], true ) ) {
			return 70.0;
		}

		return 0.0;
	}

	/**
	 * Calculate lifestyle compatibility.
	 *
	 * @param array $profile1 Profile1.
	 * @param array $profile2 Profile2.
	 */
	private function calculate_lifestyle_compatibility( array $profile1, array $profile2 ): float {
		$score = 0;

		if ( ! empty( $profile1['smoking'] ) && ! empty( $profile2['smoking'] ) && $profile1['smoking'] === $profile2['smoking'] ) {
			++$score;
		}

		if ( ! empty( $profile1['drinking'] ) && ! empty( $profile2['drinking'] ) && $profile1['drinking'] === $profile2['drinking'] ) {
			++$score;
		}

		if ( isset( $profile1['has_children'], $profile2['has_children'] ) && (bool) $profile1['has_children'] === (bool) $profile2['has_children'] ) {
			++$score;
		}

		return ( $score / 3 ) * 100;
	}

	/**
	 * Calculate location proximity.
	 *
	 * @param array $profile1 Profile1.
	 * @param array $profile2 Profile2.
	 */
	private function calculate_location_proximity( array $profile1, array $profile2 ): float {
		if ( empty( $profile1['location_lat'] ) || empty( $profile1['location_lng'] )
			|| empty( $profile2['location_lat'] ) || empty( $profile2['location_lng'] ) ) {
			return 50.0;
		}

		$distance = $this->haversine_distance(
			(float) $profile1['location_lat'],
			(float) $profile1['location_lng'],
			(float) $profile2['location_lat'],
			(float) $profile2['location_lng']
		);

		if ( $distance <= 10 ) {
			return 100.0;
		}

		if ( $distance <= 50 ) {
			return 50.0;
		}

		if ( $distance <= 100 ) {
			return 25.0;
		}

		return 5.0;
	}

	/**
	 * Find matches.
	 *
	 * @param int $user_id User id.
	 * @param int $limit Limit.
	 */
	public function find_matches( int $user_id, int $limit = 20 ): array {
		$profile = $this->db->get_profile( $user_id );

		if ( ! $profile || empty( $profile['interested_in'] ) ) {
			return array();
		}

		$candidates = array();
		$page       = 1;
		$per_page   = 200;

		$filters = array(
			'gender' => $profile['interested_in'],
		);

		while ( true ) {
			$batch = $this->db->search_profiles( $filters, $page, $per_page );
			if ( empty( $batch ) ) {
				break;
			}
			$candidates = array_merge( $candidates, $batch );
			++$page;
		}

		$results = array();

		foreach ( $candidates as $candidate ) {
			$candidate_id = (int) $candidate['user_id'];

			if ( $candidate_id === $user_id ) {
				continue;
			}

			if ( empty( $candidate['is_verified'] ) || empty( $candidate['is_active'] ) ) {
				continue;
			}

			$interaction_ab = $this->db->get_interaction( $user_id, $candidate_id );
			if ( $interaction_ab ) {
				continue;
			}

			$interaction_ba = $this->db->get_interaction( $candidate_id, $user_id );
			if ( $interaction_ba ) {
				continue;
			}

			if ( $this->db->get_match( $user_id, $candidate_id ) ) {
				continue;
			}

			$score = $this->calculate_compatibility( $user_id, $candidate_id );
			$age   = $this->calculate_age( $candidate['dob'] ?? '' );

			$photo_url = '';
			$photos    = $this->db->get_profile_photos( $candidate_id );
			foreach ( $photos as $photo ) {
				if ( ! empty( $photo['is_primary'] ) ) {
					$photo_url = $photo['photo_url'];
					break;
				}
			}
			if ( '' === $photo_url && ! empty( $photos[0]['photo_url'] ) ) {
				$photo_url = $photos[0]['photo_url'];
			}

			$results[] = array(
				'user_id'      => $candidate_id,
				'score'        => $score,
				'display_name' => $candidate['display_name'] ?: ( $candidate['user_display_name'] ?? '' ),
				'age'          => $age,
				'photo_url'    => $photo_url,
			);
		}

		usort(
			$results,
			function ( $a, $b ) {
				return $b['score'] <=> $a['score'];
			}
		);

		/**
		 * Re-prioritize the daily picks before the top-N slice (premium
		 * modules may pin featured profiles first).
		 *
		 * @param int[] $results Sorted candidate rows (user_id + score).
		 * @param int   $user_id Viewer id.
		 */
		$results = apply_filters( 'zeko_love_daily_picks_results', $results, $user_id );

		return array_slice( $results, 0, $limit );
	}

	/**
	 * Handle calculate compatibility.
	 */
	public function handle_calculate_compatibility(): void {
		if ( ! check_ajax_referer( 'zeko_love_matching_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'zeko-love' ) ) );
			return;
		}

		$user1_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		$user2_id = get_current_user_id();

		if ( ! $user1_id || ! $user2_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid user IDs.', 'zeko-love' ) ) );
			return;
		}

		$score = $this->calculate_compatibility( $user1_id, $user2_id );

		wp_send_json_success( array( 'score' => $score ) );
	}

	/**
	 * Haversine distance.
	 *
	 * @param float $lat1 Lat1.
	 * @param float $lng1 Lng1.
	 * @param float $lat2 Lat2.
	 * @param float $lng2 Lng2.
	 */
	public function haversine_distance( float $lat1, float $lng1, float $lat2, float $lng2 ): float {
		$earth_radius = 6371;

		$d_lat = deg2rad( $lat2 - $lat1 );
		$d_lng = deg2rad( $lng2 - $lng1 );

		$a = sin( $d_lat / 2 ) * sin( $d_lat / 2 )
			+ cos( deg2rad( $lat1 ) ) * cos( deg2rad( $lat2 ) )
			* sin( $d_lng / 2 ) * sin( $d_lng / 2 );

		return $earth_radius * 2 * atan2( sqrt( $a ), sqrt( 1 - $a ) );
	}

	/**
	 * Calculate age.
	 *
	 * @param string $dob Dob.
	 */
	public function calculate_age( string $dob ): int {
		if ( '' === $dob || '0000-00-00' === $dob ) {
			return 0;
		}

		$birth = new DateTime( $dob );
		$now   = new DateTime();

		return (int) $now->diff( $birth )->y;
	}
}
