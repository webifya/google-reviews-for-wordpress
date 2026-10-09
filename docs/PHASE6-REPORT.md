# Phase 6 delivery — Google Reviews for WordPress 1.4.0-rc.1

**Release candidate for permitted local storage and three-day scheduling. The primary non-owned Google listing → authentic reviews → licensed local archive requirement remains blocked by real provider access and storage/display rights.** No genuine Menjar reviews, 100+ licensed collection or full 72-hour production cycle are claimed. All provider tests and review screenshots are explicitly synthetic.

## 1. Existing code audit

Fetched remote/main before edits: `3eaa8c47c7f58ceaa7718ce9c5ada5b3c92b2092`, verified public v1.3.0-rc.1. Reviewed schema, adapters, cron, sync, storage/query/rendering, shortcodes, analytics, admin, tests, README/changelog and Phase 4 evidence. Phase 5 was interrupted before commit/push/release; its local backend checks and research/reliability improvements were preserved and incorporated. No v1.3.1 release was published. [Pre-implementation audit](PHASE6-AUDIT.md), [interrupted Phase 5 record](PHASE5-REPORT.md).

The existing PHP/WordPress services already supported stored records, locks, pagination, stable IDs, local frontend, multiple locations, carousel defaults and privacy-aware analytics. They were extended rather than duplicated or rebuilt.

## 2. Implemented changes

New locations default to 259,200 seconds. Add 1/3/5/7-day/manual schedule choices and preserve saved intervals. New licensed sources are unscheduled until retrieval validates the complete collection. Schedule-only edits preserve a valid connection. Accurate retrieved/saved counts and manual/daily/three-day/other schedule labels replace misleading daily status.

Add provider ID, business binding, license reference and `synced_at` columns plus indexes, with empty/null defaults for old rows. Stable external identities are scoped to provider/business; content edits keep the same ID. Unchanged checks update last-sync time in bounded batches without pretending content changed. A source/business/license/permission change hides stale licensed data from public/cache paths without deleting retained records.

Validate full pages and bounded stable IDs/cursors, reject cyclic pagination, resume cumulative fetched/new/updated/unchanged/page/duration statistics and grant new collections only after completion. Accept explicit `removed_ids` only for a provider declaring a reviewed deletion capability; deletion is limited to its matching collection. Use safe fixed errors and retain locks/backoff.

Connection testing uses the same complete safe sync, rather than a separate unchecked provider fetch. Locally permitted collections render without upstream requests. No new real vendor adapter is installed or misrepresented as live.

## 3. Simplified administration

Primary navigation stays Dashboard, Locations, Reviews, Widgets, Analytics, Settings. Manual import/testimonial screens, map builders and map shortcode generation remain absent. Existing legacy records/API compatibility are preserved.

The URL/Place-ID input and Find Business stay primary; stored Place ID/original URL are in Advanced. Retrieved name/address/link are displayed for confirmation without permanently storing transient Google lookup content. Location cards show provider/status, accessible and saved counts, storage category, last retrieval, next schedule, Sync Now and Sync settings for validated stored sources. Saving identity does not claim retrieval.

Dashboard shows connected locations, saved reviews, active widgets, total widget views and sync dates. Sync Now reports new/updated/no-new/continuing accurately. Authentic original review text remains read-only; licensed stored records can be hidden/featured through existing controls.

## 4. Actual source support

Official Places API (New) remains implemented for eligible public/non-owned listing live display: at most five selected reviews, server key/billing/applicable agreement and attribution required. Review text is never archived. Existing GBP owner tool needs approved project/OAuth/managed verified business; it is not a non-owned public archive.

The trusted permanent-license adapter contract supports permitted local collections and three-day jobs. Its software path was exercised with fixtures only. No usable licensed vendor account/contract was supplied. [Current source decision](PHASE6-SOURCES.md), [Google/nine-vendor comparison and costs](PHASE5-PROVIDER-COMPARISON.md).

## 5. API-free feasibility

No reviewed permitted API-free Maps extraction method satisfies individual non-owned review access, >5/history, reliable new-post discovery, local storage, commercial custom rendering and ordinary cPanel reliability together. A public URL is an identifier, not authorization. A documented vendor JSON feed is a technical lead but still needs account entitlement and rights; widget marketing is insufficient. No HTML scraper, private endpoint, CAPTCHA/proxy bypass, impersonation or private-plugin extraction was implemented. [Eight required feasibility answers with dated official sources](PHASE6-SOURCES.md).

## 6. Storage and retention permissions

Stored rows retain original author/avatar/rating/date/text/link/response, visibility, import/content-update/sync timestamps, hash and source/license provenance. Database storage has no fixed total review/location cap. Queries and jobs are bounded; widget display is configurable up to 10,000 and defaults to 100.

Literal permanent storage/public display grants and an HTTPS license reference are required for the supported permanent contract. A code declaration is not a legal verification service. Unsupported temporary/revoked grants cannot initiate retrieval or populate connected/public cards. Explicit documented deletion signals are honored and isolated. Generic time-limited TTL/purge is **not implemented**; a specific eligible vendor adapter must supply it before use. Hidden retained data is not a compliance purge. No prohibited Places archive was created; GBP retains its separate short owner cache.

## 7. Initial retrieval

Identity is saved first; provider authorization and actual complete review-bearing response validate a collection. Page validation precedes import, stable IDs prevent duplicates, optional metadata is not fabricated and empty/failed responses do not claim Connected. Bounded pagination can continue on another test/cron job; partial new collections remain unvalidated. Full remote business identity/rights enforcement belongs to the actual trusted connector, which remains unavailable for the requested live feed.

## 8. Three-day synchronization

Existing hourly WP-Cron dispatcher handles up to five due locations; per-location lock, at most five pages/job, 500 rows/page, resumable cursor, bounded retries and statistics. Successful jobs schedule their selected interval, default 72 hours. No early rerun or duplicate tick; manual only disables periodic checks. Initial large collection continuation can schedule bounded follow-up work until complete.

Configured schedule status does not prove an elapsed three-day upstream cycle. Due-job execution was tested with fixtures; genuine production synchronization remains unverified. [Schedule/local storage/cPanel guide](PHASE6-SYNCHRONIZATION.md) includes a five-minute host cron trigger and keeps production dependencies to PHP/WordPress/database.

## 9. Deduplication, changes and removals

Verified stable-ID repeated refresh, unchanged/updated distinction, same identity after text change, last-sync timestamp without fake update, different-business isolation, license change/cache revocation, malformed page/cursor rejection and explicit removal scoped to the selected provider/business. Legacy conservative fallback behavior remains; permanent adapters require bounded stable IDs instead of guessing identity from similar authentic text. The primary workflow never creates missing historical reviews.

## 10. Shortcodes

`[google_reviews_widget id="123"]` and `[google_reviews_widget id="123" limit="20"]` remain. Default 100/newest and independent selected locations/styles are preserved. Tests render 100 newest stored fixtures and a 20-card override, paginate 50-row admin queries and verify a published real WordPress page with six stored fixture reviews and no provider request. Six available records remain six, not a fabricated hundred.

Legacy map/grid/combined shortcode APIs remain compatible during upgrade, while their creation UI stays retired. Places is explicitly the live-only exception and retains its request scope/selection bounds.

## 11. Carousel

Retain reference composition, gray section, subtitle/bold heading, rounded light cards, overlapping circular avatars/initials, names, relative dates, gold stars, read-more/read-less, source/original link, arrows, responsive 3/2/1, 5-second autoplay, loop, hover pause, swipe/keyboard and reduced motion. All five designs and saved custom styles are preserved. Actual card count follows available widget width; a narrow theme content column cannot fit a full-width three-card section.

Existing behavioral/browser checks cover responsive widths, unclipped avatars, independent widgets, autoplay, loop, pointer/touch/keyboard, resize/expanded state, modal/empty/disabled controls, templates and cleanup. No unnecessary carousel rewrite or fake verification badge was added.

## 12. Analytics

Fresh installations enable analytics with explicit consent required; upgrades preserve existing opt-outs/retention/settings. Widget IDs and analytics relationships are unchanged. Existing tests cover impressions, expiring estimated unique sessions, read-more/navigation/original links, per-widget/location/day/month reporting, administrator/preview exclusion, DNT/GPC/consent and bounded event budgets. New sync and upgrade checks preserve saved analytics exactly. Benchmarks perform actual three-event fixture writes rather than timing an empty no-op.

## 13. Performance

Synthetic storage datasets 10/100/1,000/10,000 are imported in batches no larger than 500. Each render is asserted to produce actual cards; display limit is 100. The illustrative native MySQL run below uses PHP 8.4/WordPress 7.1.3, one measurement per size; it is not a hosting/provider/network guarantee.

| Stored reviews | Cold query ms | Cached query ms | Render 100 ms | Admin 50 ms | Last import batch ms | Three analytics events ms | Peak PHP MiB |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 100 | 4.58 | 1.3 | 6.81 | 0.36 | 120.39 | 9.56 | 40.0 |
| 1000 | 7.01 | 1.52 | 7.03 | 0.91 | 505.19 | 8.85 | 40.0 |
| 10000 | 13.41 | 3.38 | 17.28 | 33.24 | 1169.69 | 14.56 | 40.0 |

The local SQLite compatibility run at 10,000 rows rendered 100 cards in 5.14 ms with peak PHP memory 44.5 MiB. Native MySQL/MariaDB benchmark JSONs are supplied. Database-server memory attribution was not instrumented and is explicitly null, not mislabeled as PHP memory. Import-batch timing excludes real provider HTTP time; full production sync timing remains unverified.

## 14. Security

Retain administrator capabilities/nonces, prepared scoped queries, escaped output, typed scalar review metadata, HTTPS host allowlists, safe Maps URL/redirect handling, SSRF protection, timeouts/response-size bounds, encrypted private credentials, no keys in public state/export/HTML/logs, per-location locks and rate-limited signed live/analytics routes. New connection testing cannot bypass storage eligibility; external IDs/removal batches are bounded and scope-specific. Raw provider bodies/error codes are never substituted into safe errors.

Whole-page validation is not a promise of an all-or-nothing multi-page database transaction. Stable IDs make retries idempotent. Secrets were not requested in chat or put into packages. Test keys/content exist only in explicitly synthetic development files excluded from the ZIP.

## 15. Automated tests and compatibility

| Category | Executed evidence |
| --- | --- |
| Unit/static | No separate isolated unit framework. PHP lint, JS syntax, package structure and diff checks pass. |
| WordPress integration | Local 101 existing + 43 Phase 3 + 100 Phase 4 + 40 Phase 5 + 46 Phase 6 = **330 assertions**. |
| Synthetic provider | 100/40/46 phase assertions are subsets of the 330, not extra live passes. Include scale 1/5/10/25, paging, fields, permissions, auth/quota/rate/network, locks/backoff, removals and local cache. |
| Genuine provider | **Blocked. Zero genuine review retrieval requests**; no verified Menjar collection/count. |
| Scheduled synchronization | Real WordPress cron hook/dispatcher exercised with fixtures, 72h next calculation/no early run/locks; **no full real 72-hour cycle**. |
| Browser/visual | Chromium/Firefox/WebKit: 39 retained carousel + 92 existing workflow + 34 new Phase 6 per engine = **495 assertions**, zero page errors in completed runs. |

Native matrix: PHP 8.1–8.4 × WordPress 6.4.7/latest × MySQL 8/MariaDB 10.11, 16 combinations, **5,280 backend assertions** in total plus package installation, actual old-schema replacement and migration idempotence. Three-browser job verifies the packaged isolated site. Final GitHub completion evidence is recorded below after verification.

A Firefox test setup interrupted WordPress dashboard requests immediately after login; it was corrected to redirect/wait for the plugin page. The error assertion remains enforced; no failing checks were suppressed.

## 16. Real WordPress installation and upgrade

Native WordPress Upload Plugin installed and activated the final ZIP on an isolated fresh site. Recreated actual v1.3.0-rc.1 tables with none of the four new columns, seeded styled widgets/reviews/locations/analytics/settings/shortcode pages, uploaded/replaced through WordPress and verified every old field/ID/value unchanged. Added fields have only empty/null defaults; repeated migration and original shortcode rendering pass. All five tables, history, styling, analytics and settings are preserved. Backups/replace-not-uninstall/rollback instructions are documented.

The same installable ZIP matches the successful native CI package byte for byte. Package contains **25 production files, 81,293 bytes**; no tests, fixtures, private config, screenshots or development runtimes. SHA-256: `af010069537359bbc7065ff9e03c32c4fe2a684d66e8aa2438ecd2c9de60b2cd`.

## 17. Remaining limitations

Main live acceptance remains blocked: no real eligible key/account, no explicit non-owned local-storage/public-display license, no actual business identity/review fields/count, no genuine historical discovery/two timed fetches and no full production 72-hour cycle. No newly researched vendor connector was invented. Places still returns at most five selections and does not maintain an archive. Business Profile still requires managed ownership authorization. Temporary licensed sources need their own expiry implementation. Host cron timing and database-server memory attribution are not proven by synthetic tests.

## 18. Necessary owner configuration

For the limited fallback, privately configure Places API (New), billing/restricted key, applicable agreement/notices and project/site quotas in WordPress; explicitly approve any billable test ceiling before requests. For the central goal, obtain a usable documented vendor account/endpoint plus written permission for this non-owned listing, authentic/history/count, storage, commercial custom rendering, 72h refresh, attribution and retention/deletion rules. Resolve any contractor restrictions. Then implement/live-test the actual connector against Menjar Financial, compare metadata to its supported source, fetch twice over time and observe a real 72-hour cycle. Do not paste secrets in chat. No paid plan or vendor message was sent during this work.

## 19. GitHub commit

Tested implementation: `71a99c2b0787d8c938691709a2ec1fc5b3fb32fe`. Final test setup fix: `194b14949e3444f14e375d2139866da91a3482e6`. Runtime/package content is identical between these; the latter added login settling. Final fully successful compatibility/diagnostic test commit: `b0b07484e2197c6cf524341aca69c1dea105f9d8`. The release target adds this documentation-only report after successful verification; its exact remote SHA is recorded in the delivered report and public release metadata.

## 20. Remote and test verification

Implementation and test commits pushed to `main`, with `git ls-remote` matching the local full SHA. Follow-up run [37910543963](https://github.com/webifya/google-reviews-for-wordpress/actions/runs/37910543963) completed **all 17 jobs successfully**, including all 16 native combinations and the three-browser suite. Earlier run 37862350198 passed the native matrix but timed out waiting for an iframe preview in Firefox; diagnostic logging was added and the identical runtime passed the complete follow-up. The intermittent preview timeout was not reproduced; no behavioral assertion was removed. Release-build artifact and unauthenticated public ZIP verification are recorded in the final delivered verification section. Old tags/releases are preserved; this is a prerelease candidate, not stable completion of live integration.

## 21. Release and next action

Candidate [v1.4.0-rc.1](https://github.com/webifya/google-reviews-for-wordpress/releases/tag/v1.4.0-rc.1), [installation ZIP](https://github.com/webifya/google-reviews-for-wordpress/releases/download/v1.4.0-rc.1/google-reviews-for-wordpress-v1.4.0.zip). Public availability/download verification is recorded only after actual publication. Owner action is the account/rights/budget gate in section 18. All independently testable work is delivered while the live collection gate stays visibly blocked.
