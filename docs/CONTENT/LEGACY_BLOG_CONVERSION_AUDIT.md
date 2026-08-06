# Legacy Blog Conversion Audit

## ⚠ Correction (found and fixed during this same session, before this doc was finalized)

The first pass of this audit scanned only each post's raw `post_content` field via a server-side script, and concluded 9 posts had "zero topic cross-links." **Live-rendered spot-checks of 2 of those 9 posts (68 and 26) on production disproved that** — both render a theme-level, automatic "Related Field Notes" grid (4 contextually-matched post links) plus a "Follow This Trail Further" hub-link block, via `bhp_get_guide_registry()` (per `CONTENT/BLOG_STATUS.md`, this applies to **all 35** registry posts, independent of what's hand-placed in `post_content`). This audit's original content-only scan could not see that component at all, since it's rendered at the template level, not stored in the post body.

**Practical effect on this audit's conclusions:** every one of the 35 posts almost certainly already has a real, working topic-hub link via this automatic component — the "9 critical missing-link posts" framing below overstates the topic-link gap and should be read as **"posts with no additional hand-placed topic link beyond the automatic one,"** not **"posts with no topic-hub link at all."** The genuine, still-real gap is narrower: whether each post also has a **book-discovery link**, and whether that link points to the *right* book for that post's topic — that part of the audit still holds, though a second methodology gap was found there too: the original `amzn:` flag only matched literal `amzn.to` short links and missed full `www.amazon.com/...` URLs (confirmed on post 68, which has a working `amazon.com` purchase link this audit's regex scored as absent).

**What was not re-verified:** all 35 posts were not individually re-checked live after finding this — only 2 were spot-checked to confirm the pattern. The per-post table below is left as originally generated (content-only signals) with this correction as the governing caveat; treat "topic link: No" entries in the table as "no *additional* hand-placed link," not "no link at all," and treat "book link: No" as "not matched by the `amzn.to`-only regex," which may undercount real `amazon.com` links.

---

**Date: 2026-07-13 (overnight build, Phase 8).** Read-only audit of all 35 pre-CTA-Engine production blog posts (everything published before post 366, "10 Amazon Rainforest Facts for Kids," which is the one already-compliant modern post — see `docs/taxonomy-repair-audit-2026-07-10.md` and the prior "compliance review of post 366" session for that post's own record). **No posts were edited.** Method: server-side `wp eval-file` script inspecting each post's real `post_content`, categories, and tags directly (not a browser walkthrough of all 35 — see Known limitation below).

## Architectural finding (applies to all 35 posts)

None of these posts use the theme's CTA Engine (`bhp_contextual_cta` shortcode) or link to this site's own WooCommerce `/shop/` or `/product/` pages **at all** — confirmed 0/35 for both. Instead, every post that has a "buy the book" link uses an Amazon affiliate short link (`amzn.to/...`) and/or a `linktr.ee` link, plus (in many posts) a large hardcoded HTML footer block — a "Supportive Blog List" of other post URLs and a promo block for **The Mariana Trench specifically** (title, tagline, Amazon link). This footer boilerplate appears to be inherited from the pre-migration (Squarespace-era) content and predates both the CTA Engine and the WooCommerce storefront becoming the primary purchase path.

**The single most actionable finding:** only **1 of 35 posts** (post 46, in passing) mentions "The Amazon" — published 2026-06-26 as the third book, after all 35 of these posts were written. The evergreen content has not been updated to cross-promote it. Every post's automatic "book discovery" link, where present, points exclusively to the Mariana Trench Amazon listing.

## Full matrix

| ID | Slug | Pub date | Category(ies) | Topic link (blog cross-links) | Book link (Amazon) | Teacher link | Everest mention | Mariana mention | Amazon(3rd book) mention | Word count |
|---|---|---|---|---|---|---|---|---|---|---|
| 100 | why-stem-storytelling-builds-braver-kids | 2026-03-01 | Uncategorized | 16 | Yes | Yes | Yes | Yes | Yes | 725 |
| 68 | how-stories-build-resilience-in-children | 2026-03-02 | Uncategorized | 0 | No | Yes | No | Yes | No | 458 |
| 26 | adventure-books-for-kids-ages-6-9 | 2026-03-03 | Uncategorized | 0 | No | No | No | Yes | No | 342 |
| 66 | how-deep-is-the-mariana-trench-for-kids | 2026-03-06 | Uncategorized | 0 | No | Yes | Yes | Yes | No | 373 |
| 30 | best-bridge-books-for-kids | 2026-03-08 | Uncategorized | 0 | No | Yes | No | Yes | No | 672 |
| 60 | first-real-chapter-book-for-kids | 2026-03-10 | Uncategorized | 3 | No | Yes | No | Yes | No | 472 |
| 62 | free-teachers-guide-mariana-trench | 2026-03-20 | Uncategorized | 4 | No | No | Yes | Yes | No | 980 |
| 32 | best-early-chapter-books-for-6-year-olds | 2026-04-03 | Uncategorized | 5 | No | Yes | No | Yes | No | 432 |
| 52 | bridge-books-for-kids-mount-everest | 2026-04-08 | Uncategorized | 6 | No | Yes | Yes | Yes | No | 531 |
| 48 | bridge-books-for-early-readers | 2026-04-11 | Uncategorized | 16 | Yes | Yes | Yes | Yes | No | 1092 |
| 90 | what-are-bridge-books-guide-for-parents-and-teachers | 2026-04-13 | Bridge Books | 8 | Yes | Yes | Yes | Yes | No | 1251 |
| 74 | mariana-trench-facts-for-kids | 2026-04-15 | Uncategorized | 9 | Yes | Yes | Yes | Yes | No | 1299 |
| 34 | best-ocean-books-for-kids-ages-6-9 | 2026-04-18 | Book recommendations, Ocean Science | 10 | Yes | Yes | Yes | Yes | No | 1046 |
| 50 | bridge-books-for-kids | 2026-04-23 | Ages 6-9, Bridge Books, Early Readers, Reading Tips | 11 | Yes | Yes | Yes | Yes | No | 1852 |
| 88 | top-bridge-books-for-kids | 2026-04-27 | Bridge Books, Early Readers, Reading Tips | 12 | Yes | Yes | Yes | Yes | No | 1170 |
| 92 | what-is-a-lexile-score | 2026-04-29 | Classroom Resources, Reading Tips | 13 | Yes | Yes | Yes | Yes | No | 1010 |
| 86 | teacher-appreciation-week-thank-you | 2026-04-30 | Author Life, Teacher Resources | 0 | Yes | No | No | Yes | No | 585 |
| 78 | my-child-got-a-lexile-score-now-what | 2026-05-01 | Uncategorized | 14 | Yes | Yes | Yes | Yes | No | 1119 |
| 36 | best-read-aloud-books-for-classroom-grades-1-3 | 2026-05-01 | children's books, Teacher Resources | 14 | Yes | No | Yes | Yes | No | 1115 |
| 94 | what-is-the-mariana-trench-for-kids | 2026-05-04 | Ages 6-9, Book recommendations, Bridge Books, The Mariana Trench | 3 | Yes | No | Yes | Yes | No | 882 |
| 82 | reading-level-by-grade-chart | 2026-05-04 | Reading Levels & Lexile, Reading Tips for Parents | 0 | Yes | Yes | No | Yes | No | 984 |
| 70 | how-to-pick-a-read-aloud-book | 2026-05-05 | Classroom Resources, Read alouds, Reading Tips | 0 | Yes | No | No | Yes | No | 1004 |
| 80 | my-child-hates-reading-what-to-do | 2026-05-07 | Reading Help for Parents, Reluctant Readers | 0 | Yes | No | No | Yes | No | 926 |
| 54 | bridge-books-for-struggling-readers | 2026-05-09 | Bridge Books, Reading Help for Parents, Reluctant Readers | 0 | No | No | Yes | Yes | No | 1134 |
| 76 | mount-everest-facts-for-kids | 2026-05-09 | Adventure, Book recommendations, science for kids | 0 | No | No | **Yes (only)** | **No** | No | 1303 |
| 58 | finding-right-books-with-lexile-score | 2026-05-10 | Bridge Books, Reading Help for Parents, Reading Levels & Lexile | 0 | Yes | No | Yes | Yes | No | 1225 |
| 98 | why-i-wrote-this-book | 2026-05-10 | Adventure Book Recommendations, Author Life | 0 | Yes | Yes | Yes | Yes | No | 1461 |
| 64 | gap-between-picture-books-and-chapter-books | 2026-05-15 | Books for Kids, Bridge Books, Reading Tips | 0 | Yes | Yes | Yes | Yes | No | 1404 |
| 46 | books-like-magic-tree-house | 2026-05-16 | Book recommendations, Bridge Books, Reading Tips | 0 | Yes | No | Yes | Yes | Yes | 1922 |
| 38 | best-summer-reading-books-for-kids-ages-6-9 | 2026-05-21 | Bridge Books, Summer Reading | 8 | Yes | Yes | Yes | Yes | No | 1578 |
| 28 | best-books-for-7-year-olds | 2026-05-25 | Age-Specific Reading Lists, Bridge Books | 10 | Yes | Yes | Yes | Yes | No | 1562 |
| 96 | what-to-read-after-dog-man | 2026-05-26 | Bridge Books, Reading Lists, Reluctant Readers | 10 | Yes | Yes | Yes | Yes | No | 1888 |
| 72 | kirkus-review-adventures-of-charlotte-and-henry | 2026-05-27 | Author Life, Book News | 10 | Yes | Yes | Yes | Yes | No | 1123 |
| 56 | dog-man-to-magic-tree-house-reading-roadmap | 2026-06-01 | Book recommendations, Bridge Books, Reading Development | 5 | Yes | Yes | Yes | Yes | No | 1298 |
| 84 | science-books-for-kids-that-feel-like-adventures | 2026-06-05 | Adventure, Book recommendations, STEM | 5 | Yes | Yes | Yes | Yes | No | 1055 |

## Contextual-link policy compliance (per `docs/required-links-policy.md`'s intent, applied retroactively)

None of the 35 posts satisfy the *current* required-links policy in the way it's now enforced for new articles (a topical hub link + a book-discovery link, both in-body and editorially chosen). What they have instead:
- **A hardcoded footer "Supportive Blog List"** block (present on many, not all — 0 topic-link-count posts like 68, 26, 66, 30, 86, 82, 70, 80, 54, 76, 58, 64, 94 lack even this) that functions as a generic in-body cross-link, but isn't topically curated per-post.
- **A hardcoded Mariana Trench-only book promo** in the footer of most posts — functions as *a* book-discovery link, but never varies by which book the post is actually about (Post 76, entirely about Mount Everest, still would only promote Mariana Trench if it has the standard footer — worth a direct spot-check before remediation).

## Critical missing-link posts (9 posts with zero topic cross-links AND no clear per-post book match)

`68`, `26`, `66`, `30`, `86`, `82`, `70`, `80`, `54` — these either have 0 blog cross-links, no Amazon link, or both. Several are also still in the "Uncategorized" WordPress category, a real taxonomy gap independent of the link question.

## Highest-priority posts for remediation

Prioritized by a combination of (a) genuinely book-mismatched content, (b) zero-link posts, and (c) the-Amazon-invisibility problem — **traffic/GSC data was not re-pulled this pass** (would need a fresh Search Console sync to rank by real traffic; the closest available proxy is `docs/weekly-slate-1-monitoring.md` and the bridge-books cluster's own GSC data, now private — see `CSO_PRIVATE_REFERENCE.md`):

1. **Post 76 (`mount-everest-facts-for-kids`)** — the one post that is purely Everest-specific with zero Mariana mention. If it has the generic Mariana-only footer promo (not independently re-verified this pass, flagged for direct spot-check), that's a genuine book/content mismatch — an Everest-facts post should promote the Everest book, not default to Mariana.
2. **Posts `68`, `26`, `66`, `30`, `86`, `82`, `70`, `80`, `54`** — zero topic cross-links; several also zero book-discovery link. Easiest, lowest-risk remediation batch (add links, no rewrite needed).
3. **Any post that could naturally introduce "The Amazon"** — none currently do except post 46 in passing. Candidates for a natural mention: `100` (STEM storytelling, already mentions all 3 broadly), `34` (ocean books — natural tie-in to a rainforest book is weaker than to Mariana, use judgment), `84` (science books for kids).
4. **Uncategorized posts** (`100`, `68`, `26`, `66`, `30`, `60`, `32`, `48`, `78`) — 9 posts with no real WordPress category, a taxonomy gap independent of linking.

## Complete Collection relevance

**Zero of the 35 posts mention "Complete Collection" in any form** — not a terminology-error problem (nothing is *wrong*), but a genuine missed-opportunity gap: none of this evergreen content ever offers the Collection as an option, only ever the single Mariana Trench book via the footer boilerplate.

## Adventure Kit relevance

Not independently measured this pass — would require checking each post's rendered page for the Adventure Kit popup/CTA placement (a sitewide/template-level component, not something embedded in `post_content` itself, so it wasn't visible to this content-only audit method). Flagged as a real gap in this audit's method — see Known limitation below.

## Posts requiring no change

None identified as fully complete against the current policy — every post is missing at least the Complete Collection option and/or a topically-varied (not just Mariana-default) book link. This is expected: all 35 predate the current policy by design.

## Recommended remediation batches

**Batch 1 (lowest risk, no prose rewrite needed):** Add a topic-hub link and a book-discovery link to the 9 zero-link posts (`68`, `26`, `66`, `30`, `86`, `82`, `70`, `80`, `54`) via the existing contextual-link mechanism, staging first, per `.claude/rules/` and the content-operations policy.

**Batch 2 (needs a content judgment call, likely ChatGPT/Andrew):** Spot-check post 76 for the Mariana-default footer mismatch; if confirmed, swap its book-discovery link to point to Mount Everest instead.

**Batch 3 (new-book visibility, needs ChatGPT copy):** Draft a short "you might also like The Amazon" insertion for 2-3 well-matched posts (start with `100`, `84`) — this needs real prose, not a link swap, since introducing a third book requires a sentence of context, not just a URL.

**Batch 4 (taxonomy only, no content change):** Assign real categories to the 9 "Uncategorized" posts.

## Copy assignments for ChatGPT

- Batch 3's "introduce The Amazon" sentences for posts `100` and `84` (2 short insertions, not full rewrites — per the "never silently rewrite locked prose" rule, existing prose stays untouched, only new sentences are added).
- Batch 2's Everest-specific book-discovery link copy for post 76, if the mismatch is confirmed.

## Phase 9 — remediation packets (prepared, not implemented)

Implementation packets for the 5 highest-priority posts, per the priority list above. **Nothing here has been applied — staging-first, per the standing workflow.** No final prose invented; only insertion points and destinations are specified, matching the existing excerpt style.

### Packet 1 — Post 76, `mount-everest-facts-for-kids`
- **Current state (per the correction above, not independently live-verified for this specific post):** the content-only scan shows **zero** hand-placed book-discovery link and **zero** hand-placed topic cross-links (`amzn:0, teachers:0, bloglinks:0`). Per the systemic correction above, it almost certainly still gets the automatic Related Field Notes grid — but that automatic grid pulls by category/tag overlap, not by book relevance, so it would **not** fix the real issue this packet targets: no explicit, editorially-chosen Everest-book link exists in this post yet, hand-placed or automatic. Real opening excerpt: *"My friend's kid came home from school one day and announced that he wanted to climb Mount Everest... That's the thing about Mount Everest. The number alone — 29,032 feet — doesn't mean much to a kid."*
- **Recommended insertion location:** end of post, matching the existing footer-promo pattern seen in posts 78/86 (title + tagline + Amazon link block).
- **Intended destination:** the Mount Everest book's Amazon listing (or, if the WooCommerce product page is preferred going forward instead of the legacy Amazon-link pattern, the theme's own `/product/adventures-of-charlotte-and-henry-mount-everest/` page) — **not** the Mariana Trench link the other posts default to.
- **Technical WordPress change required:** append an `<a href="...">` block to `post_content` via `wp post update` (staging first) — no shortcode/template change needed, matches the existing hand-placed-link pattern already used across the other 34 posts.
- **ChatGPT copy needed:** No — a one-line link, not new prose.
- **Staging-first workflow:** Apply to the staging copy of post 76 (or a disposable test post) first, verify the rendered link, then apply to production per the content-operations policy (existing-post refreshes go to staging first).
- **Rollback:** `wp post get 76 --field=post_content` captured as a snapshot before any edit; a straight revert if needed.

### Packet 2 — Post 68, `how-stories-build-resilience-in-children`
- **Current state:** zero book-discovery link, zero topic cross-links. Excerpt: *"Children are not born fearless. They are born curious — but courage is built... This stage is critical for developing resilience. And one of the most powerful tools we have to support that growth is storytelling."*
- **Recommended insertion:** end-of-post footer block (same pattern).
- **Intended destination:** book-discovery → Mariana Trench (the resilience/courage framing matches the series' core positioning, no specific title is more relevant than another here) or the series overview; topic-hub → link to `26` (`adventure-books-for-kids-ages-6-9`), the closest topical match already in the corpus.
- **ChatGPT copy needed:** No.
- **Staging-first + rollback:** same pattern as Packet 1.

### Packet 3 — Post 26, `adventure-books-for-kids-ages-6-9`
- **Current state:** zero book-discovery link, zero topic cross-links. Excerpt: *"Adventure stories do more than entertain young readers — they shape how children see the world and themselves... Adventure builds more than imagination. It builds courage."*
- **Recommended insertion:** end-of-post footer block.
- **Intended destination:** book-discovery → series overview (this post is genre-level, not book-specific, so a single-title link is a judgment call better left to ChatGPT/Andrew — flagging rather than guessing); topic-hub → `68` (resilience) and/or `100` (STEM storytelling), both genre-adjacent.
- **ChatGPT copy needed:** Possibly, if a book-specific (not generic series) link is preferred — otherwise no.
- **Staging-first + rollback:** same pattern.

### Packet 4 — Post 66, `how-deep-is-the-mariana-trench-for-kids`
- **Current state:** has a teacher-guide link already (`teachers:1`) but zero blog cross-links and zero direct Amazon book link. Excerpt: *"Have you ever wondered how deep the ocean really is? The Mariana Trench is the deepest place on Earth... That's deeper than Mount Everest is tall."*
- **Recommended insertion:** end-of-post footer block, matching pattern.
- **Intended destination:** book-discovery → Mariana Trench specifically (exact topical match, unambiguous); topic-hub → `74` (`mariana-trench-facts-for-kids`) or `94` (`what-is-the-mariana-trench-for-kids`), both close topical duplicates already in the corpus.
- **ChatGPT copy needed:** No.
- **Staging-first + rollback:** same pattern.

### Packet 5 — Post 30, `best-bridge-books-for-kids`
- **Current state:** has a teacher-guide link (`teachers:1`) but zero blog cross-links and zero Amazon book link, despite the post explicitly discussing the Mariana Trench book's own design choices (wide line spacing, large font). Excerpt: *"...The Adventures of Charlotte and Henry series was intentionally written with transitional readers in mind. Every design decision focuses on helping young readers succeed. Wide Line Spacing..."*
- **Recommended insertion:** end-of-post footer block — this post is arguably the most "book-discovery-ready" of the five, since it already names the series and describes it in detail without a link.
- **Intended destination:** book-discovery → Mariana Trench (the post's own text already centers this title); topic-hub → `50` (`bridge-books-for-kids`, "The Complete Guide") or `88` (`top-bridge-books-for-kids`), both deeper treatments of the same topic already in the corpus.
- **ChatGPT copy needed:** No.
- **Staging-first + rollback:** same pattern.

## Sprint 1 — verified remediation packets (2026-07-13, posts 76 & 68 only)

**Status: staging implemented 2026-07-13, production pending Andrew's approval.** Andrew approved final copy (see "Approved copy actually implemented" below) and directed staging-only implementation, completed and QA-passed same day.

**This section supersedes Packet 1 (post 76) and Packet 2 (post 68) above.** Those were explicitly flagged as "not independently live-verified this pass"; this pass re-verified both posts against live production (`wp post get`, raw `post_content`, `bhp_get_guide_registry()` via `wp eval-file`, `bhp_get_guide_hub_url()`/`bhp_get_series_adventures()` resolution, and live HTTP status on every candidate destination URL) and against the actual enforcement code (`inc/class-bhp-cta-collision-detector.php`), not just the plain-language policy. No audience-facing copy was drafted in the audit pass itself — per this sprint's explicit constraint, that was ChatGPT/CSO's job; the audit identified exact insertion points and destinations only. Andrew subsequently supplied approved copy (below) and directed staging implementation.

### Approved copy actually implemented (staging, 2026-07-13)

**Post 76:** (1) the existing item-#10 book-discovery link's anchor text ("Adventures of Charlotte & Henry: Mount Everest") was kept unchanged; only its destination was swapped from `https://linktr.ee/CharlotteandHenryBooks` to `https://braveheartspublishing.com/product/adventures-of-charlotte-and-henry-mount-everest-paperback/`. (2) One new sentence was inserted immediately after "They come away feeling like they were there." and before "Ready to Climb?": *"If you're looking for more ways to encourage curiosity, resilience, and a love of learning, explore our [Explorer Expedition Guides for parents and educators], where you'll find articles, reading ideas, and free classroom resources designed to help children grow through adventure."* — with only "Explorer Expedition Guides for parents and educators" linked, to `https://braveheartspublishing.com/teachers/#science-geography`.

**Post 68:** (1) "Also, check out our home page here!" was replaced with: *"Parents looking for more practical reading activities, adventure ideas, and confidence-building resources can also explore our [Explorer Expedition Guides], where we regularly share new articles for families and educators."* — "Explorer Expedition Guides" linked to `https://braveheartspublishing.com/teachers/#reading-growing`. (2) "Purchase with this [link.]" (the weak-anchor Amazon sentence) was replaced with: *"Continue the adventure with [Adventures of Charlotte & Henry: The Mariana Trench]."* — the book-title portion linked to `https://braveheartspublishing.com/product/adventures-of-charlotte-and-henry-the-mariana-trench-paperback/` (replacing the old Amazon affiliate URL entirely, per Andrew's explicit direction). Per Andrew's explicit instruction, the article was **not** broadened toward the Complete Collection and **not** changed to reference The Amazon.

Both changes were applied via safety-checked `wp eval-file` scripts (verified each target string occurred exactly once before replacing; pre-edit backups captured first) and verified via byte-level diff against those backups to confirm no other content changed. Live browser QA confirmed correct hrefs/anchor text, all pre-existing untouched links unchanged, the automatic `guide-continuation` aside renders correctly on both posts, no horizontal overflow at mobile/desktop widths, zero PHP fatals, zero console errors. **Production is untouched** — pending Andrew's explicit approval to deploy the same change to production.

### Registry-vs-content correction
Both posts ARE registry members (`bhp_get_guide_registry()` is keyed by **slug**, not post ID — the original audit script's ID-keyed check would have wrongly read both as non-members). Post 76: `primary=science-geography`, `destination=mount-everest`, `book=mount-everest`. Post 68: `primary=reading-growing`, `destination=''`, `book=''`. This means post 76 already automatically receives, in the template-rendered `<aside class="guide-continuation">` below the article, a "science-geography" hub link, a "Mount Everest Expedition Guide" link, AND a direct link to the real Everest product page — post 68 automatically receives only the "reading-growing" hub link (no destination/book links, since those registry fields are empty). **This automatic aside does not satisfy the in-body requirement** (`required-links-policy.md` is explicit that it doesn't), but it does mean the packets below won't introduce any new destination the reader hasn't already seen once more, right after the article.

### Code-vs-policy gap (flagged for Andrew/engineering, not resolved here)
`BHP_CTA_Collision_Detector::detect_contextual_links()` only scans `<p>` tags (regex `/<p[^>]*>(.*?)<\/p>/is`) and its `AMAZON_AFFILIATE_URL_PATTERN` requires a literal `tag=` query parameter. Two consequences found on these exact posts:
1. Post 68's "More for parents of struggling readers → Every guide in one place" link lives inside an `<h4>`, so the detector never evaluates it at all — moot here since, per the plain-language policy, it also wouldn't qualify (no surrounding prose, arrow-styled CTA phrasing, isolated in its own heading).
2. Post 68's real, working `amazon.com/.../dp/B0GQCCPZLL/...` link has `dib_tag=se` but no true `tag=` parameter — the regex's word-boundary (`\btag=`) does not match inside `dib_tag=`, since `_` counts as a word character. The link is a genuine book-discovery link by the human-readable policy, but the automated detector would not currently flag it as a qualifying `amazon_affiliate` match, and it doesn't match any on-site `BOOK_OR_PRODUCT_URL_PATTERNS` either (those are site-relative paths only). **Separately worth flagging to Andrew:** Amazon affiliate attribution verification is pending for this URL — independent of this sprint's scope.

---

### Post 76 remediation packet — `mount-everest-facts-for-kids` (ID 76)

1. **Post ID:** 76
2. **Title:** "10 Mount Everest Facts for Kids That Will Make Their Jaw Drop"
3. **URL:** `https://braveheartspublishing.com/mount-everest-facts-for-kids/` (live, publish status confirmed)
4. **Search intent:** Informational/top-of-funnel — parent or child searching "mount everest facts for kids" for a factual listicle, not yet in book-purchase mode. Rank Math focus keyword confirms this: "mount everest facts for kids, facts about mount everest for kids, everest facts for kids."
5. **Audience:** Families / children with adult guidance (matches registry `audiences`).
6. **Funnel stage:** Awareness/education — top of funnel, pre-purchase-intent.
7. **Current contextual links (in-body, verified in raw `post_content`):** One qualifying book-discovery paragraph (item #10: "That's what Charlotte and Henry do in *Adventures of Charlotte & Henry: Mount Everest* — the newest adventure in the series..." → currently linked to `https://linktr.ee/CharlotteandHenryBooks`). Zero in-body topic-hub link. Three additional promotional/CTA-styled blocks near the very end (all pointing to the same Linktree URL or the bare homepage) — these are pre-existing hand-authored CTA blocks, not required-link candidates.
8. **Missing requirements:** (a) In-body topic-hub link — completely absent. (b) Book-discovery link exists but points to a generic Linktree hub instead of the direct, on-site Everest product page.
9. **Recommended book or Collection destination:** The individual Mount Everest book — **not** the Complete Collection (this post is single-place, single-book content; forcing the Collection would dilute relevance and risks reading as generic upsell).
10. **Verified destination URL:** `https://braveheartspublishing.com/product/adventures-of-charlotte-and-henry-mount-everest-paperback/` — confirmed live, HTTP 200.
11. **Recommended topic-hub destination:** the `science-geography` guide hub — `https://braveheartspublishing.com/teachers/#science-geography` — confirmed live, HTTP 200 (registry `primary` for this post).
12. **Exact current paragraph excerpt before insertion (book-discovery link, item #10):**
    > "That's what Charlotte and Henry do in *Adventures of Charlotte & Henry: Mount Everest* — the newest adventure in the series designed for curious readers ages 6–9. The book weaves real mountain science, real history, and a real sense of adventure into short, engaging chapters that reluctant readers actually finish. Kids who have read it come away knowing the Death Zone, the Khumbu Icefall, and why Tenzing buried a chocolate bar at the top. They come away feeling like they were there."
    Immediately following this (last paragraph before the "Ready to Climb?" CTA block) is the exact spot for the new topic-hub sentence — there is currently no sentence there at all, just a direct jump to "Ready to Climb?".
13. **Exact insertion location:** (a) Book-discovery fix: swap the `href` on the existing item-#10 link (currently `https://linktr.ee/CharlotteandHenryBooks`) to the verified product URL in point 10 — the anchor text itself ("*Adventures of Charlotte & Henry: Mount Everest*") stays accurate and does not need to change. (b) Topic-hub link: insert one new sentence as its own paragraph immediately after "They come away feeling like they were there." and before the existing "Ready to Climb?" line — this keeps the new required links ahead of the existing promotional CTA stack (Part 4 hierarchy).
14. **Modify existing vs. add new:** (a) is a pure URL-target correction on an existing sentence — no new copy needed, anchor text is already accurate. (b) requires one new sentence — net-new copy.
15. **Strategic purpose:** (a) sends purchase-intent traffic to the real, on-site format-selection/purchase page instead of an off-site Linktree intermediary (removes an unnecessary hop and off-site dependency). (b) gives search engines and readers a genuine in-article path to the broader science/geography resource hub, satisfying the policy without duplicating what the automatic aside already does.
16. **Cannibalization risk:** None found — no other of the 35 published posts targets Everest-specific keywords or content; post 76 is the sole Everest-facts post in the corpus.
17. **CTA-collision risk:** Low. The new topic-hub sentence sits before the existing "Ready to Climb?" / "Find on Amazon →" / "Purchase..." promotional block, preserving the required hierarchy (helpful content → resource link → book link → CTA). Note: the automatic `guide-continuation` aside will also render a "science-geography" hub link and a direct Everest-product link immediately below the article — intentional redundancy per the template's design, not a defect.
18. **Metadata changes required:** No (title/description/focus keyword unaffected by either change). Rank Math description is genuinely empty (0 bytes, independently reconfirmed) — a possible separate SEO gap, out of scope for this sprint but worth flagging.
19. **Taxonomy changes required:** No.
20. **Copy assignment for ChatGPT/CSO:** One new sentence connecting the article's science/geography content to the `science-geography` guide hub, placed per item 13(b) above. The book-discovery link (13a) needs **no** ChatGPT copy — it is a URL-only fix, existing anchor text stays as-is unless ChatGPT/Andrew prefers different wording.

### Post 68 remediation packet — `how-stories-build-resilience-in-children` (ID 68)

1. **Post ID:** 68
2. **Title:** "How Stories Help Children Build Resilience and Courage (Ages 6–9)"
3. **URL:** `https://braveheartspublishing.com/how-stories-build-resilience-in-children/` (live, publish status confirmed)
4. **Search intent:** Informational/parenting — SEL (social-emotional learning) and resilience-building for ages 6–9, no Rank Math title/description/focus keyword set at all (a real metadata gap, separate from the linking question).
5. **Audience:** Families / general readers (registry `audiences`).
6. **Funnel stage:** Mid-funnel — parenting/education content, natural bridge to a purchase-intent nudge but not itself transactional.
7. **Current contextual links (in-body, verified in raw `post_content`):** One qualifying book-discovery paragraph under "Explore STEM Adventures That Build Brave Hearts": "...explore the *Adventures of Charlotte and Henry* series. Purchase with this [link.]" → real, direct `amazon.com/.../dp/B0GQCCPZLL/...` URL for The Mariana Trench specifically. Anchor text is weak ("link."). No in-body topic-hub link — the only hub-destination link on the page ("More for parents of struggling readers → Every guide in one place," pointing to `/teachers/#reading-growing`) is wrapped in its own `<h4>`, has no surrounding prose, and reads as a standalone CTA, not body prose — see code-vs-policy gap note above. A generic, non-qualifying homepage link ("Also, check out our home page here!") sits between the two.
8. **Missing requirements:** In-body topic-hub link — the existing `/teachers/#reading-growing` destination is correct, but it is not currently expressed as a genuine in-body contextual link (it is CTA-shaped, `<h4>`-isolated). Book-discovery link exists and is real, but has weak/generic anchor text.
9. **Recommended book or Collection destination:** Keep the existing Mariana Trench-specific link — do **not** introduce a second, different book target in a 656-word article (Part 4's "avoid stacked promotional blocks" concern). Whether Mariana Trench specifically vs. a broader series/Collection framing is the right long-term call is a content-judgment question — flagging as an open item for ChatGPT/Andrew rather than deciding here, since this content isn't tied to any specific place/adventure the way post 76 is tied to Everest.
10. **Verified destination URL:** existing Amazon URL is live and functional (spot-checked); no on-site Mariana Trench product-page alternative recommended here since the existing link is real Amazon copy already discussing "the series" generically, and swapping to a single-format on-site page could narrow it unhelpfully — leave as a copy decision for ChatGPT.
11. **Recommended topic-hub destination:** the `reading-growing` guide hub — `https://braveheartspublishing.com/teachers/#reading-growing` — confirmed live, HTTP 200 (registry `primary` for this post; also the exact destination the existing but non-qualifying `<h4>` CTA already points to).
12. **Exact current paragraph excerpt before insertion:**
    > "If you're searching for early chapter books that blend real-world science with resilience and courage-building lessons, explore the *Adventures of Charlotte and Henry* series. Purchase with this [link.]"
    Followed immediately by: "Also, check out our home page [here]!"
13. **Exact insertion location:** Convert the low-value "Also, check out our home page here!" sentence (which currently satisfies nothing — homepage is not a topic hub) into the topic-hub sentence, retargeting it from the bare homepage to the `reading-growing` hub with real, descriptive anchor text. This reuses an existing sentence slot rather than adding a new paragraph, and points to the exact same destination the closing `<h4>` CTA already uses (no new destination introduced, no collision). Separately, strengthen the existing Amazon link's anchor text from "link." to something descriptive of the actual book (e.g., naming the title) — same paragraph, no new sentence needed.
14. **Modify existing vs. add new:** Both are edits to existing sentences — no new paragraphs required for this post.
15. **Strategic purpose:** Upgrades a currently-wasted sentence (generic homepage plug) into the policy-required in-body topic-hub link, and gives the existing real Amazon link descriptive anchor text instead of the vague "link."
16. **Cannibalization risk:** Moderate-low. Posts 26 (adventure books → confidence/curiosity) and 100 (STEM storytelling → braver/curious kids) cover adjacent "why books shape character" territory. Recommend any new/edited anchor text stay narrowly framed on resilience/courage/SEL (this post's actual angle) rather than broadening into "adventure books build confidence" (post 26's territory) or "STEM storytelling" (post 100's territory), to avoid new keyword overlap.
17. **CTA-collision risk:** Low. Both edits land well before the final "Big Places. Brave Hearts." / "More for parents..." closing block — no stacking.
18. **Metadata changes required:** Not part of this link-remediation packet, but flagging: post 68 currently has **no** Rank Math title, description, or focus keyword set at all — a genuine SEO gap independent of this sprint's scope, worth a separate task.
19. **Taxonomy changes required:** Post 68 is currently in "Uncategorized" — a real taxonomy gap, also independent of this sprint's scope.
20. **Copy assignment for ChatGPT/CSO:** (a) New anchor text for the retargeted topic-hub sentence (replacing "Also, check out our home page here!"). (b) Improved anchor text for the existing Amazon link (replacing "link."). (c) Open question: confirm whether the Mariana-Trench-specific book framing should stay as-is or shift to broader series/Collection language.

## Malformed doubled-protocol Amazon link fix — posts 38, 64, 88, 90 (2026-07-13)

**Status: fixed and verified on production.** Discovered during Conversion QA Sprint 1's blog-sample audit: 4 posts contained `href="https:// https://amzn.to/..."` — a doubled/malformed protocol (7 total occurrences: 1 each in posts 38, 88, 90; 4 in post 64) that resolved in-browser to an invalid address rather than the intended Amazon listing. Post 64 was affected worst — all 4 of its Amazon links were malformed, and its automatic end-of-article CTA doesn't render on that post either, leaving it with zero working book-discovery path before this fix.

**Method:** deterministic PHP script via `wp eval-file`, verifying the exact expected occurrence count (1/4/1/1) per post via `substr_count()` before replacing `href="https:// https://` with `href="https://` globally within each post — a pure prefix-stripping fix, no destination/anchor-text change. Pre-edit backups captured outside the repo. Byte-level isolated-diff (prefix/suffix technique) confirmed the only change per post was removal of the 9-character `" https://"` duplicate at each occurrence — all surrounding prose, HTML structure, and other links byte-identical before and after.

**Verification:** zero `https:// https://` occurrences remain in any of the 4 posts (confirmed via grep and live DOM inspection). The corrected `amzn.to` short links resolve HTTP 200 (verified with a browser-like user agent — bare `curl` requests get a 500 from Amazon's bot-blocking, a known false-negative pattern, not a real link problem). `BHP_CTA_Collision_Detector::check()` run against pre- and post-edit content for all 4 posts shows identical `collision_state` (`crowding`, pre-existing) in both cases — zero new collisions. Live-browser spot-check on all 4 posts: zero console errors, automatic "Related Field Notes" grid renders correctly, no other regressions. `wp eval 'echo "ok";'` confirms zero PHP fatals.

## Batch 2 mechanical production closeout (2026-07-13)

**Status: Batch 2 (posts 26, 66, 30) is now fully production-complete** — both the topic-hub copy (above) and the earlier mechanical fixes (below) are live on production. Andrew directed deploying the previously staging-tested mechanical fixes now that the approved copy was already confirmed live.

**Production preflight:** confirmed title/ID/slug/publication status for all three posts matched the packets exactly (post 26 = `adventure-books-for-kids-ages-6-9`, post 66 = `how-deep-is-the-mariana-trench-for-kids`, post 30 = `best-bridge-books-for-kids`, all `publish`). Confirmed the approved Batch 2 copy was present and intact on production before touching anything. Computed an exact byte-level isolated diff between production's current content and staging's fully-fixed reference version for each post — each showed exactly one contiguous change region, matching precisely the expected mechanical fix with no other drift, confirming production had not materially drifted and it was safe to proceed on all three.

**Post 26 mechanical change:** the Amazon affiliate book-link URL swapped to the direct on-site product page (`https://braveheartspublishing.com/product/adventures-of-charlotte-and-henry-the-mariana-trench-paperback/`); anchor text and all surrounding prose byte-identical, untouched.

**Post 30 mechanical change:** identical fix — Amazon URL swapped to the same direct product page URL, anchor text and surrounding prose untouched.

**Post 66 mechanical change:** the split-anchor HTML defect in the "Related Reading" list repaired — `<a href="/teachers/#mariana-trench">• Why STEM Sto</a><a href="/blog/why-stem-storytelling-builds-braver-kids">` merged into a single correct anchor `<a href="/blog/why-stem-storytelling-builds-braver-kids">Why STEM Sto...</a>` with the bullet moved outside the link. The approved Batch 2 copy (both sentences) and the "Go deeper → Every Mariana Trench & ocean guide" CTA block were confirmed byte-identical before and after — neither touched.

**Method:** deterministic PHP scripts reading the exact old/new segments from files (computed via the same prefix/suffix isolated-diff technique used for the copy implementation) rather than hand-typing long tracking-parameter URLs, each verifying the target segment's uniqueness (`substr_count() === 1`) before replacing. Pre-edit backups captured for both staging (reference) and production before any write, stored outside the repository at `C:\BHP\private-backups\legacy-blog-batch2-mechanical-20260713-211638\`.

**Verification:** post-edit production content confirmed byte-identical to staging's reference version for all three posts (`diff` returned no differences). All destination URLs re-verified HTTP 200. Live-browser QA on production confirmed: approved copy intact, book links resolve to the direct product page (no `amazon.com` in the href) with exactly one link per post, the split-anchor defect is repaired with correct anchor text and href, the "Go deeper" block and automatic `guide-continuation`/"Related Reading"/"CONTINUE THE EXPEDITION" sections render exactly as before, zero console errors, zero PHP fatals, no horizontal overflow at desktop (1440px) or mobile (~471px effective width — the only sitewide overflow at that width remains the same pre-existing header/nav element documented earlier this session). Anchor-tag balance (`<a `/`</a>` counts) confirmed matched on all three posts as an additional HTML-validity signal.

**CTA-collision verification:** ran the real `BHP_CTA_Collision_Detector::check()` against both the pre-edit and post-edit production content for all three posts — `collision_state` was identical (`crowding`, from the pre-existing "Go deeper"/"Looking for more" CTA-shaped blocks) in both cases on all three posts, confirming this mechanical work introduced zero new collisions.

**Automated gate finding (not fixed this sprint, per instruction):** `BHP_Required_Links_Gate`/`BHP_CTA_Collision_Detector`'s `TOPIC_HUB_URL_PATTERNS` still does not recognize `/teachers/#...` anchor-hash hub URLs, and its anchor-extraction regex is still fragile to attribute ordering (`href` must be the `<a>` tag's first attribute). Both were confirmed and recorded during the prior copy-implementation task (see `OPEN_QUESTIONS.md` and `KNOWN_ISSUES.md`) — manual, live-browser policy compliance verification remains the practical method until a dedicated engineering sprint fixes the detector. Not a publishing blocker.

**Result:** Posts 26, 66, and 30 are now fully production-complete — approved copy live, mechanical fixes live, zero regressions.

## Batch 2 copy implementation (2026-07-13, closes the packets below)

**Status: topic-hub copy approved and deployed to production on all three posts.** Andrew supplied final copy for the one remaining requirement flagged in each packet below (items 20). Implemented via deterministic marker-based `wp eval-file` scripts (each target string's uniqueness verified before writing, byte-diffed against pre-edit backups afterward to confirm isolated scope) — first on staging, then identically on production, in the same session.

- **Post 26:** the "Free Teacher's Guide for the book on the home page!" sentence replaced with: "Parents and educators looking for more ways to build confidence through reading can explore our Explorer Expedition Guides for practical ideas, classroom resources, and adventure-based learning activities." — linking "Explorer Expedition Guides" to `/teachers/#reading-growing`.
- **Post 66:** the "Buy on amazon here." sentence replaced with: "Continue the adventure in Adventures of Charlotte & Henry: The Mariana Trench." — linking the book title directly to the on-site Mariana Trench paperback product page (retiring the Amazon affiliate link for this instance) — plus one new sentence: "Families and educators can explore more real-world science and geography resources in our Explorer Expedition Guides." — linking to `/teachers/#science-geography`. The existing "Go deeper → Every Mariana Trench & ocean guide" CTA block was preserved unchanged, per instruction.
- **Post 30:** one new sentence inserted immediately before the existing "Looking for more on bridge books?" CTA block: "For more guidance on choosing books with approachable text, supportive spacing, and age-appropriate pacing, explore our Explorer Expedition Guides for families and educators." — linking to `/teachers/#reading-growing`, deliberately narrow (not generic "best bridge books" language) per the cannibalization note in that post's packet (item 18 below).

**QA:** all three destination URLs re-verified HTTP 200 before implementation. Staging QA confirmed exact wording, correct anchor/destination, no duplicate sentences, valid HTML, no console/PHP errors, no horizontal overflow at desktop/mobile, and — critically — that `BHP_CTA_Collision_Detector::check()` reports the identical `collision_state` ("crowding", pre-existing on all three posts from the already-present "Go deeper"/"Looking for more" CTA-shaped blocks) before and after the edit, confirming no new collision was introduced. The same detector's `required_links` gate reported `topic_hub_link_present => false` on all three posts despite genuine, policy-compliant contextual sentences — investigated and traced to a real, previously-undiscovered gap in `TOPIC_HUB_URL_PATTERNS` (never recognized `/teachers/#...` anchor-hash URLs, the actual format every hub link in this project uses) plus an anchor-attribute-order fragility in the link-extraction regex; not a content-compliance problem — see `OPEN_QUESTIONS.md` for the full finding. Production received the identical marker-based edits, byte-diff-verified against fresh production backups, cache purged, and the same live-browser QA re-run with zero regressions.

**Explicitly not part of this deploy:** the earlier Batch 2 mechanical fixes (posts 26/30's Amazon→on-site book-link URL swap, post 66's malformed split-anchor "Related Reading" repair) remain staging-only — this task's approval was scoped to "these exact three copy changes only." Production's book-discovery link on posts 26/30 still points to Amazon; post 66's split-anchor STEM-link defect is still present on production. Both remain a separate, already-documented pending deploy — see `NEXT_TASK.md`.

## Batch 2 — verified remediation packets (2026-07-13 overnight, posts 26, 66 & 30)

**Status: mechanical fixes staging-implemented; topic-hub links copy-pending.** Andrew's overnight-build directive authorized autonomous staging work strictly limited to mechanical changes (URL swaps preserving existing anchor text, HTML-syntax repairs) — no new prose was invented. All three posts were re-verified live (raw `post_content` on production, confirmed byte-identical to staging before any edit) and against `bhp_get_guide_registry()` via `wp eval-file`.

### Mechanical fixes applied (staging only, production pending approval)
- **Post 26:** existing Mariana Trench book-discovery link's destination swapped from its Amazon affiliate URL to the direct on-site product page (`https://braveheartspublishing.com/product/adventures-of-charlotte-and-henry-the-mariana-trench-paperback/`); anchor text ("Adventures of Charlotte and Henry: The Mariana Trench") unchanged.
- **Post 30:** identical fix — same Amazon URL swapped to the same direct on-site product URL, anchor text unchanged.
- **Post 66:** repaired a genuine, pre-existing malformed-anchor HTML defect in the "Related Reading" list — the text "Why STEM Sto" was wrongly wrapped in the `/teachers/#mariana-trench` link while "rytelling Builds Braver Kids" was a separate anchor to the correct blog post, splitting one intended link into two with two different destinations. Merged into a single correct anchor: "Why STEM Storytelling Builds Braver Kids" → the correct blog post. This is a pure HTML-syntax repair, not a content or destination decision.

All three verified via byte-level diff against pre-edit backups (only the intended single change per post), zero PHP fatals, zero console errors, all pre-existing untouched links/content confirmed unchanged.

### Post 26 remediation packet — `adventure-books-for-kids-ages-6-9` (ID 26)

1. **Post ID:** 26
2. **Title:** "Why Adventure Books for Kids (Ages 6–9) Build Confidence and Curiosity"
3. **Production URL:** `https://braveheartspublishing.com/blog/adventure-books-for-kids-ages-6-9/` (permalink structure is `/blog/%postname%/` — confirmed live, HTTP 200)
4. **Staging preview URL:** `https://staging2.braveheartspublishing.com/?p=26`
5. **Search intent:** Informational, genre-level — "why do adventure books matter for my child's development," top/mid-funnel parenting content, not tied to any specific place or book.
6. **Audience:** Families / general readers (registry `audiences`).
7. **Funnel stage:** Awareness/education.
8. **Existing topic links:** Two real in-body cross-links to other blog posts (`why-stem-storytelling-builds-braver-kids`, `how-stories-build-resilience-in-children`) — good internal linking, but neither is a topic/resource-**hub** link as the policy defines it (registry `primary` hub page). Zero genuine in-body hub link.
9. **Existing book links:** One, now fixed — real, on-site, direct Mariana Trench product link with solid anchor text ("Adventures of Charlotte and Henry: The Mariana Trench").
10. **Existing promotional blocks:** One small footer block ("Big Places. Brave Hearts." + the book link + a generic "Free Teacher's Guide for the book on the home page!" sentence). Low collision risk.
11. **Required-link compliance:** Book-discovery link now satisfied (mechanical fix applied). Topic-hub link still missing — the generic homepage sentence is the natural candidate to retarget, but doing so requires new anchor text (the current anchor "home page" would be misleading pointed at a hub page) — copy required, not mechanical.
12. **Recommended topic hub:** `reading-growing` — `https://braveheartspublishing.com/teachers/#reading-growing` (registry `primary`).
13. **Recommended book/Collection destination:** Individual Mariana Trench book (already anchored, keep as-is) — **not** the Complete Collection; this is genre-level confidence/curiosity content, not series-wide or gift-guide framing.
14. **Verified URLs:** Mariana Trench product page and `/teachers/` page confirmed HTTP 200 (see Sprint 1 verification, same URLs).
15. **Current excerpt:** "Free Teacher's Guide for the book on the [home page]!" (linked to `https://www.braveheartspublishing.com`).
16. **Exact insertion/replacement location:** Replace the "home page" sentence with a new sentence naming the `reading-growing` hub, in the same closing-footer position.
17. **Mechanical or copy-required:** Copy-required — the retargeted sentence needs real wording, not just a URL swap.
18. **Cannibalization risk:** Low. This post explicitly cross-links to posts 100 (STEM storytelling) and 68 (resilience/SEL) rather than competing with them — its own angle (confidence/curiosity via adventure generally) is genre-adjacent but distinct. Keep any new copy narrowly framed on confidence/curiosity, not resilience or STEM specifically.
19. **CTA-collision risk:** Low — footer block is small, single book link, no stacking.
20. **ChatGPT/CSO assignment:** One new sentence retargeting "Free Teacher's Guide for the book on the home page!" to the `reading-growing` hub with honest, descriptive anchor text.

### Post 66 remediation packet — `how-deep-is-the-mariana-trench-for-kids` (ID 66)

1. **Post ID:** 66
2. **Title:** "How Deep Is the Mariana Trench? (Explained for Kids)"
3. **Production URL:** `https://braveheartspublishing.com/blog/how-deep-is-the-mariana-trench-for-kids/` (confirmed live, HTTP 200)
4. **Staging preview URL:** `https://staging2.braveheartspublishing.com/?p=66`
5. **Search intent:** Informational, factual — "how deep is the Mariana Trench," a long-tail variant within this corpus's existing Mariana Trench facts cluster.
6. **Audience:** Families / children with adult guidance (registry `audiences`).
7. **Funnel stage:** Awareness/education.
8. **Existing topic links:** A generic `/books` link (series listing page, not a resource hub) and a 3-item "Related Reading" cross-link list (STEM, resilience, adventure posts — the STEM one had the malformed-anchor defect, now fixed). An h4-isolated "Go deeper → Every Mariana Trench & ocean guide" link correctly targets `/teachers/#mariana-trench` but is CTA-shaped (isolated heading, arrow phrasing, no surrounding prose) and does not satisfy the in-body-prose requirement.
9. **Existing book links:** One real, working Amazon link for the Mariana Trench book, anchor text is the bare word "here." — weak, needs improvement.
10. **Existing promotional blocks:** Small footer ("Big Places. Brave Hearts." + the hub CTA + Related Reading list). Low collision risk.
11. **Required-link compliance:** Neither requirement satisfied in genuine body prose — book link exists but has non-descriptive anchor text; topic-hub link exists only in CTA-shaped, non-`<p>` form.
12. **Recommended topic hub:** `science-geography` (registry `primary`) — `https://braveheartspublishing.com/teachers/#science-geography`. Note the registry `destination`/`book` are both `mariana-trench`, so the automatic `guide-continuation` aside already renders "Visit the The Mariana Trench Expedition Guide" and a direct book link below the article (confirmed live) — the in-body sentence still needs to exist per policy, but readers who reach the end already see both destinations once more.
13. **Recommended book/Collection destination:** Individual Mariana Trench book (unambiguous — the entire post is about this exact place).
14. **Verified URLs:** Mariana Trench product page, `/teachers/` page — both HTTP 200 (Sprint 1 verification).
15. **Current excerpt:** "...young explorers travel to incredible places like the **Mariana Trench** while learning about courage, teamwork, and real science. Buy on amazon [here.]"
16. **Exact insertion/replacement location:** (a) Strengthen "here." to a descriptive anchor naming the book, at the same location — copy required (anchor text must be written, not reused verbatim from the sentence). (b) Add one new sentence for the `science-geography` hub, near the "Go deeper" CTA block — copy required.
17. **Mechanical or copy-required:** Both remaining items are copy-required (anchor-text authorship and new-sentence authorship both count as new prose per Phase 6's rules).
18. **Cannibalization risk:** **Real and pre-existing, not introduced by this post.** Posts 74 (`mariana-trench-facts-for-kids`) and 94 (`what-is-the-mariana-trench-for-kids`) cover closely overlapping "Mariana Trench facts" territory. Not a defect to silently fix (would require an editorial consolidation decision) — flagged in `OPEN_QUESTIONS.md` rather than resolved here.
19. **CTA-collision risk:** Low.
20. **ChatGPT/CSO assignment:** (a) Descriptive anchor text for the existing Amazon book link (replacing "here."). (b) One new sentence for the `science-geography` hub link.

### Post 30 remediation packet — `best-bridge-books-for-kids` (ID 30)

1. **Post ID:** 30
2. **Title:** "Bridge Books: The Perfect Next Step After Frog and Toad"
3. **Production URL:** `https://braveheartspublishing.com/blog/best-bridge-books-for-kids/` (confirmed live, HTTP 200)
4. **Staging preview URL:** `https://staging2.braveheartspublishing.com/?p=30`
5. **Search intent:** Mid-funnel decision content — parents choosing a "bridge book" for a transitional reader, with the Charlotte and Henry series presented as a worked example of what makes a good bridge book (line spacing, font, sentence rhythm).
6. **Audience:** Families / general readers (registry `audiences`).
7. **Funnel stage:** Consideration — closer to purchase-intent than posts 26/66, since it directly analyzes the series' own design.
8. **Existing topic links:** An h4-isolated "Looking for more on bridge books? → Bridge Books Hub" link (correct destination `/teachers/#reading-growing`, but CTA-shaped/non-`<p>`) and a 3-item "Supportive Blog List" (STEM, resilience, adventure cross-links).
9. **Existing book links:** One, now fixed — real, on-site, direct Mariana Trench product link with excellent existing anchor text ("Adventures of Charlotte and Henry: The Mariana Trench") — this was the cleanest of the three posts' book links, needed only the URL swap.
10. **Existing promotional blocks:** Small footer plus a benign "Back to Home Page" navigational link (not a required-link candidate, no action needed).
11. **Required-link compliance:** Book-discovery link now satisfied (mechanical fix applied). Topic-hub link still missing in genuine body prose (only the h4 CTA form exists).
12. **Recommended topic hub:** `reading-growing` (registry `primary`) — `https://braveheartspublishing.com/teachers/#reading-growing` — same destination the existing h4 CTA already uses.
13. **Recommended book/Collection destination:** Individual Mariana Trench book (already anchored, keep as-is) — not the Collection.
14. **Verified URLs:** Mariana Trench product page, `/teachers/` page — both HTTP 200.
15. **Current excerpt:** "...With supportive formatting, engaging stories, and approachable language, books like **Charlotte and Henry** give young readers the confidence to keep turning pages. And once that confidence appears — a lifelong love of reading often follows." (immediately followed by the h4 CTA).
16. **Exact insertion/replacement location:** Insert one new sentence between the paragraph above and the h4 CTA, naming the `reading-growing` hub in real prose — copy required.
17. **Mechanical or copy-required:** Copy-required (new sentence).
18. **Cannibalization risk:** **High — a pre-existing, corpus-wide pattern, not specific to this post.** At least nine other published posts cover overlapping "bridge books" territory: `50` ("The Complete Guide to Bridge Books"), `88` ("Top Bridge Books for Kids"), `54` ("Bridge Books for Struggling Readers"), `90` ("What Are Bridge Books"), `48` ("How to Help Your Child Transition..."), `60` ("The First Real Chapter Book"), `52` ("bridge-books-for-kids-mount-everest"), `96`, `64`. Potential content consolidation is tracked in the private CSO knowledge base — **not re-litigated here**, but any new topic-hub sentence for post 30 should stay narrowly descriptive (naming this post's specific angle — the series' physical design features — rather than broad "best bridge books" language) to avoid adding to the existing overlap. Flagged in `OPEN_QUESTIONS.md`.
19. **CTA-collision risk:** Low.
20. **ChatGPT/CSO assignment:** One new sentence for the `reading-growing` hub link, worded narrowly around this post's specific angle (book design/format, not generic "best bridge books" framing) per the cannibalization note above.

## Posts 100 & 84 — light Amazon-mention check (2026-07-13 overnight, not a full packet)

Per the overnight directive's explicit scope limit (posts 100/84 are optional and time-boxed after the higher-priority batches), this was a light check only, not a full 20-point packet. Neither post's raw `post_content` contains rainforest/jungle/tropical/biodiversity keywords, and neither contains hardcoded "two adventures"/"two books" language that would need correcting before a third book could be mentioned — so introducing The Amazon wouldn't contradict anything currently written, but doing it well would require reframing "the series" language from two books to three, which is a real copy judgment, not a mechanical fix. **No packet built, no copy drafted, no changes made.** If Andrew wants this pursued next, it should get the same full audit treatment as posts 26/66/30, not be rushed into this session's remaining time.

## Sitewide mechanical-defect scan (2026-07-13 overnight, read-only, all 36 published posts)

A pattern-matching scan of every published post's raw `post_content` on production (read-only, no edits) found at least one flagged pattern in **35 of 36 posts**. This is a discovery pass only — flags are pattern matches, not all individually hand-verified (exceptions: posts 76/68/26/66/30, hand-verified this session; the `POSSIBLE_SPLIT_ANCHOR` and `H4_ISOLATED_CTA_LINK` flags in particular are heuristics that need a quick per-post look before fixing, not confirmed bugs on every flagged post). **No posts beyond 76/68/26/66/30 were edited.**

**Patterns checked, with corpus-wide counts:**
- `H4_ISOLATED_CTA_LINK` (~27 posts) — the "arrow-styled, `<h4>`-isolated CTA instead of in-body prose" pattern found repeatedly this session. This is the single most common gap and directly explains why so many posts fail the required-links policy's topic-hub requirement despite having a technically-correct destination URL.
- `AMAZON_NO_TAG_PARAM` (16 posts: `48`, `52`, `32`, `62`, `60`, `30`, `66`, `26`, `100`, plus others) — every sampled Amazon link in the corpus uses the same `dib_tag=se`-only pattern with no real `tag=` Associates parameter. Amazon affiliate attribution verification is pending **corpus-wide**, not isolated to one post — see `OPEN_QUESTIONS.md`.
- `BARE_HOMEPAGE_LINK` (~20 posts) — the same generic "home page" link pattern already remediated on posts 76/68/26/30 this session, present across roughly half the corpus.
- `LINKTREE` (12 posts: `98`, `58`, `76` *(prod link already fixed for the one instance this session; other Linktree instances on 76 remain, see Sprint 1 packet)*, `54`, `94`, `36`, `78`, `86`, `92`, `48`, `100`) — off-site intermediary hop where a direct on-site product link would be equally valid and one hop shorter.
- `POSSIBLE_SPLIT_ANCHOR` (8 posts: `84`, `56`, `72`, `96`, `28`, `94`, `48`, plus `66` — confirmed and fixed this session) — a heuristic for the exact malformed-anchor defect found and fixed on post 66 (text split across two adjacent `<a>` tags with different destinations). Given the identical "Related Reading" list template appears to be reused across many posts, several of these are plausibly the same copy-paste defect — **not confirmed individually**, flagged for a dedicated pass.
- `WEAK_ANCHOR_TEXT` (post 66 confirmed; likely under-counted — this pattern only catches the literal words "here."/"link."/"click here", not every weak anchor).
- Zero posts matched: `AMAZON_COLLECTION_TERMINOLOGY`, `MISSING_PROTOCOL`, `STAGING_URL_IN_CONTENT`, `EMPTY_OR_HASH_HREF` — genuinely clean on all four counts.

### Prioritized mechanical-fix queue

**P0 — broken or wrong (fix first, real defects not just gaps):**
1. `POSSIBLE_SPLIT_ANCHOR` candidates (`84`, `56`, `72`, `96`, `28`, `94`, `48`) — verify each individually against the post-66 pattern, then apply the same merge-into-one-anchor fix where confirmed. Pure HTML repair, no copy needed, autonomous-safe once confirmed.
2. `AMAZON_NO_TAG_PARAM` sitewide (16+ posts) — Amazon affiliate attribution verification is pending across the whole corpus, not a one-post issue. Needs Andrew to verify attribution configuration first (see `OPEN_QUESTIONS.md`) before any link is touched — do not silently insert a `tag=` value.

**P1 — weak conversion routing:**
3. `LINKTREE` (12 posts) — same URL-only, anchor-preserving swap pattern already used on posts 76/26/30 this session, applicable wherever the anchor text already names the book (verify per-post, not assumed).
4. `BARE_HOMEPAGE_LINK` (~20 posts) — same copy-required retargeting-to-topic-hub pattern as posts 76/68/26/30's packets. Large batch, needs ChatGPT copy per post (or a small set of reusable templates ChatGPT could draft once).

**P2 — editorial improvement:**
5. `H4_ISOLATED_CTA_LINK` (~27 posts) — convert the existing arrow-styled CTA into one genuine in-body sentence per post. This is the largest, most systemic gap in the corpus and the direct cause of most posts' topic-hub-link non-compliance. Needs per-post copy (each post's sentence should reference its own content, not a generic template) — the single biggest ChatGPT/CSO copy workload implied by this whole audit.

**P3 — optional / already clean:**
6. No action needed — zero hits for Amazon Collection terminology, missing protocol, staging-URL leakage, or dead `#`/empty hrefs.

This queue is a planning artifact for future small sprints (matching this session's Sprint 1 / Batch 2 cadence) — not a commitment to fix everything at once, per the standing "deliberately small, staging-first" workflow.

## Known limitation of this audit pass

This audit inspected `post_content` server-side (categories, tags, link presence/counts, keyword mentions) — it did **not** open all 35 rendered pages in a browser, so it cannot confirm: exact footer-block content per post (assumed consistent based on the 2 samples read in full — posts 78 and 86 — but not verified for all 35), Adventure Kit popup rendering, or whether any listed `amzn.to`/`linktr.ee`/cross-links are actually live (not 404). A full click-through link-validity pass is a reasonable Phase 9-adjacent follow-up, not done this session.
