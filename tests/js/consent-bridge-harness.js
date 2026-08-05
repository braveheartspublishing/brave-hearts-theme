/**
 * Consent-bridge behaviour harness (theme 1.19.178, `CYCLE143-GIM-51`).
 *
 * Executes the consent-bridge JS EXACTLY as it was rendered into a real
 * page -- extracted from fetched HTML, never read from the source file --
 * against a stubbed browser, and asserts what actually reaches the
 * dataLayer in each consent state.
 *
 * Usage (no dependencies, plain node, nothing installed):
 *   curl -s https://staging2.braveheartspublishing.com/ -o /tmp/page.html
 *   node tests/js/consent-bridge-harness.js /tmp/page.html
 *
 * Exits non-zero on any failure. This is the only JS behaviour test in the
 * repo and is deliberately narrow: it covers the one file whose failure
 * mode is silent (a consenting visitor never being measured, or a signal
 * being raised without the visitor). Everything else JS-side still needs a
 * real browser.
 *
 * Why it reads rendered HTML rather than inc/class-bhp-wpconsent-bridge.php:
 * the bridge is emitted inline by PHP, so the source file is not what the
 * browser runs -- and a source-level assertion was exactly the stale-grep
 * defect corrected in the 1.19.177 wave1 suite.
 */
const fs = require('fs');
const path = process.argv[2];
const html = fs.readFileSync(path, 'utf8');

// Pull the <script> block that contains the bridge.
const scripts = [...html.matchAll(/<script>([\s\S]*?)<\/script>/g)].map(m => m[1]);
const bridge = scripts.find(s => s.includes('bhpConsentBridge'));
const defaults = scripts.find(s => s.includes("gtag('consent','default'"));
if (!bridge) { console.error('FAIL: no bridge script found in rendered HTML'); process.exit(1); }

let failures = 0;
function assert(label, cond) {
  console.log((cond ? 'PASS: ' : 'FAIL: ') + label);
  if (!cond) failures++;
}

// Builds a stubbed browser and runs the head snippets in real page order.
// `extraScript` is optional and runs last, like a footer-enqueued file.
function boot(cookieString, extraScript, search) {
  const vm = require('vm');
  const listeners = {};
  const state = { cookie: cookieString };
  // In a browser `window` IS the global object, so the context must be the
  // window itself -- otherwise `window.dataLayer = ...` would not create the
  // bare `dataLayer` binding the defaults snippet relies on.
  const ctx = vm.createContext({});
  ctx.window = ctx;
  ctx.URLSearchParams = URLSearchParams;
  ctx.location = { search: search || '', pathname: '/', protocol: 'https:' };
  ctx.document = {
    get cookie() { return state.cookie; },
    set cookie(v) { state.cookie += (state.cookie ? '; ' : '') + v.split(';')[0]; },
  };
  ctx.addEventListener = (n, fn) => { (listeners[n] = listeners[n] || []).push(fn); };
  // The defaults snippet runs first in the real page and defines gtag().
  vm.runInContext(defaults, ctx);
  vm.runInContext(bridge, ctx);
  if (extraScript) { vm.runInContext(extraScript, ctx); }
  return {
    ctx,
    state,
    fire(name, detail) { (listeners[name] || []).forEach(fn => fn({ detail })); },
    layer() { return ctx.dataLayer.map(a => Array.from(a)); },
  };
}

function run(cookieString, saveEventDetail) {
  const b = boot(cookieString);
  if (saveEventDetail !== undefined) { b.fire('wpconsent_consent_saved', saveEventDetail); }
  return b.layer();
}

function consentCalls(layer) {
  return layer.filter(a => a[0] === 'consent');
}

// --- A. first-time visitor, no cookies at all -------------------------
let layer = run('');
let calls = consentCalls(layer);
assert('A. first visit, no cookie: exactly one consent call and it is the DEFAULT', calls.length === 1 && calls[0][1] === 'default');
assert('A. first visit: every default signal is denied', ['analytics_storage','ad_storage','ad_user_data','ad_personalization'].every(k => calls[0][2][k] === 'denied'));
assert('A. first visit: wait_for_update is 500', calls[0][2].wait_for_update === 500);
assert('A. first visit: NO update is sent (nothing may raise consent without the visitor)', !calls.some(c => c[1] === 'update'));

// --- B. returning visitor with our own mirror cookie, granted ---------
layer = run('bhp_consent_state=' + encodeURIComponent(JSON.stringify({analytics_storage:'granted',ad_storage:'granted',ad_user_data:'granted',ad_personalization:'granted'})));
calls = consentCalls(layer);
assert('B. stored bhp_consent_state granted: an UPDATE is sent on load (this is the path the cache used to break)', calls.some(c => c[1] === 'update'));
assert('B. that update grants analytics_storage', calls.filter(c => c[1]==='update')[0][2].analytics_storage === 'granted');
assert('B. the default still preceded it and was denied', calls[0][1] === 'default' && calls[0][2].analytics_storage === 'denied');

// --- C. returning visitor with the CMP cookie, statistics only --------
layer = run('wpconsent_preferences=' + encodeURIComponent(JSON.stringify({essential:true, statistics:true, marketing:false})));
calls = consentCalls(layer).filter(c => c[1] === 'update');
assert('C. CMP cookie statistics=true: analytics_storage granted on load', calls.length === 1 && calls[0][2].analytics_storage === 'granted');
assert('C. CMP cookie marketing=false: all three ad_* signals stay denied (advertising consent is never inferred from analytics consent)', calls[0][2].ad_storage === 'denied' && calls[0][2].ad_user_data === 'denied' && calls[0][2].ad_personalization === 'denied');

// --- D. returning visitor who explicitly rejected ---------------------
layer = run('wpconsent_preferences=' + encodeURIComponent(JSON.stringify({essential:true, statistics:false, marketing:false})));
calls = consentCalls(layer).filter(c => c[1] === 'update');
assert('D. CMP cookie with everything false: the update sent is all-denied, nothing is granted', calls.length === 1 && Object.values(calls[0][2]).every(v => v === 'denied'));

// --- E. live banner acceptance on a first visit -----------------------
layer = run('', { essential: true, statistics: true, marketing: true });
calls = consentCalls(layer).filter(c => c[1] === 'update');
assert('E. live wpconsent_consent_saved acceptance sends a granting update', calls.length === 1 && calls[0][2].analytics_storage === 'granted' && calls[0][2].ad_storage === 'granted');

// --- F. malformed / hostile cookie ------------------------------------
layer = run('bhp_consent_state=not-json{{{');
calls = consentCalls(layer);
assert('F. malformed mirror cookie: no update, defaults stand denied', !calls.some(c => c[1] === 'update'));

layer = run('bhp_consent_state=' + encodeURIComponent(JSON.stringify({analytics_storage:'GRANTED', ad_storage:'yes', bogus:'granted'})));
calls = consentCalls(layer).filter(c => c[1] === 'update');
assert('F. unrecognised signal values normalise to denied, never to granted', calls.length === 1 && Object.entries(calls[0][2]).every(([k,v]) => v === 'denied') && !('bogus' in calls[0][2]));

// --- G. the UTM attribution script's own consent gate ------------------
// Optional second argument: the fetched assets/js/bhp-attribution.js. From
// 1.19.178 that file is enqueued for EVERY visitor (the script tag has to
// be in the HTML unconditionally for the page to be cacheable), so the
// consent gate that used to live in PHP now lives inside it. These
// assertions are the proof that no first-party attribution cookie is
// written before the visitor has actually consented.
const attrPath = process.argv[3];
if (attrPath) {
  const attr = require('fs').readFileSync(attrPath, 'utf8');
  const UTM = '?utm_source=newsletter&utm_medium=email&utm_campaign=aug';
  const granted = JSON.stringify({analytics_storage:'granted',ad_storage:'denied',ad_user_data:'denied',ad_personalization:'denied'});

  let b = boot('', attr, UTM);
  assert('G. no consent: NO bhp_attr_first cookie is written', !b.state.cookie.includes('bhp_attr_first'));
  assert('G. no consent: NO bhp_attr_last cookie is written', !b.state.cookie.includes('bhp_attr_last'));

  b = boot('bhp_consent_state=' + encodeURIComponent(granted), attr, UTM);
  assert('G. stored analytics grant: first-touch IS captured', b.state.cookie.includes('bhp_attr_first'));
  assert('G. stored analytics grant: last-touch IS captured for a campaign visit', b.state.cookie.includes('bhp_attr_last'));

  b = boot('wpconsent_preferences=' + encodeURIComponent(JSON.stringify({essential:true,statistics:false,marketing:false})), attr, UTM);
  assert('G. explicit rejection: nothing is written', !b.state.cookie.includes('bhp_attr'));

  b = boot('bhp_consent_state=' + encodeURIComponent(JSON.stringify({analytics_storage:'denied'})), attr, UTM);
  assert('G. analytics denied while other signals exist: nothing is written', !b.state.cookie.includes('bhp_attr'));

  b = boot('', attr, UTM);
  assert('G. before acceptance on this page load: nothing written yet', !b.state.cookie.includes('bhp_attr'));
  b.fire('wpconsent_consent_saved', { essential: true, statistics: true, marketing: false });
  assert('G. accepting on THIS page load captures the campaign that brought them here', b.state.cookie.includes('bhp_attr_first') && b.state.cookie.includes('bhp_attr_last'));
} else {
  console.log('NOTE: assets/js/bhp-attribution.js not supplied as arg 2 -- section G (attribution consent gate) was NOT run.');
}

console.log(failures ? `\n${failures} FAILED` : '\nALL BRIDGE BEHAVIOUR TESTS PASSED');
process.exit(failures ? 1 : 0);
