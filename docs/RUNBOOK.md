# Runbook — Brave Hearts Theme

Copy-paste Windows/Git Bash commands for common tasks. All server access
is via SSH + WP-CLI (see auto-memory `reference-bhp-siteground` for the
exact host/port/user — not repeated here since it's not a repo file).

**Corrected finding (2026-07-17):** a prior session incorrectly reported
theme deployment as blocked because the browser's `file_upload` tool
(claude-in-chrome / Claude_Browser) is restricted to files already shared
with that session. **That tool is irrelevant to normal theme deployment
and always has been** — every deploy in this project's history, before
and after that incident, has gone through SSH + `scp` + WP-CLI exactly as
documented below, never a browser file picker. Re-verified 2026-07-17:
`ssh` connects with the documented key, `scp` moves a real ZIP to the
server's `/tmp`, and `wp theme install --force` installs it — all
confirmed working end to end. Under owner decision G-40 (2026-08-02),
local builds and remote writes to **staging** (`scp`, staging
`wp theme install --force`, staging cache purge and staging QA) are
standing-authorized within a `chief-of-staff`-approved build brief and do not
require repeated per-command approval. Production remote writes remain
gated by Andrew's explicit current-turn approval. Read-only checks remain
permitted on either environment when relevant and safe.

## Session-start checklist
```bash
cd "C:\BHP\brave-hearts-theme"
git status --short
git log --oneline -10
git branch --show-current
```
Then confirm the live active theme before assuming anything:
```bash
ssh -i ~/.ssh/id_ed25519 -p <port> <user>@<host> \
  "cd <doc_root> && wp theme list --status=active --format=table --user=1"
```

## Build and deploy a theme ZIP (staging first, always)
**Every deploy ZIP must contain the theme's complete file set, never a subset.** `wp theme install --force` deletes the existing theme directory ("Removing the old version of the theme...") before extracting the new one — confirmed 2026-07-13. A ZIP containing only the changed files will delete everything else on install. If production has drifted from the git repo (check first — see `KNOWN_ISSUES.md` for a real example found 2026-07-13), build the ZIP from a fresh snapshot of production's own live files with only the approved files patched, not from the repo, so unrelated drift isn't silently reintroduced or erased.
> ### ⛔ CORRECTED 2026-08-03 — the previous line here produced a ZIP that DELETED LIVE FILES
>
> **The superseded line archived only `style.css theme.json assets inc template-parts $TOP_PHP`, which produces 180 files. The real artefact is 356 entries.** The difference is `docs/` (135), `tests/` (20), `woocommerce/` (2) and six top-level files.
>
> **Why that is destructive rather than merely incomplete:** `wp theme install --force` **deletes the theme directory before extracting**. Installing the short ZIP would have **removed the theme's WooCommerce template overrides and both test suites from the live site** — silently, with the install reporting success.
>
> Caught during the 1.19.154 build by diffing two ZIPs' file lists before uploading. **Never diagnosed after the fact; by then the files are gone.**

> ### ⛔ CORRECTED AGAIN 2026-08-05 (`ACT-OPS-130`) — the list still omitted `content-engine/`, and it is LIVE on both environments
>
> **The 2026-08-03 correction above fixed `docs/`, `tests/` and `woocommerce/` and stopped there. `content-engine/` — 23 tracked files — was still missing, and it is PRESENT in the live theme directory.**
>
> **Verified live 2026-08-05, not inferred:** `ls` of the active theme directory on staging lists `content-engine` alongside `assets`, `inc` and `template-parts`. Because `wp theme install --force` **deletes the theme directory before extracting**, a ZIP built from the superseded line would have **silently removed the entire content engine from the live site**, exactly as the short ZIP would have removed the WooCommerce overrides and both test suites.
>
> **`style.min.css` is also added.** From theme 1.19.201 the root stylesheet is served from a comment-stripped build artefact (`bhp_minified_style_src()`); omitting it does not break the site — the filter falls back to `style.css` — but it silently gives up 54.9 KB of the gain.
>
> ⛔ **`tools/` IS DELIBERATELY ABSENT AND MUST STAY ABSENT.** It holds the CSS builder, which is a dev-time tool. `CYCLE141-LD-21` records that a ZIP built from tracked files is a superset that ships internal files onto a public web server. **Artefacts deploy; builders do not.**
>
> **The superseded line is preserved immediately below rather than deleted, so the movement stays visible.**
>
> ```bash
> # SUPERSEDED 2026-08-05 — omits content-engine/ (23 files, live on both environments)
> git archive --format=zip --prefix=brave-hearts-theme-deploy-explorer-expedition-guides/ \
>   -o /path/to/build.zip HEAD style.css theme.json assets inc template-parts \
>   docs tests woocommerce Brand-Soul-Audit.md CLAUDE.md Homepage-Implementation-Notes.md \
>   Logo.jpg README.md Theme-Freeze.md $TOP_PHP
> ```

**Rebuild the CSS artefacts first — the deploy ships them, not the sources:**
```bash
cd "C:\BHP\brave-hearts-theme"
node tools/build-css.mjs           # writes style.min.css and assets/css/*.min.css
node tools/build-css.mjs --check   # every line must read FRESH
```

```bash
cd "C:\BHP\brave-hearts-theme"
TOP_PHP=$(git ls-tree HEAD --name-only | grep '\.php$')
git archive --format=zip --prefix=brave-hearts-theme-deploy-explorer-expedition-guides/ \
  -o /path/to/build.zip HEAD style.css style.min.css theme.json assets inc template-parts \
  content-engine docs tests woocommerce Brand-Soul-Audit.md CLAUDE.md \
  Homepage-Implementation-Notes.md Logo.jpg README.md Theme-Freeze.md $TOP_PHP \
  ':(exclude)assets/covers'
```
The `--prefix` must exactly match the active theme's slug or the install
creates a new, inactive theme instead of replacing the live one.

> ### ⛔ ADDED 2026-09-02 (`CYCLE179-LD-350`) - `assets/covers/` must be excluded, and the `assets` path above pulls it in
>
> `assets` is listed wholesale, so a plain `git archive` sweeps in **`assets/covers/`: 117 tracked
> print-source and proof masters, roughly 500 MB**, referenced by **zero** PHP, JS or CSS files and
> present on **neither** environment. A repo-built ZIP was silently adding all 117 to staging until
> theme 1.19.343 caught it by byte-diffing the deployed theme against the pre-deploy backup.
>
> The exclusion is now in the command above as a pathspec, `':(exclude)assets/covers'`. It is verified
> working on `git version 2.49.0.windows.1`; on an older git that does not honour exclude pathspecs in
> `git archive`, build the list without `assets` and name the real asset subdirectories instead.
>
> ⭐ **The rule this makes explicit is the one `tools/` already follows: artefacts deploy, sources do
> not.** The assertion `grep -c 'assets/covers/'  # MUST be 0` below is what proves it on each build.
>
> ⚠ **Do not widen this exclusion.** `assets/look-inside/` (33 files, 3.8 MB, added by theme 1.19.349)
> is a **deployed** asset directory and rides inside the same `assets` path. An exclusion written as
> `assets/cover*` or as a broad `assets/*-sources` would drop it and blank the product pages.

**Then assert the artefact BEFORE installing it. This step is not optional:**
```bash
unzip -l /path/to/build.zip | tail -2                              # >= the previous artefact
unzip -l /path/to/build.zip | grep -c '[\\]'                       # MUST be 0
unzip -l /path/to/build.zip | grep -cE 'woocommerce/|tests/test-'  # MUST be >= 23
unzip -l /path/to/build.zip | grep -c 'content-engine/'            # MUST be >= 23
unzip -l /path/to/build.zip | grep -c '\.min\.css'                 # MUST be >= 14
unzip -l /path/to/build.zip | grep -c 'tools/'                     # MUST be 0
unzip -l /path/to/build.zip | grep -c 'assets/covers/'             # MUST be 0
md5sum /path/to/build.zip                                          # record it in the release doc
```
> ### ⛔ CORRECTED 2026-09-02 (`CYCLE178-LD-DOCS-SYNC`, applied `CYCLE179-LD-350`) - the minified-CSS assertion was an equality on a stale number, and it fails a CORRECT build
>
> **The superseded line read `# MUST be 10`, and it is preserved here rather than deleted:**
>
> ```bash
> # SUPERSEDED 2026-09-02 - the repository tracks 14 minified stylesheets, not 10
> unzip -l /path/to/build.zip | grep -c '\.min\.css'                 # MUST be 10
> ```
>
> **Verified live 2026-09-02, not inferred:** `git ls-files '*.min.css'` at HEAD returns **14** paths,
> being **13 under `assets/css/`** plus **`style.min.css`**. A correctly built artefact therefore failed
> this gate, and this runbook's documented response to a failed gate is to **stop and investigate a build
> that is in fact correct**. That is the more expensive failure: it teaches the next builder to distrust a
> passing artefact.
>
> ⭐ **It is corrected to a FLOOR (`>= 14`) rather than to an exact number**, which is the same form the
> two assertions directly above it already use (`>= 23` twice). The floor still catches the failure this
> assertion exists for, a build that **silently dropped** artefacts, because a dropped stylesheet takes
> the count below the floor. What it no longer does is go stale the next time a stylesheet is **added**,
> which is exactly how the `10` became wrong. This file's own note immediately below says the same thing
> about the superseded "expect 356 entries" figure: **a fixed number goes stale and then gets corrected
> downward by someone trusting it.** An equality assertion is that failure with a hard edge on it.
>
> ⚠ **The working tree is already at 15**, not 14: `assets/css/pdp-content.min.css` was added by theme
> 1.19.349 and is untracked at the time of writing, so `git ls-files` counts 14 while a working-tree
> build counts 15. **Both satisfy the floor. That is the point of a floor**, and it is why this was not
> corrected to an exact 14 or an exact 15.
>
> ⚠ **The `10` also survives, correctly, in the note below** ("the ten `*.min.css` build artefacts"),
> where it is describing the **1.19.201-era artefact** as a historical accounting. That sentence is
> scoped to a past build and is **not** an assertion about a current one, so it is left alone.
> **⚠ The "expect 356 entries" figure is superseded and is preserved in this note rather than left inline, where it would be read as a target.** It was correct for the 1.19.154-era artefact. The 1.19.201 artefact is **462 entries**, and the growth is accounted for: `content-engine/` (31), the ten `*.min.css` build artefacts, four mobile image variants and the docs added since. **A fixed number goes stale and then gets "corrected" downward by someone trusting it — compare against the PREVIOUS ARTEFACT and against the live file count instead.**
>
> **The assertion that actually protects you:** count files in the live theme directory over SSH and confirm the ZIP is a superset. At 1.19.201 that was 404 live files against 414 file entries in the ZIP.
An entry count materially below the previous artefact's means files are
missing, and `--force` will delete them from the target environment.
Compare against the **previously deployed** file list, not against
`git ls-files` — a ZIP built from tracked files is a *superset* and ships
internal files onto a public web server (`CYCLE141-LD-21`).

```bash
scp -i ~/.ssh/id_ed25519 -P <port> /path/to/build.zip <user>@<host>:/tmp/build.zip
ssh -i ~/.ssh/id_ed25519 -p <port> <user>@<host> \
  "cd <staging_doc_root> && wp theme install /tmp/build.zip --force --user=1"
```
Repeat against production doc root only after Andrew's explicit approval
and staging verification.

## Purge SiteGround cache after a deploy
```bash
ssh -i ~/.ssh/id_ed25519 -p <port> <user>@<host> "cd <doc_root> && wp sg purge --user=1"
```

## Verify checkout shipping after any shipping-related change
Real browser required (Store API/React-driven cart+checkout — `curl`
cannot verify this). Test matrix: 1 paperback / 2 paperbacks / mixed
titles to a Contiguous-US address (expect exactly $3.99, no USPS/UPS
line), and one unsupported destination like Hawaii (expect "No shipping
options available", never a false $3.99). Check browser console for
errors.

## Invalidate stale WooCommerce shipping-rate cache without any settings change
```bash
ssh -i ~/.ssh/id_ed25519 -p <port> <user>@<host> \
  "cd <doc_root> && wp eval 'echo WC_Cache_Helper::get_transient_version(\"shipping\", true);' --user=1"
```

## Recovery: theme installed as a new inactive theme instead of replacing the active one
```bash
ssh -i ~/.ssh/id_ed25519 -p <port> <user>@<host> "cd <doc_root> && wp theme list --user=1"
ssh -i ~/.ssh/id_ed25519 -p <port> <user>@<host> "cd <doc_root> && wp theme delete <wrong-slug> --user=1"
```
Then rebuild the ZIP with the correct `--prefix` and reinstall.

## Run the Kirkus credibility component test suite (no PHPUnit exists in this theme)
```bash
ssh -i ~/.ssh/id_ed25519 -p <port> <user>@<host> \
  "cd <doc_root> && wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-kirkus-component.php --user=1"
```
Exits non-zero on any failure. Run this after any deploy that touches
`functions.php`'s Kirkus functions or `template-parts/components/kirkus-credibility.php`.

**Always pass `--url=<site-url>` to every `wp eval-file tests/...` run** (added 2026-09-02,
CYCLE179-LD-9). Under WP-CLI `$_SERVER['HTTP_HOST']` is unset, so any suite that routes
through `BHP_Analytics_Config::is_staging()` (`inc/class-bhp-analytics-config.php`) takes the
wrong branch without it and reports a phantom failure or a phantom pass. Baselines and
comparisons are only valid when both runs used the same `--url`.

## After any CSS/JS-only change: bump the theme Version, not just the file
`wp_enqueue_style`/`script` cache-bust off `wp_get_theme()->get('Version')`
(the `Version:` header in `style.css`). Skipping the bump can leave
already-loaded browser sessions on stale CSS/JS even after a full
`wp theme install --force` and `wp sg purge` — confirmed directly during
the Kirkus component work (see `DECISIONS.md`). Bump the version as part
of the same commit as any visual/behavioral change, not as an afterthought.

## Run the Amazon customer review showcase test suite
```bash
ssh -i ~/.ssh/id_ed25519 -p <port> <user>@<host> \
  "cd <doc_root> && wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-amazon-review-showcase.php --user=1"
```
Exits non-zero on any failure. Run this (and the Kirkus test suite, as a
regression check) after any deploy that touches `inc/amazon-reviews.php`
or `template-parts/components/amazon-review-showcase.php`.

## Building a deploy ZIP by hand (before anything is committed yet)
The normal flow (`git archive --format=zip HEAD ...`) only works once the
new files are committed. If you need to test on staging before creating
the commit (e.g. one dedicated commit is planned for the whole feature),
build the ZIP directly from the working tree instead -- but if doing this
on Windows via PowerShell, do NOT use `Compress-Archive` or
`ZipFile.CreateFromDirectory` directly: both can produce backslash-
separated entry paths, which extract as literal-backslash filenames on
the Linux server instead of real subdirectories (confirmed via `unzip -l`
during the Amazon review phase — see `DECISIONS.md`). Build the archive
entry-by-entry instead, forcing forward slashes:
```powershell
Add-Type -AssemblyName System.IO.Compression
$zip = [System.IO.Compression.ZipFile]::Open($destZip, [System.IO.Compression.ZipArchiveMode]::Create)
Get-ChildItem -Path $srcRoot -Recurse -File | ForEach-Object {
    $relativePath = $_.FullName.Substring($srcRoot.Length + 1).Replace('\', '/')
    $entry = $zip.CreateEntry($relativePath, [System.IO.Compression.CompressionLevel]::Optimal)
    $entryStream = $entry.Open()
    $fileBytes = [System.IO.File]::ReadAllBytes($_.FullName)
    $entryStream.Write($fileBytes, 0, $fileBytes.Length)
    $entryStream.Close()
}
$zip.Dispose()
```
Always verify with `unzip -l build.zip | grep <a-new-file>` on the server
**before** running `wp theme install`, so a bad ZIP is caught before it's
installed, not after.

## Git workflow and branch strategy
- Integration branch: `feature/production-integration-1.17.1` (name is historical — this branch's HEAD is what's live on production; it now contains far more than the 1.17.1 work its name suggests).
- **Never branch from `main`** — it has drifted stale from production before (see the "Branch from exact production commit" decision above). Always branch from the verified current production commit hash (`wp theme list --status=active` on production, then match that commit in `git log`).
- Divergent feature lines get combined onto a fresh integration branch via `git cherry-pick` from the exact production commit — never a blind merge of two branches that both claim to be "current."
- Commit only when explicitly asked. Prefer one dedicated commit per completed, tested feature over many small provisional commits, unless the work is naturally incremental (e.g., a multi-day phase).

## Backup procedures (before any production write)
- **Theme files**: `wp theme install --force` is atomic and versioned via WordPress itself — no separate backup step needed for a full-ZIP deploy, but a `tar.gz` snapshot of the live theme directory is good practice before a major version jump.
- **Individual files patched in place** (narrow patches, not full ZIP): always copy the live file to `<filename>.rollback-<feature>-<timestamp>` in the same directory *before* writing, and verify the rollback copy is byte-identical to the pre-write file (`diff`) before proceeding.
- **Plugin files**: same pattern — timestamped `.rollback-<feature>-<timestamp>` copies before any write.
- Record every backup's exact path in the release's documentation (see `RELEASES/` for examples).

## Emergency rollback
1. Confirm the exact rollback file(s) exist and are byte-identical to what you expect (don't trust a stale doc — check the live file timestamps/checksums).
2. Copy each rollback file back over its live counterpart.
3. Purge SiteGround cache (`wp sg purge`).
4. Confirm no fatal: `wp eval 'echo "ok";'`.
5. Confirm the intended-to-be-reverted behavior is actually gone (don't just trust the file copy succeeded — re-check the live rendered page/behavior).
6. Document what was rolled back and why in `KNOWN_ISSUES.md` or a dated incident doc, whichever fits.

## Production verification checklist (after any production deploy)
- `wp theme list --status=active` (or `wp plugin get <slug>`) confirms the expected version is live.
- `wp eval 'echo "ok";'` confirms no PHP fatal.
- Relevant `wp eval-file tests/test-*.php` suites pass on production itself, not just staging.
  > ### ⛔ 2026-09-02 (`CYCLE179-LD-002`, recorded by `CYCLE179-LD-350`) - THIS LINE CANNOT BE SATISFIED BY AN AGENT. The post-deploy suite runs on STAGING.
  >
  > The `PreToolUse` gate `G1-PRODUCTION-WRITE` blocks `wp eval` and `wp eval-file` against the
  > production doc root **permanently and by design**, not by an expired token. Its own reasoning:
  > both run arbitrary PHP against the live database, so a read and a write are **not distinguishable
  > by inspecting the command**. `wp theme list`, `wp plugin list`, `wp option get` and the other
  > read-only verbs are **not** blocked and still run.
  >
  > ⭐ **Standing disposition until Andrew rules:** the `wp eval-file` suites are run on **staging**
  > against the byte-identical artefact (same ZIP md5), and the production checks are the read-only
  > verbs above plus a real logged-out browser smoke test. **State that substitution in the release
  > record rather than reporting a production suite run that did not happen** (Standing Rules §3, §9.2).
  >
  > ⚠ **This line is left standing rather than rewritten**, because it describes the verification the
  > project actually wants. **`CYCLE179-LD-002` is OPEN and is Andrew's.** The fix the gate itself
  > proposes is an explicit allow-list of exact read-only eval strings, added by him.
- SiteGround cache purged.
- A real, logged-out browser smoke test of the changed area — `curl` proves the page shell loads, not that cart/checkout/JS-driven behavior works.
- No new entries in `php_errorlog` since the deploy.

## Amazon customer review maintenance workflow
Adding, updating, or retiring an approved Amazon review excerpt is a
deliberate, manual process — there is no scraper and none should be
built (Andrew's explicit instruction). To add a new review:
1. Verify the review is still publicly visible on the book's real Amazon
   product/review page (not cached, not from memory).
2. Confirm which book it actually belongs to — never assume from a
   series page or a merged-edition view.
3. Select a short excerpt preserving the original meaning (no combining
   multiple reviews, no rewriting into a fake direct quote).
4. Confirm attribution: omit the reviewer's name by default (use "Amazon
   customer review"), record the individual star rating and Verified
   Purchase status **only if Amazon visibly shows them on that exact
   review**, and use the book's real product/review page as the source URL.
5. Add the entry to `bhp_get_amazon_review_registry()` in
   `inc/amazon-reviews.php` with `'approved' => false` initially.
6. Run `tests/test-amazon-review-showcase.php` — it must still pass with
   the new entry present but unapproved (i.e. not yet rendering).
7. Flip `'approved' => true`, rerun the tests, and deploy to staging only.
8. Andrew reviews the staging rendering.
9. Only after Andrew's explicit approval, deploy to production the same
   way as any other theme change (full-ZIP `wp theme install --force`,
   staging-verified first).

## Review-ask engine — staging QA and the production go-live gates (1.19.382)

**The engine sends nothing until `bhp_review_ask_enabled` is `yes`. That option
flip is Andrew's, and it is the only irreversible step in this list** — an email
that has gone to a parent cannot be recalled. Everything above it is reversible.

`<slug>` below is `brave-hearts-theme-deploy-explorer-expedition-guides`.
`<doc_root>` is the environment's WordPress root.

### A. Staging QA, in order (nothing here can email a customer)

```
# 1. install the candidate
wp theme install /path/to/brave-hearts-theme-1.19.382-review-seq.zip --force
wp theme list --status=active                 # must show <slug> at 1.19.382
wp sg purge

# 2. fatal check
wp eval 'echo "ok";' --user=1

# 3. the three suites
wp eval-file wp-content/themes/<slug>/tests/test-cycle179-review-seq.php --user=1
wp eval-file wp-content/themes/<slug>/tests/test-cycle169-review-ask.php --user=1
wp eval-file wp-content/themes/<slug>/tests/test-visit-completed-email.php --user=1

# 4. the schedule, read-only, on each of the four launch dates
wp bhp review-ask dry --as-of=2026-09-10
wp bhp review-ask dry --as-of=2026-09-11
wp bhp review-ask dry --as-of=2026-09-13
wp bhp review-ask dry --as-of=2026-09-14
wp bhp review-ask status

# 5. one rendered email per set, into a mailbox somebody will actually open
wp bhp review-ask test-send --to=<address> --set=day0   --order=<visit order id>
wp bhp review-ask test-send --to=<address> --set=touch1 --order=<visit order id>
wp bhp review-ask test-send --to=<address> --set=touch2 --order=<visit order id>
wp bhp review-ask test-send --to=<address> --set=web1   --order=<web order id>
```

`test-send` refuses to run anywhere but staging (the check is on `home_url()`,
not on `--url`), refuses without one valid `--to`, and refuses an unapproved set.

### B. What Gandalf must verify on staging BEFORE the option is discussed

0. **⭐⭐ THE CHARSET, AND CHECK IT FIRST BECAUSE IT INVALIDATES EVERY OTHER
   VISUAL CHECK BELOW IT.** In FluentSMTP → Email Logs, open the touch-1
   test-send and read the raw `Content-Type` header. It must read
   `text/html; charset=UTF-8`. **If the charset is missing, stop** — the stars
   will arrive as `âââââ` and nothing else on this list can be judged.
   Then confirm in the delivered message that the five stars are stars and not
   mojibake. *(Shipped 1.19.371, carried forward unchanged through 1.19.382. The defect was observed on the 1.19.369 send.)*
1. **The star row is a row.** Open the touch-1 test-send on a phone and on
   desktop: five stars, one line, left to right, no wrapping at 375px.
   **They rest GREY (`#c9c2b3`), not gold** — that is the designed resting
   state, not a bug.
1b. **Hover, on a desktop client that runs CSS** (Apple Mail, or the browser
   preview): moving the mouse across the row turns stars gold from the left up
   to the one under the pointer. **In a client that ignores `<style>` the row
   simply stays grey and every link still works** — that is an accepted
   outcome, not a failure. ⚠ Not verified in any mail client by the build.
1c. **The hero photograph.** Touch 1 and day 0 carry one 536px photograph with
   its caption baked in; **touch 2 carries none.** A visit order whose slug is
   not in the map, and every web order, must show the general read-aloud frame
   — **never a frame captioned for a school the reader did not attend.**
1d. **The signature block** below the rule reads `Andrew Signore` /
   `Author | Brave Hearts Publishing` / `Big Places. Brave Hearts.` **There is
   no Facebook or Instagram line, and that is deliberate: no real URL for
   either exists in the repository and none was invented.** See the open item
   in the deliverable.
2. **With images blocked**, the same email still shows five stars (they are
   characters now, not images), the caption and "Or open the review page".
   **The hero becomes its alt text** — read it and confirm it describes the
   photograph and its caption.
3. **Star 1 goes to `?rating=1` and star 5 to `?rating=5`** — click both and
   read the query string on the landing page.
4. **The caption names the book** ("Tap a star to rate The Amazon."), not
   "Tap a star to rate ." — an empty title there means the merge gate should
   have declined the order and did not.
5. **Names.** Test-send touch 1 for order 612 (two children) and read the
   opening sentence: it must say "<A> and <B> **have** had", never "has had".
6. **Day 0 names the school** from `_bhp_school_visit_school` and carries no
   Adams fact (no grade band, no headcount, no coloring page, no book read
   aloud) and no blank paragraph where `{VisitLine}` would be.
7. **The dry runs produce the four dates**: touch 1 on 09-10 (Dallas one-book),
   09-11 (Liberty one-book), 09-13 (Dallas multi), 09-14 (Liberty multi), and
   touch 2 four days after each. **No order may be declined `daily_cap_lane`**
   on any of those days.
8. **`wp bhp review-ask status` reports the engine DISABLED** at the end of QA.

### C. Production go-live, exact commands in order

**Steps 1 to 3 change no behaviour: the engine is off, so installing the theme
ships inert code.** Step 4 is the live one.

```
# 0. ROLLBACK ARTEFACT FIRST. Do not skip.
cd <doc_root>/wp-content/themes
tar -czf ~/PROD-theme-PRE-1.19.382-$(date +%Y%m%d-%H%M).tar.gz <slug>
wp option get bhp_review_ask_enabled                 # record the answer verbatim
wp option get bhp_review_ask_stats  > ~/PRE-369-review-ask-stats.json
wp option get bhp_review_ask_log    > ~/PRE-369-review-ask-log.json

# 1. install and confirm it replaced the LIVE theme rather than adding one
wp theme install /path/to/brave-hearts-theme-1.19.382-review-seq.zip --force
wp theme list --status=active                        # <slug>, 1.19.382
wp eval 'echo "ok";' --user=1
wp sg purge

# 2. confirm the engine is still OFF after the install
wp bhp review-ask status                             # must read disabled

# 2b. THE SIGNATURE BLOCK'S SOCIAL LINE. Production has no bhp_social_links
#     option yet, so the signature block ends on the brand line and NO social
#     link is emitted. These are the two real URLs, already set on staging.
#     ⛔ Nothing here is invented: supply exactly these, or leave the option
#     unset and accept a signature with no social line. Do this BEFORE step 4.
wp option get bhp_social_links                       # record the answer verbatim (likely: option not set)
wp option update bhp_social_links '[{"label":"Facebook","url":"https://www.facebook.com/braveheartspublishing"},{"label":"Instagram","url":"https://www.instagram.com/charlotteandhenrybooks"}]' --format=json
wp option get bhp_social_links --format=json         # read it back; two entries
wp sg purge
#     ⚠ VERIFY IT REACHES THE EMAIL, not just the option table:
wp eval 'print_r( bhp_review_ask_signature() );' --user=1
#     Expect name, role, brand and a `social` array of exactly two entries.

# 3. READ-ONLY dry runs on production, for the two Dallas dates
wp bhp review-ask dry --as-of=2026-09-10
wp bhp review-ask dry --as-of=2026-09-13
#    Read the "would send" lines. They name order ids only.
#    STOP HERE and get Andrew's word before step 4.

# 4. ⛔ ANDREW'S GATE — the only irreversible command in this runbook
wp option update bhp_review_ask_enabled yes
wp bhp review-ask status                             # must now read enabled

# 5. the scheduler must actually be scheduled
wp cron event list --fields=hook,next_run_relative | grep bhp_review_ask_daily
#    If Action Scheduler owns it instead:
wp action-scheduler list --hook=bhp_review_ask_daily --status=pending
#    If neither shows an entry, the daily runner is not scheduled and nothing
#    will send. Re-run bootstrap by loading any admin page, then re-check.

# 6. first live morning, watch rather than assume
wp bhp review-ask status
wp bhp review-ask plan --dates=2026-09-10,2026-09-11,2026-09-13,2026-09-14
```

### D. Rollback

```
# fastest, and it stops all sending in one command
wp option update bhp_review_ask_enabled no
wp bhp review-ask status                             # must read disabled

# the social line alone, if that is the only thing to undo
wp option delete bhp_social_links                    # signature block ends on the brand line again

# full code rollback
cd <doc_root>/wp-content/themes
rm -rf <slug>
tar -xzf ~/PROD-theme-PRE-1.19.382-<stamp>.tar.gz
wp theme list --status=active
wp sg purge
```

**⛔ Rolling the code back does NOT unsend an email.** The option flip in D is
the real stop; the tarball only restores the previous behaviour for future runs.

### E. What this runbook deliberately does not do

- It does not run `wp bhp review-ask migrate`. Seal 994 made the sixteen visit
  orders ordinary engine orders, and the command now refuses all sixteen by id.
  Marking any of them would suppress touch 1 forever.
- It does not touch any WooCommerce product, price, coupon, stock, shipping,
  tax, payment or checkout setting. The engine reads orders and writes two
  order meta keys plus its own options; nothing else.
