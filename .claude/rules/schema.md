# Structured Data (Schema) Rules

- Rank Math builds its Product JSON-LD `@graph` progressively across many
  calls to the *same* `rank_math/json_ld` filter at different priorities.
  A callback registered at the default priority (10) will see an EMPTY
  `$data` array — Rank Math's own Product entity isn't built yet at that
  point. Register at a very late priority (999 is confirmed to work) if
  you need to read/modify the already-built Product entity.
- GTIN: read from WooCommerce's native Global Unique ID field
  (`get_global_unique_id()`), which Rank Math itself does NOT include for
  variable products' single-variation offers (a real gap in Rank Math) —
  the theme patches this in `functions.php` by looking up the one child
  variation directly via `get_children()` when `count($children) === 1`.
- Brand: requires BOTH the `product_brand` taxonomy term assigned to the
  product AND Rank Math's own setting
  `rank-math-options-general['product_brand']` set to the taxonomy slug —
  assigning the term alone is not sufficient.
- `shippingDetails`: uses `OfferShippingDetails` / `DefinedRegion` /
  `addressRegion` (array of contiguous-US state codes) to honestly
  represent "contiguous US only" — there's no dedicated schema.org concept
  for this, so don't overclaim AK/HI/territories/international coverage.
- Never fabricate `aggregateRating` or `review` schema. If no real reviews
  exist, that section of the schema should be absent, not synthesized.
- Any structured-data change must be verified by inspecting the rendered
  `<script class="rank-math-schema">` block on a real page load, not
  assumed from the filter code alone.
