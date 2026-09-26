<?php
/**
 * File doc comment.
 *
 * @package Zeko_ZEKO_LOVE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Class Zeko_Love_Ecosystem. */
class Zeko_Love_Ecosystem {

	/**
	 * MENU VERSION.
	 *
	 * @var mixed
	 */
	private const MENU_VERSION = '1.4.0';

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
	}

	/**
	 * Init hooks.
	 */
	public function init_hooks(): void {
		add_action( 'init', array( $this, 'register_nav_menus' ) );
		add_action( 'admin_init', array( $this, 'maybe_create_nav_menu_items' ) );
		add_action( 'wp_before_admin_bar_render', array( $this, 'add_admin_bar_dating_node' ) );
		add_action( 'admin_bar_menu', array( $this, 'add_notification_bell' ), 999 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_notification_assets' ) );

		// Theme integration.
		add_action( 'zeko_header_top_bar', array( $this, 'render_header_dating_badge' ) );
		add_action( 'zeko_profile_view_sections', array( $this, 'render_profile_section' ) );
		add_action( 'zeko_theme_profile_stats', array( $this, 'render_profile_stats' ) );

		// Keep stored "Dating" menu URLs in sync with the current site scheme.
		add_filter( 'wp_nav_menu_objects', array( $this, 'fix_dating_menu_urls' ) );
	}

	/**
	 * Rewrite stored "Dating" menu item URLs at render time so they always
	 * match the current site scheme (self-heals http URLs stored before SSL).
	 *
	 * @return array
	 * @param array $items Menu item objects.
	 */
	public function fix_dating_menu_urls( $items ): array {
		$dating_slugs = array( 'dating-browse', 'dating-profile', 'dating-matches', 'dating-messages', 'dating-schedule', 'dating-settings', 'dating-calls' );

		foreach ( $items as $item ) {
			if ( ! is_object( $item ) || empty( $item->url ) || false === strpos( (string) $item->url, '/dating-' ) ) {
				continue;
			}
			$path = trim( (string) wp_parse_url( (string) $item->url, PHP_URL_PATH ), '/' );
			if ( in_array( $path, $dating_slugs, true ) ) {
				$item->url = zeko_love_page_url( $path );
			}
		}

		return $items;
	}

	/**
	 * Nav menus.
	 */
	public function register_nav_menus(): void {
		register_nav_menus(
			array(
				'zeko-love' => __( 'Zeko Love', 'zeko-love' ),
			)
		);
	}

	/**
	 * Append a "Dating" item with child pages to the primary nav.
	 * Follows the same pattern as Shop (zeko-shop) and Mentorship (zeko-mentor).
	 */
	public function maybe_create_nav_menu_items(): void {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return;
		}

		$done = get_option( 'zeko_love_menu_version', '' );
		if ( self::MENU_VERSION === $done ) {
			return;
		}

		$locations = get_nav_menu_locations();
		if ( empty( $locations['primary'] ) ) {
			return;
		}

		$menu_id = $locations['primary'];
		$items   = wp_get_nav_menu_items( $menu_id );
		if ( ! $items ) {
			return;
		}

		$love_parent_id = 0;
		$max_order      = 0;

		foreach ( $items as $item ) {
			$order = (int) $item->menu_order;
			if ( $order > $max_order ) {
				$max_order = $order;
			}
			if ( 'Dating' === $item->title ) {
				$love_parent_id = $item->ID;
			}
		}

		$children = array(
			'Browse'   => 'dating-browse',
			'Matches'  => 'dating-matches',
			'Messages' => 'dating-messages',
			'Dates'    => 'dating-schedule',
			'Calls'    => 'dating-calls',
			'Settings' => 'dating-settings',
		);

		if ( ! $love_parent_id ) {
			$parent_id = wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'  => 'Dating',
					'menu-item-url'    => zeko_love_page_url( 'dating-browse' ),
					'menu-item-status' => 'publish',
					'menu-item-type'   => 'custom',
					'menu-item-order'  => $max_order + 1,
				)
			);

			foreach ( $children as $title => $slug ) {
				wp_update_nav_menu_item(
					$menu_id,
					0,
					array(
						'menu-item-title'     => $title,
						'menu-item-url'       => zeko_love_page_url( $slug ),
						'menu-item-status'    => 'publish',
						'menu-item-type'      => 'custom',
						'menu-item-parent-id' => $parent_id,
					)
				);
			}
		} else {
			$existing_titles = array();
			foreach ( $items as $item ) {
				if ( (int) $item->menu_item_parent === $love_parent_id ) {
					$existing_titles[ $item->title ] = true;
				}
			}

			foreach ( $children as $title => $slug ) {
				if ( isset( $existing_titles[ $title ] ) ) {
					continue;
				}
				wp_update_nav_menu_item(
					$menu_id,
					0,
					array(
						'menu-item-title'     => $title,
						'menu-item-url'       => zeko_love_page_url( $slug ),
						'menu-item-status'    => 'publish',
						'menu-item-type'      => 'custom',
						'menu-item-parent-id' => $love_parent_id,
					)
				);
			}
		}

		update_option( 'zeko_love_menu_version', self::MENU_VERSION );
	}

	/**
	 * Render a Dating heart button with an unread-messages badge in the header.
	 */
	public function render_header_dating_badge(): void {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$user_id = get_current_user_id();
		$unread  = $this->db->count_unread_messages( $user_id );
		$unread += $this->db->count_unread_notifications( $user_id );
		?>
		<div class="zeko-header-dating">
			<a href="<?php echo esc_url( zeko_love_page_url( 'dating-messages' ) ); ?>"
				class="zeko-header-dating-btn"
				title="<?php echo esc_attr__( 'Dating messages', 'zeko-love' ); ?>">
				<span class="dashicons dashicons-heart"></span>
				<span><?php echo esc_html__( 'Dating', 'zeko-love' ); ?></span>
				<?php if ( $unread > 0 ) : ?>
					<span class="zeko-header-badge">
						<?php echo esc_html( min( 99, $unread ) ); ?>
					</span>
				<?php endif; ?>
			</a>
		</div>
		<?php
	}

	/**
	 * Render a dating card on a public user profile.
	 *
	 * @param int $user_id User id.
	 */
	public function render_profile_section( int $user_id ): void {
		$profile = $this->db->get_profile( $user_id );
		if ( ! $profile ) {
			return;
		}

		$photos  = $this->db->get_profile_photos( $user_id );
		$primary = null;
		foreach ( $photos as $photo ) {
			if ( ! empty( $photo['is_primary'] ) ) {
				$primary = $photo['photo_url'];
				break;
			}
		}
		if ( ! $primary && ! empty( $photos[0]['photo_url'] ) ) {
			$primary = $photos[0]['photo_url'];
		}
		if ( ! $primary ) {
			$primary = get_avatar_url( $user_id, array( 'size' => 96 ) );
		}

		$goals = array(
			'long_term'  => __( 'Long-term relationship', 'zeko-love' ),
			'short_term' => __( 'Something casual', 'zeko-love' ),
			'friendship' => __( 'Friendship', 'zeko-love' ),
			'not_sure'   => __( 'Not sure yet', 'zeko-love' ),
		);

		echo '<div class="zeko-love-profile-card" style="background:var(--color-surface,#fff);border:1px solid var(--color-border,#e2e8f0);border-radius:16px;padding:20px;margin:16px 0;display:flex;gap:16px;align-items:flex-start;">';
		echo '<img src="' . esc_url( $primary ) . '" alt="" style="width:96px;height:96px;border-radius:50%;object-fit:cover;flex-shrink:0;border:2px solid #e11d48;">';
		echo '<div style="flex:1;min-width:0;">';
		echo '<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">';
		echo '<h3 style="margin:0;font-size:18px;color:var(--color-text,#111827);">' . esc_html( $profile['display_name'] ) . '</h3>';
		if ( ! empty( $profile['is_verified'] ) ) {
			echo '<span style="display:inline-flex;align-items:center;gap:4px;background:#dcfce7;color:#15803d;font-size:11px;font-weight:700;padding:2px 8px;border-radius:10px;">'
				. '<span class="dashicons dashicons-yes-alt" style="font-size:12px;width:12px;height:12px;"></span>'
				. esc_html__( 'Verified', 'zeko-love' ) . '</span>';
		}
		echo '</div>';

		if ( ! empty( $profile['bio'] ) ) {
			echo '<p style="margin:8px 0 0;color:var(--color-text-secondary,#64748b);font-size:14px;line-height:1.6;">'
				. esc_html( wp_trim_words( $profile['bio'], 30 ) ) . '</p>';
		}

		echo '<div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;">';
		foreach ( array(
			'gender' => $profile['gender'],
			'goal'   => $goals[ $profile['relationship_goal'] ] ?? $profile['relationship_goal'],
			/* translators: %d: height in centimeters */
			'height' => $profile['height_cm'] ? sprintf( __( '%d cm', 'zeko-love' ), (int) $profile['height_cm'] ) : '',
			'city'   => '',
		) as $label => $value ) {
			if ( empty( $value ) ) {
				continue;
			}
			echo '<span style="background:var(--color-surface-alt,#f8fafc);border:1px solid var(--color-border,#e2e8f0);padding:3px 10px;border-radius:12px;font-size:12px;color:var(--color-text,#111827);">'
				. esc_html( $value ) . '</span>';
		}
		echo '</div>';

		echo '<div style="margin-top:12px;display:flex;gap:10px;flex-wrap:wrap;">';
		echo '<a href="' . esc_url( zeko_love_page_url( 'dating-messages' ) ) . '" style="background:#e11d48;color:#fff;padding:8px 16px;border-radius:20px;font-size:13px;font-weight:600;text-decoration:none;">'
			. esc_html__( 'Message', 'zeko-love' ) . '</a>';
		if ( get_current_user_id() === $user_id ) {
			echo '<a href="' . esc_url( zeko_love_page_url( 'dating-profile' ) ) . '" style="border:1px solid #e11d48;color:#e11d48;padding:8px 16px;border-radius:20px;font-size:13px;font-weight:600;text-decoration:none;">'
				. esc_html__( 'Edit profile', 'zeko-love' ) . '</a>';
		}
		echo '</div>';

		echo '</div></div>';
	}

	/**
	 * Render dating stats on a public user profile.
	 *
	 * @param int $user_id User id.
	 */
	public function render_profile_stats( int $user_id ): void {
		if ( ! $this->db->get_profile( $user_id ) ) {
			return;
		}

		$matches = $this->db->get_match_count( $user_id );
		$likes   = $this->db->get_received_like_count( $user_id );

		echo '<div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">';
		foreach ( array(
			array( 'matches', (string) $matches, __( 'Matches', 'zeko-love' ) ),
			array( 'likes', (string) $likes, __( 'Likes received', 'zeko-love' ) ),
		) as $stat ) {
			echo '<div style="background:var(--color-surface,#fff);border:1px solid var(--color-border,#e2e8f0);border-radius:12px;padding:12px 18px;text-align:center;">';
			echo '<div style="font-size:22px;font-weight:700;color:#e11d48;">' . esc_html( $stat[1] ) . '</div>';
			echo '<div style="font-size:12px;color:var(--color-text-secondary,#64748b);">' . esc_html( $stat[2] ) . '</div>';
			echo '</div>';
		}
		echo '</div>';
	}

	/**
	 * Add admin bar dating node.
	 */
	public function add_admin_bar_dating_node(): void {
		global $wp_admin_bar;

		if ( ! is_user_logged_in() ) {
			return;
		}

		$wp_admin_bar->add_node(
			array(
				'id'    => 'zeko-love',
				'title' => '<span class="ab-icon dashicons dashicons-heart"></span><span class="ab-label">' . esc_html__( 'Dating', 'zeko-love' ) . '</span>',
				'href'  => zeko_love_page_url( 'dating-browse' ),
			)
		);

		$wp_admin_bar->add_node(
			array(
				'id'     => 'zeko-love-browse',
				'parent' => 'zeko-love',
				'title'  => __( 'Browse', 'zeko-love' ),
				'href'   => zeko_love_page_url( 'dating-browse' ),
			)
		);

		$wp_admin_bar->add_node(
			array(
				'id'     => 'zeko-love-matches',
				'parent' => 'zeko-love',
				'title'  => __( 'Matches', 'zeko-love' ),
				'href'   => zeko_love_page_url( 'dating-matches' ),
			)
		);

		$wp_admin_bar->add_node(
			array(
				'id'     => 'zeko-love-messages',
				'parent' => 'zeko-love',
				'title'  => __( 'Messages', 'zeko-love' ),
				'href'   => zeko_love_page_url( 'dating-messages' ),
			)
		);

		$wp_admin_bar->add_node(
			array(
				'id'     => 'zeko-love-dates',
				'parent' => 'zeko-love',
				'title'  => __( 'Dates', 'zeko-love' ),
				'href'   => zeko_love_page_url( 'dating-schedule' ),
			)
		);

		$wp_admin_bar->add_node(
			array(
				'id'     => 'zeko-love-calls',
				'parent' => 'zeko-love',
				'title'  => __( 'Calls', 'zeko-love' ),
				'href'   => zeko_love_page_url( 'dating-calls' ),
			)
		);

		$wp_admin_bar->add_node(
			array(
				'id'     => 'zeko-love-settings',
				'parent' => 'zeko-love',
				'title'  => __( 'Settings', 'zeko-love' ),
				'href'   => zeko_love_page_url( 'dating-settings' ),
			)
		);
	}

	/**
	 * Add notification bell.
	 *
	 * @param WP_Admin_Bar $admin_bar Admin bar.
	 */
	public function add_notification_bell( WP_Admin_Bar $admin_bar ): void {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}

		$unread_messages = $this->db->count_unread_messages( $user_id );
		$total_unread    = $unread_messages;
		$count_class     = $total_unread > 0 ? 'zl-bell-unread' : '';
		$label           = $total_unread > 0
			? sprintf(
				'<span class="ab-icon dashicons dashicons-heart"></span><span class="zl-bell-count">%d</span>',
				$total_unread
			)
			: '<span class="ab-icon dashicons dashicons-heart"></span>';

		$admin_bar->add_node(
			array(
				'id'    => 'zeko-love-notifications',
				'title' => $label,
				'href'  => zeko_love_page_url( 'dating-messages' ),
				'meta'  => array(
					'class' => 'zl-admin-bell ' . $count_class,
					'title' => sprintf(
						/* translators: %d: number of unread notifications */
						_n( '%d unread notification', '%d unread notifications', $total_unread, 'zeko-love' ),
						$total_unread
					),
				),
			)
		);
	}

	/**
	 * Enqueue notification assets.
	 */
	public function enqueue_notification_assets(): void {
		if ( ! is_user_logged_in() ) {
			return;
		}

		if ( ! $this->is_love_page() ) {
			return;
		}

		wp_enqueue_style(
			'zeko-love-notifications',
			ZEKO_LOVE_PLUGIN_URL . 'assets/css/zl-notifications.css',
			array(),
			ZEKO_LOVE_VERSION
		);

		wp_enqueue_script(
			'zeko-love-notifications',
			ZEKO_LOVE_PLUGIN_URL . 'assets/js/zl-notifications.js',
			array( 'jquery' ),
			ZEKO_LOVE_VERSION,
			true
		);

		wp_localize_script(
			'zeko-love-notifications',
			'zlNotifications',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'zl_notifications_nonce' ),
				'pollInterval' => 30000,
				'i18n'         => array(
					'noNotifications' => __( 'No notifications yet.', 'zeko-love' ),
					'viewAll'         => __( 'View all', 'zeko-love' ),
				),
			)
		);
	}

	/**
	 * Love menu items.
	 */
	public function get_love_menu_items(): array {
		$slugs = array( 'dating-browse', 'dating-profile', 'dating-matches', 'dating-messages', 'dating-schedule', 'dating-settings', 'dating-calls' );
		$items = array();

		foreach ( $slugs as $slug ) {
			$page = class_exists( 'Zeko_Core_Helpers' )
				? Zeko_Core_Helpers::get_instance()->get_page_by_slug( $slug )
				: get_page_by_path( $slug );
			if ( ! $page ) {
				continue;
			}

			$items[] = (object) array(
				'title' => get_the_title( $page ),
				'url'   => get_permalink( $page ),
				'slug'  => $slug,
			);
		}

		return $items;
	}

	/**
	 * Render user status badge.
	 *
	 * @param int $user_id User id.
	 */
	public function render_user_status_badge( int $user_id ): string {
		$last_activity = get_user_meta( $user_id, 'zeko_love_last_activity', true );

		if ( ! $last_activity ) {
			return '<span class="zl-badge zl-badge-offline">' . esc_html__( 'Offline', 'zeko-love' ) . '</span>';
		}

		$now  = time();
		$then = strtotime( $last_activity );
		$diff = $now - $then;

		if ( $diff < 300 ) {
			return '<span class="zl-badge zl-badge-online">' . esc_html__( 'Online', 'zeko-love' ) . '</span>';
		}

		if ( $diff < DAY_IN_SECONDS ) {
			return '<span class="zl-badge zl-badge-recent">' . esc_html__( 'Recent', 'zeko-love' ) . '</span>';
		}

		return '<span class="zl-badge zl-badge-offline">' . esc_html__( 'Offline', 'zeko-love' ) . '</span>';
	}

	/**
	 * Premium badge.
	 */
	public function premium_badge(): string {
		return '<span class="zl-premium-badge" title="' . esc_attr__( 'Verified Premium Member', 'zeko-love' ) . '">'
			. '<span class="dashicons dashicons-yes-alt"></span>'
			. '<span class="zl-premium-label">' . esc_html__( 'Premium', 'zeko-love' ) . '</span>'
			. '</span>';
	}

	/**
	 * Love page.
	 */
	private function is_love_page(): bool {
		$love_slugs = array( 'dating-browse', 'dating-profile', 'dating-matches', 'dating-messages', 'dating-schedule', 'dating-settings', 'dating-calls' );

		foreach ( $love_slugs as $slug ) {
			if ( is_page( $slug ) ) {
				return true;
			}
		}

		return false;
	}
}
