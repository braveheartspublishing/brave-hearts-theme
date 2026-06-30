# Accessibility Summary

Prepared: June 30, 2026  
Evidence level: Source review only; rendered assistive-technology testing remains required

## Heading hierarchy

- Core theme templates provide one theme-controlled H1 on Home, About, Books, Teachers, Contact, standard pages, single posts, indexes, search, archives, and 404 pages.
- Core page sections use H2 headings; cards generally use H3; card subgroups use H4.
- Editor content can still introduce duplicate H1s or skipped levels and must be audited in the rendered WordPress content.
- The current Squarespace domain contains multiple H1s, but it is the legacy site and is not evidence for the WordPress theme.

Status: SOURCE PASS; CONTENT AND RENDERED REVIEW REQUIRED.

## Missing alt text

- Hero, feature-card, and Hub images are intentionally decorative and receive empty alt text.
- Book covers and Passport cards fall back to their visible title when a configured alt value is absent.
- Single-post featured images depend on media-library alt text and can render with empty alt text.
- Teacher CTA images accept an optional alt value; whether an empty value is correct depends on the rendered image's purpose.
- The media library and rendered HTML are unavailable, so a complete missing-alt report cannot be produced.

Status: MANUAL MEDIA-LIBRARY AND RENDERED-DOM AUDIT REQUIRED.

## Contrast

- The theme defines a consistent forest, navy, gold, ivory, parchment, earth, and white palette.
- Source review cannot prove effective contrast after backgrounds, overlays, images, editor blocks, WooCommerce, plugins, or browser rendering are applied.
- Body links are primarily distinguished by color until hover, so persistent link affordance and contrast require focused review.

Status: AXE/LIGHTHOUSE PLUS MANUAL CONTRAST REVIEW REQUIRED.

## Keyboard navigation

- Interactive navigation uses native links and buttons.
- The mobile menu exposes `aria-expanded`, closes after link activation, and supports Escape with focus returned to the toggle.
- Forms use native inputs, selects, textareas, buttons, and associated labels.
- Invalid stored links are rendered without `href`, preventing keyboard activation.
- Menu order, WooCommerce controls, modal behavior, plugin widgets, and the full checkout flow cannot be tested without staging.

Status: SOURCE PASS; FULL KEYBOARD WALKTHROUGH REQUIRED.

## Landmark usage

- The theme provides one header, primary navigation, main landmark, footer, and footer navigation.
- Skip navigation targets the main landmark.
- Some landmarks include redundant explicit roles, which are harmless but unnecessary.

Status: SOURCE PASS.

## Focus indicators

- Buttons, block buttons, key components, dark surfaces, header links, footer links, and the mobile navigation toggle receive visible focus outlines.
- Browser-default focus remains available where the theme does not define a component-specific rule.
- Focus visibility must be checked over every rendered background and image.

Status: SOURCE PASS; VISUAL VERIFICATION REQUIRED.

## Reduced motion

- `prefers-reduced-motion: reduce` disables smooth scrolling and minimizes animations/transitions globally.

Status: SOURCE PASS.

## ARIA

- Disclosure navigation uses `aria-controls` and `aria-expanded`.
- Forms use `aria-describedby` and live status messages where appropriate.
- Decorative media is hidden from assistive technology where appropriate.
- Disabled non-links expose `aria-disabled`.
- Runtime-generated WooCommerce, SEO, consent, email, and other plugin markup remains untested.

Status: SOURCE PASS; RENDERED ARIA/NAME/ROLE/VALUE AUDIT REQUIRED.

## Required Executive Review checks

- Keyboard-only traversal at desktop and mobile widths.
- NVDA/Firefox or NVDA/Chrome on Windows.
- VoiceOver/Safari on iOS or macOS.
- 200% and 400% zoom, reflow, orientation, and text-spacing checks.
- Axe or equivalent automated scan followed by manual review.
- Checkout errors, form errors, validation announcements, account flows, and focus movement.

