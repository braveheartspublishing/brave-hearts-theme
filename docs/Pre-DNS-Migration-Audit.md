# Pre-DNS Migration Audit — Content Architecture, URLs, and Internal Links

Audit date: June 29, 2026
Scope: Brave Hearts Publishing WordPress theme repository and available launch documentation
Migration: Squarespace to WordPress
Current decision: NO-GO

## Executive decision

DNS migration is currently NO-GO.

The theme-level URL and fallback-link architecture is generally sound, and one clickable placeholder blocker was corrected during this audit. However, this workspace contains only the WordPress theme. It does not contain:

- A WordPress database or WXR export
- A public/staging WordPress URL to crawl
- A Squarespace sitemap or URL export
- A list of indexed/backlinked Squarespace URLs
- WordPress page, post, product, taxonomy, SEO-meta, or media records

Without both source and destination URL inventories, the audit cannot truthfully identify all WordPress posts, categories, orphan content, metadata gaps, URL changes, or required redirects. Changing DNS before that comparison risks avoidable 404s, lost search equity, broken backlinks, and undiscovered content.

## Status legend

| Status | Meaning |
| --- | --- |
| GREEN — Ready | Verified in the available theme source or corrected safely |
| YELLOW — Manual review | Architecture exists, but live WordPress content/configuration must be checked |
| RED — Launch blocker | Required evidence or behavior is missing; do not cut DNS until resolved |

## Launch status table

| Area | Status | Finding |
| --- | --- | --- |
| Theme fallback navigation | GREEN — Ready | Required header/footer routes use WordPress home URLs |
| Theme default CTAs | GREEN — Ready | Core defaults point to Books, Teachers, About, Contact, Blog, or the Adventure Club anchor |
| Theme hard-coded staging domains | GREEN — Ready | No staging, localhost, Squarespace, or site-domain URL is hard-coded in PHP |
| Unavailable Explorer Passport download | GREEN — Ready | Fixed in this audit: inactive state now renders a non-link instead of a clickable placeholder URL |
| Required live WordPress pages | RED — Launch blocker | No database, export, or staging URL was supplied; publication/status cannot be verified |
| WordPress page/post/product inventory | RED — Launch blocker | Actual public content cannot be listed from a theme repository |
| Squarespace URL inventory | RED — Launch blocker | No Squarespace sitemap/export/current domain was supplied |
| Redirect matrix | RED — Launch blocker | Old-to-new mappings cannot be built without both inventories |
| Blog categories and hub assignments | RED — Launch blocker | No WordPress taxonomy/post assignments are available |
| Orphan posts and hub-to-child links | RED — Launch blocker | Requires a live crawl or content export |
| Post CTAs and internal links | RED — Launch blocker | Requires rendered post content; template source alone is insufficient |
| Live menus and custom-field CTA overrides | YELLOW — Manual review | Editor-managed menus and custom fields can override safe theme defaults |
| Product links and formats | YELLOW — Manual review | Theme logic is guarded, but live products/permalinks/formats are not available |
| Contact form | YELLOW — Manual decision | Theme intentionally disables it without a real provider and supplies direct email |
| Explorer Passport/resource pages | YELLOW — Manual review | Placeholder states are honest, but incomplete pages should remain noindexed/unpromoted |
| SEO titles/descriptions/social metadata | RED — Launch blocker | Plugin/content records and rendered head output are unavailable |
| Featured images and attachment alt text | RED — Launch blocker | Media library and post/product relationships are unavailable |
| DNS migration | RED — NO-GO | Inventory, redirect, live-link, and metadata gates are incomplete |

# 1. Evidence boundary and required exports

## What was available

- Complete theme PHP/CSS/JavaScript source
- Theme documentation from Phases 8A–8D
- Theme fallback routes, page templates, CTA defaults, product lookup logic, form states, and archive templates

## What must be supplied before final migration approval

- [ ] Export all published WordPress pages, posts, products, categories, product categories, tags, authors, dates, and canonical URLs.
- [ ] Export SEO titles, meta descriptions, canonical overrides, robots settings, focus keywords, and social images from the selected SEO plugin.
- [ ] Export featured-image IDs and attachment alt text.
- [ ] Export editor-managed Primary and Footer menus.
- [ ] Export relevant page/post custom fields, especially bhp_home_*, bhp_about_*, bhp_books_*, bhp_teachers_*, and bhp_contact_* URL overrides.
- [ ] Obtain the current Squarespace sitemap.xml and any secondary sitemaps.
- [ ] Export Squarespace pages, blog posts, product URLs, category/tag URLs, assets/downloads, and URL mappings.
- [ ] Export top landing pages and backlinks from Search Console, Bing, analytics, and any backlink tool available.
- [ ] Crawl both the current Squarespace site and WordPress staging site while they are simultaneously accessible.
- [ ] Create a signed redirect matrix and test it before changing DNS.

## Suggested WordPress inventory methods

Use WordPress Tools > Export for Pages, Posts, Products, and media relationships where available. A technical operator with WP-CLI may also export core records:

    wp post list --post_type=page,post,product --post_status=publish --fields=ID,post_type,post_title,post_name,post_parent,post_author,post_date --format=csv
    wp term list category --fields=term_id,name,slug,parent,count --format=csv
    wp term list product_cat --fields=term_id,name,slug,parent,count --format=csv
    wp menu list --format=csv

URLs, SEO fields, thumbnails, alt text, custom fields, and link relationships still require additional export/crawl work. Do not treat the basic CSV as a complete migration audit.

# 2. Required public pages

Theme support exists for all eight required destinations, but live status is unverified.

| Required URL | Theme evidence | Live verification required | Status |
| --- | --- | --- | --- |
| / | front-page.php and logo/Home fallbacks | Published static front page, 200, canonical, metadata, one H1 | RED |
| /books/ | Books fallback and Brave Hearts Books Page | Published page/template, 200, products and CTAs | RED |
| /teachers/ | Teacher Resources fallback/template | Published page/template, 200, resources/forms/CTAs | RED |
| /about/ | About fallback/template | Published page/template, 200, founder media and CTAs | RED |
| /blog/ | Menu fallback and archive fallback | Assigned Posts page, 200, pagination/categories/posts | RED |
| /contact/ | Menu/footer/contact template | Published page/template, 200, inquiry links and provider decision | RED |
| /privacy-policy/ | WordPress privacy assignment with slug fallback | Published/assigned, legal approval, canonical/index decision | RED |
| /terms/ | WooCommerce Terms assignment with slug fallback | Published/assigned, legal approval, canonical/index decision | RED |

## Required page acceptance

- [ ] Each URL returns 200 over final HTTPS without an avoidable redirect chain.
- [ ] Each page has exactly one H1 and an approved title/meta description.
- [ ] Each page has the correct WordPress template and canonical URL.
- [ ] Header/footer menus point to the canonical URL.
- [ ] All page-level custom-field CTA overrides are production URLs.
- [ ] No page is draft, private, password protected, accidentally noindexed, or omitted from the intended sitemap.
- [ ] Privacy Policy and Terms are assigned in WordPress/WooCommerce rather than relying only on slug fallbacks.

# 3. Public WordPress content inventory

## Actual inventory result

No actual WordPress page, post, or product records are present in this repository. Therefore:

- Verified public pages: 0 records available
- Verified public posts: 0 records available
- Verified public products: 0 records available
- Verified WordPress categories: 0 records available
- Verified media/alt records: 0 records available

This does not mean the WordPress site is empty. It means the content is outside the supplied audit scope and must be exported or crawled.

## Theme-declared content surfaces

These are templates/components, not proof that corresponding WordPress records exist:

| Surface | Theme file | Public URL certainty |
| --- | --- | --- |
| Home | front-page.php | Expected / when assigned |
| Books | page-books.php | Expected /books/ when page slug/template are assigned |
| Teachers | page-teachers.php | Expected /teachers/ |
| About | page-about.php | Expected /about/ |
| Contact | page-contact.php | Expected /contact/ |
| Blog/archive | index.php | Expected /blog/ when Posts page is assigned |
| Standard pages | page.php | Dynamic |
| Single posts | single.php | Dynamic according to permalink settings |
| Products | WooCommerce/plugin templates plus theme cards | Dynamic according to WooCommerce |
| Explorer Passport | four custom page templates | Slugs depend on actual WordPress page records |

Explorer Passport landing, lead-magnet, thank-you, and download templates must not be assumed public or indexable merely because template files exist.

## Required inventory table

Populate one row per public WordPress object before DNS:

| ID | Type | Title | WordPress URL | Status | Template | Parent hub/category | Author/date | Featured image/alt | SEO title/meta | CTA | Squarespace URL | Redirect |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| TBD | TBD | Export required | Export required | TBD | TBD | TBD | TBD | TBD | TBD | TBD | TBD | TBD |

# 4. Blog and content architecture audit

## Audit status

RED — Blog architecture cannot be validated without post and taxonomy records plus rendered content.

## Per-post checks

For every published WordPress post:

- [ ] Post has one intentional primary category/hub.
- [ ] Post is not Uncategorized.
- [ ] Category accurately represents the post’s dominant reader intent.
- [ ] Secondary categories are limited and genuinely useful.
- [ ] Post links to its parent hub/category.
- [ ] Parent hub/category links back to the post.
- [ ] At least one other relevant post links to it, or an intentional discovery route exists.
- [ ] Post links to a relevant book/product or /books/.
- [ ] Teacher-facing content links to /teachers/.
- [ ] Parent/family content includes an appropriate Adventure Club CTA.
- [ ] Post has a strong, context-appropriate CTA rather than every CTA indiscriminately.
- [ ] Post has SEO title, meta description, focus topic, featured image, accurate alt text, author, published/updated date, and canonical.
- [ ] Post contains no staging URL, Squarespace editor URL, temporary asset, or broken outbound link.
- [ ] Post has useful related-post/hub navigation.
- [ ] Post has a real-world “Try This Adventure” prompt where appropriate.

## Flag definitions

| Flag | Rule |
| --- | --- |
| Missing category | No category or only Uncategorized |
| Weak category | Vague, promotional, one-post, duplicative, or unrelated category |
| Incorrect category | Category does not match primary search/user intent |
| Orphan post | No internal contextual link from a page, hub, category introduction, or related post |
| Missing parent link | Post does not link to the hub/category it belongs to |
| Missing child link | Hub/category has no visible link/listing that leads to the post |
| Missing commerce CTA | Relevant post has no useful Books/product path |
| Missing audience CTA | Teacher/parent post lacks the relevant Teachers or Adventure Club path |
| Metadata gap | Missing SEO title, description, canonical/index decision, image, alt, author, or date |

## Required post audit output

The migration owner must produce:

- [ ] Complete post inventory with old/new URL.
- [ ] Category assignment report.
- [ ] Uncategorized report.
- [ ] Orphan report from crawl graph.
- [ ] Inbound internal-link count per post.
- [ ] Outbound internal-link list per post.
- [ ] CTA coverage report.
- [ ] Metadata/image/alt report.
- [ ] Redirect and canonical status.
- [ ] Approved exceptions/waivers.

# 5. Hub and category strategy

## Current theme evidence

The homepage currently attempts these category slugs:

- animals
- science
- geography
- conservation
- explorers
- activities

If one is absent, its homepage card safely falls back to the configured Posts page or /blog/. This avoids a theme-generated category 404, but it also means the intended hub experience disappears silently. Actual WordPress categories are unknown.

## Recommended taxonomy model

Use a small hierarchy with one primary home for each post. Titles may be search-friendly while retaining stable slugs. Do not create many overlapping categories merely to cover keywords.

| Requested cluster | Recommended role | Recommended slug | Parent/group | Current theme relationship | Pre-DNS action |
| --- | --- | --- | --- | --- | --- |
| Reading Resources | Primary hub/category | reading-resources | Top level | Homepage activities may overlap | Create/confirm; define scope |
| Early Chapter Books | Child hub/category | early-chapter-books | Reading Resources | No direct theme category | Create only with sufficient content |
| Read-Aloud Books | Child hub/category | read-aloud-books | Reading Resources | Teachers/contact CTAs support intent | Create/confirm and cross-link Teachers |
| Geography for Kids | Primary subject hub | geography | Learning by Subject | Homepage expects geography | Prefer stable slug geography |
| Science for Kids | Primary subject hub | science | Learning by Subject | Homepage expects science | Prefer stable slug science |
| Conservation | Primary subject hub | conservation | Learning by Subject | Homepage expects conservation | Keep stable slug if already used |
| Teachers | Audience hub/category | teachers | Audience Resources | /teachers/ is also a major page | Decide page-vs-category canonical strategy |
| Homeschool | Audience category | homeschool | Audience Resources | No direct theme category | Create only with distinct content |
| Parents | Audience category | parents | Audience Resources | Adventure Club targets families | Define scope separate from Reading Resources |
| Adventure Destinations | Primary destination hub | adventure-destinations | Top level | Homepage Explore World is a section, not archive | Create/confirm hub and link from posts/books |
| Ocean | Destination child | ocean | Adventure Destinations | Mariana content likely fits | Create/confirm with redirect planning |
| Mountains | Destination child | mountains | Adventure Destinations | Everest content likely fits | Create/confirm with redirect planning |
| Rainforests | Destination child | rainforests | Adventure Destinations | Amazon content likely fits | Create/confirm with redirect planning |

Additional existing theme concepts need an explicit decision:

| Theme slug | Recommended treatment |
| --- | --- |
| animals | Keep as a useful subject category only if enough wildlife content exists; otherwise map posts into Science and redirect the archive |
| explorers | Keep only if it has a clear people/exploration editorial promise; do not confuse with Adventure Destinations |
| activities | Use as a practical activity category or map into Reading Resources; preserve/redirect the old slug if changed |

## Taxonomy cleanup rules

- [ ] Export actual category names, slugs, parents, counts, and assigned posts.
- [ ] Pick one stable slug per hub before DNS.
- [ ] Avoid having both /category/science/ and /category/science-for-kids/ compete.
- [ ] Decide whether major hubs are Pages, Categories, or both; define a single canonical owner.
- [ ] Use categories for durable navigation and tags only for controlled secondary facets.
- [ ] Merge/delete empty, duplicate, typo, and one-off categories only with 301 redirects.
- [ ] Redirect every renamed Squarespace or WordPress taxonomy URL.
- [ ] Add unique hub introductions, audience path, book links, and child-post links.
- [ ] Do not index thin hub/category archives.
- [ ] Re-run orphan and internal-link reports after taxonomy cleanup.

# 6. URL and redirect audit

## Theme URL findings

GREEN:

- Header fallback routes: /, /books/, /teachers/, /about/, /blog/, /contact/
- Footer fallback routes: /books/, /teachers/, /about/, /blog/, /contact/, assigned Privacy, assigned Terms, /#adventure-club
- Homepage default CTAs: /books/, /teachers/, /about/, section anchors
- Books default CTAs: /books/#adventure-book-grid, /teachers/, /#adventure-club
- Teachers default CTAs: /books/, /contact/?inquiry=read-aloud, /#adventure-club
- Contact default CTAs: /books/, /teachers/, /#adventure-club and local contact anchors
- Blog/archive links: WordPress-generated permalinks and pagination
- 404 CTA: /

YELLOW:

- WordPress menus can replace fallback links.
- Front/About/Books/Teachers/Contact custom fields can override URLs.
- Product links depend on live WooCommerce product permalinks.
- Category cards depend on category slugs or fall back to /blog/.
- Terms/Privacy depend on assigned WordPress/WooCommerce pages.
- Resource links depend on configured custom fields.

## Hard-coded domain scan

No staging, localhost, Squarespace, or site-domain URL is hard-coded in theme PHP.

The only absolute external PHP URLs are:

- Google Fonts stylesheet
- Standard XFN profile reference

This is GREEN for theme source. It does not validate URLs stored in WordPress content, menus, widgets, products, reusable blocks, custom fields, SEO fields, or media captions.

## Likely Squarespace-to-WordPress URL differences

These are risks to investigate, not claims about the unseen Squarespace site:

- Squarespace blog path such as /blog/post-slug versus the selected WordPress permalink structure
- Page slug differences, nested Squarespace folders, and deleted/renamed pages
- Squarespace product paths versus WooCommerce /product/product-slug/
- Category/tag archive naming and hierarchy changes
- Trailing slash, uppercase/lowercase, punctuation, apostrophe, and encoded-character differences
- www versus apex and HTTP versus HTTPS variants
- Squarespace image/CDN, downloadable file, and /s/ asset links
- Old campaign/landing-page URLs
- RSS/feed URLs
- URLs with high traffic/backlinks that are absent from the Squarespace navigation
- External Amazon/social/email links that use old site URLs

## Redirect rules

- [ ] Build redirects from exact old paths to the closest equivalent new canonical page.
- [ ] Use 301 for permanent one-to-one moves.
- [ ] Do not redirect all missing URLs to the homepage; that creates poor relevance and soft-404 risk.
- [ ] Preserve query strings only where needed and strip obsolete tracking safely.
- [ ] Avoid redirect chains and loops.
- [ ] Redirect renamed category/tag/product paths.
- [ ] Map high-value old assets/downloads to approved replacements.
- [ ] Keep a true 404 for content with no useful replacement.
- [ ] Test redirects before DNS at the host/application layer.
- [ ] Retest from an external network after DNS/CDN propagation.
- [ ] Keep redirect ownership outside the theme so theme changes cannot remove migration rules.

## Required redirect matrix

| Old Squarespace URL | New WordPress URL | Source/type | Traffic/backlinks | Redirect | Tested staging | Tested live | Owner/notes |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Export required | Mapping required | TBD | TBD | TBD | No | No | Launch blocker |

# 7. Internal link audit

## Primary navigation

Status: GREEN in fallback source; YELLOW for live menu.

- [ ] Compare assigned Primary menu to Home, Books, Teacher Resources, About, Blog, Contact.
- [ ] Click each link logged out on desktop and mobile.
- [ ] Verify current-page state and canonical destination.
- [ ] Remove old Squarespace/staging/draft destinations.

## Footer

Status: GREEN in fallback source; YELLOW for live menu/widgets.

- [ ] Compare assigned Footer menu with fallback destinations.
- [ ] Verify Privacy and Terms assignments.
- [ ] Verify Adventure Club anchor.
- [ ] Verify direct email and footer widgets.
- [ ] Verify optional Footer signup placement/behavior.

## Homepage

Status: GREEN defaults; YELLOW dynamic overrides/categories/products.

- [ ] Click hero, featured-books, Why, destination, Learning Hub, Teachers, Adventure Club, and final CTAs.
- [ ] Inspect every bhp_home_* URL value for staging/old-domain links.
- [ ] Verify destination cards reach preferred live Paperback products where available.
- [ ] Verify absent categories land on Blog intentionally rather than concealing a missing hub.
- [ ] Verify section IDs match anchor links.

## Books

Status: GREEN default page links; YELLOW live product/resource data.

- [ ] Start with Book 1 lands on the preferred available product or safe book grid.
- [ ] View Book and Shop Formats return relevant live results.
- [ ] Teacher Resources and Adventure Club CTAs work.
- [ ] Missing products display non-link/disabled honest states.
- [ ] Custom bhp_books_* URL overrides are canonical production URLs.

## Teachers

Status: GREEN default page links; YELLOW resource inventory.

- [ ] Books, Read-Aloud, Adventure Club, and final CTAs work.
- [ ] Read-Aloud query preselects the contact inquiry.
- [ ] Available resource cards link to 200 responses.
- [ ] Unavailable resources remain non-links.
- [ ] Book/resource custom fields contain no stale URL.

## Contact

Status: YELLOW.

- [ ] General, Read-Aloud, and School/Library anchors reach #contact-form.
- [ ] Inquiry query values preselect correctly.
- [ ] Direct email is correct and monitored.
- [ ] If provider is not connected, controls remain disabled and direct-email guidance is visible.
- [ ] Contact form placeholder action is never presented as a navigable visitor link.
- [ ] Books, Teachers, and Adventure Club CTAs work.

## Blog and archives

Status: GREEN template mechanics; RED content architecture.

- [ ] Post title/image/read-more links use the canonical post permalink.
- [ ] Pagination works beyond page 1.
- [ ] Previous/next post links work.
- [ ] Category/author links follow the approved index/noindex strategy.
- [ ] Each post links to its parent hub and appropriate audience/commerce CTA.
- [ ] Hubs/categories visibly lead to all intended child posts.
- [ ] Orphan crawl report is empty or every exception is approved.

## Placeholder-link findings

- GREEN — Teacher unavailable resource actions use non-link spans.
- GREEN — Missing book/resource actions use non-link spans.
- GREEN — Explorer Passport unavailable download now uses a non-link span after this audit.
- YELLOW — Contact form action uses a nonfunctional placeholder only while all controls are disabled.
- YELLOW — Explorer Passport pages and future features must remain unpromoted/noindexed until approved.
- YELLOW — Product/category/custom-field URLs require live verification.

# 8. SEO basics audit

## Theme-level findings

| Item | Theme status | Finding |
| --- | --- | --- |
| Title support | GREEN | Theme delegates title to WordPress/SEO plugin |
| Meta description | GREEN architecture | No conflicting hard-coded description; actual records unknown |
| Canonical | GREEN architecture | No conflicting hard-coded canonical; plugin output unknown |
| Open Graph/Twitter/schema | GREEN architecture | Theme does not duplicate plugin output |
| H1/main landmarks | GREEN architecture | Major templates provide one theme H1 and shared main |
| Blog author/date | GREEN | Single/archive templates output author and semantic date |
| Pagination/archive | GREEN | WordPress pagination and archive title are present |
| Featured image rendering | GREEN architecture | WordPress responsive featured-image functions are used |
| Accidental noindex | GREEN theme / RED live | No theme noindex; WordPress/plugin setting unavailable |

## Actual page/post/product SEO report

RED — Cannot be completed from theme files.

For every public object, export/flag:

| Type | SEO title | Meta description | Canonical/index | Featured/product image | Alt text | Category | Author/date | Strong CTA | Status |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Page/Post/Product | Unknown | Unknown | Unknown | Unknown | Unknown | Unknown | Unknown | Unknown | RED until exported |

## Minimum pre-DNS SEO gates

- [ ] All six marketing URLs have approved unique SEO titles and meta descriptions.
- [ ] Privacy/Terms index decision is explicit.
- [ ] Every launch post/product has title, meta description, canonical/index decision, image, and detectable alt text.
- [ ] Every post has category, author, date, and strong audience/commerce CTA.
- [ ] Every product clearly identifies format and has accurate Product/Offer schema inputs.
- [ ] No critical URL is accidentally noindexed.
- [ ] Sitemap contains only 200, canonical, indexable production URLs.
- [ ] Social images and metadata use production assets/URLs.
- [ ] WordPress content and SEO fields contain no staging or Squarespace internal URL.
- [ ] Search Console top URLs are represented in the redirect matrix.

# 9. Recommended fixes before DNS

## P0 — Must complete before cutover

1. Export the complete WordPress public content/taxonomy/media/SEO/menu/custom-field inventory.
2. Export/crawl the current Squarespace URLs, sitemaps, high-traffic landing pages, and backlinks.
3. Produce and approve the one-to-one redirect matrix.
4. Crawl WordPress staging and resolve required-page 404s, internal broken links, orphan posts, staging URLs, and redirect chains.
5. Confirm the eight required pages are published, canonical, correctly templated, and linked.
6. Audit every published post for category/hub, parent/child links, CTA, metadata, author/date, featured image, and alt text.
7. Approve final category/hub slugs and redirect every renamed archive.
8. Audit every live product and format URL.
9. Verify SEO plugin metadata, canonicals, robots, schema, sitemap, and search visibility.
10. Make the explicit Contact and Explorer Passport launch-scope decisions.

## P1 — Complete before public promotion

- Populate hub introductions and child-post lists.
- Resolve weak/duplicate categories.
- Add missing parent-hub and related-post links.
- Add appropriate Books, Teachers, and Adventure Club CTAs.
- Replace/approve founder, cover, resource, and social-image placeholders.
- Verify Mailchimp signup placement/delivery and all page custom-field URL overrides.
- Validate redirects and links from an external network immediately after DNS.

# 10. Items safe to handle after launch

Only defer these when they do not change a public URL or leave content orphaned:

- Deeper Phase 9 hub copy and content scoring
- Additional related-post recommendations
- Secondary keyword refinement
- New hub pages for clusters that do not yet have enough content
- Pinterest publishing cadence and creative expansion
- Advanced internal-link optimization beyond a functional non-orphan baseline
- Additional schema enrichment beyond accurate launch schema
- New downloadable resources/features that remain honestly unpromoted
- New Mailchimp/HubSpot tag automations after consent and routing approval

Not safe to defer:

- Old-to-new redirects
- Required page publication
- Critical broken links
- Canonical/noindex/sitemap errors
- Staging URLs
- Missing product/checkout destinations
- Orphaned high-value Squarespace content
- False availability/download promises
- Unapproved legal/contact behavior

# 11. Final migration gate

DNS migration becomes GO only when all of the following are true:

- [ ] Complete Squarespace URL inventory is archived.
- [ ] Complete WordPress page/post/product/taxonomy inventory is archived.
- [ ] Every old URL has an approved new destination or intentional 404/410 decision.
- [ ] Redirects are installed and staging-tested.
- [ ] Eight required URLs return 200 with correct templates, canonicals, metadata, and links.
- [ ] Post category/hub, orphan, parent-child link, CTA, and SEO audits are complete.
- [ ] Product/format and commerce URLs are tested.
- [ ] Menus, custom fields, widgets, content, SEO fields, and media contain no staging/old-domain links.
- [ ] Contact and Explorer Passport launch scope is approved.
- [ ] Sitemap/robots/index settings are correct for the final domain.
- [ ] Backups, DNS rollback values, SSL, analytics, Mailchimp, and launch QA are ready.
- [ ] Migration owner signs GO with evidence.

Current result: NO-GO.

The theme itself is not the reason for NO-GO. The blocker is missing source/destination content evidence and the resulting inability to validate redirects, taxonomy, internal links, metadata, and public status before DNS.
