# Audience Landing Pages — Asset Manifest

Consolidated list of every placeholder asset slot across the 5 core audience
landing pages (Parent, Teachers/Librarians/Homeschool, Gift Buyers,
Bookstores/Retailers, Organizations), built 2026-07-15. All 5 pages
currently use book-cover images already in the media library
(`bhp_get_series_adventures()`); the slots below are the assets still
missing. Do not generate placeholder/fake photography — these are documented
gaps only, per company policy in `CLAUDE.md`.

No page is currently blocked by a missing asset — every slot below renders
a clearly-labeled "coming soon" placeholder box (see
`assets/css/audience-landing.css` / `assets/css/parent-landing.css`,
`.audience-landing-media__placeholder` / `.parent-landing-media__placeholder`)
at a locked aspect ratio, so layout never shifts once the real asset is
dropped in.

## How to fill a slot

1. Add the file to the Media Library.
2. Replace the corresponding `<div class="…-media__placeholder">…</div>`
   block in the page's PHP file with `bhp_parent_landing_cover()`-style
   `wp_get_attachment_image()` output (or a plain `<img>` for non-book
   photography), using the recommended size and `alt` text below.
3. Keep the existing wrapping `.audience-landing-media`/`.audience-landing-
   media--tall`/`.audience-landing-media--wide` div and its `aspect-ratio`
   — do not change the container's aspect ratio without updating the CSS
   rule, since that's what prevents layout shift.

## Shared across all 5 pages (via `bhp_get_series_adventures()`)

Already sourced from the Media Library — no gap. Only listed for context:
Mariana Trench / Mount Everest / Amazon cover images (hero, lead-magnet
panel, Complete Collection cards).

## Parent — `page-reluctant-reader-adventure-kit.php`

| Asset | Purpose | Recommended size | Aspect ratio | File type | Placement |
|---|---|---|---|---|---|
| Interior spread photo | "See inside the books" primary preview | 1200×900px min | 4:3 | JPG/WebP | `.parent-landing-media` in the "See inside" split section |
| Chapter art detail | One of 4 detail cards | 900×1200px min | 3:4 | JPG/WebP | `.parent-landing-media-grid` figure 1 |
| Explorer map detail | One of 4 detail cards | 900×1200px min | 3:4 | JPG/WebP | `.parent-landing-media-grid` figure 2 |
| Fact page detail | One of 4 detail cards | 900×1200px min | 3:4 | JPG/WebP | `.parent-landing-media-grid` figure 3 |
| Activity sheet detail | One of 4 detail cards | 900×1200px min | 3:4 | JPG/WebP | `.parent-landing-media-grid` figure 4 |
| Author photo | "Why these books exist" section | 900×1035px min | 4:4.6 | JPG/WebP | `.parent-landing-author__photo` |
| Video thumbnail + real video | "See the books in Andrew's hands" | 1600×900px min | 16:9 (thumbnail shown at 16:8 crop) | JPG thumbnail + hosted video URL | `.parent-landing-media--wide` in the video section |

Proposed filenames: `mariana-interior-spread-01.jpg`, `chapter-art-detail-01.jpg`,
`explorer-map-detail-01.jpg`, `fact-page-detail-01.jpg`,
`activity-sheet-detail-01.jpg`, `andrew-author-photo-01.jpg`,
`author-video-thumbnail-01.jpg`.

Alt-text drafts: "Interior spread from The Mariana Trench showing a chapter
opener with illustration", "Black-and-white chapter illustration from the
Adventures of Charlotte and Henry series", "Printable explorer map from The
Mariana Trench", "Real explorer fact page from The Mariana Trench",
"Printable explorer activity sheet", "Andrew, author of Adventures of
Charlotte and Henry, with Charlotte and Henry", "Andrew introducing the
Adventures of Charlotte and Henry series".

## Teachers/Librarians/Homeschool — `page-audience-educators.php`

**Updated 2026-07-16: no longer a gap — the real toolkit is delivered.**
The 5-figure "design in progress" placeholder module described below (built
2026-07-15, Round 3) was replaced once Andrew approved the real 8-page PDF.
The lead-magnet panel now shows the real cover image
(`assets/images/handoff/educator-toolkit-cover.webp`, sourced from the
approved PDF's page 1). The former 5-figure grid was replaced with a single
tall cover image plus a 6-item contents checklist (cover page, discussion
questions, vocabulary & geography, science spotlight, hands-on classroom
activity, reproducible student field journal) reflecting the PDF's real
contents — no additional per-page preview images were requested or created.
Original Round 3 note, kept for history:

**Added 2026-07-15 (Round 3):** a 5-figure "Adventure Learning Toolkit
preview" module, `.audience-landing-media-grid` between the lead-magnet
panel and the Complete Collection section. All 5 slots are currently the
dashed-border "design in progress" placeholder at the locked 3:4 ratio —
none are real assets yet, and none should be filled until the actual
toolkit pages exist (do not fabricate finished PDF pages).

| Asset | Purpose | Aspect ratio | Placement |
|---|---|---|---|
| Toolkit cover | First impression of the printed kit | 3:4 | `.audience-landing-media-grid` figure 1 |
| Discussion-questions page | Real page preview, not mockup text | 3:4 | figure 2 |
| Vocabulary & geography page | Real page preview | 3:4 | figure 3 |
| Read-aloud guide page | Real page preview | 3:4 | figure 4 |
| Classroom activity page | Real page preview | 3:4 | figure 5 |

If a real classroom-use photo becomes available separately (not a toolkit
page), it would slot into a new section between "Books as cross-curricular
resources" and the free lead-magnet section, following the same
`.audience-landing-media` pattern.

## Gift Buyers — `page-audience-gift-buyers.php`

No dedicated media slots beyond the shared book covers. A real "gift being
opened" or "grandparent reading with child" photo, if sourced later, would
slot into the "What they'll actually receive" section using the same
`.audience-landing-media--tall` pattern as Parent's detail cards.

## Bookstores/Retailers — `page-audience-retailers.php`

No dedicated media slots beyond the shared book covers and the "on the
shelf" book-card grid. A real in-store shelf/display photo, if sourced
later, would slot into the "Product / display appeal" section (currently
the "Current titles" book-card grid) using `.audience-landing-media--wide`.

## Organizations — `page-audience-organizations.php`

No dedicated media slots beyond the shared book covers. A real program/
event photo (read-aloud session, bulk-gifting moment), if sourced later,
would slot into the "Program use cases" section using
`.audience-landing-media--wide`.

## Not in scope for this manifest

Author photo and video (Parent page only) are the only two assets flagged
as "high value, still missing" by the original sprint directive — everything
else above is optional polish. See `docs/PROJECT_STATE.md` for current
priority ordering.
