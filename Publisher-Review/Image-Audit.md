# Image Audit

Review date: 2026-06-30

## Inventory

- 101 media-library objects were readable.
- 112 unique images were observed across the 88 rendered URLs.
- No rendered image omitted the alt attribute, but 82 unique rendered images use empty alt text.
- 90 media-library image records have blank alternative text.

## High-priority findings

- Contact loads three large images directly from `images.squarespace-cdn.com`; two are 2500 × 3912 and one is 2500 × 1064, with no responsive `srcset` detected.
- Teachers loads multiple original Squarespace CDN assets at 2500 pixels wide, including teaching-guide screenshots displayed around 716 pixels wide.
- Product covers are served at full dimensions on some product pages without responsive candidates. The Everest hardcover cover has empty alt text.
- Blog and category thumbnails often use empty alt text. Decorative treatment may be appropriate for duplicates, but meaningful editorial thumbnails should identify the article topic.

## Large media-library assets

- `IMG_7341.mov`: 50.4 MB.
- `v12044gd0000d7n8697og65l2ctkq0v0.mp4`: 37.2 MB.
- `IMG_1358.mov`: 34.8 MB.
- Two 2500 × 1750 WebP demo images: 5.9 MB and 5.3 MB.
- `IMG_2796-scaled.png` and `IMG_2797-scaled.png`: about 4.3 MB each at 1177 × 2560.
- Multiple HEIC originals range from roughly 2.2 MB to 4.8 MB and are not broadly web-compatible.

## Recommendations

- Replace live Squarespace original-image dependencies with WordPress-managed responsive images.
- Generate appropriately sized WebP/AVIF derivatives for large PNG/JPEG sources while preserving originals for print.
- Add meaningful alt text to product covers and informative editorial images; retain empty alt only for genuinely decorative duplicates.
- Keep videos out of initial page load, provide posters, and compress or stream large source files.
- Verify mobile focal points for hero and cover imagery after asset replacement.
