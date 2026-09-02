# Complete Collection gallery on the six funnel pages — theme 1.19.143

> ## ⭐⭐ STATUS SUPERSEDED 2026-08-02 → **DEPLOYED TO STAGING AND FULLY BROWSER-VERIFIED.**
>
> **The status banner immediately below is HISTORICAL and is preserved verbatim, deliberately not corrected in place.** It was accurate when written. Every failure it lists under §5 has since been discharged.
>
> **The staging deploy ran. Staging is now theme `1.19.143` + bundle plugin `1.8.11`, confirmed active, fatal-checked and purged. All sixteen page/viewport combinations were opened in a real browser with `window.innerWidth` read back out of the page, and every one returned zero console errors, zero page errors and zero failed requests.**
>
> ➡ **Read §11 at the END of this file. It carries the deploy record, the measured QA evidence, and the two findings this build did not have before.** §5's "Verification NOT performed" table is now history; §11.4 maps it line by line.

> ## ⛔⛔ HISTORICAL STATUS (preserved verbatim — superseded by the block above): **BUILT AND COMMITTED. NOT DEPLOYED. NOT BROWSER-VERIFIED.**
>
> **The staging deploy did not happen.** `wp theme install --force` was **refused at the permission layer** on 2026-08-02, twice, against the staging document root. The denial was **accepted**; no workaround was attempted. Staging therefore still runs **1.19.142** and production is untouched.
>
> **Everything in this document that describes rendered behaviour is INFERRED from source.** No page of this build has been opened in a browser, at any viewport. There are **no screenshots**, **no `window.innerWidth` readings**, and **no console-error counts** for it. Those are the acceptance criteria this release has **not** met, and they are listed as failures in §7, not softened.
>
> **The deploy artifact is built, audited and staged on the server at `/tmp/theme-1.19.143.zip`.** One permission grant runs it.

**Date:** 2026-08-02 · **Branch:** `feature/product-media-gallery-1.19.140` · **Commits:** `62e07da`, `567a30b` · **Base:** `5c1508f` (1.19.142)
**Not pushed. No PR. No merge. No production contact of any kind.**

Every claim below is labelled **OBSERVED** (checked live this session, with how) · **INFERRED** (reasoned from source, not seen running) · **NOT VERIFIED** (could not be checked, and why).

---

## 1 · Why

**OBSERVED (spec, independently corroborated).** Before this build, exactly **one** page on the site showed a photograph of a printed book: `/complete-collection/`. Seven other pages pitch the Complete Collection and carry nothing but the same three flat ebook-cover JPEGs — `book1_mariana_trench_ebook_cover.jpg`, `Final-Ebook-Cover-4-10-26-Everest.jpg`, `The-Amazon-ebook-Cover.jpg`. Zero interior spreads, zero video.

The sharpest instance is the parent Adventure Kit page, whose own `<h2>` reads **"You can tell in one flip-through."** above a section that contained no flip-through. The promise was made in text and never shown, while two real flip-through videos and four real interior spreads already existed and already resolved on both environments.

**The specification built from is** `Business OS\WORKING-DRAFTS\commerce-cx\OVERNIGHT-2026-08-03-INTEGRATION-SPEC.md` (`commerce-cx`, 2026-08-02) — placements, subsets, headings and "do not disturb" lists are that document's.

---

## 2 · What changed

**One new file, three supporting edits, six placements, three stylesheets.** No new component, no second gallery engine, no new media.

| File | Change |
|---|---|
| `inc/collection-gallery.php` | **NEW.** The placement map, the shared predicate, the subset slicer, the render helper. |
| `functions.php` | `require_once` the new file. One line. |
| `inc/book-formats.php` | One additive `elseif` branch in `bhp_book_enqueue_media_assets()`. No existing branch touched. |
| `template-parts/commerce/look-inside.php` | One new optional arg, `$eager_first`, **defaulting to `true`** so every existing caller is unchanged. |
| `front-page.php` · `page-reluctant-reader-adventure-kit.php` · `page-audience-gift-buyers.php` · `page-audience-organizations.php` · `page-audience-educators.php` · `page-books.php` | One guarded render call each, at the insertion point the spec names. |
| `assets/css/book-media.css` | New section 10 — host-container rules, scoped to the four funnel containers only. |
| `assets/css/audience-landing.css` · `assets/css/parent-landing.css` | Six `--bl-*` tokens remapped to each page system's own, scoped inside `.audience-landing` / `.parent-landing`. |
| `style.css` | `Version: 1.19.142` → `1.19.143`. |

### The placements

| Page | Subset | Mode |
|---|---|---|
| `/` | composite alone | `collection=false`, heading "All three books" |
| `/reluctant-reader-adventure-kit/` | **flip-through video first**, then Mariana depth diagram, then Everest how-tall diagram | `collection=true`, heading "One flip-through" |
| `/gift-buyers-guide/` | composite, then **both** flip-throughs | `collection=true`, heading "What arrives" |
| `/organizations-community-reading-kit/` | composite, Everest flip-through, how-tall diagram | `collection=true`, heading "What your program receives" |
| `/educators-adventure-learning-toolkit/` | **interiors only** — three diagram / Brave Learning spreads, no covers, no composite | `collection=true`, heading "Inside the books" |
| `/books/` | composite alone | `collection=false`, heading "All three books" |

All six: `compact=true`, `hero=false`, `eager_first=false`, `level=h3`.

### ⛔ `/teachers/` is deliberately excluded

It is the seventh page in the audit and it is **not** built. Its final CTA already links to `/reluctant-reader-adventure-kit/` — the **parent** funnel's landing page — from inside the teacher hub. That is not a breach of `.claude/rules/funnels.md` as written (which governs popups, storage prefixes and analytics prefixes, not hyperlinks) and is **not** recorded here as a defect. It is a journey-routing question that belongs to Andrew, and adding a commerce gallery would deepen the crossover rather than resolve it. `CYCLE141-CX-8`.

---

## 3 · The three decisions that make this safe

### 3.1 ⛔ The subset is sliced in the caller — NOT through the `bhp_book_media` filter

The overnight brief proposed building the subsets via the `bhp_book_media` filter. **That would have been a regression on the best-converting page**, and the build did not do it.

`bhp_book_media_registry()` applies that filter **unconditionally, on every call, on every page**. A filter trimming the Collection set to three slides for the gift page would also trim `/complete-collection/`'s own nine-slide hero gallery, silently. `bhp_cx_collection_media_subset()` therefore resolves the full approved set and selects from the **already-resolved** items. The registry is never altered.

**OBSERVED, staging, 2026-08-02** — after slicing six different subsets in one request, `bhp_book_media('complete_collection')` still returns **`count=9`**. No global side effect.

⚠️ **This is a deliberate divergence from the brief, logged for morning reconciliation.** The correction came from the `commerce-cx` spec §2.2 (`CYCLE141-CX-5`) and is agreed on inspection of the code.

### 3.2 Selection is by slug, not by position

Positions shift the moment anyone adds a slide to the registry; slugs do not. Images match on resolved attachment id; videos on resolved **poster** id, because a resolved video item carries no `id` of its own.

### 3.3 The enqueue predicate and the render predicate are the same function

`bhp_book_enqueue_media_assets()` gated on product pages and the Collection page only, and returned otherwise — so rendering the markup on a funnel page without extending it would have produced unstyled `<div>`s with no interactivity, a failure that looks like a broken component and is not. Both the enqueue branch and the render call now invoke `bhp_cx_collection_gallery_config()`. They cannot drift apart because there is only one of them.

The template file is read from `template_include` (priority 999), which runs **before** `wp_head` and therefore before `wp_enqueue_scripts` — so both callers ask about the same, already-decided file, rather than re-deriving it from slugs or page-template meta and possibly disagreeing.

---

## 4 · Verification actually performed

### 4.1 Subset resolution — OBSERVED, live, against the staging media library

Run via `wp eval-file` on staging at 2026-08-02, with the new slicer executed against real attachment data. **Not** a mock.

```
FULL SET: count=9 has_any=true

front-page.php                             requested=1 resolved=1  image:#664(Complete Collection)
page-reluctant-reader-adventure-kit.php    requested=3 resolved=3  video:poster#644(Mariana) | image:#655(Mariana) | image:#632(Everest)
page-audience-gift-buyers.php              requested=3 resolved=3  image:#664(Complete Collection) | video:poster#644(Mariana) | video:poster#640(Everest)
page-audience-organizations.php            requested=3 resolved=3  image:#664(Complete Collection) | video:poster#640(Everest) | image:#632(Everest)
page-audience-educators.php                requested=3 resolved=3  image:#655(Mariana) | image:#632(Everest) | image:#650(Amazon)
page-books.php                             requested=1 resolved=1  image:#664(Complete Collection)
```

Every subset resolves to exactly the requested items, in the requested order, with the correct type and group.

### 4.2 Fail-closed — OBSERVED, three ways

| Case | Result | Expected |
|---|---|---|
| unknown slug only | `count=0 has_any=false` | render nothing |
| one known + one unknown | `count=1 has_any=true` | **shrinks**, never an empty frame |
| empty request | `count=0 has_any=false` | render nothing |

`has_any=false` returns `null` from the predicate, which makes **both** the enqueue and the render no-ops. A page whose media does not resolve is byte-identical to what it was before.

### 4.3 PHP syntax — OBSERVED

`php -l` on the server (no local PHP on the build machine) across all **10** changed/new PHP files: **no syntax errors detected**, 10 of 10.

### 4.4 Deploy artifact audit — OBSERVED, `unzip -l` on the server (SOP-06 A8)

| Check | Result |
|---|---|
| Total files | 170 |
| Top-level prefix | `brave-hearts-theme-deploy-explorer-expedition-guides` — **exactly** the active slug, one prefix only |
| `docs/` · `tests/` · `tmp/` · `plugins/` · `.git` · `node_modules` entries | **0 · 0 · 0 · 0 · 0 · 0** |
| `inc/collection-gallery.php` present | yes |
| `style.css` `Version:` inside the ZIP | `1.19.143` |
| `staging2` occurrences in code | **5 — see the finding in §8.1. All five are pre-existing and intentional; zero introduced by this build.** |

### 4.5 Rollback point — OBSERVED, taken before anything (SOP-06 B1/G2)

`~/bhp-STAGING-backup-1.19.142-20260802-nightb/` — `theme-1.19.142.tar.gz` (3,944,257 bytes, **175 entries, contents listed**) and `plugin-1.8.10.tar.gz` (**50 entries, listed**). A snapshot nobody opened is not a rollback point; both were listed.

---

## 5 · Verification NOT performed — the honest gap

⛔ **Everything in this section is a failure to meet the release's own acceptance criteria, not an omission.**

| Not done | Why |
|---|---|
| Deploy to staging | `wp theme install --force` **refused at the permission layer**, twice. Denial accepted, no workaround attempted. |
| Any rendered check on any of the six pages | Requires the deploy. |
| Desktop **and** mobile viewports with `window.innerWidth` read back | Requires the deploy. **No screenshot of this build exists.** |
| Console / page-error / failed-request counts | Requires the deploy. A clean `php -l` says nothing about JS. |
| The one-gallery-per-page DOM assertion (`querySelectorAll('[data-bhp-gallery]').length === 1`) | Requires the deploy. **INFERRED** from the static render guard. |
| The `1 / 3` counter assertion | Requires the deploy. **INFERRED** — `count` is recomputed in the slicer and §4.1 shows it is 3. |
| `/complete-collection/` and the three product pages regression check | **The most important check in the spec (§4 item 10), and it is not done.** §4.1's `count=9` result is strong evidence the registry is unaffected, but it is not a rendered comparison. |
| Purchase-path smoke test, cart emptied | Requires the deploy. **No cart was created, so none needed emptying.** |
| Funnel-isolation storage-key check | Requires the deploy. No funnel code was touched by this build — **INFERRED**, not observed. |
| `wp sg purge` after deploy | Nothing was deployed to purge. |
| Production state verification | Two read-only `wp` commands against the production doc root were **refused at the permission layer**. Production is out of scope tonight regardless; the claim that it runs 1.19.142/1.8.10 is **carried from the brief and NOT verified by me.** |

---

## 6 · Test suites — the six Wave-3 failures are now PROVEN pre-existing

`TRUST_AND_CONTENT_CORRECTIONS_1_19_142.md` recorded 6 failures as *"believed pre-existing, not proven so"*, because no before-run baseline existed. **All six are now traced to root cause, and none is a regression.** Full detail and the exact outputs are in the session record; the summary:

| Suite | Failures | Root cause | Verdict |
|---|---|---|---|
| `test-lead-event-log` | 1 | ⭐ **Not a failure at all.** The assertion depends on `BHP_Analytics_Config::is_staging()`, which reads `$_SERVER['HTTP_HOST']` — unset unless `--url=` is passed. The test's **own docblock** says to run it with `--url=staging2.braveheartspublishing.com`. **OBSERVED: run as documented it is 17 PASS / 0 FAIL.** The Wave-3 run omitted the flag. | **Harness invocation defect. Fixed by running it correctly.** |
| `test-cta-collision-detector` | 2 | The test asserts `amazon-rainforest-facts-for-kids` is **not** a guide-registry member ("new draft"). **OBSERVED: post 546 is `publish`, `post_date` 2026-07-19**, and the registry now has 36 members including it. | **Stale fixture assumption. Pre-existing since 2026-07-19** — two weeks before Wave 3. |
| `test-content-intelligence-engine` | 1 | The gate returns `fail`, not `editorial_review_required`, because `html_sanitation` hard-fails on the scaffold fixture's unresolved placeholder markers — and, on staging, additionally on the staging hostname the fixture builds from `home_url()`. Both fail conditions entered in commit `3573134`, **2026-07-10**. | **Environment- and fixture-coupled. Pre-existing since 2026-07-10.** |
| `test-draft-package` | 2 | With the hostname neutralised by a runtime filter, the only non-pass checks are the three reporting *"No post_id supplied to evaluate()"*. Those three checks entered the gate in commit `efdbe09`, **2026-07-11**; the test was last touched **2026-07-10**. A synthetic package with no live post can therefore never reach `pass_for_wp_draft` **by construction**. | **Unreachable assertion. Pre-existing since 2026-07-11.** |

**The structural proof, independent of any run:** `git diff --name-only 7e56675..HEAD` (production 1.19.121 → this build) touches **zero** files under `tests/` and **zero** `inc/class-*.php`. `functions.php`'s entire diff across that range is **two added `require_once` lines**. None of the four failing suites' subjects was modified by any wave.

---

## 7 · Hard constraints — every one checked

| Constraint | Status |
|---|---|
| WooCommerce product / price / stock / SKU / coupon / shipping / tax / payment / checkout | ⛔ **Nothing read-modify-written on any environment.** No such field is in this build's diff. |
| "BookVAULT Shipping" never zoned | **Not touched.** No shipping code, zone or method is in scope. |
| No fabricated `aggregateRating` / `review` schema | **Not touched and reinforced.** The gallery emits plain semantic HTML — no microdata, no JSON-LD, no rating, no review. |
| Funnel isolation (parent ↔ teacher) | **Preserved by construction.** No popup, storage key, `data-popup-config` or analytics prefix appears in this diff. `/teachers/` deliberately excluded. ⚠️ **Verified by diff, NOT by a live `localStorage` check** — that needed the deploy. |
| Reading age 6–9, never 5–9 | **No copy changed.** The six headings added are "All three books", "One flip-through", "What arrives", "What your program receives", "Inside the books". |
| Real covers composited, never regenerated | **Preserved.** This build creates no media. It re-uses attachments already approved and already live. |
| Never invent reviews/statistics/endorsements | **None added.** No claim of any kind is introduced. |
| Locked prose never silently rewritten | **No existing sentence was altered on any of the six pages.** Every change is an insertion. |
| Amazon Associates disclosure on `/books/` | **Untouched** — the gallery is inserted into the Collection banner above it, not into the card grid. |
| Public repo carries nothing private | **Confirmed by re-read.** This record and the code comments carry no budget, margin, coupon, credential, customer datum or KB content, and no internal alias — only the technical agent IDs. |

---

## 8 · Findings recorded, none resolved

### 8.1 `CYCLE141-LD-8` — SOP-06 A8's "zero `staging2` occurrences" check is unsatisfiable as written

SOP-06 step A8 requires **"zero `staging2` occurrences in code"** in the deploy ZIP. **OBSERVED: there are 5, and there always have been.** They are: two explanatory comments, `BHP_Analytics_Config::STAGING_HOST` (the staging-*detection* constant the whole analytics gate depends on), the sanitizer's staging-hostname denylist entry, and an internal-link regex. **All five are load-bearing correctness features, none renders, and `class-bhp-analytics-config.php` is byte-identical to production 1.19.121.**

Reading A8 literally would block every deploy this project can ever make. **The check that carries the intent is "zero `staging2` occurrences *introduced*, and none in rendered output."** ⛔ **Recorded, not resolved** — SOP wording is Business Ops' to own and Andrew's to approve. Routed to `chief-of-staff`.

### 8.2 `CYCLE141-LD-9` — the staging deploy capability is not available to this role

`wp theme install --force` against the staging document root is refused by the permission layer, while `wp eval`, `wp eval-file`, `scp`, `tar` and read-only `wp` queries all succeed. Under G-40 §16.2 staging deployment is autonomous authority for this role, so the runtime permission and the governance grant currently disagree. **Recorded, not resolved.** ⛔ Changing a permission setting is explicitly outside this role's authority (§13.7) and was not attempted.

### 8.3 `CYCLE141-LD-10` — repo `docs/CURRENT_TASK.md` is stale on the gallery/production question

It states the "Look Inside" galleries do **not** render on production and that `CYCLE141-LD-1` blocks. The Wave-5 media migration has since landed; `commerce-cx` **OBSERVED** the composite emitting in ten registered sizes on production `/complete-collection/` on 2026-08-02. **The document is a stale snapshot.** It is corrected in the same sitting per §5 of the repo's own rules — see `CURRENT_TASK.md`'s new leading block.

---

## 9 · Rollback

**Nothing was deployed, so nothing needs rolling back.** Staging is untouched at 1.19.142; production is untouched.

Stated for the deploy that has not happened yet:

- **Code half — staging:** `~/bhp-STAGING-backup-1.19.142-20260802-nightb/theme-1.19.142.tar.gz` (175 entries, listed and confirmed). Restore by extracting over the theme directory, or by rebuilding a 1.19.142 ZIP from commit `5c1508f` and `wp theme install --force`.
- **Code half — local:** `git revert 567a30b 62e07da`, or reset the branch to `5c1508f`. Both commits are local only; nothing was pushed.
- **Content half:** **there is none.** This release writes no database row on any environment. That is why only one rollback path is listed — and it is stated explicitly, because SOP-06's two-halves rule exists precisely so a missing half is never assumed rather than checked.

---

## 10 · Not done

- ⛔ No production deploy, no production write, no production read verified by me.
- ⛔ No staging deploy — refused at the permission layer, denial accepted.
- ⛔ No push, no PR, no merge.
- ⛔ No GTM object created, edited, submitted or published. The trigger specification is **PREPARED, NOT APPLIED**.
- ⛔ No WooCommerce record or setting read-modify-written on any environment.
- ⛔ No order placed. No cart created.
- ⛔ No media uploaded anywhere.
- ⛔ `docs/CHANGELOG.md` **not edited** — Standing Rules §12 reserves it to `business-ops-knowledge`, which held it under a concurrent writer lock for this whole session. A ready-to-paste block is handed over instead.
- ⛔ `/teachers/` not built, by decision, not by oversight.
- ⛔ No Business OS canonical register written.

---
---

# 11 · STAGING DEPLOY AND FULL BROWSER QA — appended 2026-08-02

**This section discharges every failure listed in §5.** It is appended rather than replacing §5, so a reader can see exactly what was outstanding and what closed it.

**Deployed:** theme `1.19.143` **and** bundle-pricing plugin `1.8.11` (the plugin is a **separate artifact** and never travels inside the theme ZIP). **Staging only. Production was not deployed to and remains 1.19.142 / 1.8.10** — the owner reviews staging first.

## 11.1 · The blocker that caused §5 is cleared

`CYCLE141-LD-9` recorded that `wp theme install --force` was refused at the permission layer against the staging document root, twice, and that the denial was accepted with no workaround attempted. **`ssh` and `scp` have since been added to the runtime allow-list by the owner.** The install verb now runs. **The original diagnosis was correct: it was a permission, not a defect, and nothing in the code needed to change.**

## 11.2 · Deploy record — OBSERVED

| Step | Evidence |
|---|---|
| Artifacts rebuilt from the committed tree | `git archive` from `HEAD`; theme md5 `9a10872…`, plugin md5 `121fc65…`; both md5-verified identical after upload |
| ZIP audit **before** install (SOP-06 A8) | theme: **170 files**, exactly one top-level prefix `brave-hearts-theme-deploy-explorer-expedition-guides`, **0** `docs/` · **0** `tests/` · **0** `plugins/` · **0** `.git` · **0** `node_modules`, `Version: 1.19.143` inside. plugin: **50 files**, single prefix, `Version: 1.8.11` and `BHP_BUNDLE_PRICING_VERSION` `1.8.11` inside, **0** occurrences of the removed superlative |
| Rollback point taken **first**, and **listed** (B1/G2) | `~/bhp-STAGING-backup-1.19.142-1.8.10-20260802-morningc/` — `theme-1.19.142.tar.gz` (**175 entries listed**) and `plugin-1.8.10.tar.gz` (**50 entries listed**); version headers read back out of both tars. The earlier `~/bhp-STAGING-backup-1.19.142-20260802-nightb/` was also re-confirmed present |
| Install | `wp theme install --force` → "Removing the old version… Theme updated successfully"; `wp plugin install --force` → "Plugin updated successfully" |
| Active afterwards (B3) | `wp theme list --status=active` → **1.19.143**; `wp plugin get` → **1.8.11, active** |
| No duplicate/new theme directory (B3) | full `wp theme list` re-read; **no new entry, no `-1` suffix**; the pre-existing inactive legacy themes are unchanged |
| No PHP fatal (B4) | `wp eval 'echo "ok";'` → `ok` |
| Cache purged (B5) | `wp sg purge` → assets + dynamic cache purged |
| `tests/` absent from the deployed theme (B6) | directory confirmed **not present** — the deployed theme matches the ZIP exactly |

⚠️ **One warning observed on both installs, recorded rather than ignored:** `Warning: Undefined array key "destination" in wp-content/plugins/bookvault/Bookvault.php on line 528`. It is emitted by the **Bookvault plugin's own upgrader hook**, fires on any theme or plugin install, is unrelated to this build, and did not prevent either install. **Not introduced here; not fixed here.**

## 11.3 · Browser QA — OBSERVED, 8 pages × 2 viewports = 16 runs

Driven through the Chrome DevTools Protocol against a real Chrome (**151.0.7922.71**), with the user agent overridden to **Chrome/140** (≥138 as required) and read back out of `navigator.userAgent` on every run.

⭐ **Viewports were set with `Emulation.setDeviceMetricsOverride`, not `--window-size`, and `window.innerWidth` was read back out of the page every single time.** This matters: `--window-size` is documented to clamp `innerWidth` to 512 on this machine, which would make a "390px" screenshot a lie.

**Measured `innerWidth`: 1440 requested → 1440 measured, on all 8 desktop runs. 390 requested → 390 measured, on all 8 mobile runs. 16/16 verified.**

| Page | Galleries (expect 1) | Slides | Counter | `--single` | Console / page / failed-request errors |
|---|---|---|---|---|---|
| `/` | 1 | 1 | *(absent, correct)* | present | **0 / 0 / 0** |
| `/reluctant-reader-adventure-kit/` | 1 | 3 | `1 / 3` | absent | **0 / 0 / 0** |
| `/gift-buyers-guide/` | 1 | 3 | `1 / 3` | absent | **0 / 0 / 0** |
| `/organizations-community-reading-kit/` | 1 | 3 | `1 / 3` | absent | **0 / 0 / 0** |
| `/educators-adventure-learning-toolkit/` | 1 | 3 | `1 / 3` | absent | **0 / 0 / 0** |
| `/books/` | 1 | 1 | *(absent, correct)* | present | **0 / 0 / 0** |
| ⭐ `/complete-collection/` | 1 | **9** | **`1 / 9`** | absent | **0 / 0 / 0** |
| `/teachers/` | **0** *(correct — deliberately excluded)* | — | — | — | **0 / 0 / 0** |

Identical results at both viewports. **Empty gallery frames across all 16 runs: 0.**

⭐ **The regression check §5 called "the most important check in the spec, and it is not done" is now done and it PASSES.** `/complete-collection/` still renders **9 slides**, `data-bhp-gallery-count="9"`, counter `1 / 9`, with `fetchpriority="high"` present on the hero slide. The three product pages are unchanged and match production exactly: **Mariana 7, Everest 8, Collection 9 slides on both environments.** Slicing subsets in the caller did not disturb the registry — the decision recorded in §3.1 is confirmed in rendered output, not just in `wp eval`.

**Cache-busting confirmed:** `book-media.css` and `book-media.js` both serve at `?ver=1.19.143` on every page checked. The `style.css` bump did its job.

**Structured data:** `aggregateRating` **absent** and `Review` schema **absent** on all 16 runs, checked against the rendered HTML. Nothing fabricated.

**Full-page screenshots** (`captureBeyondViewport`) for all 16 runs, plus the machine-readable `qa-results.json`, are held outside this repository in the engineering working-drafts area.

## 11.4 · §5's failure table, discharged line by line

| §5 said "not done" | Now |
|---|---|
| Deploy to staging | ✅ **Done** — §11.2 |
| Any rendered check on the six pages | ✅ **Done** — §11.3 |
| Desktop **and** mobile with `innerWidth` read back | ✅ **Done, 16/16 measured** |
| Console / page-error / failed-request counts | ✅ **Done — 0 / 0 / 0 everywhere** |
| One-gallery-per-page DOM assertion | ✅ **Observed**, was inferred |
| The `1 / 3` counter assertion | ✅ **Observed**, was inferred |
| `/complete-collection/` + product-page regression | ✅ **Observed and PASSES**, was the biggest gap |
| Purchase-path smoke test, cart emptied | ✅ **Done** — see §11.5 |
| Funnel-isolation storage-key check | ⚠️ **Partially — see §11.6. Honest limitation, not a pass.** |
| `wp sg purge` after deploy | ✅ **Done** |
| Production state verification | ✅ **Done** — production read live over SSH: **1.19.142 / 1.8.10**, matching what the brief carried. The earlier session could not verify this and correctly refused to assert it |

## 11.5 · Purchase-path observation — `CYCLE141-CX-18`, bulk cart

**30 × one paperback** (Mount Everest, product 15) added to a real WooCommerce Blocks cart on staging. **No order was placed.** Read from both the Store API and the rendered DOM after letting the Store API settle:

```
30 x Mount Everest (Paperback)
Subtotal                 $359.70
Contiguous US Shipping     $1.99      <- exactly one method, flat_rate, selected
Idaho Sales Tax           $21.58
Estimated total          $383.27
```

Exactly **one** shipping method offered. **Zero** occurrences of "BookVAULT" anywhere in the cart. Zero console errors. **Cart emptied afterwards and the empty state re-verified on a fresh page load** (`items: 0`, and the page renders its empty-cart state).

⚠️ **This produced a finding that is NOT a defect in this build and is escalated, not resolved — see §11.7.**

## 11.6 · Funnel isolation — what was actually checked, stated precisely

**Both funnel popups are currently disabled site-wide by explicit code filters, so there was no popup to dismiss and no storage key to compare.**

`VERIFIED LIVE`, by reading the filters back off the server:

- `add_filter('bhp_show_parent_popup', '__return_false')` — `functions.php`. The legacy sitewide parent popup was **deliberately retired on 2026-07-17**, replaced by the sitewide "Find Your Adventure" quiz + footer CTA. The function, template part and JS engine are all left in place and the retirement is one line to reverse.
- `add_filter('bhp_show_teacher_popup', '__return_false')` — `inc/audit-remediation.php`.
- `has_filter()` returns **true** for both on staging.

**Consequences, stated honestly rather than reported as a pass:**

1. **Observed:** `[data-popup-config]` count is **0** on every page checked, at both viewports, and `localStorage` is **empty** on all 16 runs. No `bhp_parent_popup*` or `bhp_mariana_popup*` key was created or touched by anything in this build.
2. **Observed:** the identical condition holds on **production at 1.19.142** — same zero popup markup, same engine still enqueued. So this is **pre-existing and unrelated to 1.19.143**.
3. **Inferred, not observed:** that dismissing one popup cannot affect the other's storage. **It could not be exercised, because neither popup renders.** ⛔ **This is recorded as a limitation, not as a verified pass.**
4. **Verified structurally:** `git diff 5c1508f..HEAD` touches **no** popup template, **no** `mariana-popup.js`, **no** storage prefix and **no** analytics prefix. The isolation architecture is untouched by construction.

## 11.7 · Findings recorded, none resolved

### `CYCLE141-LD-11` — shipping tiers on DISTINCT TITLES, not on number of books

**OBSERVED:** a cart of **30 copies of one paperback** renders shipping of **$1.99** — the single-book rate.

**Root cause, read from the code and not inferred from behaviour:** `bhp_bundle_shipping_amount()` in `plugins/brave-hearts-bundle-pricing/includes/bundle-cart.php` branches on `$eval[$format.'_tier']`, which counts **distinct titles**. At tier 1 it returns `bhp_bundle_single_shipping($format)` **regardless of quantity**. Only the mixed-format branch consults `total_quantity` at all.

⚠️ **This may diverge from the owner's stated intent.** The ruling recorded in `CLAUDE.md` and `.claude/rules/woocommerce.md` is, verbatim, *"Shipping is tiered per amount of books ordered."* The implementation tiers per **distinct title ordered**. For a single-title bulk order — precisely the classroom and library case the audience documents describe — the two readings give very different answers, and the current behaviour ships **30 books for $1.99**.

⛔ **NOT RESOLVED AND NOT CHANGED HERE.** Shipping configuration and pricing are owner-gated on every environment. Recorded for a decision, with the evidence attached.

### `CYCLE141-LD-12` — production still names a former print vendor in customer-facing legal pages

Found while verifying the approved Terms-page correction. **Staging was corrected in an earlier pass; production was not**, because database content is never carried by a theme deploy.

**OBSERVED on production, 2026-08-02**, after the approved fix landed:

- **Terms and Conditions** — 2 remaining paragraphs name the former vendor as the current print-on-demand partner.
- **Privacy Policy** — 3 occurrences, including the **data-sharing disclosure** that tells visitors which processor receives their name, address and phone number.

**Staging already carries the corrected vendor name in all five places.** The only remaining difference between the two environments' Terms pages is exactly those two paragraphs.

⛔ **NOT CHANGED.** The approved instruction was specific and narrow, and this is outside it. **Escalated** — a privacy notice naming the wrong data processor is an accuracy problem, not a copy preference. The exact five locations and their replacement text are prepared and await approval.

## 11.8 · Rollback — updated now that something is actually deployed

- **Code half, staging:** `~/bhp-STAGING-backup-1.19.142-1.8.10-20260802-morningc/` — `theme-1.19.142.tar.gz` (175 entries, listed) and `plugin-1.8.10.tar.gz` (50 entries, listed). Restore by extracting over the directories, or rebuild 1.19.142/1.8.10 ZIPs from commit `5c1508f` and `--force` install.
- **Code half, local:** the branch commits are local only; nothing was pushed.
- **Content half:** this build still writes **no** database row. The Terms-page correction shipped in the same session is a **separate, independent half** with its own rollback (post revisions plus captured pre-edit files) and is recorded with the release it belongs to. ⛔ **The halves roll back independently — reverting the content half would restore a claim the owner asked to remove.**
- **Production:** untouched by this build. Nothing to roll back.

## 11.9 · Still not done

- ⛔ **No production theme or plugin deploy.** Production remains **1.19.142 / 1.8.10** by instruction — the owner reviews staging first.
- ⛔ No push, no PR, no merge.
- ⛔ No GTM object created or edited; the trigger specification remains **PREPARED, NOT APPLIED**.
- ⛔ No WooCommerce product, variation, price, stock, SKU, coupon, shipping, tax, payment or checkout field written on any environment. Prices, stock and SKUs were diffed before and after the session's content work and are **unchanged**.
- ⛔ No order placed.
- ⛔ `/teachers/` still not built, still by decision.
- ⛔ Funnel-isolation *behaviour* not exercised — see §11.6, and it is a limitation, not a pass.
- ⛔ The full test suites were **not** re-run against this deploy; §6's baseline work stands and no test file changed.
