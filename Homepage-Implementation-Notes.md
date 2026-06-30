# Homepage Brand Alignment — Implementation Notes

## Files changed

- `front-page.php`
- `style.css`
- `Homepage-Implementation-Notes.md`

## Why each change was made

### `front-page.php`

- Added the approved philosophy directly after the hero so the opening journey moves from wonder to purpose before presenting products.
- Reordered the existing Homepage sections to create the intended progression: wonder, purpose, adventure, books, then learning and teacher resources.
- Changed default hero and book CTA language from transactional wording to clear, emotionally framed invitations with intentional destinations.
- Reframed the featured-books introduction around real places, true science, and brave choices.
- Added one child-facing curiosity question to each destination card without changing the shared card component.
- Replaced the product-first final CTA eyebrow with language that continues the theme of wonder.
- Preserved the existing Homepage custom-field hooks and content filters so editors and integrations continue to work.

### `style.css`

- Added a restrained philosophy-section treatment using the existing brand colors, typefaces, spacing, borders, and shadows.
- Added a responsive three-to-one-column layout for the philosophy pillars.
- Added a small typographic treatment for destination curiosity questions.
- Added no animation, script, font, image, or external dependency.

## Accessibility considerations

- The philosophy section has a programmatic section label through `aria-labelledby` and a single descriptive `h2`.
- The three philosophy pillars use a semantic list with an accessible label.
- Heading order remains intact; the Homepage hero component remains the sole `h1` source.
- CTA labels describe the action and destination more clearly than the prior commerce-first language.
- Existing button focus styles and reduced-motion safeguards remain unchanged.
- Text colors use existing high-contrast theme tokens, and mobile content remains in normal document order.
- Curiosity questions are real text, not decorative or image-based content.

## Implementation compromises

- Existing WordPress `bhp_home_*` custom fields intentionally continue to override theme defaults. If staging has saved values for the revised hero, book, philosophy, or final CTA fields, those editorial values will remain visible until reviewed in WordPress.
- No local WordPress runtime or PHP executable was available for rendered-page testing or PHP linting. Static structure, escaping patterns, link destinations, responsive CSS, and the working-tree diff were reviewed instead.
- Destination questions were added through the Homepage card data so the shared hub-card component—and every other page using it—remains untouched.
