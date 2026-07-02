# Sitemap 403 Focused Investigation

## Result

Staging `/sitemap_index.xml` remains HTTP 403. This is acceptable while staging is intentionally noindex, provided production sitemap behavior is validated before DNS launch.

## Evidence

- Ordinary staging HTML pages return HTTP 200.
- Public WordPress REST endpoints return HTTP 200.
- Repeated automated requests to `robots.txt` and the sitemap have produced SiteGround-branded 403 responses.
- Rank Math namespaces are active.
- All representative public pages now emit `noindex`.

The evidence is most consistent with SiteGround/WAF/Security Optimizer request filtering or an intentional staging restriction. Server logs and authenticated Rank Math module inspection are still required to distinguish that from an XML rewrite/cache issue.

Do not weaken staging protection merely to expose the sitemap. Before launch, verify the production sitemap index and every child sitemap return HTTP 200 to ordinary search-engine requests.

