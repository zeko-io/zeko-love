<?php
/**
 * Email notifications for Zeko Love.
 *
 * Sends transactional HTML emails for matches, messages, date scheduling, and reminders.
 *
 * @package Zeko_Love
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Class Zeko_Love_Emails. */
class Zeko_Love_Emails {

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
		add_action( 'zeko_love_new_match', array( $this, 'notify_new_match' ), 10, 3 );
		add_action( 'zeko_love_new_message', array( $this, 'notify_new_message' ), 10, 3 );
		add_action( 'zeko_love_date_scheduled', array( $this, 'notify_date_scheduled' ), 10, 1 );
		add_action( 'zeko_love_date_reminder', array( $this, 'notify_date_reminder' ), 10, 1 );
		add_action( 'zeko_love_call_reminder', array( $this, 'notify_call_reminder' ), 10, 1 );
		add_action( 'zeko_love_reminders', array( $this, 'process_daily_reminders' ) );
	}

	/**
	 * Daily cron: fire reminder emails for upcoming confirmed dates and
	 * call bookings. Each item is sent at most once (48h transient guard).
	 */
	public function process_daily_reminders(): void {
		foreach ( $this->db->get_upcoming_dates() as $date ) {
			$id = (int) $date['schedule_id'];
			if ( get_transient( 'zeko_love_date_reminder_' . $id ) ) {
				continue;
			}
			set_transient( 'zeko_love_date_reminder_' . $id, 1, 2 * DAY_IN_SECONDS );
			do_action( 'zeko_love_date_reminder', $id );
		}

		foreach ( $this->db->get_upcoming_call_bookings() as $booking ) {
			$id = (int) $booking['booking_id'];
			if ( get_transient( 'zeko_love_call_reminder_' . $id ) ) {
				continue;
			}
			set_transient( 'zeko_love_call_reminder_' . $id, 1, 2 * DAY_IN_SECONDS );
			do_action( 'zeko_love_call_reminder', $id );
		}
	}

	/**
	 * Whether the user opted in to a reminder email (default on until they
	 * explicitly disable it in the settings hub).
	 *
	 * @param int    $user_id User id.
	 * @param string $key Key.
	 */
	private function wants_reminder( int $user_id, string $key ): bool {
		$settings = get_user_meta( $user_id, 'zeko_love_user_settings', true );
		$settings = is_array( $settings ) ? $settings : array();
		return ! isset( $settings[ $key ] ) || ! empty( $settings[ $key ] );
	}

	/**
	 * Notify new match.
	 *
	 * @param int   $user1_id User1 id.
	 * @param int   $user2_id User2 id.
	 * @param float $score Score.
	 */
	public function notify_new_match( int $user1_id, int $user2_id, float $score ): void {
		$user1 = get_userdata( $user1_id );
		$user2 = get_userdata( $user2_id );

		if ( ! $user1 || ! $user2 ) {
			return;
		}

		$profile1 = $this->db->get_profile( $user1_id );
		$profile2 = $this->db->get_profile( $user2_id );

		$name1 = $profile1['display_name'] ?? $user1->display_name;
		$name2 = $profile2['display_name'] ?? $user2->display_name;

		$subject1 = sprintf(
			/* translators: %s: matched user name */
			__( 'You have a new match with %s!', 'zeko-love' ),
			$name2
		);

		$subject2 = sprintf(
			/* translators: %s: matched user name */
			__( 'You have a new match with %s!', 'zeko-love' ),
			$name1
		);

		$body1 = $this->build_match_email( $name1, $name2, $score );
		$body2 = $this->build_match_email( $name2, $name1, $score );

		$this->send_email( $user1_id, $subject1, $body1 );
		$this->send_email( $user2_id, $subject2, $body2 );
	}

	/**
	 * Notify new message.
	 *
	 * @param int    $receiver_id Receiver id.
	 * @param int    $sender_id Sender id.
	 * @param string $preview Preview.
	 */
	public function notify_new_message( int $receiver_id, int $sender_id, string $preview ): void {
		$receiver = get_userdata( $receiver_id );
		$sender   = get_userdata( $sender_id );

		if ( ! $receiver || ! $sender ) {
			return;
		}

		$sender_profile = $this->db->get_profile( $sender_id );
		$sender_name    = $sender_profile['display_name'] ?? $sender->display_name;

		$subject = sprintf(
			/* translators: %s: sender name */
			__( 'New message from %s', 'zeko-love' ),
			$sender_name
		);

		$message = $this->build_html_wrapper(
			$this->email_header(),
			$this->email_content(
				__( 'New Message', 'zeko-love' ),
				sprintf(
					/* translators: %s: sender name */
					__( 'You received a new message from %s.', 'zeko-love' ),
					$sender_name
				),
				'<p style="margin: 0 0 16px; font-size: 14px; line-height: 1.6; color: #4a5568; font-style: italic;">' . esc_html( $preview ) . '</p>',
				__( 'View Message', 'zeko-love' ),
				home_url( '/dating-messages/' )
			),
			$this->email_footer()
		);

		$this->send_email( $receiver_id, $subject, $message );
	}

	/**
	 * Notify date scheduled.
	 *
	 * @param int $schedule_id Schedule id.
	 */
	public function notify_date_scheduled( int $schedule_id ): void {
		$schedule = $this->db->get_date( $schedule_id );

		if ( ! $schedule ) {
			return;
		}

		$user1 = get_userdata( (int) $schedule['user1_id'] );
		$user2 = get_userdata( (int) $schedule['user2_id'] );

		if ( ! $user1 || ! $user2 ) {
			return;
		}

		$profile1 = $this->db->get_profile( (int) $schedule['user1_id'] );
		$profile2 = $this->db->get_profile( (int) $schedule['user2_id'] );

		$name1 = $profile1['display_name'] ?? $user1->display_name;
		$name2 = $profile2['display_name'] ?? $user2->display_name;

		$date  = esc_html( $schedule['proposed_date'] ?? __( 'TBD', 'zeko-love' ) );
		$time  = esc_html( $schedule['proposed_time'] ?? __( 'TBD', 'zeko-love' ) );
		$loc   = esc_html( $schedule['location_name'] ?? '' );
		$notes = esc_html( $schedule['notes'] ?? '' );

		$details = '';

		if ( $date ) {
			$details .= '<p style="margin: 0 0 8px; font-size: 14px; line-height: 1.6; color: #4a5568;"><strong>' . esc_html__( 'Date:', 'zeko-love' ) . '</strong> ' . $date . '</p>';
		}

		if ( $time ) {
			$details .= '<p style="margin: 0 0 8px; font-size: 14px; line-height: 1.6; color: #4a5568;"><strong>' . esc_html__( 'Time:', 'zeko-love' ) . '</strong> ' . $time . '</p>';
		}

		if ( $loc ) {
			$details .= '<p style="margin: 0 0 8px; font-size: 14px; line-height: 1.6; color: #4a5568;"><strong>' . esc_html__( 'Location:', 'zeko-love' ) . '</strong> ' . $loc . '</p>';
		}

		if ( $notes ) {
			$details .= '<p style="margin: 0 0 8px; font-size: 14px; line-height: 1.6; color: #4a5568;"><strong>' . esc_html__( 'Notes:', 'zeko-love' ) . '</strong> ' . $notes . '</p>';
		}

		$message = $this->build_html_wrapper(
			$this->email_header(),
			$this->email_content(
				__( 'Date Scheduled!', 'zeko-love' ),
				sprintf(
					/* translators: 1: user name, 2: other user name */
					__( 'Hi %1$s, a date has been scheduled between you and %2$s.', 'zeko-love' ),
					esc_html( $name1 ),
					esc_html( $name2 )
				),
				$details,
				__( 'View Date Details', 'zeko-love' ),
				home_url( '/dating-schedule/' )
			),
			$this->email_footer()
		);

		$this->send_email( (int) $schedule['user1_id'], __( 'Your date has been scheduled!', 'zeko-love' ), $message );

		$message2 = $this->build_html_wrapper(
			$this->email_header(),
			$this->email_content(
				__( 'Date Scheduled!', 'zeko-love' ),
				sprintf(
					/* translators: 1: user name, 2: other user name */
					__( 'Hi %1$s, a date has been scheduled between you and %2$s.', 'zeko-love' ),
					esc_html( $name2 ),
					esc_html( $name1 )
				),
				$details,
				__( 'View Date Details', 'zeko-love' ),
				home_url( '/dating-schedule/' )
			),
			$this->email_footer()
		);

		$this->send_email( (int) $schedule['user2_id'], __( 'Your date has been scheduled!', 'zeko-love' ), $message2 );
	}

	/**
	 * Notify date reminder.
	 *
	 * @param int $schedule_id Schedule id.
	 */
	public function notify_date_reminder( int $schedule_id ): void {
		$schedule = $this->db->get_date( $schedule_id );

		if ( ! $schedule ) {
			return;
		}

		$user1 = get_userdata( (int) $schedule['user1_id'] );
		$user2 = get_userdata( (int) $schedule['user2_id'] );

		if ( ! $user1 || ! $user2 ) {
			return;
		}

		$profile1 = $this->db->get_profile( (int) $schedule['user1_id'] );
		$profile2 = $this->db->get_profile( (int) $schedule['user2_id'] );

		$name1 = $profile1['display_name'] ?? $user1->display_name;
		$name2 = $profile2['display_name'] ?? $user2->display_name;

		$date = esc_html( $schedule['proposed_date'] ?? __( 'TBD', 'zeko-love' ) );
		$time = esc_html( $schedule['proposed_time'] ?? __( 'TBD', 'zeko-love' ) );
		$loc  = esc_html( $schedule['location_name'] ?? '' );

		$details = '';

		if ( $date ) {
			$details .= '<p style="margin: 0 0 8px; font-size: 14px; line-height: 1.6; color: #4a5568;"><strong>' . esc_html__( 'Date:', 'zeko-love' ) . '</strong> ' . $date . '</p>';
		}

		if ( $time ) {
			$details .= '<p style="margin: 0 0 8px; font-size: 14px; line-height: 1.6; color: #4a5568;"><strong>' . esc_html__( 'Time:', 'zeko-love' ) . '</strong> ' . $time . '</p>';
		}

		if ( $loc ) {
			$details .= '<p style="margin: 0 0 8px; font-size: 14px; line-height: 1.6; color: #4a5568;"><strong>' . esc_html__( 'Location:', 'zeko-love' ) . '</strong> ' . $loc . '</p>';
		}

		$message = $this->build_html_wrapper(
			$this->email_header(),
			$this->email_content(
				__( 'Date Reminder', 'zeko-love' ),
				sprintf(
					/* translators: 1: user name, 2: other user name */
					__( 'Hi %1$s, this is a reminder about your upcoming date with %2$s!', 'zeko-love' ),
					esc_html( $name1 ),
					esc_html( $name2 )
				),
				$details,
				__( 'View Date Details', 'zeko-love' ),
				home_url( '/dating-schedule/' )
			),
			$this->email_footer()
		);

		if ( $this->wants_reminder( (int) $schedule['user1_id'], 'notify_date_reminder' ) ) {
			$this->send_email( (int) $schedule['user1_id'], __( 'Date reminder!', 'zeko-love' ), $message );
		}

		$message2 = $this->build_html_wrapper(
			$this->email_header(),
			$this->email_content(
				__( 'Date Reminder', 'zeko-love' ),
				sprintf(
					/* translators: 1: user name, 2: other user name */
					__( 'Hi %1$s, this is a reminder about your upcoming date with %2$s!', 'zeko-love' ),
					esc_html( $name2 ),
					esc_html( $name1 )
				),
				$details,
				__( 'View Date Details', 'zeko-love' ),
				home_url( '/dating-schedule/' )
			),
			$this->email_footer()
		);

		if ( $this->wants_reminder( (int) $schedule['user2_id'], 'notify_date_reminder' ) ) {
			$this->send_email( (int) $schedule['user2_id'], __( 'Date reminder!', 'zeko-love' ), $message2 );
		}
	}

	/**
	 * Send a reminder for a confirmed call booking to both participants.
	 *
	 * @param int $booking_id Booking id.
	 */
	public function notify_call_reminder( int $booking_id ): void {
		$booking = $this->db->get_call_booking( $booking_id );
		if ( ! $booking || 'confirmed' !== ( $booking['status'] ?? '' ) ) {
			return;
		}

		$listing        = $this->db->get_call_listing( (int) $booking['listing_id'] );
		$buyer          = get_userdata( (int) $booking['buyer_id'] );
		$seller         = get_userdata( (int) $booking['seller_id'] );
		$buyer_profile  = $this->db->get_profile( (int) $booking['buyer_id'] );
		$seller_profile = $this->db->get_profile( (int) $booking['seller_id'] );

		if ( ! $buyer || ! $seller ) {
			return;
		}

		$buyer_name  = ! empty( $buyer_profile['display_name'] ) ? $buyer_profile['display_name'] : $buyer->display_name;
		$seller_name = ! empty( $seller_profile['display_name'] ) ? $seller_profile['display_name'] : $seller->display_name;
		$title       = (string) ( $listing['title'] ?? __( 'Call', 'zeko-love' ) );
		$when        = mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $booking['start_at'] );
		$details     = '<p style="margin: 0 0 8px; font-size: 14px; line-height: 1.6; color: #4a5568;"><strong>' . esc_html__( 'Call:', 'zeko-love' ) . '</strong> ' . esc_html( $title ) . '</p>'
			. '<p style="margin: 0 0 8px; font-size: 14px; line-height: 1.6; color: #4a5568;"><strong>' . esc_html__( 'When:', 'zeko-love' ) . '</strong> ' . esc_html( $when ) . '</p>'
			. '<p style="margin: 0 0 8px; font-size: 14px; line-height: 1.6; color: #4a5568;"><strong>' . esc_html__( 'Duration:', 'zeko-love' ) . '</strong> ' . (int) $booking['duration_minutes'] . ' min</p>';

		if ( $this->wants_reminder( (int) $booking['seller_id'], 'notify_call_reminder' ) ) {
			$message = $this->build_html_wrapper(
				$this->email_header(),
				$this->email_content(
					__( 'Call Reminder', 'zeko-love' ),
					sprintf(
						/* translators: 1: seller name, 2: buyer name */
						__( 'Hi %1$s, your call with %2$s is coming up soon.', 'zeko-love' ),
						esc_html( $seller_name ),
						esc_html( $buyer_name )
					),
					$details,
					__( 'View Call Details', 'zeko-love' ),
					home_url( '/dating-calls/' )
				),
				$this->email_footer()
			);
			$this->send_email( (int) $booking['seller_id'], __( 'Upcoming call reminder', 'zeko-love' ), $message );
		}

		if ( $this->wants_reminder( (int) $booking['buyer_id'], 'notify_call_reminder' ) ) {
			$message = $this->build_html_wrapper(
				$this->email_header(),
				$this->email_content(
					__( 'Call Reminder', 'zeko-love' ),
					sprintf(
						/* translators: 1: buyer name, 2: seller name */
						__( 'Hi %1$s, your call with %2$s is coming up soon.', 'zeko-love' ),
						esc_html( $buyer_name ),
						esc_html( $seller_name )
					),
					$details,
					__( 'View Call Details', 'zeko-love' ),
					home_url( '/dating-calls/' )
				),
				$this->email_footer()
			);
			$this->send_email( (int) $booking['buyer_id'], __( 'Upcoming call reminder', 'zeko-love' ), $message );
		}
	}

	/**
	 * Send email.
	 *
	 * @param int    $to_user_id To user id.
	 * @param string $subject Subject.
	 * @param string $message Message.
	 */
	public function send_email( int $to_user_id, string $subject, string $message ): bool {
		$user = get_userdata( $to_user_id );

		if ( ! $user || empty( $user->user_email ) ) {
			return false;
		}

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . get_bloginfo( 'name' ) . ' <' . get_option( 'admin_email' ) . '>',
		);

		return wp_mail( $user->user_email, $subject, $message, $headers );
	}

	/**
	 * Build match email.
	 *
	 * @param string $recipient_name Recipient name.
	 * @param string $other_name Other name.
	 * @param float  $score Score.
	 */
	private function build_match_email( string $recipient_name, string $other_name, float $score ): string {
		$extra = '<p style="margin: 0 0 16px; font-size: 14px; line-height: 1.6; color: #4a5568;">' .
			sprintf(
				/* translators: %d: compatibility score */
				esc_html__( 'Your compatibility score: %d%%', 'zeko-love' ),
				(int) round( $score )
			) .
			'</p>';

		if ( $score >= 70 ) {
			$extra .= '<p style="margin: 0 0 16px; font-size: 14px; line-height: 1.6; color: #ec4899; font-style: italic;">' .
				esc_html__( 'You two have a lot in common — why not break the ice?', 'zeko-love' ) .
				'</p>';
		}

		return $this->build_html_wrapper(
			$this->email_header(),
			$this->email_content(
				__( "It's a Match!", 'zeko-love' ),
				sprintf(
					/* translators: 1: recipient name, 2: match name */
					__( 'Congratulations %1$s, you matched with %2$s!', 'zeko-love' ),
					esc_html( $recipient_name ),
					esc_html( $other_name )
				),
				$extra,
				__( 'Send a Message', 'zeko-love' ),
				home_url( '/dating-matches/' )
			),
			$this->email_footer()
		);
	}

	/**
	 * Email header.
	 */
	private function email_header(): string {
		return '
		<tr>
			<td style="background-color: #ec4899; height: 5px; line-height: 5px; font-size: 0;">&nbsp;</td>
		</tr>
		<tr>
			<td style="background-color: #ec4899; padding: 30px 40px; text-align: center;">
				<h1 style="margin: 0; font-size: 28px; color: #ffffff; font-family: Arial, Helvetica, sans-serif;">' . esc_html__( 'Zeko Love', 'zeko-love' ) . '</h1>
				<p style="margin: 4px 0 0; font-size: 14px; color: rgba(255,255,255,0.85); font-family: Arial, Helvetica, sans-serif;">' . esc_html__( 'Find your perfect match', 'zeko-love' ) . '</p>
			</td>
		</tr>';
	}

	/**
	 * Email content.
	 *
	 * @param string $heading Heading.
	 * @param string $intro Intro.
	 * @param string $extra Extra.
	 * @param string $button_text Button text.
	 * @param string $button_url Button url.
	 */
	private function email_content( string $heading, string $intro, string $extra, string $button_text, string $button_url ): string {
		return '
		<tr>
			<td style="padding: 40px; background-color: #ffffff;">
				<h2 style="margin: 0 0 20px; font-size: 22px; color: #1a202c; font-family: Arial, Helvetica, sans-serif;">' . esc_html( $heading ) . '</h2>
				<p style="margin: 0 0 16px; font-size: 14px; line-height: 1.6; color: #4a5568; font-family: Arial, Helvetica, sans-serif;">' . $intro . '</p>
				' . $extra . '
				<table border="0" cellpadding="0" cellspacing="0" style="margin-top: 24px;">
					<tr>
						<td style="background-color: #ec4899; border-radius: 6px; text-align: center;">
							<a href="' . esc_url( $button_url ) . '" style="display: inline-block; padding: 12px 32px; font-size: 15px; color: #ffffff; text-decoration: none; font-family: Arial, Helvetica, sans-serif; font-weight: bold;">' . esc_html( $button_text ) . '</a>
						</td>
					</tr>
				</table>
			</td>
		</tr>';
	}

	/**
	 * Email footer.
	 */
	private function email_footer(): string {
		return '
		<tr>
			<td style="background-color: #f7fafc; padding: 24px 40px; text-align: center; border-top: 1px solid #e2e8f0;">
				<p style="margin: 0; font-size: 12px; color: #a0aec0; font-family: Arial, Helvetica, sans-serif;">' . esc_html__( 'You received this email because you are a member of', 'zeko-love' ) . ' ' . esc_html( get_bloginfo( 'name' ) ) . '.</p>
				<p style="margin: 4px 0 0; font-size: 12px; color: #a0aec0; font-family: Arial, Helvetica, sans-serif;">' . esc_html__( 'Manage your notifications in your dating settings.', 'zeko-love' ) . '</p>
			</td>
		</tr>';
	}

	/**
	 * Build html wrapper.
	 *
	 * @param string $header Header.
	 * @param string $content Content.
	 * @param string $footer Footer.
	 */
	private function build_html_wrapper( string $header, string $content, string $footer ): string {
		return '<!DOCTYPE html>
<html lang="' . esc_attr( get_bloginfo( 'language' ) ) . '">
<head>
	<meta charset="' . esc_attr( get_bloginfo( 'charset' ) ) . '">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: Arial, Helvetica, sans-serif;">
	<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f1f5f9; padding: 20px;">
		<tr>
			<td align="center">
				<table border="0" cellpadding="0" cellspacing="0" width="600" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 8px; overflow: hidden;">
					' . $header . '
					' . $content . '
					' . $footer . '
				</table>
			</td>
		</tr>
	</table>
</body>
</html>';
	}
}
