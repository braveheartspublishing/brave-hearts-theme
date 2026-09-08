/**
 * EARLY CART CAPTURE — v1 theme 1.19.316, v2 this build.
 * v1 CYCLE168-CX-EARLY-CART-CAPTURE, v2 CYCLE179-CX-EARLY-CART-CAPTURE-2.
 *
 * ⛔ FUNNEL ISOLATION: every storage key written here is prefixed
 *    `bhp_cart_capture` and every analytics event is prefixed `cart_capture`.
 *    This file NEVER reads or writes `bhp_parent_popup*` or
 *    `bhp_mariana_popup*`, and it is deliberately NOT built on
 *    assets/js/mariana-popup.js -- see .claude/rules/funnels.md, which
 *    forbids forking that engine for a third funnel. This is not a third
 *    funnel; it is a cart panel.
 *
 * ⛔ THIS FILE SETS NO COOKIES. Page load performs no storage write at all.
 *    The only cookie in this feature (`mailchimp_user_email`) is written
 *    SERVER-SIDE by the Mailchimp plugin, and only after the shopper has
 *    typed an address and pressed the button. That ordering is what keeps
 *    the surface clean pre-consent, and the test suite asserts it.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ TWO SURFACES, ONE STATE MACHINE — and the asymmetry is deliberate
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * · `/cart/`  — the panel is SERVER-RENDERED into the page (uncached surface)
 *   and is INLINE AND VISIBLE under the cart items. It is not an interruption,
 *   so it does not need a reveal timer and does not get one.
 *
 * · THE DRAWER — the panel is FETCHED from `admin-ajax.php` the first time the
 *   drawer opens, because the drawer opens on PRODUCT PAGES, WHICH ARE CACHED,
 *   and the panel carries a nonce. Printing it into a cacheable page would
 *   hand one shopper's nonce to every other visitor of that URL. See the
 *   long cache note in `inc/early-cart-capture.php`.
 *
 * ⛔ THE DRAWER PANEL IS INSERTED AS A SIBLING INSIDE `.bhp-cart-drawer__body`,
 *    AFTER `.bhp-cart-drawer__message`. `bundle-drawer.js` wipes `__items`,
 *    `__message`, `__crosssell` and `__summary` with `innerHTML = ''` on every
 *    render and never touches `__body` itself, so a sibling survives. Putting
 *    it INSIDE any of those four nodes would destroy it, along with whatever
 *    the shopper had typed, on the next quantity change.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ THE FREQUENCY CAP, AND WHAT "ONCE PER SESSION" WAS TAKEN TO MEAN
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * The brief said "once per session, dismissible". An inline element on the
 * cart page and an interruption in the drawer are not the same thing, so the
 * cap is applied where it means something:
 *
 *   · THE DRAWER shows the panel AT MOST ONCE PER BROWSER SESSION. The drawer
 *     opens on every add-to-cart, so without this it would reappear on every
 *     add. This is the cap the brief is actually about.
 *   · `/cart/` renders it inline for as long as it has not been dismissed or
 *     completed. Re-rendering a page element the shopper has not dismissed is
 *     not a second ask.
 *   · EXIT INTENT emphasises it at most once per session.
 *   · A DISMISSAL ends it everywhere, for the rest of the session and for
 *     `dismissDays` after.
 *
 * ⚠ `sessionStorage` IS PER TAB, NOT PER BROWSER WINDOW. A shopper who opens
 *   a second tab gets a second "session" by this measure. That is stated
 *   rather than hidden; it is the closest primitive the platform offers
 *   without setting a cookie, and this file sets no cookies.
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
	 * This script is enqueued in the footer, and the cart-page panel is printed
	 * on `wp_footer`. When both sat at priority 20, WordPress emitted the
	 * SCRIPT TAG FIRST -- so the panel lookup ran against a DOM that did not
	 * contain the panel yet, returned null, and this whole IIFE returned early.
	 * No listeners, no reveal, no capture. Observed live on staging 2026-08-28
	 * at `window.innerWidth` 1280, while the PHP suite was 72/72 green.
	 *
	 * The PHP side renders at priority 5. This guard is the second half: it
	 * makes the script correct regardless of emission order, so a future
	 * hook-priority change cannot silently kill the feature again.
	 */
	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot, { once: true } );
	} else {
		boot();
	}

	function boot() {

	var KEY_DISMISSED     = cfg.storagePrefix + '_dismissed_at';
	var KEY_DONE          = cfg.storagePrefix + '_done';
	var KEY_SESSION_DRAWER = cfg.storagePrefix + '_drawer_shown';
	var KEY_SESSION_EXIT   = cfg.storagePrefix + '_exit_shown';

	var settled       = false;
	var drawerFetched = false;

	/* ── storage, all of it fallible ──────────────────────────────────────
	 * Storage can throw (private mode, blocked site data). Never let it break
	 * the page: a dismissal we cannot remember is a smaller problem than a
	 * thrown error on the cart.
	 */
	function readStore( store, key ) {
		try {
			return window[ store ].getItem( key );
		} catch ( e ) {
			return null;
		}
	}

	function writeStore( store, key, value ) {
		try {
			window[ store ].setItem( key, value );
		} catch ( e ) {
			/* no-op, deliberately */
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

	/** Already captured, or dismissed inside the respect window? Then stay away. */
	function suppressed() {
		if ( settled ) {
			return true;
		}

		if ( readStore( 'localStorage', KEY_DONE ) === '1' ) {
			return true;
		}

		var at = parseInt( readStore( 'localStorage', KEY_DISMISSED ) || '0', 10 );
		if ( ! at ) {
			return false;
		}

		var days = ( Date.now() - at ) / 86400000;
		return days < ( cfg.dismissDays || 30 );
	}

	/* ── wiring one panel, whichever surface it is on ─────────────────────── */

	function wire( panel ) {
		var form     = panel.querySelector( '.bhp-cart-capture__form' );
		var emailEl  = panel.querySelector( '.bhp-cart-capture__email' );
		var optinEl  = panel.querySelector( '.bhp-cart-capture__optin-input' );
		var nonceEl  = panel.querySelector( '.bhp-cart-capture__nonce' );
		var submitEl = panel.querySelector( '.bhp-cart-capture__submit' );
		var statusEl = panel.querySelector( '.bhp-cart-capture__status' );
		var closeEl  = panel.querySelector( '.bhp-cart-capture__close' );
		var surface  = panel.getAttribute( 'data-surface' ) || 'cart';

		function setStatus( message, isError ) {
			if ( ! statusEl ) {
				return;
			}
			statusEl.textContent = message || '';
			statusEl.classList.toggle( 'is-error', !! isError );
		}

		function hideEverywhere() {
			var all = document.querySelectorAll( '[data-bhp-cart-capture]' );
			Array.prototype.forEach.call( all, function ( el ) {
				el.classList.remove( 'is-visible' );
				window.setTimeout( function () { el.hidden = true; }, 250 );
			} );
		}

		function dismiss() {
			writeStore( 'localStorage', KEY_DISMISSED, String( Date.now() ) );
			settled = true;
			hideEverywhere();
			track( 'dismissed', { cart_capture_surface: surface } );
		}

		function submit( e ) {
			e.preventDefault();

			var email = ( emailEl && emailEl.value ? emailEl.value : '' ).trim();
			if ( ! email || email.indexOf( '@' ) < 1 ) {
				setStatus( 'That does not look like an email address yet.', true );
				if ( emailEl ) { emailEl.focus(); }
				return;
			}

			/*
			 * ⛔ THE NONCE COMES FROM THE FORM FIRST, THE CONFIG SECOND.
			 *    The drawer panel arrives from an uncached fetch and carries
			 *    its own fresh nonce in a hidden input; the cart-page panel
			 *    carries one too. The config nonce exists only on `/cart/` and
			 *    is the fallback, so there is ONE code path here rather than
			 *    two that can drift apart.
			 */
			var nonce = ( nonceEl && nonceEl.value ) ? nonceEl.value : ( cfg.nonce || '' );
			if ( ! nonce ) {
				setStatus( 'I could not save that just now. Please try again.', true );
				track( 'failed', { cart_capture_error: 'no_nonce', cart_capture_surface: surface } );
				return;
			}

			submitEl.disabled = true;
			setStatus( 'Saving...' );

			var body = new window.FormData();
			body.append( 'action', cfg.action );
			body.append( 'nonce', nonce );
			body.append( 'email', email );

			/*
			 * ⛔ THE FIELD IS SENT ONLY WHEN IT IS TICKED, which is exactly what
			 *    a real form does with an unchecked box. The server treats an
			 *    absent field as NO. Sending "0" for an untick would work too,
			 *    but then two wire states would mean "no" and only one of them
			 *    would be tested.
			 */
			if ( optinEl && optinEl.checked ) {
				body.append( 'optin', '1' );
			}

			var hp = panel.querySelector( '[name="bhp_website"]' );
			body.append( 'bhp_website', hp ? hp.value : '' );

			track( 'submitted', {
				cart_capture_surface: surface,
				cart_capture_optin: ( optinEl && optinEl.checked ) ? 1 : 0
			} );

			window.fetch( cfg.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: body
			} )
				.then( function ( r ) { return r.json(); } )
				.then( function ( data ) {
					if ( data && data.ok ) {
						writeStore( 'localStorage', KEY_DONE, '1' );
						settled = true;
						if ( form ) { form.hidden = true; }
						setStatus( data.message || 'Saved.' );
						track( 'saved', {
							cart_capture_surface: surface,
							cart_capture_optin: ( optinEl && optinEl.checked ) ? 1 : 0
						} );
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
					track( 'failed', { cart_capture_error: code, cart_capture_surface: surface } );
				} )
				.catch( function () {
					submitEl.disabled = false;
					setStatus( 'I could not save that just now. Please try again.', true );
					track( 'failed', { cart_capture_error: 'network', cart_capture_surface: surface } );
				} );
		}

		if ( form ) { form.addEventListener( 'submit', submit ); }
		if ( closeEl ) { closeEl.addEventListener( 'click', dismiss ); }

		return {
			el: panel,
			emailEl: emailEl,
			surface: surface
		};
	}

	/**
	 * Un-hide a panel.
	 *
	 * ⛔ rAF ALONE IS NOT ENOUGH, AND THIS WAS OBSERVED, NOT THEORISED.
	 *
	 * `requestAnimationFrame` DOES NOT FIRE IN A BACKGROUND TAB. The panel
	 * un-hides (so it occupies layout) but `is-visible` is never added, so CSS
	 * holds it at `opacity: 0` -- a 284px INVISIBLE GAP above the footer.
	 * Measured on staging 2026-08-28 at `window.innerWidth` 1280 with
	 * `document.hidden === true`.
	 *
	 * ⭐ That is a REAL shopper path: opening the cart in a background tab
	 *    (middle-click, "open in new tab") is ordinary behaviour.
	 *
	 * ⭐ SO THE CLASS IS ADDED BY WHICHEVER FIRES FIRST. `setTimeout` is
	 *    throttled in background tabs but it DOES run. Adding it twice is
	 *    harmless.
	 */
	function show( panel ) {
		panel.hidden = false;
		var add = function () { panel.classList.add( 'is-visible' ); };
		window.requestAnimationFrame( add );
		window.setTimeout( add, 32 );
	}

	/* ── surface 1: the cart page ─────────────────────────────────────────── */

	var cartPanel = document.querySelector( '.bhp-cart-capture--cart' );

	if ( cartPanel ) {
		/**
		 * ⭐ SIBLING, NEVER CHILD. The Blocks cart is a React root and anything
		 *    placed inside it is destroyed on the next Store API re-render
		 *    (which happens on every quantity change). Inserting after the
		 *    root's closing boundary keeps the panel alive across those
		 *    re-renders.
		 */
		var cartRoot = document.querySelector( '.wp-block-woocommerce-cart' );
		if ( cartRoot && cartRoot.parentNode ) {
			cartRoot.parentNode.insertBefore( cartPanel, cartRoot.nextSibling );
		} else {
			// Classic-cart fallback. If neither exists the panel simply stays
			// in the footer, which is still usable rather than broken.
			var classic = document.querySelector( '.woocommerce-cart-form' );
			if ( classic && classic.parentNode ) {
				classic.parentNode.insertBefore( cartPanel, classic.nextSibling );
			}
		}

		if ( ! suppressed() ) {
			var cartWired = wire( cartPanel );
			show( cartPanel );
			track( 'shown', { cart_capture_trigger: 'inline', cart_capture_surface: 'cart' } );

			/*
			 * ⭐ EXIT INTENT NOW EMPHASISES RATHER THAN REVEALS. In v1 the panel
			 *    was hidden and exit intent was one of three ways to un-hide
			 *    it. It is inline and visible now, so "show the prompt" on exit
			 *    means bring it to the shopper's attention: scroll it into view
			 *    and put the caret in the field. Once per session.
			 */
			document.addEventListener( 'mouseout', function ( e ) {
				if ( e.relatedTarget || e.clientY > 0 ) {
					return;
				}
				if ( suppressed() ) {
					return;
				}
				if ( readStore( 'sessionStorage', KEY_SESSION_EXIT ) === '1' ) {
					return;
				}
				writeStore( 'sessionStorage', KEY_SESSION_EXIT, '1' );

				cartPanel.classList.add( 'is-emphasised' );
				try {
					cartPanel.scrollIntoView( { behavior: 'smooth', block: 'center' } );
				} catch ( err ) {
					cartPanel.scrollIntoView();
				}
				if ( cartWired.emailEl ) {
					cartWired.emailEl.focus( { preventScroll: true } );
				}
				track( 'shown', { cart_capture_trigger: 'exit_intent', cart_capture_surface: 'cart' } );
			} );
		}
	}

	/* ── surface 2: the cart drawer ───────────────────────────────────────── */

	/*
	 * ⛔ NOT ON `/cart/`. The drawer can be opened from the cart page too, and
	 *    a second panel behind the same overlay would be a second ask for the
	 *    same thing. The page panel is right there when the drawer closes.
	 */
	/*
	 * ⛔⛔ `cfg.isCart` IS A STRING, AND `"0"` IS TRUTHY IN JAVASCRIPT. This is
	 *     the second half of a defect found by opening the drawer in a real
	 *     browser on staging 1.19.391, 2026-09-07 — not by reading this file.
	 *
	 * ⛔ WHAT HAPPENED: PHP localises `'isCart' => 0`, but
	 *    `wp_localize_script()` stringifies every scalar, so the browser
	 *    receives `"0"`. `cfg.isCart ? null : ...` therefore took the TRUTHY
	 *    branch on every page that is not the cart, `drawer` was null, the
	 *    MutationObserver was never attached, and the drawer panel could not
	 *    appear anywhere. Observed value in the page: `isCart: "0"`.
	 *
	 * ⭐ THE COMPARISON IS EXPLICIT AND ACCEPTS BOTH SHAPES, so it stays
	 *    correct if a future pass switches to `wp_add_inline_script`/JSON, where
	 *    the same value would arrive as a real number.
	 *
	 * ⛔ DO NOT "TIDY" THIS BACK TO A BARE TRUTHINESS TEST. It reads as
	 *    redundant and is not.
	 */
	var isCartSurface = ( cfg.isCart === '1' || cfg.isCart === 1 || cfg.isCart === true );

	var drawer = isCartSurface ? null : document.getElementById( 'bhp-cart-drawer' );

	if ( drawer ) {
		/*
		 * ⭐ A MUTATION OBSERVER, NOT A PLUGIN EDIT. `openDrawer()` in
		 *    bundle-drawer.js sets `aria-hidden="false"` and adds `is-open`.
		 *    Watching for that needs no hook, no event and not one byte of
		 *    change in the plugin, which is another lane's code.
		 */
		var observer = new window.MutationObserver( function () {
			if ( drawer.getAttribute( 'aria-hidden' ) === 'false' ) {
				onDrawerOpen();
			}
		} );
		observer.observe( drawer, { attributes: true, attributeFilter: [ 'aria-hidden', 'class' ] } );

		// The drawer can already be open on this load in some paths.
		if ( drawer.getAttribute( 'aria-hidden' ) === 'false' ) {
			onDrawerOpen();
		}
	}

	function onDrawerOpen() {
		if ( drawerFetched || suppressed() ) {
			return;
		}

		// ⭐ THE CAP THE BRIEF IS ACTUALLY ABOUT. The drawer opens on every
		//    add-to-cart; without this the panel would appear on every one.
		if ( readStore( 'sessionStorage', KEY_SESSION_DRAWER ) === '1' ) {
			return;
		}

		drawerFetched = true;

		var body = new window.FormData();
		body.append( 'action', cfg.panelAction );

		window.fetch( cfg.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body
		} )
			.then( function ( r ) { return r.json(); } )
			.then( function ( data ) {
				if ( ! data || ! data.ok || ! data.render || ! data.html ) {
					return;
				}
				if ( suppressed() ) {
					return;
				}

				var host = drawer.querySelector( '.bhp-cart-drawer__body' );
				if ( ! host ) {
					return;
				}

				var wrap = document.createElement( 'div' );
				wrap.innerHTML = data.html;
				var panel = wrap.querySelector( '[data-bhp-cart-capture]' );
				if ( ! panel ) {
					return;
				}

				/*
				 * ⛔ APPENDED TO `__body`, AFTER `__message`. It is a SIBLING of
				 *    the four nodes bundle-drawer.js wipes by innerHTML, never a
				 *    child of one, so a quantity change cannot destroy it or
				 *    the address the shopper has half-typed into it.
				 */
				var messageEl = host.querySelector( '.bhp-cart-drawer__message' );
				if ( messageEl && messageEl.nextSibling ) {
					host.insertBefore( panel, messageEl.nextSibling );
				} else {
					host.appendChild( panel );
				}

				wire( panel );
				show( panel );
				writeStore( 'sessionStorage', KEY_SESSION_DRAWER, '1' );
				track( 'shown', { cart_capture_trigger: 'drawer_open', cart_capture_surface: 'drawer' } );
			} )
			.catch( function () {
				/* A failed panel fetch is silent. The shopper loses a prompt,
				   not a cart. */
			} );
	}

	} // end boot()
}() );
