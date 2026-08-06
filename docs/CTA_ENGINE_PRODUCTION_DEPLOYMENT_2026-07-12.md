# CTA Engine — Isolated Production Deployment Record

**Date:** 2026-07-12
**Approved by:** Andrew Signore (explicit, current-turn authorization referencing `docs/CTA_ENGINE_ISOLATED_RELEASE_2026-07-12.md`)
**Scope:** Isolated CTA Engine + minimal QA-safety classes only. Full theme ZIP was **not** deployed — 6 files patched/added directly on production via the ZIP-deploy-equivalent narrow-patch process.

## Pre-deployment state

- Production identity confirmed: `https://braveheartspublishing.com`, active theme `brave-hearts-theme-deploy-explorer-expedition-guides` v1.19.4 (unchanged by this deploy — no CSS/JS asset version bump needed since no asset URLs changed).
- Production had **zero** CTA-engine footprint: no `inc/class-bhp-cta-*` or `class-bhp-content-classification.php` files, no related `require_once` lines in `functions.php`, no `bhp_woocommerce_product_adventure_kit_cta` hook.
- **Drift found vs. the release manifest's assumptions** (manifest corrected by this record, not by re-editing the manifest's original text):
  - `template-parts/guides/related-content.php` **already existed on production** in a pre-Phase-1D form (`if (!$post || !$data) { return; }` — no CTA-engine fallback, no duplicate-prevention). The manifest had called this a "new file." It was a **patch**, not a new-file creation. Diffed byte-for-byte against the repo version before deploying: identical except for the fallback branch.
  - `template-parts/components/final-cta.php` **already existed but was incompatible** — production's copy predated the Phase 1D `'attrs'` extension, so `BHP_CTA_Engine::render()`'s tracking attributes (`data-bhp-event`, `data-bhp-impression-event`, `data-bhp-cta-id`, etc.) would have been silently dropped. Verified the repo's version is additive/backward-compatible (existing callers that never set `'attrs'` render identically) before replacing it — required per the manifest's own conditional ("verify compatibility... unless replacement is required").
  - `assets/js/nav.js` **already had** the generic `data-bhp-event`/`data-bhp-impression-event` delegated click/impression handlers the CTA engine depends on. Per the manifest's conditional, **not replaced**.
  - Production's PHP files use **LF line endings**, not the repo's CRLF — all uploaded files were normalized to LF before transfer to preserve production's convention.

## Dependency verification (before wiring anything)

Confirmed via direct source read that `BHP_CTA_Collision_Detector` and `BHP_Required_Links_Gate` register no hooks of their own (no file-scope `add_action`/`add_filter`) — they are passive utility classes only ever invoked by `BHP_Content_QA_Gate::evaluate()`, which is intentionally **not** part of this release. Loading them without QA-Gate is inert/safe, satisfying "class loads" without activating any QA-gate behavior. Confirmed all functions the CTA engine and its templates call (`bhp_get_guide_registry`, `bhp_get_series_adventures`, `bhp_get_safe_link_url`, `bhp_get_amazon_affiliate_url`, `bhp_get_guide_hub_url`, `bhp_get_guide_post_data`, `bhp_get_guide_hubs`, `bhp_get_related_guide_posts`) already exist in production's `functions.php`.

## Files deployed

| File | Action | Verification |
|---|---|---|
| `inc/class-bhp-cta-engine.php` | New | md5 match local↔remote, `php -l` clean |
| `inc/class-bhp-content-classification.php` | New | md5 match, `php -l` clean |
| `inc/class-bhp-cta-collision-detector.php` | New | md5 match, `php -l` clean |
| `inc/class-bhp-required-links-gate.php` | New | md5 match, `php -l` clean |
| `template-parts/guides/related-content.php` | Patched (backup taken first) | md5 match, `php -l` clean |
| `template-parts/components/final-cta.php` | Patched (backup taken first) | md5 match, `php -l` clean |
| `functions.php` | Narrow patch: 4 `require_once` lines + 1 `add_action`/function block, both extracted verbatim from the tested repo file (not retyped) | `php -l` clean, `wp eval` no fatal |
| `assets/js/nav.js` | **Not touched** — handlers already present | N/A |

**Excluded, confirmed absent post-deploy:** `BHP_Content_HTML_Sanitizer`, `BHP_Classification_Completeness_Gate`, `BHP_Content_QA_Gate`, `BHP_Campaign_Landing`, `BHP_Conversion_Scoring` — none loaded.

## A build error caught and corrected before it shipped

The first `functions.php` patch attempt used a `sed` multi-line insertion that collapsed onto a single physical line — because the block started with a `//` comment, this would have **commented out all 4 `require_once` statements**, meaning `php -l` passed (a fully-commented line is valid syntax) but the classes would never have actually loaded. Caught by inspecting the inserted text before proceeding to the next step, immediately reverted from the pre-patch backup, and redone using a file-based `sed -r` insertion (which preserves real newlines) with content extracted verbatim from the repo rather than retyped. Verified correct via `grep -n` showing the require lines on their own lines, `php -l`, and a live `class_exists()` check for all four classes.

## Post-deployment verification

- `wp eval 'echo "ok";'` — no fatal, before and after each step.
- `class_exists()` check: `BHP_CTA_Engine`, `BHP_Content_Classification`, `BHP_CTA_Collision_Detector`, `BHP_Required_Links_Gate` all **LOADED**; `BHP_Content_HTML_Sanitizer`, `BHP_Classification_Completeness_Gate`, `BHP_Content_QA_Gate`, `BHP_Campaign_Landing`, `BHP_Conversion_Scoring` all **correctly absent**.
- `has_action('woocommerce_after_single_product_summary', 'bhp_woocommerce_product_adventure_kit_cta')` — **YES**.
- SiteGround dynamic cache purged (`wp sg purge`). No new entries in `php_errorlog` after deploy (tail showed only pre-existing, unrelated Bookvault/favicon entries from July 9 and July 11).

## Logged-out production QA (real browser, real pages — no test content created)

Production has exactly **one** published non-registry post (`amazon-rainforest-facts-for-kids`, ID 366) and **34** registry-curated posts; **no** post currently carries the explicit `[bhp_contextual_cta]` shortcode.

| Scenario | Page | Result |
|---|---|---|
| Non-registry post | `/blog/amazon-rainforest-facts-for-kids/` | Exactly 1 CTA (`adventure_kit_signup`), correct headline/button/destination, tracking attributes present, no duplicate IDs, no console errors |
| Registry-curated post | `/blog/science-books-for-kids-that-feel-like-adventures/` | Curated `.guide-continuation` block only, 0 CTA-engine elements — no duplicate |
| Explicit-shortcode article | — | **Not tested — no real production article uses the shortcode yet.** Reported as a gap rather than creating test content, per instruction. |
| Product page | `/product/.../the-mariana-trench-paperback/` | Exactly 1 CTA-engine element (`adventure_kit_signup`); separate, pre-existing Amazon-affiliate section confirmed distinct (no `data-bhp-cta-id`); Add to Cart present; no duplicate IDs; no console errors |
| Complete Collection | `/complete-collection/` | 0 CTA-engine elements |
| Teacher page | `/teachers/` | 0 CTA-engine elements (the page's own static `.final-cta` usage is unrelated hand-authored markup, not engine output) |
| Adventure Kit page | `/reluctant-reader-adventure-kit/` | 0 CTA-engine elements |
| Search results | `/?s=mariana` | 0 CTA-engine elements |
| Shop archive | `/shop/` | 0 CTA-engine elements |

**Keyboard/focus verification (real, not simulated):** genuine `Tab`-key navigation on the live non-registry post reached the CTA link; `:focus-visible` matched; rendered outline `rgb(216, 189, 125)` (`#d8bd7d`) — matching the accessibility finding already verified and closed earlier in this same session (10.09:1 / 8.62:1 against the CTA's dark background, passes WCAG 1.4.11).

## Remaining gap

No real production article currently uses the `[bhp_contextual_cta id="..."]` explicit-shortcode path — the fix for that exact scenario (the original blocker) is verified via the automated test suite (51 assertions, prior session) and the AI-generator-style staging fixture, but not yet exercised on a real production page simply because no such page exists yet. Recommend re-checking this once the first shortcode-bearing draft is published.

## Rollback

Timestamped backups on production (`*.rollback-ctaengine-PROD-20260712-042136`, same directory as each live file): `functions.php`, `template-parts/guides/related-content.php`, `template-parts/components/final-cta.php`. To roll back: copy each backup over its live counterpart, delete the 4 new `inc/class-bhp-cta-*`/`class-bhp-content-classification.php` files, purge cache, confirm `wp eval 'echo "ok";'`.

## Confirmation

Mailchimp, [PARENT_COUPON_CODE_SUPERSEDED], Complete Collection pricing/cart logic, WooCommerce pricing, GTM/GA4, analytics, navigation, and all Phase 1D/1E systems beyond the four approved classes were not touched by this deployment.
