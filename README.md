# Google Reviews for WordPress

**1.4.1-rc.1 — setup simplification candidate. Direct API-free Google review downloading remains blocked.**

The primary admin no longer asks for Google Cloud, API keys, billing or a Google account. Add a Google Maps URL or Place ID, validate its syntax locally, open the listing to confirm the business and enter your own label/address. This identifies your saved listing; it does not download business metadata or reviews.

No documented permitted API-free feed was established for all public/non-owned Google reviews and an independently stored custom carousel. A public URL is not an export license. No scraper, private endpoint, fake Download button or synthetic customer claim is installed. [Dated official-source investigation](docs/PHASE7-ACCESS-FINDINGS.md).

## Install and use

1. WordPress 6.4+, PHP 8.1+, MySQL/MariaDB. Back up your database/files, then Upload Plugin and activate or replace the existing candidate. Do not uninstall to upgrade.
2. Open **Google Reviews → Locations → Add Location**. Enter a Maps URL or Place ID, use **Check listing link**, confirm the business on Google Maps and supply your own business label/address. Save. Retrieval status remains unavailable unless an eligible documented source is installed.
3. A trusted installed adapter with explicit permanent-storage/public-display rights can be selected under Advanced. **Download available reviews** runs the existing bounded validated sync; partial continuation is labeled accordingly. Sync Now and schedule settings appear for validated stored sources. This is a connector contract, not a bundled live Google feed.
4. Create/configure a widget and publish `[google_reviews_widget id="123"]`. Existing shortcodes, IDs, saved styles and templates remain compatible. A limit override remains `[google_reviews_widget id="123" limit="20"]`.
5. New widgets default to 100/newest, autoplay every 5 seconds, looping, hover pause and 3/2/1 responsive cards. Only actual available permitted records render; six records do not become 100.

## Storage and synchronization

The existing five plugin tables are reused. Stable provider/business IDs, content hashes, grant/license scope, sync timestamps, bounded pagination and explicit permitted deletion handling are retained. No schema change or rewriting of existing records is required in Phase 7. Listing totals and remotely accessible totals are unknown unless supported data is actually available; they remain separate from stored/displayable counts.

Validated permitted sources use per-location 1/3/5/7-day/manual schedules, default 72 hours. Locks, retries and cache invalidation are retained. Temporary connection failures preserve previously permitted content and show an attention status. Grant revocation/business/license changes continue to fail closed. Time-limited sources require their own reviewed expiry/purge adapter before use; hiding rows is not a retention purge. [Scheduler/cPanel guide](docs/PHASE6-SYNCHRONIZATION.md).

## Compatibility and privacy

Already configured Places live widgets retain their original live-only path and encrypted credentials. They still require their existing account entitlement and applicable agreement and can return at most five selected reviews. Existing owner integration backend/callbacks remain for compatibility. New primary/secondary admin offers no Google key/OAuth setup. Credentials are retained encrypted until explicit authorized disconnect or uninstall with saved delete-data preference; they are never returned in public/admin exports, HTML or diagnostics. No Google settings are silently deleted or converted into local archives.

Historical reviews, widgets, analytics, privacy opt-outs and map/grid/combined legacy shortcodes remain unchanged. Primary navigation is Dashboard, Locations, Reviews, Widgets, Analytics, Settings. Manual/CSV/JSON review imports, map builders and map-only shortcode publishing remain absent from the normal UI. Retained testimonials/imports keep explicit original provenance.

Fresh-install analytics is enabled with explicit consent required; existing privacy preferences are preserved. Configure your consent manager with `window.grwAnalyticsConsent = true` or `new CustomEvent('grw:consent',{detail:true})`; revoke with detail false. DNT/GPC and admin/preview exclusion remain. Estimates use expiring session tokens, not stored IP addresses. Reviewer images may load from supplied provider URLs; include appropriate public notices.

## Development and verification

Run PHP lint, `npm run check`, the WordPress integration/Phase 3–7 suites, native MySQL/MariaDB CI matrix and Chromium/Firefox/WebKit suites. Tests use clearly synthetic providers and isolated fixture transport. No live Google collection or elapsed 72-hour production cycle is claimed. ZIPs exclude tests, fixtures, private configuration, docs and runtimes. [Starting audit](docs/PHASE7-AUDIT.md), [access findings](docs/PHASE7-ACCESS-FINDINGS.md), [adapter contract](docs/ADAPTERS.md). Prior-phase reports are historical evidence, not current setup instructions.
