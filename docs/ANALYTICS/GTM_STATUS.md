# GTM Status

> ## ⭐ 2026-08-02 — a full trigger/tag specification now exists for the five gallery events. **PREPARED, NOT APPLIED.**
>
> The five `look_inside_*` events shipped in 1.19.141 with **no GTM trigger or tag**, and that is still true — **no GTM object was created, edited, submitted or published.** What changed is that the missing configuration is now fully specified rather than merely noted as absent: **8 new Data Layer Variables, 5 Custom Event triggers, 5 GA4 Event tags**, with exact parameter mappings, the GA4 custom-dimension/metric registrations, and a 14-step Preview verification procedure.
>
> ⛔ **Location: `Business OS\WORKING-DRAFTS\lead-developer\DRAFT-2026-08-03-GTM-GALLERY-TRIGGER-SPEC.md` — a private working draft, deliberately NOT copied into this public repository.** GTM configuration is an owner gate.
>
> **Three things from it worth knowing here:**
>
> - **`bhp_format` is deliberately left unmapped.** `look-inside.php` emits `data-bhp-format=""` unconditionally — the gallery holds no commerce state by design — so mapping it would send an empty string forever. This follows this container's own Phase-9 precedent exactly: *"No parameters were mapped without a real, non-empty source."*
> - **Four dataLayer keys are renamed on the GTM side only** — `item_index`/`item_type`/`item_group`/`item_label` → `gallery_item_*` — to keep them out of GA4's ecommerce `item_*` namespace, which this property already populates for real. **No JavaScript change, so nothing has to be redeployed to apply it.**
> - **Applying it changes nothing customer-facing and sends nothing to GA4.** The container is still **0 Submitted / 0 Published**, and the real blocker is still consent (`CONSENT_STATUS.md`).
>
> ⚠️ **The counts in the sections below were last live-verified 2026-07-13.** Re-verify in the container before creating anything, in case an object already exists.


**Last verified live: 2026-07-12** (GTM container itself, via direct GTM console inspection) / **2026-07-13** (production theme-code deployment state, via direct SSH file inspection).

## RESOLVED (2026-07-13): the theme-side GTM loader is now deployed to production

Earlier the same day, direct inspection found production had **zero trace** of `inc/class-bhp-gtm-loader.php`, `inc/class-bhp-consent.php`, or `inc/class-bhp-analytics-config.php` — the theme-side loader stack existed only in the repo and on staging. Andrew reviewed the resulting readiness audit and approved deploying the full 6-file package. It is now live on production: `class-bhp-analytics-config.php`, `class-bhp-consent.php`, `class-bhp-gtm-loader.php`, `class-bhp-utm-attribution.php`, `class-bhp-analytics-debug.php`, `class-bhp-wpconsent-bridge.php`, all checksum-verified identical to the repo/staging versions. **GTM still does not actually load anything on production** — `bhp_gtm_container_id` and `bhp_ga4_measurement_id` are deliberately left unset, and `bhp_consent_decision_approved` stays `false`, so `BHP_GTM_Loader::render_head_snippet()` prints 0 bytes (confirmed both by direct test and by 25 live browser QA scenarios, `gtmScriptCount: 0` in every state). See `RELEASES/PRODUCTION_CONSENT_DEPLOYMENT.md` for full deployment/QA detail and `RELEASES/PRODUCTION_GTM_CONSENT_READINESS_AUDIT.md` for the original planning record.

## Container
`GTM-N474PRSH`, account "Brave Hearts Publishing," container "www.braveheartspublishing.com," `accountId 6364597357`, `containerId 257525520`, Workspace 2 ("Default Workspace").

## Current build (post Phase 9 minimum gap patch)
- **27 variables**: 1 constant (`GA4 Measurement ID`) + 26 Data Layer Variables (23 original + `DLV - bhp_book`, `DLV - bhp_format`, `DLV - bhp_source`, added Phase 9).
- **39 triggers**: all type "Custom Event," each `TRG - Custom Event - <name>` (38 original + `TRG - Custom Event - bundle_type_purchased`, added Phase 9).
- **40 tags**: 1 `Google Tag` (base config, fires on `Initialization - All Pages`) + 39 `Google Analytics: GA4 Event` tags (38 original + `TAG - GA4 Event - bundle_type_purchased`, added Phase 9).
- **108 pending workspace changes** (0 Modified / 108 Added / 0 Deleted — the 2 existing tags edited in Phase 9, `amazon_outbound_click` and `related_content_click`, were themselves still-unpublished "Added" objects from the original build, so editing them did not create separate "Modified" entries), all by `andrew@braveheartspublishing.com`.
- **0 Submitted, 0 Published.** Live Version 1 = "Empty Container," published 6 days before this build began — nothing from GTM has ever gone live.

## Quality (verified, not assumed)
Base Google Tag and 2 event tags (`purchase`, `add_to_cart`) individually inspected — both correctly reference `{{GA4 Measurement ID}}`, correctly map event-specific parameters to matching DLVs, correctly use "Send Ecommerce data" with Data Layer source for ecommerce events. `TAG - GA4 Event - bundle_add_to_cart` also inspected as the precedent for flat-parameter (non-ecommerce) business-custom tags: no "Send Ecommerce data," only parameters with an existing DLV are mapped (`bundle_type`; the real code payload also includes `format`/`product_count`/`cart_value`/`already_complete`, deliberately left unmapped since none has a DLV). Remaining tags confirmed correct at list level (correct type, correct 1:1 trigger linkage) but not all individually opened.

## Coverage
39 of 45 real codebase events now covered (was 38; `bundle_type_purchased` added Phase 9). 1 correctly excluded (`bhp_debug_internal_order_purchase_suppressed`, staging-only debug event). 6 uncovered and explicitly deferred — see `EVENT_MATRIX.md`.

## Phase 9 minimum gap patch (2026-07-12)
Approved by CSO decision: added only `bhp_book`/`bhp_format`/`bhp_source` DLVs plus full trigger/tag coverage for `bundle_type_purchased`, wired the 3 new variables into the 2 existing tags whose underlying events actually provide those parameters (`amazon_outbound_click` — all 3; `related_content_click` — `bhp_source` only, since that template never sets `data-bhp-book`/`data-bhp-format`). No parameters were mapped without a real, non-empty source. Full detail: `docs/RELEASES/GTM_PHASES.md`.

## Preview/DebugView test plan
Generic funnel-walk procedure: `docs/gtm-configuration-blueprint.md` §8. Phase 9-specific scenario checklist (exact expected values + duplicate-firing failure criteria for `bhp_book`/`bhp_format`/`bhp_source` and `bundle_type_purchased`, including individual-book, Paperback Collection, Hardcover Collection, and mixed-format purchases, plus Amazon outbound and related-content clicks): same doc, §8a, added 2026-07-12.

## Blocker to going live
**Consent only** — see `CONSENT_STATUS.md`. The plugin-staleness gap found earlier 2026-07-13 (production's `brave-hearts-bundle-pricing` v1.7.1 predating the event architecture this GTM build's triggers expect) was **fixed the same day** via an isolated analytics-only patch — see `RELEASES/BUNDLE_PRICING_ANALYTICS_PARITY_PRODUCTION.md`. The GTM build itself remains correct and ready for Preview/DebugView testing, and production's actual live event stream now matches what GTM's triggers expect.

## RESOLVED (2026-07-13, same day): production event stream now matches GTM's trigger/tag build
The Phase 10 finding below (GTM's trigger/tag build coverage did not match production's actual live event stream) is closed. Live-verified after the bundle-pricing patch: `view_item_list`, `select_item`, `view_item`, `add_to_cart`, `view_cart`, `begin_checkout`, `add_shipping_info`, `add_payment_info`, `bundle_add_to_cart` all now fire on production under the correct names with full GA4 ecommerce payloads. `contextual_cta_click` still lacks full CTA-attribution parameters (separate, theme-file gap — see `KNOWN_ISSUES.md`); this doesn't block GTM's core ecommerce tags. Full evidence: `RELEASES/BUNDLE_PRICING_ANALYTICS_PARITY_PRODUCTION.md`.

## Phase 10 finding, 2026-07-13 (historical — see RESOLVED note above): GTM's trigger/tag build coverage ≠ production's actual live event stream
Every "X of 45 events covered" statement in this document describes GTM's own trigger/tag configuration, verified true by direct inspection of the workspace. It did **not** mean those events reached GTM correctly from production — they didn't, for most ecommerce events, because of the plugin-staleness gap above. This was previously invisible because no session had diffed production's actual live plugin files against the repo (every prior "production readiness" check correctly focused on the theme-level consent/GTM-loader infrastructure, which is a separate, unrelated set of files that *is* current on production). Full evidence: `RELEASES/PHASE10_PRODUCTION_ANALYTICS_VALIDATION.md` §2; resolution: `RELEASES/BUNDLE_PRICING_ANALYTICS_PARITY_PRODUCTION.md`.

## Preview/DebugView connection attempt, 2026-07-13 (bounded diagnostic session) — root cause identified, activation stays deferred
Andrew authenticated into Tag Assistant successfully for the first time this project (previous sessions were blocked purely on Google sign-in). Despite that, the connection to `staging2.braveheartspublishing.com` still failed. A focused, bounded diagnosis (not a repeated retry loop) isolated two independent, non-GTM causes: (1) this session's own browser-automation tool specifically blocks `googletagmanager.com`/`google-analytics.com` at the network level — proven via direct `fetch()` tests showing `stripe.com`/`jsdelivr.net` succeed while Google's tracking domains fail with "Failed to fetch"; (2) Andrew's own browser also failed to connect (Tag Assistant reported "Ad blocker may be blocking tags" even with visible extensions disabled) — likely antivirus web-protection or DNS-level ad-blocking, not fully isolated. Neither cause is a GTM, consent, or WordPress-configuration defect — the container build, staging's `bhp_gtm_container_id`/`bhp_ga4_measurement_id` options, and the consent architecture are all confirmed correct. Full diagnostic detail: `KNOWN_ISSUES.md`. **Analytics activation remains deferred** until a genuine authenticated Preview/DebugView pass succeeds from an unblocked network/browser.

## Re-verification snapshot, 2026-07-13 (overnight build, Phase 2)
Re-checked the live workspace directly (Overview, Tags, Triggers, Variables pages) — **unchanged since the Phase 9 patch**: 27 variables / 39 triggers / 40 tags / 108 Added / 0 Modified / 0 Deleted / 0 Submitted / 0 Published. Every trigger has exactly 1 associated tag (no orphans, no duplicates). Spot-checked required events (GA4 base tag, `purchase`, `view_item`, `add_to_cart`, `begin_checkout`, `add_shipping_info`, `add_payment_info`, `contextual_cta_click`, `amazon_outbound_click`, `related_content_click`, `bundle_type_purchased`, `adventure_kit_signup`) — all present with correct 1:1 trigger linkage. Minimum-patch objects (`DLV - bhp_book`/`bhp_format`/`bhp_source`, `TRG`/`TAG - bundle_type_purchased`, the `amazon_outbound_click` and `related_content_click` parameter mappings) confirmed still exactly as built. No configuration defect found — no GTM changes made this pass.

**No container export was attempted.** GTM's native "Export Container" feature operates on a saved Version, and this container has none besides the original empty one (0 Submitted, 0 Published) — creating a Version to export from is adjacent to the publish workflow, which this session is explicitly barred from touching. The manual verification above (exact counts + full tag/trigger/variable enumeration, captured live) is this session's authoritative snapshot instead.
