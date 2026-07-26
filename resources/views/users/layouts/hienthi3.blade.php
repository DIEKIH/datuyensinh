
<div class="backlink-v2 content">
    <a href="/">Trang chủ</a>
    <span>/</span>
    <a class="last-a">{{ $level1->name }}</a>
</div>

@section('title', 'Thông tin tuyển sinh')

<section class="ts-section">
    <div class="ts-wrap container">

        {{-- ── HERO ── --}}
        <div class="ts-hero-stats">
    @forelse ($highlight_stats as $stat)
        <div class="ts-stat">
            @if (!empty($stat->icon))
                <span class="ts-stat-ic">
                    <i class="{{ $stat->icon }}"></i>
                </span>
            @endif

            <span class="ts-stat-n">{{ $stat->so_luong }}</span>
            <span class="ts-stat-l">{{ $stat->title }}</span>
        </div>
    @empty
        
    @endforelse
</div>

        {{-- ── TABS ── --}}
        <div class="ts-tabbar" role="tablist">
            <button class="ts-tab active" role="tab" aria-selected="true" onclick="switchTab('dhcq',this)">
                <i class="fas fa-university"></i><span>Đại học chính quy</span>
            </button>
            <button class="ts-tab" role="tab" aria-selected="false" onclick="switchTab('vhvl',this)">
                <i class="fas fa-briefcase"></i><span>Vừa học vừa làm</span>
            </button>
        </div>

        {{-- ══════════ LAYOUT 2 CỘT ══════════ --}}
        <div class="ts-layout">

            {{-- ════ CỘT TRÁI ════ --}}
            <div class="ts-main-col">

                {{-- ── PANEL ĐHCQ ── --}}
                <div id="panel-dhcq" class="ts-panel active">
<div class="ts-top-row">
                    {{-- 1. Thông tin tuyển sinh --}}
                    <div class="ts-card ts-card--anim-1">
                        <div class="ts-card-head">
                            <div class="ts-head-ic" style="background:#fef2f2;color:#c0392b"><i class="fas fa-bell"></i></div>
                            <h2 class="ts-card-title">Thông tin tuyển sinh</h2>
                        </div>
                        <ul class="ts-list">
                            @forelse ($ttts_posts as $post)
                                @php
                                    $isNew = \Carbon\Carbon::parse($post->ngaydang)->diffInDays(now()) <= 3;
                                    $postUrl = url('thong-tin-tuyen-sinh/' . $post->slug);
                                @endphp
                                <li class="ts-item">
                                    <span class="ts-item-bar"></span>
                                    <div class="ts-item-inner">
                                        <a href="{{ $postUrl }}" class="ts-item-title">
                                            @if ($isNew)<span class="ts-new-badge"><span class="ts-new-badge-dot"></span>new</span>@endif
                                            {{ $post->tieude }}
                                        </a>
                                        <time class="ts-item-date">
                                            <i class="fas fa-calendar-alt"></i>
                                            {{ \Carbon\Carbon::parse($post->ngaydang)->format('d/m/Y') }}
                                        </time>
                                    </div>
                                </li>
                                @if (!$loop->last)<li class="ts-sep"></li>@endif
                            @empty
                                <li class="ts-empty">Chưa có bài viết.</li>
                            @endforelse
                        </ul>
                    </div>

                    {{-- 2. Phương thức xét tuyển ĐHCQ --}}
                    <div class="ts-card ts-card--anim-2">
                        <div class="ts-card-head ts-card-head--between">
                            <div class="ts-head-left">
                                <div class="ts-head-ic" style="background:#edf5ff;color:#1d5fbf"><i class="fas fa-route"></i></div>
                                <h2 class="ts-card-title">Phương thức xét tuyển — Đại học chính quy</h2>
                            </div>
                            <span class="ts-card-meta">{{ count($dhcq_posts) }} phương thức</span>
                        </div>
                        <div class="ts-pt-grid">
                            @forelse ($dhcq_posts as $i => $post)
                                @php
                                    $isNew = \Carbon\Carbon::parse($post->ngaydang)->diffInDays(now()) <= 3;
                                    $postUrl = url('thong-tin-tuyen-sinh/phuong-thuc-xet-tuyen/dai-hoc-chinh-quy/' . $post->slug);
                                    $colors = [['bg'=>'#edf5ff','c'=>'#1d5fbf'],['bg'=>'#ecfdf3','c'=>'#15803d'],['bg'=>'#fdf6e3','c'=>'#9a6f0e'],['bg'=>'#f5f0ff','c'=>'#5b21b6']];
                                    $cl = $colors[$i % 4];
                                @endphp
                                <a href="{{ $postUrl }}" class="ts-pt-card">
                                    <div class="ts-pt-icon" style="background:{{ $cl['bg'] }}">
                                        <span class="ts-pt-num" style="color:{{ $cl['c'] }}">{{ $i + 1 }}</span>
                                    </div>
                                    <div class="ts-pt-name">
                                        @if ($isNew)<span class="ts-new-badge"><span class="ts-new-badge-dot"></span>new</span>@endif
                                        {{ $post->tieude }}
                                    </div>
                                    <time class="ts-pt-date">{{ \Carbon\Carbon::parse($post->ngaydang)->format('d/m/Y') }}</time>
                                </a>
                            @empty
                                <p class="ts-empty">Chưa có bài viết.</p>
                            @endforelse
                        </div>
                    </div>
</div>
                    {{-- 3. Quiz gợi ý ngành --}}
                    <div class="ts-card ts-card--anim-3">
                        <div class="ts-card-head ts-card-head--between">
                            <div class="ts-head-left">
                                <div class="ts-head-ic" style="background:#f5f0ff;color:#5b21b6"><i class="fas fa-magic"></i></div>
                                <h2 class="ts-card-title">Gợi ý ngành phù hợp</h2>
                            </div>
                            <span class="ts-card-meta" id="quiz-step-label">Trả lời 5 câu hỏi</span>
                        </div>
                        <div id="quiz-area" class="ts-quiz-wrap"></div>
                    </div>

                </div>{{-- /panel-dhcq --}}

                {{-- ── PANEL VHVL ── --}}
                <div id="panel-vhvl" class="ts-panel">
<div class="ts-top-row">
                    {{-- 1. Thông tin tuyển sinh VHVL --}}
                    <div class="ts-card ts-card--anim-1">
                        <div class="ts-card-head">
                            <div class="ts-head-ic" style="background:#fef2f2;color:#c0392b"><i class="fas fa-bell"></i></div>
                            <h2 class="ts-card-title">Thông tin tuyển sinh</h2>
                        </div>
                        <ul class="ts-list">
                            @forelse ($ttts_posts as $post)
                                @php
                                    $isNew = \Carbon\Carbon::parse($post->ngaydang)->diffInDays(now()) <= 3;
                                    $postUrl = url('thong-tin-tuyen-sinh/' . $post->slug);
                                @endphp
                                <li class="ts-item">
                                    <span class="ts-item-bar"></span>
                                    <div class="ts-item-inner">
                                        <a href="{{ $postUrl }}" class="ts-item-title">
                                            @if ($isNew)<span class="ts-new-badge"><span class="ts-new-badge-dot"></span>new</span>@endif
                                            {{ $post->tieude }}
                                        </a>
                                        <time class="ts-item-date">
                                            <i class="fas fa-calendar-alt"></i>
                                            {{ \Carbon\Carbon::parse($post->ngaydang)->format('d/m/Y') }}
                                        </time>
                                    </div>
                                </li>
                                @if (!$loop->last)<li class="ts-sep"></li>@endif
                            @empty
                                <li class="ts-empty">Chưa có bài viết.</li>
                            @endforelse
                        </ul>
                    </div>

                    {{-- 2. Phương thức VHVL --}}
                    <div class="ts-card ts-card--anim-2">
                        <div class="ts-card-head ts-card-head--between">
                            <div class="ts-head-left">
                                <div class="ts-head-ic" style="background:#edf5ff;color:#1d5fbf"><i class="fas fa-route"></i></div>
                                <h2 class="ts-card-title">Phương thức xét tuyển — Vừa học vừa làm</h2>
                            </div>
                            <span class="ts-card-meta">{{ count($vhvl_posts) }} phương thức</span>
                        </div>
                        <div class="ts-pt-grid">
                            @forelse ($vhvl_posts as $i => $post)
                                @php
                                    $isNew = \Carbon\Carbon::parse($post->ngaydang)->diffInDays(now()) <= 3;
                                    $postUrl = url('thong-tin-tuyen-sinh/phuong-thuc-xet-tuyen/vua-hoc-vua-lam/' . $post->slug);
                                    $colors = [['bg'=>'#edf5ff','c'=>'#1d5fbf'],['bg'=>'#ecfdf3','c'=>'#15803d'],['bg'=>'#fdf6e3','c'=>'#9a6f0e']];
                                    $cl = $colors[$i % 3];
                                @endphp
                                <a href="{{ $postUrl }}" class="ts-pt-card">
                                    <div class="ts-pt-icon" style="background:{{ $cl['bg'] }}">
                                        <span class="ts-pt-num" style="color:{{ $cl['c'] }}">{{ $i + 1 }}</span>
                                    </div>
                                    <div class="ts-pt-name">
                                        @if ($isNew)<span class="ts-new-badge"><span class="ts-new-badge-dot"></span>new</span>@endif
                                        {{ $post->tieude }}
                                    </div>
                                    <time class="ts-pt-date">{{ \Carbon\Carbon::parse($post->ngaydang)->format('d/m/Y') }}</time>
                                </a>
                            @empty
                                <p class="ts-empty">Chưa có bài viết.</p>
                            @endforelse
                        </div>
                    </div>
</div>
                    {{-- 3. Liên hệ VHVL --}}
                    <div class="ts-card ts-card--anim-3">
                        <div class="ts-card-head">
                            <div class="ts-head-ic" style="background:#ecfdf3;color:#15803d"><i class="fas fa-headset"></i></div>
                            <h2 class="ts-card-title">Liên hệ tuyển sinh VHVL</h2>
                        </div>
                        <div class="ts-contact-grid">
                            <div class="ts-contact-item">
                                <i class="fas fa-phone-alt ts-contact-ic"></i>
                                <span class="ts-contact-label">Hotline VHVL</span>
                                <span class="ts-contact-val">02923.898.167</span>
                                <span class="ts-contact-sub">Thứ 2–7, 8:00–20:00</span>
                            </div>
                            <div class="ts-contact-item">
                                <i class="fas fa-envelope ts-contact-ic"></i>
                                <span class="ts-contact-label">Email</span>
                                <span class="ts-contact-val">tuvantuyensinh@ctuet.edu.vn</span>
                                <span class="ts-contact-sub">Phản hồi trong 24h</span>
                            </div>
                            <div class="ts-contact-item">
                                <i class="fas fa-map-marker-alt ts-contact-ic"></i>
                                <span class="ts-contact-label">Địa chỉ</span>
                                <span class="ts-contact-val">256 Nguyễn Văn Cừ,Cái Khế,Cần Thơ</span>
                                <span class="ts-contact-sub">Cần Thơ</span>
                            </div>
                        </div>
                    </div>

                </div>{{-- /panel-vhvl --}}

            </div>{{-- /ts-main-col --}}

            {{-- ════ CỘT PHẢI — SIDEBAR ════ --}}
            <aside class="ts-sidebar">

                {{-- CTA --}}
                {{-- <div class="ts-cta">
                    <div class="ts-cta-icon"><i class="fas fa-paper-plane"></i></div>
                    <div class="ts-cta-title">Đăng ký tư vấn ngay</div>
                    <p class="ts-cta-sub">Nhận thông tin chi tiết và hỗ trợ tuyển sinh miễn phí từ nhà trường.</p>
                    <a href="https://xettuyen.ctut.edu.vn" class="ts-cta-btn ts-cta-btn--primary" target="_blank">
                        <i class="fas fa-globe"></i>Cổng xét tuyển
                    </a>
                    <a href="#" class="ts-cta-btn ts-cta-btn--ghost">
                        <i class="fas fa-file-alt"></i>Nộp hồ sơ trực tuyến
                    </a>
                </div> --}}

                {{-- Đăng ký nhanh --}}
                <div class="ts-sb-card">
                    <div class="ts-sb-head">
                        <div class="ts-sb-head-ic" style="background:#edf5ff;color:#1d5fbf"><i class="fas fa-external-link-alt"></i></div>
                        <h3 class="ts-sb-head-title">Đăng ký nhanh</h3>
                    </div>
                    <div class="ts-ql-list">
                        <a href="https://xettuyen.ctut.edu.vn" class="ts-ql-item" target="_blank" rel="noopener">
                            <div class="ts-ql-ic ts-ql-ic--blue"><i class="fas fa-globe"></i></div>
                            <div class="ts-ql-body">
                                <span class="ts-ql-name">Cổng xét tuyển trực tuyến</span>
                                <span class="ts-ql-sub">xettuyen.ctut.edu.vn</span>
                            </div>
                            <i class="fas fa-arrow-right ts-ql-arr"></i>
                        </a>
                        <a href="#" class="ts-ql-item" target="_blank" rel="noopener">
                            <div class="ts-ql-ic ts-ql-ic--fb"><i class="fab fa-facebook-f"></i></div>
                            <div class="ts-ql-body">
                                <span class="ts-ql-name">Fanpage tuyển sinh CTUT</span>
                                <span class="ts-ql-sub">Tin tức mới nhất</span>
                            </div>
                            <i class="fas fa-arrow-right ts-ql-arr"></i>
                        </a>
                        <a href="#" class="ts-ql-item" target="_blank" rel="noopener">
                            <div class="ts-ql-ic ts-ql-ic--green"><i class="fas fa-file-download"></i></div>
                            <div class="ts-ql-body">
                                <span class="ts-ql-name">Đề án tuyển sinh 2026</span>
                                <span class="ts-ql-sub">Tải file PDF đầy đủ</span>
                            </div>
                            <i class="fas fa-arrow-right ts-ql-arr"></i>
                        </a>
                    </div>
                </div>

                {{-- Liên hệ tuyển sinh --}}
                <div class="ts-sb-card">
                    <div class="ts-sb-head">
                        <div class="ts-sb-head-ic" style="background:#ecfdf3;color:#15803d"><i class="fas fa-headset"></i></div>
                        <h3 class="ts-sb-head-title">Liên hệ tuyển sinh</h3>
                    </div>
                    <div class="ts-sb-contact">
                        <div class="ts-sb-row">
                            <div class="ts-sb-row-ic"><i class="fas fa-phone-alt"></i></div>
                            <span class="ts-sb-row-lbl">Hotline</span>
                            <span class="ts-sb-row-val"><a href="tel:02923898167">02923.898.167</a></span>
                        </div>
                        <div class="ts-sb-row">
                            <div class="ts-sb-row-ic"><i class="fas fa-envelope"></i></div>
                            <span class="ts-sb-row-lbl">Email</span>
                            <span class="ts-sb-row-val" style="font-size:11px"><a href="mailto:tuvantuyensinh@ctuet.edu.vn">tuvantuyensinh@ctuet.edu.vn</a></span>
                        </div>
                        <div class="ts-sb-row">
                            <div class="ts-sb-row-ic"><i class="fas fa-clock"></i></div>
                            <span class="ts-sb-row-lbl">Giờ làm việc</span>
                            <span class="ts-sb-row-val" style="font-size:11px">T2–T6, 7:30–17:00</span>
                        </div>
                        <div class="ts-sb-row">
                            <div class="ts-sb-row-ic"><i class="fas fa-map-marker-alt"></i></div>
                            <span class="ts-sb-row-lbl">Địa chỉ</span>
                            <span class="ts-sb-row-val" style="font-size:11px">256 Nguyễn Văn Cừ, Cái Khế, Cần Thơ</span>
                        </div>
                    </div>
                </div>

            </aside>{{-- /ts-sidebar --}}

        </div>{{-- /ts-layout --}}

    </div>
</section>

<style>
/* ============================================================
   THÔNG TIN TUYỂN SINH
============================================================ */

.ts-stat-ic{
  width:30px;
  height:30px;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  margin-bottom:6px;
  border-radius:10px;
  background:#f1f6ff;
  color:var(--ts-blue);
  font-size:13px;
}

*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}

:root{
  --ts-navy:#0b2f5b;
  --ts-navy2:#123f75;
  --ts-blue:#1d5fbf;
  --ts-gold:#c9922b;
  --ts-red:#c62828;
  --ts-green:#15803d;
  --ts-purple:#6d28d9;
  --ts-text:#1f2937;
  --ts-muted:#64748b;
  --ts-line:#e2e8f0;
  --ts-soft:#f8fafc;
  --ts-card:#ffffff;
}

@keyframes ts-fade-up{
  from{opacity:0;transform:translateY(16px)}
  to{opacity:1;transform:translateY(0)}
}

/* ── SECTION ── */
.ts-section{background:var(--ts-soft);font-family:'Be Vietnam Pro',system-ui,-apple-system,sans-serif;color:var(--ts-text);font-size:13.5px;line-height:1.6;padding-bottom:52px}

/* ── WRAP ── */
.ts-wrap{padding-top:22px}

/* ── HERO ── */
.ts-hero{
  background:#fff;border:1px solid var(--ts-line);border-radius:20px;
  padding:28px 32px;margin-bottom:22px;
  box-shadow:0 1px 8px rgba(0,0,0,.05);
  animation:ts-fade-up .3s ease both;
}
.ts-eyebrow{
  display:inline-flex;align-items:center;gap:8px;margin-bottom:12px;
  padding:5px 13px;border:1px solid rgba(29,95,191,.14);border-radius:999px;
  background:#f1f6ff;color:var(--ts-blue);font-size:11px;font-weight:800;
  letter-spacing:.08em;text-transform:uppercase;
}
.ts-eyebrow-dot{
  width:7px;height:7px;border-radius:50%;background:var(--ts-gold);
  box-shadow:0 0 0 4px rgba(201,146,43,.14);
}
.ts-page-title{
  font-size:clamp(24px,3vw,38px);font-weight:850;color:var(--ts-navy);
  letter-spacing:-.03em;line-height:1.2;margin-bottom:8px;
}
.ts-page-desc{color:var(--ts-muted);font-size:14px;line-height:1.75;max-width:620px;margin-bottom:20px}
.ts-hero-stats{
  display:grid;grid-template-columns:repeat(3,minmax(0,1fr));
  border:1px solid var(--ts-line);border-radius:14px;overflow:hidden;
}
.ts-stat{
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  padding:14px 12px;border-right:1px solid var(--ts-line);text-align:center;
}
.ts-stat:last-child{border-right:none}
.ts-stat-n{font-size:20px;font-weight:850;color:var(--ts-navy)}
.ts-stat-l{font-size:11px;color:var(--ts-muted);font-weight:600;margin-top:4px}

/* ── TABS ── */
.ts-tabbar{
  display:inline-flex;gap:5px;padding:5px;margin-bottom:20px;
  background:#fff;border:1px solid var(--ts-line);border-radius:14px;
  box-shadow:0 2px 10px rgba(15,42,86,.05);
}
.ts-tab{
  display:inline-flex;align-items:center;gap:8px;
  min-height:40px;
  border:none;border-radius:10px;background:transparent;
  color:var(--ts-muted);font-family:inherit;font-size:13px;font-weight:750;
  cursor:pointer;white-space:nowrap;
  transition:background .15s,color .15s,box-shadow .15s;
}
.ts-tab i{font-size:13px}
.ts-tab:hover{color:var(--ts-navy);background:#f5f8fc}
.ts-tab.active{
  color:#1e293b;background:#fff;
  border:1px solid var(--ts-line);
  box-shadow:0 1px 4px rgba(0,0,0,.08);
}

/* ── LAYOUT 2 CỘT ── */
.ts-layout{
  display:grid;
  grid-template-columns:minmax(0,1fr) 260px;
  gap:22px;
  align-items:start;
}

/* ── PANELS ── */
.ts-panel{display:none;flex-direction:column;gap:16px}
.ts-panel.active{display:flex}

/* ── CARDS ── */
.ts-card{
  background:#fff;border:1px solid var(--ts-line);border-radius:18px;
  overflow:hidden;
  box-shadow:0 6px 24px rgba(15,42,86,.06);
  transition:box-shadow .18s,border-color .18s;
}
.ts-card:hover{
  border-color:rgba(29,95,191,.18);
  box-shadow:0 12px 36px rgba(15,42,86,.09);
}
.ts-card--anim-1{animation:ts-fade-up .32s .04s ease both}
.ts-card--anim-2{animation:ts-fade-up .32s .09s ease both}
.ts-card--anim-3{animation:ts-fade-up .32s .14s ease both}

.ts-card-head{
  display:flex;align-items:center;gap:10px;
  padding:13px 16px;border-bottom:1px solid #edf1f7;background:#fff;
}
.ts-card-head--between{justify-content:space-between}
.ts-head-left{display:flex;align-items:center;gap:10px;min-width:0}
.ts-head-ic{
  width:34px;height:34px;display:inline-flex;align-items:center;justify-content:center;
  flex-shrink:0;border-radius:11px;font-size:14px;
}
.ts-card-title{margin:0;color:var(--ts-navy);font-size:14px;font-weight:800;line-height:1.35}
.ts-card-meta{
  flex-shrink:0;padding:4px 10px;border-radius:999px;
  background:#f3f6fb;color:#64748b;font-size:11px;font-weight:750;
}

/* ── LIST BÀI VIẾT ── */
.ts-list{margin:0;padding:6px;list-style:none}
.ts-item{display:flex;overflow:hidden;border-radius:12px;transition:background .15s}
.ts-item:hover{background:#f6f9fd}
.ts-item-bar{
  width:3px;flex-shrink:0;border-radius:999px;
  margin:12px 0 12px 4px;background:#cbd5e1;transition:background .15s;
}
.ts-item:hover .ts-item-bar{background:#64748b}
.ts-item-inner{flex:1;min-width:0;padding:11px 14px 11px 12px}
.ts-item-title{
  display:-webkit-box;overflow:hidden;
  color:#243244;font-size:13.5px;font-weight:720;line-height:1.55;
  text-decoration:none;-webkit-line-clamp:2;-webkit-box-orient:vertical;
  transition:color .15s;
}
.ts-item-title:hover{color:var(--ts-blue)}
.ts-item-date{
  display:inline-flex;align-items:center;gap:5px;
  margin-top:6px;color:#8a97aa;font-size:11px;font-style:normal;font-weight:600;
}
.ts-item-date i{color:var(--ts-gold);font-size:10px}
.ts-sep{height:1px;margin:0 10px;background:#f0f3f8;list-style:none}
.ts-new-badge{
  display:inline-flex;align-items:center;gap:4px;margin-right:6px;
  padding:2px 7px;border-radius:999px;background:#fef2f2;border:1px solid #fecaca;
  color:#b91c1c;font-size:9px;font-weight:800;letter-spacing:.06em;
  text-transform:uppercase;vertical-align:middle;
}
.ts-new-badge-dot{width:4px;height:4px;border-radius:50%;background:#dc2626}
.ts-empty{padding:28px 16px;color:#8a97aa;font-size:13px;text-align:center;list-style:none}

/* ── PHƯƠNG THỨC ── */
.ts-pt-grid{display:grid;grid-template-columns:1fr;gap:4px;padding:6px}
.ts-pt-card{
  display:flex;align-items:center;gap:12px;
  padding:9px 12px;border:1px solid #eaeff7;border-radius:14px;
  background:#fff;color:inherit;text-decoration:none;
  transition:border-color .15s,background .15s,transform .15s,box-shadow .15s;
}
.ts-pt-card::after{
  content:'\f061';font-family:'Font Awesome 5 Free';font-weight:900;
  color:#c7d2e2;font-size:11px;margin-left:auto;transition:all .15s;
}
.ts-pt-card:hover{
  transform:translateY(-1px);border-color:#c3d0e8;
  background:#f7faff;box-shadow:0 4px 12px rgba(0,0,0,.05);
}
.ts-pt-card:hover::after{color:var(--ts-blue);transform:translateX(2px)}
.ts-pt-icon{
  width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;
  flex-shrink:0;border-radius:9px;
}
.ts-pt-num{font-size:12px;font-weight:800}
.ts-pt-name{flex:1;min-width:0;color:#243244;font-size:13px;font-weight:750;line-height:1.45}
.ts-pt-date{color:#8a97aa;font-size:11px;font-style:normal;font-weight:600;white-space:nowrap}

/* ── CONTACT ── */
.ts-contact-grid{display:grid;grid-template-columns:repeat(3,1fr)}
.ts-contact-item{
  display:flex;flex-direction:column;gap:4px;padding:18px;
  border-right:1px solid #edf1f7;background:linear-gradient(180deg,#fff,#fbfdff);
}
.ts-contact-item:last-child{border-right:none}
.ts-contact-ic{
  width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;
  border-radius:10px;background:#f3f6fb;color:var(--ts-navy);font-size:13px;margin-bottom:4px;
}
.ts-contact-label{color:#8a97aa;font-size:11px;font-weight:700}
.ts-contact-val{color:#1f2937;font-size:13.5px;font-weight:820;word-break:break-word}
.ts-contact-sub{color:#8a97aa;font-size:11px}

/* ── QUIZ ── */
.ts-quiz-wrap{padding:16px}
.ts-quiz-step-bar{display:flex;gap:5px;margin-bottom:16px}
.ts-quiz-step-dot{height:4px;flex:1;border-radius:999px;background:#e5ebf3;transition:background .18s}
.ts-quiz-step-dot.done{background:linear-gradient(90deg,var(--ts-navy),var(--ts-blue))}
.ts-quiz-q{margin-bottom:12px;color:#1f2937;font-size:14px;font-weight:800;line-height:1.6}
.ts-quiz-opts{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.ts-quiz-opt{
  display:flex;align-items:flex-start;gap:10px;width:100%;min-height:52px;
  padding:11px 12px;border:1px solid #e5ebf3;border-radius:14px;
  background:#fff;color:#243244;font-family:inherit;font-size:13px;font-weight:660;
  line-height:1.45;text-align:left;cursor:pointer;
  transition:all .15s;
}
.ts-quiz-opt i{
  width:26px;height:26px;display:inline-flex;align-items:center;justify-content:center;
  flex-shrink:0;border-radius:9px;background:#f3f6fb;color:#728096;font-size:12px;
  transition:all .15s;
}
.ts-quiz-opt:hover{border-color:rgba(29,95,191,.24);background:#f6f9ff;transform:translateY(-1px)}
.ts-quiz-opt.selected{
  border-color:rgba(29,95,191,.45);
  background:linear-gradient(180deg,#f0f6ff,#fff);color:var(--ts-blue);
}
.ts-quiz-opt.selected i{background:linear-gradient(135deg,var(--ts-navy),var(--ts-blue));color:#fff}
.ts-quiz-nav{display:flex;align-items:center;gap:8px;margin-top:14px}
.ts-quiz-progress{margin-right:auto;color:#8a97aa;font-size:11.5px}
.ts-quiz-btn,.ts-quiz-restart{
  display:inline-flex;align-items:center;justify-content:center;gap:6px;
  min-height:36px;padding:0 14px;border:1px solid #e1e7f0;border-radius:10px;
  background:#fff;color:#334155;font-family:inherit;font-size:12.5px;font-weight:800;
  cursor:pointer;transition:all .15s;
}
.ts-quiz-btn:hover:not(:disabled),.ts-quiz-restart:hover{background:#f6f9fd;border-color:#c5d0df}
.ts-quiz-btn:disabled{opacity:.4;cursor:not-allowed}
.ts-quiz-btn.primary{background:#1e293b;color:#fff;border-color:#1e293b}
.ts-quiz-btn.primary:hover:not(:disabled){background:#0f172a}
.ts-quiz-restart{margin-top:8px}
.ts-result-label{margin-bottom:12px;color:#8a97aa;font-size:10.5px;font-weight:850;letter-spacing:.08em;text-transform:uppercase}
.ts-nganh-card{
  display:flex;align-items:flex-start;gap:11px;margin-bottom:9px;padding:13px;
  border:1px solid #eaeff7;border-radius:15px;background:#fff;
  color:inherit;text-decoration:none;
  transition:all .15s;
}
.ts-nganh-card:hover{border-color:rgba(29,95,191,.26);background:#f4f8ff;transform:translateY(-1px)}
.ts-nc-rank{
  width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;
  flex-shrink:0;border:1px solid #e1e7f0;border-radius:50%;
  background:#f3f6fb;color:#64748b;font-size:11px;font-weight:850;
}
.ts-nc-rank.top{border-color:#cbd5e1;background:#f1f5f9;color:#1e293b}
.ts-nc-body{flex:1;min-width:0}
.ts-nc-name{margin-bottom:5px;color:#243244;font-size:13.5px;font-weight:820;line-height:1.4;transition:color .15s}
.ts-nganh-card:hover .ts-nc-name{color:var(--ts-blue)}
.ts-nc-row{display:flex;align-items:center;flex-wrap:wrap;gap:5px}
.ts-nc-tag{padding:2px 7px;border-radius:999px;background:#f1f5f9;color:#64748b;font-size:10.5px;font-weight:700}
.ts-nc-match{padding:2px 7px;border-radius:999px;background:#ecfdf3;color:var(--ts-green);font-size:10.5px;font-weight:850}
.ts-nc-sub{margin-top:5px;color:#8a97aa;font-size:11px;line-height:1.5}

/* ── SIDEBAR ── */
.ts-sidebar{display:flex;flex-direction:column;gap:16px;position:sticky;top:77px}

/* CTA */
.ts-cta{
  background:#fff;border:1px solid var(--ts-line);border-radius:18px;
  padding:20px 18px;text-align:center;
  box-shadow:0 6px 24px rgba(15,42,86,.06);
  animation:ts-fade-up .32s .05s ease both;
}
.ts-cta-icon{
  width:44px;height:44px;margin:0 auto 12px;
  background:#f1f6ff;border:1px solid #d0ddf7;border-radius:13px;
  display:inline-flex;align-items:center;justify-content:center;
  font-size:18px;color:var(--ts-navy);
}
.ts-cta-title{font-size:14px;font-weight:850;color:var(--ts-navy);margin-bottom:6px;line-height:1.3}
.ts-cta-sub{font-size:12px;color:var(--ts-muted);margin-bottom:16px;line-height:1.65}
.ts-cta-btn{
  display:flex;align-items:center;justify-content:center;gap:7px;
  width:100%;padding:11px 0;border-radius:11px;
  font-family:inherit;font-size:13px;font-weight:800;
  text-decoration:none;cursor:pointer;border:none;
  transition:all .15s;margin-bottom:8px;
}
.ts-cta-btn:last-child{margin-bottom:0}
.ts-cta-btn--primary{background:var(--ts-navy);color:#fff}
.ts-cta-btn--primary:hover{background:var(--ts-navy2)}
.ts-cta-btn--ghost{background:#fff;color:var(--ts-navy);border:1px solid #c8d4e8}
.ts-cta-btn--ghost:hover{background:#f0f5ff}

/* Sidebar cards */
.ts-sb-card{
  background:#fff;border:1px solid var(--ts-line);border-radius:18px;overflow:hidden;
  box-shadow:0 6px 24px rgba(15,42,86,.06);
  animation:ts-fade-up .32s .1s ease both;
}
.ts-sb-head{
  display:flex;align-items:center;gap:9px;
  padding:12px 15px;border-bottom:1px solid #edf1f7;
  background:#fafcff;
}
.ts-sb-head-ic{
  width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;
  border-radius:8px;font-size:12px;flex-shrink:0;
}
.ts-sb-head-title{font-size:13px;font-weight:800;color:var(--ts-navy);margin:0}

/* Quicklinks */
.ts-ql-list{display:flex;flex-direction:column}
.ts-ql-item{
  display:flex;align-items:center;gap:11px;padding:11px 15px;
  text-decoration:none;border-bottom:1px solid #f0f3f8;transition:background .13s;
}
.ts-ql-item:last-child{border-bottom:none}
.ts-ql-item:hover{background:#f6f9fd}
.ts-ql-ic{
  width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;
  border-radius:10px;font-size:13px;flex-shrink:0;
}
.ts-ql-ic--blue{background:#edf5ff;color:var(--ts-blue)}
.ts-ql-ic--green{background:#ecfdf3;color:var(--ts-green)}
.ts-ql-ic--fb{background:#edf5ff;color:#1877f2}
.ts-ql-body{flex:1;min-width:0}
.ts-ql-name{display:block;font-size:12.5px;font-weight:750;color:#243244;line-height:1.3}
.ts-ql-sub{display:block;font-size:10.5px;color:#8a97aa;margin-top:1px}
.ts-ql-item:hover .ts-ql-name{color:var(--ts-blue)}
.ts-ql-arr{color:#c3ccda;font-size:11px;flex-shrink:0;transition:all .13s}
.ts-ql-item:hover .ts-ql-arr{color:var(--ts-blue);transform:translateX(2px)}

/* Contact rows */
.ts-sb-contact{display:flex;flex-direction:column}
.ts-sb-row{
  display:flex;align-items:center;gap:10px;padding:10px 15px;
  border-bottom:1px solid #f0f3f8;
}
.ts-sb-row:last-child{border-bottom:none}
.ts-sb-row-ic{
  width:26px;height:26px;display:inline-flex;align-items:center;justify-content:center;
  border-radius:7px;background:#f3f6fb;color:var(--ts-navy);font-size:11px;flex-shrink:0;
}
.ts-sb-row-lbl{font-size:11px;color:var(--ts-muted);font-weight:600;flex:1}
.ts-sb-row-val{font-size:12px;font-weight:800;color:var(--ts-navy);text-align:right;word-break:break-all}
.ts-sb-row-val a{color:var(--ts-blue);text-decoration:none}

/* ── RESPONSIVE ── */
@media(max-width:1024px){
  .ts-layout{grid-template-columns:1fr}
  .ts-sidebar{position:static;display:grid;grid-template-columns:1fr 1fr;gap:16px;flex-direction:unset}
  .ts-cta{grid-column:1/-1}
}
@media(max-width:700px){
  .ts-sidebar{grid-template-columns:1fr}
  .ts-cta{grid-column:auto}
  .ts-hero{padding:20px 18px;border-radius:16px}
  .ts-page-title{font-size:22px}
  .ts-hero-stats{grid-template-columns:1fr}
  .ts-stat{border-right:none;border-bottom:1px solid var(--ts-line)}
  .ts-stat:last-child{border-bottom:none}
  .ts-tabbar{display:grid;grid-template-columns:1fr 1fr;width:100%}
  .ts-tab{justify-content:center}
  .ts-quiz-opts{grid-template-columns:1fr}
  .ts-quiz-nav{flex-direction:column;align-items:stretch}
  .ts-quiz-progress{margin-right:0}
  .ts-quiz-btn{width:100%}
  .ts-contact-grid{grid-template-columns:1fr}
  .ts-contact-item{border-right:none;border-bottom:1px solid #edf1f7}
  .ts-contact-item:last-child{border-bottom:none}
}
@media(prefers-reduced-motion:reduce){
  *,*::before,*::after{animation-duration:.01ms!important;transition-duration:.01ms!important}
}


/* ── HÀNG THÔNG TIN + PHƯƠNG THỨC ── */
.ts-top-row{
  display:grid;
  grid-template-columns:minmax(0,1.05fr) minmax(320px,.95fr);
  gap:16px;
  align-items:stretch;
}

.ts-top-row > .ts-card{
  height:100%;
}

@media(max-width:900px){
  .ts-top-row{
    grid-template-columns:1fr;
  }
}
@media (max-width: 991.98px) {
    .backlink-v2 {
        margin-top: 20px;
    }
}
</style>

<script>
/* ---- Tab switching ---- */
function switchTab(id, el) {
    document.querySelectorAll('.ts-tab').forEach(function(t) {
        t.classList.remove('active');
        t.setAttribute('aria-selected', 'false');
    });
    document.querySelectorAll('.ts-panel').forEach(function(p) {
        p.classList.remove('active');
    });
    el.classList.add('active');
    el.setAttribute('aria-selected', 'true');
    document.getElementById('panel-' + id).classList.add('active');
}

document.addEventListener('DOMContentLoaded', function() {
    if (window.location.hash === '#vhvl') {
        switchTab('vhvl', document.querySelectorAll('.ts-tab')[1]);
    }
    quizRender();
});

/* ======================================================
   QUIZ
====================================================== */
var quizQuestions = [
    {
        q: 'Khi bắt đầu một việc mới, bạn thường thích cách tiếp cận nào hơn?',
        opts: [
            { icon: 'fa-puzzle-piece', text: 'Phân tích vấn đề, tìm quy luật rồi xây dựng cách giải',
              vector: { logic:5, data:3, technical:2, creativity:1, communication:0, management:1, language:0, legal:0, experiment:0, fieldwork:0 } },
            { icon: 'fa-users', text: 'Trao đổi với mọi người để hiểu nhu cầu và mục tiêu',
              vector: { logic:1, data:1, technical:0, creativity:2, communication:5, management:4, language:3, legal:1, experiment:0, fieldwork:0 } },
            { icon: 'fa-chart-column', text: 'Thu thập dữ liệu rồi mới đưa ra quyết định',
              vector: { logic:3, data:5, technical:1, creativity:0, communication:1, management:3, language:0, legal:0, experiment:1, fieldwork:0 } },
            { icon: 'fa-screwdriver-wrench', text: 'Quan sát thực tế rồi thử nghiệm trực tiếp',
              vector: { logic:2, data:1, technical:5, creativity:1, communication:0, management:1, language:0, legal:0, experiment:4, fieldwork:4 } }
        ]
    },
    {
        q: 'Bạn thấy hứng thú nhất với kiểu hoạt động nào?',
        opts: [
            { icon: 'fa-laptop-code', text: 'Tạo ra sản phẩm hoặc công cụ mới',
              vector: { logic:5, data:3, technical:4, creativity:2, communication:0, management:1, language:0, legal:0, experiment:0, fieldwork:0 } },
            { icon: 'fa-lightbulb', text: 'Biến ý tưởng thành nội dung dễ tiếp cận',
              vector: { logic:1, data:0, technical:0, creativity:5, communication:4, management:2, language:4, legal:0, experiment:0, fieldwork:0 } },
            { icon: 'fa-list-check', text: 'Kiểm tra độ chính xác và phát hiện sai sót',
              vector: { logic:3, data:4, technical:1, creativity:0, communication:1, management:2, language:1, legal:4, experiment:4, fieldwork:0 } },
            { icon: 'fa-gears', text: 'Làm cho quy trình hoạt động hiệu quả hơn',
              vector: { logic:3, data:3, technical:4, creativity:1, communication:1, management:5, language:0, legal:0, experiment:1, fieldwork:2 } }
        ]
    },
    {
        q: 'Bạn cảm thấy thoải mái hơn khi làm việc với điều gì?',
        opts: [
            { icon: 'fa-table', text: 'Con số, dữ liệu và báo cáo',
              vector: { logic:3, data:5, technical:1, creativity:0, communication:0, management:3, language:0, legal:0, experiment:0, fieldwork:0 } },
            { icon: 'fa-file-lines', text: 'Văn bản, lập luận và thông tin chi tiết',
              vector: { logic:3, data:1, technical:0, creativity:1, communication:3, management:2, language:5, legal:5, experiment:0, fieldwork:0 } },
            { icon: 'fa-cubes', text: 'Thiết bị, mô hình hoặc hệ thống thực tế',
              vector: { logic:2, data:1, technical:5, creativity:1, communication:0, management:1, language:0, legal:0, experiment:2, fieldwork:4 } },
            { icon: 'fa-palette', text: 'Hình ảnh, nội dung và trải nghiệm',
              vector: { logic:0, data:0, technical:0, creativity:5, communication:4, management:1, language:3, legal:0, experiment:0, fieldwork:0 } }
        ]
    },
    {
        q: 'Nếu tham gia một nhóm, bạn thường phù hợp với vai trò nào?',
        opts: [
            { icon: 'fa-code-branch', text: 'Xử lý phần kỹ thuật hoặc phần khó',
              vector: { logic:5, data:2, technical:5, creativity:1, communication:0, management:0, language:0, legal:0, experiment:1, fieldwork:1 } },
            { icon: 'fa-user-tie', text: 'Sắp xếp công việc và theo dõi tiến độ',
              vector: { logic:2, data:2, technical:1, creativity:0, communication:3, management:5, language:1, legal:1, experiment:0, fieldwork:0 } },
            { icon: 'fa-comments', text: 'Kết nối, trình bày và thuyết phục',
              vector: { logic:0, data:0, technical:0, creativity:3, communication:5, management:3, language:5, legal:2, experiment:0, fieldwork:0 } },
            { icon: 'fa-magnifying-glass', text: 'Rà soát chi tiết và phát hiện vấn đề',
              vector: { logic:4, data:4, technical:1, creativity:0, communication:0, management:1, language:1, legal:3, experiment:4, fieldwork:0 } }
        ]
    },
    {
        q: 'Bạn mong muốn công việc tương lai tạo ra giá trị gì?',
        opts: [
            { icon: 'fa-mobile-screen', text: 'Giúp mọi người làm việc nhanh và thông minh hơn',
              vector: { logic:5, data:4, technical:4, creativity:1, communication:0, management:1, language:0, legal:0, experiment:0, fieldwork:0 } },
            { icon: 'fa-chart-pie', text: 'Giúp tổ chức đưa ra quyết định tốt hơn',
              vector: { logic:2, data:5, technical:0, creativity:0, communication:2, management:5, language:0, legal:0, experiment:0, fieldwork:0 } },
            { icon: 'fa-industry', text: 'Giúp quy trình hoặc hệ thống vận hành hiệu quả',
              vector: { logic:3, data:2, technical:5, creativity:0, communication:0, management:3, language:0, legal:0, experiment:2, fieldwork:4 } },
            { icon: 'fa-seedling', text: 'Tạo ra sản phẩm hữu ích cho sức khỏe và đời sống',
              vector: { logic:1, data:1, technical:1, creativity:1, communication:0, management:0, language:0, legal:0, experiment:5, fieldwork:2 } }
        ]
    }
];

var quizNganhs = @json($quiz_nganhs ?? []);
var quizCur = 0;
var quizAnswers = [];
var vectorKeys = ['logic','data','technical','creativity','communication','management','language','legal','experiment','fieldwork'];

function escHtml(v) {
    return String(v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function getUserVector() {
    var u = {};
    vectorKeys.forEach(function(k){ u[k]=0; });
    quizAnswers.forEach(function(a){
        var v = a.vector || {};
        vectorKeys.forEach(function(k){ u[k] += Number(v[k]||0); });
    });
    return u;
}

function cosine(a, b) {
    var d=0, nA=0, nB=0;
    vectorKeys.forEach(function(k){
        var x=Number(a[k]||0), y=Number(b[k]||0);
        d+=x*y; nA+=x*x; nB+=y*y;
    });
    return (nA===0||nB===0) ? 0 : d/(Math.sqrt(nA)*Math.sqrt(nB));
}

function qScore(n) {
    return Math.round(cosine(getUserVector(), n.profile_vector||{}) * 100);
}

function quizRender() {
    var area  = document.getElementById('quiz-area');
    var label = document.getElementById('quiz-step-label');
    if (!area) return;

    if (quizCur >= quizQuestions.length) { quizResult(); return; }

    var q = quizQuestions[quizCur];
    label.textContent = 'Câu ' + (quizCur+1) + '/' + quizQuestions.length;

    var dots = '';
    for (var i=0; i<quizQuestions.length; i++) {
        dots += '<div class="ts-quiz-step-dot'+(i<=quizCur?' done':'')+'"></div>';
    }

    var opts = quizQuestions[quizCur].opts.map(function(o, idx){
        return '<button class="ts-quiz-opt" data-idx="'+idx+'" onclick="qPick(this,'+idx+')">'
            + '<i class="fas '+o.icon+'"></i>' + escHtml(o.text) + '</button>';
    }).join('');

    var back = quizCur > 0
        ? '<button class="ts-quiz-btn" onclick="qBack()"><i class="fas fa-arrow-left"></i> Quay lại</button>'
        : '';
    var nextLbl = quizCur === quizQuestions.length-1
        ? 'Xem kết quả <i class="fas fa-magic"></i>'
        : 'Tiếp theo <i class="fas fa-arrow-right"></i>';

    area.innerHTML =
        '<div class="ts-quiz-step-bar">'+dots+'</div>' +
        '<div class="ts-quiz-q">'+escHtml(q.q)+'</div>' +
        '<div class="ts-quiz-opts">'+opts+'</div>' +
        '<div class="ts-quiz-nav">' +
        '<span class="ts-quiz-progress">Chọn một đáp án</span>' +
        back +
        '<button class="ts-quiz-btn primary" id="ts-next-btn" disabled onclick="qNext()">'+nextLbl+'</button>' +
        '</div>';

    if (quizAnswers[quizCur]) {
        var pi = -1;
        quizQuestions[quizCur].opts.forEach(function(o,i){ if(o.text===quizAnswers[quizCur].text) pi=i; });
        if (pi !== -1) {
            var b = area.querySelector('[data-idx="'+pi+'"]');
            if (b) b.classList.add('selected');
            document.getElementById('ts-next-btn').disabled = false;
        }
    }
}

function qPick(el, idx) {
    document.querySelectorAll('#quiz-area .ts-quiz-opt').forEach(function(b){ b.classList.remove('selected'); });
    el.classList.add('selected');
    quizAnswers[quizCur] = quizQuestions[quizCur].opts[idx];
    var nb = document.getElementById('ts-next-btn');
    nb.disabled = false;
    nb.innerHTML = quizCur === quizQuestions.length-1
        ? 'Xem kết quả <i class="fas fa-magic"></i>'
        : 'Tiếp theo <i class="fas fa-arrow-right"></i>';
}

function qNext() { if (!quizAnswers[quizCur]) return; quizCur++; quizRender(); }
function qBack() { quizCur = Math.max(0, quizCur-1); quizRender(); }

function quizResult() {
    document.getElementById('quiz-step-label').textContent = 'Kết quả gợi ý';
    var area = document.getElementById('quiz-area');

    if (!quizNganhs || quizNganhs.length === 0) {
        area.innerHTML = '<div class="ts-empty">Chưa có dữ liệu ngành.</div>'
            + '<button class="ts-quiz-restart" onclick="qRestart()"><i class="fas fa-redo"></i> Làm lại</button>';
        return;
    }

    var ranked = quizNganhs.map(function(n){ return {n:n, score:qScore(n)}; })
        .sort(function(a,b){ return b.score!==a.score ? b.score-a.score : (b.n.chitieu||0)-(a.n.chitieu||0); })
        .slice(0, 5);

    var h = '<div class="ts-result-label">Ngành phù hợp với bạn</div>';
    ranked.forEach(function(item, i){
        h += '<a href="'+escHtml(item.n.url||'#')+'" class="ts-nganh-card">'
            + '<div class="ts-nc-rank'+(i===0?' top':'')+'">'+( i+1)+'</div>'
            + '<div class="ts-nc-body">'
            + '<div class="ts-nc-name">'+escHtml(item.n.name||'Ngành đào tạo')+'</div>'
            + '<div class="ts-nc-row">'
            + '<span class="ts-nc-tag">'+escHtml(item.n.ma||'—')+'</span>'
            + '<span class="ts-nc-tag">'+escHtml(item.n.khoa||'—')+'</span>'
            + '<span class="ts-nc-match">'+item.score+'% phù hợp</span>'
            + '</div>'
            + '<div class="ts-nc-sub">Chỉ tiêu: '+escHtml(item.n.chitieu||'—')
            + ' · Học phí: '+escHtml(item.n.hocphi||'—')
            + ' · Tổ hợp: '+escHtml(item.n.tohops||'—')+'</div>'
            + '</div>'
            + '<i class="fas fa-arrow-right" style="font-size:12px;color:#cbd5e1;flex-shrink:0;margin-top:3px"></i>'
            + '</a>';
    });
    h += '<button class="ts-quiz-restart" onclick="qRestart()"><i class="fas fa-redo"></i> Làm lại từ đầu</button>';
    area.innerHTML = '<div>'+h+'</div>';
}

function qRestart() {
    quizCur = 0; quizAnswers = [];
    document.getElementById('quiz-step-label').textContent = 'Trả lời '+quizQuestions.length+' câu hỏi';
    quizRender();
}
</script>
