# CYCLE162-LD-VISITS-PAGE — QA evidence

Theme **1.19.233** / bundle **1.8.51**, local commit `05e90f1` on
`feature/signup-modal-1.19.223`. **Staging only. Production untouched, unread and
unapproved. Nothing was pushed.**

| File | What it is |
|---|---|
| `01-test-baseline-BEFORE.txt` | Every suite in the theme and the plugin, run on staging at 1.19.232 / 1.8.50 immediately before any deploy. **65 pass / 9 fail / 17 failing assertions, 74 suites.** |
| `02-new-suite-test-author-visits-page-PASS.txt` | The new suite, run on staging after deploy. **65 assertions, all PASS, 0 skipped, rc=0.** |
| `03-rendered-states.txt` | The page's HTML as actually served, in all three states: the live registry, the missing-time / closed-cutoff / past-visit mix, and empty. |
| `04-links-schema-and-state-checks.txt` | Deployed versions, the page record, button-versus-entitlement agreement on the real slugs, link HTTP codes, the schema comparison, and an explicit list of what was NOT checked. |
| `06-test-baseline-AFTER.txt` | The same full run after deploy. **66 pass / 9 fail / 17 failing assertions, 75 suites.** |
| `07-seeding-commands.md` | The `time` field's shape, the applied staging command, and the PREPARED-NOT-APPLIED production commands. |

## The regression question, answered directly

**BEFORE:** 65 / 9 / 17 across 74 suites.
**AFTER:** 66 / 9 / 17 across 75 suites.

The suite count rose by one because this build added one, and it passes. **The nine
failing suites are the same nine, with the same 17 failing assertions, before and
after.** They are pre-existing and were pre-existing at 1.19.230 as well (see
`CYCLE162-pickup-qa-evidence/01-test-baseline-BEFORE.txt`). **This build introduced
none of them and fixed none of them.**

Two of the nine break the usual "rc means failure" assumption and are worth naming
so nobody re-derives them: `test-seo-hygiene.php` exits 0 but prints one FAIL line,
and `test-purchase-validation-harness.php` exits 1 while printing none.

## What is NOT in here, stated rather than omitted

- **No browser console check and no visual mobile check.** No browser-automation
  tool was available to this session. The page adds zero JavaScript (proved by
  script-tag counts identical to two other pages), so any console error would be
  pre-existing and site-wide, but that is an inference and it is labelled as one.
- **No live cart or checkout walk.** Nothing in this build touches cart, checkout,
  shipping, price, stock, coupon, tax or payment. `test-school-visit-pickup.php`
  passes unchanged in the AFTER run, which is what protects that claim.
- **Nothing was read from or written to production.**
