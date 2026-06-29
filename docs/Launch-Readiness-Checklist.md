# Brave Hearts Publishing Launch Readiness Checklist

## Launch principle

The staging site should feel complete, trustworthy, and navigable before DNS changes. Placeholder forms and downloads must remain visibly disabled until their real providers and assets are tested.

## 1. Required WordPress pages and slugs

Create and publish these pages exactly:

| Page | Slug / URL | WordPress setup | Template |
| --- | --- | --- | --- |
| Home | / | Set as static homepage | Front Page template selected automatically |
| Books | /books/ | Standard published page | Brave Hearts Books Page |
| Teacher Resources | /teachers/ | Standard published page | Brave Hearts Teacher Resources Page |
| About | /about/ | Standard published page | Brave Hearts About Page |
| Blog | /blog/ | Set as Posts page | Index/archive template selected automatically |
| Contact | /contact/ | Standard published page | Brave Hearts Contact Page |
| Privacy Policy | /privacy-policy/ | Select in WordPress Privacy settings | Approved legal copy required |
| Terms | /terms/ | Link from WooCommerce Terms setting when applicable | Approved legal copy required |

### Reading settings

- In Settings > Reading, choose A static page.
- Set Homepage to Home.
- Set Posts page to Blog.
- Confirm the homepage does not also contain page-builder or duplicate block content.
- Confirm staging discourages search-engine indexing until launch approval.
- Remove the staging no-index setting only after DNS, SSL, canonical URLs, and content are verified.

## 2. Primary navigation setup

Create one WordPress menu and assign it to Primary Navigation in this order:

1. Home
2. Books
3. Teacher Resources
4. About
5. Blog
6. Contact

The theme fallback uses the same six links when no primary menu is assigned.

### Primary navigation checks

- Use page objects rather than manually typed URLs where practical.
- Keep labels concise and use exactly one Teacher Resources entry.
- Do not add Cart to the primary launch menu unless the purchase journey requires it after testing.
- Confirm desktop, mobile-toggle, keyboard, current-page, and focus behavior.
- Confirm no link points to the former Squarespace site after migration.

## 3. Footer menu setup

Create one WordPress menu and assign it to Footer Navigation with:

1. Books
2. Teacher Resources
3. About
4. Blog
5. Contact
6. Privacy Policy
7. Terms
8. Adventure Club

Adventure Club should link to /#adventure-club.

The theme fallback supplies these links when no footer menu is assigned. The footer bottom also retains Privacy Policy, Terms, and Contact links.

### Legal-link checks

- Do not publish placeholder legal copy as final policy.
- Confirm WordPress Privacy settings point to /privacy-policy/.
- Confirm WooCommerce Terms settings point to /terms/ if WooCommerce checkout uses that page.
- Review legal copy with the appropriate owner before launch.

## 4. CTA wiring audit

Current planned destinations:

| CTA intent | Destination |
| --- | --- |
| Home | / |
| Shop / Explore Books | /books/ or a real product URL |
| Teacher Resources | /teachers/ |
| About / Mission | /about/ |
| Blog / Learning articles | /blog/ or a real category/article URL |
| Contact | /contact/ |
| Read-Aloud Request | /contact/?inquiry=read-aloud#contact-form |
| Adventure Club | /#adventure-club |
| Privacy Policy | /privacy-policy/ |
| Terms | /terms/ or configured WooCommerce Terms page |
| Explorer Passport | /explorer-passport/ after its page is created |

### CTA checks

- Click every button from desktop and mobile layouts.
- Confirm product-specific View Book links resolve to published WooCommerce products.
- Confirm Shop Formats searches return only relevant products.
- Confirm Book 1 hero CTA resolves to the published Mariana Trench product or the Books grid fallback.
- Confirm all Read-Aloud links preselect Read-Aloud Request.
- Confirm category links resolve or remain intentionally future-facing.
- Scan for redirects, 404s, mixed domains, duplicate anchors, and staging URLs.

## 5. WooCommerce product setup

### Core catalog

- Create or verify live products for every customer-facing Charlotte & Henry format.
- Keep products in the Charlotte & Henry or Books product category.
- Use consistent destination and format naming so grouping works.
- Confirm Paperback, Hardcover, and Kindle availability before displaying those claims.
- Add approved prices, inventory state, tax settings, shipping class, dimensions, and purchase links.
- Confirm external Kindle links use the correct disclosure and destination when introduced.

### Product media

- Upload the final cover for every SKU.
- Use consistent aspect ratios and sufficient image resolution.
- Add accurate alternative text.
- Confirm thumbnails render correctly on the homepage, Books page, Teacher Resources page, cart, and checkout.
- Remove duplicate or obsolete cover media.

### Featured products

- Mark the intended flagship products as Featured in WooCommerce.
- Prefer one featured paperback product per adventure until canonical adventure pages exist.
- Verify featured status does not create duplicate customer-facing adventure cards.
- Confirm the homepage still shows every intended live Charlotte & Henry title.
- Document whether future homepage logic should remain all-live or become feature-only.

### Commerce testing

- Test product selection, cart, coupon policy, taxes, shipping, payment, confirmation emails, refunds, and stock changes in staging-safe modes.
- Test guest checkout and any required account flow.
- Confirm checkout Privacy and Terms links.
- Confirm no production orders or customer emails are triggered during staging tests.

## 6. Provider and placeholder status

### Mailchimp / acquisition forms

Current status: placeholder only.

- Adventure Club, teacher, lead-magnet, blog, and footer forms remain disabled until bhp_signup_form_action returns an approved endpoint.
- Confirm disabled controls and provider-pending text remain visible on staging.
- Do not add API keys or secrets to the theme.
- Before enabling, complete field mapping, consent language, double opt-in policy, tags/groups, delivery assets, success/error handling, and end-to-end testing.

### Contact form

Current status: placeholder only.

- The Contact form remains disabled until bhp_contact_form_action returns an approved endpoint.
- Direct email must remain visible while the form is disabled.
- Before enabling, implement server validation, spam protection, routing, notifications, rate limiting, privacy handling, and success/error states.
- Confirm Read-Aloud and School/Library query values preselect the expected inquiry type.

### Explorer Passport

Current status: no PDF and no delivery integration.

- Download CTAs remain disabled until the PDF is approved.
- Do not mark bhp_explorer_passport_download_ready true until the asset, URL, accessibility, and delivery path pass testing.

## 7. Required images

- Homepage cinematic hero image.
- Final cover images for every live product/SKU.
- Andrew Signore founder photograph.
- Optional About hero image.
- Optional Teacher Resources hero image.
- Optional Contact hero image.
- Teacher/classroom imagery where rights and releases are confirmed.
- Adventure destination imagery for Mariana Trench, Mount Everest, and Amazon Rainforest.
- Social sharing image for the site and major landing pages.
- Site icon/favicon and WordPress Site Icon.
- Custom logo in light/dark-safe form.

For every image:

- Confirm ownership or license.
- Optimize dimensions and compression.
- Use descriptive filenames.
- Add meaningful alternative text when the image is informative.
- Use empty alternative text for purely decorative imagery.
- Check focal point and cropping on mobile.

## 8. Mobile and accessibility review

Test at minimum:

- 320px narrow mobile.
- Common modern phone widths.
- Tablet portrait and landscape.
- Desktop and wide desktop.
- 200% browser zoom.
- Keyboard-only navigation.
- Visible focus indicators.
- Screen-reader landmark and heading navigation.
- Mobile navigation open/close behavior.
- Form labels, disabled states, and error states after provider integration.
- Color contrast on light and dark sections.
- Text reflow without horizontal scrolling.
- Touch-target size and spacing.
- Reduced-motion preference.

## 9. SEO basics

- Confirm site title, tagline, timezone, language, and favicon.
- Use human-readable permalinks and flush rewrites after page creation.
- Set unique page titles and meta descriptions.
- Confirm one meaningful H1 per page.
- Add canonical URLs using the production domain.
- Generate and verify the XML sitemap.
- Review robots.txt and staging no-index controls.
- Add Open Graph and social-sharing images.
- Add accurate image alternative text.
- Check internal links and orphan pages.
- Review 404 behavior and redirects from important Squarespace URLs.
- Preserve or redirect high-value legacy blog and book URLs.
- Connect analytics and search tools only after privacy and consent decisions are approved.

## 10. Squarespace to SiteGround DNS migration reminder

Do not cancel or disconnect Squarespace before the new SiteGround site is verified.

Before cutover:

- Inventory current DNS records, including A, AAAA, CNAME, MX, TXT, SPF, DKIM, DMARC, verification, and subdomain records.
- Record current Squarespace URLs and required redirects.
- Back up WordPress files and database on SiteGround.
- Confirm the production domain is configured in WordPress and SiteGround.
- Provision and test SSL on SiteGround.
- Lower DNS TTL in advance when practical.
- Confirm email hosting records will remain intact.
- Prepare a rollback plan and identify the person authorized to change DNS.

During cutover:

- Update only the required web records.
- Preserve email and verification records.
- Monitor DNS propagation, SSL, redirects, forms, checkout, and admin access.
- Test from multiple networks and devices.

After cutover:

- Confirm HTTPS and canonical-domain redirects.
- Re-save WordPress permalinks if needed.
- Purge SiteGround, WordPress, CDN, and browser caches.
- Verify analytics, sitemap, robots, Search Console, and transactional email.
- Keep Squarespace available until traffic and critical journeys are stable.

## 11. Pre-launch testing checklist

### Content and navigation

- [ ] All required pages exist and are published.
- [ ] Home and Blog are assigned in Reading settings.
- [ ] Primary and Footer menus are assigned.
- [ ] Every menu and CTA link has been clicked and verified.
- [ ] No staging URLs or Squarespace-domain links remain unintentionally.
- [ ] No placeholder copy is presented as completed functionality.
- [ ] Legal pages contain approved copy.

### Books and commerce

- [ ] Three adventures display correctly without format-SKU duplication.
- [ ] Covers, titles, destinations, descriptions, and format claims are accurate.
- [ ] Product links, format searches, cart, and checkout work.
- [ ] Featured products are intentionally selected.
- [ ] Test orders and emails are isolated from customers.

### Acquisition and contact

- [ ] Disabled forms cannot submit.
- [ ] Provider-pending notes are visible.
- [ ] Direct contact email is correct.
- [ ] Read-Aloud links select the correct inquiry.
- [ ] No fake download link is interactive.
- [ ] No API keys, secrets, or production credentials exist in theme files.

### Design and accessibility

- [ ] Responsive layouts pass mobile, tablet, and desktop review.
- [ ] Navigation works with keyboard and touch.
- [ ] Focus states are visible.
- [ ] Heading order and landmarks are logical.
- [ ] Images have correct alternatives and crops.
- [ ] Contrast and zoom tests pass.

### Technical

- [ ] WordPress, theme, and plugins are backed up.
- [ ] Required plugins are updated and licensed.
- [ ] PHP errors, browser-console errors, and broken links are resolved.
- [ ] Caching and image optimization are configured.
- [ ] SSL and HTTPS redirects work.
- [ ] SMTP or transactional email is tested.
- [ ] Security, backups, and uptime monitoring are enabled.
- [ ] Staging no-index protection is removed only at launch.
- [ ] DNS rollback information is available.

## Launch approval gate

Do not change DNS until the page wiring, catalog, checkout, legal pages, accessibility review, provider placeholder review, backups, SSL, redirects, and rollback plan have explicit approval.
