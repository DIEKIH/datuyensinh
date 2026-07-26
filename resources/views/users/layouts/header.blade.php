<section>
    <nav class="navbar navbar-expand-lg">
        <div class="container">

            {{-- TRÁI: Logo + tên trường --}}
<div class="d-flex align-items-center gap-2" style="min-width:0; overflow:hidden;">
    <a href="/" class="flex-shrink-0">
        <img src="/images/system/logo (2).png" alt="Logo CTUT" class="logo-img" />
    </a>
    <div class="text-white lh-sm" style="min-width:0;">
        <div style="font-size:0.7rem;">TRƯỜNG ĐẠI HỌC</div>
        <div style="font-size:0.85rem;font-weight:700;white-space:nowrap;">KỸ THUẬT - CÔNG NGHỆ CẦN THƠ</div>
        <div style="font-size:0.7rem;">MÃ TRƯỜNG: <strong>KCC</strong></div>
    </div>
</div>

            {{-- TRÁI: Logo + tên trường --}}
            

            {{-- XÓA HOÀN TOÀN div.tuyen-sinh-title ở giữa --}}

            {{-- GIỮA: Tiêu đề --}}
            {{-- <div class="tuyen-sinh-title text-white fw-bold text-uppercase text-center flex-grow-1 px-3">
                CHUYÊN TRANG TUYỂN SINH
            </div> --}}

            {{-- PHẢI: Menu --}}
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse flex-grow-0" id="navbarNav">
                <ul class="navbar-nav" id="dynamic-menu">
                    <li class="nav-item">
                        <a class="nav-link" href="/">Trang chủ</a>
                    </li>
                </ul>
            </div>

        </div>
    </nav>
</section>

<style>
    .logo-img {
        height: 60px;
        width: auto;
        object-fit: contain;
    }

    nav.navbar {
        background-color: #1a2e6b;
        padding: 8px 0;
    }

    nav.navbar .nav-link {
        color: rgba(255,255,255,0.9) !important;
        font-size: 0.95rem;
        padding: 4px 12px;
    }

    nav.navbar .nav-link:hover {
        color: #fff !important;
    }


  
</style>


@section('js')
    <script src="/js/trangchu.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>

    
@endsection