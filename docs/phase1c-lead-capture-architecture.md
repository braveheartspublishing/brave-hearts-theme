# Phase 1C — Lead Capture Architecture

**Read this first if you think Phase 1C needs to be built from scratch —
it does not.** A repository audit on 2026-07-06 found a mature,
production-live lead-capture system already in place: a Mailchimp
integration (via the MC4WP plugin), a reusable form component used by
every acquisition placement on the site, a generalized popup engine
(parent + teacher funnels), a lead-magnet registry, and whitelisted
thank-you pages. This document describes that existing system plus the
specific, genuine gaps this session closed. It does not describe a
parallel system — there is only one.

## 1. Architecture overview

```
Visitor fills out a form
  (footer, adventure_club, inline_blog, teacher_resources,
   parent-popup, teacher-popup, Adventure Kit landing page)
        |
        v
template-parts/acquisition/signup-form.php   <- ONE shared form component
        |  (POST to admin-post.php?action=bhp_mailchimp_signup)
        v
inc/mailchimp.php: bhp_handle_mailchimp_signup()
  - nonce + honeypot + email/name validation
  - MC4WP add_list_member() (upsert) + tag mapping
  - fires bhp_mailchimp_signup_success / bhp_mailchimp_signup_failed
        |
        +--> inc/class-bhp-lead-event-log.php   <- NEW this session
        |      (private CPT, admin-visible, real/test provenance,
        |       first/last-touch attribution attached)
        |
        +--> POST/redirect/GET back to the form (inline feedback)
        |      or to a dedicated thank-you page (Adventure Kit, Mariana guide)
        |
        +--> analytics: signup_error / lead_signup_success /
               adventure_kit_signup (NEW/enriched this session --
               lead_offer/audience/placement params, no PII, refresh-dedup)
```

## 2. What already existed (do not rebuild)

| Piece | File | Notes |
|---|---|---|
| Universal form component | `template-parts/acquisition/signup-form.php` | Handles nonce, honeypot, ARIA, validation-error re-population, provider-unavailable disabled state |
| Signup handler / Mailchimp adapter | `inc/mailchimp.php` | Wraps MC4WP, never the raw Mailchimp API directly; API key lives only in MC4WP's own settings |
| Popup engine | `assets/js/mariana-popup.js` | Config-driven via `data-popup-config`; parent + teacher funnels, mutually exclusive, isolated storage/event prefixes (see `.claude/rules/funnels.md`) |
| Lead-magnet registry | `functions.php` (`bhp_get_lead_magnets()`) | Central list of active + placeholder lead magnets |
| Adventure Kit landing page | `page-reluctant-reader-adventure-kit.php` | Already has benefit-led copy, form, no fabricated claims |
| Whitelisted thank-you pages | `bhp_get_signup_success_redirect_pages()` in `inc/mailchimp.php` | Never an attacker-controlled redirect |
| Audience/tag segmentation | `functions.php`, `bhp_get_mailchimp_signup_tags` filter | Context-aware Mailchimp tags per funnel |

## 3. What this session added (the actual gaps)

1. **A persistent local lead-event log** (`inc/class-bhp-lead-event-log.php`) — previously nothing recorded a local, queryable signup history; reporting was 100% delegated to Mailchimp's UI. See §5.
2. **UTM attribution attached to lead events** — `BHP_UTM_Attribution::current_visitor_attribution()` (new public helper) reused so a lead event carries the same first/last-touch snapshot already attached to WooCommerce orders.
3. **Analytics enrichment + two new events** — `signup_error` and `lead_signup_success` (§6), plus enrichment of the existing `adventure_kit_signup` event with `lead_offer`/`audience`/`placement`/`signup_method`.
4. **Refresh/back-navigation dedup** for the analytics events above (a plain page refresh was previously capable of refiring `adventure_kit_signup`).
5. **A test-provenance convention** (`+bhptest` / `@bhptest.invalid`) so QA signups are never counted as real.

## 4. Data model

Nothing new was added to the **form fields themselves** — `signup-form.php` already collects email, optional first name, `audience_type`, `lead_magnet`, `source_page`, and `context` (used here as "placement"). What's new is what happens to that data after submission:

| Field | Where it lives | Notes |
|---|---|---|
| Lead source / placement | `BHP_Lead_Event_Log::META_CONTEXT` | Same value as the form's `context` param (e.g. `inline_blog`, `parent_popup`) |
| Lead offer | `BHP_Lead_Event_Log::META_LEAD_MAGNET` | Same value as the form's `lead_magnet` param |
| Audience segment | `BHP_Lead_Event_Log::META_AUDIENCE` | Normalized via `bhp_normalize_audience_type()` |
| Source page | `BHP_Lead_Event_Log::META_SOURCE_PAGE` | URL the form was submitted from |
| First/last-touch attribution | `BHP_Lead_Event_Log::META_FIRST_TOUCH` / `META_LAST_TOUCH` | JSON snapshot from `BHP_UTM_Attribution::current_visitor_attribution()` at signup time |
| Consent status | Not separately stored on the lead event | Analytics-consent state governs whether the *analytics events* fire at all (§6); it is not a field of the signup itself, since the signup succeeds/fails independent of analytics consent |
| Signup timestamp | `post_date` | Native WP post field |
| Form version / experiment variant | **Not implemented** | No A/B infrastructure exists yet (`docs/experiment-quiz-vs-popup.md` documents a planned experiment, never built) — if/when that ships, add a `META_VARIANT` field following this same pattern |
| Signup result | `BHP_Lead_Event_Log::META_RESULT` | `success` \| `failed` |
| Provider sync status | Implicit in `result` | MC4WP's `add_list_member(..., true)` upsert means a "success" result IS the sync confirmation; there is no separate async sync step to track |

**Deliberately not stored:** the raw exception message on failure (only a generic exception-class label — the real message could contain MC4WP/Mailchimp account details). See `docs/consent-privacy-decision-record.md` §1 for the full PII posture.

## 5. Administrative visibility

**Tools → Lead Signups** (added by `BHP_Lead_Event_Log::register_admin_page()`, `manage_options` capability required) shows:
- Real vs. test/synthetic counts (success/failed), last 30 days
- Real successful signups broken down by placement
- The 50 most recent events with result, provenance, email (real signups only — this is an internal admin screen, not a public or analytics surface), context, audience, lead magnet, failure reason

This is deliberately not a CRM — no editing, no export, no bulk actions. It exists to answer "is the signup pipeline healthy" and "which placement/lead-magnet is converting," nothing more.

## 6. Analytics events (see `docs/event-dictionary.md` for the authoritative table)

- `adventure_kit_signup` — the dedicated thank-you page's own event, unchanged trigger, now carries `lead_offer`/`audience`/`placement`/`signup_method`.
- `lead_signup_success` — new. Fires only for forms with **no** dedicated thank-you page (footer, adventure_club, inline_blog, teacher_resources) when their inline success message renders. Never fires for a form that already has its own named event, avoiding double-counting.
- `signup_error` — new. Fires for any form's inline error feedback (`invalid`, `missing_name`, `unavailable`, `error`). `error_reason` is always one of those four fixed strings — never raw input or exception text.

All three: no email, no name, gated by `BHP_Analytics_Config::should_render_analytics()` (so they respect the same admin-exclusion and consent gates as every other event on this site), and guarded against refresh/back-navigation duplicate firing via a per-form sessionStorage key.

## 7. Provider adapter guidance (for a future provider, or a second Mailchimp audience)

`inc/mailchimp.php` already IS the adapter layer — it never calls the raw Mailchimp API directly, only MC4WP. If a future session needs to support a second provider:

- Do **not** fork `signup-form.php` or `bhp_handle_mailchimp_signup()`.
- Add provider selection behind the existing `bhp_signup_form_action` / `bhp_get_signup_form_action()` filter contract, or introduce a thin `BHP_Signup_Provider_Interface` (mirroring `BHP_GA4_Provider_Interface`'s null-object pattern already used for the analytics dashboard) that `bhp_handle_mailchimp_signup()` delegates to.
- Whatever provider is added, it must still fire `bhp_mailchimp_signup_success` / `_failed` with the same argument shape, since `BHP_Lead_Event_Log` and the analytics enrichment both depend on those two hooks and would otherwise silently stop working for the new provider.
- No provider credentials belong in this repository under any circumstance — MC4WP's own settings (or an equivalent settings screen for a new provider) is the only place they live.

## 8. Testing

Automated: `tests/test-lead-event-log.php` (wp-cli, run on staging only) — see `docs/analytics-validation.md` for the run command convention (`--url=staging2.braveheartspublishing.com` is required, same gotcha as every other test in this suite).

Manual staging checklist:
- [ ] Submit a real-looking (`+bhptest`) address through each placement; confirm exactly one `bhp_lead_event` post per submission, correct placement/audience/lead_magnet.
- [ ] Confirm a duplicate submission of the same address does not create duplicate Mailchimp subscribers (already MC4WP's job — upsert) and does not fire a duplicate `lead_signup_success`/`adventure_kit_signup` on refresh.
- [ ] Submit an invalid email; confirm `signup_error` fires with `error_reason: invalid` and no lead-event log entry with a `success` result.
- [ ] Confirm the honeypot field, if filled, produces a generic `error` redirect with no Mailchimp API call and no lead-event log success entry.
- [ ] Confirm Tools → Lead Signups shows the test entries under "test," never blended into "real."
- [ ] Confirm no email address appears in any `dataLayer.push()` payload for any of the above (inspect via the staging debug panel).
- [ ] Confirm the admin page requires `manage_options` (log out / use a non-admin account to confirm it 403s or redirects, per normal WP behavior for `add_management_page`).

## 9. Deliberately deferred (not built this session)

- A/B experiment variant tracking (needs its own infrastructure — see `docs/experiment-quiz-vs-popup.md`).
- Additional placements beyond what already exists (footer, adventure_club, inline_blog, teacher_resources, parent-popup, teacher-popup, Adventure Kit landing page) — the existing set already covers every placement type this phase's brief asked for.
- Lead-event retention/deletion policy — a business decision, see `docs/consent-privacy-decision-record.md` §2.
- A second email provider or provider-selection UI — no provider work was authorized or performed this session.
