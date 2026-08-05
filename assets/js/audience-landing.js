/**
 * Shared audience landing-page JS (Teachers, Gift Buyers, Bookstores/
 * Retailers, Organizations). Same proven behavior as parent-landing.js:
 * sticky mini-CTA bar visibility, the Complete Collection format toggle
 * (Paperback/Hardcover), FAQ analytics, and lead-magnet-CTA scroll-to-panel
 * behavior. Signup form submission itself is the site's existing
 * signup-form.php handler (unrelated JS, untouched here) -- this file
 * never intercepts or duplicates that submission. Independent file from
 * parent-landing.js so neither page's behavior can regress the other.
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
        var bar = qs('[data-audience-stickybar]', root);
        if (!bar) {
            return;
        }
        var suppressTargets = [
            qs('#free', root),
            qs('[data-audience-pricing-card]', root),
            qs('.audience-landing-final', root),
            qs('.site-footer') || qs('footer')
        ].filter(Boolean);

        var scrolled = false;
        var suppressed = false;

        function render() {
            var visible = scrolled && !suppressed;
            bar.classList.toggle('is-visible', visible);
            bar.classList.toggle('is-suppressed', suppressed);
            document.body.classList.toggle('bhp-audience-stickybar-visible', visible);
            // BH-06: the bundle plugin's floating cart button (#bhp-floating-cart)
            // is fixed bottom-right and its `bottom` is overconstrained, so it
            // ignores CSS bottom overrides; the plugin also drives its own
            // transform (scale) inline. Lift it clear above the sticky Collection
            // bar by writing an inline transform here — the only layer that
            // reliably wins — combining the upward shift with scale(1) so the
            // FAB stays full-size and never overlaps the bar's right-hand CTA on
            // narrow mobile. Restored to the plugin's own state when the bar hides.
            var fab = document.getElementById('bhp-floating-cart');
            if (fab) {
                if (visible) {
                    fab.style.setProperty('transform', 'translateY(-' + (bar.offsetHeight + 12) + 'px) scale(1)', 'important');
                } else {
                    fab.style.removeProperty('transform');
                }
            }
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
        qsa('[data-audience-format-btn]', root).forEach(function (btn) {
            var isMatch = btn.getAttribute('data-audience-format-btn') === format;
            btn.classList.toggle('is-selected', isMatch);
            btn.setAttribute('aria-checked', isMatch ? 'true' : 'false');
        });
        qsa('[data-audience-format-panel]', root).forEach(function (panel) {
            var isMatch = panel.getAttribute('data-audience-format-panel') === format;
            if (isMatch) {
                panel.removeAttribute('hidden');
            } else {
                panel.setAttribute('hidden', '');
            }
        });
        /*
         * 2026-08-05 -- the add-the-set-and-checkout CTAs that live OUTSIDE the
         * per-format panels (the sticky footer bar, the final CTA) post a format
         * of their own. Without this they would keep posting the page's default
         * format after the customer switched the toggle, and a customer who
         * chose paperback would land on checkout holding three hardcovers.
         *
         * This is the same defect class that once let the Complete Collection
         * page's sticky bar and its pricing panel disagree about format, fixed
         * there by `data-bhp-stickybar-action`. Same idea, same shape.
         *
         * A control INSIDE a per-format panel is deliberately NOT marked and is
         * not touched here -- it already knows its format and is hidden when
         * that format is not selected.
         */
        qsa('[data-bhp-collection-action]', root).forEach(function (input) {
            input.value = 'complete_' + format + '_smart';
        });
    }

    function initFormatToggle(root) {
        var buttons = qsa('[data-audience-format-btn]', root);
        if (!buttons.length) {
            return;
        }
        buttons.forEach(function (btn, index) {
            btn.addEventListener('click', function () {
                var format = btn.getAttribute('data-audience-format-btn');
                setFormat(root, format);
                pushEvent('audience_landing_format_selected', { format: format });
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
        qsa('.audience-landing-faq__item', root).forEach(function (item) {
            item.addEventListener('toggle', function () {
                if (item.open) {
                    pushEvent('audience_landing_faq_open', { question: item.getAttribute('data-question') || '' });
                }
            });
        });
    }

    function initLeadCtas(root) {
        qsa('[data-audience-free-cta]', root).forEach(function (link) {
            link.addEventListener('click', function () {
                pushEvent('audience_landing_free_cta_click', {});
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
     * The revealed state is never left depending on the CSS transition
     * actually completing: some engines can add both the "hidden" and
     * "revealed" classes within the same paint tick and leave opacity stuck
     * at an intermediate value with no further updates (confirmed in this
     * codebase's own staging QA -- a real, reproducible defect, not a
     * hypothetical one). So once a section is revealed, both classes are
     * removed a moment later, returning it to its plain, class-free default
     * styling -- which is unconditionally opacity:1 with no transition
     * involved at all. The animation is purely a bonus in the middle; the
     * guaranteed end state never depends on it working correctly.
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
        var sections = qsa('.audience-landing__section', root).filter(function (el) {
            return !el.classList.contains('audience-landing-hero');
        });
        if (!sections.length) {
            return;
        }

        function settle(el) {
            el.classList.add('al-in-view');
            setTimeout(function () {
                el.classList.remove('al-fade-init', 'al-in-view');
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
                el.classList.add('al-fade-init');
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
        qsa('.audience-landing-faq__item', root).forEach(function (item) {
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
        var root = qs('[data-audience-landing]');
        if (!root) {
            return;
        }
        initStickyBar(root);
        initFormatToggle(root);
        initFaqAnalytics(root);
        initLeadCtas(root);
        initSectionFadeIns(root);
        initSmoothFaq(root);
        // Page-view itself is fired server-side per page (e.g. teacher_landing_view)
        // -- no JS duplicate here.
    });
})();
