# Consent Status

**The actual critical path blocking GTM/GA4 from ever going live.**

## Superseded: CookieYes is rejected, not the approved path (2026-07-13)
Andrew and ChatGPT/CSO decided **not** to use CookieYes — it adds an unnecessary paid-SaaS dependency (account creation, vendor terms, a traffic-cap ceiling) for a site this size. The vendor comparison and "Recommended specific vendor: CookieYes" sections below are kept as historical research record only — **do not act on them, do not create a CookieYes account, do not request a CookieYes site ID.**

**Current canonical decision:** **WPConsent Free** (`wpconsent-cookies-banner-privacy-suite`, official WordPress.org plugin) is the approved staging candidate. It installs directly from the WordPress.org repository — no external CMP account is required for the free functionality this project needs.

## Production deployment: complete (2026-07-13)

Andrew explicitly approved an isolated production deployment of 3 WPConsent files; pre-deployment verification found production had none of the underlying theme-side GTM/consent infrastructure that patch assumed (see `ANALYTICS/GTM_STATUS.md`). Andrew stopped that narrower deploy and directed a read-only readiness audit (`RELEASES/PRODUCTION_GTM_CONSENT_READINESS_AUDIT.md`), then reviewed it and approved the full 6-file package instead. **That package is now live on production**, along with WPConsent Free v1.1.7 installed and configured identically to staging. Full 25-scenario logged-out browser QA passed on the live production domain — see `RELEASES/PRODUCTION_CONSENT_DEPLOYMENT.md`. GTM remains deliberately unpublished: no container/measurement ID configured, `bhp_consent_decision_approved` stays `false`, confirmed `gtmScriptCount: 0` in every consent state tested. See `DECISIONS.md` for the decision record and `NEXT_TASK.md` for what happens next (authenticated GTM Preview, then a separate analytics-activation decision).

## Implementation status: complete on both staging and production (2026-07-13)
WPConsent Free v1.1.7 is installed, activated, and configured identically on **both staging and production** (`enable_consent_banner`, `enable_consent_floating`, custom button labels matching the approved wording, `google_consent_mode` disabled). Full QA passed on both environments the same day: source control reconciled into clean commits, complete accessibility QA (tablet/mobile viewports, real keyboard Tab/focus-trap/Escape, 200% zoom), all 4 consent-state scenarios and core launch-critical events validated (via direct `dataLayer` inspection on staging; via live browser QA plus direct `dataLayer`/script-count checks on production — GTM Preview UI itself unreachable on either, no authenticated Google session in this environment). See `RELEASES/WPCONSENT_STAGING_IMPLEMENTATION.md` for the staging implementation record and `RELEASES/PRODUCTION_CONSENT_DEPLOYMENT.md` for the production deployment record.

### Architecture
- WPConsent owns the banner UI only (Accept All / Reject Nonessential / Manage Preferences) and its own `wpconsent_preferences` cookie (categories: essential/statistics/marketing).
- A new theme file, `inc/class-bhp-wpconsent-bridge.php`, listens for WPConsent's `wpconsent_consent_saved` JS event and writes the visitor's choice into the pre-existing `bhp_consent_state` cookie in the exact format `BHP_Consent` already reads — the integration point that has been documented in this codebase since Phase 1B, unchanged.
- WPConsent's own `google_consent_mode` setting is disabled (`0`) so only `BHP_Consent` ever emits the *default* `gtag('consent',...)` call — avoiding two independent systems deciding the default state. WPConsent's own `unlockScripts()` still fires a redundant `gtag('consent','update',...)` call on every preference save (hardcoded in its JS, not gated by that setting) — verified harmless: both calls are always derived from the same `preferences` object, so they can never disagree.
- `BHP_Consent`, `BHP_GTM_Loader`, `BHP_Analytics_Config` are **unchanged** except for one bug fix (below) — matches the "zero changes needed to adopt a CMP" design from Phase 1B.

### Defect found and fixed during QA
`BHP_Consent::current_state()`'s staging QA override (`bhp_staging_analytics_override`) unconditionally forced `analytics_storage` to `granted`, even when a visitor had explicitly clicked Reject — meaning GTM would load on staging regardless of a real Reject choice. Fixed 2026-07-13: the override now only fills in a default *before* any real visitor choice exists; an explicit choice (once the `bhp_consent_state` cookie exists) always wins. Verified both directions post-fix: Accept → GTM loads exactly once; Reject → GTM does not load, with the override still on. See `DECISIONS.md`.

### Known limitation (not a defect, pre-launch-appropriate)
WPConsent Free's "Manage Preferences" modal only shows category toggles for categories with at least one *scanned/registered* cookie. Since no real GTM tags are published yet (0 published, matching the current GTM state), the Statistics/Marketing categories have nothing registered and don't render toggles — only the always-on Essential category shows. The floating reopen button correctly opens this modal, but there's currently no granular UI control to flip an existing choice without clearing cookies. **The underlying mechanism is proven correct** (a simulated `wpconsent_consent_saved` event with changed preferences correctly updates `bhp_consent_state` in both directions) — this is a UI-completeness gap in the free plugin's current unscanned state, not an integration defect. Recommended: re-check once real GTM tags are configured and the cookie scanner has something to detect, or accept this as a pre-launch limitation since no real visitor has an existing "wrong" choice to fix yet.

## What exists
- Consent Mode v2 default snippet (`BHP_Consent::render_default_snippet()`) — all 4 signals (`analytics_storage`, `ad_storage`, `ad_user_data`, `ad_personalization`) default `denied` for **every visitor globally**, printed before the GTM loader tag. **Correction (2026-07-12, verified against live code):** there is no region/country/EEA detection anywhere in `class-bhp-consent.php`, `class-bhp-gtm-loader.php`, or `class-bhp-analytics-config.php` — this document previously stated the default-deny was scoped "for EEA/UK/CH region codes," which was inaccurate. The actual behavior is simpler and more conservative: denied by default for all traffic everywhere, regardless of geography, until a real consent choice is recorded via the `bhp_consent_state` cookie (or the bounded staging-only QA override).
- A first-party cookie (`bhp_consent_state`) the consent class reads from — but nothing currently writes a real, visitor-chosen value to it.
- A dual-gate loader architecture (`BHP_Analytics_Config::should_render_analytics()`): internal-traffic exclusion, environment tracking-enabled check, production consent-decision-approved gate (`bhp_consent_decision_approved` option, defaults false), and the per-visitor Consent Mode signal. All 4 must pass or nothing prints — fails closed, not open.

## What does NOT exist
- **No cookie/consent banner UI anywhere in the codebase or live site** — confirmed via live DOM/cookie/localStorage inspection.
- **No `gtag('consent','update',...)` call anywhere** — even if GTM were published, no real visitor could ever move off "denied."
- No legal/privacy review has been performed for GDPR/CCPA/COPPA-adjacency — the audience skews toward children's content, which raises COPPA-adjacent considerations beyond generic e-commerce consent requirements.

## What Andrew needs to decide
1. The consent approach itself: build a custom banner, adopt a CMP plugin, or document a reasoned "no banner needed" position.
2. Whether that decision needs outside legal/privacy input first (likely yes, given the audience).
3. Once resolved: explicitly approve `bhp_consent_decision_approved` on production.

## What must exist before GTM publish
A working consent banner + update mechanism, at minimum for EEA/UK/CH traffic. Until then, GTM staying unpublished is the correct, intended state — not a bug to route around.

## Architecture comparison (Phase 9, 2026-07-12) — analysis only, nothing implemented

**Option A — existing WordPress consent plugin already installed: does not apply.** `inc/class-bhp-consent.php`'s own header comment (written 2026-07-06, reconfirmed unchanged since) states plainly: "this site currently has no cookie/consent banner and no consent-management platform (confirmed by direct audit)." No CMP plugin slug, script handle, or integration code appears anywhere in this repo (checked for Complianz, CookieYes, Cookiebot, Real Cookie Banner, GDPR Cookie Consent, OneTrust, Termly, iubenda — only a code comment naming two of these as *future* candidates). Whether something is active on the live production/staging WordPress install itself was not independently re-verified in this session (that would require a live `wp plugin list`, which is outside this session's approved scope); if Andrew already knows one is installed, that changes the comparison and should be raised directly rather than assumed from this repo audit.

**Option B — Google-certified consent-management platform** (e.g. Cookiebot, OneTrust, Complianz's certified CMP tier, CookieYes):
- Legal/compliance risk: Lowest of the four options — these platforms track regulatory changes (GDPR/UK-GDPR/CH-FADP updates) as their core product, which a custom build cannot match without ongoing legal review.
- Consent Mode v2 compatibility: Built-in, first-class — Google's own "certified CMP" designation specifically means native Consent Mode v2 support, including the `gtag('consent','update',...)` call this codebase's `BHP_Consent` class already reads.
- EEA/UK/CH behavior: Purpose-built for this — geo-detection, correct default-deny posture, and jurisdiction-specific banner variants are standard features.
- US behavior: Most (Cookiebot, OneTrust) also handle CCPA/CPRA opt-out signals (e.g. Global Privacy Control) as part of the same platform.
- Accessibility: Generally WCAG-conscious out of the box (keyboard nav, focus trap, screen-reader labels) since these vendors sell into enterprise clients with their own accessibility requirements — but quality varies by vendor and theme; still needs a spot-check once selected.
- Mobile UX: Mature, responsive banner patterns; low risk.
- Performance: Adds one external script (typically small, 10-30KB) plus a render-blocking-adjacent banner; well-optimized vendors lazy-load the full preference-center UI.
- Maintenance: Lowest ongoing burden — vendor handles regulatory-copy updates.
- Cost: The only option with a recurring subscription fee (free tiers exist for low-traffic sites at some vendors, e.g. Cookiebot's free tier caps at a low pageview count that this site would likely stay under initially, but that should be verified against current traffic before relying on it).
- WordPress/WooCommerce compatibility: All major vendors ship official WP plugins; no known WooCommerce conflicts.
- Compatibility with the existing fail-closed loader: Clean fit — the CMP only needs to set the `bhp_consent_state` first-party cookie (already-documented integration point in `class-bhp-consent.php`) or call `gtag('consent','update',...)` directly; zero changes needed to `BHP_GTM_Loader`/`BHP_Analytics_Config`/any ecommerce event code.
- Rollback: Deactivate the plugin; `BHP_Consent` reverts to its existing hard-coded default-deny behavior automatically (fails closed, not open) — genuinely trivial.

**Option C — lightweight custom implementation** (a small banner built directly in this theme):
- Legal/compliance risk: Highest — Andrew (or counsel) becomes personally responsible for correct default-deny logic, correct geo-scoping, and keeping pace with regulatory change, with no vendor safety net. Given the audience is children's-content-adjacent (COPPA-adjacent considerations, per `CONSENT_STATUS.md` above), this is the option where a mistake is most consequential and least likely to be caught early.
- Consent Mode v2 compatibility: Achievable — the hard integration work (default-deny snippet, `gtag('consent','update',...)` call, fail-closed loader) is already built in `class-bhp-consent.php`/`class-bhp-gtm-loader.php`. Only the banner UI and the "write visitor choice to `bhp_consent_state`" logic are missing.
- EEA/UK/CH behavior: Must be hand-built and hand-tested; the geo-detection logic already exists (`BHP_Consent` reads region codes) but banner copy/behavior per jurisdiction would need to be written from scratch.
- US behavior: Would need explicit design work for CCPA/CPRA-style "opt-out" (not "opt-in") framing, which is a materially different UX pattern than the EEA opt-in banner — easy to get subtly wrong without dedicated legal review.
- Accessibility: Entirely on this team to build and test (focus trap, ARIA roles, keyboard dismiss, color contrast) — achievable given this theme's existing accessibility track record on the popup/drawer components, but real added scope.
- Mobile UX: Same — buildable, matching the theme's existing responsive patterns, but added scope.
- Performance: Best of the four options — no external script, minimal inline CSS/JS, same pattern as the existing lead-magnet popups.
- Maintenance: Highest ongoing burden — any regulatory change (a new EU ruling, a new US state law) requires this team to notice it and ship a code change, with no vendor doing that work.
- Cost: Lowest direct cost (engineering time only, no subscription), but the legal-risk cost is real and not captured in a dollar figure.
- WordPress/WooCommerce compatibility: No conflict risk since it's fully custom, matching the theme's own conventions.
- Compatibility with the existing fail-closed loader: Best possible fit by construction — it would be built directly against the documented integration point, no plugin-conflict surface at all.
- Rollback: Trivial — remove the banner markup/enqueue, `BHP_Consent` reverts to default-deny automatically.

**Option D — another safer existing option (documented "no banner needed" position):**
- Not recommended to evaluate as a real option here. This site collects data via GTM/GA4 and uses cookies for cart/session state; a defensible "no consent mechanism needed at all" position is very unlikely to hold for a site with EEA/UK/CH-reachable traffic, and this is exactly the kind of judgment this report should not make unilaterally (see "no unsupported legal conclusions" instruction). If Andrew wants this path explored, it needs actual legal input, not an engineering opinion.

## Recommended architecture
**Option B (a Google-certified CMP)** for the actual consent UI and regulatory logic, **layered on top of the Option C-equivalent work that is already done** in this codebase (`BHP_Consent`, `BHP_Analytics_Config`, `BHP_GTM_Loader` — the fail-closed default-deny scaffold, the Consent Mode v2 signal plumbing, and the documented `bhp_consent_state` integration point). This is not really "B vs. C" — it's "use a vendor for the part where getting it wrong carries real legal exposure (banner copy, jurisdiction logic, opt-in vs. opt-out framing), while keeping the part that's already built and already correct (the fail-closed technical gate)." Given the children's-content-adjacent audience specifically, the lower legal-risk profile of a certified CMP outweighs the cost and minor performance overhead — but this is an engineering recommendation, not a legal one, and Andrew's own legal/privacy judgment should be the actual decider per the existing "What Andrew needs to decide" list above.

## Named-vendor comparison (2026-07-13, overnight research — live web search, current official docs/pricing pages cited)

Four real, Google-certified, WordPress-native options researched. This site is small (36 posts, 6 products, no paid ads yet, no international traffic pattern established) — free-tier limits matter more here than they would for a larger site.

| Criterion | **Complianz** | **CookieYes** | **Cookiebot (Usercentrics)** | **Real Cookie Banner** |
|---|---|---|---|---|
| Google certification | Yes — CMP ID 332, TCF 2.2 certified (July 2024) | Yes, certified for Consent Mode v2 | Yes, certified, Consent Mode v2 enabled by default | Yes, supports Consent Mode v2 |
| Consent Mode v2 on free tier? | No — paid feature (from $59/yr) | **Yes, included in free tier** | Only below the 50-subpage free-tier cap | **No — Pro-only**, a real gap for a EU-facing site on the free plan |
| WordPress/WooCommerce support | Native WP plugin, explicit WooCommerce compatibility documented | Native WP plugin | Native WP plugin (Usercentrics-maintained) | Native WP plugin, WooCommerce-aware |
| Free tier / cost | No functional free tier for Consent Mode v2 — $59/yr minimum for the features this site needs | Free tier: Consent Mode v2, banner, consent log, 5,000 pageviews/mo, 5 scans/mo — likely sufficient at this site's current traffic. Paid from $10/mo if outgrown | Free for <50 subpages/1 domain (this site: well under 50 pages) but Consent Mode v2 stops working once auto-upgraded to Premium at 50+ pages; Premium from ~€12-49/mo by subpage count | Free plugin exists but the one feature this project needs (Consent Mode v2) is Pro-only, undisclosed pricing found this pass |
| Regional (EEA/UK/CH) behavior | Strong — one of the most complete EU-compliance-focused tools | Strong, but free tier **cannot serve different banners by visitor location** — a real limitation for a site with any EU traffic | Strong, geo-aware banner behavior | Strong on paid tier |
| US privacy handling | Documented CCPA support | Documented, included | Documented via Usercentrics | Documented |
| Accessibility | Generally solid, WCAG-conscious (not independently verified this pass) | Generally solid (not independently verified) | Generally solid, enterprise-grade (not independently verified) | Generally solid (not independently verified) |
| Preference-center / re-open control | Yes | Yes | Yes | Yes |
| Vendor lock-in / rollback | Low — deactivating the plugin reverts to this codebase's existing default-deny scaffold automatically | Low, same reasoning | Low, same reasoning | Low, same reasoning |

## Recommended specific vendor (SUPERSEDED — see banner at top of this document; kept for historical record only)
**CookieYes**, specifically because Consent Mode v2 — the one feature this integration actually needs — is included on CookieYes's free tier, while Real Cookie Banner gates that exact feature behind Pro and Cookiebot's free tier only holds it until an auto-upgrade threshold. Complianz is a strong, thorough tool but has no free path to Consent Mode v2 at all. CookieYes's free-tier traffic cap (5,000 pageviews/month) should be checked against this site's actual current traffic before committing — if this site is already over that, Cookiebot's <50-subpage free tier or a paid CookieYes tier become the fallback options. **This is a technical recommendation only; it does not replace Andrew's own read of each vendor's current terms, especially data-processing/DPA terms, which were not evaluated in this pass.**

## Branch decision (2026-07-13 overnight build, Phase 4): Branch B — SUPERSEDED same day

The original overnight-build finding was that none of the 4 researched paid/SaaS-account CMPs (Complianz, CookieYes, Cookiebot, Real Cookie Banner) satisfy a no-account/no-terms bar, so implementation was parked as "Branch B — external account required." **This finding is superseded.** Andrew and ChatGPT/CSO reviewed the recommendation the same day and rejected it: CookieYes (and the other three researched vendors) add an unnecessary paid-SaaS dependency this site doesn't need at its current stage. **WPConsent Free**, installed directly from the WordPress.org plugin repository, requires no external vendor account for the free functionality this project needs — this reclassifies the task as effectively Branch A (no external account required for initial installation), not Branch B.

### Setup checklist (current — WPConsent Free)
1. Verify official plugin identity/slug (`wpconsent-cookies-banner-privacy-suite`) and current version against the WordPress.org plugin directory before installing.
2. Install the plugin from the official WordPress.org repository on **staging only**.
3. Activate on staging only. Do not start any paid trial, do not enter billing details, do not create an external vendor account unless the plugin's own free tier unexpectedly requires one for Consent Mode v2 — if so, stop and report the exact limitation rather than proceeding.
4. Configure required controls (Accept All / Reject Nonessential / Manage Preferences) and required default-denied state for all 4 Consent Mode v2 signals.
5. Verify integration with the existing `BHP_Consent`/`BHP_GTM_Loader`/`BHP_Analytics_Config` fail-closed architecture — the same documented integration point (`bhp_consent_state` cookie, or a direct `gtag('consent','update',...)` call) applies regardless of which plugin is used; no code changes needed to those classes to adopt WPConsent.
6. Full staging QA matrix (first visit, accept, reject, manage/change preferences, return visits, desktop/mobile, keyboard, 200% zoom, all major page types, cart/checkout, lead forms, CTA links, Google Listings & Ads).
7. GTM Preview validation once the consent integration is confirmed working. Do not publish or submit GTM.
8. Production decision (whether to approve `bhp_consent_decision_approved`) remains Andrew's separate, explicit, current-turn call — not implied by a passing staging QA pass.

### Information needed
None from Andrew for the free-tier installation itself — this is the point of choosing a WordPress.org-native plugin over a SaaS-connected one. If a future feature genuinely requires an account (e.g. a paid tier for something beyond Consent Mode v2), that would be flagged and stopped on, not assumed.

## Implementation plan (current — WPConsent Free, staging only)
1. Install and activate WPConsent Free on staging from the official WordPress.org repository.
2. Configure the banner to write `bhp_consent_state` (or call `gtag('consent','update',...)` directly, whichever WPConsent supports) — zero changes needed to `BHP_Consent`/`BHP_GTM_Loader`/`BHP_Analytics_Config` per the existing integration-point design.
3. Verify on staging: default-deny before any visitor choice, correct signal values after accept/reject, preference reopen/change behavior, no duplicate GTM injection, no conflict with WooCommerce cart/checkout.
4. Andrew explicitly approves `bhp_consent_decision_approved` on production only after staging verification passes — this is a separate decision from "staging QA passed."
5. Proceed to GTM Preview/DebugView validation (see `GTM_STATUS.md`) and then GTM publish — still each requiring Andrew's separate, current-turn approval per `.claude/rules/production-safety.md`.
