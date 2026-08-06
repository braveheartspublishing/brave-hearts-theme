# Conversion QA Sprint 1 — Complete Live Funnel Validation (2026-07-13)

**Status: audit complete. Two follow-up sprints the same day acted on this sprint's findings — see the updates below.** Production, read-only + one add-to-cart/checkout mechanics pass (no real payment placed, per standing project policy — see "What was not tested" below).

**Update (2026-07-13, Hardcover Fulfillment Verification sprint, later superseded):** initially restored hardcover stock status to `outofstock` as a protective measure after finding no hardcover SKU had ever had a successful Bookvault fulfillment (order #317's true status is `wc-refunded`, not the "draft" this doc originally implied — see the correction in `KNOWN_ISSUES.md`'s history). **P1-1 (malformed blog links):** fixed on posts 38, 64, 88, 90 — this fix stands. See `CONTENT/LEGACY_BLOG_CONVERSION_AUDIT.md`.

**Update (2026-07-13, Print-on-demand stock policy — controlling, supersedes the above):** Andrew established that Brave Hearts is print-on-demand with no physical inventory, so P0-1's premise (mark out of stock pending fulfillment verification) was the wrong mechanism — "out of stock" may only represent a verified fulfillment failure or explicit sales suspension. All 3 hardcover products restored to `instock`, Bookvault mapping re-verified as structurally identical across all 6 current products, confirmed live. See `DECISIONS.md`'s "Print-on-demand stock policy" entry. **Legacy catalog:** draft product 338 (empty broken shell) permanently deleted after Andrew's explicit confirmation; draft product 12 (genuine former Lulu product, 3 real sales) confirmed correctly archived, left untouched.

P1-2, P2-1 through P2-3, P3-1, P3-2 below remain open and unaddressed, as originally reported.

## Scope and method

Live-browser walkthrough of production (`braveheartspublishing.com`) plus targeted WP-CLI/SSH read-only checks, covering every funnel and page type named in the sprint brief: homepage, navigation, search, all 3 individual book product pages (both formats), Complete Collection (both formats), Teacher funnel, Parent funnel, blog → product flow, cart/checkout mechanics, mobile viewport, and analytics script presence. No code changes were made. No real purchase was placed.

## Funnels tested

| Funnel | Entry → CTA → Product | Result |
|---|---|---|
| Homepage → Product | Homepage hero/book cards → product pages | Working. All 3 "Explore/Shop" links resolve to the correct paperback product pages. |
| Navigation → Product | Header nav (Home/Blog/About/Books/Contact/Expedition Guides) | All 6 links resolve correctly, single clear primary CTA ("Get the Complete Collection"). |
| Search → Product | WordPress `?s=` search | Functional and returns relevant posts + products, but **no discoverable search UI exists in the header, footer, or homepage** — only reachable via the `/teachers/` Expedition Guides hub, which has its own `.search-form`. See Finding P2-1. |
| Individual Book Funnel (Mariana, Everest, Amazon) | Product page → Add to Cart → Cart → Checkout | Functional for all 3 paperbacks. **All 3 hardcover editions are also live/purchasable — see Finding P0-1.** |
| Complete Collection / Bundle | `/complete-collection/` → format toggle → Add to Cart | Functional. Pricing verified correct ($31.99 paperback / $48.99 hardcover, matches $35.97/$53.97 individual-buy math). [PARENT_COUPON_CODE_SUPERSEDED] messaging correctly scoped to "Complete Collections only." Hardcover option compounds Finding P0-1. |
| Teacher funnel | `/teachers/` → `bhp_mariana_popup` (5s/30% scroll trigger) + always-visible `#teacher-resources-signup-form` | Correct isolation confirmed: exactly 1 popup element on the page, `source: teacher_popup`, storage prefix `bhp_mariana_popup`, thank-you path `mariana-guide-thank-you` — matches `.claude/rules/funnels.md` exactly. No parent-popup element present on this page. |
| Parent funnel | Sitewide (except `/teachers/`) → `bhp_parent_popup` (8s/40% scroll desktop, 10s/50% mobile) | Correct isolation confirmed: `source: parent_popup`, storage prefix `bhp_parent_popup`, thank-you path `adventure-kit-thank-you` — matches funnels.md exactly. Dismissal/localStorage state does not leak into teacher-popup keys or vice versa. |
| Blog → Product flow | 5 sampled posts (64, 50, 88, 56, 66) | Topic-hub link present on all 5 (automatic registry grid). Book-discovery link **broken on post 64** — see Finding P1-1. Post 66 (Batch 2, already remediated) confirmed correct. |

## Cart / checkout mechanics (no real payment)

- Add to Cart (Mariana Trench Paperback) → Cart page: correct item, price ($11.99), quantity stepper, subtotal, shipping ($1.99 — pre-existing intentional single-paperback tier, already documented, not a new finding), Idaho sales tax ($0.72), total ($14.70) all computed correctly. Test item removed after verification per standing rule.
- Checkout page loads cleanly: guest checkout available, full country/address fields, 9 Stripe Elements iframes loaded (payment form renders), "Place order" button present. Zero console errors on cart or checkout.
- **No visible coupon/promo-code field anywhere on the Checkout page's rendered text** — only the Cart page shows an "Add coupons" link. This matches the already-documented `KNOWN_ISSUES.md` entry (WooCommerce Blocks' native coupon toggle, low-contrast text link) — confirmed still open, not a new finding, but confirmed the checkout page itself has no coupon affordance at all, only cart does.
- **What was not tested:** a real end-to-end purchase (payment submission, Bookvault fulfillment trigger, Stripe recording, order-confirmation email, Mailchimp tag application, WooCommerce order-status transition). The sprint brief allows minimal-value production test purchases with cancel/refund; this was deliberately not done, consistent with this project's standing "no real payment" testing policy (established in prior sessions and reflected in `docs/PROJECT_STATE.md`/session history) and the global safety rule that payment actions need current-turn explicit approval. **A live test order requires Andrew's explicit go-ahead to place.**

## Findings, ranked

### P0 — Revenue-blocking / customer-harm risk
**P0-1: All 3 hardcover editions are purchasable on production, contradicting the documented out-of-stock decision.** See `docs/KNOWN_ISSUES.md` for full detail. Andrew decision needed.

### P1 — Hurts conversion
**P1-1: Malformed doubled-protocol Amazon links break the book-discovery path on 4 blog posts (38, 64, 88, 90).** See `docs/KNOWN_ISSUES.md` for full detail. Post 64 has zero working purchase path.

**P1-2: Fulfillment-partner statement is inconsistent across the 3 paperback product-page descriptions.** Mariana Trench says "Printed and shipped by Bookvault" (correct, current partner). Mount Everest says "Printed and shipped by Lulu" (stale — Lulu is a prior partner per project history). The Amazon has no fulfillment-partner statement at all. Live-verified via direct page-text extraction on all 3 pages, 2026-07-13. This was previously flagged generically in `CONTENT/BLOG_STATUS.md`'s "Known content-accuracy flags" as a two-way Lulu/Bookvault mismatch against the Collection page's "Printed and shipped in the USA" wording; this sprint confirms it's actually a three-way inconsistency at the individual-product level and elevates it from an editorial flag to a verified, ranked funnel-QA finding. Not fixed this sprint (editorial copy decision, not this sprint's scope).

### P2 — Usability
**P2-1: No discoverable on-site search entry point in global navigation, header, or footer.** The `?s=` WordPress search endpoint itself works correctly (verified: returns relevant posts and products for "mariana"), but there is no search icon/box anywhere in the site-wide header or footer — only the `/teachers/` Expedition Guides hub page has its own search form. A visitor who wants to search must already know the hub page exists.

**P2-2: "Used in 40 classrooms" trust-signal claim has no on-hand verification.** Appears on the homepage hero strip and the Complete Collection page. Already flagged in `CONTENT/BLOG_STATUS.md`'s known content-accuracy flags; independently re-observed live on both pages this session. Not a technical defect — an editorial/evidence question for Andrew.

**P2-3: Checkout page has no coupon-entry affordance at all** (only Cart does) — extends the existing `KNOWN_ISSUES.md` coupon-visibility entry with the more precise observation that Checkout shows nothing coupon-related in its rendered text, not just a low-contrast link.

### P3 — Cosmetic / hygiene
**P3-1: Two orphaned draft WooCommerce products exist in the catalog** — ID 338 ("Adventures of Charlotte and Henry: The Mariana Trench," no format suffix, `post_status=draft`) and ID 12 (duplicate Mariana Trench Paperback draft). Not customer-facing (draft status), but a catalog-hygiene risk: could be accidentally published or confuse future admin work. Verified via `wp post list --post_type=product`.

**P3-2: `DECISIONS.md`'s documented pricing ("paperback $12.99, hardcover $18.99") no longer matches live pricing** on every storefront surface checked (homepage, all 3 product pages, Complete Collection page all agree at $11.99 paperback / $17.99 hardcover, internally consistent with each other). This is a stale-documentation issue, not a live functional bug — all live surfaces agree with each other, just not with the older doc. Recommend updating `DECISIONS.md`'s price figures in a future documentation pass.

## What was cited from existing documentation, not independently re-verified live this session
- **Mailchimp automations** (Adventure Kit, Teacher classroom-guide, [PARENT_COUPON_CODE_SUPERSEDED] coupon email): last independently verified 2026-07-06 per `AI_CONTEXT_INDEX.md`; not re-tested this session (would require a real signup submission).
- **Bookvault store connection / order-routing health**: prior sessions extensively verified this (see `ENGINEERING/WOOCOMMERCE_STATUS.md`); not re-probed via API this session beyond the stock-status finding above (a quick WP-CLI option/class-name guess did not resolve, and was not pursued further to avoid an unreliable read).
- **GTM/GA4 publish status**: live-verified this session via DOM script inspection — confirmed still unpublished by design (only script present is Google Ads' `AW-18315643536` conversion pixel from the Google Listings & Ads plugin, matching `PROJECT_STATE.md`'s documented state exactly). No GTM container or GA4 measurement-ID script found.
- **Meta Pixel / Pinterest tag**: confirmed absent via DOM script inspection this session (0 scripts matching either), consistent with no prior documentation claiming either is active.
- **Search Console**: not checked this session (tracked in the separate `brave-hearts-seo-engine` repo).

## Recommended remediation order
1. **P0-1** (hardcover stock status) — Andrew decision first (restore out-of-stock, or approve hardcover sales), then a one-line WP-CLI/postmeta fix once decided.
2. **P1-1** (malformed Amazon links) — small, isolated, low-risk mechanical fix (same deterministic-replacement pattern as Batch 2), can proceed once Andrew confirms scope.
3. **P1-2** (fulfillment-partner copy) — needs Andrew/CSO to confirm the correct current-partner wording, then a 3-post copy pass.
4. **P2-1 / P2-3** (search discoverability, checkout coupon affordance) — small UI additions, not urgent, no revenue risk.
5. **P2-2** (40-classrooms claim) — Andrew to confirm or retire the claim.
6. **P3-1 / P3-2** (orphaned drafts, stale pricing doc) — cheap cleanup, no urgency.

## Not in scope for this sprint (per explicit instruction)
No code was changed. No production content was edited. This document and the corresponding `KNOWN_ISSUES.md` entries are the complete output of this sprint.
