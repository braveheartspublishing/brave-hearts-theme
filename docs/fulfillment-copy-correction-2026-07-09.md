# Fulfillment Copy Correction — Lulu → Bookvault (2026-07-09)

## Why

The real print-on-demand fulfillment partner is **Bookvault**, not Lulu.
Several visitor-facing locations still described the older Lulu
relationship. This document records the full audit and every change —
all changes were made directly to WordPress database content on
**staging only** (`wp_update_post()`), since none of this text lives in
theme/plugin template files (confirmed by a repo-wide `grep -i lulu`
across all PHP/JS/template files — zero matches).

## Audit method

`wp search-replace 'Lulu' '<marker>' --dry-run --report-changed-only --all-tables-with-prefix`
against the staging database (read-only dry run, no write) found every
occurrence, then each real post's content was inspected individually
before any change was made.

## Locations found

| # | Location | Post ID | Status | Action |
|---|---|---|---|---|
| 1 | Mount Everest (Paperback) product description | 15 | published, live | **Corrected** |
| 2 | Privacy Policy — "Order Fulfillment Information" paragraph | 3 | published, live | **Corrected** |
| 3 | Privacy Policy — "How We Use Your Information" list item | 3 | published, live | **Corrected** |
| 4 | Privacy Policy — "Service Providers and Disclosure" list item | 3 | published, live | **Corrected** |
| 5 | Terms and Conditions — "Print-on-Demand Fulfillment" section | 324 | published, live | **Corrected** |
| 6 | Terms and Conditions — "Order Cancellations" section | 324 | published, live | **Corrected** |
| 7 | Terms and Conditions — "Refunds" section | 324 | published, live | **Corrected** |
| 8 | "The Mariana Trench (Paperback)" product, slug ends `-legacy-lulu` | 12 | **draft**, not visitor-facing | **Not changed** — historically accurate (this product genuinely was Lulu-fulfilled before the migration to Bookvault); it is a draft, never served to visitors, and its own slug already marks it as a retired legacy record |
| 9 | `woocommerce_api_keys.description` (an internal REST API key's admin-only label) | n/a | not visitor-facing | **Not changed** — internal admin metadata, not customer-facing content, and editing an API key's description carries a small risk of confusing which credential it refers to for no visitor benefit |

Every other area explicitly named in the audit request — cart messaging,
checkout messaging, FAQ content, shipping content, footer content,
structured data (Rank Math schema), reusable template partials, and all
hard-coded PHP/JS strings — was confirmed **clean** (zero "Lulu"
occurrences) via a full repository grep. No template file required a
code change.

## Exact wording changes

**Product #15 (Mount Everest Paperback):**
- Before: *"Paperback. Illustrated. Printed and shipped by Lulu."*
- After: *"Paperback. Illustrated. Printed and fulfilled by our publishing partner, Bookvault."*

**Privacy Policy (#3):**
- Before: *"Physical books are printed and shipped by Lulu Direct, our print-on-demand fulfillment partner. To fulfill your order, we share your name, shipping address, and phone number with Lulu. Lulu's handling of this information is governed by their own privacy policy."*
- After: *"Physical books are printed and fulfilled by Bookvault, our print-on-demand fulfillment partner. To fulfill your order, we share your name, shipping address, and phone number with Bookvault. Bookvault's handling of this information is governed by their own privacy policy."*
- Before: *"Processing and fulfilling orders (including sending order and shipping data to Lulu Direct)"*
- After: *"Processing and fulfilling orders (including sending order and shipping data to Bookvault)"*
- Before: *"**Lulu Direct** — print-on-demand order fulfillment and shipping"*
- After: *"**Bookvault** — print-on-demand order fulfillment and shipping"*

**Terms and Conditions (#324):**
- Before: *"Direct website orders are printed and fulfilled by our print-on-demand partner, Lulu Direct."*
- After: *"Direct website orders are printed and fulfilled by our print-on-demand partner, Bookvault."*
- Before: *"Brave Hearts Publishing uses a 24-hour production delay for direct website orders fulfilled through Lulu Direct."*
- After: *"Brave Hearts Publishing uses a 24-hour production delay for direct website orders fulfilled through Bookvault."*
- Before: *"A refund does not necessarily stop production at Lulu Direct if production has already begun."*
- After: *"A refund does not necessarily stop production at Bookvault if production has already begun."*

**⚠️ Flagged, not verified:** the "24-hour production delay" figure in
the Order Cancellations section was carried over unchanged (only the
vendor name was corrected). This session has no independent record of
Bookvault's actual production-start/cancellation window — **Andrew
should confirm whether 24 hours is still the correct figure for
Bookvault**, since it was originally written for Lulu's SLA and may not
match Bookvault's exactly.

## Not claimed / not added

Per the explicit instructions, no copy was added that claims a
guaranteed delivery date, domestic-only printing, an exact shipping
time not already in the existing Shipping Policy, that Bookvault is the
"seller of record," that all orders ship from one country, or any
environmental/quality claim. The replacement copy is deliberately
identical in structure to what existed before, with only the vendor
name and verb ("shipped" → "fulfilled," matching the approved copy
direction) changed.

## "Used in 40 classrooms" claim — separate finding, NOT changed

Found in two places, both as a short trust badge:
- `front-page.php:142` (homepage trust-proof row)
- `plugins/brave-hearts-bundle-pricing/includes/bundle-landing-page.php:290` (Complete Collection landing page trust row)

The second location has its own explanatory code comment (lines
276–284 of that file), written in an earlier session:

> *"Used in 40 classrooms" is carried over verbatim from Andrew's own
> approved landing-page mockup (Adventure Bundle4.pdf); it is not
> independently verified against a stored business record in this
> repository — see the Phase 2 report's placeholder note rather than
> treating that absence as proof it's false.*

**Classification: partially supported.** The number originated from
Andrew himself (approved in a real design mockup, not invented by any
prior session), but no independently stored business record (e.g. an
actual classroom-signup count) exists in this repository to confirm the
figure is still accurate today. This is not the same as "unsupported"
(fabricated) or "unable to verify" (no evidence either way) — there is
a specific, named source, it just isn't a live/current data record.

**No copy was changed.** Per explicit instruction not to silently
rewrite an inadequately-supported numeric claim, this is reported for
Andrew's decision rather than acted on.

**Recommended replacement, if Andrew cannot confirm "40" is still
accurate:** *"Shared with classrooms across the country."* — true
regardless of exact count, and avoids a specific number that could
become stale or unverifiable over time.

## Verification performed

- Live page load + accessibility-tree confirmation of the corrected
  text on all 3 live pages (Mount Everest Paperback product page,
  Privacy Policy, Terms and Conditions) — staging, real browser, no
  console errors.
- `wp eval 'echo "ok";'` fatal-error check after the change — passed.
- Full regression suite (9 theme + 13 plugin test files, 22 total) —
  all passed, unaffected by this content-only change.
- Confirmed zero remaining "Lulu" references in any current
  (non-revision, non-draft) post content via `wp db query`.
- WordPress's own revision system automatically preserved the
  pre-edit content of posts 3, 324, and 15 as post revisions — this is
  the rollback path if any change here needs to be reverted (Admin →
  edit the post → Revisions).
