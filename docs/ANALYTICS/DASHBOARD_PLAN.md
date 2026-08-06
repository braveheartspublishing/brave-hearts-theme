# Dashboard Plan (Design Only — Not Built)

From `docs/gtm-ga4-production-readiness-audit-2026-07-12.md` §11. Recommend building exactly one executive dashboard first, not several, until validated with real data.

## Contents
Sessions, Users, Revenue, Conversion Rate, Average Order Value, Collection Sales vs. Individual Book Sales (via `bundle_type_purchased` vs. filtered `purchase`), Coupon Usage (`purchase.coupon`, needs the coupon-lifecycle events to fully separate applied/rejected/used), Adventure Kit Opt-ins (`adventure_kit_signup` count), Top Landing Pages, Top Blogs, Top CTAs (`contextual_cta_click` grouped by `cta_id`), Top Traffic Sources (GA4 default channel grouping), Top Products.

## Prerequisite funnel reports (design only, from the same audit §10)
Organic Blog Funnel, Pinterest Funnel, Collection Funnel, Adventure Kit Funnel, Teacher Funnel, Amazon Funnel, Book Funnel, Email Funnel (needs the not-yet-built email click-through event), Coupon Funnel (needs the not-yet-built coupon lifecycle events).

## Recommended custom dimensions
Book, Format, CTA ID, Collection, Source, Campaign, Content Group, Audience, Coupon, Landing Page, Blog Category, Reader Stage, Parent/Teacher Journey. Full mapping to existing parameters in the audit doc §8.

## Status
Nothing built. This is a design reference for when GA4 is actually receiving data — building dashboards against an empty property produces nothing useful.
