# Quiz Question-Screen Fit Correction — Staging 1.19.119

**Date:** 2026-07-31
**Environment:** staging2.braveheartspublishing.com **only**
**Theme:** 1.19.118 → **1.19.119**
**Production:** **1.19.112, untouched and not approved for change**
**Status:** READY FOR OWNER REVIEW

Companion to `QUIZ_QUESTION_SIMPLIFICATION_1_19_118.md`. That release removed the promotional header and enlarged the type; this one fixes the fit defect it left behind.

---

## 1. Stop gate

| Gate | Result |
| --- | --- |
| Canonical chain re-read | `START_HERE.md`, `PROJECT_STATE.md`, `CURRENT_TASK.md`, `NEXT_TASK.md`, `DECISIONS.md`, `KNOWN_ISSUES.md`, `RUNBOOK.md`, `.claude/rules/*` |
| Local + staging = 1.19.118 | Confirmed — `style.css` and `wp theme list --status=active` |
| Production = 1.19.112 | Confirmed on the production doc root |
| No competing writer | All five 1.19.118 files matched their recorded checksums exactly; full-theme diff local ↔ staging returned **IDENTICAL, 147/147** |
| Backup | `~/bhp-STAGING-backup-quizfit-20260731/` — 147 files, 5.2M, verified at `Version: 1.19.118` |

### Rollback

```bash
ssh -i ~/.ssh/id_ed25519 -p <port> <user>@<host> "cd <staging_doc_root>/wp-content/themes && rm -rf brave-hearts-theme-deploy-explorer-expedition-guides && cp -a ~/bhp-STAGING-backup-quizfit-20260731/brave-hearts-theme-deploy-explorer-expedition-guides . && cd ../.. && wp sg purge --user=1"
```

Restores staging to 1.19.118 exactly. Production is not involved in any step.

---

## 2. Root cause

The screenshots were described in the brief but not supplied to this session, so the defect was reproduced by measurement rather than assumed.

`.bhp-quiz-modal__dialog` is capped at `max-height: calc(100vh - var(--space-8))`, and `--space-8` resolves to **32px** — confirmed live as a computed `max-height: 548px` at a 580px-tall viewport. In 1.19.118 Question 1 was a **single column of four cards** whose `min-height` clamped to 80px on desktop:

| Viewport | Q1 content | Budget | Headroom |
| --- | --- | --- | --- |
| 1440×760 | 537.7px | 728px | 190px |
| 1366×620 | 537.7px | 588px | 50px |
| 1366×580 | 537.7px | 548px | **10px** |
| 320×568 | **571px** | 544px | **−27px → scrolled** |

Q1 therefore overflowed below roughly a **570px** viewport height and Q2 (parent route) below ~564px — and at **320×568 it already scrolled by 27px**, with the longest answer wrapping to **three lines** (99.5px cards). A window shorter than ~570px is ordinary once browser chrome and bookmarks are subtracted from a 1366×768 or 1280×720 screen, which is exactly when the fourth answer clips and the Q2 Back control drops below the fold.

**The single column was the cause. The type size was not** — so the fix is layout, not smaller text.

---

## 3. Before / after grid structure

| Screen | Before (1.19.118) | After (1.19.119) |
| --- | --- | --- |
| Q1, ≥760px wide | 1 col × 4 rows | **2 cols × 2 rows (2×2)** |
| Q2, ≥760px wide | 1 col × 3 rows | **2 cols × 2 rows** — two on row 1, third spanning row 2 via `grid-column: 1 / -1` (measured 354 + 354 + 12 gap = **720px**) |
| Q1/Q2, <760px | 1 col | 1 col, compacted |
| Narrow **and** short (<600w, <520h) | 1 col | **2 cols** — proven at 568×320 |
| 667×375 | 1 col | 1 col — two columns deliberately **not** forced (see §9) |

Q1's third answer correctly does **not** span — all four measured 354px — because `:nth-child(3):last-child` cannot match an element that is not last.

**The width constraint was `.bhp-quiz__inner`, not the dialog.** At its 640px cap each column resolved to 314px and each label to 250.7px. The longest Q1 answer ("Bring adventure into my classroom, library, or homeschool") measures **464.8px intrinsic** at 20.9px, so it needs about **261px** of label width to break cleanly into two lines — it was roughly 10px short, which is precisely why it took a third line. Question steps now use a **720px** measure inside a **780px** dialog: columns 354px, labels ~292px, **two lines**.

Keyboard order is not managed separately: DOM order *is* row-major grid order, and there is no `order`, `dense` or reversal anywhere in the file.

---

## 4. Before / after typography and spacing

| Metric | 1.19.118 | 1.19.119 | Target |
| --- | --- | --- | --- |
| Progress (1440 / 1024 / 390) | 16 / 15.1 / 13.2 | **15 / 14.1 / 12** | 14–15 desktop, 12–14 mobile ✅ |
| Question (1440 / 1024 / 390) | 34 / 32.7 / 25 | **37.8 / 32.2 / 23.7** | 32–38 desktop, 23–27 mobile ✅ |
| Answer (1440 / 1024 / 390) | 22 / 20.6 / 18 | **20.9 / 19.4 / 17.1** | 19–21 desktop, 17–19 mobile ✅ |
| Control height (1440 / 1024 / 390) | 80 / 74.5 / 61.8 | **78.7 / 75.5 / 54** | ~68–78 desktop, 52–60 mobile ✅ |
| Minimum control, whole matrix | 54 | **46** | ≥44 ✅ |
| Progress margin-bottom | 8 (6 in modal) | **6 (4 in modal)** | — |
| Question margin-bottom | 18 (14 in modal) | **14 (10 in modal)** | — |
| Answer padding | `14px 52px 14px 20px` | **`12px 44px 12px 18px`** (`34px` / `14px` at ≤430px) | — |
| Arrow lane | `right: 16px` | **`right: 14px`** (10px at ≤430px) | — |
| Grid gap | 10px | **12px two-col / 8px / 6px on short screens** | — |
| Question-step bottom padding | 32px | **20 / 16 / 16px** | — |
| Modal top padding | 60 / 56px | **unchanged** — still fully clears the 48px close button | — |
| Label-to-arrow gap | 29–121px | **24–33.4px** | no collision ✅ |

Height-aware compaction is scoped to `.bhp-quiz--question` at `max-height: 760px` and `max-height: 600px`, so it can never shrink the result screen.

---

## 5. Complete geometry table

Each row covers Q1 and all four Q2 routes at that viewport. `scroll?` is whether the content container can scroll at all; `sb px` is reserved scrollbar width (`offsetWidth − clientWidth`).

| Viewport | Step | Grid | Dialog W×H | clientH | scrollH | scroll? | sb px | prog | q | a | min ctrl | max lines | last ans B | back B | dialog B | safe | close | clip | h-ovf |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 1440×900 | Q1 | 2×2 | 780×340.6 | 341 | 341 | **no** | 0 | 15 | 37.8 | 20.9 | 79.4 | 2 | 594.3 | — | 620.3 | 26 | ✅ | no | no |
| 1440×900 | Q2 ×4 | 2×2 | 780×381.6–383 | 382–383 | 382–383 | **no** | 0 | 15 | 37.8 | 20.9 | 78 | 2 | 571–571.7 | 620.8–621.5 | 640.8–641.5 | 20 | ✅ | no | no |
| 1366×768 | Q1 | 2×2 | 780×338.1 | 338 | 338 | **no** | 0 | 15 | 36.8 | 20.6 | 78.7 | 2 | 527 | — | 553 | 26 | ✅ | no | no |
| 1366×768 | Q2 ×4 | 2×2 | 780×377.3–379.6 | 377–380 | 377–380 | **no** | 0 | 15 | 36.8 | 20.6 | 76.4 | 2 | 502.8–504 | 552.6–553.8 | 572.6–573.8 | 20 | ✅ | no | no |
| 1024×768 | Q1 | 2×2 | 780×324.8 | 325 | 325 | **no** | 0 | 14.1 | 32.2 | 19.4 | 75.5 | 2 | 520.4 | — | 546.4 | 26 | ✅ | no | no |
| 1024×768 | Q2 ×4 | 2×2 | 780×354.7–361.6 | 355–362 | 355–362 | **no** | 0 | 14.1 | 32.2 | 19.4 | 68.5 | 2 | 491.5–495 | 541.3–544.8 | 561.3–564.8 | 20 | ✅ | no | no |
| 768×1024 | Q1 | 2×2 | 736×314.6 | 315 | 315 | **no** | 0 | 13.2 | 28.8 | 18.4 | 73.2 | 2 | 643.3 | — | 669.3 | 26 | ✅ | no | no |
| 768×1024 | Q2 ×4 | 2×2 | 736×337.3–347.8 | 337–348 | 337–348 | **no** | 0 | 13.2 | 28.8 | 18.4 | 62.7 | 2 | 610.8–616.1 | 660.6–665.9 | 680.6–685.9 | 20 | ✅ | no | no |
| 430×932 | Q1 | 1×4 | 406×424.7 | 425 | 425 | **no** | 0 | 12.1 | 24.2 | 17.2 | 54.9 | 2 | 898 | — | 920 | 22 | ✅ | no | no |
| 430×932 | Q2 ×4 | 1×3 | 406×373.2–401.8 | 373–402 | 373–402 | **no** | 0 | 12.1 | 24.2 | 17.2 | 54.9 | 2 | 854.2 | 904 | 920 | 16 | ✅ | no | no |
| 390×844 | Q1 | 1×4 | 366×445.4 | 445 | 445 | **no** | 0 | 12 | 23.7 | 17.1 | 54 | 2 | 810 | — | 832 | 22 | ✅ | no | no |
| 390×844 | Q2 ×4 | 1×3 | 366×393.7–441 | 394–441 | 394–441 | **no** | 0 | 12 | 23.7 | 17.1 | 54 | 2 | 766.2 | 816 | 832 | 16 | ✅ | no | no |
| 320×568 | Q1 | 1×4 | 304×435.5 | 435 | 435 | **no** | 0 | 12 | 20 | 17 | 60.2 | **3** | 540 | — | 560 | 20 | ✅ | no | no |
| 320×568 | Q2 ×4 | 1×3 | 304×357.7–386 | 358–386 | 358–386 | **no** | 0 | 12 | 20 | 17 | 46 | 2 | 502.2 | 544 | 560 | 16 | ✅ | no | no |
| 667×375 | Q1 | 1×4 | 651×338.6 | 339 | 339 | **no** | 0 | 12.9 | 21.9 | 18.1 | 46 | 1 | 336.8 | — | 356.8 | 20 | ✅ | no | no |
| 667×375 | Q2 ×4 | 1×3 | 651×324.4 | 324 | 324 | **no** | 0 | 12.9 | 21.9 | 18.1 | 46 | 1 | 291.9 | 333.7 | 349.7 | 16 | ✅ | no | no |
| 568×320 *(extra)* | Q1 / Q2 | **2×2** | 552×286.6–286.7 | 287 | 287 | **no** | 0 | 12.5 | 20.9 | 17.7 | 46 | 3 / 2 | 254.2–292 | 296 | 312 | 16–20 | ✅ | no | no |

Duplicate IDs **0** and console errors **0** at every viewport.

---

## 6. Proof that Q1 and Q2 cannot scroll

Four independent confirmations, not one:

1. **Geometry** — `contentScrollH === contentClientH` on every Q1 and Q2 row in §5.
2. **Scrollability** — the derived `canScroll` (`scrollHeight > clientHeight + 1`) is `false` on all of them.
3. **No reserved scrollbar space** — `offsetWidth − clientWidth = 0px` on every row, so the browser is not silently reserving a gutter despite equal geometry.
4. **Live enumeration plus a real scroll attempt** — walking the dialog for elements that both declare `overflow-y: auto|scroll` *and* actually overflow returns **0** on Q1 and Q2. At 320×568, writing `scrollTop = 200` while on Q1/Q2 leaves the container at **0** because there is nothing to scroll; the identical write on the **result** screen does move it, since that screen legitimately scrolls (`scrollHeight 765` vs `clientHeight 552`, exactly **one** region) — which the brief permits.

Every answer's complete bounding box, and the Q2 Back control's complete bounding box, lie inside the visible dialog at every viewport (`allAnswersInside` and `backInside` true throughout), with **16–26px** of clear space below the final control.

---

## 7. Route and accessibility regression results

- **4 routes, 12 results, 12 distinct headlines**, exactly **one** visible primary CTA each, 11 with the email form, partnership form-free and still deep-linking to `#contact`, destinations and UTMs unchanged.
- **Result screens untouched:** dialog **640px** (not 780), classes `bhp-quiz bhp-quiz--result` only, inner measure 640px, padding-bottom 32px, offer 44px, headline 30px, form 420px, 2 fields, submit label unchanged.
- **Keyboard order matches visual order by construction.** Q1 measured at (360,424) (726,424) (360,515) (726,515) → **top-left, top-right, bottom-left, bottom-right**. Q2 → top-left, top-right, full-width bottom, then Back. DOM order equals row-major visual order (`matches: true`).
- Focus trap wraps **both** directions on Q1, Q2 and result; focusable sets 5 / 5 / 7, containing only visible in-dialog controls. Escape closes and returns focus to the launcher.
- Visible-question dialog labelling, live-region announcements, DOM order, answer accessible names (the arrow remains decorative — `content: ""`) and visible focus rings all preserved. Label-to-arrow gap **24–33.4px**, so text never collides with the glyph.
- **16/16 dismissals at 0px drift** on both axes (close button / Escape / backdrop / Keep browsing × four scroll positions on a 9,965px page with the launcher at 9,102px).
- **Auto-popup proven for both triggers** with captured events: `open_reason: "timer"` and `open_reason: "scroll_40"` (at measured depth 0.501). Session suppression unchanged.
- **Scroll reset holds:** a result scrolled to 200 → Start over lands Q1 at `scrollTop 0`; a result scrolled to 150 → re-entering Q2 lands at `scrollTop 0`.
- **Start over** fully resets: back to step 1, first name and email cleared, all Q1 selections cleared, dialog label back to the Q1 question.
- **200% text zoom:** at 1440×900 Q1 and Q2 do not even need to scroll; at 320px scrolling becomes necessary, with **exactly one** region, nothing clipped, every answer reachable, close button visible and hit-testable, no horizontal overflow.
- **Smoke tests:** the homepage keeps all 13 Phase 1a sections in order with its intro card intact and navy-card contrast preserved (white question, pale progress, dark-on-white answers); `/find-your-adventure/` renders the two-column grid at a 720px measure, two lines, a clean `H1 → H2` outline, no overflow.
- **No form was ever submitted — no Mailchimp contact was created.**

---

## 8. Files changed (4)

| File | Change |
| --- | --- |
| `assets/css/audience-quiz.css` | Options container → CSS grid; two columns from 760px with the third-of-three spanning; narrow-landscape two-column rule; retuned progress/question/answer clamps and control `min-height`; reduced card padding and arrow lane; `.bhp-quiz--question` height-aware compaction at 760px and 600px; 720px question measure |
| `assets/css/quiz-modal.css` | `.bhp-quiz-modal__dialog--question { max-width: 780px }`; tighter progress/question margins; question-step bottom padding; short-viewport gutter reclaim |
| `assets/js/audience-quiz.js` | `showStep()` toggles `bhp-quiz--step-1` / `bhp-quiz--step-2` / `bhp-quiz--question` on the quiz root and `bhp-quiz-modal__dialog--question` on the dialog; the same state is applied to the arrival screen at init |
| `style.css` | `Version: 1.19.118` → `1.19.119` |

**Not touched:** question copy, answer copy, routing, results, email capture, Mailchimp, redirects, auto-popup logic (`quiz-modal.js` is byte-identical), homepage Phase 1a files, Shop, products, production.

The dialog class is set explicitly from JS rather than via `:has()`, because the dialog is an **ancestor** of the quiz root and this keeps behaviour deterministic across browsers.

---

## 9. Honest limitations

- **At 320×568, Q1's longest answer still wraps to three lines.** The ≤2-line requirement was specified for the two-column desktop/tablet grid, where it is met at every viewport. In a single column at 320px it cannot be met without dropping below the 17px floor. It fits, does not clip, and does not scroll.
- **667×375 uses one column, not two.** Two columns were not *necessary* there — a single column measures 339px of a 359px budget and reads better at that width. The narrow-landscape two-column rule is real, and is proven firing at 568×320.
- **Screenshots were not supplied to this session and cannot be produced in this environment** (the tool times out — a project-long limitation in `KNOWN_ISSUES.md`). The defect was reproduced by measurement, and every claim above is DOM geometry or a computed style.
- **A same-version redeploy caused stale CSS in the test browser mid-QA.** Iterating within 1.19.119 meant the browser reused cached stylesheets; this was caught, stylesheets were force-refetched, and the final numbers were re-measured. The **served** files were then confirmed on disk to contain `max-width: 780px`, `max-width: 720px` and `@media (min-width: 760px)`, and parity is 147/147. Real visitors are unaffected — for them the version moves 1.19.118 → 1.19.119, which busts the cache normally.

---

## 10. Deployment and parity

Full-ZIP `wp theme install --force`, built on the server from the verified 1.19.118 backup with only the changed files patched in, so the complete 147-file set is guaranteed and no unrelated drift is reintroduced.

```
wp theme install ~/build-119.zip --force --user=1   →  "Theme updated successfully."
wp theme list --status=active                       →  1.19.119, active
wp eval 'echo "ok";' --user=1                       →  ok        (no PHP fatal)
wp sg purge --user=1                                →  Dynamic Cache Successfully Purged
```

**Parity: 147/147 files byte-identical** between the working tree and deployed staging, verified by full `md5sum` comparison after the final deploy. Served CSS independently confirmed to contain the final values.

**Production confirmed still 1.19.112.**

---

## 11. Outcome

**READY FOR OWNER REVIEW.**

Staging 1.19.119. Production untouched at 1.19.112 and **not** approved for deployment — no production approval was requested and none is implied by this record.

Suggested review path: open the quiz on a blog post at a normal desktop window and deliberately make the window short (drag it to roughly half screen height). Question 1 should stay a 2×2 grid with all four answers and the close button visible and no scrollbar; Question 2 should show both top answers, the full-width third, and Back. Staging is now **three** releases ahead of production (1.19.117 Homepage Phase 1a, 1.19.118, 1.19.119) and they would ship as one package.
