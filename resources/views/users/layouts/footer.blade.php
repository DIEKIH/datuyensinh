{{-- <!-- footer.php -->
<section>
    <footer class="content">
        <div class="container">
            <div class="row text-center text-md-start">
                <div class="col-md-3 col-12 footer-column footer-logo text-center">
                    <a href="#"><img src="/images/system/logo (2).png" alt="CTUT Logo" class="img-fluid"></a>
                </div>
                <div class="col-md-3 col-6 footer-column">
                    <h5>Thông tin</h5>
                    <ul>
                        <li><a href="#">Giới thiệu</a></li>
                        <li><a href="#">Thông tin tuyển sinh</a></li>
                        <li><a href="#">Ngành đào tạo</a></li>
                        <li><a href="#">Hỏi đáp</a></li>
                    </ul>
                </div>
                <div class="col-md-3 col-6 footer-column">
                    <h5>Liên hệ</h5>
                    <ul>
                        <li><a href="#"><i class="bi bi-telephone"></i> (0123) 456 789</a></li>
                        <li><a href="#"><i class="bi bi-envelope"></i> hahahihihuhu.edu.vn</a></li>
                        <li><a href=""><i class="bi bi-geo-alt"></i>256, Nguyễn Văn Cừ, Cần Thơ</a></li>
                    </ul>
                </div>
                <div class="col-md-3 col-12 footer-column text-center text-md-start">
                    <h5>Theo dõi</h5>
                    <div class="footer-social d-flex justify-content-center justify-content-md-start">
                        <a href="#"><img src="/images/system/tiktokicon.webp" alt="TikTok"></a>
                        <a href="#"><img src="/images/system/facebookicon.webp" alt="Facebook"></a>
                        <a href="#"><img src="/images/system/youtubeicon.webp" alt="YouTube"></a>
                        <a href="#"><img src="/images/system/zaloicon.webp" alt="Zalo"></a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>Copyright © 2025 Trường Đại học Kỹ thuật - Công nghệ Cần Thơ</p>
        </div>
    </footer>
</section> --}}

<!-- footer.php -->
{{-- <section>
    <footer class="content">
        <div class="container">
            <div class="row text-center text-md-start">
                <div class="col-md-3 col-12 footer-column footer-logo text-center">
                    <a href="#"><img src="/images/system/logo (2).png" alt="CTUT Logo" class="img-fluid"></a>
                </div>
                <div class="col-md-3 col-6 footer-column">
                    <h5>Thông tin</h5>
                    <ul>
                        <li><a href="#">Giới thiệu</a></li>
                        <li><a href="#">Thông tin tuyển sinh</a></li>
                        <li><a href="#">Ngành đào tạo</a></li>
                        <li><a href="#">Hỏi đáp</a></li>
                    </ul>
                </div>
                <div class="col-md-3 col-6 footer-column">
                    <h5>Liên hệ</h5>
                    <ul>
                        <li><a href="#"><i class="bi bi-telephone"></i> (0123) 456 789</a></li>
                        <li><a href="#"><i class="bi bi-envelope"></i> hahahihihuhu.edu.vn</a></li>
                        <li><a href=""><i class="bi bi-geo-alt"></i>256, Nguyễn Văn Cừ, Cần Thơ</a></li>
                    </ul>
                </div>

                <div class="col-md-3 col-12 footer-column">
                    <div class="visitor-counter-box">
                        <div class="vc-header">Số lượt truy cập</div>
                        <div class="vc-total-big" id="vc-tong">0</div>
                        <div class="vc-row">
                            <span><i class="bi bi-person"></i> Hôm nay</span>
                            <span id="vc-homnay">0</span>
                        </div>
                        <div class="vc-row">
                            <span><i class="bi bi-person"></i> Tuần này</span>
                            <span id="vc-tuannay">0</span>
                        </div>
                        <div class="vc-row">
                            <span><i class="bi bi-person"></i> Tháng này</span>
                            <span id="vc-thangnay">0</span>
                        </div>
                        <div class="vc-row">
                            <span><i class="bi bi-bar-chart"></i> Tổng số lượt truy cập</span>
                            <span id="vc-tong2">0</span>
                        </div>
                        <div class="vc-footer">Visitors Counter</div>
                    </div>

                    <h5 class="mt-3">Theo dõi</h5>
                    <div class="footer-social d-flex justify-content-center justify-content-md-start">
                        <a href="#"><img src="/images/system/tiktokicon.webp" alt="TikTok"></a>
                        <a href="#"><img src="/images/system/facebookicon.webp" alt="Facebook"></a>
                        <a href="#"><img src="/images/system/youtubeicon.webp" alt="YouTube"></a>
                        <a href="#"><img src="/images/system/zaloicon.webp" alt="Zalo"></a>
                    </div>
                </div>

            </div>
        </div>
        <div class="footer-bottom">
            <p>Copyright © 2025 Trường Đại học Kỹ thuật - Công nghệ Cần Thơ</p>
        </div>
    </footer>
</section> --}}

<style>
    footer.content {
        background-color: #0e4582;
        color: #fff;
        padding: 40px 0 0 0;
        font-family: 'DVN-Poppins', sans-serif;
    }

    .footer-col h5 {
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 16px;
        color: #fff;
        font-size: 14px;
        position: relative;
        padding-bottom: 10px;
    }

    .footer-col h5::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: 0;
        width: 36px;
        height: 2px;
        background: #5b9bd5;
        border-radius: 2px;
    }

    .footer-col ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .footer-col ul li {
        margin-bottom: 10px;
    }

    .footer-col ul li a {
        color: #b8d0ee;
        text-decoration: none;
        font-size: 14px;
        transition: color 0.2s;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .footer-col ul li a:hover {
        color: #fff;
    }

    .footer-col ul li a i {
        font-size: 14px;
        color: #5b9bd5;
        width: 16px;
    }



    /* Social */
    .footer-social {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 8px;
    }

    .footer-social a {
        width: 36px;
        height: 36px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s, transform 0.2s;
    }

    .footer-social a:hover {
        background: rgba(255, 255, 255, 0.25);
        transform: translateY(-2px);
    }

    .footer-social img {
        width: 20px;
        height: 20px;
        object-fit: contain;
    }

    /* Visitor counter */
    .vc-box {
        background: rgba(255, 255, 255, 0.07);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 10px;
        overflow: hidden;
    }

    .vc-box-header {
        background: #1a6fc4;
        color: #fff;
        font-weight: 700;
        font-size: 12px;
        /* SỬA: từ 13px xuống 12px */
        padding: 7px 12px;
        /* SỬA: giảm padding */
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }

    .vc-big-number {
        text-align: center;
        font-size: 1.4rem;
        /* SỬA: từ 1.9rem xuống 1.4rem */
        font-weight: 900;
        color: #fff;
        padding: 8px 0 6px;
        /* SỬA: giảm padding */
        letter-spacing: 4px;
        font-family: monospace;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .vc-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 5px 12px;
        /* SỬA: từ 7px 14px xuống 5px 12px */
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        font-size: 12px;
        /* SỬA: từ 13px xuống 12px */
        color: #ffffff;
    }

    .vc-item span:first-child {
        display: flex;
        align-items: center;
        gap: 7px;
    }

    .vc-item i {
        color: #5b9bd5;
        font-size: 13px;
    }

    .vc-item span:last-child {
        font-weight: 700;
        color: #fff;
    }

    .vc-credit {
        text-align: center;
        padding: 4px;
        /* SỬA: từ 6px xuống 4px */
        font-size: 10px;
        /* SỬA: từ 11px xuống 10px */
        color: rgba(255, 255, 255, 0.4);
    }

    /* Divider */
    .footer-divider {
        border-color: rgba(255, 255, 255, 0.1);
        margin: 30px 0 0 0;
    }

    .footer-bottom {
        text-align: center;
        font-size: 13px;
        color: rgba(255, 255, 255, 0.5);
    }

    @media (max-width: 767px) {
        .footer-col {
            margin-bottom: 28px;
        }

        .footer-logo-col {
            display: none !important;
        }

        .footer-social {
            justify-content: flex-start !important;
        }

        .footer-col ul li a {
            color: #ffffff !important;
        }

        .footer-col ul li a i {
            color: #ffffff !important;
        }
    }
</style>

<section>
    <footer class="content">
        <div class="container">
            <div class="row g-4">

                {{-- CỘT 1: Thông tin --}}
                <div class="col-lg-3 col-md-3 col-6 footer-col">
                    <h5>Thông tin</h5>
                    <ul>
                        <li><a href="/gioithieu"><i class="bi bi-chevron-right"></i>Giới thiệu</a></li>
                        <li>
                            <a href="{{ url('/thong-tin-tuyen-sinh') }}">
                                <i class="bi bi-chevron-right"></i>Thông tin tuyển sinh
                            </a>
                        </li>
                        @php
                            $isHome = request()->path() === '/';
                        @endphp

                        <li>
                            <a href="{{ $isHome ? '#tin-tuc' : url('/tin-tuc') }}">
                                <i class="bi bi-chevron-right"></i>Tin tức
                            </a>
                        </li>

                        <li>
                            <a href="{{ $isHome ? '#su-kien' : url('/su-kien') }}">
                                <i class="bi bi-chevron-right"></i>Sự kiện
                            </a>
                        </li>
                        <li>
                            <a href="{{ url('/dang-ky-tu-van') }}">
                                <i class="bi bi-chevron-right"></i>Đăng ký tư vấn
                            </a>
                        </li>
                    </ul>
                </div>

                {{-- CỘT 2: Liên hệ --}}
                <div class="col-lg-3 col-md-3 col-6 footer-col">
                    <h5>Liên hệ</h5>
                    <ul>
                        <li><a href="#"><i class="bi bi-telephone-fill"></i>(0123) 456 789</a></li>
                        <li><a href="#"><i class="bi bi-envelope-fill"></i>hahahihihuhu.edu.vn</a></li>
                        <li><a href="#"><i class="bi bi-geo-alt-fill"></i>256, Nguyễn Văn Cừ, Cần Thơ</a></li>
                    </ul>
                </div>

                {{-- CỘT 3: Theo dõi --}}
                <div class="col-lg-2 col-md-3 col-6 footer-col">
                    <h5>Theo dõi</h5>
                    <div class="footer-social">
                        <a href="#"><img src="/images/system/tiktokicon.webp" alt="TikTok"></a>
                        <a href="#"><img src="/images/system/facebookicon.webp" alt="Facebook"></a>
                        <a href="#"><img src="/images/system/youtubeicon.webp" alt="YouTube"></a>
                        <a href="#"><img src="/images/system/zaloicon.webp" alt="Zalo"></a>
                    </div>
                </div>

                {{-- CỘT 4: Lượt truy cập --}}
                <div class="col-lg-4 col-md-3 col-12 footer-col">
                    <h5>Lượt truy cập</h5>
                    <div class="vc-box">
                        <div class="vc-big-number" id="vc-tong">0</div>
                        <div class="vc-item">
                            <span><i class="bi bi-person-fill"></i> Hôm nay</span>
                            <span id="vc-homnay">0</span>
                        </div>
                        <div class="vc-item">
                            <span><i class="bi bi-people-fill"></i> Tuần này</span>
                            <span id="vc-tuannay">0</span>
                        </div>
                        <div class="vc-item">
                            <span><i class="bi bi-calendar3"></i> Tháng này</span>
                            <span id="vc-thangnay">0</span>
                        </div>
                        <div class="vc-item">
                            <span><i class="bi bi-bar-chart-fill"></i> Tổng cộng</span>
                            <span id="vc-tong2">0</span>
                        </div>
                        <div class="vc-credit">Visitors Counter</div>
                    </div>
                </div>

            </div>
        </div>

        <hr class="footer-divider">
        <div class="footer-bottom">
            <p class="mb-0">Copyright © 2025 Trường Đại học Kỹ thuật - Công nghệ Cần Thơ</p>
        </div>
    </footer>
</section>
