# Roadmap — Brave Hearts Publishing

Do not reopen items marked Completed. If new evidence suggests a completed item needs revisiting, that's a new roadmap item referencing the old one, not a reopening.

## Completed

- Core theme + WooCommerce storefront (3 books, 2 formats each, Stripe live mode)
- Bookvault paperback fulfillment integration
- Subsidized flat-rate shipping ($3.99)
- Parent funnel (Reluctant Reader Adventure Kit popup + landing page)
- Teacher funnel (Mariana classroom-guide popup, `/teachers/`-only)
- Kirkus credibility component (production, 2026-07-04)
- Amazon customer review showcase (production, 2026-07-05)
- Side-cart drawer + bundle-pricing UX overhaul
- Brave Hearts Bundle Pricing plugin (Complete Collection logic, discount protection)
- [PARENT_COUPON_CODE_SUPERSEDED] Collection-only coupon (production, 2026-07-11)
- Phase 1B analytics event architecture (ecommerce + business-custom events, code-side)
- Phase 1C lead-capture architecture
- Phase 1D organic conversion architecture (content classification, CTA engine, campaign landing, conversion scoring) — staging
- CTA Engine isolated subset (production, 2026-07-12)
- GTM container build: variables, triggers, tags (27/39/40, incl. Phase 9 minimum gap patch 2026-07-12) — built, verified, not published
- Order provenance / economics dashboard (Phase 1A-v2)
- SEO content-operations system (weekly slates, taxonomy, Pinterest packaging) — see `brave-hearts-seo-engine` repo
- Public/private knowledge-base split + governance hardening (2026-07-13)
- Consent CMP vendor research (Complianz/CookieYes/Cookiebot/Real Cookie Banner compared) — 2026-07-13; recommendation (CookieYes) superseded same day, see below
- GA4 event dataLayer spot-validation (4 of ~10 event categories confirmed) — 2026-07-13
- Legacy blog content audit (35 posts, read-only, 5 remediation packets prepared) — 2026-07-13

## In Progress

- Consent-management staging implementation — WPConsent Free (WordPress.org, no account required), CookieYes/SaaS-CMP path rejected 2026-07-13; see `CURRENT_TASK.md` and `ANALYTICS/CONSENT_STATUS.md`.

## Planned (approved direction, not yet started)

### Authentic Mariana interior reshoot and gallery replacement

- **Status:** QUEUED
- **Priority:** After the current trust-focused website buildouts
- **Current-gallery blocker:** No — the seven-item Mariana gallery is live and approved on staging and is not waiting on this
- **Owner:** Andrew
- **Execution:** Commerce/CX with approved design support
- **Dependency:** Complete the remaining trust-focused website buildouts first

**Scope**

1. Reshoot three spreads under controlled natural window light: the whale spread, the depth-diagram / Brave Learning spread, and the Thank-you / Glossary spread.
2. Use the genuine photographs. **No generative background replacement inside the book boundaries.**
3. Preserve every printed letter, illustration, page edge and proportion.
4. Produce the consistent navy-background presentation **by masking and compositing only**.
5. Replace the current versions on staging, verify at 100% and 200% against the originals, run mobile / gallery / accessibility QA, and obtain Andrew's visual approval before any production decision.

**Why this exists (2026-08-02).** The three spreads currently in the gallery came from a Higgsfield pass that re-rendered the *whole frame*, not only the background — so the printed body text inside the pages was regenerated and carries visible artefacts under close inspection (e.g. "Hampback whales", "Mount Ererest", "Hodal Zone"). Verified by comparing the same spread in Andrew's own capture (`IMG_3760.mov`) against the Higgsfield version: "Jacques-Yves Cousteau / Aqua-Lung / Calypso / Sylvia Earle" renders as "Jacqnes-Yvcs Couste / Aqma-Lung / Calypro / Sylvia Ectrlo". These are **not** errors in the printed book.

No authentic photograph of these three spreads exists on the Drive, and `IMG_3760.mov` stops at Chapter 2, so none of the three can be recovered from existing material — a reshoot is the only route.

**Owner decision recorded (2026-08-02):** Andrew reviewed the artefacts and **knowingly approved retaining the current images temporarily**, because the overall gallery presentation is strong and the artefacts are only visible under close inspection. The current images must not be removed, replaced, regenerated, retouched or reordered until this task is executed.

- Google Merchant Center disapproval remediation — all 6 products currently disapproved; needs Andrew's console access
- Finish GTM Preview/GA4 DebugView validation (remaining event categories + true DebugView, not just dataLayer)
- First legacy-blog remediation batch (5 packets ready in `CONTENT/LEGACY_BLOG_CONVERSION_AUDIT.md`)
- GTM event-coverage: 6 events (`bhp_direct_purchase_click`, 2 `customer_review_*_click`, `customer_review_impression`, 2 Kirkus events) deferred by explicit 2026-07-12 CSO decision, not scheduled — `bundle_type_purchased` closed 2026-07-12
- Mailchimp deliverability audit
- Staging→production test-suite parity (full theme-ZIP cycle)
- Full Phase 1D/1E suite promotion to production (campaign landing, conversion scoring, content-intelligence engine) — currently staging-only by design

## Deferred (explicitly decided not now, not forgotten)

- Cart/checkout placement for Kirkus/Amazon-review trust components — needs a formal UX audit first
- CTA-click-after-exposure attribution (Kirkus/Amazon-review) — needs session-correlation logic touching cart/checkout-adjacent code
- Adventure Quiz experiment
- Article-generation automation beyond the existing weekly content-operations cycle
- Meta Ads integration (architecture documented, not built)
- Full scheduled/state-driven marketing automation system with approval gates — durable future direction, no infrastructure built yet

## Cancelled

- None on record.
