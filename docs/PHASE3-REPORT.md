# Phase 3 audit and release verification

Release candidate: **1.2.0-rc.1**. Final remote/release identifiers and completed test evidence will be recorded after CI and upgrade verification.

## Audit findings and changes

- Location identity was conflated with review retrieval status. Public listing, map configuration, review source and synchronization now have separate structured values and friendly presentation, including legacy rows.
- The Locations page permanently exposed a large technical form. It now uses searchable/filterable cards, nine-record pagination, and a four-step native dialog with tab-scoped draft recovery.
- Official embeds only accepted a URL and had no same-page preview. The editor now accepts a single official sharing iframe or its source, validates it, discards supplied markup, and shows Map and Reviews preview tabs.
- Metadata edits could reset a feed's schedule. Public details, embeds and logos now preserve the source schedule/status/cursor. Source identity changes invalidate the cursor.
- Google account configuration occupied Synchronization and public-listing actions. Location Connections separates public listings, optional owner authorization and authorized feeds.
- Raw statuses, oversized forms and inconsistent controls were visible throughout administration. All nine screens now share plugin-scoped navigation, cards, readable controls, mobile table cards and dismissible notices.
- Visual QA found a WordPress global preview float breaking the mobile builder. Explicit scoped layout fixes keep the preview and publish panel readable.
- Preview errors remained after correcting invalid input. Successful validation now clears the stale error.
- The quoted “Your new Google reviews have been downloaded” / “Reply with ChatGPT!” notice is absent from this repository. Its actual installed-plugin owner cannot be identified without access to the user's site. No unrelated notices are hidden.

## Public listing behavior

Any business can be recorded without ownership, OAuth or an API key. Save a Maps URL or manually enter its name and address. Official short links use at most three allowlisted redirects; this does not fetch review HTML or verify ownership. A name/address search link can be generated with a supplied Place ID or numeric CID. These links do not prove automatic business resolution. The Menjar Financial address was used only as a clearly labeled fixture; no actual business identity was remotely resolved.

Google documents key-free Maps URLs in its [Maps URL guide](https://developers.google.com/maps/documentation/urls/get-started) and the sharing embed workflow in [Google Maps sharing help](https://support.google.com/maps/answer/7101463?hl=en). A custom reviews link is displayed only when supplied as a valid Google Maps listing/reviews URL.

## Owner connection and synchronization

Owner authorization remains optional and requires an approved Google project and access to a verified business. Credentials are encrypted, REST status omits secrets, account/location lists are permission-checked through Google's API, and selected business resources require confirmation. UI distinguishes a saved token from validated access and records the last successful API validation. Expiration/revocation produces attention/reconnect guidance. Disconnect removes credentials and temporary Google cache while preserving public listings and authorized imports.

Live OAuth is unavailable under the user's fixture-first testing preference. Google original reviews remain temporary, owner-only data; they are not silently republished as public review cards. Local authorized JSON media feeds and permitted adapters support scheduled synchronization. Public links, map iframes, file imports and manual testimonials do not automatically retrieve Google reviews.

## Map and review previews

Only HTTPS `www.google.com/maps/embed?pb=…` sharing URLs with a nonempty sharing payload are accepted. Multiple iframes, scripts, event handlers, `srcdoc`, foreign hosts, unsupported query forms and programmable Embed API URLs are rejected. WordPress's HTML tag processor extracts the source; renderers build a fresh escaped iframe. Heights are bounded and responsive.

The editor provides loading, navigation, blocked/error and timeout guidance. A cross-origin frame load event cannot prove visible map content, so the interface asks for visual confirmation. Browser tests use explicitly labeled synthetic iframe transport fixtures and blocked requests; they do not assert that a real Google business loaded. Reviews Preview queries actual saved data and remains empty when none exists.

## Shortcodes and upgrade

```text
[google_reviews_widget id="123"]
[google_reviews_widget id="123" limit="20"]
[google_reviews_grid id="123"]
[google_reviews_map location="456" height="350"]
[google_reviews_combined id="123"]
```

The original widget shortcode and block remain compatible. Map visibility and outbound Maps-link visibility are independent widget settings, disabled by default for existing widgets.

Back up WordPress files/database. Upload the new plugin ZIP via Plugins → Add New → Upload Plugin, select Replace current with uploaded, and keep the plugin active. Do not uninstall to upgrade. Schema updates derive presentation for old records without rewriting saved location/embed/review/widget/settings content.

## Security and remaining limits

Capability checks and REST cookie nonces protect administration; input shape checks, escaped output, prepared SQL, Google host/path allowlists, bounded SSRF-safe resolution, OAuth state binding, encrypted tokens, protected moderation and deletion safeguards remain covered. Map rendering performs no server-side Google review retrieval.

No live Google OAuth or actual Menjar embed is verified. Business identity confirmation remains manual when redirect resolution cannot provide identity. Official Google embeds contact Google and require appropriate site privacy/consent setup; no universal consent-platform blocker is included. Admin location filtering/pagination is client-side over the privileged state response. Multisite network lifecycle remains unsupported. Real MySQL/MariaDB and browser CI results will be recorded below after execution.
