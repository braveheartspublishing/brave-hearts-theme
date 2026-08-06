# Mobile experience release — theme 1.19.201

> ## ⭐ STATUS CORRECTION — 2026-08-05, later the same day: **THIS RELEASE IS NOW LIVE ON PRODUCTION.**
>
> **Deployed to production on 2026-08-05 with Andrew's approval.** Verified live the same day by
> `wp theme list --status=active` over SSH: **production active theme `1.19.201`**, staging
> `1.19.201`, bundle plugin `1.8.28` on both at the time of that reading.
>
> ⚠️ **ONE DEFECT IN THIS RELEASE WAS FOUND AFTER THE DEPLOY AND IS NOT FIXED HERE.** Deferring
> jQuery (§4.2) breaks any enqueued script that depends on jQuery and is not itself deferred. On
> the home page **seven** scripts depend on jQuery; three carry WordPress's own `defer` strategy
> and **four do not**. One of the four throws `ReferenceError: jQuery is not defined` in the
> browser console on every home-page load, on **production and staging alike**. Full diagnosis,
> evidence and the proposed fix: `KNOWN_ISSUES.md`.
>
> ⛔ **§3's claim of "zero console errors on any page checked" is therefore SUPERSEDED for the home
> page.** It is preserved below unedited rather than corrected in place, because what it recorded
> is what that session actually observed with the instrument it used, and hiding that would hide
> how the miss happened. **The instrument was the gap: a check for inline scripts calling jQuery
> was performed and passed; a check for ENQUEUED, NON-DEFERRED scripts DEPENDING on jQuery was
> not.** §4.2's docblock names the inline risk and not this one.
>
> **The status line below is preserved verbatim as the pre-deployment record.**

**Status (at the time of writing): BUILT · DEPLOYED TO STAGING · VERIFIED ON STAGING · NOT DEPLOYED TO PRODUCTION · NOT PUSHED.**

Production remains on theme `1.19.199` / bundle plugin `1.8.28`, verified live 2026-08-05 by
`wp theme list --status=active` and `wp plugin list` over SSH on both environments. **Production
deployment requires Andrew's explicit, current-turn approval and has not been requested or granted.**

---

## 1 · What prompted it

A PageSpeed Insights report of the **production** home page, **mobile**, captured 2026-08-05 15:16 MDT
(emulated Moto G Power, slow 4G, Lighthouse 13.4.1): Performance **53**, FCP 3.7 s, LCP **12.3 s**,
TBT 360 ms, CLS 0, Speed Index 7.1 s, plus render-blocking requests (est. 1,390 ms), image delivery
(165 KiB), unused/unminified CSS and JS (~464 KiB combined), forced reflow, six long main-thread
tasks, and two accessibility failures.

⚠️ **That report is not reproduced or claimed anywhere in this record.** It was captured with a
different instrument, from a different network, against a different environment. It is the *reason*
for the work, not evidence *about* the work.

---

## 2 · The instrument, named so every number here can be reproduced

**Lighthouse 12.8.2** driving local Chrome, `formFactor: mobile`, `screenEmulation` 412 × 823 at DPR
1.75 with `disabled: false`, simulated Slow 4G (1,638 kbps throughput, 150 ms RTT, 4× CPU slowdown),
run against **`staging2`**.

⛔ **Every metric below is STAGING LIGHTHOUSE. None is a PageSpeed Insights number, and none is a
production number.** Only staging-before versus staging-after is compared, because that is the only
comparison the evidence supports.

⭐ **The emulated viewport is attested by the report itself** (`configSettings.screenEmulation`,
`disabled: false`) rather than by a browser resize. Browser-automation viewport resizes are
documented as unreliable on this machine, so the instrument's own record of what it emulated is the
stronger evidence.

---

## 3 · Result — home page, mobile

| Metric | 1.19.199 (= production code) | 1.19.201 | |
|---|---|---|---|
| **Performance** | 56 | **82** | +26 |
| **Accessibility** | 93 | **100** | +7 |
| First Contentful Paint | 2.4 s | **1.7 s** | −0.7 s |
| Largest Contentful Paint | 5.8 s | **4.7 s** | −1.1 s |
| Total Blocking Time | 0 ms | 0 ms | — |
| **Cumulative Layout Shift** | 0.427 | **0.046** | −0.381 |
| Speed Index | 3.1 s | 2.2–2.7 s | − |
| Total transfer | 1,461 KB | **1,006 KB** | −455 KB |
| — images | 834 KB | **494 KB** | −340 KB |
| — stylesheets | 157 KB | **72 KB** | −85 KB |
| — scripts | 183 KB | 152 KB | −31 KB |
| Render-blocking resources | est. 400 ms | **0 ms** | — |

**Confirmed across two consecutive runs** (Performance 82 both times) rather than reported from a
single sample.

**Desktop, same build:** Performance **95**, Accessibility **100**, LCP 1.4 s, CLS 0.01, TBT 0 ms.

**Other sales-critical pages, mobile:** `/complete-collection/` Performance 82, CLS 0;
Mariana Trench product page Performance 80, CLS 0. **Zero console errors on any page checked.**

---

## 4 · What was changed, and why

### 4.1 The LCP diagnosis, which was not what the audit label suggested

Lighthouse resolved the LCP element to `section#home-hero`, and — via `prioritize-lcp-image`'s own
`debugData.initiatorPath` — its LCP **resource** to `assets/images/handoff/hero-ocean.webp`
(198.8 KB). The phase split of the 5.8 s baseline was **TTFB 19% · LOAD DELAY 54% · load 15% ·
render delay 13%**.

⭐ **Load Delay dominating while the preload audit already scored 1/1 means discovery was solved in
1.19.190 and SIZE was what remained.** The fix was fewer bytes, not a different hint. A pass that
read "improve image delivery" and added another preload would have achieved nothing.

### 4.2 Changes

* **Mobile resamples of four background photographs**, served only below 768 px:
  hero-ocean 198.8 → 95.8 KB · canopy-walk 272.0 → 90.8 KB · rainforest-bridge 320.1 → 104.0 KB ·
  summit-lake 84.0 → 29.1 KB. LANCZOS resamples of the same sources at the same aspect ratios.
  **No recolour, no crop, no restyle.** Blurring compresses far better (hero 54.1 KB vs 95.8 KB) and
  would be invisible under these overlays — **tested, measured and rejected, because it alters
  approved artwork.**
* **`.home-destinations` swapped in the same breakpoint** although it is below the fold, because it
  shares `hero-ocean` with the hero. Leaving it would have made a phone fetch **both** files.
* **The head preload is viewport-split** with `media`, for the same reason, and now **precedes the
  three font preloads** — `as=font` and `as=image fetchpriority=high` share Chrome's High priority,
  so source order broke the tie and the deciding image was queued behind 115 KB of typefaces.
  **No font family, weight, italic, `unicode-range`, `font-display` or file changed.**
* **Below-the-fold decorative art is held until window `load`** via `html.bhp-art-hold`. A CSS
  background is fetched when its rule matches the render tree; viewport position is irrelevant and
  there is no lazy-loading for it. **`hero-ocean` is deliberately not held.** Three failsafes: the
  class is added by script (no-JS keeps the old behaviour), added and removed in the same inline
  block, and released unconditionally after 4 s.
* **Commerce CSS/JS removed from pages that never paint it** — photoswipe, Stripe's checkout blocks
  CSS, `wc-blocks-style`, customer-reviews. **`woocommerce-general` and `woocommerce-layout` are
  kept**: the home page renders real product cards that inherit from them.
* **jQuery deferred on non-commerce surfaces only** — 230 ms of the baseline's 400 ms of blocking
  time, and the only non-deferred script on the page.
* **Quiz stylesheets moved off the critical path** with a `<noscript>` fallback.
* **Stylesheets ship comment-stripped** via `tools/build-css.mjs`, gated by
  `tests/test-style-minification.php`. `style.css` gzips **94,022 → 39,156 bytes**.
* **Consent banner no longer jumps**, which was 0.4167 of the 0.427 baseline CLS — 98% of it.
* **Seven accessibility failures fixed** across three pages.

---

## 5 · ⚠️ Visible changes Andrew should look at

Three colour changes were **forced** by WCAG 1.4.3 and are the only visual changes in this release.
**No pill, badge or brand colour moved — only label ink, plus one underline.**

| Element | Was | Now | Measured |
|---|---|---|---|
| "Best Value" pill label | white on gold | **jungle green on the same gold** | 2.22:1 → **7.07:1** |
| Format-card badge label | near-black brown on amber | **white on the same amber** | 3.49:1 → **4.73:1** |
| Breadcrumb links | grey, no underline | **darker, underlined** | 4.1:1 → **8.75:1** |

**The "Best Value" treatment is not an invention** — `.home-sales-paths__card--complete` already
paired exactly this gold with exactly this green. **The breadcrumb underline is not cosmetic**: only
some words in the trail are links, so `link-in-text-block` requires 3:1 against neighbouring text,
and no colour that also clears 4.5:1 on the cream background comes close (candidates measured 1.17,
1.38, 1.75). WCAG 1.4.1 accepts a non-colour cue instead.

---

## 6 · ⛔ What this release does NOT claim

* ⛔ **It is not on production, and no production approval was sought or given.**
* ⛔ **It has not been pushed.** No branch tracks a remote.
* ⛔ **It does not reproduce or refute the production PSI figures.** Different instrument, different
  network, different environment.
* ⛔ **No WooCommerce product, variation, price, coupon, stock, shipping, tax, payment or checkout
  record or setting was read for modification or changed on any environment.**
* ⛔ **No review, rating, testimonial, quote or aggregate score was added, altered or invented.**
  The accessibility work changed link labels and `role` attributes only.
* ⛔ **The forced-reflow and long-main-thread-task items were not separately addressed.** TBT
  measured 0 ms both before and after, so there was no measurable headroom; they are **not fixed,
  and not claimed as fixed.**
* ⛔ **The cache-lifetime item was skipped** — it needs server configuration outside the theme.
* ⛔ **Theme JavaScript is not minified.** Only CSS is. The scripts are deferred and TBT is 0 ms, so
  the payoff was small against real ASI risk. Remaining opportunity, honestly stated: ~39 KiB.
* ⛔ **Upload-directory images are still JPEG.** `modern-image-formats` still reports ~73 KiB
  available. Generating WebP derivatives is a media-library operation, not a theme change.

## 7 · ⛔ Pre-existing test debt, NOT introduced here

**Seven theme suites fail on the code currently deployed to production.** Measured directly: the
1.19.199 tree was restored onto staging, the full suite run, then 1.19.201 restored and re-run.

| | 1.19.199 | 1.19.201 |
|---|---|---|
| Theme suites | 28 pass / **7 fail** | 29 pass / **7 fail** |
| Bundle-pricing suites | — | 19 pass / 1 refused |

**Identical failure list both times:** `test-collection-purchase-path` ·
`test-content-intelligence-engine` · `test-cta-collision-detector` · `test-draft-package` ·
`test-header-collection-cta` · `test-lead-event-log` · `test-wave1-capture`.

⭐ **This release introduced none of them and fixes none of them.** The one suite it did break —
`test-amazon-review-showcase`, which pinned a literal `aria-label` string — was a **stale assertion
that passed on a genuinely defective build**, and it has been rewritten to assert the WCAG
requirement instead.

`test-purchase-validation-harness` **refuses to run by its own safety guard** because it creates and
deletes real WooCommerce orders. It was **not executed**, and that is correct.

---

## 8 · Rollback

**Staging:** reinstall the previous artefact with `wp theme install <zip> --force`, or restore the
pre-deploy tarball taken before this release. **The theme directory is deleted by `--force` before
extraction**, so a rollback is a full-artefact reinstall, never a file copy.

**If this is ever approved for production:** take the rollback tarball of the live theme directory
**first**, deploy, purge, then verify `wp theme list --status=active` reads the new version. The
tarball restore is the rollback path; nothing else is.

**Nothing outside the theme directory was changed**, so no database, option, product or
configuration rollback exists or is needed.

---

## 9 · Build and deploy notes

⚠️ **`docs/RUNBOOK.md`'s `git archive` list omitted `content-engine/` — 23 tracked files that are
LIVE in the theme directory on both environments.** Because `wp theme install --force` deletes the
theme directory before extracting, a strict-runbook ZIP **would have deleted the entire content
engine from the live site**. Verified live by `ls` of the active theme directory, then corrected in
the RUNBOOK. `style.min.css` was added for the same reason, and `tools/` is deliberately excluded.

**Artefact:** 462 entries, 414 file entries against 404 files live — a superset, so `--force`
deletes nothing. Zero backslash entries. Ten `*.min.css`. Zero `tools/`.
