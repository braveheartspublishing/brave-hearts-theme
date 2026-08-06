# Production Consent Infrastructure Deployment — Executed

**Status: LIVE on production, 2026-07-13.** Andrew's explicit approval covered exactly this scope — see the approval message referenced in `DECISIONS.md`. This document records what was actually done; see `RELEASES/PRODUCTION_GTM_CONSENT_READINESS_AUDIT.md` for the pre-deployment planning/dependency analysis this executed.

## What is now live on production
1. **6 new theme files** in `inc/`: `class-bhp-analytics-config.php`, `class-bhp-consent.php`, `class-bhp-gtm-loader.php`, `class-bhp-utm-attribution.php`, `class-bhp-analytics-debug.php`, `class-bhp-wpconsent-bridge.php` — byte-identical to the verified repo/staging versions (checksums matched exactly pre- and post-deploy).
2. **`functions.php`**: one narrow 6-line `require_once` addition (plus a comment header), inserted immediately after the existing CTA Engine require block, before "EXPLORER PASSPORT FOUNDATION." No existing line was modified. Diffed against a pristine pre-deploy backup to confirm this was the only change.
3. **WPConsent Free v1.1.7** installed from the official WordPress.org package and activated. `wpconsent_settings` configured identically to the verified staging configuration (banner + floating reopen enabled, `google_consent_mode` disabled, matching button labels).

## What remains deliberately OFF
- `bhp_gtm_container_id` — **not set** (does not exist as an option). `BHP_GTM_Loader::render_head_snippet()` verified to print 0 bytes with this unset — confirmed both by direct `wp eval` test and by every browser QA scenario (`gtmScriptCount: 0` in every state: no-choice, Accept, Reject, and after a preference change).
- `bhp_ga4_measurement_id` — not set.
- `bhp_consent_decision_approved` — not set (defaults `false`). This is the business gate; approving the file/plugin deployment did not touch it, per Andrew's explicit instruction and per `BHP_Analytics_Config`'s own design (these are independent decisions).
- GTM workspace — untouched, unpublished, unsubmitted (0/0, same 27/39/40/108 state as before).

**Neither `bhp_gtm_container_id` nor `bhp_ga4_measurement_id` is required for the consent banner to function** — verified via architecture (WPConsent's banner UI and `BHP_WPConsent_Bridge`'s cookie-write are entirely independent of `BHP_GTM_Loader`) and confirmed live (banner and bridge work correctly on production with both options absent). Per Andrew's instruction, they were left unset without needing to stop and ask.

## Pre-deployment state (captured, timestamped)
- Local backup directory: `backups/production-consent-deploy-20260713-021306/` (outside git, machine-local).
- `functions.php.pre-deploy-backup` — pristine copy of production's `functions.php` before any change, md5 `130059ac29a8abfbc94ae5d380ac4d89`.
- `pre-deploy-state-snapshot.txt` — full plugin list, theme list, and confirmation all 4 consent/GTM options and all 6 target files were absent, captured via SSH/WP-CLI immediately before deployment.
- Confirmed via `git fetch` that the 4 WPConsent commits (`91bee97`, `bbf0413`, `b6bf20d`, `e2f176f`) plus the readiness-audit commit (`09501b3`) are present on `origin/feature/production-integration-1.17.1`.

## Deployment method
Snapshot-based, per the established runbook: production's own live `functions.php` was downloaded, patched with the exact 6-line block (verified via `diff` against the pristine backup to show only that one insertion), lint-tested (`php -l`) in a temp location, then moved into place. The 6 new `inc/` files were uploaded to a temp location, lint-tested individually, checksum-verified against the approved repo/staging versions, then copied into place. No theme-ZIP, no `wp theme install --force` — this was a targeted 7-file addition (6 new files + 1 patched file), not a full-theme replacement.

## Verification after file deployment
- `wp eval 'echo "ok";'` — clean, no fatal.
- All 6 classes (`class_exists()`) load correctly.
- All 7 file checksums (6 new files + `functions.php`) matched the approved versions exactly.
- `BHP_GTM_Loader::render_head_snippet()` direct test: 0-byte output (confirms inert with no container ID).

## WPConsent installation
- `wp plugin install wpconsent-cookies-banner-privacy-suite --activate` — official WordPress.org source, v1.1.7, no account, no trial, no billing.
- Settings copied verbatim from staging's verified `wpconsent_settings` option (banner text, button labels, `google_consent_mode: 0`, floating button enabled).
- `wp sg purge` run immediately after (customer-facing change).
- One pre-existing, unrelated PHP warning (`Bookvault.php` "Undefined array key 'destination'") appeared in the install output and recurred in the error log at expected intervals — confirmed via `php_errorlog` review to be a long-standing, unrelated Bookvault plugin issue, not something this deployment introduced.

## Production QA results (fresh logged-out browser sessions, real production URL)

| # | Scenario | Result |
|---|---|---|
| 1 | First visit, no prior choice | Banner visible, 3 buttons + close, correct labels, no cookies, `gtmScriptCount: 0` |
| 2 | Accept All | `bhp_consent_state` all 4 signals granted, banner hides, `gtmScriptCount: 0` (business gate closed) |
| 3 | Reject Nonessential | All 4 signals denied, `gtmScriptCount: 0` |
| 4 | Manage Preferences | Modal opens via floating reopen button, functions correctly |
| 5 | Reject → Accept | Floating button reopens modal, Accept All updates cookie to all-granted correctly |
| 6 | Accept → Reject | Verified via the initial Accept-then-Reject sequence (scenario 2→3) — cookie updates correctly each direction |
| 7 | Return after acceptance | Cookie persists across reload, banner stays hidden, GTM still 0 |
| 8 | Return after rejection | Cookie persists (denied), banner stays hidden, GTM still 0 |
| 9 | Clear storage and revisit | Banner reappears fresh, no cookies, `gtmScriptCount: 0` |
| 10 | Desktop | Clean at native viewport |
| 11 | Tablet (~733px effective) | No overflow, all 3 buttons + close visible |
| 12 | Mobile (~475px effective) | No overflow, all 3 buttons + close visible |
| 13 | Keyboard Tab | First Tab lands on banner message div (same as staging), real key event |
| 14 | Escape in preferences modal | Modal closes correctly on real Escape keypress |
| 15 | 200% zoom | No overflow, all controls real non-zero dimensions |
| 16 | Homepage | Clean, no console errors |
| 17 | Blog article | Clean, no console errors |
| 18 | Product page | Clean, Add to Cart functional, no console errors |
| 19 | Complete Collection | Clean, no console errors |
| 20 | Cart | Item added/removed correctly, totals correct ($22.06 observed with tax/shipping), no interference |
| 21 | Checkout | Renders normally (contact info, shipping address, country selector); **no order placed** |
| 22 | Adventure Kit form rendering | Parent popup renders and fires its own `parent_popup_view` event correctly (pre-existing, unaffected); not submitted |
| 23 | Teacher form rendering | `/teachers/` renders cleanly, no console errors; not submitted |
| 24 | CTA links | Header "Get the Complete Collection" CTA renders with correct href on Teacher page |
| 25 | Google Listings & Ads behavior | Plugin remains `active` (confirmed via `wp plugin list`); its own pre-existing Google Ads conversion pixel (`AW-18315643536`, region-scoped EU consent-default) continues firing exactly as before deployment — untouched, independent system |

**Not directly re-tested this pass** (already exhaustively verified with the identical code/plugin/settings on staging the same day): the full focus-trap cycle inside the Manage Preferences modal, and true native Enter/Space keyboard button activation (same tooling limitation as staging — inferred correct via HTML-spec button semantics and confirmed `.click()`-equivalence, not directly observed).

## UTM attribution behavior
`BHP_UTM_Attribution` loaded without error (confirmed via `class_exists()` and the absence of any new PHP warnings/errors). Its `woocommerce_checkout_order_processed` / `woocommerce_store_api_checkout_order_processed` hooks did not fire during this QA pass because no order was placed, per instruction — its actual order-meta-write behavior (`_bhp_attribution_first_touch` / `_bhp_attribution_last_touch`) was not exercised live on production, consistent with "do not place an order." Checkout page rendering itself (contact info, shipping fields, totals) was unaffected by this class's presence.

## Errors
None. `wp eval 'echo "ok";'` clean before and after every change. Zero browser console errors across all pages tested. `php_errorlog` reviewed — only pre-existing, unrelated warnings (Bookvault plugin, Jetpack cron) at their normal recurrence, nothing from the new files.

## Rollback (prepared, not needed)
- Pristine `functions.php` backup preserved at `backups/production-consent-deploy-20260713-021306/functions.php.pre-deploy-backup` (md5 `130059ac29a8abfbc94ae5d380ac4d89`).
- Rollback procedure: delete the 6 new `inc/` files, restore `functions.php` from the backup, `wp plugin deactivate wpconsent-cookies-banner-privacy-suite` (falls back to `BHP_Consent`'s pre-existing fail-closed default automatically — fails closed, not open), `wp sg purge`.
- Not executed — no trigger condition (PHP fatal, broken banner, checkout obstruction, form regression, unexpected GTM load, unrelated drift) occurred.

## Confirmations
- GTM was not submitted or published — workspace unchanged (27/39/40/108/0/0).
- `bhp_consent_decision_approved` remains unset/`false`.
- `bhp_gtm_container_id` and `bhp_ga4_measurement_id` remain unset.
- No unrelated system was changed: WooCommerce, Mailchimp, [PARENT_COUPON_CODE_SUPERSEDED], pricing, payments, CTA Engine, header, and all other plugins/options were not touched. Google Listings & Ads plugin confirmed unaffected and still active.
