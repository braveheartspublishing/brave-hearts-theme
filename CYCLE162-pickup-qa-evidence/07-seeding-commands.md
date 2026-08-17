# Visit registry seeding — `bhp_school_visits`

The registry is a single WordPress site option. **No visit data exists anywhere
in source code**, and the test suite asserts that structurally.

Shape, one row per visit, keyed by slug:

```json
{ "<slug>": { "school": "<display name>", "date": "YYYY-MM-DD", "cutoff": "YYYY-MM-DD" } }
```

- `school` — rendered verbatim to the customer in the checkout label, the
  thank-you page and the E1 email. **This is customer-facing copy.**
- `date` — the visit date. Rendered as e.g. "September 3".
- `cutoff` — the last day an order may be placed. **Inclusive**: an order placed
  ON the cutoff date is still accepted; from the next day the option disappears.
- A row missing any of the three, or carrying an impossible date, is **dropped**
  by the sanitiser rather than defaulted. A slug not in the registry never
  resolves.

---

## STAGING — APPLIED 2026-08-17, verified

```bash
ssh -i ~/.ssh/id_ed25519 -p <port> <user>@<host> \
  "cd <staging_doc_root> && wp option update bhp_school_visits '{\"adams-2026-08-28\":{\"school\":\"Adams\",\"date\":\"2026-08-28\",\"cutoff\":\"2026-08-25\"},\"dallas-harris-2026-09-03\":{\"school\":\"Dallas Harris\",\"date\":\"2026-09-03\",\"cutoff\":\"2026-08-31\"},\"liberty-2026-09-04\":{\"school\":\"Liberty\",\"date\":\"2026-09-04\",\"cutoff\":\"2026-09-01\"}}' --format=json --autoload=yes --user=1"
```

Verify:

```bash
ssh ... "cd <doc_root> && wp option get bhp_school_visits --format=json --user=1"
ssh ... "cd <doc_root> && wp eval 'print_r(bhp_school_visit_records());' --user=1"
```

> ⚠ **THE THREE SCHOOL DISPLAY NAMES ABOVE ARE PLACEHOLDERS AND NEED ANDREW.**
> The brief supplied slugs (`adams-2026-08-28`, `dallas-harris-2026-09-03`,
> `liberty-2026-09-04`) and dates, but **not** the schools' display names.
> "Adams", "Dallas Harris" and "Liberty" are the bare stems of the slugs — they
> were used so staging QA had something real to render, and they were **not**
> expanded into "…Elementary School" or anything else, because inventing a
> school's name is on the never-invent list and it goes straight into a
> customer's checkout, thank-you page and inbox.
>
> **Andrew must supply the exact name he wants each school called** before
> production seeding.

---

## PRODUCTION — ⛔ PREPARED, NOT APPLIED. Andrew's gate.

**Do not run any of this without Andrew's explicit, current-turn approval, and
not before theme 1.19.231 / bundle 1.8.49 are approved and deployed there.**

### Step 0 — REQUIRED PRE-CHECK, run this first and read the answer

This pass could **not** read production (blocked at the permission layer), so
production's Bookvault webhook state is **unknown to this build**. The whole
duplicate-print protection acts on webhooks, so their real status must be
observed before anyone relies on it:

```bash
ssh -i ~/.ssh/id_ed25519 -p <port> <user>@<host> \
  "cd <prod_doc_root> && wp wc webhook list --user=1 --format=csv --fields=id,name,status,topic,resource,delivery_url"
```

Expected on production: one or more rows with `delivery_url` on
`webhooks.bookvault.app`. **Their `status` is the thing to read.** On staging all
four are `disabled`; if production's are `active`, that is the live push path and
the protection is doing real work from the first pickup order.

If a Bookvault webhook exists whose host is **not** `bookvault.app` /
`bookvault.com`, STOP and report it — the skip matches on host, and an unlisted
host would not be caught. The list is filterable via
`bhp_school_pickup_fulfilment_hosts`.

### Step 1 — confirm the option does not already exist

```bash
ssh ... "cd <prod_doc_root> && wp option get bhp_school_visits --user=1"
```
Expect: `Error: Could not get 'bhp_school_visits' option.` If it DOES exist,
**stop** — something already seeded it and it must be read before being replaced.

### Step 2 — seed (replace the three `school` values with Andrew's exact names)

```bash
ssh -i ~/.ssh/id_ed25519 -p <port> <user>@<host> \
  "cd <prod_doc_root> && wp option update bhp_school_visits '{\"adams-2026-08-28\":{\"school\":\"<ANDREW: EXACT NAME>\",\"date\":\"2026-08-28\",\"cutoff\":\"2026-08-25\"},\"dallas-harris-2026-09-03\":{\"school\":\"<ANDREW: EXACT NAME>\",\"date\":\"2026-09-03\",\"cutoff\":\"2026-08-31\"},\"liberty-2026-09-04\":{\"school\":\"<ANDREW: EXACT NAME>\",\"date\":\"2026-09-04\",\"cutoff\":\"2026-09-01\"}}' --format=json --autoload=yes --user=1"
```

### Step 3 — verify, then read the label a parent will actually see

```bash
ssh ... "cd <prod_doc_root> && wp eval 'print_r(bhp_school_visit_records());' --user=1"
ssh ... "cd <prod_doc_root> && wp eval 'foreach(bhp_school_visit_records() as \$r) echo bhp_school_pickup_label(\$r).PHP_EOL;' --user=1"
```

### Step 4 — the links to put in the pre-visit emails

```
https://braveheartspublishing.com/complete-collection/?bhp_visit=adams-2026-08-28
https://braveheartspublishing.com/complete-collection/?bhp_visit=dallas-harris-2026-09-03
https://braveheartspublishing.com/complete-collection/?bhp_visit=liberty-2026-09-04
```

The param works on **any** page on the site — it is captured on
`template_redirect` and is not scoped to a destination — so the landing page can
be changed later without touching code. A parent must land on the site with the
param once; the flag then persists in their WooCommerce session.

---

## Withdrawing or amending a visit

Re-seed the whole option with the row removed or changed. Effect is immediate on
the next request: nothing about a visit is ever cached in a session, only the
slug, and every fact is re-read from the option at call time. A customer already
holding a flag for a withdrawn visit silently stops being offered the option and
their flag self-clears.

## Removing the feature entirely without a code deploy

```bash
ssh ... "cd <doc_root> && wp option delete bhp_school_visits --user=1"
ssh ... "cd <doc_root> && wp eval 'echo WC_Cache_Helper::get_transient_version(\"shipping\", true);' --user=1"
```

With the option gone, every hook in `school-visit-pickup.php` returns its input
untouched and the store behaves exactly as it did at 1.19.230. This is the
fastest rollback and it needs no deploy.
