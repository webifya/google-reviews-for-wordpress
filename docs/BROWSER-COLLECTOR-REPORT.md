# Experimental Google Maps browser collector — delivery report

Version: **1.5.0-rc.1**, prototype release candidate.

## Scope and implementation

The user's “revise it” instruction authorizes a public-page scraper prototype in place of the previous permitted-method-only requirement. That instruction does not grant a Google copying/storage/republication license. The revised requirement is recorded in `docs/BROWSER-COLLECTOR-REQUIREMENTS.md`.

The browser worker opens the supplied public listing, selects Reviews, expands visible text and reads rendered DOM cards. It uses no Google credentials, API key, intercepted response payload, private endpoint, proxy rotation, stealth, or CAPTCHA solver. It stops at authentication/access barriers. No claim of all-review collection is made.

WordPress reuses its existing review tables and carousel. The new source is separate from licensed providers. Authenticated worker jobs use bounded leases, business bindings and replay protection. Stable DOM review IDs deduplicate repeats and update changes. Partial observations do not trigger deletion. Moderation and analytics continue unchanged.

A single additive `review_date_label` column stores relative labels without invented timestamps. An upgrade from 1.4.1-rc.1 preserved all records, widget IDs, styles, shortcode pages, analytics, privacy settings and encrypted Google credentials. Repeated migration was idempotent.

## Live test: Menjar Financial

Supplied Place ID: `ChIJy3bcVRzl9IgR5eOJcFC6ZCQ`.

- Listing advertised **21** reviews.
- One visible-browser collection retrieved **5 genuine reviews**, with expanded text, all five ratings and three owner replies.
- The captured result contained five relative date labels. Exact timestamps were not provided.
- Asking the public UI for the remaining reviews prompted sign-in. That barrier was not crossed.
- Other visible-browser and headless runs returned **zero review cards**. The authenticated worker's fresh collection also returned zero and correctly recorded a failure.
- The five previously captured genuine reviews were subsequently sent through the authenticated WordPress endpoint and stored/displayed in the isolated test site. This transport test is **not represented as another successful fresh browser run**.
- No elapsed live 24/72-hour production cycle was tested. All-review retrieval and dependable unattended operation remain unverified.

## Scheduling and hosting

Daily and every-three-day schedules are implemented; existing manual/5/7-day options remain. WordPress cron queues due jobs and the separate worker processes them. A quiet cPanel site needs real cron; the worker must also run continuously.

The worker needs Node.js 22+, Playwright/Chromium, and a browser display. The normal visible-browser mode is the default. PHP-only cPanel hosting alone is insufficient. Private WordPress application-password configuration and installation steps are in `worker/README.md`. Nothing was deployed to the user's hosting.

Network/layout failures preserve reviews and retry at the selected interval. CAPTCHA, login/access restrictions, identity mismatch and expired jobs require manual attention. Existing permitted providers retain their own policy controls; experimental data is never labeled licensed.

## Verification

Local backend: **421 assertions**, including 373 existing assertions and 48 new collector assertions.
Local browser checks: **132 assertions** (39 carousel, 34 licensed-source administration, 33 prior administration, 13 collector administration, 13 DOM extraction fixtures), with no JavaScript errors in the recorded browser reports. Fixtures are explicitly synthetic and separate from the live five-review capture.

Final compatibility run passed all **17 jobs**: PHP 8.1–8.4 × WordPress 6.4.7/7.1.3 × MySQL/MariaDB (16 combinations), plus the three-browser regression job. The native jobs executed **6,736 backend assertions** (421 per combination); the browser job ran **101 setup assertions** and **602 browser/DOM assertions** (576 existing across Chromium/Firefox/WebKit plus 26 collector checks), without unexpected PHP failures. An initial browser run hit an iframe preview timeout; the final full run passed, and a same-origin DOM preview check also passed locally in Firefox (86 assertions).

Implementation commit: `9bc8147936a5de1e827d4e06225def231e72e002`.
Final worker hardening commit: `d1a6d5efc7e4f8b8ea6faaa7a08913174f24ffaa`.
Compatibility run: https://github.com/webifya/google-reviews-for-wordpress/actions/runs/37928084451

## Packages

- WordPress ZIP: `google-reviews-for-wordpress-v1.5.0.zip`, 82,910 bytes, 26 production files. SHA-256: `14c23598abc1f44c423045b3074507319aa5ca1b82d927cccd69fc94c1c12266`.
- Separate worker ZIP: `google-reviews-browser-worker-v1.5.0.zip`, 15,075 bytes, 6 files. SHA-256: `01da10804c6fe6fa447fa368592cec82f4a1fa4fef4f59dd79e1cbc520739846`.

Both packages passed archive integrity checks and exclude credentials, test fixtures, downloaded review content and development runtimes. The disposable local worker credential was revoked and its private configuration file removed after acceptance testing.

## Readiness

**Experimental prototype only.** Five genuine reviews were retrieved once and the storage path works. Live access is inconsistent. Do not promise all 21 reviews, stable daily downloading, or a full archive. Hosting configuration and further operational validation are still required.
