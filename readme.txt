=== Google Reviews for WordPress ===
Contributors: webifya
Tags: reviews, carousel, google maps
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.5.0-rc.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Local review carousels with a bundled experimental Google Maps browser collector.

== Description ==

The experimental browser collector and Playwright libraries are included in this one plugin ZIP. Bundled mode needs server-installed Node.js 22+, Chrome/Chromium and PHP proc_open. Configure and check it under Settings. PHP-only shared hosting requires assistance from the host. Packaging does not establish reliable Google review access.

The prototype visits the public business page and collects accessible rendered reviews through the bundled Node/Chromium collector. No Google key or account is used. It stops at CAPTCHA, sign-in, access denial or identity mismatch. It does not promise every review or dependable unattended operation.
A live visible-browser test retrieved 5 of 21 advertised Menjar Financial reviews; other runs returned no review cards. Those captured reviews were stored through authenticated local WordPress transport. No elapsed 24/72-hour production cycle has been verified. The prototype does not grant a Google storage/republication license.
Existing review tables, carousel, styles, shortcodes, encrypted Google credentials, licensed providers and analytics are preserved. Source bindings and stable IDs deduplicate repeated collection and update changes. Relative date labels are stored without invented timestamps.

== Installation ==
1. Back up your site; upload/replace the plugin ZIP. Do not uninstall to upgrade.
2. Ask your host to provide Node.js 22+, Chrome/Chromium and PHP proc_open. The plugin includes Playwright libraries; no second package is needed. PHP-only hosting cannot run the browser.
3. Settings: select Inside this plugin, save executable paths if needed, then Check browser. No Google credentials or WordPress worker password are needed in bundled mode.
4. Locations: enter Maps URL/Place ID, confirm the business, select the experimental browser source, enable experimental collection and save. Saving alone does not download reviews.
5. Download available reviews queues a job. Refresh after completion. Counts distinguish the advertised total, accessible subset, stored records and displayable records.
6. Sync settings selects daily or every three days (default), or manual/5/7 days. Keep WordPress cron enabled. Bundled jobs require sufficient PHP execution time.
7. Use Connected review sources in a widget and publish [google_reviews_widget id="123"].

== Frequently Asked Questions ==
= Will this download all reviews? =
No. Public views can be limited, require sign-in, change layout or deny access. Results remain partial unless the visible advertised count and collected count match.
= Does it work on cPanel? =
The display features do. Bundled collection also requires server Node.js, Chrome and process execution; ordinary PHP-only cPanel hosting is insufficient.
= Does it bypass CAPTCHA or use private Google endpoints? =
No. It reads rendered public cards only and stops at access restrictions.
= What happens after a failure? =
Previously stored reviews are retained. Network/layout failures retry at the selected interval. CAPTCHA/sign-in/access-denial requires manual attention. Missing cards are not inferred to be deleted reviews.

== Changelog ==
= 1.5.0-rc.2 =
Single installable ZIP including collector and Playwright libraries. WordPress runs local browser jobs without a separate service or worker password. Hosting checks and external compatibility mode preserve existing data and schedules.
= 1.5.0-rc.1 =
Experimental browser worker, authenticated WordPress queue/results, separate public-page provenance, partial counts, relative date labels, and 24/72-hour scheduling. Prototype only; unattended reliability remains unverified.
= 1.4.1-rc.1 =
Simplified administration, preserved prior functionality, and reported blocked permitted API-free review retrieval.
