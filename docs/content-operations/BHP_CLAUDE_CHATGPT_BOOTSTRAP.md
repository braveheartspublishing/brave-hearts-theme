# Brave Hearts Publishing — Claude and ChatGPT Bootstrap

**Version:** 1.2 — active pilot is simplified to one article at a time,
one Pinterest pin per article, one hero crop from the same visual
concept. See `BHP_CONTENT_OPERATING_SYSTEM.md`'s PILOT LIMITS section
and v1.2 changelog entry for full detail.

## Canonical documents

Before any Brave Hearts content work, read:

1. `BHP_CONTENT_OPERATING_SYSTEM.md`
2. `BHP_CONTENT_TEMPLATES.md`
3. `BHP_Brand_Skill.docx`
4. `BHP_Life_Story_Skill.docx`
5. `Internal_Philosophy_NOT_Book_Volume_I.docx`
6. `Internal_Philosophy_NOT_Book_Volume_II.docx`
7. Relevant canonical manuscript or approved product source

## Required Claude.md instruction

Add this block to both repo-level `CLAUDE.md` files:

```markdown
## Brave Hearts content operations

Before performing content strategy, WordPress content implementation, Pinterest packaging, SEO metadata, taxonomy, CTA, or publishing work, read and follow:

- C:\BHP\brave-hearts-theme\docs\content-operations\BHP_CONTENT_OPERATING_SYSTEM.md
- C:\BHP\brave-hearts-theme\docs\content-operations\BHP_CONTENT_TEMPLATES.md

These files are the canonical cross-repo operating procedure.

Key boundaries:

- Claude Code analyzes, implements, validates, and monitors.
- ChatGPT researches, writes, fact-checks, finalizes prose, and supplies
  the single Pinterest title/description/visual brief as part of the
  complete article package.
- Claude Cowork is optional — used only when licensed real photography
  must be sourced, not a mandatory stage for every article.
- Claude Design performs layout using the approved supplied assets; it
  does not source photography. Under the active pilot, exactly one
  Pinterest layout plus one hero crop from the same creative — no
  additional variants.
- New articles go to production WordPress as drafts only after locked-text approval.
- Existing published article refreshes go to staging first.
- Every article requires a topical hub/collection link and a book-discovery link unless Andrew approves an exception.
- For non-AI Pinterest campaigns requiring real photography, Claude Cowork sources it before Claude Design begins final layout.
- Automatic CTAs do not replace contextual links, and a normal in-body contextual link is never a CTA collision.
- **Active pilot limits: one article at a time, one Pinterest pin per article, one hero crop from the same visual concept — no four-variant Pinterest packages, no parallel articles, until Andrew explicitly expands.**
- Never silently alter locked prose.
- Never publish or schedule a pin without explicit authorization.
```

## Recommended file locations

Canonical operational copy:

`C:\BHP\brave-hearts-theme\docs\content-operations\`

Files:

- `BHP_CONTENT_OPERATING_SYSTEM.md`
- `BHP_CONTENT_TEMPLATES.md`
- `BHP_CLAUDE_CHATGPT_BOOTSTRAP.md`

The theme repository is remote-backed, so this provides durable version control.

The SEO-engine repo should reference the canonical files from its `CLAUDE.md` rather than maintain an independent editable copy.

## ChatGPT continuity

Upload the same three files to ChatGPT File Library.

At the beginning of a new Brave Hearts content chat, use:

> Use the Brave Hearts Content Operating System v1.2 and Content Templates from my File Library as the canonical workflow. We're on the simplified pilot: one article at a time, one Pinterest pin, one hero crop from the same visual. Review the Brand Skill, Life Story Skill, Volume I Constitution, Volume II Experience, and the relevant manuscript/product sources before writing.

## Change control

Do not edit repo-specific copies independently.

Permanent changes must:

1. update the canonical operating system
2. increment its version
3. update templates if needed
4. update both CLAUDE.md references if paths change
5. upload the new version to ChatGPT File Library
