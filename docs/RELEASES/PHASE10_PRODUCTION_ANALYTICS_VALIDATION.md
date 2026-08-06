# Phase 10 — Production Analytics Validation (2026-07-13)

Autonomous validation pass requested by Andrew to confirm the production analytics/consent architecture works exactly as designed. Explicit scope: verify only, no redesign, no GTM rebuild, no event-architecture changes. This document is the canonical record of what was found; see `WORKLOG/2026-07-13.md` item 11 for the one-line timeline entry.

## Headline result

Two **independent** blockers stand between production and live analytics — the previously-known consent gate, and a **newly discovered plugin-deployment gap** that is more severe and was not previously documented anywhere in the knowledge base.

## 1. Consent gate (previously known, reconfirmed unchanged)

- `bhp_gtm_container_id` / `bhp_ga4_measurement_id` / `bhp_consent_decision_approved`: all unset/false on production (reconfirmed live via `wp option get`, all three return "does not exist").
- `BHP_GTM_Loader::render_head_snippet()` still prints 0 bytes; `gtmScriptCount: 0` confirmed in every consent scenario tested this pass.
- **This part of the architecture is working exactly as designed.** Not a defect.

## 2. NEW finding: `brave-hearts-bundle-pricing` plugin is stale on production — independent of today's consent deployment

Today's earlier consent/GTM-infrastructure deployment (`RELEASES/PRODUCTION_CONSENT_DEPLOYMENT.md`) was correctly scoped to 6 theme `inc/` files only. It never touched the `brave-hearts-bundle-pricing` plugin, which turns out to be running a **materially older version** than the repo:

| Signal | Production | Repo/staging |
|---|---|---|
| Plugin version header | **1.7.1** | **1.8.2** |
| `includes/bundle-analytics.php` | 107 lines, dated Jul 6 05:41 — pre-Phase-1B | 461 lines — full GA4 Enhanced Ecommerce rewrite |
| `assets/bundle-drawer.js` | dated Jul 6 05:41, no ecommerce nesting | current, ecommerce-nested (commits `fbe914c`/`9d5dc4f`/`5c497f3`) |
| `assets/bundle-landing.js` | dated Jul 6 05:41, checksum differs | current |
| `assets/bhp-list-tracking.js` (select_item click tracking) | **does not exist on production** | present |

### Concrete, live-verified consequences on production (this session, via direct `dataLayer` inspection — GTM Preview itself still blocked on Google auth, see §3)

| Event | Production behavior | Expected (per repo/GTM build) |
|---|---|---|
| `view_item_list` (shop archive, "Showing all 6 results") | **Never fires** — confirmed via full-page-HTML search, zero occurrences | Fires once per shop-loop/related-products render |
| `select_item` | **Never fires** — the JS file that implements it isn't deployed | Fires on tracked-list link click |
| `view_item` | **Never fires under that name** — production instead fires the old `product_viewed` (different name, flat non-ecommerce payload) | `view_item` with `currency`/`value`/`items[]` |
| `add_to_cart` | **Fires, correct event name**, but flat payload (`product_id`/`variation_id`/`quantity`/`source`) with **no `ecommerce` key at all** — live-verified via real Add to Cart click | Ecommerce-nested `items[]`/`currency`/`value` |
| `view_cart` | **Never fires** — confirmed empty `dataLayer` on `/cart/` page load | Fires on cart page view |
| `begin_checkout` | **Never fires under that name** — production's drawer still emits the old `checkout_started` | `begin_checkout` |
| `add_shipping_info` / `add_payment_info` | **Do not exist anywhere in production's codebase** (confirmed via full-directory `grep`, zero matches in active theme or plugin) | Fire on checkout page load / payment method selection |
| `purchase` | Production fires the old `purchase_completed` name with a flat, non-ecommerce payload (not live-tested — no real order placed, per instruction) | GA4-standard `purchase` with `transaction_id`/`items[]`/`tax`/`shipping` |
| `refund` | Does not exist in production's `bundle-analytics.php` at all | Fires on WooCommerce refund |
| `bundle_type_purchased` | Present in both old and new code with a compatible payload shape (`bundle_type`/`order_id`) — likely fires correctly, but not independently confirmed this pass (would require a real order) | Same |
| `contextual_cta_click` (Phase 1D CTA Engine) | **Fires, but data-impoverished**: live-tested on a real blog post — payload was `{bhp_book:"", bhp_format:"", bhp_source:""}` with **no `cta_id`/`cta_placement`/`cta_destination_type`/`audience`/`funnel_stage`/`variant`** at all, because production's `assets/js/nav.js` (deployed via today's unrelated header-fix theme ZIP) predates the CTA Engine's client-side payload-enrichment code. The CTA Engine's own PHP registry classes (`class-bhp-cta-engine.php`, `class-bhp-cta-collision-detector.php`) **are** present on production — this is a backend-present/frontend-stale split, not a total absence. | Full CTA attribution payload |
| `parent_popup_view` / `teacher_popup_view` (+ close/submit/success) | **Confirmed working correctly** — `assets/js/mariana-popup.js` checksum-matches the repo exactly; both funnels fire correct `source`/`page_path`/`page_type` | Same |
| `wpconsent_consent_processed`, Google Ads default-consent/config for the separate `AW-18315643536` pixel | **Confirmed working correctly** | Same |

### Root cause

The plugin has simply never been redeployed since a `Jul 6 05:41` release, while the repo received substantial GA4/CTA-engine work afterward (Phase 1B `view_item`/`purchase`/`view_item_list`/`select_item` rename + ecommerce nesting, Phase 1C/1D CTA Engine). Every deployment since then (staging QA, the header-layout-fix ZIP, today's consent-infrastructure deploy) was correctly scoped to *other* files and never included this plugin — so nothing "broke" it; it was simply never brought forward. This is a **deployment-lag gap**, structurally the same class of issue as the already-known "staging/production test-suite parity gap" in `KNOWN_ISSUES.md`, but far larger in scope and, until this session, undiscovered.

### Why prior sessions' "39 of 45 events covered" claims are still technically true but misleading

`GTM_STATUS.md`/`GA4_STATUS.md`/`EVENT_MATRIX.md` describe GTM's own trigger/tag *build* coverage against "the codebase" — verified true, and separately, `GA4_STATUS.md`'s 2026-07-13 Phase 7 addendum correctly validated these same events firing properly **on staging**, which does have the current plugin. No prior session diffed production's *actual live plugin files* against the repo for this specific plugin — every previous "production readiness" pass focused on the theme/consent infrastructure (correctly, since that was the explicit scope each time). This audit is the first to close that gap.

## 3. GTM Preview / GA4 DebugView — still blocked on Google authentication

Unchanged from every prior session: `tagassistant.google.com` shows "Sign in", no authenticated Google session available to this browser automation. Per Andrew's explicit instruction this session, this is a stop-and-wait item, not a blocker to the rest of the audit. **Andrew: sign in at `https://tagassistant.google.com` with the account that owns `GTM-N474PRSH`/`G-7M42X19Z2T`, then add `braveheartspublishing.com` as a debug domain** — the session will pick this up automatically once available. Not urgent: even once available, GTM Preview would only reconfirm the trigger/tag build (already verified) — it would not surface the plugin-staleness gap above, which is invisible from inside GTM's own debugger since it only sees whatever event names actually reach it.

## 4. Consent validation (Phase 4) — full pass, 7/7 scenarios, no defects

Fresh session (no cookie, fail-closed default) / Accept (all 4 signals granted) / Reject (all 4 denied) / return-visitor persistence after both Accept and Reject (banner correctly stays hidden) / change-preference via the floating reopen button (Reject → Accept flips correctly; confirmed the reopened "Manage Preferences" modal only exposes an "Accept All" action, matching the already-documented known limitation) / clear-storage-and-revisit (correctly resets to fresh state, banner reappears). No duplicate `wpconsent-container` instances found (exactly one `#wpconsent-root`/`#wpconsent-container` pair).

## 5. Funnel walk (Phase 5) — representative pass

Homepage, Books (marketing page — `view_item_list` correctly excluded by design, confirmed via code comment), Shop archive, a product page, Cart, Checkout, Teacher funnel page, and a blog post's contextual CTA were each checked via direct `dataLayer` inspection. Results folded into §2 above. No console errors, no failed network requests (all `200`s), no mixed content observed across every page visited this session.

## 6. Analytics readiness: **NO-GO** (unchanged conclusion, now for two independent reasons)

1. Consent not yet approved for production activation (known, by design).
2. **New:** even if consent were approved and GTM published today, the majority of GA4 ecommerce events would not reach GA4 correctly — either firing under the wrong event name, firing with an empty/non-ecommerce payload, or not firing at all — because production's `brave-hearts-bundle-pricing` plugin (v1.7.1) predates the event architecture GTM's triggers were built against (repo v1.8.2).

## 7. Recommended next action (not executed this session — scope was verification only)

A dedicated, explicitly-scoped deployment session should bring `brave-hearts-bundle-pricing` on production up to the current repo version (1.8.2), using the same snapshot/backup/checksum-verify method as today's consent deployment, followed by a fresh repeat of this exact funnel walk to confirm parity. This is a **plugin file deployment**, not a GTM or consent change, and needs its own staging-verified QA pass and Andrew's explicit approval before touching production, per standing production-safety rules. Until that happens, GTM must **not** be published/consent must **not** be approved, since doing so today would start sending materially incomplete/incorrect ecommerce data to GA4 the moment consent allows it.

## Verification method note

All event-firing checks in this document were performed via direct `window.dataLayer` inspection and server-side file/grep verification (SSH + WP-CLI), not GTM Preview or GA4 DebugView (both blocked on Google auth per §3). This is the same fallback method used successfully in the 2026-07-13 WPConsent closeout session, and is sufficient to establish event *presence/absence/shape* with certainty — it does not confirm GTM's own tag-firing behavior in Google's UI, which remains genuinely unverified pending Andrew's authentication.
