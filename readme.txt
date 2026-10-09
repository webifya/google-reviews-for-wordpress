=== Google Reviews for WordPress ===
Contributors: webifya
Tags: reviews, carousel, google maps
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.4.1-rc.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Permitted local review carousels, three-day synchronization and consent-aware analytics.

== Description ==
Review carousels from permitted local collections. Primary navigation: Dashboard, Locations, Reviews, Widgets, Analytics, Settings. New setup has no Google Cloud/key/billing/OAuth prompts. Maps URL/Place ID validation is local only; a public listing does not grant review access or storage rights. Direct API-free downloading remains blocked; no scraper or fake download control is installed.
Download/sync actions require an installed functioning permitted source. Default local interval: 72 hours. No real licensed feed or elapsed live three-day cycle was verified. Existing live Places widgets retain their configured limited live-only behavior; no credentials or records are silently deleted.
New widgets: 100 newest reviews, 5-second autoplay, loop, hover pause, 3/2/1 cards. Fresh analytics enabled with explicit consent; existing privacy/settings/history preserved. Legacy widget IDs, styles, shortcodes and analytics remain compatible. Review upload/manual/map builders are absent from primary UI.

== Installation ==
1. Back up your site; upload/activate or replace the ZIP via Plugins > Add New > Upload Plugin. Do not uninstall to upgrade.
2. Locations > Add Location: enter Maps URL/Place ID, Check listing link, confirm business manually and save your label/address. No reviews are downloaded by saving.
3. Eligible installed permitted sources can use Download available reviews and validated local scheduling. Without one, retrieval remains unavailable with an explicit explanation.
4. Create a widget and paste [google_reviews_widget id="123"] into a page.

== Frequently Asked Questions ==
= Can I download all Google reviews without an API or provider? =
No documented permitted direct mechanism was established. Actual accessible counts remain unknown. Public listing visibility is not independent archive permission.
= What happens to existing keys and widgets? =
Encrypted Google settings, IDs, local records and analytics are retained. Existing configured live widgets keep their compatibility path. No new Google account setup is offered in the UI.
= Do 72-hour updates work? =
The existing scheduler works with eligible permitted adapters and synthetic tests. No genuine live provider or full production 72-hour cycle was verified.
= What does uninstall remove? =
By default data is retained. Deactivation preserves all records/settings. Uninstall deletes plugin data only if its existing explicit delete-data preference is enabled.
= What external services are used? =
New identifier validation uses no external service. Stored shortcodes do not fetch a provider. Existing configured live widgets may call Places API server-side and load supplied avatars/attribution URLs; legacy owner access may call its configured OAuth/Google services. A separately installed permitted connector may use its documented host. Read the repository source/retention/privacy documentation.

== Changelog ==
= 1.4.1-rc.1 =
Remove key/billing/OAuth setup from administration, validate Maps identifiers locally and show truthful source/count/blocker status. Preserve credentials, existing live widgets, local tables, styles, analytics and shortcodes. No direct Google downloader is claimed.

= 1.4.0-rc.1 =
Default 72-hour synchronization and 1/3/5/7-day/manual choices; only validated licensed sources schedule. Local provenance, stable-ID deduplication, last-sync timestamps, explicit provider-removal handling, resumed statistics and safe errors. Simple advanced identifiers, actual saved/access counts, dashboard views/dates, accurate Sync Now notices and fresh-install consent-gated analytics. Preserve existing intervals, privacy settings, carousel, shortcodes and data. Includes unpublished Phase 5 research/reliability groundwork. Live non-owned retrieval/local archive remains blocked by account/license requirements.

= 1.3.0-rc.1 =
Live-only Google Places adapter with encrypted keys, request budgets, identity lookup, tested connection flow, mandatory author/source attribution and no stored review content. Six-item navigation, review dashboard, review-only builder, configurable empty states and documented licensed daily provider contract. Legacy migration safety preserved. Live Google access remains unverified; full-history non-owned daily archive requires a permitted provider.

= 1.2.0-rc.1 =
Public listing default, four-step editor, separate map/reviews previews, secure sharing iframe parsing, map/grid/combined shortcodes, optional widget maps, distinct connections, responsive administration, metadata-only schedule preservation. Live Google OAuth still requires verification.

= 1.1.0-rc.1 =
Optional fixture-tested Google owner OAuth/browser tools; improved builder, templates, review management, query caching and analytics; regression fixes. Live Google account verification remains outstanding. RC1 data-preserving upgrade verified.

= 1.0.0 =
Initial candidate. See repository verification record for executed tests and limitations.
