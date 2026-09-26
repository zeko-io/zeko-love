<?php
/**
 * Zeko Love - Dating Profile Template
 *
 * @package Zeko_Love
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id = get_current_user_id();
$profile = isset( $profile ) ? (array) $profile : array();
$photos  = isset( $photos ) ? (array) $photos : array();
?>

<div class="zl-profile" style="max-width:780px;margin:0 auto;">
	<h1><?php echo esc_html__( 'My Dating Profile', 'zeko-love' ); ?></h1>

	<?php
	$pricing     = function_exists( 'zeko_love_get_pricing' ) ? zeko_love_get_pricing() : array();
	$boost_price = isset( $pricing['boost_price'] ) ? (float) $pricing['boost_price'] : 10.00;
	$boost_hours = isset( $pricing['boost_duration_hours'] ) ? (int) $pricing['boost_duration_hours'] : 24;
	$love_db     = function_exists( 'zeko_love' ) ? zeko_love()->get_db() : null;
	$boost_until = $love_db ? $love_db->get_boost_until( $user_id ) : '';
	$is_boosted  = $love_db ? $love_db->is_boosted( $user_id ) : false;
	?>

	<!-- Boost -->
	<div class="zl-card zl-boost-card">
		<h2 class="zl-card-title"><?php echo esc_html__( 'Profile Boost', 'zeko-love' ); ?></h2>
		<?php if ( $is_boosted && $boost_until ) : ?>
			<p class="zl-boost-status zl-boost-active">
				<span class="dashicons dashicons-star-filled"></span>
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: boost expiry date and time */
						__( 'Your profile is boosted until %s.', 'zeko-love' ),
						date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $boost_until . ' UTC' ) + ( (int) get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) )
					)
				);
				?>
			</p>
		<?php else : ?>
			<p class="zl-boost-status">
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: number of boost hours. 2: boost price */
						__( 'Boost your profile to the top of search results for %1$d hours for %2$s.', 'zeko-love' ),
						$boost_hours,
						number_format_i18n( $boost_price, 2 )
					)
				);
				?>
			</p>
			<button type="button" class="zl-btn zl-btn-primary zl-boost-profile">
				<?php echo esc_html__( 'Boost Profile', 'zeko-love' ); ?>
			</button>
		<?php endif; ?>
	</div>

	<?php require __DIR__ . '/partials/profile-fields.php'; ?>
</div>
