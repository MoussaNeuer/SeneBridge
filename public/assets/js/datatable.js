/* Back-office SeneBridge — listings intelligents.
 * Améliore toute <table data-table> : recherche instantanée, filtre par statut
 * (data-status sur chaque <tr>), tri des colonnes, compteur et export CSV. */
(function () {
    'use strict';

    function qsa(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

    function csv(data, filename) {
        var rows = data.map(function (row) {
            return row.map(function (cell) {
                var s = String(cell == null ? '' : cell);
                return /[;"\n]/.test(s) ? '"' + s.replace(/"/g, '""') + '"' : s;
            }).join(';');
        });
        var blob = new Blob(['\ufeff' + rows.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
        var a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = filename + '-' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(a);
        a.click();
        setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 100);
    }

    function enhance(table) {
        if (table.dataset.sbEnhanced === '1') return;
        table.dataset.sbEnhanced = '1';
        var wrapper = table.closest('.sb-table-wrap') || table.parentNode;

        /* Barre d'outils */
        var toolbar = document.createElement('div');
        toolbar.className = 'sb-table-toolbar flex flex-wrap items-center gap-3 px-4 py-3 border-b border-brand/10';
        toolbar.innerHTML =
            '<label class="flex-1 min-w-[200px] relative">' +
            '<svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-ink/40" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>' +
            '<input type="search" data-dt-search placeholder="Rechercher dans le tableau\u2026" class="w-full pl-9 pr-3 py-2 rounded-lg border border-brand/15 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40">' +
            '</label>' +
            '<select data-dt-status class="px-3 py-2 rounded-lg border border-brand/15 text-sm bg-white dark:bg-ink">' +
            '<option value="">Tous les statuts</option></select>' +
            '<button type="button" data-dt-export class="px-3 py-2 rounded-lg border border-brand/15 text-sm font-semibold hover:bg-cream dark:hover:bg-white/10 transition">Export CSV</button>';

        if (wrapper) { wrapper.insertBefore(toolbar, wrapper.firstChild); }

        /* Statuts présents dans la colonne statut */
        var statusSelect = toolbar.querySelector('[data-dt-status]');
        var statuses = {};
        qsa('tbody tr[data-status]', table).forEach(function (tr) {
            var s = tr.getAttribute('data-status');
            if (s) statuses[s] = (statuses[s] || 0) + 1;
        });
        Object.keys(statuses).sort().forEach(function (s) {
            var opt = document.createElement('option');
            opt.value = s;
            opt.textContent = s + ' (' + statuses[s] + ')';
            statusSelect.appendChild(opt);
        });

        /* Tri : clic sur <th> (toutes sauf la dernière colonne d'actions) */
        var ths = qsa('thead th', table);
        ths.forEach(function (th, index) {
            if (index === ths.length - 1) return;
            th.classList.add('cursor-pointer', 'select-none');
            th.title = 'Trier';
            th.addEventListener('click', function () {
                var asc = th.getAttribute('data-dir') !== 'asc';
                qsa('thead th', table).forEach(function (t) { t.removeAttribute('data-dir'); });
                th.setAttribute('data-dir', asc ? 'asc' : 'desc');
                sortRows(table, index, asc);
                applyFilters(table);
            });
        });

        var searchInput = toolbar.querySelector('[data-dt-search]');
        searchInput.addEventListener('input', function () { applyFilters(table); });
        statusSelect.addEventListener('change', function () { applyFilters(table); });

        toolbar.querySelector('[data-dt-export]').addEventListener('click', function () { exportCsv(table); });

        updateCount(table);
    }

    function rowValue(tr, index) {
        var cell = qsa('td', tr)[index];
        if (!cell) return '';
        return (cell.textContent || '').trim();
    }

    function sortRows(table, index, asc) {
        var tbody = qsa('tbody', table)[0];
        if (!tbody) return;
        var rows = qsa('tr', tbody);
        rows.sort(function (a, b) {
            var av = rowValue(a, index).toLowerCase();
            var bv = rowValue(b, index).toLowerCase();
            if (av === bv) return 0;
            var cmp = av > bv ? 1 : -1;
            return asc ? cmp : -cmp;
        });
        rows.forEach(function (r) { tbody.appendChild(r); });
    }

    function applyFilters(table) {
        var toolbar = $('.sb-table-toolbar', table);
        var search = toolbar ? toolbar.querySelector('[data-dt-search]').value.trim().toLowerCase() : '';
        var status = toolbar ? toolbar.querySelector('[data-dt-status]').value : '';
        qsa('tbody tr', table).forEach(function (tr) {
            var hay = tr.textContent.toLowerCase();
            var matchSearch = search === '' || hay.indexOf(search) !== -1;
            var matchStatus = status === '' || tr.getAttribute('data-status') === status;
            tr.classList.toggle('sb-row-hidden', !(matchSearch && matchStatus));
        });
        updateCount(table);
    }

    function visibleRows(table) {
        var list = qsa('tbody tr', table);
        return list.filter(function (tr) { return !tr.classList.contains('sb-row-hidden'); });
    }

    function updateCount(table) {
        var toolbar = $('.sb-table-toolbar', table);
        if (!toolbar) return;
        var total = qsa('tbody tr', table).length;
        var shown = visibleRows(table).length;
        var p = toolbar.querySelector('[data-dt-count]');
        if (!p) {
            p = document.createElement('p');
            p.setAttribute('data-dt-count', '');
            p.className = 'text-xs text-ink/50 my-0';
            toolbar.appendChild(p);
        }
        p.textContent = shown + ' / ' + total + ' affichés (page courante)';
    }

    function exportCsv(table) {
        var rows = [qsa('thead th', table).map(function (th) { return th.textContent.trim(); })];
        visibleRows(table).forEach(function (tr) {
            rows.push(qsa('td', tr).map(function (td) { return td.textContent.trim(); }));
        });
        var title = (table.getAttribute('data-title') || 'export') + '-' + window.location.pathname.split('/').filter(Boolean).pop().replace(/\W+/g, '-') || 'export';
        csv(rows, title);
    }

    /* Depot : les fétiches */
    function $(sel, root) { return qsa(sel, root)[0] || null; }

    document.addEventListener('DOMContentLoaded', function () {
        qsa('table[data-table]').forEach(function (table) {
            /* Chaque ligne : data-status extrait de la colonne "Statut" au besoin */
            if (!table.querySelector('tbody tr[data-status]')) {
                var statusCol = Array.prototype.find.call(qsa('thead th', table), function (th) {
                    return (th.textContent || '').toLowerCase().indexOf('statut') !== -1;
                });
                if (statusCol) {
                    var idx = qsa('thead th', table).indexOf(statusCol);
                    qsa('tbody tr', table).forEach(function (tr) {
                        var cells = qsa('td', tr);
                        if (cells[idx]) {
                            var txt = cells[idx].textContent.trim();
                            if (txt) tr.setAttribute('data-status', txt);
                        }
                    });
                }
            }
            enhance(table);
        });
    });

    window.SBTable = { refresh: function (root) { qsa('table[data-table]', root || document).forEach(function (t) { applyFilters(t); }); } };
})();