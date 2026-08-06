# Coupon-Entry Restoration — Objective 3 (2026-07-09)

## Root cause

Customers could not find a coupon field on production, but the coupon field was
never actually missing from the page markup. Both the Cart page (post ID 7) and
Checkout page (post ID 8) are WooCommerce **Blocks** pages, and WooCommerce
Blocks' own native `wc-block-components-totals-coupon-form-block` — the
"Add coupons" toggle, code input, and Apply button — was present in both pages'
serialized block content on staging *and* production from the start.

The defect was **discoverability, not absence**: WooCommerce Blocks' default
styling renders the toggle as a plain, unstyled text line with a small chevron,
wedged between the cart line items and the totals breakdown, using generic gray
text with no border, background, or icon color. It blended into the page
background and looked like a section label rather than an interactive,
discount-bearing control. Confirmed by loading both pages in a real browser on
staging and production before any change: the element existed and worked when
clicked, but nothing about its appearance signaled "click here for a discount
code."

The theme had never added any CSS for `.wc-block-components-totals-coupon`
(confirmed via a full grep of `style.css`) — WooCommerce Blocks' own default
styling was the only thing rendering it, and that default is not designed to
stand out.

Two other things were ruled out during the investigation:
- `woocommerce_enable_coupons` is `yes` on both environments (`wp option get`).
- No PHP filter/hook anywhere in the theme or `brave-hearts-bundle-pricing`
  plugin disables, removes, or hides coupon UI (`woocommerce_coupons_enabled`,
  `remove_action.*coupon`, etc. — zero matches).

The side-cart drawer (`bundle-drawer.php` / `bundle-drawer.js`) has never had
any coupon-related code — it renders a simplified line-item summary and a
"Secure Checkout" button that goes straight to the real checkout page (which
has the fixed coupon field). It never linked to the Cart page at all.

## Staging implementation

Three files changed, no new plugin or duplicate coupon logic:

1. **`style.css`** (theme, v1.19.3 → v1.19.4) — new CSS block scoped tightly to
   `.wc-block-components-totals-coupon` (never the bare
   `.wc-block-components-panel` class it also carries, so no other WC Blocks
   panel is affected):
   - Boxed card treatment (border, background, radius) so the whole coupon
     section has visual weight instead of blending into the page.
   - Bold, theme-accent-colored toggle button with the existing gold accent
     icon color, full-width tap target, hover/focus-visible states.
   - The expanded form (`__form`, `__input`, `__button`) uses flexbox with
     `flex-wrap` so it never overflows narrow viewports; a `@media
     (max-width: 480px)` rule stacks the input and Apply button vertically at
     full width.

2. **`plugins/brave-hearts-bundle-pricing/includes/bundle-drawer.php`**
   (v1.8.0 → v1.8.1) — added one line of static text in the drawer footer:
   *"Have a coupon? You can add it at checkout."* directly above the existing
   "Secure Checkout" button. This is purely informational routing — no input
   field, no coupon logic, no new Store API calls. The drawer's own audit
   requirement ("don't add it if it creates fragile duplicate logic") is
   satisfied by not building a second coupon system; the fix routes the
   customer to the one real coupon field at checkout.

3. **`plugins/brave-hearts-bundle-pricing/assets/bundle-drawer.css`** — matching
   small, muted style for the new hint paragraph.

No WooCommerce coupon API, filter, or database field was touched. No coupon
values are hard-coded anywhere in the fix.

## Coupon codes tested (all created and deleted on staging only, never production)

| Code | Type | Config | Purpose |
|---|---|---|---|
| `BHPTEST-VALID10` | 10% off | no restrictions | valid-application path |
| `BHPTEST-EXPIRED10` | 10% off | expired 2026-07-08 | expired-coupon path |
| `BHPTEST-MINSPEND50` | $5 fixed cart | minimum spend $50 | minimum-spend-failure path |
| `BHPTEST-RESTRICT-EVEREST` | 15% off | restricted to product 17 (Mount Everest Hardcover) | product-restriction-failure path |

All 4 were deleted from staging (`wp wc shop_coupon delete`) after testing —
none remain in the database.

## Expected vs. actual results

| Scenario | Expected | Actual (observed) |
|---|---|---|
| Invalid code (`NOTAREALCODE123`) | Clear "does not exist" error, no discount | ✅ `Coupon "notarealcode123" cannot be applied because it does not exist.` |
| Expired coupon | Clear "expired" error, no discount | ✅ `Coupon "bhptest-expired10" has expired.` |
| Valid coupon, 1 paperback ($11.99) | 10% off = $1.20, total $10.79 | ✅ Discount -$1.20, total $10.79 |
| Coupon removal | Discount reverts, total returns to pre-coupon value | ✅ "removed from your cart" notice, total back to $11.99 |
| Minimum spend ($11.99 cart, $50 min coupon) | Blocked with exact threshold in the message | ✅ `The minimum spend for coupon "bhptest-minspend50" is $50.00.` |
| Product-restricted coupon on a cart without that product | Blocked | ✅ `Sorry, coupon "bhptest-restrict-everest" is not applicable to selected products.` |
| Two-book cart (Everest PB + Amazon PB, $23.98) + coupon | 10% off = $2.40; Bundle Savings fee suppressed (Phase 9 guard) | ✅ Discount -$2.40, no Bundle Savings line, total $21.58 |
| Complete Paperback Collection (3 books, $35.97) + coupon | 10% off = $3.60; bundle fee still suppressed | ✅ Discount -$3.60, no Bundle Savings line, total $32.37 |
| Complete Hardcover Collection (3 books, $53.97), no coupon | Native Bundle Savings (Hardcover) fee -$4.98 | ✅ Fee -$4.98, total $48.99 |
| Same hardcover cart + coupon | Fee suppressed, coupon discount (10%) instead | ✅ Discount -$5.40, fee absent, total $48.57 |
| Quantity change (1→2 on one line) with coupon applied | Subtotal and discount recalculate automatically | ✅ Subtotal $47.96 → discount -$4.80 (still exactly 10%), no reapply needed |
| Product removal with coupon applied | Subtotal and discount recalculate, coupon stays applied | ✅ Subtotal $35.97, discount -$3.60, coupon still present |
| Cart → checkout persistence | Coupon still applied on checkout's order summary | ✅ Same code, same discount, verified via Store API and visually |
| Page refresh on checkout | Coupon still applied after a full reload | ✅ Confirmed after `navigate` reload |
| Mobile layout | No horizontal overflow, both fields usable | ✅ Verified at ~500px width (see note below) |
| Guest checkout | Coupon UI/logic doesn't depend on login state | ⚠️ Inferred, not directly observed as a logged-out user (see note below) |

**Mobile note:** this automation environment has a known, previously-documented
limitation where the browser-resize tool cannot reliably force a true
sub-480px viewport (repeated attempts to reach 375/390/320px all settled at
~500px). At the achievable ~500px width the coupon panel rendered cleanly with
no overflow, and the CSS itself (verified by the automated test) contains an
explicit `@media (max-width: 480px)` rule that stacks the input and button to
full width. The true narrow-phone rendering was not visually observed this
session — if Andrew wants a literal phone-screen confirmation, a quick spot
check on a real device is the fastest way to close that gap.

**Guest-checkout note:** all testing this session was done as a logged-in
admin (the browser session used throughout this project). I did not log out
and repeat the flow as a guest. What I can state as directly verified:
WooCommerce Store API cart/coupon endpoints and the WC Blocks coupon
component do not branch on login state, and no code in this theme or the
`brave-hearts-bundle-pricing` plugin references `is_user_logged_in()` (or
similar) anywhere near coupon rendering or handling — confirmed by grep across
`style.css`, `bundle-drawer.php`, and the cart/checkout page block content.
This is strong, but inferred, evidence that guest behavior is identical; it is
not the same as having clicked through it as a guest.

## Bundle-savings stacking behavior

Preserved exactly as designed by the pre-existing Phase 9 safeguard in
`bundle-cart.php` (`if ( ! empty( $cart->get_applied_coupons() ) ) { return; }`)
— confirmed still present by the new automated test. Applying any coupon
makes the bundle-savings fee disappear from the cart/checkout totals entirely,
replaced by the coupon's own discount line. This was re-verified live for both
the paperback and hardcover complete sets: no scenario showed both a bundle
fee and a coupon discount at the same time, and the math in every case matched
`subtotal − discount = total` exactly, so nothing is double-counted.

## Analytics accuracy

Verified by code inspection, not by observing a live purchase event (no real
order was placed, per instruction). `bundle-analytics.php`'s `purchase` event
already reports:
- `value` = `$order->get_total() - $order->get_total_tax()` — this is the
  order's real, post-discount total (WooCommerce applies the coupon before
  `get_total()` is ever read), so coupon discounts are already correctly
  reflected and never double-subtracted.
- `coupon` = `implode(',', $order->get_coupon_codes()) ?: null` — the real
  applied coupon code(s) from the order, or `null` if none.

Neither line was changed by this fix; both already existed and are correct.

## Files/settings changed (staging only)

- `style.css` (theme root) — version 1.19.3 → 1.19.4
- `plugins/brave-hearts-bundle-pricing/brave-hearts-bundle-pricing.php` —
  version 1.8.0 → 1.8.1
- `plugins/brave-hearts-bundle-pricing/includes/bundle-drawer.php`
- `plugins/brave-hearts-bundle-pricing/assets/bundle-drawer.css`
- New test: `tests/test-coupon-ui-restoration.php`
- 4 temporary WooCommerce coupons created and deleted on staging (see table
  above) — none remain

No `wp_options`, WooCommerce settings, product data, shipping/tax
configuration, or Bookvault mapping was touched.

## Production deployment requirements

Not deployed. To ship this fix to production:
1. Andrew's explicit, current-turn approval (per this repo's standing rule).
2. Build a deploy ZIP from the commit containing these changes
   (`git archive`), `wp theme install --force` on production, copy the two
   changed plugin files.
3. Re-run `tests/test-coupon-ui-restoration.php` plus the full regression
   suite against production read-only checks (fatal-error check, version
   confirmation).
4. Purge SiteGround cache (`wp sg purge`).
5. Visual smoke test on production: confirm the boxed coupon toggle renders
   on the live Cart and Checkout pages, logged out, without applying a real
   coupon or placing an order.

## Regression coverage added

`tests/test-coupon-ui-restoration.php` (12 assertions, all passing) guards:
- The CSS selector scoping (fails if `.wc-block-components-totals-coupon`
  styling disappears, or if a future edit accidentally targets the bare
  `.wc-block-components-panel` class).
- The drawer's routing hint text and the absence of a duplicate coupon input.
- `wc_coupons_enabled()` stays `true` (native setting, not a custom flag).
- The bundle/coupon stacking guard in `bundle-cart.php` stays present.
- The purchase event's `coupon` field logic stays present in
  `bundle-analytics.php`.

Full existing regression suite (10 theme test files + 13 plugin test files,
23 total) re-run after these changes — all pass.
