# Event Dictionary (Phase 1B)

Every `window.dataLayer.push()` event in the codebase as of 2026-07-06,
its trigger, and its payload shape. Supersedes the "before" state
recorded in the original `docs/analytics-event-inventory.md` (kept for
history) — this is the "after" state once Phase 1B's reshaping landed.

## Ecommerce item schema

Every ecommerce event's `items[]` array uses this shape, built by
`bhp_bundle_ga4_item()` (PHP, server-rendered events) or
`ga4ItemFromCartLine()` (JS, client-rendered events from the live Store
API cart response — never a second, parallel source of price data):

| Field | Value |
|---|---|
| `item_id` | WooCommerce product or variation ID (string) |
| `item_name` | Catalog title, e.g. "Adventures of Charlotte and Henry: Mount Everest" |
| `item_brand` | Always `"Brave Hearts Publishing"` |
| `item_category` | Always `"Children's Books"` |
| `item_category2` | `"Paperback"` or `"Hardcover"` |
| `item_variant` | Same as item_category2 |
| `price` | Unit price (float) |
| `quantity` | int |
| `discount` | Only present when a real per-unit discount applies — never a fabricated `0` |
| `item_list_name` | Only present when the event has list context (e.g. `"Shop"`) |
| `index` | Position in a tracked list (1-based) — only present on `view_item_list`/`select_item` items |

## GA4-standard ecommerce events

| Event | Trigger | Source | Fields beyond `items[]` |
|---|---|---|---|
| `view_item_list` | Shop archive page load, or a single product page's Related Products section rendering | `bundle-analytics.php` (`woocommerce_after_shop_loop`, `woocommerce_related_products` filter) | `item_list_id`, `item_list_name` — items include `index` (1-based position) |
| `select_item` | Click on a product-card link inside a tracked list (Shop loop or Related Products) | `bhp-list-tracking.js` | `item_list_id`, `item_list_name` — matched against the clicked link's resolved `href`, never fires for an unrelated click (e.g. Add to Cart button, review link) |
| `view_item` | Single product page load | `bundle-analytics.php` (`woocommerce_after_single_product`) | `currency`, `value` |
| `add_to_cart` | Any successful Store API add (product page, cross-sell, bundle page) | `bundle-drawer.js` | `currency`, `value` (added items only), `source` (`product_page`\|`cross_sell`\|`bundle_page`), `bundle_type` where applicable |
| `remove_from_cart` | Quantity reduced to 0, or explicit Remove click | `bundle-drawer.js` | `currency`, `value` |
| `view_cart` | Side-cart drawer opens (any trigger) | `bundle-drawer.js` | `currency`, `value` (full cart) |
| `begin_checkout` | "Secure Checkout" clicked in the drawer | `bundle-drawer.js` | `currency`, `value` (full cart), `source: 'side_cart'` |
| `add_shipping_info` | A shipping rate becomes selected on the Blocks checkout page (detected via the underlying Store API `/cart`, `/cart/update-customer`, `/cart/select-shipping-rate` responses — see below) | `bhp-checkout-events.js` | `event_id` (deterministic: `add_shipping_info_<rate_id>`), `currency`, `value`, `shipping_tier` (the rate's display name) |
| `add_payment_info` | A payment-method radio is selected (or already pre-selected) on the Blocks checkout page | `bhp-checkout-events.js` | `event_id` (deterministic: `add_payment_info_<gateway_id>`), `currency`, `value`, `payment_type` (the gateway's internal ID, e.g. `"stripe"` — never the label text or any card data) |
| `purchase` | `woocommerce_thankyou`, **once per order** (deduplicated) | `bundle-analytics.php` | `transaction_id`, `event_id`, `currency`, `value` (excl. tax), `tax`, `shipping`, `coupon` |
| `refund` | `woocommerce_order_refunded` | `bundle-analytics.php` | `transaction_id`, `event_id`, `currency`, `value` |

### Why `add_shipping_info`/`add_payment_info` use network/DOM detection instead of a "step" hook

This store's checkout is the WooCommerce Blocks Checkout block — a
single scrollable page with distinct sections (Contact/Shipping/Payment),
not a classic multi-page step wizard — confirmed by live DOM inspection
2026-07-06. There is no "advance to next step" moment to hook. Shipping
detection observes the real Store API responses the checkout block
itself already makes (a stable, version-independent contract); payment
detection observes the real payment-method radio
(`input[name="radio-control-wc-payment-method-options"]`, confirmed live)
since this store has multiple real gateways configured (Stripe, PayPal)
and a real selection exists to observe.

### Scope of `view_item_list`/`select_item`

Deliberately limited to the Shop archive page and Related Products —
both are native, unmodified WooCommerce loops with one stable product per
card. Content/marketing grids (the Books landing page's "adventure"
cards, the Complete Collection bundle page) show grouped/bundle offers
rather than individually addressable products-with-stable-IDs-per-card
and remain out of scope — extending to them would need a small redesign
of those templates' data model, not just an analytics change.

## Business-specific events

| Event | Trigger | Source | Notes |
|---|---|---|---|
| `format_selected` | Variation chosen on a variable product | `bundle-drawer.js` | Renamed candidate for `select_item` deferred — kept as-is pending real usage data |
| `bundle_format_selected` | Format toggle on Complete Collection page | `bundle-landing.js` | |
| `bundle_page_view` | Complete Collection page load | `bundle-landing.js` | Candidate GA4 mapping: `view_complete_collection` (not yet renamed) |
| `side_cart_opened` | Drawer opens (any trigger) | `bundle-drawer.js` | |
| `side_cart_cross_sell_clicked` | "Add This Adventure" clicked | `bundle-drawer.js` | |
| `second_book_added` / `complete_set_reached` | Bundle progress milestones | `bundle-drawer.js` | Still string-prefix-matched against user-facing copy — fragile, flagged in the original audit, not fixed in this phase |
| `bundle_add_to_cart` / `bundle_savings_applied` | Bundle-form submissions | `bundle-drawer.js` | |
| `<prefix>_view` / `_close` / `_submit` | Popup lifecycle (parent/teacher) | `mariana-popup.js` | Unchanged this phase |
| `adventure_kit_signup` | Adventure Kit thank-you page load (only reachable via the whitelisted Mailchimp redirect key) | `page-adventure-kit-thank-you.php` | Phase 1B; Phase 1C added `lead_offer`, `audience`, `placement`, `signup_method` and a sessionStorage refresh-dedup guard (a plain page refresh was previously refiring this event) |
| `lead_signup_success` | POST/redirect success feedback rendering inline (forms with no dedicated thank-you page — footer, adventure_club, inline_blog, etc.) | `template-parts/acquisition/signup-form.php` | Phase 1C. `lead_offer`, `audience`, `placement`, `signup_method`. Never fires for forms that already have their own named success event (`adventure_kit_signup`) to avoid double-counting. Never contains email/name. sessionStorage refresh-dedup guard. |
| `signup_error` | POST/redirect error feedback rendering inline (any acquisition form) | `template-parts/acquisition/signup-form.php` | Phase 1C. `lead_offer`, `audience`, `placement`, `signup_method`, `error_reason` (one of the fixed enum values `invalid`\|`missing_name`\|`unavailable`\|`error` — never raw exception text or user input). sessionStorage refresh-dedup guard. |
| `amazon_outbound_click` | Amazon affiliate button click | `functions.php` (renamed from `bhp_amazon_affiliate_click`) | Matches the Phase 6 required event name exactly |
| generic `data-bhp-event` clicks | Any element with the attribute | `nav.js` | Unchanged |
| generic `data-bhp-impression-event` | Element scrolled into view | `nav.js` | Unchanged |
| `bhp_debug_internal_order_purchase_suppressed` | An internal/test order reaches the thank-you page | `bundle-analytics.php` | **Staging only**, never fires on production — lets a developer confirm suppression without a fake `purchase` event ever appearing |
| `contextual_cta_click` | Click on any CTA rendered by `BHP_CTA_Engine::render()` | `nav.js` (generic `data-bhp-event`) | Phase 1D. `cta_id`, `cta_placement`, `cta_destination_type`, `audience`, `funnel_stage`, `variant`. Reuses the existing generic click handler — no new JS. |
| `contextual_cta_view` | CTA scrolled into view (50% threshold, fires once) | `nav.js` (generic `data-bhp-impression-event`) | Phase 1D. Same field set as `contextual_cta_click`. |
| `related_content_click` | Click on a "Related Field Notes" article card | `template-parts/guides/article-card.php` via `nav.js` | Phase 1D. `bhp_source=related_content_module`. |
| `landing_page_view` | Campaign landing page (`BHP_Campaign_Landing`) scrolled into view | `nav.js` (generic `data-bhp-impression-event`) | Phase 1D. Carries `bhp_source` = the campaign_id. |
| `landing_page_cta_click` | Click on a campaign-landing product/CTA block | `class-bhp-campaign-landing.php` via `nav.js` | Phase 1D. |
| `lead_form_view` | Any acquisition form scrolled into view | `template-parts/acquisition/signup-form.php` via `nav.js` (generic `data-bhp-impression-event`) | Phase 1D. `lead_offer`, `audience`, `placement`. Never contains email/name. |
| `lead_form_start` | First focus into an acquisition form's email field (fires once per element via native `{once:true}`) | `template-parts/acquisition/signup-form.php` via `nav.js` (new `data-bhp-focus-event` mechanism) | Phase 1D. Same field set as `lead_form_view`. Focus only — never fires on blur/change/keystroke, so no field content is ever read. |

**Not built this phase:** `classroom_resource_download`,
`expedition_guide_download`, `click_complete_collection_cta`,
`related_book_click` (no dedicated related-BOOK module exists yet,
distinct from the related-CONTENT module above — see the Phase 1D
architecture doc), `collection_discovery_click` (the CTA engine's
`collection_paperback`/`collection_hardcover` destinations fire the
existing `contextual_cta_click` rather than a separate event name) —
flagged for a follow-up session rather than fabricated placeholders.

**Do not claim GA4 receives any Phase 1D event above until the external
GTM container is configured to listen for it** — these events reliably
reach `window.dataLayer` (verified by the automated test suites), but
GTM tag/trigger configuration is a separate, manual, external step (see
`docs/gtm-configuration-blueprint.md`).

## Common fields present on most events

- `page_path` — `window.location.pathname`
- `event` — the event name itself
- Server-rendered events additionally have no client-side fields (no
  `page_path`) since they're printed inline on the page that generated
  them.

Fields explicitly required by Phase 5 (`event_id`, `environment`,
`consent state`, campaign identifiers) are **not yet attached to every
event** — only `purchase`/`refund`/`add_shipping_info`/`add_payment_info`
carry `event_id` today (the events where deterministic deduplication
matters most: a real financial event, or a checkout-recalculation-prone
step). Attaching `environment`/campaign identifiers to every event
dataLayer-wide is deferred; the debug panel (Phase 14) separately
surfaces environment and consent state alongside the captured event list
for validation purposes.
