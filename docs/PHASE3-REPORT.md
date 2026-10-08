# Phase 3 audit and release verification

Release candidate: **1.2.0-rc.1**. Public listing and map support is implemented and tested. Live Google owner OAuth remains fixture-tested only. The final CI and release verification record follows below.

## Audit findings and changes

- Location identity was conflated with review retrieval status. Public listing, map configuration, review source and synchronization now have separate structured values and friendly presentation, including legacy rows.
- The Locations page permanently exposed a large technical form. It now uses searchable/filterable cards, nine-record pagination, and a four-step native dialog with tab-scoped draft recovery.
- Official embeds only accepted a URL and had no same-page preview. The editor now accepts a single official sharing iframe or its source, validates it, discards supplied markup, and shows Map and Reviews preview tabs.
- Metadata edits could reset a feed's schedule. Public details, embeds and logos now preserve the source schedule/status/cursor. Source identity changes invalidate the cursor.
- Google account configuration occupied Synchronization and public-listing actions. Location Connections separates public listings, optional owner authorization and authorized feeds.
- Raw statuses, oversized forms and inconsistent controls were visible throughout administration. All nine screens now share plugin-scoped navigation, cards, readable controls, mobile table cards and dismissible notices.
- Visual QA found a WordPress global preview float breaking the mobile builder. Explicit scoped layout fixes keep the preview and publish panel readable.
- Hidden imported reviews were described as absent imports. Preview responses now distinguish saved count from visible count and use accurate empty-state wording.
- Preview errors remained after correcting invalid input. Successful validation now clears the stale error.
- The quoted “Your new Google reviews have been downloaded” / “Reply with ChatGPT!” notice is absent from this repository. Its actual installed-plugin owner cannot be identified without access to the user's site. No unrelated notices are hidden.

## Public listing behavior

Any business can be recorded without ownership, OAuth or an API key. Save a Maps URL or manually enter its name and address. Official short links use at most three allowlisted redirects; this does not fetch review HTML or verify ownership. A name/address search link can be generated with a supplied Place ID or numeric CID. These links do not prove automatic business resolution. The Menjar Financial address was used only as a clearly labeled fixture; no actual business identity was remotely resolved.

Google documents key-free Maps URLs in its [Maps URL guide](https://developers.google.com/maps/documentation/urls/get-started) and the sharing embed workflow in [Google Maps sharing help](https://support.google.com/maps/answer/7101463?hl=en). A custom reviews link is displayed only when supplied as a valid Google Maps listing/reviews URL.

## Owner connection and synchronization

Owner authorization remains optional and requires an approved Google project and access to a verified business. Credentials are encrypted, REST status omits secrets, account/location lists are permission-checked through Google's API, and selected business resources require confirmation. UI distinguishes a saved token from validated access and records the last successful API validation. Expiration/revocation produces attention/reconnect guidance. Disconnect removes credentials and temporary Google cache while preserving public listings and authorized imports.

Live OAuth is unavailable under the user's fixture-first testing preference. Google original reviews remain temporary, owner-only data; they are not silently republished as public review cards. Local authorized JSON media feeds and permitted adapters support scheduled synchronization. Public links, map iframes, file imports and manual testimonials do not automatically retrieve Google reviews.

## Map and review previews

Only HTTPS `www.google.com/maps/embed?pb=…` sharing URLs with a nonempty sharing payload are accepted. Multiple iframes, scripts, event handlers, `srcdoc`, foreign hosts, unsupported query forms and programmable Embed API URLs are rejected. WordPress's HTML tag processor extracts the source; renderers build a fresh escaped iframe. Heights are bounded and responsive.

The editor provides loading, navigation, blocked/error and timeout guidance. A cross-origin frame load event cannot prove visible map content, so the interface asks for visual confirmation. Browser tests use explicitly labeled synthetic iframe transport fixtures and blocked requests; they do not assert that a real Google business loaded. Reviews Preview queries actual saved data and remains empty when none exists.

## Shortcodes and upgrade

```text
[google_reviews_widget id="123"]
[google_reviews_widget id="123" limit="20"]
[google_reviews_grid id="123"]
[google_reviews_map location="456" height="350"]
[google_reviews_combined id="123"]
```

The original widget shortcode and block remain compatible. Map visibility and outbound Maps-link visibility are independent widget settings, disabled by default for existing widgets.

Back up WordPress files/database. Upload the new plugin ZIP via Plugins → Add New → Upload Plugin, select Replace current with uploaded, and keep the plugin active. Do not uninstall to upgrade. Schema updates derive presentation for old records without rewriting saved location/embed/review/widget/settings content.

## Security and remaining limits

Capability checks and REST cookie nonces protect administration; input shape checks, escaped output, prepared SQL, Google host/path allowlists, bounded SSRF-safe resolution, OAuth state binding, encrypted tokens, protected moderation and deletion safeguards remain covered. Map rendering performs no server-side Google review retrieval.

No live Google OAuth or actual Menjar embed is verified. Business identity confirmation remains manual when redirect resolution cannot provide identity. Official Google embeds contact Google and require appropriate site privacy/consent setup; no universal consent-platform blocker is included. Admin location filtering/pagination is client-side over the privileged state response. Multisite network lifecycle remains unsupported. Native MySQL/MariaDB and browser execution results are recorded below.

## Redesigned screens

| Screen | Result |
| --- | --- |
| Dashboard | Compact metrics, grouped actions, friendly source health, consistent navigation |
| Business Locations | Search/filter/pagination, business cards, four-step add/manage dialog, independent statuses |
| Reviews | Responsive row cards, existing filtering/moderation/import/history and manual editing preserved |
| Widgets | Grouped controls, independent map/link settings, live device previews, copy grid/combined shortcodes, readable mobile publish panel |
| Analytics | Consistent controls, metrics/chart/tables, responsive rankings and export |
| Synchronization | Source health and bounded permitted sync, account setup moved to Integrations |
| Settings | Scoped privacy/retention controls and compact save feedback |
| Location Connections | Public, owner/manager and authorized feed tabs; public records never offer account disconnect |
| Tools & Diagnostics | Secondary navigation, wrapped diagnostics, scoped cache/repair/import/export controls |

## Verification evidence

Local execution: WordPress 7.1.3, PHP 8.4.23, official WordPress SQLite integration; Chromium, Firefox and WebKit through Playwright 1.62.1.

- Existing backend suite: **101 passed**. Additional public/map/credential/hidden-preview suite: **43 passed**.
- Existing carousel suite: **39 per engine**, **117 total**, no JavaScript errors.
- Existing administrator suite: **44 passed**, including eight-stage onboarding, real clipboard writes, consent-driven browser analytics, bulk moderation and widget preview editing.
- Final Chromium Phase 3 suite: **134 passed**, covering all nine screens at 375/768/1024/1440, dialog focus/Escape, draft preservation, rejection recovery, empty and hidden previews, public/account separation, pagination/filtering, protected deletion, published map/combined shortcode pages, dark-widget map readability and mobile builder layout.
- Firefox and WebKit completed the earlier **116-check** admin suite locally. Final **134-check** suites passed on all three engines in release CI, **402 total**, with no JavaScript errors. Map iframe transport is explicitly synthetic; blocked requests and loading/navigation messages are tested without claiming a live Google business was verified.
- Additional local visual QA scrolled lazy frontend frames into view and verified their synthetic transport content before screenshot capture.
- Final ZIP installed and activated through WordPress's actual Upload Plugin screen. It then replaced the published 1.1.0-rc.1 ZIP through that screen. An immediate complete snapshot comparison preserved locations, embed URLs, reviews, widget configuration, analytics, logs, settings, and original-shortcode page content. Rendering that page retained its custom heading. Repeated schema migration preserved the same snapshot.
- PHP syntax, JavaScript syntax and whitespace checks passed. Native CI tests PHP 8.1/8.2/8.3/8.4 × WordPress 6.4.7/latest × MySQL 8/MariaDB 10.11; every job runs backend checks, previous-release upgrade comparison and bounded 10/100/1000/10000-review benchmarks.

Before/after examples: [old Locations](screenshots/phase3/before-locations-desktop.png), [new Locations](screenshots/phase3/locations-desktop.png), [mobile Locations](screenshots/phase3/locations-mobile.png), [same-page map editor](screenshots/phase3/editor-map.png), [Connections](screenshots/phase3/connections-desktop.png), [frontend map and reviews](screenshots/phase3/frontend-map-mobile.png). All visible review/map transport data are test fixtures. The release screenshot archive contains the fuller set at the requested widths.

## Remote and package identifiers

Implementation and final tested source: `022c32d33245ac47d8966dd6859f95c0d4047251` on `main`.

Release verification CI: [run 37849627854](https://github.com/webifya/google-reviews-for-wordpress/actions/runs/37849627854). All **17 jobs passed**: 16 native database/WordPress/PHP combinations and one three-engine browser job. Each native job passed 144 backend assertions and the previous-release snapshot/shortcode upgrade comparison.

Release: [v1.2.0-rc.1](https://github.com/webifya/google-reviews-for-wordpress/releases/tag/v1.2.0-rc.1).

Installable package: [google-reviews-for-wordpress-v1.2.0.zip](https://github.com/webifya/google-reviews-for-wordpress/releases/download/v1.2.0-rc.1/google-reviews-for-wordpress-v1.2.0.zip), **71,856 bytes**, SHA-256:

```text
5e8d28375f66152f66089da0ca1e256b4922263ac1b11df1132aff569625eb8d
```

All packaged runtime files are byte-identical to the native CI-tested package. The only packaged change after those tests corrects the historical 1.1.0 changelog heading in readme.txt. The final ZIP is compared with the separate GitHub release-build artifact. The published download and release/tag/main references are verified after publication. The final documentation/changelog commit changes no runtime files.
