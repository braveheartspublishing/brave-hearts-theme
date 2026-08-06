# Quiz Inline Email-Capture — Readiness Audit & Implementation Design

**Date: 2026-07-30. Read-only audit. No code, Mailchimp, staging or production
change was made.** Baseline: theme 1.19.100 + bundle plugin 1.8.7 on both
staging and production.

**Verdict: NOT READY to implement all routes. Ready to implement for ZERO
routes until the Mailchimp journey states are re-verified by a logged-in
human.** See "Verdict" at the end for the precise prerequisites.

---

## 1. Readiness matrix — four free-resource routes

| Check | Parent | Educator | Gift Buyer | Organization |
|---|---|---|---|---|
| Lead-magnet registry key | `reluctant_reader_adventure_kit` | `teacher_adventure_toolkit` | `meaningful_gift_guide` | `community_reading_kit` |
| PDF option key | `adventure_kit_parent` | `teacher_toolkit` | `gift_guide` | `community_reading_kit` |
| PDF exists on prod disk | ✅ 11,229,717 B | ✅ 5,252,960 B | ✅ 5,174,985 B | ✅ 5,519,748 B |
| Real `%PDF` header | ✅ | ✅ | ✅ | ✅ |
| WP attachment record | ✅ 347/391 | ✅ 390 | ✅ 392 | ✅ 389 |
| Approved final asset | ✅ doc-confirmed | ✅ Andrew-approved v1.0 | ✅ doc-confirmed | ✅ doc-confirmed |
| Destination page published | ✅ 348 | ✅ 393 | ✅ 394 | ✅ 395 |
| Mailchimp tags wired in code | ✅ | ✅ | ✅ | ✅ |
| In redirect whitelist | ❌ | ❌ | ❌ | ❌ |
| **Mailchimp journey ACTIVE** | ⚠️ **UNVERIFIED** | ⚠️ **UNVERIFIED** | ⚠️ **UNVERIFIED** | ⚠️ **UNVERIFIED** |
| Email 1 download link real | ✅ (as of 07-16) | ✅ (as of 07-16) | ❌ **placeholder** (07-16) | ❌ **placeholder** (07-16) |
| Later-email defects | none recorded | ⚠️ Email 2 contradicts Email 1 | unknown | unknown |

Canonical URLs (from `LAUNCH_URL_REGISTER.md`, values re-confirmed live in
`bhp_lead_magnet_pdfs` on production 2026-07-30):

- Parent — `/wp-content/uploads/2026/07/Reluctant-Reader-Adventure-Kit-1.pdf`
- Educator — `/wp-content/uploads/2026/07/Educator-PDF.pdf`
- Gift — `/wp-content/uploads/2026/07/Ultimate-Gift.pdf`
- Organization — `/wp-content/uploads/2026/07/Community-Resource-Page.pdf`

**Public reachability could not be proven by HTTP this session.** SiteGround
edge security answers `fetch()` with HTTP 202 + a ~230-byte HTML challenge
for all five PDFs, exactly as `START_HERE.md` warns for `curl`. Existence,
size and `%PDF` header were verified on disk over SSH instead. The register
records each as previously opened directly in a real browser with the
correct title page. **Treat "publicly reachable" as doc-confirmed, not
re-verified today.**

## 2. Mailchimp journey state — the blocking unknown

**Not verifiable in this session.** `us6.admin.mailchimp.com` redirected to
the login screen; I did not authenticate (entering credentials is
prohibited). The Mailchimp MCP connector explicitly excludes automations —
journeys are "mailchimp_only", app-only, not exposed to the integration.

The most recent verified record is `MAILCHIMP_STATUS.md`, **last verified
2026-07-16 — two weeks stale**. As of that date:

| Automation | State |
|---|---|
| Reluctant Reader Adventure Kit (id 85, legacy Parent Email 1+2) | **Active** |
| Coupon Flow (id 86) | **Paused**, deliberately |
| Global - Tag Purchasers (id 88) | **Active** |
| Parent - Acquisition Funnel (id 89) | **Draft** |
| Educators - Acquisition Funnel (id 90) | **Draft** |
| Gift Buyer - Acquisition Funnel | **Draft**, placeholder guide URL |
| Organization - Acquisition Funnel (id 93) | **Draft**, placeholder guide URL |
| MT Lead Magnet (teacher) | **Active** |

**Why this blocks everything:** if a journey is still Draft, a tagged
subscriber receives *nothing*. The quiz would promise "your free resource is
on the way" and no email would ever arrive. That is a false promise to a
real visitor and is not acceptable under the company's no-fabrication rule.

Trigger tags the journeys listen for (`LAUNCH_URL_REGISTER.md` route table,
matching `functions.php:1354-1400`):

| Route | Trigger tag | Journey |
|---|---|---|
| Parent | `Reluctant Reader Adventure Kit` | Parent – Acquisition Funnel |
| Educator | `Adventure Learning Toolkit` | Educator – Acquisition Funnel |
| Gift | `Meaningful Gift Guide` | Gift Buyer – Acquisition Funnel |
| Organization | `Community Reading Kit` | Organization – Acquisition Funnel |

**Note on the brief's tag names.** The brief lists the Mailchimp tags as
`adventure_kit_parent` / `teacher_toolkit` / `gift_guide` /
`community_reading_kit`. Those are the **PDF storage keys** inside the
`bhp_lead_magnet_pdfs` option, not Mailchimp tags. The real tags are the
Title Case strings above, each accompanied by an `Audience: X` and a
`Source: Y` tag. The brief's **lead-magnet keys are correct**.

### Subscriber-state behaviour — NOT verified, must be confirmed

None of the four states in the brief can be answered from code or repo docs.
`add_list_member(..., true)` uses MC4WP's update-existing flag with
`status => 'subscribed'`, and tags are applied by a separate
`update_list_member_tags` call, so on the WordPress side:

- **Brand-new subscriber** — created, tagged. Journey entry depends on the
  journey being Active. Expected to enter.
- **Existing, untagged** — updated, tag added. Tag-add is the trigger event,
  so entry is expected. Not verified live.
- **Existing, already tagged** — re-applying an existing tag may not fire a
  fresh trigger, so **the resource may not resend**. This is the highest-risk
  case for a quiz, because a repeat visitor can easily retake it. **Unverified
  — must be confirmed before launch.**
- **Unsubscribed / cleaned** — `status => 'subscribed'` on a previously
  unsubscribed address is rejected by the Mailchimp API for compliance
  reasons; a cleaned address cannot be resubscribed programmatically. This
  would surface as a caught `Throwable` → generic error. **Unverified.**
- **Re-entry / resend** — journey re-entry settings were never confirmed live
  (viewing them requires pausing a live automation, which was ruled out).

## 3. Partnership route — separate assessment

`template-parts/quiz/audience-quiz.php:178` — the organization "reading
program, event, or partnership" answer already has `'result_resource' => ''`
and its own destination override to
`/organizations-community-reading-kit/#contact`.

**This is correct as-is and must not be changed.** It promises no
downloadable resource, so it must not render a signup form. Verified live on
production this session: that route's result reports resource `(none)` and
its CTA reads "Explore Group Orders & Partnerships".

Of the 12 Q2 answers (3 per route × 4 routes), **11 promise a free resource
and 1 does not.** The implementation must key the form off a non-empty
`result_resource`, not off the route.

## 4. Blockers and unresolved risks

**B1 — Mailchimp journey states unverified (BLOCKS ALL FOUR ROUTES).**
Requires a logged-in human to read and report Active/Draft for the four
Acquisition Funnels.

**B2 — Gift and Organization Email 1 carried placeholder download URLs**
(`[INSERT FINAL ... URL BEFORE ACTIVATION]`) as of 2026-07-16. If still
present, those two routes deliver a broken email even once Active.

**B3 — Educator Email 2 contradicts Email 1.** Email 1 says the toolkit is
ready and links it; Email 2 still opens "We're still putting the finishing
touches on…" and references a "reading log" that is not in the real toolkit.
Flagged 2026-07-16, awaiting Andrew/ChatGPT copy decision. Not blocking
Email 1 delivery, but it is a visible quality defect on a live journey.

**B4 — PII in URLs on the existing error path.**
`bhp_mailchimp_signup_redirect()` (`inc/mailchimp.php:248-253`) puts
`bhp_email` and `bhp_name` into the query string to repopulate a form after a
validation error. That reaches server access logs, `Referer` headers and
browser history. It directly violates the brief's "never put names or email
addresses in URLs". **The quiz path must not reuse this**, and the existing
standalone-form behaviour should be raised with Andrew separately (it is
pre-existing and out of scope to change here).

**B5 — The lead event log stores the email address.**
`inc/class-bhp-lead-event-log.php:40,137` writes `_bhp_lead_email` post meta
by design ("PII posture: the email address IS stored"). This is a
first-party WordPress record rather than a leaky log, but it is in direct
tension with the brief's "never … in logs". **Needs Andrew's explicit
decision:** keep as-is for the quiz, or suppress the email for quiz-sourced
events.

**B6 — No rate limiting exists anywhere in the theme.** A repo-wide search
found none. The brief says to "preserve … rate-limit protections" — there
are none to preserve. A public JSON endpoint without one is materially more
abusable than the current form-post path.

**B7 — None of the four funnel destinations are whitelisted.**
`bhp_get_signup_success_redirect_pages()` currently resolves only
`mariana_guide_thank_you`, `adventure_kit_thank_you`, `gift_guide_thank_you`
(all published, verified live). The four quiz destinations must be added.

**B8 — No dedicated thank-you page for Educator, Gift Buyer or Organization**
(`LAUNCH_URL_REGISTER.md`). The brief redirects to the funnel pages
themselves, which is fine, but those pages currently render their own signup
form — which the brief explicitly wants to avoid showing again post-redirect.

**B9 — Documentation is stale and partly contradictory.** `START_HERE.md`
still says production is 1.19.86/1.19.91; it is 1.19.100.
`AUDIENCE_IMPLEMENTATION_MATRIX.md` still says the `gift_guide` and
`community_reading_kit` keys are "Not set" — they are set on production. The
repo must be reconciled before it is trusted for launch decisions.

**R1 — Consent scope.** Existing forms show "Adventure Club updates and
resource news. Unsubscribe anytime." and subscribe with
`status => 'subscribed'` (single opt-in). Whether a quiz-embedded capture
needs different wording is a business/legal call, not a code call.

## 5. Recommended shared architecture

The service the brief asks for **already exists** — `inc/mailchimp.php`
`bhp_handle_mailchimp_signup()`. It already does nonce → honeypot
(`bhp_website`) → email validation → optional-name → readiness check →
merge fields → `add_list_member` → `update_list_member_tags` → success/failure
action hooks. It already resolves redirects from a **server-side whitelist
keyed by short string**, never a URL from the browser — exactly the pattern
the brief specifies.

**Do not duplicate it. Refactor the middle out and add a second front door.**

1. **Extract** the body of `bhp_handle_mailchimp_signup()` into a pure
   function, e.g. `bhp_process_signup(array $input): array`, returning a
   structured result `['ok' => bool, 'code' => string, 'redirect' => string]`.
   No `wp_redirect`, no `exit`, no superglobals inside it.
2. **Keep** `bhp_handle_mailchimp_signup()` as a thin `admin_post` wrapper
   that calls it and then redirects exactly as today. Every existing
   standalone form keeps byte-identical behaviour.
3. **Add** `wp_ajax_nopriv_bhp_quiz_signup` / `wp_ajax_bhp_quiz_signup` as a
   narrow same-origin JSON endpoint that calls the same function and returns
   JSON. It must **not** call `bhp_mailchimp_signup_redirect()` — that is the
   function that leaks PII into URLs (B4).
4. **Server-side result whitelist.** The browser sends only a quiz result key
   (e.g. `parent_reluctant`). The server maps it to audience, lead-magnet key,
   tags, source and redirect key. Reject anything unrecognised. The browser
   never supplies a URL, a tag, or a lead-magnet key.
5. **Extend** `bhp_get_signup_success_redirect_pages()` via its existing
   filter with the four funnel destinations. Resolution stays
   `get_page_by_path()` → `get_permalink()` → `wp_validate_redirect()`.
6. **Add rate limiting** in the shared function — a transient keyed by a
   hashed IP + a short window. Must be added, not "preserved" (B6).
7. **Redirect only after** `update_list_member_tags` returns without throwing.
   Do not wait on delivery — the brief is right that delivery is async.
8. **Failures stay in the modal.** JSON error → inline `role="alert"` message,
   field values preserved in the live DOM (never in a URL, never in storage).

### Post-redirect acknowledgement

Set a short-lived, HttpOnly, **contentless** cookie server-side immediately
before redirecting — e.g. `bhp_signup_ack=<result_key>`, 5-minute expiry,
`SameSite=Lax`, no name, no email, no identifier. The destination page reads
it, renders "Your free resource is on the way. Check your inbox.", suppresses
its own signup form, and clears the cookie. A cookie is preferable to a query
parameter (no PII risk, no shareable URL state, not in access logs) and to
`localStorage` (the brief forbids browser storage of personal data; a bare
result key is not personal, but a cookie is simpler to expire server-side).

### Analytics — minimum additions

Existing quiz events are already PII-free: `quiz_viewed`, `quiz_started`,
`quiz_q1_answer`, `quiz_q2_answer`, `quiz_completed`, `quiz_destination_click`,
`quiz_abandoned`, `quiz_restarted`, `homepage_quiz_started`.

`quiz_completed` already covers **result displayed** — do not add a duplicate.
Add five, all carrying only `quiz_audience` / `quiz_intent` / `entry_location`:

| Need | Event |
|---|---|
| Result displayed | *(reuse `quiz_completed`)* |
| Form started | `quiz_signup_started` (first focus/input) |
| Submission attempted | `quiz_signup_submitted` |
| Submission succeeded | `quiz_signup_success` |
| Submission failed | `quiz_signup_failed` + `error_code` (generic code only) |
| Redirect completed | `quiz_signup_landed` (fired on the destination page from the ack cookie) |

No email, no name, no hashed email in any payload.

## 6. Files expected to change (implementation, later)

| File | Change |
|---|---|
| `inc/mailchimp.php` | Extract `bhp_process_signup()`; keep `admin_post` wrapper identical; add JSON endpoint; add rate limiting |
| `functions.php` | Register the quiz result → audience/tag/redirect whitelist; extend `bhp_signup_success_redirect_pages`; ack-cookie helper |
| `template-parts/quiz/audience-quiz.php` | Render the inline form on results where `result_resource` is non-empty; leave the partnership answer untouched |
| `assets/js/audience-quiz.js` | Form submit → `fetch` → success/error handling → redirect; new analytics events; duplicate-submit guard |
| `assets/css/audience-quiz.css` | Inline form + error styling inside the modal |
| `page-audience-*.php` (4) | Read the ack cookie; render the acknowledgement; suppress the page's own form in that state |
| `style.css` | Version bump |

`inc/class-bhp-lead-event-log.php` changes **only** if Andrew decides to
suppress email storage for quiz events (B5).

**Must not change:** `plugins/brave-hearts-bundle-pricing/**`, any shipping,
pricing, coupon or Bookvault logic, Mariana media, the partnership result, the
existing standalone forms' behaviour, the popup funnels' isolation.

## 7. Accessibility, privacy, security, analytics, regression requirements

**Accessibility** — form inside the existing focus trap, with the new fields
in the tab order; `<label>` for both inputs (first name visibly marked
optional, email `required` + `aria-required`); errors in a
`role="alert"` region referenced by `aria-describedby`; focus moves to the
error on failure; submit button gets `aria-busy` and stays ≥44×44; the
success state announced via `role="status"`; contrast ≥4.5:1; reduced-motion
respected.

**Privacy** — no name/email in URLs, analytics, `localStorage`,
`sessionStorage` or client logs. Ack cookie contains a result key only.
Consent line reused verbatim: "Adventure Club updates and resource news.
Unsubscribe anytime." B4 and B5 resolved before launch.

**Security** — per-form nonce (`bhp_mailchimp_signup_<form_id>`); honeypot
retained; same-origin only; server-side whitelist for every mapped value;
`wp_validate_redirect()` retained; rate limiting added; generic error
messages that never surface Mailchimp API detail; no PII in PHP error logs.

**Regression** — the entire 1.19.100 QA set must still pass: label centring
0.0px, internal scroll reset to 0, 0px page-position drift on all four
dismissals, focus trap both directions, Back/Start over, resume-on-reopen,
UTMs, existing quiz analytics, and the four existing standalone forms
(Parent/Educator/Gift/Organization landing pages, both popups, Adventure Club)
behaving byte-identically.

## 8. Rollback strategy

Theme-only change, so the standing mechanism applies: full-ZIP
`wp theme install --force` of the previous version. Before deploying,
back up to `~/bhp-quiz-capture-backup-<timestamp>/` — theme tarball, an
md5 manifest, and `wp option get bhp_lead_magnet_pdfs`. Rollback is
reinstalling the 1.19.100 ZIP and purging cache; no database migration is
introduced, so rollback is clean. The ack cookie expires on its own.

Mailchimp-side changes (activating journeys) are **not** rolled back by a
theme rollback and must be tracked separately by whoever activates them.

## 9. Verdict

**NOT READY.** Zero routes may be implemented-and-shipped today. The
engineering design above is sound and could be built, but it must not go
live while it would promise a resource the system may not send.

Prerequisites, in order:

1. **A logged-in human reports the live state** of the four Acquisition
   Funnels (Active vs Draft), and — for any Active one — confirms Email 1
   contains the real download link (resolves B1, B2).
2. **Confirm re-entry behaviour** for an already-tagged contact; decide what
   the quiz does when the resource will not resend.
3. **Andrew decides** on B5 (email in the lead event log) and B4 (PII in
   URLs on the existing forms).
4. **Educator Email 2 copy decision** (B3).
5. Reconcile the stale docs in B9.

Once 1–4 are answered, routes whose journey is Active with a real Email 1
link can be implemented; any route still Draft stays out until it is
activated. No placeholder, no substitute download, no "coming soon" form.
