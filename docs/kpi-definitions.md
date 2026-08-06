# Brave Hearts Dashboard — KPI Definitions

Authoritative reference for every figure shown on **WooCommerce → Brave
Hearts Dashboard**. Written during the Phase 3 reconciliation pass
(2026-07-06). If a KPI's behavior ever changes, update this file in the
same commit — this document, not the code comments alone, is what Andrew
should read to understand what a number means.

**Source of truth for every figure**: live WooCommerce order/refund data,
read via `wc_get_orders()`. Nothing on the dashboard is manually entered,
hard-coded, or estimated from a sample — the only *estimated* values are
explicitly labeled "estimated" (cost/profit figures) because a real cost
input is not yet available (see `CSO_PRIVATE_REFERENCE.md` for the private cost-sources doc).

## Global conventions

- **Timezone**: all dates/times use `current_datetime()` / `wp_timezone()`
  — WordPress's own site-configured timezone (Settings → General), never
  server time or UTC. As of this writing the site is configured with a
  fixed UTC+0 offset (no named/DST-observing zone) — see
  `test-date-boundaries.php` for what happens if that's ever changed to a
  DST-observing zone.
- **Periods**: Today (local midnight → now), Last 7 days (6 full days back
  + today so far), Last 30 days (29 full days back + today so far), or a
  Custom range (admin-entered start/end dates, inclusive).
- **Prior-period comparison**: always an equal-length window immediately
  before the current period, with no gap and no overlap (prior period ends
  exactly 1 second before the current period starts). Exception: "Today"
  compares a *partial* current day (midnight → now) against a *full*
  immediately-preceding day (all of yesterday) — see the "Today" row
  below for why this is a known, accepted limitation rather than a bug.
- **Revenue-relevant order statuses**: `processing`, `completed`,
  `refunded` (see `BHP_Order_Metrics::VALID_PAID_STATUSES`). `refunded` is
  included because a fully-refunded order was still a genuine paid
  transaction; excluding it would make it silently vanish from every KPI
  instead of being shown with its refund clearly accounted for.
- **No customer PII anywhere**: every KPI and every recent-orders table
  row is built from order IDs, totals, dates, and status strings only —
  never a customer name, email, address, or phone number.

## Refund accounting — read this before trusting any revenue figure

- **Gross sales** = the sum of `$order->get_total()` for every
  revenue-relevant order created in the period. WooCommerce never rewrites
  an order's own total when it's refunded (refunds are separate
  `shop_order_refund` records), so this is a genuine *pre-refund* figure
  with no extra work needed to keep it that way.
- **Refunds** = the sum of refund amounts for every `shop_order_refund`
  record whose own `date_created` falls in the *selected period* —
  **regardless of when its parent order was created**. A refund issued
  today against a sale from three months ago shows up in today's
  "Refunds" figure, not retroactively edited into that old month's
  "Gross sales." This is a deliberate choice for a period-snapshot
  dashboard: it never rewrites a past period's historical totals.
- **Net revenue** = Gross sales (this period's *orders*) − Refunds (this
  period's *refund events*). Because these two figures can reference
  different underlying orders when a refund crosses a period boundary,
  net revenue is **not** a per-order reconciliation — it is "sales that
  started this period" minus "refund events that happened this period."
  Net revenue is never labeled "Gross" and vice versa.
- **Paid-order count** includes orders that were later fully refunded.
  Whether an order was paid and whether it was later refunded are
  different facts; the "Refunds" and refund-state-per-order column
  surface the second fact separately rather than making a refunded order
  disappear from the count of genuinely completed checkouts.
- **Refunded units**: summed from refund line-item quantities. A pure
  shipping-only or tax-only refund contributes `0` refunded units (no book
  was returned) — see the "Units sold" row.
- **Estimated profit after refunds** = Estimated gross profit − Refunds
  total (a simple pass-through reduction). This does **not** model Stripe
  retaining its processing fee on a refund, nor recover the print/shipping
  cost already spent on a refunded physical book — see `CSO_PRIVATE_REFERENCE.md` for the private cost-sources doc.

## KPI table

| Display name | Formula | Included statuses | Excluded statuses | Refund handling | Date field | Source | Cache key | Known limitation |
|---|---|---|---|---|---|---|---|---|
| **Gross sales** | Σ `order->get_total()` | processing, completed, refunded | pending, on-hold, failed, cancelled, trashed | Pre-refund by construction (see above) | `date_created` | `wc_get_orders()` | `kpis_{period}_{start:Ymd}` | None known |
| **Refunds** | Σ `abs(refund->get_total())` | n/a (refund objects, not orders) | — | This *is* the refund figure | Refund's own `date_created` | `wc_get_orders(type=shop_order_refund)` | same as period's main KPI key (computed inside `compute_kpis()`) | Can reference an order created in an earlier period (by design, see above) |
| **Net revenue** | Gross sales − Refunds | (derived) | (derived) | See above | (derived) | (derived) | (derived) | Not a per-order reconciliation — see above |
| **Orders** (paid-order count) | count of qualifying orders | processing, completed, refunded | pending, on-hold, failed, cancelled, trashed | Includes later-refunded orders (see above) | `date_created` | `wc_get_orders()` | same | None known |
| **Average order value** | Gross sales ÷ Orders | (same as Gross sales) | (same) | Uses pre-refund gross, not net | `date_created` | derived | same | Not refund-adjusted; a period with many large refunds will still show a high AOV |
| **Units sold** | Σ paperback + hardcover units across catalog line items | (same as Gross sales) | (same) | **Not refund-adjusted** — a refunded book still counts as "sold" here | `date_created` | `BHP_Offer_Classifier::classify_order()` | same | Does not subtract `refunded_units`; "Units sold" and "Refunded units" are shown as two separate figures on purpose |
| **Units per order** | Units sold ÷ Orders | (same) | (same) | Same as Units sold | `date_created` | derived | same | Same as Units sold |
| **Bundle purchase rate** | (orders classified as any 2-book/complete-set/mixed/both-complete offer) ÷ Orders × 100 | (same) | (same) | Not refund-adjusted | `date_created` | `BHP_Offer_Classifier` | same | A later-refunded bundle order still counts toward this rate |
| **Complete Collection purchase rate** | (orders classified as complete-paperback/complete-hardcover/both-complete) ÷ Orders × 100 | (same) | (same) | Not refund-adjusted | `date_created` | `BHP_Offer_Classifier` | same | Same as Bundle purchase rate |
| **Paperback vs. hardcover mix** | units_paperback / (units_paperback+units_hardcover), same for hardcover | (same) | (same) | Not refund-adjusted | `date_created` | `BHP_Offer_Classifier` | same | Same as Units sold |
| **Estimated gross profit** | Σ per-order (product revenue + shipping − discount − est. Stripe fee − est. print cost − est. ship cost) | (same as Gross sales) | (same) | Pre-refund (labeled "before refund impact") | `date_created` | `BHP_Cost_Config::estimate_order_profit()` | same | Every cost input is an *estimate*, not a confirmed invoice figure — see `CSO_PRIVATE_REFERENCE.md` for the private cost-sources doc. Never displayed without the word "Estimated." |
| **Estimated profit after refunds** | Estimated gross profit − Refunds total | (same) | (same) | Simple pass-through subtraction (see above) | (derived) | derived | same | Does not model Stripe fee retention on refunds or unrecoverable print cost precisely — see `CSO_PRIVATE_REFERENCE.md` for the private cost-sources doc |
| **Payment failures** | count of orders with status `failed` | failed | (all others) | n/a | `date_created` | `wc_get_orders(status=failed)` | same | None known |
| **Bookvault routing-success rate** | `bookvault_created_count` ÷ `bookvault_expected_count` × 100 | expected = catalog-eligible AND currently expected to fulfill (see below) | Refunded, cancelled, Bookvault-excluded, and legacy/pre-integration orders are removed from BOTH numerator and denominator, not just hidden from the count | n/a (fulfillment status, not revenue) | order note `date_created`, `BVRef` postmeta, order `date_paid` | `BHP_Bookvault_Status` + `BHP_Order_Metrics::bookvault_fulfillment_status()` | same | **2026-07-06 correction**: previously used a broader "catalog-eligible" denominator that inflated the count with orders never actually expected to route (refunded, Bookvault-declined, pre-integration) — see `docs/bookvault-chronology.md` for the full incident writeup and evidence |
| **Orders needing attention** (`bookvault_action_required_count`) | expected orders (see above) with no Bookvault record past the 15-minute retry window, OR with a genuine technical failure note | expected orders only | Refunded/cancelled/Bookvault-excluded/legacy orders are NEVER counted here, even if they have no Bookvault record | n/a | order `date_paid` | `BHP_Order_Metrics::is_routing_overdue()` | same | The 15-minute threshold is calibrated from order #351's observed real retry timings (+0m, +2m, +10m). **2026-07-06 correction**: this count previously included orders that were fully refunded or that Bookvault itself had declined to process — both are now excluded from the denominator entirely, not just from this count, so the aggregate KPI, the warning panel, and the per-row "Attention" column can never disagree (they all call the same underlying function) |
| **Bookvault fulfillment summary** (Active / Draft / Awaiting / Action required / Excluded) | see `docs/bookvault-chronology.md` for the full per-bucket definition | expected orders only (Active/Draft/Awaiting/Action required); Excluded is its own bucket with a reason breakdown | n/a | n/a | order note + `BVRef` + `date_paid` | `BHP_Order_Metrics::compute_kpis()` | same | New in the 2026-07-06 correction, replacing the old "Fulfillment status" table which conflated all of these into three coarse buckets |
| **Legacy / pre-catalog** (offer mix) | orders whose only line items are a documented legacy product ID (currently: product ID 12, the pre-variation Mariana Trench Paperback) | n/a | n/a | Not refund-adjusted | `date_created` | `BHP_Offer_Classifier::KNOWN_LEGACY_PRODUCT_IDS` | same | New in the 2026-07-06 correction. Excluded from bundle rate, Complete Collection rate, and format-mix calculations — identical treatment to how these orders behaved before this label existed (only the display label changed, not the math) |

## Bookvault fulfillment-eligibility rules (2026-07-06 correction)

Being a paid, catalog-eligible order is **no longer sufficient** to count
toward the Bookvault routing denominator. Full rationale and per-order
evidence in `docs/bookvault-chronology.md`; the rule summary:

An order is **expected to fulfill via Bookvault** only if ALL of:
1. It contains at least one catalog edition (`order_is_bookvault_eligible()`).
2. It is not fully refunded (a partial refund does not remove expectation).
3. It is not cancelled.
4. Bookvault itself did not explicitly decline to process it (an "excluded"
   note — see below).
5. It was paid on/after `BHP_Bookvault_Status::INTEGRATION_LIVE_SINCE`
   (2026-07-04 00:00:00 site-local — a conservative boundary between the
   last confirmed pre-integration order and the first confirmed
   post-integration order; update this constant if a more precise
   go-live timestamp is ever documented).

**Exception, in priority order**: a real Bookvault record (proven by the
order's own `BVRef` postmeta) always means "expected and fulfilled,"
overriding all of the above — a later refund/cancellation on an order
Bookvault already created does not retroactively un-count it. The
historical routing record is preserved.

## Source-of-truth hierarchy for Bookvault status

When multiple signals exist for one order, they are trusted in this order:

1. **`BVRef` order postmeta** — set by the Bookvault plugin only on orders
   it actually created. The single most reliable signal that a real
   Bookvault order exists; confirmed present exactly on orders that
   independently appear in the Bookvault portal, and absent on every order
   that doesn't.
2. **Bookvault's own order notes** — the only source for the specific
   state word (Active/Draft) and for distinguishing "excluded" (Bookvault
   declined the order) from "failed" (a genuine technical error).
3. **WooCommerce's own order/refund state** — status, `date_paid`,
   `get_total_refunded()` — used to decide fulfillment expectation, never
   to override what Bookvault itself reports about its own order.
4. **Unknown** — when none of the above yield a definite answer (no
   `BVRef`, no note), the order is either "legacy/pre-integration"
   (excluded, no action) or "pending"/"overdue" (still counted, decided by
   `is_routing_overdue()`) depending on when it was paid relative to
   `INTEGRATION_LIVE_SINCE`. A WooCommerce-note inference is never
   presented as a definitive CURRENT Bookvault state when `BVRef` or a
   later note says otherwise — see `BHP_Bookvault_Status::get_status()`.

## "Today" partial-vs-full-day comparison — explicit limitation

The "Today" period is **midnight through right now** (a partial, still-
accumulating window), but the prior-period comparison is **all of
yesterday** (a full 24-hour day). Checked at 8am, "Today" will always look
smaller than "Yesterday" purely because less time has elapsed — not
because anything is actually down. This is a known convention (shared with
most simple period-snapshot dashboards) and is *not* corrected to a
time-of-day-matched comparison in this pass. If this proves confusing in
practice, the fix is to compare "Today so far" against "Yesterday, same
time of day so far" instead of full-day yesterday — flagged here as a
candidate future refinement, not implemented now.

## Data-as-of timestamp and cache freshness

Added in the 2026-07-06 correction pass. A line under the period selector
reads "Data as of [site-timezone date/time] ([cached]|[freshly
calculated])." `BHP_KPI_Cache::get_with_meta()` records when a value was
actually computed (not merely when the page was requested) and whether
this page load served that cached value or triggered a new calculation.
The 5-minute cache TTL is unchanged; "Refresh now" still forces an
immediate recalculation, which updates both the figures and this
timestamp. This is a periodic snapshot, not a live feed, and the label
says so explicitly.

## Prior-period comparison — "No orders" vs. "New activity" vs. a real percentage

Three distinct situations were previously conflated into one misleading
"No prior-period data" label:

- **Prior period genuinely had zero orders** → "No orders in prior
  period." Determined by the prior period's own `order_count`, not by
  whether a specific metric's prior value happens to be zero.
- **Prior period had real orders, but this specific metric's prior value
  was zero** (e.g. 0% bundle rate) → "New activity," not a fabricated
  "+∞%" or "0%→N%" that implies a percentage calculation that is
  mathematically undefined from a zero base.
- **Both periods have real, non-zero data** → the actual absolute
  difference, percentage change, and direction arrow, as before.

## Custom date range

Added during this reconciliation pass (previously the date selector only
offered Today/7d/30d — there was no way to test or view a specific known
window). The admin enters an inclusive start and end date; internally the
end boundary is stored as *midnight of the day after* the entered end date
so an order at 23:59:59 on the selected end date is still included. The
matching prior-period comparison is an equal-length window immediately
before the custom range. Invalid input (malformed dates, end before start,
or a range longer than 366 days) is rejected and the dashboard falls back
to "Today" rather than running an unbounded or garbage query.
