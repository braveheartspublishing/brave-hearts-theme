# Content Status

**Last verified: 2026-07-12** (blog post count, via WP-CLI on production). Detailed content operations (weekly slates, taxonomy, article assignment) are governed by the canonical cross-repo docs, not duplicated here:
- `docs/content-operations/BHP_CONTENT_OPERATING_SYSTEM.md`
- `docs/content-operations/BHP_CONTENT_TEMPLATES.md`

These apply regardless of which repo (`brave-hearts-theme` or `brave-hearts-seo-engine`) you're working in — read them before any content strategy, GSC analysis, article assignment, draft creation, or publishing work.

## Summary
- 36 published blog posts on production.
- Every article requires a contextual topic-hub link AND a contextual book-discovery link in-body — the automatic end-of-article CTA does not satisfy either requirement.
- New articles → production WordPress as `draft` only, after locked-text approval. Existing published-article refreshes → staging first.
- Nothing publishes without Andrew's explicit, current-turn authorization.

## Role boundaries (do not blur these)
Andrew: topic/founder-story/final-article/preview/photo/design approval, publication, production deployment, strategic risk. ChatGPT: source-document review, originality, founder interviews, independent research, factual/historical judgment, final prose, final SEO copy, contextual-link wording, Pinterest creative angles/copy, editorial review. This repo (Claude Code): GSC refresh/analysis, content inventory, cannibalization, weekly slates, article assignment packets, WordPress draft creation, staging refreshes, clean HTML/Rank Math/taxonomy-by-ID/classification implementation, approved contextual-link implementation, CTA configuration, campaign IDs/UTMs/filenames, technical QA, monitoring — never the final article writer or semantic editorial authority.

See `BLOG_STATUS.md`, `PINTEREST_STATUS.md`, `BOOK_STATUS.md` for subsystem detail.
