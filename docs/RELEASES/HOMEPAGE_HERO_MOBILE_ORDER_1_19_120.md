# Homepage Hero — Mobile Reading Order (covers under the H1) — Staging 1.19.120

**Date:** 2026-07-31
**Environment:** staging2.braveheartspublishing.com **only**
**Theme:** 1.19.119 → **1.19.120**
**Production:** **1.19.112, untouched and not approved for change**
**Status:** READY FOR OWNER REVIEW

---

## 1. Opening state and stop-gate result

| Gate | Result |
| --- | --- |
| Canonical chain read | `START_HERE.md`, `AI_CONTEXT_INDEX.md`, `PROJECT_STATE.md`, `CURRENT_TASK.md`, `NEXT_TASK.md`, `DECISIONS.md`, `KNOWN_ISSUES.md`, `RUNBOOK.md`, `.claude/rules/*` |
| Local + staging = 1.19.119 | Confirmed (`style.css`, `wp theme list --status=active`) |
| Production = 1.19.112 | Confirmed on the production doc root |
| File drift | Full-theme `md5sum` diff local ↔ deployed staging: **IDENTICAL, 147/147** before any edit |
| DB drift | 6 published products, `page_on_front` = 24, **0** `bhp_home_*` options (so the PHP hero fallbacks are authoritative, matching the 1.19.117 record), 25 active plugins |
| Other active writer | None. A `.claude/worktrees/suspicious-einstein-5c5391` git worktree exists but is a **detached-HEAD copy last written 2026-07-29**, and `.claude/` is not part of the 147-file deploy set, so it can neither be written by nor ship with this release. Quiz files also matched their 1.19.119 checksums exactly. |

## 2. Backup location

`~/bhp-STAGING-backup-heroreorder-20260731/` — 147 files, 5.2M, verified at `Version: 1.19.119`.

## 3. Root implementation approach

A **real DOM move**, not a visual reorder.

The shared hero component gained one backward-compatible optional argument:

```php
'aside_after_title' => false,   // default — every existing caller is unchanged
```

When true, `$args['aside']` is rendered immediately after the `<h1>`; otherwise it renders where it always has, at the end of the hero content. The two placements are mutually exclusive guards over the **same single variable**, so the book-preview markup is emitted **exactly once** — no duplicate node, no hidden second copy, no separate desktop/mobile image or link sets. Verified on the served page: `bookPreviewCount: 1`, `bookCoverCount: 3`.

`front-page.php` opts in with `'aside_after_title' => true`. The component also adds a `home-hero--aside-after-title` class so CSS can target the case precisely.

**Why this does not disturb desktop.** The homepage hero content is a two-column CSS grid above 768px, and the preview is *explicitly placed* (`grid-column: 2; grid-row: 1 / 6`). An explicitly-placed grid item's position comes from its placement, not its DOM index, so moving the node changes nothing there — proven by measurement in §6.

**What actually needed CSS.** At ≤768px the hero collapses to a single column and the preview is already an ordinary in-flow grid item (`position: relative; grid-column: 1; grid-row: auto`). The DOM move alone therefore reorders it. The only genuine adjustment was its `margin-top: 78px`, which had been tuned for it being the *last* element below the CTAs; sitting between the H1 and the supporting paragraph it needed a balanced 20px/2px instead.

**No `order`, no absolute positioning, no transforms, no duplicate markup** were used to achieve the reorder — confirmed live: `cssOrderUsed: false`, `absPositioned: []` on every hero child at every mobile viewport.

### Second change: a pre-existing 320px clipping defect, found by measurement

While measuring, the hero's single grid track at 320px was found to be **284px wide inside a 244px container**. A `1fr` track takes an automatic minimum equal to its items' min-content contribution, and the widest item (a CTA button label) forced it wider than its own container. Every hero child then rendered 284px, running to x=328 on a 320px viewport, where the hero's own `overflow-x: hidden` silently clipped it — **the third cover, both CTAs and the H1 all lost their right edge, with no scrollbar to reveal them.**

This is **pre-existing** (the covers were already a grid child at that width in 1.19.119) but is fixed here because this pass promotes the covers to a primary hero element and the brief requires all three visible without horizontal scrolling. Fix, scoped to `≤380px` so 390px and wider keep their existing measured layout exactly:

```css
@media (max-width: 380px) {
  .home .home-hero--with-books .home-hero__content { grid-template-columns: minmax(0, 1fr); }
  .home .home-hero--with-books .home-hero__content > *  { min-width: 0; }
}
```

`minmax(0, 1fr)` caps the track at the container; `min-width: 0` lets items shrink into it. Covers scale proportionally (`flex-shrink` already permits it, `height: auto` keeps them uncropped) and CTA labels wrap instead of being cut off (`.btn` has no `white-space: nowrap`).

## 4. Files changed (3)

Confirmed by `diff -rq` of the deployed theme against the 1.19.119 backup — exactly three files differ:

| File | Change |
| --- | --- |
| `template-parts/components/hero.php` | New `aside_after_title` arg (default `false`); single-render conditional placement; `home-hero--aside-after-title` class; docblock |
| `front-page.php` | Passes `'aside_after_title' => true` (homepage only) |
| `style.css` | Mobile margin rebalance for the reordered preview; `≤380px` grid-track containment; `Version: 1.19.119 → 1.19.120` |

No JS changed. No quiz file changed. No product, WooCommerce, Mailchimp or database change.

## 5. Before / after mobile DOM order

Hero content children, measured on the served page at 390×844:

| Before (1.19.119) | After (1.19.120) |
| --- | --- |
| eyebrow | eyebrow |
| H1 | H1 |
| supporting paragraph | **book preview (3 covers)** |
| commercial subtext *(hidden ≤600px)* | supporting paragraph |
| CTAs (primary, secondary) | commercial subtext *(hidden ≤600px)* |
| details / signature | CTAs (primary, secondary) |
| **book preview (3 covers)** | details / signature |

Served DOM after: `eyebrow > H1 > BOOK-PREVIEW > text > subtext > actions > details`.
Rendered visual order (sorted by measured `top`): `eyebrow > H1 > covers > paragraph > CTAs > signature`.
**`domMatchesVisual: true`** at 320, 360, 390, 430 and 667 — i.e. the required order, with DOM and visual order in agreement.

## 6. Desktop non-regression evidence

The strongest available proof: on the live page the preview node was moved **back** to its old last position, geometry re-measured, then restored — and compared field by field.

| Viewport | Result |
| --- | --- |
| 1024×768 | `IDENTICAL_desktop_geometry: true`, `diffs: []` |
| 1366×768 | `IDENTICAL_geometry_with_vs_without_dom_move: true`, `diffKeys: []` |
| 1440×900 | `IDENTICAL_geometry_with_vs_without_dom_move: true`, `diffKeys: []` |

Compared elements: preview, H1, eyebrow, supporting text, actions, details, all three covers, and total hero height — to 2 decimal places. At 1024 for example the preview is `[618.31, 347.58, 406, 325.04]` **both** with and without the DOM move.

Desktop composition confirmed intact: preview in `grid-column: 2`, `grid-row: 1 / 6`, positioned right of the copy (`previewIsRightOfCopy: true`), covers 150/166/150 at 1024 and 186/200/186 at 1366/1440, no horizontal overflow, signature visible.

## 7. Viewport QA table

| Viewport | Hero order | DOM=visual | H1 clipped | 3 covers load | Cover links | Ratio delta | Covers in viewport | CTA | Signature | h-overflow | Dup IDs | Broken imgs | Console |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 1440×900 | desktop (covers right column) | n/a — explicit grid | no | 3 | ✅ | 0.26% | ✅ | ✅ | ✅ | no | 0 | 0 | clean |
| 1366×768 | desktop (covers right column) | n/a — explicit grid | no | 3 | ✅ | 0.26% | ✅ | ✅ | ✅ | no | 0 | 0 | clean |
| 1024×768 | desktop (covers right column) | n/a — explicit grid | no | 3 | ✅ | 0.26% | ✅ | ✅ | ✅ | no | 0 | 0 | clean |
| 768×1024 | eyebrow>H1>covers>para>subtext>CTAs>sig | ✅ | no | 3 | ✅ | 0.26% | ✅ | ✅ | ✅ | no | 0 | 0 | clean |
| 430×932 | eyebrow>H1>covers>para>CTAs>sig | ✅ | no | 3 | ✅ | 0.26% | ✅ 73..357 | ✅ | ✅ | no | 0 | 0 | clean |
| 390×844 | eyebrow>H1>covers>para>CTAs>sig | ✅ | no | 3 | ✅ | 0.26% | ✅ 53..337 | ✅ | ✅ | no | 0 | 0 | clean |
| 360×800 | eyebrow>H1>covers>para>CTAs>sig | ✅ | no | 3 | ✅ | 0.26% | ✅ 38..322 | ✅ | ✅ | no | 0 | 0 | clean |
| 320×568 | eyebrow>H1>covers>para>CTAs>sig | ✅ | no | 3 | ✅ | 0.26% | ✅ 18..302 | ✅ wraps, unclipped | ✅ | no | 0 | 0 | clean |
| 667×375 landscape | eyebrow>H1>covers>para>subtext>CTAs>sig | ✅ | no | 3 | ✅ | 0.26% | ✅ 191.7..475.7 | ✅ | ✅ | no | 0 | 0 | clean |

- **Ratio delta** = difference between each cover's rendered content-box ratio and its natural ratio, measured on untransformed layout boxes. Max **0.26%** (sub-pixel rounding) → covers are proportional and effectively **uncropped**. A first measurement suggested ~4% cropping; that was an artifact of `getBoundingClientRect()` on the decoratively rotated first and third covers and was corrected with `offsetWidth`/`offsetHeight`.
- **Cover links** all resolve to the three real product URLs (Mariana, Everest, Amazon paperbacks) with `aria-label="Explore <title>"`.
- **No duplicated downloads:** three distinct source files (`book1_mariana_trench_ebook_cover-417x640.jpg`, `Final-Ebook-Cover-4-10-26-Everest.jpg`, `The-Amazon-ebook-Cover.jpg`), one `<img>` each.
- **Layout shift:** `PerformanceObserver` with `buffered: true` reports **CLS = 0, 0 shift entries**. All three covers carry explicit `width`/`height` attributes and `loading="eager"`.

## 8. Accessibility results

| Check | Result |
| --- | --- |
| Keyboard order on mobile | Mariana → Everest → Amazon → "Get the Complete Collection" → "Find Their First Adventure". `TAB_MATCHES_LEFT_TO_RIGHT: true` |
| Covers precede CTAs in tab order | `blockSequence: cover > cover > cover > cta > cta` |
| Positive tabindex used | None (`anyPositiveTabindex: false`) |
| CSS `order` used anywhere in the hero | None (`cssOrderUsed: false`) |
| Absolute positioning / transforms used for the reorder | None on any hero child |
| Focus indicators | Rules present and unchanged: `.home-hero__book-stack a:focus-visible { outline: 3px solid var(--color-accent); outline-offset: 5px }` and `.btn:focus-visible { outline: 3px solid var(--color-focus); outline-offset: 3px }` |
| Duplicate IDs | 0 at every viewport |
| 200% text zoom | Order preserved, all hero children inside the viewport, H1/CTA/signature visible, covers still loaded, no horizontal overflow |
| Reduced motion | Rules present and parsed, including `.home .home-hero__book-stack a` transition/transform suppression |
| Semantics preserved | `role="group"` + `aria-labelledby` on the preview, per-cover `aria-label`, `screen-reader-text` titles, H1 id/`aria-labelledby` all unchanged |

A note on the middle cover: it carries a decorative `translateY(-8px)` lift, so a naive top-then-left sort reports it "first". Within the row the true reading axis is left-to-right, which matches DOM order exactly. No focusable element is reordered.

## 9. Quiz non-regression results

The quiz was not modified (no quiz file differs from its 1.19.119 checksum).

| Check | 390×844 | 1440×900 |
| --- | --- | --- |
| Q1 scrolls | no | no |
| Every Q2 route scrolls | no (all 4) | no (all 4) |
| Internal scroll regions on Q1/Q2 | 0 | 0 |
| All answers inside dialog | ✅ | ✅ |
| Back inside dialog on all Q2 | ✅ | ✅ |
| `scrollTop` on every question screen | 0 | 0 |
| Answer grid | 1×4 / 1×3 | 2×2 / 2×2 |
| Result screen | eyebrow `YOUR BEST NEXT STEP`, resource name, email form shown, **1** primary CTA, 2 fields, dialog 366px | same, dialog **640px** |
| Auto-open | `quiz_auto_trigger_armed` → `quiz_modal_opened: scroll_40`, Q1 opens unscrolled at `scrollTop 0` | — |

## 10. Shared-component caller verification

All seven callers were inspected before editing. **`front-page.php` is the only caller that passes `aside` at all**; the five page templates pass none, and `inc/class-bhp-campaign-landing.php` forwards a campaign config array that never sets it. A default-`false` flag therefore cannot alter them.

Confirmed on the served pages:

| Page | `--aside-after-title` class | Book previews | Covers | Hero child order |
| --- | --- | --- | --- | --- |
| `/about/` | absent | 0 | 0 | eyebrow > H1 > text > actions |
| `/books/` | absent | 0 | 0 | eyebrow > H1 > text > actions |
| `/contact/` | absent | 0 | 0 | eyebrow > H1 > text > actions |
| `/teachers/` | absent | 0 | 0 | eyebrow > H1 > text > actions |
| `/` (home) | **present** | **1** | **3** | eyebrow > H1 > BOOK-PREVIEW > text > subtext > actions > details |

`/explorer-passport/` returns **404** on staging — the template exists but no page is assigned to it. Pre-existing, unrelated to this change, and noted rather than "fixed".

Commerce smoke: `/books/` 200 (10 product links), Mariana paperback 200 with prices **$11.99 / $17.99 / $48.99**, `/complete-collection/` 200, `/find-your-adventure/` 200. Zero hero-preview leakage to any non-home page.

## 11. Local-to-served parity

**147/147 files byte-identical** between the working tree and deployed staging, verified by full `md5sum` comparison after the final deploy. Deployed via full-ZIP `wp theme install --force`, built on the server from the verified 1.19.119 backup with only the changed files patched in. `wp eval` → `ok` (no PHP fatal). SiteGround dynamic cache purged.

## 12. Rollback command

```bash
ssh -i ~/.ssh/id_ed25519 -p <port> <user>@<host> "cd <staging_doc_root>/wp-content/themes && rm -rf brave-hearts-theme-deploy-explorer-expedition-guides && cp -a ~/bhp-STAGING-backup-heroreorder-20260731/brave-hearts-theme-deploy-explorer-expedition-guides . && cd ../.. && wp sg purge --user=1"
```

Restores staging to 1.19.119 exactly. Production is not involved in any step.

## 13. Remaining concerns

1. **768×1024 (tablet portrait) also receives the new order.** The hero collapses to a single column at ≤768px, so the DOM move governs there too. The judgement made: the *approved composition* — covers in a right-hand column beside the copy — exists only at ≥769px, and it is fully preserved there (§6). At ≤768px the covers were previously just the last item in a vertical stack, not in a designed side position. If the owner wants tablet portrait to keep covers last, that is a one-line breakpoint change, but it would introduce a DOM/visual mismatch at that width, so it is flagged rather than assumed.
2. **The `≤380px` grid-containment fix touches shared homepage hero CSS**, not only the reordered case. It is deliberately scoped so 390px and wider are untouched, and it corrects a genuine pre-existing clipping defect — but it is a second change riding along with the primary task, and is called out as such.
3. **The preview's internal label uses `order: 2`** (pre-existing) so the caption paints below the cover stack while preceding it in DOM. It is a non-focusable `<p>`, so keyboard order is unaffected, and it was not introduced or altered here. Left alone to avoid changing the approved composition.
4. **At 320px the cover stack (284px) still overflows its 232px track symmetrically**, ending at 18..302 — fully inside the 320px viewport, unclipped, with no page scrollbar. Covers were left at 110/124/110 for legibility rather than being shrunk to fit the track.
5. **Screenshots remain unavailable** in this environment (the tool times out — a project-long limitation). All evidence above is DOM geometry, computed styles and served markup.
6. **Staging is now four releases ahead of production** (1.19.117, 1.19.118, 1.19.119, 1.19.120) and they would ship as one package. Homepage Phase 1a is still unreviewed.

## 14. Final verdict

**READY FOR OWNER REVIEW.**

Staging 1.19.120. Production untouched at 1.19.112 and **not** approved for deployment — none was requested.

Suggested review path: open the homepage on a phone (or a ~390px window). The order should read eyebrow → headline → three covers → supporting paragraph → both CTAs → "Big Places. Brave Hearts." Then widen past ~769px and confirm the covers return to the right-hand column exactly as before.
