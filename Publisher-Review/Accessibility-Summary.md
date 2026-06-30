# Accessibility Summary

Review date: 2026-06-30  
Scope: 88 rendered public URLs at desktop and mobile sizes; DOM structure was inspected read-only.

## High priority

- Twelve URLs do not have exactly one H1. About, Books, Shop, Teacher's Guide, Contact, Cart, Checkout, My Account, Mailchimp Test, and Sample Page have no H1. Teachers has six H1 elements. “Bridge Books: The Perfect Next Step After Frog and Toad” has seven H1 elements.
- Teachers contains duplicate IDs for both repeated email fields. Duplicate IDs can break label association and screen-reader navigation.
- My Account has one unlabeled visible input. The public Mailchimp Test page has two unlabeled visible inputs.
- Heading levels skip throughout category/post layouts, frequently from H2 to H4. Footer headings contribute to the pattern, but several content templates also skip levels.

## Images and alternatives

- No rendered image omitted the `alt` attribute entirely.
- Across 112 unique rendered images, 82 use empty alt text. Some are likely decorative; product covers and editorial thumbnails must be reviewed individually before treating them as decorative.
- The media library contains 90 image records with blank alternative text. Library metadata does not prove a live failure, but it makes future misuse likely.

## Landmarks and navigation

- Every captured URL exposed one `main` landmark and a footer.
- A skip-to-content link is present on the Homepage.
- Some templates expose multiple `header`, `nav`, or `footer` elements. These may be valid nested structures, but accessible names and landmark uniqueness need manual screen-reader confirmation.
- Teachers is split across `/teachers/` and `/teachers-guide/`; the primary menu still targets the latter. This creates a navigation and orientation problem, not merely an editorial inconsistency.

## Forms and ARIA

- Teachers repeats the same email-capture form and IDs twice.
- Homepage email capture appears to rely on placeholder text as its visible identification; confirm that an explicit programmatic label is present.
- No visible unlabeled buttons were detected.
- Automated DOM inspection cannot prove live-region behavior, error announcement, or checkout form validation. Those require manual submission testing in a non-production test order flow.

## Keyboard, focus, motion, and contrast

- Link and control semantics were present in the sampled DOM, but a complete keyboard sequence could not be certified with the available browser instrumentation.
- Focus indicator visibility, modal/menu focus containment, and mobile menu escape behavior require manual keyboard testing.
- Reduced-motion handling could not be certified from the rendered site.
- Contrast was not numerically audited because Lighthouse/axe tooling was unavailable. Visual review should be followed by WCAG contrast measurement for text over photography, muted footer text, and button states.

## Accessibility recommendation

NO-GO until core-page H1 defects, Teachers duplicate IDs, unlabeled inputs, and keyboard/focus/contrast verification are resolved.
