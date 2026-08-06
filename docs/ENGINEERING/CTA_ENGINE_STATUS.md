# CTA Engine Status

**Isolated subset live on production since 2026-07-12.** Full detail: `docs/RELEASES/CTA_ENGINE_PRODUCTION.md`.

## Architecture
Hardcoded PHP array registry (7 entries: `adventure_kit_signup`, `book_page`, `collection_paperback`, `collection_hardcover`, `amazon_listing`, `teacher_resource`, `related_content`) with `resolve_url()` closures per entry. `select_cta()` does deterministic scoring (audience=3pts, funnel_stage=2pts, intent=2pts, ties broken by registry array order). `select_specific($id, $context)` does direct lookup for editorially-fixed placements (e.g., the Adventure Kit cross-sell on every product page). Renders through the existing `template-parts/components/final-cta.php` partial — no new markup/CSS.

## What's live on production
`BHP_CTA_Engine`, `BHP_Content_Classification`, `BHP_CTA_Collision_Detector`, `BHP_Required_Links_Gate`.

## What's staging-only
`BHP_Campaign_Landing`, `BHP_Conversion_Scoring`, the content-intelligence engine, `BHP_Content_HTML_Sanitizer`, `BHP_Classification_Completeness_Gate`, `BHP_Content_QA_Gate`.

## Duplicate-CTA prevention
`related-content.php`'s automatic end-of-article fallback checks `has_shortcode($post->post_content, 'bhp_contextual_cta')` before rendering — a post with an explicit shortcode never also gets the automatic fallback.

## Known limitation
No real production article currently uses the `[bhp_contextual_cta id="..."]` explicit-shortcode path — verified via the automated test suite instead of a live production page, since none exists yet.
