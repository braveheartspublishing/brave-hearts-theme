/**
 * Banner-visibility behaviour harness (theme 1.19.309,
 * `CYCLE167-LD-CONSENT-BANNER-GEO`).
 *
 * Executes the region-gate decision function EXACTLY as it was rendered into
 * a real page -- extracted from fetched HTML, never read from the source
 * file -- and asserts the decision in BOTH directions across a fixture table.
 *
 * Usage (no dependencies, plain node, nothing installed):
 *   node tests/js/consent-region-harness.js /tmp/page.html
 *
 * Exits non-zero on any failure.
 *
 * ⭐ WHY IT READS RENDERED HTML rather than inc/consent-banner-compact.php:
 * the gate is emitted inline by PHP, so the source file is not what the
 * browser runs -- and a source-level grep is exactly the stale-assertion
 * defect corrected in the 1.19.177 wave1 suite. This is the same discipline
 * tests/js/consent-bridge-harness.js already uses, deliberately.
 *
 * ⛔ WHAT THIS CANNOT PROVE, stated so a pass is not over-quoted: it proves
 * the DECISION FUNCTION is correct for a given (timezone, languages) pair. It
 * cannot prove any particular real visitor reports a particular timezone, and
 * it does not touch the suppression wiring -- that is observed in a real
 * browser and recorded in the QA log.
 */
const fs = require('fs');
const vm = require('vm');

const path = process.argv[2];
if (!path) { console.error('usage: node consent-region-harness.js <page.html>'); process.exit(2); }
const html = fs.readFileSync(path, 'utf8');

// Pull the gate <script> block out of the rendered page by its id.
const m = html.match(/<script id="bhp-consent-region-gate">([\s\S]*?)<\/script>/);
if (!m) { console.error('FAIL: no #bhp-consent-region-gate script found in rendered HTML'); process.exit(1); }
const gate = m[1];

let failures = 0;
function assert(label, cond) {
  console.log((cond ? 'PASS: ' : 'FAIL: ') + label);
  if (!cond) failures++;
}

/**
 * Boot the gate against a stubbed browser with an injected timezone and
 * language list, and hand back the resulting window.
 *
 * The gate reads its inputs through Intl and navigator, so those are what is
 * stubbed -- NOT the pure function's arguments. That means this harness
 * exercises the REAL input path (readZone/readLangs) as well as the decision,
 * which is the half a direct function call would skip.
 */
function boot(tz, langs, opts) {
  opts = opts || {};
  const ctx = vm.createContext({});
  ctx.window = ctx;
  ctx.console = console;
  ctx.Array = Array;
  ctx.Intl = opts.breakIntl
    ? { DateTimeFormat: function () { throw new Error('Intl unavailable'); } }
    : { DateTimeFormat: function () { return { resolvedOptions: function () { return { timeZone: tz }; } }; } };
  ctx.navigator = { languages: langs, language: (langs && langs[0]) || undefined };
  ctx.document = { addEventListener: function () {} };
  ctx.window.addEventListener = function () {};
  vm.runInContext(gate, ctx);
  return ctx;
}

// ---------------------------------------------------------------------
// 1. THE TABLE. Every row is a real IANA zone and a real locale list.
//    `true` = the bar must SHOW.
// ---------------------------------------------------------------------
const table = [
  // --- must SHOW: EEA + UK -------------------------------------------
  ['Europe/Berlin',        ['de-DE'],        true,  'Germany'],
  ['Europe/Paris',         ['fr-FR'],        true,  'France'],
  ['Europe/London',        ['en-GB'],        true,  'United Kingdom'],
  ['Europe/Dublin',        ['en-IE'],        true,  'Ireland'],
  ['Europe/Madrid',        ['es-ES'],        true,  'Spain'],
  ['Europe/Rome',          ['it-IT'],        true,  'Italy'],
  ['Europe/Amsterdam',     ['nl-NL'],        true,  'Netherlands'],
  ['Europe/Stockholm',     ['sv-SE'],        true,  'Sweden'],
  ['Europe/Warsaw',        ['pl-PL'],        true,  'Poland'],
  ['Europe/Lisbon',        ['pt-PT'],        true,  'Portugal mainland'],
  ['Atlantic/Reykjavik',   ['is-IS'],        true,  'Iceland (EEA, non-Europe/ prefix)'],
  ['Atlantic/Azores',      ['pt-PT'],        true,  'Portugal, Azores'],
  ['Atlantic/Madeira',     ['pt-PT'],        true,  'Portugal, Madeira'],
  ['Atlantic/Canary',      ['es-ES'],        true,  'Spain, Canary Islands'],
  ['Asia/Nicosia',         ['el-CY'],        true,  'Cyprus (EU, Asia/ prefix)'],
  ['Asia/Famagusta',       ['el-CY'],        true,  'Cyprus, Famagusta'],
  ['Africa/Ceuta',         ['es-ES'],        true,  'Spain, Ceuta'],
  ['America/Cayenne',      ['fr-GF'],        true,  'French Guiana (EU outermost)'],
  ['America/Martinique',   ['fr-MQ'],        true,  'Martinique (EU outermost)'],
  ['Indian/Reunion',       ['fr-RE'],        true,  'Reunion (EU outermost)'],
  ['Antarctica/Troll',     ['nb-NO'],        true,  'Norwegian Antarctic station'],

  // --- must SHOW: ambiguous / location-free ---------------------------
  ['UTC',                  ['en-US'],        true,  'UTC -- Tor / resistFingerprinting'],
  ['GMT',                  ['en-US'],        true,  'GMT -- location-free'],
  ['Etc/UTC',              ['en-US'],        true,  'Etc/UTC -- location-free'],
  ['Etc/GMT+5',            ['en-US'],        true,  'Etc/GMT offset zone'],
  ['',                     ['en-US'],        true,  'empty timezone'],
  [undefined,              ['en-US'],        true,  'undefined timezone'],

  // --- must SHOW: non-EEA zone but EEA locale (secondary signal) ------
  ['America/New_York',     ['de-DE','en-US'], true, 'traveller: US zone, German locale'],
  ['Asia/Tokyo',           ['en-GB'],         true, 'traveller: JP zone, UK locale'],

  // --- must NOT show: the US and rest of world ------------------------
  ['America/Denver',       ['en-US'],        false, 'United States, Denver'],
  ['America/New_York',     ['en-US'],        false, 'United States, New York'],
  ['America/Los_Angeles',  ['en-US','es'],   false, 'US, bare es must NOT trigger'],
  ['America/Chicago',      ['en'],           false, 'US, bare en'],
  ['America/Phoenix',      ['fr'],           false, 'US, bare fr must NOT trigger'],
  ['America/Toronto',      ['en-CA'],        false, 'Canada'],
  ['America/Sao_Paulo',    ['pt-BR'],        false, 'Brazil'],
  ['Australia/Sydney',     ['en-AU'],        false, 'Australia'],
  ['Asia/Tokyo',           ['ja-JP'],        false, 'Japan'],
  ['Asia/Kolkata',         ['en-IN'],        false, 'India'],
  ['Africa/Lagos',         ['en-NG'],        false, 'Nigeria'],
  ['Pacific/Auckland',     ['en-NZ'],        false, 'New Zealand']
];

for (const [tz, langs, expect, label] of table) {
  const w = boot(tz, langs);
  const got = w.window.bhpConsentRegion.showBanner;
  assert(
    `${expect ? 'SHOWS' : 'no bar'} -- ${label} (tz=${tz === undefined ? 'undefined' : `"${tz}"`}, langs=${JSON.stringify(langs)})`,
    got === expect
  );
}

// ---------------------------------------------------------------------
// 2. THE FAIL-SAFE DIRECTION, exercised rather than asserted from source.
// ---------------------------------------------------------------------
{
  const w = boot('America/Denver', ['en-US'], { breakIntl: true });
  assert('Intl throwing -> banner SHOWS (fail-safe direction)', w.window.bhpConsentRegion.showBanner === true);
}
{
  const w = boot('America/Denver', undefined);
  assert('navigator.languages undefined on a US zone -> still no bar (language is additive only)', w.window.bhpConsentRegion.showBanner === false);
}
{
  const w = boot('Europe/Berlin', undefined);
  assert('navigator.languages undefined on an EEA zone -> SHOWS on the timezone alone', w.window.bhpConsentRegion.showBanner === true);
}
{
  const w = boot('America/Denver', [null, 42, {}]);
  assert('junk entries in navigator.languages do not throw and do not flip the decision', w.window.bhpConsentRegion.showBanner === false);
}

// ---------------------------------------------------------------------
// 3. THE PURE FUNCTION IS PURE, and is callable with fixture inputs.
// ---------------------------------------------------------------------
{
  const w = boot('America/Denver', ['en-US']);
  const f = w.window.bhpConsentRegion.shouldShowBanner;
  assert('shouldShowBanner is exposed as a function', typeof f === 'function');
  assert('shouldShowBanner("Europe/Berlin", []) === true', f('Europe/Berlin', []) === true);
  assert('shouldShowBanner("America/Denver", []) === false', f('America/Denver', []) === false);
  assert('shouldShowBanner is pure -- repeated calls agree', f('America/Denver', []) === f('America/Denver', []));
  assert('a US-zone boot did NOT mutate its own decision by calling the function',
    w.window.bhpConsentRegion.showBanner === false);
}

// ---------------------------------------------------------------------
// 4. THE REGION LIST COVERS EVERY EEA+UK STATE.
// ---------------------------------------------------------------------
{
  const w = boot('America/Denver', ['en-US']);
  const regions = w.window.bhpConsentRegion.SHOW_REGIONS;
  const expected = ['AT','BE','BG','HR','CY','CZ','DK','EE','FI','FR','DE','GR','HU','IE','IT',
                    'LV','LT','LU','MT','NL','PL','PT','RO','SK','SI','ES','SE','IS','LI','NO','GB'];
  assert('SHOW_REGIONS carries all 31 EEA+UK codes', expected.every(c => regions.indexOf(c) !== -1));
  assert('SHOW_REGIONS is exactly 31 entries (no strays)', regions.length === 31);
  assert('Cyprus is present -- the easiest EEA state to miss', regions.indexOf('CY') !== -1);
}

console.log('');
if (failures) {
  console.error(`${failures} banner-visibility test(s) FAILED`);
  process.exit(1);
}
console.log('All banner-visibility tests passed.');
