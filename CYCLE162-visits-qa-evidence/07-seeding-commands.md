# Visit registry seeding — the `time` field (1.8.51)

Extends `CYCLE162-pickup-qa-evidence/07-seeding-commands.md` (1.8.49). Read that
file first: the shape, the cutoff semantics and the withdraw/rollback paths are
unchanged and are not repeated here.

## What changed in the shape

One OPTIONAL key per row:

```json
{ "<slug>": { "school": "<display name>", "date": "YYYY-MM-DD", "cutoff": "YYYY-MM-DD", "time": "<display string>" } }
```

- `time` — **a display string, nothing else.** It is never parsed, never compared,
  never used to decide whether a visit is open or listed, and it is not sent to
  Mailchimp, to Bookvault, or into an order. Only `/author-visits/` renders it.
- **A row with no `time` is a COMPLETE row.** It survives sanitisation intact and
  renders date-only on the page, with no dangling separator. This is deliberate
  and asserted in `tests/test-author-visits-page.php`: the three visits already
  seeded on both environments predate the field, and making it required would
  have deleted all three the moment 1.8.51 shipped.
- It is tag-stripped, whitespace-collapsed, single-line and capped at 40
  characters, because it is echoed onto a public page.
- **No format is enforced.** `"8:50 AM"`, `"8:50–9:20 AM"` and `"right after
  lunch"` are all accepted. A format check would drop the third and then be
  loosened anyway.

⛔ **The time is deliberately NOT shown at checkout.** The shipping label a parent
reads while paying is approved copy and this build had no approval to reword it.
Checkout is byte-identical to 1.8.50.

---

## STAGING — APPLIED 2026-08-17, verified

```bash
ssh -i ~/.ssh/id_ed25519 -p <port> <user>@<host> \
  "cd <staging_doc_root> && wp option update bhp_school_visits '{\"adams-2026-08-28\":{\"school\":\"Adams Elementary\",\"date\":\"2026-08-28\",\"cutoff\":\"2026-08-25\",\"time\":\"8:50 AM\"},\"dallas-harris-2026-09-03\":{\"school\":\"Dallas Harris Elementary\",\"date\":\"2026-09-03\",\"cutoff\":\"2026-08-31\",\"time\":\"10:10 AM\"},\"liberty-2026-09-04\":{\"school\":\"Liberty Elementary\",\"date\":\"2026-09-04\",\"cutoff\":\"2026-09-01\",\"time\":\"9:00 AM\"}}' --format=json --autoload=yes --user=1"
```

Verify:

```bash
ssh ... "cd <doc_root> && wp option get bhp_school_visits --format=json --user=1"
ssh ... "cd <doc_root> && wp eval 'print_r(bhp_school_visit_records());' --user=1"
ssh ... "cd <doc_root> && wp eval 'print_r(bhp_author_visits_rows());' --user=1"
```

The three display names (`Adams Elementary`, `Dallas Harris Elementary`,
`Liberty Elementary`) were **already on staging when this build started** — they
were not invented by it. The 1.8.49 evidence file recorded them as bare slug
stems needing Andrew; someone has since expanded them. **They still need Andrew's
confirmation before production**, because they are rendered verbatim to a parent
on a public page, on the checkout label and in the E1 email.

⚠ **The three times above came from the build brief, relayed through the Chief of
Staff. They were NOT witnessed from Andrew by this agent.** They are now public
copy on a page that will be linked from print. Confirm them.

---

## PRODUCTION — ⛔ PREPARED, NOT APPLIED. Andrew's gate.

**Do not run any of this without Andrew's explicit, current-turn approval**, and
not before theme 1.19.233 / bundle 1.8.51 are approved and deployed there. The
1.8.49 evidence file's Step 0 pre-check (read production's Bookvault webhook
status) still applies and is still unperformed.

### If production has NOT yet been seeded at all

Use the 1.8.49 file's Step 1 and Step 2, with `"time"` added to each row and
Andrew's confirmed school names substituted:

```bash
ssh -i ~/.ssh/id_ed25519 -p <port> <user>@<host> \
  "cd <prod_doc_root> && wp option update bhp_school_visits '{\"adams-2026-08-28\":{\"school\":\"<ANDREW: EXACT NAME>\",\"date\":\"2026-08-28\",\"cutoff\":\"2026-08-25\",\"time\":\"8:50 AM\"},\"dallas-harris-2026-09-03\":{\"school\":\"<ANDREW: EXACT NAME>\",\"date\":\"2026-09-03\",\"cutoff\":\"2026-08-31\",\"time\":\"10:10 AM\"},\"liberty-2026-09-04\":{\"school\":\"<ANDREW: EXACT NAME>\",\"date\":\"2026-09-04\",\"cutoff\":\"2026-09-01\",\"time\":\"9:00 AM\"}}' --format=json --autoload=yes --user=1"
```

### If production HAS already been seeded — ⛔ READ THE OPTION FIRST

Do not blind-write over it. `wp option update` replaces the WHOLE option, so a
blind write silently discards any row or edit made on production since.

```bash
# 1. READ AND KEEP IT. Paste the output somewhere before touching anything.
ssh ... "cd <prod_doc_root> && wp option get bhp_school_visits --format=json --user=1"

# 2. Add "time" to each row of THAT json by hand, change nothing else, write it back.
ssh ... "cd <prod_doc_root> && wp option update bhp_school_visits '<the edited json>' --format=json --user=1"

# 3. Read it back and read the page.
ssh ... "cd <prod_doc_root> && wp eval 'print_r(bhp_author_visits_rows());' --user=1"
```

### Creating the page on production (also Andrew's gate)

```bash
ssh ... "cd <prod_doc_root> && wp post list --post_type=page --name=author-visits --format=csv --fields=ID,post_name --user=1"   # expect empty
ssh ... "cd <prod_doc_root> && wp post create --post_type=page --post_title='Author Visits' --post_name='author-visits' --post_status=publish --page_template='page-author-visits.php' --porcelain --user=1"
ssh ... "cd <prod_doc_root> && wp post meta get <new_id> _wp_page_template --user=1"   # must print page-author-visits.php
```

**Nothing is added to any nav menu.** Placement is Andrew's decision.

---

## Adding, changing or removing a time later — no deploy needed

Re-seed the whole option with the row edited. Effect is immediate on the next
request: nothing about a visit is cached anywhere, and `/author-visits/` re-reads
the option on every page view. Removing a `time` key reverts that row to
date-only rendering.
