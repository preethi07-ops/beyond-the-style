// Beyond The Style - small progressive enhancements. The site still works without JavaScript
// except for the mobile menu toggle and gallery thumbnails.
(function () {
    'use strict';

    // Mobile navigation toggle.
    var toggle = document.querySelector('.nav-toggle');
    var nav = document.getElementById('main-nav');
    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            var open = nav.classList.toggle('open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    // Product gallery: click a thumbnail to swap the main image.
    var main = document.getElementById('main-image');
    document.querySelectorAll('.thumb').forEach(function (thumb) {
        thumb.addEventListener('click', function () {
            if (main) { main.src = thumb.getAttribute('data-full'); }
        });
    });

    // Prevent double submits (e.g. placing an order twice by double-clicking).
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (form.dataset.submitted === '1') {
                event.preventDefault();
                return;
            }
            form.dataset.submitted = '1';
            setTimeout(function () { form.dataset.submitted = ''; }, 4000);
        });
    });
})();
