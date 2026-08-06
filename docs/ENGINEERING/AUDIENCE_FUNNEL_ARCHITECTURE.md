# Audience Funnel Architecture (Shared Specification)

**Status: canonical, 2026-07-13, updated 2026-07-14.** This is the technical naming/tracking/component reference. The business/architectural mandate this document implements — the permanent, frozen company policy every funnel must follow — is `FUNNEL_CONSTITUTION.md`. Read that document first; it governs the canonical funnel sequence, email philosophy, purchase-suppression rule, modular-automation requirement, and (added 2026-07-14, Frozen) the **Audience Coupon Policy**: audience coupons ([PARENT_COUPON_CODE_SUPERSEDED], [EDUCATOR_COUPON_CODE_SUPERSEDED], [GIFT_BUYER_COUPON_CODE_SUPERSEDED], and future codes) are never public marketing offers — they exist only inside their audience funnel's Email 3, delivered after signup/lead-magnet/Email 2/purchase-verification. No landing page, product page, or public-facing content this document's model produces may advertise a coupon code. A real violation of this was found and fixed 2026-07-14 (see `DECISIONS.md`). This document formalizes the reusable audience-funnel model referenced by `START_HERE.md`/`NEXT_TASK.md`. It does not describe a new system — it documents naming conventions and a tracking model on top of infrastructure that mostly already exists (the `BHP_Campaign_Landing` framework, the generic popup engine, the lead-magnet settings screen, the signup-form component, and the existing GA4 dataLayer pattern), and defines what future audience funnels (Teacher/Librarian/Homeschool, Bookstores & Retailers, Gift Buyers, Organizations & Community Programs) must supply to plug into it.

## The model

```
Audience → Problem → Landing Page → Lead Magnet → Email Sequence → Primary Offer → Follow-up → KPI Dashboard
```

Every audience funnel is a distinct, isolated instance of this chain. Funnels never share popup storage keys, analytics event prefixes, or Mailchimp tags with each other — see `.claude/rules/funnels.md` for the existing hard isolation rule (Parent vs. Teacher), which this document extends to all future audiences.

## Existing reusable technical layer

**`BHP_Campaign_Landing`** (`inc/class-bhp-campaign-landing.php`, Phase 1D, currently staging-only) is the config-driven landing-page framework: a config array with required keys `campaign_id`, `audience`, `funnel_stage`, `lead_offer`, `cta_goal`, `blocks` renders a page from a fixed block order — `hero → benefits → trust → resource_preview → product → signup_form → amazon_alt → related_content` — using only components that already carry the site's approved design system (`hero.php`, `signup-form.php`, `final-cta.php`, `teacher-resources-cta.php`, `amazon-review-showcase.php`, `kirkus-credibility.php`). It does not introduce new visual design. `example_adventure_kit_config()` in the same file demonstrates the shape for the Parent funnel without touching the live page.

**Current status:** this framework is staging-only (see `PROJECT_STATE.md` — "Full Phase 1D/1E suite... remains staging-only"). The live Parent funnel landing page (`page-reluctant-reader-adventure-kit.php`) predates it and is a hand-built template, not yet migrated onto this framework. Future audience funnels (Teacher/Librarian, Bookstore, Gift, Organization) should be built directly on `BHP_Campaign_Landing` once it's promoted to production, rather than as new hand-built templates — this is the intended payoff of formalizing this now. Migrating the existing Parent page onto the framework is a future task, not part of this sprint (out of scope per this sprint's own "do not modify unrelated systems" instruction).

**Other reusable pieces already in place:**
- `template-parts/acquisition/signup-form.php` — shared form component (audience_type, lead_magnet, source_page fields; nonce; honeypot field `bhp_website`).
- `assets/js/mariana-popup.js` — generic popup engine driven entirely by a `data-popup-config` JSON attribute (eventPrefix, source, storagePrefix, thankYouPath, trigger). Extending to a third+ audience means adding a new config, never forking the engine (see `.claude/rules/funnels.md`).
- `inc/lead-magnet-settings.php` — admin screen (`Settings → Lead Magnets`) mapping a lead-magnet key to a same-host HTTPS PDF URL. A landing page/popup stays disabled ("coming soon") until its key has a real URL — no guide is ever substituted. Adding an audience's lead magnet means adding one key to `BHP_LEAD_MAGNET_PDF_KEYS` and one field to the settings page.
- `bhp_mailchimp_signup_tags` filter (`functions.php`) — context-aware tag/merge-field mapping already used to differentiate `Source: Parent Popup` vs `Source: Parent Landing Page` vs `Source: Teacher Popup`. New audiences extend this filter with their own context keys.

## Naming conventions

Every convention below follows the pattern already established by the two live funnels (Parent, Teacher) — new audiences should look identical in shape, different only in the audience token.

| Element | Pattern | Existing examples |
|---|---|---|
| WordPress landing page slug | `/<audience-lead-magnet-slug>/` | `/reluctant-reader-adventure-kit/` |
| Thank-you page slug | `/<lead-magnet-slug>-thank-you/` | `/adventure-kit-thank-you/`, `/mariana-guide-thank-you/` |
| Popup storage prefix | `bhp_<audience>_popup` | `bhp_parent_popup`, `bhp_mariana_popup` (legacy name, kept — see `DECISIONS.md`) |
| Popup analytics event prefix | `<audience>_popup` | `parent_popup`, `teacher_popup` |
| Lead-magnet settings key | `<audience>_<descriptor>` | `adventure_kit_parent`, `mariana_teacher`, `mariana_parent` |
| Lead-magnet value sent to Mailchimp | snake_case, human-readable | `reluctant_reader_adventure_kit` |
| Mailchimp `audience_type` field value | snake_case plural | `parents_families`, `teachers` |
| Mailchimp signup-source tag | `Source: <Placement> <Audience Noun>` | `Source: Parent Popup`, `Source: Parent Landing Page`, `Source: Teacher Popup` |
| Mailchimp journey/automation name | `<Audience> — <Purpose>` | e.g. `Parent — Reluctant Reader Adventure Kit` (see Phase 0 finding below on exact current naming) |
| UTM `utm_source` | lowercase channel | `newsletter`, `organic_blog`, `pinterest` |
| UTM `utm_medium` | lowercase channel type | `email`, `popup`, `social` |
| UTM `utm_campaign` | `<audience>_<lead_magnet>_<purpose>` | `parent_adventure_kit_email2`, `parent_adventure_kit_coupon` |
| CTA `data-bhp-event` | `<funnel>_cta_click` or specific verb | `landing_page_cta_click`, `kirkus_review_link_click` |
| Conversion event (GA4 dataLayer) | `<audience>_<action>` or existing generic event where one already fits | `adventure_kit_signup` (existing), see Tracking model below |
| Primary offer | Always named, never "the product" | Complete Collection (parent's primary offer per `DECISIONS.md`/this sprint) |
| Secondary offer | Named explicitly | Individual book purchase |

**Do not use:** `Form 1`, `Campaign 2`, `Landing Page New`, `Test Automation`, `Popup Final`, or any other ambiguous/numbered name in code, Mailchimp, or GTM — every future name must be self-describing on its own, matching the table above.

## Tracking model

| Data point | How it's recorded today | Status |
|---|---|---|
| Audience | `audience_type` form field → Mailchimp merge field/tag | Live (Parent, Teacher) |
| Lead magnet | `lead_magnet` form field → Mailchimp tag | Live |
| Landing page | `source_page` form field (canonical URL) | Live |
| Source / medium / campaign | UTM query params, captured by `BHP_UTM_Attribution` | Live sitewide, see `utm-attribution-standard.md` |
| Signup date | Mailchimp subscription timestamp | Live (Mailchimp-side, not independently verified this sprint — see blockers) |
| Email-sequence membership | Mailchimp automation/journey state | **Not verifiable this sprint — no Mailchimp automation-level access. See Phase 0 findings.** |
| Key email clicks | Mailchimp link click tracking | **Not independently verified this sprint** — same blocker |
| Complete Collection visits | `bundle_page_view` (dataLayer event, `bundle-landing.js`) | Live, but **not yet named `view_complete_collection`** as this sprint's brief requests — see Analytics section of `PARENT_FUNNEL_STATUS.md` |
| Add-to-cart | `add_to_cart` (GA4-standard event, WooCommerce Blocks integration) | Live, confirmed in `event-dictionary.md` |
| Begin checkout | `begin_checkout` | Live |
| Purchase | `purchase` | Live, dedup-guarded (only fires from the real order-confirmation page) |
| Order value | Included in `purchase` event payload | Live |
| Product format (paperback/hardcover) | Included in cart/purchase event payloads | Live |
| Coupon use | WooCommerce coupon-application event + order meta | Live on the WooCommerce side; **Mailchimp-side coupon-click tracking not independently verified this sprint** |

**Privacy:** no personal subscriber data (email, name, address) is ever included in a dataLayer push or written to this repository — every event above carries only audience/offer/page-level identifiers, matching the existing pattern already used by `adventure_kit_signup` and `purchase`.

## Reusable page structure

Matches `BHP_Campaign_Landing`'s block order, expanded with the two additional sections this sprint's brief requires (Collection presentation, FAQ) that the current framework doesn't yet have a dedicated block type for:

1. Hero (audience-specific headline + two CTAs: primary lead-magnet offer, secondary path to primary paid offer)
2. Audience problem
3. Why the current approach may not work
4. Brave Hearts solution
5. Benefits and outcomes (`benefits` block)
6. How the books work
7. Product or collection presentation (`product` block — currently single-book only; Complete Collection presentation needs either a config extension or, as done this sprint, direct markup — see `PARENT_FUNNEL_STATUS.md`)
8. Testimonials or social proof (`trust` block — currently a badge row; full Kirkus/Amazon-review components are used directly rather than through this block on the Parent page, since they need more than a badge)
9. Authority and author story (not yet a dedicated block type — link to `/about/`)
10. Lead magnet (`signup_form` block)
11. FAQ (no dedicated block type yet — built as plain semantic `<details>/<summary>` HTML on the Parent page this sprint, no new component needed)
12. Primary CTA
13. Secondary CTA
14. Tracking and attribution (implicit — every block already carries `data-bhp-*` attributes)

## QA checklist (per funnel)

- [ ] Landing page: desktop, tablet (where practical), mobile — no horizontal overflow, valid HTML, zero PHP/JS errors
- [ ] All CTAs point to their named destination, not a placeholder
- [ ] Lead magnet: correct file, correct URL, no broken link, reasonable file size
- [ ] Signup form: submits successfully, correct tags applied, source attribution survives submission, duplicate subscribers handled safely
- [ ] Thank-you page: correct, isolated from other funnels' thank-you pages
- [ ] Email 1 (delivery): correct PDF link, correct sender identity, mobile-renders, unsubscribe works
- [ ] Email 2+ (nurture/offer): correct merge tags, no unsupported claims, one primary CTA
- [ ] Coupon/offer handoff (if any): correct restrictions, no duplicate sends, no contacts trapped between automations
- [ ] Primary offer destination: correct price, correct format options, correct stock status, no dead links
- [ ] Analytics: every named event in the Tracking Model fires exactly once per real action, never on refresh/back-navigation
- [ ] No funnel-isolation violation: this funnel's popup/storage/tags never touch another funnel's state

## Documentation requirements (per funnel)

Every audience funnel needs its own `docs/ENGINEERING/<AUDIENCE>_FUNNEL_STATUS.md` (this sprint's is `PARENT_FUNNEL_STATUS.md`) recording: current-state inventory at build time, exact naming decisions made (landing page slug, lead-magnet key, Mailchimp tag/journey names, UTM values), what was verified live vs. documented-only, known blockers, and KPI baseline (or explicit "not yet measured" if no data exists — never a fabricated number).

## Future audiences (documented only, not built this sprint)

Per this sprint's explicit scope: only requirements are recorded below. No landing page, lead magnet, form, or Mailchimp journey exists for these yet.

- **Teacher / Librarian / Homeschool** — partially exists already (Mariana classroom guide, `/teachers/` popup+page) but was built before this architecture was formalized; a future task should audit it against this spec the same way this sprint audited Parent.
- **Independent Bookstores and Retailers** — audience problem: discovering the series and understanding wholesale/ordering terms. Primary offer: bulk/wholesale inquiry, not a direct-to-consumer purchase — likely needs HubSpot (not Mailchimp) as the CRM layer per `Customer-Acquisition-Blueprint.md`'s role split, since this is relationship/pipeline data, not newsletter engagement.
- **Gift Buyers** — audience problem: choosing a book/collection as a gift without kid-fluency in the product. Primary offer: likely the Complete Collection with gift-specific framing (not a different product).
- **Organizations and Community Programs** — audience problem: bulk/classroom-set purchasing for a program, not one household. Likely shares infrastructure with the Bookstore/Retailer funnel (HubSpot-side bulk-order pipeline) rather than a fully separate Mailchimp journey.

Large-scale outreach to any of these audiences must not begin until its funnel is built and tested against this same architecture and QA checklist.
