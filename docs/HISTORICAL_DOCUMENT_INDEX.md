# Historical Document Index

**Last updated: 2026-07-13.** Classifies every `docs/*.md` file not already tracked as canonical in `AI_CONTEXT_INDEX.md`. This is a classification pass, not a fresh content-accuracy audit of every file — dates/facts inside archive-candidate and supporting documents were true when written and are not being re-verified here.

**Categories:**
- **Canonical** — current source of truth (already listed in `AI_CONTEXT_INDEX.md`, not repeated here)
- **Supporting** — real, still-useful reference or evidence record; not itself the status source of truth, but not stale either
- **Superseded** — replaced by a canonical document; kept for history, never cite as current
- **Archive candidate** — a dated, one-off session/release record whose content has been absorbed into a canonical doc or `RELEASES/`; low ongoing reference value but not wrong
- **Requires future review** — genuinely unclear status; nobody has re-verified this since it was written

## Supporting

| File | Why |
|---|---|
| `analytics-architecture.md` | Full architecture rationale behind `BHP_Consent`/`BHP_GTM_Loader`/`BHP_Analytics_Config` — referenced by code comments directly |
| `analytics-event-inventory.md` | Original event inventory; cross-check source for `ANALYTICS/EVENT_MATRIX.md` |
| `analytics-validation.md` | Validation methodology notes, referenced by `gtm-configuration-blueprint.md` |
| `event-dictionary.md` | Full event parameter dictionary — more granular than `EVENT_MATRIX.md` |
| `ga4-gtm-implementation-plan.md` | Original implementation plan, useful for "why this design" |
| `gtm-configuration-blueprint.md` | Active reference — contains the Preview/DebugView test procedure (§8, §8a) still in use |
| `utm-attribution-standard.md`, `utm-attribution.md` | UTM parameter standard, still the live convention |
| `kpi-definitions.md` | KPI formula definitions for the economics dashboard |
| `dashboard-data-sources.md` | Data-source methodology for the dashboard |
| `meta-ads-dashboard-integration.md` | Architecture spec for a not-yet-built integration |
| `required-links-policy.md` | Contextual-link policy, still enforced by `BHP_Required_Links_Gate` |
| `order-monitoring-workflow.md` | Bookvault order-monitoring procedure |
| `order-provenance-audit.md` | Real audit establishing the test-order classification system; contains a historical revenue-figures table documenting a bug fix — see `DOCUMENTATION_GOVERNANCE.md` re: this file's discretionary sensitivity flag |
| `phase1c-lead-capture-architecture.md` | Lead-capture system architecture, still accurate |
| `operator-runbook-phase1b-1c.md` | Operator procedures for the analytics/lead-capture phases |
| `experiment-quiz-vs-popup.md` | A/B test design record (Adventure Quiz experiment — deferred, not built, per `ROADMAP.md`) |
| `CTA_ENGINE_ISOLATED_RELEASE_2026-07-12.md`, `CTA_ENGINE_PRODUCTION_DEPLOYMENT_2026-07-12.md` | Full evidence records `RELEASES/CTA_ENGINE_PRODUCTION.md` cites as its detail source |
| `COLLECTION_COUPON_PRODUCTION_DEPLOYMENT_2026-07-11.md` | Full evidence record `RELEASES/COLLECTION_COUPON_PRODUCTION.md` cites as its detail source |
| `About-Page.md`, `Books-Page.md`, `Contact-Page.md`, `Teacher-Resources-Page.md`, `Explorer-Passport-System.md` | Page-content/structure specs — likely still the working reference for these pages' copy and layout, not independently re-verified against live pages this pass |

## Superseded (do not cite as current status)

| File | Superseded by |
|---|---|
| `gtm-staging-build-2026-07-09.md` | `ANALYTICS/GTM_STATUS.md` |
| `gtm-build-verification-2026-07-12.md` | `ANALYTICS/GTM_STATUS.md` |
| `gtm-ga4-production-readiness-audit-2026-07-12.md` | `ANALYTICS/GTM_STATUS.md`, `ANALYTICS/GA4_STATUS.md` — written when GTM was believed unbuilt (0/23 triggers); GTM is now built (39 triggers) |
| `consent-privacy-decision-record.md` | `ANALYTICS/CONSENT_STATUS.md` |
| `Mailchimp-Production-Integration.md`, `Mailchimp-HubSpot-Architecture.md` | `ENGINEERING/MAILCHIMP_STATUS.md` |
| `Technical-SEO-Analytics-Setup.md` | `CONTENT/CONTENT_STATUS.md`, `CONTENT/BLOG_STATUS.md` |
| `phase1d-organic-conversion-architecture.md` | `ENGINEERING/CTA_ENGINE_STATUS.md` for live-status purposes (still useful for the full staging-only architecture of the parts not shipped) |
| `bridge-books-consolidation-proposal.md` | Now a sanitized pointer only — real content moved to the private Drive `MARKETING/SEO/` (2026-07-13) |

## Archive candidate (one-off session/release records)

| File |
|---|
| `coupon-ui-restoration-2026-07-09.md` |
| `fulfillment-copy-correction-2026-07-09.md` |
| `security-investigation-nlo-finance-redirect-2026-07-09.md` |
| `taxonomy-repair-audit-2026-07-10.md` |
| `production-readiness-audit-2026-07-10.md` |
| `Production-Readiness-Audit.md` |
| `Pre-DNS-Migration-Audit.md` |
| `Launch-QA-Checklist.md`, `Launch-Readiness-Checklist.md` |
| `phase1d-conversion-scoring-sample-run.md` |
| `phase1d-security-performance-a11y-review.md` |
| `phase1e-content-intelligence-architecture.md` |
| `phase4-stabilization-report.md` |
| `weekly-cycle-1-editorial-packet.md`, `weekly-cycle-1-pinterest-packet.md`, `weekly-cycle-1-qa-failure-audit.md`, `weekly-slate-1-monitoring.md` |
| `Customer-Acquisition-Blueprint.md` |

## Requires future review

None identified as genuinely ambiguous beyond the "supporting" page-spec docs noted above (which just need a live-content re-check, not a classification decision).

## What this index does not do
It does not delete, move, or rewrite any file. It does not resolve the "public-safe whitelist vs. everything else" question `CURRENT_TASK.md` flags — that's a scope/ownership decision for Andrew, not something this classification pass can settle on its own. A file being "archive candidate" here does not mean it should be deleted; per `DOCUMENTATION_GOVERNANCE.md`'s Archival rules, historical session records are kept as an archaeology record, never removed just because a newer canonical doc exists.
