<?php
/**
 * Zeko Love — Calls & Availability addon.
 *
 * Members can publish "available for calls" listings (video/voice), sell
 * booked call slots paid from their Zeko Pay wallet, earn payouts after
 * delivering the call, and review each other.
 *
 * @package Zeko_Love
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Class Zeko_Love_Calls. */
class Zeko_Love_Calls {

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
		add_shortcode( 'zeko_love_calls', array( $this, 'shortcode_calls' ) );
		add_shortcode( 'zeko_love_calls_strip', array( $this, 'shortcode_calls_strip' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue calls frontend assets on pages using the calls or strip shortcode.
	 */
	public function enqueue_assets(): void {
		if ( ! is_page() && ! is_singular() ) {
			return;
		}
		global $post;
		if ( ! $post || ( ! has_shortcode( $post->post_content, 'zeko_love_calls' ) && ! has_shortcode( $post->post_content, 'zeko_love_calls_strip' ) ) ) {
			return;
		}

		wp_enqueue_style( 'zeko-love-calls', ZEKO_LOVE_PLUGIN_URL . 'assets/css/zeko-love-calls.css', array( 'zeko-love' ), ZEKO_LOVE_VERSION );
		wp_enqueue_script( 'zeko-love-calls', ZEKO_LOVE_PLUGIN_URL . 'assets/js/zeko-love-calls.js', array( 'jquery', 'zeko-love' ), ZEKO_LOVE_VERSION, true );

		$config = zeko_love_calls_config();
		wp_localize_script(
			'zeko-love-calls',
			'zekoLoveCalls',
			array(
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( 'zeko_love_nonce' ),
				'currency'    => $config['currency'],
				'pageUrl'     => zeko_love_page_url( 'dating-calls' ),
				'messagesUrl' => zeko_love_page_url( 'dating-messages' ),
				'i18n'        => array(
					'confirmDelete' => __( 'Delete this listing? This cannot be undone.', 'zeko-love' ),
					'confirmCancel' => __( 'Cancel this booking? The buyer will be refunded.', 'zeko-love' ),
					'error'         => __( 'Something went wrong.', 'zeko-love' ),
					'noPlans'       => __( 'No subscription plans are available right now.', 'zeko-love' ),
					'buyPlan'       => __( 'Purchase plan', 'zeko-love' ),
					'noSlots'       => __( 'Set your weekly availability slots first.', 'zeko-love' ),
					'loading'       => __( 'Loading…', 'zeko-love' ),
					'newListing'    => __( 'New listing', 'zeko-love' ),
					'editListing'   => __( 'Edit listing', 'zeko-love' ),
					'selectTime'    => __( 'Select a time', 'zeko-love' ),
					'listings'      => __( 'Listings', 'zeko-love' ),
					'minutes'       => __( 'Minutes sold', 'zeko-love' ),
					'views'         => __( 'Views', 'zeko-love' ),
				),
			)
		);
	}

	/**
	 * [zeko_love_calls] — marketplace + seller dashboard.
	 *
	 * @param mixed $atts Atts.
	 */
	public function shortcode_calls( $atts ): string {
		unset( $atts );
		if ( ! is_user_logged_in() ) {
			return '<p>' . esc_html__( 'Please log in to browse call listings.', 'zeko-love' ) . '</p>';
		}

		if ( isset( $_GET['view'] ) && 'listing' === $_GET['view'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$listing_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $listing_id > 0 ) {
				return $this->render_listing_detail( $listing_id );
			}
		}

		$template = ZEKO_LOVE_PLUGIN_PATH . 'templates/dating-calls.php';
		if ( ! file_exists( $template ) ) {
			return '<p>' . esc_html__( 'Calls template not found.', 'zeko-love' ) . '</p>';
		}

		ob_start();
		include $template;
		return ob_get_clean();
	}

	/**
	 * [zeko_love_calls_strip] — horizontal strip of active listings used to
	 * cross-link from other ecosystem surfaces (Shop catalog, dashboard).
	 *
	 * @param mixed $atts Atts.
	 */
	public function shortcode_calls_strip( $atts ): string {
		$atts = shortcode_atts(
			array(
				'limit' => 8,
			),
			$atts,
			'zeko_love_calls_strip'
		);

		$listings = $this->db->get_call_listings(
			array(
				'status'   => 'active',
				'per_page' => max( 1, absint( $atts['limit'] ) ),
			)
		);

		if ( empty( $listings ) ) {
			return '';
		}

		$cards = '';
		foreach ( $listings as $listing ) {
			$cards .= $this->render_listing_card( $listing );
		}

		ob_start();
		?>
		<div class="zl-calls-strip">
			<div class="zl-calls-strip-head">
				<h3>&#9825; <?php esc_html_e( 'Live calls — book a private video/voice call', 'zeko-love' ); ?></h3>
				<a href="<?php echo esc_url( zeko_love_page_url( 'dating-calls' ) ); ?>"><?php esc_html_e( 'View all', 'zeko-love' ); ?> &rarr;</a>
			</div>
			<div class="zl-calls-strip-track"><?php echo $cards; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Addon configuration.
	 */
	public function config(): array {
		$defaults = array(
			'commission_pct'    => 20.0,
			'free_cancel_hours' => 24,
			'currency'          => 'USD',
			'booking_window'    => 30,
		);
		$settings = get_option( 'zeko_love_calls_settings', array() );
		return wp_parse_args( is_array( $settings ) ? $settings : array(), $defaults );
	}

	/**
	 * Whether a listing is bookable right now.
	 *
	 * @return array{ok:bool,message:string}
	 * @param array $listing Listing.
	 */
	public function listing_bookable( array $listing ): array {
		if ( 'active' !== ( $listing['status'] ?? '' ) ) {
			return array(
				'ok'      => false,
				'message' => __( 'This listing is not active.', 'zeko-love' ),
			);
		}
		if ( ! empty( $listing['plan_ends_at'] ) && strtotime( $listing['plan_ends_at'] . ' UTC' ) < time() ) {
			return array(
				'ok'      => false,
				'message' => __( 'This listing plan has expired.', 'zeko-love' ),
			);
		}
		$plan = $this->db->get_call_plan( (int) ( $listing['plan_id'] ?? 0 ) );
		if ( $plan && (int) $plan['max_usage_minutes'] > 0 && (int) $listing['usage_minutes'] >= (int) $plan['max_usage_minutes'] ) {
			return array(
				'ok'      => false,
				'message' => __( 'This listing has reached its usage limit.', 'zeko-love' ),
			);
		}
		$slots = $this->db->get_call_slots( (int) $listing['listing_id'] );
		if ( empty( $slots ) ) {
			return array(
				'ok'      => false,
				'message' => __( 'The seller has not set availability yet.', 'zeko-love' ),
			);
		}
		return array(
			'ok'      => true,
			'message' => '',
		);
	}

	/**
	 * Enrich a listing row with seller display data + review stats.
	 *
	 * @param array $listing Listing.
	 */
	public function enrich_listing( array $listing ): array {
		$listing['seller_name']   = Zeko_Love_Pay::display_name( (int) $listing['user_id'] );
		$listing['seller_avatar'] = get_avatar_url( (int) $listing['user_id'], array( 'size' => 96 ) );
		$stats                    = $this->db->get_call_review_stats( (int) $listing['user_id'] );
		$listing['rating']        = $stats['avg'];
		$listing['review_count']  = $stats['count'];
		$listing['plan']          = $this->db->get_call_plan( (int) ( $listing['plan_id'] ?? 0 ) );
		$listing['bookable']      = $this->listing_bookable( $listing );
		return $listing;
	}

	/**
	 * Render a grid of listing cards (Explore tab).
	 *
	 * @param array $listings Listings.
	 */
	public function render_listing_cards( array $listings ): string {
		if ( empty( $listings ) ) {
			return '<div class="zl-empty-state"><span class="dashicons dashicons-phone"></span><h3>' . esc_html__( 'No listings found.', 'zeko-love' ) . '</h3></div>';
		}

		$html = '<div class="zl-calls-grid">';
		foreach ( $listings as $listing ) {
			$html .= $this->render_listing_card( $listing );
		}
		$html .= '</div>';
		return $html;
	}

	/**
	 * Render a single listing card.
	 *
	 * @param array $listing Listing.
	 */
	public function render_listing_card( array $listing ): string {
		$listing = $this->enrich_listing( $listing );

		$photo = ! empty( $listing['cover_photo_url'] )
			? $listing['cover_photo_url']
			: $listing['seller_avatar'];

		$price = (float) $listing['price_per_minute'];
		$min   = (int) $listing['min_duration'];
		$max   = (int) $listing['max_duration'];

		$type_label = 'voice' === $listing['call_type']
			? __( 'Voice call', 'zeko-love' )
			: __( 'Video call', 'zeko-love' );

		$url = add_query_arg(
			array(
				'view' => 'listing',
				'id'   => (int) $listing['listing_id'],
			),
			zeko_love_page_url( 'dating-calls' )
		);

		ob_start();
		?>
		<div class="zl-call-card" data-listing-id="<?php echo esc_attr( $listing['listing_id'] ); ?>">
			<a class="zl-call-card-media" href="<?php echo esc_url( $url ); ?>">
				<img class="zl-call-card-photo" src="<?php echo esc_url( $photo ); ?>" alt="" loading="lazy" />
				<span class="zl-call-type-badge"><?php echo esc_html( $type_label ); ?></span>
				<span class="zl-love-badge">&#9825; <?php esc_html_e( 'Zeko Love', 'zeko-love' ); ?></span>
			</a>
			<div class="zl-call-card-body">
				<h3><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $listing['title'] ); ?></a></h3>
				<div class="zl-call-card-seller">
					<img src="<?php echo esc_url( $listing['seller_avatar'] ); ?>" alt="" width="28" height="28" loading="lazy" />
					<span><?php echo esc_html( $listing['seller_name'] ); ?></span>
					<?php if ( $listing['review_count'] > 0 ) : ?>
						<span class="zl-call-rating" title="<?php /* translators: 1: average star rating. 2: number of reviews */ echo esc_attr( sprintf( __( '%1$.1f stars from %2$d reviews', 'zeko-love' ), $listing['rating'], $listing['review_count'] ) ); ?>">
							&#9733; <?php echo esc_html( number_format( (float) $listing['rating'], 1 ) ); ?> (<?php echo esc_html( $listing['review_count'] ); ?>)
						</span>
					<?php endif; ?>
				</div>
				<div class="zl-call-card-meta">
					<span class="zl-call-price"><?php echo esc_html( zeko_love_format_money( $price, $listing['currency'] ) ); ?>/<?php esc_html_e( 'min', 'zeko-love' ); ?></span>
					<span><?php /* translators: 1: minimum duration in minutes. 2: maximum duration in minutes */ echo esc_html( sprintf( __( '%1$d–%2$d min', 'zeko-love' ), $min, $max ) ); ?></span>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render a listing detail page (booking + slots + reviews).
	 *
	 * @param int $listing_id Listing id.
	 */
	public function render_listing_detail( int $listing_id ): string {
		$listing = $this->db->get_call_listing( $listing_id );
		if ( ! $listing ) {
			return '<div class="zl-empty-state"><h3>' . esc_html__( 'Listing not found.', 'zeko-love' ) . '</h3></div>';
		}

		$listing  = $this->enrich_listing( $listing );
		$slots    = $this->db->get_call_slots( $listing_id );
		$bookable = $listing['bookable'];
		$reviews  = $this->db->get_call_reviews( (int) $listing['user_id'] );

		$photo = ! empty( $listing['cover_photo_url'] )
			? $listing['cover_photo_url']
			: $listing['seller_avatar'];

		$back_url = zeko_love_page_url( 'dating-calls' );
		$msg_url  = add_query_arg( 'to', (int) $listing['user_id'], zeko_love_page_url( 'dating-messages' ) );

		$this->db->increment_listing_views( $listing_id );

		ob_start();
		?>
		<div class="zl-listing-detail zl-card" data-listing-id="<?php echo esc_attr( $listing['listing_id'] ); ?>"
			data-slots="<?php echo esc_attr( wp_json_encode( $slots ) ); ?>">
			<a class="zl-back-link" href="<?php echo esc_url( $back_url ); ?>">&larr; <?php esc_html_e( 'Back to listings', 'zeko-love' ); ?></a>

			<div class="zl-detail-layout">
				<div class="zl-detail-photo">
					<img class="zl-photo" src="<?php echo esc_url( $photo ); ?>" alt="" />
					<span class="zl-call-type-badge"><?php echo esc_html( 'voice' === $listing['call_type'] ? __( 'Voice call', 'zeko-love' ) : __( 'Video call', 'zeko-love' ) ); ?></span>
					<span class="zl-love-badge">&#9825; <?php esc_html_e( 'Zeko Love', 'zeko-love' ); ?></span>
					<?php if ( $listing['rating'] > 0 ) : ?>
						<span class="zl-badge zl-badge-score">&#9733; <?php echo esc_html( number_format( (float) $listing['rating'], 1 ) ); ?></span>
					<?php endif; ?>
				</div>

				<div class="zl-detail-info">
					<h2><?php echo esc_html( $listing['title'] ); ?></h2>
					<div class="zl-listing-seller">
						<img src="<?php echo esc_url( $listing['seller_avatar'] ); ?>" alt="" width="40" height="40" loading="lazy" />
						<span><?php echo esc_html( $listing['seller_name'] ); ?></span>
						<?php if ( $listing['review_count'] > 0 ) : ?>
							<span class="zl-call-rating"><?php /* translators: %d: number of reviews */ echo esc_html( sprintf( __( '%d review(s)', 'zeko-love' ), $listing['review_count'] ) ); ?></span>
						<?php endif; ?>
						<a class="zl-btn zl-btn-secondary zl-btn-sm" href="<?php echo esc_url( $msg_url ); ?>"><?php /* translators: %d: number of reviews */ esc_html_e( 'Message', 'zeko-love' ); ?></a>
					</div>

					<div class="zl-card-meta-row">
						<span class="zl-call-price"><?php echo esc_html( zeko_love_format_money( (float) $listing['price_per_minute'], $listing['currency'] ) ); ?>/<?php esc_html_e( 'min', 'zeko-love' ); ?></span>
						<span class="zl-goal"><?php /* translators: 1: minimum duration in minutes. 2: maximum duration in minutes */ echo esc_html( sprintf( __( '%1$d–%2$d min', 'zeko-love' ), (int) $listing['min_duration'], (int) $listing['max_duration'] ) ); ?></span>
						<span class="zl-goal"><?php echo esc_html( strtoupper( $listing['provider'] ) ); ?></span>
					</div>

					<?php if ( ! empty( $listing['description'] ) ) : ?>
						<p class="zl-bio zl-detail-bio"><?php echo nl2br( esc_html( $listing['description'] ) ); ?></p>
					<?php endif; ?>

					<?php if ( ! empty( $listing['youtube_url'] ) || ! empty( $listing['vimeo_url'] ) ) : ?>
						<div class="zl-listing-video">
							<?php
							$embed = '';
							if ( ! empty( $listing['youtube_url'] ) ) {
								$embed = $this->youtube_embed( $listing['youtube_url'] );
							} elseif ( ! empty( $listing['vimeo_url'] ) ) {
								$embed = $this->vimeo_embed( $listing['vimeo_url'] );
							}
							?>
							<?php if ( $embed ) : ?>
								<iframe src="<?php echo esc_url( $embed ); ?>" frameborder="0" allowfullscreen loading="lazy"></iframe>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<?php if ( ! empty( $listing['intro_video_url'] ) ) : ?>
						<video class="zl-listing-intro" controls preload="metadata" src="<?php echo esc_url( $listing['intro_video_url'] ); ?>"></video>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( get_current_user_id() === (int) $listing['user_id'] ) : ?>
				<div class="zl-actions" style="border-top:1px solid var(--zlove-gray-100);padding-top:16px;">
					<a class="zl-btn zl-btn-secondary" href="<?php echo esc_url( add_query_arg( 'my_listings', '1', zeko_love_page_url( 'dating-calls' ) ) ); ?>">
						<?php esc_html_e( 'Manage this listing', 'zeko-love' ); ?>
					</a>
				</div>
			<?php else : ?>
				<div class="zl-listing-booking">
					<h3><?php esc_html_e( 'Book a call', 'zeko-love' ); ?></h3>
					<?php if ( $bookable['ok'] ) : ?>
						<div class="zl-booking-form" data-price="<?php echo esc_attr( $listing['price_per_minute'] ); ?>" data-min="<?php echo esc_attr( (int) $listing['min_duration'] ); ?>" data-max="<?php echo esc_attr( (int) $listing['max_duration'] ); ?>">
							<div class="zl-field">
								<label><?php esc_html_e( 'Date', 'zeko-love' ); ?></label>
								<input type="date" class="zl-input zl-booking-date" min="<?php echo esc_attr( gmdate( 'Y-m-d', time() + DAY_IN_SECONDS ) ); ?>" max="<?php echo esc_attr( gmdate( 'Y-m-d', time() + (int) $this->config()['booking_window'] * DAY_IN_SECONDS ) ); ?>" />
							</div>
							<div class="zl-field">
								<label><?php esc_html_e( 'Start time', 'zeko-love' ); ?></label>
								<select class="zl-input zl-booking-time"><option value=""><?php esc_html_e( 'Select date first', 'zeko-love' ); ?></option></select>
							</div>
							<div class="zl-field">
								<label><?php esc_html_e( 'Duration (minutes)', 'zeko-love' ); ?></label>
								<select class="zl-input zl-booking-duration">
									<?php for ( $i = (int) $listing['min_duration']; $i <= (int) $listing['max_duration']; $i += 5 ) : ?>
										<option value="<?php echo esc_attr( $i ); ?>"><?php echo esc_html( $i ); ?></option>
									<?php endfor; ?>
								</select>
							</div>
							<p class="zl-booking-total"></p>
							<div class="zl-booking-error zl-notice notice-error inline" style="display:none;"><p></p></div>
							<button type="button" class="zl-btn zl-btn-primary zl-book-btn"><?php esc_html_e( 'Book & Pay', 'zeko-love' ); ?></button>
							<p class="zl-booking-note"><?php esc_html_e( 'Payment is taken from your Zeko wallet and held until the call is completed. You can cancel within the free-cancel window.', 'zeko-love' ); ?></p>
						</div>
					<?php else : ?>
						<p class="zl-booking-note"><?php echo esc_html( $bookable['message'] ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="zl-listing-reviews">
				<h3><?php esc_html_e( 'Reviews', 'zeko-love' ); ?></h3>
				<?php if ( empty( $reviews ) ) : ?>
					<p class="zl-booking-note"><?php esc_html_e( 'No reviews yet.', 'zeko-love' ); ?></p>
				<?php else : ?>
					<?php foreach ( $reviews as $review ) : ?>
						<div class="zl-review-item">
							<span class="zl-review-stars"><?php echo esc_html( str_repeat( '&#9733;', (int) $review['rating'] ) ); ?></span>
							<span class="zl-review-comment"><?php echo esc_html( $review['comment'] ); ?></span>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Convert a YouTube URL to an embed URL.
	 *
	 * @param string $url Url.
	 */
	private function youtube_embed( string $url ): string {
		$id = '';
		if ( preg_match( '~(?:youtube\.com/watch\?v=|youtu\.be/)([\w-]{6,})~', $url, $m ) ) {
			$id = $m[1];
		}
		return $id ? 'https://www.youtube.com/embed/' . rawurlencode( $id ) : '';
	}

	/**
	 * Convert a Vimeo URL to an embed URL.
	 *
	 * @param string $url Url.
	 */
	private function vimeo_embed( string $url ): string {
		if ( preg_match( '~vimeo\.com/(\d+)~', $url, $m ) ) {
			return 'https://player.vimeo.com/video/' . rawurlencode( $m[1] );
		}
		return '';
	}

	/**
	 * Pay the seller for a completed booking (net of platform commission).
	 *
	 * @return string Payout reference, or '' on failure.
	 * @param array $booking Booking row.
	 */
	public function payout_seller( array $booking ): string {
		if ( ! class_exists( 'Zeko_Pay_Ledger' ) ) {
			return '';
		}
		if ( ! empty( $booking['payout_ref'] ) ) {
			return (string) $booking['payout_ref'];
		}

		$config     = $this->config();
		$commission = (float) $config['commission_pct'];
		$net        = bcsub( (string) $booking['amount'], bcmul( (string) $booking['amount'], (string) ( $commission / 100 ), 4 ), 2 );

		$ledger    = Zeko_Pay_Ledger::instance();
		$wallet_id = $ledger->get_or_create_wallet( (int) $booking['seller_id'] );
		$ref_id    = 'love-call-payout-' . $booking['booking_id'] . '-' . wp_generate_password( 8, false );

		$result = $ledger->credit(
			$wallet_id,
			$net,
			$ref_id,
			'deposit',
			'',
			array(
				'source'       => 'zeko_love',
				'booking_id'   => (int) $booking['booking_id'],
				'listing_id'   => (int) $booking['listing_id'],
				'buyer_id'     => (int) $booking['buyer_id'],
				'platform_fee' => $commission . '%',
				'gross'        => (string) $booking['amount'],
				'type'         => 'call_payout',
			)
		);

		if ( empty( $result['success'] ) ) {
			return '';
		}

		return $ref_id;
	}
}

/**
 * Format money using the Zeko Pay formatter when available.
 *
 * @param float  $amount Amount.
 * @param string $currency Currency.
 */
function zeko_love_format_money( float $amount, string $currency = 'USD' ): string {
	if ( class_exists( 'Zeko_Pay_Utils' ) ) {
		return Zeko_Pay_Utils::format_currency( (string) $amount, $currency );
	}
	return $currency . ' ' . number_format( $amount, 2 );
}

/**
 * Calls addon configuration (shared by admin + frontend).
 */
function zeko_love_calls_config(): array {
	$defaults = array(
		'commission_pct'    => 20.0,
		'free_cancel_hours' => 24,
		'currency'          => 'USD',
		'booking_window'    => 30,
	);
	$settings = get_option( 'zeko_love_calls_settings', array() );
	return wp_parse_args( is_array( $settings ) ? $settings : array(), $defaults );
}
