# Focused SEO Remediation Readiness

Generated 2026-07-01.

## Staging

- Noindex verified: **PASS, 13/13 representative URLs**
- Cache result: public responses now reflect the owner-enabled setting; no stale `index, follow` response was observed
- Cache purge performed by Codex: no
- Password protection: not detected; recommended but not a blocker under the owner’s decision
- Sitemap: HTTP 403 on staging
- Canonical behavior: canonical output suppressed on tested noindex responses

## Real content

- Real old blog posts identified: 35
- Matched to published WordPress posts: 35
- Changed URLs: 35 (`/blog/{slug}` to `/{slug}`)
- Direct post-to-post redirects required: 35
- Unmatched real articles: 0

## Meaningful old pages

- Meaningful pages identified: 11
- Unchanged/direct equivalents: 9
- Page redirects approved: 2 (`/home/` to `/`; `/store/` to `/shop/`)
- Focused redirect map total: 37

## Legacy noise

- Ignored Squarespace-generated or irrelevant URLs: 197
- Import planned: no
- Redirect planned: no
- Launch blocker: no

## Link health

- Link occurrences checked: 452
- Clean direct: 206
- Runtime-generated staging references expected to change with site URL: 53
- Stored/rendered staging references requiring authenticated launch review: 145
- Internal HTTP occurrences: 26 (two unique URL variants)
- Redirecting link occurrences: 14
- Broken download occurrences: 8 across 3 unique files
- All three original approved PDFs were verified on production with HTTP 200 and `application/pdf`
- Other broken internal links: 0
- Redirect chains/loops in the focused map: 0

## Rank Math

- Exact version/edition: unavailable without authenticated access
- Import method: not verified against installed version
- Sitemap readiness: staging intentionally blocked/noindex; production HTTP 200 verification required before DNS
- Redirect readiness: focused map prepared; bulk import not performed
- Test redirect: not created because authenticated access/backups were not verified

## Design protection

- Runtime theme files changed: none
- Homepage visual changes: none
- Theme version: 1.16.0
- Deployment ZIP required: no

## Decision

**NOT READY — SPECIFIC BLOCKERS REMAIN**

The 197 ignored legacy URLs are not blockers. Remaining blockers are:

1. Verify staging database/files backups and obtain explicitly authorized authenticated WordPress access.
2. Correct or approve the 145 stored/rendered staging references that may survive cutover.
3. Replace 26 internal HTTP occurrences with direct HTTPS URLs.
4. Update 14 stored internal links to their direct final targets.
5. Replace the eight visible broken-download occurrences using the three verified original PDFs.
6. Verify Rank Math’s installed version, existing redirects, native import method, and one temporary redirect.
7. Confirm production sitemap HTTP 200 behavior before DNS launch.

No DNS or production Squarespace change was made.
