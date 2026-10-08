# Phase 4 provider decision — researched 2026-10-09

This record distinguishes implemented integration code, documented provider capabilities and live validation. No genuine Google credentials, verified-business access or licensed feed account was supplied. Live validation remains outstanding.

| Method | Non-owned listing | Review access | Daily stored sync / retention | Connection requirements |
| --- | --- | --- | --- | --- |
| Places API (New), implemented | Yes, subject to applicable agreement | At most five reviews selected by Google; no history pagination | Not offered. Reviews stay in request memory and are fetched for display | Billing-enabled project, enabled API, restricted server key, public terms/privacy and agreement eligibility |
| Business Profile, retained secondary tool | No; managed verified business | 50 per page; pagination through accessible reviews | Owner dashboard only. No public collection; temporary 15-minute cache | Approved project and OAuth authorization with managed-business access |
| Featurable, researched, not integrated | Public docs do not establish a non-owned feed | Advertises 15 on free plan and higher paid counts; widget endpoint documents batch maximum 50 | No independently verified permanent storage/republication grant for this plugin | Their account/Google connection and appropriate API plan; license confirmation needed |
| Trustmary, researched, not integrated | Not established by reviewed integration page | Marketing describes review imports/widgets and an open API; no verified non-owned licensed feed/count contract | No verified plugin redistribution/retention contract | Provider account, contract and API capability confirmation |
| Trusted permanent-license adapter, extension point | Depends on reviewed contract | Declared maximum, pagination and history vary by provider | Daily only when permanent storage and public display are expressly granted | Trusted integration code, actual provider access and documented upstream rights |
| Retained local JSON/import/manual data | Does not retrieve Google data | Existing authorized records | Existing local adapter remains compatible | Legacy publishing permission; no assertion of authentic Google provenance |

Places is the chosen default because its official documented API supports individual review display for public listings without business management access and does not impose a plugin subscription. It supplies a selected subset, not a full archive. [Place reference](https://developers.google.com/maps/documentation/places/web-service/reference/rest/v1/places).

Places permits display without a corresponding Google map, with mandatory attribution. Its standard caching exceptions do not grant storage of review text; the 30-day coordinate allowance does not extend to reviews. This implementation consequently avoids cron prefetch, review tables, transients, stored review identifiers/hashes and page-cached review HTML. Regional agreements, including EEA terms, require separate eligibility review. [Service terms](https://cloud.google.com/maps-platform/terms/maps-service-terms), [display policy](https://developers.google.com/maps/documentation/places/web-service/policies).

Google-selected content is visibly separate from retained records. Cards credit the supplied author, available avatar/profile and third-party providers; source links are not fabricated. Visit month/year is shown when present. The selection, ordering and rating filter are disclosed. Native review text is escaped for safe display; the provider's original text is used rather than a locally rewritten review.

Current global usage pricing: reviews trigger Place Details Enterprise + Atmosphere, listed with 1,000 free monthly events and $25/1,000 in the first paid tier; rating/count lookup triggers Enterprise, 1,000 free events and $20/1,000 in the first paid tier. Billing, region and tier determine actual costs. Maps URL name lookup uses Text Search Enterprise: 1,000 free monthly events and $35/1,000 in the first paid tier. It returns up to three candidates requiring explicit confirmation. Multiple widgets can issue separate calls; previews also consume budget. [Field-mask billing](https://developers.google.com/maps/documentation/places/web-service/place-details), [price table](https://developers.google.com/maps/billing-and-pricing/pricing).

Business Profile requires appropriate account authorization and has content-storage restrictions. Keeping its existing integration owner-facing avoids treating that access as a grant to aggregate and republish public cards. [Business Profile policy](https://developers.google.com/my-business/content/policies), [reviews endpoint](https://developers.google.com/my-business/reference/rest/v4/accounts.locations.reviews/list).

Featurable has documented widget/API endpoints, but its terms describe Google account access and do not establish the required non-owned, permanently licensed data feed. Current displayed annual-billing rates: Pro $29/month equivalent ($348/year), Enterprise $79/month equivalent ($948/year), free plan 15 reviews/one location. Do not equate advertised unlimited paid reviews with proven access or a permanent redistribution license. [API docs](https://featurable.com/docs/widgets/), [pricing](https://featurable.com/pricing), [terms](https://featurable.com/terms). Trustmary's integration listing is evidence of an offering, not proof of the necessary data contract: [integrations](https://trustmary.com/integrations/).

## Extension contract

Register trusted adapter code through `grw_source_adapters`. Implement `Webifya\GRW\PermanentReviewProvider`, which extends `SourceAdapter`, with `label()`, `supports_sync()`, `fetch($location, $cursor)` and `policy()`.

`policy()` supplies:

- `permanent_storage` and `public_display`: true only after the upstream contract has been reviewed.
- `license_reference`: a valid HTTPS reference to the applicable publishing/storage grant.
- `ownership`, `api_key`, `requirements`: access requirements.
- `maximum`, `history`, `new_reviews`: accurately documented access, not the business's entire public count.
- `attribution`: exact source/author requirements. A provider-specific renderer is needed if the generic source/author fields cannot meet them.

The permanent-storage contract deliberately excludes time-limited licenses. Such a provider requires expiry/purge behavior and its own validated adapter/UI before production use. Do not register temporary Google API responses as a permanent feed. Code registration is an administrator-trusted declaration, not a license verification service.

`fetch()` returns up to 500 scalar-field review records and an optional bounded next cursor, or a sanitized `WP_Error`. Use stable `external_id`, unmodified `content`, real `reviewer`, `rating`, ISO `review_date`, supplied `avatar`, `permalink`, `source_name` and `source_url`. Pagination advances; the scheduler limits work per job and resumes its cursor. New retrieved records use a distinct licensed source type; retained adapter/import/manual records cannot validate or populate the connected collection. The successful sync retains its provider license reference. Store keys privately; use exact code-owned HTTPS host allowlists, no redirects and response-size bounds. Never expose private response bodies or keys in errors/logs.

The connection wizard tests retrieval before preview and daily enablement. Empty results cannot validate a connected review collection. Daily eligible sources must have a real successful sync and stored accessible records before Connected is shown. The fixture adapter in tests exercises this path; it is not a real licensed service.

## Remaining acceptance blocker

No reviewed, accessible provider in this session proves the full non-owned listing → 100+ individual reviews → daily stored archive → custom carousel sequence. A real Places key can validate the implemented five-review live option. The full-history daily workflow needs a provider contract that expressly permits the needed access, storage, refresh and custom display, followed by live integration testing. Do not publish this candidate as stable on fixture evidence alone.
