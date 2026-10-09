# Phase 7 starting audit

Remote/main and published starting candidate v1.4.0-rc.1 both match `a62a9b991f6c8df8665d3b39abf8b885eb2b2d64`, verified before editing. Working tree was clean.

Reviewed primary and secondary administration, Locations, Sources, Sync, Reviews, Installer, renderer/shortcodes, encrypted Google integrations, analytics, security, package scripts, upgrade snapshots and existing tests. Reused the five existing database tables, provider grant/scope protections, bounded sync machinery and unchanged carousel assets.

Findings: the primary location identity lookup called Places; the six-step connection wizard defaulted to Google API/billing setup; Settings exposed key/OAuth setup. Those UI dependencies conflict with the new request. Existing live widgets depend on their saved encrypted settings and must be retained. No API-free retrieval provider was present. A public listing is not a supported archive feed. Count labels conflated a locally available collection with an unknown provider/business total. A temporary sync failure retained permitted content but incorrectly removed its Connected status.

Phase 7 deliberately changes setup administration and status/count labels, adds local-only identifier validation and keeps legacy integrations for already configured widgets. No Google integration or credential record is deleted, no old location/widget configuration is rewritten, and no schema change is required. API backend compatibility does not mean the new setup depends on it. No direct scraper or fake download action is added.
