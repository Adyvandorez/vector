(function () {
    var KEY = 'vi_theme_mode';
    var LEGACY_KEY = 'vi_theme';
    var root = document.documentElement;

    function safeGetMode() {
        try {
            var saved = localStorage.getItem(KEY) || localStorage.getItem(LEGACY_KEY);
            return saved === 'light' ? 'light' : 'dark';
        } catch (e) {
            return 'dark';
        }
    }

    function safeSaveMode(mode) {
        try {
            localStorage.setItem(KEY, mode);
            localStorage.setItem(LEGACY_KEY, mode);
        } catch (e) {}
    }

    function applyMode(mode, persist) {
        var isLight = mode === 'light';

        root.classList.toggle('vi-light-mode', isLight);
        root.classList.toggle('vi-dark-mode', !isLight);
        root.setAttribute('data-theme', isLight ? 'light' : 'dark');

        if (persist) safeSaveMode(isLight ? 'light' : 'dark');
        syncToggle(isLight ? 'light' : 'dark');
    }

    function syncToggle(mode) {
        var btn = document.getElementById('themeModeToggle');
        if (!btn) return;

        var label = btn.querySelector('.theme-mode-label');
        var isLight = mode === 'light';

        btn.setAttribute('aria-pressed', isLight ? 'true' : 'false');
        btn.setAttribute('title', isLight ? 'Ganti ke Dark Mode' : 'Ganti ke Light Mode');
        if (label) label.textContent = isLight ? 'Light Mode' : 'Dark Mode';
    }

    function init() {
        applyMode(safeGetMode(), false);

        var btn = document.getElementById('themeModeToggle');
        if (!btn) return;

        btn.addEventListener('click', function () {
            var next = root.classList.contains('vi-light-mode') ? 'dark' : 'light';
            applyMode(next, true);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.addEventListener('storage', function (event) {
        if (event.key === KEY || event.key === LEGACY_KEY) applyMode(safeGetMode(), false);
    });

    window.VectorInvoiceTheme = {
        apply: function (mode) { applyMode(mode === 'light' ? 'light' : 'dark', true); },
        current: function () { return root.classList.contains('vi-light-mode') ? 'light' : 'dark'; }
    };
})();
