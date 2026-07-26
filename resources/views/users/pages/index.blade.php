@extends('users.layouts.app')

@section('title', 'Trang chủ')

@section('css')
    {{-- <link rel="stylesheet" href="/css/cacnganh.css"> --}}
@endsection

@section('content')
    <!-- Banner -->
    {{-- <section id="ts-banner-home">
        <div class="container">
            <div class="banner-home">
                <div class="swiper bannerSwiper">
                    <div class="swiper-wrapper" id="banner-list">
                    </div>

                   
                    <div class="swiper-pagination"></div>
                </div>
            </div>
        </div>
    </section> --}}

    <style>



/* ===============================
   LOAD EFFECT: anim-fadeup nhẹ, tinh tế
   Không đụng logic, không phá layout
================================ */
.anim-fadeup {
    opacity: 0;
    transform: translate3d(0, 10px, 0);
    transition:
        opacity 0.45s ease,
        transform 0.45s ease;
    transition-delay: var(--anim-delay, 0ms);
    will-change: opacity, transform;
    backface-visibility: hidden;
}

.anim-fadeup.anim-fadeup-show {
    opacity: 1;
    transform: translate3d(0, 0, 0);
}

@media (max-width: 991.98px) {
    .anim-fadeup {
        transform: translate3d(0, 8px, 0);
        transition-duration: 0.38s;
    }
}

@media (prefers-reduced-motion: reduce) {
    .anim-fadeup,
    .anim-fadeup.anim-fadeup-show {
        opacity: 1;
        transform: none;
        transition: none;
    }
}
        body {
            background: #f8fafc;
        }
       

        #ts-banner-home {
            width: 100%;
        }

        .banner-home {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
        }

        .banner-home:hover {
            cursor: pointer;
        }
.bannerSwiper {
            width: 100%;
            max-height: unset; 
        }

        .swiper-slide img {
            width: 100% ;
            height: auto;
            max-height: 500px;
            object-fit: contain;
            display: block;
        }
        /* .bannerSwiper {
            width: 100%;
            max-height: 500px;
        }

        .swiper-slide img {
            height: 100%;
            width: 100%;
            
        } */

        .swiper-pagination-bullet {
            background: #c0c0c0;
        }

        @media screen and (max-width: 768px) {
            #ts-banner-home {
                display: none;
            }

            /* .mobi-mt {
                margin-top: 30px !important;
            } */
        }

        #thongbaonho,
        #thongbaonho-mobile {
            max-height: 450px;
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: thin;
            scrollbar-color: #005da0 #f0f0f0;
        }

        #thongbaonho li,
        #thongbaonho-mobile li {
            margin-bottom: 8px !important;
            line-height: 1.4;
        }

        #thongbaonho::-webkit-scrollbar,
        #thongbaonho-mobile::-webkit-scrollbar {
            width: 4px;
        }

        #thongbaonho::-webkit-scrollbar-track,
        #thongbaonho-mobile::-webkit-scrollbar-track {
            background: #f0f0f0;
        }

        #thongbaonho::-webkit-scrollbar-thumb,
        #thongbaonho-mobile::-webkit-scrollbar-thumb {
            background: #005da0;
            border-radius: 4px;
        }


        /* === TIN TUC NHO === */
        .tin-tuc-nho-wrap {
            display: flex;
            flex-wrap: nowrap;
            gap: 16px;
            align-items: flex-start;
        }

        .col-nho {
            flex: 0 0 calc(20% - 13px);
            min-width: 0;
            width: calc(20% - 13px);
        }

        /* Ép ảnh nhỏ lại */
        .tin-tuc-nho-wrap .ratio-container {
            aspect-ratio: unset !important;
            height: 180px !important;
            position: relative !important;
            overflow: hidden !important;
        }

        .tin-tuc-nho-wrap .ratio-container figure {
            margin: 0;
            width: 100%;
            height: 100%;
        }

        .tin-tuc-nho-wrap .ratio-container img {
            position: absolute !important;
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
        }

        /* Title nhỏ */
        .tin-tuc-nho-wrap .news-title a {
            font-size: 15px !important;
            font-weight: 600 !important;
            line-height: 18px !important;
            display: -webkit-box !important;
            -webkit-line-clamp: 2 !important;
            -webkit-box-orient: vertical !important;
            overflow: hidden !important;
        }
        .tin-tuc-nho-wrap {
            align-items: stretch;
        }

        .tin-tuc-nho-wrap .col-nho {
            display: flex;
            flex-direction: column;
        }

        .tin-tuc-nho-wrap .col-nho > div {
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .tin-tuc-nho-wrap .news-content {
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .tin-tuc-nho-wrap .news-content .news-info {
            margin-top: auto !important;
            padding-top: 5px;
        }

        .tin-tuc-nho-wrap .col-nho .fake-news-blur {
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .tin-tuc-nho-wrap .col-nho .fake-news-blur .news-content {
            flex: 1;
        }

        @media (max-width: 768px) {
            .tin-tuc-nho-wrap {
                flex-wrap: wrap;
            }
            .col-nho {
                flex: 0 0 calc(50% - 5px);
            }
        }

        @media (max-width: 991.98px) {
            .col-lg-8 { order: 1; }
            #tin-tuc-nho-container { order: 2; }
            .col-lg-4 { order: 3; }
        }

        @media (max-width: 480px) {
            .col-nho {
                flex: 0 0 100%;
            }
        }

        

        .thongbao-box {
            padding-left: 16px;
        }

      @media (max-width: 991.98px) {
    #featured-news {
        min-height: unset !important;
        height: auto !important;
        max-height: unset !important;
    }

    /* Bắt tất cả ảnh trong featuredSwiper */
    #featured-news img,
    #featured-news figure img,
    #featured-news .news-img img,
    #featured-news .swiper-slide img {
        height: 220px !important;
        max-height: 220px !important;
        width: 100% !important;
        object-fit: cover !important;
    }

    #featured-news figure,
    #featured-news .news-img,
    #featured-news .news-img figure {
        height: 220px !important;
        max-height: 220px !important;
        overflow: hidden !important;
    }

    #featured-news .swiper-slide {
        height: auto !important;
    }

	

    .featuredSwiper .news-title a,
    .featuredSwiper .news-title h3 a {


        white-space: normal !important;
        display: -webkit-box !important;
        -webkit-line-clamp: 2 !important;
        -webkit-box-orient: vertical !important;
        overflow: hidden !important;
    }
	
	.featuredSwiper .news-title.one-line,
    .featuredSwiper .news-title.ellipsis-text,
    .featuredSwiper .one-line,
    .featuredSwiper .ellipsis-text {
        white-space: normal !important;
        overflow: visible !important;
        text-overflow: unset !important;
        display: block !important;

    }

.featuredSwiper .news-summary,
    .featuredSwiper .news-desc,
    .featuredSwiper p {
        display: -webkit-box !important;
        -webkit-line-clamp: 3 !important;
        -webkit-box-orient: vertical !important;
        overflow: hidden !important;
         margin-bottom: 0 !important; 
    }
	
	.featuredSwiper .news-title a {
        white-space: normal !important;
        overflow: visible !important;
        text-overflow: unset !important;
        display: block !important;

    }
}

@media (max-width: 991.98px) {
    #featured-news .swiper,
    #featured-news .featuredSwiper {
        height: auto !important;
    }
    
    #featured-news .swiper.h-100,
    #featured-news .featuredSwiper.h-100 {
        height: auto !important;
    }

    #featured-news .swiper-slide.swiper-slide-active {
        height: auto !important;
    }
}

        @media (min-width: 992px) {
            #featured-news {
                min-height: 420px;
            }

    /* THÊM: cố định chiều cao slide và ảnh trên desktop */
    .featuredSwiper .swiper-slide {
        height: 420px !important;
    }

    .featuredSwiper .news-img img {
        width: 100%;
        height: 300px !important;   /* cố định chiều cao ảnh */
        object-fit: cover;
    }

    /* THÊM: khi không có ảnh thì giữ placeholder */
    .featuredSwiper .news-img figure {
        height: 300px;
        background: #f0f4f8;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
}

        @media (max-width: 991.98px) {
            .d-lg-none .ts-title {
                margin-bottom: 20px !important;
            }
        }
        

        /* === FEATURED NEWS SWIPER === */
        :root {
    --thongbao-duration: 20s; /* mặc định, JS sẽ override */
}
.thongbao-track {
    animation-duration: var(--thongbao-duration);
}
/* === FEATURED NEWS SWIPER === */
.featuredSwiper {
    width: 100%;
    height: 100%;
}

.featuredSwiper .swiper-slide {
    height: auto;
}

.featuredSwiper .news-img img {
    width: 100%;
    max-height: 370px;
    object-fit: cover;
}

/* Nút prev / next */
.featured-swiper-prev,
.featured-swiper-next {
    display: none !important;
}
.featured-swiper-prev:hover,
.featured-swiper-next:hover {
    background: #005da0;
}
.featured-swiper-prev { left: 0; }
.featured-swiper-next { right: 0; }

/* Pagination dots */
.featured-swiper-pagination {
    text-align: center;
    
}
.featured-swiper-pagination .swiper-pagination-bullet {
    background: #005da0;
}

/* === THÔNG BÁO AUTO SCROLL === */
#thongbaonho-wrap {
    overflow: hidden;
    position: relative;
    flex: 1;
}

.thongbao-track {
    display: flex;
    flex-direction: column;
    animation: scrollThongBao 20s linear infinite;
}

.thongbao-track:hover {
    animation-play-state: paused;
}

@keyframes scrollThongBao {
    0%   { transform: translateY(0); }
    100% { transform: translateY(-50%); }
}

/* === THÔNG BÁO AUTO SCROLL === */
.thongbao-box {
    overflow: hidden !important; /* ẩn scrollbar gốc */
    position: relative;
}

/* Wrapper giới hạn chiều cao, ẩn overflow */
#thongbao-scroll-wrap,
#thongbao-scroll-wrap-mobile {
    overflow: hidden;
    height: 100%;
    min-height: 420px;
    position: relative;
}

@media (max-width: 991.98px) {
    #thongbao-scroll-wrap-mobile {
        min-height: unset;
        max-height: 300px;
    }
}

/* Track chứa 2 bản copy danh sách để loop liền mạch */
.thongbao-track {
    display: flex;
    flex-direction: column;
    will-change: transform;
    /* duration set bằng JS theo số item */
    animation: thongbaoScrollUp var(--tb-duration, 18s) linear infinite;
}

.thongbao-track:hover {
    animation-play-state: paused; /* dừng khi hover */
}

@keyframes thongbaoScrollUp {
    0%   { transform: translateY(0); }
    100% { transform: translateY(-50%); } 
    /* -50% vì track có 2 bản copy → đúng 1 vòng */
}

/* Ẩn hoàn toàn scrollbar trên thongbao-box */
.thongbao-box::-webkit-scrollbar { display: none; }
.thongbao-box { scrollbar-width: none; }


    </style>




<!-- Thay section Tin tức hiện tại bằng đoạn này -->
<section id="tin-tuc" class="ts-section mobi-mt mt-60 tin-tuc-section">
    <div class="container">
        <div class="row align-items-stretch">
            {{-- Cot Tin tuc --}}
            <div class="col-lg-8 pe-lg-5 d-flex flex-column">
                <div class="ts-title">
                    <div class="vertical-bar"></div>
                    <h2 class="title-text">Tin tức</h2>
                    <div class="line"></div>
                </div>
                {{-- <div class="flex-grow-1" id="featured-news" style="min-height: 420px;">
                </div> --}}
                <div class="flex-grow-1 position-relative" id="featured-news">
                    <div class="swiper featuredSwiper h-100">
                        <div class="swiper-wrapper" id="featured-news-wrapper">
                            {{-- JS render vào đây --}}
                        </div>
                        <div class="featured-swiper-pagination"></div>
                    </div>
                    <button class="featured-swiper-prev"><i class="fas fa-chevron-left"></i></button>
                    <button class="featured-swiper-next"><i class="fas fa-chevron-right"></i></button>
                </div>
            </div>

            {{-- Cot Thong bao - ẩn trên mobile --}}
            <div class="col-lg-4 d-flex flex-column d-none d-lg-flex">
                <div class="ts-title">
                    <div class="vertical-bar"></div>
                    <h2 class="title-text">Thông báo</h2>
                    <div class="line"></div>
                </div>
                {{-- <div class="thongbao-box flex-grow-1" style="min-height: 420px;">
                    <div class="list-with-marker h-100">
                        <ul class="mb-0 ps-0" id="thongbaonho"></ul>
                        <div id="offcanvas-wrapper"></div>
                    </div>
                </div> --}}
                <div class="thongbao-box flex-grow-1" style="min-height: 420px;">
                    <div class="list-with-marker h-100">
                        <ul class="mb-0 ps-0" id="thongbaonho">
                            {{-- 2 bản ul sẽ được JS clone vào đây --}}
                        </ul>
                    </div>
                    <div id="offcanvas-wrapper"></div>
                </div>
            </div>
        </div>

        {{-- Tin tuc nho --}}
        <div class="tin-tuc-nho-wrap mt-4" id="tin-tuc-nho-container">
        </div>

        {{-- Thong bao - chỉ hiện trên mobile, nằm sau tin tức nhỏ --}}
        <div class="d-lg-none mt-4">
            <div class="ts-title">
                <div class="vertical-bar"></div>
                <h2 class="title-text">Thông báo</h2>
                <div class="line"></div>
            </div>
            <div class="thongbao-box">
            <div class="list-with-marker">
                <ul class="mb-0 ps-0" id="thongbaonho-mobile"></ul>
            </div>
            <div id="offcanvas-wrapper-mobile"></div>
        </div>
        </div>
    </div>
</section>

    <style>
        .fake-news-card {
            position: relative;
            overflow: hidden;
        }

        /* Làm mờ toàn bộ nội dung */
        .fake-news-blur {
            opacity: 0.5;
            pointer-events: none;
            transition: none;
        }

        /* Overlay chữ "Xem thêm" */
        .fake-news-overlay {
            opacity: 0;
            transition: opacity 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .fake-news-card:hover .fake-news-overlay {
            opacity: 1;
            background: rgba(255, 255, 255, 0.15);
            /* lớp kính mờ nhẹ */
            backdrop-filter: saturate(150%) contrast(90%);
            /* tăng độ tương phản, chữ rõ hơn */
            /* backdrop-filter: blur(1px); */

        }


        /* Nút "Xem thêm" rõ ràng, không bị ảnh hưởng bởi overlay mờ */
        .fake-news-overlay .fw-bold {
            opacity: 1;
            background-color: #005da0 !important;
            color: white !important;
            z-index: 1;
        }

        @media (max-width: 991.98px) {
            .fake-news-overlay {
                opacity: 1;
                pointer-events: auto;
            }

            .fake-news-overlay {
                background: rgba(255, 255, 255, 0.3);
                /* backdrop-filter: blur(2px); */
            }
        }

        .ratio-container {
            position: relative;
            aspect-ratio: 4 / 3;
            overflow: hidden;
        }

        .ratio-container img {
            position: absolute;
            width: 100%;
            height: 100%;
            object-fit: cover;
            /* Giữ tỉ lệ, crop nếu cần */
        }

        .news-img img {
            width: 100%;
            max-height: 370px;
        }

        .news-content {
            margin-top: 10px;
        }

        .news-title a {
            font-size: 16px;
            font-weight: 700;
        }

        .left-title a {
            line-height: 24px;
        }

        .news-info {
            margin-top: 10px;
        }
        .news-date i {
            font-size: 14px;
            /* margin-right: 4px; */
        }

        .news-item-small {
            display: flex !important;
        }

        .hover01 figure img {
            transform: scale(1);
            transition: .3s ease-in-out;
        }

        .hover01 figure:hover img {
            transform: scale(1.2);
        }

        .hover01 figure {
            overflow: hidden;
        }


        .text-sub {
            color: #646464
        }
    </style>
    <style>
        .noti-item:hover {
            cursor: pointer;
        }

        .list-with-marker li {
            position: relative;
            padding-left: 1em;
            font-size: 16px;
            /* text-indent: -0.6em; */
        }

        .list-with-marker li::before {
            content: "•";
            position: absolute;
            font-size: 18px;
            left: 0;
            top: 0;
            /* transform: translate(0, -50%); */
            color: #000;
            font-weight: bold;
        }

        .video-container video {
            width: 100% !important;
            /* Đảm bảo video chiếm toàn bộ chiều rộng */
            height: auto;
            /* Đảm bảo tỷ lệ khung hình không bị thay đổi */
        }

        .more-link a {
            color: var(--primary-color);
        }
    </style>


    <!-- Thong so -->
    <section class="ts-section bg-overlay py-5 text-center">
        <div class="container stats-section">
            <div class="ts-title">
                <div class="line"></div>
            </div>
            <h4 class="fw-bold mb-5">NHỮNG CON SỐ NỔI BẬT</h4>

            <!-- Swiper -->
            <div class="swiper stats-swiper">
                <div class="swiper-wrapper">
                    <!-- Sinh viên -->
                    <div class="swiper-slide">
                        <div class="col-12">
                            <i class="fas fa-users highlight-icon"></i>
                            <div class="highlight-number">20,000</div>
                            <div>Sinh viên đang theo học</div>
                        </div>
                    </div>

                    <!-- Giảng viên -->
                    <div class="swiper-slide">
                        <div class="col-12">
                            <i class="fas fa-chalkboard-teacher highlight-icon"></i>
                            <div class="highlight-number">1,200</div>
                            <div>Giảng viên và cán bộ</div>
                        </div>
                    </div>

                    <!-- Ngành học -->
                    <div class="swiper-slide">
                        <div class="col-12">
                            <i class="fas fa-graduation-cap highlight-icon"></i>
                            <div class="highlight-number">35 +</div>
                            <div>Ngành đào tạo đại học & sau đại học</div>
                        </div>
                    </div>

                    <!-- Học bổng -->
                    <div class="swiper-slide">
                        <div class="col-12">
                            <i class="fas fa-gift highlight-icon"></i>
                            <div class="highlight-number">15 TỶ+</div>
                            <div>Học bổng trao hàng năm</div>
                        </div>
                    </div>
                    <!-- Sinh viên -->
                    <div class="swiper-slide">
                        <div class="col-12">
                            <i class="fas fa-users highlight-icon"></i>
                            <div class="highlight-number">20,000</div>
                            <div>Sinh viên đang theo học</div>
                        </div>
                    </div>

                    <!-- Giảng viên -->
                    <div class="swiper-slide">
                        <div class="col-12">
                            <i class="fas fa-chalkboard-teacher highlight-icon"></i>
                            <div class="highlight-number">1,200</div>
                            <div>Giảng viên và cán bộ</div>
                        </div>
                    </div>

                    <!-- Ngành học -->
                    <div class="swiper-slide">
                        <div class="col-12">
                            <i class="fas fa-graduation-cap highlight-icon"></i>
                            <div class="highlight-number">35 +</div>
                            <div>Ngành đào tạo đại học & sau đại học</div>
                        </div>
                    </div>

                    <!-- Học bổng -->
                    <div class="swiper-slide">
                        <div class="col-12">
                            <i class="fas fa-gift highlight-icon"></i>
                            <div class="highlight-number">15 TỶ+</div>
                            <div>Học bổng trao hàng năm</div>
                        </div>
                    </div>
                    <!-- Sinh viên -->
                    <div class="swiper-slide">
                        <div class="col-12">
                            <i class="fas fa-users highlight-icon"></i>
                            <div class="highlight-number">20,000</div>
                            <div>Sinh viên đang theo học</div>
                        </div>
                    </div>

                    <!-- Giảng viên -->
                    <div class="swiper-slide">
                        <div class="col-12">
                            <i class="fas fa-chalkboard-teacher highlight-icon"></i>
                            <div class="highlight-number">1,200</div>
                            <div>Giảng viên và cán bộ</div>
                        </div>
                    </div>

                    <!-- Ngành học -->
                    <div class="swiper-slide">
                        <div class="col-12">
                            <i class="fas fa-graduation-cap highlight-icon"></i>
                            <div class="highlight-number">35 +</div>
                            <div>Ngành đào tạo đại học & sau đại học</div>
                        </div>
                    </div>

                    <!-- Học bổng -->
                    <div class="swiper-slide">
                        <div class="col-12">
                            <i class="fas fa-gift highlight-icon"></i>
                            <div class="highlight-number">15 TỶ+</div>
                            <div>Học bổng trao hàng năm</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <style>
        .highlight-icon {
            font-size: 40px;
            color: var(--primary-light-color);
        }

        .highlight-number {
            font-size: 30px;
            font-weight: bold;
            color: var(--primary-light-color);
        }

        .stats-section {
            position: relative;
            z-index: 1;
        }

        /* Swiper customization */
        .stats-swiper {
            padding: 0 20px;
        }

        .stats-swiper .swiper-slide {
            height: auto;
        }

        .swiper-pagination-bullet {
            background: var(--primary-light-color);
        }

        .swiper-button-next,
        .swiper-button-prev {
            color: var(--primary-light-color);
        }

        /* Responsive cho mobile */
        @media (max-width: 768px) {
            .stats-swiper {
                padding: 0 10px;
            }
        }

        /* === SỰ KIỆN CARD === */
/* === SỰ KIỆN CARD === */
#sukien-container {
    display: grid !important;;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

#sukien-container > div {
    width: 100% !important;
    padding: 0 !important;
}

.sk-card {
    display: flex;
    flex-direction: row;
    border-radius: 8px;
    border: 1px solid #e8edf2;
    background: #fff;
    overflow: hidden;
    transition: box-shadow 0.25s ease, transform 0.25s ease;
    height: 140px;
}

.sk-card:hover {
    box-shadow: 0 8px 28px rgba(0, 93, 160, 0.14);
    transform: translateY(-3px);
}

/* Ảnh trái */
.sk-thumb {
    width: 150px;
    min-width: 150px;
    height: 100%;
    overflow: hidden;
    background: #eef2f7;
    flex-shrink: 0;
    position: relative;
}

.sk-thumb figure {
    margin: 0;
    width: 100%;
    height: 100%;
}

.sk-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.35s ease;
}

.sk-thumb img[src=""],
.sk-thumb img:not([src]) {
    visibility: hidden;
}

.sk-thumb::after {
    content: '';
    position: absolute;
    inset: 0;
    background: #eef2f7 url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='40' height='40' viewBox='0 0 24 24' fill='%23b0bec5'%3E%3Cpath d='M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z'/%3E%3C/svg%3E") center/40px no-repeat;
    z-index: 0;
    pointer-events: none;
}

.sk-thumb img {
    position: relative;
    z-index: 1;
}

.sk-thumb:hover img {
    transform: scale(1.07);
}

/* Nội dung phải */
.sk-body {
    flex: 1;
    min-width: 0;
    padding: 12px 14px;
    display: flex;
    flex-direction: column;
    border-left: 3px solid #e3edf8;
    overflow: hidden;
    justify-content: space-between;
}

/* Tiêu đề */
.sk-title {
    font-size: 20px !important;;
    font-weight: 700 !important;;
    line-height: 1.45;
    color: #1a1a2e;
    text-decoration: none;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    word-break: break-word;
    flex: 0 0 auto;                 /* đổi từ flex:1 thành này */
    margin-bottom: 4px;
}

.sk-title:hover { color: #005da0; }

.sk-summary {
    margin-top: 5px;
    color: #667085;
    font-size: 13px;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
    flex: 1;
}

.sk-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 8px;
    padding-top: 6px;
    border-top: 1px solid #f0f4f8;
    gap: 6px;
}

.sk-meta-time {
    font-size: 12px;
    color: #667085;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    flex-shrink: 1;
    min-width: 0;
}

.sk-meta-time i {
    color: #005da0;
    margin-right: 3px;
}

.sk-meta-place {
    font-size: 12px;
    color: #667085;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 130px;
    flex-shrink: 0;
}

.sk-meta-place i {
    color: #005da0;
    margin-right: 3px;
}

/* Badge trạng thái */
.sk-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 9px;
    border-radius: 20px;
    white-space: nowrap;
    letter-spacing: 0.3px;
    flex-shrink: 0;
}

.sk-badge::before {
    content: '';
    width: 6px;
    height: 6px;
    border-radius: 50%;
    display: inline-block;
    flex-shrink: 0;
}

.sk-badge--active   { background: #d1fae5; color: #065f46; }
.sk-badge--active::before   { background: #10b981; box-shadow: 0 0 0 2px rgba(16,185,129,0.25); }
.sk-badge--done     { background: #f3f4f6; color: #6b7280; }
.sk-badge--done::before     { background: #9ca3af; }
.sk-badge--upcoming { background: #dbeafe; color: #1e40af; }
.sk-badge--upcoming::before { background: #3b82f6; }

/* Desktop: 2 cột */
@media (min-width: 992px) {
    #sukien-container {
        grid-template-columns: repeat(2, 1fr);
    }
    .sk-card { height: 140px; }
    .sk-thumb { width: 150px; min-width: 150px; }
    .sk-title { font-size: 20px !important; font-weight: 700 !important; }  /* thêm !important vào đây */
}

/* Tablet: 1 cột, ẩn tóm tắt */
@media (max-width: 991px) {
    #sukien-container {
        grid-template-columns: 1fr;
    }
    
    #sukien-container .sk-card { 
        height: auto;
        min-height: 110px;
        flex-direction: row;
    }
    
    #sukien-container .sk-thumb { 
        width: 140px;
        min-width: 140px;
        height: auto;
        align-self: stretch;
        flex-shrink: 0;
    }
    
    #sukien-container .sk-thumb figure {
        height: 100%;
        margin: 0;
    }

    #sukien-container .sk-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    #sukien-container .sk-body {
        flex: 1;
        min-width: 0;
        overflow: hidden;
        padding: 10px 12px;
    }

    #sukien-container .sk-title {
        font-size: 15px !important;
        -webkit-line-clamp: 3;
        overflow: hidden;
        word-break: break-word;
    }

    #sukien-container .sk-summary { display: none; }
    #sukien-container .sk-meta-place { display: none; }
}

@media (max-width: 480px) {
    #sukien-container .sk-thumb { 
        width: 110px; 
        min-width: 110px; 
    }
    #sukien-container .sk-title { font-size: 14px !important; }
    #sukien-container .sk-body { padding: 8px; }
    #sukien-container .sk-meta-time { font-size: 11px; }
}


    </style>

    <!-- Su kien -->
    <section id="su-kien" class="ts-section">
        <div class="container">
            <div class="ts-title">
                <div class="vertical-bar"></div>
                <h2 class="title-text">Sự kiện</h2>
                <div class="line"></div>
            </div>
        </div>
        <div class="container  mb-3">
            <div id="sukien-container">



            </div>
        </div>
    </section>

    <style>
        .date-bg {
            background-color: var(--primary-color);
        }
        .py-40 {
            padding-top: 44px;
            padding-bottom: 44px;
        }
        .bg-gray {
            background-color: #ebebeb;
        }
    </style>


    <!-- Nganh -->
    <section class="ts-section mb-5">
        <div class="container">
            <div class="ts-title">
                <div class="vertical-bar"></div>
                <h2 class="title-text">Các ngành tuyển sinh</h2>
                <div class="line"></div>
            </div>
            <div class="swiper nganhSwiper">
                <div class="swiper-wrapper">
                    <!-- Pagination -->
                    <div class="swiper-pagination"></div>
                </div>
            </div>
            <div class="row mt-lg-0 mtb-50">
                <div class="text-center  btn-more-block">
                </div>
            </div>
    </section>



    <style>
        nganhSwiper .swiper-pagination {
    display: none;
}

        /* ── NGÀNH SWIPER CARD ───────────────────────────────────── */
.n9-card {
    display: block; text-decoration: none;
    border-radius: 0px; overflow: hidden;
    background: #fff;
    border: 0.5px solid rgba(0,0,0,0.1);
    transition: transform .3s, border-color .3s;
}
.n9-card:hover { transform: translateY(-4px); border-color: rgba(0,0,0,0.22); }

.n9-thumb { position: relative; padding-top: 56%; overflow: hidden; background: #f0f2f5; }
.n9-thumb img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover;  }

.n9-name {
    font-size: 20px; font-weight: 500; color: #111;
    line-height: 1.45; margin: 0;
    display: -webkit-box; -webkit-line-clamp: 1;
    -webkit-box-orient: vertical; overflow: hidden;
}

.n9-body { padding: 13px 14px 12px; display: flex; flex-direction: column; gap: 6px; }


.n9-meta {
    display: flex; align-items: center; justify-content: space-between;
    padding-top: 8px; border-top: 0.5px solid rgba(0,0,0,0.07);
}
.n9-left { display: flex; flex-direction: column; gap: 2px; min-width: 0; flex: 1; }
.n9-khoa { font-size: 17px; color: #999; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.n9-ma { font-size: 15px; font-weight: 500; color: #555; font-variant-numeric: tabular-nums; }

.n9-icon {
    flex-shrink: 0; width: 30px; height: 30px; border-radius: 10px;
    border: 0.5px solid rgba(0,0,0,0.1); background: #f5f5f7;
    display: flex; align-items: center; justify-content: center;
    transition: background .25s, border-color .25s;
}
.n9-icon i { font-size: 15px; color: #666; transition: color .25s, transform .25s; }
.n9-card:hover .n9-icon { background: #EEEDFE; border-color: #AFA9EC; }
.n9-card:hover .n9-icon i { color: #534AB7; transform: translate(2px, -2px); }

/* Mobile tweaks */
@media (max-width: 768px) {
    .n9-name { font-size: 17px; }
    .n9-body { padding: 11px 12px 10px; gap: 5px; }
    .n9-icon { width: 28px; height: 28px; border-radius: 9px; }
    .n9-icon i { font-size: 14px; }
}

@media (max-width: 480px) {
    .n9-name { font-size: 13.5px; }
    .n9-khoa { font-size: 11px; }
    .n9-ma   { font-size: 11px; }
    .n9-body { padding: 10px 11px 10px; }
    .n9-meta { padding-top: 7px; }
}


.tin-tuc-section .container,
.ts-section .container {
    max-width: 1620px;
    width: 100%;
    padding-left: 40px;
    padding-right: 40px;
}


.sukien-card-wrap,
.nganh-card-wrap {
    max-width: 1620px;
    width: calc(100% - 80px);
    margin-left: auto;
    margin-right: auto;
    box-sizing: border-box;
}

:root {
    --card-bg: #ffffff;
    --card-border: #e3e8f0;
    --card-radius: 14px;
    --card-shadow: 0 2px 12px rgba(0, 93, 160, 0.07);
    --section-gap: 28px;
    --inner-radius: 10px;
}

.ts-section {
    margin-bottom: var(--section-gap) !important;
}

/* === TITLE BAR nằm ngoài khung === */
.ts-title {
    margin-bottom: 16px !important;
    padding-bottom: 12px !important;
    border-bottom: 1px solid #eaeff5 !important;
}

/* =============================================
   TIN TỨC + THÔNG BÁO
   Khung bao .row (2 cột), KHÔNG bao .ts-title
   ============================================= */
.tin-tuc-section > .container > .row.align-items-stretch {
    padding: 0 !important;
    margin-left: 0 !important;
    margin-right: 0 !important;
    align-items: stretch !important;
}

/* Khung con Tin tức */
.tin-tuc-section .col-lg-8 {
    background: #ffffff;
    border: 1px solid var(--card-border);
    border-radius: var(--card-radius);
    box-shadow: var(--card-shadow);
    padding: 20px 24px !important;
}

/* Khung con Thông báo */
.tin-tuc-section .col-lg-4 {
    background: #ffffff;
    border: 1px solid var(--card-border);
    border-radius: var(--card-radius);
    box-shadow: var(--card-shadow);
    padding: 20px 24px !important;
}

/* Desktop: gap + giữ tỉ lệ 8:4 */
@media (min-width: 992px) {
    .tin-tuc-section > .container > .row.align-items-stretch {
        gap: 12px;
        flex-wrap: nowrap;
    }
    .tin-tuc-section .col-lg-8 {
        flex: 0 0 calc(66.6667% - 6px) !important;
        max-width: calc(66.6667% - 6px) !important;
        padding-right: 18px !important;
    }
    .tin-tuc-section .col-lg-4 {
        flex: 0 0 calc(33.3333% - 6px) !important;
        max-width: calc(33.3333% - 6px) !important;
        padding-left: 18px !important;
    }
}

.tin-tuc-section .thongbao-box {
    padding-left: 4px !important;
}

/* =============================================
   TIN TỨC NHỎ
   ============================================= */
#tin-tuc-nho-container {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--card-radius);
    box-shadow: var(--card-shadow);
    padding: 16px !important;
    margin-top: 16px !important;
}

/* SỬA: chỉ khai báo 1 lần, gộp cả base + hover */
#tin-tuc-nho-container .col-nho > div {
    background: #ffffff;
    border: 1px solid var(--card-border);
    border-radius: var(--inner-radius);
    padding: 10px 10px 12px !important;
    height: 100%;
    transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease !important;
    cursor: pointer;
}

#tin-tuc-nho-container .col-nho > div:hover {
    transform: translateY(-5px) !important;
    box-shadow: 0 8px 24px rgba(0, 93, 160, 0.14) !important;
    border-color: #b5d0ef !important;
}

#tin-tuc-nho-container .ratio-container.news-img {
    margin-bottom: 10px !important;
    border-radius: 7px;
    overflow: hidden;
}

/* =============================================
   SỰ KIỆN
   ============================================= */
#sukien-container {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--card-radius);
    box-shadow: var(--card-shadow);
    padding: 20px !important;
}

/* =============================================
   THÔNG BÁO MOBILE
   ============================================= */
.d-lg-none > .thongbao-box {
    background: #ffffff;
    border: 1px solid var(--card-border);
    border-radius: var(--inner-radius);
    padding: 14px 16px !important;
    margin-top: 6px;
}

/* =============================================
   NGÀNH — chỉ bọc .nganhSwiper trong .tin-tuc-section hoặc .ts-section
   tránh ảnh hưởng swiper trang khác
   ============================================= */
.ts-section .nganhSwiper {
    border: 1px solid var(--card-border) !important;
    border-radius: var(--card-radius) !important;
    box-shadow: var(--card-shadow) !important;
    background: var(--card-bg) !important;
    padding: 16px !important;
}

.n9-card {
    border-radius: var(--inner-radius) !important;
    border: 1px solid var(--card-border) !important;
    box-shadow: 0 1px 6px rgba(0, 93, 160, 0.06) !important;
    background: var(--card-bg) !important;
    transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease !important;
}

.n9-card:hover {
    transform: translateY(-5px) !important;
    box-shadow: 0 8px 24px rgba(0, 93, 160, 0.14) !important;
    border-color: #b5d0ef !important;
}

.n9-thumb {
    padding-top: 42% !important;
    border-radius: var(--inner-radius) var(--inner-radius) 0 0;
    overflow: hidden;
}

.n9-body  { padding: 10px 12px 10px !important; gap: 5px !important; }
.n9-name  { font-size: 14px !important; font-weight: 500 !important; color: #1a2e6b !important; }
.n9-khoa  { font-size: 12px !important; color: #8a9ab0 !important; }
.n9-ma    { font-size: 12px !important; font-weight: 500 !important; color: #005da0 !important; }
.n9-meta  { border-top: 1px solid #eaeff5 !important; padding-top: 8px !important; }

.n9-icon {
    width: 28px !important; height: 28px !important;
    background: #eef3fb !important;
    border: 1px solid #d0dff0 !important;
    border-radius: 7px !important;
}
.n9-card:hover .n9-icon { background: #ddeaf9 !important; border-color: #a0c0e8 !important; }
.n9-card:hover .n9-icon i { color: #005da0 !important; transform: translate(2px,-2px) !important; }

/* =============================================
   RESPONSIVE
   ============================================= */
@media (max-width: 991.98px) {
    .tin-tuc-section > .container > .row.align-items-stretch {
        padding: 0 !important;
        gap: 0;
    }

    /* Xóa khung nền các cột tin tức / thông báo */
    .tin-tuc-section .col-lg-8,
    .tin-tuc-section .col-lg-4 {
        flex: unset !important;
        max-width: unset !important;
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        padding: 8px 0 !important;
    }

    /* Xóa khung nền tin tức nhỏ */
    #tin-tuc-nho-container {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        padding: 8px 0 !important;
        margin-top: 8px !important;
    }

    /* Giữ khung card con bên trong tin tức nhỏ nhưng nhẹ hơn */
    #tin-tuc-nho-container .col-nho > div {
        padding: 8px !important;
        border: 1px solid #eaeff5 !important;
        box-shadow: none !important;
    }

    /* Xóa khung sự kiện */
    #sukien-container {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        padding: 0 !important;
    }

    /* Xóa khung ngành swiper */
    .ts-section .nganhSwiper {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        padding: 0 !important;
    }

    /* Xóa khung thông báo mobile */
    .d-lg-none > .thongbao-box {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
    }
}

@media (max-width: 575px) {
    /* Không cần border-radius vì đã xóa khung hết rồi */
    #tin-tuc-nho-container .col-nho > div {
        border-radius: 8px !important;
    }
}

@media (max-width: 991.98px) {
    .tin-tuc-section .container,
    .ts-section .container {
        padding-left: 12px !important;
        padding-right: 12px !important;
    }
}














/* Pin news-info xuống dưới cùng */
.featuredSwiper .news-content {
    display: flex !important;
    flex-direction: column !important;
}

.featuredSwiper .news-summary {
    flex: 1 !important;
    display: -webkit-box !important;
    -webkit-line-clamp: 2 !important;
    -webkit-box-orient: vertical !important;
    overflow: hidden !important;
    margin-bottom: 10px !important;
}

.featuredSwiper .news-summary p {
    margin: 0 !important;
}

.featuredSwiper .news-info {
    margin-top: auto !important;
    flex-shrink: 0 !important;
}

    </style>
@endsection

@section('js')
    <script src="{{ asset('js/trangchu.js') }}?v={{ filemtime(public_path('js/trangchu.js')) }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>

    {{-- Thong so --}}
    <script>
        // Initialize Swiper
        const swiper = new Swiper('.stats-swiper', {
            // Slides per view
            slidesPerView: 1,
            spaceBetween: 30,

            // Responsive breakpoints
            breakpoints: {
                // when window width is >= 576px
                576: {
                    slidesPerView: 2,
                    spaceBetween: 20
                },
                // when window width is >= 768px
                768: {
                    slidesPerView: 3,
                    spaceBetween: 30
                },
                // when window width is >= 992px
                992: {
                    slidesPerView: 4,
                    spaceBetween: 40
                }
            },
            // Auto play (optional)
            autoplay: {
                delay: 199999000,
                disableOnInteraction: false,
            },

            // Loop
            loop: true,

            // Centered slides
            centeredSlides: false,
        });
    </script>

    {{-- Nganh --}}
    <script>
        const nganhSwiper = new Swiper(".nganhSwiper", {
            loop: true,
            autoplay: {
                delay: 4000,
                disableOnInteraction: false
            },
            pagination: {
                el: ".nganhSwiper .swiper-pagination",
                clickable: true
            },
            breakpoints: {
                0: {
                    slidesPerView: 1,
                    spaceBetween: 16
                },
                768: {
                    slidesPerView: 2,
                    spaceBetween: 24
                },
                992: {
                    slidesPerView: 3,
                    spaceBetween: 32
                }
            }
        });
    </script>



<script>
    /*
     * LOAD EFFECT: anim-fadeup
     * Làm trực tiếp trong index.blade.php
     * Không sửa logic render, không sửa AJAX, không sửa Swiper
     */
    (function () {
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        const animSelectors = [
            'main > section',
            'main .ts-title',
            'main .ts-section h4',

            '#featured-news',
            '#featured-news .news-item',

            '#tin-tuc-nho-container',
            '#tin-tuc-nho-container .col-nho',

            '#thongbaonho li',
            '#thongbaonho-mobile li',

            '.stats-swiper .swiper-slide > .col-12',

            '#sukien-container > div',

            '.nganhSwiper .n9-card',

            '.btn-more-block',
            '.more'
        ].join(',');

        const skipExactSelectors = [
            '.swiper-wrapper',
            '.swiper-slide',
            '.swiper-pagination',
            '.swiper-pagination-bullet',
            '.swiper-button-next',
            '.swiper-button-prev',
            '.featured-swiper-prev',
            '.featured-swiper-next',
            '.fake-news-overlay'
        ].join(',');

        function shouldSkip(el) {
            if (!el) return true;
            if (el.matches(skipExactSelectors)) return true;
            if (el.closest('.offcanvas, .modal')) return true;
            return false;
        }

        function cleanupAnimClass(el) {
            el.classList.remove('anim-fadeup', 'anim-fadeup-show');
            el.dataset.animFadeupDone = '1';
            el.style.removeProperty('--anim-delay');
        }

        function revealElement(el) {
            if (!el || el.dataset.animFadeupDone === '1') return;

            if (prefersReducedMotion) {
                cleanupAnimClass(el);
                return;
            }

            requestAnimationFrame(function () {
                el.classList.add('anim-fadeup-show');

                const fallbackTimer = setTimeout(function () {
                    cleanupAnimClass(el);
                }, 1200);

                el.addEventListener('transitionend', function () {
                    clearTimeout(fallbackTimer);
                    cleanupAnimClass(el);
                }, { once: true });
            });
        }

        const observer = 'IntersectionObserver' in window
            ? new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        revealElement(entry.target);
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.08,
                rootMargin: '0px 0px -20px 0px'
            })
            : null;

        function bindAnimFadeup(root) {
            const scope = root || document;
            const elements = scope.querySelectorAll(animSelectors);

            elements.forEach(function (el, index) {
                if (!el || el.dataset.animFadeupBound === '1') return;
                if (shouldSkip(el)) return;

                el.dataset.animFadeupBound = '1';
                el.style.setProperty('--anim-delay', `${Math.min(index % 8, 7) * 55}ms`);
                el.classList.add('anim-fadeup');

                if (observer) {
                    observer.observe(el);
                } else {
                    revealElement(el);
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            bindAnimFadeup(document);
        });

        const mutationObserver = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (!(node instanceof HTMLElement)) return;

                    if (node.matches && node.matches(animSelectors)) {
                        bindAnimFadeup(node.parentElement || document);
                    }

                    if (node.querySelector && node.querySelector(animSelectors)) {
                        bindAnimFadeup(node);
                    }
                });
            });
        });

        mutationObserver.observe(document.querySelector('main') || document.body, {
            childList: true,
            subtree: true
        });
    })();
</script>

@endsection

{{-- @push('body_end')
    @include('users.partials.chatbot-widget')
@endpush --}}
