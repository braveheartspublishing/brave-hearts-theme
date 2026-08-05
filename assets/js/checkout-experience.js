/**
 * Brave Hearts — cart/checkout DOM enhancements that PHP cannot reach.
 *
 * Capstone fix wave, 2026-08-03. Loaded on /cart/ and /checkout/ only.
 *
 * ⛔ SCOPE, AND IT IS NARROW ON PURPOSE. This file:
 *      - sets ONE attribute on ONE field (inputmode on the ZIP);
 *      - inserts TWO static messages;
 *      - reads the shipping-state <select> to decide whether to show one of them;
 *      - [2C-4, 2026-08-03] appends a Remove button to each checkout order-summary
 *        line and a branded panel to the checkout's own empty state.
 *    It never writes a field value, never intercepts a submit, never re-parents a
 *    node React owns, and knows nothing about consent or payment.
 *
 * ⚠ THE SCOPE LINE ABOVE CHANGED ON 2026-08-03 AND THE CHANGE IS NAMED RATHER
 *   THAN SMOOTHED OVER. It used to end "never touches totals … and knows nothing
 *   about the cart contents". 2C-4 reads the cart and can change it. It does so
 *   ONLY through `wp.data.dispatch('wc/store/cart').removeItemFromCart()` — the
 *   same action WooCommerce's own cart page uses — so totals, shipping and tax
 *   are recalculated by WooCommerce and never by this file. It still computes no
 *   price, writes no total, and touches no payment or submit path.
 *
 * WHY A MUTATION OBSERVER. The Blocks checkout is React. It re-renders the
 * address form on every `update-customer` round trip, which discards anything
 * added to it. A one-shot `DOMContentLoaded` pass measurably does not survive
 * the first keystroke. The observer is debounced with `requestAnimationFrame`
 * so a burst of React commits costs one pass, not one pass per commit.
 */
(function () {
  'use strict';

  var COPY = window.bhpCheckoutCopy || {};
  var OFFSHORE = COPY.offshoreStates || ['AK', 'HI'];
  var scheduled = false;

  function schedule() {
    if (scheduled) { return; }
    scheduled = true;
    window.requestAnimationFrame(function () { scheduled = false; apply(); });
  }

  /**
   * F10 / CYCLE142-LD-10 — a US ZIP field that opens the full QWERTY keyboard.
   *
   * WooCommerce Blocks emits `shipping-postcode` as `type="text"` with no
   * `inputmode`, and exposes no filter for the attribute. Setting
   * `inputmode="numeric"` gives a phone the number pad; `type` is deliberately
   * left as `text`, because `type="number"` would add spinners, break
   * ZIP+4 and break autofill.
   */
  function zipKeyboard() {
    ['shipping-postcode', 'billing-postcode'].forEach(function (id) {
      var el = document.getElementById(id);
      if (el && el.getAttribute('inputmode') !== 'numeric') {
        el.setAttribute('inputmode', 'numeric');
        el.setAttribute('autocapitalize', 'off');
      }
    });
  }

  /**
   * D3, part one — the disclosure, stated BEFORE anyone types an address.
   * Placed inside the shipping-address step's own content, directly above the
   * fields, so it is read in the same glance as the country selector.
   */
  function shippingScopeNotice() {
    if (!COPY.shippingScope) { return; }
    var step = document.querySelector('.wp-block-woocommerce-checkout-shipping-address-block .wc-block-components-checkout-step__content');
    if (!step || step.querySelector('.bhp-ship-scope')) { return; }
    var p = document.createElement('p');
    p.className = 'bhp-ship-scope';
    p.textContent = COPY.shippingScope;
    step.insertBefore(p, step.firstChild);
  }

  /**
   * D3, part two — the friendly message when Alaska or Hawaii is chosen.
   *
   * Shown as a plain paragraph, NOT as a WooCommerce error notice: it is not
   * an error, it is information, and styling it as an error is what made the
   * stock behaviour read as "your address is wrong" rather than "we do not go
   * there yet". Nothing is disabled and nothing is blocked here — this file
   * has no authority over submission, and server-side enforcement is a
   * separate question recorded for Andrew.
   */
  /**
   * Builds the approved AK/HI message (B1) out of DOM nodes.
   *
   * Every string comes from bhp_checkout_offshore_state_notice() and is set
   * with textContent, never innerHTML, so no server string is ever parsed as
   * markup. The only element that is not plain text is the mailto link, whose
   * href is composed from the address alone.
   */
  function buildOffshoreNode(copy) {
    var wrap = document.createElement('div');
    wrap.className = 'bhp-offshore-notice';
    wrap.setAttribute('role', 'status');

    if (typeof copy === 'string') {
      // Backward-compatible with the pre-2026-08-03 single-string shape.
      var only = document.createElement('p');
      only.textContent = copy;
      wrap.appendChild(only);
      return wrap;
    }

    if (copy.heading) {
      var h = document.createElement('p');
      h.className = 'bhp-offshore-notice__heading';
      h.textContent = copy.heading;
      wrap.appendChild(h);
    }
    if (copy.body) {
      var b = document.createElement('p');
      b.textContent = copy.body;
      wrap.appendChild(b);
    }
    if (copy.email) {
      var help = document.createElement('p');
      help.appendChild(document.createTextNode(copy.helpBefore || ''));
      var a = document.createElement('a');
      a.href = 'mailto:' + copy.email;
      a.textContent = copy.email;
      help.appendChild(a);
      help.appendChild(document.createTextNode(copy.helpAfter || ''));
      wrap.appendChild(help);
    }
    return wrap;
  }

  function offshoreNotice() {
    if (!COPY.offshoreNotice) { return; }
    var select = document.getElementById('shipping-state') || document.getElementById('billing-state');
    var value = '';
    if (select) {
      value = (select.value || '').toUpperCase();
      if (!value && select.getAttribute) { value = (select.getAttribute('data-value') || '').toUpperCase(); }
    }
    // The state control is sometimes a combobox <input> rather than a <select>.
    if (!value) {
      var combo = document.querySelector('#shipping-state, [id$="-state"] input');
      if (combo && combo.value) { value = combo.value.trim().toUpperCase(); }
    }
    var isOffshore = OFFSHORE.some(function (code) {
      return value === code || value === ({ AK: 'ALASKA', HI: 'HAWAII' })[code];
    });

    var host = document.querySelector('.wp-block-woocommerce-checkout-shipping-methods-block .wc-block-components-checkout-step__content')
      || document.querySelector('.wp-block-woocommerce-checkout-shipping-address-block .wc-block-components-checkout-step__content');
    if (!host) { return; }
    var existing = host.querySelector('.bhp-offshore-notice');
    if (isOffshore && !existing) {
      host.insertBefore(buildOffshoreNode(COPY.offshoreNotice), host.firstChild);
    } else if (!isOffshore && existing) {
      existing.remove();
    }
  }

  /* ==================================================================
   * 2C-4 — REMOVING A LINE ITEM FROM THE CHECKOUT ORDER SUMMARY
   * ==================================================================
   *
   * Andrew, final staging walk, verbatim: "you are unable to remove items
   * from your cart on the checkout page- the order summary should allow
   * that". (Relayed through the Chief of Staff; NOT witnessed first-hand by
   * this agent.)
   *
   * VERIFIED BEFORE BUILDING, on staging 1.19.153 at 390px and 1440px:
   * `.wc-block-components-order-summary-item` contains an image, a quantity
   * badge, a name, prices and metadata — and no control of any kind. There
   * was nothing to fix; the control did not exist.
   *
   * ⭐ THE MECHANISM IS BLOCKS' OWN DATA STORE, NOT A RAW FETCH.
   *
   *    `wp.data.dispatch('wc/store/cart').removeItemFromCart(key)` is the
   *    same action WooCommerce's own cart-page remove link dispatches. It
   *    calls the Store API `cart/remove-item` route, receives the whole new
   *    cart back, and writes it into the `wc/store/cart` store — so totals,
   *    shipping, tax and the item list all re-render from ONE source of
   *    truth. Verified live on staging that the action exists on this build
   *    (it is in `Object.keys(wp.data.dispatch('wc/store/cart'))`).
   *
   *    ⛔ WHY NOT A DIRECT `fetch()` TO cart/remove-item. It works, and it is
   *       WRONG here: the Store API would then hold one cart while the React
   *       store still held the old one, so the summary would keep showing the
   *       removed book and the total would keep including it until a reload.
   *       A stale total on the page that takes the money is a worse defect
   *       than the one being fixed. The raw fetch survives ONLY as the
   *       fallback below, and that fallback reloads the page precisely so the
   *       two can never disagree.
   *
   * ⭐ KEY MAPPING IS CHECKED, NOT ASSUMED — this is the part that can go
   *    quietly wrong. The DOM gives no cart-item key. Items are matched by
   *    position against the store's own item list AND the product name must
   *    agree; if it does not, a name search runs; if that fails too, NO
   *    BUTTON IS RENDERED for that row. Removing the wrong book because two
   *    lists drifted out of step is exactly the failure class that produced
   *    the "$3.99 on a one-paperback cart" measurement in the 2B build, and
   *    a missing button is a visible, harmless failure where a wrong one is
   *    an invisible, expensive one.
   *
   * The control is appended as the LAST child of the item, never inserted
   * between nodes React owns, and it is re-added by the existing observer
   * after every re-render.
   */
  function cartStore() {
    if (!window.wp || !window.wp.data) { return null; }
    try {
      var sel = window.wp.data.select('wc/store/cart');
      var dis = window.wp.data.dispatch('wc/store/cart');
      if (!sel || !dis || typeof dis.removeItemFromCart !== 'function') { return null; }
      return { select: sel, dispatch: dis };
    } catch (e) { return null; }
  }

  function normName(s) {
    return (s || '').replace(/\s+/g, ' ').trim().toLowerCase();
  }

  /** Raw Store API removal + reload. Only used when wp.data is unavailable. */
  function fallbackRemove(key) {
    return fetch('/wp-json/wc/store/v1/cart', { headers: { Accept: 'application/json' } })
      .then(function (r) { return r.headers.get('Nonce'); })
      .then(function (nonce) {
        return fetch('/wp-json/wc/store/v1/cart/remove-item', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', Nonce: nonce || '' },
          body: JSON.stringify({ key: key }),
        });
      })
      .then(function () { window.location.reload(); });
  }

  function orderSummaryRemove() {
    // Checkout only. The cart page already has WooCommerce's own remove link.
    if (!document.querySelector('.wp-block-woocommerce-checkout')) { return; }
    var store = cartStore();
    var items = [];
    if (store) {
      try { items = (store.select.getCartData() || {}).items || []; } catch (e) { items = []; }
    }
    if (!items.length) { return; }

    document.querySelectorAll('.wp-block-woocommerce-checkout-order-summary-block').forEach(function (block) {
      var rows = block.querySelectorAll('.wc-block-components-order-summary-item');
      var used = {};
      Array.prototype.forEach.call(rows, function (row, i) {
        var nameEl = row.querySelector('.wc-block-components-product-name');
        var domName = normName(nameEl && nameEl.textContent);

        // 1. same position, and the name must agree.
        var match = (items[i] && normName(items[i].name) === domName) ? items[i] : null;
        // 2. otherwise the first unclaimed item with that exact name.
        if (!match) {
          for (var j = 0; j < items.length; j++) {
            if (!used[items[j].key] && normName(items[j].name) === domName) { match = items[j]; break; }
          }
        }
        // 3. otherwise nothing. Deliberately no button rather than a guess.
        if (!match) { return; }
        used[match.key] = true;

        var existing = row.querySelector('.bhp-summary-remove');
        if (existing) {
          existing.setAttribute('data-key', match.key);
          return;
        }

        var wrap = document.createElement('div');
        wrap.className = 'bhp-summary-remove-wrap';
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'bhp-summary-remove';
        btn.textContent = COPY.removeLabel || 'Remove';
        btn.setAttribute('data-key', match.key);
        if (COPY.removeAria && nameEl) {
          btn.setAttribute('aria-label', COPY.removeAria.replace('%s', nameEl.textContent.trim()));
        }
        btn.addEventListener('click', function (ev) {
          ev.preventDefault();
          ev.stopPropagation();
          if (btn.disabled) { return; }
          var key = btn.getAttribute('data-key');
          btn.disabled = true;
          btn.setAttribute('aria-busy', 'true');
          btn.textContent = COPY.removingLabel || 'Removing…';
          var s = cartStore();
          var done = function () { schedule(); };
          var failed = function () {
            btn.disabled = false;
            btn.removeAttribute('aria-busy');
            btn.textContent = COPY.removeLabel || 'Remove';
            if (COPY.removeFailed) { btn.setAttribute('title', COPY.removeFailed); }
          };
          try {
            if (s) {
              var p = s.dispatch.removeItemFromCart(key);
              if (p && typeof p.then === 'function') { p.then(done, failed); } else { done(); }
            } else {
              fallbackRemove(key).catch(failed);
            }
          } catch (e) { failed(); }
        });
        wrap.appendChild(btn);
        /*
         * ⚠ IT GOES INSIDE `__description`, NOT INSIDE THE ITEM — and this was
         *   found by measuring, not by reasoning. The first build appended it
         *   to the item and allowed the row to wrap. Measured result at 390px:
         *   the item grew 94px -> 224px and the DESCRIPTION dropped BELOW the
         *   48px cover instead of sitting beside it. Desktop was unaffected
         *   (592px of room), which is exactly how a mobile-only layout
         *   regression ships unnoticed. `__description` is a normal block
         *   column that already stacks name, price and metadata, so the
         *   control joins that stack and the row's flex line is untouched.
         */
        (row.querySelector('.wc-block-components-order-summary-item__description') || row).appendChild(wrap);
      });
    });
  }

  /**
   * 2C-4b — the empty state a customer lands in after removing the last item.
   *
   * VERIFIED LIVE on staging 1.19.153 before this was written, because the
   * brief asked which of two things happens and the answer had to be measured
   * rather than assumed: WooCommerce Blocks does NOT redirect to /cart/. It
   * renders `.wc-block-checkout-empty` in place — a 100px shopping-trolley
   * glyph, "Your cart is currently empty!", "Checkout is not available whilst
   * your cart is empty…" and a "Browse store" button. The URL stayed
   * `/checkout/` seven seconds after the removal.
   *
   * That is functional and tonally alien, in the same way F11's crying face
   * was. This puts the SAME panel the cart page already shows above it, using
   * the SAME strings (`bhp_empty_cart_copy()`), and CSS demotes the stock
   * text beneath it. Nothing is deleted and no new copy is written; remove
   * this function and the stock state returns exactly.
   */
  function checkoutEmptyPanel() {
    var host = document.querySelector('.wc-block-checkout-empty');
    if (!host || host.querySelector('.bhp-empty-cart')) { return; }
    var c = COPY.emptyCart;
    if (!c || !c.title) { return; }

    var panel = document.createElement('div');
    panel.className = 'bhp-empty-cart';

    // The same compass as the cart page's server-rendered panel. Built as
    // nodes rather than parsed from a string — this file never sets innerHTML.
    var NS = 'http://www.w3.org/2000/svg';
    var mark = document.createElement('span');
    mark.className = 'bhp-empty-cart__mark';
    mark.setAttribute('aria-hidden', 'true');
    var svg = document.createElementNS(NS, 'svg');
    svg.setAttribute('viewBox', '0 0 64 64');
    svg.setAttribute('focusable', 'false');
    svg.setAttribute('aria-hidden', 'true');
    [
      ['circle', { cx: 32, cy: 32, r: 27, fill: 'none', stroke: 'currentColor', 'stroke-width': 1.5, opacity: '.55' }],
      ['circle', { cx: 32, cy: 32, r: 21, fill: 'none', stroke: 'currentColor', 'stroke-width': 1, opacity: '.3' }],
      ['path', { d: 'M32 8v6M32 50v6M8 32h6M50 32h6', stroke: 'currentColor', 'stroke-width': 1.5, 'stroke-linecap': 'round', opacity: '.55', fill: 'none' }],
      ['path', { d: 'M32 15l4.4 12.6L49 32l-12.6 4.4L32 49l-4.4-12.6L15 32l12.6-4.4z', fill: 'currentColor' }],
    ].forEach(function (pair) {
      var n = document.createElementNS(NS, pair[0]);
      Object.keys(pair[1]).forEach(function (k) { n.setAttribute(k, pair[1][k]); });
      svg.appendChild(n);
    });
    mark.appendChild(svg);
    panel.appendChild(mark);

    var h = document.createElement('h2');
    h.className = 'bhp-empty-cart__title';
    h.textContent = c.title;
    panel.appendChild(h);

    var p = document.createElement('p');
    p.className = 'bhp-empty-cart__text';
    p.textContent = c.text;
    panel.appendChild(p);

    var actions = document.createElement('div');
    actions.className = 'bhp-empty-cart__actions';
    if (c.primaryUrl && c.primaryLabel) {
      var a1 = document.createElement('a');
      a1.className = 'btn btn-cta-primary bhp-empty-cart__cta';
      a1.href = c.primaryUrl;
      a1.textContent = c.primaryLabel;
      actions.appendChild(a1);
    }
    if (c.secondaryUrl && c.secondaryLabel) {
      var a2 = document.createElement('a');
      a2.className = 'btn bhp-empty-cart__cta bhp-empty-cart__cta--quiet';
      a2.href = c.secondaryUrl;
      a2.textContent = c.secondaryLabel;
      actions.appendChild(a2);
    }
    panel.appendChild(actions);

    host.insertBefore(panel, host.firstChild);
    host.classList.add('bhp-checkout-empty--branded');
  }

  function apply() {
    try {
      zipKeyboard();
      shippingScopeNotice();
      offshoreNotice();
      orderSummaryRemove();
      checkoutEmptyPanel();
    } catch (e) { /* never let an enhancement break a checkout */ }
  }

  function start() {
    apply();
    var root = document.querySelector('.wp-block-woocommerce-checkout') || document.body;
    if (typeof MutationObserver === 'function') {
      new MutationObserver(schedule).observe(root, { childList: true, subtree: true });
    }
    document.addEventListener('change', schedule, true);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
