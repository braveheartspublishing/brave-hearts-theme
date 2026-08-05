#!/usr/bin/env node
/**
 * Brave Hearts — CSS build step (CYCLE144-LD-218, 2026-08-05, theme 1.19.201).
 *
 * ⛔ THIS FILE IS A DEV-TIME TOOL AND IS DELIBERATELY *NOT* IN THE DEPLOY ZIP.
 *    `docs/RUNBOOK.md`'s `git archive` list does not include `tools/`, and it
 *    must not: `CYCLE141-LD-21` records that a ZIP built from tracked files is a
 *    superset that ships internal files onto a public web server. The ARTEFACTS
 *    it produces (`*.min.css`) are what deploy; the builder stays here.
 *
 * WHAT IT DOES, AND — MORE IMPORTANTLY — WHAT IT REFUSES TO DO.
 *
 * It strips comments and collapses runs of blank lines. THAT IS ALL. It does
 * not collapse whitespace inside rules, does not remove the last semicolon of a
 * block, does not touch selectors, values, at-rules, `calc()` expressions,
 * strings or `url()`s.
 *
 * That restraint is a MEASURED decision, not timidity. On `style.css` at
 * 1.19.201, gzipped over the wire:
 *
 *     raw                        94,022 bytes
 *     comments stripped          39,014 bytes   <-- this build
 *     fully collapsed            37,593 bytes
 *
 * Stripping comments alone captures 55.0 KB of a possible 56.4 KB — 97.5% of
 * the entire available win. Full collapsing buys 1.4 KB more and, on a
 * 7,700-line stylesheet, brings real risk: a naive collapser breaks
 * `calc(100% - 10px)`, mangles quoted strings and can eat a space that a
 * descendant selector depends on. 1.4 KB is not worth that trade, and writing
 * the number down here is what stops a future pass from "improving" this into
 * a bug.
 *
 * This codebase writes essay-length CSS comments on purpose — they are the
 * record of why rules exist and which specificity wars they settled. They are
 * enormously valuable in the repository and pure dead weight over a 1.47 Mbps
 * mobile link. Stripping them at build time keeps both.
 *
 * THE THEME HEADER COMMENT IS PRESERVED VERBATIM in `style.min.css`. WordPress
 * parses `Version:` out of `style.css` (which is untouched), but a stylesheet
 * with no attribution at all is worse than one with it.
 *
 * INTEGRITY: each artefact records the md5 of the source it was built from.
 * `tests/test-style-minification.php` re-computes that hash on the server and
 * FAILS if they diverge — so editing a `.css` without rebuilding is caught by
 * the test suite rather than shipping stale styles to a customer.
 *
 * Usage:  node tools/build-css.mjs        # build
 *         node tools/build-css.mjs --check  # verify freshness, exit 1 if stale
 */

import { readFileSync, writeFileSync, existsSync, readdirSync } from 'node:fs';
import { createHash } from 'node:crypto';
import { gzipSync } from 'node:zlib';
import { join, dirname, basename } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');
const CHECK_ONLY = process.argv.includes('--check');

/**
 * ⚠ LINE ENDINGS ARE NORMALISED BEFORE HASHING, AND THIS IS NOT COSMETIC.
 *
 * FOUND BY DEPLOYING, NOT BY REASONING. The first version of this build hashed
 * the raw bytes of each source file. It passed locally and FAILED on staging for
 * six of ten stylesheets, with the artefact and the source reported as diverged
 * when they were in fact identical.
 *
 * The cause: this repository is checked out on Windows, `git archive` applies
 * the `text` attribute when it exports, and the ZIP therefore contains CRLF for
 * files that are LF in the working tree. The bytes differ; the STYLESHEET does
 * not. An integrity check that fires on that is worse than no check at all —
 * it trains whoever sees it to ignore a red suite.
 *
 * Normalising CRLF -> LF makes the hash a statement about CONTENT, which is what
 * the check is actually trying to assert. `tests/test-style-minification.php`
 * performs the identical normalisation, and the two must always agree.
 */
const normalise = (s) => s.replace(/\r\n/g, '\n');
const md5 = (s) => createHash('md5').update(normalise(s)).digest('hex');
const gz = (s) => gzipSync(Buffer.from(s), { level: 9 }).length;

/**
 * Strip comments without ever looking inside a string or a url().
 *
 * A regex-only strip is wrong here: `content: "/*"` and
 * `url(data:image/svg+xml;...)` both contain sequences that a naive
 * `/\/\*[\s\S]*?\*\//g` will happily treat as comment delimiters, silently
 * deleting real declarations. This walks the file once and tracks whether it is
 * inside a single-quoted string, a double-quoted string, or a comment.
 */
function stripComments(css, keepFirstComment) {
  let out = '';
  let i = 0;
  let inSingle = false;
  let inDouble = false;
  let keptFirst = !keepFirstComment;

  while (i < css.length) {
    const c = css[i];
    const next = css[i + 1];

    if (!inSingle && !inDouble && c === '/' && next === '*') {
      const end = css.indexOf('*/', i + 2);
      const stop = end === -1 ? css.length : end + 2;
      if (!keptFirst) {
        out += css.slice(i, stop); // the theme header, verbatim
        keptFirst = true;
      }
      i = stop;
      continue;
    }

    if (!inDouble && c === "'" && css[i - 1] !== '\\') inSingle = !inSingle;
    else if (!inSingle && c === '"' && css[i - 1] !== '\\') inDouble = !inDouble;

    out += c;
    i++;
  }

  return out;
}

function build(relPath) {
  const srcPath = join(ROOT, relPath);
  const source = readFileSync(srcPath, 'utf8');
  const hash = md5(source);

  const isRootStylesheet = relPath === 'style.css';
  let body = stripComments(source, isRootStylesheet);

  // Collapse runs of blank lines. Nothing else about whitespace is touched.
  body = body.replace(/\n[ \t]*(?:\n[ \t]*)+/g, '\n').replace(/^\s+/, '');

  const banner =
    `/*! Built from ${basename(relPath)} by tools/build-css.mjs — DO NOT EDIT.\n` +
    `    source-md5: ${hash}\n` +
    `    Comments stripped for transfer size; the source file is canonical. */\n`;

  const outRel = relPath.replace(/\.css$/, '.min.css');
  const outPath = join(ROOT, outRel);
  const built = banner + body;

  if (CHECK_ONLY) {
    if (!existsSync(outPath)) return { relPath, ok: false, why: 'artefact missing' };
    const current = readFileSync(outPath, 'utf8');
    const recorded = (current.match(/source-md5:\s*([0-9a-f]{32})/) || [])[1];
    return { relPath, ok: recorded === hash, why: recorded === hash ? '' : 'source changed since last build' };
  }

  // Brace balance is the cheap structural assertion that catches a bad strip.
  const count = (s, ch) => (s.match(ch) || []).length;
  if (count(built, /\{/g) !== count(built, /\}/g)) {
    throw new Error(`${relPath}: brace imbalance after stripping — refusing to write`);
  }

  writeFileSync(outPath, built, 'utf8');
  return {
    relPath,
    outRel,
    ok: true,
    raw: source.length,
    min: built.length,
    gzRaw: gz(source),
    gzMin: gz(built),
  };
}

const targets = ['style.css'].concat(
  readdirSync(join(ROOT, 'assets', 'css'))
    .filter((f) => f.endsWith('.css') && !f.endsWith('.min.css'))
    .map((f) => join('assets', 'css', f).replace(/\\/g, '/'))
);

let failed = false;
for (const t of targets) {
  const r = build(t);
  if (CHECK_ONLY) {
    console.log(`${r.ok ? 'FRESH' : 'STALE'}  ${r.relPath}${r.why ? '  (' + r.why + ')' : ''}`);
    if (!r.ok) failed = true;
  } else {
    console.log(
      `${r.relPath.padEnd(34)} ${String(r.raw).padStart(7)} -> ${String(r.min).padStart(7)} bytes` +
        `   gzip ${String(r.gzRaw).padStart(6)} -> ${String(r.gzMin).padStart(6)}`
    );
  }
}
if (failed) process.exit(1);
