# Production release: theme `1.19.357` and `1.19.358`, plus bundle plugin `1.8.82` and `1.8.83`

**Date:** 2026-09-03
**Production after this series:** theme **`1.19.358`**, bundle plugin `brave-hearts-bundle-pricing`
**`1.8.83`**
**Production before this series:** theme `1.19.356`, bundle plugin `1.8.81`
**Written:** 2026-09-03, by the `lead-developer` role, as a documentation sync alongside the deploy.

---

## 1. What shipped, and what only reached staging

Two theme releases and two plugin releases were built and staging-verified on 2026-09-03. **Theme
`1.19.358` and plugin `1.8.83` are recorded as deployed to production on 2026-09-03, on the owner's
explicit approval.**

| Version | Built and staging-verified | Deployed to production | How its contents reached production |
|---|---|---|---|
| `1.19.357` | 2026-09-03 | no | inside `1.19.358` |
| `1.19.358` | 2026-09-03 | **2026-09-03** | directly |
| plugin `1.8.82` | 2026-09-03 | no | inside `1.8.83` |
| plugin `1.8.83` | 2026-09-03 | **2026-09-03** | directly |

`1.19.358` is built on the `1.19.357` working tree and `1.8.83` on `1.8.82`, so **one theme artefact and
one plugin artefact carry both releases each**. Production moved **two theme releases and two plugin
releases at once**. `1.19.357` and `1.8.82` are recorded separately because each is a distinct staging
release with its own tests and its own rollback artefact, and collapsing them into the version number
that happened to carry them would lose the record of what changed.

⛔ **The `1.19.357` and `1.8.82` build artefacts are superseded and must not be deployed to any
environment.**

The per-release detail is in `docs/CHANGELOG.md` under the 2026-09-03 entry headed **"PRODUCTION IS
NOW THEME `1.19.358` / BUNDLE PLUGIN `1.8.83`"**. This document does not restate it.

---

## 2. Contents, in one line each

**`1.19.357`** The theme half of the after-visit phase. A school-visit link gains a third state: from
00:00 site time on the morning of the read-aloud, the flagged shop URL and every catalog archive carrying
the flag show a green band naming the school and offering ordinary shipping to the home, with no shelf
counters, no hand-delivery option, no paperback-only restriction and both formats orderable.
`/author-visits/` shows the visited school as "Read-aloud done" with a live ordering button in place of
the greyed closed control, and a past read-aloud carries the same button without losing its story card,
its recap link or its photographs.

**`1.19.358`** The hand-delivery "How It Works" steps on `/author-visits/` render only when at least one
registry visit is actually in the hand-delivery phase. A display gate over approved strings: **not one
word of the copy changed.**

**Plugin `1.8.82`** The plugin half of the after-visit phase, built as a second, parallel session flag
that the entitlement chain cannot see, plus the phase marker written onto an order.

**Plugin `1.8.83`** The after-visit state loses its end date. It now runs from the visit date onward,
indefinitely, for every registry visit, automatically. **No price, amount, coupon, shipping method, tax or
checkout setting changed in either plugin release.**

---

## 3. Tests

| Release | Suites | Result |
|---|---|---|
| `1.19.357` | new `tests/test-cycle179-after-visit-357.php` (105 assertions); full 163-suite set with `--url=` on every run | new suite 105 passed / 0 failed / 0 skipped. **Zero new failures and zero resolved failures** against a **same-day** `1.19.356` baseline: 102 FAIL lines and 13 non-zero exits on each side, the identical sets |
| `1.19.358` | new `tests/test-cycle179-358.php` (72 assertions); the `1.19.357` suite extended to 120 assertions; full 165-suite set with `--url=` on every run | both new suites 0 failed / 0 skipped. **Zero new failures** against a same-day `1.19.357` baseline taken immediately before the install: 49 FAIL lines and 13 non-zero exits on each, the identical list |

**The `1.19.357` baseline was re-taken rather than inherited**, and that mattered twice. The `1.19.356`
run filed the previous day covered only the theme suites and none of the plugin suites, and the visit
registry means one day's calendar is not the next day's. Staging was rolled back to `1.19.356` / `1.8.81`
from that lane's own tarballs, the full set was run against the same server and the same registry, and
staging was then returned.

⚠️ **The two runs report different absolute FAIL-line counts, 102 and 49, and that is a counting-method
difference rather than a change in the tree.** The same divergence is already recorded for the previous
series, where two lanes counted one tree as 52 and as 34. **Read the trend, not the absolute:** each lane
independently found zero new failures against its own same-day baseline, which is the claim the releases
rest on. If an absolute failure count is ever needed for a decision, re-derive it once, with one method,
and record the method beside it.

**A pre-existing failing set is carried forward and is not claimed as fixed.** It includes assertions that
hard-pin an old version string, whose text moves with every release.

⚠️ **One suite refused to run its flagged half on both sides of the `1.19.357` comparison.** Its own guard
printed the reason: no registry visit was open that day, so a flagged session could not be simulated and
the flagged assertions would have passed while testing the unflagged path. **It is right, it is a calendar
condition, and it is proved rather than argued because it failed identically on the same-day baseline.**
The cost is stated as a cost: **121 assertions in that suite went unexercised in both runs.**

**A test-harness caveat that changes verdicts:** a suite's result can depend on whether the runner is
invoked with `--url`. Every run in both releases used it. Runs from earlier series that omitted it are not
comparable line for line. Still open in `KNOWN_ISSUES.md`.

**Both build lanes found defects in their own new suites by running them, and fixed them rather than
reporting around them.** The `1.19.357` lane found that its own function-body extractor returned an empty
string on one function, so the three assertions that prove the entitlement gate was not widened **did not
run at all** while the run reported only one failure. Only a "was located" guard made it visible. Every
extraction in that file is now asserted non-empty before anything is asserted about its contents. The same
lane also caught itself writing an internal call name into a docblock, and caught a real date in a test
file that another suite's own assertion was written to forbid.

---

## 4. Measured on staging, not inferred

All figures are **staging** measurements taken by the build lanes on the byte-identical artefacts, in a
real browser with a fresh profile, the network cache disabled, and `window.innerWidth` asserted **in the
same evaluation** as every rectangle, colour and count.

`1.19.358`:

- **The defect, observed and then observed gone.** On `/author-visits/`, the hand-delivery "How It Works"
  block present **true** at `1.19.357` and **false** at `1.19.358`, at an asserted 1440 and an asserted
  375, step count **3 to 0**, zero console errors on both. Hand-delivery steps and a "Read-aloud done"
  card on one page: **true before, false after.**
- **The gate is narrower, not broken.** With the site's one movable clock standing on a date when two
  visits are genuinely open, the block renders **true** again at both viewports.
- **The removed bound, measured past where the old one was.** With the clock a year forward, the flagged
  URL still shows the after band at visit plus 371 days and at visit plus 365 days, at both viewports, and
  the past column carries three ordering links. **Under `1.8.82` every one of those would have been a
  plain storefront with no band and no link.**

`1.19.357`:

- Band class changes from closed to after on two flagged URLs at both viewports; a visit still a day away
  keeps the closed band, **unchanged**; the unflagged shop page shows no band before or after.
- **What the owner asked to be absent, counted in the served HTML** on a flagged after-visit URL at both
  viewports: shelf-counter elements **0**, "Only N left" **0**, school-visit framing **0**, hand-delivery
  mentions **0**, paperback-only notes **0**, the visit body class **absent**, and the hardcover option
  present and identical to the unflagged shop page.
- **Band contrast 14.24:1** against a 4.5:1 gate, computed from resolved values.
- **A stateful customer walk in one browser context:** arrive on the flagged URL, navigate away with no
  parameter and the band survives on the session, a category archive keeps it, a real cart resolves to
  "Contiguous US Shipping $1.99" with zero hand-delivery mentions, the clear token removes the band. **The
  test cart was emptied and the item count re-read as zero.**
- **The ordinary shopper is untouched by measurement rather than by intent:** the unflagged shop page is
  identical on every measured property before and after, at both viewports.

**Console errors: zero on every surface, at both viewports, in both releases.**

---

## 5. The behaviour a customer can feel

**This series changes what happens after a school read-aloud, and it is not a layout change.**

Before `1.19.357`, a school-visit link closed the day before the visit and stayed closed. A parent who
came to it after the read-aloud found an ordinary storefront with no school context at all.

**From `1.19.357`, and as shipped in `1.19.358` with plugin `1.8.83`:**

- From 00:00 site time on the morning of the read-aloud, that school's link opens again in a **ship-only**
  state. The band names the school and says the books can still be ordered and shipped to the home.
- **This is generic and automatic.** It applies to every registry visit, past and future, from that
  visit's own date. There is no per-school switch, no manual step, and **no end date**. A visit that
  happened weeks ago is in this state now, and a visit added next year will enter it on its own date
  without anyone doing anything.
- **Hand delivery is not offered, and the shelf counters are gone.** Both formats are orderable and
  shipping is ordinary. Nothing about the after-visit state grants a school-visit entitlement.
- **The button says shipped, never signed.** Books ordered before the visit are signed in person on the
  day; an after-visit order is printed and posted, and nobody signs it.
- **The day before a visit is unchanged** and still shows the closed band.
- A parent's remembered school context still lasts **14 days** per click, while the link itself now lasts
  indefinitely. Following the link again re-arms it. Recorded as `LD-24`.

**For anyone answering a customer:** a parent who was at a read-aloud can order from that school's link at
any time afterwards, and the books are posted to their home. They cannot choose hand delivery, because the
visit has happened.

---

## 6. Rollback artefacts

Named here so a future session can find them. **Paths are intentionally omitted for the hosted
environments;** the artefact names and their checksums are the identifying facts.

| Artefact | md5 | Entries | What it restores |
|---|---|---|---|
| `rollback-theme-1.19.356.tar.gz` | `508ea8871bf4f509d3ed2b06c1dade7a` | 717 | staging at `1.19.356`, pulled before the first `1.19.357` install |
| `rollback-plugin-1.8.81.tar.gz` | `ba344dcef51d530910c655dedff9a8e2` | 99 | staging plugin at `1.8.81` |
| `rb358-theme-1.19.357.tgz` | `395133eaf6a44723595999becf2b681d` | 719 | staging at `1.19.357`, pulled before the first `1.19.358` install |
| `rb358-plugin-1.8.82.tgz` | `add9cd7d78672f7680a8413ed1551dd3` | 99 | staging plugin at `1.8.82` |
| `build-1.19.357.zip` | `78b2219ae600b57410d4ccf95b0eb57d` | 666 | the `1.19.357` **staging** artefact. **Superseded. Not a deploy target.** |
| `build-plugin-1.8.82.zip` | `247f7c2d5cb144bad8334eec08508884` | 90 | the `1.8.82` **staging** artefact. **Superseded. Not a deploy target.** |
| `build-1.19.358.zip` | `62346f52abb22745b2d85b53e67af894` | 667 | the `1.19.358` artefact |
| `build-plugin-1.8.83.zip` | `6d973deec1858c967c9192d1c3355532` | 90 | the `1.8.83` artefact |

Earlier tarballs, including the previous series', remain in place and were not overwritten. **Both
rollback pairs were pulled and md5-verified before the first install of the release they protect**, and
**both were exercised for real**: staging went `1.19.357` to `1.19.356` and back while the same-day
baseline was taken, and `1.19.358` to `1.19.357` and back while the before-and-after evidence was taken.

**Every RUNBOOK ZIP assertion was run before each install** and passed on both artefacts: zero backslash
entries, zero `tools/`, zero `assets/covers/`, at least 14 minified stylesheets, and exactly one top-level
prefix matching the active theme slug. **The theme ZIP is an exact superset of the previously accepted
artefact** - file lists diffed, nothing removed - which is the check that matters because a forced theme
install deletes the directory before extracting. The plugin ZIP is 90 files to 90.

**Restoring is `wp theme install <artefact> --force`,** with the artefact's top-level folder name matching
the active theme slug. See `docs/RUNBOOK.md`.

⚠️ **Honestly scoped, and this is the same caveat the previous release record carries.** The artefacts
above were produced and verified by the **staging** build lanes, each of which recorded that it never
copied a ZIP to production. The **production** deploy was performed by a different lane, and this document
does not assert what pre-deploy production backup that lane took, or which artefacts it installed. **If a
production rollback is ever needed, confirm the production-side artefact with the deploying lane's record
before relying on anything here.**

---

## 7. Known issues open at the end of this series

Full detail in `docs/KNOWN_ISSUES.md`. In short:

- **`LD-22`**: an internal identifier that should not be in the public source. Present in several places
  inherited from earlier releases, **written by neither release in this series and removed by neither**. A
  plugin suite assertion already fails on it and failed identically on the same-day baseline, so the
  failure is a standing signal rather than a regression. **This repository is public, which is why it is
  the open item most worth scheduling.**
- **`LD-24`**: a parent's remembered school context lasts 14 days per click while the link itself now has
  no end. The two numbers previously differed by 16 days; they now differ without bound. Deliberate, one
  lifetime mechanism rather than two, **and recorded because the numbers diverge, not because it is
  known to be wrong.**
- **`LD-26`**: the after-window fail-safe direction is now inverted. A broken filter hook used to fall
  back to a 30-day window, so the failure mode was a band closing too early; it now falls back to
  unlimited, so the failure mode is a band that never closes. **This follows necessarily from the ruling**
  and is asserted by the suite. Registered because it is a genuine change in failure behaviour that no
  brief named.
- **`LD-27`**: every past read-aloud now reopens permanently, including the ones that already have. There
  is no expiry and no manual switch. **That is exactly what the ruling asks for**, and it is recorded so
  it is a decision seen rather than discovered.
- **`LD-28`**: `/author-visits/`'s past column accumulates one ordering button per visit, for ever, with
  no pagination and no cap. **Not a defect today**, and the lane that found it did not invent a fix. It is
  a scaling question about a page reached from printed codes, and it belongs to design and to the owner.
- **`LD-25`**: the visit band renders on the shop page and on category archives but not on a product page
  or the collection landing page. **Unchanged since `1.19.350`** and true of the open and closed states
  too. Named because a reader should not have to discover that boundary from a screenshot.
- **`F-08`** and **`F-09`**: the mobile visit surface, and clear-token consistency across two surfaces.
  **Both still open.** Neither was in scope for either release, and `F-08`'s stated condition, a real
  mobile user-agent string, has still never been exercised by any instrument.
- The **`--url` test-harness dependency** described in section 3, and the counting-method divergence
  beside it. **Open.**

**Closed by this series, recorded so it is not re-opened:** `LD-23`, the hand-delivery steps rendering
beside a "Read-aloud done" card, closed by `1.19.358`, observed true before and false after at both
viewports.

**`LD-10` is not reopened by this series.** It was closed by `1.19.355` and is recorded closed in
`RELEASES/PRODUCTION_RELEASE_1_19_355_356.md` section 5. Both build lanes in this series re-asserted its
fix directly, including on the hop that added the second session flag.

---

## 8. What this record does not claim

- It does **not** claim a real-browser verification of production after the deploy. That check is a
  separate lane's and its result is not restated here.
- It does **not** claim to have read production's version. **The version numbers and the deploy date here
  are recorded from the deploying lane.** Re-read them with `wp theme list --status=active` and
  `wp plugin get brave-hearts-bundle-pricing --field=version` over SSH before relying on them.
- It does **not** assert which artefacts the deploying lane installed, only that the contents of
  `1.19.357` and `1.8.82` are inside the later artefacts by construction.
- The geometry, colour, test and artefact figures in sections 3, 4 and 6 are **staging** measurements
  taken by the build lanes on the byte-identical artefacts. They are labelled as such rather than
  presented as production readings.
- **Nothing in this document was measured by the lane that wrote it.** It is a documentation sync,
  compiled from the two build lanes' own records, each of which states its own instruments.
- **The order phase marker was never observed on a real order.** Proving it needs a real order, which is a
  store data change the build lanes do not make. What is asserted instead is the half where a mistake is
  dangerous: the after-visit marker cannot write the hand-delivery flag and cannot run on a hand-delivery
  order.
- **A real after-visit cart was checked once, during `1.19.357`, and not re-checked during `1.19.358`.**
  Neither change in `1.19.358` touches the cart, shipping or checkout, and the full suite run covers the
  cart and shipping suites with zero new failures, but the claim is inherited rather than re-observed.
- **No real phone was used.** Everything mobile in this series is emulated at an asserted 375.
- Content updates made on production by the owner are not theme releases and are outside this record.