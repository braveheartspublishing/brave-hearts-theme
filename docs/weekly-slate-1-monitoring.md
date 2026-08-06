# Weekly Slate #1 — Production Change Monitoring Record

Tracks the 6-page SEO metadata/taxonomy/link change deployed to production on
**2026-07-11** (confirmed via server-side `wp_sync` timestamp; see
`weekly_slate1_PRODUCTION_rollback_baseline.json` in the session scratchpad
for the full pre-change field values). This file is the living tracking
record — update it in place at each checkpoint rather than creating new
files, so the history stays in one place.

## Checkpoint schedule

| Checkpoint | Target date | Status |
| --- | --- | --- |
| 7-day | 2026-07-18 | pending |
| 28-day | 2026-08-08 | pending |
| 90-day | 2026-10-09 | pending |

**GSC data lags ~3 days behind real-time** (`data_delay_days` confirmed via
`bhp-seo gsc status`). Pull data 3-4 days after each target date, not on the
exact date, or the most recent days will be incomplete/absent and read as a
false drop.

## How to re-pull each metric at a checkpoint

```powershell
cd C:\BHP\brave-hearts-seo-engine
bhp-seo gsc sync
bhp-seo gsc status
```
Then query `search_console_pages` / `search_console_queries` for
`import_source='api'` rows on each URL/query below, for the window since
2026-07-11.

Rendered title/canonical: `curl -s <url> | grep -oE '<title>[^<]*</title>|<link rel="canonical"[^>]*>'`
(works directly against production; staging needs the server-local
`Host:` header trick documented in this session due to SiteGround's edge
block on external non-browser clients).

**Not available with current tooling — do not fabricate, flag as
"unavailable" at each checkpoint instead:** organic landing-page sessions,
product-category visits, and CTA click counts all live in GA4, and no GA4
Reporting API credential/tool is connected in this environment. If Andrew
wants these tracked, they need a GA4 pull added separately (new capability,
not something to improvise here).

## Baseline (day 0, captured 2026-07-11, cumulative all-time through
last GSC sync 2026-07-08 — 3 days of pre-change data since the sync itself
predates the deployment by hours, so this is effectively the true pre-change
state)

| Page (ID) | Target query | Baseline clicks | Baseline impr | Baseline avg pos | Baseline SEO title | Baseline canonical |
| --- | --- | --- | --- | --- | --- | --- |
| top-bridge-books-for-kids (88) | bridge books for kids | 22 | 1,545 | 7.24 | (default template, no override) | self-ref, non-www |
| what-are-bridge-books-guide... (90) | what is a bridge book | 3 | 897 | 3.79 (partly inflated by mismatched "origins of bridge" queries — see session finding) | (default template) | self-ref, non-www |
| mount-everest-facts-for-kids (76) | mount everest facts for kids | 9 | 589 | 10.96 | (default template) | self-ref, non-www |
| bridge-books-for-kids-mount-everest (52) | bridge books for kids (secondary) | 0 | 62 | 12.39 | (default template) | self-ref, non-www |
| books-like-magic-tree-house (46) | books like magic tree house | 43 | 1,537 | 8.97 | (default template) | self-ref, non-www |
| kirkus-review-adventures-of-charlotte-and-henry (72) | adventures of charlotte and henry / mismatched "henry" queries | 0 | 281 | 8.41 | (default template) | self-ref, non-www |

**Post-change values (verified live 2026-07-11, immediately after
deployment):**

| Page | New SEO title | New focus keyword |
| --- | --- | --- |
| 88 | Top Bridge Books for Kids (Ages 6-9) - Brave Hearts Publishing | bridge books for kids, bridge books for kids ages 6-9, transitional chapter books |
| 90 | What Are Bridge Books? A Guide for Parents - Brave Hearts Publishing | what is a bridge book, bridge books guide for parents |
| 76 | 10 Mount Everest Facts for Kids (Ages 6-9) - Brave Hearts Publishing | mount everest facts for kids, facts about mount everest for kids, everest facts for kids |
| 46 | 8 Books Like Magic Tree House (Ages 6-9) - Brave Hearts Publishing | books like magic tree house, books similar to magic tree house, magic tree house alternatives |
| 72 | Adventures of Charlotte and Henry Kirkus Review - Brave Hearts Publishing | adventures of charlotte and henry kirkus review, charlotte and henry book review, brave hearts publishing kirkus |

Also changed: post 52's CTA now links to `/product-category/mount-everest/`
(previously unlinked text); product 17 (Everest hardcover) recategorized
from Uncategorized to Mount Everest; post 72's purchase CTA now leads with
on-site product-category links (Amazon retained as secondary), and 14
hardcoded `www` internal links corrected to non-www.

## What to record at each checkpoint

For each of the 6 pages above:
- impressions, clicks, CTR, average position (cumulative since 2026-07-11,
  compared to the day-0 baseline)
- primary query movement (the query named in the table above)
- secondary query movement (the other secondary keywords set in each
  page's focus-keyword field)
- indexed canonical URL (re-check the rendered `<link rel="canonical">`
  tag — confirm it's still self-referencing non-www)
- rendered SEO title (re-check the live `<title>` tag matches what was set;
  catches any Rank Math override getting reset by a future edit)
- organic landing-page sessions, product-category visits, CTA clicks —
  mark "unavailable, no GA4 tool connected" unless that gap gets closed
- any unexpected loss of visibility (a page dropping out of the index
  entirely, a sudden impression collapse, a 404/redirect where there
  wasn't one before)

## Interpretation rules (apply at every checkpoint)

- **Do not treat 7-day movement as a verdict.** Google's re-crawl and
  re-snippet cycle for a title/meta change is not instant and is noisy at
  small volumes — several of these pages have single-digit weekly clicks,
  where normal variance alone can double or halve a number.
- **28-day is the first checkpoint with enough volume to say anything with
  moderate confidence** for the higher-traffic pages (88, 46); the
  low-volume pages (52, 72, 90's true relevant-query subset) likely still
  won't have enough signal even at 28 days — say so explicitly rather than
  force a read.
- **90-day is the real decision point.** Report whether there is enough
  data to conclude anything, and for which pages specifically — it's
  normal for some to have enough and others not to.
- A page holding flat or even dipping slightly is not automatically a
  failure — rule out normal seasonal/volatility causes (cross-check against
  sitewide impression trends, not just the single page) before calling
  anything a regression.
