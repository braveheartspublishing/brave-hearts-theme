# Broken Link Validation

Review date: 2026-06-30

## Method

Collected links from all 88 rendered URLs and issued read-only requests to 304 unique internal destinations. Cart mutation, login/logout, admin, and add-to-cart action links were excluded. The controlled 404 URL used for the 404 capture is not counted as a defect.

## Result

Twelve genuine broken destinations were found.

| Source | Link text | Broken destination | Status |
|---|---|---|---:|
| Home | Explore animals → | `/learning-hub/animals/` | 404 |
| Home | Explore geography → | `/learning-hub/geography/` | 404 |
| Home | Explore conservation → | `/learning-hub/conservation/` | 404 |
| Home | Meet explorers → | `/learning-hub/explorers/` | 404 |
| Home | Try an activity → | `/learning-hub/activities/` | 404 |
| Books | Empty linked element | `/books/null` | 404 |
| Teachers | Empty linked element | `/teachers/null` | 404 |
| Contact | Empty linked element | `/contact/null` | 404 |
| Free Teacher's Guide — Mariana Trench | Download the Teacher's Guide — Free | `/s/Charlotte_Henry_Teachers_Guide-3-19-2026-pdf.pdf` | 404 |
| Dog Man to Magic Tree House Reading Roadmap | Get it here → | `/s/Mariana-Trench-20-min-Guide-300DPI.pdf` | 404 |
| Finding the Right Books With a Lexile Score | What Is a Lexile Score? | `/blog/blog/what-is-a-lexile-score` | 404 |
| What Are Bridge Books? | Free 20 minute No Prep Teachers Guide: Mount Everest | `/s/Everest-20min-Guide-300DPI.pdf` | 404 |

## Additional routing findings

- `/teachers-guide/` returns its own page; it does not redirect to `/teachers/`.
- The primary navigation Teachers item points to `/teachers-guide/`, while the stronger resource page is `/teachers/`.
- `/checkout/` redirects to `/cart/` when the cart is empty. This is expected WooCommerce behavior, but a populated checkout was not exercised because the review was non-mutating.
- `/learning-hub/science/` redirects successfully to the science article, while five sibling Learning Hub routes fail.
- No public Explorer Passport URL or link was discovered.
- No public Privacy Policy or Terms URL or link was discovered.

## Recommendation

NO-GO. Broken Homepage journeys, `null` links, and failed teacher downloads are launch blockers.
