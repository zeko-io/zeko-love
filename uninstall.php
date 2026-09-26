<?php
/**
 * Uninstall script for Zeko Love.
 *
 * Runs when the plugin is deleted via WordPress admin.
 * Cleans up love-owned tables, user meta, plugin options, and scheduled
 * cron events. Shared ecosystem data is kept.
 *
 * @package Zeko_Love
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$prefix = $wpdb->prefix;

// Drop love-owned tables.
$tables = array(
	$prefix . 'zeko_love_profiles',
	$prefix . 'zeko_love_photos',
	$prefix . 'zeko_love_interests',
	$prefix . 'zeko_love_matches',
	$prefix . 'zeko_love_interactions',
	$prefix . 'zeko_love_date_scheduling',
	$prefix . 'zeko_love_conversations',
	$prefix . 'zeko_love_messages',
	$prefix . 'zeko_love_notifications',
	$prefix . 'zeko_love_call_plans',
	$prefix . 'zeko_love_call_listings',
	$prefix . 'zeko_love_call_slots',
	$prefix . 'zeko_love_call_bookings',
	$prefix . 'zeko_love_call_reviews',
	$prefix . 'zeko_love_call_usage',
);

foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

// Delete love-owned user meta. Never a bare `zeko_%` wildcard, which would
// wipe other ecosystem modules' meta.
$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	'DELETE FROM ' . $wpdb->usermeta . " WHERE meta_key LIKE 'zeko_love\_%'" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
);

// Delete plugin options.
$options = array(
	'zeko_love_settings',
	'zeko_love_calls_settings',
	'zeko_love_db_version',
	'zeko_love_menu_version',
	'zeko_love_pages_created',
);

foreach ( $options as $option ) {
	delete_option( $option );
}

// Clear all scheduled cron events.
$cron_hooks = array(
	'zeko_love_match_cleanup',
	'zeko_love_reminders',
);

foreach ( $cron_hooks as $hook ) {
	wp_clear_scheduled_hook( $hook );
}
