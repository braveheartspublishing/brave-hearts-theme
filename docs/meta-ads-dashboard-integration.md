# Meta Ads Dashboard Integration — Future Architecture Specification

**Status: architecture and adapter contract only. No live Meta API calls
have been made. No Meta app or credentials exist yet. This document and
its accompanying code are scaffolding for a future session, not an active
feature.**

Written during the Phase 9 KPI-dashboard reconciliation pass
(2026-07-06). Companion code: `includes/dashboard/analytics-adapters/`
(interface + null provider, both dormant — not required from
`dashboard-bootstrap.php`), `tests/test-analytics-adapter-contract.php`.

## Why this exists now, before any Meta connection

Andrew asked for the dashboard's KPI/refund/Bookvault/cost/cache
foundation to be solid *before* any new integration work begins. This
document defines the boundary a future Meta integration must respect so
it can be built later without coupling the dashboard UI to Meta's API
shape, without ever blocking a page load on a live external call, and
without becoming the only integration the dashboard can ever support.

## A. Secure configuration

**Future inputs** (not collected or stored yet):

- Meta Business Portfolio / Business ID
- Meta ad account ID
- Meta App ID (only if a full OAuth app is used instead of a system-user token)
- System-user access token, scoped to `ads_read` (see below)
- Graph API version pinned explicitly (e.g. `v21.0`), never "latest"
- Ad account's own reporting timezone
- Attribution settings actually configured on the ad account (click/view windows)

**Least privilege**: this is a *reporting* dashboard. The system-user
token should request `ads_read` only. Ad-management scopes
(`ads_management`) must not be requested unless a later, separately
approved feature genuinely needs to create/edit/pause campaigns — that is
a materially different trust level and should require its own explicit
sign-off, not be bundled in "while we're at it."

**Security requirements** (all must hold before any real token exists):

- No token in Git, ever — not in a comment, not in a "temporary" test
  file, not in a `.env.example` with a real-looking value.
- No token in a committed WordPress options export (e.g. a UpdraftPlus
  backup checked into the repo, or a `wp option get` output pasted into a
  doc).
- No token in logs — error_log lines, debug.log, or any diagnostic output
  must redact it (see below).
- No token in HTML or JavaScript output, ever, including inline in an
  admin page's source for "just this session."
- No token in public documentation, including this file.
- Preferred storage: a server-level environment variable
  (`wp-config.php` constant sourced from an environment variable, or a
  secrets manager if SiteGround's hosting tier supports one) rather than
  a plain `wp_options` row, so a database export/backup does not also
  export the credential.
- **Diagnostics without exposure**: any admin-facing status message about
  the token (expired, invalid, missing) must describe the *situation*
  ("Token expired 2026-06-01" / "Authentication failed — check the system
  user's permissions") and never echo any portion of the token itself,
  including a truncated prefix/suffix.

## B. Data flow

```
Meta Ads Insights API
    -> scheduled server-side fetch (WP-Cron or a real system cron, NOT a page-load-triggered call)
    -> normalization layer (maps Meta's response shape to the schema in
       BHP_Analytics_Provider_Interface::get_daily_metrics())
    -> cached/stored daily aggregate records (a dedicated table or
       postmeta-free custom table -- NOT ad-hoc transients, since this
       data needs to survive longer than a 5-minute KPI cache and support
       historical backfill/re-fetch)
    -> dashboard adapter (a real class implementing
       BHP_Analytics_Provider_Interface)
    -> Meta Ads tab/panel (reads only from the adapter, never from a raw
       Meta API response)
```

The dashboard page itself **never** makes a synchronous Meta API call.
Every number shown was fetched by a prior scheduled job and normalized
before the admin ever loads the page — matching the existing KPI cache's
philosophy of "compute ahead of time, serve fast."

## C. Initial Meta KPIs (planned, not built)

Per-row (already reflected in `get_daily_metrics()`'s documented schema):
amount spent, impressions, reach, frequency, link clicks, landing-page
views (where available), click-through rate, cost per click, cost per
landing-page view, add-to-cart actions (where available), initiated
checkouts (where available), Meta-attributed purchases, purchase
conversion value, cost per purchase (derived: spend ÷ purchases),
return on ad spend (derived: purchase value ÷ spend), campaign/ad-set/ad
breakdown and names, reporting date, attribution window/settings, last
successful sync time, and sync/freshness/failure state.

## D. Reconciliation model — the part most likely to be gotten wrong

The future dashboard must show these as **separate, clearly labeled**
figures, never combined into one number:

- WooCommerce actual orders/revenue (already authoritative today —
  `BHP_Order_Metrics::compute_kpis()`)
- GA4-attributed web conversions (not yet connected)
- Meta-reported attributed conversions (not yet connected)
- Meta spend
- **Blended business ROAS** = actual WooCommerce revenue attributable to
  the campaign period ÷ Meta spend — calculated from the *site's own*
  authoritative order data, not from Meta's self-reported conversion
  value
- **Platform-reported ROAS** = Meta's own `purchase_value ÷ amount_spent`,
  shown alongside the blended figure, explicitly labeled as
  platform-reported (Meta's attribution model very commonly over-counts
  relative to what a site's own order data shows)

**Hard rule, enforced by `test-analytics-adapter-contract.php`'s
"reconciliation-model guard" test**: WooCommerce order counts and
Meta-attributed purchase counts are never added together into a single
"total purchases" figure. They measure different things (a confirmed
completed WooCommerce transaction vs. a platform's attribution-window
guess) and summing them produces a number that means nothing.

## E. Scheduled synchronization (planned, not built)

- **Cadence**: daily is sufficient for a small store's ad spend review;
  hourly is unnecessary API load for the data freshness this dashboard
  needs. A manual "sync now" action (mirroring the existing "Refresh now"
  KPI-cache button) can supplement the schedule.
- **Ad-account timezone**: Meta reports each day's data in the ad
  account's own configured timezone, which may differ from the
  WordPress site's timezone (currently a fixed UTC+0 offset — see
  `docs/kpi-definitions.md`). Normalized records store the date exactly
  as Meta reports it, with the ad-account timezone recorded alongside so
  a later cross-reference with WooCommerce dates is done deliberately,
  not by assuming the two calendars line up.
- **Backfill window**: Meta's own attribution can take several days to
  fully settle (a "purchase" attributed to a click from 5 days ago can
  still arrive today). A production sync job should re-fetch the
  trailing ~7-10 days on every run, not just "yesterday," so late
  attribution updates are captured — an idempotent upsert (below) makes
  this safe to do repeatedly.
- **Idempotent upserts**: each stored record is keyed by
  (provider, date, campaign_id, ad_set_id, ad_id) so re-fetching the same
  day multiple times updates the existing row rather than creating
  duplicates.
- **Pagination**: Meta's Insights API paginates; the fetch job must
  follow `paging.next` until exhausted, not assume a single page.
- **Rate limiting**: respect Meta's returned rate-limit headers and back
  off accordingly rather than fixed-interval retries.
- **Retry/backoff**: exponential backoff on transient failures (5xx,
  timeout); a hard failure after N retries marks that day's sync
  `'failed'` in `get_sync_status()` rather than silently leaving stale
  data mislabeled as current.
- **Partial failure handling**: if campaign A's fetch succeeds and
  campaign B's fails in the same run, A's data is still stored — one
  failure must not discard an entire run's otherwise-good data.
- **API-version upgrades**: the pinned Graph API version is a
  configuration value, not a hard-coded literal buried in a request URL,
  so it can be bumped deliberately (with its own changelog review) rather
  than silently drifting.
- **Last-good-data preservation**: exactly like `BHP_KPI_Cache`'s existing
  "a failed refresh never replaces valid cached data with corrupt data"
  guarantee (see `test-cache-invalidation.php`), a failed Meta sync must
  never overwrite yesterday's good stored data with an empty/partial
  result — it should leave the last-good record in place and mark the
  sync status `'stale'` or `'failed'` instead.

## F. Interface contract (built now, dormant)

`BHP_Analytics_Provider_Interface`
(`includes/dashboard/analytics-adapters/interface-bhp-analytics-provider.php`)
defines the full provider-neutral contract: `get_provider_key()`,
`get_display_name()`, `is_configured()`, `get_configuration_issue()`,
`get_daily_metrics($start, $end)`, `get_sync_status()`. Any future
provider — Meta, GA4, Pinterest, or an email platform's own campaign
stats — implements this same interface, so the dashboard UI is written
once against the interface and never against a specific provider's
response shape.

`BHP_Null_Analytics_Provider` is a working null-object implementation
proving the "dashboard works with zero configured integrations"
requirement: `is_configured()` returns `false`, `get_daily_metrics()`
returns an empty array (never `null`), and `get_sync_status()` reports
`'never_synced'` — all safe, well-formed answers a real panel could
render today with zero risk, if one existed. **Neither file is required
from `dashboard-bootstrap.php`** — they are present and tested but not
part of the live plugin initialization, so this work has zero effect on
the currently-running dashboard.

## G. Deliverables (this pass)

- [x] `docs/meta-ads-dashboard-integration.md` (this file)
- [x] Provider interface:
      `includes/dashboard/analytics-adapters/interface-bhp-analytics-provider.php`
- [x] Null/reference implementation:
      `includes/dashboard/analytics-adapters/class-bhp-null-analytics-provider.php`
- [x] Normalized metric schema: documented on `get_daily_metrics()`'s
      docblock (see the interface file) — one canonical shape every
      provider must normalize into
- [ ] Configuration checklist: see Section A above (inputs Andrew will
      need to gather) — no settings UI exists yet; building one is future
      work
- [x] Security checklist: see Section A above
- [ ] Proposed admin UI wireframe: a new "Meta Ads" tab/section on the
      existing dashboard page, positioned after "Fulfillment status" and
      before "Future analytics (not yet connected)" (which this would
      eventually replace/absorb) — showing the KPI cards from Section C
      plus the Section D reconciliation table; not built yet
- [x] Test strategy using fixtures/mocks only:
      `tests/test-analytics-adapter-contract.php` — a fixture provider
      standing in for a real Meta adapter, zero live API calls

## Explicitly out of scope for this pass (per instruction)

No live Meta API call was made. No Meta Business/App/ad-account was
created or connected. No token — real or placeholder — was written
anywhere, including in this document. No third-party Meta plugin was
installed. The dashboard's `wp_enqueue_scripts`/menu registration was not
touched by this section of work.
