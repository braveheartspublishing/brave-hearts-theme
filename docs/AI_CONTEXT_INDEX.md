# AI Context Index

> ## ⭐⭐ NEWEST, 2026-09-03 - **PRODUCTION IS THEME `1.19.358` / BUNDLE PLUGIN `1.8.83`.** The block immediately below, which records `1.19.356` / `1.8.81`, is **SUPERSEDED ON BOTH VERSION NUMBERS** and is preserved rather than rewritten.
>
> Two theme releases and two plugin releases were built and staging-verified on 2026-09-03. **Theme
> `1.19.358` and bundle plugin `1.8.83` were deployed to production on 2026-09-03**, with the owner's
> explicit approval. `1.19.357` and plugin `1.8.82` did not ship on their own; their contents reached
> production inside the later artefacts. **Those two artefacts are superseded and must not be deployed.**
>
> **Canonical for this series:** `RELEASES/PRODUCTION_RELEASE_1_19_357_358.md` (release record, contents,
> tests, rollback artefact names, the customer-visible behaviour change, known issues) and the
> 2026-09-03 `CHANGELOG.md` entry headed "PRODUCTION IS NOW THEME `1.19.358`" (per-release detail).
> Open issues: `KNOWN_ISSUES.md`. Rules recorded from this series: `DECISIONS.md`.
>
> ⚠️ **Recorded from the deploying lane, not read from production by this block.** Verify with
> `wp theme list --status=active` and `wp plugin get brave-hearts-bundle-pricing --field=version` over SSH
> before quoting these numbers.
> ## ⭐⭐ NEWEST, 2026-09-02 - **PRODUCTION IS THEME `1.19.356` / BUNDLE PLUGIN `1.8.81`.** The block immediately below, which records `1.19.354` / `1.8.79`, is **SUPERSEDED ON BOTH VERSION NUMBERS** and is preserved rather than rewritten.
>
> Two theme releases and two plugin releases were built and staging-verified on 2026-09-02. **Theme
> `1.19.356` and bundle plugin `1.8.81` were deployed to production on 2026-09-02**, with the owner's
> explicit approval. `1.19.355` and plugin `1.8.80` did not ship on their own; their contents reached
> production inside the later artefacts. **The `1.8.80` artefact is superseded and must not be deployed.**
>
> **Canonical for this series:** `RELEASES/PRODUCTION_RELEASE_1_19_355_356.md` (release record, contents,
> tests, rollback artefact names, the one customer-visible behaviour change, known issues) and the
> 2026-09-02 `CHANGELOG.md` entry headed "PRODUCTION IS NOW THEME `1.19.356`" (per-release detail).
> Open issues: `KNOWN_ISSUES.md`. Rules recorded from this series: `DECISIONS.md`.
>
> ⚠️ **Recorded from the deploying lane, not read from production by this block.** Verify with
> `wp theme list --status=active` and `wp plugin get brave-hearts-bundle-pricing --field=version` over SSH
> before quoting these numbers.
> ## ⭐⭐ NEWEST, 2026-09-02 (later the same day) - **PRODUCTION IS THEME `1.19.354` / BUNDLE PLUGIN `1.8.79`.** The correction block immediately below, which records `1.19.349` / `1.8.78`, is **SUPERSEDED ON BOTH VERSION NUMBERS** and is preserved rather than rewritten.
>
> Five theme releases were built and staging-verified on 2026-09-02. **`1.19.353` then `1.19.354` were
> deployed to production on 2026-09-02**, each with the owner's explicit approval, and **bundle plugin
> `1.8.79` was deployed to production on 2026-09-02**. `1.19.350`, `1.19.351` and `1.19.352` did not ship on
> their own; their contents reached production inside `1.19.353`.
>
> **Canonical for this series:** `RELEASES/PRODUCTION_RELEASE_1_19_350_354.md` (release record, contents,
> tests, rollback artefact names, known issues) and the 2026-09-02 `CHANGELOG.md` entry headed "PRODUCTION
> IS NOW THEME `1.19.354`" (per-release detail). Open issues: `KNOWN_ISSUES.md`. Rules recorded from this
> series: `DECISIONS.md`.
>
> ⚠️ **Relayed, not read from production by this block.** Verify with `wp theme list --status=active` and
> `wp plugin get brave-hearts-bundle-pricing --field=version` over SSH before quoting these numbers.

> ## ⛔⛔ CORRECTION 2026-09-02 - **PRODUCTION IS THEME `1.19.349` / BUNDLE PLUGIN `1.8.78`.** Every version line below this block, INCLUDING the 2026-08-03 correction block, is **SUPERSEDED ON THE VERSION NUMBER**.
>
> ⭐ **VERIFIED WITH THE DEFINITIVE INSTRUMENT, 2026-09-02, and this is the difference from the block
> below it.** Read-only over SSH against the production document root:
> `wp theme list --status=active` returns **`1.19.349`** and
> `wp plugin get brave-hearts-bundle-pricing --field=version` returns **`1.8.78`**.
>
> **Corroborated independently** by a read-only HTTP GET of the production home page (HTTP 200,
> 248,926 bytes, canonical `https://braveheartspublishing.com/`, zero `staging2` occurrences):
> **14 theme assets enqueued at `ver=1.19.349`** and **6 plugin assets at `ver=1.8.78`**.
> **Two instruments, agreeing.**
>
> ⛔ **No production write of any kind was made by the pass that wrote this block.** The only production
> contact was the read-only version query above and the read-only GET. `wp eval` and `wp eval-file` are
> blocked against production by the `G1-PRODUCTION-WRITE` gate by design (`CYCLE179-LD-002`, OPEN, and
> recorded in `RUNBOOK.md`'s production verification checklist).
>
> ⚠️ **A CONTRADICTION IS RECORDED HERE RATHER THAN RESOLVED, per the refusal duty.** The build brief
> that authorised this documentation pass stated that production **"stays 1.19.344 tonight"** and that
> the 1.19.349 production deploy **"did not happen"**. **Both instruments say otherwise.** The
> engineering lane that found it does not own the question of how 1.19.349 reached production, and has
> not answered it. **Routed to `chief-of-staff` and to Andrew. Recorded, not decided.**
>
> ⭐ **Production moved from `1.19.157` to `1.19.349` between the block below and this one**, and this
> file recorded none of it. The releases immediately preceding the current state are written up in
> `docs/CHANGELOG.md`: **1.19.342**, **plugin 1.8.77**, **1.19.343 / 1.8.78**, **1.19.344**,
> **1.19.345 / plugin 1.8.79**, **1.19.346**, **1.19.347**, **1.19.348** and **1.19.349**. **Everything
> between `1.19.157` and `1.19.342` still has no record here, and that gap is named rather than filled.**
>
> **Every block and header line below is preserved verbatim rather than edited, so the movement stays
> visible and is not re-derived.**

> ## ⛔⛔ CORRECTION, SAME DAY — **PRODUCTION IS THEME `1.19.157` / BUNDLE PLUGIN `1.8.16`.** The header line below says `1.19.156` and is **SUPERSEDED ON THE VERSION NUMBER**.
>
> **Verified live 2026-08-03:** the production home page (HTTP 200) enqueues **11 theme assets at `ver=1.19.157`** and 4 plugin assets at `ver=1.8.16`.
>
> ⭐ **Production shipped THREE times on 2026-08-03:** `1.19.155` → `1.19.156` (transactional email copy layer) → `1.19.157` (Bookvault dispatch tracker, shipped in **DRY** mode).
>
> ✅ **THE 1.19.156 RECORD GAP IS NOW CLOSED, and a 1.19.157 record exists.** Both were written from the builder's own commit messages, `git show --stat` counts, the builder's writer-lock closeout table and live verification — **not** from a prepared builder report, because none exists. **Each record says so on its face.** New: `RELEASES/PRODUCTION_RELEASE_1_19_156.md` and `RELEASES/PRODUCTION_RELEASE_1_19_157.md`.
>
> ⚠️ **The definitive instrument was NOT run for this correction.** `wp theme list --status=active` requires SSH, and the session that wrote this holds no SSH credentials. **The HTTP enqueue-version check is strong live evidence and is not the definitive check.**
>
> **The header line below is preserved verbatim rather than edited, so the movement stays visible.**

**Last updated: 2026-08-03 — ⛔ PRODUCTION IS THEME `1.19.156` / BUNDLE PLUGIN `1.8.16`, verified live at closeout. Production shipped TWICE this day: 1.19.155 (fully recorded) then a theme-only 1.19.156 email-copy layer (⚠️ NO release record exists for it — gap named in `NEXT_TASK.md`).** Maps every major topic to its one authoritative source. If two documents seem to cover the same topic, this file says which one wins — do not treat a non-canonical document as current truth.

| Topic | Authoritative document | Visibility | Owner | Last verified | Canonical | Superseded documents |
|---|---|---|---|---|---|---|
| ⭐ **Current production THEME version — `1.19.157`** · the Bookvault dispatch tracker, its DRY-mode default, credential handling, rollback and live-fire test window | `RELEASES/PRODUCTION_RELEASE_1_19_157.md` | Public-safe | Engineering + Andrew (approval) | **2026-08-03 — production version verified live over HTTP (`ver=1.19.157`, 11 assets); branch, HEAD, worktree version and clean status verified by `git`** | **Yes** | Supersedes the "no record exists" row below, and the `1.19.156` version headline everywhere |
| ⭐ **The 1.19.156 release — the transactional email copy layer E1–E7, and the footer-filter defect found by rendering** | `RELEASES/PRODUCTION_RELEASE_1_19_156.md` | Public-safe | Engineering + Andrew (approval) | **2026-08-03 — written from the builder's verbatim commit messages, `git show --stat`, and the builder's own deployment closeout record** | **Yes, for its own release.** ⚠️ Its version headline is superseded by 1.19.157 | ⭐ **Closes the documented gap in the row below** |
| ⭐ **The 1.19.155 release — six layers, per-layer rollback paths, and what the release did NOT prove.** ⚠️ Its **non-theme** layers (products 333/15/12, page 3, thumbnails, seven email/site options) are still current on production; its **version headline is superseded** | `RELEASES/PRODUCTION_RELEASE_1_19_155.md` | Public-safe | Engineering + Andrew (approval) | **2026-08-03 — verified live via `wp theme list --status=active`, `wp plugin list`, `wp option get`, `wp eval` and seven HTTP checks; corrected in place at closeout when production moved to 1.19.156** | Yes, **for its own release** | Supersedes `RELEASES/PRODUCTION_RELEASE_1_19_142.md` for the layers it covers |
| ⛔ ~~**Current production THEME version — `1.19.156`**~~ ✅ **GAP CLOSED 2026-08-03** | ~~No release record exists.~~ → **`RELEASES/PRODUCTION_RELEASE_1_19_156.md`** | Public-safe | Engineering | **2026-08-03 — verified live; `ver=1.19.156` also confirmed in live page source** | ⛔ ~~NO — documented GAP~~ → ✅ **The record now exists.** ⭐ **The concern that stopped it being written was right and is respected in how it was closed:** it was written **not** by reconstructing a QA narrative, but from the builder's own verbatim commit messages, `git show --stat` counts and the builder's own writer-lock closeout table, **with every unverified item named as unverified.** The record states its own provenance on its face and claims no QA step it cannot evidence | Row superseded by the two `1.19.156` / `1.19.157` rows above |
| ⭐ **Building and deploying a theme ZIP — the corrected `git archive` line and the mandatory pre-install entry-count assertion** | `RUNBOOK.md` §"Build and deploy a theme ZIP" | Public-safe | Engineering | **2026-08-03 — corrected; the prior line produced 180 files against a real artefact of 356 and would have deleted live `woocommerce/` and `tests/` directories** | Yes | The superseded line is quoted in place in `RUNBOOK.md` rather than deleted |
| **Collection gallery on the funnel pages** (placement map, the caller-side subset rule, the shared enqueue/render predicate) | `RELEASES/COLLECTION_GALLERY_FUNNEL_PAGES_1_19_143.md` + `inc/collection-gallery.php`'s own header | Public-safe | Engineering | 2026-08-02 — ⛔ **BUILT AND COMMITTED, NOT DEPLOYED, NOT BROWSER-VERIFIED** | Yes | — |
| **"Look Inside" gallery component and media registry** | `inc/book-media.php` (registry + provenance) · `template-parts/commerce/look-inside.php` (component contract) · `RELEASES/GALLERY_ASSETS_ANALYTICS_1_19_141.md` (assets + the five analytics events) | Public-safe | Engineering | 2026-08-02 | Yes | — |
| **GTM triggers/tags for the five gallery events** | `Business OS\WORKING-DRAFTS\lead-developer\DRAFT-2026-08-03-GTM-GALLERY-TRIGGER-SPEC.md` — ⚠️ **private working draft, outside this repo; PREPARED, NOT APPLIED.** Container status stays `ANALYTICS/GTM_STATUS.md` | Pointer only | Engineering + Andrew (gate) | 2026-08-02 | Yes (for the spec) | — |
| Company/CSO strategy | *(private — see `CSO_PRIVATE_REFERENCE.md`)* | Private | Andrew | 2026-07-12 | Yes | — |
| Project state | `PROJECT_STATE.md` | Public-safe | Engineering | 2026-07-12 | Yes | — |
| Current task | `CURRENT_TASK.md` | Public-safe | Engineering | 2026-07-12 | Yes | — |
| Roadmap (technical) | `ROADMAP.md` | Public-safe | Engineering | 2026-07-12 | Yes | — |
| Decisions (technical/architectural) | `DECISIONS.md` | Public-safe | Engineering | 2026-07-12 | Yes | — |
| Production status | `ENGINEERING/PRODUCTION_STATUS.md` | Public-safe | Engineering | 2026-07-12 | Yes | — |
| Staging status | `ENGINEERING/STAGING_STATUS.md` | Public-safe | Engineering | 2026-07-12 | Yes | — |
| WooCommerce | `ENGINEERING/WOOCOMMERCE_STATUS.md` | Public-safe | Engineering | 2026-07-12 | Yes | — |
| Complete Collection (bundle pricing) | `ENGINEERING/WOOCOMMERCE_STATUS.md` ("Bundle pricing" section) | Public-safe | Engineering | 2026-07-12 | Yes | — |
| [PARENT_COUPON_CODE_SUPERSEDED] | `RELEASES/COLLECTION_COUPON_PRODUCTION.md` | Public-safe | Engineering | 2026-07-14 (public advertising removed, see `DECISIONS.md`'s Audience Coupon Policy) | Yes | — |
| Audience-coupon scope: how a coupon becomes Collection-only (the per-coupon meta flag, the ONLY route since plugin 1.8.29) | `RELEASES/BUNDLE_PLUGIN_SANITISATION_1_8_29.md` | Public-safe | Engineering | 2026-08-05 | Yes | — |
| Dashboard unit-economics model: where the amounts live and what an unseeded environment does | `RELEASES/BUNDLE_PLUGIN_SANITISATION_1_8_29.md` | Public-safe (carries no figure) | Engineering | 2026-08-05 | Yes | `dashboard-data-sources.md`, `kpi-definitions.md` |
| Mobile-experience release (theme 1.19.201, live on production 2026-08-05) | `RELEASES/MOBILE_EXPERIENCE_1_19_201.md` | Public-safe | Engineering | 2026-08-05 | Yes | — |
| Audience Coupon Policy (Frozen) | `ENGINEERING/FUNNEL_CONSTITUTION.md`, `DECISIONS.md` | Public-safe | Andrew (CSO) + Engineering | 2026-07-14 | Yes | — |
| Mailchimp | `ENGINEERING/MAILCHIMP_STATUS.md` | Public-safe | Engineering | 2026-07-16 (Educator toolkit delivered end to end; all 5 audience journeys built in Draft) | Yes | `Mailchimp-Production-Integration.md`, `Mailchimp-HubSpot-Architecture.md` (historical, root-level, superseded) |
| Audience funnel implementation matrix (at-a-glance per-audience component status) | `ENGINEERING/AUDIENCE_IMPLEMENTATION_MATRIX.md` | Public-safe | Engineering | 2026-07-16 | Yes | — |
| Educator toolkit delivery + Mailchimp manual-completion tracking | `ENGINEERING/MAILCHIMP_MANUAL_COMPLETION_REGISTER.md` | Public-safe | Engineering | 2026-07-16 | Yes | — |
| Adventure Kit | `ENGINEERING/MAILCHIMP_STATUS.md` ("Active automations" section) | Public-safe | Engineering | 2026-07-06 | Yes | — |
| CTA Engine | `ENGINEERING/CTA_ENGINE_STATUS.md` | Public-safe | Engineering | 2026-07-12 | Yes | `phase1d-organic-conversion-architecture.md` (superseded for live-status purposes; still useful for full architecture detail) |
| GTM | `ANALYTICS/GTM_STATUS.md` | Public-safe | Engineering | 2026-07-13 | Yes | `gtm-staging-build-2026-07-09.md`, `gtm-build-verification-2026-07-12.md`, `gtm-ga4-production-readiness-audit-2026-07-12.md` (historical session records, not canonical status) |
| GA4 | `ANALYTICS/GA4_STATUS.md` | Public-safe | Engineering | 2026-07-12 | Yes | — |
| Consent | `ANALYTICS/CONSENT_STATUS.md` | Public-safe | Engineering + Andrew (decision) | 2026-07-13 | Yes | `consent-privacy-decision-record.md` (historical, superseded for current-status purposes) |
| Production GTM/consent deployment | `RELEASES/PRODUCTION_CONSENT_DEPLOYMENT.md` | Public-safe | Engineering + Andrew (approval) | 2026-07-13 | Yes | `RELEASES/PRODUCTION_GTM_CONSENT_READINESS_AUDIT.md` (planning record, still useful for the dependency map/rationale, but superseded for current-status purposes now that the deploy has executed) |
| Production analytics validation (historical discovery of the plugin-staleness gap) | `RELEASES/PHASE10_PRODUCTION_ANALYTICS_VALIDATION.md` | Public-safe | Engineering | 2026-07-13 | Yes | Superseded for current-status purposes by the fix below; still useful for the original evidence/methodology |
| Bundle-pricing analytics-parity fix (current status of production ecommerce events) | `RELEASES/BUNDLE_PRICING_ANALYTICS_PARITY_PRODUCTION.md` | Public-safe | Engineering + Andrew (direction) | 2026-07-13 | Yes | — |
| SEO | `CONTENT/CONTENT_STATUS.md`, `CONTENT/BLOG_STATUS.md` | Public-safe | Content ops (primarily tracked in `brave-hearts-seo-engine` repo) | 2026-07-12 | Yes | `Technical-SEO-Analytics-Setup.md` (historical) |
| Blog content | `CONTENT/BLOG_STATUS.md` | Public-safe | Content ops | 2026-07-12 | Yes | — |
| Pinterest | `CONTENT/PINTEREST_STATUS.md` | Public-safe | Content ops | 2026-07-12 | Yes | — |
| Google Merchant Center | `MARKETING/GOOGLE_MERCHANT_STATUS.md` | Public-safe | Andrew (console) + Engineering (sync config) | 2026-07-13 | Yes | — |
| Product-media gallery workstream (all three titles) — remaining reshoot scope | `ROADMAP.md` → *Planned* → **"Authentic Mariana interior reshoot and gallery replacement"** (status QUEUED) | Public-safe | Andrew (capture/approval) + Commerce/CX (staging implementation) | 2026-08-02 | Yes | `NEXT_TASK.md` top owner TODO (2026-07-31) — **retained as historical context, superseded for the remaining reshoot scope** |
| Deployments | `RUNBOOK.md` | Public-safe | Engineering | 2026-07-12 | Yes | — |
| Rollbacks | `RUNBOOK.md` (rollback procedures section) | Public-safe | Engineering | 2026-07-12 | Yes | — |
| Known issues | `KNOWN_ISSUES.md` | Public-safe | Engineering | 2026-07-13 | Yes | — |
| Legacy blog content audit | `CONTENT/LEGACY_BLOG_CONVERSION_AUDIT.md` | Public-safe | Content ops + Engineering | 2026-07-13 | Yes | — |
| Historical document classification | `HISTORICAL_DOCUMENT_INDEX.md` | Public-safe | Engineering | 2026-07-13 | Yes | — |
| Desktop header layout fix | `RELEASES/HEADER_LAYOUT_FIX_PRODUCTION.md` | Public-safe | Engineering | 2026-07-13 | Yes | — |
| "Printed Just for You" print-on-demand notice | `ENGINEERING/PRINTED_FOR_YOU_STATUS.md` | Public-safe | Engineering + Andrew (approved copy + production deploy) | 2026-07-13 | Yes | — |
| Conversion QA Sprint 1 (full funnel validation, findings) | `ENGINEERING/CONVERSION_QA_SPRINT1.md` | Public-safe | Engineering + Andrew (decision on P0) | 2026-07-13 | Yes | — |
| **Frozen Funnel Architecture (permanent company policy, read first)** | `ENGINEERING/FUNNEL_CONSTITUTION.md` | Public-safe | Andrew (permanent decision) | 2026-07-14 | Yes | — |
| Shared audience-funnel architecture (naming/tracking/page-structure spec) | `ENGINEERING/AUDIENCE_FUNNEL_ARCHITECTURE.md` | Public-safe | Engineering | 2026-07-13 | Yes | — |
| Per-audience implementation status at a glance (which components are live/staged/not built, all 5 audiences) | `ENGINEERING/AUDIENCE_IMPLEMENTATION_MATRIX.md` | Public-safe | Engineering | 2026-07-15 | Yes | — |
| Screenshot-driven fixes A–G (hero caption/transform bounds, mobile dvh centering, question gap, result compaction, WPConsent gear layering, nav breakpoint, homepage quiz consolidation) | `RELEASES/SCREENSHOT_FIXES_1_19_121.md` | Public-safe | Engineering | 2026-07-31 (staging 1.19.121; production untouched at 1.19.112) | Yes | — |
| Homepage hero mobile reading order (`aside_after_title`, shared-hero component contract) | `RELEASES/HOMEPAGE_HERO_MOBILE_ORDER_1_19_120.md` | Public-safe | Engineering | 2026-07-31 (staging 1.19.120; production untouched at 1.19.112) | Yes | — |
| Quiz question-screen fit (two-column answer grid, no-scroll geometry, step state classes) | `RELEASES/QUIZ_QUESTION_FIT_1_19_119.md` | Public-safe | Engineering | 2026-07-31 (staging 1.19.119; production untouched at 1.19.112) | Yes | — |
| Quiz question screens (header removal, typography, answer alignment, dialog accessible name) | `RELEASES/QUIZ_QUESTION_SIMPLIFICATION_1_19_118.md` | Public-safe | Engineering | 2026-07-31 (staging 1.19.118; production untouched at 1.19.112) | Yes | — |
| Find Your Adventure quiz (per-answer results, copy, modal scroll behavior) | `RELEASES/QUIZ_UX_PERSONALIZATION_1_19_93.md` | Public-safe | Engineering | 2026-07-29 (staging 1.19.93; production untouched at 1.19.91) | Yes | — |
| Parent Funnel implementation status | `ENGINEERING/PARENT_FUNNEL_STATUS.md` | Public-safe | Engineering + Andrew (production approval + Mailchimp completion) | 2026-07-13 (Mailchimp blocker resolved 2026-07-14, see `MAILCHIMP_STATUS.md`) | Yes | — |

## Notes on using this index
- **"Canonical: Yes"** means: if this document conflicts with a non-canonical/superseded one, this document wins — go verify live state, don't average the two.
- **Superseded documents are not deleted.** They stay in the repo as historical session records (useful for "why did we decide this" archaeology) but must never be cited as current status.
- **Private-strategy topics** (company-level revenue targets, channel strategy, competitive positioning) have no public-repo document at all by design — see `CSO_PRIVATE_REFERENCE.md` for where that content actually lives.
- This table itself can go stale. If a "Last verified" date is more than a couple weeks old, re-verify the live system before trusting the document as current — see `DOCUMENTATION_GOVERNANCE.md`.
