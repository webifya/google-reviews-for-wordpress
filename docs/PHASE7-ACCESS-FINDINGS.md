# Direct Google Maps review collection — findings, 9 October 2026

**Blocked: no reviewed documented, reliable, permitted API-free mechanism satisfies public/non-owned listing → all accessible individual reviews → authorized independent WordPress storage/custom carousel → recurring three-day discovery.** Public readability is not bulk export permission. This is a conclusion from the documented sources, not a proof that every possible negotiated agreement is impossible.

| Question | Evidence-based result |
| --- | --- |
| Does Google permit automated extraction? | End-user Maps terms §2 restrict copying (subject to stated exceptions), mass downloading/bulk feeds and redistribution. Platform terms §3.2.3 separately prohibit extracting Maps content for use outside its services, including saving user reviews. No direct bulk-review exception supporting this product was established. |
| All historical reviews? | No documented API-free review pagination/feed found. Places returns at most five selected reviews. Managed Business Profile review pagination is owner-authorized API access, excluded from this requested setup. A listing total never proves access to that many individual records. |
| Stable IDs? | Official API schemas provide review identifiers, but their existence does not authorize extracting private/internal Maps interfaces. No authorized API-free ID feed was established. |
| Local storage and custom publication? | Attribution alone does not authorize independent copying/archiving. Places caching is restricted; Place ID is a documented caching exception, not review-text permission. A separate reviewed agreement could change rights but none was supplied. |
| Ordinary cPanel? | Local validation/storage/rendering and bounded WP-Cron work with PHP/WordPress. No permitted direct downloader was established, so hosting compatibility/reliability for one cannot be claimed. |
| Non-owned business? | Public Maps links identify listings. They do not provide account entitlements, review export or independent-storage rights. |
| Recurring/new/changed/deleted reviews? | The existing authorized provider contract and scheduler are operational with fixtures; no genuine API-free feed or elapsed live 72-hour cycle was verified. |
| Authentic live retrieval count? | Zero live review retrievals attempted, zero authentic records downloaded during Phase 7. Actual accessible count is unknown, not zero. |

Official sources reviewed:

- [Google Maps end-user additional terms](https://www.google.com/help/terms_maps/), last modified 27 January 2026, §§1–2. Public display permissions are conditional; copying and bulk feeds are expressly restricted. [Geo permissions](https://about.google/brand-resource-center/products-and-services/geo-guidelines/) describes permitted Maps presentation, not an individual review export endpoint.
- [Maps Platform terms](https://cloud.google.com/maps-platform/terms), §3.2.3(a)–(b). These govern platform customers; they are not substituted for the separately reviewed end-user terms. Neither establishes this direct archive workflow.
- [Places schema](https://developers.google.com/maps/documentation/places/web-service/reference/rest/v1/places), `reviews` maximum five, relevance ordering and review resource name. [Places policies](https://developers.google.com/maps/documentation/places/web-service/policies), pre-fetch/cache restrictions and Place ID exception.
- [Business Profile reviews](https://developers.google.com/my-business/reference/rest/v4/accounts.locations.reviews/list), OAuth-managed location path and pagination; [Business Profile policies](https://developers.google.com/my-business/content/policies) describe applicable restrictions.
- [Phase 5 nine-vendor review](PHASE5-PROVIDER-COMPARISON.md) remains an account/rights investigation. Marketing, a paid widget or a JSON endpoint alone is not evidence of non-owned Google archive rights. No new vendor connection is represented as operational.

Available alternatives require changing the no-provider/no-API constraint or obtaining explicit rights: a usable licensed provider account with a documented endpoint and written independent-storage/custom-display/retention permission; or the official limited Places API if the user later chooses it. Neither is silently substituted in the new UI. Existing Places widgets continue their previously configured live-only path for compatibility. A map iframe is not a replacement review carousel.

Exact blocker: supply a documented operational source and reviewed authorization covering the chosen business, accessible history/counts, stable IDs, incremental/refresh/deletion semantics, storage duration, custom commercial display and attribution. Then implement the actual connector and perform genuine retrieval/two timed checks/a full 72-hour cycle. No such account/contract is available; no secrets, purchase or vendor contact were requested.
