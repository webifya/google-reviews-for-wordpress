# Google review integration research — 9 October 2026

| Candidate | Credentials, ownership and cost | Available data and limits | Sync, rights, attribution and maintenance | Decision |
|---|---|---|---|---|
| Business Profile reviews API | OAuth business.manage, approved eligible project, verified business the account may manage; registered API users are not charged | Paginated reviews, maximum 50/page; author/avatar when supplied, text, rating, create/update times, replies, source total. Review resource names are not public permalinks | Owner management/reporting scope; temporary limited-content storage constraints; quota/approval/token lifecycle | Implement optional private owner tools, first-page scheduled refresh and paginated browsing; no permanent public import |
| Places API | API credentials and billing setup; business ownership not required | Small selected sample, up to five reviews; author attribution, text/rating/time and supplied links/images | Google display/attribution and caching rules; billing and API upkeep; no all-review pagination | Not added: cannot satisfy 100-review archive or mandatory-key-free preference |
| Official Share → Embed iframe | Existing official share iframe URL; no plugin API key/account authentication/subscription | Google controls displayed listing/review content; no individual-review extraction | Keep official presentation; Google updates content; iframe contacts Google | Retained; easiest official key-free display |
| Developer Maps Embed API | API key; no-charge usage per published API documentation | Official map modes, not a custom-card review export | Key restrictions and Google presentation | No key-free claim for this distinct API |
| Authorized third-party feeds | Provider-dependent credentials, commercial fee and verified licensing; ownership varies | Text/avatar/date/rating/links/count depend on contract and source | Daily retrieval only where feed and redistribution license permit; provider quota/availability maintenance | Trusted extension contract retained; no external vendor license claimed or bundled |
| Operator-authorized CSV/JSON | No Google key/account or plugin fee; actual publishing rights required | Whatever licensed fields operator provides; unlimited stored count in bounded batches | File imports manual; maintained local JSON attachment can sync; stable identities deduplicate updates | Retained and regression-tested |
| Manual testimonials | No Google credential; publishing permission | Operator's actual content; unknown fields stay unknown | No automated Google retrieval; visibly labeled testimonial | Retained |
| Public Maps URL, page markup, structured data | Public visibility does not provide a redistribution license | No documented general public all-review feed identified | Scraping/private endpoints are not used; metadata is not authenticated ownership | Save identifiers; safely resolve official share redirects only; manual confirmation |

Google owner API scope and limited caching prevent treating owner access as an unrestricted public-review license. Our implementation keeps raw responses temporary and outside public review storage. Agencies must assess Google's distinct supplemental-project and automated-access rules. These implementation decisions are not an assurance that any applicant qualifies or that any imported Google export may be republished.

Primary references:

- [Business Profile policies](https://developers.google.com/my-business/content/policies), updated 28 August 2026.
- [Business Profile prerequisites](https://developers.google.com/my-business/content/prereqs), [OAuth](https://developers.google.com/my-business/content/implement-oauth), [pricing](https://developers.google.com/my-business/content/pricing).
- [Reviews list reference](https://developers.google.com/my-business/reference/rest/v4/accounts.locations.reviews/list).
- [Places resource reference](https://developers.google.com/maps/documentation/places/web-service/reference/rest/v1/places), [Place Details Legacy](https://developers.google.com/maps/documentation/places/web-service/legacy/details), [Places policies](https://developers.google.com/maps/documentation/places/web-service/policies).
- [Official sharing/embed help](https://support.google.com/maps/answer/144361?hl=en), [Embed API billing](https://developers.google.com/maps/documentation/embed/usage-and-billing).
- [Maps user terms](https://www.google.com/help/terms_maps/), [Maps Platform terms](https://cloud.google.com/maps-platform/terms).

No approved Google project or verified business was supplied for testing. Mock fixtures verify application behavior, not live Google access, approval, quota or permissions. See [setup](GOOGLE-SETUP.md).
