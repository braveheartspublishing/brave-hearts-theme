# Permalink Configuration Before Change

Recorded 2026-07-01 before any mutation.

- Observed post path structure: `/%postname%/`
- Required post path structure: `/blog/%postname%/`
- Front page: WordPress page ID 24
- Posts page: WordPress page ID 118
- Current WordPress home URL setting reports the temporary staging host with HTTP; public requests normalize to HTTPS.
- Category paths: `/category/{slug}/`
- Tag paths: `/tag/{slug}/`
- Product paths: `/product/{slug}/`
- Product category paths: `/product-category/{slug}/`
- Pages, products, hubs, and media were inventoried in `new-staging-url-inventory.csv`.

The authenticated core REST settings endpoint does not expose `permalink_structure`, `category_base`, or `tag_base`. The current post structure is established by all 35 published post URLs and is therefore observationally confirmed, but no supported REST write operation is available for changing it.

No WordPress setting was changed.

