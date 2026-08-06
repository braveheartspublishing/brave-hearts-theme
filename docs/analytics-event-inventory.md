# Analytics Event Inventory

Every `window.dataLayer.push()` call site in the codebase as of
2026-07-05, audited directly from source (not from memory of what was
"supposed" to be implemented). Confirmed via live testing (see prior
session reports): `window.dataLayer` is correctly initialized sitewide
and every event below does push into it — **but no GTM container or GA4
tag exists anywhere on the site, so none of this data currently reaches
any analytics platform.** This inventory is what a GTM container would
need to listen for on day one.

## Client-side events (assets/js and plugin JS)

| Event | Trigger | Payload | Source file | Duplicate risk | Missing fields |
|---|---|---|---|---|---|
| `format_selected` (product page) | jQuery `found_variation` on a variable-product form | `variation_id` | `bundle-drawer.js` | Low | none |
| `format_selected` (Complete Collection page) — actually `bundle_format_selected` | Format toggle clicked | `format` | `bundle-landing.js` | Low | none |
| `bundle_page_view` | Complete Collection page load | `{}` | `bundle-landing.js` | Low (one per pageview) | no page/referrer context beyond `page_path` |
| `add_to_cart` | Any successful Store API add — product page, bundle page, cross-sell | `product_id`, `variation_id`, `quantity`, `source` (`product_page`\|`bundle_page`\|`cross_sell`) | `bundle-drawer.js` | Low | **no price/value field** — GA4's `add_to_cart` event expects an `items[]` array with price; current payload has no price at all |
| `side_cart_opened` | Drawer opens (any trigger) | `{}` | `bundle-drawer.js` | Medium — fires every reopen, not just first open per session | none |
| `side_cart_cross_sell_clicked` | "Add This Adventure" button clicked | `title_key` | `bundle-drawer.js` | Low | none |
| `second_book_added` | Drawer message text starts with "You saved" | `{}` | `bundle-drawer.js` | Low | fragile trigger — depends on message text starting with a specific string, not a structured flag |
| `complete_set_reached` | Drawer message text starts with "Best Value" | `{}` | `bundle-drawer.js` | Low | same fragility as above |
| `bundle_add_to_cart` | Bundle/smart-complete-set form submitted | `format`, `bundle_type`, `product_count`, `cart_value` (or `already_complete: true`) | `bundle-drawer.js` | Low | none |
| `bundle_savings_applied` | A smart-complete-set add reaches all 3 titles | `format`, `savings_amount`, `cart_value` | `bundle-drawer.js` | Low | none |
| `checkout_started` | "Secure Checkout" clicked in drawer | `source: 'side_cart'` | `bundle-drawer.js` | Low | **no cart value/items** — GA4's `begin_checkout` expects a value + items array |
| `<prefix>_view` / `_close` / `_submit` / `_success` | Popup lifecycle (parent or teacher popup) | `source`, `page_type` | `mariana-popup.js` | Low | none |
| `bhp_direct_purchase_click` | Nav-level "buy now" style link click (see `nav.js`) | `bhp_book`, `bhp_format: ''` | `nav.js` | Low | format is always empty string — never actually populated |
| generic `data-bhp-event` clicks (incl. Amazon affiliate link clicks, `customer_review_source_click`) | Any element with `data-bhp-event` attribute clicked | `bhp_book`, `bhp_format`, `bhp_source` | `nav.js` | Low | none |
| generic `data-bhp-impression-event` (Kirkus/review impressions) | Element scrolled into view | `bhp_book`, `bhp_source` | `nav.js` | Low | none |

## Server-side events (PHP, printed inline via `bhp_bundle_print_datalayer_push()`)

| Event | Trigger | Payload | Duplicate risk | Missing fields |
|---|---|---|---|---|
| `product_viewed` | `woocommerce_after_single_product` (every product page load) | `product_id`, `product`, `price` | Low (one per pageview) | no currency field |
| `purchase_completed` | `woocommerce_thankyou` hook | `order_id`, `order_total`, `bundle_types[]` | **HIGH** — `woocommerce_thankyou` fires on every visit/refresh of the order-received page, not just the first. **No dedup mechanism exists today.** | no `items[]`, no `currency`, no tax/shipping breakdown — not GA4-Enhanced-Ecommerce shaped yet |
| `bundle_type_purchased` (one per qualifying bundle) | Same hook, fired once per bundle type in the order | `bundle_type`, `order_id` | Same HIGH risk as above (same hook) | none beyond the shape issue |

## Priority fixes before wiring to GA4 (see GA4/GTM implementation plan)

1. **Purchase deduplication is the single most important gap.** `purchase_completed` must not double-count revenue if a customer refreshes or returns to the thank-you page. Fix: either (a) a GTM trigger condition checking a `sessionStorage` "already fired for this order_id" flag, or (b) reshape the PHP hook to only fire once by checking/setting an order meta flag (e.g. `_bhp_purchase_event_fired`) — the meta-flag approach is more reliable since it survives across devices/sessions, unlike sessionStorage.
2. **No `items[]` array or `currency` field anywhere** — every add-to-cart/checkout/purchase event needs reshaping to GA4's expected Enhanced Ecommerce item structure before mapping.
3. **Two events rely on matching message text** (`second_book_added`, `complete_set_reached` triggered by string-prefix checks against user-facing copy) — fragile if that copy ever changes for a wording reason unrelated to analytics. Recommend switching to a structured flag once GA4 wiring work begins, to avoid analytics silently breaking on a future copy edit.
4. `bhp_direct_purchase_click`'s `bhp_format` field is always empty — either populate it or drop it.

## Confirmed via live testing (2026-07-05)

- `window.dataLayer` exists and is an array on every page (sitewide init confirmed).
- Events fire correctly and in the right order during a real add-to-cart flow (verified: `product_viewed` → `format_selected` → `add_to_cart` → `side_cart_opened`).
- **Zero network requests** to `googletagmanager.com`, `google-analytics.com`, or any other analytics domain on any page or action tested. No GTM container script, no `gtag.js`, no GA4 measurement ID anywhere in page source.
