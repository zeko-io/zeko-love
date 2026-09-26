<?php
/**
 * Zeko Love - Dating Browse Template
 *
 * @package Zeko_Love
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id = get_current_user_id();

// Daily Picks: top compatibility matches, rendered server-side.
$picks_html = '';
$love       = Zeko_Love::instance();
$db         = $love->get_db();
$matching   = $love->get_matching();

if ( $matching && $user_id ) {
	$picks = $matching->find_matches( $user_id, 6 );

	if ( empty( $picks ) ) {
		$picks = $db->search_profiles( array(), 1, 6 );
	}

	if ( ! empty( $picks ) ) {
		$enriched = array();
		foreach ( $picks as $pick ) {
			$full = $db->get_profile( (int) $pick['user_id'] );
			if ( ! $full ) {
				continue;
			}
			$enriched[] = $full;
		}
		if ( ! empty( $enriched ) ) {
			$picks_html = '<section class="zl-daily-picks">'
				. '<h2>' . esc_html__( 'Daily Picks', 'zeko-love' ) . '</h2>'
				. '<div class="zl-profiles-grid">'
				. $love->get_public()->render_profile_cards( $enriched )
				. '</div>'
				. '</section>';
		}
	}
}
?>

<div class="zl-browse">
	<?php if ( $picks_html ) : ?>
		<?php echo $picks_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- server-rendered cards. ?>
	<?php endif; ?>

	<h2 class="zl-browse-heading"><?php echo esc_html__( 'Find Matches', 'zeko-love' ); ?></h2>

	<form class="zl-search-form zl-search-filters" method="get">
		<div class="zl-search-fields">
			<div class="zl-field">
				<input
					type="text"
					name="name"
					class="zl-input"
					placeholder="<?php echo esc_attr__( 'Name', 'zeko-love' ); ?>"
					value="<?php echo isset( $_GET['name'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['name'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display flag; no state change. ?>"
				/>
			</div>

			<div class="zl-field">
				<select name="gender" class="zl-input">
					<option value=""><?php echo esc_html__( 'All', 'zeko-love' ); ?></option>
					<option value="male" <?php selected( isset( $_GET['gender'] ) && 'male' === $_GET['gender'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display flag; no state change. ?>><?php echo esc_html__( 'Men', 'zeko-love' ); ?></option>
					<option value="female" <?php selected( isset( $_GET['gender'] ) && 'female' === $_GET['gender'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display flag; no state change. ?>><?php echo esc_html__( 'Women', 'zeko-love' ); ?></option>
				</select>
			</div>

			<div class="zl-field">
				<input
					type="number"
					name="age_min"
					class="zl-input zl-age-input"
					placeholder="<?php echo esc_attr__( 'Min age', 'zeko-love' ); ?>"
					min="18"
					max="120"
					value="<?php echo isset( $_GET['age_min'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['age_min'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display flag; no state change. ?>"
				/>
			</div>

			<div class="zl-field">
				<input
					type="number"
					name="age_max"
					class="zl-input zl-age-input"
					placeholder="<?php echo esc_attr__( 'Max age', 'zeko-love' ); ?>"
					min="18"
					max="120"
					value="<?php echo isset( $_GET['age_max'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['age_max'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display flag; no state change. ?>"
				/>
			</div>

			<div class="zl-field">
				<select name="relationship_goal" class="zl-input">
					<option value=""><?php echo esc_html__( 'Any goal', 'zeko-love' ); ?></option>
					<option value="casual" <?php selected( isset( $_GET['relationship_goal'] ) && 'casual' === $_GET['relationship_goal'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display flag; no state change. ?>><?php echo esc_html__( 'Casual', 'zeko-love' ); ?></option>
					<option value="long-term" <?php selected( isset( $_GET['relationship_goal'] ) && 'long-term' === $_GET['relationship_goal'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display flag; no state change. ?>><?php echo esc_html__( 'Long-term', 'zeko-love' ); ?></option>
					<option value="marriage" <?php selected( isset( $_GET['relationship_goal'] ) && 'marriage' === $_GET['relationship_goal'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display flag; no state change. ?>><?php echo esc_html__( 'Marriage', 'zeko-love' ); ?></option>
					<option value="friendship" <?php selected( isset( $_GET['relationship_goal'] ) && 'friendship' === $_GET['relationship_goal'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display flag; no state change. ?>><?php echo esc_html__( 'Friendship', 'zeko-love' ); ?></option>
					<option value="not-sure" <?php selected( isset( $_GET['relationship_goal'] ) && 'not-sure' === $_GET['relationship_goal'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display flag; no state change. ?>><?php echo esc_html__( 'Not sure', 'zeko-love' ); ?></option>
				</select>
			</div>

			<div class="zl-field">
				<button type="button" class="zl-btn zl-btn-primary zl-search-btn"><?php echo esc_html__( 'Search', 'zeko-love' ); ?></button>
			</div>
		</div>
	</form>

	<div class="zl-profiles-grid zl-browse-results"></div>

	<div class="zl-pagination"></div>
</div>
