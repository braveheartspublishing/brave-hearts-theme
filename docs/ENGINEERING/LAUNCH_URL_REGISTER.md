# Launch URL Register (2026-07-17)

All URLs below are public destinations (no private Mailchimp identifiers).
Real coupon codes, campaign IDs, and automation IDs stay in the private,
gitignored `docs-private/MAILCHIMP_INTERNAL_REFERENCE.md` as usual — none
of those appear here.

## Landing pages

| Audience | Destination | Full production URL | Verified loading | Used by page | Used by Email 1 | Notes |
|---|---|---|---|---|---|---|
| Parent | Landing page | `https://braveheartspublishing.com/reluctant-reader-adventure-kit/` | Yes (live) | — | — | Live on production. |
| Educator | Landing page | `https://braveheartspublishing.com/educators-adventure-learning-toolkit/` | **Not yet — awaiting theme deploy** | — | — | Currently only exists on staging (`https://staging2.braveheartspublishing.com/educators-adventure-learning-toolkit/`, verified live there). Same slug expected to resolve on production once deployed. |
| Community Organization | Landing page | `https://braveheartspublishing.com/organizations-community-reading-kit/` | **Not yet — awaiting theme deploy** | — | — | Staging equivalent verified live at `https://staging2.braveheartspublishing.com/organizations-community-reading-kit/`, signup form fully operational (real-signup tested 2026-07-17, see PDFs section). |
| Gift Buyer | Landing page | `https://braveheartspublishing.com/gift-buyers-guide/` | **Not yet — awaiting theme deploy** | — | — | Staging equivalent verified live at `https://staging2.braveheartspublishing.com/gift-buyers-guide/`, signup form fully operational (real-signup tested 2026-07-17, see PDFs section). |
| Retailer (deferred, not this batch) | Landing page | `https://staging2.braveheartspublishing.com/retailers-wholesale-guide/` | Staging only | — | — | Post-Launch — Awaiting IngramSpark Availability and Wholesale Readiness. Not deployed to production this cycle. |

## PDFs

| PDF | Full production URL | Verified |
|---|---|---|
| Reluctant Reader Adventure Kit | `https://braveheartspublishing.com/wp-content/uploads/2026/07/Reluctant-Reader-Adventure-Kit-1.pdf` | Yes — opened directly, title page confirmed, connected to the Parent Lead Magnet setting live on production. |
| Adventure Learning Toolkit | `https://braveheartspublishing.com/wp-content/uploads/2026/07/Educator-PDF.pdf` | Yes — opened directly, title page confirmed ("The Adventure Learning Toolkit", approved trust wording present). **Setting field to connect it does not exist on production yet — see Phase 2 note below.** |
| Community Reading Kit | `https://braveheartspublishing.com/wp-content/uploads/2026/07/Community-Resource-Page.pdf` | Yes — opened directly, title page confirmed ("The Community Reading Kit"). **Setting field pending deploy, same as above.** |
| Ultimate Children's Book Gift Guide | `https://braveheartspublishing.com/wp-content/uploads/2026/07/Ultimate-Gift.pdf` | Yes — opened directly, title page confirmed ("The Ultimate Children's Book Gift Guide", "Big Places. Brave Hearts." tagline present). **Setting field pending deploy, same as above.** |

**Minor observed defect (not blocking):** the Educator, Community Reading Kit,
and Ultimate Gift PDFs all show a generic/leftover document-title metadata
property in the browser tab (e.g. "Bravehearts Publishing brand identity",
"Braveheartsplublishing branding" — note the typo in the latter) rather than
the actual resource title. This is cosmetic (affects browser tab title /
bookmark name only, not the visible page content) — flagging for Andrew's
source file if he wants it cleaned up in a future export; does not block
launch.

**Update 2026-07-17 (later same day) — staging forms restored, root cause
confirmed environment-config drift, not a code defect.** Page 1 of both
PDFs (production Media Library attachments #392 and #389) was rendered to
`assets/images/handoff/gift-guide-cover.webp` and
`community-reading-kit-cover.webp`, replacing the "cover in progress"
placeholders — this part shipped correctly. Separately, staging's
`bhp_lead_magnet_pdfs` option was missing the `gift_guide` and
`community_reading_kit` keys entirely (confirmed via `wp option get`),
which left both pages' signup forms in the disabled "Coming Soon" state
even with the real covers showing. **Root cause:** an earlier deployment
pass (PROD-3, see `docs/PROJECT_STATE.md`) populated these keys on
**production only**; staging's option was never touched in that pass.
Production's option already had correct, live-reachable URLs for both
keys the entire time — confirmed again fresh via `wp option get` on
2026-07-17: `gift_guide` →
`https://braveheartspublishing.com/wp-content/uploads/2026/07/Ultimate-Gift.pdf`,
`community_reading_kit` →
`https://braveheartspublishing.com/wp-content/uploads/2026/07/Community-Resource-Page.pdf`
(both HTTP-reachable, real PDFs, no staging URL leakage). Staging was
brought to parity by uploading the same two PDFs to staging's own Media
Library (new attachments #598, #599, matching the site's existing
per-environment-hosting pattern) and patching only the two missing keys
via `wp option patch insert` (all 4 pre-existing keys — including the
empty `mariana_parent`, left as-is — were preserved untouched). Both
forms were then verified fully live on staging with real signups
(`andrew+gift-final-<ts>@` and `andrew+organization-final-<ts>@`):
correct-only Mailchimp tags applied, correct-only journey started (Gift
Buyer / Organization Acquisition Funnels — each showed exactly 2 total
started/2 in progress for 2026-07-17, matching the day's known test
contacts, no legacy-journey cross-entry), Email 1 sent and opened for
both. **Production's theme is still on 1.19.46 and does not yet have
the landing-page code at all** (see Phase 2 status table below) — the
lead-magnet option values are unrelated database configuration and were
already correct on production independent of that pending theme
deploy.

## Shared commercial destinations

| Destination | Full production URL | Verified |
|---|---|---|
| Complete Collection | `https://braveheartspublishing.com/complete-collection/` | Yes (confirmed live in an earlier verification pass this project). |
| Books / Shop | `https://braveheartspublishing.com/books/` | Yes. |
| Contact | `https://braveheartspublishing.com/contact/` | Yes. |
| Parent thank-you page | `https://braveheartspublishing.com/adventure-kit-thank-you/` | Referenced in code (`bhp_get_signup_success_redirect_pages()`), not re-verified this pass. |

No dedicated thank-you/confirmation page exists yet for Educator, Gift
Buyer, or Organization — all three currently show the generic sitewide
signup-success message inline rather than redirecting. This is accurate,
not a defect; adding dedicated thank-you pages is optional future work.

## Logo

**Not applied anywhere.** `brave-hearts-stacked.png` (`https://braveheartspublishing.com/wp-content/uploads/2026/07/brave-hearts-stacked.png`)
was rejected for production use per Andrew's explicit 2026-07-17 decision —
opaque light-gray background, 880×640px canvas/aspect ratio unsuitable for
header/footer/Mailchimp. Current live logo, footer logo, favicon, and Site
Identity setting are all untouched. Tracked as a separate manual asset task
pending an approved transparent, tightly-cropped re-export.

## Sitewide audience quiz routing (2026-07-17 batch — staging only, not yet on production)

Canonical quiz page: `/find-your-adventure/` (staging Page ID 597, template
`page-find-your-adventure.php`). Renders the existing
`template-parts/quiz/audience-quiz.php` component (shortcode
`[bhp_audience_quiz]` also still works) — no duplicate quiz logic.

**Revised 2026-07-17 (modal follow-up):** the sitewide entry point was
originally a plain link to the canonical page (superseded). Andrew's
follow-up requirement was that the quiz itself, not just a link to it,
needed to be reachable without navigating away. `template-parts/components/
quiz-entry-cta.php` (rendered from `footer.php`) now renders a `<button>`
launcher plus a hidden modal/dialog containing the same
`template-parts/quiz/audience-quiz.php` component. Clicking the launcher
opens the quiz in place via a new `assets/js/quiz-modal.js` (generic
focus-trap/dialog behavior only — no quiz logic duplicated) and
`assets/css/quiz-modal.css`. The canonical `/find-your-adventure/` page and
a small "Open the full quiz page" link inside the modal remain as a
direct-link/no-JS fallback.

Quiz route mapping (Retailer intentionally excluded, per standing decision):

| Quiz answer | Destination | Lead magnet | Trigger tag | Journey |
|---|---|---|---|---|
| Parent/family | `/reluctant-reader-adventure-kit/` | Reluctant Reader Adventure Kit | Reluctant Reader Adventure Kit | Parent – Acquisition Funnel |
| Educator | `/educators-adventure-learning-toolkit/` | Adventure Learning Toolkit / Educator PDF | Adventure Learning Toolkit | Educator – Acquisition Funnel |
| Gift buyer | `/gift-buyers-guide/` | The Ultimate Children's Book Gift Guide | Meaningful Gift Guide | Gift Buyer – Acquisition Funnel |
| Community organization | `/organizations-community-reading-kit/` | Community Reading Kit | Community Reading Kit | Organization – Acquisition Funnel |

UTM applied to every quiz-driven outbound link:
`utm_source=quiz&utm_medium=onsite&utm_campaign=audience_quiz&utm_content=<entry_location>`.
`entry_location` is now computed per page type by
`bhp_get_quiz_entry_location()` in `functions.php` rather than a flat
`footer` value, since the launcher itself always renders in the same DOM
position (the footer) regardless of the page it's on: `homepage`,
`quiz_page`, `blog_archive`, `blog_post`, `shop`, `product`, `about`,
`information_page` (fallback for any other eligible page, e.g. Contact).

Sitewide launcher appears on: blog archive/posts, shop archive, individual
product pages, About/informational pages, other ordinary content pages
(and `/teachers/`, which was never in the exclusion list — coexists there
with the separate teacher popup with no conflict). Excluded (verified live
on staging): homepage (already embeds the quiz), the canonical quiz page
itself, cart, checkout, all 4 audience landing pages, the Parent thank-you
page, privacy policy, and (by the same existing
`bhp_should_show_any_popup()` gate) account/terms/admin pages.

### ⛔ SUPERSEDED TIMING — read this before the 2026-07-17 block below (theme 1.19.167, 2026-08-04)

**The auto-open block immediately below is preserved verbatim as the original
design record. Its two numbers and its "whichever happens first" framing are
SUPERSEDED by this release and must not be acted on.** Still current from it:
the once-per-session `sessionStorage` key, the `data-bhp-quiz-autoopen`
attribute, the overlay deferral, and the manual launcher's independence.

> ⚠ **A SEPARATE, OLDER STALENESS IN THAT BLOCK, found while making this
> change and NOT introduced by it (`CYCLE143-LD-151`).** The block states
> that *"`/teachers/` is now excluded from automatic opening specifically"*.
> **That has not been true since 2026-07-19.** `bhp_should_autoopen_quiz()`
> in `functions.php` carries Andrew's explicit 2026-07-19 decision that the
> quiz is the only popup on the site and must therefore be eligible on every
> page a popup is allowed on **including `/teachers/`**, knowingly superseding
> the earlier carve-outs. **Verified live 2026-08-04 over HTTP on BOTH
> environments:** `/teachers/` renders `data-bhp-quiz-autoopen="true"` on
> production 1.19.166 *and* on staging 1.19.167, so this is pre-existing and
> unrelated to the dwell floor. `/cart/` correctly renders no launcher and no
> auto-open attribute at all. **Recorded, not resolved** — correcting the
> 2026-07-17 block itself is a documentation decision for the Chief of Staff,
> not something this release changes.

**Owner ruling, Andrew Signore, 2026-08-04:** the auto-open *"needs more time
for people to peruse the site"* — *"20 seconds please"*. ⚠ **Relayed to the
implementing session by `chief-of-staff` and witnessed by the main session;
not witnessed first-hand by the implementer.**

| | Was (through 1.19.166) | Is (1.19.167) |
|---|---|---|
| Timer | `AUTO_OPEN_DELAY_MS = 8000` (the block below still says 9000 — stale since an earlier build) | **`AUTO_OPEN_DELAY_MS = 20000`** |
| Scroll threshold | `SCROLL_THRESHOLD = 0.40` | **`SCROLL_THRESHOLD = 0.60`** |
| How they interact | Two triggers **racing**; whichever fired first opened the modal | A **hard minimum dwell floor of 20000ms**. No automatic open by any path before it |
| Crossing the threshold early | Opened immediately | **Records intent, opens when the floor is reached** |
| The arm-time one-shot evaluation | Could open a short page **near-instantly** at load | Runs through the same gate — can only record intent |
| `open_reason` / `cancel_reason` | `scroll_40` / `opened_scroll_40` | **`scroll_60_after_floor`** / `opened_scroll_60_after_floor` |
| Manual launcher click | Immediate | **Immediate — unchanged.** The floor governs automatic opens only |

**Also new, additive only:** `quiz_auto_trigger_armed` now carries
`dwell_floor_ms` and `scroll_threshold`, and one observability-only event
`quiz_auto_trigger_scroll_intent` fires when the threshold is crossed while
the floor is still holding the open back. No event was removed or renamed
apart from the two reason strings above, which were renamed because the old
names would have described the new behaviour dishonestly.

**Enforcement is structural, not by convention:** `dwellFloorReached` is
written `true` in exactly one place (the floor timer's own callback), and
`evaluateScrollTrigger()` returns early whenever it is false — so the gate,
not each call site, is what makes "never before 20 seconds" true. Guarded by
`tests/test-quiz-autoopen-timing.php`, which runs against the **deployed**
file via `wp eval-file`.

**Honest limit of that suite:** it is a source-level assertion suite, not a
browser. It proves the deployed file still has the shape that enforces the
floor; it cannot observe a real 20-second wait. Runtime timing evidence is a
separate, browser-based check.

### Auto-open trigger (2026-07-17 follow-up — staging only, not yet on production)

Andrew corrected the modal follow-up above: it was click-to-open only and
didn't satisfy the intended auto-open behavior. The same shared modal now
also opens itself, once per browser session, on whichever happens first:

- **9000ms** after the page becomes ready ("9–10 seconds" per spec, 9000ms
  chosen as the canonical value), or
- **40% scroll depth**, computed as
  `(scrollY + viewportHeight) / documentHeight >= 0.40`.

Both triggers are armed together; whichever fires first cancels the other.
A `sessionStorage` key (`bhp_quiz_auto_shown` — deliberately session-scoped,
not a cookie, so a fresh browser session can auto-open again) is set the
moment any open occurs (manual or automatic), so the modal never
auto-opens more than once per session and closing an auto-opened modal
never causes it to reopen automatically. Manually clicking the launcher
before either trigger fires cancels both immediately. Overlay conflicts
(this modal already open, the teacher/parent popup, the side-cart drawer,
or WPConsent actively displaying) defer the open with a bounded retry (5
attempts, 1s apart) rather than polling indefinitely, then give up
silently.

**`/teachers/` is now excluded from *automatic* opening specifically**
(new `bhp_should_autoopen_quiz()` in `functions.php`, layered on top of
`bhp_should_show_quiz_cta()`), because that page already runs its own
separate automatic overlay (the teacher popup) and two automatic overlays
firing on one page would conflict. The manual launcher itself is
unaffected and still renders and works on `/teachers/` — only the
timer/scroll auto-open trigger is suppressed there
(`data-bhp-quiz-autoopen="false"` on that page's modal markup, vs.
`"true"` on every other eligible page).

New analytics events (same `window.dataLayer` convention, no new
platform): `quiz_auto_trigger_armed` (trigger armed on page load),
`quiz_auto_trigger_cancelled` (`cancel_reason`: `opened_manual` /
`opened_timer` / `opened_scroll_40`). `quiz_modal_opened` gained an
`open_reason` field (`manual` / `timer` / `scroll_40`).

Staging QA verified live: timer trigger (~9s, single fire, no reopen),
manual-first cancellation, session suppression across in-page navigation,
and all exclusions (homepage, `/teachers/` auto-open specifically, cart,
checkout, Parent landing page, thank-you page, privacy policy). The
scroll-depth trigger could not be mechanically exercised end-to-end in
this session's browser-automation tool (all tabs report
`document.visibilityState: "hidden"`, which suspends
`requestAnimationFrame`) — verified correct via source review and the
`quiz_auto_trigger_armed` event instead; flagged for a real-browser
spot-check once live. See `docs/CHANGELOG.md` 2026-07-17 "Quiz modal now
auto-opens on timer/scroll trigger" entry for full detail.

Analytics events (fire through the existing `nav.js` generic
`data-bhp-event` / `data-bhp-impression-event` dispatcher, same consent
gate/dataLayer-presence check as all other theme events):
`quiz_cta_viewed` (launcher viewed), `quiz_cta_clicked` (launcher clicked).
New in the modal follow-up, pushed directly by `quiz-modal.js` using the
same dataLayer convention: `quiz_modal_opened`, `quiz_modal_closed`
(`close_reason`: `escape` / `backdrop` / `close_button`). Existing quiz
events (`quiz_viewed` through `quiz_destination_click`) already carried an
`entry_location` field; `quiz_restarted` was added to the shared engine's
Restart handler (previously fired no event at all).

Modal state: closing (Escape, backdrop, close button) hides the modal but
does not reset quiz progress, since the underlying markup is only
hidden/shown, never re-rendered — re-opening resumes where the visitor
left off. Only the quiz's own Restart control clears answers; navigating
to a different page resets everything on the next load.

Not yet done: production deployment (staging QA complete, awaiting
Andrew's explicit approval), Mailchimp email-content changes (explicitly
out of scope for this batch per Andrew's instruction).

## Phase 2 status: PDF-to-settings connection

**Corrected 2026-07-17 (later same day):** the table below previously said
the Educator/Community/Gift Buyer settings fields "don't exist" on
production. That conflated the WP **admin settings-page UI** (which does
require the pending theme deploy to render, since
`inc/lead-magnet-settings.php` ships in the theme) with the underlying
`bhp_lead_magnet_pdfs` **database option**, which is independent of theme
code and was already populated correctly for all three on production
(verified fresh via `wp option get` 2026-07-17 — see the "root cause"
note in the PDFs section above). The landing pages themselves still
return 404 on production until the theme ZIP ships, so nothing is
publicly reachable yet — but no production database update will be
needed when that deploy happens.

| Audience | DB option value correct on production? | Settings-page UI live on production? | Landing page live on production? |
|---|---|---|---|
| Parent | Yes | Yes | Yes |
| Educator | Yes (`teacher_toolkit`) | No — awaiting theme deploy | No — awaiting theme deploy |
| Community Organization | Yes (`community_reading_kit`) | No — awaiting theme deploy | No — awaiting theme deploy |
| Gift Buyer | Yes (`gift_guide`) | No — awaiting theme deploy | No — awaiting theme deploy |

The settings code for all 3 fields already exists, complete and correct,
in the repository (`inc/lead-magnet-settings.php` defines `teacher_toolkit`,
`gift_guide`, `community_reading_kit`, and `bookstore_wholesale_guide`
alongside the 3 fields currently live on production) and is packaged in
the deployment ZIP (see `docs/ENGINEERING/LANDING_PAGE_LAUNCH_MANIFEST.md`).
The moment the ZIP is deployed, the Settings → Lead Magnets screen will
simply *display* the values that are already saved — no data entry or
database update is required at deploy time for these four keys.
