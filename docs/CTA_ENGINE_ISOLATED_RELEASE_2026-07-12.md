# CTA Engine — Phase 8B Blocker Remediation & Isolated Release Package

**Date:** 2026-07-12
**Status:** Fixed and validated on staging. **Deployed to production 2026-07-12** after Andrew's explicit, current-turn authorization — see `docs/CTA_ENGINE_PRODUCTION_DEPLOYMENT_2026-07-12.md` for the full deployment record, drift findings, and live QA results.
**Scope:** Isolated CTA Engine + minimal QA-safety classes only — no Mailchimp, WooCommerce, [PARENT_COUPON_CODE_SUPERSEDED], Google Merchant, GTM/GA4, campaign-landing, conversion-scoring, or Pinterest changes.

## Blocker 1 — Shortcode `id` parameter (fixed)

**Root cause:** `BHP_CTA_Engine::shortcode()` never accepted an `id` attribute. `BHP_Blog_Draft_Generator::cta_marker()` emits `[bhp_contextual_cta id="..."]` into generated drafts, but `shortcode_atts()` silently discarded the unrecognized `id` key, so the shortcode always fell through to the generic classification-based `render_for_post()` — the same selection `related-content.php`'s end-of-article fallback also performs on the same page, producing two near-identical CTA blocks.

**Fix** (`inc/class-bhp-cta-engine.php`): `shortcode()` now accepts `id`. A non-empty, valid `id` resolves via the existing `select_specific()` method — never a fabricated substitute. An unknown/unresolvable `id` renders nothing (safe failure). Empty/absent `id` is unchanged: falls back to `render_for_post()`. `placement`/`variant` behavior is untouched.

## Duplicate-CTA prevention (fixed)

**Fix** (`template-parts/guides/related-content.php`): the automatic end-of-article fallback now checks `has_shortcode( $post->post_content, 'bhp_contextual_cta' )` — WordPress's own shortcode parser, not text matching — before rendering. A post that already carries an explicit CTA shortcode gets that CTA only; the automatic fallback is skipped. Registry-curated posts and product pages are unaffected (separate code paths, untouched).

## Blocker 2 — QA-gate wiring (fixed on staging)

The local repo's `functions.php` already `require_once`s all four QA-gate classes (`class-bhp-content-html-sanitizer.php`, `class-bhp-cta-collision-detector.php`, `class-bhp-classification-completeness-gate.php`, `class-bhp-required-links-gate.php`) — no repo change was needed for this blocker. **Staging's live `functions.php` was missing all four requires**, even though the class files existed on disk. Verified via SSH: `BHP_Content_QA_Gate::evaluate()` guards every sub-checker with `class_exists()` (confirmed at 4 call sites), so this gap never caused a fatal — it just made the checks silently report as unavailable.

**Decision — deploy 2 of 4 now, defer 2:**

| Class | Relevance to CTA-engine safety | Included now? |
|---|---|---|
| `BHP_CTA_Collision_Detector` | Direct — the one mechanism meant to catch duplicate-CTA/promotional-link patterns at content-QA time | **Yes** |
| `BHP_Required_Links_Gate` | Direct — enforces the complementary in-body contextual-links policy that's explicitly documented as coexisting with the automatic CTA | **Yes** |
| `BHP_Content_HTML_Sanitizer` | None — checks migrated-HTML artifacts (Squarespace remnants, nested `<p>` tags), unrelated to CTA rendering | No — deferred to a future isolated QA release |
| `BHP_Classification_Completeness_Gate` | Indirect at most — checks classification-metadata completeness, not CTA duplication specifically | No — deferred to a future isolated QA release |

**Staging action taken:** uploaded a narrow patch to staging's live `functions.php` (12 added lines, verified via diff and checksum) that requires only `BHP_CTA_Collision_Detector` and `BHP_Required_Links_Gate`. Confirmed post-deploy via `wp eval`: both classes now `class_exists() === true`; the other two remain `false` as intended. No repo change was needed since the repo already reflects the eventual target state (all four wired) — this is a documented, deliberate staging/repo divergence pending the future QA release, not an oversight.

## Verification

- **51 assertions** in `tests/test-cta-engine.php` (36 pre-existing + 15 new), **all pass** on staging, covering all 8 required regression scenarios (valid id, invalid id, empty id, explicit-shortcode-suppresses-fallback, no-shortcode-still-renders, AI-generator-style draft duplicate-defect fix, registry-curated preservation, product-page hook preservation).
- **6 test suites total** run clean: CTA engine, CTA collision detector, required-links gate, content classification, taxonomy safety, classification completeness gate, content HTML sanitizer (the last two run as an extra safety check even though their classes aren't wired for this release).
- **PHP lint**: clean on all 4 touched files (functions.php, class-bhp-cta-engine.php, related-content.php, test-cta-engine.php).
- **Live curl verification** (staging): registry post renders curated block with zero CTA-engine markup; product page renders exactly one CTA-engine element (`adventure_kit_signup`) plus the separate, pre-existing, unrelated Amazon affiliate section; Complete Collection, Teacher, Adventure Kit, search results, and shop archive all show zero CTA-engine markup — confirming isolation holds after the fix.
- **Performance** (isolated measurement via `wp eval-file`): `BHP_CTA_Engine::render_for_post()` — 0.44ms, 0 additional DB queries. `related-content.php` non-registry fallback — 0.38ms, 0 additional queries. Negligible.
- **Not verified this session**: live browser interactive checks (keyboard nav, focus states, real mobile/tablet viewport rendering) — the Chrome browser tool was disconnected for this turn. Static/curl-based checks substituted where possible; disclosed as a genuine gap, not claimed as passing.

## Accessibility re-investigation (2026-07-12, later same day) — no code change

A follow-up task asked me to fix a reported focus-outline contrast defect: outline color `#7A4E2D` against the CTA's dark-gradient background (`#071522` / `#12271D`), reported at ~2.59:1 / ~2.22:1 — below the WCAG 1.4.11 3:1 minimum for UI-component focus indicators. That number came from the prior QA turn's contrast calculation (see "Not verified this session" note above, and the corresponding entry in the browser-QA report), which read `--color-focus`'s `:root` default (`--color-earth: #7A4E2D`) directly from the CSS source without tracing whether anything overrides it on the pages where the CTA actually renders.

**Before implementing the requested CSS change, I verified the live cascade instead of trusting that number, and found the premise was wrong:**

- `style.css` line ~3302 defines `body:not(.home) { --color-focus: var(--expedition-focus); ... }`, where `--expedition-focus: #d8bd7d` (line ~3297). Every real page a CTA renders on (blog posts, product pages, static pages) carries `body:not(.home)` — the exact same condition that triggers the CTA's dark gradient background in the first place (`body:not(.home) .final-cta { background: linear-gradient(...) }`). The two conditions are coupled: wherever the dark background applies, the gold-tan focus color also applies.
- Confirmed empirically on live staging (`/teachers/`, a real page using the same `.final-cta` template partial the CTA engine renders through): `getComputedStyle(document.body).getPropertyValue('--color-focus')` returns `#d8bd7d`, not `#7A4E2D`.
- Confirmed via genuine keyboard-driven `Tab` navigation (not just `.focus()`) that `:focus-visible` actually matches and the browser renders `outlineColor: rgb(216, 189, 125)` (`#d8bd7d`) — checked across all three button variants used inside `.final-cta` (`btn-primary`, `btn-secondary`, `btn-outline`), all three identical.
- Recalculated contrast with the real, live color:

| Pair | Ratio | WCAG 1.4.11 (3:1 min) |
|---|---|---|
| `#d8bd7d` vs `#071522` (navy) | **10.09:1** | Passes |
| `#d8bd7d` vs `#12271D` (jungle) | **8.62:1** | Passes |
| `#7A4E2D` vs `#071522` (the value the original finding used — not what's live) | 2.59:1 | Would fail, but does not apply |
| `#7A4E2D` vs `#12271D` (same) | 2.22:1 | Would fail, but does not apply |

**Conclusion: no defect exists on the live site. No CSS was changed.** The originally reported finding was a false positive caused by reading the wrong layer of the CSS cascade, not a real accessibility gap. I'm correcting the record here rather than implementing a change against a color that already passes comfortably — changing a working, high-contrast value would have been pure risk with no benefit, and would have made a real "before/after" claim about a fix that fixed nothing.

No files were touched, no commit was made, and no staging/production write occurred for this task.

## Rollback (staging)

Backups created before any staging write, timestamp `20260712-084826`:
- `functions.php.rollback-ctaengine-20260712-084826`
- `inc/class-bhp-cta-engine.php.rollback-ctaengine-20260712-084826`
- `template-parts/guides/related-content.php.rollback-ctaengine-20260712-084826`

To roll back: copy each `.rollback-ctaengine-20260712-084826` file back over its live counterpart, purge cache, re-run `wp eval 'echo "ok";'` to confirm no fatal.

## Isolated production release manifest (DEPLOYED 2026-07-12 — see production deployment record for exact drift/patch detail)

| File | Production destination | Status | Dependency | Test requirement |
|---|---|---|---|---|
| `inc/class-bhp-cta-engine.php` | (new file, theme not currently on production) | New file | `BHP_Content_Classification` | test-cta-engine.php |
| `inc/class-bhp-content-classification.php` | (new file) | New file | none (pairs with engine) | test-content-classification.php |
| `inc/class-bhp-cta-collision-detector.php` | (new file) | New file | none | test-cta-collision-detector.php |
| `inc/class-bhp-required-links-gate.php` | (new file) | New file | none | test-required-links-gate.php |
| `template-parts/guides/related-content.php` | (new file — production has no such template today) | New file | CTA engine | test-cta-engine.php |
| `template-parts/components/final-cta.php` | **Already present on production** (used by 5 static pages) | Verify compatible, do not treat as new | `bhp_get_safe_link_url()` (already in production's `functions.php`) | manual spot-check |
| `functions.php` | Live on production | **Narrow patch only** — not a full-file overwrite. Exact diff: 2 `add_action` hook registrations (product-page CTAs) + 4 `require_once` lines (classification, engine, collision-detector, required-links-gate) | none beyond the files above | `wp eval 'echo "ok";'` + full theme test suite |
| `assets/js/nav.js` | Live on production | **Diff required before assuming no-op** — confirm production's current copy already has the `data-bhp-event`/`data-bhp-impression-event` delegated handlers before treating this as unchanged | none | manual click-through |

**Explicitly excluded from this release:** `inc/class-bhp-content-html-sanitizer.php`, `inc/class-bhp-classification-completeness-gate.php` (deferred QA-pipeline tooling, not CTA runtime dependencies), `inc/class-bhp-campaign-landing.php`, `inc/class-bhp-conversion-scoring.php` (Phase 1D extensions, not required for baseline CTA rendering), `inc/class-bhp-blog-draft-generator.php` (content-generation tooling, separate from rendering).

**Rollback plan (production, if ever deployed):** production currently has zero CTA-engine footprint — rollback is: delete the new files, revert `functions.php` to its pre-deploy timestamped backup, purge SiteGround cache, confirm `wp eval 'echo "ok";'` and no fatals. No database migrations exist (only postmeta keys, inert if the reading code is removed).

**Cache requirement:** SiteGround dynamic cache purge after any deploy.
