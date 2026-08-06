# Taxonomy Repair & Prevention — Audit Record (2026-07-10)

This is a durable audit record of an authorized, completed taxonomy-repair
task. **It documents what was found and fixed — it is not an instruction to
restore the malformed state described below.** No credentials, database
secrets, or manuscript text are included in this file.

## What happened

A `wp post term set <id> <taxonomy> 'A,B,C'` command (a single comma-joined
string passed as one shell argument) does not split into separate terms.
WP-CLI's `<term>...` argument is variadic and requires separate
space-separated shell arguments. Passing one comma-joined string instead
silently created a single malformed term literally named with embedded
commas, on:

- **Production post 90** (`what-are-bridge-books-guide-for-parents-and-teachers`, published) — tags
- **Staging post 545** (SEL/STEM draft) — tags
- **Staging post 546** (Amazon facts draft) — categories AND tags

This reached production before being caught. All three were authorized for
repair in the same session that discovered them, plus a code-level
prevention mechanism (`BHP_Taxonomy_Safety`, see below) to stop this
failure mode from recurring.

## Repair: before / after

### Production post 90
| Field | Before | After |
|---|---|---|
| Category | 49 — Bridge Books | 49 — Bridge Books (unchanged, was already correct) |
| Tags | 204 — malformed single term `"bridge books for kids,picture books to chapter books,early chapter books"` | 97 — bridge books for kids; 90 — picture books to chapter books; 36 — early chapter books |
| Status | publish | publish (unchanged) |
| Content MD5 | `515bf457d36268e5096596fbfed1f3ae` | `515bf457d36268e5096596fbfed1f3ae` — identical, verified via two independent methods (`get_post_field()` and a direct SQL `SELECT`) |
| Rank Math title | unchanged | unchanged |

### Staging post 545 (SEL/STEM, draft)
- Category: 170 — STEM (pre-existing, untouched by this fix)
- Tags: malformed term 206 replaced with 73 (Books for kids ages 6-9), 85 (Reading Tips), 56 (stem books)
- Content unchanged (verified by MD5 before/after)

### Staging post 546 (Amazon facts, draft)
- Categories: malformed term 207 replaced with 148 (Adventure), 149 (science for kids) — explicitly **not** "Book recommendations," per this task's requirement that a facts article not be categorized as a book-recommendation list
- Tags: malformed term 208 replaced with 73 (Books for kids ages 6-9), 83 (Brave Hearts Publishing), 82 (Charlotte and Henry), 142 (facts for kids)
- Content unchanged (verified by MD5 before/after)

## Malformed terms — final disposition

Terms 204 (production, post_tag), 206 (staging, post_tag), 207 (staging,
category), 208 (staging, post_tag) were each confirmed to have **zero
remaining usages** before deletion, and were then permanently deleted.
Re-verified after deletion: `wp term get` returns "Term doesn't exist" for
all four. They are not recoverable and should not be recreated — the
gap_note in the rollback artifact (see below) documents that the exact
malformed name strings for the three staging-side terms were not preserved
verbatim, but the structural facts (post IDs, term IDs, taxonomy, and the
correct replacement term sets applied) are fully documented and are what
should be trusted going forward.

## Prevention mechanism added

`inc/class-bhp-taxonomy-safety.php` (`BHP_Taxonomy_Safety`) — resolves
requested category/tag values (names or numeric IDs) to existing term IDs
only, never creates a term, rejects any single requested value containing a
comma (the exact shape of this defect), assigns strictly by term ID via
`wp_set_object_terms()`, and reads the post back afterward to verify the
actual assigned term set matches what was requested before reporting
success. Covered by `tests/test-taxonomy-safety.php` (19 assertions,
proving: 3 requested tags become 3 assignments; a comma-joined string is
rejected with no partial write; a missing term ID/name fails rather than
auto-creating; term-replacement semantics prevent stray extra terms from
surviving undetected; the approved post-90 taxonomy set (49 / 97, 90, 36)
passes).

This class is **not yet wired into `functions.php`** and has not been
deployed to either environment's live theme build — it exists in source
control only pending a future, separately-approved deployment.

## Companion mechanical QA validators (same commit)

Also added and integration-tested on staging (74 total assertions across 4
suites, all passing): `BHP_Content_HTML_Sanitizer`, `BHP_CTA_Collision_Detector`,
`BHP_Classification_Completeness_Gate` — wired into the existing
`BHP_Content_QA_Gate::evaluate()`. Full root-cause detail for the defects
these were built to catch is in `docs/weekly-cycle-1-qa-failure-audit.md`.

## Rollback / structural reference

Full structural rollback data (post IDs, term IDs, taxonomy, replacement
term sets applied, and the explicit "not Book recommendations" exclusion
note for post 546) is preserved in this repo's session history and is
summarized in full above. No database names, hostnames beyond the two
public site URLs, or credentials are included in this file by design.

## Files changed / commit

Commit `3573134` on `feature/production-integration-1.17.1`:
`inc/class-bhp-taxonomy-safety.php`, `inc/class-bhp-content-html-sanitizer.php`,
`inc/class-bhp-cta-collision-detector.php`,
`inc/class-bhp-classification-completeness-gate.php`, four matching test
files, `docs/weekly-cycle-1-qa-failure-audit.md`,
`docs/weekly-slate-1-monitoring.md`,
`docs/bridge-books-consolidation-proposal.md`,
`docs/weekly-cycle-1-editorial-packet.md`,
`docs/weekly-cycle-1-pinterest-packet.md` (new), plus modifications to
`functions.php`, `inc/class-bhp-content-qa-gate.php`, `docs/PROJECT_STATE.md`.

Diff was reviewed for secrets/environment-specific values before commit —
none found.
