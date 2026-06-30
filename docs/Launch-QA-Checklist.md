# Brave Hearts Publishing Launch QA Checklist

Definitive pre-launch and release-validation process
Version: 1.0
Created: June 29, 2026

## Release record

- [ ] Release/version: ______________________________
- [ ] Planned launch date and time: ______________________________
- [ ] Canonical production URL: ______________________________
- [ ] Staging URL: ______________________________
- [ ] QA lead: ______________________________
- [ ] Business approval owner: ______________________________
- [ ] Technical cutover owner: ______________________________
- [ ] DNS owner: ______________________________
- [ ] Rollback decision owner: ______________________________
- [ ] Backup location and timestamp: ______________________________
- [ ] Evidence folder or release ticket: ______________________________

## How to use this checklist

- [ ] Complete the checklist against staging with production content before DNS cutover.
- [ ] Mark an item complete only after direct verification; configuration assumptions do not count.
- [ ] Attach evidence for payment, email, Mailchimp, analytics, DNS, accessibility, and restore tests.
- [ ] Record every waived item with approver, reason, risk, and expiration date.
- [ ] Treat every item labeled MANUAL as requiring a person to verify the live/staging system.
- [ ] Stop launch for any blocker in the Launch approval gate.
- [ ] Reuse this document for future releases by copying it and resetting every checkbox.

## Current manual gates identified from the theme

These are not new theme defects. They require a production decision or external configuration.

- [ ] MANUAL — Confirm the contact form has a real, secured provider/handler. If not, approve launch with the form disabled and verify the monitored direct-email fallback.
- [ ] MANUAL — Confirm the founder photo is uploaded, or explicitly approve the visible founder placeholder.
- [ ] MANUAL — Confirm every Teacher Resource placeholder is either linked to a real resource or honestly labeled unavailable.
- [ ] MANUAL — Keep Explorer Passport download/thank-you promotion inactive until the approved file, delivery automation, privacy review, and download tests pass.
- [ ] MANUAL — Confirm whether Footer signup and Blog inline signup are intentionally placed in the production site. Their reusable components exist, but placement is a content/release decision.
- [ ] MANUAL — Confirm live WooCommerce products, formats, checkout configuration, legal pages, SEO plugin, analytics, DNS, and verification services.
- [ ] MANUAL — Confirm no staging URL, test credential, draft product, fake review, or placeholder promise is visible.

# 1. WordPress

## Reading settings

- [ ] MANUAL — Settings > Reading uses A static page.
- [ ] MANUAL — Homepage is assigned to the published Home page using front-page.php.
- [ ] MANUAL — Posts page is assigned to the published Blog page at /blog/.
- [ ] MANUAL — “Discourage search engines from indexing this site” remains enabled on private staging.
- [ ] MANUAL — “Discourage search engines” is disabled on production only after final smoke testing.
- [ ] MANUAL — Feed settings and excerpt/full-text behavior match the editorial decision.
- [ ] MANUAL — Search-engine visibility is rechecked after cache clearing and while logged out.

## Permalinks

- [ ] MANUAL — Permalink structure is approved and saved, normally Post name.
- [ ] MANUAL — Saving permalinks produces no server error.
- [ ] MANUAL — Existing public URLs retain their intended slugs.
- [ ] MANUAL — No required URL contains index.php, duplicate slashes, or avoidable query parameters.
- [ ] MANUAL — HTTP, www/apex, and old-domain variants resolve through one intentional redirect path.
- [ ] MANUAL — Redirects do not loop or chain unnecessarily.

## Timezone and localization

- [ ] MANUAL — Settings > General timezone uses the correct named region, not an accidental UTC offset.
- [ ] MANUAL — Date format, time format, week start, site language, and admin email are approved.
- [ ] MANUAL — Scheduled posts, sales, backups, and automation times match the business timezone.
- [ ] MANUAL — Copyright year and published dates display correctly.

## Homepage

- [ ] MANUAL — / returns 200 over HTTPS.
- [ ] MANUAL — Exactly one H1 is rendered.
- [ ] MANUAL — Hero copy, CTAs, imagery, featured books, testimonials, and Adventure Club copy are approved.
- [ ] MANUAL — Homepage book links prefer Paperback when a matching paperback product exists.
- [ ] MANUAL — Missing products never produce a broken product URL or false availability claim.
- [ ] MANUAL — All homepage anchor links land on the correct section.
- [ ] MANUAL — No custom-field URL points to staging, Squarespace, or a draft page.

## Posts page and publishing

- [ ] MANUAL — /blog/ returns 200 and uses the index/archive template.
- [ ] MANUAL — At least one published post renders correctly.
- [ ] MANUAL — Empty, archive, category, search, pagination, and single-post states are tested.
- [ ] MANUAL — Post dates and author attribution are accurate.
- [ ] MANUAL — Featured images, excerpts, categories, tags, related links, and CTAs are reviewed.
- [ ] MANUAL — Page 2+ pagination returns 200 and preserves correct canonicals.
- [ ] MANUAL — Drafts, private posts, and test content are not publicly accessible.

## Discussion

- [ ] MANUAL — The business has explicitly chosen whether comments are open or closed.
- [ ] MANUAL — Default discussion settings match that decision.
- [ ] MANUAL — Existing posts do not accidentally override the intended comment state.
- [ ] MANUAL — If comments are enabled, moderation, spam protection, notification routing, privacy language, and abuse handling are tested.
- [ ] MANUAL — If comments are disabled, no broken comment links or empty comment UI remains.

## Media

- [ ] MANUAL — Logo, favicon/site icon, founder photo, hero images, covers, featured images, and social images are approved.
- [ ] MANUAL — Meaningful images have accurate alt text; decorative images have empty alt text.
- [ ] MANUAL — No image displays a placeholder label unless specifically approved.
- [ ] MANUAL — WordPress-generated responsive sizes exist for older uploads that predate the theme.
- [ ] MANUAL — Cover crops remain legible on mobile and high-density screens.
- [ ] MANUAL — Uploaded originals are compressed and use supported modern formats where appropriate.
- [ ] MANUAL — No missing media URL returns 404.
- [ ] MANUAL — Attachment pages are redirected or noindexed according to the SEO plan.

## Required pages and legal content

- [ ] MANUAL — /books/ is published with Brave Hearts Books Page.
- [ ] MANUAL — /teachers/ is published with Brave Hearts Teacher Resources Page.
- [ ] MANUAL — /about/ is published with Brave Hearts About Page.
- [ ] MANUAL — /contact/ is published with Brave Hearts Contact Page.
- [ ] MANUAL — /privacy-policy/ is published, legally approved, and assigned in WordPress.
- [ ] MANUAL — /terms/ is published, legally approved, and assigned where WooCommerce expects it.
- [ ] MANUAL — All eight required URLs return 200 over HTTPS: /, /books/, /teachers/, /about/, /blog/, /contact/, /privacy-policy/, /terms/.
- [ ] MANUAL — Editor-authored content begins at H2 and does not add a second H1.

# 2. Navigation

## Header

- [ ] MANUAL — Custom logo or text fallback links to /.
- [ ] MANUAL — Primary menu order is Home, Books, Teacher Resources, About, Blog, Contact unless an approved release decision says otherwise.
- [ ] MANUAL — Current-page state is visually understandable.
- [ ] MANUAL — Header links work from every major template.
- [ ] MANUAL — No obsolete, draft, staging, Squarespace, or malformed link remains.
- [ ] MANUAL — Primary navigation has a clear accessible name.

## Footer

- [ ] MANUAL — Footer menu contains approved Books, Teacher Resources, About, Blog, Contact, Privacy Policy, Terms, and Adventure Club destinations.
- [ ] MANUAL — Privacy Policy uses the assigned WordPress privacy URL.
- [ ] MANUAL — Terms uses the assigned WooCommerce Terms page when configured.
- [ ] MANUAL — Adventure Club points to the live homepage signup anchor.
- [ ] MANUAL — Public contact email is correct, monitored, and protected operationally.
- [ ] MANUAL — Affiliate disclosure, company name, and copyright text are approved.
- [ ] MANUAL — Footer widget content is intentional and contains no test widget.
- [ ] MANUAL — Footer signup is visible and working if it is part of the approved launch scope; otherwise its omission is documented.

## Mobile navigation

- [ ] MANUAL — Menu toggle works by touch and keyboard.
- [ ] MANUAL — aria-expanded changes between false and true.
- [ ] MANUAL — aria-controls matches the primary navigation ID.
- [ ] MANUAL — Menu links remain reachable at 200% zoom.
- [ ] MANUAL — Opening the menu does not hide, overlap, or trap required content.
- [ ] MANUAL — Navigation remains usable with JavaScript blocked or failure behavior is explicitly accepted.
- [ ] MANUAL — Orientation changes do not leave the menu in an unusable state.

## Broken links and redirects

- [ ] MANUAL — Crawl the production candidate for internal 4xx/5xx links.
- [ ] MANUAL — Click every header, footer, card, product, form, legal, email, and CTA destination.
- [ ] MANUAL — External Amazon/retailer/social links open the correct destination.
- [ ] MANUAL — No link points to localhost, staging, preview, temporary download, or old hosting.
- [ ] MANUAL — Redirects preserve relevant path/query data and use the intended status code.
- [ ] MANUAL — No redirect chain exceeds one intentional hop after canonicalization.

## 404 handling

- [ ] MANUAL — A random nonexistent URL returns an actual HTTP 404 status.
- [ ] MANUAL — The branded 404 template renders with one H1.
- [ ] MANUAL — Back to Home works.
- [ ] MANUAL — 404 responses are not included in sitemaps.
- [ ] MANUAL — SEO plugin outputs the expected noindex behavior for 404 pages.
- [ ] MANUAL — Server/CDN does not replace the theme 404 with an unbranded page.

# 3. WooCommerce

## Catalog and products

- [ ] MANUAL — WooCommerce is active, updated, and connected to the intended production store.
- [ ] MANUAL — Only approved launch products are published.
- [ ] MANUAL — Each product has correct title, slug, long description, short description, price, currency, SKU/identifier, tax class, stock state, category, tags, and images.
- [ ] MANUAL — Product permalinks return 200 and use the canonical HTTPS host.
- [ ] MANUAL — Product images have accurate cover alt text.
- [ ] MANUAL — No draft, duplicate, test, hidden, or stale product appears in customer-facing cards/search.
- [ ] MANUAL — Missing products produce honest unavailable states.
- [ ] MANUAL — Featured product selection and homepage ordering are intentional.
- [ ] MANUAL — Related products are accurate and do not mix unrelated/test inventory.
- [ ] MANUAL — Product reviews and aggregate ratings represent real visible reviews only.

## Formats

- [ ] MANUAL — Paperback is clearly labeled and is the preferred primary card destination where available.
- [ ] MANUAL — Hardcover is clearly labeled and links to the correct hardcover product.
- [ ] MANUAL — Kindle is clearly labeled and links to the approved WooCommerce or external Kindle destination.
- [ ] MANUAL — Format attributes and/or titles allow cards to detect Paperback, Hardcover, and Kindle correctly.
- [ ] MANUAL — Price, stock, shipping, and availability claims match each format.
- [ ] MANUAL — No format-SKU duplication makes the Books page look like duplicate adventures.
- [ ] MANUAL — Product search/Shop Formats links return relevant results.
- [ ] MANUAL — Preorder, coming-soon, out-of-stock, and unavailable states are not confused.

## Cart and checkout

- [ ] MANUAL — Add to cart works for each shippable format.
- [ ] MANUAL — Cart quantity update, removal, coupon behavior, subtotal, and totals are correct.
- [ ] MANUAL — Checkout works for guest and intended account flows.
- [ ] MANUAL — Billing/shipping fields, validation, privacy text, Terms acceptance, and error states are correct.
- [ ] MANUAL — Checkout is usable by keyboard and on mobile.
- [ ] MANUAL — Cart and checkout are noindexed and absent from the sitemap.
- [ ] MANUAL — Abandoned/test carts do not leak customer data.

## Taxes

- [ ] MANUAL — Tax nexus and collection decisions are approved by the responsible business/accounting owner.
- [ ] MANUAL — WooCommerce tax settings, inclusive/exclusive display, rounding, and product tax classes are correct.
- [ ] MANUAL — Test addresses in required jurisdictions calculate expected tax.
- [ ] MANUAL — Tax appears correctly in cart, checkout, order, refund, and emails.
- [ ] MANUAL — External tax service, if used, is in production mode and healthy.

## Shipping

- [ ] MANUAL — Shipping origin, zones, methods, rates, free-shipping thresholds, package dimensions, and weights are correct.
- [ ] MANUAL — Domestic and any approved international addresses return intended rates.
- [ ] MANUAL — Unsupported destinations receive a clear honest message.
- [ ] MANUAL — Kindle/digital items do not incorrectly require shipping.
- [ ] MANUAL — Paperback/Hardcover shipping totals match operational expectations.
- [ ] MANUAL — Label/fulfillment integration is tested if applicable.

## Payment gateway

- [ ] MANUAL — Production gateway credentials are configured outside the theme.
- [ ] MANUAL — Test/sandbox mode is disabled only when production testing is approved.
- [ ] MANUAL — A low-value real transaction succeeds on each enabled payment method.
- [ ] MANUAL — Declined payment, authentication/3DS, timeout, duplicate-submit, and retry states are tested.
- [ ] MANUAL — Gateway webhooks report healthy and reach the production HTTPS URL.
- [ ] MANUAL — Refund and partial refund are tested.
- [ ] MANUAL — Secrets are not stored in theme files or release documentation.

## WooCommerce emails

- [ ] MANUAL — From name/address and reply-to behavior are approved.
- [ ] MANUAL — SMTP/transactional email provider is authenticated with SPF, DKIM, and DMARC aligned.
- [ ] MANUAL — New order, processing, completed, refunded, failed, cancelled, customer note, reset password, and account emails are tested as applicable.
- [ ] MANUAL — Email links use the production HTTPS domain.
- [ ] MANUAL — Emails render on desktop and mobile and do not enter spam in owned tests.
- [ ] MANUAL — Internal order notifications reach the monitored recipients.

## Customer account and order confirmation

- [ ] MANUAL — Account creation, login, logout, password reset, address update, and order history work.
- [ ] MANUAL — Account and password forms have labels, errors, and keyboard access.
- [ ] MANUAL — Order-received page shows the correct order only to the correct customer/session.
- [ ] MANUAL — Confirmation number, products, formats, totals, taxes, shipping, billing, and payment method are accurate.
- [ ] MANUAL — Confirmation page is noindexed and excluded from analytics content reports where appropriate.
- [ ] MANUAL — One complete test order is fulfilled, refunded, and removed/flagged from production reporting according to policy.

# 4. Mailchimp

## Connection and audience

- [ ] MANUAL — MC4WP reports Connected with the production API key.
- [ ] MANUAL — Mailchimp for WooCommerce reports connected to the intended store and single audience.
- [ ] MANUAL — The published MC4WP Adventure Club form resolves to exactly one production audience.
- [ ] MANUAL — Single opt-in is the approved configuration; test contacts become Subscribed, not Pending.
- [ ] MANUAL — No API key, audience ID, email address, or provider error appears in public source/URLs.

## Signup rendering

- [ ] MANUAL — Homepage Adventure Club form renders with the existing Brave Hearts UI.
- [ ] MANUAL — Homepage signup works at desktop, tablet, and mobile widths.
- [ ] MANUAL — Teacher signup renders and preserves teacher segmentation.
- [ ] MANUAL — Lead magnet signup renders and preserves lead-magnet segmentation.
- [ ] MANUAL — Footer signup renders and submits if included in launch scope.
- [ ] MANUAL — Blog inline signup renders and submits if included in launch scope.
- [ ] MANUAL — Labels, keyboard focus, disabled/unavailable state, honeypot, success, and error messages are accessible.
- [ ] MANUAL — No signup placeholder endpoint appears in rendered form actions.
- [ ] MANUAL — Refreshing the result page does not repeat the POST.
- [ ] MANUAL — Submitted email addresses never appear in redirect URLs.

## Delivery and field mapping

- [ ] MANUAL — New owned test address submitted through Homepage arrives in Mailchimp.
- [ ] MANUAL — Existing subscribed address can submit again without creating a duplicate contact.
- [ ] MANUAL — Adventure Club tag is active.
- [ ] MANUAL — AUDIENCE merge field contains the expected audience_type.
- [ ] MANUAL — LEADMAG merge field contains the expected lead_magnet when applicable.
- [ ] MANUAL — SOURCE merge field contains the correct local source_page.
- [ ] MANUAL — Teacher signup records teachers and the approved teacher lead magnet.
- [ ] MANUAL — Lead magnet signup records the selected resource.
- [ ] MANUAL — Footer and Blog contexts record the expected source and audience values.
- [ ] MANUAL — Failed provider tests show only friendly Brave Hearts errors.
- [ ] MANUAL — MC4WP/server logs are reviewed privately after tests.

## Tags and welcome email

- [ ] MANUAL — Adventure Club tag exists or is created by the successful API call.
- [ ] MANUAL — No unapproved future tag is applied.
- [ ] MANUAL — Teacher, Explorer Passport, Customer, Blog Subscriber, Book Interest, School Visit, Homeschool, and Library tags are used only after their automation rules are approved.
- [ ] MANUAL — Welcome automation triggers exactly once for the intended new subscriber state.
- [ ] MANUAL — Welcome email uses approved subject, sender, reply-to, brand copy, address, unsubscribe link, and privacy language.
- [ ] MANUAL — Explorer Passport/resource delivery is not promised unless the approved file and automation work.
- [ ] MANUAL — Welcome links use production HTTPS URLs and pass click testing.
- [ ] MANUAL — Unsubscribe and suppression behavior is tested and respected.
- [ ] MANUAL — Test contacts are labeled or removed according to reporting policy.

# 5. Contact Forms

## General contact

- [ ] MANUAL — Decide whether a functioning web contact form is required at launch.
- [ ] MANUAL — If required, configure a secure provider/first-party endpoint with server validation, nonce/origin checks, spam controls, rate limiting, safe notifications, logging, retention, and privacy review.
- [ ] MANUAL — If no endpoint is configured, confirm the form is disabled and clearly says to use direct email.
- [ ] MANUAL — Public email address is correct and monitored by an assigned owner.
- [ ] MANUAL — Valid submission reaches the intended recipient/CRM without exposing API details.
- [ ] MANUAL — Required fields, labels, validation, success, failure, retry, and mobile states pass.
- [ ] MANUAL — No sensitive student or child information is requested or submitted during tests.

## Read-aloud

- [ ] MANUAL — /contact/?inquiry=read-aloud#contact-form selects Read-Aloud Request.
- [ ] MANUAL — Teacher-page Request a Read-Aloud links reach the same correct state.
- [ ] MANUAL — Inquiry routing reaches the assigned school-visit/read-aloud owner if the form is active.
- [ ] MANUAL — Location, timing, audience, organization, and message fields are handled according to privacy policy.
- [ ] MANUAL — Availability disclaimer remains visible and accurate.
- [ ] MANUAL — Direct-email fallback includes enough guidance if the form remains disabled.

## Teacher inquiry

- [ ] MANUAL — Teacher and school/library inquiry paths select the correct inquiry type.
- [ ] MANUAL — School, library, homeschool, bulk-order, and media inquiries route correctly if active.
- [ ] MANUAL — Qualified opportunities enter HubSpot or another approved workflow only if that integration is live.
- [ ] MANUAL — Teacher inquiries do not accidentally enter the general Adventure Club automation without consent.
- [ ] MANUAL — Notification ownership and response-time expectation are documented.

# 6. SEO

## Rank Math or approved alternative

- [ ] MANUAL — Exactly one primary SEO plugin is active: Rank Math or Yoast, never both.
- [ ] MANUAL — Site representation is Organization: Brave Hearts Publishing.
- [ ] MANUAL — Approved organization logo and real social profiles are configured.
- [ ] MANUAL — Posts use Article/BlogPosting; Pages use WebPage; Products use compatible Product schema.
- [ ] MANUAL — Visible breadcrumbs remain off unless a design-approved placement exists.
- [ ] MANUAL — No second plugin/theme feature duplicates titles, canonicals, social metadata, sitemap, or schema.

## Metadata and canonicals

- [ ] MANUAL — Homepage, Books, Teachers, About, Contact, Blog, Privacy, and Terms have approved unique SEO titles and descriptions.
- [ ] MANUAL — Every sampled indexable page has exactly one title element and one meta description.
- [ ] MANUAL — Every sampled indexable URL has exactly one self-referencing HTTPS canonical.
- [ ] MANUAL — Paginated archives self-canonicalize correctly rather than pointing all pages to page 1.
- [ ] MANUAL — Final canonicals use the chosen www/apex hostname.
- [ ] MANUAL — Open Graph and Twitter/X output contains one coherent title, description, URL, and approved image.
- [ ] MANUAL — Approved 1200 × 630 social images are assigned for Homepage, Books, Teachers, representative posts, and products.

## Schema

- [ ] MANUAL — One coherent Organization/WebSite/WebPage graph appears.
- [ ] MANUAL — Blog posts expose accurate Article, date, author, image, and publisher data.
- [ ] MANUAL — Products expose accurate Product/Offer name, image, description, SKU, price, currency, availability, and real ratings.
- [ ] MANUAL — No duplicate Product, Article, Organization, or BreadcrumbList graph exists.
- [ ] MANUAL — Representative post and product pass Google Rich Results Test without critical errors.
- [ ] MANUAL — Schema claims match visible page content.

## Sitemap and robots

- [ ] MANUAL — Selected SEO plugin sitemap index returns 200.
- [ ] MANUAL — Sitemap contains canonical indexable pages, posts, products, and only curated taxonomies.
- [ ] MANUAL — Sitemap excludes noindex, search, 404, cart, checkout, account, result, placeholder, draft, private, and thank-you URLs.
- [ ] MANUAL — /robots.txt returns 200, permits required assets, and references the final sitemap URL.
- [ ] MANUAL — robots.txt does not block pages whose noindex directive must be crawled.
- [ ] MANUAL — Six critical marketing URLs are indexable: /, /books/, /teachers/, /about/, /blog/, /contact/.
- [ ] MANUAL — Privacy/Terms index decision matches the approved SEO/legal plan.
- [ ] MANUAL — Date, thin tag, single-author, attachment, and empty archive rules match the SEO plan.

## Search Console, Bing, and Pinterest

- [ ] MANUAL — Google Search Console Domain property is verified by DNS.
- [ ] MANUAL — Final sitemap is submitted and processed without blocking errors.
- [ ] MANUAL — Six critical URLs pass URL Inspection and live test.
- [ ] MANUAL — Homepage and priority hubs are requested for indexing only after final validation.
- [ ] MANUAL — Bing Webmaster Tools is verified/imported and uses the same sitemap.
- [ ] MANUAL — Six critical URLs pass Bing inspection/markup review.
- [ ] MANUAL — IndexNow, if enabled, has one owner/integration and no duplicate submission path.
- [ ] MANUAL — Pinterest business account claims the canonical domain, preferably via DNS TXT.
- [ ] MANUAL — Search Console Manual actions and Security issues report clear.

# 7. Analytics

## GA4

- [ ] MANUAL — One GA4 property and one Web stream use the final canonical HTTPS domain, business timezone, and reporting currency.
- [ ] MANUAL — GA4 is installed through one approved maintained integration, not hard-coded into the theme.
- [ ] MANUAL — GA4 is not installed both directly and through GTM.
- [ ] MANUAL — Consent behavior is approved and tested for applicable jurisdictions.
- [ ] MANUAL — Internal traffic and unwanted referral exclusions are configured.
- [ ] MANUAL — One page_view fires per page without duplication.
- [ ] MANUAL — Approved Enhanced Measurement events work without inflating counts.
- [ ] MANUAL — Outbound retailer/product clicks, successful signup, and WooCommerce events are defined and tested if in launch scope.
- [ ] MANUAL — No email address, student data, or other prohibited personal data enters analytics parameters.

## Realtime and DebugView

- [ ] MANUAL — Realtime shows an owned visit on the production hostname.
- [ ] MANUAL — DebugView shows the intended page and event sequence.
- [ ] MANUAL — Cross-domain/referral behavior is correct for payment providers and approved external checkout paths.
- [ ] MANUAL — Purchase event fires once with correct transaction ID, value, tax, shipping, currency, and items.
- [ ] MANUAL — Test transactions are excluded or labeled according to reporting policy.
- [ ] MANUAL — Ad blockers/consent denial produce expected behavior without breaking the site.

## Verification services

- [ ] MANUAL — Search Console ownership remains verified after DNS cutover.
- [ ] MANUAL — Search Console is linked to GA4.
- [ ] MANUAL — Bing ownership remains verified/imported.
- [ ] MANUAL — Pinterest ownership remains verified.
- [ ] MANUAL — Verification tokens are stored through DNS or maintained integrations, not theme code.
- [ ] MANUAL — Optional Meta Pixel/GTM remain disabled unless approved, consented, documented, and tested.

# 8. Accessibility

## Keyboard and focus

- [ ] MANUAL — Complete all critical journeys using keyboard only.
- [ ] MANUAL — Skip link appears on focus and moves to #main.
- [ ] MANUAL — Header, mobile menu, cards, forms, product controls, cart, checkout, account, pagination, and footer are reachable in logical order.
- [ ] MANUAL — No keyboard trap exists.
- [ ] MANUAL — Focus is always visible against light, dark, image, modal, and form backgrounds.
- [ ] MANUAL — Focus does not disappear behind sticky content.
- [ ] MANUAL — Enter and Space activate controls according to native semantics.

## Contrast and zoom

- [ ] MANUAL — Text and interactive controls meet WCAG AA contrast against actual production images/colors.
- [ ] MANUAL — Focus indicators are clearly visible.
- [ ] MANUAL — Content remains usable at 200% browser zoom.
- [ ] MANUAL — Text spacing overrides do not clip or overlap content.
- [ ] MANUAL — Information is not conveyed by color alone.
- [ ] MANUAL — Disabled and error states remain understandable.

## Headings, landmarks, labels, and ARIA

- [ ] MANUAL — Every major page has exactly one H1.
- [ ] MANUAL — Heading order is logical without visual-only level jumps.
- [ ] MANUAL — header, nav, main, article/section where appropriate, and footer landmarks are announced correctly.
- [ ] MANUAL — All visible form controls have programmatic labels.
- [ ] MANUAL — Required fields, instructions, errors, and success messages are associated and announced.
- [ ] MANUAL — Mobile navigation aria-expanded and aria-controls are correct.
- [ ] MANUAL — Decorative images are ignored; meaningful images have useful alternatives.
- [ ] MANUAL — Links have understandable accessible names outside visual context.
- [ ] MANUAL — Automated accessibility scan has no critical issue.
- [ ] MANUAL — A manual screen-reader sample covers Homepage, Books, Teachers, Contact, a post, a product, cart, and checkout.

# 9. Performance

## Core Web Vitals

- [ ] MANUAL — Measure Homepage, Books, Teachers, Blog, representative post, product, cart, and checkout on production-like hosting.
- [ ] MANUAL — LCP, INP, and CLS meet the agreed launch thresholds, targeting current “good” Core Web Vitals ranges.
- [ ] MANUAL — Hero/LCP image is intentional, optimized, and requested with correct priority.
- [ ] MANUAL — Layout does not shift when images, fonts, forms, notices, or product content load.
- [ ] MANUAL — Third-party scripts are reviewed for measurable cost.
- [ ] MANUAL — Field data monitoring is planned after Search Console/CrUX data becomes available.

## Caching and compression

- [ ] MANUAL — Page caching is configured with cart, checkout, account, admin, and form-result exclusions.
- [ ] MANUAL — Object cache is configured only if supported and tested.
- [ ] MANUAL — Browser cache headers and CDN behavior are correct.
- [ ] MANUAL — Brotli or gzip compression is active for text assets.
- [ ] MANUAL — HTML/CSS/JS optimization does not break navigation, forms, checkout, or analytics.
- [ ] MANUAL — All caches can be purged during launch and rollback.
- [ ] MANUAL — Logged-in and logged-out cache behavior is tested separately.

## Images, fonts, and assets

- [ ] MANUAL — WordPress responsive srcset/sizes output appears on theme-controlled images.
- [ ] MANUAL — Below-the-fold images lazy-load where appropriate.
- [ ] MANUAL — Hero/LCP image is not incorrectly lazy-loaded.
- [ ] MANUAL — Covers and content images use suitable dimensions and compression.
- [ ] MANUAL — No oversized hidden image is downloaded unnecessarily.
- [ ] MANUAL — Google Fonts availability/privacy/performance decision is approved; fallback fonts render acceptably.
- [ ] MANUAL — Browser console has no missing asset, mixed-content, JavaScript, or source-map error that affects launch.
- [ ] MANUAL — No unnecessary page-builder or heavy front-end dependency is loaded.

# 10. Mobile QA

## iPhone / iOS Safari

- [ ] MANUAL — Test a current iPhone-sized device or simulator in portrait and landscape.
- [ ] MANUAL — Header, logo, menu, hero, cards, forms, footer, cart, checkout, and account fit without horizontal scrolling.
- [ ] MANUAL — Inputs do not trigger unusable zoom or hidden labels.
- [ ] MANUAL — Tap targets are comfortably usable.
- [ ] MANUAL — Sticky/browser chrome does not cover controls.
- [ ] MANUAL — Payment flow and autofill work.
- [ ] MANUAL — Social/email/tel links open intended apps.

## Android / Chrome

- [ ] MANUAL — Test a current Android phone-sized device or emulator in portrait and landscape.
- [ ] MANUAL — Navigation, cards, forms, footer, cart, checkout, and account remain usable.
- [ ] MANUAL — Back button behavior is correct after forms, cart, and checkout steps.
- [ ] MANUAL — Autofill, keyboard types, validation, and payment authentication work.
- [ ] MANUAL — Slow-network behavior is understandable and does not duplicate submissions.
- [ ] MANUAL — No Android-specific clipping or font substitution breaks layout.

## Tablet

- [ ] MANUAL — Test iPadOS Safari and/or representative Android tablet at portrait and landscape widths.
- [ ] MANUAL — Breakpoints do not create awkward one/two-column transitions.
- [ ] MANUAL — Header and footer do not strand links between desktop and mobile layouts.
- [ ] MANUAL — Product cards, forms, and checkout use available space without overly long lines.
- [ ] MANUAL — Touch and keyboard input both work where a hardware keyboard may be attached.

# 11. Browser QA

## Chrome

- [ ] MANUAL — Latest stable desktop Chrome passes all critical journeys.
- [ ] MANUAL — Console and Network panels show no launch-affecting error.
- [ ] MANUAL — Responsive images, fonts, caching, forms, analytics, and checkout behave correctly.

## Safari

- [ ] MANUAL — Latest supported macOS Safari passes all critical journeys.
- [ ] MANUAL — Flex/grid layout, focus rings, date/time rendering, form controls, cookies, payment, and redirects behave correctly.
- [ ] MANUAL — Private browsing produces an acceptable consent/cart/session experience.

## Firefox

- [ ] MANUAL — Latest stable Firefox passes all critical journeys.
- [ ] MANUAL — Keyboard focus, form validation, responsive images, print/download behavior, and checkout work.
- [ ] MANUAL — Tracking protection does not break core site behavior.

## Edge

- [ ] MANUAL — Latest stable Edge passes all critical journeys.
- [ ] MANUAL — Autofill, account, cart, checkout, downloads, and analytics behavior are acceptable.
- [ ] MANUAL — No Chromium assumption hides an Edge-specific operational issue.

## Shared browser matrix

- [ ] MANUAL — Test logged-out visitor, logged-in administrator, and customer/account states.
- [ ] MANUAL — Test normal and private/incognito sessions.
- [ ] MANUAL — Clear cookies/cache and retest one clean first visit.
- [ ] MANUAL — Record browser/OS versions and evidence in the release ticket.

# 12. Security

## HTTPS and transport

- [ ] MANUAL — Valid TLS certificate covers apex and www as required.
- [ ] MANUAL — Certificate chain, renewal, and expiration monitoring are healthy.
- [ ] MANUAL — HTTP redirects to canonical HTTPS in one hop.
- [ ] MANUAL — No mixed-content request exists.
- [ ] MANUAL — HSTS is enabled only after HTTPS behavior and rollback implications are approved.
- [ ] MANUAL — Secure cookies and proxy/CDN HTTPS detection work correctly.

## Accounts and access

- [ ] MANUAL — No default/admin-style shared username is used.
- [ ] MANUAL — Every administrator has an individual least-privilege account and MFA where supported.
- [ ] MANUAL — Former vendors/users are removed.
- [ ] MANUAL — Application Passwords are inventoried, named, least-privilege, rotated, or revoked if unused.
- [ ] MANUAL — Hosting, DNS, registrar, CDN, payment, Mailchimp, analytics, and email accounts use MFA and recovery ownership.
- [ ] MANUAL — Emergency access procedure is documented and tested.

## Updates and hardening

- [ ] MANUAL — WordPress core, required plugins, and the theme are backed up before updates.
- [ ] MANUAL — WordPress core and plugin updates are current, supported, and approved after staging regression tests.
- [ ] MANUAL — Unused themes/plugins are removed, not merely deactivated, except an approved fallback theme if policy requires it.
- [ ] MANUAL — Theme updates use the controlled release artifact and do not overwrite uncommitted production edits.
- [ ] MANUAL — File editing, XML-RPC, REST exposure, login protection, firewall, malware scanning, and security headers match hosting/security policy.
- [ ] MANUAL — No secret, API key, password, private key, customer export, or debug log is public.
- [ ] MANUAL — WP_DEBUG and display_errors are off in production; errors log privately.
- [ ] MANUAL — Security plugin/firewall does not block checkout, webhooks, Mailchimp, cron, or verification crawlers.

## Backups and recovery

- [ ] MANUAL — Automated database and file backups are active with an off-host copy.
- [ ] MANUAL — Backup schedule and retention meet business recovery requirements.
- [ ] MANUAL — A restore has been tested in a separate environment.
- [ ] MANUAL — Pre-launch database, uploads, theme/plugin, configuration, and DNS snapshots are timestamped.
- [ ] MANUAL — Rollback steps, owner, decision threshold, and old-host availability are documented.
- [ ] MANUAL — Uptime, certificate, error, security, backup-failure, and broken-link alerts reach monitored recipients.

# 13. Launch Sequence

## Pre-cutover preparation — T-7 days to T-24 hours

- [ ] MANUAL — Freeze nonessential content, plugin, theme, product, DNS, payment, and automation changes.
- [ ] MANUAL — Complete staging QA and record all approved waivers.
- [ ] MANUAL — Confirm launch owner, rollback owner, communication channel, and maintenance window.
- [ ] MANUAL — Export current DNS zone and preserve A/AAAA/CNAME/MX/TXT/CAA records.
- [ ] MANUAL — Verify mail records: MX, SPF, DKIM, and DMARC.
- [ ] MANUAL — Lower relevant DNS TTL 24–48 hours before cutover where possible.
- [ ] MANUAL — Pre-provision/validate SSL for the final host if the provider supports it.
- [ ] MANUAL — Add Search Console and Pinterest DNS verification records without changing web routing.
- [ ] MANUAL — Confirm old hosting remains available through the stabilization window.
- [ ] MANUAL — Prepare cache purge, rollback DNS values, status message, and smoke-test worksheet.

## Exact cutover order

### 1. Freeze and go/no-go

- [ ] MANUAL — Announce change freeze.
- [ ] MANUAL — Verify every launch-approval blocker below is clear or explicitly waived.
- [ ] MANUAL — Record GO decision, approver, and timestamp.

### 2. Backup

- [ ] MANUAL — Create final database backup.
- [ ] MANUAL — Create final uploads/files backup.
- [ ] MANUAL — Export theme/plugin/configuration inventory.
- [ ] MANUAL — Reconfirm DNS-zone backup and rollback values.
- [ ] MANUAL — Verify backups are readable and record storage location.

### 3. DNS

- [ ] MANUAL — Apply only the approved web-hosting DNS changes.
- [ ] MANUAL — Preserve mail and verification records.
- [ ] MANUAL — Record old/new values and exact cutover time.
- [ ] MANUAL — Check propagation using multiple resolvers and networks.

### 4. SSL and canonical routing

- [ ] MANUAL — Issue/attach the production certificate after routing if pre-provisioning was unavailable.
- [ ] MANUAL — Verify apex and www certificate coverage.
- [ ] MANUAL — Force the chosen canonical HTTPS hostname.
- [ ] MANUAL — Check for mixed content and redirect loops.
- [ ] MANUAL — Purge host, page, object, CDN, and browser caches.

### 5. Immediate infrastructure smoke test

- [ ] MANUAL — Confirm homepage, wp-admin, all critical URLs, robots.txt, sitemap, product, cart, checkout, account, and 404 responses.
- [ ] MANUAL — Confirm database writes, media, cron, email, payment webhooks, and server logs.
- [ ] MANUAL — Roll back immediately if site access, admin access, database integrity, checkout, or TLS is critically broken.

### 6. Search visibility

- [ ] MANUAL — Disable Settings > Reading search-engine discouragement.
- [ ] MANUAL — Confirm critical pages no longer output accidental noindex.
- [ ] MANUAL — Confirm staging remains blocked/noindexed and cannot become canonical.

### 7. Analytics

- [ ] MANUAL — Verify GA4 Realtime and DebugView on the production hostname.
- [ ] MANUAL — Verify one page_view and approved events without duplication.
- [ ] MANUAL — Confirm consent and purchase measurement.

### 8. Mailchimp and contact

- [ ] MANUAL — Submit owned tests through Homepage, Teacher, Lead magnet, and any approved Footer/Blog placements.
- [ ] MANUAL — Confirm Subscribed status, Adventure Club tag, merge fields, welcome email, and production links.
- [ ] MANUAL — Test contact/read-aloud/teacher inquiry or confirm disabled-email fallback decision.
- [ ] MANUAL — Confirm transactional WooCommerce email.

### 9. Search Console, Bing, and Pinterest

- [ ] MANUAL — Confirm ownership remains verified after DNS cutover.
- [ ] MANUAL — Add/import the exact canonical HTTPS property where required.
- [ ] MANUAL — Run live inspection on the homepage and priority pages.

### 10. Sitemap submission

- [ ] MANUAL — Recheck sitemap host, status, canonicals, indexability, and exclusions.
- [ ] MANUAL — Submit/resubmit the sitemap to Google and Bing.
- [ ] MANUAL — Request indexing for Homepage, Books, Teachers, About, Blog, and Contact only after live inspection passes.

### 11. Final customer-journey testing

- [ ] MANUAL — Complete header/footer/mobile/404 navigation.
- [ ] MANUAL — Complete one real payment order, confirmation email/page, account view, and approved refund.
- [ ] MANUAL — Complete Mailchimp and contact paths.
- [ ] MANUAL — Recheck accessibility, mobile, browsers, performance, SEO source, analytics, and logs.
- [ ] MANUAL — Record final PASS decision and timestamp.

### 12. Stabilize

- [ ] MANUAL — Keep change freeze during the first-hour observation window.
- [ ] MANUAL — Maintain old host and rollback capability.
- [ ] MANUAL — Raise DNS TTL only after the site is stable.
- [ ] MANUAL — Communicate launch completion and monitoring ownership.

## Rollback triggers

- [ ] MANUAL — Roll back for unavailable site/admin, data corruption, persistent TLS failure, broken checkout/payment, widespread 5xx, uncontrolled email failure, security incident, or customer-data exposure.
- [ ] MANUAL — Consider rollback for severe navigation/mobile/accessibility breakage, wrong pricing/tax/shipping, duplicate analytics/purchases, or broken form routing that cannot be safely disabled.
- [ ] MANUAL — Rollback owner records decision, time, symptom, actions, and customer impact.
- [ ] MANUAL — Restore DNS/app/database only through the documented path; do not improvise destructive changes.
- [ ] MANUAL — Preserve logs and failed state for later diagnosis.

# 14. Post Launch

## First hour

- [ ] MANUAL — Monitor uptime, TLS, DNS propagation, server/PHP logs, firewall, cache/CDN, and database health continuously.
- [ ] MANUAL — Recheck Homepage, critical pages, wp-admin, 404, robots, sitemap, product, cart, checkout, account, and contact paths.
- [ ] MANUAL — Verify one real order, payment webhook, order confirmation, and transactional emails.
- [ ] MANUAL — Verify one Mailchimp signup, tag, merge fields, and welcome email.
- [ ] MANUAL — Verify GA4 Realtime and no duplicate page/purchase events.
- [ ] MANUAL — Check Search Console/Bing live inspection and ownership.
- [ ] MANUAL — Review customer-facing forms and mobile navigation from an external network.
- [ ] MANUAL — Keep rollback owner available and avoid nonessential changes.

## First day

- [ ] MANUAL — Review uptime, errors, 404s, redirect logs, security alerts, cron, backups, email delivery, payment failures, refunds, and abandoned checkouts.
- [ ] MANUAL — Check GA4 traffic source/hostname/event sanity.
- [ ] MANUAL — Confirm Search Console and Bing accepted the sitemap.
- [ ] MANUAL — Check Mailchimp growth, tags, merge fields, bounces, unsubscribes, and automation activity.
- [ ] MANUAL — Check Contact/read-aloud/teacher inquiry delivery or monitored direct inbox.
- [ ] MANUAL — Run broken-link and mixed-content scans.
- [ ] MANUAL — Record issues, owners, severity, and planned fixes.

## First week

- [ ] MANUAL — Review Core Web Vitals lab results, uptime, slow pages, top 404s, crawl/indexing reports, sitemap coverage, rich-result errors, and mobile usability.
- [ ] MANUAL — Reconcile WooCommerce orders, taxes, shipping, gateway settlements, refunds, inventory, and emails.
- [ ] MANUAL — Reconcile GA4 purchases against WooCommerce without expecting perfect real-time attribution.
- [ ] MANUAL — Review Mailchimp delivery, engagement, complaints, tagging, duplicates, and welcome automation.
- [ ] MANUAL — Review contact response time and school/read-aloud routing.
- [ ] MANUAL — Confirm backups completed and perform a spot restore/check.
- [ ] MANUAL — Fix launch defects through a controlled release and rerun impacted checklist sections.
- [ ] MANUAL — Decide whether old hosting can remain longer; do not shut it down solely because DNS appears propagated.

## First month

- [ ] MANUAL — Review Search Console/Bing indexing, queries, pages, crawl errors, canonicals, rich results, Core Web Vitals, and manual/security reports.
- [ ] MANUAL — Review GA4 acquisition, engagement, conversions, checkout funnel, outbound book clicks, and data quality.
- [ ] MANUAL — Review WooCommerce revenue, conversion, product/format performance, failed orders, refunds, tax/shipping accuracy, and customer support.
- [ ] MANUAL — Review Mailchimp list growth, source fields, tags, welcome performance, unsubscribes, bounces, and complaints.
- [ ] MANUAL — Review uptime, security, backups, update cadence, certificate renewal, performance, and operational ownership.
- [ ] MANUAL — Raise DNS TTL to the long-term value if not already done.
- [ ] MANUAL — Retire old hosting only after backup retention, rollback, email, assets, redirects, and ownership are confirmed.
- [ ] MANUAL — Archive launch evidence and retrospective.
- [ ] MANUAL — Create prioritized follow-up work for Phase 9 — Knowledge & Discovery Engine.

# Launch approval gate

Launch status: [ ] READY  [ ] CONDITIONAL  [ ] NO-GO

The release is NO-GO unless every item below is complete or has an explicit signed waiver:

- [ ] Final backups exist and restore/rollback paths are credible.
- [ ] DNS and mail records are backed up; cutover and rollback owners are present.
- [ ] SSL and canonical HTTPS routing are ready.
- [ ] All critical URLs and navigation return the expected status/destination.
- [ ] No unapproved placeholder, test content, staging URL, or false availability claim is public.
- [ ] WooCommerce pricing, tax, shipping, payment, checkout, order confirmation, email, and account flows pass.
- [ ] Mailchimp delivery, Adventure Club tag, merge fields, and welcome path pass.
- [ ] Contact form is functional or the disabled/direct-email fallback is explicitly approved.
- [ ] Privacy Policy, Terms, consent, and customer communications are approved.
- [ ] Exactly one SEO plugin owns metadata/schema/sitemaps; search visibility is correct.
- [ ] Sitemap, robots, canonicals, social metadata, schema, Search Console, Bing, and Pinterest pass the approved scope.
- [ ] GA4 and required verification tools are installed once and validated.
- [ ] Critical keyboard, focus, contrast, heading, label, ARIA, skip-link, mobile, and browser tests pass.
- [ ] Core Web Vitals/performance have no known launch-critical failure.
- [ ] Security, backups, updates, monitoring, logging, and account access meet policy.
- [ ] Business owner, QA lead, technical owner, and rollback owner sign below.

## Sign-off

- [ ] Business owner — Name/signature/date: ______________________________
- [ ] QA lead — Name/signature/date: ______________________________
- [ ] Technical owner — Name/signature/date: ______________________________
- [ ] Commerce owner — Name/signature/date: ______________________________
- [ ] Marketing/SEO/analytics owner — Name/signature/date: ______________________________
- [ ] Rollback owner — Name/signature/date: ______________________________

## Release outcome

- [ ] Launch completed at: ______________________________
- [ ] Rollback window ends at: ______________________________
- [ ] Old hosting retirement date: ______________________________
- [ ] First-day review completed by: ______________________________
- [ ] First-week review completed by: ______________________________
- [ ] First-month review completed by: ______________________________
- [ ] Retrospective/document archive location: ______________________________
