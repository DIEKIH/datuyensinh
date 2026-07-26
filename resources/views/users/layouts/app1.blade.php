<!DOCTYPE html>
<html class="no-js" lang="">

<head>
    <meta charset="utf-8">
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>CTUT | Phòng Đào tạo</title>
    <meta property="og:locale" content="vi_VN" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="" />
    <meta property="og:description" content="/>
    <meta property="og:url" content="/" />
    <meta property="og:site_name" content="Đại học Quốc gia TP.HCM" />
    <meta property="og:image" content="" />
    <link rel="apple-touch-icon" href="apple-touch-icon.png">
    <link href="/styles/fonts_google.css" rel="stylesheet" type="text/css">
    <script src="/scripts/client_apis_google.js" gapi_processed="true"></script>
    <link rel="stylesheet" href="/styles/font_roboto.css">
    <link rel="stylesheet" href="/styles/font_roboto_condensed.css">

    <link rel="stylesheet" href="/styles/vendor.css?v=1.8">
    <link rel="stylesheet" href="/styles/main.css?v=8.9">
    <link rel="stylesheet" href="/assets/styles/custom.css?v=2.2">
    <script async src="/scripts/vendor2.js?v=2.7"></script>
</head>

<body>
    <div class="wrapper" id="wrapper">
        <header>
            <div class="header-top">
                <div class="container">
                    <ul class="navbar-top d-flex justify-content-end">
                        <li class="dropdown">
                            <button class="btn btn-link" type="button" id="dropdownSearch" data-toggle="dropdown"
                                style="line-height: 2;" aria-haspopup="true" aria-expanded="false"><i
                                    class="fa fa-search"></i></button>
                            <div class="dropdown-menu dropdown-search" aria-labelledby="dropdownSearch">
                                <form action="/tim-kiem" method="get">
                                    <input class="form-control" type="text" name="search" placeholder="Tìm kiếm">
                                    <button class="btn-link btn" type="submit"><i class="fa fa-search"></i></button>
                                </form>
                            </div>
                        </li>
                        <li class="dropdown">
                            <div class="">
                                <a class="btn btn-link" style="padding: 5px; text-decoration: none; color: white"
                                    href="/lang/en">
                                    English
                                </a>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
            <nav class="navbar navbar-expand-lg navbar-light bg-white">
                <div class="container">
                    <a class="navbar-brand" href="/">
                        <img src="" alt="" width="145">
                    </a>
                    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav"
                        aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation"><span></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarNav">
                        <ul class="navbar-nav ml-auto">
                            @foreach ($menus as $menu)
                                <li class="nav-item">
                                    {{-- @php
                      $encrypted = \Illuminate\Support\Facades\Crypt::encrypt($menu->slug);
                  @endphp --}}
                                    <a href="{{ route('page.show', ['slug' => $menu->slug]) }}"
                                        class="nav-link crop-text-1">
                                        {{ $menu->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </nav>











        </header>









        <main id="main-content">
            <div class="sub-banner" style="background-image:url('/img/banner/subbanner-event-research.jpg')">
                @yield('content') <!-- Các nội dung sẽ được chèn vào đây từ các trang con -->
            </div>
        </main>












        <footer class="footer wow fadeInUp">
            <div class="container">
                <div class="row">
                    <div class="col col-ft-1 mb-3 mb-lg-0">
                        <div class="footer-link">
                            <h3 style="text-align: left">Đại học Quốc gia TP. HỒ CHÍ MINH <i
                                    class="fa fa-angle-down d-md-none"></i></h3>
                            <ul class="list-unstyled"
                                style="list-style: none; margin-left: 35px; display: block !important;">
                                <li><i class="fa fa-map-marker"></i>P. Linh Trung, TP. Thủ Đức, TP. HCM</li>
                                <li><i class="fa fa-phone"></i>(+84-28) 37 242 181 (ext: 1651/1652)</li>
                                <li><i class="fa fa-envelope"></i><a
                                        href="mailto:info@vnuhcm.edu.vn">info@vnuhcm.edu.vn</a></li>
                                <li><i class="fa fa-facebook-square"></i><a
                                        href="https://www.facebook.com/vnuhcm.info/" target="_blank">Facebook</a></li>
                                <li style="margin-left: -35px">Phát triển bởi HPT</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col col-ft-3 mb-3 mb-lg-0">
                        <div class="footer-link">
                            <h3>Các đơn vị thành viên<i class="fa fa-angle-down d-md-none"></i></h3>
                            <ul class="list-unstyled">
                                <li><a target="_blank" href="http://www.hcmut.edu.vn/">Trường Đại học Bách Khoa </a>
                                </li>
                                <li><a target="_blank" href="https://www.hcmus.edu.vn/">Trường Đại học Khoa học Tự
                                        nhiên</a></li>
                                <li><a target="_blank" href="http://hcmussh.edu.vn/">Trường Đại học KHXH và Nhân
                                        văn</a></li>
                                <li><a target="_blank" href="https://www.hcmiu.edu.vn/">Trường Đại học Quốc tế </a>
                                </li>
                                <li><a target="_blank" href="https://www.uit.edu.vn/">Trường Đại học Công nghệ Thông
                                        tin</a></li>
                                <li><a target="_blank" href="http://www.uel.edu.vn/">Trường Đại học Kinh tế - Luật</a>
                                </li>
                                <li><a target="_blank" href="https://www.agu.edu.vn/">Trường Đại học An Giang</a></li>
                                <li><a target="_blank" href="https://medvnu.edu.vn/">Trường Đại học Khoa học Sức
                                        khỏe</a></li>
                                <li><a target="_blank" href="http://www.hcmier.edu.vn">Viện Môi trường và Tài
                                        nguyên</a></li>
                                <li><a target="_blank" href="/ve-dhqg-hcm/33396864/316864/376864">Các đơn vị khác</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="col col-ft-2 mb-3 mb-lg-0">
                        <div class="footer-link">
                            <h3>Danh mục <i class="fa fa-angle-down d-md-none"></i></h3>
                            <ul class="list-unstyled">
                                <li>
                                    <a href="/tin-tuc/32343364">Tin tức</a>
                                </li>
                                <li>
                                    <a href="/su-kien/33353364">Sự kiện</a>
                                </li>
                                <li>
                                    <a href="https://research.vnuhcm.edu.vn/">Nghiên cứu</a>
                                </li>
                                <li>
                                    <a href="/dao-tao/33373364">Đào tạo</a>
                                </li>
                                <li>
                                    <a href="/doi-ngoai/34303364">Đối ngoại</a>
                                </li>
                                <li>
                                    <a href="/sinh-vien/33383364">Sinh viên</a>
                                </li>
                                <li>
                                    <a href="/ve-dhqg-hcm/33393364">Về ĐHQG-HCM</a>
                                </li>
                                <li>
                                    <a href="https://megastory.vnuhcm.edu.vn">Megastory</a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="col col-ft-4 mb-3 mb-lg-0">
                        <div class="footer-link">
                            <h3>Liên hệ<i class="fa fa-angle-down d-md-none"></i></h3>
                            <ul class="list-unstyled">
                                <li><a href="https://accounts.google.com/signin/v2/identifier?hd=vnuhcm.edu.vn&sacu=1&flowName=GlifWebSignIn&flowEntry=AddSession"
                                        target="_blank">Thư điện tử</a></li>
                                <li><a href="/lien-he">Hỗ trợ</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
        </footer>
    </div>

    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-87720639-4"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'UA-87720639-4');
    </script>

</body>

</html>
