# Staging Sitemap 403 Investigation

Status: public investigation complete; server-log/authenticated attribution remains blocked.

## Observed behavior

- `GET https://staging2.braveheartspublishing.com/sitemap_index.xml` returned HTTP 403.
- Ordinary HTML pages returned HTTP 200.
- Public WordPress REST collections returned HTTP 200.
- Some repeated requests to `robots.txt` also began returning a SiteGround-branded 403 response body.
- The response behavior is therefore consistent with hosting/security/WAF request filtering rather than proof of a Rank Math generation failure.
- Rank Math namespaces are active, but exact module state is unavailable publicly.

## Likely cause hierarchy

1. SiteGround/Security Optimizer/WAF rate or bot filtering
2. Server/cache/rewrite rule affecting XML requests
3. Rank Math sitemap module disabled or inaccessible
4. Plugin conflict

## Required authenticated/server checks

- Compare logged-in and logged-out requests.
- Inspect SiteGround Security Optimizer and WAF logs.
- Inspect web-server access/error logs and response headers.
- Confirm Rank Math sitemap module state.
- Flush permalink and sitemap caches only after backup and approval.
- Verify production `/sitemap_index.xml` returns HTTP 200 before DNS launch.

Staging sitemap blocking may remain intentional, but the production behavior must be tested independently.

