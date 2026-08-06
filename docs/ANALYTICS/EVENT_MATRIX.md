# Event Matrix

Full, verified list of every real `dataLayer.push()` event in the codebase, cross-referenced against GTM trigger/tag coverage. Source: direct code read (file:line citations in `docs/gtm-ga4-production-readiness-audit-2026-07-12.md` and the Phase 9 audit background research) + live GTM inspection.

**Note (2026-07-13, Phase 10, resolved same day):** "Covered by GTM" below means GTM's own trigger/tag configuration matches these event names in the repo/staging codebase. Earlier on 2026-07-13, production ran `brave-hearts-bundle-pricing` v1.7.1 and most ecommerce events below didn't reach GTM correctly from production (wrong event name, missing ecommerce payload, or didn't fire at all). This was fixed the same day via an isolated analytics-only patch — production now correctly fires `view_item_list`/`select_item`/`view_item`/`add_to_cart`/`view_cart`/`begin_checkout`/`add_shipping_info`/`add_payment_info`/`bundle_add_to_cart`, live-reverified with full ecommerce payloads. See `RELEASES/BUNDLE_PRICING_ANALYTICS_PARITY_PRODUCTION.md`. `contextual_cta_click`'s attribution-parameter gap remains open (theme file, tracked in `KNOWN_ISSUES.md`) — this table's claim for that one event still doesn't hold on production.

## Fully covered by GTM (39)

All GA4-standard ecommerce events: `view_item_list`, `select_item`, `view_item`, `add_to_cart`, `remove_from_cart`, `view_cart`, `begin_checkout`, `add_shipping_info`, `add_payment_info`, `purchase`, `refund`.

Plus business-custom: `adventure_kit_signup`, `amazon_outbound_click`, `bundle_add_to_cart`, `bundle_format_selected`, `bundle_page_view`, `bundle_savings_applied`, `bundle_type_purchased` (added Phase 9, 2026-07-12), `complete_set_reached`, `contextual_cta_click`, `contextual_cta_view`, `format_selected`, `landing_page_cta_click`, `landing_page_view`, `lead_form_start`, `lead_form_view`, `lead_signup_success`, `signup_error`, `related_content_click`, `second_book_added`, `side_cart_cross_sell_clicked`, `side_cart_opened`, plus the 8-event popup set (`teacher_popup_view/close/submit/success`, `parent_popup_view/close/submit/success`).

## Intentionally excluded (1)

`bhp_debug_internal_order_purchase_suppressed` — staging-only debug event, correctly has no GTM trigger/tag.

## Deferred by explicit CSO decision (6) — see `KNOWN_ISSUES.md`

Phase 9 (2026-07-12) deliberately did not add these. Reason: the first analytics launch stays focused on traffic, book interest, format, Collection-vs-individual-purchase, cart, checkout, and revenue — not yet on secondary credibility/review-module engagement.

| Event | Fires where | Status |
|---|---|---|
| `bhp_direct_purchase_click` | `assets/js/nav.js:79` | Deferred |
| `customer_review_product_click` | `template-parts/components/amazon-review-showcase.php:151` | Deferred |
| `customer_review_source_click` | `amazon-review-showcase.php:136`, `bundle-landing-page.php:361` | Deferred |
| `customer_review_impression` | `amazon-review-showcase.php:71` | Deferred |
| `kirkus_review_link_click` | `kirkus-credibility.php:67,93` | Deferred |
| `kirkus_component_impression` | `kirkus-credibility.php:49` | Deferred |

## Generic variables — closed Phase 9 (2026-07-12)

`bhp_book`, `bhp_format`, `bhp_source` — attached to click/impression events via the generic `nav.js` `bhpBuildEventPayload()` mechanism (reads `data-bhp-book`/`data-bhp-format`/`data-bhp-source` HTML attributes, defaulting each to `''` if absent). All 3 now have matching GTM Data Layer Variables (`DLV - bhp_book`/`DLV - bhp_format`/`DLV - bhp_source`) and are wired into the tags whose events actually provide them:
- `amazon_outbound_click` (`functions.php:1701-1710`) — all 3, since that link unconditionally sets all three attributes with real values.
- `related_content_click` (`template-parts/guides/article-card.php:20`) — `bhp_source` only. That template only ever sets `data-bhp-source="related_content_module"`; it never sets `data-bhp-book`/`data-bhp-format`, so those two were deliberately left unmapped on this tag rather than wired to a permanently-empty value.
