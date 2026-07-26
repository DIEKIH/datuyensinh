$(document).ready(function () {
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    const state = {
        daily: [],
        traffic: { today: 0, total: 0 },
        chat: { sessions: 0, totalMessages: 0, userMessages: 0, botMessages: 0 },
        posts: 0,
        hoverIndex: -1
    };

    loadStats();
    loadDashboardStatsAndCharts();
    loadRecentPosts();
    loadRecentSessions();

    $(window).on('resize', debounce(function () {
        redrawAllCharts();
    }, 180));

    $('#visitorChart').on('mousemove', function (e) {
        if (!state.daily.length) return;
        const rect = this.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const index = nearestBarIndex(x, rect.width, state.daily.length);
        if (index !== state.hoverIndex) {
            state.hoverIndex = index;
            renderVisitorBarChart();
        }
    }).on('mouseleave', function () {
        state.hoverIndex = -1;
        renderVisitorBarChart();
    });

    function loadStats() {
        $.get('/admin/tatcabaiviet', function (res) {
            const total = res.data ? res.data.length : 0;
            state.posts = total;
            animateCount('#stat-baiviet', total);
            renderSpark('sparkPosts', buildSparkFromNumber(total), '#2563eb', '#06b6d4');
        }).fail(function () {
            $('#stat-baiviet').text('—');
        });

        $.get('/admin/advise/stats', function (res) {
            const sessions = Number(res.total_sessions || 0);
            const totalMessages = Number(res.total_messages || 0);
            const userMessages = Number(res.user_messages || 0);
            const botMessages = Math.max(totalMessages - userMessages, 0);

            state.chat = { sessions, totalMessages, userMessages, botMessages };

            animateCount('#stat-sessions', sessions);
            animateCount('#stat-messages', totalMessages);
            animateCount('#stat-user-msg', userMessages);
            animateCount('#hero-chat-messages', totalMessages);

            renderSpark('sparkSessions', buildSparkFromNumber(sessions), '#f59e0b', '#fb923c');
            renderSpark('sparkMessages', buildSparkFromNumber(totalMessages), '#7c3aed', '#a855f7');
            renderSpark('sparkUserMsg', buildSparkFromNumber(userMessages), '#e11d48', '#fb7185');
            renderChatStackedChart();
        }).fail(function () {
            $('#stat-sessions, #stat-messages, #stat-user-msg, #hero-chat-messages').text('—');
            setCanvasNoData('chatStackedChart', 'Không tải được dữ liệu advise');
        });
    }

    function loadDashboardStatsAndCharts() {
        $.get('/admin/dashboard/stats', function (res) {
            const today = Number(res.today || 0);
            const total = Number(res.total || 0);
            const daily = res.daily || [];

            state.traffic = { today, total };
            state.daily = daily.map(function (d) {
                return {
                    date: d.visited_date,
                    label: formatShortDate(d.visited_date),
                    count: Number(d.count || 0)
                };
            });

            animateCount('#stat-visitors', today);
            animateCount('#stat-total-visitors', total);
            animateCount('#hero-today-visitors', today);
            animateCount('#hero-total-visitors', total);

            const percent = total > 0 ? Math.min((today / total) * 100, 100) : 0;
            $('#todayProgress').css('width', Math.max(percent, today > 0 ? 8 : 0) + '%');
            $('#hero-pulse-percent').text(Math.round(percent) + '%');

            updateVisitorSummary();
            redrawAllCharts();
        }).fail(function () {
            $('#stat-visitors, #stat-total-visitors, #hero-today-visitors, #hero-total-visitors').text('—');
            setCanvasNoData('visitorChart', 'Không tải được dữ liệu truy cập');
            setCanvasNoData('trafficRingChart', 'Không tải được dữ liệu truy cập');
        });
    }

    function redrawAllCharts() {
        if (state.daily.length) {
            renderVisitorBarChart();
            renderSpark('sparkToday', state.daily.map(d => d.count), '#10b981', '#34d399');
            renderSpark('sparkTotal', state.daily.map(d => d.count), '#0891b2', '#22d3ee');
        } else {
            setCanvasNoData('visitorChart', 'Chưa có dữ liệu truy cập');
        }

        renderTrafficRingChart();
        renderChatStackedChart();
    }

    function updateVisitorSummary() {
        const daily = state.daily;
        const weekTotal = daily.reduce((sum, d) => sum + d.count, 0);
        const best = daily.reduce((m, d) => d.count > m.count ? d : m, { label: '—', count: 0 });
        const first = daily.length ? daily[0].count : 0;
        const last = daily.length ? daily[daily.length - 1].count : 0;
        const delta = first > 0 ? ((last - first) / first) * 100 : (last > 0 ? 100 : 0);

        $('#weekTotalText').text(weekTotal.toLocaleString('vi-VN'));
        $('#bestDayText').text(best.label + ' · ' + best.count.toLocaleString('vi-VN'));
        $('#visitorDeltaText').text((delta >= 0 ? '+' : '') + Math.round(delta) + '%');
    }

    /* ─── BAR CHART: Lượt truy cập 7 ngày ─── */
    function renderVisitorBarChart() {
        const canvas = document.getElementById('visitorChart');
        if (!canvas) return;
        if (!state.daily.length) {
            setCanvasNoData('visitorChart', 'Chưa có dữ liệu truy cập');
            return;
        }

        const { ctx, w, h } = setupCanvas(canvas);
        const data = state.daily;
        const values = data.map(d => d.count);
        const max = Math.max(...values, 1);

        const pad = { top: 48, right: 24, bottom: 52, left: 54 };
        const cw = w - pad.left - pad.right;
        const ch = h - pad.top - pad.bottom;

        const barCount = data.length;
        const groupW = cw / barCount;
        const barW = Math.min(52, groupW * 0.58);
        const topR = Math.min(10, barW / 2);   // bo góc trên
        const MIN_BAR = 4;                      // chiều cao tối thiểu

        ctx.clearRect(0, 0, w, h);

        /* Reset shadow trước khi vẽ grid */
        ctx.shadowColor = 'transparent';
        ctx.shadowBlur = 0;
        ctx.shadowOffsetY = 0;

        /* Grid lines */
        ctx.strokeStyle = '#edf2f8';
        ctx.lineWidth = 1;
        ctx.fillStyle = '#94a3b8';
        ctx.font = '800 10px system-ui, -apple-system, Segoe UI, sans-serif';
        ctx.textAlign = 'right';
        for (let i = 0; i <= 4; i++) {
            const gy = pad.top + ch * (1 - i / 4);
            ctx.beginPath();
            ctx.moveTo(pad.left, gy);
            ctx.lineTo(w - pad.right, gy);
            ctx.stroke();
            ctx.fillText(Math.round(max * i / 4).toLocaleString('vi-VN'), pad.left - 9, gy + 4);
        }

        data.forEach(function (d, i) {
            const rawH = ch * (d.count / max);
            const barH = Math.max(rawH, d.count > 0 ? MIN_BAR : 0);
            const x = pad.left + groupW * i + (groupW - barW) / 2;
            const y = pad.top + ch - barH;
            const active = i === state.hoverIndex;
            const r = Math.min(topR, barH / 2);

            /* Shadow */
            ctx.shadowColor = active ? 'rgba(37,99,235,.30)' : 'rgba(37,99,235,.08)';
            ctx.shadowBlur = active ? 18 : 6;
            ctx.shadowOffsetY = active ? 4 : 2;

            /* Gradient từ trên xuống */
            const grad = ctx.createLinearGradient(0, y, 0, y + barH);
            if (active) {
                grad.addColorStop(0, '#2563eb');
                grad.addColorStop(0.5, '#0284c7');
                grad.addColorStop(1, '#06b6d4');
            } else {
                grad.addColorStop(0, '#93c5fd');
                grad.addColorStop(1, '#bfdbfe');
            }

            /* Vẽ bar: góc trên bo tròn, góc dưới vuông */
            ctx.beginPath();
            ctx.moveTo(x + r, y);
            ctx.lineTo(x + barW - r, y);
            ctx.quadraticCurveTo(x + barW, y, x + barW, y + r);
            ctx.lineTo(x + barW, y + barH);
            ctx.lineTo(x, y + barH);
            ctx.lineTo(x, y + r);
            ctx.quadraticCurveTo(x, y, x + r, y);
            ctx.closePath();
            ctx.fillStyle = grad;
            ctx.fill();

            /* Reset shadow sau khi vẽ bar */
            ctx.shadowColor = 'transparent';
            ctx.shadowBlur = 0;
            ctx.shadowOffsetY = 0;

            /* Số trên đỉnh cột */
            ctx.fillStyle = active ? '#1e40af' : '#94a3b8';
            ctx.font = active
                ? '900 12px system-ui, -apple-system, Segoe UI, sans-serif'
                : '700 10px system-ui, -apple-system, Segoe UI, sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText(d.count.toLocaleString('vi-VN'), x + barW / 2, y - 7);

            /* Nhãn ngày dưới trục X */
            ctx.fillStyle = active ? '#0f172a' : '#64748b';
            ctx.font = active
                ? '900 11px system-ui, -apple-system, Segoe UI, sans-serif'
                : '700 10px system-ui, -apple-system, Segoe UI, sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText(d.label, x + barW / 2, h - 14);
        });

        /* Tooltip — chỉ hiện khi đang hover */
        if (state.hoverIndex >= 0) {
            const ti = state.hoverIndex;
            const td = data[ti];
            const rawH = ch * (td.count / max);
            const barH = Math.max(rawH, td.count > 0 ? MIN_BAR : 0);
            const tx = pad.left + groupW * ti + (groupW - barW) / 2 + barW / 2;
            const ty = pad.top + ch - barH;
            drawBarTooltip(ctx, { x: tx, y: ty, label: td.label, value: td.count }, w, pad.top);
        }
    }

    function drawBarTooltip(ctx, point, w, top) {
        if (!point) return;
        const text1 = point.label;
        const text2 = point.value.toLocaleString('vi-VN') + ' lượt';
        const boxW = 120;
        const boxH = 56;
        let x = point.x - boxW / 2;
        x = Math.max(12, Math.min(w - boxW - 12, x));
        const y = Math.max(top - 8, point.y - 80);

        ctx.shadowColor = 'rgba(15,23,42,.18)';
        ctx.shadowBlur = 18;
        ctx.shadowOffsetY = 8;
        roundRect(ctx, x, y, boxW, boxH, 16, '#0f172a');
        ctx.shadowBlur = 0;
        ctx.shadowOffsetY = 0;

        ctx.fillStyle = '#cbd5e1';
        ctx.font = '800 11px system-ui, -apple-system, Segoe UI, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText(text1, x + boxW / 2, y + 21);
        ctx.fillStyle = '#ffffff';
        ctx.font = '950 14px system-ui, -apple-system, Segoe UI, sans-serif';
        ctx.fillText(text2, x + boxW / 2, y + 40);

        ctx.beginPath();
        ctx.moveTo(point.x - 7, y + boxH - 1);
        ctx.lineTo(point.x + 7, y + boxH - 1);
        ctx.lineTo(point.x, y + boxH + 8);
        ctx.closePath();
        ctx.fillStyle = '#0f172a';
        ctx.fill();
    }

    function renderTrafficRingChart() {
        const canvas = document.getElementById('trafficRingChart');
        if (!canvas) return;
        const today = state.traffic.today;
        const total = state.traffic.total;
        if (today === 0 && total === 0) {
            setCanvasNoData('trafficRingChart', 'Chưa có dữ liệu truy cập');
            return;
        }

        const { ctx, w, h } = setupCanvas(canvas);
        ctx.clearRect(0, 0, w, h);

        const cx = w / 2;
        const cy = h * .45;
        const radius = Math.min(w, h) * .26;
        const line = Math.max(18, radius * .28);
        const pct = total > 0 ? Math.min(today / total, 1) : 0;
        const start = -Math.PI / 2;
        const end = start + pct * Math.PI * 2;

        ctx.beginPath();
        ctx.arc(cx, cy, radius, 0, Math.PI * 2);
        ctx.strokeStyle = '#e8eef7';
        ctx.lineWidth = line;
        ctx.lineCap = 'round';
        ctx.stroke();

        const grad = ctx.createLinearGradient(cx - radius, cy - radius, cx + radius, cy + radius);
        grad.addColorStop(0, '#2563eb');
        grad.addColorStop(.55, '#06b6d4');
        grad.addColorStop(1, '#7c3aed');
        ctx.beginPath();
        ctx.arc(cx, cy, radius, start, end);
        ctx.strokeStyle = grad;
        ctx.lineWidth = line;
        ctx.lineCap = 'round';
        ctx.shadowColor = 'rgba(37,99,235,.24)';
        ctx.shadowBlur = 16;
        ctx.stroke();
        ctx.shadowBlur = 0;

        ctx.fillStyle = '#0f172a';
        ctx.font = '950 34px system-ui, -apple-system, Segoe UI, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText(Math.round(pct * 100) + '%', cx, cy + 4);
        ctx.fillStyle = '#64748b';
        ctx.font = '800 12px system-ui, -apple-system, Segoe UI, sans-serif';
        ctx.fillText('chỉ số hôm nay', cx, cy + 27);

        drawRingLegend(ctx, w, h, [
            { label: 'Hôm nay', value: today, color: '#2563eb' },
            { label: 'Còn lại', value: Math.max(total - today, 0), color: '#c7d2fe' }
        ]);
    }

    function renderChatStackedChart() {
        const canvas = document.getElementById('chatStackedChart');
        if (!canvas) return;
        const rows = [
            { label: 'Phiên chat', value: state.chat.sessions, colorA: '#f59e0b', colorB: '#fb923c' },
            { label: 'Tin người dùng', value: state.chat.userMessages, colorA: '#e11d48', colorB: '#fb7185' },
            { label: 'Tin còn lại', value: state.chat.botMessages, colorA: '#7c3aed', colorB: '#a855f7' }
        ];

        if (rows.every(r => r.value === 0)) {
            setCanvasNoData('chatStackedChart', 'Chưa có dữ liệu advise');
            return;
        }

        const { ctx, w, h } = setupCanvas(canvas);
        ctx.clearRect(0, 0, w, h);
        const max = Math.max(...rows.map(r => r.value), 1);
        const left = 132;
        const right = 28;
        const barH = 22;
        const gap = 38;
        const startY = 52;
        const chartW = w - left - right;

        rows.forEach((row, i) => {
            const y = startY + i * gap;
            const bw = Math.max(8, (row.value / max) * chartW);

            ctx.fillStyle = '#475569';
            ctx.font = '850 12px system-ui, -apple-system, Segoe UI, sans-serif';
            ctx.textAlign = 'left';
            ctx.fillText(row.label, 20, y + 15);

            roundRect(ctx, left, y, chartW, barH, 11, '#edf2f8');
            const grad = ctx.createLinearGradient(left, 0, left + bw, 0);
            grad.addColorStop(0, row.colorA);
            grad.addColorStop(1, row.colorB);
            roundRect(ctx, left, y, bw, barH, 11, grad);

            ctx.fillStyle = '#0f172a';
            ctx.font = '950 12px system-ui, -apple-system, Segoe UI, sans-serif';
            ctx.textAlign = 'right';
            ctx.fillText(row.value.toLocaleString('vi-VN'), w - 20, y + 15);
        });

        const total = state.chat.totalMessages;
        ctx.fillStyle = '#64748b';
        ctx.font = '800 11px system-ui, -apple-system, Segoe UI, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText('Tổng tin nhắn: ' + total.toLocaleString('vi-VN'), w / 2, h - 28);
    }

    function renderSpark(canvasId, values, colorA, colorB) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        const { ctx, w, h } = setupCanvas(canvas);
        const arr = values && values.length ? values : [0, 1, 0, 1, 0, 1, 0];
        const max = Math.max(...arr, 1);
        const min = Math.min(...arr, 0);
        const range = Math.max(max - min, 1);
        const pad = 3;
        const points = arr.map((v, i) => ({
            x: pad + (w - pad * 2) * (arr.length === 1 ? .5 : i / (arr.length - 1)),
            y: pad + (h - pad * 2) * (1 - (v - min) / range)
        }));

        ctx.clearRect(0, 0, w, h);
        ctx.beginPath();
        points.forEach((p, i) => {
            if (i === 0) ctx.moveTo(p.x, p.y);
            else {
                const prev = points[i - 1];
                const cpX = (prev.x + p.x) / 2;
                ctx.bezierCurveTo(cpX, prev.y, cpX, p.y, p.x, p.y);
            }
        });
        const grad = ctx.createLinearGradient(0, 0, w, 0);
        grad.addColorStop(0, colorA);
        grad.addColorStop(1, colorB);
        ctx.strokeStyle = grad;
        ctx.lineWidth = 2.5;
        ctx.lineCap = 'round';
        ctx.stroke();

        const last = points[points.length - 1];
        ctx.beginPath();
        ctx.arc(last.x, last.y, 3.4, 0, Math.PI * 2);
        ctx.fillStyle = '#fff';
        ctx.fill();
        ctx.lineWidth = 2;
        ctx.strokeStyle = colorA;
        ctx.stroke();
    }

    function loadRecentPosts() {
        $.get('/admin/tatcabaiviet', function (res) {
            const posts = (res.data || []).slice(0, 6);
            if (!posts.length) {
                $('#recentPosts').html('<div class="no-data">Chưa có bài viết</div>');
                return;
            }

            let html = '';
            posts.forEach(function (p) {
                const date = p.ngaydang ? new Date(p.ngaydang).toLocaleDateString('vi-VN') : '—';
                const statusMap = {
                    show:     { label: 'Hiện',     cls: 'badge-show' },
                    hide:     { label: 'Ẩn',       cls: 'badge-hide' },
                    upcoming: { label: 'Sắp diễn', cls: 'badge-upcoming' },
                    ended:    { label: 'Kết thúc', cls: 'badge-ended' },
                };
                const st = statusMap[p.status] || { label: p.status || '—', cls: 'badge-hide' };
                const title = escapeHtml(p.tieude || 'Không có tiêu đề');
                const img = p.image_url
                    ? `<img src="${p.image_url}" class="post-thumb" alt="" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                       <div class="post-thumb post-thumb-placeholder" style="display:none"><i class="fas fa-image"></i></div>`
                    : `<div class="post-thumb post-thumb-placeholder"><i class="fas fa-image"></i></div>`;

                html += `
                <div class="post-row">
                    ${img}
                    <div class="post-info">
                        <div class="post-title">${title}</div>
                        <div class="post-meta">${date} · <span class="badge-status ${st.cls}">${st.label}</span></div>
                    </div>
                    <div class="post-views"><i class="fas fa-eye"></i> ${Number(p.views || 0).toLocaleString('vi-VN')}</div>
                </div>`;
            });

            $('#recentPosts').html(html);
        }).fail(function () {
            $('#recentPosts').html('<div class="no-data">Không tải được</div>');
        });
    }

    function loadRecentSessions() {
        $.get('/admin/advise/sessions', function (res) {
            const sessions = (res.data || []).slice(0, 5);
            if (!sessions.length) {
                $('#recentSessions').html('<div class="no-data">Chưa có phiên chat</div>');
                return;
            }

            let html = '';
            sessions.forEach(function (s) {
                const time = s.last_active_at ? formatTime(s.last_active_at) : '—';
                const ip = escapeHtml(s.ip_address || '—');

                html += `
                <div class="session-row">
                    <div class="session-avatar"><i class="fas fa-user"></i></div>
                    <div class="session-info">
                        <div class="session-ip">${ip}</div>
                        <div class="session-time">${time}</div>
                    </div>
                    <div class="session-count"><span class="badge-msg">${Number(s.message_count || 0).toLocaleString('vi-VN')} tin</span></div>
                </div>`;
            });

            $('#recentSessions').html(html);
        }).fail(function () {
            $('#recentSessions').html('<div class="no-data">Không tải được</div>');
        });
    }

    function setupCanvas(canvas) {
        const dpr = window.devicePixelRatio || 1;
        const rect = canvas.getBoundingClientRect();
        const w = Math.max(260, rect.width || canvas.parentElement.clientWidth || 320);
        const h = Math.max(120, rect.height || canvas.parentElement.clientHeight || 180);
        canvas.width = Math.floor(w * dpr);
        canvas.height = Math.floor(h * dpr);
        const ctx = canvas.getContext('2d');
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        return { ctx, w, h };
    }

    function drawRingLegend(ctx, w, h, items) {
        const startX = w * .18;
        const y = h - 46;
        const gap = w * .36;
        items.forEach((item, i) => {
            const x = startX + i * gap;
            roundRect(ctx, x, y - 8, 13, 13, 4, item.color);
            ctx.fillStyle = '#64748b';
            ctx.font = '800 11px system-ui, -apple-system, Segoe UI, sans-serif';
            ctx.textAlign = 'left';
            ctx.fillText(item.label, x + 20, y + 2);
            ctx.fillStyle = '#0f172a';
            ctx.font = '950 12px system-ui, -apple-system, Segoe UI, sans-serif';
            ctx.fillText(item.value.toLocaleString('vi-VN'), x + 20, y + 19);
        });
    }

    function roundRect(ctx, x, y, w, h, r, fillStyle) {
        const radius = Math.min(r, w / 2, h / 2);
        ctx.beginPath();
        ctx.moveTo(x + radius, y);
        ctx.arcTo(x + w, y, x + w, y + h, radius);
        ctx.arcTo(x + w, y + h, x, y + h, radius);
        ctx.arcTo(x, y + h, x, y, radius);
        ctx.arcTo(x, y, x + w, y, radius);
        ctx.closePath();
        ctx.fillStyle = fillStyle;
        ctx.fill();
    }

    function nearestBarIndex(x, width, count) {
        if (count <= 1) return 0;
        const pad = { left: 54, right: 24 };
        const cw = width - pad.left - pad.right;
        const groupW = cw / count;
        const relative = Math.max(0, Math.min(cw, x - pad.left));
        return Math.min(Math.floor(relative / groupW), count - 1);
    }

    function setCanvasNoData(canvasId, text) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        const { ctx, w, h } = setupCanvas(canvas);
        ctx.clearRect(0, 0, w, h);
        ctx.fillStyle = '#94a3b8';
        ctx.font = '800 13px system-ui, -apple-system, Segoe UI, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText(text, w / 2, h / 2);
    }

    function buildSparkFromNumber(n) {
        const base = Math.max(Number(n || 0), 1);
        return [
            Math.round(base * .22),
            Math.round(base * .36),
            Math.round(base * .29),
            Math.round(base * .55),
            Math.round(base * .48),
            Math.round(base * .78),
            base
        ];
    }

    function animateCount(selector, target) {
        const $el = $(selector);
        const end = Number(target || 0);
        let current = 0;
        const step = Math.max(1, Math.ceil(end / 42));
        clearInterval($el.data('timer'));
        const timer = setInterval(function () {
            current = Math.min(current + step, end);
            $el.text(current.toLocaleString('vi-VN'));
            if (current >= end) clearInterval(timer);
        }, 15);
        $el.data('timer', timer);
    }

    function formatShortDate(str) {
        const d = new Date(str);
        if (isNaN(d)) return str || '—';
        return String(d.getDate()).padStart(2, '0') + '/' + String(d.getMonth() + 1).padStart(2, '0');
    }

    function formatTime(str) {
        if (!str) return '—';
        const d = new Date(str);
        if (isNaN(d)) return str;
        const p = n => String(n).padStart(2, '0');
        return `${p(d.getDate())}/${p(d.getMonth()+1)} ${p(d.getHours())}:${p(d.getMinutes())}`;
    }

    function escapeHtml(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function debounce(fn, delay) {
        let timer = null;
        return function () {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, arguments), delay);
        };
    }
});