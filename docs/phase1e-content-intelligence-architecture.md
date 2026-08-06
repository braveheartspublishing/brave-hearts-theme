# Phase 1E — Content Intelligence and Production Engine

**Status: built and tested on staging. Nothing published. No live
analytics credentials connected.** This is the single reference document
for Phase 1E; it replaces the need for 20 separate docs by covering
every workstream in one place, each section tagged with its actual
status (built / tested / staging-only / fixture-backed / manual-action-
required).

## 0. What this is, in one paragraph

Phase 1E connects the systems Phase 1D already built (content
classification, the CTA engine, campaign landing pages, conversion
scoring) to a new pipeline: import analytics → score content
opportunities → queue a recommendation → generate a structured brief →
generate a draft + SEO package + Pinterest variants → run a QA gate →
(only with explicit approval) create ONE WordPress staging draft →
evaluate published content later via the same analytics adapter. Every
step past "score and report" requires an explicit approving user, and
nothing in this system can publish a post or a GTM container.

## 1. Design principles actually enforced in code

| Principle | Where enforced |
|---|---|
| No live credentials required | `BHP_Analytics_Adapter` accepts CSV/JSON import + fixtures; no API client exists |
| Never fabricate facts/claims | `BHP_Content_Brief_Generator::detect_prohibited_claims()`, QA gate `unsupported_claim_risk` check, every brief field that would require invented content is an explicit `[PLACEHOLDER: ...]` string |
| Factual accuracy never auto-passed | `BHP_Content_QA_Gate::evaluate()` hard-codes `factual_accuracy` to `requires_human_review` regardless of every other check |
| No auto-publish | `BHP_WP_Draft_Workflow::create_draft()` only ever sets `post_status => 'draft'`; there is no method anywhere in this codebase that publishes a Phase-1E-generated post |
| Approval-gated transitions | `BHP_Content_Production_Queue::transition()` refuses `approved`/`ready_for_wp_draft`/`wp_draft_created`/`approved_for_publishing`/`published` without an explicit `$approved_by` argument (returns `WP_Error`, verified by test) |
| No bulk-edit of existing content | `BHP_Content_Inventory` and `BHP_SEO_Metadata_Package` only ever *read* existing Rank Math postmeta; no method writes metadata onto an existing published post |
| Expose confidence, not false precision | Every `BHP_Content_Opportunity_Engine` score returns `confidence` + `missing_signals` alongside the number |
| Extend, don't duplicate | Every new class in this phase depends on and calls the Phase 1D classes (`BHP_Content_Classification`, `BHP_CTA_Engine`, `BHP_Conversion_Scoring`) rather than re-implementing their logic |

## 2. New files (theme `inc/`, following the existing `class-bhp-*.php` convention)

| File | Workstream | Depends on |
|---|---|---|
| `inc/class-bhp-analytics-adapter.php` | 2 | — |
| `inc/class-bhp-content-inventory.php` | 1 | Classification, CTA registry, `bhp_get_guide_registry()`, Rank Math postmeta (read-only) |
| `inc/class-bhp-internal-link-engine.php` | 9 | Content Inventory |
| `inc/class-bhp-content-opportunity-engine.php` | 3 | Analytics Adapter |
| `inc/class-bhp-content-production-queue.php` | 4 | — |
| `inc/class-bhp-content-originality.php` | 6 | — |
| `inc/class-bhp-content-brief-generator.php` | 5 | Production Queue, CTA Engine, Internal Link Engine |
| `inc/class-bhp-seo-metadata-package.php` | 8 | — |
| `inc/class-bhp-blog-draft-generator.php` | 7 | — |
| `inc/class-bhp-pinterest-variant-generator.php` | 10 | Existing `content-engine/` schema |
| `inc/class-bhp-wp-draft-workflow.php` | 11 | Production Queue |
| `inc/class-bhp-content-qa-gate.php` | 12 | Conversion Scoring, Originality, Brief Generator |
| `inc/class-bhp-content-feedback-loop.php` | 13 | Analytics Adapter |
| `inc/class-bhp-content-engine-cli.php` | 14 | All of the above; guarded by `if (!WP_CLI) return;` |
| `inc/class-bhp-content-engine-admin.php` | 15 | All of the above (read-only summary page) |
| `tests/test-content-intelligence-engine.php` | 17 | — |

New content-engine artifacts:
- `content-engine/blogs/reluctant-reader-chapter-books/` — the Workstream 16 end-to-end example (`content-brief.json`, `draft-scaffold.json` [placeholder scaffold, never WP-eligible], `article-draft.json` [only if real prose has been assembled], `design-brief-generated.json`). **See §14a for the 2026-07-10 correction to this example.**
- `content-engine/reports/content-inventory.json` — a real export from the live staging inventory (64 items)
- `content-engine/reports/internal-link-report.json` — a real export of the internal-link recommendation report

## 3. Analytics adapter (Workstream 2) — built, tested, fixture-backed

Sources supported: `gsc`, `ga4`, `pinterest`, `woocommerce`. Import via
CSV or JSON (`BHP_Analytics_Adapter::import_csv()` /
`::import_json()`), or via `BHP_Analytics_Adapter::fixture_rows($source)`
for safe demo data. Each import is validated per-row
(`validate_row()`): a missing required field or malformed URL/date
rejects that row with a specific reason -- **no metric is ever silently
defaulted to zero**. Deduplication is by `(source, url_field, date,
query, variant)` within a batch. Storage is one private CPT post
(`bhp_analytics_import`) per import batch, postmeta holds the
normalized row JSON -- not a new database table, matching this
codebase's established storage convention.

**Live vs. fixture:** no source in this file is live. `wp bhp-content
import <source> <file>` accepts a real GSC/GA4/Pinterest export CSV the
moment Andrew has one; `wp bhp-content import-fixtures <source>` loads
built-in safe demo rows. Every batch is flagged `is_fixture` and every
downstream report (admin page, CLI output, feedback loop) surfaces that
flag explicitly -- fixture data is never presented as live.

## 4. Content inventory (Workstream 1) — built, tested, live-data-backed

`BHP_Content_Inventory::build()` reads (never writes) every published
`post`/`page`, joins Phase 1D classification + the guide registry +
Rank Math postmeta, and flags gaps: orphan pages, missing metadata,
missing classification, missing CTA alignment, and possible keyword
cannibalization (same non-empty primary keyword across 2+ items). Run
live against staging on 2026-07-10: **64 content items**, **33 orphan
pages**, **64 with at least one metadata gap**, **29 unclassified**, **0
cannibalization groups currently detected**. Full export at
`content-engine/reports/content-inventory.json`.

## 5. Content opportunity engine (Workstream 3) — built, tested

`BHP_Content_Opportunity_Engine::score_item()` computes a weighted 0–10
score across 10 factors (demand, CTR gap, trend, conversion potential,
business relevance, audience fit, funnel coverage, internal-link value,
cannibalization risk, content freshness). **Any factor with no
available data is excluded from both the weighted sum and its
denominator (never scored as zero), and `confidence` (high/medium/low)
reflects how much of the total weight was actually available.**
Recommendations are deterministic rule matches (never a black box), each
returned with a plain-text `reason`. Recommendation types match the
spec's list exactly (`create_new_article`, `refresh_existing_article`,
`improve_title_meta`, `strengthen_internal_links`, `add_contextual_cta`,
`add_lead_offer`, `add_product_block`, `create_teacher_variation`,
`create_parent_variation`, `create_pinterest_campaign`,
`consolidate_overlapping_articles`, `monitor`, `deprioritize`).

**Known, honest limitation:** with the GSC/GA4 fixture rows imported
during this session, several pages with *no* analytics data at all
scored numerically higher than one page *with* real fixture data,
because the weighted average renormalizes across a smaller available
weight. This is why `confidence` exists as a first-class, separately
displayed field -- **never sort or act on `score` without also checking
`confidence`.** The admin page and CLI output both show them side by
side for this reason.

## 6. Production queue (Workstream 4) — built, tested

Private CPT `bhp_content_queue`, same storage pattern as
`BHP_Lead_Event_Log`. 13 statuses exactly matching the spec. Every item
starts at `discovered` regardless of what the caller passes.
`transition()` refuses `approved` and every status after it without an
explicit `$approved_by` string (returns `WP_Error`, not a silent
no-op). `list_items()` is filterable (audience/funnel_stage/
content_intent/status), paginated, and hard-capped at 100 per page.
Admin: Tools → Content Queue. CLI: `wp bhp-content queue-list`, `wp
bhp-content queue-transition <id> <status> [--approved-by=<name>]`.

## 7. Brief generator + originality (Workstreams 5–6) — built, tested

`BHP_Content_Brief_Generator::generate($queue_id)` produces
`content-brief.json` (distinct from the pre-existing Pinterest
`design-brief.json` -- one describes what to *write*, the other what to
*pin*). Every field that would require invented knowledge is an
explicit `[PLACEHOLDER: ...]` string -- **the generator never
fabricates a reader problem, a unique angle, a citation, or a factual
claim.** `detect_prohibited_claims()` scans for
guarantee/clinically/proven-to/award-winning/limited-time/#1 language.

`BHP_Content_Originality` runs deterministic structural checks (generic
opening patterns, repetitive sentence-openers, keyword density,
duplicate 8-word phrases against existing published text, shallow-
listicle detection) -- **explicitly not an "AI detection" score**, per
the requirement to avoid that unreliable category of tool. A separate
manual-checklist method verifies a human confirmed at least 2 of the 12
originality options from the brief; this can never be satisfied by code
alone.

## 8. Blog draft + SEO + Pinterest pipeline (Workstreams 7–10) — built, tested

`BHP_Blog_Draft_Generator::generate($brief)` emits a **structural
scaffold only** -- WordPress block-compatible HTML
(`<!-- wp:heading -->`, `<!-- wp:paragraph -->`, etc.) with a CTA
shortcode marker resolved at render time by the existing
`BHP_CTA_Engine` (never duplicated). Every section body and every
factual claim is a `[PLACEHOLDER: ...]` marker requiring a human writer
-- this output is written to `draft-scaffold.json` and is never eligible
for WordPress draft creation (see §14a).

`BHP_Blog_Draft_Generator::assemble_article_draft($brief, $prose)` is the
separate method that produces the actual **article draft** from real
prose supplied by a human writer/editor (`$prose['opening_hook']`,
`$prose['sections'][]['heading'|'body']`, optional
`$prose['faq_answers']`). It refuses (`WP_Error`) if any supplied text
still contains a `[PLACEHOLDER:` marker, written to `article-draft.json`
-- only this artifact is accepted by `create-wp-draft`.

Both methods share block-builder helpers that are validated against the
real, currently-published Gutenberg markup on this exact WordPress
install (post ID 119, WP 7.0) via `validate_markup()`, which walks the
`parse_blocks()` tree and flags any block carrying raw `<li>` markup
outside a properly nested `core/list-item` child -- the defect class
that produced the invalid "unexpected or invalid content" recovery
prompt on post 460 (flat `<ul><li>` lists with no `wp:list-item`
wrapping). See §14a for the full incident record.

`BHP_SEO_Metadata_Package::generate()` produces the full metadata
package (title/description/slug/H1/outline/OG tags/Pinterest
title-description/image alt placeholders) and validates title/
description length, H1-vs-title duplication, and -- against the
existing inventory -- slug collision, duplicate title/description, and
primary-keyword cannibalization.

`BHP_Pinterest_Variant_Generator::generate()` produces the 4 required
creative hypotheses (problem-led/outcome-led/curiosity-led/resource-led)
targeting the exact same schema as the pre-existing
`content-engine/templates/design-brief.template.json` and validated with
the same rules `content-engine/scripts/validate-design-brief.php`
already enforces (4 distinct types, no duplicate headlines, correct
`utm_content` pattern, no prohibited claim language) -- re-implemented
as a pure PHP method so the QA gate can call it directly.

## 9. WordPress draft workflow (Workstream 11) — built, tested, staging-only

`BHP_WP_Draft_Workflow::create_draft()` requires an already-`approved`
queue item, an explicit `$approved_by` string, AND (added 2026-07-10, see
§14a) a `$qa_result` from `BHP_Content_QA_Gate::evaluate()` run against
the exact same draft. It refuses to insert anything if: unresolved
placeholder sections remain, the body still contains a
`[PLACEHOLDER:`-style internal editorial instruction, the content fails
`BHP_Blog_Draft_Generator::validate_markup()`, or the QA gate's
`overall_status` is `fail`/`revise`/`editorial_review_required`. It
always creates `post_status => 'draft'` with a unique slug (never
collides with an existing post) and a `_bhp_draft_provenance =
phase1e_generated` marker. `delete_synthetic_draft()` refuses to delete
anything that is not `draft` status AND not carrying that exact
provenance marker -- verified by test against a real published control
post, which it correctly refused to touch -- and now also rolls the
originating queue item back to `ready_for_wp_draft` on deletion, so a
rolled-back draft doesn't leave the queue falsely reporting
`wp_draft_created`.

## 10. QA gate (Workstream 12) — built, tested

`BHP_Content_QA_Gate::evaluate()` combines: SEO validation
(deterministic), Pinterest validation (deterministic), prohibited-claim
scan (deterministic), placeholder-resolution check (deterministic,
now also catches any `[PLACEHOLDER:` text even if the caller's
`$draft['placeholders']` array was empty), **Gutenberg markup validity
(deterministic, added 2026-07-10 -- see §14a)**, structural originality
(heuristic), cannibalization (heuristic), `BHP_Conversion_Scoring` reuse
(inferred), manual originality checklist (manual), **factual accuracy
(manual, never inferred -- becomes `pass` only when a named human is
passed in via `$editorial_confirmations['factual_accuracy']`, otherwise
permanently `requires_human_review`)**, and audience-fit editorial
judgment (manual, same explicit-human-confirmation pattern via
`$editorial_confirmations['audience_fit']`). Overall status is `fail` >
`editorial_review_required` > `revise` > `pass_for_wp_draft` in that
precedence order -- `pass_for_publishing_review` is never computed
automatically; it is a status an editor sets manually after this gate.

## 11. Feedback loop (Workstream 13) — built, tested, fixture-backed

`BHP_Content_Feedback_Loop::evaluate_url($url, $window_days)` looks up
imported analytics rows for a URL within a window and returns a
deterministic recommendation (`improve_title`, `change_cta`,
`add_lead_offer`, `create_more_pinterest_variants`,
`deprioritize_topic`, `keep_unchanged`, or `monitor_longer` when no data
exists). Every result carries `data_is_fixture` so a fixture-derived
recommendation is never mistaken for a real one.

## 12. Automation architecture (Workstream 14) — built

WP-CLI commands under `wp bhp-content ...`, matching the three safety
levels:

- **Level 1 (automatic):** `import`, `import-fixtures`, `inventory`,
  `opportunities`, `queue-list`, `feedback`, `link-report`,
  `inspect-draft` (read-only; §19.13)
- **Level 2 (automatic with review):** `generate-brief`,
  `generate-draft-package` (scaffold only, writes local JSON, supports
  `--dry-run`), `assemble-article-draft` (real prose in, `article-draft.json`
  out, local only, supports `--dry-run`)
- **Level 3 (explicit approval only):** `queue-transition` (for gated
  statuses), `create-wp-draft` (requires `--approved-by`,
  `--factual-review-confirmed-by`, `--audience-fit-confirmed-by`; builds
  and validates the full publishing package via
  `BHP_Draft_Package_Builder` before ever inserting the post -- see
  §19.14), `delete-draft` (rollback, also resets the queue item's status)

No cron jobs are registered in this phase -- the CLI commands above are
cron-safe (idempotent, no interactive prompts) but scheduling them is a
deliberate future decision, not made here, since "public publishing must
remain outside automatic execution" and none of these commands publish
anything regardless.

## 13. Admin interface (Workstream 15) — built

Tools → Content Intelligence: a new page (same pattern as the existing
"Lead Signups" page), NOT a second KPI/revenue dashboard -- the
commerce dashboard in
`plugins/brave-hearts-bundle-pricing/includes/dashboard/` is untouched.
Read-only except one nonce-protected "Refresh summary" button; summary
is cached via transient (15 minutes) to avoid recomputing the inventory
scan on every page load. Capability-gated to `edit_others_posts`.
Staging environments show an explicit notice that any imports may
include fixture data.

## 14. End-to-end example (Workstream 16) — run live on staging, 2026-07-10

**Topic:** "Chapter books for reluctant readers" -- chosen from
**fixture** GSC data (2,600 impressions, position 5.1, 5% CTR, growing)
representing a real content-gap pattern: strong keyword demand with no
dedicated existing page. *This opportunity is fixture-derived, not from
a live Search Console connection -- clearly labeled as such throughout.*

1. Queue item #459 added (`create_new_article`, audience=parent,
   funnel_stage=awareness, featured_book=mariana_trench,
   lead_offer=adventure_kit_parent).
2. Brief generated: `content-engine/blogs/reluctant-reader-chapter-books/content-brief.json`.
3. Draft package generated (SEO metadata + draft skeleton + 4 Pinterest
   variants) via `wp bhp-content generate-draft-package`.
4. QA gate result: **`editorial_review_required`** -- `seo_metadata`
   and `placeholders_resolved` came back `revise` (image alt text still
   placeholder; draft sections still placeholder, exactly as expected
   for a freshly generated skeleton), `factual_accuracy` and
   `audience_fit_editorial` are `requires_human_review` as always,
   everything else passed.
5. Queue item explicitly approved (`--approved-by="Andrew (Phase 1E
   demo, staging only)"`) and ONE WordPress staging draft created:
   **post ID 460**, `post_status = draft`. Confirmed returning HTTP 404
   to a logged-out public request at its would-be URL.
6. Monitoring plan: once real GSC/GA4 data exists for this topic (either
   after this draft is finished/published following normal editorial
   review, or for the existing related content), run `wp bhp-content
   feedback <url>` at the 7/28/90-day marks.

**This example is superseded by §14a below -- post 460 was correctly
identified by Andrew as an unacceptable output and has been deleted.**

## 14a. Correction: post 460 was not an acceptable draft (2026-07-10)

Andrew opened post 460 in the Gutenberg editor and got a "Block contains
unexpected or invalid content" recovery prompt, and correctly rejected
the whole premise: a placeholder-filled scaffold is not a usable
editorial draft, and step 5 above should never have created a WordPress
post from one in the first place.

**Root cause, verified against this exact WordPress install (WP 7.0),
not assumed:** queried a real, currently-published post (`ID 119`) for
its actual `post_content` and confirmed modern Gutenberg's `core/list`
block requires every `<li>` individually wrapped in its own
`<!-- wp:list-item -->...<!-- /wp:list-item -->` block. The old
`BHP_Blog_Draft_Generator::related_links_block()` emitted a flat
`<!-- wp:list --><ul><li>...</li></ul><!-- /wp:list -->` with no
`wp:list-item` nesting, and `fact_box()` embedded raw `<ul><li>` HTML
directly inside a `wp:group` block -- both invalid. Confirmed
structurally via `parse_blocks()`: a valid `core/list` block has
`innerBlocks` populated entirely with `core/list-item` children and
carries no raw `<li>` in its own `innerContent`; the broken output had
`innerBlocks = []` with the `<li>` markup sitting directly in the
block's own content. (PHP's `parse_blocks()`/`serialize_blocks()`
round-trip alone does **not** catch this -- it treats raw inner HTML as
opaque content; only inspecting the parsed block tree's `innerBlocks`
structure does.)

**A second, independent defect**, also raised by Andrew, is that
`create-wp-draft` never actually ran the QA gate before inserting the
post -- it only checked for a non-empty `--approved-by`. A draft
carrying unresolved `[PLACEHOLDER:` markers throughout its body could
reach `wp_insert_post()` regardless of QA status.

**Fix (this repo, same date):**
- `BHP_Blog_Draft_Generator::related_links_block()` and `fact_box()`
  rewritten to nest every `<li>` in its own `wp:list-item` block, matching
  the ground-truth markup above.
- Added `BHP_Blog_Draft_Generator::validate_markup( $html )` -- walks
  `parse_blocks()` recursively and flags any block with raw `<li>` in its
  own content outside `core/list-item`, or any `core/list` with no (or
  non-list-item) children.
- Split the generator's public contract in two: `generate()` now
  produces an explicitly-labeled scaffold only (written to
  `draft-scaffold.json`, never `draft.json`), and a new
  `assemble_article_draft( $brief, $prose )` is the only method that can
  produce WP-eligible content -- it refuses (`WP_Error`) if any supplied
  prose still contains a `[PLACEHOLDER:` marker.
- `BHP_WP_Draft_Workflow::create_draft()` now takes a required
  `$qa_result` argument and refuses creation if placeholders remain, the
  body contains editorial-instruction markers, `validate_markup()`
  returns any error, or `$qa_result['overall_status']` is
  `fail`/`revise`/`editorial_review_required`.
- `BHP_Content_QA_Gate::evaluate()` gained a `gutenberg_markup_valid`
  deterministic check and an `$editorial_confirmations` parameter so a
  named human can explicitly confirm factual accuracy / audience fit
  (never inferred by code -- see §10).
- `wp bhp-content create-wp-draft` now requires
  `--factual-review-confirmed-by=<name>` and
  `--audience-fit-confirmed-by=<name>` in addition to `--approved-by`,
  and reads `article-draft.json` (never the scaffold). New command
  `wp bhp-content assemble-article-draft <slug> <prose.json>` is the only
  path that produces that file.
- `BHP_WP_Draft_Workflow::delete_synthetic_draft()` now also rolls the
  originating queue item back to `ready_for_wp_draft` (was left stuck at
  `wp_draft_created`, which would have told future automation a draft
  already existed for this topic).
- Post 460 deleted via `delete_synthetic_draft()` after confirming its
  provenance (`is_phase1e_generated = true`, `queue_id = 459`, still
  `post_status = draft`); confirmed no other post was touched (full
  `post_modified`-ordered listing checked). Queue item #459 rolled back
  to `ready_for_wp_draft`.
- Regression tests added (§15) proving generated/assembled content always
  parses with zero `validate_markup()` errors, lists always use
  `wp:list`/`wp:list-item`, and `create_draft()` now refuses
  placeholder-laden or QA-uncleared content.
- No new WordPress draft was created as part of this fix -- per Andrew's
  explicit instruction, draft generation remains local/fixture-only until
  the generator passes all tests.

## 15. Testing (Workstream 17)

`tests/test-content-intelligence-engine.php` — **68 assertions, all
passing** on staging (`wp eval-file tests/test-content-intelligence-engine.php --user=1 --url=staging2.braveheartspublishing.com`).
Covers every class above: import validation/dedup/rejection, scoring
confidence/missing-data behavior, queue status transitions + approval
gating + pagination, brief required-fields + prohibited-claim
detection, SEO length/collision/cannibalization validation, originality
pattern detection, Pinterest 4-variant distinctness + UTM pattern
validation, draft-workflow draft-only/duplicate-slug/provenance-gated
deletion (including a real published control post it correctly refused
to delete), QA gate's permanent `requires_human_review` on factual
accuracy, and feedback-loop fixture-flagging. All fixtures created by
the test suite are deleted at the end of the run.

**Full regression run (existing suites), same session:**
`test-content-classification.php`, `test-cta-engine.php`,
`test-conversion-scoring.php`, `test-campaign-landing.php`,
`test-lead-event-log.php`, `test-gtm-loader.php`,
`test-analytics-phase1b.php`, `test-coupon-ui-restoration.php` — **all
pass, no regressions** from the functions.php changes or new class
loading.

**Limitations:** live-provider adapter behavior (real GSC/GA4/Pinterest
API responses) is untested because no live credentials exist; only the
CSV/JSON/fixture import paths are exercised. Admin-page rendering was
verified via PHP fatal-check only, not a full browser/accessibility
pass in this session (see Workstream 18 below).

## 16. Security / performance / accessibility (Workstream 18)

**Security:** every WP-CLI write command requires `--approved-by` for
gated actions; every admin write (the one "refresh summary" button) is
nonce-protected (`wp_nonce_field`/`check_admin_referer`); every echoed
value uses `esc_html`/`esc_attr`/`esc_url`; all new CPTs are
`show_ui => false`, `publicly_queryable => false`, `show_in_rest =>
false` (no REST exposure, no public archive); capability checks use
`edit_others_posts` (editor role) for the queue/admin views, matching
this codebase's existing pattern of `manage_options` for the most
sensitive admin pages and a lighter capability for editorial tooling;
CSV parsing uses `str_getcsv()` (no `eval`/formula execution risk); no
file path from user input is used directly in a filesystem call without
`sanitize_title()`/`sanitize_key()` first. No secrets are read, stored,
or logged anywhere in this phase (there are no API credentials to
handle yet).

**Performance:** `BHP_Content_Inventory::build()` is capped at 300 posts
by default; `BHP_Content_Production_Queue::list_items()` hard-caps
`per_page` at 100; the admin summary is cached via a 15-minute
transient rather than recomputed per page load; analytics row lookups
by URL (`get_rows_for_url()`) are the cheap path used by the
opportunity engine and feedback loop, while the more expensive
all-rows-for-a-source query (`get_rows()`) is capped at 50 import
batches.

**Accessibility:** admin tables use semantic `<table>`/`<thead>`/`<th>`
markup consistent with the existing Lead Signups page; the one form
control (refresh button) is a standard `<button>`, keyboard-operable by
default; status/error messages use WordPress's standard `.notice`
classes. Not separately screen-reader-tested this session (recommend a
manual pass before this admin page is used routinely).

## 17. Unresolved credentials / future integration steps

None of the following exist yet, and none are required for this
system to be useful today:

- **Google Search Console API** — future adapter would call
  `BHP_Analytics_Adapter::import_rows('gsc', $api_rows, [...])` with
  rows in the same normalized shape; no code changes needed downstream.
- **GA4 Data API** — same integration point, source `'ga4'`.
- **Pinterest API** — same integration point, source `'pinterest'`;
  Pinterest publishing itself remains entirely out of scope regardless
  (see Hard Safety Boundaries).
- **Scheduled cron execution** of the Level 1/2 CLI commands — a
  deliberate future decision once Andrew wants ongoing automatic
  imports/scoring; the commands are already cron-safe today.

## 18. Exact next actions for Andrew

**Superseded 2026-07-11 -- see §19 for the current state.** Post 460 was
deleted (§14a) and the full publishing-package expansion below now
governs how any future draft gets created; there is no pending example
draft to review right now.

1. Visit **Tools → Content Intelligence** in wp-admin (staging) to see
   the live inventory/opportunity/queue summary.
2. When you have a real Google Search Console or GA4 export, run `wp
   bhp-content import gsc /path/to/export.csv` (or `ga4`) to replace the
   fixture data with real signal, then re-run `wp bhp-content
   opportunities` to see recommendations grounded in real demand.
3. Decide whether/when to prioritize a live GSC/GA4/Pinterest API
   adapter -- not required to start using this system today.
4. When ready to produce a real article draft, the full path is now:
   `generate-brief` → a human writer supplies real prose →
   `assemble-article-draft` → `create-wp-draft` (which now requires
   `--factual-review-confirmed-by` and `--audience-fit-confirmed-by` in
   addition to `--approved-by`, and builds/validates the full
   taxonomy/SEO/classification/images/links/Pinterest/analytics/
   editorial package before ever calling `wp_insert_post()`). See §19.
5. Push the commits from this session via GitHub Desktop when ready
   (not done automatically, per your workflow).

## 19. Full publishing-package expansion (Workstreams 1–16, 2026-07-11)

Every field a WordPress draft carries beyond raw body content --
taxonomy, SEO, social, classification, images, internal links,
Pinterest, analytics, editorial governance -- is now assembled by
`BHP_Draft_Package_Builder::build()` and enforced by
`BHP_WP_Draft_Workflow::create_full_package_draft()`, which supersedes
the narrower `create_draft()` for all new work (that method remains only
because the original test suite already depends on its exact contract).

### 19.1 Taxonomy inventory (Workstream 1)

`BHP_Taxonomy_Inventory::build()` reads the REAL, live WordPress
taxonomy (verified 2026-07-11 against staging): **27 categories**
(flat, no hierarchy -- every category's `parent` is 0) and **~140 tags**,
plus per-post usage and whether Rank Math's primary-category feature is
enabled. Confirmed directly against `seo-by-rank-math` plugin source
(not assumed): `titles.pt_post_primary_taxonomy` = `"category"` on this
site, so the feature IS active for the `post` post type, and the real
postmeta key is `rank_math_primary_category` (confirmed via
`includes/admin/importers/class-seopress.php`'s literal
`update_post_meta()` call and `includes/class-common.php`'s
`get_primary_term()` construction of `"primary_{$taxonomy}"`). One stray
empty term exists (`null - null`, 0 posts) -- the assignment engine
skips any category with `post_count === 0`, so it (and `Uncategorized`)
can never be selected. This class never creates, renames, or deletes a
term.

### 19.2–19.3 Category + tag assignment (Workstreads 2–3)

`BHP_Taxonomy_Assignment_Engine::assign()` scores every REAL existing
category/tag by word-overlap against the brief's primary/secondary
keywords, featured book, audience, and content intent -- **never** a
hardcoded, plausible-sounding name. This directly replaces the old
`BHP_SEO_Metadata_Package::infer_category()`, which returned category
names like `"Reading & Growth"` and `"Science & Geography"` that do not
exist anywhere in this site's real taxonomy; that method has been
deleted. When no existing term scores above the match threshold, the
field is left at `0`/empty and `taxonomy_approval_status` is set to
`approval_required` with a `category_recommendation` -- the engine never
calls `wp_insert_term()`. Tags are capped at 8, deduplicated (including
singular/plural near-duplicates like "reluctant reader" vs "reluctant
readers"), and every rejected candidate is recorded with a reason.

### 19.4 Core WordPress fields (Workstream 4)

Assembled in `BHP_Draft_Package_Builder::build()`'s `core` key: title,
proposed slug, excerpt, author (defaults to the current CLI user),
post type, post format, parent page, content version. Enforced by
`validate_complete()`: unresolved title, slug collision (checked against
`get_page_by_path()` at gate time, not just at insert time), missing
excerpt, and invalid/missing author all block.

### 19.5 SEO metadata → real Rank Math fields (Workstream 5)

`BHP_SEO_Metadata_Package::to_rank_math_postmeta()` maps the package to
the REAL Rank Math postmeta keys, verified directly against
`seo-by-rank-math/includes/admin/importers/class-yoast.php`'s
field-mapping table (not assumed from general SEO-plugin convention):

| Package field | Real Rank Math postmeta key |
| --- | --- |
| seo_title | `rank_math_title` |
| meta_description | `rank_math_description` |
| primary_keyword | `rank_math_focus_keyword` |
| canonical_recommendation | `rank_math_canonical_url` |
| breadcrumb_title | `rank_math_breadcrumb_title` |
| open_graph_title | `rank_math_facebook_title` (Rank Math's own field name -- not "opengraph") |
| open_graph_description | `rank_math_facebook_description` |
| twitter_title | `rank_math_twitter_title` |
| twitter_description | `rank_math_twitter_description` |
| primary category ID | `rank_math_primary_category` |

Schema type: this site's Rank Math setting
`titles.pt_post_default_rich_snippet` is already `"article"` for the
`post` post type -- confirmed no existing published post carries any
`rank_math_schema_*` postmeta, so the site-wide default already applies
automatically; the package records this as an informational
`schema_type_recommendation` rather than writing redundant postmeta.
**Known limitation:** FAQ schema requires Rank Math's own FAQ Gutenberg
block (`rank-math/faq-block`) in the body; the article-draft assembler
does not yet emit that block type, so FAQ content is currently plain
heading/paragraph pairs without FAQ schema -- documented, not silently
claimed as done. `to_rank_math_postmeta()` never writes a literal
`"[PLACEHOLDER"` string into a real postmeta value.

### 19.6 Classification metadata (Workstream 6)

Reuses `BHP_Content_Classification`'s own postmeta keys
(`_bhp_content_audience`, `_bhp_content_funnel_stage`,
`_bhp_content_intent`, `_bhp_content_lead_offer`,
`_bhp_content_featured_book`) and `BHP_WP_Draft_Workflow`'s existing
`_bhp_content_primary_goal` for the primary CTA -- no duplicate parallel
keys. New fields not previously tracked anywhere
(`_bhp_draft_campaign_id`, `_bhp_draft_brief_id`,
`_bhp_draft_opportunity_id`, `_bhp_draft_qa_status`,
`_bhp_draft_originality_status`, `_bhp_draft_content_version`) are new,
draft-package-specific constants on `BHP_WP_Draft_Workflow`.

### 19.7 Images (Workstream 7)

`BHP_Image_Metadata_Package` defines an explicit 4-value status enum
(`complete`, `pending_generation`, `pending_upload`, `not_required`) --
there is no silent missing state. `status=complete` requires both a
real `attachment_id` and non-placeholder `alt_text`, or validation
flags it.

### 19.8 Internal linking (Workstream 8)

`BHP_Internal_Link_Engine::validate_body_links()` resolves every
same-host `<a href>` actually inserted into the body via
`url_to_postid()` against the REAL live site (not the inventory cache),
so a genuinely broken link is always caught. Recommendation fields
(inbound/outbound/hub/related/product/lead-offer links) remain
unresolved-but-visible outside the public body when not yet finalized --
only inserted, broken links block draft creation.

### 19.9 Pinterest linkage (Workstream 9)

`BHP_Pinterest_Draft_Linkage::build()` wraps
`BHP_Pinterest_Variant_Generator`'s existing 4-variant output (never
regenerates or duplicates the creative concepts) into the draft's
package, always recording `publishing_status: not_published`.

### 19.10 Analytics/attribution (Workstream 10)

`BHP_Analytics_Metadata_Package` persists only IDs/labels needed for
`BHP_Content_Feedback_Loop` to evaluate this content later, and actively
scans every string value for email- and phone-number-shaped patterns
(`validate()`) as a safety net against PII -- not just a naming
convention.

### 19.11 Editorial governance (Workstream 11)

`BHP_Editorial_Governance::build()` deliberately does NOT re-collect
reviewer names -- `factual_reviewer` and `audience_fit_reviewer` are
read directly from `BHP_Content_QA_Gate::evaluate()`'s own
`factual_accuracy`/`audience_fit_editorial` checks, so there is exactly
one place a human confirmation is recorded, never two copies that could
drift apart.

### 19.12 Admin panel (Workstream 12)

`BHP_Draft_Package_Admin_Panel` adds a read-only meta box ("Phase 1E
Content Package") on the post edit screen, gated to `edit_others_posts`,
rendering only for posts carrying the Phase 1E provenance marker. Never
renders on the front end (meta boxes only exist in `wp-admin`).

### 19.13 CLI inspection (Workstream 13)

`wp bhp-content inspect-draft <post_id> [--format=json]` prints every
section of the package plus a computed `missing_fields` list and
`would_currently_pass_full_gate` boolean.

### 19.14 Strict full-package gate (Workstream 14)

`BHP_Draft_Package_Builder::validate_complete()` is the single
exhaustive gate -- roughly 30 independent checks, each returning
`array('field' => ..., 'reason' => ...)`. `create_full_package_draft()`
calls `wp_insert_post()` only when this returns an empty array; the
`WP_Error` returned on refusal carries the full issues list via
`get_error_data()` for a genuine field-by-field blocking report.

### 19.15 Tests (Workstream 15)

`tests/test-draft-package.php` -- new, dedicated suite (kept separate
from the already-100-assertion `test-content-intelligence-engine.php`
for readability). Covers every category Andrew specified: taxonomy
(primary/secondary assignment, no-Uncategorized, near-duplicate tag
rejection, no-match approval_required path, zero taxonomy-count drift
across the whole run), SEO (real Rank Math key mapping, collision/
cannibalization), classification, images, internal links (real broken-
vs-working URL resolution), Pinterest (4-variant linkage, UTM, no
duplicates), analytics (content ID, PII detection), the full gate
(each missing field individually verified to block, a complete package
verified to succeed, an incomplete package verified to never reach
`wp_insert_post()`), and rollback (provenance-gated deletion, queue
reset, the real taxonomy term proven byte-identical before/after
deletion, an unrelated real post proven untouched).

### 19.16 Field matrix

| Field | Generated by | Stored in WordPress | Stored in content-engine package | Visible in admin panel | Validated | Approval required | N/A |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Primary category | Taxonomy Assignment Engine | `wp_set_post_terms` + `rank_math_primary_category` | taxonomy.primary_category_id | ✓ | ✓ | only if no match | |
| Secondary categories | Taxonomy Assignment Engine | `wp_set_post_terms` | taxonomy.secondary_category_ids | ✓ | ✓ | | |
| Tags | Taxonomy Assignment Engine | `wp_set_post_terms` | taxonomy.tag_ids | ✓ | ✓ | only if new tag proposed | |
| SEO title/description/focus keyword | SEO Metadata Package | `rank_math_title`/`_description`/`_focus_keyword` | seo.* | ✓ | ✓ | | |
| Canonical/robots/schema type | SEO Metadata Package | canonical: `rank_math_canonical_url`; robots/schema: site default, no postmeta needed | seo.* | ✓ | ✓ | | |
| OG/Twitter title+description | SEO Metadata Package | `rank_math_facebook_*`/`rank_math_twitter_*` | seo.* | ✓ | ✓ | | |
| OG/Twitter image | Image Metadata Package | not yet (no attachment created by this system) | images.* | ✓ | ✓ | ✓ | |
| Audience/funnel/intent/lead offer/featured book | Content Classification (reused) | `_bhp_content_*` | classification.* | ✓ | ✓ | | |
| Primary/secondary CTA | Draft Workflow (reused key) | `_bhp_content_primary_goal`/`_secondary_goal` | classification.* | ✓ | ✓ | | |
| Campaign/brief/opportunity ID | Draft Package Builder | `_bhp_draft_campaign_id`/`_brief_id`/`_opportunity_id` | classification.* | ✓ | | | |
| QA/originality status | QA Gate / Draft Package Builder | `_bhp_draft_qa_status`/`_originality_status` | classification.* | ✓ | ✓ | | |
| Featured/inline image status+alt | Image Metadata Package | not yet (metadata only, no real upload) | images.* | ✓ | ✓ | ✓ (upload/generation) | |
| Internal links (inserted) | Internal Link Engine | in `post_content` | internal_links.* | ✓ | ✓ (broken-link check) | | |
| Internal links (recommended) | Internal Link Engine | not inserted | internal_links.* | ✓ | | ✓ (editor picks) | |
| Pinterest 4 variants | Pinterest Variant Generator / Draft Linkage | `_bhp_draft_pinterest_linkage` (JSON) | pinterest.* | ✓ | ✓ | ✓ (publishing) | |
| Analytics content/campaign ID, expected events | Analytics Metadata Package | `_bhp_draft_analytics_metadata` (JSON) | analytics.* | ✓ | ✓ (PII scan) | | |
| Sources/claims/reviewer/notes | Editorial Governance | `_bhp_draft_editorial_governance` (JSON) | editorial.* | ✓ | ✓ | ✓ (human review) | |
| FAQ schema | -- | -- | -- | -- | -- | -- | ✓ (documented limitation, not implemented) |
| Rollback identifier | Editorial Governance | part of `_bhp_draft_editorial_governance` | editorial.rollback_identifier | ✓ | | | currently always empty -- no rollback-versioning scheme exists yet beyond delete_synthetic_draft() |

## 20. Author Fingerprint integration (canonical brand/founder corpus, 2026-07-11)

Every article now requires grounding in Brave Hearts' real brand identity
and Andrew's real history -- sourced from a SEPARATE, independent
repository (`C:\BHP\brave-hearts-seo-engine`), never duplicated into this
theme repo, never exposing manuscript text through WordPress.

### 20.1 Integration architecture

`brave-hearts-seo-engine` already had a substantial, real Author
Fingerprint system before this integration: `corpus/` (registry, gate,
retrieval), `authorship/` (`seed_anecdotes.py` -- 9 curated, source-cited
anecdotes; `fingerprint_check.py` -- prohibited-claim + reuse-repetition
checks), `briefs/`, `packets/`. This pass added the missing piece: a
one-way, structured JSON export (`bhp-seo package export-approved`) that
crosses into this repo, and the WordPress-side consumer
(`BHP_Author_Fingerprint_Package`). The boundary is intentionally narrow:

```
seo-engine: packet approve -> brief approve -> package export-approved
                                                        |
                                              JSON file (schema_version: 1)
                                                        v
theme: wp bhp-content import-approved-package --dry-run
       wp bhp-content validate-package <uuid>
       wp bhp-content create-wp-draft ... --author-package-uuid=<uuid>
```

WordPress never parses a DOCX, never queries the seo-engine's SQLite
database, and never receives a raw manuscript excerpt -- see §20.6.

### 20.2 Canonical sources referenced (by ID/status only)

| Source ID | Title | Status |
| --- | --- | --- |
| SRC-001 | Brave Hearts Publishing -- Brand Skill | canonical |
| SRC-002 | Andrew Signore's founder/life story | canonical |
| SRC-003 | Adventures of Charlotte and Henry -- Volume I (Mariana Trench) | canonical |
| SRC-004 | Adventures of Charlotte and Henry -- Volume II (Mount Everest) | canonical_provisional -- pending Andrew's confirmation (see brave-hearts-seo-engine's docs/canonical_source_policy.md) |

WordPress only ever sees this table (ID/title/canonical_status/checksum)
via `corpus_manifest` -- never the underlying files.

### 20.3 Brand voice profile

Paraphrased from SRC-001, not invented and not a verbatim reproduction:
warm/direct/genuine/occasionally-dry-funny/stoic-about-hardship tone;
short direct sentences, "farmers market" register; forbidden patterns
(listicle titles, generic "reading is important" messaging, guarantee/
hype language, performative exclamation-point enthusiasm, corporate
throat-clearing). `BHP_Author_Fingerprint_Package::check_brand_voice()`
is a PHP-side heuristic mirroring `brave-hearts-seo-engine`'s
`authorship/brand_voice.py::check_brand_voice()` -- the pattern lists
are kept in sync by hand across the two languages/repos; if one is
updated, update the other.

### 20.4 Author Connection

Every content brief already carried this section (built by the seo-engine
side). The exported package's `author_connection` field carries the
chosen anecdote's `full_text` (meant to be reused -- that is its purpose),
`source_passage`, `verification_state`, `reuse_count`, and
`prior_uses`. `BHP_Author_Fingerprint_Package::validate_for_draft_gate()`
requires: an anecdote is present, it has a recorded source, and its
`reuse_count` does not exceed `MAX_REUSE_COUNT` (3).

### 20.5 Author Fingerprint Check

The exported `author_fingerprint_check.passed` value must be `true` --
mirrors `brave-hearts-seo-engine`'s own
`authorship/fingerprint_check.py::check_author_fingerprint()`, which
blocks known-prohibited claims (naming a specific Himalayan peak/route/
altitude, "Bob Banks" as a location, profit-goal references, "just an
author") and flags anecdote overuse within a repetition window. WordPress
does not re-derive this result -- it trusts the seo-engine's own
computation (recorded at export time) and refuses the draft if it wasn't
passing, rather than re-implementing prohibited-claim detection from
scratch a second time.

### 20.6 Manuscript grounding -- honest limitation, not invented

No structured book-fact registry (volume/chapter/scene/character/
location/theme) exists yet in `brave-hearts-seo-engine`. The exported
package's `book_corpus_grounding.status` is always
`"not_yet_populated"` with an explanatory note. This theme's pipeline
does not invent scene, dialogue, or character data to fill the gap --
that would violate the explicit "do not invent scenes/dialogue/facts"
requirement. Building a real registry (a human reading the actual
manuscripts and recording verified facts) is separate future work.

### 20.7 Draft-gate additions

`BHP_Draft_Package_Builder::validate_complete()` now includes (all
mandatory, none bypassable):
- `author_package.missing` if no package was supplied at all
- Everything `BHP_Author_Fingerprint_Package::validate_for_draft_gate()`
  returns (corpus-source completeness per mandatory key, Author
  Connection completeness, Fingerprint Check pass, reuse threshold,
  brand voice profile presence)
- `author_package.brand_voice_check` -- the article body itself run
  through `check_brand_voice()`

`wp bhp-content create-wp-draft` now requires `--author-package-uuid=<uuid>`
in addition to the existing `--approved-by`/`--factual-review-confirmed-by`/
`--audience-fit-confirmed-by` flags -- refuses immediately if omitted or if
the UUID doesn't resolve to a previously-imported package.

### 20.8 Admin/CLI additions

- Admin meta box: new "Author Fingerprint (brand/founder grounding)"
  section -- package UUID, corpus status per mandatory key, anecdote
  used + relevance tags + reuse count, Fingerprint Check pass/fail,
  any unsupported-claim/overused-anecdote warnings. Never displays
  manuscript text (none is ever stored).
- `wp bhp-content import-approved-package <file> [--dry-run]` -- schema/
  checksum-shape/corpus-completeness validation, writes a local JSON
  copy under `content-engine/author-packages/` (never committed --
  same gitignore posture as other content-engine build artifacts).
- `wp bhp-content validate-package <uuid>` -- read-only re-check against
  the full draft-gate criteria.
- `wp bhp-content inspect-draft` -- extended with the same Author
  Fingerprint fields for an already-created draft.

**Checksum verification scope, stated plainly:** this integration does
NOT attempt to bit-for-bit recompute the Python-side sha256 in PHP --
canonicalizing JSON identically across `json.dumps`/`json_encode`
(unicode escaping, forward-slash escaping, recursive key ordering) is
genuinely fragile cross-language. What IS verified: the field's shape
(64-hex-char), and a PHP-computed hash of the raw imported file's own
bytes, stored locally to detect the file changing after import. This
tradeoff is documented rather than presented as stronger than it is.

### 20.9 Field matrix addition

| Field | Generated by | Stored in WordPress | Stored in content-engine package | Visible in admin panel | Validated | Approval required | N/A |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Author Fingerprint package UUID | seo-engine export | `_bhp_draft_author_package_uuid` | author_package.package_uuid | ✓ | ✓ (schema) | | |
| Corpus manifest (source IDs/status/checksum) | seo-engine export | part of `_bhp_draft_author_fingerprint_package` (JSON) | author_package.corpus_manifest | ✓ | ✓ (4 mandatory keys present) | | |
| Brand voice profile | seo-engine (SRC-001) | part of same JSON blob | author_package.brand_voice_profile | ✓ | ✓ (heuristic check) | | |
| Author Connection anecdote | seo-engine (author_anecdotes) | part of same JSON blob | author_package.author_connection | ✓ | ✓ (source + reuse threshold) | ✓ (Andrew, in the seo-engine repo, via brief approve) | |
| Author Fingerprint Check result | seo-engine (fingerprint_check.py) | part of same JSON blob | author_package.author_fingerprint_check | ✓ | ✓ (trusted from export, not re-derived) | | |
| Book-fact grounding (volume/chapter/scene) | -- | -- | -- | -- | -- | -- | ✓ (honest gap -- no registry exists yet) |

### 20.10 Andrew's exact review process for this handoff

1. In `brave-hearts-seo-engine`: approve a research packet (`bhp-seo
   packet approve <id> --by "Andrew Signore"`), then approve the
   resulting brief (`bhp-seo brief approve <id> --by "Andrew Signore"`).
2. Export the approved package: `bhp-seo package export-approved
   --brief-id <id> --output <path-into-this-repo-or-elsewhere>`.
3. In this repo: `wp bhp-content import-approved-package <file>
   --dry-run` to check it's schema-valid, then without `--dry-run` to
   actually import it, then `wp bhp-content validate-package <uuid>` to
   confirm it clears every gate check.
4. Only then does `wp bhp-content create-wp-draft ...
   --author-package-uuid=<uuid>` become possible -- it still separately
   requires `--factual-review-confirmed-by` and
   `--audience-fit-confirmed-by`, which are WordPress-side confirmations
   distinct from the seo-engine's own brief approval.

No WordPress draft or article was created as part of this integration
pass -- all new code is covered by
`tests/test-author-fingerprint-package.php` (safe, synthetic packages
only) and the updated `tests/test-draft-package.php`.

## 21. Weekly Production Engine (upstream, 2026-07-11)

The supervised weekly batch workflow (live GSC analysis -> persistent
slate -> per-item approval -> Author Connection selection -> article
review -> Pinterest/design handoff -> monitoring) lives entirely in
`brave-hearts-seo-engine` (`weekly/` package + `bhp-seo weekly ...`
commands) -- see that repo's `docs/weekly_production_engine.md` for the
full Day 1-3 procedure. Nothing changed on the WordPress side for this
pass; the existing Phase 1E pipeline (`generate-brief` ->
`assemble-article-draft` -> `create-wp-draft` with its own factual-
review/audience-fit/Author-Fingerprint gates) is the consumer once an
article clears the weekly engine's own article-approval gate. Approving
an article in the weekly engine does NOT bypass any WordPress-side gate
-- both approval chains are independent and both must clear before
`wp_insert_post()` runs.
