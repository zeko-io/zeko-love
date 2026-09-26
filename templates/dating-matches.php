<?php
/**
 * Zeko Love - Dating Matches Template
 *
 * @package Zeko_Love
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id = get_current_user_id();
?>

<div class="zl-matches-container">
	<h1><?php echo esc_html__( 'My Matches', 'zeko-love' ); ?></h1>

	<div class="zl-matches-tabs">
		<button type="button" class="nav-tab nav-tab-active" data-tab="matches">
			<?php echo esc_html__( 'Matches', 'zeko-love' ); ?>
		</button>
		<button type="button" class="nav-tab" data-tab="likes-sent">
			<?php echo esc_html__( 'Likes Sent', 'zeko-love' ); ?>
		</button>
		<button type="button" class="nav-tab" data-tab="likes-received">
			<?php echo esc_html__( 'Likes Received', 'zeko-love' ); ?>
		</button>
	</div>

	<div class="zl-matches-grid"></div>
</div>
