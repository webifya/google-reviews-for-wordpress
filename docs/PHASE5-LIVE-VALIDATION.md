# Phase 5 live validation — blocked

Research date: 2026-10-09. Target supplied by owner: **Menjar Financial, 105 Banks Station #1068, Fayetteville, GA 30214**. This address is a test target, not an independently verified current Maps identity. No genuine provider credential, contracted non-owned feed or approved billable budget was available. **Zero genuine provider retrieval requests were made.** Research-page browsing is not API validation. The earlier instruction to use fixtures remains applicable.

| Acceptance item | Actual result |
| --- | --- |
| Provider authentication / live connection | Blocked: no genuine key/account/license |
| Correct current business Place ID | Unverified; requires official lookup/confirmation |
| Actual review count accessible | Unknown; not zero and not 100+ |
| Real names, ratings, timestamps, text | Not retrieved |
| Available photos, links, replies | Not retrieved; optional missing fields must not be invented |
| Display rights / attribution | Places documented policy retained; real account/region eligibility unverified; vendor grant absent |
| Genuine admin collection / shortcode carousel | Not verified; synthetic transport tests only |
| Genuine manual refresh and deduplication | Not verified; fixture path passed |
| Two distinct timed provider fetches | Not performed |
| Full 24-hour production sync | Not performed; blocked by licensed source/access |

## Secure continuation for Places' limited five-review option

1. In the owner's Google Cloud project enable Places API (New), billing and appropriate project quotas. Review the applicable Google agreement, region and public site terms/privacy. Agree an explicit maximum test spend before requests; no default approval is inferred from a free allowance.
2. Restrict a server API key to Places API (New) and the WordPress server's outbound IP. Enter it privately in **Google Reviews → Settings → Review connections**. Do not send it through chat, commit it, include it in fixtures or diagnostic exports.
3. Add the supplied Menjar Maps URL or use the current officially returned Place ID. Run Find Business after budget approval, compare the returned name/address with the target and explicitly confirm the right candidate. Store an administrator-supplied label/address; do not save transient Google lookup content.
4. Open Connect Reviews, choose Places, test actual retrieval. Record request time, confirmed Place ID and the actual returned count (0–5), not the listing's total count. Empty content does not validate the connection. Respect project/site budgets; raw responses and secrets must stay private.
5. Verify original reviewer/name/rating/date/text and optional author photo/profile, review link, credits and visit date. Preview and publish `[google_reviews_widget id="123"]`. Check Google Maps/author attribution and response no-store behavior; do not archive changing selections.
6. Run a second approved retrieval later. Record whether selection changed, without claiming newly posted reviews merely because selections differ. Places still has no full-history cursor or daily stored archive.

## Secure continuation for the full daily archive

Obtain explicit non-owned acquisition/display/storage/commercial rights plus a supported account/endpoint, 100+ entitlement where available, daily refresh, stable IDs, deletion/expiry policy, attribution and rate limits. Resolve any contractor restriction. Approve costs. Implement and test the real adapter; it is absent from this candidate. Connect the confirmed Menjar listing, compare returned individual metadata to the vendor's supported source and verify a successful manual refresh without duplicates. Observe two time-separated fetches and one actual 24-hour scheduled run on the production cron path. Record new reviews only if new records actually exist; otherwise record an unchanged collection. Apply required deletion/retention before public use. Publish live acceptance evidence without secrets.
