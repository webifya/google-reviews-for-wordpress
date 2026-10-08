# Changelog

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
