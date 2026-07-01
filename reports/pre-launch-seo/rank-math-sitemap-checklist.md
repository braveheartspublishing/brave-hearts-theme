# Rank Math Sitemap Checklist

Generated 2026-07-01. Read-only audit; no settings changed.

## Current observations

- Production is a Squarespace site. `/sitemap_index.xml` is unavailable; `https://www.braveheartspublishing.com/sitemap.xml` is the advertised sitemap and yielded 243 URLs.
- Staging exposes Rank Math REST namespaces, confirming Rank Math is active, but `/sitemap_index.xml` returned HTTP 403 to the audit client.
- Staging HTML currently emits `index, follow` and staging-domain canonicals. This is a launch blocker while staging remains publicly reachable.
- Exact Rank Math version, Free/Pro status, sitemap module configuration, and existing redirect schema require authenticated WordPress/WP-CLI access.

## Include after launch

- [ ] Homepage
- [ ] Published posts
- [ ] Approved published pages
- [ ] Published WooCommerce products
- [ ] Shop and intentional product-category archives
- [ ] Explorer Expedition Guides index (`/teachers/`)
- [ ] Any future standalone topic/destination hubs with substantial unique content
- [ ] Educator/family pages only where they are standalone, useful, canonical pages

## Exclude after launch

- [ ] Search results
- [ ] Cart, checkout, My Account, order confirmation
- [ ] Internal utility/lead-processing/thank-you pages where appropriate
- [ ] Draft, private, staging, and duplicate pages
- [ ] Thin tag archives (137 tags currently exposed through REST)
- [ ] Duplicate or thin category/product-tag archives
- [ ] Author/date archives unless intentionally maintained
- [ ] Attachment pages unless intentionally maintained

## Required authenticated review

- [ ] Record Rank Math version and edition
- [ ] Export Rank Math sitemap settings
- [ ] Confirm post/page/product sitemap inclusion
- [ ] Review all 28 categories, 137 tags, 6 product categories, and 12 product tags for search intent and content depth
- [ ] Confirm self-referencing production canonicals
- [ ] Resolve staging sitemap HTTP 403 without making staging indexable
- [ ] Confirm production sitemap returns HTTP 200 after migration

