# Dashboard Data Source Audit

Documents, for every dashboard KPI/field, exactly where its data comes
from, how reliable that source is, and what happens when the data is
missing. Written from direct, read-only inspection of real staging and
production order records (2026-07-05) — nothing here is assumed from
documentation alone.

## Architecture

Extends the existing `brave-hearts-bundle-pricing` plugin
(`includes/dashboard/`) rather than a new plugin — the plugin already
owns the canonical product/variation → format/title catalog
(`bundle-data.php`) that offer classification depends on; a second
plugin would fragment that single source of truth.

| Module | File | Responsibility |
|---|---|---|
| Cost config | `class-bhp-cost-config.php` | Canonical print/shipping/Stripe-fee assumptions |
| Offer classifier | `class-bhp-offer-classifier.php` | Pure function: order items → one offer type |
| Bookvault status | `class-bhp-bookvault-status.php` | Parses order notes for routing status/reference |
| Order metrics | `class-bhp-order-metrics.php` | Aggregates orders into the full KPI set |
| KPI cache | `class-bhp-kpi-cache.php` | Transient cache, invalidated on order status change |
| Dashboard page | `class-bhp-dashboard-page.php` | Read-only admin UI, `manage_woocommerce` gated |

## Per-KPI data sources

| KPI | Source | Field/method | Reliability | Missing-data behavior |
|---|---|---|---|---|
| Gross revenue | WC_Order | `get_total()` | High — native WC field | Sum is 0 if no orders; shown as "No orders in this period" |
| Orders | WC_Order query | `wc_get_orders(status: processing/completed)` | High | 0 shown plainly, not hidden |
| Average order value | Derived | revenue / order count | High | `null` → "No orders" (never a divide-by-zero) |
| Units sold / format mix | WC_Order_Item_Product | `get_variation_id()`/`get_product_id()` matched against `bhp_bundle_catalog()` | High | Non-catalog items counted separately, never silently dropped |
| Offer classification | Derived | `BHP_Offer_Classifier::classify_order()` | High, but only as good as the catalog ID list | Falls back to `other_needs_review`, never a wrong guess |
| Estimated gross profit | Derived | `BHP_Cost_Config::estimate_order_profit()` | **Estimated only** — see cost config | Always labeled `(est.)`, `cost_basis: estimated` |
| Bookvault routing status/reference | **Order notes** (`wc_get_order_notes()`) | Regex against note text — see below | Medium — depends on Bookvault's own note wording never changing | Falls back to `unknown`/`Pending`, never a false "routed" |
| Bookvault eligibility | Derived | Order line items matched against the 6 catalog IDs | High | Non-eligible orders excluded from the denominator entirely |
| Payment failures | WC_Order query | `wc_get_orders(status: failed)` count | High | 0 if none |
| Tracking / shipped status | **None exists** | — | N/A | Always shown as "not available from any current data source" |

## Bookvault routing — the actual mechanism (important finding)

Bookvault does **not** expose routing status via order postmeta or a
custom database table — confirmed directly:

```
wp post meta list <order_id>   # no bvlt_*/bookvault_* keys on orders
wp db query "SHOW TABLES LIKE '%bookvault%'"   # empty
wp db query "SHOW TABLES LIKE '%bvlt%'"        # empty
```

(Bookvault *does* store `bvlt_locations` / `bvlt_liked` on the
**product/variation**, confirming catalog mapping — just not on orders.)

The only trace of order-level routing is the order note Bookvault's own
plugin writes at routing time. Two patterns observed directly on real
orders:

```
Success: "Order saved with status Active as BV2796848"   (order #355)
Success: "Order saved with status Draft as BV2796764"    (order #353)
Failure: "Failed to read line_items: Notice - The Bookvault plugin
          scans all incoming orders to identify those specifically
          intended for Bookvault to fulfill..."           (order #351)
```

`BHP_Bookvault_Status::SUCCESS_PATTERN` / `FAILURE_PATTERN` implement
these exact regexes. **If Bookvault ever changes its note wording, every
order will report `unknown` rather than a false positive** — this is a
deliberate fail-safe, not an oversight.

Time-to-route is computed as `(note timestamp) - (order->get_date_paid())`.
Observed real value: order #355 routed 5 seconds after payment.

## Known gaps (documented, not silently worked around)

- **No tracking/shipment data exists anywhere** — confirmed by searching
  every order note ever recorded on this store for the word "track"
  (zero matches). The dashboard shows this section as an explicit
  "not available" state rather than fabricating a status.
- **No Bookvault balance visibility** — would require a manual portal
  login; not automatable from WordPress.
- **Cost figures are estimates**, not actual per-order Bookvault
  invoices — see `class-bhp-cost-config.php` for exact sourcing/dates.
- **Stripe fees are estimated** from the standard published 2.9% + $0.30
  rate, not this account's actual negotiated rate.

## Known defect found and fixed during staging verification

`wc_get_orders()` can return refund objects
(`Automattic\WooCommerce\Admin\Overrides\OrderRefund` under HPOS)
alongside real orders when a broad date range includes a refunded order —
refund objects don't implement `get_order_number()` and fatal any caller
that assumes a plain `WC_Order`. Fixed in
`BHP_Order_Metrics::get_valid_paid_orders()` with an explicit
`instanceof WC_Order && ! ( $item instanceof WC_Order_Refund )` filter at
the single point orders are fetched, protecting every downstream caller.
Found and fixed on staging before this ever reached production.
