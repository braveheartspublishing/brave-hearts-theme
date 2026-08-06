# Order Provenance Audit — Executive Dataset Correction (2026-07-06)

Written after discovering the dashboard's executive KPIs (gross sales,
orders, units, offer mix, estimated profit) were including every order in
the store's history regardless of whether it was a genuine customer
purchase. On investigation, **every order in the database at the time of
this audit was an internal test/verification order** — none showed
evidence of a genuine third-party customer. Enforced in code by
`BHP_Order_Provenance` (`includes/dashboard/class-bhp-order-provenance.php`).

## Why this is an explicit, documented list — not an automated rule

There is no reliable signal in this store's order data to detect "this was
a test" automatically:

- No `_stripe_livemode` postmeta exists on any order (this WooCommerce
  Stripe gateway version doesn't record it) — live vs. test Stripe mode
  cannot be read from the order itself.
- `_created_via` is `store-api` on every order checked, including ones
  definitively known to be internal tests — the real checkout flow was
  used deliberately, not the wp-admin "new order" screen, so this field
  cannot distinguish admin testing from a genuine sale.
- IP address and note-text patterns are strong circumstantial evidence
  (used to build the list below) but are not something a fully-automated
  rule should re-derive on every dashboard load — a genuine future
  customer could share an IP with Andrew's own network, and a future
  genuine Bookvault "Draft" note is not itself suspicious.

Given that, this list is maintained the same way `BHP_Offer_Classifier`
already documents `KNOWN_LEGACY_PRODUCT_IDS`: a short, explicit,
human-reviewed list, corrected by Andrew's direct confirmation when
evidence is circumstantial rather than definitive. A per-order meta
override (`_bhp_order_provenance_override`) exists for future corrections
without a code deploy.

## Per-order evidence

| Order | Origin | Payment | Fulfillment | Evidence |
|---|---|---|---|---|
| #317 | Production payment test | Real, live-mode | n/a | Refund reason literally states "Test-mode refund verification" — definitive, in the order's own data |
| #318 | Production payment test | Real | n/a | Same IP/day as #317; contains a failed-then-retried charge consistent with deliberate payment-flow testing; legacy product ID |
| #319 | Failed payment (test cluster) | Failed, never captured | n/a | Same IP/day/cluster as #317/#318; ended in `failed` status; legacy product ID. Was already excluded from executive KPIs by the pre-existing status filter, but still inflated the raw "payment failures" count before this correction |
| #321 | Production payment test | Real | n/a | Same IP/day as #322, 10 minutes apart; legacy product ID; timing matches this project's own documented Mariana catalog-remediation testing phase |
| #322 | Production payment test | Real | n/a | Same IP/day as #321/#318/#317/#319 |
| #336 | **Production live-mode internal fulfillment test** | Real, live-mode | **Real — manually fulfilled** | Confirmed by Andrew (2026-07-06): the first Mariana Trench paperback fulfillment test. Automatic Bookvault routing did not occur; Andrew manually created the Bookvault record afterward (ref `BV2793822`, manual reference `43908-#00001`). Real payment AND real fulfillment, but still an internal operational test, not an external customer sale |
| #351 | Production payment test | Real, live-mode | Declined by Bookvault | Definitively documented in this project's own prior session history as an explicitly Andrew-approved live test order placed specifically to verify Bookvault routing (see `docs/bookvault-chronology.md`) |
| #353 | Production payment test | Real, live-mode | Automatic — Draft | Its Bookvault "Draft" reference (`BV2796764`) is the exact value later hardcoded as example/fixture data in this plugin's own test suite — strong documentary evidence of a deliberate verification order |
| #355 | Production payment test | Real, live-mode | Automatic — Active | Same IP/session as #351/#353 (2026-07-05), completing a deliberate 3-order sequence exercising Bookvault's declined/Draft/Active status branches in one sitting |

No genuine third-party customer order has been identified as of this
writing. Every confirmed test order above was placed from one of only two
IP addresses across the entire dataset.

## Bookvault fulfillment: automatic vs. manual

A real Bookvault record can exist for an order two different ways, and the
dashboard now distinguishes them explicitly (`BHP_Order_Metrics::compute_kpis()`,
`bookvault_manual_fulfillment_count` / `bookvault_created_count` /
`bookvault_total_records_count`):

- **Automatic**: the WooCommerce → Bookvault plugin integration created the
  record itself (#353, #355).
- **Manual**: Andrew created the record directly in the Bookvault portal,
  because automatic routing did not occur (#336). This is **not derivable
  from WooCommerce order data at all** — no BVRef postmeta or order note
  exists for a manually-created record — so it is documented the same way
  as the origin list above, in `BHP_Order_Provenance::MANUALLY_FULFILLED_BOOKVAULT_ORDERS`.

Totals as of this writing:

| Metric | Value |
|---|---|
| Total Bookvault records tied to Brave Hearts orders | 3 |
| Automatically created from WooCommerce | 2 (#353, #355) |
| Manually created after routing failure/test | 1 (#336) |
| Automatic-routing eligible denominator | 2 |
| Automatic-routing successes | 2 |
| Automatic-routing success rate | 100% |
| Manually fulfilled | 1 |
| Active fulfillment action required | 0 |

`#336` is correctly excluded from the automatic-routing denominator
(it predates/was outside successful automatic integration routing — the
same `legacy_pre_integration` exclusion reason any other pre-integration
order gets) but is still counted as a genuine, fulfilled Bookvault record
in the total/manual figures above. It is never counted as an external
customer order merely because the payment was live and fulfillment
occurred.

## Corrected executive KPI totals (last 30 days, as of 2026-07-06)

| Metric | Before this correction | After |
|---|---|---|
| Gross customer sales | $153.96 | **$0.00** |
| Customer refunds | $38.97 | **$0.00** |
| Net customer revenue | $114.99 | **$0.00** |
| External customer orders | 8 | **0** |
| Customer units sold | 7–10 | **0** |
| Customer AOV | $19.25 | n/a |
| Genuine customer payment failures | 1 | **0** |
| Internal/test refunds (new figure) | — | $38.97 |
| Internal/test payment failures (new figure) | — | 1 |

## Source-of-truth hierarchy

1. **Andrew's direct confirmation** of an order's real-world origin (as
   recorded in `KNOWN_TEST_ORDER_IDS` / `MANUALLY_FULFILLED_BOOKVAULT_ORDERS`)
   — authoritative, since no WooCommerce data field can answer this
   question on its own.
2. **A per-order manual override** (`_bhp_order_provenance_override` meta),
   for correcting a specific order without a code deploy.
3. **WooCommerce's own order status** (failed / processing / etc.) — used
   for the coarse "was this even a completed checkout" gate before
   provenance is considered at all.
4. **Unknown** — an order with only circumstantial evidence sits in
   `NEEDS_CONFIRMATION_ORDER_IDS` and defaults to excluded from executive
   KPIs until Andrew confirms it one way or the other.

## Update procedure

When a new order needs provenance review: add it to
`KNOWN_TEST_ORDER_IDS` (confirmed test) or `NEEDS_CONFIRMATION_ORDER_IDS`
(circumstantial only) in `class-bhp-order-provenance.php`, with a one-line
evidence comment, and update the table above in the same commit. If it
involved a manually-created Bookvault record, add it to
`MANUALLY_FULFILLED_BOOKVAULT_ORDERS` too.
