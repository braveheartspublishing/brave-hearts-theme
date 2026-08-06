# Brave Hearts Publishing Content Operating System

**Document ID:** BHP-CONTENT-OPS  
**Version:** 1.3  
**Owner:** Andrew Signore  
**Effective date:** 2026-07-11 (v1.0–1.2) · 2026-08-03 (v1.3 authorship amendment)  
**Status:** Canonical operating procedure  
**Applies to:** ChatGPT, Claude Code, Claude Design, Claude Cowork, `marketing-growth`, `chief-of-staff`, and Andrew

> ### ⚠ AMENDED 2026-08-03 (v1.3) — ARTICLE AUTHORSHIP HAS MOVED. READ SECTION 20 BEFORE ACTING ON SECTION 3 OR SECTION 8.
>
> **For the weekly blog pipeline, final article prose and the articles' SEO copy are no longer written by ChatGPT.** They are drafted by `marketing-growth`, reviewed by `chief-of-staff`, and gated by **Andrew's own review assisted by an external AI of his choice**.
>
> ⛔ **This amendment is NARROW.** ChatGPT's other lanes — **founder interviews, source-document review where Andrew uses it, and Pinterest creative angles** — are **UNCHANGED and still ChatGPT's**. This is not a repeal of ChatGPT's role.
>
> **The sections below are preserved verbatim and deliberately NOT rewritten**, so a future reader sees what changed rather than re-deriving it. **Where Section 3 or Section 8 assigns final prose or SEO copy to ChatGPT for the weekly blog pipeline, Section 20 governs.**

---

## PILOT LIMITS (active, supersedes conflicting instructions below until Andrew explicitly expands)

Andrew has simplified the pilot process as of v1.2. Until Andrew explicitly
expands the workflow, the following limits are the **active, binding**
operating constraints — they override any older instruction elsewhere in
this document (including the weekly-cadence and four-variant-Pinterest
language below, which described the pre-pilot-simplification target and
is retained for historical context only, not as current instruction):

- One article at a time — no multiple articles in parallel.
- One Pinterest pin per article — no four-variant Pinterest packages.
- One hero crop, adapted from the *same* approved visual concept as the
  pin — no separate creative concept for the hero, no additional visual
  variants.
- No automatic publication of the article.
- No automatic pin publication or scheduling without Andrew's approval.

### Simplified pilot workflow

1. Claude Code identifies **one** qualified article opportunity.
2. Andrew approves the topic.
3. ChatGPT researches and writes the **complete article package**: full
   article, factual sources, SEO title, meta description, slug,
   headings, one topical internal link, one relevant book-discovery
   link, CTA recommendation, one Pinterest title, one Pinterest
   description, and one visual brief.
   > ⚠ **AMENDED 2026-08-03 (v1.3) — step 3 is SPLIT, not deleted.** For the weekly blog
   > pipeline, the **full article, SEO title, meta description, slug and headings** are now
   > drafted by **`marketing-growth`** and reviewed by **`chief-of-staff`**. **The Pinterest
   > title, Pinterest description and visual brief remain ChatGPT's.** See Section 20.
4. Andrew approves the complete editorial package.
   > ⚠ **AMENDED 2026-08-03 (v1.3):** Andrew's approval at this step is now **assisted by an
   > external AI of his choice plus his own knowledge** — the new final quality gate. See Section 20.
5. Claude Code creates the WordPress draft without rewriting the locked
   prose.
6. Claude Design creates **exactly one** Pinterest creative.
7. The same approved visual concept is adapted into the website hero
   crop.
8. Andrew and ChatGPT approve the article, the pin, and the hero crop.
9. Claude Code publishes the article with the hero image and publishes
   or schedules the single pin **at the same time**.
10. Claude Code records the final URL and monitoring dates.

---

## 1. Purpose

This document defines the permanent operating system for turning search data into original, accurate Brave Hearts Publishing content, WordPress drafts, Pinterest assets, published articles, and measurable business results.

The system must automate labor without surrendering editorial judgment.

The commercial path is:

**Real search data → opportunity → approved topic → source review → original article → WordPress draft → Pinterest brief → Claude Cowork photo sourcing (optional, only when real photography must be sourced) → Andrew/ChatGPT asset approval → Claude Design layout (one pin + one hero crop under the v1.2 pilot) → publication → pin → monitoring → better future decisions**

Traffic alone is not the goal. The intended outcome is:

**qualified visitor → useful article → deeper topic exploration → book discovery → email subscriber or buyer → measurable learning**

---

## 2. Source-of-truth hierarchy

When instructions conflict, use this order:

1. Andrew’s explicit current approval
2. This operating system
3. Approved Brave Hearts source documents
4. Approved article assignment
5. Locked article package
6. Repo-specific implementation documentation
7. Automation defaults

No automation may override Andrew’s current instruction or alter locked prose silently.

### Required approved sources for writing

Before ChatGPT writes a Brave Hearts article, it must review the relevant sources:

- `BHP_Brand_Skill.docx`
- `BHP_Life_Story_Skill.docx`
- `Internal_Philosophy_NOT_Book_Volume_I.docx`
  - mapped to **Volume I — The Constitution**
- `Internal_Philosophy_NOT_Book_Volume_II.docx`
  - mapped to **Volume II — The Brave Hearts Experience**
- the relevant canonical manuscript or approved product material
- approved founder anecdote records
- independent authoritative external sources for scientific, historical, educational, medical, or bibliographic claims

A manuscript is not automatically an authoritative source for real-world facts.

---

## 3. Permanent role division

**Pilot role clarification (v1.2, active):** for the current one-article,
one-pin pilot, the roles below are scoped as follows. Where this
clarification and the fuller lists further down disagree on scope
(volume, variant count), this clarification governs.

- **Claude Code**: opportunity identification, cannibalization check,
  WordPress implementation, SEO implementation, taxonomy, links, CTA,
  technical QA, publication after explicit approval, monitoring.
- **ChatGPT**: research, factual review, article writing, SEO copy,
  contextual-link wording, book connection, CTA recommendation,
  single-pin copy and visual brief, editorial review.
  > ⚠ **AMENDED 2026-08-03 (v1.3) — PARTIALLY SUPERSEDED, and the line above is preserved
  > deliberately.** ⛔ *"article writing"* and *"SEO copy"* **are no longer ChatGPT's for the
  > weekly blog pipeline** — they move to `marketing-growth` (draft) → `chief-of-staff`
  > (review) → Andrew (final gate, external-AI assisted). ✅ **Everything else on this line
  > stands, unchanged: research, factual review, contextual-link wording, book connection,
  > CTA recommendation, single-pin copy and visual brief, editorial review.** See Section 20.
- **`marketing-growth`** *(added v1.3)*: drafts the final article prose and the article's
  SEO copy (SEO title, meta description, slug, headings) for the weekly blog pipeline,
  against the existing claims rules and the Section 11 technical QA gate.
- **`chief-of-staff`** *(added v1.3)*: reviews and quality-gates that draft before it
  reaches Andrew. Internal acceptance only — never publication authority.
- **Claude Design**: one final Pinterest layout, one hero crop/adaptation
  from the same creative — no sourcing responsibility, no strategic or
  editorial rewriting.
- **Claude Cowork**: optional — used only when licensed real photography
  must be sourced. Not a mandatory stage for every article.
- **Andrew**: topic approval, final article approval, visual approval,
  publication approval.

### Andrew

Andrew controls:

- topic approval
- founder-story approval
- factual corrections about his life or company
- final article approval
- rendered WordPress approval
- visual approval
- final publication
- production deployment
- strategic risk

### ChatGPT

ChatGPT owns:

- source-document review
- originality assessment
- focused founder interviews when needed
- independent authoritative research
- source interpretation
- factual and historical judgment
- children’s publishing judgment
- age-appropriate language
- Brave Hearts voice
- article structure
- final prose
- SEO title and meta description
- contextual link language
- Pinterest creative strategy and copy
- final editorial review

> ### ⚠ AMENDED 2026-08-03 (v1.3) — THREE ITEMS IN THE LIST ABOVE ARE SUPERSEDED FOR THE WEEKLY BLOG PIPELINE
>
> **The list above is preserved verbatim and was deliberately NOT edited.** For the weekly blog
> pipeline only, these three items move to Claude-side authorship (`marketing-growth` drafts,
> `chief-of-staff` reviews, Andrew gates with external-AI assistance):
>
> | Item in the list above | Status from 2026-08-03 |
> |---|---|
> | **article structure** | ⛔ moves — for weekly blog-pipeline articles |
> | **final prose** | ⛔ moves — for weekly blog-pipeline articles |
> | **SEO title and meta description** | ⛔ moves — for weekly blog-pipeline articles |
>
> ✅ **EVERY OTHER ITEM IN THE LIST ABOVE IS UNCHANGED AND REMAINS ChatGPT's** — including,
> named explicitly because the amendment must not be read as a total repeal:
> **source-document review · originality assessment · focused founder interviews when needed ·
> independent authoritative research · source interpretation · factual and historical judgment ·
> children's publishing judgment · age-appropriate language · Brave Hearts voice ·
> contextual link language · Pinterest creative strategy and copy · final editorial review.**
>
> ⚠ **Scope limit, stated so it is not over-read:** this covers the **weekly blog pipeline**.
> It does not by itself reassign authorship of any other content type. See Section 20.

ChatGPT must not invent:

- Andrew’s experiences
- travel
- memories
- classroom reactions
- parent or teacher feedback
- quotes
- reviews
- book scenes
- character actions
- awards
- facts

### Claude Code

Claude Code owns:

- Search Console refresh and analysis
- content inventory
- cannibalization detection
- opportunity scoring
- weekly slate creation
- article assignment packets
- WordPress draft creation
- staging refreshes
- clean HTML implementation
- Rank Math fields
- existing taxonomy assignment
- BHP classification metadata
- contextual-link implementation from approved text
- CTA configuration
- analytics IDs and UTMs
- Pinterest package mechanics
- technical QA
- publishing only after explicit authorization
- monitoring at 7, 28, and 90 days

Claude Code is not the final article writer or semantic editorial authority.

Claude Code may flag a suspected editorial issue, but it may not silently rewrite locked prose.

### Claude Cowork

**Pilot note (v1.2):** optional. Only invoked when licensed real
photography must be sourced for that article's single visual concept —
not a mandatory stage for every article in the current pilot.

Claude Cowork owns the sourcing and rights-verification stage for real photography and other externally licensed visual assets.

Claude Cowork must:

- search approved commercial-use libraries or clearly licensed public-domain/Creative Commons sources
- prefer genuine, non-AI photography when the campaign is intended to avoid generative-AI labeling
- verify each asset's source, asset ID, creator/photographer, license type, and download record
- confirm whether the source labels the asset as AI-generated or AI-modified
- reject assets with unclear commercial rights
- assemble a visual-source shortlist and asset ledger
- download or organize only the assets Andrew approves
- hand the approved files and ledger to Claude Design
- preserve proof of licensing in the project records

Claude Cowork must not:

- assume that an image is reusable because it appears on Google, Pinterest, a blog, a publisher site, a news site, or a scientific organization site
- use generative fill, AI background extension, AI object replacement, or generated wildlife when the campaign requires non-AI photography
- alter the approved article or Pinterest copy
- publish or schedule pins

### Claude Design

**Pilot note (v1.2):** exactly one final Pinterest layout per article,
plus one hero crop/adaptation from that same creative — no additional
visual variants, no sourcing responsibility, no strategic or editorial
rewriting.

Claude Design owns layout and visual production after the source assets are supplied.

Claude Design may:

- build design templates and placeholders
- place Andrew-approved photographs into the approved layouts
- crop, resize, mask, adjust brightness/contrast/color, add non-generative overlays, typography, borders, logos, and standard graphic elements
- produce Pinterest graphics and blog hero/featured-image adaptations
- apply safe zones, hierarchy, brand colors, and export specifications

Claude Design is treated as a generative design environment. Therefore:

- it must not generate landscapes, animals, children, products, or other scene imagery for Pinterest campaigns that are intended to avoid AI labels
- it must not use generative fill, generative extension, AI object replacement, or generated background repair for those campaigns
- it must use only the approved assets supplied through the Claude Cowork sourcing stage
- if a layout cannot be completed without generating new imagery, it must stop and request another sourced asset

Claude Design must not invent facts, rewrite approved copy, source unlicensed third-party assets, or publish anything.

---

## 4. Weekly production cadence

### Target workload — SUPERSEDED by the v1.2 pilot limits

**The workload target below describes the pre-pilot-simplification
scaling target and is historical context only. It is not the current
active instruction.** The active instruction is the PILOT LIMITS section
at the top of this document: one article at a time, one Pinterest pin
per article, one hero crop from the same visual. Do not act on the
"4 Pinterest variants" or "12 Pinterest concepts total" language below.

<details>
<summary>Historical v1.0/v1.1 target (superseded by v1.2 pilot limits)</summary>

Begin with:

- 3 content items per weekly cycle
- preferably 2 new articles and 1 refresh or optimization
- 4 Pinterest variants per approved article
- 12 Pinterest concepts total

Increase volume only after two complete cycles run smoothly.

</details>

Under v1.2, increase volume only after Andrew explicitly expands the
pilot past the one-article/one-pin limits.

### Work-in-progress limit

Only one article should be under active editorial review at a time.

Allowed states:

1. Proposed
2. Approved for assignment
3. Researching
4. Drafting
5. Awaiting Andrew review
6. Locked
7. In WordPress draft
8. Awaiting design
9. Ready to publish
10. Published
11. Monitoring

No content should remain indefinitely between states.

---

## 5. Weekly cycle

### Phase 1 — Opportunity discovery

**Pilot note (v1.2):** per the active PILOT LIMITS, step 5–6 below are
simplified — Claude Code identifies **one** qualified article
opportunity for Andrew's approval, not up to five with three
recommended. The evaluation process (search-data review, cannibalization
check) is unchanged; only the output volume is reduced.

Claude Code must:

1. Refresh Search Console.
2. Confirm data freshness and normal GSC delay.
3. Review the complete published-content inventory.
4. Check cannibalization before recommending a topic.
5. Identify the single strongest qualified opportunity (historical
   target of "up to five, recommend three" is superseded under the v1.2
   pilot).
6. Present that one opportunity for Andrew's topic approval.

Possible outcomes:

- new article
- refresh
- title/meta improvement
- CTA improvement
- internal-link improvement
- consolidation proposal
- no action

More content is not automatically better.

### Weekly slate requirements

Every opportunity must include:

- working title
- primary query
- secondary queries
- search intent
- audience
- funnel stage
- content type
- Search Console evidence
- competing Brave Hearts URLs
- cannibalization risk
- distinct purpose
- featured product or series connection
- topical hub opportunity
- book-discovery opportunity
- CTA recommendation
- lead-offer decision
- founder-source options
- factual risks
- priority
- preferred recommendation

Claude stops for Andrew’s topic approval.

---

## 6. Article assignment packet

After topic approval, Claude Code gives ChatGPT an assignment packet. Claude does not draft the article.

The packet must contain:

### Search and strategy

- approved title direction
- primary and secondary queries
- audience
- search intent
- funnel stage
- reason the article should exist
- cannibalization findings
- competing pages
- internal-link opportunities
- product path
- CTA recommendation
- lead-offer decision
- existing taxonomy recommendations

### Brand and founder sources

- exact approved source-document paths
- relevant anecdote records
- verification status
- prohibited-use notes
- prior-use count
- whether an originality interview is recommended

### Book and manuscript sources

- canonical status
- product facts
- approved age range
- approved product positioning
- neutral format-selection URL
- uncertainty or missing-source warnings

### Research leads

- authoritative source leads
- known factual risks
- disputed claims
- prohibited formulations
- claims that require independent verification

### Technical requirements

- slug
- Rank Math direction
- classification fields
- contextual-link requirements
- CTA mode
- image constraints
- Pinterest naming conventions

---

## 7. Originality gate and founder interviews

Before writing, ChatGPT must decide whether the approved corpus provides enough original Brave Hearts perspective.

Trigger a focused interview when:

- no founder anecdote genuinely fits
- the same anecdote is being reused too often
- the topic needs Andrew’s actual opinion
- the article is accurate but generic
- a new book introduces undocumented themes
- classroom, nursing, travel, outdoor, publishing, or reluctant-reader experience would materially strengthen the article

### Interview workflow

1. Identify the missing perspective.
2. Ask Andrew focused questions.
3. Capture exact answers.
4. Separate fact, opinion, memory, and interpretation.
5. Ask Andrew to approve the extracted source record.
6. Create a structured source document containing:
   - interview date
   - topic
   - exact source
   - approved facts
   - usable anecdotes
   - approved quotes
   - allowed interpretations
   - unverified claims
   - prohibited uses
   - related audiences and books
   - prior-use count
   - reuse limits
   - canonical or provisional status
7. Add it to the private corpus.
8. Use it only within the approved boundaries.

Do not force an interview when a fact-forward article does not need a founder anecdote.

---

## 8. ChatGPT article-writing requirements

> ### ⚠ AMENDED 2026-08-03 (v1.3) — THIS SECTION'S REQUIREMENTS SURVIVE; ITS AUTHOR CHANGES
>
> **The section title and every requirement below are preserved verbatim.** For the weekly blog
> pipeline, **read "ChatGPT" in this section as `marketing-growth` (drafting) and
> `chief-of-staff` (reviewing)**. ⭐ **Not one editorial or factual requirement in this section is
> relaxed by the change of author** — the standard is the standard regardless of who writes to it.
> **The Pinterest title, Pinterest description and visual brief named below remain ChatGPT's.**
> See Section 20.

**Pilot note (v1.2):** ChatGPT's complete article package now includes,
in the same delivery: full article, factual sources, SEO title, meta
description, slug, headings, one topical internal link, one
book-discovery link, CTA recommendation, one Pinterest title, one
Pinterest description, and one visual brief — not a multi-variant
Pinterest creative-strategy deliverable.

ChatGPT writes one article at a time.

Every final article must include:

- useful standalone value
- truthful Brave Hearts perspective
- age-appropriate language
- independent factual verification
- no fabricated personal material
- no exaggerated claims
- no generic filler
- no unsupported medical, psychological, scientific, historical, or educational outcomes
- clean heading hierarchy
- a natural close
- contextual internal links
- a clear book-discovery path

### Required contextual links

Every article must contain, unless Andrew explicitly approves an exception:

1. **At least one topic link**
   - relevant hub
   - collection
   - resource page
   - category archive
   - related pillar

2. **At least one book-discovery link**
   - relevant Brave Hearts book
   - neutral format-selection page
   - books/series collection page
   - approved Amazon affiliate link

The automatic CTA does not replace these contextual links.

All destinations must be verified before publication.

### Optional Amazon affiliate links

Amazon affiliate links may be included when:

- they are approved
- the commercial relationship is clear where needed
- an on-site direct-sales or neutral format-selection path remains primary when strategically appropriate
- the article does not become a wall of affiliate links

---

## 9. Locked article package

Once Andrew approves the exact article, it becomes locked.

The locked package must include:

- final title
- final body
- approved contextual links and anchor text
- SEO title
- meta description
- focus keyword
- secondary keywords
- proposed slug
- audience
- funnel stage
- content intent
- primary goal
- featured product
- primary category
- approved existing tags
- CTA mode
- lead-offer status
- image direction
- alt-text direction
- source record
- factual caveats
- approval date

Claude Code may perform only mechanical formatting.

Claude Code must stop and report, rather than silently edit, if it detects a suspected factual, grammatical, legal, brand, or technical problem.

---

## 10. WordPress implementation rules

### New article

After receiving the locked package, Claude Code creates the article directly on the main WordPress site as:

- post type: `post`
- post status: `draft`
- no scheduling
- no automatic publication

Claude must:

1. Confirm production identity.
2. Confirm slug uniqueness.
3. Resolve all existing taxonomy terms by ID.
4. Create the draft.
5. Read the status back.
6. Apply metadata and classifications.
7. Apply approved contextual links.
8. Configure the CTA.
9. Verify the draft remains absent from public archives and sitemap.
10. Return an authenticated preview.

### Existing published article refresh

Use staging first.

Claude must:

1. Capture rollback state.
2. Apply the locked refresh to staging.
3. Return a rendered preview.
4. Wait for approval.
5. Apply a guarded field-level production update only after explicit authorization.

### SEO-only or technical corrections

Narrow SEO metadata, taxonomy, and link repairs may be made directly to production only after explicit scope approval, drift check, rollback capture, and post-write verification.

---

## 11. Technical QA gate

A WordPress package must fail if any of the following are present:

### HTML

- Squarespace markup
- `sqsrte-`
- `data-rte-preserve-empty`
- `white-space:pre-wrap`
- nested paragraph tags
- empty paragraph tags
- unmatched tags
- stray body H1
- malformed CTA classes
- editor instructions
- placeholders

### Environment and links

- staging URLs in production-ready content or metadata
- hardcoded internal `www` hostname
- broken or placeholder links
- unverified destination URLs
- missing topic link
- missing book-discovery link

### Taxonomy

- missing intended category or tags
- comma-joined accidental terms
- automatic taxonomy creation
- assignment by unverified name strings
- unexpected extra terms
- category/article-type mismatch

Taxonomy must be:

1. resolved to existing IDs
2. assigned by ID
3. read back
4. compared with the expected exact set

### Classification

Required:

- audience
- funnel stage
- content intent
- primary goal
- featured product when applicable
- CTA mode
- lead-offer status

No post may pass as “Unclassified” or by flat defaults.

### CTA

The package must explicitly choose:

- manual CTA only
- automatic CTA only
- both with distinct purposes and adequate separation
- no CTA

Contextual text links are not CTA collisions.

### Publication safety

- status must remain draft unless explicitly authorized
- no automatic publishing
- no unrelated production changes
- no theme/plugin/GTM/analytics deployment hidden inside content work

---

## 12. Rendered WordPress review

> ⚠ **AMENDED 2026-08-03 (v1.3):** for weekly blog-pipeline articles, the reviewer pairing on the
> **article prose and SEO copy** is now **Andrew plus an external AI of his choice, plus his own
> knowledge** — because ChatGPT is no longer the author of that material and a checker should not
> be the writer. ✅ **ChatGPT's review of the Pinterest creative is unchanged.** The review
> checklist below is unchanged in every particular. See Section 20.

Andrew and ChatGPT review the real WordPress draft for:

- title
- introduction
- spacing
- heading hierarchy
- readability
- contextual links
- CTA placement
- mobile layout
- desktop layout
- category display
- metadata
- overall brand feel

No Pinterest image is required before draft creation.

---

## 13. Pinterest and visual-production workflow

**SUPERSEDED by the v1.2 pilot limits.** Pinterest creative planning may
begin after the title and core article are stable. Final destination
URLs and UTMs must be verified before publishing pins.

**Active instruction (v1.2): every article receives exactly ONE
Pinterest creative, sourced from the single Pinterest title/description/
visual brief ChatGPT supplies as part of the complete article package
(Section 8). The website hero crop is adapted from that same approved
visual concept — not a separate creative concept. No four-variant
package is produced under the current pilot.**

<details>
<summary>Historical v1.0/v1.1 four-variant target (superseded by v1.2 — retained for historical context only, do not act on this)</summary>

Every article receives four variants:

1. Problem-led
2. Outcome-led
3. Curiosity-led
4. Resource/list-led

</details>

### ChatGPT supplies

**Pilot note (v1.2): one of each, not one per variant** — the fields
below describe a single Pinterest creative's content, delivered as part
of the complete article package in Section 8.

- strategic angle
- on-image headline
- supporting copy
- visual concept
- Pinterest title
- description
- CTA
- alt text
- factual restrictions
- rights restrictions
- whether the campaign requires real non-AI photography

### Claude Code supplies

- campaign ID
- variant ID (single variant under the v1.2 pilot)
- destination URL
- UTM values
- filename
- board recommendation
- dimensions
- safe zones
- production packet

### Claude Cowork supplies

When real photography or licensed external assets are required, Claude Cowork:

1. Searches approved libraries.
2. Produces a shortlist for each visual role.
3. Records:
   - source platform
   - asset title
   - asset ID
   - creator/photographer
   - license type
   - direct source record
   - AI-generated/AI-modified status when provided
   - intended design use
4. Rejects unclear or noncommercial rights.
5. Presents the shortlist to Andrew and ChatGPT.
6. Downloads or organizes only approved assets.
7. Preserves proof of license.
8. Hands the approved files and asset ledger to Claude Design.

### Andrew and ChatGPT approve the assets

Before final design production, Andrew and ChatGPT review:

- subject accuracy
- real-photo status
- ecological or factual fit
- visual quality
- brand fit
- rights clarity
- whether the crop will support the copy and safe zones

No unapproved asset proceeds to final design.

### Claude Design supplies

Claude Design receives:

- the approved layout brief
- approved copy
- approved photo files
- asset ledger
- dimensions and safe zones

Claude Design then:

- places the supplied photos
- performs standard non-generative graphic design
- applies typography, brand colors, overlays, logos, borders, and crops
- produces Pinterest graphics and blog adaptations
- returns requested exports

### Non-AI Pinterest campaigns

When avoiding Pinterest AI labeling is a campaign requirement:

Use only:

- real, licensed photography
- owned human-created photography
- clearly verified public-domain photography
- standard non-generative graphic-design operations

Do not use:

- generated landscapes or animals
- AI-generated book illustrations as dominant imagery
- generative fill
- generative background extension
- AI object replacement
- synthetic children or wildlife
- source assets labeled AI-generated or AI-modified
- unlicensed web images

Because platform detection can still be imperfect, no workflow can guarantee that Pinterest will never apply an AI label. The process must minimize that risk and preserve the asset/license trail.

### General rights rules

Use:

- owned Brave Hearts assets where appropriate
- original human-created photography
- licensed commercial photography
- verified public-domain assets
- generated assets only when Andrew explicitly approves AI imagery for that campaign

Do not use third-party book covers or copyrighted art without permission.

### Standard deliverables

**Pilot note (v1.2):** these are export formats of the **single** approved
creative concept, not separate creative variants — the Pinterest pin and
the blog hero crop both derive from that one concept.

- Pinterest 1000 × 1500
- PNG master
- WebP export
- mobile-safe version
- blog hero adaptation (from the same approved visual concept as the pin)
- headline-free featured-image version where useful
- completed asset ledger
- license proof/reference record

The blog featured image and the Pinterest pin do not need to be
pixel-identical (different crops/safe zones are expected), but both come
from the same one approved creative concept under the v1.2 pilot.

---

## 14. Visual approval and featured image

Andrew and ChatGPT review the sourced assets before design and the finished designs after layout.

Asset review covers:

- genuine photography/non-AI status where required
- factual and ecological accuracy
- photographer/source/license record
- commercial-use rights
- brand fit
- composition and crop potential

Final-design review covers:

- mobile readability
- factual accuracy
- visual hierarchy
- brand consistency
- child appropriateness
- rights safety
- click potential
- overcrowding
- obvious AI artifacts or generative modifications

After approval:

1. Select the featured image.
2. Upload it to the WordPress draft.
3. Use a descriptive filename.
4. Add accurate alt text.
5. Check desktop and mobile previews again.
6. Approve the final draft.

---

## 15. Publication

Nothing publishes automatically.

Andrew gives explicit publication approval — covering the article, the
hero image, and the pin together.

**Pilot note (v1.2):** once Andrew and ChatGPT approve the article, the
pin, and the hero crop (Section 3 pilot workflow step 8), Claude Code
publishes the article with the hero image and publishes or schedules the
single pin **at the same time** — not as separate, staggered approvals.
No automatic pin publication without that same explicit approval.

After publication, Claude Code must:

- confirm final public URL
- confirm 200 status
- verify sitemap inclusion
- verify canonical
- verify one CTA
- verify contextual topic and book links
- verify category/tags
- finalize Pinterest URLs and UTMs
- record publication date and campaign IDs

Pins may then be scheduled or published.

---

## 16. Monitoring

Every published article receives:

### Day 7

- indexing
- canonical
- rendered title
- broken links
- CTA function
- technical anomalies

### Day 28

- impressions
- clicks
- CTR
- average position
- primary-query movement
- Pinterest impressions
- outbound clicks
- email signups where available

### Day 90

- durable query performance
- traffic quality
- CTA clicks
- product visits
- attributable sales
- subscriber conversion
- refresh or consolidation decision

Do not treat seven-day volatility as a strategic verdict.

---

## 17. Next major build after Weekly Cycle 1

Continue the weekly content cycle while building:

1. Adventure Kit and lead-magnet audit
2. Audience-specific landing pages
3. Parent, teacher, and homeschool segmentation
4. Mailchimp welcome and nurture sequences
5. Subscriber-source attribution
6. Subscriber-to-product tracking
7. Subscriber-to-sale tracking
8. GA4 reporting access

Paid acquisition comes only after the organic-to-email-to-sale path is measurable.

---

## 18. Nonnegotiable boundaries

Do not:

- fabricate
- publish without approval
- deploy production code without approval
- publish GTM without approval
- activate analytics consent without approval
- change URLs casually
- create taxonomy automatically
- merge or delete content without approval
- expose private manuscripts
- use staging links in production content
- allow locked prose to drift silently
- produce Pinterest visuals before rights and factual constraints are defined
- ask Claude Design to source real photography; real-photo sourcing belongs to Claude Cowork
- use sourced photography without an asset ID, license record, and approval trail
- use traffic volume as the only measure of success
- run multiple articles in parallel, produce more than one Pinterest pin per article, or create a separate hero-crop creative concept, while the v1.2 pilot limits are active

---

## 19. Change control

This document is canonical.

Any permanent process change must:

1. be explicitly approved by Andrew
2. update this document first
3. increment the version
4. record the change in the log
5. update templates and repo instructions
6. avoid contradictory hidden instructions elsewhere

### Change log

| Version | Date | Change |
|---|---|---|
| 1.0 | 2026-07-11 | Initial canonical workflow covering topic discovery, source review, writing, production drafts, links, QA, Pinterest, publishing, and monitoring |
| 1.1 | 2026-07-11 | Added Claude Cowork as the real-photo sourcing and license-verification owner; limited Claude Design to layout using supplied approved assets for non-AI Pinterest campaigns |
| 1.2 | 2026-07-11 | Andrew simplified the pilot process. Reduces the active workflow to one article at a time with one Pinterest pin and one hero crop from the same visual concept, superseding the v1.0/v1.1 targets of up to 3 content items/week, four Pinterest variants per article, and 12 Pinterest concepts total (retained below as historical context only, not current instruction). Claude Cowork is now explicitly optional per article. Claude Design is limited to exactly one Pinterest layout plus one hero crop from the same creative. The article is now published together with its hero image and its single pin, on the same explicit approval, rather than as separate staggered steps. The required two-link-class contextual-link policy (topic hub + book discovery) is unchanged and remains fully active. |
| 1.3 | 2026-08-03 | **The G-1 authorship amendment.** Final article prose and the articles' SEO copy (SEO title, meta description, slug, headings) for the **weekly blog pipeline** move from ChatGPT to Claude-side authorship: drafted by `marketing-growth`, reviewed by `chief-of-staff`, quality-gated against the unchanged Section 11 QA gate and the unchanged claims/never-invent rules, with a **new final gate — Andrew's own review assisted by an external AI of his choice and his own knowledge.** ⛔ **Narrow by design.** ChatGPT's other lanes — founder interviews, source-document review where Andrew uses it, Pinterest creative angles and copy, contextual-link wording, factual/historical judgment, editorial review — are **explicitly UNCHANGED**. Sections 3, 8 and 12 annotated in place with the original text preserved; full amendment at Section 20. Ruled by Andrew Signore 2026-08-03. |

---

## 20. The G-1 authorship amendment — v1.3, 2026-08-03

**Added 2026-08-03 under Section 19 change control.** Section 19 requires that a permanent process
change be approved by Andrew, update this document first, increment the version, and be recorded in
the change log. **All four were done in this sitting.**

### 20.1 The ruling

Andrew Signore ruled on **2026-08-03**, in the weekly blog-pipeline discussion, that the articles
are to be written on the Claude side rather than by ChatGPT, and that **he** will check the work
using another AI of his choosing together with his own knowledge.

> ⚠ **Why this is stated in substance here rather than word-for-word.** Andrew's exact sentence
> names an **internal call name**. Internal call names may never appear in this repository, which
> is **public on GitHub**. **The verbatim ruling is recorded, unaltered, in the private company
> record — `Business OS\FOUNDER-DECISIONS-2026-08-01.md`, FD-94** — together with its scope
> statement and what it supersedes. **This is a pointer to where the private record lives, by path;
> it is not a summary of anything else that record contains.**
>
> Rendered with the call name resolved to its technical ID, and otherwise exactly as spoken
> (including Andrew's own spelling): *"I rather have you [`chief-of-staff`] right* [sic] *them - not
> chatgpt. I will check your work with another AI and my own knowledge. Change on the canon please."*

### 20.2 ⛔ Scope — deliberately narrow, and it must not be read wider

**WHAT MOVES — and only this:**

| Deliverable | Was | Is, from 2026-08-03 |
|---|---|---|
| Final article prose | ChatGPT | **`marketing-growth`** drafts → **`chief-of-staff`** reviews |
| Article SEO copy — SEO title, meta description, slug, headings | ChatGPT | **`marketing-growth`** drafts → **`chief-of-staff`** reviews |
| Final quality gate on the two rows above | Andrew and ChatGPT | ⭐ **Andrew's own review, assisted by an external AI of his choice, plus his own knowledge** |

⛔ **It applies to the weekly blog pipeline.** It does not by itself reassign authorship of product
copy, landing-page copy, email copy, manuscripts, or any other content type.

**WHAT IS EXPLICITLY UNCHANGED — listed so this amendment cannot be read as a total repeal of
ChatGPT's role, because it is not one:**

- ✅ **Founder interviews** — Section 7's originality gate and interview workflow are ChatGPT's, unchanged.
- ✅ **Source-document review**, where Andrew uses ChatGPT for it — unchanged.
- ✅ **Pinterest creative angles, copy, and the visual brief** — unchanged, still ChatGPT's, still delivered as part of the article package.
- ✅ Independent authoritative research · source interpretation · factual and historical judgment · children's publishing judgment · age-appropriate language · Brave Hearts voice · contextual-link wording · final editorial review — **all unchanged.**
- ✅ **Every editorial and factual requirement in Section 8** applies identically to the new author. A change of author is not a change of standard.
- ✅ **The Section 11 technical QA gate** — HTML, environment/links, taxonomy, classification, CTA, publication safety — **unchanged in every particular.**
- ✅ **The claims rules are unchanged and absolute**: the never-invent list in Section 3 and the Section 18 boundaries bind `marketing-growth` exactly as they bound ChatGPT. **Reviews, ratings, testimonials, classroom results, statistics, awards, endorsements and founder experiences are never invented, by any author.**
- ✅ **Section 10's publication rule is untouched:** new articles go to production as `draft` only. **Nothing publishes without Andrew's explicit, current-turn authorization.**
- ✅ **`Claude Code`'s implementation role, `Claude Cowork`'s sourcing/ledger role and `Claude Design`'s layout-only role are all unchanged.**

### 20.3 The weekly cycle of authorship

| # | Stage | Owner |
|---|---|---|
| 1 | Friday slate — qualified opportunities identified and presented | `Claude Code` / repo side |
| 2 | Andrew approves the slate | **Andrew** |
| 3 | Three articles drafted — prose + SEO copy | **`marketing-growth`** |
| 4 | Review and quality gate against Sections 8, 11 and the claims rules | **`chief-of-staff`** |
| 5 | Image sets produced | **`design-creative`** |
| 6 | ⭐ Weekly batch approval — **Andrew, assisted by an external AI of his choice and his own knowledge** | **Andrew** |
| 7 | Production WordPress **drafts** created per Section 10 | `Claude Code` / repo side |
| 8 | Publication | ⛔ **Andrew only, explicit and current-turn. Unchanged.** |

⛔ **`chief-of-staff` acceptance at stage 4 means "ready for Andrew's review". It is never publication authority and never a substitute for stage 6 or stage 8.**

### 20.4 ⚠ ONE OPEN CONTRADICTION — REGISTERED, NOT RESOLVED

**The weekly cycle above describes a three-article weekly cadence. The PILOT LIMITS section at the
top of this document says "One article at a time — no multiple articles in parallel", and declares
itself active and binding "until Andrew explicitly expands" it.**

⛔ **Both statements are in this document and they conflict on volume. This amendment does NOT
resolve that, and did NOT amend the pilot limits** — Andrew's ruling recorded here was about
**authorship**, not about volume, and widening it would be inference rather than instruction.

**Registered as `CYCLE142-OPS-033`. Status: OPEN, waiting on Andrew.** Whether the v1.2
one-article-at-a-time pilot limit is expanded to three articles per week is **Andrew's call and his
alone**, under this document's own Section 19 change control. **Until he rules, the PILOT LIMITS
section governs volume and Section 20 governs authorship.**

### 20.5 ⛔ What this amendment does NOT do

- ⛔ **It grants no agent any capability.** It records who writes what; runtime permissions live elsewhere and were not touched.
- ⛔ **It authorises no publication, no production write and no deployment.** Section 10 and Section 15 are unchanged.
- ⛔ **It resolves no contradiction** — `CYCLE142-OPS-033` is registered above and left open.
- ⛔ **It relaxes no factual, claims, QA or approval standard.** Every one of them survives the change of author intact.
- ⛔ **It does not repeal ChatGPT's role.** Section 20.2 lists what remains ChatGPT's, explicitly.
- ⛔ **No private company material was written into this public repository** — this section carries a record ID, a status, dates, safe operational facts and one path pointer, and nothing else.
