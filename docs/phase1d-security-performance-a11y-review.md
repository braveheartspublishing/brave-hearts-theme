# Phase 1D — Security / Performance / Accessibility Review

Covers every new/touched file in this phase: `inc/class-bhp-content-classification.php`,
`inc/class-bhp-cta-engine.php`, `inc/class-bhp-campaign-landing.php`,
`inc/class-bhp-conversion-scoring.php`, `functions.php` (Adventure Kit
cross-sell hook), `assets/js/nav.js`, `template-parts/guides/article-card.php`
and `related-content.php`, `template-parts/acquisition/signup-form.php`,
`template-parts/components/final-cta.php`, `footer.php`,
`plugins/brave-hearts-bundle-pricing/includes/dashboard/class-bhp-dashboard-page.php`,
and `content-engine/scripts/*.php`.

## Security

- **Nonces/CSRF**: `BHP_Content_Classification::save()` (unchanged this
  phase, already nonce-protected from its original build) is the only
  write path among the new code. Every other new class (`BHP_CTA_Engine`,
  `BHP_Campaign_Landing`, `BHP_Conversion_Scoring`) is read-only —
  nothing in Phase 1D writes to the database from a public request.
- **Capability checks**: the new dashboard panel renders inside
  `BHP_Dashboard_Page::render()`, already gated on `manage_woocommerce`
  before any new code runs. No new admin entry point was added.
- **Sanitization/escaping**: every dynamic value in every new
  `get_template_part()` call is escaped at render (`esc_html`, `esc_url`,
  `esc_attr`, `sanitize_key`, `sanitize_html_class`). The one place that
  renders a caller-supplied array of HTML attributes
  (`final-cta.php`'s `attrs` support) strips attribute names to
  `[a-zA-Z0-9-]` and `esc_attr()`s every value; the `attrs` array is
  never derived from `$_GET`/`$_POST`/`$_REQUEST` at any call site added
  this phase (`BHP_CTA_Engine::render()` and
  `BHP_Campaign_Landing`'s product block both build it from fixed,
  hardcoded keys) — there is no request-input-to-markup path to attack.
- **SQL**: no new code touches `$wpdb` directly. All queries go through
  `get_posts()`/`WP_Query` with typed, bounded parameters.
- **REST/AJAX**: no new REST route or AJAX handler was added this phase.
- **Secrets/log redaction**: no credential, key, or token is referenced
  anywhere in Phase 1D code, tests, or docs.
- **XSS via the CTA registry**: every `resolve_url()` callback in
  `BHP_CTA_Engine::registry()` returns either a literal `home_url()`
  string or the output of an existing, already-reviewed helper
  (`bhp_get_series_adventures()`, `bhp_get_amazon_affiliate_url()`) —
  never raw request input.
- **Content-engine scripts** (`content-engine/scripts/*.php`) are
  WP-CLI/plain-PHP-CLI only, not registered as any web-accessible route
  — no public attack surface.

## Performance

- **No unbounded queries**: `BHP_Content_Classification::coverage_stats()`
  caps `posts_per_page` at a finite default (500) rather than `-1`, and
  the dashboard panel wraps it in the existing `BHP_KPI_Cache::get()`
  mechanism so it is computed once per cache window, not once per page
  load.
- **No repeated metadata queries**: `BHP_CTA_Engine::select_cta()` calls
  `get_classification()` once per invocation (already memoized nowhere,
  but each call is a handful of `get_post_meta()` reads, not a query) —
  consistent with the existing classification system's own performance
  profile from Workstream 2.
- **No sitewide asset loading**: no new CSS/JS file was enqueued
  sitewide. `nav.js`'s additions (the `data-bhp-focus-event` listener,
  the shared payload builder) extend the same file that already loads
  everywhere — no new HTTP request.
- **No layout shift**: all new CTA/trust-row markup renders through the
  existing `final-cta.php`/`teacher-resources-cta.php` templates and
  their existing CSS classes — no new unstyled markup.
- **No duplicate observers**: `nav.js`'s `data-bhp-focus-event` listeners
  use native `{ once: true }`, which auto-removes itself after firing —
  no manual bookkeeping, no leaked listeners across page navigations
  (each page load re-queries the DOM fresh).
- **Cache compatibility**: no new code writes a per-visitor cookie or
  session value that would fragment page caching. The two dashboard
  reads are admin-only (never cached by a page cache) and already
  wrapped in `BHP_KPI_Cache`.
- **Checkout impact**: nothing in this phase touches
  cart/checkout/Store-API code paths.
- **Admin report performance**: the new dashboard panel adds 2 cached
  reads to an existing admin-only page; no new option/meta read runs on
  every frontend page load.

## Accessibility

- **Semantic structure**: `BHP_Campaign_Landing::render()`'s wrapper is
  a plain `<div>` (not an interactive element) with real `<h2>`/`<h3>`
  headings inside its blocks, matching the existing heading hierarchy
  pattern used by `final-cta.php` and `teacher-resources-cta.php`.
  `render_benefits_block()`'s list uses a real `<ul>`/`<li>`, not styled
  `<div>`s.
- **Field labels**: no new form field was introduced. The
  `lead_form_start` focus-tracking attribute is added to the EXISTING,
  already-labeled email `<input>` in `signup-form.php` — no new
  unlabeled input.
- **Link/button purpose**: every new CTA link renders through
  `final-cta.php`, which already requires both a URL and a label before
  rendering anything (`if ($primary['url'] && $primary['label'])`) —
  never an empty or icon-only link.
- **Focus states / keyboard access**: no new custom-focusable element
  was introduced; all new interactive elements are standard `<a>`/
  `<input>` elements that already have the site's existing focus-style
  CSS applied globally.
- **Screen-reader announcements**: no new live region was added; the
  one new impression/focus tracking mechanism is invisible to assistive
  technology by design (it only adds `data-bhp-*` attributes, which
  screen readers do not announce).
- **Error identification**: no new form validation was added; the
  existing `signup-form.php` error-feedback pattern is untouched.
- **Contrast**: no new color was introduced; all new markup inherits
  the existing design-system classes (`.btn`, `.bhp-trust-badge`,
  `.text-section-title`, etc.).
- **Touch targets / reduced motion**: no new custom-sized clickable
  element or animation was introduced.
- **Known gap, not fixed this phase**: the sample conversion-scoring run
  found at least one existing blog-post image missing meaningful `alt`
  text (see `docs/phase1d-conversion-scoring-sample-run.md`) — flagged
  for a future, deliberate accessibility pass rather than silently
  bulk-edited here.
