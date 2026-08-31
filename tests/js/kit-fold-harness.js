/**
 * Desktop fold harness for /reluctant-reader-adventure-kit/ (theme 1.19.311,
 * `CYCLE167-LD-KIT-FOLD-FIX`).
 *
 * Asserts, mechanically and at stated viewport sizes, that the first-name
 * field, the email field and the submit control's BOTTOM EDGE all sit inside
 * the fold budget with the sticky nav rendered.
 *
 * Usage — paste into the browser console at the staged page, or load it:
 *   await bhpKitFold();                       // full sweep, PASS/FAIL per row
 *   await bhpKitFold({ verbose: true });      // + the full geometry dump
 *
 * ⭐ WHY AN IFRAME AND NOT `window.resizeTo`. Browser-automation viewport
 *    resizes are unreliable on this machine — a resize can report success and
 *    change nothing (observed again on 2026-08-27: a resize to 1366x768
 *    returned "Successfully resized" while `window.innerWidth` stayed 1280).
 *    A same-origin iframe of a stated width and height gives the child
 *    document a REAL layout viewport of exactly that size: media queries
 *    evaluate against it, `vh` resolves against it, and the geometry is read
 *    from the child's own `getBoundingClientRect()`. Every number below is a
 *    layout measurement, not an estimate.
 *
 * ⭐ THE INSTRUMENT WAS VALIDATED BEFORE IT WAS TRUSTED, which is the whole
 *    reason to believe it: at 1280x495 the iframe returned button bottom 625,
 *    copy 110-666, h1 154-255, photo 108-668, nav 80, clientWidth 1265 —
 *    IDENTICAL in every field to the same measurement taken in the real top-
 *    level window at the same size. A harness that has not been checked
 *    against the real thing is a guess with a decimal point.
 *
 * ⛔ WHAT THIS CANNOT PROVE, stated so a PASS is not over-quoted:
 *    · It cannot prove any particular visitor's browser furniture. The budget
 *      is an assumption about chrome height, declared below and defensible,
 *      not a measurement of the founder's laptop.
 *    · It cannot see the consent banner. That element is `position: fixed`,
 *      consumes no layout space, and is already dismissed in any profile that
 *      has visited before — so it does not move these numbers and this file
 *      does not claim it was accounted for. It OVERLAYS the viewport for a
 *      first-time paid visitor and is a separate open question.
 *    · It measures the STAGED page. It says nothing about production.
 */

/* ---------------------------------------------------------------------------
 * THE BUDGETS, DECLARED HONESTLY RATHER THAN REVERSE-ENGINEERED FROM A PASS.
 * -------------------------------------------------------------------------
 * 1366x768 is the most common laptop panel. Chrome's tab strip + omnibox take
 * ~88px; a bookmarks bar another ~32; a Windows taskbar ~40. 630 is the good
 * case and ~600 the ordinary one — so 630 is the BINDING budget and the one
 * this release was built against.
 *
 * 1920x1080 with the same chrome leaves ~940-970. ⛔ THAT BUDGET WAS ALREADY
 * MET AT 1.19.309 (button bottom 634, slack +306). It is asserted here for
 * completeness and is NOT the criterion that drove the fix — recorded so a
 * later reader does not conclude the 1080p pass is what was achieved.
 * ------------------------------------------------------------------------- */
const BHP_FOLD_BUDGET_LAPTOP = 630;
const BHP_FOLD_BUDGET_1080P  = 940;

const BHP_FOLD_CASES = [
  { w:  901, h: 630, budget: BHP_FOLD_BUDGET_LAPTOP },
  { w: 1024, h: 630, budget: BHP_FOLD_BUDGET_LAPTOP },
  { w: 1280, h: 630, budget: BHP_FOLD_BUDGET_LAPTOP },
  { w: 1366, h: 630, budget: BHP_FOLD_BUDGET_LAPTOP },
  { w: 1440, h: 790, budget: BHP_FOLD_BUDGET_LAPTOP },
  { w: 1920, h: 940, budget: BHP_FOLD_BUDGET_1080P  },
  { w: 1920, h: 940, budget: BHP_FOLD_BUDGET_LAPTOP },
  { w: 2560, h: 940, budget: BHP_FOLD_BUDGET_LAPTOP },
];

/* Mobile rows exist for ONE purpose: to prove this desktop-only release did
   not reach a phone. They assert unchanged controls, not a tighter fold. */
const BHP_FOLD_MOBILE = [ { w: 375, h: 812 }, { w: 390, h: 844 }, { w: 768, h: 1024 } ];

async function bhpFoldMeasure(w, h, url) {
  const f = document.createElement('iframe');
  f.style.cssText = 'position:fixed;left:-99999px;top:0;border:0;';
  f.width = w; f.height = h;
  f.src = url || (location.pathname + '?bhpfold=' + w + 'x' + h);
  document.body.appendChild(f);
  await new Promise(r => { f.onload = r; });
  await new Promise(r => setTimeout(r, 700));           // fonts + layout settle
  const d = f.contentDocument, W = f.contentWindow;
  const q = s => d.querySelector(s);
  const box = e => { if (!e) return null; const b = e.getBoundingClientRect();
    return { top: Math.round(b.top + W.scrollY), bottom: Math.round(b.bottom + W.scrollY),
             h: Math.round(b.height), w: Math.round(b.width) }; };
  const btn   = q('.parent-landing-hero__form button[type=submit], .parent-landing-hero__form .acquisition-form__submit');
  const first = q('.parent-landing-hero__form input[name=first_name]');
  const email = q('.parent-landing-hero__form input[name=email]');
  const photo = q('.parent-landing-hero__photo');
  const img   = q('.parent-landing-hero__photo img');
  const h1    = q('.parent-landing-hero h1');
  const out = {
    vw: W.innerWidth, vh: W.innerHeight, clientWidth: d.documentElement.clientWidth,
    nav: box(q('header') || q('.site-header')),
    h1: box(h1), h1FontSize: h1 ? W.getComputedStyle(h1).fontSize : null,
    lead: box(q('.parent-landing-hero p.parent-landing__lead')),
    copy: box(q('.parent-landing-hero__copy')),
    firstName: box(first), email: box(email), submit: box(btn),
    submitLabel: btn ? btn.textContent.trim() : null,
    photo: box(photo),
    objectPosition: img ? W.getComputedStyle(img).objectPosition : null,
  };
  f.remove();
  return out;
}

async function bhpKitFold(opts) {
  opts = opts || {};
  let pass = 0, fail = 0;
  const ok = (label, cond, detail) => {
    console.log((cond ? 'PASS: ' : 'FAIL: ') + label + (detail ? '  [' + detail + ']' : ''));
    cond ? pass++ : fail++;
  };

  console.log('=== KIT DESKTOP FOLD — theme 1.19.311 ===');
  for (const c of BHP_FOLD_CASES) {
    const m = await bhpFoldMeasure(c.w, c.h);
    if (opts.verbose) console.log(JSON.stringify(m, null, 1));
    const tag = c.w + 'x' + c.h + ' (budget ' + c.budget + ')';

    /* The whole capture unit, not just the button — a submit control above the
       fold with its own email field below it is not a capture form. */
    ok(tag + ' · first-name field inside the budget',
       m.firstName && m.firstName.bottom <= c.budget, 'bottom ' + (m.firstName && m.firstName.bottom));
    ok(tag + ' · email field inside the budget',
       m.email && m.email.bottom <= c.budget, 'bottom ' + (m.email && m.email.bottom));
    ok(tag + ' · ⭐ submit BOTTOM EDGE inside the budget',
       m.submit && m.submit.bottom <= c.budget,
       'bottom ' + (m.submit && m.submit.bottom) + ', slack ' + (c.budget - (m.submit ? m.submit.bottom : 0)));

    /* The viewport actually took. A harness that silently measured the wrong
       size would pass everything, which is the failure mode this row exists
       to make impossible. */
    ok(tag + ' · viewport actually applied', m.vw === c.w && m.vh === c.h, m.vw + 'x' + m.vh);

    /* ⛔ THE CROP FIX MUST STILL HOLD. The 1.19.310 lane's value is correct
       only while the panel keeps a constant 0.800 shape. */
    ok(tag + ' · ⛔ photo panel keeps its 0.800 shape (crop-fix premise)',
       m.photo && Math.abs((m.photo.w / m.photo.h) - 0.8) < 0.01,
       m.photo && (m.photo.w + 'x' + m.photo.h));
    ok(tag + ' · ⛔ crop anchor still top (object-position 50% 0%)',
       m.objectPosition === '50% 0%', String(m.objectPosition));
  }

  console.log('=== MOBILE — proving this desktop release did NOT reach a phone ===');
  for (const c of BHP_FOLD_MOBILE) {
    const m = await bhpFoldMeasure(c.w, c.h);
    const tag = c.w + 'x' + c.h;
    ok(tag + ' · controls unchanged: input 59px, submit 52px',
       m.firstName && m.firstName.h === 59 && m.submit && m.submit.h === 52,
       'input ' + (m.firstName && m.firstName.h) + ', submit ' + (m.submit && m.submit.h));
    ok(tag + ' · submit still inside the phone fold',
       m.submit && m.submit.bottom <= c.h, 'bottom ' + (m.submit && m.submit.bottom));
  }

  console.log('============================================================');
  console.log('KIT DESKTOP FOLD: ' + pass + ' passed, ' + fail + ' failed');
  console.log('============================================================');
  return { pass, fail };
}

if (typeof window !== 'undefined') { window.bhpKitFold = bhpKitFold; window.bhpFoldMeasure = bhpFoldMeasure; }
