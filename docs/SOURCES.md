# Source feasibility review — 9 October 2026

Primary sources reviewed:

- [Google Maps End User Additional Terms](https://www.google.com/help/terms_maps/) (modified 27 January 2026): copying is restricted unless permission or applicable law allows it; mass downloading/bulk feeds are prohibited.
- [Google Maps Platform Terms](https://cloud.google.com/maps-platform/terms): platform licensing restrictions include no scraping and limits on caching/exporting content. Integrations must evaluate their applicable terms, including regional terms.
- [Official share/embed help](https://support.google.com/maps/answer/144361?hl=en): official Maps UI provides share/embed functionality for supported map content.
- [Maps Embed API usage](https://developers.google.com/maps/documentation/embed/usage-and-billing): the developer Embed API requires an API key, despite no-charge usage for the service.
- [Maps Embed API documentation](https://developers.google.com/maps/documentation/embed/embedding-map): supported map modes render official content, not an export of individual reviews for custom cards.

Implementation decisions:

1. Accept only an existing official Google share iframe `src` at `https://www.google.com/maps/embed?pb=...`. Preserve the iframe's presentation and attribution; do not read or transform its internal content. The API `/maps/embed/v1/` path is not offered as key-free functionality.
2. Save public Maps links and Place IDs as manually confirmed location metadata. Do not fetch short-link redirects or infer review availability. There is no supported key-free method implemented to retrieve 100 individual Google reviews or automatically synchronize a public Maps URL.
3. Authorized imported content and manual testimonials remain separate from official embeds. Administrator rights confirmation does not itself prove Google's data license. Operators must have actual permission for the content and any source marks they use.
4. No Google logo/verification badge is added to imported/manual cards. Source labels and original links remain visible, regardless of widget visibility customization.
5. The included local JSON feed reads operator-controlled authorized data only. Extensions must establish their own licensing/attribution/cache obligations before enabling synchronization.

These are implementation constraints, not a legal opinion or an assertion that all third-party reviews can be republished. Public accessibility alone is insufficient authorization.
