# Release Record — CTA Engine (Production)

**Status:** Live on production since 2026-07-12 (isolated subset).

## What shipped
`inc/class-bhp-cta-engine.php`, `inc/class-bhp-content-classification.php`, `inc/class-bhp-cta-collision-detector.php`, `inc/class-bhp-required-links-gate.php`, plus patches to `template-parts/guides/related-content.php` and `template-parts/components/final-cta.php`, plus a narrow `functions.php` patch (4 `require_once` lines + the Adventure Kit product-page hook).

## What did NOT ship
The full Phase 1D/1E suite: `BHP_Campaign_Landing`, `BHP_Conversion_Scoring`, the content-intelligence engine (`class-bhp-content-inventory.php` and friends), `BHP_Content_HTML_Sanitizer`, `BHP_Classification_Completeness_Gate`, `BHP_Content_QA_Gate` — all remain staging-only by design.

## Blocker fixed
`BHP_CTA_Engine::shortcode()` never accepted an `id` attribute — `shortcode_atts()` silently discarded it, so an explicit `[bhp_contextual_cta id="..."]` always fell through to the generic classification-based fallback, producing duplicate CTAs on AI-generator-style drafts. Fixed by accepting `id`, resolving via `select_specific()`, and adding `has_shortcode()`-based duplicate-prevention in the end-of-article fallback.

## Production drift discovered and handled
`related-content.php` and `final-cta.php` already existed on production in pre-Phase-1D form — the original release manifest assumed they were new files. Diffed byte-for-byte, confirmed additive/backward-compatible, patched in place rather than installed as new files.

## Verification
51 automated assertions pass. Live-verified across 9 real production pages (1 non-registry post, 1 registry-curated post, 1 product page, 5 non-target pages) — exactly the expected CTA count on each, zero duplicates, zero console errors, keyboard focus confirmed with real Tab navigation (outline contrast 10.09:1/8.62:1, passes WCAG 1.4.11).

## Rollback
Timestamped backups on production: `*.rollback-ctaengine-PROD-20260712-042136` (functions.php, related-content.php, final-cta.php). To roll back: restore each, delete the 4 new `inc/` files, purge cache, confirm no fatal.

## Full detail
`docs/CTA_ENGINE_ISOLATED_RELEASE_2026-07-12.md` (blocker fix + staging QA), `docs/CTA_ENGINE_PRODUCTION_DEPLOYMENT_2026-07-12.md` (production deployment record).
