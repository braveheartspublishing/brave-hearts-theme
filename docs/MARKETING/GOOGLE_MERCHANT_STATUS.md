# Google Merchant Status

**Last verified: 2026-07-13**, via read-only WP-CLI inspection of production (`google-listings-and-ads` plugin options and per-product sync meta). No Merchant Center or Google Ads console login was available this session — findings below are limited to what the WordPress-side plugin state reports.

## Connection
`google-listings-and-ads` (Google for WooCommerce) plugin **active**, v3.7.3, on production. Merchant Center and Ads accounts are linked (`gla_google_connected = true`, merchant/ads account IDs present as WP options, not reproduced here). Setup fully completed at both the Merchant Center (`gla_mc_setup_completed_at` set) and Ads (`gla_install_timestamp` set) level.

**Flag:** the Ads account-state record's `account_access` step carries the message *"Please reconnect your Google account"* — the connection may be stale and should be checked directly in the plugin's WooCommerce → Google for WooCommerce settings screen, not assumed healthy from this WP-CLI snapshot alone.

## Paid advertising
**Skipped, confirmed.** `gla_campaign_convert_status` = `not-applicable` — no Performance Max (or any) ads campaign has been created or converted. This matches the company decision to defer paid acquisition until analytics is live (see `ANALYTICS/CONSENT_STATUS.md`).

## Product sync
All **6 individual book products** (3 titles × 2 formats — the only real SKUs; there is no separate Complete Collection SKU, see below) are configured `_wc_gla_visibility = sync-and-show` and report `_wc_gla_sync_status = synced`.

**Important finding, not previously documented:** every one of the 6 products also carries `_wc_gla_mc_status = disapproved`. "Synced" means the feed data reached Google; "disapproved" means Google Merchant Center rejected the listing on review (reason not visible from WP-CLI — requires opening the Merchant Center console directly, which this session could not access). **This is a real gap, not a false negative** — all 6 products are currently synced-but-disapproved, meaning none of them are actually eligible to show as a free listing or in any future paid campaign until the disapproval is resolved.

## No Complete Collection feed
Confirmed correct, not a gap: the Complete Collection is cart/bundle logic (`brave-hearts-bundle-pricing` plugin), not a standalone WooCommerce product post, so it has no product ID for GLA to sync and correctly does not appear as a separate Merchant Center listing.

## Next manual verification
1. Open the Merchant Center console directly (requires Andrew's Google login) to read the actual disapproval reason(s) for each of the 6 products.
2. Re-authenticate the Ads account connection (`account_access` "Please reconnect" message) via WooCommerce → Google for WooCommerce.
3. Re-check `_wc_gla_mc_status` after any fix to confirm it moves off `disapproved`.

## Ownership and status
- **Owner:** Andrew (Merchant Center console access), Engineering (WP-side sync configuration)
- **Last verified date:** 2026-07-13
- **Canonical:** Yes, for this topic — no other document in this repo tracks Google Merchant/GLA state
