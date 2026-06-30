# Brave Hearts Publishing — Publisher Review Index

Prepared: June 30, 2026  
Review scope: WordPress theme repository and required production page inventory

## Evidence boundary

No WordPress staging URL, database, content export, product inventory, or media library is available in this workspace. The configured public domain currently serves the legacy Squarespace website, so it is not used as rendered evidence for this WordPress theme.

URLs below are the theme's expected canonical paths. Dynamic rows must be expanded from the WordPress database before Executive Publisher Review begins.

## Package contents

- [Capture Manifest](Capture-Manifest.md)
- [Lighthouse Summary](Lighthouse-Summary.md)
- [Accessibility Summary](Accessibility-Summary.md)
- [Broken Link Validation](Broken-Link-Validation.md)
- [Image Audit](Image-Audit.md)
- [Theme Freeze Recommendation](../Theme-Freeze.md)

## Core and commerce pages

| Title | URL | Template | Primary CTA | Secondary CTA | Forms | Notes |
| --- | --- | --- | --- | --- | --- | --- |
| Home | `/` | `front-page.php` | Shop/Explore the Books | Explore Adventures | Adventure Club signup | |
| About | `/about/` | `page-about.php` | Shop the Books | Teacher Resources | None | |
| Books | `/books/` | `page-books.php` | Start with Book 1 | Shop All Books | None | |
| Shop | `/shop/` | WooCommerce archive with theme wrappers | Product purchase journey | Product/category navigation | WooCommerce product controls | |
| Every Product | Inventory required | WooCommerce single-product template | Add to cart or approved external purchase | Related products/formats | WooCommerce purchase form | |
| Cart | `/cart/` | WooCommerce Cart page/block | Proceed to Checkout | Continue Shopping | Cart update/coupon controls | |
| Checkout | `/checkout/` | WooCommerce Checkout page/block | Place Order | Return to Cart | Checkout and payment forms | |
| My Account | `/my-account/` | WooCommerce My Account page | Sign in/account action | Account navigation | Login, registration, account forms | |

## Editorial and Learning Hub pages

| Title | URL | Template | Primary CTA | Secondary CTA | Forms | Notes |
| --- | --- | --- | --- | --- | --- | --- |
| Blog | `/blog/` | `index.php` via Posts page | Read article | Pagination | None | |
| Blog Archive | Dynamic archive URL | `index.php` | Read article | Pagination | None | |
| Animals Learning Hub | Published page or `category/animals/` | `page.php` or `index.php` | Read topic content | Blog fallback | Content-defined | |
| Science Learning Hub | Published page or `category/science/` | `page.php` or `index.php` | Read topic content | Blog fallback | Content-defined | |
| Geography Learning Hub | Published page or `category/geography/` | `page.php` or `index.php` | Read topic content | Blog fallback | Content-defined | |
| Conservation Learning Hub | Published page or `category/conservation/` | `page.php` or `index.php` | Read topic content | Blog fallback | Content-defined | |
| Explorers Learning Hub | Published page or `category/explorers/` | `page.php` or `index.php` | Read topic content | Blog fallback | Content-defined | |
| Activities Learning Hub | Published page or `category/activities/` | `page.php` or `index.php` | Read topic content | Blog fallback | Content-defined | |
| Every Blog Post | Inventory required | `single.php` | Content-defined | Previous/next post | Content-defined | |
| Every Category/Tag/Author Archive | Inventory required | `index.php` | Read article | Pagination | None | |

## Audience and contact pages

| Title | URL | Template | Primary CTA | Secondary CTA | Forms | Notes |
| --- | --- | --- | --- | --- | --- | --- |
| Teachers | `/teachers/` | `page-teachers.php` | Explore the Books | Request a Read-Aloud | Teacher signup or Contact fallback | |
| Contact | `/contact/` | `page-contact.php` | Contact Us | Request a Read-Aloud | Contact form or email fallback | |

## Explorer Passport pages

| Title | URL | Template | Primary CTA | Secondary CTA | Forms | Notes |
| --- | --- | --- | --- | --- | --- | --- |
| Explorer Passport | `/explorer-passport/` | `page-explorer-passport.php` | Join the Adventure Club | See What's Inside | Lead-magnet signup | |
| Explorer Passport Lead Magnet | Page slug must be confirmed | `page-explorer-passport-lead-magnet.php` | Join the Adventure Club | None | Lead-magnet signup | |
| Explorer Passport Thank You | Page slug must be confirmed | `page-explorer-passport-thank-you.php` | Choose a Book | Visit the Passport | Download state | |
| Explorer Passport Download | Page slug must be confirmed | `page-explorer-passport-download.php` | Download when approved | Explore the Books | Download state | |

## Legal and system pages

| Title | URL | Template | Primary CTA | Secondary CTA | Forms | Notes |
| --- | --- | --- | --- | --- | --- | --- |
| Privacy Policy | `/privacy-policy/` if published | `page.php` | Content-defined | Content-defined | Content-defined | |
| Terms | `/terms/` or WooCommerce-assigned Terms page | `page.php` | Content-defined | Content-defined | Content-defined | |
| Search | `/?s={query}` | `index.php` | Read result | Pagination | Search form location must be confirmed | |
| 404 | Invalid URL | `404.php` | Back to Home | None | None | |
| Additional Standard Pages | Inventory required | `page.php` | Content-defined | Content-defined | Content-defined | |

## Inventory required before review

- Export every published page, post, product, category, tag, author archive, and canonical URL.
- Record every page-template assignment and editor-managed CTA override.
- Record the WooCommerce Shop, Cart, Checkout, My Account, and Terms page assignments.
- Record every public Explorer Passport slug and publication/indexing state.
- Record all menus, widgets, forms, redirects, and media attachments used by the rendered site.
