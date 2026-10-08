# Phase 5 synchronization verification

Historical Phase 5 local work was interrupted before publication and incorporated into Phase 6 (1.4.0-rc.1). Its daily-default descriptions refer to the earlier proposal; Phase 6 defaults to 72 hours. No v1.3.1 release was published.
**Fixture verification is not live Google synchronization.** The existing hourly WP-Cron dispatcher executes due jobs in bounded groups of five locations. Each job fetches at most five pages, each with at most 500 rows; a further cursor resumes later. These are job/hosting bounds, not a total collection cap. Successful daily jobs schedule another run 86,400 seconds later. Quiet sites should configure their host's cron to invoke WordPress every five minutes; actual execution depends on the host and traffic.

## What the new simulated suite verifies

`tests/phase5.php` uses clearly named SYNTHETIC permanent-provider adapters in an isolated WordPress installation. Its 40 assertions cover configuration-only/empty validation boundaries, manual versus daily versus other schedules, retrieved counts and duration, source/business isolation, stable identities, license-reference changes, literal permission booleans, cache revocation, whole-page input validation, missing IDs, safe provider error bodies/codes, successful retry, six-page resumption and cumulative statistics, repeated refresh deduplication, cyclic cursors and actual due-job dispatcher processing at 1/5/10/25 locations. The admin summary counts only current bound collections. The fixture is removed at the end and never included in the installation ZIP.

The retained Phase 4 suite separately exercises real WordPress cron hooks with synthetic transport, changed review updates, locks, backoff, authentication/network/rate/quota handling, Places request budgets/no-storage and attribution. Existing integration and carousel tests remain in the required checks.

## Operational behavior and limits

A complete successful licensed run records provider/business/license binding, cumulative retrieved/new/updated/unchanged/page counts and duration. Subsequent runs use stable IDs to update or deduplicate records. Failed retrieval retains prior valid rows and uses bounded exponential backoff; logs retain only allowlisted error categories and fixed safe messages. Incompatible source identity clears pagination/progress so a different collection cannot resume an old cursor. Review counts do not borrow a listing's public total or legacy imports.

The default is daily, but **Daily Sync Active** also requires eligible adapter, successful validated nonempty collection, frequency 86,400 and a nonzero next schedule. Disabled scheduling is Manual Sync Only; another frequency is Scheduled Sync Active. This is a configured schedule label, not proof the host has already completed 24 hours of production operation.

This candidate supports the existing permanent-license contract only. It does not implement any new vendor's expiry/purge, incremental token, remote deletion or historical-count promise. These must be supplied and tested with that provider's real documented requirements before registration. Retained rows hidden after source/permission change are preserved for migration safety; that is not a compliance purge implementation. Never register a time-limited feed as a permanent provider.

## Evidence categories

- **A Unit:** No separate isolated unit framework added; syntax/static checks and assertions run inside WordPress.
- **B WordPress integration:** Local 101 integration + 43 Phase 3 + 100 Phase 4 + 40 Phase 5 = 284 backend assertions. Compatibility matrix, package install and upgrade evidence are recorded in the final report after execution.
- **C Simulated provider:** 100 Phase 4 and 40 Phase 5 checks are contained in B, not additional live passes. Synthetic scales 1/5/10/25, plus stored-dataset benchmarks 10/100/1,000/10,000.
- **D Live provider:** Blocked; no genuine retrieval or verified Menjar collection.
- **E Recurring:** Actual WordPress due-job/cron software execution with fixtures passed; no real upstream 24-hour cycle or time-separated genuine fetches.
- **F Browser/visual:** Existing three-engine workflow and carousel results are recorded after completion in the final report. All provider-card screenshots use synthetic content.

No observation of genuinely new Google reviews, public listing history or real daily provider uptime is claimed.
