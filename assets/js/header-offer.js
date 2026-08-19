/*
 * THE MOBILE-HEADER OFFER — the homepage reveal.
 * ---------------------------------------------------------------------------
 * Loaded ONLY on a page whose offer button rendered in the `deferred` state
 * (today: the homepage). Everywhere else the button is already visible and no
 * script is enqueued, so this file costs those pages nothing.
 *
 * WHAT IT DOES: watches the page's own above-the-fold primary CTA (the hero's
 * free-sample invite) and flips the header offer to `visible` once that CTA has
 * left the viewport. Before that moment the fold contains one primary; after
 * it, it contains none, so revealing the offer cannot produce two.
 *
 * FAILS CLOSED, DELIBERATELY. If `IntersectionObserver` is missing, if no
 * watched element resolves, or if this file never runs, the button stays
 * hidden. The homepage keeps exactly one above-fold primary either way. See
 * `inc/header-offer.php` for why closed is the safe direction here.
 *
 * NO LAYOUT SHIFT. The button already occupies its box; this only changes
 * `data-bhp-offer-state`, which the stylesheet reads for `visibility` and
 * `opacity`. Nothing here writes a geometric property.
 */
(function () {
  'use strict';

  var offer = document.querySelector('.bhp-header-offer[data-bhp-offer-state="deferred"]');
  if (!offer) return;

  var selectors = (offer.getAttribute('data-bhp-offer-watch') || '')
    .split(',')
    .map(function (s) { return s.trim(); })
    .filter(Boolean);

  var target = null;
  for (var i = 0; i < selectors.length; i++) {
    target = document.querySelector(selectors[i]);
    if (target) break;
  }
  if (!target) return;                       // fail closed
  if (!('IntersectionObserver' in window)) return; // fail closed

  var reveal = function () {
    if (offer.getAttribute('data-bhp-offer-state') !== 'visible') {
      offer.setAttribute('data-bhp-offer-state', 'visible');
    }
  };
  var hide = function () {
    if (offer.getAttribute('data-bhp-offer-state') !== 'hidden') {
      offer.setAttribute('data-bhp-offer-state', 'hidden');
    }
  };

  /*
   * The sticky header sits over the top of the document, so an element is only
   * genuinely "gone" once it has cleared the header too. rootMargin's negative
   * top inset measures against the header's real rendered height rather than a
   * typed pixel figure, so a header that changes height stays correct.
   */
  var header = document.querySelector('.site-header');
  var inset = header ? Math.round(header.getBoundingClientRect().height) : 0;

  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        hide();
      } else {
        reveal();
      }
    });
  }, { root: null, rootMargin: '-' + inset + 'px 0px 0px 0px', threshold: 0 });

  observer.observe(target);
})();
