@extends('users.layouts.app')

@section('title', $post->tieude ?? 'Chi tiết bài viết')

@section('css')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-bs5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

    {{-- GIỮ NGUYÊN CSS CŨ:
         video + ảnh trong nội dung vẫn hoạt động như trang hiện tại --}}
    <link rel="stylesheet" href="{{ asset('css/chitiet.css') }}">

    <style>
        /*
        |--------------------------------------------------------------------------
        | CHỈ ÁP DỤNG CHO TRANG KHÔNG CÓ ẢNH ĐẠI DIỆN
        |--------------------------------------------------------------------------
        */

        .detail-no-thumbnail .news-item {
            display: block;
            padding: 16px 18px;
        }

        .detail-no-thumbnail .news-content {
            width: 100%;
            min-width: 0;
        }

        .detail-no-thumbnail .news-item h4 {
            margin: 0 0 8px;
            line-height: 1.45;
        }

        .detail-no-thumbnail .news-item h4 a {
            color: var(--text-dark);
            text-decoration: none;
        }

        .detail-no-thumbnail .news-item h4 a:hover {
            color: var(--primary-color);
        }

        .detail-no-thumbnail .news-item p {
            margin-bottom: 10px;
            line-height: 1.6;
        }

        /*
        |--------------------------------------------------------------------------
        | DANH SÁCH CÙNG MENU
        |--------------------------------------------------------------------------
        */

        .detail-no-thumbnail .div2 .news-item {
            display: block;
            height: auto;
            min-height: unset;
            padding: 14px 16px;
        }

        .detail-no-thumbnail .div2 .news-item .news-content {
            display: block;
            padding: 0;
            border: 0;
        }

        .detail-no-thumbnail .div2 .news-item h4 {
            font-size: 1rem;
            margin-bottom: 7px;
        }

        .detail-no-thumbnail .div2 .news-item p {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /*
        |--------------------------------------------------------------------------
        | BÀI VIẾT TIẾP THEO
        |--------------------------------------------------------------------------
        */

        .detail-no-thumbnail .next-news-horizontal {
            display: block;
        }

        .detail-no-thumbnail .next-news-horizontal > a {
            display: block;
            width: 100%;
            padding: 16px 18px;
            text-decoration: none;
            color: inherit;
        }

        .detail-no-thumbnail .next-news-horizontal .content {
            width: 100%;
            height: auto;
            padding: 0;
        }

        .detail-no-thumbnail .next-news-horizontal h4 {
            margin-bottom: 7px;
        }

        .detail-no-thumbnail .next-news-horizontal p {
            margin-bottom: 10px;
        }

        /*
        |--------------------------------------------------------------------------
        | LIÊN QUAN / NỔI BẬT
        |--------------------------------------------------------------------------
        */

        .detail-no-thumbnail .related-grid,
        .detail-no-thumbnail .featured-grid {
            align-items: stretch;
        }

        .detail-no-thumbnail .related-grid .news-item,
        .detail-no-thumbnail .featured-grid .news-item {
            height: 100%;
            margin-bottom: 0;
        }

        .detail-no-thumbnail .related-grid .news-content,
        .detail-no-thumbnail .featured-grid .news-content {
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .detail-no-thumbnail .related-grid .news-date,
        .detail-no-thumbnail .featured-grid .news-date {
            margin-top: auto;
            padding-top: 8px;
        }

        /*
        |--------------------------------------------------------------------------
        | MOBILE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 768px) {
            .detail-no-thumbnail .div2 .news-item,
            .detail-no-thumbnail .related-grid .news-item,
            .detail-no-thumbnail .featured-grid .news-item {
                display: block !important;
                min-height: unset !important;
                height: auto !important;
                padding: 14px !important;
                border-radius: 6px !important;
            }

            .detail-no-thumbnail .div2 .news-item .news-content,
            .detail-no-thumbnail .related-grid .news-item .news-content,
            .detail-no-thumbnail .featured-grid .news-item .news-content {
                display: flex !important;
                padding: 0 !important;
                border-left: 0 !important;
            }

            .detail-no-thumbnail .div2 .news-item p,
            .detail-no-thumbnail .related-grid .news-item p,
            .detail-no-thumbnail .featured-grid .news-item p {
                display: -webkit-box !important;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }

            .detail-no-thumbnail .next-news-horizontal > a {
                display: block !important;
                padding: 14px !important;
            }

            .detail-no-thumbnail .next-news-horizontal .content {
                padding: 0 !important;
            }

            .detail-no-thumbnail .next-news-horizontal p {
                display: -webkit-box !important;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }
        }
    </style>
@endsection

<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">

@section('content')

    <div class="parent detail-no-thumbnail">

        @php
            $offcanvasId = 'offcanvas-' . $post->id;
            $pdfUrl = !empty($post->file_url)
                ? asset($post->file_url)
                : null;
        @endphp

        <div class="detail-main-row">

            {{-- =========================================================
                NỘI DUNG CHÍNH
            ========================================================== --}}
            <div class="div1">

                <div class="page-container">

                    <h1 class="main-title">
                        {{ $post->tieude }}
                    </h1>

                    <div class="meta-info">

                        <div class="meta-item">
                            <i class="fas fa-calendar-alt"></i>

                            {{ \Carbon\Carbon::parse($post->ngaydang)->format('d/m/Y') }}
                        </div>

                        <div class="meta-item">
                            <i class="fas fa-user"></i>
                            {{ $tacgia }}
                        </div>

                        <div class="meta-item">
                            <i class="fas fa-eye"></i>
                            {{ number_format($post->views) }} lượt xem
                        </div>

                    </div>


                    {{-- =====================================================
                        XỬ LÝ NỘI DUNG
                        GIỮ NGUYÊN LOGIC VIDEO
                    ====================================================== --}}

                    @php
                        $content = $post->noidung;

                        /*
                         * Bọc iframe Youtube / Embed
                         */
                        $content = preg_replace(
                            '/<iframe(.*?)<\/iframe>/is',
                            '<div class="video-wrapper">$0</div>',
                            $content
                        );

                        /*
                         * Video HTML5
                         */
                        $content = preg_replace_callback(
                            '/<video(.*?)>(.*?)<\/video>/is',
                            function ($matches) {

                                $attrs = $matches[1];

                                /*
                                 * Thêm controls nếu chưa có
                                 */
                                if (stripos($attrs, 'controls') === false) {
                                    $attrs .= ' controls';
                                }

                                /*
                                 * Bỏ width / height cố định
                                 */
                                $attrs = preg_replace(
                                    '/\s*width\s*=\s*["\'][^"\']*["\']/i',
                                    '',
                                    $attrs
                                );

                                $attrs = preg_replace(
                                    '/\s*height\s*=\s*["\'][^"\']*["\']/i',
                                    '',
                                    $attrs
                                );

                                return '<div class="video-container">' .
                                    '<video' . $attrs . '>' .
                                    $matches[2] .
                                    '</video>' .
                                    '</div>';
                            },
                            $content
                        );
                    @endphp


                    {{-- ẢNH TRONG NỘI DUNG VẪN HIỂN THỊ --}}
                    <div class="post-content">
                        {!! $content !!}
                    </div>


                    {{-- =====================================================
                        TÀI LIỆU ĐÍNH KÈM
                    ====================================================== --}}

                    @if ($post->file_url)

                        <div class="mb-3">

                            <a
                                class="btn-document"
                                data-bs-toggle="offcanvas"
                                href="#{{ $offcanvasId }}"
                            >
                                <i class="fas fa-file-pdf"></i>
                                Xem tài liệu đính kèm
                            </a>

                        </div>


                        <div
                            class="offcanvas offcanvas-end col-6"
                            tabindex="-1"
                            id="{{ $offcanvasId }}"
                            aria-labelledby="{{ $offcanvasId }}-label"
                            style="height: 100vh;"
                            data-pdf-url="{{ $pdfUrl }}"
                        >

                            <div class="offcanvas-header">

                                <h5 id="{{ $offcanvasId }}-label">
                                    {{ $post->tieude }}
                                </h5>

                                <button
                                    type="button"
                                    class="btn-close"
                                    data-bs-dismiss="offcanvas"
                                    aria-label="Đóng"
                                ></button>

                            </div>


                            <div
                                class="offcanvas-body p-0"
                                style="height: calc(100vh - 56px); overflow-y: auto;"
                            >

                                <div
                                    id="{{ $offcanvasId }}-pdf"
                                    class="p-3"
                                >

                                    @if ($pdfUrl)

                                        <p>Đang tải file PDF...</p>

                                    @else

                                        <p class="text-danger">
                                            Không có file PDF đính kèm.
                                        </p>

                                    @endif

                                </div>

                            </div>

                        </div>

                    @endif

                </div>


                {{-- =========================================================
                    BÀI VIẾT TIẾP THEO
                    KHÔNG ẢNH ĐẠI DIỆN
                ========================================================== --}}

                @if (isset($nextPost))

                    @php
                        $nextUrl = $nextPost->menuslug . '/' . $nextPost->slug;
                    @endphp

                    <div class="next-news mt-4">

                        <div class="news-item next-news-horizontal">

                            <a href="{{ route('page.show', $nextUrl) }}">

                                <div class="content">

                                    <h4>
                                        {{ $nextPost->tieude }}
                                    </h4>

                                    <p>
                                        {{ Str::limit(strip_tags($nextPost->tomtat), 150) }}
                                    </p>

                                    <div class="news-date">

                                        <i class="fas fa-clock"></i>

                                        {{ \Carbon\Carbon::parse($nextPost->ngaydang)->format('d/m/Y') }}

                                    </div>

                                </div>

                            </a>

                        </div>

                    </div>

                @endif

            </div>


            {{-- =============================================================
                BÀI VIẾT CÙNG MENU
                KHÔNG ẢNH ĐẠI DIỆN
            ============================================================== --}}

            <div class="div2">

                <h2 class="section-title">
                    {{ $menu->name ?? 'Không rõ menu' }}
                </h2>


                <div class="suggested-news-container">

                    @forelse ($baivietCungMenu as $item)

                        @php
                            $articleUrl = $item->menuslug . '/' . $item->slug;
                        @endphp


                        <div class="news-item">

                            <div class="news-content">

                                <h4>

                                    <a href="{{ route('page.show', $articleUrl) }}">
                                        {{ $item->tieude }}
                                    </a>

                                </h4>


                                <p>
                                    {{ Str::limit(strip_tags($item->tomtat), 100) }}
                                </p>


                                <div class="news-date">

                                    <i class="fas fa-clock"></i>

                                    {{ \Carbon\Carbon::parse($item->ngaydang)->format('d/m/Y') }}

                                    <span class="news-menu">
                                        &nbsp;| {{ $item->tenmenu }}
                                    </span>

                                </div>

                            </div>

                        </div>

                    @empty

                        <p>Không có bài viết gợi ý.</p>

                    @endforelse

                </div>

            </div>

        </div>


        {{-- =============================================================
            BÀI VIẾT LIÊN QUAN
            KHÔNG ẢNH ĐẠI DIỆN
        ============================================================== --}}

        <div class="div3">

            <h2 class="section-title">
                {{ $menu->name ?? 'Không rõ menu' }} liên quan
            </h2>


            <div class="related-grid">

                @forelse ($relatedPosts as $rel)

                    @php
                        $relatedUrl = $rel->menuslug . '/' . $rel->slug;
                    @endphp


                    <div class="news-item">

                        <div class="news-content">

                            <h4>

                                <a href="{{ route('page.show', $relatedUrl) }}">
                                    {{ $rel->tieude }}
                                </a>

                            </h4>


                            <p>
                                {{ Str::limit(strip_tags($rel->tomtat), 120) }}
                            </p>


                            <div class="news-date">

                                <i class="fas fa-clock"></i>

                                {{ \Carbon\Carbon::parse($rel->ngaydang)->format('d/m/Y') }}

                            </div>

                        </div>

                    </div>

                @empty

                    <p>
                        Không có {{ $menu->name ?? 'Không rõ menu' }} liên quan.
                    </p>

                @endforelse

            </div>

        </div>


        {{-- =============================================================
            BÀI VIẾT NỔI BẬT
            KHÔNG ẢNH ĐẠI DIỆN
        ============================================================== --}}

        <div class="div4">

            <h2 class="section-title">
                {{ $menu->name ?? 'Không rõ menu' }} Nổi Bật
            </h2>


            <div class="featured-grid">

                @forelse ($featuredPosts as $feat)

                    @php
                        $featuredUrl = $feat->menuslug . '/' . $feat->slug;
                    @endphp


                    <div class="news-item">

                        <div class="news-content">

                            <h4>

                                <a href="{{ route('page.show', $featuredUrl) }}">
                                    {{ $feat->tieude }}
                                </a>

                            </h4>


                            <p>
                                {{ Str::limit(strip_tags($feat->tomtat), 120) }}
                            </p>


                            <div class="news-date">

                                <i class="fas fa-clock"></i>

                                {{ \Carbon\Carbon::parse($feat->ngaydang)->format('d/m/Y') }}

                            </div>

                        </div>

                    </div>

                @empty

                    <p>
                        Không có {{ $menu->name ?? 'Không rõ menu' }} nổi bật.
                    </p>

                @endforelse

            </div>

        </div>

    </div>

@endsection


@section('js')

    {{-- =============================================================
        GIỮ NGUYÊN LOGIC ĐẾM VIEW
    ============================================================== --}}

    <script>
        const postId = {{ $post->id }};
        const csrfToken = "{{ csrf_token() }}";

        setTimeout(function() {

            if (!sessionStorage.getItem('viewed_' + postId)) {

                fetch('/baiviet/' + postId + '/view', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                sessionStorage.setItem(
                    'viewed_' + postId,
                    true
                );
            }

        }, 10000);
    </script>


    <script src="/js/trangchu.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>

    <script src="/js/thongbao.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-bs5.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/lang/summernote-vi-VN.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

@endsection