# Release Record — WPConsent Free Staging Implementation

**Status:** Staging only, complete and QA-passed 2026-07-13 (implementation) + 2026-07-13 (closeout: source control, full accessibility QA, consent-state and core-event validation). **Production untouched.**

## Closeout addendum (2026-07-13, same day)

- **Source control**: 3 clean commits created on `feature/production-integration-1.17.1` — `91bee97` (fix: consent precedence + new regression test `tests/test-consent-precedence.php`), `bbf0413` (feat: WPConsent bridge integration), `b6bf20d` (docs). Both existing suites (`test-gtm-loader.php`, `test-analytics-phase1b.php`, 46 assertions) plus the 7 new precedence assertions pass with zero regressions. `git push` blocked by the same known non-interactive credential prompt (`KNOWN_ISSUES.md`) — commits sit locally for Andrew's GitHub Desktop push.
- **Accessibility QA completed** (previously flagged as not directly tested): tablet (~733px effective) and mobile-range (~475px effective) viewports both confirmed — all banner controls visible, correctly labeled, no horizontal overflow. Real keyboard `Tab` navigation confirmed working end-to-end including a full focus-trap cycle inside the Manage Preferences modal (H2 → Close → Toggle Essential → description → Accept All → Close → Save and Close → Powered-by link → wraps to H2 — never escapes to the banner behind it). Real `Escape` keypress confirmed closing the modal. Real `Enter`/`Space` keypresses did **not** trigger native button activation in this session's browser-automation tool, while `Escape`'s `keydown` listener fired normally on the same real key events — the most likely explanation is that browsers only convert Enter/Space into a native button click for **trusted** input events, and this automation tool's synthesized key events aren't flagged as trusted, whereas an app's own `keydown` listener (like Escape-to-close) fires regardless of trust. This narrows (but does not eliminate) the prior session's more general "shadow-DOM key routing" theory — still not directly observed, still inferred correct via native `<button type="button">` HTML-spec guarantees plus confirmed `.click()`-equivalence, not claimed as verified. No overlap confirmed between the banner (fixed top, 0–65px) and the checkout page's Place Order button.
- **GTM Preview**: the browser session's `tagassistant.google.com` tab shows "Sign in" — not authenticated, no domain being debugged. Entering Andrew's Google credentials is outside what this session can do. All 4 consent-state scenarios (A: no decision, B: Accept All, C: Reject Nonessential, D: change preference reject→accept) were instead validated via direct `dataLayer`/script-count inspection with the staging override temporarily enabled and cleaned up after: A → `analytics_storage` granted (override default), ad_* denied, GTM loads once; B → all 4 signals granted, GTM loads once, bridge's and WPConsent's own redundant `consent update` calls agree; C → all 4 denied, **GTM container script never prints at all** (`gtmScriptCount: 0`) — the exact defect that was fixed, still correct; D → reject→accept via the floating reopen button updates state correctly, GTM loads exactly once, no duplicate container.
- **Core event validation** (dataLayer-substituted, same reason as above): `view_item_list`, `select_item`, `view_item`, `add_to_cart`, `remove_from_cart`, `view_cart`, `begin_checkout`, `add_shipping_info`, `add_payment_info`, `bundle_add_to_cart` all fired with well-formed payloads via real UI interactions (not URL-based shortcuts — the URL-based `?add-to-cart=` flow does **not** fire these events, only the real AJAX Add-to-Cart/side-cart-drawer path does, since `add_to_cart`/`view_cart`/`begin_checkout` are pushed from `bundle-drawer.js`, not from a page load). The "Amazon outbound click" and "automatic CTA click" items in the task's list map to this codebase's real event names `customer_review_source_click` and `related_content_click`/`contextual_cta_click` (from `data-bhp-event` attributes) — both confirmed firing correctly. `bundle_type_purchased` requires a completed real order and was left untested (no real purchase was placed, per instruction). Adventure Kit signup was **not tested** — the task called for "a designated test contact," but no such contact was specified this session, and guessing an email would create an unauthorized real Mailchimp signup; left pending for a session where Andrew designates one. No console errors observed across any tested page. `wp eval 'echo "ok";'` clean after every change. Staging override and all test cookies/cart items were cleaned up at the end.

## Summary
WPConsent Free (`wpconsent-cookies-banner-privacy-suite` v1.1.7, official WordPress.org plugin) installed, configured, and integrated with the theme's existing fail-closed consent architecture on staging. Supersedes the earlier CookieYes recommendation (see `ANALYTICS/CONSENT_STATUS.md` and `DECISIONS.md`) — no external CMP account was required.

## What shipped (staging only)
1. **Plugin**: WPConsent Free installed/activated from WordPress.org. No account, no trial, no billing.
2. **Settings** (`wpconsent_settings` option): banner enabled, floating reopen button enabled, `cancel_button_text` = "Reject Nonessential", `preferences_button_text` = "Manage Preferences", `accept_button_text` = "Accept All", `google_consent_mode` disabled (to avoid a second system emitting default consent calls).
3. **New theme file**: `inc/class-bhp-wpconsent-bridge.php` — a small, WPConsent-specific bridge that writes the visitor's banner choice into the pre-existing `bhp_consent_state` cookie `BHP_Consent` already reads. No-ops entirely if WPConsent isn't active.
4. **Bug fix**: `inc/class-bhp-consent.php`'s `current_state()` — the staging QA override no longer overrides an explicit visitor choice (see DECISIONS.md for the full defect writeup).
5. **functions.php**: one new `require_once` line for the bridge file.

`BHP_GTM_Loader` and `BHP_Analytics_Config` were **not modified**.

## Required Consent Mode v2 default state — verified
| Signal | Default (no choice) |
|---|---|
| `analytics_storage` | denied |
| `ad_storage` | denied |
| `ad_user_data` | denied |
| `ad_personalization` | denied |

## QA results

| Scenario | Result |
|---|---|
| First visit before decision | Banner visible (top position, full width, all 3 buttons present with correct labels), no cookies set, no GTM script |
| Accept All | `wpconsent_preferences` + `bhp_consent_state` set (all 4 signals granted), `gtag('consent','update',granted)` fired, banner hides |
| Reject Nonessential | `bhp_consent_state` set (all 4 signals denied), banner hides |
| Manage Preferences | Modal opens (only Essential category populated — see known limitation), Save/Close functions |
| Change accepted → rejected | No dedicated UI control yet (known limitation, see below) — underlying mechanism verified correct via simulated event |
| Change rejected → accepted | Same as above |
| Return visit after acceptance | `bhp_consent_state` persists, banner stays hidden, GTM loads (override on) |
| Return visit after rejection | `bhp_consent_state` persists (denied), banner stays hidden, GTM does not load |
| Clear storage and revisit | Behaves as first visit — banner reappears, all cookies absent |
| Desktop (1366px) | Banner and all controls render correctly |
| 200% zoom | Banner and all 3 buttons remain visible with real (non-zero) dimensions, no unexpected overflow |
| Keyboard navigation | First Tab press lands directly on the banner's close button with a visible 3px+ focus outline; Accept/Reject/Preferences are genuine `<button type="button">` elements (native Enter/Space activation guaranteed by the HTML spec). Direct keyboard-triggered activation could not be *observed* through this session's browser-automation tooling (a shadow-DOM/synthetic-key-event routing limitation of the tool, not the site) — `.click()`-based activation was used to complete the remaining scenarios, and is confirmed to trigger the correct behavior every time. |
| Homepage | Clean — banner behaves correctly, no console errors |
| Blog article | Clean — consent persists, banner correctly stays hidden after a choice |
| Product page | Clean — Add to Cart unaffected, banner doesn't block interaction |
| Complete Collection | Clean |
| Cart | Item added/removed correctly, totals calculated correctly ($14.70 estimated total observed), no interference |
| Checkout | Email field and Place Order button render normally; **no order was placed** (per instruction) |
| Adventure Kit / Teacher forms | Teacher signup form on `/teachers/` renders correctly, unaffected; not submitted (avoids a real Mailchimp signup) |
| CTA Engine links | Header CTA and homepage book links render correctly with correct hrefs, unaffected |
| Google Listings & Ads | Plugin remains `active`, no PHP fatal — read-only confirmation, not modified this session |
| GTM loads no more than once | Confirmed — exactly one `gtm.js` script tag and one noscript iframe, in both the Accept and (post-fix) Reject-blocked states |
| No contradictory consent commands | Confirmed — WPConsent's own redundant `gtag('consent','update',...)` call (hardcoded in its JS, not gated by settings) and the bridge's call are always derived from the same `preferences` object, so they can never disagree |
| Console errors | None observed on any tested page |
| PHP errors | None — `wp eval 'echo "ok";'` clean after every deploy; no new `php_errorlog` entries |

### Not directly tested
- True keyboard-triggered native button activation (Enter/Space) — see closeout addendum above. Tab navigation, focus trap, and Escape are directly observed and confirmed correct with real key events; only the final "does Enter/Space click the button" step remains inferred (native `<button>` HTML-spec guarantee + confirmed `.click()`-equivalence), not directly observed, due to a tool limitation in how this session's browser automation delivers synthesized keyboard input.
- GTM Preview UI itself (the tag-firing/debug console) — requires Andrew's own Google sign-in; this session has no credentials and entering credentials on his behalf is outside what this session can do. **Substituted with direct `dataLayer` inspection** for both consent-state scenarios and core events (see closeout addendum) — genuine evidence of correctness, not a full substitute for Preview's own tag-firing confirmation.
- `bundle_type_purchased` event (requires a completed real order — not placed, per instruction).
- Adventure Kit signup event (requires a designated test contact — none specified this session).
- Tablet and mobile viewports, keyboard Tab/focus-trap/Escape, and 200% zoom are now directly confirmed (see closeout addendum) — previously flagged as untested in the initial implementation pass.

## GTM state (unchanged, confirmed)
27 variables / 39 triggers / 40 tags / 108 unpublished additions / 0 submitted / 0 published. This session never authenticated to GTM and made zero changes to the workspace.

## Rollback (staging)
- `inc/class-bhp-consent.php`: prior version (without the override fix) was overwritten in place on staging without a separate timestamped backup — this is staging, not production, and the fix is a strict correctness improvement (narrows a bug) with no behavior change for any state other than "override on + explicit reject," so risk is minimal. If needed, the file is fully reconstructable from git (uncommitted local working tree at time of writing).
- WPConsent plugin: `wp plugin deactivate wpconsent-cookies-banner-privacy-suite` reverts the site to `BHP_Consent`'s pre-existing default-deny behavior automatically (fails closed, not open) — no other cleanup needed.
- `inc/class-bhp-wpconsent-bridge.php`: no-ops safely if WPConsent is deactivated (checks `function_exists('wpconsent')`).

## Isolated production release plan (NOT executed — planning only)

**Exact source of truth**: local commits `91bee97` (fix), `bbf0413` (feat), `b6bf20d` (docs) on `feature/production-integration-1.17.1` — not yet pushed to origin (credential-prompt block, see `KNOWN_ISSUES.md`). Andrew can push these via GitHub Desktop before or independently of any production deploy; the deploy itself uses the snapshot method below regardless of push status.

**Scope of the eventual production change** (once Andrew explicitly approves, current-turn):
1. Deploy 3 files via the established snapshot-based method (production's own live files, only these 3 patched — same method used for the header fix), taken from commits `91bee97`/`bbf0413`: `functions.php` (1-line `require_once` addition), `inc/class-bhp-consent.php` (the override-precedence fix), `inc/class-bhp-wpconsent-bridge.php` (new file). `tests/test-consent-precedence.php` is a dev-only regression test, not part of the production file set.
2. Install WPConsent Free on **production** from WordPress.org (same install, no account).
3. Configure the same `wpconsent_settings` (banner + floating button enabled, matching button labels, `google_consent_mode` disabled).
4. **Do not** set `bhp_consent_decision_approved` as part of this deploy — that is a separate, explicit business decision per `BHP_Analytics_Config`'s own design ("approving the business gate is NOT the same as a visitor having granted consent"). Deploying the banner does not itself authorize GTM to go live in production; that gate stays closed until Andrew separately approves it.
5. Post-deploy verification: `wp eval 'echo "ok";'`, banner renders on a real logged-out production page load, Accept/Reject write `bhp_consent_state` correctly, `wp sg purge`.
6. Full production QA repeat of the staging matrix above (including the now-completed tablet/mobile/keyboard passes), logged-out, no real order placed.
7. Cache: `wp sg purge` after the file deploy and after the WPConsent plugin install/config — both are customer-facing changes per `.claude/rules/production-safety.md`.

**Explicitly out of scope for that future deploy**: approving `bhp_consent_decision_approved`, publishing/submitting the GTM container, any change to Mailchimp/[PARENT_COUPON_CODE_SUPERSEDED]/WooCommerce pricing/payments/other funnels, header/CSS/other theme changes.

**Gate before that deploy can happen:** Andrew's explicit, current-turn approval — this document does not constitute that approval.
