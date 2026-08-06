# Homepage Conversion Phase 1a — Staging Release Record (theme 1.19.117)

**Status: staging only, awaiting owner review. PRODUCTION WAS NOT TOUCHED and remains theme 1.19.112.**
Owner-review URL: https://staging2.braveheartspublishing.com/

## Objective
Two of the four Phase 1 homepage improvements: **hero clarity** and **product-first section order**. Quiz consolidation, editorial compression and Learning Hub reduction are explicitly NOT in this release and are not claimed as done.

## Starting versions
Staging theme 1.19.113 → **1.19.117**. Bundle plugin **1.8.7**, unchanged and not deployed. Production **1.19.112** throughout.

## Backups
- `~/bhp-STAGING-1.19.113-backup-20260731-014141` (pre-Phase-1a)
- `~/bhp-STAGING-1.19.115-backup-20260731-021006` (pre-mobile-CTA-fix)

## Root causes fixed
1. **Zero product cards after the section move.** `$adventure_cards` and its supporting data (`$mariana_book`, `$everest_book`, `$amazon_book`, `$find_formats_for_destination`) were prepared *after* the section consuming them. Moving `#explore-world` above the editorial sections made the loop run before the data existed. Fixed by hoisting the whole preparation block above the hero render — dependency-ordered, exactly one copy, lookup rules/prices/formats/URLs/images/filters byte-identical.
2. **Oversized desktop H1.** The new explanatory H1 inherited a 92px display scale built for the two-word brand line and set over 4 lines (353px), inflating the hero to 1130px.
3. **Mobile primary CTA below the fold (706px at 320x568).** NOT caused by the book covers — they already render after the CTA. Caused by 92px hero padding-top, four ~45px stack gaps, and a 94px commercial subtext duplicating the eyebrow and supporting copy.
4. **Signature invisible on mobile.** `.home-hero__details` was `display: none` at 768px and below, and the new signature lives inside it.

## Files changed (whole Phase 1a effort)
- `front-page.php` — hero copy fallbacks, product-data hoist, single Complete Collection feature, `#explore-world` relocation, `#featured-books` removal, `/books/` section action.
- `style.css` — Phase 1a component CSS, homepage H1 scale, mobile hero compaction, signature preservation, version.

No other theme file, no template part, no JavaScript, no database value, no product, no plugin, and no Mailchimp object was changed.

## Final homepage section order
`home-hero → home-trust-proof → home-sales-paths (Complete Collection feature) → explore-world (3 adventure cards) → kirkus-credibility-home → home-audience-gateway → home-philosophy → …`

## Hero copy (authoritative PHP fallbacks; no `bhp_home_*` meta exists on the front page)
- Eyebrow: `REAL-WORLD ADVENTURE BOOKS FOR AGES 6–9`
- H1: `Adventure Books That Turn Curiosity Into Courage`
- Supporting: `Follow Charlotte and Henry from the Mariana Trench to Mount Everest and the Amazon—story-led adventures for family read-alouds and growing independent readers.`
- Signature: `Big Places. Brave Hearts.` (retained, visible, no longer the H1)
- Primary CTA: `GET THE COMPLETE COLLECTION` → `/complete-collection/`
- Secondary CTA: `FIND THEIR FIRST ADVENTURE` → `#explore-world`

## Before/after geometry
**Desktop 1440x900:** H1 92px / 4 lines / 353px → **54px / 3 lines / 180px**; hero 1130px → **956px**; primary CTA 827px → **653px**; page height 9599px → 9425px.

**Mobile 320x568:** primary CTA top 706px → **436px**, bottom 775px → **505px**, fully inside the 568px fold and clear of the 93px sticky header; signature hidden → **visible**; H1 32px / 3 lines; body 16px; CTA height 69px.

## Seven-viewport matrix (staging 1.19.117)

| Viewport | H1 | Lines | Clip | Body | CTA top→bot | In fold | Sig | Covers | Cards/Imgs/Prices/Links | Overflow | Dup IDs | Broken |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| 1440x900 | 54 | 3 | no | 20 | 653→707 | yes | yes | 3 | 3/3/6/6 | none | 0 | 0 |
| 1366x768 | 54 | 3 | no | 20 | 651→704 | yes | yes | 3 | 3/3/6/6 | none | 0 | 0 |
| 1024x768 | 45 | 3 | no | 20 | 752→805 | **no** | yes | 3 | 3/3/6/6 | none | 0 | 0 |
| 768x1024 | 39 | 3 | no | 20 | 729→783 | yes | yes | 3 | 3/3/6/6 | none | 0 | 0 |
| 390x844 | 32 | 3 | no | 16 | 410→479 | yes | yes | 3 | 3/3/6/6 | 0 | 0 | 0 |
| 320x568 | 32 | 3 | no | 16 | 436→505 | yes | yes | 3 | 3/3/6/6 | 0 | 0 | 0 |
| 667x375 | 37 | 3 | no | 20 | 721→775 | **no** | yes | 3 | 3/3/6/6 | 0 | 0 | 0 |

Zero console errors at every viewport.

## Landscape 667x375 — intentional accessible tradeoff
The CTA does not fit the 375px-tall initial viewport. Accepted because every stated condition holds: hero start visible and understandable, nothing hidden under the sticky header, text readable (H1 37px, body 20px), normal unobstructed vertical scrolling, no nested-scroll trap, CTA appears before the covers, CTA fully reachable and 54px tall, no overlap or clipping, no horizontal overflow. Forcing it into the fold would require sub-accessible text sizes or removing essential content.

**1024x768 falls in the same category** — CTA at 752px against a 768px viewport, 16px short, with all the same conditions satisfied. Recorded as a tradeoff, not a defect.

## 200% text
Tested at 320x568 by doubling the root font size (text-only enlargement proxy — true browser page zoom is not controllable from this environment). **No clipping (0 elements), no page-level horizontal overflow (0px)**, CTA reachable at 69px with an unclipped label, signature visible, 3 cards intact.

## Keyboard and focus
5 focusable controls in the hero, in DOM and visual order: primary CTA → secondary CTA → three cover links (all resolving to `/adventures-of-charlotte-and-henry-…`). Visible focus indicator confirmed. Anchor target `#explore-world` exists; primary CTA resolves to `/complete-collection/`.

## Reduced motion
No cover animations or transitions exist to suppress (0 animated cover elements), so nothing disappears or breaks under `prefers-reduced-motion: reduce`.

## Performance sanity (measured, warm cache)
At 768x1024: 63 requests, 6 image requests, ~78 KB transferred, DOMContentLoaded 1767 ms, load 1802 ms. Page height 9425px at 1440x900, down from 9599px at 1.19.115.

**LCP and CLS were NOT captured.** The PerformanceObserver entry types are supported in this environment, but no lab or field values were collected. They are not claimed here. No new render-blocking resource was introduced — all CSS changes are appended to the existing `style.css`; no new file, font or image asset was added.

## Regression evidence
Exactly one `#home-hero`, one `#home-sales-paths`, one `#explore-world`, zero `#featured-books`, zero duplicate IDs. Three adventure cards, three valid images, six live prices ($11.99 / $17.99 x3) and six valid product links at every viewport. Three hero covers. Product-data preparation exists exactly once and precedes its consumer (verified by character offset: defined at 3604, first consumed at 15043). PHP lint clean.

## Parity
Local ↔ staging: **147 / 147 files match** by md5.

## Rollback

    ssh -i ~/.ssh/id_ed25519 -p <port> <user>@<host> "cd ~/www/staging2.braveheartspublishing.com/public_html/wp-content/themes && rm -rf brave-hearts-theme-deploy-explorer-expedition-guides && tar xzf ~/bhp-STAGING-1.19.115-backup-20260731-021006/theme-1.19.115.tar.gz -C . && cd ~/www/staging2.braveheartspublishing.com/public_html && wp sg purge"

## Known limitations
- CTA is below the initial viewport at 1024x768 and 667x375 (documented tradeoff above).
- The signature renders *after* the CTAs rather than before them. It lives inside `.home-hero__details`, which the shared hero component renders after the actions; reordering requires a shared-component change, deliberately avoided in favour of a homepage-scoped fix.
- The 200% test used root-font scaling, not true browser page zoom.
- LCP/CLS not measured.
- Screenshots unavailable in this environment (long-standing `KNOWN_ISSUES.md` limitation); all evidence is measured DOM geometry.

## Not in this release
Quiz consolidation, Philosophy compression, Founder compression, Learning Hub reduction, and the final conversion band. All Phase 1b.
