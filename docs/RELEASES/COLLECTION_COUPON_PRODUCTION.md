# Release Record — [PARENT_COUPON_CODE_SUPERSEDED] Coupon (Production)

> **RENAMED 2026-08-05 under Andrew Signore's explicit approval.** This file's previous
> filename contained a superseded audience-coupon code and is therefore deliberately NOT
> reproduced here. The rename was performed with `git mv`, so the full history is intact and
> `git log --follow` on this path reaches every earlier revision. All 6 references to the old
> paths elsewhere in `docs/` were updated in the same commit. The coupon this record describes
> was set to **draft** on 2026-08-05 and can no longer be applied.

**Status:** Live on production since 2026-07-11. Plugin `brave-hearts-bundle-pricing` v1.7.1.

## Behavior
`[PARENT_COUPON_CODE_SUPERSEDED]` (case-insensitive) gives an additional 10% off, but only when the cart is a genuine Paperback or Hardcover Complete Collection (all 3 titles, single format, no extras). Any other cart composition throws and the coupon is rejected, or auto-removed if the cart changes after application. Stacks on top of the existing non-coupon "Bundle Savings" fee.

## What shipped
4 production-native patch files: `brave-hearts-bundle-pricing.php` (version bump), `includes/bundle-cart.php` (the [PARENT_COUPON_CODE_SUPERSEDED] logic), `includes/bundle-landing-page.php` (2-line coupon-scope note), `assets/bundle-landing.css` (1-line styling addition).

## Verification
18-scenario QA matrix, all passed, on a genuinely logged-out production browser session. No completed paid order was placed during QA.

## Rollback
`*.rollback-[PARENT_COUPON_CODE_SUPERSEDED]-20260711-231846` (same directory as each live file).

## Full detail
`docs/COLLECTION_COUPON_PRODUCTION_DEPLOYMENT_2026-07-11.md`.
