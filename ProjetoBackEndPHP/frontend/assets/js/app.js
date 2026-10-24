/* Eventos Escolares — melhorias progressivas (o site funciona sem JS, exceto os atalhos abaixo). */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        /* ---------- Menu mobile ---------- */
        var toggle = document.querySelector('[data-nav-toggle]');
        var menu = document.getElementById('menu');
        if (toggle && menu) {
            toggle.addEventListener('click', function () {
                var open = menu.classList.toggle('is-open');
                toggle.setAttribute('aria-expanded', String(open));
            });
        }

        /* ---------- Toasts: fechar e sumir sozinho ---------- */
        function dismiss(toast) {
            toast.classList.add('is-leaving');
            setTimeout(function () { toast.remove(); }, 320);
        }
        document.querySelectorAll('[data-toast]').forEach(function (toast) {
            var btn = toast.querySelector('[data-toast-close]');
            if (btn) { btn.addEventListener('click', function () { dismiss(toast); }); }
            if (!toast.classList.contains('toast--error')) {
                setTimeout(function () { if (toast.isConnected) { dismiss(toast); } }, 6000);
            }
        });

        /* ---------- Busca instantânea em listas ---------- */
        var input = document.querySelector('[data-filter-input]');
        var list = document.querySelector('[data-filter-list]');
        if (input && list) {
            var empty = document.querySelector('[data-filter-empty]');
            var items = Array.prototype.slice.call(list.querySelectorAll('[data-filter-item]'));
            var normalize = function (s) {
                return (s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
            };
            var index = items.map(function (el) { return normalize(el.getAttribute('data-search')); });
            input.addEventListener('input', function () {
                var q = normalize(input.value.trim());
                var visible = 0;
                items.forEach(function (el, i) {
                    var match = q === '' || index[i].indexOf(q) !== -1;
                    el.hidden = !match;
                    if (match) { visible++; }
                });
                if (empty) { empty.hidden = visible !== 0; }
            });
        }

        /* ---------- Confirmação antes de ações destrutivas ---------- */
        var dialog = document.getElementById('confirm-dialog');
        var pendingForm = null;
        document.addEventListener('click', function (ev) {
            var btn = ev.target.closest('[data-confirm]');
            if (!btn) { return; }
            ev.preventDefault();
            var message = btn.getAttribute('data-confirm');
            var form = btn.form;
            if (!dialog || typeof dialog.showModal !== 'function') {
                if (window.confirm(message) && form) { form.submit(); }
                return;
            }
            pendingForm = form;
            dialog.querySelector('[data-dialog-text]').textContent = message;
            dialog.showModal();
        });
        if (dialog) {
            dialog.addEventListener('close', function () {
                if (dialog.returnValue === 'ok' && pendingForm) { pendingForm.submit(); }
                pendingForm = null;
                dialog.returnValue = '';
            });
        }

        /* ---------- Evita duplo clique em envios ---------- */
        document.querySelectorAll('form').forEach(function (form) {
            form.addEventListener('submit', function (ev) {
                var submitter = ev.submitter;
                if (!submitter || ev.defaultPrevented) { return; }
                setTimeout(function () { submitter.classList.add('is-loading'); }, 0);
            });
        });
        // Ao voltar pelo histórico (bfcache), remove o estado de carregamento
        window.addEventListener('pageshow', function () {
            document.querySelectorAll('.is-loading').forEach(function (b) { b.classList.remove('is-loading'); });
        });

        /* ---------- Mostrar/ocultar senha ---------- */
        document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var field = btn.parentElement.querySelector('input');
                var show = field.type === 'password';
                field.type = show ? 'text' : 'password';
                btn.setAttribute('aria-label', show ? 'Ocultar senha' : 'Mostrar senha');
            });
        });

        /* ---------- Cadastro: RA só para alunos ---------- */
        var radios = document.querySelectorAll('[data-role-radio]');
        var raField = document.querySelector('[data-only-role="aluno"]');
        if (radios.length && raField) {
            var sync = function () {
                var checked = document.querySelector('[data-role-radio]:checked');
                var isAluno = !checked || checked.value === 'aluno';
                raField.hidden = !isAluno;
                var ra = raField.querySelector('input');
                if (ra) { ra.required = isAluno; }
            };
            radios.forEach(function (r) { r.addEventListener('change', sync); });
            sync();
        }
    });
})();
