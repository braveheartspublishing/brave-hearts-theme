# UTM and Attribution Standard

One consistent naming convention for every channel this site will ever
link from. All values lowercase, hyphen/underscore-separated (never
spaces), machine-safe.

## Parameters

| Parameter | Purpose | Required? |
|---|---|---|
| `utm_source` | Where the click came from (platform) | Always |
| `utm_medium` | The channel type | Always |
| `utm_campaign` | The specific campaign/initiative | Always |
| `utm_content` | Distinguishes creative variants within a campaign | When more than one creative exists |
| `utm_term` | Paid search keyword | Paid search only |

## Additional attribution fields (not standard UTM, tracked alongside)

- `landing_page` — the destination slug, captured server-side/client-side alongside UTMs
- `creative_id` — internal identifier for the specific image/design asset, when applicable
- `blog_slug` — source blog post, for content-engine attribution
- `design_variant` — one of `problem-led` / `outcome-led` / `curiosity-led` / `resource-led` (see Pinterest system)
- `experiment_variant` — set only when a visit lands inside an active A/B test

## Standard values by channel

| Channel | `utm_source` | `utm_medium` |
|---|---|---|
| Pinterest | `pinterest` | `organic_social` (or `paid_social` once ads exist) |
| Instagram | `instagram` | `organic_social` |
| TikTok | `tiktok` | `organic_social` |
| YouTube | `youtube` | `organic_social` or `video` |
| Facebook | `facebook` | `organic_social` |
| Google organic (campaign-tagged) | `google` | `organic` |
| Teacher outreach | `teacher_outreach` | `email` or `direct_outreach` |
| Email (Mailchimp/HubSpot campaigns) | `mailchimp` or `hubspot` | `email` |
| Quiz funnel | `onsite` | `quiz` |
| Reluctant Reader popup | `onsite` | `popup` |
| Paid campaigns (future) | platform name, e.g. `google` | `cpc` |

## `utm_campaign` naming pattern

`<audience_or_problem>_<theme>` — lowercase, underscore-separated.

Examples:
- `reluctant_reader_transition`
- `teacher_classroom_guide`
- `complete_collection_launch`

## `utm_content` naming pattern

`<blog-slug>_<design-variant>_<version>`

Example: `blog-mariana-trench-facts_problem-led_v1`

## Worked examples per channel

```
Pinterest (organic pin, problem-led creative, v1):
utm_source=pinterest&utm_medium=organic_social&utm_campaign=reluctant_reader_transition&utm_content=blog-mariana-trench-facts_problem-led_v1

Instagram (organic post, outcome-led creative):
utm_source=instagram&utm_medium=organic_social&utm_campaign=reluctant_reader_transition&utm_content=blog-mariana-trench-facts_outcome-led_v1

Facebook (organic post):
utm_source=facebook&utm_medium=organic_social&utm_campaign=complete_collection_launch&utm_content=blog-charlotte-henry-series-overview_curiosity-led_v1

TikTok:
utm_source=tiktok&utm_medium=organic_social&utm_campaign=reluctant_reader_transition&utm_content=video-mariana-trench-facts_v1

YouTube:
utm_source=youtube&utm_medium=video&utm_campaign=complete_collection_launch&utm_content=video-series-overview_v1

Google organic (campaign-tagged content, e.g. a bio link):
utm_source=google&utm_medium=organic&utm_campaign=reluctant_reader_transition

Teacher outreach (direct email to a school contact):
utm_source=teacher_outreach&utm_medium=email&utm_campaign=teacher_classroom_guide&utm_content=classroom-guide-offer_v1

Email newsletter campaign:
utm_source=mailchimp&utm_medium=email&utm_campaign=complete_collection_launch&utm_content=newsletter-2026-07_v1

Quiz funnel internal link (e.g. from a blog CTA into the quiz):
utm_source=onsite&utm_medium=quiz&utm_campaign=reader_profile_quiz

Reluctant Reader popup (for cross-referencing internal funnel attribution, not an external click):
utm_source=onsite&utm_medium=popup&utm_campaign=reluctant_reader_adventure_kit

Paid campaign (future, Google Ads example):
utm_source=google&utm_medium=cpc&utm_campaign=complete_collection_launch&utm_content=search-ad_v1&utm_term=chapter+books+for+kids
```

## Rules

- Always lowercase. Never mix case (`Pinterest` vs `pinterest` fragments reporting).
- Never use spaces — use hyphens within a value segment, underscores to
  join segments (matches the examples above).
- Every paid/organic social link and every blog-to-Pinterest pin must
  carry all four core parameters (`source`, `medium`, `campaign`,
  `content`) — `content` is the one most often skipped and is exactly
  what distinguishes creative performance in the Pinterest system's
  30/60/90-day review (see `content-engine/` architecture).
