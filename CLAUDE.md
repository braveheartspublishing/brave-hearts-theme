# Brave Hearts Theme — Repository Memory

See `C:\BHP\CLAUDE.md` for company-level context. This file is
repository-specific.

## Before ANY engineering work

Read these, in order, before writing code or making a change — never begin
implementation until they've been reviewed this session (files can go
stale between sessions; re-read, don't rely on memory of a prior read):

1. `docs/START_HERE.md` — one-page current state.
2. `docs/AI_CONTEXT_INDEX.md` — full topic-to-source map; use it to find the
   right document instead of guessing a filename.
3. `docs/PROJECT_STATE.md` — detailed executive summary of what's true right now.
4. `docs/CURRENT_TASK.md` — the one active engineering objective, if any.
5. `docs/NEXT_TASK.md` — the one approved next task, if any.
6. `docs/DECISIONS.md` — durable architectural/business decisions. Do not
   re-decide something already decided here without new evidence.
7. `docs/KNOWN_ISSUES.md` — open issues, so you don't rediscover a known gap
   as if it were new.
8. Whichever subsystem doc is relevant (`docs/ANALYTICS/`, `docs/CONTENT/`,
   `docs/ENGINEERING/`) — look it up via `AI_CONTEXT_INDEX.md`, don't guess.
9. Whichever release doc is relevant: `docs/RELEASES/`.

**Verify against actual git/production/staging state before trusting any
of the above** — they can go stale between sessions; the repo and the live
systems are the source of truth, the docs are a snapshot.

## Rules

- **Do not rely on chat memory when canonical documentation exists.** If
  `AI_CONTEXT_INDEX.md` names an authoritative document for a topic, read
  that document — don't reconstruct the answer from conversation history.
- **Do not reopen completed work unless the canonical documents mark it
  active.** `ROADMAP.md`'s "Completed" section and `DECISIONS.md`'s
  "must not reopen" items are binding unless new evidence is presented.
- **Verify live state when a claim could have changed.** A matching
  version number across environments does not prove content parity —
  check post/product counts, plugin versions, and actual behavior, not
  just the version string.
- **Update the knowledge base after every completed release.** See
  "Maintenance" below — do this automatically, don't wait to be asked.
- **Never copy private CSO material into a public repository.** See
  `docs/CSO_PRIVATE_REFERENCE.md` — private strategy, revenue targets,
  and company-priority content live in the private Google Drive knowledge
  base only, never in this repo, never paraphrased into recognizability.
- **Stop if the documentation conflicts with live state.** Reconcile the
  conflict (verify which is actually true, fix the stale doc) before
  implementing anything that depends on the disputed fact.
- **Every final report must state which canonical files were read** —
  name them explicitly, not "the usual docs."

## Maintenance — after every completed release

Update, in the same session the release completes:
`docs/PROJECT_STATE.md`, `docs/CURRENT_TASK.md`, `docs/NEXT_TASK.md`,
`docs/CHANGELOG.md`, the relevant subsystem doc, and the relevant release
doc in `docs/RELEASES/`. Do this automatically — don't wait to be asked.

## Also useful
- `docs/RUNBOOK.md` — copy-paste commands for common tasks.
- `.claude/rules/*.md` — narrow, load-bearing safety rules by topic
  (production-safety, woocommerce, funnels, schema, testing).

## Brave Hearts Content Operations

Before performing content strategy, Search Console analysis, weekly
content slates, article assignments, WordPress drafts, existing-post
refreshes, SEO metadata, taxonomy, contextual links, CTA handling,
Pinterest packaging, visual-sourcing handoffs, publishing, or monitoring
work, read and follow:

- `C:\BHP\brave-hearts-theme\docs\content-operations\BHP_CONTENT_OPERATING_SYSTEM.md`
- `C:\BHP\brave-hearts-theme\docs\content-operations\BHP_CONTENT_TEMPLATES.md`

These are the **canonical cross-repository instructions** for all of the
above. Both are absolute paths and resolve identically regardless of
which repo (`brave-hearts-theme` or `brave-hearts-seo-engine`) you're
currently working in.

Key boundaries (full detail in the operating system doc):
- Andrew: topic/founder-story/final-article/preview/photo/design approval, publication, production deployment, strategic risk.
- ChatGPT: source-document review, originality, founder interviews, independent research, factual/historical judgment, contextual-link wording, Pinterest creative angles/copy, editorial review. **No longer the final article writer or SEO-copy author for the weekly blog pipeline — moved to the Claude side 2026-08-03 (v1.3 amendment, content-ops doc §20, FD-94 countersigned by Andrew 2026-08-04). Every other lane above is unchanged.**
- Weekly blog pipeline authorship (from 2026-08-03): `marketing-growth` drafts final article prose and the article's SEO copy → `chief-of-staff` reviews and quality-gates against the content-ops §11 QA gate and the claims rules → **Andrew's own review, assisted by an external AI of his choice, is the final gate** → production WordPress drafts only. Nothing publishes without Andrew's explicit, current-turn authorization.
- Claude Code (this repo): GSC refresh/analysis, content inventory, cannibalization, weekly slates, article assignment packets, WordPress draft creation, staging refreshes, clean HTML/Rank Math/taxonomy-by-ID/classification implementation, approved contextual-link implementation, CTA configuration, campaign IDs/UTMs/filenames, technical QA, monitoring. **Not** the final article writer or semantic editorial authority — never silently rewrite locked prose.
- Claude Cowork: real licensed photo sourcing and the asset/license ledger.
- Claude Design: layout only, using Claude Cowork's approved assets — must not generate imagery for non-AI Pinterest campaigns.
- Every article requires a contextual topic-hub link AND a contextual book-discovery link in-body (the automatic end-of-article CTA does not satisfy either).
- New articles → production WordPress as `draft` only, after locked-text approval. Existing published-article refreshes → staging first.
- Nothing publishes without Andrew's explicit, current-turn authorization.

## What this repo is
WordPress theme (`brave-hearts-theme-deploy-explorer-expedition-guides` is
the actual installed slug on both environments — legacy name, don't rename
it, it's load-bearing for `wp theme install --force` to overwrite the
right directory) for braveheartspublishing.com. WooCommerce + Rank Math
SEO, hosted on SiteGround, PHP.

- Production: https://braveheartspublishing.com
- Staging: https://staging2.braveheartspublishing.com
- Read-only/deploy SSH access details: see Claude Code auto-memory
  (`reference-bhp-siteground`) — host/port/user only, never a private key.

## Non-negotiable safety rules (see `.claude/rules/production-safety.md` for detail)
- Never branch from a stale `main` — verify the exact current production
  commit/theme version first (`wp theme list --status=active`).
- Staging before production, always.
- Never deploy to production without Andrew's explicit approval.
- Full-ZIP `wp theme install --force` deploys, not piecemeal file copies —
  and the ZIP's top-level folder name must match the *active* theme slug
  or it installs as a new, inactive theme instead of replacing the live one.
- Bookvault must never inject live carrier rates into customer checkout.
  Do not add "BookVAULT Shipping" to any WooCommerce zone.
- **Customer shipping is TIERED per number of books — corrected 2026-08-02.**
  The zone's `flat_rate` **$3.99** (Contiguous United States, the only zone,
  the only method) is the **base configuration**; the bundle plugin adjusts
  it per cart via the approved tier table, so the customer sees
  **$1.99 / $2.99 / $3.99 / $4.99** depending on format and quantity.
  Owner ruling: *"Andrew Signore, 2026-08-02: 'Shipping is tiered per amount
  of books ordered.'"* Implementation: `bhp_bundle_shipping_amount()` /
  `bhp_bundle_single_shipping()` / `bhp_bundle_rules()` in
  `plugins/brave-hearts-bundle-pricing/includes/bundle-{cart,data}.php`.
  Full table in `.claude/rules/woocommerce.md`.

  **SUPERSEDED wording, retained so it is not re-derived:** this file
  previously read *"the customer-facing flat rate ($3.99, Contiguous US zone)
  must stay intact."* That sentence conflated the **zone configuration**
  ($3.99, true) with the **rendered customer rate** (tiered, not always
  $3.99). Both halves of the old sentence were true of different things,
  which is exactly why it misled. Tracked as `CYCLE140-DEV-2` — now closed.
- Parent funnel and teacher funnel must stay isolated (separate storage
  keys, separate analytics event prefixes) — see `.claude/rules/funnels.md`.
- Never reintroduce the removed Teachers-page `the_content` filter (removed
  in commit `43fdf8a` — a real signup already existed, the filter was
  redundant).
- No fabricated review/aggregateRating schema.

## Before asserting anything about current state
Run `git log -1 --format='%H %s'`, `git branch --show-current`, and check
`style.css`'s `Version:` line yourself. `docs/PROJECT_STATE.md` is a
snapshot, not live truth.
