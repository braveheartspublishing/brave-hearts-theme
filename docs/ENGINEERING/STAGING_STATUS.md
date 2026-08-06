# Staging Status

**Last verified: 2026-07-12** via WP-CLI. **Superseded for version purposes by `PROJECT_STATE.md`'s "Current Staging Version" line (currently v1.19.37 after Sprint A conversion fixes, 2026-07-16) — staging has been well ahead of production since the Audience Landing-Page System sprint (2026-07-15) and is not "in sync with production" as this snapshot states.**

- Theme `brave-hearts-theme-deploy-explorer-expedition-guides` v1.19.4 as of 2026-07-12 (stale — see note above).
- Host: `staging2.braveheartspublishing.com`.
- Has the full Phase 1D/1E suite (content classification, CTA engine, campaign landing, conversion scoring, content-intelligence engine) — production only has the isolated CTA Engine subset.
- Has newer regression test suites than production (`test-content-classification.php`, `test-cta-engine.php`, and others) — a known, non-urgent parity gap.
- GTM/GA4 options ARE saved on staging (`bhp_gtm_container_id` = `GTM-N474PRSH`, `bhp_ga4_measurement_id` = `G-7M42X19Z2T`), but `bhp_staging_analytics_override` is off, so nothing currently loads even here.
- `bhp_staging_analytics_override` — turn on only for a bounded GTM Preview/GA4 DebugView validation session, then turn back off.
- Environment detection: hostname-based (`STAGING_HOST = 'staging2.braveheartspublishing.com'`), not a constant that could go stale after a migration.

## Access
SSH host/port/user in Claude Code auto-memory (`reference-bhp-siteground`) — never duplicated into repo files.
