# PRODUCTION RELEASE — theme 1.19.155 + bundle-pricing 1.8.16, two product-record corrections, the privacy-policy sentence, cart-thumbnail regeneration and the transactional-email configuration

**Date:** 2026-08-03 (America/Denver, −06:00)
**Environments changed:** **PRODUCTION** (theme, plugin, two product records, one page, image derivatives, seven options)
**Branch:** `feature/product-media-gallery-1.19.140` · **Commit deployed:** `e98cd0f` — **local, unpushed**
**Prior production state:** theme **1.19.142**, bundle plugin **1.8.10** — read live immediately before the deploy, not inherited from a document
**Staging at time of release:** theme **1.19.155**, bundle plugin **1.8.16** — the same artefacts

---

## 0 · Authority, stated with its relay

Andrew Signore, 2026-08-02/03, **relayed verbatim** by the Chief of Staff:

> *"You dont need my approval - move to production after final audit"*

⚠️ **Neither the engineer who executed this release nor the agent writing this record witnessed that message directly.** A production deploy, a WooCommerce product mutation and a WooCommerce configuration change are owner-gated. This release ran on a relayed authorization, and the provenance is recorded here rather than described as first-hand. **What would close the gap:** Andrew's own countersignature in the canonical decision record.

⚠️ **Scope note, recorded rather than assumed.** The authorization is general ("move to production after final audit"). It was executed as covering the full push including the WooCommerce **product-record** edits (layer C) and the **email/site configuration** (layer E) — both of which prior build records had deliberately withheld under the stricter reading of the owner-gate list. **That widening of scope is a judgement made on a relayed instruction and is flagged for Andrew, not presented as settled.**

---

## 1 · What is live on production now

Every row was **read from the running system after the push**, over SSH and over HTTP. None is inherited from a build report.

| | Before | After | Verified how |
|---|---|---|---|
| Theme | 1.19.142 | **1.19.155** | `wp theme list --status=active` |
| Bundle plugin | 1.8.10 | **1.8.16** | `wp plugin list \| grep bundle` |
| Enqueued asset version | `ver=1.19.142` | **`ver=1.19.155`** | HTTP GET of `/` |
| Product **333** `post_content` | 1886 B, format sentence + "shipped by" | **1888 B**, `fulfilledBy=1`, other two flags 0 | `wp eval` strlen + substring flags |
| Product **15** `post_content` | 1773 B, redundant format sentence | **1749 B**, `fulfilledBy=1`, other two flags 0 | same |
| Product **12** `post_content` | 2210 B, legacy strings | **2210 B — UNCHANGED BY DESIGN** | same |
| Page **3** privacy policy | 8470 B, md5 `b201e9a3…` | **8563 B, md5 `2a274067592a2d8ec341283e417904ff`** | `wp eval` strlen + md5 |
| `woocommerce_thumbnail_cropping` | unset | **`uncropped`** | `wp option get` |
| Product 333 cart thumbnail | 300×300 | **300×460** | `wp_get_attachment_image_src` |
| `blogname` length | 24 (trailing space) | **23** | `wp eval strlen` |
| `woocommerce_email_auto_sync_with_theme` | `yes` | **`no`** | `wp option get` |
| `woocommerce_email_base_color` | `#8526ff` | **`#071522`** | `wp option get` |
| `woocommerce_email_header_image` | empty | **uploaded attachment URL** | `wp option get` |
| `customer_cancelled_order` email | disabled | **ENABLED** | `WC()->mailer()` enumeration |
| `woocommerce_checkout_phone_field` | `required` | **`required` — NOT changed by this push** | `wp option get` |

---

## 2 · The six layers, and how each rolls back

**They are independent. Roll back only the layer that failed.**

| Layer | What changed | Rollback |
|---|---|---|
| **A** theme | directory replaced | restore `~/bhp-PROD-rollback-theme-1.19.142-20260803-110349` |
| **B** plugin | directory replaced | restore `~/bhp-PROD-rollback-plugin-1.8.10-20260803-110349` |
| **C** products | `post_content` of 333 and 15 | `wp post update` from `~/bhp-PROD-backup-333-20260803-110349.html` and `~/bhp-PROD-backup-15-20260803-110349.html` |
| **D** policy | `post_content` of page 3 | `wp post update 3 ~/bhp-PROD-backup-page3-20260803-110349.html` |
| **E1** thumbnails | option + regenerated derivatives | `wp option delete woocommerce_thumbnail_cropping` then regenerate |
| **E2** email/site config | seven options, one new attachment | restore the captured before-values; **leave the attachment in place** — deleting media is destructive and is the owner's call, not a rollback step |
| **F** docs | `RUNBOOK.md`, `KNOWN_ISSUES.md`, `CHANGELOG.md`, this record | git revert of the documentation commit |

**All server-side rollback artefacts share the stamp `20260803-110349` and were verified present on the server after the push.** Product records create **no** WordPress revisions, so the `.html` backups are the only undo path for layer C.

Code-level rollback: `git revert e98cd0f` and the preceding build commits on the feature branch.

---

## 3 · Verification performed, and what it does and does not prove

### 3.1 Server-side, over SSH — every value in §1

Read after the push completed. Both theme test suites and the plugin test suite were executed against the **installed production code** and passed. `wp eval` fatal check passed. Cache purged.

### 3.2 External, over HTTP — seven checks

| # | Check | Result |
|---|---|---|
| 1 | Enqueued theme version | **`ver=1.19.155`** |
| 2 | Leaked homepage source comment | **0 occurrences** |
| 3 | Product page default format | **`data-bhp-format-initial="hardcover"`** |
| 4 | Explicit `?bhp_format=paperback` URL honoured | **`paperback`** |
| 5 | Quiz auto-open | **`false` on `/complete-collection/`**, **`true` on `/` and `/books/`** |
| 6 | Privacy-policy sentence | new sentence **×1**, superseded sentence **×0** |
| 7 | Homepage families CTA | "Get the collection Here" **×1**, removed link clusters **×0** |

### 3.3 ⛔ What was NOT verified, stated rather than glossed

- **No test order was placed and no transactional email was read.** Layer E2 is confirmed *stored*; what a real message renders like in a real inbox is **unverified**.
- **Apple Pay was not exercised on a real device.** The wallet latch fix is verified in a headless browser only, and a cold headless profile is not a wallet-less device.
- **Cart and checkout interaction geometry** — quantity-button boxes, the Remove control, totals recalculation, the empty-checkout state — is **React-rendered and cannot be proven by HTTP**. It was verified on staging in a real browser at 390px and 1440px; it has **not** been re-verified in a browser against production.
- **The 92 regenerated image derivatives were not individually inspected.** One product's thumbnail dimensions were confirmed.

---

## 4 · ⚠️ Findings from this release — recorded, not resolved

1. **`/book-bundles/` does not exist on production.** It is a published page on staging (ID 356); on production it returns **HTTP 404**, and ID 356 there is an unrelated attachment. The plugin bump **1.8.15 → 1.8.16** was made specifically to order the hardcover bundle offers above the paperback offers *on that page*. The plugin change is inert on production rather than wrong — no pricing or handler behaviour changed — but **its purpose is unobservable on the live site, and the release check written for it cannot pass.** Whether the page should exist on production is an owner question.
2. **The product-record and email-configuration layers were executed under a general relayed authorization.** Prior build records had withheld both under the stricter reading of the owner-gate list. See §0.
3. **Product 12** still carries the legacy format sentence and names the former print vendor. Untouched by design on both environments. Owner question.
4. **The collection page's two format pills are still paperback-left** while its selected format, visible panel and sticky bar are all hardcover. Left unchanged deliberately; making it consistent is a one-line change. Owner call.
5. **The product page's default CTA now routes through the legacy hardcover 301.** Verified working, but it now carries the default traffic rather than a secondary path.

---

## 5 · Artefacts

| File | md5 | Note |
|---|---|---|
| `bhp-1.19.155.zip` | `d6f6b508d71659a055ff487cc4788749` | 356 entries · 5,571,414 bytes |
| `bhp-plugin-1.8.16.zip` | `78ad97f7d18e8f5a17fd9e3a8a046433` | 44 entries · 185,461 bytes |
| `bhp-a2a3-prod-derived.php` | `011523cf37a39b01901dadc698f93d80` | guarded, idempotent, dry-run-first product edit |
| `bhp-policy-cookie-line.php` | `a0e37dd159df43cf5a169169bb5427c1` | guarded, idempotent policy edit |

Both `.php` artefacts ran a **dry run first**, and every predicted byte count and md5 matched the live result exactly. Artefacts and evidence screenshots are held outside this repository in the build working directory; they are **not** committed here.

**Pre-install artefact assertion, and why it is mandatory:** `wp theme install --force` deletes the theme directory before extracting. The ZIP was md5-checked on the server and asserted to contain **356 entries**, zero backslash paths, a single top-level root matching the active theme slug, and the `woocommerce/` template overrides and both test suites, **before** install. See `RUNBOOK.md` and layer F of the changelog entry — the previously documented build line produced 180 files and would have deleted those directories from the live site.

---

## 6 · Branch state

**The commit deployed to production is `e98cd0f`.** No push, PR or merge was performed. Andrew pushes via GitHub Desktop.

⚠️ **Production is therefore running code that exists only in a local working tree and in the deployed artefact.** Until the branch is pushed, the deployed commit has no remote copy.

### ⭐ CORRECTION, same sitting — **HEAD has since moved past the deployed commit. Do not read HEAD as production.**

**Re-read at the end of this session, after the body above was written:** `git log -1` now reports **`237d71b` — *"feat(email): the transactional email COPY layer, E1 through E7 — 1.19.156"***, and the working tree's `style.css` reads **`Version: 1.19.156`**.

**That commit is LOCAL, UNDEPLOYED and UNPUSHED.** It landed from a concurrent build while this record was being written.

| | Value | Verified |
|---|---|---|
| **Deployed to production** | **`e98cd0f`** · theme **1.19.155** · plugin **1.8.16** | `wp theme list --status=active` / `wp plugin list`, **re-confirmed after `237d71b` landed** — production did **not** move |
| **Local branch HEAD** | **`237d71b`** · worktree `style.css` **1.19.156** | `git log -1`, `git branch --show-current`, `style.css` header |

⛔ **The worktree version is NOT the production version, and this record is the one that says so.** The gap between them is now **two** commits of unpushed work — the deployed one and the newer email-copy layer — and pushing the branch (`ACT-OPS-041`) covers both.

⭐ **Recorded rather than quietly amended, because a stale "HEAD is what shipped" claim is precisely the failure the live-state rule exists to prevent.**

### ⛔⛔ SECOND CORRECTION, same sitting — **PRODUCTION MOVED AGAIN, TO 1.19.156, BEFORE THIS RECORD WAS FINISHED**

**Final live re-check at closeout, 2026-08-03:** `wp theme list --status=active` reads **`1.19.156`**; the live homepage enqueues **`ver=1.19.156`**; plugin unchanged at **`1.8.16`**.

**A second production deploy — the transactional-email COPY layer, theme-only — shipped from a concurrent build while this record was being written.**

⭐ **THIS RECORD REMAINS VALID FOR THE RELEASE IT DOCUMENTS.** 1.19.155 was genuinely live and fully verified when §1–§5 were written. 1.19.156 is a **theme-only** change layered on top, so **every non-theme value verified here is still true on production**: products 333/15/12, page 3, the thumbnail setting and its derivatives, and all seven email/site options. **The rollback artefacts under stamp `20260803-110349` are still the correct undo path for those layers.**

⛔ **What is now false in this document, and only this:** any sentence implying the *current* production theme is 1.19.155. **It was; it is not.**

⚠️ **No release record exists for 1.19.156.** ⛔ **One is not written here** — it belongs to the agent that shipped it, and inventing one from another session's work would be a fabricated verification. **The gap is named in `NEXT_TASK.md` instead.**

⭐ **The durable lesson from a single day: production shipped twice, and a document written between the two was stale before it was saved. Assert the live version from `wp theme list --status=active` — never from a release record, including this one.**
