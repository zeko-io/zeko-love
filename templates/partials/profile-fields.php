<?php
/**
 * Zeko Love - Profile Fields Partial
 *
 * Shared by the dating-profile page and the Settings > Profile tab. Renders
 * the full editable profile form (basic info, lifestyle, preferences,
 * interests, photos). Posts via the `.zl-save-profile` AJAX handler.
 *
 * Expects (all optional):
 *   $profile   array  Profile row from Zeko_Love_DB::get_profile().
 *   $photos    array  Photo rows from Zeko_Love_DB::get_profile_photos().
 *   $interests array  Flat list of interest values (e.g. array of category slugs).
 *
 * @package Zeko_Love
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$profile   = isset( $profile ) ? (array) $profile : array();
$photos    = isset( $photos ) ? (array) $photos : array();
$interests = isset( $interests ) ? (array) $interests : array();

$gender_options = array(
	'male'       => __( 'Male', 'zeko-love' ),
	'female'     => __( 'Female', 'zeko-love' ),
	'non-binary' => __( 'Non-binary', 'zeko-love' ),
);

$interested_options = array(
	'men'      => __( 'Men', 'zeko-love' ),
	'women'    => __( 'Women', 'zeko-love' ),
	'everyone' => __( 'Everyone', 'zeko-love' ),
);

$yes_no_undecided = array(
	''          => __( 'Select...', 'zeko-love' ),
	'yes'       => __( 'Yes', 'zeko-love' ),
	'no'        => __( 'No', 'zeko-love' ),
	'undecided' => __( 'Undecided', 'zeko-love' ),
);

$prefer_options = array(
	''               => __( 'Select...', 'zeko-love' ),
	'yes'            => __( 'Yes', 'zeko-love' ),
	'no'             => __( 'No', 'zeko-love' ),
	'prefer-not-say' => __( 'Prefer not to say', 'zeko-love' ),
);

$smoking_options = array(
	''           => __( 'Select...', 'zeko-love' ),
	'non-smoker' => __( 'Non-smoker', 'zeko-love' ),
	'occasional' => __( 'Occasional', 'zeko-love' ),
	'regular'    => __( 'Regular', 'zeko-love' ),
);

$drinking_options = array(
	''            => __( 'Select...', 'zeko-love' ),
	'non-drinker' => __( 'Non-drinker', 'zeko-love' ),
	'social'      => __( 'Social', 'zeko-love' ),
	'regular'     => __( 'Regular', 'zeko-love' ),
);

$goal_options = array(
	''           => __( 'Select...', 'zeko-love' ),
	'casual'     => __( 'Casual', 'zeko-love' ),
	'long-term'  => __( 'Long-term', 'zeko-love' ),
	'marriage'   => __( 'Marriage', 'zeko-love' ),
	'friendship' => __( 'Friendship', 'zeko-love' ),
	'not-sure'   => __( 'Not sure', 'zeko-love' ),
);

$interest_categories = array(
	'sports'       => __( 'Sports', 'zeko-love' ),
	'music'        => __( 'Music', 'zeko-love' ),
	'travel'       => __( 'Travel', 'zeko-love' ),
	'food'         => __( 'Food', 'zeko-love' ),
	'art'          => __( 'Art', 'zeko-love' ),
	'fitness'      => __( 'Fitness', 'zeko-love' ),
	'reading'      => __( 'Reading', 'zeko-love' ),
	'movies'       => __( 'Movies', 'zeko-love' ),
	'gaming'       => __( 'Gaming', 'zeko-love' ),
	'outdoors'     => __( 'Outdoors', 'zeko-love' ),
	'technology'   => __( 'Technology', 'zeko-love' ),
	'fashion'      => __( 'Fashion', 'zeko-love' ),
	'photography'  => __( 'Photography', 'zeko-love' ),
	'dancing'      => __( 'Dancing', 'zeko-love' ),
	'volunteering' => __( 'Volunteering', 'zeko-love' ),
);

$interest_values = array_map( 'sanitize_text_field', array_map( 'strval', $interests ) );
?>

<form method="post" enctype="multipart/form-data" class="zl-profile-form">
	<?php wp_nonce_field( 'zeko_love_save_profile', '_zl_profile_nonce' ); ?>
	<input type="hidden" name="action" value="save_profile" />

	<!-- Basic Info -->
	<div class="zl-card">
		<h2 class="zl-card-title"><?php echo esc_html__( 'Basic Info', 'zeko-love' ); ?></h2>

		<div class="zl-field">
			<label class="zl-label" for="zl-display-name"><?php echo esc_html__( 'Display Name', 'zeko-love' ); ?></label>
			<input type="text" id="zl-display-name" name="display_name" class="zl-input" value="<?php echo isset( $profile['display_name'] ) ? esc_attr( $profile['display_name'] ) : ''; ?>" />
		</div>

		<div class="zl-field">
			<label class="zl-label" for="zl-bio"><?php echo esc_html__( 'Bio', 'zeko-love' ); ?></label>
			<textarea id="zl-bio" name="bio" class="zl-input zl-textarea" rows="4"><?php echo isset( $profile['bio'] ) ? esc_textarea( $profile['bio'] ) : ''; ?></textarea>
			<?php if ( class_exists( 'Zeko_AI_Writer_UI' ) ) : ?>
				<?php
				echo wp_kses_post(
					(string) Zeko_AI_Writer_UI::button(
						array(
							'preset' => 'dating_bio',
							'target' => '#zl-bio',
						)
					)
				);
				?>
			<?php endif; ?>
		</div>

		<div class="zl-field">
			<label class="zl-label" for="zl-dob"><?php echo esc_html__( 'Date of Birth', 'zeko-love' ); ?></label>
			<input type="date" id="zl-dob" name="dob" class="zl-input" value="<?php echo isset( $profile['dob'] ) ? esc_attr( $profile['dob'] ) : ''; ?>" />
		</div>

		<div class="zl-field">
			<label class="zl-label" for="zl-gender"><?php echo esc_html__( 'Gender', 'zeko-love' ); ?></label>
			<select id="zl-gender" name="gender" class="zl-input">
				<option value=""><?php echo esc_html__( 'Select...', 'zeko-love' ); ?></option>
				<?php foreach ( $gender_options as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( isset( $profile['gender'] ) && $profile['gender'] === $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<div class="zl-field">
			<label class="zl-label" for="zl-interested-in"><?php echo esc_html__( 'Interested In', 'zeko-love' ); ?></label>
			<select id="zl-interested-in" name="interested_in" class="zl-input">
				<option value=""><?php echo esc_html__( 'Select...', 'zeko-love' ); ?></option>
				<?php foreach ( $interested_options as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( isset( $profile['interested_in'] ) && $profile['interested_in'] === $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>

	<!-- Appearance / Lifestyle -->
	<div class="zl-card">
		<h2 class="zl-card-title"><?php echo esc_html__( 'Appearance & Lifestyle', 'zeko-love' ); ?></h2>

		<div class="zl-field">
			<label class="zl-label" for="zl-height"><?php echo esc_html__( 'Height (cm)', 'zeko-love' ); ?></label>
			<input type="number" id="zl-height" name="height_cm" class="zl-input" min="100" max="250" value="<?php echo isset( $profile['height_cm'] ) ? esc_attr( $profile['height_cm'] ) : ''; ?>" />
		</div>

		<div class="zl-field">
			<label class="zl-label" for="zl-occupation"><?php echo esc_html__( 'Occupation', 'zeko-love' ); ?></label>
			<input type="text" id="zl-occupation" name="occupation" class="zl-input" value="<?php echo isset( $profile['occupation'] ) ? esc_attr( $profile['occupation'] ) : ''; ?>" />
		</div>

		<div class="zl-field">
			<label class="zl-label" for="zl-education"><?php echo esc_html__( 'Education', 'zeko-love' ); ?></label>
			<input type="text" id="zl-education" name="education" class="zl-input" value="<?php echo isset( $profile['education'] ) ? esc_attr( $profile['education'] ) : ''; ?>" />
		</div>

		<div class="zl-field">
			<label class="zl-label" for="zl-smoking"><?php echo esc_html__( 'Smoking', 'zeko-love' ); ?></label>
			<select id="zl-smoking" name="smoking" class="zl-input">
				<?php foreach ( $smoking_options as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( isset( $profile['smoking'] ) && $profile['smoking'] === $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<div class="zl-field">
			<label class="zl-label" for="zl-drinking"><?php echo esc_html__( 'Drinking', 'zeko-love' ); ?></label>
			<select id="zl-drinking" name="drinking" class="zl-input">
				<?php foreach ( $drinking_options as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( isset( $profile['drinking'] ) && $profile['drinking'] === $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<div class="zl-field">
			<label class="zl-label" for="zl-has-children"><?php echo esc_html__( 'Has Children', 'zeko-love' ); ?></label>
			<select id="zl-has-children" name="has_children" class="zl-input">
				<?php foreach ( $prefer_options as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( isset( $profile['has_children'] ) && (string) $profile['has_children'] === $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<div class="zl-field">
			<label class="zl-label" for="zl-wants-children"><?php echo esc_html__( 'Wants Children', 'zeko-love' ); ?></label>
			<select id="zl-wants-children" name="wants_children" class="zl-input">
				<?php foreach ( $yes_no_undecided as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( isset( $profile['wants_children'] ) && (string) $profile['wants_children'] === $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<div class="zl-field">
			<label class="zl-label" for="zl-religion"><?php echo esc_html__( 'Religion', 'zeko-love' ); ?></label>
			<input type="text" id="zl-religion" name="religion" class="zl-input" value="<?php echo isset( $profile['religion'] ) ? esc_attr( $profile['religion'] ) : ''; ?>" />
		</div>

		<div class="zl-field">
			<label class="zl-label" for="zl-ethnicity"><?php echo esc_html__( 'Ethnicity', 'zeko-love' ); ?></label>
			<input type="text" id="zl-ethnicity" name="ethnicity" class="zl-input" value="<?php echo isset( $profile['ethnicity'] ) ? esc_attr( $profile['ethnicity'] ) : ''; ?>" />
		</div>
	</div>

	<!-- Preferences -->
	<div class="zl-card">
		<h2 class="zl-card-title"><?php echo esc_html__( 'Preferences', 'zeko-love' ); ?></h2>

		<div class="zl-field">
			<label class="zl-label" for="zl-relationship-goal"><?php echo esc_html__( 'Relationship Goal', 'zeko-love' ); ?></label>
			<select id="zl-relationship-goal" name="relationship_goal" class="zl-input">
				<?php foreach ( $goal_options as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( isset( $profile['relationship_goal'] ) && $profile['relationship_goal'] === $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>

	<!-- Interests -->
	<div class="zl-card">
		<h2 class="zl-card-title"><?php echo esc_html__( 'Interests', 'zeko-love' ); ?></h2>

		<div class="zl-interests-tags">
			<?php foreach ( $interest_categories as $value => $label ) : ?>
				<label class="zl-tag <?php echo in_array( $value, $interest_values, true ) ? 'zl-tag-active' : ''; ?>">
					<input
						type="checkbox"
						name="interests[]"
						value="<?php echo esc_attr( $value ); ?>"
						<?php checked( in_array( $value, $interest_values, true ) ); ?>
						style="display:none;"
					/>
					<?php echo esc_html( $label ); ?>
				</label>
			<?php endforeach; ?>
		</div>
	</div>

	<!-- Photos -->
	<div class="zl-card">
		<h2 class="zl-card-title"><?php echo esc_html__( 'Photos', 'zeko-love' ); ?></h2>

		<div class="zl-photo-upload">
			<div class="zl-upload-area">
				<p><?php echo esc_html__( 'Drag & drop photos here or click to browse', 'zeko-love' ); ?></p>
				<input type="file" name="photos[]" accept="image/*" multiple style="display:none;" />
			</div>

			<div class="zl-photo-grid">
				<?php if ( ! empty( $photos ) ) : ?>
					<?php foreach ( $photos as $photo ) : ?>
						<div class="zl-photo-item">
							<img src="<?php echo esc_url( $photo['photo_url'] ); ?>" alt="" />
							<div class="zl-photo-overlay">
								<button
									type="button"
									class="zl-btn zl-btn-sm zl-set-primary"
									data-photo-id="<?php echo esc_attr( $photo['photo_id'] ); ?>"
								><?php echo esc_html__( 'Primary', 'zeko-love' ); ?></button>
								<button
									type="button"
									class="zl-btn zl-btn-sm zl-btn-danger zl-delete-photo"
									data-photo-id="<?php echo esc_attr( $photo['photo_id'] ); ?>"
								><?php echo esc_html__( 'Delete', 'zeko-love' ); ?></button>
							</div>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<div style="margin-top:24px;">
		<button type="submit" class="zl-btn zl-btn-primary zl-save-profile">
			<?php echo esc_html__( 'Save Profile', 'zeko-love' ); ?>
		</button>
	</div>
</form>
