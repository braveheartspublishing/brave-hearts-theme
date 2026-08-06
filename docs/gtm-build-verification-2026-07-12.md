# GTM Existing Build Verification — 2026-07-12

**Read-only verification pass. No GTM/GA4 changes made — Workspace Changes confirmed at 0 Modified / 103 Added / 0 Deleted before and after this session, all attributed to `andrew@braveheartspublishing.com`, 2 days ago. Live published Version 1 = "Empty Container" (published 6 days ago, before this build began) — nothing has ever gone live.**

## Methodology

Full list-level inventory captured for all 24 variables, 38 triggers, 39 tags (names, types, firing-trigger linkages). Deep parameter-level inspection performed on 3 representative tags (base Google Tag, `purchase`, `add_to_cart`) covering the config tag, a full-ecommerce tag with extra parameters, and an ecommerce tag with a bundle-specific parameter. The remaining 36 event tags follow an identical, consistent construction pattern (verified at list level: correct type, correct 1:1 trigger linkage by name) — not individually opened one-by-one given the volume, but cross-checked against the actual codebase dataLayer implementation from the Phase 9 audit (which has exact file:line citations for every real event and parameter).

## 1. Workspace Inventory — Confirmed

- **24 variables**: 1 constant (`GA4 Measurement ID`) + 23 Data Layer Variables — matches exactly.
- **38 triggers**: all type "Custom Event," each named `TRG - Custom Event - <name>`.
- **39 tags**: 1 `Google Tag` (base config) + 38 `Google Analytics: GA4 Event` tags, each firing on its matching trigger by name — confirmed 1:1, no orphaned triggers, no tag firing on the wrong trigger, no duplicate tags for the same event.
- **103 unpublished changes, 0 Submitted, 0 Published** — confirmed at start and end of this session, unchanged.

## 2. Event Coverage — Verified Against Real Codebase dataLayer

Cross-referencing GTM's 38 triggers against the full, verified event inventory from the Phase 9 audit:

**Fully covered (38):** all GA4-standard ecommerce events (`view_item_list`, `select_item`, `view_item`, `add_to_cart`, `remove_from_cart`, `view_cart`, `begin_checkout`, `add_shipping_info`, `add_payment_info`, `purchase`, `refund`) and all business-custom events except the gap below (popup 8-event set, CTA engine 2, lead funnel 4, bundle-specific 7, outbound 1, related-content 1, landing-page 2, format-selection 2).

**Intentionally excluded (1):** `bhp_debug_internal_order_purchase_suppressed` — staging-only debug event, correctly has no trigger/tag, matching the blueprint's explicit exclusion.

**Missing — the known six-event gap (6):** `kirkus_review_link_click`, `kirkus_component_impression`, `customer_review_impression`, `customer_review_source_click`, `customer_review_product_click`, `bhp_direct_purchase_click`.

**Missing — a 7th gap not previously scoped (1), found this pass:** `bundle_type_purchased` — fires from `plugins/brave-hearts-bundle-pricing/includes/bundle-analytics.php:418`, once per qualifying order, payload `{bundle_type, order_id}`. This is the dedicated, purpose-built signal for distinguishing a Complete Collection purchase from an individual-book purchase without parsing `items[]` composition — directly relevant to the exact business question in Section 5 below. **Recommend treating this as high-value, alongside `bhp_direct_purchase_click` and the two `customer_review_*_click` events**, since without it, "Complete Collection vs. individual" reporting depends on `items[]` inspection inside the `purchase` event rather than a clean, purpose-built event.

**Obsolete:** none — the pre-Phase-1B event vocabulary (`product_viewed`, etc.) doesn't exist in current code, only in a superseded doc.

**Undocumented but now covered:** none additional found this pass beyond the 6+1 above.

## 3. Six (Now Seven)-Event Gap Review

| Event | Fires where | Params | Business question | Covered elsewhere? | Recommendation |
|---|---|---|---|---|---|
| `bhp_direct_purchase_click` | `assets/js/nav.js:79` | `bhp_book`, `bhp_format` (always empty — known bug), `bhp_source` | Which book gets clicked "Buy Now" directly vs. added to cart first? | No | **Include** (matches your call) |
| `customer_review_product_click` | `template-parts/components/amazon-review-showcase.php:151` | generic `bhp_book`/`bhp_format`/`bhp_source` | Does a trust-content click lead to product interest? | No | **Include** (matches your call) |
| `customer_review_source_click` | `amazon-review-showcase.php:136`, `bundle-landing-page.php:361` | same | Does trust content drive outbound Amazon interest? | No | **Include** (matches your call) |
| `customer_review_impression` | `amazon-review-showcase.php:71` | none beyond generic | How often is trust content actually seen? | No | Optional, per your call — view-only, lower signal value than the click events |
| `kirkus_review_link_click` | `kirkus-credibility.php:67, 93` | generic | Does a Kirkus badge click drive engagement? | No | Secondary, per your call |
| `kirkus_component_impression` | `kirkus-credibility.php:49` | none beyond generic | How often is the Kirkus badge seen? | No | Secondary, per your call |
| `bundle_type_purchased` | `bundle-analytics.php:418` | `bundle_type`, `order_id` | **Directly answers**: was this a Complete Collection purchase? | Partially — `purchase.items[]` composition can be inspected manually, but there's no dedicated, GTM-native signal | **Recommend including** — same tier as the three you already approved, since it's the cleanest way to answer a question your own dashboard design explicitly needs |

**None of these 7 were added this pass** — audit only, per instruction.

Adding all three high-value clicks + `bundle_type_purchased` needs: for each, 1 new trigger (Custom Event, filtered on the event name) + 1 new tag (GA4 Event, mapped to existing generic DLVs — `bhp_book`/`bhp_format`/`bhp_source` for the three clicks, `DLV - bundle_type` [already exists] + a new `DLV - order_id` for the purchase-type event). No new variables needed for the click events since `bhp_book`/`bhp_format`/`bhp_source` aren't in the current 24-variable set either — **this is itself a small gap**: those three generic parameters (used by `amazon_outbound_click`, `related_content_click`, and the recommended new events) aren't currently mapped to any DLV, meaning even `amazon_outbound_click`'s tag (already built) likely isn't capturing them. Flagged in Section 4.

## 4. Variable and Parameter Validation

Deep-checked 3 tags directly; all correct. Naming-pattern cross-check against the full 24-variable list:

**Present and correctly used (deep-verified):** `event_id` (used on `purchase`, confirmed `{{DLV - event_id}}`), `bundle_type` (used on `add_to_cart`, confirmed `{{DLV - bundle_type}}`), `GA4 Measurement ID` (used on the base tag and `purchase`, confirmed `{{GA4 Measurement ID}}`).

**Present in the variable list, not individually re-verified this pass (21):** `transaction_id`, `currency`, `value`, `items`, `tax`, `shipping`, `coupon`, `item_list_id`, `item_list_name`, `payment_type`, `shipping_tier`, `cta_id`, `cta_placement`, `cta_destination_type`, `audience`, `funnel_stage`, `variant`, `error_reason`, `lead_offer`, `placement`, `signup_method` — all match real codebase parameter names exactly by name; not clicked into individually.

**Genuine gap found:** `bhp_book`, `bhp_format`, `bhp_source` — the three generic parameters that the `nav.js` mechanism attaches to *every* click/impression event (`amazon_outbound_click`, `related_content_click`, and the 6 gap-review events) — **have no corresponding Data Layer Variable in the 24-variable set at all.** This means even the tags that ARE already built for these events (`amazon_outbound_click`, `related_content_click`) likely aren't capturing which book/format/placement triggered them — only the bare event name. **This needs 3 new DLVs** (`DLV - bhp_book`, `DLV - bhp_format`, `DLV - bhp_source`) mapped into the existing `amazon_outbound_click`/`related_content_click` tags, independent of the six/seven-event gap decision.

**No naming conflicts, no incorrect nesting, no event-specific parameter mapped globally by mistake found** in what was checked.

## 5. Ecommerce Validation

- **Ecommerce object sent correctly**: confirmed on `purchase` and `add_to_cart` — both use "Send Ecommerce data" with Data source = Data Layer, matching the exact way `bundle-analytics.php`/`bundle-drawer.js` nest `currency`/`value`/`items`/`transaction_id`/`tax`/`shipping`/`coupon` into an `ecommerce` object (confirmed in the Phase 9 audit, §2.5).
- **`purchase` uses `transaction_id`**: yes, via the ecommerce auto-read (not a manually-mapped event parameter, which is correct — GA4's reserved `transaction_id` field lives inside the ecommerce object).
- **`purchase` includes value/currency/items/tax/shipping/coupon**: yes, all via the same ecommerce auto-read mechanism — confirmed present in the code's push shape, and the tag's "Send Ecommerce data" setting means GTM reads whatever the code pushes without a manual per-field mapping (correct approach, matches the blueprint recommendation).
- **Purchase deduplication**: enforced at the *code* level (confirmed in the Phase 9 audit — `event_id: purchase_<order_id>` deterministic pattern, prevents a page refresh from re-firing). GTM's GA4 Event tag doesn't itself deduplicate — it relies on GA4 server-side deduplication using the `event_id` parameter, which **is** correctly mapped on `purchase` (confirmed: `event_id` → `{{DLV - event_id}}`). This is the correct, standard mechanism.
- **Cart events don't reuse stale ecommerce data**: confirmed in code (Phase 9 audit §2.1) — every JS-side ecommerce push is preceded by an `{ecommerce: null}` clearing push specifically to prevent gtag's dataLayer merge behavior from bleeding stale items into the next event. This is a code-level safeguard, not a GTM-level one — GTM's tags don't need to (and don't) do anything extra to benefit from it.
- **Complete Collection vs. individual-book distinguishable**: **partially, as covered in Section 3** — technically possible via `items[]` inspection on `purchase`, but the purpose-built `bundle_type_purchased` event isn't yet wired into GTM.
- **Paperback vs. hardcover distinguishable**: yes — `items[].item_variant` carries the format, confirmed present in the item schema documented in the Phase 9 audit and referenced correctly wherever `items` is read from the Data Layer.
- No test purchases sent, nothing published.

## 6. GTM Base Tag

- `GA4 Measurement ID` variable correctly holds `G-7M42X19Z2T` (confirmed via the constant variable's referenced value showing correctly on the base tag).
- `TAG - GA4 - Google Tag` uses `{{GA4 Measurement ID}}` (variable reference, not hardcoded) — confirmed.
- Fires on `Initialization - All Pages` — correct, standard, ensures it loads before any event tag needs it.
- **No duplicate GA4 base tag** — confirmed only one `Google Tag` type tag exists in the 39-tag list.
- **No duplicate GTM loader in code** — confirmed in the Phase 9 audit: `inc/class-bhp-gtm-loader.php` is the single print location, verified by its own test suite (`tests/test-gtm-loader.php`).
- **No Google Analytics script bypasses GTM** — confirmed live on production this session (prior turn): the only Google script currently firing on production is `googletagmanager.com/gtag/js?id=AW-18315643536`, which is the **Google Listings & Ads (GLA) WooCommerce plugin's own Google Ads conversion tag** — architecturally separate from GTM/GA4 entirely, does not touch `window.dataLayer` in a GTM-relevant way, and is documented separately in the Phase 9 audit (§2 "Current GA4 State"). Not a bypass of GTM — a wholly different, pre-existing integration.

## 7. Consent Dependency

Unchanged from the Phase 9 audit, re-confirmed:
- **Default Consent Mode values**: all 4 signals (`analytics_storage`, `ad_storage`, `ad_user_data`, `ad_personalization`) default `denied`, correctly, printed by `BHP_Consent::render_default_snippet()` before the GTM loader tag.
- **Current loader gate**: `BHP_Analytics_Config::should_render_analytics()` — 4 checks, all must pass (not internal/admin traffic, tracking enabled for the environment, production consent-decision approved, per-visitor consent granted).
- **Can GTM load today?** No, on either environment — production has no container ID configured at all (`wp option get bhp_gtm_container_id` returns nothing); staging has the ID configured but `bhp_staging_analytics_override` is off.
- **Missing consent update mechanism**: confirmed — no `gtag('consent','update',...)` call exists anywhere in the codebase. Even if GTM were published, no real visitor could ever move off "denied" without this.
- **Missing banner**: confirmed — no consent/cookie banner UI exists anywhere in the codebase or on the live site (re-confirmed via live DOM/cookie/localStorage inspection in the Phase 9 audit).
- **Required production configuration**: set `bhp_gtm_container_id` and `bhp_ga4_measurement_id` options on production, get `bhp_consent_decision_approved` explicitly approved, publish the GTM container to Live.
- **What Andrew must approve**: the consent approach itself (banner build/adopt a CMP), then the `bhp_consent_decision_approved` flip — two separate decisions, the first almost certainly involving legal/privacy input given the children's-content-adjacent audience.
- **What must exist before publish**: a working consent banner + update mechanism, at minimum for EEA/UK/CH traffic to ever generate real analytics data once live.

## 8. Preview-Readiness Test Plan (for when Preview/DebugView work begins — not run this pass)

| Scenario | Expected event | Expected key params | Expected tag | Success criteria | Duplicate-firing failure condition |
|---|---|---|---|---|---|
| Homepage load | (GTM auto page view, once published) | — | — | Single hit, no repeats on scroll | Firing twice per load |
| Blog post view | (page view) | — | — | Single hit | — |
| Contextual book/CTA link click | `contextual_cta_click` | `cta_id`, `cta_placement`, `audience`, `funnel_stage` | `TAG - GA4 Event - contextual_cta_click` | Params populated, not blank | Firing on both the CTA's own click AND a generic outbound-link handler |
| Individual product page view | `view_item` | `currency`, `value`, `items[]` (1 item) | `TAG - GA4 Event - view_item` | `items[0].item_id` matches the real product | Firing once per page load, not once per re-render |
| Complete Collection page view | `bundle_page_view` | — | `TAG - GA4 Event - bundle_page_view` | Fires once | — |
| Add to cart (product page) | `add_to_cart` | `currency`, `value`, `items[]`, `bundle_type` | `TAG - GA4 Event - add_to_cart` | `items[]` non-empty, `value` matches price | Also firing `bundle_add_to_cart` unless genuinely a bundle-page add |
| Remove from cart | `remove_from_cart` | `currency`, `value`, `items: [item]` | `TAG - GA4 Event - remove_from_cart` | Correct single item removed | — |
| View cart (side-cart open) | `view_cart` | `currency`, `value`, `items[]` | `TAG - GA4 Event - view_cart` | Matches cart contents | Firing on every drawer re-render, not just open |
| Begin checkout | `begin_checkout` | `currency`, `value`, `items[]` | `TAG - GA4 Event - begin_checkout` | Fires once per checkout entry | — |
| Shipping selected | `add_shipping_info` | `event_id`, `shipping_tier`, `items[]` | `TAG - GA4 Event - add_shipping_info` | `event_id` unique per rate | Firing on every re-render of the shipping panel |
| Payment selected | `add_payment_info` | `event_id`, `payment_type`, `items[]` | `TAG - GA4 Event - add_payment_info` | `payment_type` matches real gateway ID | — |
| Purchase (test order, staging only) | `purchase` | `event_id: purchase_<order_id>`, `transaction_id`, `tax`, `shipping`, `coupon`, `items[]` | `TAG - GA4 Event - purchase` | Fires exactly once, values match order totals | **Critical**: firing again on a thank-you page refresh — this is the exact defect the dedup fix addresses; must be explicitly re-verified in Preview |
| Adventure Kit signup | `adventure_kit_signup` | `lead_offer`, `audience`, `placement`, `signup_method` | `TAG - GA4 Event - adventure_kit_signup` | Fires once, sessionStorage dedup holds on refresh | Firing again on thank-you page reload |
| Amazon outbound click | `amazon_outbound_click` | `bhp_book`, `bhp_format`, `bhp_source` | `TAG - GA4 Event - amazon_outbound_click` | **Currently params will be blank — see Section 4 gap** | — |
| Review-component clicks | not yet built | — | — | N/A until Section 3 decision is implemented | — |
| Pinterest/UTM traffic | GA4 default channel grouping (no custom event) | — | — | Session correctly attributed to Pinterest referrer | — |
| [PARENT_COUPON_CODE_SUPERSEDED] coupon purchase | `purchase` with `coupon: "[PARENT_COUPON_CODE_SUPERSEDED]"` | same as purchase + `coupon` populated | same | `coupon` field shows the code, discount reflected in `value` | — |

## 9. Final Recommendations

### 1. Executive Summary
The existing GTM build is substantially complete and, on the sample inspected, correctly constructed — matching the codebase's real event/parameter shapes, using GTM's native ecommerce auto-read (the recommended approach), and correctly wired end-to-end (variable → trigger → tag) with no duplicates. Nothing has been published; the live version is still empty. Two genuine gaps exist beyond what was already known: `bundle_type_purchased` (a 7th uncovered event, directly relevant to Collection-vs-individual reporting) and three missing generic variables (`bhp_book`/`bhp_format`/`bhp_source`) that silently limit the value of two *already-built* tags (`amazon_outbound_click`, `related_content_click`). Consent remains the actual blocker to publishing, unchanged from the Phase 9 audit.

### 2. Verified GTM Workspace Inventory
24 variables / 38 triggers / 39 tags / 103 pending changes / 0 published — all confirmed, unchanged by this session.

### 3. Variable-Quality Matrix
21 of 24 variables verified by naming-pattern cross-check against real code parameters (all match); 3 deep-verified directly (`event_id`, `bundle_type`, `GA4 Measurement ID` — all correct). 3 real parameters (`bhp_book`/`bhp_format`/`bhp_source`) have **no** variable at all — gap.

### 4. Trigger-Quality Matrix
38/38 correctly typed (Custom Event), 38/38 correctly named to match a real event, 1:1 with tags, no duplicates, no orphans. 1 correctly excluded (staging debug event). 7 events (6 known + `bundle_type_purchased`) have no trigger.

### 5. Tag-Quality Matrix
39/39 correctly typed and firing on the right trigger (list-level). 3/39 deep-verified for parameter correctness, all correct. Ecommerce tags use the recommended native auto-read approach.

### 6. Event-Coverage Matrix
See Section 2 — 38 fully covered, 1 intentionally excluded, 7 missing (6 known + 1 newly found), 0 obsolete, 0 additional undocumented.

### 7. Six (Seven)-Event Gap Recommendations
Agree with your calls on `bhp_direct_purchase_click`, `customer_review_product_click`, `customer_review_source_click` (include), impressions (optional), Kirkus (secondary). **Recommend adding `bundle_type_purchased` to the "include" tier** — it's the cleanest signal for a question your own dashboard design (Phase 9 audit §11) explicitly needs answered.

### 8. Ecommerce-Readiness Findings
Ecommerce plumbing is correctly built and code-side safeguards (dedup, stale-data clearing) are real and independently verified. The only ecommerce-adjacent gap is the missing `bundle_type_purchased` coverage.

### 9. Consent Blockers
Unchanged from the Phase 9 audit — no banner, no consent-update mechanism, both production gate options unset. This remains the actual critical path, independent of how complete the GTM build is.

### 10. Preview/DebugView Test Plan
See Section 8 — 16 scenarios defined, most importantly the purchase-dedup and Adventure-Kit-dedup re-verification (the exact defects previously fixed at the code level, worth confirming they hold under real GTM/GA4 delivery too).

### 11. Exact items that need correction
- Add 3 missing DLVs (`bhp_book`, `bhp_format`, `bhp_source`) and wire them into the 2 already-built tags that need them (`amazon_outbound_click`, `related_content_click`).
- Decide on and potentially add `bundle_type_purchased` (recommend: add).
- Decide on and potentially add the 6-event gap per your stated priorities.

### 12. Exact items that should remain unchanged
Everything else — the 24 variables, 38 triggers, and 39 tags already built are correctly constructed on the sample verified and should not be rebuilt or touched.

### 13. Readiness Score: **7/10** for the GTM build itself (up from the Phase 9 audit's 3/10 overall score, which was dragged down primarily by "nothing published" and "no consent" — both still true, but the build quality itself is now known to be strong, not just present).

### 14. Go/No-Go for Preview mode
**Go** — the existing build is safe to test in GTM Preview against staging (with `bhp_staging_analytics_override` turned on for a bounded session) without any further changes. This exercises real data flow without publishing anything.

### 15. Go/No-Go for Publishing
**No-Go** — unchanged reason from the Phase 9 audit: no consent banner or update mechanism exists, so publishing today would either collect nothing for a large share of visitors (correctly, by design) or require bypassing a safeguard the code deliberately won't allow without your explicit approval.

### 16. Confirmation no GTM or GA4 changes were made
Confirmed — every tag/variable panel opened during this session was closed via the X control, never Save. Workspace Changes verified at exactly 0 Modified / 103 Added / 0 Deleted both before and after this session.

### 17. Confirmation nothing was submitted or published
Confirmed — 0 Submitted, 0 Published throughout. Live Version 1 remains "Empty Container," unchanged.
