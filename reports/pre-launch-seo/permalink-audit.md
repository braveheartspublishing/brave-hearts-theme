# Permalink and URL Normalization Audit

Generated 2026-07-01. No permalink settings were changed.

## Production

- Canonical host: `www.braveheartspublishing.com`
- Platform: Squarespace, inferred from page markup and robots.txt
- Sitemap: `/sitemap.xml`
- Blog format: `/blog/{post-slug}/`
- Sitemap inventory: 243 URLs (205 blog posts, 38 other pages by path classification)
- Search/account/config/API patterns are disallowed in production robots.txt.

## Staging / intended rebuild

- Host: `staging2.braveheartspublishing.com`
- Platform: WordPress with WooCommerce and Rank Math namespaces
- Post format: `/{post-slug}/` (not `/blog/{post-slug}/`)
- Pages: root-level slugs
- Products: `/product/{product-slug}/`
- Category base: `/category/`
- Tag base: `/tag/`
- Product category base: `/product-category/`
- Product tag base: `/product-tag/`
- Feed observed at `/feed/`; final feed/indexation handling requires launch validation.
- Trailing-slash behavior: WordPress objects are emitted with trailing slashes.
- Attachment, author, date archive, pagination, and exact permalink option values require authenticated WP-CLI confirmation.

## Migration risks

- Production currently canonicalizes to `www`; the brief names the bare domain. Select one final host and enforce it in one hop. Do not allow bare → www → HTTPS or the reverse to form chains.
- At least 36 legacy `/blog/{slug}/` URLs have same-slug staging articles at `/{slug}/`; all remain review-required until traffic/backlink review and final host choice.
- The remaining 198 production URLs lack an unambiguous replacement in the public staging inventory.
- Do not change permalink settings in this phase.

