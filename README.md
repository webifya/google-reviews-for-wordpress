# Google Reviews for WordPress

A WordPress-native plugin for official Google Maps embeds and review cards using content you are authorized to publish. No API key, subscription, application server, build tool, or external database is required in production.

**Release status:** 1.0.0 candidate. See [verification and limitations](docs/VERIFICATION.md) before deploying. An arbitrary Google Maps URL cannot supply individual review data, unlimited Google reviews, or daily Google synchronization.

## Installation

1. Back up your WordPress site. Requires WordPress 6.4+, PHP 8.1+, MySQL/MariaDB.
2. Upload `google-reviews-for-wordpress-v1.0.0.zip` under **Plugins → Add New → Upload Plugin**; activate.
3. Open **Google Reviews → Locations** and add/confirm the business details.
4. Import CSV/JSON with publishing permission, add manual testimonials, or configure an authorized local JSON feed. For an official map, paste the `src` from Google's **Share → Embed a map** iframe and select the official embed widget template.
5. Create a widget, select location IDs, save, customize the live preview, and copy its shortcode into your page. Choose a full-width section in your page builder to reproduce the reference composition.
6. Enable analytics only after configuring consent and privacy notices.

## Features

- Independent locations and widgets; indexed dedicated tables; storage has no review-count cap.
- Authorized CSV/JSON batches of 200, deduplication by location/source/stable external ID, and updates that preserve moderation. Files are parsed locally; there is no uploaded code execution.
- Manual testimonials and protected third-party text: imported reviews can be hidden, featured or removed, but cannot be edited as original reviews.
- **Classic Google Reviews Carousel**, Modern Minimal, Dark Premium, Review Grid, Compact Review Slider; additional Official Google Maps embed layout.
- Colors, typography, spacing, visibility, filtering, review selection/order, summary, arrows, dots, autoplay, infinite cycling, pointer swipe/drag, keyboard support and reduced motion.
- Independent shortcodes and a Gutenberg block selecting a saved widget.
- Privacy-conscious first-party analytics, consent integration, daily/monthly tables, a daily bar chart, widget/location rankings and CSV export.
- Source status, cron logs, JSON settings export/import, safe diagnostics, table/index repair and an eight-step guided onboarding flow.

## Shortcodes

```text
[google_reviews_widget id="123"]
[google_reviews_widget id="123" limit="20"]
```

Use in WordPress posts/pages, Gutenberg Shortcode blocks, Classic Editor, Elementor's shortcode widget and other shortcode-compatible builders. Only `id` and `limit` are accepted. The block is **Google Reviews Widget**.

## Sources and synchronization

| Mode | Custom cards | Automatic sync | Requirements |
| --- | --- | --- | --- |
| Official Google Maps share embed | No; Google's own iframe | Google controls iframe content | Official embed URL; site privacy setup |
| Authorized CSV/JSON import | Yes | No | Rights to store and republish content |
| Manual testimonials | Yes, accurately labeled | No | Publishing rights |
| Authorized local JSON feed | Yes | Yes | JSON media attachment, rights confirmation, active location |
| Extension adapter | Yes when license permits | Adapter-dependent | Trusted integration code and explicit redistribution rights |

Upload the local JSON feed through **Media → Add New**, then enter its media attachment ID on the location. JSON uploads are enabled only for administrators. Maintain/replace that attachment file through your authorized publishing workflow. The plugin reads only `.json` files resolved inside WordPress uploads, up to 10 MB; it does not call a remote URL. A feed is a JSON array in the import schema. Changes are detected on each scheduled sync; stable IDs update existing records. Review disappearance does not automatically delete saved records.

Schedule choices: 12h, 24h, 48h, weekly or manual. WP-Cron checks hourly and handles up to five due locations per tick, at most five provider pages per location. Pagination continues on subsequent checks. Locks prevent concurrent sync; temporary failures preserve valid data and retry at 15/30/60 minutes before returning to the configured interval. A low-traffic site needs real server cron. On cPanel, configure a five-minute job using your hosting account's PHP path:

```sh
/usr/local/bin/php /home/ACCOUNT/public_html/wp-cron.php >/dev/null 2>&1
```

After testing that job, set `DISABLE_WP_CRON` in `wp-config.php`. Never claim that this makes unsupported Maps retrieval available.

See [source research](docs/SOURCES.md) and [adapter contract](docs/ADAPTERS.md).

## Import schema

JSON array or CSV header:

```csv
external_id,reviewer,rating,content,review_date,avatar,permalink,source_name,source_url,response
sample-001,Sample reviewer,5,Sample only. Replace with authorized content.,2024-01-15,,,Authorized dataset,,
```

Alternate headers: `review_id`, `reviewer_name`, `review_text`, `reviewer_profile_image_url`, `review_permalink`, `business_response`. Select the location explicitly for each import; IDs in the file never silently associate an ambiguous business. Ratings are integers 1–5 or blank. Dates are ISO calendar dates or ISO timestamps; blank stays unknown. UTF-8 and Unicode are preserved. Use stable external IDs (or a stable original permalink) for updates. Without either, a changed text is considered a new identity. No verification badge is inferred from a source name.

Files: 10 MB per browser-selected upload; 500 rows maximum per REST request (UI sends 200). Importing more than that is supported through repeated batches/files. Each widget defaults to 100 matching reviews; display limit is validated up to 10,000 to protect page memory. Storage has no arbitrary count limit. For large sites, keep display counts modest, use location/rating filters, and avoid random sorting on huge datasets. Export currently assembles downloaded data in the browser, so very large exports may need an external authorized backup workflow.

## Privacy and analytics

Disabled by default. An impression occurs after at least 30% of the section has been visible for one second; it is deduplicated per widget/session. Unique estimates use a random sessionStorage token, hashed on the server and retained in expiring transients; no IP, user agent, visitor email or WordPress user identity is recorded. No browser cookies are created. The token is sent only after consent. Administrator previews are excluded. Do Not Track and Global Privacy Control prevent collection.

Consent manager integration:

```javascript
window.grwAnalyticsConsent = true; // set before plugin initialization when consent is granted
window.dispatchEvent(new CustomEvent('grw:consent', {detail: true})); // grant later
window.dispatchEvent(new CustomEvent('grw:consent', {detail: false})); // revoke
```

Daily aggregate counters expire after the configured 1–730 days (default 90). Public collection uses same-origin checks, strict event validation, maximum 30 events per batch and 120 per session token per minute. Anonymous counters remain estimates: a determined client can rotate tokens or forge events; they are not billing-grade analytics. Engagement rates are event counts divided by impressions and can exceed 100%. Per-location impressions indicate an assigned widget was visible, not that a particular card was read. Public assets and URLs work with page caching; synchronization never runs during shortcode rendering.

External avatars and official embeds may contact third parties. Google iframe content cannot be restyled as plugin cards. Configure those features consistently with your consent policy; the plugin does not automatically block iframe loading for every consent platform.

## Development and testing

No frontend asset compilation is required: shipped vanilla CSS/JS are production assets.

```sh
find . -name '*.php' -print0 | xargs -0 -n1 php -l
npm install
npm run check
GRW_WP_ROOT=/path/to/disposable/wordpress php tests/integration.php
python3 scripts/package.py
```

Tests write sample data into a disposable installation; do not run them on a production database. GitHub Actions tests MySQL-backed WordPress across PHP/WordPress versions and builds the ZIP; browser CI exercises Chromium, Firefox and WebKit. For local browser tests, use `scripts/browser-site.sh` and follow the environment variables in `tests/browser.cjs`. [Verification record](docs/VERIFICATION.md) distinguishes executed checks from planned CI.

## Architecture and lifecycle

Namespaced class services: Installer, Locations, Reviews, Sources, Sync, Widgets, Renderer, Analytics, Admin, Security and Plugin bootstrap. Five indexed tables isolate storage. Schema installation is idempotent and versioned; cron registration is idempotent. REST mutations require `manage_options` and WordPress REST cookie nonces; public analytics have a separate restricted payload. Safe source helpers allow only exact code-defined hosts, reject unsafe URLs and disable redirects. No Google scraping, hidden endpoint access, proxy rotation or CAPTCHA bypass exists.

Deactivation clears scheduled work and preserves data. Uninstall preserves everything unless the administrator explicitly selects data deletion. Multisite network activation and network-wide cleanup are not supported; install/configure per site. Diagnostics contain version/health/count information, never provider tokens or internal logs. Source credentials belong in trusted integration configuration and must not be exported.

GPL-2.0-or-later. Not affiliated with or endorsed by Google. Google trademarks belong to their owners.
