# Staging Indexation Audit

Generated 2026-07-01. No staging settings were changed.

## Finding: FAIL — launch-critical

- Homepage robots meta: `follow, index, max-snippet:-1, max-video-preview:-1, max-image-preview:large`
- Homepage canonical: `https://staging2.braveheartspublishing.com/`
- Homepage Open Graph URL: `https://staging2.braveheartspublishing.com/`
- Public REST API: accessible; 35 posts, 18 pages, 6 products, and public taxonomies were enumerable.
- Active theme asset path: `brave-hearts-theme-deploy-explorer-expedition-guides`, version `1.16.0`.
- Public asset versions indicate WordPress `7.0` and WooCommerce `10.9.1`.
- Rank Math namespaces confirm the plugin is active; its exact version and Free/Pro edition are not exposed publicly.
- HTTP authentication: not present on the tested public homepage.
- `robots.txt`: was publicly reachable during the initial probe, but later audit requests were blocked by SiteGround HTTP 403/WAF behavior.
- Rank Math sitemap index: HTTP 403 to the audit client.
- Search-engine cache presence: not assessed; no Search Console/search-engine account access was available.
- WordPress “Discourage search engines” setting: cannot be confirmed without authenticated WordPress/WP-CLI access; rendered output demonstrates it is not producing `noindex` on the homepage.

Staging can currently compete with production. Protect it promptly with HTTP authentication and/or sitewide `noindex`, while preserving access for launch QA.

## Before launch

- [ ] Add HTTP authentication or equivalent staging access control
- [ ] Emit sitewide `noindex, nofollow` on staging
- [ ] Confirm staging canonicals do not create duplicate production signals
- [ ] Verify RSS, REST, media, Open Graph, schema, and sitemaps do not expose indexable staging URLs
- [ ] Keep staging blocked after production launch

## At production launch

- [ ] Confirm production has no `noindex`
- [ ] Confirm every indexable production page has a production-domain self-canonical
- [ ] Confirm Open Graph/schema/media URLs use production HTTPS URLs
- [ ] Confirm production robots.txt and sitemap index return HTTP 200
- [ ] Confirm staging remains inaccessible or noindexed
