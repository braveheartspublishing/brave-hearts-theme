# GTM/GA4 Production Readiness Audit — 2026-07-12

**Read-only audit. Nothing was implemented, published, or modified on production, staging, GTM, or GA4.**

**Methodology note:** GTM/GA4 console access was attempted and blocked — no authenticated Google session was available in this session's browser (login wall at `accounts.google.com`), so this audit does not enter credentials. Live GTM workspace/publish state below is taken from `docs/gtm-staging-build-2026-07-09.md`, the most recent real, credentialed session on record — flagged as "as of 2026-07-09," not re-verified live this turn. Everything else (WordPress option values, live dataLayer contents, live script tags, codebase contents) **was** independently re-verified live this session via WP-CLI and a real logged-out browser session on both environments.

---

## 1. Current GTM State

| Field | Value | Source |
|---|---|---|
| Container ID | `GTM-N474PRSH` | Real, confirmed in Andrew's account (`accountId 6364597357`, `containerId 257525520`), per 2026-07-09 session |
| Published? | **No.** 0 Modified / 4 Added / 0 Deleted pending changes at end of last session; workspace never Submitted or Published | `gtm-staging-build-2026-07-09.md` |
| Workspace state | Workspace 2, mid-build, session stopped deliberately due to browser-automation reliability risk against a live (non-sandbox) account | same |
| Variables completed | 4 of ~24: 1 constant (`GA4 Measurement ID` = `G-7M42X19Z2T`) + 3 Data Layer Variables (`event_id`, `transaction_id`, `currency`) | same |
| Variables remaining | ~20 more DLVs per the blueprint (`item_list_id`, `item_list_name`, `shipping_tier`, `payment_type`, `bhp_book`, `bhp_format`, `bhp_source`, `cta_id`, `lead_offer`, etc.) | `gtm-configuration-blueprint.md` |
| Triggers completed | 0 of 23 planned custom-event triggers | `gtm-staging-build-2026-07-09.md` |
| Tags completed | 0 — the base Google tag itself has not been created yet, nor any GA4 event tags | same |
| Tags remaining | 1 base Google tag + 11 GA4-standard ecommerce event tags + tags for the business-custom/Phase 1D events (CTA, popup, lead-form events) | `gtm-configuration-blueprint.md` |

**Every unfinished item:** effectively the entire container build beyond the 4 items above — no triggers, no tags, no environments configured, no version ever submitted, container never published to any environment (Live or otherwise).

## 2. Current GA4 State

| Field | Value |
|---|---|
| Measurement ID | `G-7M42X19Z2T` — confirmed real, saved as a GTM constant variable |
| Current configuration / Enhanced Measurement / Custom Dimensions / Custom Metrics / Existing Events / Existing Conversions | **Not verifiable this session — no GA4 console access.** Not independently confirmed by prior sessions either; no doc in this repo reports having opened the GA4 property UI. |
| Does anything currently reach GA4? | **Confirmed live, this session: No.** |

Live verification (real browser, this session):
- **Production** (`braveheartspublishing.com`): the only Google script loading is `googletagmanager.com/gtag/js?id=AW-18315643536` — a **Google Ads conversion tag**, injected by the **Google Listings & Ads (GLA) WooCommerce plugin**, completely unrelated to the GTM-N474PRSH/G-7M42X19Z2T infrastructure. Confirmed via `wp option get`: `bhp_gtm_container_id`, `bhp_ga4_measurement_id`, and `bhp_consent_decision_approved` are all **unset** on production.
- **Staging** (`staging2.braveheartspublishing.com`): zero Google scripts load at all, `window.dataLayer` is an empty array. Confirmed via `wp option get`: `bhp_gtm_container_id` = `GTM-N474PRSH` and `bhp_ga4_measurement_id` = `G-7M42X19Z2T` **are** saved as options, but `bhp_staging_analytics_override` is unset (false), so the code's own gate (`BHP_Analytics_Config::is_tracking_enabled()`) suppresses the loader anyway.

**Root cause, precisely identified:** the site-side loader code (`inc/class-bhp-gtm-loader.php`, `inc/class-bhp-analytics-config.php`) is fully built, tested, and correctly fails closed — but is currently inert everywhere because (a) production has no container ID configured at all, (b) production's consent-decision-approval gate is unapproved, and (c) staging's bounded-QA override toggle is off. This is a **configuration/activation gap, not a code gap.**

## 3. Existing Data Layer

**39 distinct event names found live in the codebase**, all confirmed via direct grep/read of `functions.php`, `assets/js/nav.js`, `assets/js/mariana-popup.js`, `inc/class-bhp-consent.php`, `inc/class-bhp-cta-engine.php`, `inc/class-bhp-campaign-landing.php`, `template-parts/**`, and `plugins/brave-hearts-bundle-pricing/**`.

### 3.1 GA4-standard ecommerce events (11)
| Event | Fires when | Key params | Gaps |
|---|---|---|---|
| `view_item_list` | Shop loop render, related-products render | `item_list_id`, `item_list_name`, `items[]` | None found |
| `select_item` | Click inside a tracked product list | `item_list_id`, `item_list_name`, `items[]` | None found |
| `view_item` | Single product page render | `currency`, `value`, `items[]` | None found |
| `add_to_cart` | 4 separate call sites (product page, side-cart cross-sell, bundle page smart/non-smart) | `source`, `currency`, `value`, `items[]` | Not a duplicate defect — 4 legitimate distinct entry points, each correctly labeled by `source` |
| `remove_from_cart` | Side-cart item removal | `currency`, `value`, `items: [item]` | None found |
| `view_cart` | Side-cart open | `currency`, `value`, `items[]` | None found |
| `begin_checkout` | Side-cart checkout initiation | `source`, `currency`, `value`, `items[]` | Only one entry point (side-cart) — no `begin_checkout` fires from a direct-to-checkout path if one exists |
| `add_shipping_info` | Store-API shipping-rate selection detected | `event_id`, `shipping_tier`, `items[]` | None found |
| `add_payment_info` | DOM payment-radio selection detected | `event_id`, `payment_type`, `items[]` | None found |
| `purchase` | `woocommerce_thankyou`, deduplicated | `event_id: purchase_<order_id>`, `transaction_id`, `tax`, `shipping`, `coupon`, `items[]` | Dedup confirmed fixed (was flagged HIGH risk in the original 2026-07-05 inventory) |
| `refund` | `woocommerce_order_refunded` | `event_id: refund_<refund_id>`, `transaction_id`, `currency`, `value` | None found |

### 3.2 Business-custom events (28)
Popup funnel (`teacher_popup_*` / `parent_popup_*` — view/close/submit/success, 8 total), CTA engine (`contextual_cta_click`/`contextual_cta_view`), lead funnel (`lead_form_view`, `lead_form_start`, `lead_signup_success`, `signup_error`, `adventure_kit_signup`), campaign landing (`landing_page_view`, `landing_page_cta_click`), bundle-specific (`bundle_page_view`, `bundle_format_selected`, `format_selected`, `bundle_add_to_cart`, `bundle_savings_applied`, `bundle_type_purchased`, `second_book_added`, `complete_set_reached`, `side_cart_opened`, `side_cart_cross_sell_clicked`), outbound (`amazon_outbound_click`, `bhp_direct_purchase_click`, `related_content_click`), and a staging-only debug event (`bhp_debug_internal_order_purchase_suppressed`).

### 3.3 Missing parameters
- **`bhp_format` is always the literal empty string** on `bhp_direct_purchase_click` (`assets/js/nav.js:79`) — never populated. Known, previously flagged gap, still present.
- No dataLayer event carries `utm_source`/`utm_medium`/`utm_campaign`/`gclid`/`fbclid` etc. — these exist **only as WooCommerce order-meta** (`inc/class-bhp-utm-attribution.php`), never pushed to `dataLayer`. This means session-level attribution (which channel drove *this specific pageview/event*) is not currently possible from GTM/GA4's perspective — only order-level, after purchase.
- No `environment` key attached dataLayer-wide (`event-dictionary.md` notes this as "not yet attached to every event").

### 3.4 Naming convention consistency
**Not fully consistent.** Three overlapping conventions coexist:
1. GA4-standard snake_case reserved names (`view_item`, `add_to_cart`, `purchase`) — correct, matches Google's schema.
2. `bhp_`-prefixed custom events (`bhp_direct_purchase_click`, `bhp_debug_internal_order_purchase_suppressed`) — namespaced to avoid collision.
3. Unprefixed business-custom names with no consistent namespace (`second_book_added`, `complete_set_reached`, `format_selected`, `side_cart_opened`, `amazon_outbound_click`, `kirkus_review_link_click`) — these could collide with a future GA4-reserved name or another plugin's event, since they carry no `bhp_` or product namespace.

### 3.5 Duplicates
No true duplicate event definitions found (same event, same trigger, fired twice). The 4 `add_to_cart` call sites are legitimate distinct sources, each labeled via `source`.

### 3.6 Obsolete events
`docs/analytics-event-inventory.md` documents an **earlier vocabulary** (`product_viewed`, `purchase_completed`, `checkout_started`) that has been fully superseded by the current GA4-standard names — this vocabulary does **not** exist in current code, only in a superseded doc kept for history. No cleanup needed in code; the doc itself is correctly marked superseded.

### 3.7 Undocumented events (real code, missing from `event-dictionary.md`/`gtm-implementation-manifest.json`)
`kirkus_review_link_click`, `kirkus_component_impression`, `customer_review_impression`, `customer_review_source_click`, `customer_review_product_click`, `bhp_direct_purchase_click`. All use the same safe, no-PII generic mechanism as documented events — this is a documentation-completeness gap, not a safety issue, but it means the GTM trigger-build spec (§4 of the blueprint) is **missing 6 real events** it should also build triggers for.

## 4. Business Event Coverage

| Business Action | Tracked? | Missing? | Priority |
|---|---|---|---|
| Homepage visit | Via GTM's automatic `gtm.js`/page_view (once published) | Not yet reaching GA4 (container inert) | High |
| Blog visit | Same as above | Same | High |
| Category visit | Same as above | Same | Medium |
| Individual product visit | `view_item` ✅ (code-complete) | Not yet reaching GA4 | High |
| Complete Collection visit | `bundle_page_view` ✅ | Not yet reaching GA4 | High |
| Teacher page visit | Page-level only (no dedicated event) | No teacher-page-specific event | Low |
| Adventure Kit page | Page-level only | No dedicated event | Low |
| Adventure Kit signup | `adventure_kit_signup` ✅ | Not yet reaching GA4 | High |
| Adventure Kit thank-you | Covered by the signup event on that page | — | — |
| Mailchimp signup | `lead_signup_success` ✅ (form-based signups only) | Mailchimp-side automation-triggered signups (if any exist outside this form) not tracked | Medium |
| Coupon email click | ❌ Not tracked | No dataLayer event fires from an email click-through — only detectable indirectly via UTM order-meta after purchase | Medium |
| Collection page click | `landing_page_cta_click` ✅ for campaign pages; general Collection page CTAs via `bhp_source` values | Partial | Medium |
| Product CTA click | `contextual_cta_click` ✅ | Not yet reaching GA4 | High |
| CTA Engine click | `contextual_cta_click` / `contextual_cta_view` ✅ | Not yet reaching GA4 | High |
| Amazon click | `amazon_outbound_click` ✅ | Not yet reaching GA4 | Medium |
| Pinterest visit | ❌ Not tracked as inbound source distinctly (would rely on GA4's own referrer/UTM detection once live) | No custom handling | Low (GA4 default handles this once live) |
| Organic Google visit | Same — relies on GA4 default channel grouping once live | — | Low |
| Social visit | Same | — | Low |
| Add to cart | `add_to_cart` ✅ (4 sources) | Not yet reaching GA4 | High |
| Remove from cart | `remove_from_cart` ✅ | Not yet reaching GA4 | High |
| View cart | `view_cart` ✅ | Not yet reaching GA4 | High |
| Begin checkout | `begin_checkout` ✅ | Not yet reaching GA4 | High |
| Shipping selected | `add_shipping_info` ✅ | Not yet reaching GA4 | High |
| Payment selected | `add_payment_info` ✅ | Not yet reaching GA4 | High |
| Coupon applied | ❌ Not a distinct dataLayer event | Only visible after the fact in `purchase.coupon` | Medium |
| Coupon rejected | ❌ Not tracked | No event | Low |
| Coupon removed | ❌ Not tracked | No event | Low |
| Purchase completed | `purchase` ✅ (deduplicated) | Not yet reaching GA4 | **Critical** |
| Revenue | `purchase.value` ✅ | Not yet reaching GA4 | **Critical** |
| Tax | `purchase.tax` ✅ | Not yet reaching GA4 | High |
| Shipping | `purchase.shipping` ✅ | Not yet reaching GA4 | High |
| Book purchased (which book) | `purchase.items[].item_name`/`item_id` ✅ | Not yet reaching GA4 | High |
| Paperback / Hardcover | `items[].item_variant` (format) — confirmed present in the item schema | Not yet reaching GA4 | High |
| Collection purchased | `bundle_type_purchased` ✅ | Not yet reaching GA4 | High |
| Individual book purchased | Distinguishable via `items[]` composition of `purchase` | Not yet reaching GA4 | High |

**Bottom line for this section: code-level tracking coverage is strong (roughly 30 of 38 listed actions have a real, tested dataLayer event). The gap is not "what to build" — it's that literally none of it reaches GA4 yet, plus a handful of real gaps (coupon lifecycle, email click-through, teacher/Adventure-Kit page-level events).**

## 5. Attribution

| Question | Currently answerable? | Why |
|---|---|---|
| Which blog generates sales? | No | GA4 not receiving data; no content-group/page-path-to-purchase linkage configured |
| Which Pinterest pin generates sales? | No | No pin-level UTM capture into dataLayer; GA4 not receiving data |
| Which CTA converts best? | No | `contextual_cta_click`/`view` exist in code but don't reach GA4; no conversion-to-purchase join designed yet |
| Which product converts best? | No | `view_item`→`purchase` funnel exists in code but GA4 isn't receiving either event |
| Which book is most viewed? | No | Same — `view_item` not reaching GA4 |
| Which landing page converts? | No | `landing_page_view`/`landing_page_cta_click` exist but don't reach GA4 |
| Which traffic source buys? | No | UTMs only live in order-meta post-purchase, not in dataLayer — GA4 can't join session source to on-site events even once live, only to the eventual order via a separate reconciliation step |
| Which email converts? | No | No email-click-through event exists at all (§4) |
| Which coupon converts? | Partially, post-purchase only | `purchase.coupon` exists, but no application/rejection event, and GA4 isn't receiving purchase events anyway |
| Which customer journey produces highest revenue? | No | Requires GA4 live + session-level UTM/CTA join, neither of which exists yet |

**None of these 10 questions can be answered today.** All require, at minimum, GTM published + GA4 actually receiving events — several also require net-new events (email click-through, coupon lifecycle) or dataLayer-level UTM capture that doesn't exist yet.

## 6. Consent Mode

| Item | State |
|---|---|
| Consent Mode | v2-shaped default snippet exists and fires correctly (`BHP_Consent::render_default_snippet()`), but **no `gtag('consent','update',...)` call exists anywhere in the codebase** — there is no mechanism to ever change consent state, because... |
| Cookie banner | **Does not exist.** No banner UI anywhere in the codebase. Confirmed live this session: no consent-related DOM elements, cookies, or localStorage keys found on the real production homepage. |
| Consent defaults | All 4 signals (`analytics_storage`, `ad_storage`, `ad_user_data`, `ad_personalization`) default to `denied` sitewide, correctly, per Google's required default-deny pattern for EEA/UK/CH (region list hardcoded in the default snippet) |
| Consent update | **Missing** — with no banner, no real visitor can ever change from the default-denied state, meaning even once GTM is published, analytics_storage will be permanently denied for every real visitor (the code's own gate would keep blocking GTM from ever loading for anyone) |
| Google requirements | Google requires Consent Mode v2 defaults for any site serving EEA/UK/CH traffic using Google Ads/Analytics. The default-snippet exists and is compliant *as a default*, but a site with **zero mechanism to ever grant consent** cannot actually run analytics for those regions at all — this is not just incomplete, it's a structural block until a banner exists |
| Legally/technically incomplete | **Both.** Technically: no consent-update path exists. Legally: no legal/privacy review has been performed for GDPR/CCPA/COPPA-adjacency (this audience skews toward children's content, which raises COPPA-adjacent considerations beyond generic e-commerce) — `docs/consent-privacy-decision-record.md` explicitly flags this as needing outside counsel, not resolved internally. |

## 7. Event Naming — Canonical Convention (Recommendation)

Recommend a strict two-tier convention going forward:
1. **GA4-reserved ecommerce events**: use Google's exact reserved names verbatim (`view_item`, `add_to_cart`, `purchase`, etc.) — never modify or prefix these, since GTM's GA4 event tag template auto-maps them.
2. **Everything else**: `bhp_<noun>_<verb>` snake_case, always prefixed. This resolves the current inconsistency (§3.4) where roughly a third of business-custom events have no namespace.

**Recommend renaming** (repo-side code change, not a GTM-side fix — flagged for the separate implementation project): `second_book_added` → `bhp_bundle_second_book_added`; `complete_set_reached` → `bhp_bundle_set_completed`; `format_selected` → `bhp_variation_format_selected`; `side_cart_opened` → `bhp_side_cart_opened`; `amazon_outbound_click` → `bhp_amazon_outbound_click`; `kirkus_review_link_click`/`kirkus_component_impression` → `bhp_kirkus_link_click`/`bhp_kirkus_impression`; `customer_review_*` → `bhp_review_*`.

**Recommend consolidating**: the 4-suffix popup pattern (`teacher_popup_view/close/submit/success`, `parent_popup_view/close/submit/success`) into a single `bhp_popup_interaction` event with a `funnel` and `action` parameter pair — reduces 8 GTM triggers to 1 trigger + parameter-based tags, easier to maintain as more popups are added.

**Recommend adding** (net-new, not yet built): `bhp_email_click` (coupon/campaign email click-through, needs a UTM-tagged redirect or pixel), `bhp_coupon_applied`/`bhp_coupon_rejected`/`bhp_coupon_removed`, `bhp_teacher_page_view`/`bhp_adventure_kit_page_view` (page-level, if funnel-specific reporting is wanted beyond generic pageviews).

## 8. Custom Dimensions (Recommendation Only — Not Implemented)

| Dimension | Source parameter | Scope |
|---|---|---|
| Book | `items[].item_id` / `bhp_book` | Event |
| Format | `items[].item_variant` / `bhp_format` | Event |
| CTA ID | `cta_id` | Event |
| Collection | `bundle_type` | Event |
| Source (internal placement) | `bhp_source` / `source` | Event |
| Campaign | order-meta UTM (needs dataLayer promotion first) | Event/User |
| Content Group | none yet — needs a new `content_group` parameter mapped from post category/taxonomy | Event |
| Audience | `audience` | Event |
| Traffic Source | GA4 default channel grouping (once live) — no custom dimension needed unless finer granularity wanted | — |
| Coupon | `purchase.coupon` | Event |
| Landing Page | `landing_page_view`'s implicit page_path, or a dedicated `campaign_id` | Event |
| Blog Category | none yet — needs promotion from WP taxonomy into dataLayer | Event |
| Reader Stage | `funnel_stage` | Event |
| Parent Journey / Teacher Journey | `funnel` (currently only set on `adventure_kit_signup`) — would need to be set consistently across the popup/lead-form events too | Event |

## 9. Custom Conversions (Recommendation Only)

Adventure Kit Signup (`adventure_kit_signup`), Begin Checkout (`begin_checkout`), Purchase (`purchase`), Collection Purchase (`bundle_type_purchased`, filtered), Individual Purchase (`purchase`, filtered by `items[].item_variant` composition), Teacher Signup (`teacher_popup_success`), Amazon Outbound (`amazon_outbound_click`), Pinterest Outbound (does not exist yet — would need a dedicated outbound-link handler if Pinterest links are ever tracked as outbound clicks rather than just inbound referrer).

## 10. Funnel Reporting (Design Only)

Organic Blog Funnel (page_view on blog → `contextual_cta_click` → `view_item`/`bundle_page_view` → `purchase`), Pinterest Funnel (inbound session source = Pinterest → any on-site event → `purchase`, requires GA4 default channel grouping only, no custom event needed), Collection Funnel (`bundle_page_view` → `add_to_cart` [source=bundle_page] → `begin_checkout` → `purchase` [bundle_type_purchased]), Adventure Kit Funnel (`lead_form_view` → `lead_form_start` → `lead_signup_success`/`adventure_kit_signup`), Teacher Funnel (`teacher_popup_view` → `teacher_popup_submit` → `teacher_popup_success`), Amazon Funnel (`amazon_outbound_click` — one-way, no on-site purchase completion visible from GA4's side since the sale happens on Amazon), Book Funnel (`view_item` → `add_to_cart` → `purchase`, filterable per book via `items[].item_id`), Email Funnel (needs the net-new `bhp_email_click` event described in §7 before this is buildable at all), Coupon Funnel (needs the net-new coupon lifecycle events from §7).

## 11. Executive Dashboard (Design Only)

Sessions, Users, Revenue, Conversion Rate, Average Order Value — all standard GA4 metrics, available once live. Collection Sales vs. Individual Book Sales — split via `bundle_type_purchased` vs. filtered `purchase`. Coupon Usage — from `purchase.coupon`, needs the coupon-lifecycle events for a full picture (applied vs. rejected vs. used). Adventure Kit Opt-ins — `adventure_kit_signup` count. Top Landing Pages / Top Blogs — GA4 default page-path reporting, enriched by the `content_group` custom dimension recommended in §8. Top CTAs — `contextual_cta_click` grouped by `cta_id`. Top Traffic Sources — GA4 default channel grouping. Top Products — `purchase.items[]` aggregation.

## 12. Technical Risks

- **Duplicate events**: none found currently, but the unnamespaced business-custom events (§3.4) risk future collision with a plugin or a future GA4-reserved name.
- **Missing events**: coupon lifecycle, email click-through, teacher/Adventure-Kit page-level events (§4).
- **Incorrect triggers**: cannot assess — no triggers exist yet in GTM.
- **Broken attribution**: UTMs never reach dataLayer (§3.3) — this alone breaks session-to-event attribution even after GTM is published; must be fixed in code (a new UTM-to-dataLayer bridge), not just in GTM.
- **Future scaling issues**: the generic `nav.js` `data-bhp-event`/`data-bhp-impression-event`/`data-bhp-focus-event` mechanism is a genuinely good, reusable pattern — low risk here. The bigger scaling risk is the growing undocumented-event gap (§3.7) — without a habit of updating the manifest/dictionary alongside code, this will keep drifting.
- **Consent issues**: structural block (§6) — no consent-update mechanism exists, meaning EEA/UK/CH traffic can never generate analytics data even after publish, until a real banner is built.
- **Performance concerns**: none identified — the loader is a single, minimal, well-tested class; no evidence of duplicate/competing tag-manager installs.
- **Naming inconsistencies**: documented in §3.4/§7.

## 13. Implementation Roadmap (Isolated, Independently Deployable Releases)

**Release 1 — Foundation**: Finish the GTM container build (remaining ~20 variables, base Google tag). No code changes. Deployable/testable entirely inside GTM's own workspace; publishing not required yet.

**Release 2 — Core ecommerce**: Build the 23 custom-event triggers + 11 GA4 ecommerce event tags in GTM, validated via GTM Preview against staging (requires `bhp_staging_analytics_override` turned on for a bounded session, then off). No code changes needed — all 11 events are already code-complete.

**Release 3 — CTA/business-event tracking**: Tags/triggers for the Phase 1D and popup/lead events (`contextual_cta_*`, `teacher_popup_*`, `parent_popup_*`, `lead_form_*`, `adventure_kit_signup`). Also close the §3.7 documentation gap for the 6 undocumented events. No code changes.

**Release 4 — Gap-filling code work** (this is the one release that touches the repo): UTM-to-dataLayer bridge, coupon lifecycle events, email click-through event, event-naming cleanup from §7, `bhp_format` fix on `bhp_direct_purchase_click`. Independently deployable/testable on staging before any GTM work depends on it.

**Release 5 — Consent**: The real blocker — build or adopt a consent banner, get Andrew's explicit consent-decision approval, wire `bhp_consent_decision_approved`. This can and should happen in parallel with Releases 1-4, not after — it's on a completely different critical path (legal/business decision, not engineering).

**Release 6 — Reporting**: Custom dimensions (§8), conversions (§9), funnel explorations (§10), the executive dashboard (§11) — all built inside GA4's UI once real data is flowing.

**Release 7 — QA**: Full GTM Preview + GA4 DebugView validation pass on staging (the exact checklist already exists in `docs/gtm-configuration-blueprint.md` §12 and was never executed) — real purchase-event validation, UTM capture validation for a genuinely anonymous visitor (never done — `analytics-validation.md` flags this explicitly).

**Release 8 — Production publish**: Only after Release 5 (consent) is genuinely resolved. Approve `bhp_consent_decision_approved`, publish the GTM container to Live, set `bhp_gtm_container_id`/`bhp_ga4_measurement_id` on production, confirm via a live, logged-out smoke test.

## 14. Deliverables

### Executive Summary
The engineering foundation is genuinely strong: 39 well-structured dataLayer events, a correctly fail-closed loader architecture, real GTM/GA4 credentials already provisioned, and roughly 80% of the business-critical ecommerce funnel already code-complete and tested. **None of it currently reaches GA4 on either environment.** The GTM container is ~15% built and never published. Production has zero analytics configuration at all (only an unrelated Google Ads pixel from a WooCommerce plugin fires). The single largest blocker is not technical — it's that **no consent banner exists**, which means even a fully-published GTM container cannot legally collect EEA/UK/CH analytics data under the current default-deny Consent Mode setup, since there is no mechanism for a visitor to ever grant consent.

### Readiness Score: **3/10**
Strong code foundation (would be 7-8/10 on code alone) offset heavily by: nothing live anywhere, GTM ~15% built, no consent mechanism at all, and no attribution bridge (UTMs never reach dataLayer).

### Everything complete
Ecommerce dataLayer events (11/11 GA4-standard), most business-custom events (28), the GTM loader/consent-default architecture, defensive option validation, staging/production environment separation, purchase deduplication, no-PII payload design, automated test coverage for the loader and consent classes, real GTM container + GA4 property provisioned with real credentials.

### Everything incomplete
GTM triggers (0/23), GTM tags (0/12+), GTM publish (never), consent banner (doesn't exist), consent-update mechanism (doesn't exist), UTM-to-dataLayer bridge (doesn't exist), coupon lifecycle events (don't exist), email click-through tracking (doesn't exist), production GTM/GA4 option configuration (unset), production consent-decision approval (unset), GTM Preview validation (never performed), GA4 DebugView validation (never performed), a real end-to-end purchase-event validation (never performed), custom dimensions/conversions/dashboards in GA4 (none built — property configuration itself unverified).

### Every required GTM variable
1 constant (done) + ~23 Data Layer Variables per `gtm-configuration-blueprint.md` §1 (4 done, ~19 remaining) — full list in that doc.

### Every required trigger
23 custom-event triggers per blueprint §4, **plus 6 more** for the undocumented events found in §3.7 of this audit (29 total).

### Every required tag
1 base Google tag, 11 GA4 ecommerce event tags, ~18 business-custom event tags (one per remaining trigger, some may share a tag with parameter-based differentiation per the §7 consolidation recommendation).

### Every required GA4 event
The 11 GA4-reserved ecommerce events (already code-complete) + all business-custom events as GA4 custom events.

### Every required conversion
Listed in §9 (8 conversions).

### Every required custom dimension
Listed in §8 (14 dimensions).

### Every required dashboard
One executive dashboard (§11) — a single, sufficient starting point; do not over-build additional dashboards before this one is validated with real data.

### Every required report
The 9 funnel explorations in §10.

### Every technical blocker
1. GTM container not built/published (Release 1-3). 2. UTM-to-dataLayer bridge missing (Release 4). 3. Coupon/email-click events missing (Release 4). 4. No live validation ever performed (Release 7).

### Every business blocker
1. **No consent banner or consent-update mechanism — the single largest blocker, structural not incidental.** 2. `bhp_consent_decision_approved` requires Andrew's explicit decision. 3. No legal/privacy review performed for a children's-content-adjacent audience (COPPA-adjacent considerations).

### Estimated implementation effort
Release 1-3 (GTM build): 1-2 focused sessions, mostly mechanical, GTM-side only. Release 4 (code gap-filling): 1 session. Release 5 (consent): depends entirely on Andrew's decision + banner build/adoption — could be fast (adopt a CMP plugin) or slow (custom build + legal review). Release 6 (reporting): 1 session once data is flowing. Release 7 (QA): 1 focused session, needs real credentialed access. Release 8 (publish): under an hour once everything above is done.

### Recommended implementation order
Consent (5) should start in parallel with GTM build (1-3) immediately, since it's the longest lead-time item and blocks final publish regardless of how fast the technical work goes. Sequence: **5 (start) + 1 → 2 → 3 → 4 → 7 → 5 (finish) → 8 → 6.**

### Go/No-Go Recommendation
**No-Go for production analytics today.** Not because the engineering is weak — it's genuinely solid — but because publishing GTM to production right now would either (a) collect nothing for a large share of visitors due to the consent gate correctly blocking it, or (b) require bypassing that gate, which the code deliberately refuses to allow without Andrew's explicit approval. **Recommend starting the consent decision conversation with Andrew immediately** (it's the true critical path), while treating the GTM container build as parallel, lower-risk engineering work that can proceed independently.
