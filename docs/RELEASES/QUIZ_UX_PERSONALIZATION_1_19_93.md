# Find Your Adventure — UX/copy personalization + scroll-position fix (staging 1.19.93)

**Date:** 2026-07-29. **Environment: staging only.** Production remained at theme **1.19.91** throughout and was touched only for read-only comparison. **No production approval was requested or given for this release.**

- Branch at time of work: `feature/production-integration-1.17.1`, HEAD `3e02529` (1.19.91).
- Staging before: 1.19.91. Staging after: **1.19.93**.
- Backup taken before the first install: `~/bhp-staging-theme-backup-1.19.91-20260729-172521.tar.gz` on the SiteGround account (outside the repo).
- Rollback: reinstall that tarball's directory, or rebuild a ZIP from commit `3e02529`.

## Why

The quiz looked finished but behaved like an audience-classification form. The second question rarely changed anything, one answer ("Author visit information") pointed at a resource that doesn't answer it, "is a good fit" read mechanical, "Get the Free …" implied an immediate download that doesn't happen, and the modal repeated its own heading on the result screen. Separately, closing an automatically-opened modal moved the visitor to the footer.

## Files changed (7)

| File | Change |
|---|---|
| `template-parts/quiz/audience-quiz.php` | Per-answer result config; new Q1/Q2 copy; intro copy; progress labels; result eyebrow; `in_modal` arg; header wrapper; option label span |
| `assets/js/audience-quiz.js` | Reads per-answer result copy/destination with route-level fallback; collapses the header on the result step; focus management; `aria-label` on the Q2 radiogroup; sibling `aria-checked` clearing |
| `template-parts/components/quiz-entry-cta.php` | New teaser copy + CTA; passes `in_modal`; removed the "Open the full quiz page" link and its now-unused URL build |
| `assets/js/quiz-modal.js` | Scroll capture/restore hardening; `scrollToInstant()`; binds "Keep browsing this page" |
| `assets/css/audience-quiz.css` | Progress label; chevron affordance + reduced-motion guard; result-state padding; dismiss-button styling; focus-visible rules |
| `assets/css/quiz-modal.css` | Removed dead `.bhp-quiz-modal__fallback` rules; rebalanced modal padding; tighter result-state top padding |
| `style.css` | Version bump; homepage dark-section contrast fix for the quiz's body copy |

An **uncommitted working-tree change already present** in `assets/js/quiz-modal.js` (scroll capture + `preventScroll` focus) was reviewed, found correct, and **kept** — it was extended, not replaced. See "Scroll fix" below for what was added on top.

## Routing / result matrix (all 12 verified live on staging)

Audience destinations unchanged. `utm_source=quiz&utm_medium=onsite&utm_campaign=audience_quiz&utm_content=<entry_location>` on every CTA.

### Q1 — "What brings you to Brave Hearts today?"

| Answer | Route | Audience |
|---|---|---|
| Help a young reader enjoy reading | parent | `parents_families` |
| Bring adventure into my classroom, library, or homeschool | educator | `educators` |
| Create a reading program, event, or partnership | organization | `organizations` |
| Choose a meaningful gift for a child | gift | `gift_buyers` |

### Parent — "What would feel like the biggest win right now?" → `/reluctant-reader-adventure-kit/`

| Answer | `quiz_intent` | Result headline | CTA |
|---|---|---|---|
| They choose reading with less resistance | `reluctant_reader` | Start with a low-pressure reading adventure. | Explore My Free Adventure Kit |
| They build confidence with chapter books | `chapter_confidence` | Build confidence one adventure at a time. | Explore My Free Adventure Kit |
| We find a first adventure to enjoy together | `first_adventure_together` | Choose a first adventure to enjoy together. | Explore My Free Adventure Kit |

### Educator — "What would help you bring the story to life?" → `/educators-adventure-learning-toolkit/`

| Answer | `quiz_intent` | Result headline | CTA |
|---|---|---|---|
| Ready-to-use discussion and activity ideas | `discussion_activities` | Turn the story into ready-to-use learning. | Explore the Free Classroom Toolkit |
| Science and geography connections | `science_geography` | Connect the adventure to real-world discovery. | Explore the Free Classroom Toolkit |
| Vocabulary and discussion support | `vocabulary_discussion` | Build vocabulary and discussion through story. | Explore the Free Classroom Toolkit |

**Removed:** `read_aloud_ideas`, `author_visit`, `classroom_resources`. Author-visit intent deliberately has no quiz route until a genuinely distinct destination exists.

### Organization — "What are you hoping to create?" → `/organizations-community-reading-kit/`

| Answer | `quiz_intent` | Result headline | CTA | Destination |
|---|---|---|---|---|
| A memorable community reading event | `community_event` | Turn your next reading event into an adventure. | Explore Community Reading Resources | page top |
| An engaging youth literacy program | `literacy_programming` | Give your literacy program a story-led starting point. | Explore Community Reading Resources | page top |
| A book order or partnership for a larger group | `partnership_inquiry` | Let's explore the right group or partnership path. | Explore Group Orders & Partnerships | **`#contact`** |

`#contact` is the page's existing "Let's bring story-led reading to your program." section, which already covers bulk orders and partnership enquiries. No new program was invented and no partnership terms are stated.

### Gift buyer — "What do you want the gift to spark?" → `/gift-buyers-guide/`

| Answer | `quiz_intent` | Result headline | CTA |
|---|---|---|---|
| Curiosity about the world | `curiosity` | Give them a story that opens up the world. | Explore the Free Gift Guide |
| Confidence in a growing reader | `confidence` | Choose an adventure for a growing reader. | Explore the Free Gift Guide |
| Time to read and discover together | `shared_discovery` | Give them an adventure you can share. | Explore the Free Gift Guide |

**Removed:** `birthday`, `holiday`, `milestone` — none of them changed the recommendation.

`community_event` / `literacy_programming` / `partnership_inquiry` were **kept** from the previous build (same concepts). `reluctant_reader` was kept for the parent "less resistance" answer for the same reason. Everything else is a new, descriptive value.

## Scroll fix

**Root cause (proven, not inferred):** returning focus to the launcher after an automatic open. The launcher sits near the footer, so it is off-screen; the browser scrolls it into view. Measured on staging from `scrollY 1295` with focus on `<body>`:

- `launcher.focus()` → scrollY **3749** (**+2454px**)
- `launcher.focus({preventScroll:true})` → scrollY **1295** (**0px**)

**What ships:**
1. `scrollX`/`scrollY` captured in `openModal()` immediately before the dialog is shown.
2. `focus({preventScroll:true})` inside `try`, with a plain `focus()` fallback for browsers without `FocusOptions`.
3. Captured coordinates re-asserted after focus, then once more on the next animation frame (layout settles when the body scroll lock is released; some engines apply focus scrolling asynchronously).
4. **`scrollToInstant()`**: the theme sets `html { scroll-behavior: smooth }` sitewide, which would have turned the restore into a visible animated scroll. The helper sets `scrollBehavior:'auto'` inline for the duration of the jump and then restores the inline value to `''` — verified after close that the computed value is still `smooth`, so ordinary in-page anchor links keep animating.
5. All four dismissal routes go through the same `closeModal()`; "Keep browsing this page" is bound in `quiz-modal.js` to `closeModal('keep_browsing')`, not a second close routine.

**Measured results — every row is `before` vs `after` window scroll, live on staging 1.19.92/1.19.93:**

| Open | Page | Dismissal | Before | After | Δ | Launcher distance below | Focus returned |
|---|---|---|---|---|---|---|---|
| manual | blog post | close button | 1400 | 1400 | **0** | — | yes |
| manual | blog post | Escape | 1400 | 1400 | **0** | — | yes |
| manual | blog post | backdrop | 1400 | 1400 | **0** | — | yes |
| manual | blog post | Keep browsing | 1400 | 1400 | **0** | — | yes |
| **automatic** | blog post | close button | 1295 | 1295 | **0** | 2830px | yes |
| **automatic** | blog post 2 | Escape | 2998 | 2998 | **0** | 5182px | yes |
| **automatic** | product page | backdrop | 2492 | 2492 | **0** | 3960px | yes |
| **automatic** | About page | Keep browsing | 1284 | 1284 | **0** | 2477px | yes |

Exact match, not within-a-pixel. Horizontal position was 0 throughout (no horizontally scrolled page available to test a non-zero `scrollX`; the code path is symmetric).

Reopening after "Keep browsing this page" showed the **result step still on screen with the same headline** — closing does not reset progress.

## QA

**Environment:** staging 1.19.93, `wp eval` clean (no PHP fatal), SiteGround cache purged after each install. PHP lint clean on both changed templates (`php -l` on the server). `node --check` clean on both changed JS files. No console errors on the homepage, canonical quiz page, or any blog post visited.

- **12/12 Q2 answers** produce the correct headline, supporting text, CTA label, destination, `quiz_audience`, `quiz_intent` and UTM string.
- **Progress labels** correct on both steps; **Back** returns to step 1 with focus on the previously chosen answer (`aria-checked="true"`); **Start over** clears all four `aria-checked` values, restores the header and focuses the first answer.
- **No duplicate IDs** on the homepage (which renders the quiz twice — inline plus modal — by existing design), the canonical quiz page, or a blog post.
- **Breakpoints — result dialog needs no internal scrolling at any width, page has no horizontal overflow at any width:**

| Width × height | Page overflow | Result needs internal scroll | CTA fully in viewport |
|---|---|---|---|
| 320 × 640 | 0 | no | yes |
| 375 × 667 | 0 | no | yes |
| 390 × 844 | 0 | no | yes |
| 430 × 932 | 0 | no | yes |
| 768 × 1024 | 0 | no | yes |
| 1024 × 768 | 0 | no | yes |
| 1280 × 800 | 0 | no | yes |
| 1440 × 900 | 0 | no | yes |

  At 320×640 and 375×667 the **question** steps do scroll inside the dialog (four answers plus heading in a short viewport). That is unchanged from before and does not affect the result screen. Desktop `-15px` readings are the scrollbar, not overflow.
- **Contrast (measured, homepage navy section, after fix):** question 11.48:1, lead / progress / secondary actions / result text 9.34:1, result headline 11.48:1, answer buttons 14.35:1. Canonical page (cream): question 12.86:1, progress 6.14:1.
- **Reduced motion:** both `@media (prefers-reduced-motion: reduce)` blocks confirmed present and parsed in the live stylesheets. The scroll restore never animates, by construction. *Not* verified under an actual reduced-motion browser profile — this environment cannot emulate the preference.
- **ARIA:** launcher `aria-haspopup="dialog"`, `aria-controls` resolves, `aria-expanded` toggles true/false; dialog `role="dialog"` + `aria-modal="true"`; `aria-labelledby` resolves to "Find Your Adventure quiz". Q2 radiogroup now gets an `aria-label` from the prompt (it had none before). The advance chevron is `content:""` with borders — nothing for a screen reader to announce.
- **Analytics:** event names unchanged (`quiz_viewed`, `quiz_started`, `quiz_q1_answer`, `quiz_q2_answer`, `quiz_completed`, `quiz_destination_click`, `quiz_abandoned`, `quiz_restarted`, `homepage_quiz_started`, `quiz_modal_opened`, `quiz_modal_closed`). `quiz_intent` added to the `quiz_completed` payload and as a `data-bhp-quiz-intent` attribute on the result CTA (additive). No PII, no email capture in the quiz. The quiz's own events are consent-gated and correctly did **not** push on staging (`analyticsOn: false`) — that is the deliberate analytics-activation state, not a defect.
- **Regressions checked:** canonical `/find-your-adventure/` page (1 quiz, no launcher, no "Keep browsing" button, routes correctly with `utm_content=quiz_page`), homepage inline quiz (intro gate, new copy, dark section), blog post, product page, informational page, organizations landing page (quiz correctly suppressed there).

## Not verified / open

1. **Anchor scroll to `#contact` could not be observed.** The anchor exists and carries the right section, and the URL is correctly formed, but this automation browser never scrolls to a hash target — reproduced with a pre-existing, unrelated anchor (`#find-your-adventure`) on the homepage, so it is an environment limitation, not a site defect. Needs a real-browser spot check.
2. **Focus-trap leak to WPConsent (pre-existing).** Tab from the last control in the dialog moves focus to `#wpconsent-container`. The trap handler does run (`defaultPrevented === true`); WPConsent then takes focus. Reproduced with both a synthetic event and a real Tab press, and **identically on production 1.19.91** — not a regression from this release. Logged as a separate task.
3. **Screenshots unavailable.** The screenshot tool timed out repeatedly (renderer confirmed alive). All visual claims here come from measured geometry and computed styles, not from an image.
4. **`/find-your-adventure/` now has no internal inbound link** anywhere on the site, since the modal link was the only one. Deliberate per the brief; worth deciding whether it should be linked from the footer resource cluster.

## Guardrails observed

Frozen Audience Routing Constitution unchanged; no retailer route; no new funnel; four audience destinations preserved; no email capture in the quiz; no Mailchimp / coupon / WooCommerce / lead-magnet / production-content changes; `.claude/settings.local.json` untouched; auto-open timing and eligibility untouched; no fabricated statistic or claim; **production untouched.**

---

# Second pass — conversion refinements (staging 1.19.95)

**Date: 2026-07-29, same day.** Staging **1.19.93 → 1.19.95** (1.19.94 was an intermediate build, superseded within the pass). **Production untouched at 1.19.91; no production approval requested or given.** Backup before the first install of this pass: `~/bhp-staging-theme-backup-1.19.93-20260729-183736.tar.gz`.

## Files changed (5 + docs)

| File | Change |
|---|---|
| `template-parts/quiz/audience-quiz.php` | Supporting line, Q1 prompt, educator answer 3 + its result, parent "less resistance" body, all 12 result CTA labels + 4 route-level fallbacks, intro start-button label |
| `template-parts/components/quiz-entry-cta.php` | Launcher label to "Find My Best Next Step" |
| `assets/css/audience-quiz.css` | Intentional gold/navy primary-CTA treatment with real hover/focus-visible/active states; 44px option floor |
| `assets/css/quiz-modal.css` | Dialog no longer scrolls (single inner scroll region); close button pinned + navy focus ring; modal-scoped compact heading/spacing |
| `assets/js/quiz-modal.js` | *(working-tree consent fix preserved — see below; scroll-restoration code unchanged)* |
| `style.css` | Version bump only |

**Preserved, not authored here:** `assets/js/quiz-modal.js` carried an uncommitted `hasVisibleConsentUI()` rewrite when this pass began. It was kept and validated rather than overwritten. One exploratory edit to it was made and then fully reverted; the file's final state is the author's, plus nothing.

## Copy implemented

- Eyebrow (kept): `2 QUESTIONS · ABOUT 30 SECONDS`
- Headline (kept): `Where Should Your Adventure Begin?`
- Supporting: `No wrong answers—tell us who you're here for and what would feel like a win. We'll match you with the most useful free resource and next step.`
- Q1: `What would you like help with today?` — four answers unchanged
- Q2 prompts: all four unchanged
- Educator answer 3: `Vocabulary and discussion support` becomes **`History and vocabulary connections`**; result `Connect the story to history and language.` / `The free Adventure Learning Toolkit connects the series to history and vocabulary, alongside geography, science, and discussion.`
- Parent "less resistance" body: `The free Reluctant Reader Adventure Kit gives ages 6–9 a 20-minute, low-pressure way into the story—an easy first win to share.`
- Launcher + standalone start button: **`Find My Best Next Step`**
- Result CTAs: parent `Get My Free Adventure Kit`; educator `Get the Free Classroom Toolkit`; organization event/program `Get Community Reading Resources`; organization partnership `Explore Group Orders & Partnerships` (kept); gift `Get the Free Gift Guide`
- Kept verbatim: `YOUR BEST NEXT STEP`, `Keep browsing this page`, `Start over`, `Question 1 of 2`, `Question 2 of 2`

`quiz_intent` values are unchanged, including `vocabulary_discussion` on the reworded educator answer. `ANALYTICS/EVENT_MATRIX.md` contains no quiz entries, so no canonical analytics document requires a value migration.

## The gold CTA conflict, resolved

`audience-quiz.css` declared `background: var(--bq-green); color: #fff` for the result button. It never rendered: `style.css` line ~3844 declares `.btn-primary { background: var(--expedition-gold) !important; color: var(--expedition-navy) !important }`. The same `!important` also defeats style.css's own `.btn-primary:hover` at line ~633 — so **before this change the primary CTA had no working hover state anywhere on the site**, and the quiz's gold appearance was an accident of a rule it never asked for.

Resolution: the quiz declares its intent at `.bhp-quiz` scope. `!important` is required to participate against the sitewide `!important`, and `.bhp-quiz .btn-primary` (0,2,0) outranks `.btn-primary` (0,1,0) among important declarations. Existing expedition tokens only — no new palette.

| State | Background | Text | Measured contrast |
|---|---|---|---|
| normal | `--expedition-gold` #c4a15c | `--expedition-navy` #071522 | **7.60:1** (AA + AAA normal) |
| hover | `--expedition-focus` #d8bd7d | navy | **10.19:1** |
| focus-visible | `--expedition-focus` | navy | 10.19:1, plus a 3px navy outline (offset 3px) |
| active | back to `--expedition-gold`, `translateY(1px)` + inset shadow | navy | 7.60:1 |

The focus ring is navy rather than the sitewide `button:focus-visible { outline: … var(--expedition-focus) }`, which is gold and would be nearly invisible on a gold button — a real focus-visibility gap this scope closes. Verified: `.btn-primary` elements outside `.bhp-quiz` are unchanged.

## Modal compaction

The modal headline is an `<h2>` and was inheriting `body:not(.home) h2 { font-size: clamp(2.25rem, 4.5vw, 4rem) }`. Scoped override at `.bhp-quiz-modal .bhp-quiz .bhp-quiz__heading` (0,3,0 beats the sitewide 0,1,2 — per `DECISIONS.md`'s specificity rule, no `!important` needed): `clamp(1.9rem, 3.4vw, 3.25rem)`, line-height 1.08.

| Viewport | Headline before | Headline after | Dialog height before to after |
|---|---|---|---|
| 1920×1080 | 64px | **52px** | — to 553px |
| 1440×900 | 64px | **49px** | 584px to **546px** |
| 1366×768 | 61px | **46px** | 579px to **541px** |
| 375×667 | — | **30px** | — |

Standalone presentations are untouched — measured on `/find-your-adventure/` after deploy: headline still **64px**, quiz padding-top still 48px, `overflow: visible`.

## Two layout defects found by measurement and fixed

1. **Content overlapped the close button.** At 1440×900 the eyebrow's box ran under the close button (`contentOverlapsClose: true` on 1.19.93). Fixed structurally by clearing the close button's footprint; now `false` at every viewport tested.
2. **The close button scrolled out of view.** The dialog was the scroll container and the close button was `position: absolute` inside it, so on a short viewport it scrolled away — measured on 1.19.93 at 1024×560: dialog top 16, close top **9** (above the dialog, clipped). The dialog now clips instead of scrolling and `.bhp-quiz` is the single scroll region. Re-measured at 1024×420 (content genuinely scrolls, `scrollTop 90`): close top **26 before and after**, still within the dialog and on screen. Scroll-region count: **1** (`bhp-quiz`), never nested.

## Working-tree consent fix — preserved and validated

`hasVisibleConsentUI()` previously checked `consent.children.length && offsetWidth && offsetHeight`, which can never be true: WPConsent renders into an **open shadow root** on a `position: fixed` host whose own box is 0×0. The rewrite reads the shadow root and scopes to `#wpconsent-banner-holder, #wpconsent-preferences-modal`.

Validated live rather than assumed. WPConsent leaves a persistent **44×44 floating "reopen preferences" button** rendered in that same shadow root forever after a choice is made; a broader "any rendered descendant" test would have silently suppressed quiz auto-open for every visitor who has ever answered the banner. Measured after choosing *Reject Nonessential* on staging 1.19.95: both overlay containers 0×0, floating button rendered 44×44, **guard returns false**, and auto-open then fired normally.

## Validation performed (all against deployed staging 1.19.95)

| Check | Result |
|---|---|
| `node --check` on both changed JS files | **PASS** |
| `php -l` on both changed PHP templates | **PASS** (no syntax errors) |
| `wp eval` after install | **PASS** (`php_ok`, no fatal) |
| Server-side render of the quiz template part | **PASS** — 11,239 bytes, config/dismiss/new-prompt present, `error_get_last()` unchanged (no new notice/warning) |
| PHP error logs grepped for quiz/Warning/Notice/Fatal | **PASS** — no entries |
| Repository test suites for quiz/modal | **N/A — none exist.** `tests/` has 19 suites; none cover the quiz or modal (verified by grep). No canonical doc identifies one. |
| All 4 Q1 routes × every Q2 answer (12 results) | **PASS** — 12 distinct titles/bodies/CTAs, correct audience, intent, destination, UTMs |
| Result CTA label + destination per answer | **PASS** (partnership answer keeps `#contact`) |
| Back from Q2 | **PASS** — returns to Q1, focus on the previously chosen answer (`aria-checked="true"`), header restored |
| Start over from result | **PASS** — Q1 restored, all four `aria-checked` cleared, focus on first answer |
| Focus management on advance | **PASS** — Q1 to first Q2 option (`role="radio"`), Q2 to result `<h3>`; Q2 radiogroup gets `aria-label` from the prompt |
| Visible focus states | **PASS** — close button `:focus-visible` = solid 3px navy, **16.51:1** against the cream card; quiz CTA focus ring navy 3px |
| Escape / backdrop / X / Keep browsing dismissal | **PASS** — all four close, body unlocks, focus returns to launcher |
| Exact page-position restoration, 4 methods × 4 positions on a 9,954px page | **PASS — 0px delta on both axes, every case** |
| Same after an **automatic** open | **PASS** — auto-opened at y=3027 (launcher 5,460px below), closed to y=3027, delta 0 |
| Progress preserved on close/reopen | **PASS** — result step and headline intact |
| Body scroll lock | **PASS** — applied while open, removed on close in all cases |
| Console errors | **PASS** — none on blog post, homepage, or canonical quiz page |
| Horizontal overflow | **PASS** — 0 at every viewport (desktop `-15px` readings are the scrollbar) |
| Duplicate IDs | **PASS** — none on homepage (renders the quiz twice by existing design), canonical page, or blog post |
| Unrelated sitewide buttons | **PASS** — `.btn-primary` outside `.bhp-quiz` unchanged (gold bg, navy text, original 1px borders) |
| Reduced-motion rules present in deployed CSS | **PASS** — 3 blocks (option affordance, primary CTA, modal) |
| Standalone quiz regression (`/find-your-adventure/`) | **PASS** — 64px headline, 48px padding, `overflow: visible`, no launcher, no dismiss button, 1 quiz instance |
| Homepage quiz regression | **PASS** — new copy, gold start button 7.55:1, 1.19.93 contrast fixes intact (lead/progress 9.34:1, question 11.48:1) |

### Desktop acceptance criteria

| Viewport | Q1 + 4 answers + close visible without internal scroll | Content overlaps close | Q2 answers + Back fit | Scroll regions |
|---|---|---|---|---|
| 1920×1080 | **yes** | no | yes | 0 |
| 1440×900 | **yes** | no | yes | 0 |
| 1366×768 | **yes** (nothing clipped) | no | yes | 0 |

### Mobile / short-screen

| Viewport | Page overflow | Headline | Option min height | Close visible while scrolled | Scroll regions |
|---|---|---|---|---|---|
| 320×640 | 0 | 30px | 81px | **yes** (top 22, unchanged) | 1 |
| 375×667 | 0 | 30px | 56px | **yes** (top 22, unchanged) | 1 |
| 430×932 | 0 | 30px | 56px | yes (no scroll needed) | 0 |
| 768×1024 | 0 | 30px | 81px | yes (no scroll needed) | 0 |
| 1024×420 (short) | 0 | 46px | 81px | **yes** (top 26 before and after scrolling) | 1 |

## Not verified — environment limitations, reported rather than assumed

1. **CSS `:hover` painting could not be observed.** A real pointer hover was performed (`element.matches(':hover')` returned true) but the computed background did not change — and a **control test** injecting a brand-new last-in-document `!important` `:hover` rule with a sentinel colour *also* failed to apply. This browser does not resolve `:hover` styles. The hover/active rules are therefore verified as **declared in the deployed stylesheet with winning specificity**, not as observed pixels. `:focus-visible` *does* resolve here and was observed working.
2. **Real `Tab` traversal is not driven by this browser.** Control test: with the modal closed and focus on a page link, a real `Tab` key press did not move focus either. Keyboard operation is therefore verified through focus-management behaviour (JS-driven, fully observed), the trap handler receiving Tab and calling `preventDefault()` at boundaries, and `getFocusable()` returning exactly the four visible controls in DOM order — **not** through an end-to-end keyboard walk.
3. **Screenshots unavailable** (tool times out; renderer confirmed alive). All visual claims here are measured geometry and computed styles.
4. Carried over from the first pass: anchor scroll to `#contact` still cannot be observed in this browser (fails for unrelated pre-existing anchors too), and reduced-motion rendering under a real OS preference is unverified.

## Documentation conflict flagged, not silently resolved

`DECISIONS.md`'s "Audience Routing Constitution" and `ENGINEERING/FUNNEL_CONSTITUTION.md` both still state the routing quiz is "explicitly not part of any current sprint" and "must not be built" until every audience funnel is production-complete. That text is **stale, not violated**: the quiz was built, approved by Andrew, and deployed to production on 2026-07-20 as part of theme 1.19.91 (see `CHANGELOG.md` and `PROJECT_STATE.md`). Per `CLAUDE.md` the repo and live systems win over a stale snapshot, and per the Constitution's own Amendment Process frozen text is not silently edited. A dated reconciliation note has been added to both documents recording the live state; no frozen policy was altered, and nothing in this pass touched routing architecture, destinations, or the funnel design.

## Guardrails observed

Two-question routing architecture unchanged; no questions added; no destinations changed; no funnel redesign; no statistic added; Frozen Audience Routing Constitution untouched; analytics event names and required attributes unchanged; consent behaviour unchanged; auto-open timing/eligibility unchanged; scroll restoration preserved and regression-tested; existing uncommitted work preserved; `.claude/settings.local.json` untouched; nothing committed, pushed, merged, or opened as a PR; **production untouched.**

---

# Third pass — internal scroll-state correction (staging 1.19.96)

**Date: 2026-07-30.** Staging **1.19.95 → 1.19.96**. **Production untouched at 1.19.91; no production approval requested or given.** Backup taken before install: `~/bhp-staging-theme-backup-1.19.95-20260730-000922.tar.gz`. Rollback: reinstall that tarball's directory.

## Root cause

`showStep()` in `assets/js/audience-quiz.js` swaps which step element carries the `hidden` attribute, but never touched the scroll container. Since the second pass (1.19.95) that container is `.bhp-quiz` itself — `quiz-modal.css` gives `.bhp-quiz-modal .bhp-quiz` `overflow-y: auto` so the dialog can stop scrolling and keep its close button pinned. The container therefore **retained its `scrollTop` across every screen change**.

The visible consequence matches the report exactly: at 1024×420 the third and fourth Question 1 answers sit below the fold inside the modal (measured: "Create a reading program, event, or partnership" has `optionVisibleBeforeScroll: false`), so reaching them *requires* scrolling — and that scroll position then carried into Question 2.

**Reproduced on the 1.19.95 baseline before writing any fix**, 1024×420:

| Route | Q1 scrollTop before select | Q2 scrollTop after select | Q2 starts at top | Eyebrow clipped |
|---|---|---|---|---|
| organization | 90 | **90** | no | **yes — 38px above the container top** |
| gift | 90 | **83** (clamped) | no | **yes — 31px above** |
| educator | 90 | **90** | no | **yes — 38px above** |
| parent | 90 | **90** | no | **yes — 38px above** |

All four routes were affected, not only the two named in the report. The result screen appeared to reset (`scrollTop 0`) but only **incidentally** — it is short enough that the browser clamps `scrollTop` when `scrollHeight <= clientHeight`. On a shorter viewport or with longer result copy it would have carried too.

## Files changed

| File | Change |
|---|---|
| `assets/js/audience-quiz.js` | `getScrollContainer()` (bounded, memoised), `resetInternalScroll()`, call added at the end of `showStep()`, and `focusQuietly()` hardened to preserve the container's intended position on the non-`preventScroll` fallback path |
| `style.css` | Version bump only, 1.19.95 → 1.19.96 |

Nothing else was touched. `quiz-modal.js`, both stylesheets and both PHP templates are byte-identical to 1.19.95.

## How the internal reset was implemented

Centralised in the **existing** screen-transition function rather than duplicated across click handlers, as instructed. `showStep()` now ends with:

```js
root.classList.toggle('bhp-quiz--result', String(name) === 'result');
resetInternalScroll();
```

`resetInternalScroll()` sets `container.scrollTop = 0` immediately and re-asserts once inside `requestAnimationFrame` — the outgoing step leaves the flow in the same frame, so the container's `scrollHeight` changes after the first write, and a late clamp could otherwise leave the new screen part-scrolled.

The container is resolved by `getScrollContainer()`: a walk from the quiz root upward, **bounded by `.bhp-quiz-modal__dialog`**, returning the first ancestor whose computed `overflow-y` is `auto` or `scroll`, and falling back to the root. The bound is deliberate — the walk can never reach the document scroller, so this code is structurally incapable of moving the page. It is memoised and resolved lazily (at `DOMContentLoaded` the modal is still `hidden`; only the declared overflow is read, never a measurement).

All five call sites go through it: intro→Q1, Q1→Q2, Q2→result, Back, Start over.

**`window.scrollTo()` is not called anywhere in this file** — verified by grep; the only match is the comment saying so.

## How focus was prevented from undoing the reset

`focusQuietly()` already used `focus({ preventScroll: true })`. The gap was the `catch` fallback for browsers without `FocusOptions`: a plain `focus()` scrolls the nearest scrollable ancestor to reveal the target, which on a scrollable Question 2 would have re-scrolled the container immediately after the reset. It now captures `scrollTop`/`scrollLeft` before focusing and restores them if focus moved them:

```js
var savedTop = container ? container.scrollTop : 0;
try { el.focus({ preventScroll: true }); } catch (e) { el.focus(); }
if (container && container.scrollTop !== savedTop) { container.scrollTop = savedTop; }
```

Ordering is also correct by construction: `showStep()` resets, and only then does the caller move focus — and the rAF re-assert lands after focus as a second guarantee.

## Test results — deployed staging 1.19.96

All four routes tested at every viewport, deliberately scrolling to a lower Question 1 answer before selecting it.

| Viewport | Q1 genuinely scrollable | Q2 scrollTop | Q2 starts at top | Anything clipped | Result scrollTop | Back | Start over | Close reachable | window scrollY |
|---|---|---|---|---|---|---|---|---|---|
| 1440×900 | no (modal fits) | 0 | yes | no | 0 | 0 | 0 | yes | unchanged |
| 1366×768 | no (modal fits) | 0 | yes | no | 0 | 0 | 0 | yes | unchanged |
| **1024×420** | **yes (89)** | **0** | **yes** | **no** | 0 (from 107) | 0 | 0 | yes | unchanged |
| 390×844 | no (modal fits) | 0 | yes | no | 0 | 0 | 0 | yes | unchanged |
| **390×600** (added) | **yes (73)** | **0** | **yes** | **no** | 0 (from 27–31) | 0 | 0 | yes | unchanged |

**Honest note on coverage:** at 1440×900, 1366×768 and 390×844 the modal fits entirely, so nothing scrolls internally and the defect cannot manifest. Those three rows confirm *no regression*; they do **not** exercise the fix. The two rows that actually exercise it are **1024×420** (the required short desktop) and **390×600**, which was added specifically because the required 390×844 turned out to be a trivial pass on mobile.

### Genuine-interaction verification (real browser scroll + real click, not scripted state)

1024×420, using the browser's own `scroll_to` inside the modal followed by a real left-click on the option:

| Selected answer | Q1 scrollTop before click | Q2 scrollTop after | Eyebrow | Headline | Lead | Progress | Question |
|---|---|---|---|---|---|---|---|
| "Create a reading program, event, or partnership" | 89 | **0** | visible, +52px | visible, +82px | visible, +126px | visible, +199px | visible, +224px |
| "Choose a meaningful gift for a child" | 89 | **0** | visible, +52px | visible, +82px | visible, +126px | visible, +199px | visible, +224px |

Offsets are measured from the scroll container's top edge; every element reported `fullyVisible: true`. Focus landed on the first Question 2 answer, inside the dialog, on a visible element, in both cases.

### Page-position regression (the 1.19.95 fix must survive)

5,053px page, launcher 694–3,094px below the fold:

| Dismissal | before | after | Δy | Δx | focus back on launcher | body unlocked |
|---|---|---|---|---|---|---|
| X close button | 900 | 900 | **0** | 0 | yes | yes |
| Escape | 2100 | 2100 | **0** | 0 | yes | yes |
| backdrop | 3300 | 3300 | **0** | 0 | yes | yes |
| Keep browsing this page | 1650 | 1650 | **0** | 0 | yes | yes |

Window `scrollY` was also confirmed unchanged *during* every internal transition, on every viewport — the whole QA matrix asserts it per route and overall.

### Accessibility

| Check | Result |
|---|---|
| Focusable set per screen | Q1 **5**, Q2 **5**, result **4** — all inside the dialog, all visible |
| Any focusable on hidden content | **none** at any step |
| Tab at forward boundary | trapped (`defaultPrevented`) on Q1, Q2 and result |
| Shift+Tab at backward boundary | trapped on Q1 |
| Focus after each transition | Q1→first Q2 answer; Q2→result `<h3>`; Back→previously chosen answer; Start over→first answer — all visible, all in-dialog |
| Focus movement re-scrolling the modal | no — container `scrollTop` remained 0 after focus in every case |
| Escape from the result screen | closes the modal |
| Close button reachable | present in the focusable set at every stage |

### Preserved / unchanged (spot-checked live)

Questions and answers, route logic, result recommendations, emotional copy, CTA wording, gold `rgb(196,161,92)` on navy `rgb(7,21,34)`, destinations with full UTM strings (including the organization `#contact` anchor), auto-open, consent behaviour, no duplicate IDs, no horizontal overflow, **zero console errors** on the blog post, homepage and canonical quiz page. Standalone renders re-measured: `overflow-y: visible`, root `scrollTop` stays 0, page `scrollY` unchanged at 400 (canonical page) and 300 (homepage) through every transition — the reset is a verified no-op there.

## Not done / limitations

1. **Screenshots could not be produced.** The `computer{action:"screenshot"}` tool timed out again — in the existing tab and in a fresh one — consistent with the project-long failure recorded in `KNOWN_ISSUES.md`. The requested images of the top of Question 2 after the organization and gift selections are therefore **not** included; the genuine-interaction table above is exact DOM geometry captured at those moments, and is labelled as measurement, not imagery. `scroll`/`hover` by coordinate are also unavailable because they depend on a cached screenshot; element-`ref` interaction was used instead, which is why the real-click verification was still possible.
2. **Reopening the modal does not reset internal scroll.** Deliberate and out of the stated scope: reopening does not change the displayed view, so the visitor resumes exactly where they left off. Page position on open/close is unaffected either way.
3. A separate background session was editing `assets/js/quiz-modal.js` while this pass ran. That file was **not** modified here and its shipped checksum was `9376b3e6cdfdda1a173c8aca3ec594ea`. If that session lands changes, the deployed build will need rebuilding to include them.

## Guardrails observed

Smallest safe change (one behavioural file); behaviour centralised in the existing transition function rather than duplicated; no `window.scrollTo()`; underlying page scroll untouched; no questions, routes, results, copy, CTA wording, colours, dimensions, analytics, destinations, auto-open or consent behaviour changed; existing uncommitted work preserved; `.claude/settings.local.json` untouched; `git diff --check` clean; nothing committed, pushed, merged or opened as a PR; **production untouched.**

---

# Reconciliation + release-candidate verification (staging 1.19.96, no change required)

**Date: 2026-07-30.** Purpose: resolve the outstanding `assets/js/quiz-modal.js` working-tree question flagged at the end of the third pass, then confirm one authoritative staging candidate. **Outcome: no code change and no version bump were needed — 1.19.96 already IS the reconciled candidate.** Production untouched at 1.19.91.

## The pending `quiz-modal.js` edit

Provenance established by checksum across every available artifact (all LF-normalised):

| Copy | md5 |
|---|---|
| Local working tree | `9376b3e6cdfdda1a173c8aca3ec594ea` |
| Deployed on staging 1.19.96 | `9376b3e6cdfdda1a173c8aca3ec594ea` |
| Inside the 1.19.96 deploy ZIP artifact | `9376b3e6cdfdda1a173c8aca3ec594ea` |
| Inside the **1.19.95** backup tarball | `9376b3e6cdfdda1a173c8aca3ec594ea` |
| Git HEAD (`3e02529`, = production 1.19.91) | `4e31e25dfe30c2b9b29f9ccfd3ada322` |

The file has been identical since before both the 1.19.95 and 1.19.96 deploys. Its mtime is `2026-07-29 12:39:51`, which **predates both**, and it was unchanged nine hours later at the time of this reconciliation — **no process is still writing to it.**

### Semantic content (HEAD → current), three logical changes

1. **Page-scroll preservation.** `openScrollX/Y` captured in `openModal()`; `closeModal()` focuses with `focus({preventScroll:true})` and a plain-`focus()` fallback, then re-asserts the captured coordinates immediately and once more on the next animation frame; `scrollToInstant()` suppresses the sitewide `html{scroll-behavior:smooth}` for the duration of the jump so the restore is instant rather than animated.
   *Authorisation:* explicitly specified in the 1.19.93 task brief; recorded in `CHANGELOG.md` (2026-07-29) and this document's first pass.
2. **"Keep browsing this page" binding.** Binds `[data-bhp-quiz-dismiss]` to `closeModal('keep_browsing')` so the result-screen dismissal uses the same close path as every other route.
   *Authorisation:* specified in the same 1.19.93 brief; recorded in the same places.
3. **WPConsent overlay detection** — `isRendered()`, `CONSENT_OVERLAY_SELECTOR = '#wpconsent-banner-holder, #wpconsent-preferences-modal'`, `hasVisibleConsentUI()`, replacing the old inline `consent.children.length && offsetWidth && offsetHeight` test inside `hasActiveOverlay()`. The old test could never return true (WPConsent renders into an **open shadow root** on a `position:fixed`, 0×0 host), so the quiz auto-opened on top of a visible cookie banner — two competing `role="dialog"` overlays, which is what made Tab appear to escape the quiz's focus trap.
   *Authorisation:* this is the deliverable of background task `task_8f952193` ("Fix quiz modal focus-trap leak to WPConsent"), **started by Andrew** after it was flagged at the end of the first pass. Independently documented in project auto-memory (`project-bhp-quiz-modal-consent-collision`, `reference-wpconsent-shadow-dom`, both dated 2026-07-29), including the root cause, the deliberate decision *not* to trap focus harder (that would lock a visitor away from the consent banner), and an explicitly accepted side effect: the `attemptAutoOpen()` retry loop (5 tries, 1s apart) now genuinely engages for consent, so if a visitor leaves the banner up longer than ~5s the quiz will not auto-open on that page view.

### Assessment

| Question | Finding |
|---|---|
| Complete? | **Yes.** Every symbol is defined and referenced (`isRendered` 3, `CONSENT_OVERLAY_SELECTOR` 2, `hasVisibleConsentUI` 2, `scrollToInstant` 3, `openScrollX/Y` 4 each, `keepBrowsingBtn` 3). The superseded inline check was removed cleanly, no orphan references. |
| Debugging artifacts? | **None** — no `console.*`, `debugger`, TODO/FIXME/WIP in any changed file. |
| Approved / documented? | **Yes**, all three changes — two by explicit task brief, the third by an Andrew-started task plus two auto-memory records. |
| Conflicts with the 1.19.96 scroll correction? | **No.** The 1.19.96 fix lives entirely in `audience-quiz.js` (`showStep()` → `resetInternalScroll()`, `focusQuietly()` hardening) and governs the modal's **internal** container. `quiz-modal.js` governs the **page** scroll and consent detection. Verified by grep: `quiz-modal.js` contains no reference to `showStep`, `resetInternalScroll`, `getScrollContainer`, `focusQuietly` or the step markup. They were also QA'd together at 1.19.95 and 1.19.96. |
| Still being modified? | **No** — mtime stable for 9 hours and predating both deploys. |

**Disposition: integrated.** It was already integrated and deployed; nothing needed to be merged, excluded, quarantined or discarded, and no one's work was overwritten.

## Release-candidate verification

Full 143-file comparison, local intended source set vs deployed staging (LF-normalised):

- **143 files matched, 0 differing, 0 local-only, 0 deployed-only.**
- Excluded from the set by construction: `.claude/settings.local.json`, `docs/`, `tests/`, backups, temp files. (`tests/` is confirmed absent from the deployed theme.)

Because the deployed build already equals the intended source exactly, **the version stays 1.19.96** and no redeploy was performed — per the rule "if no code changes are needed, keep 1.19.96."

### Live-served asset verification (the important one)

An initial `curl` check appeared to show a mismatch on `audience-quiz.js`. **It was a false alarm and is worth recording:** SiteGround's edge security answers non-browser clients with `HTTP 202` and a ~292-byte challenge body (`Set-Cookie: nevercache-…`, `X-Proxy-Cache-Info: DT:1`) instead of the asset — the same mechanism that makes the WP REST API return 403 to `curl` on this host. **`curl` cannot be used to verify served assets here.**

Re-verified from the real browser (`fetch()` on the actual `<script src>`), which is the path a visitor uses:

| Asset | HTTP | sha256(first 32) served | sha256(first 32) local | Match |
|---|---|---|---|---|
| `quiz-modal.js?ver=1.19.96` | 200 | `1057ebc97f2fc45b558ece3bc8dc67b5` | `1057ebc97f2fc45b558ece3bc8dc67b5` | **exact** |
| `audience-quiz.js?ver=1.19.96` | 200 | `a0e369cd99d6ee2aed61448438ed4fcc` | `a0e369cd99d6ee2aed61448438ed4fcc` | **exact** |

Feature markers in the served bytes confirm the two fixes are live and correctly separated: `audience-quiz.js` carries `resetInternalScroll` + `getScrollContainer` and none of the modal symbols; `quiz-modal.js` carries `CONSENT_OVERLAY_SELECTOR` + `scrollToInstant` + the `data-bhp-quiz-dismiss` binding and none of the step symbols. `style.css` serves at `?ver=1.19.96`; active theme reports **1.19.96**.

### Checks run

`node --check` on both JS files — pass. `php -l` on both changed templates — pass. `git diff --check` — pass. Server-side `get_template_part()` render — 11,239 bytes, config present, `error_get_last()` unchanged (no new PHP notice/warning). PHP error logs — no quiz-related entries. SiteGround caches purged (assets + dynamic; file cache reports disabled, as it has on every deploy this project).

**Project test suites: not applicable.** The repo has 19 `wp eval-file` suites; **0** reference the quiz or the modal (verified by grep), and `tests/` is not part of the deployed theme set. No canonical document identifies a quiz/modal suite. Nothing was skipped that exists.

## Phase 3 regression — combined candidate, live staging 1.19.96

Every viewport × all four routes. At the genuinely scrolling viewports Question 1 was scrolled to the bottom before each answer was selected.

| Viewport | Q1 genuinely scrolls | Q1 scrollTop before select | Q2 scrollTop | Clipped elements | Result | Back | Start over | Tab / Shift+Tab trapped | Focusables (all visible, in-dialog) | window scrollY | Verdict |
|---|---|---|---|---|---|---|---|---|---|---|---|
| 1440×900 | no (modal fits) | 0 | **0** | none | 0 | 0 | 0 | yes / yes | 5 | unchanged | **PASS** |
| 1366×768 | no (modal fits) | 0 | **0** | none | 0 | 0 | 0 | yes / yes | 5 | unchanged | **PASS** |
| **1024×420** | **yes** | **89** | **0** | none | 0 (from 107) | 0 | 0 | yes / yes | 5 | unchanged | **PASS** |
| 390×844 | no (modal fits) | 0 | **0** | none | 0 | 0 | 0 | yes / yes | 5 | unchanged | **PASS** |
| **390×600** | **yes** | **73** | **0** | none | 0 (from 27–31) | 0 | 0 | yes / yes | 5 | unchanged | **PASS** |

"Clipped elements" checks eyebrow, headline, lead, progress label, question **and first answer** against the container bounds — zero clipped anywhere. Window `scrollY` was held at 1200 throughout and asserted per route and overall.

**Coverage caveat, stated rather than glossed:** at 1440×900, 1366×768 and 390×844 the modal fits entirely, so those rows confirm *no regression* but cannot exercise the fix. The rows that genuinely exercise it are **1024×420** and **390×600**.

### Element geometry at the two exercising viewports (top of Question 2, measured at the moment of transition)

Offsets are from the scroll container's top edge; every element reported `visible: true`.

| | 1024×420, organization | 390×600, gift |
|---|---|---|
| Q1 scrollTop before select | 89 | 27 |
| **Q2 scrollTop after** | **0** | **0** |
| window scrollY | 2500 (unchanged) | 2500 (unchanged) |
| eyebrow "2 QUESTIONS · ABOUT 30 SECONDS" | +52px | +48px |
| headline "Where Should Your Adventure Begin?" | +82px | +78px |
| lead "No wrong answers—…" | +126px | +150px |
| progress "Question 2 of 2" | +199px | +250px |
| question | +224px ("What are you hoping to create?") | +275px ("What do you want the gift to spark?") |
| first answer | +266px | +317px |
| close button (rel. dialog) | +10px, visible | +10px, visible |

### Dismissal / page-position regression (1024×420, 5,053px page)

| Method | before | after | Δy | Δx | jumped to the quiz CTA section (y≈3968)? | focus back on launcher | body unlocked |
|---|---|---|---|---|---|---|---|
| X close button | 700 | 700 | **0** | 0 | no | yes | yes |
| Escape | 1900 | 1900 | **0** | 0 | no | yes | yes |
| backdrop | 3200 | 3200 | **0** | 0 | no | yes | yes |
| Keep browsing this page | 2500 | 2500 | **0** | 0 | no | yes | yes |

Launcher sat 791–3,291px below the viewport in these runs — the condition that originally caused the jump.

### Preserved behaviour (explicitly re-checked, not assumed)

- **Resume-where-left-off on reopen: unchanged.** Reopening after "Keep browsing this page" still shows the result step with the same headline. Not altered — testing gave no reason to.
- Result CTAs and destinations, with full UTM strings, including the organization `#contact` anchor. Gold `rgb(196,161,92)` on navy `rgb(7,21,34)` on every result CTA and the homepage start button. Approved copy, route logic and result recommendations byte-identical (no template or CSS file changed in this pass).
- **Standalone renders unaffected:** `/find-your-adventure/` — 1 quiz, no launcher, no dismiss button, `overflow-y: visible`, root `scrollTop` 0, page `scrollY` held at 500 through every transition. Homepage — start button "Find My Best Next Step", `overflow-y: visible`, page `scrollY` held at 350, result renders correctly. No duplicate IDs, no horizontal overflow on either.
- **Zero console errors** on the blog post, canonical quiz page and homepage.

## Screenshots — not produced

The `computer{action:"screenshot"}` tool timed out again at **both** requested viewports (1024×420 and 390×600), and previously in a fresh tab. This is the project-long limitation recorded in `KNOWN_ISSUES.md`. **No screenshots exist for this or any prior pass**; the geometry tables above are exact DOM measurements captured at the moment of each transition, and are presented as measurement, not imagery. Coordinate-based `hover`/`scroll` are also unavailable because they depend on a cached screenshot — element-`ref` interaction was used instead, which is how the earlier real-click verification was still possible.

### Re-verification pass (2026-07-30, later)

The reconciliation above was re-run from scratch against the live site. **Nothing had changed** and no action was required.

- Working tree, `quiz-modal.js` (`9376b3e6…`), `audience-quiz.js` (`a91ca240…`), all quiz file mtimes, and staging's theme-directory mtime (`2026-07-30 00:09:25`) are **unchanged**. The background session has still not written to `quiz-modal.js`.
- **143/143 files** re-compared local vs deployed: 0 differing, 0 local-only, 0 deployed-only.
- Staging **1.19.96**, `wp eval` clean, render 11,239 bytes with no new PHP error, no quiz entries in the error logs, caches purged again. Production **1.19.91**.
- `node --check` ×2, `php -l` ×2, `git diff --check` — all pass.
- **New untracked artifacts noted:** `tmp/pdfs/bhp-site-review/*.png` (7 site-review images). `tmp/` is **not** gitignored, but it contributes **0 files** to the 143-file source set, which is built from explicit paths (`style.css theme.json assets inc template-parts` + top-level PHP). Excluded by construction; left in place, not deleted.
- Full regression re-run live: 5 viewports × 4 routes, **all PASS**; four dismissals at 0px delta from 800/2000/3400/2600 with the launcher 591–3,191px below the fold, none landing near the quiz CTA band (y≈3968); resume-on-reopen intact.
- **Accessibility, measured per screen:** 12 elements match the focusable selector in the DOM at all times, but only **5 (Q1), 5 (Q2), 4 (result)** are exposed — 7–8 correctly excluded as hidden. All exposed controls are inside the dialog, the close button is present on every screen, the focused element is always visible, and Escape closes from the result screen.
- **Screenshots still not produced**, but the tool now returns a specific cause rather than a bare timeout: *"the Browser pane is not displayed, so the page is not compositing frames. Display the pane and retry."* This is a host-UI state this session cannot change — not a site defect and not a tool failure. Geometry tables above stand in as measurement.

## Guardrails observed

No code change was needed, so none was made; version held at 1.19.96; no redeploy; caches purged; `.claude/settings.local.json`, `tests/`, `docs/`, backups and temp files all excluded from the deploy set by construction; no one's uncommitted work overwritten, discarded or quarantined; nothing committed, pushed, merged or opened as a PR; **production untouched at 1.19.91.**
