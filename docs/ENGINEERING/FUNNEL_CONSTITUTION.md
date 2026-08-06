# Audience Funnel Architecture (Frozen) — The Brave Hearts Funnel Constitution

**Decision Date: 2026-07-14. Status: Permanent Company Architecture.** This document is canonical and supersedes any conflicting framing in `AUDIENCE_FUNNEL_ARCHITECTURE.md` (which remains the technical naming/tracking reference — this file is the business/architectural mandate that document implements). Every future Claude session must read this before touching marketing, funnels, Mailchimp, landing pages, blogs, SEO, or advertising.

## Mission

Brave Hearts Publishing will not market from a generic homepage or generic newsletter. Every significant customer audience enters a dedicated conversion funnel built specifically for that audience. This architecture is the permanent foundation of the company's marketing, outreach, SEO, social media, advertising, email marketing, and future product launches. Future books, destinations, lead magnets, campaigns, and audiences must plug into this architecture rather than creating parallel systems.

## Core Philosophy

Every audience has different motivations. Therefore every audience deserves: dedicated messaging, dedicated landing page, dedicated lead magnet, dedicated popup, dedicated Mailchimp journey, dedicated tracking, dedicated analytics, dedicated KPIs, dedicated conversion optimization. The objective is to make every visitor feel that Brave Hearts Publishing understands exactly why they arrived.

## Canonical Funnel Structure

Every audience funnel follows this sequence:

```
Traffic Source
  ↓
Audience Landing Page
  ↓
Audience-Specific Popup
  ↓
Audience-Specific Lead Magnet
  ↓
Email 1 — Deliver promised resource, build trust
  ↓
Email 2 — Sell the audience-specific result, present the Complete Collection (no coupon)
  ↓
Purchase Decision
  ↓
  Purchased?
   │
   Yes → Post-Purchase Nurture (full-margin Email 2 conversion, tracked)
   No  → Email 3 (audience coupon)
  ↓
30–60 Day Follow-up
  ↓
Long-Term Relationship
```

## Email Philosophy

**Email 1** — Deliver exactly what was promised. Never oversell. Build trust.

**Email 2** — The primary sales email. Its job is NOT to sell books. Its job is to sell the RESULT. The books are simply the vehicle. Every audience should clearly understand the transformation Brave Hearts hopes to help create:
- Parents: curiosity, reading confidence, excitement for discovery, family reading time
- Teachers: reading engagement, classroom discussion, geography, science, vocabulary, SEL
- Gift Buyers: meaningful gifts, lasting memories, educational value
- Organizations: literacy, engagement, community programs
- Bookstores: repeat customers, educational value, strong series potential

The Complete Collection is presented as the natural next step toward that outcome. No coupon in Email 2.

**Email 3** — Exists only for subscribers who have NOT already purchased. Its purpose is to reduce purchase friction: reinforce the audience-specific result, provide the audience-specific coupon, invite purchase. It never becomes the primary sales message.

## Purchase Suppression Rule

If a subscriber purchases before Email 3: immediately remove them from the coupon journey. Do NOT send the first-purchase coupon. Instead, enter the customer into a purchase-specific nurture journey and track them as a full-margin Email 2 conversion.

**Purchase scope — resolved 2026-07-15/16, Frozen:** the suppression rule applies to **any valid Brave Hearts Publishing purchase**, not just Complete Collection purchases. An individual paperback, an individual hardcover, the Complete Collection paperback, the Complete Collection hardcover, or any other legitimate product purchase that successfully enters the WooCommerce-to-Mailchimp customer sync all suppress the purchaser from the current pre-purchase coupon path. Reason: a person who has already purchased should stop receiving the automated acquisition coupon sequence regardless of which product they bought — they may later receive appropriate purchaser, series, launch, or Collection communication through a post-purchase path (see below), but must not continue through the immediate pre-purchase discount sequence. This closes the previously open `REQUIRES ANDREW DECISION` flag in `KNOWN_ISSUES.md`/`AUDIENCE_IMPLEMENTATION_MATRIX.md` — do not reopen it. Live behavior (`Global - Tag Purchasers` triggers on "Contact buys any product from your store") already matched this rule before it was formally ratified.

**Confirmed 2026-07-16 via a controlled staging test:** the purchaser tag is applied automatically regardless of the contact's marketing-subscription status — a contact synced as "transactional" (not subscribed to marketing) was still tagged by `Global - Tag Purchasers`. Cancelling the order afterward did **not** remove the tag — no automation currently reverses purchaser-suppression on refund/cancellation (see Post-Purchase Target State below).

## Post-Purchase Philosophy

Once someone purchases, let the books do the work. Avoid immediately trying to sell again. Approximately 30–60 days later, send a follow-up based on exactly what the customer purchased:
- **Single-book purchasers**: ask how the adventure went, invite them to explore the remaining books.
- **Complete Collection purchasers**: ask how the series is going, encourage reviews, introduce future adventures.

Never attempt to resell the identical product immediately after purchase.

**Post-Purchase Target State — technical gap specification (written 2026-07-15/16, not yet built):** no post-purchase automation exists for any of the five audiences as of this writing. Already-canonical elements (derivable from decisions already made in this document, no further approval needed to build them): purchaser acquisition-path exit via the existing If/Else Yes-branch; the `Customer - Purchased` tag as the trigger signal; immediate exclusion from the coupon Email 3; no duplicate entry (the tagging automation's re-entry is disabled). **Still requires Andrew's decision before this can be built:**
- Exact follow-up timing within the 30–60 day window (no canonical value fixed anywhere yet).
- Exact feedback/review request wording and destination platform (on-site, Amazon, email reply) — must comply with the standing no-fabricated-review rule.
- Whether/how future series or launch eligibility is mentioned, and what that eligibility actually consists of.
- Refund/cancellation handling: **confirmed 2026-07-16 that cancelling an order does NOT automatically remove the `Customer - Purchased` tag** — a refunded/cancelled purchaser remains suppressed from acquisition coupons indefinitely under current behavior. Andrew must decide whether this is acceptable or whether a tag-removal mechanism is needed.
- Audience-attribution preservation: no existing tag or merge field records which specific audience/lead-magnet originally acquired a contact, separately from the generic `Customer - Purchased` tag — referencing "which audience brought this contact in" in a post-purchase email would require a new tagging scheme, not just new copy.

## Audience Attribution

Every funnel retains, where technically possible: Audience, Traffic Source, UTM Source, UTM Medium, UTM Campaign, Landing Page, Lead Magnet, Popup, Journey, Coupon, Product Purchased, Format Purchased, Order Value, Purchase Date, Revenue. These data points let Brave Hearts determine exactly which audience and acquisition channels generate revenue.

## Audience-Specific Coupons

Coupons are part of attribution:
- Parents: [PARENT_COUPON_CODE_SUPERSEDED]
- Teachers/Librarians: [EDUCATOR_COUPON_CODE_SUPERSEDED]
- Gift Buyers: [GIFT_BUYER_COUPON_CODE_SUPERSEDED]
- Future audiences may receive additional audience-specific codes.

Coupons are only sent to qualified non-buyers.

## Audience Coupon Policy (added 2026-07-14, Frozen)

Do not hardcode or publicly render audience-specific coupon codes in themes, plugins, landing pages, posts, or navigation. Audience coupon delivery is controlled outside public page templates — coupons ([PARENT_COUPON_CODE_SUPERSEDED], [EDUCATOR_COUPON_CODE_SUPERSEDED], [GIFT_BUYER_COUPON_CODE_SUPERSEDED], and future audience codes) are delivered only via the Email 3 step of the relevant audience's Mailchimp journey, after signup, lead magnet delivery, Email 2, and purchase verification. No product page, collection page, homepage, navigation, footer, blog article, landing page, banner, or static promotional copy may advertise a coupon code.

**Correction made 2026-07-14:** `plugins/brave-hearts-bundle-pricing/includes/bundle-landing-page.php` publicly rendered an [PARENT_COUPON_CODE_SUPERSEDED] coupon-code line on the Complete Collection page. The line and its now-unused CSS rule were removed (plugin v1.8.2 → v1.8.3), no replacement discount messaging was added, and the change was deployed to staging then production and verified live. The underlying WooCommerce [PARENT_COUPON_CODE_SUPERSEDED] coupon itself was not modified.

**This decision is Frozen** — future funnels' coupons ([EDUCATOR_COUPON_CODE_SUPERSEDED], [GIFT_BUYER_COUPON_CODE_SUPERSEDED], etc.) must comply.

## Modular Automation Rule

Build reusable automation modules. Do NOT build one giant audience-specific workflow. The intended architecture is:

```
Module: Email 1 (delivery)
  ↓
Module: Transformation Email (Email 2 — result-focused)
  ↓
Module: Purchase Check
  ↓
Module: Coupon (Email 3)
  ↓
Module: Post-Purchase
  ↓
Module: Long-Term Nurture
```

Future audience funnels reuse this structure by changing only: audience copy, lead magnet, coupon, attribution, destination. The automation architecture itself stays consistent.

## Audience Routing Constitution (added 2026-07-14, permanent)

**Known audiences never use a routing quiz.** Parents, Teachers/Librarians, Gift Buyers, Bookstores, and Organizations are each always sent directly to their own dedicated landing page — audience-specific popup, audience-specific lead magnet, audience-specific Mailchimp journey, no quiz, no intermediate routing step. This is the default and permanent behavior for every direct-audience acquisition channel (paid ads targeting a known audience, direct links, email campaigns, partner referrals, anything where the audience is already known before the click).

**Unknown audiences** — visitors arriving with no known audience signal, from organic sources: SEO, blog posts, Pinterest, organic social, shared links, AI search/answer engines, and any future organic-discovery channel — will eventually be routed through a new **Audience Routing Quiz**. This is a **future capitalization layer, not part of any current sprint**. It is recorded here as frozen architecture so no future session reinvents or contradicts it, and so no future session builds it prematurely, before every core audience funnel is production-complete and validated.

**Quiz purpose:** determine which audience an unknown visitor belongs to *before* presenting a lead magnet, so the offer they receive is already audience-correct rather than generic.

**Quiz flow (future build, not yet started):**
```
Unknown Visitor
  ↓
Audience Quiz Popup
  ↓
Select Audience
  ↓
Audience-Specific Offer
  ↓
Email Signup
  ↓
Correct Audience Journey (the same per-audience Mailchimp journey a direct visitor to that audience's landing page would enter — the quiz is a router into the existing funnels, not a parallel funnel)
  ↓
Success Screen
  ↓
Offer two choices:
  "Continue reading this article" OR "Visit the matching audience landing page"
  (e.g. "Continue Reading" or "Visit the Parent Guide"; "Continue Reading" or "Visit the Teacher & Librarian Guide")
```

**The quiz must be suppressed on:** every audience landing page (a visitor there has already self-selected — showing the quiz would be redundant and could re-route them wrong), cart, checkout, account pages, thank-you pages, existing subscribers, visitors who dismissed it recently, and any visitor already classified by audience (existing tag/cookie/UTM signal).

**Build sequencing (binding):** this routing layer is built **only after** every core audience funnel (Parent, Teacher/Librarian, Gift, Bookstore, Organization) is individually production-complete and validated end-to-end. Building the quiz before the underlying audience journeys exist would give it nothing correct to route into. Do not start this work as a side effect of any other sprint — it gets its own dedicated future sprint.

**This routing layer supplements — it does not replace — the direct audience landing pages and funnels.** A known-audience visitor's experience never changes because the quiz exists; the quiz only affects visitors who would otherwise land on generic, unclassified content.

## Audience Routing Quiz — build status reconciliation (added 2026-07-29)

The "Audience Routing Constitution" section above describes the quiz as a **future capitalization layer**, "not part of any current sprint," gated behind every core audience funnel being production-complete. **The quiz has since been built and is live on production** (theme **1.19.91**, deployed 2026-07-20 with Andrew's explicit approval; it is currently the only sitewide popup). This note records that live state so no future session is misled by the older text — it is a currency correction under the Amendment Process's "leave the old section in place with a note" rule, **not** a change to any frozen policy, and no prior text was edited.

Everything the section above mandates about *how* the quiz behaves remains in force and is implemented: known audiences still go directly to their own landing page/popup/lead magnet/journey and never through the quiz; the quiz addresses unknown/organic visitors only; it routes into the existing per-audience funnels rather than creating a parallel one; it stays suppressed on the audience landing pages, cart, checkout, account and thank-you pages; it never captures email itself. The build sequencing clause is the only part overtaken by events.

Refinement work on 2026-07-29 (staging 1.19.93 and 1.19.95) changed copy, per-answer result content, CTA labels, accessibility and modal presentation only — **no questions added, no audience destinations changed, no funnel redesign.** See `../RELEASES/QUIZ_UX_PERSONALIZATION_1_19_93.md`.

## Interactive Authentication Rule

Andrew is available during development. If authenticated access is required (Mailchimp, Bookvault, WooCommerce, Google Analytics, Google Tag Manager, Search Console, Stripe, or any required platform): stop, prompt Andrew to log in with the exact service/page/reason/action needed, resume the sprint immediately after authentication. Do not defer implementation simply because authentication is required.

## Future Expansion Rule

Every future book, destination, audience, lead magnet, campaign, blog, social campaign, Pinterest campaign, email campaign, or paid advertisement must integrate into this architecture. Do not build parallel funnel systems. Improve this architecture rather than replacing it.

## Email 2 clarifications (added 2026-07-15, Frozen)

Two refinements to the Email Philosophy section above, confirmed as gaps against the existing text and folded in here rather than as a separate document:

**Email 2 opens with a check-in on the lead magnet, not a pitch.** Its first line asks whether the subscriber engaged with the resource just delivered, before pivoting to the audience-specific result:
- Parents: "Have you had a chance to try one of the activities with your child?"
- Teachers: "Have you had an opportunity to look through the classroom activities?"
- Gift Buyers: "Were the gift ideas helpful?"
- Retailers: "Did you have a chance to review the retailer information?"
- Organizations: "Did you get an opportunity to review the partnership guide?"

**Email 2's CTA always links to the audience's own landing page, never directly to checkout or the Complete Collection page.** The landing page is what performs the selling (it already embeds the Complete Collection presentation) — Email 2's job is to reconnect the subscriber with that experience, not to duplicate it inline. Landing-page destinations: Parents `/reluctant-reader-adventure-kit/` (existing canonical page, not `/parents/`), Teachers `/educators/`, Gift Buyers `/gift-buyers/`, Retailers `/retailers/`, Organizations `/organizations/` — confirm each against the actual live slug before building, since generic slugs guessed here may not match the real page filename.

**If the promised resource isn't actually available yet, the journey stays paused rather than sending Email 1 anyway.** This matches the existing lead-magnet gating pattern (`bhp_get_lead_magnet_pdf_url()` returning empty keeps a page's signup form in "Coming Soon" state) — the same honesty rule now applies explicitly to the email side: never claim a resource was delivered if the underlying file isn't set.

## Amendment Process (added 2026-07-15)

This document is Frozen, but Frozen does not mean immutable — it means changes require a deliberate, dated addendum, never a silent edit to existing frozen text. To amend:
1. State the proposed change and why the current text doesn't already cover it.
2. Get Andrew's explicit, current-turn approval for the change (a prior approval for a different change does not carry over — same rule as production deploys).
3. Add a new dated section (`(added YYYY-MM-DD, Frozen)`) rather than editing prior frozen sections in place — the history of what changed and when stays visible.
4. If a new section genuinely supersedes an old one, say so explicitly in the new section and leave the old one in place with a note, rather than deleting it (matches this project's existing `DECISIONS.md` convention).

## Guiding Principle

Brave Hearts Publishing is not building a website that sells books. It is building a conversion-driven educational publishing company where every audience experiences a journey tailored to their needs, every conversion is measurable, and every future product strengthens the same scalable acquisition system.

**The test for every future decision:** "Does this strengthen the Funnel Constitution, or does it create a parallel system?" If it creates a parallel system, don't build it. If it strengthens the constitution, it belongs.

---

## AMENDMENT — 2026-08-04 · Owner ruling: coupon placement moves to Emails 1 and 2

**Andrew Signore, verbatim, 2026-08-04 (witnessed by the chief-of-staff main session; record: Business OS chief-of-staff handoff register §17):**

> "Amend constitution: coupon moves to email 1 and 2 per my new sequence design."

**What changes:** in each lead-magnet sequence, the discount code may now appear in **Email 1** (bottom placement, after the resource) and **Email 2** (the single reminder). The prior rule — coupon only later in the sequence — is superseded on placement ONLY.

**What does NOT change:** mandatory purchase suppression stands in full and must gate the coupon wherever it appears — under the new design the suppression If/Else sits **before Email 2** (and buyers entering the sequence are handled per the suppression rules as before). Funnel isolation, no-coupon policy for the Organization funnel, and every other clause of this constitution are untouched.

**Design of record:** the three-email restructure (E1 resource + code at bottom; E2 single honest reminder; E3 feedback ask) — see the marketing-growth sequence drafts of 2026-08-04. No coupon code strings appear in this public file; codes live only in the gitignored private reference.
