# Known Issues — Open Items Only


> ## ⭐⭐ ADDED 2026-09-02 · Open at the end of the `1.19.355` and `1.19.356` releases (production is theme `1.19.356` / bundle plugin `1.8.81`)
>
> Release record: `RELEASES/PRODUCTION_RELEASE_1_19_355_356.md`. **Nothing below is resolved here; each is
> recorded with what is actually established and what is not.** The `1.19.350` to `1.19.354` block below
> this one is not superseded: `F-08`, `F-09` and the `--url` caveat it records are still open and are
> carried forward, and `LD-10` and `LD-12` in it are closed by this series.
>
> ### `F-08` · School-visit surface on mobile · **STILL OPEN, and its stated condition has still never been tested.**
>
> Unchanged by this series and not in scope for either release. The whole point of the entry below remains
> true: every non-reproduction so far ran with a **desktop user-agent string**, because viewport emulation
> does not change the user agent, and `F-08`'s stated condition is the mobile one. **A real phone outranks
> every instrument named here.** Note that `1.19.356` deliberately changed the mobile shop grid, so any
> future attempt should be made against `1.19.356` and not against the older findings.
>
> ### `F-09` · `?bhp_visit=clear` consistency across `/shop/` and `/book-bundles/` · **STILL OPEN, unaddressed by this series.**
>
> Unchanged and not in scope. ⚠️ **It should be re-verified before it is specified.** `1.19.355` changed how
> an explicit slug interacts with a stored session, and the explicit clear token's early return was
> deliberately kept ahead of the new logic and is gated by a suite assertion. The original `F-09` finding
> predates that change.
>
> ### `LD-11` · **OPEN.** Minted by the `1.19.354` lane and untouched by this series.
>
> ### `LD-14` · An audit's numeric shop-card test was already failing before this series · **OPEN as a question, not a defect.**
>
> The test is `counterY - priceY < 40`. It **already failed at 115px on `1.19.354`**, because an earlier
> release inserted two elements between those two points. `1.19.355` moves that number further while
> restoring the other half of the same audit item, that the counter sits immediately above the button,
> which had been 96px out. **This series did not break a passing test; it made a deliberate trade on a
> failing one.** Whether the audit item is restated is not an engineering call.
>
> ### `LD-17` · One utility page is identified by slug, not by an option · **OPEN as a brittleness.**
>
> The set of pages that suppress the decorative "FIELD NOTE" coordinate is read from WordPress and
> WooCommerce options. **`/shipping-policy/` is the only member with no option behind it**, so it is
> resolved by slug. **Renaming that slug silently restores its kicker**, with no error anywhere. A filter
> exists as the intended escape hatch and the single brittle line is commented in place rather than hidden.
>
> ### `LD-18` · A pre-existing em dash in a customer-facing `aria-label` · **OPEN. NOT FIXED.**
>
> In the bundle plugin's Complete Collection landing page, inside a label a screen reader speaks. **It was
> found by a test assertion that was itself checking too widely, and it predates the release that found
> it.** It was deliberately not fixed: the wider em-dash sweep is the owner's to schedule, and rewriting a
> customer-facing string outside a brief is exactly the unscoped edit the brief forbade. **Recorded rather
> than silently corrected.**
>
> ### `LD-19` · Two surfaces corrected in `1.8.81` are not customer-reachable today · **OPEN, and it needs a decision, not a fix.**
>
> Two of the three surfaces moved onto the render-time saving render only through a shortcode that lives on
> a page which **redirects to `/complete-collection/`**. Confirmed live, and confirmed deliberate in code,
> where the comment records that the staging 302 is meant to be a **301** on production. **The correction is
> right and cannot be seen by a customer.** It was verified by rendering the shortcode directly rather than
> by claiming a page check that could not be made. Two questions follow and neither is engineering's:
> **is the shortcode meant to stay maintained, and should the redirect be a 301 on production?**
>
> ### `LD-20` · `display: contents` is now used on a purchase surface at 640px and below · **OPEN as an accepted trade.**
>
> It is how two separate lists are flattened so their cards can pair. On any engine since Chrome 89,
> Firefox 87 or Safari 15.4 the list **semantics** survive. On an older engine the "list of N items"
> grouping is lost. **No control is lost in either case** - every link, button and form stays in the
> accessibility tree and stays operable - and the desktop grid, where the lists are real boxes, is
> untouched. If zero tolerance is wanted, the alternative is a markup change plus a desktop-restoring rule,
> which is a larger change to a layout already accepted.
>
> ### `LD-21` · Paired cards no longer end at the same height · **OPEN as a visual judgement.**
>
> `align-items: start` is what removes the dead band above ADD TO CART; it costs a ragged bottom edge when
> the two paired cards differ in content, measured at 7px at 390 on `/shop/` and 66px on a school-visit URL.
> Leaving the default stretch would have reopened a 66px gap between the price and the button on the visit
> surface, which is the defect the release existed to remove. **The trade is stated in the stylesheet and
> here, and it is a judgement the owner can now make on a live page.**
>
> ### A test-harness dependency that changes verdicts · **STILL OPEN.**
>
> Carried forward unchanged. Every run in both releases used `--url`. ⚠️ **A second, related item was found
> in this series:** the two build lanes counted the same tree's failing assertion lines differently, 52
> versus 34, from the same raw output, each with its own method, and both said so on the record. **Neither
> is disputed and no count is picked here.** Both lanes independently found zero new failures, which is what
> the releases rest on. **If an absolute failure count is ever needed for a decision, re-derive it once,
> with one method, and record the method beside it.**
>
> ### One symptom in this series was never reproduced by any instrument
>
> The near-white "Prefer the hardcover?" line was **observed in the owner's own screenshot of production on
> his phone** and **could not be reproduced in headless Chrome**, which computed black at every width
> tested, including with a dark colour scheme emulated. **The fix does not depend on reproducing it:** the
> defect was the **absence** of an author colour, read directly from the stylesheet, and declaring one
> removes the user agent's discretion on every device. A regression guard prevents the control silently
> returning to a system colour. **No real iOS device was available to either lane.** Recorded so that a
> future reader does not treat the non-reproduction as evidence there was nothing wrong.
> ## ⭐⭐ ADDED 2026-09-02 · Open at the end of the `1.19.350` to `1.19.354` series (production is theme `1.19.354` / bundle plugin `1.8.79`)
>
> Six open items, none of them fixed by that series. Release record:
> `RELEASES/PRODUCTION_RELEASE_1_19_350_354.md`. **Nothing below is resolved here; each is recorded with
> what is actually established and what is not.**
>
> ### `F-08` · School-visit surface on mobile · **OPEN. AND THE CONDITION IT NAMES HAS NEVER BEEN TESTED.**
>
> **The report:** at 375x812 a flagged visit URL rendered no school-visit surface at all.
>
> **What is established:** at a 375 viewport **with a desktop user agent**, on `1.19.349` and again on
> `1.19.350`, the flagged page rendered all three shelf counters, the `bhp-bundle-stock-counter` markup
> three times in the served HTML, and the school name in the visible text, identical to 1440x900. **It did
> not reproduce.**
>
> ⛔ **What is NOT established, and this is the whole point:** `F-08`'s stated condition is the **mobile
> Android user agent**. CDP's `Emulation.setDeviceMetricsOverride` with `mobile: true` changes the viewport,
> the device-pixel behaviour and touch emulation. **It does not change the User-Agent string.** A dedicated
> re-run confirmed both the "mobile UA" and "desktop UA" jobs reported the same desktop UA. **The one
> variable `F-08` names was never exercised, at any point, by the lane that reported the non-reproduction.**
>
> **Therefore: no cause is claimed, no fix exists, and one non-reproduction under the wrong instrument does
> not close it.** The next attempt must set a real mobile User-Agent string explicitly, not rely on viewport
> emulation. **A real phone outranks every instrument named here.**
>
> ### `F-09` · `?bhp_visit=clear` consistency across `/shop/` and `/book-bundles/` · **OPEN, and unaddressed by the whole series.**
>
> The clear flag does not behave consistently across the two surfaces. **It did not reproduce in one run:**
> after a flagged session, `/book-bundles/?bhp_visit=clear` at 1440x900 rendered zero shelf counters, zero
> `bhp-bundle-stock-counter` markup and zero visit text. The clear took.
>
> ⛔ **That does not close it.** The original observation was of a **different sequence**, and one
> non-reproduction is not a fix. The structural reason it stayed unaddressed is worth recording:
> **`/book-bundles/` is a plugin-rendered landing page, outside the theme's catalog predicate**, so the
> theme-side work in this series could not have made the two consistent even incidentally. The `1.19.350`
> ship-to-home confirmation step changes how a parent **reaches** the clear; it does not change what the
> clear does once reached.
>
> ### `LD-10` · The band and the shelf counters can name different schools · **OPEN. NEEDS THE OWNER'S RULING.**
>
> Introduced by `1.19.353`, knowingly and recorded at the time.
>
> On a request where **the session holds an open visit** and **the URL names a registered visit that is past
> its online close**, the band correctly names the URL's school and shows that school's closed state, while
> the **per-card shelf counters, which are the plugin's and session-driven, still count the session
> school's shelf.** One page, two schools.
>
> ⛔ **This is deliberately not fixed.** `bhp_visit_band_body_class()` keys off the session because that is
> the same question the shelf counter asks, which keeps the flagged-card geometry married to the counter
> markup it exists to pay for. **Reconciling the two is an entitlement change, not a display change**, and
> it is the owner's ruling to make. Until it is made, this is the highest-value open item in the series
> because it is the only one that can put a wrong number in front of a parent.
>
> ### `LD-12` · CSS specificity trap in `style.css` · **OPEN as a documentation gap, not a defect.**
>
> `body:not(.home) .section` and `body:not(.home) .component-heading` are specificity **(0,2,1)**. A rule
> written against either as a **bare class selector is (0,2,0) and loses silently**: it computes nothing,
> renders nothing, and produces no error anywhere.
>
> **It has already cost two rules in a single pass.** One of them was written, built, shipped to staging and
> only then measured as a no-op; it was removed rather than forced, with the reasoning preserved in
> `style.css`. The `1.19.354` padding rules are deliberately written at **(0,3,1)** for this reason.
>
> **The fix is four lines of comment near the `body:not(.home)` block**, so the next author sees the rail
> before writing against it rather than after a deploy cycle. It was out of scope for `1.19.354` because
> that is a shared region of the sheet and the release was scoped to one page. **Cheapest open item on this
> list.**
>
> ### A test-harness dependency that changes verdicts · **OPEN.**
>
> A suite's result can depend on whether `wp eval-file` is invoked with `--url`. **Two runs are comparable
> only if both used it.** Every `1.19.354` run did. Runs from earlier in the series that omitted it are not
> comparable line-for-line with runs that included it. Until this is settled, **state the invocation
> alongside any suite result**, or the number does not mean what it appears to mean.
>
> ### Pre-existing cosmetic items on other pages · **PARKED, not scheduled.**
>
> A list of vertical-rhythm and colour-token items on pages other than `/author-visits/`, of the same kind
> as the fold fix that became `1.19.354`. **Deliberately not built:** that release was scoped to one page.
> It needs the owner's go-ahead before any of it becomes work, and it should be **re-checked against the
> live pages first**, because five releases have shipped since it was written. See `CURRENT_TASK.md`.
>
> ### One more thing, recorded so a future diff does not read as a defect
>
> `docs/RUNBOOK.md` inside the theme tree changed **while the `1.19.354` artefact was being built**, outside
> any declared writer scope. The content was correct and useful. The consequence: **the `1.19.354` artefact
> carries a `docs/RUNBOOK.md` that differs from the one `1.19.353` shipped.** Documentation only. No
> rendered byte and no test result is affected.

> ## ⭐ UPDATE 2026-08-05, LATER THE SAME DAY · **FIXED AND VERIFIED ON STAGING IN THEME 1.19.202. STILL LIVE ON PRODUCTION.**
>
> **The block immediately below is preserved verbatim** — it is still an accurate description of what
> production is serving right now, and it stays until the fix is deployed there.
>
> **What changed.** `bhp_defer_jquery_tag()` was rewritten to defer the DEPENDENTS rather than
> un-defer jQuery, which preserves the ordering and the 230 ms saving. It is dependency-graph driven
> — `bhp_jquery_defer_plan()` walks `wp_scripts()->registered` transitively over the page's enqueued
> closure — and contains no hardcoded handle list, so a future plugin cannot silently recreate the
> bug. It is **all-or-nothing**: if any script on the page cannot be safely deferred, jQuery is not
> deferred either, so the broken intermediate state is unrepresentable.
>
> **Verified on staging, with the same instrument that caught the defect** — Lighthouse 12.8.2,
> local headless Chrome, mobile emulation, simulated throttling:
>
> | | production 1.19.201 | staging 1.19.202 |
> |---|---|---|
> | `errors-in-console` | **FAIL**, 1 item (the `ReferenceError`) | **PASS**, 0 items (3 of 3 runs) |
> | Best Practices | 96 | **100** |
> | Performance | 77 | 82 / 78 / 81 |
>
> ⚠️ **Those two columns are DIFFERENT ENVIRONMENTS, not a before/after of one.** Production carries
> ~437 KiB of Google tag payload staging does not, so **only the `errors-in-console` and Best
> Practices rows are a like-for-like comparison.** The Performance figures are not comparable and
> must not be quoted as if they were. Neither column is a PageSpeed Insights number.
>
> ⭐ **The defect was worse than a console error, and this is measured, not inferred.** A
> headless-browser check of the live production home page shows the Mailchimp pixel's `init()`
> **never runs at all** — zero Mailchimp console output — where the fixed staging build emits its
> initialisation message. **Revenue attribution is not merely erroring on production; it is dead.**
>
> **Also verified on staging:** rendered tags on the home page and `/books/` defer jQuery AND all
> dependents; the product page, `/cart/` and `/complete-collection/` defer **nothing at all**. Never
> partial, on any surface checked. Theme suites **30 pass / 7 fail** against a **29 / 7** baseline —
> identical failure list, nothing introduced. New suite: `tests/test-jquery-defer-integrity.php`.
>
> ⛔ **NOT DEPLOYED TO PRODUCTION. Andrew's approval has not been given and was not requested by the
> session that built this.** A production deploy packet is prepared and held.
>
> ⚠️ **One thing staging CANNOT prove:** the Mailchimp Pixel SDK does not load on staging at all
> (*"SDK not loaded within timeout. Tracking disabled."*). That the pixel now **initialises** is
> proven; that end-to-end tracking then **works** is not, and needs a production re-check after any
> deploy.


## ⛔⛔ OPEN 2026-08-05 · `ReferenceError: jQuery is not defined` on the home page — LIVE ON PRODUCTION, caused by theme 1.19.201

**Severity: high. Not urgent — no order, cart, checkout or payment path is affected — but it is a real, customer-visible-in-devtools JavaScript error on the busiest page of the site, and it is a Best Practices audit failure.**

### What is observed, and with what

⭐ **OBSERVED, not inferred.** Lighthouse 12.8.2 driving local headless Chrome, mobile emulation 412×823 @ DPR 1.75, simulated Slow 4G, run **2026-08-06T00:10:52Z (2026-08-05 18:10 MDT)** against the production home page. The `errors-in-console` audit **fails with exactly one item**:

```
ReferenceError: jQuery is not defined
  at .../wp-content/plugins/mailchimp-for-woocommerce/public/js/mailchimp-woocommerce-pixel-tracking.js?ver=1.0.0:556:4
```

⭐ **It reproduces identically on staging** — same audit, same single item, same script, run 2026-08-06T00:12:42Z. **So it is fixable and testable on staging, and does not require production to diagnose.**

### Root cause

Theme 1.19.201 added `bhp_defer_jquery_tag()` (`functions.php`), which adds `defer` to `jquery-core`/`jquery-migrate` on non-commerce surfaces. `defer` postpones execution until after parsing. **Any enqueued script that depends on jQuery and is NOT itself deferred therefore now runs BEFORE jQuery.**

A read-only dependency scan of the front page's script queue (both environments, same result) found **seven** enqueued scripts whose dependency chain reaches jQuery:

| Handle | WP `strategy` | Deferred? |
|---|---|---|
| `wc-jquery-blockui` | `defer` | yes |
| `wc-add-to-cart` | `defer` | yes |
| `woocommerce` | `defer` | yes |
| `bhp-cart-drawer` | *(none)* | **no** |
| `mailchimp-woocommerce-pixel-tracking` | *(none)* | **no** |
| `rank-math` | *(none)* | **no** |
| `bhp-addon-upsell` | *(none)* | **no** |

**Four are not deferred.** Only the Mailchimp one currently throws — the other three evidently defend themselves — but all four are exposed, and the next plugin update can move any of them into the failing set without warning.

⛔ **`bhp_defer_jquery_tag()`'s own docblock reasons about the risk one level too narrowly.** It says the surface is safe because *"19 inline script blocks, ZERO referencing `$(` or `jQuery`"*. That was **true and verified** — and it is a statement about **inline** scripts. The failure is in an **external, enqueued, non-deferred** script. **The check that would have caught this was never run**, which is why the release's own record could truthfully report zero console errors.

### Proposed fix — NOT implemented, NOT deployed

**Defer the dependents too, rather than un-deferring jQuery.** Deferred scripts execute in document order, so deferring every jQuery-dependent handle preserves the existing ordering and the 230 ms saving. Concretely, in `bhp_defer_jquery_tag()`:

1. resolve each enqueued handle's dependency chain; if it reaches `jquery`/`jquery-core`, mark it for `defer`;
2. **except** where that handle carries a `before`/`after` inline block matching `jQuery` or `$(` — an inline block is printed adjacent to the tag and is not deferred with it, so deferring the file would break it;
3. if any such exception exists on the page, **fall back to not deferring jQuery at all** on that page (the existing `bhp_defer_jquery` filter already provides the switch), so the page is never left in the broken intermediate state.

⭐ **The scan already shows the exception branch is currently unused:** none of the four carries a jQuery-touching inline block today. The branch exists so a future plugin cannot silently reintroduce the failure.

**This is a theme change and therefore a new release (1.19.202), a new brief, and — because production is already exposed — an Andrew production-deployment gate. Nothing about it has been built.**

### Also recorded from the same read-only pass

- **SiteGround Speed Optimizer is NOT re-introducing render-blocking on production.** Read-only `wp option get`: `optimize_css`, `combine_css`, `optimize_javascript`, `optimize_javascript_async` and `remove_query_strings` are all **`0`**; only `enable_cache` is `1`. Lighthouse's `render-blocking-resources` reports **0 ms estimated savings** on both environments.
- **Production carries ~437 KiB of Google tag payload that staging does not** (GTM + GA4 + Google Ads). Images (494.2 KiB), fonts (252.1 KiB) and stylesheets (72.5 KiB) are **byte-identical** across the two, so 1.19.201's weight reduction is delivering on production exactly as measured on staging. The remaining production/staging gap is tags, not theme.
- ⚠️ **`bhp_gtm_container_id`, `bhp_ga4_measurement_id` and `bhp_consent_decision_approved` are all SET on production**, and GTM is loading on the live home page. **Several documents in this corpus still describe GTM as deliberately unpublished with those options unset.** That is a documentation-versus-live-state conflict; it is **recorded here, not resolved**, and routed to the Chief of Staff.


## ⛔ OPEN 2026-08-03 · The Bookvault dispatch tracker's DISPATCH path has never run against real data

**Shipped in theme 1.19.157, in DRY mode.** The tracker polls the fulfilment API every three hours and **writes no order meta, no order note and no status change.**

**What HAS been verified (2026-08-03):** one authenticated live read against the real API returned **both open orders at `SentToPrint`** — `examined=2 skipped=2 errors=0`. ⭐ **That closed the build's unverified-payload gap:** until then the code had only ever run against a mock, and nobody had confirmed the live response actually carried the fields the state machine reads.

**What has NOT been verified, and is the open item:**

- ⛔ **The dispatch path itself.** `SentToPrint` is **pre**-dispatch. The transition that completes an order and fires the "Your books have shipped" email **has never executed against real data.**
- ⛔ **No WP-Cron schedule has been independently observed** by anyone recording it.

**How it closes:** the supervised live-fire test at real dispatch of the two open orders, expected **~2026-08-11 to 08-12**. Switching the tracker live is a **separate act** at that test, not an automatic consequence of dispatch.

**Blast radius if it is wrong:** ⭐ **bounded by design, and this is the reassuring part.** The state machine requires **three** independent conditions — `IsDispatched === true` **and** a real `Dispatched` timestamp **and** a post-dispatch `Progress.Status` — and *"any error, missing field, unknown value or self-contradiction logs, skips and retries next run."* **The failure mode is "do nothing", never "email the customer anyway."** Two idempotency guards run **before** any API call, so a completed order cannot be re-transitioned or re-emailed. A kill switch (`bhp_tracker_enabled`) and a dry-run option/filter both exist.

**Manual fallback remains intact and is the current operating method.**

Record: `RELEASES/PRODUCTION_RELEASE_1_19_157.md`.

> ### ⚠️ IDENTIFIER WARNING — do not close an unrelated issue on this one's evidence
>
> An operations record describes the authenticated read above as closing an issue identified as `CYCLE142-LD-16`. ⛔ **That identifier is already in use, in the capstone technical audit, for a homepage image-weight defect that is still OPEN** and is described there as the largest remaining mobile performance win. **`LD-17`, `-18` and `-19` are likewise image items in that audit** (JPEG-vs-WebP derivatives; inconsistent `sizes` between collection and funnel pages; the product page's full-size LCP original).
>
> ⛔ **None of those four is closed, and none is closed by anything in this release.** The collision is registered and routed for a decision on which claimant keeps the number. **This entry therefore carries NO `LD` identifier at all** — describing the issue is safer than mislabelling it.

---

## ⛔ OPEN 2026-08-03 · The transactional email layer has never been seen in a real email client

**Theme 1.19.156 shipped the full E1–E7 copy layer to production.** Every HTML template has a plain-text twin; `php -l` was clean on all 17 changed files; one staging test send of E1 was made to the owner's own address and asserted to be **exactly one message to exactly one recipient**.

**What is unverified — by anyone:**

- ⛔ **No real email-client rendering check.** Desktop, mobile 390px and the cross-client matrix are **all unverified.** The building session said so plainly: *"the test send is the instrument and it is in Andrew's inbox, not this agent's."*
- ⛔ **No deliverability check** — SPF, DKIM and DMARC untested.
- ⛔ **No real order has been placed on any environment**, so **no customer-triggered email has ever been observed end-to-end.**

**How it closes:** one real order on production, then reading the resulting messages. **This is a human task.** It is the single largest unverified customer-facing surface currently live.

**Carried limitations that are deliberate, not defects:**
- **E4 on-hold** ships a **neutral** wording; the payment-specific version stays blocked until this store's on-hold causes are enumerated.
- **E5**'s unsourced pending-authorisation line was **omitted, not softened.**
- **E2 and E7 are Variant A** — each true only under an operating rule a human follows. ⭐ **The 1.19.157 tracker is what converts E2's rule into an automated fact, and it is not live yet.**

Record: `RELEASES/PRODUCTION_RELEASE_1_19_156.md`.

---

## ⛔ OPEN 2026-08-03 · `/book-bundles/` does not exist on production

**The bundle-pricing plugin was bumped 1.8.15 → 1.8.16 specifically to order the hardcover bundle offers above the paperback offers on `/book-bundles/`. That page returns HTTP 404 on production.**

**Verified live 2026-08-03, both environments:** on **staging**, page ID **356** is `book-bundles`, post type `page`, status `publish`. On **production**, ID **356** is an **attachment** (`the-amazon-ebook-cover-jpg`), and `wp post list --post_type=page --name=book-bundles` returns **nothing**.

The plugin change is **inert on production, not wrong** — it altered only offer ordering inside a shortcode, with no pricing, discount, shipping-tier, catalog, nonce or handler change. But its purpose is **unobservable on the live site**, and the release verification check written for it cannot pass.

**What closes this:** an owner decision on whether `/book-bundles/` should exist on production. Creating a page is a content decision, not an engineering one. Until then, treat any "hardcover-first bundles" claim as **staging-only**.

## ⛔ OPEN 2026-08-03 · Product **12** still names the former print vendor

A second Mariana paperback record (`-legacy-lulu`) carrying "Paperback. Illustrated." and **"Printed and shipped by Lulu"**. **Byte-identical on both environments — 2210 bytes, re-verified live on production 2026-08-03 after the 1.19.155 push.**

Deliberately untouched by every content wave so far. Every replacement in those waves was exact-string and guarded per post ID precisely so a global regex could not reach it.

**Whether it is live, legacy or a duplicate is an owner question, and it is a product record either way** — an Andrew gate. **Do not "clean it up" as a side effect of another task.**

## ⚠️ OPEN 2026-08-03 · The cart table overflows a 320px viewport

Table right edge measures **356px against a 320px viewport**. **Pre-existing** — before the 1.19.154 quantity-control change it was 341px vs 320px, already 21px past.

⭐ **It is invisible to the usual check.** `body:not(.home) .site-main` is `overflow: clip`, so `document.scrollWidth` reports **0 at every width including 320px** while cells are genuinely past the viewport edge. **`hScroll === 0` is not sufficient on this site** — measure each cell's `getBoundingClientRect().right` against `window.innerWidth`.

At **390px there is 26px of slack** and nothing is clipped. Outside the current viewport matrix; not fixed.

## ⚠️ OPEN 2026-08-03 · Collection page format pills are paperback-left while everything else is hardcover-first

`/complete-collection/`'s two format pills still render **Paperback first**, while the page's selected format, its visible panel and its sticky bar are all **hardcover**. Every other surface — product pages, the five funnel pages, the bundle offers — is now hardcover-first in order as well as in selection.

Left untouched deliberately: the brief said "verify unchanged". Making it consistent is **one function call**. **Owner call.**


## ⭐ OPEN 2026-08-03 · `CYCLE141-LD-30` — WooCommerce's `img` rule outranks bare logo classes

`.woocommerce img, .woocommerce-page img { height:auto; max-width:100% }` ships at specificity **(0,1,1)** and beats any bare `.some-class` (0,1,0) image rule. It applies **only** on product, shop, cart and checkout pages, so a logo/image rule can look correct across an entire QA pass and still be broken on exactly the commerce pages. It caused the reversed header/footer lockup to render at its natural **654×214** with real horizontal scroll in 1.19.148. **Any new image rule in the header or footer must carry a type selector** (`.site-logo img.site-logo__mark`). Fixed in 1.19.149.

## ⭐ OPEN 2026-08-03 · `CYCLE141-LD-31` — a contrast probe that reads only `backgroundColor` lies

A section with `background-image: linear-gradient(...)` reports `backgroundColor: transparent`. A contrast checker that walks ancestors for an opaque `backgroundColor` therefore skips it and reports the ratio against the **body**, producing false failures. This produced a spurious "four homepage sections are at 1.2–2.3:1" finding. **Measure contrast from sampled rendered pixels, or read `backgroundImage` too.** The one genuine instance — `#where-you-will-find-us` had no ground at all — was fixed in 1.19.149.

## ⛔ OPEN 2026-08-03 · Funnel-page CLS reported at **0.408** — carried, NOT measured by this record

A cumulative-layout-shift figure of **0.408** on the funnel pages was carried into the 2026-08-03 release-record session. ⚠️ **The agent writing this entry did not measure it and does not corroborate it** — no browser run was performed. It is recorded so it is not lost, and it is **explicitly unverified**.

It is consistent with `CYCLE141-LD-32` immediately below: the shift on these pages arrives late (~7,000 ms), so a sweep that samples early reports **0.000** and looks clean. **Two sweeps have now reported 0.000 on pages that a full-duration audit scores as shifting.**

**What closes this:** one browser run per funnel page that waits out the full load and reports CLS with its sampling window stated. Until then, **do not quote 0.000 for these pages**.

## ⭐ OPEN 2026-08-03 · `CYCLE141-LD-32` — the audience pages lazy-grow, defeating a one-shot scroll scan

`/gift-buyers-guide/` grows as content lazy-loads, so a scan bounded by a `scrollHeight` captured once stops short and reports **zero samples** rather than an error. Sticky-bar/overlap checks on these pages must either settle lazy content first or use a **scroll-independent** measurement. The Wave F sticky-bar fix was ultimately verified statically: `.bhp-compass` padding-bottom **132px** vs bar height **89.4px** = **+42.6px margin at all six mobile geometries**.

## ⚠ OPEN 2026-08-03 · `CYCLE141-LD-33` — **one limb CLOSED, one limb still OPEN**

⛔ **Read both limbs. This entry was half-fixed, and recording it as "resolved" would be a false claim about the live site.**

- ✅ **Duplicate nonce DOM id — CLOSED.** Was `bhp_bundle_nonce` emitted **3×** with the same DOM id on `/complete-collection/`. **Verified live on production 2026-08-03 at theme 1.19.155:** `id="bhp_bundle_nonce"` occurrences **0**, `name="bhp_bundle_nonce"` occurrences **5**. The name attribute is correctly repeated per form; the id is gone. Fixed in 1.19.150.
- ⛔ **`/retailers-wholesale-guide/` renders no gallery — STILL OPEN.** The other four audience landing pages do. **Re-checked live on production 2026-08-03:** the page emits no `data-bhp-gallery-count` attribute at all. Unchanged by this release.


## ⭐ OPEN 2026-08-02 · `CYCLE141-LD-20` — a specification named a product ID that is an attachment

The Lexile specification named **product 13** as “The Mariana Trench (Paperback)”. **Product 13 is an `attachment`.** The paperback is **333**; **12** is a `draft` legacy-Lulu record. Applying the edit to 13 would have written into an attachment's `post_content` and left Mariana with **no measure at all**, silently, while every occurrence assertion passed.

Caught by a `post_type` + exact-`post_title` guard that aborts before writing. **Every content edit on these records should carry that guard.** An ID written in a document is a memory; live state beats it.

**Correct IDs, verified live on both environments 2026-08-02:** 333 Mariana PB · 14 Mariana HC · 15 Everest PB · 17 Everest HC · 18 Amazon PB · 20 Amazon HC.

## ⭐ OPEN 2026-08-02 · `CYCLE141-LD-21` — a deploy ZIP built from `git ls-files` is a superset and ships internal files

Building the 1.19.146 ZIP from tracked files produced **284** entries against a deployed theme of **156** — it would have shipped `CLAUDE.md`, `.claude/rules/*.md`, `tests/`, `reports/pre-launch-seo/`, `content-engine/` and `Logo.jpg` onto a public web server. Caught by diffing the ZIP's file set against the **actually deployed** set before deploying; rebuilt from the deployed list plus the one intended addition (**157**).

➡ **Build the deploy set from what is deployed, not from what is tracked.** Secondary tell: SOP-06 step A8's `staging2` count read **28** on the bad ZIP and **5** on the correct one — which is exactly the pre-existing count `CYCLE141-LD-8` documents. **A8's number is a useful canary even though the check as worded is unsatisfiable.**

## ⭐ OPEN 2026-08-02 · `CYCLE141-LD-22` — theme deploys normalise line endings on ~110 files

The 1.19.146 deploy left **43** files byte-identical, **110** differing **by line endings only** (the previously-deployed files were CRLF; the local tree is LF under `core.autocrlf=true`), and **3** with real content differences — exactly the three intended files, plus one new asset.

Functionally inert for PHP/CSS/JS. Recorded because **“only three files changed” is false at the byte level**, and because the same normalisation will occur on the eventual production deploy: a post-deploy byte-diff against production will show ~110 line-ending differences that are **not** regressions. Anyone auditing that deploy needs this or they will report a phantom.

## ⭐ OPEN 2026-08-02 · `CYCLE141-LD-23` — WooCommerce products create no revisions, so content edits have no in-WordPress undo

`wp_get_post_revisions()` returned **0** after both Lexile writes on both environments — the `product` post type does not support revisions. The two **pages** edited in the same wave created revisions normally (5 and 6).

➡ **A product `post_content` edit is only reversible from a pre-edit backup.** Wave E's are at `~/bhp-WAVEE-backup-20260802/` on the server. **Take one before any future product content edit; a revision will not be there.**

## ⭐ OPEN 2026-08-02 · `CYCLE141-LD-24` — `check_author_fingerprint()` misses “I stood” (SEO-engine repo)

`has_first_person` matches `wrote|remember|learned|grew up|worked|traveled|chose|decided`. The **live blog bio sentence uses “I stood”**, so the check reports `has_author_connection=False` on genuinely author-connected copy and fails it for the wrong reason.

**Pre-existing and unrelated to the Island Peak allowlist**; found while testing it. Widening the verb list is a behaviour change outside that wave's brief, so it is **pinned by a deliberately failing-if-fixed test** rather than silently corrected. Delete `test_first_person_detector_misses_stood__PRE_EXISTING_GAP` when the verb list is widened.


Resolved issues are removed from this file (see `CHANGELOG.md` for history), not marked done in place.

## ⚠️ OPEN 2026-08-03 · `CYCLE142-OPS-021` — reversed brand lockup: **mechanically confirmed on production, one human look outstanding**

⛔ **The original problem — no reversed (light-on-dark) export existing, and a CSS cream plate standing in for one — is RESOLVED and has been removed from this file.** History is in `CHANGELOG.md` and `RELEASES/PRODUCTION_RELEASE_1_19_155.md`.

**Verified live on production 2026-08-03, after the 1.19.155 deploy:**

- `wp theme list --status=active` reads **1.19.155**, so the theme carrying the reversed export is the live theme.
- `assets/images/brand/brave-hearts-horizontal-reversed-rose.png` returns **HTTP 200** on production.
- The homepage references it **twice** — header and footer.
- **No `site-logo__plate` class is emitted anywhere in the rendered homepage.** The only logo class present is `site-logo__mark`.

⚠️ **What remains, stated rather than closed by assumption: the entry's own closure criterion also required "one look at the rendered header and footer."** That look has **not** been taken. The checks above are structural HTTP checks; they prove the correct asset is served and the plate markup is gone, **not** that the lockup reads correctly to a human eye at real viewports. **Closing this entry needs one person to look at the live header and footer on a phone and a desktop.**

## ⛔ OPEN 2026-08-02 · The E1 email's timing slot is empty, and must stay empty until measured

The order-confirmation email deliberately makes **no elapsed-time claim**. There is no measured production window for the current print partner. The live Terms page carries a **"24 hours"** figure written for the **former** vendor, unverified since 2026-07-09; it is a cancellation-window commitment of unknown truth and it was **not** propagated into the email.

**What closes this:** record the payment timestamp and the print partner's "Packed"/dispatch timestamp for roughly ten real orders and state the observed range. That is a portal login — a human task, not an agent one. The slot is marked in both email templates with the rule that only a measured range may fill it.

## ⛔ OPEN 2026-08-02 · Sticky CTA bar overlaps the last line of the compass module on mobile

At the worst-case scroll position (module bottom aligned to the viewport bottom, verified within 0.5px), the module's small context line is overlapped by the fixed bottom bar: **40.9px at 390, 41.3px at 360, 41.0px at 320**. It **clears by 9.3px at 1440**. The compass mark and the dedication are **never** obscured at any viewport.

The same worst-case test shows the **FAQ section overlaps the bar too**, so this is a page-level condition rather than something the module introduces. Left unfixed deliberately: the module-versus-sticky-bar treatment is an open owner decision whose own recommendation is *do nothing*.

## ⚠️ OPEN 2026-08-02 · WooCommerce plain-text order details leak HTML entities

`&#036;` and `&#8217;` appear in the plain-text order-totals block. This is emitted by WooCommerce's own `plain/email-order-details.php` through a core hook, is **pre-existing core behaviour**, and is not introduced by the theme's E1 override. Not fixed: fixing it means patching a second core template for a path the store does not currently send on (email type is `html`).

## ⚠️ OPEN 2026-08-02 · `wp sg purge` cannot purge file cache on staging

`wp sg purge` reports *"Unable to Purge File Cache. Please make sure it is enabled."* Dynamic cache purges successfully. Pre-existing staging behaviour; not caused by any recent release. Worth knowing when a staging asset looks stale.

## ⛔ OPEN 2026-08-02 · `CYCLE141-LD-11` — shipping tiers on DISTINCT TITLES, not on number of books

**OBSERVED on staging, real WooCommerce Blocks cart, 2026-08-02: 30 copies of one paperback renders shipping of `$1.99`** — subtotal $359.70, one shipping method ("Contiguous US Shipping"), tax $21.58, total $383.27. No BookVAULT line. No order was placed and the cart was emptied afterwards.

**Root cause, read from the code:** `bhp_bundle_shipping_amount()` in `plugins/brave-hearts-bundle-pricing/includes/bundle-cart.php` branches on `$eval[$format . '_tier']`, which counts **distinct titles**. At tier 1 it returns `bhp_bundle_single_shipping($format)` **regardless of quantity**. Only the mixed-format branch consults `total_quantity` at all.

**Why this is recorded rather than fixed:** the owner ruling captured in `CLAUDE.md` and `.claude/rules/woocommerce.md` reads, verbatim, *"Shipping is tiered per amount of books ordered."* The implementation tiers per **distinct title ordered**. Those two readings agree on every cart the tier table illustrates (1–3 distinct books) and diverge sharply on a single-title bulk order — exactly the classroom and library case. **Shipping is an owner-gated setting on every environment; nothing was changed.** Decision needed.

## ⛔ OPEN 2026-08-02 · `CYCLE141-LD-12` — production still names the former print vendor in customer-facing legal pages

Found while verifying an approved, narrower Terms correction. **Staging was corrected in an earlier pass; production was not** — database content is never carried by a theme deploy, which is the same mechanism that once let a false vendor claim survive 24 days on the live site.

**OBSERVED on production, 2026-08-02:**

| Page | Occurrences | What they say |
|---|---|---|
| Terms and Conditions (324) | **2** | names the former vendor as the current print-on-demand partner, and as where production would continue after a refund |
| Privacy Policy (3) | **3** | including the **data-sharing disclosure** telling visitors which processor receives their name, shipping address and phone number |

**Staging already carries the correct vendor name in all five places**, so this is a one-environment gap, not a wording question. After the approved 24-hour-delay fix landed, those two Terms paragraphs are the **only** remaining difference between the two environments' Terms pages.

⛔ **Not changed — outside the specific approval that was given.** A privacy notice naming the wrong data processor is an accuracy problem rather than a copy preference, which is why it is escalated rather than queued. Replacement text is prepared; applying it is a `post_content` edit on production and needs no deploy.

## ⛔ OPEN 2026-08-02 · funnel-isolation behaviour cannot currently be exercised

**Both funnel popups are disabled site-wide by explicit filters** — `add_filter('bhp_show_parent_popup', '__return_false')` in `functions.php` (the parent popup was deliberately retired 2026-07-17 in favour of the "Find Your Adventure" quiz) and `add_filter('bhp_show_teacher_popup', '__return_false')` in `inc/audit-remediation.php`. `has_filter()` returns true for both on staging.

**Consequence for testing:** `[data-popup-config]` count is **0** on every page at both viewports, and `localStorage` is empty on all runs — on **both** environments, so this is pre-existing and not caused by any recent build. The architectural isolation (separate storage prefixes, separate analytics prefixes, at most one popup per page) is intact by construction and confirmed by diff. ⛔ **But the behavioural check `.claude/rules/testing.md` asks for — dismiss one popup, confirm the other's storage is untouched — cannot be run while neither renders.** Recorded so that a future session does not report it as a pass, and so the deliberate retirement is not rediscovered as a bug.

## REFERENCE 2026-08-02 · `CYCLE141-OPS-009` is CLOSED — there was never a third theme version

A prior pass recorded an unexplained `Version: 1.14.2` returned by an HTTP GET of a theme stylesheet, against every other signal saying production ran 1.19.142, and correctly left it open rather than explaining it away.

**Resolved 2026-08-02 by the definitive check.** `wp theme list --status=active` on production returns **1.19.142**, and the versions differ **by directory**: `wp-content/themes/brave-hearts-theme/` is a **legacy, inactive** theme that genuinely is 1.14.2, while the active theme is `brave-hearts-theme-deploy-explorer-expedition-guides` at 1.19.142 — which is also what `wp_get_theme()->get('Version')` reports. **Never read a theme version from a guessed directory URL.** Kept here as a reference note because the mistake is easy to repeat.

## RESOLVED 2026-08-02 · `CYCLE141-LD-9` — the staging theme deploy was refused at the permission layer

**Closed.** `ssh` and `scp` were added to the runtime allow-list by the owner, and `wp theme install --force` against the staging document root now runs. Theme **1.19.143** and bundle plugin **1.8.11** were deployed to staging and fully browser-QA'd on 2026-08-02.

**The original diagnosis was correct and is worth keeping:** the refusal was a runtime permission, not a code defect, and the governance grant (staging deployment is autonomous for the engineering role) and the runtime permission had simply disagreed. Nothing in the theme needed to change. *(Retained one cycle for traceability, then removable.)*

## OPEN 2026-08-02 · `CYCLE141-LD-8` — SOP-06 step A8's "zero `staging2` occurrences" check is unsatisfiable as written

SOP-06 requires the deploy ZIP to contain **"zero `staging2` occurrences in code."** It contains **5**, and always has: two explanatory comments, `BHP_Analytics_Config::STAGING_HOST` (the staging-**detection** constant the entire analytics/consent gate depends on), the content sanitizer's staging-hostname denylist entry, and an internal-link regex. All five are correctness features, none renders to a visitor, and `class-bhp-analytics-config.php` is byte-identical to production 1.19.121.

Read literally, A8 blocks every deploy this project can make. **The check that carries the intent is "zero `staging2` occurrences *introduced*, and none in rendered output."** Recorded, not resolved — SOP wording is Business Operations & Knowledge's to own and Andrew's to approve.

## OPEN 2026-08-02 · `CYCLE141-CX-8` — `/teachers/` final CTA links into the *parent* funnel

`page-teachers.php`'s final CTA offers "Join the Adventure Club", pointing at `/reluctant-reader-adventure-kit/` — the parent funnel's landing page, whose lead magnet is a parent sample chapter and whose popup is the parent popup — from inside the teacher hub.

**This is NOT a breach of `.claude/rules/funnels.md` as written.** That rule governs popups, storage prefixes and analytics prefixes; a hyperlink is none of those, and the two funnels' storage and event namespaces remain fully separate. It is recorded as a **journey-design question**, not a defect. The educator equivalent (`/educators-adventure-learning-toolkit/`) exists and is live.

**Consequence already taken:** the Complete Collection gallery built in 1.19.143 was deliberately **not** added to `/teachers/`, because doing so would deepen the crossover rather than resolve it. Andrew's decision.

## REFERENCE 2026-07-18 (NOT a site defect — diagnostic note to prevent re-misdiagnosis): Stripe checkout "memoize" failure is a Claude-in-Chrome automation artifact, not a production bug
A Fable audit and manual testing reported that production checkout showed **no payment methods** with a console `TypeError: Cannot read properties of undefined (reading 'memoize')` from `woocommerce-gateway-stripe/build/upe-blocks.js`. A full Opus-level investigation (2026-07-18) proved this is **NOT** a Brave Hearts site defect and **must not** be "fixed" in site code.

**Proven root cause:** the failing browser sessions were running the **Claude-in-Chrome browser extension**, which injects a **second, `async`, un-handled copy of `/wp-includes/js/dist/vendor/lodash.min.js`** into the page (found grouped inside the extension's own injected DOM — `claude-agent-glow-border`, `claude-static-chat-button`, `claude-agent-animation-styles`, etc.). That async duplicate races WordPress's correct synchronous lodash + its `window.lodash = _.noConflict()` init inline, re-claims `window._`, and leaves `window.lodash` undefined at the moment Stripe's Blocks integration reads `lodash.memoize` → registration throws → payment methods disappear.

**Evidence the site is correct:**
- Raw server-rendered `/checkout/` HTML emits **exactly one** `vendor/lodash.min.js` tag (verified by server-side `curl`, no browser).
- WordPress prints its standard `window.lodash = _.noConflict();` (`lodash-js-after`) inline init; script order is correct (lodash before `upe-blocks`).
- `upe-blocks.asset.php` correctly declares `lodash` as a dependency.
- Theme, custom plugins, mu-plugins, WPConsent, CSP, SiteGround cache/optimization all ruled out (a freshly-injected inline script executed fine; no CSP present; JS optimization off).
- A **clean browser with no Claude overlay renders Stripe correctly** (the in-app Claude Browser tool — a different tool that does NOT inject lodash — has one lodash and a working Stripe field).

**Update 2026-07-19 (2nd Fable audit, BH-01/BH-02/BH-08):** an independent second audit reproduced the same `wp.template`/`memoize`/`debounce` errors on rendered staging. A fresh clean-browser re-investigation **reconfirmed the artifact diagnosis**: the in-app Browser (no injected lodash — 1 tag verified) renders the Mariana product add-to-cart correctly (variation 334, add works) and checkout payment methods render (Stripe card field, no "no methods" message, no `memoize` error). `_`/`wp.template` are legitimately `undefined` on checkout (underscore/wp-util aren't enqueued there; Blocks/Stripe use bundled lodash). The `wp.template`-not-a-function symptom likewise only appears when `_` is externally clobbered. **BH-01 remains externally pending Andrew's clean-device check** and payment code was NOT modified. Two genuine, unrelated theme-side items *were* fixed this pass (deterministic single-variation auto-select via `defer`; empty express-frame suppression) — neither touches Stripe registration. See CHANGELOG "2nd Fable audit".

**Update 2026-07-19 (CLEAN PRODUCTION VALIDATION — case closed):** after the 1.19.86 production deploy, the full purchase path was re-verified on **production** in a clean, non-instrumented browser. **BH-01 and BH-02 both PASSED.** Mariana variation **334** auto-selects and adds to cart; Stripe card fields render (4 iframes); no "no payment methods available"; **no `template`, `memoize`, or `debounce` errors**; zero console errors; shipping/totals calculate. `window._` is genuine **Underscore 1.13.8** with `_.runInContext` undefined (lodash is *not* masquerading as `_`), and the page carries **exactly one lodash and one underscore**. Final status recorded: *"Production validated. The failed Fable findings were caused by browser instrumentation injecting or altering lodash/underscore behavior and did not reproduce in a clean browser."* **No hotfix and no rollback were performed; no production files or settings were changed.** Diagnostic indicators for future triage: duplicate/altered lodash-underscore globals in the failing session; `wp.template` undefined **only** in the instrumented environment; clean production has one lodash + one underscore; variation 334 and Stripe both function normally. Also confirmed structurally correct and **not to be "fixed"**: theme/bundle plugin have **zero** lodash enqueues and **zero** `script_loader_tag` filters, there are **no mu-plugins**, SiteGround JS optimization is entirely off, and the order `underscore → wp-util (blocking) → wc-add-to-cart-variation (defer) → product-format-autoselect (defer)` is the intended BH-02 fix. Removing core lodash would break Blocks/Stripe; removing the `defer` would reintroduce the BH-02 race. See `RELEASES/FABLE_AUDIT_REMEDIATION.md` § "PRODUCTION VALIDATION — 2026-07-19".

**Standing directives (do NOT violate in future sessions):** do not add a lodash shim; do not change WooCommerce Stripe, WooCommerce Blocks, or payment-registration code; do not deploy defensive code to compensate for the Claude extension; do not treat a Claude-in-Chrome checkout failure as a production defect. **Never use a Claude-in-Chrome-instrumented browser as the source of truth for Stripe payment-field rendering** — validate checkout only on a clean device (e.g. a phone on cellular data) with no Claude extension. A clean-device Stripe render check + Andrew's manual real paid order remain the required customer-path verification.

## OPEN 2026-07-16: Educators Email 2 copy now contradicts the delivered toolkit
Email 2 ("Which part of the toolkit would help you most?") opens with "We're still putting the finishing touches on the Adventure Learning Toolkit... While you wait, you can preview..." — this was accurate when Email 1 was still a placeholder, but Email 1 was rewritten this session to confirm delivery with a real download link. A subscriber who already received the real PDF in Email 1 would then get a "still finishing" email two days later. Not rewritten unilaterally — Email 2 is locked copy per the content-operations rules, and the directive for this session explicitly said "review, not rewrite." Needs Andrew's or ChatGPT's decision on replacement copy reflecting a toolkit that's already delivered. Secondary, smaller mismatch in the same email: it asks whether "a reading log" would be useful, but the real toolkit contains a science spotlight and a reproducible student field journal, not a reading log.

## OPEN 2026-07-16 (non-blocking finding, not a defect): Educator toolkit PDF is entirely image-based
The Andrew-approved 8-page "Adventure Learning Toolkit v1.0" PDF (a "Microsoft: Print To PDF" export) has zero extractable text — every page is a flattened image (`DCTDecode`/JPEG). No screen-reader text, no copy/paste, not indexable. Confirmed via direct PDF inspection (`fitz`/PyMuPDF page count, metadata, and per-page `get_text()` all returning empty). Not treated as a blocking defect per Andrew's explicit instruction not to send the file back for redesign — recorded here as a known limitation of the delivered asset, for future reference if an accessible/text-layer version is ever produced.

## RESOLVED 2026-07-16: Educators Email 1 and Email 2 Subject/Preview Text
Both were unset as of the entry below; both are now fixed. Email 1: Subject "Your Adventure Learning Toolkit is being prepared," Preview "We're finishing the classroom resource for Charlotte and Henry's adventures." Email 2: Subject "Which part of the toolkit would help you most?," Preview "Explore discussion, geography, vocabulary, and read-aloud ideas for your students." Drafted under Andrew's standing authorization to write the bulk of the Mailchimp emails; both confirmed to survive a full page reload. All 4 of Educators' known Mailchimp gaps (If/Else, Email 3, Email 1, Email 2) are now fixed. See `ENGINEERING/MAILCHIMP_MANUAL_COMPLETION_REGISTER.md`.

## RESOLVED 2026-07-15 (later): Educators If/Else condition and Email 3 Subject/Preview Text
Both of the previously-open defects (If/Else condition unconfigured; Email 3 Subject/Preview unset) were fixed this session and confirmed to survive a full page reload and node reopen. If/Else now reads `Tags > contact is tagged > Customer - Purchased`; Email 3 Subject is "A little something for your next classroom adventure," Preview is "Use [EDUCATOR_COUPON_CODE_SUPERSEDED] for 10% off an eligible Complete Collection." See `CHANGELOG.md` for the full entry.

## RESOLVED 2026-07-16 (Frozen): Purchase-tagging scope is "any purchase suppresses"
Andrew's decision: any valid Brave Hearts purchase (individual paperback/hardcover, Collection paperback/hardcover, or any other legitimate product) suppresses the purchaser from the current pre-purchase coupon path. Reasoning and full wording recorded in `ENGINEERING/FUNNEL_CONSTITUTION.md`'s Purchase Suppression Rule section. This matches the live `Global - Tag Purchasers` trigger exactly ("any product purchase") — no code change was needed, only the documentation status changed from open-decision to Frozen. Do not reopen without new evidence.

## RESOLVED 2026-07-16: Cancellation does NOT remove the `Customer - Purchased` tag (tested, not assumed)
A controlled staging test (order #595, cancelled after the tag was confirmed applied) directly confirmed the tag remains on the contact after cancellation — `Global - Tag Purchasers` has no corresponding "remove tag" action, and cancelling the order does not trigger one. This is no longer an assumption. **Remaining open question for Andrew:** whether this is the desired policy for refunded/cancelled customers, or whether a second automation should be built to remove the tag on refund — see `ENGINEERING/FUNNEL_CONSTITUTION.md`'s Post-Purchase Target State section.

## All 5 audience Mailchimp journeys built in Draft, but every non-Parent one has a placeholder lead-magnet/guide URL
**Severity:** Medium (blocks activation for Educators, Gift Buyers, Retailers, Organizations until real asset URLs exist)
**Status:** Open, confirmed 2026-07-15
**Owner:** Engineering (asset/URL swap once ready) + Andrew (landing-page production approval, still staging-only for all 4)
**Workaround:** None needed — all 5 journeys remain in Draft and cannot send real email until explicitly activated.
**Details:** All 5 audiences (Parent, Educators, Gift Buyer, Retailer, Organization) now have a fully-structured journey: Trigger → Email 1 → delay → Email 2 → delay → If/Else purchaser-suppression → Email 3, including Parent's Email 3 ([PARENT_COUPON_CODE_SUPERSEDED]), built and verified 2026-07-15. The genuine remaining gap for 4 of the 5 audiences is content, not platform behavior: every non-Parent journey has a literal placeholder string (e.g. `[INSERT FINAL RETAILER INFORMATION PACKET URL BEFORE ACTIVATION]`) in place of a real asset link, and all 4 new landing pages are staging-only pending Andrew's page-by-page approval — all 4 were verified live this session to honestly show "Coming Soon" gating with no false promises or coupon leakage. (Educators has an additional, separate gap — see the entry above.) Full checklist: `ENGINEERING/MAILCHIMP_MANUAL_COMPLETION_REGISTER.md`.
**Next action:** Per audience: get the real lead-magnet/guide/packet asset, swap the placeholder URL, get landing-page production approval, then re-verify before activation — see the register doc for the exact remaining item per audience.

## PARTIALLY RESOLVED 2026-07-16: end-to-end purchaser-suppression test performed on staging — tagging and condition proven, branch execution still unproven
**Severity:** Low-Medium (down from Medium — the two riskiest unknowns, automatic tagging and condition correctness, are now proven; only journey-activation-dependent branch execution remains)
**Status:** Open, narrowed 2026-07-16 — this is no longer "not currently safely performable," it was performed under Andrew's explicit authorization
**Owner:** Andrew (authorize the next, smaller step when ready to move a journey toward launch)
**Workaround:** N/A
**Details:** Andrew explicitly authorized a controlled staging test: a dedicated non-admin, non-subscriber test contact (recorded only as `suppression-test-contact-01` per privacy instruction, never the real address), a WP-CLI-created WooCommerce staging order (no real payment, no production order), transitioned to Processing via `wc_get_order()->update_status()` (required because HPOS makes raw `wp post update` a silent no-op for order status). Before transitioning, confirmed via direct Bookvault plugin source-code inspection that its only fulfillment-trigger function is invoked exclusively by a manual admin order-action, never automatically by a status change — zero real-world fulfillment risk. Result: `Global - Tag Purchasers` automatically applied the `Customer - Purchased` tag (live Flow Data: 1 started, 1 completed, confirmed via both Flow Data and the Tags contact list), and Educators' If/Else condition was confirmed (read-only inspection) to reference the identical tag. **Tagging: PROVEN. Condition-configuration: PROVEN. Branch execution (a tagged contact actually routing through a live journey's Yes-branch): NOT proven** — observing that would require activating a Draft journey, which stays prohibited absent new authorization. Also newly confirmed: cancelling the order does not remove the tag (see the resolved cancellation entry above).
**Next action:** When Andrew is ready to move a specific journey toward launch, the smallest remaining step is an explicitly-authorized single-journey activation test using the same test contact to observe actual branch routing. Until then, no journey should be activated on the strength of this test alone.

## No post-purchase follow-up automation exists for any audience
**Severity:** Low (not a blocker for the 5 acquisition journeys; a genuine future-work gap)
**Status:** Open, reconfirmed 2026-07-15; technical gap specification written 2026-07-15 (later)
**Owner:** Engineering (future sprint) + Andrew (several sub-decisions needed before building — see spec)
**Workaround:** N/A
**Details:** Confirmed via the live Mailchimp automations list that no automation engages a customer after purchase (e.g. a 30–60 day check-in, cross-sell to the remaining books in the series, or a review request) for any of the 5 audiences. A technical target-state specification was written this session (see `ENGINEERING/MAILCHIMP_MANUAL_COMPLETION_REGISTER.md`'s "Post-purchase automation" section) distinguishing what's already canonical (purchaser exit, tag, Email-3 exclusion, no-duplicate-entry — all already implemented via the acquisition journeys' own If/Else) from what still needs Andrew's decision (exact follow-up timing, review-request wording/platform, refund/cancellation handling, whether/how to reference the original acquisition audience).
**Next action:** Andrew resolves the flagged sub-decisions in the spec; then design and build post-purchase automations as a separate future sprint, once the 5 acquisition journeys are activation-ready.

## Audience coupons [EDUCATOR_COUPON_CODE_SUPERSEDED] and [GIFT_BUYER_COUPON_CODE_SUPERSEDED] exist on staging only, in draft status
**Severity:** Low (intentional — not a defect)
**Status:** Open by design, 2026-07-15
**Owner:** Engineering + Andrew (production coupon creation, when ready)
**Workaround:** N/A — coupons are deliberately non-functional (`draft` post status) until explicitly published.
**Details:** `plugins/brave-hearts-bundle-pricing/includes/bundle-cart.php`'s Collection-only coupon-validation logic was generalized from a single hardcoded [PARENT_COUPON_CODE_SUPERSEDED] check to a shared `BHP_AUDIENCE_COUPON_CODES` list (`[PARENT_COUPON_CODE_SUPERSEDED]`, `[EDUCATOR_COUPON_CODE_SUPERSEDED]`, `[GIFT_BUYER_COUPON_CODE_SUPERSEDED]`). Two new coupons were created on staging (`[EDUCATOR_COUPON_CODE_SUPERSEDED]` post ID 592, `[GIFT_BUYER_COUPON_CODE_SUPERSEDED]` post ID 593) with meta matching `[PARENT_COUPON_CODE_SUPERSEDED]` exactly (10% off, Collection-only via the plugin's own cart-composition check, not WooCommerce's native product restriction fields), left in `draft` status so they cannot be applied at checkout. Verified live via the Store API: both correctly accept a genuine 3-book Complete Collection cart and correctly reject a non-qualifying cart with a coupon-specific error message; `[PARENT_COUPON_CODE_SUPERSEDED]`'s own behavior confirmed unchanged after the refactor. Neither coupon exists in production, and neither is referenced anywhere in any landing page, popup, or email.
**Next action:** Publish each coupon (staging first, then production with Andrew's approval) only once its paired Mailchimp Email 3 is ready to reference it — do not publish early.

## Audience landing pages: distinct per-audience visual module missing on Gift Buyers, Retailers, Organizations
**Severity:** Low (cosmetic/differentiation gap, not a functional defect — all 4 pages are fully functional)
**Status:** Open, confirmed 2026-07-15 during the shared-layout refinement sprint; partially addressed 2026-07-15 (Round 3)
**Owner:** Engineering
**Workaround:** None needed — pages are usable as-is; this is a content-distinctiveness gap, not a broken feature.
**Details:** Educators now has two real distinct modules (the "Invite Andrew for a Read-Aloud" CTA block, plus a new 5-figure Adventure Learning Toolkit preview module added 2026-07-15 with honest "design in progress" placeholders). Gift Buyers, Bookstores/Retailers, and Organizations still follow the identical shared section sequence (Hero → problem cards → checklist → lead magnet → Collection → trust → FAQ → final CTA) with only copy changes — no audience-specific visual module yet on any of the 3.
**Next action:** Being addressed page-by-page per the one-page-at-a-time approval rule — Educators must be explicitly approved by Andrew before Gift Buyers' own review round (which would include its equivalent module) begins.

## Audience landing pages: no genuinely logged-out visual screenshot captures produced yet
**Severity:** Low (verification-method gap, not a site defect)
**Status:** Open, ongoing this project — a second capture route was tried and also failed 2026-07-15 (Round 3)
**Owner:** Engineering (tooling), Andrew (final visual sign-off needs a real capture, not just DOM inspection)
**Workaround:** DOM/computed-style inspection (opacity, dimensions, grid columns, font sizes) has been used throughout as a substitute, and is always labeled as such rather than presented as a visual capture.
**Details:** The `computer{action:"screenshot"}` browser-automation tool has failed (timeout) on every attempt across this entire project, not just the audience-landing-page sprints — a persistent environment limitation, confirmed again via 3 more deliberately varied attempts (fresh tab, existing tab, small 400×300px `zoom` region) 2026-07-15. A second route was tried this round: Andrew's real connected Chrome (`mcp__claude-in-chrome__*`) captured successfully, but revealed that browser carries an active WordPress admin session on staging2 even in a brand-new tab, reproducing the exact same admin-toolbar/gear-button artifacts as Andrew's original PDFs — confirms the Round 2 root-cause finding in real time, but still doesn't satisfy "genuinely logged-out."
**Next action:** This is now a genuine open question for Andrew, not just a tooling note — either he supplies an incognito capture himself, or explicitly authorizes a specific alternative (e.g. logging out that Chrome session, knowing it will also close his 2 other active tabs there).

## Parent Funnel Mailchimp consolidation build — structurally complete, contact migration/testing still outstanding
**Severity:** Medium (blocks activation)
**Status:** Open, in progress. Updated 2026-07-15 — Email 3 ([PARENT_COUPON_CODE_SUPERSEDED]) is now built and verified; contact migration and end-to-end testing remain.
**Owner:** Engineering (continuation session) + Andrew (approval gates: contact migration, old-flow retirement, activation)
**Workaround:** N/A — this is in-progress build work, not a tooling gap. See `MAILCHIMP_MANUAL_COMPLETION_REGISTER.md` for the exact remaining steps.
**Details:** Mailchimp account is on Standard Annual. `Global - Tag Purchasers` (purchase tagger) is built and Active. `Parent - Acquisition Funnel` (id 89): Trigger → Email 1 → 2-day delay → Email 2 → 1-week delay → If/Else (`Tags > contact is tagged > Customer - Purchased`, confirmed configured and persisted) → **Email 3 ([PARENT_COUPON_CODE_SUPERSEDED]), built and verified 2026-07-15** (body, Subject, Preview Text all confirmed to survive a full page reload) → exit. Still outstanding: migrating the 3 real contacts currently protected in the paused live `Coupon Flow` (id 86), cutover/retirement of the old split-flow design, and an end-to-end purchaser-suppression test (see the dedicated entry above — not yet performed for any journey, including Parent). `Coupon Flow` remains deliberately Paused — do not resume it without first migrating those 3 contacts into the new journey.
**Next action:** Migrate the 3 contacts, retire the old flow, and run an end-to-end suppression test before activation. See `ENGINEERING/MAILCHIMP_STATUS.md` for full live-state detail.

## `bundle_page_view` is not consent-gated, unlike every other pageview-scoped event
**Severity:** Low (a pre-existing inconsistency, not a functional defect — the event just fires before consent is granted)
**Status:** Open, confirmed 2026-07-13, deliberately reviewed and left unfixed 2026-07-14 (Parent Funnel Sprint 1B)
**Owner:** Engineering
**Workaround:** None needed for current operation (GTM remains unpublished, so no data currently reaches GA4 regardless — this is a code-consistency gap, not an active privacy leak, since there is no live GTM/GA4 destination for the event to reach).
**Details:** `bundle_page_view` (Complete Collection pageview, fired from `plugins/brave-hearts-bundle-pricing/assets/bundle-landing.js`) pushes to `dataLayer` unconditionally, while `parent_landing_view` and all Phase 1B theme events (`view_item_list`, `add_shipping_info`, etc.) correctly gate on `BHP_Analytics_Config::should_render_analytics()`. Confirmed live on production 2026-07-14: `bundle_page_view` fired with zero consent state present in the same browser session where `parent_landing_view` correctly did not fire.
**Deliberate review, 2026-07-14 (Parent Funnel Sprint 1B):** considered fixing directly, but `bundle-landing.js` belongs to a separate, unrelated plugin (`brave-hearts-bundle-pricing`) that this sprint's own scope rules explicitly forbid modifying ("do not modify... unrelated theme or plugin systems"). No privacy/consent rule was weakened to reach this conclusion — the resolution is to leave the gap open and fix it correctly in its own isolated pass, not to either loosen `parent_landing_view`'s gating or quietly patch a file outside this sprint's authorized surface.
**Next action:** A future, isolated analytics-consistency task should gate `bundle-landing.js`'s dataLayer push the same way, as a standalone change to the bundle-pricing plugin (with its own backup/QA/version-bump cycle), not bundled into unrelated theme work.

## `BHP_CTA_Collision_Detector`/`BHP_Required_Links_Gate`: hub-URL pattern and anchor-parsing gaps
**Severity:** Low (automated gate under-reports/misses compliance; does not affect actual site behavior or real compliance)
**Status:** Open, confirmed 2026-07-13 during Legacy Blog Batch 2 work
**Owner:** Engineering
**Workaround:** Manual, live-browser verification against `required-links-policy.md`'s written definition — used successfully throughout Sprint 1 and Batch 2, and remains the practical method.
**Details:**
1. `TOPIC_HUB_URL_PATTERNS` in `inc/class-bhp-cta-collision-detector.php` only matches `/blog/category/...`, `/blog/tag/...`, and literal `/mariana-trench/` or `/bridge-books/` path segments — it has never recognized the `/teachers/#reading-growing` / `/teachers/#science-geography` anchor-hash URLs that are the *actual* format every topic-hub link in this project uses (Sprint 1 and Batch 2 alike). Confirmed by running `BHP_CTA_Collision_Detector::check()` directly against Batch 2's posts: it reported `topic_hub_link_present => false` despite genuine, policy-compliant in-body contextual sentences.
2. The anchor-extraction regex (`/<a\s+href="([^"]+)"[^>]*>/`) requires `href` to be the *first* attribute on an `<a>` tag — it silently misses links where `target="_blank"` (or any other attribute) comes first, e.g. post 30's pre-existing book-discovery link.
3. Also unfixed from an earlier finding (Sprint 1, `OPEN_QUESTIONS.md`): `detect_contextual_links()` only scans `<p>` tags (misses `<h4>`/other elements), and its Amazon-affiliate pattern requires a literal `tag=` query parameter (misses real links using `dib_tag=` or similar).
**Next action:** A dedicated, isolated engineering sprint should widen `TOPIC_HUB_URL_PATTERNS` to include the `/teachers/#...` format, make the anchor-extraction regex attribute-order-agnostic, extend the scan beyond `<p>` tags, and broaden the Amazon-affiliate pattern. Not urgent — does not block publishing, since manual verification is already the established, reliable workflow. See `OPEN_QUESTIONS.md` and `CONTENT/LEGACY_BLOG_CONVERSION_AUDIT.md`'s "Batch 2 mechanical production closeout" section.

## `assets/js/nav.js` on production predates the CTA Engine's client-side attribution enrichment
**Severity:** Low-Medium (CTA clicks still fire, just without rich attribution)
**Status:** Open, discovered 2026-07-13 (Phase 10), confirmed still open after the 2026-07-13 bundle-pricing analytics-parity deploy (that deploy was plugin-only, `nav.js` is a theme file)
**Owner:** Engineering
**Workaround:** `contextual_cta_click` still fires with `bhp_book`/`bhp_format`/`bhp_source` present (empty strings) but `cta_id`/`cta_placement`/`cta_destination_type`/`audience`/`funnel_stage`/`variant` all absent. The CTA Engine's PHP registry classes are live and correct on production; only the JS-side enrichment is stale.
**Next action:** Deploy the current `assets/js/nav.js` to production (theme-ZIP cycle, not urgent) and re-verify `contextual_cta_click` carries full attribution. See `RELEASES/BUNDLE_PRICING_ANALYTICS_PARITY_PRODUCTION.md`.

## GTM: 6-event coverage gap (deferred by CSO decision, not a defect)
**Severity:** Low (analytics completeness, deliberately scoped out of first launch)
**Status:** Open, deferred by explicit decision (2026-07-12 Phase 9)
**Owner:** Andrew (business priority call) + engineering (implementation)
**Workaround:** None needed — the events fire in code regardless; they just don't reach GTM/GA4 yet.
**Next action:** `bundle_type_purchased` closed in Phase 9 (2026-07-12). Remaining 6 (`bhp_direct_purchase_click`, `customer_review_product_click`, `customer_review_source_click`, `customer_review_impression`, `kirkus_review_link_click`, `kirkus_component_impression`) intentionally deferred — first launch stays focused on traffic/interest/format/Collection-vs-individual/cart/checkout/revenue. Revisit after Preview/DebugView validation and consent resolution. See `ANALYTICS/EVENT_MATRIX.md`.

## WPConsent's cookie banner can cover the quiz modal's close button at narrow widths
**Severity:** Low–Medium (a first-time visitor at ~320px who manually opens the quiz before answering the banner cannot see or tap the quiz's close button; Escape and the backdrop still dismiss it)
**Status:** Open, **pre-existing** — confirmed on staging 2026-07-31 during the 1.19.118 quiz QA. Not introduced by that release.
**Owner:** Engineering
**Detail:** `.wpconsent-banner` renders inside the open shadow root on `#wpconsent-container` as `position: fixed`, `z-index: 900000`, measured occupying **0,0 → 320×308** at a 320×568 viewport. The quiz modal is `z-index: 2100`, so the banner paints over it. `document.elementFromPoint()` at the close button's centre returns `#wpconsent-container` (focus/hit-testing retargets to the shadow host). At 390×844 and wider the banner does not reach the close button and the hit test passes. Related: [WPConsent shadow-DOM detection](#) notes in `quiz-modal.js` and the 2026-07-29 quiz/consent collision finding.
**Workaround:** `quiz-modal.js`'s `hasVisibleConsentUI()` already defers **automatic** opening while a consent overlay is painted, and holds the trigger as pending rather than discarding it — so the common path (auto-open) is already protected. Only a deliberate manual launcher click while the banner is still unanswered reaches the bad state. Dismissing the banner restores a correct hit target at every viewport (verified).
**Next action:** Decide whether the quiz launcher should also defer/no-op while a consent overlay is visible, or whether the modal should raise its `z-index` above the banner (**not** recommended — the visitor must stay able to answer the consent banner, and two stacked dialogs is the collision already documented). Needs its own scoped task; deliberately out of scope for 1.19.118.

## WPConsent Free's "Manage Preferences" modal only shows the Essential category until real cookies are scanned
**Severity:** Low (pre-launch-appropriate, not a launch blocker)
**Status:** Open on both staging and production (deployed to production 2026-07-13, same limitation applies — confirmed live)
**Owner:** Engineering (monitor), no action needed right now
**Workaround:** The top-level Accept All / Reject Nonessential banner buttons work correctly regardless; only the granular per-category modal toggles are affected, and there's nothing to toggle yet since no GTM tags are published/firing. The floating reopen button is the practical way to change a prior choice on both environments.
**Next action:** Re-check once real GTM tags are configured and WPConsent's cookie scanner has something to detect — Statistics/Marketing category toggles should populate naturally at that point. See `ANALYTICS/CONSENT_STATUS.md`.

## `bhp_direct_purchase_click`'s `bhp_format` parameter always empty
**Severity:** Low
**Status:** Open, known since the original 2026-07-05 event inventory
**Owner:** Engineering
**Workaround:** None needed currently — event still fires, just missing one field.
**Next action:** Populate `bhp_format` in `assets/js/nav.js` when this event fires.

## Staging/production test-suite parity gap
**Severity:** Low
**Status:** Open, deployment-lag from the manual ZIP-deploy workflow
**Owner:** Engineering
**Workaround:** Run staging's newer suites on staging; production still only has the older Amazon-review/Kirkus suites.
**Next action:** A dedicated full theme-ZIP deployment cycle (not a side effect of unrelated work).

## RESOLVED 2026-07-16 (Sprint A): "Used in 40 classrooms" corrected sitewide
Andrew confirmed the defensible fact is that books were placed in 40 Boise-area classrooms — actual classroom use, reading frequency, and outcomes are not confirmed. Replaced "Used in 40 classrooms" (which implied verified usage/adoption) with "Placed in 40 Boise classrooms" everywhere it appeared: homepage, Complete Collection page, and all 5 audience landing pages (hero proof strips and trust-section stat grids). Also precision-edited "Kirkus-reviewed series" to "Featuring a Kirkus-reviewed title" sitewide, since only one of three books has an actual Kirkus review. See `CHANGELOG.md` for the full Sprint A entry.

## RESOLVED 2026-08-02: the stale "Printed and shipped by Lulu" claim, the Lexile badges, and the debunked oxygen opener
The entry that stood here described the Lulu claim as being on *"individual product pages"* (plural) and framed it as *"needs an editorial decision, not a code fix."* **Both halves were wrong and are recorded here rather than silently deleted, because the wrong framing is why the defect sat unfixed for 24 days.**

Live state, verified by WP-CLI on both environments 2026-08-02: it was **exactly one** page (product **15**, Mount Everest Paperback), on **production only**, and the cause was **mechanical, not editorial** — the 2026-07-09 correction was applied to the **staging database only** (`fulfillment-copy-correction-2026-07-09.md` says so in its own opening paragraph) and was never promoted. Because it is `post_content`, not theme code, **no theme ZIP deploy could ever have carried it**.

Fixed 2026-08-02 under Andrew's approval, as `post_content`-only `wp post update` edits with before/after capture on both environments:
- **Lulu sentence removed** from product 15 on production. (Product **12**, the `-legacy-lulu` draft with real historical Lulu-fulfilled sales, was deliberately **not** touched; a global `wp search-replace` would have falsified it.)
- **Lexile "(500L–580L)" parenthetical removed** from all three paperbacks (**333** Mariana, **15** Everest, **18** The Amazon) on **both** environments; "grades 2–3" retained. No certified Lexile record was ever located — `ACT-CX-002` / `C25`.
- **Debunked "20% of the world's oxygen" opener replaced** on product **18**, both environments, with the copy approved 2026-07-06 (`Strategy\Fable Growth Audit 2026-07\03-WEBSITE-COPY-AND-LAYOUT-SPECIFICATIONS.md` §S1).

Verified after the edits, on rendered pages, both environments: `Lulu` = 0, `Lexile` = 0, `20% of the world's oxygen` = 0, `Perfect for:` still renders 4 bullets, no orphan punctuation, and `aggregateRating`/`review` schema still absent. See `RELEASES/TRUST_AND_CONTENT_CORRECTIONS_1_19_142.md`.

## ⛔ OPEN 2026-08-02 — BLOCKER: the "Look Inside" galleries render on staging but NOT on production, because none of the 29 media assets exist in the production media library
**Severity:** High — the headline customer-facing feature of theme 1.19.140/1.19.141/1.19.142 is invisible on the live storefront. It is **not** a regression (production had no gallery code at all before 1.19.142), and it is **not** an error: `inc/book-media.php` is a deliberate fail-closed gate — *"an item whose asset does not resolve is DROPPED… a title left with no items renders no section at all."* So production degrades to exactly its pre-release appearance, silently and cleanly.
**Status:** Open, discovered 2026-08-02 during post-deploy production QA
**Owner:** Andrew — this needs his decision before anyone acts, for a reason bigger than the deploy (see below)
**Evidence (VERIFIED LIVE 2026-08-02, both environments):**
- The registry addresses assets by **attachment slug**, never by ID. All 29 distinct slugs referenced by `bhp_book_media_registry()` were resolved against both media libraries by `wp post list --post_type=attachment --name=<slug>`: **production resolved 0 of 29. Staging resolved 28 of 29.**
- Rendered, real browser, 1440 and 390, after `wp sg purge`: **staging** Mariana **7** items, Everest **8**, The Amazon **5**, Complete Collection **9** (composite slide 1 loads, `collection-look-01-three-books-v2`, correct alt). **Production**: `[data-bhp-gallery]` element count **0** on all four pages, `bhp-gallery` markup occurrences **0**, and **zero console errors, zero page errors** — the fail-closed path working as designed.
**⚠ Why this must NOT be "just fixed" by uploading the files:** `inc/book-media.php`'s own provenance block records that the Everest set comes from an **AI-assisted production pipeline** (Higgsfield job IDs; one spread carries `trainedAlgorithmicMedia` / "Made with Google AI" XMP that was deliberately preserved), and that **two items carry visible text artefacts a print run would not produce** — a running head reading `ADVENTURES OF CHARLOTTE AND IJENRS`, and a back cover reading `breathioking landscopes`, `Perfect fcr first chapter book readers`. That file states these were **"approved by him for staging"**. Putting them on the live storefront is a separate creative approval that Andrew has not given, and no agent should infer it from a theme-deploy approval.
**Next action:** Andrew decides, per book, which gallery assets may go live. Only then does the media get uploaded to production (with the exact slugs, since the slug is the contract) and the galleries verified rendering. **Do not upload media to production as a side effect of a theme deploy.** `CYCLE141-LD-1`.

## REFERENCE 2026-08-02 (NOT a site defect — tooling note to prevent a wasted diagnostic session): SiteGround's edge now 403s an *outdated* Chrome User-Agent
Browser QA on 2026-08-02 initially returned an **80,633-byte "403 - Forbidden" page on BOTH environments**, with zero console errors — easy to misread as a deploy failure. It is neither. SiteGround's Security Optimizer rejects a request whose UA claims to be an **old** Chrome.

**Probed directly, same host, same minute:** plain `curl` (its own UA) → **200**. `Chrome/131.0.0.0` → **403**. `Chrome/138.0.0.0` → **200**. `Chrome/151.0.0.0` → **200**. `Chrome/151.0.7922.71` → **200**. Safari 17.4 → **200**. And, counter-intuitively, a literal `HeadlessChrome/151.0.0.0` → **200**. So the filter keys on the **version**, not on "headless" and not on the IP.

**What this means operationally:** the UA workaround recorded in the 2026-08-02 Wave 3 report (`Chrome/131`) **no longer works and now causes the failure it was written to avoid.** Drive headless Chrome with a UA matching a *current* Chrome build — ideally the installed browser's own version, read from `/json/version`. Unchanged and still true: `curl` proves the page shell only, never cart/checkout/shipping state (WooCommerce Blocks) — use a real browser for those.

## RESOLVED 2026-08-02: the oxygen myth in the Amazon products' short descriptions (`post_excerpt`)
The entry that stood here recorded the myth surviving in `post_excerpt` on products **18** and **20** — feeding `<meta name="description">`, `og:description`, the JSON-LD `description` and the cart line item across four URLs.

**Fixed 2026-08-02 under Andrew's approval, both environments, `post_excerpt` only.** The replacement uses §S1's own defensible framing verbatim (*"trees help drive the rain and weather that the whole continent depends on"*) rather than any new claim:
- **18** → *"Charlotte and Henry land in the Amazon — a forest whose trees help drive the rain and weather that the whole continent depends on."* (326→347 bytes production, 307→328 staging)
- **20** → *"A forest whose trees help drive the rain and weather that the whole continent depends on."* (263→293 production, 250→280 staging)

Each edit asserted **exactly 1** occurrence before writing and was **byte-verified on readback**. Verified after purge on the rendered pages, both environments: `lungs of the Earth` = 0, `One in five breaths` = 0, and the new text confirmed present in `meta description`, `og:description` **and** the rendered `rank-math-schema` `description`. See `RELEASES/PRODUCTION_RELEASE_1_19_142.md`.

## RESOLVED 2026-08-02: the Lexile parenthetical and the oxygen opener on the three HARDCOVER products (14, 17, 20)
The entry that stood here recorded `Independent readers in grades 2–3 (Lexile 500L–580L)` still stored on all three hardcovers on both environments, plus the full `"20% of the world's oxygen"` opener still on product **20**.

**Fixed 2026-08-02 under Andrew's approval, both environments, `post_content` only** — the identical, already-proven edit from the paperback pass:
- ` (Lexile 500L–580L)` removed from **14**, **17**, **20**; **"grades 2–3" retained** in every case.
- Product **20**'s opener replaced with the 2026-07-06 approved §S1 copy, the same string already applied to product 18.

Every edit asserted exactly 1 occurrence and was byte-verified on readback. **Residue check across all six products × both fields × both environments now reads `Lexile=0 Lulu=0 oxygen20=0 fifthBreath=0 lungsOfEarth=0 oneInFive=0`, with `grades 2–3` still present on all six.** Prices, `_regular_price`, `_stock_status` and SKUs diffed **unchanged** before and after on both environments.

## RESOLVED 2026-08-02: fulfilment wording divergence between production 15 and staging 15
The entry that stood here recorded that removing the false Lulu sentence had left **production 15 with no fulfilment sentence while staging 15 had one** (`CYCLE140-CX-2`).

**Fixed 2026-08-02 under Andrew's approval.** Production product **15** now carries the **already-approved 2026-07-09 wording**, verbatim from `docs/fulfillment-copy-correction-2026-07-09.md` — *"Paperback. Illustrated. Printed and fulfilled by our publishing partner, Bookvault."* — appended in production's own markup convention (matching product 18's trailing italic line). 1,625 → 1,760 bytes, byte-verified on readback; the write refused to run unless `Lulu` was already 0 and the sentence was not already present. Rendered production Everest paperback page now reports `Bookvault` present, `Lulu` = 0. **The two environments now match.**

⚠️ **Still open, and deliberately not decided here:** the three paperbacks still use **two different** sentences — Mariana (333) *"Printed and shipped by Bookvault."* versus Everest (15) and staging's convention *"Printed and fulfilled by our publishing partner, Bookvault."*, while The Amazon (18) has none. Andrew approved **parity for 15**, not a single sitewide sentence. Picking one wording for all three remains his call.

## All 6 synced Google Merchant Center products currently show "disapproved"
**Severity:** Medium (blocks any current or future free-listing/Shopping visibility)
**Status:** Open, discovered 2026-07-13 (overnight build)
**Owner:** Andrew (Merchant Center console access required — this session could not open it)
**Workaround:** None — products sync successfully but Google has rejected the listings on review.
**Next action:** Andrew opens the Merchant Center console directly to read the actual disapproval reason(s), then Engineering fixes whatever the feed issue is. Also re-authenticate the Google Ads account connection (`account_access` shows "Please reconnect your Google account"). See `MARKETING/GOOGLE_MERCHANT_STATUS.md`.

## Git push to origin blocked by non-interactive credential prompt
**Severity:** Low (commits are safe locally, just not on GitHub yet)
**Status:** Open, discovered 2026-07-13 (overnight build), recurred same day (header-fix commit, WPConsent commits)
**Owner:** Andrew (needs to push interactively, or provide a working non-interactive auth method)
**Workaround:** Commits sit locally, ahead of `origin/feature/production-integration-1.17.1`: `572421d`, `d26a7f6`, `7878b68`, `bf8f79d`, plus the WPConsent closeout commits `91bee97`, `bbf0413`, `b6bf20d`. Nothing lost — `git push` from Andrew's own machine/session (e.g. GitHub Desktop) should work normally. The `bf8f79d` header-layout-fix commit and the WPConsent staging changes were already deployed/live independently of this push, since both deploys were built from a snapshot of the live environment's own files, not from the local git working tree.
**Next action:** Andrew runs `git push` himself, or confirms a credential helper/token for future automated pushes.

## Production `style.css` is missing a ~74-line WooCommerce coupon-contrast CSS block that exists in the git repo
**Severity:** Low-medium (a real, pre-existing feature gap — WooCommerce Blocks' native coupon toggle likely still renders as a low-contrast text link on production checkout/cart)
**Status:** Open, discovered 2026-07-13 during the header-layout-fix production deploy's pre-deploy drift check
**Owner:** Engineering
**Workaround:** None — deliberately left untouched during the header fix deploy (out of scope for that change; the production deploy ZIP was built from production's own current files specifically so this gap wouldn't be silently reintroduced or altered).
**Next action:** A dedicated, explicitly-scoped session should investigate why this CSS (dated 2026-07-09 per its own comment) never reached production, and get an explicit decision from Andrew before deploying it — do not fix as a side effect of unrelated work.

## `wp theme install --force` deletes the entire existing theme directory before extracting the new ZIP
**Severity:** Informational (operational constraint, not a defect)
**Status:** Documented, confirmed 2026-07-13
**Owner:** Engineering
**Workaround:** N/A — this is why every theme-ZIP deploy (staging or production) must contain the theme's complete file set, never a subset. A narrow ZIP containing only the changed files would delete everything else on install.
**Next action:** None needed — documented here and in `DECISIONS.md`/`RUNBOOK.md` so a future session doesn't attempt a narrow-file ZIP deploy.

## "The Amazon" (third book) essentially invisible in the 35 legacy blog posts
**Severity:** Low-medium (missed cross-promotion opportunity, not a defect)
**Status:** Open, discovered 2026-07-13 (overnight build)
**Owner:** Content ops / ChatGPT (copy) + Andrew (approval)
**Workaround:** None needed — the book sells fine via its own product page; this is about the existing content corpus not mentioning it.
**Next action:** See `CONTENT/LEGACY_BLOG_CONVERSION_AUDIT.md` Batch 3 — 2 short insertions proposed (posts 100 and 84), copy needed from ChatGPT, not a code fix.

## GTM Preview / GA4 DebugView cannot connect — root cause now proven (2026-07-13)
**Severity:** Low (a tooling/network limitation, not a GTM, consent, or site defect)
**Status:** Open, root cause conclusively identified 2026-07-13 in a bounded diagnostic session. Supersedes all prior "Sign in required" framing — Andrew successfully authenticated this time, and the connection still failed, isolating the problem to network-level blocking rather than authentication.
**Owner:** Engineering (tooling)
**Diagnosis performed (2026-07-13):**
1. Confirmed production's `gtm.js` gate is architecturally correct and untouched: `BHP_GTM_Loader::should_print()` requires both `bhp_gtm_container_id` set AND (on production only) `bhp_consent_decision_approved = true`. Both remain unset/false — production was never touched.
2. Per Andrew's explicit direction, moved all Preview testing to staging instead (bypasses the `consent_decision_approved` gate via `is_staging()`). Temporarily set `bhp_staging_analytics_override = 1` on staging only — bounded, reversible, restored to unset before this session closed. Staging's `bhp_gtm_container_id`/`bhp_ga4_measurement_id` were already correctly configured from earlier work.
3. Andrew authenticated into Tag Assistant successfully (a first — no longer blocked on Google sign-in) and added `staging2.braveheartspublishing.com` as a debug domain, producing a valid `?gtm_debug=...` URL.
4. **This session's own browser-automation tool cannot load the URL**: direct `fetch()` tests from that browser proved `stripe.com` and `jsdelivr.net` (arbitrary third-party domains) succeed normally, while `googletagmanager.com` and `google-analytics.com` **specifically** fail with "Failed to fetch" — a targeted block on Google's ad/analytics domains built into this automation tool's environment, not a general third-party-request block, not a site/GTM misconfiguration.
5. **Andrew's own browser, with the Tag Assistant extension installed, also reported "Ad blocker may be blocking tags" / "0 Google tags found"** even after disabling his visible browser extensions. This remains only partially diagnosed on his end — likely candidates not yet isolated: antivirus/security-suite web protection (Norton/McAfee/Avast-style modules), OS-level or router/ISP DNS-based ad-blocking (e.g. NextDNS, AdGuard DNS, Pi-hole), or an extension that doesn't appear in the standard toggle list. An Incognito-window test was started but not completed before this session was directed to close out the diagnosis.
**Workaround:** Direct `dataLayer` inspection (used successfully and extensively throughout this project, including the 2026-07-13 Phase 10 validation and the bundle-pricing analytics-parity fix) remains the practical validation method — it has already confirmed every real event's exact name/payload/ecommerce-shape correctness on both staging and production. It is not a substitute for GTM's own tag-firing confirmation inside Google's UI.
**Next action:** Analytics activation (GTM publish, `bhp_consent_decision_approved`) stays deferred until a genuine authenticated Preview/DebugView pass succeeds. To get there: Andrew should test from a machine/network without antivirus web-shields or DNS-level ad-blocking (e.g. a personal device on a different network), or this session's browser-automation tool needs a fix to stop blocking `googletagmanager.com`/`google-analytics.com` specifically. Do not repeat the same URL-exchange workflow again without first addressing one of those two root causes — it will reproduce the identical failure.

## Mobile/tablet browser-automation viewport resize is imprecise but usable with a workaround
**Severity:** Low
**Status:** Partially resolved 2026-07-13 — narrower than previously documented
**Owner:** Engineering (tooling quirk, not a theme defect)
**Workaround:** The `resize_window` tool's preset/exact-width requests do not produce the requested `window.innerWidth` (e.g. requesting 375px yields ~475px actual; requesting 668px yields ~733px actual) — always verify actual `innerWidth` via JS eval rather than trusting the tool's confirmation, per this repo's existing machine-local guidance. However, explicitly setting a width via `resize_window` followed by a fresh `navigate` (not just a reload) DOES produce a real, usable narrower viewport for testing overflow/button-visibility/no-horizontal-scroll — used successfully during WPConsent accessibility QA at ~475px (mobile-range) and ~733px (tablet-range). A true 375px-exact device viewport still cannot be produced reliably.
**Next action:** Use the width-then-navigate pattern for future mobile/tablet spot-checks in this environment; a real-phone spot-check remains recommended before any visually-sensitive production deploy.
