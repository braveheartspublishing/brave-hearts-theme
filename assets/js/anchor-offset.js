/**
 * anchor-offset.js — 1.19.390 (2026-09-07, CYCLE179-LD-BUILD-390)
 *
 * Two small reading aids that both need a measurement CSS cannot take.
 *
 * 1. THE ANCHOR OFFSET. `.site-header` is `position: sticky; top: 0` at
 *    z-index 100, so the browser's default anchor scroll puts a heading at
 *    viewport y=0 and the header paints over it. style.css declares a static
 *    fallback of 93px on `--bhp-anchor-offset`; this measures the real header
 *    and replaces it. `offsetHeight` alone is not enough: with the WordPress
 *    admin bar the header's own `top` is 32px (46px below 783px), and the
 *    occluded strip is the sum. `getBoundingClientRect().bottom` was rejected
 *    because it is a function of scroll position on a translucent header and
 *    would give a different answer depending on when it ran.
 *
 * 2. THE TABLE SCROLL CUE. `.bhp-table-scroll__hint` says "Scroll for more"
 *    under every wrapped table below 768px and had no way to stop saying it.
 *    This adds `is-scroll-end` to the WRAPPER once the pane reaches its right
 *    edge, and immediately if the pane never overflowed. The class is never
 *    removed: re-showing the line on a leftward swipe would flicker it.
 *
 * No dependencies, no build step, no globals. Everything is guarded so a page
 * without a header or without a table costs one failed querySelector.
 */
( function () {
	'use strict';

	/* ── 1 · ANCHOR OFFSET ────────────────────────────────────────────── */

	var root = document.documentElement;

	function measureHeader() {
		var header = document.querySelector( '.site-header' );
		if ( ! header ) {
			return; // Leave the CSS fallback in place rather than writing a 0.
		}

		var height = header.offsetHeight;
		if ( ! height ) {
			return; // Hidden or not yet laid out — the fallback is still right.
		}

		// The admin bar pushes the sticky header down; both strips occlude.
		var top = parseFloat( window.getComputedStyle( header ).top );
		if ( isNaN( top ) || top < 0 ) {
			top = 0;
		}

		/*
		 * ⭐ `ceil`, NOT `round`, AND IT IS NOT A STYLE CHOICE. MEASURED ON
		 *    STAGING at 1.19.390 before this line existed: `round` gave 80px
		 *    against a header whose bottom edge sat at 80.0, and the heading
		 *    landed at 79.67 — 0.33px BEHIND the header. At 375 it was 0.01px.
		 *    Neither hides a glyph, but the error is always in the same
		 *    direction, and the direction that hurts is under-reserving.
		 *    Rounding up can only ever push the heading further into view.
		 */
		root.style.setProperty( '--bhp-anchor-offset', Math.ceil( height + top ) + 'px' );
	}

	/* ── 2 · TABLE SCROLL CUE ─────────────────────────────────────────── */

	var END_SLOP = 2; // Sub-pixel widths mean scrollLeft never lands exactly.

	function retireHint( wrap, pane ) {
		if ( wrap.classList.contains( 'is-scroll-end' ) ) {
			return true;
		}
		if ( pane.scrollWidth - pane.clientWidth <= END_SLOP ) {
			wrap.classList.add( 'is-scroll-end' ); // Nothing to scroll to.
			return true;
		}
		if ( pane.scrollLeft + pane.clientWidth >= pane.scrollWidth - END_SLOP ) {
			wrap.classList.add( 'is-scroll-end' ); // Reached the right edge.
			return true;
		}
		return false;
	}

	function wireTables() {
		var wraps = document.querySelectorAll( '.bhp-table-scroll' );
		var i;

		for ( i = 0; i < wraps.length; i++ ) {
			( function ( wrap ) {
				var pane = wrap.querySelector( '.bhp-table-scroll__pane' );
				if ( ! pane || pane.getAttribute( 'data-scroll-cue' ) === 'wired' ) {
					return;
				}
				pane.setAttribute( 'data-scroll-cue', 'wired' );

				// A table that does not overflow should never have asked.
				retireHint( wrap, pane );

				// Named so it can unbind itself. The self-reference an
				// anonymous function would need for this is a TypeError under
				// the strict mode this file opts into, so the name is not
				// style — it is the only thing that works here.
				var onScroll = function () {
					if ( retireHint( wrap, pane ) ) {
						pane.removeEventListener( 'scroll', onScroll, false );
					}
				};
				pane.addEventListener( 'scroll', onScroll, false );
			} )( wraps[ i ] );
		}
	}

	/* ── 3 · LIFECYCLE ────────────────────────────────────────────────── */

	function run() {
		measureHeader();
		wireTables();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', run, false );
	} else {
		run();
	}

	// Web fonts and late images change the header's height after DOMContentLoaded.
	window.addEventListener( 'load', run, false );

	/*
	 * ⭐⭐ A ResizeObserver ON THE HEADER ITSELF, AND IT EARNED ITS PLACE.
	 *
	 * The `resize` listener below is necessary but NOT sufficient. Observed on
	 * staging at 1.19.390: emulating a 1440 -> 375 viewport fired no `resize`
	 * event at all, the header grew 80px -> 93px, and the variable stayed at
	 * 80 — the exact 13px under-reservation this whole file exists to prevent.
	 * Dispatching a synthetic `resize` corrected it instantly, which proves the
	 * listener was right and its TRIGGER was wrong.
	 *
	 * That was a browser-automation quirk rather than a real phone, but the
	 * class of failure is not: the header also changes height when a web font
	 * finishes loading, when the admin bar appears, and when a container query
	 * re-lays the nav — none of which is guaranteed to fire a window `resize`.
	 * Observing the box means the measurement follows the thing being measured
	 * instead of a proxy for it.
	 */
	if ( typeof window.ResizeObserver === 'function' ) {
		var headerEl = document.querySelector( '.site-header' );
		if ( headerEl ) {
			new window.ResizeObserver( measureHeader ).observe( headerEl );
		}
	}

	// The header is 93px at 375 and 80px at 1440 — a resize crosses that.
	var resizeTimer = null;
	window.addEventListener(
		'resize',
		function () {
			if ( resizeTimer ) {
				window.clearTimeout( resizeTimer );
			}
			resizeTimer = window.setTimeout( run, 150 );
		},
		false
	);

	window.addEventListener( 'orientationchange', function () {
		window.setTimeout( run, 150 );
	}, false );
} )();
