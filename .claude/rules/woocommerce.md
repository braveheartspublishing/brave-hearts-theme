# WooCommerce Rules

- The store uses full WooCommerce Blocks (React/Store-API driven cart and
  checkout) — `curl` can verify server-rendered pages but NOT cart/checkout
  contents. Use a real browser for anything shipping/pricing/checkout
  related.
- **Shipping — corrected 2026-08-02 (owner-ruled, live-verified). Two
  different facts, and conflating them is the documented failure here.**

  **1. The zone configuration.** Exactly one zone, *Contiguous United
  States*; exactly one method, `flat_rate` instance 1, cost **`3.99`**.
  This is the **base** the plugin adjusts. Verified live on **production**
  2026-08-02 by direct `ugc_woocommerce_shipping_zones` /
  `ugc_woocommerce_shipping_zone_methods` query plus
  `woocommerce_flat_rate_1_settings`. Verify this way, never by assumption.

  **2. What the customer actually pays is TIERED per number of books.**
  Owner ruling, verbatim: *"Andrew Signore, 2026-08-02: 'Shipping is tiered
  per amount of books ordered.'"* `bhp_bundle_override_shipping_cost()`
  (priority 20 on `woocommerce_package_rates`) rewrites the `flat_rate`
  cost from the approved tier table — it adjusts the number, it never adds
  or removes a method:

  | Cart | Rendered shipping |
  |---|---|
  | 1 paperback | **$1.99** |
  | 2 distinct paperbacks | **$2.99** |
  | 3 distinct paperbacks | **$3.99** |
  | 1 hardcover | **$2.99** |
  | 2 distinct hardcovers | **$3.99** |
  | 3 distinct hardcovers | **$4.99** |
  | mixed formats, ≤2 items | **$3.99** |
  | mixed formats, ≥3 items | **$4.99** |

  Source of the numbers: `bhp_bundle_single_shipping()` and
  `bhp_bundle_rules()` in `plugins/brave-hearts-bundle-pricing/includes/
  bundle-data.php`; selection logic in `bhp_bundle_shipping_amount()` in
  `bundle-cart.php`. **A cart containing any product outside the six
  approved editions is left completely alone** (`has_unrelated`).

  **OBSERVED LIVE 2026-08-02**, real Blocks cart, both environments, single
  Mariana paperback: Store API and rendered DOM both report *Contiguous US
  Shipping **$1.99*** — subtotal $11.99, tax $0.72, total $14.70, exactly
  one shipping method, zero "BookVAULT" occurrences. This settles the
  $1.87/order finance sensitivity in favour of **$1.99**, not $3.99, for a
  single-book order.

  **SUPERSEDED statement, retained so it is not re-derived:** this file
  previously read *"Customer-facing shipping must always resolve to a single
  flat rate ($3.99, Contiguous US zone)."* It is true of the **zone**, false
  of the **rendered rate**, and reading it as the latter would make a
  correct $1.99 look like a regression. Tracked as `CYCLE140-DEV-2` — now
  closed by the owner ruling above.

  **Unchanged and still binding:** never add "BookVAULT Shipping" to any
  zone (see below), and no agent changes any shipping setting on any
  environment without Andrew's explicit, current-turn approval.
- **Never add "BookVAULT Shipping" to any WooCommerce shipping zone.** Its
  plugin class doesn't declare zone support and will inject live carrier
  rates the instant it's zoned — see `docs/PROJECT_STATE.md` and
  `docs/DECISIONS.md`.
- If a shipping rate discrepancy appears after a zone/settings change with
  no code change, suspect WooCommerce's session-level shipping-rate cache
  before suspecting a real regression — bump
  `WC_Cache_Helper::get_transient_version('shipping', true)` to rule it out
  (same mechanism WooCommerce itself uses when zone settings are saved).
- **Stock status — corrected 2026-08-01 (live-verified).** All six published
  products, including all three hardcovers (14 Mariana HC, 17 Everest HC,
  20 Amazon HC), are **`instock`**. Verified by direct `_stock_status` query on
  staging 2026-08-01, and confirmed by Andrew in the same turn. Hardcover is
  also the Complete Collection's default format
  (`bhp_bundle_default_format()`).

  **SUPERSEDED statement, retained so it is not re-derived:** this file
  previously read *"Hardcover editions are intentionally out-of-stock."* That
  was true of an earlier period and is **no longer current**. It was already
  superseded by the **print-on-demand stock policy** in `docs/DECISIONS.md`
  (2026-07-13, declared controlling) and corroborated by
  `docs/ENGINEERING/WOOCOMMERCE_STATUS.md` (2026-07-16); this rules file was
  simply never updated to match. Tracked as CYCLEX-CX-1 / Business OS C12 —
  now closed.

  **The safeguard is unchanged and still binding:** do not change the
  `_stock_status` of any of the six core products without a fresh, explicit,
  current-turn decision from Andrew. Out-of-stock is **not** an inventory-control
  mechanism for print-on-demand titles; setting one requires either a verified
  fulfillment failure or an explicit suspension from Andrew.
- Never fabricate or infer `aggregateRating`/`review` schema — only emit it
  from real WooCommerce reviews, and only if real reviews exist.
- GTIN comes from WooCommerce's native "Global Unique ID" field
  (`_global_unique_id` postmeta / `get_global_unique_id()`), not a custom
  field.
- `product_brand` taxonomy: use the exact term *name* string with WP-CLI
  (`wp post term set <id> product_brand "Brave Hearts Publishing"`), never
  a bare numeric ID — WP-CLI will silently create a spurious new term
  literally named that number if you pass one.

- **This store runs WooCommerce HPOS (High-Performance Order Storage).**
  Orders live in the `wc_orders` custom table, **not** in `wp_posts`. A
  `wp post list --post_type=shop_order` query returns **0** and is wrong — it
  has already produced false "no orders" claims in a session report. Read
  order counts via `wp wc shop_order list --user=1`, or
  `SELECT COUNT(*) FROM <prefix>wc_orders`. Confirm with
  `wp option get woocommerce_custom_orders_table_enabled` (currently `yes`).
  Coupon redemptions are in `<prefix>wc_order_coupon_lookup`.
