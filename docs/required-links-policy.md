# Required Contextual Links Policy (added 2026-07-11)

Every Brave Hearts blog article must contain, in the article body itself:

1. **At least one contextual link to the relevant main-topic hub** —
   a category archive, tag archive, collection page, or a curated
   resource/hub page (e.g. the Mariana Trench or Bridge Books hub
   pages). Chosen from *existing* live pages only — never invent a URL.
2. **At least one contextual book-discovery link** — to a relevant
   Brave Hearts book / neutral format-selection (product-category)
   page, the `/books/` series collection page, `/shop/`,
   `/complete-collection/`, or an approved Amazon affiliate link.

**The automatic end-of-article CTA (`related-content.php` →
`BHP_CTA_Engine::render_for_post(..., 'blog_end_of_article')`) does not
satisfy either requirement by itself.** It is a distinct, separate
conversion element from an in-body contextual link.

## What counts as "contextual"

A link embedded in a real sentence, with genuine surrounding prose in
the same paragraph (not just the link itself) — descriptive, natural
anchor text, not an imperative/promotional phrase ("Buy Now," "Shop
Now," "Get Your Copy," etc.), and not the sole content of its own
paragraph. See `BHP_CTA_Collision_Detector::detect_contextual_links()`
and `PROMOTIONAL_ANCHOR_PHRASES` for the exact rules.

A link that IS promotional in shape (a real CTA-engine render, the
`final-cta` class, imperative anchor text, or a link alone in its own
paragraph) is a manual CTA, not a contextual link, and is evaluated by
the existing collision logic instead (see
`docs/weekly-cycle-1-qa-failure-audit.md`, defect #12).

## Enforcement

`BHP_Required_Links_Gate::check( $post_id, $post_content )` — wired
into `BHP_Content_QA_Gate::evaluate()` as the `required_links` check.
Fails the package unless both link classes are present, **or** Andrew
explicitly marks a class not applicable via postmeta:

- `_bhp_required_links_override` = `'topic_hub'` | `'book_link'` | `'both'`
- `_bhp_required_links_override_reason` = free-text reason (required for
  the override to be meaningful in QA reports, though not itself
  enforced as non-empty by the gate)

An overridden post reports gate state `'overridden'`, which counts as
passing for `BHP_Content_QA_Gate`'s overall status, but stays visible
in the detail payload so the override and its reason are never silently
lost.

## Workflow additions

Every future article package must also verify and record:
- **Exact destination verification** — the chosen topic-hub and book
  URLs are real, live, non-invented pages.
- **Link-status verification** — both destinations return HTTP 200 at
  package-creation time.
- **Internal-link readback after WordPress creation** — re-parse the
  saved `post_content` and confirm both required links are present with
  the correct `href` and anchor text, not just that they were written.

## Change history

- **2026-07-11**: Policy introduced. `BHP_CTA_Collision_Detector`'s
  `MANUAL_CTA_SIGNATURES` was corrected at the same time — the original
  version flagged any `product-category/*` URL as a manual CTA purely
  by destination, which would have wrongly rejected the now-required
  contextual book links. Detection is now structural (promotional
  phrasing / CTA-engine markup / isolated-paragraph shape), not
  destination-based. See `inc/class-bhp-cta-collision-detector.php` for
  the corrected logic and `tests/test-cta-collision-detector.php` for
  the updated regression coverage (including the corrected expectation
  for the historical post-546 fixture, which used to assert
  `collision_state === 'duplicate'` and now correctly asserts
  `'none'` with `contextual_book_link_present === true`).
