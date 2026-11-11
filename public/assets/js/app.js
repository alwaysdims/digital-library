/* Lentera Digital Library — interaksi ringan. */
(function () {
    'use strict';

    // Sidebar offcanvas pada layar kecil.
    document.addEventListener('DOMContentLoaded', function () {
        var toggle = document.querySelector('[data-sidebar-toggle]');
        var sidebar = document.querySelector('[data-sidebar]');
        var backdrop = document.querySelector('[data-sidebar-backdrop]');

        function closeSidebar() {
            if (sidebar) sidebar.classList.remove('is-open');
            if (backdrop) backdrop.classList.remove('is-open');
            if (toggle) toggle.setAttribute('aria-expanded', 'false');
        }

        if (toggle && sidebar && backdrop) {
            toggle.addEventListener('click', function () {
                var open = sidebar.classList.toggle('is-open');
                backdrop.classList.toggle('is-open', open);
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            });

            backdrop.addEventListener('click', closeSidebar);

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') closeSidebar();
            });
        }

        // Konfirmasi untuk aksi yang mengubah data.
        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                var message = form.getAttribute('data-confirm') || 'Lanjutkan aksi ini?';
                if (!window.confirm(message)) {
                    e.preventDefault();
                }
            });
        });

        // Tombol yang memerlukan konfirmasi lewat atribut data.
        document.querySelectorAll('[data-confirm-click]').forEach(function (el) {
            el.addEventListener('click', function (e) {
                var message = el.getAttribute('data-confirm-click') || 'Lanjutkan aksi ini?';
                if (!window.confirm(message)) {
                    e.preventDefault();
                }
            });
        });

        // Sembunyikan peringatan setelah beberapa detik.
        document.querySelectorAll('[data-auto-dismiss]').forEach(function (el) {
            setTimeout(function () {
                el.style.transition = 'opacity .4s ease';
                el.style.opacity = '0';
                setTimeout(function () { el.remove(); }, 450);
            }, 6000);
        });
    });
})();
