# Phase 6 installation, upgrade and troubleshooting

Install the candidate ZIP through WordPress Plugins → Add New → Upload Plugin, activate, then follow Locations → Add Location → Connect Reviews → Widgets → Copy shortcode. Requires WordPress 6.4+, PHP 8.1+, MySQL/MariaDB. SQLite is used only for isolated local/browser testing; native compatibility is verified separately.

Upgrade from v1.3.0-rc.1: back up the database/plugin directory, upload the new ZIP and choose Replace current with uploaded. Do not uninstall. Versioned migration adds provenance and last-sync columns/indexes with empty/null defaults, preserving old values, location/review/widget IDs, styles, shortcodes/pages, analytics, settings and history. Repeated migration is idempotent. New defaults do not overwrite saved intervals or analytics opt-outs. Retained licensed rows need a completed permitted source sync before being identified as current connected records; legacy records retain their original provenance.

Rollback: restore the old plugin ZIP and the pre-upgrade database backup. Do not uninstall to roll back. Changing WordPress salts requires private credential reconfiguration. Existing legacy shortcode APIs remain; creation UI stays retired. Existing carousel designs/styles stay intact.

| Symptom | Meaning and next step |
| --- | --- |
| Location saved, no reviews | Identity saved only. Connect a supported source; a public Maps link is not extraction/storage permission. |
| Find Business needs a key | Configure restricted Places credentials privately in Settings → Review connections, confirm terms and approve any billable test budget. Or confirm identity manually without claiming retrieval. |
| Five live reviews | Places' selected subset, not complete history. It cannot populate a local archive. |
| No eligible stored source | No licensed connector/account is installed. Obtain the documented account/rights listed in the source guide; vendor widgets alone do not establish them. |
| Source requires validation | Test a real successful nonempty collection with correct business/rights. Large pagination may need another bounded test to complete. |
| Authentication expired / quota / request limit | Reconnect privately or inspect the provider plan/quota. Fixed safe errors preserve raw secrets; configured retry/backoff applies. |
| Existing local reviews during outage | Prior permitted records remain usable; failure does not become a successful update. Review last error/attempt and provider authorization. |
| Schedule disabled | Manual only, inactive, unsupported source or unvalidated connection. Sync settings requires an operational stored source. |
| Quiet site missed check | Verify host cron/WP-Cron execution and plugin diagnostics; configured next time does not prove host execution. |
| Retained count exceeds connected count | Old/import/other-provider data is preserved; only current authorized bound collection populates connected cards. Do not relabel retained reviews. |
| License/source identity changed | Stale licensed content is hidden. Complete a valid permitted source sync; time-limited deletion/expiry rules require a specific supported adapter. |
| No analytics | Check saved opt-out, widget analytics flag, explicit consent, DNT/GPC and administrator/preview exclusion. Fresh-enabled analytics still waits for consent. |
| Cached live placeholders fail after key change | Purge page caches; do not cache the live POST response or override no-store. Stored widgets use local queries with plugin invalidation. |

No genuine Menjar collection, non-owned licensed archive or real 72-hour upstream cycle was verified. [Source alternatives and owner action](PHASE6-SOURCES.md), [schedule/storage guide](PHASE6-SYNCHRONIZATION.md).
