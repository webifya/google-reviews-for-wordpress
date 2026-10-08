=== Google Reviews for WordPress ===
Contributors: webifya
Tags: reviews, carousel, google maps
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.3.0-rc.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Live Google Places review carousels, permitted provider adapters and consent-aware analytics.

== Description ==
Review-first setup: add a business, connect a supported source, test retrieval, customize a carousel and publish its shortcode. Navigation: Dashboard, Locations, Reviews, Widgets, Analytics, Settings.
Google Places API (New) supports non-owned listings with up to five Google-selected reviews. Billing, a restricted API key and applicable agreement/terms/privacy are required. Reviews load live without WordPress storage or page-cached content. JavaScript is required. Daily stored synchronization needs an installed provider with documented permanent storage/public display rights. No such live feed was verified. This is a candidate, not proof of production Google retrieval.
Legacy records, widget IDs, analytics and map/grid/combined shortcodes remain compatible. Upload/manual/map builders are removed from normal UI. No scraping or fake verification indicators.

== Installation ==
1. Upload and activate the ZIP via Plugins > Add New > Upload Plugin.
2. Add a location by Maps business URL or Place ID and confirm its identity.
3. Open Settings > Review connections and privately configure Google Places API (New), billing and a restricted server key. Set Google project quotas and the site's request budget.
4. Test retrieval, preview actual returned reviews, create a review widget and copy [google_reviews_widget id="123"].
5. Configure consent/privacy before analytics. Upgrade by replacing the plugin ZIP, not uninstalling.

== Frequently Asked Questions ==
= Does a Maps URL retrieve all reviews without a key? =
No. Places needs a key and supplies up to five selected reviews; share URLs without a Place ID require manual ID entry.
= Does Places support daily stored review synchronization? =
No. Google review caching restrictions prevent this implementation from maintaining an archive. A permitted licensed provider is needed.
= Does saving credentials mean Connected? =
No. Review retrieval must successfully return individual reviews.
= What does uninstall remove? =
Private credentials are always removed. Other plugin data is retained unless deletion is explicitly enabled. Deactivation preserves data.
= What external services are used? =
Google Places calls places.googleapis.com from WordPress using your encrypted server key; live display contacts supplied reviewer image hosts. Google Maps attribution/source links are retained. Review Google's service terms (https://cloud.google.com/maps-platform/terms/maps-service-terms), Places policy (https://developers.google.com/maps/documentation/places/web-service/policies), pricing (https://developers.google.com/maps/billing-and-pricing/pricing) and privacy (https://policies.google.com/privacy). Google account/OAuth/Business Profile endpoints are optional owner-dashboard tools (https://developers.google.com/my-business/content/policies). Existing map shortcodes contact Google through their saved official iframe. Purge page caches after key changes; never cache live-widget POST REST responses.

== Changelog ==
= 1.3.0-rc.1 =
Live-only Google Places adapter with encrypted keys, request budgets, identity lookup, tested connection flow, mandatory author/source attribution and no stored review content. Six-item navigation, review dashboard, review-only builder, configurable empty states and documented licensed daily provider contract. Legacy migration safety preserved. Live Google access remains unverified; full-history non-owned daily archive requires a permitted provider.

= 1.2.0-rc.1 =
Public listing default, four-step editor, separate map/reviews previews, secure sharing iframe parsing, map/grid/combined shortcodes, optional widget maps, distinct connections, responsive administration, metadata-only schedule preservation. Live Google OAuth still requires verification.

= 1.1.0-rc.1 =
Optional fixture-tested Google owner OAuth/browser tools; improved builder, templates, review management, query caching and analytics; regression fixes. Live Google account verification remains outstanding. RC1 data-preserving upgrade verified.

= 1.0.0 =
Initial candidate. See repository verification record for executed tests and limitations.
