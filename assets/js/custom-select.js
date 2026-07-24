(function () {
    'use strict';

    function isMobile() {
        return window.matchMedia && window.matchMedia('(max-width: 767px)').matches;
    }

    function closeAll(except) {
        document.querySelectorAll('.vi-select-wrap.is-open').forEach(function (wrap) {
            if (wrap !== except) {
                wrap.classList.remove('is-open');
                var btn = wrap.querySelector('.vi-select-btn');
                if (btn) btn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    function getSelectedText(select) {
        var option = select.options[select.selectedIndex];
        return option ? option.text : '-- pilih --';
    }

    function syncOptions(select, list, text) {
        list.innerHTML = '';
        Array.prototype.forEach.call(select.options, function (option) {
            var item = document.createElement('button');
            item.type = 'button';
            item.className = 'vi-select-option';
            item.setAttribute('role', 'option');
            item.dataset.value = option.value;
            item.textContent = option.text;
            if (option.selected) item.classList.add('is-selected');
            if (option.disabled) item.disabled = true;

            item.addEventListener('click', function () {
                if (option.disabled) return;
                select.value = option.value;
                text.textContent = option.text;
                list.querySelectorAll('.vi-select-option').forEach(function (node) {
                    node.classList.toggle('is-selected', node.dataset.value === select.value);
                });
                var wrap = select.closest('.vi-select-wrap');
                if (wrap) wrap.classList.remove('is-open');
                var btn = wrap ? wrap.querySelector('.vi-select-btn') : null;
                if (btn) btn.setAttribute('aria-expanded', 'false');
                select.dispatchEvent(new Event('change', { bubbles: true }));
            });

            list.appendChild(item);
        });
    }

    function build(select) {
        if (!select || select.dataset.viCustomReady === '1') return;
        select.dataset.viCustomReady = '1';

        var wrap = document.createElement('div');
        wrap.className = 'vi-select-wrap';

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'vi-select-btn';
        btn.setAttribute('aria-haspopup', 'listbox');
        btn.setAttribute('aria-expanded', 'false');

        var text = document.createElement('span');
        text.className = 'vi-select-text';
        text.textContent = getSelectedText(select);

        var arrow = document.createElement('span');
        arrow.className = 'vi-select-arrow';
        arrow.setAttribute('aria-hidden', 'true');
        arrow.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>';

        btn.appendChild(text);
        btn.appendChild(arrow);

        var list = document.createElement('div');
        list.className = 'vi-select-list';
        list.setAttribute('role', 'listbox');
        syncOptions(select, list, text);

        select.parentNode.insertBefore(wrap, select);
        wrap.appendChild(select);
        wrap.appendChild(btn);
        wrap.appendChild(list);

        btn.addEventListener('click', function (event) {
            event.preventDefault();
            if (!isMobile()) {
                select.focus();
                return;
            }
            syncOptions(select, list, text);
            var willOpen = !wrap.classList.contains('is-open');
            closeAll(willOpen ? wrap : null);
            wrap.classList.toggle('is-open', willOpen);
            btn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        });

        select.addEventListener('change', function () {
            text.textContent = getSelectedText(select);
            list.querySelectorAll('.vi-select-option').forEach(function (node) {
                node.classList.toggle('is-selected', node.dataset.value === select.value);
            });
        });
    }

    function refresh(root) {
        root = root || document;
        root.querySelectorAll('select.vi-custom-select').forEach(build);
    }

    window.VICustomSelect = { refresh: refresh, closeAll: closeAll };

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.vi-select-wrap')) closeAll(null);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closeAll(null);
    });

    document.addEventListener('DOMContentLoaded', function () {
        refresh(document);
    });
})();
