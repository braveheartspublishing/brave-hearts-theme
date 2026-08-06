# Experiment Specification: Reluctant Reader Popup vs. Quiz Funnel

**Status: specification only. Not launched. No code has been written for
this experiment.** This document exists so the experiment can be built
and reviewed as its own scoped piece of work, not started blind.

## Experiment ID

`exp_popup_vs_quiz_v1` — versioned so a future re-run or variant change
gets its own ID rather than silently reusing/polluting this one's data.

## Variants

**Variant A — Reluctant Reader static popup (control)**
Existing sitewide parent popup, unchanged. Low-friction lead capture:
chapter-book transition problem/solution offer, single-step email
capture.

**Variant B — Interactive quiz funnel (treatment)**
New multi-step popup/panel: 3-4 short questions determine a "reading
profile," recommends a first adventure (or the Complete Collection if
the profile suggests strong fit), then captures email. Higher-friction,
higher-intent-signal design.

## Assignment method

- **50/50, first-party, persistent.** On first exposure, assign a
  variant via `crypto.getRandomValues` (not `Math.random`, for a cleaner
  even split) and store it in `localStorage` under
  `bhp_exp_popup_vs_quiz_v1_variant`, alongside an anonymous visitor ID
  (`bhp_exp_popup_vs_quiz_v1_visitor_id`, a random UUID — no PII).
- **Returning-visitor behavior:** always re-serve the same stored
  variant; never re-roll. If a visitor clears storage, they are treated
  as new (this is an accepted, standard limitation, not a bug to solve
  for v1).
- **Session behavior:** the assignment persists across sessions (via
  `localStorage`, not `sessionStorage`) so a visitor doesn't flip
  variants mid-consideration across multiple visits.
- **A visitor must never see both variants in the same experiment
  period** — enforced by the same storage-first assignment check used
  by the existing popup engine's dismissal-suppression logic
  (`mariana-popup.js`'s `readLocal`/`writeLocal` pattern is the natural
  place to extend, not replace).

## Suppression rules (inherited from the existing popup engine, both variants)

- Never on cart or checkout pages.
- Never while the side-cart drawer is open (same fix already shipped
  for the existing popup — see `mariana-popup.js`'s `isDrawerOpen()`
  guard, added 2026-07-05).
- Never immediately after Add to Cart (the drawer opening already
  satisfies that moment's attention).
- Never re-open repeatedly in one session once dismissed (same 10-day
  `DISMISS_DAYS` suppression convention already in place).
- Mobile: quiz variant must not require more scrolling/typing than is
  comfortable on a 390px viewport — this needs an explicit mobile design
  pass before launch, not an afterthought.

## Email capture timing

- Variant A: unchanged — captured at the existing single-step point.
- Variant B: captured **after** the quiz recommendation is shown, not
  before — asking for email before delivering value first is a known
  higher-abandonment pattern and would bias the comparison unfairly
  toward Variant A.

## Quiz result / recommendation logic

- Recommend a **single specific book** (not a shrug "browse them all")
  based on the profile answers — needs a real, small decision table
  (e.g., adventurous vs. cautious reader × preferred subject) mapped to
  one of the three titles.
- **Complete Collection recommendation rule:** only recommend the
  Complete Collection when the quiz signals strong intent (e.g.,
  "reads multiple books a week" or "buying for more than one child") —
  do not default to the highest-priced offer for every respondent, since
  that would be a dark pattern, not a genuine recommendation.

## Privacy considerations

- No new PII collected beyond what the existing popup already asks for
  (first name + email). Quiz answers are behavioral/preference data,
  not identity data, and should not be sent anywhere requiring separate
  consent beyond what email capture already implies.
- Anonymous visitor ID is a random UUID, never derived from email,
  IP, or any other identifying value.

## Reset / testing mechanism

- A URL parameter (e.g. `?bhp_exp_reset=popup_vs_quiz_v1`) that clears
  the stored assignment for that experiment only, for QA use — must be
  removed or capability-gated before this becomes a persistent feature
  of the live site, since letting the public self-reset assignment would
  contaminate real experiment data.

## Exclusion criteria

- Admin/shop-manager logged-in users (matches the existing popup's own
  `current_user_can('manage_options')` exclusion).
- The Teachers page (out of scope for this experiment — the teacher
  popup is a separate, already-isolated funnel).

## Metrics

**Primary metric: revenue per exposed visitor** — total revenue
attributed to a variant's exposed cohort, divided by the number of
visitors exposed to that variant (not just those who converted) — this
is the only metric that can't be gamed by a low-intent-high-volume
variant looking artificially good on a narrower funnel-stage metric
alone.

**Secondary metrics:**
- Lead conversion rate (popup/quiz email captured ÷ exposed)
- Quiz start rate, quiz completion rate (Variant B only)
- Email capture rate
- Recommendation click rate (Variant B only)
- Add-to-cart rate, checkout-start rate, purchase rate
- Complete Collection purchase rate
- Average order value
- Unsubscribe rate
- Time to purchase (first exposure → first purchase, if within a
  reasonable attribution window)

**Required fields on every experiment-related event:**
`experiment_id`, `variant`, anonymous `visitor_id`, `utm_source`,
`utm_medium`, `utm_campaign`, `landing_page`, `recommended_product`
(Variant B only), `cart_value`, `order_value`, `order_id` (once a
purchase occurs).

## Sample size and significance

- Define a minimum sample size **before launch**, using this store's
  actual current traffic/conversion volume (not a generic e-commerce
  benchmark) — cannot be finalized without GA4 sessions data, so this
  experiment should launch only after the GA4/GTM implementation plan
  is executed, or with an explicit acknowledgment that the primary
  metric will be revenue-per-visitor computed from WooCommerce data
  alone (order source tagged via UTM/experiment fields) without
  session-level context.
- **Do not claim statistical significance prematurely.** Report results
  with a clear "insufficient data" state below the defined minimum
  sample size, the same low-data-state philosophy already used
  throughout the dashboard.

## Explicit non-goals for this document

This specification does not launch the experiment, does not write the
quiz's actual questions/copy, and does not build any code. It exists so
that work can be scoped and reviewed as its own approved piece of work.
