/* ==========================================================================
   Lightweight SVG charts for dashboard, statistics and admin pages.
   <div data-chart="bars|line" data-series='[{"label","title","value"}]' data-unit="games">
   Single series → one validated hue (--c-chart), no legend; hover tooltips on every mark.
   ========================================================================== */
(() => {
    'use strict';
    const { $$, formatNumber } = window.DC;
    const NS = 'http://www.w3.org/2000/svg';
    const PAD = { top: 16, right: 12, bottom: 26, left: 40 };

    const el = (name, attrs = {}, parent = null) => {
        const node = document.createElementNS(NS, name);
        Object.entries(attrs).forEach(([k, v]) => node.setAttribute(k, v));
        if (parent) parent.appendChild(node);
        return node;
    };

    /** Rounded "nice" axis maximum and tick step. */
    function niceScale(max, ticks = 4) {
        if (max <= 0) return { max: ticks, step: 1 };
        const rough = max / ticks;
        const pow = Math.pow(10, Math.floor(Math.log10(rough)));
        const step = [1, 2, 2.5, 5, 10].map((m) => m * pow).find((s) => s >= rough);
        return { max: Math.ceil(max / step) * step, step };
    }

    function compact(n) {
        return n >= 10000 ? `${Math.round(n / 1000)}k` : n >= 1000 ? `${(n / 1000).toFixed(1)}k` : String(n);
    }

    function frame(container, data) {
        container.querySelector('svg')?.remove();
        const width = container.clientWidth;
        const height = container.clientHeight;
        const svg = el('svg', { viewBox: `0 0 ${width} ${height}`, preserveAspectRatio: 'none' });
        container.prepend(svg);

        const values = data.map((d) => d.value);
        const scale = niceScale(Math.max(...values, 0));
        const plotW = width - PAD.left - PAD.right;
        const plotH = height - PAD.top - PAD.bottom;
        const y = (v) => PAD.top + plotH - (v / scale.max) * plotH;

        // Recessive grid + y labels
        const grid = el('g', { class: 'chart__grid' }, svg);
        const axis = el('g', { class: 'chart__axis' }, svg);
        for (let v = 0; v <= scale.max + 1e-9; v += scale.step) {
            el('line', { x1: PAD.left, x2: width - PAD.right, y1: y(v), y2: y(v) }, grid);
            const t = el('text', { x: PAD.left - 8, y: y(v) + 4, 'text-anchor': 'end' }, axis);
            t.textContent = compact(Math.round(v));
        }

        // x labels, thinned so they never collide
        const slot = plotW / data.length;
        const every = Math.max(1, Math.ceil(36 / slot));
        data.forEach((d, i) => {
            if (i % every !== 0 && i !== data.length - 1) return;
            const t = el('text', { x: PAD.left + slot * (i + 0.5), y: height - 6, 'text-anchor': 'middle' }, axis);
            t.textContent = d.label;
        });

        return { svg, width, height, plotW, plotH, slot, y };
    }

    function tooltip(container) {
        let tip = container.querySelector('.chart__tooltip');
        if (!tip) {
            tip = document.createElement('div');
            tip.className = 'chart__tooltip';
            tip.style.opacity = '0';
            container.appendChild(tip);
        }
        return {
            show(x, y, d, unit) {
                tip.innerHTML = '';
                const strong = document.createElement('strong');
                strong.textContent = `${formatNumber(d.value)} ${unit}`;
                tip.append(strong, document.createTextNode(d.title || d.label));
                tip.style.left = `${x}px`;
                tip.style.top = `${y}px`;
                tip.style.opacity = '1';
            },
            hide() { tip.style.opacity = '0'; },
        };
    }

    function renderBars(container, data, unit) {
        const f = frame(container, data);
        const tip = tooltip(container);
        const g = el('g', { class: 'chart__bars' }, f.svg);
        const barW = Math.max(4, Math.min(36, f.slot - 2 - f.slot * 0.3)); // ≥2px gap between bars
        const r = Math.min(4, barW / 2);
        const base = f.y(0);

        data.forEach((d, i) => {
            const cx = PAD.left + f.slot * (i + 0.5);
            const x = cx - barW / 2;
            const top = d.value > 0 ? Math.min(f.y(d.value), base - 3) : base - 2;
            const h = base - top;
            // Rounded top corners only; anchored flat to the baseline
            const path = `M${x},${base} L${x},${top + r} Q${x},${top} ${x + r},${top} L${x + barW - r},${top} Q${x + barW},${top} ${x + barW},${top + r} L${x + barW},${base} Z`;
            const bar = el('path', { d: path, class: `chart__bar ${d.value === 0 ? 'chart__bar--zero' : ''}` }, g);

            // Hit target: the full column, larger than the mark
            const hit = el('rect', { x: cx - f.slot / 2, y: PAD.top, width: f.slot, height: f.plotH, fill: 'transparent' }, g);
            hit.addEventListener('mouseenter', () => { bar.classList.add('is-active'); tip.show(cx, top, d, unit); });
            hit.addEventListener('mouseleave', () => { bar.classList.remove('is-active'); tip.hide(); });
        });
    }

    function renderLine(container, data, unit) {
        const f = frame(container, data);
        const tip = tooltip(container);
        const pts = data.map((d, i) => ({ x: PAD.left + f.slot * (i + 0.5), y: f.y(d.value), d }));
        const base = f.y(0);

        const defs = el('defs', {}, f.svg);
        const grad = el('linearGradient', { id: 'chart-area', x1: 0, y1: 0, x2: 0, y2: 1 }, defs);
        el('stop', { offset: '0%', style: 'stop-color: var(--c-chart); stop-opacity: .28' }, grad);
        el('stop', { offset: '100%', style: 'stop-color: var(--c-chart); stop-opacity: 0' }, grad);

        const line = pts.map((p, i) => `${i ? 'L' : 'M'}${p.x},${p.y}`).join(' ');
        el('path', { d: `${line} L${pts[pts.length - 1].x},${base} L${pts[0].x},${base} Z`, class: 'chart__area' }, f.svg);
        const path = el('path', { d: line, class: 'chart__line' }, f.svg);

        if (window.DC.motionOn()) {
            const len = path.getTotalLength();
            path.style.strokeDasharray = len;
            path.style.strokeDashoffset = len;
            path.getBoundingClientRect();
            path.style.transition = 'stroke-dashoffset 1.2s cubic-bezier(.2,.8,.2,1)';
            path.style.strokeDashoffset = '0';
        }

        // Direct label on the latest point and the best point only
        const best = pts.reduce((a, b) => (b.d.value > a.d.value ? b : a));
        [best, pts[pts.length - 1]].forEach((p, idx, arr) => {
            if (idx === 1 && p === arr[0]) return;
            el('circle', { cx: p.x, cy: p.y, r: 4, class: 'chart__dot' }, f.svg);
            const t = el('text', { x: p.x, y: p.y - 10, 'text-anchor': 'middle', class: 'chart__label' }, f.svg);
            t.textContent = `${idx === 0 ? 'Best ' : ''}${formatNumber(p.d.value)}`;
        });

        // Crosshair + nearest-point tooltip
        const cross = el('line', { y1: PAD.top, y2: base, class: 'chart__crosshair', opacity: 0 }, f.svg);
        const dot = el('circle', { r: 5, class: 'chart__dot', opacity: 0 }, f.svg);
        const overlay = el('rect', { x: PAD.left, y: PAD.top, width: f.plotW, height: f.plotH, fill: 'transparent' }, f.svg);
        overlay.addEventListener('mousemove', (e) => {
            const box = f.svg.getBoundingClientRect();
            const mx = (e.clientX - box.left) * (f.width / box.width);
            const i = Math.max(0, Math.min(pts.length - 1, Math.floor((mx - PAD.left) / f.slot)));
            const p = pts[i];
            cross.setAttribute('x1', p.x); cross.setAttribute('x2', p.x); cross.setAttribute('opacity', 1);
            dot.setAttribute('cx', p.x); dot.setAttribute('cy', p.y); dot.setAttribute('opacity', 1);
            tip.show(p.x, p.y, p.d, unit);
        });
        overlay.addEventListener('mouseleave', () => {
            cross.setAttribute('opacity', 0); dot.setAttribute('opacity', 0); tip.hide();
        });
    }

    function render(container) {
        let data;
        try { data = JSON.parse(container.dataset.series || '[]'); } catch { data = []; }
        const width = container.clientWidth;
        if (!data.length || width === 0 || Number(container.dataset.width) === width) return;
        container.dataset.width = String(width);
        const unit = container.dataset.unit || '';
        (container.dataset.chart === 'line' ? renderLine : renderBars)(container, data, unit);
    }

    const charts = $$('[data-chart]');
    charts.forEach(render);

    // Re-render on resize (debounced) so the SVG stays crisp at every width
    if ('ResizeObserver' in window) {
        let timer;
        const ro = new ResizeObserver(() => {
            clearTimeout(timer);
            timer = setTimeout(() => charts.forEach(render), 150);
        });
        charts.forEach((c) => ro.observe(c));
    }
})();
