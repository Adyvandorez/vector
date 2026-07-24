(function () {
  'use strict';

  function updateActionField(select) {
    var form = select.closest('form');
    if (!form) return;
    var input = form.querySelector('[data-action-value]');
    var help = form.querySelector('[data-action-help]');
    if (!input) return;

    var type = select.value;
    var config = {
      ORDER: ['Opsional: ID jenis desain', 'Kosongkan untuk membuka proses pemesanan umum.'],
      CATALOG: ['Opsional: ID jenis desain', 'Kosongkan untuk membuka seluruh katalog.'],
      PORTFOLIO: ['Opsional: ID portofolio', 'Kosongkan untuk membuka seluruh galeri.'],
      URL: ['https://alamat-tujuan.com', 'URL wajib diawali http:// atau https://.'],
      NONE: ['Tidak digunakan', 'Tombol tidak akan menjalankan aksi.']
    };
    var current = config[type] || config.NONE;
    input.placeholder = current[0];
    input.disabled = type === 'NONE';
    if (help) help.textContent = current[1];
  }

  document.querySelectorAll('[data-action-select]').forEach(function (select) {
    updateActionField(select);
    select.addEventListener('change', function () { updateActionField(select); });
  });

  document.querySelectorAll('input[inputmode="numeric"]').forEach(function (input) {
    input.addEventListener('input', function () {
      this.value = this.value.replace(/[^0-9]/g, '');
    });
  });

  document.querySelectorAll('.content-edit-toggle').forEach(function (details) {
    var card = details.closest('.content-manage-card');
    var panel = card ? card.querySelector('[data-details-panel]') : null;
    if (!panel) return;
    details.addEventListener('toggle', function () {
      panel.classList.toggle('is-open', details.open);
      var summary = details.querySelector('summary');
      if (summary) summary.textContent = details.open ? 'Tutup Form Edit' : 'Edit';
      if (details.open) {
        setTimeout(function () {
          panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }, 80);
      }
    });
  });

  var tabs = Array.prototype.slice.call(document.querySelectorAll('.app-content-tabs a'));
  var sections = tabs.map(function (tab) {
    var id = tab.getAttribute('href');
    return id && id.charAt(0) === '#' ? document.querySelector(id) : null;
  }).filter(Boolean);

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      tabs.forEach(function (item) { item.classList.remove('is-active'); });
      tab.classList.add('is-active');
    });
  });

  if ('IntersectionObserver' in window && sections.length) {
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        tabs.forEach(function (tab) {
          tab.classList.toggle('is-active', tab.getAttribute('href') === '#' + entry.target.id);
        });
      });
    }, { rootMargin: '-22% 0px -65% 0px', threshold: 0 });
    sections.forEach(function (section) { observer.observe(section); });
  }

  if (location.hash) {
    var target = document.querySelector(location.hash);
    if (target) {
      setTimeout(function () { target.scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 120);
    }
  }
})();
