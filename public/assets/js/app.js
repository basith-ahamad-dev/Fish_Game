/* ==========================================================================
   DeepCatch — shared UI behaviour (loaded on every page)
   Exposes window.DC with api(), toast(), modal helpers and icon markup.
   ========================================================================== */
(() => {
    'use strict';

    const $ = (sel, root = document) => root.querySelector(sel);
    const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];
    const meta = (name) => document.querySelector(`meta[name="${name}"]`)?.content ?? '';
    const motionOn = () => document.documentElement.dataset.motion !== 'off'
        && !window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const ICONS = {
        check: '<path d="m5 12 5 5 9-10"/>',
        x: '<path d="M18 6 6 18M6 6l12 12"/>',
        sparkles: '<path d="M12 3v4M12 17v4M3 12h4M17 12h4M6 6l2 2M16 16l2 2M6 18l2-2M16 8l2-2"/>',
        medal: '<circle cx="12" cy="15" r="6"/><path d="m9 9.5-3-6.5h4l2 4 2-4h4l-3 6.5"/>',
        heart: '<path d="M12 20s-7.5-4.6-9.2-9.4C1.6 7 4 4 7.2 4c2 0 3.6 1.1 4.8 2.7C13.2 5.1 14.8 4 16.8 4 20 4 22.4 7 21.2 10.6 19.5 15.4 12 20 12 20Z"/>',
    };
    const icon = (name, cls = 'icon') =>
        `<svg class="${cls}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${ICONS[name] || ''}</svg>`;

    /* ---------- fetch wrapper: JSON + CSRF, errors carry status & message ---------- */
    async function api(url, data = null, { method = data ? 'POST' : 'GET' } = {}) {
        let response;
        try {
            response = await fetch(url, {
                method,
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'fetch',
                    'X-CSRF-Token': meta('csrf-token'),
                    ...(data ? { 'Content-Type': 'application/json' } : {}),
                },
                body: data ? JSON.stringify(data) : undefined,
            });
        } catch (networkError) {
            const err = new Error('You appear to be offline. Check your connection and try again.');
            err.status = 0;
            throw err;
        }

        let body = {};
        try { body = await response.json(); } catch { /* non-JSON error page */ }

        if (!response.ok) {
            const err = new Error(body.message || 'Something went wrong. Please try again.');
            err.status = response.status;
            err.data = body;
            if (response.status === 401) {
                window.location.href = `${meta('base-url').replace(/\/$/, '')}/login`;
            }
            throw err;
        }
        return body;
    }

    /* ---------- Toasts ---------- */
    function dismissToast(el) {
        if (!el || el.classList.contains('is-leaving')) return;
        el.classList.add('is-leaving');
        el.addEventListener('animationend', () => el.remove(), { once: true });
        setTimeout(() => el.remove(), 400);
    }

    function toast(message, type = 'info', { timeout = 4500, iconHtml = null } = {}) {
        const stack = $('#toast-stack');
        if (!stack) return;
        const el = document.createElement('div');
        el.className = `toast toast--${type}`;
        el.setAttribute('role', type === 'error' ? 'alert' : 'status');
        const iconName = { success: 'check', error: 'x', info: 'sparkles', gold: 'medal' }[type] || 'sparkles';
        el.innerHTML = `<span class="toast__icon">${iconHtml || icon(iconName)}</span><p class="toast__msg"></p>
            <button class="toast__close" type="button" aria-label="Dismiss">${icon('x')}</button>`;
        $('.toast__msg', el).textContent = message;
        stack.appendChild(el);
        if (timeout) setTimeout(() => dismissToast(el), timeout);
        return el;
    }

    document.addEventListener('click', (e) => {
        const close = e.target.closest('.toast__close');
        if (close) dismissToast(close.closest('.toast'));
    });
    $$('.toast[data-autodismiss]').forEach((el) => setTimeout(() => dismissToast(el), 5000));

    /* ---------- Modals (focus trap, Esc, backdrop) ---------- */
    let lastFocus = null;

    function openModal(id) {
        const modal = typeof id === 'string' ? document.getElementById(id) : id;
        if (!modal) return;
        lastFocus = document.activeElement;
        modal.hidden = false;
        modal.classList.remove('is-closing');
        document.body.style.overflow = 'hidden';
        const focusable = $$('button, a[href], input, select, [tabindex]:not([tabindex="-1"])', modal).filter((n) => !n.disabled);
        (focusable.find((n) => !n.matches('.modal__close')) || $('.modal__panel', modal))?.focus();
    }

    function closeModal(id) {
        const modal = typeof id === 'string' ? document.getElementById(id) : id;
        if (!modal || modal.hidden) return;
        modal.classList.add('is-closing');
        setTimeout(() => {
            modal.hidden = true;
            modal.classList.remove('is-closing');
            if (!$('.modal:not([hidden])')) document.body.style.overflow = '';
            lastFocus?.focus?.();
        }, motionOn() ? 180 : 0);
    }

    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-modal-close]')) closeModal(e.target.closest('.modal'));
    });
    document.addEventListener('keydown', (e) => {
        const modal = $('.modal:not([hidden])');
        if (!modal) return;
        if (e.key === 'Escape' && !modal.matches('.result-modal')) closeModal(modal);
        if (e.key === 'Tab') {
            const nodes = $$('button, a[href], input, select, textarea', modal).filter((n) => !n.disabled && n.offsetParent !== null);
            if (!nodes.length) return;
            const first = nodes[0], last = nodes[nodes.length - 1];
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        }
    });

    /** Promise-based confirm dialog using #confirm-modal. */
    function confirmDialog(message, { title = 'Are you sure?', confirmLabel = 'Confirm' } = {}) {
        const modal = $('#confirm-modal');
        if (!modal) return Promise.resolve(window.confirm(message));
        $('.modal__title', modal).textContent = title;
        $('.modal__body', modal).textContent = message;
        const ok = $('[data-modal-confirm]', modal);
        $('span', ok).textContent = confirmLabel;
        openModal(modal);
        return new Promise((resolve) => {
            const done = (value) => {
                ok.removeEventListener('click', onOk);
                modal.removeEventListener('click', onCancel);
                closeModal(modal);
                resolve(value);
            };
            const onOk = () => done(true);
            const onCancel = (e) => { if (e.target.closest('[data-modal-close]')) done(false); };
            ok.addEventListener('click', onOk);
            modal.addEventListener('click', onCancel);
        });
    }

    // Forms that need confirmation: <form data-confirm="…">
    document.addEventListener('submit', async (e) => {
        const form = e.target;
        if (form.dataset.confirm && !form.dataset.confirmed) {
            e.preventDefault();
            if (await confirmDialog(form.dataset.confirm)) {
                form.dataset.confirmed = '1';
                form.requestSubmit ? form.requestSubmit() : form.submit();
            }
            return;
        }
        if (!e.defaultPrevented) form.querySelector('button.btn[type="submit"]')?.classList.add('is-loading');
    });
    // Restore buttons when navigating back (bfcache)
    window.addEventListener('pageshow', () => $$('.btn.is-loading').forEach((b) => b.classList.remove('is-loading')));

    /* ---------- Dropdowns ---------- */
    function closeDropdowns(except = null) {
        $$('[data-dropdown]').forEach((dd) => {
            if (dd === except) return;
            $('.dropdown__trigger', dd).setAttribute('aria-expanded', 'false');
            $('.dropdown__menu', dd).hidden = true;
        });
    }
    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('.dropdown__trigger');
        if (trigger) {
            const dd = trigger.closest('[data-dropdown]');
            const menu = $('.dropdown__menu', dd);
            const open = menu.hidden;
            closeDropdowns(dd);
            menu.hidden = !open;
            trigger.setAttribute('aria-expanded', String(open));
            if (open) $('.dropdown__item', menu)?.focus();
            return;
        }
        if (!e.target.closest('.dropdown__menu')) closeDropdowns();
    });
    document.addEventListener('keydown', (e) => {
        const menu = e.target.closest?.('.dropdown__menu');
        if (e.key === 'Escape') {
            const open = $('.dropdown__menu:not([hidden])');
            if (open) { closeDropdowns(); open.closest('[data-dropdown]').querySelector('.dropdown__trigger').focus(); }
        }
        if (menu && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) {
            e.preventDefault();
            const items = $$('.dropdown__item', menu);
            const i = items.indexOf(document.activeElement);
            items[(i + (e.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length].focus();
        }
    });

    /* ---------- Mobile sidebar drawer ---------- */
    const sidebar = $('#sidebar');
    const scrim = $('.sidebar-scrim');
    const setSidebar = (open) => {
        if (!sidebar) return;
        sidebar.classList.toggle('is-open', open);
        if (scrim) scrim.hidden = !open;
        document.body.style.overflow = open ? 'hidden' : '';
        if (open) $('.side-link', sidebar)?.focus();
    };
    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-sidebar-open]')) setSidebar(true);
        if (e.target.closest('[data-sidebar-close]')) setSidebar(false);
    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && sidebar?.classList.contains('is-open')) setSidebar(false); });

    /* ---------- Navbar shadow on scroll ---------- */
    const navbar = $('[data-navbar]');
    if (navbar) {
        const onScroll = () => navbar.classList.toggle('is-scrolled', window.scrollY > 8);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }

    /* ---------- Count-up numbers & progress bars ---------- */
    const formatNumber = (n) => Math.round(n).toLocaleString('en-US');

    function countUp(el, to, duration = 1100) {
        const from = 0;
        if (!motionOn() || to === 0) { el.textContent = formatNumber(to); return; }
        const start = performance.now();
        const step = (now) => {
            const t = Math.min(1, (now - start) / duration);
            const eased = 1 - Math.pow(1 - t, 3);
            el.textContent = formatNumber(from + (to - from) * eased);
            if (t < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
    }

    const io = 'IntersectionObserver' in window ? new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            const el = entry.target;
            io.unobserve(el);
            if (el.dataset.countTo !== undefined) countUp(el, parseFloat(el.dataset.countTo) || 0);
            if (el.classList.contains('progress__fill')) {
                requestAnimationFrame(() => { el.style.setProperty('--progress', el.dataset.target); });
            }
        });
    }, { threshold: 0.2 }) : null;

    if (io && motionOn()) {
        $$('[data-count-to]').forEach((el) => io.observe(el));
        $$('.progress__fill').forEach((el) => {
            el.dataset.target = el.style.getPropertyValue('--progress') || '0%';
            el.style.setProperty('--progress', '0%');
            io.observe(el);
        });
    }

    /* ---------- Misc ---------- */
    document.addEventListener('change', (e) => {
        const form = e.target.closest('form[data-auto-submit]');
        if (form && e.target.matches('select')) form.submit();
    });
    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-history-back]')) history.back();
    });

    // Landing page: refresh live counters every 30s
    const live = $('[data-live-stats]');
    if (live) {
        setInterval(async () => {
            if (document.hidden) return;
            try {
                const stats = await api(`${meta('base-url').replace(/\/$/, '')}/api/stats`);
                Object.entries(stats).forEach(([key, value]) => {
                    const el = $(`[data-stat="${key}"]`, live);
                    if (el && el.textContent !== formatNumber(value)) {
                        el.textContent = formatNumber(value);
                        el.classList.remove('is-updated');
                        void el.offsetWidth;
                        el.classList.add('is-updated');
                    }
                });
            } catch { /* keep the last known numbers */ }
        }, 30000);
    }

    window.DC = { $, $$, api, toast, openModal, closeModal, confirm: confirmDialog, countUp, formatNumber, motionOn, url: (p) => `${meta('base-url').replace(/\/$/, '')}/${p.replace(/^\//, '')}` };
})();
