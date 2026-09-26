<?php
/**
 * Zeko Love - Calls & Availability Template
 *
 * @package Zeko_Love
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id = get_current_user_id();
$love    = Zeko_Love::instance();
$calls   = $love->get_calls();
$config  = $calls->config();

$default_tab = isset( $_GET['my_listings'] ) ? 'my-listings' : 'explore'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
if ( in_array( $_GET['tab'] ?? '', array( 'explore', 'my-listings', 'bookings', 'earnings' ), true ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$default_tab = sanitize_key( $_GET['tab'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}
?>

<div class="zl-calls zl-card" data-currency="<?php echo esc_attr( $config['currency'] ); ?>">

	<div class="zl-calls-header">
		<h2><?php echo esc_html__( 'Calls & Availability', 'zeko-love' ); ?></h2>
		<?php if ( Zeko_Love_Pay::active() ) : ?>
			<span class="zl-wallet-pill"><?php echo esc_html__( 'Balance:', 'zeko-love' ); ?> <strong><?php echo esc_html( Zeko_Love_Pay::balance( $user_id ) ); ?></strong> <?php echo esc_html( $config['currency'] ); ?></span>
		<?php endif; ?>
	</div>

	<div class="zl-tabs zl-calls-tabs" role="tablist">
		<button type="button" class="zl-tab<?php echo 'explore' === $default_tab ? ' active' : ''; ?>" data-tab="explore"><?php echo esc_html__( 'Explore', 'zeko-love' ); ?></button>
		<button type="button" class="zl-tab<?php echo 'my-listings' === $default_tab ? ' active' : ''; ?>" data-tab="my-listings"><?php echo esc_html__( 'My Listings', 'zeko-love' ); ?></button>
		<button type="button" class="zl-tab" data-tab="bookings"><?php echo esc_html__( 'Bookings', 'zeko-love' ); ?></button>
		<button type="button" class="zl-tab" data-tab="earnings"><?php echo esc_html__( 'Earnings', 'zeko-love' ); ?></button>
	</div>

	<div class="zl-tab-panel zl-calls-panel" data-panel="explore">
		<div class="zl-explore-toolbar">
			<input type="search" class="zl-input zl-explore-search" placeholder="<?php echo esc_attr__( 'Search listings…', 'zeko-love' ); ?>" />
			<select class="zl-input zl-explore-type">
				<option value=""><?php echo esc_html__( 'All call types', 'zeko-love' ); ?></option>
				<option value="video"><?php echo esc_html__( 'Video', 'zeko-love' ); ?></option>
				<option value="voice"><?php echo esc_html__( 'Voice', 'zeko-love' ); ?></option>
			</select>
			<select class="zl-input zl-explore-order">
				<option value="created_at"><?php echo esc_html__( 'Newest', 'zeko-love' ); ?></option>
				<option value="price"><?php echo esc_html__( 'Lowest price', 'zeko-love' ); ?></option>
				<option value="views"><?php echo esc_html__( 'Most viewed', 'zeko-love' ); ?></option>
			</select>
		</div>
		<div class="zl-explore-results"><div class="zl-loading"><?php echo esc_html__( 'Loading listings…', 'zeko-love' ); ?></div></div>
		<div class="zl-pagination" data-current="1"></div>
	</div>

	<div class="zl-tab-panel zl-calls-panel" data-panel="my-listings">
		<div class="zl-my-listings-toolbar">
			<button type="button" class="zl-btn zl-btn-primary zl-new-listing-btn">+ <?php echo esc_html__( 'New Listing', 'zeko-love' ); ?></button>
		</div>
		<div class="zl-my-listings"></div>
	</div>

	<div class="zl-tab-panel zl-calls-panel" data-panel="bookings">
		<div class="zl-subtabs">
			<button type="button" class="zl-tab active" data-subtab="buyer"><?php echo esc_html__( 'As buyer', 'zeko-love' ); ?></button>
			<button type="button" class="zl-tab" data-subtab="seller"><?php echo esc_html__( 'As seller', 'zeko-love' ); ?></button>
		</div>
		<div class="zl-bookings-buyer zl-bookings-panel"></div>
		<div class="zl-bookings-seller zl-bookings-panel" style="display:none;"></div>
	</div>

	<div class="zl-tab-panel zl-calls-panel" data-panel="earnings">
		<div class="zl-earnings-summary">
			<div class="zl-earnings-balance">
				<span><?php echo esc_html__( 'Wallet balance', 'zeko-love' ); ?></span>
				<strong class="zl-earnings-balance-amount">—</strong>
			</div>
			<div class="zl-earnings-stats"></div>
		</div>
		<div class="zl-tx-list"></div>
	</div>

	<div class="zl-review-modal-wrap" style="display:none;">
		<div class="zl-review-modal">
			<h3><?php echo esc_html__( 'Review this call', 'zeko-love' ); ?></h3>
			<div class="zl-field">
				<label><?php echo esc_html__( 'Rating', 'zeko-love' ); ?></label>
				<div class="zl-star-picker">
					<?php for ( $search_term = 1; $search_term <= 5; $search_term++ ) : ?>
						<button type="button" class="zl-star" data-value="<?php echo esc_attr( $search_term ); ?>">&#9733;</button>
					<?php endfor; ?>
				</div>
			</div>
			<div class="zl-field">
				<label><?php echo esc_html__( 'Comment', 'zeko-love' ); ?></label>
				<textarea class="zl-input zl-review-comment" rows="3"></textarea>
			</div>
			<div class="zl-modal-actions">
				<button type="button" class="zl-btn zl-btn-secondary zl-review-cancel"><?php echo esc_html__( 'Cancel', 'zeko-love' ); ?></button>
				<button type="button" class="zl-btn zl-btn-primary zl-review-submit"><?php echo esc_html__( 'Submit review', 'zeko-love' ); ?></button>
			</div>
		</div>
	</div>

	<div class="zl-listing-form-wrap" style="display:none;">
		<div class="zl-listing-form">
			<h3 class="zl-listing-form-title"><?php echo esc_html__( 'New listing', 'zeko-love' ); ?></h3>
			<input type="hidden" class="zl-input zl-form-listing-id" value="0" />
			<div class="zl-form-grid">
				<div class="zl-field zl-field-wide">
					<label><?php echo esc_html__( 'Title', 'zeko-love' ); ?></label>
					<input type="text" class="zl-input zl-form-title" placeholder="<?php echo esc_attr__( 'e.g. Coffee & deep conversations', 'zeko-love' ); ?>" />
				</div>
				<div class="zl-field zl-field-wide">
					<label><?php echo esc_html__( 'Description', 'zeko-love' ); ?></label>
					<textarea class="zl-input zl-form-description" rows="3" placeholder="<?php echo esc_attr__( 'What will the call be about?', 'zeko-love' ); ?>"></textarea>
				</div>
				<div class="zl-field">
					<label><?php echo esc_html__( 'Call type', 'zeko-love' ); ?></label>
					<select class="zl-input zl-form-call-type">
						<option value="video"><?php echo esc_html__( 'Video', 'zeko-love' ); ?></option>
						<option value="voice"><?php echo esc_html__( 'Voice', 'zeko-love' ); ?></option>
					</select>
				</div>
				<div class="zl-field">
					<label><?php echo esc_html__( 'Platform', 'zeko-love' ); ?></label>
					<select class="zl-input zl-form-provider">
						<option value="google_meet"><?php echo esc_html__( 'Google Meet', 'zeko-love' ); ?></option>
						<option value="whatsapp"><?php echo esc_html__( 'WhatsApp', 'zeko-love' ); ?></option>
						<option value="other"><?php echo esc_html__( 'Other', 'zeko-love' ); ?></option>
					</select>
				</div>
				<div class="zl-field">
					<label><?php echo esc_html__( 'Price per minute', 'zeko-love' ); ?></label>
					<input type="number" class="zl-input zl-form-price" min="0.01" step="0.01" value="1.00" />
				</div>
				<div class="zl-field">
					<label><?php echo esc_html__( 'Min duration (min)', 'zeko-love' ); ?></label>
					<input type="number" class="zl-input zl-form-min" min="5" step="5" value="15" />
				</div>
				<div class="zl-field">
					<label><?php echo esc_html__( 'Max duration (min)', 'zeko-love' ); ?></label>
					<input type="number" class="zl-input zl-form-max" min="5" step="5" value="60" />
				</div>
				<div class="zl-field zl-field-wide">
					<label><?php echo esc_html__( 'Cover photo URL', 'zeko-love' ); ?></label>
					<input type="url" class="zl-input zl-form-cover" placeholder="https://…" />
				</div>
				<div class="zl-field">
					<label><?php echo esc_html__( 'YouTube URL (intro)', 'zeko-love' ); ?></label>
					<input type="url" class="zl-input zl-form-youtube" placeholder="https://youtube.com/watch?v=…" />
				</div>
				<div class="zl-field">
					<label><?php echo esc_html__( 'Vimeo URL (intro)', 'zeko-love' ); ?></label>
					<input type="url" class="zl-input zl-form-vimeo" placeholder="https://vimeo.com/…" />
				</div>
				<div class="zl-field zl-field-wide">
					<label><?php echo esc_html__( 'Intro video URL (self-hosted)', 'zeko-love' ); ?></label>
					<input type="url" class="zl-input zl-form-intro-video" placeholder="https://…" />
				</div>
				<div class="zl-field">
					<label><?php echo esc_html__( 'WhatsApp number', 'zeko-love' ); ?></label>
					<input type="tel" class="zl-input zl-form-whatsapp" placeholder="+00000000000" />
				</div>
				<div class="zl-field">
					<label><?php echo esc_html__( 'Join link (private)', 'zeko-love' ); ?></label>
					<input type="url" class="zl-input zl-form-join" placeholder="https://meet.google.com/…" />
				</div>
			</div>

			<h4 class="zl-slots-title"><?php echo esc_html__( 'Weekly availability', 'zeko-love' ); ?></h4>
			<div class="zl-slot-editor">
				<div class="zl-slot-rows"></div>
				<button type="button" class="zl-btn zl-btn-secondary zl-btn-sm zl-add-slot">+ <?php echo esc_html__( 'Add time slot', 'zeko-love' ); ?></button>
			</div>

			<div class="zl-form-error zl-notice notice-error inline" style="display:none;"><p></p></div>
			<div class="zl-modal-actions">
				<button type="button" class="zl-btn zl-btn-secondary zl-listing-form-cancel"><?php echo esc_html__( 'Cancel', 'zeko-love' ); ?></button>
				<button type="button" class="zl-btn zl-btn-primary zl-listing-form-save"><?php echo esc_html__( 'Save listing', 'zeko-love' ); ?></button>
			</div>
		</div>
	</div>

	<div class="zl-plan-modal-wrap" style="display:none;">
		<div class="zl-plan-modal">
			<h3><?php echo esc_html__( 'Choose a plan to go live', 'zeko-love' ); ?></h3>
			<p class="zl-plan-note"><?php echo esc_html__( 'A plan activates your listing so members can book calls with you.', 'zeko-love' ); ?></p>
			<div class="zl-plan-list"></div>
			<div class="zl-form-error zl-notice notice-error inline" style="display:none;"><p></p></div>
			<div class="zl-modal-actions">
				<button type="button" class="zl-btn zl-btn-secondary zl-plan-cancel"><?php echo esc_html__( 'Cancel', 'zeko-love' ); ?></button>
			</div>
		</div>
	</div>
</div>
