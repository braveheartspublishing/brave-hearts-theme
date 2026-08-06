# Fable Audit Remediation — Canonical Release Tracker

**Status:** **Phase 12 QA complete — awaiting Andrew's Phase 13 production approval.** Staging only
(theme `brave-hearts-theme-deploy-explorer-expedition-guides`).
Current staging versions: **theme 1.19.85**, **bundle plugin 1.8.6**. Production untouched at 1.19.58.
**2nd Fable audit (BH-01…BH-08) pre-production pass complete on staging (2026-07-19)** — see CHANGELOG
"2nd Fable audit" entry. BH-01 externally pending Andrew's clean-device check (no payment code changed);
BH-06 needs a real-device spot-check; all others verified. New per-env prod steps added to Phase 14 below.
All 36 findings dispositioned; #15/#24/#25/#26 closed per Andrew's 2026-07-19 business decisions.
The site-wide "Printed Just for You" notice (`inc/class-bhp-printed-for-you.php`) was softened per
Andrew's 2026-07-19 direction — "Most orders arrive within 1–2 weeks" → "Each book is printed
especially for your order. Production and delivery times can vary, so please order early for
birthdays, holidays, and other special occasions." No open decisions remain.
**Branch:** `feature/production-integration-1.17.1`. Nothing committed/pushed/deployed to prod yet.

This document is the **authoritative source of truth** for the Fable audit remediation release
(36 findings / 15 phases). It was reconstructed from the original brief (session transcript,
2026-07-18) after context compaction, so scope survives future compaction. **Update it as work
progresses.** Do not reconstruct scope from memory when this file exists.

## Governing constraints (from the brief — do not violate)
- Improve toward a genuine 90+ experience (Brand/UX/Mobile/Conversion/Trust/Content/Accessibility)
  **without** redesigning from scratch. No new design system, fonts, or palette; preserve the
  expedition-journal aesthetic.
- Preserve: brand identity, typography, color system, homepage audience-routing + quiz model,
  audience landing-page architecture, `/teachers/` dual-purpose hub, Complete Collection
  positioning, existing Mailchimp journeys, current product/bundle mechanics.
- **Checkout is NOT a site defect.** The Stripe `memoize` failure was the Claude-in-Chrome
  extension injecting a duplicate async lodash. Do **not** add a lodash shim, change WooCommerce
  Stripe/Blocks/payment-registration code, or deploy defensive code. Never use a Claude-in-Chrome
  browser as source of truth for Stripe. (Recorded in `docs/KNOWN_ISSUES.md`.) A clean-device
  Stripe check + a real paid order are Andrew's manual actions in Phase 15.
- Staging-first. **Stop at the Phase 13 review gate** before any production deploy. Stop and report
  on anything touching fulfillment, payments, legal promises, pricing, or historical order integrity.
- Prefer the smallest reusable improvement over page-specific patches. Don't chase the audit score.

## Classification legend
`NOW` implement now · `CAUTIOUS` implement cautiously · `ANDREW` requires Andrew's manual WP action ·
`TRAFFIC` requires real traffic data · `NOCHANGE` no change recommended.
Status: ☐ not started · ◐ in progress · ✅ done+verified on staging · ⏸ parked/gated.

---

## Phase 0 — Checkpoint & safety ✅
Baseline verified: branch/commit confirmed, tree clean at start, prod vs staging versions checked,
Complete Collection paperback+hardcover catalog + cart/bundle behavior confirmed working. Checkout
conclusion recorded in `docs/KNOWN_ISSUES.md`.

## Phase 1 — Objective trust & accuracy fixes
| # | Finding | Class | Status | Where / evidence |
|---|---|---|---|---|
| 1 | Refund & Returns Policy page (footer link went to `/`) | NOW / ANDREW-content | ✅ staging | Page 10 populated from Terms (damaged/defective/incorrect/missing, reporting window, POD/buyer-remorse limits, contact); published on staging. **Prod replication = manual page create/publish (record steps).** |
| 2 | Blog author showed raw email | NOW | ✅ staging | Staging DB: user 1 display_name→"Andrew Signore", nicename→`andrew-signore`. Code: `bhp_redirect_legacy_author_slug()` 301s old slug → `/author/andrew-signore/` (`inc/audit-remediation.php`). **Prod replication = WP-CLI user update.** |
| 3 | Shipping policy said flat $3.99; real = tiered | NOW | ✅ staging | Approved range wording applied to post 350 on staging: "…rates typically range from approximately $1.99 to $4.99…contiguous United States." **Prod replication = str_replace on prod post 350.** Note: real rates are tiered $1.99–$4.99, not a flat $3.99/$4.99. |
| 4 | Checkout offered worldwide countries | NOW+CAUTIOUS | ◐ | Staging: `woocommerce_allowed_countries=specific`, `woocommerce_specific_allowed_countries=["US"]`. **Still TODO:** Blocks-aware early contiguous-US disclosure + friendly AK/HI/PR message in theme code; verify unsupported-destination messaging. **Prod replication = 2 option updates.** |

## Phase 2 — High-intent commerce flow ✅ (verified 2026-07-18)
| # | Finding | Class | Status | Where / evidence |
|---|---|---|---|---|
| 5 | Complete Collection upsell on product pages | NOW | ✅ | `bhp_product_collection_upsell()` (`inc/audit-remediation.php`, hook `woocommerce_after_single_product_summary` pri 15). Format-aware; PB $35.97/$31.99/save $3.98, HC $53.97/$48.99/save $4.98; CTA→/complete-collection/; `data-bhp-event="collection_upsell_click"`. Render-verified both formats. |
| 6 | Book 1 paperback = variable w/ 1 meaningless option | CAUTIOUS (UX-only per Andrew) | ✅ | Andrew chose Option 1: keep variable product, hide selector, auto-select sole variation. `bhp_single_variation_ux()`. Verified: selector hidden, variation 334 auto-selected, **real add-to-cart works** + drawer opens. **No product-type migration, no fulfillment change.** |
| 7 | Shop not in series reading order | NOW | ◐ staging | `menu_order` set on staging (333→1,14→2,15→3,17→4,18→5,20→6) + catalog orderby=menu_order. **Re-verify rendered shop grid order (desktop+mobile) still pending.** **Prod replication = menu_order values.** |
| 8 | Empty "Reviews (0)" tab | NOW | ✅ | `bhp_hide_empty_reviews_tab()` unsets reviews tab when `get_review_count()===0`. Verified: no Reviews tab; Kirkus/Amazon proof untouched. |
| 9 | "SKU:" label is really an ISBN | NOW | ✅ | `bhp_relabel_sku_as_isbn()` gettext filter on product pages. Verified renders `ISBN:`; underlying SKU/`_global_unique_id` untouched. |

**Phase 2 performance sub-task (Message 8):** collection add-to-cart optimized from 3 sequential
`/cart/add-item` (~5.3s, 6 reqs) → single Store API `/batch` (~1.6–2.1s, 5 reqs), sequential path
retained as fallback. **Gotcha:** `/batch` validates nonce per sub-request; fixed by stamping nonce
into each sub-request header (`addItemsBatch` in `bundle-drawer.js`). Added button feedback
("Adding to cart…", disabled, aria-busy) + duplicate-click prevention (`initBundleFormFeedback`),
verified live (dup submit → 0 extra requests). Documented in `docs/CHANGELOG.md`.

## Phase 3 — Homepage flow & aesthetic refinement ◐ (5 done, #15 parked for decision)
| # | Finding | Class | Status | Notes |
|---|---|---|---|---|
| 10 | Mobile hero CTA reachability (320/375/390/430) | NOW | ✅ | Appended `≤480px` hero block (`style.css`): trimmed hero padding-top (128→92px), eyebrow/lead/actions margins, title font `clamp(2.75rem,13vw,3.35rem)`, lead 20→~16px. Primary Collection CTA now fully in first screen — top ≈685–694 / bottom ≈754–763 (<812) at 320/375/390, eyebrow ≈109 clear of the 93px sticky header. Desktop untouched (media-scoped). Landscape: hero is short at reduced landscape heights; CTA remains reachable within a short scroll (spot-check pending in Phase 12 full matrix). |
| 11 | Mobile tap targets ~44px | NOW | ✅ | Appended `≤600px` block: `min-height:44px` on gateway links + footer nav/learn/contact/audience-cluster links (type size unchanged). Verified all named groups now min 44px. Inline prose links + dot-separated legal row (`.footer-bottom__link`) intentionally kept inline (WCAG 2.5.8 exception). Learning Hub cards already full-card tap via #12 `::after`. |
| 12 | Learning Hub cards all → generic blog; hidden CTA text | NOW | ✅ | **Live WP-CLI confirmed** none of animals/science/geography/conservation/explorers/activities exist as page or category (all six fell back to `/blog/`); `/teachers/` has no per-topic anchors (builder hex ids). Fix: `bhp_get_learning_category_url()` fallback changed `/blog/` → per-topic **post search** `/?post_type=post&s=<topic>` (each topic has 3–35 real posts); page→category resolution kept ahead so future taxonomy auto-upgrades. Scoped to `post_type=post` (bare `?s=` also surfaced pages like Privacy Policy). CSS: `.feature-card--field-note .feature-card__link` was `color:transparent` stretched full-card → restored visible forest-green "Explore … →" + accessible `::after` full-card overlay. Verified 1280/375/320. **No prod data step (theme code only).** |
| 13 | Homepage length / bottom-third repeated asks | CAUTIOUS | ◐ | **Padding half done:** `≤768px` section padding on tall sections (philosophy/origin/destinations/learning-hub/together/trust/newsletter + books-path bottom) 88→56px; home height 17,454→16,974px @375. No content removed. **Copy-consolidation half deferred** — reducing the repeated commercial asks is a merchandising decision needing traffic evidence (finding's "evidence required before deletion"); park for Andrew/Phase 11 traffic plan. |
| 14 | Newsletter placeholder truncates | NOW | ✅ | Shortened homepage `email_placeholder` default (`front-page.php`) "Your email - no noise, just wonder" → "Your email address". Real `<label for>` already present; no DB override. Verified no truncation 320–430px. **Theme code only.** |
| 15 | Product-card consistency | NOW | ✅ (no change, per Andrew) | Crops already consistent (`bhp-book-card` everywhere). Per Andrew 2026-07-19: **keep "Buy Direct"** (intentionally distinguishes direct-from-publisher purchases from Amazon links) and **do not** add "from $X" pricing to multi-format `/books/` cards — pricing stays on the applicable product/format pages. No code change. |

## Phase 4 — Mobile page-length & resource-hub flow ✅
| # | Finding | Class | Status | Notes |
|---|---|---|---|---|
| 16 | `/teachers/` renders nearly every article inline | NOW | ✅ | **Progressive enhancement** (`page-teachers.php` + inline JS + `style.css`): every guide grid with >6 cards gets `guide-article-grid--collapsible`; JS collapses to first 6 and injects an accessible toggle (`aria-expanded`/`aria-controls`, "View all N field notes" ↔ "Show fewer"). All 72 cards stay in the HTML (crawlable/no-JS safe — `.is-collapsed` is only added by JS). Verified: page 55,046→31,139px (67.8→38.3 screens), toggle 6↔24 works, aria updates, search + topic-anchor nav + compact toolkit intact, no console errors, `/teachers/` NOT redirected. |
| 17 | Amazon lacks destination coverage in hub | CAUTIOUS | ✅ | Audit: prod has a real Amazon article (`amazon-rainforest-facts-for-kids`, post 366, published) but it was absent from the guide registry (missing from `$science` + `$destinations`) and there was no Amazon destination hub — parallel to how Mount Everest has a single-article hub. Fix (`functions.php` registry + `bhp_get_guide_hubs()`; `page-teachers.php` destination + collection loops; `style.css` `--amazon-rainforest` canopy bg). **Presence-guarded**: destination card + section only render when the hub has ≥1 published post — never a "Coming Soon". Verified on staging both ways: negative (draft only → hub hidden, Mariana/Everest intact) and positive (published → Amazon destination card + "The Amazon Rainforest" section with 1 accurate article, canopy bg HTTP 200, no console errors). **Prod: NO content step — post 366 is already published, so the theme deploy alone lights up the hub.** |
| 18 | Kindle reference though not on-site | NOW | ✅ | On-site every book card lists only Paperback/Hardcover (no Kindle product/link exists); the `/books/` `#book-formats` heading "Kindle, Paperback, and Hardcover" (`page-books.php`) falsely implied an on-site Kindle format. Changed to "Paperback and Hardcover". Verified 0 Kindle mentions on `/books/`. (Optional future: a genuine external Amazon-Kindle link would be an additive merchandising decision requiring affiliate disclosure — not done.) `functions.php` Kindle-detection logic left untouched (harmless; only activates if a product actually carries that format). **Theme code only.** |

## Phase 5 — Quiz & modal usability ✅
| # | Finding | Class | Status | Notes |
|---|---|---|---|---|
| 19 | Quiz modal no Escape close | NOW | ✅ **already fixed** | **Audit finding was stale** — the 2026-07-17 `assets/js/quiz-modal.js` rewrite already implements Escape close + focus return to launcher, focus entry (close btn/first focusable), Tab focus-trap, backdrop + close-button close, quiz-state preservation (only Restart clears), and session suppression. Markup (`quiz-entry-cta.php`) has `role="dialog"` + `aria-modal="true"` + `aria-labelledby`. **Verified LIVE on staging** (product page): open moves focus into dialog; Escape closes + returns focus to launcher + `aria-expanded=false`. No code change needed. |
| 20 | Commerce-page auto-trigger | CAUTIOUS | ✅ | Inspected existing exclusions first: `bhp_should_show_any_popup()` already fully blocks **every** popup on cart/checkout/account/order-received (+ privacy/terms/audience-signup templates). Gap: timer/scroll **auto-open** was still eligible on shop/product/Collection browsing pages. Fix (`functions.php` `bhp_should_autoopen_quiz()`): also return false on `is_shop()`/`is_product_taxonomy()`/`is_product()`/`complete-collection` — this gates **auto-open only**; the manual launcher still renders (via `bhp_should_show_quiz_cta()`), and auto-open still works on canonical/blog pages. Session suppression (`bhp_quiz_auto_shown`) unchanged. **Verified**: product/shop/Collection → `data-bhp-quiz-autoopen="false"` + launcher present; About (canonical) → `="true"`. |

## Phase 6 — Lead funnel & post-signup flow ✅ (all 6 done; #24/#25/#26 closed per Andrew 2026-07-19)
| # | Finding | Class | Status | Notes |
|---|---|---|---|---|
| 21 | Parent lead-magnet naming inconsistent | NOW | ✅ **already consistent** | Verified: canonical "Reluctant Reader Adventure Kit" on the landing template title and thank-you H1; consistent "Free Adventure Kit" CTA shorthand across landing + parent popup. No genuinely divergent name found (only long/short forms of the same name, which is acceptable). Landing page is Andrew's supplied custom design — not rewritten. Email1 subject/body/download live in Mailchimp, not the repo (out of code scope). No change needed. |
| 22 | Parent thank-you commercial hierarchy | NOW | ✅ | `page-adventure-kit-thank-you.php`: added a compact "Continue the adventure" **Complete Collection** module as the primary next step — placed after the inbox/download instructions (which stay first) and **before** the individual-book cards, which are now framed as the secondary path ("Prefer to start with a single story?"). CTA → `/complete-collection/` with `data-bhp-event="collection_upsell_click"` + `data-bhp-source="parent_thank_you"`. No prices hardcoded (collection page is canonical). Verified order + render. |
| 23 | Welcome-email timing copy | NOW | ✅ | Thank-you lead copy updated to "Please allow up to 15 minutes for it to arrive, and check your promotions or spam folder if you don't see it." MC journey timing untouched. Verified. |
| 24 | Educator procurement clarity | ANDREW/NOW | ✅ | Per Andrew: added a bulk-inquiry FAQ (`page-audience-educators.php`) — schools/teachers/librarians/homeschool orgs may contact us about classroom, library, and larger-volume purchases; handled individually via direct inquiry. **No** PO / institutional-invoicing / formal-school-terms / W-9 claims. Verified rendered. |
| 25 | Gift-buyer flow | CAUTIOUS/TRAFFIC | ✅ | Per Andrew: gift-wrap "not currently" already present; removed two "arrive within 1–2 weeks" delivery-window promises (`page-audience-gift-buyers.php` FAQ + Collection copy) → print-to-order + order-early language; no specific production/delivery window promised. Verified. Also softened the site-wide "Printed Just for You" notice (`inc/class-bhp-printed-for-you.php`) per Andrew — removed "Most orders arrive within 1–2 weeks" in favor of print-to-order + order-early advisory copy. Verified rendered on a product page. |
| 26 | Organization contact path | NOW | ✅ | Per Andrew: contact section (`page-audience-organizations.php`) now enumerates topics — literacy programs, classroom/community sponsorships, reading initiatives, event/program partnerships, bulk purchases — with "every request is reviewed individually." No fixed discounts / packages / guaranteed response times. Verified. |

## Phase 7 — Contact & About ✅
| # | Finding | Class | Status | Notes |
|---|---|---|---|---|
| 27 | Contact relies on `mailto:` | NOW | ✅ | The provider-neutral contact form existed but **no provider was configured on either env**, so the page fell back to `mailto:`. Built a native handler (`inc/audit-remediation.php`): `bhp_contact_form_action` filter defaults to `admin-post.php` (external provider still wins if ever set); `bhp_handle_contact_submit` verifies a nonce + honeypot, does server-side validation, sends via `wp_mail` to a **server-controlled** recipient (`admin_email`, never user input), and redirects to `?bhp_contact=success|invalid|error#contact-form`. Template (`contact-form.php`) renders the nonce/honeypot/action fields + an aria-live status message + a production-gated `contact_submit`/`contact_error` dataLayer event; the "Prefer Email?" section provides the visible email alt; privacy note ("no student info") retained. **Verified on staging via the in-page nonce**: honeypot→success-without-send, missing-required→invalid, bad-nonce→error; success/invalid/error messages render. `should_render_analytics=false` on staging (event fires on prod). **Andrew: do one real submission on prod post-deploy to confirm `wp_mail` delivery (SPF/DKIM).** |
| 28 | About page credibility | NOW | ✅ | The founder's **real** ICU/travel-nurse background already lived in the About page's post_content (Andrew's own words) but the template rendered generic hardcoded defaults instead, so it never appeared. Added `founder_text_3` (editor-overridable via `bhp_about_founder_text_3`) drawn faithfully from his own words — "ICU nurse … COVID and neuro intensive care … travel nurse" — framed around courage/care/steadiness/respect-for-science, **not** as a teaching credential, with Charlotte & Henry kept central. Verified rendered; no teacher/credential overclaim; no overflow. **Theme code only.** |

## Phase 8 — Blog flow & content presentation ✅ (#32 = appendix, needs traffic data)
| # | Finding | Class | Status | Notes |
|---|---|---|---|---|
| 29 | Standardize end-of-post conversion module | NOW | ✅ **already implemented** | The intent-aware module the finding asks for already exists: `BHP_CTA_Engine` (registry keyed by `destination_type`/`presentation_style`/`audiences`/`intents`/`funnel_stages`, `render_for_post()`) plus the curated `guide-continuation` block for guide-registry posts (via `BHP_CTA_Collision_Detector`). **Verified** different intents get different, topic-relevant CTAs — reading post → `/teachers/#reading-growing` ("Follow This Trail Further → Reading & Growing"), teacher post → `/teachers/#educator-resources` — **not** the same aggressive Collection CTA everywhere. No change needed. |
| 30 | Amazon post book link → category archive | NOW | ✅ | Amazon post (`amazon-rainforest-facts-for-kids`, staging 546 / prod 366) in-body book link `…/product-category/the-amazon/` (bare category archive) → exact product `…/product/adventures-of-charlotte-and-henry-the-amazon-paperback/`. Verified only that link changed; the general `/books` link left intact. **Per-env content change (prod post 366 needs the same replace).** |
| 31 | Typo/title cleanup (double `??`) | NOW | ✅ | Post 82 title "…What Level Should My Child Be At**??**" → "…At**?**". Slug preserved (`reading-level-by-grade-chart`); no Rank Math title override existed. Verified rendered. **Per-env content change (prod post 82 needs the same title fix).** |
| 32 | Content overlap (Dog Man/Lexile/reluctant-reader) | TRAFFIC (appendix) | ⏸ **appendix — needs GSC data** | No destructive edits (preserve URLs). Candidate overlap clusters inventoried from the guide registry: **Dog Man** (`dog-man-to-magic-tree-house-reading-roadmap`, `what-to-read-after-dog-man`); **Lexile** (`what-is-a-lexile-score`, `my-child-got-a-lexile-score-now-what`, `finding-right-books-with-lexile-score`, `reading-level-by-grade-chart`); **bridge/reluctant** (`bridge-books-for-kids`, `top-bridge-books-for-kids`, `best-bridge-books-for-kids`, `bridge-books-for-early-readers`, `bridge-books-for-struggling-readers`, `what-are-bridge-books-guide-for-parents-and-teachers`, `my-child-hates-reading-what-to-do`). Final consolidate/differentiate calls (canonical + internal-link steering, keep the strongest per cluster) require GSC query/position/cannibalization data — folded into the **Phase 11** traffic plan for Andrew. Strategy appendix, not edits. |

## Phase 9 — Image & visual quality ◐ (#33/#35 verified, #34 already-safe, #36 substantially covered)
| # | Finding | Class | Status | Notes |
|---|---|---|---|---|
| 33 | Product image resolution (soft on retina) | NOW/CAUTIOUS | ✅ **verified adequate** | Cards use `wp_get_attachment_image('bhp-book-card' 480×640 / 'bhp-card-landscape' 640×420)`, which emits WP responsive `srcset` + `sizes`. Live `/books/` cover: `srcset` candidates **198/600/675/768/1012/1318w**, `sizes="(max-width:422px) 100vw, 422px"`; at DPR 2 for a 294px-rendered cover the browser loaded the **768w** candidate (≥ the ~588px retina target). Retina candidates present, no originals forced, crops unchanged. The `sizes` value (422) slightly over-states the real 294px render, which favors sharpness. No fix needed. |
| 34 | Testimonial repetition (repeated Payton quote) | NOW | ✅ **already safe (no fabrication)** | Inventory: **7** approved Amazon reviews (`inc/amazon-reviews.php`) — Mariana ×4 (incl. "Payton" = `amz-mariana-04`), Everest ×2, **Amazon ×0** (correctly empty until a real review exists). `amazon-review-showcase.php` renders **per-book, in order, approved-only** (`array_slice`), never fabricating. The Payton quote on the Complete Collection page is **Andrew's explicit 2026-07-05 direction** (documented in-code), not accidental repetition; the homepage uses a different (Kirkus) quote. Broader per-audience variety is bounded by genuine review supply — **revisit when more real reviews exist**; no paraphrase/fabrication added. |
| 35 | Cart/checkout header height ("Field Journal") | NOW | ✅ | Compact commerce header on `.woocommerce-cart/.woocommerce-checkout/.woocommerce-order-received` (`style.css`): interior-hero **199→118px**, decorative "FIELD NOTE · BHP" coordinate hidden, brand "Field Journal" eyebrow kept, cart content moved up (372→292 @375). Scoped to WC body classes; **no checkout/payment markup touched**. Cart verified live; checkout header inferred from identical CSS/structure → **confirm with a populated cart in Phase 12**. |
| 36 | General aesthetic consistency pass | NOW | ◐ **substantially covered** | The load-bearing rhythm/spacing items were addressed by earlier findings: mobile hero scale/spacing (#10), 44px tap targets (#11), mobile section padding 88→56 (#13), visible field-note CTA affordance (#12), compact commerce header (#35). Every touched page was verified for **no 320–430px horizontal overflow**. Residual micro-polish (button-wrapping edge cases, eyebrow/attribution legibility, FAQ/testimonial micro-spacing) has **no confirmed defect on staging** and is best judged in Andrew's Phase 13 visual review — no new design system/fonts/palette introduced. |

## Phase 10 — Accessibility & usability pass ◐ (verified alongside each fix; final sweep in Phase 12)
Confirmed on staging during implementation:
- **Keyboard/modal:** quiz modal Escape close + focus return to launcher + focus entry into dialog + Tab focus-trap, `role="dialog"`/`aria-modal="true"` (#19, verified live).
- **Tap targets:** discrete nav/CTA groups ≥44px on mobile (#11); inline prose links keep the WCAG 2.5.8 inline exception.
- **Transparent link text:** the field-note CTA "hidden" text was restored to a visible label with an accessible full-card `::after` overlay (#12).
- **Forms/labels + announcements:** contact form has `<label>` on every field, aria-describedby, aria-live success/error/invalid status (#27); signup forms already label name/email (#14).
- **Progressive disclosure semantics:** guide "View all" toggles use `aria-expanded`/`aria-controls`, content stays in the DOM (#16).
- **No horizontal overflow** at 320/375/390/430 on every touched page (verified per finding).
Remaining for the Phase 12 sweep: one-H1-per-page audit across all templates, 200%-zoom clipping check, contrast spot-checks on new elements, alt-text/decorative-image sweep. Fix only low-risk confirmed barriers.

## Phase 11 — Analytics & 30-day test plan ◐ (events in place; measurement plan drafted below)
All existing events preserved (analytics gated by `BHP_Analytics_Config::should_render_analytics()` → **production only**; false on staging by design, so events are verified by code + the collision/dedup patterns, not by staging dataLayer).
- **Added / present:** product-page Collection view+click (`collection_upsell_click`, #5); Learning-Hub routing (cards now link to real per-topic search, #12); thank-you Collection click (`collection_upsell_click` + `parent_thank_you`, #22); **Contact submit** (`contact_submit`/`contact_error`, #27); quiz manual/auto launch + suppression (`quiz_modal_opened` with `open_reason`, `quiz_auto_trigger_armed/cancelled`, and #20 suppresses auto-open on commerce pages); lead signup success/error (existing).
- **Dedup:** each new inline event uses a `sessionStorage` fire-once guard (contact, signup) — no duplicate events.
- **30-day measurement plan (14 traffic metrics):** for each — event/source, current baseline (**unknown until prod traffic**), success threshold, and action — to be finalized against **GSC + GA4 on production** post-deploy (see #32 cannibalization appendix which feeds the same plan). This is the one genuinely traffic-dependent deliverable and completes once production has ≥1–2 weeks of data.

## Phase 12 — Staging QA ✅ (consolidated matrix passed 2026-07-19)
**Commerce functional matrix (in-app Browser, populated cart):**
- Cart populated to all 6 books: 3 paperbacks (Mariana/Everest/Amazon @ $11.99) + 3 hardcovers (@ $17.99). Individual PB + HC and both Complete Collections added; same-format items **merge** as WooCommerce intends.
- **Collection pricing correct** — applied as negative fees: "Bundle Savings (Paperback) −$3.98" and "Bundle Savings (Hardcover) −$4.98" (so 3 PB net **$31.99**, 3 HC net **$48.99**). `total_discount` is $0 because the mechanism is `total_fees`=−$8.96, not a coupon. Subtotal $89.94 − $8.96 + shipping $4.99 + tax $4.86 = **$90.83**.
- **Duplicate-click protection:** two rapid clicks on "Add the Complete Paperback Collection" → exactly one batch add (all qty 1, not doubled).
- **Cart drawer** opened on add-to-cart; **#6** verified live (Mariana PB selector hidden, variation 334 auto-selected, real add works).
- **Cart page & checkout page** render with all items; **compact commerce header (#35)** confirmed *with items* — hero 158px desktop / 118px mobile, "FIELD NOTE · BHP" hidden, brand eyebrow kept.
- **Coupon field + order summary** present on checkout.
- **Shipping restriction:** Hawaii → **0 shipping options**; contiguous-US (FL) → 1 option "**Contiguous US Shipping $4.99**" (theme flat/tiered rate; **no** USPS/UPS/FedEx/BookVAULT live carrier rate).
- **Payment methods render through the payment screen:** Stripe (Test Mode on staging) renders its card field. Per Andrew, no paid order placed (already proven in clean iPhone Safari). **No console errors on checkout — the prior Lodash/`memoize` failure did NOT recur in this (non-Claude-in-Chrome) browser**, consistent with `KNOWN_ISSUES.md`.
- **Quiz suppression:** quiz modal **absent** on cart & checkout (fully excluded from all popups); auto-open `false` on shop/product/Collection (Phase 5).
- Test cart emptied afterward (0 items).

**Cross-viewport sweep:** no horizontal overflow at **320 / 375 / 390 / 768 / 1280**; wide-desktop is fluid within the 1300px `--container-max` cap. **No PHP fatals** (`wp eval` green after every deploy); **no console errors** on the pages exercised.

Residual (low-risk, for Andrew's visual pass, not blockers): one-H1-per-page and 200%-zoom sweeps across every template; sitemap spot-check on prod post-deploy.

## Phase 13 — RELEASE REVIEW ⏸ (HARD STOP — approval gate)
Return the full staging review (every finding + disposition, before/after evidence, files changed,
settings changed, product-data changes, pages created, redirects, analytics changes, a11y verification,
known limitations, rollback plan, recommended production package). **Stop for Andrew's approval before production.**

## Phase 14 — Production deployment ⏸ (AFTER Andrew's explicit approval ONLY)

### Deployment artifacts
1. **Theme ZIP** — `brave-hearts-theme-deploy-explorer-expedition-guides` **v1.19.74**. Allowlist verified: only `style.css`, `theme.json`, `assets/`, `inc/`, `template-parts/`, and top-level `*.php`; **no** docs/tmp/plugins/.git/node_modules; **zero** `staging2` URLs in code. (~3.9 MB.) Rebuild the final ZIP at 1.19.74 immediately before deploy.
2. **Plugin update** — `brave-hearts-bundle-pricing` **v1.8.6** (Phase 2 batch add-to-cart + duplicate-click). Separate artifact → `wp-content/plugins/`, not in the theme ZIP.

### Exact production data migrations — ALL VERIFIED STILL PENDING ON PROD (2026-07-19)
Run these on the prod doc root (they are NOT in the ZIP). Current prod state confirmed in parentheses:
| # | Command (prod) | Current prod state |
|---|---|---|
| 2 | `wp user update 1 --display_name="Andrew Signore" --user_nicename="andrew-signore"` | still `Andrew@braveheartspublishing.com` / `andrewbraveheartspublishing-com` |
| 1 | Populate **page 10** (Refund and Returns Policy) from the approved Terms-derived copy, then publish | currently **draft** |
| 3 | `str_replace` the approved shipping range wording into **post 350** (see Phase 1 #3 row for exact strings) | old wording still live |
| 4 | `wp option update woocommerce_allowed_countries specific` **and** `wp option update woocommerce_specific_allowed_countries '["US"]' --format=json` | **`all` / `[]` — prod checkout currently allows every country** |
| 7 | `wp post update 333 --menu_order=1; 14=2; 15=3; 17=4; 18=5; 20=6` | all `menu_order=0` |
| 30 | On **post 366**: replace `product-category/the-amazon/` → `product/adventures-of-charlotte-and-henry-the-amazon-paperback/` | still category-archive |
| 31 | `wp post update 82 --post_title='Reading Level Chart by Grade: What Level Should My Child Be At?'` | still `…Be At??` |
| 17 | **NONE** — prod post 366 already published; the theme deploy lights up the Amazon hub automatically | already published ✅ |
| BH-05 | On **page 355** (Shipping Policy): replace "…for a flat rate of $3.99 per order." with the accurate "$1.99 to $4.99" per-order range wording (see CHANGELOG BH-05 / staging page 355 for exact text) | prod page 355 still says "$3.99 flat" |
| BH-04 | Create a **published page** at slug `gift-guide-thank-you`, template `page-gift-guide-thank-you.php` (`wp post create --post_type=page --post_status=publish --post_name=gift-guide-thank-you --page_template=page-gift-guide-thank-you.php`) — the theme ships the template + redirect key, but the page row is per-env | staging page 614; prod page does not exist yet |
After data steps: `wp sg purge`. **Staging QA leftovers to clear:** Mailchimp contact `andrew+bh04qa@braveheartspublishing.com` (BH-04 test signup) — remove or retain.

**Gift Guide PDF — NO WordPress migration required (confirmed 2026-07-19).** The corrected
customer-facing Gift Guide PDF is **managed and delivered externally through Mailchimp**, attached to
the gift-guide signup automation. The WordPress Media Library file `Ultimate-Gift.pdf` is **not** the
customer-delivered asset — it exists only as an internal readiness gate (`bhp_get_gift_guide_download()`
is read solely for its `ready` flag; its URL is never rendered). Therefore: do **not** upload, replace,
rename, migrate, or publicly link any PDF in WordPress for this release, and its filename/title mismatch
is explicitly non-blocking. **Signup-only delivery is preserved and verified** — no download button, no
PDF link on the gift landing page, no PDF link on the thank-you page (0 `.pdf` hrefs on the gift page);
visitors receive the corrected PDF only via Mailchimp. Gift signup tags verified in code:
`Meaningful Gift Guide` / `Audience: Gift Buyer` / `Source: Gift Buyer Landing Page`. Confirming the
Mailchimp automation's email carries the corrected PDF remains Andrew's check (Mailchimp connector is
not authorized in the build session).

---

## RELEASE PACKAGE — ready for approval (2026-07-19)
- **Commit:** `e2dcbdb2f2533a083dc27fa5936b6378ebbc5fb1` — pushed to
  `origin/feature/production-integration-1.17.1` (`bac8d38..e2dcbdb`).
- **Theme package:** `bhp-theme-PROD-1.19.86.zip` (~3.95 MB), top-level folder
  `brave-hearts-theme-deploy-explorer-expedition-guides`, **Version: 1.19.86**. Allowlist verified:
  only `style.css`, `theme.json`, `assets/`, `inc/`, `template-parts/`, top-level `*.php`; **0**
  docs/tmp/plugins/.git entries; **0** `staging2` occurrences in code; new
  `page-gift-guide-thank-you.php` present.
- **Plugin package:** `bhp-plugin-bundle-pricing-1.8.6.zip` (~174 KB), `brave-hearts-bundle-pricing`
  **1.8.6** → `wp-content/plugins/`.
- **Preserved exactly (verified):** product **333**, variation **334**, SKU/ISBN `9798234014016`,
  Bookvault mapping, Collection/bundle references, analytics identity, historical-order relationships.
- **Production baseline for rollback:** theme **1.19.58** + current page content (see Rollback below).

### Ordered deployment sequence
1. **Backup:** `tar -czf ~/bhp-theme-prod-<ts>.tar.gz` the live theme dir; snapshot the bundle plugin dir; record current versions (`wp theme list --status=active`, `wp plugin get brave-hearts-bundle-pricing --field=version`) and current prod commit.
2. Confirm clean prod state / no in-flight orders mid-deploy.
3. `scp` theme ZIP → `/tmp`; `unzip -l` to re-confirm allowlist on the server.
4. `wp theme install /tmp/<zip> --force --user=1` → **verify** `wp theme list --status=active` shows the slug at **1.19.73** (no new/duplicate theme dir).
5. Deploy the bundle-pricing plugin update (same force-install pattern to the plugins dir) → verify version 1.8.6, no duplicate plugin dir.
6. Run the **data migrations** table above.
7. `wp sg purge` (+ CDN if applicable); `wp eval 'echo "ok";'` (no fatal).
8. Proceed to Phase 15 smoke test.

## Rollback sequence (if anything fails)
1. Confirm the backup tar(s) exist and match pre-deploy checksums.
2. Theme: `wp theme install ~/bhp-theme-prod-<ts>.tar.gz --force` (or restore the tar over the theme dir) back to **1.19.58**; plugin: restore the snapshot dir.
3. Reverse any data steps already applied: `woocommerce_allowed_countries` → `all` + `woocommerce_specific_allowed_countries` → `[]`; menu_order → 0; post 82 title → prior; post 366 link → prior; page 10 → draft; user 1 name → prior. (Author redirect is theme code and reverts with the ZIP.)
4. `wp sg purge`; `wp eval 'echo "ok";'`.
5. Re-check the previously-broken behavior is restored; record the incident in `KNOWN_ISSUES.md`.

## Phase 15 — Production smoke test ⏸ (post-deploy)
- `wp theme list --status=active` = 1.19.73; plugin = 1.8.6; no dup theme/plugin dirs; `php_errorlog` no new fatals.
- Homepage mobile: Complete-Collection hero CTA in first screen; Learning-Hub cards → per-topic search; footer/nav tap targets.
- `/teachers/`: progressive disclosure toggles; **Amazon destination hub now visible** (prod post 366).
- `/books/`: heading "Paperback and Hardcover"; "Buy Direct" retained.
- `/contact/`: native form (not mailto); **submit one real message → confirm `wp_mail` delivery** to `admin_email` (SPF/DKIM).
- `/about/`: ICU-nurse paragraph renders.
- Blog: post 82 title fixed; Amazon post → product link; intent-aware end-of-post CTAs.
- Commerce: repeat the Phase 12 cart/collection/shipping/coupon/compact-header matrix on prod; **Andrew** does the clean-device Stripe check + (optional) one real paid order — agent never handles card details — then verify order/status/emails/Bookvault routing/no-dup/shipping/tax.
- Region: confirm `woocommerce_allowed_countries=specific`/US and Hawaii → no shipping options.
- Sitemap correct; no staging-URL leak; no placeholders.

## Final verdict options
READY TO BEGIN TRAFFIC · READY AFTER ANDREW MANUAL ACTIONS · PRODUCTION CORRECTIONS REQUIRED · ROLLED BACK

---
### Files touched so far (staging only, uncommitted)
`inc/audit-remediation.php` (new), `functions.php`, `style.css`, `front-page.php`,
`page-teachers.php`, `page-books.php`, `page-adventure-kit-thank-you.php`,
`page-about.php`, `template-parts/contact/contact-form.php`,
`page-audience-educators.php`, `page-audience-gift-buyers.php`, `page-audience-organizations.php`,
`inc/class-bhp-printed-for-you.php`,
`plugins/brave-hearts-bundle-pricing/assets/bundle-drawer.js`,
`plugins/brave-hearts-bundle-pricing/brave-hearts-bundle-pricing.php`,
`docs/CHANGELOG.md`, `docs/KNOWN_ISSUES.md`, `docs/RELEASES/FABLE_AUDIT_REMEDIATION.md` (this file).
Phase 3–4 were **theme-code only** (no new per-environment data changes except the staging QA post below).

### Staging DB / settings changes so far (per-environment — NOT in theme ZIP; must be replayed on prod)
user 1 display_name/nicename; page 10 refund content published; post 350 shipping copy; post 350 (shipping);
`woocommerce_allowed_countries=specific` + `woocommerce_specific_allowed_countries=["US"]`;
product `menu_order` 333/14/15/17/18/20 = 1..6.
- **Phase 8 / #30 (per-env content — replay on prod):** Amazon post in-body book link changed from
  `/product-category/the-amazon/` → `/product/adventures-of-charlotte-and-henry-the-amazon-paperback/`
  (staging post 546; **prod post 366 needs the same replace**).
- **Phase 8 / #31 (per-env content — replay on prod):** post 82 title `…Be At??` → `…Be At?`
  (slug unchanged; **prod post 82 needs the same title fix**).
- **Phase 4 / #17 (staging QA parity only — NO prod step):** staging draft post 546
  (`amazon-rainforest-facts-for-kids`) was published so the new Amazon destination hub could be
  render-verified on staging. **Production already has this post published (ID 366)** — the Phase 14
  theme deploy activates the Amazon hub on prod with no content action. (A stray duplicate created
  during the sync attempt, post 605 `...-2`, was trashed.)

---

## PRODUCTION DEPLOYMENT — COMPLETED 2026-07-19
- **Deployed:** theme **1.19.86** + bundle plugin **1.8.6** (prod plugin was **1.8.3**, so the plugin
  update was required). Source commit `b14e5f85c350db5ddb9e0020af6013050a942e77`.
- **Packages:** `bhp-theme-PROD-1.19.86.zip` (sha256 `7965cbeddf9253886ae980cf…`),
  `bhp-plugin-bundle-pricing-1.8.6.zip` (sha256 `41785aaf806a9d19b46390ac…`).
- **Rollback dir (prod):** `~/bhp-rollback-20260719-225125/` containing
  `theme-1.19.58.tar.gz`, `plugin-bundle-pricing.tar.gz` (1.8.3), per-post content/title/status
  backups for 10/355/82/366, and `baseline.txt` (user 1 name, country options, all six menu_orders).
- **Data migrations applied:** user 1 → "Andrew Signore"/`andrew-signore`; `woocommerce_allowed_countries`
  = specific + `["US"]`; menu_order 333/14/15/17/18/20 = 1..6; post 82 title `??`→`?`; post 366 Amazon
  link → exact product; page 10 refund content published; **page 350** Shipping Policy → $1.99–$4.99
  range; gift thank-you page created **ID 409** (`/gift-guide-thank-you/`, correct template).
- **⚠️ Production page IDs differ from staging — corrected during deploy.** `/shipping-policy/` is
  **page 350 on production** (staging uses 355). The shipping copy was first written to prod post 355,
  which is a `shop_order_placehold`, not a page. Post 355 was **restored from backup** (verified back to
  `shop_order_placehold`, no shipping copy) and the copy applied to the correct page 350 (verified live:
  range present, old "$3.99 flat" claim absent). **Always resolve prod IDs via `get_page_by_path()`
  rather than reusing staging IDs.**
- **Caches purged:** SiteGround Speed Optimizer assets + Dynamic Cache, WP object cache flush,
  105 transients deleted. (SG file cache not enabled — benign warning.)
- **Verified live:** theme/plugin versions active, no duplicate theme dir, no `.maintenance`, Stripe
  gateway + Bookvault plugin active, all key pages and all six product pages HTTP 200, gift thank-you
  renders, Kindle claim gone from /books/, Amazon hub visible on /teachers/, author archive 200,
  **no PHP fatals since deploy** (the 3 in the log are old wp-cli eval typos from Jul 6/11/14).
- **`staging2` strings in the deployed theme are intentional** — an environment-aware internal-link
  regex in 4 `inc/` files matching either domain. Not a leaked staging URL.
- **Gift Guide PDF:** delivered externally through Mailchimp; the WordPress attachment is an internal
  readiness gate only. **No WordPress PDF migration was performed or is required.**

---

## PRODUCTION VALIDATION — 2026-07-19 (clean browser, post-deploy)

**Final release status: “Production validated. The failed Fable findings were caused by browser
instrumentation injecting or altering lodash/underscore behavior and did not reproduce in a clean
browser.”**

**No hotfix and no rollback were performed. No production files, options, products, or settings were
changed during this validation.**

### BH-01 and BH-02 — PASSED in clean production validation

Verified live on production theme 1.19.86 / bundle plugin 1.8.6 in a clean (non-instrumented) browser:

| Check | Result |
| --- | --- |
| Mariana paperback variation auto-select | **334** ✅ |
| Add to Cart | enabled; paperback added to cart ✅ |
| `_.template` / `wp.template` error | absent — `wp.template` is a `function` ✅ |
| Stripe card fields at checkout | 4 iframes rendered ✅ |
| “There are no payment methods available” | absent ✅ |
| `memoize` error | absent — `_.memoize` is a `function` ✅ |
| `debounce` error | absent — `_.debounce` is a `function` ✅ |
| Console errors | none ✅ |
| Shipping / totals | Contiguous US $4.99; total calculated ✅ |

Test cart emptied afterwards (0 items). **No order was placed.**

### Fable lodash/underscore errors — environment-specific FALSE POSITIVES

See `docs/KNOWN_ISSUES.md` (REFERENCE entry, "Stripe checkout 'memoize' failure is a Claude-in-Chrome
automation artifact"). Diagnostic indicators distinguishing the artifact from a real defect:

- **Duplicate or altered lodash/underscore globals** in the failing session (an injected second copy
  re-claims `window._` after WordPress's `_.noConflict()`).
- **`wp.template` undefined only in the instrumented environment** — it is a working `function` in a
  clean browser on the same URL.
- **Clean production contains exactly one lodash and one underscore**
  (`/js/dist/vendor/lodash.min.js?ver=4.18.1`, `/js/underscore.min.js?ver=1.13.8`); `window._` is
  genuine Underscore 1.13.8 (`_.runInContext` is `undefined`, i.e. lodash is *not* masquerading as `_`).
- **Variation 334 and Stripe both function normally** in the clean environment.

### Production script configuration (confirmed correct — do not "fix")

`underscore` → `wp-util` (blocking) → `wc-add-to-cart-variation` (defer) → `product-format-autoselect`
(defer). Both deferred scripts share one post-parse queue in dependency order — this **is** the BH-02
fix. Additionally confirmed: the theme and bundle plugin contain **zero** lodash enqueues and **zero**
`script_loader_tag` filters; there are **no mu-plugins**; SiteGround JS optimization is entirely off
(`optimize_javascript=0`, `async=0`, combine/defer unset).

**Rejected "fixes" (each would cause the outage it purports to cure):** removing WordPress core's
lodash (WooCommerce Blocks and Stripe require it); removing `defer` from `wc-add-to-cart-variation` or
`product-format-autoselect` (reintroduces the BH-02 race); excluding scripts from SiteGround JS
optimization (already off); editing `script_loader_tag` (none exists).

### Backups — both PRESERVED

- `~/bhp-rollback-20260719-225125/` — pre-deploy rollback (theme 1.19.58,
  plugin 1.8.3, post content/title/status backups, `baseline.txt`). **Intact and untouched.**
- `~/bhp-hotfix-backup-20260719-235856/` — validation-time backup (theme
  1.19.86, plugin 1.8.6, `state.txt`). Created as a precaution; **unused**.

**No further production changes are approved.**

---

## POST-AUDIT RELEASE — 1.19.91 to PRODUCTION, 2026-07-20

Deployed with Andrew's explicit approval. **Theme 1.19.86 → 1.19.91.** Plugin unchanged at 1.8.6.

**Changes:** both lead-magnet popups retired sitewide (quiz modal is now the only popup); quiz auto-open
extended to every eligible page; homepage "Join the Expedition" newsletter section removed and the Find
Your Adventure quiz promoted into its slot; homepage capture rerouted from the dead `explorer_passport`
key to the existing `reluctant_reader_adventure_kit` funnel; 7 orphaned `#adventure-club` anchors
repointed to `/reluctant-reader-adventure-kit/`.

**Supersedes finding #20** — the commerce-page quiz auto-open carve-out was deliberately reversed on
Andrew's instruction. Cart/checkout/account/order-received remain excluded (payment-flow risk).

**Purchase path verified live on production after deploy:** variation **334** auto-selects, add-to-cart
succeeds ($11.99), Stripe renders 4 iframes, no "no payment methods", shipping $1.99, total $14.70, no
"Perfect Bound", `wp.template` is a function, zero console errors. Test cart emptied; **no order placed**.

**Rollback:** `~/bhp-rollback-20260720-063726/` (theme-1.19.86.tar.gz + baseline.txt).
Package `bhp-theme-PROD-1.19.91.zip`, sha256 `6a107a625b35cb7d7bd7fe3d…`.

**Open follow-ups:** link labels still read "Join the Expedition"/"Join the Adventure Club" (destinations
fixed, wording not); homepage carries three routing surfaces (early audience-gateway + promoted quiz +
modal) — the gateway is the leanest cut if trimming; no visual design review of the promoted section.
