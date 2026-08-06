# Phase 4 — Production Stabilization & Measurement Foundation

Date: 2026-07-05. Scope: post-launch stabilization, no storefront
redesign, no pricing/mapping changes, no production deploys beyond the
explicitly-scoped dashboard-bug-fix commits (staging only).

## 1. Production health audit

Read-only review of PHP/Stripe/webhook/WooCommerce logs, order notes,
and current catalog state.

| Area | Finding | Classification |
|---|---|---|
| PHP fatal errors | None (`wp eval` clean on every check) | No issue found |
| Stripe log errors | 20 occurrences, all from orders #318/319/321/322/336 (pre-launch test-mode orphaned checkout sessions), none since deployment | Expected behavior (historical) |
| Webhook delivery failures | Zero, today | No issue found |
| Order #351 Bookvault routing failure | Confirmed historical, already documented, no new occurrence | Expected behavior (already known/resolved via manual resend) |
| Price/stock drift | None — all 6 editions confirmed at approved prices/stock | No issue found |
| Bookvault mapping integrity | All 6 confirmed intact (SKU, ISBN, GTIN, `bvlt_locations`, `bvlt_liked`) | No issue found |
| Amazon links | All 3 product pages + homepage reviews render correctly | No issue found |
| Popup/cart conflict | Fixed and verified in the prior deployment phase | Resolved |
| Coupon-stacking guard | Existing automated test covers it; no live coupon exists on production to test end-to-end (would require creating test data) | Needs more evidence (low priority) |
| Bookvault balance/production/tracking status | Not accessible from any WordPress data source | Needs more evidence (external, requires manual portal check) |

## 2. Order-monitoring framework

See `docs/order-monitoring-workflow.md` for the full 14-step checklist
and per-offer-type quick-reference. Key finding: **Bookvault routing
status is only available via order-note text parsing** (no postmeta, no
custom DB table) — documented exact regex patterns in
`docs/dashboard-data-sources.md`, verified against three real orders
(#351 failure, #353/#355 success).

## 3. Lightweight executive dashboard

Built as a new `includes/dashboard/` module inside the existing
`brave-hearts-bundle-pricing` plugin (architecture decision: extending
rather than a new plugin, since the plugin already owns the canonical
product/format catalog needed for offer classification). Location:
WooCommerce → Brave Hearts Dashboard, gated on `manage_woocommerce`.

**Built and verified on staging only — not deployed to production.**

Components: cost config, offer classifier (with 24 passing automated
tests), Bookvault status parser, order metrics aggregator, 5-minute
transient cache (auto-invalidated on order status change), and the
read-only admin page itself (KPI cards, offer/format mix tables,
fulfillment status, operational warnings, recent-orders table, a
clearly-labeled inactive "Analytics connection required" panel).

**Real bug found and fixed during staging verification**: `wc_get_orders()`
can return refund objects that lack `get_order_number()`, causing a
fatal error on any date range including a refunded order. Fixed with a
defensive `instanceof WC_Order` filter at the single data-fetch point.
This is exactly the kind of defect the staging-first process exists to
catch — found before ever touching production.

## 4. Funnel smoke testing

Full non-paid smoke test already executed as part of the prior
deployment phase (navigation, all 6 products, full bundle matrix,
checkout, mobile, popup, Amazon links) — see that phase's report. This
phase additionally re-verified: coupon validation (rejects nonexistent
codes correctly), and browser back/forward drawer-state persistence
(confirmed correct once given adequate async settle time — an initial
0-item reading was a test-script timing artifact, not a real defect).

## 5. Analytics event inventory

Full audit in `docs/analytics-event-inventory.md`. Every event fires
correctly into `window.dataLayer`; **zero network requests to any
analytics platform** (confirmed, no GTM/GA4 installed). Highest-priority
gap found: `purchase_completed` fires on every `woocommerce_thankyou`
page load, including refreshes — **no deduplication exists today**. This
must be fixed before GA4 wiring, or every thank-you-page refresh would
double-count revenue.

## 6. GA4/GTM implementation plan

Full plan in `docs/ga4-gtm-implementation-plan.md`. No placeholder ID
installed. Recommends GTM (not gtag-direct) for future extensibility.
Exact list of what Andrew must provide before any of this can execute:
a GTM Container ID or GA4 Measurement ID, a decision on GTM vs. direct
gtag, and edit access for validation.

## 7. Quiz vs. Reluctant Reader popup experiment specification

Full specification in `docs/experiment-quiz-vs-popup.md`. **Not
launched, no code written.** 50/50 first-party persistent assignment,
inherits all existing popup suppression rules (cart/checkout/drawer-open
guards), primary metric is revenue per exposed visitor, explicit
low-data-state handling so significance is never claimed prematurely.

## 8. UTM and attribution standard

Full standard with worked examples for every channel in
`docs/utm-attribution-standard.md`, mirrored as machine-readable YAML in
`content-engine/config/utm-standard.yaml`.

## 9. Blog-to-Pinterest system architecture

Scaffolding only — `content-engine/` directory with the full 14-stage
pipeline documented, 5 config YAML files (brand guidelines, funnel
routes, Pinterest boards, scoring rubric, UTM standard), and 6 JSON
schema templates for the per-blog pipeline artifacts. **No automation
runs, no Pinterest credentials referenced, no blog has been processed.**

## 10. Recommendation for next phase

**Stabilization is holding — no critical defects found in this phase
beyond the one caught and fixed on staging.** Recommended next steps, in
priority order:

1. Deploy the dashboard to production (needs your explicit approval —
   not done in this phase per instruction).
2. Fix the `purchase_completed` deduplication gap on staging before any
   GA4 wiring begins (small, isolated change).
3. Decide on GTM vs. direct GA4 and provide the container/measurement ID
   when ready — everything else in the implementation plan is ready to
   execute the same day that arrives.
4. Reshape `add_to_cart`/`checkout_started`/`purchase_completed`
   payloads to include `items[]`/`value`/`currency` before GA4 wiring,
   so the ecommerce reports are trustworthy from day one instead of
   needing a second pass.
5. The quiz-vs-popup experiment and Pinterest content engine are both
   fully specified and ready to move into actual build work whenever
   prioritized — neither is blocking anything else.

## Explicit boundaries respected this phase

No SKU, ISBN, product ID, variation ID, Bookvault mapping, or approved
price was modified. No paid order was placed. No customer communication
was sent. No customer PII appears in this report or in the dashboard's
recent-orders table (order number is the only customer-facing
identifier used throughout). The dashboard was built and tested on
staging only and has not been deployed to production.
