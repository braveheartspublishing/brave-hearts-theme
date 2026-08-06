# Release 1.19.145 — compass logo/favicon swap, the FD-30 compass module, and the E1 order-confirmation email

- **Status: STAGING ONLY. Awaiting the owner's review. Production is untouched at theme 1.19.142 / bundle plugin 1.8.10** — verified live, read-only, during this release.
- **Date:** 2026-08-02 (machine clock, −06:00). ⚠️ The briefs and both source specifications for this work are titled **2026-08-03**. The discrepancy is **recorded, not resolved** — every timestamp in this record is the machine clock.
- **Branch:** `feature/product-media-gallery-1.19.140` · **base:** `422106f` · **six local commits, no push, no PR, no merge**
- **Deploys this wave: TWO, deliberately.** 1.19.144 reached staging, its QA found two defects, and the corrected build shipped as 1.19.145 rather than redeploying the same version number (which would have left the `?ver=` asset cache-buster unchanged and risked a reviewer seeing stale CSS). Both defects, and how they were measured, are in §5.

---

## 1 · What shipped, in one line each

| # | Item | Source specification |
|---|---|---|
| 1 | The retired sunrise-heart mark replaced by the compass across the site: custom logo, favicon/site icon, and the Organization schema logo | FD-32 (relayed) |
| 2 | A dedication/compass module on `/gift-buyers-guide/`, between the FAQ and the final CTA | FD-30 + the `commerce-cx` module specification |
| 3 | The WooCommerce processing-order email replaced by the E1 expectation-setting email | the `marketing-growth` post-purchase sequence, section 2 |

Full specifications live outside this repository and are referenced by path in the handoff, not reproduced here.

---

## 2 · Item 1 — the logo and favicon swap

### 2.1 ⭐ The finding that changed this item's scope

**`the_custom_logo()` was rendering NOTHING, on either environment.** `has_custom_logo()` returned **false** on staging *and* production before this release — verified by direct theme-mod read on staging and by the rendered header markup on both. The header, the footer and all five audience-landing heroes were falling through to their text-wordmark fallbacks.

**The retired sunrise-heart's only live appearance was inside the Rank Math Organization schema** (`knowledgegraph_logo` → `wp-content/uploads/2026/06/Brave-Hearts-Logo.png`), emitted sitewide on both environments. That is a **different setting** from the custom-logo theme mod, and swapping only the theme mod would have left the retired mark in every page's structured data.

Consequence for whoever ships this to production: **three settings, not one.**

### 2.2 What was changed on staging

| Setting | Before | After |
|---|---|---|
| `custom_logo` theme mod | `false` (unset) | attachment **760** — `brave-hearts-horizontal.png`, 1464×400 |
| `site_icon` option | `0` (no favicon at all; the head emitted zero icon links) | attachment **763** — `brave-hearts-favicon.png`, 480×480 |
| Rank Math `knowledgegraph_logo` / `_logo_id` | `.../2026/06/Brave-Hearts-Logo.png` / `215` | `.../2026/08/brave-hearts-horizontal.png` / `760` |

**Four NEW media attachments were created (760–763): horizontal, stacked, icon, favicon.** Each was verified byte-identical to its approved export by md5 before and after transfer. **No existing attachment was modified, replaced, renamed or deleted.** The retired sunrise-heart remains attachment **215**, untouched — `post_modified` still reads `2026-06-30 02:09:02` after this release. **It is the rollback.**

The site-icon intermediate sizes had to be generated explicitly. Setting `site_icon` by option skips the crop flow that normally creates them, so WordPress was serving a 100×100 crop for the declared 32×32 icon and a 300×300 for the declared 192×192. After regeneration the four `site_icon-*` sizes exist and each declared size is served at its true dimensions.

### 2.3 The dark-header problem, and the decision taken

**The header background is `rgba(6,19,31,.96)`. The approved lockup's wordmark is navy `#17293b`. That is a measured contrast of roughly 1.24:1 — effectively invisible.** All four approved exports are colour-type 6 RGBA with navy artwork, drawn for light backgrounds; **no reversed / light-on-dark export exists.**

The asset was **not** filtered, inverted or recoloured — that is a brand-asset mutation, not an engineering decision. It was instead given the light ground it was drawn for: a **cream plate** in the mark's own cream (`#f6f1e7`), applied in CSS, fully reversible, with the asset served byte-unchanged. The same treatment is applied in the footer, which is equally dark.

⚠️ **This is an implementation decision and it is flagged for the owner.** The alternative — a reversed export with a cream wordmark and cream compass ring, sitting directly on the dark header with no plate — is a **design-asset request, not a code change**, and would be the cleaner answer if the owner prefers it.

Two pre-existing rules also had to be cancelled for `.custom-logo-link`, which the selector `.site-logo a` also matches: a **56px left padding** and a **gold-diamond `::before` ornament** that would have sat beside the real mark. Both verified cancelled in the rendered page (`content: none`, `padding-left: 13px`).

### 2.4 Measured result — staging vs the production counterfactual

Production still has no custom logo, so it is a valid live measurement of the "before" state. Both read in a real browser.

| | Production (fallback wordmark) | Staging 1.19.145 (compass plate) | Δ |
|---|---|---|---|
| Header height, 1440px | **80.00px** | **82.59px** | **+2.59px** |
| Header height, 390px | **92.80px** | **92.80px** | **0.00px** |
| Logo lockup, 1440px | 401.31 × 42 | plate **143.11 × 42**, image 117.11 × 32 | same height, **258px narrower** |
| Logo lockup, 390px | 290 × 42 | plate **118.92 × 35**, image 98.92 × 27 | narrower and shorter |

The compass lockup is substantially **narrower** than the text wordmark it replaces, so the nav gained horizontal room rather than losing it. At 390 and 360px the hamburger toggle sits at x=326 / x=296 with a clean 20px right margin and **does not overflow** — the 2026-07-14 P0 failure mode was specifically re-tested.

Audience-landing heroes: the five templates call `the_custom_logo()` into the hero art column, and core emits `class="custom-logo"`, which the existing `.audience-landing-hero__logo` rule never matched. An uncapped 1464px PNG would have dominated the column above the covers. It is now capped **on height** (270.8 × 74 desktop, 212.5 × 58 mobile — measured), not on width, because a 3.66:1 lockup and a 1:1 mark get very different optical weight from a width cap.

### 2.5 Sitewide verification

**12 pages × 3 viewports = 36 combinations, in a real browser, `window.innerWidth` recorded for every one.**

- Compass logo present in the header on **36/36**. Desktop 117.11 × 32 on all 12 pages; 390 and 360px 98.92 × 27 on all 12.
- Retired sunrise-heart references in the rendered DOM: **0 on 36/36.**
- Fallback text wordmark still rendering anywhere: **none.**
- Horizontal scroll introduced: **none on 36/36.**
- Console errors: **0 on 36/36.**
- Favicon: `icon 32×32`, `icon 192×192`, `apple-touch-icon 180×180`, `msapplication-TileImage 270×270` — all present, all at their true sizes.

Pages covered: `/`, `/books/`, `/about/`, `/teachers/`, `/gift-buyers-guide/`, `/complete-collection/`, `/blog/`, `/contact/`, `/educators-adventure-learning-toolkit/`, `/organizations-community-reading-kit/`, `/reluctant-reader-adventure-kit/`, `/cart/`.

⚠️ **Browser-cache note for reviewers:** the favicon is the one asset browsers cache hardest. A tab that showed no icon before may keep showing none until a hard refresh, and a new tab is the most reliable way to see it.

---

## 3 · Item 2 — the compass module

Renders on `/gift-buyers-guide/` **and nowhere else**, exactly **once**, between the FAQ and the final CTA. Four elements in order: the compass mark, the printed dedication, "Not just a story. A compass.", one context line. No CTA, no link, no form control, no analytics event.

**The dedication is reproduced verbatim from the printed front matter of Volume I (*The Mariana Trench*)** and was diffed character-for-character against the approved string at all three viewports — **exact match, 6 logical lines, 6 rendered lines, 5 `<br>`, em-dash intact, no line wrapping at any width.**

⚠️ **The dedication differs per book.** Volume I reads *"Be brave."*; Volume II (*Mount Everest*) reads *"Be strong."* The module uses Volume I's wording as specified. **Which book's dedication is canonical for site use is an owner decision and has not been taken.** The source book is named in a comment directly above the markup so a future editor cannot "correct" it against the wrong volume.

The compass mark is served from `assets/images/brand/brave-hearts-compass-icon.png`, a **byte-identical copy** of the approved export (md5 `e21d582cb228d9540d43933c8bddb4dc`), as a **theme asset rather than a Media Library attachment** — no per-environment upload, no "attachment missing on staging" failure mode.

### 3.1 Acceptance criteria — result per criterion

Desktop measured at `window.innerWidth = 1440`; mobile at **390**, **360** and **320**, each with `window.innerWidth` read back and recorded before any measurement was trusted.

| Criterion | Result |
|---|---|
| Exactly one module, between FAQ and final CTA | **PASS** — count 1; previous sibling is the `--muted` FAQ section, next is `audience-landing-final` |
| Mark 48×48 desktop / 40×40 mobile, centred, no box behind it | **PASS** — 48.00×48.00 and 40.00×40.00; natural 480×480, alpha corners show the cream section through |
| Dedication renders on six lines with the em-dash | **PASS** at all viewports |
| Dedication matches the approved string character-for-character | **PASS** — diffed, not eyeballed |
| Payoff line larger, darker, roman serif vs the italic dedication | **PASS** — 30px/22px roman `#1f3d29` vs 24px/19px italic `#514f45`, both Cormorant Garamond |
| Context line sans, smallest, lightest | **PASS** — 15px Nunito Sans `#7a7869` |
| Zero links, buttons, inputs or `data-bhp-event` inside the module | **PASS** — 0 at every viewport; 0 tappable elements |
| Block padding ≥ 48px | **PASS** — 72px desktop, 48px mobile |
| Section background is the page's base cream | **PASS** — painted `rgb(244, 238, 222)` from `.audience-landing`; the section itself is transparent, which is the plain-section case the specification describes |
| Console clean | **PASS** — 0 errors at 1440/390/360/320 |
| Rendered schema byte-identical apart from the deliberate logo change | **PASS** — see §3.2 |
| Price card, lead-magnet block and every existing event untouched | **PASS** — see §3.3 |
| Other three audience pages show no change | **PASS** — see §3.4 |
| No horizontal scroll | **PASS** at 1440/390/360/320 |
| Payoff line wraps only between its two sentences | **FIXED** — it did not. See §5.1 |
| Module clears the fixed sticky bar | **PARTIAL — reported honestly in §3.5** |
| Module retained and legible in print | **PASS** — under emulated print media the section is `display:block`, `opacity:1`, height 635.8px, mark and dedication both visible, full text present |

### 3.2 Structured data

The rendered `rank-math-schema` block was captured before and after and diffed. **The entire diff is four lines**, all of them the deliberate Organization-logo change:

```
< "url":".../2026/06/Brave-Hearts-Logo.png"        > "url":".../2026/08/brave-hearts-horizontal.png"
< "contentUrl":".../2026/06/Brave-Hearts-Logo.png" > "contentUrl":".../2026/08/brave-hearts-horizontal.png"
< "width":"424"                                    > "width":"1464"
< "height":"422"                                   > "height":"400"
```

**The compass module added no schema entity of any kind.** Counts in the after-state: `aggregateRating` **0**, `"review"` **0**, `ratingValue` **0**, `reviewCount` **0**, `Quotation` **0**. The dedication is **not** marked up as a review, a testimonial or a quotation, and `<cite>` was deliberately omitted — attributing a dedication would turn it into one.

### 3.3 Commerce blocks — proven untouched

Server-rendered fingerprints, before vs after, gift page: `data-bhp-event` **5 → 5**, `class="btn` **9 → 9**, `add-to-cart` **4 → 4**, format-toggle markup **12 → 12**, shipping mentions **6 → 6**. The `data-bhp-event` **value multiset** and the `$`-amount multiset both diff **IDENTICAL**.

Because the Collection price card is JS-rendered, it was also read in a real browser: **$31.99 paperback / $48.99 hardcover, $3.98 saving, $35.97 strike, $3.99 shipping**, format toggle functional, both CTAs present with unchanged event names, lead-magnet block present with its 15 fields, sticky bar intact. Identical at 1440 and 390.

Store configuration re-read after the release: **one shipping zone, one `flat_rate` method, cost `3.99`, BookVAULT zoned = 0**; all resolved core products `instock` at unchanged prices; **8 orders on staging, 0 created today.**

### 3.4 Cross-page regression

`audience-landing.css` is shared by five templates, so this was measured rather than assumed. At 1440 and 390: the module renders on the gift page (**1**) and on **none** of `/educators-adventure-learning-toolkit/`, `/organizations-community-reading-kit/`, `/reluctant-reader-adventure-kit/`, `/teachers/` (**0** each). Section counts, hero cover counts and hero logo dimensions are consistent across the audience pages; zero console errors; no horizontal scroll.

### 3.5 ⚠️ The sticky bar — reported as partial, with the measurements

The page has a fixed bottom CTA bar (63px desktop, 89.4px mobile). Scrolled to the **worst case** — the module's bottom aligned to the viewport bottom, verified to within 0.5px — the module's final context line:

| Viewport | Result |
|---|---|
| 1440 × 900 | **clears** the bar by **9.3px** |
| 390 × 844 | **overlapped by 40.9px** |
| 360 × 780 | **overlapped by 41.3px** |
| 320 × 700 | **overlapped by 41.0px** |

The mark and the dedication are **never** obscured at any viewport; only the small context line, and only at that one scroll position.

**Two measurement passes disagreed and both are reported rather than the flattering one.** A second sweep that scrolled through every section in turn put the module 48.5px clear — but that pass did not verify fold alignment per section and its layout had shifted from lazy-loaded imagery above. **The table above is the authoritative measurement** because the module's bottom was confirmed within 0.5px of the fold in every row. That same sweep did establish that the **FAQ section overlaps the bar too**, so this is a page-level condition rather than something the module introduces.

**Not fixed here, deliberately.** The module-versus-sticky-bar tension is an open owner decision in the source specification, whose own recommendation is *do nothing*. Overriding it would be resolving a decision that is not the implementer's.

### 3.6 One deviation from the specification, stated rather than buried

The specification says the module has no scroll reveal. **It inherits one** — `audience-landing.js` applies a fade-in to every `.audience-landing__section` on the page except the hero, and the module is such a section. **Nothing was added**; excluding only the compass module would make it behave differently from the FAQ above it and the CTA below it, which would be more conspicuous, not less. Observed mid-reveal opacity 0.90; settles to 1. Under `prefers-reduced-motion` the whole mechanism is skipped, and in print it is forced visible.

---

## 4 · Item 3 — the E1 order-confirmation email

Overrides `emails/customer-processing-order.php` **and** its `plain/` twin from the theme. Both confirmed to resolve to the theme, not the plugin, by `wc_locate_template()` on staging.

The plain-text twin exists because without it the HTML recipient would get the honest copy and a plain-text recipient would silently fall back to WooCommerce's stock wording — **two different promises from one order.** (The store's current email type is `html`, so the plain template is not used for sending today; it is the multipart/fallback safety net.)

### 4.1 What the email deliberately does not say

- ⛔ **No elapsed-time claim of any kind.** The specification's timing slot is left **empty** on its author's own ruling: there is no measured production window for the current print partner, and the site's Terms page carries a "24 hours" figure written for the **former** vendor that has been flagged unverified since 2026-07-09. Repeating it into a customer's inbox would propagate an unverified claim, not inherit one. The slot is marked in both templates with the rule that only a **measured** range may ever fill it.
- ⛔ **No tracking promise.** There is no dispatch event and no tracking field on the WordPress side. The line reads *"We'll be in touch again in a few days"* — which a time-based follow-up can actually keep — and **not** *"we'll email you when it moves"*, which nothing in the system can trigger. This is the specification's own correction to its own first draft, applied.
- ⛔ **No sell, no upsell, no coupon, no promised date.** The refund/cancellation sentence is hedged exactly as the live Terms page hedges it.

### 4.2 Verified by preview render — ZERO mail sent

Rendered through WooCommerce's own preview against its **dummy order**, with `pre_wp_mail` hard-blocked and instrumented to shout if anything attempted a send. **Nothing attempted a send.** **No order was placed, no order status was changed, no real customer record was read, and the staging order count is unchanged at 8 with 0 created today.**

**Forbidden-content scan — every one absent:** a 24-hour claim · a business-days claim · a weeks claim · a tracking promise · "when it moves" · any coupon or discount code · any arrives-by / ships-by date.

**Required-phrase scan — every one present:** "printed for you after you order" · "Bookvault" · "short production step before anything ships" · "We'll be in touch again in a few days" · "read the first chapter together" · "once a book is in production we may not be able to stop it" · "Big Places. Brave Hearts." · "Thanks for taking a chance on us."

Order table, addresses and order number all render. Preheader confirmed injected immediately after `<body>`: *"A quick note on how your books are made, and when to expect us again."* Subject renders as **"Your order is in — here's what happens next"** (after the §5.2 fix).

### 4.3 Where the subject and preheader live, and why

In theme code (`inc/post-purchase-email.php`), **not** in WooCommerce settings. It writes **no WooCommerce setting, no option and no order record**, so it reverts entirely with a theme rollback and the store's own email configuration is exactly as it was found. The subject filter **defers to a real admin-configured subject** where one exists, so changing it in WooCommerce → Settings → Emails keeps working.

### 4.4 Two things flagged rather than changed

1. **The email heading still reads "Thank you for your order"** while the subject reads "Your order is in — here's what happens next". The heading is a **WooCommerce setting**, and changing it is a configuration mutation reserved to the owner. Not changed. Worth a decision at the copy gate.
2. **HTML entities (`&#036;`, `&#8217;`) appear in the plain-text order-totals block.** This comes from WooCommerce's own `plain/email-order-details.php`, is emitted by a core hook this override calls unmodified, and is **pre-existing core behaviour, not introduced here.** Not fixed, because fixing it means patching a second core template for a path the store does not currently send on.

---

## 5 · The two defects this release's own QA found

Both were found by measurement, not by review, and both are recorded because the process that caught them is the point.

### 5.1 The payoff line broke mid-sentence at every viewport

`"Not just a story. A compass."` rendered as **`["Not just a story. A", "compass."]`** at 1440, 390, 360 **and** 320px — greedy wrapping always fits the orphan "A" onto line one. That fails the specification's own criterion that the line may only break *between the two sentences*.

Three candidate fixes were measured before one was chosen:

| Candidate | Result |
|---|---|
| Narrow `max-width` (22ch → 18ch → 17ch → 16ch) | **FAILS at every value and every viewport.** The orphan "A" always fits; a box narrow enough to prevent it breaks the first sentence instead |
| `text-wrap: balance` | **PASSES**, but is not universally supported, so older browsers keep the defect |
| A `nowrap` span around the second sentence | **PASSES at 1440/390/360/320**, zero overflow, in every browser, with the rendered text character-identical (`innerText` verified) — **chosen** |

Measured and recorded so it is not re-added: combining the span **with** `text-wrap: balance` produces a **third, worse** split — `["Not just a", "story. A compass."]`. The CSS carries that warning.

### 5.2 The E1 subject filter could never fire

The guard deferred to an admin-configured subject by testing `get_option('subject')` for emptiness. `WC_Email::get_subject()` calls `get_option_or_transient('subject', $this->get_default_subject())` **before** applying the filter, and that call **populates `settings['subject']` with the default as a side effect** — so inside the filter the option always read as non-empty and the callback always deferred.

Confirmed by tracing the live filter chain rather than by reading the code: reading the option one line **before** `get_subject()` returned `''`; reading it **inside** the filter returned `"Your {site_title} order has been received!"`. Fixed by comparing against `get_default_subject()`, which is what actually distinguishes a real admin setting from WooCommerce's stock string.

---

## 6 · Build and deploy

`git archive` from the committed tree, prefix matched to the active theme slug, `tar` locally and `zip` on the server (PowerShell archive tools write backslash entry paths that extract as literal-backslash filenames on Linux). **178 entries, zero backslash entries.**

**Every PHP file in the artifact was linted on the server before installation: 116/116 clean, both builds.** Installed with `wp theme install --force`; active version read back as **1.19.145**; `wp eval` fatal-check returned `ok`; SiteGround dynamic cache purged.

⚠️ `wp sg purge` reports *"Unable to Purge File Cache. Please make sure it is enabled."* on staging. Dynamic cache purges successfully. This is pre-existing staging behaviour, not caused by this release.

---

## 7 · Rollback

Three independent layers, each verified to exist before it was needed.

| Layer | Restore |
|---|---|
| **Theme files** | Full pre-change copy of the 1.19.143 theme directory at `~/bhp-STAGING-backup-1.19.143-20260802-morningd/theme` on the server (37 top-level entries, `Version: 1.19.143` read back). Re-zip and `wp theme install --force`. |
| **Logo / favicon / schema** | `wp theme mod remove custom_logo` · `wp option update site_icon 0` · restore Rank Math `knowledgegraph_logo` to `.../2026/06/Brave-Hearts-Logo.png` and `knowledgegraph_logo_id` to `215`. All three prior values were read and recorded **before** being written. |
| **Media** | Nothing to undo. The four uploads are **new** attachments (760–763); no existing attachment was touched. **The retired sunrise-heart is attachment 215 and is intact** — it is the rollback, and deleting it would destroy it. |
| **Repo** | Six commits on a feature branch, unpushed. `git reset --hard 422106f` returns the tree to the pre-wave state. |

Pre/post rendered captures for eight pages: `~/bhp-morningd-captures-20260802/` on the server.

---

## 8 · Owner gates still open

- **Production deployment of any part of this release.** Note it is **three settings plus a theme deploy**, not a theme deploy alone (§2.1).
- **The final rendered wording of the compass module**, reviewed on staging rather than as a copy deck.
- **Which book's dedication is canonical for site use** — Volume I *"Be brave."* vs Volume II *"Be strong."*
- **The header logo plate**, or commissioning a reversed light-on-dark export instead (§2.3).
- **The E1 copy**, its subject line and preheader, and the mismatched email heading (§4.4).
- **The sticky-bar treatment** on mobile (§3.5).
- **The unverified "24 hours" production figure** on the live Terms page — it is a cancellation-window commitment of unknown truth and it was deliberately **not** propagated into E1.

## 9 · Evidence

QA artefacts — 42 screenshots, `qa-results.json`, `compass-detail.json`, `final-checks.json`, `M6-stickybar.json`, `commerce-guard.json`, `crosspage-and-print.json` — are stored outside this public repository with the wave record, and are referenced by the handoff rather than reproduced here.
