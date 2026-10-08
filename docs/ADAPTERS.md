# Authorized adapter contract

Register a trusted `Webifya\GRW\SourceAdapter` instance with `grw_source_adapters`. Never turn arbitrary Maps links into scrape targets.

```php
add_filter('grw_source_adapters', function (array $providers): array {
    $providers['licensed_source'] = new YourLicensedSourceAdapter();
    return $providers;
});
```

Implement:

- `label(): string`
- `supports_sync(): bool`: true only for a licensed, working retrieval mechanism.
- `fetch(array $location, ?string $cursor)`: return `['reviews' => [...], 'cursor' => $nextOrNull]`, or `WP_Error`. Maximum 500 reviews per page. Cursors must advance and be at most 2,048 bytes.

Use `grw_location_config` to validate any provider-specific fields on location saving. Keep secrets out of location JSON, browser responses and diagnostics. Prefer trusted server constants or encrypted integration-owned credential storage.

`Sources::request($url, ['api.licensed-source.example'])` enforces an exact code-supplied HTTPS allowlist, WordPress safe HTTP validation, no redirects, 15-second timeout and 1 MB response ceiling. An administrator-entered hostname is not a trusted allowlist. Do not implement bypasses for rejected internal/private hosts. Validate response schemas and respect rate limits, attribution and data-retention rules.

Rows use the documented import fields. Stable external IDs are essential for update semantics. No adapter can authorize publishing by simply setting a source name or verification flag; the core intentionally renders no fabricated verification marks. Sync imports retain existing moderation states and preserve previously saved rows on temporary errors. Missing upstream rows are not deleted automatically.

The built-in `local_json` adapter reads a rights-confirmed media attachment within uploads. A content fingerprint in its cursor restarts pagination if the feed changes during a run, preventing stale offsets from losing rows. It serves as a working API-free integration without remote-network permissions.


## Phase 6 stored-provider additions

The existing `PermanentReviewProvider` remains the contract for reviewed permanent storage/public display; unsupported time-limited policies are rejected. New licensed imports include provider ID, business binding, license reference and `synced_at`. Stable external IDs must be nonempty and at most 190 bytes. New sources are unscheduled until a complete successful collection; default frequency is 259,200 seconds. Optional `removed_ids` (at most 500 scalar IDs per page) require `policy()['deletions'] === true` and are scoped to the current provider/business. Declare this only against the actual documented contract. A future TTL feed needs provider-specific expiry/purge before registration. See [Phase 6 source guide](PHASE6-SOURCES.md) and [scheduling](PHASE6-SYNCHRONIZATION.md).
