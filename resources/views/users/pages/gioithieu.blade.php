@extends('users.layouts.app')

@section('title', 'Giới thiệu - CTUT')

@section('css')
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"/>
<style>
*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

  :root {
    --navy: #0e4582;
    --navy-deep: #08285a;
    --navy-light: #1a5a9e;
    --gold: #c4a44a;
    --gold-light: #e8d28e;
    --cream: #f8f5ef;
    --white: #ffffff;
    --ink: #0d1b2e;
    --muted: #6b7a8d;
    --border: rgba(14,69,130,0.12);
  }

  html { scroll-behavior: smooth; }

  body {
    font-family: 'DM Sans', sans-serif;
    background: var(--cream);
    color: var(--ink);
    /* overflow-x: hidden; */
  }

  /* NOISE TEXTURE OVERLAY */
  body::before {
    content: '';
    position: fixed;
    inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.035'/%3E%3C/svg%3E");
    pointer-events: none;
    z-index: 10000;
    opacity: 0.4;
  }

  /* ========== HERO ========== */
  .hero {
    min-height: 100vh;
    background: var(--navy-deep);
    display: grid;
    grid-template-columns: 1fr 1fr;
    position: relative;
    overflow: hidden;
  }

  .hero-geo {
    position: absolute;
    inset: 0;
    pointer-events: none;
  }

  .hero-left {
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 7rem 5rem 5rem 6rem;
    position: relative;
    z-index: 2;
  }

  .hero-eyebrow {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 2.5rem;
  }
  .hero-eyebrow-line {
    width: 40px; height: 1px;
    background: var(--gold);
    flex-shrink: 0;
  }
  .hero-eyebrow span {
    font-family: 'DM Sans', sans-serif;
    font-size: 11px;
    letter-spacing: 0.22em;
    text-transform: uppercase;
    color: var(--gold);
    font-weight: 400;
  }

  .hero-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(3.8rem, 6vw, 5.5rem);
    font-weight: 300;
    line-height: 1.04;
    color: var(--white);
    margin-bottom: 2rem;
    letter-spacing: -0.01em;
  }
  .hero-title em {
    font-style: italic;
    color: var(--gold-light);
  }

  .hero-sub {
    font-size: 15px;
    line-height: 1.8;
    color: rgba(255,255,255,0.55);
    max-width: 380px;
    margin-bottom: 3.5rem;
    font-weight: 300;
  }

  .hero-cta {
    display: flex;
    align-items: center;
    gap: 20px;
  }
  .btn-primaryy {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 15px 32px;
    background: var(--gold);
    color: var(--navy-deep);
    font-size: 13px;
    font-weight: 500;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    text-decoration: none;
    transition: all 0.35s cubic-bezier(0.23, 1, 0.32, 1);
    position: relative;
    overflow: hidden;
    border-radius: 0px !important;
  }
  .btn-primaryy::before {
    content: '';
    position: absolute;
    inset: 0;
    background: var(--white);
    transform: translateX(-101%);
    transition: transform 0.35s cubic-bezier(0.23, 1, 0.32, 1);
  }
  .btn-primaryy:hover::before { transform: translateX(0); }
  .btn-primaryy span, .btn-primaryy i { position: relative; z-index: 1; }

  .btn-ghost {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: rgba(255,255,255,0.5);
    font-size: 13px;
    letter-spacing: 0.08em;
    text-decoration: none;
    transition: color 0.25s;
    text-transform: uppercase;
  }
  .btn-ghost:hover { color: rgba(255,255,255,0.9); }

  .hero-right {
    position: relative;
    overflow: hidden;
  }
  .hero-img-wrap {
    position: absolute;
    inset: 0;
  }
  .hero-img-wrap img {
    width: 100%; height: 100%;
    object-fit: cover;
    filter: saturate(0.6) brightness(0.55);
    mix-blend-mode: luminosity;
  }
  .hero-img-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to right, var(--navy-deep) 0%, transparent 35%),
                linear-gradient(to top, rgba(8,40,90,0.7) 0%, transparent 50%);
  }

  .hero-stat-strip {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    display: flex;
    border-top: 1px solid rgba(255,255,255,0.07);
    z-index: 3;
  }
  .hero-stat {
    flex: 1;
    padding: 28px 36px;
    border-right: 1px solid rgba(255,255,255,0.07);
  }
  .hero-stat:last-child { border-right: none; }
  .hero-stat-num {
    font-family: 'Cormorant Garamond', serif;
    font-size: 2.6rem;
    font-weight: 600;
    color: var(--gold-light);
    line-height: 1;
    margin-bottom: 4px;
  }
  .hero-stat-label {
    font-size: 11px;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: rgba(255,255,255,0.35);
    font-weight: 400;
  }

  .hero-badge {
    position: absolute;
    top: 40px; right: 40px;
    z-index: 5;
    width: 96px; height: 96px;
  }
  .hero-badge svg { animation: spin-slow 18s linear infinite; }
  .hero-badge-center {
    position: absolute;
    inset: 0;
    display: flex; align-items: center; justify-content: center;
  }
  .hero-badge-dot {
    width: 12px; height: 12px;
    background: var(--gold);
    border-radius: 50%;
  }
  @keyframes spin-slow { to { transform: rotate(360deg); } }

  /* ========== HISTORY SECTION ========== */
  .section-history {
    padding: 10rem 0 8rem;
    background: var(--cream);
    position: relative;
  }

  .container { max-width: 1400px; margin: 0 auto; padding: 0 5rem; }

  .kicker {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    font-size: 10.5px;
    letter-spacing: 0.26em;
    text-transform: uppercase;
    color: var(--navy);
    font-weight: 500;
    margin-bottom: 1.5rem;
  }
  .kicker::before {
    content: '';
    display: block;
    width: 28px; height: 1px;
    background: var(--navy);
    flex-shrink: 0;
  }

  .history-grid {
    display: grid;
    grid-template-columns: 5fr 4fr;
    gap: 6rem;
    align-items: start;
  }

  .section-headline {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(2.8rem, 4.5vw, 4rem);
    font-weight: 300;
    line-height: 1.12;
    color: var(--ink);
    margin-bottom: 2.5rem;
    letter-spacing: -0.01em;
  }
  .section-headline em { font-style: italic; color: var(--navy); }

  .history-body p {
    font-size: 15.5px;
    line-height: 1.85;
    color: #4a5568;
    margin-bottom: 1.5rem;
    font-weight: 300;
  }
  .history-body strong { color: var(--navy); font-weight: 500; }

  .history-quote {
    margin-top: 2.5rem;
    padding: 2rem 2rem 2rem 2.5rem;
    border-left: 2px solid var(--gold);
    background: rgba(196,164,74,0.05);
  }
  .history-quote p {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.45rem;
    line-height: 1.5;
    font-style: italic;
    color: var(--navy-deep);
    font-weight: 400;
    margin-bottom: 0 !important;
  }

  .history-aside {
    position: sticky;
    top: 5rem;
  }

  .milestone-list {
    list-style: none;
    position: relative;
    padding-left: 2rem;
  }
  .milestone-list::before {
    content: '';
    position: absolute;
    left: 6px; top: 8px; bottom: 8px;
    width: 1px;
    background: linear-gradient(to bottom, var(--navy), transparent);
  }
  .milestone-list li {
    position: relative;
    padding-bottom: 2.5rem;
  }
  .milestone-list li:last-child { padding-bottom: 0; }
  .milestone-list li::before {
    content: '';
    position: absolute;
    left: -2rem;
    top: 8px;
    width: 7px; height: 7px;
    border-radius: 50%;
    background: var(--navy);
    border: 2px solid var(--cream);
    box-shadow: 0 0 0 1px var(--navy);
  }
  .milestone-year {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.6rem;
    font-weight: 600;
    color: var(--navy);
    line-height: 1;
    margin-bottom: 4px;
  }
  .milestone-text {
    font-size: 13.5px;
    color: var(--muted);
    line-height: 1.6;
    font-weight: 300;
  }

  /* ========== MISSION SECTION ========== */
  .section-mission {
    background: var(--navy-deep);
    padding: 9rem 0;
    position: relative;
    overflow: hidden;
  }
  .mission-bg-text {
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    font-family: 'Cormorant Garamond', serif;
    font-size: 18vw;
    font-weight: 700;
    color: rgba(255,255,255,0.025);
    pointer-events: none;
    white-space: nowrap;
    letter-spacing: -0.04em;
    user-select: none;
  }

  .mission-grid {
    display: grid;
    grid-template-columns: 1fr 1px 1fr;
    gap: 0 5rem;
    position: relative;
    z-index: 2;
  }
  .mission-divider {
    background: rgba(255,255,255,0.1);
    height: 100%;
    align-self: stretch;
  }
  .mission-card { padding: 0 1rem; }
  .mission-icon {
    width: 52px; height: 52px;
    border: 1px solid rgba(196,164,74,0.4);
    display: flex; align-items: center; justify-content: center;
    color: var(--gold);
    font-size: 20px;
    margin-bottom: 2rem;
  }
  .mission-label {
    font-size: 10px;
    letter-spacing: 0.28em;
    text-transform: uppercase;
    color: var(--gold);
    font-weight: 400;
    margin-bottom: 1rem;
  }
  .mission-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 2.4rem;
    font-weight: 300;
    color: var(--white);
    line-height: 1.15;
    margin-bottom: 1.5rem;
    letter-spacing: -0.01em;
  }
  .mission-body {
    font-size: 14.5px;
    line-height: 1.85;
    color: rgba(255,255,255,0.45);
    font-weight: 300;
  }

  .values-strip { display: none; }

  /* Core values redesigned */
  .core-values {
    margin-top: 5rem;
    padding-top: 4rem;
    border-top: 1px solid rgba(255,255,255,0.1);
    text-align: center;
    position: relative;
    z-index: 2;
  }
  .core-values-label {
    font-size: 10px;
    letter-spacing: 0.28em;
    text-transform: uppercase;
    color: var(--gold);
    margin-bottom: 2.5rem;
    font-weight: 400;
  }
  .core-values-grid {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0;
    flex-wrap: wrap;
  }
  .core-value-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    padding: 2rem 3.5rem;
    transition: background 0.3s;
    border: 1px solid rgba(255,255,255,0.06);
  }
  .core-value-item:hover { background: rgba(255,255,255,0.04); }
  .core-value-icon {
    font-size: 22px;
    color: var(--gold);
  }
  .core-value-name {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.5rem;
    font-weight: 400;
    color: var(--white);
    letter-spacing: 0.02em;
  }
  .core-value-sep {
    font-size: 1.8rem;
    color: rgba(196,164,74,0.3);
    padding: 0 0.5rem;
    align-self: center;
  }

  /* ========== PHILOSOPHY SECTION ========== */
  .section-philosophy {
    padding: 10rem 0;
    background: var(--cream);
    position: relative;
  }
  .philosophy-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6rem;
    align-items: center;
  }
  .philosophy-visual {
    position: relative;
  }
  .phil-card {
    aspect-ratio: 4/5;
    background: var(--navy);
    position: relative;
    overflow: hidden;
  }
  .phil-card img {
    width: 100%; height: 100%;
    object-fit: cover;
    filter: saturate(0.5) brightness(0.6);
    mix-blend-mode: luminosity;
    transition: transform 6s ease;
  }
  .phil-card:hover img { transform: scale(1.04); }
  .phil-card-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(8,40,90,0.8) 0%, transparent 55%);
  }
  .phil-card-text {
    position: absolute;
    bottom: 2.5rem; left: 2.5rem; right: 2.5rem;
  }
  .phil-card-tag {
    font-size: 10px;
    letter-spacing: 0.25em;
    text-transform: uppercase;
    color: var(--gold-light);
    margin-bottom: 8px;
    font-weight: 400;
  }
  .phil-card-quote {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.7rem;
    font-weight: 300;
    color: var(--white);
    font-style: italic;
    line-height: 1.35;
  }

  .phil-float {
    position: absolute;
    top: -2rem; right: -2rem;
    width: 160px; height: 160px;
    background: var(--cream);
    border: 1px solid var(--border);
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    padding: 1.5rem;
    text-align: center;
  }
  .phil-float-num {
    font-family: 'Cormorant Garamond', serif;
    font-size: 3.5rem;
    font-weight: 600;
    color: var(--navy);
    line-height: 1;
  }
  .phil-float-label {
    font-size: 10px;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: var(--muted);
    margin-top: 6px;
    line-height: 1.4;
    font-weight: 400;
  }

  .phil-pillars {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5px;
    background: rgba(14,69,130,0.1);
    border: 1.5px solid rgba(14,69,130,0.1);
    margin-top: 3rem;
  }
  .phil-pillar {
    background: var(--cream);
    padding: 2rem;
    transition: background 0.3s;
  }
  .phil-pillar:hover { background: rgba(14,69,130,0.04); }
  .phil-pillar-icon {
    font-size: 22px;
    color: var(--gold);
    margin-bottom: 10px;
  }
  .phil-pillar-name {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.3rem;
    font-weight: 600;
    color: var(--navy-deep);
    margin-bottom: 4px;
  }
  .phil-pillar-sub {
    font-size: 12.5px;
    color: var(--muted);
    font-weight: 300;
    line-height: 1.5;
  }

  /* ========== FUNCTIONS SECTION ========== */
  .section-functions {
    background: var(--white);
    padding: 9rem 0;
  }
  .functions-header {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4rem;
    align-items: end;
    margin-bottom: 5rem;
  }
  .functions-intro {
    font-size: 15px;
    line-height: 1.8;
    color: var(--muted);
    font-weight: 300;
  }
  .functions-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5px;
    background: var(--border);
    border: 1.5px solid var(--border);
  }
  .func-card {
    background: var(--white);
    padding: 2.5rem 2.2rem;
    position: relative;
    transition: background 0.3s;
    overflow: hidden;
  }
  .func-card::after {
    content: '';
    position: absolute;
    bottom: 0; left: 0;
    height: 2px;
    width: 0;
    background: var(--gold);
    transition: width 0.4s cubic-bezier(0.23, 1, 0.32, 1);
  }
  .func-card:hover { background: var(--cream); }
  .func-card:hover::after { width: 100%; }
  .func-num {
    font-family: 'Cormorant Garamond', serif;
    font-size: 3rem;
    font-weight: 300;
    color: rgba(14,69,130,0.08);
    line-height: 1;
    margin-bottom: 1.2rem;
    transition: color 0.3s;
  }
  .func-card:hover .func-num { color: rgba(14,69,130,0.15); }
  .func-text {
    font-size: 14px;
    line-height: 1.75;
    color: #4a5568;
    font-weight: 300;
  }

  /* ========== REVEAL ANIMATIONS ========== */
  .reveal {
    opacity: 0;
    transform: translateY(32px);
    transition: opacity 0.9s cubic-bezier(0.25, 0.46, 0.45, 0.94),
                transform 0.9s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  }
  .reveal.visible { opacity: 1; transform: translateY(0); }
  .reveal-delay-1 { transition-delay: 0.1s; }
  .reveal-delay-2 { transition-delay: 0.2s; }
  .reveal-delay-3 { transition-delay: 0.3s; }
  .reveal-delay-4 { transition-delay: 0.4s; }

  /* HERO ENTRANCE */
  .hero-left > * {
    opacity: 0;
    transform: translateY(24px);
    animation: heroIn 0.9s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
  }
  .hero-left > *:nth-child(1) { animation-delay: 0.3s; }
  .hero-left > *:nth-child(2) { animation-delay: 0.5s; }
  .hero-left > *:nth-child(3) { animation-delay: 0.65s; }
  .hero-left > *:nth-child(4) { animation-delay: 0.8s; }
  @keyframes heroIn {
    to { opacity: 1; transform: translateY(0); }
  }

  .hero-stat-strip .hero-stat {
    opacity: 0;
    transform: translateY(16px);
    animation: heroIn 0.7s ease forwards;
  }
  .hero-stat:nth-child(1) { animation-delay: 1.1s; }
  .hero-stat:nth-child(2) { animation-delay: 1.2s; }
  .hero-stat:nth-child(3) { animation-delay: 1.3s; }

  @media (max-width: 900px) {
    .hero { grid-template-columns: 1fr; }
    .hero-right { display: none; }
    .hero-left { padding: 6rem 2rem 8rem; }
    .container { padding: 0 2rem; }
    .history-grid, .mission-grid, .philosophy-grid, .functions-header { grid-template-columns: 1fr; gap: 3rem; }
    .values-strip { grid-template-columns: 1fr 1fr; }
    .functions-grid { grid-template-columns: 1fr 1fr; }
    .phil-pillars { grid-template-columns: 1fr; }
    .hero-stat-strip { flex-direction: column; }
    .hero-stat { border-right: none; border-bottom: 1px solid rgba(255,255,255,0.07); }
  }

</style>
@endsection


@section('content')

<!-- DÁN TOÀN BỘ HTML TRONG <body> CỦA BẠN VÀO ĐÂY -->
<section class="hero">
    <!-- Geometric SVG background -->
    <svg class="hero-geo" viewBox="0 0 1400 800" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
      <line x1="600" y1="0" x2="600" y2="800" stroke="rgba(255,255,255,0.04)" stroke-width="1"/>
      <line x1="0" y1="320" x2="600" y2="320" stroke="rgba(255,255,255,0.04)" stroke-width="1"/>
      <circle cx="100" cy="150" r="180" fill="none" stroke="rgba(196,164,74,0.07)" stroke-width="1"/>
      <circle cx="100" cy="150" r="100" fill="none" stroke="rgba(196,164,74,0.05)" stroke-width="1"/>
      <polygon points="480,60 540,160 420,160" fill="none" stroke="rgba(196,164,74,0.12)" stroke-width="1"/>
      <rect x="30" y="400" width="60" height="60" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="1"/>
      <rect x="50" y="420" width="60" height="60" fill="none" stroke="rgba(255,255,255,0.03)" stroke-width="1"/>
    </svg>

    <div class="hero-left">
      <div class="hero-eyebrow">
        <span class="hero-eyebrow-line"></span>
        <span>Thành lập năm 2013 · Cần Thơ, Việt Nam</span>
      </div>
      <h1 class="hero-title">
        Đào tạo<br>
        <em>kỹ sư</em><br>
        tương lai
      </h1>
      <p class="hero-sub">
        Trường Đại học Kỹ thuật - Công nghệ Cần Thơ — nơi nuôi dưỡng tài năng kỹ thuật và công nghệ cho sự phát triển bền vững của vùng Đồng bằng sông Cửu Long.
      </p>
      <div class="hero-cta">
        <a href="#history" class="btn-primaryy">
          <span>Khám phá</span>
          <i class="fas fa-arrow-down"></i>
        </a>
        <a href="#mission" class="btn-ghost">
          Sứ mạng <i class="fas fa-arrow-right"></i>
        </a>
      </div>
    </div>

    <div class="hero-right">
      <div class="hero-img-wrap">
        {{-- <img src="https://mykhanh.com/files/images/khu-du-lich-can-tho-moi-nhat.jpg" alt="CTUT campus"/> --}}
        <img src="images/system/khu-du-lich-can-tho-moi-nhat.jpg" alt="CTUT campus">
        <div class="hero-img-overlay"></div>
      </div>
      <!-- Rotating badge -->
      <div class="hero-badge">
        <svg viewBox="0 0 96 96" width="96" height="96">
          <defs>
            <path id="circle-text" d="M 48,48 m -32,0 a 32,32 0 1,1 64,0 a 32,32 0 1,1 -64,0"/>
          </defs>
          <text fill="rgba(196,164,74,0.9)" font-size="8" font-family="DM Sans" letter-spacing="3" font-weight="400">
            <textPath href="#circle-text">CTUT · EST. 2013 · CẦN THƠ ·</textPath>
          </text>
        </svg>
        <div class="hero-badge-center">
          <div class="hero-badge-dot"></div>
        </div>
      </div>
    </div>

  </section>

  <!-- ===== HISTORY ===== -->
  <section class="section-history" id="history">
    <div class="container">
      <div class="history-grid" id="history-grid">
        <div>
          <div class="kicker reveal">Hành trình</div>
          <h2 class="section-headline reveal reveal-delay-1">
            Lịch sử<br><em>phát triển</em>
          </h2>
          <div class="history-body reveal reveal-delay-2">
            <p>
              Tháng 8 năm 1981, Ủy ban nhân dân tỉnh Hậu Giang ra quyết định thành lập <strong>Trường Kinh tế - Kỹ thuật Hậu Giang</strong> — nền móng quan trọng cho lịch sử hình thành và phát triển Trường Đại học Kỹ thuật - Công nghệ Cần Thơ.
            </p>
            <p>
              Năm 1987, Trường đổi tên thành <strong>Trung tâm Đại học Tại chức Cần Thơ</strong>, liên kết đào tạo với gần 20 trường đại học lớn trên toàn quốc. Hơn 20.000 kỹ sư, cử nhân sau khi tốt nghiệp đã phát huy kiến thức, nhanh chóng trưởng thành, đóng góp cho sự phát triển của TP. Cần Thơ và các tỉnh ĐBSCL.
            </p>
            <p>
              Ngày 29 tháng 01 năm 2013, Thủ tướng Chính phủ ban hành <strong>Quyết định số 249/QĐ-TTg</strong> chính thức thành lập Trường Đại học Kỹ thuật - Công nghệ Cần Thơ. Trường đã đạt 05 cờ xuất sắc dẫn đầu khối thi đua các trường đại học, cao đẳng TP. Cần Thơ và Bằng khen của Thủ tướng Chính phủ năm 2023.
            </p>
            <div class="history-quote">
              <p>"Tất cả vì sinh viên thân yêu!"</p>
            </div>
          </div>
        </div>

        <div class="history-aside reveal reveal-delay-2">
          <ul class="milestone-list">
            <li>
              <div class="milestone-year">1981</div>
              <div class="milestone-text">Thành lập Trường Kinh tế - Kỹ thuật Hậu Giang</div>
            </li>
            <li>
              <div class="milestone-year">1987</div>
              <div class="milestone-text">Đổi tên thành Trung tâm Đại học Tại chức Cần Thơ, liên kết 20+ trường ĐH</div>
            </li>
            <li>
              <div class="milestone-year">2013</div>
              <div class="milestone-text">Thủ tướng ký QĐ 249/QĐ-TTg thành lập Trường ĐH Kỹ thuật - Công nghệ Cần Thơ</div>
            </li>
            <li>
              <div class="milestone-year">2030</div>
              <div class="milestone-text">Tầm nhìn trở thành ĐH theo định hướng ứng dụng, quản lý liên ngành kỹ thuật công nghệ, phù hợp xu thế Cách mạng công nghiệp lần thứ tư</div>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </section>

  <!-- ===== MISSION & VISION ===== -->
  <section class="section-mission" id="mission">
    <div class="mission-bg-text">CTUT</div>
    <div class="container">
      <div class="mission-grid reveal">
        <div class="mission-card">
          <div class="mission-icon"><i class="fas fa-flag"></i></div>
          <div class="mission-label">Sứ mạng</div>
          <h3 class="mission-title">Đào tạo nguồn nhân lực tiên tiến</h3>
          <p class="mission-body">
            Đào tạo nguồn nhân lực có đạo đức tốt, có chuyên môn cao, có khả năng tiếp cận nghiên cứu khoa học, ứng dụng và chuyển giao công nghệ tiên tiến trong lĩnh vực kỹ thuật, công nghệ, đáp ứng nhu cầu phát triển của TP. Cần Thơ, vùng ĐBSCL và cả nước.
          </p>
        </div>
        <div class="mission-divider"></div>
        <div class="mission-card">
          <div class="mission-icon"><i class="fas fa-binoculars"></i></div>
          <div class="mission-label">Tầm nhìn 2030</div>
          <h3 class="mission-title">Đại học ứng dụng</h3>
          <p class="mission-body">
            Đến năm 2030, Trường Đại học Kỹ thuật - Công nghệ Cần Thơ trở thành trường đại học theo định hướng ứng dụng, quản lý liên ngành kỹ thuật công nghệ phù hợp với xu thế phát triển trong thời kỳ Cách mạng công nghiệp lần thứ tư.
          </p>
        </div>
      </div>

      <!-- Giá trị cốt lõi -->
      <div class="core-values reveal reveal-delay-2">
        <div class="core-values-label">Giá trị cốt lõi</div>
        <div class="core-values-grid">
          <div class="core-value-item">
            <div class="core-value-icon"><i class="fas fa-medal"></i></div>
            <div class="core-value-name">Chất lượng</div>
          </div>
          <div class="core-value-sep">·</div>
          <div class="core-value-item">
            <div class="core-value-icon"><i class="fas fa-lightbulb"></i></div>
            <div class="core-value-name">Sáng tạo</div>
          </div>
          <div class="core-value-sep">·</div>
          <div class="core-value-item">
            <div class="core-value-icon"><i class="fas fa-bolt"></i></div>
            <div class="core-value-name">Năng động</div>
          </div>
          <div class="core-value-sep">·</div>
          <div class="core-value-item">
            <div class="core-value-icon"><i class="fas fa-seedling"></i></div>
            <div class="core-value-name">Phát triển</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ===== PHILOSOPHY ===== -->
  <section class="section-philosophy" id="philosophy">
    <div class="container">
      <div class="philosophy-grid">
        <div class="philosophy-visual reveal">
          <div class="phil-card">
            <img src="https://trangedu.com/wp-content/uploads/2020/04/dai-hoc-ky-thuat-cong-nghe-can-tho-tuyen-sinh.jpg" alt="Sinh viên CTUT"/>
            <div class="phil-card-overlay"></div>
            <div class="phil-card-text">
              <div class="phil-card-tag">Triết lý giáo dục</div>
              <div class="phil-card-quote">Đức trí · Kỹ năng<br>Sáng tạo · Hội nhập</div>
            </div>
          </div>
          <div class="phil-float">
            <div class="phil-float-num" id="years-count"></div>
            <div class="phil-float-label">Năm kiến tạo<br>tương lai</div>
          </div>
        </div>

        <div class="reveal reveal-delay-2">
          <div class="kicker">Triết lý & giá trị</div>
          <h2 class="section-headline">
            Bốn trụ cột<br><em>cốt lõi</em>
          </h2>
          <p style="font-size:15px;line-height:1.8;color:var(--muted);font-weight:300;margin-bottom:2rem;">
            Triết lý giáo dục của CTUT được xây dựng trên nền tảng bốn giá trị cốt lõi, định hướng toàn bộ hoạt động đào tạo và nghiên cứu của nhà trường.
          </p>
          <div class="phil-pillars">
            <div class="phil-pillar">
              <div class="phil-pillar-icon"><i class="fas fa-heart"></i></div>
              <div class="phil-pillar-name">Đức Trí</div>
              <div class="phil-pillar-sub">Đạo đức nghề nghiệp và trí tuệ vượt trội</div>
            </div>
            <div class="phil-pillar">
              <div class="phil-pillar-icon"><i class="fas fa-tools"></i></div>
              <div class="phil-pillar-name">Kỹ Năng</div>
              <div class="phil-pillar-sub">Thực hành chuyên sâu, sẵn sàng thực chiến</div>
            </div>
            <div class="phil-pillar">
              <div class="phil-pillar-icon"><i class="fas fa-lightbulb"></i></div>
              <div class="phil-pillar-name">Sáng Tạo</div>
              <div class="phil-pillar-sub">Tư duy đổi mới, giải pháp đột phá</div>
            </div>
            <div class="phil-pillar">
              <div class="phil-pillar-icon"><i class="fas fa-globe-asia"></i></div>
              <div class="phil-pillar-name">Hội Nhập</div>
              <div class="phil-pillar-sub">Kết nối toàn cầu, chuẩn quốc tế</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ===== FUNCTIONS ===== -->
  <section class="section-functions" id="functions">
    <div class="container">
      <div class="functions-header">
        <div class="reveal">
          <div class="kicker">Hoạt động</div>
          <h2 class="section-headline">Chức năng<br><em>& nhiệm vụ</em></h2>
        </div>
        <div class="functions-intro reveal reveal-delay-2">
          Trường thực hiện đầy đủ các chức năng của một cơ sở giáo dục đại học hiện đại, từ đào tạo chất lượng cao đến nghiên cứu ứng dụng và hợp tác quốc tế.
        </div>
      </div>

      <div class="functions-grid reveal reveal-delay-1">
        <div class="func-card">
          <div class="func-num">01</div>
          <p class="func-text">Xây dựng chiến lược, kế hoạch phát triển nhà trường theo định hướng phát triển kinh tế - xã hội TP. Cần Thơ và các tỉnh ĐBSCL.</p>
        </div>
        <div class="func-card">
          <div class="func-num">02</div>
          <p class="func-text">Đào tạo trình độ đại học, cao đẳng các chuyên ngành kỹ thuật công nghệ theo nhu cầu phát triển kinh tế - xã hội của thành phố và vùng.</p>
        </div>
        <div class="func-card">
          <div class="func-num">03</div>
          <p class="func-text">Nghiên cứu ứng dụng và chuyển giao công nghệ phục vụ phát triển kinh tế - xã hội của TP. Cần Thơ và các tỉnh Đồng bằng sông Cửu Long.</p>
        </div>
        <div class="func-card">
          <div class="func-num">04</div>
          <p class="func-text">Hợp tác với các tổ chức kinh tế, giáo dục, văn hóa, y tế, nghiên cứu khoa học trong nước và nước ngoài.</p>
        </div>
        <div class="func-card">
          <div class="func-num">05</div>
          <p class="func-text">Phát triển các chương trình đào tạo theo mục tiêu xác định; bảo đảm sự liên thông giữa các chương trình và trình độ đào tạo.</p>
        </div>
        <div class="func-card">
          <div class="func-num">06</div>
          <p class="func-text">Tổ chức bộ máy; tuyển dụng, quản lý, bồi dưỡng đội ngũ giảng viên, cán bộ quản lý đủ về chất lượng, số lượng và cân đối cơ cấu.</p>
        </div>
        <div class="func-card">
          <div class="func-num">07</div>
          <p class="func-text">Tuyển sinh và quản lý người học; bảo đảm quyền và lợi ích hợp pháp của giảng viên, viên chức, nhân viên và người học.</p>
        </div>
        <div class="func-card">
          <div class="func-num">08</div>
          <p class="func-text">Tự đánh giá chất lượng đào tạo và chịu sự kiểm định chất lượng giáo dục theo quy định.</p>
        </div>
        <div class="func-card">
          <div class="func-num">09</div>
          <p class="func-text">Quản lý, sử dụng đất đai, trường sở, trang thiết bị và tài chính theo quy định của pháp luật; huy động, quản lý các nguồn lực.</p>
        </div>
        <div class="func-card">
          <div class="func-num">10</div>
          <p class="func-text">Thực hiện chế độ thông tin, báo cáo và chịu sự kiểm tra, thanh tra của Bộ Giáo dục và Đào tạo, các bộ ngành và UBND TP. Cần Thơ.</p>
        </div>
        <div class="func-card">
          <div class="func-num">11</div>
          <p class="func-text">Thực hiện các nhiệm vụ và quyền hạn khác theo quy định của pháp luật hiện hành.</p>
        </div>
      </div>
    </div>
  </section>

@endsection

@section('js')
<script>
    document.getElementById('years-count').textContent = new Date().getFullYear() - 1981;

    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(e) {
            if (e.isIntersecting) e.target.classList.add('visible');
        });
    }, { threshold: 0.12 });

    document.querySelectorAll('.reveal').forEach(function(el) {
        observer.observe(el);
    });
</script>
<script src="{{ asset('js/trangchu.js') }}?v={{ filemtime(public_path('js/trangchu.js')) }}"></script>
@endsection