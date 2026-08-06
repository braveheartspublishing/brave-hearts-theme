# Production GTM/Consent Readiness Audit

**Status: Read-only reconciliation, 2026-07-13. Production untouched. Not a deployment record — no deploy occurred or is authorized by this document.**

## Why this document exists

Andrew approved an isolated 3-file WPConsent production deployment on 2026-07-13. Pre-deployment verification (SSH inspection of production, per Andrew's own instruction to reconcile any doc/live-state conflict before deploying) found that production has **none** of the GTM/consent theme infrastructure the approved 3-file patch assumed already existed. Andrew stopped the deployment and asked for this read-only reconciliation instead: an exact dependency map, minimum coherent package, file-by-file diff, and full deployment planning artifacts — with explicit instruction not to touch production, not to publish GTM, not to enable analytics, and not to expand scope without his separate approval.

## 1. File inventory: production vs. staging vs. repository

| File | Repository | Staging | Production |
|---|---|---|---|
| `inc/class-bhp-analytics-config.php` | Present | Present (md5 `1da9d9ec...` — matches repo exactly) | **Absent** |
| `inc/class-bhp-consent.php` | Present | Present (md5 `1b97f7a1...` — matches repo exactly) | **Absent** |
| `inc/class-bhp-gtm-loader.php` | Present | Present (md5 `868c2ba8...` — matches repo exactly) | **Absent** |
| `inc/class-bhp-utm-attribution.php` | Present | Present (md5 `e9353959...` — matches repo exactly) | **Absent** |
| `inc/class-bhp-analytics-debug.php` | Present | Present (md5 `4b80d04e...` — matches repo exactly) | **Absent** |
| `inc/class-bhp-wpconsent-bridge.php` | Present | Present (md5 `87fc83bf...` — matches repo exactly) | **Absent** |
| `functions.php` require_once block (6 lines, `class-bhp-analytics-config.php` → `class-bhp-wpconsent-bridge.php`) | Present, lines 110–115 | Present, byte-identical | **Absent entirely** — no analytics/GTM/consent require_once lines anywhere in production's `functions.php` |
| WPConsent Free plugin | N/A (not a theme file) | Installed, active, v1.1.7, configured | **Not installed** |
| `bhp_gtm_container_id` option | N/A | `GTM-N474PRSH` | **Does not exist** |
| `bhp_ga4_measurement_id` option | N/A | `G-7M42X19Z2T` | **Does not exist** |
| `bhp_consent_decision_approved` option | N/A | Does not exist (defaults `false`) | **Does not exist** (defaults `false`) |
| `bhp_staging_analytics_override` option | N/A | Does not exist at rest (toggled on/off during QA sessions, always cleaned up) | **Does not exist** (this option only ever affects staging hosts by design — harmless if present on production, but isn't) |

**Verification method:** `grep -rl` for `BHP_Analytics_Config`, `BHP_GTM_Loader`, `BHP_Consent` across the entire live production theme directory returned zero matches. `wp option get` for each option returned "does not exist" on production. `wp plugin list` on production shows no `wpconsent-*` plugin. All checks run read-only via SSH/WP-CLI; no file was written, no option was set, no plugin was installed.

## 2. Dependency map

```
functions.php (production: MISSING this entire block)
  │
  ├─ require_once class-bhp-analytics-config.php   [BHP_Analytics_Config]
  │    Pure static utility, no hooks of its own. Defines:
  │      OPTION_GTM_CONTAINER_ID       = 'bhp_gtm_container_id'
  │      OPTION_GA4_MEASUREMENT_ID     = 'bhp_ga4_measurement_id'
  │      OPTION_STAGING_TRACKING_OVERRIDE = 'bhp_staging_analytics_override'
  │      OPTION_CONSENT_DECISION_APPROVED = 'bhp_consent_decision_approved'
  │    is_staging(), gtm_container_id(), ga4_measurement_id(),
  │    consent_decision_approved(), should_render_analytics(),
  │    is_excluded_internal_request(), debug_mode_available()
  │    Depended on by: Consent, GTM_Loader, Analytics_Debug.
  │
  ├─ require_once class-bhp-consent.php             [BHP_Consent]
  │    Depends on: BHP_Analytics_Config (is_staging, OPTION_STAGING_TRACKING_OVERRIDE).
  │    Reads: bhp_consent_state cookie (visitor-set, first-party).
  │    No hooks of its own — called by GTM_Loader and Analytics_Debug.
  │    current_state(), render_default_snippet(), analytics_allowed().
  │
  ├─ require_once class-bhp-gtm-loader.php           [BHP_GTM_Loader]
  │    Depends on: BHP_Consent (render_default_snippet), BHP_Analytics_Config
  │    (should_render_analytics, gtm_container_id).
  │    Hooks: wp_head priority 2 (render_head_snippet), wp_body_open
  │    priority 1 (render_noscript_snippet).
  │    THE single place GTM's container script is ever printed.
  │    No-ops entirely (prints nothing) if gtm_container_id() is empty —
  │    confirmed by its own test suite (tests/test-gtm-loader.php,
  │    "With no GTM container ID configured, the head snippet prints
  │    nothing" — verified passing on staging this session).
  │
  ├─ require_once class-bhp-utm-attribution.php      [BHP_UTM_Attribution]
  │    Independent of Consent/GTM_Loader (not consent-gated itself —
  │    attribution capture is a separate concern from whether GTM fires).
  │    Reads/writes its own cookies: bhp_attr_first, bhp_attr_last.
  │    Hooks: woocommerce_checkout_order_processed,
  │    woocommerce_store_api_checkout_order_processed — writes 2 order
  │    postmeta fields (_bhp_attribution_first_touch/_last_touch) on
  │    EVERY checkout once deployed, regardless of GTM/consent state.
  │    ** This is the one file in the package that touches live
  │    WooCommerce order processing. In-scope (part of the approved
  │    analytics stack), not "unrelated," but must be called out and
  │    QA'd explicitly per point 6 below. **
  │
  ├─ require_once class-bhp-analytics-debug.php      [BHP_Analytics_Debug]
  │    Depends on: BHP_Analytics_Config, BHP_Consent.
  │    Hook: wp_footer priority 100.
  │    Self-gates to BHP_Analytics_Config::debug_mode_available() —
  │    staging host AND logged-in administrator, "never renders on
  │    production regardless of login state" per its own doc comment
  │    (Phase 14 requirement). Inert on production by design.
  │
  └─ require_once class-bhp-wpconsent-bridge.php     [BHP_WPConsent_Bridge]
       Depends on: nothing PHP-side beyond function_exists('wpconsent')
       (no-ops entirely if WPConsent isn't active).
       Hook: wp_enqueue_scripts priority 20 — writes a small inline JS
       bridge that listens for WPConsent's wpconsent_consent_saved event
       and writes bhp_consent_state (read by BHP_Consent above).
```

**Note on Andrew's named dependency, `BHP_Order_Attribution`:** no class or file by this exact name exists anywhere in the repository. The closest match is `BHP_UTM_Attribution` (`inc/class-bhp-utm-attribution.php`, described above) — flagging this rather than silently substituting, in case a different, not-yet-built class was intended.

**Option defaults if the 6 files are deployed with zero option changes:** all four options (`bhp_gtm_container_id`, `bhp_ga4_measurement_id`, `bhp_staging_analytics_override`, `bhp_consent_decision_approved`) resolve to their code-level defaults (empty string / `false`). With an empty container ID, `BHP_GTM_Loader` prints nothing at all — GTM stays completely inert on production even after the file deploy, independent of the `bhp_consent_decision_approved` gate. This is the most conservative posture and is explicitly called out as a decision point in §7.

## 3. Minimum coherent production package

Exactly the 6 files above, in the exact `require_once` order shown (order matters: `class-bhp-analytics-config.php` must load before anything that references `BHP_Analytics_Config`; `class-bhp-consent.php` before anything referencing `BHP_Consent`) — no more, no fewer. Removing any one of the 6 breaks the chain (e.g. deploying `class-bhp-gtm-loader.php` without `class-bhp-consent.php` produces a fatal `Class "BHP_Consent" not found`). This supersedes the originally-approved 3-file scope (`functions.php` 1-line wiring + `class-bhp-consent.php` fix + `class-bhp-wpconsent-bridge.php`), which is not independently deployable without the other 3.

## 4. Staging-tested-together confirmation

All 6 files are byte-identical between the repository and staging (checksums verified above) and have been exercised together on staging repeatedly this session and in prior phases:
- `tests/test-gtm-loader.php` (9 assertions) and `tests/test-analytics-phase1b.php` (37 assertions) both exercise `BHP_Analytics_Config` + `BHP_Consent` + `BHP_GTM_Loader` together — both pass clean on staging as of today.
- `tests/test-consent-precedence.php` (7 assertions, new today) exercises the `BHP_Analytics_Config` + `BHP_Consent` interaction specifically.
- Today's live-browser QA exercised the full chain end-to-end on staging: WPConsent → bridge → `bhp_consent_state` cookie → `BHP_Consent::current_state()` → `BHP_GTM_Loader::render_head_snippet()`, across all 4 consent-state scenarios (no-decision/accept/reject/change), confirming GTM prints exactly once when consent allows and not at all when denied.
- `BHP_UTM_Attribution` has not been freshly re-exercised today specifically, but is unchanged from its Phase 1B/1C build and covered by `tests/test-analytics-phase1b.php`'s UTM-parsing assertions (cookie sanitization, field capping, malformed-cookie handling) — its `woocommerce_checkout_order_processed` hook itself was not re-verified against a real order this session (no order was placed, per instruction).

## 5. File-by-file diff against current production

Production has none of these 6 files, so every diff is a full-file addition (not a patch to existing content):

| File | Production before | Production after (if deployed) |
|---|---|---|
| `functions.php` | No analytics/GTM/consent `require_once` lines | + 6 lines (110–115 in repo/staging) |
| `inc/class-bhp-analytics-config.php` | Does not exist | New file, 223 lines |
| `inc/class-bhp-consent.php` | Does not exist | New file, 135 lines |
| `inc/class-bhp-gtm-loader.php` | Does not exist | New file, 61 lines |
| `inc/class-bhp-utm-attribution.php` | Does not exist | New file, 139 lines |
| `inc/class-bhp-analytics-debug.php` | Does not exist | New file, 122 lines |
| `inc/class-bhp-wpconsent-bridge.php` | Does not exist | New file, 115 lines |

No existing production file's content would be modified except `functions.php` (a pure addition, no existing lines touched — the insertion point is between two existing, unrelated `require_once` blocks per the repo's own structure).

## 6. Confirmation: no unrelated systems pulled in

Grepped each of the 6 files individually for any reference to dashboard, campaign, scoring, WooCommerce (beyond the one confirmed touchpoint), Mailchimp, or Phase 1D/1E content-engine classes:

- **Two adjacent, similarly-named files were specifically checked and ruled out**: `inc/class-bhp-analytics-adapter.php` and `inc/class-bhp-analytics-metadata-package.php` are Phase 1E content-intelligence/SEO-engine features (Search Console/Pinterest performance ingestion for content drafts) — confirmed via their own doc comments. Neither is referenced by, nor required by, any of the 6 package files. They are **not** part of this package.
- No `require_once`/`require`/`include` statements exist in any of the 6 files pointing to CTA Engine, campaign-landing, conversion-scoring, dashboard, or Mailchimp code. Two incidental comment-only mentions of `BHP_Order_Provenance` (an unrelated plugin-side economics class) exist as analogy references in code comments, not actual dependencies.
- **The one genuine WooCommerce touchpoint**: `BHP_UTM_Attribution` hooks `woocommerce_checkout_order_processed` and `woocommerce_store_api_checkout_order_processed`, writing 2 postmeta fields per order. This is in-scope (part of the approved analytics stack, unchanged from its original Phase 1B/1C build) but is explicitly called out here per Andrew's instruction, since it is the only place this package writes anything into live order data. It does not touch pricing, payment, shipping, or coupon logic.
- `BHP_Analytics_Debug` renders a UI panel but is self-gated to staging+admin only — verified inert on any production host by its own code (`debug_mode_available()` check), independent of this deploy.
- WPConsent Free itself (the plugin, not part of this 6-file theme package) is a separate WordPress.org install with its own settings UI — it does not require or interact with Mailchimp, WooCommerce discount logic, [PARENT_COUPON_CODE_SUPERSEDED], CTA Engine, or Phase 1D systems.

## 7. Deployment planning artifacts (prepared, not executed)

### Isolated deployment manifest
6 files exactly as listed in §3, deployed via the established snapshot-based method (production's own current live files, only these 6 added — same method used for the header-fix and prior narrow patches). No theme-ZIP, no `wp theme install --force` (would delete/replace the entire theme directory unnecessarily for a 6-file addition). Source: repository commits `91bee97`/`bbf0413` (already on `origin/feature/production-integration-1.17.1`) plus the 5 pre-existing infrastructure files, byte-verified identical to staging above.

### Production-native patch plan
1. Download production's current `functions.php` (fresh, not from cache).
2. Insert the 6-line `require_once` block at the same relative position as the repo/staging version (after production's existing unrelated `require_once` statements, before the `// ENQUEUE STYLES & SCRIPTS` section — matches the repo's own surrounding structure).
3. Upload the 5 new infrastructure files + the already-approved bridge file to production's `inc/` directory (6 new files total, since `class-bhp-wpconsent-bridge.php` was already part of the original approval).
4. `wp eval 'echo "ok";'` immediately after to catch any fatal before proceeding further.

### Option/configuration plan (open decision for Andrew, not yet approved)
- Deploying the 6 files alone, with **zero option changes**, leaves `bhp_gtm_container_id` and `bhp_ga4_measurement_id` unset (empty string) — `BHP_GTM_Loader` prints nothing at all in this state, independent of the consent gate. This is the most conservative option and is what this document recommends as the default unless Andrew says otherwise.
- Setting `bhp_gtm_container_id` = `GTM-N474PRSH` and `bhp_ga4_measurement_id` = `G-7M42X19Z2T` (matching staging) would make GTM's script print gate depend solely on `bhp_consent_decision_approved` (staying `false`) and real per-visitor consent — still inert, since that gate stays closed. This is a separate decision Andrew should make explicitly, not bundled into "deploy the files."
- `bhp_consent_decision_approved` stays `false` — hard requirement, explicitly excluded by Andrew both times.
- WPConsent Free install/config: same settings as staging (banner + floating button enabled, matching button labels, `google_consent_mode` disabled) — this part of the original approval is unaffected by the infrastructure-gap finding and can proceed once the file-scope question is resolved.

### QA matrix (to run once/if deployment is separately approved)
Same 25-scenario matrix Andrew specified for the original approval (first-visit/Accept/Reject/Manage Preferences/change-preference/return-visits/clear-storage/desktop/tablet/mobile/keyboard/Escape/zoom/homepage/blog/product/Complete-Collection/cart/checkout/Adventure-Kit-form/Teacher-form/CTA-links/Google-Listings), plus 2 new checks specific to the expanded scope: (a) confirm `BHP_UTM_Attribution`'s order-meta write doesn't alter checkout totals/behavior (a UI-only, no-side-effect-visible-to-customer check, verifiable without a real order by inspecting the hook registration and prior test coverage rather than placing an order), (b) confirm `BHP_Analytics_Debug`'s panel genuinely never renders for a logged-out visitor on production.

### Rollback plan
- File-level: delete the 6 new files, revert `functions.php`'s 6-line addition — production returns to its exact current (verified) state. No existing file content is modified by this deploy, so rollback has zero risk of losing unrelated production drift (e.g. the still-unresolved missing WooCommerce coupon-contrast CSS block, `KNOWN_ISSUES.md`).
- WPConsent plugin (if installed as part of a future approved deploy): `wp plugin deactivate` — falls back to `BHP_Consent`'s fail-closed default automatically.
- No database rollback needed if the conservative "zero option changes" path is taken (§ above) — no new options would be written.

## 8. Explicit non-actions this session

Per Andrew's instruction: production was not modified, GTM was not published, `bhp_consent_decision_approved` was not enabled, WPConsent was not installed on production, and no file was written to production. Every command run against production during this reconciliation was read-only (`grep -rl`, `ls`, `wp option get`, `wp plugin list`, `wp theme list`, `md5sum`, `wp eval 'echo "ok";'`).

## Next step

Return to Andrew/CSO for a scope decision — see `NEXT_TASK.md`. This document is the readiness audit that decision needs, not an approval to proceed.
