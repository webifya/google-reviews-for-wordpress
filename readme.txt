=== Menjar Financial Reviews ===
Contributors: webifya
Tags: reviews, carousel
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.7.0-rc.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Menjar Financial Google connection, live reviews and carousel style setup.

== Description ==
Three screens only: Connection, Reviews and Style Setup. The server restricts business connections and public review rendering to Menjar Financial's known Place ID. Existing encrypted Google authorization and saved styles are preserved. Google review content uses an encrypted 15-minute performance cache, not a permanent archive. Public display must comply with the applicable Google agreement.

== Installation ==
1. Replace the existing plugin with this ZIP; do not uninstall to upgrade.
2. Connection: if already signed into Google, click Connect Menjar Financial.
3. Enable authorized website display. Reviews: browse and refresh the Google API pages.
4. Style Setup: customize the existing design and publish [menjar_reviews]. Existing [google_reviews_widget id="123"] shortcodes still work.
5. See API-SETUP.md for private OAuth setup when needed.

== Changelog ==
= 1.7.0-rc.1 =
Dedicated Menjar Financial build. Removes scraper, worker libraries, analytics, maps, imports, unrelated providers and generic business/widget management from the active plugin. Stops previous scheduled jobs. Preserves authorization, old data and existing shortcode IDs.
