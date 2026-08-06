# Production Readiness Audit + Deployment Runbook (2026-07-10)

**Status: NOT approved for deployment.** This document is a read-only audit
and a prepared runbook. No production files, database values, or GTM/GA4
configuration were touched while producing it. Andrew's explicit,
current-turn approval is required before any of the deployment steps below
are executed, per this repo's standing production-safety rule.

## 1. Current state (live-verified, not from stale docs)

| | Production (`braveheartspublishing.com`) | Staging (`staging2...`) |
|---|---|---|
| Theme version | **1.19.2** | **1.19.4** |
| Theme commit (matched by version) | `f76102b` | `03b3cb8` (HEAD) |
| Bundle-pricing plugin version | **1.7.0** | **1.8.1** |
| Plugin commit (matched by version) | `41d7725` | `03b3cb8` (HEAD) |
| GA4 Measurement ID | unset (not live) | `G-7M42X19Z2T` (analytics config only, no live traffic yet) |
| GTM container | not installed | `GTM-N474PRSH`, workspace draft: 11 Added / 0 Modified / 0 Deleted, never Submitted/published |
| `bhp_staging_analytics_override` | n/a | unset (default/off) |

`docs/PROJECT_STATE.md` still describes production as v1.17.5 (deployed
2026-07-05) — that entry is stale; production has since received at least
one more deploy (the economics/CPA feature at `41d7725`, per completed
tasks #186-194) that brought it to 1.19.2/1.7.0. This is a documentation
gap, not a defect; recommend updating `PROJECT_STATE.md`'s top summary the
next time any deploy actually happens.

## 2. Full staged diff (everything currently undeployed)

Two independent commit lineages, since theme and plugin last deployed on
different occasions:

- Theme: `git diff f76102b..HEAD` — **58 files, +7,999/-19 lines**
- Plugin: `git diff 41d7725..HEAD` (within `plugins/brave-hearts-bundle-pricing/`) — **12 files, +1,423/-47 lines**

This is the accumulated output of three full phases plus two smaller fixes,
all previously staged but never shipped:

1. **Phase 1B/1C** — GA4 event coverage (`view_item_list`, `select_item`,
   `add_shipping_info`, `add_payment_info`), consent gate
   (`inc/class-bhp-consent.php`), UTM attribution (`inc/class-bhp-utm-attribution.php`,
   `assets/js/bhp-attribution.js`), lead-event logging, purchase validation
   harness. Docs: `docs/analytics-architecture.md`, `docs/consent-privacy-decision-record.md`,
   `docs/utm-attribution.md`, `docs/operator-runbook-phase1b-1c.md`.
2. **Phase 1D** — content classification, CTA decision engine, campaign
   landing-page framework, conversion-readiness scoring, Pinterest
   content-brief schema. Docs: `docs/phase1d-organic-conversion-architecture.md`
   (has its own full deployment checklist/rollback plan — read that before
   deploying this slice) and its sibling `phase1d-*` docs.
3. **Coupon-entry restoration** (this session, Objective 3) — `style.css`
   coupon CSS, `bundle-drawer.php` checkout hint, version bumps. Doc:
   `docs/coupon-ui-restoration-2026-07-09.md`.
4. **Security investigation** (this session) — read-only, no code changes
   beyond the report itself: `docs/security-investigation-nlo-finance-redirect-2026-07-09.md`.
5. **This session's GTM/analytics work** — `bundle-analytics.php` GA4
   provider abstraction, checkout-events JS, list-tracking JS, purchase
   harness Scenario C (coupon analytics). None of this requires a
   production deploy by itself yet, since the GTM container it feeds is
   still mid-build and unpublished (see §4).

Every one of these was already independently staged, tested, and
documented as **not deployed, pending Andrew's approval** in its own prior
session — this audit does not surface anything new, it consolidates what's
outstanding into one picture.

## 3. Database / options / commerce data

- **No `wp_options` changes** required or made this session (confirmed:
  no writes to production; staging's `bhp_staging_analytics_override` was
  never toggled — it was read once via WP-CLI this session, which
  returned "does not exist," i.e. unset/default/off, exactly as expected).
- **No product price, coupon, or shipping-zone changes.** `[PARENT_COUPON_CODE_SUPERSEDED]`
  (ID 346) was never touched — read-only throughout.
- **No new database tables, custom post types, or schema migrations**
  anywhere in the outstanding diff. Everything is theme/plugin file-based;
  a `wp theme install --force` + plugin file copy is a complete deploy,
  no `wp db` step is needed.
- One historical exception already known and unrelated to this diff: the
  Lulu→Bookvault product-copy fix from a much earlier session was a
  one-time, already-applied data fix, not a pending migration.

## 4. GTM / GA4 / consent

- **GTM is mid-build, not production-ready.** 11 of 24 required
  `gtm_variables_required` entries exist (all `DLV - *` variables plus the
  `GA4 Measurement ID` constant). Missing: the Google tag itself, all
  custom-event triggers, and all GA4 event tags — see
  `docs/gtm-staging-build-2026-07-09.md` (now stale by 7 variables; should
  be updated to reflect 11/24 the next time this container is worked on).
  **The container has never been Submitted, so nothing in it can reach any
  site regardless of code deploy state** — GTM publish is a fully separate
  action from a WordPress deploy and was correctly never invoked.
- **GA4 Measurement ID (`G-7M42X19Z2T`) is not live anywhere** — it only
  exists in this repo's documentation and the GTM variable above. No
  production or staging page currently loads a GTM container or fires GA4
  events from real traffic.
- **Consent gate** (`inc/class-bhp-consent.php`) exists in the outstanding
  diff, already reviewed in Phase 1B/1C's own security/perf/a11y pass
  (task #211) — defaults denied, no silent ad-consent grant, revocation
  works. Unchanged this session. Production consent gate remains
  unapproved (by design — GTM isn't finished, so there's nothing to gate
  yet).

## 5. Version bumps required at deploy time

- Theme `style.css`: `1.19.2` → `1.19.4` (two bumps already made in the
  outstanding diff: 1.19.3 for the analytics-foundation commit, 1.19.4 for
  the coupon fix — no further bump needed for deploy, just ship as-is).
- Plugin: `1.7.0` → `1.8.1` (1.8.0 for analytics foundation, 1.8.1 for the
  coupon-fix drawer hint — likewise ready to ship as-is).

## 6. Migration steps

None. No DB schema changes, no data backfill, no cache-warming beyond the
standard post-deploy purge below.

## 7. Cache

Standard post-deploy step per `.claude/rules/production-safety.md`:
`wp sg purge` on production immediately after the file deploy, before any
customer-facing verification.

## 8. Rollback plan (prepared, not executed)

Before any future deploy:
1. `tar czf` a fresh backup of the live theme directory and the
   `brave-hearts-bundle-pricing` plugin directory on production, timestamped,
   stored outside the web root (e.g. `~/backups/`).
2. Verify the backup archive is non-empty and readable
   (`tar tzf <file> | head`) before proceeding — do not trust a silent
   success.
3. Record the exact pre-deploy `wp theme list` / `wp plugin list` version
   output as the rollback target reference.

Rollback if a post-deploy check fails:
1. `wp theme install --force` using the pre-deploy ZIP.
2. Restore the plugin directory from the pre-deploy tar.gz.
3. `wp sg purge` again.
4. Re-run the fatal-error check (`wp eval 'echo "ok";'`) and re-verify
   `wp theme list --status=active` / `wp plugin list` show the prior
   versions.

No backup was created this session — production access was required to
stay strictly read-only throughout, and creating one now (before Andrew
has decided whether/when to deploy) would be a write action outside this
session's authorization. This is the exact procedure to run immediately
before the actual deploy.

## 9. Post-deploy validation checklist (for whenever a deploy is approved)

1. `wp theme list --status=active` and `wp plugin list` show the new
   versions.
2. `wp eval 'echo "ok";' --user=1` on production — no fatal error.
3. Run `tests/test-coupon-ui-restoration.php`,
   `tests/test-analytics-phase1b.php`, `tests/test-cta-engine.php`, and
   the rest of the theme/plugin test suites against production via
   `wp eval-file` (all currently pass on staging; see Workstream 10's full
   run this session).
4. `wp sg purge`.
5. Logged-out browser smoke test on production: homepage, one product
   page, cart (coupon panel renders styled), checkout (redirects to cart
   only if empty, as expected), `/teachers/` (teacher popup, not parent
   popup), no console errors, no PII in page content, [PARENT_COUPON_CODE_SUPERSEDED] untouched.
6. Confirm GTM/GA4 remain uninstalled on production (this deploy does not
   change that — the GTM container is still mid-build and unpublished).

## 10. Go/No-Go

**No-Go — awaiting Andrew.** Everything above is staged, tested, and
documented. Nothing in this diff is blocked on unfinished work *except*
that Andrew has not reviewed or approved a production deploy this session,
and per explicit instruction this session does not deploy or ask to
deploy — it only prepares this record. When Andrew is ready, the concrete
next actions are: (1) decide whether to ship all three phases together or
in smaller slices, (2) approve in this chat, current turn, (3) run the
backup step in §8, then the deploy, then the checklist in §9.
