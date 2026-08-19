/**
 * Brave Hearts — "Look Inside" media gallery.
 *
 * Progressive enhancement only. Without JavaScript every item is still in the
 * DOM, the first is visible, and the video plays through native controls.
 * This file adds stage switching, hover preview, and the enlarge lightbox.
 *
 * It contains NO commerce logic — no price, no cart, no format state — and it
 * must stay that way. book-formats.js owns all of that.
 *
 * Two behaviours worth stating because they are deliberate:
 *
 *  - HOVER PREVIEW IS NOT SELECTION. Hovering a thumb previews it on the
 *    stage; leaving restores whatever is actually selected. Only click (or
 *    keyboard) commits. This is what makes the rail feel fast without
 *    stranding the visitor somewhere they never chose.
 *
 *  - A PLAYING VIDEO IS NEVER INTERRUPTED BY A HOVER. If the visitor has
 *    started the flip-through, drifting the mouse across the thumbs must not
 *    yank it away mid-play. Committed navigation still moves, and pauses it.
 *
 * ---------------------------------------------------------------------------
 * ANALYTICS (added 2026-08-02)
 * ---------------------------------------------------------------------------
 * This file uses the theme's ONE existing event convention and adds no second
 * tracking system: a plain `window.dataLayer.push()` of an object whose first
 * three fields are the same `bhp_book` / `bhp_format` / `bhp_source` that
 * `bhpBuildEventPayload()` in `assets/js/nav.js` emits, read from the same
 * `data-bhp-*` attributes, with the same no-op guard when `dataLayer` is
 * absent or is not an array.
 *
 * NO gtag(), no analytics library, no consent check of its own, no second
 * queue. Consent stays exactly where it already lives — server-side, in
 * `BHP_Consent` / `BHP_Analytics_Config` / `BHP_GTM_Loader`, which decide
 * whether GTM is printed at all. This file only appends to the queue GTM
 * reads; if GTM is not loaded, or consent is denied, nothing leaves the page.
 *
 * WHY NOT THE DECLARATIVE `data-bhp-event` ATTRIBUTE, which nav.js's delegated
 * click handler would pick up for free: half of what is worth measuring here
 * is not a click on the element that changed (a swipe on the stage, Escape
 * closing the lightbox, a video starting). Marking the clickable half
 * declaratively and pushing the other half from here would mean two emitters
 * for one component, and the lightbox close button would then fire twice —
 * once from nav.js's handler and once from `closeLightbox()`. One emitter,
 * inside the component, is what makes "nothing double-fires" checkable.
 *
 * Two rules this instrumentation must keep, and how each is enforced:
 *   - NOTHING FIRES ON PAGE LOAD. Every push below sits inside a user-gesture
 *     handler. The initial slide is markup state, and `select()`/`show()` are
 *     never instrumented — `show()` is also what a hover preview calls, and a
 *     hover is not a choice.
 *   - NOTHING DOUBLE-FIRES. Every close route funnels through one
 *     `closeLightbox()` that early-returns when already closed; each video
 *     reports its first play only.
 */
(function () {
	'use strict';

	/*
	 * Same payload shape and same guard as nav.js's bhpBuildEventPayload():
	 * bhp_book / bhp_format / bhp_source always present, defaulting to ''.
	 * Event-specific keys are added on top, never in place of them.
	 */
	function track(root, eventName, extra) {
		if (typeof window.dataLayer === 'undefined' || !Array.isArray(window.dataLayer)) {
			return;
		}

		var payload = {
			event: eventName,
			bhp_book: root.getAttribute('data-bhp-book') || '',
			bhp_format: root.getAttribute('data-bhp-format') || '',
			bhp_source: root.getAttribute('data-bhp-source') || ''
		};

		payload.gallery_count = parseInt(root.getAttribute('data-bhp-gallery-count'), 10) || 0;

		if (extra) {
			Object.keys(extra).forEach(function (k) {
				payload[k] = extra[k];
			});
		}

		window.dataLayer.push(payload);
	}

	function initGallery(root) {
		var stage = root.querySelector('[data-bhp-gallery-stage]');
		if (!stage) {
			return;
		}

		var slides = Array.prototype.slice.call(root.querySelectorAll('[data-bhp-slide]'));
		var thumbs = Array.prototype.slice.call(root.querySelectorAll('[data-bhp-gallery-thumb]'));
		var prev = root.querySelector('[data-bhp-gallery-prev]');
		var next = root.querySelector('[data-bhp-gallery-next]');
		var counter = root.querySelector('[data-bhp-gallery-current]');
		var status = root.querySelector('[data-bhp-gallery-status]');

		if (slides.length < 1) {
			return;
		}

		var selected = 0;  // committed
		var shown = 0;     // currently on the stage (may be a hover preview)

		function videoIn(index) {
			return slides[index] ? slides[index].querySelector('[data-bhp-gallery-video]') : null;
		}

		function isPlaying(v) {
			return v && !v.paused && !v.ended && v.currentTime > 0;
		}

		/*
		 * Describe one item for an analytics payload. Index is 1-based because
		 * that is what the visitor sees in the "3 / 9" counter; type and group
		 * come straight off the slide, so they cannot drift from what rendered.
		 */
		function itemContext(index) {
			var slide = slides[index];
			return {
				item_index: index + 1,
				item_type: slide ? (slide.getAttribute('data-bhp-slide-type') || '') : '',
				item_group: slide ? (slide.getAttribute('data-bhp-slide-group') || '') : ''
			};
		}

		function trackItem(eventName, index, extra) {
			var data = itemContext(index);
			if (extra) {
				Object.keys(extra).forEach(function (k) { data[k] = extra[k]; });
			}
			track(root, eventName, data);
		}

		var allVideos = Array.prototype.slice.call(
			root.querySelectorAll('[data-bhp-gallery-video]')
		);

		/*
		 * Mount a lazily-deferred video: move its poster and source URLs out of
		 * data-* into the real attributes, then load() so the poster paints.
		 * Idempotent — a video is only ever mounted once.
		 */
		function mountVideo(video) {
			if (!video || video.getAttribute('data-bhp-mounted') === '1') {
				return;
			}
			var poster = video.getAttribute('data-bhp-poster');
			if (poster) {
				video.setAttribute('poster', poster);
			}
			Array.prototype.forEach.call(video.querySelectorAll('source[data-bhp-src]'), function (s) {
				s.setAttribute('src', s.getAttribute('data-bhp-src'));
				s.removeAttribute('data-bhp-src');
			});
			video.setAttribute('data-bhp-mounted', '1');
			video.load();
		}

		/* Exactly one video may ever be playing. */
		function pauseAllExcept(video) {
			allVideos.forEach(function (v) {
				if (v !== video && !v.paused) {
					v.pause();
				}
			});
		}

		/* Show an item on the stage. Does NOT change what is selected. */
		function show(index) {
			if (index === shown || !slides[index]) {
				return;
			}

			// Never leave a video playing in a hidden slide.
			var leaving = videoIn(shown);
			if (leaving && !leaving.paused) {
				leaving.pause();
			}

			// Mount the arriving video on first reveal, and make sure nothing
			// else is still playing behind it.
			var arriving = videoIn(index);
			if (arriving) {
				mountVideo(arriving);
			}
			pauseAllExcept(arriving);

			slides.forEach(function (s, i) {
				var active = i === index;
				s.classList.toggle('is-active', active);
				if (active) {
					s.removeAttribute('hidden');
				} else {
					s.setAttribute('hidden', '');
				}
			});

			shown = index;

			if (counter) {
				counter.textContent = String(index + 1);
			}
		}

		/* Commit a selection. */
		function select(index, opts) {
			opts = opts || {};

			if (index < 0) {
				index = slides.length - 1;
			}
			if (index >= slides.length) {
				index = 0;
			}

			selected = index;
			show(index);

			thumbs.forEach(function (t, i) {
				t.setAttribute('aria-current', i === index ? 'true' : 'false');
			});

			if (status) {
				// Announce the item's own name. A gallery can hold more than
				// one video, so a generic "Flip-through video" would read
				// identically for two different clips.
				var video = videoIn(index);
				var name = video
					? (video.getAttribute('aria-label') || 'Video')
					: 'Image ' + (index + 1);
				status.textContent = name + ' (' + (index + 1) + ' of ' + slides.length + ')';
			}

			if (opts.focusThumb && thumbs[index]) {
				thumbs[index].focus();
			}
		}

		/* ---- arrows ---- */
		/*
		 * Instrumented HERE rather than inside select(), deliberately: select()
		 * is also reached from the thumbnail rail and from a swipe, and each of
		 * those is a different visitor action that deserves its own name.
		 */
		if (prev) {
			prev.addEventListener('click', function () {
				select(selected - 1);
				trackItem('look_inside_advance', selected, { direction: 'prev', interaction: 'arrow' });
			});
		}
		if (next) {
			next.addEventListener('click', function () {
				select(selected + 1);
				trackItem('look_inside_advance', selected, { direction: 'next', interaction: 'arrow' });
			});
		}

		/*
		 * ---- touch swipe ----
		 * Horizontal drags across the stage move slides. Deliberately passive
		 * and threshold-gated so it never fights vertical page scrolling, and
		 * it ignores drags that start on the video element so the native
		 * scrubber and controls keep working.
		 */
		(function () {
			var x0 = null, y0 = null, onVideo = false;
			var THRESHOLD = 45;

			stage.addEventListener('touchstart', function (e) {
				if (e.touches.length !== 1) { x0 = null; return; }
				onVideo = !!(e.target.closest && e.target.closest('[data-bhp-gallery-video]'));
				x0 = e.touches[0].clientX;
				y0 = e.touches[0].clientY;
			}, { passive: true });

			stage.addEventListener('touchend', function (e) {
				if (x0 === null || onVideo || slides.length < 2) { return; }
				var t = e.changedTouches[0];
				var dx = t.clientX - x0;
				var dy = t.clientY - y0;
				// Only a clearly horizontal gesture counts.
				if (Math.abs(dx) > THRESHOLD && Math.abs(dx) > Math.abs(dy) * 1.5) {
					var forward = dx < 0;
					select(forward ? selected + 1 : selected - 1);
					trackItem('look_inside_advance', selected, {
						direction: forward ? 'next' : 'prev',
						interaction: 'swipe'
					});
				}
				x0 = null;
			}, { passive: true });
		})();

		/* ---- thumbs: hover previews, click commits ---- */
		thumbs.forEach(function (btn, i) {
			btn.addEventListener('mouseenter', function () {
				// Do not interrupt a video the visitor is actually watching.
				if (isPlaying(videoIn(shown))) {
					return;
				}
				show(i);
			});

			btn.addEventListener('mouseleave', function () {
				if (isPlaying(videoIn(shown))) {
					return;
				}
				show(selected); // restore the committed item
			});

			/*
			 * HOVER IS NOT INSTRUMENTED, by design: the mouseenter/mouseleave
			 * handlers above are a preview, not a choice, and a visitor sliding
			 * the pointer across a nine-tile rail would otherwise emit nine
			 * events for one intention. Only a commit is reported.
			 */
			btn.addEventListener('click', function () {
				// Re-picking the item already committed is not a selection.
				// Behaviour is unchanged either way — select() is still called.
				var changed = (i !== selected);
				select(i);
				if (changed) {
					trackItem('look_inside_thumb_select', i, { interaction: 'click' });
				}
			});

			// Roving arrow-key navigation, matching the format selector's pattern.
			btn.addEventListener('keydown', function (e) {
				var target = null;

				if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
					target = (i + 1) % thumbs.length;
				} else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
					target = (i - 1 + thumbs.length) % thumbs.length;
				} else if (e.key === 'Home') {
					target = 0;
				} else if (e.key === 'End') {
					target = thumbs.length - 1;
				}

				if (target !== null) {
					e.preventDefault();
					var moved = (target !== selected);
					select(target, { focusThumb: true });
					if (moved) {
						trackItem('look_inside_thumb_select', target, { interaction: 'keyboard' });
					}
				}
			});
		});

		/*
		 * Belt and braces: whatever starts playing wins, everything else stops.
		 * Slide switching already handles this, but a page can hold more than
		 * one gallery and a visitor can reach a <video> by keyboard alone.
		 */
		allVideos.forEach(function (v) {
			v.addEventListener('play', function () {
				pauseAllExcept(v);

				/*
				 * FIRST PLAY ONLY, per video, per page view. `play` also fires
				 * on every resume after a pause or a seek, and "the visitor
				 * started the flip-through" is one fact, not one per scrub.
				 * The flag lives on the element, so it survives slide switching
				 * and is trivially inspectable in the DOM.
				 */
				if (v.getAttribute('data-bhp-play-tracked') === '1') {
					return;
				}
				v.setAttribute('data-bhp-play-tracked', '1');

				var index = slides.indexOf(v.closest('[data-bhp-slide]'));
				if (index < 0) {
					return;
				}
				trackItem('look_inside_video_play', index, {
					item_label: v.getAttribute('aria-label') || ''
				});
			});
		});

		/* Leaving the rail entirely restores the committed item. */
		var rail = root.querySelector('[data-bhp-gallery-thumbs]');
		if (rail) {
			rail.addEventListener('mouseleave', function () {
				if (isPlaying(videoIn(shown))) {
					return;
				}
				show(selected);
			});
		}

		/* ---- lightbox ---- */
		var lightbox = root.querySelector('[data-bhp-gallery-lightbox]');
		var lightboxImg = root.querySelector('[data-bhp-gallery-lightbox-img]');
		var lastFocus = null;
		// Which slide the open lightbox belongs to. Read from the button that
		// opened it, not from `selected`, because a hover preview can be on the
		// stage while a different item is the committed one.
		var lightboxIndex = 0;

		/*
		 * ⭐ 1.19.266 (CYCLE165-LD-ITERATE-2-AESTHETICS-TOKENS, audit §8a item 9)
		 *    — `openLightbox` now takes the source image's intrinsic size and
		 *    stamps it on the lightbox <img> together with the src.
		 *
		 * WHY. The lightbox <img> is rendered by `look-inside.php` with NO src
		 * and NO dimensions, on 14 pages. The audit counted it among the 47
		 * images missing width/height. Two honest observations, both measured:
		 *
		 *   1. At rest it contributes ZERO CLS. It is inside a `hidden` panel
		 *      and the probe read it as 0x0 / displayed 0x0. Adding invented
		 *      width/height to the markup would have satisfied a counter
		 *      without improving a single page.
		 *   2. The shift that CAN happen is at OPEN, when a src arrives on an
		 *      element with no aspect ratio and the panel reflows around the
		 *      decoded image. That is the real defect, and this is where it
		 *      lives — so it is fixed here rather than papered over in markup.
		 *
		 * The numbers come from the thumbnail that was clicked
		 * (`naturalWidth`/`naturalHeight`, or its own width/height attributes
		 * if it has not decoded yet), so they are the real ratio of the real
		 * asset. If neither is available the attributes are REMOVED rather than
		 * guessed — a wrong ratio reserves the wrong box, which is worse than
		 * reserving none.
		 */
		function openLightbox(src, alt, natW, natH) {
			if (!lightbox || !lightboxImg) {
				return;
			}
			lastFocus = document.activeElement;
			if (natW && natH) {
				lightboxImg.setAttribute('width', natW);
				lightboxImg.setAttribute('height', natH);
			} else {
				lightboxImg.removeAttribute('width');
				lightboxImg.removeAttribute('height');
			}
			lightboxImg.src = src;
			lightboxImg.alt = alt || '';
			lightbox.removeAttribute('hidden');
			document.body.classList.add('bhp-gallery-lightbox-open');

			var closeBtn = lightbox.querySelector('.bhp-gallery__lightbox-close');
			if (closeBtn) {
				closeBtn.focus();
			}
		}

		/*
		 * THE SINGLE CLOSE FUNNEL. Button, backdrop and Escape all arrive here,
		 * and the early return above makes a second call on an already-closed
		 * lightbox a no-op — which is what guarantees one close event per close,
		 * whichever route the visitor took.
		 */
		function closeLightbox(method) {
			if (!lightbox || lightbox.hasAttribute('hidden')) {
				return;
			}
			trackItem('look_inside_lightbox_close', lightboxIndex, {
				method: method || 'unknown'
			});
			lightbox.setAttribute('hidden', '');
			document.body.classList.remove('bhp-gallery-lightbox-open');
			// Release the decoded full-size image. removeAttribute, NOT src='',
			// because an empty src is a broken image and can trigger a request
			// to the page URL itself.
			lightboxImg.removeAttribute('src');
			// 1.19.266: the reserved box goes with the src it was reserved for.
			lightboxImg.removeAttribute('width');
			lightboxImg.removeAttribute('height');
			lightboxImg.alt = '';
			if (lastFocus && lastFocus.focus) {
				lastFocus.focus();
			}
		}

		root.querySelectorAll('[data-bhp-gallery-inspect]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var slide = btn.closest('[data-bhp-slide]');
				var index = slides.indexOf(slide);
				lightboxIndex = index < 0 ? shown : index;
				/*
				 * 1.19.266: the ratio comes from the thumbnail this button sits
				 * on. `naturalWidth` is the decoded truth and is preferred; the
				 * width/height ATTRIBUTES are the fallback for an image that has
				 * not decoded yet. Both may be absent, in which case
				 * `openLightbox` reserves nothing rather than reserving wrong.
				 *
				 * ⚠ The full-size asset behind `data-bhp-full` and the thumbnail
				 *   are the same photograph at different scales, so the RATIO is
				 *   the same even though the pixel counts are not — and a ratio
				 *   is the only thing width/height contribute here, because CSS
				 *   sizes this element.
				 */
				var thumb = slide ? slide.querySelector('img') : null;
				var natW = thumb ? (thumb.naturalWidth || parseInt(thumb.getAttribute('width'), 10) || 0) : 0;
				var natH = thumb ? (thumb.naturalHeight || parseInt(thumb.getAttribute('height'), 10) || 0) : 0;
				openLightbox(btn.getAttribute('data-bhp-full'), btn.getAttribute('data-bhp-alt'), natW, natH);
				trackItem('look_inside_lightbox_open', lightboxIndex, { interaction: 'click' });
			});
		});

		root.querySelectorAll('[data-bhp-gallery-lightbox-close]').forEach(function (el) {
			// The backdrop and the × button share this attribute; the class is
			// what tells them apart, so `method` reports the real route taken.
			var method = el.classList.contains('bhp-gallery__lightbox-backdrop')
				? 'backdrop'
				: 'button';
			el.addEventListener('click', function () { closeLightbox(method); });
		});

		document.addEventListener('keydown', function (e) {
			if (!lightbox || lightbox.hasAttribute('hidden')) {
				return;
			}
			if (e.key === 'Escape') {
				closeLightbox('escape');
				return;
			}
			// Minimal focus trap: the panel has exactly one focusable control,
			// so keep Tab on it rather than letting focus escape behind the
			// modal backdrop.
			if (e.key === 'Tab') {
				e.preventDefault();
				var closeBtn = lightbox.querySelector('.bhp-gallery__lightbox-close');
				if (closeBtn) {
					closeBtn.focus();
				}
			}
		});

		/* ================================================================
		 * 1.19.241 (CYCLE164-LD-STOREFRONT-BATCH) — THE FLIP-THROUGH CUE.
		 * ================================================================
		 *
		 * A visible control that selects the video slide and starts it. The
		 * button is rendered by bhp_book_flip_through_cue_html() INSIDE this
		 * same <section>, so it is found by scope rather than by an id
		 * reference that could go stale.
		 *
		 * WHY THIS IS WIRED HERE, INSIDE initGallery, RATHER THAN AS A
		 * DOCUMENT-LEVEL DELEGATE: select(), show(), mountVideo() and
		 * pauseAllExcept() are all closure-scoped, and the whole point of the
		 * cue is to reuse them rather than to grow a second, subtly different
		 * way of switching slides. A delegate outside this closure would have
		 * had to re-implement every one of them.
		 *
		 * NO AUTOPLAY. play() is called synchronously inside a click handler,
		 * which is a genuine user gesture; nothing here runs on load, on
		 * scroll or on a timer.
		 */
		var cue = root.querySelector('[data-bhp-gallery-cue]');
		if (cue) {
			cue.addEventListener('click', function () {
				var index = parseInt(cue.getAttribute('data-bhp-gallery-cue'), 10);
				if (isNaN(index) || !slides[index]) {
					return;
				}

				select(index);
				trackItem('look_inside_cue_click', index, { interaction: 'cue' });

				/*
				 * Bring the stage into view before playing. On a phone the
				 * stage sits well above this button, and starting a clip the
				 * visitor cannot see is worse than not starting it.
				 * `scrollIntoView` is a no-op cost when it is already visible.
				 */
				var reduce = window.matchMedia
					&& window.matchMedia('(prefers-reduced-motion: reduce)').matches;
				if (stage.scrollIntoView) {
					stage.scrollIntoView({
						behavior: reduce ? 'auto' : 'smooth',
						block: 'nearest'
					});
				}

				var video = videoIn(index);
				if (!video) {
					return;
				}
				mountVideo(video);

				/*
				 * Focus moves to the video so a keyboard visitor lands on the
				 * native controls they just asked for, instead of being left
				 * on a button whose job is done.
				 */
				if (video.focus) {
					video.focus({ preventScroll: true });
				}

				/*
				 * play() returns a promise that REJECTS on some browsers (an
				 * unsatisfied autoplay policy, a codec the device declines).
				 * Swallowing it deliberately: the poster and native controls
				 * are still on screen, so the visitor can start it themselves,
				 * and an unhandled rejection in the console is noise that
				 * looks like a defect during QA.
				 */
				var started = video.play();
				if (started && typeof started.catch === 'function') {
					started.catch(function () {});
				}
			});
		}
	}

	function init() {
		var galleries = document.querySelectorAll('[data-bhp-gallery]');
		Array.prototype.forEach.call(galleries, initGallery);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
