# Funnel Isolation Rules

**Read `docs/ENGINEERING/FUNNEL_CONSTITUTION.md` first.** It is the permanent, frozen, company-wide audience-funnel architecture (established 2026-07-14) governing every current and future funnel — canonical sequence, email philosophy, mandatory purchase suppression before any coupon, and the modular-automation requirement. This file covers only the narrower technical isolation rules between the two funnels currently live in theme code.

Two lead-magnet funnels exist and must stay independent:

- **Parent funnel** (Reluctant Reader Adventure Kit): sitewide except
  `/teachers/`. Storage prefix `bhp_parent_popup`, event prefix
  `parent_popup`, lead magnet key `adventure_kit_parent`, thank-you path
  `adventure-kit-thank-you`.
- **Teacher funnel** (Mariana classroom guide): `/teachers/` only. Storage
  prefix `bhp_mariana_popup` (kept from before it was page-restricted —
  don't rename it, see `docs/DECISIONS.md`), event prefix `teacher_popup`,
  thank-you path `mariana-guide-thank-you`.

Rules:
- Never render both popups on the same page.
- Dismissing or signing up on one popup must never affect the other's
  storage/analytics state — they use fully separate `localStorage`/
  `sessionStorage` key prefixes and event-name prefixes by design.
- Both share one generalized JS engine
  (`assets/js/mariana-popup.js`) driven entirely by a `data-popup-config`
  JSON attribute on each popup's root element — don't fork the engine to
  add a third funnel; extend the config schema instead.
- Do not reintroduce the removed Teachers-page `the_content` filter — the
  real signup form on that page already covers what it was meant to do
  (see `docs/DECISIONS.md`).
- Mailchimp tag/merge-field mapping is context-aware (`Source: Parent
  Popup` vs `Source: Parent Landing Page` vs `Source: Teacher Popup`, etc.)
  — check `bhp_mailchimp_signup_tags` filter in `functions.php` before
  assuming a tag applies globally.
