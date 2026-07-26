<!-- =========================================================
FILE 1: resources/views/admins/dashboard.blade.php
BẢN WHITE VIP PRO - giữ nguyên endpoint/API, chỉ thay giao diện + canvas chart.
========================================================= -->
@extends('admins.layouts.app')

@section('title', 'Bảng điều khiển')

@section('css')
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
:root {
    --dash-bg: #f7f9fc;
    --dash-card: #ffffff;
    --dash-card-2: #fbfdff;
    --dash-line: #e7edf5;
    --dash-line-2: #dbe5f1;
    --dash-text: #0f172a;
    --dash-muted: #64748b;
    --dash-soft: #f1f5f9;
    --dash-blue: #2563eb;
    --dash-sky: #0284c7;
    --dash-cyan: #06b6d4;
    --dash-violet: #7c3aed;
    --dash-emerald: #10b981;
    --dash-amber: #f59e0b;
    --dash-rose: #f43f5e;
    --dash-indigo: #4f46e5;
    --dash-shadow: 0 24px 80px rgba(15, 23, 42, .10);
    --dash-shadow-sm: 0 14px 36px rgba(15, 23, 42, .075);
    --dash-radius: 28px;
}

.db-wrap {
    min-height: 100vh;
    padding: 26px 28px 66px;
    color: var(--dash-text);
    background:
        radial-gradient(circle at 8% -10%, rgba(37,99,235,.13), transparent 30%),
        radial-gradient(circle at 92% 2%, rgba(124,58,237,.11), transparent 30%),
        linear-gradient(180deg, #ffffff 0%, var(--dash-bg) 52%, #f5f8fd 100%);
}

.db-shell {
    max-width: 1680px;
    margin: 0 auto;
}

.db-topbar {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 20px;
    align-items: center;
    margin-bottom: 18px;
}

.db-title-block {
    min-width: 0;
}

.db-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 11px;
    border-radius: 999px;
    background: #eff6ff;
    border: 1px solid #dbeafe;
    color: #1d4ed8;
    font-size: .72rem;
    font-weight: 900;
    letter-spacing: .055em;
    text-transform: uppercase;
}

.db-title-row {
    display: flex;
    align-items: flex-end;
    gap: 14px;
    flex-wrap: wrap;
    margin-top: 11px;
}

.db-title-row h1 {
    margin: 0;
    font-size: 2.05rem;
    line-height: 1;
    font-weight: 950;
    letter-spacing: -1.1px;
    color: #07111f;
}

.db-date {
    color: var(--dash-muted);
    font-size: .83rem;
    font-weight: 720;
    padding-bottom: 2px;
}

.db-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.db-action-pill {
    height: 42px;
    display: inline-flex;
    align-items: center;
    gap: 9px;
    padding: 0 14px;
    border-radius: 16px;
    border: 1px solid var(--dash-line);
    background: rgba(255,255,255,.9);
    color: #334155;
    box-shadow: 0 8px 22px rgba(15,23,42,.05);
    font-size: .78rem;
    font-weight: 850;
}

.db-action-pill i { color: var(--dash-blue); }

.exec-hero {
    position: relative;
    overflow: hidden;
    display: grid;
    grid-template-columns: minmax(0, 1.55fr) minmax(320px, .45fr);
    gap: 18px;
    margin-bottom: 18px;
}

.hero-command {
    position: relative;
    overflow: hidden;
    min-height: 210px;
    border-radius: 34px;
    padding: 28px;
    background:
        linear-gradient(135deg, #ffffff 0%, #f7fbff 44%, #eef6ff 100%);
    border: 1px solid rgba(219,229,241,.95);
    box-shadow: var(--dash-shadow);
}

.hero-command::before {
    content: "";
    position: absolute;
    right: -80px;
    top: -120px;
    width: 360px;
    height: 360px;
    border-radius: 999px;
    background: conic-gradient(from 210deg, rgba(37,99,235,.22), rgba(6,182,212,.20), rgba(124,58,237,.16), rgba(37,99,235,.22));
    filter: blur(.2px);
}

.hero-command::after {
    content: "";
    position: absolute;
    right: 54px;
    bottom: -74px;
    width: 260px;
    height: 260px;
    border-radius: 58px;
    transform: rotate(22deg);
    background: linear-gradient(135deg, rgba(37,99,235,.10), rgba(6,182,212,.04));
    border: 1px solid rgba(37,99,235,.10);
}

.hero-content {
    position: relative;
    z-index: 1;
    max-width: 780px;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 999px;
    background: #0f172a;
    color: #e0f2fe;
    font-size: .72rem;
    font-weight: 900;
    letter-spacing: .055em;
    text-transform: uppercase;
    box-shadow: 0 14px 30px rgba(15,23,42,.18);
}

.hero-content h2 {
    margin: 18px 0 10px;
    font-size: 2.35rem;
    line-height: 1.05;
    font-weight: 950;
    letter-spacing: -1.3px;
    color: #07111f;
}

.hero-content p {
    margin: 0;
    max-width: 660px;
    color: #64748b;
    line-height: 1.7;
    font-size: .94rem;
    font-weight: 620;
}

.hero-metrics {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    margin-top: 24px;
    max-width: 760px;
}

.hero-mini {
    position: relative;
    overflow: hidden;
    padding: 13px 14px;
    border-radius: 20px;
    background: rgba(255,255,255,.78);
    border: 1px solid rgba(219,229,241,.95);
    box-shadow: 0 10px 28px rgba(15,23,42,.055);
}

.hero-mini span {
    display: block;
    color: #64748b;
    font-size: .72rem;
    font-weight: 850;
}

.hero-mini strong {
    display: block;
    margin-top: 6px;
    color: #0f172a;
    font-size: 1.2rem;
    font-weight: 950;
    letter-spacing: -.45px;
}

.hero-mini::after {
    content: "";
    position: absolute;
    width: 72px;
    height: 72px;
    right: -28px;
    bottom: -36px;
    border-radius: 999px;
    background: rgba(37,99,235,.10);
}

.hero-radar {
    position: relative;
    overflow: hidden;
    border-radius: 34px;
    border: 1px solid rgba(219,229,241,.95);
    background: #fff;
    box-shadow: var(--dash-shadow-sm);
    padding: 22px;
    min-height: 210px;
}

.radar-ring {
    position: absolute;
    right: -34px;
    top: -48px;
    width: 196px;
    height: 196px;
    border-radius: 999px;
    background:
        radial-gradient(circle at center, #fff 0 42%, transparent 43%),
        conic-gradient(#2563eb, #06b6d4, #7c3aed, #2563eb);
    opacity: .14;
}

.radar-label {
    position: relative;
    z-index: 1;
    color: #64748b;
    font-size: .75rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .08em;
}

.radar-value {
    position: relative;
    z-index: 1;
    margin-top: 12px;
    color: #07111f;
    font-size: 3.2rem;
    line-height: 1;
    font-weight: 950;
    letter-spacing: -2px;
}

.radar-sub {
    position: relative;
    z-index: 1;
    margin-top: 10px;
    color: #64748b;
    font-size: .82rem;
    font-weight: 650;
    line-height: 1.55;
}

.radar-progress {
    position: relative;
    z-index: 1;
    height: 11px;
    margin-top: 22px;
    border-radius: 999px;
    overflow: hidden;
    background: #e8eef7;
}

.radar-progress span {
    display: block;
    height: 100%;
    width: 0%;
    border-radius: inherit;
    background: linear-gradient(90deg, #2563eb, #06b6d4, #7c3aed);
    box-shadow: 0 0 24px rgba(37,99,235,.28);
    transition: width .45s ease;
}

.stat-grid {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 18px;
}

.stat-card {
    position: relative;
    overflow: hidden;
    min-height: 154px;
    padding: 17px;
    border-radius: 26px;
    background: var(--dash-card);
    border: 1px solid var(--dash-line);
    box-shadow: 0 14px 36px rgba(15,23,42,.065);
    transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
}

.stat-card::before {
    content: "";
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 88% 12%, var(--card-glow), transparent 42%);
    opacity: .95;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 24px 58px rgba(15,23,42,.105);
    border-color: var(--card-border);
}

.stat-top {
    position: relative;
    z-index: 1;
    display: flex;
    justify-content: space-between;
    gap: 10px;
}

.stat-icon {
    width: 46px;
    height: 46px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 18px;
    color: #fff;
    background: linear-gradient(135deg, var(--card-a), var(--card-b));
    box-shadow: 0 14px 28px var(--card-shadow);
}

.stat-chip {
    height: 26px;
    display: inline-flex;
    align-items: center;
    padding: 0 9px;
    border-radius: 999px;
    background: #f8fafc;
    border: 1px solid #eef2f7;
    color: #64748b;
    font-size: .64rem;
    font-weight: 900;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.stat-val {
    position: relative;
    z-index: 1;
    margin-top: 16px;
    color: #07111f;
    font-size: 1.72rem;
    line-height: 1;
    font-weight: 950;
    letter-spacing: -.85px;
}

.stat-label {
    position: relative;
    z-index: 1;
    margin-top: 7px;
    color: #64748b;
    font-size: .75rem;
    font-weight: 800;
}

.stat-spark {
    position: absolute;
    left: 14px;
    right: 14px;
    bottom: 12px;
    height: 34px;
    opacity: .96;
}

.stat-spark canvas {
    width: 100% !important;
    height: 100% !important;
    display: block;
}

.card-blue   { --card-a:#2563eb; --card-b:#06b6d4; --card-shadow:rgba(37,99,235,.22); --card-glow:rgba(37,99,235,.12); --card-border:rgba(37,99,235,.28); }
.card-green  { --card-a:#059669; --card-b:#34d399; --card-shadow:rgba(16,185,129,.20); --card-glow:rgba(16,185,129,.13); --card-border:rgba(16,185,129,.28); }
.card-teal   { --card-a:#0891b2; --card-b:#22d3ee; --card-shadow:rgba(6,182,212,.20); --card-glow:rgba(6,182,212,.13); --card-border:rgba(6,182,212,.28); }
.card-amber  { --card-a:#f59e0b; --card-b:#fb923c; --card-shadow:rgba(245,158,11,.20); --card-glow:rgba(245,158,11,.14); --card-border:rgba(245,158,11,.30); }
.card-violet { --card-a:#7c3aed; --card-b:#a855f7; --card-shadow:rgba(124,58,237,.20); --card-glow:rgba(124,58,237,.13); --card-border:rgba(124,58,237,.28); }
.card-rose   { --card-a:#e11d48; --card-b:#fb7185; --card-shadow:rgba(244,63,94,.18); --card-glow:rgba(244,63,94,.12); --card-border:rgba(244,63,94,.28); }

.db-grid-main {
    display: grid;
    grid-template-columns: minmax(0, 1.42fr) minmax(360px, .58fr);
    gap: 18px;
    margin-bottom: 18px;
}

.db-grid-two {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
    margin-bottom: 18px;
}

.db-panel {
    position: relative;
    overflow: hidden;
    border-radius: var(--dash-radius);
    background: var(--dash-card);
    border: 1px solid var(--dash-line);
    box-shadow: var(--dash-shadow-sm);
}

.db-panel::before {
    content: "";
    position: absolute;
    inset: 0;
    pointer-events: none;
    background: linear-gradient(135deg, rgba(37,99,235,.035), transparent 42%, rgba(124,58,237,.035));
}

.panel-head {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 18px 20px;
    border-bottom: 1px solid #eef2f7;
    background: linear-gradient(180deg, #ffffff, #fbfdff);
}

.panel-title {
    min-width: 0;
}

.panel-head h2 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
    color: #0f172a;
    font-size: .96rem;
    font-weight: 950;
    letter-spacing: -.2px;
}

.panel-head h2 i {
    width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 14px;
    color: #fff;
    background: linear-gradient(135deg, #2563eb, #06b6d4);
    box-shadow: 0 12px 24px rgba(37,99,235,.18);
    font-size: .82rem;
}

.panel-subtitle {
    margin-top: 5px;
    color: #64748b;
    font-size: .74rem;
    font-weight: 650;
}

.panel-link {
    white-space: nowrap;
    color: #2563eb;
    text-decoration: none;
    font-size: .76rem;
    font-weight: 900;
}

.panel-link:hover { color: #1d4ed8; }

.chart-wrap {
    position: relative;
    z-index: 1;
    height: 374px;
    padding: 18px 18px 20px;
}

.chart-wrap.mid { height: 304px; }
.chart-wrap.small { height: 260px; }

.chart-wrap canvas {
    width: 100% !important;
    height: 100% !important;
    display: block;
}

.chart-foot {
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
    padding: 0 20px 18px;
}

.chart-foot-item {
    padding: 11px 12px;
    border-radius: 17px;
    background: #f8fafc;
    border: 1px solid #eef2f7;
}

.chart-foot-item span {
    display: block;
    color: #64748b;
    font-size: .69rem;
    font-weight: 850;
    text-transform: uppercase;
    letter-spacing: .04em;
}

.chart-foot-item strong {
    display: block;
    margin-top: 5px;
    color: #0f172a;
    font-size: .94rem;
    font-weight: 950;
}

.panel-body {
    position: relative;
    z-index: 1;
    padding: 16px 20px 18px;
}

.no-data {
    min-height: 118px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: #94a3b8;
    font-size: .82rem;
    font-weight: 760;
}

.post-list-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0 24px;
}

.post-row,
.session-row {
    display: flex;
    align-items: center;
    gap: 13px;
    min-width: 0;
    padding: 13px 0;
    border-bottom: 1px solid #eef2f7;
}

.post-row:last-child,
.session-row:last-child { border-bottom: none; }

.post-thumb {
    width: 54px;
    height: 54px;
    min-width: 54px;
    border-radius: 18px;
    object-fit: cover;
    flex-shrink: 0;
    background: #f1f5f9;
    box-shadow: 0 0 0 1px #e2e8f0 inset, 0 10px 24px rgba(15,23,42,.07);
}

.post-thumb-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    color: #cbd5e1;
}

.post-info,
.session-info { flex: 1; min-width: 0; }

.post-title,
.session-ip {
    color: #172033;
    font-size: .86rem;
    font-weight: 850;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.post-meta,
.session-time {
    margin-top: 5px;
    color: #94a3b8;
    font-size: .72rem;
    font-weight: 680;
}

.post-views {
    white-space: nowrap;
    color: #475569;
    font-size: .76rem;
    font-weight: 850;
}

.badge-status,
.badge-msg {
    display: inline-flex;
    align-items: center;
    padding: 3px 9px;
    border-radius: 999px;
    font-size: .66rem;
    font-weight: 950;
}

.badge-show     { background:#dcfce7; color:#15803d; }
.badge-hide     { background:#f1f5f9; color:#64748b; }
.badge-upcoming { background:#dbeafe; color:#1d4ed8; }
.badge-ended    { background:#ffe4e6; color:#be123c; }
.badge-msg      { background:#ecfdf5; color:#047857; }

.session-avatar {
    width: 44px;
    height: 44px;
    border-radius: 17px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: #fff;
    background: linear-gradient(135deg, #2563eb, #06b6d4);
    box-shadow: 0 12px 24px rgba(37,99,235,.18);
}

.quick-grid {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 13px;
}

.quick-item {
    position: relative;
    overflow: hidden;
    min-height: 112px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: flex-start;
    gap: 11px;
    padding: 17px;
    text-decoration: none;
    color: #172033;
    border-radius: 24px;
    border: 1px solid var(--dash-line);
    background: #fff;
    box-shadow: 0 12px 28px rgba(15,23,42,.055);
    transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
}

.quick-item::after {
    content: "";
    position: absolute;
    right: -34px;
    bottom: -38px;
    width: 96px;
    height: 96px;
    border-radius: 999px;
    background: rgba(37,99,235,.09);
}

.quick-item:hover {
    transform: translateY(-5px);
    border-color: rgba(37,99,235,.28);
    box-shadow: 0 24px 54px rgba(15,23,42,.10);
}

.quick-item i {
    width: 44px;
    height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 17px;
    color: #fff;
    background: linear-gradient(135deg, #0f172a, #2563eb);
    box-shadow: 0 12px 24px rgba(15,23,42,.16);
}

.quick-item span {
    position: relative;
    z-index: 1;
    font-size: .83rem;
    font-weight: 950;
}

@media (max-width: 1320px) {
    .stat-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .quick-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}

@media (max-width: 1024px) {
    .exec-hero,
    .db-grid-main,
    .db-grid-two { grid-template-columns: 1fr; }
    .post-list-grid { grid-template-columns: 1fr; }
}

@media (max-width: 740px) {
    .db-wrap { padding: 18px 14px 54px; }
    .db-topbar { grid-template-columns: 1fr; }
    .db-actions { flex-wrap: wrap; }
    .hero-command { padding: 22px; }
    .hero-content h2 { font-size: 1.66rem; }
    .hero-metrics,
    .chart-foot { grid-template-columns: 1fr; }
    .stat-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .quick-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .chart-wrap { height: 306px; }
}
</style>
@endsection

@section('content')
<div class="db-wrap">
    <div class="db-shell">

        <header class="db-topbar">
            <div class="db-title-block">
                <div class="db-eyebrow"><i class="fas fa-layer-group"></i> Bảng phân tích quản trị</div>
                <div class="db-title-row">
                    <h1>Bảng điều khiển</h1>
                    <span class="db-date" id="dbDate">—</span>
                </div>
            </div>
            <div class="db-actions">
                <div class="db-action-pill"><i class="fas fa-signal"></i> Dữ liệu trực tiếp</div>
                <div class="db-action-pill"><i class="fas fa-shield-halved"></i> Chế độ quản trị</div>
            </div>
        </header>

        <section class="exec-hero">
            <div class="hero-command">
                <div class="hero-content">
                    <div class="hero-badge"><i class="fas fa-bolt"></i> Trung tâm điều hành</div>
                    <h2>Tổng quan nội dung, truy cập và advise</h2>
                    <p>Giao diện điều hành trực quan hóa dữ liệu bằng các biểu đồ tùy chỉnh, giúp bạn nắm bắt toàn bộ hoạt động hệ thống trong một tầm nhìn.</p>

                    <div class="hero-metrics">
                        <div class="hero-mini"><span>Truy cập hôm nay</span><strong id="hero-today-visitors">—</strong></div>
                        <div class="hero-mini"><span>Tổng truy cập</span><strong id="hero-total-visitors">—</strong></div>
                        <div class="hero-mini"><span>Tổng tin nhắn</span><strong id="hero-chat-messages">—</strong></div>
                    </div>
                </div>
            </div>

            <aside class="hero-radar">
                <div class="radar-ring"></div>
                <div class="radar-label">Chỉ số truy cập</div>
                <div class="radar-value" id="hero-pulse-percent">0%</div>
                <div class="radar-sub">Tỷ trọng truy cập hôm nay trên tổng số lượt truy cập đã ghi nhận.</div>
                <div class="radar-progress"><span id="todayProgress"></span></div>
            </aside>
        </section>

        <section class="stat-grid">
            <div class="stat-card card-blue">
                <div class="stat-top"><div class="stat-icon"><i class="fas fa-newspaper"></i></div><span class="stat-chip">NỘI DUNG</span></div>
                <div class="stat-val" id="stat-baiviet">—</div>
                <div class="stat-label">Bài viết</div>
                <div class="stat-spark"><canvas id="sparkPosts"></canvas></div>
            </div>
            <div class="stat-card card-green">
                <div class="stat-top"><div class="stat-icon"><i class="fas fa-user-check"></i></div><span class="stat-chip">HÔM NAY</span></div>
                <div class="stat-val" id="stat-visitors">—</div>
                <div class="stat-label">Lượt truy cập hôm nay</div>
                <div class="stat-spark"><canvas id="sparkToday"></canvas></div>
            </div>
            <div class="stat-card card-teal">
                <div class="stat-top"><div class="stat-icon"><i class="fas fa-globe"></i></div><span class="stat-chip">TỔNG CỘNG</span></div>
                <div class="stat-val" id="stat-total-visitors">—</div>
                <div class="stat-label">Tổng lượt truy cập</div>
                <div class="stat-spark"><canvas id="sparkTotal"></canvas></div>
            </div>
            <div class="stat-card card-amber">
                <div class="stat-top"><div class="stat-icon"><i class="fas fa-comments"></i></div><span class="stat-chip">CHAT</span></div>
                <div class="stat-val" id="stat-sessions">—</div>
                <div class="stat-label">Phiên chat</div>
                <div class="stat-spark"><canvas id="sparkSessions"></canvas></div>
            </div>
            <div class="stat-card card-violet">
                <div class="stat-top"><div class="stat-icon"><i class="fas fa-envelope"></i></div><span class="stat-chip">TIN NHẮN</span></div>
                <div class="stat-val" id="stat-messages">—</div>
                <div class="stat-label">Tổng tin nhắn</div>
                <div class="stat-spark"><canvas id="sparkMessages"></canvas></div>
            </div>
            <div class="stat-card card-rose">
                <div class="stat-top"><div class="stat-icon"><i class="fas fa-user"></i></div><span class="stat-chip">NGƯỜI DÙNG</span></div>
                <div class="stat-val" id="stat-user-msg">—</div>
                <div class="stat-label">Tin nhắn người dùng</div>
                <div class="stat-spark"><canvas id="sparkUserMsg"></canvas></div>
            </div>
        </section>

        <section class="db-grid-main">
            <div class="db-panel">
                <div class="panel-head">
                    <div class="panel-title">
                        <h2><i class="fas fa-chart-bar"></i> Lượt truy cập 7 ngày qua</h2>
                        <div class="panel-subtitle">Biểu đồ cột so sánh lượt truy cập từng ngày trong tuần.</div>
                    </div>
                </div>
                <div class="chart-wrap"><canvas id="visitorChart"></canvas></div>
                <div class="chart-foot">
                    <div class="chart-foot-item"><span>Tổng 7 ngày</span><strong id="weekTotalText">—</strong></div>
                    <div class="chart-foot-item"><span>Ngày cao nhất</span><strong id="bestDayText">—</strong></div>
                    <div class="chart-foot-item"><span>Biến động</span><strong id="visitorDeltaText">—</strong></div>
                </div>
            </div>

            <div class="db-panel">
                <div class="panel-head">
                    <div class="panel-title">
                        <h2><i class="fas fa-gauge-high"></i> Chỉ số truy cập</h2>
                        <div class="panel-subtitle">Tỷ trọng hôm nay so với tổng truy cập.</div>
                    </div>
                </div>
                <div class="chart-wrap mid"><canvas id="trafficRingChart"></canvas></div>
            </div>
        </section>

        <section class="db-grid-two">
            <div class="db-panel">
                <div class="panel-head">
                    <div class="panel-title">
                        <h2><i class="fas fa-chart-simple"></i> Hiệu suất Advise</h2>
                        <div class="panel-subtitle">So sánh phiên chat, tin người dùng và tin bot.</div>
                    </div>
                </div>
                <div class="chart-wrap small"><canvas id="chatStackedChart"></canvas></div>
            </div>

            <div class="db-panel">
                <div class="panel-head">
                    <div class="panel-title">
                        <h2><i class="fas fa-robot"></i> Phiên chat gần đây</h2>
                        <div class="panel-subtitle">Danh sách phiên advise mới nhất.</div>
                    </div>
                    <a href="/admin/advise" class="panel-link">Xem tất cả →</a>
                </div>
                <div class="panel-body" id="recentSessions"><div class="no-data"><i class="fas fa-spinner fa-spin"></i> Đang tải...</div></div>
            </div>
        </section>

        <section class="db-panel" style="margin-bottom:18px;">
            <div class="panel-head">
                <div class="panel-title">
                    <h2><i class="fas fa-newspaper"></i> Bài viết gần đây</h2>
                    <div class="panel-subtitle">Các nội dung mới nhất đang có trong hệ thống.</div>
                </div>
                <a href="/admin/baiviet" class="panel-link">Xem tất cả →</a>
            </div>
            <div class="panel-body">
                <div class="post-list-grid" id="recentPosts"><div class="no-data"><i class="fas fa-spinner fa-spin"></i> Đang tải...</div></div>
            </div>
        </section>

        <section class="db-panel">
            <div class="panel-head">
                <div class="panel-title">
                    <h2><i class="fas fa-compass"></i> Điều hướng nhanh</h2>
                    <div class="panel-subtitle">Truy cập nhanh các khu vực quản trị chính.</div>
                </div>
            </div>
            <div class="panel-body">
                <div class="quick-grid">
                    <a href="/admin/baiviet" class="quick-item"><i class="fas fa-pen-to-square"></i><span>Bài viết</span></a>
                    <a href="/admin/banner" class="quick-item"><i class="fas fa-images"></i><span>Banner</span></a>
                    <a href="/admin/tacgia" class="quick-item"><i class="fas fa-user-pen"></i><span>Tác giả</span></a>
                    <a href="/admin/advise" class="quick-item"><i class="fas fa-robot"></i><span>Advise</span></a>
                    <a href="/admin/nganhhoc" class="quick-item"><i class="fas fa-graduation-cap"></i><span>Ngành học</span></a>
                    <a href="/" target="_blank" class="quick-item"><i class="fas fa-globe"></i><span>Xem trang web</span></a>
                </div>
            </div>
        </section>

    </div>
</div>
@endsection

@section('js')
<script>
(function () {
    const d = new Date();
    const days = ['Chủ nhật','Thứ 2','Thứ 3','Thứ 4','Thứ 5','Thứ 6','Thứ 7'];
    const months = ['01','02','03','04','05','06','07','08','09','10','11','12'];
    const el = document.getElementById('dbDate');
    if (el) el.textContent = days[d.getDay()] + ', ' + d.getDate() + '/' + months[d.getMonth()] + '/' + d.getFullYear();
})();
</script>
<script src="{{ asset('js/admins/dashboard.js') }}"></script>
@endsection