# Landing Page Launch Manifest (2026-07-16)

Execution checklist for the 5 audience landing pages, prepared per Andrew's
"Current-Turn Direction — Continue the Launch Build Without Deployment
Access" instruction. This is a deployment manifest, not a strategy document —
see `docs/ENGINEERING/AUDIENCE_LANDING_STATUS.md` and
`docs/ENGINEERING/FUNNEL_CONSTITUTION.md` for the durable architecture this
work builds on.

**Status labels used throughout:** Implemented Locally / Verified in
Repository / Verified on Existing Staging / Awaiting Staging Deployment /
Awaiting Manual WordPress Configuration / Awaiting Asset / Blocked by
Business Decision / Launch Blocker.

## 1. Funnel integration matrix (traced from repository code only)

| Audience | Landing template | Form -> handler | `lead_magnet` key | Tag branch (in `bhp_mailchimp_signup_tags`) | Journey (private ref) | Analytics view event | Code verified | Live verification |
|---|---|---|---|---|---|---|---|---|
| Parent | `page-reluctant-reader-adventure-kit.php` | `signup-form.php` -> `bhp_handle_mailchimp_signup` (`inc/mailchimp.php`) | `reluctant_reader_adventure_kit` | `Reluctant Reader Adventure Kit`, `Audience: Parent/Grandparent`, `Source: ...` | `[PARENT_AUTOMATION_ID]` | `parent_landing_view` (n/a - no dedicated dataLayer push found on this page; relies on `lead_form_view`) | Verified in Repository | Verified on Existing Staging/Production (form renders live, confirmed via browser) |
| Educator | `page-audience-educators.php` | same | `teacher_adventure_toolkit` | `Adventure Learning Toolkit`, `Audience: Educator`, `Source: Educator Landing Page` | `[EDUCATOR_AUTOMATION_ID]` | `educator_landing_view` (dataLayer push, consent-gated via `BHP_Analytics_Config`) | Verified in Repository | Verified on Existing Staging (per prior session's controlled signup test) |
| Gift Buyer | `page-audience-gift-buyers.php` | same | `meaningful_gift_guide` | `Meaningful Gift Guide`, `Audience: Gift Buyer`, `Source: Gift Buyer Landing Page` | `[GIFT_BUYER_AUTOMATION_ID]` | `gift_landing_view` | Verified in Repository | Awaiting Staging Deployment (page not yet live-tested this session) |
| Retailer | `page-audience-retailers.php` | same | `bookstore_wholesale_guide` | `Wholesale Guide`, `Audience: Retailer`, `Source: Retailer Landing Page` | `[RETAILER_AUTOMATION_ID]` | `retailer_landing_view` | Verified in Repository | Awaiting Staging Deployment (this session's edits not yet deployed) |
| Organization | `page-audience-organizations.php` | same | `community_reading_kit` | `Community Reading Kit`, `Audience: Organization`, `Source: Organization Landing Page` | `[ORGANIZATION_AUTOMATION_ID]` | `organization_landing_view` | Verified in Repository | Awaiting Staging Deployment |

**Cross-audience routing check (code-level):** every `bhp_mailchimp_signup_tags`
branch matches on an exact `$lead_magnet` key with no fallthrough between
audiences, and the generic `['Adventure Club']` default only applies to
non-audience-specific forms (footer/general signup) — confirmed no audience
can trigger another audience's tag branch. Purchaser-suppression tag
(`Customer - Purchased`) is applied by a separate, unrelated automation
(`Global - Tag Purchasers`, private ref `[GLOBAL_TAG_PURCHASERS_AUTOMATION_ID]`)
that this session did not touch — the validated suppression architecture is
unchanged.

**Success/error states:** all 5 forms share `bhp_mailchimp_signup_redirect()`
— same whitelisted-redirect pattern, same sanitized error states (`invalid`,
`missing_name`, `unavailable`, `error`), same preserved-value redisplay on
error. No audience has a forked or divergent handler.

**Coupon-stage dependency:** only Parent/Gift Buyer/Educator Email 3s carry a
coupon (private ref `[PARENT_COUPON_CODE]` / `[GIFT_BUYER_COUPON_CODE]` /
`[EDUCATOR_COUPON_CODE]`), gated to the non-purchaser branch in each journey.
Retailer/Organization Email 3s are inquiry-led with no coupon. Real coupon
values are mid-rotation — see the Private Launch Task below.

## 2. SEO metadata implementation

**Confirmed defect (live-verified on production for Parent):** no meta
description, no `og:description`. Canonical URL, robots tags, single H1, and
`og:title` were all already correct.

**Fix implemented in code** (`functions.php`, new
`bhp_audience_landing_seo_description_filter()`): unique, keyword-relevant
description per audience (using "Adventure Books" / "Educational Adventure
Books for Kids Ages 6-9" per the approved terminology), applied via
`rank_math/frontend/description`, `rank_math/opengraph/facebook/description`,
`rank_math/opengraph/twitter/description` — **only fires when Rank Math's own
field is empty**, so a manual wp-admin entry always wins with no code change
needed. Status: **Implemented Locally / Verified in Repository.** Takes
effect the moment this code is deployed — **Awaiting Staging Deployment**,
then **Awaiting Manual WordPress Configuration** only if Andrew later wants a
hand-tuned description instead of the code fallback (optional, not required).

**Titles/canonical/robots/H1:** already correct on the one page checked live
(Parent/production). Not independently re-verified for the other 4 pages
since only Parent is live/indexable right now (the other 4 are staging-only,
correctly `noindex`) — re-check once each page goes live. **Awaiting Staging
Deployment** for that re-check.

**Alt text:** all raw `<img>` tags across all 5 templates already carry real,
descriptive alt text (verified by direct code inspection, not assumed).

**Internal linking:** each landing page already links to `/books/`,
`/complete-collection/`, and `/contact/` as appropriate — all live-verified
as real, correct destinations on production (not 404s). No additional
internal-link work identified as a launch blocker for these 5 pages
specifically; broader sitewide internal-linking from blog posts/book pages
into these landing pages was out of scope for this pass (see
`NEXT_TASK.md`'s existing Phase 9/10 items for that broader work).

## 3. Retailer pricing correction

`page-audience-retailers.php`'s pricing block now explicitly labels $11.99/
$17.99 as **current consumer list prices**, not wholesale/trade pricing —
verified live against `/shop/` on production. No margin, minimum-order, or
trade-term numbers are published anywhere on the page; all wholesale-term
questions route to the inquiry CTA. **Implemented Locally / Verified in
Repository.**

## 4. Gift Buyer / Organization pages

Both audited in full against Andrew's checklist (paperback/hardcover
distinction, age range, occasion/use-case relevance, correct testimonial,
Collection CTA, correct lead-magnet gating, correct tag routing). **No code
changes were needed — both already comply.** Status: **Verified in
Repository.**

## 5. Missing-asset integration register

Both registers below describe a mechanism that **already exists in code and
requires no new code** — the same self-activating pattern already proven
working for the Educator toolkit (Settings → Lead Magnets → paste/select
PDF → `$download['ready']` flips to `true` → page and Email 1 both go live
automatically). Nothing here is launch-ready until the real asset exists.

### 5a. Community Reading Kit (Organization audience)

| Field | Value |
|---|---|
| Approved title | "Community Reading Kit" (already in code, `community_reading_kit` lead-magnet key) |
| Proposed production filename | `brave-hearts-community-reading-kit.pdf` |
| Production URL placeholder | `[COMMUNITY_READING_KIT_PRODUCTION_URL]` |
| Organization landing-page CTA | "Get the Community Reading Kit" / "Send Me the Community Reading Kit" (already in code) |
| Organization Email 1 CTA | Not yet built — Andrew's Mailchimp Email 1 for Organizations still needs the real download link once the PDF exists |
| Confirmation-message copy | Generic sitewide success message ("You're in! Welcome to the Adventure Club.") — no dedicated thank-you page exists for this audience yet; adding one is optional, not a blocker |
| Download event | No new event needed — `organization_landing_view` (dataLayer, consent-gated) and the generic `lead_form_view`/`lead_form_start` pair from `signup-form.php` already cover it |
| Required final verification | PDF uploaded under a real production URL, Settings field set, one real end-to-end signup tested (same verification Educator got 2026-07-16) |

**Status: Awaiting Asset — Launch Blocker.**

### 5b. Gift Buyer lead magnet

Approved concept confirmed from canonical docs (`docs/ENGINEERING/AUDIENCE_IMPLEMENTATION_MATRIX.md`) — **not invented this pass**, already the live code's concept:

| Field | Value |
|---|---|
| Approved title | "The Meaningful Gift Guide" (already in code, `meaningful_gift_guide` / `gift_guide` settings key) |
| Proposed production filename | `brave-hearts-meaningful-gift-guide.pdf` |
| Production URL placeholder | `[GIFT_GUIDE_PRODUCTION_URL]` |
| Landing-page CTA | "Get the Meaningful Gift Guide" / "Send Me the Meaningful Gift Guide" (already in code) |
| Email 1 CTA | Not yet built — Andrew's Mailchimp Email 1 for Gift Buyers still needs the real download link once the PDF exists |
| Confirmation copy | Generic sitewide success message, same as Organization above |
| Download event | No new event needed — `gift_landing_view` + generic `lead_form_view`/`lead_form_start` already cover it |
| Required final verification | PDF uploaded under a real production URL, Settings field (`gift_guide`) set, one real end-to-end signup tested |

**Status: Awaiting Asset — Launch Blocker.**

### 5c. Retailer wholesale packet

**Status: Blocked by Business Decision.** No wholesale prices, margins,
minimums, return policy, or Ingram availability are recorded, invented, or
implied anywhere in this register or in the live page — the Retailer page's
Ordering section and FAQ correctly route every commercial-term question to
a direct inquiry instead. This item does not become a code task until
Andrew has approved actual retailer terms; at that point it would follow
the same Settings → Lead Magnets pattern as the other two (key already
reserved: `bookstore_wholesale_guide`).

## 6. Static accessibility/responsive review

Reviewed all 5 templates plus the shared `signup-form.php` /
`lead-magnet-cta.php` template parts: proper `label[for]`/`id` pairing,
`wp_unique_id()` / explicit unique panel IDs (no collisions found across any
of the 5 pages), all images have real alt text, FAQ uses native
`<details>/<summary>` (accessible without extra ARIA), consistent single-H1
-> H2 -> H3 heading order, `aria-live` status regions present on form
feedback, no disabled focus outlines found in scoped CSS (not exhaustively
re-checked this pass, but no override was found in the audience-landing.css
selectors inspected). **No code-level defects found.** Live 9-breakpoint
visual QA (actual rendered overflow/stacking/contrast) remains **Awaiting
Staging Deployment** — cannot be verified without seeing the rendered page.

## 7. Product-link / checkout code review

All 5 pages resolve every product/Collection/contact link via `home_url()`/
`get_permalink()` — zero hardcoded staging domains or legacy URLs found.
Live-verified on production: `/books/`, `/complete-collection/`, `/contact/`
all resolve correctly (not 404s). Complete Collection pricing card pulls live
pricing from `bhp_bundle_expected_price()`/`bhp_bundle_rules()` — the same
functions the real checkout uses — not hardcoded numbers. **Verified in
Repository + partial live verification (link destinations only, not full
cart/checkout flow).** Full cart/checkout regression (6 core products + 2
Collection SKUs, coupon application, payment) remains **Awaiting Staging
Deployment**.

## 8. Private launch task (real values never enter this file)

Coupon rotation is recorded in `docs-private/MAILCHIMP_INTERNAL_REFERENCE.md`
— a **private, local, untracked, gitignored** file (confirmed via
`git ls-files` returning nothing for it and `git status --ignored` showing
it as ignored). It is never committed and never pushed. (Real codes were
exposed in
commits before this session's sanitization pass and are being treated as
compromised — Andrew's explicit decision, 2026-07-16). Public docs and code
use only the placeholders `[PARENT_COUPON_CODE]` / `[GIFT_BUYER_COUPON_CODE]`
/ `[EDUCATOR_COUPON_CODE]`. **Status: Blocked by Business Decision /ready to
execute once Andrew has WooCommerce+Mailchimp access to create the
replacements** — not a code blocker.

## 9. Production deployment package (2026-07-17, updated — supersedes both the
`1a5c9ea` package and the staging-only package below)

**Source commit:** `23aab6f84b81155bcbbf4c7fb2f6580e05cd5711` (short
`23aab6f`) on branch `feature/production-integration-1.17.1`, pushed to
origin. Re-verify with `git log -1` before rebuilding in case later commits
land on this branch first.

**ZIP filename:** `brave-hearts-theme-deploy-explorer-expedition-guides-23aab6f.zip`
(built via `git archive`, 3,576,821 bytes, top-level prefix
`brave-hearts-theme-deploy-explorer-expedition-guides/` — matches the
active theme slug exactly, required for `wp theme install --force` to
overwrite the live theme instead of installing a new inactive one).
Contains `style.css`, `theme.json`, `assets/`, `inc/`, `template-parts/`,
and all top-level `*.php` files as of commit `23aab6f`. Verified via
`unzip -l` to include every file changed this batch, including
`template-parts/quiz/audience-quiz.php`, `assets/css/audience-quiz.css`,
`assets/js/audience-quiz.js`, `front-page.php`,
`page-reluctant-reader-adventure-kit.php`, and `inc/lead-magnet-settings.php`.

**Superseded package:** `brave-hearts-theme-deploy-explorer-expedition-guides-1a5c9ea.zip`
(commit `1a5c9ea428b11a4bf02a447e92e87b4ba814073b`) is superseded, not
deleted — it remains a valid, older rollback target if `23aab6f` ever needs
to be backed out independently of the homepage quiz-entry change. Do not
deploy `1a5c9ea` going forward; deploy `23aab6f`.

**Destination-relative theme path:** `wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/`
(relative to whichever document root — staging or production — Andrew
installs it into).

**Files changed since the last deployed commit (`a327410`):**

| File (repo-relative) | Reason | Admin/DB action required after deploy |
|---|---|---|
| `page-reluctant-reader-adventure-kit.php` | Removed 6 "coming soon" media placeholders; replaced interior-spread/media-grid with a text+feature-grid section; replaced video block with a founder-photo card | None |
| `functions.php` | Registered `[bhp_audience_quiz]` shortcode + its asset enqueue (homepage + shortcode pages only) | None |
| `front-page.php` | Renders the quiz as a homepage quiz-entry section (`intro_gate` arg) between the newsletter section and the footer — see below | None |
| `template-parts/quiz/audience-quiz.php` (new) | Find Your Adventure audience-routing quiz markup/config, plus the optional `intro_gate` lead-in-card state added this batch | None |
| `assets/js/audience-quiz.js` (new) | Quiz state machine + analytics events, plus the `homepage_quiz_started` click handler added this batch | None |
| `assets/css/audience-quiz.css` (new) | Quiz styling, scoped to `.bhp-quiz`, plus 2 small rules for the intro card added this batch | None |
| `docs/ENGINEERING/LAUNCH_URL_REGISTER.md` (new) | URL register, doc-only | None |
| `inc/lead-magnet-settings.php` | **Already present in this commit, unchanged tonight** — defines all 7 Lead Magnet URL fields (`mariana_teacher`, `mariana_parent`, `adventure_kit_parent`, `teacher_toolkit`, `gift_guide`, `bookstore_wholesale_guide`, `community_reading_kit`). Production is currently running an older deploy with only the first 3 fields live. | **Yes — after deploy, fill in `teacher_toolkit`, `gift_guide`, and `community_reading_kit` at Settings → Lead Magnets with the 3 verified PDF URLs in `LAUNCH_URL_REGISTER.md` §PDFs.** |

**Homepage quiz-entry section (added this batch, commit `23aab6f`):** the
homepage's quiz placement (between the newsletter section and the footer,
DOM order unchanged from the prior package) now shows a lead-in card first
— eyebrow "Find Your Adventure," heading "Not Sure Where to Start?," the
approved supporting copy, a "Take the Quick Quiz" button, and "It only
takes about a minute." — instead of the quiz's own default header.
Clicking the button reveals Q1 in place (no navigation, no new page/URL)
and fires `homepage_quiz_started` (`source: homepage`, `destination:
audience_quiz`, `cta_text`) once, gated by the same consent check as the
quiz's other 7 events, which are unchanged. No new dependency, no new
button/color styles (reuses `.btn.btn-primary` and the quiz's existing
CSS variables). Live rendered-viewport verification (320/375/768/1024/
desktop) is not yet possible — this batch has not been deployed to
staging or production — and remains part of the post-deployment
verification checklist below.

Everything from the prior staging-only package (§ below: Retailer FAQ/
pricing, SEO description filter, Mailchimp docs, QA sheets) is also
included since it's already merged into this branch history — no separate
deploy needed for it.

**Database changes:** none in the ZIP itself. The one admin action needed
post-deploy is filling 3 text fields on an existing Settings page (no
schema change, no plugin activation).

**Logo:** explicitly excluded from this package per Andrew's 2026-07-17
decision — `brave-hearts-stacked.png` is not applied anywhere; header,
footer, favicon, and Site Identity remain untouched. See
`docs/ENGINEERING/LAUNCH_URL_REGISTER.md` §Logo.

**Deployment instructions** (I cannot upload files myself — the
`file_upload` tool is restricted to files shared directly with this
session, confirmed via direct test — so Andrew or someone with SiteGround/
WP-admin access must perform the actual install):
1. Get the ZIP from this machine's scratchpad path onto the target server.
   Either works:
   - **WP-admin:** Appearance → Themes → Add New → Upload Theme → select
     the ZIP → **Replace current with uploaded** (WordPress will prompt
     this since the slug matches the active theme).
   - **SiteGround Site Tools File Manager:** upload the ZIP into
     `wp-content/themes/`, then extract in place — this achieves the same
     result as `wp theme install --force` without needing SSH.
2. Confirm `wp theme list --status=active` (via WP-CLI if SSH is used) or
   the Appearance → Themes screen shows the active theme's modified
   timestamp changed — a mismatched top-level folder name would silently
   install a new, inactive theme instead of replacing the live one, so
   don't skip this check.
3. Load any page and confirm no fatal-error white screen (equivalent to
   `wp eval 'echo "ok";'` if WP-CLI is available).

**Cache-clearing steps:** Purge SiteGround cache — either the "Purge SG
Cache" link in the WP-admin toolbar, or `wp sg purge` via WP-CLI/SSH.
Required because this deploy changes customer-facing markup (Parent page,
homepage quiz).

**Rollback steps:** every file in this package is a git-tracked template/
asset/functions.php change with no DB dependency. Rollback is either (a)
re-upload the previously-installed theme ZIP (keep a copy of the current
live theme before deploying — SiteGround also keeps its own backups per
`docs/RUNBOOK.md`), or (b) `git revert 23aab6f` (and `1a5c9ea` if rolling
back the whole batch, not just the homepage quiz-entry change), rebuild
the ZIP from the reverted commit, and redeploy. If the 3 new Lead Magnet
fields were filled in and need to be undone, clear those 3 fields on the
Settings page — no other data changes anything else.

**Production verification checklist (execute after deploy, before
declaring this batch live):**
- [ ] No PHP fatals on load (any page)
- [ ] Parent page (`/reluctant-reader-adventure-kit/`) shows the new
      feature-grid "See inside" section and founder-photo card, no
      placeholder boxes remain
- [ ] Homepage shows the "Not Sure Where to Start?" quiz-entry card
      between the newsletter section and the footer (in that exact
      order); clicking "Take the Quick Quiz" reveals Q1 in place; Q1→Q2→
      result flow works for all 4 routes (Parent/Educator/Organization/
      Gift); each result CTA links to the correct landing page with
      `utm_source=quiz` appended
- [ ] `homepage_quiz_started` fires once (GTM Preview/dataLayer) on the
      "Take the Quick Quiz" click, with `source: homepage`,
      `destination: audience_quiz`, `cta_text` populated; the quiz's own
      7 events (`quiz_viewed` → `quiz_abandoned`) still fire unchanged
- [ ] No horizontal overflow or footer collision at 320/375/768/1024/
      desktop widths on the new quiz-entry card
- [ ] Browser console clean on homepage and Parent page
- [ ] Settings → Lead Magnets shows all 7 fields (confirms the deploy
      landed); fill in `teacher_toolkit`, `gift_guide`,
      `community_reading_kit` with the 3 verified PDF URLs and save
- [ ] Educator, Community Organization, and Gift Buyer pages' download
      CTAs now resolve to real PDFs (no more "Coming Soon")
- [ ] Retailer page unaffected/unpublished-priority (spot check only —
      not part of this launch)
- [ ] Cart/checkout regression: no shipping/coupon change expected from
      this package, spot-check one add-to-cart to confirm no regression
- [ ] Run `docs/ENGINEERING/LANDING_PAGE_9_BREAKPOINT_QA_SHEET.md` and
      `docs/ENGINEERING/LANDING_PAGE_FUNNEL_TEST_CASES.md` against the
      now-fully-connected 4 pages

**Do not hand-edit live PHP files** — this ZIP is the only sanctioned path
for these changes, per `.claude/rules/production-safety.md`.

## 9b. Staging deployment executed and QA'd (2026-07-17)

Deployed via SSH + `scp` + `wp theme install --force` — the canonical
route documented in `docs/RUNBOOK.md` (a prior session incorrectly
reported this as blocked based on the unrelated browser `file_upload`
tool; corrected there, see that file's top note).

**Two builds were deployed to staging this pass:**
1. `...-23aab6f.zip` (commit `23aab6f`) — installed, but `wp theme list`
   still reported version `1.19.39` afterward. Root cause: `style.css`'s
   `Version:` header was never bumped across any of tonight's commits,
   despite real JS/CSS/PHP behavior changes — a real defect, not a failed
   install (files did land; `unzip -l` and file-content checks confirmed
   it).
2. **Fix applied:** bumped `style.css` to `1.19.40` (commit `236bdb6`,
   also correcting the RUNBOOK finding above), rebuilt as
   `...-236bdb6.zip`, redeployed. `wp theme list` now correctly reports
   `1.19.40`. **This is the ZIP that should be used for any future
   production deploy, not `...-23aab6f.zip` or `...-1a5c9ea.zip`.**

**Live-verified on staging (`staging2.braveheartspublishing.com`):**
- Quiz-entry card renders in the correct position (between newsletter and
  footer); exact required copy confirmed.
- "Take the Quick Quiz" reveals Q1 in place; keyboard focus moves to the
  first Q1 option (confirmed via `document.activeElement`).
- All 4 routes' Q1→Q2→result flow work (Parent, Educator, Organization,
  Gift Buyer all individually clicked through); Educator's full CTA
  click-through landed on the real page with `?utm_source=quiz&utm_medium=
  onsite&utm_campaign=audience_quiz` correctly appended, no console error.
- "Start over" correctly resets to Q1.
- All 4 audience landing pages load with real content, no fatals.
- All 7 Lead Magnet Settings fields render (confirmed both via WP-admin
  screenshot and `BHP_LEAD_MAGNET_PDF_KEYS` on the server); 4 of 7 already
  carry real saved staging PDF URLs via this same save mechanism, proving
  it works. Gift Guide and Community Reading Kit fields are correctly
  still empty (those 2 PDFs don't yet exist in staging's own uploads —
  the production-uploaded PDFs weren't synced to staging's media library,
  a separate/expected environment gap, not part of this deploy's scope).
- No console errors observed on homepage or the 4 landing pages.

**Not live-fired, verified by code inspection instead:** `homepage_quiz_
started` and the quiz's other 7 events. Staging has its own dedicated
`bhp_staging_analytics_override` gate (pre-existing, unrelated to this
batch) currently unset, so `analyticsOn` is `false` and all events no-op
by design — toggling that server option is a separate write outside this
turn's authorized scope. The JS calls the identical `pushEvent`/`dataLayer.
push` mechanism already used by the other 7 shipped events, so this is a
config-gate observation, not a code defect.

**Not live-verified, code-level only:** true narrow-viewport rendering
(320/375/768/1024px). The browser automation's `resize_window` call
reports success but `window.innerWidth` stays at 1280 regardless — a
known, pre-existing machine-local tool limitation, not a defect in this
change. Reasoned from CSS instead: `.bhp-quiz__intro`/`.bhp-quiz__start`
inherit the existing `.bhp-quiz__inner` (max-width 640px) and the quiz's
already-shipped `@media (min-width: 600px)` breakpoint; no new fixed-
pixel widths were introduced.

**Replacement ZIP required:** yes — `...-236bdb6.zip` supersedes both
`...-23aab6f.zip` and `...-1a5c9ea.zip`. Neither earlier ZIP should be
deployed to production.

**Production-deployment recommendation:** Ready to propose for
production once Andrew reviews this report — no defects found in the new
functionality itself; the one real defect found (missing version bump)
was caught and fixed before it reached production. Recommend also
deciding, before a production deploy, whether to sync/upload the Gift
Guide and Community Reading Kit PDFs directly to production (as already
planned) rather than to staging, since production is the actual target
environment for Andrew's uploaded files.

---

## 9c. PRODUCTION deployed (2026-07-17) — with one launch-blocking finding

Deployed to production with Andrew's explicit approval, via SSH + `scp` +
`wp theme install --force` (docs/RUNBOOK.md canonical route).

**Commands:** production theme dir backed up first
(`/home/customer/backups-theme-1.19.20-20260717-141315.tar.gz`, verified
readable); `...-236bdb6.zip` scp'd to `/tmp` (byte-identical, confirmed);
`wp theme install --force`; `wp theme list`; `wp eval`; `wp sg purge`.

**Version:** `1.19.20` → `1.19.40`. Active slug unchanged
(`brave-hearts-theme-deploy-explorer-expedition-guides`). No PHP fatal.

**Lead Magnet URLs — all 4 populated and confirmed persisted** (verified
both in the WP-admin field values after a full page reload, and at the
`wp option get bhp_lead_magnet_pdfs` database level):
- Parent: `.../Reluctant-Reader-Adventure-Kit-1.pdf` (was already set from
  an earlier session)
- Educator: `.../Educator-PDF.pdf`
- Community Organization: `.../Community-Resource-Page.pdf`
- Gift Buyer: `.../Ultimate-Gift.pdf`
- Wholesale (Retailer): left blank, correctly — deferred.

**Quiz — fully verified live on production:** exact placement (directly
after the Adventure Club newsletter section, before the footer), exact
copy, "Take the Quick Quiz" reveal, keyboard focus to first Q1 option,
Parent and Gift Buyer routes clicked through Q1→Q2→result→destination
click with the correct `?utm_source=quiz&utm_medium=onsite&utm_campaign=
audience_quiz`. No site logo image exists in the header at all (it's a
text+icon mark) — nothing for this deploy to have touched, confirmed.

**LAUNCH-BLOCKING FINDING (not a deployment regression):** the Educator,
Gift Buyer, and Community Organization landing page URLs 404 on
production. Root cause confirmed via `wp post list --post_type=page
--name=<slug>` on production: **no WordPress Page object exists at all**
for `educators-adventure-learning-toolkit`, `gift-buyers-guide`, or
`organizations-community-reading-kit` — only `reluctant-reader-adventure-
kit` (post ID 348) exists. These 3 pages exist only on staging (post IDs
585, 587, 589, each `publish` status with the correct `_wp_page_template`
already assigned: `page-audience-educators.php`,
`page-audience-gift-buyers.php`, `page-audience-organizations.php`) and
were apparently never created on production. The theme's PHP templates
for all 3 are correctly present and deployed — WordPress cannot route a
URL to a template without a matching Page object in the database, no
matter what the theme ships. **This was not caused by tonight's
deployment** — these pages 404'd before this deploy too, since they never
existed on production; a theme rollback would not fix it. The
`LAUNCH_URL_REGISTER.md` assumption ("expected to resolve on production
once deployed") was incorrect and is corrected here.

**Practical effect:** only 1 of the 4 audience funnels (Parent) is
actually reachable end-to-end on production right now. The quiz, PDF
settings, and homepage integration are all launch-ready; the missing
piece is 3 WordPress Page objects, which is a content-creation action
(new production Pages), not a theme/code action — flagged for Andrew's
decision rather than created unilaterally.

**Analytics:** code-verified only, matching the staging pass — all 8
event names (`homepage_quiz_started` + the quiz's 7) and the consent gate
are confirmed present in the deployed production
`assets/js/audience-quiz.js`. Live firing was **not** observable under
any consent state because `bhp_gtm_container_id` is empty on production
— GTM isn't configured yet at all, a pre-existing, deliberate business
gate unrelated to this deploy (see `inc/class-bhp-analytics-config.php`'s
`consent_decision_approved()` gate). Not toggled or worked around.

**Rollback status:** not needed. No regression occurred; production
backup (`backups-theme-1.19.20-20260717-141315.tar.gz`) remains available
if ever required.

---

## 9d. Missing production Pages created (2026-07-17) — all 4 funnels now live

With Andrew's explicit authorization, created the 3 missing WordPress
Page objects on production, mirroring staging exactly (title, slug,
template, publish status; empty `post_content` since these are fully
template-driven, same as staging). New production IDs assigned by
WordPress (not copied from staging):

| Audience | Production Page ID | Title | Slug | Template |
|---|---|---|---|---|
| Educator | 393 | Teachers, Librarians & Homeschool | `educators-adventure-learning-toolkit` | `page-audience-educators.php` |
| Gift Buyer | 394 | Gift Buyers | `gift-buyers-guide` | `page-audience-gift-buyers.php` |
| Community Organization | 395 | Organizations | `organizations-community-reading-kit` | `page-audience-organizations.php` |

**Note on the requested verification URLs:** the approval message listed
`/educator-expedition-guides/` and `/community-reading-kit/` as the URLs
to check — these don't match the real staging slugs or the quiz's
hardcoded routes (`educators-adventure-learning-toolkit`,
`organizations-community-reading-kit`). Created pages at the real,
staging-mirrored slugs instead, since matching the listed URLs would
require also changing the quiz's routing code (a new theme deploy) and
would leave the quiz broken in the meantime. Flagging this for Andrew's
awareness rather than silently picking one interpretation.

**Verified live (real browser, not `curl` — SiteGround's edge issues an
`SG-Captcha: challenge` / HTTP 202 to non-browser clients, a known,
pre-existing behavior, not a defect):** all 3 pages load full real
content, no placeholder/"Coming Soon" state, no new console errors (the
same 8 pre-existing admin-bar/media-library errors as before, confirmed
via anonymous `curl` earlier to be invisible to real shoppers). Each
page's signup form carries the correct `lead_magnet` / `audience_type`
hidden fields (`teacher_toolkit`/educators, `meaningful_gift_guide`/
gift_buyers, `community_reading_kit`/organizations — the `lead_magnet`
field values are the Mailchimp-tagging key, separate from the
`bhp_lead_magnet_pdfs` settings-array keys used for PDF resolution;
both already verified correct). Cache purged, rewrite rules flushed.

**All 4 quiz routes verified end-to-end** (Parent, Educator, Gift Buyer,
Organization): each Q1→Q2→result flow completes and each result CTA
resolves to the correct real production landing page.

**Confirmed unaffected:** Parent page, main nav, `/books/` shop page,
side-cart drawer (Andrew's own real 2-item cart still intact) — no
commerce regression from either the theme deploy or the new pages.

---

## 9e. Sitewide "Find Your Adventure" quiz routing (2026-07-17) — STAGING ONLY

Andrew's instruction: stop all Mailchimp email-content work (he corrects
the `*|FNAME|*` merge tags himself); Claude focuses on making the
existing homepage quiz discoverable sitewide so any visitor can reach the
correct audience landing page, lead magnet, and Mailchimp journey.

**Architecture chosen:** a dedicated canonical quiz page (`/find-your-adventure/`)
plus a small sitewide CTA banner — the first option listed in Andrew's
assignment. Rejected alternatives: embedding the full quiz repeatedly
inside every blog/product page (explicitly forbidden — duplicate
rendering, heavier pages) and a timed/forced popup (explicitly forbidden
— too intrusive). Both the canonical page and the CTA reuse the existing
`template-parts/quiz/audience-quiz.php` component; no second quiz
implementation was created.

**Files changed:**
- `template-parts/quiz/audience-quiz.php` — added `entry_location` arg (default `quiz`), forwarded into `utmParams.utm_content` and a new `entryLocation` config key; root `id` changed from hardcoded `find-your-adventure` to a per-render-unique ID via `wp_unique_id()`.
- `front-page.php` — passes `entry_location => 'homepage'`.
- `assets/js/audience-quiz.js` — added `entry_location` to every quiz event via a `pushQuizEvent()` wrapper; result CTA now also carries `data-bhp-entry-location`.
- `assets/js/nav.js` — the existing generic `data-bhp-event`/`data-bhp-impression-event` dispatcher now also reads `data-bhp-quiz-audience` and `data-bhp-entry-location` off the target element into the event payload (no new script needed for CTA analytics).
- `functions.php` — new `bhp_should_show_quiz_cta()` (reuses the existing `bhp_should_show_any_popup()` exclusion set, plus excludes the homepage and the quiz page itself) and `bhp_enqueue_quiz_cta_assets()`.
- `footer.php` — renders the CTA (`template-parts/components/quiz-entry-cta.php`) right after `</main>`, gated by `bhp_should_show_quiz_cta()`.
- `template-parts/components/quiz-entry-cta.php` (new) — the sitewide CTA banner markup.
- `assets/css/quiz-entry-cta.css` (new) — CTA styling, reuses existing `.btn.btn-secondary`.
- `page-find-your-adventure.php` (new) — canonical quiz page template (`Template Name: Find Your Adventure Quiz`), no `intro_gate` (Q1 visible immediately).
- `style.css` — version bump 1.19.40 → 1.19.41.

**Staging deployment:** built `build-1.19.41.zip` directly from the
working tree (uncommitted at deploy time — not yet committed to git this
pass), `--prefix=brave-hearts-theme-deploy-explorer-expedition-guides/`,
deployed via `wp theme install --force` to
`staging2.braveheartspublishing.com`. Confirmed active version 1.19.41,
`wp eval 'echo "ok";'` succeeded (no PHP fatal), `wp sg purge` succeeded.
Created the Page via WP-CLI: ID **597**, title "Find Your Adventure",
slug `find-your-adventure`, template `page-find-your-adventure.php`,
status publish. Slug confirmed free on both staging and production
before creation (`wp post list --name=find-your-adventure` returned
empty on both).

**Staging QA (live-verified in a real browser):**
- `/find-your-adventure/` loads: intro copy + quiz's own default header + Q1, one `[data-bhp-quiz]` instance, unique root ID `find-your-adventure-1`, no CTA banner on the page itself (correctly excluded), no console errors.
- Homepage: intro-gate quiz unchanged (CTA correctly absent — homepage already embeds the quiz), full Q1→Q2→result flow tested live for the Parent route: destination `https://staging2.braveheartspublishing.com/reluctant-reader-adventure-kit/?utm_source=quiz&utm_medium=onsite&utm_campaign=audience_quiz&utm_content=homepage`, correct `data-bhp-quiz-audience=parents_families`, `data-bhp-entry-location=homepage`, `data-bhp-event=quiz_destination_click`.
- CTA banner present with exact required copy and `utm_content=footer` on: blog archive, individual product page, shop archive.
- CTA banner correctly absent on: cart (empty and with a real item), checkout (with a real item in cart — WooCommerce Blocks), all 4 audience landing pages (`/reluctant-reader-adventure-kit/`, `/educators-adventure-learning-toolkit/`, `/gift-buyers-guide/`, `/organizations-community-reading-kit/`), the Parent thank-you page, privacy policy.
- Mobile (375px, confirmed via `window.innerWidth`, not just a resize call): CTA renders full-width, no horizontal overflow (`scrollWidth === clientWidth`).
- Test cart item (Everest hardcover, WC 17) added for the checkout-exclusion check, then removed — cart left empty, confirmed via page text ("Your cart is currently empty!").
- No console errors observed across any of the above pages.

**Not re-tested this pass (already verified in the prior MC-C series,
unchanged by this batch):** the 4 landing pages' own signup forms, PDF
association, Mailchimp trigger tags, and journey entry — this batch only
changed *how visitors reach* those pages, not the pages/forms themselves.

**Not done:** production deployment (awaiting Andrew's explicit
approval), git commit (not requested this pass), any Mailchimp
email-content change (explicitly out of scope).

**Rollback:** re-install the prior 1.19.40 ZIP
(`brave-hearts-theme-deploy-explorer-expedition-guides-236bdb6.zip`, see
§9c) via the same `wp theme install --force` command; delete staging
Page ID 597 via `wp post delete 597 --force` if the canonical page should
be removed too.

---

## 9f. Parent landing-page visual corrections + nav centering (2026-07-17) — STAGING ONLY

Three targeted visual fixes on `/reluctant-reader-adventure-kit/` and the
sitewide desktop nav, requested alongside the quiz-completion assignment
in §9e. All copy, commerce logic, Mailchimp wiring, and Retailer scope
left untouched — visual-only.

**1. Duplicate founder photo.** `assets/images/handoff/founder-and-charlotte.webp`
was rendering in two consecutive sections: "Written for one real kid
first." (the longer founder narrative) and "Hi, I'm Andrew." (the shorter
video-block replacement added 2026-07-16). Removed the `<img>` and its
wrapping `.parent-landing-media` div from the "Hi, I'm Andrew." section
only; the photo remains in "Written for one real kid first." above it.
No stock, generated, cropped, or mirrored replacement image was
introduced. Restructured "Hi, I'm Andrew." from the 2-column
`.parent-landing-author` grid (built for photo + text) to the existing
centered `.parent-landing__header-block` pattern already used by the
PROBLEM and HOW-IT-WORKS sections on the same page — this avoids leaving
an empty second grid column where the photo used to sit. Added the
existing `.parent-landing__lead` class to the section's two paragraphs
(the only structural change beyond removing the image) so they keep
consistent typography that the old grid's `.parent-landing-author p` rule
used to provide. Copy unchanged.

**2. Redundant five-star trust treatment.** The TRUST section showed five
stars twice: once in the "verified Amazon reviews" trust-stat card, once
above Payton's testimonial (where the stars legitimately belong to that
specific verified review). No live-verifiable Amazon review count exists
that would stay accurate over time without automated upkeep (the site's
own review registry is a curated subset for testimonial display, not a
live aggregate count), so per the assignment's own fallback, replaced the
stat card's star icons with plain verified-but-unquantified text:
"Verified" / "Amazon reviews" — no number was guessed or fabricated.
Payton's testimonial, its stars, attribution, and the real Amazon review
link are byte-for-byte unchanged.

**3. "Adventure Books" nav centering.** The desktop nav's two-line label
(`.site-nav .menu-item--adventure-books > a`, added 2026-07-16 per
`DECISIONS.md`) stacked "Adventure" over "Books" via
`flex-direction: column` but had no `align-items`/`text-align`, so each
line defaulted to left-aligned within a stretched flex item. Added
`align-items: center; text-align: center;` to that single existing
selector — no other nav rule touched. The aria-label
(`bhp_adventure_books_nav_aria_label()` in `functions.php`) and the
mobile single-line override (`@media` block further down in `style.css`)
were both already correct and are unaffected.

**Files changed:** `page-reluctant-reader-adventure-kit.php`,
`style.css`. Theme bumped 1.19.41 → 1.19.42.

**Staging deployment:** built `build-1.19.42.zip` from the working tree
(same forward-slash-safe Python method as §9e, prefix
`brave-hearts-theme-deploy-explorer-expedition-guides/`), deployed via
`wp theme install --force`. Confirmed active version 1.19.42, `wp eval
'echo "ok";'` succeeded (no PHP fatal), `wp sg purge` succeeded.

**Staging QA (live-verified in a real browser):**
- Founder image: exactly 1 match for `img[src*="founder-and-charlotte"]` on the page (was 2).
- Trust-stat cards: live DOM text confirms `"VerifiedAmazon reviews"` with no star glyphs; exactly one `.parent-landing-review__stars` element remains on the whole page.
- Payton's testimonial block (stars, quote, attribution, real Amazon review link `amazon.com/portal/customer-reviews/B0GQCCPZLL/...`) confirmed unchanged.
- "Hi, I'm Andrew." section: centered `.parent-landing__header-block`, 660px wide, horizontally centered within its section, no `<img>`, section height 354px (not oversized/empty-looking).
- Nav centering measured via `getBoundingClientRect()` at 1280px, 1440px, and 1920px real viewports (confirmed via `window.innerWidth`, not just the resize call): both label lines' horizontal centers match to within floating-point rounding (0px difference) at all three widths. No overlap with the About or Contact nav links at 1440px. Mobile (375px, confirmed real): nav toggle opens, label renders as its existing single-line override, unaffected by the fix, no horizontal overflow (`scrollWidth === clientWidth`).
- No console errors observed on the Parent landing page or homepage after this deploy.
- Quick regression check: `/find-your-adventure/` canonical quiz page (from §9e) still renders correctly post-redeploy — one quiz instance, no CTA banner on itself, confirming this deploy didn't disturb the prior quiz-routing work.

**Not done:** production deployment (staging only, awaiting Andrew's
explicit approval), git commit (not requested this pass).

**Rollback:** re-install the 1.19.41 ZIP (`build-1.19.41.zip`, see §9e) or
the 1.19.40 ZIP (`...-236bdb6.zip`, see §9c) via the same
`wp theme install --force` command.

---

## 9a. Prior staging-only package (2026-07-16, historical — superseded above)

**Source commit:** `a327410397a1a80a0c4488d236d354a28fad6482` on branch
`feature/production-integration-1.17.1`.

| File (repo-relative) | Reason | Admin/DB action required | Asset dependency |
|---|---|---|---|
| `.gitignore` | Added `/docs-private/` exclusion | None | None |
| `docs-private/MAILCHIMP_INTERNAL_REFERENCE.md` | Private, local, untracked, gitignored — never deployed, never leaves this machine | None | None |
| `docs/ENGINEERING/MAILCHIMP_MANUAL_COMPLETION_REGISTER.md` | Sanitized placeholders, session findings | None | None |
| `docs/ENGINEERING/MAILCHIMP_EDUCATOR1_MANUAL_BUILD_PLAN.md` | New doc, sanitized | None | None |
| `docs/ENGINEERING/MAILCHIMP_TEMPLATE_REUSE_PLAN.md` | New doc, sanitized | None | None |
| `docs/CURRENT_TASK.md` / `docs/CHANGELOG.md` / `docs/NEXT_TASK.md` | Sanitized session entries | None | None |
| `page-reluctant-reader-adventure-kit.php` | Fixed stale "coming soon" header comment (comment-only, no behavior change) | None | None |
| `page-audience-retailers.php` | Expanded FAQ (5→9 items), added pricing block labeled as consumer/list price | None | None |
| `functions.php` | Added `bhp_audience_landing_seo_description_filter()` — meta/OG description fallback for 5 pages | None — fires automatically, no wp-admin field required | None |
| `docs/ENGINEERING/LANDING_PAGE_LAUNCH_MANIFEST.md` | This file | None | None |
| `docs/ENGINEERING/LANDING_PAGE_9_BREAKPOINT_QA_SHEET.md` | New — QA sheet, not yet executed | None | None |
| `docs/ENGINEERING/LANDING_PAGE_FUNNEL_TEST_CASES.md` | New — test cases, not yet executed | None | None |

This entire set is already included in commit `1a5c9ea` above (it's an
ancestor commit on the same branch) — deploying §9 covers this too.

## 10. Reclassified remaining items

**Launch blockers** (must resolve before any production-launch discussion):
- Latest code (this package) not yet deployed to staging
- 9-breakpoint staging QA not completed (sheet prepared, not executed)
- Checkout/cart regression not completed
- Community Reading Kit PDF absent (Organization audience)
- Gift Buyer lead magnet PDF absent
- Coupon rotation not completed (0 of 12 steps done — see private reference doc)
- Production URLs for both absent lead magnets not verified (cannot be, until the PDFs exist)
- Complete Collection factual claims not corrected **on production** (repo already has the fix in commit `15ddb93`; production has not been redeployed since)
- Production deployment not authorized (and not attempted this pass)

**Business dependency** (not code, not deployment — needs an actual business decision from Andrew):
- Retailer wholesale packet (asset doesn't exist)
- Ingram distribution status (unconfirmed anywhere in canonical docs)
- Approved retailer wholesale terms (prices, margins, minimums, returns — none exist yet, none invented)

**Andrew-owned parallel task** (explicitly not a Claude engineering blocker, tracked separately):
- Visual completion of all 15 Mailchimp emails (image uploads, button styling, footer cleanup) — see `MAILCHIMP_TEMPLATE_REUSE_PLAN.md`

## 11. Single remaining deployment-access requirement

The one blocker to moving this batch onto staging for live verification is
the SiteGround staging document-root path (or Andrew running the ZIP deploy
himself) — see `docs/RUNBOOK.md`'s `<doc_root>` placeholder, referenced
above as `[VERIFIED_STAGING_DOCUMENT_ROOT]`. No further guessing at SSH
hosts/paths will be attempted.

## Launch-readiness classification

**Not Launch Ready — Awaiting Assets, Staging Deployment, End-to-End QA,
Coupon Rotation, and Production Authorization.**

- Repository batch: complete and pushed.
- Local template implementation: complete.
- Staging verification: pending (blocked on `[VERIFIED_STAGING_DOCUMENT_ROOT]`).
- Production readiness: pending — production is currently serving stale
  trust-claim copy on the Complete Collection page that this branch's
  commit `15ddb93` already fixes; nothing further is safe to claim about
  production until it is redeployed and re-verified.
- Mailchimp visual completion: Andrew-owned, in progress, not a Claude blocker.

Do not treat any item in the Launch Blockers list above as minor. This
classification supersedes the "Launch Ready With Listed Manual Dependencies"
classification given in the prior turn's report, which was too optimistic.
