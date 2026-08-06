# Audience Landing-Page System — Status

**Round 5 (2026-07-16, Sprint A — content-only, no new rounds of layout work):** implemented the approved private CSO Conversion Optimization Audit's Sprint A across all 5 pages plus the homepage and Complete Collection page: corrected "Used in 40 classrooms" to "Placed in 40 Boise classrooms" and "Kirkus-reviewed series" to "Featuring a Kirkus-reviewed title" everywhere; replaced Educators'/Organizations' generic hero trust bar with audience-relevant claims; reduced the Educator toolkit-preview module from 5 panels to 1 teaser + a contents list; added a new trust/credibility section to Retailer and Organization (neither had one); swapped Gift Buyer's testimonial and added shipping/FAQ content; added a wholesale-pricing line to Retailer and a sponsored-book FAQ item to Organization; wired the real founder photo into Parent's author-photo slot. Theme v1.19.36 → **v1.19.37**, deployed and `wp eval`-clean, zero console errors observed across all 7 touched pages, 375px mobile-width spot-check on Educators showed no overflow. **This does not change any page's approval status** — the one-page-at-a-time rule and the mandated order (Educators next) are unaffected; this was a content-fix pass, not a review round. Full detail: `CHANGELOG.md`, `CURRENT_TASK.md`.

Built 2026-07-15, staging only, no production deployment. Covers the 5 core
audience landing pages and their shared component system. Theme currently
**v1.19.36** on staging (progressed from v1.19.30 across this sprint's
deploys — confirmed live via `wp theme list --status=active` after each
deploy, including the Round 4 deploy below). See also
`docs/ENGINEERING/AUDIENCE_FUNNEL_ARCHITECTURE.md` (naming conventions this
system follows), `docs/ENGINEERING/FUNNEL_CONSTITUTION.md` (frozen funnel
rules), `docs/ENGINEERING/AUDIENCE_LANDING_ASSET_MANIFEST.md` (missing
assets).

**Per-page review status (2026-07-15, corrected twice — real state, not the
earlier batch-complete claim):** two rounds of real defects were found
after the original "complete" claim below. Round 1 was a P0 rendering
defect; round 2 was a shared-layout refinement pass after Andrew reviewed
real rendered captures. Both are recorded in full below. Per-page status is
tracked individually, one page at a time — **no page is approved until
Andrew explicitly says so, in this session or a future one.**

### Round 1 — P0 section-visibility defect (root cause and fix)

Andrew's own six-page render of the Educator page showed hero on page 1,
blank content pages 2–5, footer on page 6. Reproduced directly: every
non-hero section sat at `opacity: 0` indefinitely on a fresh load.

**Root cause:** `assets/js/audience-landing.js` had been generated from
`parent-landing.js` via a find-and-replace pass, and one leftover instance
of the old class name survived: the `IntersectionObserver` callback added
class `pl-in-view` to a revealed section, but `audience-landing.css` only
ever defined a rule for `al-in-view`. The class actually being added never
matched any CSS selector, so revealed sections never became visible,
regardless of scrolling. (Parent's own file had matching class names
throughout, so it only suffered the milder issue below, not this one.)

**Secondary issue, same investigation:** even with matching class names, a
real browser transition-engine quirk was reproduced: setting two classes
that together should transition `opacity` 0→1 could leave the computed
value stuck at an intermediate point indefinitely, confirmed via direct
CSSOM inspection, forced-reflow busy-loop sampling, and class-reset
experiments — not assumed, directly observed.

**Fix (both `assets/js/audience-landing.js` and `assets/js/parent-landing.js`):**
content is visible by default; a section already in or above the viewport
on load is never hidden at all; an `IntersectionObserver` reveals
off-screen sections on scroll, with a hard 2.5s backstop timeout that
reveals everything regardless of observer support, JS errors, or reduced
motion; and — the actual fix for the transition-engine quirk — once a
section is revealed, both fade classes are removed entirely about 700ms
later, returning the element to its plain, class-free CSS (unconditionally
`opacity: 1`, no transition involved), so the guaranteed final state never
depends on a transition completing correctly. A `@media print` override
was added to both CSS files as defense-in-depth for capture tools that
render via the print stylesheet. See commit `bc8cd3b`.

### Round 2 — shared layout defects (found from Andrew's real rendered PDFs)

Andrew supplied four full-page PDF renders (Gift Buyers, Educators,
Organizations, Parent) and a written list of repeated visual defects.
Reproduced and fixed all of them in the shared component files (not
per-page patches):

1. **Problem-card grid left an orphan card.** `auto-fit` grid columns fit
   3 cards per row for a 4-card set, stranding the 4th card alone with a
   large empty gap beside it. Fixed with an explicit `repeat(4, 1fr)`
   desktop / `repeat(2, 1fr)` tablet / `1fr` mobile grid. **Note:** this
   fix left Retailer's 3-card grid on the 4-column default with one empty
   cell — Andrew corrected this in review as "technically tidy but
   visually weak," not actually fixed. See Round 3 item 1 below for the
   real fix (card-count-aware `--cols-3`/`--cols-2` modifiers).
2. **Hero headlines wrapped 4–5 lines.** Two causes, both fixed: the hero
   text column was too narrow (widened the grid ratio and increased the
   heading's `max-width`), and — the real root cause, found while
   verifying the first fix — a **sitewide CSS specificity bug**. A global
   rule in `style.css` (`body:not(.home) h1 { font-size: clamp(3.2rem, 7vw,
   6rem); }`) has higher specificity than a single-class landing-page
   selector and was silently winning, rendering headlines *larger* than
   even the page's own intended maximum (measured 89.6px against an
   intended 66px cap on a 1280px viewport). Fixed by adding the
   `.audience-landing`/`.parent-landing` scope class to every affected
   heading selector (9 per file) so the landing-page rule wins on
   specificity. Verified live: headlines that wrapped 5 lines now wrap 3.
3. **Oversized vertical spacing.** Section `padding-block` and several
   stacked component margins were reduced (see CSS diff for exact values)
   so adjacent sections don't compound into an oversized gap at their
   shared boundary.
4. **Lead-magnet panels showed a real book cover as if it were the free
   PDF.** All four not-yet-built lead magnets (Adventure Learning Toolkit,
   Meaningful Gift Guide, Wholesale Guide, Community Reading Kit) were
   rendering the Mariana Trench book cover next to "Free · [Guide Name]"
   text, implying the book was the download. Replaced with an honest
   dashed-border placeholder at a realistic document aspect ratio, labeled
   "[X] cover in progress." **Parent's own lead-magnet panel was
   deliberately left untouched** — its Chapter 7 preview legitimately
   shows the Mariana Trench cover, since that's the real book the free
   chapter is from.

   **Update 2026-07-17:** Educator's placeholder was replaced with its real
   cover first (`educator-toolkit-cover.webp`, prior release). This same
   pass now closes it out for Meaningful Gift Guide and Community Reading
   Kit too — `assets/images/handoff/gift-guide-cover.webp` and
   `community-reading-kit-cover.webp`, rendered from the approved PDFs
   (production Media Library attachments #392 and #389, the only place
   either asset existed). See `docs/CHANGELOG.md` 2026-07-17 "Gift Buyer +
   Community Org lead-magnet covers..." entry for the full naming caveat on
   the Gift Guide cover. Wholesale Guide (Retailer) remains a placeholder —
   out of scope, Retailer is Post-Launch per `docs/DECISIONS.md`.
5. **Collection block read as oversized.** Card widened, padding and
   surrounding margins tightened, price and CTA sizing increased slightly
   for visual weight.
6. **Trust-section stat numbers read as too small** relative to the dark
   background. Enlarged ~11% (46px → 51px) and bolded the labels.
7. **Sticky mini-CTA bar overlapped the footer** in Andrew's captures. The
   JS suppression list (already hiding the bar over the lead-magnet panel,
   Collection card, and final CTA) now also includes the site footer.
8. **Distinct-per-audience content module — not completed this round.**
   Educators already has one real distinct module (an "Invite Andrew for
   a Read-Aloud" CTA block); Gift Buyers, Retailers, and Organizations do
   not yet have an equivalent. Flagged as separate, real design/content
   work rather than folded into this bug-fix pass. **Superseded by Round
   3 item 2 below** — Educators now has a second distinct module (the
   Adventure Learning Toolkit preview). Gift Buyers, Retailers, and
   Organizations still have none; unchanged status, real open item.

### Round 3 — Educator-review directive (2026-07-15, this session)

Andrew reviewed the Round 2 shared fixes directly (confirmed directionally
correct) and issued a 7-phase directive: commit/document the shared fixes,
correct the Retailer 3-card grid, do a full Educator-only visual review,
add an Educator-specific resource-preview module, produce logged-out
captures, run full Educator QA, and regression-check Parent. Production
remained out of scope throughout.

1. **Retailer 3-card grid — real fix.** Andrew's correction: leaving an
   empty 4th cell on the shared 4-column grid is "technically tidy but
   visually weak." Added card-count-aware modifier classes to both
   `assets/css/audience-landing.css` and `assets/css/parent-landing.css`:
   `--cols-3` (equal thirds desktop, 2-col tablet, 1-col mobile) and
   `--cols-2` (centered via `max-width` + `margin-inline:auto`, not
   stretched full-width). Applied `--cols-3` to Retailers' one grid (the
   "reader profile" section) only — verified live: 3 equal 356px columns,
   `childCount: 3`, no empty cell. `--cols-2` modifier added to both files
   for future reuse but not yet applied anywhere (no page currently has a
   2-card grid).
2. **Educator-specific toolkit-preview module.** The existing Read-Aloud
   invitation alone wasn't enough to visually distinguish the page per
   Andrew's instruction. Added a new section between the lead-magnet panel
   and the Complete Collection section: 5 honest placeholder figures
   (toolkit cover, discussion questions, vocabulary/geography, read-aloud
   guide, classroom activity), each labeled "— design in progress," reusing
   the existing `.audience-landing-media-grid` pattern (locked 3:4 aspect
   ratio, dashed-border placeholder) originally built for Parent's interior
   preview. No fabricated PDFs, photography, or results — verified live: 5
   figures found, all `ratio: 0.75`, captions match exactly.
3. **Full Educator QA (9 breakpoints + functional).** 320/360/375/390/430/
   768/1024/1280/1440px swept live: zero horizontal overflow, all 10
   sections (9 original + new toolkit-preview) visible at full opacity at
   every width, hero `h1` correctly clamps from 40px (mobile) to 66px
   (desktop). Lead-magnet form confirmed correctly gated — `$download['ready']`
   is `false` for Educators, so the page renders the honest "Coming Soon"
   `aria-disabled="true"` block, not a live form (verified in
   `page-audience-educators.php:211-230`). No coupon-code text found
   anywhere on the page. Complete Collection format toggle
   (`.audience-landing-format-toggle`, `role="radiogroup"`) tested live —
   clicking Hardcover correctly flips `aria-checked` and swaps the visible
   price panel; reset to Paperback afterward. FAQ accordion (native
   `<details>`/`<summary>`, 6 items) tested live — opens/closes correctly,
   inherently keyboard-operable with no JS dependency. Zero browser console
   errors. `wp eval 'echo "ok";'` clean (no PHP fatal) after deploy;
   `wp theme list --status=active` confirms v1.19.35 live on staging.
   Reduced-motion/JS-disabled safety confirmed by code inspection: the
   `.al-fade-init` hidden-state class (which drives the reveal animation)
   only applies inside `@media (prefers-reduced-motion: no-preference)`,
   and is only ever added by JS — so with JS disabled the class is never
   added and sections stay at their default `opacity: 1`. Keyboard focus:
   all currently-reachable interactive elements retain a visible focus
   indicator (default browser outline, or an explicit `:focus-visible`
   ring on the format-toggle buttons); the one exception is the acquisition
   form's `input:focus { outline: none }` rule with no replacement — but
   that form doesn't render while the toolkit is gated to "Coming Soon,"
   so it isn't currently reachable by keyboard users. Flagged as a
   pre-existing, dormant gap, not new and not currently live.
   Scroll-triggered sticky-bar show/hide behavior (fixed positioning,
   `bottom:0`) was not re-tested this round — the sandboxed browser tool
   cannot programmatically scroll the page (`window.scrollTo`/
   `scrollIntoView` have no effect on `window.scrollY`, confirmed via
   direct testing), a limitation in the same family as the screenshot-tool
   failure below. The underlying show/hide logic was already fixed and
   verified in Round 2 item 7.
4. **Parent regression (Phase 7).** Re-checked live against the new
   grid-modifier CSS: 4-card problem grid still renders as 4 columns (not
   affected by Retailer's `--cols-3` addition), Chapter 7/interior-preview
   images all at correct 3:4 ratio, signup form live and ungated (Parent's
   toolkit PDF is set), no "Coming Soon" block, 10 FAQ items present,
   sticky bar present, trust section present, no coupon-code text. No
   horizontal overflow at 1280px or 390px. Parent was not redesigned —
   only re-verified against the shared CSS changes.
5. **Logged-out captures — still not resolved, escalating rather than
   silently dropping.** The `computer{screenshot}` tool failed on three
   deliberately varied attempts this session (full-page on an existing
   tab, full-page on a fresh tab, a small 400×300px `zoom` region) — a
   genuine, confirmed, total failure, not a fluke. As an alternative,
   tried capturing via Andrew's real connected Chrome
   (`mcp__claude-in-chrome__*`): this succeeded technically, but revealed
   that browser carries an active WordPress admin session on staging2
   even in a brand-new tab, reproducing the same admin-toolbar +
   "Analytics Debug" gear-button artifacts seen in Andrew's original PDFs
   — real-time confirmation of the Round 2 root-cause finding, but not a
   genuinely logged-out capture. Deliberately did not force a logout of
   that session, since it would also have terminated Andrew's two other
   active tabs (production wp-admin, Mailchimp automation builder) —
   treated as a disruptive, unrequested action requiring his explicit
   authorization. **This gap is unresolved and needs Andrew's input**:
   either he supplies an incognito capture himself, or explicitly
   authorizes a specific alternative.

Deploy: full-ZIP `wp theme install --force` to staging, `wp eval` clean,
`wp sg purge` run. `style.css` bumped 1.19.34 → 1.19.35. Code committed
(`3607201`) after the diff was checked for whitespace/conflict markers
(`git diff --check`, clean) and scanned for sensitive strings (clean).

### Round 4 — Gift Buyer page content update (2026-07-15, this session)

Updated the Gift Buyer audience landing page (`page-audience-gift-buyers.php`)
to match the established shared landing-page specification. Checked each
section against the specification and found 2 content gaps: the occasions
module had 4 category cards where the specification calls for 5, missing
"milestone" and "classroom/teacher gift" categories; the FAQ was missing
a question covering individual-book purchasing.

**Content additions.** Added "Milestones" and "Classroom & teacher gifts"
to the page's `$occasions` array (4 → 6 items) and "Can I buy just one book
instead of the whole Collection?" to `$faqs` (6 → 7 items). The individual-
purchase FAQ answer was written to match an existing "View individual
books" link already present in the Collection pricing card — no new claim
introduced. The occasions grid growing to 6 cards would have left 2 empty
cells on the existing 4-column grid default; applied the existing
`.audience-landing-grid--cols-3` modifier class (already used elsewhere in
this component system) instead of leaving the empty cells or writing a new
rule.

**Data verification performed during the audit.** The page's existing
testimonial content was checked against `inc/amazon-reviews.php`'s
`amz-mariana-04` registry entry and matches exactly (reviewer name, excerpt
text, verified-purchase flag). The page's "Used in 40 classrooms" statistic
is a pre-existing, previously-flagged content item — see `KNOWN_ISSUES.md`'s
existing classroom-count entry — and was not modified this round.

**Lead magnet / coupon status, verified live.** `wp eval` confirmed
`bhp_get_lead_magnet_pdf_url('gift_guide')` still returns an empty value on
staging, so the page correctly renders its gated "Coming Soon" state (no
live signup form) rather than a functional form — same gating mechanism as
the Educator page. `wp post list --post_type=shop_coupon` shows only
`[PARENT_COUPON_CODE_SUPERSEDED]` registered in WooCommerce; `[GIFT_BUYER_COUPON_CODE_SUPERSEDED]` does not exist yet as a
coupon, and a live page-text scan confirmed no coupon code appears anywhere
on the page.

**Mailchimp status.** No automation referencing the Gift Buyer lead magnet
exists yet in `ENGINEERING/MAILCHIMP_STATUS.md` or in live Mailchimp state.
Confirming or building one requires an authenticated Mailchimp session —
not attempted this round.

**Analytics.** The page uses the same event conventions already documented
for the other 4 audience pages: a consent-gated `gift_landing_view`
pageview event, the shared `audience_landing_format_selected`/
`audience_landing_faq_open`/`audience_landing_free_cta_click` events fired
by `assets/js/audience-landing.js`, and CTA-click tracking via `nav.js`'s
existing `[data-bhp-event]` click listener. No new analytics code was
added.

**QA.** Swept all 9 breakpoints (320/360/375/390/430/768/1024/1280/1440px)
live: zero horizontal overflow, all 9 sections visible at full opacity at
every width, occasions grid renders at 3/3/2/1 columns across the sweep as
expected. Format-selector toggle tested live: clicking each option updates
`aria-checked` and swaps the visible pricing panel correctly in both
directions. FAQ accordion tested live: all 7 items open and close
correctly (native `<details>`/`<summary>`, no JS dependency). Zero
coupon-code text found on the page. Zero browser console errors observed.
`wp eval 'echo "ok";'` returned clean (no PHP fatal) after deploy;
`wp theme list --status=active` confirmed v1.19.36 live. Reduced-motion,
JS-disabled, and keyboard-focus behavior were verified by code inspection
(same shared CSS/JS classes already runtime-verified on the Educator page).
Regression spot-check: Educators, Retailers, and Parent were each checked
post-deploy at 1440px — section counts, grid column counts, and overflow-
free rendering were all unchanged, since no shared CSS/JS file was modified
this round (only the page-specific PHP file and the version-bump line in
`style.css`).

**Deploy.** Full-ZIP `wp theme install --force` to staging, `wp eval`
clean, `wp sg purge` run. `style.css` bumped 1.19.35 → 1.19.36. Code
committed (`81c7e33`) after `git diff --check` (clean) and a sensitive-
string scan of the diff (clean).

**Not done this round, recorded rather than omitted:** no audience-specific
visual/media module was added to the Gift Buyer page — the specification
does not currently define one for this page (unlike the Educator page,
which has one). `KNOWN_ISSUES.md`'s distinct-per-audience-module entry
still lists Gift Buyers as open. This page has not received Andrew's
explicit review or approval.

### Logged-out capture requirement (permanent, going forward)

Andrew's supplied PDFs showed the WordPress admin toolbar and a row of
small "gear" icons on every section. Investigated and root-caused, not
assumed: the toolbar plus icons only appear because the browser session
that produced those PDFs was **logged in** as a WordPress administrator
(`Howdy, [admin]` is visible in the toolbar in the captures themselves).
The gear icons are the `sg-ai-studio` plugin's admin-only content-edit
affordances (SiteGround's built-in AI content-editing tool, confirmed via
its own source, gated by `current_user_can()`); a separate "Analytics
Debug" button seen in captures is this project's own pre-existing
admin-only debug panel (`BHP_Analytics_Config::debug_mode_available()`,
requires staging + logged-in administrator). None of this is a defect and
none of it renders for a real, logged-out visitor — confirmed via a fresh,
never-reused browser tab on every audience page this sprint. **All future
review captures must be taken from a fully logged-out browser session (or
a private/incognito window)** — a capture taken while signed into
wp-admin is not evidence of what a customer sees.

### One-page-at-a-time approval rule (permanent, established by Andrew)

Build or repair one audience page → run full QA (fresh-load section
visibility, breakpoint sweep, debug-control check, content-vs-spec review,
PHP/JS error check) → produce desktop and mobile evidence → Andrew reviews
it → revise until Andrew explicitly approves it → **only then** move to the
next page. Shared-component fixes (CSS/JS files used by all pages) may be
applied and deployed together, since they're one change touching every
page identically — but that does **not** constitute approval of any
individual page. Never batch-declare multiple pages "complete" from DOM
text and two viewport widths alone — a full section-by-section
computed-style inventory (opacity, visibility, dimensions) is the minimum
bar. Mandated page order: Educators → Gift Buyers → Bookstores/Retailers →
Organizations → final Parent regression review → cross-page staging QA →
assets → Mailchimp connections → coordinated production deployment.

### Current per-page status (2026-07-15, real state)

- **Educators** — P0 fix applied and verified; shared-layout refinements
  (Round 2) applied and verified live; Round 3 Educator-review directive
  complete: Educator-specific toolkit-preview module added, full 9-breakpoint
  + functional QA passed, Parent regression-checked clean. **Not yet
  explicitly approved** — this is the page immediately awaiting Andrew's
  approval decision before the one-page-at-a-time rule allows formal
  progression. Logged-out visual captures are still outstanding (see
  Round 3 item 5) — Andrew's input needed on how to proceed.
- **Gift Buyers** — built, P0 fix applied, independently re-verified
  (fresh-load section inventory, 9-breakpoint sweep, debug-control check,
  content-vs-spec review, zero PHP/console errors), shared-layout
  refinements applied and verified live. **Round 4 (2026-07-15):** added 2
  occasion categories and 1 FAQ item to close content gaps against the
  page specification, applied the existing `--cols-3` grid modifier to the
  now-6-card occasions grid. Lead magnet confirmed correctly gated (PDF
  not set); no `[GIFT_BUYER_COUPON_CODE_SUPERSEDED]` coupon exists yet and none is exposed on the page;
  no Mailchimp automation exists yet (requires an authenticated session,
  not attempted). Full 9-breakpoint + functional QA passed; Educators/
  Retailers/Parent regression-checked clean. Not yet reviewed or approved
  by Andrew.
- **Retailers** — same full verification pass as Gift Buyers, completed
  and passed; shared-layout refinements applied and verified live,
  **corrected in Round 3**: the 3-card reader-profile grid now renders as
  3 genuine equal columns via the `--cols-3` modifier, not a 4-column grid
  with an empty cell. Not yet reviewed or approved by Andrew.
- **Organizations** — same full verification pass, completed and passed;
  shared-layout refinements applied and verified live. Not yet reviewed
  or approved by Andrew.
- **Parent** — Chapter 7 lead-image sizing fix complete (prior sprint);
  P0 fix regression-checked (all sections visible, image ratio preserved
  at 65.1%, still within the 55–70% target); shared-layout refinements
  applied — hero/grid/spacing fixes verified live, lead-magnet panel
  deliberately unchanged (its real book cover is accurate), interior-
  preview and author sections confirmed still present and untouched.
  This is the **final Parent regression review** step in the mandated
  order — formally still pending Andrew's review alongside the 4 new
  pages, not a separate earlier gate.

**Exact next action:** Andrew reviews Educators (first in the mandated
order) — including the outstanding logged-out-capture gap — and gives
explicit approval or further correction. No other page moves to
"approved" before Educators does, per the one-page-at-a-time rule above.
Only after explicit Educator approval does Gift Buyers become the next
page in the mandated order.

**Production remains untouched throughout all four rounds.** All work
this sprint was staging-only (`wp theme install --force`, verified
`wp eval` clean after every deploy, cache purged). Theme progressed
1.19.30 → 1.19.36 across this sprint's deploys.

## Pages (all staging, all `publish` status, none in production nav)

| Audience | Template | Staging URL | WP post ID |
|---|---|---|---|
| Parents / Reluctant readers (canonical template) | `page-reluctant-reader-adventure-kit.php` | `/reluctant-reader-adventure-kit/` | 348 |
| Teachers / Librarians / Homeschool | `page-audience-educators.php` | `/educators-adventure-learning-toolkit/` | 585 |
| Gift Buyers | `page-audience-gift-buyers.php` | `/gift-buyers-guide/` | 587 |
| Bookstores / Retailers | `page-audience-retailers.php` | `/retailers-wholesale-guide/` | 588 |
| Organizations | `page-audience-organizations.php` | `/organizations-community-reading-kit/` | 589 |
| Staging review index (links to all 5, not in nav) | default page template | `/staging-audience-review-index/` | 591 |

**Note:** the Gift Buyers page's originally-intended slug
(`/gift-buyers-meaningful-gift-guide/`) collided with a pre-existing,
unrelated page (post ID 586, "Gift Buyers", default template, generic
"Field Journal" boilerplate content) already on staging — left untouched,
new page uses `/gift-buyers-guide/` instead.

## Shared component system

- `assets/css/audience-landing.css` / `assets/js/audience-landing.js` —
  independent files from `parent-landing.css`/`.js` (same proven design
  system and both root-caused Parent-page fixes carried over: the
  lead-image wrapper-stretch fix and touch-device hover-media-query
  gating), so neither page's future changes can regress the other.
- Enqueued via `bhp_enqueue_audience_landing_assets()` in `functions.php`,
  gated to the 4 new page templates only (Parent keeps its own dedicated
  enqueue function, untouched).
- No new template-part files were needed — all 5 pages reuse the existing
  real infrastructure directly: `template-parts/acquisition/lead-magnet-
  cta.php` → `signup-form.php` → `bhp_mailchimp_signup` (the same pipeline
  Parent already uses), and `bhp_parent_landing_cover()` for book images.

## Form / Mailchimp connection status

All 4 new pages' lead-magnet forms use the exact same real, live
MC4WP-backed submission pipeline as the Parent page (single shared list,
segmented by `AUDIENCE`/`LEADMAG`/`SOURCE` merge fields plus tags) — there
is no separate "unverified" form destination to build. What's actually
gated is the **PDF**, not the pipeline: each page checks a new
`bhp_get_*_download()` helper (mirroring `bhp_get_reluctant_reader_download()`)
and shows a static "Coming Soon" block instead of the live form until
Andrew sets the real PDF URL under **Settings → Lead Magnets** (extended
this sprint to 4 new fields: Adventure Learning Toolkit, Meaningful Gift
Guide, Wholesale Guide, Community Reading Kit). This matches the existing
site convention (Parent's own Adventure Kit PDF field works the same way)
and means no live signup can reach an unbuilt Mailchimp automation.

### New audience types (extends `bhp_audience_types` filter)

`educators`, `gift_buyers`, `retailers`, `organizations` — kept distinct
from the existing `teachers` audience type (which belongs to the separate,
already-live Mariana classroom-guide funnel) so tracking never mixes.

### New lead magnets (extends `bhp_lead_magnets` filter)

`teacher_adventure_toolkit`, `meaningful_gift_guide`,
`bookstore_wholesale_guide`, `community_reading_kit` — each `status:
placeholder` until its PDF is set.

### Tags already applied (extends `bhp_mailchimp_signup_tags` filter)

Applied now, following the site's exact existing Title Case + "Audience: X"
+ "Source: Y Landing Page" convention, so signups are never left on the
generic `Adventure Club` fallback while Andrew/ChatGPT build the matching
Mailchimp automations:

| Lead magnet | Tags applied |
|---|---|
| `teacher_adventure_toolkit` | `Adventure Learning Toolkit`, `Audience: Educator`, `Source: Educator Landing Page` |
| `meaningful_gift_guide` | `Meaningful Gift Guide`, `Audience: Gift Buyer`, `Source: Gift Buyer Landing Page` |
| `bookstore_wholesale_guide` | `Wholesale Guide`, `Audience: Retailer`, `Source: Retailer Landing Page` |
| `community_reading_kit` | `Community Reading Kit`, `Audience: Organization`, `Source: Organization Landing Page` |

### Exact values Andrew/ChatGPT still need to supply

1. Four PDF files under **Settings → Lead Magnets** (Adventure Learning
   Toolkit, Meaningful Gift Guide, Wholesale Guide, Community Reading Kit).
2. Four Mailchimp automations triggered on the tags above (mirroring the
   Parent/Mariana pattern: delivery email → nurture sequence). None of
   these 4 automations exist yet — this sprint only applied the tags the
   automations will key off of.
3. No dedicated thank-you pages were built for these 4 (unlike Parent's
   `/adventure-kit-thank-you/`) — forms show their default inline success
   message. A dedicated thank-you page per audience is optional future
   work, not required for the forms to work correctly.

### Attribution fields

Every form already carries `audience_type`, `lead_magnet`, `source_page`
(auto-derived from the actual page URL), plus the honeypot/nonce/context
fields the shared `signup-form.php` template always emits. UTM/referrer/
consent-state capture is handled by the site's existing sitewide analytics
layer (`BHP_Analytics_Config`), not duplicated per-page — each page fires
its own `*_landing_view` pageview event (`educator_landing_view`,
`gift_landing_view`, `retailer_landing_view`, `organization_landing_view`)
matching Parent's `parent_landing_view` pattern.

## Popup architecture

All 4 new pages added to `bhp_should_show_any_popup()`'s exclusion list —
each is already the dedicated signup destination for its own audience via
its embedded lead-magnet panel, so no sitewide or cross-audience popup can
render on top of it. The (unbuilt) Audience Routing Quiz is not referenced
anywhere in this system, per the frozen Audience Routing Constitution.

## Commerce / pricing

All 5 pages read live Complete Collection pricing via
`bhp_bundle_expected_price()`/`bhp_bundle_rules()` — no hardcoded prices.
No public coupon codes appear anywhere (`[PARENT_COUPON_CODE_SUPERSEDED]`/`[EDUCATOR_COUPON_CODE_SUPERSEDED]`/`[GIFT_BUYER_COUPON_CODE_SUPERSEDED]`
absent from all 5 pages, verified via live browser page-text scan).
Bookstore/Retailer page deliberately does not route through WooCommerce as
the primary wholesale path (no Add to Cart present) — its primary CTA is a
wholesale contact inquiry. Organizations page shows the real per-set
Complete Collection price and routes bulk-quantity requests to a contact
inquiry rather than inventing a bulk-discount table. Ingram distribution is
explicitly marked "coming soon" on the Retailer page (not claimed active) —
no canonical doc confirms Ingram readiness as of this writing
(`docs/WORKLOG/2026-07-13.md` lists Ingram as explicitly out of scope /
untouched).

## QA performed this sprint (staging, real browser, logged out)

- All 5 pages: no PHP fatals (`wp eval` clean after every deploy), correct
  active theme version, cache purged.
- All 5 pages: root `[data-audience-landing]`/`[data-parent-landing]`
  element present, shared CSS file loads, FAQ items render (5–10 per
  page), Complete Collection pricing card renders with live pricing (where
  applicable).
- All 5 pages: no public coupon code text present.
- Desktop (1280px) and mobile (375px, confirmed via `window.innerWidth`,
  not just the resize tool) checked for horizontal overflow — zero found
  on any of the 5 pages.
- Parent page specifically: Chapter 7 lead image renders at 63.2% of panel
  height on desktop / 57.2% on mobile (target 55–70%), root-caused via the
  wrapper-stretch CSS fix (see commit `b877723`).
- Teacher and Bookstore pages: coming-soon lead-magnet state confirmed
  correct (no live form submission until PDF is set).
- Debug/gear controls: none found on a fresh logged-out session (DOM scan
  for fixed-position elements with debug/gear/admin-bar class or id names)
  — no admin bar, not logged in.

### QA performed in the P0-fix and shared-layout-refinement rounds (2026-07-15)

- Full 9-breakpoint (320/360/375/390/430/768/1024/1280/1440) sweep
  completed live for Educators, Gift Buyers, Retailers, Organizations —
  zero horizontal overflow, all sections visible at every width.
- Fresh-load section-by-section computed-style inventory (opacity,
  visibility, bounding rect) run on all 5 pages after both the P0 fix and
  the shared-layout refinements — not just DOM-text presence checks.
- Debug-control scan (full fixed/sticky element enumeration plus
  aria-label/title text scan) run in a genuinely fresh, never-reused
  browser tab, logged out, on all 5 pages — zero debug/gear controls found
  in any case; only the legitimate sticky site header present.
- `wp eval` clean (no PHP fatals) after every deploy this sprint.
- Zero browser console errors observed on any of the 5 pages.

### Still not done (deferred, honestly, not silently dropped)

- Distinct per-audience visual/content module for Gift Buyers, Retailers,
  Organizations (Educators now has two — the Read-Aloud CTA and the new
  toolkit-preview module, see Round 3 item 2) — real design/content work,
  scoped as a separate next step, not started for the other 3 pages.
- Full logged-out desktop/mobile visual screenshot captures for Andrew's
  review — the `computer{screenshot}` browser-automation tool has failed
  on every attempt this project (3 more varied attempts this round); a
  second capture route via Andrew's real connected Chrome was tried and
  also could not satisfy the logged-out requirement (that browser carries
  an active wp-admin session — see Round 3 item 5). DOM/computed-style
  inspection was used as the verification method instead throughout, and
  is documented as such rather than presented as a visual capture. This
  is now a genuine open question for Andrew, not just a tooling note.
- Keyboard/screen-reader accessibility pass (FAQ `<details>` semantics and
  focus order were inherited unchanged from the already-accessible Parent
  template, but not independently re-tested per new page).
- Live GTM/GA4 event verification for the 4 new `*_landing_view` events
  (code-reviewed and pattern-matched against Parent's already-verified
  event, not fired-and-observed in Preview/DebugView this sprint).
