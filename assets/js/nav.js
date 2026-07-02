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

        window.dataLayer.push({
            event: target.getAttribute('data-bhp-event'),
            bhp_book: target.getAttribute('data-bhp-book') || '',
            bhp_format: target.getAttribute('data-bhp-format') || '',
            bhp_source: target.getAttribute('data-bhp-source') || ''
        });
    });
});
