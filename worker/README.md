# Experimental browser collector

This worker opens the public Google Maps page, selects Reviews, expands visible text, reads rendered cards, and sends accessible reviews to WordPress. It uses no Google API key, Google account, intercepted response data, private endpoint, proxy rotation, or CAPTCHA solver.

**Prototype limitation:** one live visible-browser run on Menjar Financial returned 5 genuine reviews out of 21 advertised. Other visible-browser and headless runs returned no review cards. Downloading every review or dependable unattended operation is not established. Google can require sign-in or restrict the public view. Revising the project requirement does not grant a Google copying or republication license.

## Where it runs

The WordPress ZIP runs on normal PHP hosting. The separate worker needs Node.js 22+, Playwright, Chromium, and a browser display. A normal visible browser is the default. Run it on an always-on computer or a server that supports a display (for example Linux with Xvfb). Ordinary PHP-only cPanel hosting cannot run this worker. Headless mode is optional but returned no review cards in our live test.

## Setup

1. Install/replace the plugin ZIP without uninstalling the existing plugin.
2. Extract the separate worker ZIP, open its directory and install:

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
