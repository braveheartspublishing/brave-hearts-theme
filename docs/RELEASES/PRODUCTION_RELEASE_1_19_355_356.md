# Production release: theme `1.19.355` and `1.19.356`, plus bundle plugin `1.8.80` and `1.8.81`

**Date:** 2026-09-02
**Production after this series:** theme **`1.19.356`**, bundle plugin `brave-hearts-bundle-pricing`
**`1.8.81`**
**Production before this series:** theme `1.19.354`, bundle plugin `1.8.79`
**Written:** 2026-09-02, by the `lead-developer` role, as a documentation sync alongside the deploy.

---

## 1. What shipped, and what only reached staging

Two theme releases and two plugin releases were built and staging-verified on 2026-09-02. **Theme
`1.19.356` and plugin `1.8.81` were deployed to production on 2026-09-02, under the founder's explicit
approval.**

| Version | Built and staging-verified | Deployed to production | How its contents reached production |
|---|---|---|---|
| `1.19.355` | 2026-09-02 | no | inside `1.19.356` |
| `1.19.356` | 2026-09-02 | **2026-09-02** | directly |
| plugin `1.8.80` | 2026-09-02 | no | inside `1.8.81` |
| plugin `1.8.81` | 2026-09-02 | **2026-09-02** | directly |

`1.19.356` is built on top of the `1.19.355` working tree and `1.8.81` on top of `1.8.80`, so **one theme
artefact and one plugin artefact carry both releases each**. Production moved **two theme releases and two
plugin releases at once**. `1.19.355` and `1.8.80` are recorded separately because each is a distinct
staging release with its own tests and its own rollback artefact, and collapsing them into the version
number that happened to carry them would lose the record of what changed.

⛔ **The `1.8.80` artefact is superseded and must not be deployed to any environment.**

The per-release detail is in `docs/CHANGELOG.md` under the 2026-09-02 entry headed **"PRODUCTION IS
NOW THEME `1.19.356` / BUNDLE PLUGIN `1.8.81`"**. This document does not restate it.

---

## 2. Contents, in one line each

**`1.19.355`** The cosmetic release, and the school-visit session rule. 10px between the product page spec
line and the format label at 783px and above. The colouring product page's single-format chip row removed
from the DOM rather than hidden, and its spec line, label and price given one alignment. The shop stock
counter pinned against ADD TO CART on a visit session, 96px of air removed at 1440 and 73px at 375, with no
button moved. The duplicate H1 suppressed on plain pages whose hero already carries the title. The
decorative "FIELD NOTE" coordinate removed from eight WooCommerce and legal pages. My-account submit
buttons and checkboxes moved onto brand colours. One em dash removed from the adventure kit thank-you page.
And the rule below in section 5.

**`1.19.356`** The mobile catalog pair. At 640px and below, the Complete Collection card and the book plus
colouring book bundle card sit side by side in one row on `/shop/` and on a school-visit URL, by flattening
two lists into their shared container rather than by moving any markup. The Collection card's "Prefer the
hardcover? $48.99" given an author colour instead of falling through to the user agent's. The space between
the price and ADD TO CART reduced from 74px to 60px without shrinking the 48px touch target inside it.

**Plugin `1.8.80`** The plugin half of the school-visit session rule, as a pure decision function; and the
"Save $X" badge on the two shortcode boxes computed from live prices at render, suppressed when a live
price drifts from the price the cart will apply the discount under.

**Plugin `1.8.81`** The same render-time saving applied to the three remaining surfaces, and the Complete
Collection fine print rebuilt as joined parts so an empty last part can no longer leave a dangling
separator. **No amount changed in either plugin release.**

---

## 3. Tests

| Release | Suites | Result |
|---|---|---|
| `1.19.355` | new `tests/test-cycle179-visit-capture-355.php` (32 assertions) and `tests/test-cycle179-cosmetic-355.php` (57 assertions); full 126-suite set with `--url=` on every run | both new suites 0 failed / 0 skipped. **Zero new failures** against a `1.19.354` baseline the same lane measured on the deployed tree immediately before the build. Nine non-zero exits on both sides, the same nine suites. |
| `1.19.356` | new `tests/test-cycle179-catalog-356.php` (42 assertions); full 127-suite set with `--url=` on every run | 42 passed / 0 failed / 0 skipped. **Zero new failure lines against `1.19.355`, diffed line by line rather than counted:** no new FAIL line appeared and none went away. Nine non-zero exits on both sides, the same nine suites. |

**A pre-existing failing set is carried forward and is not claimed as fixed.** It is unchanged in kind
across the series and is recorded in `KNOWN_ISSUES.md`. Five of those failures are `test-cycle170-*`
assertions that hard-code an expected version string; their text moves with every release and they have
been failing since `1.19.342`.

⚠️ **The two lanes counted the same tree differently, and that is recorded here rather than resolved.**
For `1.19.355` the build lane reported **52 FAIL assertion lines and 2,403 to 2,492 PASS lines**. The
`1.19.356` lane, re-counting the **same raw per-suite output files with its own method**, reported **34
FAIL lines and 7,253 PASS lines** for that same tree. Neither figure is disputed by the other lane; they
are two counting methods over one set of primary command output, and both lanes said so on the record.
**Read the trend, not the absolute:** both lanes independently found **zero new failures**, which is the
claim the releases actually rest on. If an absolute failure count is ever needed for a decision, re-derive
it once, with one method, and record the method beside it.

**A test-harness caveat that changes verdicts:** a suite's result can depend on whether `wp eval-file` is
invoked with `--url`. Every run in both releases used `--url`. Runs from earlier series that omitted it are
not comparable line-for-line. Recorded in `KNOWN_ISSUES.md` and still open.

**Both lanes found defects in their own new suites by running them, and both fixed them rather than
reporting around them.** Between them: three assertions that passed while reading nothing at all, because
"this literal is absent" is satisfied by an empty string when the file was never opened; a CRLF file that
made a boundary search match nothing, which the run correctly reported as SKIP rather than PASS; an
assertion that scanned two whole files and failed on a pre-existing string the pass had not written; and
three checks whose own needles would have put the very literals they were guarding against into the
repository. Every plugin assertion is now gated to report SKIP, never PASS, on an unreadable source.

---

## 4. Geometry and colour actually measured, not inferred

All figures are **staging** measurements taken by the build lanes on the byte-identical artefacts, in a
real browser with `window.innerWidth` and `window.innerHeight` asserted **in the same evaluation** as every
rectangle, colour and contrast ratio.

`1.19.356`:

- **Mobile pairing.** Lone half-width cards **2 to 0** at 375x812 and 390x844, on `/shop/` and on a
  school-visit URL, four captures. At 390 the pair is x16 and x202, both 172px wide, both at y873; at 375,
  x16 and x195, both 165px. No horizontal overflow at any viewport on any surface, before or after.
- **Desktop byte-identical**, not merely similar: at 1440x900 the Collection item is `856,193,231,537` and
  the bundle item `102,771,608,370` before and after, on both shop surfaces. Category archives and
  `/complete-collection/` identical at 375, 390 and 1440.
- **Contrast.** The hardcover swap line computed `rgb(0,0,0)` at 19.83:1 before and `rgb(52,47,40)` at
  **12.53:1** after, on the card's cream. **The number goes down and that is not a regression:** the
  browser's own black had more contrast than the brand ink. What the change bought is determinism on every
  device, against a 4.5:1 gate cleared by a factor of 2.8.
- **The band.** Price bottom to ADD TO CART top **74px to 60px** at both mobile viewports; **6px to 6px**
  on the visit URL after a 66px regression this release created and removed; **82px unchanged** at 1440.
  Touch target 48px before and after.

`1.19.355`:

- ADD TO CART above the fold on both product templates at both viewports after the 10px was added: chapter
  page CTA at y611 (1440) and y684 (375); colouring page at y616 and y701, having been recorded at y690 on
  `1.19.353`.
- Shop stock counter to button air **96px to 0px** at 1440 and **73px to 0px** at 375.
- `/read-alouds/` H1 count **2 to 1** at both viewports.

⚠️ **One symptom in this series was never reproduced by an instrument.** The near-white hardcover line was
**observed in the founder's own screenshot of production on his phone** and **could not be reproduced in
headless Chrome**, which computed black at every width tested, including with a dark colour scheme
emulated. The fix does not depend on reproducing it: the defect is the **absence** of an author colour,
read directly from the stylesheet, and declaring one removes the user agent's discretion on every device.
A regression guard in the suite prevents the control silently returning to a system colour. **No real iOS
device was available to either lane.**

---

## 5. The one behaviour change a customer can feel

**Everything else in this series is layout, colour and wording. This is not.**

Before `1.19.355`, a school-visit flag stored in a visitor's session outranked an explicit school-visit
link. A parent flagged for one school who then opened a different school's link could see **the second
school's name in the band while the per-card shelf counters still counted the first school's shelf** - two
schools named on one page. That was tracked as `LD-10`.

**From `1.19.355`, an explicit `?bhp_visit=<slug>` that names a registered visit decides the session.**

**Stated plainly, for anyone answering a customer:**

- A parent who opens **their own school's link** sees that school's band and counters, exactly as before.
  Nothing changes for the ordinary case.
- A parent already flagged for one school who opens **a different school's link, where that other visit is
  closed**, **loses the first school's flag.** The page correctly stops offering hand delivery for a school
  they are no longer being routed to, and **the flag does not come back on its own.**
- **Recovery is one visit to their own link**, which re-arms the 14-day window. No support action is
  needed and nothing is lost permanently.
- A **truncated, mistyped or unknown** slug does nothing at all. It cannot strip an entitled parent's flag.
  That protection was recorded in 2026-08-19, it is deliberate, and it was verified live in this series.

The 2026-08-19 reasoning behind that protection is preserved verbatim in the plugin source rather than
deleted, because it still governs every slug that is not in the registry.

---

## 6. Rollback artefacts

Named here so a future session can find them. **Paths are intentionally omitted for the hosted
environments;** the artefact names and their checksums are the identifying facts.

| Artefact | md5 | Entries | What it restores |
|---|---|---|---|
| `rollback-theme-1.19.354.tar.gz` | `3324db876fd208eccbe41f21ac0de3f9` | 712 | staging at `1.19.354`, taken before the first `1.19.355` edit |
| `rollback-plugin-1.8.79.tar.gz` | `5efdeff494eda9fb1b3586d72b121981` | 99 | staging plugin at `1.8.79` |
| `rollback-theme-1.19.355.tar.gz` | `704a9be114e1194a30fa86a9a3959bbc` | 715 | staging at `1.19.355`, taken before the first `1.19.356` edit |
| `rollback-plugin-1.8.80.tar.gz` | `5004bd64fa9ecf51c3271644373c47c9` | 99 | staging plugin at `1.8.80`, pulled while the deployed plugin was still pristine |
| `build-1.19.355.zip` | `376be04ef2636f8f8a9195c3094fa9ab` | 662 | the `1.19.355` **staging** artefact |
| `build-plugin-1.8.80.zip` | `cc7273800801feed8cc70ed9336cb88d` | 90 | the `1.8.80` **staging** artefact. **Superseded. Not a deploy target.** |
| `build-1.19.356.zip` | `b0dc1a0147e0cff50a704599e62045e3` | 664 | the `1.19.356` **deploy** artefact |
| `build-plugin-1.8.81.zip` | `1ae9d1268df5291f3640c2f4094a65e4` | 90 | the `1.8.81` **deploy** artefact |

Earlier tarballs for `1.19.349` through `1.19.353` remain in place and were not overwritten.

⛔ **Superseded build artefacts exist and must not be used.** One `1.19.355` theme ZIP,
md5 `9f220aa38af5763273ba76827245cf1f`, was installed on staging before three suite defects were fixed.
Three further `1.19.356` theme ZIPs were installed before the layout fix and the suite fix; the build
lane's own record carries their identifiers. **Only the two artefacts marked "deploy" above are correct.**

**Every RUNBOOK ZIP assertion was run before each install** and passed on both artefacts: zero backslash
entries, zero `tools/`, zero `assets/covers/`, zero `docs-private`, at least 14 minified stylesheets, and
exactly one top-level prefix matching the active theme slug. **The theme ZIP is an exact superset of the
previously accepted artefact** - file lists were diffed, nothing removed - which is the check that matters
because `wp theme install --force` deletes the directory before extracting. The plugin ZIP is 90 files to
90, nothing added or removed.

**Restoring is `wp theme install <artefact> --force`,** with the artefact's top-level folder name matching
the active theme slug. See `docs/RUNBOOK.md`.

⚠️ **Honestly scoped, and this is the same caveat the previous release record carries.** The artefacts
above were produced and verified by the **staging** build lanes. The **production** deploy was performed by
a different lane, and this document does not assert what pre-deploy production backup that lane took. The
deployment runbook for this window names two pre-existing production tarballs by partial identifier,
`PROD-theme-1.19.354-pre-355` and `PROD-plugin-1.8.79-pre-80`; **those names are relayed from the runbook,
not verified by the build lanes.** If a production rollback is ever needed, confirm the production-side
artefact with the deploying lane's record before relying on anything here.

---

## 7. Known issues open at the end of this series

Full detail in `docs/KNOWN_ISSUES.md`. In short:

- **`F-08`** and **`F-09`**: the mobile visit surface, and `?bhp_visit=clear` consistency across `/shop/`
  and `/book-bundles/`. **Both still open.** Neither was in scope for either release, and `F-08`'s stated
  condition, a real mobile user-agent string, has still never been exercised by any instrument.
- **`LD-11`**: minted by the `1.19.354` lane, untouched by this series. **Open.**
- **`LD-14`**: an audit's numeric test for the shop card, `counterY - priceY < 40`, **already failed at
  115px before this series**, because an earlier release inserted two elements between those two points.
  `1.19.355` moves that failing number further while restoring the other half of the same audit item, that
  the counter sits immediately above the button. **This series did not break a passing test; it made a
  deliberate trade on a failing one.** Whether the audit item is restated is not an engineering call.
- **`LD-17`**: `/shipping-policy/` is the only page in the utility group with no WordPress or WooCommerce
  option identifying it, so it is resolved by slug. **Renaming that slug silently restores its "FIELD NOTE"
  kicker.** A filter exists as the intended escape hatch. **Open as a brittleness, flagged rather than
  hidden.**
- **`LD-18`**: a **pre-existing em dash in a customer-facing `aria-label`** in the bundle plugin's landing
  page, which a screen reader speaks. **Not fixed.** The wider em-dash sweep is out of scope and is the
  owner's to schedule; rewriting a customer-facing string outside a brief is exactly the unscoped sweep the
  brief forbade.
- **`LD-19`**: two of the three surfaces corrected in `1.8.81` render only through a shortcode on a page
  that **redirects to `/complete-collection/`**. The correction is right and **cannot be seen by a customer
  today**. Two questions follow that are not engineering's: whether that shortcode is meant to stay
  maintained, and whether the staging 302 is meant to become a 301 on production, as its own code comment
  says.
- **`LD-20`**: `display: contents` is now used on a purchase surface at 640px and below. Modern engines
  keep list semantics; engines older than Chrome 89, Firefox 87 or Safari 15.4 lose the list grouping.
  **No control is lost in either case** - every link, button and form stays in the accessibility tree and
  stays operable. If zero tolerance is wanted, the alternative is a markup change plus a desktop-restoring
  rule, which is a larger change to a layout already accepted.
- **`LD-21`**: the `align-items: start` trade. The Collection and bundle cards no longer end at the same
  height when their content differs, 7px at 390 on `/shop/` and 66px on a school-visit URL. This is what
  removes the dead band above ADD TO CART, at the cost of a ragged bottom edge. **A visual judgement the
  owner may want to make now that it is live and visible.**
- **The `--url` test-harness dependency** described in section 3. **Open.**

**Closed by this series, recorded so they are not re-opened:** `LD-10`, closed by `1.19.355`, with the
customer-visible consequence written out in section 5 · `LD-12`, the CSS specificity trap, closed by the
four-line note `1.19.355` added above the selectors that caused it · `LD-16`, the build-time saving
literal, closed by `1.8.81`, subject to `LD-19` above · `LD-15`, which asked whether a different saving
figure should be printed: **the owner ruled that the existing amount is correct, and no amount was changed
by anything in this series.**

---

## 8. What this record does not claim

- It does **not** claim a real-browser verification of production after the deploy. That check is a
  separate lane's and its result is not restated here.
- It does **not** explain how any version reached production; it records that it did, on the date given.
- The geometry, colour, test and artefact figures in sections 3, 4 and 6 are **staging** measurements taken
  by the build lanes on the byte-identical artefacts. They are labelled as such rather than presented as
  production readings.
- **Nothing in this document was measured by the lane that wrote it.** It is a documentation sync, compiled
  from the two build lanes' own records, each of which states its own instruments.
- The near-white hardcover line was never reproduced by any instrument. See section 4.
- Content updates made on production by the owner are not theme releases and are outside this record.
