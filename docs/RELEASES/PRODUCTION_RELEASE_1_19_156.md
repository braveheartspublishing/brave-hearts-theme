# PRODUCTION RELEASE — theme 1.19.156 · the transactional email COPY layer

- **Released to production:** 2026-08-03
- **Theme:** 1.19.155 → **1.19.156** · **Bundle plugin:** 1.8.16 (unchanged)
- **Commits:** `237d71b` (the copy layer) · `ab11990` (the footer-mechanism fix)
- **Branch at time of build:** `feature/product-media-gallery-1.19.140`
- **Record status:** ✅ **This document closes the 1.19.156 release-record GAP** that
  `PRODUCTION_RELEASE_1_19_155.md`, `AI_CONTEXT_INDEX.md` and `NEXT_TASK.md` item 0 each recorded
  as outstanding.

---

## ⛔ PROVENANCE — read this before treating any line below as a builder's report

⛔ **This record was NOT written by the agent that built or shipped 1.19.156.** It is written by
`business-ops-knowledge` from **primary evidence that the builder did leave behind**, and each
section names its source.

**What this record IS built from:**

| Source | What it supplies |
|---|---|
| `git log` / `git show --stat` for `237d71b` and `ab11990` | The builder's own commit messages, **quoted verbatim below**, plus exact file and line counts |
| The `CYCLE142-EMAIL-BUILD` writer-lock release table | The builder's own closeout record: artefact md5, deploy method, rollback path, test send |
| Live HTTP verification, 2026-08-03 | The version actually being served by production |

⛔ **What this record is NOT.** A prepared release report from the building session **does not
exist** — both `lead-developer` working-draft folders were listed in full and neither contains an
email-build report. **Nothing in this document is presented as the builder's own narrative except
the passages explicitly marked as verbatim commit text.** No QA step is claimed here that is not
evidenced by one of the three sources above.

---

## 1 · What shipped

Per-email copy for **E1 through E7**, implemented through a theme filter layer plus six
template-override pairs. **The email CONFIG layer** — colours, masthead, `blogname`, auto-sync,
cancelled-order enable — **was already live before this release and was not touched by it.**

### The builder's own description, verbatim from commit `237d71b`

> ```
> NEW inc/transactional-emails.php
>   Subjects, headings and additional-content suppression per email id;
>   the id-keyed preheader map; the order-email-only Bookvault footer note
>   (FD-76 D6); and a minimal brand CSS layer. Every callback defers to a
>   real admin-entered value, comparing against the class default rather
>   than testing for empty.
>
> E1 processing   two changes only, body copy untouched: H1 becomes
>                 "Your order is confirmed" and the stock additional_content
>                 filler is suppressed. Subject unchanged.
> E2 completed    VARIANT A, "Your books have shipped". True only under the
>                 mark-complete-after-dispatch operating rule; states plainly
>                 that no tracking number exists.
> E3 refunded     full and partial branches, both written.
> E4 on hold      neutral shipped default; the payment-specific wording stays
>                 blocked until this store's on-hold causes are enumerated.
> E5 failed       the suite's only button: bulletproof table, forest fill,
>                 gold border, no `display` property. The unsourced
>                 pending-authorisation line is omitted, not softened.
> E6 note         nearly empty by design.
> E7 cancelled    new template pair. VARIANT A, refund sentence true only
>                 under the cancel-and-refund-together operating rule.
> ```
>
> ```
> Every HTML template has a plain-text twin carrying the same promises.
> Zero em dashes in any file touched here. No duration, delivery-date,
> tracking, coupon, upsell or review-ask claim anywhere. emails/email-
> header.php and emails/email-footer.php are NOT overridden.
>
> php -l clean on all 17 changed files, PHP 8.2.33.
> ```

### Files changed — `237d71b`, from `git show --stat`

**17 files, +1,869 / −59.**

| File | Δ |
|---|---|
| `inc/transactional-emails.php` **(NEW)** | +596 |
| `inc/post-purchase-email.php` | 78 |
| `functions.php` | +10 |
| `style.css` | 2 |
| `woocommerce/emails/customer-cancelled-order.php` **(NEW)** | +127 |
| `woocommerce/emails/customer-completed-order.php` | +127 |
| `woocommerce/emails/customer-failed-order.php` | +132 |
| `woocommerce/emails/customer-note.php` | +115 |
| `woocommerce/emails/customer-on-hold-order.php` | +107 |
| `woocommerce/emails/customer-refunded-order.php` | +129 |
| `woocommerce/emails/plain/` × 7 | +84 / +80 / +81 / +75 / +75 / 23 / +87 |

---

## 2 · The defect found by rendering, and fixed in the same release — `ab11990`

⭐ **This is the most instructive part of the release and is recorded in full, because it is a
worked example of the project's own rule that rendered output beats source review.**

### The builder's own account, verbatim from commit `ab11990`

> ```
> Found by rendering, not by reading. The first build put the FD-76 D6
> fulfilment sentence into all seven plain twins and into NONE of the HTML
> siblings.
>
> Root cause, read from source on staging: emails/email-footer.php calls
> apply_filters( 'woocommerce_email_footer_text', $text, $email ), which
> looks scopable -- but WC_Emails::email_footer() is declared with NO
> parameters and calls wc_get_template( 'emails/email-footer.php' ) with NO
> arguments (includes/class-wc-emails.php:388, WooCommerce 10.9.1), so the
> template's own `$email = $email ?? null;` resolves to null on every
> render and the filter can never be scoped by email id.
>
> Switched to the woocommerce_email_footer ACTION at priority 5, which every
> template fires as do_action( 'woocommerce_email_footer', $email ) and which
> therefore does receive the email object.
> ```

### A pre-existing defect repaired in passing

> ```
> Also repairs a PRE-EXISTING plain-text footer defect, observed in the
> 1.19.155 baseline render before this build touched anything: the stored
> footer option is `{site_title}<br />{store_address}` and
> WC_Email::get_content() runs wp_strip_all_tags() over the plain body, which
> DELETES the <br /> rather than breaking the line, rendering
> "Brave Hearts Publishing580 Hyde Ave, Pocatello...". Converted to a newline
> before the strip.
> ```

**Builder's re-verification, verbatim:** *"Re-verified on staging: all seven order emails now carry
the sentence exactly once in BOTH versions; both account emails carry it zero times."*

⭐ **Note the failure mode this documents for future work: a filter whose signature advertises a
parameter the caller never passes.** Reading `email-footer.php` alone would confirm the first
approach as correct. **Only the render disproved it.**

---

## 3 · Deployment — from the builder's own writer-lock release record

| | |
|---|---|
| **Artefact** | `bhp-1.19.156.zip`, md5 `0f928b7ab8081c0038110c9859f6fe39`, **369 entries**, 8,657,405 bytes |
| **Artefact integrity check** | Entry list diffed against the verified 1.19.155 artefact: **0 dropped, exactly 13 added**, 1 top-level root, 0 backslash paths |
| **Method** | `wp theme install --force` from an artefact md5-verified **on the server before install**, then `wp sg purge` |
| **Rollback taken FIRST** | `~/bhp-PROD-rollback-theme-1.19.155-20260803-113832` — 324 files, `Version: 1.19.155` read back |
| **Staging** | Deployed and purged ahead of production |
| **Production option writes** | ⛔ **NONE.** The email config was already live and was **READ ONLY** |
| **Test suites** | Theme and plugin suites reported passing on **both** staging and production |

### Rollback path

`wp theme install --force` the retained 1.19.155 artefact, or restore
`~/bhp-PROD-rollback-theme-1.19.155-20260803-113832`, then `wp sg purge`.
⚠️ **Production has since moved to 1.19.157** — a rollback of 1.19.156 today would also remove the
dispatch tracker. See `PRODUCTION_RELEASE_1_19_157.md`.

---

## 4 · Verification — stated per claim, with what was NOT checked

| Claim | Basis |
|---|---|
| Production is serving the theme released after this one | ⭐ **VERIFIED LIVE 2026-08-03** by HTTP against the production home page (200): **11 theme assets enqueued at `ver=1.19.157`**, 4 plugin assets at `ver=1.8.16`. ⚠️ This confirms the **current** version, not 1.19.156 in isolation — 1.19.156 was superseded the same day |
| `php -l` clean on all 17 changed files, PHP 8.2.33 | **Builder-reported** in the commit message; server-side. Not re-run by this record |
| Artefact md5, entry counts, rollback snapshot | **Builder-reported** in the writer-lock release table |
| One test send, E1, staging → `andrew@braveheartspublishing.com` | **Builder-reported.** `trigger()` not called; `WC_Email::send()` called once with an explicit recipient; a `wp_mail` counter asserted **exactly 1 call, exactly 1 recipient**; body 12,357 bytes, byte-identical to the post-fix verified render |

### ⛔ NOT VERIFIED — by anyone, and stated rather than implied

- ⛔ **No real email-client rendering check.** Desktop, mobile-390px and the cross-client matrix are
  **all unverified.** The builder's own closeout says so: *"the test send is the instrument and it
  is in Andrew's inbox, not this agent's."*
- ⛔ **No deliverability check** — SPF, DKIM and DMARC were not tested.
- ⛔ **No real order was placed on any environment**, so **no customer-triggered email has been
  observed end-to-end.**
- ⛔ **No browser QA of the order-received page** was performed for this release.
- ⛔ **`wp theme list --status=active` over SSH was NOT run by this record's author**, who holds no
  SSH credentials. The HTTP enqueue-version check above is strong live evidence and is **not** the
  definitive instrument.

---

## 5 · Known limitations carried forward

- **E4 on-hold**: the payment-specific wording stays blocked until this store's on-hold causes are
  enumerated. A **neutral** version shipped.
- **E5**: the unsourced pending-authorisation line was **omitted, not softened.**
- **E2 and E7 are Variant A** — each is true only under an operating rule a human must follow
  (mark-complete-after-dispatch; cancel-and-refund-together). ⭐ **The dispatch tracker released in
  1.19.157 is what converts E2's rule into an automated fact.**
- **Deck §3.2 Variant B** is recorded on the order by the tracker but **remains unbuilt.**

## 6 · Boundaries held during the build

⛔ No push, PR or merge · nothing written into `docs/` by the building session (a documentation
block was handed over instead) · **no WooCommerce product, price, coupon, promotion, stock,
shipping, tax, payment or checkout mutation on any environment** · no production option write ·
exactly **one** email sent, to the owner's own address, from staging, subject-tagged `[STAGING TEST]`.

---

*Recorded 2026-08-03 by `business-ops-knowledge` under the documentation-governance rules in
`DOCUMENTATION_GOVERNANCE.md`. Indexed in `AI_CONTEXT_INDEX.md`. Companion record:
`PRODUCTION_RELEASE_1_19_157.md`.*
