# Phase 5 integration decision — 2026-10-09

Historical Phase 5 local work was interrupted before publication and incorporated into Phase 6 (1.4.0-rc.1). Its daily-default descriptions refer to the earlier proposal; Phase 6 defaults to 72 hours. No v1.3.1 release was published.
**Decision: retain the implemented official Places fallback and ship a reliability/provenance candidate, 1.3.1-rc.1. The full non-owned 100+ daily integration is blocked.** No new vendor adapter is registered or advertised as operational. This patch milestone accurately describes implemented fixes rather than implying a completed 1.4 provider feature.

The [provider comparison](PHASE5-PROVIDER-COMPARISON.md) reviews Google and nine vendors. Free supported Google access is limited to the Places selection or authorized managed-business tools. Free vendor tiers do not establish the desired autonomous, independently licensed historical archive. Technical API/feed leads exist, but none has an available account plus an explicit reviewed grant for this business/use. Trustindex has no public API. Buying a widget subscription does not establish extraction rights.

SociableKIT is the strongest low-cost **conditional research lead**: its official developer tutorial documents a JSON feed and Google review fields; published paid source pricing starts at $10/month. Its terms leave third-party content rights with the customer and restrict third-party/contractor service use, so it is awaiting licensing confirmation, suitable account access and daily/history entitlement. EmbedSocial's API-listed paid/partner offering is a secondary contractual lead. Featurable, Trustmary and Taggbox likewise require entitlement/rights clarification. None is recommended for purchase as a proven solution.

Places remains **implemented but awaiting credentials/live verification**. It can display up to five selected reviews for an eligible public listing without Business Profile owner OAuth. It cannot deliver the requested historical archive. GBP remains **unsupported for arbitrary non-owned access**; the existing owner-facing tool is preserved. Private scraping, intercepting vendor plugins, undocumented endpoints, CAPTCHA/proxy circumvention and republishing demo content were excluded.

## Implemented safe groundwork

- Add provider ID and hashed business/source binding to licensed rows. Stable review IDs are scoped to that binding. Changing provider, business, license reference, active state or storage permission excludes stale licensed rows from public queries, including cached and legacy-widget paths, without deleting or relabeling retained data.
- Require literal boolean storage/display grants. A license URL is a trusted code declaration, not an automated legal verification service. Only a reviewed permanent-storage adapter may use this workflow. Time-limited contracts require a specific expiry/purge implementation first.
- Validate each entire page before import, require stable licensed review IDs, reject repeated/cyclic cursors and retain bounded five-page jobs with resumable counts/duration.
- Grant a new collection only after a complete successful fetch. Failed/empty/configuration-only connections do not falsely validate. Existing valid records survive provider errors; partial newly bound collections remain hidden until a complete validated pass.
- Show actual observed/retrieved accessible counts, storage category, accurate manual/scheduled/daily labels and safe actionable errors. Batch admin counts; cache source-location metadata only for the current request, with invalidation after mutations.
- Preserve Places request-only data, owner tools, legacy records, shortcode IDs/styles, carousel, analytics, consent, encrypted credentials, endpoint allowlists, locks and retries.

## Concrete owner action

For the limited live path: configure an enabled/billing-approved Places project and restricted server key privately under WordPress **Settings → Review connections**; confirm the applicable agreement/public notices; explicitly approve a test cost ceiling before requests. Do not paste the key into chat.

For the main goal: obtain the written vendor confirmation listed at the end of the comparison, the applicable documented endpoint/schema and a usable licensed account/feed. An ordinary widget subscription or a claim of “unlimited reviews” is insufficient. After these exist, implement the vendor-specific authentication/identity/fields/pagination/limits/attribution/retention adapter, validate actual Menjar Financial data twice and observe a real 24-hour cycle before claiming completion. No owner purchase or vendor message was sent during this work.
