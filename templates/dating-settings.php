<?php
/**
 * Zeko Love - Dating Settings Template
 *
 * Advanced tabbed settings hub: Profile, Payouts, Privacy, Notifications,
 * Blocked users.
 *
 * Expects (all optional):
 *   $settings       array  Per-user privacy/notification settings.
 *   $blocked_users  array  List of blocked users (name + id).
 *   $profile        array  Profile row.
 *   $photos         array  Photo rows.
 *   $interests      array  Flat interest values.
 *   $payout         array  Saved payout method + account details.
 *   $balance        string Wallet balance.
 *   $min_withdrawal float  Minimum withdrawal amount.
 *   $currency       string Display currency code.
 *   $withdrawals    array  Recent withdrawal transactions.
 *
 * @package Zeko_Love
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id        = get_current_user_id();
$settings       = isset( $settings ) ? (array) $settings : array();
$blocked_users  = isset( $blocked_users ) ? (array) $blocked_users : array();
$profile        = isset( $profile ) ? (array) $profile : array();
$photos         = isset( $photos ) ? (array) $photos : array();
$interests      = isset( $interests ) ? (array) $interests : array();
$payout         = isset( $payout ) ? (array) $payout : array();
$balance        = isset( $balance ) ? (string) $balance : '0.00';
$min_withdrawal = isset( $min_withdrawal ) ? (float) $min_withdrawal : 10.00;
$currency       = isset( $currency ) ? $currency : 'USD';
$withdrawals    = isset( $withdrawals ) ? (array) $withdrawals : array();

$payout_method = isset( $payout['method'] ) ? $payout['method'] : 'paypal';

$method_labels = array(
	'paypal' => __( 'PayPal', 'zeko-love' ),
	'crypto' => __( 'Crypto', 'zeko-love' ),
	'bank'   => __( 'Bank transfer', 'zeko-love' ),
);
?>

<div class="zl-settings" style="max-width:820px;margin:0 auto;">
	<h1><?php echo esc_html__( 'Dating Settings', 'zeko-love' ); ?></h1>
	<p class="zl-settings-sub" style="color:var(--zlove-gray-500);margin-top:-8px;"><?php echo esc_html__( 'Manage your profile, payouts, privacy and notifications.', 'zeko-love' ); ?></p>

	<!-- Tabs -->
	<div class="zl-settings-tabs" role="tablist">
		<button type="button" class="zl-settings-tab zl-settings-tab-active" data-tab="profile" role="tab"><?php echo esc_html__( 'Profile', 'zeko-love' ); ?></button>
		<button type="button" class="zl-settings-tab" data-tab="payouts" role="tab"><?php echo esc_html__( 'Payouts', 'zeko-love' ); ?></button>
		<button type="button" class="zl-settings-tab" data-tab="privacy" role="tab"><?php echo esc_html__( 'Privacy', 'zeko-love' ); ?></button>
		<button type="button" class="zl-settings-tab" data-tab="notifications" role="tab"><?php echo esc_html__( 'Notifications', 'zeko-love' ); ?></button>
		<button type="button" class="zl-settings-tab" data-tab="blocked" role="tab"><?php echo esc_html__( 'Blocked Users', 'zeko-love' ); ?></button>
	</div>

	<!-- Profile -->
	<div class="zl-settings-panel zl-settings-panel-active" id="zl-tab-profile" role="tabpanel">
		<?php require __DIR__ . '/partials/profile-fields.php'; ?>
	</div>

	<!-- Payouts -->
	<div class="zl-settings-panel" id="zl-tab-payouts" role="tabpanel">
		<div class="zl-card">
			<h2 class="zl-card-title"><?php echo esc_html__( 'Payout Method', 'zeko-love' ); ?></h2>
			<p style="color:var(--zlove-gray-500);font-size:13px;margin-top:-6px;"><?php echo esc_html__( 'Where should your call earnings and withdrawals be sent?', 'zeko-love' ); ?></p>

			<form method="post">
				<?php wp_nonce_field( 'zeko_love_payout', '_zl_payout_nonce' ); ?>

				<div class="zl-field">
					<label class="zl-label" for="zl-payout-method"><?php echo esc_html__( 'Method', 'zeko-love' ); ?></label>
					<select id="zl-payout-method" name="payout_method" class="zl-input zl-payout-method">
						<?php foreach ( $method_labels as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $payout_method, $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="zl-payout-group" data-method="paypal">
					<div class="zl-field">
						<label class="zl-label" for="zl-paypal-email"><?php echo esc_html__( 'PayPal Email', 'zeko-love' ); ?></label>
						<input type="email" id="zl-paypal-email" name="paypal_email" class="zl-input" value="<?php echo isset( $payout['paypal_email'] ) ? esc_attr( $payout['paypal_email'] ) : ''; ?>" placeholder="you@example.com" />
					</div>
				</div>

				<div class="zl-payout-group" data-method="crypto">
					<div class="zl-field">
						<label class="zl-label" for="zl-crypto-address"><?php echo esc_html__( 'Wallet Address', 'zeko-love' ); ?></label>
						<input type="text" id="zl-crypto-address" name="crypto_address" class="zl-input" value="<?php echo isset( $payout['crypto_address'] ) ? esc_attr( $payout['crypto_address'] ) : ''; ?>" placeholder="<?php echo esc_attr__( 'e.g. bc1q...', 'zeko-love' ); ?>" />
					</div>
					<div class="zl-field">
						<label class="zl-label" for="zl-crypto-network"><?php echo esc_html__( 'Network', 'zeko-love' ); ?></label>
						<input type="text" id="zl-crypto-network" name="crypto_network" class="zl-input" value="<?php echo isset( $payout['crypto_network'] ) ? esc_attr( $payout['crypto_network'] ) : ''; ?>" placeholder="<?php echo esc_attr__( 'e.g. BTC, USDT (TRC20), ETH', 'zeko-love' ); ?>" />
					</div>
				</div>

				<div class="zl-payout-group" data-method="bank">
					<div class="zl-field">
						<label class="zl-label" for="zl-bank-name"><?php echo esc_html__( 'Bank Name', 'zeko-love' ); ?></label>
						<input type="text" id="zl-bank-name" name="bank_name" class="zl-input" value="<?php echo isset( $payout['bank_name'] ) ? esc_attr( $payout['bank_name'] ) : ''; ?>" />
					</div>
					<div class="zl-field">
						<label class="zl-label" for="zl-bank-account-name"><?php echo esc_html__( 'Account Holder Name', 'zeko-love' ); ?></label>
						<input type="text" id="zl-bank-account-name" name="bank_account_name" class="zl-input" value="<?php echo isset( $payout['bank_account_name'] ) ? esc_attr( $payout['bank_account_name'] ) : ''; ?>" />
					</div>
					<div class="zl-field">
						<label class="zl-label" for="zl-bank-account-number"><?php echo esc_html__( 'Account Number / IBAN', 'zeko-love' ); ?></label>
						<input type="text" id="zl-bank-account-number" name="bank_account_number" class="zl-input" value="<?php echo isset( $payout['bank_account_number'] ) ? esc_attr( $payout['bank_account_number'] ) : ''; ?>" />
					</div>
					<div class="zl-field">
						<label class="zl-label" for="zl-bank-routing"><?php echo esc_html__( 'Routing / SWIFT', 'zeko-love' ); ?></label>
						<input type="text" id="zl-bank-routing" name="bank_routing" class="zl-input" value="<?php echo isset( $payout['bank_routing'] ) ? esc_attr( $payout['bank_routing'] ) : ''; ?>" />
					</div>
				</div>

				<div style="margin-top:16px;">
					<button type="submit" class="zl-btn zl-btn-primary zl-save-payout">
						<?php echo esc_html__( 'Save Payout Method', 'zeko-love' ); ?>
					</button>
				</div>
			</form>
		</div>

		<div class="zl-card">
			<h2 class="zl-card-title"><?php echo esc_html__( 'Withdraw Balance', 'zeko-love' ); ?></h2>

			<div class="zl-balance-card" style="background:var(--zlove-primary-light);border-radius:var(--zlove-radius);padding:16px;margin-bottom:16px;">
				<span style="font-size:13px;color:var(--zlove-gray-500);"><?php echo esc_html__( 'Available balance', 'zeko-love' ); ?></span>
				<div style="font-size:26px;font-weight:700;color:var(--zlove-gray-900);">
					<?php echo esc_html( $balance ); ?> <?php echo esc_html( $currency ); ?>
				</div>
			</div>

			<form method="post">
				<?php wp_nonce_field( 'zeko_love_withdraw', '_zl_withdraw_nonce' ); ?>

				<div class="zl-field">
					<label class="zl-label" for="zl-withdraw-amount"><?php echo esc_html__( 'Amount', 'zeko-love' ); ?></label>
					<input type="number" id="zl-withdraw-amount" name="amount" class="zl-input" min="0.01" step="0.01" placeholder="0.00" required />
					<p style="color:var(--zlove-gray-500);font-size:12px;margin-top:4px;">
						<?php /* translators: %s: minimum withdrawal amount */ echo esc_html( sprintf( __( 'Minimum withdrawal: %s. Requests are reviewed by the team.', 'zeko-love' ), number_format( $min_withdrawal, 2 ) ) ); ?>
					</p>
				</div>

				<div class="zl-field" data-zl-otp hidden>
					<label class="zl-label" for="zl-withdraw-otp-code"><?php echo esc_html__( 'Verification code', 'zeko-love' ); ?></label>
					<div style="display:flex;gap:8px;align-items:center;">
						<input type="text" id="zl-withdraw-otp-code" name="otp_code" class="zl-input" inputmode="numeric" pattern="[0-9]*" maxlength="6" placeholder="000000" autocomplete="one-time-code" />
						<button type="button" class="zl-btn" data-zl-otp-send><?php echo esc_html__( 'Email me a code', 'zeko-love' ); ?></button>
					</div>
					<p class="zl-otp-status" style="color:var(--zlove-gray-500);font-size:12px;margin-top:4px;"></p>
				</div>

				<?php if ( 'paypal' === $payout_method && ! empty( $payout['paypal_email'] ) ) : ?>
					<p class="zl-account-summary"><?php /* translators: %s: payout address or PayPal email */ echo esc_html( sprintf( __( 'Paying to: %s', 'zeko-love' ), $payout['paypal_email'] ) ); ?></p>
				<?php /* translators: %s: payout address or PayPal email */ elseif ( 'crypto' === $payout_method && ! empty( $payout['crypto_address'] ) ) : ?>
					<p class="zl-account-summary"><?php /* translators: %s: payout address or PayPal email */ /* translators: %s: payout address or PayPal email */ echo esc_html( sprintf( __( 'Paying to: %s', 'zeko-love' ), $payout['crypto_address'] ) ); ?></p>
				<?php elseif ( 'bank' === $payout_method && ! empty( $payout['bank_account_number'] ) ) : ?>
					<p class="zl-account-summary"><?php /* translators: %s: payout address or PayPal email */ echo esc_html( sprintf( __( 'Paying to: %s', 'zeko-love' ), $payout['bank_name'] . ' •••• ' . substr( $payout['bank_account_number'], -4 ) ) ); ?></p>
				<?php else : ?>
					<p class="zl-account-summary" style="color:#b45309;"><?php echo esc_html__( 'Save a payout method above before withdrawing.', 'zeko-love' ); ?></p>
				<?php endif; ?>

				<div style="margin-top:16px;">
					<button type="submit" class="zl-btn zl-btn-primary zl-withdraw-btn">
						<?php echo esc_html__( 'Request Withdrawal', 'zeko-love' ); ?>
					</button>
				</div>
			</form>
		</div>

		<div class="zl-card">
			<h2 class="zl-card-title"><?php echo esc_html__( 'Payout History', 'zeko-love' ); ?></h2>

			<?php if ( empty( $withdrawals ) ) : ?>
				<p style="color:var(--zlove-gray-500);"><?php echo esc_html__( 'No withdrawals yet.', 'zeko-love' ); ?></p>
			<?php else : ?>
				<table class="zl-table" style="width:100%;border-collapse:collapse;">
					<thead>
						<tr>
							<th style="text-align:left;padding:8px;border-bottom:1px solid var(--zlove-gray-200);"><?php echo esc_html__( 'Date', 'zeko-love' ); ?></th>
							<th style="text-align:left;padding:8px;border-bottom:1px solid var(--zlove-gray-200);"><?php echo esc_html__( 'Method', 'zeko-love' ); ?></th>
							<th style="text-align:right;padding:8px;border-bottom:1px solid var(--zlove-gray-200);"><?php echo esc_html__( 'Amount', 'zeko-love' ); ?></th>
							<th style="text-align:right;padding:8px;border-bottom:1px solid var(--zlove-gray-200);"><?php echo esc_html__( 'Status', 'zeko-love' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $withdrawals as $tx ) : ?>
							<?php
							$meta       = ! empty( $tx['metadata'] ) ? json_decode( $tx['metadata'], true ) : array();
							$tx_method  = is_array( $meta ) ? ( $meta['method'] ?? $tx['gateway'] ?? '' ) : '';
							$tx_method  = isset( $method_labels[ $tx_method ] ) ? $method_labels[ $tx_method ] : ( $tx_method ? $tx_method : '-' );
							$tx_amount  = (float) $tx['amount'];
							$tx_status  = $tx['status'] ?? '';
							$status_cls = in_array( $tx_status, array( 'completed', 'approved' ), true ) ? 'success' : ( 'pending' === $tx_status ? 'pending' : 'error' );
							?>
							<tr>
								<td style="padding:8px;border-bottom:1px solid var(--zlove-gray-100);"><?php echo esc_html( gmdate( 'M j, Y', strtotime( $tx['created_at'] ) ) ); ?></td>
								<td style="padding:8px;border-bottom:1px solid var(--zlove-gray-100);"><?php echo esc_html( $tx_method ); ?></td>
								<td style="padding:8px;border-bottom:1px solid var(--zlove-gray-100);text-align:right;"><?php echo esc_html( '-' . number_format( abs( $tx_amount ), 2 ) . ' ' . $currency ); ?></td>
								<td style="padding:8px;border-bottom:1px solid var(--zlove-gray-100);text-align:right;"><span class="zl-status zl-status-<?php echo esc_attr( $status_cls ); ?>"><?php echo esc_html( ucfirst( $tx_status ) ); ?></span></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	</div>

	<!-- Privacy -->
	<div class="zl-settings-panel" id="zl-tab-privacy" role="tabpanel">
		<form method="post" class="zl-settings-form">
			<?php wp_nonce_field( 'zeko_love_save_settings', '_zl_settings_nonce' ); ?>
			<input type="hidden" name="action" value="save_settings" />

			<div class="zl-card">
				<h2 class="zl-card-title"><?php echo esc_html__( 'Privacy', 'zeko-love' ); ?></h2>

				<label class="zl-checkbox-label">
					<input type="checkbox" name="show_in_search" value="1" <?php checked( ! empty( $settings['show_in_search'] ) ); ?> />
					<?php echo esc_html__( 'Show my profile in search', 'zeko-love' ); ?>
				</label>

				<label class="zl-checkbox-label">
					<input type="checkbox" name="verified_only" value="1" <?php checked( ! empty( $settings['verified_only'] ) ); ?> />
					<?php echo esc_html__( 'Only show me to verified users', 'zeko-love' ); ?>
				</label>
			</div>

			<div style="margin-top:16px;">
				<button type="submit" class="zl-btn zl-btn-primary zl-save-settings">
					<?php echo esc_html__( 'Save Privacy', 'zeko-love' ); ?>
				</button>
			</div>
		</form>
	</div>

	<!-- Notifications -->
	<div class="zl-settings-panel" id="zl-tab-notifications" role="tabpanel">
		<form method="post" class="zl-settings-form">
			<?php wp_nonce_field( 'zeko_love_save_settings', '_zl_settings_nonce' ); ?>
			<input type="hidden" name="action" value="save_settings" />

			<div class="zl-card">
				<h2 class="zl-card-title"><?php echo esc_html__( 'Notifications', 'zeko-love' ); ?></h2>

				<label class="zl-checkbox-label">
					<input type="checkbox" name="notify_match" value="1" <?php checked( ! empty( $settings['notify_match'] ) ); ?> />
					<?php echo esc_html__( 'New match email', 'zeko-love' ); ?>
				</label>

				<label class="zl-checkbox-label">
					<input type="checkbox" name="notify_message" value="1" <?php checked( ! empty( $settings['notify_message'] ) ); ?> />
					<?php echo esc_html__( 'New message email', 'zeko-love' ); ?>
				</label>

				<label class="zl-checkbox-label">
					<input type="checkbox" name="notify_date_reminder" value="1" <?php checked( ! empty( $settings['notify_date_reminder'] ) ); ?> />
					<?php echo esc_html__( 'Date reminder email', 'zeko-love' ); ?>
				</label>

				<label class="zl-checkbox-label">
					<input type="checkbox" name="notify_call_reminder" value="1" <?php checked( ! empty( $settings['notify_call_reminder'] ) ); ?> />
					<?php echo esc_html__( 'Call reminder email', 'zeko-love' ); ?>
				</label>
			</div>

			<div style="margin-top:16px;">
				<button type="submit" class="zl-btn zl-btn-primary zl-save-settings">
					<?php echo esc_html__( 'Save Notifications', 'zeko-love' ); ?>
				</button>
			</div>
		</form>
	</div>

	<!-- Blocked Users -->
	<div class="zl-settings-panel" id="zl-tab-blocked" role="tabpanel">
		<div class="zl-card">
			<h2 class="zl-card-title"><?php echo esc_html__( 'Blocked Users', 'zeko-love' ); ?></h2>

			<?php if ( ! empty( $blocked_users ) ) : ?>
				<ul class="zl-blocked-list">
					<?php foreach ( $blocked_users as $blocked ) : ?>
						<li class="zl-blocked-item" style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--zlove-gray-100);">
							<span><?php echo esc_html( $blocked['name'] ); ?></span>
							<button
								type="button"
								class="zl-btn zl-btn-sm zl-unblock-btn"
								data-id="<?php echo esc_attr( $blocked['id'] ); ?>"
							><?php echo esc_html__( 'Unblock', 'zeko-love' ); ?></button>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p style="color:var(--zlove-gray-500);"><?php echo esc_html__( 'No blocked users.', 'zeko-love' ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<!-- Account -->
	<?php
	/** Zeko PRO hook: member-facing verification requests (renders only when a premium module is active). */
	do_action( 'zeko_love_settings_verification', $user_id );
	?>
	<div class="zl-card" style="margin-top:32px;border-color:#e74c3c;">
		<h2 class="zl-card-title"><?php echo esc_html__( 'Account', 'zeko-love' ); ?></h2>
		<p style="color:var(--zlove-gray-500);font-size:14px;">
			<?php echo esc_html__( 'This will permanently delete your dating profile and all associated data (matches, messages, photos).', 'zeko-love' ); ?>
		</p>
		<button
			type="button"
			class="zl-btn zl-btn-danger zl-delete-profile"
			onclick="if ( confirm( '<?php echo esc_js( __( 'Are you sure you want to delete your dating profile? This action cannot be undone.', 'zeko-love' ) ); ?>' ) ) { document.getElementById( 'zl-delete-form' ).submit(); }"
		><?php echo esc_html__( 'Delete my dating profile', 'zeko-love' ); ?></button>
		<form id="zl-delete-form" method="post" style="display:none;">
			<?php wp_nonce_field( 'zeko_love_delete_profile', '_zl_delete_nonce' ); ?>
			<input type="hidden" name="action" value="delete_profile" />
		</form>
	</div>
</div>
