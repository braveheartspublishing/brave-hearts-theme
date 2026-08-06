# Operator Runbook — Phase 1B Analytics + Phase 1C Lead Capture

For Andrew. No codebase knowledge required — this is the "what do I
actually click/run" version. For the engineering detail behind any of
this, see the cross-referenced docs.

## Where things stand right now

- **Staging** (`staging2.braveheartspublishing.com`): GA4 ID
  `G-7M42X19Z2T` and GTM container `GTM-N474PRSH` are both configured.
  Nothing is publicly visible yet — the GTM container itself still needs
  tags/triggers built inside Google Tag Manager (you have an
  authenticated Google session already; see §2).
- **Production** (`braveheartspublishing.com`): completely untouched.
  No GA4 ID, no GTM ID, no consent approval. Analytics code isn't even
  deployed there yet.
- **Lead capture**: your existing Mailchimp/popup/form system is
  untouched and still works exactly as before. What's new is a private,
  admin-only log of signups (Tools → Lead Signups in wp-admin) and a bit
  more analytics detail — nothing customer-facing changed.

## 1. Approving analytics for production (when you're ready)

Three things, in order:

1. **Decide** whether you want a consent banner or not (see
   `docs/consent-privacy-decision-record.md` §2 — this is a business
   call, not a technical one).
2. **Build the real GTM container** — follow
   `docs/gtm-configuration-blueprint.md` step by step, using
   `docs/gtm-implementation-manifest.json` as the exact list of what to
   create. This requires your own Google login; I don't have access to
   Google Tag Manager's web console.
3. **Flip the one approval switch** (only after 1 and 2):
   ```
   wp option update bhp_consent_decision_approved 1
   ```
   Until you do this, GTM will not go live on production no matter what
   else is configured — that's intentional.

## 2. Building the GTM container (your action, in your browser)

You're already logged into Google Tag Manager with the correct account —
the "Brave Hearts Publishing" account and the `GTM-N474PRSH` container
are both visible when you open tagmanager.google.com. I looked, but did
not make any changes inside it (I'm not authorized to configure your
live ad/analytics tooling without you walking through it, and the exact
tags/triggers should be built by you following the checklist so you know
what's in your own container). Follow `docs/gtm-configuration-blueprint.md`
sections 1–9 in order. It will take roughly 30–60 minutes the first time.

## 3. Checking lead signups

**Tools → Lead Signups** in wp-admin shows real vs. test signup counts,
broken down by placement (which form/popup), audience, and lead magnet,
plus the 50 most recent events. Test signups (from QA, using
`+bhptest@...` addresses, or anything from staging) are always shown
separately and never mixed into the real counts.

## 4. If something looks wrong

- **GTM/GA4 IDs disappeared from staging unexpectedly**: this happened
  three times during this session's testing, always caused by re-running
  an older version of one of the automated test files that didn't
  preserve real configuration. All known instances are now fixed (see
  `tests/test-analytics-phase1b.php` and `tests/test-gtm-loader.php`).
  If it ever happens again: `wp option update bhp_gtm_container_id
  GTM-N474PRSH` and `wp option update bhp_ga4_measurement_id
  G-7M42X19Z2T` on staging restores them immediately — nothing else is
  affected.
- **Analytics isn't firing on staging when you expect it to**: check
  `wp option get bhp_staging_analytics_override` — it should be `1`
  during an active validation session, and you should turn it back to
  `0` (or `wp option delete bhp_staging_analytics_override`) when done.
- **A form seems broken**: check whether MC4WP itself is connected
  (Plugins → Mailchimp for WordPress in wp-admin) — the form disables
  itself automatically and shows a friendly "temporarily unavailable"
  message if MC4WP loses its API connection; that's by design, not a bug.

## 5. What NOT to do without thinking it through first

- Don't approve `bhp_consent_decision_approved` before you've actually
  decided your consent-banner posture (§1.1) — there's no undo notice to
  visitors once real tracking starts.
- Don't copy GTM/GA4 IDs to production until you've validated the
  container on staging first (same staged-rollout rule as every other
  change to this site).
- Don't delete `bhp_lead_event` posts in bulk without checking
  `docs/consent-privacy-decision-record.md` §2 first — there's currently
  no retention policy decided, so "how long to keep these" is still an
  open question, not a default to assume.

## 6. Reference index

| Question | Document |
|---|---|
| What does every analytics event actually send? | `docs/event-dictionary.md` |
| Exact GTM container build steps | `docs/gtm-configuration-blueprint.md` |
| Machine-readable event/parameter list | `docs/gtm-implementation-manifest.json` |
| What's a business decision vs. a legal question vs. already handled? | `docs/consent-privacy-decision-record.md` |
| How the lead-capture system actually works | `docs/phase1c-lead-capture-architecture.md` |
| How to re-run the automated tests | `docs/analytics-validation.md` |
