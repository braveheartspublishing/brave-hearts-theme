# GA4 Status

**Last verified live: 2026-07-12** (site-side only — GA4 console itself was not accessible this session, no Google login available).

## Property
Measurement ID `G-7M42X19Z2T` — confirmed real (saved as a GTM constant variable in the live container). Property-level configuration (Enhanced Measurement, custom dimensions, custom metrics, existing events/conversions inside the GA4 console itself) has **not been independently verified** — no prior session's docs report having opened the GA4 property UI either.

## Is anything currently reaching GA4?
**No, confirmed live.** Checked both environments directly:
- **Production**: only Google script loading is `googletagmanager.com/gtag/js?id=AW-18315643536` — a Google Ads conversion tag injected by the **Google Listings & Ads (GLA) WooCommerce plugin**, architecturally unrelated to GTM/GA4. `bhp_gtm_container_id`/`bhp_ga4_measurement_id`/`bhp_consent_decision_approved` are all unset on production.
- **Staging**: zero Google scripts load, empty `dataLayer`. IDs *are* saved as WP options, but `bhp_staging_analytics_override` is off, so the code's own gate suppresses the loader.

## What needs to happen before GA4 receives real data
1. GTM container published (currently 0/103 changes submitted).
2. Consent resolved (see `CONSENT_STATUS.md`) — required for production regardless of GTM publish status.
3. Production `bhp_gtm_container_id`/`bhp_ga4_measurement_id` options set (currently unset).
4. `bhp_consent_decision_approved` explicitly approved by Andrew.

## Custom dimensions / conversions
Not yet configured in GA4 (or at least not independently confirmed configured). Recommended sets are documented in `docs/gtm-ga4-production-readiness-audit-2026-07-12.md` §8-9 — recommendations only, nothing implemented.

## Phase 7 validation, 2026-07-13 (overnight build) — dataLayer-level, not full GTM Preview

**GTM Preview UI could not be connected** — Tag Assistant's browser-automation handshake to `staging2.braveheartspublishing.com` timed out twice (a known limitation of this session's browser tooling with GTM Preview's popup-window flow, not a site or GTM configuration issue). Per the session's own retry discipline, switched to direct `dataLayer` inspection instead, using the pre-existing bounded `bhp_staging_analytics_override` QA mechanism (turned on for this session, confirmed turned back off afterward, `wp eval` confirmed no PHP fatal both times).

**Confirmed via direct `dataLayer` inspection on staging:**
- Consent default correctly resolves to `analytics_storage: granted` (staging-QA-override only), `ad_storage`/`ad_user_data`/`ad_personalization` stay `denied` — matches documented behavior exactly, advertising consent never inferred from analytics consent.
- `gtm.js` loads (container script fires) once the override + consent gate both pass.
- `view_item_list` (shop page): correct GA4-standard item array (item_id/name/brand/category/variant/price/quantity/index) for all 6 products.
- `view_item` (product page): correct currency/value/items payload.
- `add_to_cart` (real click, not page load — correct, matches expected action-triggered behavior): correct currency/value/items payload, plus this site's own `source: "product_page"` context field.
- `add_shipping_info` fires on direct checkout-page load, as expected.
- `begin_checkout` did **not** fire on a direct URL navigation to `/checkout/` — expected, not a defect: it's tied to the "Proceed to Checkout" click action from the cart, which this pass didn't exercise (direct-nav testing only, not a full click-through funnel).

**Not validated this pass** (requires either a working GTM Preview connection or a dedicated click-through session): exact tag-level firing confirmation inside GTM's own debugger, `bundle_type_purchased`/Complete Collection event scenarios, CTA click events, Adventure Kit/Teacher form events, full checkout click-through (`begin_checkout` via the real button), and GA4 DebugView (no GA4 console access this session). Phase 2's manual GTM console audit already confirmed every tag's trigger/parameter mapping directly, which is complementary but not a substitute for a live Preview-confirmed firing pass.

## Phase 10 finding, 2026-07-13: this validation was on STAGING — production ran different, older code — RESOLVED same day
**Historical note, now resolved.** The "Confirmed via direct `dataLayer` inspection on staging" section above was accurate for staging (`brave-hearts-bundle-pricing` v1.8.2). A same-day Phase 10 production validation pass found production was running v1.7.1 (dated 2026-07-06, pre-dating this Phase 7 validation's event architecture), so none of the positive results above held on production at that time. **This gap was closed the same day** via an isolated 7-file analytics-only patch (see `RELEASES/BUNDLE_PRICING_ANALYTICS_PARITY_PRODUCTION.md`) — live-reverified on production afterward: `view_item_list`, `select_item`, `view_item`, `add_to_cart`, `view_cart`, `begin_checkout`, `add_shipping_info`, `add_payment_info`, `bundle_add_to_cart` all now fire correctly with full GA4 ecommerce payloads, with zero commerce regressions. Production and staging are now equivalent for storefront ecommerce-analytics purposes (though staging's internal KPI/economics dashboard code remains ahead of production's — a separate, deliberately untouched module, see `DECISIONS.md`).
