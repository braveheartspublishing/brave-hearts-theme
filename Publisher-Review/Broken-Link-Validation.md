# Broken Link Validation

Prepared: June 30, 2026  
Evidence level: Theme-source validation only

## Source checks passed

- No literal `href="null"` or empty `href=""` exists in theme PHP.
- Visitor-facing dynamic URLs pass through safe URL normalization at shared component boundaries.
- Invalid editor-content links are stripped of `href` at render time.
- `/teachers-guide/` redirects permanently to canonical `/teachers/`.
- Fallback primary and footer navigation use `/`, `/books/`, `/teachers/`, `/about/`, `/blog/`, and `/contact/`.
- Books-page Shop CTAs use `/shop/`.
- Amazon CTAs accept only approved Amazon marketplace hosts or official short-link hosts.
- Learning Hub links prefer a published topic page, then its category archive, then the configured Blog page.
- Teacher resource links render only when a valid destination exists.
- Contact and teacher forms expose working email/contact fallbacks when providers are unavailable.

## Required live checks

| Area | What must be validated |
| --- | --- |
| Internal links | Crawl every rendered page and resolve all 3xx chains, 4xx, 5xx, fragments, canonicals, and mixed-domain links |
| CTAs | Click every hero, card, section, final, email, download, and purchase CTA while logged out |
| Product links | Verify every product, variation, external marketplace URL, stock state, format search, cart action, and return path |
| Menus | Export and test editor-managed Primary and Footer menus, including mobile behavior and current-page state |
| Footer | Verify Privacy Policy, Terms, Contact, email, Adventure Club, and any widget links |
| Teacher resources | Verify every configured `bhp_teachers_*_url` and book-resource override |
| Learning Hub | Confirm whether each topic resolves to a dedicated page or intended category; ensure no topic silently falls back to an unrelated Blog index |
| Forms | Verify contact, Adventure Club, teacher, and lead-magnet success/error destinations without sending production test data |
| Legal/system pages | Verify Shop, Privacy, Terms, Cart, Checkout, My Account, Search, 404, feeds, and sitemap URLs |
| Redirects | Test `/teachers-guide/` and the complete Squarespace-to-WordPress redirect matrix |

## Known unresolved destinations

- Privacy Policy and Terms theme fallbacks can still lead to nonexistent pages if WordPress/WooCommerce assignments are incomplete.
- Shop, Cart, Checkout, My Account, and product URLs depend on live WooCommerce configuration.
- Product inventory and configured Amazon URLs are unavailable.
- Learning Hub publication/category state is unavailable.
- Explorer Passport downloads intentionally remain unavailable until the approved asset and delivery workflow exist.
- Editor-managed menus, custom fields, widgets, post content, and plugin output are unavailable.

## Result

Theme source: PASS.  
Rendered site: NOT TESTED.  
Launch gate: BLOCKED pending staging crawl and click-through validation.

