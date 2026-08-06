# Analytics Architecture (Phase 1B)

Foundation built 2026-07-06, before any paid acquisition begins. Covers
GA4/GTM installation architecture, environment separation, consent,
purchase deduplication, internal-test exclusion, and the dashboard
adapter contract. See also `docs/event-dictionary.md`,
`docs/utm-attribution.md`, `docs/analytics-validation.md`, and
`docs/gtm-configuration-blueprint.md` (the full manual GTM container
setup, since no container exists yet to configure directly).

## Current state (as of 2026-07-06)

**Neither a GA4 property nor a GTM container exists yet.** Confirmed by
direct audit: no `G-XXXXXXXXXX` or `GTM-XXXXXXX` ID anywhere in the
repository, and empirically via live network-request inspection on the
production homepage (no request to `googletagmanager.com` or
`google-analytics.com`). This phase builds the full code architecture
with configuration placeholders (empty WordPress options) so wiring in
real credentials later is a config change, not a code change.

## GA4 / GTM asset ownership

Andrew has confirmed (2026-07-06) that neither asset exists yet. He must
create them manually — this code does not create external Google assets
on its own, and never invents an ID.

**What to create:**

| Asset | Name | Setting |
|---|---|---|
| GA4 property | Brave Hearts Publishing | Reporting timezone: **America/Boise**; Currency: **USD** |
| GA4 web data stream | BraveHeartsPublishing.com | URL: `https://braveheartspublishing.com` |
| GTM container | Brave Hearts Publishing Website | Type: **Web** |

### Reporting timezone: America/Boise, not UTC (corrected 2026-07-06)

An earlier draft of this doc recommended UTC on the reasoning that
WordPress's `gmt_offset` is currently `0` with no named `timezone_string`
set. That reasoning was wrong: **WordPress's internal storage timezone
and GA4's reporting timezone are two independent decisions.** WordPress
storing timestamps in UTC internally (its own default, unrelated to where
the business operates) is not a reason for GA4 to report in UTC too — GA4
should report in the timezone that matches when Brave Hearts Publishing
actually operates and when a day's business activity actually starts and
ends, so "today" in the dashboard means the same day Andrew is living in.

- **GA4 property reporting timezone: `America/Boise`.**
- WordPress itself may continue storing timestamps in UTC internally —
  this phase does not require changing WordPress's own
  `timezone_string`/`gmt_offset` settings, and doing so is a separate,
  larger decision (it would shift every existing WooCommerce order
  timestamp's displayed local time) that is out of scope here.
- **Event timestamps sent to GA4 remain standards-compliant** (ISO 8601 /
  Unix epoch, always implicitly UTC or explicitly offset) — GA4 itself
  converts to the property's configured reporting timezone for display
  and day-boundary bucketing. Nothing in this codebase needs to convert
  timestamps before sending them; that conversion is GA4's job once the
  property's timezone is set correctly.
- **Dashboard reconciliation must account for this explicitly.** The
  existing KPI dashboard's "day" boundaries are computed in WordPress's
  own configured timezone (see `docs/kpi-definitions.md`). Once a real
  GA4 adapter is built (Phase 13 scaffold, still dormant), any
  side-by-side comparison of a GA4 metric for "today" against a
  WooCommerce KPI for "today" must confirm both are using the same
  day-boundary definition before treating a difference as a real
  discrepancy rather than a timezone-bucketing artifact — flagged here so
  a future session doesn't silently assume they match.

### Where to find each ID (these are configuration identifiers, not secrets — safe to note here, never a password or API key)

**GTM container ID** (`GTM-XXXXXXX`):
1. Sign in at `tagmanager.google.com` with the Google account used to
   create the "Brave Hearts Publishing Website" container.
2. Select that container from the account/container picker on the left.
3. The container ID is displayed in the top-right corner of the container
   dashboard (format `GTM-XXXXXXX`), and again in the "Install Google Tag
   Manager" dialog (Admin → Install Google Tag Manager) alongside the
   head/body snippets GTM itself generates — those snippets are for
   reference only; this codebase's own `BHP_GTM_Loader` prints the
   equivalent snippet itself once the ID is stored in the
   `bhp_gtm_container_id` option, so the GTM-generated snippet itself
   should never be pasted directly into any template.

**GA4 Measurement ID** (`G-XXXXXXXXXX`):
1. Sign in at `analytics.google.com`.
2. Admin (gear icon, bottom-left) → under the "Brave Hearts Publishing"
   property column → Data Streams → select the "BraveHeartsPublishing.com"
   web stream.
3. The Measurement ID is shown at the top-right of that stream's detail
   page (format `G-XXXXXXXXXX`).

Neither of these requires a Google password to be given to anyone else,
stored anywhere, or typed into this codebase — they are copy-pasted
configuration values, entered once via the `wp option update` commands
below. **No Google password, and no service-account JSON credential
file, should ever be requested, pasted into chat, or committed to this
repository** — if a future GA4 Data API integration needs a service
account, that credential is a WordPress-option-stored (or environment-
variable-stored) secret at that time, handled with the same care as the
Stripe/Bookvault credentials already in this project, never committed to
git.

Once created, store the IDs as WordPress options (never hardcoded):
```
wp option update bhp_gtm_container_id GTM-XXXXXXX
wp option update bhp_ga4_measurement_id G-XXXXXXXXXX
```
`BHP_Analytics_Config` validates the shape of both (rejects anything not
matching `GTM-...`/`G-...`, e.g. an old Universal Analytics `UA-...` ID)
and returns an empty string rather than printing a malformed value.

### Production consent-decision gate — set only when ready

Even once both IDs above are configured, **GTM will not go operational on
production** until a separate, explicit gate is turned on:
```
wp option update bhp_consent_decision_approved 1
```
This is deliberately a second, independent switch from the container ID
itself — see "Consent" below for why. Staging is not affected by this
gate (its own `bhp_staging_analytics_override` option already governs
bounded QA access).

## Environment strategy (Phase 3)

- **Staging** (`staging2.braveheartspublishing.com`, matched by exact
  hostname in `BHP_Analytics_Config::is_staging()`): tracking is
  **disabled by default**. An explicit WordPress option
  (`bhp_staging_analytics_override`) must be turned on for a bounded
  validation session — never left on permanently. This guarantees
  staging traffic can never silently reach the production GA4 property.
- **Production**: tracking is enabled by default (subject to consent and
  admin-exclusion below).
- **Same GTM container for both environments** — GTM's own
  environment/workspace features are the intended mechanism for any
  staging-vs-production tag differences later, not a second container.
  Confirm this assumption with Andrew before assuming it covers every
  future need.
- **Admin/internal exclusion**: `BHP_Analytics_Config::is_excluded_internal_request()`
  excludes `is_admin()`, `wp-login.php`, and any logged-in
  administrator/shop-manager session from tracking on the storefront,
  regardless of environment. Verified live: browsing the storefront while
  logged in as the site admin correctly shows "Tracking enabled this
  load: no" in the debug panel (Phase 14), even with staging override on.

## Installation architecture (Phase 4)

One centralized loader, `BHP_GTM_Loader` (`inc/class-bhp-gtm-loader.php`):
- `render_head_snippet()` on `wp_head` (priority 2, right after
  `bhp_init_datalayer()` at priority 1) — prints Consent Mode defaults
  (see below) THEN the GTM script, in that order, only once.
- `render_noscript_snippet()` on `wp_body_open` (priority 1) — the
  standard noscript iframe fallback.
- Both no-op completely (print nothing) when
  `BHP_Analytics_Config::gtm_container_id()` is empty, or when
  `should_render_analytics()` is false (staging without override,
  or excluded internal traffic).
- No other file in this theme or the bundle-pricing plugin ever prints a
  `googletagmanager.com` script tag — verified by test
  (`tests/test-gtm-loader.php`): a configured ID produces exactly one
  `gtm.js` reference and exactly one `<iframe>`.

## Consent (Phase 12) — compliance assumption flagged for Andrew

**No cookie/consent banner exists on this site.** This is a business/legal
decision for Andrew to make, not inferred here. `BHP_Consent`
(`inc/class-bhp-consent.php`) implements Google Consent Mode v2 with all
four signals (`analytics_storage`, `ad_storage`, `ad_user_data`,
`ad_personalization`) defaulting to **'denied'** whenever no consent
cookie exists — the conservative posture. A future real consent banner
sets a first-party cookie (`bhp_consent_state`, a small JSON object of
the four signals); this class only reads it back, never builds the
banner UI itself (out of scope for this phase).

Practical consequence: **with no consent banner AND no approved
production gate (below), GTM will not load in production at all** —
not "loads but tags don't fire," but "prints nothing." This fails safe
rather than silently collecting data without a legal basis, and rather
than relying on the consent signal alone as the only line of defense. A
staging-only exception exists purely for QA (see below).

Advertising consent (`ad_storage`/`ad_user_data`/`ad_personalization`) is
never inferred from analytics consent — verified by test.

### Production readiness gate (Phase 1B correction pass, 2026-07-06)

A real GTM container ID being configured is **not, by itself, sufficient
to activate GTM in production.** A second, independent switch —
`bhp_consent_decision_approved` (a WordPress option, default `false`) —
must also be explicitly turned on, and it is a deliberate business
decision, not a technical default:

- This gate exists so that configuring a container ID (a purely technical
  step) can never accidentally be read as "we've also handled the consent
  question." The two are intentionally decoupled.
- Approving this gate means Andrew has made an actual decision about
  consent handling — which could be "we built/adopted a real consent
  banner" or "we've reviewed this and concluded a banner isn't required
  for our current traffic/posture" — but a decision either way, never an
  unexamined default.
- `BHP_Analytics_Config::consent_gate_reason()` returns a plain-English
  explanation of whatever is currently blocking analytics (no GTM ID yet
  / gate not approved / staging override off / consent denied), checked
  in priority order so the reported reason is always the real first
  blocker. This is surfaced two ways so an administrator is never left
  guessing:
  - A `wp-admin` notice (`BHP_Analytics_Config::maybe_render_admin_notice()`)
    that appears on every admin screen **once a real GTM container ID
    exists** but the production gate isn't approved yet — silent before
    that, so it never nags before Andrew has started configuring
    anything.
  - The staging debug panel (Phase 14) shows the same reason string
    whenever tracking is blocked for the current page load.
- This gate never silently grants consent on its own — it only ever
  permits GTM to load at all; the four Consent Mode signals underneath
  still govern what GTM/GA4 tags are actually allowed to do once loaded.

### Staging validation exception

When `bhp_staging_analytics_override` is on, `BHP_Consent` also grants
`analytics_storage` (never the advertising signals) specifically so the
Phase 16/17 validation walkthrough can observe real dataLayer events —
this is a bounded, pre-launch QA convenience on a site with no real
visitors, not a production compliance decision.

## Purchase deduplication (Phase 7) — the highest-risk event

`woocommerce_thankyou` fires on every load/refresh of the order-received
page. `bhp_bundle_track_purchase_completed()`
(`plugins/brave-hearts-bundle-pricing/includes/bundle-analytics.php`)
guards this with an order-meta flag (`_bhp_purchase_event_fired`),
checked and set atomically per order — survives refreshes, different
devices/sessions, and page caching, unlike a sessionStorage flag.

- `transaction_id` = the WooCommerce order ID (string), stable forever.
- `event_id` = `purchase_<order_id>`, deterministic, for any future
  server-side reconciliation to de-duplicate against without needing to
  know GA4's own ingestion-window behavior.
- Only fires for orders in `processing`/`completed`/`on-hold` status —
  never failed or cancelled.
- `value` = order total minus tax (tax excluded from revenue by default
  policy; shipping is included, matching GA4's own recommended `value`
  definition). `tax` and `shipping` are reported as separate fields.
- Coupons, per-line discounts, and a full `items[]` array (see
  `docs/event-dictionary.md`) are included.
- Original order timestamp is never altered; no historical order
  financial data is modified by this phase.

## Internal/test order exclusion (Phase 8)

Reuses `BHP_Order_Provenance::is_executive_eligible()` — the exact
classifier the executive KPI dashboard already relies on — for both the
`purchase` and `refund` events, rather than maintaining a second,
separately-hardcoded ID list. An internal/test order still marks the
dedup flag as fired (so a refresh never re-evaluates it) but never emits
`purchase`; on staging only, it emits a clearly separate
`bhp_debug_internal_order_purchase_suppressed` event instead, so a
developer can confirm suppression happened without it ever being
mistaken for a real conversion downstream. Provenance classifications
themselves are never altered by this phase — verified by the existing
`test-order-provenance.php` suite still passing unchanged.

## Dashboard adapter architecture (Phase 13)

A second, GA4-shaped adapter contract
(`plugins/brave-hearts-bundle-pricing/includes/dashboard/analytics-adapters/interface-bhp-ga4-provider.php`
+ `class-bhp-ga4-null-provider.php`) sits alongside the existing ad-spend
contract (`interface-bhp-analytics-provider.php`, built for Meta/Pinterest
ad-spend reconciliation) rather than overloading one interface for two
different data shapes. Both are dormant scaffold code today — not
required by `dashboard-bootstrap.php` — following the exact pattern
already established for the ad-spend adapter.

The reconciliation rule this architecture protects: **WooCommerce remains
authoritative for actual orders/revenue everywhere.** GA4 is authoritative
only for behavioral analytics and attributed journeys. A future real GA4
adapter's revenue/purchase-count fields must always be displayed
alongside WooCommerce's own figures with the discrepancy shown
explicitly — never summed together.

## Debug/validation mode (Phase 14)

`BHP_Analytics_Debug` (`inc/class-bhp-analytics-debug.php`) renders a
floating panel, staging + logged-in-administrator only (never production
regardless of login state), that intercepts every `dataLayer.push()` call
and displays the event name, full payload, and timestamp, plus the
current environment/GTM-config/consent state. Verified live: fires
correctly through a full browse -> add-to-cart -> view-cart ->
remove-from-cart flow with zero console errors.

## Known limitations / deferred to a later session

- **`view_item_list`, `select_item`, `add_shipping_info`, and
  `add_payment_info` are now implemented** (Phase 1B correction pass,
  2026-07-06) — see `docs/event-dictionary.md` for the exact payload
  shape of each. `view_item_list`/`select_item` are scoped to the Shop
  archive page and related-products (content/marketing grids like the
  Books landing page and the Complete Collection bundle page remain
  deliberately out of scope — see the event dictionary for why).
- No real GTM Preview / GA4 DebugView validation is possible without real
  credentials (Phase 2 blocker) — validation in this phase is limited to
  direct `dataLayer` inspection (browser console + the debug panel),
  which is one of the explicitly permitted validation methods per the
  original spec.
- No consent-banner UI is built — only the Consent Mode plumbing to
  respect one once Andrew approves building it, now gated by an explicit
  production-readiness switch (see "Production readiness gate" above)
  so a real container ID alone can never accidentally activate GTM on
  production ahead of that decision.
