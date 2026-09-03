# Project State — Brave Hearts Publishing (Executive Summary)

> ## ⭐⭐ NEWEST, 2026-09-02 · **PRODUCTION IS THEME `1.19.356` / BUNDLE PLUGIN `1.8.81`.** Every version number below this block is SUPERSEDED.
>
> **Theme `1.19.356` and bundle plugin `1.8.81` were deployed to production on 2026-09-02, with the
> owner's explicit approval.**
>
> `1.19.355` and plugin `1.8.80` were built and staging-verified on 2026-09-02 and **did not ship on their
> own**; `1.19.356` is built on top of the `1.19.355` tree and `1.8.81` on top of `1.8.80`, so their
> contents reached production inside the later artefacts. **Production moved two theme releases and two
> plugin releases at once.** The `1.8.80` artefact is superseded and must not be deployed anywhere.
>
> | | Theme | Bundle plugin |
> |---|---|---|
> | **Production, 2026-09-02** | **`1.19.356`** | **`1.8.81`** |
>
> **Release record:** `RELEASES/PRODUCTION_RELEASE_1_19_355_356.md`.
> **Per-release detail:** `CHANGELOG.md`, the 2026-09-02 entry headed "PRODUCTION IS NOW THEME
> `1.19.356`".
> **Open issues at the end of this series:** `KNOWN_ISSUES.md` (F-08, F-09, LD-11, LD-14, LD-17,
> LD-18, LD-19, LD-20, LD-21 and the `--url` test caveat).
> **Three rules recorded from this series:** `DECISIONS.md` (an explicit visit slug decides the session; the
> mobile catalog pair is made by flattening containers, not by moving markup; the bundle saving is computed
> at render and fails closed).
>
> ⭐ **One customer-visible behaviour changed in this series and it is not a layout change.** A parent
> flagged for one school who opens a different school's **closed** visit link now loses the first flag, and
> it does not return on its own; reopening their own link restores it and restarts the 14-day window. An
> unknown or truncated slug still does nothing. Full wording for anyone answering a customer:
> `RELEASES/PRODUCTION_RELEASE_1_19_355_356.md` section 5.
>
> ⚠️ **Provenance, stated rather than implied.** This block was written by the documentation lane from the
> build lanes' own records. **The version numbers above are recorded from the deploying lane, not read from
> production by this block.** Before relying on them for anything consequential, re-run the definitive
> instrument: `wp theme list --status=active` and
> `wp plugin get brave-hearts-bundle-pricing --field=version` over SSH, read-only. Every correction block in
> this file's history exists because a version number was trusted past its verification.
> ## ⭐⭐ NEWEST, 2026-09-02 · **PRODUCTION IS THEME `1.19.354` / BUNDLE PLUGIN `1.8.79`.** Every version number below this block is SUPERSEDED.
>
> **`1.19.353` and then `1.19.354` were deployed to production on 2026-09-02, each with the owner's
> explicit approval. Bundle plugin `1.8.79` was deployed to production on 2026-09-02.**
>
> `1.19.350`, `1.19.351` and `1.19.352` were built and staging-verified the same day and **did not ship on
> their own**; they are cumulative builds of the same tree, so their contents reached production inside
> `1.19.353`.
>
> | | Theme | Bundle plugin |
> |---|---|---|
> | **Production, 2026-09-02** | **`1.19.354`** | **`1.8.79`** |
>
> **Release record:** `RELEASES/PRODUCTION_RELEASE_1_19_350_354.md`.
> **Per-release detail:** `CHANGELOG.md`, the 2026-09-02 entry headed "PRODUCTION IS NOW THEME `1.19.354`".
> **Open issues at the end of this series:** `KNOWN_ISSUES.md` (F-08, F-09, LD-10, LD-12, the `--url` test
> caveat, and the parked cosmetic list).
> **Two rules recorded from this series:** `DECISIONS.md` (the visit deadline resolver, and the catalog card
> on every catalog surface).
>
> ⚠️ **Provenance, stated rather than implied.** This block was written by the documentation-sync lane on
> 2026-09-02 from the build lanes' own records. **The version numbers above are relayed from the deploying
> lane, not read from production by this block.** Before relying on them for anything consequential, re-run
> the definitive instrument: `wp theme list --status=active` and
> `wp plugin get brave-hearts-bundle-pricing --field=version` over SSH, read-only. Every correction block in
> this file's history exists because a version number was trusted past its verification.
>
> **Content updates made on production the same day were made by the owner and are not theme releases.**

> ## ⛔⛔⛔⛔ FOURTH CORRECTION, SAME DAY — **PRODUCTION IS THEME `1.19.161` / PLUGIN `1.8.19`.** Every correction block below is **SUPERSEDED ON BOTH VERSION NUMBERS.**
>
> **Verified live 2026-08-03T23:07−06:00** by read-only HTTP GET of the production home page (HTTP 200): **11 theme assets enqueued at `ver=1.19.161`** and **4 plugin assets at `ver=1.8.19`**. `1.19.161` was independently read the same evening at **2026-08-03T22:21:38−06:00** by `wp theme list --status=active` over SSH — **two instruments, two sessions, same theme number.**
>
> ⭐ **Production shipped at least SIX times on 2026-08-03:** `1.19.155` → `1.19.156` → `1.19.157` → … → **`1.19.161`**. **The intermediate steps `1.19.158`–`1.19.160` have no release record in this file.** Their absence is named rather than filled — the records belong to the sessions that shipped them.
>
> ### ⚠️ TWO separate stale figures were corrected here, and only one of them was expected
>
> | Figure | Was recorded | Is actually live | How this block knows |
> |---|---|---|---|
> | **Theme** | `1.19.157` | **`1.19.161`** | HTTP enqueue check (this block) **and** `wp theme list --status=active` over SSH at 22:21:38 |
> | **Plugin** | `1.8.16` | **`1.8.19`** | HTTP enqueue check (this block) **only** |
>
> ⚠️ **The plugin drift was NOT anticipated and has no corresponding release record.** The block below asserts `1.8.16` as a live-verified fact, and it was true when made; **the plugin has since moved three patch versions with nothing here recording why.** ⛔ **Do not treat `1.8.19` as reviewed — only as observed.**
>
> ### ⚠️ What was NOT verified for this correction — stated because a partial check reported as a full one is the failure this file exists to prevent
>
> ⛔ **`wp theme list --status=active` was NOT run by the session writing this block** (no SSH credentials held; none sought). The theme number is corroborated by a *different* session's SSH read, cited above with its timestamp — **that is corroboration, not this session's own first-hand run of the definitive instrument.**
> ⛔ **`wp plugin list` was NOT run at all, by anyone, for `1.8.19`.** The HTTP enqueue check is the *only* evidence for it. **Strong live evidence; not the definitive instrument.**
> ⛔ **No deployed commit SHA was read** — no git command of any kind was run by this session. **The `Deployed to production` commit rows below are therefore UNVERIFIED as of this correction.**
> ⛔ **Staging was not checked** and no staging claim in this file is refreshed by this block.
>
> **Every block below is preserved verbatim rather than edited, so a reader sees the sequence rather than re-deriving it.**

> ## ⛔⛔⛔ THIRD CORRECTION, SAME DAY — **PRODUCTION IS THEME `1.19.157` / PLUGIN `1.8.16`.** Both correction blocks below are **SUPERSEDED ON THE VERSION NUMBER.**
>
> **Verified live 2026-08-03** by HTTP against the production home page (200): **11 theme assets enqueued at `ver=1.19.157`**, 4 plugin assets at `ver=1.8.16`.
>
> ⭐ **Production shipped THREE times on 2026-08-03.** `1.19.155` (six layers) → `1.19.156` (transactional email copy layer, E1–E7) → `1.19.157` (Bookvault dispatch tracker).
>
> ### What is now RECORDED that was not before
>
> | | |
> |---|---|
> | ✅ `RELEASES/PRODUCTION_RELEASE_1_19_156.md` | **NEW.** Closes the record gap the block below names |
> | ✅ `RELEASES/PRODUCTION_RELEASE_1_19_157.md` | **NEW.** The dispatch tracker |
>
> ### ⚠️ The tracker is LIVE CODE in a DORMANT STATE — read this before assuming anything about it
>
> **1.19.157 ships in DRY mode.** It polls the fulfilment API every three hours and **writes no order meta, no order note and no status change.** Its credential was installed by the owner, in the owner's own terminal, and is held by no agent. **The first authenticated read returned both open orders at `SentToPrint` — pre-dispatch.**
>
> ⛔ **The tracker has never completed an order and has never caused a customer email to be sent.** A supervised live-fire test is expected **~2026-08-11 to 08-12**, at which point switching it live is a separate act.
>
> ### ⚠️ What was NOT verified for this correction
>
> ⛔ **`wp theme list --status=active` was NOT run.** It requires SSH, and the session that wrote this holds no SSH credentials and did not look for any. **The HTTP enqueue-version check is strong live evidence; it is not the definitive instrument.** The two blocks below, which cite `wp theme list` directly, were made by a session that had it.
>
> **Both blocks below are preserved verbatim rather than edited, so a reader sees the sequence rather than re-deriving it.**

> ## ⛔⛔ CORRECTION, SAME DAY — **PRODUCTION IS NOW THEME `1.19.156` / PLUGIN `1.8.16`.** The block immediately below says 1.19.155 and is **SUPERSEDED ON THE VERSION NUMBER ONLY**.
>
> **Verified live 2026-08-03 at closeout:** `wp theme list --status=active` reads **1.19.156**; the live homepage enqueues **`ver=1.19.156`**; plugin unchanged at **1.8.16**.
>
> **What happened:** a **second** production deploy — the transactional-email **copy** layer — shipped from a concurrent build **while the 1.19.155 release record below was being written**. **1.19.155 was genuinely live and fully verified when that record was made**; production then moved again the same day.
>
> ⭐ **The block below remains accurate as the record of the 1.19.155 release** — its six layers, its per-layer rollback under stamp `20260803-110349`, and every product/page/option value it verified are **still true**, because 1.19.156 is a theme-only change on top of them. ⛔ **Only its "production is 1.19.155" headline is stale.**
>
> ⚠️ **A release record for 1.19.156 is NOT written here.** It belongs to the agent that shipped it. **Its absence is a gap, and it is named rather than filled** — see `NEXT_TASK.md`.
>
> ⭐ **The durable lesson: production moved twice in one day. Assert the live version from `wp theme list --status=active`, never from a document — including this one.**

> ## 2026-08-03 — **THE 1.19.155 PRODUCTION RELEASE** (headline version superseded above; everything else stands). Staging was the same. **Supersedes every version claim below, including the 1.19.149 block.**
>
> | | Theme | Plugin | Verified how |
> |---|---|---|---|
> | **Production** | **1.19.155** | **1.8.16** | `wp theme list --status=active` and `wp plugin list` over SSH, **after** the push, plus `ver=1.19.155` in the live page source |
> | **Staging** | **1.19.155** | **1.8.16** | same artefacts deployed |
>
> **A production push shipped on 2026-08-03**, collapsing thirteen staging builds into one deployment. Full record with per-layer rollback paths: **`RELEASES/PRODUCTION_RELEASE_1_19_155.md`**.
>
> **Six independent layers, each independently reversible:** theme · bundle plugin · two product `post_content` records (333, 15) · the privacy-policy sentence (page 3) · cart-thumbnail cropping + 92 regenerated derivatives · seven WooCommerce email/site options. Server-side rollback artefacts all carry the stamp `20260803-110349`.
>
> **Also verified live on production after the push:** product **333** = 1888 B and **15** = 1749 B, both now carrying only the standardized fulfilment sentence · product **12** = 2210 B, **unchanged by design, still naming the former print vendor** · page 3 = 8563 B, md5 `2a274067592a2d8ec341283e417904ff` · `woocommerce_thumbnail_cropping` = `uncropped`, product 333's cart thumbnail resolving **300×460** · `blogname` length **23** · email auto-sync **off**, base colour **#071522**, masthead attachment set, cancelled-order email **ENABLED** · `woocommerce_checkout_phone_field` still `required`, **not** changed by this push.
>
> ⛔ **BRANCH STATE — and read both rows, they are different facts.** The branch `feature/product-media-gallery-1.19.140` is **UNPUSHED**; Andrew pushes via GitHub Desktop. **Production is running code that has no remote copy.**
>
> | | Commit | Theme version |
> |---|---|---|
> | **Deployed to production** | **`e98cd0f`** | **1.19.155** |
> | **Local branch HEAD** (undeployed, unpushed) | **`237d71b`** — "the transactional email COPY layer, E1 through E7" | worktree `style.css` reads **1.19.156** |
>
> ⛔ **HEAD is NOT production.** `237d71b` landed from a concurrent build after this release shipped and has **not** been deployed. **Re-verified after it landed: production did not move — still 1.19.155 / 1.8.16.** ⭐ **Anyone asserting a production version from `style.css` in the working tree will be wrong by one release.**
>
> ⚠️ **Three things this release did NOT prove, stated rather than glossed:** no test order was placed and **no transactional email was read** · Apple Pay was **not** exercised on a real device · cart/checkout interaction geometry is React-rendered, was verified in a browser **on staging only**, and has not been re-verified in a browser against production.
>
> ⚠️ **New open item: `/book-bundles/` returns HTTP 404 on production.** It is a published page on staging (ID 356); on production ID 356 is an unrelated attachment. The plugin bump targeted that page, so the plugin change is **inert on production** and its release check cannot pass. See `KNOWN_ISSUES.md`.

> ## 2026-08-03 (superseded by the block above on every version number) — STAGING 1.19.149 / PLUGIN 1.8.12. PRODUCTION THEME 1.19.142, UNTOUCHED.
>
> Verified live this session via `wp theme list --status=active` on both environments. **Supersedes every version claim below.**
>
> Wave F (reconstructed after a mid-run laptop shutdown) + Wave G. **Two owner-ordered PRODUCTION CONTENT writes**, no production theme/plugin deploy:
> 1. **Review toggles** — canonical paperbacks 333/15/18 `open`, hardcover twins 14/17/20 `closed`, both environments. All six had `comment_count = 0`.
> 2. **Em-dash content sweep** — `wp_posts` `post_title`/`post_excerpt`/`post_content` only, per-post `wp_update_post`, never `wp search-replace`. **Staging 779→0 (56 posts), production 754→0 (55 posts).** En dashes protected and asserted. Slugs unchanged. Commerce guard (`_price`, `_regular_price`, `_stock_status`, `_sku`) byte-identical before/after on both environments. Rollback artifact `~/bhp-emdash-rollback-20260803-014627.json`.
>
> Full browser QA ran for the first time since Wave E: Chrome 151, `innerWidth` asserted, 14 pages × 2 viewports, and it **caught a real regression** (reversed lockup at natural 654×214 on WooCommerce pages, horizontal scroll) fixed in 1.19.149. Full record: `RELEASES/WAVE_F_G_HOMEPAGE_CONTRAST_EMDASH_1_19_149.md`.

> ## ⭐ NEWEST 2026-08-02 (WAVE E) — verify these before trusting anything below
>
> | | Theme | Plugin | State |
> |---|---|---|---|
> | **Staging** | **1.19.146** | 1.8.11 | uniform funnel set, educator exception, farmers-market element. Deployed once, fatal-checked, purged, 157/157 byte-identical to the ZIP |
> | **Production** | **1.19.142** | 1.8.10 | **theme UNTOUCHED.** Two `post_content` fixes only — see below |
>
> **Production content changed this wave (no deploy, `post_content` only):** Privacy Policy (3) and Terms (324) now name **Bookvault**, not Lulu — including the data-sharing disclosure; products **333** (Mariana PB) and **15** (Everest PB) carry per-title Lexile measures. **Product 18 (The Amazon) deliberately carries none.**
>
> ⛔ **Product IDs, corrected:** the Mariana Trench **paperback is 333**. **13 is an `attachment`** and **12 is a `draft` legacy-Lulu record.** A specification named 13 and was wrong. Guard every content edit with `post_type` + exact title.
>
> ⛔ **No browser QA exists for 1.19.146.** No viewport, no console, no screenshot — the building runtime had no browser tool. Rendered-HTML and server-side evidence only.
>
> **Rollback:** staging theme `~/bhp-STAGING-backup-1.19.145-20260802-wavee/theme`; all eight touched `post_content` records `~/bhp-WAVEE-backup-20260802/`.
>
> ---


## ⭐⭐⭐ NEWEST (2026-08-02, MORNING WAVE D) — **1.19.145 IS LIVE ON STAGING AND FULLY QA'd. Awaiting the owner's staging review.** Read this first; every block below it is history.

**Environment truth, read live over SSH and in a real browser this session, not inherited:**

| | Theme | Bundle plugin |
|---|---|---|
| **Staging** | **1.19.145** | 1.8.11 |
| **Production** | **1.19.142** (untouched) | 1.8.10 (untouched) |

**Three things shipped to staging as one build.** Full record: `RELEASES/BRAND_COMPASS_MODULE_AND_E1_EMAIL_1_19_145.md`.

1. **The compass brand mark replaced the retired sunrise-heart** — custom logo, site icon and the Rank Math Organization schema logo. ⭐ **Scope finding that matters for the production ship: `the_custom_logo()` was rendering NOTHING on either environment** (`has_custom_logo()` was `false` on staging *and* production), and the retired heart's only live appearance was in the **Organization schema**, a different setting entirely. **Production needs three settings changed plus a theme deploy, not a theme deploy alone.** Verified sitewide: 12 pages × 3 viewports, logo present 36/36, retired-heart references 0/36, zero console errors, no horizontal scroll, `window.innerWidth` recorded for every row. Header height +2.59px at 1440 and unchanged at 390 against a live production counterfactual.
2. **The compass module on `/gift-buyers-guide/`** — the printed Volume I dedication, the compass mark, "Not just a story. A compass.", one context line, between the FAQ and the final CTA. No CTA, no link, no analytics event inside it. Dedication diffed character-for-character. Rendered schema diff is **four lines**, all of them the deliberate logo change; zero review/rating/quotation markup added.
3. **The E1 order-confirmation email** — a WooCommerce processing-order template override (plus its plain-text twin) carrying the print-on-demand expectation-setting copy, **with no elapsed-time claim and no tracking promise**, both deliberately absent. Verified by **preview render against WooCommerce's dummy order with the mailer hard-blocked — zero mail sent, no order placed, staging order count unchanged at 8.**

**Two defects were found by this release's own QA and fixed before it settled** (the payoff line broke mid-sentence at every viewport; the E1 subject filter could never fire because WooCommerce populates the option with its own default before applying the filter). Both are written up with their measurements in the release record. **Two staging deploys this wave, deliberately** — 1.19.144 then the corrected 1.19.145.

**Open owner gates:** production deploy · the module's final rendered wording · which book's dedication is canonical (Volume I *"Be brave."* vs Volume II *"Be strong."*) · the header logo plate vs commissioning a reversed light-on-dark export · the E1 copy, subject and preheader · the mobile sticky-bar treatment.

**Rollback:** theme backup at `~/bhp-STAGING-backup-1.19.143-20260802-morningd/theme`; all three prior setting values recorded before being written; the four brand uploads are **new** attachments and the retired sunrise-heart (attachment 215) is **untouched and is the rollback**.

---


## ⭐⭐⭐ NEWEST (2026-08-02, MORNING) — **1.19.143 + plugin 1.8.11 ARE LIVE ON STAGING AND FULLY QA'd. Awaiting the owner's staging review.** Read this first; the block below it is history.

**Environment truth, every value read live over SSH this session, not inherited:**

| | Theme | Bundle plugin | `style.css` header |
|---|---|---|---|
| **Production** | **1.19.142** | **1.8.10** | 1.19.142 |
| **Staging** | **1.19.143** | **1.8.11** | 1.19.143 |
| Local branch `feature/product-media-gallery-1.19.140` | worktree 1.19.143 | 1.8.11 | — |

⭐ **This settles `CYCLE141-OPS-009`, the "three versions in circulation" confusion.** There was never a third version: the active production theme is **1.19.142**, and the `1.14.2` that a prior HTTP check returned came from **`/wp-content/themes/brave-hearts-theme/`, a legacy INACTIVE directory that genuinely is 1.14.2**. The active slug is `brave-hearts-theme-deploy-explorer-expedition-guides`. **Never read a version from a guessed theme-directory URL; read `wp theme list --status=active`.** Full evidence: `RELEASES/PRODUCTION_MEDIA_MIGRATION_2026-08-02.md` §5.4.

### What is now verified on staging, in a real browser

**8 pages × 2 viewports = 16 runs, `window.innerWidth` read back out of the page every time (1440→1440, 390→390, 16/16).** Zero console errors, zero page errors, zero failed requests, on every run. The six funnel pages each render exactly one gallery with the correct 1- or 3-item subset and the correct `1 / 3` counter; `/teachers/` correctly renders none.

⭐ **The regression check that the overnight build could not run — and correctly called its biggest gap — now PASSES:** `/complete-collection/` still renders **9 slides** with counter `1 / 9`, and the three product pages match production exactly (Mariana 7, Everest 8, Collection 9 on **both** environments). Slicing subsets in the caller genuinely did not disturb the registry.

### Two content corrections shipped, and they are a SEPARATE half from the code

- **The unsourced price superlative is gone.** "Lowest combined price direct from the publisher" — a comparative claim about every other seller, which nothing in the corpus could source — is removed from the Collection landing page and replaced with the plain, true "Direct from the publisher". Plugin **1.8.10 → 1.8.11**. Verified rendering on staging at both viewports; zero occurrences remain anywhere in code or in either database.
- **The 24-hour production delay claim is gone from the Terms page on BOTH environments** (a database change, which no theme deploy carries). Replaced with "Orders are printed on demand and dispatched by our print partner; timing varies." Occurrence-asserted, byte-diffed before writing, revisions preserved, purged, and **verified on the rendered page: 0 occurrences of any "24 hour" form on either environment.**

### ⛔ Two findings escalated, neither resolved

- **`CYCLE141-LD-11` — shipping tiers on distinct TITLES, not on number of books.** OBSERVED: **30 copies of one paperback renders shipping of $1.99.** The owner's recorded ruling reads *"Shipping is tiered per amount of books ordered"*; the code tiers per distinct title and ignores quantity outside mixed carts. Owner-gated; not changed.
- **`CYCLE141-LD-12` — production still names the former print vendor** in 2 Terms paragraphs and 3 Privacy Policy locations, **including the data-sharing disclosure naming which processor receives customer name, address and phone.** Staging was corrected in an earlier pass; production was not, because content never travels in a theme ZIP. Outside the narrow approval that was given; escalated with replacement text prepared.

### What has NOT happened

⛔ **No production deploy.** Production stays 1.19.142 / 1.8.10 until the owner reviews staging. ⛔ No push, PR or merge. ⛔ No WooCommerce price, stock, SKU, coupon, shipping, tax, payment or checkout field written on any environment — diffed before and after, unchanged. ⛔ No order placed. ⛔ No GTM configuration.

⚠️ **Funnel isolation is verified STRUCTURALLY, not behaviourally, and that distinction is deliberate.** Both popups are currently disabled by explicit `__return_false` filters (parent retired 2026-07-17 in favour of the quiz; teacher retired in `inc/audit-remediation.php`), so there was no popup to dismiss and no storage key to compare. Zero `localStorage` keys observed on all 16 runs, and the diff touches no funnel file. The same zero-popup condition holds on production. **Recorded as a limitation, not as a pass.**

---

## (HISTORICAL — superseded by the block above) 2026-08-02, OVERNIGHT — theme **1.19.143 is BUILT AND COMMITTED, and DEPLOYED NOWHERE.**

**Environment truth right now: production 1.19.142 / plugin 1.8.10 · staging 1.19.142 / plugin 1.8.10 · local branch `feature/product-media-gallery-1.19.140` at `567a30b`, `style.css` 1.19.143.** The worktree version is **not** proof of what is deployed, and in this case it is deliberately ahead of both environments.

⚠️ **Production's version was NOT re-verified by the engineer who wrote this block** — two read-only `wp` commands against the production document root were refused at the permission layer, the denial was accepted, and the 1.19.142/1.8.10 figure is carried from the prior release record. Staging's **1.19.142 was** verified live this session by `wp theme list --status=active`.

**What 1.19.143 does.** The Complete Collection gallery now renders on the six funnel pages that previously pitched the Collection in words and showed no product: the homepage, `/reluctant-reader-adventure-kit/`, `/gift-buyers-guide/`, `/organizations-community-reading-kit/`, `/educators-adventure-learning-toolkit/` and `/books/`. Each gets one instance of the **existing** component, in compact mode, below the fold, carrying a 1- or 3-item subset of the already-approved nine-item Collection set chosen for that page's actual question — the parent page leads with the flip-through video because its own heading is *"You can tell in one flip-through."*; the educators page shows **interiors only, no covers**. **No new media, no new component, no registry change.** `/teachers/` is deliberately excluded pending an Andrew routing decision.

**The subsets are sliced in the caller, never through the `bhp_book_media` filter** — that filter is global and applies on every call on every page, so trimming the set there would silently have trimmed `/complete-collection/`'s own nine-slide hero gallery. **OBSERVED on staging after the change: the full set still resolves to `count=9`.**

### ⛔ What is NOT true of 1.19.143, stated as failures

**It has never been deployed and never been opened in a browser.** `wp theme install --force` is refused at the permission layer against the **staging** doc root (`CYCLE141-LD-9`); the denial was accepted and no workaround attempted. There is therefore **no rendered check, no viewport measurement, no console-error count, no screenshot, and no regression check on `/complete-collection/` or the three product pages** for this build. The audited ZIP sits ready at `/tmp/theme-1.19.143.zip` on the server.

**What was verified live:** all six subsets resolve to exactly the requested items in the requested order against the real staging media library; fail-closed holds three ways (unknown slug → nothing; mixed → shrinks; empty → nothing); `php -l` clean on 10/10 changed files; ZIP audit 170 files, correct prefix, zero `docs/`/`tests/`/`plugins/`/`.git` entries.

### The six "unproven" test failures are resolved as PRE-EXISTING

All six carried from the 1.19.142 wave are now traced to root cause; **none is a regression, and one is not a failure at all** — `test-lead-event-log` scores **17 PASS / 0 FAIL** when run with the `--url=` flag its own docblock specifies. The rest date to 2026-07-10 (QA-gate `html_sanitation` conditions), 2026-07-11 (three `post_id`-degraded gate checks that make the assertion unreachable by construction) and 2026-07-19 (post 546 published, making a "not a registry member" fixture assumption false). **Structural proof: `git diff 7e56675..HEAD` touches zero `tests/` files and zero `inc/class-*.php`.**

### Gallery media on production — the earlier blocker is discharged

`CYCLE141-LD-1` (galleries render on staging, not production) is **closed**: the Wave-5 media migration landed, and the composite was OBSERVED emitting in ten registered sizes on production `/complete-collection/` on 2026-08-02. Blocks below that still describe production as gallery-less are **historical**.

---

## ⭐⭐ (2026-08-02, LATER) — **PRODUCTION IS NOW THEME 1.19.142 + BUNDLE PLUGIN 1.8.10.** Read this before the 1.19.142-staging-only block below it, which is now superseded on the version question.

**Deployed to production 2026-08-02 on Andrew's explicit approval, relayed verbatim by the Chief of Staff:** *"Confirm push to production. Approved follow up. IM ok with the image change - She is my niece - im not her parent but I have consent."* ⚠️ The executing engineer did not witness that message directly; the provenance is recorded in the release record rather than described as first-hand.

**Verified live after the deploy, not inherited from any document:** `wp theme list --status=active` → **1.19.142**; `wp plugin get brave-hearts-bundle-pricing` → **1.8.10**, active; **151/151 theme files and 44/44 plugin files byte-identical to the deployed ZIPs**; no duplicate theme directory; `wp eval 'echo "ok";'` → ok; `wp sg purge` run. **Zero production-only files were destroyed** — the pre-deploy manifest diff found 0 files on production that were absent from the ZIP. Fresh rollback point taken **before** anything was written: `~/bhp-PROD-backup-1.19.121-prewave4-20260802/` (theme + plugin tarballs, 147-file md5 manifest, product price/stock/SKU snapshot, and the pre-edit `post_content`/`post_excerpt` of all six products); the earlier `~/bhp-PROD-backup-1.19.121-20260731/` is also still intact.

**Live on production now:** the founder photograph on the homepage (asset already published on `/about/`, existing approved alt, caption corrected to *"Andrew and Charlotte"*, measured **463×610** at 1440 and **249×328** at 390 — identical to staging); the **expanded Kirkus quote** on the Complete Collection page via the existing centralised component; the five-star badge **scoped** to *"on our first two titles"*; and the unsourced *"Printed and shipped in the USA"* claim **removed**. Rendered QA at **1440 / 390 / 320**, every viewport confirmed by reading `window.innerWidth` back out of the page: **0 console errors, 0 page errors, 0 broken images, `aggregateRating` 0, `review` 0** on every page. Purchase path smoke-tested on production **without placing an order** (1 PB → $11.99 + $1.99 shipping + $0.72 tax = $14.70; 14 checkout inputs; 12 Stripe iframes; one shipping method; zero "BookVAULT"); **test cart emptied and confirmed empty**.

**A second, larger content batch also landed on BOTH environments** (`post_content` **and** `post_excerpt` only, 7 edits production / 6 staging, every one occurrence-asserted before writing and byte-verified on readback): the Lexile parenthetical removed from hardcovers **14/17/20**; the debunked oxygen opener replaced on **20**; the oxygen myth removed from the `post_excerpt` of **18** and **20** — which is what Google's SERP snippet, `og:description`, the JSON-LD `description` and the cart line item actually show; and the approved Bookvault fulfilment sentence restored to production **15** so it matches staging. **Residue now reads `Lexile=0 Lulu=0 oxygen20=0 fifthBreath=0 lungsOfEarth=0 oneInFive=0` across all six products, both fields, both environments**, with `grades 2–3` retained on all six. Prices, `_regular_price`, stock, SKUs and the shipping zone/method diffed **UNCHANGED** before and after.

⛔ **ONE ACCEPTANCE CRITERION FAILED, AND IT IS A BLOCKER, NOT A REGRESSION: the "Look Inside" galleries do not render on production.** All **29** gallery media slugs resolve on staging (28/29) and **0 of 29 on production** — the media library assets were never uploaded there. `inc/book-media.php` is fail-closed by design, so production renders no gallery section, no empty frame and **zero console errors**; it looks exactly as it did at 1.19.121. **No rollback is warranted.** Uploading the media was deliberately **not** done: that file's own provenance block records the Everest set as AI-assisted with two items carrying visible text artefacts, *"approved by him for staging"* — storefront use is a separate creative approval. `CYCLE141-LD-1`, `KNOWN_ISSUES.md`.

**Shipping documentation corrected** (owner ruling, verbatim: *"Andrew Signore, 2026-08-02: 'Shipping is tiered per amount of books ordered.'"*). The zone's `flat_rate` **$3.99** is the **base config**; the bundle plugin adjusts it to **$1.99 / $2.99 / $3.99 / $4.99** by format and quantity. **OBSERVED live on both environments:** a single paperback renders **$1.99**. Repo `CLAUDE.md` and `.claude/rules/woocommerce.md` updated, superseded wording retained. `CYCLE140-DEV-2` closed.

Full record: `RELEASES/PRODUCTION_RELEASE_1_19_142.md`. **The paragraph below is the superseded staging-only snapshot of the same build:**

## ⭐ 2026-08-02 (SUPERSEDED on the version question): staging theme **1.19.142** + bundle plugin **1.8.10**. Production theme unchanged at **1.19.121**. **Production PRODUCT CONTENT was changed** — read the next paragraph before assuming production is untouched.

**This entry supersedes every "production is untouched" statement below, and only in one specific respect.** No production theme or plugin file was deployed. But three **WooCommerce `post_content` fields on production** were corrected under Andrew's explicit 2026-08-02 approval: product **15** (Lulu claim removed), product **18** (debunked "20% of the world's oxygen" opener replaced with the copy approved 2026-07-06), and products **333/15/18** (uncertified Lexile "(500L–580L)" parenthetical removed, "grades 2–3" kept). The same Lexile and oxygen corrections were applied to **staging**. **Nothing but `post_content` was written** — prices ($11.99 PB / $17.99 HC), stock (all six `instock`), SKUs, coupons, shipping, tax, payment and checkout settings were all left alone and spot-checked afterwards on both environments.

**Why this needs its own paragraph:** product content is **database content, not repository content**. No theme ZIP has ever carried it and none ever will. That is exactly how "Printed and shipped by Lulu" survived on production for 24 days after being "fixed" on staging on 2026-07-09.

**Staging-only theme/plugin work in the same wave:** the homepage founder card now uses the real founder photograph already published on `/about/` (existing asset, existing alt); the Complete Collection page renders the **full Kirkus quote** through the existing centralised component instead of a bare badge; the five-star badge on the homepage and Collection page is scoped to **"on our first two titles"** (four 5-star reviews for Mariana, two for Everest, **zero** for The Amazon); and the unsourced **"Printed and shipped in the USA"** claim was removed from the Collection purchase panel.

**Evidence:** 1061 PASS / 6 FAIL / 0 fatals across 32 test files on staging; browser QA at 1440, 390 and 320 with each viewport confirmed via `window.innerWidth`; zero console errors, page errors or failed requests on every page checked; rendered JSON-LD still carries **zero** `aggregateRating` and **zero** `review`. Full record: `RELEASES/TRUST_AND_CONTENT_CORRECTIONS_1_19_142.md`. Open items this wave surfaced: `KNOWN_ISSUES.md` (short-description oxygen myth, hardcover Lexile, fulfilment wording).

**Not done:** no production theme/plugin deploy, no push/PR/merge, no `post_excerpt` edit, no hardcover product edit, and `CYCLE140-CX-9` (the bare ★★★★★ glyph run) deliberately unresolved — it is Andrew's call.

## HIGH PRIORITY OWNER WORKSTREAM (2026-07-31): Mariana product media

The next conversion asset task is a clean Mariana Trench product-gallery still set plus a short book flip-through. Andrew's hospital photographs successfully identified the strongest content—depth/STEM spread, submarine-pressure Ocean Fact, and the courage/character page—but are scouting references only because the artificial light, shadows, hands, countertop, orientation, and framing are not website-ready. Andrew will reshoot at home in indirect window light and submit the four-image approval set defined in `docs/NEXT_TASK.md` before completing the remaining stills or video. Higgsfield enhancement is deferred until clean source assets exist and is limited to subtle atmosphere outside the protected book artwork/text. Website implementation is not started and, when approved, must be staged and QA'd before any production decision.

**Last updated: 2026-07-30 — PRODUCTION DEPLOYED: theme v1.19.100 + bundle plugin v1.8.7, with Andrew's explicit approval. Staging and production are now level at 1.19.100 / 1.8.7.** Ships the quiz personalization/copy work, the Complete Collection Hardcover default, the deeper star gold, and the button optical-centring fix. Backup `~/bhp-PROD-release-backup-20260730-214515/`. Full production QA passed — see the section below and the `CHANGELOG.md` entry of the same date. (Prior header, superseded: 2026-07-30 — Quiz modal internal scroll-state correction, staging v1.19.96, production untouched at v1.19.91.) (Prior header, superseded: 2026-07-18 — Independent audit fixes (Educator Toolkit connected to /teachers/, early homepage audience gateway) implemented and QA'd on staging, theme v1.19.55.)** SEO metadata, internal-link updates, and the full Mailchimp design-system/15-email restyle remain outstanding — see `CURRENT_TASK.md` and `NEXT_TASK.md`. **Re-verify state-changing facts before trusting this if it's been more than a few days** — see `CLAUDE.md`.

## NEWEST (2026-07-31): PRODUCTION IS 1.19.121 — deployed on the owner's explicit approval. Staging and production are now level.

**Production theme v1.19.112 → v1.19.121. Staging unchanged at 1.19.121. Local == staging == production, 147/147 byte-identical.** This supersedes every "production remains 1.19.112" statement below.

Ships five accumulated staging releases as one package: **1.19.117** Homepage Phase 1a, **1.19.118** quiz question simplification, **1.19.119** quiz no-scroll fit, **1.19.120** hero mobile reorder, **1.19.121** screenshot fixes A–G.

**Stop gate:** identical 147 path sets, no production-only orphans, no new files, no build artifacts, exactly 11 content-differing files (the true 1.19.112→1.19.121 delta), no active writer. **Backup:** `~/bhp-PROD-backup-1.19.121-20260731/` — full 1.19.112 theme, 147-file md5 manifest, themes/plugins/products CSVs, lead-magnet options, `page_on_front`, price record — plus `~/bhp-PROD-theme-1.19.112-20260731.tar.gz` (3.8M). **Method:** ZIP built from the approved staging build and proven byte-identical to it before `wp theme install --force`; no selective copies, no version change, no new fixes during deploy. Caches purged (SiteGround assets + dynamic + `wp cache flush`); served assets report `?ver=1.19.121`.

**Live production QA across nine viewports (1440×900 → 667×375):** hero mobile order correct with DOM matching visual; caption 19.1–28.1px clear of the covers with zero overlap; desktop hero composition intact; nav never shows both modes; homepage has exactly 1 launcher / 1 modal / 1 quiz, no gateway, 0 duplicate IDs, 0 broken images, no horizontal overflow. **Quiz:** centred at 0.0px deviation, Q1 and all four Q2 routes fit with no scrolling, submit visible for all five offers everywhere, partnership form-free, 16/16 dismissals at 0px drift, focus trap both directions, timer + scroll + one-per-session auto-open all pass, consent gear behind the backdrop and not clickable. **Commerce:** three unified book pages with four correct format cards, Complete Collection still defaults to **Hardcover $48.99**, cart/checkout show 0 quiz instances, cart left empty, no purchase.

**Nothing else changed:** products, plugins, prices, lead magnets and `page_on_front` all diffed **UNCHANGED**; zero theme-file PHP errors; zero console errors. **Known, pre-existing and unchanged:** the WPConsent banner can cover the quiz close button at narrow widths while consent is unanswered — deliberate (consent must remain answerable); Escape and backdrop still dismiss.

## PRIOR (2026-07-31): STAGING 1.19.121 — seven screenshot-driven fixes (A–G).

**Staging theme v1.19.121, staging only, awaiting owner review — with a required real-device check.** Staging is now **five** releases ahead (1.19.117 → 1.19.121) and would ship as one package. 7 files changed; parity 147/147. **Quiz behaviour files byte-identical** (`quiz-modal.js`, `audience-quiz.js`, `audience-quiz.php`, `mailchimp.php`) — no routing, capture, tagging, redirect or trigger change.

Driven by the owner's 3 desktop + 4 real-iPhone screenshots, each defect root-caused before editing:

- **A** Caption painted behind the covers — the cover items' `translateY(24px)` + 3° rotation add **zero layout height**, and 1.19.120 had tightened the caption margin to 10px. Stack now reserves 28px for the overhang → **19.1px clear, zero overlap**, centered.
- **B** Mobile dialog was a bottom sheet (`align-items: flex-end` + `100vh` + top-only radius) → centered, `height: 100dvh` behind `@supports`, `max(12px, env(safe-area-inset-*))` padding, 16px radius all round. **0.0px vertical / 0.0px horizontal deviation at all 12 viewports**, including after auto-open and after orientation change while open.
- **C** A modal-scoped `margin-bottom: 10px` at (0,3,0) outranked the component rule → now **32 / 27.2 / 24.0 / 18px** at 1440 / 1024 / 768 / 390. **No question screen scrolls at any viewport**; answers never below 17px, cards never below 46px.
- **D** Result too tall, submit below the fold → offer measure 14ch → 20ch (3 lines → **2**), 16px copy floor, two-column fields ≥600px, 52px submit, 44px/16px inputs, `max-height: 600px` and `440px` tiers. **Submit visible for all five offers at all 12 viewports.** Three short viewports keep exactly one internal scroll region for the secondary links — reported as a scroll, not a pass.
- **E** `#wpconsent-consent-floating` is a **shadow-root child** at z-index 9999; modal was 2100 → raised to **10000**, above the gear and still far below the 900000 consent banner. `gearReceivesClicks: false` everywhere; nothing disabled or removed; auto-open deferral untouched.
- **F** The D2 touch-target rule sat at top level forcing `display: inline-flex`, overriding the base `display:none` and the container query → display moved back into `@container (max-width: 1116px)`. Verified either side: 1136 → nav only; 1096 → toggle **44×57** only. **Never both.**
- **G** Homepage consolidated: audience-gateway render and inline quiz removed (component files kept) → **exactly 1 launcher / 1 modal / 1 `[data-bhp-quiz]`**, 0 duplicate IDs. `#find-your-adventure` moved to the launcher wrapper (homepage only). Dead `.home #find-your-adventure` navy-section CSS deleted so it cannot repaint the launcher. Stale "two quizzes by design" comment corrected.

**Regression clean:** both auto-open triggers, one-per-session, 16/16 dismissals at 0px drift, focus trap both directions, cart/checkout exclusions (0 launchers/modals), prices $11.99/$17.99/$48.99, zero console errors, **no Mailchimp contact created**.

**Honest limitation, and the reason this is not called closed:** this environment has no Safari toolbars, so `dvh` == `vh` and the visual viewport never shrinks. **The real-iPhone condition behind the bottom-sheet and gear screenshots could not be reproduced.** Parts B and E require owner verification on the device. Rollback `~/bhp-STAGING-backup-screenshotfixes-20260731/`. Full record: `RELEASES/SCREENSHOT_FIXES_1_19_121.md`.

## PRIOR (2026-07-31): STAGING 1.19.120 — homepage hero covers move under the H1 on mobile. PRODUCTION UNCHANGED AT 1.19.112.

**Staging theme v1.19.120, staging only, awaiting owner review. Production remains 1.19.112, verified untouched.** Staging is now **four** releases ahead (1.19.117 Homepage Phase 1a, 1.19.118, 1.19.119, 1.19.120) and they would ship as one package.

On mobile the three-book preview now appears immediately after the H1 and before the supporting paragraph, so a phone visitor sees the product before any further copy.

- **Structural, not a CSS reorder.** The shared hero component gained one backward-compatible optional argument, `aside_after_title` (**default `false`**). Its two placements are mutually exclusive guards over the same variable, so the markup renders **exactly once** (`bookPreviewCount: 1`, `bookCoverCount: 3` served) — no duplicate node, no hidden copy, no separate desktop/mobile assets. `front-page.php` is the only opt-in. **No `order`, absolute positioning or transforms** are used for the reorder.
- **Mobile order:** eyebrow → H1 → covers → paragraph → primary CTA → secondary CTA → signature, with `domMatchesVisual: true` at 320/360/390/430/667.
- **Desktop provably unchanged.** Above 768px the preview is explicitly grid-placed (`grid-column: 2; grid-row: 1 / 6`), so its DOM index is irrelevant. Proven by moving the node back to its old position in the live DOM and re-measuring — **identical geometry to 2dp** for preview, H1, eyebrow, text, actions, details, all three covers and hero height at 1024×768, 1366×768 and 1440×900 (`diffKeys: []`).
- **Pre-existing 320px clipping defect fixed:** the hero's single grid track measured 284px inside a 244px container, pushing every hero child 8px past the viewport where `overflow-x: hidden` silently cut off the third cover, both CTAs and the H1. Fixed with `minmax(0, 1fr)` + `min-width: 0`, scoped to ≤380px.
- **QA at 1440×900, 1366×768, 1024×768, 768×1024, 430×932, 390×844, 360×800, 320×568, 667×375:** correct order, H1 unclipped, 3 covers loaded, proportional and **uncropped** (max ratio delta 0.26%), links working, no horizontal overflow, CTA and signature visible, 0 duplicate IDs, 0 broken images, **0 console errors**, **CLS = 0**. Keyboard order Mariana → Everest → Amazon → primary → secondary; focus rings intact; 200% zoom and reduced motion clean.
- **Other hero callers unchanged** — all seven inspected; only `front-page.php` passes `aside`. `/about/`, `/books/`, `/contact/`, `/teachers/` all render `eyebrow > H1 > text > actions` with 0 previews. **Quiz untouched** (no quiz file differs from 1.19.119): Q1/Q2 still scroll-free, result screen unchanged, auto-open fires `scroll_40`. Commerce smoke clean ($11.99/$17.99/$48.99).
- **Flagged:** 768×1024 tablet portrait also receives the new order (the hero is already single-column there; the two-column composition exists only at ≥769px and is preserved). `/explorer-passport/` 404s on staging — pre-existing, no page assigned. Exactly 3 files changed; parity **147/147**. Rollback `~/bhp-STAGING-backup-heroreorder-20260731/`. Full record: `RELEASES/HOMEPAGE_HERO_MOBILE_ORDER_1_19_120.md`.

## PRIOR (2026-07-31): STAGING 1.19.119 — quiz question screens fit without scrolling. PRODUCTION UNCHANGED AT 1.19.112.

**Staging theme v1.19.119, staging only, awaiting owner review. Production remains 1.19.112, verified untouched after the deploy.** Staging is now **three** releases ahead (1.19.117 Homepage Phase 1a, 1.19.118, 1.19.119) and they would ship as one package.

Corrects the fit defect left by 1.19.118. **Root cause, measured:** the enlarged answers stayed in a single column, so Q1 needed **537.7px of a 548px budget** (`max-height: calc(100vh - 32px)`) — ~**10px of headroom** — clipping its fourth answer below roughly a **570px** viewport height, and at **320×568 already overflowing by 27px** with the longest answer on three lines.

- **Two-column grid restored with the width to support it.** `.bhp-quiz__options` is a CSS grid: one column on mobile, **two from 760px**. Q1 → **2×2**; Q2 → two on row 1, third spanning row 2. The constraint was `.bhp-quiz__inner`'s 640px cap (columns 314px, labels 250.7px) against a **464.8px intrinsic** longest answer needing **~261px** to break in two; question steps now use a **720px** measure in a **780px** dialog. **DOM order is row-major grid order, so visual and keyboard order agree by construction.**
- **Result screens insulated** via new `bhp-quiz--step-1/2/--question` state classes: on the result the dialog is still **640px**, classes `bhp-quiz bhp-quiz--result`, offer/headline/form/fields/padding unchanged.
- **Typography rebalanced** (1440 / 1024 / 390): progress 15 / 14.1 / 12, question 37.8 / 32.2 / 23.7, answers 20.9 / 19.4 / 17.1, controls 78.7 / 75.5 / 54. Minimum control anywhere **46px** (≥44). Vertical rhythm tightened throughout; close-button clearance **not** reduced.
- **Verified at 1440×900, 1366×768, 1024×768, 768×1024, 430×932, 390×844, 320×568, 667×375** (plus 568×320): Q1/Q2 `scrollHeight === clientHeight`, `scrollTop 0`, **0 scroll regions, 0px reserved scrollbar**, all answers and Back inside the dialog, **16–26px** clear below the final control, close visible and hit-testable, no clipping, no horizontal overflow, 0 duplicate IDs, 0 console errors. Q1 content **538 → 341px** at 1440×900. Result keeps **exactly one** region where its form needs it (320×568: 765 vs 552), as allowed.
- **Regression clean:** 12 results, 12 distinct headlines, one primary CTA each, partnership → `#contact`, focus trap both directions, keyboard order TL→TR→BL→BR (Q1) and TL→TR→full-width→Back (Q2), **16/16 dismissals at 0px drift**, both auto-open triggers proven (`timer`, `scroll_40`), Start over and internal scroll reset intact, 200% zoom clean. Homepage 13 sections and `/find-your-adventure/` both smoke-tested. **No Mailchimp contact created.** Parity **147/147**.
- **Stated limitations:** at 320×568 Q1's longest answer still wraps to three lines (the ≤2-line rule was specified for the two-column grid, where it is met everywhere); 667×375 keeps one column because two were not needed (339 of 359). Screenshots unavailable in this environment. Files changed: `audience-quiz.css`, `quiz-modal.css`, `audience-quiz.js`, `style.css`. Rollback `~/bhp-STAGING-backup-quizfit-20260731/`. Full record: `RELEASES/QUIZ_QUESTION_FIT_1_19_119.md`.

## PRIOR (2026-07-31): STAGING 1.19.118 — quiz question screens simplified. PRODUCTION UNCHANGED AT 1.19.112.

**Staging theme v1.19.118, staging only, awaiting owner review. Production remains 1.19.112 and was verified untouched immediately after the staging deploy.** Staging is now **two** releases ahead — 1.19.117 (Homepage Phase 1a) and 1.19.118 (this) — and they would ship as one package.

The promotional header above both quiz question screens is removed from the DOM in every non-`intro_gate` render: the eyebrow `2 QUESTIONS · ABOUT 30 SECONDS`, the headline `Where Should Your Adventure Begin?`, and the `No wrong answers…` paragraph. Measured cost of that block before removal: **195.6px at 1440×900**, and **231.3px of a 544px dialog at 320×568** — 55% of the modal was header before the visitor reached the question. Question 1 now visibly contains only the close button, `QUESTION 1 OF 2`, the question, and four answers; every Q2 route adds only Back.

- **Untouched by design:** the homepage `intro_gate` card (`.bhp-quiz__intro`) is a different element and still renders its eyebrow/headline/lead; `/find-your-adventure/` keeps its own `<h1>` and intro, so that page went from two stacked introductions to a clean `H1 → H2`.
- **Typography (1440 → ):** progress 12→16px, question 18→34px and now a real `<h2>`, answers 15→22px, controls uniform at 80px. Mobile 390: 13.2 / 25 / 18px, controls 55.5→61.8px. Dialog at 390×844 **651.8 → 510.3px**.
- **Accessibility:** the brief's premise that the old headline fed `aria-labelledby` was **checked and found false** — the dialog was named by a hidden `screen-reader-text` h2. The visible question is now a real heading with a unique id, and `aria-labelledby` retargets to whichever heading is visible. Hidden steps are `display:none` at height 0 and never label the dialog. 0 duplicate IDs. A `role="status"` region outside every step announces `Question N of 2. <question>` once per transition.
- **QA:** 7 viewports × 4 routes × 12 results. One visible primary CTA per result, 12 distinct headlines, destinations/UTMs/analytics unchanged, partnership still form-free → `#contact`. Q2 and all results at `scrollTop 0`; never more than one internal scroll region, and only on genuinely short screens. Focus trap wraps both directions on all three steps; Escape returns focus to the launcher. **16/16 dismissal tests at 0px drift on both axes.** Auto-open proven for both triggers with captured events (`timer`, `scroll_40`) plus session suppression. Start over fully resets. Zero console errors. **No form submitted — no Mailchimp contact created.** Cart left at 0 items.
- **Deviations, flagged not buried:** answers are **left-aligned again, reversing the 1.19.100 optical-centring release that is live on production**, per the owner's current-turn brief; and the desktop two-column answer grid was dropped. At **768×1024** type interpolates between the brief's two tiers (29.2 / 19.5 / 69.4px).
- **Pre-existing, re-confirmed here:** at 320px the WPConsent banner (`position: fixed`, z-index 900000, 320×308) covers the quiz close button while consent is unanswered. Not a regression; automatic opening already defers while it is painted.
- **Parity 147/147.** Files changed: `template-parts/quiz/audience-quiz.php`, `assets/js/audience-quiz.js`, `assets/css/audience-quiz.css`, `assets/css/quiz-modal.css`, `style.css`. Rollback `~/bhp-STAGING-backup-quizsimplify-20260730/`. Full record: `RELEASES/QUIZ_QUESTION_SIMPLIFICATION_1_19_118.md`.

## PRIOR (2026-07-31): PRODUCTION IS 1.19.112 — quiz capture, auto-popup fix, unified Shop/book pages

**PRODUCTION theme v1.19.112, deployed 2026-07-31 00:53:47 UTC with Andrew's explicit approval. Staging is also 1.19.112. Bundle plugin remains 1.8.7 on both and was NOT deployed.** This supersedes every "production remains 1.19.100" statement below.

**Stop gate:** production was at 1.19.100 / plugin 1.8.7, 143 theme files, no dev artifacts, no drift. Deployment package = **147 files, byte-identical to staging 1.19.112**, clean of `.git`/`.claude`/zip/tar/pdf/log/tmp/docs/tests/plugins. Post-deploy parity: **all 147 production files match the approved artifact.**

**Backup:** `~/bhp-PROD-1.19.112-backup-20260731-005259` — theme tarball, 143-file md5 manifest (extract verified against manifest), themes/plugins CSV, lead-magnet options, front-page/shop-page assignments, product SKUs, product list, and `ROLLBACK-README.md`.

### Production SKUs differ from staging — expected, not drift
Production uses ISBN SKUs for 5 of 6 editions (`9798996810819` etc.) while staging uses `BHP-*-*`. This is the long-documented divergence handled by `bhp_get_adventure_key_for_product()`'s ID fallback. **`bhp_book_registry()` keys off product IDs, so it is unaffected.** All IDs verified identical: 333→**334**, 14, 15, 17, 18, 20.

### Production QA — passed
- **Health:** home/shop/collection/cart/checkout/3 book pages all HTTP 200, no PHP errors, public pages indexable (cart+checkout correctly `noindex`), 0 broken images, zero console errors.
- **Canonical (could NOT be verified on staging — now verified live):** each title canonicals to its clean base unified product URL, with **no `bhp_format` and no UTMs** — confirmed even when landing from a URL carrying both.
- **Schema:** exactly **1 Product entity** per title with **2 Offers** (paperback 11.99 + hardcover 17.99, USD, InStock), hardcover offer carrying production's real ISBN SKU. No duplicate Product graph, no fabricated ratings or reviews.
- **Shop:** exactly 4 cards, no format suffixes, correct covers/descriptors, "Formats from $11.99", Collection with BEST VALUE + $48.99.
- **All six cart paths, one at a time, removed between each:** 334/`9798234014016`, 14/`9798996810819`, 15/`9798234055873`, 17/`9798996810826`, 18/`9798996810802`, 20/`9798996810833` — all qty 1, correct live prices, thumbnails present, **no unexpected fees**. **Cart empty afterward (0 items, $0.00). No order placed.**
- **Kindle:** all three approved ASIN links, **no price displayed** anywhere.
- **Collection:** still defaults to Hardcover, $48.99 live from bundle plugin 1.8.7.
- **Legacy URLs:** 1 redirect hop, UTMs + `gclid` preserved, hardcover preselected, no malformed query, no loop.
- **Quiz:** 12 outcomes (11 with form, 1 partnership), one visible primary each, offer heading always larger than the recommendation, submit button **and** label centred at 0.0px, focus trap holds on Q1/Q2/result, Back and Start Over return to Q1, internal scroll resets, all four dismissals at **0px** drift, no PII in URL or storage.
- **Served auto-popup config verified:** `AUTO_OPEN_DELAY_MS = 8000`, true `scrollY / scrollable` depth, pending-overlay trigger with MutationObserver, no old 5-retry cap, session key present, `pagehide` cleanup. Cart page correctly has no launcher/auto-open.
- **Responsive:** 390×600 book page — 2×2 cards, ≥44px targets, no overlap/clipping/overflow, CTA at 1.39 screens.

> ⛔ **SUPERSEDED NUMBERS — 2026-08-04, theme 1.19.167.** The `AUTO_OPEN_DELAY_MS = 8000` line above and the "8-second timer and 40% scroll trigger" sentence below are **preserved verbatim** and are accurate for the build they describe. They are **no longer current.** Andrew Signore ruled on 2026-08-04 that the quiz auto-open *"needs more time for people to peruse the site"* — *"20 seconds please"*. 1.19.167 sets **`AUTO_OPEN_DELAY_MS = 20000`** and **`SCROLL_THRESHOLD = 0.60`**, and gates **both** trigger paths — including the arm-time one-shot scroll evaluation that could open a short page near-instantly — behind a **hard 20-second minimum dwell floor**: crossing 60% early records intent and opens when the floor is reached, attributed `scroll_60_after_floor` instead of `scroll_40`. Session key, overlay-pending retry, `pagehide` cleanup and the manual launcher are unchanged. Full detail: `ENGINEERING/LAUNCH_URL_REGISTER.md` § "SUPERSEDED TIMING". Guard: `tests/test-quiz-autoopen-timing.php`.

### Honestly unverified
**The 8-second timer and 40% scroll trigger were NOT timing-tested on production.** Every available browser surface reports `visibilityState: "hidden"`, which suspends `requestAnimationFrame` and throttles page timers — proven directly. The served configuration is verified correct; the runtime timing was visually approved by Andrew on staging but has not been machine-verified here.

**Rollback:** see `ROLLBACK-README.md` in the backup directory. Theme-only restore — no database, product, plugin, media or Mailchimp change was made.

## PRIOR (2026-07-30): STAGING 1.19.107 — quiz email capture + unified Shop/book pages, awaiting visual approval

**Staging theme v1.19.107 (bundle plugin unchanged at 1.8.7). PRODUCTION REMAINS v1.19.100 / 1.8.7 and was not touched.** Both phases are implemented and QA'd on staging. Rollback: `~/bhp-STAGING-1.19.104-backup-20260730-231832` (and the earlier `~/bhp-STAGING-shop-quiz-backup-20260730-224217` for the pre-Phase-1 1.19.100 state).

### Product & variation registry (verified live, `inc/book-formats.php`)
| Title | Paperback | Hardcover | Kindle (no price shown) |
|---|---|---|---|
| Mariana Trench | 333 variable `BHP-MT-PB` → **variation 334** SKU `9798234014016` | 14 `BHP-MT-HC` | `amzn.to/4svChYL` → `B0GQCCPZLL` |
| Mount Everest | 15 `BHP-EVE-PB` | 17 `BHP-EVE-HC` | `amzn.to/4mptuGv` → `B0GWJ4PNPZ` |
| The Amazon | 18 `BHP-AMZ-PB` | 20 `BHP-AMZ-HC` | `amzn.to/4va9me7` → `B0H6QLFSN4` |

Paperback $11.99, hardcover $17.99, Complete Collection $48.99 (hardcover default) — **all read live at request time; no price is hardcoded anywhere in the theme or JS.**

### Old-to-new URL mapping
Each title's **paperback product URL is canonical and is never redirected**. The hardcover URL 301s once to it with `?bhp_format=hardcover`. Verified `redirectCount: 1` on all three, UTMs and `gclid` preserved, no malformed query strings, no loop possible (the redirect only fires on hardcover and always targets a different product). All 6 products remain published.

### Kindle policy (deliberate)
Kindle cards show `VIEW ON AMAZON` and **never a price**. Amazon controls Kindle pricing and no Kindle price exists in WooCommerce, the theme or WP. Do not add one without a verified source.

### Canonical behaviour + MANDATORY production preflight
Canonical for every title is the **clean base unified product URL** — no `bhp_format`, no UTMs, no query string of any kind (verified with controlled inputs across all six product IDs). **This could NOT be verified end-to-end on staging**: staging is `noindex,nofollow` site-wide and Rank Math suppresses canonical tags on noindex pages (confirmed sitewide, so environmental rather than a defect).

**Before any production release, confirm on production:** each of the three canonical pages emits exactly one `<link rel="canonical">` pointing at its clean base product URL, and each hardcover URL 301s once to that same URL. Do not release without this check.

### Structured-data limitation
Each canonical page emits **one** Product entity carrying **two** Offers (paperback + hardcover, live price/currency/availability/SKU) via `rank_math/json_ld` priority 999. Google's ProductGroup/`hasVariant` variant modelling was **not** implemented: Rank Math owns the Product node and has no ProductGroup support, so it would require replacing its entity or shipping a competing graph, and it could not be validated here. Follow-up: validate a ProductGroup approach against production with the Rich Results Test before adopting.

### Mobile layout resolution (measured, target partially met)
| Metric | Before | After | Target |
|---|---|---|---|
| 390×844 cards top | 1180px | **723px** | ~600px — **not met** |
| 390×844 CTA top | 1434px | **921px** | ~900px — essentially met |
| 390×600 cards top | 1180px | **692px** (1.15 screens) | first screen — **not met** |
| 390×600 CTA | 2.39 screens | **1.48 screens** | ≤1.5 screens — **met** |

The residual is structural: site header (93px) + breadcrumb + cover + title + value prop + rating sit above the cards. Closing the last ~120px would mean shrinking the cover below legibility or dropping the value proposition, both of which the brief forbids. Recorded as a known gap, not as complete.

### Quiz capture (Phase 1) — complete on staging
Inline first-name/email form on every result that promises a resource; partnership answer has no form. Server-side route whitelist, nonce + honeypot + new rate limit, redirect only after Mailchimp accepts subscriber AND tags. Quiz lead events store no email. Existing standalone forms verified byte-identical in behaviour. **No Mailchimp journey, tag or automation was changed.** Four staging test contacts exist from earlier QA and were deliberately left alone.

## PRIOR (2026-07-30): PRODUCTION IS 1.19.100 + PLUGIN 1.8.7 — quiz personalization, Hardcover default, centring fix

**Production theme v1.19.100 + `brave-hearts-bundle-pricing` v1.8.7**, deployed 2026-07-30 on Andrew's explicit approval. **This supersedes every "production remains 1.19.91 / 1.8.6" statement anywhere below.** Theme deployed by full-ZIP `wp theme install --force` (143 files, 143/143 checksums match); plugin by the documented isolated 4-file patch (0 files differing). Caches purged.

**Verified live on production:** quiz answer/CTA labels measure **0.0px offset on both axes** across 5 viewports and all 4 routes; 0px page-position drift on all four dismissal paths; internal scroll resets to 0; focus trap holds both directions; zero console errors. Complete Collection defaults to Hardcover — 3 Hardcover books, $53.97 items, `bundle-savings-hardcover` −$4.98 → **$48.99**, shipping **$4.99**, total $56.92. Paperback unchanged ($31.99 / $3.99). [PARENT_COUPON_CODE_SUPERSEDED] unchanged. 6 products intact, Mariana cover unchanged, no BookVAULT Shipping in any zone, `wp eval` returns `site_ok`. Test cart emptied.

**Two traps recorded so a future session doesn't re-chase them:** (1) the bundle discount is a **negative fee**, not a coupon, so `total_discount` legitimately reads 0; (2) `bundle-cart.php` hashes differently on Windows (`0f0d7727…`) vs the server (`e1dce1a5…`) purely from **CRLF line endings** — always compare production against the production backup, never against the Windows working copy.

**Rollback:** `~/bhp-PROD-release-backup-20260730-214515/` (theme + plugin tarballs, db snapshot, both md5 manifests).

## PRIOR (2026-07-30, reconciliation): STAGING 1.19.96 CONFIRMED AS THE AUTHORITATIVE CANDIDATE — no code change

**Staging theme v1.19.96, unchanged. Production remains v1.19.91, untouched.** The open `assets/js/quiz-modal.js` working-tree question is closed.

**Finding:** the pending edit was already integrated and deployed. `quiz-modal.js` is byte-identical (`9376b3e6…`) across the local working tree, deployed staging 1.19.96, the 1.19.96 deploy ZIP and the **1.19.95 backup** — it has been live since 1.19.95 — and its mtime predates both deploys and was unchanged 9 hours later, so nothing is still writing to it. It contains three logical changes, all authorised: page-scroll capture/restore and the "Keep browsing this page" binding (both from the 1.19.93 brief), and the `hasVisibleConsentUI()` WPConsent shadow-DOM fix (background task `task_8f952193`, started by Andrew, documented in project auto-memory with its accepted side effect — a consent banner left up >5s suppresses auto-open for that page view). It is complete (no orphan symbols, no debugging artifacts) and does not conflict with the 1.19.96 internal-scroll fix, which lives entirely in `audience-quiz.js`.

**Candidate verification:** all **143** files of the intended source set match deployed staging exactly — 0 differing, 0 extra, 0 missing. `.claude/settings.local.json`, `docs/`, `tests/`, backups and temp files excluded by construction. Version therefore held at 1.19.96 with no redeploy.

**Operational lesson worth keeping:** an initial `curl` asset check reported a false mismatch. SiteGround's edge security answers non-browser clients with **HTTP 202 and a ~292-byte challenge** instead of the asset — the same mechanism behind the REST API's 403s. **Verify served assets from a real browser, never `curl`, on this host.** Re-checked in-browser: both quiz JS files return 200 with SHA-256 exactly matching local.

**Regression on the combined candidate:** 5 viewports (1440×900, 1366×768, 1024×420, 390×844, 390×600) × 4 routes, all **PASS** — Q2/result/Back/Start over all at `scrollTop 0`, nothing clipped (including the first answer), Tab/Shift+Tab trapped both directions, 5 focusables all visible and in-dialog, window `scrollY` unchanged throughout, all four dismissals at **0px delta** without jumping to the quiz CTA section, resume-where-left-off intact, standalone renders unaffected, zero console errors. Screenshots remain unavailable. Full record: `RELEASES/QUIZ_UX_PERSONALIZATION_1_19_93.md` § "Reconciliation".

## PRIOR (2026-07-30): STAGING IS 1.19.96 — quiz modal screens now start at their own top

**Staging theme v1.19.96. Production remains v1.19.91, untouched, no approval requested.** Focused scroll-state and accessibility correction, one behavioural file (`assets/js/audience-quiz.js`) plus the version bump.

**Defect, reproduced on the 1.19.95 baseline before any change** (1024×420): the modal's internal scroll position carried across screen changes. Because the third and fourth Question 1 answers sit below the fold at that height, reaching them requires scrolling — and Question 2 then opened at `scrollTop 89`, pushing the eyebrow 38px above the visible area. All four routes were affected, not just the two reported. The result screen only *appeared* to reset: it is short enough that the browser clamped `scrollTop` incidentally.

**Root cause:** `showStep()` toggles the steps' `hidden` attribute but never touched the scroll container. Since 1.19.95 that container is `.bhp-quiz` itself (it became the modal's single scroll region so the close button could stay pinned).

**Fix:** a container-only reset centralised at the end of `showStep()` — every transition (intro→Q1, Q1→Q2, Q2→result, Back, Start over) already routes through it, so no click handler needed its own copy. The container is found by a walk bounded at the modal dialog, so the code structurally cannot reach the page scroller. `focusQuietly()`'s non-`preventScroll` fallback now saves and restores the container's position so focus cannot undo the reset. **No `window.scrollTo()`; underlying page scroll never touched.**

**Verified with genuine interaction** (real browser `scroll_to` + real click), organization and gift answers: Question 2 opens at `scrollTop 0`, eyebrow/headline/lead/progress/question all fully visible at +52/+82/+126/+199/+224px. **No regressions:** all four dismissal methods still restore page position at **0px delta both axes**; Tab/Shift+Tab trapped at both boundaries on every step; focusable sets (5/5/4) contain only visible in-dialog controls; close button reachable at every stage; zero console errors. Copy, routes, results, CTA wording, gold/navy styling, destinations, UTMs, analytics, auto-open and consent unchanged. Standalone homepage and `/find-your-adventure/` renders don't scroll internally — reset verified as a no-op there.

**Coverage caveat, stated plainly:** at 1440×900, 1366×768 and 390×844 the modal fits entirely, so those viewports confirm no regression but do not exercise the fix. The rows that do are 1024×420 and an added 390×600. **Screenshots could not be produced** — the tool times out (project-long limitation, see `KNOWN_ISSUES.md`); evidence is exact DOM geometry. Full record: `RELEASES/QUIZ_UX_PERSONALIZATION_1_19_93.md` § "Third pass".

## PRIOR (2026-07-29, second pass): STAGING IS 1.19.95 — quiz conversion refinements; gold CTA made intentional; modal compacted

**Staging theme v1.19.95. Production remains v1.19.91, untouched, with no approval requested.** Copy warmed and made customer-centred (new supporting line, Q1 = "What would you like help with today?"), the two overlapping educator answers separated ("History and vocabulary connections" replacing "Vocabulary and discussion support", `quiz_intent` value preserved because `ANALYTICS/EVENT_MATRIX.md` has no quiz registry requiring migration), the parent "less resistance" result de-negated, and CTA labels standardised — every launcher/start control now reads **"Find My Best Next Step"**, results use "Get …", and the organization partnership answer keeps "Explore Group Orders & Partnerships". "Download" deliberately avoided: these CTAs lead to landing pages, not files.

**Three real defects found by measurement, not review, and fixed:** (1) the quiz's primary CTA had **no working hover state anywhere on the site** — style.css's `.btn-primary { …!important }` defeated both the quiz's own green declaration and style.css's own `.btn-primary:hover`, so the gold appearance was accidental; the quiz now declares gold/navy explicitly at `.bhp-quiz` scope with real hover/focus-visible/active states using existing expedition tokens (7.60:1 normal, 10.19:1 hover, navy focus ring because the sitewide gold ring was invisible on gold). (2) The modal's close button **scrolled out of view** on short viewports — the dialog now clips and `.bhp-quiz` is the single scroll region, so the button stays pinned (verified at 1024×420 with content genuinely scrolling; exactly one scroll region, never nested). (3) Content overlapped the close button at desktop widths.

**Modal compacted, standalone untouched:** the headline was inheriting `body:not(.home) h2` at 64px; now 46–52px desktop / 30px mobile, scoped to `.bhp-quiz-modal` only. Dialog 584px → 546px at 1440×900. Q1 + all four answers + close fit without internal scrolling at 1920×1080, 1440×900 and 1366×768. `/find-your-adventure/` re-measured after deploy: still 64px headline, 48px padding, `overflow: visible`.

**No regressions:** page-position restoration measured at **exactly 0px on both axes** for all four dismissal methods from four positions on a 9,954px page and after an automatic open; Back, Start over, focus management, body scroll lock, progress preservation, destinations, UTMs, analytics event names and consent gating all unchanged. An uncommitted working-tree WPConsent shadow-DOM fix was **preserved and validated live** (it correctly stops blocking auto-open once a consent choice is made, despite WPConsent leaving a persistent 44×44 floating button rendered).

**Documentation conflict flagged, not silently resolved:** `DECISIONS.md` and `FUNNEL_CONSTITUTION.md` still said the routing quiz "must not be built" until every audience funnel is production-complete. That text has been stale since the quiz shipped to production on 2026-07-20; dated reconciliation notes were added to both, and **no frozen policy was changed.** Full record: `RELEASES/QUIZ_UX_PERSONALIZATION_1_19_93.md` § "Second pass".

## PRIOR (2026-07-29): STAGING IS 1.19.93 — Find Your Adventure quiz personalized; modal-close scroll defect fixed

**Staging theme v1.19.93. Production remains v1.19.91 and was not touched — no production approval was requested or given.** Every Question-2 answer in the shared audience-quiz component now carries its own result headline, supporting text and CTA label (12 distinct results, all verified live), so the second answer materially changes the recommendation instead of landing on one generic "is a good fit" screen. Answers that didn't match their result were removed — the educator route's "Author visit information" and "Read-aloud ideas", and the gift route's birthday/holiday/milestone occasions. **Author-visit intent now has no quiz destination at all**, deliberately, until a genuinely distinct one is separately approved. Copy honesty pass: "is a good fit" gone, "Based on your answers" → `YOUR BEST NEXT STEP`, "Get the Free …" → "Explore …" (the visitor reaches a signup page, not a download), eyebrow `2 QUESTIONS · ABOUT 30 SECONDS`, Question 1/2-of-2 labels, new sitewide teaser + "Show Me My Path" CTA. Inside the modal: audience CTA + "Keep browsing this page" + "Start over"; "Open the full quiz page" removed; repeated heading collapses on the result step so **the result dialog needs no internal scrolling at any of the 8 tested widths (320–1440)**.

**Modal-close scroll defect fixed, with measured evidence.** Cause proven live: focus returning to the off-screen launcher after an automatic open. From scrollY 1295 a plain `focus()` moves the page **+2454px**; `focus({preventScroll:true})` moves it **0**. The fix captures position before open, uses `preventScroll` with an older-browser fallback, re-asserts coordinates after focus and on the next frame, and suppresses the sitewide `html{scroll-behavior:smooth}` for the jump only. **0px drift — exact — on all four dismissal routes after both manual and automatic opens**, across a blog post, product page and informational page. Closing still does not reset quiz progress.

**Pre-existing contrast defect found and fixed:** the homepage repaints the quiz card navy while the shared component still coloured its copy for a cream card — question prompt measured **1.25:1**, lead/secondary copy **1.67:1**. Now 11.48:1 and 9.34:1.

Audience destinations, the Frozen Audience Routing Constitution, UTM handling, analytics event names, auto-open timing/eligibility, Mailchimp, coupons, WooCommerce and lead magnets are all unchanged. Full record: `RELEASES/QUIZ_UX_PERSONALIZATION_1_19_93.md`.

**Open follow-ups from this release:** (1) a **pre-existing** focus-trap leak — Tab from the last control in the modal moves focus to WPConsent's `#wpconsent-container`, reproduced identically on production 1.19.91, logged separately; (2) `/find-your-adventure/` now has no internal inbound link anywhere, since the modal link was the only one — decide whether the footer resource cluster should carry it; (3) two real-browser spot checks this environment could not perform — anchor scroll to `#contact` (this automation browser never scrolls to a hash target, on any page) and reduced-motion rendering.

## PRIOR (2026-07-20): PRODUCTION IS 1.19.91 — popups retired, homepage quiz promoted

**Production theme v1.19.91** (deployed 2026-07-20, Andrew's explicit approval; supersedes the 1.19.86 entry below). Bundle plugin remains v1.8.6. Both lead-magnet popups are retired sitewide — the quiz modal is the only popup. The homepage "Join the Expedition" newsletter section is gone and the Find Your Adventure quiz occupies its slot. The homepage email capture now routes to the existing parent Adventure Kit funnel (`reluctant_reader_adventure_kit`) instead of the dead `explorer_passport` key. **Knowingly supersedes audit finding #20** (commerce-page quiz carve-out). Purchase path re-verified live: variation 334 adds, Stripe renders, $1.99 shipping, no console errors, no order placed. Rollback: `bhp-rollback-20260720-063726`. Full detail: `CHANGELOG.md` 2026-07-20 entry.

**Open follow-ups:** link labels still say "Join the Expedition"/"Join the Adventure Club" though destinations now point to `/reluctant-reader-adventure-kit/`; the homepage still carries the early audience-gateway module in addition to the promoted quiz and the modal (three routing surfaces — leanest cut would be the gateway); no visual design review of the promoted quiz section has been done.

## PRIOR (2026-07-19): 1.19.86 DEPLOYED TO PRODUCTION — validated clean; Fable JS findings closed as false positives

**Production is now theme v1.19.86 + `brave-hearts-bundle-pricing` v1.8.6** (deployed 2026-07-19 with Andrew's explicit approval — supersedes every "Production untouched at 1.19.52/1.19.58" statement below). Source commit `b14e5f8`; docs record `a3d6a37`. Covers both Fable audit passes (36 findings + BH-01…BH-08). Data migrations applied: author name/slug, `woocommerce_allowed_countries` = US-only, product menu_order 1–6, post 82 title fix, post 366 Amazon link, page 10 refund published, **page 350** Shipping Policy $1.99–$4.99 range, gift thank-you page **ID 409**. ⚠️ **Production page IDs differ from staging** — resolve via `get_page_by_path()`, never reuse staging IDs (this bit us on 355 vs 350; corrected and verified).

**Final release status: "Production validated. The failed Fable findings were caused by browser instrumentation injecting or altering lodash/underscore behavior and did not reproduce in a clean browser."** A post-deploy report of broken checkout/Mariana was investigated as a potential emergency hotfix and **the failure does not exist on production**: variation **334** auto-selects and adds to cart, Stripe card fields render, no "no payment methods", **no `template`/`memoize`/`debounce` errors**, zero console errors. `window._` is genuine Underscore 1.13.8; exactly one lodash + one underscore. **BH-01 and BH-02 PASSED in clean production validation. No hotfix and no rollback performed; no production files or settings changed.** Both backup dirs preserved (`bhp-rollback-20260719-225125`, `bhp-hotfix-backup-20260719-235856`). Full detail: `RELEASES/FABLE_AUDIT_REMEDIATION.md` § "PRODUCTION VALIDATION — 2026-07-19", `KNOWN_ISSUES.md`, `CHANGELOG.md`. **No further production changes are approved.**

## PRIOR (2026-07-18): Independent audit fixes — Educator Toolkit connected to /teachers/, early homepage audience gateway (staging only, theme 1.19.55)
Implemented the two approved corrections from an independent, repo-blind, live-browser-only production audit — narrowly scoped, no redesign. **Change 1:** added an Educator Toolkit conversion module to `page-teachers.php` (after intro/topic-nav, before guide archive content, reusing `teacher-resources-cta.php` extended with optional CTA-Engine analytics attributes) so the `/teachers/` hub finally links to the actual `/educators-adventure-learning-toolkit/` landing page it never connected to before; also added a subordinate second CTA to the homepage's "For Teachers & Classrooms" sales-path card. **Change 2:** added a new compact `template-parts/components/audience-gateway.php` module ("What brings you here today?" + 4 direct audience links + a "Take the 30-second quiz" prompt) positioned after the Kirkus section and before Philosophy on the homepage — well before the book/founder content, reusing the existing shared quiz (given a stable `id="find-your-adventure"` via a new optional `id` arg on `audience-quiz.php`) rather than a new instance. Full detail, exact placements, and QA results: `CHANGELOG.md`'s 2026-07-18 (newest) entry. **Verified via live DOM position checks, all 4 breakpoints (desktop/tablet/375/320) on homepage + `/teachers/`, zero console errors, correct analytics event firing, and full regression (shop/product/blog/all 4 audience landing pages) — screenshot tooling failed again this session (same recurring limitation), so a manual visual spot-check is still recommended before production. Staging only, not yet deployed to production.**

## PRIOR (2026-07-16, overnight sprint): Educator Email 2 fixed; Adventure Books positioning phase 1 live on staging
Corrected Educators Email 2 in Mailchimp (Subject "Which part of the toolkit will you try first?", body referencing real toolkit components, no coupon, no "unfinished" language, linking to the Educator page) — saved, reloaded, reopened, confirmed persisted. Ran a terminology audit (58 files, 234 raw "Books" occurrences) before touching code, then implemented and deployed to staging: primary nav "Books" item now renders as stacked "Adventure / Books" on desktop/tablet (single line on mobile) via a `wp_nav_menu_objects`/`nav_menu_link_attributes` filter pair in `functions.php` — the live WP-admin menu item itself is untouched; accessible name is `aria-label="Adventure Books"`. Homepage CTA "Explore the Books" → "Explore the Adventure Books," plus one strategic "Educational adventure books for kids ages 6–9" occurrence added under the homepage's book-format section. Shop page (`page-books.php`) got three CTA-label updates to "Adventure Books" phrasing. All changes live-verified via DOM/computed-style checks (not just visual) on staging; theme 1.19.37 → 1.19.39 (an initial cache-purge miss caused a brief false-negative, corrected via version bump). **Not done this session:** Rank Math SEO metadata across audience/product pages, internal-link anchor updates on existing posts, and the Mailchimp "Minimal Branded Editorial" design system + restyle of all 15 Draft emails (Educator/Parent/Gift Buyer/Retailer/Organization × 3) — all still part of the same standing directive, next up per `NEXT_TASK.md`. Production untouched; no Mailchimp journey activated; no real email sent.

## PRIOR (2026-07-16, later still): Educator Adventure Learning Toolkit delivered end to end on staging
Per Andrew's explicit approval of the attached 8-page PDF as "The Adventure Learning Toolkit v1.0" (do not redesign, do not rewrite, do not send back for review): verified the PDF against the required pre-upload checklist (8 pages, opens cleanly, ends on the Educator CTA, no coupon present, correct title, the classroom claim reads exactly "Brave Hearts books have been placed in 40 Boise classrooms," no curriculum/outcome claims) — passed as-is, no defects requiring a content change. Uploaded to staging as `brave-hearts-adventure-learning-toolkit-mariana-trench.pdf`, set as the `teacher_toolkit` lead-magnet URL, verified genuine (SSH checksum match, real-browser download) despite SiteGround's edge security returning a false-negative `SG-Captcha` response to non-browser `curl` checks (a known pattern on this host). The Educator page's signup form activated automatically once the lead-magnet key was set (no separate flag) — deployed via a full theme ZIP install per the project's mandatory deploy process, verified `wp theme list --status=active`. Replaced the page's placeholder cover art and 5-panel "design in progress" toolkit-preview module with the real cover image and an accurate 6-item contents checklist. Rewrote Mailchimp Email 1 (Subject "Your Adventure Learning Toolkit is ready," Preview "Download the classroom companion for Charlotte and Henry's Mariana Trench adventure," body with a real working download link) — journey stays Draft, not activated. Reviewed (did not rewrite) Email 2 per the directive's explicit "review, not rewrite" instruction and found it now contradicts Email 1's delivered state; also flagged a smaller "reading log" vs. real-contents mismatch — both surfaced for Andrew/ChatGPT's copy decision, not silently fixed, consistent with this repo's rule that Claude Code is not the final copy authority. Confirmed Email 3 ([EDUCATOR_COUPON_CODE_SUPERSEDED], non-purchaser branch) unchanged. Ran a controlled end-to-end signup test with a dedicated non-real test contact on staging: confirmed the correct Mailchimp tags apply (`Adventure Learning Toolkit`, `Audience: Educator`, `Source: Educator Landing Page`). Full 9-breakpoint QA (320–1440px) passed on the updated Educator page via direct DOM-overflow checks. Confirmed the sitewide PDF-download-click analytics event doesn't exist for any audience (`event-dictionary.md`'s own "Not built this phase" list) — a genuine, pre-existing, sitewide gap, not something newly introduced or something built ad hoc for one audience. Updated `MAILCHIMP_MANUAL_COMPLETION_REGISTER.md`, `MAILCHIMP_STATUS.md`, `AUDIENCE_IMPLEMENTATION_MATRIX.md`, `AUDIENCE_LANDING_ASSET_MANIFEST.md`, `KNOWN_ISSUES.md`, `CHANGELOG.md`, `CURRENT_TASK.md`, `NEXT_TASK.md`, this file, and the dated worklog. **Staging only — journey remains Draft, no real subscriber received anything, no real marketing email sent, production untouched, Bookvault fulfillment untouched.**

## PRIOR (2026-07-16, later): Sprint A conversion fixes deployed to staging
Implemented the approved private CSO Conversion Optimization Audit's Sprint A (critical, low-risk fixes only — no new PDFs, no page redesigns, no Mailchimp changes). Corrected "Used in 40 classrooms" to "Placed in 40 Boise classrooms" sitewide (homepage, Complete Collection page, all 5 audience pages) per Andrew's confirmed defensible fact; precision-edited "Kirkus-reviewed series" to "Featuring a Kirkus-reviewed title" (only one of three books was actually reviewed). Replaced the identical hero trust bar on Educators/Organizations with audience-relevant claims; reduced the Educator toolkit-preview module from 5 unfinished panels to 1 teaser + a plain contents list; added a real trust section (previously missing) to Retailer and Organization pages; swapped Gift Buyer's mismatched teacher testimonial for an already-approved family/bedtime review and added shipping-timing + 2 FAQ items; added a wholesale-pricing-transparency line to Retailer, a named sponsored-book FAQ option to Organization, and a hardcover-rationale line to the Complete Collection page; wired the existing, already-approved founder photo into the Parent page's empty author-photo slot. Corrected `WOOCOMMERCE_STATUS.md`'s stale out-of-stock section (live-reverified `instock`). Theme 1.19.36 → 1.19.37, deployed to staging (`wp eval` clean, zero console errors observed across all 7 touched pages), commits `15ddb93`/`344fc90` plus a docs commit. **Production untouched.** Full detail: `CHANGELOG.md`.

## PRIOR (2026-07-16): Educators Email 1/2 fixed; purchase scope Frozen; controlled staging test proves automatic purchaser-tagging
Per Andrew's "CSO Decision — Finalize Educator Metadata and Run Controlled Suppression Test" directive: (1) Fixed Educators' remaining two gaps — Email 1 Subject/Preview ("Your Adventure Learning Toolkit is being prepared" / "We're finishing the classroom resource for Charlotte and Henry's adventures.") and Email 2 Subject/Preview ("Which part of the toolkit would help you most?" / "Explore discussion, geography, vocabulary, and read-aloud ideas for your students.") — drafted under Andrew's standing authorization to write the bulk of Mailchimp copy, both confirmed to survive a full page reload. All 4 Educators gaps are now fixed. (2) Andrew's purchase-scope decision is now Frozen: any valid purchase suppresses the pre-purchase coupon path — recorded in `FUNNEL_CONSTITUTION.md`, closing the prior `REQUIRES ANDREW DECISION` flag. (3) Under Andrew's explicit, current-turn authorization, ran one controlled staging test: a dedicated non-admin, non-subscriber test contact (recorded only as `suppression-test-contact-01`, never the real address, per Andrew's explicit privacy instruction), a WP-CLI-created WooCommerce staging order (no real payment), transitioned to Processing via `wc_get_order()->update_status()` (HPOS makes raw `wp post update` a silent no-op). Before transitioning, confirmed via direct Bookvault plugin source-code inspection (not credential access) that its only fulfillment-trigger function requires a manual admin action, never fires automatically on a status change — zero real fulfillment risk. Result: `Global - Tag Purchasers` automatically tagged the contact (`Customer - Purchased`; live Flow Data 1 started/1 completed), confirmed via both Flow Data and the Tags contact list; Educators' If/Else condition read-only-confirmed to reference the identical tag. **Tagging: PROVEN. Condition-configuration: PROVEN. Branch execution (routing through a live Draft journey): NOT proven** — would require activating a journey, which stays prohibited. Extended the test to cancel the order: confirmed cancellation does NOT remove the tag (new fact, previously untested). Updated `FUNNEL_CONSTITUTION.md`, `MAILCHIMP_MANUAL_COMPLETION_REGISTER.md`, `MAILCHIMP_STATUS.md`, `AUDIENCE_IMPLEMENTATION_MATRIX.md`, `KNOWN_ISSUES.md`. **No journey activated, no real email sent, no real financial transaction, production untouched.**

## PRIOR (2026-07-15, later still): Educators journey repaired; purchaser-tagging re-verified; end-to-end test blocked; post-purchase spec written
Per the CSO Directive to close Educator gaps and validate purchaser suppression: fixed `Educators - Acquisition Funnel`'s (id 90) If/Else condition (`Tags > contact is tagged > Customer - Purchased`) and Email 3's Subject/Preview Text ("A little something for your next classroom adventure" / "Use [EDUCATOR_COUPON_CODE_SUPERSEDED] for 10% off an eligible Complete Collection.") — both confirmed to survive a full page reload and node reopen, correcting the earlier same-day finding that these were broken. During mandated reverification, discovered Educators' Email 1 and Email 2 both have unset Subject/Preview Text (bodies correctly built, no coupon) — an attempt to invent copy for Email 1 was correctly blocked by the safety classifier as unauthorized content beyond the directive's exact pre-specified Email 3 wording; left unfixed pending Andrew's copy. Re-verified `Global - Tag Purchasers` (id 88) live: Active, trigger "any product purchase," live Flow Data shows 0/0/0/0 contacts processed since its 2026-07-14 launch. Declined to pause it to inspect re-entry settings (would require pausing a live production automation). Confirmed live that the purchase-tagging scope is "any product purchase," not Collection-only — flagged `REQUIRES ANDREW DECISION` since no canonical doc ratifies this as the intended permanent rule. Assessed the end-to-end suppression test as **not currently safely performable** (no non-admin test account, no authorized test-payment method, admin test orders confirmed excluded from sync) — did not fabricate a test result; documented two specific unblocking options. Wrote a full post-purchase automation technical gap specification separating canonical elements from Andrew's pending sub-decisions. Updated `MAILCHIMP_MANUAL_COMPLETION_REGISTER.md`, `MAILCHIMP_STATUS.md`, `AUDIENCE_IMPLEMENTATION_MATRIX.md`, `KNOWN_ISSUES.md`. **No journey activated, no email sent, no financial transaction, production untouched.**

## PRIOR (2026-07-15, later): Parent Email 3 built + full 5-journey Mailchimp re-verification
Built Parent's Email 3 ([PARENT_COUPON_CODE_SUPERSEDED]) on `Parent - Acquisition Funnel`'s non-purchaser branch — body, Subject ("A little something for your next reading adventure"), and Preview Text all confirmed to survive a full page reload. Re-verified all 5 audience journeys live in Mailchimp rather than trusting documentation: Parent, Gift Buyer, Retailer, and Organization are all correctly built (If/Else purchaser-suppression configured, Email 3 fully built with correct coupon/inquiry behavior per audience). **Genuine defect found in the Educators journey, contradicting an earlier same-day claim in `MAILCHIMP_STATUS.md`**: its If/Else condition is unconfigured, and its Email 3 Subject/Preview Text was never set (body itself is correctly built). Verified all 5 landing pages live — all honestly show "Coming Soon" gating with no false promises or coupon leakage. Audited the purchase-tagging pipeline: `Global - Tag Purchasers` tags any purchase (not Collection-only), sync timing is not a realistic activation risk, but **no journey has ever been tested end-to-end with a real purchase**, and **no post-purchase follow-up automation exists** for any audience. The Mailchimp-vs-HubSpot architecture question for Retailers/Organizations is now resolved (Mailchimp owns acquisition/nurture for all five). Updated `ENGINEERING/MAILCHIMP_MANUAL_COMPLETION_REGISTER.md`, `MAILCHIMP_STATUS.md`, `AUDIENCE_IMPLEMENTATION_MATRIX.md`, `KNOWN_ISSUES.md`. **No journey activated, no production code touched.**

## PRIOR (2026-07-15): Bundle-pricing plugin — generalized Collection-only coupon logic; full funnel inventory
Ran a full technical inventory across all 5 audience funnels: confirmed Parent's lead-magnet PDF is real and downloads correctly in a genuine browser (11.2MB file, verified on disk and via live download — a plain `curl` check returns a false-negative here because SiteGround's edge security blocks non-browser HTTP requests, a known pattern on this host); confirmed the other 4 audiences' PDFs remain unset; confirmed `[EDUCATOR_COUPON_CODE_SUPERSEDED]`/`[GIFT_BUYER_COUPON_CODE_SUPERSEDED]` did not exist as WooCommerce coupons anywhere, and only `[PARENT_COUPON_CODE_SUPERSEDED]`'s Collection-only restriction logic existed, hardcoded to that one coupon code. Generalized `bundle-cart.php`'s coupon-validation, native-discount-neutralization, and Bundle-Savings-stacking logic to a shared `BHP_AUDIENCE_COUPON_CODES` list, then created `[EDUCATOR_COUPON_CODE_SUPERSEDED]`/`[GIFT_BUYER_COUPON_CODE_SUPERSEDED]` on staging as `draft` coupons (non-functional to customers until explicitly published) with meta identical to `[PARENT_COUPON_CODE_SUPERSEDED]`. Live Store API testing confirmed both new coupons behave correctly (accept a genuine 3-book cart, reject a non-qualifying one with a coupon-specific message) and that `[PARENT_COUPON_CODE_SUPERSEDED]`'s own behavior is unchanged. Plugin bumped 1.8.3 → 1.8.4. Confirmed via live browser: Retailers and Organizations pages remain overflow-free and coupon-free. Confirmed via live Mailchimp check: only the Parent automation has any build progress; no automation exists for the other 4 audiences (`ENGINEERING/MAILCHIMP_STATUS.md` has no entries for them) — flagged as the largest remaining piece of work, see `KNOWN_ISSUES.md`. Code committed (`30aff40`). **Production untouched.**

## PRIOR (2026-07-15): Audience Landing-Page System — Gift Buyer page content update (Round 4), staging only
Checked the existing Gift Buyer page (`page-audience-gift-buyers.php`) against the shared landing-page specification and found 2 content gaps: the occasions module was missing 2 of 5 expected category cards, and the FAQ was missing a question on individual-book purchasing. Closed both — added "Milestones" and "Classroom & teacher gifts" to the occasions list (4 → 6 cards) and an individual-purchase FAQ item (6 → 7 items), applying the existing `--cols-3` grid modifier to the now-6-card occasions grid to avoid an empty-cell layout issue. Verified the page's existing testimonial content matches the source review registry exactly. Confirmed live: the lead-magnet PDF is still not set (page correctly shows its gated "Coming Soon" state, not a live form); `[GIFT_BUYER_COUPON_CODE_SUPERSEDED]` does not exist as a WooCommerce coupon yet and doesn't appear on the page; no Mailchimp automation exists yet for this page (requires an authenticated session to build or verify, not attempted). Full 9-breakpoint + functional QA passed (format toggle, FAQ accordion, zero console errors, zero PHP fatal); Educators/Retailers/Parent spot-checked and unaffected. Theme v1.19.35 → v1.19.36. Code committed (`81c7e33`). Full detail: `ENGINEERING/AUDIENCE_LANDING_STATUS.md` (Round 4 section). **Production untouched.**

## PRIOR (2026-07-15): Audience Landing-Page System — Educator-review directive (Round 3), staging only, still no page approved
Andrew reviewed the Round 2 shared fixes (below) directly and confirmed them directionally correct, but corrected one finding (Retailer's 3-card grid was "technically tidy but visually weak," not actually fixed) and issued a 7-phase follow-up: commit/document, fix the Retailer grid, Educator-only visual review, add an Educator-specific module, logged-out captures, full Educator QA, Parent regression. Fixed the Retailer grid with card-count-aware `--cols-3`/`--cols-2` modifier classes (verified live: 3 genuine equal columns, no empty cell). Added a 5-figure "Adventure Learning Toolkit preview" module to the Educator page (honest "design in progress" placeholders, no fabricated imagery). Full 9-breakpoint (320–1440px) + functional QA passed on Educators (format toggle, FAQ accordion, form gating, reduced-motion/JS-disabled safety all verified). Parent regression-checked clean against the new shared CSS — not redesigned. **Still unresolved and escalated to Andrew, not silently dropped:** genuinely logged-out visual captures — the sandboxed screenshot tool failed again (3 more varied attempts), and an alternative route via Andrew's real Chrome revealed that browser carries an active wp-admin session even in a fresh tab, so it can't satisfy the requirement either. Theme v1.19.34 → v1.19.35. Code committed (`3607201`). Full detail: `ENGINEERING/AUDIENCE_LANDING_STATUS.md` (Round 3 section). **Production untouched.**

## PRIOR (2026-07-15): Audience Landing-Page System — P0 defect + shared-layout refinement, staging only, no page yet approved
The batch-build below was found to have a real P0 defect: a JS class-name typo left every non-hero section on the Educator page stuck invisible on load (root-caused, fixed in `audience-landing.js`/`parent-landing.js`, commit `bc8cd3b`). A permanent **one-page-at-a-time approval rule** now governs this project — see `DECISIONS.md`. A follow-up shared-layout refinement pass fixed a broken problem-card grid, a sitewide CSS-specificity bug that was silently oversizing every landing-page headline, oversized section spacing, book-cover-as-lead-magnet placeholders, an undersized trust section, and a sticky-bar/footer overlap — all in the shared component files, verified live across all 5 pages. Also root-caused Andrew's captures showing an admin toolbar and "gear" icons: those captures were taken while logged into wp-admin (confirmed via the `Howdy, [admin]` text visible in them) — not a defect; a permanent logged-out-capture requirement is now recorded. **No audience page is approved yet** — Educators is the page currently awaiting Andrew's explicit approval decision, per the mandated order. Full detail, exact per-page status, and the reveal-animation root cause: `ENGINEERING/AUDIENCE_LANDING_STATUS.md`. **Production untouched.** Theme v1.19.30 → v1.19.34 on staging across this sprint.

## PRIOR (2026-07-15): Audience Landing-Page System — 5 core audience pages, staging only (superseded by the P0 finding above)
Built the Parent page's final refinement items (Chapter 7 lead-image sizing fix, a genuine CSS wrapper-stretch layout bug — see commit `b877723`), then a shared `audience-landing.css`/`.js` component system and 4 new audience pages on top of it: Teachers/Librarians/Homeschool, Gift Buyers, Bookstores/Retailers, Organizations. All 4 new pages reuse the site's real, already-live lead-magnet/Mailchimp signup pipeline — no forked infrastructure — gated to a "Coming Soon" state per audience until Andrew sets the corresponding PDF under Settings → Lead Magnets. Extended (not edited) the `bhp_audience_types`/`bhp_lead_magnets`/`bhp_mailchimp_signup_tags` filters and the popup-suppression exclusion list. All 5 pages live on staging (theme v1.19.30), verified: no PHP fatals, no public coupon codes, no fabricated Ingram/bulk-pricing/partnership claims, correct live WooCommerce Complete Collection pricing throughout, no horizontal overflow at 375px/1280px. Full detail: `ENGINEERING/AUDIENCE_LANDING_STATUS.md`, `ENGINEERING/AUDIENCE_LANDING_ASSET_MANIFEST.md`. **Production untouched.** Not yet done: full 9-breakpoint QA sweep, live GTM/GA4 event verification for the 4 new pages, the 4 new Mailchimp automations themselves (Andrew/ChatGPT).

## PRIOR (2026-07-14): P0 mobile header fix — nav hamburger restored, mobile CTA added — production
A real mobile device showed the header wordmark overflowing and the hamburger menu completely missing on narrow viewports. Root cause: flexbox's `min-width:auto` default meant the full "Brave Hearts Publishing" wordmark's `white-space:nowrap` content refused to shrink, pushing `.nav-toggle` off-screen. Fix: a dedicated short "Brave Hearts" wordmark variant shown only at the mobile `@container` breakpoint (not a clipped/ellipsis workaround) — full wordmark on tablet/desktop is unchanged. Separately, the mobile dropdown had no equivalent to the desktop header-bar "Get the Complete Collection" CTA (hidden at that breakpoint for space) — added `.site-nav__cta`, wired into the same shared sitewide primary-CTA color palette (`.header-expedition-cta` and other primary buttons use it too) so it can't drift out of color parity. Two real CSS bugs found and fixed during this work: a specificity collision that made the CTA render at every width instead of just mobile, and a second one that hid it at every width when first corrected — both resolved by matching selector specificity to the competing base rules. Deployed and verified via computed-style inspection (not just visual) on staging then production at 320/360/390/430px plus desktop/tablet regression — zero PHP fatals, zero console errors. Andrew confirmed on a real device before the production deploy. Theme v1.19.14 → v1.19.20. Commit `277bd8a`.

## NEW (2026-07-14): Frozen Funnel Architecture established — permanent company policy
Andrew established a permanent, company-wide audience-funnel architecture (`ENGINEERING/FUNNEL_CONSTITUTION.md`) governing every current and future funnel (Parent, Teacher/Librarian, Bookstore, Gift, Organization). Canonical sequence, email philosophy (Email 2 sells the result not the books, no coupon), mandatory purchase suppression before any coupon send, and a modular-automation requirement (reusable Email 1/Transformation/Purchase-Check/Coupon/Post-Purchase/Nurture modules, not one-off workflows). This must not be reopened or replaced with a parallel system.

## NEW (2026-07-14): P0 production correction — public [PARENT_COUPON_CODE_SUPERSEDED] advertising removed, Audience Coupon Policy frozen
The Complete Collection landing page publicly advertised an [PARENT_COUPON_CODE_SUPERSEDED] coupon code — inconsistent with the Frozen Funnel Constitution's principle that audience coupons are conversion tools, not public offers. Fixed: the line and its now-unused CSS rule removed from `plugins/brave-hearts-bundle-pricing/includes/bundle-landing-page.php` (no replacement discount messaging added), deployed to staging then production (plugin v1.8.2 → v1.8.3), verified live on both with a sitewide search confirming zero remaining public coupon-code references anywhere on the site. WooCommerce's [PARENT_COUPON_CODE_SUPERSEDED] coupon itself was not touched — confirmed unchanged. A permanent **Audience Coupon Policy** is now Frozen in `ENGINEERING/FUNNEL_CONSTITUTION.md` and `DECISIONS.md`. Future funnels' coupons ([EDUCATOR_COUPON_CODE_SUPERSEDED], [GIFT_BUYER_COUPON_CODE_SUPERSEDED], etc.) must comply — never public offers, always Email-3-only delivery.

## NEW (2026-07-14): Audience Routing Constitution — permanent policy
Known audiences (Parents, Teachers/Librarians, Gift Buyers, Bookstores, Organizations) are always routed directly to their own dedicated landing page/popup/journey — never through a quiz. A future Audience Routing Quiz will route only unknown/organic visitors (SEO, blog, Pinterest, social, AI search) into the same existing per-audience journeys; it is explicitly not part of any current sprint and cannot be built until every core audience funnel is production-complete and validated. Recorded in `ENGINEERING/FUNNEL_CONSTITUTION.md` and `DECISIONS.md`.

## NEW (2026-07-14): Audience Funnel System Sprint 1B — Parent Funnel landing page LIVE on production; Mailchimp consolidation build in progress
The Parent landing page's Collection/Trust/FAQ sections, hero secondary CTA, and `parent_landing_view` analytics event are now live on production (theme v1.19.14 on both environments). Popup, lead magnet, and Complete Collection destination all re-verified live post-deployment — zero regressions. The Mailchimp login gate is resolved — Andrew's authenticated session was used directly. Mailchimp account upgraded (by Andrew, manually) from Essentials Annual to **Standard Annual** ($192/yr, 6,000 sends, up to 200 Customer Journey steps — removes the 4-step cap that blocked native branching). A global purchaser-tagging automation (`Global - Tag Purchasers`) is Active. A new draft automation, `Parent - Acquisition Funnel`, is being built to consolidate the old split Email1/2 + Coupon Flow design into one canonical 3-email journey with a native Conditional Split for purchase suppression — trigger configured, everything else (Email 1/2/3, purchase-sync buffer, Conditional Split, testing, contact migration, old-flow retirement, post-purchase automations) still outstanding. See `ENGINEERING/MAILCHIMP_STATUS.md`, `ENGINEERING/PARENT_FUNNEL_STATUS.md`, `NEXT_TASK.md`.

## Current Production Version
Theme `brave-hearts-theme-deploy-explorer-expedition-guides` **v1.19.20**, active — bumped from v1.19.14 for the P0 mobile header fix (nav hamburger + mobile CTA, 2026-07-14). Staging is at parity (also v1.19.20).

## RESOLVED (2026-07-13): production GTM/consent theme infrastructure deployed
The gap found earlier the same day (production had zero trace of `BHP_Analytics_Config`/`BHP_Consent`/`BHP_GTM_Loader`) is now closed. Andrew approved the full 6-file package identified in the readiness audit, and it was deployed to production the same day: `class-bhp-analytics-config.php`, `class-bhp-consent.php`, `class-bhp-gtm-loader.php`, `class-bhp-utm-attribution.php`, `class-bhp-analytics-debug.php`, `class-bhp-wpconsent-bridge.php`, plus a 6-line `functions.php` wiring addition, plus WPConsent Free v1.1.7 installed/configured. Production now has a fully functional, fail-closed consent banner. GTM itself remains deliberately unpublished (no container/measurement ID set, `bhp_consent_decision_approved` stays `false`) — verified via 25-scenario browser QA that `gtmScriptCount` is 0 in every consent state. See `RELEASES/PRODUCTION_CONSENT_DEPLOYMENT.md` for full QA results, `RELEASES/PRODUCTION_GTM_CONSENT_READINESS_AUDIT.md` for the planning record, `ANALYTICS/GTM_STATUS.md`, `DECISIONS.md`, `KNOWN_ISSUES.md`.

## Current Staging Version
Theme `brave-hearts-theme-deploy-explorer-expedition-guides` **v1.19.55**, active — ahead of production (v1.19.52) by the independent-audit conversion fixes (2026-07-18: Educator Toolkit module on `/teachers/` + homepage audience gateway), the homepage form UX fix (1.19.54), and the audience-page discoverability layer (1.19.53). See `CHANGELOG.md`. Never assume full parity elsewhere from a matching theme version alone.

## RESOLVED (2026-07-13): "Printed Just for You" print-on-demand notice — deployed to production
A reusable component (product page, Cart, Checkout, Order Received/Thank You page) proactively setting delivery-time expectations, since real orders take ~8 days and customers previously had no on-site indication books are print-on-demand. Built on staging, then a copy revision was approved and the isolated component deployed to production the same day (theme v1.19.13 on both environments). Full detail, architecture, and production deployment record: `ENGINEERING/PRINTED_FOR_YOU_STATUS.md`.

## RESOLVED (2026-07-13): bundle-pricing plugin analytics parity restored on production
Both environments now report `brave-hearts-bundle-pricing` v1.8.2, but this is **not** full parity — production received only the isolated 7-file analytics patch (see `RELEASES/BUNDLE_PRICING_ANALYTICS_PARITY_PRODUCTION.md`); staging's `includes/dashboard/` (KPI/economics module) is materially ahead of production's, which was deliberately left untouched. Storefront-facing ecommerce analytics behavior is now equivalent between the two environments; the internal KPI dashboard is not.

## Current Phase
Analytics foundation (GTM/GA4) — build substantially complete, verified, **not published**. Consent architecture is the active blocker to publishing. See `/ANALYTICS/GTM_STATUS.md`.

## Completed Releases
- Kirkus credibility component — production, 2026-07-04
- Amazon customer review showcase — production, 2026-07-05
- [PARENT_COUPON_CODE_SUPERSEDED] Collection-only coupon — production, 2026-07-11
- CTA Engine (isolated subset: `BHP_CTA_Engine`, `BHP_Content_Classification`, `BHP_CTA_Collision_Detector`, `BHP_Required_Links_Gate`) — production, 2026-07-12
- Desktop header layout fix — production, 2026-07-13 (theme 1.19.4 → 1.19.8)

Full detail: `/RELEASES/`.

## Current Build
Branch `feature/production-integration-1.17.1` (name is historical — this branch's HEAD is what's live on production, though production's exact live files were captured as an independent snapshot for the header-fix deploy rather than deployed from this branch directly — see `RELEASES/HEADER_LAYOUT_FIX_PRODUCTION.md`). Local commits `572421d`, `d26a7f6`, `7878b68`, `bf8f79d` sit ahead of `origin` — push attempted twice more on 2026-07-13, still blocked by the same non-interactive credential prompt (see `KNOWN_ISSUES.md`); nothing lost, Andrew can push from his own session.

## Blocked Items
1. **GTM/GA4 publish** — consent mechanics are resolved (WPConsent Free live on production and staging, 2026-07-13, re-verified working correctly 2026-07-13 Phase 10). The plugin-staleness gap found the same day (production running `brave-hearts-bundle-pricing` v1.7.1 against repo/staging v1.8.2, breaking most GA4 ecommerce events) was **fixed the same day** via an isolated 7-file analytics-only patch — production now correctly fires `view_item_list`/`select_item`/`view_item`/`add_to_cart`/`view_cart`/`begin_checkout`/`add_shipping_info`/`add_payment_info`/`bundle_add_to_cart`, all live-verified with zero commerce regressions. See `RELEASES/BUNDLE_PRICING_ANALYTICS_PARITY_PRODUCTION.md`. Remaining blocker is now only the deliberate, separate business decision: `bhp_consent_decision_approved` stays `false` and no GTM/GA4 IDs are configured on production until Andrew explicitly approves analytics activation, after an authenticated GTM Preview/GA4 DebugView validation session. See `/ANALYTICS/CONSENT_STATUS.md`, `/RELEASES/PRODUCTION_CONSENT_DEPLOYMENT.md`.
2. **Staging→production full-suite parity** — staging's newer `tests/` regression suites (Phase 1D-era) haven't all been promoted to production via a full theme-ZIP cycle. Not urgent; do not fix as a side effect of unrelated work.
3. **Mailchimp deliverability audit** — flagged as the next planned phase, not started, awaiting Andrew's explicit go-ahead.
4. **Google Merchant Center disapproval** — all 6 synced products show "disapproved" (new finding, 2026-07-13); needs Andrew's console access. See `/MARKETING/GOOGLE_MERCHANT_STATUS.md`.
5. **Local commits not yet pushed to origin** — see Current Build above.
6. **Production missing a WooCommerce coupon-contrast CSS block that exists in the git repo** — discovered 2026-07-13 during the header-fix deploy's drift check, deliberately left untouched (out of scope). See `KNOWN_ISSUES.md`.

## Major Systems
| System | Status |
|---|---|
| WordPress theme | v1.19.12 production / v1.19.12 staging (corrected 2026-07-13 — was stale at v1.19.4) |
| WooCommerce | Live, Stripe live mode, 6 published products (3 books × 2 formats) |
| Bookvault | Connected for paperback fulfillment; hardcovers intentionally out-of-stock pending tested hardcover fulfillment |
| GTM | Container `GTM-N474PRSH` — 27 variables / 39 triggers / 40 tags built, verified, **not published** |
| GA4 | Property `G-7M42X19Z2T` — not currently receiving any data (GTM unpublished) |
| Mailchimp | Standard Annual plan. Adventure Kit Active, Coupon Flow deliberately Paused, Global purchaser-tagger Active, new consolidated Parent journey Draft-in-progress |
| CTA Engine | Live on production (isolated subset) |
| Consent | Default-deny Consent Mode v2 live on **both staging and production**. WPConsent Free (WordPress.org, no account required) installed/configured on both — CookieYes/SaaS-CMP path rejected. |

## Current GTM Status
27 variables, 39 triggers, 40 tags built and verified correct on sample inspection (Phase 9 minimum gap patch applied 2026-07-12: `bhp_book`/`bhp_format`/`bhp_source` variables + `bundle_type_purchased` trigger/tag). 108 unpublished workspace changes, 0 submitted, 0 published. Live version is still the empty container from initial setup. Full detail: `/ANALYTICS/GTM_STATUS.md`.

## Current CTA Status
Isolated subset live on production since 2026-07-12: contextual CTA engine, content classification, duplicate-CTA prevention, required-links gate (PHP backend). **Gap found 2026-07-13 (Phase 10), still open:** production's `assets/js/nav.js` (a theme file) predates the CTA Engine's client-side payload-enrichment code, so `contextual_cta_click` fires with `cta_id`/`cta_placement`/`cta_destination_type`/`audience`/`funnel_stage`/`variant` all missing — backend registry present, frontend attribution lost. Not fixed by the same-day bundle-pricing plugin patch (that was plugin-scoped; this is a theme file). Full Phase 1D/1E suite (campaign landing, conversion scoring, content-intelligence engine) remains staging-only. Full detail: `/ENGINEERING/CTA_ENGINE_STATUS.md`, `KNOWN_ISSUES.md`.

## Current WooCommerce Status
Live, Stripe live mode. 3 books (Mariana Trench, Mount Everest, The Amazon), each in paperback (in stock) and hardcover (intentionally out of stock pending Bookvault hardcover fulfillment testing). [PARENT_COUPON_CODE_SUPERSEDED] coupon live, Collection-only. Full detail: `/ENGINEERING/WOOCOMMERCE_STATUS.md`.

## Current Mailchimp Status
Account upgraded to **Standard Annual** (2026-07-14, by Andrew). Live automations: Reluctant Reader Adventure Kit (Parent Email 1+2, Active), Coupon Flow (Email 3/[PARENT_COUPON_CODE_SUPERSEDED], **deliberately Paused** — protecting 3 real contacts pending the new consolidated journey), Global - Tag Purchasers (purchase tagger, Active, **proven working via a controlled staging test 2026-07-16** — live Flow Data 1/0/0/1), Parent - Acquisition Funnel (new consolidated 3-email journey, **Draft, fully built**), Educators/Gift Buyer/Retailer/Organization journeys (all **Draft, fully structured**, all 4 Educators gaps fixed 2026-07-15/16), MT Lead Magnet (Teacher funnel, Active). Purchase scope Frozen (any purchase suppresses). Full detail: `/ENGINEERING/MAILCHIMP_STATUS.md`.

## Current SEO Status
Rank Math active, structured data (GTIN, brand, shippingDetails) verified clean, no fabricated review/rating schema anywhere. GSC integration lives in the separate `brave-hearts-seo-engine` repo — not tracked in detail here.

## Current Blog Count
**36 published posts** (production, verified via WP-CLI 2026-07-12). Detailed content operations (weekly slates, taxonomy, editorial packets) tracked in `/CONTENT/BLOG_STATUS.md` and the canonical content-operations docs.

## Current Book Count
**3 published, purchasable books**: The Mariana Trench, Mount Everest, The Amazon (published 2026-06-26).

## Current Pinterest Count
Not independently verified this session — content-operations pin production is tracked via the weekly content cycle docs and the `brave-hearts-seo-engine` repo. See `/CONTENT/PINTEREST_STATUS.md` for what's known.

## Current Analytics Status
GTM/GA4 build complete but unpublished; nothing currently reaches GA4. The only live Google script on production is an unrelated Google Ads conversion pixel from the Google Listings & Ads plugin. GTM Preview UI could not be connected via browser automation 2026-07-13 (tool limitation); direct `dataLayer` inspection on staging confirmed `view_item_list`, `view_item`, `add_to_cart`, `add_shipping_info` fire correctly. See `/ANALYTICS/GA4_STATUS.md`.

## Current Legacy Content Audit
All 35 pre-CTA-Engine blog posts audited 2026-07-13, read-only. Automatic `bhp_get_guide_registry()` grid gives near-universal topic-hub-link coverage already; biggest real gap is "The Amazon" (3rd book) mentioned in only 1 of 35 posts. **Update, same day (overnight build):** Sprint 1 (posts 76 & 68) fully implemented and deployed to production — both now have real in-body topic-hub and book-discovery links. **Update, same day (final session):** Batch 2 (posts 26, 66, 30) is now **fully production-complete** — both the approved topic-hub copy (each post gained a genuine in-body sentence linking "Explorer Expedition Guides" to its topic hub, post 66's book link points directly to the product page) and the earlier mechanical fixes (Amazon→on-site book-link URL swaps on posts 26/30, the malformed split-anchor repair on post 66) are live on production, byte-diff-verified against a staging reference and QA-passed with zero regressions. A sitewide read-only scan of all 36 posts found at least one mechanical-defect pattern in 35 of them (Linktree links, weak anchors, CTA-shaped-not-in-body hub links, corpus-wide missing Amazon Associates tracking tags, possible malformed split-anchors) — prioritized P0–P3 queue built for future sprints. A real gap was also confirmed in `BHP_CTA_Collision_Detector`'s automated required-links gate (never recognized `/teachers/#...` hub URLs, plus an anchor-attribute-order regex fragility) — see `OPEN_QUESTIONS.md` and `KNOWN_ISSUES.md`. See `/CONTENT/LEGACY_BLOG_CONVERSION_AUDIT.md`.

## Current Google Merchant Center Status
Google Listings & Ads plugin active, all 6 products synced but showing **"disapproved"** at Merchant Center (new finding, 2026-07-13); Google Ads account also needs reconnection. See `/MARKETING/GOOGLE_MERCHANT_STATUS.md`.

## Conversion QA Sprint 1 (2026-07-13) — full live funnel validation, findings only
Read-only (plus one no-payment cart/checkout mechanics pass) audit of every customer conversion path on production: individual book funnels, Complete Collection/bundle, Teacher funnel, Parent funnel, blog→product, homepage/nav/search→product. Found 1 P0, 2 P1, 3 P2, 2 P3 issues. No code changed, no remediation performed at the time (by design — that was a validation-only sprint). Full detail: `ENGINEERING/CONVERSION_QA_SPRINT1.md`.

## Hardcover Fulfillment Verification sprint (2026-07-13) — same day follow-up, later superseded
Acted on 2 of the above findings. Fixed the malformed blog links (posts 38, 64, 88, 90) — zero regressions, this fix stands. Restored hardcover stock status to `outofstock` as a protective measure pending Andrew's decision — **this specific action was superseded the same day, see below.**

## Print-on-demand stock policy established (2026-07-13) — controlling policy for the 6 core products
Andrew corrected the framing above: Brave Hearts is print-on-demand with no physical inventory, so "out of stock" was never the right mechanism for unverified fulfillment — it may only represent a verified fulfillment failure or an explicit sales suspension. Canonical policy recorded verbatim in `DECISIONS.md`. Bookvault mapping directly re-verified as structurally identical across all 6 current products (3 paperback + 3 hardcover) immediately before acting. All 3 hardcover products restored to `instock`, confirmed live on production. Legacy catalog cleaned up: empty broken draft product 338 permanently deleted (zero sales/dependencies, backed up first, Andrew explicitly confirmed the specific product ID); genuine former-Lulu draft product 12 (3 real sales) confirmed correctly archived, left untouched. The WooCommerce catalog is now exactly 6 published products + 1 correctly-archived draft.

## Last Updated
2026-07-13 — Print-on-demand stock policy established: all 6 core products (3 paperback + 3 hardcover) confirmed in-stock and purchasable; empty legacy draft product deleted. See `DECISIONS.md`'s "Print-on-demand stock policy" entry. Earlier same day: the 4-post malformed-link defect (found by Conversion QA Sprint 1) was fixed, and Legacy Blog Conversion Batch 2 (posts 26, 66, 30) reached fully production-complete status. See `CONTENT/LEGACY_BLOG_CONVERSION_AUDIT.md`, `CURRENT_TASK.md`, `NEXT_TASK.md`.

**Earlier same day:** overnight build #2 — Legacy Blog Conversion Sprint 1 (posts 76 & 68) deployed to production and live-verified; Batch 2 mechanical fixes staging-implemented; sitewide read-only mechanical-defect scan across all 36 published posts.

**Earlier same-day:** overnight build #1 (knowledge-base closeout, GTM re-verification, consent vendor research, GA4 dataLayer validation, legacy blog audit) followed by the desktop header layout fix, staging-tested and Andrew-approved, deployed to production (theme 1.19.4 → 1.19.8).
