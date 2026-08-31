/**
 * Brave Hearts — photo carousel.
 *
 * ⛔ NO THIRD-PARTY LIBRARY, AND NO NEW ENGINE EITHER. This reuses the theme's
 *    OWN carousel idiom — the one `.bhp-gallery__thumbs` in `book-media.css`
 *    already uses: a flex rail in its own `overflow-x` scroller with
 *    `scroll-snap`. `book-media.js` could not be reused as-is because every one
 *    of its slides is a WordPress ATTACHMENT (`wp_get_attachment_image($id)`)
 *    and these photographs are deliberately THEME ASSETS with no attachment ID
 *    on either environment — 1.19.329 §7's reason, unchanged. So the mechanism
 *    is reused; the attachment coupling is not.
 *
 * ---------------------------------------------------------------------------
 * ⭐ THE SCROLLER IS THE CAROUSEL. THE JAVASCRIPT IS ONLY THE CONTROLS.
 * ---------------------------------------------------------------------------
 *
 * The rail is a real horizontal scroll container with `scroll-snap-type: x
 * mandatory`. That single decision buys four of the brief's requirements from
 * the platform instead of from code:
 *
 *   - SWIPE ON TOUCH is the browser's own scroll gesture. There is no
 *     `touchstart` handler, no threshold constant, no velocity guess, and no
 *     conflict with the page's vertical scroll. It has momentum and rubber-band
 *     because it IS scrolling.
 *   - KEYBOARD works before this file loads: the rail is focusable and the
 *     arrow keys scroll it natively. The explicit Left/Right handler below only
 *     upgrades that to whole-slide steps.
 *   - LAZY LOADING works because an off-screen slide is genuinely off-screen.
 *     `loading="lazy"` on slides 2..n means one photograph is fetched on load,
 *     not six. Nothing here mounts or unmounts a `src`.
 *   - WITHOUT JAVASCRIPT THE CAROUSEL STILL WORKS. Every slide is in the DOM,
 *     visible, and reachable by swipe, trackpad or the rail's own scrollbar.
 *     No slide is `hidden`, so nothing is stranded when this file fails to
 *     load. The arrows and dots are printed `hidden` by the template and are
 *     UNHIDDEN here — a control that cannot work is never shown.
 *
 * ⛔ STATE IS READ FROM THE SCROLLER, NEVER STORED. There is no `current`
 *    variable that a swipe could desynchronise from. `activeIndex()` measures.
 *    That is why a swipe, an arrow, a dot, a trackpad flick and a native
 *    scrollbar drag all produce identical dot/counter state.
 *
 * ⛔ NO ANALYTICS. This component emits no event and touches no `dataLayer`.
 *    `book-media.js` instruments its gallery because that gallery sits on the
 *    purchase path; this one is a photograph rail on a booking page, and adding
 *    a second uninstrumented-then-instrumented emitter is how two components
 *    start double-firing. If measurement is ever wanted here it is a separate,
 *    deliberate change.
 */
(function () {
	'use strict';

	/**
	 * Index of the slide currently closest to the rail's left edge.
	 *
	 * Measured, not remembered. `Math.round` rather than `Math.floor` so a rail
	 * parked mid-snap (a fling that has not settled, or a browser without
	 * smooth-scroll) still reports the slide a person would say they are on.
	 */
	function activeIndex(rail, slides) {
		if (!slides.length) {
			return 0;
		}
		var step = slides[0].getBoundingClientRect().width;
		if (!step) {
			return 0;
		}
		var i = Math.round(rail.scrollLeft / step);
		return Math.max(0, Math.min(slides.length - 1, i));
	}

	function init(root) {
		var rail = root.querySelector('[data-bhp-pc-rail]');
		if (!rail) {
			return;
		}
		var slides = Array.prototype.slice.call(root.querySelectorAll('[data-bhp-pc-slide]'));
		if (slides.length < 2) {
			return; // A one-photograph carousel is a photograph. The template already hides the controls.
		}

		var prev = root.querySelector('[data-bhp-pc-prev]');
		var next = root.querySelector('[data-bhp-pc-next]');
		var dots = Array.prototype.slice.call(root.querySelectorAll('[data-bhp-pc-dot]'));
		var current = root.querySelector('[data-bhp-pc-current]');
		var status = root.querySelector('[data-bhp-pc-status]');

		/*
		 * The controls exist in the markup but are printed `hidden`, so a
		 * visitor whose JavaScript failed never sees an arrow that does
		 * nothing. Revealing them is the first thing this file does, and it is
		 * the only thing that makes them appear.
		 */
		[prev, next].forEach(function (el) {
			if (el) {
				el.hidden = false;
			}
		});
		var dotStrip = root.querySelector('[data-bhp-pc-dots]');
		if (dotStrip) {
			dotStrip.hidden = false;
		}
		root.setAttribute('data-bhp-pc-ready', '1');

		/*
		 * Move to a slide, WITHOUT TRUSTING THE ANIMATION TO FINISH.
		 *
		 * ⚠⚠ THIS SHAPE CAME FROM QA, AND THE HONEST VERSION OF WHY IS WORTH
		 *    MORE THAN THE DRAMATIC ONE. The first version called
		 *    `rail.scrollTo({ left: x, behavior: 'auto' })` and left the
		 *    animation to the stylesheet's `scroll-behavior: smooth`. In QA on
		 *    staging 1.19.330 that scroll never completed — `scrollLeft` was
		 *    still 0 after 2,500ms — so every arrow, dot and arrow key appeared
		 *    to do nothing.
		 *
		 *    ⭐ THE ROOT CAUSE WAS THE QA ENVIRONMENT, NOT THE PAGE:
		 *       `document.visibilityState` was `'hidden'` and the browser had
		 *       suspended its animation frames, so no smooth scroll of any kind
		 *       could finish. It is NOT established that a visible browser has
		 *       this problem, and this comment does not claim it does.
		 *
		 *    ⛔ BUT THE FIRST VERSION WAS ALSO GENUINELY WRONG, INDEPENDENT OF
		 *       THAT: it asked the computed `scroll-behavior` whether to animate
		 *       and then passed `'auto'`, which per CSSOM View means "use the
		 *       CSS value" — so both branches did the same thing and the
		 *       reduced-motion intent was never expressed at all.
		 *
		 * ⭐ SO THE FIX DOES TWO THINGS. It states the reduced-motion decision
		 *    explicitly through `matchMedia`, and it GUARANTEES THE OUTCOME
		 *    WITHOUT GIVING UP THE ANIMATION: ask for smooth, then verify, and
		 *    if the rail has not arrived shortly after, put it there instantly.
		 *    A visitor whose browser animates sees the glide; a visitor whose
		 *    browser does not — a suspended tab, an interrupted animation, a
		 *    snap that fought the scroll — still lands on the photograph they
		 *    asked for. It is a guard, not a workaround for a proven bug.
		 */
		var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		var settleTimer  = null;

		/* The everywhere-safe instant jump: `behavior: 'instant'` is not old
		   enough to rely on, and `'auto'` defers to the stylesheet's `smooth`.
		   Turning the CSS off for one assignment cannot be misread by anything. */
		function jump(left) {
			var was = rail.style.scrollBehavior;
			rail.style.scrollBehavior = 'auto';
			rail.scrollLeft = left;
			rail.style.scrollBehavior = was;
		}

		function goTo(i) {
			var clamped = Math.max(0, Math.min(slides.length - 1, i));
			var left    = slides[0].getBoundingClientRect().width * clamped;

			if (reduceMotion) {
				jump(left);
				sync();
				return;
			}

			rail.scrollTo({ left: left, behavior: 'smooth' });

			window.clearTimeout(settleTimer);
			settleTimer = window.setTimeout(function () {
				if (Math.abs(rail.scrollLeft - left) > 2) {
					jump(left);
				}
				sync();
			}, 450);
		}

		function sync() {
			var i = activeIndex(rail, slides);

			dots.forEach(function (d, di) {
				d.setAttribute('aria-current', di === i ? 'true' : 'false');
			});
			if (current) {
				current.textContent = String(i + 1);
			}
			/*
			 * Arrows are DISABLED, not hidden, at the ends. A control that
			 * vanishes moves the two beside it; a disabled one keeps the row
			 * still and still announces itself.
			 */
			if (prev) {
				prev.disabled = (0 === i);
			}
			if (next) {
				next.disabled = (i === slides.length - 1);
			}
			/*
			 * One polite live region, and it is deliberately NOT updated on
			 * every scroll event — the text only changes when the INDEX does,
			 * so a screen reader hears one announcement per photograph rather
			 * than one per pixel of a swipe.
			 */
			if (status && status.getAttribute('data-bhp-pc-at') !== String(i)) {
				status.setAttribute('data-bhp-pc-at', String(i));
				status.textContent = 'Photograph ' + (i + 1) + ' of ' + slides.length;
			}
		}

		/*
		 * The swipe -> dot/counter/live-region hop, throttled on a TIMER rather
		 * than on `requestAnimationFrame`.
		 *
		 * ⭐ A TIMER, BECAUSE THIS HANDLER DOES NOT ANIMATE ANYTHING. It reads
		 *    `scrollLeft` and writes one attribute and two short strings. rAF is
		 *    the right tool for work that must land on a frame; a timer also
		 *    reconciles the state when frames are throttled, so the dots a
		 *    visitor comes back to are correct either way. 100ms, not 16ms: ten
		 *    updates a second is far more than anyone can read off a dot strip,
		 *    and it is a tenth of the work.
		 *
		 * ⚠⚠ AND A STATED LIMIT OF THIS LANE'S QA, RECORDED HERE BECAUSE THE
		 *    NEXT PERSON WILL TRY THE SAME THING. This path — swipe, then watch
		 *    the dot follow — COULD NOT BE OBSERVED on staging 1.19.330. The QA
		 *    browser pane was hidden (`document.visibilityState === 'hidden'`)
		 *    and in that state Chrome dispatches NO `scroll` EVENT AT ALL:
		 *    measured directly with a probe listener, a programmatic scroll that
		 *    demonstrably moved `scrollLeft` from 0 to 686 produced zero scroll
		 *    events over 1,500ms. ⛔ So the choice of throttle is NOT what makes
		 *    this unobservable, and swapping it back to rAF would not make it
		 *    observable. What IS verified live is that the rail moves on a swipe
		 *    and lands snapped, and that `sync()` is correct — because the
		 *    arrow, dot, Home, End and arrow-key routes all reach the same
		 *    function and all were observed updating the dot, the counter and
		 *    the live region. The one unverified hop is `scroll event ->
		 *    sync()`, and it needs a visible browser.
		 */
		var ticking = false;
		rail.addEventListener('scroll', function () {
			if (ticking) {
				return;
			}
			ticking = true;
			window.setTimeout(function () {
				ticking = false;
				sync();
			}, 100);
		}, { passive: true });

		if (prev) {
			prev.addEventListener('click', function () {
				goTo(activeIndex(rail, slides) - 1);
			});
		}
		if (next) {
			next.addEventListener('click', function () {
				goTo(activeIndex(rail, slides) + 1);
			});
		}
		dots.forEach(function (d, di) {
			d.addEventListener('click', function () {
				goTo(di);
			});
		});

		/*
		 * Whole-slide steps for the arrow keys. The rail already scrolls on
		 * these natively, by a browser-chosen pixel amount that lands between
		 * snap points on a wide slide; `preventDefault()` replaces that with a
		 * step to the next photograph. Home/End are the conventional pair.
		 *
		 * Only keys this component handles are prevented — Tab, Escape and
		 * everything else pass straight through.
		 */
		rail.addEventListener('keydown', function (e) {
			var i = activeIndex(rail, slides);
			if ('ArrowRight' === e.key) {
				e.preventDefault();
				goTo(i + 1);
			} else if ('ArrowLeft' === e.key) {
				e.preventDefault();
				goTo(i - 1);
			} else if ('Home' === e.key) {
				e.preventDefault();
				goTo(0);
			} else if ('End' === e.key) {
				e.preventDefault();
				goTo(slides.length - 1);
			}
		});

		/*
		 * A resize changes the slide width, so the pixel offset that meant
		 * "slide 3" no longer does. Re-anchoring on the measured index keeps
		 * the same photograph on screen across an orientation change.
		 */
		var resizeTimer = null;
		window.addEventListener('resize', function () {
			var at = activeIndex(rail, slides);
			window.clearTimeout(resizeTimer);
			resizeTimer = window.setTimeout(function () {
				rail.scrollLeft = slides[0].getBoundingClientRect().width * at;
				sync();
			}, 120);
		});

		sync();
	}

	function boot() {
		Array.prototype.forEach.call(document.querySelectorAll('[data-bhp-photo-carousel]'), init);
	}

	if ('loading' === document.readyState) {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
}());
