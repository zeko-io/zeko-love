<?php
/**
 * Public frontend handler for Zeko Love.
 *
 * Registers shortcodes and renders dating-related templates.
 *
 * @package Zeko_Love
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Class Zeko_Love_Public. */
class Zeko_Love_Public {

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

		add_action( 'init', array( $this, 'register_shortcodes' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register all shortcodes.
	 */
	public function register_shortcodes(): void {
		add_shortcode( 'zeko_love_browse', array( $this, 'shortcode_browse' ) );
		add_shortcode( 'zeko_love_profile', array( $this, 'shortcode_profile' ) );
		add_shortcode( 'zeko_love_matches', array( $this, 'shortcode_matches' ) );
		add_shortcode( 'zeko_love_messages', array( $this, 'shortcode_messages' ) );
		add_shortcode( 'zeko_love_dates', array( $this, 'shortcode_dates' ) );
		add_shortcode( 'zeko_love_settings', array( $this, 'shortcode_settings' ) );
	}

	/**
	 * Enqueue frontend assets.
	 */
	public function enqueue_assets(): void {
		if ( is_singular() || is_page() ) {
			global $post;
			if ( $post && (
				has_shortcode( $post->post_content, 'zeko_love_browse' )
				|| has_shortcode( $post->post_content, 'zeko_love_profile' )
				|| has_shortcode( $post->post_content, 'zeko_love_matches' )
				|| has_shortcode( $post->post_content, 'zeko_love_messages' )
				|| has_shortcode( $post->post_content, 'zeko_love_dates' )
				|| has_shortcode( $post->post_content, 'zeko_love_settings' )
				|| has_shortcode( $post->post_content, 'zeko_love_calls' )
			) ) {
				wp_enqueue_style(
					'zeko-love',
					ZEKO_LOVE_PLUGIN_URL . 'assets/css/zeko-love.css',
					array( 'zeko-core' ),
					ZEKO_LOVE_VERSION
				);
				wp_enqueue_script(
					'zeko-love',
					ZEKO_LOVE_PLUGIN_URL . 'assets/js/zeko-love.js',
					array( 'jquery' ),
					ZEKO_LOVE_VERSION,
					true
				);
				$timezone = '';
				if ( is_user_logged_in() ) {
					$user_tz = get_user_meta( get_current_user_id(), 'zeko_timezone', true );
					if ( $user_tz ) {
						$timezone = $user_tz;
					}
				}
				wp_localize_script(
					'zeko-love',
					'zekoLove',
					array(
						'ajaxUrl'                => admin_url( 'admin-ajax.php' ),
						'nonce'                  => wp_create_nonce( 'zeko_love_nonce' ),
						'timezone'               => $timezone,
						'currency'               => apply_filters( 'zeko_love_currency', 'USD' ),
						'withdraw_otp_threshold' => class_exists( 'Zeko_Pay_Trust' ) ? (string) Zeko_Pay_Trust::instance()->get_setting( 'withdrawal_otp_threshold' ) : '0',
						'i18n'                   => array(
							'confirm'       => __( 'Are you sure?', 'zeko-love' ),
							'liked'         => __( 'Liked!', 'zeko-love' ),
							'matched'       => __( "It's a match!", 'zeko-love' ),
							'error'         => __( 'Something went wrong. Please try again.', 'zeko-love' ),
							'superLiked'    => __( 'Super Liked!', 'zeko-love' ),
							'itsAMatch'     => __( "It's a Match!", 'zeko-love' ),
							'matchMessage'  => __( 'You matched!', 'zeko-love' ),
							'noMatches'     => __( 'No matches yet.', 'zeko-love' ),
							'noDates'       => __( 'No dates scheduled.', 'zeko-love' ),
							'confirmCancel' => __( 'Cancel this date?', 'zeko-love' ),
							'date'          => __( 'Date', 'zeko-love' ),
							'time'          => __( 'Time', 'zeko-love' ),
							'location'      => __( 'Location', 'zeko-love' ),
							'notes'         => __( 'Notes', 'zeko-love' ),
							'send'          => __( 'Send', 'zeko-love' ),
							'proposeDate'   => __( 'Propose a Date', 'zeko-love' ),
							'boosted'       => __( 'Profile boosted!', 'zeko-love' ),
							'boostTitle'    => __( 'Profile Boost', 'zeko-love' ),
							'confirmBoost'  => __( 'Boost your profile? This will charge your wallet.', 'zeko-love' ),
						),
					)
				);
			}
		}
	}

	/**
	 * [zeko_love_browse] — Browse dating profiles.
	 *
	 * @param mixed $atts Atts.
	 */
	public function shortcode_browse( $atts ): string {
		unset( $atts );
		if ( ! is_user_logged_in() ) {
			return '<p>' . esc_html__( 'Please log in to browse profiles.', 'zeko-love' ) . '</p>';
		}

		if ( isset( $_GET['view'] ) && 'profile' === $_GET['view'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$target_id = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $target_id > 0 ) {
				return $this->render_profile_detail( $target_id );
			}
		}

		$template = ZEKO_LOVE_PLUGIN_PATH . 'templates/dating-browse.php';
		if ( ! file_exists( $template ) ) {
			return '<p>' . esc_html__( 'Browse template not found.', 'zeko-love' ) . '</p>';
		}

		ob_start();
		include $template;
		return ob_get_clean();
	}

	/**
	 * URL for viewing another member's dating profile.
	 *
	 * @return string
	 * @param int $user_id Target user id.
	 */
	public function profile_view_url( int $user_id ): string {
		return add_query_arg(
			array(
				'view'    => 'profile',
				'user_id' => $user_id,
			),
			zeko_love_page_url( 'dating-browse' )
		);
	}

	/**
	 * [zeko_love_profile] — My dating profile editing page.
	 *
	 * @param _ $_atts atts.
	 */
	public function shortcode_profile( $_atts ): string {
		if ( ! is_user_logged_in() ) {
			return '<p>' . esc_html__( 'Please log in to edit your profile.', 'zeko-love' ) . '</p>';
		}

		$template = ZEKO_LOVE_PLUGIN_PATH . 'templates/dating-profile.php';
		if ( ! file_exists( $template ) ) {
			return '<p>' . esc_html__( 'Profile template not found.', 'zeko-love' ) . '</p>';
		}

		$user_id   = get_current_user_id();
		$profile   = $this->db->get_profile( $user_id ) ?: array();
		$photos    = $this->db->get_profile_photos( $user_id );
		$interests = wp_list_pluck( $this->db->get_interests( $user_id ), 'value' );

		ob_start();
		include $template;
		return ob_get_clean();
	}

	/**
	 * [zeko_love_matches] — View matches.
	 *
	 * @param _ $_atts atts.
	 */
	public function shortcode_matches( $_atts ): string {
		if ( ! is_user_logged_in() ) {
			return '<p>' . esc_html__( 'Please log in to view your matches.', 'zeko-love' ) . '</p>';
		}

		$template = ZEKO_LOVE_PLUGIN_PATH . 'templates/dating-matches.php';
		if ( ! file_exists( $template ) ) {
			return '<p>' . esc_html__( 'Matches template not found.', 'zeko-love' ) . '</p>';
		}

		ob_start();
		include $template;
		return ob_get_clean();
	}

	/**
	 * [zeko_love_messages] — Messaging inbox.
	 *
	 * @param _ $_atts atts.
	 */
	public function shortcode_messages( $_atts ): string {
		if ( ! is_user_logged_in() ) {
			return '<p>' . esc_html__( 'Please log in to view messages.', 'zeko-love' ) . '</p>';
		}

		$template = ZEKO_LOVE_PLUGIN_PATH . 'templates/dating-messages.php';
		if ( ! file_exists( $template ) ) {
			return '<p>' . esc_html__( 'Messages template not found.', 'zeko-love' ) . '</p>';
		}

		ob_start();
		include $template;
		return ob_get_clean();
	}

	/**
	 * [zeko_love_dates] — Date scheduling.
	 *
	 * @param _ $_atts atts.
	 */
	public function shortcode_dates( $_atts ): string {
		if ( ! is_user_logged_in() ) {
			return '<p>' . esc_html__( 'Please log in to view your dates.', 'zeko-love' ) . '</p>';
		}

		$template = ZEKO_LOVE_PLUGIN_PATH . 'templates/dating-schedule.php';
		if ( ! file_exists( $template ) ) {
			return '<p>' . esc_html__( 'Dates template not found.', 'zeko-love' ) . '</p>';
		}

		ob_start();
		include $template;
		return ob_get_clean();
	}

	/**
	 * [zeko_love_settings] — Dating settings.
	 *
	 * @param mixed $atts Atts.
	 */
	public function shortcode_settings( $atts ): string {
		unset( $atts );
		if ( ! is_user_logged_in() ) {
			return '<p>' . esc_html__( 'Please log in to manage settings.', 'zeko-love' ) . '</p>';
		}

		$template = ZEKO_LOVE_PLUGIN_PATH . 'templates/dating-settings.php';
		if ( ! file_exists( $template ) ) {
			return '<p>' . esc_html__( 'Settings template not found.', 'zeko-love' ) . '</p>';
		}

		$user_id = get_current_user_id();

		$settings = get_user_meta( $user_id, 'zeko_love_user_settings', true );
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

		$blocked_users = array();
		foreach ( $this->db->get_blocked_users( $user_id ) as $blocked_id ) {
			$user = get_userdata( (int) $blocked_id );
			if ( $user ) {
				$blocked_users[] = array(
					'id'   => (int) $blocked_id,
					'name' => $user->display_name,
				);
			}
		}

		$profile   = $this->db->get_profile( $user_id ) ?: array();
		$photos    = $this->db->get_profile_photos( $user_id );
		$interests = wp_list_pluck( $this->db->get_interests( $user_id ), 'value' );

		$payout = get_user_meta( $user_id, 'zeko_love_payout', true );
		$payout = is_array( $payout ) ? $payout : array();

		$currency = 'USD';
		if ( function_exists( 'zeko_love_calls_config' ) ) {
			$config   = zeko_love_calls_config();
			$currency = $config['currency'] ?? 'USD';
		}

		$balance        = '0.00';
		$withdrawals    = array();
		$min_withdrawal = (float) get_option( 'zeko_pay_min_withdrawal', 10 );

		if ( class_exists( 'Zeko_Pay_SDK' ) ) {
			try {
				$sdk         = new Zeko_Pay_SDK();
				$balance     = $sdk->get_balance( $user_id );
				$withdrawal  = $sdk->get_transactions(
					$user_id,
					array(
						'type'  => 'withdrawal',
						'limit' => 20,
					)
				);
				$withdrawals = isset( $withdrawal['transactions'] ) ? $withdrawal['transactions'] : array();
			} catch ( \Exception $e ) {
				$balance = '0.00';
			}
		}

		ob_start();
		include $template;
		return ob_get_clean();
	}

	/**
	 * Render a grid of dating profile cards (verified + online + compatibility badges).
	 *
	 * @return string
	 * @param array $profiles Profile rows from search_profiles() or similar.
	 */
	public function render_profile_cards( array $profiles ): string {
		if ( empty( $profiles ) ) {
			return '<div class="zl-empty-state"><span class="dashicons dashicons-heart"></span><h3>'
				. esc_html__( 'No profiles found.', 'zeko-love' ) . '</h3></div>';
		}

		$viewer_id = get_current_user_id();
		$photos    = $this->db->get_primary_photos( wp_list_pluck( $profiles, 'user_id' ) );
		$matching  = new Zeko_Love_Matching( $this->db );

		$html = '';
		foreach ( $profiles as $profile ) {
			$html .= $this->render_profile_card( $profile, $photos, $matching, $viewer_id );
		}

		return $html;
	}

	/**
	 * Render a single dating profile card.
	 *
	 * @return string
	 * @param array              $profile Profile row.
	 * @param array              $photos Map of user_id => photo row.
	 * @param Zeko_Love_Matching $matching Matching engine (for age + compatibility).
	 * @param int                $viewer_id Current user id.
	 */
	public function render_profile_card( array $profile, array $photos, Zeko_Love_Matching $matching, int $viewer_id ): string {
		$id        = (int) $profile['user_id'];
		$photo_row = $photos[ $id ] ?? array();
		$photo_url = ! empty( $photo_row['photo_url'] )
			? $photo_row['photo_url']
			: get_avatar_url( $id, array( 'size' => 480 ) );

		$verified = ! empty( $profile['is_verified'] )
			|| ( isset( $photo_row['verification_status'] ) && 'approved' === $photo_row['verification_status'] );

		$name   = ! empty( $profile['display_name'] ) ? $profile['display_name'] : ( $profile['user_display_name'] ?? '' );
		$age    = $matching->calculate_age( $profile['dob'] ?? '' );
		$score  = $matching->calculate_compatibility( $viewer_id, $id );
		$status = $this->online_status( $profile['last_active'] ?? '' );

		$meta_parts = array();
		if ( ! empty( $profile['gender'] ) ) {
			$meta_parts[] = ucfirst( (string) $profile['gender'] );
		}
		if ( empty( $meta_parts ) && $age <= 0 ) {
			$meta_parts[] = __( 'Age hidden', 'zeko-love' );
		}

		$goal = $this->goal_label( $profile['relationship_goal'] ?? '' );

		$bio = wp_trim_words( wp_strip_all_tags( $profile['bio'] ?? '' ), 22 );

		$name_display = $name . ( $age > 0 ? ', ' . $age : '' );
		$profile_url  = $this->profile_view_url( $id );

		ob_start();
		?>
		<div class="zl-profile-card" data-profile-id="<?php echo esc_attr( $id ); ?>">
			<div class="zl-card-photo">
				<a class="zl-profile-link" href="<?php echo esc_url( $profile_url ); ?>" aria-label="<?php /* translators: %s: user display name */ echo esc_attr( sprintf( __( 'View profile of %s', 'zeko-love' ), $name_display ) ); ?>">
					<img class="zl-photo" src="<?php echo esc_url( $photo_url ); ?>" alt="" loading="lazy" />
				</a>
				<span class="zl-status-badge <?php echo esc_attr( $status['key'] ); ?>">
					<?php echo esc_html( $status['label'] ); ?>
				</span>
				<?php if ( $verified ) : ?>
					<span class="zl-badge zl-verified-badge">
						<span aria-hidden="true">&#10003;</span> <?php echo esc_html__( 'Verified', 'zeko-love' ); ?>
					</span>
				<?php endif; ?>
				<?php echo apply_filters( 'zeko_love_card_badges', '', $profile, $id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Premium badge markup is escaped by the callback (empty by default). ?>
			</div>
			<div class="zl-info">
				<h3><a class="zl-profile-link" href="<?php echo esc_url( $profile_url ); ?>"><?php echo esc_html( $name_display ); ?></a></h3>
				<?php if ( ! empty( $meta_parts ) ) : ?>
					<div class="zl-meta"><?php echo esc_html( implode( " \u{00B7} ", $meta_parts ) ); ?></div>
				<?php endif; ?>
				<div class="zl-card-meta-row">
					<?php if ( $goal ) : ?>
						<span class="zl-goal"><?php echo esc_html( $goal ); ?></span>
					<?php endif; ?>
					<span class="zl-badge zl-badge-score"><?php echo esc_html( (int) round( $score ) ); ?>%</span>
				</div>
				<?php if ( $bio ) : ?>
					<p class="zl-bio"><?php echo esc_html( $bio ); ?></p>
				<?php endif; ?>
			</div>
			<div class="zl-actions">
				<button type="button" class="zl-btn zl-btn-secondary zl-pass-btn" data-profile-id="<?php echo esc_attr( $id ); ?>">
					<?php esc_html_e( 'Pass', 'zeko-love' ); ?>
				</button>
				<button type="button" class="zl-btn zl-btn-primary zl-like-btn" data-profile-id="<?php echo esc_attr( $id ); ?>">
					<?php esc_html_e( 'Like', 'zeko-love' ); ?>
				</button>
				<button type="button" class="zl-btn zl-btn-danger zl-superlike-btn" data-profile-id="<?php echo esc_attr( $id ); ?>" title="<?php esc_attr_e( 'Super Like', 'zeko-love' ); ?>">
					&#10084;
				</button>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render a read-only dating profile detail view for another member.
	 *
	 * @return string
	 * @param int $target_id User id whose profile is viewed.
	 */
	public function render_profile_detail( int $target_id ): string {
		$viewer_id = get_current_user_id();
		if ( ! $viewer_id || $target_id === $viewer_id ) {
			return '<p>' . esc_html__( 'This profile is not available.', 'zeko-love' ) . '</p>';
		}

		$profile = $this->db->get_profile( $target_id );
		if ( ! $profile || $this->db->is_blocked( $viewer_id, $target_id ) || $this->db->is_blocked( $target_id, $viewer_id ) ) {
			return '<p>' . esc_html__( 'This profile is not available.', 'zeko-love' ) . '</p>';
		}

		$matching = new Zeko_Love_Matching( $this->db );

		$photos  = $this->db->get_profile_photos( $target_id );
		$primary = '';
		foreach ( $photos as $photo ) {
			if ( ! empty( $photo['is_primary'] ) ) {
				$primary = (string) $photo['photo_url'];
				break;
			}
		}
		if ( '' === $primary && ! empty( $photos[0]['photo_url'] ) ) {
			$primary = (string) $photos[0]['photo_url'];
		}
		if ( '' === $primary ) {
			$primary = get_avatar_url( $target_id, array( 'size' => 480 ) );
		}

		$verified = ! empty( $profile['is_verified'] );
		$name     = ! empty( $profile['display_name'] ) ? $profile['display_name'] : get_the_author_meta( 'display_name', $target_id );
		$age      = $matching->calculate_age( (string) ( $profile['dob'] ?? '' ) );
		$score    = $matching->calculate_compatibility( $viewer_id, $target_id );
		$status   = $this->online_status( (string) ( $profile['last_active'] ?? '' ) );
		$goal     = $this->goal_label( (string) ( $profile['relationship_goal'] ?? '' ) );
		$bio      = trim( (string) ( $profile['bio'] ?? '' ) );

		$name_display = $name . ( $age > 0 ? ', ' . $age : '' );

		$meta_parts = array();
		if ( ! empty( $profile['gender'] ) ) {
			$meta_parts[] = ucfirst( (string) $profile['gender'] );
		}
		if ( ! empty( $profile['occupation'] ) ) {
			$meta_parts[] = (string) $profile['occupation'];
		}

		$interests        = $this->db->get_interests( $target_id );
		$interest_values  = array();
		$viewer_interests = array();
		if ( $viewer_id ) {
			foreach ( $this->db->get_interests( $viewer_id ) as $viewer_interest ) {
				$viewer_interests[] = strtolower( trim( (string) $viewer_interest['value'] ) );
			}
		}
		$mutual = array();
		foreach ( $interests as $interest ) {
			$value             = (string) $interest['value'];
			$interest_values[] = $value;
			if ( in_array( strtolower( trim( $value ) ), $viewer_interests, true ) ) {
				$mutual[] = $value;
			}
		}

		$back_url = zeko_love_page_url( 'dating-browse' );
		$msg_url  = add_query_arg( 'to', $target_id, zeko_love_page_url( 'dating-messages' ) );

		ob_start();
		?>
		<div class="zl-profile-detail zl-card" data-profile-id="<?php echo esc_attr( $target_id ); ?>">
			<a class="zl-back-link" href="<?php echo esc_url( $back_url ); ?>">
				&larr; <?php echo esc_html__( 'Back to Browse', 'zeko-love' ); ?>
			</a>

			<div class="zl-detail-layout">
				<div class="zl-detail-photo">
					<img class="zl-photo" src="<?php echo esc_url( $primary ); ?>" alt="" />
					<span class="zl-status-badge <?php echo esc_attr( $status['key'] ); ?>">
						<?php echo esc_html( $status['label'] ); ?>
					</span>
					<?php if ( $verified ) : ?>
						<span class="zl-badge zl-verified-badge">
							<span aria-hidden="true">&#10003;</span> <?php echo esc_html__( 'Verified', 'zeko-love' ); ?>
						</span>
					<?php endif; ?>
				</div>

				<div class="zl-detail-info">
					<h2><?php echo esc_html( $name_display ); ?></h2>

					<?php if ( ! empty( $meta_parts ) ) : ?>
						<div class="zl-meta"><?php echo esc_html( implode( " \u{00B7} ", $meta_parts ) ); ?></div>
					<?php endif; ?>

					<div class="zl-card-meta-row">
						<span class="zl-badge zl-badge-score"><?php echo esc_html( (int) round( $score ) ); ?>%</span>
						<?php if ( $goal ) : ?>
							<span class="zl-goal"><?php echo esc_html( $goal ); ?></span>
						<?php endif; ?>
					</div>

					<?php if ( $bio ) : ?>
						<p class="zl-bio zl-detail-bio"><?php echo nl2br( esc_html( $bio ) ); ?></p>
					<?php endif; ?>

					<?php if ( ! empty( $interest_values ) ) : ?>
						<div class="zl-detail-interests">
							<h3><?php echo esc_html__( 'Interests', 'zeko-love' ); ?></h3>
							<div class="zl-chips">
								<?php foreach ( $interest_values as $value ) : ?>
									<span class="zl-chip"><?php echo esc_html( $value ); ?></span>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>

					<?php if ( ! empty( $mutual ) ) : ?>
						<div class="zl-mutual">
							<span class="dashicons dashicons-heart"></span>
							<?php /* translators: %s: comma-separated list of mutual interests */ echo esc_html( sprintf( __( 'Mutual interest: %s', 'zeko-love' ), implode( ', ', array_slice( $mutual, 0, 3 ) ) ) ); ?>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<div class="zl-actions">
				<a class="zl-btn zl-btn-secondary" href="<?php echo esc_url( $msg_url ); ?>">
					<span class="dashicons dashicons-email-alt" aria-hidden="true"></span>
					<?php esc_html_e( 'Message', 'zeko-love' ); ?>
				</a>
				<?php if ( Zeko_Love_Pay::active() ) : ?>
					<button type="button" class="zl-btn zl-btn-secondary zl-gift-btn" data-profile-id="<?php echo esc_attr( $target_id ); ?>">
						<span class="dashicons dashicons-heart" aria-hidden="true"></span>
						<?php esc_html_e( 'Send Gift', 'zeko-love' ); ?>
					</button>
				<?php endif; ?>
				<?php
				$call_listings = $this->db->get_call_listings(
					array(
						'status'   => 'active',
						'user_id'  => $target_id,
						'per_page' => 1,
					)
				);
				if ( ! empty( $call_listings ) ) :
					$call_url = add_query_arg(
						array(
							'view' => 'listing',
							'id'   => (int) $call_listings[0]['listing_id'],
						),
						zeko_love_page_url( 'dating-calls' )
					);
					?>
					<a class="zl-btn zl-btn-secondary" href="<?php echo esc_url( $call_url ); ?>">
						<span class="dashicons dashicons-phone" aria-hidden="true"></span>
						<?php
						/* translators: %s: formatted price per minute */
						echo esc_html( sprintf( __( 'Call — %s/min', 'zeko-love' ), zeko_love_format_money( (float) $call_listings[0]['price_per_minute'], $call_listings[0]['currency'] ) ) );
						?>
					</a>
				<?php endif; ?>
				<button type="button" class="zl-btn zl-btn-secondary zl-pass-btn" data-profile-id="<?php echo esc_attr( $target_id ); ?>">
					<?php esc_html_e( 'Pass', 'zeko-love' ); ?>
				</button>
				<button type="button" class="zl-btn zl-btn-primary zl-like-btn" data-profile-id="<?php echo esc_attr( $target_id ); ?>">
					<?php esc_html_e( 'Like', 'zeko-love' ); ?>
				</button>
				<button type="button" class="zl-btn zl-btn-danger zl-superlike-btn" data-profile-id="<?php echo esc_attr( $target_id ); ?>" title="<?php esc_attr_e( 'Super Like', 'zeko-love' ); ?>">
					&#10084;
				</button>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Derive online/recent/offline status from a last_active timestamp.
	 *
	 * @return array{key:string,label:string}
	 * @param string $last_active UTC datetime or empty.
	 */
	private function online_status( string $last_active ): array {
		if ( '' === $last_active || '0000-00-00 00:00:00' === $last_active ) {
			return array(
				'key'   => 'offline',
				'label' => __( 'Offline', 'zeko-love' ),
			);
		}

		$ts = strtotime( $last_active . ' UTC' );
		if ( false === $ts ) {
			return array(
				'key'   => 'offline',
				'label' => __( 'Offline', 'zeko-love' ),
			);
		}

		$diff = time() - $ts;
		if ( $diff < 15 * MINUTE_IN_SECONDS ) {
			return array(
				'key'   => 'online',
				'label' => __( 'Online now', 'zeko-love' ),
			);
		}
		if ( $diff < DAY_IN_SECONDS ) {
			return array(
				'key'   => 'recent',
				'label' => __( 'Active recently', 'zeko-love' ),
			);
		}

		return array(
			'key'   => 'offline',
			'label' => __( 'Offline', 'zeko-love' ),
		);
	}

	/**
	 * Map a relationship-goal slug to its display label.
	 *
	 * @param string $goal Goal.
	 */
	private function goal_label( string $goal ): string {
		$labels = array(
			'casual'     => __( 'Casual', 'zeko-love' ),
			'long-term'  => __( 'Long-term', 'zeko-love' ),
			'long term'  => __( 'Long-term', 'zeko-love' ),
			'marriage'   => __( 'Marriage', 'zeko-love' ),
			'friendship' => __( 'Friendship', 'zeko-love' ),
			'not-sure'   => __( 'Not sure', 'zeko-love' ),
		);
		return $labels[ strtolower( trim( $goal ) ) ] ?? '';
	}
}
