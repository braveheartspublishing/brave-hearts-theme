# Content Engine (Blog → Pinterest) — Architecture Scaffolding

**Status: architecture and schemas only. No automation runs yet. No Pin
has ever been published by this system.** This directory exists so the
future publishing pipeline has a defined shape to build into, rather
than starting from nothing when that work is prioritized.

## What this system will eventually do

Take an existing blog post, classify it by funnel stage and audience,
generate four distinct creative hypotheses for a Pinterest pin, route
each through an objective design review, get human approval, schedule
publication with correct UTM attribution, and feed 30/60/90-day
performance data back into future creative briefs.

## Pipeline stages

1. **Blog ingestion** — pull a blog post's content/metadata into `source.json`.
2. **Funnel classification** — which stage of the funnel this content serves (awareness / consideration / conversion), per `config/funnel-routes.yaml`.
3. **Audience classification** — parent, teacher, homeschool, gift-buyer, etc.
4. **Search-intent analysis** — what the reader was actually looking for when they'd find this content.
5. **Design brief generation** — `design-brief.json`, one per blog, describing the four required variants.
6. **Claude Design output** — the actual creative generation step (external to this repo's scope until wired up).
7. **Objective design review** — scored against `config/scoring-rubric.yaml`.
8. **Revision threshold** — a variant scoring below threshold gets revised, not silently shipped.
9. **Four strategic variants** — problem-led, outcome-led, curiosity-led, resource/list-led (see below).
10. **Human approval** — Andrew (or a designated reviewer) signs off before anything schedules.
11. **Pinterest scheduling** — `publish-manifest.json` records exactly what was scheduled, when, to which board.
12. **UTM attribution** — every scheduled pin carries the standard from `docs/utm-attribution-standard.md`.
13. **30/60/90-day performance review** — `performance.json` per blog, filled in after the fact.
14. **Feedback into future briefs** — performance data informs the next blog's `design-brief.json`, closing the loop.

## Four required creative hypotheses

Every blog gets exactly these four variants, each with its own headline,
supporting line, visual direction, Pinterest title, Pinterest
description, alt text, board, destination URL, and `utm_content` value:

- **Problem-led** — opens with the reader's pain point (e.g., "Struggling to get your reluctant reader excited about chapter books?")
- **Outcome-led** — opens with the result (e.g., "Watch your child beg for 'just one more chapter.'")
- **Curiosity-led** — opens with an intriguing fact or question (e.g., "The Mariana Trench is deeper than Everest is tall — did you know?")
- **Resource/list-led** — opens with a practical, save-worthy framing (e.g., "5 real-science facts your child will want to know before bed")

## Directory structure

```
content-engine/
├── config/
│   ├── brand-guidelines.yaml     — voice, color, typography constraints for generated creative
│   ├── funnel-routes.yaml        — maps content types to funnel stage + recommended destination
│   ├── pinterest-boards.yaml     — canonical board list + which audience/theme each serves
│   ├── scoring-rubric.yaml       — objective design-review criteria and pass threshold
│   └── utm-standard.yaml         — machine-readable mirror of docs/utm-attribution-standard.md
├── blogs/
│   └── <blog-slug>/
│       ├── source.json           — ingested blog content/metadata
│       ├── strategy.json         — funnel + audience + search-intent classification
│       ├── design-brief.json     — the four-variant brief
│       ├── variants/             — one file per generated variant
│       ├── review.json           — scoring-rubric results + revision history
│       ├── publish-manifest.json — what was actually scheduled/published, when, where
│       └── performance.json      — 30/60/90-day metrics, filled in after publication
├── templates/                    — reusable brief/variant/review JSON templates
├── scripts/                      — future automation entry points (none exist yet)
├── reports/                      — aggregate cross-blog performance reports
└── README.md                     — this file
```

## Explicit non-goals right now

- No script in `scripts/` executes anything yet — this is schema and
  config only.
- No Pinterest API credentials are referenced or required by anything
  in this directory.
- No blog has been processed through this pipeline.
- This system does not decide when it's time to build the automation —
  that's a future, separate scoping decision.
