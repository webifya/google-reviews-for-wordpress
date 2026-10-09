# Phase 7 — Google Reviews for WordPress 1.4.1-rc.1

**Direct API-free Google review collection is blocked. This candidate delivers the requested safe admin simplification and preserves existing carousels, local data and legacy live widgets. It does not claim a new operational Google downloader.**

## 1. Genuine API-free collection

No reviewed documented, reliable, permitted direct public/non-owned Google Maps review feed satisfies the requested URL → authentic individual review archive → independent local WordPress carousel. Local URL/Place ID validation is implemented without HTTP; it does not verify a business or retrieve reviews. The administrator confirms identity externally and enters their own label/address. Short share links may need a full listing URL; they are not scraped or followed by this new flow.

## 2. Extraction, storage and publication permission

Reviewed Google Maps end-user terms §2, linked geo permissions, Maps Platform terms §3.2.3, Places policies/schema and Business Profile docs. The end-user terms restrict copying, redistribution and bulk downloading subject to their stated exceptions. Platform customer terms separately restrict extracting/storing user reviews outside the services. No applicable independent-archive exception or written agreement was established. Attribution and public visibility alone are insufficient. [Dated official sources and eight-question findings](PHASE7-ACCESS-FINDINGS.md).

## 3. All reviews and available counts

No authentic archive was collected and no historical completeness can be claimed. Places' documented five-selected-review limit is retained only in legacy configured widgets. Managed GBP pagination is not an API-free non-owned source. No private endpoints, reverse engineering, CAPTCHA/proxy bypass, bulk extractor or synthetic review download button was added.

Location cards explicitly separate listing-advertised total (**unknown**), remotely accessible source total (**unknown** unless an actual legacy selection has been validated), downloaded/stored rows and locally displayable rows. Displayable local count respects published state and current licensed scope; it is not the full remotely accessible history or actual impression count. Retained legacy rows retain original provenance. Source completeness cannot be inferred from a database count or a listing total.

## 4. Real 72-hour synchronization

No operational genuine API-free source or elapsed 72-hour live cycle was verified. The existing scheduler runs only the eligible provider contract with reviewed storage/display grants: default 72 hours, independent 1/3/5/7-day/manual schedules, bounded pages/locks/backoff and cache invalidation. Source-specific incremental behavior belongs to an actual connector; the plugin does not invent a delta feed.

New eligible collections stay unscheduled until complete successful review-bearing retrieval. Temporary network/rate/quota failures keep previously validated, permitted stored collections visible with an attention status. Empty/invalid responses do not validate access. License/business changes fail closed. The existing local tables, stable IDs and explicit provider-authorized deletion mechanism are reused. Time-limited sources require a vendor-specific expiry/purge implementation before use. [Existing cPanel/WP-Cron guide](PHASE6-SYNCHRONIZATION.md).

## 5. Authentic live test result

**Zero genuine review retrieval requests and zero authentic reviews downloaded during Phase 7.** Actual available business review count remains unknown; this does not mean the business has zero reviews. All test data and review screenshots are explicitly synthetic. No credentials/account/contract were supplied, no vendor was purchased or contacted, and no billable Google retrieval was attempted.

## 6. Source limitations and compatibility

No new vendor adapter is installed. A trusted adapter declaration is not proof of legal authorization; a usable account, documented endpoints and reviewed rights must precede genuine use. Existing Places API backend remains for already configured widgets: live-only selected reviews, encrypted saved key, applicable existing account/rights and attribution. Legacy Business Profile backend/callbacks remain for compatibility and do not supply public cards. Retaining those paths avoids breaking old widgets; new primary and secondary admin do not offer Google credential, billing or OAuth setup.

## 7. Removed and simplified features

Removed the six-step Google-default connection wizard, key setup form, Cloud/billing instructions, account/OAuth prompts and API-based Find Business from administration. New Check listing link validates only supplied syntax locally. Saving a new listing no longer opens a Google setup wizard. Primary navigation remains Dashboard, Locations, Reviews, Widgets, Analytics, Settings. Secondary Source availability explains the blocker and lists only installed permitted provider options; no secret setup controls.

Reviews defaults to current licensed stored collections. A separate retained-content view keeps legacy/testimonial provenance explicit. Manual/CSV/JSON review creation, map builders and map-only shortcode generation remain absent. No map iframe replaces review cards. Download available reviews appears only for an eligible installed permanent-license backend; validated collections offer Sync Now and schedules. No built-in unsupported public listing exposes those actions. No available subset is labeled All Reviews.

## 8. Database and credential migration

No Phase 7 schema change or mass record rewrite. Existing five plugin tables, identities, hashes, timestamps, grant/binding/license metadata, IDs and configuration are preserved. Installer updates its version marker and keeps saved settings with add-option behavior. Encrypted Places/OAuth options are retained byte for byte; they are never exported as plaintext or displayed in the new UI. They remain until explicit authorized disconnect through the existing authenticated compatibility endpoint or uninstall with the saved explicit delete-data preference. Default uninstall retention and deactivation preservation remain.

Native WordPress fresh Upload Plugin and actual v1.4.0-rc.1 replacement results, complete field/ID/style/analytics/settings/shortcode/credential snapshot and repeat-migration checks are recorded in the verification appendix. Backup and replace the package; do not uninstall to upgrade. Roll back by restoring both matching files and database backup rather than assuming file-only rollback reverses migrations.

## 9. Shortcodes and carousel compatibility

Preserve `[google_reviews_widget id="123"]`, its `limit="20"` override, existing widget IDs, custom colors, five templates, avatars, gold stars, dates, expansion, arrows, autoplay/loop/hover pause, responsive layouts and analytics. Default 100/newest, 5 seconds, 3/2/1 cards remains. Available actual records bound the displayed count. Legacy map/grid/combined shortcode APIs remain compatible; their publishing UI stays retired. Frontend/renderer assets were not rewritten.

Existing live Places widgets retain their saved provider and render path. The updated legacy browser regression configures only an isolated synthetic transport through authenticated compatibility REST, exercises live markup/attribution/failure states and preserves public security assertions. This verifies backwards-compatible software behavior, not genuine Google access. Retired wizard selectors were replaced with tests for the new intentional UI.

## 10. Analytics preservation

Upgrade snapshot verifies historical analytics and privacy settings unchanged. Existing integration/browser suites retain consent/DNT/GPC, admin/preview exclusion, impressions, expiring estimated unique sessions, navigation/expansion/original links and widget/location aggregation checks. Fresh-install defaults remain enabled with explicit consent required; existing opt-outs and retention remain. No analytics reset or global identifier/session change is introduced.

## 11. Security, performance and testing

New administrator-only identifier route uses the existing capability/nonces and scalar payload validation. The audit found and fixed an uppercase-host path-check bypass; its regression assertion passes. Local identifier checks reject non-HTTPS URLs, unsupported hosts/paths, IPs, credentials, custom ports, script markup, empty/oversized inputs and make zero HTTP requests. Existing host allowlists/safe redirect/SSRF, prepared SQL, sanitized content/escaped rendering, locked/bounded cron, signed public live routes and rate-limited analytics protections are retained. Raw provider errors and credentials remain excluded from state/diagnostics.

Counts use grouped database queries rather than loading thousands of review bodies. Existing 50-row admin queries and 100-card default renders remain. Synthetic benchmarks import 10/100/1,000/10,000 records in bounded batches and assert actual renders and three-event analytics writes. Individual measurements are not hosting/network/provider performance guarantees; database-server memory attribution remains uninstrumented/null.

Automated results will list exact completed local and CI counts, PHP/WordPress/database combinations and browser engines in the verification appendix. There is no separate isolated unit-test framework; the executed suites are WordPress integration/security/provider/browser tests plus PHP/JS/package checks. Fixture tests do not satisfy real retrieval acceptance.

## 12. GitHub commit and remote verification

Starting verified main/release: `a62a9b991f6c8df8665d3b39abf8b885eb2b2d64`. Phase 7 implementation: `37f6929184fd8661f0417e8fa606fb58eaa9a9de`. URL validation fix/tested runtime: `8ae67a44b9b0ae29177d99a2b6c10e39104469ba`. Exact final tested and release SHAs, remote ref comparison and job URLs are recorded after their checks complete. [Starting audit](PHASE7-AUDIT.md). Existing tags/releases are preserved.

## 13. Verified release ZIP

The candidate is labeled **administration simplification**, not automatic API-free Google retrieval or stable readiness. Final build/public download/size/production-file count/checksum and artifact links are recorded only after verification. No tests, fixtures, credentials, private WordPress configuration or development runtimes are packaged.

## 14. Exact remaining blockers

The user wants to avoid Google API accounts and billing. No permitted direct alternative was established. Fulfilling authentic URL → full accessible reviews → authorized local storage → real recurring collection therefore needs a separately usable documented provider/account/endpoint and explicit authorization covering this non-owned business, history/access/count, stable IDs, incremental updates/deletions, retention, independent commercial custom display and attribution. A paid widget or undocumented endpoint alone does not satisfy that gate. Without changing that constraint or obtaining an applicable written authorization/source, direct downloading remains unavailable.

Genuine business/review identity, live authentic count, complete accessible history, two timed retrievals and an elapsed three-day production cycle remain unverified. The independent UI/preservation improvements are delivered without substituting another unrelated feature or claiming the principal collection requirement complete.

## Completed verification appendix

- Local WordPress 7.1.3 / PHP 8.4.23 / SQLite compatibility: **373 backend assertions** (101 existing integration, 43 Phase 3, 100 Phase 4, 40 Phase 5, 46 Phase 6, 43 Phase 7). PHP lint, JS syntax and package checks pass.
- Local Chromium/Firefox/WebKit: **576 browser assertions** (39 carousel, 86 updated legacy compatibility, 34 local storage/schedule, 33 new setup per engine), zero page errors. The backend rerun cleared local browser seed content, causing a WebKit retained-content fixture check to fail; reseeding and rerunning the unchanged suite passed. Tests were not suppressed.
- [Final CI run 37916095932](https://github.com/webifya/google-reviews-for-wordpress/actions/runs/37916095932) completed successfully on `8ae67a44b9b0ae29177d99a2b6c10e39104469ba`: **all 17 jobs passed**, PHP 8.1–8.4 × WordPress 6.4.7/latest 7.1.3 × MySQL 8/MariaDB 10.11. **5,968 native backend assertions** plus installation/upgrade/repeat-migration checks. The downloaded CI browser artifact independently confirms **576 assertions and zero page errors** across all three engines.
- Native WordPress Upload Plugin fresh install and actual v1.4.0-rc.1 replacement pass on the final ZIP. Snapshot verifies all five tables, every original field/ID, settings, styling, analytics, shortcode pages and encrypted Google options unchanged. No new schema columns. Repeated migration remains idempotent.
- Final installable ZIP: **25 production files, 78,631 bytes**; excludes tests/fixtures, private WordPress configuration, snapshots, docs/screenshots and runtimes. Matches both final native MySQL/MariaDB CI packages byte for byte. SHA-256: `42d722b5db8e8a6e25dc3bbe33d8facd785e527676ee102a1bfededa2d740779`.

Illustrative final native MySQL benchmark: PHP 8.4.26 / WordPress 7.1.3 / MySQL 8.0.46, one synthetic measurement per size; display limit 100. Maximum import batch 500. Database-server memory attribution was not instrumented.

| Stored rows | Cold query ms | Cached query ms | Render 100 ms | Admin 50 ms | Last import batch ms | Three analytics events ms | Peak PHP MiB |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 10 | 2.77 | 0.9 | 2.37 | 0.29 | 14.06 | 10.29 | 38.0 |
| 100 | 4.61 | 1.31 | 7.19 | 0.43 | 104.31 | 9.46 | 40.0 |
| 1000 | 7.59 | 1.62 | 7.63 | 0.95 | 493.52 | 9.56 | 40.0 |
| 10000 | 5.21 | 0.94 | 6.72 | 10.34 | 562.41 | 9.41 | 40.0 |

At 10,000 stored rows, the final native MariaDB 10.11.19 run rendered 100 cards in 9.40 ms (admin 50 query 12.11 ms); local SQLite rendered them in 5.04 ms. JSON evidence includes all dataset sizes and environment/limits. These are isolated test measurements, not production cPanel or network/provider guarantees. Repeated local/native/browser executions are separate totals, not additive claims of unique coverage. All provider data are synthetic; genuine retrieval count remains zero.
