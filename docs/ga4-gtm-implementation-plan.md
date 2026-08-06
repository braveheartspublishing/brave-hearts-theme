# GA4 / GTM Implementation Plan

This is a plan to execute once Andrew provides real credentials. **No
placeholder ID has been installed anywhere, and none will be** — every
step below is blocked on the exact items listed in "What Andrew must
provide."

## What Andrew must provide (nothing here is invented)

- A Google Tag Manager Container ID (`GTM-XXXXXXX`), **or**
- A GA4 Measurement ID (`G-XXXXXXXXXX`) if going gtag-direct without GTM
- Confirmation of which approach is preferred (GTM is recommended — see below)
- A Google account with edit access to the container/property so tag
  changes can be validated in GTM's own Preview mode before publishing

## Recommended architecture: GTM, not gtag-direct

Install Google Tag Manager (not a direct `gtag.js` snippet) because:
- All the custom event mapping, purchase dedup logic, and future
  Pinterest/Meta pixel additions can live in GTM's tag/trigger config
  instead of more theme code.
- Non-engineering changes (adding a new destination, adjusting a
  trigger) become a GTM console change, not a deploy.
- The existing `window.dataLayer` push pattern already used sitewide is
  exactly what GTM expects — zero JS rework needed to switch from "push
  and nothing listens" to "push and GTM listens."

## Installation method

1. **Staging first.** Add the GTM head snippet immediately after
   `<head>` and the noscript body snippet immediately after `<body>` in
   `header.php` — the theme already has a single, sitewide `header.php`
   used by every template, so this is one file, one place.
2. Gate the snippet behind a real container ID stored as a WordPress
   option (`bhp_gtm_container_id`), not hardcoded — so the ID lives in
   one place and can be changed without a deploy. If the option is
   empty, print nothing (never fall back to a placeholder).
3. Verify via GTM's own Preview/Debug mode on staging before touching
   production.

## GA4 configuration inside GTM

- One GA4 Configuration tag, firing on All Pages, using the Measurement
  ID.
- One GA4 Event tag per mapped custom event (see mapping table below),
  each triggering off a Custom Event trigger matching the exact
  `event:` value already pushed to `dataLayer`.

## Custom event → GA4 event mapping

| Existing dataLayer event | GA4 recommended event | Notes |
|---|---|---|
| `product_viewed` | `view_item` | Needs `items[]` reshaping (see event inventory doc) |
| `format_selected` / `bundle_format_selected` | `select_item` | |
| `add_to_cart` | `add_to_cart` | Needs `items[]` + `value` + `currency` reshaping |
| `checkout_started` | `begin_checkout` | Needs `items[]` + `value` |
| `purchase_completed` | `purchase` | **Must be deduplicated first — see below. This is the highest-priority fix.** |
| `side_cart_opened`, `side_cart_cross_sell_clicked`, `second_book_added`, `complete_set_reached`, `bundle_add_to_cart`, `bundle_savings_applied` | Custom GA4 events (no exact standard-event equivalent) — keep as-is, named consistently | |
| `<prefix>_view` / `_submit` / `_success` (popup) | `generate_lead` on `_submit`, custom events for `_view`/`_success` | |

## Ecommerce payload mapping (required before "add_to_cart"/"purchase" are trustworthy)

Every product-related event needs an `items[]` array shaped like:
```json
{ "item_id": "<product or variation ID>", "item_name": "<title>", "price": 11.99, "quantity": 1 }
```
This is a small, targeted PHP/JS change to the existing push call sites
— not a rewrite — and should happen on staging as its own tested change
before GA4 wiring, so the event shape is already correct when GTM starts
listening.

## Purchase deduplication (do not skip this)

`purchase_completed` currently fires on every load of
`woocommerce_thankyou`, including refreshes. Before mapping it to GA4's
`purchase` event:
- Add an order meta flag (`_bhp_purchase_event_fired`) set the first
  time the event is printed; skip printing on subsequent loads of the
  same order.
- Use `order_id` as GA4's `transaction_id` — GA4 also does its own
  dedup by `transaction_id` within its ingestion window, so this is
  belt-and-suspenders, not redundant.

## UTM persistence

- Capture `utm_source/medium/campaign/content/term` on landing via a
  first-party cookie or `sessionStorage`, persisted through the whole
  session (including through the popup/quiz funnel and into checkout),
  so `purchase` events can be attributed back to the original visit
  even after multiple page views.
- See `utm-attribution-standard.md` for the exact parameter values to
  expect from each channel.

## Experiment attribution

- Once the quiz-vs-popup A/B test (see `experiment-quiz-vs-popup.md`)
  is live, its `experiment_id`/`variant` fields should ride alongside
  every event in the same session (added to the dataLayer push, not a
  separate disconnected event stream) so GA4 can segment conversion by
  variant without a custom join.

## Consent/privacy considerations

- No cookie/consent banner currently exists on this site (confirmed by
  earlier audits). Before GTM goes live in production, confirm with
  Andrew whether Google Consent Mode is required for the site's current
  legal/privacy posture — this is a business decision, not a technical
  default, and this plan does not assume an answer.

## Staging validation

1. Install the real container ID on staging only.
2. Use GTM Preview mode against staging to confirm every tag in the
   mapping table above fires exactly once, with the right payload, for
   a full funnel walkthrough (browse → add to cart → checkout →
   purchase-page load, load again to confirm no duplicate `purchase`).
3. Use GA4 DebugView (same GTM Preview session) to confirm events
   arrive in GA4 with the expected parameters.
4. Check the Network tab for actual `google-analytics.com/g/collect`
   (or `region1.google-analytics.com`) requests firing — dataLayer
   pushes alone are not proof of delivery, exactly per the standard
   this project already holds itself to.

## Production deployment

- Only after staging validation passes in full, and only after
  Andrew's explicit approval for a new production deploy (this plan
  does not authorize itself to deploy).
- Same container ID reused (GTM's own environment/workspace features,
  not a second container, handle staging vs. production if ever
  needed) — confirm this with Andrew before assuming one container
  covers both environments.

## Rollback

- Removing the `bhp_gtm_container_id` option (or blanking it) stops the
  snippet from printing at all — no code rollback needed, since the
  snippet is gated on that option's presence.
