<?php
/**
 * Zeko Love - Dating Schedule Template
 *
 * @package Zeko_Love
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id = get_current_user_id();
?>

<div class="zl-dates-container">
	<h1><?php echo esc_html__( 'My Dates', 'zeko-love' ); ?></h1>

	<div class="zl-dates-tabs">
		<button type="button" class="nav-tab nav-tab-active" data-tab="upcoming">
			<?php echo esc_html__( 'Upcoming', 'zeko-love' ); ?>
		</button>
		<button type="button" class="nav-tab" data-tab="past">
			<?php echo esc_html__( 'Past', 'zeko-love' ); ?>
		</button>
		<button type="button" class="nav-tab" data-tab="cancelled">
			<?php echo esc_html__( 'Cancelled', 'zeko-love' ); ?>
		</button>
	</div>

	<div class="zl-dates-list"></div>
</div>
