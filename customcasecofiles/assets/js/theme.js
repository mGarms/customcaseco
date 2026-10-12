(function () {
    'use strict';

    var STORAGE_KEY = 'cc-theme';

    function getPreferredTheme() {
        try {
            var saved = localStorage.getItem(STORAGE_KEY);

            if (saved === 'dark' || saved === 'light') {
                return saved;
            }
        } catch (e) {}

        if (
            window.matchMedia &&
            window.matchMedia('(prefers-color-scheme: dark)').matches
        ) {
            return 'dark';
        }

        return 'light';
    }

    function applyTheme(theme) {
        var html = document.documentElement;

        html.setAttribute('data-theme', theme);
        html.classList.toggle('theme-dark', theme === 'dark');
        html.classList.toggle('theme-light', theme === 'light');

        try {
            localStorage.setItem(STORAGE_KEY, theme);
        } catch (e) {}

        updateThemeButton(theme);
    }

    function updateThemeButton(theme) {
        var button = document.getElementById('themeToggleBtn');
        var icon = document.getElementById('themeToggleIcon');

        if (!button || !icon) {
            return;
        }

        icon.classList.remove(
            'bi-sun-fill',
            'bi-moon-stars-fill'
        );

        if (theme === 'dark') {
            icon.classList.add('bi-sun-fill');

            button.setAttribute(
                'aria-label',
                'Switch to light mode'
            );

            button.setAttribute(
                'title',
                'Switch to light mode'
            );
        } else {
            icon.classList.add('bi-moon-stars-fill');

            button.setAttribute(
                'aria-label',
                'Switch to dark mode'
            );

            button.setAttribute(
                'title',
                'Switch to dark mode'
            );
        }
    }

    /* Apply theme immediately */
    applyTheme(getPreferredTheme());

    document.addEventListener('DOMContentLoaded', function () {

        var button = document.getElementById('themeToggleBtn');

        if (!button) {
            return;
        }

        updateThemeButton(
            document.documentElement.getAttribute('data-theme') || 'light'
        );

        button.addEventListener('click', function () {

            var current =
                document.documentElement.getAttribute('data-theme') ||
                'light';

            var next =
                current === 'dark'
                    ? 'light'
                    : 'dark';

            applyTheme(next);

        });

    });

})();