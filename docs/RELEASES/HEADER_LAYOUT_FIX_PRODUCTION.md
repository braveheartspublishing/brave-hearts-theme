# Release Record — Desktop Header Layout Fix

**Status:** Deployed to production, 2026-07-13. Theme version 1.19.4 → 1.19.8, then corrected 1.19.8 → 1.19.12 same day (see addendum at bottom).

## Problem
Andrew visually confirmed the production desktop header was broken at his
~1648×927 viewport: the "Big Places. Brave Hearts." tagline compressed into
narrow lines and crowded the Home nav item, and the "Get the Complete
Collection" CTA could extend beyond the dark header's right edge at
intermediate desktop widths.

## Root cause
1. `.site-logo a` used `display:flex` with the default row direction, so
   the site-name text and `.tagline` sat side by side instead of stacked —
   this wrapped the name across multiple lines and squeezed the tagline
   against the nav with zero gap at common desktop widths, even at exact
   100% zoom.
2. A `@media (min-width:1101px)` CTA `margin-left` hotfix, combined with a
   rigid (non-shrinking) nav, could let the CTA overflow the header at
   ~1101–1220px real width.

Reproduced live on both production (read-only) and staging at the exact
reported viewport before any fix was written — see `DECISIONS.md`.

## Fix
- `header.php`: wrapped the site name in `<span class="site-logo__name">`.
- `.site-logo a`: `flex-direction: column` — wordmark/tagline correctly
  stack as a two-line lockup.
- `.site-logo__name`: `white-space: nowrap` — wordmark never wraps again
  (top design priority, protected).
- `.header-inner`: added `gap: clamp(16px, 2vw, 32px)`, replacing the old
  CTA `margin-left` hotfix.
- Single consolidated `@media (max-width: 1300px)` block reclaims width in
  priority order (tagline hidden, nav tightened, CTA tightened) instead of
  several scattered one-off overrides.
- Desktop/mobile switchover raised from 900px to 1180px — direct
  measurement showed a non-wrapping wordmark + full nav + full CTA cannot
  coexist narrower than that regardless of further text tightening; moving
  the breakpoint (not squeezing further) is the correct outcome per the
  task's own design priorities.

Two iterations were needed on staging after the first two deploys (1.19.5,
1.19.6) each fixed part of the problem but left a residual overflow band —
caught by direct width-by-width measurement, not assumed fixed. See
`DECISIONS.md` for the full iteration record.

## Staging verification
Full width matrix (1920 → 375px), zoom simulation, logged-in-state
simulation, hamburger-nav functional test, keyboard focus, console-error
check — all clean on `staging2.braveheartspublishing.com` before Andrew's
visual approval.

## Production deployment
- **Method:** atomic `wp theme install --force` from a full-directory ZIP,
  built from a **fresh snapshot of production's own live files** (not the
  git repo, not staging) with only `header.php` and `style.css` patched —
  chosen specifically because `wp theme install --force` deletes the
  existing theme directory before extracting the new one, so any ZIP fed
  into it must contain the complete file set.
- **Drift discovered before deploy:** production's live `style.css` was
  missing a ~74-line WooCommerce Store-API coupon-contrast CSS block that
  exists in the git repo, and had 2 sections in minified single-line
  formatting instead of the repo's pretty-printed style. Both were
  deliberately preserved exactly as found — the deploy ZIP was built from
  production's own current files precisely so this pre-existing gap
  wouldn't be silently reintroduced or altered by an out-of-scope change.
  See `KNOWN_ISSUES.md`.
- **Pre-deploy verification:** recursive `diff -rq` between the ZIP
  payload and live production confirmed content differences in exactly
  `header.php` and `style.css`, nothing else.
- **Post-deploy verification:** recursive `diff -rq` between the deployed
  live directory and the ZIP payload showed zero difference (clean,
  atomic apply).
- **Rollback:** full-theme tarball of production's pre-deploy state,
  `bhp-prod-rollback-20260713-1.19.4.tar.gz` (sha256 `ff708dcd…`),
  captured and verified before any write, preserved in two locations.

## Production QA (logged-out + logged-in-simulated)
Full required width matrix — 1920, 1648, 1440, 1366, 1181, 1180, 1024,
mobile — all clean: wordmark never wraps, tagline never crowds the nav,
CTA stays fully inside the dark header, Expedition Guides label stays
centered (mechanism unchanged, only its breakpoint moved in sync), hamburger
nav opens/closes correctly, no horizontal page overflow at any width, no
PHP fatal (`wp eval`), no new JS console errors.

## Known limitations of this verification pass
- Zoom testing used a CSS `zoom` simulation, which does not re-evaluate
  `@media` breakpoints the same way real browser zoom does — real-width
  testing across the full range is the trustworthy evidence; zoom results
  above ~140% aren't meaningful with this technique.
- Logged-in state was verified via a `body.admin-bar` class simulation
  (confirms the header's vertical offset rule and that layout is
  otherwise unaffected), not a real authenticated WP session.
- Screenshot capture failed repeatedly (tool timeout, consistent with a
  documented limitation this session) — all verification is DOM/computed-
  style measurement instead.

## Source control
- Commit `bf8f79d` — `fix: correct responsive desktop header layout` —
  contains exactly `header.php` and `style.css`, created locally.
- `git push` blocked by the same non-interactive credential prompt
  documented in `KNOWN_ISSUES.md`; production deployment proceeded
  independently of the push per explicit instruction, since the deploy
  used a snapshot built from production's own files, not from the local
  git working tree.

---

## Addendum: single-post scoping correction (1.19.8 → 1.19.12, 2026-07-13)

**Status:** Deployed to production, 2026-07-13.

### Problem
Andrew found the deployed v1.19.8 fix broken on a real blog post
(`/blog/amazon-rainforest-facts-for-kids/`) — worse than before, with the
CTA visibly overflowing the header.

### Root cause
`style.css` had two bare `.single-post { max-width: 1120px; ... }` rules.
WordPress's own `body_class()` independently adds `single-post` to
`<body>` on every single-post template — unrelated to and coincidentally
colliding with this theme's own `post_class('single-post section')` call
on the `<article>` element in `single.php`. The unqualified selector
matched both, clamping the entire page — including the unrelated header —
to a 1120px reading-width constraint at viewports far wider than that
should ever have applied to anything. `@media` queries, being viewport-
based, could not detect this (the viewport was wide; only the container
was narrow). See `DECISIONS.md` for the full architecture-review record,
including Andrew's explicit rejection of a container-query-only
workaround in favor of the root-cause fix.

### Fix
- `style.css`: both `.single-post` rules qualified to `article.single-post`
  — scopes the reading-width constraint to the `<article>` only, matching
  what `single.php` always intended. The actual paragraph reading-width
  mechanism (`.content-narrow`, `min(100% - 2×gutter, 780px)`) is separate
  and untouched.
- `style.css`: header's intermediate/mobile `@media` breakpoints converted
  to `@container` queries against `.header-inner`'s own rendered width —
  kept as a secondary, defense-in-depth layer against any future unknown
  width-constraining context, not as the primary fix.
- Theme `Version:` bumped to 1.19.12 for cache-busting.

### Staging verification
Full width matrix (1920/1648/1440/1366/1181/1180/1024/tablet/mobile)
across Homepage, Blog article, Complete Collection, Books — zero overflow,
CTA always contained, wordmark never wraps, tagline behaves correctly,
hamburger transition consistent across all four templates, desktop nav
now correctly stays visible on blog posts (previously forced into
hamburger mode by the bug). `body.maxWidth` confirmed `"none"` on blog
posts (was `"1120px"` before the fix); `article.single-post` width stays
1120px; `.content-narrow` stays ~820px; breadcrumbs stay 1056px —
confirming the fix is surgical.

### Production deployment
- **Method:** same snapshot-based method as the original fix — a fresh
  snapshot of production's *own* live files (v1.19.8, not the git repo)
  was taken, and only `style.css` was patched, using the same manual
  block-level edits verified on staging (not a wholesale file copy, which
  would have reintroduced the missing WooCommerce coupon CSS block).
- **Drift re-verified before deploy:** production's live `style.css` at
  the time of this deploy also still contained a stale, already-inert
  `@media (max-width: 1100px)` block that the local working tree had
  already superseded as part of the container-query conversion — this
  fell within the exact region being rewritten by the approved fix, so it
  was not "unrelated drift" and was correctly allowed to be replaced. The
  only genuinely unrelated, preserved drift was the missing ~74-line
  WooCommerce coupon-contrast CSS block (still absent, exactly as before).
- **Pre-deploy verification:** recursive `diff -rq` between the patched
  build and a fresh copy of live production confirmed content differences
  in exactly `style.css`, nothing else.
- **Post-deploy verification:** recursive `diff -rq` between the deployed
  live directory and the deploy ZIP payload showed zero difference.
- **Rollback:** full-theme tarball of production's pre-deploy (v1.19.8)
  state, `bhp-prod-rollback-20260713-1.19.8.tar.gz` (sha256
  `f88c77298ff1f39c7ebecf339c1784093281078c6315970f63aad14bfd333f64`),
  verified byte-identical to live production immediately before deploy.

### Production QA (logged-out)
Full required width matrix on production across Homepage, Blog article
(the exact page Andrew reported broken), Complete Collection, Books — all
clean: no overflow, CTA contained, wordmark never wraps, desktop nav stays
visible on blog posts up to the 1181px breakpoint (matching every other
template), hamburger opens/closes correctly (verified via direct DOM
interaction — `aria-expanded` toggles, nav becomes visible), keyboard
focus visible (3px solid outline on nav links), no console errors, no new
PHP error-log entries since deploy (`wp eval 'echo "ok";'` clean).

### Known limitations of this verification pass
- Logged-in state not re-tested this pass (the fix touches no admin-bar-
  related selectors; logged-in behavior was directly verified clean during
  the original v1.19.8 production QA).
- Screenshot capture failed repeatedly (tool timeout, same documented
  limitation as the original deploy) — all verification is DOM/computed-
  style measurement and direct element interaction instead.

### Source control
- Commit `d99ee60` — `fix: scope single-post layout and stabilize header
  responsiveness` — contains exactly `style.css`, created locally on top
  of `bf8f79d`.
- `git push` timed out on the same non-interactive credential prompt
  documented in `KNOWN_ISSUES.md`. Both commits (`bf8f79d`, `d99ee60`)
  remain local-only, 2 ahead of `origin/feature/production-integration-1.17.1`.
