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
git -c core.autocrlf=false -c core.eol=lf archive --worktree-attributes \
  --format=zip --prefix=brave-hearts-theme-deploy-explorer-expedition-guides/ \
  -o /path/to/build.zip HEAD style.css style.min.css theme.json assets inc template-parts \
  content-engine docs tests woocommerce Brand-Soul-Audit.md CLAUDE.md \
  Homepage-Implementation-Notes.md Logo.jpg README.md Theme-Freeze.md $TOP_PHP \
  ':(exclude)assets/covers'
```

> ### ⛔ ADDED 2026-09-12 (`CYCLE180-LD-BUILD-412`) — `--worktree-attributes` IS NOT OPTIONAL
>
> **`export-ignore` is read from `.gitattributes` AS FOUND IN THE TREE-ISH BEING ARCHIVED — not
> from the working tree.** That is fine when archiving `HEAD` with `.gitattributes` committed. It
> is **silently wrong** in the two cases this project actually builds in:
>
> 1. **A temporary index** — the method this RUNBOOK recommends immediately below, and on this
>    project the tree running ahead of `HEAD` is the normal state. A temp index seeded with only
>    the deploy paths does **not** contain `.gitattributes`, so **every `export-ignore` line
>    stops applying** and the files they exclude ship.
> 2. **An uncommitted edit to `.gitattributes` itself** — the new rule does not take effect until
>    it is committed, so the build meant to test the exclusion does not test it.
>
> **What ships when it fails:** `docs/security-investigation-nlo-finance-redirect-2026-07-09.md`,
> which **quotes malware IOC strings** and **trips SiteGround's malware scanner on upload**
> (observed 2026-08-04). ⚠ `assets/covers/` is *not* the exposure here — it is excluded **twice**,
> by `export-ignore` and by the `':(exclude)assets/covers'` pathspec, and the pathspec has been
> doing the work. **The IOC document has only the `export-ignore`.**
>
> ⭐ **VERIFIED BY NEGATIVE CONTROL, 2026-09-12, not asserted.** Same temp index, the flag as the
> only variable:
>
> | build | IOC doc in ZIP |
> |---|---|
> | temp index without `.gitattributes`, no flag | **1 — it ships** |
> | same index, `--worktree-attributes` | **0 — excluded** |
>
> ### ⚠️ ADDED 2026-09-12 (`CYCLE180-LD-BUILD-413`) — STAGE **EVERY** DEPLOY PATH INTO THE TEMP INDEX
>
> **A path you forget to `git add` into the temp index is archived from `HEAD`, silently, at
> whatever version `HEAD` happens to carry.** It does not error and the ZIP looks normal.
>
> ⛔ **OBSERVED IN THIS BUILD, not hypothetical.** The temp index was seeded with the theme paths
> but **not** `plugins`. The plugin ZIP built from that tree therefore carried **1.8.91** — the
> version in `HEAD` — while the working tree held **1.8.93**. It installed cleanly, reported
> "Plugin updated successfully", and **downgraded staging by two versions.** Caught only because
> the post-install `wp plugin list` was read rather than assumed.
>
> **The rule:** the `git add` list and the `git archive` pathspec list must match, and the
> post-install version check is not a formality — it is the only thing that catches this.

**Build the bundle plugin's artefact from the same tree**, so theme and plugin can never come
from different commits:

```bash
git -c core.autocrlf=false -c core.eol=lf archive --worktree-attributes \
  --format=zip --prefix=brave-hearts-bundle-pricing/ \
  -o /path/to/plugin.zip "$TREE":plugins/brave-hearts-bundle-pricing . \
  ':(exclude)_pre-edit-backups*'
```

> **The deploy archive must be built from the WORKING TREE, not from `HEAD`.**
> `git ls-tree HEAD` and `git archive ... HEAD` read the last commit. On this project the
> tree routinely runs many versions ahead of the newest commit - on 2026-09-07 the tree was
> `1.19.400` while `HEAD` was `1.19.384` - so a `HEAD`-based archive builds a stale version
> **without erroring**. Build from the tree, or stage into a temporary index first.
>
> **Run the entry-list gate on every artefact.** Compare the ZIP's entry list against the
> expected theme set, and fail the build on any entry outside it. This gate caught all three
> artefact defects during the 1.19.389-400 run and is the reason the 1.19.400 ZIP could be
> asserted clean. It is a required step, not a nicety.
>
> **A check that cannot fail is not a check.** `deploy-399.sh`'s carriage-return scan ran with
> an empty pattern and reported a meaningless count; the artefacts happened to be clean, so
> nothing broke and nobody noticed. Assert that each guard produces a non-trivial result before
> trusting its verdict.
>
> ### ⛔⛔ ADDED 2026-09-12 (`CYCLE180-LD-BUILD-413`) — THE REHEARSAL mu-plugin MUST NEVER SHIP
>
> `wp-content/mu-plugins/bhp-rehearsal-testsku.php` is scaffolding created by
> `CYCLE180-LD-COLORING-MIGRATION-REHEARSAL`. It filters `bhp_colouring_product_ids` at
> **priority 99** to point the colouring catalogue at the staging **TEST SKU** `TEST000000001`
> instead of the real ISBN, so staging can hold the migrated state without the live ISBN ever
> being written onto a staging record.
>
> ⛔ **On production it would point the live catalogue at a product that does not exist.** It is
> **STAGING2 ONLY**. The two `unzip` assertions added to the gate block below exist so that it
> cannot reach an artefact even by accident — it lives outside the theme directory, so the
> normal pathspec would never pick it up, and the assertions make that a *checked* fact rather
> than a structural assumption.
>
> ⚠️ **IT ALSO MAKES STAGING SUITE RESULTS UNRELIABLE WHILE IT IS INSTALLED, AND THIS IS
> MEASURED, NOT SUSPECTED.** Its priority-99 filter runs **after** the priority-10 fixtures that
> the test suites use through the documented `bhp_colouring_product_ids` injection seam, so it
> **silently overwrites them**. Observed 2026-09-12: `test-cycle179-count-discount.php` injects
> the fixture id `9000001` and the mu-plugin replaced it with `19020`, failing 7 assertions.
> With the mu-plugin moved aside the same suite was **ALL PASS, exit 0**. ⭐ **Read any colouring
> suite failure on staging against this before treating it as a code regression.**
>
> **Removing it when the migration is finished is one command, and it is the last step:**
>
> ```bash
> ssh <user>@<host> -p <port> "rm <staging_docroot>/wp-content/mu-plugins/bhp-rehearsal-testsku.php"
> ssh <user>@<host> -p <port> "cd <staging_docroot> && wp sg purge --user=1"
> ```
>
> ⛔ **Do not remove it while staging is still holding the migrated rehearsal state for review** —
> without it the catalogue looks for the real ISBN, finds nothing, and the colouring book
> disappears from staging entirely.
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
unzip -l /path/to/build.zip | grep -c 'security-investigation-nlo' # MUST be 0  (1.19.412)
unzip -l /path/to/build.zip | grep -ci 'bhp-rehearsal-testsku'     # MUST be 0  (1.19.413)
unzip -l /path/to/build.zip | grep -c 'mu-plugins/'                # MUST be 0  (1.19.413)
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

> ⛔ CORRECTED 2026-09-05 (1.19.383): build the ZIP with `git -c core.autocrlf=false -c core.eol=lf archive ...`. On a Windows checkout with `core.autocrlf=true`, a plain `git archive` writes CRLF files into the ZIP, so the shipped style.css no longer matches the `source-md5:` stamp in style.min.css and the ship-prep suite fails. Verify after building: md5 of style.css extracted from the ZIP equals the stamp; 0 CR bytes in extracted text files; then php -l every PHP file out of the ZIP on the server before `wp theme install --force`.

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
  >
  > ### ⛔ CORRECTED 2026-09-13 (`CYCLE180-LD-BUILD-416`, finding `CYCLE180-LD-415-F1`) — "permanently and by design, not by an expired token" IS THE WRONG HALF OF THE SENTENCE
  >
  > **`wp eval` and `wp eval-file` are NOT permanently blocked against production.** They are classified as mutating verbs by `C:\BHP\.claude\hooks\gates\g1_production_write.py` (`MUTATING_WP`, last pattern) and are therefore blocked on production **exactly as long as Andrew's `PROD-UNLOCK` token is stale — and they run when it is fresh, inside its 60-minute window.** That is how `CYCLE180-LD-BUILD-415` diagnosed `CX-19` on production with `wp eval-file` on 2026-09-12.
  >
  > **Verified first-hand 2026-09-13, not inherited from the 415 report:** a read-only `wp eval 'echo "ok";'` against the production docroot was refused by the gate, and the gate's own message named the mechanism — *"Unlock state: token is STALE (76 min old, limit 60); touched 2026-09-12T22:01:04"*. The refusal was accepted; no workaround was attempted and no token was requested.
  >
  > ⭐ **THE STANDING DISPOSITION ABOVE IS UNCHANGED AND IS STILL CORRECT PRACTICE.** The token is Andrew's to touch and an agent never asks for it in order to run a test suite, so the suites still run on **staging** against the byte-identical artefact and production is still verified with read-only verbs plus a logged-out browser check. **What changes is the reason recorded, not the behaviour:** the previous wording taught the next reader that the block could never lift, which is why a real production diagnosis later read as impossible. **`CYCLE179-LD-002` remains OPEN and remains Andrew's** — an `ALLOWED_ON_PROD` allow-list of exact read-only eval strings would remove the need for a token at all, which is a different and better fix.
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

## Review-ask sequence and school-visit emails — staging QA and the production go-live gates (1.19.383)

⛔ **The engine sends nothing until `bhp_review_ask_enabled` is `yes`. That option
flip is Andrew's, and it is the only irreversible step in this document** — an
email that has gone to a parent cannot be recalled. Everything above it is
reversible.

`<slug>` is `brave-hearts-theme-deploy-explorer-expedition-guides`.
`<doc_root>` is the environment's WordPress root.

### A. Staging QA, in order (nothing here can email a customer)

⛔ **Step 0 is not optional and it is not new advice.** `wp theme install --force`
deletes the theme directory before it extracts, so a PHP parse error inside the
ZIP is an immediate HTTP 500 with no theme left to fall back to. That is exactly
what 1.19.378 did to staging.


`test-send` refuses to run anywhere but staging (the check is on `home_url()`,
not on `--url`), refuses without one valid `--to`, and refuses an unapproved set.

⚠ **Expect one large, benign byte diff on the first install of 1.19.379.** The
artefact is LF; earlier candidates on staging were CRLF. Nearly every text file
will report as changed. That is the line-ending correction landing once, toward
parity with production, not a regression. ⭐ Installing **1.19.383 over an
installed 1.19.379 or 1.19.380** must NOT reproduce it: all three artefacts are
LF, so the diff should be confined to the files each build actually changed. At
1.19.383 those are `inc/review-ask-email.php`, `inc/visit-completed-email.php`,
`tests/test-cycle179-review-seq.php`, `tests/test-visit-completed-email.php`,
`style.css` and `style.min.css` — six files. A wide diff on that install means the
build flags were dropped again.

### 0. Before anything else is judged
0a. `wp theme list --status=active` reports version **1.19.383**.
0b. `php -l` clean on every PHP file in the artefact — proven by step 0 above, not
    by spot-checking the files this release touched.
0c. All three suites green, with SKIPs read as skips.
0d. Object cache and page cache purged after the deploy, then re-verify 0a.

### 0B. The gates that decide WHO the first run may write to (1.19.380 + seal 1066/1064 at 1.19.383)
⛔ **These are read before any rendering is judged.** They are the difference
between a first run that writes to the orders the sequence was built for and one
that writes to a year of backlog.

0Ba. `wp bhp review-ask plan --dates=<the intended first send date>` prints a
     `backlog floor:` line naming the date in force. If it prints `NONE`, stop:
     the constant has been overridden or filtered off somewhere.
0Bb. Every order the floor excludes is named in that output as `DECLINED
     ... before_floor` with its resolved anchor. Read the anchors, not the
     count: a visit-lane order must report its VISIT date, a web-lane order its
     completion date.
0Bc. Every order whose only touch-1 record came from the retired 21-day engine
     reports `DECLINED ... legacy_touch1`. None of them may appear as
     `WOULD SEND touch 2`.
0Bd. The day ends with one `SUMMARY <date>: would_send=N; ...` line. Quote that
     line into the release record rather than re-deriving the counts.
0Be. ⭐⭐ **SEAL 1066 SETTLED THE FLOOR AND IT IS NOW A FACT, NOT A DEFAULT TO BE
     INHERITED QUIETLY.** `BHP_REVIEW_ASK_FLOOR_DATE` ships as `'2026-08-28'`,
     the Adams visit date, on both lanes. Andrew, asked to choose between the
     two candidates: *"Include them all"*, confirmed *"Yes"*. ⛔ The superseded
     candidate `'2026-09-03'` excluded the eight Adams orders from the sequence
     permanently and was rejected. **If the plan prints any floor other than
     `2026-08-28`, stop** — staging or `wp-config.php` is overriding the
     shipped constant and the run in front of you is not the ruled one.
0Bf. ⭐⭐ **AND THE CAP LINE IS PART OF THE SAME CHECK.** `plan` prints
     `daily cap: 20 visit lane | 10 web lane` before it evaluates any date, and
     each day ends with a per-lane comparison. On the intended first send date
     the visit line must read **`visit 13 of cap 20`** and the day must report
     **the whole day fits under both caps**. ⛔ If it reports orders slipping to
     the next day, the ruling is being half-applied by a filter and the enable
     gate is NOT met: the eight Adams parents and the five Dallas one-book
     parents were ruled to go together.
0Bg. The `SUMMARY` line now carries the lane split — `visit=n/cap` and
     `web=n/cap`. Quote the whole line; the totals alone cannot say whether a
     day fits.
0Bh. ⭐ **Seal 1064 — read one rendered day-0 email before enabling.** The body
     must contain *"The places are real. So are the animals, the weather and the
     science. The adventures are made up; the world they happen in is not."* and
     must **not** contain the phrase *"all of it is true"* in any wording. The
     old sentence claimed everything in the books was true; there is a talking
     dog. The suite pins this both ways, but the rendered email is the check
     that matters.

### 1. Rendering — check the SOURCE, not only the screenshot
1a. Day 0, touch 1, touch 2 and web touch 1 all render, each with its hero (touch
    2 has none, by design).
1b. Exactly one greeting per email. No "Hi X, Hi X,".
1c. Exactly one sign-off per email: the signature block, three lines, in order —
    Andrew Signore / Author | Brave Hearts Publishing / Big Places. Brave Hearts.
    No plain-text tagline above or below it.
1d. No "we", "us" or "our" anywhere in customer-facing words.
1e. No em dash (U+2014) anywhere.
1f. **The charset first, because it invalidates every visual check below it.** In
    the SMTP plugin's own log, open the touch-1 test-send and read the raw
    `Content-Type` header: it must read `text/html; charset=UTF-8`. ⛔ If the
    charset is missing, STOP — the stars will arrive as `âââââ` and nothing
    else on this list can be judged.
1g. The star row renders as five stars, resting pale gold `#dfc793`, with its
    accessible label, in Gmail web, Gmail iOS and Apple Mail. A row of question
    marks or boxes means the charset did not reach the wire.
1g-ii. Hover, on a desktop client that runs CSS: the row fills to `#c4a15c` from
    the left up to the star under the pointer. **In a client that ignores
    `<style>` the row simply stays pale and every link still works** — an accepted
    outcome, not a failure.
1h. **No empty heading band.** View the RAW message and confirm there is no `<h1`
    anywhere in it, and that the hero touches the top of the card with no cream
    gap. ⛔ 1.19.372 passed the eyeball and still had the element and its 20px
    band in the HTML.
1i. **Then send yourself an ordinary processing-order and completed-order email
    and confirm their headings are STILL THERE.** The same code path runs on
    every WooCommerce email in the store.
1j. With images blocked, the same email still shows five stars (they are
    characters, not images), the caption and "Or open the review page". The hero
    becomes its alt text — read it and confirm it describes the photograph and
    its baked caption.
1k. Star 1 goes to `?rating=1` and star 5 to `?rating=5`. Click both and read the
    query string on the landing page.
1l. At 375px the order and downloads tables are readable, left-aligned, wrapped,
    with their column headings intact.

### 1m. Every book on the order is named (seal 1032)
1m-i.   Place a staging visit order holding **two** chapter books and preview
        touch 1. The paragraph must read "… has had The Mariana Trench and Mount
        Everest for a week and a half now." — both titles, "and", no comma.
1m-ii.  Repeat with **three** books: "The Mariana Trench, Mount Everest and The
        Amazon". ⛔ There is no comma before "and". That is deliberate and
        matches the day-0 email.
1m-iii. On both, the caption under the star row names **ONE** book — the first —
        and the "Or open the review page" link goes to **that same** book's
        review page. ⛔ Caption and destination naming different books is worse
        than either being wrong alone.
1m-iv.  Preview the **web** lane at two books. The second clause must read "They
        went out in the mail, so I never got to see who opened them." ⛔ "It went
        out" about two books is the regression this gate exists for.
1m-v.   Preview a **one-book** order in both lanes and confirm it is unchanged
        from 1.19.374, word for word.

### 1n. The day-0 receipt on a phone, and the sentence that names the books (seal 1042)
1n-i.   Open the day-0 email at **375px**. The totals block heading reads exactly
        "Hand delivery:" — nothing after the colon — and the pickup label appears
        ONCE, in the cell beside it. This gate has now caught the same defect in
        two consecutive builds (1.19.374, 1.19.376), each time because the fix was
        applied to the row's `label` while the duplicate was being printed from
        its `meta`. **Read the RENDER, not the callback.** An ordinary web receipt
        must still show its shipping method name in this heading — check one.
1n-ii.  At a 375px viewport, read `document.scrollWidth` in the client's own
        inspector, or scroll the receipt sideways with a finger. It must not move.
        At 1.19.376/1.19.377 it was 553px against a 375px viewport, and the cause
        was NOT the table width — it was `white-space: nowrap` on the totals row's
        amount cell, which holds the 70-character hand-delivery sentence rather
        than a price. **Before adding `nowrap` to any cell in
        `.email-order-details`, check what that cell actually contains in a REAL
        render.** The class is shared by three tables with three different
        payloads.
1n-iii. In the same render, "Quantity", "Price" and every "$" amount sit on ONE
        line each. ⛔ A word split across two lines ("Qu / antity") means the
        blanket word-break came back.
1n-iv.  The product name still wraps normally, at word boundaries, and the
        Downloads table still shows both live links, left-aligned.
1n-v.   Check the same render at **600px** — the narrow-screen work lives inside a
        `≤480px` media query and must be invisible above it.
1n-vi.  Place a staging visit order with **two** chapter books and preview day 0.
        The paragraph must read "… why The Mariana Trench and Mount Everest
        **are** built the way **they are**, because **they are** built for one
        particular kid." Repeat at **three** books. Preview a **one-book** order
        and confirm that paragraph is unchanged from 1.19.375, word for word.
1n-vii. ⛔ A visit order whose books cannot be resolved must still send NOTHING.
        The send gate moved from `{BookTitle(s)}` onto `{WhyBuiltLine}` in
        1.19.376.

### 2. Photographs
2a. An order with `_bhp_school_visit_slug = dallas-harris-2026-09-03` renders
    hero 01 on day 0 and hero 04 on touch 1.
2b. An order with `_bhp_school_visit_slug = adams-2026-08-28` renders
    `hero-adams-2026-08-28-01.jpg` on day 0 and `-02.jpg` on touch 1, each with
    alt text naming Adams Elementary and August 28, 2026.
2c. An order with NO visit slug, and every web-lane ask, renders
    `BHP_EMAIL_GENERAL_HERO` — as of seal 1027 that is
    `hero-read-aloud-general-adams.jpg`, the Adams library.
    ⛔ Seeing a different picture there means someone changed the constant.
2d. Every hero has non-empty alt text. No child named, no reaction described.
2e. `read-aloud-dallas-harris-2026-09-03-05.jpg` is absent from the theme and
    named by no mapping.

### 3. The day-0 order summary
3a. The hand-delivery row's label reads `Hand delivery:` and nothing more.
3b. Its value is the approved pickup label alone, left-aligned, on one or two
    lines — not the pickup name followed by a forty-word paragraph.
3c. Subtotal, tax and total are unchanged, in the same order, with the same
    values.
3d. ⛔ **Then place an ordinary web order and confirm its shipping row is exactly
    as it was in 1.19.373** — "Shipping:" and the flat rate, untouched.

### 4. Content truth
4a. The day-0 body makes no shipping, tracking or arrival claim.
4b. Every review link resolves to the correct ASIN.
4c. No unconfirmed founder specific appears anywhere ("Island Peak", "Jiri",
    "20,000 feet", "without oxygen").
4d. No review, rating, testimonial or aggregate score is asserted anywhere.

### 5. Schedule and cap, still on staging
5a. The dry runs produce the four dates: touch 1 on 09-10 (Dallas one-book),
    09-11 (Liberty one-book), 09-13 (Dallas multi), 09-14 (Liberty multi), and
    touch 2 four days after each. **No order may be declined `daily_cap_lane`**
    on any of those days.
5b. `wp bhp review-ask status` reports the engine **DISABLED** at the end of QA.

### B. Production go-live, exact commands in order

**Steps 0 to 3 change no behaviour: the engine is off, so installing the theme
ships inert code.** Step 4 is the live one.


### C. ⛔ ANDREW'S GATE — the only irreversible command in this document

**Do not run this on inference, on a prior approval, or on "it is a small
change". It needs Andrew's explicit, current approval, and it is the last
command in the go-live sequence.**


### D. Immediately after enabling — prove the runner is actually scheduled

**An enabled engine with no scheduled run sends nothing and reports no error.
Check this in the same sitting as the flip, not the next morning.**


### E. Rollback


**⛔ Rolling the code back does NOT unsend an email.** The option flip is the real
stop; the tarball only restores the previous behaviour for future runs.

### F. What this runbook deliberately does not do

- It does not run `wp bhp review-ask migrate`. Seal 994 made the sixteen visit
  orders ordinary engine orders, and the command now refuses all sixteen by id.
  Marking any of them would suppress touch 1 forever.
- It does not touch any WooCommerce product, price, coupon, stock, shipping, tax,
  payment or checkout setting. The engine reads orders and writes two order meta
  keys plus its own options; nothing else. The day-0 receipt change is a
  render-time override of one table cell in one email — no shipping method, zone,
  rate, title or pickup location is created or edited.
- It does not enable anything on its own. Section C is the only switch, and it is
  Andrew's, per-action and per-session.

## Book rail placement - `_bhp_book_rail_position`

The theme auto-injects a book rail into post content. Before `1.19.388` it could land between a
numbered list item's title and its first paragraph, splitting the entry (observed on post 46,
item 8, at both 375 and 1440).

**What the guard does.** The injector refuses insertion points that fall inside a numbered entry
(directly after a paragraph that begins with a number and a period, or after a heading) and moves
the rail to the next legal boundary.

**Per-post override.** Post meta `_bhp_book_rail_position` pins the rail explicitly (an integer
paragraph index, or `off`).

    wp post meta get <post_id> _bhp_book_rail_position
    wp post meta update <post_id> _bhp_book_rail_position <value>
    wp post meta delete <post_id> _bhp_book_rail_position   # restore automatic placement

**When to use it.** Only when automatic placement is wrong on a specific post. Prefer fixing the
guard over pinning individual posts; a pin is invisible to the next editor.

**Verify after changing it.** Load the post in a real browser at 375 and 1440, assert
`window.innerWidth` in-page before trusting the viewport, and confirm the rail sits clear of every
numbered entry. `curl` proves the shell loads, not that placement is correct.

**Owner:** `lead-developer`. **Introduced:** theme `1.19.388`, 2026-09-06.
