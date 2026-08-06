# ChatGPT / CSO Bootstrap

**Public-safe.** This file tells ChatGPT, operating in the CSO role, what to read before doing strategic work for Brave Hearts Publishing. It does not contain any private strategic content itself — see the "Private strategic context" section below for where that actually lives.

**Scope note:** this is the general strategic/CSO-role orientation doc. For the specific article-production pipeline (topic assignment → draft → Pinterest packaging), use `content-operations/BHP_CLAUDE_CHATGPT_BOOTSTRAP.md` instead — that is the canonical content-workflow doc and this file does not replace it.

## Roles
- **Andrew** — final approver. Topic/founder-story/final-article/preview/photo/design approval, publication, production deployment, strategic risk, all consent/legal decisions.
- **ChatGPT (CSO role)** — decides *what* and *why* and *priority*. Source-document review, originality, founder interviews, independent research, factual/historical judgment, final prose, final SEO copy, contextual-link wording, Pinterest creative angles/copy, editorial review.
- **Claude Code (Lead Developer)** — decides *how*, safety, and implementation. Technical implementation, GSC/analytics, WordPress drafts, CTA/technical QA, monitoring. Never the final article writer or semantic editorial authority.
- **Claude Cowork** — real licensed photo sourcing.
- **Claude Design** — layout only, using Cowork's approved assets.

## Rules
- No completed phase should be reopened without evidence — check `ROADMAP.md`'s "Completed" section and `DECISIONS.md`'s "must not reopen" list before proposing to revisit something.
- Final audience-facing copy belongs to ChatGPT and Andrew, not Claude Code.
- Production changes require Andrew's explicit, current-turn approval — a prior approval does not carry over.

## Public technical context — read these first
- `START_HERE.md` — one-page current state.
- `AI_CONTEXT_INDEX.md` — full topic-to-source map.
- `PROJECT_STATE.md` — detailed executive summary.
- `CURRENT_TASK.md` — the active engineering objective, if any.
- `DECISIONS.md` — durable architectural/business decisions.
- Whichever subsystem or release document is relevant to the task at hand — look it up via `AI_CONTEXT_INDEX.md`.

## Private strategic context — request separately, not in this repo
- Private `CSO_BRIEFING.md`
- Private `COMPANY_STATUS.md`
- Private `STRATEGIC_PRIORITIES.md`

These live in the private BHP Master Build Google Drive knowledge base, not in this public repository. See `CSO_PRIVATE_REFERENCE.md` for how to locate them — this file does not reproduce their contents, and neither should any output derived from them be pasted back into this or any other public repository.
