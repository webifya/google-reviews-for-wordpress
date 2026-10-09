# Revised review collection requirement

The user's October 9, 2026 instruction replaces Phase 7's permitted-method-only requirement with development of an **experimental public-page browser scraper**. This revision authorizes the prototype and its live test; it does not establish permission from Google or change Google's terms.

The collector visits the administrator-selected public Google Maps listing, opens its visible Reviews tab, expands displayed review text, and collects accessible cards. It must stop at CAPTCHA, authentication requirements, access denial, or mismatched business identity. No private endpoints, reverse engineering of network payloads, stealth, proxy rotation, or challenge solving is included.

Reuse the existing WordPress review database, source bindings, carousel, moderation, analytics and scheduler. Support a 24-hour or 72-hour interval. A separate browser worker handles page navigation; WordPress handles authenticated job claims/results, storage, update/deduplication, and cron. Preserve old data and credentials.

Always distinguish advertised listing count, accessible current subset, accumulated stored reviews and displayable rows. Do not claim a full archive, licensing, reliable production synchronization or stable readiness from fixture results. Report the real count retrieved and any failed live tests.

Live verification on the supplied Menjar Financial link: a normal visible-browser run retrieved 5 of 21 advertised reviews. Other runs exposed no review cards; Google also requested sign-in when attempting to open the remaining public-view reviews. All-review collection and unattended reliability remain unverified.
