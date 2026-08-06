# Order-Monitoring Workflow

Reusable process for reviewing new real orders, backed by the same data
sources documented in `dashboard-data-sources.md`. No step here sends a
customer communication or modifies an order automatically.

## Per-order checklist

For any new order, in order:

1. **WooCommerce order status** — WooCommerce → Orders, confirm `processing` or `completed`.
2. **Stripe payment status** — read the order notes for "Stripe charge complete" (success) vs "Stripe SCA authentication failed" / a decline reason.
3. **Products and quantities** — confirm line items match what the customer actually selected (compare against the offer type shown on the dashboard).
4. **Discount** — confirm the Bundle Savings fee (if any) matches the offer type: $1.99 (2-book paperback), $2.99 (2-book hardcover), $3.98 (complete paperback), $4.98 (complete hardcover).
5. **Shipping** — confirm the charged rate matches the total-book-count tier ($1.99 / $2.99 / $3.99 / $4.99).
6. **Tax** — confirm a tax line is present and labeled (e.g. "Idaho Sales Tax") when applicable to the shipping address.
7. **Final total** — confirm subtotal − discount + shipping + tax = order total, exactly.
8. **Bookvault order creation** — read the order notes for "Order saved with status Active/Draft as BV\d+" (success) or "Failed to read line_items" (failure). See `dashboard-data-sources.md` for the exact patterns.
9. **ISBNs routed** — cross-check the order's line-item product/variation IDs against the six approved ISBNs (see `PROJECT_STATE.md` catalog table).
10. **Balance deduction** — not visible from WordPress; requires a manual Bookvault portal login if needed.
11. **Production status** — not visible from WordPress; requires a manual Bookvault portal login.
12. **Tracking return** — no tracking data source currently exists anywhere in this store's records (confirmed by exhaustive order-note search); requires a manual Bookvault portal check.
13. **Manual intervention needed?** — true only if step 8 shows failure/timeout past the 15-minute window (see `BHP_Order_Metrics::ROUTING_FAILURE_THRESHOLD_MINUTES`).
14. **Update dashboard warning state** — happens automatically; the dashboard's cache invalidates on any order status change, so the next admin page load reflects the new order without manual action.

## Per-offer-type quick checklists

Each checklist below is the *expected-correct* shape of a healthy order of that type — deviations are exactly what step-by-step review above is meant to catch.

**Single paperback** — 1 line item, $11.99 subtotal, no discount fee, $1.99 shipping.
**Single hardcover** — 1 line item, $17.99 subtotal, no discount fee, $2.99 shipping.
**Two-paperback bundle** — 2 distinct line items, $23.98 subtotal, −$1.99 discount, $2.99 shipping.
**Complete paperback set** — 3 distinct line items, $35.97 subtotal, −$3.98 discount, $3.99 shipping.
**Two-hardcover bundle** — 2 distinct line items, $35.98 subtotal, −$2.99 discount, $3.99 shipping.
**Complete hardcover collection** — 3 distinct line items, $53.97 subtotal, −$4.98 discount, $4.99 shipping.
**Mixed-format order** — verify per the Priority-7 rule: a format only keeps its discount if it independently reaches 3 distinct titles; a 2-distinct-title format inside a mixed cart gets **no** discount.

## First real Complete Collection order

The first live "complete paperback set" or "complete hardcover collection"
order (not a staging test) should get the full 14-step review above plus:

- A dashboard screenshot/export of that order's row from the Recent
  Orders table, kept as a reference baseline for what "healthy" looks
  like operationally.
- Explicit confirmation that all three ISBNs for that format routed in a
  single Bookvault submission (not three separate ones) — check the
  order note for a single `BV\d+` reference covering all three line
  items, not three different references.

## What this workflow deliberately does not do

- Never sends a customer email, SMS, or notification as part of review.
- Never modifies order status, refunds, or resends to Bookvault
  automatically — those remain explicit, human-triggered actions from
  the normal WooCommerce/Bookvault portal UI.
- Never assumes a customer's personal information is needed for
  operational review — every step above works from order number,
  product IDs, and status fields only.
