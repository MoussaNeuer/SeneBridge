/* Back-office SeneBridge — interactions globales (vanilla, aucun inline). */
(function () {
    'use strict';

    var $ = function (sel, root) { return (root || document).querySelector(sel); };
    var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };

    /* ---------- Toasts ---------- */
    var toastStack = $('#sb-toast-stack');

    function toast(message, type) {
        type = type || 'success';
        if (!toastStack) return;
        var el = document.createElement('div');
        el.className = 'sb-toast pointer-events-auto flex items-start justify-between gap-3 px-4 py-3 rounded-xl text-sm font-medium border shadow-lg ' +
            (type === 'success' ? 'bg-emerald-50 border-emerald-500/30 text-emerald-brand' : type === 'error' ? 'bg-red-50 border-red-500/30 text-red-700' : 'bg-white border-brand/15 text-ink');
        el.setAttribute('role', 'alert');
        el.innerHTML = '<span></span><button type="button" class="shrink-0 opacity-60 hover:opacity-100" aria-label="Fermer"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6L6 18"/></svg></button>';
        el.querySelector('span').textContent = message;
        el.querySelector('button').addEventListener('click', function () { dismiss(el); });
        toastStack.appendChild(el);
        setTimeout(function () { dismiss(el); }, 5000);
    }

    function dismiss(el) {
        if (!el || !el.parentNode) return;
        el.classList.add('sb-toast-leave');
        setTimeout(function () { if (el.parentNode) el.parentNode.removeChild(el); }, 200);
    }

    document.addEventListener('click', function (e) {
        var t = e.target.closest('[data-dismiss-toast]');
        if (t) dismiss(t.closest('[data-toast]'));
    });

    /* Auto-dismiss des toasts de flash existants */
    $$('#sb-toast-stack .sb-toast, [data-toast]').forEach(function (el, i) {
        if (!el.classList.contains('sb-toast-leave')) {
            setTimeout(function () { dismiss(el); }, 5000 + i * 300);
        }
    });

    /* ---------- Modale de confirmation ---------- */
    var modalRoot = null;

    function confirmModal(message, onConfirm, title) {
        if (!modalRoot) return;
        title = title || 'Confirmer l\u2019action';
        modalRoot.querySelector('.sb-modal-title').textContent = title;
        modalRoot.querySelector('.sb-modal-text').textContent = message;
        modalRoot.querySelector('.sb-modal-cancel').focus();
        modalRoot.classList.remove('sb-modal-hidden');
        document.body.classList.add('overflow-hidden');
        modalRoot._onConfirm = onConfirm;
    }

    function closeModal() {
        if (!modalRoot) return;
        modalRoot.classList.add('sb-modal-hidden');
        document.body.classList.remove('overflow-hidden');
        modalRoot._onConfirm = null;
    }

    function ensureModal() {
        if (modalRoot || !document.body) return;
        modalRoot = document.createElement('div');
        modalRoot.className = 'sb-modal sb-modal-hidden';
        modalRoot.innerHTML =
            '<div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">' +
            '<div class="absolute inset-0 bg-black/50" data-sb-modal-close></div>' +
            '<div class="sb-modal-card relative w-full max-w-md bg-white dark:bg-ink rounded-2xl border border-brand/10 shadow-2xl p-6">' +
            '<div class="w-11 h-11 rounded-full bg-red-50 grid place-items-center mb-4"><svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg></div>' +
            '<h3 class="sb-modal-title text-lg font-extrabold"></h3>' +
            '<p class="sb-modal-text mt-1 text-sm text-ink/70"></p>' +
            '<div class="mt-5 flex justify-end gap-3">' +
            '<button type="button" class="sb-modal-cancel px-4 py-2 rounded-lg border border-brand/15 text-sm font-semibold hover:bg-cream transition">Annuler</button>' +
            '<button type="button" class="sb-modal-ok px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-semibold hover:bg-red-700 transition">Continuer</button>' +
            '</div></div></div>';
        document.body.appendChild(modalRoot);
        modalRoot.addEventListener('click', function (e) {
            if (e.target.closest('[data-sb-modal-close]')) closeModal();
            if (e.target.closest('.sb-modal-cancel')) closeModal();
            if (e.target.closest('.sb-modal-ok')) {
                var fn = modalRoot._onConfirm;
                closeModal();
                if (typeof fn === 'function') { fn(); }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        ensureModal();

        /* Confirmation : boutons/liens data-confirm */
        document.addEventListener('click', function (e) {
            var t = e.target.closest('[data-confirm]');
            if (!t) return;
            e.preventDefault();
            t.classList.add('sb-confirm-pending');
            confirmModal(t.getAttribute('data-confirm') || 'Confirmer cette action\u2009?', function () {
                t.classList.remove('sb-confirm-pending');
                if (t.tagName === 'FORM') { t.submit(); return; }
                if (t.getAttribute('href')) { window.location.href = t.getAttribute('href'); return; }
                var wasPending = t;
                var parentForm = t.closest('form');
                if (parentForm) { parentForm.submit(); }
            });
        });

        /* Formulaires asynchrones data-async (fetch JSON + CSRF + toast) */
        document.addEventListener('submit', function (e) {
            var form = e.target.closest('form[data-async]');
            if (!form) return;
            e.preventDefault();
            var body = new FormData(form);
            fetch(form.action, {
                method: form.method || 'POST',
                body: body,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': metaCsrf()
                }
            }).then(function (res) {
                return res.json().catch(function () { return { success: false, message: 'Réponse serveur illisible.' }; }).then(function (data) {
                    return { res: res, data: data };
                });
            }).then(function (out) {
                var ok = out.res.ok && out.data && out.data.success !== false;
                var body = (out.data && out.data.data) || {};
                var msg = (out.data && out.data.message) || body.message || (ok ? 'Action effectuée.' : 'Action refusée.');
                toast(msg, ok ? 'success' : 'error');
                refreshCounters();
                var target = form.getAttribute('data-callback');
                if (target && window[target] && typeof window[target] === 'function') { window[target](form, out); }
                if (body.redirect) { setTimeout(function () { window.location.href = body.redirect; }, 350); }
            }).catch(function () {
                toast('Erreur réseau : action non effectuée.', 'error');
            });
        });

        /* Changement de statut instantané depuis un select du Kanban */
        document.addEventListener('change', function (e) {
            var sel = e.target.closest('select[data-status-root]');
            if (!sel) return;
            var form = sel.closest('form');
            if (form) form.requestSubmit();
        });
    });

    function metaCsrf() {
        var m = $('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }

    /* ---------- Barre de progression de scroll ---------- */
    var progressBar = $('#sb-progress-bar');
    function updateProgress() {
        if (!progressBar) return;
        var h = document.documentElement;
        var scrolled = h.scrollTop || document.body.scrollTop;
        var max = h.scrollHeight - h.clientHeight;
        progressBar.style.width = (max > 0 ? Math.min(100, scrolled / max * 100) : 0) + '%';
    }
    window.addEventListener('scroll', updateProgress, { passive: true });
    document.addEventListener('DOMContentLoaded', updateProgress);

    /* ---------- Drawer mobile + dropdown profil ---------- */
    document.addEventListener('DOMContentLoaded', function () {
        var sidebar = $('#sb-sidebar');
        var overlay = $('#sb-drawer-overlay');
        function openDrawer() {
            if (!sidebar) return;
            sidebar.classList.remove('-translate-x-full');
            if (overlay) overlay.classList.remove('hidden');
        }
        function closeDrawer() {
            if (!sidebar) return;
            sidebar.classList.add('-translate-x-full');
            if (overlay) overlay.classList.add('hidden');
        }
        document.body.addEventListener('click', function (e) {
            if (e.target.closest('[data-open-drawer]')) { openDrawer(); }
            if (e.target.closest('[data-close-drawer]')) { closeDrawer(); }
        });

        var profileBtn = $('#sb-profile-btn');
        var profileMenu = $('#sb-profile-menu');
        if (profileBtn && profileMenu) {
            profileBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                profileMenu.classList.toggle('hidden');
            });
            document.addEventListener('click', function () {
                if (!profileMenu.classList.contains('hidden')) profileMenu.classList.add('hidden');
            });
        }
    });

    /* ---------- Thème sombre ---------- */
    (function theme() {
        var KEY = 'sb_theme';
        var stored = null;
        try { stored = localStorage.getItem(KEY); } catch (e) {}
        var btn = document.getElementById('sb-theme-toggle');
        function apply(dark) {
            document.documentElement.classList.toggle('dark', dark);
            try { localStorage.setItem(KEY, dark ? 'dark' : 'light'); } catch (e) {}
        }
        apply(stored === 'dark');
        if (btn) {
            btn.addEventListener('click', function () {
                apply(!document.documentElement.classList.contains('dark'));
            });
        }
    })();

    /* ---------- Recherche dans la navigation ---------- */
    document.addEventListener('DOMContentLoaded', function () {
        var input = $('#sb-nav-search');
        if (!input) return;
        input.addEventListener('input', function () {
            var q = mbLower(input.value.trim());
            $$('#sb-nav .sb-section').forEach(function (section) {
                var any = false;
                $$('.sb-nav-link', section).forEach(function (link) {
                    var hay = mbLower(link.getAttribute('data-search') || '');
                    var hit = q === '' || hay.indexOf(q) !== -1;
                    link.classList.toggle('sb-nav-hidden', !hit);
                    if (hit) any = true;
                });
                section.classList.toggle('sb-nav-hidden', !any && q !== '');
            });
        });
    });

    function mbLower(s) { return s.toLowerCase(); }

    /* ---------- Compteurs temps réel ---------- */
    var countersEndpoint = null;
    (function countersRoute() {
        var m = $('meta[name="csrf-token"]');
        countersEndpoint = (m && m.getAttribute('data-counters')) || null;
    })();

    function refreshCounters() {
        if (!countersEndpoint) return;
        fetch(countersEndpoint, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (out) {
                var data = out.data || out;
                $$('[data-counter]').forEach(function (el) {
                    var route = el.getAttribute('data-counter');
                    var value = 0;
                    Object.keys(data).forEach(function (k) {
                        if (k === route || k.indexOf(route) === 0 || route.indexOf(k) === 0) value += (parseInt(data[k], 10) || 0);
                    });
                    el.textContent = value;
                    el.classList.toggle('sb-badge-zero', value === 0);
                });
            })
            .catch(function () { /* le pont n'existe pas encore : silencieux */ });
    }

    var active = false;
    window.addEventListener('focus', startPolling);
    startPolling();
    function startPolling() {
        if (window._sbPolling) return;
        window._sbPolling = setInterval(function () { if (document.hasFocus()) refreshCounters(); }, 60000);
        refreshCounters();
    }

    window.SBAdmin = {
        toast: toast,
        confirm: confirmModal,
        closeModal: closeModal,
        refreshCounters: refreshCounters,
        csrf: metaCsrf
    };
})();