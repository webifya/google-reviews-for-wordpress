# Menjar Financial Reviews · v1.7.0-rc.1

A dedicated WordPress plugin with three screens: **Connection**, **Reviews**, **Style Setup**. Only the Menjar Financial Place ID is accepted. Google Manager OAuth, encrypted short-lived review caching, pagination and the existing carousel renderer are reused from v1.6.0-rc.1.

The update preserves the private credential option, callback URI and saved style/shortcode IDs. Historical database records are retained without exposing unrelated source features. Existing scraper and stored-source schedules are stopped. The distributable excludes all collector code, browser libraries, analytics, maps and import/provider modules; old source and tests in Git are historical records.

[Project setup guide](API-SETUP.md). Build the PHP-only ZIP with `python3 scripts/package.py`; Node dependencies are only for development browser tests.

Validate on a disposable WordPress installation with `tests/menjar-baseline.php`, `tests/menjar.php` and `tests/menjar-browser.cjs`. Google API and browser review fixtures are synthetic; successful live authorization must be verified on the installed site.
