# Local review storage and three-day synchronization

New locations default to **259,200 seconds (72 hours)**. Existing saved frequency values are preserved, including legacy intervals. New widgets already default to 100/newest/5-second autoplay/loop/hover pause/3-2-1 cards. The setting is a display limit, not an invented source count.

## Connect, configure and display

1. Open Google Reviews → Locations → Add Location. Paste a Google Maps Business URL or Place ID and use Find Business where configured lookup is available. A real key may incur charges. Compare the name/address/link and confirm the right business. Supply your own saved label/address; identifiers and original URL live in Advanced settings. Without a configured lookup source, confirm manually; that saves only identity, not review access.
2. Save location & connect reviews. Choose an available source, privately configure its authorization, test retrieval, inspect returned reviews and choose an interval. Places is the separate live-only fallback. A licensed adapter must enforce correct business association/rights and return original bounded fields/stable IDs. Configuration alone does not validate or schedule a collection.
3. Connection validation uses the existing bounded safe sync. It imports permitted records, validates each full page and grants a newly bound collection only after completion. Large pagination can require another Test connection call to resume; it is never falsely marked complete. Provider errors use fixed safe messages.
4. On a connected stored location, use Sync settings for **Every 1 day / Every 3 days (default) / Every 5 days / Every 7 days / Manual only**. Legacy intervals remain selectable as Existing schedule. Sync Now performs an authorized check and accurately reports new/updated/unchanged or continuing results. Manual only disables future automatic checks without removing permitted saved records.
5. Create/edit a widget, select locations, customize and copy `[google_reviews_widget id="123"]`. Optional `[google_reviews_widget id="123" limit="20"]` overrides the display count. Saved collections use database queries and no provider request during ordinary page rendering. Places widgets remain live placeholders/no-store responses and should be kept separate from stored collections.

## Job and data behavior

The hourly `grw_tick` dispatcher handles at most five due locations. Each per-location job holds an atomic option lock, fetches at most five pages of at most 500 rows, preserves a bounded cursor and cumulative statistics, and resumes later where necessary. Repeated/cyclic cursors and unstable/malformed licensed identities fail safely. Successful runs schedule the selected interval; failures use bounded retries/backoff. A busy or not-yet-due job does not trigger a duplicate fetch.

Review rows retain internal/location IDs, provider ID, stable external ID, hashed business/source binding, original author/avatar/rating/date/text/link/response, import/content-update/last-sync timestamps, content hash, visibility and the applicable license reference. Stable IDs are scoped to the current provider/business, so edits update that identity and different businesses cannot merge records. Unchanged pages refresh `synced_at` in bounded batches without reporting fake content updates. Indexes cover location/visibility/date/rating, provider/external ID, source binding and last sync.

Source/business/license/permission changes exclude stale licensed rows from connected/public/cache paths without silently deleting retained customer data. Explicit `removed_ids` are accepted only when the permanent adapter declares a reviewed deletion capability; the delete query is limited to its current provider/business. Actual removed count is reported. The permanent contract has no general time-limited TTL support; expired count is zero because no such feed is enabled, not proof an arbitrary vendor expiry was checked. A temporary feed must implement expiry/purge before being admitted. Places review content is never imported or archived; the owner-only Google cache keeps its separate bounded lifetime.

Statistics: last attempt/success/next check, retrieved/new/updated/unchanged (duplicates skipped), removed/expired, pages/duration, completion and last safe error. Next schedule is configuration, not evidence of a completed real three-day production cycle. A new empty collection or unusable response does not grant Connected. A confirmed removal leaving zero rows clears public cards and connected count.

## Reliable cPanel cron

WP-Cron works by default when WordPress receives traffic. For quiet sites, open cPanel → Cron Jobs and configure a five-minute trigger. Replace the example domain with the real site and use the host's available executable:

```sh
*/5 * * * * curl -fsS 'https://example.com/wp-cron.php?doing_wp_cron' >/dev/null 2>&1
```

Alternatively execute the real absolute `wp-cron.php` using the host's PHP binary. No Node, Python, VPS, browser, Supabase or automation subscription is needed. Start with normal WP-Cron enabled. Disable traffic-driven cron with `DISABLE_WP_CRON` only after verifying a working server trigger. Keep plugin/host cron logs and confirm the next check actually advances after an eligible due job. The five-minute trigger dispatches due work; it does not change the location's three-day interval. A full 72-hour upstream cycle remains unverified in this delivery.

## Analytics

Fresh installations enable analytics and explicit consent requirement. Upgrades preserve stored choices. Integrate the consent manager with `window.grwAnalyticsConsent = true` before the script or the `grw:consent` event. DNT/GPC, revocation, expiring random session estimates, rate budgets, administrator/preview exclusion and configured retention remain enforced. Synchronization preserves widget IDs and analytics rows. Settings → Privacy and data retention controls opt-out and retention; Analytics reports per-widget/location/day/month activity.
