# Decisions Log — Brave Hearts Theme

Durable architectural decisions and why they were made. Append, don't
rewrite history here.

## Branch from exact production commit, not `main`
`main` drifted stale relative to what was actually deployed (a structured-
data feature branch was built from `main` at 1.16.7 while production had
independently advanced to 1.17.0 via a different branch). Fix: any new
work branches from the verified production commit hash, and divergent
feature lines get combined via `git cherry-pick` onto a fresh integration
branch from that exact commit — never a blind merge of two branches that
both claim to be "current."

## Full-ZIP `wp theme install --force`, not piecemeal file copies
Piecemeal `scp` copies leave the deployed state inconsistent and
undocumented. A full ZIP install is atomic and versioned. **Trap**: the
ZIP's top-level folder name must match the *active* theme's slug
(`brave-hearts-theme-deploy-explorer-expedition-guides`), or WP-CLI
installs it as a brand-new, inactive theme instead of replacing the live
one — this happened once and was caught by checking `wp theme list
--status=active` immediately after install, not by assuming success.

## Frozen Funnel Architecture — permanent company-wide policy (2026-07-14)
Andrew established a permanent, frozen architecture governing every current and future Brave Hearts audience funnel — full text in `docs/ENGINEERING/FUNNEL_CONSTITUTION.md`. Summary: every audience (Parents, Teachers/Librarians, Bookstores, Gift Buyers, Organizations) gets its own landing page, popup, lead magnet, Mailchimp journey, and KPIs, following one canonical sequence (Traffic Source → Landing Page → Popup → Lead Magnet → Email 1 delivery → Email 2 result-sell, no coupon → Purchase Decision → [purchased: post-purchase nurture, tracked as full-margin conversion] / [not purchased: Email 3 with audience coupon] → 30-60 day follow-up → long-term relationship). Purchase suppression before the coupon is mandatory, not optional. Automations must be built as reusable modules (Email 1 / Transformation Email / Purchase Check / Coupon / Post-Purchase / Long-Term Nurture), not one-off audience-specific workflows — future funnels change only copy/lead-magnet/coupon/attribution, not the underlying automation structure. **This architecture must not be reopened or replaced with a parallel system** — future work must extend it. See `FUNNEL_CONSTITUTION.md` for the complete, verbatim policy.

## Audience Coupon Policy — no public coupon advertising (2026-07-14, Frozen)
Do not hardcode or publicly render audience-specific coupon codes ([PARENT_COUPON_CODE_SUPERSEDED], [EDUCATOR_COUPON_CODE_SUPERSEDED], [GIFT_BUYER_COUPON_CODE_SUPERSEDED], future codes) in themes, plugins, landing pages, posts, or navigation. Audience coupon delivery is controlled outside public page templates. **Correction made 2026-07-14**: the Complete Collection landing page publicly advertised an [PARENT_COUPON_CODE_SUPERSEDED] coupon line in `plugins/brave-hearts-bundle-pricing/includes/bundle-landing-page.php` — a real violation of this now-frozen policy, found during Andrew's review. The line and its unused CSS rule were removed (no replacement discount messaging added), deployed to staging then production (plugin v1.8.2 → v1.8.3), and verified live on both environments plus a sitewide search confirming zero remaining occurrences. The underlying WooCommerce [PARENT_COUPON_CODE_SUPERSEDED] coupon (post 346) was not modified — confirmed byte-identical before/after. Full policy text: `ENGINEERING/FUNNEL_CONSTITUTION.md`. **This decision is Frozen** — future funnels' coupons ([EDUCATOR_COUPON_CODE_SUPERSEDED], [GIFT_BUYER_COUPON_CODE_SUPERSEDED], etc.) must never appear as public offers either.

## Audience Routing Constitution — known vs. unknown audiences (2026-07-14, permanent)
Known audiences (Parents, Teachers/Librarians, Gift Buyers, Bookstores, Organizations) are **always** sent directly to their own dedicated landing page/popup/lead-magnet/Mailchimp journey — never through a quiz or intermediate router. A future **Audience Routing Quiz** will handle only *unknown*-audience visitors arriving from organic sources (SEO, blog, Pinterest, organic social, shared links, AI search) — asking which audience they belong to before presenting a lead magnet, then routing them into the same existing per-audience journey a direct visitor would enter, with a post-signup choice to keep reading or visit the matching audience landing page. The quiz is explicitly **not part of any current sprint** and must not be built until every core audience funnel is individually production-complete and validated — building it earlier would have nothing correct to route into. Full spec: `ENGINEERING/FUNNEL_CONSTITUTION.md`'s "Audience Routing Constitution" section. This must not be reopened, started early, or replaced with a different routing concept without a new explicit decision.

### Reconciliation note (2026-07-29) — the routing quiz has since been built, approved, and shipped
The entry above still reads "explicitly **not part of any current sprint**" and "must not be built until every core audience funnel is individually production-complete." **That sentence is now stale, and recording it here is a currency correction, not an amendment to the decision.** The Find Your Adventure quiz was built and is **live on production** in theme **1.19.91**, deployed 2026-07-20 with Andrew's explicit approval, as the only sitewide popup — see `CHANGELOG.md`'s 2026-07-20 entry and `PROJECT_STATE.md`. Per `CLAUDE.md`, live systems and the repo win over a stale snapshot; per `FUNNEL_CONSTITUTION.md`'s own Amendment Process, frozen text is corrected by a dated addendum rather than edited in place, which is what this note is.

**What is still binding and was NOT changed:** known audiences are always routed directly to their own landing page/funnel and never through the quiz; the quiz serves unknown/organic visitors only; it is a router into the existing per-audience journeys, not a parallel funnel; it stays suppressed on audience landing pages, cart, checkout, account and thank-you pages. Subsequent quiz work (staging 1.19.93 and 1.19.95, 2026-07-29) changed only copy, per-answer result content, CTA labels and modal presentation — **no questions added, no destinations changed, no funnel redesign.** See `RELEASES/QUIZ_UX_PERSONALIZATION_1_19_93.md`.

## One-page-at-a-time approval rule for audience landing pages (2026-07-15, permanent)
After a batch-build sprint declared all 5 audience landing pages "complete" from DOM-text checks and two viewport widths, Andrew's own rendered capture showed a real P0 defect (entire sections invisible) that the batch verification had missed. Andrew established a permanent rule, superseding the batch-approval approach: build or repair one page → full QA (fresh-load section-by-section computed-style inventory, full breakpoint sweep, debug-control check in a genuinely fresh logged-out session, content-vs-spec review, PHP/JS error check) → produce evidence → Andrew reviews → revise until Andrew explicitly approves → only then move to the next page. Shared component files (CSS/JS used by every page) may still be fixed and deployed together in one pass, since that's one change applied identically everywhere — but a shared-component fix is never itself a page approval. Mandated order: Educators → Gift Buyers → Bookstores/Retailers → Organizations → final Parent regression review → cross-page staging QA → assets → Mailchimp connections → coordinated production deployment. **Do not batch-declare pages complete again.** See `ENGINEERING/AUDIENCE_LANDING_STATUS.md`.

## CSS specificity: sitewide `body:not(.home) h1/h2/h3` rules outrank single-class landing-page selectors (2026-07-15)
`style.css` has a sitewide heading-size rule (`body:not(.home) h1 { font-size: clamp(3.2rem, 7vw, 6rem); }`, and matching h2/h3 rules) with higher CSS specificity than a typical single-class landing-page selector like `.audience-landing-hero h1` (2 type-selectors beat 1 class + 1 element). This silently overrode the audience/parent landing pages' own intended heading sizes, rendering them larger than even the page's own already-oversized fallback — the real root cause of headlines wrapping 4–5 lines, not just a narrow text column as first assumed. Fix: every landing-page heading selector that sets `font-size` must include the page's own outer scope class (e.g. `.audience-landing .audience-landing-hero h1`, not `.audience-landing-hero h1` alone) to win on specificity against the sitewide rule. **Any future heading-level CSS added to `audience-landing.css`/`parent-landing.css` must follow this pattern** — a bare `<selector> h1/h2/h3` without the outer scope class will silently lose to the sitewide rule.

## Mailchimp plan upgrade; consolidated single-journey Parent automation (2026-07-14)
Andrew upgraded the Mailchimp account to a higher tier, resolving a hard cap on total Customer Journey steps that was blocking native purchase-suppression branching (Conditional Split) required by the Frozen Funnel Architecture. The old split design — a separate `Reluctant Reader Adventure Kit` automation (Email 1+2) feeding a separate linear `Coupon Flow` automation (Email 3/[PARENT_COUPON_CODE_SUPERSEDED], no purchase check) — was a workaround for the prior plan's step cap, not the intended architecture. Decision: consolidate into one canonical automation (`Parent - Acquisition Funnel`) — trigger → Email 1 → delay → Email 2 → purchase-sync buffer → Conditional Split → [purchased: exit to post-purchase nurture] / [not purchased: Email 3] → exit. This becomes the reusable template every future audience funnel (Teacher/Librarian/Homeschool, Bookstore, Gift, Organization) clones. The old two-automation split must be retired once the new journey is live and existing contacts have migrated — do not maintain both in parallel long-term. See `ENGINEERING/MAILCHIMP_STATUS.md`, `ENGINEERING/FUNNEL_CONSTITUTION.md`.

## Complete Collection is the Parent Funnel's primary offer, individual books secondary
Established 2026-07-13 in the "Audience Funnel System — Phase 1" sprint. The parent landing page (`page-reluctant-reader-adventure-kit.php`) leads with the Complete Collection (Hero secondary CTA, dedicated Collection section, first section after the lead-magnet block) rather than any single book. The pre-existing single-book "Choose Your Adventure" section was retitled "Prefer to Choose One Book First?" and demoted below the Collection/Trust/FAQ sections to reflect this. Do not re-promote a single book above the Collection on this page without a new explicit decision.

## Audience-funnel naming/tracking conventions are now formalized
Established 2026-07-13, see `docs/ENGINEERING/AUDIENCE_FUNNEL_ARCHITECTURE.md`. Every future audience funnel (Teacher/Librarian, Bookstore/Retailer, Gift Buyer, Organization) must follow the naming table and tracking model in that document rather than inventing new conventions per funnel. `BHP_Campaign_Landing` (staging-only) is the intended eventual technical foundation for future funnels' landing pages — new hand-built templates should not be created once it's promoted to production.

## Kept the `bhp_mariana_popup` storage prefix after restricting it to Teachers-only
When the Mariana classroom-guide popup was restricted from sitewide to
`/teachers/`-only, the storage-key prefix was deliberately *not* renamed
to `bhp_teacher_popup`, so a returning visitor's already-saved
dismissal/suppression state (from before the restriction) stays valid.
Only the analytics event names changed (to `teacher_popup_*`), since those
carry no suppression behavior of their own.

## Shipping fix: theme-level filter, not Bookvault plugin edit
Bookvault's shipping method injects live carrier rates because it never
declares `supports('shipping-zones')`. The fix was made as a
`woocommerce_package_rates` filter in the theme (`functions.php`), not by
editing the Bookvault plugin file, so plugin updates don't silently
re-break the fix and Bookvault's fulfillment API/order-submission/label
features remain completely untouched. Confirmed via unit-style testing
(synthetic rate objects) plus live browser checkout tests on both staging
and production before/after.

## Removed the redundant Teachers-page `the_content` filter
A `the_content` filter appending a lead-magnet CTA panel to the Teachers
page was added, then found to be non-functional/redundant — a real signup
form already existed on that page. Removed in `43fdf8a`. **Do not
reintroduce this** — if a similar-looking gap appears, check whether a
real signup mechanism already covers it before adding a content filter.

## Hardcovers kept out-of-stock as a deliberate business decision
All three hardcover editions are intentionally out-of-stock (not a bug)
until Bookvault hardcover fulfillment is connected and tested. Confirmed
directly with Andrew via an explicit choice between three options — do
not "fix" this by setting them in-stock without a new explicit decision.

## Subsidized shipping: customer always pays $3.99 flat
Approved pricing strategy: paperback $12.99, hardcover $18.99, shipping
$3.99 flat regardless of actual carrier cost — Brave Hearts absorbs the
difference against whatever Bookvault's real fulfillment cost turns out to
be. This is why Bookvault's live-rate method must never be customer-facing
(see the shipping fix decision above).

> ### 2026-09-02 - Correction: the product prices and the shipping model in the decision immediately above are STALE
>
> **Supersedes:** the price and shipping figures recorded in the paragraph directly above this note.
> **Status:** correction of record. The live storefront is the price of record.
> **Verified:** live product pages, 2026-09-02.
> **The superseded text is preserved above rather than rewritten**, so the movement is visible and is
> not re-derived by a future session.
>
> **What was wrong.** That paragraph records a paperback price of `$12.99`, a hardcover price of
> `$18.99`, and `$3.99 flat` shipping. **None of the three matches the storefront.** The figures appear
> to date from a pre-launch plan and were never corrected after the prices were set.
>
> **Prices of record, as published on the live storefront, 2026-09-02:**
>
> | Product | Price |
> |---|---|
> | Chapter book, paperback | `$11.99` |
> | Chapter book, hardcover | `$17.99` |
> | Complete Collection (paperback) | `$31.99` |
> | Coloring book | `$12.99` |
> | Book + coloring bundle | `$22.99` |
> | Hardcover + coloring bundle | `$28.99` |
>
> **Shipping of record.** Shipping is **tiered by the number of books in the order**, not flat. The
> tiers are `$1.99`, `$2.99`, `$3.99` and `$4.99`, and shipping is **free at three or more books**. The
> `$3.99 flat` figure recorded above is superseded. ⚠ **The `$3.99` is still correct as the ZONE
> CONFIGURATION**, one zone, one `flat_rate` method, which the bundle plugin then adjusts per cart. The
> two facts are different and conflating them is the documented failure here. See
> `.claude/rules/woocommerce.md` for the full tier table and its own dated correction.
>
> ⚠ **Note for anyone changing shipping copy.** The tier a customer sees depends on the contents of the
> cart, so a single hard-coded shipping sentence cannot be correct on every product page. **There is an
> open defect against exactly this on the coloring-book product page.** Do not copy a shipping figure
> from one template into another; read the tier logic.
>
> **Related records.** The full evidence, both sides of each superseded figure, and the open defect are
> held in the private Business OS registers. **Pointers only, by path**, per the public/private
> separation rule:
> - `Business OS\04-DECISIONS-REQUIRED-REGISTER.md` - decision `G-154`
> - `Business OS\20-CONFLICT-REGISTER.md` - `CYCLE179-CX-3`, and §73.1
> - `Business OS\17-CURRENT-OPERATING-STATE.md` - PART 50, PART 51
>
> **Applies to:** documentation only. **No price, product, coupon or shipping setting is changed by
> this entry.**

## Kirkus credibility component: one reusable partial + centralized data function, not per-template copies
Followed the theme's existing `bhp_render_amazon_affiliate_section()` /
`woocommerce_single_product_summary` pattern exactly (same hook,
adjacent priority) rather than inventing a new architecture, so the two
product-page trust/conversion features stay consistent for future
maintainers. All approved Kirkus content (quote, attribution, reviewed
title, URL) lives in exactly one place, `bhp_get_kirkus_review_data()` —
every placement calls the same `template-parts/components/kirkus-credibility.php`
partial with a `mode` argument, never a copy-pasted quote string.

## Kirkus quote never applied to Everest/Amazon Rainforest, even softly
The review is of *Adventures of Charlotte & Henry: The Mariana Trench*
only. Rather than reuse the quote with vague series-level language on
other product pages (which risks a parent reasonably believing Everest
was reviewed too), those pages get a distinct `series_note` mode with its
own wording that names the Mariana Trench book explicitly as "our debut
title" and never repeats the quote itself. Verified live on staging that
the Everest product page's rendered text does not contain the word
"Everest" anywhere inside the Kirkus component.

## No cart/checkout placement, and no click-to-purchase attribution tracking, this phase
Both were explicitly listed as available options in the task brief but
required either a formal UX audit (checkout placement) or session-
correlation logic touching cart/checkout-adjacent code (CTA-after-exposure
attribution) — both cross into territory this phase's own safety rules
said to leave alone. Documented as intentionally deferred rather than
built halfway.

## Theme version bumped for a CSS/JS-only change, confirmed necessary by direct testing
`wp_enqueue_style`/`wp_enqueue_script` key their cache-busting `?ver=`
query string off `wp_get_theme()->get('Version')`. During staging
verification, a fresh `wp theme install --force` plus `wp sg purge` was
NOT sufficient to get an already-loaded browser session to pick up the
new CSS — confirmed directly via computed-style inspection showing stale
values until the version number itself changed. Bumped 1.17.2 -> 1.17.3.
Lesson for future CSS/JS-only changes: the version bump is not just a
changelog convention, it is load-bearing for real users' browser caches.

## Full production re-verification, not a "staging passed so production is fine" assumption
After Andrew approved the staging package, the exact same verification
suite (schema inspection, wp eval-file test run, live browser checks on
homepage/product/shop/Everest/Amazon pages) was re-run directly against
`braveheartspublishing.com` post-deploy, rather than trusting the staging
result to carry over. This caught nothing new this time, but the
principle matches the theme's own testing rules: verify the actual
rendered output on the environment that matters, not the filter code or
an earlier environment's result.

## Amazon review phase (Phase 3): mirrored the Kirkus architecture deliberately, not reinvented
`inc/amazon-reviews.php` and `template-parts/components/amazon-review-showcase.php`
follow the exact same shape as the Kirkus phase's centralized-data +
reusable-partial pattern (`bhp_get_kirkus_review_data()` /
`kirkus-credibility.php`), and the product-page hook reuses the same
`woocommerce_single_product_summary` action, just at a later priority
(40, after Kirkus at 34 and the Amazon-affiliate CTA at 35). Consistency
with an already-reviewed, already-proven pattern was chosen over any
novel architecture, per the task's own instruction to mirror established
centralized-data conventions.

## Excluded a genuine, Verified-Purchase Everest review for authenticity, not policy compliance
One of Mount Everest's three written reviews reads: "This book is extra
special to our family because it was written by someone we love..." —
real, Verified Purchase, emotionally genuine. Excluded from the registry
anyway: a customer-review trust section is meant to demonstrate arm's-
length validation, and this review's own text discloses a personal
relationship with the author. Including it as generic "Amazon customer
review" social proof would be technically true but substantively
misleading about what kind of evidence it is. This leaves Everest with
only 2 registry entries instead of 3 -- an honest count, not a shortfall
to fix.

## No aggregate rating, no review count, individual stars only when Amazon showed them
Amazon's own displayed rating/count can change at any time and this
project has no automated, approved mechanism to keep a displayed
aggregate in sync with Amazon's real numbers. Rather than show a
snapshot that could silently go stale (or require ongoing manual
aggregate-rating maintenance), only each individual cited review's own
star rating is shown, exactly as Amazon displayed it for that specific
review -- including the one Everest review that is NOT Verified
Purchase, shown honestly without the badge rather than omitted or
mislabeled.

## Built and verified entirely on staging during an unattended overnight session, by explicit instruction
Andrew was asleep before a night shift and explicitly authorized
autonomous work within the stated permissions and stop conditions. No
step in this phase required stopping for him: The Amazon's zero-review
count was anticipated by the task's own instructions ("do not force
equal review counts... use fewer when fewer can be verified") rather
than being a true blocker, and no credential, payment, checkout, or
Bookvault boundary was ever approached. Production was never touched --
the single deploy target this session was staging (v1.17.4).

## Windows-built ZIP needs explicit forward-slash entry paths for the Linux server
`Compress-Archive` and `ZipFile.CreateFromDirectory` on Windows can
produce backslash-separated ZIP entry names. `unzip -l` on the SiteGround
server confirmed this would extract as literal-backslash filenames, not
real subdirectories -- caught before installing, not after. Fixed by
building the archive entry-by-entry with an explicit
`.Replace('\', '/')` on every relative path. The normal `git archive`-
based flow in the Runbook doesn't have this problem (git always uses
forward slashes); this only bites when a ZIP is built by hand outside
that flow, as was necessary here since nothing was committed yet at
build time (see "one dedicated commit" below).

## Amazon review showcase (Phase 3 + correction pass) deployed to production as one bundle, not two separate deploys
Production had never received Phase 3 (the base Amazon review feature) — only staging had it, at v1.17.4. When the v1.17.5 correction pass was approved, deploying it to production necessarily meant deploying the entire feature (Phase 3 + corrections) together in one commit (`a8d2667369b237fe3a711a584a567e849fb367e9`), since there was no intermediate v1.17.4-equivalent production state to deploy to first. Verified this was the correct, intended scope by diffing against the actual last-deployed production commit (`9ffd6aa`, the Kirkus-only release) rather than trusting a stale `git log` snippet from session start — the diff showed exactly the expected runtime files (`front-page.php`, `functions.php`, `inc/amazon-reviews.php`, `style.css`, `template-parts/components/amazon-review-showcase.php`) plus tests and docs, nothing from Bookvault/shipping/checkout/Mailchimp/Quiz/side-cart/SEO.

## Test files deployed to production alongside runtime files, via the same ZIP-deploy process
`tests/test-amazon-review-showcase.php` and `tests/test-kirkus-component.php` aren't runtime theme files, but the production-verification requirement to run both suites directly against production meant they needed to exist there. First attempt was to `scp`+`unzip` just the tests folder directly into the live theme directory — correctly blocked by the permission system as a live-file-write bypassing the documented `wp theme install --force` process. Fixed by rebuilding the full ZIP (via `git archive`) to include `tests/` and redeploying through the normal flow instead. Lesson: even non-runtime files needed on production should go through the same ZIP-install path, not a targeted file copy, so there's exactly one documented way theme files change on production.

## Kept the native WooCommerce "Reviews (0)" tab rather than hiding it
Audited during the staging correction pass: the "Customer Reviews for WooCommerce" plugin is actively installed (v5.113.0), reviews are enabled, every product has `comment_status = open`, and a full submission form (with photo/video upload) renders live. Zero submissions exist yet, but that's an honest count of real, functional first-party review infrastructure someone deliberately set up — not a dead default. Hiding it would work against a review-collection channel that's already built and waiting for real customers, for a purely cosmetic juxtaposition concern (Amazon reviews shown above, "Reviews (0)" shown below). Left untouched; the distinct headings already make the two systems distinguishable.

## Product-page Amazon review section: moved to `woocommerce_after_single_product_summary`, and why the grid-column fix was necessary
Originally hooked to `woocommerce_single_product_summary` (inside the narrow 2-column purchase summary), which made 3-review grids too narrow. Moved to `woocommerce_after_single_product_summary` priority 5 (ahead of native tabs/related products) for full width. This alone wasn't sufficient: that hook fires *inside* `.woocommerce div.product`, which is itself a CSS grid (`grid-template-columns: minmax(0,1fr) minmax(360px,.85fr)`), so anything added there without `grid-column: 1/-1` collapses into the grid's narrow first column -- the exact bug being fixed, reintroduced by the move itself. Fixed by adding the same `grid-column: 1/-1` pattern already used for `.woocommerce-tabs` and `.related.products` in the same grid. Caught by inspecting the theme's actual grid CSS before deploying, not by assuming the hook move alone would work.

## Compact catalog-card treatment scoped to the shop-loop call only, not the shared `compact` mode
`amazon-review-showcase.php`'s `compact` mode is used both by the homepage (already-approved design, one full-length excerpt per book with a source link) and by shop/catalog cards (need short excerpts, no badge, no link). Rather than change `compact` mode's behavior globally, new opt-in args (`max_excerpt_words`, `show_verified_badge`) default to preserving the original behavior and are only overridden in the shop-loop call (`bhp_woocommerce_loop_amazon_review_badge`). This kept the homepage's already-reviewed design untouched while fixing the catalog-card crowding complaint.

## Related-products "clipped" card was a false alarm -- it's a deliberate horizontal-scroll rail
Initial inspection of `.related.products ul.products` (`display:flex; flex-wrap:nowrap`) looked like a broken grid silently clipping the 4th related item via the section's `overflow:hidden`. Investigated further before reporting it as a bug: `style.css` (~line 1600) has a purpose-built "CSS-only horizontal expedition rail" comment, with `scroll-snap-type`, a custom-styled scrollbar, and `overflow-x: auto` on the inner `ul.products`. Confirmed by scripting `ul.scrollLeft = ul.scrollWidth` and finding all 4 items reachable. Lesson: verify a suspected layout bug by actually exercising the interaction (scrolling) before concluding it's broken, especially when the CSS includes purpose-built scroll-container styling.

## [PARENT_COUPON_CODE_SUPERSEDED] applies only to genuine, single-format Complete Collection carts
The coupon gives an additional 10% off, but only when the cart is exactly all 3 titles in one format (paperback or hardcover), no extras. Any other composition (single book, 2-book bundle, mixed format, invalid combination) is rejected with an explicit scope message, or auto-removed if the cart changes after application. Stacks on top of the existing non-coupon Bundle Savings fee rather than replacing it. Approved and deployed to production 2026-07-11.

## CTA Engine deployed to production as an isolated subset, not the full Phase 1D/1E suite
Only 4 classes shipped (`BHP_CTA_Engine`, `BHP_Content_Classification`, `BHP_CTA_Collision_Detector`, `BHP_Required_Links_Gate`) — explicitly not `BHP_Campaign_Landing`, `BHP_Conversion_Scoring`, the content-intelligence engine, or the HTML sanitizer/classification-completeness QA-gate classes, which remain staging-only. Verified via dependency analysis that the 2 QA-safety classes shipped are passive utility classes with no auto-wiring — safe to load without the `BHP_Content_QA_Gate` orchestrator that isn't part of this release. Deployed 2026-07-12.

## GTM/GA4 must not go live until a real consent mechanism exists — code-level gate, not a suggestion
`BHP_Analytics_Config::should_render_analytics()` checks 4 conditions (not internal traffic, tracking enabled for the environment, production consent-decision explicitly approved via `bhp_consent_decision_approved`, per-visitor consent granted) and fails closed if any is false. This is deliberate: with no consent banner and no approved production gate, GTM will not load in production at all — not "loads but tags don't fire." The gate is not to be bypassed by setting a placeholder consent-approved value; a real consent mechanism must exist first.

## GTM container built directly in the Google console, verified by Claude rather than rebuilt
Andrew built the container's variables/triggers/tags (24/38/39) directly in GTM's web UI rather than having Claude build them programmatically (GTM has no file-based config in this repo by design — see the "no ID hardcoded" decision in `analytics-architecture.md`). When a later session found this pre-existing build, the correct response was a read-only verification pass (spot-checking configuration correctness, cross-referencing against real codebase events) rather than rebuilding or assuming it needed redoing. Verified 2026-07-12: correctly constructed on the sample checked, not yet published.

## Consent vendor comparison stayed a technical recommendation, not an account creation (2026-07-13)
Researched 4 real, Google-certified WordPress CMPs (Complianz, CookieYes, Cookiebot, Real Cookie Banner) via live web search against current pricing/feature pages. Recommended CookieYes specifically because Consent Mode v2 — the one feature this integration needs — is on its free tier, unlike Real Cookie Banner (Pro-only) or Complianz (no free path at all). Did not create an account or accept any vendor's terms on Andrew's behalf, since every option requires that step regardless of free/paid tier — correctly treated as Branch B (external account required) per the task's own branching rule, not attempted.

## GTM Preview UI connection failure handled by switching method, not by giving up or faking results (2026-07-13)
Tag Assistant's browser-automation handshake to staging timed out twice (a known limitation of this session's browser tooling with GTM Preview's popup-window flow). Per the standing "two attempts, then switch methods" rule, pivoted to direct `dataLayer` inspection via the pre-existing, already-documented `bhp_staging_analytics_override` QA mechanism instead — captured real, verified payloads for `view_item_list`/`view_item`/`add_to_cart`/`add_shipping_info` directly from the live page, then turned the override back off. This is genuine evidence of site-side correctness, not a substitute for GTM's own tag-firing confirmation (which Phase 2's manual console audit already covered from the config side) — documented as a partial validation, not oversold as a full Preview pass.

## `wp eval-file` requires an explicit `<?php` opening tag, unlike a script passed to `wp eval`
Discovered while building the legacy-blog audit script: a file with no opening tag gets treated as plain text and dumped rather than executed. Fixed by prepending `<?php` before upload. Documented here so a future session doesn't lose time on the same mistake.

## Legacy-content audit corrected mid-session after a live spot-check contradicted the automated scan
A server-side, `post_content`-only audit script initially flagged 9 posts as having "zero topic cross-links." Live-rendering 2 of those 9 posts on production showed both actually get a theme-level "Related Field Notes" grid + hub link automatically, via `bhp_get_guide_registry()` — a template-level feature invisible to a content-only scan. Corrected the audit document in place rather than let the overstated finding stand, per the standing "verify live state, correct the canonical documents" rule. Lesson: any future content audit needs at least a small live-render spot-check before trusting a content-only scan's negative findings (absence of a feature is much easier to get wrong than presence).

## Header wordmark/tagline lockup fixed with column-direction flex, not a shrink hack (2026-07-13)
Root cause of the tagline-crowds-Home bug: `.site-logo a` was `display:flex` with the default row direction, so the flex algorithm treated the site-name text and the tagline span as items sharing one line, wrapping the name across multiple lines and squeezing the tagline against the nav with zero gap — reproduced live at Andrew's exact reported viewport before any fix was written, not assumed from reading the CSS alone. Fixed by making the lockup `flex-direction:column` (the two lines it was always meant to be) and giving the wordmark `white-space:nowrap` so it can never wrap again, since a wrapped wordmark is a worse failure than any other degradation step. See `RELEASES/HEADER_LAYOUT_FIX_PRODUCTION.md`.

## Desktop/mobile header breakpoint raised from 900px to 1180px, not squeezed further (2026-07-13)
Once the wordmark was protected from wrapping (see above), the previous "shrink via ugly wrapping" mechanism that had been silently absorbing the header's width pressure was no longer available. Direct width-by-width measurement (not assumption) showed the non-wrapping wordmark + full nav + full CTA genuinely cannot fit narrower than ~1180px, even after tightening every other degradable element (tagline hidden, nav/CTA letter-spacing reduced). Per the task's own explicit design priorities ("switch to mobile navigation before any element collision occurs"), moved the breakpoint rather than continuing to chase a moving target with smaller font sizes. Two staging deploys (1.19.5, 1.19.6) each closed part of the gap before this was found to be the real fix — each iteration was caught by re-measuring, not assumed fixed after one plausible-looking change.

## Production header-fix deployed from a fresh snapshot of production's own live files, not the git repo or staging (2026-07-13)
Pre-deploy drift check found production's live `style.css` missing a ~74-line WooCommerce coupon-contrast CSS block present in the git repo, plus 2 sections in different (minified) formatting. Deploying the repo's or staging's `style.css` directly would have silently reintroduced or altered that out-of-scope drift. Instead: downloaded a full tarball of production's current live theme directory, applied the exact same header-only edits to that copy, and built the deploy ZIP from it — verified via recursive `diff -rq` (before deploy: only `header.php`/`style.css` differ from live production; after deploy: zero difference from the ZIP payload). This is why `wp theme install --force` was used despite the user's initial "no full ZIP" instruction — that command deletes the existing theme directory before extracting the new one, so any ZIP fed into it must contain the complete file set; a genuinely narrow (2-file) ZIP would have deleted the rest of the theme. Andrew explicitly authorized this specific method after the tradeoff was explained. See `RELEASES/HEADER_LAYOUT_FIX_PRODUCTION.md` and `KNOWN_ISSUES.md`.

## `.single-post` bug fixed at its root cause instead of patched over with container queries alone (2026-07-13)
After the header layout fix (above) reached production, Andrew found it broken on a real blog post — worse than before the fix. Root cause: `style.css` had two bare `.single-post { max-width: 1120px; ... }` rules. WordPress's own `body_class()` independently adds `single-post` to `<body>` on every single-post template — a WordPress core convention unrelated to and coincidentally colliding with this theme's own `post_class('single-post section')` call on the `<article>` element in `single.php` — so the unqualified selector matched both, clamping the entire page (including the header) to a 1120px reading-width constraint at viewports far wider than that should ever have applied to anything. A first-pass fix (converting the header's `@media` breakpoints to `@container` queries) made the symptom disappear but left the actual bug in place. Andrew explicitly rejected shipping that as final, instructing: "Do not optimize for the smallest code change. Optimize for the cleanest architecture." A formal architecture review followed, confirming via `single.php` that `single-post` was only ever meant to scope the `<article>` — the real paragraph reading-width mechanism is the separately-scoped `.content-narrow` class, untouched by this bug. Fixed by qualifying both rules to `article.single-post`. Live measurement on staging confirmed `body.maxWidth` becomes `"none"` and the header renders identically to the homepage at the same viewport, with article/`content-narrow`/breadcrumbs widths unchanged. The container-query conversion was kept as a secondary, defense-in-depth layer (protects against any future unknown width-constraining context) rather than removed, since it is correct and low-risk on its own merits — but it is not the primary fix. See `RELEASES/HEADER_LAYOUT_FIX_PRODUCTION.md` and `KNOWN_ISSUES.md`.

## WPConsent's staging QA override bypass fixed to never outrank an explicit visitor choice (2026-07-13)
`BHP_Consent::current_state()`'s staging-only QA convenience (`bhp_staging_analytics_override`) unconditionally forced `analytics_storage` to `granted` whenever it was on, with no check for whether a real visitor consent cookie already existed. During WPConsent Free integration QA this was caught directly: with the override on, GTM loaded on staging even after explicitly clicking "Reject Nonessential" in the real banner — a live contradiction between the visitor's actual choice and what analytics actually did. Root cause: the override ran *before* the cookie-read logic and nothing after it could downgrade `analytics_storage` back to `denied`, only ever upgrade it. Fixed by gating the override on the ABSENCE of a real `bhp_consent_state` cookie (`$has_real_choice`), and by making every signal explicit (`granted` or `denied`) once a real cookie exists, instead of only ever upgrading toward `granted`. Verified in a real browser both directions post-fix: Accept → GTM loads exactly once; Reject → GTM does not load, override still on. The override's original purpose (letting a staging QA session observe events before any consent banner exists) is unaffected — it still applies whenever no real choice has been recorded yet.

## WPConsent Free chosen and integrated without modifying BHP_Consent/BHP_GTM_Loader/BHP_Analytics_Config's actual gating logic
Per the Phase 1B design ("the ONLY integration work needed here is for that CMP to set the `bhp_consent_state` first-party cookie"), the entire WPConsent integration is a new, small, additive file (`inc/class-bhp-wpconsent-bridge.php`) that listens for WPConsent's `wpconsent_consent_saved` JS event and writes the pre-existing cookie format — zero changes to the three core analytics classes, aside from the one narrow bug fix above (unrelated to WPConsent itself; it would have affected any future CMP tested the same way). WPConsent's own `google_consent_mode` setting was disabled so only `BHP_Consent` ever emits the *default* `gtag('consent',...)` call, avoiding two systems independently deciding a default posture — WPConsent's own `unlockScripts()` still fires a redundant `update` call on every save (hardcoded, not gated by that setting), verified harmless since both calls always derive from the same preferences object and can never disagree. See `ANALYTICS/CONSENT_STATUS.md` and `RELEASES/WPCONSENT_STAGING_IMPLEMENTATION.md`.

## Production WPConsent deploy stopped after discovering production has no GTM/consent theme infrastructure at all (2026-07-13)
Andrew explicitly approved an isolated 3-file production deploy (`functions.php`, `inc/class-bhp-consent.php`, `inc/class-bhp-wpconsent-bridge.php`) plus WPConsent Free install/config, per his own instruction to reconcile any doc/live-state conflict before deploying. Pre-deployment verification (`grep -rl` across the entire live production theme directory via SSH) found zero references to `BHP_Consent`, `BHP_GTM_Loader`, or `BHP_Analytics_Config` anywhere on production, and no `bhp_gtm_container_id` WP option — this infrastructure exists in the git repo and is fully wired on staging (`functions.php` `require_once` block, `inc/class-bhp-*.php` files present) but was never deployed to production. Every "GTM built, verified, not published" statement across the canonical docs was accurate about the GTM container in Google's own dashboard (variables/triggers/tags configured, workspace unpublished) but not about whether the theme code that would ever print it exists on production — it doesn't. This means the isolated 3-file consent patch Andrew approved would have been effectively inert (a bridge writing a cookie that nothing downstream reads) or would have required silently expanding to a materially larger change (5 additional infrastructure files) than what was actually approved. Andrew was presented with three options (expand scope to the full stack, stop entirely, or deploy the 3 files inertly) and chose to **stop entirely** — no production files were touched, WPConsent was not installed, nothing needed rollback since nothing changed. See `ANALYTICS/GTM_STATUS.md`, `ANALYTICS/CONSENT_STATUS.md`, `NEXT_TASK.md` for the correction and the scope decision now needed before any further production consent/GTM work.

## Read-only reconciliation performed instead of expanding production deploy scope live (2026-07-13, same day as the entry above)
After the production WPConsent deploy was stopped, Andrew explicitly directed a read-only reconciliation rather than either abandoning the question or deciding scope unilaterally: exact file inventory across production/staging/repo, a full dependency map, identification of the minimum coherent package, confirmation of staging-tested-together status, a file-by-file diff, confirmation no unrelated systems (dashboard/campaign/scoring/Mailchimp/Phase 1D/1E) would be pulled in, and prepared-but-not-executed deployment planning artifacts (manifest, patch plan, option plan, QA matrix, rollback plan). All checks were read-only WP-CLI/SSH/grep commands against production; no file was written, no plugin installed, no option set. This produced `RELEASES/PRODUCTION_GTM_CONSENT_READINESS_AUDIT.md`. One naming discrepancy was found and flagged rather than silently resolved: Andrew referred to `BHP_Order_Attribution`, which doesn't exist in the codebase — the actual dependency is `BHP_UTM_Attribution`. The audit's own recommendation (not yet approved) is that the minimum coherent package is exactly 6 files deployed with zero option changes, keeping GTM completely inert (empty container ID) independent of the `bhp_consent_decision_approved` gate, as the most conservative starting posture — but this is presented as a decision for Andrew, not something this session decided on his behalf.

## Full 6-file GTM/consent package approved and deployed to production after the readiness audit (2026-07-13, same day as the two entries above)
Andrew reviewed `RELEASES/PRODUCTION_GTM_CONSENT_READINESS_AUDIT.md` and explicitly approved the full 6-file package it identified as the minimum coherent unit — superseding the original narrow 3-file approval, which was moot once the dependency gap was found. Deployed via the snapshot method: pristine `functions.php` backup captured first, each of the 6 new files and the patched `functions.php` lint-tested and checksum-verified in a temp location before going live, `functions.php` diffed against its own pristine backup to prove only the approved 6-line block changed. WPConsent Free v1.1.7 installed from WordPress.org and configured identically to staging. `bhp_gtm_container_id`/`bhp_ga4_measurement_id` were deliberately left unset (confirmed via architecture and live testing that neither is required for the consent banner itself) and `bhp_consent_decision_approved` was not touched — GTM verified completely inert (`gtmScriptCount: 0`) in every one of 25 QA scenarios on the live production domain. Andrew also corrected a naming discrepancy from the prior reconciliation: he'd referred to `BHP_Order_Attribution`, which doesn't exist — the canonical class is `BHP_UTM_Attribution`, confirmed not to be renamed. See `RELEASES/PRODUCTION_CONSENT_DEPLOYMENT.md` for full QA results and `NEXT_TASK.md` for what comes next (authenticated GTM Preview, then a separate analytics-activation decision).

## GTM must not be published and consent must not be approved until the bundle-pricing plugin is redeployed to production (2026-07-13, Phase 10 validation) — RESOLVED same day
A full production-analytics validation pass (see `RELEASES/PHASE10_PRODUCTION_ANALYTICS_VALIDATION.md`) discovered that `brave-hearts-bundle-pricing` on production is v1.7.1 (dated 2026-07-06, pre-Phase-1B) while the repo/staging are at v1.8.2 — a plugin-deployment gap independent of and undiscovered by every prior consent/GTM deployment session, none of which touched this plugin. Live-verified consequence: most GA4 ecommerce events either don't fire under their expected name (`view_item`/`purchase`/`begin_checkout` are still the old `product_viewed`/`purchase_completed`/`checkout_started`), don't fire at all (`view_item_list`, `select_item`, `view_cart`, `add_shipping_info`, `add_payment_info`, `refund`), or fire with an empty/non-ecommerce payload (`add_to_cart` fires under the correct name but with no `items`/`value`/`currency`). Decision: **do not set `bhp_gtm_container_id`/`bhp_ga4_measurement_id`, do not approve `bhp_consent_decision_approved`, and do not publish the GTM container** until this plugin is brought to parity on production. **Resolved same day** — see the entry below.

## Isolated 7-file analytics patch deployed instead of the full v1.8.2 plugin (2026-07-13)
Andrew directed fixing the plugin-staleness gap above via the smallest safe patch, not a full-version deploy, after diffing every file between production's v1.7.1 and repo's v1.8.2 revealed the full version also bundles ~1,300 lines of unrelated, unreleased KPI/economics-dashboard work (`class-bhp-cost-config.php`, `class-bhp-offer-classifier.php`, `class-bhp-dashboard-page.php` — a separate Phase1A-v2 feature never approved for this release) and one unrelated coupon-hint UI line. The isolated patch (7 files: the main plugin file's version bump, `bundle-analytics.php`, a hand-patched `bundle-drawer.php` containing only 2 approved script-enqueue additions, `bundle-drawer.js`, `bundle-landing.js`, and 2 new JS files) was verified line-by-line against a pristine pull of production's actual live files, PHP-linted, JS-syntax-checked, and checksum-compared against staging's already-working copies before deploying. `includes/dashboard/` was left completely untouched — the plugin's own pre-existing "optional module" loader (see the entry above on safe optional-module loading) means production's existing dashboard keeps running unaffected, exactly as it did before this deploy. Post-deploy: full commerce regression QA (pricing/discount/shipping/coupon/checkout) showed zero regressions, and live analytics revalidation confirmed `view_item_list`/`select_item`/`view_item`/`add_to_cart`/`view_cart`/`begin_checkout`/`add_shipping_info`/`add_payment_info`/`bundle_add_to_cart` all now fire correctly with full GA4 ecommerce payloads. `contextual_cta_click`'s attribution-enrichment gap remains open (that fix lives in a theme file, `assets/js/nav.js`, out of scope for this plugin-only patch — see `KNOWN_ISSUES.md`). Full detail: `RELEASES/BUNDLE_PRICING_ANALYTICS_PARITY_PRODUCTION.md`.

## One dedicated commit for the whole Amazon review phase, not incremental commits
Unlike the Kirkus phase (several small commits as work progressed), this
task explicitly asked for "a dedicated local commit" with a specific
preferred title. All testing, staging deployment, and verification were
done directly against the working tree (building the staging ZIP by hand
rather than via `git archive HEAD`, since HEAD didn't yet contain these
files) so that the single final commit could be created only after
everything was already confirmed working -- not committed provisionally
and fixed up afterward.

## Hardcover fulfillment verification (2026-07-13) — supersedes the earlier "kept out-of-stock" entry above, does not overturn its intent
Andrew's current business direction is that Bookvault-backed hardcover sales are intended for the website (both individually and via the Complete Collection's hardcover format), conditioned on fulfillment configuration actually being valid. A dedicated verification pass (Hardcover Fulfillment Verification sprint) found:

- All 3 hardcover products (WC 14 Mariana Trench, 17 Mount Everest, 20 The Amazon) carry the Bookvault plugin's own local "synced" metadata (`bvlt_liked=true`, `bvlt_locations={"locations":[1,3]}`), identical across all three — this proves the plugin considers them enrolled for sync, not that Bookvault's backend has correct trim size/interior/cover files for any of them.
- No API access exists to Bookvault's backend to check print-job configuration directly; the Bookvault portal (`portal.bookvault.app`) is only accessible via Andrew's own authenticated login — not available to Claude Code sessions (see `docs/bookvault-chronology.md`).
- Order history was checked directly against the real WooCommerce orders table (`ugc_wc_orders`, not `wp_posts` — this site uses HPOS). The only hardcover item ever placed in a real order is Mount Everest Hardcover, in order #317 (`wc-refunded`, paid and fully refunded 24 minutes later as a documented test-mode transaction). #317 has zero Bookvault notes and no `BVRef` postmeta — it never routed to Bookvault at all before being refunded. Mariana Trench Hardcover and The Amazon Hardcover have **no order history whatsoever**.
- **Conclusion: hardcover Bookvault fulfillment has never been verified successful for any of the 3 SKUs.** This is not "one confirmed-broken SKU among two confirmed-good ones" — none of the three has ever cleared verification.

**Decision (protective, reversible): all 3 hardcover products were restored to `_stock_status = outofstock` on production** (2026-07-13), matching the original pre-existing decision above, pending Andrew's resolution of the specific question this sprint could not answer on its own: whether to (a) keep all 3 out of stock until a real successful hardcover order is confirmed, (b) treat "never ordered" (14, 20) differently from "ordered but never routed" (17), or (c) independently confirm via the Bookvault portal that print-job configuration is correct for all 3 and restore to in-stock on that basis. **Do not flip hardcover stock status again without a specific answer to this question from Andrew** — a timestamped backup of the pre-change state exists outside the repository, per the project's standing private-backups convention.

**Separately, resolved without ambiguity:** 4 blog posts (38, 64, 88, 90) had malformed doubled-protocol Amazon affiliate links (`href="https:// https://amzn.to/..."`, 7 total occurrences) that resolved to broken addresses in-browser — fixed via deterministic replacement, verified live, zero regressions. See `CONTENT/LEGACY_BLOG_CONVERSION_AUDIT.md`.

**Legacy catalog audit (read-only, no changes made pending approval):** the full WooCommerce product table has only 8 records total — the 6 live published products (3 paperback + 3 hardcover) plus 2 drafts. Draft product 12 (`...-legacy-lulu` slug) is the genuine former Lulu-fulfilled product, has 3 real historical sales, and is already correctly excluded from the storefront (draft status, no menu/cross-sell/content references, its old URL already 200s through to the current live product via WordPress's own old-slug mechanism) — recommend it remain archived as-is, no further action needed. Draft product 338 is an empty, broken variable-product shell (zero variations, zero sales, no Lulu meta) with an active Rank Math redirect already pointing its slug to the current live product — recommend permanent deletion, but not executed this session pending Andrew's explicit go-ahead per the sprint's own instruction ("recommend archive/delete actions before making any removals").

## Print-on-demand stock policy (2026-07-13) — formally supersedes both entries immediately above
Andrew corrected the framing of the two entries directly above this one: this is a print-on-demand business with no physical inventory, so "unverified fulfillment" was never the right basis for marking the 6 core products out of stock — that conflated inventory control (a mechanism this business doesn't use) with fulfillment-configuration risk (a real but different question). The canonical policy, verbatim from Andrew:

> Brave Hearts Publishing operates on a print-on-demand model through Bookvault. The six current individual book products—three paperback and three hardcover—are intended to remain in stock and purchasable. "Out of stock" is not an inventory-control mechanism for these products. It may be used only for a verified fulfillment failure or an explicit temporary sales suspension. Collections are backend groupings of the six products and do not create additional inventory-bearing editions.

**Action taken:** all 3 hardcover products (14, 17, 20) restored to `_stock_status = instock` on production (2026-07-13), confirmed live (Add to Cart enabled on all 3 product pages). Bookvault mapping directly re-verified across all 6 current products immediately before restoring: `bvlt_liked=true` and `bvlt_locations={"locations":[1,3]}` present and structurally identical on the Mariana Trench paperback variation (334), Mount Everest paperback (15), The Amazon paperback (18), and all 3 hardcovers (14, 17, 20) — no differentiating evidence of a broken or missing mapping on any hardcover SKU. Order #317's "never routed" status (see the entry above) does not meet the "verified fulfillment failure" bar this policy requires — it's a single unattempted/inconclusive data point (refunded before Bookvault's scan ran), not proof any SKU would fail to fulfill.

**This entry is the current, controlling policy for these 6 products' stock status.** Do not mark any of them out of stock again without either a verified fulfillment failure (a real order that Bookvault's own notes show as a genuine technical failure, not merely absent) or an explicit, current-turn sales-suspension decision from Andrew — an absence of completed orders is not sufficient grounds on its own.

## Audience-coupon scope lives on the coupon record, not in source code (2026-08-01)

The Collection-only restriction on audience coupons was enforced by a
hardcoded array of literal coupon codes in
`plugins/brave-hearts-bundle-pricing/includes/bundle-cart.php` — a file
tracked in a **public** GitHub repository. That made the security posture
self-defeating: the only way to give a rotated replacement code its
Collection-only scope was to publish the new code in the same public file
that leaked the old one.

It was also not merely a scope gap. Measured on staging, a coupon with
meta cloned field-for-field from the live source was accepted on an
ineligible single-book cart, and on a genuine 3-book Collection it
suppressed the Bundle Savings fee — the customer paid **$38.31 against
$34.51** with the legacy code, i.e. **$0.41 more than using no coupon at
all**. Cloning coupon fields never reproduced the behaviour.

**Decision:** scope is declared by the coupon record via the
`_bhp_audience_coupon` meta flag (plugin 1.8.8), surfaced as a checkbox on
the WooCommerce coupon screen, and resolved for every call site by one
shared helper. The legacy literal list is retained unchanged so the three
original codes keep working with no migration.

**Consequences that must not be forgotten:**
- A replacement coupon created on an environment still running plugin
  1.8.7 is an **unrestricted store-wide 10% code that also breaks
  Collection pricing**. The plugin must reach an environment *before* any
  replacement coupon is created there.
- Coupon code strings never go in the repo. IDs and audience labels only;
  real values live solely in gitignored `docs-private/`.

## Audience coupon inventory is larger than documented — verify, never assume (2026-08-01)

`STEP-02-COUPON-CREATION.md` asserted "only three coupons exist in the
whole system… there is no fourth or fifth code hiding anywhere." A
logged-in Mailchimp check found a **fourth** audience code quoted in the
Organization journey's Email 3 — recorded in no repo document, and
existing as a WooCommerce coupon in **neither** environment, so it would
fail at checkout. Organization carrying any coupon also contradicts the
frozen "no coupon for Organization or Retailer" policy.

**Decision:** the coupon inventory is whatever the live systems contain,
reconciled across **both** WooCommerce environments **and** every Mailchimp
email body. A repo document is never sufficient evidence that a code does
or does not exist. See `RELEASES/C1_C6_COUPON_ROTATION.md`.

## A displayed visit deadline is the earlier of the stated cutoff and the gate close

*Recorded 2026-09-02. Implemented in `1.19.351`. See
`RELEASES/PRODUCTION_RELEASE_1_19_350_354.md`.*

Two different dates govern a school visit and they are not the same thing.
The **order gate** is `bhp_school_visit_last_order_date()`, which is the
visit date **minus 2** and is the last date the site will actually accept
an order. The **stated cutoff** is a `cutoff` field on the registry row:
the date parents were told, on a flyer or a QR handout.

Before this decision, different surfaces computed a deadline
independently, and a surface could print a date **later** than either the
date parents were told or the date the site would still take an order. The
gap between the two is a grace window that was never advertised and must
never be printed.

**Decision:** one function, `bhp_visit_deadline_display()` in
`inc/visit-band.php`, returns the **earlier** of the stated cutoff and the
online close. It returns the registry's `cutoff` when that falls on or
before the online close, and the online close otherwise. **Every visit
surface reads it and nothing else computes a deadline**: the shop band
open and closed, and `/author-visits/` open and closed rows. Asserted
across 600 synthetic rows, 0 violations.

**Two boundaries this decision does not cross, stated because they are
easy to blur:**

- **The gate is unchanged and is still `visit − 2`.**
  `bhp_school_visit_last_order_date()` and
  `bhp_school_visit_is_open_on()` are untouched. This governs a
  **display**, never entitlement. A parent's ability to order is decided
  where it always was.
- **No registry row is edited by this.** The resolver reads; it does not
  write, and it does not default a missing field into existence. The visit
  data is the owner's.

A standing test gate locks the rule in, including the case that motivated
it: the never-advertised grace window cannot be printed under any row.

## One catalog card, on every surface that lists a product

*Recorded 2026-09-02. Implemented in `1.19.350`. See
`RELEASES/PRODUCTION_RELEASE_1_19_350_354.md`.*

Card rendering was gated on `is_shop()`. That gave `/shop/` a real product
card and gave **every other catalog surface** a 1110px tile with one price
and a navigation link styled as a button: six product-category archives,
twelve product-tag archives, and WooCommerce product search. Twenty-one
surfaces, two different answers to the same question, and the difference
was invisible to anyone who only ever checked `/shop/`.

**Decision:** the card is not a shop feature. It is what a product looks
like when it is listed anywhere. **One predicate,
`bhp_catalog_grid_context()`, and one CSS scope, `body.bhp-catalog-grid`,
govern every catalog surface**, and any new listing surface joins by
satisfying the predicate rather than by having its own branch added.

Consequences that follow from the rule rather than from taste, recorded so
they are not re-litigated as bugs:

- **Asset enqueues key off the predicate, not off `is_shop()`.**
  `inc/book-formats.php` was widened for exactly this reason: the archives
  were rendering the card **without its own stylesheet**.
- **The WooCommerce result count and sort select are removed from the
  DOM** on catalog grids. The count was stating a wrong number on real
  pages: four results above six cards on `/shop/`, two above four on
  search. A wrong count is worse than no count.
- **Reading order is set by a `pre_get_posts` filter, in theme code.** No
  `menu_order` is written. Ordering is a presentation decision and must
  not become product data.
- **Proof blocks and bundle cards live below the grid, not inside a
  card.** Their wording is untouched by this decision.
- **A product archive that renders no card is noindexed**, and
  `/product-category/hardcover-books/` 301s to `/shop/`. Hardcovers stay
  hidden from the grid and stay purchasable; that is deliberate and is not
  a stock defect.
