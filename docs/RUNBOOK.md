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
standing-authorized within a Gandalf-approved build brief and do not
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
  Homepage-Implementation-Notes.md Logo.jpg README.md Theme-Freeze.md $TOP_PHP
```
The `--prefix` must exactly match the active theme's slug or the install
creates a new, inactive theme instead of replacing the live one.

**Then assert the artefact BEFORE installing it. This step is not optional:**
```bash
unzip -l /path/to/build.zip | tail -2                              # >= the previous artefact
unzip -l /path/to/build.zip | grep -c '[\\]'                       # MUST be 0
unzip -l /path/to/build.zip | grep -cE 'woocommerce/|tests/test-'  # MUST be >= 23
unzip -l /path/to/build.zip | grep -c 'content-engine/'            # MUST be >= 23
unzip -l /path/to/build.zip | grep -c '\.min\.css'                 # MUST be 10
unzip -l /path/to/build.zip | grep -c 'tools/'                     # MUST be 0
md5sum /path/to/build.zip                                          # record it in the release doc
```
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
