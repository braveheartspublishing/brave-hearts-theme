# Production release: theme `1.19.350` through `1.19.354`, plus bundle plugin `1.8.79`

**Date:** 2026-09-02
**Production after this series:** theme **`1.19.354`**, bundle plugin `brave-hearts-bundle-pricing`
**`1.8.79`**
**Production before this series:** theme `1.19.349`, bundle plugin `1.8.78`
**Written:** 2026-09-02, by the `lead-developer` role, as a documentation sync after the fact.

---

## 1. What shipped, and what only reached staging

Five theme releases were built and staging-verified on 2026-09-02. **Two of them were deployed to
production, each under the founder's explicit approval: `1.19.353`, then `1.19.354`.** Bundle plugin
`1.8.79` was deployed to production on the same day.

| Version | Built and staging-verified | Deployed to production | How its contents reached production |
|---|---|---|---|
| `1.19.350` | 2026-09-02 | no | inside `1.19.353` |
| `1.19.351` | 2026-09-02 | no | inside `1.19.353` |
| `1.19.352` | 2026-09-02 | no | inside `1.19.353` |
| `1.19.353` | 2026-09-02 | **2026-09-02** | directly |
| `1.19.354` | 2026-09-02 | **2026-09-02** | directly |
| plugin `1.8.79` | 2026-09-02 | **2026-09-02** | directly |

`1.19.350`, `1.19.351` and `1.19.352` are **cumulative builds of the same working tree**, so `1.19.353`
carries all three. They are recorded separately because each is a distinct staging release with its own
tests and its own rollback artefact, and collapsing them into the version number that happened to carry
them would lose the record of what changed.

The per-release detail is in `docs/CHANGELOG.md` under the 2026-09-02 entry headed **"PRODUCTION IS NOW
THEME `1.19.354` / BUNDLE PLUGIN `1.8.79`"**. This document does not restate it.

---

## 2. Contents, in one line each

**`1.19.350`** The catalog card, sitewide. One predicate and one CSS scope replace an `is_shop()` gate, so
the shop, six product-category archives, twelve product-tag archives and product search all render the same
card. The false result count and the sort select are removed from the DOM. F-01 closed:
`/product-category/hardcover-books/` 301s to `/shop/` and empty product archives are noindexed. School
visits gain a closed-state band and a ship-to-home confirmation step.

**`1.19.351`** One deadline across every surface. `bhp_visit_deadline_display()` returns the earlier of the
registry's stated cutoff and the online close, and every visit surface reads it. The order gate is not
touched. The catalog card's age line is restored, scoped so the approved mobile geometry is unaffected.

**`1.19.352`** Production-readiness pass. The desktop age line is hidden with `display: none` and proved to
leave no zero-height ghost. A live school-visit slug is removed from a code comment. A test that pinned the
plugin version by equality is converted to a floor. A separate same-day pass with **no version bump**
removed 45 internal-role call-name occurrences from 12 files, all of them comments or assertion labels.

**`1.19.353`** Defect F-10. An explicit `?bhp_visit=<slug>` naming a registered visit now decides the
school-visit band, whatever the session holds. Display only: no session is written or cleared and no
entitlement changes.

**`1.19.354`** Cosmetic, one page. `/author-visits/` hero and section padding tightened so the first visit
card clears the fold complete with its status pill at 1440x900 and 375x812. Hero body copy moved to a brand
token.

**Plugin `1.8.79`** Built and recorded on 2026-09-02 alongside theme `1.19.345`: one arithmetic for every
blog ask, and a Signed Copies admin screen under WooCommerce.

---

## 3. Tests

| Release | Suites | Result |
|---|---|---|
| `1.19.350` | new `tests/test-cycle179-catalog-350.php`, 106 assertions | all passing. `test-shop-grid-2up-204.php` (4 assertions updated) and `test-item-209-books-shop-merge.php` (4 changes) updated to the new behaviour and green. Four sibling shop suites pass unchanged. |
| `1.19.351` | 20 new standing gates added to `test-cycle179-catalog-350.php`; `test-cycle167-readaloud-bundle-visit.php` moved to the new route | 134 passed, 0 failed. Superseded assertions preserved verbatim. |
| `1.19.352` | 3 new standing gates; full 122-suite set on the deployed artefact | suite 128 passed / 0 failed. Against a **real** `1.19.349` baseline built by installing the `1.19.349` rollback tarball and running all 121 suites against it: 15 failing suites / 31 failing assertions at `1.19.349` versus 14 / 30 at `1.19.352`. **Zero new failures.** |
| call-name scrub (no version bump) | full 122-suite set, compared per suite against the accepted `1.19.352` baseline | **empty diff, zero new failures.** |
| `1.19.353` | new `tests/test-cycle179-visit-band-f10.php`, 43 assertions | all four session-versus-URL cases plus the unknown-slug and clear-token no-ops covered. |
| `1.19.354` | new `tests/test-cycle179-author-visits-fold-354.php`, 27 assertions; full 124-suite set with `--url` on every invocation | new gate 27 pass / 0 fail. **Zero new failures** against the accepted `1.19.353` baseline: 75 failing assertions on both trees, 9 non-zero exits on both. |

**A pre-existing failing set is carried forward and is not claimed as fixed.** It is unchanged in kind
across the series and is recorded in `KNOWN_ISSUES.md`.

**A test-harness caveat that changes verdicts:** a suite's result can depend on whether `wp eval-file` is
invoked with `--url`. Every `1.19.354` run used `--url`. Runs of earlier suites in this series that omitted
it are not comparable line-for-line with runs that included it. Recorded in `KNOWN_ISSUES.md`.

---

## 4. Geometry actually measured, not inferred

`1.19.354`, measured in-page with `innerWidth` and `innerHeight` asserted in the same evaluation as the
rectangles:

- **1440x900:** first visit card top-to-bottom `763 to 984` becomes `587 to 808`. It ends **92px above** a
  900 fold, complete with school, date, time, order-by line and status pill.
- **375x812:** `652 to 850` becomes `568 to 766`, **46px above** an 812 fold.
- Hero height 444 to 332 at 1440, 410 to 362 at 375. Section gap 128 to 64 and 80 to 44.
- `style.min.css?ver=1.19.354` confirmed served at both viewports, read from the DOM.

`1.19.352`, measured in-page: the desktop age line has `getClientRects().length === 0` and
`offsetParent === null` at 1920, 1440 and 1366; the mobile line renders at 19px at an asserted 375x812.

---

## 5. Rollback artefacts

Named here so a future session can find them. **Paths are intentionally omitted for the hosted
environments;** the artefact names and their checksums are the identifying facts.

| Artefact | md5 | Entries | What it restores |
|---|---|---|---|
| `staging-1.19.352-SCRUBBED-rollback.tar.gz` | `079f8a9d13a9c955a7edc9f500f9db1b` | 710 | staging at `1.19.352`, **post** call-name scrub. This is the correct `1.19.352` restore point. |
| a pre-scrub `1.19.352` tarball | `0d33b0381fec6cd3195faaecd72c0229` | see the artefact | a valid `1.19.352` tree that **still carries the 45 internal call names**. **Not a rollback target.** Kept, not overwritten. |
| `1.19.353` staging tarball | `ecb9ab557e8b2adc2761bdf3c9299f54` | 711 | staging at `1.19.353`. Taken before the `1.19.354` install and byte-verified server-side and locally. |
| `f10-build-1.19.353.zip` | `cbdd3d3309d3cac1f93b7271fdf19d0a` | 658 | the `1.19.353` **deploy** artefact. |
| the `1.19.354` deploy ZIP | `5059149bbf3c899966a8095c397a88eb` | 659 | the `1.19.354` **deploy** artefact. Correct top-level slug; every runbook assertion run before install. |

Earlier tarballs for `1.19.349`, `1.19.350` and `1.19.351` remain in place and were not overwritten.

**Restoring is `wp theme install <artefact> --force`,** with the artefact's top-level folder name matching
the active theme slug. See `docs/RUNBOOK.md`.

⚠️ **Honestly scoped:** the artefacts above were produced and verified by the **staging** build lane. The
**production** deploys of `1.19.353`, `1.19.354` and plugin `1.8.79` were performed by a different lane on
the same day, and this document does not assert what pre-deploy production backup that lane took. If a
production rollback is ever needed, confirm the production-side artefact with that lane's record before
relying on anything here.

---

## 6. Known issues open at the end of this series

Full detail in `docs/KNOWN_ISSUES.md`. In short:

- **F-08** and **F-09**: the mobile visit surface, and clear-flag consistency. **Still open.** Neither was
  in scope for any release in this series.
- **LD-10**: on a session-open plus URL-slug-closed request, the `1.19.353` band names the URL's school
  while the per-card counters, which are plugin-side and session-driven, still count the session school's
  shelf. **Open. Reconciling them is an entitlement change and needs the owner's ruling.**
- **LD-12**: a CSS specificity trap in `style.css`. `body:not(.home) .section` and
  `body:not(.home) .component-heading` are (0,2,1); a bare class selector written against either is a
  **silent no-op** that costs a deploy cycle to discover. **Open as a documentation gap.**
- **The `--url` test-harness dependency** described in section 3. **Open.**
- **A file inside the theme tree changed mid-build,** outside any declared writer scope, while the
  `1.19.354` artefact was being built. The content was correct; the consequence is that the `1.19.354`
  artefact carries a `docs/RUNBOOK.md` that differs from the one `1.19.353` shipped. **Documentation only:
  no rendered byte and no test result is affected.** Recorded so a future diff of the two artefacts does not
  read as a defect.
- A **parked cosmetic list** for other pages exists and was deliberately not built. Only `/author-visits/`
  was in scope for `1.19.354`.

---

## 7. What this record does not claim

- It does **not** claim a real-browser verification of production after the deploys. That check was a
  separate lane's and its result is not restated here.
- It does **not** explain how any version reached production; it records that it did, on the date given.
- The geometry, test and artefact figures in sections 3, 4 and 5 are **staging** measurements, taken by the
  build lane on the byte-identical artefact. They are labelled as such rather than presented as production
  readings.
- Content updates made on production the same day were made by the owner and are **not** theme releases.
  They are outside this record.
