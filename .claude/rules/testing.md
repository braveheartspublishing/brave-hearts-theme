# Testing Requirements

Before considering any checkout/funnel/schema change done:

- **PHP**: no fatal errors — a quick `wp eval 'echo "ok";' --user=1` after
  deploy will surface a syntax/fatal error immediately (WP-CLI will error
  instead of printing "ok").
- **JavaScript**: check browser console for errors on the affected pages
  (checkout, popup pages) — a passing PHP deploy doesn't guarantee clean
  JS.
- **Desktop and mobile**: popup timing/scroll triggers and checkout layout
  should be spot-checked at both viewport sizes. Note: some browser
  automation viewport-resize tools don't reliably change the actual
  rendered viewport — verify with `window.innerWidth` via JS eval if a
  resize doesn't visibly change layout.
- **Cart and checkout**: WooCommerce Blocks (React/Store-API) — `curl`
  only proves the page shell loads, not the actual cart contents or
  shipping rates. Use a real browser, and wait 2-3 seconds after any
  address/quantity change for the Store API to recalculate before reading
  the DOM.
- **Funnels**: verify popup dismissal/signup on one funnel doesn't affect
  the other's storage state (check `localStorage` keys directly via JS
  eval if needed).
- **Schema**: inspect the actual rendered `<script class="rank-math-schema">`
  block, not just the filter code.
- Always test on staging first; only test on production for read-only
  verification after an approved deploy.
- Clean up any test cart items after verification — this browser session's
  WooCommerce cart persists across turns.
