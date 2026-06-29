# Production Readiness Audit

Audit date: June 29, 2026
Scope: Brave Hearts Publishing WordPress theme, Phase 8A

## Executive status

The theme is structurally ready for production once the manual WordPress, commerce, form-provider, legal-content, and infrastructure checks below are complete. No redesign, feature expansion, WooCommerce template override, or commit was made.

Theme-contained launch blockers found during this audit were corrected. A live WordPress installation was not available in this workspace, so HTTP responses, rendered page content, plugin configuration, checkout, and device/browser behavior still require staging verification.

## Templates audited

- front-page.php: component-provided H1, semantic sections, responsive images, product and content fallbacks.
- page-about.php: component-provided H1, heading sequence, founder-image state.
- page-books.php: component-provided H1, product grouping, missing-product states, paperback preference, CTAs.
- page-teachers.php: component-provided H1, placeholder resources, signup state, contact CTAs.
- page-contact.php: component-provided H1, inquiry links, labels, disabled state, direct-email fallback.
- page.php: now supplies the page-title H1 and a semantic article wrapper for standard pages.
- single.php: post-title H1, responsive featured image, post navigation.
- index.php: blog/archive H1, post-card H2 headings, pagination, empty state.
- 404.php: H1 and valid home CTA.
- header.php: title support, skip link, fallback menu, mobile controls, ARIA state.
- footer.php: fallback menu, legal URLs, contact path, semantic headings, widget area.

Supporting code reviewed: functions.php, assets/js/nav.js, relevant template parts, style.css, and theme.json.

## Launch-critical URLs

| URL | Theme status | Manual production verification |
| --- | --- | --- |
| / | Logo, Home fallback, 404 CTA, and front-page anchors use it | Assign a published static front page and confirm 200 |
| /books/ | Primary/footer fallbacks and book CTAs use it | Publish with Books template and confirm 200 |
| /teachers/ | Primary/footer fallbacks and education CTAs use it | Publish with Teachers template and confirm 200 |
| /about/ | Primary/footer fallbacks and mission CTA use it | Publish with About template and confirm 200 |
| /blog/ | Navigation and missing-category fallback use it | Assign as Posts page and test archives/pagination |
| /contact/ | Navigation, footer, and inquiry links use it | Publish with Contact template; test query anchors |
| /privacy-policy/ | Fallback when no Privacy page is assigned | Publish and assign in WordPress |
| /terms/ | Fallback unless WooCommerce supplies its Terms page | Publish and assign in WooCommerce where applicable |

No obsolete link was found in the specified navigation or footer fallbacks. Learning Hub links now fall back to the configured Posts page (or /blog/) when a category does not exist instead of constructing a likely 404.

## Critical issues found and fixed

1. Standard pages did not guarantee a page-level H1. page.php now renders the WordPress page title as the H1 inside an article. Editors must not add another H1 to standard-page content.
2. Footer and widget headings started at H4. They now use H2 while retaining existing styling.
3. Any non-empty form action could enable forms even if malformed or a placeholder. Actions now require a valid HTTP(S) URL; known placeholder paths are rejected. Invalid or absent endpoints keep all controls disabled.
4. Book cards claimed formats were available without matching products. Formats now come from published matching products, unavailable cards say the book is not currently in the shop, and page copy no longer promises identical availability.
5. Homepage destination links could select the first format SKU. Paperback is now preferred, with the first valid match as fallback. Grouped Books-page cards continue to prefer paperback.
6. Variable-product format discovery called wc_get_product() without its own explicit guard. The call is now guarded.

## WooCommerce integration

- No WooCommerce template was overridden or modified.
- Theme WooCommerce hooks use WordPress hook APIs and are safe when WooCommerce is inactive.
- Runtime WooCommerce calls are protected by function, class, post-type, or taxonomy checks.
- With WooCommerce inactive, no product post type means product cards are not rendered; internal Books-page/navigation links remain valid.
- Missing matches render disabled controls and honest availability text.
- Paperback remains the preferred grouped-adventure and homepage destination link.
- Product images use responsive WordPress attachment functions.

Manual checks: publish only launch-ready products; verify recognizable adventure/format titles or attributes; test price, stock, tax, shipping, every product permalink, cart, checkout, payment emails, and the WooCommerce Terms assignment.

## Forms

Audited: Adventure Club/newsletter, teacher signup, and contact/read-aloud form.

Controls remain disabled unless the applicable filter/custom field resolves to a valid HTTP(S) endpoint. Disabled forms explain that connection is pending; Contact supplies direct email. No fake success response or fake theme handler exists.

Before enabling: verify provider ownership, HTTPS, field names, tags, consent/privacy copy, spam protection, success/error handling, notifications, and retention. URL validation proves syntax only, not that a provider workflow works.

## Accessibility

- Major custom templates, posts, archives, and 404 have one theme-controlled H1.
- Standard pages now output a page-title H1.
- Heading flow is generally H1 to H2 to card H3 to detail H4.
- Footer section/widget headings no longer jump to H4.
- Forms have explicit labels.
- Skip link targets the unique #main landmark and has visible focus styling.
- Mobile toggle controls #primary-navigation and updates aria-expanded.
- Styles do not globally suppress outlines; focus remains visible.
- Decorative images have empty alt text; content images use metadata/context.

Manual checks: keyboard-only use at desktop/mobile widths, screen-reader menu announcement, 200% zoom/reflow, contrast over real hero images, provider error messages, and no editor-authored extra H1.

## Performance

- The only custom JavaScript is the small dependency-free mobile navigation script.
- No page builder dependency exists.
- Theme-controlled images use WordPress attachment APIs and responsive markup.
- Hero images intentionally use eager loading/high fetch priority; other images retain normal loading.
- No heavy asset was added.

Future: consider self-hosting/subsetting Lato, optimize originals, use modern formats where supported, and measure the populated production site with PageSpeed Insights or WebPageTest.

## SEO basics

- Theme title-tag support enables WordPress/SEO-plugin document titles.
- Templates use semantic main, section, article, header, footer, and nav elements.
- Blog home, archives, search, pagination, single posts, and empty states remain supported.
- No theme-level robots meta or noindex behavior was found.
- No obvious theme-controlled missing-H1 issue remains.

Manual checks: ensure Settings > Reading does not discourage search engines; inspect live robots and sitemap; configure canonicals/social metadata; verify excerpts, alt text, and titles; request indexing only on the final domain.

## Recommended fixes before launch

1. Publish and test all eight critical URLs with intended templates/slugs.
2. Assign static Front page and Posts page in Settings > Reading.
3. Assign Primary and Footer menus and compare with fallback links.
4. Upload/approve the founder photo or explicitly approve the visible placeholder.
5. Legally approve and assign Privacy Policy and Terms.
6. Publish/test all launch products, especially paperback links, covers, price, stock, and checkout.
7. Connect and test real form providers or intentionally launch with forms disabled.
8. Confirm the public contact email is correct and monitored.
9. Run staging keyboard, mobile, browser, and responsive QA with production content.

## Manual WordPress setup still required

- Create and publish the required pages with exact launch slugs and assign their custom templates.
- Set the static Front page and /blog/ Posts page in Settings > Reading.
- Assign Primary and Footer menus; remove any obsolete editor-managed menu items.
- Save permalinks and confirm all critical routes and anchors.
- Assign Privacy Policy in WordPress and Terms in WooCommerce.
- Configure products, images, format attributes, inventory, tax, shipping, payment, transactional email, and checkout pages.
- Add approved real provider endpoints only after end-to-end form testing; otherwise leave forms disabled.
- Verify the site is not set to discourage search engines at launch.
- Add production analytics, SEO metadata, backups, caching, security, and monitoring configuration.

## Future improvements

Not launch blockers and not implemented in Phase 8A:

- Provider-side form analytics, consent automation, and CRM lifecycle tests.
- Automated URL, accessibility, and checkout smoke tests.
- Self-hosted fonts and performance budgets.
- Structured data/social metadata through a maintained SEO plugin.
- Featured-only homepage merchandising controls if the published-product pool grows too broad.
- Automated broken-link monitoring.

## DNS migration reminders

- Lower TTL 24-48 hours before migration where possible.
- Record A/AAAA/CNAME/MX/TXT/CAA values before changes.
- Preserve MX, SPF, DKIM, and DMARC; do not replace the whole zone just to move the site.
- Provision TLS for apex and www before forcing HTTPS.
- Choose the canonical hostname and redirect the alternate with one 301 hop.
- Update WordPress/Site Address carefully; use serialized-safe staging URL replacement if needed.
- Clear host, object, page, CDN, and browser caches after cutover.
- Verify DNS through multiple resolvers/networks.
- Keep old hosting available until DNS, HTTPS, forms, checkout, email, analytics, and backups pass.
- Restore an appropriate production TTL after stabilization.

## Pre-launch checklist

### WordPress and content

- [ ] Production backup and tested restore path exist.
- [ ] Core, theme, and required plugins are updated on staging.
- [ ] All eight launch URLs return 200 over HTTPS.
- [ ] Front page and Posts page are assigned.
- [ ] Permalinks are saved; no critical URL redirects unexpectedly.
- [ ] Primary and Footer menus are assigned and tested.
- [ ] No draft copy, visible placeholder, test product, or staging URL remains.
- [ ] Contact email, entity name, and legal text are approved.

### Commerce and forms

- [ ] Product, cart, checkout, account, email, tax, shipping, payment, refund, and Terms flows pass.
- [ ] Paperback is the primary card target where one exists.
- [ ] Missing products show an honest unavailable state.
- [ ] Forms are connected to verified providers or visibly disabled.
- [ ] Enabled forms pass success, error, spam, consent, tagging, notification, and mobile tests.
- [ ] Test contacts/orders are removed from production reporting.

### Accessibility, SEO, and performance

- [ ] Each major rendered page has exactly one H1; editor content begins at H2.
- [ ] Keyboard, skip link, mobile menu, focus, zoom, labels, and errors pass manual QA.
- [ ] Real images have appropriate alt text and sufficient overlay contrast.
- [ ] Search visibility, robots, and sitemap are correct.
- [ ] Titles, canonicals, social previews, analytics, and Search Console are configured.
- [ ] Core Web Vitals, images, and caching are measured in production.
- [ ] 404, blog, archive, search, pagination, and single-post views are tested.

### Infrastructure and cutover

- [ ] DNS records are backed up and mail records preserved.
- [ ] TLS, redirects, CDN/cache, security headers, and firewall rules are verified.
- [ ] Backups, uptime monitoring, error logging, and broken-link monitoring are active.
- [ ] Smoke-test owner and rollback decision point are assigned.
- [ ] Old hosting remains available through stabilization.

## Current launch blockers

No known code-level blocker remains from this theme audit. Launch is still blocked until staging/live checks prove required pages exist, legal content is approved, products/checkout work, DNS/TLS are ready, and the form state is intentional. If lead capture is required at launch, verified provider endpoints are also blocking; otherwise the theme safely keeps forms disabled.
