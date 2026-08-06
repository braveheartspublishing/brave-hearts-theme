# GTM Configuration Blueprint (Phase 1B)

No GTM container exists yet (confirmed with Andrew, 2026-07-06) — this is
a **blueprint for the manual container configuration**, to be executed
once the container is created (see `docs/analytics-architecture.md` for
naming/setup). Nothing here is a screenshot or a claim that GTM Preview
has been used — that is explicitly not possible without a real
container, and is not claimed anywhere in this phase's work.

## 1. Variables to create first

| Variable name | Type | Value / config |
|---|---|---|
| `GA4 Measurement ID` | Constant | The real `G-XXXXXXXXXX` value |
| `DLV - event_id` | Data Layer Variable | Data Layer Variable Name: `event_id` |
| `DLV - transaction_id` | Data Layer Variable | `transaction_id` |
| `DLV - currency` | Data Layer Variable | `currency` |
| `DLV - value` | Data Layer Variable | `value` |
| `DLV - items` | Data Layer Variable | `items` |
| `DLV - item_list_id` | Data Layer Variable | `item_list_id` |
| `DLV - item_list_name` | Data Layer Variable | `item_list_name` |
| `DLV - shipping_tier` | Data Layer Variable | `shipping_tier` |
| `DLV - payment_type` | Data Layer Variable | `payment_type` |
| `DLV - tax` | Data Layer Variable | `tax` |
| `DLV - shipping` | Data Layer Variable | `shipping` |
| `DLV - coupon` | Data Layer Variable | `coupon` |
| `DLV - bundle_type` | Data Layer Variable | `bundle_type` |

Use GTM's built-in **Google Tag: Google Analytics: GA4 Event** tag type,
which already reads standard ecommerce parameters (`items`, `value`,
`currency`, etc.) directly off the Data Layer without needing every field
above individually mapped as a variable — the table above is only needed
for the **custom/business-specific parameters** (`shipping_tier`,
`payment_type`, `bundle_type`, `item_list_id`/`item_list_name` on the
custom business events, etc.) that aren't part of GA4's own standard
ecommerce parameter set.

## 2. Consent initialization (must be tag #1, fires before everything else)

- **Consent Initialization trigger**: GTM has a dedicated trigger type,
  "Consent Initialization – All Pages," specifically for this. This
  codebase already prints the `gtag('consent','default',...)` call
  itself (`BHP_Consent::render_default_snippet()`, before the GTM script
  tag loads) — so GTM does NOT need its own separate Consent Initialization
  tag for the default state. Confirm this in GTM Preview once available:
  the consent defaults should already be visible in the Preview's
  "Consent" panel from page load, before any GTM-configured tag fires.
- If a future CMP is adopted, it may ALSO push its own
  `gtag('consent','update',...)` call (per that CMP's own Google-approved
  integration) — no GTM-side change needed for that; GTM tags with a
  Consent Settings requirement of `analytics_storage: granted` will
  simply start firing once that signal updates.

## 3. GA4 Configuration tag

| Setting | Value |
|---|---|
| Tag type | Google Tag |
| Tag ID | `{{GA4 Measurement ID}}` |
| Trigger | All Pages |
| Additional settings | None required beyond the default — this codebase does not use GA4's built-in "Enhanced Measurement" auto-events (page views are handled by this tag; ecommerce events are all explicit custom events below, not auto-detected) |

## 4. Custom Event triggers (one per implemented event)

Create a **Custom Event** trigger for each event name below (Trigger
type: Custom Event, Event name: exact match, case-sensitive):

`view_item_list`, `select_item`, `view_item`, `add_to_cart`,
`remove_from_cart`, `view_cart`, `begin_checkout`, `add_shipping_info`,
`add_payment_info`, `purchase`, `refund`, `adventure_kit_signup`,
`amazon_outbound_click`, `bundle_type_purchased`, `bundle_add_to_cart`,
`bundle_savings_applied`, `side_cart_opened`, `side_cart_cross_sell_clicked`,
`format_selected`, `bundle_format_selected`, `bundle_page_view`,
`lead_signup_success`, `signup_error` (Phase 1C — see
`docs/gtm-implementation-manifest.json` for the complete, machine-readable
list including every parameter, kept in sync with this doc).

**Do not create a trigger for `bhp_debug_internal_order_purchase_suppressed`**
— it only ever fires on staging (never production, by design — see
`docs/analytics-architecture.md`), so there is nothing for a production
GTM container to listen for.

## 5. GA4 Event tags (one per Custom Event trigger)

For each of the 11 GA4-standard ecommerce events (`view_item_list`
through `refund`), create a **Google Analytics: GA4 Event** tag:

| Tag | Configuration tag | Event name | Event parameters |
|---|---|---|---|
| GA4 - view_item_list | the GA4 Config tag above | `view_item_list` | `item_list_id`, `item_list_name` (from the DLVs above); `items` is read automatically |
| GA4 - select_item | same | `select_item` | `item_list_id`, `item_list_name` |
| GA4 - view_item | same | `view_item` | (currency/value/items read automatically) |
| GA4 - add_to_cart | same | `add_to_cart` | `source` (custom, not a standard GA4 param — map as a custom parameter if this level of detail is wanted in reports) |
| GA4 - remove_from_cart | same | `remove_from_cart` | none beyond standard |
| GA4 - view_cart | same | `view_cart` | none beyond standard |
| GA4 - begin_checkout | same | `begin_checkout` | none beyond standard |
| GA4 - add_shipping_info | same | `add_shipping_info` | `shipping_tier` |
| GA4 - add_payment_info | same | `add_payment_info` | `payment_type` |
| GA4 - purchase | same | `purchase` | `transaction_id`, `tax`, `shipping`, `coupon` (all standard GA4 purchase parameters) |
| GA4 - refund | same | `refund` | `transaction_id` |

For the business-specific events (`adventure_kit_signup`,
`amazon_outbound_click`, `bundle_type_purchased`, etc.), create GA4 Event
tags using the SAME event name as a **custom** GA4 event (GA4 accepts any
event name that isn't already a reserved/automatically-collected name) —
map each event's own custom parameters (`bundle_type`, `funnel`,
`bhp_book`, etc.) as event parameters the same way.

## 6. Required event-parameter mappings — summary

Every ecommerce tag above should have "Send Ecommerce data" (or
equivalent, depending on the GTM tag template version) checked, reading
from the Data Layer's `items` array automatically — **do not manually map
each item field** (`item_id`, `price`, etc.) as individual tag
parameters; GA4's own ecommerce tag template already expects and
correctly parses the exact schema this codebase already produces (see
`docs/event-dictionary.md`'s item schema table).

## 7. Environment handling

- Use GTM's own built-in **Environments** feature (Admin → Environments)
  to create a "Staging" environment pointed at
  `staging2.braveheartspublishing.com` and a "Live"/production
  environment — rather than a second container — matching the intent
  already documented in `docs/analytics-architecture.md`'s environment
  strategy. Confirm this covers every actual need before assuming it;
  if GTM environments prove insufficient later (e.g. genuinely different
  tag sets needed per environment), revisit with Andrew rather than
  silently working around it.
- This codebase's own environment gating (`BHP_Analytics_Config::is_staging()`,
  the staging tracking override, and the production consent gate) is a
  SEPARATE, code-level safety layer that exists regardless of which GTM
  environment feature is used — even if GTM's own environment config is
  misconfigured, staging traffic still can't reach production destinations
  by default, and production still won't activate without the consent
  gate. Treat GTM's environment feature as defense-in-depth, not the only
  safeguard.

## 8. Preview/debug procedure (once a real container exists)

1. In GTM, click **Preview**, enter the staging URL.
2. On staging, turn on the validation override:
   `wp option update bhp_staging_analytics_override 1`.
3. Walk the full funnel (browse → view_item_list → select_item →
   view_item → add_to_cart → view_cart → begin_checkout →
   add_shipping_info → add_payment_info → purchase-page load, then
   reload the purchase page to confirm no duplicate `purchase` fires).
4. In the GTM Preview pane, confirm each expected tag fires exactly once
   per real user action, with the parameter values expected (cross-check
   against this codebase's own debug panel, which shows the exact
   `dataLayer` payload for the same event — the two should always agree,
   since both read the same underlying pushes).
5. Switch to GA4 DebugView (same browser session) and confirm the events
   arrive with the expected parameters.
6. Check the Network tab for actual `google-analytics.com/g/collect` (or
   `region1.google-analytics.com`) requests — a dataLayer push or a GTM
   Preview showing a tag "fired" is not proof of delivery to GA4 by
   itself.
7. **Turn the staging override back off** (`wp option delete bhp_staging_analytics_override`)
   when finished.

### 8a. Phase 9 scenario checklist (2026-07-12 — new variables/event)

Added to the generic funnel walk above once GTM Preview is connected. Each row states the exact expected values and what counts as a failure.

| Scenario | Event(s) | Expected values | Duplicate-firing failure criteria |
|---|---|---|---|
| Amazon outbound click | `amazon_outbound_click` | `bhp_book` = the clicked book's adventure key (non-empty); `bhp_format` = `paperback` or `hardcover` (non-empty); `bhp_source` = the calling context string (non-empty) | Fires more than once per single click |
| Related-content click | `related_content_click` | `bhp_source` = `related_content_module`; `bhp_book` and `bhp_format` should be **absent/undefined** in the GTM Preview payload (this tag intentionally does not map them — confirm the tag does NOT show blank/empty values for these, since that would mean an unwanted mapping was added) | Fires more than once per single click; or `bhp_book`/`bhp_format` unexpectedly present with any value (including empty string) |
| Individual-book purchase (not a Collection) | `purchase`, and **no** `bundle_type_purchased` | Standard `purchase` ecommerce parameters populate correctly; `bundle_type_purchased` must NOT fire for a cart that doesn't qualify as a 2-3 title bundle | `bundle_type_purchased` firing for a non-qualifying order is a failure |
| Paperback Complete Collection purchase | `purchase` AND `bundle_type_purchased` | `bundle_type_purchased` fires with `bundle_type` = `paperback_3` (or `paperback_2` if only 2 distinct titles qualify) | Fires more than once per qualifying paperback group in a single order, or fires with the wrong tier number |
| Hardcover Complete Collection purchase | `purchase` AND `bundle_type_purchased` | `bundle_type_purchased` fires with `bundle_type` = `hardcover_3` (or `hardcover_2`) | Same as above, hardcover side |
| Mixed-format order (both paperback and hardcover Collections in one cart) | `purchase` AND `bundle_type_purchased` (twice) | `bundle_type_purchased` fires once for `paperback_<tier>` and once for `hardcover_<tier>` — two separate events in the same order, this is correct/expected, not a duplicate | Fires for the same `bundle_type` value twice, or fails to fire for one of the two qualifying formats |

## 9. Publication checklist (before publishing the container to production)

- [ ] Every tag above tested via GTM Preview on staging, at least once
      per event, with correct parameters confirmed in GA4 DebugView.
- [ ] Purchase event confirmed to fire exactly once even on a page
      refresh (staging validation, or the `test-purchase-validation-harness.php`
      fixture — see `docs/analytics-validation.md`).
- [ ] Confirmed no tag fires on `wp-admin` or while logged in as an
      administrator (this codebase already prevents this at the
      `dataLayer` level, but confirm the GTM container itself doesn't
      ALSO fire GA4's own auto-detected page-view tag in an admin
      context if "Enhanced Measurement" or an auto-page-view tag is ever
      added later).
- [ ] `bhp_consent_decision_approved` explicitly approved by Andrew (see
      `docs/analytics-architecture.md`, "Production readiness gate") —
      **do not publish the container to production before this is set**,
      since without it this codebase's own `BHP_GTM_Loader` won't print
      the GTM script on production at all, making the published container
      inert regardless of what GTM itself thinks its publish status is.
- [ ] GTM container published to the **Live** workspace/environment only
      after the above, with a clear version name/description noting the
      date and what was included.
- [ ] Confirm via `wp option get bhp_gtm_container_id` on production that
      the ID matches the just-published container (never a stale or
      wrong ID).

## 10. Container version naming convention

Name every GTM published version `YYYY-MM-DD — <what changed>`, e.g.
`2026-07-06 — initial GA4 ecommerce + Phase 1C lead events`. Never leave
a version with GTM's auto-generated default name — six months from now,
"Version 4" tells nobody anything. Include in the version description:
which events this version adds/changes, and a one-line pointer back to
this file's `_meta.generated` date in `docs/gtm-implementation-manifest.json`
so a future reader can find the exact matching manifest state.

## 11. Rollback procedure

GTM keeps every prior published version automatically — rollback never
requires touching this repository:

1. In GTM, go to **Versions** → find the last known-good version.
2. Click **Publish** on that version (this does NOT delete the broken
   version; it just makes the known-good one live again).
3. Independently of GTM: if something is badly wrong, the fastest full
   stop is still `wp option delete bhp_gtm_container_id` on production —
   this makes `BHP_GTM_Loader` print nothing at all regardless of GTM's
   own publish state, since the print gate is checked in this codebase,
   not just in GTM.
4. Purge the SiteGround cache after either action so the change is
   visible immediately (`wp sg purge`), then re-verify via the debug
   panel or a real page load that the intended state (rolled back, or
   fully off) is actually what's rendering.

## 12. Screenshot checklist (evidence for a completed setup)

When a real container is built and validated, capture and keep these
screenshots (they are the evidence a "GTM configured and verified" claim
should be backed by — do not assert this step is done without them):

- [ ] GTM container overview showing all triggers created (§4 list)
- [ ] GTM Preview pane mid-session showing at least `purchase`,
      `add_to_cart`, and one Phase 1C lead event firing with correct
      parameters
- [ ] GA4 DebugView showing the same events arriving
- [ ] Browser Network tab showing a real request to
      `google-analytics.com` or `region1.google-analytics.com`
- [ ] The published version's name/description (confirming §10's naming
      convention was followed)
- [ ] `wp option get bhp_gtm_container_id` output on production,
      immediately after publishing, showing the matching ID
