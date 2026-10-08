# Phase 2 audit ledger

Baseline: 36dea654964656a13868ffd09ae8ed594374bafd. All service classes, assets, tests, packaging workflows, lifecycle and documentation reviewed before edits.

| Severity | Confirmed finding | Regression target |
|---|---|---|
| High | Rotating analytics session tokens create unbounded per-token transient rows; token budget can overshoot | Global collection budget and strict batch budget |
| High | Disabled previews still allocate tokens, observers, event listeners and timer closures; repeated editor preview replacements leak resources | Explicit disposal and no tracking initialization for previews |
| Medium | Continuous interactions reset debounce forever; batches beyond 30 can remain queued; navigation/outbound events omit generic interactions | Fixed bounded flush schedule and complete drain |
| Medium | Tall grid sections may never reach 30% viewport visibility | Observe reachable viewport portion |
| Medium | Same session/widget mounted twice counts two client impressions | Shared session deduplication |
| Medium | Location name edits reset status/schedule; changing source keeps an incompatible pagination cursor | Preserve unchanged source state; clear cursor on source change |
| Medium | Feed updates hash omits some metadata; manual edit identity can vary with input source label | Canonical normalized hashes and manual identity |
| Medium | Every imported row queries location existence; identical widget queries are uncached | Validate location once per batch; generation-based query cache |
| Medium | Integer settings accept fractional cards/counts, scalar fields accept nested types, invalid time components can normalize silently | Strict type/calendar validation |
| Medium | Carousel resize resets current index and expanded state; reduced-motion updates aren't observed | Preserve index/state; media listener |
| Medium | Widgets cannot be deleted; locations cannot be safely removed; reviews lack visibility/bulk actions | Lifecycle and management endpoints |
| Medium | Preview can display stale async responses; no draft preview or mobile preview controls | Request sequencing and preview dimensions |
| Low | Diagnostic counts run on every screen; cache clearing is a placeholder; settings backup widgets aren't restorable | Scoped diagnostics, real cache generation invalidation; widget restoration remains a documented limitation |
| Low | Source availability and limitations lack structured status cards; unsupported sources expose sync frequency | Provider-aware connection UI |

Review priorities: permissions/nonces/URL safety; baseline preservation; owner API policy boundaries; frontend lifecycle/analytics accuracy; UI and management; realistic compatibility/upgrade/performance tests. Final evidence appears in [PHASE2-REPORT.md](PHASE2-REPORT.md). No live Google verification is implied by fixture tests.
