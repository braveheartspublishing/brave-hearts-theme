# Visual Capture Manifest

Prepared: June 30, 2026  
Status: BLOCKED — WordPress staging target unavailable

## Why capture is blocked

- No local WordPress runtime, database, or staging URL is present.
- No local web server is listening for this project.
- `https://www.braveheartspublishing.com/` currently serves Squarespace assets and legacy content, not this WordPress theme.
- Capturing the legacy site as though it represented the new theme would invalidate Executive Publisher Review.

## Required capture settings

Desktop:

- Width: 1440px
- Full-page capture from top to bottom
- Use production-equivalent content while logged out

Mobile:

- Modern mobile viewport; recommended 390 × 844px
- Full-page capture from top to bottom
- Use the same content and logged-out state as desktop

## Sequential naming plan

Assign numbers only after the WordPress export fixes the product, archive, Learning Hub, and additional-page counts.

1. `01-Home.png`
2. `02-About.png`
3. `03-Books.png`
4. `04-Shop.png`
5. Continue with every product, one file per canonical product URL.
6. Continue with Blog and every public blog archive.
7. Continue with Animals, Science, Geography, Conservation, Explorers, Activities, and every additional Learning Hub discovered.
8. Continue with Teachers, Contact, and every public Explorer Passport page.
9. Continue with Privacy Policy and Terms when present.
10. Continue with Cart, Checkout, My Account, Search, and 404.
11. Append every additional public page discovered in the WordPress inventory.

Use identical filenames beneath separate directories:

- `Publisher-Review/Desktop/`
- `Publisher-Review/Mobile/`

## Capture status

| Capture set | Generated | Required next input |
| --- | ---: | --- |
| Desktop | 0 | WordPress staging URL and complete public URL inventory |
| Mobile | 0 | WordPress staging URL and complete public URL inventory |

## Diagnostic evidence

`Blocked-Target-Legacy-Squarespace.png` is a 1440px full-page diagnostic capture of the configured public domain. It confirms that the available public target is the legacy Squarespace site. It is not part of the WordPress desktop/mobile review set and must not be used to approve the new theme.
