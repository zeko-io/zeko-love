<?php
/**
 * Admin handler for Zeko Love.
 *
 * Admin pages, photo verification moderation, settings.
 *
 * @package Zeko_Love
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Class Zeko_Love_Admin. */
class Zeko_Love_Admin {

	/**
	 * Db.
	 *
	 * @var Zeko_Love_DB Db.
	 */
	private Zeko_Love_DB $db;

	/**
	 * Construct.
	 *
	 * @param Zeko_Love_DB $db Db.
	 */
	public function __construct( Zeko_Love_DB $db ) {
		$this->db = $db;
		$this->init_hooks();
	}

	/**
	 * Init hooks.
	 */
	public function init_hooks(): void {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'wp_ajax_zeko_love_admin_verify_photo', array( $this, 'handle_verify_photo' ) );
		add_action( 'wp_ajax_zeko_love_admin_flag_photo', array( $this, 'handle_flag_photo' ) );
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Hook.
	 */
	public function enqueue_admin_assets( string $hook ): void {
		if ( false === strpos( $hook, 'zeko-love' ) ) {
			return;
		}
		wp_add_inline_style(
			'wp-admin',
			'
			.zeko-love-stats-grid { display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin:20px 0; }
			.zeko-love-stat-card { background:#fff;border:1px solid #e2e8f0;padding:20px;border-radius:8px;text-align:center; }
			.zeko-love-stat-card h3 { margin:0;color:#e11d48; }
			.zeko-love-stat-card p { margin:4px 0 0;color:#666; }
			.zeko-love-photo-thumb { width:60px;height:60px;object-fit:cover;border-radius:4px; }
			.zeko-love-actions .button { margin-right:4px; }
			.zeko-love-report-count { display:inline-block;min-width:24px;padding:2px 8px;border-radius:10px;text-align:center;font-weight:600;font-size:12px; }
			.zeko-love-report-count.count-low { background:#ecfdf5;color:#059669; }
			.zeko-love-report-count.count-medium { background:#fef3c7;color:#b45309; }
			.zeko-love-report-count.count-high { background:#fee2e2;color:#b91c1c; }
		'
		);
	}

	/**
	 * Add admin menu.
	 */
	public function add_admin_menu(): void {
		add_menu_page(
			__( 'Zeko Love', 'zeko-love' ),
			__( 'Zeko Love', 'zeko-love' ),
			'manage_options',
			'zeko-love',
			array( $this, 'render_dashboard_page' ),
			'dashicons-heart',
			31
		);

		add_submenu_page(
			'zeko-love',
			__( 'Dashboard', 'zeko-love' ),
			__( 'Dashboard', 'zeko-love' ),
			'manage_options',
			'zeko-love',
			array( $this, 'render_dashboard_page' )
		);

		add_submenu_page(
			'zeko-love',
			__( 'Profiles', 'zeko-love' ),
			__( 'Profiles', 'zeko-love' ),
			'manage_options',
			'zeko-love-profiles',
			array( $this, 'render_profiles_page' )
		);

		add_submenu_page(
			'zeko-love',
			__( 'Photos', 'zeko-love' ),
			__( 'Photos', 'zeko-love' ),
			'manage_options',
			'zeko-love-photos',
			array( $this, 'render_photos_page' )
		);

		add_submenu_page(
			'zeko-love',
			__( 'Reports', 'zeko-love' ),
			__( 'Reports', 'zeko-love' ),
			'manage_options',
			'zeko-love-reports',
			array( $this, 'render_reports_page' )
		);

		add_submenu_page(
			'zeko-love',
			__( 'Settings', 'zeko-love' ),
			__( 'Settings', 'zeko-love' ),
			'manage_options',
			'zeko-love-settings',
			array( $this, 'render_settings_page' )
		);

		add_submenu_page(
			'zeko-love',
			__( 'Calls & Availability', 'zeko-love' ),
			__( 'Calls', 'zeko-love' ),
			'manage_options',
			'zeko-love-calls',
			array( $this, 'render_calls_page' )
		);
	}

	/**
	 * Table profiles.
	 */
	private function get_table_profiles(): string {
		return $this->db->get_table_profiles();
	}

	/**
	 * Table photos.
	 */
	private function get_table_photos(): string {
		return $this->db->get_table_photos();
	}

	/**
	 * Table matches.
	 */
	private function get_table_matches(): string {
		return $this->db->get_table_matches();
	}

	/**
	 * Table interactions.
	 */
	private function get_table_interactions(): string {
		return $this->db->get_table_interactions();
	}

	/**
	 * Render dashboard page.
	 */
	public function render_dashboard_page(): void {
		global $wpdb;

		$profiles_table = $this->get_table_profiles();
		$photos_table   = $this->get_table_photos();
		$matches_table  = $this->get_table_matches();
		$interactions   = $this->get_table_interactions();

		$total_profiles    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$profiles_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$active_today      = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$profiles_table} WHERE DATE(updated_at) = CURDATE() AND is_active = %d", 1 ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$matches_this_week = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$matches_table} WHERE YEARWEEK(matched_at, 1) = YEARWEEK(CURDATE(), 1)" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$pending_photos    = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$photos_table} WHERE verification_status = %s", 'pending' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$verified_profiles = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$profiles_table} WHERE is_verified = %d", 1 ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$total_reports     = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$interactions} WHERE type = %s", 'report' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Zeko Love — Dashboard', 'zeko-love' ); ?></h1>

			<div class="zeko-love-stats-grid">
				<div class="zeko-love-stat-card">
					<h3><?php echo esc_html( $total_profiles ); ?></h3>
					<p><?php esc_html_e( 'Total Profiles', 'zeko-love' ); ?></p>
				</div>
				<div class="zeko-love-stat-card">
					<h3><?php echo esc_html( $active_today ); ?></h3>
					<p><?php esc_html_e( 'Active Today', 'zeko-love' ); ?></p>
				</div>
				<div class="zeko-love-stat-card">
					<h3><?php echo esc_html( $matches_this_week ); ?></h3>
					<p><?php esc_html_e( 'Matches This Week', 'zeko-love' ); ?></p>
				</div>
				<div class="zeko-love-stat-card">
					<h3><?php echo esc_html( $pending_photos ); ?></h3>
					<p><?php esc_html_e( 'Pending Photo Verifications', 'zeko-love' ); ?></p>
				</div>
				<div class="zeko-love-stat-card">
					<h3><?php echo esc_html( $verified_profiles ); ?></h3>
					<p><?php esc_html_e( 'Verified Profiles', 'zeko-love' ); ?></p>
				</div>
				<div class="zeko-love-stat-card">
					<h3><?php echo esc_html( $total_reports ); ?></h3>
					<p><?php esc_html_e( 'Total Reports', 'zeko-love' ); ?></p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render profiles page.
	 */
	public function render_profiles_page(): void {
		global $wpdb;

		$profiles_table = $this->get_table_profiles();
		$photos_table   = $this->get_table_photos();

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$profiles = $wpdb->get_results(
			"SELECT p.*, u.display_name AS user_display_name, u.user_email,
				( SELECT COUNT(*) FROM {$photos_table} WHERE user_id = p.user_id ) AS photo_count
			FROM {$profiles_table} p
			LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
			ORDER BY p.updated_at DESC
			LIMIT 100",
			ARRAY_A
		); // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		?>
		<?php
		$extra_header = (string) apply_filters( 'zeko_love_profiles_extra_header', '' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Zeko Love — Profiles', 'zeko-love' ); ?></h1>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Display Name', 'zeko-love' ); ?></th>
						<th><?php esc_html_e( 'Email', 'zeko-love' ); ?></th>
						<th><?php esc_html_e( 'Gender', 'zeko-love' ); ?></th>
						<th><?php esc_html_e( 'Verified', 'zeko-love' ); ?></th>
						<th><?php esc_html_e( 'Photos', 'zeko-love' ); ?></th>
						<th><?php esc_html_e( 'Last Active', 'zeko-love' ); ?></th>
						<?php echo $extra_header; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Column header from the feature filter is escaped by its owner (empty by default). ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $profiles as $profile ) : ?>
						<tr>
							<td><?php echo esc_html( $profile['user_display_name'] ); ?></td>
							<td><?php echo esc_html( $profile['user_email'] ); ?></td>
							<td><?php echo esc_html( $profile['gender'] ); ?></td>
							<td><?php echo $profile['is_verified'] ? esc_html__( 'Yes', 'zeko-love' ) : esc_html__( 'No', 'zeko-love' ); ?></td>
							<td><?php echo esc_html( $profile['photo_count'] ); ?></td>
							<td><?php echo esc_html( $profile['updated_at'] ); ?></td>
							<?php echo apply_filters( 'zeko_love_profiles_extra_cells', '', $profile ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Cell markup from the feature filter is escaped by its owner (empty by default). ?>
						</tr>
					<?php endforeach; ?>
					<?php if ( empty( $profiles ) ) : ?>
						<tr><td colspan="<?php echo esc_attr( 6 + ( $extra_header ? 1 : 0 ) ); ?>"><?php esc_html_e( 'No profiles found.', 'zeko-love' ); ?></td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render photos page.
	 */
	public function render_photos_page(): void {
		global $wpdb;

		$photos_table   = $this->get_table_photos();
		$profiles_table = $this->get_table_profiles();

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$photos = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ph.*, u.display_name AS user_display_name
				FROM {$photos_table} ph
				LEFT JOIN {$wpdb->users} u ON ph.user_id = u.ID
				WHERE ph.verification_status = %s
				ORDER BY ph.created_at ASC
				LIMIT 100",
				'pending'
			),
			ARRAY_A
		); // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Zeko Love — Pending Photo Verification', 'zeko-love' ); ?></h1>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Photo', 'zeko-love' ); ?></th>
						<th><?php esc_html_e( 'User', 'zeko-love' ); ?></th>
						<th><?php esc_html_e( 'Status', 'zeko-love' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'zeko-love' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $photos as $photo ) : ?>
						<tr data-photo-id="<?php echo esc_attr( $photo['photo_id'] ); ?>">
							<td><img src="<?php echo esc_url( $photo['photo_url'] ); ?>" alt="" class="zeko-love-photo-thumb"></td>
							<td><?php echo esc_html( $photo['user_display_name'] ); ?></td>
							<td class="photo-status"><?php echo esc_html( $photo['verification_status'] ); ?></td>
							<td class="zeko-love-actions">
								<button class="button button-primary zeko-verify-photo" data-id="<?php echo esc_attr( $photo['photo_id'] ); ?>">
									<?php esc_html_e( 'Verify', 'zeko-love' ); ?>
								</button>
								<button class="button zeko-flag-photo" data-id="<?php echo esc_attr( $photo['photo_id'] ); ?>">
									<?php esc_html_e( 'Flag', 'zeko-love' ); ?>
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
					<?php if ( empty( $photos ) ) : ?>
						<tr><td colspan="4"><?php esc_html_e( 'No pending photos.', 'zeko-love' ); ?></td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

		<script type="text/javascript">
		jQuery( function( $ ) {
			$( '.zeko-verify-photo' ).on( 'click', function() {
				var btn  = $( this );
				var id   = btn.data( 'id' );
				var row  = btn.closest( 'tr' );
				$.post( ajaxurl, {
					action: 'zeko_love_admin_verify_photo',
					photo_id: id,
					_ajax_nonce: '<?php echo esc_attr( wp_create_nonce( 'zeko_love_photo_verify' ) ); ?>'
				}, function( resp ) {
					if ( resp.success ) {
row.find( '.photo-status' ).text( '<?php echo esc_js( __( 'verified', 'zeko-love' ) ); ?>' );
					row.find( '.zeko-love-actions' ).html( '<span style="color:#10b981;"><?php echo esc_js( __( 'Verified', 'zeko-love' ) ); ?></span>' );
					} else {
						alert( resp.data || '<?php echo esc_js( __( 'Error', 'zeko-love' ) ); ?>' );
					}
				} );
			} );

			$( '.zeko-flag-photo' ).on( 'click', function() {
				var btn  = $( this );
				var id   = btn.data( 'id' );
				var row  = btn.closest( 'tr' );
				var note = prompt( '<?php echo esc_js( __( 'Reason for flagging:', 'zeko-love' ) ); ?>' );
				if ( null === note ) return;
				$.post( ajaxurl, {
					action: 'zeko_love_admin_flag_photo',
					photo_id: id,
					admin_note: note,
					_ajax_nonce: '<?php echo esc_attr( wp_create_nonce( 'zeko_love_photo_flag' ) ); ?>'
				}, function( resp ) {
					if ( resp.success ) {
row.find( '.photo-status' ).text( '<?php echo esc_js( __( 'rejected', 'zeko-love' ) ); ?>' );
					row.find( '.zeko-love-actions' ).html( '<span style="color:#e11d48;"><?php echo esc_js( __( 'Flagged', 'zeko-love' ) ); ?></span>' );
					} else {
alert( resp.data || '<?php echo esc_js( __( 'Error', 'zeko-love' ) ); ?>' );
				}
			} );
		} );
	} );
		</script>
		<?php
	}

	/**
	 * Render reports page.
	 */
	public function render_reports_page(): void {
		global $wpdb;

		$interactions   = $this->get_table_interactions();
		$profiles_table = $this->get_table_profiles();

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$reports = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT i.*, u1.display_name AS reporter_name, u2.display_name AS reported_name
				FROM {$interactions} i
				LEFT JOIN {$wpdb->users} u1 ON i.user_id = u1.ID
				LEFT JOIN {$wpdb->users} u2 ON i.target_id = u2.ID
				WHERE i.type = %s
				ORDER BY i.created_at DESC
				LIMIT 100",
				'report'
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

		$report_counts = $this->db->get_report_counts();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Zeko Love — Reported Users', 'zeko-love' ); ?></h1>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Reporter', 'zeko-love' ); ?></th>
						<th><?php esc_html_e( 'Reported User', 'zeko-love' ); ?></th>
						<th><?php esc_html_e( 'Total Reports', 'zeko-love' ); ?></th>
						<th><?php esc_html_e( 'Reason', 'zeko-love' ); ?></th>
						<th><?php esc_html_e( 'Date', 'zeko-love' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'zeko-love' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $reports as $report ) : ?>
						<?php
						$target_id    = (int) $report['target_id'];
						$report_count = $report_counts[ $target_id ] ?? 0;
						$badge        = 'count-' . ( $report_count >= 3 ? 'high' : ( $report_count >= 2 ? 'medium' : 'low' ) );
						?>
						<tr>
							<td><?php echo esc_html( $report['reporter_name'] ); ?></td>
							<td><?php echo esc_html( $report['reported_name'] ); ?></td>
							<td>
								<span class="zeko-love-report-count <?php echo esc_attr( $badge ); ?>"><?php echo esc_html( $report_count ); ?></span>
							</td>
							<td><?php echo esc_html( $report['report_reason'] ); ?></td>
							<td><?php echo esc_html( $report['created_at'] ); ?></td>
							<td class="zeko-love-actions">
								<button class="button" disabled><?php esc_html_e( 'Review', 'zeko-love' ); ?></button>
							</td>
						</tr>
					<?php endforeach; ?>
						<?php if ( empty( $reports ) ) : ?>
						<tr><td colspan="6"><?php esc_html_e( 'No reports found.', 'zeko-love' ); ?></td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
			<?php
	}

	/**
	 * Render settings page.
	 */
	public function render_settings_page(): void {
		if ( isset( $_POST['submit'] ) && check_admin_referer( 'zeko_love_settings' ) ) {
			$settings = array(
				'enable_public_api'    => ! empty( $_POST['zeko_love_enable_public_api'] ) ? 1 : 0,
				'default_radius_km'    => isset( $_POST['zeko_love_default_radius_km'] ) ? absint( $_POST['zeko_love_default_radius_km'] ) : 50,
				'super_like_price'     => isset( $_POST['zeko_love_super_like_price'] ) ? round( max( 0, (float) $_POST['zeko_love_super_like_price'] ), 2 ) : 5.00,
				'boost_price'          => isset( $_POST['zeko_love_boost_price'] ) ? round( max( 0, (float) $_POST['zeko_love_boost_price'] ), 2 ) : 10.00,
				'boost_duration_hours' => isset( $_POST['zeko_love_boost_duration_hours'] ) ? min( 720, max( 1, absint( $_POST['zeko_love_boost_duration_hours'] ) ) ) : 24,
			);
			update_option( 'zeko_love_settings', $settings );
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved.', 'zeko-love' ) . '</p></div>';
		}

		$settings = get_option(
			'zeko_love_settings',
			array(
				'enable_public_api'    => 0,
				'default_radius_km'    => 50,
				'super_like_price'     => 5.00,
				'boost_price'          => 10.00,
				'boost_duration_hours' => 24,
			)
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Zeko Love — Settings', 'zeko-love' ); ?></h1>
			<form method="post" action="">
				<?php wp_nonce_field( 'zeko_love_settings' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable Public API', 'zeko-love' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="zeko_love_enable_public_api" value="1" <?php checked( $settings['enable_public_api'], 1 ); ?>>
								<?php esc_html_e( 'Allow public REST API access to dating profiles', 'zeko-love' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Default Distance Radius (km)', 'zeko-love' ); ?></th>
						<td>
							<input type="number" name="zeko_love_default_radius_km" value="<?php echo esc_attr( $settings['default_radius_km'] ); ?>" min="1" max="500" class="small-text">
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Super Like Price', 'zeko-love' ); ?></th>
						<td>
							<input type="number" name="zeko_love_super_like_price" value="<?php echo esc_attr( $settings['super_like_price'] ); ?>" min="0" step="0.01" class="small-text">
							<p class="description"><?php esc_html_e( 'Wallet amount charged for a Super Like. Charges Zeko Pay wallets.', 'zeko-love' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Boost Price', 'zeko-love' ); ?></th>
						<td>
							<input type="number" name="zeko_love_boost_price" value="<?php echo esc_attr( $settings['boost_price'] ); ?>" min="0" step="0.01" class="small-text">
							<p class="description"><?php esc_html_e( 'Wallet amount charged to boost a profile.', 'zeko-love' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Boost Duration (hours)', 'zeko-love' ); ?></th>
						<td>
							<input type="number" name="zeko_love_boost_duration_hours" value="<?php echo esc_attr( $settings['boost_duration_hours'] ); ?>" min="1" max="720" class="small-text">
							<p class="description"><?php esc_html_e( 'How long a boosted profile appears at the top of search results.', 'zeko-love' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render calls page.
	 */
	public function render_calls_page(): void {
		global $wpdb;

		if ( isset( $_POST['submit_settings'] ) && check_admin_referer( 'zeko_love_calls_settings' ) ) {
			$settings = array(
				'commission_pct'    => isset( $_POST['zeko_love_calls_commission'] ) ? min( 90.0, max( 0.0, (float) $_POST['zeko_love_calls_commission'] ) ) : 20.0,
				'free_cancel_hours' => isset( $_POST['zeko_love_calls_free_cancel_hours'] ) ? max( 0, absint( $_POST['zeko_love_calls_free_cancel_hours'] ) ) : 24,
				'booking_window'    => isset( $_POST['zeko_love_calls_booking_window'] ) ? max( 1, absint( $_POST['zeko_love_calls_booking_window'] ) ) : 30,
				'currency'          => sanitize_text_field( wp_unslash( $_POST['zeko_love_calls_currency'] ?? 'USD' ) ),
			);
			update_option( 'zeko_love_calls_settings', $settings );
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Calls settings saved.', 'zeko-love' ) . '</p></div>';
		}

		if ( isset( $_POST['save_plan'] ) && check_admin_referer( 'zeko_love_calls_settings' ) ) {
			$this->db->save_call_plan(
				array(
					'name'              => sanitize_text_field( wp_unslash( $_POST['plan_name'] ?? '' ) ),
					'price'             => (float) sanitize_text_field( wp_unslash( $_POST['plan_price'] ?? 0 ) ),
					'duration_days'     => absint( wp_unslash( $_POST['plan_duration_days'] ?? 30 ) ),
					'max_usage_minutes' => absint( wp_unslash( $_POST['plan_max_usage_minutes'] ?? 0 ) ),
					'max_listings'      => absint( wp_unslash( $_POST['plan_max_listings'] ?? 1 ) ),
					'features'          => sanitize_textarea_field( wp_unslash( $_POST['plan_features'] ?? '' ) ),
					'is_active'         => ! empty( $_POST['plan_is_active'] ),
				),
				isset( $_POST['plan_id'] ) ? absint( $_POST['plan_id'] ) : 0
			);
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Plan saved.', 'zeko-love' ) . '</p></div>';
		}

		if ( isset( $_GET['delete_plan'] ) && check_admin_referer( 'zeko_love_calls_settings' ) ) {
			$this->db->delete_call_plan( absint( $_GET['delete_plan'] ) );
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Plan deleted.', 'zeko-love' ) . '</p></div>';
		}

		$config  = zeko_love_calls_config();
		$plans   = $this->db->get_call_plans();
		$editing = isset( $_GET['edit_plan'] ) ? $this->db->get_call_plan( absint( $_GET['edit_plan'] ) ) : null;

		$table_listings = $this->db->get_table_call_listings();
		$table_bookings = $this->db->get_table_call_bookings();

		$stat_total    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_listings}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$stat_active   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table_listings} WHERE status = %s", 'active' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$stat_bookings = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_bookings}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$stat_revenue  = (float) $wpdb->get_var( "SELECT COALESCE(SUM(amount),0) FROM {$table_bookings} WHERE status IN ('completed','confirmed','pending')" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Zeko Love — Calls & Availability', 'zeko-love' ); ?></h1>

			<div class="zeko-love-stats-grid">
				<div class="zeko-love-stat-card">
					<h3><?php echo esc_html( $stat_total ); ?></h3>
					<p><?php esc_html_e( 'Total Listings', 'zeko-love' ); ?></p>
				</div>
				<div class="zeko-love-stat-card">
					<h3><?php echo esc_html( $stat_active ); ?></h3>
					<p><?php esc_html_e( 'Active Listings', 'zeko-love' ); ?></p>
				</div>
				<div class="zeko-love-stat-card">
					<h3><?php echo esc_html( $stat_bookings ); ?></h3>
					<p><?php esc_html_e( 'Bookings', 'zeko-love' ); ?></p>
				</div>
				<div class="zeko-love-stat-card">
					<h3><?php echo esc_html( zeko_love_format_money( $stat_revenue, $config['currency'] ) ); ?></h3>
					<p><?php esc_html_e( 'Booked Value', 'zeko-love' ); ?></p>
				</div>
			</div>

			<h2 style="margin-top:24px;"><?php esc_html_e( 'Settings', 'zeko-love' ); ?></h2>
			<form method="post" action="">
				<?php wp_nonce_field( 'zeko_love_calls_settings' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Platform Commission (%)', 'zeko-love' ); ?></th>
						<td>
							<input type="number" name="zeko_love_calls_commission" value="<?php echo esc_attr( $config['commission_pct'] ); ?>" min="0" max="90" step="0.5" class="small-text">
							<p class="description"><?php esc_html_e( 'Share of each booking kept by the platform. Sellers receive the remainder.', 'zeko-love' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Free Cancel Window (hours)', 'zeko-love' ); ?></th>
						<td>
							<input type="number" name="zeko_love_calls_free_cancel_hours" value="<?php echo esc_attr( $config['free_cancel_hours'] ); ?>" min="0" class="small-text">
							<p class="description"><?php esc_html_e( 'Buyers can cancel without penalty up to this many hours before the call.', 'zeko-love' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Booking Window (days)', 'zeko-love' ); ?></th>
						<td>
							<input type="number" name="zeko_love_calls_booking_window" value="<?php echo esc_attr( $config['booking_window'] ); ?>" min="1" max="365" class="small-text">
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Currency', 'zeko-love' ); ?></th>
						<td>
							<input type="text" name="zeko_love_calls_currency" value="<?php echo esc_attr( $config['currency'] ); ?>" class="small-text" maxlength="3">
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save settings', 'zeko-love' ), 'primary', 'submit_settings' ); ?>
			</form>

			<h2 style="margin-top:24px;"><?php esc_html_e( 'Subscription Plans', 'zeko-love' ); ?></h2>

			<table class="widefat striped" style="max-width:900px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Name', 'zeko-love' ); ?></th>
						<th><?php esc_html_e( 'Price', 'zeko-love' ); ?></th>
						<th><?php esc_html_e( 'Days', 'zeko-love' ); ?></th>
						<th><?php esc_html_e( 'Max Usage (min)', 'zeko-love' ); ?></th>
						<th><?php esc_html_e( 'Status', 'zeko-love' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'zeko-love' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $plans as $plan ) : ?>
						<tr>
							<td><?php echo esc_html( $plan['name'] ); ?></td>
							<td><?php echo esc_html( zeko_love_format_money( (float) $plan['price'], $config['currency'] ) ); ?></td>
							<td><?php echo esc_html( $plan['duration_days'] ); ?></td>
							<td><?php echo esc_html( $plan['max_usage_minutes'] ); ?></td>
							<td><?php echo $plan['is_active'] ? '<strong style="color:#059669;">' . esc_html__( 'Active', 'zeko-love' ) . '</strong>' : '<span style="color:#6b7280;">' . esc_html__( 'Inactive', 'zeko-love' ) . '</span>'; ?></td>
							<td>
								<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'edit_plan', $plan['plan_id'] ), 'zeko_love_calls_settings' ) ); ?>"><?php esc_html_e( 'Edit', 'zeko-love' ); ?></a>
								&nbsp;|&nbsp;
								<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'delete_plan', $plan['plan_id'] ), 'zeko_love_calls_settings' ) ); ?>" onclick="return confirm('<?php esc_attr_e( 'Delete this plan?', 'zeko-love' ); ?>');"><?php esc_html_e( 'Delete', 'zeko-love' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<h3 style="margin-top:24px;"><?php echo $editing ? esc_html__( 'Edit plan', 'zeko-love' ) : esc_html__( 'Add plan', 'zeko-love' ); ?></h3>
			<form method="post" action="">
				<?php wp_nonce_field( 'zeko_love_calls_settings' ); ?>
				<?php if ( $editing ) : ?>
					<input type="hidden" name="plan_id" value="<?php echo esc_attr( $editing['plan_id'] ); ?>">
				<?php endif; ?>
				<table class="form-table" style="max-width:700px;">
					<tr>
						<th scope="row"><?php esc_html_e( 'Name', 'zeko-love' ); ?></th>
						<td><input type="text" name="plan_name" value="<?php echo esc_attr( $editing['name'] ?? '' ); ?>" class="regular-text" required></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Price', 'zeko-love' ); ?></th>
						<td><input type="number" name="plan_price" value="<?php echo esc_attr( $editing['price'] ?? '9.99' ); ?>" min="0" step="0.01" class="small-text"></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Duration (days)', 'zeko-love' ); ?></th>
						<td><input type="number" name="plan_duration_days" value="<?php echo esc_attr( $editing['duration_days'] ?? 30 ); ?>" min="1" class="small-text"></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Max usage (minutes)', 'zeko-love' ); ?></th>
						<td>
							<input type="number" name="plan_max_usage_minutes" value="<?php echo esc_attr( $editing['max_usage_minutes'] ?? 0 ); ?>" min="0" class="small-text">
							<p class="description"><?php esc_html_e( '0 = unlimited.', 'zeko-love' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Max listings', 'zeko-love' ); ?></th>
						<td><input type="number" name="plan_max_listings" value="<?php echo esc_attr( $editing['max_listings'] ?? 1 ); ?>" min="1" class="small-text"></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Features (one per line)', 'zeko-love' ); ?></th>
						<td><textarea name="plan_features" rows="3" class="large-text"><?php echo esc_textarea( $editing['features'] ?? '' ); ?></textarea></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Active', 'zeko-love' ); ?></th>
						<td><label><input type="checkbox" name="plan_is_active" value="1" <?php checked( $editing['is_active'] ?? true ); ?>></label></td>
					</tr>
				</table>
				<?php submit_button( $editing ? __( 'Update plan', 'zeko-love' ) : __( 'Add plan', 'zeko-love' ), 'primary', 'save_plan' ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Handle verify photo.
	 */
	public function handle_verify_photo(): void {
		check_ajax_referer( 'zeko_love_photo_verify' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		$photo_id = isset( $_POST['photo_id'] ) ? absint( $_POST['photo_id'] ) : 0;
		if ( ! $photo_id ) {
			wp_send_json_error( __( 'Invalid photo ID.', 'zeko-love' ) );
		}

		global $wpdb;
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$photo_user = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT user_id FROM {$this->get_table_photos()} WHERE photo_id = %d", $photo_id )
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

		$updated = $wpdb->update(
			$this->get_table_photos(),
			array( 'verification_status' => 'verified' ),
			array( 'photo_id' => $photo_id ),
			array( '%s' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			wp_send_json_error( __( 'Database error.', 'zeko-love' ) );
		}

		if ( $photo_user > 0 && class_exists( 'Zeko_Love_Pay' ) && Zeko_Love_Pay::active() ) {
			$config = zeko_love_get_earn_config();
			if ( ! empty( $config['photo_verified'] ) ) {
				Zeko_Love_Pay::credit(
					$photo_user,
					(float) $config['photo_verified']['amount'],
					'photo_verified',
					(string) $config['photo_verified']['label']
				);
			}
		}

		wp_send_json_success( __( 'Photo verified.', 'zeko-love' ) );
	}

	/**
	 * Handle flag photo.
	 */
	public function handle_flag_photo(): void {
		check_ajax_referer( 'zeko_love_photo_flag' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		$photo_id   = isset( $_POST['photo_id'] ) ? absint( $_POST['photo_id'] ) : 0;
		$admin_note = isset( $_POST['admin_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['admin_note'] ) ) : '';

		if ( ! $photo_id ) {
			wp_send_json_error( __( 'Invalid photo ID.', 'zeko-love' ) );
		}

		global $wpdb;
		$updated = $wpdb->update(
			$this->get_table_photos(),
			array(
				'verification_status' => 'rejected',
				'admin_note'          => $admin_note,
			),
			array( 'photo_id' => $photo_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			wp_send_json_error( __( 'Database error.', 'zeko-love' ) );
		}

		wp_send_json_success( __( 'Photo flagged.', 'zeko-love' ) );
	}
}
