# Google Reviews for WordPress · v1.6.0-rc.1

This release candidate adds a private Google Business Profile Manager connection to the existing plugin. Use an approved Google Cloud project, an OAuth web client and a Google account that manages the verified business. Account and business lists are paginated, and connecting checks review access before saving the location. Matching an existing Place ID preserves its widget assignment.

[Private API connection setup](API-SETUP.md) explains the enabled APIs, exact redirect URI, consent setup and website publication choice. The API connection needs PHP 8.1+, OpenSSL and HTTPS. It does not need Node.js or Chromium. Credentials and short-lived Google review caches are encrypted with the WordPress authentication salt; client secrets and access/refresh tokens are never returned through the admin state, public HTML or diagnostics.

Admin review browsing supports all available API pages, including rating-only reviews and business replies. Optional website display follows API pages up to the configured widget limit through signed, no-store responses. Original review text and attribution are preserved. Review content is cached for at most 15 minutes for request performance and never imported into the permanent review table. Refresh is on demand after expiry. Manager status and a checkbox do not grant additional Google display or storage rights; public display is disabled by default and must comply with the applicable Google agreement.

The existing shortcode, carousel designs, stored reviews, imported/licensed source contracts, analytics, and bundled experimental public-page collector are preserved. [Collector setup](worker/README.md) is optional and separately requires Node.js 22+, Chrome/Chromium and PHP process execution. Public-page scraping remains experimental and may expose only a subset. Daily/three-day stored-source schedules are separate from the Manager API's temporary cache.

## Build and validation

Run `npm ci`, `npm run check`, and `python3 scripts/package.py` to build one installable ZIP. The package includes the PHP plugin, frontend/admin assets, API setup guide and existing collector libraries; development tests, reports, screenshots and credentials are excluded.

`tests/manager.php` uses synthetic HTTP fixtures to verify OAuth state replay protection, server-side token refresh, API selection, 50+21 review pagination, encrypted caches, publication revocation, daily public request budgets and preservation of the permanent review table. `tests/manager-browser.cjs` verifies the private form and renders the actual PHP transport fixture pages into the live carousel. Existing regression suites and upgrade checks are retained. These checks do not prove successful live Google authorization: that must be completed privately on the installed WordPress site.

Earlier phase documents and release reports are historical records, not current API setup instructions.
