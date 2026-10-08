=== Google Reviews for WordPress ===
Contributors: webifya
Tags: reviews, testimonials, carousel, google maps
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.2.0-rc.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Official map embeds and custom carousels for reviews you are authorized to publish. No API key required for installation.

== Description ==
Multiple locations and widgets, five custom templates, shortcode and block, authorized CSV/JSON imports, manual testimonials, an authorized local JSON feed adapter with daily scheduling, moderation, visual styling and consent-aware aggregate analytics.
Public Google Maps URLs do not retrieve individual reviews. Official embeds retain Google's presentation. No scraping or fabricated verification badges.

== Installation ==
1. Upload the ZIP via Plugins > Add New > Upload Plugin.
2. Activate and open Google Reviews.
3. Add a public location using the four-step editor. Paste an official sharing iframe to preview its map.
4. Import authorized reviews separately, create a widget, and insert its shortcode.
5. Configure privacy settings before enabling analytics or external embeds.

== Frequently Asked Questions ==
= Does a Google Maps URL retrieve all reviews without a key? =
No. Use Google's official embed, authorized imports, manual testimonials or a licensed adapter.
= Does synchronization work without a key? =
The authorized local JSON media feed can sync on schedule. It does not collect reviews from Google Maps.
= Is storage limited to 100 reviews? =
No. 100 is the default display count; imports and storage support larger datasets in batches.
= Does uninstall delete reviews? =
Only when explicitly enabled in Settings. Deactivation preserves data.
= What external services are used? =
Optional official Google Maps iframes contact Google (https://www.google.com/help/terms_maps/ and https://policies.google.com/privacy). Reviewer avatar URLs contact their configured hosts. Optional Google Business Profile owner tools contact accounts.google.com, oauth2.googleapis.com and Google Business Profile API hosts using OAuth (https://developers.google.com/my-business/content/policies). They do not republish Google reviews as public cards. Review external services and permissions before use.

== Changelog ==
= 1.2.0-rc.1 =
Public listing default, four-step editor, separate map/reviews previews, secure sharing iframe parsing, map/grid/combined shortcodes, optional widget maps, distinct connections, responsive administration, metadata-only schedule preservation. Live Google OAuth still requires verification.

= 1.2.0-rc.1 =
Optional fixture-tested Google owner OAuth/browser tools; improved builder, templates, review management, query caching and analytics; regression fixes. Live Google account verification remains outstanding. RC1 data-preserving upgrade verified.

= 1.0.0 =
Initial candidate. See repository verification record for executed tests and limitations.
