# Documentation Governance

**Last updated: 2026-07-12.**

## Ownership
- **Engineering** (Claude Code + Andrew) owns `docs/` in this repo: `PROJECT_STATE.md`, `CURRENT_TASK.md`, `NEXT_TASK.md`, `DECISIONS.md`, `ROADMAP.md`, `KNOWN_ISSUES.md`, `CHANGELOG.md`, `RUNBOOK.md`, `RELEASES/`, `ANALYTICS/`, `ENGINEERING/`, `START_HERE.md`, `AI_CONTEXT_INDEX.md`, this file.
- **Content ops** (primarily tracked in `brave-hearts-seo-engine`) owns `CONTENT/` status files in this repo, plus everything in `content-operations/`.
- **Andrew** owns all consent/legal/strategic decisions and every production/publish approval, regardless of which document records the outcome.
- **Private strategy** (CSO/company-level material) is owned by Andrew alone and lives outside this repo — see `CSO_PRIVATE_REFERENCE.md`.

## Update cadence
- **Every completed release**: `PROJECT_STATE.md`, `CURRENT_TASK.md`, `NEXT_TASK.md`, `CHANGELOG.md`, the relevant subsystem status doc, and the relevant `RELEASES/` record — same session, not deferred. See `CLAUDE.md`'s Maintenance section.
- **Monthly** (or whenever a session has spare capacity and nothing more urgent): run the audit procedure below.
- **On demand**: any time a canonical document is found to conflict with live state, fix it immediately rather than filing it as a known issue.

## Canonical-status rules
- `AI_CONTEXT_INDEX.md` is the single source of truth for "which document wins" on any topic. If two documents disagree, the one marked canonical in that index is correct — go verify live state, don't average the two.
- A document not listed in `AI_CONTEXT_INDEX.md` is not canonical for anything, even if it looks authoritative — it's most likely a historical session record (see Archival rules).
- Adding a new canonical document requires adding it to `AI_CONTEXT_INDEX.md` in the same change — a canonical doc that isn't indexed is undiscoverable and will be treated as historical by default.

## Archival rules
- **Never delete a historical session-record document** (dated audit reports, phase-completion write-ups, one-off investigation docs) just because a newer canonical doc supersedes it for status purposes — it stays as an archaeology record of *why* a decision was made.
- **Never cite a superseded document as current status.** If you're tempted to quote a `phase1d-*.md` or `gtm-*-2026-07-*.md` file for "what's true now," stop and read the canonical doc `AI_CONTEXT_INDEX.md` points to instead.
- A large batch of historical root-level `docs/*.md` files predates this governance structure and has not yet been individually triaged into "keep public as-is" vs. "move private" vs. "archive." Until that triage happens, treat anything not listed in `AI_CONTEXT_INDEX.md` as informational/historical only, never as an authoritative source for a current-state claim.

## Public/private classification
- **Public-safe** (this repo): sanitized technical/operational documentation only. See `AI_CONTEXT_INDEX.md`'s "Visibility" column.
- **Private** (Google Drive `Company Knowledge Base/`): company strategy, revenue targets, channel strategy, competitive positioning, internal financial/cost/margin figures, personal contact information beyond the public business email.
- **Before adding new content to any public-repo document**, check it against this list: no credentials/secrets, no customer/subscriber PII, no personal founder information beyond the business email, no private Drive paths, no unpublished manuscript excerpts, no revenue/margin/CPA figures, no internal competitive strategy, no security-sensitive infrastructure detail (real hostnames/ports/usernames — placeholders only, pointing to a local, non-repo credential store).
- If in doubt, it's private until Andrew says otherwise — this repo is public on GitHub, confirmed via the GitHub API (`private: false`, `visibility: public`), and that is not going to change silently.

## Conflict-resolution process
1. Identify which document is marked canonical for the topic in `AI_CONTEXT_INDEX.md`.
2. Verify the actual live state (git log, WP-CLI, live browser check, GTM console, etc.) — never resolve a doc conflict by picking the more recently-edited file, since "recently edited" isn't the same as "correct."
3. Fix the stale document(s) to match live reality.
4. If the conflict reveals a genuine ambiguity in ownership or scope (not just staleness), update this file or `AI_CONTEXT_INDEX.md` to close the gap, so the same conflict can't recur.
5. Note the correction in `CHANGELOG.md` if it's a substantive factual correction (e.g., "docs said X, live state was actually Y").

## Required release-closeout updates
Every completed release must update, before the session ends:
- `PROJECT_STATE.md` (the relevant summary line/table entry)
- `CURRENT_TASK.md` and `NEXT_TASK.md` (reflect the new state)
- `CHANGELOG.md` (one entry, dated)
- The relevant subsystem status doc (`ANALYTICS/`, `CONTENT/`, `ENGINEERING/`)
- The relevant `RELEASES/` record (new entry or append to an existing phase record — don't create a second file for the same release track)
- `ROADMAP.md` if the release moves something from "Planned" to "Completed"
- `KNOWN_ISSUES.md` if the release closes or changes the status of an open issue

## Internal-link convention
Every internal doc citation should be **repo-root-relative and `docs/`-prefixed** (e.g. `docs/ANALYTICS/GTM_STATUS.md`, not `/ANALYTICS/GTM_STATUS.md`, not a bare `GTM_STATUS.md` assumed same-directory). A 2026-07-12 audit found dozens of citations across the tree using three different, inconsistent conventions (leading-slash, bare-filename, and correctly-prefixed) for the same target files — none of which resolve correctly under standard relative-link rules from every citing location. New documents must use the `docs/`-prefixed form; fixing the existing inconsistent citations is a backlog item, not urgent, since the mechanical impact is a broken formal Markdown link (this repo does not currently use formal `[text](path)` links at all — every citation is a plain-text/backtick reference, so nothing is technically "broken" in a rendering sense, only ambiguous to a human or tool trying to resolve it).

## Monthly documentation audit procedure
1. Re-run the sensitive-content categories from this governance doc's "Public/private classification" section against any file added or substantially edited in the past month.
2. Spot-check 3-5 "Last verified" dates in `AI_CONTEXT_INDEX.md` against live state (WP-CLI counts, GTM console, git log) — if more than one is stale, re-verify the whole table.
3. Check `ROADMAP.md`'s "Completed" section for anything that got quietly reopened without a documented reason.
4. Check for new duplicate/contradictory status claims across subsystem docs (e.g., two different post counts for "current blog post total").
5. Note findings in `CHANGELOG.md` if corrections were made; otherwise no action needed — a clean audit doesn't need its own report.
