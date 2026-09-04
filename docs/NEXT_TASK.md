# Next Task


> ## ⭐⭐ NEWEST, 2026-09-03 · Production is theme `1.19.358` / bundle plugin `1.8.83`. **Everything below this block predates the `1.19.357` and `1.19.358` releases.**
>
> **Nothing here is scheduled.** These are the candidates a next session would pick from, in the order that
> most reduces risk. Each one needs the owner's go-ahead before it becomes work.
>
> 1. **`LD-22`, the internal identifier in the public source** (`KNOWN_ISSUES.md`). **This repository is
>    public**, the identifier was inherited rather than introduced, and a plugin suite assertion has been
>    failing on it across several releases. It is the only item on this list where the cost of waiting
>    grows on its own. **It is scoped work, not a decision**, but it touches comments another release
>    preserved deliberately, so the removal should be specified rather than improvised.
> 2. **`LD-27`, the permanent reopening.** Every past read-aloud is now orderable for ever. It is what was
>    ruled; the only question is whether any school should be narrowed. **Until it is confirmed, further
>    work on the after-visit phase has no settled target.**
> 3. **`LD-28`, the uncapped past column.** One ordering button per visit, for ever, on a page reached from
>    printed codes. Cheap to bound now, awkward to bound after a year of visits. Design and the owner
>    decide the rule; engineering can implement whichever it is.
> 4. **`LD-24`, the 14-day context against an endless link.** Deliberate, and probably right. Worth one
>    sentence of confirmation rather than a change.
> 5. **`LD-26`, the inverted fail-safe.** Same: confirm, do not change, unless the answer is that a broken
>    hook should be able to close a window.
> 6. **`F-08` and `F-09`**, the mobile visit surface and clear-token consistency. Open since before the
>    `1.19.350` series and still unscoped. **`F-08` must be re-attempted with a real mobile user-agent
>    string**, or on a real phone; viewport emulation does not change the user agent and every
>    non-reproduction so far used the wrong instrument. Re-verify both against live `1.19.358` first: the
>    visit surface has changed again twice in this series.
> 7. **`LD-25`, the band's surface coverage.** Only becomes work if the band is wanted on a product page or
>    the collection landing page; it has never rendered there in any state.
> 8. **The items carried from the previous series** - `LD-18` and the wider sweep it belongs to, `LD-19`,
>    `LD-20`, `LD-21`, `LD-14`, `LD-17`, `LD-11`, and the parked cosmetic list. Unchanged in priority.
> 9. **The `--url` test-harness dependency**, and beside it the counting-method divergence: two lanes in
>    this series reported 102 and 49 FAIL lines for adjacent trees using their own methods. **If an
>    absolute failure count is ever needed for a decision, one method should be fixed and recorded.**
>
> ⚠️ **Verify production's version with `wp theme list --status=active` before starting any of these.** The
> numbers in this block are recorded from the deploying lane, not read from production here.
> ## ⭐⭐ NEWEST, 2026-09-02 · Production is theme `1.19.356` / bundle plugin `1.8.81`. **Everything below this block predates the `1.19.355` and `1.19.356` releases.**
>
> **Nothing here is scheduled.** These are the candidates a next session would pick from, in the order that
> most reduces risk. Each one needs the owner's go-ahead before it becomes work.
>
> 1. **`LD-21`, the ragged bottom edge** (`KNOWN_ISSUES.md`). It is the only item on this list the owner can
>    settle by looking at a live phone, it is on the purchase surface he asked to have fixed, and whichever
>    way it goes it is a small CSS change. **Cheapest decision, highest visibility.**
> 2. **`LD-19`, the redirecting shortcode page.** Two corrections shipped in `1.8.81` are invisible today
>    because the page they render on redirects. The questions are whether the shortcode stays maintained and
>    whether the staging 302 should become a production 301, as its own code comment says. **Both are
>    decisions, and until they are made, work on those two surfaces cannot be verified by looking at a
>    page.**
> 3. **`LD-18`, the customer-facing em dash** in a plugin `aria-label` a screen reader speaks. One string.
>    It should be taken as part of the wider em-dash sweep rather than alone, so the sweep is what needs
>    scheduling.
> 4. **`F-08` and `F-09`**, the mobile visit surface and clear-flag consistency. Open since before the
>    `1.19.350` series and still unscoped. **`F-08` must be re-attempted with a real mobile user-agent
>    string**, or on a real phone; viewport emulation does not change the user agent and every
>    non-reproduction so far used the wrong instrument. Re-verify both against live `1.19.356` before
>    specifying anything: the visit band has changed five times since the findings were written.
> 5. **`LD-20`, the `display: contents` trade.** Only becomes work if zero tolerance is wanted for the
>    older-engine list-grouping loss; the alternative is a markup change plus a desktop-restoring rule.
> 6. **`LD-14` and `LD-17`**, an audit test to restate and a slug dependency to make robust. Both small,
>    neither urgent.
> 7. **The parked cosmetic list** described in `CURRENT_TASK.md`. Re-check it against the live pages first.
> 8. **The `--url` test-harness dependency** in `KNOWN_ISSUES.md`. Until it is settled, two suite runs are
>    only comparable if both used `--url`. **A related, cheaper item sits beside it:** the two build lanes in
>    this series counted the same tree's failures differently, so if an absolute failure count is ever needed
>    for a decision, one method should be fixed and recorded.
>
> ⚠️ **Verify production's version with `wp theme list --status=active` before starting any of these.** The
> numbers in this block are recorded from the deploying lane, not read from production here.
> ## ⭐⭐ NEWEST, 2026-09-02 · Production is theme `1.19.354` / bundle plugin `1.8.79`. **Everything below this block predates the `1.19.350` to `1.19.354` series.**
>
> **Nothing here is scheduled.** These are the candidates a next session would pick from, in the order that
> most reduces risk. Each one needs the owner's go-ahead before it becomes work.
>
> 1. **`LD-10`, the band-versus-counter divergence** (`KNOWN_ISSUES.md`). It is the only open item in this
>    series that can show a parent one school's name beside another school's shelf count. **It cannot be
>    fixed without a ruling**, because reconciling the two changes entitlement, not display. Getting that
>    ruling is the highest-value next step even though it is not itself engineering work.
> 2. **`F-08` and `F-09`**, the mobile visit surface and clear-flag consistency. Open since before this
>    series and unscoped. They should be re-verified against live `1.19.354` before being specified, because
>    the visit band changed three times in this series and the original findings predate all three.
> 3. **`LD-12`**, the CSS specificity trap. A four-line comment near the `body:not(.home)` block in
>    `style.css`. It has already cost two rules in one pass and is the cheapest item on this list.
> 4. **The parked cosmetic list** described in `CURRENT_TASK.md`. Re-check it against the live pages first.
> 5. **The `--url` test-harness dependency** in `KNOWN_ISSUES.md`. Until it is settled, two suite runs are
>    only comparable if both used `--url`.
>
> ⚠️ **Verify production's version with `wp theme list --status=active` before starting any of these.** The
> numbers in this block are relayed from the deploying lane, not read from production here.

> ## ⭐⭐⭐⭐ NEWEST 2026-08-03 — **ITEMS 0 AND 1 BELOW ARE DISCHARGED.** Production is theme `1.19.157` / plugin `1.8.16`.
>
> ### ✅ Item 0 — DISCHARGED. The 1.19.156 release record exists.
>
> `RELEASES/PRODUCTION_RELEASE_1_19_156.md`, plus `RELEASES/PRODUCTION_RELEASE_1_19_157.md` for the release that followed it.
>
> ⭐ **Item 0's reasoning was right and is respected in how it was closed.** It said the record *"must be written by the session that shipped it — reconstructing it from another agent's artefacts would be a fabricated verification."* **No QA narrative was reconstructed.** The record is built from the builder's **own verbatim commit messages**, `git show --stat` file-and-line counts, the builder's **own writer-lock closeout table** (artefact md5, rollback snapshot, deploy method, test send), and live verification — and **every unverified item is named as unverified**, including that no real email client has rendered any of these messages and no deliverability check has been run. **The record states its own provenance on its face.**
>
> ### ✅ Item 1 — DISCHARGED. The branches are pushed.
>
> **Verified live 2026-08-03:** branch `feature/bookvault-dispatch-tracker-1.19.157`, HEAD `f9f5c7e`, `git status --porcelain` **empty**. Both branches and the documentation commit `f9f5c7e` are on origin, on the owner's explicit instruction. ⛔ **Pushed is not merged and not deployed.**
>
> ### ⛔ Items 2 and 3 remain OPEN and are now the top of the list
>
> **2. A real test order, and read the email.** ⭐ **This is now larger than it was.** Since item 2 was written, the entire E1–E7 copy layer shipped (1.19.156) and **no real email client has rendered any of it** — desktop, mobile-390px and the cross-client matrix are all unverified, and no SPF/DKIM/DMARC check has been run. **One real order exercises all seven.** Human task; nobody else can do it.
>
> **3. A browser QA pass against PRODUCTION.** Unchanged and still open.
>
> ### 🆕 4. The dispatch tracker's live-fire test — ~2026-08-11 to 08-12
>
> 1.19.157 polls the fulfilment API every three hours in **DRY** mode and writes nothing. At real dispatch of the two open orders the log is expected to flip to `DISPATCH`. **Switching it live is a separate, supervised act at that test.** ⛔ **Until then it has never completed an order and has never caused an email.** Record: `RELEASES/PRODUCTION_RELEASE_1_19_157.md`.
>
> **The list below is preserved verbatim rather than edited.**

> ## ⭐⭐⭐ NEXT, after the 1.19.155 production push (2026-08-03) — supersedes every block below
>
> 0. ⛔ **WRITE THE 1.19.156 RELEASE RECORD — it does not exist.** Production shipped **twice** on 2026-08-03: 1.19.155 (fully recorded at `RELEASES/PRODUCTION_RELEASE_1_19_155.md`) and then a **theme-only** transactional-email **copy** layer, **1.19.156**, verified live but **undocumented**. ⭐ **It must be written by the session that shipped it** — reconstructing it from another agent's artefacts would be a fabricated verification, which is why the gap was named rather than filled. **Until it exists, the only way to know what is in 1.19.156 is to read the commits.**
>
> 1. **Push the branch.** `feature/product-media-gallery-1.19.140` is **unpushed**, so production is running code with no remote copy. Andrew pushes via GitHub Desktop. **This is the highest-priority item on the list** — it is the only one where waiting increases risk rather than just deferring value. ⚠️ **It is now two layers of unpushed work:** the deployed commit **`e98cd0f`** (1.19.155, live) **and** a newer local commit **`237d71b`** (1.19.156, the transactional email copy layer) which is **undeployed**. Pushing covers both; **deploying 1.19.156 is a separate decision and is not part of this item.**
> 2. **A real test order, and read the email.** The navy email chrome, the masthead attachment, the corrected site title and the cancelled-order email are all confirmed **stored** on production. **Nobody has seen a rendered message.** This is a human task and it is the largest unverified surface in the release.
> 3. **A browser QA pass against PRODUCTION.** Cart quantity geometry, the checkout Remove control, totals recalculation and the empty-checkout state are React-rendered, cannot be proven over HTTP, and were verified **on staging only**. 390px and 1440px, `window.innerWidth` asserted.
> 4. **Andrew's six queued decisions** — see `CURRENT_TASK.md`. The two that block other work are `/book-bundles/` (does it exist on production?) and product 12 (live, legacy or duplicate?).
> 5. **One human look at the live header and footer logo**, phone and desktop. That is all that remains of `CYCLE142-OPS-021`.
> 6. **Funnel-page CLS.** A figure of **0.408** was carried into the release-record session and is **unverified** — no browser run corroborates it, and two earlier sweeps reported 0.000 by sampling too early. One full-duration run per funnel page, with the sampling window stated.
> 7. **Still not discharged, carried forward:** `/retailers-wholesale-guide/` renders no gallery while the other four audience pages do · the 320px cart-table overflow (pre-existing) · the wider retired-CSS-fallback inventory · the cost literals still in `class-bhp-cost-config.php`.


> ## NEXT, after Wave F + G (2026-08-03) — superseded by the block above
>
> 1. **Andrew's two decisions**, both recorded in `RELEASES/WAVE_F_G_HOMEPAGE_CONTRAST_EMDASH_1_19_149.md` §9:
>    a. **The verbatim customer-review em dash** in `inc/amazon-reviews.php:67`. "Replace all em dashes" says no exemptions; the absolute rule says a real review is never altered. **Left untouched pending his ruling** — a one-character change either way.
>    b. **"12 short chapters" / the Everest TOC.** The claim is live on four pages and **could not be verified from anything in either environment**. Needs the physical book or a TOC scan.
> 2. **Andrew's combined staging review of 1.19.149**, then his production decision. **Production ship would be a theme + plugin deploy;** the em-dash content sweep and the review toggles are already live on production.
> 3. **Not done, flagged:** `/retailers-wholesale-guide/` has no gallery while the other four audience pages have one. Decide whether that is intended.


> ## ⭐ NEXT, after Wave E (2026-08-02)
>
> 1. **Browser QA of staging 1.19.146** — desktop and mobile, `window.innerWidth` read back and recorded, console error count, horizontal-overflow check on `/` and `/educators-adventure-learning-toolkit/`. **This is the one required step Wave E could not perform.**
> 2. **Andrew's combined staging review**, then his production decision. Production ship of 1.19.146 is a theme deploy only — the two content fixes are already live.
> 3. **Five owner decisions are queued** — see `CURRENT_TASK.md`'s Wave E block: the educator gallery exception · hardcovers 14/17/20 · the “One flip-through” heading · the MetaMetrics attribution line (blocked, not invented) · the Standing Rules §3 amendment for the Island Peak specifics.
> 4. **`ACT-OPS-035` — the remaining-Lulu sweep is NOT discharged.** Wave E fixed two production pages. Every other customer-facing surface still needs the same class of sweep as FD-32's brand sweep, and the obsolete Lulu build guide still reads as current in Drive (`C18`).
>
> ---


> ## ⭐⭐⭐ NEWEST 2026-08-02 (after MORNING WAVE D) — **the next task is the owner's staging review of 1.19.145, then his decisions.** Nothing engineering-side is blocked on engineering.
>
> **Nothing ships to production until Andrew reviews staging.** When he does, six decisions come back, and four of them change what gets built next:
>
> | Decision | If yes | If no |
> |---|---|---|
> | The header logo plate | ship as built | commission a **reversed light-on-dark export**; the plate CSS is then deleted, not adjusted |
> | Which dedication is canonical (Volume I *"Be brave."* / Volume II *"Be strong."*) | Volume I already implemented | one string changes in `page-audience-gift-buyers.php` |
> | The mobile sticky bar over the module | no work | ~15 lines in `audience-landing.js` to suppress the bar while the module is in view |
> | The E1 email heading ("Thank you for your order" vs the new subject) | no work | one WooCommerce setting, which is an **owner-gated** change |
>
> ⛔ **The production ship of the brand swap is THREE settings plus a theme deploy** — `custom_logo`, `site_icon`, and the Rank Math Organization logo. `the_custom_logo()` renders nothing on production today and the retired sunrise-heart lives only in the Organization schema. Deploying the theme alone would change **nothing visible** and would leave the retired mark in every page's structured data. Release record §2.1.
>
> **One human task nobody else can do:** record the payment timestamp and the print partner's "Packed"/dispatch timestamp for roughly ten real orders. Ten data points turn the E1 email's empty timing slot into a real, honest range and unblock the follow-up email. It is a portal login.

---


## ⭐⭐ NEWEST 2026-08-02 (MORNING) — THE ONE APPROVED NEXT TASK: **the owner's staging review of 1.19.143 / 1.8.11**

**The task described in the block below is DONE.** The deploy ran, and all eight QA points it lists were executed and passed. See `RELEASES/COLLECTION_GALLERY_FUNNEL_PAGES_1_19_143.md` §11 for the measured evidence.

**What is next is not an engineering task.** Staging carries theme **1.19.143** and bundle plugin **1.8.11**; production remains **1.19.142 / 1.8.10**. **A production deploy requires the owner's explicit, current-turn approval and nothing about this release changes that.**

### The review package

1. The six funnel pages on staging, desktop and mobile — the Complete Collection gallery in compact mode below the fold.
2. `/complete-collection/` — unchanged, 9 slides, `1 / 9`.
3. The Collection landing page's value list — the price superlative is gone, replaced by "Direct from the publisher".
4. `/terms-and-conditions/` — **on both environments**, the 24-hour production delay claim is gone.

### ⛔ Two owner decisions that should be settled BEFORE or ALONGSIDE that deploy

- **`CYCLE141-LD-11` — shipping on bulk single-title orders.** 30 copies of one paperback currently renders **$1.99** shipping. Tiering is by *distinct title*, not by book count. If the intent is genuinely per-book, this is a code change to the bundle plugin **and an owner-gated one**, and it is better decided before another release than after.
- **`CYCLE141-LD-12` — the former print vendor still named on production's Terms and Privacy pages** (5 locations, including the data-sharing disclosure). **This is a database fix, not a deploy** — it can be applied independently and does not need to wait for the theme release. Replacement text is prepared.

### Then, still queued and unchanged

- The **authentic Mariana interior reshoot** (`ROADMAP.md`, QUEUED) — the current storefront images are the AI-assisted set knowingly approved as temporary, two carrying visible text artefacts. Widening that entry to Everest is the owner's call and has not been done.
- The **GTM trigger/variable/tag specification** for the five gallery dataLayer events — **PREPARED, NOT APPLIED**; applying it is a configuration change and owner-gated.
- **`CYCLE141-CX-8`** — `/teachers/`' final CTA routes into the parent funnel. Unchanged, and the reason the Collection gallery was deliberately not added to that page.

---

## (HISTORICAL — the task below is COMPLETE, superseded by the block above) 2026-08-02 (OVERNIGHT) — deploy theme 1.19.143 to staging and QA it

Everything else is behind this, because **1.19.143 is built, committed and completely unverified in a browser.**

**Step 1 — unblock the deploy. This is a permission, not a decision.**
`wp theme install --force` against the **staging** document root is refused by the permission layer (`CYCLE141-LD-9`), while `wp eval`, `wp eval-file`, `scp` and read-only `wp` queries all succeed. Under G-40 §16.2 staging deployment is autonomous authority for `lead-developer`, so the runtime permission and the governance grant currently disagree. **Resolving that is Andrew's** — changing a permission setting is outside the engineering role's authority and was not attempted.

The artifact is ready and already audited: **`/tmp/theme-1.19.143.zip`** on the SiteGround host — 170 files, prefix `brave-hearts-theme-deploy-explorer-expedition-guides`, zero `docs/`/`tests/`/`plugins/`/`.git` entries, `Version: 1.19.143` inside. Rollback point already taken and listed: `~/bhp-STAGING-backup-1.19.142-20260802-nightb/`.

```
wp theme install /tmp/theme-1.19.143.zip --force --user=1     # staging doc root
wp theme list --status=active --user=1                        # must read 1.19.143
wp eval 'echo "ok";' --user=1                                 # must print ok
wp sg purge --user=1
```

**Step 2 — the QA that has not happened.** In a real browser, desktop and mobile, **`window.innerWidth` read back on every mobile check** (browser-automation resizes are unreliable on this machine and a "390px" screenshot taken any other way is a lie):

1. Each of the six pages renders **exactly one** `[data-bhp-gallery]` and one `id="bhp-look-inside-complete_collection"`.
2. `book-media.css` and `book-media.js` both present at `?ver=1.19.143` on all six.
3. Where `count > 1`, the counter reads **`1 / 3`**, never `1 / 9`. Where `count === 1`, `bhp-media-gallery--single` is present and arrows/counter/rail are absent.
4. ⭐ **The regression check that matters most: `/complete-collection/` still renders 9 slides in hero mode with `1 / 9` and `loading=eager fetchpriority=high` on slide 0, and the three product pages are unchanged.**
5. Zero console errors on all six. Zero horizontal overflow at ≤428px. Thumb tap targets ≥44×44.
6. Videos: play on click, never autoplay, poster present, `playsinline` + `preload="metadata"` intact.
7. Funnel isolation by direct `localStorage` inspection — `bhp_parent_popup*` and `bhp_mariana_popup*` untouched and still independent.
8. Purchase-path smoke test, **no order placed**, cart emptied and confirmed empty afterwards.
9. Full-page screenshots, both viewports, to `Business OS\WORKING-DRAFTS\lead-developer\screenshots-2026-08-03-overnight\` — **that directory is currently EMPTY, because nothing renderable exists to shoot.**

**Step 3 — only then** consider production, which is a separate Andrew gate and must not be inferred from a successful staging QA.

### Parallel, independent of the above

- **GTM.** The full trigger/variable/tag specification for the five gallery events is written and ready to apply: `Business OS\WORKING-DRAFTS\lead-developer\DRAFT-2026-08-03-GTM-GALLERY-TRIGGER-SPEC.md` — 8 Data Layer Variables, 5 Custom Event triggers, 5 GA4 Event tags, exact parameter mappings, a 14-step Preview verification. **PREPARED, NOT APPLIED — GTM configuration is an owner gate.** Note it changes nothing customer-facing and sends nothing to GA4: the container is still 0 Published and the real blocker is consent.
- **`/teachers/`** — Andrew to rule on whether its final CTA should keep linking to the *parent* funnel's landing page. The Collection gallery was deliberately not added there pending that answer (`CYCLE141-CX-8`).
- **SOP-06 step A8** says "zero `staging2` occurrences in code"; there are 5 and always have been, all of them load-bearing staging-*detection* constants and guards. The wording needs repair (`CYCLE141-LD-8`) — Business Ops owns it, Andrew approves it.

---

## SUPERSEDED 2026-08-02 (the gallery-media task below is DISCHARGED — the Wave-5 migration landed and the composite was observed live on production)

## 2026-08-02 — get the "Look Inside" galleries onto production, and it starts with an Andrew decision, not with engineering

Theme **1.19.142** and bundle plugin **1.8.10** are live on production. The gallery **code** is there; the gallery **media** is not. **0 of 29 attachment slugs resolve in the production media library** (staging: 28/29), so `inc/book-media.php` fails closed and production renders no gallery at all — cleanly, with zero console errors, looking exactly as it did at 1.19.121.

**This is not an engineering task yet, and must not be started as one.** `inc/book-media.php`'s own provenance block records that the Everest set comes from an AI-assisted pipeline (Higgsfield job IDs; preserved `trainedAlgorithmicMedia` / "Made with Google AI" XMP) and that two items carry **visible text artefacts a print run would not produce** — a running head reading `ADVENTURES OF CHARLOTTE AND IJENRS`, and a back cover reading `breathioking landscopes` / `Perfect fcr first chapter book readers`. That file states these were **"approved by him for staging."**

**Ordered steps:**

1. **Andrew decides, per book, which gallery assets may appear on the live storefront.** Approving a theme deploy did not approve these images for customers. Related and already queued: the Mariana interior reshoot in `ROADMAP.md` → *Planned* (status QUEUED).
2. Only then: upload the approved assets to the **production** media library **with the exact attachment slugs** — the slug is the contract; the registry never uses IDs, deliberately, because the two environments assign different ones.
3. Re-verify rendered on production at 1440 and 390 with `window.innerWidth` read back: expected item counts **Mariana 7 · Everest 8 · The Amazon 5 · Complete Collection 9**, composite slide 1 loading with byline and brand line, zero console errors.
4. Separately and independently: the five gallery analytics events (`gallery_count`, `item_index`, `item_type`, `item_group`, `direction`, `interaction`, `method`, `item_label`) still have **no GTM trigger and no tag**. GTM is configuration and an owner gate — it was deliberately skipped this wave, not attempted.

`CYCLE141-LD-1` · `KNOWN_ISSUES.md` · `RELEASES/PRODUCTION_RELEASE_1_19_142.md`

**The section below is retained unedited, per this repo's append-don't-rewrite rule:**

## HIGH PRIORITY OWNER TODO (2026-07-31): Mariana Trench product-gallery photography and flip-through

> **STATUS UPDATE 2026-08-02 — largely delivered; the remainder is now QUEUED, not high priority.**
>
> The product gallery described below was built and is **live and owner-approved on staging** (theme 1.19.133) for all three titles, not just Mariana. Mariana renders seven items: cover, flip-through video, whale spread, depth-diagram / Brave Learning spread, Thank-you / Glossary spread, front-cover photograph, back-cover photograph. Everest (8 items) and The Amazon (6 items) are also live. Production is untouched at 1.19.121.
>
> **What remains** is narrower than this section: a reshoot of three Mariana interior spreads whose printed text was altered by a Higgsfield pass. That work is now tracked as **"Authentic Mariana interior reshoot and gallery replacement"** in `ROADMAP.md` → *Planned*, **status QUEUED**, gated behind the remaining trust-focused website buildouts. It is **not** a blocker on the current gallery, and Andrew has explicitly approved keeping the current images until then.
>
> Read the ROADMAP entry as authoritative for the remaining scope. The capture conditions and guardrails below still apply to that reshoot.

**Priority: superseded — see the status update above. Original text retained unedited, per this repo's append-don't-rewrite rule.** This was the next owner-led product-media task for the unified Shop/book-page conversion experience. It is not a production defect, and it did not authorize any theme, product, media-library, staging, or production change at the time it was written.

### Approved capture conditions

- Shoot at home beside a window in bright, indirect daylight; turn all overhead lights off.
- Use a matte navy or similarly clean neutral background, the iPhone rear 1× camera, and an overhead/parallel camera position.
- No hands, phone shadows, glare, filters, digital zoom, cropped page corners, sideways final orientation, or unreadable text.
- Take three versions of every shot and inspect the smallest text for sharpness before moving on.

### First approval set — do these four before the full shoot

1. Front cover: one clean overhead image and one restrained 30-degree angle showing the physical book.
2. Mariana depth diagram plus Brave Learning questions: complete two-page spread, straight and fully readable. This is the strongest STEM/educational-value proof.
3. Submarine/Ocean Fact pressure page: clean single-page composition. This is the strongest story + illustration + science proof.
4. Courage page beginning “Everyone feels afraid sometimes”: clean single-page composition. This is the strongest emotional/character-building proof.

Andrew sends this four-image test set for visual approval before photographing the rest. The hospital scouting images established the page choices but are not website-ready because of mixed artificial light, hard shadows, visible hands, countertop distraction, perspective/cropping, and sideways orientation.

### Full Mariana asset set after the test is approved

- Whale/Ocean Fact page as a secondary learning image.
- One additional dramatic Mariana-specific adventure spread (deep-sea creature, descent, glowing discovery, submarine, or danger)—not the kiteboarding spread.
- Back cover.
- Angled cover/spine image.
- Paperback/hardcover comparison if both formats are available.
- Vertical iPhone flip-through: 4K/30, 15–20 seconds raw, steady overhead frame, consistent page turns, no narration/music/effects; target final edit 10–12 seconds.
- Two clean five-second “magic plates”: closed cover and strongest open spread, motionless and hand-free.

### Higgsfield and website guardrails

- Higgsfield may add only subtle surrounding atmosphere (underwater light, restrained bubbles, gentle depth/parallax). It must not redraw, rewrite, distort, replace, or animate the actual cover title, author name, characters, illustrations, or readable page text.
- After Andrew approves the final assets, optimize them for web and build the Mariana product gallery/flip-through on **staging first**. Verify desktop/mobile performance, image readability, layout stability, accessibility, and the full purchase path before requesting a separate production decision.
- Repeat the approved capture and implementation pattern for Everest and Amazon only after the Mariana treatment is accepted.

---

**No approved next engineering task (2026-07-31, newest — post-production-deploy).** Production and staging are level at 1.19.121. The deployment is complete and verified; nothing is pending from it.

Worth doing when the owner chooses:
1. **Watch production for a day.** Rollback stays available at `~/bhp-PROD-backup-1.19.121-20260731/` (one command). Nothing indicates a need.
2. **Decide on the audience-gateway removal.** 1.19.121 removed that homepage module as a competing routing surface; the component file is retained and unrendered, so restoring it is one line. It is a conversion-surface call, not a technical one.
3. **The WPConsent banner can still cover the quiz close button at narrow widths** while consent is unanswered — pre-existing and deliberate (consent must stay answerable); Escape and backdrop still dismiss. Worth its own scoped task if the owner wants the launcher to defer while the banner is up. See `KNOWN_ISSUES.md`.
4. **Still carried forward:** the quiz answers' left alignment reversed the 1.19.100 optical-centring work on the owner's direction — now live on production, worth an explicit yes/no. `/explorer-passport/` 404s (template exists, no page assigned).
5. **Not started, per instruction:** Founder/Amazon video work.
6. **Longer-standing, unchanged:** Rank Math SEO metadata across audience/product pages, internal-link anchor updates, and the Mailchimp design-system + 15-email restyle.

---

**Superseded (2026-07-31): awaiting owner review of staging 1.19.121.** Production remains 1.19.112; no production approval requested.

1. **Re-check the quiz on the actual iPhone.** This is required, not optional. The automation browser has no Safari toolbars, so `100dvh` resolves as `100vh` and the visual viewport never shrinks — **the exact condition that produced the bottom-sheet screenshots and the gear overlap cannot be reproduced here.** Parts B (centering) and E (consent gear) measure perfectly in emulation and are correct by layering, but only the real device closes them. Please open the quiz on the iPhone with the toolbars both shown and collapsed, and confirm: the dialog is centred (not stuck to the bottom), rounded on all four corners, and the gear no longer sits on Question 1's fourth answer or the result's lower controls.
2. **Confirm the seven screenshot-specific fixes** against the originals — caption below the covers, Q1 fourth answer fully visible, result submit visible, dialog centred, gear clear, no hamburger beside the desktop nav, larger desktop question→answer gap.
3. **Decide on the audience-gateway removal.** Part G removed that homepage module as a competing routing surface. The component file is retained and unrendered, so restoring it is one line — but it is a conversion-surface decision, not just a technical one.
4. **Note the short-viewport honesty:** at 320×568, 844×390 and 667×375 the submit is above the fold, but the two secondary links sit under exactly one internal scroll region. Reported as a scroll, deliberately not claimed as a no-scroll pass.
5. **Decide on production deployment.** Staging is now **five** releases ahead (1.19.117 → 1.19.121) and would ship as one package. Homepage Phase 1a is still unreviewed.
6. **Still carried forward:** the quiz answers' left alignment reversed the production-live 1.19.100 optical-centring work, on the owner's direction — worth an explicit yes/no. `/explorer-passport/` 404s on staging (no page assigned), pre-existing.
7. **Not started, per instruction:** Founder/Amazon video work.

---

**Superseded (2026-07-31): awaiting owner review of staging 1.19.120.** The homepage hero mobile reorder is complete and QA-passed. Production remains 1.19.112 and **no production approval was requested or granted.** In order:

1. **Review the homepage on a phone** (or a ~390px window): the order should read eyebrow → headline → three covers → supporting paragraph → both CTAs → "Big Places. Brave Hearts." Then widen past ~769px and confirm the covers return to the right-hand column exactly as before.
2. **Decide on 768×1024 tablet portrait.** It also receives the new order, because the hero is already a single column at ≤768px. The approved two-column composition exists only at ≥769px and is fully preserved there. If tablet should keep covers last, that is a one-line breakpoint change — but it would introduce a DOM/visual mismatch at that width, so it needs an explicit call.
3. **Note the second, pre-existing fix that rode along:** the ≤380px hero grid-track containment. It corrects genuine clipping at 320px (third cover, both CTAs and H1 were cut off with no scrollbar) but touches shared homepage hero CSS, so it deserves a look.
4. **Spot checks this environment cannot perform:** screenshots (tool times out), CSS `:hover`, and a real OS reduced-motion pass.
5. **Decide on production deployment.** Staging is now **four** releases ahead — 1.19.117 (Homepage Phase 1a), 1.19.118, 1.19.119, 1.19.120 — and they would ship as one package. Phase 1a is still unreviewed.
6. **Not started, per instruction:** the Founder/Amazon video work.
7. **Pre-existing, unrelated:** `/explorer-passport/` returns 404 on staging (template exists, no page assigned); and the WPConsent banner can cover the quiz close button at 320px while consent is unanswered (`KNOWN_ISSUES.md`).

Also still carried forward: the quiz answers' **left alignment reversed the production-live 1.19.100 optical-centring work** on the owner's direction — worth an explicit yes/no.

---

**Superseded (2026-07-31): awaiting owner review of staging 1.19.119.** The quiz question-screen fit correction is complete and QA-passed. Production remains 1.19.112 and **no production approval was requested or granted.** In order:

1. **Review the quiz on staging, deliberately at a short window.** Drag the browser to roughly half screen height on a blog post. Q1 should stay a 2×2 grid with all four answers, the progress label and the close button visible and **no scrollbar**; Q2 should show both top answers, the full-width third, and Back.
2. **Confirm the two stated limitations** (`RELEASES/QUIZ_QUESTION_FIT_1_19_119.md` §9): at 320×568 Q1's longest answer wraps to three lines in a single column, and 667×375 keeps one column because two were not necessary.
3. **Note the answer grid is now two columns on desktop again**, which is a visible change from 1.19.118's single column. Q1 reads as a 2×2 and Q2 as two-plus-a-full-width-third; the modal is also wider on question screens (780px) and unchanged (640px) on results.
4. **Four spot checks this environment cannot perform** (each a proven tool limitation): screenshots, CSS `:hover` painting, a real OS reduced-motion pass, and the partnership CTA actually landing on `#contact`.
5. **Decide on production deployment.** Staging is now **three** releases ahead — 1.19.117 (Homepage Phase 1a), 1.19.118, 1.19.119 — and they would ship as one package. The Homepage Phase 1a work is still unreviewed.
6. **Still open, pre-existing:** at 320px the WPConsent banner can cover the quiz close button while consent is unanswered (`KNOWN_ISSUES.md`). Automatic opening already defers for it; a manual open can still land underneath.

Also still carried forward from 1.19.118: the answers' **left alignment reversed the production-live 1.19.100 optical-centring work** on the owner's direction — worth an explicit yes/no.

---

**Superseded (2026-07-31): awaiting owner review of staging 1.19.118.** The quiz question-screen simplification is complete and QA-passed on staging. Production remains 1.19.112 and **no production approval was requested or granted.** What is needed next, in order:

1. **Review the quiz on staging.** The condition that most exercised the change is a **short** window — 320×568, or a phone in landscape — where the removed header previously consumed more than half the dialog. Open any blog post and use the footer launcher (or let it auto-open).
2. **Confirm two deliberate deviations**, both recorded in `RELEASES/QUIZ_QUESTION_SIMPLIFICATION_1_19_118.md` §12: (a) answers are **left-aligned again**, which reverses the optical-centring shipped in 1.19.100 and now live on production — this followed the current-turn brief, but it undoes a measured, production-live release, so it deserves an explicit yes/no; (b) the desktop **two-column** answer grid was dropped for a single column, because 22px labels wrapped the longest Question 1 answer to three lines in 291px.
3. **Decide on the 768×1024 tablet gap.** Type there interpolates between the brief's two tiers (question 29.2px vs a 30px desktop floor; answers 19.5 vs 20; controls 69.4 vs 72). Meeting both bands exactly would need a near-vertical ramp between 667px and 768px. Leave as-is, or accept a visible step at that boundary.
4. **Four spot checks this environment cannot perform** (each a proven tool limitation, not a site defect): screenshots (the tool times out), CSS `:hover` painting, a real OS reduced-motion pass, and the partnership CTA actually landing on `#contact`.
5. **Decide on production deployment.** Staging would ship as one package **1.19.117 + 1.19.118** — the Homepage Phase 1a work is also staged and unreviewed. Not approved and not requested.
6. **Pre-existing, not introduced here:** at 320px the WPConsent banner (`position: fixed`, z-index 900000, 320×308 from the top-left) covers the quiz's close button while consent is unanswered. Automatic opening already defers while it is painted; a manual open can still land underneath it. Logged in `KNOWN_ISSUES.md`, worth its own task.

---

**No approved next engineering task (2026-07-30, prior).** The 1.19.100 / 1.8.7 release is deployed to production and verified. Two items are explicitly parked pending a separate decision from Andrew: (1) the **Shop / WooCommerce product-consolidation project**, and (2) **cleanup of the 11 staging rollback/scratch files**. Longer-standing outstanding work is unchanged: Rank Math SEO metadata across audience/product pages, internal-link anchor updates, and the Mailchimp design-system + 15-email restyle.

Also still open from the quiz work: `/find-your-adventure/` has no internal inbound link anywhere (the modal link was the only one) — decide whether the footer resource cluster should carry it.

---

**RESOLVED — was blocked, Andrew decided 2026-07-30: Complete Collection hardcover default.** Decision: default to Hardcover and **keep** the existing $4.99 Hardcover shipping. Shipped in 1.19.99 and now live on production. Original analysis retained below for the record.

**Prior status — BLOCKED, needed Andrew's decision: Complete Collection hardcover default.** The brief asked for the $48.99 Hardcover collection to be the initial selection on `/complete-collection/` while stating "existing $3.99 shipping behavior remains unchanged" and "do not change shipping". **Those cannot both hold.** Verified live in a real staging cart: 3 hardcovers → subtotal $53.97, bundle discount −$4.98 = **$48.99**, **shipping $4.99**, tax $2.94, total **$56.92**. The $3.99 figure is the *paperback* collection's shipping; hardcover has always been $4.99 (`bhp_bundle_rules('hardcover')[3]['shipping']`, and the page's own fine print already advertises "$4.99 flat shipping" for hardcover). This is the deliberate tiered model ratified as BH-05, with the Shipping Policy page reading "$1.99 to $4.99".

Defaulting to hardcover therefore changes two customer-facing commercial values by default: **shipping $3.99 → $4.99** and **entry price $31.99 → $48.99** (+53%). Nothing was changed pending Andrew's answer. Options:
1. **Proceed anyway** — accept $4.99 default shipping and the higher default price (hardcover is the giftable, higher-margin edition). One-line change plus JS `?format=` support.
2. **Proceed and equalise shipping at $3.99 for both collections** — requires editing `bhp_bundle_rules()`, i.e. an actual shipping change, which the brief forbids without separate approval.
3. **Leave paperback as the default** and promote hardcover some other way (badge, ordering, copy).

[PARENT_COUPON_CODE_SUPERSEDED] is **not** a blocker — it qualifies for a complete collection in either format. Implementation is otherwise ready: server-rendered default in `bundle-landing-page.php` (cache-safe), with `?format=` honoured client-side in `bundle-landing.js`. **Note this is plugin code, not theme code** — it needs the separate plugin-deploy path, not the theme ZIP.

**Active (2026-07-30 — reconciliation complete): staging 1.19.96 is the single authoritative candidate; Andrew reviews, then decides on production.** The open `quiz-modal.js` working-tree question is **closed**: that edit was already integrated and deployed (identical across the working tree, deployed staging, the 1.19.96 ZIP and the 1.19.95 backup), all three of its logical changes are authorised — two from the 1.19.93 brief, one from background task `task_8f952193` that Andrew started, documented in auto-memory — it does not conflict with the 1.19.96 internal-scroll fix, and no process is still writing to it. **All 143 files of the intended source set match deployed staging exactly, so no version bump and no redeploy were needed.** Full regression re-run on the combined candidate: 5 viewports × 4 routes, all PASS. See `RELEASES/QUIZ_UX_PERSONALIZATION_1_19_93.md` § "Reconciliation".

Remaining before any production decision — items 1–3 need Andrew, item 4 is a standing caution:
1. **Review the quiz on staging**, ideally at a short window (~1024×420, or a phone in landscape) where the modal genuinely scrolls — that is the only condition under which the corrected defect could appear. Scroll to the third or fourth Question 1 answer, select it, and confirm Question 2 opens at the top.
2. **Four spot checks this environment cannot perform** (each proven a tool limitation, not a site defect): **screenshots** (the tool times out — none exist for any pass); the primary CTA's **hover/active** appearance (this browser does not resolve CSS `:hover`); an **end-to-end keyboard walk** (real `Tab` does not move focus here even with the modal closed); and the organization partnership CTA actually landing on `#contact`.
3. **Decide on production deployment.** Production is at 1.19.91; staging is three releases ahead (1.19.93 → 1.19.95 → 1.19.96) and they would ship as one package. Not approved and not requested.
4. **Never verify served assets on this host with `curl`** — SiteGround's edge security returns HTTP 202 and a ~292-byte challenge instead of the file. Use a real browser.

**Superseded (2026-07-30, after the third quiz pass): Andrew reviews the quiz on staging (theme 1.19.96), then decides on production.** All three passes recorded in `RELEASES/QUIZ_UX_PERSONALIZATION_1_19_93.md` are implemented and QA-passed on `staging2`. Needed from Andrew, in order:
1. **Review the quiz on staging** — ideally at a short window (roughly 1024×420 or a phone in landscape) where the modal genuinely scrolls, since that is the only condition under which the corrected defect could appear. Scroll down to the third or fourth Question 1 answer, select it, and confirm Question 2 opens at the top with the eyebrow, headline and intro copy fully visible.
2. **Four real-browser spot checks this environment cannot perform** (each proven a tool limitation by control test, not a site defect): (a) **screenshots** — the tool times out in every tab, so no images exist for any pass; (b) the primary CTA's **hover and active** appearance — this browser does not resolve CSS `:hover`; (c) an **end-to-end keyboard walk** — real `Tab` does not move focus here even with the modal closed; (d) the organization partnership CTA actually landing on `#contact`.
3. **Decide on production deployment.** Not approved and not requested. Production is at 1.19.91; staging is three releases ahead (1.19.93 → 1.19.95 → 1.19.96) and they would ship together.
4. **Coordinate with the separate background session editing `assets/js/quiz-modal.js`.** It was not modified in this pass; staging 1.19.96 ships checksum `9376b3e6cdfdda1a173c8aca3ec594ea`. If that work lands, rebuild and redeploy staging so the two changes are validated together before any production decision.
5. **Still open from earlier passes:** `/find-your-adventure/` has no internal inbound link since the modal's redundant link was removed — decide whether the footer resource cluster should carry it. Author-visit intent still has no quiz destination and needs its own page before it can be routed to.

**Superseded (2026-07-29, after the second quiz pass): Andrew reviews the refined quiz on staging (theme 1.19.95), then decides on production.** Everything in `RELEASES/QUIZ_UX_PERSONALIZATION_1_19_93.md` (both passes) is implemented and QA-passed on `staging2`. Needed from Andrew, in order:
1. **Review the quiz on staging** — the sitewide modal on any blog post, the homepage inline quiz, and `/find-your-adventure/`. Copy, the per-answer results, the CTA labels and the compacted modal are the things to judge; routing, scroll restoration and layout fit are already measured.
2. **Three real-browser spot checks this environment cannot perform** (each proven to be a tool limitation via control tests, not a site defect): (a) the primary CTA's **hover and active** appearance — this browser does not resolve CSS `:hover` at all; (b) an end-to-end **keyboard walk** of the modal — real `Tab` does not move focus in this browser even with the modal closed; (c) the organization partnership CTA actually landing on `#contact`.
3. **Decide on production deployment.** Not approved and not requested.
4. **Still open from the first pass:** `/find-your-adventure/` has no internal inbound link since the modal's redundant link was removed — decide whether the footer resource cluster should carry it. Author-visit intent still has no quiz destination and needs its own page before it can be routed to.
5. **Documentation conflict now recorded, worth Andrew's awareness:** `DECISIONS.md` and `FUNNEL_CONSTITUTION.md` said the quiz "must not be built" — stale since 2026-07-20. Dated reconciliation notes were added; no frozen policy was changed. If Andrew wants the frozen sections themselves amended, that needs his explicit approval under the Amendment Process.

**Superseded (2026-07-29, first pass): Andrew reviews the reworked Find Your Adventure quiz on staging (theme 1.19.93), then decides on production.** Everything in `RELEASES/QUIZ_UX_PERSONALIZATION_1_19_93.md` is implemented and QA-passed on `staging2`. What needs Andrew, in order:
1. **Review the quiz on staging** — the sitewide modal on any blog post, the homepage inline quiz, and `/find-your-adventure/`. Copy, per-answer results, and the result-screen actions are the things to judge; the routing and scroll behavior are already measured.
2. **Two real-browser spot checks this environment could not do:** (a) clicking the organization "Explore Group Orders & Partnerships" CTA actually lands on the `#contact` section of `/organizations-community-reading-kit/` — this automation browser never scrolls to a hash target, on any page, including pre-existing anchors; (b) the quiz with the OS "reduce motion" preference on.
3. **Decide on production deployment.** Not approved and not requested — the repo requires a specific, current-turn approval, and this release does not have one.
4. **Decide whether `/find-your-adventure/` should be linked from somewhere.** Removing the modal's "Open the full quiz page" link (redundant, per the brief) left the canonical page with no internal inbound link. The footer "Resources for Every Reader" cluster is the natural home if it should have one.
5. **Author-visit intent has no destination.** The "Author visit information" answer was removed rather than left pointing at the Adventure Learning Toolkit. If author visits are a real offer, they need their own page/section before the quiz can route to them — a separate, unapproved task.
6. **Pre-existing focus-trap leak** (Tab from the modal escapes to WPConsent's container, reproduces on production 1.19.91) is logged as its own task — not part of this release.


**Active (2026-07-16, launch build, newest): Single blocker is the SiteGround staging document-root path.** All local landing-page/SEO/funnel/accessibility work for this pass is complete and committed — see `docs/ENGINEERING/LANDING_PAGE_LAUNCH_MANIFEST.md` for the full execution checklist. Once Andrew supplies the verified staging path (or deploys the ZIP himself):
1. Deploy the current theme build to staging, verify no PHP fatals.
2. Run the 9-breakpoint visual QA across all 5 audience landing pages.
3. Run cart/checkout regression (6 core products + 2 Complete Collection SKUs).
4. Live-test Gift Buyer/Retailer/Organization signup forms end to end (Parent/Educator already tested in prior sessions).
5. Separately, as a private pre-launch task (not blocked on deployment): rotate the compromised coupon codes (`[PARENT_COUPON_CODE]` / `[GIFT_BUYER_COUPON_CODE]` / `[EDUCATOR_COUPON_CODE]` — real values only in the private, gitignored, untracked `docs-private/MAILCHIMP_INTERNAL_REFERENCE.md`) — see that file's rotation plan.
6. Community Reading Kit (Organization audience) remains Awaiting Asset — funnel is not launch-ready for that lead magnet until the PDF exists (see manifest section 5).

**Active (2026-07-16, overnight sprint, prior): Andrew completes the manual Mailchimp visual pass (build plan provided), then this session's remaining reliable work — SEO metadata, internal links, 9-breakpoint QA — resumes; production deploy stays parked.** Mailchimp visual/structural editing (image upload, button-label editing, arbitrary hyperlinking, block deletion near other elements, footer-logo toggling) is now confirmed unreliable through this browser-automation path after repeated, varied attempts — see `CURRENT_TASK.md`'s newest entry and `ENGINEERING/MAILCHIMP_MANUAL_COMPLETION_REGISTER.md`. Andrew's next action:
1. Resolve Parent Email 1's two-CTA state first (see the register — either hyperlink the correct text and remove the mislabeled button, or relabel the button and remove the text line; do not leave both).
2. Follow `ENGINEERING/MAILCHIMP_EDUCATOR1_MANUAL_BUILD_PLAN.md` to finish Educator Email 1 (hero image, styled CTA button, footer duplicate-logo removal, alt text) — roughly 15-20 minutes.
3. Once approved, follow `ENGINEERING/MAILCHIMP_TEMPLATE_REUSE_PLAN.md` to propagate the same structure to the other 14 emails, prioritizing the 9 emails that are not `Resource Blocked` (Educator 2-3, Parent 2-3, Retailer 3, Organization 3, Gift Buyer 3) before the 6 that need a lead-magnet asset to exist first.

This session's remaining reliable, non-Mailchimp work — still not reached, in this order:
1. Rank Math SEO title/meta-description alignment across the homepage, shop page, Complete Collection, and the 5 audience landing pages.
2. Internal-link anchor updates on a handful of existing high-value blog posts/CTAs.
3. Full staging site QA at the 9 standard breakpoints (320–1440px) for the Adventure Books terminology/nav changes.
4. Verify the Educator funnel and checkout have not regressed from any of this session's changes.
5. **Production deployment of the terminology/Mailchimp scope is explicitly parked** — ask Andrew again immediately before that step, after the above is complete.

**Superseded (2026-07-16, overnight sprint, prior): Finish staging site QA at 9 breakpoints, then SEO metadata + internal links; production deploy of the terminology/Mailchimp scope stays parked pending Andrew's specific go-ahead.** The full Mailchimp "Minimal Branded Editorial" design system is now built and applied to all 15 Draft emails across all 5 audiences (Educator/Parent/Gift Buyer/Retailer/Organization × 3), every email's persistence and content verified, and journey safety (Draft status, triggers, delays, `Customer - Purchased` If/Else suppression) re-verified unchanged across all 5 automations — see `CURRENT_TASK.md`'s latest entry and `CHANGELOG.md`. Still outstanding from the same overnight directive, in roughly this order:
1. Full staging site QA at the 9 standard breakpoints (320–1440px) for the Adventure Books terminology/nav changes from earlier in this same directive (Phase 1) — not yet run this round.
2. Rank Math SEO title/meta-description alignment across the homepage, shop page, Complete Collection, and the 5 audience landing pages (not started — the existing per-post Rank Math fields are empty/inherit-default, so this needs a considered per-page pass, not a blind bulk WP-CLI set).
3. Internal-link anchor updates on a handful of existing high-value blog posts/CTAs (generic "Books"/"Learn More" anchors → natural varied anchors) — not started.
4. **Production deployment of the terminology/Mailchimp-design-system scope is explicitly parked** — the mega-directive's blanket authorization does not substitute for the repo's own rule that production needs a specific, current-turn approval; ask Andrew again immediately before that step, after the remaining staging QA above is complete.

One known, accepted cosmetic limitation carried forward: Parent Email 1's CTA button text ("Download Your Free Chapter") could not be changed to match the directive's exact suggested wording ("Download the Reluctant Reader Adventure Kit") — 6+ distinct editing techniques all failed to alter the text despite the button being visually selectable; its underlying link was independently verified correct (points to the real PDF). Not a functional defect, just a wording deviation — revisit only if Mailchimp's editor behavior changes or Andrew wants a different approach tried.

**Superseded (2026-07-16, overnight sprint, prior): Continue the Adventure Books rollout — SEO metadata, internal links, then the Mailchimp design system across all 15 Draft emails; production deploy of the terminology scope stays parked pending Andrew's specific go-ahead.** Phase 1 (terminology audit, nav stacking, homepage/shop CTA copy) is live on staging and verified — see `CURRENT_TASK.md`. The Mailchimp design-system item (#3 below) is now done — see the active entry above.
1. Rank Math SEO title/meta-description alignment across the homepage, shop page, Complete Collection, and the 5 audience landing pages (not started — the existing per-post Rank Math fields are empty/inherit-default, so this needs a considered per-page pass, not a blind bulk WP-CLI set).
2. Internal-link anchor updates on a handful of existing high-value blog posts/CTAs (generic "Books"/"Learn More" anchors → natural varied anchors) — not started.
3. Build the "Brave Hearts — Minimal Branded Editorial" Mailchimp design system (single-column, warm-white, real brand accent color, one dominant CTA, founder sign-off) and apply it to Educator's 3 emails first as the reference sequence, then Parent, Gift Buyer, Retailer, Organization (15 emails total) — not started. Expect this to be the most time-consuming remaining piece given Mailchimp's UI fragility (screenshot timeouts, need for accessibility-tree-based navigation rather than pixel clicks, as encountered repeatedly this session).
4. Full email persistence/QA pass (save→close→reload→reopen on all 15) and staging site QA at the 9 standard breakpoints for the terminology changes.
5. **Production deployment of the terminology/SEO scope is explicitly parked** — the mega-directive's blanket authorization does not substitute for the repo's own rule that production needs a specific, current-turn approval; ask Andrew again immediately before that step, after staging QA is complete.

**Superseded (2026-07-16): Andrew's next decisions — (1) approve or revise the now-live Educator landing page; (2) decide how to handle Email 2's now-outdated "still finishing" copy; (3) same PDF-and-Mailchimp sequence for Gift Buyer, Retailer, Organization.** The Educator toolkit is fully delivered on staging (see `CURRENT_TASK.md`'s newest entry, `CHANGELOG.md`, `MAILCHIMP_MANUAL_COMPLETION_REGISTER.md`): real PDF uploaded, real signup form live, Email 1 rewritten for delivery, a controlled test proved the signup pipeline end to end. Two things now need Andrew (or ChatGPT, per the content-operations copy-ownership rules) rather than further unilateral engineering work: (a) approve or revise the Educator landing page itself — still next in the one-page-at-a-time approval order, now showing its real, working form instead of "Coming Soon"; (b) Email 2's "we're still finishing the toolkit, give us your input" copy now contradicts Email 1's "it's ready" messaging — needs new copy reflecting a delivered toolkit, not a rewrite performed unilaterally this session. Once those are resolved, the same three-step sequence (produce the real PDF/guide → wire it into Mailchimp Email 1 → activate the real form) still needs to happen for Gift Buyer, Retailer, and Organization, in that order — none of their assets exist yet.

**Superseded (2026-07-16): Andrew reviews Sprint A changes on staging, then the mandated sequence resumes: approve or revise the Educator page, produce the Educator PDF, finalize and Email-1-test the Educator Mailchimp journey, then move through Gift Buyer, Retailer, and Organization in that order.** Sprint A (the private CSO audit's critical-fix sprint) is implemented and staging-verified — see `CURRENT_TASK.md` and `CHANGELOG.md` for the full list (classroom-claim correction sitewide, audience-specific trust bars, Gift Buyer testimonial swap, Retailer/Organization trust sections, Parent author photo, hardcover rationale, stale-doc correction). Nothing in Sprint A touched Mailchimp, PDFs, or coupons — those remain exactly where the prior entry below left them. Once Andrew reviews and approves the staging changes: (1) approve or revise the Educator landing page (still next in the one-page-at-a-time approval order); (2) produce the Educator PDF (the largest remaining blocker for that audience's Mailchimp Email 1); (3) review/finalize the Educator Mailchimp journey with the real PDF URL inserted; (4) run a real Email 1 delivery test; (5) repeat for Gift Buyer, Retailer, Organization in that order.

**Superseded (2026-07-16): the smallest remaining step is an explicitly-authorized single-journey activation test (branch-execution proof); otherwise, Andrew's Mailchimp review and creation of the four missing lead-magnet PDFs per the CSO directive's own branching instruction.** All 4 Educators Mailchimp gaps are now fixed (If/Else, Email 3, Email 1, Email 2). Purchase scope is Frozen (any purchase suppresses). A controlled, Andrew-authorized staging test proved automatic purchaser-tagging (`Global - Tag Purchasers`, live Flow Data 1/0/0/1) and confirmed Educators' If/Else condition reads the identical tag — both PROVEN. Branch execution (a tagged contact actually routing through a live journey's Yes-branch) remains NOT proven — proving it requires activating a Draft journey, which stays prohibited absent new, explicit, current-turn authorization. Cancellation-does-not-remove-tag is now a confirmed fact (tested, not assumed) — still needs Andrew's policy decision on whether refunded/cancelled purchases should have the tag removed (see `FUNNEL_CONSTITUTION.md`'s Post-Purchase Target State section). Per the directive's own branching instruction: since tagging and condition-configuration are both proven, the next phase is Andrew's Mailchimp review and creation of the four missing lead-magnet PDFs (Educators/Gift Buyer/Retailer/Organization) — every journey stays Draft in the meantime.

**Superseded (2026-07-15): Andrew authorizes one of two end-to-end suppression-test unblocking options; then supply Educators Email 1/2 copy; then activation review.** The two Educators-journey gaps named in the entry below are now fixed and reload-verified (If/Else condition, Email 3 Subject/Preview Text). Reverification surfaced 2 new Educators-only gaps — Email 1 and Email 2 Subject/Preview Text are unset (bodies correctly built) — left unfixed since only Email 3's exact wording was pre-authorized; needs Andrew to supply/approve copy for Email 1 and Email 2 before those can be set. The end-to-end purchaser-suppression test (never performed for any journey) was assessed this session as **not currently safely performable**: no dedicated non-admin test WordPress account exists, no test-payment method is authorized, and admin-placed test orders are confirmed excluded from the Mailchimp sync. Two unblocking options, either sufficient: (1) Andrew provides a non-admin test WordPress account plus an authorized test-payment method (a real zero/low-cost order through the live checkout), or (2) Andrew explicitly authorizes a WP-CLI-created order manually set to `Processing` status for a non-admin test contact (bypassing checkout but exercising the same Mailchimp sync path). Until one of these is authorized and executed, Email 3 suppression remains unproven end-to-end for every journey and **no journey should be activated**. Also still needs Andrew: confirm whether the purchase-tagging scope ("any product purchase" vs. Collection-only) is the intended permanent rule — currently observed live behavior is "any purchase," not ratified in any canonical doc. Separately, a full post-purchase automation technical gap specification was written this session (see `ENGINEERING/MAILCHIMP_MANUAL_COMPLETION_REGISTER.md`) with several sub-decisions flagged for Andrew (follow-up timing, review-request wording/platform, refund/cancellation handling, audience-attribution scheme) — do not build until those are resolved.

**Superseded (2026-07-15): Fix the two confirmed Educators-journey gaps, then run one end-to-end purchaser-suppression test before activating any journey.** Parent's Email 3 is now built and verified (see `CURRENT_TASK.md`). The full 5-journey re-verification this session found the Educators journey (id 90) genuinely broken in two ways: (1) its If/Else purchaser-suppression condition is unconfigured — open the node, select Tags → contact is tagged → Customer - Purchased from the dropdown suggestion, click "Use segment," reopen to confirm the label renders; (2) its Email 3 Subject/Preview Text was never set — open the Send Email node, click "Set Subject & Preview Text," enter subject/preview copy consistent with the [EDUCATOR_COUPON_CODE_SUPERSEDED] body that's already correctly built, save, close, reload to confirm persistence. After that, before activating **any** of the 5 journeys, run one non-production test-contact through trigger → Email 1 → a real (or dedicated non-admin) purchase → confirm Email 3 is actually suppressed — this has never been done for any journey and the If/Else condition being configured in the UI is not sufficient proof it works in practice. Then continue down the remaining per-audience checklist in `ENGINEERING/MAILCHIMP_MANUAL_COMPLETION_REGISTER.md`: replace placeholder lead-magnet/guide/packet URLs, get Andrew's production approval for the 4 staging-only landing pages, decide on [GIFT_BUYER_COUPON_CODE_SUPERSEDED]/[EDUCATOR_COUPON_CODE_SUPERSEDED] publication, migrate the 3 real contacts out of the old paused `Coupon Flow` (id 86), and eventually build post-purchase follow-up automations (confirmed not built for any audience). The Mailchimp-vs-HubSpot architecture question is resolved (Mailchimp owns acquisition/nurture for all five audiences) — do not reopen it.

**Superseded (2026-07-15): Build Parent's Email 3 ([PARENT_COUPON_CODE_SUPERSEDED]) on the non-purchaser branch, then work through the per-audience gap list in `ENGINEERING/MAILCHIMP_MANUAL_COMPLETION_REGISTER.md`.** The persistence-bug item below is resolved — it did not reproduce across Gift Buyer, Retailer, Organization, or a retroactive fix to Parent's own If/Else condition this session, when fields were populated via dropdown-suggestion selection and saved/closed/reopened-to-confirm carefully. All 5 audience journeys (Parent id 89, Educators, Gift Buyer, Retailer id 92, Organization id 93) are now Draft with a full Trigger → Email 1 → delay → Email 2 → delay → If/Else structure, except Parent's Email 3 which is still missing. Remaining work, in priority order: (1) build Parent's Email 3 on the non-purchaser branch, following the same pattern used for Gift Buyer/Retailer/Organization this session; (2) migrate the 3 real contacts mid-delay in the old paused `Coupon Flow` (id 86) into the new Parent journey, then retire the old flow; (3) work the per-audience checklist in `MAILCHIMP_MANUAL_COMPLETION_REGISTER.md` — every non-Parent journey needs its placeholder lead-magnet/guide/packet URL replaced with a real asset link before activation, and all 4 new landing pages need Andrew's production approval (still staging-only); (4) resolve the open HubSpot-vs-Mailchimp architecture question for Retailers/Organizations before treating Mailchimp as their permanent home. **Do not re-litigate the persistence bug as a platform limitation** — see the register doc's "Persistence finding" section for the corrected understanding and the exact working technique.

**Superseded (2026-07-15): Resolve the two Mailchimp UI persistence bugs before continuing the Parent Acquisition Funnel or starting any other audience's journey.** `Parent - Acquisition Funnel` (id 89) now has Trigger → Email 1 → 2-day delay → Email 2 (body + link done) → 1-week delay → If/Else (node present, condition unconfigured). Two fields silently fail to save despite the UI accepting input with no error (see `MAILCHIMP_STATUS.md`'s "UI notes" section and `KNOWN_ISSUES.md` for exact repro steps): Email 2's Subject ("How did the Chapter 7 activity go?") / Preview Text ("One activity can open the door to a much bigger reading adventure.") and the If/Else condition (Tags → contact is tagged → Customer - Purchased). Try a fresh session or Andrew's own direct keyboard input before assuming this is permanent; if it recurs, it may need a Mailchimp support ticket. Once resolved: wire the If/Else's Yes-branch (purchased) to exit toward a post-purchase journey (not yet built) and the No-branch to Email 3 ([PARENT_COUPON_CODE_SUPERSEDED], not yet built). Do not start Educators/Gift Buyer/Retailer/Organization Mailchimp builds until this is resolved, since the same purchaser-suppression pattern would hit the identical bug in every audience's journey.

**Still active (2026-07-15): Build the Mailchimp automation/email-sequence for one audience (Educators recommended, next in the approval-order sequence) — the largest remaining body of work across all 5 audience funnels, blocked on the persistence bug above for the purchaser-suppression step specifically.** The Round 5 inventory pass confirmed only Parent has any Mailchimp automation progress; Educators, Gift Buyers, Retailers, and Organizations have none. Each needs: an entry trigger tag, a 3-email sequence (Email 1 resource delivery, Email 2 audience value + full-price Complete Collection, Email 3 reinforcement + coupon for Educators/Gift Buyers only — Retailers/Organizations are inquiry-led, no coupon), and purchaser suppression before Email 3. This requires a live authenticated Mailchimp session and was explicitly not attempted in the inventory round (Round 5) since it's the single largest scope item and the directive prohibits building "incomplete/unsafe" journeys — do the full 3-email build for one audience at a time, not shells across all 4. `[EDUCATOR_COUPON_CODE_SUPERSEDED]` (post 592) and `[GIFT_BUYER_COUPON_CODE_SUPERSEDED]` (post 593) already exist as `draft`-status WooCommerce coupons on staging, ready to pair with Email 3 once each sequence is built — publish only once the paired sequence is ready. This does not change Educators' position in the mandated one-page-at-a-time approval order for the landing pages themselves (see below) — Mailchimp-build work is a separate, parallel track from Andrew's page-by-page visual approval.

**Still active (2026-07-15): Andrew reviews and explicitly approves Educators — still next in the mandated approval order for the landing pages.** The Gift Buyer page received a content-gap-closure pass (2 occasion categories, 1 FAQ item) and full QA, but this does not change Educators' position in the approval queue — the permanent one-page-at-a-time rule still requires Educators' explicit approval first, including a decision on the outstanding logged-out-capture gap (see below). Theme v1.19.36 on staging, code committed (`81c7e33`). Gift Buyer's own remaining items once its turn comes: no lead-magnet PDF set yet, no `[GIFT_BUYER_COUPON_CODE_SUPERSEDED]` coupon created yet, no Mailchimp automation built yet (each needs direct engineering-adjacent action: PDF sourcing/upload, coupon creation, and an authenticated Mailchimp session respectively).

**Superseded active item (2026-07-15): earlier version of the item above, before the Gift Buyer page content update.** Andrew reviews and explicitly approves Educators, including the outstanding logged-out-capture gap — one page at a time, per the now-permanent approval rule. The Educator-review directive (Round 3) is complete: Retailer's 3-card grid corrected (real fix, not the earlier "technically tidy" one), an Educator-specific toolkit-preview module added, full 9-breakpoint + functional QA passed, Parent regression-checked clean. Theme v1.19.35 on staging, code committed (`3607201`). **Genuinely logged-out visual captures are still not delivered** — the sandboxed screenshot tool failed again and the alternative (Andrew's real Chrome) can't satisfy the requirement either since that session carries active wp-admin auth. Andrew needs to decide: supply an incognito capture himself, or explicitly authorize a specific alternative. **No page is approved yet.** Mandated order going forward: Educators (awaiting Andrew's explicit approval now) → Gift Buyers → Bookstores/Retailers → Organizations → final Parent regression review → cross-page staging QA → assets → Mailchimp connections → coordinated production deployment. Do not batch-declare pages complete again (`DECISIONS.md`). Once every page is individually approved, the remaining superseded items below still apply: finalize real imagery/video assets, complete the 4 new Mailchimp automations, run full cross-funnel staging QA, prepare one coordinated production-deployment plan.

**Superseded active item (2026-07-15): earlier version of the item above, before the Round 3 Educator-review directive.** The Audience Landing-Page System's P0 defect (sections stuck invisible) and a follow-up shared-layout refinement sprint (grid/hero-specificity/spacing/lead-magnet-honesty/collection/trust/sticky-bar fixes) were complete and verified live on staging (theme v1.19.34) — see `ENGINEERING/AUDIENCE_LANDING_STATUS.md`.

**Superseded active item (2026-07-15): Finalize real imagery and video assets, complete the 4 new Mailchimp automations, run full cross-funnel staging QA, and prepare one coordinated production-deployment plan.** Set by the Audience Landing-Page System sprint (`CURRENT_TASK.md`'s 2026-07-15 entry, `ENGINEERING/AUDIENCE_LANDING_STATUS.md`, `ENGINEERING/AUDIENCE_LANDING_ASSET_MANIFEST.md`). Breaks down as: (1) Andrew sources the missing Parent-page author photo/video plus any optional imagery for the 4 new pages per the asset manifest; (2) Andrew/ChatGPT build the 4 new Mailchimp automations (Adventure Learning Toolkit, Meaningful Gift Guide, Wholesale Guide, Community Reading Kit) keyed off the tags already applied in code, then set each PDF under Settings → Lead Magnets, which will flip each page's lead-magnet CTA from "Coming Soon" to live automatically — no further code change needed for that step; (3) a full 9-breakpoint (320/360/375/390/430/768/1024/1280/1440) QA sweep across all 5 audience pages, plus live GTM/GA4 event verification for the 4 new `*_landing_view` events — neither was completed in the build sprint, only 375px/1280px spot-checks; (4) once all 5 pages and their funnels are staging-verified together, prepare one coordinated production-deployment plan (do not deploy piecemeal). Also still outstanding, paused mid-build before this sprint: the Parent Funnel Mailchimp automation build itself (Time Delay node after Email 1) — see the item below, unaffected by this sprint.

**Resolved (2026-07-14): P0 mobile header regression — nav hamburger + mobile CTA fixed and deployed to production.** A real mobile device showed the header wordmark overflowing and the hamburger fully missing. Root cause and fix, staging/production deployment record, and QA detail: `PROJECT_STATE.md`'s "P0 mobile header fix" entry, `CURRENT_TASK.md`, commit `277bd8a`. Theme is now v1.19.20 on both environments. No further action needed on this — resume the item below, exactly where it was paused before this interrupt.

**Resolved (2026-07-14): P0 production correction — public [PARENT_COUPON_CODE_SUPERSEDED] advertising removed.** The Complete Collection page publicly advertised an [PARENT_COUPON_CODE_SUPERSEDED] coupon code — fixed and deployed to production (see `DECISIONS.md`'s "Audience Coupon Policy" entry, `ENGINEERING/FUNNEL_CONSTITUTION.md`). No further action needed on this — the fix is live and verified. Resume the item below.

**Continue building the consolidated `Parent - Acquisition Funnel` (2026-07-14).** Mailchimp is now on Standard Annual (verified live), unlocking the intended single-journey architecture. Draft automation `Parent - Acquisition Funnel` (id 89): trigger is correctly configured and saved (tag added: Reluctant Reader Adventure Kit — a prior-session bug where this was silently unsaved has been found and fixed), and **Email 1 is fully built** (PDF-delivery body, subject, preview text, working download button). Remaining, in order:
1. **Delay step, then Email 2** (Andrew's approved "How did Chapter 7 go with your kiddo?" result-focused copy — see `ENGINEERING/PARENT_FUNNEL_STATUS.md`). **Blocked this session by a new tooling issue**: dragging any step onto the "+" node between the already-built Email 1 and "Contact exits" did not register, across 10+ attempts/techniques including a controlled test with a known-working draggable item. Try a fresh session/different technique (e.g. a different browser zoom level, or check whether Mailchimp added a native "insert step" click affordance since this was last tried) before assuming it's still broken.
2. Purchase-sync delay/buffer
3. Conditional Split (`Customer - Purchased` tag OR current ecommerce purchase activity) — verify branch direction twice before activating, a reversed condition sends the coupon to a buyer
4. Email 3 ([PARENT_COUPON_CODE_SUPERSEDED], non-buyer branch only)
5. Test with approved test contacts (purchaser path exits without coupon, non-buyer path reaches Email 3) before activating
6. Migrate the 3 real contacts currently protected in the paused live `Coupon Flow` (privately verified, purchasers suppressed, non-buyers safely transitioned) — do not put anyone in both the old and new flow
7. Cut over: stop new entries to the old `Reluctant Reader Adventure Kit` + `Coupon Flow` split flows, activate the new journey, retire the old ones once their remaining contacts finish
8. Build the two 45-day post-purchase automations (single-book, Complete Collection)
9. Re-verify [PARENT_COUPON_CODE_SUPERSEDED] coupon config in WooCommerce (not re-checked this session)
10. Verify landing page/popup routing into the new journey and document analytics/attribution events
11. Full canonical-memory update reflecting the finished, tested, activated state

**Do not repeat:** the plan-tier investigation (Standard is verified active), the purchaser-tagger build (`Global - Tag Purchasers` is Active and correct), the drag-and-drop-vs-click root-cause (documented), the Audience Routing Constitution (written, permanent, see `DECISIONS.md`/`ENGINEERING/FUNNEL_CONSTITUTION.md`), or Email 1 (built and verified via live preview — reproduce/adjust copy only if Andrew requests a change, don't rebuild from scratch).

**Live state to protect:** `Coupon Flow` is deliberately Paused — do not resume it unprotected. `Reluctant Reader Adventure Kit` and `MT Lead Magnet` remain Active and untouched. `Parent - Acquisition Funnel` (id 89) is Draft with trigger + Email 1 populated — safe to resume editing directly.

Once Sprint 1B fully passes (Parent Funnel complete, tested, documented), the next sprint is: build and production-validate the Teacher/Librarian/Homeschool Funnel by reusing the Parent Funnel architecture (landing page, popup, Adventure Learning Toolkit lead magnet, result email, [EDUCATOR_COUPON_CODE_SUPERSEDED] offer logic, purchase suppression, post-purchase nurture, attribution, KPIs) — per this sprint's own explicit "exact next sprint" instruction.

**Resolved (2026-07-13): print-on-demand stock policy.** Andrew established that Brave Hearts is print-on-demand with no physical inventory — "out of stock" is not an inventory-control mechanism for the 6 core products, only for a verified fulfillment failure or explicit sales suspension. All 3 hardcover products restored to `instock`, confirmed live. See `DECISIONS.md`'s "Print-on-demand stock policy" entry.

**Resolved (2026-07-13): legacy catalog cleanup.** Draft product 338 (empty, broken shell, zero sales) permanently deleted after Andrew's explicit confirmation. Draft product 12 (genuine former Lulu product, 3 real sales) confirmed correctly archived, left untouched. See `DECISIONS.md`.

**Resolved (2026-07-13):** the 4 malformed blog-post Amazon links (posts 38, 64, 88, 90) are fixed and verified on production. See `CONTENT/LEGACY_BLOG_CONVERSION_AUDIT.md`.

**No blocking open items remain from the Conversion QA Sprint 1 / Hardcover Fulfillment Verification workstream.**

**Continue with the remaining legacy-blog batches** (the original Batches 1/3/4 plus the sitewide P0–P3 mechanical-fix queue, 35 of 36 posts flagged), each needing the same audit-then-copy-then-staging discipline as Sprint 1/Batch 2 — confirm with Andrew which batch is next. Followed by the educator outreach funnel build.

**Resolved (2026-07-13): Conversion QA Sprint 1** — full live funnel validation across production, findings-only (no fixes applied, per explicit instruction). See `ENGINEERING/CONVERSION_QA_SPRINT1.md` for the complete ranked findings list (1 P0, 2 P1, 3 P2, 2 P3).

**Resolved (2026-07-13):** Legacy Blog Conversion Batch 2 (posts 26, 66, 30) is now **fully production-complete** — both the approved topic-hub copy and the earlier mechanical fixes (Amazon→direct book-link URL swaps, post 66's split-anchor repair) are live on production, byte-diff-verified and QA-passed with zero regressions. See `CONTENT/LEGACY_BLOG_CONVERSION_AUDIT.md`'s "Batch 2 mechanical production closeout" section and `CURRENT_TASK.md`.

**Engineering candidate, not urgent (found 2026-07-13, Batch 2 work):** `BHP_CTA_Collision_Detector`'s `TOPIC_HUB_URL_PATTERNS` has never recognized `/teachers/#...` anchor-hash hub URLs — the actual format used by every topic-hub link this project has ever added — so its automated `required_links` gate has likely under-reported topic-hub compliance corpus-wide. Also found: its anchor-extraction regex requires `href` to be the first attribute on an `<a>` tag, silently missing links where `target` comes first. Not fixed (deliberately out of scope, doesn't block publishing — manual verification is the established workaround) — see `KNOWN_ISSUES.md` and `OPEN_QUESTIONS.md`; worth its own isolated engineering sprint alongside the existing `<p>`-tag-only-scan / Amazon-`tag=`-parameter gap.

**Resolved (2026-07-13):** "Printed Just for You" copy revision is deployed to production and QA-passed — see `ENGINEERING/PRINTED_FOR_YOU_STATUS.md` and `CURRENT_TASK.md`. The one deliberately-deferred item is **order-email copy** (documented as a recommendation only in `ENGINEERING/PRINTED_FOR_YOU_STATUS.md`'s "Email recommendation" section) — needs its own scoped task if Andrew wants to pursue it; not started.

Set 2026-07-13 (overnight build, legacy-blog items). **Sprint 1 (posts 76 & 68) is fully closed** — deployed to production, live-verified, cache purged. See `CURRENT_TASK.md` and `CONTENT/LEGACY_BLOG_CONVERSION_AUDIT.md`'s "Sprint 1" section.

**Also awaiting Andrew:** the day's documentation work now sits in 3 sanitized, unpushed local commits (`bd9be30`, `03a30b1`, `64ad4fb`) — a prior single-commit version was correctly not pushed after Claude Code's own safety classifier flagged committed WordPress backups and private-strategy-adjacent language; both were fixed (backups moved to `C:\BHP\private-backups\`, language replaced with public-safe wording) before recommitting. Andrew should review and push via GitHub Desktop, same as the existing credential-prompt workaround below.

**Batch 2 (posts 26, 66, 30) — fully production-complete (2026-07-13):**
Both Andrew's approved copy for the 3 topic-hub sentences/post 66's book-title link swap, and the earlier staging-tested mechanical fixes (Mariana Trench Amazon→direct book-link URL swaps on posts 26/30, the malformed split-anchor repair on post 66's "Related Reading" list) are now deployed to production. See `CONTENT/LEGACY_BLOG_CONVERSION_AUDIT.md`'s "Batch 2 copy implementation" and "Batch 2 mechanical production closeout" sections. Two real, pre-existing cannibalization questions surfaced during the original audit remain open (Mariana Trench content cluster affecting post 66/74/94; the much larger bridge-books cluster affecting post 30) — see `OPEN_QUESTIONS.md`; did not block this work, since the approved copy was deliberately worded narrowly to avoid adding to the overlap.

**Sitewide mechanical-fix queue (new, 2026-07-13 overnight):** a read-only scan across all 36 published posts found at least one flagged pattern in 35 of them — see the audit doc's "Sitewide mechanical-defect scan" section for the full P0–P3 prioritized queue. Confirm with Andrew which priority tier to tackle next (P0's split-anchor and Amazon-tag findings are good candidates — real defects, not just policy gaps).

**Still open, not yet resolved (see `OPEN_QUESTIONS.md`):** the Amazon-Associates-tag gap is now confirmed **corpus-wide** (16+ posts share the same no-`tag=` pattern), not isolated to one post — needs Andrew to check the Associates dashboard before any link is touched. The `BHP_CTA_Collision_Detector` code-vs-policy gap (only scans `<p>` tags, requires literal `tag=` for Amazon-affiliate matching) also remains unfixed.

**After Batch 2's production deploy:** continue with the remaining legacy-blog batches already scoped in the 2026-07-13 overnight audit (the original Batches 1/3/4 plus the new sitewide P0–P3 queue), each needing the same audit-then-copy-then-staging discipline as Sprint 1/Batch 2. Confirm with Andrew which batch is next before starting.

**Educator outreach funnel build** (after the legacy-blog work above) — not yet scoped in any canonical doc as of this writing. Before building, read `ENGINEERING/CTA_ENGINE_STATUS.md` and the existing Teacher funnel documentation (`.claude/rules/funnels.md`, `ANALYTICS/CONSENT_STATUS.md`'s funnel-isolation notes) as prior art — the existing on-site Teacher popup/classroom-guide funnel (`bhp_mariana_popup` storage prefix, `/teachers/`-scoped) is a separate, already-live system; clarify with Andrew whether this new "educator outreach" effort extends that funnel, replaces it, or is a distinct initiative (e.g. cold outreach to schools/teachers rather than on-site opt-in) before writing code.

---

## On hold, not abandoned: GTM Preview / GA4 DebugView / analytics activation

A bounded diagnostic session on 2026-07-13 identified the root cause of why Preview/DebugView connections keep failing — see `KNOWN_ISSUES.md` ("GTM Preview / GA4 DebugView cannot connect — root cause now proven") and `ANALYTICS/GTM_STATUS.md`. Summary: Andrew successfully authenticated into Tag Assistant for the first time this project, but the connection still failed — isolating the problem to network-level blocking (this session's browser-automation tool specifically blocks `googletagmanager.com`/`google-analytics.com`; Andrew's own browser also failed for a not-fully-diagnosed reason, likely antivirus/DNS-level ad-blocking). **Do not repeat the same URL-exchange workflow** without first addressing one of those root causes (e.g. Andrew testing from a different machine/network without security-suite web-shields).

Production analytics activation stays deferred:
- `bhp_gtm_container_id` / `bhp_ga4_measurement_id`: unset on production.
- `bhp_consent_decision_approved`: `false` on production.
- GTM container: unpublished.
- Staging's `bhp_staging_analytics_override` was temporarily enabled during the 2026-07-13 diagnostic session and has been restored to unset.

Do not modify production analytics or publish GTM until a genuine authenticated Preview/DebugView pass succeeds and Andrew makes the separate, explicit activation decision.

---

**Resolved (2026-07-13):** the production GTM/consent infrastructure gap (production had none of the 6 required theme files) is closed — see `RELEASES/PRODUCTION_CONSENT_DEPLOYMENT.md` and `DECISIONS.md`. The naming discrepancy (`BHP_Order_Attribution` → `BHP_UTM_Attribution`) was confirmed and corrected per Andrew's clarification; no rename was made since `BHP_UTM_Attribution` was already the canonical name.

**Resolved (2026-07-13, same day):** the bundle-pricing plugin analytics-staleness gap (production v1.7.1 vs repo/staging v1.8.2, breaking most GA4 ecommerce events) is closed via an isolated 7-file patch — see `RELEASES/BUNDLE_PRICING_ANALYTICS_PARITY_PRODUCTION.md`. Production's `includes/dashboard/` (KPI/economics module) deliberately remains untouched/behind staging — that's a separate, unapproved feature, not part of this fix.

**Other candidates, unrelated to the above, still awaiting Andrew's go-ahead (not started, no priority order implied):**
- Google Merchant Center disapproval — all 6 products show "disapproved," needs Andrew's console access. See `MARKETING/GOOGLE_MERCHANT_STATUS.md`.
- Deploy current `assets/js/nav.js` to production to restore `contextual_cta_click`'s full CTA attribution — a theme-ZIP cycle, low priority. See `KNOWN_ISSUES.md`.
- Remaining GTM event-coverage gap (6 events, deferred by 2026-07-12 CSO decision, not blocking).
- Mailchimp deliverability audit — not started.
- Staging→production test-suite parity — a full theme-ZIP deployment cycle, not urgent.
- KPI/economics dashboard module on production is now further behind staging's (Phase1A-v2 work never deployed) — a separate, deliberately-deferred feature decision, not an analytics blocker. See `DECISIONS.md`.
- `bundle_type_purchased`, Adventure Kit-signup event validation, and `BHP_UTM_Attribution`'s live order-meta-write behavior — need a real test order and/or a designated test contact, neither authorized so far.
- Git push to `origin` — commits sit locally ahead of origin (see `KNOWN_ISSUES.md`'s credential-prompt entry); Andrew should push via GitHub Desktop.
