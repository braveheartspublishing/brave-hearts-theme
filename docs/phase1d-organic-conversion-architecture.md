# Phase 1D — Organic Content-to-Lead and Content-to-Sale Conversion Architecture

Master reference for everything built in Phase 1D. Companion docs:
`docs/event-dictionary.md` (events), `docs/gtm-implementation-manifest.json`
(machine-readable event/DLV list), `docs/phase1d-pinterest-workflow.md`,
`docs/phase1d-conversion-scoring-sample-run.md`,
`docs/phase1d-security-performance-a11y-review.md`.

## 1. Architecture overview

Phase 1D added a layered, config-driven conversion system on top of the
existing site, reusing existing components wherever they already
existed rather than duplicating them:

```
BHP_Content_Classification   (what is this content, who is it for, what stage)
        |
        v
BHP_CTA_Engine                (given classification -> which CTA, rendered
        |                       through the EXISTING final-cta.php /
        |                       teacher-resources-cta.php templates)
        v
nav.js generic data-bhp-*      (CTA/impression/focus events -> dataLayer,
event mechanism                 same mechanism used since Phase 1B)
        |
        v
BHP_Conversion_Scoring          (report-only: how conversion-ready is a
                                 given page, using the classification +
                                 CTA engine + rendered HTML)

BHP_Campaign_Landing             (a separate, parallel config-driven page
                                  framework, composing the SAME components
                                  above into a full landing page)

content-engine/                  (pre-existing, separately-scoped Pinterest
                                  pin-creative pipeline -- Phase 1D populated
                                  its first real example, did not redesign it)
```

## 2. Funnel map (as inventoried, Workstream 1)

- **Entry points**: organic blog posts (35, all currently guide-registry
  curated), `/teachers/` resource hub, homepage, product/collection
  pages, Amazon listings (outbound only).
- **Mid-funnel**: `template-parts/guides/related-content.php` (guide
  continuation for registry posts, CTA-engine fallback for everything
  else), the new Adventure Kit product-page cross-sell, the Amazon
  affiliate secondary section.
- **Conversion destinations**: individual book pages, Complete
  Collection page (paperback/hardcover), Adventure Kit signup, teacher
  classroom guide signup, Amazon (outbound, non-attributable beyond
  click).

## 3. Content classification data dictionary

See `inc/class-bhp-content-classification.php`. Enum registries (all
`apply_filters`-wrapped, extendable without editing this file):

| Field | Values | Default |
|---|---|---|
| `audience` | parent, teacher, librarian, homeschool, grandparent_gift, mixed | mixed |
| `funnel_stage` | awareness, problem_recognition, consideration, product_discovery, conversion, retention_engagement | awareness |
| `intent` | educational, reading_development, adventure_geography, teacher_resource, book_recommendation, product_discovery, activity_resource, trust_authority | educational |
| `primary_goal` | adventure_kit_signup, visit_book_page, visit_collection_page, direct_purchase, amazon_outbound_click, related_content_engagement | related_content_engagement |
| `secondary_goal` | audience_identification, another_resource, another_book, email_signup, bundle_discovery | another_resource |

`source` is always one of `explicit` (meta box saved), `guide_registry_derived`
(read-only bridge to the pre-existing `bhp_get_guide_registry()`), or
`flat_default` (nothing else available — still a complete, valid array,
never null).

**This is intentionally a richer, separate taxonomy from the existing,
live `bhp_get_audience_types()` (3 values, used for Mailchimp
segmentation)** — the two do not overlap and neither was modified to
match the other.

## 4. CTA registry guide

See `inc/class-bhp-cta-engine.php::registry()`. 7 destination types:
`adventure_kit_signup`, `book_page`, `collection_paperback`,
`collection_hardcover`, `amazon_listing`, `teacher_resource`,
`related_content`. Each entry has real, non-fabricated copy, an
audience/funnel_stage/intent match list (used for scoring, not an
exclusive filter), and a `resolve_url()` callback that returns `''`
(never a broken link) when its destination genuinely isn't available
for the given context (e.g. `book_page` with no `featured_book`).

`select_cta($context)` scores every registry entry (audience 3pts,
funnel_stage 2pts, intent 2pts) and returns the highest-scoring
*resolvable* entry — `related_content` is deliberately low-scoring but
always-resolvable, so it is the guaranteed final fallback.

`select_specific($id, $context)` bypasses scoring for placements that
need one deliberate, named destination (e.g. the product-page Adventure
Kit cross-sell) rather than a contextual guess.

## 5. CTA placement rules (where each mechanism is actually wired)

| Placement | Mechanism | File |
|---|---|---|
| Blog end-of-article (non-registry posts only) | `render_for_post()` fallback | `template-parts/guides/related-content.php` |
| Blog inline (mid-article) | `[bhp_contextual_cta]` shortcode, editor opt-in only | `inc/class-bhp-cta-engine.php` |
| Product page cross-sell | `select_specific('adventure_kit_signup')` at `woocommerce_after_single_product_summary` priority 40 | `functions.php` |
| Related Field Notes cards | `data-bhp-event="related_content_click"` | `template-parts/guides/article-card.php` |
| Campaign landing page | `BHP_Campaign_Landing::render()` composing CTA/signup-form blocks | `inc/class-bhp-campaign-landing.php` |

**Registry posts (all 35 current live posts) are completely unaffected**
by any of the above except the Related Field Notes card event and the
product-page cross-sell — their existing guide-continuation experience
renders exactly as before.

## 6. Landing-page framework guide

`BHP_Campaign_Landing::render($config)` takes a config array (`campaign_id`,
`audience`, `funnel_stage`, `lead_offer`, `cta_goal`, `variant`,
`source_channel`, `blocks`) and composes `hero` / `benefits` / `trust` /
`resource_preview` / `product` / `signup_form` / `amazon_alt` /
`related_content` blocks IN THAT FIXED ORDER, each rendering through an
existing, already-approved component. `validate($config)` checks shape
before rendering; `render()` fails safe (empty string) rather than a
partial page. Two example configs
(`example_adventure_kit_config()`, `example_teacher_guide_config()`)
demonstrate the framework without replacing the existing live
`/reluctant-reader-adventure-kit/` or `/teachers/` pages.

## 7. Conversion-event dictionary

See `docs/event-dictionary.md` and `docs/gtm-implementation-manifest.json`
for the full table. New this phase: `contextual_cta_click`,
`contextual_cta_view`, `related_content_click`, `landing_page_view`,
`landing_page_cta_click`, `lead_form_view`, `lead_form_start`. All
reuse the existing `nav.js` generic `data-bhp-event`/
`data-bhp-impression-event` mechanism, plus one new, equally generic
`data-bhp-focus-event` mechanism for "first interaction with a form."
None contain PII. **None of these reach GA4 until the external GTM
container is configured** — verified only that they reach
`window.dataLayer`.

## 8. Pinterest content-brief system

Not built new — an existing, schema-only `content-engine/` architecture
was found and populated with one real example
(`content-engine/blogs/mariana-trench-facts-for-kids/`). See
`docs/phase1d-pinterest-workflow.md` for the full workflow and explicit
non-goals (no Pinterest account/board/API credential exists; nothing is
scheduled or published).

## 9. Conversion-readiness scoring rubric

`BHP_Conversion_Scoring` — 14 criteria, each tagged `automated`/
`inferred`/`manual` (see `inc/class-bhp-conversion-scoring.php::criteria()`).
Distinct from `content-engine/config/scoring-rubric.yaml` (which scores
Pinterest pin creative). Report-only — never edits content. Sample run
against the 6 required page types: `docs/phase1d-conversion-scoring-sample-run.md`.

## 10. Admin operations guide

- **Classify content**: Posts list screen shows a "Funnel Classification"
  column; edit the meta box on any eligible post to set explicit values.
- **View coverage/lead summary**: WooCommerce → Brave Hearts Dashboard →
  "Organic content-to-lead conversion (Phase 1D)" section (integrated
  into the existing dashboard, not a new page).
- **View per-signup detail**: Tools → Lead Signups (pre-existing,
  unchanged).
- **Run a conversion-readiness scoring pass**:
  `wp eval-file content-engine/scripts/run-conversion-scoring-sample.php --user=1 --url=<host>`
- **Validate a Pinterest design brief**:
  `php content-engine/scripts/validate-design-brief.php <blog-slug>`

## 11. Staging validation guide

Every Phase 1D change was deployed to staging via `scp`, linted
(`php -l`), fatal-checked (`wp eval 'echo "ok";'`), and verified against
the FULL regression suite (all 9 theme test files + all 13 plugin test
files = 22 suites) before each commit — see individual commit messages
for per-checkpoint results. Real GA4 (`G-7M42X19Z2T`)/GTM
(`GTM-N474PRSH`) IDs, the staging-analytics-override option (unset), and
the production consent gate (unset) were re-verified after every
checkpoint to confirm no configuration drift.

## 12. Production deployment checklist (NOT executed this phase)

1. Confirm the exact staging commit that was validated (this branch's
   HEAD after the final Phase 1D commit).
2. Build a theme ZIP via `git archive` from that exact commit
   (`--prefix=brave-hearts-theme-deploy-explorer-expedition-guides/`).
3. Get Andrew's explicit, current-turn approval — a prior approval for
   a different change does not carry over.
4. `wp theme install <zip> --force` on production, then immediately
   `wp theme list --status=active` to confirm it replaced the live
   theme (not installed as a new inactive one).
5. Deploy the touched plugin file
   (`plugins/brave-hearts-bundle-pricing/includes/dashboard/class-bhp-dashboard-page.php`)
   the same way.
6. `wp eval 'echo "ok";'` fatal check.
7. `wp sg purge` cache.
8. Re-run the fatal check and spot-check the new dashboard panel and
   one blog post's CTA rendering on production (read-only checks only).
9. Confirm production's own GA4/GTM/consent-gate options are unchanged
   by this deploy (Phase 1D added no production config).

## 13. Rollback plan

- **Theme**: reinstall the previous production theme ZIP (built from
  the commit immediately before this phase's first commit,
  `8403b3b`'s parent, `d1d0447`) the same way — `wp theme install --force`
  + verify active version.
- **Plugin**: restore the single touched dashboard file from git
  (`git show d1d0447:plugins/.../class-bhp-dashboard-page.php`) and
  re-deploy it alone.
- **No database migration occurred** — the only new persistent data are
  post-meta keys (`_bhp_content_*`) and a cached transient
  (`phase1d_classification_coverage`, via `BHP_KPI_Cache`), both inert
  if the code reading them is rolled back.

## 14. Unresolved editorial decisions (Andrew's call, not made here)

- Individual product pages describe fulfillment as "Printed and shipped
  by Lulu" while the Complete Collection page says "Printed and shipped
  in the USA" — the real fulfillment partner is Bookvault, not Lulu
  (per company memory). This is stale product-description copy, found
  during the Workstream 5 audit, NOT edited here since it's WooCommerce
  product content, not template code.
- The Complete Collection page's "Used in 40 classrooms" claim was
  found during the same audit — no verification evidence was available
  either way; flagged rather than assumed fabricated or assumed true.
- Whether to build a genuine Page/product-level bridge for
  `BHP_Content_Classification` (currently posts-only, see the
  conversion-scoring sample run's findings) is a future-phase scoping
  decision, not made here.

## 15. Unresolved asset needs

- No new visual/creative assets exist for the Pinterest example's 4
  variants — `visual_direction` fields describe what should be created,
  not a delivered image.
- The campaign-landing framework's 2 example configs use only existing
  copy/URLs; no new photography, illustration, or video was requested
  or produced.

## 16. Unresolved manual Google/Pinterest actions

- No Pinterest account, board, or API credential exists — creating one
  is an Andrew-only, explicit-approval action, not attempted here.
- GTM must still be manually configured (tags/triggers/variables) per
  `docs/gtm-configuration-blueprint.md` and the updated
  `docs/gtm-implementation-manifest.json` before any Phase 1D event
  reaches GA4 — this was true before Phase 1D and remains true after.

## 17. Exact operator checklist (what to do next, in order)

1. Review this doc and `docs/phase1d-conversion-scoring-sample-run.md`.
2. Decide the two unresolved editorial items in Section 14.
3. When ready for production, follow Section 12 exactly, with your
   explicit approval given in that session.
4. Configure GTM (external, manual) using the updated manifest.
5. Consider whether to classify more blog posts explicitly (currently
   0 explicit / 35 registry-derived) using the new meta box.
6. When ready to expand Pinterest, follow
   `docs/phase1d-pinterest-workflow.md`'s "How to add the next blog"
   section — one blog at a time, never a bulk batch.
