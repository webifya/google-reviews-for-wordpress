# Phase 2 implementation and verification report

Release: **1.1.0-rc.1**, deliberately a prerelease. Baseline: `36dea654964656a13868ffd09ae8ed594374bafd`. Stable 1.1.0 is withheld because a real approved Google project/verified business was unavailable and actual public HTTPS/Apache/cPanel hosting was not supplied. The owner requested fixture testing. No fixture result represents live Google access.

## Audit and changes

All PHP services, schema/lifecycle, REST routes, frontend/admin assets, Gutenberg/shortcodes, source contracts, tests, workflows, package inputs and original documentation were reviewed. The prioritized [audit ledger](AUDIT-PHASE2.md) records confirmed findings.

High-priority fixes: anonymous analytics collection now has a global fixed-minute write budget as well as strict per-token limits; nested input is handled safely; repeated preview replacements no longer retain frontend instances, timers or observers. These reduce abuse/resource exposure; anonymous analytics remain forgeable estimates.

Medium-priority fixes: continuous event activity flushes without starvation and drains batches beyond 30; navigation/outbound actions include interaction metrics; grids use the reachable viewport portion; duplicate session/widget mounts share client deduplication. Resize preserves current review and expanded clone state; motion preference changes update autoplay. Location metadata edits preserve status/schedule, while provider changes discard incompatible cursors. Normalized source metadata participates in update hashes, manual identities are stable, fractional counts become integers, invalid hours and nested fields are rejected. Import validates the location once per batch. Public review queries are cached and invalidated by imports, moderation and location edits.

Management and UI: distinct classic/minimal/dark/grid/compact compositions, small-screen spacing, configurable gestures/random start/full width/custom class; isolated draft previews with response sequencing, three editor panels, device toggles, five editable presets, undo/save-as-new; named location selection; bulk hide/show/feature/manual reassignment, visibility/date/source/search filters, original links/identity details, import history, widget deletion and protected location deletion. Safe official share redirects resolve metadata only. Diagnostic table scans are requested on the tools screen rather than every screen. Cache clearing now changes generations. Settings JSON export still includes widget records for reference but only privacy settings are restored—this is disclosed rather than implied as full restoration.

Dashboard: active widget counts, reviews/locations/views/interactions, quick actions and compact sync status. Analytics adds generic interactions, dot navigation, original/Maps outbound counts, exact reset confirmation and site-date ranges. Event-derived engagement can exceed 100%; per-location counts describe assigned widgets, not individual card visibility. No IP addresses are stored. Consent, DNT/GPC, session deduplication, aggregate retention and preview/admin exclusion remain.

## Actual source capabilities

See the detailed [comparison](SOURCES.md) and [private setup guide](GOOGLE-SETUP.md). Optional Google Business Profile code supports OAuth, account/location selection, manually confirmed owned identity, up to 50 owner reviews per page, on-demand pagination, encrypted tokens, a 15-minute raw response cache, refresh/reconnect errors and disconnect. Scheduled refresh fetches the first page only. Google content never enters the public review table or export. OAuth/API access was mocked, not live-tested.

Authorized CSV/JSON import, manual testimonials, authorized local JSON feed and licensed extension adapters remain. Local JSON and qualifying adapters can update/deduplicate on 12h/24h/48h/weekly/manual schedules; five pages/job continue from saved cursors. Actual cron-hook fixtures verify due updates and pagination continuation. Temporary failure retains saved data, locks prevent overlap and retries are bounded. Public rendering does not fetch reviews remotely. Official map embeds retain Google's content/presentation. There is no turnkey key-free public Google URL → 100-review carousel solution in this release.

## Tests and security

- Local WordPress 7.1.3 / PHP 8.4.23 / official SQLite integration: **101 integration assertions**, including the final index migration.
- Browser suites: Chromium, Firefox and WebKit. Expanded checks cover 320/375/768/1024/1440, independent instances, loops, autoplay/motion, touch/drag, keyboard status, inline/modal/empty states, clone expansion after resize, instance disposal, disabled controls, every custom card template and bounded continuous analytics batching. Screenshots are saved for all engines and templates. 39 checks per engine passed locally (117 total), with no JavaScript errors.
- Admin: all eight screens, saved/live styling, nonce/capability denial, real consent-driven viewport analytics, rapid-scroll deduplication and guided onboarding. 40 admin checks passed in the first expanded run. Additional checks verify device viewport widths, presets, save-as-new/reset/undo, hidden sync settings, add/delete location, owner-only setup guidance, bulk hide/filter and real clipboard copying.
- Actual **Plugins → Add New → Upload Plugin** clean installation and **Replace current with uploaded** RC1 upgrade were executed. A full persisted snapshot compares locations, reviews, widget configurations/styling, analytics, logs and privacy settings; repeated migration is also checked.
- Security regressions: foreign analytics origins, malformed events/tokens/settings/review fields, rate overshoot/token rotation budgets, SQL-safe searches/IDs, escaped HTML/CSS and CSV formulas, anonymous/nonce-free mutation denial, local-feed traversal, unsafe URL schemes/hosts/credentials, Google host/resource allowlists, foreign share redirects, OAuth HTTPS requirement, credential encryption/redaction, protected source content/reassignment and confirmed reset/deletion.
- No executable file upload/deserialization pathway is introduced. JSON feeds are bounded local files within uploads; unknown network destinations cannot be chosen through location fields. OAuth state is random, expires and binds to the administrator; its live redirect/token exchange remains unverified. This is an application review and regression suite, not an independent penetration-test certification.

## Performance

Synthetic, visibly labeled Unicode fixtures only. macOS, PHP 8.4.23, WordPress 7.1.3, official SQLite/MySQL compatibility layer; one measurement per dataset, warm application process, no load/concurrency simulation. Rendering uses 100 matching reviews; admin page uses 50. These are observations, not shared-hosting performance guarantees. See `benchmarks-phase2.json` for final timings, query counts, HTML size and process memory. CI also collects native MySQL/MariaDB benchmark artifacts.


| Stored reviews | Cold query ms | Cached query ms | Render ≤100 ms | Admin 50 ms |
|---:|---:|---:|---:|---:|
| 10 | 3.4 | 0.89 | 2 | 0.75 |
| 100 | 4.9 | 1.51 | 4.26 | 1.14 |
| 1,000 | 5.43 | 1.99 | 4.6 | 2.48 |
| 10,000 | 4.43 | 1.29 | 3.96 | 19.82 |

Peak total PHP process memory was 42.5 MiB. A 100-card response was approximately 96 KB before browser loop clones.

Storage has no plugin count ceiling. Display defaults to 100, accepts custom 1–10,000, and large HTML/loop clones can consume substantial browser/server memory; use modest output limits rather than displaying all stored reviews. Search with partial text and random ordering can be costly. Query-cache invalidation is immediate via generations, with abandoned entries expiring after five minutes. Added indexes support public/location/source date ordering.

## Compatibility and limits

Configured CI covers PHP 8.1/8.2/8.3/8.4 × WordPress 6.4.7/current × MySQL 8/MariaDB 10.11, plus browser CI. Executed run status and URL are added below. Native Node is never needed on production hosting; CSS/JS ship in the ZIP. Local PHP server tests use query-string REST routes. Apache rewrites, actual public HTTPS, host filesystem policies and cPanel cron require host-specific validation; they were not simulated as successful deployment. Multisite network activation/cleanup remains unsupported; configure per site. Google approval/ownership/quota/token lifetime remains external. A shared OAuth project/indirect client API service is not offered.

Other limits: full widget backup restoration is not implemented; browser-side very large CSV export uses memory; source review removal does not automatically delete imports; WP-Cron timing depends on traffic or server cron; analytics collection can be forged by clients and the global budget may drop events on very busy sites. Cached pages first built while logged in may omit tracking until cache rebuild. Missing avatars use initials; no Google badge, reviewer identity, star, date or verification status is fabricated. The Google owner UI is read-only; it does not post replies or edit listings.

## Installation and upgrade

Download `google-reviews-for-wordpress-v1.1.0.zip`, back up the database, upload under **Plugins → Add New → Upload Plugin**, activate for a fresh installation or choose **Replace current with uploaded** for RC1. Do not uninstall during upgrade. Add confirmed business details, choose the actual permitted source, import licensed content or maintain an authorized feed, create/style/save a widget and paste its shortcode. Optional Google owner connection is separately documented and requires public HTTPS plus eligible authorization. Configure consent before analytics/external embeds. For reliable cPanel scheduling, test a five-minute PHP job against `wp-cron.php`, then disable visitor-triggered WP-Cron. Hosting paths differ; use your host's PHP path.

## Delivery evidence

Final commit, CI totals, remote-main verification, tag, release URL, asset URL and downloaded ZIP SHA-256 are appended after remote verification. No stable release claim is made.
