<?php
/**
 * Zeko Love - Dating Messages Template
 *
 * @package Zeko_Love
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id = get_current_user_id();
$love    = Zeko_Love::instance();
$db      = $love->get_db();

// Support opening (or creating) a conversation with a specific user via ?to=ID.
$open_conv_id = 0;
if ( $user_id && isset( $_GET['to'] ) && absint( $_GET['to'] ) > 0 ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$to_user_id = absint( $_GET['to'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( $to_user_id !== $user_id && ! $db->is_blocked( $user_id, $to_user_id ) && ! $db->is_blocked( $to_user_id, $user_id ) ) {
		$open_conv_id = (int) $db->create_conversation( $user_id, $to_user_id );
	}
}
?>

<div class="zl-messages"<?php echo $open_conv_id ? ' data-open-conv="' . esc_attr( $open_conv_id ) . '"' : ''; ?>>
	<div class="zl-messages-sidebar">
		<div class="zl-messages-header">
			<h2><?php echo esc_html__( 'Messages', 'zeko-love' ); ?></h2>
		</div>
		<div class="zl-conversations"></div>
	</div>

	<div class="zl-thread">
		<div class="zl-empty-state">
			<span class="dashicons dashicons-email-alt"></span>
			<h3><?php echo esc_html__( 'No conversation selected', 'zeko-love' ); ?></h3>
			<p><?php echo esc_html__( 'Pick a conversation to start chatting.', 'zeko-love' ); ?></p>
		</div>
	</div>
</div>
