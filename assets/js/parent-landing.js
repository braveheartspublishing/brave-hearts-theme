/**
 * Parent - Reluctant Reader Adventure Kit landing page.
 *
 * Owns only this page's own UI: sticky mini-CTA bar visibility, the
 * Complete Collection format toggle (Paperback/Hardcover), FAQ analytics,
 * and "get the free chapter" scroll-to-panel behavior. Signup form
 * submission itself is the site's existing signup-form.php handler
 * (unrelated JS, untouched here) -- this file never intercepts or
 * duplicates that submission.
 */
(function () {
    'use strict';

    function qs(sel, ctx) { return (ctx || document).querySelector(sel); }
    function qsa(sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); }

    function pushEvent(eventName, extra) {
        if (typeof window.dataLayer === 'undefined' || !Array.isArray(window.dataLayer)) {
            return;
        }
        window.dataLayer.push(Object.assign({
            event: eventName,
            page_path: window.location.pathname || ''
        }, extra || {}));
    }

    /**
     * Sticky bar shows past 640px of scroll, but suppresses itself while
     * the signup form, the collection pricing card, or the final CTA is
     * already on screen -- those sections already show the same "get the
     * free chapter" / "explore the collection" actions directly, so
     * stacking the sticky bar on top of them is a redundant, cramped
     * CTA collision rather than a helpful nudge.
     */
    function initStickyBar(root) {
        var bar = qs('[data-parent-stickybar]', root);
        if (!bar) {
            return;
        }
        var suppressTargets = [
            qs('#free', root),
            qs('[data-parent-pricing-card]', root),
            qs('.parent-landing-final', root),
            qs('.site-footer') || qs('footer')
        ].filter(Boolean);

        var scrolled = false;
        var suppressed = false;

        function render() {
            bar.classList.toggle('is-visible', scrolled && !suppressed);
            bar.classList.toggle('is-suppressed', suppressed);
        }

        window.addEventListener('scroll', function () {
            var y = window.scrollY || document.documentElement.scrollTop || 0;
            var visible = y > 640;
            if (visible !== scrolled) {
                scrolled = visible;
                render();
            }
        }, { passive: true });

        if (suppressTargets.length && typeof IntersectionObserver === 'function') {
            var activeCount = 0;
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    activeCount += entry.isIntersecting ? 1 : -1;
                });
                activeCount = Math.max(0, activeCount);
                var nextSuppressed = activeCount > 0;
                if (nextSuppressed !== suppressed) {
                    suppressed = nextSuppressed;
                    render();
                }
            }, { threshold: 0.15 });
            suppressTargets.forEach(function (el) { observer.observe(el); });
        }
    }

    function setFormat(root, format) {
        qsa('[data-parent-format-btn]', root).forEach(function (btn) {
            var isMatch = btn.getAttribute('data-parent-format-btn') === format;
            btn.classList.toggle('is-selected', isMatch);
            btn.setAttribute('aria-checked', isMatch ? 'true' : 'false');
        });
        qsa('[data-parent-format-panel]', root).forEach(function (panel) {
            var isMatch = panel.getAttribute('data-parent-format-panel') === format;
            if (isMatch) {
                panel.removeAttribute('hidden');
            } else {
                panel.setAttribute('hidden', '');
            }
        });
        /*
         * 2026-08-05 -- keep the add-the-set-and-checkout CTAs that live OUTSIDE
         * the per-format panels (sticky footer bar, final CTA) on the format the
         * customer actually chose. Without this a customer who switched to
         * paperback would land on checkout holding three hardcovers. Mirrors
         * audience-landing.js exactly; the two files stay independent by design
         * so neither page's behaviour can regress the other.
         */
        qsa('[data-bhp-collection-action]', root).forEach(function (input) {
            input.value = 'complete_' + format + '_smart';
        });
    }

    function initFormatToggle(root) {
        var buttons = qsa('[data-parent-format-btn]', root);
        if (!buttons.length) {
            return;
        }
        buttons.forEach(function (btn, index) {
            btn.addEventListener('click', function () {
                var format = btn.getAttribute('data-parent-format-btn');
                setFormat(root, format);
                pushEvent('parent_landing_format_selected', { format: format });
            });
            btn.addEventListener('keydown', function (e) {
                if (['ArrowRight', 'ArrowDown', 'ArrowLeft', 'ArrowUp'].indexOf(e.key) === -1) {
                    return;
                }
                e.preventDefault();
                var dir = (e.key === 'ArrowRight' || e.key === 'ArrowDown') ? 1 : -1;
                var next = buttons[(index + dir + buttons.length) % buttons.length];
                next.focus();
                next.click();
            });
        });
    }

    function initFaqAnalytics(root) {
        qsa('.parent-landing-faq__item', root).forEach(function (item) {
            item.addEventListener('toggle', function () {
                if (item.open) {
                    pushEvent('parent_landing_faq_open', { question: item.getAttribute('data-question') || '' });
                }
            });
        });
    }

    function initFreeChapterCtas(root) {
        qsa('[data-parent-free-cta]', root).forEach(function (link) {
            link.addEventListener('click', function () {
                pushEvent('parent_landing_free_cta_click', {});
            });
        });
    }

    function prefersReducedMotion() {
        return typeof window.matchMedia === 'function' &&
            window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    /**
     * Section fade-ins. Content is visible by default -- this only ADDS a
     * hidden starting state to sections that are below the fold right now,
     * so scroll-in-progress content settles into place the first time it's
     * ~15% into the viewport. Sections already on screen (or above it) are
     * never hidden at all: a static capture tool that never scrolls, or a
     * print/PDF render, must see the finished page immediately, not an
     * empty one waiting for a scroll event that will never happen.
     *
     * A hard 2.5s timeout is an unconditional backstop that reveals
     * everything no matter what -- IntersectionObserver missing, a JS
     * exception anywhere above, an observer that never fires, capture/print
     * tooling. JavaScript must never be a hard requirement for core content
     * to become visible; this function only ever adds an optional
     * enhancement on top of already-visible content.
     */
    function initSectionFadeIns(root) {
        if (prefersReducedMotion()) {
            return;
        }
        var sections = qsa('.parent-landing__section', root).filter(function (el) {
            return !el.classList.contains('parent-landing-hero');
        });
        if (!sections.length) {
            return;
        }

        // The revealed state is never left depending on the CSS transition
        // actually completing: some engines can add both the "hidden" and
        // "revealed" classes within the same paint tick and leave opacity
        // stuck at an intermediate value with no further updates (confirmed
        // reproducible in this codebase's own staging QA). So once a
        // section is revealed, both classes are removed a moment later,
        // returning it to its plain, class-free default styling -- which
        // is unconditionally opacity:1 with no transition involved at all.
        function settle(el) {
            el.classList.add('pl-in-view');
            setTimeout(function () {
                el.classList.remove('pl-fade-init', 'pl-in-view');
            }, 700);
        }

        var revealed = false;
        function revealAll() {
            if (revealed) {
                return;
            }
            revealed = true;
            sections.forEach(settle);
        }
        setTimeout(revealAll, 2500);

        if (typeof IntersectionObserver !== 'function') {
            revealAll();
            return;
        }

        try {
            var viewportHeight = window.innerHeight || document.documentElement.clientHeight;
            var enrolled = [];
            sections.forEach(function (el) {
                var rect = el.getBoundingClientRect();
                if (rect.top < viewportHeight) {
                    return; // already visible on load -- never hide it
                }
                el.classList.add('pl-fade-init');
                enrolled.push(el);
            });
            if (!enrolled.length) {
                return;
            }
            var observer = new IntersectionObserver(function (entries, obs) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        settle(entry.target);
                        obs.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
            enrolled.forEach(function (el) { observer.observe(el); });
        } catch (e) {
            revealAll();
        }
    }

    /**
     * Smooth FAQ open/close using the Web Animations API rather than
     * native <details> instant show/hide. Falls back to native instant
     * behavior under prefers-reduced-motion or if WAAPI is unavailable --
     * the FAQ stays fully functional either way, this only adds motion.
     */
    function initSmoothFaq(root) {
        if (prefersReducedMotion() || typeof Element === 'undefined' || !Element.prototype.animate) {
            return;
        }
        qsa('.parent-landing-faq__item', root).forEach(function (item) {
            var summary = item.querySelector('summary');
            var answer = item.querySelector('p');
            if (!summary || !answer) {
                return;
            }
            var animating = false;
            summary.addEventListener('click', function (e) {
                if (animating) {
                    e.preventDefault();
                    return;
                }
                e.preventDefault();
                animating = true;
                if (!item.open) {
                    item.open = true;
                    var target = answer.getBoundingClientRect().height;
                    answer.animate(
                        [{ height: '0px', opacity: 0 }, { height: target + 'px', opacity: 1 }],
                        { duration: 220, easing: 'cubic-bezier(.16,1,.3,1)' }
                    ).onfinish = function () { animating = false; };
                } else {
                    var current = answer.getBoundingClientRect().height;
                    var anim = answer.animate(
                        [{ height: current + 'px', opacity: 1 }, { height: '0px', opacity: 0 }],
                        { duration: 180, easing: 'cubic-bezier(.4,0,1,1)' }
                    );
                    anim.onfinish = function () { item.open = false; animating = false; };
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var root = qs('[data-parent-landing]');
        if (!root) {
            return;
        }
        initStickyBar(root);
        initFormatToggle(root);
        initFaqAnalytics(root);
        initFreeChapterCtas(root);
        initSectionFadeIns(root);
        initSmoothFaq(root);
        // Page-view itself is already fired server-side (parent_landing_view,
        // see page-reluctant-reader-adventure-kit.php) -- no JS duplicate here.
    });
})();
