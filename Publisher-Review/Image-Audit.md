# Image Audit

Prepared: June 30, 2026  
Evidence level: Theme-source review only

## Repository finding

The theme repository contains no raster or vector image assets. Public imagery is expected to come from the WordPress media library and WooCommerce products, neither of which is available here.

## Large-image risks

- The shared hero component requests the WordPress `full` attachment size. Large originals may increase Largest Contentful Paint, memory use, and mobile transfer size.
- Founder and Teacher CTA images request `large`.
- Book covers use the custom `bhp-book-card` size.
- Hub images use the cropped `bhp-card-landscape` size.
- Blog cards use `medium_large`; single-post featured images use `large`.

## Missing-alt risks

- Single-post featured images rely entirely on media-library alt text.
- Teacher CTA images may intentionally be decorative, but an empty configured alt must be reviewed against the actual image purpose.
- Book covers and Passport images have title fallbacks.
- Decorative hero, icon, and Hub media correctly use empty alt text in source.

## Responsive image handling

- WordPress attachment APIs are used, so `srcset` and `sizes` can be generated for registered image sizes.
- CSS constrains dimensions and generally uses `object-fit` appropriately.
- Responsive output cannot be confirmed without rendered media attachments and browser inspection.

## WebP/AVIF opportunities

- Confirm WordPress generates and serves modern formats through core, the hosting image pipeline, or one approved optimization layer.
- Do not keep duplicate optimization plugins or CDNs that rewrite the same asset.
- Preserve high-quality originals outside the public delivery path.
- Verify transparency, color, book-cover text legibility, and illustration quality after conversion.

## Required media export

For every public attachment, collect:

- Attachment ID and canonical URL
- Filename and MIME type
- Pixel dimensions and file size
- Alt text, caption, and credit
- Pages/products where used
- Rendered dimensions at 1440px and mobile widths
- `src`, `srcset`, `sizes`, lazy-loading, and fetch-priority output
- Duplicate or orphan status

## Review thresholds

- Flag hero/LCP images materially larger than their rendered requirement.
- Flag non-hero images that load eagerly above the fold without purpose.
- Flag images missing meaningful alt when they convey information.
- Flag decorative images with noisy or redundant alt.
- Flag blurry upscaling, distorted aspect ratios, unreadable book-cover text, and art-direction failures.

## Result

Theme image API usage: GENERALLY SOUND.  
Media-library audit: NOT POSSIBLE.  
Launch gate: BLOCKED pending rendered image inventory and performance review.

