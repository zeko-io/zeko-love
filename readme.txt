=== Zeko Love ===
Contributors: zeko
Tags: dating, matchmaking, matching, dating-site, relationships
Requires at least: 5.8
Tested up to: 7.1.2
Requires PHP: 7.4
Stable tag: 1.4.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Full-featured dating and matchmaking module for the Zeko ecosystem with smart matching, date scheduling, photo verification, safety tools, and a paid Calls addon.

== Description ==

Zeko Love is a dating and matchmaking module for the Zeko ecosystem. Members browse profiles, like, superlike, or pass, match, and message — including wallet-funded gifts and date proposals.

= Features =

* **Profile Browsing** — Browse and filter member profiles by criteria
* **Likes, Superlikes & Passes** — Swipe-style matching with daily limits and purchasable superlikes
* **Matches** — Mutual-interest matching with notifications
* **Messaging** — Private conversations with match partners
* **Wallet Gifts** — Send gifts funded through the Zeko Pay wallet
* **Date Proposing** — Propose real-world or virtual dates from matches
* **Photo Verification** — Verify profile photos (manual or AI-assisted) with SLA tracking
* **Safety Tools** — Report/block flows with evidence and admin escalation
* **Advanced Settings Hub** — Privacy, discovery, and notification preferences in one place
* **Calls Addon** — Monetized addon for paid call listings, plans, bookings, reviews, and earnings
* **Earnings** — Call providers earn payouts through Zeko Pay with an OTP-protected withdrawal flow

= Shortcodes =

* `[zeko_love_browse]` — Member browsing grid
* `[zeko_love_profile]` — Single member profile
* `[zeko_love_matches]` — Matches list
* `[zeko_love_messages]` — Messaging inbox
* `[zeko_love_dates]` — Date proposals
* `[zeko_love_settings]` — Settings hub
* `[zeko_love_calls]` — Calls addon listings and bookings
* `[zeko_love_calls_strip]` — Calls addon widget strip

== Installation ==

1. Upload the `zeko-love` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Activate Zeko Pay for wallet gifts and call payouts
4. Create pages and add the shortcodes above

== Frequently Asked Questions ==

= Is Zeko Love a dating site plugin? =

Yes. It provides profiles, matching, messaging, dates, and safety tools, plus the optional paid Calls addon for monetizing conversations and services.

= Do I need Zeko Pay? =

For social features, no. Wallet gifts and Calls earnings require Zeko Pay.

== Dependencies ==

Zeko Love runs standalone for dating profiles, matching, messaging, and safety tools. Zeko Pay is required for wallet gifts and Calls earnings, and Zeko AI (when active) powers optional moderation of profile text.

== Privacy ==

Data stored includes member profile fields and photos, likes/passes, matches, private messages, date proposals, report/block records, and (with Zeko Pay) gift and earnings records. Activity entries store an IP address. The plugin's uninstall also removes love-scoped user meta, and a `wp_privacy_personal_data_erasers` registration covers messages and profile data.

== External Services ==

Zeko Love makes no server-side outbound requests of its own. Payment
processing goes through Zeko Pay's configured gateways; optional profile-text
moderation is sent to Zeko AI when that plugin is active and configured with a
provider.

* **YouTube / Vimeo intro videos (client-side)** — When a listing has an intro
  video URL, the visitor's browser loads `https://www.youtube.com/embed/{id}`
  or `https://player.vimeo.com/video/{id}`; the provider then receives the
  embed request and the visitor's IP per its own policies. What is sent: no
  profile, email, or account data. When: only when a listing has a video and a
  visitor opens it.

Detail with file:line citations is in `REMOTE-SERVICES.txt` in this plugin
folder.

== Third-party resources ==

Zeko Love bundles no third-party fonts, scripts, icon libraries, or images
(icons are inline SVG; the only image is the screenshot). All bundled code is
original Zeko work under GPL-2.0-or-later.

== Uninstall ==

Deleting the plugin drops all Zeko Love tables, clears its crons (match cleanup, email reminders), removes only the pages and nav items it created, and deletes love-scoped user meta and options.

== Troubleshooting ==

* Gifts/payouts fail? Confirm Zeko Pay is active and the wallet charge succeeded.
* No notifications? Date/call reminders are opt-in — check the user's settings hub.
* Profile moderation not running? Install and configure Zeko AI, or review the thresholds in Zeko AI settings.

== Screenshots ==

1. Matchmaking dashboard with curated matches


== Changelog ==

= 1.4.1 =
* REST profiles endpoint no longer exposes user emails in browse results
* Uninstall now drops all tables, cron, options, pages, and love-scoped usermeta
* Accept-match AJAX verifies the caller is a participant
* Booking payout failures no longer report success (booking stays confirmed for retry)
* Stale-match cleanup cron wired to a handler
* Withdrawal OTP flow for earnings payouts
* Dating profile text auto-reviewed by Zeko AI moderation on save
* Email reminders for upcoming confirmed dates and call bookings (daily cron, at-most-once per item, per-user opt-out in settings)
* Report trust signals: repeat-offender counts surfaced in the admin Reports page

= 1.0.0 =
* Initial release
* Profile browsing, likes/superlikes/passes, matching, messaging, gifts, date proposals
* Photo verification and safety tools
* Calls addon with listings, plans, bookings, reviews, and earnings

== Upgrade Notice ==

= 1.4.1 =
Security and reliability fixes. Please upgrade as soon as possible.
