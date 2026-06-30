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
});
