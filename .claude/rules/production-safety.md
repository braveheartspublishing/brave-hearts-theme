# Production Safety Rules

**Scope note (added 2026-07-11):** this file governs **theme code**
deployment (git branches, ZIP deploys, live SSH edits, cache purge). It
does not govern WordPress **article/post content**. New-article
WordPress drafts follow the separate, explicit rule in
`docs/content-operations/BHP_CONTENT_OPERATING_SYSTEM.md` §10 (new
articles go directly to production as `post_status: draft` after locked-
text approval — never published, never scheduled). Existing published-
article *refreshes* still go to staging first, matching this file's
spirit. Do not read "staging before production, always" below as
applying to blog-post content creation.

- Do not branch from `main` without first checking whether it matches the
  actual current production commit — it has gone stale before. Branch from
  the verified production commit hash instead when in doubt.
- Staging (`staging2.braveheartspublishing.com`) before production
  (`braveheartspublishing.com`), always, no exceptions.
- Never deploy to production without Andrew's explicit, current-turn
  approval. A prior approval for a different change does not carry over.
- Never edit production files directly via SSH for anything other than
  read-only inspection (`wp post get`, `wp option get`, etc.) or an
  explicitly-approved narrow data fix (e.g. a single price correction
  Andrew approved by name). Theme code changes go through the ZIP-deploy
  process, not live edits.
- Full-ZIP `wp theme install --force` deploys only — verify
  `wp theme list --status=active` immediately after to confirm it actually
  replaced the live theme (see `docs/DECISIONS.md` for why this matters).
- Purge SiteGround cache (`wp sg purge`) after any deploy that changes
  customer-facing behavior.
- Never run `--no-verify`, force-push, or `git reset --hard` without
  Andrew explicitly requesting it in the current turn.
