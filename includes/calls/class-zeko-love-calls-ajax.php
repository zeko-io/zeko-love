<?php
/**
 * Zeko Love — Calls & Availability AJAX handlers.
 *
 * @package Zeko_Love
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Class Zeko_Love_Calls_Ajax. */
class Zeko_Love_Calls_Ajax {

	/**
	 * Db.
	 *
	 * @var Zeko_Love_DB Db.
	 */
	private Zeko_Love_DB $db;

	/**
	 * Calls.
	 *
	 * @var Zeko_Love_Calls Calls.
	 */
	private Zeko_Love_Calls $calls;

	/**
	 * Construct.
	 *
	 * @param Zeko_Love_DB    $db Db.
	 * @param Zeko_Love_Calls $calls Calls.
	 */
	public function __construct( Zeko_Love_DB $db, Zeko_Love_Calls $calls ) {
		$this->db    = $db;
		$this->calls = $calls;
		$this->init_hooks();
	}

	/**
	 * Init hooks.
	 */
	private function init_hooks(): void {
		add_action( 'wp_ajax_zeko_love_calls_get_listings', array( $this, 'handle_get_listings' ) );
		add_action( 'wp_ajax_zeko_love_calls_get_plans', array( $this, 'handle_get_plans' ) );
		add_action( 'wp_ajax_zeko_love_calls_buy_plan', array( $this, 'handle_buy_plan' ) );
		add_action( 'wp_ajax_zeko_love_calls_save_listing', array( $this, 'handle_save_listing' ) );
		add_action( 'wp_ajax_zeko_love_calls_delete_listing', array( $this, 'handle_delete_listing' ) );
		add_action( 'wp_ajax_zeko_love_calls_set_listing_status', array( $this, 'handle_set_listing_status' ) );
		add_action( 'wp_ajax_zeko_love_calls_get_my_listings', array( $this, 'handle_get_my_listings' ) );
		add_action( 'wp_ajax_zeko_love_calls_book', array( $this, 'handle_book_call' ) );
		add_action( 'wp_ajax_zeko_love_calls_get_my_bookings', array( $this, 'handle_get_my_bookings' ) );
		add_action( 'wp_ajax_zeko_love_calls_cancel_booking', array( $this, 'handle_cancel_booking' ) );
		add_action( 'wp_ajax_zeko_love_calls_confirm_booking', array( $this, 'handle_confirm_booking' ) );
		add_action( 'wp_ajax_zeko_love_calls_complete_booking', array( $this, 'handle_complete_booking' ) );
		add_action( 'wp_ajax_zeko_love_calls_submit_review', array( $this, 'handle_submit_review' ) );
		add_action( 'wp_ajax_zeko_love_calls_get_earnings', array( $this, 'handle_get_earnings' ) );
	}

	// ---------------------------------------------------------------------.
	// Guards.
	// ---------------------------------------------------------------------.

	/**
	 * Verify.
	 */
	private function verify(): bool {
		$nonce = $_POST['nonce'] ?? ( $_POST['_wpnonce'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce compared via wp_verify_nonce(); nonces are not unslashed/sanitized before verification.
		if ( ! wp_verify_nonce( $nonce, 'zeko_love_nonce' ) ) {
			$this->json_error( __( 'Security check failed.', 'zeko-love' ) );
			return false;
		}
		if ( ! is_user_logged_in() ) {
			$this->json_error( __( 'Please log in.', 'zeko-love' ) );
			return false;
		}
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
	 * User name.
	 *
	 * @param int $user_id User id.
	 */
	private function user_name( int $user_id ): string {
		return Zeko_Love_Pay::display_name( $user_id );
	}

	/**
	 * Notify.
	 *
	 * @param int    $user_id User id.
	 * @param string $type Type.
	 * @param string $message Message.
	 * @param int    $actor_id Actor id.
	 */
	private function notify( int $user_id, string $type, string $message, int $actor_id ): void {
		$this->db->add_notification( $user_id, $type, $message, $actor_id, 0, 'calls' );
	}

	// ---------------------------------------------------------------------.
	// Explore.
	// ---------------------------------------------------------------------.

	/**
	 * Handle get listings.
	 */
	public function handle_get_listings(): void {
		if ( ! $this->verify() ) {
			return;
		}

		// Edit-mode: return a single listing (with slots) for the seller form.
		if ( ! empty( $_POST['for_edit'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify() (called at top of every AJAX handler).
			$listing_id = absint( $_POST['listing_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify() (called at top of every AJAX handler).
			$listing    = $this->db->get_call_listing( $listing_id );
			if ( ! $listing || get_current_user_id() !== (int) $listing['user_id'] ) {
				$this->json_error( __( 'Listing not found.', 'zeko-love' ) );
				return;
			}
			$listing['slots'] = $this->db->get_call_slots( $listing_id );
			wp_send_json_success( array( 'listing' => $listing ) );
		}

		$page = max( 1, absint( $_POST['page'] ?? 1 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify() (called at top of every AJAX handler).
		$args = array(
			'status'    => 'active',
			'page'      => $page,
			'per_page'  => 12,
			'call_type' => sanitize_text_field( wp_unslash( $_POST['call_type'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify() (called at top of every AJAX handler).
			'search'    => sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify() (called at top of every AJAX handler).
			'orderby'   => sanitize_text_field( wp_unslash( $_POST['orderby'] ?? 'created_at' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify() (called at top of every AJAX handler).
		);

		$listings = $this->db->get_call_listings( $args );
		$total    = $this->db->count_call_listings( array( 'status' => 'active' ) );
		$pages    = max( 1, (int) ceil( $total / 12 ) );

		wp_send_json_success(
			array(
				'html'  => $this->calls->render_listing_cards( $listings ),
				'page'  => $page,
				'pages' => $pages,
				'total' => $total,
			)
		);
	}

	// ---------------------------------------------------------------------.
	// Seller: plans.
	// ---------------------------------------------------------------------.

	/**
	 * Handle get plans.
	 */
	public function handle_get_plans(): void {
		if ( ! $this->verify() ) {
			return;
		}

		$plans = $this->db->get_call_plans( true );
		if ( empty( $plans ) ) {
			$this->json_error( __( 'No subscription plans are available right now.', 'zeko-love' ) );
			return;
		}

		$currency = $this->calls->config()['currency'];
		$html     = '';

		foreach ( $plans as $plan ) {
			$html .= '<div class="zl-plan-card" data-plan-id="' . esc_attr( $plan['plan_id'] ) . '">';
			$html .= '<h4>' . esc_html( $plan['name'] ) . '</h4>';
			$html .= '<div class="zl-plan-price">' . esc_html( zeko_love_format_money( (float) $plan['price'], $currency ) ) . '</div>';
			/* translators: %d: plan duration in days */
			$html .= '<div class="zl-plan-meta">' . esc_html( sprintf( __( '%d days', 'zeko-love' ), (int) $plan['duration_days'] ) );
			if ( (int) $plan['max_usage_minutes'] > 0 ) {
				/* translators: %d: maximum usage minutes */
				$html .= ' &middot; ' . esc_html( sprintf( __( '%d min usage', 'zeko-love' ), (int) $plan['max_usage_minutes'] ) );
			}
			$html .= '</div>';
			if ( ! empty( $plan['features'] ) ) {
				$html .= '<p class="zl-plan-features">' . esc_html( $plan['features'] ) . '</p>';
			}
			$html .= '<button type="button" class="zl-btn zl-btn-primary zl-buy-plan-btn">' . esc_html__( 'Purchase plan', 'zeko-love' ) . '</button>';
			$html .= '</div>';
		}

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * Handle buy plan.
	 */
	public function handle_buy_plan(): void {
		if ( ! $this->verify() ) {
			return;
		}

		$user_id    = get_current_user_id();
		$plan_id    = absint( $_POST['plan_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify() (called at top of every AJAX handler).
		$listing_id = absint( $_POST['listing_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify() (called at top of every AJAX handler).

		$plan = $this->db->get_call_plan( $plan_id );
		if ( ! $plan || empty( $plan['is_active'] ) ) {
			$this->json_error( __( 'That plan is not available.', 'zeko-love' ) );
			return;
		}

		$listing = $this->db->get_call_listing( $listing_id );
		if ( ! $listing || (int) $listing['user_id'] !== $user_id ) {
			$this->json_error( __( 'Listing not found.', 'zeko-love' ) );
			return;
		}

		if ( ! Zeko_Love_Pay::active() ) {
			$this->json_error( __( 'Payments are not available right now.', 'zeko-love' ) );
			return;
		}

		$charge = Zeko_Love_Pay::charge(
			$user_id,
			(float) $plan['price'],
			sprintf( 'Zeko Love — Call plan: %s', $plan['name'] ),
			array(
				'feature'    => 'call_plan',
				'plan_id'    => $plan_id,
				'listing_id' => $listing_id,
			)
		);
		if ( empty( $charge['success'] ) ) {
			$this->json_error( $charge['message'] ?? __( 'Payment could not be processed.', 'zeko-love' ) );
			return;
		}

		$plan_ends_at = gmdate( 'Y-m-d H:i:s', time() + ( (int) $plan['duration_days'] * DAY_IN_SECONDS ) );
		$this->db->save_call_listing(
			array(
				'plan_id'      => $plan_id,
				'plan_ends_at' => $plan_ends_at,
				'status'       => 'active',
			),
			$listing_id
		);
		/* translators: 1: listing title. 2: plan name */
		$this->db->log_activity( $user_id, 'call_plan_purchased', sprintf( __( 'You activated "%1$s" with the %2$s plan', 'zeko-love' ), $listing['title'], $plan['name'] ) );

		wp_send_json_success(
			array(
				'message'      => __( 'Plan activated! Your listing is now live.', 'zeko-love' ),
				'plan_ends_at' => $plan_ends_at,
				'balance'      => Zeko_Love_Pay::balance( $user_id ),
			)
		);
	}

	// ---------------------------------------------------------------------.
	// Seller: listings CRUD.
	// ---------------------------------------------------------------------.

	/**
	 * Handle save listing.
	 */
	public function handle_save_listing(): void {
		if ( ! $this->verify() ) {
			return;
		}

		$user_id    = get_current_user_id();
		$listing_id = absint( $_POST['listing_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify() (called at top of every AJAX handler).

		if ( $listing_id > 0 ) {
			$existing = $this->db->get_call_listing( $listing_id );
			if ( ! $existing || (int) $existing['user_id'] !== $user_id ) {
				$this->json_error( __( 'Listing not found.', 'zeko-love' ) );
				return;
			}
		}

		$price = (float) sanitize_text_field( wp_unslash( $_POST['price_per_minute'] ?? 0 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify() (called at top of every AJAX handler).
		$min   = max( 1, absint( $_POST['min_duration'] ?? 0 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify() (called at top of every AJAX handler).
		$max   = max( 1, absint( $_POST['max_duration'] ?? 0 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify() (called at top of every AJAX handler).

		if ( empty( $_POST['title'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify() (called at top of every AJAX handler).
			$this->json_error( __( 'Please add a title.', 'zeko-love' ) );
			return;
		}
		if ( $price < 0.01 ) {
			$this->json_error( __( 'Please set a valid price per minute.', 'zeko-love' ) );
			return;
		}
		if ( $min > $max ) {
			$this->json_error( __( 'Minimum duration cannot exceed maximum duration.', 'zeko-love' ) );
			return;
		}

		$provider = sanitize_text_field( wp_unslash( $_POST['provider'] ?? 'google_meet' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify() (called at top of every AJAX handler).
		$join_url = esc_url_raw( wp_unslash( $_POST['join_url'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify() (called at top of every AJAX handler).
		$whatsapp = sanitize_text_field( wp_unslash( $_POST['whatsapp_phone'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify() (called at top of every AJAX handler).
		if ( 'whatsapp' !== $provider && empty( $join_url ) ) {
			$this->json_error( __( 'Please provide a join link (e.g. your Google Meet room) — buyers need it to join the call.', 'zeko-love' ) );
			return;
		}

		$slots = isset( $_POST['slots'] ) ? json_decode( wp_unslash( (string) $_POST['slots'] ), true ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified by verify() (called at top of every AJAX handler); each slot's day/time is validated below (absint + allow-listed day 0-6 + time pattern).
		if ( ! is_array( $slots ) || empty( $slots ) ) {
			$this->json_error( __( 'Please set at least one weekly availability slot.', 'zeko-love' ) );
			return;
		}
		$clean_slots = array();
		foreach ( $slots as $slot ) {
			$day  = isset( $slot['day_of_week'] ) ? absint( $slot['day_of_week'] ) : 0;
			$from = preg_replace( '/[^0-9:]/', '', (string) ( $slot['start_time'] ?? '' ) );
			$to   = preg_replace( '/[^0-9:]/', '', (string) ( $slot['end_time'] ?? '' ) );
			if ( $day >= 0 && $day <= 6 && $from && $to ) {
				$clean_slots[] = array(
					'day_of_week' => $day,
					'start_time'  => $from,
					'end_time'    => $to,
				);
			}
		}
		if ( empty( $clean_slots ) ) {
			$this->json_error( __( 'Your availability slots are invalid.', 'zeko-love' ) );
			return;
		}

		$data = array(
			'user_id'          => $user_id,
			'title'            => sanitize_text_field( wp_unslash( $_POST['title'] ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify() (called at top of every AJAX handler).
			'description'      => sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify() (called at top of every AJAX handler).
			'call_type'        => sanitize_text_field( wp_unslash( $_POST['call_type'] ?? 'video' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify() (called at top of every AJAX handler).
			'provider'         => $provider,
			'price_per_minute' => $price,
			'min_duration'     => $min,
			'max_duration'     => $max,
			'currency'         => sanitize_text_field( wp_unslash( $_POST['currency'] ?? 'USD' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify() (called at top of every AJAX handler).
			'cover_photo_url'  => esc_url_raw( wp_unslash( $_POST['cover_photo_url'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify() (called at top of every AJAX handler).
			'intro_video_url'  => esc_url_raw( wp_unslash( $_POST['intro_video_url'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify() (called at top of every AJAX handler).
			'youtube_url'      => esc_url_raw( wp_unslash( $_POST['youtube_url'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify() (called at top of every AJAX handler).
			'vimeo_url'        => esc_url_raw( wp_unslash( $_POST['vimeo_url'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify() (called at top of every AJAX handler).
			'whatsapp_phone'   => $whatsapp,
			'join_url'         => $join_url,
			'status'           => $existing['status'] ?? 'draft',
		);

		$saved_id = $this->db->save_call_listing( $data, $listing_id );
		$this->db->save_call_slots( $saved_id, $clean_slots );

		$needs_plan = false;
		$listing    = $this->db->get_call_listing( $saved_id );
		if ( 'active' !== $listing['status'] ) {
			$bookable   = $this->calls->listing_bookable( $listing );
			$needs_plan = ! $bookable['ok'];
		}

		wp_send_json_success(
			array(
				'message'    => $listing_id > 0 ? __( 'Listing updated.', 'zeko-love' ) : __( 'Listing saved as draft. Activate it to go live.', 'zeko-love' ),
				'listing_id' => $saved_id,
				'needs_plan' => $needs_plan,
			)
		);
	}

	/**
	 * Handle delete listing.
	 */
	public function handle_delete_listing(): void {
		if ( ! $this->verify() ) {
			return;
		}
		$listing_id = absint( $_POST['listing_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify() (called at top of every AJAX handler).
		$listing    = $this->db->get_call_listing( $listing_id );
		if ( ! $listing || get_current_user_id() !== (int) $listing['user_id'] ) {
			$this->json_error( __( 'Listing not found.', 'zeko-love' ) );
			return;
		}
		$this->db->delete_call_listing( $listing_id );
		wp_send_json_success( array( 'message' => __( 'Listing deleted.', 'zeko-love' ) ) );
	}

	/**
	 * Handle set listing status.
	 */
	public function handle_set_listing_status(): void {
		if ( ! $this->verify() ) {
			return;
		}
		$user_id    = get_current_user_id();
		$listing_id = absint( $_POST['listing_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify() (called at top of every AJAX handler).
		$status_raw = wp_unslash( $_POST['status'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified by verify() (called at top of every AJAX handler); status allow-listed below before use.
		$status     = in_array( $status_raw, array( 'active', 'paused', 'draft' ), true ) ? $status_raw : '';

		if ( ! $status ) {
			$this->json_error( __( 'Invalid status.', 'zeko-love' ) );
			return;
		}

		$listing = $this->db->get_call_listing( $listing_id );
		if ( ! $listing || (int) $listing['user_id'] !== $user_id ) {
			$this->json_error( __( 'Listing not found.', 'zeko-love' ) );
			return;
		}

		if ( 'active' === $status ) {
			$bookable = $this->calls->listing_bookable( $listing );
			if ( ! $bookable['ok'] ) {
				$plans = $this->db->get_call_plans( true );
				wp_send_json_error(
					array(
						'message'    => $bookable['message'],
						'needs_plan' => ! empty( $plans ),
					)
				);
				return;
			}
		}

		$this->db->update_call_listing_status( $listing_id, $status );
		wp_send_json_success(
			array(
				'message' => __( 'Listing updated.', 'zeko-love' ),
				'status'  => $status,
			)
		);
	}

	/**
	 * Handle get my listings.
	 */
	public function handle_get_my_listings(): void {
		if ( ! $this->verify() ) {
			return;
		}
		$listings = $this->db->get_user_call_listings( get_current_user_id() );

		if ( empty( $listings ) ) {
			wp_send_json_success(
				array(
					'html' => '<div class="zl-empty-state"><span class="dashicons dashicons-phone"></span><h3>' . esc_html__( 'You have no listings yet.', 'zeko-love' ) . '</h3></div>',
				)
			);
			return;
		}

		$status_labels = array(
			'draft'   => __( 'Draft', 'zeko-love' ),
			'active'  => __( 'Live', 'zeko-love' ),
			'paused'  => __( 'Paused', 'zeko-love' ),
			'expired' => __( 'Expired', 'zeko-love' ),
		);

		$html = '';
		foreach ( $listings as $listing ) {
			$enriched = $this->calls->enrich_listing( $listing );
			$status   = $listing['status'];
			$label    = $status_labels[ $status ] ?? $status;

			$edit_url = add_query_arg(
				array(
					'view' => 'listing',
					'id'   => (int) $listing['listing_id'],
				),
				zeko_love_page_url( 'dating-calls' )
			);

			$html .= '<div class="zl-my-listing" data-listing-id="' . esc_attr( $listing['listing_id'] ) . '">';
			$html .= '<div class="zl-my-listing-info">';
			$html .= '<h4>' . esc_html( $listing['title'] ) . '</h4>';
			$html .= '<div class="zl-my-listing-meta">';
			$html .= '<span class="zl-status zl-status-' . esc_attr( $status ) . '">' . esc_html( $label ) . '</span>';
			$html .= '<span>' . esc_html( zeko_love_format_money( (float) $listing['price_per_minute'], $listing['currency'] ) ) . '/min</span>';
			/* translators: %d: number of listing views */
			$html .= '<span>' . esc_html( sprintf( __( '%d views', 'zeko-love' ), (int) $listing['views'] ) ) . '</span>';
			if ( (int) $listing['usage_minutes'] > 0 ) {
				/* translators: %d: usage minutes */
				$html .= '<span>' . esc_html( sprintf( __( '%d min used', 'zeko-love' ), (int) $listing['usage_minutes'] ) ) . '</span>';
			}
			if ( ! empty( $listing['plan_ends_at'] ) ) {
				/* translators: %s: plan end date */
				$html .= '<span>' . esc_html( sprintf( __( 'Plan until %s', 'zeko-love' ), mysql2date( get_option( 'date_format' ), $listing['plan_ends_at'] ) ) ) . '</span>';
			}
			$html .= '</div></div>';
			$html .= '<div class="zl-my-listing-actions">';
			if ( 'active' !== $status ) {
				$html .= '<button type="button" class="zl-btn zl-btn-primary zl-btn-sm zl-set-status" data-status="active">' . esc_html__( 'Activate', 'zeko-love' ) . '</button>';
			}
			if ( 'paused' !== $status && 'draft' !== $status ) {
				$html .= '<button type="button" class="zl-btn zl-btn-secondary zl-btn-sm zl-set-status" data-status="paused">' . esc_html__( 'Pause', 'zeko-love' ) . '</button>';
			}
			$html .= '<button type="button" class="zl-btn zl-btn-secondary zl-btn-sm zl-edit-listing" data-listing-id="' . esc_attr( $listing['listing_id'] ) . '">' . esc_html__( 'Edit', 'zeko-love' ) . '</button>';
			$html .= '<a class="zl-btn zl-btn-secondary zl-btn-sm" href="' . esc_url( $edit_url ) . '">' . esc_html__( 'View', 'zeko-love' ) . '</a>';
			$html .= '<button type="button" class="zl-btn zl-btn-danger zl-btn-sm zl-delete-listing">' . esc_html__( 'Delete', 'zeko-love' ) . '</button>';
			$html .= '</div></div>';
		}

		wp_send_json_success( array( 'html' => $html ) );
	}

	// ---------------------------------------------------------------------.
	// Booking.
	// ---------------------------------------------------------------------.

	/**
	 * Handle book call.
	 */
	public function handle_book_call(): void {
		if ( ! $this->verify() ) {
			return;
		}

		$buyer_id   = get_current_user_id();
		$listing_id = absint( $_POST['listing_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify() (called at top of every AJAX handler).
		$duration   = absint( $_POST['duration'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify() (called at top of every AJAX handler).
		$start_at   = sanitize_text_field( wp_unslash( $_POST['start_at'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify() (called at top of every AJAX handler).

		$listing = $this->db->get_call_listing( $listing_id );
		if ( ! $listing ) {
			$this->json_error( __( 'Listing not found.', 'zeko-love' ) );
			return;
		}
		if ( (int) $listing['user_id'] === $buyer_id ) {
			$this->json_error( __( 'You cannot book your own listing.', 'zeko-love' ) );
			return;
		}

		$bookable = $this->calls->listing_bookable( $listing );
		if ( ! $bookable['ok'] ) {
			$this->json_error( $bookable['message'] );
			return;
		}

		$start_ts = strtotime( $start_at );
		if ( ! $start_ts || $start_ts < time() + HOUR_IN_SECONDS ) {
			$this->json_error( __( 'Please choose a valid future time.', 'zeko-love' ) );
			return;
		}
		if ( $start_ts > time() + ( (int) $this->calls->config()['booking_window'] * DAY_IN_SECONDS ) ) {
			$this->json_error( __( 'That date is too far in the future.', 'zeko-love' ) );
			return;
		}

		$min = (int) $listing['min_duration'];
		$max = (int) $listing['max_duration'];
		if ( $duration < $min || $duration > $max || $duration <= 0 ) {
			$this->json_error( __( 'Invalid duration.', 'zeko-love' ) );
			return;
		}

		if ( ! $this->valid_slot_window( $listing, $start_at, $duration ) ) {
			$this->json_error( __( 'That time is outside the seller\'s availability.', 'zeko-love' ) );
			return;
		}
		if ( $this->db->call_booking_overlaps( $listing_id, $start_at, $duration ) ) {
			$this->json_error( __( 'That time is already booked.', 'zeko-love' ) );
			return;
		}

		if ( ! Zeko_Love_Pay::active() ) {
			$this->json_error( __( 'Payments are not available right now.', 'zeko-love' ) );
			return;
		}

		$amount = round( (float) $listing['price_per_minute'] * $duration, 2 );

		$charge = Zeko_Love_Pay::charge(
			$buyer_id,
			$amount,
			sprintf( 'Zeko Love — Call booking: %s', $listing['title'] ),
			array(
				'feature'    => 'call_booking',
				'listing_id' => $listing_id,
				'seller_id'  => (int) $listing['user_id'],
				'start_at'   => $start_at,
			)
		);
		if ( empty( $charge['success'] ) ) {
			$this->json_error( $charge['message'] ?? __( 'Payment could not be processed.', 'zeko-love' ) );
			return;
		}

		$booking_id = $this->db->create_call_booking(
			array(
				'listing_id'       => $listing_id,
				'seller_id'        => (int) $listing['user_id'],
				'buyer_id'         => $buyer_id,
				'start_at'         => $start_at,
				'duration_minutes' => $duration,
				'amount'           => $amount,
				'currency'         => $listing['currency'],
				'status'           => 'pending',
				'tx_id'            => (int) ( $charge['tx_id'] ?? 0 ),
			)
		);

		/* translators: 1: call duration in minutes. 2: host user name */
		$this->db->log_activity( $buyer_id, 'call_booked', sprintf( __( 'You booked a %1$d-min call with %2$s', 'zeko-love' ), $duration, $this->user_name( (int) $listing['user_id'] ) ) );
		$this->notify(
			(int) $listing['user_id'],
			'call_booking',
			/* translators: 1: buyer name. 2: call duration in minutes. 3: booking date and time */
			sprintf( __( '%1$s booked a %2$d-min call with you for %3$s', 'zeko-love' ), $this->user_name( $buyer_id ), $duration, mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $start_at ) ),
			$buyer_id
		);

		wp_send_json_success(
			array(
				'message'    => __( 'Booking confirmed! Payment was taken from your wallet and is held until the call is completed.', 'zeko-love' ),
				'booking_id' => $booking_id,
				'balance'    => Zeko_Love_Pay::balance( $buyer_id ),
			)
		);
	}

	/**
	 * Validate a requested start time/duration fits inside a weekly slot.
	 *
	 * @param array  $listing Listing.
	 * @param string $start_at Start at.
	 * @param int    $duration Duration.
	 */
	private function valid_slot_window( array $listing, string $start_at, int $duration ): bool {
		$ts       = strtotime( $start_at );
		$day      = (int) gmdate( 'w', $ts );
		$time     = gmdate( 'H:i', $ts );
		$time_end = gmdate( 'H:i', $ts + $duration * MINUTE_IN_SECONDS );

		$slots = $this->db->get_call_slots( (int) $listing['listing_id'] );
		foreach ( $slots as $slot ) {
			if ( (int) $slot['day_of_week'] !== $day ) {
				continue;
			}
			if ( strtotime( $time ) >= strtotime( $slot['start_time'] ) && strtotime( $time_end ) <= strtotime( $slot['end_time'] ) ) {
				return true;
			}
		}
		return false;
	}

	// ---------------------------------------------------------------------.
	// Bookings dashboard.
	// ---------------------------------------------------------------------.

	/**
	 * Handle get my bookings.
	 */
	public function handle_get_my_bookings(): void {
		if ( ! $this->verify() ) {
			return;
		}
		$user_id = get_current_user_id();

		$buyer_bookings  = $this->db->get_call_bookings( $user_id, 'buyer' );
		$seller_bookings = $this->db->get_call_bookings( $user_id, 'seller' );

		wp_send_json_success(
			array(
				'buyer'  => $this->render_bookings( $buyer_bookings, 'buyer' ),
				'seller' => $this->render_bookings( $seller_bookings, 'seller' ),
			)
		);
	}

	/**
	 * Render bookings.
	 *
	 * @param array  $bookings Bookings.
	 * @param string $role Role.
	 */
	private function render_bookings( array $bookings, string $role ): string {
		if ( empty( $bookings ) ) {
			return '<div class="zl-empty-state"><h3>' . esc_html__( 'No bookings yet.', 'zeko-love' ) . '</h3></div>';
		}

		$status_labels = array(
			'pending'   => __( 'Awaiting confirmation', 'zeko-love' ),
			'confirmed' => __( 'Confirmed', 'zeko-love' ),
			'completed' => __( 'Completed', 'zeko-love' ),
			'cancelled' => __( 'Cancelled', 'zeko-love' ),
		);

		$html = '';
		foreach ( $bookings as $booking ) {
			$listing = $this->db->get_call_listing( (int) $booking['listing_id'] );
			if ( ! $listing ) {
				continue;
			}
			$is_seller   = 'seller' === $role;
			$counterpart = $is_seller ? (int) $booking['buyer_id'] : (int) $booking['seller_id'];
			$status      = $booking['status'];
			$start_label = mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $booking['start_at'] );

			$join = '';
			if ( 'confirmed' === $status && ! empty( $listing['join_url'] ) ) {
				$join = $listing['join_url'];
			} elseif ( 'confirmed' === $status && 'whatsapp' === $listing['provider'] && ! empty( $listing['whatsapp_phone'] ) ) {
				$join = 'https://wa.me/' . preg_replace( '/[^0-9]/', '', $listing['whatsapp_phone'] );
			}

			$msg_url = add_query_arg( 'to', $counterpart, zeko_love_page_url( 'dating-messages' ) );

			$hint = '';
			if ( 'pending' === $status ) {
				$hint = $is_seller
					? __( 'Confirm the booking to send the buyer the join details.', 'zeko-love' )
					: __( 'Payment received. You will get the join details once the seller confirms.', 'zeko-love' );
			} elseif ( 'confirmed' === $status ) {
				$hint = $is_seller
					? __( 'Join the call when it starts, then mark it completed.', 'zeko-love' )
					: __( 'Your call is confirmed. Tap "Join call" when it is time.', 'zeko-love' );
			} elseif ( 'completed' === $status && ! $is_seller ) {
				$hint = __( 'Call completed. Leave a review below.', 'zeko-love' );
			} elseif ( 'cancelled' === $status ) {
				$hint = __( 'This booking was cancelled and refunded.', 'zeko-love' );
			}

			$html .= '<div class="zl-booking-row" data-booking-id="' . esc_attr( $booking['booking_id'] ) . '">';
			$html .= '<div class="zl-booking-info">';
			$html .= '<h4>' . esc_html( $listing['title'] ) . '</h4>';
			$html .= '<div class="zl-booking-meta">';
			$html .= '<span class="zl-status zl-status-' . esc_attr( $status ) . '">' . esc_html( $status_labels[ $status ] ?? $status ) . '</span>';
			$html .= '<span>' . esc_html( $this->user_name( $counterpart ) ) . '</span>';
			$html .= '<span>' . esc_html( $start_label ) . '</span>';
			/* translators: %d: call duration in minutes */
			$html .= '<span>' . esc_html( sprintf( __( '%d min', 'zeko-love' ), (int) $booking['duration_minutes'] ) ) . '</span>';
			$html .= '<span>' . esc_html( zeko_love_format_money( (float) $booking['amount'], $booking['currency'] ) ) . '</span>';
			$html .= '</div>';
			if ( $hint ) {
				$html .= '<div class="zl-booking-hint">' . esc_html( $hint ) . '</div>';
			}
			$html .= '</div>';

			$html .= '<div class="zl-booking-actions">';
			if ( $is_seller && 'pending' === $status ) {
				$html .= '<button type="button" class="zl-btn zl-btn-primary zl-btn-sm zl-confirm-booking">' . esc_html__( 'Confirm & send join link', 'zeko-love' ) . '</button>';
			}
			if ( $is_seller && 'confirmed' === $status ) {
				$html .= '<button type="button" class="zl-btn zl-btn-primary zl-btn-sm zl-complete-booking">' . esc_html__( 'Mark completed', 'zeko-love' ) . '</button>';
			}
			if ( $join ) {
				$html .= '<a class="zl-btn zl-btn-secondary zl-btn-sm" href="' . esc_url( $join ) . '" target="_blank" rel="noopener">' . esc_html__( 'Join call', 'zeko-love' ) . '</a>';
			}
			if ( ! $is_seller && 'completed' === $status ) {
				$review = $this->db->get_booking_review( (int) $booking['booking_id'], get_current_user_id() );
				if ( ! $review ) {
					$html .= '<button type="button" class="zl-btn zl-btn-secondary zl-btn-sm zl-review-booking">' . esc_html__( 'Leave review', 'zeko-love' ) . '</button>';
				} else {
					$html .= '<span class="zl-booking-note">&#9733; ' . esc_html( $review['rating'] ) . '/5</span>';
				}
			}
			if ( in_array( $status, array( 'pending', 'confirmed' ), true ) && 'completed' !== $status ) {
				$html .= '<button type="button" class="zl-btn zl-btn-danger zl-btn-sm zl-cancel-booking">' . esc_html__( 'Cancel', 'zeko-love' ) . '</button>';
			}
			$html .= '<a class="zl-btn zl-btn-secondary zl-btn-sm" href="' . esc_url( $msg_url ) . '">' . esc_html__( 'Message', 'zeko-love' ) . '</a>';
			$html .= '</div></div>';
		}

		return $html;
	}

	/**
	 * Handle cancel booking.
	 */
	public function handle_cancel_booking(): void {
		if ( ! $this->verify() ) {
			return;
		}
		$user_id    = get_current_user_id();
		$booking_id = absint( $_POST['booking_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify() (called at top of every AJAX handler).
		$booking    = $this->db->get_call_booking( $booking_id );

		if ( ! $booking ) {
			$this->json_error( __( 'Booking not found.', 'zeko-love' ) );
			return;
		}
		if ( (int) $booking['buyer_id'] !== $user_id && (int) $booking['seller_id'] !== $user_id ) {
			$this->json_error( __( 'You cannot cancel this booking.', 'zeko-love' ) );
			return;
		}
		if ( in_array( $booking['status'], array( 'completed', 'cancelled' ), true ) ) {
			$this->json_error( __( 'This booking can no longer be cancelled.', 'zeko-love' ) );
			return;
		}

		$is_buyer  = (int) $booking['buyer_id'] === $user_id;
		$cancel_ts = time() + ( (int) $this->calls->config()['free_cancel_hours'] * HOUR_IN_SECONDS );
		if ( $is_buyer && strtotime( $booking['start_at'] ) < $cancel_ts ) {
			$this->json_error( __( 'The free-cancel window has passed. Please contact the seller.', 'zeko-love' ) );
			return;
		}

		if ( ! empty( $booking['tx_id'] ) && class_exists( 'Zeko_Pay_SDK' ) ) {
			$sdk    = new Zeko_Pay_SDK();
			$refund = $sdk->refund( (int) $booking['tx_id'], 'Call booking cancelled (zeko_love)' );
			if ( empty( $refund['success'] ) ) {
				$this->json_error( $refund['message'] ?? __( 'Refund could not be processed.', 'zeko-love' ) );
				return;
			}
		}

		$this->db->update_call_booking(
			$booking_id,
			array(
				'status'        => 'cancelled',
				'cancelled_by'  => $user_id,
				'cancel_reason' => __( 'Cancelled by user', 'zeko-love' ),
			)
		);

		$other = $is_buyer ? (int) $booking['seller_id'] : (int) $booking['buyer_id'];
		/* translators: %s: other participant user name */
		$this->notify( $other, 'call_cancelled', sprintf( __( 'Your call booking with %s was cancelled.', 'zeko-love' ), $this->user_name( $user_id ) ), $user_id );
		$this->db->log_activity( $user_id, 'call_cancelled', __( 'You cancelled a call booking', 'zeko-love' ) );

		wp_send_json_success( array( 'message' => __( 'Booking cancelled and refunded.', 'zeko-love' ) ) );
	}

	/**
	 * Handle confirm booking.
	 */
	public function handle_confirm_booking(): void {
		if ( ! $this->verify() ) {
			return;
		}
		$user_id    = get_current_user_id();
		$booking_id = absint( $_POST['booking_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify() (called at top of every AJAX handler).
		$booking    = $this->db->get_call_booking( $booking_id );

		if ( ! $booking || (int) $booking['seller_id'] !== $user_id ) {
			$this->json_error( __( 'Booking not found.', 'zeko-love' ) );
			return;
		}
		if ( 'pending' !== $booking['status'] ) {
			$this->json_error( __( 'This booking is not pending.', 'zeko-love' ) );
			return;
		}

		$listing = $this->db->get_call_listing( (int) $booking['listing_id'] );
		$join    = '';
		if ( $listing ) {
			if ( ! empty( $listing['join_url'] ) ) {
				$join = $listing['join_url'];
			} elseif ( 'whatsapp' === $listing['provider'] && ! empty( $listing['whatsapp_phone'] ) ) {
				$join = 'https://wa.me/' . preg_replace( '/[^0-9]/', '', $listing['whatsapp_phone'] );
			}
		}

		$this->db->update_call_booking( $booking_id, array( 'status' => 'confirmed' ) );
		$this->notify(
			(int) $booking['buyer_id'],
			'call_confirmed',
			/* translators: 1: other participant name. 2: booking date and time */
			sprintf( __( '%1$s confirmed your call for %2$s', 'zeko-love' ), $this->user_name( $user_id ), mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $booking['start_at'] ) ),
			$user_id
		);
		if ( $join ) {
			$this->notify(
				(int) $booking['buyer_id'],
				'call_join',
				/* translators: %s: call join URL */
				sprintf( __( 'Join your call here: %s', 'zeko-love' ), $join ),
				$user_id
			);
		}

		wp_send_json_success(
			array(
				'message' => __( 'Booking confirmed. The buyer has the join details.', 'zeko-love' ),
				'join'    => $join,
			)
		);
	}

	/**
	 * Handle complete booking.
	 */
	public function handle_complete_booking(): void {
		if ( ! $this->verify() ) {
			return;
		}
		$user_id    = get_current_user_id();
		$booking_id = absint( $_POST['booking_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify() (called at top of every AJAX handler).
		$booking    = $this->db->get_call_booking( $booking_id );

		if ( ! $booking || (int) $booking['seller_id'] !== $user_id ) {
			$this->json_error( __( 'Booking not found.', 'zeko-love' ) );
			return;
		}
		if ( 'confirmed' !== $booking['status'] ) {
			$this->json_error( __( 'Only confirmed bookings can be completed.', 'zeko-love' ) );
			return;
		}

		$this->db->log_call_usage( (int) $booking['listing_id'], $user_id, (int) $booking['duration_minutes'] );

		$payout_ref = $this->calls->payout_seller( $booking );
		if ( '' === $payout_ref ) {
			$this->json_error( __( 'Payment could not be processed. The call was not marked completed — please try again or contact support.', 'zeko-love' ) );
			return;
		}

		$this->db->update_call_booking(
			$booking_id,
			array(
				'status'       => 'completed',
				'payout_ref'   => $payout_ref,
				'completed_at' => current_time( 'mysql' ),
			)
		);

		$this->notify(
			(int) $booking['buyer_id'],
			'call_completed',
			/* translators: %s: other participant name */
			sprintf( __( '%s marked your call as completed.', 'zeko-love' ), $this->user_name( $user_id ) ),
			$user_id
		);
		/* translators: %d: call duration in minutes */
		$this->db->log_activity( $user_id, 'call_completed', sprintf( __( 'You completed a %d-min call', 'zeko-love' ), (int) $booking['duration_minutes'] ) );

		wp_send_json_success(
			array(
				'message'    => __( 'Call completed. Earnings credited to your wallet.', 'zeko-love' ),
				'payout_ref' => $payout_ref,
			)
		);
	}

	/**
	 * Handle submit review.
	 */
	public function handle_submit_review(): void {
		if ( ! $this->verify() ) {
			return;
		}
		$user_id    = get_current_user_id();
		$booking_id = absint( $_POST['booking_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify() (called at top of every AJAX handler).
		$rating     = max( 1, min( 5, absint( $_POST['rating'] ?? 5 ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify() (called at top of every AJAX handler).
		$comment    = sanitize_textarea_field( wp_unslash( $_POST['comment'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Nonce verified by verify() (called at top of every AJAX handler).

		$booking = $this->db->get_call_booking( $booking_id );
		if ( ! $booking || (int) $booking['buyer_id'] !== $user_id ) {
			$this->json_error( __( 'Booking not found.', 'zeko-love' ) );
			return;
		}
		if ( 'completed' !== $booking['status'] ) {
			$this->json_error( __( 'You can only review completed calls.', 'zeko-love' ) );
			return;
		}
		if ( $this->db->get_booking_review( $booking_id, $user_id ) ) {
			$this->json_error( __( 'You already reviewed this call.', 'zeko-love' ) );
			return;
		}

		$this->db->add_call_review(
			array(
				'booking_id'  => $booking_id,
				'reviewer_id' => $user_id,
				'reviewee_id' => (int) $booking['seller_id'],
				'rating'      => $rating,
				'comment'     => $comment,
			)
		);

		$this->notify(
			(int) $booking['seller_id'],
			'call_review',
			/* translators: 1: reviewer name. 2: rating out of 5 */
			sprintf( __( '%1$s reviewed your call: %2$d/5', 'zeko-love' ), $this->user_name( $user_id ), $rating ),
			$user_id
		);

		wp_send_json_success( array( 'message' => __( 'Thanks for your review!', 'zeko-love' ) ) );
	}

	// ---------------------------------------------------------------------.
	// Earnings.
	// ---------------------------------------------------------------------.

	/**
	 * Handle get earnings.
	 */
	public function handle_get_earnings(): void {
		if ( ! $this->verify() ) {
			return;
		}
		$user_id = get_current_user_id();

		$balance   = '0.00';
		$rows      = array();
		$total_out = 0.0;

		if ( Zeko_Love_Pay::active() ) {
			$sdk     = new Zeko_Pay_SDK();
			$balance = $sdk->get_balance( $user_id );
			$ledger  = Zeko_Pay_Ledger::instance();
			$wallet  = $ledger->get_wallet_by_user( $user_id );
			if ( $wallet ) {
				$res  = $ledger->get_transactions( (int) $wallet['id'], array( 'limit' => 50 ) );
				$rows = $res['transactions'] ?? array();
			}
		}

		$html = '';
		foreach ( $rows as $row ) {
			$amount     = (float) $row['amount'];
			$is_in      = $amount >= 0;
			$total_out += $is_in ? 0 : abs( $amount );

			$label = $this->tx_label( $row );

			$html .= '<div class="zl-tx-row">';
			$html .= '<div class="zl-tx-label">' . esc_html( $label ) . '</div>';
			$html .= '<div class="zl-tx-date">' . esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $row['created_at'] ) ) . '</div>';
			$html .= '<div class="zl-tx-amount ' . ( $is_in ? 'zl-tx-in' : 'zl-tx-out' ) . '">'
				. esc_html( ( $is_in ? '+' : '-' ) . zeko_love_format_money( abs( $amount ), $this->calls->config()['currency'] ) )
				. '</div>';
			$html .= '</div>';
		}

		if ( empty( $html ) ) {
			$html = '<div class="zl-empty-state"><h3>' . esc_html__( 'No wallet activity yet.', 'zeko-love' ) . '</h3></div>';
		}

		$my_listings   = $this->db->get_user_call_listings( $user_id );
		$total_minutes = 0;
		$views         = 0;
		foreach ( $my_listings as $l ) {
			$total_minutes += (int) $l['usage_minutes'];
			$views         += (int) $l['views'];
		}

		wp_send_json_success(
			array(
				'balance' => $balance,
				'html'    => $html,
				'stats'   => array(
					'listings' => count( $my_listings ),
					'minutes'  => $total_minutes,
					'views'    => $views,
					'payouts'  => $total_out,
				),
			)
		);
	}

	/**
	 * Tx label.
	 *
	 * @param array $row Row.
	 */
	private function tx_label( array $row ): string {
		$meta = array();
		if ( ! empty( $row['metadata'] ) ) {
			$decoded = json_decode( $row['metadata'], true );
			if ( is_array( $decoded ) ) {
				$meta = $decoded;
			}
		}

		if ( ! empty( $meta['type'] ) ) {
			switch ( $meta['type'] ) {
				case 'call_payout':
					return __( 'Call earnings', 'zeko-love' );
				case 'earned_credit':
					return __( 'Earned credit', 'zeko-love' );
				case 'call_plan':
					return __( 'Call listing plan', 'zeko-love' );
				case 'call_booking':
					return __( 'Call booking payment', 'zeko-love' );
			}
		}
		if ( ! empty( $row['description'] ) ) {
			return $row['description'];
		}
		return __( 'Wallet transaction', 'zeko-love' );
	}
}
