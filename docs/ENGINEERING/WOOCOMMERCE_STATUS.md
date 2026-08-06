# WooCommerce Status

**Live, Stripe live mode.** Full WooCommerce Blocks (React/Store-API driven cart and checkout) — `curl` cannot verify cart/checkout contents, real browser required.

## Products
6 published products (3 books × 2 formats), all confirmed `instock`. **Corrected 2026-07-16 (Sprint A, Phase 12) — this section was stale.** The print-on-demand stock policy established 2026-07-13 (see `DECISIONS.md`) restored all 3 hardcover products to `instock`; "out of stock" is not an inventory-control mechanism for these 6 products and is not used absent a verified fulfillment failure or an explicit sales-suspension decision. Live-reverified via WP-CLI on staging 2026-07-16 (`_stock_status = instock` on all 3 hardcover product IDs); production was confirmed in-stock at the time of the 2026-07-13 decision.

## Shipping
Contiguous US zone, single flat-rate method at $3.99, customer-facing, regardless of actual carrier cost (subsidized — see `DECISIONS.md`). Bookvault's own shipping method must **never** be zoned — it doesn't declare WooCommerce zone support and will inject live carrier rates the instant it's zoned (theme-level filter strips its rates as a safety net regardless).

## Fulfillment
Bookvault. Paperback and hardcover both confirmed enrolled (`bvlt_liked`/`bvlt_locations` metadata identical across all 6 products, per `DECISIONS.md`'s hardcover fulfillment verification entry) — no SKU has ever had a verified fulfillment failure.

## Coupons
[PARENT_COUPON_CODE_SUPERSEDED] — Collection-only, additional 10% off, live since 2026-07-11. See `docs/RELEASES/COLLECTION_COUPON_PRODUCTION.md`.

## Bundle pricing
`brave-hearts-bundle-pricing` plugin — Complete Collection smart-cart logic, discount protection, side-cart drawer, cross-sell messaging. **v1.8.2 as of 2026-07-13** (isolated analytics-parity patch — pricing/discount/shipping/coupon logic unchanged since the [PARENT_COUPON_CODE_SUPERSEDED] release; only ecommerce-analytics event code was updated). See `RELEASES/BUNDLE_PRICING_ANALYTICS_PARITY_PRODUCTION.md`. Note: production's `includes/dashboard/` (KPI/economics module) was deliberately left behind staging's — a separate, unapproved feature, not part of this update.

## Structured data
GTIN (WooCommerce native Global Unique ID field), brand taxonomy (`product_brand` + Rank Math setting), `shippingDetails` (custom `rank_math/json_ld` filter, priority 999). No `aggregateRating`/`review` schema anywhere — never fabricated.
