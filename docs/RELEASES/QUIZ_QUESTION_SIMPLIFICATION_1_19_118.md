# Quiz Question-Screen Simplification — Staging 1.19.118

**Date:** 2026-07-31
**Environment:** staging2.braveheartspublishing.com **only**
**Theme:** 1.19.117 → **1.19.118**
**Production:** **1.19.112, untouched and not approved for change**
**Status:** READY FOR OWNER REVIEW

---

## 1. Stop gate (completed before any edit)

| Gate | Result |
| --- | --- |
| Canonical chain read | `START_HERE.md`, `AI_CONTEXT_INDEX.md` (via repo `CLAUDE.md` order), `PROJECT_STATE.md`, `CURRENT_TASK.md`, `NEXT_TASK.md`, `DECISIONS.md`, `KNOWN_ISSUES.md`, `RUNBOOK.md`, `.claude/rules/*` |
| Local = staging = 1.19.117 | Confirmed. `style.css` `Version: 1.19.117`; `wp theme list --status=active` on staging → `1.19.117` |
| Production = 1.19.112 | Confirmed via `wp theme list --status=active` on the production doc root |
| No competing writer | Confirmed by full-theme checksum diff: deployed staging vs working tree differed in **exactly the 5 files this task edits**; the other 142 were byte-identical |
| Staging backup | `~/bhp-STAGING-backup-quizsimplify-20260730/` — 147 files, 5.2M, verified at `Version: 1.19.117` |

**Documentation note:** `START_HERE.md`/`PROJECT_STATE.md` describe staging at 1.19.100/1.19.107. The live system is authoritative and was at 1.19.117; the snapshot docs were stale, as they are designed to be between sessions. No action taken beyond recording it here.

### Rollback command

```bash
ssh -i ~/.ssh/id_ed25519 -p <port> <user>@<host> "cd <staging_doc_root>/wp-content/themes && rm -rf brave-hearts-theme-deploy-explorer-expedition-guides && cp -a ~/bhp-STAGING-backup-quizsimplify-20260730/brave-hearts-theme-deploy-explorer-expedition-guides . && cd ../.. && wp sg purge --user=1 && wp theme list --status=active --user=1"
```

Restores staging to 1.19.117 exactly. Production is not involved in any step.

---

## 2. Exact content removed

The entire `.bhp-quiz__header` block was deleted from `template-parts/quiz/audience-quiz.php`'s non-`intro_gate` branch. It rendered above **both** question screens in the sitewide modal, the canonical `/find-your-adventure/` page and the `[bhp_audience_quiz]` shortcode.

| Removed | Element |
| --- | --- |
| `2 QUESTIONS · ABOUT 30 SECONDS` | `<span class="bhp-quiz-eyebrow">` |
| `Where Should Your Adventure Begin?` | `<h2 class="bhp-quiz__heading">` |
| `No wrong answers—tell us who you're here for and what would feel like a win. We'll match you with the most useful free resource and next step.` | `<p class="bhp-quiz__lead">` |
| The wrapper itself, and all of its height | `<div class="bhp-quiz__header" data-bhp-quiz-header>` |

Removed from the **DOM**, not hidden — verified live: `removedCopyPresent = {eyebrow: false, headline: false, lead: false}`.

**Header height reclaimed (measured, before → after):**

| Viewport | Header height | Question screen started at | Now starts at |
| --- | --- | --- | --- |
| 1440×900 | 195.6px | 229px into the dialog | **60px** |
| 1024×768 | 127.4px | 199.4px | **60px** |
| 390×844 | 182.4px | 250.4px | **56px** |
| 320×568 | 231.3px (of a 544px dialog) | 299.3px | **56px** |

**Question 1 now visibly contains exactly:** close button · `Question 1 of 2` · `What would you like help with today?` · four answers.
**Every Question 2 route contains exactly:** close button · `Question 2 of 2` · route question · answers · `← Back`.

### What was deliberately NOT removed

- **The homepage `intro_gate` card** (`.bhp-quiz__intro`) is a different element and still renders its eyebrow, headline, lead and start button. Verified live on the homepage after deploy.
- **`/find-your-adventure/`'s own `<h1>` and intro paragraph** (`page-find-your-adventure.php`) — untouched. That page had been showing *two* stacked introductions; removing the component's header de-duplicated it. Heading outline is now `H1: Two Quick Questions…` → `H2: What would you like help with today?`.

---

## 3. Accessible-name solution

**The brief's premise was checked, not assumed, and it did not hold.** The old headline was never part of `aria-labelledby`. Measured on 1.19.117 before editing:

```
aria-labelledby → "bhp-quiz-modal-2-title"
                → <h2 class="screen-reader-text">Find Your Adventure quiz</h2>
```

The visible `Where Should Your Adventure Begin?` heading had no `id` and was referenced by nothing. Removing it therefore could not break the dialog's accessible name — but the name was also not *truthful* about which screen the visitor was on.

**Implemented:**

1. The visible question is a real `<h2 class="bhp-quiz__question">` on both steps, with ids derived from the already-unique root id (`<root>-q1`, `<root>-q2`); result headings gained `<root>-result-resource` / `<root>-result-headline`.
2. `syncDialogLabel(step)` in `audience-quiz.js` retargets the dialog's `aria-labelledby` to the heading of the **visible** step on every transition, and once at init so the name is correct from the first frame. Result step points at the free-offer heading, falling back to the recommendation headline on the partnership answer (which deliberately has no offer).
3. The persistent SR-only `Find Your Adventure quiz` heading is retained **only as a fallback** and is no longer referenced. `Where Should Your Adventure Begin?` is not used as a hidden title anywhere.
4. Each radiogroup is named by `aria-labelledby` pointing at its own question heading; the previous duplicated `aria-label` write in JS was removed.
5. A `role="status" aria-live="polite" aria-atomic="true"` region sits **outside every step wrapper** — inside a step, the step's own `hidden` attribute would remove it from the accessibility tree at the exact moment it needs to speak. It announces `Question N of 2. <question>` once per transition, cleared-then-rewritten so an identical string (Back to Q1 twice) still re-announces.

**Verified live:**

| Check | Result |
| --- | --- |
| Dialog name on Q1 | `What would you like help with today?` (visible H2, not inside a hidden step) |
| Dialog name on each Q2 | The four route questions, respectively |
| Dialog name on results | The free-offer heading; partnership → `Let's explore the right group or partnership path.` |
| Hidden steps | `display: none`, height 0 — absent from layout, tab order and a11y tree |
| Focus trap set on Q1 | Exactly 5: close + 4 answers. No hidden content reachable |
| Duplicate IDs | **0** (90 ids on the page) |
| Announcement | `Question 2 of 2. What would feel like the biggest win right now?` etc., once per transition |
| Heading outline in dialog | `H2 Find Your Adventure quiz` (SR-only fallback) → `H2 <current question>` |

---

## 4. Before / after typography

| Metric | Before (1.19.117) | After (1.19.118) | Target |
| --- | --- | --- | --- |
| **Desktop 1440×900** | | | |
| Progress | 12px | **16px** | 14–16 ✅ |
| Question | 18px (`<p>`) | **34px** (`<h2>`) | 30–34 ✅ |
| Answer label | 15px | **22px** | 20–22 ✅ |
| Answer control height | 81px (2-col, ragged) | **80px** (uniform) | ≥72 ✅ |
| **Desktop 1366×768** | | | |
| Progress / Question / Answer | 12 / 18 / 15 | **16 / 34 / 22** | ✅ ✅ ✅ |
| Control height | — | **80px** | ≥72 ✅ |
| **Desktop 1024×768** | | | |
| Progress / Question / Answer | 12 / 18 / 15 | **15.1 / 32.7 / 20.6** | ✅ ✅ ✅ |
| Control height | 81px | **74.5px** | ≥72 ✅ |
| **Tablet 768×1024** | | | |
| Progress / Question / Answer | 12 / 18 / 15 | **14.3 / 29.2 / 19.5** | see §8 |
| Control height | — | **69.4px** | see §8 |
| **Mobile 390×844** | | | |
| Progress / Question / Answer | 12 / 18 / 15 | **13.2 / 25 / 18** | 13–14 / 25–28 / 18–20 ✅ |
| Control height | 55.5px (below target) | **61.8px** | ≥60 ✅ |
| **Mobile 320×568** | | | |
| Progress / Question / Answer | 12 / 18 / 15 | **13 / 25 / 18** | ✅ ✅ ✅ |
| Control height | 81px | **76.1px** | ≥60, ≥44 ✅ |
| **Landscape 667×375** | | | |
| Progress / Question / Answer | 12 / 18 / 15 | **14 / 27.7 / 19** | ✅ ✅ ✅ |
| Control height | — | **67.3px** | ≥60 ✅ |

All values from fluid `clamp()`; no stepped breakpoint. Answer text is **not** uppercase (`text-transform: none`) at any viewport.

## 5. Before / after modal dimensions

| Viewport | Dialog before | Dialog after | Change |
| --- | --- | --- | --- |
| 1440×900 | 640 × 546 | **640 × 537.7** | −8.3px, with a 195.6px header removed and all type enlarged |
| 1024×768 | 640 × 477.8 | **640 × 512.5** | +34.7px (type is far larger; header gone) |
| 768×1024 | — | **640 × 486.4** | — |
| 390×844 | 366 × 651.8 | **366 × 510.3** | **−141.5px** |
| 320×568 | 296 × 544 | **296 × 544** | unchanged (already at max-height) |
| 667×375 | — | **635.3 × 343.3** | — |

The dialog is now **compact where the content fits** and the reclaimed header height went into legibility rather than empty space.

---

## 6. Seven-viewport results

| Viewport | Old intro absent | Progress ×1 | Question ×1, dominant | Answers larger | No clip | No h-overflow | Dup IDs | Dup buttons | Scroll regions | Close visible + tappable | Q2 scrollTop 0 | Console |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 1440×900 | ✅ | ✅ | ✅ 34px | ✅ 22px | ✅ | ✅ | 0 | 0 | 0 (Q1/Q2/result) | ✅ 48×48 | ✅ | clean |
| 1366×768 | ✅ | ✅ | ✅ 34px | ✅ 22px | ✅ | ✅ | 0 | 0 | 0 Q1/Q2, **1** result | ✅ 48×48 | ✅ | clean |
| 1024×768 | ✅ | ✅ | ✅ 32.7px | ✅ 20.6px | ✅ | ✅ | 0 | 0 | 0 Q1/Q2, **1** result | ✅ 48×48 | ✅ | clean |
| 768×1024 | ✅ | ✅ | ✅ 29.2px | ✅ 19.5px | ✅ | ✅ | 0 | 0 | 0 everywhere | ✅ 48×48 | ✅ | clean |
| 390×844 | ✅ | ✅ | ✅ 25px | ✅ 18px | ✅ | ✅ | 0 | 0 | 0 everywhere | ✅ 44×44 | ✅ | clean |
| 320×568 | ✅ | ✅ | ✅ 25px | ✅ 18px | ✅ | ✅ | 0 | 0 | **1** Q1, 0 Q2, **1** result | ✅ 44×44 | ✅ | clean |
| 667×375 | ✅ | ✅ | ✅ 27.7px | ✅ 19px | ✅ | ✅ | 0 | 0 | **1** Q1/Q2/result | ✅ 48×48 | ✅ | clean |

Never more than **one** scroll region, and only on genuinely short screens. Label-to-arrow clearance measured 29.7px (mobile) to 121px (landscape) — text never crowds the glyph.

### One finding worth naming: close-button hit test at 320×568

The first 320×568 pass returned `closeHitOk: false`. Root-caused rather than accepted: `document.elementFromPoint` returned `#wpconsent-container`, the WPConsent cookie banner's shadow host. `.wpconsent-banner` is `position: fixed`, `z-index: 900000`, occupying **0,0 → 320×308** — it covers the quiz's close button at that width. The quiz modal is z-index 2100.

- **Not a regression from this release** and not a quiz defect. It is the already-documented WPConsent shadow-DOM collision (`KNOWN_ISSUES.md`, auto-memory `project_bhp_quiz_modal_consent_collision`). `quiz-modal.js`'s `hasVisibleConsentUI()` already defers **automatic** opening while consent UI is painted; this test forced a manual open.
- With the banner dismissed (Reject Nonessential — the privacy-preserving option), the close button hit-tests **true** at 320×568 and every other viewport.
- Corner probes return false because the button is a `border-radius: 50%` circle; its corners are outside the rendered shape. That is correct, and matches the existing D1 note that the visible circle *is* the interactive box.

---

## 7. Four-route results

All four routes and **all 12 Q2 answers** exercised at every one of the seven viewports.

| Route | Q2 question | Answers | Results |
| --- | --- | --- | --- |
| Parent | What would feel like the biggest win right now? | 3 | 3 distinct → `/reluctant-reader-adventure-kit/` |
| Educator | What would help you bring the story to life? | 3 | 3 distinct → `/educators-adventure-learning-toolkit/` |
| Organization | What are you hoping to create? | 3 | 3 distinct → `/organizations-community-reading-kit/`, partnership → `…#contact` |
| Gift | What do you want the gift to spark? | 3 | 3 distinct → `/gift-buyers-guide/` |

- **12 distinct headlines**, unchanged from 1.19.117 (byte-compared against the pre-change baseline walk).
- **Exactly one visible primary CTA on every result** — the legacy duplicate stays suppressed.
- Result screens unchanged and not redesigned: `YOUR BEST NEXT STEP` eyebrow, large free-offer name, supporting explanation, first-name + email fields, email-submit CTA, partnership CTA, Keep browsing, Start over, close button — all present on every applicable result.
- **Partnership exception correct:** no form, no fabricated free-kit promise, CTA `Explore Group Orders & Partnerships` → `#contact`.
- UTMs unchanged (`utm_source=quiz&utm_medium=onsite&utm_campaign=audience_quiz&utm_content=<entry_location>`), `entry_location` correctly reflecting page type (`blog_post`, `information_page`, `shop`).
- **No form was ever submitted. No Mailchimp contact was created.**

---

## 8. Keyboard and accessibility results

| Check | Result |
| --- | --- |
| Keyboard-only completion | Q1 → Q2 → result reached entirely by focusing and activating controls |
| Focus on open | First interactive control inside the dialog |
| Focus after advance | Q2 → first answer; result → result headline. Always inside the dialog |
| Tab from last | Wraps to first — Q1, Q2 and result |
| Shift+Tab from first | Wraps to last — Q1, Q2 and result |
| Escape | Closes, focus returns to the launcher, `aria-expanded="false"` |
| Focusable sets | Q1 = 5, Q2 = 5, result = 7 — visible in-dialog controls only |
| Question announcement | Once per transition via `role="status"`; focus behaviour unchanged |
| Duplicate IDs | 0, on the modal pages, the homepage and `/find-your-adventure/` |
| 200% text zoom, 1440×900 | No clipping, no horizontal overflow, dialog inside viewport, close visible + tappable, 0 scroll regions |
| 200% text zoom, 320×568 | No clipping, no horizontal overflow, close visible + tappable, exactly 1 scroll region |
| Reduced motion | 5 quiz rules present and parsed (option + arrow transitions, CTA transition/active, modal transition/animation) |

**Tablet-gap disclosure (768×1024).** The brief defines two typography tiers, desktop and mobile. 768 sits between them and interpolates: question **29.2px** (desktop floor 30), answers **19.5px** (desktop floor 20), controls **69.4px** (desktop floor 72) — each just under the desktop band and just over the mobile band. This is the intended behaviour of fluid `clamp()`; meeting both bands exactly would require a near-vertical ramp between 667px and 768px and a visible jump between a phone in landscape and a tablet in portrait. Called out rather than reported as a pass.

**200% zoom, layout-viewport note.** At 320px with 200% text zoom the emulated layout viewport expands to 505px. Measured with the modal **closed**, the page alone already does this (`documentElement.scrollWidth` 505, widest elements are blog content) — it is pre-existing page behaviour, not caused by the quiz. With the modal open the dialog fills that viewport correctly (12px symmetric padding, 480.7px wide), with no clipping and no scrollbar.

**Not verified, and reported as such:** reduced-motion *rendering* under a genuine OS preference (rules confirmed present and parsed only); CSS `:hover` painting; screenshots — the tool times out in this environment, a project-long limitation recorded in `KNOWN_ISSUES.md`. All visual claims here are exact DOM geometry and computed styles.

---

## 9. Scroll, auto-popup and dismissal results

**Internal scroll.** `scrollTop` reset on step change preserved. Q2 opens at **0 on all four routes at all seven viewports**; every result opens at **0**. Exactly one scroll region where genuinely needed, never nested, never two.

**Page-position preservation — 16/16 at 0px on both axes.** Four dismissal methods × four scroll positions (0 / 1200 / 3000 / 6798) on a 9,965px blog post whose launcher sits at 9,102px, i.e. off-screen in every case:

| Dismissal | 0 | 1200 | 3000 | 6798 |
| --- | --- | --- | --- | --- |
| Close button | 0px | 0px | 0px | 0px |
| Escape | 0px | 0px | 0px | 0px |
| Backdrop | 0px | 0px | 0px | 0px |
| Keep browsing | 0px | 0px | 0px | 0px |

Body scroll lock applied while open and released on close in all 16; focus returned to the launcher in all 16. Also 0px drift after an **automatic** open.

**Auto-popup — both triggers proven with captured events:**

| Trigger | Evidence |
| --- | --- |
| Delay (8s) | `quiz_modal_opened` with `open_reason: "timer"`, `entry_location: "blog_post"` — reproduced twice |
| Scroll (40%) | `quiz_auto_trigger_armed` then `quiz_modal_opened` with `open_reason: "scroll_40"` at measured depth 0.501 |
| Session suppression | Flag `bhp_quiz_auto_shown = "1"`; after reload the modal did **not** auto-open again |

**Start over.** From a completed result with the form filled: returns to step 1 only, clears first name and email, clears all Q1 `aria-checked`, resets the dialog label to the Q1 question, `scrollTop` 0.

**Smoke tests.** Homepage: all 13 Phase 1a sections present and in order (`home-hero` first, `find-your-adventure` last), hero untouched, intro card intact, quiz typography applied with contrast preserved on the navy card (question white, progress pale, answers dark-on-white), no overflow. Shop `/books/`: 12 product links, all CTAs present, no overflow. Product (Mariana paperback): prices $11.99 / $17.99 / $48.99, format selector, `ADD PAPERBACK TO CART`, cart drawer, 1 Rank Math schema block, 0 duplicate IDs. No quiz CSS leaked outside `.bhp-quiz` / `.bhp-quiz-modal` — sampled non-quiz buttons keep their own sizes and centring. **Cart left at 0 items.**

---

## 10. Files changed (5)

| File | Change |
| --- | --- |
| `template-parts/quiz/audience-quiz.php` | Removed `.bhp-quiz__header`; questions → `<h2>` with unique ids; ids on result headings; `role="status"` live region; radiogroups via `aria-labelledby` |
| `assets/js/audience-quiz.js` | `syncDialogLabel()` + `announceStep()` + `headingForStep()`; wired into `showStep()` and init; removed dead `headerEl` handling and the duplicated Q2 `aria-label` write |
| `assets/css/audience-quiz.css` | Progress/question/answer `clamp()` scales; left-aligned answers with reserved arrow lane; `min-height` clamp; removed the two-column desktop grid; component-scoped `.bhp-quiz .bhp-quiz__question` so it beats the sitewide `body:not(.home) h2` rule |
| `assets/css/quiz-modal.css` | `padding-top` 52 → 60px (56px ≤400px); removed the now-unreachable `.bhp-quiz__heading` / `.bhp-quiz__lead` overrides |
| `style.css` | `Version: 1.19.117` → `1.19.118` |

**Not touched:** `quiz-modal.js`, `quiz-entry-cta.php`, `front-page.php`, `page-find-your-adventure.php`, `functions.php`, `inc/mailchimp.php`, any plugin, any product, any WooCommerce or Mailchimp configuration, any database content.

**Cache-busting is truthful:** all quiz assets are enqueued with `wp_get_theme()->get('Version')`, so the version bump is the cache-bust. Verified served: `style.css?ver=1.19.118`, `audience-quiz.css?ver=1.19.118`, `quiz-modal.css?ver=1.19.118`, `audience-quiz.js?ver=1.19.118`, `quiz-modal.js?ver=1.19.118`.

---

## 11. Deployment and parity

Deployed with the required full-ZIP method — the ZIP was assembled on the server from the verified 1.19.117 backup with only the 5 changed files patched in, so the complete file set is guaranteed and no unrelated drift was reintroduced.

```
wp theme install ~/build-118.zip --force --user=1   →  "Theme updated successfully."
wp theme list --status=active                       →  1.19.118, active
wp eval 'echo "ok";' --user=1                       →  ok        (no PHP fatal)
wp sg purge --user=1                                →  Dynamic Cache Successfully Purged
```

**Parity: 147/147 files byte-identical** between the working tree and deployed staging (full `md5sum` comparison after deploy). Forward-slash ZIP entries confirmed; zero backslash entries.

One pre-existing, unrelated notice appears during `wp theme install`: `Undefined array key "destination" … plugins/bookvault/Bookvault.php on line 528`. It comes from the Bookvault plugin, not the theme, and is unchanged by this release.

**Production confirmed still 1.19.112** immediately after the staging deploy.

---

## 12. Deviations from the brief, stated explicitly

1. **Answers are now left-aligned, reversing 1.19.100's optical-centring work.** The brief says answers must "remain left-aligned"; they were in fact centred. Followed the current-turn direction and flagged here because 1.19.100 is a shipped, production-live release with its own measured record.
2. **Single-column answers at all widths.** Consequence of the mandated 20–22px labels: the previous `flex: 1 1 45%` grid left 291px per answer, wrapping the longest to three lines with four unequal row heights. Layout only — answers, order, wording and routing unchanged.
3. **The brief's accessibility premise did not hold.** The old headline never participated in `aria-labelledby` (§3). The requested outcome was implemented regardless.
4. **768×1024 interpolates between the two defined tiers** (§8).
5. **The homepage and `/find-your-adventure/` inherit the typography change**, because they embed the same shared component. No homepage Phase 1a file was edited; both were smoke-tested after deploy.

---

## 13. Outcome

**READY FOR OWNER REVIEW.**

Staging 1.19.118. Production untouched at 1.19.112 and **not** approved for deployment — no production approval was requested and none is implied by this record.

Suggested review path: open any blog post, let the quiz auto-open (or use the footer launcher), and check Question 1 at a normal desktop width and on a phone. The condition that most exercised the change is a **short** window (320×568 or a phone in landscape), where the header previously consumed more than half the dialog.
