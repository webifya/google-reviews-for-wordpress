# Google Reviews for WordPress

**1.5.0-rc.1 — experimental public-page browser collector.**

The collector visits a public Google Maps business page, opens its visible reviews, expands text, and stores accessible reviews in the existing WordPress tables. A separate Node/Chromium worker is required. No Google API key or account is used. Collection stops at CAPTCHA, sign-in or access denial.

A visible-browser live test on the supplied Menjar Financial listing retrieved **5 of 21 advertised reviews**. Other visible-browser and headless runs returned no review cards. Those five captured reviews were stored through the authenticated local WordPress endpoint. A full archive, dependable unattended operation and an elapsed live 24/72-hour cycle are **not established**. This is a prototype, not a stable review-download release. The user's revised scope does not grant a Google storage/republication license.

## Install and use

1. Back up your site. Upload/replace the plugin ZIP on WordPress 6.4+, PHP 8.1+, without uninstalling the existing plugin.
2. Install the separate worker on an always-on computer or browser-capable server. It needs Node.js 22+, Chromium and a browser display; PHP-only cPanel hosting is insufficient. [Worker setup](worker/README.md).
3. Set the worker's private WordPress HTTPS URL, administrator username and application password in its environment. No credential is entered into Google Maps or published in diagnostics. Start the worker.
4. In **Locations**, enter the full Maps URL/Place ID, confirm the business, select **Google Maps browser collector · experimental**, and enable experimental collection. Existing public listings can use **Enable browser collection**. Saving a listing alone does not download reviews.
5. Click **Download available reviews**. The job is queued for the worker; refresh after collection. Listing advertised total, accessible current subset, stored accumulated count, and displayable count remain separate. Partial results are explicitly labeled.
6. **Sync settings** supports daily, every three days (default), 5/7 days, or manual. WordPress cron queues due checks; the browser worker performs them. Quiet sites need a real cron request to `wp-cron.php` every five minutes. Network/layout failures preserve data and retry at the selected interval. CAPTCHA/sign-in/access denial pauses until manual attention.
7. Create or edit a widget with **Connected review sources**, then publish `[google_reviews_widget id="123"]`. Existing IDs, styles, templates, shortcodes and analytics remain compatible. New widgets retain 100-review limits, five-second autoplay, looping and 3/2/1 responsive cards; only actually available records render.

## Storage and compatibility

Stable visible review IDs and content hashes deduplicate repeated checks and update edits/replies. Source/business bindings prevent old business content from appearing under a changed listing. Incomplete public views do not imply deletions. Browser-collected rows are marked `scraped`, separate from licensed adapter rows, without invented license metadata or verification badges.

The existing tables are reused. An additive `review_date_label` column preserves dates such as “3 months ago” without inventing exact timestamps. Older records remain unchanged. Undated records use database ordering; exact-date filters and historical newest-first guarantees require actual timestamps from the source. Reviewer photos remain URLs; image files are not mirrored.

Existing encrypted Google settings and live Places widgets retain their compatibility behavior. Licensed providers retain their existing validation, scoped display and synchronization. Historical analytics and privacy preferences are preserved. New primary navigation still has no Google key/billing/OAuth prompts or manual/CSV/JSON review imports.

## Verification

PHP integration, source/security, migration and browser tests use isolated synthetic fixtures. Live extraction is reported separately. The collector's tests cover partial counts, expanded text, rating-only reviews, owner replies, identity checks, authenticated callbacks, stale/replayed jobs, duplicate/update handling, scheduling, CAPTCHA stops and preservation of stored reviews.

Build the plugin with `python3 scripts/package.py` and the separate worker with `python3 scripts/package-worker.py`. Packages exclude fixtures, credentials, downloaded review content and screenshots. Install the worker dependencies with `npm ci` in its own directory.

[Revised requirements](docs/BROWSER-COLLECTOR-REQUIREMENTS.md), [worker instructions](worker/README.md), [historical access investigation](docs/PHASE7-ACCESS-FINDINGS.md), [licensed adapter contract](docs/ADAPTERS.md). Previous phase reports are historical evidence, not current setup instructions.
