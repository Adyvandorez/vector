(function () {
    'use strict';

    var activeInput = null;
    var viewDate = null;
    var selectedDate = null;
    var monthNames = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    var dayNames = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];

    function isMobile() {
        return window.matchMedia && window.matchMedia('(max-width: 767px)').matches;
    }

    function parseISO(value) {
        if (!value || !/^\d{4}-\d{2}-\d{2}$/.test(value)) return null;
        var p = value.split('-').map(Number);
        var d = new Date(p[0], p[1] - 1, p[2]);
        if (d.getFullYear() !== p[0] || d.getMonth() !== p[1] - 1 || d.getDate() !== p[2]) return null;
        return d;
    }

    function toISO(date) {
        var y = date.getFullYear();
        var m = String(date.getMonth() + 1).padStart(2, '0');
        var d = String(date.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + d;
    }

    function sameDate(a, b) {
        return a && b && a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
    }

    function ensurePicker() {
        var existing = document.getElementById('viDatePicker');
        if (existing) return existing;
        var picker = document.createElement('div');
        picker.id = 'viDatePicker';
        picker.className = 'vi-date-picker';
        picker.setAttribute('aria-hidden', 'true');
        picker.innerHTML = '' +
            '<div class="vi-date-picker__backdrop" data-vi-date-close></div>' +
            '<div class="vi-date-picker__panel" role="dialog" aria-modal="true">' +
                '<div class="vi-date-picker__top">' +
                    '<button type="button" class="vi-date-picker__month" data-vi-date-title></button>' +
                    '<div class="vi-date-picker__nav">' +
                        '<button type="button" data-vi-date-prev aria-label="Bulan sebelumnya">‹</button>' +
                        '<button type="button" data-vi-date-next aria-label="Bulan berikutnya">›</button>' +
                    '</div>' +
                '</div>' +
                '<div class="vi-date-picker__days" data-vi-date-days></div>' +
                '<div class="vi-date-picker__grid" data-vi-date-grid></div>' +
                '<div class="vi-date-picker__actions">' +
                    '<button type="button" data-vi-date-clear>Hapus</button>' +
                    '<button type="button" data-vi-date-today>Hari ini</button>' +
                '</div>' +
            '</div>';
        document.body.appendChild(picker);
        picker.querySelector('[data-vi-date-close]').addEventListener('click', close);
        picker.querySelector('[data-vi-date-prev]').addEventListener('click', function () {
            viewDate.setMonth(viewDate.getMonth() - 1);
            render();
        });
        picker.querySelector('[data-vi-date-next]').addEventListener('click', function () {
            viewDate.setMonth(viewDate.getMonth() + 1);
            render();
        });
        picker.querySelector('[data-vi-date-clear]').addEventListener('click', function () {
            if (activeInput) {
                activeInput.value = '';
                activeInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
            close();
        });
        picker.querySelector('[data-vi-date-today]').addEventListener('click', function () {
            setValue(new Date());
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') close();
        });
        return picker;
    }

    function render() {
        var picker = ensurePicker();
        var title = picker.querySelector('[data-vi-date-title]');
        var days = picker.querySelector('[data-vi-date-days]');
        var grid = picker.querySelector('[data-vi-date-grid]');
        title.textContent = monthNames[viewDate.getMonth()] + ' ' + viewDate.getFullYear();
        days.innerHTML = dayNames.map(function (d) { return '<span>' + d + '</span>'; }).join('');
        grid.innerHTML = '';

        var first = new Date(viewDate.getFullYear(), viewDate.getMonth(), 1);
        var start = new Date(first);
        start.setDate(1 - first.getDay());
        var today = new Date();
        for (var i = 0; i < 42; i++) {
            var d = new Date(start);
            d.setDate(start.getDate() + i);
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'vi-date-picker__date';
            btn.textContent = String(d.getDate());
            btn.dataset.value = toISO(d);
            if (d.getMonth() !== viewDate.getMonth()) btn.classList.add('is-muted');
            if (sameDate(d, today)) btn.classList.add('is-today');
            if (sameDate(d, selectedDate)) btn.classList.add('is-selected');
            btn.addEventListener('click', function () {
                setValue(parseISO(this.dataset.value));
            });
            grid.appendChild(btn);
        }
    }

    function setValue(date) {
        if (!activeInput || !date) return;
        activeInput.value = toISO(date);
        activeInput.dispatchEvent(new Event('input', { bubbles: true }));
        activeInput.dispatchEvent(new Event('change', { bubbles: true }));
        close();
    }

    function open(input) {
        if (!isMobile()) return;
        activeInput = input;
        selectedDate = parseISO(input.value);
        viewDate = selectedDate ? new Date(selectedDate) : new Date();
        var picker = ensurePicker();
        render();
        picker.setAttribute('aria-hidden', 'false');
        picker.classList.add('is-open');
        document.body.classList.add('vi-date-open');
    }

    function close() {
        var picker = document.getElementById('viDatePicker');
        if (!picker) return;
        picker.classList.remove('is-open');
        picker.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('vi-date-open');
        activeInput = null;
    }

    function enhance(input) {
        if (!input || input.dataset.viDateReady === '1') return;
        input.dataset.viDateReady = '1';
        if (isMobile()) {
            input.dataset.nativeType = input.type;
            try { input.type = 'text'; } catch (e) {}
            input.setAttribute('readonly', 'readonly');
            input.setAttribute('inputmode', 'none');
        }
        input.addEventListener('click', function (event) {
            if (!isMobile()) return;
            event.preventDefault();
            this.blur();
            open(this);
        });
        input.addEventListener('keydown', function (event) {
            if (!isMobile()) return;
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                open(this);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('input.vi-date-input').forEach(enhance);
    });
})();
