<?php
/**
 * Zeko Love — WordPress personal-data exporter and eraser.
 *
 * Registers with tools.php > Export Personal Data / Erase Personal Data so
 * site owners can fulfil data-protection requests for dating profiles, photos,
 * interests, matches, interactions, messages, and call data.
 *
 * @package Zeko_Love
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the exporter and eraser callbacks.
 */
function zeko_love_register_privacy_callbacks(): void {
	add_filter( 'wp_privacy_personal_data_exporters', 'zeko_love_privacy_register_exporter' );
	add_filter( 'wp_privacy_personal_data_erasers', 'zeko_love_privacy_register_eraser' );
}
add_action( 'init', 'zeko_love_register_privacy_callbacks' );

/**
 * Register the personal-data exporter.
 *
 * @param array $exporters Exporters.
 */
function zeko_love_privacy_register_exporter( array $exporters ): array {
	$exporters['zeko-love'] = array(
		'exporter_friendly_name' => __( 'Zeko Love', 'zeko-love' ),
		'callback'               => 'zeko_love_privacy_export',
	);
	return $exporters;
}

/**
 * Register the personal-data eraser.
 *
 * @param array $erasers Erasers.
 */
function zeko_love_privacy_register_eraser( array $erasers ): array {
	$erasers['zeko-love'] = array(
		'eraser_friendly_name' => __( 'Zeko Love', 'zeko-love' ),
		'callback'             => 'zeko_love_privacy_erase',
	);
	return $erasers;
}

/**
 * Get a prepared DB instance (null when the plugin is not active).
 */
function zeko_love_privacy_db(): ?Zeko_Love_DB {
	if ( ! class_exists( 'Zeko_Love_DB' ) ) {
		return null;
	}
	return new Zeko_Love_DB();
}

/**
 * Export a user's love data.
 *
 * @return array{data: array[], done: bool}
 * @param string $email_address User who requested the export.
 * @param int    $_page page.
 */
function zeko_love_privacy_export( string $email_address, int $_page = 1 ): array {
	$user = get_user_by( 'email', $email_address );
	if ( ! $user ) {
		return array(
			'data' => array(),
			'done' => true,
		);
	}
	$user_id = (int) $user->ID;

	$db = zeko_love_privacy_db();
	if ( ! $db ) {
		return array(
			'data' => array(),
			'done' => true,
		);
	}

	$data = array();

	$profile = $db->get_profile( $user_id );
	if ( $profile ) {
		$rows   = array(
			array(
				'name'  => __( 'Display name', 'zeko-love' ),
				'value' => $profile['display_name'] ?? '',
			),
			array(
				'name'  => __( 'Bio', 'zeko-love' ),
				'value' => $profile['bio'] ?? '',
			),
			array(
				'name'  => __( 'Date of birth', 'zeko-love' ),
				'value' => $profile['dob'] ?? '',
			),
			array(
				'name'  => __( 'Gender', 'zeko-love' ),
				'value' => $profile['gender'] ?? '',
			),
			array(
				'name'  => __( 'Interested in', 'zeko-love' ),
				'value' => $profile['interested_in'] ?? '',
			),
			array(
				'name'  => __( 'Latitude', 'zeko-love' ),
				'value' => $profile['location_lat'] ?? '',
			),
			array(
				'name'  => __( 'Longitude', 'zeko-love' ),
				'value' => $profile['location_lng'] ?? '',
			),
			array(
				'name'  => __( 'Relationship goal', 'zeko-love' ),
				'value' => $profile['relationship_goal'] ?? '',
			),
			array(
				'name'  => __( 'Height (cm)', 'zeko-love' ),
				'value' => $profile['height_cm'] ?? '',
			),
			array(
				'name'  => __( 'Occupation', 'zeko-love' ),
				'value' => $profile['occupation'] ?? '',
			),
			array(
				'name'  => __( 'Education', 'zeko-love' ),
				'value' => $profile['education'] ?? '',
			),
			array(
				'name'  => __( 'Smoking', 'zeko-love' ),
				'value' => $profile['smoking'] ?? '',
			),
			array(
				'name'  => __( 'Drinking', 'zeko-love' ),
				'value' => $profile['drinking'] ?? '',
			),
			array(
				'name'  => __( 'Has children', 'zeko-love' ),
				'value' => $profile['has_children'] ?? '',
			),
			array(
				'name'  => __( 'Wants children', 'zeko-love' ),
				'value' => $profile['wants_children'] ?? '',
			),
			array(
				'name'  => __( 'Religion', 'zeko-love' ),
				'value' => $profile['religion'] ?? '',
			),
			array(
				'name'  => __( 'Ethnicity', 'zeko-love' ),
				'value' => $profile['ethnicity'] ?? '',
			),
		);
		$data[] = array(
			'group_id'    => 'zeko-love-profile',
			'group_label' => __( 'Zeko Love — Dating profile', 'zeko-love' ),
			'item_id'     => 'profile-' . $user_id,
			'data'        => $rows,
		);
	}

	$photos = $db->get_profile_photos( $user_id );
	foreach ( $photos as $photo ) {
		$data[] = array(
			'group_id'    => 'zeko-love-photos',
			'group_label' => __( 'Zeko Love — Photos', 'zeko-love' ),
			'item_id'     => 'photo-' . $photo['photo_id'],
			'data'        => array(
				array(
					'name'  => __( 'Photo URL', 'zeko-love' ),
					'value' => $photo['photo_url'] ?? '',
				),
				array(
					'name'  => __( 'Primary', 'zeko-love' ),
					'value' => $photo['is_primary'] ?? '',
				),
			),
		);
	}

	$interests = $db->get_interests( $user_id );
	if ( $interests ) {
		$rows = array();
		foreach ( $interests as $interest ) {
			$rows[] = array(
				'name'  => $interest['category'] ?? 'Interest',
				'value' => $interest['value'] ?? '',
			);
		}
		$data[] = array(
			'group_id'    => 'zeko-love-interests',
			'group_label' => __( 'Zeko Love — Interests', 'zeko-love' ),
			'item_id'     => 'interests-' . $user_id,
			'data'        => $rows,
		);
	}

	return array(
		'data' => $data,
		'done' => true,
	);
}

/**
 * Erase a user's love data.
 *
 * @return array{items_removed: bool, items_retained: bool, messages: string[], done: bool}
 * @param string $email_address User who requested erasure.
 * @param int    $_page page.
 */
function zeko_love_privacy_erase( string $email_address, int $_page = 1 ): array {
	$user = get_user_by( 'email', $email_address );
	if ( ! $user ) {
		return array(
			'items_removed'  => false,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);
	}

	$db = zeko_love_privacy_db();
	if ( ! $db ) {
		return array(
			'items_removed'  => false,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);
	}

	$removed = $db->delete_user_data( (int) $user->ID );

	return array(
		'items_removed'  => $removed,
		'items_retained' => false,
		'messages'       => array(
			$removed
				? __( 'Zeko Love data for this user was deleted from all dating tables, including profiles, photos, interests, matches, conversations, messages, and call records.', 'zeko-love' )
				: __( 'No Zeko Love data was found for this user.', 'zeko-love' ),
			__( 'Messages and conversation threads are removed entirely when either participant erases their data.', 'zeko-love' ),
		),
		'done'           => true,
	);
}
