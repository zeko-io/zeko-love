<?php
/**
 * Plugin Name:       Zeko Love
 * Plugin URI:        https://ozconsultz.com/zeko-love
 * Description:       Full-featured dating and matchmaking module for the Zeko ecosystem with smart matching, date scheduling, photo verification, and safety tools.
 * Version:           1.4.1
 * Author:            Zeko Team
 * Author URI:        https://ozconsultz.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       zeko-love
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Tested up to:      7.1.2
 *
 * @package Zeko_ZEKO_LOVE
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'ZEKO_LOVE_VERSION' ) ) {
	define( 'ZEKO_LOVE_VERSION', '1.4.1' );
}

if ( ! defined( 'ZEKO_LOVE_PLUGIN_PATH' ) ) {
	define( 'ZEKO_LOVE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'ZEKO_LOVE_PLUGIN_URL' ) ) {
	define( 'ZEKO_LOVE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'ZEKO_LOVE_PLUGIN_BASENAME' ) ) {
	define( 'ZEKO_LOVE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'ZEKO_LOVE_DB_VERSION' ) ) {
	define( 'ZEKO_LOVE_DB_VERSION', '1.5.0' );
}

require_once ZEKO_LOVE_PLUGIN_PATH . 'includes/class-zeko-love.php';
require_once ZEKO_LOVE_PLUGIN_PATH . 'includes/privacy/class-zeko-love-privacy.php';

/**
 * Zeko love init.
 */
function zeko_love_init() {
	load_plugin_textdomain( 'zeko-love', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	$instance = Zeko_Love::instance();

	// One-time schema upgrade (adds the notifications table since v1.1.0,.
	// profiles.last_active since v1.2.0, profiles.boosted_until since v1.3.0).
	$installed = get_option( 'zeko_love_db_version', '0' );
	if ( version_compare( $installed, ZEKO_LOVE_DB_VERSION, '<' ) ) {
		$instance->get_db()->create_tables();
	}

	// Self-heal the daily cron schedules so installs upgraded without a fresh.
	// (de)activation pick up the reminder + cleanup events on next load.
	if ( ! wp_next_scheduled( 'zeko_love_match_cleanup' ) ) {
		wp_schedule_event( time(), 'daily', 'zeko_love_match_cleanup' );
	}
	if ( ! wp_next_scheduled( 'zeko_love_reminders' ) ) {
		wp_schedule_event( time(), 'daily', 'zeko_love_reminders' );
	}

	return $instance;
}
add_action( 'plugins_loaded', 'zeko_love_init' );

/**
 * Zeko love.
 */
function zeko_love() {
	return Zeko_Love::instance();
}

/**
 * Zeko love activate.
 */
function zeko_love_activate() {
	require_once ZEKO_LOVE_PLUGIN_PATH . 'includes/db/class-zeko-love-db.php';
	$db = new Zeko_Love_DB();
	$db->create_tables();

	zeko_love_create_shortcode_pages();
	flush_rewrite_rules();

	if ( ! wp_next_scheduled( 'zeko_love_match_cleanup' ) ) {
		wp_schedule_event( time(), 'daily', 'zeko_love_match_cleanup' );
	}

	if ( ! wp_next_scheduled( 'zeko_love_reminders' ) ) {
		wp_schedule_event( time(), 'daily', 'zeko_love_reminders' );
	}
}

/**
 * Zeko love deactivate.
 */
function zeko_love_deactivate() {
	$ts = wp_next_scheduled( 'zeko_love_match_cleanup' );
	if ( $ts ) {
		wp_unschedule_event( $ts, 'zeko_love_match_cleanup' );
	}

	$ts = wp_next_scheduled( 'zeko_love_reminders' );
	if ( $ts ) {
		wp_unschedule_event( $ts, 'zeko_love_reminders' );
	}
}

/**
 * Uninstall: drop all tables + delete options + remove pages + menu items.
 */
function zeko_love_uninstall() {
	if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
		return;
	}

	global $wpdb;

	// Drop all love tables (single source of truth: Zeko_Love_DB::drop_tables()).
	require_once ZEKO_LOVE_PLUGIN_PATH . 'includes/db/class-zeko-love-db.php';
	( new Zeko_Love_DB() )->drop_tables();

	// Clear scheduled cron.
	wp_clear_scheduled_hook( 'zeko_love_match_cleanup' );
	wp_clear_scheduled_hook( 'zeko_love_reminders' );

	// Remove love-created pages — ownership-verified only (marker, the module's.
	// own recorded page IDs, or a legacy page matching a love slug whose.
	// content carries a love shortcode). A user's unrelated page that merely.
	// shares a slug is never deleted.
	$love_page_ids = array();
	$love_slugs    = array( 'dating-browse', 'dating-profile', 'dating-matches', 'dating-messages', 'dating-schedule', 'dating-settings', 'dating-calls' );
	if ( class_exists( 'Zeko_Core_Helpers' ) ) {
		$love_page_ids = Zeko_Core_Helpers::get_instance()->get_plugin_owned_page_ids( 'love' );
		foreach ( $love_slugs as $slug ) {
			$recorded = (int) get_option( 'zeko_love_' . $slug . '_page_id', 0 );
			if ( $recorded && 'page' === get_post_type( $recorded ) ) {
				$love_page_ids[] = $recorded;
			}
			$legacy = Zeko_Core_Helpers::get_instance()->get_page_by_slug( $slug );
			if ( $legacy && false !== strpos( (string) get_post_field( 'post_content', $legacy->ID ), '[zeko_love' ) ) {
				Zeko_Core_Helpers::mark_plugin_page( (int) $legacy->ID, 'love' );
				$love_page_ids[] = (int) $legacy->ID;
			}
		}
	}
	$love_page_ids = array_values( array_unique( array_map( 'intval', array_filter( $love_page_ids ) ) ) );

	// Remove love nav menu items before deleting the pages they point to.
	$menu_locations = get_nav_menu_locations();
	foreach ( $menu_locations as $menu_id ) {
		$items = wp_get_nav_menu_items( (int) $menu_id );
		if ( ! $items ) {
			continue;
		}
		foreach ( $items as $item ) {
			$is_love_page_link = 'post_type' === $item->type && 'page' === $item->object && in_array( (int) $item->object_id, $love_page_ids, true );
			$is_love_custom    = 'custom' === $item->type && false !== strpos( (string) $item->url, 'dating-' );
			if ( $is_love_page_link || $is_love_custom ) {
				wp_delete_post( (int) $item->ID, true );
			}
		}
	}

	foreach ( $love_page_ids as $page_id ) {
		if ( function_exists( 'zeko_is_plugin_owned_page' ) && zeko_is_plugin_owned_page( $page_id, 'love' ) ) {
			wp_delete_post( $page_id, true );
		}
	}

	// Options (incl. the per-page ID options).
	delete_option( 'zeko_love_db_version' );
	delete_option( 'zeko_love_pages_created' );
	delete_option( 'zeko_love_settings' );
	delete_option( 'zeko_love_calls_settings' );
	delete_option( 'zeko_love_menu_version' );
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'zeko_love_%_page_id'" );

	// Love-owned user meta (credit flags, settings, payout). The shared.
	// zeko_timezone key used by mentor is intentionally left alone.
	$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'zeko_love_%'" );
}

/**
 * Zeko love create shortcode pages.
 */
function zeko_love_create_shortcode_pages() {
	$pages = array(
		'dating-browse'   => array(
			'title'   => __( 'Find Matches', 'zeko-love' ),
			'content' => '[zeko_love_browse]',
		),
		'dating-profile'  => array(
			'title'   => __( 'My Dating Profile', 'zeko-love' ),
			'content' => '[zeko_love_profile]',
		),
		'dating-matches'  => array(
			'title'   => __( 'My Matches', 'zeko-love' ),
			'content' => '[zeko_love_matches]',
		),
		'dating-messages' => array(
			'title'   => __( 'Messages', 'zeko-love' ),
			'content' => '[zeko_love_messages]',
		),
		'dating-schedule' => array(
			'title'   => __( 'Dates', 'zeko-love' ),
			'content' => '[zeko_love_dates]',
		),
		'dating-settings' => array(
			'title'   => __( 'Dating Settings', 'zeko-love' ),
			'content' => '[zeko_love_settings]',
		),
		'dating-calls'    => array(
			'title'   => __( 'Calls & Availability', 'zeko-love' ),
			'content' => '[zeko_love_calls]',
		),
	);

	foreach ( $pages as $slug => $page ) {
		$existing = class_exists( 'Zeko_Core_Helpers' )
			? Zeko_Core_Helpers::get_instance()->get_page_by_slug( $slug )
			: get_page_by_path( $slug );
		if ( ! $existing ) {
			$result = wp_insert_post(
				array(
					'post_title'   => $page['title'],
					'post_content' => $page['content'],
					'post_status'  => 'publish',
					'post_type'    => 'page',
					'post_name'    => $slug,
				)
			);
			if ( is_wp_error( $result ) ) {
				error_log( 'Zeko Love: Failed to create page "' . $slug . '": ' . $result->get_error_message() );
				continue;
			}
			if ( function_exists( 'zeko_mark_plugin_page' ) ) {
				zeko_mark_plugin_page( $result, 'love' );
			}
			$existing = get_post( $result );
		}

		if ( $existing ) {
			update_option( 'zeko_love_' . $slug . '_page_id', (int) $existing->ID );
		}
	}
}

/**
 * Resolve the permalink for a Zeko Love page by its slug.
 * Prefers the stored page-ID option, falls back to a path lookup, then
 * finally to a best-effort slug URL. Delegates to the ecosystem page-URL
 * registry (Zeko Core) when present; the local resolution is the fallback
 * so love still works without it.
 *
 * @return string
 * @param string $slug Page slug (e.g. 'dating-browse').
 */
function zeko_love_page_url( string $slug ): string {
	if ( class_exists( 'Zeko_Core_Helpers' ) && method_exists( 'Zeko_Core_Helpers', 'get_page_url' ) ) {
		return Zeko_Core_Helpers::get_instance()->get_page_url( 'love', $slug );
	}

	$page_id = (int) get_option( 'zeko_love_' . $slug . '_page_id', 0 );

	if ( $page_id && 'publish' === get_post_status( $page_id ) ) {
		return get_permalink( $page_id );
	}

	$page = class_exists( 'Zeko_Core_Helpers' )
		? Zeko_Core_Helpers::get_instance()->get_page_by_slug( $slug )
		: get_page_by_path( $slug );
	if ( $page ) {
		update_option( 'zeko_love_' . $slug . '_page_id', (int) $page->ID );
		return get_permalink( $page );
	}

	return home_url( '/' . $slug . '/' );
}

/**
 * Zeko love maybe create pages.
 */
function zeko_love_maybe_create_pages() {
	$pages_created = get_option( 'zeko_love_pages_created', false );
	if ( ! $pages_created ) {
		zeko_love_create_shortcode_pages();
		update_option( 'zeko_love_pages_created', true );
		return;
	}

	// Backfill page-ID options for installs upgraded to v1.1.0.
	$needs_ids = false;
	foreach ( array( 'dating-browse', 'dating-profile', 'dating-matches', 'dating-messages', 'dating-schedule', 'dating-settings', 'dating-calls' ) as $slug ) {
		if ( ! get_option( 'zeko_love_' . $slug . '_page_id', 0 ) ) {
			$needs_ids = true;
			break;
		}
	}
	if ( $needs_ids ) {
		zeko_love_create_shortcode_pages();
	}
}

register_activation_hook( __FILE__, 'zeko_love_activate' );
register_deactivation_hook( __FILE__, 'zeko_love_deactivate' );
register_uninstall_hook( __FILE__, 'zeko_love_uninstall' );

/**
 * Get the conversation icebreaker prompts shown in the message composer.
 * Filterable via `zeko_love_icebreakers`.
 *
 * @return string[]
 */
function zeko_love_get_icebreakers(): array {
	$icebreakers = array(
		__( 'What is one thing on your bucket list this year?', 'zeko-love' ),
		__( 'If you could travel anywhere tomorrow, where would you go?', 'zeko-love' ),
		__( 'What is your favourite way to unwind after a long week?', 'zeko-love' ),
		__( 'Coffee or tea — and what is your go-to order?', 'zeko-love' ),
		__( 'What is the best meal you have ever had?', 'zeko-love' ),
		__( 'What show are you currently obsessed with?', 'zeko-love' ),
	);

	return apply_filters( 'zeko_love_icebreakers', $icebreakers );
}

/**
 * Paid feature pricing (super-like, profile boost).
 * Stored in the zeko_love_settings option, filterable via
 * 'zeko_love_pricing'. Falls back to defaults when unset.
 *
 * @return array{super_like_price: float, boost_price: float, boost_duration_hours: int}
 */
function zeko_love_get_pricing(): array {
	$settings = get_option( 'zeko_love_settings', array() );

	$pricing = array(
		'super_like_price'     => isset( $settings['super_like_price'] ) ? max( 0.0, (float) $settings['super_like_price'] ) : 5.00,
		'boost_price'          => isset( $settings['boost_price'] ) ? max( 0.0, (float) $settings['boost_price'] ) : 10.00,
		'boost_duration_hours' => isset( $settings['boost_duration_hours'] ) ? max( 1, (int) $settings['boost_duration_hours'] ) : 24,
	);

	return apply_filters( 'zeko_love_pricing', $pricing );
}
