# Theme Freeze Recommendation

Review date: 2026-06-30  
Staging: https://staging2.braveheartspublishing.com/

## Completion estimates

- Theme completion: **82%**
- Website completion: **72%**

The theme has a coherent visual identity, responsive rendering, functioning WooCommerce product/catalog views, and a substantial editorial library. Website completion is lower because critical public journeys, legal trust pages, educator downloads, and accessibility hierarchy are incomplete.

## Launch blockers

1. Twelve genuine broken internal destinations, including five Homepage Learning Hub cards, three `null` paths, and three teacher-resource files.
2. `/teachers-guide/` does not redirect to canonical `/teachers/`; navigation still points to the duplicate route.
3. Core heading failures: About, Books, Shop, Contact, Cart, Checkout, My Account, and Teacher's Guide have no H1; Teachers has six H1 elements.
4. Teachers repeats form IDs; My Account and Mailchimp Test expose unlabeled inputs.
5. Privacy Policy and Terms were not found.
6. Explorer Passport was not found.
7. Public Mailchimp Test and Sample Page remain published.
8. Lighthouse, keyboard, focus, reduced-motion, and contrast certification remain incomplete.

## Version 1.1 candidates

- Consolidate overlapping blog categories and move posts out of Uncategorized.
- Build a unified educator-resource library with grade, skill, and duration filters.
- Strengthen explicit Character messaging across product and archive pages.
- Migrate remaining Squarespace CDN assets into WordPress and expand AVIF/WebP delivery.
- Add richer parent/teacher trust signals and structured classroom-adoption content.

## Recommended freeze date

Do not set a calendar freeze yet. Freeze on the first business day after all launch blockers are resolved, a populated purchase flow is tested, and one clean desktop/mobile Lighthouse plus manual accessibility pass is archived.

## Recommendation

**NO-GO. Do not freeze or migrate DNS in the current state.**
