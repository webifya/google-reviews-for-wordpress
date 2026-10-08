# Phase 4 delivery — Google Reviews for WordPress 1.3.0-rc.1

**Candidate release. Live Google access remains unverified. The full non-owned business → 100+ reviews → daily stored synchronization acceptance requirement is still blocked by provider access and storage rights.**

The official Places integration is implemented, with up to five reviews per business, retrieved live without saving Google content. No genuine API key or licensed feed account was available. All displayed test reviews and screenshots are explicitly synthetic transport fixtures.

## 1. Removed primary workflows

Removed manual testimonial creation/editing, CSV/JSON review uploads, upload history and file-size notices, map builders/embed controls/previews, map-only template selection for new widgets, map shortcode generation and the former upload-driven onboarding. Legacy records, authorized local feeds, backend compatibility routes and saved map displays remain intact. No migration silently converts their provenance into authentic Google reviews.

## 2. Navigation

Dashboard, Locations, Reviews, Widgets, Analytics, Settings. Review connections, synchronization and diagnostics are secondary Settings screens, registered without primary sidebar entries. The location form leads directly into Connect Reviews.

## 3. Research and integration choice

Chosen: official Google Places API (New). Place-ID lookup uses Place Details; supported Maps business-name URLs use Text Search with three candidates requiring confirmation. Allowlisted short-link redirects can be resolved without reading review HTML. Unresolvable URLs need a Place ID or manual identity confirmation. Lookup details are transient; administrators supply their own saved business labels/address.

Places is a documented route for public listing review display. It has a five-review selection limit and no review-history pagination. Google review storage is not enabled; cached pages contain a placeholder and dynamic responses use `no-store`. [API reference](https://developers.google.com/maps/documentation/places/web-service/reference/rest/v1/places), [display policy](https://developers.google.com/maps/documentation/places/web-service/policies), [service terms](https://cloud.google.com/maps-platform/terms/maps-service-terms).

Business Profile remains owner-only. Featurable and Trustmary were researched; their reviewed public materials did not establish an accessible, licensed non-owned permanent feed for this plugin. Neither was represented as an integrated or verified provider. [Complete provider decision and extension contract](https://github.com/webifya/google-reviews-for-wordpress/blob/v1.3.0-rc.1/docs/PHASE4-PROVIDERS.md).

## 4. Actual retrieval evidence

**No successful genuine Google retrieval is claimed.** WordPress HTTP fixtures exercise the exact fixed API endpoints, field masks, private headers, parsing, identity validation, review attribution, rendering, authentication failures and quota errors. Browser tests run actual WordPress REST requests and published shortcodes, with synthetic upstream responses. Those tests validate plugin behavior, not real provider eligibility or access to Menjar Financial.

A saved URL/key alone never grants Connected. Review-bearing successful retrieval is required. Empty or failed responses do not validate a collection. Images and examples are marked synthetic; no fake verification badges are added.

## 5. Non-owned businesses

Places supports the implemented non-owned listing workflow under the applicable agreement, with billing and a restricted API key. That live path still requires real validation. Business Profile needs management authorization. Full-history daily access for a non-owned business was not established.

## 6. Available review bounds

| Provider/method | Bound and status |
| --- | --- |
| Places API (New) | At most five selected reviews per business. Up to five locations per live widget request. No full-history/new-review guarantee. |
| Business Profile | Up to 50 per page with pagination for authorized managed businesses; owner dashboard only. |
| Featurable, researched | Free plan advertises 15/widget; documented endpoint batches up to 50; paid marketing claims higher/unlimited access. Not integrated, no verified non-owned permanent grant. |
| Permanent-license extension | Provider-declared bound and pagination; no real account verified. The 100-review test capability is synthetic, not an actual provider promise. |
| Retained imports/testimonials | Existing records remain usable with their original provenance; they do not demonstrate Google retrieval. |

## 7. Daily synchronization

Fixture-verified: default 24-hour schedule, manual Sync Now, actual due-job cron execution, stable-ID deduplication, changed record updates, statistics, next run, bounded paging, locking and error/retry handling. The hourly scheduler processes bounded jobs; cPanel may use a five-minute real cron for dependable triggers.

Only providers with a documented permanent storage/public display grant enter the new daily connection workflow. Newly retrieved licensed rows are stored separately from retained legacy rows, and their license reference is preserved. A retained testimonial cannot validate or populate a connected collection. **Places never schedules daily review prefetch or maintains a stored review archive.**

## 8. Shortcodes and migration

```text
[google_reviews_widget id="123"]
[google_reviews_widget id="123" limit="20"]
```

Widgets have independent locations, filters, limits, ordering, appearance and autoplay. Places still supplies only five per business. New widgets default to connected sources. Public empty behavior is hide or a configured message, including failed live requests. Legacy map/grid/combined shortcodes remain compatible, with no new map generators in the UI.

The real WordPress ZIP upload/replace flow preserved the complete before/after snapshot of all five plugin tables, settings and original shortcode pages. Repeated migration was idempotent. Rollback guidance is documented; new Places functionality needs this version, so restore the backup when reverting it.

## 9. Carousel

Reference composition retained: gray section, centered subtitle and bold heading, rounded light cards, overlapping circular avatars, gold stars, relative dates, circular arrows, responsive three/two/one cards, five-second default autoplay, infinite loop, swipe and read-more/read-less. Colors, typography, spacing, radius, transitions and ordering remain configurable.

Google attribution, available author/avatar/profile/source links, third-party credits and visit month/year stay visible. Original text is preserved and escaped for display. Google-selected data stays separate from legacy content. Subset selection, order and rating filtering are disclosed. Mandatory attribution is not suppressed by design controls.

## 10. Analytics

Verified actual visitor visibility, estimated session uniques, independent widgets, next/previous and read-more interactions, original review links, daily/monthly reporting and widget/location dimensions. Existing data survives upgrade. Consent, DNT/GPC and retention behavior remain enforced. Admin editor previews and logged-in administrator live frontend visits do not become visitor traffic; the dynamic rendering context preserves that exclusion.

## 11. Security and compatibility

Encrypted private credentials, no key in URLs/frontend/state/export, strict administrator permissions/nonce handling, fixed Google HTTPS endpoints, disabled redirects, unsafe-URL rejection, bounded responses, escaped text/attributes, typed configuration and budget limits. Signed public widget requests cannot select arbitrary locations or provider URLs. Site request-attempt budget defaults to 100/day; Google project quotas remain necessary. Key changes require page-cache purging. Review content is not placed in WordPress transients or persistent Google review rows.

No external application server is required. WordPress/PHP handle frontend rendering and provider calls. Provider images contact their supplied hosts. Analytics avoids storing IP addresses. Existing indexes/cache bounds and 10/100/1,000/10,000-record benchmarks are retained.

## 12. Executed tests

- Local WordPress: 101 integration assertions, 43 retained Phase 3 assertions, 100 Phase 4 provider/cron/security fixture assertions.
- Chromium, Firefox and WebKit: 92 Phase 4 workflow checks per engine plus 39 existing carousel checks per engine; 393 browser assertions in total, with no JavaScript errors.
- GitHub compatibility matrix: PHP 8.1–8.4 × WordPress 6.4.7/latest × MySQL 8/MariaDB 10.11, 16 combinations. Each runs all 244 backend assertions, installation from the packaged ZIP, previous-candidate replacement and exact preservation/idempotence checks. Combined backend assertions: 3,904.
- Local native WordPress upload: fresh activation from the final ZIP, followed by actual upload/replace upgrade from v1.2.0-rc.1. All five plugin tables, settings and shortcode pages preserved.
- Real published WordPress page: multiple live-placeholder shortcodes, late-loaded styles, mobile cards, attribution, read-more/navigation, consented events, administrator exclusion, signed scope, failed-request hide/message fallback, and retained map compatibility.
- Synthetic stored-dataset benchmark: 10/100/1,000/10,000 rows, asserting that the renderer actually displays cards. At 10,000 rows on the GitHub PHP 8.4/MySQL runner, 100-card rendering took 6.77 ms, a 50-row admin query 10.21 ms and peak memory 40 MiB. This is one illustrative run, not a provider-speed or hosting guarantee.
- Genuine provider requests: **zero**. No approved live access or real key was supplied.

Final GitHub run [37857292382](https://github.com/webifya/google-reviews-for-wordpress/actions/runs/37857292382) completed successfully: **all 17 jobs passed**, including all three browser engines. The latest WordPress matrix runtime was 7.1.3. The local final Phase 4 browser runs also each passed 92 assertions with zero JavaScript errors.

Install/upgrade evidence and screenshots are supplied alongside the ZIP. Earlier browser timing/transport issues were corrected rather than ignored. Mocked providers and successful live Google access are explicitly distinguished.

## 13. Limitations and external costs

Live Places requires API enablement, billing, a restricted server key and applicable agreement/public terms/privacy. JavaScript is required for live cards. A normal page cache may store placeholders but must not cache the live POST response. Business images/photos are not fetched. Name/address lookup candidates require explicit confirmation; arbitrary short URLs do not always resolve to a usable ID.

Current global list: review requests use Place Details Enterprise + Atmosphere, 1,000 free monthly events then $25/1,000 in the first paid tier. Identity rating/count lookup uses Enterprise, 1,000 free then $20/1,000. Maps-name search uses Text Search Enterprise, 1,000 free then $35/1,000. Rates depend on region/agreement/volume. Previews and multiple locations consume calls. This is pay-as-you-go rather than a required plugin subscription. [Google pricing](https://developers.google.com/maps/billing-and-pricing/pricing).

Featurable lists annual-billing equivalents of $29/month Pro ($348/year), $79/month Enterprise ($948/year), plus its 15-review free plan. These are researched alternatives, not proof of licensed access for this plugin. [Featurable pricing](https://featurable.com/pricing), [terms](https://featurable.com/terms).

No compliant, accessible provider was verified for non-owned 100+ reviews with a daily stored archive. Time-limited feeds need provider-specific expiry/purge support and must not be declared permanent-license adapters.

## 14. GitHub commit and push

Tested source: `08da622bb2e07b58f2f93626fffc5dbed83b75d9`. Runtime includes the administrator tracking fix from `f921f48abfc95167afdff7902b9757c3da9b3e2f`; subsequent test-only changes isolate fixture image transport, wait for plugin API completion, and verify the live iframe preview.

All implementation and test changes were pushed to `main`; the remote branch was verified against the tested commit. This report is a documentation-only addition after the successful run. The release build and public download verification are recorded in the delivered report and release assets.

## 15. Installation ZIP

[Download the WordPress ZIP](https://github.com/webifya/google-reviews-for-wordpress/releases/download/v1.3.0-rc.1/google-reviews-for-wordpress-v1.3.0.zip).

Package: 76,987 bytes, 25 production files. SHA-256: `e22614b5d918fc583fdc4799d2ad95c3bf78c17e5c88ff92cebd9132f8e2abd3`. The locally installed final ZIP matches the successful GitHub compatibility-build artifact byte for byte. Development files, tests, fixtures, credentials and screenshots are excluded. The release is `v1.3.0-rc.1`, a prerelease candidate; older tags are unchanged.

## 16. Necessary next steps

Privately configure a real Google Places project/key in WordPress, select/confirm a real business, retrieve its actual reviews and verify fields/attribution/costs. That verifies the five-review live option. For the requested full-history daily workflow, obtain a provider contract explicitly permitting non-owned access, storage, refresh and custom display; integrate and live-test that provider. Keep this release a candidate until those acceptance blockers are resolved.
