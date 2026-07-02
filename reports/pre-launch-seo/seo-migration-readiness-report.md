# SEO Migration Readiness Report

Generated 2026-07-01.

## Theme baseline

- Source ZIP: `brave-hearts-theme-deploy-explorer-expedition-guides.zip`
- Version: 1.16.0
- Runtime files: 61
- Baseline hashes: recorded in `deployed-theme-baseline.txt`
- Runtime files changed: none
- Homepage regression risk from this phase: none; no runtime file was edited

## Staging protection

- Noindex: **FAIL** — most tested public pages emit `index, follow`
- Password protection: **not detected**
- Sitemap: **HTTP 403**
- Canonicals/Open Graph: staging-domain URLs
- Protection changes: not applied because database/files backups could not be verified and authenticated execution was not accepted by the credential safety gate

## Content discrepancy

- Original Squarespace sitemap URLs classified as posts: 205
- Corrected unique legacy blog posts: 205
- Non-post sitemap URLs: 38
- Duplicate sitemap URLs: 0
- Matched WordPress posts: 35
- Missing/restoration candidates: 170
- Confirmed intentionally excluded: 0
- Confirmed merged: 0
- Blog redirect candidates: 35
- Page redirect candidates: 1 (`/home` to `/`)
- Retirement candidates approved: 0
- Remaining content decisions: 198 (170 restore/import; 28 manual page review)

## URL classification

- Total old URLs: 243
- Unchanged: 9
- Approved redirect proposals: 36
- Approved 404: 0
- Approved 410: 0
- Restore/import: 170
- Manual review: 28

All old URLs now have an explicit classification; none was silently retired or redirected to a broad fallback.

## Internal links

- Links checked: 452
- Clean direct: 206
- Redirecting: 14
- Staging references: 198
- Insecure HTTP references: 26
- Broken download occurrences: 8 (3 unique missing URLs)
- Other broken internal links: 0

No stored content was changed because backup/authentication prerequisites were not met. Final audit counts therefore remain unchanged.

## Rank Math

- Version: unavailable without authenticated plugin access
- Edition: unavailable
- Public status: active namespaces detected
- Sitemap: HTTP 403; SiteGround/WAF filtering is likely but server-log confirmation is required
- Redirect method: not verified against the installed version
- Test redirect: not created; backup and authenticated prerequisites unmet
- Bulk import: not performed; importer apply mode remains locked

## Explorer Expedition Guides

- Main index: existing `/teachers/` page, one H1, reciprocal post links, navigation path present
- Topic/destination areas are section anchors rather than standalone indexable pages
- Final production canonicals and sitemap behavior require launch configuration
- No template redesign or runtime change occurred in this phase

## Decision

**NOT READY — BLOCKERS REMAIN**

1. Staging is publicly indexable and lacks verified access protection.
2. Required staging database/files/settings backups are unverified.
3. 170 legacy articles are absent from WordPress and require restore/import decisions.
4. 28 legacy pages require manual intent/traffic/backlink review.
5. Existing Rank Math redirects and installed import method are unverified.
6. Staging sitemap HTTP 403 root cause is not confirmed through logs/authenticated testing.
7. 198 staging-domain, 26 HTTP, 14 redirecting-link, and 8 broken-download occurrences remain in the audited output.
8. Search Console, Analytics, redirect-hit, and backlink data remain unavailable.

No DNS or production Squarespace change was made.
