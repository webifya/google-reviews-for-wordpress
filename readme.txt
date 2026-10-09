=== Google Reviews for WordPress ===
Contributors: webifya
Tags: reviews, carousel, google maps
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.5.0-rc.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Local review carousels and an experimental public Google Maps browser collector requiring a separate worker.

== Description ==
The prototype visits the public business page and collects accessible rendered reviews through a separate Node/Chromium worker. No Google key or account is used. It stops at CAPTCHA, sign-in, access denial or identity mismatch. It does not promise every review or dependable unattended operation.
A live visible-browser test retrieved 5 of 21 advertised Menjar Financial reviews; other runs returned no review cards. Those captured reviews were stored through authenticated local WordPress transport. No elapsed 24/72-hour production cycle has been verified. The prototype does not grant a Google storage/republication license.
Existing review tables, carousel, styles, shortcodes, encrypted Google credentials, licensed providers and analytics are preserved. Source bindings and stable IDs deduplicate repeated collection and update changes. Relative date labels are stored without invented timestamps.

== Installation ==
1. Back up your site; upload/replace the plugin ZIP. Do not uninstall to upgrade.
2. Install the separate browser worker on an always-on computer or server with Node.js 22+, Chromium and a browser display. PHP-only cPanel hosting cannot run the worker. Follow worker/README.md in the repository or worker ZIP.
3. Supply the private WordPress HTTPS URL, username and application password to the worker's environment and start it. No Google credentials are used.
4. Locations: enter Maps URL/Place ID, confirm the business, select the experimental browser source, enable experimental collection and save. Saving alone does not download reviews.
5. Download available reviews queues a job. Refresh after completion. Counts distinguish the advertised total, accessible subset, stored records and displayable records.
6. Sync settings selects daily or every three days (default), or manual/5/7 days. Keep the worker running and WordPress cron enabled.
7. Use Connected review sources in a widget and publish [google_reviews_widget id="123"].

== Frequently Asked Questions ==
= Will this download all reviews? =
No. Public views can be limited, require sign-in, change layout or deny access. Results remain partial unless the visible advertised count and collected count match.
= Does it work on cPanel? =
The PHP plugin does. The separate worker requires a browser-capable machine; ordinary PHP-only cPanel hosting is insufficient.
= Does it bypass CAPTCHA or use private Google endpoints? =
No. It reads rendered public cards only and stops at access restrictions.
= What happens after a failure? =
Previously stored reviews are retained. Network/layout failures retry at the selected interval. CAPTCHA/sign-in/access-denial requires manual attention. Missing cards are not inferred to be deleted reviews.

== Changelog ==
= 1.5.0-rc.1 =
Experimental browser worker, authenticated WordPress queue/results, separate public-page provenance, partial counts, relative date labels, and 24/72-hour scheduling. Prototype only; unattended reliability remains unverified.
= 1.4.1-rc.1 =
Simplified administration, preserved prior functionality, and reported blocked permitted API-free review retrieval.
