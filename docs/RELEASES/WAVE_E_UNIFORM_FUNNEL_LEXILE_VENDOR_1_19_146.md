# Wave E — uniform funnel set, Lexile restoration, vendor correction, farmers-market element

- **Theme:** `1.19.146` on **staging only**. **Production theme remains `1.19.142` and no theme or plugin file was deployed to production.**
- **Bundle plugin:** `1.8.11`, untouched this wave.
- **Branch:** `feature/product-media-gallery-1.19.140`. Local commits only — **no push, no PR, no merge.**
- **Date:** 2026-08-02 (machine clock, all times −06:00).
- **Deploys:** exactly **one** staging theme deploy, per SOP-06.

> ## ⛔ THE ONE THING THIS RELEASE RECORD CANNOT CLAIM
>
> **No browser QA was performed, because no browser-automation tool existed in the runtime that
> built this.** No viewport was set, `window.innerWidth` was never read, no console was inspected
> and **no screenshot exists.** Every visual, layout, console and mobile statement that a normal
> release record would carry is **absent here rather than asserted**, because a verification that
> was not run is on the never-invent list. See
> `Business OS\WORKING-DRAFTS\lead-developer\screenshots-2026-08-03-wavee\README-WHAT-IS-AND-IS-NOT-HERE.md`.
>
> ➡ **Whoever holds a browser must run the desktop and mobile passes before this build is put in
> front of Andrew as visually reviewed.** Everything below is either rendered-HTML evidence,
> server-side measurement, or a file that was opened and looked at — each labelled.

---

## 1 · What shipped, in one table

| # | Item | Where it landed | Type |
|---|---|---|---|
| 1 | FD-40 uniform 3-slide funnel set | staging theme | code |
| 2 | Educator subset drops the artefact-bearing Mariana spread | staging theme | code |
| 3 | Lexile restoration, per title | **staging AND production** `post_content` | content |
| 4 | Lulu → Bookvault on Privacy and Terms | **PRODUCTION** `post_content` (+ staging parity check) | content |
| 5 | "Where you'll find us" farmers-market element | staging theme + new theme asset | code + asset |
| 6 | `check_author_fingerprint()` founder-verified allowlist | `brave-hearts-seo-engine` (local commit only) | code |

**Production writes this wave were items 3 and 4 ONLY, and both were `post_content`.** No
production theme file, plugin file, price, stock, SKU, coupon, shipping, tax, payment or checkout
setting was touched or read-modified on any environment.

---

## 2 · Item 1 + 2 — the funnel galleries

### The rule, not just the change

FD-40 establishes something broader than a media swap: **funnel media stays consistent across
audiences unless there is a stated reason to differ.** Per-audience variation is now the exception
that must carry a written justification. The five uniform pages therefore reference **one shared
`$uniform` list** in `bhp_cx_collection_gallery_map()` rather than each holding its own copy —
five copies of a list that must stay identical is a drift defect waiting to happen.

The uniform set: **composite · Mariana flip-through · Everest flip-through**
(`collection-look-01-three-books-v2`, `mariana-look-01-poster`, `everest-look-01-poster-v2`).

**The educator-spread 4th slide was offered to Andrew as an opt-in and he did not take it.
Default is uniform. Do not add a 4th slide without his words.**

### The one exception, and why it is inside FD-40 rather than against it

`/educators-adventure-learning-toolkit/` keeps an interiors-only subset and **drops
`mariana-look-03-depth-diagram-brave-learning`**. Its left page carries four visible text
artefacts from the Higgsfield regeneration pass — *"Mount **Ererest**"*, *"**Hodal** Zone"*,
*"Very few creatures **lire** here"*, *"The **despert spet** of all"* — verified by `commerce-cx`
downloading and opening the full-size file (`CYCLE141-CX-40`). Teachers are the one audience that
**reads** the printed words in a photograph rather than glancing at it.

⛔ **The registry is untouched and the asset is unaltered.** The Mariana set is protected until the
queued reshoot (`ROADMAP.md`; `CURRENT_TASK.md`: *"must not be removed, replaced, regenerated,
retouched or reordered"*). The slide still renders on `/complete-collection/` and on the Mariana
product page exactly as Andrew approved. This is a caller-side presentation choice on one page.

⚠️ **Andrew may veto in one line:** replace that page's `items` list with `$uniform`.

### Measured result — rendered HTML, staging 1.19.146

| Page | `data-bhp-gallery-count` | slides in DOM | slide identity | duplicate DOM ids |
|---|---|---|---|---|
| `/` | **3** | 3 | composite · Mariana flip · Everest flip | 1 |
| `/reluctant-reader-adventure-kit/` | **3** | 3 | same three | 1 |
| `/gift-buyers-guide/` | **3** | 3 | same three | 1 |
| `/organizations-community-reading-kit/` | **3** | 3 | same three | 1 |
| `/educators-adventure-learning-toolkit/` | **2** | 2 | Everest how-tall · Amazon Brave Learning. **Artefact slug: 0 occurrences** | 1 |
| `/books/` | **3** | 3 | same three | 1 |
| `/teachers/` | — | **0** | no gallery, deliberately | 0 |

### ⭐ The regression check that matters most — PASSES

| Page | Expected | Measured |
|---|---|---|
| `/complete-collection/` | 9 | **9**, artefact slide still present, unchanged |
| Mariana paperback | 7 | **7** |
| Mount Everest paperback | 8 | **8** |
| The Amazon paperback | 5 | **5** |

The caller-side slicing did what its own head note promised: the registry was never altered, so
the page that converts best is byte-unaffected.

---

## 3 · Item 3 — Lexile restoration

### ⛔ A LOAD-BEARING CORRECTION TO THE SOURCE SPEC — read this before repeating the edit

The specification named **product 13** as "The Mariana Trench (Paperback)".

**Product 13 is an `attachment`.** The Mariana Trench paperback is **product 333**. Product 12 is a
`draft` legacy-Lulu record. Editing 13 would have written into an attachment's `post_content` and
left Mariana with no measure at all, silently.

Caught by a `post_type` + exact-`post_title` guard that aborts before writing. **Every future
content edit on these records should carry the same guard** — an ID in a document is a memory, and
this one was stale. Registered `CYCLE141-LD-20`.

### The asymmetry is the whole point

| Product | Title | Action |
|---|---|---|
| **333** | The Mariana Trench (Paperback) | append **`(Lexile® 580L)`** |
| **15** | Mount Everest (Paperback) | append **`(Lexile® 500L)`** |
| **18** | The Amazon (Paperback) | ⛔ **NOTHING. No measure exists.** Asserted unchanged, before and after |

**On production, products 15, 18, 14, 17 and 20 carry a BYTE-IDENTICAL bullet.** A string-scoped
replace would have stamped `Lexile® 500L` onto a title with no measure — a fabricated third-party
reading measurement on a live product page (`CYCLE141-CX-40a`). **Every edit was product-ID
scoped. Never run a string-scoped replace on this field.**

### Guards that ran before any write

`post_type === "product"` · exact `post_title` match · bullet occurs **exactly once** · no existing
Lexile string · `grades 2–3` retained · the adjacent `Read-aloud from age 4` bullet untouched ·
blended-range occurrences **0** · `&reg;` entity count exactly 1 · The Amazon asserted at Lexile
count 0 both before and after. Any failure aborts that product without writing.

### Rendered result — VERIFIED LIVE, both environments

| Environment | Mariana | Everest | The Amazon |
|---|---|---|---|
| **Production** | `Independent readers in grades 2–3 (Lexile&reg; 580L)` | `… (Lexile&reg; 500L)` | `Independent readers in grades 2–3` — **no Lexile** |
| **Staging** | same | same | same |

`&amp;reg;` (double-encoded) occurrences: **0**. `500L–580L` blended range: **0** on every page,
both environments — it is retired and must not be restored. `aggregateRating`: **0**. A Lexile
measure describes text complexity; it is not a rating and must never be emitted as one.

### ⚠️ Two honest deviations

1. **No revisions were created for the two products.** WooCommerce `product` does not support
   revisions, so `wp_get_post_revisions()` returned 0 after both writes. The brief asked for
   revisions; the rollback is therefore the **pre-edit backup**, not a revision:
   `~/bhp-WAVEE-backup-20260802/{PROD,STAG}-<id>.pre.html` on the server. The two *pages* in item 4
   did create revisions (5 and 6).
2. **Hardcovers 14 / 17 / 20 were NOT edited — prepared, not applied.** All three carry the same
   bullet on both environments, and their `/product/…-hardcover/` URLs 301-redirect to the
   paperback, so their stored content renders on no page. The brief and the spec both named the
   paperbacks; extending to hardcovers is a further product mutation and Andrew's call. If he wants
   it, it is the same guarded script with 14 → 580L and 17 → 500L, and **20 gets nothing**.

### Still blocked, not invented

**The MetaMetrics trademark attribution line.** `commerce-cx` could not retrieve it (three
MetaMetrics legal URLs returned 404; their footers are JS-rendered) and **did not write one from
memory**. That decision is carried forward unchanged: **the citation block is not shipped, and no
attribution sentence appears anywhere in this build.** The per-title bullets do not depend on it.

---

## 4 · Item 4 — Lulu → Bookvault on production

**The most serious item in the wave, and the severity is structural rather than editorial:** the
production Privacy Policy named the wrong data processor **in its data-sharing disclosure** — the
sentence that tells a visitor which company receives their name, shipping address and phone number.

**Staging was already correct.** So the fix was not a wording question, and the strongest available
guard was used: apply the replacement pairs to production's content and **assert the result is
byte-identical to the proven-correct staging content** before writing anything. It passed on both
pages, which proves both that the pairs were right and that the two environments are now at parity.

| Page | Occurrences before | After | Guard |
|---|---|---|---|
| Privacy Policy (**3**) | Lulu **5**, Bookvault 0 | Lulu **0**, Bookvault **5** | 3 find/replace pairs, each asserted at exactly 1 occurrence; result === staging bytes |
| Terms and Conditions (**324**) | Lulu **2**, Bookvault 0 | Lulu **0**, Bookvault **2** | 2 pairs, same assertions |

**Rendered and verified live on production after `wp sg purge`:** `/privacy-policy/` → 0 Lulu,
5 Bookvault, the data-sharing sentence now reading *"we share your name, shipping address, and
phone number with Bookvault"*. `/terms-and-conditions/` → 0 Lulu, 2 Bookvault.

Note the first Privacy sentence also changed *"printed and **shipped** by"* → *"printed and
**fulfilled** by"*, because that is what the proven staging text says. It was applied as found, not
edited further.

⚠️ **This fixes the symptom on two pages. It does not fix `C18`** — the obsolete Lulu build guide
still reads as current in Drive, and every remaining "Lulu" occurrence in customer-facing surfaces
needs a sweep of the same class as the FD-32 brand sweep. That is `ACT-OPS-035` and is **not**
discharged by this wave.

---

## 5 · Item 5 — the farmers-market element

Placement is `commerce-cx`'s trust audit, verbatim: a `Where you'll find us` strip **below
`#first-reader`**, **not above the fold**, ranked **5th of 6** in the trust hierarchy. Verified in
the rendered DOM: it sits between `#first-reader` and `#learning-hub`. **No CTA**, deliberately.

**Route: theme asset, not a media-library upload.** `assets/images/handoff/farmers-market-2026-05.webp`,
handled exactly like `founder-and-charlotte.webp` — version-controlled with the theme, cannot drift
between environments, and requires no media upload (which is an Andrew gate and is what stranded
the Collection media for a week). ⛔ **It is deliberately NOT registered in
`bhp_book_media_registry()`** — that is the *book* media registry, and a marketing event photograph
is a different asset class.

### The derivative, and what was done to it

| Step | Result |
|---|---|
| Source | `Farmers Market.jpeg`, 2,895,485 bytes, 4032×3024 stored, EXIF Orientation **6**, iPhone 14 Pro, `DateTimeOriginal` **2026:05:23 10:28:34**, **no GPS**. Opened **read-only**; the Drive original is byte-unchanged (verified after) |
| Rotate | Orientation baked in → **3024×4032 portrait**, tag normalised to `1` so nothing double-rotates |
| Crop | Variant A `(0, 420, 2590, 3873)` → **2590×3453**, aspect 0.7501. Price-sign left edge is x≈2620, so a **30px margin** excludes it |
| Resize | **1400×1867** — the same frame shape as the founder photograph, so it drops into the existing portrait treatment with no CSS aspect work |
| Encode | WebP, **257,336 bytes** |
| Metadata | GPS stripped unconditionally · Orientation normalised · **`DateTimeOriginal` retained** (the caption's provenance) · **camera make/model retained** — they corroborate an authentic photograph rather than a generated one |

⛔ **No colour grading, no sharpening beyond the resize filter, no retouching, no object removal.**
The whole value of the asset is that it is unmodified evidence of a real event.

**⚠️ Deviation from the spec, declared:** the spec asks for *"quality ≈ 82"* **and** *"< 260 KB"*.
**For this frame the two are not simultaneously satisfiable** — q82 gives 379,782 bytes and even q70
gives 285,136. The byte budget is the testable acceptance criterion, so quality was swept down to
**q60 → 257,336 bytes**, which is under 260,000, under 260 KiB, and under the founder photograph's
own 258,186. A dense outdoor scene of grass and foliage simply does not compress like a portrait.
**Recorded rather than silently chosen.**

### The two visual acceptance criteria — performed as visual tests, not inferred

The spec is explicit that criterion 2 must be *"a visual test performed by a person. Do not mark it
passed from the crop coordinates alone."* So the published derivative was **opened and looked at**,
including magnified crops, and the images are in the evidence folder.

- ✅ **Criterion 2 — the price sign is entirely outside the frame.** Viewed at 6×: the only acrylic
  object remaining is a small QR-code card. **No `$8.99`, no `$16`, no `save $1.98`, no price of any
  kind.** The three stale commercial claims (`$8.99` against a live `$11.99`; `BOTH BOOKS $16` for a
  now three-title catalogue; a superseded `save $1.98`) are gone.
- ✅ **Criterion 3 —** banner complete including *"Big Places. Brave Hearts."*, Andrew, table, plush
  dog and books all present.
- ✅ **Criterion 8 — the "WHAT READERS ARE SAYING" card is illegible.** Viewed at 5×: **no review
  word is readable and no star glyph resolves as a glyph.** Stated precisely: at 5× the *presence*
  of faint marks can be inferred; no shape and no word can be read, and at published size it is a
  grey smudge.
- ✅ **Criteria 4–7 —** 1400×1867, < 260 KB, WebP, explicit `width`/`height` (zero CLS by
  construction), `loading="lazy"` and `decoding="async"` (it cannot become LCP), alt text and
  caption exactly the specified strings, **no location named.**

### What the copy does and does not claim

**Alt:** *"Andrew Signore standing behind a Brave Hearts Publishing table at an outdoor farmers
market, with a pop-up canopy, a roll-up banner for Adventures of Charlotte and Henry, copies of the
paperbacks laid out, and a plush dog on the table."*
**Caption:** *"Brave Hearts at a farmers market, May 2026."*

⛔ **Absent and prohibited:** attendance, footfall, sales, queues, popularity, reactions, "meeting
readers", "signing books", or how the day went. In the photograph Andrew is holding two drink cups
with no customer at the table — **a caption describing him serving a reader would be a fabricated
scene.** The copy describes presence, not activity.
⛔ **The market and city are NOT named.** The file has no GPS; a named location is a factual claim
and is Andrew's to confirm.

**The banner carries the retired sunrise-heart logo, and that is deliberate** — Andrew approved it
as-is. It is a dated documentary photograph, and retouching a logo out of one would be the
dishonest act. The caption carries the date so the banner reads as history.

---

## 6 · Item 6 — `check_author_fingerprint()` (separate repository)

`brave-hearts-seo-engine`, local commit `09d1512`. **Independent repository, independent approval
gates — nothing was pushed and nothing was deployed.**

**Andrew's own founder attestation of 2026-08-03 confirms the Island Peak ascent, in Nepal, without
supplemental oxygen.** Island Peak (Imja Tse) is ≈20,300 ft, so *"20,000 feet"* is accurate.

> ⛔ **The verbatim sentence is deliberately NOT reproduced here. This repository is public on
> GitHub.** A founder's personal biographical statement from an internal session is pointed at, not
> copied — Standing Rules §4.1: *"a handoff POINTS AT a private source; it never COPIES private
> contents into a public file."* **The attestation lives at `Business OS\WORKING-DRAFTS\chief-of-staff\`
> (2026-08-03 morning register) and in the allowlist entry in the non-public
> `brave-hearts-seo-engine` repository, which has no git remote.** The underlying *fact* is already
> published by Andrew on the live blog, which is why the fact is stated above and only the internal
> wording is withheld.

**The guard is suppressed by attestation, never removed.** Every pattern remains in
`_PROHIBITED_PATTERNS` and now carries a stable key; a new `FOUNDER_VERIFIED_SPECIFICS` map names
the three released keys with the attestation, its date, its quote, its record and its scope. The
repository's own rule — *"do not remove entries from that list to make a specific brief pass"* — is
now **pinned by a test** that fails if a future edit deletes a pattern instead of allowlisting it.

- ✅ Released: `island-peak`, `without-oxygen`, `20000-feet`.
- ⛔ **`jiri` stays blocked** — the attestation did not mention it. One founder sentence releases
  exactly what it says and nothing adjacent.
- ⭐ **The never-embellish rail ships WITH the release, not after it**, because releasing a true
  claim is only safe if the ways of overstating it are blocked at the same moment. Three **new**
  prohibited patterns: altitude inflation above 20,000 ft, implying a summit of Everest, and
  claiming he climbed Everest. **Trekking in the Everest region is not summiting Everest.**

**213/213 tests pass.**

⭐ **Canonical record: `FOUNDER-DECISIONS-2026-08-01.md` FD-46 (PART 10).**

⚠️ **The sequence is left visible deliberately.** When the allowlist was written the number did
not exist — PART 9 ended at **FD-44** with no Island Peak entry — so the code said exactly that
rather than inventing one. **`business-ops-knowledge` minted FD-46 in a concurrent pass minutes
later**, and it is now recorded in the code. *"There was no number yet"* and *"nobody checked"*
look identical in a finished file, and only one of them is true.

⚠️ **FD-45 is deliberately absent, not an off-by-one.** It had already been allocated as an
alias resolving to FD-30, and a superseded row still owns its number.

⚠️ **`BHP-AGENT-STANDING-RULES.md` §3 still lists all four specifics as "unconfirmed founder
specifics … must not appear in any output".** Three of the four are now confirmed. **That file was
not edited** — it is `business-ops-knowledge`'s and Andrew's. A ready-to-paste amendment is in the
wave handoff, **PREPARED, NOT APPLIED.**

---

## 7 · Deploy record

| | |
|---|---|
| ZIP | 157 files, 4,357,011 bytes, md5 `8c449f2c2451b5c6a7133a67baceaca6` |
| Top-level folder | `brave-hearts-theme-deploy-explorer-expedition-guides` — matches the active slug |
| Method | `wp theme install /tmp/theme-1.19.146.zip --force --user=1`, **once** |
| Active after | `brave-hearts-theme-deploy-explorer-expedition-guides  1.19.146  active` |
| Fatal check | `wp eval 'echo "ok";'` → **ok** |
| Cache | `wp sg purge` → Dynamic Cache purged (file cache disabled on this host — pre-existing) |
| Deployed vs ZIP | **157/157 byte-identical, 0 mismatched, 0 missing** |
| PHP error log | **empty** after the deploy and the page fetches |
| Served assets | `style.css?ver=1.19.146`; the new WebP returns **200**, `image/webp`, 257,336 bytes, md5 matching the repo file exactly |
| Rollback | `~/bhp-STAGING-backup-1.19.145-20260802-wavee/theme` (full pre-deploy copy, `Version: 1.19.145` read back, 156 files) |

### ⚠️ A build-hygiene finding worth keeping — `CYCLE141-LD-21` and `-22`

**`CYCLE141-LD-21` — the first ZIP I built was a superset and would have shipped internal files.**
Building from `git ls-files` produced **284** files: `CLAUDE.md`, `.claude/rules/*.md`, `tests/`,
`reports/pre-launch-seo/`, `content-engine/`, `Logo.jpg`, `README.md`. The deployed theme is **156**
files. It was caught by diffing the ZIP's file set against the **actually deployed** set before
deploying, and the ZIP was rebuilt from the deployed list + the one intended addition = **157**.
➡ **Build the deploy set from what is deployed, not from what is tracked.** A secondary symptom
made it visible: SOP-06 step A8's `staging2` count read **28** on the bad ZIP and **5** on the
correct one — which is exactly the pre-existing count `CYCLE141-LD-8` documents.

**`CYCLE141-LD-22` — this deploy normalised line endings on 110 files.** Measured file-by-file
against the 1.19.145 backup:

| | Count |
|---|---|
| Files compared | 156 |
| Byte-identical | 43 |
| **Differ by line endings only (CRLF → LF)** | **110** |
| **Real content differences** | **3** — `front-page.php`, `inc/collection-gallery.php`, `style.css` |
| New files | 1 — the farmers-market WebP |

The previously-deployed 1.19.145 files carried **CRLF**; the local working tree is **LF**
(`core.autocrlf=true`, so git normalises on commit and this build shipped the normalised bytes).
**Functionally inert for PHP, CSS and JS, and the content delta is exactly the three intended files
— but "only three files changed" would have been false at the byte level, so it is recorded.**
➡ **This matters for the eventual production deploy:** the same normalisation will occur there, and
a post-deploy byte-diff against production's current files will show ~110 line-ending differences
that are not regressions.

---

## 8 · What was NOT done, stated explicitly

- ⛔ **No browser QA of any kind** — §"THE ONE THING THIS RELEASE RECORD CANNOT CLAIM".
- ⛔ **No production theme or plugin deploy.** Production is `1.19.142` / `1.8.10`, verified live.
- ⛔ **No push, no PR, no merge.**
- ⛔ **No hardcover product edited** (14 / 17 / 20) — prepared, not applied.
- ⛔ **No media-library upload** on either environment.
- ⛔ **The three classroom read-aloud photographs and the infant photograph were never opened for
  processing, cropped or uploaded** — `CYCLE141-CX-48`'s consent gate held. Identifiable children
  require written guardian consent for commercial use, and that is Andrew's, not an implementation
  detail.
- ⛔ **No MetaMetrics attribution wording written** — blocked, not invented.
- ⛔ **No price, stock, SKU, coupon, shipping, tax, payment or checkout setting** touched anywhere.
  Verified live on staging after the deploy: 1 zone, 1 `flat_rate` at the `3.99` base, **BookVAULT
  zoned = 0**.
- ⛔ **No heading or approved page copy rewritten.** See the flagged wording note below.
- ⛔ **`CHANGELOG.md` not edited** — §12 reserves it to `business-ops-knowledge`; a ready-to-paste
  block is handed over.
- ⛔ **No contradiction resolved, no `FD-` or `G-` number minted, nothing declared canonical.**

### ⚠️ Flagged, deliberately not changed

`/reluctant-reader-adventure-kit/` still heads its rail **"One flip-through"** while the uniform set
now contains **two** flip-throughs. FD-40 says *"format otherwise owner-approved"* and approved copy
is locked — **propose, do not rewrite.** The section's own `<h2>` is *"You can tell in one
flip-through."*, so the claim is over-satisfied rather than unmet. **Andrew's call whether the `<h3>`
should read "Two flip-throughs" or stay.**
