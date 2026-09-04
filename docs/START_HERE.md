# Start Here

> ## ⭐⭐ READ THIS FIRST, 2026-09-03 · **PRODUCTION IS THEME `1.19.358` / BUNDLE PLUGIN `1.8.83`.** Every version number below this block is SUPERSEDED.
>
> | | Theme | Bundle plugin |
> |---|---|---|
> | **Production, 2026-09-03** | **`1.19.358`** | **`1.8.83`** |
>
> Theme `1.19.358` and bundle plugin `1.8.83` were deployed to production on 2026-09-03 with the
> owner's explicit approval. `1.19.357` and plugin `1.8.82` were built and staging-verified on 2026-09-03
> and did not ship on their own; their contents reached production inside the later artefacts, so
> production moved two theme releases and two plugin releases at once. **The `1.19.357` and `1.8.82`
> artefacts are superseded and must not be deployed anywhere.**
>
> **Where to read what:** release record `RELEASES/PRODUCTION_RELEASE_1_19_357_358.md` · per-release detail
> `CHANGELOG.md` (2026-09-03 entry) · open issues `KNOWN_ISSUES.md` · rules from this series
> `DECISIONS.md`.
>
> ⭐ **The customer-visible change.** A school-visit link reopens in a **ship-only** state from the morning
> of the read-aloud and **never expires**. It names the school and offers ordinary shipping to the home;
> hand delivery is not offered and the shelf counters are gone. **It is generic and automatic for every
> registry visit, past and future**, so every past read-aloud is permanently orderable. Read
> `RELEASES/PRODUCTION_RELEASE_1_19_357_358.md` section 5 before answering a customer about it.
>
> ⚠️ **The version numbers and the deploy date above are recorded from the deploying lane, not read from
> production by this block.** Verify with `wp theme list --status=active` over SSH before relying on them.
> **Staging and production parity is not asserted here and should be checked, not assumed.**
> ## ⭐⭐ READ THIS FIRST, 2026-09-02 · **PRODUCTION IS THEME `1.19.356` / BUNDLE PLUGIN `1.8.81`.** Every version number below this block is SUPERSEDED.
>
> | | Theme | Bundle plugin |
> |---|---|---|
> | **Production, 2026-09-02** | **`1.19.356`** | **`1.8.81`** |
>
> Theme `1.19.356` and bundle plugin `1.8.81` were deployed to production on 2026-09-02 with the
> owner's explicit approval. `1.19.355` and plugin `1.8.80` were built and staging-verified on 2026-09-02
> and did not ship on their own; their contents reached production inside the later artefacts, so
> production moved two theme releases and two plugin releases at once. **The `1.8.80` artefact is superseded
> and must not be deployed anywhere.**
>
> **Where to read what:** release record `RELEASES/PRODUCTION_RELEASE_1_19_355_356.md` · per-release detail
> `CHANGELOG.md` (2026-09-02 entry) · open issues `KNOWN_ISSUES.md` · rules from this series
> `DECISIONS.md`.
>
> ⭐ **One customer-visible behaviour changed.** A parent flagged for one school who opens a different
> school's **closed** visit link loses the first flag; it does not return on its own, and reopening their
> own link restores it. An unknown or truncated slug still does nothing. Read
> `RELEASES/PRODUCTION_RELEASE_1_19_355_356.md` section 5 before answering a customer about it.
>
> ⚠️ **The version numbers above are recorded from the deploying lane, not read from production by this
> block.** Verify with `wp theme list --status=active` over SSH before relying on them. **Staging and
> production parity is not asserted here and should be checked, not assumed.**
> ## ⭐⭐ READ THIS FIRST, 2026-09-02 · **PRODUCTION IS THEME `1.19.354` / BUNDLE PLUGIN `1.8.79`.** Every version number below this block is SUPERSEDED.
>
> | | Theme | Bundle plugin |
> |---|---|---|
> | **Production, 2026-09-02** | **`1.19.354`** | **`1.8.79`** |
>
> `1.19.353` then `1.19.354` were deployed to production on 2026-09-02, each with the owner's explicit
> approval; bundle plugin `1.8.79` was deployed on 2026-09-02. `1.19.350`, `1.19.351` and `1.19.352` were
> built and staging-verified the same day and did not ship on their own; their contents reached production
> inside `1.19.353`.
>
> **Where to read what:** release record `RELEASES/PRODUCTION_RELEASE_1_19_350_354.md` · per-release detail
> `CHANGELOG.md` (2026-09-02 entry) · open issues `KNOWN_ISSUES.md` · rules from this series `DECISIONS.md`.
>
> ⚠️ **The version numbers above are relayed from the deploying lane, not read from production by this
> block.** Verify with `wp theme list --status=active` over SSH before relying on them. **Staging and
> production parity is not asserted here and should be checked, not assumed.**

> ## ⛔⛔⛔⛔⛔ READ THIS FIRST — 2026-08-05, LATER THE SAME DAY · **PRODUCTION IS NOW THEME `1.19.201` / BUNDLE PLUGIN `1.8.28`.** The block immediately below it, which says production is `1.19.199`, is SUPERSEDED.
>
> ⭐ **Verified live 2026-08-05 with the definitive instrument** — `wp theme list --status=active` and `wp plugin list` over SSH, run against **both** environments in one command:
>
> | | Theme | Bundle plugin |
> |---|---|---|
> | **Production** | **`1.19.201`** | **`1.8.28`** |
> | **Staging** | **`1.19.201`** | **`1.8.29`** (see below) |
>
> **The mobile-experience release shipped to production on 2026-08-05 with Andrew's approval.** Theme parity between the two environments is now exact. The performance/accessibility table in the superseded block below described this build while it was staging-only; it is still an accurate record of what changed, and it is still a **staging Lighthouse** measurement that must never be quoted as a PageSpeed Insights number.
>
> ### ⛔ Bundle plugin `1.8.29` is on STAGING ONLY and is NOT approved for production
>
> It removes two private values from the published source tree: the audience-coupon literal code list in `bundle-cart.php` (now empty; scope resolves from the per-coupon meta flag alone) and the hardcoded unit-economics amounts in the dashboard's cost config (now read from a per-environment site option). **Production still runs `1.8.28`.** ⚠️ **The production upgrade has a REQUIRED ORDERING STEP: the cost-model option must be seeded BEFORE the plugin is replaced**, because the seed reads the amounts out of the currently-installed code. A packet with the exact commands is prepared and held for Andrew.
>
> ### ⛔ ONE LIVE DEFECT WAS FOUND ON PRODUCTION AFTER THIS DEPLOY — see `KNOWN_ISSUES.md`
>
> A single browser console error, **`ReferenceError: jQuery is not defined`**, is thrown on the production home page by a third-party plugin script. **It is caused by this release** — 1.19.201 defers jQuery on non-commerce surfaces, and that script uses jQuery without being deferred itself. It reproduces identically on staging, so it is fixable and testable there. **No fix is deployed. It is diagnosed, not repaired.**
>
> ⭐ **Assert the production version from `wp theme list --status=active`, never from a document — including this one.**
>
> **The block below is preserved verbatim rather than edited, so the movement stays visible.**


> ## ⛔⛔⛔⛔ SUPERSEDED (see the block above) — 2026-08-05 · **PRODUCTION IS THEME `1.19.199` / BUNDLE PLUGIN `1.8.28`.** Every version number below this block, INCLUDING the `1.19.157` block, is stale.
>
> ⭐ **Verified live 2026-08-05 with the DEFINITIVE instrument, not by HTTP inference:** `wp theme list --status=active` and `wp plugin list` over SSH, run against **both** environments.
>
> | | Theme | Bundle plugin |
> |---|---|---|
> | **Production** | **`1.19.199`** | **`1.8.28`** |
> | **Staging** | `1.19.201` (see below) | `1.8.28` |
>
> ⚠️ **The `1.19.157` block immediately below named its own verification limit and was right to** — it used HTTP because that session held no SSH credentials, and it told the next session to re-check with `wp theme list --status=active`. **This block is that re-check.** Production advanced 42 theme releases and 12 plugin releases between the two readings.
>
> ### Staging is AHEAD of production, deliberately
>
> **Staging runs theme `1.19.201`** — the mobile-experience release. **It is NOT on production and has no approval to go there.** Local branch `feature/mobile-experience-1.19.201`.
>
> **What 1.19.201 changed, measured on staging with Lighthouse 12.8.2 (mobile emulation, simulated Slow 4G) — before vs after, same instrument, same URL:**
>
> | | 1.19.199 | 1.19.201 |
> |---|---|---|
> | Performance | 56 | **82** |
> | Accessibility | 93 | **100** |
> | First Contentful Paint | 2.4 s | **1.7 s** |
> | Largest Contentful Paint | 5.8 s | **4.7 s** |
> | Cumulative Layout Shift | 0.427 | **0.046** |
> | Total page weight | 1,461 KB | **1,006 KB** |
> | Render-blocking resources | 400 ms | **0 ms** |
>
> ⚠️ **These are STAGING LIGHTHOUSE numbers and must never be quoted as PageSpeed Insights numbers for production.** PSI runs from Google's infrastructure over a different network; the two instruments are not comparable. Only staging-before vs staging-after is claimed.
>
> ⚠️ **THREE VISIBLE COLOUR CHANGES** ride in 1.19.201, all forced by WCAG 1.4.3 contrast failures and all listed in `RELEASES/MOBILE_EXPERIENCE_1_19_201.md`: the "Best Value" pill's label, the format-card badge label, and breadcrumb links. **No pill, badge or brand colour changed — only label ink, plus an underline on breadcrumb links.**
>
> ### ⛔ Two things a new session must not get wrong
>
> 1. ⛔ **`git push` HAS NOT HAPPENED. The local history is roughly 330 commits ahead of `origin/main`, and NO local branch tracks a remote.** Production is running code with no remote copy. **The repository is PUBLIC on GitHub**, so what gets pushed is a sanitisation question, not just a git question — see `RELEASES/MOBILE_EXPERIENCE_1_19_201.md`.
> 2. ⛔ **SEVEN theme test suites fail, and they fail on the code CURRENTLY IN PRODUCTION.** `test-collection-purchase-path` · `test-content-intelligence-engine` · `test-cta-collision-detector` · `test-draft-package` · `test-header-collection-cta` · `test-lead-event-log` · `test-wave1-capture`. **Measured on both 1.19.199 and 1.19.201, identical list both times**, so 1.19.201 introduced none of them. **They are a real, unresolved, pre-existing debt and are not this release's to claim.**
>
> ⭐ **Assert the production version from `wp theme list --status=active`, never from a document — including this one.**
>
> **The blocks below are preserved verbatim rather than edited, so the movement stays visible.**


> ## ⛔⛔⛔ READ THIS FIRST — **PRODUCTION IS THEME `1.19.157` / PLUGIN `1.8.16`** (2026-08-03). Every version number below this block is stale.
>
> **Verified live 2026-08-03** by HTTP against the production home page (200): **11 theme assets at `ver=1.19.157`**, 4 plugin assets at `ver=1.8.16`.
>
> ⭐ **Production shipped THREE times on 2026-08-03** — `1.19.155`, then `1.19.156` (transactional email copy, E1–E7), then `1.19.157` (Bookvault dispatch tracker, **DRY mode**).
>
> **All three now have release records:**
> - `RELEASES/PRODUCTION_RELEASE_1_19_155.md`
> - `RELEASES/PRODUCTION_RELEASE_1_19_156.md` ⭐ **NEW — closes the gap the block below names**
> - `RELEASES/PRODUCTION_RELEASE_1_19_157.md` ⭐ **NEW**
>
> ⚠️ **Two things a new session must not get wrong about 1.19.157:**
> 1. **The tracker is DORMANT.** DRY mode: it writes nothing, and it has never completed an order or caused an email. Live-fire test ~2026-08-11 to 08-12.
> 2. **Its API credential is the owner's, installed in the owner's own terminal.** ⛔ **No agent holds it. Do not look for it, and do not ask for it in a chat.**
>
> ⚠️ **Verification limit, stated:** this correction used HTTP, **not** `wp theme list --status=active` — the session that made it holds no SSH credentials. **Re-check with `wp theme list --status=active` before relying on the version for a deploy decision.**
>
> **The block below is preserved verbatim rather than edited.**

> ## ⛔⛔ CORRECTION, SAME DAY — **PRODUCTION IS THEME `1.19.156`**, not 1.19.155. Plugin unchanged at **1.8.16**.
>
> **Verified live at closeout 2026-08-03:** `wp theme list --status=active` reads **1.19.156**; the live homepage enqueues **`ver=1.19.156`**.
>
> **Production shipped TWICE on 2026-08-03.** The block below records the first push (1.19.155) and is accurate for it; a second, theme-only deploy carrying the transactional-email **copy** layer followed while that record was being written. **Everything the 1.19.155 record verified — products, privacy policy, thumbnails, email configuration — is still true; only its version headline is stale.**
>
> ⚠️ **No release record exists for 1.19.156.** That gap is named, not filled — see `NEXT_TASK.md`.
>
> ⭐ **Assert the production version from `wp theme list --status=active`, never from a document — including this one.**

> ## 2026-08-03 — **THE 1.19.155 RELEASE** (version headline superseded above; the rest stands). Staging was the same.
>
> **This block supersedes every version number anywhere below, including the 2026-07-19 line.** Verified live by `wp theme list --status=active` and `wp plugin list` over SSH after the push, and by `ver=1.19.155` in the live page source.
>
> A production push shipped on **2026-08-03**: theme, bundle plugin, two product `post_content` records (333, 15), the privacy-policy sentence, cart-thumbnail cropping with 92 regenerated derivatives, and seven WooCommerce email/site options. **Full record with per-layer rollback: `RELEASES/PRODUCTION_RELEASE_1_19_155.md`.**
>
> ⛔ **The branch is UNPUSHED** — `feature/product-media-gallery-1.19.140`. Production is running code with no remote copy. **Pushing it is the top item in `NEXT_TASK.md`.**
>
> ⛔ **HEAD IS NOT PRODUCTION.** The commit deployed to production is **`e98cd0f` (1.19.155)**. Local HEAD has since advanced to **`237d71b` (1.19.156, the transactional email copy layer)**, which is **undeployed and unpushed**. **The working tree's `style.css` reads 1.19.156 and is one release ahead of the live site** — verify production with `wp theme list --status=active`, never from `style.css`.
>
> ⚠️ **Not proven by this release:** no test order was placed and **no transactional email was read** · Apple Pay unexercised on a real device · cart/checkout interaction geometry verified **on staging only**.
>
> ⚠️ **New open item:** `/book-bundles/` returns **HTTP 404 on production** while being a published page on staging. See `KNOWN_ISSUES.md`.
>
> **The narrative sections further down this file date to 2026-07-15/19 and describe an intermediate state. Trust this block, `PROJECT_STATE.md`'s newest block, and `CHANGELOG.md`'s newest entry over any of them.**

**Superseded — last updated 2026-07-19: PRODUCTION IS NOW THEME v1.19.86 + PLUGIN v1.8.6 (deployed and clean-browser validated; Fable lodash/underscore findings closed as environment-specific false positives; no hotfix, no rollback, no further production changes approved). Older text below that says production is at v1.19.52/1.19.58 is superseded. Previously: (independent audit fixes — Educator Toolkit connected to /teachers/, early homepage audience gateway, then the Educator Toolkit module resized to a compact supporting band — implemented and QA'd on staging at v1.19.58, approved pending Andrew's final manual visual check; production remains at v1.19.52).** Read this first, every session, before any engineering work. This is a one-page summary — `AI_CONTEXT_INDEX.md` is the full topic map, `PROJECT_STATE.md` is the detailed snapshot. **Note: the "Current engineering task" and release-history sections below this line were not fully rewritten this pass — several entries date to 2026-07-15 and describe an intermediate state. Trust `Current production status`/`Current staging status` below and `docs/CHANGELOG.md`'s newest entries over the older narrative paragraphs.**

## Current company/build phase
Analytics foundation. Core storefront, funnels, CTA Engine, GTM build, consent infrastructure, and production ecommerce-analytics event parity are all done and verified. WPConsent Free is installed, configured, and QA-passed on both staging and production. `brave-hearts-bundle-pricing` on production correctly fires `view_item_list`/`select_item`/`view_item`/`add_to_cart`/`view_cart`/`begin_checkout`/`add_shipping_info`/`add_payment_info` with full GA4 ecommerce payloads. Remaining blocker is purely the deliberate analytics-activation business decision — see "Major blockers" below.

## High-priority owner TODO — Mariana product media (2026-07-31)

Create a clean Mariana Trench product-gallery still set and short book flip-through at home in indirect window light. The hospital photographs are scouting references only. First approval set: front cover, Mariana depth/STEM spread, submarine-pressure Ocean Fact page, and courage/character page. Do not begin Higgsfield enhancement or website implementation until those clean source images are reviewed. Full requirements and staging-first handoff: `docs/NEXT_TASK.md`.

## Current engineering task
**Audience Landing-Page System — Gift Buyer page content update complete; Educators still awaiting formal approval.** After the P0 fix, shared-layout-refinement sprint, and Educator-review pass, the existing Gift Buyer page was checked against the shared landing-page specification, 2 content gaps closed, and full QA passed. Andrew established a **permanent one-page-at-a-time approval rule** — see `DECISIONS.md`; the Gift Buyer content update does not change Educators' position as the page next in line for formal approval. Theme v1.19.36 on staging. **No page is formally approved yet; production is untouched.** See `ENGINEERING/AUDIENCE_LANDING_STATUS.md`.

**Prior, still relevant:** Audience Funnel System Sprint 1B (Parent Funnel, complete/deploy/validate) — the landing page is **live on production** (theme v1.19.14). Mailchimp is now on **Standard Annual** (upgraded by Andrew, verified live 2026-07-14 — 6,000 email sends, no Customer Journey step cap). `Global - Tag Purchasers` (purchase-tagging automation) is Active. Draft automation `Parent - Acquisition Funnel` (id 89): trigger correctly configured (a prior-session bug where it was silently unsaved was found and fixed), Email 1 fully built (PDF delivery, verified via live preview). Still to build: delay + Email 2, purchase-sync buffer, Conditional Split, Email 3, testing, contact migration, old-flow retirement, two post-purchase automations — currently blocked by a newly-found tooling limitation inserting a second step into an already-populated flow (see `ENGINEERING/MAILCHIMP_STATUS.md`). The permanent Audience Routing Constitution (known audiences always direct, a future quiz only for unknown/organic visitors, not part of any current sprint) is now recorded in `ENGINEERING/FUNNEL_CONSTITUTION.md` and `DECISIONS.md`. See `CURRENT_TASK.md`, `NEXT_TASK.md`, `ENGINEERING/MAILCHIMP_STATUS.md`, and `ENGINEERING/PARENT_FUNNEL_STATUS.md`.

## Next approved task
**Continue building `Parent - Acquisition Funnel`** — see `NEXT_TASK.md` for the exact ordered remaining steps. Separately: **continue with the remaining legacy-blog batches** (original Batches 1/3/4 plus the sitewide P0–P3 mechanical-fix queue, 35 of 36 posts flagged) — confirm with Andrew which is next. See `NEXT_TASK.md`. GTM Preview/GA4 DebugView validation is on hold, not abandoned — a bounded diagnostic session 2026-07-13 proved the connection failures are caused by network-level blocking (this session's browser-automation tool blocks `googletagmanager.com`/`google-analytics.com`; Andrew's own browser also failed for a not-fully-diagnosed reason), not a GTM/consent/WordPress defect. See `NEXT_TASK.md`, `KNOWN_ISSUES.md`, `DECISIONS.md`. CookieYes (and the other 3 SaaS-CMP vendors researched 2026-07-13) remains rejected. **WPConsent Free** (official WordPress.org plugin `wpconsent-cookies-banner-privacy-suite`) is the approved solution, live on both staging and production. See `ANALYTICS/CONSENT_STATUS.md`.

## Most recently completed releases
- Audience Landing-Page System P0 fix + shared-layout refinement — staging, 2026-07-15 (`ENGINEERING/AUDIENCE_LANDING_STATUS.md`) — section-visibility defect root-caused and fixed, grid/hero-specificity/spacing/lead-magnet-honesty/trust/sticky-bar defects fixed in shared component files, theme v1.19.34, no page yet approved, production untouched
- Audience Funnel System Sprint 1B — Parent landing page deployed to production, 2026-07-14 (`ENGINEERING/PARENT_FUNNEL_STATUS.md`) — theme v1.19.14 live on production, zero PHP/console errors, popup/lead-magnet/Collection destination all re-verified live; paused at the Mailchimp login gate
- Audience Funnel System Phase 1 — Shared architecture doc + Parent Funnel landing page, staging only, 2026-07-13 (`ENGINEERING/AUDIENCE_FUNNEL_ARCHITECTURE.md`, `ENGINEERING/PARENT_FUNNEL_STATUS.md`) — Collection/Trust/FAQ sections added to the parent landing page, theme v1.19.14, Email 2 copy reviewed and ready; Mailchimp-level work blocked, documented honestly
- Print-on-demand stock policy — production, 2026-07-13 (`DECISIONS.md`) — all 6 core products (3 paperback + 3 hardcover) confirmed in-stock; Bookvault mapping re-verified structurally identical across all 6; empty legacy draft product permanently deleted; genuine former-Lulu draft product confirmed correctly archived
- Malformed blog-link fix — production, 2026-07-13 (`CONTENT/LEGACY_BLOG_CONVERSION_AUDIT.md`) — 4 posts' doubled-protocol Amazon links fixed and verified, zero regressions
- Conversion QA Sprint 1 — full live funnel validation, 2026-07-13 (`ENGINEERING/CONVERSION_QA_SPRINT1.md`) — findings-only sprint, no code changed; found 1 P0, 2 P1, 3 P2, 2 P3 issues
- Legacy Blog Conversion Batch 2 (posts 26, 66, 30) — fully production-complete, 2026-07-13 (`CONTENT/LEGACY_BLOG_CONVERSION_AUDIT.md`) — approved topic-hub copy AND earlier mechanical fixes (Amazon→direct book-link swaps, post 66's split-anchor repair) both live on production, zero regressions
- "Printed Just for You" print-on-demand notice — copy revision, production, 2026-07-13 (`ENGINEERING/PRINTED_FOR_YOU_STATUS.md`) — reusable component live on product/Cart/Checkout/Thank-You pages on both staging and production, theme v1.19.13
- Legacy Blog Conversion Sprint 1 (posts 76 & 68) — production, 2026-07-13 (`CONTENT/LEGACY_BLOG_CONVERSION_AUDIT.md`) — real in-body topic-hub + book-discovery links added/fixed on both posts, live-verified, cache purged
- Bundle-pricing analytics parity fix — production, 2026-07-13 (`RELEASES/BUNDLE_PRICING_ANALYTICS_PARITY_PRODUCTION.md`) — isolated 7-file patch, zero commerce regressions, all ecommerce events now fire correctly
- Phase 10 production analytics validation (read-only) — 2026-07-13 (`RELEASES/PHASE10_PRODUCTION_ANALYTICS_VALIDATION.md`) — discovered the plugin-staleness gap the release above fixed
- Production GTM/consent infrastructure + WPConsent Free deployment — production, 2026-07-13 (`RELEASES/PRODUCTION_CONSENT_DEPLOYMENT.md`)
- WPConsent Free consent banner — staging, 2026-07-13 (`RELEASES/WPCONSENT_STAGING_IMPLEMENTATION.md`)
- Header single-post scoping correction — production, 2026-07-13 (`RELEASES/HEADER_LAYOUT_FIX_PRODUCTION.md`)

## Current production status
**Theme v1.19.91 + bundle plugin v1.8.6 (deployed 2026-07-20, approved).** Both lead-magnet popups retired — the quiz modal is the only popup sitewide, auto-opening on every eligible page (cart/checkout/account/order-received still excluded). Homepage "Join the Expedition" section removed, Find Your Adventure quiz promoted into its place, homepage capture rerouted to the existing parent Adventure Kit funnel. **Supersedes audit finding #20.** Purchase path verified live post-deploy (variation 334, Stripe, $1.99 shipping, zero console errors). Rollback: `bhp-rollback-20260720-063726`. See `CHANGELOG.md` 2026-07-20. The paragraph below is the superseded 1.19.86 snapshot:


**Theme v1.19.86 + `brave-hearts-bundle-pricing` v1.8.6 (deployed 2026-07-19, approved).** Covers both Fable audit passes (36 findings + BH-01…BH-08). Source commit `b14e5f8`. **Production validated in a clean browser: Mariana variation 334 auto-selects and adds to cart, Stripe card fields render, no payment-method error, no `template`/`memoize`/`debounce` errors, zero console errors.** The failed Fable JS findings are **environment-specific false positives** caused by browser instrumentation altering the lodash/underscore globals — **no hotfix and no rollback were performed.** See `KNOWN_ISSUES.md` and `RELEASES/FABLE_AUDIT_REMEDIATION.md` § "PRODUCTION VALIDATION — 2026-07-19". ⚠️ Production page IDs differ from staging — resolve via `get_page_by_path()`. **No further production changes are approved.** The paragraph below is the superseded pre-deploy snapshot, retained for history:

Theme **v1.19.52** (deployed 2026-07-17 — Gift Buyer/Community Org page corrections, quiz auto-open trigger, lead-magnet DB restoration; see `CHANGELOG.md`'s 2026-07-17 entries and `docs/ENGINEERING/LAUNCH_URL_REGISTER.md`). All 4 audience landing pages (Parent, Educator, Gift Buyer, Community Organization) are live and their signup forms are fully operational on production — `bhp_lead_magnet_pdfs` confirmed correct for all 4 keys. **Sitemap:** resolved 2026-07-18 — Rank Math's stale physical sitemap cache was cleared (with explicit approval) and `page-sitemap.xml` regenerated; confirmed live at 30 URLs (up from 19), all 4 audience pages present, no code/content change involved. No sitemap work remains outstanding. WooCommerce live (Stripe live mode), 6 published products (3 books × 2 formats), all confirmed in stock. 36 published blog posts. `brave-hearts-bundle-pricing` plugin **v1.8.2**. WPConsent Free v1.1.7 live. GTM built but **not published** (unchanged from prior status).

## Current production status — READ THIS FIRST
**PRODUCTION IS THEME v1.19.121 (deployed 2026-07-31 on the owner's explicit approval). Staging is also 1.19.121. Local == staging == production, 147/147 byte-identical.** This supersedes every statement below that says production is 1.19.112 or 1.19.100 or earlier.

Shipped five accumulated releases as one package: 1.19.117 Homepage Phase 1a, 1.19.118 quiz question simplification, 1.19.119 quiz no-scroll fit, 1.19.120 hero mobile reorder, 1.19.121 screenshot fixes A–G. Deployed via full-ZIP `wp theme install --force` from a ZIP proven byte-identical to the approved staging build; caches purged; served assets report `?ver=1.19.121`. Verified live on production across nine viewports (hero order and caption clearance, nav breakpoint, 1 launcher/1 modal/1 quiz, quiz centring and fit, all five result offers, 16/16 dismissals at 0px drift, all three auto-open behaviours, commerce and Complete Collection Hardcover default). Products, plugins, prices, lead magnets and `page_on_front` all diffed **UNCHANGED**; zero theme-file PHP errors; zero console errors. Backup and one-command rollback: `~/bhp-PROD-backup-1.19.121-20260731/`. Full record: `RELEASES/SCREENSHOT_FIXES_1_19_121.md` and `CHANGELOG.md`'s 2026-07-31 production entry.

## Current staging status
**Theme v1.19.121 (2026-07-31) — now level with production.** The paragraph below described this build while it was still staging-only:

**Theme v1.19.121 (2026-07-31, pre-deploy snapshot) — staging only, awaiting owner review AND a required real-iPhone check. Production is 1.19.112, verified untouched.** Staging is **five** releases ahead (1.19.117 → 1.19.121); they would ship as one package.

**Seven screenshot-driven fixes (A–G)**, each root-caused from the owner's 3 desktop + 4 real-iPhone screenshots. **A** the hero caption was painted behind the covers (their `translateY(24px)` adds zero layout height) → 19.1px clear, zero overlap. **B** the mobile dialog was a bottom sheet → centered with `100dvh` + safe-area padding, 0.0px deviation at all 12 viewports. **C** a modal-scoped 10px margin outranked the component rule → question→answers gap now 32/27.2/24.0/18px at 1440/1024/768/390, no question screen scrolls. **D** the result was too tall with the submit below the fold → offer 3 lines → 2, two-column fields, 52px submit; **submit visible for all five offers at all 12 viewports**. **E** the consent gear is a shadow-root child at z-index 9999 and the modal was 2100 → modal raised to 10000, gear no longer clickable through the overlay, consent system untouched. **F** the D2 rule forced the hamburger visible at top level → display returned to the container query; never both nav modes. **G** homepage consolidated to exactly 1 launcher / 1 modal / 1 quiz with the `#find-your-adventure` anchor moved to the launcher. Regression clean (both auto-open triggers, 16/16 dismissals at 0px drift, cart/checkout exclusions, zero console errors, no Mailchimp contact). **Limitation stated plainly: this environment has no Safari toolbars, so the real-iPhone condition behind the bottom-sheet and gear screenshots could not be reproduced — Parts B and E need owner verification on the device.** Full record: `RELEASES/SCREENSHOT_FIXES_1_19_121.md`. The paragraph below is the superseded 1.19.120 snapshot:

**Theme v1.19.120 (2026-07-31, superseded) — staging only, awaiting owner review. Production is 1.19.112, verified untouched.** Staging is **four** releases ahead (1.19.117 Homepage Phase 1a, 1.19.118, 1.19.119, 1.19.120) and they would ship as one package.

**Homepage hero: the three-book preview now sits under the H1 on mobile.** Order is eyebrow → H1 → covers → supporting paragraph → primary CTA → secondary CTA → signature. Done structurally, via a new backward-compatible `aside_after_title` argument on the shared hero component (**default `false`**, homepage the only opt-in) — the markup renders **exactly once**, and **no `order`, absolute positioning or transforms** are used, so keyboard order matches the visible order. **Desktop is provably unchanged**: above 768px the preview is explicitly grid-placed, and moving the node back to its old DOM position in the live page produced *identical geometry to 2dp* at 1024/1366/1440. A **pre-existing 320px clipping defect** was found and fixed along the way (the hero grid track was 284px inside a 244px container, cutting off the third cover, both CTAs and the H1 with no scrollbar) — `minmax(0, 1fr)` scoped to ≤380px. QA across all nine required viewports: covers proportional and uncropped (0.26% max delta), 0 duplicate IDs, 0 broken images, 0 console errors, CLS 0. Other hero callers (`/about/`, `/books/`, `/contact/`, `/teachers/`) verified structurally unchanged; quiz untouched and non-regressed. **Flagged for the owner:** 768×1024 tablet portrait also gets the new order. Full record: `RELEASES/HOMEPAGE_HERO_MOBILE_ORDER_1_19_120.md`. The paragraph below is the superseded 1.19.119 snapshot:

**Theme v1.19.119 (2026-07-31, superseded) — staging only, awaiting owner review. Production is 1.19.112, verified untouched.** Staging is **three** releases ahead (1.19.117 Homepage Phase 1a, 1.19.118, 1.19.119) and they would ship as one package.

**Quiz question screens now fit without scrolling.** 1.19.118 enlarged the answers but left them in a single column, so Q1 needed **537.7px of a 548px budget** — ~10px of headroom — clipping its fourth answer below roughly a **570px** viewport height, and overflowing by 27px at 320×568. Fixed with a real CSS grid: one column on mobile, **two from 760px** (Q1 a 2×2; Q2 two-plus-a-full-width-third), with the question-step measure widened to **720px** in a **780px** dialog because the longest answer needs ~261px of label width to break in two. New `bhp-quiz--step-1/2/--question` state classes keep all compaction off the result screen, which still uses 640px and is unchanged. Verified at all eight required viewports plus 568×320: Q1/Q2 `scrollHeight === clientHeight`, **0 scroll regions, 0px scrollbar**, everything inside the dialog with 16–26px clear below, no clipping, no overflow, 0 console errors, parity 147/147. Regression clean (12 results, 16/16 dismissals at 0px drift, both auto-open triggers, keyboard order matches visual order). **Limitations stated:** 320×568 Q1's longest answer still takes three lines in one column; 667×375 keeps one column because two weren't needed. Full record: `RELEASES/QUIZ_QUESTION_FIT_1_19_119.md`. The paragraph below is the superseded 1.19.118 snapshot:

**Theme v1.19.118 (2026-07-31, superseded) — staging only, awaiting owner review. Production is 1.19.112 and was verified untouched after the deploy.** Staging is **two** releases ahead of production (1.19.117 Homepage Phase 1a, then 1.19.118) and they would ship as one package. **Everything below this paragraph that says staging is 1.19.100 or that production is 1.19.91/1.19.100 is superseded** — verify against `wp theme list --status=active` before trusting any of it.

**Quiz question screens simplified.** The promotional header above both question screens — `2 QUESTIONS · ABOUT 30 SECONDS`, `Where Should Your Adventure Begin?`, and the `No wrong answers…` paragraph — is removed from the DOM in the sitewide modal, `/find-your-adventure/` and the shortcode. It measured **195.6px at 1440×900** and **231.3px of a 544px dialog at 320×568**, where the question screen began 299.3px in. Question 1 now shows only: close button, `QUESTION 1 OF 2`, the question, four answers. The homepage `intro_gate` card is a different element and is untouched; `/find-your-adventure/` keeps its own `<h1>`, so it went from two stacked introductions to a clean `H1 → H2`. Typography at 1440: progress 12→16px, question 18→34px and now a real `<h2>`, answers 15→22px; dialog at 390×844 **651.8 → 510.3px**. The dialog's accessible name now follows the **visible** question (the old headline was never part of `aria-labelledby` — that was verified, not assumed), hidden steps never label it, 0 duplicate IDs, and a `role="status"` region announces each new question once. QA: 7 viewports × 4 routes × 12 results, one primary CTA each, 16/16 dismissals at 0px drift, both auto-open triggers proven, zero console errors, parity 147/147, **no Mailchimp contact created**. **Two flagged deviations:** answers are left-aligned again, reversing the production-live 1.19.100 optical-centring work, per the current-turn brief; and the desktop two-column answer grid was dropped. At 768×1024 type interpolates between the brief's two tiers. Full record: `RELEASES/QUIZ_QUESTION_SIMPLIFICATION_1_19_118.md`. The paragraph below is the superseded 1.19.100 snapshot:

**Theme v1.19.100 + bundle plugin v1.8.7 (2026-07-30, superseded).** CSS-only correction: quiz button labels are now optically centred. The answer label was a flex item sharing the row with the arrow, so its box sat left of the button's true centre — **measured −9.5px**. Fixed structurally: arrow absolutely positioned out of flow at `right: 16px` with `translateY(-50%)` leading every transform state, symmetrical 34px horizontal padding, `justify-content: center`. Both CTAs (intro/start and result) moved from `inline-block` to `inline-flex` with explicit centring, `line-height: 1.25` and `min-height: 48px`. **Measured after: 16 answer buttons at 0.0px on both axes across all 5 viewports and 4 routes, including 6 multi-line labels; CTAs 0.0px horizontal / ≤0.2px vertical.** Long CTAs wrap centrally on mobile without overflow. Only `audience-quiz.css` + `style.css` changed — verified by checksum that no JS, PHP, plugin or product file differs from the 1.19.99 baseline. All prior behaviour regression-tested intact. **Production untouched: theme 1.19.91, plugin 1.8.6, Mariana media unchanged.** The paragraph below is the superseded 1.19.99 snapshot:

**Theme v1.19.99 + bundle plugin v1.8.7 (2026-07-30).** Both previously-blocked commercial decisions resolved by Andrew and implemented. (1) **Complete Collection now defaults to Hardcover** — new `bhp_bundle_default_format()` is the single source of truth for the selector, pricing panel and final CTA. Fresh load verified: Hardcover selected/`aria-checked`, $48.99 shown, both CTAs `complete_hardcover_smart`; cart from the default state gives 3 Hardcovers, $53.97 − $4.98 = **$48.99**, shipping **$4.99** (kept as-is per decision), tax $2.94, total $56.92. **`bundle-cart.php` — all shipping and coupon logic — was not modified or deployed** (checksum identical). Paperback still switches perfectly ($31.99 / $3.99 shipping); [PARENT_COUPON_CODE_SUPERSEDED] unchanged. `bundle_page_view` now reports the active `format`. (2) **Star gold deepened** via new `--color-gold-deep: #9A6A00` applied only to `.home-trust-proof__stars` — contrast **1.79:1 → 4.28:1**, hue 41°/sat 100% (amber-gold, not brown); pill and neighbours untouched. Quiz 1.19.98 work regression-tested intact at all five viewports. **Production untouched: theme 1.19.91, plugin 1.8.6, Mariana media unchanged.** The paragraph below is the superseded 1.19.98 snapshot:

**Theme v1.19.98 (2026-07-30).** Two low-risk improvements shipped to staging; a third was stopped on a business conflict. (1) **Quiz results now lead with the free resource** — `result_text` refactored into structured `result_resource` + `result_detail` across all 16 entries, rendered as a real `<strong>` + text node (no `innerHTML`/regex/punctuation-splitting), bold `1.06em` brand green, no modal height increase, no "PDF" claim added. A fallback bug caught in QA — the organization partnership answer wrongly advertising a "Free Community Reading Kit" it doesn't offer — was fixed before the final build. (2) **Homepage five-star pill** now matches its three neighbours exactly (measured identical background/border/colour/padding/radius/height); gold confined to the stars via `--color-gold`, stars `aria-hidden` with real "5 out of 5 stars" text. (3) **Complete Collection hardcover default: NOT done, blocked** — defaulting to hardcover changes default shipping $3.99 → **$4.99** and default price $31.99 → **$48.99**, both verified in a live cart; the brief forbids changing shipping, so this needs Andrew's decision (see `NEXT_TASK.md`). Full QA: 5 viewports × 4 routes all PASS, 0px page-position drift on all four dismissals, zero console errors. **Production untouched at 1.19.91.** The paragraph below is the superseded 1.19.96 snapshot:

**Theme v1.19.96 — reconciled and confirmed as the single authoritative production candidate (2026-07-30).** The open `assets/js/quiz-modal.js` working-tree question is **closed with no code change**: that edit was already integrated and deployed (byte-identical across the working tree, deployed staging, the 1.19.96 ZIP and the 1.19.95 backup), all three of its logical changes are authorised — page-scroll restore and "Keep browsing this page" from the 1.19.93 brief, plus the WPConsent shadow-DOM detection fix from background task `task_8f952193` that Andrew started — it is complete, it does not conflict with the 1.19.96 internal-scroll fix, and nothing is still writing to it. **All 143 files of the intended source set match deployed staging exactly**, so no version bump and no redeploy were needed. Full regression re-run on the combined candidate: 5 viewports × 4 routes, all PASS (every screen starts at `scrollTop 0`, nothing clipped, focus trapped both directions, all four dismissals at 0px page-position delta, resume-on-reopen intact, zero console errors). **Production untouched at 1.19.91 — staging is three releases ahead (1.19.93 → 1.19.95 → 1.19.96) and they would ship as one package.** ⚠️ **Do not verify served assets with `curl` on this host** — SiteGround edge security returns HTTP 202 and a ~292-byte challenge instead of the file; use a real browser. Full record: `RELEASES/QUIZ_UX_PERSONALIZATION_1_19_93.md` § "Reconciliation". The paragraph below is the pre-reconciliation 1.19.96 snapshot:

**Theme v1.19.96 (2026-07-30) — ahead of production by one release.** Focused correction: the quiz modal's **internal** scroll position was carrying from one screen to the next. Reproduced on the 1.19.95 baseline at 1024×420 — a visitor who scrolled inside the modal to reach the third or fourth Question 1 answer arrived at Question 2 with `scrollTop 89` retained, pushing the eyebrow 38px above the visible area, on **all four** routes. Root cause: `showStep()` swapped the `hidden` steps but never reset `.bhp-quiz`, which became the modal's single scroll region in 1.19.95. Fixed by centralising a container-only reset at the end of `showStep()` (every transition already routes through it) plus hardening the `focusQuietly()` fallback so a focus-driven scroll cannot undo it. **No `window.scrollTo()`; the underlying page position is never touched.** Verified with real browser scroll + real click on the organization and gift answers: Question 2 opens at `scrollTop 0` with eyebrow/headline/lead/progress/question all fully visible. The 1.19.95 page-position fix regression-tested at **0px delta on all four dismissal methods**; Tab/Shift+Tab still trapped, focusable sets contain only visible in-dialog controls, zero console errors. Copy, routes, results, CTA wording/colour, destinations, UTMs, analytics, auto-open and consent all unchanged. Full record: `RELEASES/QUIZ_UX_PERSONALIZATION_1_19_93.md` § "Third pass". **Production untouched at 1.19.91.** Screenshots remain unavailable in this environment (tool times out — see `KNOWN_ISSUES.md`); evidence is DOM measurement. The paragraph below is the superseded 1.19.95 snapshot:

**Theme v1.19.95 (2026-07-29) — ahead of production by one release.** Second quiz pass, building on 1.19.93 below: warmer supporting copy ("No wrong answers—…"), Q1 reworded to "What would you like help with today?", the overlapping educator answer replaced with "History and vocabulary connections" (its `quiz_intent` value deliberately preserved), the parent "less resistance" result de-negated, and every launcher/start CTA standardised to **"Find My Best Next Step"** with result CTAs on "Get …" (never "Download" — these lead to landing pages). **Two genuine defects found by measurement and fixed:** the quiz's primary CTA had no working hover state anywhere on the site (the sitewide `.btn-primary !important` killed both the quiz's green declaration and style.css's own hover rule — the gold was an accident), and the modal's close button scrolled out of view on short viewports. The modal headline no longer inherits the 64px sitewide section scale (now 46–52px desktop / 30px mobile); dialog height 584px → 546px at 1440×900. **Standalone homepage and `/find-your-adventure/` presentations verified unchanged.** Scroll restoration regression-tested: **0px drift, all four dismissal methods, four positions on a 9,954px page, plus after an automatic open.** Full record: `RELEASES/QUIZ_UX_PERSONALIZATION_1_19_93.md` § "Second pass". **Production untouched at 1.19.91.** Note: `DECISIONS.md` and `FUNNEL_CONSTITUTION.md` still contained "the quiz must not be built yet" text — stale since 2026-07-20; dated reconciliation notes were added to both, no frozen policy changed. The paragraph below is the superseded 1.19.93 snapshot:

**Theme v1.19.93 (2026-07-29) — ahead of production by one release.** Find Your Adventure quiz UX/copy pass: every Question-2 answer now produces its own result headline/text/CTA (12 distinct results, all verified live), mismatched answers removed ("Author visit information", "Read-aloud ideas", gift occasions), "is a good fit"/"Get the Free …" wording replaced, `YOUR BEST NEXT STEP` result eyebrow, Question 1/2 of 2 progress labels, "Keep browsing this page" added and "Open the full quiz page" removed inside the modal. **Modal-close scroll defect fixed — measured 0px drift on all four dismissal routes after both manual and automatic opens** (counterfactual on the same page: plain `focus()` moves the visitor +2454px). A pre-existing homepage contrast defect was found and fixed (question prompt was 1.25:1 on the navy section, now 11.48:1). Audience destinations, the Frozen Audience Routing Constitution, UTMs, analytics event names and auto-open timing are all unchanged. **Production untouched at 1.19.91; no production approval requested.** Full record: `RELEASES/QUIZ_UX_PERSONALIZATION_1_19_93.md`. Known, pre-existing, not fixed here: Tab leaks focus from the modal to WPConsent's container (reproduces on production 1.19.91 too). The paragraph below is the superseded pre-1.19.93 snapshot:


Theme **v1.19.58** — ahead of production by four releases: (1.19.53) the audience-page discoverability layer (CTA engine extension for 3 new audience destinations, footer "Resources for Every Reader" cluster, homepage direct-access line beneath the quiz); (1.19.54) homepage "Join the Adventure Club" form UX fix (scroll-to-success + busy state, sitewide) — the sitemap investigation from this same release is now fully resolved on production directly (cache clear, no theme code involved — see "Current production status" above and `CHANGELOG.md`'s RESOLVED note); (1.19.55) the two approved independent-audit conversion fixes — an Educator Toolkit conversion module connecting `/teachers/` to the actual toolkit landing page, and a new early-homepage audience gateway module ("What brings you here today?") routing visitors before most book/founder content; (1.19.58) the Educator Toolkit module revised from an oversized hero-like treatment into a compact two-column supporting band (see `CHANGELOG.md`'s newest entry — intermediate versions 1.19.56/1.19.57 were build-cache-busting steps during that fix, superseded, not separate releases). See `CHANGELOG.md`'s 2026-07-18 (newest) entry for full detail. **Superseded — this work shipped to production on 2026-07-19 as part of v1.19.86.** Matching theme version numbers still doesn't prove full content parity elsewhere — always diff the actual files/content in question for anything not explicitly confirmed at parity.

## Major blockers
**GTM publication (on hold, root cause known)** — both the consent infrastructure and production's ecommerce-analytics event correctness are done and verified. GTM remains deliberately unpublished — `bhp_gtm_container_id`/`bhp_ga4_measurement_id` are unset and `bhp_consent_decision_approved` stays `false` by design. A bounded diagnostic session 2026-07-13 proved Preview/DebugView connection failures are caused by network-level blocking (this session's tooling + likely Andrew's local antivirus/DNS filtering), not a GTM/consent/WordPress defect — see `KNOWN_ISSUES.md`. Next step needs a genuine unblocked network/browser, not a repeat of the same workflow. See `RELEASES/PRODUCTION_CONSENT_DEPLOYMENT.md`, `RELEASES/BUNDLE_PRICING_ANALYTICS_PARITY_PRODUCTION.md`, `ANALYTICS/GTM_STATUS.md`, `ANALYTICS/CONSENT_STATUS.md`, `NEXT_TASK.md`.
**Minor, non-blocking:** `contextual_cta_click` still lacks full CTA attribution on production (theme file `nav.js` is stale) — see `KNOWN_ISSUES.md`.
**Google Merchant Center** — all 6 synced products show "disapproved"; needs Andrew to open the console and read the actual reason (`MARKETING/GOOGLE_MERCHANT_STATUS.md`).

## Systems that must not be reopened
Hardcover out-of-stock status; the removed Teachers-page `the_content` filter; parent/teacher funnel isolation design; the "never fabricate reviews/ratings" rule; completed CTA Engine and [PARENT_COUPON_CODE_SUPERSEDED] scope decisions; **the Frozen Funnel Architecture** (`ENGINEERING/FUNNEL_CONSTITUTION.md`, 2026-07-14 — permanent, every future audience funnel must extend it, never replace it with a parallel system); **the Audience Coupon Policy** (2026-07-14, Frozen — audience coupons are never public offers, only Email-3-delivered conversion tools; do not re-add coupon-code advertising to any public page); **the one-page-at-a-time audience-landing-page approval rule** (2026-07-15, permanent — never batch-declare multiple audience pages "complete" or "approved" again; see `DECISIONS.md`). Full list: `DECISIONS.md`.

## Read before any funnel/Mailchimp/marketing work
`docs/ENGINEERING/FUNNEL_CONSTITUTION.md` — the permanent, frozen audience-funnel architecture governing every current and future funnel. Read this before touching landing pages, popups, lead magnets, Mailchimp automations, blogs, SEO, or advertising.

## Required reading order
See repo `CLAUDE.md` — `START_HERE.md` → `AI_CONTEXT_INDEX.md` → `PROJECT_STATE.md` → `CURRENT_TASK.md` → `NEXT_TASK.md` → `DECISIONS.md` → `KNOWN_ISSUES.md` → relevant subsystem doc → relevant release record.

## Relevant subsystem documents for the current phase
`ANALYTICS/GTM_STATUS.md`, `ANALYTICS/CONSENT_STATUS.md`, `ANALYTICS/GA4_STATUS.md`, `CONTENT/LEGACY_BLOG_CONVERSION_AUDIT.md`, `MARKETING/GOOGLE_MERCHANT_STATUS.md`, `DOCUMENTATION_GOVERNANCE.md`, `AI_CONTEXT_INDEX.md`.

## Private strategic context
Company strategy, revenue targets, and CSO briefing material live outside this public repository — see `CSO_PRIVATE_REFERENCE.md`.
