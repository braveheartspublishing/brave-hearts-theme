# Parent Funnel — Implementation Status (Phase 1, 2026-07-13)

**Status: landing page built and staging/production-verified (live on production, theme v1.19.14).** The Mailchimp-access blocker described below (Phase 1, 2026-07-13) is **resolved as of 2026-07-14** — Andrew's authenticated Mailchimp session was used directly via browser automation, both to verify live state and to build automations. The account is now on **Standard Annual**. A global purchaser-tagging automation (`Global - Tag Purchasers`) is Active, and a new consolidated 3-email journey (`Parent - Acquisition Funnel`, id 89) is under construction, replacing the split Email1/2-flow + separate Coupon-Flow design described below. **The Phase 0-through-KPI sections below are the original 2026-07-13 findings and are now partially superseded** — for current live Mailchimp state, automation inventory, and remaining build steps, see `MAILCHIMP_STATUS.md` and `NEXT_TASK.md`. **2026-07-14 update:** `Parent - Acquisition Funnel` (id 89) trigger bug fixed (was silently unsaved) and Email 1 fully built and verified live. The permanent Audience Routing Constitution is recorded in `FUNNEL_CONSTITUTION.md`/`DECISIONS.md` — known audiences (including Parents) are never routed through a quiz; a future quiz will handle only unknown/organic visitors and is out of scope for this sprint. This file's non-Mailchimp findings (landing page, popup, lead magnet, analytics event definitions) remain accurate. Companion doc: `AUDIENCE_FUNNEL_ARCHITECTURE.md`.

## Phase 0 — current-state inventory

| Component | Finding |
|---|---|
| Parent landing page | `page-reluctant-reader-adventure-kit.php` (slug `/reluctant-reader-adventure-kit/`) — already existed, already suitable, already live on both environments. No duplicate page found. This sprint extended it rather than building a new one, per the directive's "do not create a duplicate page when a suitable canonical page already exists." |
| Parent popup | `template-parts/acquisition/parent-popup.php` — sitewide except `/teachers/`, `lead_magnet: 'reluctant_reader_adventure_kit'`, `success_redirect_key: 'adventure_kit_thank_you'`. Correctly isolated from the Teacher popup per `.claude/rules/funnels.md` (re-verified this sprint, no change). |
| Lead magnet PDF | `Reluctant-Reader-Adventure-Kit.pdf`, 11.2MB, served via `bhp_get_lead_magnet_pdf_url('adventure_kit_parent')` (same-host HTTPS enforced by `inc/lead-magnet-settings.php`). Live popup copy describes it as "a sample chapter, explorer activity" — matches the directive's "Mariana Trench chapter/activity" framing. **Exact interior chapter number not independently verified** — no PDF-rendering tool available in this environment (`pdftoppm`/poppler-utils not installed); file existence, size, and cover/branding metadata were confirmed, content was not OCR'd. |
| Dormant lead-magnet key | `mariana_parent` exists in `BHP_LEAD_MAGNET_PDF_KEYS` with an empty configured value — genuinely unused, not a competing/duplicate funnel, just an unfilled admin field. No action taken (out of scope; flagging only). |
| Thank-you page | `page-adventure-kit-thank-you.php`, path `adventure-kit-thank-you` — fires `adventure_kit_signup` GA4 event, dedup-guarded via sessionStorage. Confirmed working (existing code, not touched this sprint). |
| Signup form | `template-parts/acquisition/signup-form.php` — shared component, `audience_type`/`lead_magnet`/`source_page` fields, nonce, honeypot (`bhp_website`). |
| Mailchimp state | Per `docs/ENGINEERING/MAILCHIMP_STATUS.md`: Adventure Kit funnel (parent) and [PARENT_COUPON_CODE_SUPERSEDED] coupon email are documented active. **Not independently re-verified this sprint** — see blocker below. |
| Complete Collection page | `/complete-collection/`, built on `BHP_Campaign_Landing`-adjacent bundle-landing markup; `bundle_page_view` fires unconditionally (not consent-gated — a pre-existing inconsistency noted below, out of scope to fix this sprint). |
| GA4/GTM | Unpublished by design (standing, unrelated business decision) — `should_render_analytics()` gates all *new* consent-aware events including this sprint's `parent_landing_view`, but does not gate `bundle_page_view` (pre-existing gap, not introduced this sprint). |
| Duplicate/abandoned funnels | None found. Teacher and Parent funnels remain the only two live audience funnels; both isolated correctly. |

## Objective A — Shared funnel architecture

Delivered: `docs/ENGINEERING/AUDIENCE_FUNNEL_ARCHITECTURE.md` (naming conventions, tracking model, 14-step reusable page structure, QA checklist, documentation requirements, future-audience requirements for Teacher/Bookstore/Gift/Organization funnels — documented only, none built this sprint per explicit instruction).

## Objective B — Parent landing page (built and staging-verified)

**What changed** (both files staging-deployed only, theme version 1.19.13 → 1.19.14):
- `page-reluctant-reader-adventure-kit.php`: added a `parent_landing_view` dataLayer push (gated identically to every other consent-aware event on the site — see Analytics section); added a hero secondary-CTA row (Free Chapter primary, Complete Collection secondary — `data-bhp-event="parent_hero_secondary_cta_click"`); inserted three new sections ahead of the existing single-book section (now retitled "Prefer to Choose One Book First?" to correctly demote it to secondary, per the directive's "primary offer is the Complete Collection"):
  - **Collection section**: names all 3 books, both formats, links to `/complete-collection/` for live pricing rather than hardcoding numbers (deliberate choice — eliminates a price-drift risk between this page and the Collection page).
  - **Trust section**: reuses the existing `kirkus-credibility.php` (expanded mode) and `amazon-review-showcase.php` (compact mode, `mariana_trench` slug) components — no new trust infrastructure built, no fabricated reviews. The "40 classrooms" claim was **not used**, per the directive's explicit instruction pending evidentiary confirmation.
  - **FAQ section**: 9 items covering every topic the directive required (age range, reluctant-reader fit, chapter count, paperback/hardcover, real-world-facts basis, POD shipping expectations, Collection contents, PDF cost, email frequency/unsubscribe) as plain semantic `<details>/<summary>` HTML — no JS, natively keyboard-accessible, no new component built.
- `style.css`: added `.passport-page-hero__cta-row` and `.passport-faq`/`.passport-faq__item` rules using existing design tokens only; version bump for cache-busting.

**Staging QA performed and observed (live browser, `staging2.braveheartspublishing.com`):**
- Content: all new sections render with correct text (Collection, Trust — Kirkus + 4 Amazon reviews, FAQ — all 9 items present and closed by default). Verified via `get_page_text` and DOM inspection, not just source review.
- CTAs: hero primary (`#adventure-kit-signup`), hero secondary and Collection-section CTA (`/complete-collection/`) all point to correct live destinations — verified via `read_page` interactive-element extraction, not assumed from markup.
- Desktop: zero console errors on initial load.
- Mobile (~472px effective viewport — this machine's resize tool doesn't hit exactly 375px, confirmed via `window.innerWidth` per standing project practice): zero horizontal overflow (`scrollWidth === innerWidth`), zero console errors.
- FAQ: 9 `<details>` elements confirmed present via DOM query, `open=false` by default, native keyboard accessibility requires no JS (not separately tested with a real Tab/Enter sequence this pass — inferred from the element type, not directly observed).
- PHP: zero fatals (`wp eval 'echo "ok";'` returned "ok" post-deploy).
- `parent_landing_view` event: **code-verified, not observed firing.** Confirmed via source inspection that it's gated by `class_exists('BHP_Analytics_Config') && BHP_Analytics_Config::should_render_analytics()` — the identical condition already used by every other consent-aware event this project has shipped (Phase 1B's `view_item_list`/`add_shipping_info`/etc.). On this staging browser session, `window.dataLayer` was present but empty (length 0) because neither a real consent grant (`bhp_consent_state` cookie) nor Andrew's staging tracking override was active — both are pre-existing, documented gates (`inc/class-bhp-consent.php`), not something this sprint changed. **This is expected behavior, not a defect** — confirmed by checking that the Collection page's `bundle_page_view` event, which is NOT consent-gated (a separate, pre-existing inconsistency, out of scope to fix here), fired unconditionally in the same session, proving the empty dataLayer wasn't a site-wide accident. A real firing test requires either Andrew's staging override toggle or a real consent grant — neither was exercised this session, consistent with the standing rule against creating consent state improperly.
- Screenshot-based visual QA was **not completed** — the browser automation's screenshot tool timed out repeatedly this session (a tooling limitation; `get_page_text`/`read_page`/DOM `javascript_tool` checks all worked normally and were used instead to verify actual rendered content).
- Tablet viewport was not separately tested this pass.

**Production:** untouched. Staging-only per standing safety rule — do not deploy without Andrew's current-turn explicit approval (requesting below).

## Lead magnet delivery audit

Confirmed: correct current file, HTTPS same-host URL, no broken link (200 status, verified earlier this session), no obsolete branding visible in the PDF metadata/filename, reasonable file size (11.2MB — acceptable for a one-time download, not emailed as an attachment). **Not independently confirmed:** exact interior page/chapter content (no rendering tool available), whether pricing/fulfillment claims inside the PDF itself are current (would require the same rendering capability). No PDF changes made — none were required or requested.

## Form and tagging QA

**Not completed this sprint.** Verifying tag application, source-attribution survival, duplicate-subscriber handling, and journey-start behavior all require either a real form submission against live Mailchimp (not authorized without a designated test contact) or Mailchimp-side inspection (blocked — see below). The form's client-side contract (fields, nonce, honeypot) was confirmed by reading `signup-form.php`, not by submitting it.

## Email 1 (PDF delivery)

**Not independently re-verified this sprint.** Per `MAILCHIMP_STATUS.md`, this automation was rebuilt and test-verified in an earlier session (2026-07-13, "Redesign Reluctant Reader Adventure Kit email(s)"). No changes were made or needed this sprint. Re-confirming it live requires Mailchimp access — blocked.

## Email 2 (result/transformation) — reviewed, ready for implementation

The directive supplied full approved strategic copy verbatim. Reviewed against the requested criteria; **no rewrite performed** (treating it as approved strategic direction, per instruction) — findings below are advisory only.

**Review findings:**
- **Grammar/cadence:** clean. The staccato three-question opening ("Did they ask a question? / Want to know what happened next? / Tell you a fact about the deep ocean later?") is a deliberate rhythm device, not an error — reads naturally, especially on mobile where short lines matter most.
- **Merge tag:** `<<First Name>>` is correct Mailchimp merge-tag syntax. First-name fallback behavior itself was verified working in an earlier session (`MAILCHIMP_STATUS.md`); not re-tested this sprint.
- **Unsupported claims:** none found. "My hope is that each adventure helps your child feel a little more curious, a little more confident..." is appropriately hedged (aspiration, not guarantee). "I can do this" is framed as the child's own internal takeaway from finishing chapters, not a claim Brave Hearts makes about outcomes. Confirmed absent: "better child," any guaranteed literacy/academic/developmental claim.
- **Repetition:** "adventure" appears 5 times across the email. This reads as deliberate thematic reinforcement (matches the brand's "Big Places. Brave Hearts." throughline used elsewhere in the site's copy) rather than a drafting accident — flagging for awareness, not recommending a cut, since removing it would likely weaken brand consistency more than it would tighten the copy.
- **CTA clarity:** exactly one primary CTA, "Explore the Complete Collection" — matches the directive's explicit instruction.
- **Brand voice:** consistent with the established "Andrew as author/uncle" personal framing used in Email 1 (per `MAILCHIMP_STATUS.md`), signed personally, closes with the tagline.
- **Mobile readability:** short paragraphs, no dense blocks — should render well; not independently confirmed in a real Mailchimp mobile preview (blocked, no Mailchimp access).

**Implementation status:** copy is ready to build into Mailchimp as-is. **Not implemented this sprint** — building/scheduling a live Mailchimp automation is Mailchimp-account-level work this session cannot reach (see blocker below). Timing target ("approximately two days after Email 1") also needs configuring inside Mailchimp's automation builder, not something achievable from this environment.

## Email 3 (Collection coupon) and coupon handoff

**Not independently verified this sprint.** The directive requires auditing the actual current setup (likely a separate automation per Mailchimp's own constraints) before changing anything — that audit requires Mailchimp automation-level access, which is blocked. What *was* re-verified (WooCommerce-side, via WP-CLI, read-only): [PARENT_COUPON_CODE_SUPERSEDED] coupon config on staging — `discount_type=percent`, `coupon_amount=10`, `individual_use=yes`, no expiration meta present. This matches the already-documented policy ("10% discount applies only to the Complete Collection, not individual books or two-book combinations") and was previously confirmed scoped correctly to Collection-only in an earlier sprint this session (`CONVERSION_QA_SPRINT1.md`). The Mailchimp-side handoff (no contacts trapped between automations, no coupon sent before Email 2, no duplicate sends) could not be checked.

## Complete Collection destination — light re-verification

Re-confirmed via WP-CLI (read-only, staging): all 6 core products (333, 20, 18, 17, 15, 14) are `_stock_status=instock` — matches the current print-on-demand policy (`DECISIONS.md`). [PARENT_COUPON_CODE_SUPERSEDED] coupon config unchanged from the prior sprint's verification. Full destination audit (pricing display, format toggle, mobile/desktop rendering, trust signals, checkout continuity) was already completed live this session in `CONVERSION_QA_SPRINT1.md` and is not stale enough to warrant a full redo — no changes have touched that page since.

## Analytics and attribution

| Event named in directive | Status |
|---|---|
| `parent_landing_view` | **Built this sprint.** Code-verified correct gating; not observed firing live (consent not granted in this session — see landing-page QA above). |
| `parent_lead_magnet_signup` | Does not exist under this name. The equivalent live event is `adventure_kit_signup` (fires on the thank-you page, confirmed working, pre-existing). Not renamed — renaming a working, dedup-guarded production event was judged out of this sprint's safe scope; recorded as a naming-convention gap in `AUDIENCE_FUNNEL_ARCHITECTURE.md`'s tracking model instead. |
| `parent_pdf_click` | Does not exist. Not built this sprint (would require identifying and instrumenting the actual PDF download link/button on the thank-you page — deferred, not in the achievable-this-session set). |
| `parent_email2_collection_click` | Cannot exist without Email 2 being live in Mailchimp first (blocked, see above). |
| `parent_coupon_click` | Cannot exist without Email 3 audit first (blocked, see above). |
| `view_complete_collection` | Does not exist under this name. The real live event is `bundle_page_view` — same purpose, different name, pre-existing (not renamed this sprint, same reasoning as `adventure_kit_signup` above). |
| `add_to_cart` | Live, confirmed in `event-dictionary.md`, unaffected by this sprint. |
| `begin_checkout` | Live, unaffected. |
| `purchase` | Live, dedup-guarded, unaffected. |

**GA4/GTM publication state:** unchanged — still unpublished by standing business decision, unrelated to this sprint. **Mailchimp link tracking, coupon reporting, source attribution inside Mailchimp:** not verified (blocked). **UTM persistence:** `BHP_UTM_Attribution` is live sitewide and unaffected by this sprint's changes (not re-tested this pass, no code in its path was touched). **KPI dashboard Parent Funnel segmentation:** not assessed this sprint — no existing KPI dashboard code was found to segment by funnel/audience during Phase 0; would need to be scoped as its own task if wanted.

Per the directive's explicit instruction, tracking is **not** claimed operational beyond what was actually code-verified or observed above.

## KPI definitions (baselines: not yet measured)

| KPI | Definition | Baseline |
|---|---|---|
| Landing-page sessions | GA4 sessions on `/reluctant-reader-adventure-kit/` | Not yet measured — GA4 unpublished |
| Signup rate | `parent_lead_magnet_signup`-equivalent events ÷ landing sessions | Not yet measured |
| PDF-delivery rate | Successful Email 1 sends ÷ signups | Not yet measured — Mailchimp not audited this sprint |
| Email 1 open rate | Mailchimp automation report | Not yet measured |
| Email 1 download-click rate | Mailchimp link-click report | Not yet measured |
| Email 2 open rate | Mailchimp automation report (once live) | Not yet measured — Email 2 not yet implemented in Mailchimp |
| Email 2 collection-click rate | `parent_email2_collection_click` or Mailchimp link-click report | Not yet measured |
| Email 3 open rate | Mailchimp automation report | Not yet measured |
| Coupon-click rate | `parent_coupon_click` or Mailchimp link-click report | Not yet measured |
| Collection-page conversion rate | `purchase` ÷ `bundle_page_view` sessions from Parent Funnel source | Not yet measured — no funnel-level source segmentation built yet |
| Add-to-cart rate | `add_to_cart` ÷ Collection sessions | Not yet measured |
| Checkout-start rate | `begin_checkout` ÷ `add_to_cart` | Not yet measured |
| Purchase conversion rate | `purchase` ÷ landing sessions | Not yet measured |
| Average order value | `purchase` event value | Not yet measured |
| Paperback vs. hardcover mix | `purchase` event item format | Not yet measured |
| Coupon usage rate | Orders with [PARENT_COUPON_CODE_SUPERSEDED] applied ÷ total Parent Funnel orders | Not yet measured |
| Revenue per lead | Attributed revenue ÷ signups | Not yet measured |
| Revenue per landing-page visitor | Attributed revenue ÷ sessions | Not yet measured |
| Unsubscribe rate | Mailchimp automation report | Not yet measured |
| Spam-complaint rate | Mailchimp automation report, where available | Not yet measured |

No baseline numbers are fabricated. All require either GA4 publication or Mailchimp access, neither available this session.

## What could not be completed (genuine blockers as of 2026-07-13 — RESOLVED 2026-07-14, see below)

1. ~~No active Mailchimp browser session.~~ **Resolved 2026-07-14:** Andrew's authenticated Chrome session was used directly via browser automation for both verification and live automation building.
2. ~~The available Mailchimp MCP toolset explicitly excludes automations, workflows, contacts, segments, and tags.~~ Still true of that specific MCP toolset, but no longer a blocker — direct browser automation against Andrew's session bypasses it.
3. Consequence at the time: Email 1 re-verification, Email 2 live implementation, Email 3/coupon-handoff audit, tag/segmentation QA, and all Mailchimp-side KPI baselines were blocked. **As of 2026-07-14, work is in progress, not blocked** — see `MAILCHIMP_STATUS.md` for current automation inventory and `NEXT_TASK.md` for the exact ordered remaining steps (Email 1/2/3 content, purchase-sync buffer, Conditional Split, testing, contact migration, old-flow retirement, 2 post-purchase automations). KPI baselines remain genuinely not-yet-measured (GA4 still unpublished, journey still mid-build) — not fabricated.

None of the directive's STOP CONDITIONS were triggered by anything discovered this sprint (no Mailchimp structural incompatibility found, no subscriber reset/double-enrollment risk found, no production/staging drift found, no real transaction required, no pricing/coupon ambiguity found, no unsupported claim shipped, no Bookvault/checkout change made, no broad theme rewrite needed, the lead magnet in use matches the documented current version, no destructive replacement was required). The blockers above are access limitations, not STOP-CONDITION triggers requiring a business decision from Andrew — they need Andrew (or someone with Mailchimp login) to complete the Mailchimp-side steps directly, or a future session with Mailchimp automation-level MCP access.

## Pre-existing inconsistency noted, not fixed (out of scope)

`bundle_page_view` (Complete Collection pageview event) fires unconditionally, without the consent gate this sprint's new `parent_landing_view` correctly uses. This predates this sprint and wasn't introduced by it — flagged here and in `AUDIENCE_FUNNEL_ARCHITECTURE.md` for a future, separate analytics-consistency pass, not fixed now (touching `bundle-landing.js` would be exactly the kind of "unrelated system" this sprint's own instructions said not to modify).
