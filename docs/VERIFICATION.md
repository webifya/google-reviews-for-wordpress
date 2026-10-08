# Verification record

Status: 1.0.0 candidate; final verification results below distinguish executed checks from unverified environments.

## Executed locally

- PHP 8.4.23: all plugin PHP files linted successfully.
- WordPress 7.1.3 with the official SQLite Database Integration drop-in: **55 integration assertions passed**.
- A second fresh WordPress installation, plugin extracted from the generated installation ZIP: **55 assertions passed**.
- Coverage: activation/table installation/idempotence, cron registration/deactivation, location creation/update, URL/embed validation, SSRF allowlist, Unicode imports, duplicate detection/stable-ID updates, invalid ratings/dates, filtering/order/moderation, multiple widgets, sanitized CSS, shortcode overrides, accurate subset summaries, authorized adapter sync, locks/retries/data preservation, local JSON feed updates/path checks, capability/REST isolation, analytics consent/collection/dedup/origin/token checks, CSV formula safety and uninstall retention/cleanup.
- Chrome: expanded suite also covers autoplay, reduced motion, single-review navigation and touch pointer events. Admin browser checks cover all eight screens, saving/live styling, nonce enforcement, real visible-impression events, deduplication and complete guided onboarding.
- Chrome, Firefox and WebKit: responsive screenshots and carousel interaction checks at 375, 768, 1024 and 1440 pixels. WebKit exercises the Safari rendering engine; the installed Safari app itself was not automated.
- Default design visually inspected against the supplied reference. Composition matches gray section, centered titles, white rounded cards, raised avatars, stars and circular controls. Authorized-import attribution adds a footer; screenshot identities and verification marks were not copied.

## CI

The first GitHub run executed all four MySQL 8 / PHP 8.1 and 8.4 / WordPress 6.4.7 and latest integration combinations successfully, installing from the generated ZIP. The final commit is separately rechecked. Browser CI exercises Chromium, Firefox and WebKit and emits screenshots. Local database testing used SQLite; MySQL testing ran in GitHub Actions. MariaDB and an actual cPanel deployment are not independently qualified.

## Limits and follow-up work

- No arbitrary public Google Maps URL can retrieve individual reviews; no Google scraper or third-party scrape service is included. Optional licensed Google integration requires a separate adapter and its own credentials, licensing/retention/attribution evaluation.
- Local JSON feed is limited to 10 MB per file and reads its array into memory before slicing; large source feeds need a streaming provider. CSV/JSON request batching is supported. Backend reviews are paginated; browser CSV export accumulates the export before download.
- Widget display limits are bounded to 10,000 for page safety; review storage has no count cap. Large widgets, random ordering on very large datasets and very many locations/widgets need load testing on the intended host.
- WP-Cron is traffic dependent. cPanel/server-cron instructions are documented; no real cPanel deployment was tested.
- Analytics are estimates, not fraud-resistant measurement. Random tokens can be rotated; rates are counts per impression, so repeated interactions may exceed 100%.
- The core has a consent event integration; no specific third-party consent-manager certification. External iframe/avatar loading must be handled by the site's privacy setup.
- Per-site installation only; network activation and network-wide cleanup have not been qualified.
- Core frontend strings and admin control labels use WordPress translation mechanisms with a POT catalog. No completed translated locale packs ship; some dynamic admin help/status sentences remain English.
- The default live preview uses real saved content. Test/sample fixtures remain in development files and never populate a customer's site on activation.
- Diagnostics/settings import is deliberately limited to non-secret privacy settings; widget records in the settings export are for backup/reference, not automatically restored to avoid ambiguous location associations.

Recommended improvements: qualify a licensed remote provider, add streaming imports/exports for very large datasets, finish full admin-language coverage, add a consent-manager integration package, and perform host-specific load/accessibility audits.
