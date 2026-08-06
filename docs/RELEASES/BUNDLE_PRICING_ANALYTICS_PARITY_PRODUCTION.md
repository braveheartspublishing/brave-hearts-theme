# Bundle-Pricing Analytics Parity — Production Deployment (2026-07-13)

Isolated production release closing the Critical plugin-staleness gap found in `RELEASES/PHASE10_PRODUCTION_ANALYTICS_VALIDATION.md`. Andrew directed a targeted, analytics-only deployment rather than the full v1.8.2 plugin, since the full version also contains ~1,300 lines of unrelated, unreleased KPI/economics-dashboard work.

## What changed

Production's `brave-hearts-bundle-pricing` plugin was v1.7.1 (dated 2026-07-06, pre-Phase-1B). Repo/staging are v1.8.2. Diffing every file between production's live v1.7.1 and the repo found the changes fall into three buckets:

1. **Pure analytics (deployed)** — 7 files, isolated patch, detailed below.
2. **Unrelated dashboard/economics work (NOT deployed)** — `includes/dashboard/class-bhp-cost-config.php` (822 changed lines), `class-bhp-offer-classifier.php` (460 changed lines), `class-bhp-dashboard-page.php` (62 changed lines) — Phase1A-v2 KPI/economics dashboard rewrite, a separate feature never approved for this release. Left completely untouched; production's existing (older) dashboard keeps working exactly as before, since the plugin's own `bhp_bundle_pricing_load_dashboard_module()` architecture (unchanged, already precedented — see `DECISIONS.md`) safely no-ops on a missing/mismatched dashboard directory rather than fataling. Since the dashboard directory wasn't touched at all, this isn't even exercised — it's simply unaffected.
3. **One excluded UI line** — a "Have a coupon? You can add it at checkout." hint (`bundle-drawer.css`/`bundle-drawer.php`) — out of scope for an analytics-only fix, deliberately excluded from the patch at Andrew's direction.

## Isolated patch — 7 files

| File | Change |
|---|---|
| `brave-hearts-bundle-pricing.php` | Version bump only, 1.7.1 → 1.8.2 |
| `includes/bundle-analytics.php` | Full replacement (107→461 lines): adds `view_item_list` (shop loop + related products), `select_item` registry, `refund`; renames `product_viewed`→`view_item`, `purchase_completed`→`purchase`; adds GA4 ecommerce nesting throughout |
| `includes/bundle-drawer.php` | Hand-patched against production's pristine file: adds exactly 2 script-enqueue lines (`bhp-list-tracking.js`, `bhp-checkout-events.js`), verified via unified diff to contain nothing else — no coupon-hint markup, no other change |
| `assets/bundle-drawer.js` | Full replacement: adds `view_cart`, `remove_from_cart`; renames `checkout_started`→`begin_checkout`; adds ecommerce nesting to `add_to_cart`/`begin_checkout`/`bundle_add_to_cart` |
| `assets/bundle-landing.js` | Full replacement: adds the same ecommerce-nesting helper |
| `assets/bhp-list-tracking.js` | New file — `select_item` click tracking against the `window.bhpTrackedLists` registry |
| `assets/bhp-checkout-events.js` | New file — `add_shipping_info`/`add_payment_info`, checkout-page only |

`bundle-cart.php`, `bundle-data.php` (pricing/discount/catalog core), `bundle-landing-page.php`, `bundle-shop-series.php`, `bundle-shortcode.php`, and all CSS confirmed byte-identical to production's pre-deploy state — zero pricing, discount, shipping, or coupon logic touched.

## Verification performed before deploy

- Full unified diff of every shared file between production's pristine copy and the repo (line-ending-normalized) to classify every change.
- Staging's 13 applicable `tests/test-*.php` suites run fresh: all pass (`test-purchase-validation-harness.php` self-refuses outside its exact expected hostname — pre-existing test-guard behavior, unrelated to this patch, not fixed here).
- Package PHP-linted (`php -l`) and JS syntax-checked (`node --check`) — all clean.
- Patched `bundle-drawer.php` diffed against a freshly-repulled pristine copy of production's live file — confirmed the only difference is the 2 approved script-enqueue additions.
- All 7 files checksummed and compared against staging's own live copies (already running successfully) — 6 of 7 byte-identical; `bundle-drawer.php` intentionally differs by exactly the excluded coupon-hint line.
- Production backup captured and verified readable: `backups/bundle-pricing-v182-deploy-20260713-040112/brave-hearts-bundle-pricing-v1.7.1-pristine.tar.gz` (48 files).
- Pre-deploy commerce baseline captured live (Complete Collection pricing, [PARENT_COUPON_CODE_SUPERSEDED] messaging).

## Deployment

Targeted per-file copy to production's live plugin directory (not a destructive folder-replace — matches this plugin's established precedent of excluding/including specific subdirectories per release, see `DECISIONS.md` "safe optional-module loading architecture"). Immediately verified: `wp plugin get` reports 1.8.2, `wp eval 'echo "ok";'` succeeds (no PHP fatal), no new lines in `wp-content/debug.log`, cache purged.

## Post-deploy commerce regression QA — all pass, zero regressions

Individual paperback/hardcover add-to-cart · Complete Paperback Collection ($35.97 → -$3.98 fee → $31.99, matching pre-deploy baseline exactly) · [PARENT_COUPON_CODE_SUPERSEDED] valid application (-$3.20, 10% of collection price) · [PARENT_COUPON_CODE_SUPERSEDED] invalid rejection on non-Collection cart (400, correct message) · coupon remove/reapply (returns to exact pre-coupon state) · 2-book same-format tier (-$1.99) · mixed-format cart (paperback+hardcover) correctly gets **no** discount · cart-to-checkout persistence · checkout page loads with zero console errors.

## Post-deploy analytics revalidation — all pass

| Event | Before | After |
|---|---|---|
| `view_item_list` (shop, 6 items) | Never fired | Fires, full GA4 schema, ecommerce-nested |
| `view_item_list` (related products) | Never fired | Fires correctly |
| `select_item` | Never fired (file didn't exist) | Fires, matches clicked item |
| `view_item` | Fired as `product_viewed` (wrong name, flat) | Fires as `view_item`, ecommerce-nested |
| `add_to_cart` | Fired, but no ecommerce data | Fires, full ecommerce payload |
| `view_cart` | Never fired | Fires on drawer open, full payload |
| `begin_checkout` | Fired as `checkout_started` (wrong name) | Fires as `begin_checkout`, ecommerce-nested |
| `add_shipping_info` | Did not exist anywhere in codebase | Fires on checkout, `event_id`-deduplicated |
| `add_payment_info` | Did not exist anywhere in codebase | Fires on checkout, `event_id`-deduplicated |
| `bundle_add_to_cart` / `bundle_savings_applied` | Fired, old flat shape | Fire correctly alongside the new ecommerce-nested `add_to_cart` |
| `contextual_cta_click` | Fires, all attribution fields empty | **Unchanged** — `nav.js` is a theme file, out of scope for this plugin-only patch (still tracked in `KNOWN_ISSUES.md`) |
| `refund`, `purchase` | Old flat names/shapes | Code confirmed correct (renamed, ecommerce-nested) via file review; not live-tested (would require a real order, not authorized this session) |

## Not fixed by this release (tracked separately)

- `contextual_cta_click` full attribution (`cta_id`/`cta_placement`/etc.) — requires deploying current `assets/js/nav.js`, a theme file, not part of this plugin-scoped patch.
- GTM Preview/GA4 DebugView authenticated validation — still pending Andrew's Google sign-in.
- GTM publish / `bhp_consent_decision_approved` — still an explicit, separate business decision, now genuinely unblocked from the analytics-correctness side.

See `KNOWN_ISSUES.md` for updated status and `NEXT_TASK.md` for what comes next.
