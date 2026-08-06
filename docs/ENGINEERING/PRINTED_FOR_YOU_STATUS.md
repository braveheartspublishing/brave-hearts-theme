# "Printed Just for You" Status

**Live on production as of 2026-07-13 (theme v1.19.13). Staging and production are at parity for this component.**

## Objective
End-to-end WooCommerce -> Bookvault testing confirmed the automation, discounts, taxes, and margins all work correctly, and real orders arrive in roughly 8 days — but customers had no on-site indication their book is printed to order. This component proactively and reassuringly sets that expectation without adding friction to the purchase flow.

## Approved copy (single source of truth: `bhp_get_printed_for_you_data()`)
**Revised 2026-07-13 (copy-revision sprint) — this is the current live copy, superseding the original build-sprint copy below.**
> ### Printed Just for You
> **Good things take time.**
> Every Brave Hearts book is **printed especially for you** after your order is placed.
> This helps us reduce waste, maintain exceptional quality, and continue publishing independently.
> **Most orders arrive within 1–2 weeks, and many arrive even sooner.**
> Thank you for supporting independent publishing.

Bold emphasis on the tagline and the first/third paragraphs is real markup (`<strong>`), rendered via `wp_kses($text, ['strong' => []])` in the partial — not `esc_html()`, which would print the tags as literal text. `title` and `thanks` carry no emphasis and stay on plain `esc_html()`.

*Original build-sprint copy (superseded, kept for history):* "Good things take time. Every Brave Hearts book is printed especially for you after your order is placed. This helps us reduce waste, maintain quality, and publish independently. Most orders arrive within 1–2 weeks, although many arrive sooner depending on production and shipping. Thank you for supporting independent publishing."

Never hardcode this text anywhere else — every placement calls `bhp_render_printed_for_you_notice()`, which reads it from that one function.

## Architecture
Mirrors the existing Kirkus/Amazon-review components deliberately, not a new pattern:
- `inc/class-bhp-printed-for-you.php` — data function, render function, all placement wiring (hooks + shortcode), self-contained.
- `template-parts/components/printed-for-you-notice.php` — the actual markup partial.
- `style.css` — `.bhp-printed-for-you` and children, added after the Kirkus CSS block. Theme version bumped 1.19.12 -> 1.19.13 to bust cached CSS on already-loaded browsers.

## Placements
| Placement | Mechanism | Details |
|---|---|---|
| Single product page | `add_action('woocommerce_single_product_summary', ..., 37)` | Between the existing "What Kids Will Learn" block (36) and teacher/shipping links (38) |
| Order Received / Thank You | `add_action('woocommerce_thankyou', ..., 25)` | Confirmed this hook fires reliably even with Blocks checkout (the pre-existing `bhp_order_confirmation_expedition_links` callback already relies on it) |
| Cart page | `[bhp_printed_for_you context="cart"]` shortcode, embedded once in the Cart page's own block content | Inserted as a sibling block inside `filled-cart-block`, after `cart-totals-block` — does not appear on the empty-cart state |
| Checkout page | `[bhp_printed_for_you context="checkout"]` shortcode, embedded once in the Checkout page's own block content | Replaces (consolidates) a pre-existing ad-hoc "Books are printed on demand. See our Shipping Policy..." paragraph that already lived in the Checkout block's "Additional information" slot |

**Why a shortcode for Cart/Checkout, not a PHP hook:** this site's Cart and Checkout pages are built from the real WooCommerce Blocks (confirmed via live inspection — literal `<!-- wp:woocommerce/cart -->` / `<!-- wp:woocommerce/checkout -->` block markup in each page's `post_content`), not the classic shortcode templates. Classic action hooks (`woocommerce_before_cart`, `woocommerce_before_checkout_form`, `woocommerce_checkout_before_order_review`, etc.) do not fire on either page — confirmed by finding an existing, likely-dead precedent in `brave-hearts-bundle-pricing/includes/bundle-cart.php` that registers exactly these hooks for bundle progress messaging, which the plugin's own `bundle-drawer.php` comment explains was actually solved client-side via the Store API instead, for the same reason. A shortcode embedded once in each page's content is the only proven mechanism — and it still routes through the single shared render function, so nothing is hardcoded per page.

## QA performed (staging, real browser, 2026-07-13)
- Product page (Mount Everest paperback): correct copy, correct position, no console errors, no horizontal overflow at desktop (1280px) and mobile (~471px effective width — this environment's viewport-resize tool is imprecise, confirmed via `window.innerWidth`).
- Cart page: added a real product to cart, confirmed the notice renders below the order summary/checkout button, no console errors, no overflow. Confirmed it does *not* render on the empty-cart state (correctly scoped to the filled-cart block only).
- Checkout page: confirmed the notice renders in the consolidated "Additional information" slot, no console errors, no overflow, and that checkout's functional elements (email field, place-order button, totals block) are unaffected.
- Thank You page: a real order was not placed (per the standing "no real payment" testing rule). Verified instead via direct function call (`bhp_render_printed_for_you_notice('thankyou')`) that the render path produces valid, correctly-classed output, and via `has_action()` that the hook is registered at priority 25.
- Confirmed via `has_action()`/shortcode-registry checks that all four placements are wired at their intended priorities.
- Test cart item removed after QA.
- Zero PHP fatals throughout (`wp eval 'echo "ok";'` clean after every file change).

## Copy revision + production deployment (2026-07-13)
Andrew approved the revised copy above and isolated production deployment in the same session.

**Staging QA (revised copy):** all 5 placements verified in a real browser — product page (desktop 1440px, mobile ~471px effective width, tablet 768px, 200% zoom, keyboard/accessibility — no interactive elements inside the notice, icon `aria-hidden`), Cart (added a real product via Store API, verified notice + no console errors, removed after QA), Checkout (verified notice + email field/totals block unobstructed), Thank You (verified via safe render-path call, not a real order). Zero PHP fatals, zero console errors throughout. The only overflow observed at 200% zoom / narrow mobile width was pre-existing (header logo, nav-toggle, product zoom image) and confirmed unrelated to this component via `getBoundingClientRect()` — the notice itself never exceeded the viewport.

**Production preflight:** confirmed production had zero trace of the component (no `inc/class-bhp-printed-for-you.php`, no CSS, no require line, no shortcode in Cart/Checkout content) before deploying — matching the expected "prior version" state. Cart (post 7) and Checkout (post 8) page IDs matched staging exactly, and diffing production's content against staging's showed only the isolated shortcode-insertion diff, with no other unrelated drift to reconcile.

**Production deployment:** narrow, isolated patch — the 2 new component files (`inc/class-bhp-printed-for-you.php`, `template-parts/components/printed-for-you-notice.php`), one `require_once` line in `functions.php` (inserted immediately after the existing `amazon-reviews.php` require), the CSS block appended to `style.css` + version bump 1.19.12 → 1.19.13, and the Cart/Checkout page-content shortcode insertions (byte-identical to the already-QA'd staging content). All required CSS custom properties (`--space-4`, `--color-forest`, etc.) already existed on production. Backups of all 4 touched targets captured to `C:\BHP\private-backups\printed-for-you-production-deploy-20260713-200232\` before any change.

**Production QA:** all 5 placements re-verified live on `braveheartspublishing.com` — exact copy, correct emphasis, single notice per page, no PHP fatals, no console errors, CSS confirmed loading at `?ver=1.19.13`. Commerce regression checks: cart totals correct, [PARENT_COUPON_CODE_SUPERSEDED] correctly rejected a single-item cart (documented Collection-only behavior, unchanged), shipping resolved to the pre-existing `brave-hearts-bundle-pricing` tiered rate ($1.99 for a single paperback — confirmed via `bundle-data.php:81`, not a $3.99 regression as initially suspected, and entirely unrelated to this deployment), no analytics/consent/GTM settings changed. Test cart item added and removed via Store API; no real order placed.

**Rollback:** if needed, restore from `C:\BHP\private-backups\printed-for-you-production-deploy-20260713-200232\` (`functions.php.bak`, `style.css.bak`, `cart-page-content.bak`, `checkout-page-content.bak`) via `scp`/`wp post update`, and delete the two new component files.

## Not yet done — explicitly out of scope
- **Order emails.** See "Email recommendation" below — documented only, not implemented.
- **No analytics changes, no checkout logic changes** — every sprint in this component's history has touched only its own files, the one `require_once` line in `functions.php`, the CSS block (+ version bump) in `style.css`, and the Cart/Checkout page-content edits described above.

## Email recommendation (documented only — do not implement yet)
Recommend adding a short version of this messaging to the WooCommerce "Processing order" / order-confirmation email, since that email is the other place a customer's expectations get set immediately after purchase (the Thank You page notice covers the same moment on-site, but not everyone reads it, and the order confirmation email is opened at a higher rate for delivery-related information). Suggested approach if approved: a compact 2-line variant (title + "Most orders arrive within 1–2 weeks...") via WooCommerce's native email template hooks (`woocommerce_email_before_order_table` or `woocommerce_email_order_details`), reusing `bhp_get_printed_for_you_data()` for the copy so it never drifts from the on-site version — but email HTML/CSS has different rendering constraints (many clients strip `<style>` blocks, need inline styles) than the on-site component, so this should be scoped as its own small task, not treated as a drop-in reuse of the existing partial. Not implemented this sprint per the explicit instruction to document only.
