# Pinterest Content-Brief Production Workflow (Phase 1D)

## Do not build a new system — one already exists

Before starting Phase 1D's Pinterest workstream, an existing,
already-designed system was found at `content-engine/` (see
`../content-engine/README.md`, relative to this doc — repo root `content-engine/README.md`). It already defines exactly the schema this
workstream asked for: a 4-variant design brief (problem-led/outcome-led/
curiosity-led/resource-led), a funnel/audience classification step, an
objective scoring rubric, and a UTM standard. **This document explains
how to use that existing system, not a replacement for it.**

Its status, unchanged by this phase: *"architecture and schemas only. No
automation runs yet. No Pin has ever been published by this system."*
Nothing in this phase changes that — no Pinterest account, board, or API
credential exists or is referenced anywhere in this repo.

## What Phase 1D added

One real, populated example under `content-engine/blogs/mariana-trench-facts-for-kids/`:

- `source.json` — real title/URL/excerpt from the actual published blog post
- `strategy.json` — funnel stage (`awareness`) and audience classification, reasoned against `config/funnel-routes.yaml`
- `design-brief.json` — all 4 required variants, with headline/copy grounded in facts that are **directly quoted from the actual published post** (Everest fitting inside the trench with a mile of water to spare, the 36,000 ft depth, the anglerfish's glowing lure, etc.) — nothing invented
- `review.json` — a manual, copy-level self-review against `config/scoring-rubric.yaml`, explicitly labeled as **not** an automated design-review (none exists yet) and **not yet approved** by Andrew
- `content-engine/scripts/validate-design-brief.php` — a plain-PHP (no WordPress dependency, matching the directory's own schema-only scope) structural validator: checks all 4 variant types are present exactly once, every required field is non-empty, every `destination_url` carries all 4 required UTM parameters, `utm_content` matches the `<blog-slug>_<design-variant>_<version>` naming pattern, and no forbidden claim words (`award`, `guarantee`, `limited time`, `clinically`, `proven to`) appear in any headline/description field

This is deliberately **one** blog, not a bulk run — see the workstream's
own instruction not to generate hundreds of pins in one session.

## How to add the next blog

1. Create `content-engine/blogs/<blog-slug>/`.
2. Populate `source.json` from the real, already-published post (title, URL, excerpt, `related_book`) — copy the shape from `templates/source.template.json`.
3. Populate `strategy.json` using `config/funnel-routes.yaml`'s `content_type_routing` table to pick the funnel stage, audience, and destination. Write real `reasoning`, don't leave it blank.
4. Populate `design-brief.json` with all 4 variants. Every fact in every headline/description must trace back to something the actual blog post already says — read the real post content first (`wp post get <id> --field=post_content` or the live page) rather than inventing numbers.
5. Run the validator:
   ```
   php content-engine/scripts/validate-design-brief.php <blog-slug>
   ```
   Fix every reported error before moving on.
6. Populate `review.json` — score honestly against `config/scoring-rubric.yaml`'s 5 weighted criteria. If no real creative image exists yet, say so explicitly in the review (as `mariana-trench-facts-for-kids/review.json` does) rather than pretending `brand_consistency` was verified against an asset that doesn't exist.
7. Stop there. `publish-manifest.json` and `performance.json` are template-only stages that require an actual Pinterest account/board and Andrew's explicit human approval (repo-root `content-engine/README.md` pipeline stages 10-13) — out of scope for this phase and every future phase until those prerequisites exist.

## What this phase explicitly did NOT do

- Did not create a Pinterest account, board, or API credential.
- Did not schedule or publish anything.
- Did not process more than one blog post.
- Did not fabricate any statistic, review, or claim not already present in the source blog post.
- Did not change `config/*.yaml` (the existing standard was correct as found).
