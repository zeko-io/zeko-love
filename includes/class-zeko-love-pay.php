<?php
/**
 * Zeko Love wallet & credits bridge to Zeko Pay.
 *
 * Provides a small, safe wrapper around the Zeko Pay SDK/ledger so the
 * dating module can credit "earned" funds, charge for paid features, and
 * gift credits between members — without touching the ledger directly.
 *
 * @package Zeko_Love
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Class Zeko_Love_Pay. */
class Zeko_Love_Pay {

	/**
	 * Whether the Zeko Pay wallet system is available.
	 */
	public static function active(): bool {
		return class_exists( 'Zeko_Pay_SDK' ) && class_exists( 'Zeko_Pay_Ledger' );
	}

	/**
	 * Current wallet balance for a user (formatted amount string).
	 *
	 * @param int $user_id User id.
	 */
	public static function balance( int $user_id ): string {
		if ( ! self::active() ) {
			return '0.00';
		}
		$sdk = new Zeko_Pay_SDK();
		return $sdk->get_balance( $user_id );
	}

	/**
	 * Credit a user's wallet (idempotent per reward key).
	 *
	 * @return array{success:bool,message:string,already?:bool}
	 * @param int    $user_id Recipient user id.
	 * @param float  $amount Amount to credit.
	 * @param string $key Reward key used for idempotency (e.g. 'first_match').
	 * @param string $label Human description of the credit.
	 * @param array  $meta Extra ledger metadata.
	 */
	public static function credit( int $user_id, float $amount, string $key, string $label, array $meta = array() ): array {
		if ( ! self::active() ) {
			return array(
				'success' => false,
				'message' => __( 'Wallet system is not available.', 'zeko-love' ),
			);
		}
		if ( $user_id <= 0 || $amount <= 0 ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid credit.', 'zeko-love' ),
			);
		}

		$flag = 'zeko_love_earned_' . sanitize_key( $key );
		if ( get_user_meta( $user_id, $flag, true ) ) {
			return array(
				'success' => true,
				'already' => true,
				'message' => __( 'Already rewarded.', 'zeko-love' ),
			);
		}

		$ledger    = Zeko_Pay_Ledger::instance();
		$wallet_id = $ledger->get_or_create_wallet( $user_id );
		$ref_id    = 'love-' . $key . '-' . $user_id . '-' . time();

		$result = $ledger->credit(
			$wallet_id,
			(string) $amount,
			$ref_id,
			'deposit',
			'',
			array_merge(
				array(
					'source'     => 'zeko_love',
					'reward_key' => $key,
					'type'       => 'earned_credit',
				),
				$meta
			)
		);

		if ( ! empty( $result['success'] ) ) {
			update_user_meta( $user_id, $flag, 1 );
			$love = Zeko_Love::instance();
			$love->get_db()->log_activity(
				$user_id,
				'credit_earned',
				/* translators: 1: earned amount. 2: earning reason */
				sprintf( __( 'You earned %1$s: %2$s', 'zeko-love' ), Zeko_Pay_Utils::format_currency( (string) $amount ), $label )
			);
		}

		return $result;
	}

	/**
	 * Charge a user's wallet for a paid dating feature.
	 *
	 * @return array
	 * @param int    $user_id User to charge.
	 * @param float  $amount Amount.
	 * @param string $desc Description.
	 * @param array  $meta Extra metadata.
	 */
	public static function charge( int $user_id, float $amount, string $desc, array $meta = array() ): array {
		if ( ! self::active() ) {
			return array(
				'success' => false,
				'message' => __( 'Wallet system is not available.', 'zeko-love' ),
			);
		}
		$sdk = new Zeko_Pay_SDK();
		return $sdk->charge(
			$user_id,
			$amount,
			$desc,
			array_merge( array( 'source' => 'zeko_love' ), $meta )
		);
	}

	/**
	 * Gift wallet credits from one member to another.
	 *
	 * @return array
	 * @param int    $from_id Sender user id.
	 * @param int    $to_id Recipient user id.
	 * @param float  $amount Amount to gift.
	 * @param string $message Optional note.
	 */
	public static function transfer_gift( int $from_id, int $to_id, float $amount, string $message = '' ): array {
		if ( ! self::active() ) {
			return array(
				'success' => false,
				'message' => __( 'Wallet system is not available.', 'zeko-love' ),
			);
		}
		if ( $from_id === $to_id ) {
			return array(
				'success' => false,
				'message' => __( 'You cannot gift yourself.', 'zeko-love' ),
			);
		}

		$sdk    = new Zeko_Pay_SDK();
		$result = $sdk->transfer(
			$from_id,
			$to_id,
			$amount,
			/* translators: 1: sender user ID. 2: gift message */
			sprintf( __( 'Credit gift from %1$s: %2$s', 'zeko-love' ), $from_id, $message )
		);

		if ( ! empty( $result['success'] ) ) {
			$db = Zeko_Love::instance()->get_db();
			$db->add_notification(
				$to_id,
				'gift',
				/* translators: 1: sender name. 2: gift amount */
				sprintf( __( '%1$s sent you a credit gift of %2$s', 'zeko-love' ), self::display_name( $from_id ), Zeko_Pay_Utils::format_currency( (string) $amount ) ),
				$from_id,
				0,
				'wallet'
			);
			/* translators: 1: gift amount. 2: sender name */
			$db->log_activity( $to_id, 'gift_received', sprintf( __( 'You received a credit gift of %1$s from %2$s', 'zeko-love' ), Zeko_Pay_Utils::format_currency( (string) $amount ), self::display_name( $from_id ) ) );
			/* translators: 1: gift amount. 2: recipient name */
			$db->log_activity( $from_id, 'gift_sent', sprintf( __( 'You sent a credit gift of %1$s to %2$s', 'zeko-love' ), Zeko_Pay_Utils::format_currency( (string) $amount ), self::display_name( $to_id ) ) );
		}

		return $result;
	}

	/**
	 * Display name helper (dating display name preferred).
	 *
	 * @param int $user_id User id.
	 */
	public static function display_name( int $user_id ): string {
		$db      = Zeko_Love::instance()->get_db();
		$profile = $db->get_profile( $user_id );
		if ( $profile && ! empty( $profile['display_name'] ) ) {
			return $profile['display_name'];
		}
		$user = get_userdata( $user_id );
		return $user ? $user->display_name : (string) $user_id;
	}
}

/**
 * Earn-credit configuration for the dating module.
 * These rewards are paid once per user (guarded by user meta flags).
 * Filterable via 'zeko_love_earn_config'.
 *
 * @return array{profile_complete?:array,photo_verified?:array,first_match?:array,first_like?:array}
 */
function zeko_love_get_earn_config(): array {
	$config = array(
		'profile_complete' => array(
			'amount' => 2.00,
			'label'  => __( 'Completing your dating profile', 'zeko-love' ),
		),
		'photo_verified'   => array(
			'amount' => 1.00,
			'label'  => __( 'Getting a photo verified', 'zeko-love' ),
		),
		'first_match'      => array(
			'amount' => 5.00,
			'label'  => __( 'Your first match', 'zeko-love' ),
		),
		'first_like'       => array(
			'amount' => 0.50,
			'label'  => __( 'Sending your first like', 'zeko-love' ),
		),
	);

	return apply_filters( 'zeko_love_earn_config', $config );
}
