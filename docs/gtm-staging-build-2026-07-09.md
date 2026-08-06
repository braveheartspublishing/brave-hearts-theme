# GTM Container Build — Staging Session (2026-07-09)

## Status: PARTIALLY BUILT, NOT PUBLISHED, NOT VALIDATED

Container: `GTM-N474PRSH` ("Brave Hearts Publishing" account, `www.braveheartspublishing.com`
container, accountId `6364597357`, containerId `257525520`), workspace 2 (default draft
workspace — this was empty before this session, confirmed via a "You haven't made any
changes yet" / "No Recent Data" welcome-state screen before any work began).

**Confirmed via the Overview page's own "Pending Changes" counter at the end of this
session: 0 Modified, 4 Added, 0 Deleted.** The workspace has never been Submitted or
Published — the "Submit" button (which creates a version, a separate step from
publishing) was never clicked.

## Why this session stopped short of full configuration

Building this container requires many repetitive UI actions in Google's own
web app (a New → rename → choose type → configure → Save cycle per item, ~24
variables + 1 Google tag + ~13-15 triggers + ~13-15 tags = ~50+ items). Partway
through, the browser-automation tooling used for this session showed
intermittent unreliability against this specific app (page reflows during
clicks, a couple of screenshot timeouts, and at least one batch of clicks that
landed on the wrong elements because the page was still loading). Two of those
misclicks were caught and corrected immediately (an accidental "select all
built-in variables" and an accidental 2-row checkbox selection) — nothing was
deleted or corrupted. Given this is Andrew's real, live Google account (not a
sandbox), the judgment call was to stop building via rapid automation once
reliability degraded, rather than risk an uncaught mistake, and instead hand
off the exact remaining specification below.

## What was actually built and verified this session

| # | Item | Type | Value/Key | Verified |
|---|---|---|---|---|
| 1 | `GA4 Measurement ID` | Constant variable | `G-7M42X19Z2T` | Confirmed in the variables list, `variableId=3` |
| 2 | `DLV - event_id` | Data Layer Variable | key: `event_id` | Confirmed, `variableId=4` |
| 3 | `DLV - transaction_id` | Data Layer Variable | key: `transaction_id` | Confirmed |
| 4 | `DLV - currency` | Data Layer Variable | key: `currency` | Confirmed |

Each was verified by reading the actual saved row in the Variables list after
saving (name + type + "a few seconds ago" timestamp), not assumed from the
click sequence alone.

## Exact remaining work (do this next, in this order)

### A. Remaining 20 Data Layer Variables

Same recipe as the 4 above (New → type `DLV - <name>` as the variable name →
choose "Data Layer Variable" type → enter `<key>` as the Data Layer Variable
Name → Save):

| Variable name | Data Layer Variable Name (key) |
|---|---|
| `DLV - value` | `value` |
| `DLV - items` | `items` |
| `DLV - item_list_id` | `item_list_id` |
| `DLV - item_list_name` | `item_list_name` |
| `DLV - shipping_tier` | `shipping_tier` |
| `DLV - payment_type` | `payment_type` |
| `DLV - tax` | `tax` |
| `DLV - shipping` | `shipping` |
| `DLV - coupon` | `coupon` |
| `DLV - bundle_type` | `bundle_type` |
| `DLV - lead_offer` | `lead_offer` |
| `DLV - audience` | `audience` |
| `DLV - placement` | `placement` |
| `DLV - signup_method` | `signup_method` |
| `DLV - error_reason` | `error_reason` |
| `DLV - cta_id` | `cta_id` |
| `DLV - cta_placement` | `cta_placement` |
| `DLV - cta_destination_type` | `cta_destination_type` |
| `DLV - funnel_stage` | `funnel_stage` |
| `DLV - variant` | `variant` |

(Full list including the 4 already-built ones lives in
`docs/gtm-implementation-manifest.json`'s `gtm_variables_required` array —
treat that JSON file as the single source of truth if this doc and that file
ever disagree.)

### B. The Google tag (do this before any GA4 event tag — they all reference it)

- Tags → New → name it `TAG - GA4 - Google Tag`
- Tag type: **Google Tag** (the modern unified GA4 config tag type, not the
  legacy "Google Analytics: GA4 Configuration" type)
- Tag ID field: `{{GA4 Measurement ID}}` (the constant variable built above)
- Triggering: **All Pages** (GTM's built-in trigger — do not create a custom
  one for this)
- Consent Settings: set to require `analytics_storage` = granted (this
  matches the codebase's existing default-denied consent architecture — see
  `inc/class-bhp-consent.php` and `docs/consent-privacy-decision-record.md`).
  **Do not check "Additional consent checks are not required."**

### C. Custom Event triggers (one per real event name)

Create a **Custom Event** trigger (Trigger type: Custom Event, Event name:
exact match, case-sensitive, "Use regex matching" left OFF) for each of these
23 real, implemented event names — see `docs/event-dictionary.md` and
`docs/gtm-implementation-manifest.json` for the source file behind each:

```
view_item_list, select_item, view_item, add_to_cart, remove_from_cart,
view_cart, begin_checkout, add_shipping_info, add_payment_info, purchase,
refund, adventure_kit_signup, lead_signup_success, signup_error,
amazon_outbound_click, contextual_cta_click, contextual_cta_view,
related_content_click, landing_page_view, landing_page_cta_click,
lead_form_view, lead_form_start, bundle_page_view
```

**Do NOT create a trigger for:**
- `bhp_debug_internal_order_purchase_suppressed` — staging-only debug event,
  never fires on production, no GTM trigger should exist for it.
- `related_book_click`, `collection_discovery_click` — **these do not exist
  in the codebase yet.** No `dataLayer.push()` anywhere emits these event
  names (confirmed via `docs/event-dictionary.md`'s explicit "Not built this
  phase" list). Creating a trigger for them would be harmless (it would just
  never fire) but is pointless until the underlying code exists — don't
  build tags for these either.
- `direct_purchase_click` — this event is real but is actually named
  `bhp_direct_purchase_click` in the live code (see `assets/js/nav.js`), not
  `direct_purchase_click`. Use the real name if building this trigger.

### D. GA4 Event tags (one per trigger above)

For each trigger in section C, create a **Google Analytics: GA4 Event** tag
(name convention: `TAG - GA4 Event - <event_name>`):
- Configuration Tag / Google tag: select the `TAG - GA4 - Google Tag` built in
  step B (this is what ties every event tag to the one Measurement ID).
- Event Name: the literal event name (e.g. `view_item_list`).
- Event Parameters: map only what's documented per event in
  `docs/gtm-implementation-manifest.json`'s `parameters` array — e.g.
  `add_shipping_info` gets `event_id` → `{{DLV - event_id}}`, `shipping_tier`
  → `{{DLV - shipping_tier}}` (plus `currency`/`value` which GA4's own
  "Send Ecommerce data" checkbox reads automatically off `items`/`value`/
  `currency` in the dataLayer — do not manually re-map those).
- For the 11 GA4-standard ecommerce events (`view_item_list` through
  `refund`), check **"Send Ecommerce data"** so `items[]` is read
  automatically per `docs/event-dictionary.md`'s item schema table — never
  manually map individual item fields.
- Triggering: the matching Custom Event trigger from section C.

### E. Consent

- Confirm the Google tag from step B has Consent Settings requiring
  `analytics_storage: granted` (built in step B — verify it, don't skip it).
- Do **not** add a "Consent Initialization – All Pages" trigger/tag — this
  codebase already prints `gtag('consent','default',...)` server-side before
  the GTM script tag loads (`BHP_Consent::render_default_snippet()`), so GTM
  doesn't need its own initialization tag for the default state (see
  `docs/gtm-configuration-blueprint.md` §2).
- Do not grant any `ad_storage`/`ad_user_data`/`ad_personalization` consent
  anywhere in this container — no advertising tag exists or should exist here.

### F. After A-E are complete: staging validation (not done this session)

1. Confirm production is untouched and staging's real IDs are intact (repeat
   the checks in this doc's "Environment safety" section below).
2. Record the current `bhp_staging_analytics_override` value (expected:
   unset), then `wp option update bhp_staging_analytics_override 1
   --url=staging2.braveheartspublishing.com`.
3. Click **Preview** in GTM, enter `staging2.braveheartspublishing.com`.
4. Walk the funnel per `docs/gtm-configuration-blueprint.md` §8 — browse,
   `view_item_list`, `select_item`, `view_item`, `add_to_cart`, `view_cart`,
   `begin_checkout`, `add_shipping_info`, `add_payment_info`; use the
   existing purchase-validation harness (`tests/test-purchase-validation-harness.php`
   or the plugin's equivalent) for `purchase`/`refund` — never a real order.
5. Cross-check GA4 DebugView (same browser session) for the same events.
6. Check the Network tab for a real request to `google-analytics.com/g/collect`
   or `region1.google-analytics.com` — a "tag fired" indicator in Preview is
   not proof of delivery by itself.
7. Test consent denied vs. granted explicitly (a denied `analytics_storage`
   cookie must block every tag from firing, even with Preview active).
8. **Restore the override afterward**: `wp option delete
   bhp_staging_analytics_override --url=staging2.braveheartspublishing.com`
   (or restore its exact prior value if this repo's history ever shows it was
   something other than unset).
9. Only after all of the above passes, and only with Andrew's explicit,
   current-turn approval, proceed to the production-activation prerequisites
   in `docs/consent-privacy-decision-record.md` §4 — none of which are
   affected by anything in this document.

## Environment safety (confirmed at the end of this session)

- Staging theme: 1.19.3 (unchanged) — plugin: 1.8.0 (unchanged)
- Staging GA4: `G-7M42X19Z2T` (unchanged) — GTM: `GTM-N474PRSH` (unchanged)
- Staging analytics override: unset/off (never touched this session — no
  Preview validation was attempted since the container isn't complete yet)
- Production: theme 1.19.2, plugin 1.7.0, no GA4/GTM options set, consent
  gate unapproved — all unchanged, confirmed via read-only SSH checks
- GTM workspace: 4 items added, 0 modified, 0 deleted, never Submitted,
  never Published
- No real order, no fulfillment, no Mailchimp signup, no live email — none of
  this session's work touched WooCommerce, Bookvault, or Mailchimp at all
