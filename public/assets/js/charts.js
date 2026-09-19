/* Back-office SeneBridge — graphiques SVG légers (aucune dépendance).
 * API : SBCharts.bars(elm, data), SBCharts.donut(elm, data), SBCharts.spark(el, values).
 * Auto-montage : <div data-chart="bars|donut|sparkline" data-chart-json="selecteur">
 * Plus simple : payload JSON directement dans un <script type="application/json"> fils. */
(function () {
    'use strict';

    var NS = 'http://www.w3.org/2000/svg';

    function el(tag, attrs, text) {
        var n = document.createElementNS(NS, tag);
        if (attrs) Object.keys(attrs).forEach(function (k) {
            if (k === 'class' || k === 'xlink') n.setAttributeNS(null, k, attrs[k]);
            else if (k.slice(0, 5) === 'data-') n.setAttributeNS(null, k, attrs[k]);
            else if (k === 'style') n.setAttribute('style', attrs[k]);
            else n.setAttribute(k, attrs[k]);
        });
        if (text != null) n.textContent = text;
        return n;
    }

    function fmt(n) {
        if (typeof n !== 'number') return String(n == null ? '' : n);
        if (Math.abs(n) >= 1000000) return (n / 1000000).toFixed(1).replace('.', ',') + ' M';
        if (Math.abs(n) >= 1000) return (n / 1000).toFixed(0).replace('.', ',') + ' k';
        return String(Math.round(n));
    }

    function bars(root, data) {
        root.innerHTML = '';
        var W = root.clientWidth || 480;
        var H = 180;
        var padL = 0, padR = 4, padT = 10, padB = 24;
        var max = Math.max(1, Math.max.apply(null, data.map(function (d) { return Number(d.value) || 0; })));
        var bw = (W - padL - padR) / data.length;
        var svg = el('svg', { viewBox: '0 0 ' + W + ' ' + H, width: '100%', height: H, 'aria-hidden': 'true' });

        /* Grille horizontale */
        for (var i = 0; i <= 4; i++) {
            var y = padT + (H - padT - padB) * (i / 4);
            svg.appendChild(el('line', { x1: padL, y1: y, x2: W - padR, y2: y, stroke: 'rgba(23,34,31,.08)', 'stroke-width': 1 }));
        }

        var innerW = W - padL - padR, innerH = H - padT - padB;
        data.forEach(function (d, idx) {
            var v = Math.max(0, Number(d.value) || 0);
            var h = Math.max(2, v / max * innerH);
            var x = padL + bw * idx + bw * 0.18;
            var w = bw * 0.64;
            var y = padT + innerH - h;
            var bar = el('rect', {
                x: x, y: y, width: w, height: h, rx: Math.min(5, w / 2),
                fill: d.color || 'rgba(0,91,79,.78)', 'data-v': String(v)
            });
            bar.addEventListener('mouseenter', function () { showTip(bar, d.label + ' : ' + (d.display != null ? d.display : fmt(v))); });
            bar.addEventListener('mouseleave', hideTip);
            svg.appendChild(bar);
            var lbl = el('text', { x: x + w / 2, y: H - 7, 'text-anchor': 'middle', 'font-size': 10, fill: 'rgba(23,34,31,.55)' }, d.label);
            svg.appendChild(lbl);
        });

        root.appendChild(svg);
    }

    function spark(root, values, color) {
        if (!values.length) { root.textContent = '—'; return; }
        root.innerHTML = '';
        var W = root.clientWidth || 120, H = root.clientHeight || 36;
        var max = Math.max.apply(null, values), min = Math.min.apply(null, values);
        var span = (max - min) || 1;
        var step = W / Math.max(1, values.length - 1);
        var pts = values.map(function (v, i) { return (i * step).toFixed(1) + ',' + (H - 3 - (v - min) / span * (H - 6)).toFixed(1); }).join(' ');
        var svg = el('svg', { viewBox: '0 0 ' + W + ' ' + H, width: '100%', height: H, 'aria-hidden': 'true' });
        svg.appendChild(el('polyline', {
            points: pts, fill: 'none', stroke: color || 'rgba(0,91,79,.9)', 'stroke-width': 2,
            'stroke-linecap': 'round', 'stroke-linejoin': 'round'
        }));
        root.appendChild(svg);
    }

    function donut(root, data) {
        root.innerHTML = '';
        var total = data.reduce(function (a, d) { return a + (Number(d.value) || 0); }, 0) || 1;
        var W = root.clientWidth || 200, H = root.clientHeight || 160;
        var cx = W / 2, cy = H / 2, r = Math.min(W, H) / 2 - 8, t = 16;
        var svg = el('svg', { viewBox: '0 0 ' + W + ' ' + H, width: '100%', height: H, 'aria-hidden': 'true' });
        svg.appendChild(el('circle', { cx: cx, cy: cy, r: r, fill: 'none', stroke: 'rgba(23,34,31,.06)', 'stroke-width': t }));
        var offset = -Math.PI / 2;
        var R = 2 * Math.PI * r;
        data.forEach(function (d) {
            var frac = (Number(d.value) || 0) / total;
            if (frac <= 0) return;
            var len = frac * R;
            var el2 = el('circle', {
                cx: cx, cy: cy, r: r, fill: 'none',
                stroke: d.color || 'rgba(0,91,79,.8)', 'stroke-width': t,
                'stroke-dasharray': len + ' ' + (R - len),
                'stroke-dashoffset': -offset * R / (2 * Math.PI),
                'transform': 'rotate(-90 ' + cx + ' ' + cy + ')'
            });
            el2.addEventListener('mouseenter', function () { showTip(el2, d.label + ' : ' + d.value); });
            el2.addEventListener('mouseleave', hideTip);
            svg.appendChild(el2);
            offset += frac * 2 * Math.PI;
        });
        svg.appendChild(el('text', { x: cx, y: cy - 4, 'text-anchor': 'middle', 'font-size': 18, 'font-weight': 800, fill: '#005B4F' }, String(total)));
        svg.appendChild(el('text', { x: cx, y: cy + 14, 'text-anchor': 'middle', 'font-size': 10, fill: 'rgba(23,34,31,.55)' }, 'total'));
        root.appendChild(svg);
    }

    var tip = null;
    function showTip(anchor, html) {
        hideTip();
        tip = document.createElement('div');
        tip.className = 'sb-chart-tip';
        tip.textContent = html;
        document.body.appendChild(tip);
        positionTip(anchor);
    }
    function positionTip(anchor) {
        if (!tip) return;
        var r = anchor.getBoundingClientRect();
        var w = tip.offsetWidth;
        tip.style.left = Math.max(4, Math.min(window.innerWidth - w - 8, r.left + r.width / 2 - w / 2)) + 'px';
        tip.style.top = Math.max(4, r.top - tip.offsetHeight - 8) + 'px';
    }
    function hideTip() { if (tip) { tip.remove(); tip = null; } }
    window.addEventListener('scroll', hideTip, { passive: true });

    function mount() {
        document.querySelectorAll('[data-chart]').forEach(function (elm) {
            var kind = elm.getAttribute('data-chart');
            var payload = null;
            var script = elm.querySelector('script[type="application/json"]');
            if (script) {
                try { payload = JSON.parse(script.textContent || 'null'); } catch (e) { payload = null; }
            }
            if (payload == null && elm.getAttribute('data-chart-json')) {
                try { payload = JSON.parse(elm.getAttribute('data-chart-json')); } catch (e) { payload = null; }
            }
            if (!payload) return;
            if (kind === 'bars') bars(elm, payload);
            else if (kind === 'donut') donut(elm, payload);
            else if (kind === 'sparkline') spark(elm, payload.values || payload, payload.color);
        });
    }

    document.addEventListener('DOMContentLoaded', mount);

    window.SBCharts = { bars: bars, donut: donut, spark: spark, mount: mount };
})();