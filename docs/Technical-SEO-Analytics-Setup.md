# Technical SEO & Analytics Setup

Audit date: June 29, 2026
Scope: Brave Hearts Publishing WordPress theme, Phase 8C

## Executive status

The theme is compatible with common SEO plugins and correctly delegates document metadata to WordPress and the selected plugin. It enables WordPress title-tag support, calls wp_head(), and does not hard-code a title element, meta description, canonical URL, Open Graph tags, Twitter card tags, robots directives, or JSON-LD schema.

Choose one primary SEO plugin. Do not run Rank Math and Yoast together because duplicate titles, canonicals, Open Graph tags, and schema can create conflicting signals.

No tracking ID, verification secret, plugin setting, schema graph, robots rule, breadcrumb output, or social-image placeholder is added by the theme in this phase.

## Theme compatibility audit

### Passed

- WordPress owns document titles through add_theme_support('title-tag').
- header.php calls wp_head(), allowing SEO plugins and analytics integrations to output approved head markup.
- The site has one main landmark with the skip link targeting it.
- Major templates have a theme-controlled H1.
- Blog home, archives, search results, pagination, single posts, 404 pages, and featured images have working template output.
- WordPress responsive attachment functions generate theme-controlled images.
- No theme-level noindex, nofollow, robots.txt rule, canonical, social tag, or schema output exists.
- No page-builder dependency exists.
- No WooCommerce template override is present.

### Theme-level fix made in Phase 8C

Single posts and blog cards now expose published dates through semantic time elements with machine-readable datetime values and show linked author attribution. Categories remain visible on single posts. SEO plugins remain responsible for Article, Person, and breadcrumb schema.

### Manual content caveat

The standard page template supplies the page-title H1. Editors must begin page content at H2 and must not insert another H1. Custom hero templates already supply their H1.

## Recommended SEO plugin setup

### Preferred path: Rank Math

Rank Math is the recommended default for this site because one configuration can manage titles, descriptions, canonicals, XML sitemaps, robots directives, Open Graph/Twitter output, organization schema, Article schema, and WooCommerce Product schema.

Recommended modules/settings:

1. Run the setup wizard with the site represented as an Organization.
2. Enter Brave Hearts Publishing as the organization name.
3. Upload the approved square organization logo and add real social profile URLs.
4. Enable Sitemap, Schema, and WooCommerce support.
5. Enable Open Graph and select Summary Card with Large Image for Twitter/X.
6. Set a real default social image only after a 1200 × 630 brand asset is approved.
7. Configure Posts as Article/BlogPosting and Pages as WebPage.
8. Configure Products as WooCommerce Product, then validate that only one Product graph is emitted.
9. Configure index/noindex rules from the indexing plan below.
10. Leave visible breadcrumbs off unless a later design-approved placement is chosen. Breadcrumb schema may remain plugin-managed if it is valid and non-duplicated.
11. Do not enable a second sitemap, schema, social, or redirect plugin that duplicates these responsibilities.

Rank Math documentation:

- Setup: https://rankmath.com/kb/how-to-setup/
- Sitemaps: https://rankmath.com/kb/configure-sitemaps/
- Open Graph: https://rankmath.com/kb/open-graph-meta-tags/
- Noindex: https://rankmath.com/kb/how-to-noindex-urls/
- WooCommerce Product schema: https://rankmath.com/kb/woocommerce-product-schema/

### Acceptable alternative: Yoast SEO

Yoast is fully compatible with the theme. If Yoast is selected:

1. Complete First-time configuration and represent the site as an Organization.
2. Enable XML sitemaps and Open Graph/social sharing.
3. Configure Posts, Pages, Products, Categories, Product Categories, and archives according to the indexing plan.
4. Set social profiles and an approved default social image.
5. Keep Yoast as the only owner of titles, descriptions, canonicals, Open Graph, Twitter cards, and general schema.
6. Evaluate Yoast WooCommerce SEO only if its additional commerce features are needed. Do not enable overlapping Product schema from multiple tools.
7. Leave visible breadcrumbs disabled until a placement is approved; never show both WooCommerce and Yoast breadcrumbs in the same location.

Yoast documentation:

- XML sitemaps: https://yoast.com/help/xml-sitemaps-in-the-wordpress-seo-plugin/
- Noindex controls: https://yoast.com/help/how-do-i-noindex-urls/
- Open Graph: https://yoast.com/help/getting-open-graph-for-your-articles/

## XML sitemap

Use only the selected SEO plugin’s sitemap index, normally /sitemap_index.xml. Do not submit both the plugin sitemap and WordPress core /wp-sitemap.xml as separate competing sources.

Include:

- Published marketing pages
- Published blog posts
- Published WooCommerce products
- Curated, useful blog categories
- Curated product categories with unique copy

Exclude:

- Anything set to noindex
- Internal search results
- 404 responses
- Cart, checkout, account, and order endpoints
- Form handlers and success/result URLs
- Placeholder, private, draft, and thank-you/download-gate pages
- Thin tag, date, and single-author archives
- Media attachment pages if attachment URLs are redirected or noindexed

Verify that every sitemap URL:

- Uses the final HTTPS canonical hostname
- Returns 200
- Is indexable
- Is not redirected
- Is not blocked by robots.txt
- Has the expected canonical
- Has an accurate lastmod value where the plugin supplies one

## robots.txt

Use the SEO plugin or WordPress virtual robots.txt unless hosting requirements demand a physical file. A conservative production baseline is:

    User-agent: *
    Disallow: /wp-admin/
    Allow: /wp-admin/admin-ajax.php
    Sitemap: https://FINAL-DOMAIN.example/sitemap_index.xml

Replace the domain after DNS launch. Do not block CSS, JavaScript, images, wp-content, or pages carrying noindex; crawlers must be able to render pages and see their robots directives. Do not use robots.txt as a substitute for noindex.

Before launch, clear Settings > Reading > Discourage search engines from indexing this site.

## Canonical URLs

Let the selected SEO plugin generate self-referencing HTTPS canonicals.

- Use one canonical hostname: either apex or www.
- Redirect HTTP and the alternate hostname to the canonical hostname in one 301 hop.
- Preserve self-referencing canonicals on paginated blog/archive pages; do not canonicalize every page to page 1.
- Product-format URLs should self-canonicalize when they are genuinely distinct purchasable products.
- Filter, search, tracking-parameter, and duplicate URLs should canonicalize or noindex according to plugin/WooCommerce behavior.
- Never output a second theme canonical.

Confirm declared and Google-selected canonicals through Search Console after launch.

## Open Graph and Twitter cards

Enable Open Graph and Twitter cards in the selected SEO plugin only. Do not enable social metadata in another plugin.

Recommended card type: Summary Card with Large Image.

Image standard:

- Primary working size: 1200 × 630 pixels
- Aspect ratio: approximately 1.91:1
- Safest formats: JPEG or PNG
- Keep below 2 MB; target well below 500 KB when quality permits
- Keep faces, book covers, logos, and essential text away from crop-prone edges
- Use approved final artwork, never a placeholder
- Test Facebook/LinkedIn/Pinterest and X previews after cache clearing

Required manual social assets:

| Page type | Recommended social title | Recommended description direction | Manual image need |
| --- | --- | --- | --- |
| Homepage &#124; Big Places. Brave Hearts. | Real-world adventure books that build STEM curiosity and courage for ages 6–9 | Brand-level landscape image with series identity |
| Books | Choose Your Next Charlotte & Henry Adventure | Explore real places, science, wildlife, courage, and available book formats | Landscape series image featuring approved covers |
| Teacher Resources | Bring Big Places Into Your Classroom | STEM, SEL, vocabulary, read-aloud, and classroom resources for Grades 1–3 | Classroom/educator landscape creative |
| Blog posts | Post title | Use the post’s custom social description or SEO description | Unique featured/social image tied to the real-world topic |
| Product pages | Product title | Use the accurate product short description and format | Landscape product creative; do not rely only on a narrow portrait cover |

For product pages, the portrait cover remains the product image, but a separate landscape social image should be assigned manually when the plugin supports it.

Rank Math currently recommends 1200 × 630 for Open Graph thumbnails and large Twitter cards:
https://rankmath.com/kb/open-graph-meta-tags/
https://rankmath.com/kb/how-to-add-twitter-cards/

## Schema plan

The selected SEO plugin should own the main schema graph.

Recommended graph:

- Organization: Brave Hearts Publishing, approved logo, canonical URL, real social profiles
- WebSite: site name and canonical URL
- WebPage: standard pages
- AboutPage: About page when supported
- ContactPage: Contact page when supported
- Blog/CollectionPage: Blog or Learning Hub index
- Article or BlogPosting: individual educational posts
- Person: real author/founder identity connected to posts
- Product and Offer: individual WooCommerce products
- BreadcrumbList: only from one source

Do not mark ordinary marketing pages as Articles. Do not add ratings that are not visibly supported by real reviews.

For Product schema, confirm name, canonical URL, image, description, SKU/identifier where available, price, currency, availability, and real aggregate ratings. Validate each product format and variation. Google distinguishes purchasable merchant listings from editorial product snippets and recommends accurate price, availability, rating, shipping, return, and variant data:
https://developers.google.com/search/docs/appearance/structured-data/product

Run Google Rich Results Test and Bing URL Inspection/Markup checks after production deployment.

## Breadcrumbs

Breadcrumbs are not added in Phase 8C because they are visible interface elements.

Recommended approach:

- Let the chosen SEO plugin produce BreadcrumbList schema if valid.
- If visible breadcrumbs are later approved, choose one renderer only.
- Do not combine WooCommerce, theme, Rank Math, and Yoast breadcrumb output.
- Define a logical trail such as Home > Books > Product or Home > Learning Hub > Category > Post.
- Validate keyboard access, contrast, mobile wrapping, and schema parity with the visible trail.

## Core page metadata plan

Treat these as editorial starting points. Check final pixel-width previews in the selected plugin; search engines may rewrite titles or descriptions.

| Page | Recommended SEO title | Recommended meta description |
| --- | --- | --- |
| Homepage | Children’s Adventure Books for Curious Kids &#124; Brave Hearts | Discover Brave Hearts children’s adventure books for curious kids ages 6–9, blending real places, STEM learning, SEL, courage, and family reading. |
| Books | STEM Adventure Books for Ages 6–9 &#124; Brave Hearts | Explore Charlotte & Henry adventure books for ages 6–9, with real-world STEM, geography, wildlife, courage, and formats for families and classrooms. |
| Teacher Resources | Teacher Resources for STEM Adventure Books &#124; Brave Hearts | Bring children’s adventure books into Grades 1–3 classrooms with STEM connections, SEL discussion, vocabulary, read-aloud ideas, and teacher resources. |
| About | About Brave Hearts Publishing &#124; Big Places. Brave Hearts. | Meet Brave Hearts Publishing and founder Andrew Signore, creating real-world children’s adventures that build curiosity, STEM learning, and courage. |
| Contact | Contact Brave Hearts Publishing &#124; Books & School Visits | Contact Brave Hearts Publishing about children’s books, classroom read-alouds, school visits, bulk orders, media, and upcoming adventures. |
| Blog / Learning Hub | Learning Hub: STEM Adventures for Curious Kids &#124; Brave Hearts | Explore the Brave Hearts Learning Hub for kid-friendly science, geography, animals, conservation, activities, and real-world reading adventures. |
| Privacy Policy | Privacy Policy &#124; Brave Hearts Publishing | Read the Brave Hearts Publishing Privacy Policy to learn how website, contact, newsletter, and customer information is collected and handled. |
| Terms | Terms & Conditions &#124; Brave Hearts Publishing | Review the terms and conditions for using the Brave Hearts Publishing website, resources, and online store. |

Primary theme phrase: Big Places. Brave Hearts.

Use naturally where helpful; do not repeat every positioning phrase on every page.

## Indexing plan

### Index

- /
- /books/
- /teachers/
- /about/
- /blog/
- /contact/
- Valuable individual blog posts
- Published WooCommerce product pages
- Curated blog categories with unique introductions and enough content
- Curated product categories that help shoppers
- Approved resource/hub pages with complete original content

### Noindex

- Internal search results
- 404 pages
- Cart, checkout, account, order-pay, order-received, and similar customer endpoints
- Form submission/result URLs
- Draft, private, staging, placeholder, gated-thank-you, and incomplete download pages
- Thin tag archives
- Date archives
- Media attachment pages unless intentionally developed
- Single-author archives on this currently founder-led site, because they duplicate the blog stream
- Empty or thin categories/product categories
- Filter/sort parameter URLs where the SEO/WooCommerce configuration does not canonicalize them safely

### Conditional

- Privacy Policy and Terms: recommendation is to index both for brand trust and direct branded searches. If counsel supplies generic duplicate boilerplate or the business prefers exclusion, noindex them consistently and remove them from the sitemap.
- Author archives: index only when multiple real authors have unique biographies and genuinely useful archive pages.
- Tag archives: index only after they become curated discovery pages with unique copy.
- Resource landing pages: index only when the promised resource and page content are complete.

Noindex pages should generally remain follow unless there is a specific security or crawl reason otherwise.

## Blog archive settings

- Keep /blog/ indexable with a unique title and description.
- Keep useful category archives indexable only when curated.
- Let paginated archives use their own canonical URLs.
- Do not noindex page 2+ merely because they are paginated.
- Keep date archives noindexed.
- Keep tag archives noindexed until curated.
- Keep single-author archives noindexed unless the site adds multiple substantive author profiles.
- Verify pagination links return 200 and do not create redirect chains.
- Ensure excerpts do not expose unfinished or sensitive copy.

## Image SEO

For every meaningful image:

- Use a descriptive filename before upload.
- Write concise contextual alt text; do not keyword-stuff.
- Leave alt empty for decorative images.
- Use the book title and format in cover alt text when that distinction helps.
- Set a real featured image for each post.
- Compress originals and use WebP/AVIF when the hosting stack reliably supports them.
- Preserve adequate dimensions for responsive output and social sharing.
- Avoid embedding important text only inside images.
- Confirm social images and content images are not blocked from crawling.
- Do not create attachment pages solely for indexing.

## Reusable blog metadata audit

Use this checklist for every existing and future post:

- [ ] SEO title is unique, accurate, and compelling.
- [ ] Meta description summarizes the real reader benefit.
- [ ] One primary focus keyword/topic is selected.
- [ ] Featured image is approved and large enough for sharing.
- [ ] Featured and inline images have accurate alt text.
- [ ] Post has one H1 and logical H2/H3 sections.
- [ ] Internal links point to relevant hub/category pages.
- [ ] Internal links point to related books or the Books page.
- [ ] A teacher or parent CTA is present where relevant.
- [ ] Adventure Club CTA is present where relevant.
- [ ] Related posts are selected by genuine topic relevance.
- [ ] Published and last-updated dates are accurate.
- [ ] Real author attribution and biography are present.
- [ ] Claims, facts, and sources are reviewed.
- [ ] A real-world “Try This Adventure” prompt gives the reader a safe next step.
- [ ] Canonical, index status, and sitemap inclusion are correct.
- [ ] Open Graph title, description, and image are previewed.
- [ ] Mobile layout, links, and image loading are tested.

## WooCommerce product SEO checklist

For each Kindle, Paperback, and Hardcover product:

- [ ] Product title clearly identifies book and format.
- [ ] Long product description is original, useful, and accurate.
- [ ] Short description works as concise sales and schema copy.
- [ ] Cover image is high quality with useful alt text.
- [ ] Product gallery contains only approved assets.
- [ ] Primary product category is selected.
- [ ] Product tags are controlled and non-duplicative.
- [ ] SKU/identifier is present where applicable.
- [ ] Price, currency, inventory, and availability are accurate.
- [ ] Format is unmistakable: Kindle, Paperback, or Hardcover.
- [ ] Canonical URL is correct for the product/format strategy.
- [ ] Product and Offer schema come from one compatible source.
- [ ] Review and aggregate-rating markup reflects visible real reviews only.
- [ ] Related products are genuinely related.
- [ ] Social title, description, and landscape image are assigned.
- [ ] Product appears once in the correct sitemap.
- [ ] Rich Results Test passes without critical errors.
- [ ] Cart and checkout links work after metadata/plugin changes.

## Analytics plan

### Google Analytics 4

1. Create a GA4 property using the business timezone and reporting currency.
2. Create one Web data stream for the final canonical HTTPS domain.
3. Install through one maintained WordPress integration, preferably Google Site Kit or another approved analytics plugin.
4. Do not paste the Measurement ID into the theme.
5. Do not install GA4 both directly and through Google Tag Manager.
6. Enable only approved Enhanced Measurement events.
7. Define internal traffic and referral exclusions.
8. Configure consent behavior appropriate to the site’s jurisdictions.
9. Verify Realtime and DebugView using an owned test session.
10. Confirm page_view, outbound book/product clicks, successful signup, and WooCommerce commerce events without duplicate firing.
11. Link GA4 and Search Console after both are verified.

Google’s official GA4 setup guidance notes that CMS integrations can accept the Measurement ID and that Realtime can verify collection:
https://support.google.com/analytics/answer/14183469

### Google Search Console

1. Create a Domain property for the final domain.
2. Verify using a DNS TXT record.
3. Add appropriate users with least-privilege access.
4. Submit the selected SEO plugin’s sitemap index after HTTPS launch.
5. Inspect the six launch-critical URLs.
6. Confirm crawling allowed, index allowed, canonical, rendered HTML, and discovered structured data.
7. Request indexing for the homepage and priority hubs after final validation.
8. Monitor Page indexing, Sitemaps, Core Web Vitals, Enhancements, Manual actions, and Security issues.
9. Link Search Console to GA4.

Use URL Inspection for individual pages and sitemap submission for broader discovery:
https://support.google.com/webmasters/answer/9012289

### Bing Webmaster Tools

1. Import the verified site from Search Console or verify manually.
2. Confirm the canonical HTTPS property.
3. Submit the same sitemap index.
4. Inspect the six critical URLs.
5. Review Site Explorer, URL Inspection, SEO, crawl, and markup reports.
6. Consider IndexNow only through the selected SEO plugin or one approved integration; do not duplicate submissions.

Bing can import Search Console properties and sitemaps:
https://www.bing.com/webmasters/help/add-and-verify-site-12184f8b

### Pinterest site verification

Preferred method: add Pinterest’s verification TXT record at DNS so verification is independent of the theme. HTML tag or file verification are alternatives, but no verification token should be hard-coded in theme files.

After verification, confirm the canonical production domain is claimed and review Pin analytics.

Pinterest documents DNS TXT, HTML tag, and HTML file methods:
https://help.pinterest.com/en/business/article/claim-your-website

### Meta Pixel — optional later

Do not add Meta Pixel during launch unless there is an approved advertising requirement, privacy notice, consent configuration, event plan, and data-retention owner. Install through one maintained integration or GTM, not theme code. Test with Meta’s event tools and prevent duplicate PageView events.

### Google Tag Manager — optional later

Use GTM only when the measurement stack has enough approved tags/events to justify a container. Install one container through a maintained integration, apply consent controls, use environments/workspaces, preview before publish, and document ownership. If GA4 moves into GTM, remove any direct GA4 installation to prevent duplicate events.

Google documents adding and verifying the Google tag through GTM:
https://support.google.com/tagmanager/answer/14842872

## Pre-launch validation

### Metadata and source

- [ ] Exactly one title element is present.
- [ ] Exactly one canonical is present.
- [ ] One meta description is present on indexable pages.
- [ ] One coherent Open Graph/Twitter set is present.
- [ ] One coherent schema graph is present.
- [ ] No accidental noindex appears on launch-critical pages.
- [ ] Settings > Reading search-engine discouragement is off.
- [ ] Final URLs use HTTPS and one canonical hostname.

### Discovery

- [ ] /robots.txt returns 200 and references the final sitemap.
- [ ] Sitemap index returns 200 and contains only canonical indexable URLs.
- [ ] Google Search Console Domain property is verified.
- [ ] Sitemap is submitted to Google and Bing.
- [ ] Six critical URLs pass Google and Bing URL inspection.
- [ ] 404, search, cart, checkout, account, and placeholder pages are excluded as planned.

### Social and schema

- [ ] Approved 1200 × 630 social images exist for required page types.
- [ ] Homepage, Books, Teachers, sample post, and sample product previews are tested.
- [ ] Article and Product structured data pass Rich Results Test.
- [ ] Price, availability, review, author, organization, and logo data match visible content.
- [ ] No duplicate Product, Article, Organization, or Breadcrumb graphs exist.

### Analytics

- [ ] GA4 receives one page_view per page.
- [ ] Realtime and DebugView show the production hostname.
- [ ] Internal traffic and consent behavior are tested.
- [ ] Search Console is linked to GA4.
- [ ] Bing and Pinterest ownership are verified.
- [ ] No tracking ID or verification secret is stored in the theme.

## Launch blockers

Theme code has no known unresolved technical SEO blocker after the post attribution fix.

Launch remains blocked until:

- One SEO plugin is selected and configured.
- Search-engine discouragement is disabled on production.
- Titles/descriptions and social metadata are completed for critical pages.
- Approved social images are uploaded.
- Index/noindex and archive rules are reviewed.
- Sitemap, robots.txt, canonicals, and schema are validated on the final HTTPS domain.
- Search Console, Bing, and the chosen analytics installation are verified.
- Product schema is tested against live products.
- DNS verification records and canonical redirects are in place.

## Knowledge & Discovery Engine bridge

Phase 8C covers only the technical SEO, metadata, analytics, social-sharing, schema, sitemap, and index-discovery foundation.

The full ongoing content strategy belongs to Phase 9 — Knowledge & Discovery Engine.

Phase 9 will include:

- Hub pages
- Blog audits
- Internal linking
- Metadata completion
- EEAT
- Founder voice
- Pinterest
- Content scoring
- Brave Hearts Gold Standard

## Recommended next steps

1. Choose Rank Math or Yoast; do not activate both.
2. Complete organization, titles, archives, sitemap, social, and schema settings on staging.
3. Enter the core-page metadata plan.
4. Create and upload approved 1200 × 630 social assets.
5. Apply the blog and product checklists to launch content.
6. Validate the final HTTPS site with source inspection, Rich Results Test, Google, and Bing.
7. Install GA4 through one approved integration and verify events.
8. Complete DNS verification for Search Console and Pinterest.
9. Proceed to Phase 9 only after the technical launch foundation passes.
