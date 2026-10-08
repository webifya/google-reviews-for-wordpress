# Changelog

## 1.4.0-rc.1 — 2026-10-09

- Set new location checks to 72 hours; offer 1/3/5/7-day and manual schedules, preserving saved intervals. Newly configured licensed sources stay unscheduled until a complete review collection validates them.
- Extend retained tables with provider/business/license provenance, last synchronization timestamps and indexes. Unchanged checks update sync time without pretending content changed; stable review identities survive edits.
- Apply explicit licensed-provider removal signals only to matching collections, validate bounded IDs and require declared deletion capability. Unsupported time-limited storage remains rejected.
- Use the existing complete safe sync for connection validation; show accurate local counts, schedules, Sync Now notices and fixed redacted errors.
- Move identifiers to Advanced settings, preserve review-first navigation, show dashboard saved reviews/widgets/views and sync dates. Enable analytics only on fresh installs with explicit consent; preserve saved privacy preferences.
- Add 46 Phase 6 WordPress fixtures, browser tests for all schedules/published local rendering, analytics/batch benchmarks and four-column migration preservation. No genuine provider retrieval or full 72-hour production cycle is claimed.

### Incorporated Phase 5 groundwork (previously unpublished)

- Research Google and nine review vendors, document individual capabilities, rights, account requirements and 1/5/10/25/100-location costs. Retain the official Places fallback; no unlicensed or unverified vendor connector is claimed.
- Bind licensed records and stable identities to the current provider/business and license reference; exclude stale/revoked collections from public/cache paths without deleting existing records.
- Correct actual accessible counts and manual/scheduled/daily status; add storage visibility and fixed safe provider errors.
- Validate complete pages and stable review IDs, detect cyclic cursors, resume bounded jobs with cumulative retrieval/update/page/duration statistics, grant newly bound collections only after completion.
- Add 40 simulated WordPress assertions and previous-1.3 candidate migration checks; preserve carousel, shortcodes, analytics, legacy data and security controls. Live retrieval and a genuine 24-hour provider cycle remain blocked.

## 1.3.0-rc.1 — 2026-10-09

- Add official Places API (New) request-only review display, restricted encrypted server keys, identity confirmation, budgets and attribution.
- Simplify review-first navigation, connection flow and widget builder; retain legacy records and shortcodes.
- Verify provider transport and recurring software with fixtures; no genuine live Google access. See [Phase 4 report](docs/PHASE4-REPORT.md).

## 1.2.0-rc.1 — 2026-10-09

- Public-listing setup, separate review/map connection status, four-step widget builder, secure sharing iframe parsing and legacy shortcode compatibility. See [Phase 3 report](docs/PHASE3-REPORT.md).

## 1.1.0-rc.1 — 2026-10-09

- Add optional Google Business Profile owner OAuth, encrypted credentials, account/location selection, paginated owner review browsing, temporary cache and scheduled first-page refresh. Google content never becomes public imported cards.
- Add three-panel widget builder, isolated draft preview, device views, presets, undo, save-as-new, named business selection, custom class/full width and configurable gesture controls.
- Refine distinct card templates and mobile spacing; preserve slider index and expanded text across resize and loop clones; observe changing motion preferences; dispose removed instances.
- Add review visibility filters, bulk moderation, protected manual reassignment, import history, widget deletion and safe empty-location deletion.
- Fix bounded analytics batching, reachable grid impressions, duplicate client mounts, generic interactions, cached administrator exclusion and collection budgets; add confirmed reset and more dashboard metrics.
- Cache public review queries with invalidation, validate locations once per import, preserve sync state on metadata edits, discard incompatible cursors, validate malformed input and redact source errors.
- Resolve official share-link redirects through allowlisted HEAD requests with manual confirmation.
- Expand PHP 8.1–8.4 and MySQL/MariaDB CI, browser regressions, upgrade/upload tests and synthetic 10,000-review benchmarks.

Candidate qualification and executed evidence: [Phase 2 report](docs/PHASE2-REPORT.md).

## 1.0.0 candidate — 2026-10-09

Initial implementation: namespaced WordPress services, dedicated tables, locations, authorized CSV/JSON import and local-feed sync, manual testimonials, source adapters, five custom card templates, official map embed layout, widget editor, shortcodes/block, carousel controls, privacy-aware analytics, moderation, diagnostics, test suites and packaging workflows.

See `docs/VERIFICATION.md` for executed checks and release qualification limits.
