/**
 * EARLY CART CAPTURE — theme 1.19.316, 2026-08-28.
 * CYCLE168-CX-EARLY-CART-CAPTURE.
 *
 * ⛔ FUNNEL ISOLATION: every storage key written here is prefixed
 *    `bhp_cart_capture` and every analytics event is prefixed `cart_capture`.
 *    This file NEVER reads or writes `bhp_parent_popup*` or
 *    `bhp_mariana_popup*`, and it is deliberately NOT built on
 *    assets/js/mariana-popup.js -- see .claude/rules/funnels.md, which
 *    forbids forking that engine for a third funnel. This is not a third
 *    funnel; it is a cart-page panel.
 *
 * ⛔ THIS FILE SETS NO COOKIES. Page load performs no storage write at all.
 *    The only cookie in this feature (`mailchimp_user_email`) is written
 *    SERVER-SIDE by the Mailchimp plugin, and only after the shopper has
 *    typed an address and pressed the button. That ordering is what keeps
 *    the surface clean pre-consent, and the test suite asserts it.
 */
( function () {
	'use strict';

	var cfg = window.bhpCartCapture;
	if ( ! cfg ) {
		return;
	}

	/**
	 * ⛔ DOM-READY GUARD, AND IT EXISTS BECAUSE THE FEATURE ONCE SHIPPED INERT.
	 *
	 * This script is enqueued in the footer, and the panel is printed on
	 * `wp_footer`. When both sat at priority 20, WordPress emitted the SCRIPT
	 * TAG FIRST -- so `getElementById('bhp-cart-capture')` ran against a DOM
	 * that did not contain the panel yet, returned null, and this whole IIFE
	 * returned early. No listeners, no reveal, no capture. Observed live on
	 * staging 2026-08-28 at `window.innerWidth` 1280, while the PHP suite was
	 * 72/72 green.
	 *
	 * The PHP side now renders at priority 5. This guard is the second half:
	 * it makes the script correct regardless of emission order, so a future
	 * hook-priority change cannot silently kill the feature again.
	 */
	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot, { once: true } );
	} else {
		boot();
	}

	function boot() {

	var panel = document.getElementById( 'bhp-cart-capture' );
	if ( ! panel ) {
		return;
	}

	var KEY_DISMISSED = cfg.storagePrefix + '_dismissed_at';
	var KEY_DONE      = cfg.storagePrefix + '_done';

	var form      = panel.querySelector( '.bhp-cart-capture__form' );
	var emailEl   = panel.querySelector( '.bhp-cart-capture__email' );
	var submitEl  = panel.querySelector( '.bhp-cart-capture__submit' );
	var statusEl  = panel.querySelector( '.bhp-cart-capture__status' );
	var closeEl   = panel.querySelector( '.bhp-cart-capture__close' );

	var revealed = false;
	var settled  = false;

	/** Storage can throw (private mode, blocked site data). Never let it break the page. */
	function readStore( key ) {
		try {
			return window.localStorage.getItem( key );
		} catch ( e ) {
			return null;
		}
	}

	function writeStore( key, value ) {
		try {
			window.localStorage.setItem( key, value );
		} catch ( e ) {
			/* no-op: a dismissal we cannot remember is a smaller problem than a thrown error */
		}
	}

	function track( event, extra ) {
		try {
			window.dataLayer = window.dataLayer || [];
			var payload = { event: cfg.eventPrefix + '_' + event, cart_capture_variant: cfg.variant };
			if ( extra ) {
				Object.keys( extra ).forEach( function ( k ) { payload[ k ] = extra[ k ]; } );
			}
			window.dataLayer.push( payload );
		} catch ( e ) {
			/* analytics must never block the feature */
		}
	}

	/** Already captured, or dismissed inside the respect window? Then stay hidden. */
	function suppressed() {
		if ( readStore( KEY_DONE ) === '1' ) {
			return true;
		}

		var at = parseInt( readStore( KEY_DISMISSED ) || '0', 10 );
		if ( ! at ) {
			return false;
		}

		var days = ( Date.now() - at ) / 86400000;
		return days < ( cfg.dismissDays || 30 );
	}

	/**
	 * ⭐ SIBLING, NEVER CHILD. The Blocks cart is a React root and anything
	 *    placed inside it is destroyed on the next Store API re-render (which
	 *    happens on every quantity change). Inserting after the root's closing
	 *    boundary keeps the panel alive across those re-renders.
	 */
	function position() {
		var cart = document.querySelector( '.wp-block-woocommerce-cart' );
		if ( cart && cart.parentNode ) {
			cart.parentNode.insertBefore( panel, cart.nextSibling );
			return;
		}
		// Classic-cart fallback. If neither exists the panel simply stays in
		// the footer, which is still usable rather than broken.
		var classic = document.querySelector( '.woocommerce-cart-form' );
		if ( classic && classic.parentNode ) {
			classic.parentNode.insertBefore( panel, classic.nextSibling );
		}
	}

	function reveal( trigger ) {
		if ( revealed || settled || suppressed() ) {
			return;
		}
		revealed = true;
		panel.hidden = false;

		/**
		 * ⛔ rAF ALONE IS NOT ENOUGH, AND THIS WAS OBSERVED, NOT THEORISED.
		 *
		 * `requestAnimationFrame` DOES NOT FIRE IN A BACKGROUND TAB. The panel
		 * un-hides (so it occupies layout) but `is-visible` is never added, so
		 * CSS holds it at `opacity: 0` -- a 284px INVISIBLE GAP above the
		 * footer. Measured on staging 2026-08-28 at `window.innerWidth` 1280
		 * with `document.hidden === true`: height 284, opacity "0", class list
		 * still bare `bhp-cart-capture`.
		 *
		 * ⭐ That is a REAL shopper path, not just a test artefact: opening the
		 *    cart in a new background tab (middle-click, "open in new tab") is
		 *    ordinary behaviour. It would self-correct when the tab was focused,
		 *    but "renders as a blank gap until you focus it" is not acceptable.
		 *
		 * ⭐ SO THE CLASS IS ADDED BY WHICHEVER FIRES FIRST. `setTimeout` is
		 *    throttled in background tabs but it DOES run, so the panel is
		 *    always eventually visible. Adding the class twice is harmless.
		 */
		var show = function () { panel.classList.add( 'is-visible' ); };
		window.requestAnimationFrame( show );
		window.setTimeout( show, 32 );

		track( 'shown', { cart_capture_trigger: trigger } );
	}

	function dismiss() {
		writeStore( KEY_DISMISSED, String( Date.now() ) );
		settled = true;
		panel.classList.remove( 'is-visible' );
		window.setTimeout( function () { panel.hidden = true; }, 250 );
		track( 'dismissed' );
	}

	function setStatus( message, isError ) {
		if ( ! statusEl ) {
			return;
		}
		statusEl.textContent = message || '';
		statusEl.classList.toggle( 'is-error', !! isError );
	}

	function submit( e ) {
		e.preventDefault();

		var email = ( emailEl && emailEl.value ? emailEl.value : '' ).trim();
		if ( ! email || email.indexOf( '@' ) < 1 ) {
			setStatus( 'That does not look like an email address yet.', true );
			if ( emailEl ) { emailEl.focus(); }
			return;
		}

		submitEl.disabled = true;
		setStatus( 'Saving...' );

		var body = new window.FormData();
		body.append( 'action', cfg.action );
		body.append( 'nonce', cfg.nonce );
		body.append( 'email', email );
		var hp = panel.querySelector( '[name="bhp_website"]' );
		body.append( 'bhp_website', hp ? hp.value : '' );

		window.fetch( cfg.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body
		} )
			.then( function ( r ) { return r.json(); } )
			.then( function ( data ) {
				if ( data && data.ok ) {
					writeStore( KEY_DONE, '1' );
					settled = true;
					if ( form ) { form.hidden = true; }
					setStatus( data.message || 'Saved.' );
					track( 'saved' );
					return;
				}

				submitEl.disabled = false;
				var code = data && data.code ? data.code : 'error';
				if ( code === 'invalid_email' ) {
					setStatus( 'That does not look like an email address yet.', true );
				} else if ( code === 'rate_limited' ) {
					setStatus( 'Too many tries just now. Give it a minute.', true );
				} else if ( code === 'empty_cart' ) {
					setStatus( 'Your cart looks empty now.', true );
				} else {
					setStatus( 'I could not save that just now. Please try again.', true );
				}
				track( 'failed', { cart_capture_error: code } );
			} )
			.catch( function () {
				submitEl.disabled = false;
				setStatus( 'I could not save that just now. Please try again.', true );
				track( 'failed', { cart_capture_error: 'network' } );
			} );
	}

	// ── wiring ────────────────────────────────────────────────────────────
	position();

	if ( suppressed() ) {
		// Respect an earlier "no". Nothing is shown and nothing is tracked.
		return;
	}

	if ( form ) { form.addEventListener( 'submit', submit ); }
	if ( closeEl ) { closeEl.addEventListener( 'click', dismiss ); }

	// Reveal on the FIRST of: dwell, exit intent, or reaching the foot of the
	// page. Three low-friction signals that the shopper has paused, rather
	// than one interrupt fired at everybody on arrival.
	window.setTimeout( function () { reveal( 'dwell' ); }, ( cfg.revealAfter || 8 ) * 1000 );

	document.addEventListener( 'mouseout', function ( e ) {
		if ( ! e.relatedTarget && e.clientY <= 0 ) {
			reveal( 'exit_intent' );
		}
	} );

	window.addEventListener( 'scroll', function () {
		var reached = ( window.innerHeight + window.scrollY ) >= ( document.body.offsetHeight - 120 );
		if ( reached ) {
			reveal( 'scroll_end' );
		}
	}, { passive: true } );

	} // end boot()
}() );
