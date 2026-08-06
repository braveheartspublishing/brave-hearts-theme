# Gallery assets revert, Collection slide 1 rebuild, and gallery analytics — theme 1.19.141

**Status: STAGING ONLY. Production remains 1.19.121 and was verified untouched after this deploy.**
**Date: 2026-08-02. Branch: `feature/product-media-gallery-1.19.140`. Not pushed, no PR, no merge.**

Three changes shipped as one staging release, plus a full real-browser QA pass.
Every claim below is labelled **OBSERVED** (checked on the live staging system in
this session, with how) or **NOT VERIFIED**.

---

## 1 · The Amazon gallery reverted to the authentic photographs

The four `-navy` slugs were replaced with the authentic set:

| Position | Slug now used | Staging attachment | Previous (`-navy`) |
|---|---|---|---|
| 1 | `amazon-look-02-brave-learning` | 650 | 659 |
| 2 | `amazon-look-03-chapter-jaguar` | 651 | 660 |
| 3 | `amazon-look-04-front-cover` | 652 | 661 |
| 4 | `amazon-look-05-back-cover` | 653 | 662 |

The Complete Collection registry referenced two `-navy` variants
(`amazon-look-04-front-cover-navy` as its Amazon cover tile, and
`amazon-look-03-chapter-jaguar-navy` inside its reserved-video comment). Both were
updated so the two registries cannot disagree.

**Why.** No owner decision approving the navy set exists. `docs/DECISIONS.md`
contains zero matches for "navy" and no record under `docs/RELEASES/` mentions it;
the only evidence of approval was a code comment in `inc/book-media.php`, and a code
comment is not a decision record. The navy set also contradicted the continuity
checkpoint in `docs/CURRENT_TASK.md` (The Amazon's stills are "genuine
full-resolution iPhone photographs" carrying "no Higgsfield artefacts") and carried
visible misspellings plus a wrong printed ISBN.

**OBSERVED** — all four authentic slugs resolve on staging (`wp post list`
by `--name__in`, IDs 650–653) and every declared image slug in the whole registry
resolves: **22 declared, 22 resolved, 0 unresolved**. Each of the four files was
downloaded and opened: genuine photographs of the printed paperback on a wood
table, correct spelling throughout, and the back cover's printed ISBN reads
**979-8-9968-1080-2** with barcode **9798996810802** — the correct value.

The navy attachments 659–662 remain uploaded as documented rollback. Restoring them
is re-adding the `-navy` suffix to four slugs — **and requires a recorded owner
decision first, not a comment.**

A resolution claim in the old comment was also corrected: the uploaded derivatives
are **1050×1400**; 4283×5711 describes the camera originals, not what is on the
server. The navy derivatives were 896×1200, so the authentic set is also the
higher-resolution one on staging.

## 2 · Complete Collection slide 1 rebuilt (v2)

v1 was rejected by the owner: composition and background treatment were right, but
its Mariana panel came from a cover variant carrying **no**
"Big Places. Brave Hearts. / Andrew Signore" byline, while Everest and Amazon both
showed theirs.

New asset: `collection-look-01-three-books-v2`, staging attachment **664**,
1800×1140. v1 (attachment **663**) is **not** deleted — rollback is one slug edit.

**Method, stated precisely because it matters.** The approved v1 image is the
canvas. Only the Mariana panel's pixels were replaced, with a plain geometric warp
(resize + rotate, bilinear, 4×4 supersampled edge coverage) of the genuine cover
file `book1_mariana_trench_ebook_cover.jpg` (1303×1999, byline present), placed in
the exact quadrilateral the old panel occupied. That quad was fitted by least
squares to the old panel's own silhouette — left, top and bottom edge residuals
**0.27 / 0.36 / 0.28 px**. Everything else, including the background gradient, both
drop shadows, and the Everest and Amazon panels, is carried through unchanged.

**No generative fill, no inpainting, no text synthesis, no recolouring, no
retouching, no cover regenerated.**

**OBSERVED** — off-panel pixels are identical pre-encode, and after JPEG
re-encoding differ by noise only (mean absolute difference **1.49/255**, p99 12,
across the whole region right of x=700). Rendered on staging at 1440×900 and
390×844, all three books show their byline.

## 3 · Gallery analytics

The gallery previously had **zero** instrumentation. Five events were added, on the
theme's existing convention rather than a parallel system.

| Event | Fires on | Extra keys |
|---|---|---|
| `look_inside_advance` | prev/next arrow, touch swipe | `direction`, `interaction` |
| `look_inside_thumb_select` | thumbnail commit (click or keyboard) | `interaction` |
| `look_inside_lightbox_open` | enlarge | `interaction` |
| `look_inside_lightbox_close` | any close route | `method` = `button`/`backdrop`/`escape` |
| `look_inside_video_play` | first play of a video, per page view | `item_label` |

Every payload also carries `bhp_book`, `bhp_format`, `bhp_source` (the same three
generic fields `bhpBuildEventPayload()` in `assets/js/nav.js` emits, read from the
same `data-bhp-*` attributes), plus `gallery_count`, `item_index` (1-based),
`item_type` and `item_group`. `bhp_source` is `look_inside_hero` in hero mode and
`look_inside_section` otherwise.

**Consent is untouched.** The gallery only appends to `window.dataLayer`, with the
same no-op guard nav.js uses. Whether GTM loads at all remains a server-side
decision in `BHP_Consent` / `BHP_Analytics_Config` / `BHP_GTM_Loader`. No `gtag()`,
no analytics library, no second queue, no new global.

`data-bhp-event` is deliberately **not** used on gallery controls: half of what is
worth measuring is not a click on the element that changed (swipe, Escape, video
start), so marking the clickable half declaratively would give the component two
emitters and make the lightbox close fire twice.

### GTM/GA4 follow-up required before these events are usable

These events have **no GTM trigger or tag yet**. `bhp_book`/`bhp_format`/
`bhp_source` already have Data Layer Variables; `gallery_count`, `item_index`,
`item_type`, `item_group`, `direction`, `interaction`, `method` and `item_label`
do not. GTM remains deliberately unpublished (see `ANALYTICS/GTM_STATUS.md`), so
nothing reaches GA4 today either way.

---

## QA — real browser (headless Chrome over CDP, `puppeteer-core`)

Desktop **1440×900** and mobile **390×844**, on all four galleries. **Every mobile
result was gated on a live `window.innerWidth` check before being trusted**; all
reported 390 exactly.

| Check | Result |
|---|---|
| All four galleries render | **OBSERVED PASS** — Mariana 7 items, Everest 8, Amazon 5, Collection 9 (4 groups) |
| Nothing fires on page load | **OBSERVED PASS** — 0 events after a 2.5s settle, 0 `look_inside_*` in `dataLayer`, on all four pages, both viewports |
| Prev / next | **OBSERVED PASS** — exactly 1 event each, correct `direction`, counter advances |
| Touch swipe (real CDP touch) | **OBSERVED PASS** — 1 `look_inside_advance` with `interaction: swipe`; correctly suppressed when the gesture starts on a video, which is the existing design |
| Hover preview | **OBSERVED PASS** — previews the slide, emits **0** events, does not commit |
| Thumbnail click commits | **OBSERVED PASS** — 1 event, `aria-current` moves |
| Re-click the selected thumb | **OBSERVED PASS** — 0 events |
| Keyboard roving (Arrow keys) | **OBSERVED PASS** — 1 event with `interaction: keyboard`, focus stays on the rail |
| Lightbox open | **OBSERVED PASS** — 1 event, panel unhidden, `src` set, focus moves to the close button |
| Lightbox close × 3 routes | **OBSERVED PASS** — button → `method: button`, backdrop → `backdrop`, Escape → `escape`; exactly 1 event each |
| Escape on an already-closed lightbox | **OBSERVED PASS** — 0 events |
| Focus trap in the lightbox | **OBSERVED PASS** — two Tabs leave focus on the close button; focus returns to the opener on close |
| Video lazy mounting | **OBSERVED PASS** — at load every video has no `poster` and no `src` on any `<source>`. On the Collection, selecting video 1 mounts **only** that video; the second stays unmounted |
| First play only | **OBSERVED PASS** — 1 `look_inside_video_play`; pause + resume emits 0 |
| Double-emitter check | **OBSERVED PASS** — no ancestor of a gallery control carries `data-bhp-event`, so nav.js's delegated handler never fires for the gallery |
| Mobile layout / overflow | **OBSERVED PASS** — horizontal overflow **0px** on all four pages at 390 and at 1440; arrows visible; rail scrolls horizontally by design |
| Browser console | **OBSERVED PASS** for first-party code — zero first-party errors on all pages. See "console noise" below |
| Related products | **OBSERVED PASS** — 2 correct cross-title cards on each product page. The Collection page is a page, not a product, and correctly has none |
| Format selection | **OBSERVED PASS** — Paperback → Hardcover changes the panel price $11.99 → $17.99 and the CTA to "ADD HARDCOVER TO CART"; `book_format_selected` fires |
| Add to cart | **OBSERVED PASS** — variation added, Store API shows 1 item, subtotal $11.99, tax $0.72, total $14.70 |
| Checkout regression (**no order placed**) | **OBSERVED PASS** — Blocks checkout renders, 14 visible inputs, PLACE ORDER button present and **not clicked**, single shipping method, no "BookVAULT Shipping" anywhere |
| Test cart emptied | **OBSERVED PASS** — every added item removed, `items_count` back to 0 |
| Production untouched | **OBSERVED PASS** — `wp theme list --status=active` on production still 1.19.121; `inc/book-media.php` absent from the production theme |

### Shipping, checked because it is a hard constraint

**OBSERVED** — the Contiguous United States zone has exactly **one** method,
`flat_rate` "Contiguous US Shipping", configured cost **3.99**. **No "BookVAULT
Shipping" method is zoned anywhere.** The constraint holds.

The rendered rate for a single paperback is **$1.99**, not $3.99, because
`bhp_bundle_override_shipping_cost()` in the bundle plugin's `bundle-cart.php`
adjusts the flat rate's cost per the approved per-tier shipping table. That file
was **not** modified by this release, and the behaviour matches the production
purchase-path record of 2026-07-20. Noted as a wording divergence to reconcile, not
a regression — see the conflicts section.

### Console noise, stated rather than hidden

The only console entries observed were third-party or benign:
`w.hcaptcha.com/logo.png` (blocked third-party asset), `static.klaviyo.com`
(`ERR_BLOCKED_BY_ORB`), a Google Pay manifest icon warning from Stripe, and
`ERR_ABORTED` on the `.webm` sources — the last is the browser cancelling a partial
media fetch. Both video files were confirmed reachable (HTTP 206, correct MIME) and
playback succeeded. **Zero first-party JavaScript errors.**

---

## Conflicts recorded, not resolved

- **`CYCLE140-DEV-1`** — `/cart/` emits no `view_cart` (and no `add_to_cart`) on
  staging with an item in the cart, while `/checkout/` does emit
  `add_shipping_info` and `add_payment_info`. Pre-existing; nothing in this release
  touches that code path. **OBSERVED**, twice, with a 9-second settle.
- **`CYCLE140-DEV-2`** — repo `CLAUDE.md` and `.claude/rules/woocommerce.md` state
  the customer-facing flat rate is "$3.99, Contiguous US". The **zone configuration**
  is $3.99, but the **rendered** single-book rate is $1.99 via the approved per-tier
  table in the bundle plugin. Both statements are true of different things; the
  wording should be reconciled by the owner rather than by an agent.
- **`CYCLE140-DEV-3`** — on mobile the stage's "n / N" counter chip overlaps the
  Everest byline on the Collection's slide 1. Pre-existing overlay design, cosmetic,
  not introduced here.

## Rollback

- Staging theme: `~/bhp-STAGING-backup-1.19.140-20260802/` (151 files, taken before
  the deploy). Reinstall by ZIP, or `wp theme install --force` the prior build.
- Slide 1: change the slug back to `collection-look-01-three-books` (attachment 663
  is still uploaded).
- Amazon stills: re-add the `-navy` suffix to four slugs (attachments 659–662 are
  still uploaded) — **owner decision required**.
- Production needs no rollback: it was never changed.

## Not done

- No production deploy, no push, no PR, no merge.
- No WooCommerce product, price, stock, coupon, shipping, tax, payment or checkout
  configuration was changed on any environment.
- No order was placed.
- `docs/CHANGELOG.md`, `docs/PROJECT_STATE.md`, `docs/CURRENT_TASK.md`,
  `docs/AI_CONTEXT_INDEX.md` and `docs/ANALYTICS/EVENT_MATRIX.md` were **not**
  edited here — those canonical records are Business Operations & Knowledge's to
  update, and ready-to-paste blocks were handed over instead.
- GTM triggers/tags and GA4 custom dimensions for the five new events were not
  created; GTM remains unpublished by design.
- Safari/real-iOS behaviour was not reproduced — this environment has no Safari.
