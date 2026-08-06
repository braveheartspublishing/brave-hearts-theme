# Trust and content corrections — theme 1.19.142 / bundle-pricing 1.8.10

**Date:** 2026-08-02 · **Branch:** `feature/product-media-gallery-1.19.140` · **Base:** `d28d9bb`
**Staging:** DEPLOYED and verified · **Production theme:** NOT deployed — no approval requested or given

This release has **two independent halves that must not be confused**:

| Half | Mechanism | Where it landed | Reversal |
|---|---|---|---|
| **A. Product-content corrections** | WooCommerce `post_content` edits via `wp post update` | **staging AND production databases** | WordPress post revisions + captured pre-edit files |
| **B. Theme/plugin presentation** | Full-ZIP `wp theme install --force` / `wp plugin install --force` | **staging only** | pre-deploy tar snapshot |

**Half A is database content. It is NOT in this repository and NO theme deploy carries it.** That is precisely the trap that let the Lulu claim survive 24 days on production after being "fixed" on staging — see `KNOWN_ISSUES.md`.

---

## Half A — product-content corrections (both environments)

**Authority:** Andrew Signore, 2026-08-02, approving the corrections specified in the Commerce & CX stale-claims audit. **Relayed** to the implementer through the Chief of Staff; the implementer did not witness the approval directly.
**Scope discipline:** `post_content` only. No price, stock, SKU, coupon, shipping, tax, payment, checkout or configuration field was read-modify-written on either environment. Post-edit spot check on all six products, both environments: paperbacks `$11.99` / hardcovers `$17.99`, all six `instock`, SKUs unchanged.

### Products edited

| Env | ID | Product | Edits |
|---|---|---|---|
| production | 15 | Mount Everest (Paperback) | Lulu sentence removed · Lexile parenthetical removed |
| production | 18 | The Amazon (Paperback) | oxygen opener replaced · Lexile parenthetical removed |
| production | 333 | The Mariana Trench (Paperback) | Lexile parenthetical removed |
| staging | 15 | Mount Everest (Paperback) | Lexile parenthetical removed |
| staging | 18 | The Amazon (Paperback) | oxygen opener replaced · Lexile parenthetical removed |
| staging | 333 | The Mariana Trench (Paperback) | Lexile parenthetical removed |

> **Product ID correction, recorded because a prior audit had it wrong:** the Mariana paperback is post **333** on *both* environments. It is **not** post 13; no post 13 exists. Draft product **12** (`…-legacy-lulu`) was deliberately left untouched — it is genuinely Lulu-fulfilled history with real historical sales, and a global `wp search-replace 'Lulu'` would have falsified it.

### Edit 1 — the Lulu claim

Production product 15 only. Staging was already clean (verified before editing: it read *"Printed and fulfilled by our publishing partner, Bookvault."*).

```
REMOVED (production 15, verbatim):
<p><i><span style="font-weight: 400">Paperback. Illustrated. Printed and shipped by Lulu.</span></i></p>
```

Removal, not replacement, because the single approved fulfilment sentence does not exist yet — it is an open Andrew decision. Removing a false claim needs no new claim. **Consequence recorded rather than hidden:** production 15 now has no fulfilment sentence while staging 15 still has one. See `KNOWN_ISSUES.md` → "Fulfilment wording is still inconsistent".

### Edit 2 — the debunked oxygen opener

Product 18, **both** environments. Replacement copy approved **2026-07-06**, taken verbatim from
`G:\My Drive\Brave Hearts Publishing\Strategy\Fable Growth Audit 2026-07\03-WEBSITE-COPY-AND-LAYOUT-SPECIFICATIONS.md` §S1.

```
BEFORE: The Amazon produces 20% of the world's oxygen. Every fifth breath you take
        came from here. Charlotte and Henry go to find out why.

AFTER:  About one in every ten known species on Earth lives in the Amazon. Somewhere
        under that green canopy, a jaguar is waiting to change Charlotte and Henry's
        whole journey.
```

The replacement is sourced to the book's own Rainforest Facts, per §S1. The removed claim is a documented myth; the SEO engine's `check_author_fingerprint()` already flags the same claim family.

### Edit 3 — the Lexile parentheticals

Products 333, 15, 18 on both environments. `grades 2–3` retained; only the uncertified measured metric removed.

```
REMOVED (exact, six times): " (Lexile 500L–580L)"     [en dash U+2013 in "500L–580L"]
```

> **Markup warning — the prior audit's "one string per environment" rule is WRONG and would have missed a page.** The audit stated production uses `style="font-weight: 400"` and staging uses `style="font-weight: 400;" aria-level="1"`. **Production product 333 uses the staging-style markup** (it was created after the migration). A find-and-replace written per environment would have silently skipped it. The edit was therefore performed as a markup-agnostic removal of the parenthetical substring, applied per product, with a 1-occurrence assertion on every file and a byte-diff before writing.

### Half A verification (OBSERVED)

- **Pre-edit `post_content` captured** for all six products on both environments before any write, and re-captured after. Every write verified **byte-identical** to the locally prepared file.
- **Rendered pages after `wp sg purge` on both environments**, all three paperback URLs: `Lulu` = 0, `Lexile` = 0, `20% of the world's oxygen` = 0, `fifth breath` = 0. The Amazon PDP renders the approved replacement opener on both.
- `Perfect for:` still renders **4** bullets on all six pages; **no orphan parenthesis**.
- Rendered JSON-LD: **zero** `aggregateRating`, **zero** `review` — unchanged.
- **Rollback path:** WordPress post revisions preserve every pre-edit body; the captured pre-edit files are the second copy.

---

## Half B — theme 1.19.142 + bundle-pricing 1.8.10 (STAGING ONLY)

### B1 · Homepage founder card (P1a) — `front-page.php`, `style.css`

The `#first-reader` journal frame held an **illustration** while a real founder photograph was already published on `/about/` and the Adventure Kit page. Swapped to that existing theme asset with its existing approved alt text — **no new media, no new licence question, no new claim.**

- `assets/images/handoff/charlotte-henry.webp` → `assets/images/handoff/founder-and-charlotte.webp`
- alt: `Andrew Signore with Charlotte and a Brave Hearts book` (unchanged from `/about/`)
- caption: `Charlotte and Henry` → `Andrew and Charlotte` — required, not cosmetic: the old caption under the new photograph would have been false
- added `width="1400" height="1867"`; added a `--photo`-scoped `object-position: 50% 30%` because the illustration-tuned `44%` crop clipped the faces. The illustration rule is left intact.

**Zero layout regression, measured rather than asserted.** Production (illustration) and staging (photo) compared at the same viewports in the same browser session:

| Viewport | frame | img box | gap below img |
|---|---|---|---|
| 1440 prod / 1440 stg | 479×626 / 479×627 | 463×610 / **463×610** | 8 / **8** |
| 390 prod / 390 stg | 267×453 / 267×453 | 249×328 / **249×328** | 117 / **117** |

The 117px mobile gap is **pre-existing** and unchanged. Image confirmed loaded on staging: `complete=true`, `naturalWidth=1400`, `naturalHeight=1867`.

### B2 · Kirkus expanded on the Complete Collection (P4a) — bundle-pricing plugin

Closes gap **G4**: the page carried the Kirkus *badge* with no quote and no link while the homepage carried the full quote. New `bhp_bundle_render_landing_kirkus()` calls the theme's **existing** centralised component in `expanded` mode, placed after the three-book story section.

- **Zero new copy.** Quote, attribution, reviewed title and URL all come from `bhp_get_kirkus_review_data()`.
- **No `Review` / `AggregateRating` microdata** — the component has never emitted any; re-verified on the rendered page.
- **Fail-closed:** renders nothing if `bhp_render_kirkus_credibility()` is absent or the approved data is incomplete.
- CSS is **layout-only**; the card's visual treatment stays in the theme, so this page cannot drift from the homepage rendering of the same quote.
- Rendered section order confirmed: `hero → outcomes → story → **kirkus** → value → gift → final`.

### B3 · Five-star badge scoped honestly — `front-page.php` + bundle-pricing plugin

`Five-star reader reviews` → **`Five-star reader reviews on our first two titles`** on the homepage trust strip and the Collection trust band.

Sourced to `inc/amazon-reviews.php`: **four** approved 5-star reviews for The Mariana Trench, **two** for Mount Everest, **zero** for The Amazon (published 2026-06-26). The Collection page's `$has_reviews` guard is an **OR**, so on a page selling all three books the unqualified badge asserted proof the third book does not have.

**Deliberately NOT changed:** the `★★★★★` glyph run, the `5 out of 5 stars` screen-reader text, and all schema. Whether a bare glyph run reads as an aggregate rating is `CYCLE140-CX-9` — **Andrew's presentation call, and this release does not resolve it.**

The per-product-page trust row (`functions.php`) needed no change: it is already gated per title, so The Amazon correctly shows no review badge.

### B4 · "Printed and shipped in the USA" removed — bundle-pricing plugin

Country-of-origin claim in the Collection purchase-panel fine print, with **no located source**. Searched: repo `docs/` (including `bookvault-chronology.md` and `fulfillment-copy-correction-2026-07-09.md`), the Business OS corpus, and the Strategy corpus. Nothing establishes where Bookvault prints a given order.

The 2026-07-09 fulfilment record states in its own *"Not claimed / not added"* section that no copy claiming **domestic-only printing** was added — yet this line claimed exactly that, on the highest-value page, while no product page made any such claim.

```
BEFORE: Secure checkout · Tracking provided · Printed and shipped in the USA
AFTER:  Secure checkout · Tracking provided
```

Both surviving statements are mechanically verifiable. To restore the claim, a Bookvault country-of-print record must exist and be cited. `CYCLE140-CX-10`.

---

## Build and deploy

| Artifact | Version | Notes |
|---|---|---|
| `brave-hearts-theme-deploy-explorer-expedition-guides` | **1.19.142** | 4.03 MB. Allowlist audited: `style.css`, `theme.json`, `assets/`, `inc/`, `template-parts/`, top-level `*.php`. **0** `docs/`, `tmp/`, `plugins/`, `.git`, `node_modules`, `Publisher-Review` entries; **0** `staging2` occurrences in code |
| `brave-hearts-bundle-pricing` | **1.8.10** | 174 KB, separate artifact → `wp-content/plugins/` (never in the theme ZIP). Staging had drifted to **1.8.8** vs repo **1.8.9** before this deploy |

Pre-deploy rollback snapshot: `~/bhp-stg-prebuild-20260802-043838.tar.gz` (theme 1.19.141 + plugin 1.8.8, both directories, 3.9 MB, contents listed and confirmed).

Post-deploy verification (OBSERVED): `wp theme list --status=active` → `brave-hearts-theme-deploy-explorer-expedition-guides,1.19.142,active`; **no duplicate/new theme directory** created; `wp plugin get brave-hearts-bundle-pricing` → `1.8.10, active`; `wp eval 'echo "ok";'` → `ok` (no fatal); `wp sg purge` run.

---

## Tests

PHP syntax: all three changed PHP files pass `php -l` (run on the server — no local PHP on the build machine).

Full suite run on staging, **19 theme + 13 plugin files**: **1061 PASS · 6 FAIL · 0 fatals.**
(The theme's `tests/` directory is *not* in the deploy allowlist, so it was uploaded temporarily, run, and **removed afterwards** — the deployed theme now matches the ZIP contents exactly.)

Directly relevant suites, all green: `test-kirkus-component.php` **31/0**, `test-bundle-pricing.php` **56/0**, `test-amazon-review-showcase.php` **58/0**, `test-campaign-landing.php` **22/0**, `test-author-fingerprint-package.php` **33/0**.

The 6 failures — `test-content-intelligence-engine` (1), `test-cta-collision-detector` (2), `test-draft-package` (2), `test-lead-event-log` (1) — are in the content-operations and lead-analytics subsystems. **Honest labelling: they are believed pre-existing, not proven so**, because these suites had never been deployed to staging and therefore no before-run baseline exists. The supporting evidence is (a) this wave's entire diff is 5 files — `front-page.php`, `style.css` and three plugin files — none of which any of the 4 failing suites references, and (b) two of the failing assertions are explicitly environment-state assertions (*"LIVE FIXTURE: post 546…"*, *"On staging, even an ordinary-looking address is classified as test provenance"*).

## Browser QA — staging, real browser, viewports confirmed

Driven over the Chrome DevTools Protocol with `Emulation.setDeviceMetricsOverride`. **`--window-size` was tested first and rejected: it clamps to `innerWidth` 512 on this machine, so a "390px" screenshot taken that way would have been a lie.** Every viewport below was confirmed by reading `window.innerWidth` back out of the page.

| Page | Requested | Measured `innerWidth` | Horizontal scroll | Console errors | Page errors | Failed requests |
|---|---|---|---|---|---|---|
| Homepage | 1440×900 | **1440** ✓ | none | 0 | 0 | 0 |
| Homepage | 390×844 | **390** ✓ | none | 0 | 0 | 0 |
| Homepage | 320×568 | **320** ✓ | none | 0 | 0 | 0 |
| Complete Collection | 1440×900 | **1440** ✓ | none | 0 | 0 | 0 |
| Complete Collection | 390×844 | **390** ✓ | none | 0 | 0 | 0 |
| The Amazon PDP | 1440×900 | **1440** ✓ | none | 0 | 0 | 0 |
| The Amazon PDP | 390×844 | **390** ✓ | none | 0 | 0 | 0 |

Screenshots reviewed at 1440 and 390 for the founder card and the Kirkus block: both faces fully visible and uncropped in the photograph, caption inside the frame, Kirkus quote/attribution/reviewed-title/link all rendering, no overflow at 390.

**Commerce regression (staging, real Blocks cart):** the Collection primary CTA added all three paperbacks at `$11.99` each; **no "BookVAULT Shipping" method appeared**; 0 console errors. **Test cart emptied afterwards and confirmed empty** (`line item count = 0`, empty-cart marker present). No shipping rate is asserted here — `C13` is open on the correct rate and this release did not touch shipping.

**Funnel isolation:** `bhp*` `localStorage` keys were empty before and after the cart run — no parent/teacher funnel state was created or altered.

**Found during the cart run and recorded rather than absorbed:** the cart line item for The Amazon still renders *"…the lungs of the Earth"* from the product's **short description**, which this wave's `post_content` scope did not cover. See `KNOWN_ISSUES.md`.

---

## Rollback

**Half B (staging theme/plugin):** restore `~/bhp-stg-prebuild-20260802-043838.tar.gz` over `wp-content/themes/` and `wp-content/plugins/`, or re-install theme 1.19.141 / plugin 1.8.8 by ZIP; then `wp sg purge`. Locally, `git revert` the two feature commits.

**Half A (product content, both environments):** WordPress revisions hold every pre-edit body — Admin → edit product → Revisions. The pre-edit `post_content` was also captured to file before each write. **Reverting Half A restores a false vendor name, an uncertified reading measure and a debunked science claim, so it should only be done to correct a mistake in the edit itself, never as collateral to a theme rollback.** The two halves roll back independently and must be treated separately.

## Not done

- **No production theme or plugin deploy.** Half B is staging-only and unapproved for production.
- **No git push, PR or merge.** Two local commits on `feature/product-media-gallery-1.19.140`.
- **No `post_excerpt` edit** on any product — the oxygen myth survives there (open item).
- **No edit to hardcover products 14/17/20** — read only; findings in `KNOWN_ISSUES.md`.
- **`CYCLE140-CX-9`** (the bare `★★★★★` glyph run) **not resolved** — Andrew's call.
- **`docs/CHANGELOG.md` not edited** — a ready-to-paste block was handed to Business Operations & Knowledge instead.
- No shipping rate asserted or changed; no coupon, tax, payment or checkout setting touched.
