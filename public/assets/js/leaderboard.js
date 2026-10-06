/* ==========================================================================
   Leaderboard tabs without a page reload (links still work without JS).
   ========================================================================== */
(() => {
    'use strict';
    const { $, $$, api, toast } = window.DC;

    const tabs = $('[data-leaderboard-tabs]');
    const board = $('[data-leaderboard]');
    if (!tabs || !board) return;

    const cache = new Map();
    let controllerId = 0;

    function setActive(tab) {
        $$('.tabs__tab', tabs).forEach((el) => {
            const active = el.dataset.tab === tab;
            el.classList.toggle('is-active', active);
            el.setAttribute('aria-selected', String(active));
        });
    }

    async function load(tab, push = true) {
        setActive(tab);
        const id = ++controllerId;
        board.classList.add('is-loading');
        try {
            const data = cache.get(tab) ?? await api(`${tabs.dataset.endpoint}?tab=${encodeURIComponent(tab)}`);
            cache.set(tab, data);
            if (id !== controllerId) return; // a newer tab was clicked meanwhile
            board.innerHTML = data.html;
            if (push) history.pushState({ tab }, '', `?tab=${tab}`);
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            if (id === controllerId) board.classList.remove('is-loading');
        }
    }

    tabs.addEventListener('click', (e) => {
        const link = e.target.closest('[data-tab]');
        if (!link || e.metaKey || e.ctrlKey) return;
        e.preventDefault();
        if (!link.classList.contains('is-active')) load(link.dataset.tab);
    });

    // Arrow keys move between tabs
    tabs.addEventListener('keydown', (e) => {
        if (!['ArrowLeft', 'ArrowRight'].includes(e.key)) return;
        const items = $$('[data-tab]', tabs);
        const i = items.indexOf(document.activeElement);
        if (i < 0) return;
        const next = items[(i + (e.key === 'ArrowRight' ? 1 : -1) + items.length) % items.length];
        next.focus();
        load(next.dataset.tab);
    });

    window.addEventListener('popstate', (e) => {
        const tab = e.state?.tab ?? new URLSearchParams(location.search).get('tab') ?? 'global';
        load(tab, false);
    });
})();
