(function () {
    'use strict';

    function inferType(message) {
        var text = String(message || '').toLowerCase();
        if (/hapus|delete|remove|nonaktif|putuskan|disconnect/.test(text)) return 'danger';
        if (/sinkron|migrasi|cleanup|bersihkan|logout|lanjutkan|reset/.test(text)) return 'warning';
        return 'warning';
    }

    function ensureModal() {
        var existing = document.getElementById('viConfirmModal');
        if (existing) return existing;

        var modal = document.createElement('div');
        modal.id = 'viConfirmModal';
        modal.className = 'vi-confirm';
        modal.setAttribute('aria-hidden', 'true');
        modal.innerHTML = '' +
            '<div class="vi-confirm__backdrop" data-vi-confirm-cancel></div>' +
            '<div class="vi-confirm__box" role="dialog" aria-modal="true" aria-labelledby="viConfirmTitle">' +
                '<div class="vi-confirm__iconWrap">' +
                    '<div class="vi-confirm__icon"><span class="vi-confirm__iconSymbol" aria-hidden="true">!</span></div>' +
                '</div>' +
                '<div class="vi-confirm__content">' +
                    '<h2 id="viConfirmTitle">Konfirmasi Aksi!</h2>' +
                    '<p id="viConfirmMessage">Lanjutkan aksi ini?</p>' +
                '</div>' +
                '<div class="vi-confirm__actions">' +
                    '<button type="button" class="vi-confirm__cancel" data-vi-confirm-cancel>Cancel</button>' +
                    '<button type="button" class="vi-confirm__ok" data-vi-confirm-ok>Yes</button>' +
                '</div>' +
            '</div>';
        document.body.appendChild(modal);
        return modal;
    }

    function applyType(modal, type) {
        modal.classList.remove('vi-confirm--warning', 'vi-confirm--danger', 'vi-confirm--success');
        modal.classList.add('vi-confirm--' + type);

        var icon = modal.querySelector('.vi-confirm__iconSymbol');
        var ok = modal.querySelector('[data-vi-confirm-ok]');
        var cancel = modal.querySelector('[data-vi-confirm-cancel].vi-confirm__cancel');

        if (icon) {
            icon.textContent = type === 'danger' ? '🗑' : '!';
        }
        if (ok) {
            ok.textContent = type === 'danger' ? 'Yes' : 'Yes';
        }
        if (cancel) {
            cancel.textContent = 'Cancel';
        }
    }

    function ask(message, options) {
        return new Promise(function (resolve) {
            var modal = ensureModal();
            var msg = modal.querySelector('#viConfirmMessage');
            var ok = modal.querySelector('[data-vi-confirm-ok]');
            var cancelEls = modal.querySelectorAll('[data-vi-confirm-cancel]');
            var previousFocus = document.activeElement;
            var done = false;
            var type = (options && options.type) || inferType(message);

            applyType(modal, type);
            if (msg) msg.textContent = message || 'Lanjutkan aksi ini?';

            function close(result) {
                if (done) return;
                done = true;
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('vi-confirm-open');
                document.removeEventListener('keydown', onKeydown);
                ok.removeEventListener('click', onOk);
                cancelEls.forEach(function (el) { el.removeEventListener('click', onCancel); });
                setTimeout(function () {
                    if (previousFocus && typeof previousFocus.focus === 'function') previousFocus.focus();
                }, 0);
                resolve(result);
            }

            function onOk() { close(true); }
            function onCancel() { close(false); }
            function onKeydown(event) {
                if (event.key === 'Escape') close(false);
                if (event.key === 'Enter') close(true);
            }

            ok.addEventListener('click', onOk);
            cancelEls.forEach(function (el) { el.addEventListener('click', onCancel); });
            document.addEventListener('keydown', onKeydown);

            document.body.classList.add('vi-confirm-open');
            modal.setAttribute('aria-hidden', 'false');
            modal.classList.add('is-open');
            setTimeout(function () { ok.focus(); }, 40);
        });
    }

    window.viConfirm = ask;

    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('a[data-confirm], button[data-confirm]');
        if (!trigger || trigger.dataset.confirmAccepted === '1') return;

        e.preventDefault();
        e.stopPropagation();

        ask(trigger.dataset.confirm || 'Lanjutkan aksi ini?', {
            type: trigger.dataset.confirmType || undefined
        }).then(function (ok) {
            if (!ok) return;
            trigger.dataset.confirmAccepted = '1';
            if (trigger.tagName === 'A' && trigger.href) {
                window.location.href = trigger.href;
            } else {
                trigger.click();
            }
        });
    }, true);

    document.addEventListener('submit', function (e) {
        var form = e.target.closest('form[data-confirm]');
        if (!form || form.dataset.confirmAccepted === '1') return;

        e.preventDefault();
        e.stopPropagation();

        ask(form.dataset.confirm || 'Lanjutkan aksi ini?', {
            type: form.dataset.confirmType || undefined
        }).then(function (ok) {
            if (!ok) return;
            form.dataset.confirmAccepted = '1';
            if (typeof form.requestSubmit === 'function') {
                HTMLFormElement.prototype.submit.call(form);
            } else {
                form.submit();
            }
        });
    }, true);
})();
