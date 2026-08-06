# Analytics Validation Procedures (Phase 1B)

## Running the tests

```
# Plugin (WooCommerce/order-side logic, list-tracking, purchase harness)
wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-analytics-events.php --user=1 --url=staging2.braveheartspublishing.com
wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-purchase-validation-harness.php --user=1 --url=staging2.braveheartspublishing.com

# Theme (config/consent/UTM/GTM loader/production consent gate)
wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-analytics-phase1b.php --user=1 --url=staging2.braveheartspublishing.com
wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-gtm-loader.php --user=1 --url=staging2.braveheartspublishing.com
```

**`--url=staging2.braveheartspublishing.com` is required** (Phase 1B
correction pass, 2026-07-06): `BHP_Analytics_Config::is_staging()` reads
`$_SERVER['HTTP_HOST']`, which is simply unset in a bare `wp eval-file`
CLI process (there is no real HTTP request). Without `--url`, every
staging-only test — including the purchase harness's own safety check —
sees an empty host, fails the `is_staging()` match, and fails
safe-as-production (the harness refuses to run at all; the phase1b
production-gate tests get the wrong environment). WP-CLI's `--url` flag
populates `$_SERVER['HTTP_HOST']` for the duration of the process, which
fixes this with no code change needed.

**`test-purchase-validation-harness.php` refuses to run anywhere but
staging** (checked first, before creating anything) — it creates and
force-deletes real, temporary `WC_Order` fixtures to exercise the actual
`bhp_bundle_track_purchase_completed()` code path end-to-end. Never run
it, and it will refuse to run itself, on production.

**Note for anyone re-running `test-gtm-loader.php`**: it runs via
`--user=1`, which authenticates as the site's real administrator for the
whole process — exactly the traffic `BHP_Analytics_Config` is designed to
exclude from tracking. The test simulates a logged-out visitor
(`wp_set_current_user(0)`) for the "does GTM actually render" assertions,
then restores the real user before testing the admin-exclusion path
itself. This isn't a bug in the code; it's how the test works around its
own execution environment.

Also run the full existing regression suite (11 other plugin tests, 3
other theme tests) to confirm no regression — all still pass, 15 test
files total (verified 2026-07-06, Phase 1B correction pass).

**A real bug was found and fixed via this test run** (2026-07-06):
`BHP_Analytics_Config::should_render_analytics()` was missing a check
against `BHP_Consent::analytics_allowed()` — the production consent-decision
gate being approved was not, by itself, sufficient to also require a real
per-visitor consent grant, contradicting this doc's own stated design
("with no consent banner AND no approved production gate, GTM will not
load in production at all"). Fixed in `inc/class-bhp-analytics-config.php`
by adding the missing check. `tests/test-gtm-loader.php`'s "configured ID
prints" scenario predated the consent gate work entirely and never
simulated gate-approved + consent-granted state, so it needed updating
alongside the fix (not weakening — it now simulates the fully-approved
state it always intended to test).

## Debug/validation mode walkthrough (no real GA4/GTM credentials required)

1. Log in as an administrator on **staging**.
2. Enable the validation override: `wp option update bhp_staging_analytics_override 1`.
3. Purge cache (`wp sg purge`).
4. Browse any storefront page while logged in — a small "Analytics
   Debug" toggle appears bottom-left. Click it to open the panel.
5. The panel header shows: current environment, configured GTM container
   ID (or "not configured"), current `analytics_storage`/`ad_storage`
   consent state, and whether tracking is enabled for this specific page
   load (correctly shows "no (blocked)" for your own admin session, even
   with the override on — this is the admin-exclusion rule working
   correctly, verified live 2026-07-06).
6. Every `dataLayer.push()` from that point on appears in the panel with
   its full payload — confirmed live for `view_item`, `add_to_cart`,
   `side_cart_opened`, `view_cart`, and `remove_from_cart` during a real
   browse → add-to-cart → remove flow, with zero console errors.
7. **When finished, disable the override**: `wp option delete bhp_staging_analytics_override`
   — staging must return to its tracking-disabled default so it can never
   silently contaminate anything afterward.

## What was verified live (2026-07-06, staging)

| Check | Result |
|---|---|
| `view_item` fires on product page load with full `items[]` schema | Confirmed via direct `dataLayer` inspection |
| `add_to_cart` fires on product-page add with `items[]`/`value`/`currency` | Confirmed |
| `side_cart_opened` + `view_cart` fire together when the drawer opens | Confirmed |
| `remove_from_cart` fires with the correct item data before removal | Confirmed |
| `view_item_list` fires on the Shop archive with `item_list_id`/`item_list_name`/per-item `index` | Confirmed (Phase 1B correction pass) |
| `select_item` fires on a real product-card click, matched via the href registry; does NOT fire for an unrelated click (Add to Cart button) | Confirmed both directions |
| `add_shipping_info` fires once a shipping rate is selected (real address entered, Store API `/cart` response observed), with deterministic `event_id`, correct `value` (tax-excluded), `shipping_tier`, and items | Confirmed live, including on a cold page reload with a persisted address |
| `add_payment_info` fires for an already-selected gateway on page load (no real card data submitted) | Confirmed live — **found and fixed a real bug first**: the original fixed 1500ms-after-DOMContentLoaded check sometimes ran before the payment section had mounted, silently losing the event; replaced with a `MutationObserver` in `bhp-checkout-events.js` that reacts whenever the radio actually appears, verified live afterward |
| No console errors across the full browse → list → select → shipping → payment flow | Confirmed |
| Executive KPI dashboard renders unchanged (provenance/economics untouched) | Confirmed — same figures before/after this phase's deploy |
| No PHP fatal errors anywhere in the deployed files | Confirmed (`php -l` + `wp eval`) |
| All new + all existing automated tests pass | Confirmed, 15 test files total (one real gate-logic bug found and fixed mid-session — see "Running the tests" above) |
| Test cart items removed after verification | Confirmed — cart returned to 0 items via the real UI remove control |
| Staging analytics override disabled after the session | Confirmed (`bhp_staging_analytics_override` deleted) |

## What could NOT be verified this phase (blocked on real credentials, or deferred)

- **GTM Preview mode** — no GTM container exists yet (Andrew confirmed
  neither GA4 nor GTM exists). Once a real container ID is configured,
  re-run this walkthrough using GTM's own Preview/Debug mode instead of
  (or alongside) the custom debug panel, and confirm actual
  `google-analytics.com`/`region1.google-analytics.com` network requests
  fire — dataLayer pushes alone are not proof of delivery.
- **GA4 DebugView** — same blocker.
- **A real purchase event on a live order** — deliberately not tested
  with a real payment in this phase (no explicit authorization was given
  for a controlled test transaction, and none was needed to validate the
  code paths above). The dedup/provenance logic was instead verified with
  the purchase validation harness (real, temporary, safely-deleted
  `WC_Order` fixtures exercising the real `bhp_bundle_track_purchase_completed()`
  code path) and with synthetic, unsaved `WC_Order` objects
  (`test-analytics-events.php`), matching the existing pattern used
  elsewhere in this codebase (`test-bookvault-fulfillment-eligibility.php`).
- **UTM first/last-touch capture for a real anonymous visitor** — the
  browser session used for this validation was logged in as a WordPress
  administrator and shares cookies across many other open tabs (including
  production tabs from other in-progress work); `BHP_Analytics_Config::should_render_analytics()`
  correctly excludes admin traffic from enqueuing `bhp-attribution.js` at
  all (confirmed by reading `functions.php`'s enqueue gate — this is the
  designed admin-exclusion behavior, not a defect). Logging out to test as
  a real visitor was judged too disruptive to the shared session and was
  not done. The underlying cookie read/parse/sanitize logic remains
  covered by `test-analytics-phase1b.php` section 6 (unchanged this
  phase). Recommend a follow-up live check in a dedicated/incognito
  session before the GTM container goes live.

## Manual validation checklist status

| Item | Status |
|---|---|
| Homepage/blog/shop page-view behavior | Not explicitly re-checked this phase (no new page-view event added there) |
| Product-list event | **Verified live** (Phase 1B correction pass — `view_item_list` on Shop archive) |
| Product-selection event | **Verified live** (Phase 1B correction pass — `select_item`, including negative case) |
| Product-page event | **Verified live** |
| Add-to-cart event | **Verified live** |
| Side-cart open | **Verified live** |
| Remove-from-cart event | **Verified live** |
| Cart view | **Verified live** (`view_cart`) |
| Checkout start | Verified via code inspection + test only — not click-tested live this phase |
| Shipping step event | **Verified live** (Phase 1B correction pass — `add_shipping_info`, real address, real Store API rate) |
| Payment step event | **Verified live** (Phase 1B correction pass — `add_payment_info`; a real timing bug was found and fixed during this verification) |
| Purchase deduplication logic | Verified via the purchase validation harness (real, temporary `WC_Order` fixtures) and via test with a synthetic order; still not verified with a real paid transaction (none placed, none authorized) |
| Production consent-decision gate | **Verified via test** — a real gap (missing consent-state check) was found and fixed this phase; all 28 assertions in `test-analytics-phase1b.php` pass after the fix |
| UTM persistence | Verified via test (cookie read/sanitize/idempotent write); not verified end-to-end through a real checkout in a browser this phase (see note above — admin-exclusion correctly blocked capture in this session's shared browser context) |
| Direct-visit preservation | Verified via test |
| Internal-test exclusion | Verified via test (order #351 confirmed excluded) |
| Consent denied / granted | Verified via test |
| Admin exclusion | **Verified live** (debug panel correctly showed "blocked" for the admin session; ecommerce `dataLayer` events still fire for admins by design since they are not consent/gate-dependent — only the GTM container print and the UTM attribution script are admin-excluded) |
| Staging isolation | Verified — staging tracking correctly defaults off, override correctly scoped, and was disabled again after this session's validation |

Screenshots from this session's live verification are available via the
browser tool outputs captured during the 2026-07-06 validation session
(product page with debug panel open, showing `view_item` and `view_cart`
payloads); no customer PII appears in any of them since none was
generated (synthetic/no real order was placed).
