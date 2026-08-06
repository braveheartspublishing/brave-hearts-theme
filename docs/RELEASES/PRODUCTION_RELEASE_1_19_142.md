# PRODUCTION RELEASE — theme 1.19.142 + bundle-pricing 1.8.10, and the approved content batch

**Date:** 2026-08-02 (America/Denver, −06:00)
**Environments changed:** **PRODUCTION** (theme, plugin, product content) and **STAGING** (product content only)
**Branch:** `feature/product-media-gallery-1.19.140`
**Prior production state:** theme **1.19.121**, bundle plugin **1.8.8** — verified live immediately before the deploy, not inherited from a document

---

## 0 · Authority, stated with its relay

Andrew Signore, 2026-08-02, main session, **relayed verbatim** by the Chief of Staff:

> *"Confirm push to production. Approved follow up. IM ok with the image change - She is my niece - im not her parent but I have consent."*

⚠️ **The engineer who executed this release did not witness that message directly.** A production deploy and a WooCommerce product mutation are owner-gated; this release ran on the relayed approval and the provenance is recorded here rather than described as first-hand. **What would close the gap:** Andrew's own confirmation in the canonical decision record.

**Founder-attested consent for the founder photograph, recorded verbatim as required:**

> *"IM ok with the image change - She is my niece - im not her parent but I have consent."*

That is an attestation by the founder, not a verification performed by anyone in this repository. It is recorded, not corroborated. No agent verified it and none could.

---

## 1 · What is live on production now

| | Before | After | Verified how |
|---|---|---|---|
| Theme | 1.19.121 | **1.19.142** | `wp theme list --status=active` |
| Bundle plugin | 1.8.8 | **1.8.10** | `wp plugin get … --field=version` |
| Theme files | 147 | **151** | md5 manifest, **151/151 byte-identical to the deployed ZIP** |
| Plugin files | — | **44** | md5 manifest, **44/44 byte-identical to the deployed ZIP** |
| Products 14/17/20 `post_content` | Lexile parenthetical present; 20 carried the oxygen opener | corrected | byte-verified readback + rendered page |
| Products 18/20 `post_excerpt` | oxygen myth | corrected | byte-verified readback + rendered `meta`/`og`/JSON-LD |
| Product 15 `post_content` | no fulfilment sentence | approved Bookvault sentence | byte-verified readback + rendered page |
| Prices · `_regular_price` · `_stock_status` · SKUs | — | **diffed UNCHANGED** | pre/post `wp post meta get` diff, 6 products |
| Shipping zones/methods | 1 zone, 1 `flat_rate` @ 3.99 | **unchanged** | direct table query, before and after |

---

## 2 · The deploy

### 2.1 Backup taken FIRST, before anything was touched

`~/bhp-PROD-backup-1.19.121-prewave4-20260802/` — 4.0 MB:

- `theme-1.19.121.tar.gz` (3.9 MB, the complete live theme directory)
- `plugin-bundle-pricing-1.8.8.tar.gz` (143 KB)
- `theme-manifest-1.19.121.md5` — **147 files**
- `themes-before.csv`, `plugins-before.csv`, `products-before.csv`
- `product-prices-before.txt` — `_price`, `_regular_price`, `_stock_status`, `_sku` for all six products
- `page_on_front-before.txt`, `lead-magnets-before.json`
- `post_content-<id>.before.txt` and `post_excerpt-<id>.before.txt` for all six products

The **pre-existing** `~/bhp-PROD-backup-1.19.121-20260731/` was also confirmed still present (5.2 MB, same shape). **Two independent production rollback points existed before a single byte was written.**

### 2.2 Drift check — nothing was silently erased

`wp theme install --force` deletes the theme directory before extracting, so production-only files would be lost. Compared production's 147-path manifest against the ZIP's 151:

- **files on production but not in the ZIP: 0** — nothing was destroyed
- files in the ZIP but not on production: **4** — `assets/css/book-media.css`, `assets/js/book-media.js`, `inc/book-media.php`, `template-parts/commerce/look-inside.php` (the 1.19.140–142 additions)

### 2.3 The artifacts are the ones staging was QA'd on

Rather than rebuild, the release used the **exact ZIPs already installed and QA'd on staging** — `theme-1.19.142.zip` (4,035,447 bytes, archive comment `b88f71ea…`, top-level folder `brave-hearts-theme-deploy-explorer-expedition-guides` matching the active slug) and `bundle-pricing-1.8.10.zip` (177,808 bytes, top-level `brave-hearts-bundle-pricing`).

Verified against the **live staging directories** before deploying:

- theme: **151/151 md5-identical**. Staging carried **2 extra** files — `content-engine/author-packages/index.json` and `content-engine/blogs/bhp-test-draft-package-bridge-books/content-brief.json` — both **runtime artifacts the content engine writes on staging**, not theme source. Recorded rather than glossed.
- plugin: **44/44 md5-identical**, zero difference.
- ZIP hygiene: 0 `docs/`, 0 `tests/`, 0 `plugins/`, 0 `.git`, 0 `node_modules`.

⚠️ **A correction to the Wave 3 record:** it reported *"0 `staging2` occurrences in code"* in the theme ZIP. That is **not accurate as literally worded** — there are **5**, in 4 files. All five are intentional environment-detection constants (`BHP_Analytics_Config::STAGING_HOST`, the sanitizer allowlist, the internal-link regex, a comment) and **all five are byte-identical to what was already live on production 1.19.121**. Benign and pre-existing; the claim was simply too broad.

### 2.4 Install and immediate verification

Theme then plugin, back to back in one session. `wp theme list --status=active` → **1.19.142**. `wp plugin get` → **1.8.10**, active. **No duplicate theme directory created** (the only other `explorer-expedition` entry is the pre-existing inactive `…-1.16.1`). `wp eval 'echo "ok";'` → **ok** (no fatal). `wp sg purge` run.

⚠️ **Recorded, not smoothed over:** both installs emitted `Warning: Undefined array key "destination" in …/plugins/bookvault/Bookvault.php on line 528`. It is a **third-party plugin's** notice on WP-CLI's upgrader hook, it fired identically for both installs, and both installs succeeded. Not caused by this release; not investigated here.

---

## 3 · Rendered production QA

Headless Chrome over CDP. **Every viewport was confirmed by reading `window.innerWidth` back out of the page** — never assumed from a `--window-size` flag.

| Page | Viewports confirmed | Console errors | Page errors | H-scroll |
|---|---|---|---|---|
| Homepage | **1440 · 390 · 320** | 0 | 0 | none |
| Complete Collection | **1440 · 390** | 0 | 0 | none |
| Mariana PB · Everest PB · The Amazon PB | **1440 · 390** | 0 | 0 | none |
| Cart | 1440 | 0 | 0 | — |
| Checkout | 1440 | 0 | 0 | — |

**OBSERVED PASS:**

- **Founder photograph** — `founder-and-charlotte.webp`, `complete: true`, natural **1400×1867**, `object-fit: cover`, `object-position: 50% 30%`; asset serves HTTP 200 / 258,186 bytes / `image/webp`. Alt text is the already-approved *"Andrew Signore with Charlotte and a Brave Hearts book"*. Card reads `FIELD JOURNAL - ENTRY 01 / ANDREW AND CHARLOTTE / …` — the corrected caption, matching what the frame contains. Rendered box **463×610** at 1440 and **249×328** at 390, **exactly the figures measured on staging**, so the deploy reproduced staging's layout with no drift.
- **Kirkus, expanded, on the Complete Collection** — `.bhp-landing-kirkus` present, 283 characters, quote + `KIRKUS REVIEWS` + the reviewed title scoped to *The Mariana Trench* + the "read the full review" link. Identical at 1440 and 390.
- **Five-star badge scoped** — `Five-star reader reviews on our first two titles` on the homepage trust strip and the Collection trust band. Product pages correctly keep their **per-title** gating: Mariana and Everest show `Five-Star Reader Reviews`; **The Amazon shows none** (it has zero reviews).
- **Unsourced `Printed and shipped in the USA`** — **0** occurrences on the Collection page and sitewide.
- **Schema honesty** — `aggregateRating` **0** and `review` **0** in the rendered `rank-math-schema` on every page checked, before and after every change.
- **Commerce guards** — one zone, one `flat_rate`, **zero "BookVAULT" occurrences** anywhere on cart or checkout.
- **0 broken images** on production (`complete && naturalWidth === 0` returned an empty set on every page).

**⛔ OBSERVED FAIL — the one acceptance criterion this release does not meet:** *"all three galleries render (item counts 7/8/5)"* and *"Collection composite slide 1 shows byline + brand line."*

**On production, `[data-bhp-gallery]` count is 0 on all four pages.** Not an error, not a regression — see §5.

### 3.1 Purchase path, production, **no order placed**

Single Mariana paperback added through the real UI:

```
items 1 · subtotal $11.99 · shipping $1.99 · tax $0.72 · total $14.70
rates: [Contiguous US Shipping · flat_rate · $1.99 · selected]
```

Checkout rendered: title *Checkout - Brave Hearts Publishing*, **14 visible inputs**, **12 Stripe iframes** (card fields render), exactly one shipping method, **0 "BookVAULT"**, 0 console errors. The **PLACE ORDER** button was located and confirmed present — **it was not clicked. No order was placed.**

**Test cart emptied and confirmed empty afterwards** (`items = 0, qty = 0`) on both environments.

---

## 4 · The approved content batch — 13 edits, both environments

**Strict scope: `post_content` and `post_excerpt` only.** No price, `_regular_price`, stock, SKU, coupon, shipping, tax, payment, checkout or configuration field was written, on either environment. No `wp search-replace` was run anywhere. Draft product **12** (`-legacy-lulu`, genuinely Lulu-fulfilled history) was **not** touched.

Applied by a single server-side script that, for every edit, **asserted an exact occurrence count before it would write** and **compared the readback byte-for-byte** afterwards. It was run in **dry mode on both environments first**; all assertions passed before anything was written.

| # | Product | Field | Change | prod bytes | staging bytes |
|---|---|---|---|---|---|
| 1 | 14 Mariana HC | `post_content` | ` (Lexile 500L–580L)` removed | 1803→1782 | 1840→1819 |
| 2 | 17 Everest HC | `post_content` | ` (Lexile 500L–580L)` removed | 1718→1697 | 1755→1734 |
| 3 | 20 Amazon HC | `post_content` | ` (Lexile 500L–580L)` removed | 1768→1747 | 1805→1784 |
| 4 | 20 Amazon HC | `post_content` | oxygen opener → §S1 approved copy | 1747→1785 | 1784→1822 |
| 5 | 18 Amazon PB | `post_excerpt` | oxygen myth → §S1 defensible framing | 326→347 | 307→328 |
| 6 | 20 Amazon HC | `post_excerpt` | oxygen myth → §S1 defensible framing | 263→293 | 250→280 |
| 7 | 15 Everest PB | `post_content` | approved Bookvault sentence appended | 1625→1760 | *(already present)* |

**7 edits on production, 6 on staging** (staging 15 already carried the sentence — the script detected that and **skipped** rather than duplicating it).

**The copy, sourced:**

- Opener (edit 4) — `Strategy\Fable Growth Audit 2026-07\03-WEBSITE-COPY-AND-LAYOUT-SPECIFICATIONS.md` §S1, dated **2026-07-06**, verbatim: *"About one in every ten known species on Earth lives in the Amazon. Somewhere under that green canopy, a jaguar is waiting to change Charlotte and Henry's whole journey."* Identical to the string already applied to product 18 in Wave 3.
- Short descriptions (edits 5–6) — built on §S1's **own** stated defensible alternative, verbatim: *"Its trees help drive the rain and weather that the whole continent depends on."* Rendered as *"…land in the Amazon — a forest whose trees help drive the rain and weather that the whole continent depends on."* (18) and *"A forest whose trees help drive the rain and weather that the whole continent depends on."* (20). **No new factual claim was introduced** — the only editorial act was a grammatical connector.
- Fulfilment sentence (edit 7) — `docs/fulfillment-copy-correction-2026-07-09.md`, verbatim: *"Paperback. Illustrated. Printed and fulfilled by our publishing partner, Bookvault."*

**Residue check after writing — all six products, both fields, both environments:**

```
Lexile=0  Lulu=0  oxygen20=0  fifthBreath=0  lungsOfEarth=0  oneInFive=0   grades2-3=1 (all six)
```

**Rendered verification after `wp sg purge`, both environments, real browser:** The Amazon PB's `<meta name="description">`, `og:description` **and** the JSON-LD `description` all now carry the new framing; `lungs of the Earth` = 0 and `One in five breaths` = 0 on every page. Everest PB on production now reports `Bookvault` present, `Lulu` = 0 — matching staging. `Perfect for:` still renders four bullets; **0 orphan parentheses**; `aggregateRating` and `review` still **0**.

---

## 5 · ⛔ The blocker this release surfaced — production has none of the gallery media

**OBSERVED, not inferred.** `bhp_book_media_registry()` addresses every asset by **attachment slug** (deliberately, because staging and production have different attachment IDs). All 29 distinct slugs were resolved against both media libraries:

| | resolved | missing |
|---|---|---|
| **Production** | **0** | **29** |
| Staging | 28 | 1 |

Consequence, confirmed in a real browser after a cache purge: **staging** renders Mariana **7**, Everest **8**, The Amazon **5**, Collection **9** (composite slide 1 loads correctly); **production** renders **zero galleries and zero gallery markup**, with **zero console errors**.

This is the module's designed fail-closed behaviour, quoted from its own header: *"An item whose asset does not resolve is DROPPED from the gallery. A title left with no items renders no section at all — never an empty frame, never a placeholder."* **Production therefore looks exactly as it did at 1.19.121. Nothing is broken and no rollback is warranted.**

**⚠️ Uploading the media was NOT done, and must not be done as a side effect of a theme deploy.** `inc/book-media.php` records in its own provenance block that the Everest set comes from an **AI-assisted pipeline** (Higgsfield job IDs; preserved `trainedAlgorithmicMedia` / "Made with Google AI" XMP) and that **two items carry visible text artefacts a print run would not produce** — `ADVENTURES OF CHARLOTTE AND IJENRS`, `breathioking landscopes`, `Perfect fcr first chapter book readers` — which that file states were *"approved by him for staging."* **Staging approval is not storefront approval, and a theme-deploy approval does not imply a creative-asset approval.** This is Andrew's call, per book. `CYCLE141-LD-1`.

---

## 6 · Shipping — the documentation correction, and what was actually observed

Owner ruling, **verbatim**: *"Andrew Signore, 2026-08-02: 'Shipping is tiered per amount of books ordered.'"*

Repo `CLAUDE.md` and `.claude/rules/woocommerce.md` both said the customer-facing rate was *"a single flat rate ($3.99, Contiguous US)"*. That conflated two different true facts, and the old wording would make a correct $1.99 look like a regression. Both files now carry the correction, with the superseded sentence **retained and labelled**, never deleted.

- **Zone configuration (unchanged, verified live on production):** one zone *Contiguous United States*; one method `flat_rate` instance 1; `{"title":"Contiguous US Shipping","tax_status":"taxable","cost":"3.99"}`. **No "BookVAULT Shipping" method is zoned anywhere.**
- **What the customer pays** is that base adjusted by `bhp_bundle_override_shipping_cost()` from the approved tier table: **1 PB $1.99 · 2 PB $2.99 · 3 PB $3.99 · 1 HC $2.99 · 2 HC $3.99 · 3 HC $4.99 · mixed ≤2 $3.99 · mixed ≥3 $4.99** (`bundle-data.php`, `bundle-cart.php`). A cart containing anything outside the six approved editions is left completely alone.
- **OBSERVED LIVE 2026-08-02, real Blocks cart, single Mariana paperback, both environments:** Store API and rendered DOM agree — *Contiguous US Shipping **$1.99***, subtotal $11.99, tax $0.72, total $14.70, one method, zero "BookVAULT". **This settles the $1.87/order sensitivity at $1.99, not $3.99, for a single-book order.** Cart emptied afterwards.

**Nothing in WooCommerce was changed.** This section is a documentation correction plus a read-only observation.

---

## 7 · Rollback

**The three layers roll back independently. Do not roll one back as collateral to another.**

1. **Theme** — `wp theme install ~/bhp-PROD-backup-1.19.121-prewave4-20260802/theme-1.19.121.tar.gz`-derived ZIP, or reinstall the 1.19.121 build; then `wp sg purge`. Second, older rollback point also intact: `~/bhp-PROD-backup-1.19.121-20260731/`.
2. **Plugin** — restore `plugin-bundle-pricing-1.8.8.tar.gz` from the same backup directory. ⚠️ **The Collection hero depends on the theme/plugin hook pair (`CYCLE139-DEV-4`) — roll both back together or neither.**
3. **Product content** — WordPress post revisions hold every pre-edit value, and the exact pre-edit `post_content`/`post_excerpt` of all six products was captured to file in the backup directory before any write. ⚠️ **Reverting this layer restores an uncertified reading measure and a debunked science claim.** Do it only to correct an error in the edit itself.
4. **Local repo** — the working tree was clean at `089c092` before this session's edits; `git revert` or `git checkout 089c092 -- <path>` restores any file.

---

## 8 · Tests

The **1061 PASS / 6 FAIL / 0 fatals** suite result recorded in `TRUST_AND_CONTENT_CORRECTIONS_1_19_142.md` covers **this exact code** — the deployed production files are byte-identical to the staging files that suite ran against. **The suite was not re-run against production**, because the theme's `tests/` directory is deliberately not in the deploy allowlist and uploading it to a live storefront to run tests is not an acceptable trade. **Stated as a limitation, not presented as a pass.** What *was* run on production directly: `wp eval 'echo "ok";'` (no fatal), the byte-identity manifests, and the rendered browser QA above.

The 6 failures remain as previously characterised — in `test-content-intelligence-engine`, `test-cta-collision-detector`, `test-draft-package`, `test-lead-event-log`, at least two of them explicit environment-state assertions. **Believed pre-existing, still not proven so.**

---

## 9 · Not done, stated explicitly

- **No git push, pull request or merge.** Commits are local to `feature/product-media-gallery-1.19.140`.
- **No gallery media uploaded to production** — §5, needs Andrew.
- **No GTM change.** The five gallery events (`gallery_count`, `item_index`, `item_type`, `item_group`, `direction`, `interaction`, `method`, `item_label`) still have **no trigger and no tag**. GTM is configuration and an owner gate; it was deliberately skipped, not attempted.
- **No WooCommerce configuration touched** — price, stock, SKU, coupon, shipping, tax, payment, checkout: all verified unchanged, none written.
- **No order placed**, on either environment.
- **`docs/CHANGELOG.md` not edited** — a ready-to-paste block was handed to Business Operations & Knowledge instead, which is the only role that edits it.
- **`CYCLE140-CX-9`** (whether a bare ★★★★★ glyph run reads as an aggregate rating) untouched — still Andrew's.
- **`CYCLE140-CX-5`** (the *"Read-aloud from age 4"* line) untouched — still Andrew's.
- The three paperbacks still carry **two different** fulfilment sentences. Parity for 15 was approved; a single sitewide sentence was not.
