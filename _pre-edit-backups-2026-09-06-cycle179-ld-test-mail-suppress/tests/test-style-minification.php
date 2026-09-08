<?php
/**
 * Brave Hearts — the CSS build artefacts must match their sources.
 *
 * CYCLE144-LD-218 (2026-08-05, theme 1.19.201).
 *
 * ⛔ THIS SUITE EXISTS BECAUSE A BUILD STEP WAS INTRODUCED, AND A BUILD STEP
 *    WITHOUT A FRESHNESS CHECK IS A BUG WAITING TO SHIP.
 *
 * `bhp_minified_style_src()` in `functions.php` serves `foo.min.css` in place of
 * `foo.css` whenever the artefact exists. That is a large transfer win — 54.9 KB
 * gzipped on `style.css` alone — and it introduces exactly one new failure mode:
 * somebody edits a `.css` file, does not run `node tools/build-css.mjs`, and the
 * site serves the OLD styles while the repository shows the new ones. The edit
 * appears to have "not worked", the developer edits again, and the real cause is
 * invisible because both files look right in isolation.
 *
 * Each artefact records the md5 of the source it was built from. This suite
 * recomputes that hash from the source ON THE SERVER, against the files actually
 * deployed, and fails loudly when they diverge. It is deliberately a runtime
 * test rather than a pre-commit hook: what matters is what is ON the server, and
 * a hook cannot see that.
 *
 * ⚠ IF THIS SUITE FAILS, THE FIX IS `node tools/build-css.mjs` FOLLOWED BY A
 *   REBUILD OF THE DEPLOY ZIP — never editing the `.min.css` by hand. The
 *   artefacts carry a DO NOT EDIT banner for the same reason.
 *
 * Run:
 *   wp eval-file wp-content/themes/<slug>/tests/test-style-minification.php --user=1
 * Exits non-zero on any failure.
 *
 * @package brave-hearts
 */

defined('ABSPATH') || exit;

$failures = 0;

function bhp_min_assert(&$failures, $label, $condition) {
    if ($condition) {
        echo "PASS: {$label}\n";
        return;
    }
    $failures++;
    echo "FAIL: {$label}\n";
}

$theme_dir = get_template_directory();

/*
 * Discovered rather than hard-coded: a future stylesheet added to assets/css/
 * is covered automatically, instead of being silently exempt from the check.
 */
$sources = array('style.css');
foreach ((array) glob($theme_dir . '/assets/css/*.css') as $path) {
    if (substr($path, -8) === '.min.css') {
        continue;
    }
    $sources[] = 'assets/css/' . basename($path);
}

bhp_min_assert($failures, 'at least the root stylesheet and the assets/css sheets were discovered', count($sources) >= 2);

$checked = 0;
$missing = array();

foreach ($sources as $rel) {
    $src_path = $theme_dir . '/' . $rel;
    $min_rel  = substr($rel, 0, -4) . '.min.css';
    $min_path = $theme_dir . '/' . $min_rel;

    if (!file_exists($src_path)) {
        bhp_min_assert($failures, "source exists: {$rel}", false);
        continue;
    }

    /*
     * A MISSING artefact is NOT a failure. `bhp_minified_style_src()` falls back
     * to the source file, so the page is styled correctly and merely larger.
     * Failing here would block a legitimate "add a stylesheet, build later"
     * sequence for no safety benefit. It is reported, not failed.
     */
    if (!file_exists($min_path)) {
        $missing[] = $rel;
        continue;
    }

    /*
     * ⚠ CRLF IS NORMALISED AWAY BEFORE HASHING, AND THE BUILDER DOES THE SAME.
     *   `md5_file()` was the obvious call and it was WRONG: this repository is
     *   checked out on Windows and `git archive` applies the `text` attribute on
     *   export, so the deployed ZIP carries CRLF for files that are LF in the
     *   working tree. Hashing raw bytes reported six of ten stylesheets as
     *   diverged from artefacts that were in fact built from exactly that
     *   content — caught on the first staging run of this suite, not in review.
     *
     *   A false alarm in an integrity check is worse than no check: it teaches
     *   whoever sees it to ignore a red suite. The hash is a statement about
     *   CONTENT, so line endings must not enter it.
     */
    $expected = md5(str_replace("\r\n", "\n", (string) file_get_contents($src_path)));
    $built    = file_get_contents($min_path);

    $recorded = preg_match('/source-md5:\s*([0-9a-f]{32})/', $built, $m) ? $m[1] : '';

    bhp_min_assert(
        $failures,
        "{$min_rel} records the md5 of the source it was built from",
        $recorded !== ''
    );
    bhp_min_assert(
        $failures,
        "{$min_rel} is CURRENT with {$rel} (run `node tools/build-css.mjs` if this fails)",
        $recorded === $expected
    );

    /*
     * Structural sanity, cheap and worth having: a comment strip that ate a
     * brace would produce a stylesheet the browser silently mis-parses from that
     * point on. Balanced braces will not catch every possible mangle, but it
     * catches the one this build step could actually cause.
     */
    bhp_min_assert(
        $failures,
        "{$min_rel} has balanced braces",
        substr_count($built, '{') === substr_count($built, '}')
    );

    bhp_min_assert(
        $failures,
        "{$min_rel} is smaller than its source (the build did something)",
        strlen($built) < filesize($src_path)
    );

    $checked++;
}

if (!empty($missing)) {
    echo "NOTE: no build artefact for: " . implode(', ', $missing)
        . " — these serve their source file, which is correct but larger.\n";
}

/*
 * The root stylesheet is the one that actually matters for LCP, so it is
 * asserted specifically rather than being left to the loop above.
 */
bhp_min_assert(
    $failures,
    'style.min.css exists — it is the single largest render-blocking asset on the site',
    file_exists($theme_dir . '/style.min.css')
);

/*
 * The theme header must survive into the artefact. WordPress reads `Version:`
 * from style.css (untouched), but an unattributed stylesheet on a public server
 * is worse than an attributed one, and the builder promises to keep it.
 */
if (file_exists($theme_dir . '/style.min.css')) {
    $root_min = file_get_contents($theme_dir . '/style.min.css');
    bhp_min_assert(
        $failures,
        'style.min.css preserves the theme header comment',
        strpos($root_min, 'Theme Name: Brave Hearts Publishing') !== false
    );
    bhp_min_assert(
        $failures,
        'style.min.css carries the DO NOT EDIT banner',
        strpos($root_min, 'DO NOT EDIT') !== false
    );
}

echo "Checked {$checked} stylesheet(s).\n";

if ($failures > 0) {
    WP_CLI::error("{$failures} CSS build-artefact test(s) failed.");
}
WP_CLI::success('All CSS build-artefact tests passed.');
