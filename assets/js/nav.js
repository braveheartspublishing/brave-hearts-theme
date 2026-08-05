document.addEventListener('DOMContentLoaded', function() {
    var toggle = document.querySelector('.nav-toggle');
    var nav = document.querySelector('.site-nav');
    if (toggle && nav) {
        var closeNavigation = function() {
            nav.classList.remove('open');
            toggle.setAttribute('aria-expanded', 'false');
        };

        toggle.addEventListener('click', function() {
            nav.classList.toggle('open');
            toggle.setAttribute('aria-expanded', nav.classList.contains('open'));
        });

        nav.addEventListener('click', function(event) {
            if (event.target.closest('a')) {
                closeNavigation();
            }
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && nav.classList.contains('open')) {
                closeNavigation();
                toggle.focus();
            }
        });
    }

    // Shared payload builder for both the generic click handler and the
    // generic impression observer below. Always includes the original
    // three fields (bhp_book/bhp_format/bhp_source) exactly as before --
    // existing elements using only those keep an identical payload shape.
    // Phase 1D CTA fields are only added when the element actually
    // carries them (data-bhp-cta-*), so unrelated events (Amazon links,
    // the Kirkus credibility component, etc.) never gain empty/unused
    // keys they didn't have before.
    function bhpBuildEventPayload(el, eventName) {
        var payload = {
            event: eventName,
            bhp_book: el.getAttribute('data-bhp-book') || '',
            bhp_format: el.getAttribute('data-bhp-format') || '',
            bhp_source: el.getAttribute('data-bhp-source') || ''
        };
        var ctaId = el.getAttribute('data-bhp-cta-id');
        if (ctaId) {
            payload.cta_id = ctaId;
            payload.cta_placement = el.getAttribute('data-bhp-cta-placement') || '';
            payload.cta_destination_type = el.getAttribute('data-bhp-cta-destination') || '';
            payload.audience = el.getAttribute('data-bhp-cta-audience') || '';
            payload.funnel_stage = el.getAttribute('data-bhp-cta-funnel-stage') || '';
            payload.variant = el.getAttribute('data-bhp-cta-variant') || '';
        }
        var leadOffer = el.getAttribute('data-bhp-lead-offer');
        if (leadOffer) {
            payload.lead_offer = leadOffer;
            payload.audience = el.getAttribute('data-bhp-form-audience') || '';
            payload.placement = el.getAttribute('data-bhp-form-placement') || '';
        }
        // Sitewide quiz routing (2026-07-17): destination audience on the
        // quiz result CTA and the entry-location banner, read generically
        // here rather than adding a second quiz-specific click handler.
        var quizAudience = el.getAttribute('data-bhp-quiz-audience');
        if (quizAudience) {
            payload.quiz_audience = quizAudience;
        }
        var entryLocation = el.getAttribute('data-bhp-entry-location');
        if (entryLocation) {
            payload.entry_location = entryLocation;
        }
        return payload;
    }

    // Fires direct-purchase / Amazon-affiliate click events into dataLayer
    // when an analytics platform is present. No-op otherwise — this does
    // not add or activate any analytics platform on its own.
    document.addEventListener('click', function(event) {
        var bookMatch = (document.body.className || '').match(/bhp-book-(\S+)/);
        var target = event.target.closest('[data-bhp-event]');
        var isNativeAddToCart = !target && event.target.closest('form.cart button[type="submit"]') && bookMatch;

        if (!target && !isNativeAddToCart) {
            return;
        }
        if (typeof window.dataLayer === 'undefined' || !Array.isArray(window.dataLayer)) {
            return;
        }

        if (isNativeAddToCart) {
            window.dataLayer.push({
                event: 'bhp_direct_purchase_click',
                bhp_book: bookMatch[1],
                bhp_format: '',
                bhp_source: 'product_page'
            });
            return;
        }

        window.dataLayer.push(bhpBuildEventPayload(target, target.getAttribute('data-bhp-event')));
    });

    // Generic, reusable one-time impression tracking for any element with
    // data-bhp-impression-event (e.g. the Kirkus credibility component).
    // Native IntersectionObserver only -- no new library, no vendor script.
    // No-op if dataLayer or IntersectionObserver aren't present.
    if (typeof window.IntersectionObserver === 'function') {
        var impressionTargets = document.querySelectorAll('[data-bhp-impression-event]');
        if (impressionTargets.length) {
            var impressionObserver = new IntersectionObserver(function (entries, observer) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) {
                        return;
                    }
                    var el = entry.target;
                    observer.unobserve(el);
                    if (typeof window.dataLayer === 'undefined' || !Array.isArray(window.dataLayer)) {
                        return;
                    }
                    window.dataLayer.push(bhpBuildEventPayload(el, el.getAttribute('data-bhp-impression-event')));
                });
            }, { threshold: 0.5 });
            impressionTargets.forEach(function (el) {
                impressionObserver.observe(el);
            });
        }
    }

    // Generic, reusable one-time "first interaction" tracking (e.g.
    // lead_form_start on an acquisition form's first field focus) for
    // any element with data-bhp-focus-event. Delegated on 'focusin' with
    // { once: true } per element so each element fires at most once and
    // needs no manual listener bookkeeping/cleanup. No-op if dataLayer
    // isn't present. Never fires on blur/change -- focus only, matching
    // "the visitor engaged with the form" rather than "the visitor
    // finished a field."
    document.querySelectorAll('[data-bhp-focus-event]').forEach(function (el) {
        el.addEventListener('focusin', function () {
            if (typeof window.dataLayer === 'undefined' || !Array.isArray(window.dataLayer)) {
                return;
            }
            window.dataLayer.push(bhpBuildEventPayload(el, el.getAttribute('data-bhp-focus-event')));
        }, { once: true });
    });
});
