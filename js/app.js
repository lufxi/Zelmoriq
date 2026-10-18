/* Zelmoriq: small progressive-enhancement helpers. Everything still works without JS. */
(function () {
  'use strict';
  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };

  // Mobile navigation
  $$('[data-nav-toggle]').forEach(function (b) {
    b.addEventListener('click', function () { document.body.classList.toggle('nav-open'); });
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { document.body.classList.remove('nav-open'); }
  });

  // Flash messages: dismiss + auto-hide
  function hideFlash(el) { el.classList.add('gone'); setTimeout(function () { el.remove(); }, 450); }
  $$('[data-flash]').forEach(function (el) {
    var x = $('[data-flash-close]', el);
    if (x) { x.addEventListener('click', function () { hideFlash(el); }); }
    if (el.classList.contains('success')) { setTimeout(function () { hideFlash(el); }, 6000); }
  });

  // Confirm before destructive actions: <form data-confirm="Delete this?">
  $$('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (!window.confirm(f.getAttribute('data-confirm'))) { e.preventDefault(); }
    });
  });

  // Auto-submit filter forms and star ratings
  $$('form[data-autosubmit] select').forEach(function (s) {
    s.addEventListener('change', function () { s.form.submit(); });
  });
  $$('.rate input').forEach(function (r) {
    r.addEventListener('change', function () {
      var f = r.form;
      if (f.requestSubmit) { f.requestSubmit(); } else { f.submit(); }
    });
  });

  // Register page: show the fields for the chosen role
  var roleRadios = $$('input[name="role"][data-role-toggle]');
  function syncRole() {
    var chosen = roleRadios.filter(function (r) { return r.checked; })[0];
    var role = chosen ? chosen.value : '';
    $$('.role-fields').forEach(function (box) {
      var on = box.getAttribute('data-for') === role;
      box.hidden = !on;
      $$('input, select, textarea', box).forEach(function (el) {
        if (el.hasAttribute('data-req')) { el.required = on; }
        el.disabled = !on;
      });
    });
  }
  if (roleRadios.length) {
    roleRadios.forEach(function (r) { r.addEventListener('change', syncRole); });
    syncRole();
  }

  // Password strength meter: <input data-meter="#id">
  $$('input[data-meter]').forEach(function (inp) {
    var bar = $(inp.getAttribute('data-meter') + ' i');
    if (!bar) { return; }
    inp.addEventListener('input', function () {
      var v = inp.value, s = 0;
      if (v.length >= 8) { s++; }
      if (/[a-z]/.test(v) && /[A-Z]/.test(v)) { s++; }
      if (/\d/.test(v)) { s++; }
      if (/[^A-Za-z0-9]/.test(v)) { s++; }
      bar.style.width = (v ? s * 25 : 0) + '%';
      bar.setAttribute('data-s', s);
    });
  });

  // Live table filtering: <input data-filter-table="#tbl"> and <select data-filter-table="#tbl" data-col="3">
  $$('[data-filter-table]').forEach(function (ctl) {
    var table = $(ctl.getAttribute('data-filter-table'));
    if (!table) { return; }
    var controls = $$('[data-filter-table="' + ctl.getAttribute('data-filter-table') + '"]');
    function run() {
      var rows = $$('tbody tr', table), shown = 0;
      rows.forEach(function (tr) {
        if (tr.hasAttribute('data-empty')) { return; }
        var ok = true;
        controls.forEach(function (c) {
          var val = c.value.trim().toLowerCase();
          if (!val) { return; }
          var col = c.getAttribute('data-col');
          var text = col === null ? tr.textContent.toLowerCase()
                                  : (tr.cells[parseInt(col, 10)] || {}).textContent.toLowerCase().trim();
          if (col === null ? text.indexOf(val) === -1 : text !== val) { ok = false; }
        });
        tr.hidden = !ok;
        if (ok) { shown++; }
      });
      var none = $('[data-empty]', table);
      if (none) { none.hidden = shown !== 0; }
    }
    ctl.addEventListener(ctl.tagName === 'SELECT' ? 'change' : 'input', run);
  });

  // CSV export of a visible table: <button data-export-csv="#tbl" data-filename="students.csv">
  $$('[data-export-csv]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var table = $(btn.getAttribute('data-export-csv'));
      if (!table) { return; }
      var lines = [];
      $$('tr', table).forEach(function (tr) {
        if (tr.hidden || tr.hasAttribute('data-empty')) { return; }
        var cells = $$('th, td', tr).filter(function (c) { return !c.hasAttribute('data-nocsv'); }).map(function (c) {
          return '"' + c.textContent.replace(/\s+/g, ' ').trim().replace(/"/g, '""') + '"';
        });
        lines.push(cells.join(','));
      });
      var blob = new Blob(['\ufeff' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
      var a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = btn.getAttribute('data-filename') || 'export.csv';
      document.body.appendChild(a); a.click(); a.remove();
      setTimeout(function () { URL.revokeObjectURL(a.href); }, 500);
    });
  });
})();
