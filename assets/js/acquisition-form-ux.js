/**
 * Brave Hearts Publishing — acquisition-form success visibility + busy state.
 *
 * Every acquisition form on the site (Adventure Club, footer, blog inline,
 * teacher, lead magnet) already POSTs through the same proven backend
 * (inc/mailchimp.php's bhp_handle_mailchimp_signup) and redirects back to
 * itself with a #{form_id}-status URL fragment. That backend was verified
 * working end-to-end (2026-07-18) -- this file does not touch it.
 *
 * The real defect this file fixes: native browser "scroll to URL fragment
 * on load" is not reliable enough after a 303 redirect + full page reload,
 * especially for a form positioned far down a long page (the homepage
 * Adventure Club section sits below 9 other sections). A visitor can
 * genuinely submit successfully and land back at the top of the page with
 * zero visible sign anything happened. This script makes the existing
 * success/error message impossible to miss by explicitly scrolling to it,
 * and adds a standard busy/disabled state on submit so a slow connection
 * doesn't read as "did nothing" and doesn't invite a duplicate click.
 *
 * No AJAX, no new endpoint, no change to validation/redirect/Mailchimp
 * logic -- purely a visibility + busy-state layer on the existing,
 * already-working POST/redirect/GET flow.
 */
(function () {
  'use strict';

  function onReady(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  onReady(function () {
    // Scroll the status message into view after a redirect back, instead of
    // depending solely on the browser's native fragment-scroll (unreliable
    // after a POST/redirect/GET round trip on some browsers/situations).
    var hash = window.location.hash;
    if (hash && hash.indexOf('-status') !== -1) {
      var target = document.querySelector(hash);
      if (target && typeof target.scrollIntoView === 'function') {
        // Not deferred via requestAnimationFrame: rAF can be starved on a
        // backgrounded/hidden tab, which would silently skip this entirely
        // on some browsers/situations -- a plain synchronous call is the
        // more robust choice for something this load-bearing. A brief
        // setTimeout still lets already-parsed layout (e.g. webfonts) settle
        // without depending on frame callbacks at all.
        window.setTimeout(function () {
          target.scrollIntoView({ block: 'center', behavior: 'smooth' });
        }, 50);
      }
    }

    // Busy state + duplicate-submit guard on every acquisition form. This
    // never calls preventDefault() -- the real, already-working native
    // submission still proceeds; this only changes what the button looks
    // like while the browser navigates.
    var forms = document.querySelectorAll('form.acquisition-form');
    forms.forEach(function (form) {
      form.addEventListener('submit', function () {
        var btn = form.querySelector('.acquisition-form__submit');
        if (!btn || btn.disabled) {
          return;
        }
        if (!btn.dataset.bhpIdleLabel) {
          btn.dataset.bhpIdleLabel = btn.textContent;
        }
        btn.disabled = true;
        btn.setAttribute('aria-disabled', 'true');
        btn.textContent = 'Sending…';
      });
    });

    // Safety net: if the page is restored from the back/forward cache (the
    // user hits Back after a submit), a disabled/"Sending..." button would
    // otherwise stay stuck in that state with no way to submit again.
    window.addEventListener('pageshow', function (event) {
      if (!event.persisted) {
        return;
      }
      forms.forEach(function (form) {
        var btn = form.querySelector('.acquisition-form__submit');
        if (btn && btn.dataset.bhpIdleLabel) {
          btn.disabled = false;
          btn.setAttribute('aria-disabled', 'false');
          btn.textContent = btn.dataset.bhpIdleLabel;
        }
      });
    });
  });
})();
