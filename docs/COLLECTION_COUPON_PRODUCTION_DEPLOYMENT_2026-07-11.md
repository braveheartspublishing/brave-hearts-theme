# [PARENT_COUPON_CODE_SUPERSEDED] Collection-Only Coupon — Production Deployment Record

> **RENAMED 2026-08-05 under Andrew Signore's explicit approval.** This file's previous
> filename contained a superseded audience-coupon code and is therefore deliberately NOT
> reproduced here. The rename was performed with `git mv`, so the full history is intact and
> `git log --follow` on this path reaches every earlier revision. All 6 references to the old
> paths elsewhere in `docs/` were updated in the same commit. The coupon this record describes
> was set to **draft** on 2026-08-05 and can no longer be applied.

**Date:** 2026-07-11
**Approved by:** Andrew Signore (explicit, current-turn approval quoted in full in the session transcript)
**Plugin:** `brave-hearts-bundle-pricing`, version **1.7.0 → 1.7.1**
**Scope:** 4 production-native patch files only, deployed directly to production (staging had already passed the same 18-scenario QA matrix in a prior session).

## What changed

`[PARENT_COUPON_CODE_SUPERSEDED]` (case-insensitive) now gives an additional 10% off the cart total, but **only** when the cart contains a genuine Paperback or Hardcover Complete Collection — all 3 *Adventures of Charlotte and Henry* titles, single format, no extras. Any other cart composition throws a scoped exception and the coupon is rejected (or auto-removed if the cart changes after application, via WooCommerce's native `woocommerce_coupon_is_valid` re-check on recalculation).

The [PARENT_COUPON_CODE_SUPERSEDED] discount is a cart fee layered on top of the existing native non-coupon "Bundle Savings" fee — it does not replace it. The pre-existing production coupon (WC coupon ID 346, code `[PARENT_COUPON_CODE_SUPERSEDED]`) was not modified: it already had `individual_use: true`, so no code change was needed to make it reject stacking with a second coupon.

A one-line coupon-scope note was added to the `/complete-collection/` landing page only: *"Use [PARENT_COUPON_CODE_SUPERSEDED] for an additional 10% off the Paperback or Hardcover Complete Collection. Complete Collections only."*

## Files deployed

| File | Change |
|---|---|
| `brave-hearts-bundle-pricing.php` | Version bump 1.7.0 → 1.7.1 (docblock + `BHP_BUNDLE_PRICING_VERSION` constant) |
| `includes/bundle-cart.php` | Full [PARENT_COUPON_CODE_SUPERSEDED] patch: seven functions covering cart qualification, qualifying format, coupon identification, scope validation (hooked `woocommerce_coupon_is_valid`), native-discount zeroing (hooked `woocommerce_coupon_get_discount_amount`), the savings amount and the savings-fee action (hooked `woocommerce_cart_calculate_fees` priority 21). **Their original names embedded the superseded coupon code and are deliberately not reproduced here; they were renamed to the `bhp_audience_coupon_*` family in 1.8.4 and that family is what the current tree carries.** Plus a modified guard in the existing `bhp_bundle_apply_discount_fees()` allowing [PARENT_COUPON_CODE_SUPERSEDED] — and only [PARENT_COUPON_CODE_SUPERSEDED] — to coexist with the native Bundle Savings fee |
| `includes/bundle-landing-page.php` | 2-line addition: the coupon-note paragraph on the Complete Collection page template |
| `assets/bundle-landing.css` | 1-line addition: `.bhp-landing-panel__coupon-note` styling |

## Deployment procedure followed

1. Captured timestamped backups of all 4 live production files before touching anything: `*.rollback-[PARENT_COUPON_CODE_SUPERSEDED]-20260711-231846` (same directory as each live file).
2. Recorded pre-deployment checksums; confirmed production was still on plugin version 1.7.0 and the existing `[PARENT_COUPON_CODE_SUPERSEDED]` coupon was unchanged before deploying.
3. Deployed exactly the 4 files listed above — no other files touched, no complete plugin/theme redeploy.
4. Post-deployment: checksums matched the approved patch files; plugin confirmed active at version 1.7.1; `wp eval 'echo "ok";'` confirmed no PHP fatal; no debug.log or new error_log entries found; SiteGround dynamic cache purged ("Dynamic Cache Successfully Purged").
5. Full QA performed in a genuinely logged-out browser session (via `wp-login.php?action=logout` + the real WP confirmation page, not just cleared cookies).

## QA results — all 18 required scenarios, all passed

| # | Scenario | Result |
|---|---|---|
| 1 | Paperback Collection, no coupon | Subtotal $35.97, Bundle Savings −$3.98, price $31.99 — confirmed |
| 2 | Paperback Collection + [PARENT_COUPON_CODE_SUPERSEDED] | $28.79 product cost after both discounts, $34.51 total incl. $3.99 shipping + tax — confirmed |
| 3 | Hardcover Collection, no coupon | Subtotal $53.97, Bundle Savings −$4.98, price $48.99 — confirmed |
| 4 | Hardcover Collection + [PARENT_COUPON_CODE_SUPERSEDED] | $44.09 product cost, $51.73 total incl. $4.99 shipping + tax — confirmed |
| 5 | Individual paperback + [PARENT_COUPON_CODE_SUPERSEDED] | Rejected with scope message — confirmed |
| 6 | Individual hardcover + [PARENT_COUPON_CODE_SUPERSEDED] (uppercase `[PARENT_COUPON_CODE_SUPERSEDED]`) | Rejected; also proved uppercase code works — confirmed |
| 7 | Two-paperback bundle + [PARENT_COUPON_CODE_SUPERSEDED] | Rejected via dynamic auto-removal on cart-change recalculation — confirmed |
| 8 | Two-hardcover bundle + [PARENT_COUPON_CODE_SUPERSEDED] | Rejected via dynamic auto-removal — confirmed |
| 9 | Mixed-format cart + [PARENT_COUPON_CODE_SUPERSEDED] | Rejected — confirmed |
| 10 | Invalid three-book cart (2 paperback + 1 hardcover) + [PARENT_COUPON_CODE_SUPERSEDED] | Rejected with the exact scope message; Subtotal $41.97 / Shipping $4.99 / Tax $2.52 / Total $49.48 unchanged after the rejected attempt — confirmed |
| 11 | Lowercase/uppercase code both work | Confirmed (scenario 6 used uppercase) |
| 12 | Coupon removal restores original Collection price | Confirmed |
| 13 | Reapplication restores correct discounted price | Confirmed |
| 14 | Shipping remains $3.99 PB / $4.99 HC | Confirmed throughout (unaffected by coupon logic) |
| 15 | Tax recalculates correctly | Confirmed (Idaho Sales Tax line correct in every scenario) |
| 16 | Cart-to-checkout persistence | Confirmed |
| 17 | Another coupon cannot stack | Verified via read-only WP-CLI inspection of production coupon 346 (`individual_use: true`, unmodified by this deploy) rather than creating a new production test coupon — this is native, untouched WooCommerce behavior, already proven live on staging with the identical mechanism |
| 18 | Complete Collection page displays the approved coupon note | Confirmed after a hard reload (first check hit a stale cached tab, not a real defect) — exact copy match |
| 19 | Note does NOT appear on individual-book or two-book pages | Confirmed absent from an individual product page, the shop page, and the cart page — the note is scoped entirely to `bundle-landing-page.php`, which only renders on `/complete-collection/` |
| 20 | Existing non-coupon bundle behavior unchanged | Satisfied by scenarios 1 and 3 (native Bundle Savings fee unchanged) |

No completed paid order was placed. All test cart contents were removed from production after QA completed.

## Post-deployment state

- Plugin `brave-hearts-bundle-pricing`: **active, version 1.7.1**.
- Production theme (unrelated, unaffected by this deploy): `brave-hearts-theme-deploy-explorer-expedition-guides`, active.
- No new PHP fatals, warnings, or debug/error log entries since deployment.
- Mailchimp's "Coupon Email - [PARENT_COUPON_CODE_SUPERSEDED]" automation: confirmed **active and unchanged** (launched 2026-07-04, 2 contacts in progress).
- Production cart left empty (test items removed).

## Rollback path (if ever needed)

Restore the 4 files from their `*.rollback-[PARENT_COUPON_CODE_SUPERSEDED]-20260711-231846` backups in the plugin directory, confirm `wp plugin get brave-hearts-bundle-pricing --field=version` returns `1.7.0`, purge SiteGround cache, and re-verify no fatals.
