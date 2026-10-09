# Combined plugin · v1.5.0-rc.2

Built on verified GitHub main commit `81f180755f328e0e6225a3444b6b204d6a32161e`.

## Delivered

One WordPress-installable ZIP contains the PHP plugin, browser collector, and pinned Playwright 1.62.1 libraries. In bundled mode, WordPress starts the browser through its existing background queue, passes the bounded job over stdin, and validates results through the same lease, business-binding, import, provenance and deduplication logic. No separate service, worker ZIP, or WordPress application password is required. Existing external transport remains optional and is retained for prior worker installations until the administrator switches modes.

Settings offers executable-path detection, bundled/external mode, headless choice, and a browser startup check that makes no Google request. Daily and three-day schedules, stored reviews, existing shortcodes/carousel, widgets/styles, analytics and encrypted legacy Google credentials remain intact. Deactivation clears local execution events; opt-in uninstall cleanup includes collector options. No new review tables or review-data migrations are introduced.

## Hosting requirements

Node.js 22+, Chrome/Chromium with its OS libraries, PHP proc_open, and adequate job execution time are required on the hosting server. Headed mode also needs an existing display. Platform-specific Node and browser binaries are not included or silently installed. PHP-only shared/cPanel hosting requires assistance from the host or optional external mode.

## Validation

- 373 previous backend assertions and 48 existing scraper assertions passed on disposable WordPress 7.1.3/PHP 8.4.23.
- 16 new installed-package assertions passed: browser-library inclusion; actual browser startup; path/mode validation; anonymous configuration denial; credential-free process-to-storage transport; daily/72-hour schedules; deduplication; single-process lock; malformed-result retention; optional external mode.
- 15 admin browser assertions and 13 public-page DOM fixtures passed in Chrome. No JavaScript errors.
- Upgrade from the preceding rc.1 installation compared every persisted location/review/widget/analytics/log row, settings and encrypted legacy credentials, without seeding or replacing existing data. All were preserved; repeated migration was idempotent and the stored genuine-review carousel shortcode still rendered. The old upgrade test's unrelated fixture page was absent on this installation; a direct full-data comparison and existing carousel were used here. The standard seeded upgrade suite remains in CI.
- Syntax and package archive checks passed. Private admin screenshots, review captures and local snapshots are not release assets.

All new transport/import tests use synthetic fixtures and make zero Google requests. Browser startup checks establish runtime availability only.

## Scraper limitations remain

No new successful live Google collection is claimed. Previously one headed Menjar Financial run retrieved 5 of 21 advertised reviews; repeated/headless runs yielded no usable cards. WordPress's five authentic-row storage test replayed that prior capture. Consolidating packages does not establish reliable unattended Google access, complete retrieval or an elapsed 24/72-hour production cycle. The collector stops at sign-in, CAPTCHA, access denial and identity mismatch, without bypasses. Public-page access and administrator opt-in do not grant a Google extraction/storage/republication license. The release remains experimental, not stable.
