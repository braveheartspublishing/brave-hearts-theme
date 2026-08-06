# Consent & Privacy Decision Record (Phase 1B/1C)

This document exists to separate four things that are easy to blur
together: what the code already does, what Andrew still has to decide,
what needs an outside legal/privacy opinion, and the exact switches that
must be flipped before anything goes live on production. Nothing in this
document is legal advice, and nothing here should be read as a claim that
this site is currently compliant with any specific law or regulation —
that determination is explicitly listed under "external review" below,
not asserted here.

## 1. Technical implementation status (verified against the actual code, 2026-07-06)

| Behavior | Status | Where |
|---|---|---|
| Default `analytics_storage`/`ad_storage`/`ad_user_data`/`ad_personalization` = denied | **Implemented, tested** | `inc/class-bhp-consent.php`, `BHP_Consent::current_state()` |
| GTM does not load without both the visitor consent signal AND the production business gate | **Implemented, tested, live-verified** | `BHP_Analytics_Config::should_render_analytics()` |
| Production requires the explicit `bhp_consent_decision_approved` gate | **Implemented, tested, live-verified** | Same file; option currently unset (unapproved) on both environments |
| Staging requires the explicit, bounded `bhp_staging_analytics_override` | **Implemented, tested, live-verified** | Same file; currently off |
| Consent state represented correctly in the dataLayer | **Implemented, tested** | `BHP_Consent::render_default_snippet()` prints `gtag('consent','default',...)` before the GTM script tag |
| Revoking consent stops future analytics execution | **Implemented, tested** — a denied `analytics_storage` cookie blocks `BHP_GTM_Loader::render_head_snippet()` even with a real container ID and the production gate approved | `tests/test-analytics-phase1b.php` §7; live-verified 2026-07-06 |
| Admin/editor sessions excluded from the GTM container print and from UTM attribution capture | **Implemented, tested, live-verified** | `BHP_Analytics_Config::is_excluded_internal_request()` |
| Bots / server-side requests never create a browser-side event | **True by construction** — every event is a client-side `dataLayer.push()` triggered by a real DOM event, or a server-rendered `<script>` tag that only executes in an actual browser; there is no server-to-server event dispatch anywhere in this codebase | n/a |
| No email/name/address/phone/payment/message content in any analytics payload | **Verified by direct review of every `dataLayer.push()` call site**, 2026-07-06 (ecommerce events, popup events, lead-signup events, adventure_kit_signup) — see `docs/event-dictionary.md` and `docs/phase1c-lead-capture-architecture.md` for the full parameter list of each event | n/a |
| WooCommerce checkout values sent to analytics are limited to approved commerce parameters | **Verified** — `add_shipping_info`/`add_payment_info`/`purchase` only ever send `currency`, `value`, `items[]` (id/name/brand/category/price/qty), `shipping_tier`, `payment_type` (gateway ID, never card data), `transaction_id`, `tax`, `shipping`, `coupon` | `docs/event-dictionary.md` |
| Debug panel not exposed on production | **Implemented, tested, live-verified** — `BHP_Analytics_Debug::debug_mode_available()` requires staging host AND a logged-in administrator; the panel echoes real dataLayer content, so its safety depends entirely on the "no PII in dataLayer" guarantee above, which is independently verified | `inc/class-bhp-analytics-debug.php` |
| Lead-signup email is stored only in an internal, capability-gated admin log — never in analytics | **Implemented, tested** — `BHP_Lead_Event_Log` stores email in `postmeta` on a private CPT, visible only behind `manage_options`; the analytics events fired alongside it (`signup_error`, `lead_signup_success`, `adventure_kit_signup`) never include it | `inc/class-bhp-lead-event-log.php` |

## 2. Business decisions Andrew still has to make (not technical, not legal — just his call)

- **Whether a consent-management banner/UI is required at all** for this site's current traffic mix and jurisdictions, or whether the existing default-denied posture plus this decision record is sufficient for launch. Either answer is a legitimate business decision; the code supports both (see §4).
- **When to approve the production gate** (`bhp_consent_decision_approved`). This is deliberately a manual, one-time flip — nothing in this codebase will do it automatically, and nothing should.
- **Whether to adopt a real CMP (consent management platform)** later. The integration point is already documented (`inc/class-bhp-consent.php`'s "FUTURE CMP INTEGRATION POINT" docblock) — any future CMP only needs to set the `bhp_consent_state` cookie; no other code changes required.
- **How long to keep lead-event log entries.** No automatic retention/deletion policy exists yet for `bhp_lead_event` posts — this is a business/legal call, not something this session should decide silently.

## 3. Items that need an outside legal/privacy opinion (not answered here)

- Whether this site's current data flows (Mailchimp for email marketing, GA4/GTM for analytics once configured, WooCommerce/Stripe for payment) require a published privacy policy update beyond what already exists.
- Whether GDPR/CCPA/COPPA-adjacent considerations apply given the audience (parents of children ages 5–9; the site does not knowingly collect data directly from children — all forms collect only an adult's email/first name).
- Whether a cookie-consent banner is legally required for the specific analytics/advertising configuration eventually deployed (this depends on real traffic geography, which this session has no visibility into).
- Data processing agreements with Mailchimp/Google, if not already in place.

**This document does not answer any of the above. Do not treat the technical implementation in §1 as a substitute for this review.**

## 4. Exact production activation prerequisites (in order)

1. Andrew resolves §2's business decisions (with §3's legal input if he chooses to seek it).
2. Real GA4 property + GTM container fully configured per `docs/gtm-configuration-blueprint.md` and validated in GTM Preview / GA4 DebugView (Andrew's own Google account — see that doc for why this session could not do it).
3. `wp option update bhp_consent_decision_approved 1` — the one explicit, manual, no-going-back-silently switch.
4. GTM container published to its Live environment.
5. Confirm on production immediately after: `wp option get bhp_gtm_container_id` matches the published container, and a real (non-admin) page load shows the GTM script actually present.

No step above has been performed by this session. Both `bhp_gtm_container_id` and `bhp_consent_decision_approved` remain unset on production as of 2026-07-06.
