# Changelog — Brave Hearts Publishing

Major milestones only, human-readable. Not a commit log — see `git log` for that.

## 2026-09-02 - PRODUCTION IS NOW THEME `1.19.354` / BUNDLE PLUGIN `1.8.79` (releases 1.19.350 through 1.19.354)

> ⭐ **This block supersedes, on the version number only, the entry immediately below it, which recorded
> production as theme `1.19.349` / plugin `1.8.78` earlier the same day.** That entry is correct for the
> moment it describes and is deliberately NOT rewritten. Read this one first.

**Production, 2026-09-02: theme `1.19.354`, bundle plugin `brave-hearts-bundle-pricing` `1.8.79`.**
Five theme releases were built and staging-verified on 2026-09-02. **`1.19.353` and then `1.19.354` were
deployed to production on 2026-09-02**, each under the founder's explicit approval; **plugin `1.8.79` was
deployed to production on 2026-09-02.** `1.19.350`, `1.19.351` and `1.19.352` never shipped to production
on their own: their contents reached production **inside** `1.19.353` and `1.19.354`, which are cumulative
builds of the same working tree. They are recorded below individually because each is a distinct staging
release with its own tests and its own rollback artefact, and because the record of what changed should not
be collapsed into the version number that happened to carry it.

**Release contents and per-release detail: `docs/RELEASES/PRODUCTION_RELEASE_1_19_350_354.md`.**

---

### `1.19.354` - 2026-09-02 - `/author-visits/` fold and hero body colour - **DEPLOYED TO PRODUCTION 2026-09-02**

Cosmetic release, single page, staging-verified before deployment.

- `/author-visits/` hero `padding-block` 128px to 72px at 1440 and 80px to 56px at 375; the list section's
  top padding 128px to 64px and 80px to 44px. The first visit card now clears the fold complete with its
  status pill at both viewports (1440x900: card bottom 984 to 808 against a 900 fold; 375x812: 850 to 766
  against an 812 fold). The page is reached from printed QR codes, so the next visit clearing the fold is
  the page's whole job.
- Hero body copy moved from the inherited `--color-sky` to `--color-parchment`, a colour the brand kit
  carries. 15.02:1 on navy. `.section--dark` is unchanged and no sitewide token was repointed.
- Both padding rules are written at specificity (0,3,1) because `body:not(.home) .section` is (0,2,1); a
  bare class selector is a silent no-op there. A third rule written at (0,2,0) was shipped to staging,
  measured as a no-op and removed, with the reasoning preserved in `style.css`. See `KNOWN_ISSUES.md`
  `LD-12`.
- New standing gate `tests/test-cycle179-author-visits-fold-354.php`, 27 assertions, covering the
  specificity rail, the colour token, artefact parity, the one-template blast radius and the untouched copy.
- No copy, visit data, deadline-resolver, closed-state or visit-band change.
- Full suite, 124 suites, run with `--url` on every invocation: **zero new failures** against the accepted
  `1.19.353` baseline (75 failing assertions on both trees, 9 non-zero exits on both).

### `1.19.353` - 2026-09-02 - School-visit band: the slug in the URL wins (F-10) - **DEPLOYED TO PRODUCTION 2026-09-02**

**Defect F-10.** A browser holding one school's live visit session that opened a DIFFERENT school's QR URL
kept showing the FIRST school's band. Reproduced on staging at `1.19.352` at an asserted `innerWidth` of
1440: with `?bhp_visit=<second-school>` in the address bar, the band still named the first school and its
hand-delivery date.

**Fixed.** An explicit `?bhp_visit=<slug>` that names a registered visit now decides the band, open or
closed, whatever the session holds. A slug that resolves renders the open band; a registered slug past its
online close renders that slug's closed band (`1.19.351` behaviour, now reachable from a flagged session).
A slug absent from the registry still names no visit and changes nothing.

- `inc/visit-band.php`: new `bhp_visit_band_request_slug()` and `bhp_visit_band_decide()` (pure);
  `bhp_visit_band_state()` consults the URL before the session and reads the session only when the URL
  named no registered visit; `bhp_visit_band_body_class()` keys off the session, which is the same question
  the shelf counter asks, so the flagged-card geometry stays married to the counter markup it pays for.
- `tests/test-cycle179-visit-band-f10.php`: new suite, 43 assertions, covering all four session-versus-URL
  cases plus the unknown-slug and clear-token no-ops.
- `style.css` / `style.min.css`: version bump only.

**Display only.** No session is written or cleared, no entitlement changes, and the 14-day TTL, the
visit-close guard, the paperback-only gate, the deadline resolver and the `?bhp_shiphome=` confirmation
path are untouched. No registry write. No WooCommerce product, price, coupon, stock, shipping, tax or
payment change.

**Known divergence, open, registered as `LD-10` in `KNOWN_ISSUES.md`:** on a session-open plus
URL-slug-closed request the band names the URL's school while the per-card counters, which are the
plugin's and session-driven, still count the session school's shelf. Reconciling them is an entitlement
change and needs the founder's ruling.

### `1.19.352` - 2026-09-02 - Production-readiness pass - reached production inside `1.19.353`

- Desktop catalog card: the age line is hidden with `display: none`. Verified `getClientRects().length === 0`
  and `offsetParent === null` at 1920, 1440 and 1366, so no zero-height ghost remains. The mobile card is
  untouched and still renders the line at 19px at an asserted 375x812.
- Removed a live production school-visit slug that `1.19.351` had written into a code comment in
  `inc/author-visits.php`. Caught independently by two suites. The code moved; neither assertion was touched.
- `tests/test-cycle173-consent-checkout.php` pinned the bundle plugin version by equality to `1.8.78`, so it
  failed on a correct build and gave opposite results on production and staging. Converted to a floor,
  matching the sibling theme assertion. The superseded line is preserved verbatim.
- Three new standing gates lock the age-line ruling in: mobile renders the line, desktop hides it with
  `display:none`, and the desktop hide leaves no zero-height ghost.
- Full 122-suite set run on the deployed artefact, against a real `1.19.349` baseline created by installing
  the `1.19.349` rollback tarball and running all 121 suites against it: 15 failing suites / 31 failing
  assertions at `1.19.349` against 14 / 30 at `1.19.352`. **Zero new failures.**

**A separate no-version-bump pass on the same day** removed 45 internal-role call-name occurrences from 12
files in this repository and replaced them with the technical role ID or a neutral phrase. Every occurrence
was a code comment or a test assertion label; **no selector, value, statement, assertion logic or rendered
string changed**, so the version stayed at `1.19.352`. The three rebuilt CSS artefacts differ by exactly one
line each, the builder's own source-md5 provenance header, proved by diff against artefacts rebuilt from the
pre-edit sources. The full 122-suite set was re-run and compared per suite against the accepted `1.19.352`
baseline: the diff is empty, zero new failures. A post-scrub grep over the working tree, the extracted
deploy artefact, the deployed staging theme and the rendered DOM each return zero.

### `1.19.351` - 2026-09-02 - Deadline single source of truth, age line restored - reached production inside `1.19.353`

- **One deadline across every surface.** New `bhp_visit_deadline_display()` in `inc/visit-band.php` returns
  the **earlier** of the registry's stated cutoff and the online close, and is read by the shop band (open
  and closed) and by `/author-visits/` (open and closed rows). Nothing else computes a deadline. Asserted
  across 600 synthetic rows, 0 violations. A printed deadline can never be later than what parents were
  told, and never later than the date the site will actually accept an order. **Rule recorded in
  `DECISIONS.md`.**
- **The order gate is unchanged.** `bhp_school_visit_last_order_date()` (visit minus 2) and
  `bhp_school_visit_is_open_on()` are not touched. This changes a display, never entitlement, and no
  registry row was edited on any environment. The brief's premise that the gate read the registry cutoff was
  corrected rather than implemented; a standing test gate locks the correction in.
- Age line restored on the catalog card at both viewports; the desktop rule scoped to `min-width: 641px` so
  the approved mobile geometry is unaffected. Cost, stated as a loss: at 375 the Complete Collection card no
  longer peeks above the fold (y779 to y824). Four of five cards still clear it.
- Closed-state band verified on staging against a real registered slug past its close.
- `test-cycle167-readaloud-bundle-visit.php` was failing five assertions at `1.19.350` while the `1.19.350`
  record reported it green. Assertions moved to the new `?bhp_shiphome=` route; **superseded assertions
  preserved verbatim**.

### `1.19.350` - 2026-09-02 - The catalog card, sitewide - reached production inside `1.19.353`

Every customer-facing surface that lists a product now renders one card. The shop, the six product-category
archives, the twelve product-tag archives and WooCommerce product search share one predicate
(`bhp_catalog_grid_context`) and one CSS scope (`body.bhp-catalog-grid`), replacing an `is_shop()` gate that
gave `/shop/` a real card and the other twenty surfaces a 1110px tile with one price and a navigation link
wearing a button. **Rule recorded in `DECISIONS.md`.**

- The stacked 279px archive hero becomes one band of roughly 98px. H1 wording unchanged.
- The WooCommerce result count and sort select are **removed from the DOM** on catalog grids. The count was
  stating a wrong number (four results above six cards on `/shop/`, two above four on search).
- Five-up grid at 1280+, two-up at 640 and below; reading order set by a `pre_get_posts` filter. **No
  `menu_order` was written; ordering is theme code, not product data.**
- The card: fixed cover well, per-card eyebrow ("Book 1 of 3"), italic place line, one "From" price, format
  chips with a tick on the selected binding so no figure prints twice.
- The Kirkus badge and the Amazon review showcase move from inside the card to one strip below the grid.
  Wording untouched; both components still render.
- The two bundle cards move from inside the grid to a strip below it, with corrected labels.
- **F-01 closed:** `/product-category/hardcover-books/` 301s to `/shop/`; any product archive that renders no
  card is noindexed. Hardcovers remain hidden from the grid and remain purchasable.
- School visits: a band above the fold carrying the school name, "Order by <date>" (or "Order by today,
  <date>" on the last order date) and the pickup line, plus a **CLOSED state** where a flagged URL past its
  close previously rendered the ordinary storefront in silence.
- "Ship to your home" no longer clears the visit session on click. It asks first, on a confirmation panel
  with two plain links, and the single coloring card now carries the same explanatory note the bundle card
  has carried since `1.19.295`.
- Two `1.19.349` cosmetics: the coloring product page printed the trim size twice, 23px apart, in two
  different glyphs; the selected format chip repeated the card's own price.
- `assets/downloads/mariana-trench-coloring-pages.pdf` replaced with v2, md5
  `49ae06f1402bf7b6dc0f821ddb5c60a9`.
- `inc/book-formats.php`'s asset enqueue widened from `is_product() || is_shop()` to the catalog predicate,
  because the archives were rendering the card without its own stylesheet.
- New suite `tests/test-cycle179-catalog-350.php`, 106 assertions, all passing.

### Bundle plugin `brave-hearts-bundle-pricing` `1.8.79` - **DEPLOYED TO PRODUCTION 2026-09-02**

Plugin `1.8.79` was built and recorded on 2026-09-02 alongside theme `1.19.345` (see that entry below for
what it contains: the single blog-ask arithmetic and the Signed Copies admin screen under WooCommerce). It
**reached production on 2026-09-02**, in the same window as the theme releases above. The earlier entry
recording production at plugin `1.8.78` is correct for the moment it describes and is not rewritten.

### Not changed by any of the above, on any environment

No product record, no variation, no price, no coupon, no stock, no shipping, tax, payment or checkout
setting, no `menu_order`, no `bhp_school_visits` registry row. **Content updates on production the same day
were made by the owner and are not theme releases.**

---

## 2026-09-02 - Production state re-verified with the definitive instrument, and two deploy-runbook assertions corrected

**Production is theme `1.19.349` / bundle plugin `1.8.78`.** Verified 2026-09-02, read-only over SSH
against the production document root: `wp theme list --status=active` returns `1.19.349` and
`wp plugin get brave-hearts-bundle-pricing --field=version` returns `1.8.78`. Corroborated
independently by a read-only HTTP GET of the production home page (HTTP 200, canonical
`https://braveheartspublishing.com/`, zero `staging2` occurrences), which enqueues **14 theme assets at
`ver=1.19.349`** and **6 plugin assets at `ver=1.8.78`**. **Two instruments, agreeing.**

⚠️ **A contradiction is recorded rather than resolved.** The build brief authorising this documentation
pass stated that production "stays 1.19.344 tonight" and that the 1.19.349 production deploy "did not
happen". Both instruments say otherwise. **How 1.19.349 reached production is not this record's to
answer, and it is not answered here.** Escalated.

⛔ **No production write of any kind was made by the pass that wrote this entry.** The only production
contact was the two read-only checks above.

**`docs/RUNBOOK.md`, correction 1: the minified-CSS gate.** The deploy-artefact block asserted that a
built ZIP `MUST be 10` minified stylesheets. `git ls-files '*.min.css'` at HEAD returns **14** (13 under
`assets/css/` plus `style.min.css`), and a working-tree build now returns **15**. A correctly built
artefact therefore **failed that gate**, and the runbook's documented response to a failed gate is to
stop and investigate a build that is in fact correct. ⭐ **The gate is now a floor (`>= 14`) rather than
an equality**, matching the two assertions immediately above it and the file's own standing warning that
a fixed number goes stale and then gets corrected downward by someone trusting it. A floor still catches
the failure the assertion exists for, a build that silently dropped artefacts, while adding a stylesheet
no longer falsifies it. **The superseded value is preserved beside it rather than deleted.**

**`docs/RUNBOOK.md`, correction 2: `assets/covers/` is now excluded from the deploy artefact.** The
`git archive` path list names `assets` wholesale, which sweeps in 117 tracked print-source and proof
masters, roughly 500 MB, referenced by zero PHP, JS or CSS files and present on neither environment. The
exclusion is now a pathspec in the documented command, with a matching `MUST be 0` pre-install
assertion. ⚠️ **The note warns explicitly against widening it**: `assets/look-inside/` is a deployed
asset directory added by 1.19.349 and rides inside the same `assets` path.

**`docs/RUNBOOK.md`, correction 3: the production verification checklist cannot be fully satisfied by an
agent.** Its `wp eval-file tests/test-*.php` line is blocked against production by the
`G1-PRODUCTION-WRITE` gate, permanently and by design rather than by an expired token, because a read
and a write are not distinguishable by inspecting an eval command. **The post-deploy suite therefore
runs on staging against the byte-identical artefact**, and the production checks are the read-only verbs
plus a real logged-out browser smoke test. The line is left standing rather than rewritten, because it
describes the verification the project wants. The gap is open and is Andrew's.

**Internal call names removed from this public repository's `docs/`.** Three occurrences across
`RUNBOOK.md`, `CURRENT_TASK.md` and one release record now name the technical role IDs instead. No
customer-facing string changed.

---

## 2026-09-02 - Theme 1.19.349: product-page redesign phase 2, and a live shipping understatement fixed (CYCLE179-LD-349)

Fills the column 1.19.348 emptied.

A new left-column block renders "Look inside" plates and "What is inside" bullets on all six product
surfaces, from one theme registry (`bhp_book_whats_inside()` / `bhp_pdp_look_inside_registry()` in
`inc/book-formats.php`) with an optional `bhp_whats_inside` product-meta override that ships empty
everywhere. ⭐ **The registry was chosen over product meta for three reasons that are each independently
decisive**: `/complete-collection/` has no product record at all, the three hardcover records are never
served, and the coloring product has a different post ID on every environment. 33 new image assets,
md5-verified against the source manifest.

The purchase card is reordered to spec strip, picker, price, CTA, trust line, and **the duplicate price
is removed from the selected format chip, so every distinct price now appears exactly once.**

**A live understatement was corrected on the coloring product page.** The page said shipping starts at
`$1.99` where the cart charges `$2.99`. The sentence is repointed at `bhp_colouring_single_shipping()`
through a new `rail_note`. Proven in a real WooCommerce Blocks cart on staging: `$12.99 + $2.99 =
$15.98`, one shipping method, zero "BookVAULT" occurrences. **No shipping rate, zone, method or tier was
changed on any environment.**

The phone gallery is capped (235px cover, 44px rail) and 36px of top-of-page chrome trimmed, taking the
coloring book's Add to Cart from **257px below** the fold at 375x812 to **33px above**, and the Mariana
title's clearance from 6px to 42px. The desktop card is compacted by 50px, restoring the 1366x768
clearance the reorder had cost.

Three motif-audit strings applied: **the series no longer claims that "stop, breathe, think, choose" is
a habit the books teach.** That is a design-truth correction, not a copy tweak.

New suite `test-cycle179-pdp-349.php`, 173 assertions, all passing; nine existing suites still green.
**Six further copy strings were prepared and deliberately NOT applied, because they are founder gates.**
No product record, WooCommerce setting, price, coupon, stock or shipping configuration was changed.

---

## 2026-09-02 - Theme 1.19.348: product-page redesign phase 1, and a gallery that went blank on resize (CYCLE179-LD-348)

Two defects, both reported from a real device rather than found by reading code.

**Dead space under the gallery.** `.woocommerce div.product` defaulted to `align-items: stretch`, which
inflated the gallery box to the purchase column's height. Set to `start`: **1,072px of dead space to
0px** on the coloring page, **1,953px to 0px** on Mariana, and 0px on all four product-page types at
1440x900 and 1366x768.

**The main image is now capped against the viewport, not against the image file:**
`min(560px, calc(100vh - 400px))` for the WooCommerce gallery and
`--bhp-stage-h: min(520px, calc(100vh - 415px))` for the chapter-book hero. The coloring image ended
21px below the fold at 1440x900 and 152px below at 1366x768; it now ends 149px and 153px **above**.

**F3, the gallery that blanked after a resize, was fixed in CSS with no JS added.** FlexSlider's stale
inline slide widths and translate are retired above 901px and the slides are driven by its own
`.flex-active-slide` class instead.

The desktop thumbnail rail is one non-wrapping row with all seven tiles visible (it was two rows), and
the mobile rail is one horizontal scroller on `body.bhp-gallery-multi`. 22px of mobile spacing takes
Mariana's Add to Cart from 16px below the fold to 6px above at 375x812. Columns move from 613.6/521.6 to
592.8/592.8 at 1440.

New suite `test-cycle179-pdp-348.php`, 37 assertions, all passing; 7 existing suites still green.

⛔ **NOT done, and named rather than buried:** the coloring book's Add to Cart was still 257px below the
fold at 375x812 (improved from 337px) and needed a content decision, which 1.19.349 then made. **The
sticky purchase column was asked for and deliberately not built, because measurement showed it would be
a no-op.** No copy, no bundle, no WooCommerce data or setting, no production write.

---

## 2026-09-02 - Theme 1.19.347: internal call names scrubbed from a public repository, and the phone gallery widened for multi-image products (CYCLE178-LD-347)

**264 occurrences of nine internal call names across 89 files**, replaced with the technical role IDs
they resolve to. ⭐ **This closed a LIVE exposure, not a hypothetical one:** `assets/js/` is served
unminified and two of its comments carried call names, and one test suite was **already failing on
staging** for exactly this reason. That assertion now passes.

**No call name appeared in a customer-facing rendered string.** All 264 were comments, docblocks, test
labels or fixtures, so no customer-visible text changed.

⭐ **The most instructive finding: three test files whose job is to detect call names spelled all nine
out as regex literals. The guard against publishing them was publishing them.** Their patterns are now
assembled at runtime from split literals. The compiled regex is character for character unchanged, and
each carries an integrity precondition, so a later tidy-up of a split literal cannot leave an assertion
that passes while checking nothing.

Five founder-verbatim quotations contained a call name. Removing it is required on a public surface and
altering a quotation is forbidden. Resolved with marked square-bracket editorial substitution plus a
note at each site, and **flagged for ratification rather than settled in the lane that found it.**

**Second item, the phone gallery.** `book-formats.css` capped the product gallery at `max-width: 150px`
under 782px. Correct when the gallery held one cover, wrong now that the coloring page carries six
interior previews, which are the purchase argument for a coloring book and were unreadable on a phone.
`bhp_body_classes()` now emits `bhp-gallery-multi` when the product has gallery images, and one scoped
rule takes the gallery to 100% and the thumbnails to 56px under 782px. ⭐ **A server-side class rather
than `:has()`**, which fails silently on Firefox below 121 and pre-2023 Safari, where the phone would
keep the 150px cap with nothing in the DOM to explain why. Keyed on gallery metadata, not on a product
ID. Verified on staging at asserted `innerWidth` 375: gallery 343px (was 150), seven 56x56 thumbnails,
no horizontal overflow, console clean.

⚠️ **Call names remained in `docs/` after this release**, which is public and ships in the deploy
artefact. That was out of scope for this pass and is closed by the 2026-09-02 entry at the top of this
file.

---

## 2026-09-02 - Theme 1.19.346: the product page told the coloring book it was a chapter book (CYCLE178-LD-345-PDP-LINE, CYCLE178-LD-346-FOLLOWUPS)

**The value-proposition line under the H1 is now product-aware.** The coloring title rendered the
chapter-book sentence, which describes an object that book is not. It now renders a coloring-specific
line. Chapter-book product pages are unchanged, verbatim.

⭐ **Classification uses the existing SKU-keyed coloring resolver rather than a product ID, and the
reason is the useful part: the coloring product has a different post ID on each environment** (618 on
production, 4065 on staging, same SKU). **An ID check would have been inert on staging**, which is to
say it would have passed QA by never running. Degrades to the previous sentence when the resolver is
unavailable. New suite `tests/test-cycle178-pdp-value-prop.php`, 21 assertions, covering the coloring
page, the regression on every non-coloring page, and the degrade path.

**A shipping-copy contradiction on the same page was resolved.** The shipping and returns link read
"flat-rate shipping" while the format card directly above it read "shipping starts at $1.99, three or
more books ship free". ⭐ **Both sentences were true of different things**, which is exactly why the
contradiction survived: the zone method **is** a single flat rate, and what the customer actually pays
is **tiered** by the bundle plugin. The string moved out of an inline literal in `functions.php` into
`bhp_book_pdp_shipping_link_text()` in `inc/book-formats.php`, beside the rest of the store's shipping
copy. **Being inline is why it escaped the 2026-08-02 correction that fixed its neighbour.** The free
clause is gated on a live engine read. **No shipping rate, zone, method or tier was changed.**

**Test correction, and the second half is the serious one.** Two CTA-href assertions in
`tests/test-book-formats.php` required `href` to be the attribute immediately following
`data-bhp-format-cta`. Release 1.19.281 had inserted three attributes between them, so the positive
assertion failed on all three titles **and the negative assertion PASSED VACUOUSLY**. Both now extract
the anchor's opening tag and assert against the decoded href, and a third assertion guards anchor
presence **so that none of them can pass by finding nothing.** Suite 193/193. No product code was
changed for this item.

---

## 2026-09-02 - Theme 1.19.345 / plugin 1.8.79: one arithmetic for every blog ask, and shelf counts off the command line (CYCLE174-LD-345)

**Blog ask placement is now derived from post depth rather than from fixed ordinals.** The small email
ask sits at one third and the book rail at two thirds, both measured in clean top-level paragraphs
rather than in fixed positions or visible-text bytes, with a minimum two-paragraph gap so the two can
never render adjacent. The whole rule lives in one pure, testable function.

**Every superseded rule is preserved in place with a dated note rather than deleted:** the
band-after-paragraph-5 ordinal survives as the fallback for when no clean paragraph sits at the target,
and the previous visible-text arithmetic is kept callable for one release. **A short post loses the
RAIL, never the email ask**, which is the deliberate half of the short-post rule: under nine clean
paragraphs the band goes after paragraph two and the rail appends at the end of the article.

**No new signup path, magnet, context, tag, popup, storage key or analytics prefix. Funnel isolation is
untouched.**

**Plugin 1.8.79 adds a Signed Copies admin screen under WooCommerce.** ⭐ **The gap it closes is
operational, not technical: until now the only way to set a shelf count was a WP-CLI line over SSH, and
the person who knows how many books are on the shelf is the person holding the books. A count that needs
a terminal goes stale, and a stale count is a false scarcity claim on a storefront.** Follows the
existing dashboard page exactly: capability checked on both the menu and the save handler, nonce and
referer checked, redirect after save, admin-only load, zero front-end output.

It writes **exactly one option**. No product record, no stock or backorder field, no price, no coupon,
no shipping setting. The WP-CLI route is unchanged and still documented as the fallback. ⚠️ **The
gross-versus-net warning is rendered on the screen beside the fields, because that is where the mistake
gets made.**

---

## 2026-08-31 - Theme 1.19.344: blog body links made visible, and a third ask for one lead magnet removed (CYCLE173-LD-344, CYCLE173-LD-344B)

Two founder orders of 2026-08-31, **both relayed rather than witnessed by the implementing session, and
both recorded as relayed.**

**Item 1, in-body blog link visibility.** The brief expected a light-green rule to be winning the
cascade. Measured on the live production post instead, `.entry-content a:not(.btn)` **was** the winning
rule at `rgb(23,63,47)`, and there is no light green anywhere on the page. The two real defects were
different: `text-decoration-line` computed to `none`, so the rule was setting the colour of a line that
was never drawn; and the link was near-indistinguishable from body copy.

A new `--expedition-link: #2f6949` is the brightest green in the brand family that still clears AA on
the darkest cream in circulation, at **4.84:1 on `#efdcc1`**. `#2A7050` was tried and rejected at
**4.44:1**. ⭐ **Both ratios were recomputed independently for this entry** from the shipped hex values
(WCAG relative luminance) and both match the implementing lane's figures. A real 2px underline is drawn
in the link's own colour at `.16em` offset, with `text-decoration-skip-ink: auto` stated rather than
left to the user agent. Font weight was considered and deliberately not changed, because a 600 weight
reflows every published post and the order asked for two things, brighter and underlined.

Scoped to `.post-content.entry-content`, a class pair unique to `single.php`, so pages are untouched.
The rail, capture band and mid-capture are injected into `the_content` and therefore fall inside that
scope, so they are pinned back explicitly in both resting and hover state.

**Item 2, the redundant end-of-post kit box.** Live DOM order was: book rail, then a free-chapter
capture, then a free-kit box. **Two consecutive boxes for one lead magnet.** Suppressed at its call site
in `related-content.php` behind a two-way filter. `BHP_CTA_Engine` and the shortcode are **byte-
untouched**, so every other surface keeps 1.19.343 behaviour. The two-asks doctrine comment is
superseded in place rather than deleted: its ask counter never counted a contextual-CTA block, which is
how a third ask for the same magnet shipped while the assertion read exactly two.

### ⚠ Two corrections to this release, kept in the record rather than folded away

1. **The claim that the rendered ask count was "still two" was an inference from a diff, not an
   observation**, because the lane that wrote it was killed before it could open a browser. Measured
   afterwards in the live staging DOM at 1.19.344, in a real browser at both 375px and 1440px width,
   the page carried **three** asks for one lead magnet, two of them under a byte-identical headline,
   with `article input[type=email]` returning 2.
2. **The correction note itself then failed two of the theme's own guards**: one because it named
   internal role call names, which are forbidden on a public surface, and one because it reproduced a
   funnel storage-key literal. Both were rewritten. ⭐ **Both guards did exactly what they exist to do,
   and caught it in the same sitting.**

**Test correction in the same lane.** `test-cycle173-blog-link-visibility.php` section 5.5 failed on
first execution, reporting a complex `:not()` in the blog-link path. The CSS was never wrong: the match
was inside `style.css`'s own explanatory comment, which quotes the very construct it is explaining that
it avoided. After stripping comments, all twelve `:not()` in real selectors in that path are
`:not(.btn)`, which is simple and safe on Safari below 16.4. **No CSS was changed to make a test pass.**
The comment strip is now applied to every assertion in the file, not just 5.5, and ⭐ **that is the
larger half of the fix**: 5.5 is a negative assertion, so a comment made it fail loudly, whereas the
positive assertions would have passed **silently** with no rule present.

Commits: `159ff91`, `a3f80d6`, `5b6650a`, `e2d98cc`.

---

## 2026-08-31 - Theme 1.19.343 / plugin 1.8.78: begin_checkout was built, and it was losing a race (CYCLE173-LD-CONSENT-CHECKOUT)

GA4 carried `view_item`, `add_to_cart` and `purchase` but **no `begin_checkout` at all**. The event was
never missing from the code. The side-cart checkout button is a real link to `/checkout/`, and its
handler did an asynchronous Store API `getCart()` and **then** pushed `begin_checkout`, both racing the
browser's navigation away from the document. On a normal connection the navigation wins. ⭐ **The event
was emitted at the one moment an asynchronous emission is least likely to survive.**

`begin_checkout` now fires on `/checkout/` page load from `bhp-checkout-events.js`, where nothing is
unloading. It is latched to exactly once per page load, guarded on a non-empty cart, and driven by an
unconditional cart read rather than by hoping Blocks makes a request. The drawer's click event is
**renamed** to `side_cart_checkout_click` rather than left in place, because keeping both would
double-count every side-cart customer the moment the reliable one started arriving.

**Second defect, left behind by 1.19.302 and 1.19.312: the attribution gate.**
`assets/js/bhp-attribution.js` asked for a **stored choice**, while GA4, the Meta pixel and
WooCommerce's own sourcebuster all run on the site's consent **state**. Since 1.19.309 the banner is
deliberately suppressed outside the EEA and UK, so a US visitor **cannot** record a choice. ⭐ **The
condition was not merely strict, it was unsatisfiable, and neither attribution cookie had ever been
written for anyone.** Verified on production in a real browser on 2026-08-31 at 1.19.342: the GA4, Google
Ads, Meta and `sbjs_*` cookies were all present, while `bhp_attr_first` and `bhp_attr_last` were both
absent.

The gate now reads the same shipped `window.bhpConsentRegion` object the rest of the consent system runs
on. No second region list, no second heuristic. Precedence matches `BHP_WPConsent_Bridge` exactly: an
explicit stored choice wins in both directions, then GPC, then the region default, then no capture.
Every uncertain path still returns false. **No new cookie, no new field, no personal data.**

### ⛔ Two briefed items were stopped, not implemented

1. **"Make the consent banner display" would reverse Andrew's own ruling.** Non-display outside the EEA
   and UK is theme 1.19.309's designed behaviour, on his report that the consent bar was still firing on
   new browsers and his ruling to go with US law. Section 4 of the new suite guards against a future
   release reversing it by accident.
2. **The `returnMethod: ReturnByMail` rider is refused.** The live returns page says, verbatim, that
   because every book is printed on demand there is nothing to send back. `ReturnByMail` would publish a
   claim the store's own policy contradicts. The omission was already deliberate and documented in
   `functions.php`.

**Build correction in the same version, and it was found by byte-diffing the deployed staging theme
against the pre-deploy backup rather than by reading the build command.** The raw diff reported roughly
250 files changed; **the real number was 4.** Everything else was CRLF versus LF, because this
workstation runs `core.autocrlf=true` while both environments run LF. Separately, `assets/covers/` holds
117 cover-design source and proof masters, referenced by **zero** PHP, JS or CSS files and present on
**neither** environment, and a repo-built ZIP was silently adding all 117 to staging. That directory now
lives under the same rule `tools/` already does: **artefacts deploy, sources do not.**

**Test correction, and the sequence is the useful part.** The `begin_checkout` count assertion used
`substr_count` over the raw file and matched the `pushEvent('begin_checkout', ...)` literal quoted inside
the new 1.8.78 docblock, reporting a double emission that does not exist. Comments are stripped before
counting now, because the claim was always about emissions the browser executes, so the instrument has
to look at exactly that. The corrected assertion is **stricter** than the original: it also pins which
file the single emission lives in. ⭐ **This failure was found on the first staging run and then found
again by the artefact rebuild**, because the fix had been copied straight to staging and never
committed, so the ZIP built from HEAD carried the pre-fix suite. **Deploying the artefact rather than
hand-copied files is what surfaced that, which is the argument for the whole-artefact rule.**

Commits: `0c67302`, `7ca04e2`, `a675a18`.

---

## 2026-08-31 - Plugin 1.8.77: a live money defect on production, caused by an array key (CYCLE172-LD-COUPON-DEFECT)

Two of three audience coupons showed as "applied" on a three-paperback cart and charged **35.97**, which
is **3.98 more than applying no coupon at all** and **7.18 more** than the third coupon on a
byte-identical cart. Real money, on production, with no error surfaced anywhere.

The cause was neither the coupon records (byte-identical) nor the coupon code strings (nothing in the
codebase branches on one). `WC_Cart::remove_coupon()` unsets **without reindexing**, and `apply_coupon()`
appends at max-key plus one, so an `individual_use` swap inside a single request leaves the
applied-coupons array as `array(1 => 'code')`. Three call sites in `bundle-cart.php` read index `0`, a
key that no longer existed, and each silently produced no fee:

- `bhp_audience_coupon_savings_amount()` produced no savings fee
- `bhp_audience_coupon_apply_savings_fee()` produced no savings fee
- `bhp_bundle_apply_discount_fees()` produced no "Bundle Savings" fee

Meanwhile `bhp_audience_coupon_zero_native_discount()` reads the coupon **object** rather than the array,
so it kept correctly zeroing WooCommerce's own 10 percent. ⭐ **That is why the total discount read zero
and nothing anywhere reported a problem: one half of the mechanism kept working perfectly.**

**Fix:** a single normaliser, `bhp_cart_applied_coupons()`, returning `array_values()`, with every reader
routed through it. Reproduced deterministically on staging before the fix (key 0 gives both fees; key 1
gives none, plus an "Undefined array key 0" notice).

**Shipped in the same deployable:** `bhp_bundle_nonce_input()` no longer emits `wp_referer_field()`, and
`bundle-shop-series.php` passes `$referer = false`. On a page-cached site that hidden field publishes one
visitor's click and campaign parameters to the next visitor, which was observed live on production.
Nonce verification is unaffected, because `wp_verify_nonce()` never reads `_wp_http_referer` and this
plugin has no `wp_get_referer()` caller.

New suite: `tests/test-cycle172-coupon-key.php`. A follow-up commit corrected the suite's own source
assertion so it pins that the normaliser itself calls `get_applied_coupons()` exactly once. A third
commit **redacted a coupon code literal from a source comment, because this repository is public**, and
no coupon code literal is reproduced in this entry for the same reason.

Commits: `8aa099e`, `ba04e0b`, `5b51585`.

---

## 2026-08-31 - Theme 1.19.342: four funnel-observability leaks closed (CYCLE172-LD-FUNNEL-FIX)

Closed four defects found by the funnel-observability audit of 2026-08-31. The headline one is
architectural rather than cosmetic: an attribution field was being manufactured into rendered HTML from
the query string, which **on a page-cached site means one visitor's click IDs can be served to the next
visitor.** ⭐ **The fix does not tighten the condition, it removes the mechanism**, so the edge cache
stops being able to poison anything regardless of URL shape.

**The accompanying test correction is the more instructive half and is recorded rather than folded
away.** The existing suite (`test-cycle169` sections 7.9 and 7.9b) asserted that a clean URL emits no
field and a click-ID URL emits one carrying the value. Both assertions passed against a fresh PHP
render, both were true, and both were irrelevant to what visitors actually received: ⭐ **the suite was
asserting that the poison was correctly manufactured.** They are replaced by the invariant that makes
the cache irrelevant, namely that no query string of any shape may put a value into the rendered HTML.
The superseded assertion text is preserved in place rather than deleted.

Two further source-text assertions were matching the filler's own documentation. They grepped for
`localStorage` and `document.cookie` and hit comments **saying the code uses neither**. Comments are
stripped before the check now. Caught on staging, not by reading the diff.

Version pin moved 1.19.341 to 1.19.342 by this lane, which owns that pin. Four stale pins owned by other
lanes were deliberately left alone.

Commits: `486799e` (theme 1.19.342), `3b9858e` (stray PHP opener in the school-read-alouds edit),
`f07a5af` (test assertion inversion).

---

## 2026-08-28 — STAGING ONLY: theme 1.19.314 + bundle plugin 1.8.76 — the retailer ordering route, and school-visit backorders

⛔ **STAGING ONLY. NO PRODUCTION WRITE OF ANY KIND.** Production was read **read-only** and is
**theme `1.19.312` / plugin `1.8.74`**, verified live with `wp theme list --status=active` and
`wp plugin list`. Staging is **1.19.314 / 1.8.76**, verified the same way after the install.

Combined production candidate, both components in one plan:
`Business OS/ANDREW-REVIEW/2026-08-28/PROD-CANDIDATE-CYCLE168-theme-1.19.314-plugin-1.8.76/`.
It **supersedes** the standalone 1.19.313 checkout-opt-in candidate, whose two files ride inside it.

**Theme `1.19.314`** (founder items 363, 364, 365, 366 + the retailer funnel review's D1/D2/D3):
the retailer hero's primary CTA becomes an **ordering route to ipage** with an **ungated sell-sheet PDF**
beside it, both above the fold at 1440x900 and at 375x812 (measured, `innerWidth` asserted);
hero spacing tightened through a **scoped** `--tight` modifier that touches no other audience page;
the **sixth ISBN** `9798996810833` opens and every "still being set up" line is removed;
`Imprint: Brave Hearts Publishing LLC` is printed beside the ordering route;
`ipage` becomes a real link; and a sitewide footer link finally makes the page reachable by a human.
Files: `page-audience-retailers.php` · `footer.php` · `inc/retailer-trade-terms.php` ·
`assets/css/audience-landing.css` (+ rebuilt `.min`) · `assets/downloads/bhp-retailer-sell-sheet.pdf` (new).

**Bundle plugin `1.8.76`** (founder item 363, *"I think we allow backorders"*): "the shelf is empty" and
"the parent may not buy" become two different facts. `bhp_visit_shelf_title_is_exhausted()` is the physical
shelf and governs the counter; `bhp_visit_shelf_title_is_closed()` keeps its name and every caller, and now
relaxes when backorders are allowed. **Default ON**, one WP-CLI line to reverse, no deploy.
⛔ **Not a WooCommerce backorder setting**: no `_stock`, `_stock_status`, `_manage_stock` or `_backorders`
value is read-modified or written on any environment. New file `includes/school-visit-backorder.php`.

**Tests.** Every suite covering the changed code is green on staging: retailer funnel **184/0**,
shelf-stock gate **358/0**, stock suppression **112/0**, archive surface **57/0**, signup modal ALL PASS,
CRO-iterate5 ALL PASS, style minification ALL PASS. ⚠ Test sections were **updated deliberately, never
deleted** — the sold-out refusal seams now run with backorders explicitly OFF (a real supported mode) and
new sections exercise the shipped default.

### ⚠ Three findings recorded rather than absorbed

1. **`docs/security-investigation-nlo-finance-redirect-2026-07-09.md` reached four staging builds.** It is
   `export-ignore` in `.gitattributes` because shipping it once triggered SiteGround malware quarantine
   (2026-08-04). Working-tree ZIP builds **do not honour `export-ignore`**; only `git archive` does, and it
   cannot be used while the tree carries uncommitted lane work. Excluded from the artefact and removed from
   staging. **Any future working-tree build must exclude it by hand.**
2. **Internal agent aliases are in 20+ shipped files** on a **public** GitHub repo (standing rule §14
   constraint 5). `test-cro-iterate5.php` §7.3 only checks three of them. This lane removed every alias it
   introduced and left the pre-existing ones for a lane of their own.
3. **`assets/covers/` — 439 MB, untracked, not on either environment.** A working-tree ZIP build picks it
   up by default; the first build of this release was **478 MB** before it was excluded.

---

## 2026-08-03 (newest) — STAGING ONLY: theme 1.19.165 — native review system, review-pass fixes

⛔ **STAGING ONLY. NOT DEPLOYED TO PRODUCTION.** Production was re-verified read-only after this work and is **theme `1.19.161` / plugin `1.8.19`** — see the FOURTH CORRECTION block at the top of `PROJECT_STATE.md`. **Staging is at least four theme releases ahead of production and they would ship as one package.**

Branch `feature/review-system-1.19.162`, three local commits. **Unpushed and unmerged.**

**Files:** `inc/reviews.php` · `assets/js/reviews.js` · `assets/css/reviews.css` · `template-parts/reviews/review-form.php` · `template-parts/reviews/review-section.php` · `template-parts/reviews/standalone-review-page.php` · `tests/test-reviews.php` · `style.css` (`Version:` line only). All eight confirmed present in the working tree; `style.css` reads `Version: 1.19.165`.

**Tests: 224 passing, 0 failing, 0 errors.**

Lineage: `1.19.162` (feature) → `1.19.163` (three QA fixes) → `1.19.164` (main-landmark fix) → **`1.19.165`** (this entry — the review-pass fixes).

### ⚠️ What this entry does NOT record, and what would complete it

**The eight individual fixes in `1.19.165` are not enumerated here, because no source available to the session writing this entry enumerates them.** The staging deploy, the file list, the version and the test result are documented; **the fix-by-fix breakdown is not.** ⛔ **It is left blank rather than reconstructed** — an invented fix list in a changelog is worse than a gap, because it reads as a record.

**What would complete it:** the three commit messages on `feature/review-system-1.19.162` (`git log`), or a `RELEASES/` document from the session that shipped it. **No `RELEASES/` document exists for the review system at all** — that gap covers `1.19.162` through `1.19.165` and is named here rather than filled.

### Staging runtime actions taken with this build

Full-ZIP `wp theme install --force` to **staging**, staging cache purge, staging WP-CLI verification. Three staging QA test comments (`136`, `137`, `138`) were deleted **on staging only**, their full records captured first; zero remain, verified by query. ⛔ **No production write of any kind.** ⛔ No WooCommerce product, price, stock, coupon, shipping, tax, payment or checkout change on any environment — the three products' `comment_status` and `review_count` were re-read afterwards and are unchanged. ⛔ No email was sent to any recipient.

### ⚠️ Route availability — the sequencing fact that matters before anything links to it

The `/review/<slug>/` route exists on **staging only**. **Production 404s it**, and production PDPs carry zero `id="reviews"` and zero `aggregateRating`. **Any external link pointing at a review URL can only point at production, so nothing may link to it until this ships.**

⛔ **Production deployment is not approved by this entry and was not requested by it.**

---

## 2026-08-03 — PRODUCTION RELEASES: theme 1.19.156 (transactional email copy layer) and 1.19.157 (Bookvault dispatch tracker)

**Production shipped three times on 2026-08-03.** The 1.19.155 push is recorded in the entry below this one; these are the two that followed it. **Current production: theme `1.19.157`, bundle plugin `1.8.16`** — verified live by HTTP (11 theme assets at `ver=1.19.157`, 4 plugin assets at `ver=1.8.16`).

Records: `RELEASES/PRODUCTION_RELEASE_1_19_156.md` · `RELEASES/PRODUCTION_RELEASE_1_19_157.md`.

### theme 1.19.156 — the transactional email COPY layer (`237d71b`, `ab11990`)

Per-email copy for **E1 through E7**, through a theme filter layer plus six template-override pairs. **17 files, +1,869 / −59.** The email **config** layer (colours, masthead, site title, auto-sync, cancelled-order enable) was already live and was not touched.

- **E1** processing — H1 becomes "Your order is confirmed"; stock filler suppressed; **body copy and subject untouched**.
- **E2** completed — Variant A, "Your books have shipped". **True only under the mark-complete-after-dispatch operating rule**, and it states plainly that no tracking number exists.
- **E3** refunded — full and partial branches. **E4** on hold — neutral default; payment-specific wording **stays blocked** pending evidence. **E5** failed — the suite's only button; the unsourced pending-authorisation line **omitted, not softened**. **E6** note — nearly empty by design. **E7** cancelled — new template pair, Variant A.
- Every HTML template has a **plain-text twin carrying the same promises**. **Zero em dashes.** No duration, delivery-date, tracking, coupon, upsell or review-ask claim anywhere. `php -l` clean on all 17 files (PHP 8.2.33).

**A defect found by rendering rather than by reading**, and fixed in the same release: the fulfilment footer sentence landed in all seven plain twins and **none** of the HTML siblings. Root cause — `emails/email-footer.php` applies `woocommerce_email_footer_text` with an `$email` argument, but `WC_Emails::email_footer()` takes **no parameters** and renders the template with **no arguments**, so `$email` resolves to `null` on every render and the filter can never be scoped by email id. Switched to the `woocommerce_email_footer` **action**, which does receive the email object. ⭐ **A filter whose signature advertises a parameter the caller never passes — reading the template alone would have confirmed the wrong approach.** Also repaired a pre-existing plain-text defect where `wp_strip_all_tags()` deleted the footer's `<br />` instead of breaking the line, rendering the site title and street address run together.

**Deploy:** artefact md5-verified on the server before install; entry list diffed against the previous verified artefact (**0 dropped, exactly 13 added**); rollback snapshot taken **first**; cache purged. **No production option write** — the email config was read only.

### theme 1.19.157 — the Bookvault dispatch tracker (`652da0f`, `f6a781e`)

A 3-hourly WP-Cron poll of the fulfilment API for orders still in processing, completing them **only on an unambiguous dispatch signal**. **5 files, +2,086 / −1**, including 1,008 lines of tests. ⭐ **Its purpose is to make E2's "Your books have shipped" a fact rather than a promise somebody has to remember.**

- ⚠️ **Ships in DRY mode: writes no meta, no note and no status change.**
- Reads `Progress.Status` and **never** `Order.Status` — two fields, same name, only one of them is fulfilment.
- Requires **three** conditions together: `IsDispatched === true`, a real `Dispatched` timestamp, and a post-dispatch `Progress.Status`. **Any error, missing field, unknown value or self-contradiction logs, skips and retries.** ⭐ **The failure mode is "do nothing", never "email the customer anyway."**
- Two idempotency guards run **before** any API call. Kill switch, dry-run option and filter, and a WP-CLI command.
- ⛔ **Never logs, echoes or stores the API credential.** The credential was generated and installed by the owner, in the owner's own terminal, and **is held by no agent.**
- `f6a781e` fixed ten WP-CLI synopsis warnings and — the better catch — **a test suite that was passing while 23 of its own assertions could never reach the code they targeted**, because the no-credential guard halted the run.

**Arming:** deployed with backup and md5-verified artefact; post-deploy status **dormant / dry / no-credential**, which is correct for a build shipped without its credential. The owner then installed the key and the **first authenticated live read** returned both open orders at `SentToPrint` (`examined=2 skipped=2 errors=0`).

⛔ **The tracker has never completed an order and has never caused a customer email.** Live-fire test expected **~2026-08-11 to 08-12**; switching it live is a separate supervised act.

### Documentation and honesty notes

- ⭐ **The 1.19.156 release-record gap is closed.** It had been deliberately left open on the ground that reconstructing a QA narrative from another session's artefacts would be a fabricated verification. **That reasoning was right and is respected in how it was closed:** the record is built from the builder's **verbatim commit messages**, `git show --stat` counts and the builder's **own writer-lock closeout table**, and it names every unverified item as unverified. ⛔ **It is recorder-authored from builder evidence, and it says so on its face.**
- ⚠️ **`wp theme list --status=active` was not run for this entry's verification** — the recording session holds no SSH credentials. The HTTP enqueue-version check is strong live evidence and is **not** the definitive instrument.
- ⚠️ **An identifier collision is registered and unresolved:** an operations record describes the tracker's payload verification as closing `CYCLE142-LD-16`, but that identifier belongs to an **open homepage image-weight defect** in the capstone technical audit (as do `LD-17`, `-18`, `-19`). ⛔ **None of those four is closed.** `KNOWN_ISSUES.md` therefore records the tracker item with **no `LD` number attached.**

---

## 2026-08-03 — PRODUCTION RELEASE: theme 1.19.155 + bundle-pricing 1.8.16, two product-record corrections, the privacy-policy sentence, cart-thumbnail regeneration and the transactional-email configuration

**Production moved 1.19.142 → 1.19.155 and plugin 1.8.10 → 1.8.16 in a single push.** Thirteen staging builds are collapsed into one deployment; the per-build detail is below under "What shipped, by build". Release record: `RELEASES/PRODUCTION_RELEASE_1_19_155.md`.

**Six independent layers. They roll back independently. Do not conflate them.** Rollback artefacts for every layer were taken on the server immediately before the first write and are named in the release record.

### Verified live on production after the push — read from the running system, not from any build report

`wp theme list --status=active` reads **1.19.155**. `wp plugin list` reads bundle-pricing **1.8.16**. `woocommerce_thumbnail_cropping` = **uncropped**; product 333's `woocommerce_thumbnail` resolves **300×460**, not 300×300. Product **333** `post_content` is **1888 bytes** with `Paperback. Illustrated`=0, `Printed and shipped by`=0, `Printed and fulfilled by`=1; product **15** is **1749 bytes** with the same three flags; product **12** is **2210 bytes** and **still carries both legacy strings, untouched by design**. Page 3 (privacy policy) is **8563 bytes**, md5 `2a274067592a2d8ec341283e417904ff` — the exact value the guarded script predicted before it ran. `blogname` length is **23** (the trailing space is gone). `woocommerce_email_auto_sync_with_theme` = **no**, `woocommerce_email_base_color` = **#071522**, `woocommerce_email_header_image` resolves to an uploaded attachment. The `customer_cancelled_order` email reports **ENABLED**.

Externally, over HTTP against the live site: enqueued asset versions read **`ver=1.19.155`**; the leaked homepage source comment returns **0 occurrences**; the Mariana product page opens on **hardcover** (`data-bhp-format-initial="hardcover"`) while an explicit `?bhp_format=paperback` URL still opens on **paperback**; `data-bhp-quiz-autoopen` is **`false` on `/complete-collection/`** and **`true` on `/` and `/books/`**; the privacy policy renders the new cookie-consent sentence **once** and the superseded sentence **zero** times; the homepage "What Families Are Saying" section renders exactly **one** "Get the collection Here" button and **zero** of the two removed link clusters.

### A. Theme 1.19.142 → 1.19.155

Deployed from the ZIP QA'd on staging — **356 entries**, md5 verified on the server before `wp theme install --force` ran, with an explicit pre-install assertion that the archive contained the `woocommerce/` template overrides and both test suites. That assertion exists because `--force` **deletes the theme directory before extracting**, so a short archive silently deletes live files. See layer F.

Both theme test suites were re-run against the installed production code and passed. `wp eval` fatal check passed. Cache purged.

### B. Bundle-pricing plugin 1.8.10 → 1.8.16

**One file and one version constant** (`includes/bundle-shortcode.php`), ordering the hardcover bundle offers above the paperback offers. **No pricing, discount, shipping-tier, catalog, nonce or handler change of any kind.** The plugin's own test suite passed against the installed production code.

⚠️ **Recorded as a discrepancy rather than smoothed over: the surface this plugin change targets does not exist on production.** `/book-bundles/` is a published page on staging (ID 356) and returns **HTTP 404 on production**, where ID 356 is an unrelated attachment. The plugin bump is inert on production rather than wrong, and it was verified on staging — but the change's stated purpose cannot be observed on the live site, and the release verification step for it cannot pass. Open item, not resolved here.

### C. Product records 333 and 15 — `post_content` prose only

Two records, each edited by **one exact-string replacement derived from production's own content**, not copied from staging. That distinction matters: production and staging diverge in paragraph markup on these records for reasons unrelated to this change, and copying staging over production would have rewritten an entire live product description to alter one sentence.

- **333** (Mariana paperback): the format sentence removed and the fulfilment sentence standardised — 1886 → **1888** bytes.
- **15** (Everest paperback): the redundant format sentence removed — 1773 → **1749** bytes.
- **12** (legacy Mariana record) was **deliberately not touched.** It still reads "Printed and shipped by Lulu" and names the former print vendor. A global regex would have caught it; every replacement here was exact-string and guarded per post ID against `post_type` and exact `post_title`. Whether record 12 is live, legacy or a duplicate is an open owner question.

**No price, SKU, stock, variation, shipping class, tax class or WooCommerce setting was written by this layer.** Product records create no revisions, so the pre-edit `post_content` of 333, 15 and page 3 was captured to files on the server before any write.

### D. Privacy policy — one sentence

Page 3 gained the approved sentence describing the cookie-consent banner, and the superseded sentence was removed. 8470 → **8563** bytes, delta exactly +93, md5 matching the dry run's prediction on both sides. The claim is true of the running site: the consent plugin is active on both environments with Accept All / Reject Nonessential / Manage Preferences controls.

### E. Cart thumbnails and email configuration

`woocommerce_thumbnail_cropping` set to `uncropped` and **92 attachments regenerated**, so cart and checkout line items show uncropped 2:3 covers rather than 1:1 crops.

Email configuration: the theme auto-sync flag disabled **first** (while it was on, WooCommerce silently overwrites the colour options from the theme on save, so the colour writes would not have stuck), then the navy/cream email palette, the masthead uploaded as a real media import with its returned URL verified HTTP 200 **before** the option was written, the site title's trailing space removed, and the customer-facing cancelled-order email enabled.

⚠️ **Not verified, and therefore not claimed: no test order was placed and no transactional email was read.** The configuration is confirmed stored and the email reports enabled; what a real message looks like in a real inbox is unverified.

### F. Repository documentation — the deploy line that would have deleted live files

`RUNBOOK.md`'s copy-paste `git archive` line omitted `docs`, `tests`, `woocommerce` and six top-level files, producing a **180-file** ZIP where the real artefact is **356 entries**. Because `wp theme install --force` deletes the theme directory before extracting, following that line literally would have **deleted the theme's WooCommerce template overrides and both test suites from the live site**. Corrected in this release, with a mandatory pre-install entry-count assertion added next to it.

### What shipped, by build

- **1.19.150** — design-system convergence: one gold family, one button spec (8px / Archivo / 15px), long-form typography reduced to EB Garamond and Cormorant. Homepage "Choose Your Adventure" rebuilt on real covers. Checkout reduced to one order summary and three totals rows. Sticky buy bar on the collection page. Homepage image weight 4,615 → 3,047 KB. Tap targets raised to 44px on quantity, remove, drawer-close and coupon controls. Numeric keyboard on the postcode field. Branded empty-cart state. Duplicate opt-in checkbox removed. Duplicate nonce DOM id removed. Homepage carousel 3 → 8 slides, Kirkus block repositioned, section order revised. Cookie bar 285 → 112px and bottom-anchored. Quiz auto-open suppressed on the collection page. Tax row no longer shown before an address is entered.
- **1.19.151 / 1.19.152** — express-checkout wallet no longer latches hidden when the first paint reports no wallet. Quiz reduced to two questions with four result routes, one of which deliberately captures no email. Cart thumbnail cropping corrected at the source. Retired brand-colour literals and wrong CSS fallbacks repointed to tokens.
- **1.19.153** — per-format shipping copy on the product page, read from the plugin's own tables at render time rather than hardcoded. One redundant photo removed from the educators gallery. Privacy-policy sentence applied on staging.
- **1.19.154** — cart quantity control widened so its 44px children sit inside the container instead of hanging 22px past it. Two link clusters on the homepage replaced by a single centred button. `/find-your-adventure/` header gap corrected on mobile. Checkout order-summary lines gained a Remove control dispatched through the Blocks data store, so totals, shipping and tax recalculate.
- **1.19.155** — a leaked source comment removed from the homepage. Hardcover made the default format across the product page, the five funnel pages and the bundle offers.

**Not done in this release:** no push, PR or merge — the branch carries local commits through `e98cd0f` and is unpushed · no test order placed and no transactional email read · Apple Pay not exercised on a real device · the cart table still overflows a 320px viewport (pre-existing) · `/book-bundles/` absent on production (layer B) · product 12 untouched.

## 2026-08-02 — PRODUCTION RELEASE: theme 1.19.142 + bundle-pricing 1.8.10, the approved content batch, and the "Look Inside" gallery media migration

**Four independent layers. They roll back independently. Do not conflate them.**

### A. Production theme + plugin deploy, under the owner's explicit 2026-08-02 approval

Production 1.19.121 → **1.19.142** and bundle-pricing 1.8.8 → **1.8.10**, deployed from the exact ZIPs already QA'd on staging (verified 151/151 and 44/44 md5-identical to the live staging directories before install, and again to the installed files after). Pre-deploy manifest diff found **zero** production-only files, so nothing was silently erased. No duplicate theme directory. `wp eval` fatal check passed. Cache purged.

Now live: the founder photograph on the homepage; the expanded Kirkus quote on the Complete Collection page; the five-star badge scoped to "on our first two titles"; the unsourced "Printed and shipped in the USA" claim removed.

Rollback: `~/bhp-PROD-backup-1.19.121-prewave4-20260802/` (fresh, taken before any write) and `~/bhp-PROD-backup-1.19.121-20260731/` (pre-existing, still intact).

### B. WooCommerce content batch — staging and production databases

`post_content` and `post_excerpt` only; not carried by any theme ZIP; rolls back via post revisions. **7 edits on production, 6 on staging**, each asserting an exact occurrence count before writing and byte-verified on readback.

- Hardcovers 14/17/20 (both environments): uncertified Lexile "(500L–580L)" parenthetical removed; "grades 2–3" retained.
- Product 20 (both environments): the debunked "20% of the world's oxygen" opener replaced with the copy approved 2026-07-06.
- Products 18 and 20 `post_excerpt` (both environments): the same oxygen myth removed from the short description — which is what the SERP snippet, `og:description`, the JSON-LD description and the cart line item actually show. Replacement uses the approved spec's own defensible framing; **no new claim was introduced.**
- Product 15 (production only): the already-approved 2026-07-09 Bookvault fulfilment sentence restored, so production and staging now match.

Residue across all six products, both fields, both environments now reads `Lexile=0 Lulu=0 oxygen20=0 fifthBreath=0 lungsOfEarth=0 oneInFive=0`, with "grades 2–3" retained on all six. Draft product 12 (`-legacy-lulu`) deliberately untouched. **No `search-replace` was run.** Prices, regular prices, stock, SKUs and the shipping zone/method diffed **unchanged** before and after on both environments.

### C. Repository documentation — shipping truth corrected

Customer shipping is **tiered** per number of books ($1.99/$2.99/$3.99/$4.99 via the bundle plugin's approved tier table); the zone's $3.99 `flat_rate` is the base configuration the plugin adjusts. Owner ruling recorded. Superseded wording retained, not deleted. **No WooCommerce setting was changed.**

### D. "Look Inside" gallery media migration to production — the failed criterion from layer A, now closed

The 2026-08-02 release above shipped with a **failed acceptance criterion, reported as a failure**: the "Look Inside" galleries rendered on staging (7/8/5, Collection 9) and **not** on production, because 0 of 29 gallery media slugs existed in the production media library. `inc/book-media.php` is fail-closed by design, so production rendered no gallery section and zero console errors — it looked exactly as it did at 1.19.121. Not a regression; no rollback was warranted.

A follow-up pass migrated the gallery media set from staging to production under the owner's explicit approval, after he reviewed the artefacts and knowingly approved retaining the current Mariana images temporarily, with the authentic reshoot queued in `docs/ROADMAP.md`. **24 new attachments** were declared for import with the exact slugs the registry resolves, alt/title carried over from staging, followed by a targeted `wp media regenerate` and a cache purge.

Pre-write gates recorded before anything was imported: 24 target slugs checked against production with `wp post list` → **0 hits, all 24**; a broad `post_name LIKE '%-look-%'` sweep → **none**; 24 target basenames under production `wp-content/uploads` → **0 hits**; registered image subsizes **identical on both environments** (11 each). **Production was clean, so no rename, no `-1` suffix and no deletion was required** — a `-1` suffix would have silently broken slug resolution, which is the specific failure this gate exists to prevent.

**Verified live on production, by direct request rather than from any report:**

- `mariana-look-02-whale-chapter-spread-1024x765.jpg`, `mariana-look-03-depth-diagram-brave-learning-1024x765.jpg`, `mariana-look-05-front-cover-765x1024.jpg` and `mariana-look-06-back-cover-765x1024.jpg` all return **HTTP 200** with real payloads (59 KB / 115 KB / 99 KB / 95 KB).
- All three paperback product pages return HTTP 200 and render the `bhp-look-inside` / `look_inside_hero` gallery markup, referencing **47 (Mariana), 56 (Everest) and 32 (The Amazon)** distinct `2026/08` look-image URLs.
- `/complete-collection/` returns HTTP 200 and references **66** distinct `2026/08` look-image URLs.
- Enqueued asset versions on a live production product page read **`ver=1.19.142`** and **`ver=1.8.10`**, which is the theme and plugin version WordPress itself reports.

⚠️ **Recorded as a discrepancy rather than resolved:** a direct HTTP GET of `/wp-content/themes/brave-hearts-theme/style.css` returns a file whose header reads `Version: 1.14.2`, with `Last-Modified: 2026-07-01` and `Cache-Control: max-age=31536000`; a query-string cache-buster did not change the response. Every other live signal — including the enqueued `ver=` values, which the theme derives from `wp_get_theme()->get('Version')` — indicates the active theme is 1.19.142, and the gallery feature rendering on production does not exist in 1.14.2. **The definitive check, `wp theme list --status=active` over SSH, was not run in this pass and is the outstanding action.** Until it is, the served `style.css` is treated as a stale cached artefact and the discrepancy is left open, not explained away.

⚠️ **The surviving QA artefact for this migration is misleading if read alone and must not be quoted as the outcome.** `screenshots-2026-08-02-wave5/qa-results.json` records broken gallery images on production. It was captured **mid-migration** — before the media set finished landing — and the live checks above, taken afterwards, supersede it. It was retained rather than corrected, and the ordering is stated here so a future reader does not read a mid-flight snapshot as a final result.

**Not done in this release:** no push, PR or merge · no GTM trigger or tag created (configuration, owner gate, deliberately skipped) · no WooCommerce price, stock, SKU, coupon, shipping, tax, payment or checkout setting written · no order placed on any environment · full test suite **not** re-run against production (the `tests/` directory is deliberately not in the deploy allowlist; the byte-identical-code result is stated as a limitation, not presented as a production pass) · hardcover product *pages* not directly rendered (they 301 to the paperbacks) and were verified via stored fields plus the paperback pages instead.

**Release records:** `RELEASES/PRODUCTION_RELEASE_1_19_142.md`, `RELEASES/TRUST_AND_CONTENT_CORRECTIONS_1_19_142.md`, `RELEASES/GALLERY_ASSETS_ANALYTICS_1_19_141.md`. ⚠️ **A dedicated release record for layer D was declared but never written** — the session that performed the migration was terminated before it could write one. The reconstruction of what is evidenced is held outside this repository; layer D above is written from live verification and from that pass's own pre-write gate log, and is explicitly **not** a substitute for the release record it never produced.

## 2026-08-01 — Coupons renamed to customer-facing codes; Parent Email 3 still pending

**Permanent policy set by the owner:** customer-facing coupon codes must use recognisable English words tied to their audience or offer, with the number matching the actual discount — no random strings. Values stay out of the public repo (`docs-private` only); IDs and audience labels are the public reference.

- **Renamed on production and staging, IDs unchanged** — 414 (Parent, `publish`), 415 (Educator, `draft`), 416 (Gift Buyer, `draft`); staging 622/623/624 matched. Staging 593 untouched, legacy **346 still enabled**.
- **Rename touched only the code string.** Meta verified byte-identical before/after (percent/10, Collection-only flag, individual use, usage limits, expiry, sale-item exclusion); statuses preserved. A WordPress-generated `_wp_old_slug` row was removed so no retired value lingers.
- **Nine-case cart matrix re-run on production: 9/9 pass** — −$3.20 paperback Collection, −$4.90 hardcover Collection, rejected on single-book carts, for all three coupons.
- **Two proposed names rejected on evidence:** `[GIFT_BUYER_COUPON_CODE_SUPERSEDED]` and `[EDUCATOR_COUPON_CODE_SUPERSEDED]` are two of the three publicly disclosed codes (14 and 16 tracked files; 11 and 12 commits). Owner-approved substitutes were adopted for the Educator and Gift Buyer codes — both verified absent from the repo tree and its full history. Values in `docs-private` only.
- **A prior coupon-rotation abort was completed cleanly first:** Parent Email 3 left byte-identical with its legacy code, journey 89 returned to **Active** with its in-progress contact intact, no coupon disabled/published/renamed/deleted at that point.
- **Outstanding:** Parent Email 3 (campaign 8118781) still references the legacy code and still says *"10% off your order"* instead of *"10% off the Complete Collection"*. Blocked — Mailchimp's Pause & Edit / Actions controls for journey 89 do not respond to automation.
- **Correction:** earlier entries reporting "0 orders" read `wp_posts`; this store uses **HPOS**, so orders live in `wc_orders` — 12 exist, including a real customer order on 2026-08-01 (paid, no coupon). **No coupon has ever been redeemed** (`usage_count` 0 on all four).

## 2026-08-01 — C1/C6 PRODUCTION REMEDIATION EXECUTED (owner-approved)

**Production plugin `brave-hearts-bundle-pricing` 1.8.7 → 1.8.8; three replacement coupons created; Organization discount promise removed. Theme untouched at 1.19.121.** No coupon string entered any tracked file.

- **Baseline re-verified** before any change (theme 1.19.121, plugin 1.8.7, `bundle-cart.php` md5 `e1dce1a5…`, coupons ID 346 only, 6 products, one `flat_rate` zone with no BookVAULT) — matched the reported state exactly.
- **Backup + one-command rollback:** `~/bhp-PROD-C1C6-backup-20260801/`, including a reinstallable `…-1.8.7-ROLLBACK.zip` whose top-level folder is the correct plugin slug, plus a 44-file md5 manifest, coupon SQL, and product/price/lead-magnet manifests.
- **Plugin 1.8.8 deployed** via `wp plugin install --force`; **44/44 files byte-identical to validated staging**; helper and meta-key constant confirmed loaded; legacy code list intact.
- **Regression proven before creating anything:** legacy coupon 346 still rejected on a single book (HTTP 400) and still stacks on a 3-book Collection to the identical **$34.51**.
- **Coupons created:** Parent **414** (`publish`), Educator **415** (`draft`), Gift **416** (`draft`) — meta byte-identical to 346 plus the `_bhp_audience_coupon` scope flag. **9/9 cart tests pass** (−$3.20 paperback, −$4.90 hardcover, rejected on single book). `usage_count` 0 on all four; **0 orders**; carts emptied.
- **Incident, disclosed:** WooCommerce's rejection notice quoted a coupon code in lower case while the output mask only covered upper case, exposing the **Educator** and **Gift** values in a session transcript. Both were Draft, unused and referenced by no email, so they were **rotated immediately** on production and staging; the exposed values now match no coupon anywhere. The **Parent** value was not exposed.
- **Organization journey 93 Email 3 corrected** — the invalid discount promise and its non-existent code removed from subject, preview text and body; replaced with partnership/group-order inquiry copy and no coupon, per the frozen policy. Reload-verified. Journey remains **Paused**.
- **Deliberately not done:** Parent Email 3 not edited and journey 89 **left Active** (editing requires pausing, and resuming requires a real-inbox seed test for which no approved address exists); Gift Email 3 code not substituted (typing the value would place it in the transcript); **coupon 346 not disabled**, per the gate; Gift Buyer and Organization **not resumed**; Retailer still Draft; no test emails sent.
- Production verified healthy after: PHP ok, 6 products, 0 orders, `flat_rate` zone unchanged, `bhp_lead_magnet_pdfs` md5 identical to backup, plugin parity with staging 44/44. Full record: `RELEASES/C1_C6_COUPON_ROTATION.md`.

## 2026-08-01 — C1/C6 coupon rotation: STAGING COMPLETE, PRODUCTION STOPPED AT THE GATE

**Staging plugin `brave-hearts-bundle-pricing` 1.8.7 → 1.8.8. Production deliberately untouched** (theme 1.19.121, plugin 1.8.7, `bundle-cart.php` md5 unchanged, coupon inventory still ID 346 only). No coupon string appears in this repo — replacements live only in gitignored `docs-private/`.

- **`docs-private` confirmed ignored (`.gitignore:23`) and never tracked on any branch.** Backups: `~/bhp-C1C6-backup-20260801-backup/` (coupon post+meta JSON and SQL for both environments, `bundle-cart.php` both, full pre-change staging plugin directory, version manifests) plus `docs-private/…​.bak-20260801`.
- **Stop-gate finding 1 — the Collection-only restriction was never on the coupon.** IDs 346/565/592/593 carry no `product_ids`, `product_categories` or `minimum_amount` meta at all; scope came from a hardcoded literal-code allowlist in a **public, git-tracked** plugin file. Proven on staging: a control coupon with meta cloned field-for-field was **accepted on a single-book cart** (where the legacy code is correctly rejected) and on a 3-book Collection **suppressed the Bundle Savings fee**, making the customer pay **$38.31 vs $34.51** — $0.41 *more* than using no coupon at all.
- **Fix (plugin 1.8.8, staging only):** additive per-coupon meta flag `_bhp_audience_coupon`, one shared resolver used by all four decision points, plus a wp-admin checkbox. Legacy codes unchanged, no migration. Scope now travels with the coupon record, so rotated codes never enter source control.
- **Three replacement coupons created on staging** — Parent **622** (`publish`), Educator **623** (`draft`), Gift Buyer **624** (`draft`). Codes generated server-side into a mode-600 file and transferred without ever being printed; a full repo filesystem scan confirms none appears outside `docs-private`. Control coupon 621 deleted after use.
- **9/9 cart tests pass**, matching the legacy code exactly: −$3.20 stacked on −$3.98 (paperback Collection), −$4.90 stacked on −$4.98 (hardcover Collection), and correct rejection on a single book. Cart emptied; no order placed.
- **Stop-gate finding 2 — live Mailchimp contradicts every document.** Parent (89), Gift Buyer (91) and Organization (93) have been **ACTIVE since 2026-07-17**; Educators (90) is **PAUSED**; **legacy automations 85 and 86 no longer exist**, so the approved "keep 85 active, then cut over to 89" step is moot — 89 has been the sole live parent path for 14 days. Placeholder URLs are already replaced with a real hosted PDF, and Educator Email 2's contradiction is already fixed.
- **Stop-gate finding 3 — three live journeys quote coupon codes that do not exist in WooCommerce**, including **a fourth audience code recorded in no document**, inside the Organization journey which by frozen policy should carry no coupon. Email 3 has sent **0** times on every route, so no subscriber has received a dead code yet.
- **Not done, deliberately:** no production change, coupon ID 346 not disabled, no journey activated/paused/retired, no email body edited, no seed test (three journeys are live and no approved seed address was supplied). Full record: `RELEASES/C1_C6_COUPON_ROTATION.md`.

## 2026-07-31 — PRODUCTION 1.19.121 DEPLOYED (owner-approved)

**Production theme v1.19.112 → v1.19.121, deployed on the owner's explicit current-turn authorization. Staging unchanged at 1.19.121.** Ships five accumulated staging releases as one package: 1.19.117 (Homepage Phase 1a), 1.19.118 (quiz question simplification), 1.19.119 (quiz no-scroll fit), 1.19.120 (hero mobile reorder) and 1.19.121 (screenshot fixes A–G).

- **Stop gate:** local == staging **147/147 byte-identical**; staging 1.19.121; production 1.19.112; **identical 147 path sets** with no production-only orphans, no new files and no build artifacts; exactly **11 content-differing files** (the cumulative 1.19.112→1.19.121 delta); no active writer.
- **Backup:** `~/bhp-PROD-backup-1.19.121-20260731/` — full 1.19.112 theme copy, **147-file md5 manifest**, themes/plugins/products CSVs, lead-magnet options, `page_on_front`, and a 6-product price record; plus tarball `~/bhp-PROD-theme-1.19.112-20260731.tar.gz` (3.8M).
- **Method:** the deploy ZIP was built **from the approved staging build itself** and proven byte-identical to it before install, then installed with the required full-ZIP `wp theme install --force`. No selective file copies, no version change, no new fixes introduced during deployment.
- **Parity proven three ways:** production == staging == local, **147/147 byte-identical** after deploy. Served assets report `?ver=1.19.121` (not cached 1.19.112). SiteGround assets + dynamic cache purged and `wp cache flush` run.
- **Live production QA at 1440×900, 1366×768, 1024×768, 768×1024, 430×932, 390×844, 360×800, 320×568 and 667×375:** hero mobile order eyebrow → H1 → covers → caption → paragraph → CTAs → signature with `domMatchesVisual: true`; **caption 19.1–28.1px clear of the covers, zero overlap**; desktop hero preview still in grid column 2 right of the H1; nav never shows both modes (toggle 44×57 when shown); homepage has **exactly 1 launcher / 1 modal / 1 `[data-bhp-quiz]`**, no audience-gateway, **0 duplicate IDs, 0 broken images, no horizontal overflow**.
- **Quiz on production:** dialog centred at **0.0px vertical / 0.0px horizontal** deviation with 16px radius; Q1 and all four Q2 routes fit with **no scrolling and 0 internal regions**; question→answers gap 32 / 27.2 / 24.0 / 18.9px at 1440 / 1024 / 768 / 360; **submit visible without scrolling for all five offers at every viewport** (one internal scroll region only at 320×568 and 667×375, for the secondary links); partnership route form-free; **16/16 dismissals at 0px page-position drift**; focus trap wraps both directions; **timer, 40%-scroll and one-per-session auto-open all PASS**; the consent gear renders behind the backdrop and is **not clickable through the modal**.
- **Commerce untouched and correct:** all three unified book pages load with four format cards (Paperback $11.99 selected / Hardcover $17.99 / Kindle / Complete Collection $48.99), Mariana / Everest / Amazon covers all load, **Complete Collection still defaults to Hardcover $48.99**, cart and checkout render **0 launchers / 0 modals / 0 quiz**, cart left empty, no purchase made.
- **No data changed:** products, plugins, prices, lead-magnet options and `page_on_front` all diffed **UNCHANGED** against the pre-deploy snapshot. Zero theme-file PHP errors (the only log entries are the long-standing Bookvault plugin warning and WP-CLI `eval` artifacts). Zero browser console errors.
- **Rollback available:** `~/bhp-PROD-backup-1.19.121-20260731/` (one command, see `RELEASES/SCREENSHOT_FIXES_1_19_121.md`).
- **Carried forward, unchanged by this deploy:** the WPConsent **banner** (z-index 900000) can still cover the quiz close button at narrow widths while consent is unanswered — pre-existing, deliberate (consent must stay answerable), and Escape/backdrop still dismiss the quiz. Logged in `KNOWN_ISSUES.md`.

## 2026-07-31 — STAGING 1.19.121: seven screenshot-driven fixes (A–G)

**Staging only. Production remains v1.19.112 and was not touched.** Driven by the owner's three desktop and four real-iPhone screenshots. 7 files changed; parity 147/147. **The quiz behaviour files were deliberately not touched and are byte-identical** (`quiz-modal.js`, `audience-quiz.js`, `audience-quiz.php`, `mailchimp.php`) — no routing, capture, tagging, redirect or trigger change.

- **A — mobile caption behind the covers.** The cover items carry `translateY(24px)` plus a 3° rotation; transforms paint outside the layout box and add **zero** layout height, so the 10px caption margin set in 1.19.120 was measured ~24px above where the artwork actually painted. The stack now reserves 28px of real space for the overhang, so the caption's 18px margin is genuine visible space: **19.1px clear, zero overlap** at 320/390/430, centered, no horizontal overflow.
- **B — mobile dialog was a bottom sheet.** `align-items: flex-end` + `100vh` + top-only radius. Now centered on both axes with `height: 100dvh` behind `@supports`, `max(12px, env(safe-area-inset-*))` padding and 16px radius on all four corners. **0.0px vertical and 0.0px horizontal deviation from the visual viewport at all 12 viewports**, including after an automatic open and after an orientation change while open.
- **C — question sat on the answer grid.** A modal-scoped `margin-bottom: 10px` at (0,3,0) outranked the component rule, so 10px is what shipped. Now `clamp(1.125rem, 0.9rem + 1.25vw, 2rem)`: **32 / 27.2 / 24.0 / 18px** at 1440 / 1024 / 768 / 390. No question screen scrolls at any viewport; answer text never below 17px, cards never below 46px, 16–20px clear below the last control.
- **D — result too tall; submit below the fold.** Offer measure 14ch → 20ch at ≤480 (three lines → **two**), supporting copy floored at 16px, two-column fields from 600px, submit ≥52px, inputs ≥44px at 16px (iOS no-zoom), plus `max-height: 600px` and `max-height: 440px` compaction tiers. **Submit now visible without scrolling for all five offers at all 12 viewports.** At 320×568 / 844×390 / 667×375 the two secondary links sit under exactly **one** internal scroll region — reported as a scroll, not claimed as a pass. Labels, consent line and both fields intact everywhere.
- **E — consent gear over the quiz.** `#wpconsent-consent-floating` is a **shadow-root child** (unreachable by page CSS) at `z-index 9999`; the modal was 2100. Modal raised to **10000** — above the gear, still far below WPConsent's banner/preferences overlay (900000), so consent stays answerable and the auto-open deferral is untouched. Measured: the backdrop is now topmost at the gear's centre and **`gearReceivesClicks: false`** everywhere. Nothing disabled, hidden or removed.
- **F — desktop hamburger beside the desktop nav.** The D2 touch-target rule sat at **top level, outside any query**, forcing `display: inline-flex` at every width and overriding both the base `display:none` and the `@container (max-width: 1116px)` reveal. The 44×44 box is kept; the display decision moves back into the container query. Verified either side of the breakpoint: header-inner 1136 → toggle hidden / nav shown; 1096 → toggle **44×57** / nav hidden; **never both, never neither**; `aria-expanded` cycles and the menu opens and closes.
- **G — homepage quiz consolidation.** Removed the audience-gateway render and the inline homepage quiz (component files kept, not deleted). Homepage now has **exactly 1 launcher, 1 modal, 1 `[data-bhp-quiz]`**, 0 duplicate IDs. The `#find-your-adventure` deep-link contract moves to the launcher wrapper via a new optional `id` arg passed by `footer.php` **on the homepage only**. The now-dead `.home #find-your-adventure` navy-section CSS was deleted — it would otherwise have repainted the small launcher as a full navy section. Stale "two quizzes by design" comment corrected in `functions.php`.
- **Behaviour regression clean:** 8s timer and 40% scroll auto-open both fire, one per session, manual launcher works, all 4 routes / 12 results / partnership exception intact, **16/16 dismissals at 0px drift**, focus trap wraps both ways, cart/checkout exclusions hold (0 launchers/modals there), zero console errors. **No Mailchimp contact created.**
- **Stated plainly:** this environment has no Safari toolbars, so `100dvh` resolves as `100vh` and the visual viewport never shrinks — **the real-iPhone condition that produced the bottom-sheet and gear screenshots could not be reproduced here.** Parts B and E are verified by measurement and correct layering, but **owner verification on the actual iPhone is required** before they are treated as closed. Screenshots remain unavailable in this environment. Full record: `RELEASES/SCREENSHOT_FIXES_1_19_121.md`.

## 2026-07-31 — STAGING 1.19.120: homepage hero — three-book preview moves under the H1 on mobile

**Staging only. Production remains v1.19.112 and was not touched.** Three files changed (`template-parts/components/hero.php`, `front-page.php`, `style.css`), confirmed by `diff -rq` against the 1.19.119 backup. Parity 147/147.

- **Structural, not a CSS reorder.** The shared hero component gained one backward-compatible optional argument, `aside_after_title` (**default `false`**). When true the aside renders immediately after the `<h1>`; otherwise it renders exactly where it always did. The two placements are mutually exclusive guards over the **same variable**, so the markup is emitted **exactly once** — verified served: `bookPreviewCount: 1`, `bookCoverCount: 3`. No duplicate node, no hidden copy, no separate desktop/mobile images or links. `front-page.php` is the only opt-in.
- **New mobile order:** eyebrow → H1 → **three-book preview** → supporting paragraph → primary CTA → secondary CTA → "Big Places. Brave Hearts." `domMatchesVisual: true` at 320/360/390/430/667. **No `order`, no absolute positioning, no transforms** on any hero child (`cssOrderUsed: false`, `absPositioned: []`).
- **Desktop provably unchanged.** Above 768px the preview is *explicitly* grid-placed (`grid-column: 2; grid-row: 1 / 6`), so its position comes from the placement, not its DOM index. Proven by moving the node back to its old position in the live DOM and re-measuring: **identical geometry to 2dp for preview, H1, eyebrow, text, actions, details, all three covers and total hero height** at 1024×768, 1366×768 and 1440×900 (`diffKeys: []` at all three).
- **Only real CSS need** was the preview's `margin-top: 78px`, tuned for it being the *last* hero element; between the H1 and the paragraph it becomes 20px/2px.
- **Pre-existing 320px clipping defect found and fixed.** The hero's single grid track measured **284px inside a 244px container** — a `1fr` track takes its items' min-content as an automatic minimum, and the widest CTA label forced it past its own container. Every hero child then ran to x=328 on a 320px viewport, where the hero's `overflow-x: hidden` silently clipped the third cover, both CTAs and the H1, with no scrollbar to reveal them. Fixed with `grid-template-columns: minmax(0, 1fr)` + `min-width: 0`, scoped to **≤380px** so 390px and wider keep their existing measured layout. Covers now scale proportionally and CTA labels wrap instead of being cut off.
- **Covers verified proportional and uncropped:** max rendered-vs-natural ratio delta **0.26%** (sub-pixel). An initial ~4% "crop" reading was an artifact of `getBoundingClientRect()` on the decoratively rotated first/third covers and was corrected using untransformed layout boxes. Three distinct source files, one `<img>` each, all links resolving to the real product URLs.
- **QA across 1440×900, 1366×768, 1024×768, 768×1024, 430×932, 390×844, 360×800, 320×568, 667×375:** correct order, H1 unclipped, 3 covers loaded, links working, no horizontal overflow, CTA and signature visible, **0 duplicate IDs, 0 broken images, 0 console errors**, and **CLS = 0 with zero layout-shift entries** (covers carry explicit width/height + `loading="eager"`).
- **Accessibility:** keyboard order Mariana → Everest → Amazon → primary → secondary (`TAB_MATCHES_LEFT_TO_RIGHT: true`), covers before CTAs, no positive tabindex, focus-outline rules present and unchanged, 200% text zoom preserves order with everything inside the viewport, reduced-motion rules present.
- **Other hero callers untouched.** All seven callers inspected first; `front-page.php` is the only one passing `aside`. Served check: `/about/`, `/books/`, `/contact/`, `/teachers/` all render `eyebrow > H1 > text > actions` with **0** previews and no new class. (`/explorer-passport/` 404s on staging — pre-existing, no page assigned.) Commerce smoke clean: prices $11.99/$17.99/$48.99 intact.
- **Quiz untouched and non-regressed:** no quiz file differs from its 1.19.119 checksum; Q1 and all four Q2 routes still scroll-free with 0 internal regions at 390 and 1440 (grids 1×4/1×3 and 2×2), result screen unchanged (1 primary CTA, 2 fields, 640px desktop dialog), auto-open fires `scroll_40`.
- **Flagged, not assumed:** **768×1024 tablet portrait also gets the new order**, because the hero is already single-column there; the approved two-column composition exists only at ≥769px and is fully preserved. Reverting tablet is a one-line breakpoint change but would create a DOM/visual mismatch, so it is left for the owner to decide. Full detail: `RELEASES/HOMEPAGE_HERO_MOBILE_ORDER_1_19_120.md`.

## 2026-07-31 — STAGING 1.19.119: quiz question screens fit without scrolling (two-column answer grid)

**Staging only. Production remains v1.19.112 and was not touched.** Corrects the fit defect left by 1.19.118. Quiz CSS + one JS state-class change + the version. Parity 147/147.

- **Root cause, measured not guessed.** 1.19.118 enlarged the answers but kept them in a **single column**: four cards at `min-height` 80px plus gaps came to **537.7px of content against a 548px budget** (`max-height: calc(100vh - 32px)`) at a 580px-tall viewport — about **10px of headroom**. Any shorter window, or any answer wrapping one extra line, pushed the fourth answer out of view. At **320×568 it already overflowed by 27px** (`scrollHeight 571` vs `clientHeight 544`) with the longest answer wrapping to **three lines**. So the defect was real and reproducible, and the single column was the cause.
- **Two-column grid restored, this time with the width to support it.** `.bhp-quiz__options` is now a real CSS grid: one column on mobile, **two from 760px up**. Q1's four answers form a **2×2**; Q2's three form **two on the first row with the third spanning the full second row** (`:nth-child(3):last-child`, which cannot match Q1's third answer because it is not last). **DOM order is row-major grid order, so visual and keyboard order agree by construction** — no `order`, no `dense`, no reversal anywhere.
- **The real width constraint was `.bhp-quiz__inner`, not the dialog.** At its 640px cap each column resolved to 314px and the label to 250.7px. The longest Q1 answer measures **464.8px intrinsic** at 20.9px, so it needs **~261px** to break cleanly in two — it was ~10px short, which is exactly why it took a third line. Question steps now widen the measure to **720px** (columns 354px, labels ~292px) inside a **780px** dialog. **The result step deliberately keeps 640px.**
- **Step-scoped, so result screens are untouched.** `showStep()` now sets `bhp-quiz--step-1` / `bhp-quiz--step-2` / `bhp-quiz--question` on the quiz root and `bhp-quiz-modal__dialog--question` on the dialog (set explicitly rather than relying on `:has()`, since the dialog is an ancestor). All compaction is scoped to `.bhp-quiz--question`. Verified live: on the result the dialog is **640px**, classes are `bhp-quiz bhp-quiz--result`, inner 640px, padding-bottom 32px, offer 44px, headline 30px, form 420px, 2 fields — **all unchanged**.
- **Typography rebalanced to the new ranges** (1440 → 1024 → 390): progress **15 / 14.1 / 12**, question **37.8 / 32.2 / 23.7**, answers **20.9 / 19.4 / 17.1**, control heights **78.7 / 75.5 / 54**. Height-aware compaction at `max-height: 760px` and `600px` shrinks question steps only. **No control anywhere in the matrix is below 44px** (minimum measured 46px).
- **Vertical rhythm reduced** where it was dead space: progress margin 8→6 (4 in modal), question margin 18→14 (10 in modal), answer padding `14px 52px 14px 20px` → `12px 44px 12px 18px` (and `34px/14px` on ≤430px, which is what gets the narrow-phone label its extra measure), arrow lane 16→14px, grid gap 10→8/6 on short screens, question-step bottom padding 32→20/16/12px. Close-button clearance was **not** reduced — 60px still fully clears the 48px button.
- **Result: Q1 and Q2 cannot scroll at any tested viewport.** Q1 content **538 → 341px** at 1440×900. Measured across **1440×900, 1366×768, 1024×768, 768×1024, 430×932, 390×844, 320×568, 667×375** plus an extra 568×320: `scrollHeight === clientHeight`, `scrollTop 0`, **0 scroll regions, 0px scrollbar**, every answer and the Back control fully inside the dialog, ≥16px clear below the final control (16–26px), close button visible and hit-testable, no clipping, no horizontal overflow, 0 duplicate IDs. The result screen still keeps **exactly one** region where its form genuinely needs it (at 320×568: 765 vs 552) — as allowed.
- **Regression clean.** All 4 routes, all 12 results, 12 distinct headlines, exactly one primary CTA each, partnership form-free → `#contact`, destinations/UTMs unchanged. Focus trap wraps both directions on all three steps; keyboard order verified as TL→TR→BL→BR (Q1) and TL→TR→full-width→Back (Q2). Scroll reset holds (scrolled a result to 200 → Start over lands Q1 at 0). **16/16 dismissals at 0px drift.** Both auto-open triggers proven (`timer`, `scroll_40`). Start over fully resets. 200% text zoom: no scrolling needed at 1440; at 320 scrolling is needed but nothing clips or becomes unreachable, exactly one region. Zero console errors. **No form submitted — no Mailchimp contact created.**
- **Stated plainly:** at **320×568 the longest Q1 answer still wraps to three lines** in the single column. The ≤2-line requirement was specified for the two-column desktop/tablet grid, where it is met everywhere; at 320px it cannot be met without dropping below the 17px floor. It fits, does not clip, and does not scroll. Two-column landscape is proven to work at 568×320 but is **not** applied at 667×375, where a single column already fits (339px of a 359px budget) and reads better. Screenshots remain unavailable in this environment; evidence is DOM geometry. Full detail: `RELEASES/QUIZ_QUESTION_SIMPLIFICATION_1_19_118.md` § "Fit correction (1.19.119)".

## 2026-07-31 — STAGING 1.19.118: quiz question screens simplified (promotional header removed, question promoted)

**Staging only. Production remains v1.19.112 and was not touched. Awaiting owner review at https://staging2.braveheartspublishing.com/** Scope was the quiz question screens only — no homepage, product, Shop, WooCommerce, Mailchimp or database change. Parity 147/147 files.

- **Removed from Question 1 and every Question 2 route** (deleted from the DOM, not merely hidden): the eyebrow `2 QUESTIONS · ABOUT 30 SECONDS`, the headline `Where Should Your Adventure Begin?`, and the `No wrong answers…` paragraph — the whole `.bhp-quiz__header` block. It cost a measured **195.6px at 1440×900** and **231.3px of a 544px dialog at 320×568**, where the question screen began 299.3px in, i.e. 55% of the modal was header before the visitor reached the question they were asked to answer. Question 1 now visibly contains exactly: close button, `QUESTION 1 OF 2`, the question, four answers.
- **Nothing was lost on `/find-your-adventure/`.** That page already renders its own `<h1>` and intro paragraph directly above the component, so it had been showing **two stacked introductions**; its heading outline is now a clean H1 → H2. The homepage's `intro_gate` lead-in card is a different element (`.bhp-quiz__intro`) and is **deliberately untouched** — eyebrow, headline and lead all still render there.
- **Accessible naming corrected, and the brief's premise checked rather than assumed.** The old headline was **never** part of `aria-labelledby` — the dialog was named by a hidden `screen-reader-text` h2 ("Find Your Adventure quiz"), verified live before any edit. The visible question is now a real `<h2>` with a unique id derived from the already-unique root id, and `syncDialogLabel()` retargets the dialog's `aria-labelledby` to whichever heading is **visible** (Q1 → Q2 → result offer, falling back to the recommendation headline on the partnership answer, which has no offer). Hidden steps never label the dialog; the persistent SR-only heading remains only as a fallback. Verified: hidden steps are `display:none` at height 0, the focus trap sees exactly 5 visible controls on Q1, **zero duplicate IDs**.
- **Transition announcement.** A `role="status"` live region outside every step wrapper announces `Question 2 of 2. <question>` once per transition — placed outside the steps because a live region inside one is removed from the accessibility tree by that step's `hidden` attribute at the exact moment it needs to speak. Focus behaviour is unchanged.
- **Typography — measured, not intended.** Desktop 1440: progress **12 → 16px**, question **18 → 34px** (and now a real heading), answers **15 → 22px**, answer controls **81 → 80px** but now uniform. Mobile 390: progress **13.2px**, question **25px**, answers **18px**, controls **55.5 → 61.8px** (they were below the 60px target). All fluid `clamp()`, no stepped breakpoint.
- **Answers are left-aligned again.** This **reverses the optical-centring work shipped in 1.19.100** — deliberately, on the owner's current-turn direction that answers "remain left-aligned and easy to scan". The arrow stays out of the flow (that part of 1.19.100 was right and is retained), so every label starts at one predictable left edge; asymmetric padding reserves a 29–121px arrow lane so a long label wraps before crowding the glyph.
- **Single-column answers.** The `flex: 1 1 45%` two-column desktop grid was removed: at the new 22px label size it halved each answer to 291px, wrapping the longest Question 1 answer to three lines and giving the 2×2 grid four different row heights. Answer wording, order and routing are untouched.
- **Close-button clearance corrected.** Modal `padding-top` 52 → **60px**. The 52px figure was derived from the old 34px button at `top:10px`; the D1 touch-target fix grew it to 48px at `top:8px` (bottom edge 56px), so 52px had silently become **4px short** of clearing it. 56px at ≤400px, where the button is 44px.
- **QA — 7 viewports × 4 routes, all 12 results.** All four Q1 routes, every Q2 branch and all 12 results correct, 12 distinct headlines, correct destinations/UTMs, exactly **one visible primary CTA** per result, partnership still form-free and deep-linking to `#contact`. Q2 and every result open at `scrollTop 0`. Zero horizontal overflow, zero clipping, zero duplicate IDs, **zero console errors**. Exactly one internal scroll region where a screen genuinely needs it (320×568, 667×375, and the taller result screens at 1366/1024) and none elsewhere. Focus trap wraps both directions on all three steps; Escape returns focus to the launcher. **16/16 dismissal tests at 0px drift on both axes** (close, Escape, backdrop, Keep browsing × four scroll positions on a 9,965px page). Auto-open proven for **both** triggers with captured events (`open_reason: "timer"` and `open_reason: "scroll_40"`), plus session suppression. Start over fully resets. 200% text zoom clean at 1440 and 320. **No form was ever submitted — no Mailchimp contact created.**
- **Stated plainly rather than glossed:** at **768×1024** the type interpolates between the brief's two defined tiers (question 29.2px, answers 19.5px, controls 69.4px — just under the desktop floor, just over the mobile ceiling). This is the intended consequence of fluid `clamp()` across the tablet gap; forcing both bands to be met 101px apart would create a visible jump between a phone in landscape and a tablet. Reduced-motion rules are present and parsed but were **not** observed under a real OS preference. Screenshots remain unavailable in this environment. Full detail: `RELEASES/QUIZ_QUESTION_SIMPLIFICATION_1_19_118.md`.

## 2026-07-31 — STAGING 1.19.117: Homepage Conversion Phase 1a (hero clarity + product-first order)

**Staging only. Production remains v1.19.112 and was not touched. Awaiting owner review at https://staging2.braveheartspublishing.com/** Phase 1a covers two of the four Phase 1 improvements — hero clarity and product-first section order. **Quiz consolidation, Philosophy/Founder compression and Learning Hub reduction are NOT in this release and are not claimed as done.**

- **Product-data dependency hoist.** `$adventure_cards` and its supporting data (`$mariana_book`, `$everest_book`, `$amazon_book`, `$find_formats_for_destination`) were prepared *after* the section that consumes them; relocating `#explore-world` above the editorial sections made the loop run before the data existed and render **zero cards**. The whole preparation block now sits above the hero render — dependency-ordered, exactly one copy, with lookup rules, prices, formats, URLs, images and filters byte-identical. Verified by character offset: defined 3604, first consumed 15043. A first attempt was caught in QA and **rolled back** rather than left partially deployed.
- **Hero copy.** Eyebrow `REAL-WORLD ADVENTURE BOOKS FOR AGES 6–9`; H1 `Adventure Books That Turn Curiosity Into Courage`; new Charlotte-and-Henry supporting line; `Big Places. Brave Hearts.` retained as a visible gold signature rather than the H1; primary `GET THE COMPLETE COLLECTION`, secondary `FIND THEIR FIRST ADVENTURE` → `#explore-world`. No `bhp_home_*` metadata exists on the front page, so the PHP fallbacks are authoritative and **no database update was needed**.
- **Section restructuring.** `#home-sales-paths` collapsed from three competing pathway cards into one Complete Collection feature (Best Value retained; duplicate "Choose Your First Adventure" and teacher cards removed — the teacher path already exists lower in Teachers & Families). `#explore-world` moved directly beneath it. Standalone `#featured-books` band removed, replaced by one restrained `EXPLORE EVERY FORMAT AND EDITION` → `/books/` action. Final order: `home-hero → home-trust-proof → home-sales-paths → explore-world → kirkus-credibility-home → home-audience-gateway → home-philosophy`.
- **Desktop H1 scale correction.** The explanatory H1 inherited a 92px display scale built for the two-word brand line and set over 4 lines (353px). Homepage-scoped `clamp()` brings it to **54px / 3 lines / 180px**; hero 1130px → **956px**; primary CTA 827px → **653px**.
- **Mobile CTA reachability.** At 320×568 the CTA sat at 706px. Root cause was **not** the covers (they already render after the CTA) but 92px hero padding-top, four ~45px stack gaps and a 94px commercial subtext duplicating the eyebrow. CTA now **436→505px, fully inside the 568px fold and clear of the 93px sticky header**.
- **Signature visibility.** `.home-hero__details` was `display: none` ≤768px, hiding the new signature on every mobile viewport. The block is re-shown with only the destination stat list hidden, so the signature is visible at all seven tested viewports.
- **QA.** Seven viewports all pass for H1/clipping/body size/covers/cards/prices/links/overflow/duplicate IDs/broken images/console errors. 3 cards, 3 valid images, 6 live prices, 6 product links everywhere. 200% text at 320px: zero clipping, zero horizontal overflow. Keyboard: 5 hero focusables in logical order with visible focus. Reduced motion: no animations to suppress. Parity 147/147.
- **Two documented tradeoffs, not defects:** the CTA sits below the initial viewport at 1024×768 (752 vs 768px) and 667×375 landscape — accepted because forcing it in would need sub-accessible text sizes; all other landscape conditions pass. The signature renders after the CTAs because it lives in the shared component's `details` slot; reordering needs a shared-component change, deliberately avoided.
- **Not measured:** LCP and CLS. Screenshots unavailable in this environment. Full detail: `RELEASES/HOMEPAGE_PHASE1A_STAGING_1_19_117.md`.

## 2026-07-30 — STAGING 1.19.111: quiz submit button geometrically centred

**Staging only. Production remains v1.19.100.** Per Screenshot 839 the label was centred *inside* the button, but the button element itself sat left of the form and modal centrelines.

- **Root cause:** the submit is a child of the signup form's flex **column**. A sitewide `.btn` rule outranks this file's `width: 100%`, so the button resolved to shrink-to-fit — and a flex item that cannot stretch falls back to the column's **start** edge. The offset therefore tracked label width exactly: **−40.1px** (Adventure Kit), **−26.6px** (Learning Toolkit), **−58.3px** (Gift Guide), **−2.6px** (Community Reading Kit). The label was already 0.0px inside its own button, which is why only the element looked wrong.
- **Fix — one CSS declaration:** `align-self: center` on `.bhp-quiz__signup-submit` (plus `max-width: 100%`). Centring on the column's cross axis is independent of the resolved width, so it holds for every label length with **no pixel offset, transform or per-route margin**. `width: 100%` was removed because it never applied and was misleading.
- **Measured after — 0.0px on every axis, all four labels, all five viewports:** button-vs-form-column 0.0, button-vs-email-field 0.0, button-vs-modal-content-centre 0.0, label-vs-button 0.0 horizontal and 0.0 vertical. Well inside the ≤1px acceptance.
- **Widths preserved** at desktop (340 / 367 / 303 / 415px). At 390px the two longest labels cap at the 342px form column via `max-width: 100%` — the intended responsive shrink, still centred, no clipping, no overflow.
- **Partnership CTA** was already geometrically centred (0.0px) via the existing centred actions column and is unchanged.
- **Regression clean:** offer hierarchy unchanged (30.1px offer > 23.1px recommendation), one visible primary with the legacy CTA at `display: none`, form fields unchanged (318px each), focus trap both directions, internal scroll reset (33px induced → 0), all four dismissal methods at **0px** page-position drift, Shop unchanged (4 cards, 3 × "CHOOSE YOUR FORMAT"), zero console errors. **No Mailchimp contact created.**

## 2026-07-30 — STAGING 1.19.110: quiz result hierarchy — the free offer now leads

**Staging only. Production remains v1.19.100.** Per Screenshot 838, the free offer was the least prominent thing on the result: a 19.08px `<strong>` embedded inside the supporting paragraph, sitting *below* a 24.8px recommendation headline.

- **Structure (template + JS):** the resource name is now its own real heading — `<h3 class="bhp-quiz__result-resource-title">` above the recommendation, which becomes `<h4>`. `renderResultText()` was replaced by `renderResultResource()` (fills/hides the offer heading) and `renderResultDetail()` (writes the explanation only). The supporting paragraph no longer repeats the resource name and no longer carries the leading em dash. The old `<strong>`-building function was removed rather than left as dead code.
- **Typography, measured live.** Desktop: offer **44px**, recommendation **30px**, detail 16px. Mobile: offer **30.1px**, recommendation **23.1px**, detail 15.2px. Both sit inside the requested 40–48/30–36 and 28–34/23–28 bands, using `clamp()` so sizes flow rather than jump at a breakpoint. All three centred; offer in brand green.
- **Specificity trap avoided:** the pre-existing `.bhp-quiz__step--result h3 / p` rules (0,1,1) would have overridden single-class rules on margin and colour, so the new rules are scoped `.bhp-quiz__step--result .bhp-quiz__result-*` (0,2,0).
- **Vertical balance:** the offer heading initially wrapped to 3 lines / 148px tall on desktop. Widening the measure to `min(21ch, 100%)` sets it in 2 lines / **99px**, keeping it dominant without pushing the form down.
- **Partnership result unchanged:** its offer heading is `hidden`, so there is no empty heading, no blank gap and nothing in the accessibility tree — only the `<h4>` recommendation and the direct `#contact` CTA remain, with no form.
- **Verified across all 12 outcomes and 5 viewports:** correct offer title per route, offer always larger than the recommendation, explanation separate, no duplicated resource text, exactly one visible primary action (legacy CTA at `display: none`), no clipping, no horizontal overflow. Heading order reads `H3: Free … → H4: recommendation`. Contrast on cream: offer **8.66:1**, recommendation **12.06:1**, detail **6.14:1** — all comfortably WCAG AA. Modal accessible name still "Find Your Adventure quiz". Focus trap holds both directions (7 focusables), all four dismissal methods preserve page position at **0px**, internal scroll still resets (3px induced → 0). Shop re-confirmed unchanged (4 cards, 3 × "CHOOSE YOUR FORMAT"). Zero console errors. **No Mailchimp contact created.**

## 2026-07-30 — STAGING 1.19.108: double-button defect fixed on quiz resource results

**Staging only. Production remains v1.19.100.** Andrew reported two primary buttons on resource results (the new `SEND ME THE FREE …` submit plus the legacy `Get My Free …` CTA).

- **Root cause — a regression introduced by this project's own CSS, not by the JS.** The JS was correctly setting `resultCta.hidden = true`, but the result CTA carries `.btn.btn-primary`, and `assets/css/audience-quiz.css`'s `.bhp-quiz .btn-primary { display: inline-flex }` (specificity 0,2,0) outranks the user agent's `[hidden] { display: none }`. The attribute was set and silently ignored, so the old CTA stayed **visible and in the tab order**. Measured before the fix: `hidden=true`, `computed display: flex`, `focusable: true`, two visible primary buttons.
- **Fix (CSS only, 1 file):** a `[hidden]` guard scoped to `.bhp-quiz` / `.bhp-quiz-modal`, placed before the button rules, restoring `hidden` as the single source of truth. `display: none` removes the element from layout, the tab order and the accessibility tree in one move — deliberately not a visual cover-up, clip or off-screen move. The legacy CTA is retained because the partnership route still uses it; the two states are now structurally mutually exclusive.
- **Verified across all 12 outcomes:** the 11 resource results each show exactly **one** visible and one focusable primary action (the correct `SEND ME THE FREE …` label, text offset 0.0/0.0), with the old CTA at `display: none`, non-focusable and out of the a11y tree. The partnership result shows exactly one visible primary action, its `#contact` CTA, and no form.
- **State transitions verified:** partnership→resource, resource→partnership, Back, Start Over, route change, and close/reopen all restore the correct state. After a validation error the form submit remains the only visible primary action and entries are preserved.
- Five viewports (1440×900, 1366×768, 1024×768, 390×844, 390×600) all clean: one primary action, no clipping, no horizontal overflow, result-actions row 36px (no blank gap left by the removed CTA). Focus trap holds both directions with 7 focusables; all four dismissal methods preserve page position at 0px; internal scroll still resets. Zero console errors. **No Mailchimp contact was created for this fix.** Shop and product pages re-confirmed unchanged (4 shop cards, 3 "CHOOSE YOUR FORMAT", selector + Kindle-without-price intact).
- Also folded in from the in-flight closeout: the mobile `Choose your format` heading is now screen-reader-only below 782px (it still labels the group via `aria-labelledby`), reclaiming ~30px of mobile scroll depth.

## 2026-07-30 — STAGING 1.19.107: quiz inline email capture + unified Shop/book purchase experience

**Staging only — theme v1.19.107. Production remains v1.19.100 / plugin 1.8.7, untouched. Awaiting Andrew's visual approval.** Two phases, both complete and QA'd on staging. The bundle plugin was NOT changed (still 1.8.7).

### Phase 1 — quiz inline email capture (shipped 1.19.101 → 1.19.102)
- `inc/mailchimp.php`: the body of `bhp_handle_mailchimp_signup()` was extracted into a shared, request-free `bhp_process_signup()`. The classic `admin_post` handler is now a thin wrapper with **byte-identical behaviour** — re-verified with a real native form POST returning `?bhp_signup=success&bhp_form=…`. There is still exactly one place that talks to Mailchimp.
- New same-origin JSON endpoint `wp_ajax(_nopriv)_bhp_quiz_signup`, protected by nonce + honeypot + a **new IP-hashed transient rate limit** (the theme had no rate limiting of any kind before this — it was added, not "preserved").
- Server-side result whitelist `bhp_get_quiz_signup_routes()`: the browser sends only a short route key; audience, lead-magnet key and redirect destination are all resolved server-side. No tag strings are duplicated — the existing `bhp_mailchimp_signup_tags` filters produce the live-verified tag sets.
- The four funnel destinations are registered as whitelisted redirect KEYS resolved through `get_page_by_path()` → `get_permalink()` → `wp_validate_redirect()`.
- The endpoint deliberately never calls `bhp_mailchimp_signup_redirect()` — that helper puts email/name in a query string for classic forms. Quiz entries are preserved in the live DOM instead.
- **Quiz-sourced lead events store no email** (`_bhp_lead_email` written empty when context is `audience_quiz`); provenance is still classified in memory so test-vs-real reporting works. Every other context is unchanged. Verified: quiz event #619 `email_stored=NO`, standalone event #620 `email_stored=YES`. Two pre-fix quiz records (#617, #618) still hold their test addresses.
- 11 of 12 answers render the form with a resource-specific CTA; the organization partnership answer renders **no form and no delivery promise**, keeping its `#contact` CTA.
- Verified live: success redirects to the correct funnel page, validation failure keeps the visitor on the result with entries intact, 0.0/0.0px label centring, 0px dismissal drift, scroll reset, focus trap, zero console errors, no PII in URLs/storage/analytics.

### Phase 2 — unified Shop and book pages (shipped 1.19.103 → 1.19.107)
- New `inc/book-formats.php` + `template-parts/commerce/format-cards.php` + `assets/{css,js}/book-formats.*`. **Presentation layer only** — no product merged, deleted, renamed or re-priced. All 6 products remain published with unchanged SKUs, prices, stock and Bookvault mapping; one shipping zone (`flat_rate`) only.
- One canonical page per title (the paperback product), with four format cards: PAPERBACK, HARDCOVER, KINDLE, COMPLETE COLLECTION (BEST VALUE). Real `<button>`s with `aria-pressed` — no dropdown, no radio circles.
- **Every price is read live from WooCommerce/the bundle plugin.** No price exists in any template or JS.
- **Kindle shows no price by design** — Amazon controls it and none is stored anywhere. The card shows `VIEW ON AMAZON`; selecting it shows "Available on Amazon" and a `VIEW KINDLE ON AMAZON` CTA to the verified title link with `rel="noopener nofollow sponsored"`.
- Legacy hardcover URLs 301 to the canonical page with `?bhp_format=hardcover`. **Exactly one hop, confirmed via Navigation Timing `redirectCount: 1`** on all three titles, with UTMs and `gclid` preserved and no malformed query strings. The canonical paperback URL is never redirected, so a loop is structurally impossible.
- Shop grid shows exactly 3 titles + Complete Collection; hardcovers are hidden from the loop only (still published and directly reachable). Titles render without their "(Paperback)" suffix in the catalog and on the canonical page — a display filter only; admin, cart lines, orders and exports are untouched.
- Mobile purchase-first hierarchy: selector moved to `woocommerce_single_product_summary` priority **15** (above the short description, meta, tabs, reviews and related content), plus a compact mobile cover and 2×2 card grid.
- Structured data: a **second Offer** (hardcover, live price/currency/availability/SKU) is appended to the SAME Product entity at `rank_math/json_ld` priority 999. Verified on all three pages: **1 Product entity, 2 offers**, no duplicate/conflicting Product schema, no fabricated ratings or reviews.

### Known limitations recorded honestly
- **Canonical output cannot be verified on staging** — staging is `noindex,nofollow` site-wide and Rank Math suppresses canonical tags on noindex pages (confirmed on `/`, `/shop/`, `/complete-collection/` too, so it is environmental, not a defect). The filter was instead verified with controlled inputs: all six products resolve to the clean base unified URL with **no query strings ever**. **A production preflight is mandatory** — see `PROJECT_STATE.md`.
- **ProductGroup / `hasVariant` variant schema was deliberately NOT implemented.** Rank Math owns the Product node and has no ProductGroup support, so emitting one would mean replacing its entity or shipping a competing graph, and it could not be validated here (staging is unreachable by the Rich Results Test). Recommended follow-up against production with a real validator.
- **Mobile scroll depth improved but does not fully meet the stated targets.** See PROJECT_STATE for the measurements.

## 2026-07-30 — PRODUCTION DEPLOYED: theme 1.19.100 + bundle plugin 1.8.7

**Production is now theme v1.19.100 + `brave-hearts-bundle-pricing` v1.8.7** (deployed 2026-07-30 with Andrew's explicit approval; supersedes every "Production remains 1.19.91 / 1.8.6" statement below). This ships the whole staging-approved release in one go: the quiz personalization/copy work (1.19.93–1.19.98), the Complete Collection Hardcover default + deeper star gold (1.19.99), and the button optical-centring fix (1.19.100).

- **Stop-gate before deploy:** production confirmed at 1.19.91 / 1.8.6. Theme diff = **exactly 8 approved files, 0 additions, 0 deletions**; plugin diff = **exactly 4 approved files**. Semantic review confirmed no shipping, pricing, coupon, Bookvault, schema or Shop change. Mariana attachments 13 (`a1f213d9…`) and 359 (`e863ebc5…`) verified `inherit` and untouched. The 11 staging rollback/scratch files were excluded and confirmed absent from production after deploy.
- **Backup:** `~/bhp-PROD-release-backup-20260730-214515/` — `theme-1.19.91.tar.gz`, `plugin-bundle-pricing-1.8.6.tar.gz`, a `db/` snapshot (product thumbnails, att13/att359 meta, options, themes, plugins, coupons), and `MANIFEST-theme.md5` (143 files) / `MANIFEST-plugin.md5` (44 files).
- **Deploy mechanism:** theme via full-ZIP `wp theme install --force` (143 files); plugin via the documented isolated 4-file patch (it is not covered by the theme ZIP). SiteGround cache purged. Post-deploy parity: theme **143/143 checksums match**, plugin **0 files differing**.
- **Production QA — all PASS.** Quiz measured at **1440×900, 1366×768, 1024×420, 390×844, 390×600**: 16 answer buttons at **0.0px on both axes**, CTAs 0.0px horizontal / ≤0.2px vertical, all four routes returning the correct resource (partnership correctly `(none)` — no invented free-resource claim), UTMs intact, internal scroll reset to 0, focus trap both directions, touch targets ≥44px, zero console errors.
- **Page-position drift is 0px on all four dismissal paths** (X, Escape, backdrop, "Keep browsing") at every viewport. A transient −8px reading at 1024×420 was traced to the harness capturing its reference *after* the modal opened: `body{overflow:hidden}` shifts the page 8px while the modal is open (pre-existing, invisible under the modal) and it is fully corrected on close. Measured pre-open 1500 → post-close **1500**. **Harness error, not a defect** — do not re-chase this.
- **Commerce verified live and reconciled exactly:** Hardcover is the default on a fresh load (`bundle_page_view {format:"hardcover"}`), cart gives exactly 3 Hardcover books, items $53.97, fee `bundle-savings-hardcover` **−$4.98** → **$48.99**, shipping **$4.99** (single "Contiguous US Shipping" rate), Idaho tax $2.94, total **$56.92**. The bundle discount is a **negative fee, not a coupon** — `total_discount` reads 0 by design. Paperback unchanged at $31.99 / $3.99. [PARENT_COUPON_CODE_SUPERSEDED] unchanged (ID 346, percent, 10, publish). **Test cart emptied afterward.**
- **No BookVAULT Shipping in any zone** — exactly one zone method exists (`Contiguous United States` → `flat_rate`, enabled); the rest-of-world zone has none. `bundle-cart.php` checksum on production is **identical to the pre-deploy backup** (`e1dce1a5…`).
- **Checksum note:** the local Windows copy of `bundle-cart.php` hashes `0f0d7727…` while production hashes `e1dce1a5…` — this is a **CRLF line-ending difference, not a content difference**. Compare production-now against the production backup, never against the Windows working copy.
- **Integrity:** 6 published products intact, Shop renders all 6 with 0 broken images, Mariana cover serving from the approved canonical attachment, product thumbnails unchanged (14→13, 15→352, 17→358, 18→356, 20→357, 333→13), 9 key pages HTTP 200 with no PHP errors, `wp eval` returns `site_ok`.
- **Homepage:** 4 trust pills share identical cream styling; only the stars are `--color-gold-deep: #9A6A00` (contrast **4.28:1**, pill text 6.42:1). Stars are `aria-hidden`; accessible name computes to **"5 out of 5 stars Five-star reader reviews"** with no star glyph. Pills wrap to 3 rows at 390px with no clipping and no horizontal document overflow.

## 2026-07-30 — Quiz button labels optically centred (staging theme 1.19.100, CSS-only)

**Staging only, CSS-only. Production remains theme 1.19.91 / plugin 1.8.6. Bundle plugin unchanged at 1.8.7 and not redeployed.** One file of substance changed: `assets/css/audience-quiz.css` (plus the `style.css` version bump).

- **Root cause:** `.bhp-quiz__option-label` was a flex item (`flex: 1 1 auto`) sharing the row with the arrow and a 12px gap, so the label's box was narrower than the button and sat left of its true centre; `text-align: left` compounded it. Centring the text alone would not have fixed it — the arrow was consuming layout width on one side only. **Measured −9.5px horizontal offset.**
- **Structural fix, no nudges:** the arrow is now `position: absolute; right: 16px; top: 50%` (out of flow, so it cannot affect label width), horizontal padding is symmetrical at `34px` reserving arrow room on both sides, the button is `justify-content: center` with `text-align: center`, and the label is `flex: 0 1 auto`. Every arrow transform — base, hover, focus-visible, reduced-motion — now leads with `translateY(-50%)` so vertical centring holds in all states.
- **CTAs:** the result CTA was overriding the sitewide inline-flex button with `display: inline-block`, centring the line box but not the text within the button height. A shared rule now gives both the intro/start CTA and the result CTA `inline-flex` + `align-items/justify-content: center` + `text-align: center` + `line-height: 1.25` + `min-height: 48px` + symmetrical block padding. Colour, border, width behaviour, wording, destination and analytics untouched.
- **Measured before → after, live A/B on identical content:** answer labels **−9.5px → 0.0px** horizontal (vertical was already 0). Across all 5 viewports and all 4 routes, **16 answer buttons measured 0.0px on both axes**, including 6 multi-line labels. CTAs measured 0.0px horizontal / ≤0.2px vertical (sub-pixel). Long CTA labels wrap centrally on mobile — the organization CTA becomes 2 lines at 390px (318×57) with no clipping or overflow.
- **No JS, PHP, plugin or product change.** Verified by checksum against the 1.19.99 baseline: only `audience-quiz.css` and `style.css` differ. Regression re-tested: scroll reset to 0 on transitions, 0px page-position drift on dismissal, focus trap both directions, selected/hover/focus states intact, arrow decorative and excluded from accessible names, all touch targets ≥44px, zero console errors.
- **Note for future sessions:** SiteGround's edge security served a "Robot Challenge Screen" to the automation browser mid-session after heavy request volume, producing zero-size geometry readings. Those readings were discarded, not reported. Pace browser automation on this host and re-check `document.title` before trusting measurements.

## 2026-07-30 — Complete Collection defaults to Hardcover + deeper star gold (staging theme 1.19.99 / plugin 1.8.7)

**Staging only. Production remains theme 1.19.91 / plugin 1.8.6.** Andrew resolved the two commercial questions left open by the previous entry: default the collection to Hardcover, and keep the existing $4.99 Hardcover shipping as-is.

- **Complete Collection now opens on Hardcover.** New `bhp_bundle_default_format()` in `bundle-data.php` is the single source of truth; the format selector, the pricing panel and the final CTA panel all read it, so they cannot drift apart. Verified on a fresh load: `aria-checked="true"` + `is-selected` on Hardcover, Hardcover pricing panel and final CTA visible with Paperback hidden, both CTAs configured `complete_hardcover_smart`, visible prices $53.97 / $48.99 / $4.99 / $4.98. **No URL parameter system was added** — none existed and none was required.
- **Cart verified from the default state** (no format click): exactly 3 Hardcover books, subtotal **$53.97**, `Bundle Savings (Hardcover) −$4.98` → **$48.99**, shipping **$4.99**, tax $2.94, total **$56.92**.
- **No shipping code touched.** `bundle-cart.php` — which holds every shipping and coupon rule — was **not modified and not deployed**; its checksum is identical local and on staging (`0f0d7727…`). Shipping rates, taxes, discounts, product prices, product IDs and Bookvault behaviour are all unchanged.
- **Paperback regression clean:** switching flips every control (`aria-checked`, `is-selected`, both panels, both CTA actions, fine print), cart gives 3 Paperbacks, subtotal $35.97, −$3.98 → **$31.99**, shipping **$3.99**, tax $1.92, total $37.90. Keyboard arrow-key selection still works. `bundle_format_selected` still fires with the chosen format.
- **Analytics:** `bundle_page_view` now additionally carries `format`, read from the rendered selector rather than hardcoded, so it reports the actual default (`format: "hardcover"` verified live). Event names and all existing fields unchanged. **[PARENT_COUPON_CODE_SUPERSEDED] unchanged** — applied successfully to a qualifying paperback collection, total $37.90 → $34.51.
- **Star gold deepened.** New documented token `--color-gold-deep: #9A6A00`, applied *only* to `.home-trust-proof__stars`. Contrast against the cream pill **1.79:1 → 4.28:1**. Measured hue 41° / saturation 100% — squarely in the amber-gold band, not brown. Badge background, border, text, padding and the three neighbouring pills are untouched and still measure identical; stars remain `aria-hidden` with real "5 out of 5 stars" text.
- **Quiz 1.19.98 work regression-tested and intact** at all five viewports: free-resource labels bold `700` / `19.08px` on all applicable results, the partnership result still carries **no invented free-resource claim**, scroll reset 0, page position 0px drift on all four dismissals, focus trap both directions, UTMs intact, zero console errors.

## 2026-07-30 — Quiz free-resource emphasis + homepage five-star pill fix (staging 1.19.98); Complete Collection default STOPPED

**Staging only. Production remains at 1.19.91.** Theme **1.19.96 → 1.19.98** (1.19.97 was an intermediate build superseded within the pass — see the fallback bug below). Two of three requested improvements shipped; the third is blocked on a business decision.

- **Quiz results now lead with the free resource.** The combined `result_text` string was refactored into structured `result_resource` + `result_detail` across all 16 result entries (12 per-answer + 4 route-level fallbacks). `audience-quiz.js` builds the output from real DOM nodes — a `<strong class="bhp-quiz__result-resource">` plus a text node — **no `innerHTML`, no regex, no punctuation-splitting.** Styling: bold, `1.06em`, brand green `#1F4D36`, scoped to the inline `<strong>` so the paragraph keeps its size and the modal does not grow taller. Labels: *Free Reluctant Reader Adventure Kit*, *Free Adventure Learning Toolkit*, *Free Community Reading Kit*, *Free Meaningful Gift Guide*. **No "PDF" claim was introduced anywhere.**
- **Bug caught in QA and fixed before final deploy.** The first build used `opt.result_resource || route.result_resource`, which treated the organization partnership answer's *deliberately empty* resource as "absent" and fell back to advertising a **"Free Community Reading Kit"** on an answer that routes to a contact conversation, not a free download — an unsupported offer claim. Absence is now tested by type (`typeof === 'string'`), so that answer renders detail-only. Verified live.
- **Homepage five-star pill now matches its neighbours.** The `--gold` badge modifier (different background, border and text colour) was removed and its now-unused CSS rule deleted. All four trust pills measured identical: background `rgb(255,243,208)`, same border, text colour, padding `5.12px 11.52px`, radius `999px`, height `33px`. Gold is confined to the stars via the design-system `--color-gold` token. Stars are `aria-hidden="true"` with real accessible text "5 out of 5 stars" added. Lower testimonial section untouched.
- **Complete Collection hardcover default: NOT implemented — stopped and reported.** The brief requires both "default to Hardcover" and "existing $3.99 shipping behavior remains unchanged". These are incompatible: a 3-hardcover collection ships at **$4.99** by existing intentional design (`bhp_bundle_rules('hardcover')[3]['shipping']`), verified live in a real cart — subtotal $53.97, discount −$4.98 = **$48.99**, **shipping $4.99**, total $56.92. Defaulting to hardcover would also raise the default entry price from **$31.99 to $48.99**. Both are commercial decisions, so nothing was changed. [PARENT_COUPON_CODE_SUPERSEDED] was confirmed to qualify for either format, so coupon eligibility is not the blocker.

## 2026-07-30 — Working-tree reconciliation; staging 1.19.96 confirmed as the authoritative candidate (no code change)

**Staging only, no code change, no version bump, no redeploy. Production remains at 1.19.91.** Closes the open question left at the end of the third pass: what the uncommitted `assets/js/quiz-modal.js` edit was, and whether it belonged in the release. Full record: `RELEASES/QUIZ_UX_PERSONALIZATION_1_19_93.md` § "Reconciliation".

- **The pending edit was already integrated and deployed.** `quiz-modal.js` is byte-identical (`9376b3e6…` LF-normalised) across the local working tree, deployed staging 1.19.96, the 1.19.96 deploy ZIP, **and the 1.19.95 backup** — i.e. it has been live since 1.19.95. Its mtime predates both deploys and was unchanged 9 hours later: **no process is still writing to it.**
- **Three logical changes, all authorised.** (1) page-scroll capture/restore with `preventScroll` focus and `scrollToInstant()`, and (2) the "Keep browsing this page" close binding — both specified in the 1.19.93 brief. (3) `hasVisibleConsentUI()` replacing the old light-DOM/`offsetWidth` WPConsent check, which could never fire because WPConsent renders into an **open shadow root** on a `position:fixed` 0×0 host — that is the deliverable of background task `task_8f952193`, which **Andrew started**, and it is independently documented in project auto-memory along with its deliberately accepted side effect (the `attemptAutoOpen()` retry loop now genuinely engages for consent, so a banner left up >5s suppresses auto-open for that page view).
- **No conflict with the 1.19.96 internal-scroll fix.** That fix lives entirely in `audience-quiz.js` and governs the modal's internal container; `quiz-modal.js` governs page scroll and consent detection. Verified by grep — neither file references the other's symbols.
- **Candidate verified:** all **143** files of the intended source set match deployed staging exactly. `.claude/settings.local.json`, `docs/`, `tests/`, backups and temp files are excluded by construction. Version held at **1.19.96**.
- **A `curl`-based asset check produced a false mismatch and is worth remembering:** SiteGround's edge security answers non-browser clients with `HTTP 202` and a ~292-byte challenge instead of the file — the same mechanism behind the REST API's 403s. **Do not verify served assets with `curl` on this host.** Re-checked from the real browser: both quiz JS files return 200 with SHA-256 exactly matching local.
- **Full regression re-run on the combined candidate** — 5 viewports × 4 routes, all **PASS**: Q2/result/Back/Start over all begin at `scrollTop 0`, nothing clipped, Tab and Shift+Tab trapped at both boundaries, 5 focusables all visible and in-dialog, window `scrollY` unchanged throughout, all four dismissals at **0px delta** without jumping to the quiz CTA section, resume-where-left-off intact, standalone homepage and `/find-your-adventure/` unaffected, zero console errors. Screenshots again unavailable (tool times out) — evidence is DOM geometry.

## 2026-07-30 — Quiz modal: each screen now starts at its own top (staging 1.19.96)

**Staging only. Production remains at 1.19.91 and was not touched.** Theme **1.19.95 → 1.19.96**. One behavioural file changed (`assets/js/audience-quiz.js`) plus the version bump. Full record: `RELEASES/QUIZ_UX_PERSONALIZATION_1_19_93.md` § "Third pass".

- **Defect:** the modal's internal scroll position carried from one quiz screen to the next. A visitor who scrolled down inside the modal to reach the third or fourth Question 1 answer ("Create a reading program, event, or partnership" / "Choose a meaningful gift for a child") arrived at Question 2 **already scrolled**, clipping the eyebrow, headline and introductory copy. **Reproduced on the 1.19.95 baseline before any change**, at 1024×420: Question 1 scrollTop **89** carried straight into Question 2, pushing the eyebrow **38px above** the visible area — on all four routes, not only the two reported.
- **Root cause:** `showStep()` in `audience-quiz.js` toggles the steps' `hidden` attribute but never touched the scroll container. Since 1.19.95 that container is `.bhp-quiz` itself (it became the modal's single scroll region so the close button could stay pinned), and it kept its `scrollTop` across the swap. The result screen only *appeared* to reset — it is short enough that the browser clamped `scrollTop` to 0 incidentally, not deliberately.
- **Fix, centralised in the existing transition function:** `showStep()` now ends by resetting the quiz's own scroll container to the top, re-asserting once on the next frame after layout settles. Every transition already routes through it — intro→Q1, Q1→Q2, Q2→result, Back, Start over — so no click handler needed its own copy. The container is resolved by a bounded walk from the quiz root up to the modal dialog, so it can never reach, let alone move, the page's own scroller. **No `window.scrollTo()` is involved and the underlying page position is never touched.**
- **Focus can no longer undo the reset.** `focusQuietly()` still uses `focus({preventScroll:true})`, but the fallback path for browsers without `FocusOptions` now captures the container's intended `scrollTop`/`scrollLeft` first and restores it if focus moved them — the modal's scroll state wins over the browser's scroll-into-view.
- **Verified with genuine browser interaction, not just scripted state:** a real `scroll_to` inside the modal followed by a real click on the organization and gift answers both land Question 2 at `scrollTop 0` with the eyebrow, headline, lead, progress label and question all fully visible (offsets 52/82/126/199/224px from the container top).
- **No regressions.** All four dismissal methods (X, Escape, backdrop, "Keep browsing this page") still restore the page to **exactly 0px delta on both axes** from four positions on a 5,053px page. Tab and Shift+Tab remain trapped at both boundaries on every step; the focusable set contains only visible in-dialog controls (5 / 5 / 4), so no keyboard user can land on hidden quiz content; the close button is present at every stage. Copy, routes, results, CTA wording, gold/navy CTA styling, destinations, UTMs, analytics, auto-open and consent behaviour all unchanged. The homepage and `/find-your-adventure/` standalone renders don't scroll internally, so the reset is a verified no-op there.

## 2026-07-29 (second pass) — Quiz conversion refinements: warmer copy, distinct educator answers, intentional gold CTA, compact modal (staging 1.19.95)

**Staging only. Production remains at 1.19.91 and was not touched.** Theme **1.19.93 → 1.19.95** on `staging2` (1.19.94 was an intermediate build superseded within the same pass — one release, two installs). Builds directly on the 1.19.93 entry below; routing architecture, destinations and analytics event names are unchanged. Full record: `RELEASES/QUIZ_UX_PERSONALIZATION_1_19_93.md` § "Second pass".

- **Copy.** Supporting line → "No wrong answers—tell us who you're here for and what would feel like a win. We'll match you with the most useful free resource and next step." Question 1 → "What would you like help with today?" Eyebrow, headline, the four Q1 answers and all four Q2 prompts unchanged. **No statistic was added.**
- **Educator answers are now mutually distinct.** "Vocabulary and discussion support" overlapped the "discussion and activities" answer; it is now **"History and vocabulary connections"** with its own result ("Connect the story to history and language."). The `quiz_intent` value stays `vocabulary_discussion` — `ANALYTICS/EVENT_MATRIX.md` has no quiz registry and therefore requires no migration, so continuity was preserved over cosmetic renaming.
- **Parent "less resistance" result** loses its negative framing: "…gives ages 6–9 a 20-minute, low-pressure way into the story—an easy first win to share."
- **CTA labels standardised.** Every launcher/start control → **"Find My Best Next Step"** (sitewide launcher and the homepage/standalone start button). Results → "Get My Free Adventure Kit" / "Get the Free Classroom Toolkit" / "Get Community Reading Resources"; the organization partnership answer keeps "Explore Group Orders & Partnerships". All 12 per-answer labels **and all 4 route-level fallbacks** updated — no stale labels. "Download" deliberately not used: these lead to resource landing pages.
- **The gold CTA is now intentional, not an accident.** `audience-quiz.css` had declared green-on-white, which never rendered — style.css's `.btn-primary { background: …!important; color: …!important }` outranked it, and that same `!important` also kills style.css's own `.btn-primary:hover`, so the button had **no working hover state anywhere on the site**. The quiz now declares gold/navy explicitly at `.bhp-quiz` scope using existing expedition tokens, with real hover / focus-visible / active states. Measured contrast **7.60:1** normal, **10.19:1** hover. Focus ring is navy, not the sitewide gold (which was near-invisible on a gold button). Verified sitewide `.btn-primary` buttons outside the quiz are unchanged.
- **Modal made compact; standalone presentation untouched.** The modal headline was inheriting `body:not(.home) h2` at **64px / 134px tall**; scoped to `.bhp-quiz-modal .bhp-quiz .bhp-quiz__heading` it is now **46–52px on desktop, 30px on mobile**. Dialog height at 1440×900: **584px → 546px**.
- **Two real layout defects found by measurement and fixed.** (1) The eyebrow's box overlapped the close button at desktop widths — content now clears it structurally. (2) The dialog itself scrolled, so the absolutely-positioned close button **scrolled out of view** on short viewports (measured at 1024×560: it ended 7px above the dialog's top edge, clipped). The dialog no longer scrolls; `.bhp-quiz` inside it is the single scroll region, so the close button stays pinned. **Exactly one scroll region at every viewport tested — no nesting.**
- **Working-tree consent fix validated, not just shipped.** An uncommitted `hasVisibleConsentUI()` rewrite (reads WPConsent's open shadow root; the old light-DOM/offsetWidth check could never fire) was preserved and verified live: after a consent choice it correctly returns **false** despite WPConsent leaving a persistent 44×44 floating button rendered, so auto-open is not permanently suppressed. Auto-open confirmed still working end to end.
- **No regressions.** All four dismissal methods restore the page position **exactly (0px, both axes)** from four positions on a 9,954px page with the launcher 5,991–8,591px below the fold, including after an automatic open. Back, Start over, focus management, body scroll lock, progress preservation, destinations, UTMs, consent gating all unchanged.

## 2026-07-29 — Find Your Adventure quiz: per-answer results, honest copy, scroll-position fix (staging 1.19.93)

**Staging only. Production remains at 1.19.91 and was not touched.** Theme **1.19.91 → 1.19.93** on `staging2` (1.19.92 was the first pass; 1.19.93 adds the contrast fix found during QA — one release, two installs). Full record: `RELEASES/QUIZ_UX_PERSONALIZATION_1_19_93.md`.

- **The second question now changes the recommendation.** Each Q2 answer carries its own `result_title` / `result_text` / `cta_label` (and optionally `destination`) in `template-parts/quiz/audience-quiz.php`; `audience-quiz.js` reads the selected option first and falls back to the route-level copy. All **12** answers verified live to produce 12 distinct results. The 4 audience destinations are unchanged — the **Frozen Audience Routing Constitution is not altered**, and no retailer route was added.
- **Answers that didn't match their result are gone.** Removed the educator route's "Author visit information" (it recommended the Adventure Learning Toolkit, which answers a different question) and "Read-aloud ideas"; removed the gift route's birthday/holiday/milestone occasions, which never changed the outcome. Author-visit intent now has **no** quiz destination — building one is a separate, unapproved task.
- **Copy honesty.** "is a good fit" removed everywhere; "Based on your answers" → **YOUR BEST NEXT STEP**; every "Get the Free …" CTA → "Explore …", because the visitor lands on a signup page, not a download. New eyebrow **2 QUESTIONS · ABOUT 30 SECONDS** (a real count and a real duration — no outside literacy statistic was added). "Question 1 of 2" / "Question 2 of 2" progress labels added.
- **Scroll-position defect fixed** (`assets/js/quiz-modal.js`). Closing an auto-opened modal used to dump the visitor at the footer: returning focus to the off-screen launcher made the browser scroll it into view. Position is now captured before open and re-asserted on close, focus uses `focus({preventScroll:true})` with a fallback, and the restore temporarily suppresses the sitewide `html{scroll-behavior:smooth}` so it is a jump, not an animation. **Measured live: 0px drift on all four dismissal routes (close button, Escape, backdrop, "Keep browsing this page"), after both manual and automatic opens.** Counterfactual measured on the same page: a plain `focus()` moves the page **+2454px**. Quiz progress is still not reset by closing.
- **Result screen inside the modal** now offers the audience CTA, **"Keep browsing this page"** (closes and restores position), and "Start over". The redundant "Open the full quiz page" link was removed; the canonical `/find-your-adventure/` page is unchanged and still reachable directly. The repeated eyebrow/heading/lead collapses on the result step — **result dialog needs no internal scrolling at any of the 8 tested widths** (320–1440).
- **Sitewide teaser** reworded to "Not sure which Brave Hearts path fits? Two questions will match you with the best next step for your reader, classroom, gift, or program." with CTA **"Show Me My Path"**.
- **Accessibility defect found and fixed (pre-existing, shipped in 1.19.91).** On the homepage the quiz card is repainted navy by `.home #find-your-adventure`, but the shared component still coloured its body copy for a cream card: the question prompt measured **1.25:1** and the lead/secondary copy **1.67:1**. Repointed at the existing light tokens in `style.css` — now **11.48:1** and **9.34:1**. Answer buttons were already fine (14.35:1).
- Focus management added: answering Q1 moves focus to the first Q2 option, answering Q2 moves it to the result headline, Back returns to the chosen Q1 answer. The advance affordance is a border-drawn chevron (`content:""`), so no screen reader announces a stray character.
- **Known, not introduced, not fixed here:** Tab from the last control in the modal leaks focus to the WPConsent plugin's `#wpconsent-container`. Reproduced identically on **production 1.19.91** and staging, with both synthetic and real key presses — pre-existing, logged as a follow-up.

## 2026-07-20 — Popups retired, homepage quiz promoted, homepage capture rerouted (production 1.19.91)

Deployed to production with Andrew's explicit approval. Theme **1.19.86 → 1.19.91**. Purchase path re-verified live after deploy.

- **Both lead-magnet popups retired sitewide** (Andrew, explicit). The quiz modal is now the only popup on the site. Suppressed via `bhp_show_parent_popup` / `bhp_show_teacher_popup` filters in `inc/audit-remediation.php` rather than deleting funnel code — one-line reversal; templates, the shared `mariana-popup.js` engine, storage/event prefixes, thank-you pages and Mailchimp tag mappings all left intact. **Removes POPUP email capture only** — inline forms on the parent landing page, /teachers/ and the four audience landing pages are unaffected.
- **Quiz auto-open opened to every eligible page.** Removed the /teachers/, shop, product and Complete Collection exclusions, and lifted the homepage exclusion. **This knowingly supersedes audit finding #20's commerce-page carve-out.** Cart, checkout, account and order-received remain excluded upstream — a modal over an active payment flow risks real revenue. Still capped to one auto-open per session.
- **"Join the Expedition" newsletter section removed from the homepage; the Find Your Adventure quiz promoted into its slot** with the same dark full-width treatment (`.home #find-your-adventure`). Not duplicated — the same single quiz instance moved up; verified exactly 1 quiz section in the homepage body.
- **Homepage email capture rerouted to the existing parent Adventure Kit funnel.** `lead_magnet` `explorer_passport` → `reluctant_reader_adventure_kit`. Previously it matched no tag case and got only a bare `Adventure Club` tag, and promised an "explorer_passport" printable that had **no configured PDF on either environment**. Now resolves to the same triple as the parent landing page — verified live: `Reluctant Reader Adventure Kit | Audience: Parent/Grandparent | Source: Parent Landing Page`. **No new Mailchimp tag, funnel, automation or PDF.** Copy corrected to describe the Adventure Kit that is actually delivered.
- **Regression caught and fixed:** deleting the newsletter section orphaned its `#adventure-club` anchor, which **7 sitewide links** targeted (footer, About, Books, Contact, Teachers, plus two nav/CTA rewrites in `functions.php`). All repointed to `/reluctant-reader-adventure-kit/`. Verified 0 remaining references. **Note:** link *labels* still read "Join the Expedition"/"Join the Adventure Club" — destinations fixed, wording not yet updated.
- **Production purchase path verified live post-deploy:** Mariana variation **334** auto-selects, add-to-cart succeeds ($11.99), Stripe renders 4 iframes, no "no payment methods", shipping $1.99, total $14.70, no "Perfect Bound", `wp.template` a function, zero console errors. Test cart emptied; **no order placed**.
- Rollback: `~/bhp-rollback-20260720-063726/` (theme 1.19.86 + baseline).

## 2026-07-19 — Production validated at 1.19.86; Fable JS findings closed as false positives

**Final release status: "Production validated. The failed Fable findings were caused by browser instrumentation injecting or altering lodash/underscore behavior and did not reproduce in a clean browser."**

After deploying theme **1.19.86** + bundle plugin **1.8.6** to production, a report of broken checkout / broken Mariana paperback was investigated as a potential emergency hotfix. **The failure does not exist on production.** Clean-browser validation of the live site: variation **334** auto-selects and adds to cart; Stripe card fields render (4 iframes); no "no payment methods available"; **no `template`, `memoize`, or `debounce` errors**; zero console errors; shipping/totals calculate. `window._` is genuine Underscore 1.13.8 (`_.runInContext` undefined → lodash not masquerading as `_`); exactly one lodash + one underscore on the page.

- **BH-01 and BH-02 marked PASSED in clean production validation.**
- **Fable lodash/underscore errors marked environment-specific false positives** — see `KNOWN_ISSUES.md` (REFERENCE entry). Indicators: duplicate/altered lodash-underscore globals; `wp.template` undefined *only* under instrumentation; clean production has one lodash + one underscore; variation 334 and Stripe both work.
- **No hotfix and no rollback performed. No production files, options, products, or settings changed.** The proposed fixes were each rejected with evidence: theme/plugin have zero lodash enqueues and zero `script_loader_tag` filters; no mu-plugins; SG JS optimization already fully off; removing core lodash would break Blocks/Stripe; removing `defer` would reintroduce the BH-02 race.
- **Both backup directories preserved:** `bhp-rollback-20260719-225125` (pre-deploy, intact) and `bhp-hotfix-backup-20260719-235856` (validation-time, unused).
- Test cart emptied (0 items); **no order placed**.

## 2026-07-19 — 2nd Fable audit: commerce/JS pre-production pass (staging only, theme 1.19.85)

Scoped commerce/conversion fixes from an independent second Fable audit (BH-01…BH-08). Staging-only; production untouched at 1.19.58; no commit/deploy yet.

**Phase 1 root cause (critical):** the reported `wp.template is not a function` / `…'memoize'` / `…'debounce'` errors do **not** originate in the site. The server emits one lodash, correct dep order (underscore→wp-util→lodash→`_.noConflict()`), SG JS-optimize off; in a clean browser the Mariana product adds correctly and checkout payment renders. `_`/`wp.template` being undefined on checkout is expected (underscore/wp-util aren't enqueued there; Blocks/Stripe use bundled lodash). All three errors share one cause — `window._`/`window.lodash` re-clobbered after WP's noConflict by an externally-injected duplicate utility lib (browser extension/instrumentation), matching the prior Claude-in-Chrome finding. **BH-01 does not reproduce clean → treated as externally pending Andrew's clean-device check; no Stripe/payment code changed.**

- **BH-02 Mariana add-to-cart (kept variable; 333/334 preserved exactly).** Removed the redundant second auto-select (`bhp_single_variation_ux` inline) that raced WooCommerce's **deferred** `wc-add-to-cart-variation`; kept `product-format-autoselect.js` as the sole implementation and gave it a `defer` strategy (`functions.php`) so it runs *after* the variation form initializes — deterministic, no timing luck. Verified: selector hidden, variation 334 auto-selected, real add-to-cart works, price $11.99, no console errors.
- **BH-03 "Perfect Bound" removed from customer surfaces (display-only; fulfillment metadata untouched).** Product page selector hidden (`.postid-333` CSS); `woocommerce_variation_option_name` normalizes the value "Perfect Bound"→"Paperback"/"Case Bound"→"Hardcover" (cleans the bundle drawer's format badge); redundant Blocks cart/checkout meta row hidden via CSS; classic `woocommerce_get_item_data` + `woocommerce_order_item_get_formatted_meta_data` filters cover order emails/received/account. Verified no "Perfect Bound" on product page, drawer, cart, or checkout.
- **BH-08 empty express-checkout frame.** A checkout-scoped script hides the express block + its "Or continue below" divider **only when no real wallet button (>20px) renders**, and self-heals to show them if a supported wallet (Apple Pay/Google Pay/Link) appears — so express stays functional where supported. Verified empty frame + orphaned divider suppressed, standard card checkout intact, no console errors. No Stripe registration or gateway-setting change.
- **BH-04 gift-guide thank-you journey.** New `page-gift-guide-thank-you.php` template + published page (slug `gift-guide-thank-you`); registered redirect key `gift_guide_thank_you`; wired the gift form's `success_redirect_key`. A real staging signup now redirects to a dedicated page confirming the **Meaningful Gift Guide** with a 15-minute delivery + Promotions/Spam note, Adventure Club as secondary context, and a Complete Collection CTA (individual-books option too) — replacing the generic "Welcome to the Adventure Club" inline message. Site naming already consistent ("Meaningful Gift Guide"); the source PDF (#392) internally reads "Ultimate Children's Book Gift Guide" — an asset mismatch flagged for Andrew (PDF not editable here). QA signup `bh04-qa-test-contact` (real address deliberately not recorded here) (remove/retain as desired).
- **BH-05 shipping policy accuracy.** Confirmed the per-tier "Subsidized Shipping" model is **intentional** (`bhp_bundle_shipping_amount`: tiers off distinct titles per format + mixed detection; the "3 identical hardcovers = $2.99 vs 3 distinct = $4.99" difference is a natural consequence, not a defect). No rate redesign. Rewrote Shipping Policy page 355 from "flat rate of $3.99 per order" to the accurate "$1.99 to $4.99" per-order range.
- **BH-06 mobile gift-page FAB collision.** `audience-landing.js` now sets an inline transform on `#bhp-floating-cart` (lifting it above the sticky Collection bar's live height) when the bar is visible — the FAB's `bottom` is overconstrained and the plugin drives its transform inline, so JS-inline is the only reliable layer. Transform confirmed applied in the DOM; the visual lift needs a real-device spot-check (the automation browser doesn't reflect fixed+transform under an emulated viewport — a documented machine quirk).

**Regression:** all 6 individual products add (Store API 201) with no "Perfect Bound"; cart renders 6 items, no overflow, quiz absent on commerce pages, no console errors. **Not deployed to production.**

## 2026-07-19 (prior) — Fable audit remediation: sitewide POD notice softened (staging only, theme 1.19.74)

Per Andrew's direction, the sitewide "Printed Just for You" commerce notice (`inc/class-bhp-printed-for-you.php`) no longer states "Most orders arrive within 1–2 weeks" (which reads as a delivery promise and can go inaccurate during holidays/printer/carrier disruptions). Replaced with: "Each book is **printed especially for your order**. Production and delivery times can vary, so please order early for birthdays, holidays, and other special occasions." Accurate, operationally safe, and consistent with the gift-buyer funnel. Verified rendered on a product page; this closes the last open decision from the Phase 13 package.

## 2026-07-19 (prior) — Fable audit remediation Phase 6 close-out + Phase 12 QA (staging only, theme 1.19.73)

Andrew's business decisions closed the four parked findings, and the consolidated Phase 12 staging QA passed. Awaiting his Phase 13 production approval.

- **#15 product-card convention:** keep "Buy Direct" (distinguishes direct-from-publisher from Amazon links); no "from $X" pricing added to multi-format `/books/` cards (price stays on product/format pages). No code change — confirmed as-is.
- **#24 educator procurement:** added an accurate bulk-inquiry FAQ to the educators page — schools/teachers/librarians/homeschool organizations may contact us about classroom, library, and larger-volume purchases, handled individually via direct inquiry. **No** claims of purchase orders, institutional invoicing, formal school terms, or W-9 availability.
- **#25 gift buyers:** gift-wrap "not currently offered" already present; removed two "arrive within 1–2 weeks" delivery-window promises (a FAQ and Collection body copy) in favor of print-to-order + order-early language, per the decision not to promise a specific production/delivery window. (Flagged separately: the site-wide "Printed Just for You" commerce notice still states "Most orders arrive within 1–2 weeks" — left unchanged pending Andrew's call on whether it's a formally-adopted Bookvault commitment.)
- **#26 organizations:** contact section now enumerates the accurate topics (literacy programs, classroom/community sponsorships, reading initiatives, event/program partnerships, bulk purchases) with "every request is reviewed individually." No fixed discounts, sponsorship packages, or guaranteed response times (the page already avoided these).

**Phase 12 consolidated QA (in-app Browser):** cart populated to all 6 books; collection pricing correct via Bundle Savings fees (−$3.98 PB, −$4.98 HC → $31.99 / $48.99 sets; total $90.83); duplicate-click blocked (two clicks → one batch add); drawer opens; #6 auto-select works; cart + checkout pages render; compact commerce header (#35) confirmed with items (158px desktop / 118px mobile); coupon field + order summary present; Hawaii → 0 shipping options, contiguous-US → "Contiguous US Shipping $4.99" (no Bookvault live rate); Stripe payment field renders (Test Mode) with **no console errors** (the prior Lodash/memoize failure did not recur in this non-Claude-in-Chrome browser); quiz modal absent on cart/checkout. No horizontal overflow at 320/375/390/768/1280; no PHP fatals. Test cart emptied.

**Production data migrations verified still pending on prod** (author, refund page draft, shipping copy, `woocommerce_allowed_countries=all`, menu_order=0, Amazon post link, post 82 title) — exact ordered checklist + rollback recorded in `docs/RELEASES/FABLE_AUDIT_REMEDIATION.md` (Phase 14). Theme ZIP v1.19.73 allowlist verified clean (no docs/tmp/plugins/.git; zero staging URLs).

**Not deployed to production** — staging only, holding at the Phase 13 approval gate.

## 2026-07-19 (prior) — Fable audit remediation Phase 9: image & visual quality (staging only, theme 1.19.71)

Part of the Fable audit remediation release (staging-first, stopping at the Phase 13 review gate).

- **#35 compact cart/checkout header.** The tall "Brave Hearts Field Journal / FIELD NOTE · BHP" interior-hero rendered on cart/checkout/order-received, pushing the transactional content far down (199px hero; cart content at y≈372 on mobile). Added CSS scoped to `.woocommerce-cart/.woocommerce-checkout/.woocommerce-order-received`: hero padding + title scale reduced, the decorative "FIELD NOTE · BHP" coordinate hidden, the brand "Field Journal" eyebrow kept. Cart verified: hero 199→118px, content moved up to y≈292, no overflow, cart still renders. **No checkout/payment markup touched** — CSS only, on the page wrapper. Checkout-with-items header to be confirmed in the Phase 12 matrix.
- **#33 product image resolution — verified adequate.** Cards use `wp_get_attachment_image('bhp-book-card' 480×640)`, which emits a WP `srcset` (live: candidates 198–1318w) with `sizes`; at DPR 2 a 294px-rendered cover loaded the 768w candidate — comfortably above the ~588px retina target. Retina candidates present, originals not forced, crops unchanged. No change needed.
- **#34 testimonial repetition — already safe, no fabrication.** 7 approved Amazon reviews exist (Mariana ×4 incl. Payton, Everest ×2, Amazon ×0 — correctly empty). The showcase renders per-book, in order, approved-only. Payton on the Complete Collection page is Andrew's explicit 2026-07-05 direction (documented in code), and the homepage uses a different (Kirkus) quote — so this is not accidental repetition. Broader per-audience variety is bounded by the genuine review supply; nothing paraphrased or invented.
- **#36 aesthetic consistency — substantially covered.** The load-bearing rhythm/spacing work landed under #10 (hero scale), #11 (44px tap targets), #12 (visible CTA affordance), #13 (mobile section padding), and #35 (commerce header); every touched page was checked for no 320–430px horizontal overflow. No new design system, fonts, or palette. Residual micro-polish left for Andrew's Phase 13 visual review.

**Not deployed to production** — staging only, stopping at the Phase 13 review gate.

## 2026-07-19 (prior) — Fable audit remediation Phase 8: blog flow (staging only, theme 1.19.70 — content edits)

Part of the Fable audit remediation release (staging-first, stopping at the Phase 13 review gate). No theme code changed this phase — two per-environment content edits plus verification of an already-built module.

- **#29 end-of-post conversion module — already implemented; verified.** The intent-aware module the finding describes already exists as `BHP_CTA_Engine` (a registry keyed by destination type, presentation style, audiences, intents, and funnel stages, with `render_for_post()`) plus a curated `guide-continuation` block for guide-registry posts. Verified that different post intents get different, topic-relevant CTAs — a reading-level post ends with "Follow This Trail Further → Reading & Growing" (`/teachers/#reading-growing`) while a teacher-guide post ends with an educator-resources continuation — not the same aggressive Collection CTA everywhere. No change made.
- **#30 Amazon post link fixed.** The "10 Amazon Rainforest Facts for Kids" post linked its book mention to a bare category archive (`/product-category/the-amazon/`); changed to the exact product page (`/product/adventures-of-charlotte-and-henry-the-amazon-paperback/`). Staging post 546; prod post 366 needs the same replace.
- **#31 title typo fixed.** Post 82's title "…What Level Should My Child Be At??" → "…At?" (slug preserved, no Rank Math override). Verified in the rendered `<title>`. Prod post 82 needs the same one-character title fix.
- **#32 content-overlap — appendix, deferred to traffic.** No destructive edits; URLs preserved. Candidate overlap clusters (Dog Man, Lexile, bridge/reluctant-reader) inventoried from the guide registry for the Phase 11 traffic plan; final consolidate/differentiate decisions require GSC data and Andrew.

**Not deployed to production** — staging only, stopping at the Phase 13 review gate.

## 2026-07-19 (prior) — Fable audit remediation Phase 7: contact & about (staging only, theme 1.19.70)

Part of the Fable audit remediation release (staging-first, stopping at the Phase 13 review gate).

- **#27 native contact form (replaces mailto).** The theme's provider-neutral contact form was dormant — no external form provider is configured on staging or production, so the page silently fell back to a `mailto:` link. Built a lightweight native handler (no form platform) in `inc/audit-remediation.php`: a `bhp_contact_form_action` filter defaults the form to `admin-post.php` (any future external provider still wins), and `bhp_handle_contact_submit` verifies a WordPress nonce and a honeypot, performs server-side validation, sends via `wp_mail` to a **server-controlled** recipient (`admin_email` — never a user-supplied address), and redirects to `?bhp_contact=success|invalid|error#contact-form`. The template now renders the nonce/honeypot/action fields, an aria-live success/error/invalid status message, and a production-gated `contact_submit`/`contact_error` dataLayer event; the "Prefer Email?" section is the visible email alternative, and the "no student information" privacy note is retained. Verified on staging via the in-page nonce: honeypot submission → success **without sending**, missing-required → invalid, bad nonce → error; all three status messages render. (`should_render_analytics` is false on staging, so the analytics event fires on production only, consistent with every other event. Real `wp_mail` delivery to confirm on production, where the domain's SPF/DKIM apply.)
- **#28 About page credibility — real ICU-nurse background surfaced.** The founder's authentic ICU/travel-nurse story already existed in the About page's post content (Andrew's own words) but the template rendered generic hardcoded defaults, so it never showed. Added a `founder_text_3` paragraph (editor-overridable) drawn faithfully from that text — ICU nurse, COVID and neuro intensive care, now a travel nurse — framed around courage, care, steadiness, and respect for science, explicitly **not** presented as a teaching credential, with Charlotte & Henry kept central. Nothing invented. Verified rendered with no overclaim and no overflow.

**Not deployed to production** — staging only, stopping at the Phase 13 review gate.

## 2026-07-18 (prior) — Fable audit remediation Phase 6 (partial): parent funnel post-signup (staging only, theme 1.19.68)

Part of the Fable audit remediation release (staging-first, stopping at the Phase 13 review gate). 3 of 6 findings done; the other 3 are gated on business facts only Andrew can confirm (no fabrication).

- **#21 parent lead-magnet naming — already consistent.** Verified the canonical "Reluctant Reader Adventure Kit" appears at the primary touchpoints (landing template title, thank-you H1) with a consistent "Free Adventure Kit" CTA shorthand; no genuinely divergent name exists. The landing page is Andrew's supplied custom design and was not rewritten; the Email 1 subject/body live in Mailchimp, not the repo. No change made.
- **#22 parent thank-you commercial hierarchy.** `page-adventure-kit-thank-you.php` now leads (after the download/inbox instructions) with a compact "Continue the adventure" Complete Collection module as the primary next step, placed above the individual-book cards, which are reframed as the secondary path ("Prefer to start with a single story?"). Collection CTA → `/complete-collection/` with `collection_upsell_click` / `parent_thank_you` tracking; no prices hardcoded. Verified section order and render.
- **#23 welcome-email timing copy.** Thank-you copy updated to "Please allow up to 15 minutes for it to arrive, and check your promotions or spam folder if you don't see it." Mailchimp journey timing untouched.
- **#24 / #25 / #26 — parked for Andrew (business facts).** Educator procurement (POs/W-9/bulk terms), gift-buyer gift-wrap + POD lead-time claims, and organization bulk/sponsorship + response-time claims all require confirmed facts that must not be invented. Flagged for Andrew's input before these copy blocks can be written.

**Not deployed to production** — staging only, stopping at the Phase 13 review gate.

## 2026-07-18 (prior) — Fable audit remediation Phase 5: quiz & modal usability (staging only, theme 1.19.67)

Part of the Fable audit remediation release (staging-first, stopping at the Phase 13 review gate).

- **#19 quiz modal Escape close — already implemented; verified.** The audit finding was stale: the 2026-07-17 `quiz-modal.js` rewrite already added Escape close, focus return to the launcher, focus entry, Tab focus-trap, backdrop/close-button close, quiz-state preservation, and per-session suppression, with `role="dialog"`/`aria-modal="true"`/`aria-labelledby` in the markup. Verified live on a staging product page: opening moves focus into the dialog; pressing Escape closes the modal, returns focus to the launcher, and sets `aria-expanded="false"`. No code change made.
- **#20 commerce-page quiz auto-trigger (cautious).** Confirmed first that `bhp_should_show_any_popup()` already blocks every popup on cart/checkout/account/order-received. The remaining gap was the timer/scroll **auto-open**, still eligible on shop/product/Complete-Collection browsing pages. Updated `bhp_should_autoopen_quiz()` to also return false on `is_shop()`/`is_product_taxonomy()`/`is_product()`/`complete-collection` — gating auto-open only; the manual launcher still renders on those pages, and auto-open still fires on canonical/blog pages. Verified across page types: product/shop/Complete-Collection render `data-bhp-quiz-autoopen="false"` with the launcher still present, while `/about/` (canonical) renders `="true"`. No console errors; session-suppression flag unchanged.

**Not deployed to production** — staging only, stopping at the Phase 13 review gate.

## 2026-07-18 (prior) — Fable audit remediation Phase 4: /teachers/ hub flow (staging only, theme 1.19.66)

Part of the Fable audit remediation release (staging-first, stopping at the Phase 13 review gate). Verification via live JS DOM measurement.

- **#16 /teachers/ progressive disclosure.** The hub rendered all 72 guide cards inline (55,046px ≈ 67.8 screens at 375px; reading-growing 24 cards, family-resources 29). Added progressive **enhancement** (`page-teachers.php` grids get `guide-article-grid--collapsible` when >6 cards; inline JS collapses each to the first 6 and injects an accessible toggle with `aria-expanded`/`aria-controls`, "View all N field notes" ↔ "Show fewer field notes"; `style.css` hides `:nth-child(n+7)` when `.is-collapsed`). All cards stay in the HTML so crawlers/no-JS users lose nothing — JS is the only thing that ever collapses. Result: 55,046→31,139px (67.8→38.3 screens), toggle verified 6↔24 with correct aria + label, search + topic-anchor nav + compact toolkit module intact, `/teachers/` not redirected, no console errors.
- **#17 Amazon destination hub (cautious).** Audited first: production has a real Amazon article ("10 Amazon Rainforest Facts for Kids", post 366, published) that was never wired into the guide registry, so the Amazon book — unlike Mariana/Everest — had no destination trail. Added it to the registry (`$science` + `$destinations`), added an `amazon-rainforest` hub to `bhp_get_guide_hubs()`, added it to the destination + collection loops in `page-teachers.php`, and a `.guide-destination-card--amazon-rainforest` canopy background in `style.css`. **Presence-guarded** — the destination card and section only render once the hub has a published post, so neither environment ever shows a "Coming Soon"/empty hub (this guard now also protects Mariana/Everest). Verified on staging in both states: with the post as draft the hub is correctly absent (Mariana/Everest unaffected); after publishing the staging draft (parity with prod) the Amazon destination card + "The Amazon Rainforest" section render with the one real article, canopy image HTTP 200, no console errors. **No production content step** — prod post 366 is already published, so the theme deploy alone activates the hub there.
- **#18 Kindle reference removed.** On-site, every `/books/` card lists only Paperback/Hardcover (no Kindle product or link exists), but the `#book-formats` heading read "Kindle, Paperback, and Hardcover" — implying an on-site Kindle format. Changed to "Paperback and Hardcover" (`page-books.php`); verified 0 Kindle mentions on `/books/`. The `functions.php` format-detection code that recognizes a Kindle attribute is left in place (harmless — it only activates if a product actually carries that format). A genuine external Amazon-Kindle link would be a separate additive merchandising decision (affiliate disclosure) and was not added.

**Staging content note:** staging draft post 546 (`amazon-rainforest-facts-for-kids`) was published for #17 QA parity; production already has it published (366), so no prod content action is required. A stray duplicate (605) created during the sync was trashed.

**Not deployed to production** — staging only, stopping at the Phase 13 review gate.

## 2026-07-18 (prior) — Fable audit remediation Phase 3: homepage flow & aesthetic refinement (staging only, theme 1.19.64)

Part of the larger Fable audit remediation release (36 findings across 15 phases; staging-first, stopping at the Phase 13 review gate before any production deploy). All work theme-code only — no per-environment DB/settings changes in this phase. All verification via live JS-based DOM measurement (screenshot tooling times out on this machine, consistent with prior sessions).

- **#12 Learning Hub routing + hidden CTA text.** Live WP-CLI check confirmed none of the six curiosity slugs (animals/science/geography/conservation/explorers/activities) exist as a page or category, so all six cards were falling back to `/blog/`; `/teachers/` has only builder-generated hex anchors (no per-topic anchors). Changed the `bhp_get_learning_category_url()` fallback (`functions.php`) from the generic `/blog/` to a real per-topic **blog-post search** (`/?post_type=post&s=<topic>`) — each topic returns genuine posts (3–35), so every card now lands on a distinct, topic-faithful destination; the page→category resolution is kept ahead of it so any future taxonomy auto-upgrades the card. Scoped to `post_type=post` after verifying a bare `?s=` also surfaced pages like "Privacy Policy". CSS: the `.feature-card--field-note .feature-card__link` was `color:transparent` and stretched over the whole card (invisible affordance); restored a visible forest-green "Explore … →" label with an accessible `::after` overlay that preserves full-card clickability. Verified 1280/375/320px.
- **#14 Newsletter placeholder truncation.** Shortened the homepage email placeholder default (`front-page.php`) from "Your email - no noise, just wonder" (34 chars, truncated on narrow phones) to "Your email address". Accessible `<label for>` was already present; no DB override existed. Verified no truncation 320–430px (text ~120px vs input ~239px).
- **#10 Mobile hero CTA reachability.** At 375×812 the primary "Get the Complete Collection" CTA sat at y≈947, below the fold, behind a 1489px hero. Appended a `≤480px`-scoped hero block (spacing + type scale only, no copy change) trimming hero padding, eyebrow/lead/actions margins, and title/lead font size. CTA now fully within the first screen (top ≈685–694, bottom ≈754–763, within 812) and clear of the 93px sticky header (eyebrow ≈109) at 320/375/390px. Desktop untouched (media-query scoped).
- **#11 Mobile tap targets (~44px).** Gave discrete nav/CTA link groups a ≥44px tap height on `≤600px` — audience gateway links, footer nav/learn/contact/audience-cluster links — via `min-height` (type size unchanged). Genuinely inline prose links and the dot-separated legal row (`.footer-bottom__link`) keep the WCAG 2.5.8 inline exception. Learning Hub cards were already full-card tap targets via the #12 `::after` overlay. Verified all named groups now min 44px; no horizontal overflow.
- **#13 Homepage length (cautious).** Trimmed the `≤768px` section padding on the tall homepage sections (philosophy/origin/destinations/learning-hub/together/trust/newsletter and books-path bottom) from 88px to 56px — mobile vertical rhythm only, **no section/CTA/quiz/founder/trust content removed**. Homepage height 17,454→16,974px at 375px. Copy-level consolidation of the repeated commercial asks is deliberately **deferred pending traffic evidence** (a merchandising decision), per the finding's "evidence required before deletion" constraint.
- **#15 Product-card consistency — parked for Andrew's decision.** Evidence gathered: image crops already consistent (`bhp-book-card` everywhere); homepage book-bearing cards are the **destination cards** ("Shop [Book] →", with price), `/books/` uses **purchase-hub cards** ("Buy Direct" + "Shop Formats", no price, Amazon affiliate row alongside). The remaining divergences (whether multi-format `/books/` cards should display a price, and whether "Buy Direct" — an intentional direct-from-publisher signal distinct from the page's Amazon links — should be flattened to match "Shop [Book]") are merchandising choices, not clear-cut bugs. Recommendation deferred to the Phase 13 review gate rather than making a risky editorial change to commerce CTAs.

**Not deployed to production** — staging only, stopping at the Phase 13 review gate.

## 2026-07-18 (prior) — Fable audit remediation Phase 1–2: product-page trust/UX fixes + Complete Collection add-to-cart performance (staging only, theme 1.19.59 / bundle plugin 1.8.6)

Part of the larger Fable audit remediation release (36 findings across 15 phases; staging-first, stopping at the Phase 13 review gate before any production deploy).

**New theme module `inc/audit-remediation.php`** (required from `functions.php`), covering four product-page findings, all render-verified live on staging:
- **#5 Complete Collection upsell card** — format-aware card injected on single-product pages (`woocommerce_after_single_product_summary`, pri 15) via `bhp_product_collection_upsell()`. Paperback pages show "$35.97 separately / $31.99 collection / Save $3.98 → See the Complete Paperback Collection"; hardcover pages show "$53.97 / $48.99 / Save $4.98 → Complete Hardcover Collection". Skips silently if the bundle plugin is inactive or the product isn't in the bundle catalog. Analytics: `data-bhp-event="collection_upsell_click"`.
- **#6 single-variation UX** — the Mariana paperback is the only variable product (variation 334). `bhp_single_variation_ux()` hides the `.variations` selector and auto-selects the sole variation. **Verified end-to-end**: selector `display:none`, `variation_id` input auto-populates to 334, Add-to-Cart is enabled, and a real click adds variation 334 to the cart and opens the side drawer — the hidden selector does not break the purchase path.
- **#8 empty reviews tab** — `bhp_hide_empty_reviews_tab()` unsets the WooCommerce "Reviews (0)" product tab when `get_review_count() === 0` (no fabricated review schema; honest absence). Verified: product tabs render without a Reviews tab.
- **#9 SKU→ISBN relabel** — `bhp_relabel_sku_as_isbn()` relabels the product-meta "SKU:" label to "ISBN:" on product pages via `gettext`. Verified: pages render `ISBN: 9798234014016`.
- Also includes `bhp_redirect_legacy_author_slug()` (301 the old author slug → `/author/andrew-signore/`).

**Complete Collection add-to-cart performance (bundle plugin `assets/bundle-drawer.js`).** Measured the existing click-to-drawer path first: adding all three books fired **6 Store API requests, of which 3 were sequential `POST /cart/add-item` calls**, settling in ~5.3s. Replaced the sequential adds with a single Store API `POST /batch` (`addItemsBatch()` → `addTitles()`), keeping the proven sequential path as an automatic fallback if `/batch` ever returns a non-2xx sub-response. **Gotcha discovered and fixed during staging verification:** the Store API `/batch` endpoint validates the Nonce header on **each inner sub-request**, not just the outer request — an outer-only nonce returns `401 woocommerce_rest_missing_nonce` on every sub-response, silently forcing the fallback (net: an extra failed round-trip, zero speedup). Fixed by calling `ensureNonce()` first, then stamping the nonce into every sub-request's `headers`. **After (verified live, both formats, desktop + mobile 375px):** one `POST /batch` adds all three books in a single round-trip; total dropped to **5 Store API requests**, cart populated and drawer open by **~1.6–2.1s** (~3× faster). Correct 3-book contents and pricing in both paperback and hardcover.

**Immediate button feedback + duplicate-click prevention** (`initBundleFormFeedback()`): a capture-phase submit listener disables the button, sets label "Adding to cart…" + `aria-busy`, and a `__bhpBusy` guard blocks duplicate submits (12s safety timeout; restored when the drawer opens). Verified live: first submit sets the busy state; an immediate second submit produced **zero** additional Store API requests (cart stayed at 3, not 6).

**Not deployed to production** — staging only. Screenshot tooling timed out this session (consistent with prior sessions); all verification above used live JS-based Store API / DOM measurement rather than screenshots.

## 2026-07-18 (prior) — Educator Toolkit module on /teachers/ resized to a compact supporting band (staging only, theme 1.19.58)

Follow-up correction to the 1.19.55 audit-fix module: the initial implementation was functionally correct but visually read as a full landing-page hero (oversized headline, section filling most of the first viewport, empty right column, risk of the hub being mistaken for the Educator Toolkit landing page itself). Rebuilt as a compact two-column supporting band: left column (eyebrow "Free resource for educators", heading "Bring every adventure into the classroom.", one line of body copy), right column (primary "Get the Free Educator Toolkit" CTA + a subordinate "Browse the Expedition Guides ↓" text link that anchors down to the destinations section immediately below — added `id="guide-destinations"` to that section for the anchor target, matching the id convention every other section on this page already uses). Removed the old scroll-hint sentence from the body copy (now redundant given the explicit secondary link).

Extended `template-parts/components/teacher-resources-cta.php` with a backward-compatible `compact` + `secondary_link` arg pair — when unset, output is unchanged for existing callers (`page-books.php`, `BHP_Campaign_Landing`). Added a `.teacher-resources-cta--compact` CSS variant: desktop heading clamps to 30–54px (was up to 72px), section padding reduced so the module measures ~347px tall at 1440px width (target was 350–450px), content capped to a 960px-wide two-column row instead of stretching the full container. Hit one real specificity bug during implementation: an existing `body:not(.home) .section { padding-block: ... }` rule outranked the first version of the compact override, so the padding fix silently didn't apply — fixed by matching/exceeding that selector's specificity (`body:not(.home) .teacher-resources-cta--compact.section`). Also hit a browser-cache-only issue where two CSS edits under an unchanged `?ver=1.19.56` query string were served stale by the browser's own HTTP cache despite the server file changing and SiteGround's server-side cache being purged each time — resolved by bumping the theme version string (1.19.56 → 1.19.57 → 1.19.58) to force a fresh asset URL each time, not a defect in the fix itself.

**Verified live on staging:** desktop (1440px) — 347px section height, 48.96px heading, 644px/276px two-column split, no horizontal overflow, confirmed the floating cart re-entry button (`.bhp-floating-cart`, fixed bottom-right) sits 113px clear of the CTA button with no visual collision. Tablet (768px) — stacks to a single 700px-wide column, no overflow. 375px and 320px — stacked, headings 26.25px/24px (materially smaller than the original), full-width 48px-tall buttons, secondary link visible directly beneath the primary CTA, zero horizontal overflow at either width. Destination URL, CTA Engine analytics attributes (`contextual_cta_click` / `educator_toolkit_teachers_hub` / etc.), and page section order are all unchanged from the 1.19.55 version — zero console errors observed.

**Not deployed to production** — staging only, per explicit instruction.


Implemented the two approved corrections from an independent, repo-blind, live-browser-only production audit. Narrowly scoped — no redesign, no reopened strategy.

**Change 1 — Educator Toolkit connected to the `/teachers/` hub.** The audit found every prominent teacher-facing nav link/CTA led to on-page guide content, never to the actual Educator Learning Toolkit landing page (`/educators-adventure-learning-toolkit/`). Added a conversion module to `page-teachers.php`, placed after the intro/topic-nav section and before the guide/destination archive content (visible without a near-footer scroll, confirmed via live DOM position check) — reuses the existing `template-parts/components/teacher-resources-cta.php` component rather than new markup. `/teachers/` remains the guide/content hub; nothing was replaced or redirected. Extended `teacher-resources-cta.php` with optional `link_cta_id`/`link_cta_placement`/`link_cta_destination`/`link_cta_audience`/`link_cta_funnel_stage` args that add the CTA Engine's existing `data-bhp-event="contextual_cta_click"` attribute set when supplied — omitted entirely (byte-identical markup) for existing callers (`page-books.php`, `BHP_Campaign_Landing`), so nothing else changed behavior. Also added a subordinate second CTA ("Get the Free Educator Toolkit") to the homepage's "For Teachers & Classrooms" sales-path card in `front-page.php` — the existing "Open Classroom Resources" CTA is unchanged and remains primary; the card's markup was restructured from a single `<a>` to a `<div>` wrapping an inner link (`display: contents` in CSS to preserve the exact prior layout) plus the new sibling CTA, since two links can't nest inside one `<a>`. The secondary CTA is hidden at the ≤700px compact single-line treatment (no room in a 44px row) — the toolkit stays reachable there via the new homepage gateway module, the `/teachers/` module itself, and the sitewide footer cluster.

**Change 2 — Early homepage audience gateway.** The audit's second finding: audience routing (the quiz) sat ~12,000px down the homepage, past nearly all book/founder/educational content. Added a new compact module (`template-parts/components/audience-gateway.php`), placed after the Kirkus credibility section and before the Philosophy section — well before the book/founder content, after the hero/trust intro, and not competing with the primary Complete Collection hero CTA (confirmed via live DOM position check). Heading "What brings you here today?" with 4 direct crawlable links (reluctant reader, classroom resources, meaningful gift, community reading program) using the same CTA Engine analytics attributes as Change 1, plus a secondary "Not sure? Take the 30-second quiz" prompt that anchor-links to the existing shared quiz section lower on the page rather than opening a new instance. Extended `template-parts/quiz/audience-quiz.php` with a new optional `id` arg (previously always auto-generated via `wp_unique_id()`, unpredictable) so `front-page.php`'s existing bottom quiz call could be given a stable `id="find-your-adventure"` for the anchor link to target — no other behavior change to the quiz. Complete Collection stays the primary commercial offer; the embedded quiz, sitewide quiz modal/trigger, and footer audience cluster are all unchanged; no audience pages were added to primary navigation; the quiz still never collects email.

**QA (staging, theme 1.19.55):** Both modules' exact placement confirmed via live `compareDocumentPosition` DOM checks, not assumption. Desktop/tablet/375px/320px breakpoints checked on both the homepage and `/teachers/` — no horizontal overflow (`scrollWidth` vs `innerWidth`). All 4 direct gateway links resolve to the correct destinations; the quiz anchor-link correctly targets `#find-your-adventure`; the quiz's full flow (manual open via footer launcher, all 4 routes, restart) is unaffected by the `id`-override change. Homepage newsletter form, header, and footer unaffected. Zero console errors observed on homepage or `/teachers/`. Analytics verified via `dataLayer` inspection — `contextual_cta_click` fires exactly once per click on all new CTAs with correct `cta_id`/`placement`/`destination`/`audience`/`funnel_stage`; `quiz_cta_viewed`/`quiz_cta_clicked` fire correctly from the new gateway's quiz prompt with `bhp_source`/`entry_location` = `homepage_gateway`, distinguishing it from other quiz entry points. Regression-checked clean: shop page, one product page, one blog post's contextual link, and all 4 audience landing pages (Educator/Parent/Gift Buyer/Organization — signup form present with correct `admin-post.php` action, zero console errors on each).

**Screenshot tooling failed this session** (repeated timeouts, consistent with prior sessions) — all visual/layout verification above used live JS-based DOM measurement instead of actual screenshots; a manual visual spot-check is still recommended before production.

**Not deployed to production** — staging only, per explicit instruction.

## 2026-07-18 (prior) — Homepage form UX fix + sitemap root cause identified, subsequently resolved on production (staging only, theme 1.19.54)

**Homepage "Join the Adventure Club" form.** Investigated as a reported release-blocking defect ("appears interactive but does not successfully do anything"). Root cause is **not** a broken Mailchimp integration — the backend (`bhp_handle_mailchimp_signup()` in `inc/mailchimp.php`, shared by every acquisition form sitewide including the 4 working audience forms) was proven working via the theme's own local `BHP_Lead_Event_Log` audit trail: two real submissions to this exact form in the hour before this fix — my own test and a separate submission from Andrew's real `Asignore19@icloud.com` address — both recorded `success`, correct MC4WP list (`2c0c9a25a3`), tag `Adventure Club` applied. `explorer_passport`/`parents_families` are the documented, intended values (`docs/Mailchimp-Production-Integration.md`), not stale leftovers.

The real defect is UX: the Adventure Club section sits ~12,000px down the homepage (last section before the footer/quiz, after 9 other sections), and its only success feedback was a small inline text message reached via a full-page 303-redirect + URL-fragment scroll — a mechanism this session's tooling could not confirm reliably scrolls into view for real users (a structurally hidden-viewport browser tab, confirmed via `document.visibilityState` and a failed sanity-check `window.scrollTo()` call, cannot validate any scroll behavior at all this session — flagged honestly, not claimed as fixed by observation). A real user landing back at the top of a long page with zero visible confirmation is a completely plausible match for "does nothing."

**Fix** (`assets/js/acquisition-form-ux.js`, new; enqueued sitewide in `functions.php` alongside `bhp-nav`): after a redirect back with a `#{form_id}-status` fragment, explicitly scrolls that element into view via `scrollIntoView()` on a `setTimeout` (deliberately not `requestAnimationFrame`, which this session confirmed is starved on hidden/backgrounded tabs and would risk silently no-op'ing in some real situations too). Also adds a standard busy state (disables the submit button, "Sending…") on submit as a duplicate-submission guard, with a `pageshow`/`bfcache` safety net to un-stick the button if a visitor navigates back. No change to validation, redirect, or Mailchimp logic — a visibility layer only, verified not to break the underlying working POST/redirect flow (native submission still completes, `BHP_Lead_Event_Log` still records success). **The actual on-screen scroll behavior could not be visually verified this session (tooling limitation) — needs a real foreground-browser check**, same caveat pattern as the quiz scroll-trigger from the prior release.

Also found via `docs/Mailchimp-Production-Integration.md`: resource-delivery/welcome automation for the `Adventure Club` tag is explicitly documented as a **separate, manual, Mailchimp-side configuration step** ("Configure any resource-delivery or welcome automation separately in Mailchimp") — this session has no live Mailchimp browser access to confirm whether that automation was ever built. If it wasn't, a technically-successful signup delivers nothing to the subscriber, which would also read as "did nothing." Flagged for Andrew to check directly in Mailchimp Automations — not assumed either way.

**Sitemap investigation (this session).** All 4 audience landing pages are `index,follow` with correct canonicals/descriptions on production, but none appeared in Rank Math's `page-sitemap.xml` (19 URLs total, none of the 4) at the time of this investigation. Ruled out empirically, in order: per-page `rank_math_robots` meta (none set, same as control pages that ARE in the sitemap), sitewide Rank Math sitemap/robots settings (`pt_page_sitemap: on`, `pt_page_robots: [index]` — same as control pages), theme/plugin-level `rank_math/sitemap/*` filter hooks (zero found in the codebase), sitemap-specific transient caching (none exist), stale rewrite rules (`wp rewrite flush` + SiteGround cache purge — no change), and a stale `save_post` hook (`wp post update 348` to force a genuine resave — no change). This session also identified a plausible contributing factor (all 4 pages have essentially empty `post_content` since they are 100% custom-PHP-templated and never call `the_content()`) and proposed adding real `post_content` as one possible fix — **superseded by the actual resolution below; the empty-`post_content` theory was not what fixed it.**

**RESOLVED (2026-07-18, production, no theme/code change):** Rank Math's stale physical sitemap cache was cleared with explicit approval and `page-sitemap.xml` was regenerated on production. URL count went from 19 to 30; all 4 audience pages (`/reluctant-reader-adventure-kit/`, `/educators-adventure-learning-toolkit/`, `/gift-buyers-guide/`, `/organizations-community-reading-kit/`) are now present, along with several other newer legitimate pages that had also been missing from the stale cache. No page content, metadata, Rank Math settings, or theme code was changed — the fix was a cache clear + regeneration only. **Confirmed live** on production's `page-sitemap.xml`: 30 URLs, all 4 audience pages present. No sitemap work remains outstanding.

## 2026-07-17 (prior) — Audience-page discoverability layer (staging only, theme 1.19.53)
Andrew's stated concern: the 4 audience landing pages (Parent, Educator, Gift Buyer, Organization) depended almost entirely on the sitewide quiz for discovery — closing the popup, having it session-suppressed, or not wanting to take a quiz left a visitor with no passive route to 3 of the 4 pages. Approved 3-layer fix, staging only:

1. **Contextual CTA engine** (`inc/class-bhp-cta-engine.php`, `inc/class-bhp-content-classification.php`) — added `educator_toolkit_signup`, `gift_guide_signup`, `community_reading_kit_signup` registry entries alongside the existing `adventure_kit_signup`/`teacher_resource`; added `organization` audience and `gift_occasion`/`literacy_program`/`homeschool_curriculum` intents to the classification taxonomy so future blog content can be scored toward the right destination. Verified via `wp eval`: all 3 new entries resolve correct URLs, and 5 scoring scenarios (gift/organization/homeschool/teacher/parent content) route correctly with no regression to existing teacher/parent behavior. **Real finding, not fixed by this alone:** the live end-of-article mechanism for all 36 currently-published posts is `related-content.php`'s guide-continuation block (driven by the separate, hand-curated `bhp_get_guide_registry()`), not this CTA engine's scored selection — the CTA engine only fires for posts outside that ~30-slug registry, i.e. future posts. Extended `related-content.php` itself to add a real Educator-toolkit link alongside the existing hub-anchor link, plus new (currently unused, since no existing post matches) Gift Buyer/Organization conditionals for future post classification.
2. **Footer audience cluster** (`footer.php`, `style.css`) — new "Resources for Every Reader" section below the main 4-column footer grid, 4 intent-based links ("Helping a reluctant reader?" / "Shopping for a meaningful gift?" / "Teaching or homeschooling?" / "Planning a reading program?"), visually subordinate (smaller, lower-contrast) to primary nav, real crawlable `<a href>` markup, present sitewide.
3. **Homepage direct-access line** (`template-parts/quiz/audience-quiz.php`, `assets/css/audience-quiz.css`) — one line beneath the quiz intro card's "Take the Quick Quiz" button, inline-linking all 4 audience names for visitors who don't want to answer the quiz.

**Content-audit finding (honest, not fabricated):** all 36 currently-published posts were reviewed by title/topic. None are naturally Gift Buyer or Organization intent (no holiday/gift/donation/reading-program content exists yet) — 0 posts were force-reclassified. This is a genuine content gap for the weekly production system to address going forward, not a shortcoming of this pass.

**QA:** deployed to staging via direct file copy (theme 1.19.52 → 1.19.53), `wp eval` clean, no PHP fatal. Verified live: footer cluster + homepage line render correctly with 0 page overflow at 1280px/768px/375px on both home and interior pages; guide-continuation's new Educator link confirmed live on a real registry post. **SEO audit:** all 4 pages on production are `index,follow` with correct self-referencing canonical and real meta descriptions (staging is sitewide `noindex,nofollow` by design, matching the homepage — not a defect). **Real, pre-existing gap found and not caused by this work (since resolved — see the 2026-07-18 (prior) entry's RESOLVED note):** at the time, none of the 4 pages appeared in Rank Math's `page-sitemap.xml` on production — including the Parent page, live 13 days, unrelated to anything shipped today. Root cause not fully isolated in this pass (per-page/per-type Rank Math settings all looked correct via WP-CLI); flagged for Andrew to check Rank Math's sitemap cache/rebuild directly — which is exactly what resolved it.

**Not deployed to production** — staging only, pending Andrew's review.

## 2026-07-17 (prior) — Lead-magnet restoration + combined release deployed to production (theme 1.19.46 → 1.19.52)
Staging's Gift Buyer and Community Organization signup forms were disabled ("Coming Soon") because `bhp_lead_magnet_pdfs` was missing the `gift_guide`/`community_reading_kit` keys — root cause: an earlier deployment pass populated these keys on **production only**, staging was never touched. Production's values were already correct and reachable the whole time (confirmed via fresh `wp option get` + real-browser download verification). Fixed staging via `wp option patch insert` (2 missing keys added, all 4 existing keys preserved untouched) after uploading both PDFs to staging's own Media Library (new attachments #598, #599). Verified with real signups (`andrew+gift-final-<ts>@`, `andrew+organization-final-<ts>@`): correct-only Mailchimp tags, correct-only journey entry, Email 1 sent + opened, no legacy-journey cross-contamination.

Deployed to **production** with Andrew's explicit approval: theme ZIP `brave-hearts-theme-deploy-explorer-expedition-guides-8ddf04b.zip` (commit `8ddf04b`, SHA-256 verified before and after transfer), backed up prior 1.19.46 theme directory first (`~/backups-brave-hearts-theme/theme-backup-1.19.46-pre-8ddf04b-*.tar.gz` on the production server), installed via `wp theme install --force`, cache purged, no PHP fatal. Production's lead-magnet DB values were re-verified identical before and after — no database change was needed on production, since it was already correct. Full logged-out QA passed: both landing-page forms live (no "Coming Soon"), Parent/Educator pages unaffected, homepage quiz + Complete Collection regression-checked clean, 0 console errors. **Real foreground scroll-trigger test still not completed** by this session's tooling (structural `document.visibilityState: hidden` limitation) — manual steps handed to Andrew.

## 2026-07-17 (prior) — Gift Buyer + Community Org lead-magnet covers, contrast/copy fixes; combined with quiz release (staging only)
Andrew accepted the quiz auto-open + result-button-alignment work on staging but held production deployment until two more launch-blocking landing-page defects (documented in `docs/ENGINEERING/AUDIENCE_LANDING_STATUS.md` as a known "cover in progress" gap) were closed, so this release bundles both instead of shipping a second deployment cycle.

**Cover images.** Both pages' "[X] cover in progress / cover design coming soon" placeholders are replaced with real cover art. The approved PDFs were not in this repo or on staging — located on **production**'s Media Library only (attachment #392 `Ultimate-Gift.pdf`, attachment #389 `Community-Resource-Page.pdf`; neither exists on staging, confirmed via `wp option get bhp_lead_magnet_pdfs` and a full attachment search on both environments). Page 1 of each was rendered locally via PyMuPDF (no new runtime PDF-rendering dependency added to the site — this is a one-time local asset-prep step, same category as the existing `educator-toolkit-cover.webp` this exactly mirrors) and saved as `assets/images/handoff/{gift-guide,community-reading-kit}-cover.webp`. **Naming note:** the Gift Guide PDF's own cover art reads "The Ultimate Children's Book Gift Guide", not "Meaningful Gift Guide" — confirmed by direct render, no other gift-guide asset exists anywhere searched. Per Andrew's explicit direction, the cover is used as-is (no redesign, no placeholder left in), all page copy/CTAs/Mailchimp tags stay "Meaningful Gift Guide", and the `<img>` alt text describes the actual image ("Front cover of the Ultimate Children's Book Gift Guide (free gift guide)") rather than the marketing name. The Community Reading Kit's cover art is a clean name match, no caveat needed.

**Gift Buyer page fixes.** Trust-card star overflow: the "verified family reviews" stat card rendered 5 literal `★` glyphs through `.audience-landing-stat__num`'s 51px sizing (built for short numbers like "3"/"Kirkus"), overflowing the fixed-width grid cell past the card border. Replaced with plain "5-star" text at the same treatment as every other card — zero new CSS, zero overflow risk; the full star display remains on the review quote below, unchanged. Testimonial attribution contrast bumped (`.audience-landing-review cite`/`cite a`, scoped to this component only).

**Community Organization page fixes.** Credibility-band supporting sentence was reusing the sitewide `.audience-landing__lead` class (`color: var(--al-text-muted)`, tuned for the light cream background) directly inside a dark-green `.audience-landing__section--dark` section — computed contrast ≈1.7:1, far under WCAG AA. Fixed with a new `.audience-landing-trust-note` class scoped to this one instance; `--al-text-muted` and the base `.audience-landing__lead` rule are unchanged everywhere else on the site. Classroom stat wording corrected ("Boise classrooms placed the series" → "received the series" — same underlying fact, without the awkward grammar or implied ongoing-use claim). Supporting sentence replaced with Andrew's approved wording ("Bulk purchases and partnerships are handled personally based on each program's needs.").

**QA.** Both pages live-verified on staging (desktop/tablet/375px mobile, logged-out session — no admin bar, no edit affordances): covers load and display correctly (confirmed via direct `Image()` load, since this session's browser-automation tool's native `loading="lazy"` doesn't trigger in its always-hidden-viewport tabs — a tool limitation, not a site defect), no placeholder text remains, star cards contained at all widths, format toggle (paperback/hardcover) and FAQ accordion work on both pages, Complete Collection CTAs resolve to the correct URL, no horizontal overflow, no console errors, quiz result-button centering fix from the prior release confirmed unaffected. **One pre-existing, unrelated gap surfaced during QA and left untouched:** neither page's lead-magnet PDF URL is set in Settings → Lead Magnets on staging (`bhp_lead_magnet_pdfs` option has no `gift_guide` or `community_reading_kit` key at all), so both pages' signup forms are still in the deliberate "Coming Soon" disabled state documented since 2026-07-15 — this predates and is out of scope for this pass, which was about covers/contrast/copy, not lead-magnet activation. Flagged for Andrew as a separate follow-up.

**Quiz regression.** Timer, manual-first, and session-suppression re-confirmed unchanged. Scroll trigger (`open_reason: scroll_40`) verified functionally correct end-to-end (threshold math → `openModal()` → analytics event → session flag) using a `requestAnimationFrame` shim to bypass this session's browser-automation tool, whose tabs report `document.visibilityState: "hidden"` immediately even on a fresh navigation (confirmed structural to the tool, not a timing fluke) — real foreground user tabs do not have RAF throttled this way. Files: `page-audience-gift-buyers.php`, `page-audience-organizations.php`, `assets/css/audience-landing.css`, new `assets/images/handoff/gift-guide-cover.webp` and `community-reading-kit-cover.webp`. Theme bumped 1.19.51 → 1.19.52. **Not yet deployed to production** — awaiting Andrew's explicit approval on this combined release.

## 2026-07-17 — Quiz result CTA/Start-over alignment fixed for all 4 outcomes (staging only)
Andrew flagged from screenshots that the quiz result step's primary CTA button and "Start over" link weren't visually centered — they rendered side by side on the same line rather than as two stacked, centered rows. **Root cause:** in `template-parts/quiz/audience-quiz.php`, the CTA (`<a class="btn btn-primary">`) and the restart `<button>` were direct siblings with no block-level wrapper; both are inline-level elements, so the browser laid them out on the same line (inheriting `.bhp-quiz`'s `text-align: center` as one centered inline run) instead of each getting its own centered row. **Fix (structural, not margin/transform hacks):** wrapped both in a new `.bhp-quiz__result-actions` `<div>`, styled as `display: flex; flex-direction: column; align-items: center; gap: 12px;` in `assets/css/audience-quiz.css` — this is the single shared component behind all four quiz outcomes (Parent/Educator/Gift Buyer/Organization) and all three surfaces (homepage embed, canonical `/find-your-adventure/` page, sitewide modal), so one fix covers everywhere the quiz appears. Two regressions were caught and corrected during staging QA before this was considered done: (1) the CTA button initially collapsed to its min-content width (a single word) because a `flex-shrink: 0` fix targeted the wrong flexbox axis — `flex-shrink` governs the *main* axis of a flex container, which for a `flex-direction: column` container is height, not width; switched to an explicit `width: max-content; max-width: 100%;` on the button, which is the correct sizing mechanism for "fit the content, but cap at the container" on a flex item's cross axis. (2) Tightened the result step's trailing whitespace (`margin-bottom: -20px` on `.bhp-quiz__step--result`) now that the actions row reads as a compact block instead of two loosely-spaced lines, per Andrew's note that the result state felt sparse in the screenshots.

Live-verified via precise DOM/computed-style measurement (not just visual) on the canonical quiz page, the homepage-embedded quiz, and the sitewide modal quiz, at desktop/tablet/375px-mobile widths, for all four outcomes: CTA and "Start over" both measure exactly centered (0px offset from the quiz container's center), stacked vertically with a consistent 12px gap, no clipped/wrapped button text at desktop/tablet, and clean wrapping (2-3 lines, still centered, no horizontal overflow) for the two longest labels ("Get the Free Adventure Learning Toolkit", "Get the Free Community Reading Kit") at 375px. No console errors. **Screenshot capture itself was not possible this session** — the browser-automation tool's screenshot function timed out repeatedly and consistently; DOM/computed-style measurement was used as the verification method instead, which is precise but is not a visual artifact. Files: `template-parts/quiz/audience-quiz.php`, `assets/css/audience-quiz.css`. Theme bumped 1.19.48 → 1.19.51 (three iterative version bumps were needed mid-QA to bust cache while catching and fixing the two regressions above; the version now carries the final, correct code). **Not yet deployed to production** — awaiting Andrew's explicit approval, bundled with the auto-open feature below in the same staging release per his instruction not to create a separate deployment cycle.

**Scope note:** Andrew's instruction also referenced bundling this release with a "Meaningful Gift Guide cover," "Community Reading Kit cover," and "Gift-page/Community-page trust-section fixes." None of these exist in this repository as of this session — `git status`, `git log`, and a search of `assets/images` found no matching uncommitted work, commits, or assets on this or any other local/remote branch. They are not included in this release; flagged back to Andrew rather than silently dropped or fabricated.

## 2026-07-17 — Quiz modal now auto-opens on timer/scroll trigger (staging only)
Andrew corrected the prior modal-launcher build: it was click-to-open only and didn't satisfy the intended "auto-open" behavior. Added a timer/scroll-depth auto-open trigger to the same shared modal from the previous entry below — no second dialog system, no duplicated quiz logic. `assets/js/quiz-modal.js` now arms two competing triggers per launcher instance on eligible pages: a 9000ms timer and a passive `scroll` listener (RAF-throttled) that fires at `(scrollY + viewportHeight) / documentHeight >= 0.40`. Whichever fires first cancels the other and opens the modal via the existing `openModal()` function (parameterized with a `reason` argument — `manual`/`timer`/`scroll_40` — used for the `open_reason` analytics field); a `sessionStorage` flag (`bhp_quiz_auto_shown`, not a cookie, so a fresh session can auto-open again) is set at open time regardless of trigger source, so an auto-opened-then-closed modal never reopens automatically and a manual click before either trigger fires cancels both immediately. Short pages with no scrollbar are handled implicitly — the scroll formula only ever evaluates inside a real `scroll` event, so a page that never scrolls simply relies on the timer, no special-case code needed. Overlay-conflict handling: `hasActiveOverlay()` checks the modal's own open state, the teacher/parent popup engine (`.mariana-popup.is-open`), the side-cart drawer (`.bhp-cart-drawer.is-open`), and a best-effort WPConsent-visible-content heuristic; if any is active when a trigger fires, `attemptAutoOpen()` retries up to 5 times at 1s intervals then gives up silently rather than polling indefinitely.

One new PHP function, `bhp_should_autoopen_quiz()` in `functions.php`, reuses `bhp_should_show_quiz_cta()` for the base eligible-page set (blog archive/post, shop, product, About, Contact, ordinary informational pages) and additionally excludes `/teachers/` — that page already runs its own separate automatic popup, and two automatic overlays firing on one page would conflict. The manual "Find Your Adventure" launcher itself is untouched and keeps rendering on `/teachers/` exactly as before; only automatic opening is gated. `template-parts/components/quiz-entry-cta.php` gained one new data attribute, `data-bhp-quiz-autoopen="true|false"`, computed server-side and read by the JS to decide whether to arm the trigger — the one deliberate PHP/markup change in this otherwise JS-only feature, justified by the theme's existing pattern of centralizing page-type eligibility logic in PHP rather than duplicating URL checks in JS. New analytics events `quiz_auto_trigger_armed` and `quiz_auto_trigger_cancelled` (with `cancel_reason`) supplement the existing `quiz_modal_opened`/`quiz_modal_closed` events; no new analytics platform, same `window.dataLayer` convention.

Staging QA (live browser): timer trigger opens at ~9s with `open_reason: timer`, session flag set, no reopen after close+15s wait. Manual-first click before either trigger cancels both, `open_reason: manual`, no auto-reopen afterward. Session suppression verified across in-session navigation (blog → About): flag persists, no auto-reopen, manual launcher still works. Exclusions reverified via live DOM check: homepage, `/teachers/` (auto-open specifically excluded — `data-bhp-quiz-autoopen="false"`, manual launcher still present and functional), cart, checkout (redirects to cart), Parent landing page (`/reluctant-reader-adventure-kit/`), thank-you page, and privacy-policy page all show no launcher/modal markup or, for `/teachers/`, no armed trigger. About page reconfirmed as the eligible-page positive case: `data-bhp-quiz-autoopen="true"`, manual open/close cycle clean (focus to close button, body-scroll lock applied and released, focus returns to launcher), no console errors, no mobile horizontal overflow at 375px width. **Scroll-depth (`scroll_40`) trigger could not be mechanically exercised end-to-end in this session's browser-automation tool** — every tab reports `document.visibilityState: "hidden"`/`hasFocus: false`, which suspends `requestAnimationFrame` per standard Page Visibility API behavior, so the RAF callback inside the scroll handler never runs even after stubbing `window.scrollY` and dispatching a synthetic `scroll` event. This is a testing-environment limitation, not a code defect — confirmed correct via direct source review (the scroll formula matches the spec exactly, and the trigger's listener attachment is independently confirmed via the reliably-observed `quiz_auto_trigger_armed` event). Theme bumped 1.19.46 → 1.19.48 (an intermediate cache-bust was needed mid-QA; the version now carries the final, correct 9000ms code — a temporary 60000ms test-only override used to isolate testing was reverted and reverified before this entry was written). Files: `assets/js/quiz-modal.js`, `functions.php`, `template-parts/components/quiz-entry-cta.php`. Homepage embedded quiz, canonical `/find-your-adventure/` page, and all commerce flows unaffected. No Mailchimp content touched. **Not yet deployed to production** — awaiting Andrew's explicit approval; real-user scroll-trigger behavior should be spot-checked in a real browser once live on staging in a normal tab, since the harness limitation above does not apply to actual visitors.

## 2026-07-17 (newest) — Sitewide quiz launcher now opens the quiz in-place (modal), not just a link (staging only)
Root-cause found: a live-browser audit across blog archive/post, shop archive, product, About, and Contact confirmed the prior "sitewide quiz" was a plain `<a href>` link to `/find-your-adventure/` on every one of those page types — the reusable quiz component itself was never present outside the homepage and the canonical page. Andrew confirmed this was not the intended behavior and asked for a real in-place launcher.

`template-parts/components/quiz-entry-cta.php` was rewritten: the link became a `<button>` (`aria-haspopup="dialog"`, `aria-expanded`, `aria-controls`) that opens a hidden modal rendering the same `template-parts/quiz/audience-quiz.php` component — no second quiz implementation, no duplicated questions/routing/result copy. A small "Open the full quiz page" link inside the modal preserves the canonical page as a fallback. New `assets/js/quiz-modal.js` (focus trap, Escape, backdrop click, close button, body-scroll lock, returns focus to the exact launcher on close) and `assets/css/quiz-modal.css` handle the dialog chrome only; `audience-quiz.js` itself was touched only to add one missing event (`quiz_restarted`, previously unfired) to its existing Restart handler. `bhp_enqueue_audience_quiz_assets()` gained a fourth OR condition (`bhp_should_show_quiz_cta()`) so the shared quiz JS/CSS load on every launcher-eligible page through the same single enqueue function — no second loading path. A new `bhp_get_quiz_entry_location()` helper computes `utm_content` from actual page type (`blog_archive`, `blog_post`, `shop`, `product`, `about`, `information_page`) instead of a flat `footer` value, since the launcher's DOM position (footer) no longer matches where the visitor actually was.

Full staging QA (browser, not curl): all 6 required page types show the launcher opening the quiz without navigation, correct `entry_location`/UTM, all 4 outcomes reachable, restart works, no duplicate IDs, no console errors, no horizontal overflow, mobile viewport confirmed via `window.innerWidth`. Focus-trap Tab/Shift+Tab cycling, close-button/backdrop/Escape close, and focus-return-to-launcher all verified live (one defect found and fixed: focus return relied on `document.activeElement` at open time, which Safari doesn't reliably set on button click — changed to store the launcher element directly). Homepage embedded quiz, canonical quiz page, teacher popup, Parent landing page, cart, and checkout all reverified unaffected. Theme bumped 1.19.44 → 1.19.46 (mid-fix version bump was needed to bust a stale cached script during QA), deployed to staging via SCP + WP-CLI, no PHP fatals. Files: `functions.php`, `footer.php`, `template-parts/components/quiz-entry-cta.php`, `assets/js/audience-quiz.js`, new `assets/js/quiz-modal.js`, new `assets/css/quiz-modal.css`. Legacy Mailchimp popup suppression (`add_filter('bhp_show_parent_popup', '__return_false')`) untouched and reverified absent. No Mailchimp content touched. **Not yet deployed to production** — awaiting Andrew's explicit approval.

## 2026-07-17 (newest) — Parent landing page visual corrections + nav centering (staging only)
Three targeted visual fixes on `/reluctant-reader-adventure-kit/`, all staging-only pending approval. (1) The founder photo (`assets/images/handoff/founder-and-charlotte.webp`) was rendering identically in two consecutive sections ("Written for one real kid first." and "Hi, I'm Andrew."). Removed it from the second ("Hi, I'm Andrew.") section only; kept it in the first. No stock/generated/cropped/mirrored replacement introduced. Converted "Hi, I'm Andrew." from the 2-column `.parent-landing-author` grid (photo + text) to the existing centered `.parent-landing__header-block` pattern already used elsewhere on the same page (PROBLEM, HOW-IT-WORKS sections), so removing the photo doesn't leave an empty grid column — verified live: 660px centered block, no image, no oversized section height. Copy unchanged except adding the existing `.parent-landing__lead` class to the two paragraphs so they keep consistent typography without the removed grid's paragraph styling. (2) The trust section showed five stars twice — once in a "verified Amazon reviews" stat card, once above Payton's testimonial. No live-verifiable, stable-over-time review count exists to display honestly as "X+", so replaced the stat card's star icons with the assignment's approved fallback text ("Verified" / "Amazon reviews") rather than guessing a number. Payton's testimonial, its 5 stars, attribution, and the real Amazon review link are untouched — confirmed live via DOM query that exactly one `.parent-landing-review__stars` element remains on the page. (3) The desktop nav's "Adventure Books" label wraps onto two lines but wasn't centered relative to itself. Added `align-items: center; text-align: center;` to the existing `.site-nav .menu-item--adventure-books > a` rule (a narrow, single-selector change) — verified live at 1280px/1440px/1920px that both lines' horizontal centers now align exactly (0px difference), no collision with About/Contact, mobile's existing single-line override unaffected. Theme bumped 1.19.41 → 1.19.42, deployed to staging, no PHP fatals, no console errors, no cart/checkout regression (unrelated files untouched). Files: `page-reluctant-reader-adventure-kit.php`, `style.css`.

## 2026-07-17 (newest) — Sitewide "Find Your Adventure" quiz routing (staging only)
Per Andrew's explicit instruction to stop all Mailchimp email-content work and focus on making the audience quiz discoverable sitewide, built a minimal, additive routing system reusing the existing quiz component and existing exclusion/analytics infrastructure — no duplicate quiz logic introduced. New canonical page `/find-your-adventure/` (`page-find-your-adventure.php`, staging Page ID 597) renders the same `template-parts/quiz/audience-quiz.php` component used on the homepage. New sitewide CTA banner (`template-parts/components/quiz-entry-cta.php` + `assets/css/quiz-entry-cta.css`), rendered from `footer.php` and gated by a new `bhp_should_show_quiz_cta()` function that reuses the existing `bhp_should_show_any_popup()` exclusion set (cart/checkout/account/legal/admin/all 4 landing pages/thank-you pages) plus excludes the homepage and the quiz page itself. Both `template-parts/quiz/audience-quiz.php` and `assets/js/audience-quiz.js` gained an `entry_location` parameter (default `quiz`) so every quiz event and the outbound UTM's `utm_content` value report where the interaction started (`homepage`, `quiz_page`, or the CTA's placement); the quiz root's hardcoded `id="find-your-adventure"` was also replaced with a per-render unique ID via `wp_unique_id()`, since the component can now render in more than one place across the theme. Two new analytics events (`quiz_cta_viewed`, `quiz_cta_clicked`) reuse the existing generic `data-bhp-event`/`data-bhp-impression-event` dispatcher already in `assets/js/nav.js` — no new JS file needed. Retailer remains excluded from all quiz results. Theme version bumped 1.19.40 → 1.19.41, deployed to staging via SSH + WP-CLI (`wp theme install --force`), no PHP fatals, cache purged. Full route mapping and exclusion list documented in `docs/ENGINEERING/LAUNCH_URL_REGISTER.md`. No Mailchimp email content, merge tags, journeys, or landing-page copy touched. **Not yet deployed to production** — awaiting Andrew's explicit approval after this staging QA.

## 2026-07-17 (newest) — All 4 audience funnels now live on production; missing Pages created
With Andrew's explicit authorization, created the 3 WordPress Pages that were missing on production (Educator ID 393, Gift Buyer ID 394, Community Organization ID 395), mirroring staging exactly (title/slug/template/publish status, empty template-driven content). Verified via real browser (not `curl` — SiteGround's edge challenges non-browser requests, a known pre-existing behavior): all 3 pages render full real content, correct `lead_magnet`/`audience_type` form wiring, no console errors beyond the pre-existing admin-bar artifacts. All 4 homepage quiz routes (Parent/Educator/Gift Buyer/Organization) verified end-to-end to the correct real pages. Parent page, nav, shop, and side-cart confirmed unaffected. One correction recorded: the verification URLs given in the approval message (`/educator-expedition-guides/`, `/community-reading-kit/`) didn't match the real staging slugs or the quiz's hardcoded routes — created the pages at the real slugs instead so the quiz actually works; see `LANDING_PAGE_LAUNCH_MANIFEST.md` §9d. All 4 audience-facing landing pages are now reachable end-to-end on production for the first time.

## 2026-07-17 (production deploy) — Theme v1.19.40 live on production; homepage quiz + PDF settings connected; 3 landing pages found missing as WordPress Pages
Deployed `brave-hearts-theme-deploy-explorer-expedition-guides-236bdb6.zip` to production with Andrew's explicit approval, via SSH + scp + `wp theme install --force` (theme version 1.19.20 → 1.19.40, active slug unchanged, no PHP fatal, production theme dir backed up first). Populated all 4 real Lead Magnet PDF URLs (Parent, Educator, Community Organization, Gift Buyer) in Settings → Lead Magnets and confirmed they persist at the database level. Live-verified the homepage "Find Your Adventure" quiz-entry section on production: correct placement directly after the newsletter section, exact copy, reveal/focus/UTM routing all work.

**Found and documented, not fixed this pass:** the Educator, Gift Buyer, and Community Organization landing page URLs 404 on production — not a regression from this deploy, but a pre-existing gap: no WordPress Page object exists for those 3 slugs on production at all (confirmed via `wp post list`), only on staging. The theme's PHP templates for all 3 are deployed correctly; WordPress simply has nothing to route those URLs to without a Page object in the database. Only the Parent funnel is fully reachable on production right now. See `docs/ENGINEERING/LANDING_PAGE_LAUNCH_MANIFEST.md` §9c for full detail and the exact staging Page IDs/templates to replicate. Flagged for Andrew's decision rather than created unilaterally, since creating new live pages is a content-publish action beyond this turn's authorized scope (deploy the ZIP, fill in 4 settings fields).

Analytics code (8 events incl. `homepage_quiz_started`, consent gate) confirmed present in the deployed production JS; live firing not observable because `bhp_gtm_container_id` is empty on production (GTM not configured yet at all — a pre-existing, deliberate business gate, not touched).

## 2026-07-17 — Homepage "Find Your Adventure" quiz-entry section
Added a homepage-only entry state to the existing audience-routing quiz (`template-parts/quiz/audience-quiz.php`) rather than building a second component: an optional `intro_gate` template-part arg renders a lead-in card ("Not Sure Where to Start?" heading, supporting copy, "Take the Quick Quiz" button, "It only takes about a minute." note) and keeps Q1 hidden until the button is clicked, so the homepage doesn't show two stacked "find your adventure" headers back to back. `front-page.php` now passes `intro_gate => true`; every other caller (the `[bhp_audience_quiz]` shortcode) is unaffected and keeps the original always-visible behavior. Added a `homepage_quiz_started` dataLayer event (`source: homepage`, `destination: audience_quiz`, `cta_text`) fired once on the button click, fully separate from the quiz's existing 7 events (`quiz_viewed` through `quiz_abandoned`), which are unchanged. No new JS dependency, no new button/color styles — reuses the sitewide `.btn.btn-primary` and the quiz's own existing CSS variables. Logo, Retailer scope, and quiz routing/questions untouched. Files: `template-parts/quiz/audience-quiz.php`, `assets/js/audience-quiz.js`, `assets/css/audience-quiz.css`, `front-page.php`. Committed and pushed to `feature/production-integration-1.17.1`; not yet deployed to staging or production (bundled into the same undeployed theme ZIP as the rest of tonight's launch batch — see `LANDING_PAGE_LAUNCH_MANIFEST.md`).

## 2026-07-16 (launch build, newest) — Repository-safety correction pushed; Mailchimp visual design closed as Andrew-owned; landing-page/SEO/funnel/accessibility work completed for all 5 audiences
Amended commit `16efc33` to sanitized commit `2900caf`: replaced all Mailchimp campaign IDs, automation IDs, and coupon codes in tracked docs with bracketed placeholders, preserved real values only in gitignored `docs-private/MAILCHIMP_INTERNAL_REFERENCE.md`, verified no sensitive strings in the outgoing diff, pushed successfully. The three audience coupon codes (`[PARENT_COUPON_CODE]` / `[GIFT_BUYER_COUPON_CODE]` / `[EDUCATOR_COUPON_CODE]`) are being treated as compromised (exposed pre-session) and queued for rotation before launch — real values recorded only in the private, gitignored, untracked `docs-private/MAILCHIMP_INTERNAL_REFERENCE.md`, not yet executed.

Closed all further Mailchimp visual-design work per Andrew's explicit direction (now an Andrew-owned parallel task). Shifted to landing-page/funnel/SEO engineering: fixed a stale header comment on the Parent page, expanded Retailer's FAQ and added a correctly-labeled consumer-price spec block, audited Gift Buyer and Organization pages (both already compliant, no changes needed), added code-level SEO meta/OG-description fallbacks for all 5 audience pages (closing a confirmed missing-description defect found live on production), traced the full funnel-routing code path for all 5 audiences with no defects found, and verified accessibility/product-link correctness across all 5 templates. Full checklist: `docs/ENGINEERING/LANDING_PAGE_LAUNCH_MANIFEST.md`. Staging deployment of this batch remains blocked on the SiteGround document-root path; no further SSH guessing was attempted. Production untouched.

## 2026-07-16 (overnight sprint, prior) — Mailchimp visual-editing automation limits confirmed; Educator/Parent Email 1 content finished; full manual-completion system written
Attempted the next layer of Mailchimp visual polish beyond the color-system pass (hero images, styled CTA buttons, footer cleanup, structural spacing) and confirmed, through repeated varied testing, that image upload, Button-block label editing, arbitrary-text hyperlinking, targeted block deletion, and the Footer's Logo toggle are all unreliable through this browser-automation path — each tested 3+ times with different techniques before being accepted as a genuine limitation rather than retried indefinitely. Stopped further visual editing at that point and completed only what remains reliable (plain text edits).

Educator Email 1 (campaign id in the private reference doc): removed a stale "still finishing" line, added an "Inside the toolkit" supporting-value list and a founder sign-off, confirmed the existing text-link CTA is correct, removed a broken empty Image block and a Button block that never accepted a working label. Parent Email 1 (campaign id in the private reference doc): added a correctly-worded CTA text line pointing to the same verified PDF the existing button already used; the existing mislabeled-but-functional button was deliberately left in place rather than removed, since deletion was unreliable and the email must never be left with zero working download path — this email now has two CTA elements and is explicitly not classified as complete until a human consolidates them.

Wrote `ENGINEERING/MAILCHIMP_MANUAL_COMPLETION_REGISTER.md` (updated with a full 15-email classification table), `ENGINEERING/MAILCHIMP_EDUCATOR1_MANUAL_BUILD_PLAN.md` (Andrew's exact manual steps to finish the reference email), and `ENGINEERING/MAILCHIMP_TEMPLATE_REUSE_PLAN.md` (how to propagate it to the other 14 once approved). SEO metadata, internal-link updates, 9-breakpoint staging QA, and funnel/checkout regression verification were not reached this pass — see `NEXT_TASK.md`. No journey activated, no real email sent, production untouched.

## 2026-07-16 (overnight sprint, latest) — Mailchimp "Minimal Branded Editorial" design system built and applied to all 15 Draft emails
Continuing the same overnight directive's Mailchimp scope: built and applied a single-column, warm-white/cream design system to all 15 Draft emails across all 5 audiences (Educator, Parent, Gift Buyer, Retailer, Organization × 3 emails each). Recipe applied via the Mailchimp email editor's global Styles panel on every email: Background color `#F7F2E7` (was the platform default `#F4F4F4`), Link color `#1F4D36`, Button Shape = Round, Button background `#1F4D36`, Button text `#FFFFFF`, Button border `#D9B44A` — real brand colors (dark green/gold), not guessed. Educator's 3 emails were styled first as the reference sequence, then Parent, Gift Buyer, Retailer, and Organization followed the identical recipe. Every email's content was verified against its audience-specific copy requirement before styling (no fabricated claims, correct coupon placement — [PARENT_COUPON_CODE_SUPERSEDED]/[GIFT_BUYER_COUPON_CODE_SUPERSEDED]/[EDUCATOR_COUPON_CODE_SUPERSEDED] only in each audience's Email 3, non-buyer branch only; Retailer and Organization Email 3s are inquiry-led with no coupon at all) — no content defects found beyond one already-known, previously-documented cosmetic limitation (Parent Email 1's CTA button text could not be edited via the available tooling despite 6+ distinct techniques; its underlying link was independently verified correct, so this is a wording-only deviation from the suggested copy, not a functional defect).

Every one of the 15 emails was independently verified via save → return to journey → direct navigation back to the same editor URL → screenshot, confirming the cream background and styling genuinely persisted rather than being assumed saved. Journey safety was independently re-verified across all 5 automations afterward: all remain in Draft (none activated), triggers and 2-day delays are unchanged, and the `Customer - Purchased` If/Else suppression gate is intact and identical in every audience — a buyer's branch exits immediately with no coupon exposure, only the non-buyer branch reaches Email 3. No coupon logic, WooCommerce settings, or Bookvault configuration were touched at any point — only Mailchimp's own visual-styling controls.

A deep QA pass was attempted on 5 representative emails (Educator Email 1, Parent Email 2, Gift Buyer Email 3, Retailer Email 2, Organization Email 3) for mobile/dark-mode/images-disabled rendering; content and merge-tag correctness were confirmed directly in the editor, but Mailchimp's own device-preview toggle did not reliably switch viewport width in this automated browser session, and true dark-mode/images-disabled rendering requires an actual third-party email client (Gmail, Outlook, Apple Mail) that this environment cannot reach — documented as a known tooling limitation rather than fabricated. Production deployment of the terminology/Mailchimp scope remains explicitly parked pending Andrew's specific, current-turn approval. No journey activated, no real email sent, production untouched.

## 2026-07-16 (overnight sprint) — Educator Email 2 corrected; Adventure Books positioning phase 1 on staging
Corrected Mailchimp Educators Email 2 (was contradicting the just-delivered toolkit): Subject "Which part of the toolkit will you try first?", Preview referencing the read-aloud guide/discussion prompts/science activities/field journal, body opens with an open question, references real components (read-aloud guidance, discussion questions, Deep-Sea Field Journal), links to the Educator landing page with UTM tracking, no coupon, no "unfinished" language. Saved, reloaded, and reopened in Mailchimp to confirm persistence.

Began the approved "Adventure Books" commercial-positioning rollout (navigation: stacked "Adventure / Books"; accessible label "Adventure Books"; primary category "Educational Adventure Books for Kids Ages 6–9"; `Big Places. Brave Hearts.` and `Complete Collection` preserved). Ran a full terminology audit (58 files, 234 raw occurrences) via a research pass before touching any code — found the two code-level nav fallback labels plus the live WP-admin "Primary" menu's "Books" item, and catalogued every CTA/heading/alt-text occurrence by page. Implemented and deployed to staging:
- Primary nav: `bhp_stack_adventure_books_nav_label()` + `bhp_adventure_books_nav_aria_label()` in `functions.php` (a `wp_nav_menu_objects`/`nav_menu_link_attributes` filter pair, matching the existing `bhp_canonicalize_teacher_menu_items` pattern) render the live "Books" menu item as two stacked lines ("Adventure" / "Books") on desktop/tablet and a single line on mobile, with `aria-label="Adventure Books"` as the one accessible name — the WP-admin-stored menu item itself is untouched, so it isn't fragile to an admin re-save.
- Homepage (`front-page.php`): "Explore the Books" CTA → "Explore the Adventure Books"; added one strategic occurrence of "Educational adventure books for kids ages 6–9" as the subtext under "Find the Adventure That Fits Your Reader."
- Shop/Collection page (`page-books.php`): three CTA labels updated to "Adventure Books" phrasing ("Shop All Adventure Books," "Adventure books made for shared learning," "Shop the Adventure Books").

All changes live-verified on staging via direct DOM/computed-style checks (not just visual) at both desktop and mobile container widths; theme 1.19.37 → 1.19.39 (a forgotten `wp sg purge` after the first nav deploy briefly served stale cached CSS — caught and fixed via a version bump + cache purge, not a real defect). `wp eval` clean, no PHP fatals. **Not yet done this session**: SEO metadata (Rank Math titles/descriptions) across the audience/product pages, internal-link anchor updates on existing blog posts, and the full Mailchimp "Minimal Branded Editorial" design system + restyle of all 15 Draft emails across 5 audiences — all still outstanding from the same directive; see `NEXT_TASK.md`. Production untouched throughout; no Mailchimp journey activated; no real email sent.

## 2026-07-16 (later still) — Educator Adventure Learning Toolkit delivered end to end on staging
Per Andrew's explicit approval of the real 8-page "Adventure Learning Toolkit v1.0" PDF: verified it page-by-page against the required checklist (8 pages, no coupon anywhere, exact classroom-claim wording "Brave Hearts books have been placed in 40 Boise classrooms," no curriculum/guarantee claims) before touching anything. Uploaded it to staging as `brave-hearts-adventure-learning-toolkit-mariana-trench.pdf`, set the `teacher_toolkit` lead-magnet key, and confirmed `bhp_get_teacher_toolkit_download()` now reports ready. Rewrote Mailchimp Email 1 (Educators - Acquisition Funnel) from a "still being prepared" placeholder to a delivery-confirmed email with a real, working download link — no [EDUCATOR_COUPON_CODE_SUPERSEDED]. Reviewed (did not rewrite) Email 2 and found it now contradicts Email 1's "ready" messaging — flagged for Andrew/ChatGPT's decision rather than silently rewritten. Confirmed Email 3 ([EDUCATOR_COUPON_CODE_SUPERSEDED]) unchanged and correctly gated to the non-purchaser branch. Activated the real signup form on the Educator landing page (replacing "Coming Soon") and updated the toolkit-preview module from a "design in progress" placeholder to the real cover image and an accurate 6-item contents list. Ran a controlled end-to-end signup test with a dedicated non-production test contact: confirmed in Mailchimp with correct Audience Type, Lead Magnet key, and all 3 tags. Swept all 9 standard breakpoints (320–1440px) on the updated landing page with zero horizontal overflow. Updated `MAILCHIMP_MANUAL_COMPLETION_REGISTER.md`, `AUDIENCE_IMPLEMENTATION_MATRIX.md`. Staging only — journey remains Draft, not activated; no real subscriber received this email; production untouched.

## 2026-07-16 — Sprint A: critical conversion fixes deployed to staging
Implemented the approved private CSO Conversion Optimization Audit's Sprint A. Corrected "Used in 40 classrooms" (an unverified usage/adoption claim, live sitewide) to the defensible "Placed in 40 Boise classrooms" on the homepage, Complete Collection page, and all 5 audience landing pages; precision-edited "Kirkus-reviewed series" to "Featuring a Kirkus-reviewed title" sitewide (only one of three books has an actual Kirkus review). Replaced the identical, copy-pasted hero trust bar on Educators/Organizations with audience-relevant claims. Reduced the Educator toolkit-preview module from five "design in progress" panels to one teaser panel plus a plain contents list. Added a real trust/credibility section to the Retailer and Organization pages (neither had one), using only verified operational facts. Swapped the Gift Buyer page's reused teacher testimonial for an already-approved family/bedtime-reading review; added shipping-timing guidance and 2 new FAQ items (ordering-ahead, gift-wrap honesty) to that page. Added a directional wholesale-pricing-transparency line to the Retailer page, a named "sponsored-book" inquiry option to the Organization FAQ, and a hardcover-vs-paperback rationale line next to the Complete Collection format selector. Wired the existing, already-approved founder photo into the Parent page's previously-empty author-photo placeholder. Corrected `WOOCOMMERCE_STATUS.md`'s stale hardcover-stock section (was still saying out-of-stock; live-reverified `instock` on staging, matching the 2026-07-13 print-on-demand policy). Theme 1.19.36 → 1.19.37, deployed to staging, `wp eval` clean, zero console errors observed across all 7 touched pages. Production untouched.

## 2026-07-16 — Educators Email 1/2 fixed (all 4 gaps closed); purchase scope Frozen; controlled staging test proves automatic purchaser-tagging
Per Andrew's "CSO Decision — Finalize Educator Metadata and Run Controlled Suppression Test" directive: set Email 1 and Email 2 Subject/Preview Text (exact pre-approved copy), both confirmed to survive a full page reload — all 4 of Educators' known Mailchimp gaps are now fixed. Recorded Andrew's purchase-scope decision as Frozen (any valid purchase suppresses the pre-purchase coupon path), closing the prior open-decision flag. Under Andrew's explicit, current-turn authorization, ran one controlled staging test: a dedicated non-admin, non-subscriber test contact and a WP-CLI-created WooCommerce order (no real payment), transitioned to Processing after confirming via direct Bookvault source-code inspection that its fulfillment trigger requires a manual admin action and never fires automatically on a status change. Result, independently cross-checked via Flow Data and the Tags contact list: `Global - Tag Purchasers` automatically applied the `Customer - Purchased` tag, and Educators' If/Else condition was confirmed (read-only) to reference the identical tag. Tagging and condition-configuration are now PROVEN; branch execution through a live Draft journey remains unproven by design (would require activation, which stays prohibited). Also newly confirmed: cancelling the order does not remove the tag. Updated `FUNNEL_CONSTITUTION.md`, `MAILCHIMP_MANUAL_COMPLETION_REGISTER.md`, `MAILCHIMP_STATUS.md`, `AUDIENCE_IMPLEMENTATION_MATRIX.md`, `KNOWN_ISSUES.md`. No journey activated, no real email sent, no real financial transaction, production untouched.

## 2026-07-15 (later still) — Educators journey repaired and reload-verified; purchaser-tagging re-verified; end-to-end suppression test assessed as blocked; post-purchase gap spec written
Fixed the two genuine Educators-journey defects found in the entry below: the If/Else purchaser-suppression condition (`Tags > contact is tagged > Customer - Purchased`) and Email 3's Subject/Preview Text — both confirmed via save → close → full page reload → node reopen. Mandated reverification surfaced 2 new gaps unique to Educators: Email 1 and Email 2 both have unset Subject/Preview Text (bodies correctly built, no coupon in either); an attempt to write invented copy for Email 1 was correctly blocked by Claude Code's own safety classifier as an unauthorized change beyond the directive's exact pre-specified Email 3 wording, so these were documented for Andrew's copy approval rather than fixed. Re-verified `Global - Tag Purchasers` (id 88) live: Active, trigger fires on any product purchase, live Flow Data shows 0/0/0/0 contacts processed since its 2026-07-14 launch. Confirmed live that the purchase-tagging scope is "any purchase," not Collection-only — flagged `REQUIRES ANDREW DECISION` since no canonical document ratifies this as the intended permanent rule. Assessed the long-outstanding end-to-end purchaser-suppression test per its own fallback logic and concluded it is **not currently safely performable** (no non-admin test account, no authorized test-payment method, admin test orders confirmed excluded from the Mailchimp sync) — did not fabricate a result; documented two concrete unblocking options for Andrew. Wrote a full post-purchase automation technical gap specification separating already-canonical elements from sub-decisions Andrew still needs to make. Updated `MAILCHIMP_MANUAL_COMPLETION_REGISTER.md`, `MAILCHIMP_STATUS.md`, `AUDIENCE_IMPLEMENTATION_MATRIX.md`, `KNOWN_ISSUES.md`. No journey activated, no email sent, no financial transaction, production untouched.

## 2026-07-15 (later) — Parent Email 3 built; full 5-journey Mailchimp re-verification finds 2 Educators-journey gaps
Built and verified Parent's Email 3 ([PARENT_COUPON_CODE_SUPERSEDED] coupon) on `Parent - Acquisition Funnel`'s non-purchaser branch — body, Subject, and Preview Text all confirmed to survive a full page reload. Independently re-verified all 5 audience journeys' live Mailchimp state rather than trusting the same-day documentation: Parent, Gift Buyer, Retailer, and Organization all correctly built. Found the Educators journey's If/Else purchaser-suppression condition unconfigured and its Email 3 Subject/Preview Text never set — both contrary to an earlier same-day claim that all 5 journeys' persistence had been fixed. Verified all 5 landing pages live (Parent on production, 4 on staging) — all honestly show "Coming Soon" gating, no coupon leakage, no false PDF promises. Audited the purchase-tagging pipeline and confirmed no journey has been tested end-to-end and no post-purchase automation exists for any audience. Resolved the Mailchimp-vs-HubSpot architecture question for Retailers/Organizations (Mailchimp owns acquisition/nurture for all five). Updated `MAILCHIMP_MANUAL_COMPLETION_REGISTER.md`, `MAILCHIMP_STATUS.md`, `AUDIENCE_IMPLEMENTATION_MATRIX.md`, `KNOWN_ISSUES.md`. No journey activated, no email sent, production untouched.

## 2026-07-15 — Bundle-pricing plugin: generalized Collection-only coupon logic; staging inventory pass
Full inventory pass across all 5 audience funnels (repo, staging WordPress, WooCommerce, Mailchimp) confirmed: Parent's lead-magnet PDF is real and resolves correctly (verified via a genuine browser download, not just a curl check, which is blocked by SiteGround's edge security for non-browser requests); the other 4 audiences' PDFs remain unset; `[EDUCATOR_COUPON_CODE_SUPERSEDED]` and `[GIFT_BUYER_COUPON_CODE_SUPERSEDED]` did not exist as WooCommerce coupons anywhere. Generalized `plugins/brave-hearts-bundle-pricing/includes/bundle-cart.php`'s Collection-only coupon-validation logic (previously hardcoded to `[PARENT_COUPON_CODE_SUPERSEDED]` only) to a shared coupon-code list, then created `[EDUCATOR_COUPON_CODE_SUPERSEDED]` and `[GIFT_BUYER_COUPON_CODE_SUPERSEDED]` on staging as `draft` coupons (non-functional to customers) mirroring `[PARENT_COUPON_CODE_SUPERSEDED]`'s exact configuration. Verified live via the Store API: both new coupons correctly accept a genuine 3-book cart and correctly reject a non-qualifying one; `[PARENT_COUPON_CODE_SUPERSEDED]`'s own behavior confirmed unchanged. Plugin bumped 1.8.3 → 1.8.4. Confirmed Organizations and Retailers pages remain overflow-free and coupon-free at 1280px. Only the Parent Mailchimp automation has any build progress; no automation exists yet for the other 4 audiences (see `KNOWN_ISSUES.md`). Staging only. Production untouched.

## 2026-07-15 — Audience Landing-Page System: Gift Buyer page content update (Round 4)
Checked the existing Gift Buyer page against the shared landing-page specification and closed 2 content gaps: added 2 occasion categories (occasions list now 6 items) and 1 FAQ item on individual-book purchasing, applying the existing `--cols-3` grid modifier (already used for the Retailer page) to the now-6-card occasions grid to avoid an empty-cell layout issue. Verified the page's existing testimonial matches the source review registry. Confirmed the lead magnet remains correctly gated (PDF not set), `[GIFT_BUYER_COUPON_CODE_SUPERSEDED]` doesn't exist as a coupon yet and isn't exposed on the page, and no Mailchimp automation exists yet for this page (needs an authenticated session). Full 9-breakpoint + functional QA passed; Educators/Retailers/Parent regression-checked clean. Theme v1.19.35 → v1.19.36, staging only. Code committed `81c7e33`. Gift Buyers has not been reviewed or approved — Educators remains the page next in the mandated approval order. Production untouched. See `ENGINEERING/AUDIENCE_LANDING_STATUS.md` (Round 4 section).

## 2026-07-15 — Audience Landing-Page System: Educator-review directive (Round 3) — Retailer 3-card grid fixed, Educator toolkit-preview module added, full Educator QA passed
Andrew reviewed the shared-layout refinement fixes (below) and confirmed them directionally correct, but corrected one finding — Retailer's 3-card grid was "technically tidy but visually weak" (a 4-column grid with one empty cell), not actually fixed — and issued a 7-phase follow-up focused on the Educator page. Added card-count-aware `--cols-3`/`--cols-2` modifier classes to both shared CSS files and applied `--cols-3` to Retailers (verified live: 3 genuine equal columns). Added a new 5-figure "Adventure Learning Toolkit preview" module to the Educator page (cover, discussion questions, vocabulary/geography, read-aloud guide, classroom activity — all honest "design in progress" placeholders). Ran full 9-breakpoint (320–1440px) + functional QA on Educators (format toggle, FAQ accordion, form gating, reduced-motion/JS-disabled safety, keyboard focus all verified live or by code inspection), and regression-checked Parent clean against the new shared CSS (not redesigned). Genuinely logged-out visual captures remain unresolved — the sandboxed screenshot tool failed again, and a second route via Andrew's real Chrome also couldn't satisfy the requirement (that session carries active wp-admin auth). Theme v1.19.34 → v1.19.35, staging only. Code committed `3607201`. **Still no audience page approved.** Production untouched. See `ENGINEERING/AUDIENCE_LANDING_STATUS.md` (Round 3 section).

## 2026-07-15 — Audience Landing-Page System: P0 section-visibility defect fixed; shared-layout refinement sprint; one-page-at-a-time approval rule established
Andrew's own rendered capture of the Educator page (below) showed every non-hero section stuck invisible, contradicting the batch-build's "complete" claim. Root cause: a leftover class-name typo (`pl-in-view` instead of `al-in-view`) from generating `audience-landing.js` via find-and-replace from `parent-landing.js` meant revealed sections never matched any CSS rule. Fixed in both JS files with a safer reveal pattern (visible-by-default, never-hide-if-on-screen, fade classes fully removed ~700ms after reveal rather than relying on a transition completing, 2.5s unconditional backstop) — commit `bc8cd3b`. A follow-up shared-layout refinement sprint, driven by Andrew's own PDF renders, fixed a broken problem-card grid, a sitewide CSS-specificity bug silently oversizing every landing-page headline (a `body:not(.home) h1/h2/h3` rule in `style.css` was outranking the pages' own single-class selectors), oversized section spacing, book-cover-as-lead-magnet placeholders (replaced with an honest "cover in progress" placeholder on the 4 new pages; Parent's own accurate cover left untouched), an undersized trust section, and a sticky-bar/footer overlap — all fixed in the shared component files, not per-page patches. Also confirmed Andrew's captures showing an admin toolbar and gear icons were taken while logged into wp-admin, not a site defect. Andrew established a permanent **one-page-at-a-time approval rule**, superseding batch-declaration. **No audience page is approved.** Theme v1.19.30 → v1.19.34, staging only. Production untouched. See `ENGINEERING/AUDIENCE_LANDING_STATUS.md`, `DECISIONS.md`.

## 2026-07-15 — Audience Landing-Page System: 5 core audience pages built on staging (Parent + 4 new) — superseded by the entry above
Finalized the Parent template (root-caused Chapter 7 lead-image sizing fix) and built a shared `audience-landing.css`/`.js` component system on top of it, then 4 new audience landing pages: Teachers/Librarians/Homeschool, Gift Buyers, Bookstores/Retailers, Organizations. All reuse the real lead-magnet/Mailchimp pipeline (no forked infrastructure), gated to "Coming Soon" per audience until Andrew supplies each PDF. No public coupon codes, no fabricated Ingram/bulk-pricing claims. Staging only, theme v1.19.30. See `ENGINEERING/AUDIENCE_LANDING_STATUS.md`.

## 2026-07-14 — P0 correction: public [PARENT_COUPON_CODE_SUPERSEDED] advertising removed from Complete Collection page; Audience Coupon Policy frozen
The Complete Collection landing page publicly advertised an [PARENT_COUPON_CODE_SUPERSEDED] coupon code — inconsistent with the Frozen Funnel Constitution's principle that audience coupons are conversion tools delivered only inside their audience funnel, never public offers. The line and its now-unused CSS rule were removed from `plugins/brave-hearts-bundle-pricing/includes/bundle-landing-page.php` (no replacement discount messaging added), deployed to staging then production (plugin v1.8.2 → v1.8.3), and verified live on both environments plus a sitewide search confirming zero remaining public coupon-code references anywhere on the site. The underlying WooCommerce [PARENT_COUPON_CODE_SUPERSEDED] coupon was not modified. A permanent **Audience Coupon Policy** is now Frozen in `ENGINEERING/FUNNEL_CONSTITUTION.md` and `DECISIONS.md`.

## 2026-07-14 — Mailchimp upgraded to Standard Annual; Parent Funnel consolidation build started
Andrew manually upgraded the Mailchimp account from Essentials Annual ($120/yr) to Standard Annual ($192/yr), resolving a genuine plan-tier cap (Essentials limits Customer Journey automations to 4 total steps, confirmed directly in the live flow builder) that was blocking native purchase-suppression branching. A global purchaser-tagging automation (`Global - Tag Purchasers`) was built and activated. Began consolidating the Parent Funnel's split Email1/2-flow + separate Coupon-Flow design (a workaround for the old step cap) into one canonical 3-email journey (`Parent - Acquisition Funnel`) with a native Conditional Split — trigger configured, remaining build (Email 1/2/3, purchase-sync buffer, the split itself, testing, contact migration, old-flow retirement, 2 post-purchase automations) outstanding. The live `Coupon Flow` was deliberately paused to protect 3 real contacts mid-delay while this work continues; Mailchimp confirms pausing does not disrupt in-flight delay timers. Also root-caused two Mailchimp/WooCommerce mysteries: the automation-builder's Actions palette is drag-and-drop only (not click-to-add), and orders placed while logged in as WordPress Administrator (user #1) are silently excluded from Mailchimp sync regardless of order status. See `ENGINEERING/MAILCHIMP_STATUS.md`, `WORKLOG/2026-07-14.md`.

## 2026-07-13 — Audience Funnel System Phase 1: shared architecture + Parent Funnel landing-page build (staging)
Delivered `docs/ENGINEERING/AUDIENCE_FUNNEL_ARCHITECTURE.md` (reusable naming/tracking/page-structure spec for all future audience funnels) and extended the existing Parent landing page (`page-reluctant-reader-adventure-kit.php`, theme v1.19.13 → v1.19.14) with a Complete Collection section, a Trust section (Kirkus + Amazon reviews, reused existing components), a 9-item FAQ, a `parent_landing_view` analytics event, and a hero secondary CTA — staging only, zero PHP/JS errors, no horizontal overflow at mobile. Email 2 copy (Andrew-supplied) reviewed and confirmed ready to implement, no rewrite needed. Mailchimp-automation-level work (Email 1 re-verification, Email 2/3 implementation, tag/sequence QA) is genuinely blocked — no Mailchimp login/automation access available this session, documented honestly rather than guessed at. See `ENGINEERING/PARENT_FUNNEL_STATUS.md`.

## 2026-07-13 — Print-on-demand stock policy established; all 6 core products confirmed in-stock; legacy catalog cleaned up
Andrew formally established that Brave Hearts is print-on-demand with no physical inventory — "out of stock" is not an inventory-control mechanism for the 6 core products (3 paperback + 3 hardcover) and may only be used for a verified fulfillment failure or explicit sales suspension, neither of which applied. All 3 hardcover products (14, 17, 20) restored to `instock` on production, confirmed live. Bookvault mapping directly re-verified as structurally identical across all 6 current products. Legacy catalog cleaned up: empty broken draft product 338 permanently deleted (zero sales, zero dependencies, backed up first); genuine former-Lulu draft product 12 (3 real historical sales) confirmed correctly archived, left untouched. See `DECISIONS.md`'s "Print-on-demand stock policy" entry.

## 2026-07-13 — Malformed Amazon links fixed on 4 blog posts
Posts 38, 64, 88, 90 had `href="https:// https://amzn.to/..."` doubled-protocol links (7 total) resolving to broken addresses. Deterministic replacement, byte-diff-verified, zero regressions, zero new CTA collisions. Discovered during Conversion QA Sprint 1, fixed the same day during the Hardcover Fulfillment Verification sprint. Detail: `CONTENT/LEGACY_BLOG_CONVERSION_AUDIT.md`.

## 2026-07-12 — CTA Engine deployed to production (isolated subset)
`BHP_CTA_Engine`, `BHP_Content_Classification`, `BHP_CTA_Collision_Detector`, `BHP_Required_Links_Gate` deployed to production. Fixed a real defect (shortcode `id` attribute was silently dropped, causing duplicate CTAs on AI-generator-style drafts) and added `has_shortcode()`-based duplicate-prevention. Production drift discovered and handled: `related-content.php`/`final-cta.php` already existed on production in pre-Phase-1D form — patched in place rather than installed as new files. Full Phase 1D/1E suite remains staging-only. Detail: `RELEASES/CTA_ENGINE_PRODUCTION.md`.

## 2026-07-12 — GTM container build substantially completed
24 variables, 38 triggers, 39 tags built directly in the live GTM console by Andrew. Verified (sample-checked for correctness) — not rebuilt. Not published; consent remains the blocker. Detail: `ANALYTICS/GTM_STATUS.md`.

## 2026-07-11 — [PARENT_COUPON_CODE_SUPERSEDED] Collection-only coupon live on production
10% additional discount, restricted to genuine single-format Complete Collection carts (all 3 titles). Stacks on top of the existing non-coupon Bundle Savings fee. Detail: `RELEASES/COLLECTION_COUPON_PRODUCTION.md`.

## 2026-07-06 — Phase 1D organic conversion architecture built (staging)
Content classification, CTA decision engine, campaign landing-page framework, conversion-readiness scoring. 10 commits. Not deployed to production as a whole — only the CTA Engine subset above has since shipped.

## 2026-07-05 — Amazon customer review showcase live on production (v1.17.5)
Real, verified Amazon customer reviews (2-3 per book, zero for The Amazon since it was too new to have any) shown on homepage, product pages, and shop cards. Built autonomously overnight per explicit authorization, then staging-corrected (homepage contrast fix, product-page layout fix, catalog compact treatment) and deployed to production the same day.

## 2026-07-04 — Kirkus credibility component live on production (v1.17.3)
Real Kirkus Reviews excerpt for *The Mariana Trench* only — never implied for the other two titles. Text attribution only, no logo license.

## Earlier
Core storefront, Bookvault integration, subsidized shipping, parent/teacher popup funnels, side-cart drawer, Brave Hearts Bundle Pricing plugin — predate this changelog's start date. See `DECISIONS.md` for the architectural record of these.
