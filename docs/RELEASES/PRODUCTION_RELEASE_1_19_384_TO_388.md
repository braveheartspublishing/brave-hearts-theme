# Production release - theme 1.19.384 to 1.19.388, bundle plugin 1.8.83 to 1.8.84

**Date:** 2026-09-06 (four production installs in one day)
**Owner approval:** explicit, per install, by token touch. Seals 1088, 1127, 1153, 1185.

| Version | To production | Authorization | Contents |
|---|---|---|---|
| `1.19.384` | 10:13 | seal 1088 | Review-engine scheduling: the daily action is booked at 09:30 site-local across every day of 2026 including both DST changes; an action already at the wanted time is left alone; status prints the next run. |
| `1.19.385` | 17:12 | seal 1127 | Checkout opt-in moved to 66 px / 50 px under the email field (was 1,147 px desktop / 1,471 px mobile, under Payment); 301s for `/what-is-a-lexile-score/` and `/resources`; stale DKIM prohibition comment removed from `review-ask-email.php`. |
| `1.19.386` | 20:57 | seal 1153 | `tests/bootstrap-mail-guard.php` in all 132 suites (`pre_wp_mail` short-circuit); `inc/staging-mail-guard.php` gains `customer_failed_order` and `customer_cancelled_order`. Root cause of six real bounces from staging test runs. |
| `1.19.387` | **NEVER SHIPPED** | - | Tracking-claim removals. The built ZIP swept in a half-written rail guard from a shared working tree (`git add` by directory). **Superseded by `1.19.388`; must not be deployed anywhere.** |
| `1.19.388` | 23:34 | seal 1185 | Carries 387's eight tracking-string removals plus: post tables scroll on phones; the auto-injected book rail no longer splits a numbered entry. |
| plugin `1.8.84` | 23:34 | seal 1185 | Collection-page fine print "Secure checkout - Tracking provided" becomes "Secure checkout". |

## Rollback artefacts (on the server, `~/_rollback/`)
- `PROD-theme-1.19.383-pre-384.tar.gz`
- `PROD-theme-1.19.384-pre-385-20260906-230828.tar.gz`
- `PROD-theme-1.19.385-pre-386-20260907-025620.tar.gz`
- `PROD-theme-1.19.386-pre-388-20260907-052932.tar.gz`
- `PROD-bundle-pricing-1.8.83-pre-1.8.84-20260907-053041.tar.gz`
- `PROD-rankmath-titles-pre-sharecard-20260906-231109.json` (share-card swap)

## ZIP md5, verified on the server before each install
`1.19.384` `87e57acf093b46a52ff2d86400ae3909` - `1.19.385` `7489794599a37b19e21dd5729ad4a5ad` -
`1.19.386` `b3cf30b0d916dd6f5dc15f71541c1e80` - `1.19.388` `4632131da3fd6680de29b7b3702f9e71` -
plugin `1.8.84` `34d3d5deeffb28216a46ce0a611e69f2`

## Ritual, run in full on every install
Lint every PHP file out of the ZIP on the server before install; live-vs-ZIP file diff; rollback tarball;
install; `wp theme list` / `wp plugin list`; ok probe; `sg purge`; version confirmed live (curl with a
browser user agent worked from the operator's machine on 2026-09-06; the SiteGround edge returns 403 to
some non-browser clients, so fall back to a real browser when it does).

## Content shipped the same night, under the same tokens
Post 829 published (`/blog/dallas-harris-and-liberty-read-aloud/`); post 82 and post 46 bodies replaced
from reviewed drafts (pre-write snapshots kept locally under `_prod-candidates/cycle179-drafts/_snapshots/`);
post 46 title corrected on the owner's word; post 350 Tracking section deleted, post 3 processing-purpose
line dropped; review drafts 831 and 833 trashed (reversible); site default share card replaced (media id 822).

## Verified live after the final install (real browser, 2026-09-06 23:50)
Theme `1.19.388` and plugin `1.8.84` active; reading-level post PASS at 375 and 1440 (table scrolls,
column 5 reachable); Magic Tree House post PASS (rail clear of item 8, six `bhothers-20` links, no
Amazon links to Brave Hearts titles); visible tracking claims 0 on product and collection pages;
0 console errors.

## Known defects carried out of this series (queued for 1.19.389)
- Medium: sticky header covers every in-page anchor target (`scroll-margin-top: 0`).
- Table scroll cue never retires after scrolling fully right.
- Kit page copy names "Chapter 7"; the delivered kit is Chapter 10.
- `addon_upsell_shown` fires for the hidden cart-drawer panel (plugin).
