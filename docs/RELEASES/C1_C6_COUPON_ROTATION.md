# C1/C6 — Coupon Rotation + Mailchimp Remediation (execution record)

**Date:** 2026-08-01 · **Status:** Staging complete, then **production remediation executed on owner approval** — see "PRODUCTION EXECUTION" at the end of this file, which supersedes the "not mutated" statements in the staging-phase sections below.

Coupon code strings appear nowhere in this document by design. Replacement
codes are recorded only in `docs-private/MAILCHIMP_INTERNAL_REFERENCE.md`
(gitignored, verified never committed on any branch). Everything below
refers to WooCommerce coupon **IDs** and audience labels.

---

## 1. Baseline verified before any change

| Check | Result |
|---|---|
| Production theme | 1.19.121 |
| Staging theme | 1.19.121 |
| Production plugin `brave-hearts-bundle-pricing` | 1.8.7 |
| Repo branch | `feature/production-integration-1.17.1` @ `1e939fb` |
| `docs-private` gitignored | Yes — `.gitignore:23` `/docs-private/` |
| `docs-private` ever tracked | **No** — `git ls-files` empty, `git log --all -- docs-private` empty |
| Production coupons | **ID 346 only** (`publish`) |
| Staging coupons | 565 (`publish`), 592 (`draft`), 593 (`draft`) |
| **Do IDs 592/593 exist on production?** | **No — staging only.** Confirmed by full inventory, not by document |

Backups: `~/bhp-C1C6-backup-20260801-backup/` on the server — coupon
post+meta JSON and a `wp_posts`/`wp_postmeta` SQL dump for both
environments, `bundle-cart.php` for both, full pre-change staging plugin
directory, plugin/theme version manifests, and `bhp_lead_magnet_pdfs`.
Local: `docs-private/MAILCHIMP_INTERNAL_REFERENCE.md.bak-20260801`.

---

## 2. STOP-GATE FINDING 1 — the Collection restriction is not on the coupon

Coupon IDs 346, 565, 592 and 593 carry **no** `product_ids`,
`product_categories`, `exclude_product_ids` or `minimum_amount` meta.
Their complete meta is: `discount_type=percent`, `coupon_amount=10`,
`individual_use=yes`, `usage_limit=0`, `usage_limit_per_user=1`,
`limit_usage_to_x_items=""`, `usage_count=0`, `date_expires=""`,
`free_shipping=no`, `exclude_sale_items=yes`. All four are identical.

The Collection-only scope came entirely from a hardcoded literal-code
allowlist in `plugins/brave-hearts-bundle-pricing/includes/bundle-cart.php`
(`BHP_AUDIENCE_COUPON_CODES`), a **git-tracked file in a public repo**.
A rotated code could therefore only be made Collection-only by publishing
it — which defeats the rotation.

**Proven on staging with a control coupon**, meta cloned field-for-field
from the live source, exercised through the real Store API cart:

| Cart | Bundle Savings | Coupon effect | Customer pays |
|---|---|---|---|
| 3-book paperback Collection, no coupon | −$3.98 | — | $37.90 |
| same cart, legacy Parent code | −$3.98 kept | −$3.20 stacked fee | **$34.51** |
| same cart, cloned-meta replacement | **suppressed** | −$3.60 native | **$38.31** |

The cloned coupon was also **accepted on a single-book cart** (HTTP 200,
−$1.20) where the legacy code was correctly **rejected** (HTTP 400). So a
naively cloned replacement is both unrestricted *and* makes the Collection
$0.41 more expensive than using no coupon at all.

## 3. Fix implemented — plugin 1.8.8 (staging only)

Additive, backward-compatible: a per-coupon opt-in meta flag
`_bhp_audience_coupon = yes`, resolved by a single new helper
`bhp_is_audience_coupon_code()` that all four decision points now share
(validation, native-discount zeroing, savings fee, Bundle-Savings
stacking guard). A wp-admin checkbox was added to the coupon screen so
the scope is visible and operable rather than living invisibly in
postmeta. The legacy literal list is retained unchanged, so the three old
codes behave exactly as before and no data migration was required.

Scope now travels with the coupon record, so **replacement codes never
need to enter source control.**

## 4. Coupons created — staging

| Audience | Staging coupon ID | Status after this session |
|---|---|---|
| Parent | **622** | `publish` (left live for continued cart testing) |
| Educator | **623** | `draft` (per plan — journey not ready) |
| Gift Buyer | **624** | `draft` (per plan — journey not ready) |

Codes were generated on the server and written directly to a
mode-600 file, transferred to `docs-private` without ever being printed.
Verified: all three distinct; none matches the `<AUDIENCE>10` shape; a
full filesystem scan of the repo confirms **no replacement code appears
in any file outside `docs-private`**.

Temporary control coupon 621 was force-deleted after testing.

### Cart test results — all 9 pass, matching the legacy code exactly

| Audience | Paperback Collection | Hardcover Collection | Single book |
|---|---|---|---|
| Parent (622) | −$3.20 stacked on −$3.98 | −$4.90 stacked on −$4.98 | **rejected** |
| Educator (623) | −$3.20 stacked on −$3.98 | −$4.90 stacked on −$4.98 | **rejected** |
| Gift Buyer (624) | −$3.20 stacked on −$3.98 | −$4.90 stacked on −$4.98 | **rejected** |

Rejection message is the exact Collection-only string. Cart emptied after
testing; no order was placed.

**Evidence note, stated plainly:** the three replacement coupons were
exercised through a real WooCommerce cart server-side, so the codes stayed
out of the session transcript. The identical-configuration control coupon
was additionally exercised through the real Store API in a browser, which
is what proves the Store API path. No single coupon was tested by both
methods.

---

## 5. STOP-GATE FINDING 2 — live Mailchimp state contradicts every document

Verified by a logged-in session on 2026-08-01. The repo last verified
this on 2026-07-16.

| Journey | Documented (2026-07-16) | **Actual, 2026-08-01** | Contacts |
|---|---|---|---|
| Parent — Acquisition Funnel (89) | Draft | **ACTIVE** since Jul 17 | 4 started, 1 in progress |
| Educators — Acquisition Funnel (90) | Draft | **PAUSED** since Jul 30 | 4 started, 3 in progress |
| Gift Buyer — Acquisition Funnel (91) | Draft | **ACTIVE** since Jul 17 | 7 started, 1 in progress |
| Retailer — Acquisition Funnel (92) | Draft | Draft | — |
| Organization — Acquisition Funnel (93) | Draft | **ACTIVE** since Jul 17 | 2 started |
| Global — Tag Purchasers (88) | Active | Active | 1 started, 1 completed |

**Legacy automations id 85 (Reluctant Reader Adventure Kit) and id 86
(Coupon Flow) no longer exist.** The complete campaign list contains 7
items and neither appears. The approved "keep id 85 active, then cut over
atomically to id 89" operation is therefore **moot — id 89 has already
been the sole live parent path for 14 days.** There is no double-delivery
risk and nothing to retire.

**Placeholder URLs are gone too.** Gift Buyer Email 1 links a real hosted
PDF (`mcusercontent.com/.../Gift_Guide.pdf`), not
`[INSERT FINAL ... URL]`. Its 75% click rate is consistent with a working
link.

**Educator Email 2 has already been rewritten** and no longer contains
"finishing touches" or the non-existent "reading log" — B3/D-6 is already
resolved, but with different copy and a different subject
("How Will You Use Your Adventure Learning Toolkit?") than the approved
Step-01 draft expected. Step-01's own stop conditions ("stop if the
journey shows anything other than Draft"; "stop if the subject differs")
are both triggered, so the approved body was **not** entered — doing so
would have overwritten a newer fix.

## 6. STOP-GATE FINDING 3 — three journeys will email non-working coupon codes

| Journey | State | Code in Email 3 | Exists in WooCommerce? |
|---|---|---|---|
| Parent (89) | **Active**, 1 in progress | Parent legacy code | Yes (ID 346) — but publicly compromised |
| Educators (90) | Paused, 3 in progress | Educator legacy code | **No — nowhere on production** |
| Gift Buyer (91) | **Active**, 1 in progress | Gift legacy code | **No — nowhere on production** |
| Organization (93) | **Active** | **a fourth, previously unrecorded code** | **No — exists in neither environment** |
| Retailer (92) | Draft | none — correct per policy | — |

Two policy breaches follow:

1. **A fourth audience coupon code exists** that no document records.
   `STEP-02-COUPON-CREATION.md` states "only three coupons exist in the
   whole system… there is no fourth or fifth code hiding anywhere." That
   is wrong. It is quoted in the Organization Email 3 body and subject.
2. **Organization carries a coupon at all**, contradicting the
   approved decision "No coupon for Organization or Retailer audiences"
   and the Frozen Audience Coupon Policy.

Email 3 has sent **0** times on every route so far, so **no subscriber has
yet received a dead code** — but all three are queued behind delays inside
live journeys.

---

## 7. What was deliberately NOT done

- **No production mutation of any kind.** Production remains theme
  1.19.121, plugin 1.8.7, `bundle-cart.php` md5 `e1dce1a5…` unchanged,
  coupon inventory still ID 346 only.
- **Coupon ID 346 was not disabled** — correct on two counts: the
  replacement is not on production yet, and the instruction forbids
  disabling it while Mailchimp work is incomplete.
- **No journey activated, paused, resumed or retired.**
- **No Mailchimp email body edited.**
- **No seed test run** — three journeys are live, so submitting a quiz
  route would enroll a real contact and send a real email containing a
  broken coupon code. No approved seed address was supplied.
- **Email 3 code substitution deferred** — pointless and misleading until
  the replacement coupons exist on production.

## 8. Rollback

| Item | Rollback |
|---|---|
| Staging plugin 1.8.8 | `~/bhp-C1C6-backup-20260801-backup/staging/plugin-dir-before/` (full pre-change directory); production copy is the unchanged 1.8.7 |
| Staging coupons 622/623/624 | `wp post delete <id> --force` — created this session, referenced by nothing |
| Coupon records | `~/bhp-C1C6-backup-20260801-backup/{prod,staging}/coupon-*.json` + `coupons-tables.sql` |
| `docs-private` reference | `docs-private/MAILCHIMP_INTERNAL_REFERENCE.md.bak-20260801` |
| Production | Nothing to roll back — untouched |

---

# PRODUCTION EXECUTION — 2026-08-01 (owner-approved)

## Baseline re-verified before any change

Theme 1.19.121 · plugin 1.8.7 · `bundle-cart.php` md5 `e1dce1a5…` · coupons **ID 346 only** · 6 products · PHP ok · one `flat_rate` shipping zone, **no BookVAULT**. Matched the reported baseline exactly, so the deploy proceeded.

## Backup / rollback materials — `~/bhp-PROD-C1C6-backup-20260801/`

Full pre-change plugin directory (`plugin-1.8.7-before/`), a **reinstallable `brave-hearts-bundle-pricing-1.8.7-ROLLBACK.zip`** whose top-level folder is the correct plugin slug, a 44-file md5 manifest, coupon post/meta JSON + raw SQL rows, theme/plugin/product manifests, per-product prices and stock, `bhp_lead_magnet_pdfs`, `page_on_front`, and the shipping-zone method table.

## Production changes made

| # | Change | Evidence |
|---|---|---|
| 1 | Plugin **1.8.7 → 1.8.8** via `wp plugin install --force` | 44/44 files byte-identical to validated staging; `bhp_is_audience_coupon_code()` present; `BHP_AUDIENCE_COUPON_META_KEY` = `_bhp_audience_coupon`; legacy list intact |
| 2 | Coupon **414 — Parent**, `publish` | meta identical to 346 + scope flag |
| 3 | Coupon **415 — Educator**, `draft` | meta identical to 346 + scope flag |
| 4 | Coupon **416 — Gift Buyer**, `draft` | meta identical to 346 + scope flag |
| 5 | Organization journey **id 93** Email 3 (campaign 8118780) — discount promise **removed** | reload-verified |

Nothing else on production changed. Theme untouched at 1.19.121; 6 products; **0 orders**; one `flat_rate` zone; `bhp_lead_magnet_pdfs` md5 identical to the pre-change backup.

## Tests

**Regression, before creating any coupon** — legacy coupon 346 on production via the real Store API:
- single book → **rejected, HTTP 400**, exact Collection-only message
- 3-book paperback Collection → stacks: `−$3.20` savings fee **plus** `−$3.98` Bundle Savings, total **$34.51** — identical to pre-deploy

**All three replacements, 9/9 pass** (real WooCommerce cart, server-side so codes stayed private):

| Coupon | Paperback Collection | Hardcover Collection | Single book |
|---|---|---|---|
| Parent 414 | −$3.20 on −$3.98 | −$4.90 on −$4.98 | rejected |
| Educator 415 | −$3.20 on −$3.98 | −$4.90 on −$4.98 | rejected |
| Gift 416 | −$3.20 on −$3.98 | −$4.90 on −$4.98 | rejected |

`usage_count` remained **0** on all four coupons; order count **0**; carts emptied. **No order was placed.**

## Incident — two coupon values exposed and rotated

During a status check, WooCommerce's rejection notice quoted the coupon code in **lower case** while the output mask only replaced the upper-case form, so the **Educator** and **Gift** values appeared in the session transcript. The **Parent** value was not exposed (it applied successfully and produced no error string).

Both affected coupons were Draft, unused (`usage_count` 0) and referenced by no email, so they were **rotated immediately** on both environments (production 415/416 and staging 623/624 updated in step, prod and staging values confirmed matching). The exposed values now resolve to **no coupon in either environment**. The private reference records the rotation.

Method fixed: subsequent checks print pass/fail only and never echo WooCommerce notice strings.

## Deliberately NOT done

- **Parent Email 3 not updated and journey id 89 not paused.** Editing a live journey's email requires pausing it, and instruction 9 permits resuming only after a **real-inbox seed test** — for which no approved seed address exists. Pausing would have created an indefinite outage of the parent welcome sequence with no sanctioned way to end it, so id 89 was left **Active and fully functional**.
- **Gift Email 3 code not substituted.** Typing the replacement value into the Mailchimp editor would place it in the session transcript, which the standing instruction forbids. Journey 91 stays Paused, so nothing sends.
- **Legacy coupon ID 346 NOT disabled** — correct per instruction 12, since the Parent journey has not passed end-to-end testing.
- **Gift Buyer (91) and Organization (93) NOT resumed** — per instruction 10, awaiting owner review.
- **Retailer (92)** untouched, still Draft.
- No test emails sent; "Send test" was never used.

## Rollback

```
wp --path=<prod_doc_root> plugin install ~/bhp-PROD-C1C6-backup-20260801/brave-hearts-bundle-pricing-1.8.7-ROLLBACK.zip --force
```
Then `wp post delete 414 415 416 --force` and `wp sg purge`. Coupon 346 is untouched and still live throughout, so reverting restores the exact pre-session state. Organization Email 3 can be restored from Mailchimp's own campaign revision history.
