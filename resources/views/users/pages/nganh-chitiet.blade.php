@extends('users.layouts.app')

@section('title', $nganh->ten_nganh . ' — Tuyển sinh')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/nganh-chitiet.css') }}">
@endsection

@section('content')

<meta name="nganh-id" content="{{ $nganh->id }}">

{{-- ── HERO ─────────────────────────────────────────────── --}}
<section class="nd-hero">
    <img class="nd-hero__bg"
         src="{{ $nganh->image_url ?: 'https://images.unsplash.com/photo-1517077304055-6e89abbf09b0?w=1200&q=80' }}"
         alt="{{ $nganh->ten_nganh }}">
    <div class="nd-hero__overlay"></div>
    <div class="nd-hero__content">
        <div class="container">
            <span class="nd-hero__badge">
                <i class="fas fa-graduation-cap"></i>
                {{ $nganh->loai_hinh ?? 'Đại học chính quy' }}
            </span>
            <h1 class="nd-hero__title">{{ $nganh->ten_nganh }}</h1>
            <p class="nd-hero__subtitle">{{ $nganh->tomtat }}</p>
            <div class="nd-hero__tags">
                @if($nganh->ma_nganh)
                    <span class="nd-hero__tag">
                        <i class="fas fa-hashtag me-1"></i>Mã ngành: {{ $nganh->ma_nganh }}
                    </span>
                @endif
                @if($nganh->thoi_gian)
                    <span class="nd-hero__tag">
                        <i class="fas fa-clock me-1"></i>{{ $nganh->thoi_gian }}
                    </span>
                @endif
                @if($nganh->ten_khoa)
                    <span class="nd-hero__tag">
                        <i class="fas fa-university me-1"></i>{{ $nganh->ten_khoa }}
                    </span>
                @endif
            </div>
        </div>
    </div>
</section>

{{-- ── BREADCRUMB ───────────────────────────────────────── --}}
<nav class="nd-breadcrumb" aria-label="breadcrumb">
    <div class="container">
        <ol>
            <li><a href="/">Trang chủ</a></li>
            {{-- <li><a href="/tuyen-sinh">Tuyển sinh</a></li>
            <li><a href="/cac-nganh">Các ngành đào tạo</a></li> --}}
            <li><a href="#">Các ngành đào tạo</a></li>
            <li aria-current="page">{{ $nganh->ten_nganh }}</li>
        </ol>
    </div>
</nav>

{{-- ── QUICK INFO BAR ───────────────────────────────────── --}}
{{-- <div class="nd-quickbar">
    <div class="container">
        <div class="nd-quickbar__inner">
            @if($nganh->ma_nganh)
            <div class="nd-quickbar__item">
                <span class="nd-quickbar__label">Mã ngành</span>
                <span class="nd-quickbar__value">{{ $nganh->ma_nganh }}</span>
            </div>
            @endif
            @if($nganh->thoi_gian)
            <div class="nd-quickbar__item">
                <span class="nd-quickbar__label">Thời gian</span>
                <span class="nd-quickbar__value">{{ $nganh->thoi_gian }}</span>
            </div>
            @endif
            @if($nganh->so_tin_chi)
            <div class="nd-quickbar__item">
                <span class="nd-quickbar__label">Số tín chỉ</span>
                <span class="nd-quickbar__value">{{ $nganh->so_tin_chi }} TC</span>
            </div>
            @endif
            @if($nganh->chi_tieu)
            <div class="nd-quickbar__item">
                <span class="nd-quickbar__label">Chỉ tiêu</span>
                <span class="nd-quickbar__value">{{ number_format($nganh->chi_tieu) }}</span>
            </div>
            @endif
            @if($nganh->hoc_phi)
            <div class="nd-quickbar__item">
                <span class="nd-quickbar__label">Học phí / năm</span>
                <span class="nd-quickbar__value">{{ number_format($nganh->hoc_phi) }} đ</span>
            </div>
            @endif
            <div class="nd-quickbar__item">
                <span class="nd-quickbar__label">Hình thức xét</span>
                <span class="nd-quickbar__value" style="font-size:12px">THPTQG & học bạ</span>
            </div>
        </div>
    </div>
</div> --}}

{{-- ── BODY ─────────────────────────────────────────────── --}}
<div class="nd-body">
    <div class="container">
        <div class="nd-layout">

            {{-- ════ CỘT TRÁI ════ --}}
            <div class="nd-main">

                {{-- GIỚI THIỆU + STATS --}}
                <div class="nd-card nd-animate">
                    <h2 class="nd-section-title">
                        <i class="fas fa-info-circle"></i> Giới thiệu ngành
                    </h2>
                    <div class="nd-intro__text">
                        {!! $nganh->noidung !!}
                    </div>

                    {{-- STATS NỔI BẬT --}}
                    <div class="nd-stats">
                        @if($nganh->chi_tieu)
                        <div class="nd-stat">
                            <span class="nd-stat__number">{{ number_format($nganh->chi_tieu) }}</span>
                            <span class="nd-stat__label">Chỉ tiêu tuyển sinh năm nay</span>
                        </div>
                        @endif
                        @if($nganh->so_tin_chi)
                        <div class="nd-stat">
                            <span class="nd-stat__number">{{ $nganh->so_tin_chi }}</span>
                            <span class="nd-stat__label">Tín chỉ toàn khoá</span>
                        </div>
                        @endif
                        @if($nganh->hoc_phi)
                        <div class="nd-stat">
                            <span class="nd-stat__number">{{ number_format($nganh->hoc_phi / 1000000, 0) }}tr</span>
                            <span class="nd-stat__label">Học phí mỗi năm học</span>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- THÔNG TIN + TỔ HỢP (ngang nhau) --}}
                <div class="nd-double nd-animate">

                    {{-- THÔNG TIN ĐÀO TẠO --}}
                    <div class="nd-card">
                        <h2 class="nd-section-title">
                            <i class="fas fa-list-alt"></i> Thông tin đào tạo
                        </h2>
                        <ul class="nd-info-list">
                            @if($nganh->ma_nganh)
                            <li class="nd-info-list__item">
                                <div class="nd-info-list__icon"><i class="fas fa-hashtag"></i></div>
                                <div>
                                    <div class="nd-info-list__label">Mã ngành</div>
                                    <div class="nd-info-list__value">{{ $nganh->ma_nganh }}</div>
                                </div>
                            </li>
                            @endif
                            @if($nganh->bac_dao_tao)
                            <li class="nd-info-list__item">
                                <div class="nd-info-list__icon"><i class="fas fa-layer-group"></i></div>
                                <div>
                                    <div class="nd-info-list__label">Bậc đào tạo</div>
                                    <div class="nd-info-list__value">{{ $nganh->bac_dao_tao }}</div>
                                </div>
                            </li>
                            @endif
                            @if($nganh->thoi_gian)
                            <li class="nd-info-list__item">
                                <div class="nd-info-list__icon"><i class="fas fa-clock"></i></div>
                                <div>
                                    <div class="nd-info-list__label">Thời gian học</div>
                                    <div class="nd-info-list__value">{{ $nganh->thoi_gian }}</div>
                                </div>
                            </li>
                            @endif
                            {{-- @if($nganh->hinh_thuc_xet)
                            <li class="nd-info-list__item">
                                <div class="nd-info-list__icon"><i class="fas fa-file-alt"></i></div>
                                <div>
                                    <div class="nd-info-list__label">Hình thức xét tuyển</div>
                                    <div class="nd-info-list__value">{{ $nganh->hinh_thuc_xet }}</div>
                                </div>
                            </li>
                            @endif --}}
                            @if($nganh->ten_khoa)
                            <li class="nd-info-list__item">
                                <div class="nd-info-list__icon"><i class="fas fa-university"></i></div>
                                <div>
                                    <div class="nd-info-list__label">Khoa quản lý</div>
                                    <div class="nd-info-list__value">{{ $nganh->ten_khoa }}</div>
                                </div>
                            </li>
                            @endif
                            @if($nganh->co_so)
                            <li class="nd-info-list__item">
                                <div class="nd-info-list__icon"><i class="fas fa-map-marker-alt"></i></div>
                                <div>
                                    <div class="nd-info-list__label">Cơ sở đào tạo</div>
                                    <div class="nd-info-list__value">{{ $nganh->co_so }}</div>
                                </div>
                            </li>
                            @endif
                        </ul>
                    </div>

                    {{-- TỔ HỢP XÉT TUYỂN --}}
                    @if($toHopList->isNotEmpty())
                    <div class="nd-card">
                        <h2 class="nd-section-title">
                            <i class="fas fa-th"></i> Tổ hợp xét tuyển
                        </h2>
                        <div class="nd-tohop-list">
                            @foreach($toHopList as $toHop)
                            <div class="nd-tohop-item">
                                <span class="nd-tohop-item__ma">{{ $toHop->ma_to_hop }}</span>
                                <span class="nd-tohop-item__ten">{{ $toHop->ten_to_hop }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                </div>{{-- /nd-double --}}

            </div>{{-- /nd-main --}}

            {{-- ════ SIDEBAR ════ --}}
            <aside class="nd-sidebar">

                {{-- CTA --}}
                <div class="nd-cta nd-animate">
                    <i class="fas fa-paper-plane nd-cta__icon"></i>
                    <div class="nd-cta__title">Đăng ký tư vấn ngay</div>
                    <p class="nd-cta__sub">Nhận thông tin chi tiết và hỗ trợ tuyển sinh miễn phí từ nhà trường.</p>
                    <a href="/dang-ky-tu-van?nganh={{ $nganh->id }}" class="nd-cta__btn">
                        <i class="fas fa-paper-plane me-2"></i> Đăng ký tư vấn
                    </a>
                    <a href="/ho-so-truc-tuyen" class="nd-cta__btn nd-cta__btn--ghost">
                        <i class="fas fa-file-alt me-2"></i> Nộp hồ sơ trực tuyến
                    </a>
                </div>

                {{-- NGÀNH LIÊN QUAN --}}
                @if($nganhLienQuan->isNotEmpty())
                <div class="nd-sidebar-card nd-animate">
                    <div class="nd-sidebar-card__header">
    <i class="fas fa-university"></i> {{ 'Khoa: ' .( $nganh->ten_khoa) }}
</div>
                    <ul class="nd-related">
                        @foreach($nganhLienQuan as $related)
                        <li>
                            <a href="{{ route('nganh.chitiet', $related->slug ?? $related->id) }}"
                               class="nd-related__item">
                                <i class="fas fa-chevron-right"></i>
                                {{ $related->ten_nganh }}
                            </a>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif

                {{-- LIÊN HỆ --}}
                <div class="nd-sidebar-card nd-animate">
                    <div class="nd-sidebar-card__header">
                        <i class="fas fa-phone-alt"></i> Liên hệ tư vấn
                    </div>
                    <div class="nd-sidebar-card__body">
                        <div class="nd-sidebar-card__row">
                            <i class="fas fa-phone"></i>
                            <span>Hotline</span>
                            <strong>
                                <a href="tel:02923733333"
                                   style="color:var(--primary);text-decoration:none">0292 373 3333</a>
                            </strong>
                        </div>
                        <div class="nd-sidebar-card__row">
                            <i class="fas fa-envelope"></i>
                            <span>Email</span>
                            <strong style="font-size:12.5px">
                                <a href="mailto:tuyensinh@ctut.edu.vn"
                                   style="color:var(--primary);text-decoration:none">tuyensinh@ctut.edu.vn</a>
                            </strong>
                        </div>
                        <div class="nd-sidebar-card__row">
                            <i class="fas fa-clock"></i>
                            <span>Giờ làm việc</span>
                            <strong style="font-size:12.5px">7:00 – 17:00 (T2–T6)</strong>
                        </div>
                    </div>
                </div>

            </aside>

        </div>{{-- /nd-layout --}}
    </div>{{-- /container --}}
</div>{{-- /nd-body --}}

@endsection



@section('js')
    <script src="{{ asset('js/trangchu.js') }}?v={{ filemtime(public_path('js/trangchu.js')) }}"></script>
    <script src="{{ asset('js/nganh-chitiet.js') }}"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>



@endsection

