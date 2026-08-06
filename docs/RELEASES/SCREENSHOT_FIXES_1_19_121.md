# Screenshot-Driven Fixes A–G — Staging 1.19.121

**Date:** 2026-07-31 · **Staging only** · Theme 1.19.120 → **1.19.121** · **Production 1.19.112, untouched**
**Status:** READY FOR OWNER REVIEW — with one explicit real-device verification request (see §17)

---

## 1. Stop-gate result

| Gate | Result |
| --- | --- |
| Canonical chain read | `START_HERE.md`, `AI_CONTEXT_INDEX.md`, `PROJECT_STATE.md`, `CURRENT_TASK.md`, `NEXT_TASK.md`, `DECISIONS.md`, `KNOWN_ISSUES.md`, `RUNBOOK.md`, `.claude/rules/*` |
| Local + staging = 1.19.120 | Confirmed |
| Production = 1.19.112 | Confirmed |
| Local↔staging parity | **IDENTICAL, 147/147** before any edit |
| Active background writer | None |
| Approved quiz-behaviour checksums recorded | `quiz-modal.js fb819ac6…`, `audience-quiz.js 1a9798cb…`, `audience-quiz.php 5f86653c…`, `quiz-entry-cta.php ba972c61…`, `mailchimp.php ae1ee977…` |

## 2. Backup location

`~/bhp-STAGING-backup-screenshotfixes-20260731/` — 147 files, 5.2M, verified at `Version: 1.19.120`.

## 3. Screenshot-by-screenshot findings

| Screenshot | Finding |
| --- | --- |
| **843** (desktop Q1) | Hamburger **☰ rendering beside the full desktop nav**. Question sat tight on the answer grid. |
| **844** (desktop Q2) | Same hamburger duplication. Same tight question→answers gap. |
| **845** (desktop result) | Offer title enormous over 2 lines; **submit button below the fold** with a visible internal scrollbar on the dialog. Consent gear visible bottom-left. |
| **User attachment (3)** (iPhone home) | **Caption "REAL PLACES. DOORS INTO WONDER." painted behind the lower edges of the left and right covers.** Consent gear overlapping the hero CTA. |
| **User attachment (1)** (iPhone Q1) | **Bottom-sheet dialog** (rounded top corners only, flush to bottom). **Consent gear covering the fourth answer** — "Choose" rendered as "hoose". No clear space below the last card. |
| **User attachment (2)** (iPhone Q2) | Same bottom-sheet. Gear overlapping the dialog's lower-left beside Back. |
| **User attachment.png** (iPhone result) | Offer title over **three lines**; dialog occupying nearly the whole screen; **gear overlapping "Start over"**. |

## 4. Root cause for each defect

- **A — caption behind covers.** `.home .home-hero__book-stack li:first-child/:nth-child(3)` carry `translateY(24px)` and the anchors add a 3° rotation. Transforms paint outside the layout box and contribute **zero** layout height, so the caption's `margin-top: 10px` — set in 1.19.120, i.e. introduced by the previous pass — was measured against boxes ~24px above where the artwork actually painted.
- **B — bottom sheet.** `.bhp-quiz-modal { align-items: flex-end }` + `border-radius: 16px 16px 0 0` + `100vh`. `vh`/`inset:0` size to the **layout** viewport, which on iOS extends behind the collapsed toolbars.
- **C — tight question gap.** `.bhp-quiz-modal .bhp-quiz .bhp-quiz__question { margin-bottom: 10px }` at (0,3,0) outranked the component's own (0,2,0) rule, so 10px was what actually shipped.
- **D — tall result.** `max-width: 14ch` on the offer title at ≤480px forced three lines; supporting copy was 15.2px; gaps were generous; fields always stacked.
- **E — consent gear on top.** `#wpconsent-consent-floating` is a **shadow-root child** of `#wpconsent-container` (unreachable by page CSS), `position: fixed`, **z-index 9999**. The quiz modal was **z-index 2100**.
- **F — hamburger duplication.** The D2 touch-target rule sat at **top level, outside any media/container query**, declaring `display: inline-flex`. Being later in source than both the base `display:none` and the `@container (max-width: 1116px)` reveal rule, it forced the toggle visible at every width.
- **G — two quizzes.** `front-page.php` rendered the audience-gateway module *and* the full inline intro-gated quiz, while `footer.php` also rendered the sitewide launcher + modal → **2 `[data-bhp-quiz]` instances** on the homepage.

## 5. Files changed (7)

`assets/css/quiz-modal.css`, `assets/css/audience-quiz.css`, `style.css`, `front-page.php`, `footer.php`, `functions.php`, `template-parts/components/quiz-entry-cta.php` — confirmed by `diff -rq` against the backup.

**Quiz behaviour files deliberately NOT touched**, verified byte-identical to their pre-release checksums: `assets/js/quiz-modal.js`, `assets/js/audience-quiz.js`, `template-parts/quiz/audience-quiz.php`, `inc/mailchimp.php`. No routing, capture, tagging, redirect or trigger logic was modified.

### What each fix does
- **A** — the stack reserves `padding-bottom: 28px` (the 24px transform + rotation overhang) so the caption's `margin-top: 18px` measures as **real visible space**. No absolute positioning, no duplicated markup.
- **B** — `align-items: center`, `border-radius: 16px` all round, `height: 100dvh` on the overlay behind `@supports`, and `max(12px, env(safe-area-inset-*))` padding.
- **C** — the modal-scoped margin becomes `clamp(1.125rem, 0.9rem + 1.25vw, 2rem)`: 18px floor on phones, crossing 24px at exactly 768px, capped 32px.
- **D** — offer measure 14ch → 20ch at ≤480 (three lines → two); supporting copy `clamp(1rem, …, 1.1875rem)`; two-column fields ≥600px; submit ≥52px; inputs ≥44px at 16px; plus `max-height: 600px` and `max-height: 440px` compaction tiers.
- **E** — quiz modal **z-index 2100 → 10000**: above the gear (9999), still far below WPConsent's banner/preferences overlay (900000), so consent stays answerable and the auto-open deferral in `quiz-modal.js` is untouched. Nothing disabled or hidden.
- **F** — the D2 rule keeps the 44×44 box but no longer declares `display`; the reveal moves into `@container (max-width: 1116px) { .nav-toggle { display: inline-flex } }`.
- **G** — both homepage renders removed (component files kept); the `#find-your-adventure` anchor moves to the launcher wrapper via a new optional `id` arg, passed by `footer.php` **on the homepage only**; the now-dead `.home #find-your-adventure` navy-section CSS deleted (it would otherwise have repainted the small launcher as a full navy section); stale "two quizzes by design" comment in `functions.php` corrected.

## 6. Before/after measured geometry

| Metric | Before | After |
| --- | --- | --- |
| Caption gap below **painted** cover edge (390/430/320) | overlapping (negative) | **19.1px**, zero overlap |
| Caption → paragraph gap | — | 29.6px |
| Mobile dialog alignment | `flex-end`, radius `16px 16px 0 0` | **`center`, radius `16px`** |
| Dialog centre deviation (all 12 viewports) | bottom-aligned | **0.0px vertical, 0.0px horizontal** |
| Question→answers gap 1440 / 1024 / 768 / 390 | 10 / 10 / 10 / 10 | **32 / 27.2 / 24.0 / 18** |
| Result offer title lines (iPhone) | 3 | **2** |
| Supporting copy | 15.2px @≤480 | **16px floor** |
| Submit height | 48px | **52–59px** |
| Input height / font | 44px / 16px | **45–49px / 16px** |
| Quiz modal z-index | 2100 (under gear) | **10000 (over gear)** |
| Desktop hamburger | visible beside nav | **`display:none`** |
| Homepage `[data-bhp-quiz]` | 2 | **1** |

## 7. Question-screen fit matrix (Q1 + all four Q2 routes)

| Viewport | Gap | Answer px | Min card h | Scroll | Regions | All answers in | Back in | Clear below |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 1440×900 | 32.0 | 20.9 | 78.0 | none | 0 | ✅ | ✅ | 20 |
| 1366×768 | 31.5 | 20.6 | 76.4 | none | 0 | ✅ | ✅ | 20 |
| 1024×768 | 27.2 | 19.4 | 68.5 | none | 0 | ✅ | ✅ | 20 |
| 768×1024 | 24.0 | 18.4 | 62.7 | none | 0 | ✅ | ✅ | 20 |
| 430×932 | 19.8 | 17.2 | 54.9 | none | 0 | ✅ | ✅ | 16 |
| 393×852 | 19.3 | 17.1 | 54.0 | none | 0 | ✅ | ✅ | 16 |
| 390×844 | 18.0 | 17.1 | 54.0 | none | 0 | ✅ | ✅ | 16 |
| 375×812 | 19.1 | 17.0 | 53.6 | none | 0 | ✅ | ✅ | 16 |
| 360×800 | 18.9 | 17.0 | 53.3 | none | 0 | ✅ | ✅ | 16 |
| 320×568 | 14.0 | 17.0 | 46.0 | none | 0 | ✅ | ✅ | 16 |
| 844×390 | 14.0 | 18.7 | 46.0 | none | 0 | ✅ | ✅ | 16 |
| 667×375 | 14.0 | 18.1 | 46.0 | none | 0 | ✅ | ✅ | 16 |

**No question screen scrolls at any viewport.** Answer text never below 17px; cards never below 46px. The 14px gap rows are the deliberate `max-height: 600px` compaction tier on genuinely short screens.

## 8. Result-screen visibility matrix (all five offers)

Offers tested every time: Reluctant Reader Adventure Kit, Adventure Learning Toolkit, Community Reading Kit, Meaningful Gift Guide, Partnership.

| Viewport | Submit visible w/o scrolling | Result scrolls | Regions | Submit h | Input h / px | 1 primary | Partnership form-free |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 1440×900 | ✅ all 5 | no | 0 | 52–59 | 49 / 16 | ✅ | ✅ |
| 1366×768 | ✅ all 5 | no | 0 | 52–59 | 49 / 16 | ✅ | ✅ |
| 1024×768 | ✅ all 5 | no | 0 | 52–59 | 49 / 16 | ✅ | ✅ |
| 768×1024 | ✅ all 5 | no | 0 | 52–59 | 49 / 16 | ✅ | ✅ |
| 430×932 | ✅ all 5 | no | 0 | 52–59 | 49 / 16 | ✅ | ✅ |
| 393×852 | ✅ all 5 | no | 0 | 52–59 | 49 / 16 | ✅ | ✅ |
| 390×844 | ✅ all 5 | no | 0 | 52–59 | 49 / 16 | ✅ | ✅ |
| 375×812 | ✅ all 5 | no | 0 | 52–59 | 49 / 16 | ✅ | ✅ |
| 360×800 | ✅ all 5 | no | 0 | 52–59 | 49 / 16 | ✅ | ✅ |
| 320×568 | ✅ all 5 | yes | **1** | 59 | 45 / 16 | ✅ | ✅ |
| 844×390 | ✅ all 5 | yes | **1** | 52 | 45 / 16 | ✅ | ✅ |
| 667×375 | ✅ all 5 | yes | **1** | 52 | 45 / 16 | ✅ | ✅ |

At the three short viewports the **submit is above the fold** but the two secondary links (Keep browsing / Start over) sit under **exactly one** internal scroll region — the brief's explicit allowance. Never nested; the close button is anchored to the non-scrolling dialog and stays pinned (44×44 / 48×48, hit-tested true at every viewport). Labels, consent sentence and both fields are present everywhere; nothing was removed.

## 9. Mobile visual-viewport centering

`visualViewport`-relative deviation, all 12 viewports: **0.0px vertical, 0.0px horizontal** (targets: ≤8px and ≤1px). Outer visible space ≥12px everywhere (16.1px at the tightest, 667×375). Radius 16px on all four corners. Verified centered **after an automatic open** (0px) and **after an orientation change while open** (844×390: 0px both axes, dialog inside viewport, close still visible and tappable, body still locked).

## 10. Consent-control layering

| Check | Result |
| --- | --- |
| Gear z-index / modal z-index | 9999 / **10000** |
| Element on top at the gear's centre while modal open | `bhp-quiz-modal__backdrop` (or a quiz option at 667×375) |
| `gearReceivesClicks` | **false** at every viewport |
| Gear still rendered (not disabled/removed) | yes — returns to normal when the modal closes |
| Consent banner/preferences overlay | unchanged at z-index 900000, still above the quiz, still answerable |
| Auto-open deferral while consent UI visible | untouched (`quiz-modal.js` byte-identical) |

## 11. Navigation breakpoint

| Header-inner width | Toggle | Desktop nav | Both visible |
| --- | --- | --- | --- |
| 1302 (vp 1366) | `display:none` | visible | **no** |
| 1136 (vp 1200) — just above | `display:none` | visible | **no** |
| 1096 (vp 1160) — just below | visible **44×57** | `display:none` | **no** |

Toggle click cycles `aria-expanded` false → true → false, menu opens and closes, `aria-controls="primary-navigation"`, focus outline 3px. Never both, never neither.

## 12. Homepage quiz-instance counts

| Page | Launchers | Modals | `[data-bhp-quiz]` | `#find-your-adventure` | Gateway | Dup IDs |
| --- | --- | --- | --- | --- | --- | --- |
| `/` | **1** | **1** | **1** | **1** (launcher wrapper) | 0 | 0 |
| `/books/`, `/about/`, `/teachers/`, `/complete-collection/`, product | 1 | 1 | 1 | 0 | 0 | 0 |
| `/find-your-adventure/` | 0 | 0 | 1 (the page itself) | 0 | 0 | 0 |
| `/cart/`, `/checkout/` | **0** | **0** | **0** | 0 | 0 | 0 |

## 13. Quiz behaviour regression

8s timer auto-open **PASS** (`open_reason: "timer"`); 40% scroll auto-open **PASS** (`scroll_40`); one auto-open per session **PASS**; manual launcher **PASS**; four Q1 routes and every Q2 branch intact; all 12 results correct with exactly one primary CTA; partnership form-free with `#contact`; Back and Start Over intact; **16/16 dismissals at 0px page-position drift** (close / Escape / backdrop / Keep browsing × four positions on a 14,500px page), focus returned to the launcher every time, body locked while open and released on close; focus trap wraps both directions (5 focusables, all inside the dialog); cart/checkout/account exclusions intact; no double buttons; **zero console errors**. **No Mailchimp contact was created — no form was ever submitted.**

## 14. Homepage and commerce smoke

Homepage hero: caption 19.1px clear at 320/390/430, covers loaded/proportional/uncropped, all three product links intact, no horizontal overflow. Sections render without the deleted gateway/quiz slots. Product page prices **$11.99 / $17.99 / $48.99**; `/books/`, `/complete-collection/`, `/cart/`, `/checkout/` all 200. 200% text zoom: exactly one scroll region, nothing clipped, no horizontal overflow, close visible and tappable. Reduced-motion rules present for both quiz and hero.

## 15. Local-to-served parity

**147/147 byte-identical.** Full-ZIP `wp theme install --force` from the verified backup with only changed files patched. `wp eval` → `ok`. PHP lint clean on all four changed PHP files. Cache purged.

## 16. Rollback

```bash
ssh -i ~/.ssh/id_ed25519 -p <port> <user>@<host> "cd <staging_doc_root>/wp-content/themes && rm -rf brave-hearts-theme-deploy-explorer-expedition-guides && cp -a ~/bhp-STAGING-backup-screenshotfixes-20260731/brave-hearts-theme-deploy-explorer-expedition-guides . && cd ../.. && wp sg purge --user=1"
```

## 17. Remaining concerns

1. **Real-iPhone Safari behaviour is NOT proven by this QA.** The automation browser has no Safari toolbars, so `100dvh` resolves identically to `100vh` here and the visual viewport never shrinks. Centering measured 0px, but the specific condition that produced the bottom-sheet screenshots — **toolbars showing then collapsing on a real iPhone** — cannot be reproduced in this environment. **Owner verification on the real device is required** before Part B is considered closed. The same applies to Part E's gear position, which is fixed-positioned relative to that same viewport.
2. **Screenshots cannot be produced here** (tool times out — project-long limitation). All evidence is DOM geometry and computed styles.
3. **320×568 / 844×390 / 667×375 keep one internal scroll region** on the result screen to reach the two secondary links. Submit is above the fold at all three; this is reported as a scroll, not as a no-scroll pass.
4. **The `.home #find-your-adventure` CSS block was deleted**, not just bypassed. If the inline homepage quiz is ever restored, that navy-section treatment must be restored with it.
5. **The audience-gateway component file is retained but unrendered.** Removing the module is a conversion-surface change the owner may want to weigh separately from the technical consolidation.
6. Staging is now **five** releases ahead of production (1.19.117 → 1.19.121); Homepage Phase 1a remains unreviewed.

## 18. Verdict

**READY FOR OWNER REVIEW**, with the explicit caveat in §17.1: Parts B and E are verified by measurement in emulation and by correct layering/z-index reasoning, but the real-iPhone Safari conditions that produced the original screenshots could not be reproduced here. Please re-check the quiz on the actual iPhone before treating those two as closed.
