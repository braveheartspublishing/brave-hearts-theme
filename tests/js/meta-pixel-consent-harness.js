/**
 * Meta pixel consent-state harness (theme 1.19.312,
 * `CYCLE167-LD-CONSENT-PIXEL-EXT`).
 *
 * Executes the pixel runtime EXACTLY as it was rendered into a real page --
 * extracted from fetched HTML, never read from the PHP source file -- and
 * drives it across a fixture table of (recorded choice, GPC, region) inputs,
 * asserting the resolved consent state, whether fbevents.js would be
 * requested, and whether an opt-out genuinely revokes.
 *
 * Usage (no dependencies, plain node, nothing installed):
 *   node tests/js/meta-pixel-consent-harness.js /tmp/page.html
 *
 * Exits non-zero on any failure.
 *
 * ⭐ WHY IT READS RENDERED HTML rather than inc/class-bhp-meta-pixel.php: the
 * runtime is emitted inline by PHP, so the source file is not what the browser
 * runs, and a source-level grep is the stale-assertion defect the 1.19.177
 * wave1 suite was corrected for. Same discipline as
 * tests/js/consent-region-harness.js and tests/js/consent-bridge-harness.js.
 *
 * ⛔ WHAT THIS CANNOT PROVE, stated plainly so a pass is not over-quoted:
 *   · It does not execute Meta's real fbevents.js. `fbq` here is Meta's own
 *     inline stub, which QUEUES commands. So this proves WHICH COMMANDS THE
 *     RUNTIME ISSUES and WHETHER THE SDK WOULD BE FETCHED -- it does not prove
 *     what Meta's SDK does with them. That is a browser observation and it is
 *     recorded in the QA log with its timestamp and instrument.
 *   · `window.bhpConsentRegion` is INJECTED as a fixture rather than derived by
 *     running the region gate. The gate's own decision function is proven
 *     separately and thoroughly by tests/js/consent-region-harness.js; re-deriving
 *     it here would test that harness twice and this runtime once.
 *   · staging renders `config.sdk` as '' by design (zero bytes to Meta from
 *     staging). Rows that need a real SDK URL set it explicitly and are LABELLED
 *     "sdk fixture" -- that is a simulation of the production config value, and
 *     it is disclosed rather than hidden behind a green line.
 */
const fs = require('fs');
const vm = require('vm');

const path = process.argv[2];
if (!path) { console.error('usage: node meta-pixel-consent-harness.js <page.html>'); process.exit(2); }
const html = fs.readFileSync(path, 'utf8');

function extract(re, what) {
  const m = html.match(re);
  if (!m) { console.error('FAIL: could not find ' + what + ' in the rendered HTML'); process.exit(1); }
  return m[1];
}

const baseCode = extract(/<script>(!function\(f,b,e,v,n,t,s\)[\s\S]*?)<\/script>/, 'the Meta base-code stub');
const configJs = extract(/<script>(window\.bhpMetaPixel=window\.bhpMetaPixel\|\|\{\};[\s\S]*?)<\/script>/, 'the bhpMetaPixel config block');
const runtime  = extract(/<script>(\(function \(\) \{\s*var NS = window\.bhpMetaPixel;[\s\S]*?)<\/script>/, 'the pixel runtime');

let failures = 0;
function assert(label, cond) {
  console.log((cond ? 'PASS: ' : 'FAIL: ') + label);
  if (!cond) failures++;
}

/**
 * Boot the emitted pixel scripts against a stubbed browser.
 *
 * opts:
 *   cookies      {name: value}          -- document.cookie fixture
 *   gpc          true|false|'throw'     -- navigator.globalPrivacyControl
 *   region       undefined | {showBanner: bool}  -- window.bhpConsentRegion
 *   sdk          string                 -- overrides config.sdk ("sdk fixture")
 *   deferOnload  true                   -- do NOT fire the injected script's
 *                                          onload; the caller fires it, so the
 *                                          in-flight opt-out window can be
 *                                          exercised deliberately.
 */
function boot(opts) {
  opts = opts || {};
  const ctx = vm.createContext({});
  ctx.window = ctx;
  ctx.console = { log: function () {}, warn: function () {}, error: function () {} };
  ctx.JSON = JSON;
  ctx.Object = Object;
  ctx.Array = Array;
  ctx.String = String;
  ctx.Number = Number;
  ctx.Boolean = Boolean;
  ctx.Math = Math;
  ctx.Date = Date;
  ctx.RegExp = RegExp;
  ctx.Error = Error;
  ctx.Uint8Array = Uint8Array;
  ctx.decodeURIComponent = decodeURIComponent;
  ctx.encodeURIComponent = encodeURIComponent;

  // --- cookies -----------------------------------------------------------
  const jar = Object.assign({}, opts.cookies || {});
  const doc = {
    addEventListener: function () {},
    head: { appendChild: function (el) { injected.push(el); } }
  };
  Object.defineProperty(doc, 'cookie', {
    get: function () {
      return Object.keys(jar).map(function (k) { return k + '=' + jar[k]; }).join('; ');
    },
    set: function (str) {
      const pair = String(str).split(';')[0];
      const i = pair.indexOf('=');
      const k = pair.slice(0, i).trim();
      const v = pair.slice(i + 1);
      if (/Max-Age=0/i.test(str)) { delete jar[k]; } else { jar[k] = v; }
    }
  });

  // --- the injected <script>, so SDK loading is observable ----------------
  const injected = [];
  doc.createElement = function () {
    return { async: false, src: '', onload: null, onerror: null };
  };
  doc.getElementsByTagName = function () {
    return [ { parentNode: { insertBefore: function (el) { injected.push(el); } } } ];
  };
  ctx.document = doc;

  // --- navigator / GPC ---------------------------------------------------
  if (opts.gpc === 'throw') {
    Object.defineProperty(ctx, 'navigator', { get: function () { throw new Error('navigator unavailable'); } });
  } else {
    ctx.navigator = { globalPrivacyControl: opts.gpc === true ? true : undefined };
  }

  // --- listeners ---------------------------------------------------------
  const listeners = {};
  ctx.addEventListener = function (name, fn) { (listeners[name] = listeners[name] || []).push(fn); };
  ctx.setInterval = function () { return 0; };
  ctx.crypto = { randomUUID: function () { return 'fixed-uuid-for-the-harness'; } };

  // --- the region gate's published global, as a fixture -------------------
  if (typeof opts.region !== 'undefined') { ctx.bhpConsentRegion = opts.region; }

  vm.runInContext(baseCode, ctx);
  vm.runInContext(configJs, ctx);
  if (typeof opts.sdk === 'string') { ctx.bhpMetaPixel.config.sdk = opts.sdk; }
  vm.runInContext(runtime, ctx);

  function fireOnload() {
    injected.forEach(function (el) { if (typeof el.onload === 'function') { el.onload(); } });
  }
  if (!opts.deferOnload) { fireOnload(); }

  return {
    ctx: ctx,
    injected: injected,
    fireOnload: fireOnload,
    queue: function () { return (ctx.fbq && ctx.fbq.queue) || []; },
    commands: function () {
      return ((ctx.fbq && ctx.fbq.queue) || []).map(function (a) {
        return Array.prototype.slice.call(a).slice(0, 2).join(' ');
      });
    },
    granted: function () { return ctx.bhpMetaPixel.debug.granted(); },
    consent: function () { return ctx.bhpMetaPixel.debug.consent(); },
    dispatch: function (name, detail) {
      (listeners[name] || []).forEach(function (fn) { fn({ detail: detail }); });
    },
    push: function (payload) { ctx.dataLayer.push(payload); }
  };
}

const ACCEPT = { 'wpconsent_preferences': '{"essential":true,"statistics":true,"marketing":true}' };
const REJECT = { 'wpconsent_preferences': '{"essential":true,"statistics":false,"marketing":false}' };
const MIRROR_GRANTED = { 'bhp_consent_state': '{"analytics_storage":"granted","ad_storage":"granted","ad_user_data":"granted","ad_personalization":"granted"}' };
const MIRROR_DENIED  = { 'bhp_consent_state': '{"analytics_storage":"denied","ad_storage":"denied","ad_user_data":"denied","ad_personalization":"denied"}' };
const US  = { showBanner: false, timeZone: 'America/Denver' };
const EEA = { showBanner: true,  timeZone: 'Europe/Berlin' };

// =======================================================================
// 1. THE DECISION TABLE — both directions, every branch
// =======================================================================
console.log('--- 1. the consent decision table ---');

const TABLE = [
  // label                                                    cookies  gpc    region      expect
  ['US visitor, no choice recorded -> GRANTED (the release)',  {},      false, US,          true ],
  ['EEA visitor, no choice recorded -> DENIED (unchanged)',    {},      false, EEA,         false],
  ['ambiguous region (showBanner true) -> DENIED',             {},      false, {showBanner:true}, false],
  ['region gate absent entirely -> DENIED (fail-safe)',        {},      false, undefined,   false],
  ['region gate present but showBanner undefined -> DENIED',   {},      false, {},          false],
  ['showBanner is the STRING "false", not false -> DENIED',    {},      false, {showBanner:'false'}, false],
  ['US visitor with GPC on, no choice -> DENIED',              {},      true,  US,          false],
  ['EEA visitor with GPC on, no choice -> DENIED',             {},      true,  EEA,         false],
  ['US visitor who OPTED OUT -> DENIED (the opt-out works)',   REJECT,  false, US,          false],
  ['EEA visitor who ACCEPTED -> GRANTED (choice outranks region)', ACCEPT, false, EEA,      true ],
  ['accepting visitor with GPC on -> GRANTED (choice outranks GPC)', ACCEPT, true, EEA,     true ],
  ['rejecting visitor in the US with GPC off -> DENIED',       REJECT,  false, US,          false],
  ['mirror cookie granted, no CMP cookie, EEA -> GRANTED',     MIRROR_GRANTED, false, EEA,  true ],
  ['mirror cookie denied, no CMP cookie, US -> DENIED',        MIRROR_DENIED,  false, US,   false],
  ['malformed CMP cookie in the US -> falls through to region -> GRANTED', {'wpconsent_preferences':'not-json{{{'}, false, US, true ],
  ['malformed CMP cookie in the EEA -> falls through to region -> DENIED', {'wpconsent_preferences':'not-json{{{'}, false, EEA, false],
  ['malformed mirror cookie in the EEA -> DENIED',             {'bhp_consent_state':']]]'}, false, EEA, false],
  ['navigator throws (GPC unreadable), US -> region still decides -> GRANTED', {}, 'throw', US, true ]
];

TABLE.forEach(function (row) {
  const label = row[0], cookies = row[1], gpc = row[2], region = row[3], expect = row[4];
  const p = boot({ cookies: cookies, gpc: gpc, region: region });
  assert(label, p.granted() === expect);
});

// =======================================================================
// 2. THE SDK IS FETCHED ONLY ON THE GRANTED PATH
//    "sdk fixture": config.sdk is set explicitly, because staging renders it
//    empty by design. Disclosed, not hidden.
// =======================================================================
console.log('\n--- 2. SDK loading (sdk fixture) ---');

const SDK = 'https://connect.facebook.net/en_US/fbevents.js';

const us = boot({ cookies: {}, gpc: false, region: US, sdk: SDK });
assert('2a ⭐ a US visitor with no recorded choice causes fbevents.js to be requested — the founder\'s signal path is alive', us.injected.length === 1 && us.injected[0].src === SDK);
assert('2b ⭐ and the grant is signalled after it, so the SDK is initialised rather than stalled behind the revoke', us.commands().indexOf('consent grant') !== -1);
/* ⚠ 2c FAILED ON ITS FIRST RUN AGAINST ENTIRELY CORRECT CODE, and the defect
 * is recorded rather than quietly patched. It searched for the exact command
 * string 'init' — but commands() joins the first TWO arguments, so the real
 * entry is 'init 2050405642533821'. indexOf returned -1, and `0 < -1` is
 * false. ⭐ THE LESSON, which is the same one this repo learned about
 * substring assertions on CSS blocks: an exact-match lookup against a
 * normalised projection is only as good as the projection. Match the PREFIX
 * of the command, not a string the projection never produces. */
function firstIndexStarting(list, prefix) {
  for (var i = 0; i < list.length; i++) { if (list[i].indexOf(prefix) === 0) { return i; } }
  return -1;
}
const usCmds = us.commands();
assert('2c the revoke is still the FIRST command and still precedes init — layer 1 is untouched by this release',
  firstIndexStarting(usCmds, 'consent revoke') === 0
  && firstIndexStarting(usCmds, 'init ') > 0
  && firstIndexStarting(usCmds, 'consent revoke') < firstIndexStarting(usCmds, 'init '));

const eu = boot({ cookies: {}, gpc: false, region: EEA, sdk: SDK });
assert('2d ⛔ an EEA visitor with no recorded choice requests NO third-party script at all', eu.injected.length === 0);
assert('2e ⛔ and no grant is ever signalled for them', eu.commands().indexOf('consent grant') === -1);
assert('2f ⛔ the EEA visitor\'s command list still ends with the revoke/init/PageView the stub queued, and transmits nothing (no SDK to transmit with)', eu.commands().indexOf('consent revoke') === 0);

const noRegion = boot({ cookies: {}, gpc: false, region: undefined, sdk: SDK });
assert('2g ⛔ with the region gate absent, no script is requested — the fail-safe direction reaches the network layer, not just the flag', noRegion.injected.length === 0);

// =======================================================================
// 3. THE OPT-OUT GENUINELY REVOKES — including mid-flight
// =======================================================================
console.log('\n--- 3. the opt-out path ---');

const optOut = boot({ cookies: {}, gpc: false, region: US, sdk: SDK });
const beforeOptOut = optOut.commands().length;
optOut.dispatch('wpconsent_consent_saved', { essential: true, statistics: false, marketing: false });
assert('3a ⭐ opting out flips the resolved state to denied', optOut.granted() === false);
assert('3b ⭐ opting out issues a live fbq consent revoke', optOut.commands().slice(beforeOptOut).indexOf('consent revoke') !== -1);
assert('3c ⭐ no further grant is issued after the opt-out', optOut.commands().slice(beforeOptOut).indexOf('consent grant') === -1);

/* A Lead raised AFTER the opt-out must not produce a grant, and the pixel must
 * remain revoked. The track call itself still lands in Meta's stub queue --
 * that is Meta's own documented behaviour under a revoked consent, and the SDK
 * is what refuses to transmit. This asserts what THIS file controls. */
const beforeLead = optOut.commands().length;
optOut.push({ event: 'adventure_kit_signup' });
assert('3d ⭐ a signup after the opt-out does not re-grant consent', optOut.granted() === false && optOut.commands().slice(beforeLead).indexOf('consent grant') === -1);

/* ⭐⭐ THE MID-FLIGHT WINDOW. This is the path that did not exist before
 * 1.19.312 and is the one most likely to be broken by a future edit: the
 * visitor opts out while fbevents.js is still downloading. Without the
 * re-check inside the onload callback, the stale closure re-grants. */
const midFlight = boot({ cookies: {}, gpc: false, region: US, sdk: SDK, deferOnload: true });
assert('3e the SDK request is in flight and no grant has been signalled yet', midFlight.injected.length === 1 && midFlight.commands().indexOf('consent grant') === -1);
midFlight.dispatch('wpconsent_consent_saved', { essential: true, statistics: false, marketing: false });
midFlight.fireOnload();
assert('3f ⭐⭐ an opt-out taken WHILE fbevents.js is in flight is NOT undone when the script finally loads — no grant is ever signalled', midFlight.commands().indexOf('consent grant') === -1);
assert('3g ⭐⭐ ...and the resolved state stays denied', midFlight.granted() === false);

/* And the reverse: an EEA visitor who accepts must be granted without a
 * reload, which is what makes the banner worth showing. */
const euAccept = boot({ cookies: {}, gpc: false, region: EEA, sdk: SDK });
assert('3h an EEA visitor starts denied with no SDK', euAccept.granted() === false && euAccept.injected.length === 0);
euAccept.dispatch('wpconsent_consent_saved', { essential: true, statistics: true, marketing: true });
euAccept.fireOnload();
assert('3i ⭐ accepting in the banner loads the SDK and grants, with no page reload', euAccept.injected.length === 1 && euAccept.granted() === true && euAccept.commands().indexOf('consent grant') !== -1);

// =======================================================================
// 4. THE LEAD PATH AND THE LATCH ARE UNCHANGED
// =======================================================================
console.log('\n--- 4. the Lead path (unchanged by this release) ---');

const lead = boot({ cookies: {}, gpc: false, region: US, sdk: SDK });
lead.push({ event: 'adventure_kit_signup' });
const leadInfo = lead.ctx.bhpMetaPixel.debug.lead();
assert('4a ⭐ adventure_kit_signup raises a Lead', !!leadInfo && lead.commands().indexOf('track Lead') !== -1);
assert('4b the Lead carries an eventID for Conversions-API dedup', !!leadInfo && !!leadInfo.eventID);

const before2nd = lead.commands().filter(function (c) { return c === 'track Lead'; }).length;
lead.push({ event: 'parent_popup_success' });
const after2nd = lead.commands().filter(function (c) { return c === 'track Lead'; }).length;
assert('4c ⭐ the one-Lead-per-page-load latch still drops the second mapped Lead', before2nd === 1 && after2nd === 1);

/* A denied visitor's Lead must not cause an SDK fetch. The call lands in the
 * stub queue and goes nowhere, because there is nothing to send it with. */
const euLead = boot({ cookies: {}, gpc: false, region: EEA, sdk: SDK });
euLead.push({ event: 'adventure_kit_signup' });
assert('4d ⛔ an EEA visitor\'s signup raises no third-party request — the Lead sits in the stub queue with no SDK to transmit it', euLead.injected.length === 0 && euLead.granted() === false);

// =======================================================================
// 5. NO STORAGE, NO FABRICATED CHOICE
// =======================================================================
console.log('\n--- 5. invariants ---');

assert('5a ⛔ the emitted runtime touches no localStorage', runtime.indexOf('localStorage') === -1);
assert('5b ⛔ the emitted runtime touches no sessionStorage', runtime.indexOf('sessionStorage') === -1);

const noWrite = boot({ cookies: {}, gpc: false, region: US, sdk: SDK });
const jarAfter = noWrite.ctx.document.cookie;
assert('5c ⛔ a granted-by-default visitor has NO consent cookie written for them — a default is not a decision, and recording one would fabricate a choice', jarAfter.indexOf('bhp_consent_state') === -1 && jarAfter.indexOf('wpconsent_preferences') === -1);

console.log('');
if (failures) {
  console.error(failures + ' meta-pixel consent harness assertion(s) failed.');
  process.exit(1);
}
console.log('All meta-pixel consent harness assertions passed.');
