document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.querySelector('.nav-toggle');
    const nav = document.querySelector('.main-nav');

    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            nav.classList.toggle('open');
        });
    }

    document.querySelectorAll('[data-confirm]').forEach(function (button) {
        button.addEventListener('click', function (event) {
            const message = button.dataset.confirm || 'আপনি কি নিশ্চিত?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('.password-toggle').forEach(function (button) {
        button.addEventListener('click', function () {
            const input = document.getElementById(button.dataset.target || 'password');
            if (!input) return;
            const hidden = input.type === 'password';
            input.type = hidden ? 'text' : 'password';
            button.textContent = hidden ? 'গোপন করুন' : 'দেখুন';
        });
    });

    document.querySelectorAll('[data-preview]').forEach(function (input) {
        input.addEventListener('change', function () {
            const preview = document.getElementById(input.dataset.preview);
            if (!preview || !input.files || !input.files[0]) return;
            const reader = new FileReader();
            reader.onload = function (event) {
                preview.src = event.target.result;
                preview.hidden = false;
                const fallback = document.getElementById(input.dataset.previewFallback || '');
                if (fallback) fallback.hidden = true;
            };
            reader.readAsDataURL(input.files[0]);
        });
    });

    document.querySelectorAll('img[data-fallback], img[data-fallback-element]').forEach(function (image) {
        if (!image.getAttribute('src')) return;

        image.addEventListener('error', function () {
            const fallbackElement = document.getElementById(image.dataset.fallbackElement || '');
            if (fallbackElement) {
                image.hidden = true;
                fallbackElement.hidden = false;
                return;
            }

            const fallbackSource = image.dataset.fallback || '';
            if (fallbackSource && image.src !== fallbackSource) {
                image.removeAttribute('data-fallback');
                image.src = fallbackSource;
            }
        });
    });
});
