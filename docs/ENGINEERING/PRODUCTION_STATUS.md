# Production Status

**Last verified: 2026-07-12** via WP-CLI. **Superseded for version/deploy-history purposes by `PROJECT_STATE.md`'s "Current Production Version" line (currently v1.19.20 after the 2026-07-14 mobile-header fix) — this file's theme version below is a stale 2026-07-12 snapshot, not current truth.** The plugin/feature facts below (Stripe, GTM/GA4 state, coupon, Kirkus/Amazon review components) remain accurate as of their stated dates.

- Theme `brave-hearts-theme-deploy-explorer-expedition-guides` v1.19.4 as of 2026-07-12 (stale — see note above), active. Host: `braveheartspublishing.com`.
- 36 published blog posts, 6 published products (3 books × 2 formats), all live.
- Stripe live mode confirmed working (order #336 was the first real production order).
- GTM/GA4 options are **unset** on production — no container ID, no measurement ID, `bhp_consent_decision_approved` unset.
- The only live Google script is `googletagmanager.com/gtag/js?id=AW-18315643536` — a Google Ads conversion tag from the Google Listings & Ads WooCommerce plugin, unrelated to GTM/GA4.
- CTA Engine (isolated subset only) live since 2026-07-12.
- [PARENT_COUPON_CODE_SUPERSEDED] coupon live since 2026-07-11.
- Kirkus credibility component live since 2026-07-04.
- Amazon customer review showcase live since 2026-07-05.

## Deploy mechanism
Full-ZIP `wp theme install --force` for theme changes (never piecemeal file copies for a full release) — the ZIP's top-level folder name must match the active theme's slug or it installs as a new, inactive theme. Narrow file patches (with timestamped rollback backups) are used only for small, isolated, explicitly-approved changes — see `RUNBOOK.md`.

## Safety rules
Never deploy to production without Andrew's explicit, current-turn approval. Staging first, always. Full detail: repo `CLAUDE.md` and `.claude/rules/production-safety.md`.
