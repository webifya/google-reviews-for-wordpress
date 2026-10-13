# Menjar Financial Reviews — connection and style setup

This plugin is dedicated to Menjar Financial, Place ID `ChIJy3bcVRzl9IgR5eOJcFC6ZCQ`. It has only Connection, Reviews and Style Setup screens. Other business identities are rejected on the server.

## Upgrade

Upload `menjar-financial-reviews-v1.7.1.zip` and replace the existing Google Reviews for WordPress plugin. Keep the existing installation; do not uninstall. The plugin keeps the same folder, OAuth callback, encrypted credential option, locations and saved style IDs. Historical review and analytics tables are retained but are no longer displayed or collected. Previous scraper/sync jobs are disabled.

## Connection

If Google is already connected, your saved authorization is kept. Click **Connect Menjar Financial**. The plugin follows your managed-account and business pages and finds only the matching Place ID, then verifies review access before saving. You do not need to select unrelated businesses.

If no matching profile is found, confirm that the signed-in Google account manages the verified Menjar Financial profile. For Google access errors, check the approved project's Account Management, Business Information and Google My Business review API enablement and quota.

Only if setup needs changing, expand **Private Google setup**. Keep the exact displayed redirect URI in your approved OAuth web client. Enter the client ID and secret here, never in a chat. The client secret is masked and saved encrypted; blank fields preserve existing values. HTTPS is required for sign-in. External OAuth apps in Testing mode may require test-user enrollment and have seven-day refresh-token expiry; production consent configuration is needed for sustained access.

The existing callback remains `wp-admin/admin-post.php?action=grw_google_callback`. Updating this plugin does not require a new redirect URI or deleting the connected account.

## Reviews and website display

Reviews come from the official Business Profile API and are paginated, up to 50 per API page. Previous/Next pages show all available API reviews, including rating-only reviews and business responses. Refresh requests an updated page. Content is encrypted in a temporary 15-minute performance cache and never copied into the permanent review table.

Enable **Show Menjar Financial reviews on the website** if your applicable Google agreement and authorization permit publication. This choice is not an additional storage or display license. Website requests load paginated reviews up to the configured Review Limit through no-store responses.

## Style Setup

Set colors, fonts, headings, spacing, responsive columns, carousel movement and Read More behavior. The preview uses actual reviews from the first API page; it never inserts example testimonials. The website follows additional pages up to the Review Limit. Google author, date and source attribution are retained.

Publish `[menjar_reviews]`. Existing `[google_reviews_widget id="123"]` shortcodes and the existing block continue using their saved designs with Menjar Financial reviews. Select an existing shortcode/style if more than one saved design is present.

No Node.js, browser worker, Places key, analytics, maps, import tools or unrelated review sources are part of this package. Historical source/tests in Git are not shipped.

References: [Google API prerequisites](https://developers.google.com/my-business/content/prereqs), [review pagination](https://developers.google.com/my-business/reference/rest/v4/accounts.locations.reviews/list), [content policies](https://developers.google.com/my-business/content/policies), [OAuth token expiry](https://developers.google.com/identity/protocols/oauth2#expiration).

## Diagnosing Google access failures

A successful Google sign-in and the “You manage this Business Profile” panel confirm account access, but do not prove Cloud project API approval. The plugin now shows the failed API, HTTP status and recognized Google reason without exposing raw responses or credentials.

In the Cloud project that owns the saved OAuth client, check Account Management API, Business Information API and Google My Business API enablement. Google documents 0 requests/minute as unapproved and 300 as the usual approved quota. SERVICE_DISABLED means enable the named API; scope errors mean reconnect and grant business.manage; quota errors require checking approval and usage. An unspecified 403 cannot uniquely identify the cause: check enablement, quota, the connected Google account, profile verification and Workspace Business Profile access.

Do not create another OAuth client or delete the existing connection just to enable an API. After fixing project configuration, click Check Menjar Financial connection again. Share only the diagnostic text if it still fails, never OAuth credentials or tokens.
