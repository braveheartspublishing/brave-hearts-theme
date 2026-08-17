# CYCLE162-LD-SCHOOL-PICKUP — QA evidence

Build: **theme 1.19.231 / bundle plugin 1.8.49**, staging only, 2026-08-17.
Branch `feature/signup-modal-1.19.223`. Production untouched and unverified by
this pass — see §"What was NOT verified".

⚠ **This directory is inside a PUBLIC GitHub repository.** Coupon codes are
redacted as `[PARENT_COUPON_CODE_REDACTED]` in `03-cart-walks.txt`. The school
display names present here are the ones seeded on **staging** and are
placeholders pending Andrew's confirmation of the exact names.

## Files

| File | What it is |
|---|---|
| `01-test-baseline-BEFORE.txt` | Full theme+plugin suite run on staging BEFORE any code was written |
| `02-new-suite-test-school-visit-pickup-PASS.txt` | The new suite, 87 assertions, 0 failures |
| `03-cart-walks.txt` | Six cart walks through WooCommerce's own cart/shipping engine |
| `04-store-api.txt` | The pickup rate as the React checkout actually receives it |
| `05-order-marking-and-emails.txt` | Order meta, order note, thank-you notice, E1 email diff, webhook decision |
| `06-test-baseline-AFTER.txt` | Full suite AFTER, compared to the baseline |
| `07-seeding-commands.md` | Staging (done) and production (PREPARED, NOT APPLIED) |

## Result in one line

The option appears only for a flagged, non-expired visitor; it is offered
alongside normal shipping and never instead of it; a hand-delivery order is
blocked from all four of this store's Bookvault webhooks and from the Bookvault
plugin's manual resend action; normal orders are unaffected; and the baseline
gained one passing suite and no new failures.

## What was NOT verified — read this before treating the build as QA-complete

1. ⛔ **No browser was used.** This agent has no browser-automation tool in its
   runtime toolset. Therefore: **no console-error check, no four-viewport
   check, no visual confirmation that the radio button renders in the Blocks
   checkout, and no real click-through purchase.** The Store API evidence in
   `04-store-api.txt` proves the rate is *offered and selectable through the
   exact endpoint the React checkout calls*, which is the strongest evidence
   obtainable without a browser — it is not a substitute for one.
   **This is a required, outstanding QA step and it is an unmet acceptance
   criterion of the brief.**
2. ⛔ **No real payment was taken.** No order was placed through the checkout
   UI with a card. Every order in this evidence is a zero-total `pending`
   probe created and permanently deleted in the same run.
3. ⛔ **Production was not read.** Read-only production WP-CLI was blocked by
   this session's permission layer on two attempts. **Production's Bookvault
   webhook status is therefore UNKNOWN to this pass.** The exact verification
   command is in `07-seeding-commands.md` and must be run before production
   deployment.
4. ⚠ **Staging's four Bookvault webhooks are all `disabled`** (since
   2026-07-06). So "a normal order still pushes on staging's normal path"
   **could not be observed** — nothing pushes on staging. What was proven
   instead is the decision itself, at WooCommerce's own
   `woocommerce_webhook_should_deliver` filter, against the real webhook rows:
   pickup → `false`, normal → `true`, Rutter → untouched.
