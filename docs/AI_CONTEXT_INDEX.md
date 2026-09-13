# AI Context Index

> ## ⭐⭐ NEWEST, 2026-09-12 (~20:1x MDT) - **PRODUCTION IS THEME `1.19.412` / BUNDLE PLUGIN `1.8.92`.** Every version number below this block is SUPERSEDED.
>
> | | Theme | Bundle plugin |
> |---|---|---|
> | **Production, 2026-09-12 ~20:1x MDT** | **`1.19.412`** | **`1.8.92`** |
>
> ⭐ **VERIFIED LIVE, not read from a document.** `wp theme list --status=active` over SSH returns
> `brave-hearts-theme-deploy-explorer-expedition-guides  active  none  1.19.412`, and `wp plugin list`
> returns `brave-hearts-bundle-pricing  active  none  1.8.92`, both read at **2026-09-12 ~21:1x MDT**.
> The production colouring PDP returned HTTP 200 in 0.49 s serving **26** `ver=1.19.412` markers and
> **0** `ver=1.19.411`. ⭐ **The plugin version was verified by WP-CLI, not inferred from asset markers.**
>
> **What moved production, 2026-09-12 ~20:01-21:00 MDT**, on Andrew's token and his word "go":
> theme `1.19.411` to **`1.19.412`** and bundle plugin `1.8.91` to **`1.8.92`**, followed immediately by
> the colouring-book product migration the release exists to make safe.
>
> ⭐⭐ **THE COLOURING BOOK'S PRODUCT ID CHANGED, AND EVERY DOCUMENT THAT SAYS `618` IS NOW WRONG
> ABOUT PRODUCTION.** The colouring book of record is **`946`** (variable parent: page, archive and
> thumbnail identity) with variation **`947`** (price, stock, SKU `9798996810840`, and the Bookvault
> link `bvlt_liked true` / `bvlt_locations {"locations":[1,3]}`), at `12.99`, in stock. **`618` is now
> `draft`** with SKU `9798996810840-OLD` and slug `...-legacy`, **kept as the rollback and not deleted**;
> product `899` (the failed 2026-09-08 attempt) **remains in the trash**. All verified live by read-only
> WP-CLI at this desk on 2026-09-12.
>
> ⚠️ **The trap this release exists to remove.** `wc_get_product_id_by_sku()` searches `product` AND
> `product_variation`, so on the migrated shape it returns **`947`**, not `946`. A caller treating that
> as "the product" gets a variation: `get_permalink()` yields nothing usable, a `post__in` product query
> never matches, `get_post_thumbnail_id()` returns `0`, and the PDP's `get_queried_object_id()` returns
> the parent, which no longer matches. **None of it throws - it silently stops being there.** `1.8.92`
> splits parent identity from buy identity; **use `bhp_colouring_parent_ids()` and
> `bhp_colouring_buy_ids()`, never a bare SKU lookup.**
>
> ⛔⛔ **THE COLOURING BOOK IS PURCHASABLE AGAIN, BUT "CONNECTED TO BOOKVAULT" IS NOT PROVEN AND MUST
> NOT BE WRITTEN AS PROVEN.** What is observed is that the link **fields** are present on `947`. Andrew
> declined a proof order on cost: "I dont have money to keep buying coloring books. Get it into
> production we test it live with new orders - if it doesnt work I do it manually." **The connectivity
> read is the next real customer order, watched**, with manual fulfilment pre-authorised as the fallback.
>
> ⛔⛔ **THIS RELEASE IS IN NO COMMIT ON ANY BRANCH, AND NEITHER IS `1.19.413`.** Verified first-hand
> 2026-09-12: `HEAD` is `ece5cd53ee59cd4558ff0e2f49e1cda7bb9ce752`, four days old, **titled "1.19.409"
> while containing `1.19.411`**; the current branch `feature/cycle180-colouring-resolver-1.19.412`
> **has no remote ref** (`git rev-list` against `origin/<branch>` fails with *unknown revision*); **no
> branch's committed `style.css` reads `1.19.412` or `1.19.413`**; the working tree holds 28 modified
> and 2 untracked files. ⛔ **There is therefore no rollback-to-commit path for the code production is
> serving** - the only rollback is a server-side tarball, whose existence nobody in the record has
> verified. ⛔ **`git log` is not a way to ask what production runs.** Use WP-CLI over SSH.
>
> ⚠️ **Known and recorded, not fixed here:** the colouring PDP hero renders `bhp-media-gallery--single`
> on production (**the carousel is absent**) while staging renders the full carousel. Confirmed live at
> this desk and reported first-hand by Andrew. **It is NOT a migration regression** - it was filed as
> `CYCLE180-CX-3` nearly four hours before the migration ran, and is a per-environment **media**
> difference, not code. Queued for `1.19.415` with two further founder notes (related-product card
> height at desktop; review-star styling - centred, larger, and live brand gold `#D9A45F`).
>
> ⭐ **Also live since 2026-09-12:** the pair landing page `/mariana-trench-book-and-coloring-book/`
> (page `943`, HTTP 200, "ADD THE SET" at `$22.99`), which is the QR target for the printed
> colouring-page handout; and the **first two approved customer reviews in the company's history**
> (comments `481` and `490`), from which an `aggregateRating` now renders on two PDPs.
>
> **Per-release detail:** `CHANGELOG.md`, the 2026-09-12 entry.
>
> ## ⛔ SUPERSEDED 2026-09-12 ~20:1x MDT — preserved, not deleted. This block was headed "⭐⭐ NEWEST, 2026-09-09 (00:52 MDT)" and was TRUE WHEN WRITTEN. **PRODUCTION WAS THEME `1.19.411` / BUNDLE PLUGIN `1.8.91` at 00:52 MDT on 2026-09-09; production is now THEME `1.19.412` / BUNDLE PLUGIN `1.8.92` — see the block above.** Every version number below this block is SUPERSEDED.
>
> | | Theme | Bundle plugin |
> |---|---|---|
> | **Production, 2026-09-09 00:52 MDT** | **`1.19.411`** | **`1.8.91`** (UNCHANGED) |
>
> ⭐ **VERIFIED LIVE, not read from a document, and re-verified on 2026-09-12.**
> `wp theme list --status=active` over SSH returns
> `brave-hearts-theme-deploy-explorer-expedition-guides  active  none  1.19.411`, and
> `wp plugin list` returns `brave-hearts-bundle-pricing  active  none  1.8.91`, both read at
> **2026-09-12 ~16:4x MDT**. `https://braveheartspublishing.com/` returned HTTP 200 in 0.49 s
> serving **17** `ver=1.19.411` markers, **0** `ver=1.19.409` markers and **6** bundle-plugin
> asset markers at `ver=1.8.91`; the home Mariana card reads **"From $11.99"** (3 occurrences).
> ⭐ **The plugin version was verified by WP-CLI this time, not inferred from asset markers.**
>
> Production moved once after the 22:03 release recorded below:
>
> 1. **2026-09-09 00:52 MDT** - theme `1.19.409` to **`1.19.411`**, carrying `1.19.410`.
>    `1.19.410` fixes a **live production defect**: the parent adventure-kit popup's × close
>    control did not close the popup at either width, because the photo `figure`
>    (`position:relative`) painted over the button (`position:absolute`) and neither carried a
>    `z-index`. `1.19.411` applies three founder-approved copy and type changes to the same
>    popup. Plugin **untouched** at `1.8.91`. Founder seals 1451-1454; Andrew: "we can push it
>    tonight" then "touched". Artefact `build-411.zip` md5
>    `592159e610928f0b74b3362b7d547804`.
>
> ⚠️ **THE RELEASE IS VERIFIED; THE DEFECT FIX IS NOT VERIFIED ON PRODUCTION.** The
> `1.19.411` version marker and the new copy line are confirmed live. ⛔ **But the defect was
> that a *click* did nothing, and no click has been performed against production by anyone in
> the record** - the fix is well-evidenced on staging2 (real click closes at 390 and 1440,
> Escape and overlay still work, 44×44 hit area kept, popup suite 85/0 with 12 new rows) and
> **staging2 is not production**. ⛔ **How long the defect was live is UNAVAILABLE**: it was
> observed at 22:46 on 2026-09-08 and removed at 00:52 on 2026-09-09, but nothing in the record
> establishes which release introduced it.
>
> ⛔ **The repository commit that contains this tree is `ece5cd5`, and its title reads
> "1.19.409" while its content is `1.19.411` + plugin `1.8.91`.** The commit is already pushed;
> correcting the message would rewrite published history and has deliberately not been done.
> Verified first-hand 2026-09-12: `HEAD` `ece5cd53ee59cd4558ff0e2f49e1cda7bb9ce752`, branch
> `feature/cycle179-review-seq-1.19.362`, working tree clean (0 entries), level with origin (0/0),
> `style.css` `Version: 1.19.411`.
>
> ## ⛔ SUPERSEDED 2026-09-09 00:52 MDT — preserved, not deleted. This block was headed "⭐⭐ NEWEST, 2026-09-08 (22:03 MDT)" and was TRUE WHEN WRITTEN. **PRODUCTION WAS THEME `1.19.409` / BUNDLE PLUGIN `1.8.91` at 22:03 MDT on 2026-09-08; the theme is now `1.19.411` — see the block above. The PLUGIN is UNCHANGED at `1.8.91`.** Every version number below this block is SUPERSEDED.
>
> | | Theme | Bundle plugin |
> |---|---|---|
> | **Production, 2026-09-08 22:03 MDT** | **`1.19.409`** | **`1.8.91`** |
>
> ⭐ **VERIFIED LIVE, not read from a document.** `https://braveheartspublishing.com/` returned
> HTTP 200 at 2026-09-08 ~22:1x MDT serving **17** `ver=1.19.409` markers and **6** bundle-plugin
> asset markers at `ver=1.8.91`; the home Mariana card reads **"From $11.99"** (3 occurrences).
> ⭐ **Both version numbers were verified live this time** - unlike the 06:07 block below, where
> the plugin version was read from the release record. The distinction is stated, not blurred.
>
> Production moved twice more on 2026-09-08 after the 06:07 release recorded below:
>
> 1. **2026-09-08 15:20 MDT** - theme `1.19.407` to **`1.19.408`** (home Mariana price by registry
>    identity; coloring page media script enqueued). Plugin unchanged at `1.8.89`. Founder seals
>    1436 ("Push 408") and 1439. Artefact `build-408.zip`, ZIP md5
>    `5084a80484cfb17de27bc4946c416789`.
> 2. **2026-09-08 22:03 MDT** - theme `1.19.408` to **`1.19.409`** and plugin `1.8.89` to
>    **`1.8.91`** (carrying `1.8.90`). Founder seal 1447; Andrew: "token touched". Artefacts
>    `build-409.zip` md5 `a7e3eb79d42b012b6cedc6399962c904` and
>    `brave-hearts-bundle-pricing-1.8.91.zip` md5 `d58b8799289f948ce66c728fe7ca2575`.
>    **All three ZIP md5s above were re-computed locally when this block was written and match
>    their release seals.**
>
> ⛔⛔ **A SECURITY-RELEVANT FILE WAS ON PRODUCTION AND `1.19.409` REMOVED IT.** The `1.19.408`
> artefact shipped `docs/security-investigation-nlo-finance-redirect-2026-07-09.md`, a file that
> `.gitattributes` marks `export-ignore` because it quotes malware IOC strings and tripped
> SiteGround's scanner on 2026-08-04. `1.19.409` removes it. ⭐ **Verified gone: a live request for
> that path returns HTTP 404.**
>
> ⚠️⚠️ **BUT THE CAUSE IS NOT ESTABLISHED, AND THIS FILE WILL NOT PRETEND IT IS.** The release seal
> attributes the leak to archiving without `--worktree-attributes`. **That does not explain the
> artefact:** `assets/covers` - 121 tracked files, governed by a line in the *same committed*
> `.gitattributes` - was correctly excluded from the very same `build-408.zip`. Both `export-ignore`
> lines were committed well before the build (the IOC line since `aaecd9f`, 2026-08-05) and both are
> present at the archive-time commit. **Until someone states the exact command that built
> `build-408.zip`, this incident has no established cause.** The durable fix is a preflight
> assertion that no `export-ignore` path appears in a deploy artefact - a control that holds
> whatever the mechanism turns out to be. Passing `--worktree-attributes` is strictly safer and
> should be done anyway; on this evidence it is not sufficient as an explanation.
>
> ⛔⛔ **NO COMMIT IN THIS REPOSITORY CONTAINS WHAT PRODUCTION IS RUNNING.** `HEAD` is `755d5ae`
> ("docs: release record for 1.19.401-407 and plugin 1.8.87-1.8.89", 2026-09-08 15:14:45 -0600),
> pushed, and its tree content is `1.19.408` - so even its own message understates it. The
> `1.19.409` and `1.8.91` changes are **uncommitted in the working tree: 11 modified files and 3
> untracked test files.** ➡ **This is the first thing to do when Andrew is back: commit the working
> tree.** Until then the only copies of what production runs are the ZIPs and the server itself.
>
> **Artefacts built and never released on their own, and which must not be deployed:** plugin
> `1.8.90` (its contents reached production inside `1.8.91`), in addition to everything already
> listed in the 06:07 block below.
>
> **Basis:** written 2026-09-08 ~22:2x MDT by `business-ops-knowledge` under workstream
> `CYCLE179-OPS-CARRY-1437-1447` (carrier 15), from sidecar items 1437-1447 plus first-hand
> read-only verification (`curl`, `md5sum`, `unzip -l`, `git log`/`status`/`show`). ⛔ **No git
> write command was run. This block is uncommitted.** Full evidence:
> `Business OS\20-CONFLICT-REGISTER.md` §124 and `17-CURRENT-OPERATING-STATE.md` PART 105.

> ## ⛔ SUPERSEDED 2026-09-08 22:03 MDT — preserved, not deleted. This block was headed "⭐⭐ NEWEST, 2026-09-08 (06:07 MDT)" and was TRUE WHEN WRITTEN. **PRODUCTION WAS THEME `1.19.407` / BUNDLE PLUGIN `1.8.89` at 06:07 MDT; it is now `1.19.409` / `1.8.91` — see the block above.** Every version number below this block is SUPERSEDED.
>
> | | Theme | Bundle plugin |
> |---|---|---|
> | **Production, 2026-09-08** | **`1.19.407`** | **`1.8.89`** |
>
> ⭐ **VERIFIED LIVE, not read from a document:** `https://braveheartspublishing.com/` returned
> HTTP 200 serving `ver=1.19.407` when this correction was written, 2026-09-08 ~06:35 MDT.
> The plugin version is **read from the release record (founder seal 1427)**, not independently
> re-verified here - the distinction is stated rather than blurred.
>
> Production moved twice in under seven hours, and **both moves were undocumented in this file
> until now**:
>
> 1. **2026-09-07 23:53 MDT** - theme `1.19.400` to **`1.19.404`**, carrying 1.19.401 to
>    1.19.404 (the cart band, the adventure-kit panel hardening, the preview-only kit panel).
>    Plugin unchanged at `1.8.86`. Founder seals 1396 (staging approval), 1397 (token), 1398
>    (execution).
> 2. **2026-09-08 06:07 MDT** - theme `1.19.404` to **`1.19.407`** and plugin `1.8.86` to
>    **`1.8.89`**, carrying 1.19.405, 1.19.406, 1.19.407 and plugin 1.8.87, 1.8.88, 1.8.89.
>    Founder seal 1427. **Andrew did not do his own staging look on this push.**
>
> **Artefacts that were built and never released on their own, and must not be deployed:**
> `1.19.401` (superseded within the hour by `1.19.402`), `1.19.405` and `1.19.406` (their
> contents reached production inside `1.19.407`), plugin `1.8.87` and `1.8.88` (inside
> `1.8.89`), and the superseded `-r1` rebuild of the `1.19.407` ZIP (`6d1f702e...`). **The
> 1.19.407 artefact of record is ZIP md5 `97360f03786d549de57bcc35d8b8134f`.**
>
> ⚠️ **Two things are live and unsettled, and they are not buried in the detail:**
> the placeholder string `"Temporarily unavailable"` is on the coloring product page **without
> the owner's wording approval**; and **product 618 (the Mariana coloring book) is out of stock
> by founder ruling** until Bookvault links the title, which is what makes the whole coloring
> stock gate visible at all.
>
> **Per-release detail:** `CHANGELOG.md`, the 2026-09-08 entry headed "PRODUCTION IS NOW THEME
> `1.19.407`" and the 2026-09-07 (23:53 MDT) entry headed "PRODUCTION MOVED TO THEME
> `1.19.404`". Both were written after the fact on 2026-09-08 to close a gap in that file.

> ## ⭐⭐ NEWEST, 2026-09-03 - **PRODUCTION IS THEME `1.19.358` / BUNDLE PLUGIN `1.8.83`.** The block immediately below, which records `1.19.356` / `1.8.81`, is **SUPERSEDED ON BOTH VERSION NUMBERS** and is preserved rather than rewritten.
>
> Two theme releases and two plugin releases were built and staging-verified on 2026-09-03. **Theme
> `1.19.358` and bundle plugin `1.8.83` were deployed to production on 2026-09-03**, with the owner's
> explicit approval. `1.19.357` and plugin `1.8.82` did not ship on their own; their contents reached
> production inside the later artefacts. **Those two artefacts are superseded and must not be deployed.**
>
> **Canonical for this series:** `RELEASES/PRODUCTION_RELEASE_1_19_357_358.md` (release record, contents,
> tests, rollback artefact names, the customer-visible behaviour change, known issues) and the
> 2026-09-03 `CHANGELOG.md` entry headed "PRODUCTION IS NOW THEME `1.19.358`" (per-release detail).
> Open issues: `KNOWN_ISSUES.md`. Rules recorded from this series: `DECISIONS.md`.
>
> ⚠️ **Recorded from the deploying lane, not read from production by this block.** Verify with
> `wp theme list --status=active` and `wp plugin get brave-hearts-bundle-pricing --field=version` over SSH
> before quoting these numbers.
> ## ⭐⭐ NEWEST, 2026-09-02 - **PRODUCTION IS THEME `1.19.356` / BUNDLE PLUGIN `1.8.81`.** The block immediately below, which records `1.19.354` / `1.8.79`, is **SUPERSEDED ON BOTH VERSION NUMBERS** and is preserved rather than rewritten.
>
> Two theme releases and two plugin releases were built and staging-verified on 2026-09-02. **Theme
> `1.19.356` and bundle plugin `1.8.81` were deployed to production on 2026-09-02**, with the owner's
> explicit approval. `1.19.355` and plugin `1.8.80` did not ship on their own; their contents reached
> production inside the later artefacts. **The `1.8.80` artefact is superseded and must not be deployed.**
>
> **Canonical for this series:** `RELEASES/PRODUCTION_RELEASE_1_19_355_356.md` (release record, contents,
> tests, rollback artefact names, the one customer-visible behaviour change, known issues) and the
> 2026-09-02 `CHANGELOG.md` entry headed "PRODUCTION IS NOW THEME `1.19.356`" (per-release detail).
> Open issues: `KNOWN_ISSUES.md`. Rules recorded from this series: `DECISIONS.md`.
>
> ⚠️ **Recorded from the deploying lane, not read from production by this block.** Verify with
> `wp theme list --status=active` and `wp plugin get brave-hearts-bundle-pricing --field=version` over SSH
> before quoting these numbers.
> ## ⭐⭐ NEWEST, 2026-09-02 (later the same day) - **PRODUCTION IS THEME `1.19.354` / BUNDLE PLUGIN `1.8.79`.** The correction block immediately below, which records `1.19.349` / `1.8.78`, is **SUPERSEDED ON BOTH VERSION NUMBERS** and is preserved rather than rewritten.
>
> Five theme releases were built and staging-verified on 2026-09-02. **`1.19.353` then `1.19.354` were
> deployed to production on 2026-09-02**, each with the owner's explicit approval, and **bundle plugin
> `1.8.79` was deployed to production on 2026-09-02**. `1.19.350`, `1.19.351` and `1.19.352` did not ship on
> their own; their contents reached production inside `1.19.353`.
>
> **Canonical for this series:** `RELEASES/PRODUCTION_RELEASE_1_19_350_354.md` (release record, contents,
> tests, rollback artefact names, known issues) and the 2026-09-02 `CHANGELOG.md` entry headed "PRODUCTION
> IS NOW THEME `1.19.354`" (per-release detail). Open issues: `KNOWN_ISSUES.md`. Rules recorded from this
> series: `DECISIONS.md`.
>
> ⚠️ **Relayed, not read from production by this block.** Verify with `wp theme list --status=active` and
> `wp plugin get brave-hearts-bundle-pricing --field=version` over SSH before quoting these numbers.

> ## ⛔⛔ CORRECTION 2026-09-02 - **PRODUCTION IS THEME `1.19.349` / BUNDLE PLUGIN `1.8.78`.** Every version line below this block, INCLUDING the 2026-08-03 correction block, is **SUPERSEDED ON THE VERSION NUMBER**.
>
> ⭐ **VERIFIED WITH THE DEFINITIVE INSTRUMENT, 2026-09-02, and this is the difference from the block
> below it.** Read-only over SSH against the production document root:
> `wp theme list --status=active` returns **`1.19.349`** and
> `wp plugin get brave-hearts-bundle-pricing --field=version` returns **`1.8.78`**.
>
> **Corroborated independently** by a read-only HTTP GET of the production home page (HTTP 200,
> 248,926 bytes, canonical `https://braveheartspublishing.com/`, zero `staging2` occurrences):
> **14 theme assets enqueued at `ver=1.19.349`** and **6 plugin assets at `ver=1.8.78`**.
> **Two instruments, agreeing.**
>
> ⛔ **No production write of any kind was made by the pass that wrote this block.** The only production
> contact was the read-only version query above and the read-only GET. `wp eval` and `wp eval-file` are
> blocked against production by the `G1-PRODUCTION-WRITE` gate by design (`CYCLE179-LD-002`, OPEN, and
> recorded in `RUNBOOK.md`'s production verification checklist).
>
> ⚠️ **A CONTRADICTION IS RECORDED HERE RATHER THAN RESOLVED, per the refusal duty.** The build brief
> that authorised this documentation pass stated that production **"stays 1.19.344 tonight"** and that
> the 1.19.349 production deploy **"did not happen"**. **Both instruments say otherwise.** The
> engineering lane that found it does not own the question of how 1.19.349 reached production, and has
> not answered it. **Routed to `chief-of-staff` and to Andrew. Recorded, not decided.**
>
> ⭐ **Production moved from `1.19.157` to `1.19.349` between the block below and this one**, and this
> file recorded none of it. The releases immediately preceding the current state are written up in
> `docs/CHANGELOG.md`: **1.19.342**, **plugin 1.8.77**, **1.19.343 / 1.8.78**, **1.19.344**,
> **1.19.345 / plugin 1.8.79**, **1.19.346**, **1.19.347**, **1.19.348** and **1.19.349**. **Everything
> between `1.19.157` and `1.19.342` still has no record here, and that gap is named rather than filled.**
>
> **Every block and header line below is preserved verbatim rather than edited, so the movement stays
> visible and is not re-derived.**

> ## ⛔⛔ CORRECTION, SAME DAY — **PRODUCTION IS THEME `1.19.157` / BUNDLE PLUGIN `1.8.16`.** The header line below says `1.19.156` and is **SUPERSEDED ON THE VERSION NUMBER**.
>
> **Verified live 2026-08-03:** the production home page (HTTP 200) enqueues **11 theme assets at `ver=1.19.157`** and 4 plugin assets at `ver=1.8.16`.
>
> ⭐ **Production shipped THREE times on 2026-08-03:** `1.19.155` → `1.19.156` (transactional email copy layer) → `1.19.157` (Bookvault dispatch tracker, shipped in **DRY** mode).
>
> ✅ **THE 1.19.156 RECORD GAP IS NOW CLOSED, and a 1.19.157 record exists.** Both were written from the builder's own commit messages, `git show --stat` counts, the builder's writer-lock closeout table and live verification — **not** from a prepared builder report, because none exists. **Each record says so on its face.** New: `RELEASES/PRODUCTION_RELEASE_1_19_156.md` and `RELEASES/PRODUCTION_RELEASE_1_19_157.md`.
>
> ⚠️ **The definitive instrument was NOT run for this correction.** `wp theme list --status=active` requires SSH, and the session that wrote this holds no SSH credentials. **The HTTP enqueue-version check is strong live evidence and is not the definitive check.**
>
> **The header line below is preserved verbatim rather than edited, so the movement stays visible.**

**Last updated: 2026-08-03 — ⛔ PRODUCTION IS THEME `1.19.156` / BUNDLE PLUGIN `1.8.16`, verified live at closeout. Production shipped TWICE this day: 1.19.155 (fully recorded) then a theme-only 1.19.156 email-copy layer (⚠️ NO release record exists for it — gap named in `NEXT_TASK.md`).** Maps every major topic to its one authoritative source. If two documents seem to cover the same topic, this file says which one wins — do not treat a non-canonical document as current truth.

| Topic | Authoritative document | Visibility | Owner | Last verified | Canonical | Superseded documents |
|---|---|---|---|---|---|---|
| ⭐ **Current production THEME version — `1.19.157`** · the Bookvault dispatch tracker, its DRY-mode default, credential handling, rollback and live-fire test window | `RELEASES/PRODUCTION_RELEASE_1_19_157.md` | Public-safe | Engineering + Andrew (approval) | **2026-08-03 — production version verified live over HTTP (`ver=1.19.157`, 11 assets); branch, HEAD, worktree version and clean status verified by `git`** | **Yes** | Supersedes the "no record exists" row below, and the `1.19.156` version headline everywhere |
| ⭐ **The 1.19.156 release — the transactional email copy layer E1–E7, and the footer-filter defect found by rendering** | `RELEASES/PRODUCTION_RELEASE_1_19_156.md` | Public-safe | Engineering + Andrew (approval) | **2026-08-03 — written from the builder's verbatim commit messages, `git show --stat`, and the builder's own deployment closeout record** | **Yes, for its own release.** ⚠️ Its version headline is superseded by 1.19.157 | ⭐ **Closes the documented gap in the row below** |
| ⭐ **The 1.19.155 release — six layers, per-layer rollback paths, and what the release did NOT prove.** ⚠️ Its **non-theme** layers (products 333/15/12, page 3, thumbnails, seven email/site options) are still current on production; its **version headline is superseded** | `RELEASES/PRODUCTION_RELEASE_1_19_155.md` | Public-safe | Engineering + Andrew (approval) | **2026-08-03 — verified live via `wp theme list --status=active`, `wp plugin list`, `wp option get`, `wp eval` and seven HTTP checks; corrected in place at closeout when production moved to 1.19.156** | Yes, **for its own release** | Supersedes `RELEASES/PRODUCTION_RELEASE_1_19_142.md` for the layers it covers |
| ⛔ ~~**Current production THEME version — `1.19.156`**~~ ✅ **GAP CLOSED 2026-08-03** | ~~No release record exists.~~ → **`RELEASES/PRODUCTION_RELEASE_1_19_156.md`** | Public-safe | Engineering | **2026-08-03 — verified live; `ver=1.19.156` also confirmed in live page source** | ⛔ ~~NO — documented GAP~~ → ✅ **The record now exists.** ⭐ **The concern that stopped it being written was right and is respected in how it was closed:** it was written **not** by reconstructing a QA narrative, but from the builder's own verbatim commit messages, `git show --stat` counts and the builder's own writer-lock closeout table, **with every unverified item named as unverified.** The record states its own provenance on its face and claims no QA step it cannot evidence | Row superseded by the two `1.19.156` / `1.19.157` rows above |
| ⭐ **Building and deploying a theme ZIP — the corrected `git archive` line and the mandatory pre-install entry-count assertion** | `RUNBOOK.md` §"Build and deploy a theme ZIP" | Public-safe | Engineering | **2026-08-03 — corrected; the prior line produced 180 files against a real artefact of 356 and would have deleted live `woocommerce/` and `tests/` directories** | Yes | The superseded line is quoted in place in `RUNBOOK.md` rather than deleted |
| **Collection gallery on the funnel pages** (placement map, the caller-side subset rule, the shared enqueue/render predicate) | `RELEASES/COLLECTION_GALLERY_FUNNEL_PAGES_1_19_143.md` + `inc/collection-gallery.php`'s own header | Public-safe | Engineering | 2026-08-02 — ⛔ **BUILT AND COMMITTED, NOT DEPLOYED, NOT BROWSER-VERIFIED** | Yes | — |
| **"Look Inside" gallery component and media registry** | `inc/book-media.php` (registry + provenance) · `template-parts/commerce/look-inside.php` (component contract) · `RELEASES/GALLERY_ASSETS_ANALYTICS_1_19_141.md` (assets + the five analytics events) | Public-safe | Engineering | 2026-08-02 | Yes | — |
| **GTM triggers/tags for the five gallery events** | `Business OS\WORKING-DRAFTS\lead-developer\DRAFT-2026-08-03-GTM-GALLERY-TRIGGER-SPEC.md` — ⚠️ **private working draft, outside this repo; PREPARED, NOT APPLIED.** Container status stays `ANALYTICS/GTM_STATUS.md` | Pointer only | Engineering + Andrew (gate) | 2026-08-02 | Yes (for the spec) | — |
| Company/CSO strategy | *(private — see `CSO_PRIVATE_REFERENCE.md`)* | Private | Andrew | 2026-07-12 | Yes | — |
| Project state | `PROJECT_STATE.md` | Public-safe | Engineering | 2026-07-12 | Yes | — |
| Current task | `CURRENT_TASK.md` | Public-safe | Engineering | 2026-07-12 | Yes | — |
| Roadmap (technical) | `ROADMAP.md` | Public-safe | Engineering | 2026-07-12 | Yes | — |
| Decisions (technical/architectural) | `DECISIONS.md` | Public-safe | Engineering | 2026-07-12 | Yes | — |
| Production status | `ENGINEERING/PRODUCTION_STATUS.md` | Public-safe | Engineering | 2026-07-12 | Yes | — |
| Staging status | `ENGINEERING/STAGING_STATUS.md` | Public-safe | Engineering | 2026-07-12 | Yes | — |
| WooCommerce | `ENGINEERING/WOOCOMMERCE_STATUS.md` | Public-safe | Engineering | 2026-07-12 | Yes | — |
| Complete Collection (bundle pricing) | `ENGINEERING/WOOCOMMERCE_STATUS.md` ("Bundle pricing" section) | Public-safe | Engineering | 2026-07-12 | Yes | — |
| [PARENT_COUPON_CODE_SUPERSEDED] | `RELEASES/COLLECTION_COUPON_PRODUCTION.md` | Public-safe | Engineering | 2026-07-14 (public advertising removed, see `DECISIONS.md`'s Audience Coupon Policy) | Yes | — |
| Audience-coupon scope: how a coupon becomes Collection-only (the per-coupon meta flag, the ONLY route since plugin 1.8.29) | `RELEASES/BUNDLE_PLUGIN_SANITISATION_1_8_29.md` | Public-safe | Engineering | 2026-08-05 | Yes | — |
| Dashboard unit-economics model: where the amounts live and what an unseeded environment does | `RELEASES/BUNDLE_PLUGIN_SANITISATION_1_8_29.md` | Public-safe (carries no figure) | Engineering | 2026-08-05 | Yes | `dashboard-data-sources.md`, `kpi-definitions.md` |
| Mobile-experience release (theme 1.19.201, live on production 2026-08-05) | `RELEASES/MOBILE_EXPERIENCE_1_19_201.md` | Public-safe | Engineering | 2026-08-05 | Yes | — |
| Audience Coupon Policy (Frozen) | `ENGINEERING/FUNNEL_CONSTITUTION.md`, `DECISIONS.md` | Public-safe | Andrew (CSO) + Engineering | 2026-07-14 | Yes | — |
| Mailchimp | `ENGINEERING/MAILCHIMP_STATUS.md` | Public-safe | Engineering | 2026-07-16 (Educator toolkit delivered end to end; all 5 audience journeys built in Draft) | Yes | `Mailchimp-Production-Integration.md`, `Mailchimp-HubSpot-Architecture.md` (historical, root-level, superseded) |
| Audience funnel implementation matrix (at-a-glance per-audience component status) | `ENGINEERING/AUDIENCE_IMPLEMENTATION_MATRIX.md` | Public-safe | Engineering | 2026-07-16 | Yes | — |
| Educator toolkit delivery + Mailchimp manual-completion tracking | `ENGINEERING/MAILCHIMP_MANUAL_COMPLETION_REGISTER.md` | Public-safe | Engineering | 2026-07-16 | Yes | — |
| Adventure Kit | `ENGINEERING/MAILCHIMP_STATUS.md` ("Active automations" section) | Public-safe | Engineering | 2026-07-06 | Yes | — |
| CTA Engine | `ENGINEERING/CTA_ENGINE_STATUS.md` | Public-safe | Engineering | 2026-07-12 | Yes | `phase1d-organic-conversion-architecture.md` (superseded for live-status purposes; still useful for full architecture detail) |
| GTM | `ANALYTICS/GTM_STATUS.md` | Public-safe | Engineering | 2026-07-13 | Yes | `gtm-staging-build-2026-07-09.md`, `gtm-build-verification-2026-07-12.md`, `gtm-ga4-production-readiness-audit-2026-07-12.md` (historical session records, not canonical status) |
| GA4 | `ANALYTICS/GA4_STATUS.md` | Public-safe | Engineering | 2026-07-12 | Yes | — |
| Consent | `ANALYTICS/CONSENT_STATUS.md` | Public-safe | Engineering + Andrew (decision) | 2026-07-13 | Yes | `consent-privacy-decision-record.md` (historical, superseded for current-status purposes) |
| Production GTM/consent deployment | `RELEASES/PRODUCTION_CONSENT_DEPLOYMENT.md` | Public-safe | Engineering + Andrew (approval) | 2026-07-13 | Yes | `RELEASES/PRODUCTION_GTM_CONSENT_READINESS_AUDIT.md` (planning record, still useful for the dependency map/rationale, but superseded for current-status purposes now that the deploy has executed) |
| Production analytics validation (historical discovery of the plugin-staleness gap) | `RELEASES/PHASE10_PRODUCTION_ANALYTICS_VALIDATION.md` | Public-safe | Engineering | 2026-07-13 | Yes | Superseded for current-status purposes by the fix below; still useful for the original evidence/methodology |
| Bundle-pricing analytics-parity fix (current status of production ecommerce events) | `RELEASES/BUNDLE_PRICING_ANALYTICS_PARITY_PRODUCTION.md` | Public-safe | Engineering + Andrew (direction) | 2026-07-13 | Yes | — |
| SEO | `CONTENT/CONTENT_STATUS.md`, `CONTENT/BLOG_STATUS.md` | Public-safe | Content ops (primarily tracked in `brave-hearts-seo-engine` repo) | 2026-07-12 | Yes | `Technical-SEO-Analytics-Setup.md` (historical) |
| Blog content | `CONTENT/BLOG_STATUS.md` | Public-safe | Content ops | 2026-07-12 | Yes | — |
| Pinterest | `CONTENT/PINTEREST_STATUS.md` | Public-safe | Content ops | 2026-07-12 | Yes | — |
| Google Merchant Center | `MARKETING/GOOGLE_MERCHANT_STATUS.md` | Public-safe | Andrew (console) + Engineering (sync config) | 2026-07-13 | Yes | — |
| Product-media gallery workstream (all three titles) — remaining reshoot scope | `ROADMAP.md` → *Planned* → **"Authentic Mariana interior reshoot and gallery replacement"** (status QUEUED) | Public-safe | Andrew (capture/approval) + Commerce/CX (staging implementation) | 2026-08-02 | Yes | `NEXT_TASK.md` top owner TODO (2026-07-31) — **retained as historical context, superseded for the remaining reshoot scope** |
| Deployments | `RUNBOOK.md` | Public-safe | Engineering | 2026-07-12 | Yes | — |
| Rollbacks | `RUNBOOK.md` (rollback procedures section) | Public-safe | Engineering | 2026-07-12 | Yes | — |
| Known issues | `KNOWN_ISSUES.md` | Public-safe | Engineering | 2026-07-13 | Yes | — |
| Legacy blog content audit | `CONTENT/LEGACY_BLOG_CONVERSION_AUDIT.md` | Public-safe | Content ops + Engineering | 2026-07-13 | Yes | — |
| Historical document classification | `HISTORICAL_DOCUMENT_INDEX.md` | Public-safe | Engineering | 2026-07-13 | Yes | — |
| Desktop header layout fix | `RELEASES/HEADER_LAYOUT_FIX_PRODUCTION.md` | Public-safe | Engineering | 2026-07-13 | Yes | — |
| "Printed Just for You" print-on-demand notice | `ENGINEERING/PRINTED_FOR_YOU_STATUS.md` | Public-safe | Engineering + Andrew (approved copy + production deploy) | 2026-07-13 | Yes | — |
| Conversion QA Sprint 1 (full funnel validation, findings) | `ENGINEERING/CONVERSION_QA_SPRINT1.md` | Public-safe | Engineering + Andrew (decision on P0) | 2026-07-13 | Yes | — |
| **Frozen Funnel Architecture (permanent company policy, read first)** | `ENGINEERING/FUNNEL_CONSTITUTION.md` | Public-safe | Andrew (permanent decision) | 2026-07-14 | Yes | — |
| Shared audience-funnel architecture (naming/tracking/page-structure spec) | `ENGINEERING/AUDIENCE_FUNNEL_ARCHITECTURE.md` | Public-safe | Engineering | 2026-07-13 | Yes | — |
| Per-audience implementation status at a glance (which components are live/staged/not built, all 5 audiences) | `ENGINEERING/AUDIENCE_IMPLEMENTATION_MATRIX.md` | Public-safe | Engineering | 2026-07-15 | Yes | — |
| Screenshot-driven fixes A–G (hero caption/transform bounds, mobile dvh centering, question gap, result compaction, WPConsent gear layering, nav breakpoint, homepage quiz consolidation) | `RELEASES/SCREENSHOT_FIXES_1_19_121.md` | Public-safe | Engineering | 2026-07-31 (staging 1.19.121; production untouched at 1.19.112) | Yes | — |
| Homepage hero mobile reading order (`aside_after_title`, shared-hero component contract) | `RELEASES/HOMEPAGE_HERO_MOBILE_ORDER_1_19_120.md` | Public-safe | Engineering | 2026-07-31 (staging 1.19.120; production untouched at 1.19.112) | Yes | — |
| Quiz question-screen fit (two-column answer grid, no-scroll geometry, step state classes) | `RELEASES/QUIZ_QUESTION_FIT_1_19_119.md` | Public-safe | Engineering | 2026-07-31 (staging 1.19.119; production untouched at 1.19.112) | Yes | — |
| Quiz question screens (header removal, typography, answer alignment, dialog accessible name) | `RELEASES/QUIZ_QUESTION_SIMPLIFICATION_1_19_118.md` | Public-safe | Engineering | 2026-07-31 (staging 1.19.118; production untouched at 1.19.112) | Yes | — |
| Find Your Adventure quiz (per-answer results, copy, modal scroll behavior) | `RELEASES/QUIZ_UX_PERSONALIZATION_1_19_93.md` | Public-safe | Engineering | 2026-07-29 (staging 1.19.93; production untouched at 1.19.91) | Yes | — |
| Parent Funnel implementation status | `ENGINEERING/PARENT_FUNNEL_STATUS.md` | Public-safe | Engineering + Andrew (production approval + Mailchimp completion) | 2026-07-13 (Mailchimp blocker resolved 2026-07-14, see `MAILCHIMP_STATUS.md`) | Yes | — |

## Notes on using this index
- **"Canonical: Yes"** means: if this document conflicts with a non-canonical/superseded one, this document wins — go verify live state, don't average the two.
- **Superseded documents are not deleted.** They stay in the repo as historical session records (useful for "why did we decide this" archaeology) but must never be cited as current status.
- **Private-strategy topics** (company-level revenue targets, channel strategy, competitive positioning) have no public-repo document at all by design — see `CSO_PRIVATE_REFERENCE.md` for where that content actually lives.
- This table itself can go stale. If a "Last verified" date is more than a couple weeks old, re-verify the live system before trusting the document as current — see `DOCUMENTATION_GOVERNANCE.md`.
