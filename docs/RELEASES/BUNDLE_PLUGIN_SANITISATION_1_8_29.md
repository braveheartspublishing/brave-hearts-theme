# Bundle-pricing plugin 1.8.29 — private values leave the published source tree

**Status: BUILT · DEPLOYED TO STAGING · VERIFIED ON STAGING · NOT DEPLOYED TO PRODUCTION · NOT PUSHED.**

Production runs bundle plugin **`1.8.28`**, verified live 2026-08-05 by `wp plugin list` over SSH.
**A production deployment packet is prepared and held. It requires Andrew's explicit, current-turn
approval, and it has a mandatory ordering step — see §7.**

---

## 1 · Why

This repository is public. Two things in it did not need to be.

1. **`includes/bundle-cart.php` carried a literal list of working coupon codes.** Anyone reading
   the source learned discount codes that applied on the live store. Rotating a code could not
   help, because the replacement had to be committed to be honoured — which republished it.
2. **`includes/dashboard/class-bhp-cost-config.php` carried the unit-economics model as
   hardcoded amounts** — per-unit and per-title print costs, observed multi-book quotes, postage,
   the processing-fee assumption and the reserve rates. Storefront prices are public, so
   publishing the cost side publishes the margin on every product the store sells.

Both are removed here. **No figure and no code from either is reproduced in this document.**

---

## 2 · What changed — coupons

`BHP_AUDIENCE_COUPON_CODES` is now **an empty array**. Audience-coupon scope resolves from the
per-coupon meta flag `_bhp_audience_coupon`, which has existed since 1.8.8.

The `! defined()` guard around the constant is **deliberately kept**. It is the supported way for a
private, non-published environment to pin literal codes outside source control. Nothing in this
repository may ever populate it, and a test asserts that.

**The four decision points are unchanged and still route through the one shared resolver:** coupon
validity (`woocommerce_coupon_is_valid`), native-discount zeroing
(`woocommerce_coupon_get_discount_amount`), the itemised savings fee
(`woocommerce_cart_calculate_fees` priority 21) and the Bundle Savings stacking guard (priority
20). The savings-fee label is built from the applied code at runtime, so no code has to be known
ahead of time for the line to render correctly.

The admin checkbox that shows and sets the flag is unchanged. Its locked, already-checked state now
only occurs on an environment that has privately pinned codes; with an empty list every coupon's
scope is editable.

### 2.1 · The equivalence check that made this safe

⭐ **Read-only, live, on production, before anything was changed.** Every published coupon record
on production was tested against both resolution routes, printing only IDs and verdicts — never a
code. **Result: every published coupon resolves as an audience coupon by the META FLAG, and none of
them matches the literal list.** The only record that matched the literal list is the one Andrew set
to **draft** on 2026-08-05, which can no longer be applied to a cart at all.

⛔ **So emptying the constant changes the behaviour of zero live coupons.** That is an observed
result on the production database, not an inference from the code.

⚠️ **Staging's coupon data is different and was left alone.** One published staging coupon matched
only the literal list, so on staging that record does lose audience scope. **No coupon record was
edited on any environment to make the tests pass** — that would be a WooCommerce mutation and an
Andrew gate.

---

## 3 · What changed — unit economics

`BHP_Cost_Config` keeps the **model**: the keys, the array shapes, the provenance labels, the
effective dates, and every line of costing logic. It reads the **amounts** — twenty-one of them —
from a single site option, `bhp_cost_model`, seeded per environment out of band and never written,
exported or printed by the plugin. A filter, `bhp_cost_model`, lets a private environment supply
them from somewhere else entirely.

**An unseeded environment is a supported, loud state, not a broken one:**

* every getter reports `basis => 'unavailable'`;
* `estimate_order_profit()` and `estimate_order_profit_precise()` return **null** cost fields with
  `cost_basis => 'unavailable'` — never a profit computed against zero costs, which would report
  the entire order total as profit;
* the offer/SKU tables report `basis => 'unavailable'` rather than `'estimated'`;
* the dashboard screen carries an admin notice naming **how many** keys are missing, and the option
  they belong in. It names no value.

**Partial seeding is not seeding.** `is_seeded()` is false unless all twenty-one keys resolve to a
number, because a half-populated model produces arithmetic that looks complete and is not.

### 3.1 · The equivalence proof

A fingerprint script hashed two projections of the model — the 19 amount-bearing config values, and
44 numeric/basis outputs of the costing functions across single, two-book, three-book, unknown-title
and unseeded cases. It prints **only the hashes**, never a figure.

| | AMOUNTS (19 keys) | BEHAVIOUR (44 values) |
|---|---|---|
| Production, plugin 1.8.28 (hardcoded), read-only | `570b3be14216fb2b709aa787399c5a35` | `8ca5fa34bc7268b3133e660211a2cd56` |
| Staging, plugin 1.8.28 (hardcoded) | `570b3be14216fb2b709aa787399c5a35` | `8ca5fa34bc7268b3133e660211a2cd56` |
| **Staging, plugin 1.8.29 (option-driven, seeded)** | **`570b3be14216fb2b709aa787399c5a35`** | **`8ca5fa34bc7268b3133e660211a2cd56`** |

⭐ **Byte-identical. The relocated model computes exactly what the hardcoded one computed.**

---

## 4 · What changed — tests

**The two consuming suites no longer pin a single cost figure.** They previously asserted
contributions, break-evens and print costs as literals; since storefront prices are public, a
contribution literal discloses the cost that produced it by subtraction, so those literals were a
second copy of the thing this release removes. They now assert the model's **identities** —
gross profit = revenue − fees − costs; contribution = gross profit − reserves; break-even =
contribution; hardcover > paperback; per-title costs differ; the free-shipping reserve base is the
price alone — against values read back from the model.

**Published storefront prices, bundle discounts, shipping rates and the Andrew-approved CPA targets
and ceilings are still pinned as literals, deliberately.** They are on the site or in an approved
table, and *pinning an approved ceiling is what protects it from being silently lowered.*

⚠️ **Stated honestly: a relational assertion cannot catch a wholesale mis-seed of the option.** That
is what the deploy-time fingerprint in §3.1 is for.

**Two new suites:**

* `tests/test-audience-coupon-meta-scope.php` — asserts the shipped `bundle-cart.php` defines the
  constant as an empty array with no string literal of any kind; that every published coupon's
  scope, by both resolution routes, equals its meta flag exactly; that a non-existent, empty,
  whitespace-only or unsaved code is never scoped; and that all four decision points resolve
  through the shared resolver, checked against the source so an inlined comparison fails here
  rather than in production. It creates and edits **nothing**, and it names which assertions it
  could not exercise on an environment rather than reporting a green run it did not earn.
* `tests/test-cost-model-source.php` — asserts the shipped cost-config carries no non-zero
  money-shaped literal and no dollar figure in code or prose; that both consuming suites pin nothing
  outside the published set; that this environment is fully seeded; and — by filtering an empty and
  then a partial model, never by touching the real option — that the unseeded path reports
  `unavailable` rather than a number. It asserts its own restoration afterwards.

---

## 5 · Test evidence — before and after, same instrument, same environment

Full PHP suite, run over WP-CLI on **staging**, `wp eval-file` per file.
`test-purchase-validation-harness.php` is excluded by its own safety guard because it creates and
deletes real WooCommerce orders; it was **not executed**, and that is correct.

| | Suites passing | Failing | Skipped by design |
|---|---|---|---|
| **Before** — plugin 1.8.28 | **19** | 0 | 1 |
| **After** — plugin 1.8.29 | **21** | 0 | 1 |

The two added suites are the difference. **No pre-existing suite changed verdict.**

---

## 6 · Cart and checkout QA — staging, plugin 1.8.29

Driven server-side through the real WooCommerce cart — the same code the Store API calls — against
a Contiguous-US destination. **No order, coupon, product or setting was created or changed; the
cart is emptied and asserted empty at the end of each run.**

**Tiered shipping — all six rows pass, each compared against `bhp_bundle_rules()` /
`bhp_bundle_single_shipping()` rather than a re-typed table:** 1 paperback · 2 distinct paperbacks ·
3 distinct paperbacks · 1 hardcover · 2 distinct hardcovers · 3 distinct hardcovers. Every case
offers **exactly one** shipping method and **zero** BookVAULT methods.

⚠️ **Both three-book rows now render shipping at `$0.00`** — the free-shipping-on-collections
ruling implemented in 1.8.23. **`.claude/rules/woocommerce.md`'s tier table still shows the older
`$3.99`/`$4.99` for those two rows and is stale.** Flagged, not edited: that file is outside this
release's scope.

**Bundle and coupon behaviour:** the Bundle Savings fee equals the approved discount on a complete
collection · a **meta-flagged** audience coupon applies, stacks alongside Bundle Savings and renders
its own itemised savings line · the same coupon is **rejected** on a single-book cart and is not
left applied · a **non-audience** coupon still fully suppresses Bundle Savings, so the Phase 9
behaviour is intact.

**Free add-on with a complete collection:** the collection earns the add-on · exactly one add-on
line appears, carrying the grant marker · **it prices at `$0.00`** · `woocommerce_check_cart_items`
raises **zero** error notices, so the Store API's `validate_cart()` **409** guard stays quiet on a
valid granted cart · breaking the collection **withdraws** the granted copy so nobody is silently
charged for it, and raises no notice either · a customer who removes the granted copy is recorded as
declining it and **it is not silently re-added**.

⚠️ **Recorded because it was a real failure before it was understood:** the first QA run reported
three add-on failures. **The QA script was wrong, not the plugin** — it treated
`bhp_bundle_addon_free_with_collection()` as returning a product ID when it returns a bool. Fixed,
re-run, all green. The original failing output is not hidden.

---

## 7 · ⛔ Production deployment — PREPARED, NOT EXECUTED, and the ORDER MATTERS

**The seed must run BEFORE the plugin is replaced.** The seed derives every amount from the
`BHP_Cost_Config` methods of the **currently-installed 1.8.28**, which is the only place those
amounts still exist on that environment. Once 1.8.29 is installed, the class reads the option, and
a seed run afterwards would write zeros over the model.

Deploying in the wrong order is **recoverable but noisy**: reinstall 1.8.28 from the rollback
tarball, seed, then reinstall 1.8.29.

⭐ **The seed command contains no figure**, on purpose: it copies values from one place on the
server to another and never names one. The packet is held outside this repository.

**Rollback:** reinstall the previous artefact with `wp plugin install <zip> --force` from the
pre-deploy tarball. The plugin directory is deleted before extraction, so a rollback is a
full-artefact reinstall, never a file copy. `bhp_cost_model` is additive and can be left in place —
1.8.28 ignores it entirely.

**Nothing outside the plugin directory changes, apart from that one non-autoloaded option.** No
database schema, no product, no order, no WooCommerce setting.

---

## 8 · What this release does NOT claim

* ⛔ **It is not on production, and no production approval was sought or given.**
* ⛔ **It has not been pushed.**
* ⛔ **No WooCommerce product, variation, price, coupon, stock, shipping, tax, payment or checkout
  record or setting was changed on any environment.** The coupon that is now draft was set so by
  Andrew's own decision, before this work began.
* ⛔ **It does not fix the jQuery console error** introduced by theme 1.19.201. That is a theme
  defect, diagnosed in `KNOWN_ISSUES.md`, and is not this release's to claim.
* ⛔ **It does not remove the CPA target and ceiling figures** from `class-bhp-cpa-model.php`. Those
  are a separate, Andrew-approved table, they were not in this brief, and pinning them is what
  protects them. **They remain in the public tree and are flagged as a residual exposure for
  Andrew's decision.**
* ⚠️ **The relational tests cannot detect a mis-seeded option.** The deploy-time fingerprint can,
  and is the check that must be run after any seed.
