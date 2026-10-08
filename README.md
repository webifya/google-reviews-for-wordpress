# Google Reviews for WordPress

**1.3.0-rc.1 — Phase 4 candidate. Live Google access has not been verified.**

Review carousels are the primary product. Google Places API (New) is implemented for non-owned listings, with at most **five Google-selected reviews per business**. It requires a billing-enabled Google Cloud project, a restricted server API key and acceptance of the applicable agreement. Places content loads live; it is not saved to WordPress or embedded into cached pages. This provider cannot supply a daily stored review archive or 100 reviews from a non-owned listing.

Daily stored synchronization is available for trusted adapters with a documented permanent storage and custom display grant. No such third-party account/license was available for live testing. The complete non-owned → 100 reviews → daily stored sync requirement remains blocked. Fixtures prove software behavior, not live provider access.

## Install and connect

1. Requires WordPress 6.4+, PHP 8.1+, MySQL/MariaDB. Upload the candidate ZIP through **Plugins → Add New → Upload Plugin** and activate.
2. Open **Google Reviews → Locations → Add Location**. Enter a Maps business URL or Place ID. **Find Business** uses configured Places credentials. Standard Maps /place/ URLs use official Text Search and show up to three candidates for confirmation. Supported share redirects are resolved without reading listing HTML. URLs without a usable business name or Place ID require manual ID entry. Manual business identity confirmation is available without credentials.
3. Confirm the business and supply your own label/address. Google lookup identity, rating and count are shown only during that request; the Place ID is saved.
4. **Save location & connect reviews** opens six steps: choose business, choose method, provide authorization, test actual retrieval, preview reviews, enable the supported display/sync mode.
5. Configure the key privately under **Settings → Review connections**. Enable **Places API (New)** and billing in Google Cloud; restrict the key to that API and your server's outbound IP. Publish appropriate public terms/privacy, review regional agreement eligibility, and set project quotas. The site also caps request attempts per UTC day, default 100.
6. A successful response containing reviews validates the collection. An empty response, a saved URL, or a saved key alone does not mean reviews are connected.
7. Create a widget, choose its locations, customize, save and paste `[google_reviews_widget id="123"]` into a page. `[google_reviews_widget id="123" limit="20"]` reduces its saved display limit. A Places location still supplies at most five reviews.
8. Enable analytics only after configuring consent and privacy notices.

Navigation: **Dashboard, Locations, Reviews, Widgets, Analytics, Settings**. Connections, synchronization and diagnostics are secondary settings pages. Reviews shows live collections on request; retained legacy content has a separate opt-in view and its original provenance. Authentic text cannot be edited.

## Display and costs

Classic defaults follow the reference composition: light gray section, centered subtitle and bold title, rounded light cards, overlapping circular avatars, relative dates, gold stars, circular arrows, three/two/one cards, five-second autoplay, looping and swipe. Five review templates, independent multiple widgets, colors, typography, radius, spacing, arrows, animation, filtering and ordering remain customizable. Google author information, available credits, direct source links, visit month/year when supplied and Google Maps attribution stay visible. Google data is kept separate from legacy datasets. Places supports ordering/filtering of its selected subset, not discovery of the whole review history; saved-ID selection/featured order cannot apply to its ephemeral content.

Public empty widgets can be hidden or show a configured message. Preview traffic is excluded from analytics. Original review links, navigation, read-more, widget/location views and estimated session uniques preserve consent, DNT and GPC handling.

Places content is fetched through a signed, bounded public widget route. Cached pages hold only a placeholder; the content response uses `no-store`. Do not configure a CDN to override those headers or cache the POST REST response. Dynamic review images still contact their supplied provider hosts. JavaScript is required for live Places cards. Key changes invalidate cached placeholder tokens: purge your page cache after configuring or disconnecting credentials.

Google's current global list gives Place Details Enterprise + Atmosphere (reviews) a 1,000-event monthly free usage cap, then $25 per 1,000 in the first paid tier. Identity lookup requests rating/count, using Enterprise: 1,000-event cap, then $20 per 1,000 in the first paid tier. Business-name lookup from a Maps URL uses Text Search Enterprise: 1,000-event cap, then $35 per 1,000 in the first paid tier. Rates vary by region, billing agreement and volume. This is pay-as-you-go, not a required plugin subscription. Live widgets retrieve up to five locations per request; choose locations explicitly for larger sites. Multiple locations and previews consume additional calls; the site cap is a request-attempt ceiling, not a billing guarantee. See [Google pricing](https://developers.google.com/maps/billing-and-pricing/pricing).

## Daily synchronization and provider extensions

The hourly WP-Cron task processes up to five due locations, at most five pages per job, 500 rows per page. Stable source IDs deduplicate and update records; locks prevent overlap, errors retain existing data, retries back off, successful daily jobs schedule another run in 24 hours. On quiet cPanel sites, use a server cron job every five minutes to request your site's `wp-cron.php`, or use your host's PHP path to execute it. Keep the normal WordPress scheduler enabled unless you configured an equivalent real cron.

[Provider setup and policy contract](docs/PHASE4-PROVIDERS.md) describes `PermanentReviewProvider`. It declares access requirements, review bounds, ownership, daily capability, historical/new access, attribution and a license reference. Credentials and endpoint allowlists belong in trusted provider code. Time-limited feed licenses need an adapter with explicit expiry/purge handling; they must not claim permanent storage permission. A license URL or a provider's marketing claim is not itself proof of upstream publication rights.

Legacy local JSON adapters remain supported for existing installations, but are not offered as a Google review connection. Business Profile is an optional owner-only dashboard with approved-project/OAuth/managed verified-business requirements, up to 50 reviews per page and pagination. It does not publish public cards or run a misleading zero-review public sync.

## Upgrade and rollback

Back up the database and plugin directory. Upload this ZIP and choose **Replace current with uploaded**. Do not uninstall to upgrade. No locations, reviews, widgets, IDs, source provenance, styling, analytics or privacy settings are removed by the migration. Legacy map/grid/combined shortcodes and saved map templates still render, but new map controls/shortcode generators and testimonial/upload workflows are absent from the primary UI. Existing widgets keep their saved source settings; new widgets default to connected sources. Legacy widgets without an empty-state setting retain a displayed message.

Rollback: restore the previous plugin ZIP and the database backup if you made changes after upgrade. Existing legacy configuration remains compatible. New Places widgets are a new feature and cannot render through an older plugin; switch those widgets to an available legacy source or restore the backup. Changing WordPress authentication salts invalidates encrypted provider credentials and requires private reconfiguration. Uninstall always removes private credentials; data deletion otherwise remains opt-in. Deactivation preserves data.

## Development and evidence

- `tests/integration.php`: existing security, storage, moderation, analytics and adapter regression checks.
- `tests/phase3.php`: retained map/shortcode and migration compatibility.
- `tests/phase4.php`: synthetic provider transport, permissions, credential/URL safety, no storage, attribution, budgets, real cron path, dedupe, updates and empty states.
- `tests/browser.cjs`: retained carousel behavior and responsive interaction.
- `tests/phase4-browser.cjs`: simplified admin workflow and dynamic Places cards in Chromium, Firefox and WebKit. The isolated mu-plugin fixture is **never packaged**.
- `tests/upgrade.php`: exact persisted-data snapshots across the previous release; repeated migration idempotency.
- `tests/benchmark.php`: 10/100/1,000/10,000 stored-review datasets.

GitHub CI covers PHP 8.1–8.4, minimum/latest WordPress, MySQL/MariaDB, package installation, upgrade and three browser engines. [Historical Phase 3 evidence](docs/PHASE3-REPORT.md) describes the prior release, not the current primary UI.

No scraping, fabricated reviews, false verification badges or unverified claims of production synchronization are included.
