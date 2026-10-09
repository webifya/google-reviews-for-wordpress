# Bundled Google Maps collector (experimental)

Install **only the WordPress plugin ZIP**. It contains the collector and pinned Playwright libraries. No second plugin, worker ZIP, WordPress application password or separate service is needed in bundled mode.

## Hosting requirements

The hosting server must already have Node.js **22 or newer**, Chrome/Chromium, their operating-system dependencies, and PHP `proc_open` enabled. PHP-only shared hosting cannot run this feature unless the host adds these capabilities. Browser and Node binaries are platform-specific and are not shipped or downloaded by the plugin.

In **Google Reviews → Settings**, choose **Inside this plugin**, enter executable paths if automatic detection fails, save, and click **Check browser**. The check launches and closes a browser without requesting Google. Headless mode works without a display; ordinary headed mode requires an existing display or Xvfb on Linux. Ask your host to resolve browser startup restrictions.

Enable experimental collection on a confirmed location. Click **Download available reviews**, then refresh after the background job completes. Select **1 day** or **3 days** in Sync settings. WordPress's existing WP-Cron schedule feeds the same bounded queue and validated import path; no second review table is created. For timely processing on quiet sites, configure the host's scheduler to request `wp-cron.php` every five minutes. Long-running PHP/browser jobs need roughly four minutes of permitted execution time.

Bundled jobs pass an allowlisted job over process input and return bounded JSON directly to WordPress. No credentials or temporary review files are written. One local collector runs at a time; the existing leases, business binding, stable review IDs and result validation protect imports. Pausing a location or changing its identity invalidates queued results. Browser failures retain previously stored reviews.

## What remains experimental

Packaging does not improve Google's access or layout reliability. The prior Menjar Financial live test exposed **5 of 21** reviews in one headed run; repeated and headless checks exposed **0 usable cards**. The five-row WordPress storage test replayed that successful capture. This version does not claim a new successful live scrape, unattended reliability, or access to all reviews.

The collector reads rendered public pages only, in a fresh browser context. It stops at sign-in, CAPTCHA and access restrictions. No accounts, private endpoints, stealth, proxies or bypasses. Relative dates remain labels rather than invented timestamps. Administrator opt-in is not a Google storage license. Existing licensed and legacy sources are preserved separately.

## Existing external installations

Choose **External worker · compatibility** to retain an existing external worker. Its source remains bundled under `worker/`; you may run `run.cjs` from that folder with your existing configuration. The legacy transport is optional. Bundled mode requires no HTTP worker credentials.

## Where it runs

The WordPress ZIP runs on normal PHP hosting. The separate worker needs Node.js 22+, Playwright, Chromium, and a browser display. A normal visible browser is the default. Run it on an always-on computer or a server that supports a display (for example Linux with Xvfb). Ordinary PHP-only cPanel hosting cannot run this worker. Headless mode is optional but returned no review cards in our live test.

## Optional external-mode setup

1. Install/replace the plugin ZIP without uninstalling the existing plugin.
2. Copy the bundled worker directory outside the web root, open that copy and install:

```sh
npm ci
npx playwright install chromium
```

On Linux, install the browser's required operating-system libraries using [Playwright's browser installation instructions](https://playwright.dev/docs/browsers#install-system-dependencies). Keep the worker directory and environment file outside the web root.

3. In WordPress, create an application password for a dedicated administrator account under Users → Profile → Application Passwords. Use HTTPS. The password is only for this worker's WordPress connection; no Google credentials are used. Never paste it into a review field, commit it, or send it in chat. Revoke it when retiring the worker. WordPress application passwords inherit the user's access, so protect this credential like the administrator account.
4. Supply these private environment variables to your service manager:

```text
GRW_SITE_URL=https://your-wordpress-site.example
GRW_WP_USER=your-worker-wordpress-username
GRW_WP_APP_PASSWORD=your-private-wordpress-application-password
```

For an existing installed Chrome, optionally set `GRW_BROWSER_PATH` to its executable. For an explicitly selected headless test, set `GRW_HEADLESS=1`. No persistent browser profile or Google login is used.

5. Start the worker and keep it running:

```sh
npm start
```

A Linux display setup may run `xvfb-run -a npm start`, following [Playwright's headed-browser guidance](https://playwright.dev/docs/ci#running-headed). A service manager should restart the process after a machine restart or connection failure. The worker polls WordPress every five minutes and processes one business at a time. Credentials are sent only to the configured WordPress HTTPS origin; redirects are rejected.

6. WordPress → Google Reviews → Locations: edit/add your location, enter the full URL or Place ID, select **Google Maps browser collector · experimental**, enable experimental collection, and save. For a previously saved public location, **Enable browser collection** performs this switch without deleting reviews or widgets. Use the business's actual visible name as the business label; mismatched names stop collection.
7. Click **Download available reviews**. It queues a job; it does not immediately claim that reviews downloaded. Refresh the page after the worker finishes. Settings shows the worker's recent check-in.
8. **Sync settings** selects daily or every three days (default), with manual/5-day/7-day options retained. WordPress cron queues due jobs. A quiet cPanel site needs a real cron request to `wp-cron.php` every five minutes, as with the existing plugin scheduler. The worker must also remain running.
9. Use the existing carousel with **Connected review sources** and its normal shortcode. Public-page rows are labeled separately from licensed provider rows.

## Behavior and limits

- Up to 500 accessible cards per check, bounded by time; visible page limits remain authoritative. Partial results never mean a complete archive.
- Stable IDs from visible DOM deduplicate repeated checks and update changed text/replies. Only current business/source bindings display.
- Existing stored reviews survive failures and incomplete public views. Missing rows are not treated as deleted reviews.
- CAPTCHA, sign-in restrictions, access denial, identity mismatch, or expired worker jobs pause collection for manual attention. Network failures or changed layouts retry at the configured interval; they preserve stored content.
- Relative date labels are preserved as shown. The worker does not invent exact timestamps; date filters require actual timestamps, and undated records use database order. Ordering cannot be guaranteed to match all historical reviews.
- Rating-only reviews retain the rating without fabricated text. Avatar URLs are retained; image files are not mirrored.
- The original listing URL is retained. The prototype does not construct undocumented individual-review URLs.
- An elapsed 24/72-hour production cycle has not been tested. Scheduling and deduplication are verified with fixtures, separately from the live extraction test.

## One-shot diagnostic collection

```sh
node run.cjs --collect 'https://www.google.com/maps/search/?api=1&query=Google&query_place_id=ChIJy3bcVRzl9IgR5eOJcFC6ZCQ' --business 'Menjar Financial' --output reviews.json
```

This creates a local result file and does not import it into WordPress. Standard output reports only counts and the collection outcome. `node run.cjs --once` instead claims one existing WordPress queue job, collects, and submits its result. Use diagnostic files privately; they contain review content.
