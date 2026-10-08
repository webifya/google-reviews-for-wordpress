# Phase 6 pre-implementation audit — 2026-10-09

Verified remote/main and latest release: `3eaa8c47c7f58ceaa7718ce9c5ada5b3c92b2092`, v1.3.0-rc.1. Remote was fetched and compared before Phase 6 edits. The user interrupted Phase 5 before commit/push/release. Its local changes are preserved and incorporated, not represented as a previously published 1.3.1 release.

Reviewed README/changelog, Phase 4 report/provider contract, local Phase 5 research/decision/sync records, Installer, Sources, Locations, Sync, Reviews, Renderer, Plugin shortcodes, Widgets, Analytics, Admin and all existing test/workflow scripts.

| Existing service | Audit finding | Phase 6 action |
| --- | --- | --- |
| Dedicated location/review/widget/analytics/log tables | Already operational; indexes, pagination and imports in PHP; no fixed storage quota | Extend existing migration only for provenance/last-sync indexes, preserve old rows |
| Provider architecture | Official Places live-only five selections; owner-only GBP; trusted permanent-license extension; no licensed vendor account | Retain paths, refuse unsupported storage, document API-free blocker |
| Phase 5 local fixes | Provider/business binding, strict grants, public query revocation, safe errors, truthful counts, bounded paging/stats | Preserve and verify together; 40 Phase 5 fixture assertions available |
| WP-Cron/sync | Hourly dispatcher, five locations/pages, locks/backoff/cursors, daily default | Change new default to 72h; offer 1/3/5/7 days/manual; preserve saved legacy frequencies |
| Local frontend | Existing permitted stored collections render from database, no source fetch | Test absence of provider requests, 100 default/newest sorting, updates/cache behavior |
| Carousel/shortcodes | Requested 100/newest/3-2-1/5s/autoplay/loop/hover/swipe already defaults; five designs and legacy shortcodes | Keep implementation; rerun browser checks |
| Primary admin | Six requested screens; manual/map creation workflows already retired; Place ID still on main form, schedule wizard hardcodes daily | Move identifier controls to Advanced, add precise sync/count facts and schedule selector |
| Analytics | Existing widget default true; installation global default false, explicit consent true; data retained | Enable only fresh-install default, keep consent and saved opt-outs unchanged |
| Retention | Permanent-contract extension only; Places never stored, GBP temporary cache restricted | Reject time-limited storage through this contract; do not claim provider-specific TTL/deletion without a real contract |
| Tests/release | Prior verified matrix 16 native combinations + 3 browser engines; Phase 5 local backend 284 passed, browser completion interrupted | Run all current backend/browser suites, real ZIP upload and 1.3 replacement, CI and public download verification |

No permitted credential-free Google Maps extraction/local archive was established in the current official research. SociableKIT's documented JSON feed is a technical lead but still needs account/rights/history/freshness confirmation; a Maps URL itself is not authorization. No purchase or vendor contact is authorized by this audit. No production external runtime or scraping is introduced.

Important compatibility boundary: previously retained licensed rows remain stored, but cannot be falsely treated as a current verified collection without a successful complete permitted source sync. Existing non-licensed legacy records keep their original provenance. The new 72h and analytics defaults apply to new configurations only.
