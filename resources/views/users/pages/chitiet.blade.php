@extends('users.layouts.app')

@section('title', $post->tieude ?? 'Chi tiết bài viết')

@section('css')
    {{-- CSS riêng cho trang này (nếu cần) --}}
    <!-- Bootstrap CSS -->
    <!-- Summernote CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-bs5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

    <link rel="stylesheet" href="{{ asset('css/chitiet.css') }}">

@endsection

<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">


@section('content')
    <div class="parent">
        <!-- div1 -->
        @php
            $offcanvasId = 'offcanvas-' . $post->id; // ID duy nhất
            $pdfUrl = !empty($post->file_url) ? asset($post->file_url) : null;
        @endphp
        <div class="detail-main-row">

            <div class="div1">
                <div class="page-container">
                    <!-- nội dung bài viết -->
                    <h1 class="main-title">{{ $post->tieude }}</h1>
                    <div class="meta-info">
                        <div class="meta-item">
                            <i class="fas fa-calendar-alt"></i>
                            {{ \Carbon\Carbon::parse($post->ngaydang)->format('d/m/Y') }}
                        </div>
                        <div class="meta-item"><i class="fas fa-user"></i>{{ $tacgia }}</div>

                        <div class="meta-item"><i class="fas fa-eye"></i>{{ number_format($post->views) }} lượt xem</div>
                    </div>

                    @php
                        $content = $post->noidung;

                        // Bọc iframe youtube/embed
                        $content = preg_replace(
                            '/<iframe(.*?)<\/iframe>/is',
                            '<div class="video-wrapper">$0</div>',
                            $content,
                        );

                        // Bọc video thường vào container responsive + căn giữa
                        $content = preg_replace_callback(
                            '/<video(.*?)>(.*?)<\/video>/is',
                            function ($matches) {
                                // Đảm bảo có thuộc tính controls
                                $attrs = $matches[1];
                                if (stripos($attrs, 'controls') === false) {
                                    $attrs .= ' controls';
                                }
                                // Xóa width/height cố định nếu có (tránh tràn màn hình)
                                $attrs = preg_replace('/\s*width\s*=\s*["\'][^"\']*["\']/i', '', $attrs);
                                $attrs = preg_replace('/\s*height\s*=\s*["\'][^"\']*["\']/i', '', $attrs);

                                return '<div class="video-container"><video' .
                                    $attrs .
                                    '>' .
                                    $matches[2] .
                                    '</video></div>';
                            },
                            $content,
                        );
                    @endphp

                    <div class="post-content">
                        {!! $content !!}
                    </div>
                    @if ($post->file_url)
                        <div class="mb-3">
                            <a class="btn-document" data-bs-toggle="offcanvas" href="#{{ $offcanvasId }}">
                                <i class="fas fa-file-pdf"></i>
                                Xem tài liệu đính kèm
                            </a>
                        </div>


                        <div class="offcanvas offcanvas-end col-6" tabindex="-1" id="{{ $offcanvasId }}"
                            aria-labelledby="{{ $offcanvasId }}-label" style="height: 100vh;"
                            data-pdf-url="{{ $pdfUrl }}">

                            <div class="offcanvas-header">
                                <h5 id="{{ $offcanvasId }}-label">{{ $post->tieude }}</h5> <!-- sửa ở đây -->
                                <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
                                    aria-label="Đóng"></button>
                            </div>

                            <div class="offcanvas-body p-0" style="height: calc(100vh - 56px); overflow-y: auto;">
                                <div id="{{ $offcanvasId }}-pdf" class="p-3">
                                    @if ($pdfUrl)
                                        <p>Đang tải file PDF...</p>
                                    @else
                                        <p class="text-danger">Không có file PDF đính kèm.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                </div>

                @if (isset($nextPost))
                    @php
                        // Kiểm tra ảnh của bài viết kế tiếp
                        $nextImage = $nextPost->image_url;
                        $defaultImage = asset('images/system/Rectangle_3897.jpg');

                        // SAU - chỉ cần kiểm tra empty, không check file tồn tại
                        if (empty($nextImage)) {
                            $nextImage = $defaultImage;
                        } else {
                            $nextImage = asset($nextImage);
                        }

                        // ✅ FIX 1: Tạo URL đầy đủ cho bài viết kế tiếp
                        $nextUrl = $nextPost->menuslug . '/' . $nextPost->slug;
                    @endphp

                    <div class="next-news mt-4">
                        <div class="news-item next-news-horizontal">
                            <a href="{{ route('page.show', $nextUrl) }}">
                                <img src="{{ $nextImage }}" alt="{{ $nextPost->tieude }}">
                                <div class="content">
                                    <h4>{{ $nextPost->tieude }}</h4>
                                    <p>{{ Str::limit(strip_tags($nextPost->tomtat), 100) }}</p>
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


            <!-- div2 -->
            <div class="div2">
                <h2 class="section-title">{{ $menu->name ?? 'Không rõ menu' }}</h2>

                <div class="suggested-news-container">
                    @forelse ($baivietCungMenu as $item)
                        @php
                            $imageUrl = $item->image_url;
                            $defaultImage = asset('images/system/Rectangle_3897.jpg');

                            if (empty($imageUrl)) {
                                $imageUrl = $defaultImage;
                            } else {
                                $imageUrl = asset($imageUrl);
                            }

                            // URL bài viết
                            $articleUrl = $item->menuslug . '/' . $item->slug;
                        @endphp

                        <div class="news-item">
                            <!-- 🔗 LINK ẢNH -->
                            <a href="{{ route('page.show', $articleUrl) }}" class="news-thumb">
                                <img src="{{ $imageUrl }}" alt="{{ $item->tieude }}">
                            </a>

                            <div class="news-content">
                                <h4>
                                    <!-- 🔗 LINK TIÊU ĐỀ -->
                                    <a href="{{ route('page.show', $articleUrl) }}">
                                        {{ $item->tieude }}
                                    </a>
                                </h4>

                                <p>{{ Str::limit(strip_tags($item->tomtat), 80) }}</p>

                                <div class="news-date">
                                    <i class="fas fa-clock"></i>
                                    {{ \Carbon\Carbon::parse($item->ngaydang)->format('d/m/Y') }}
                                    <span class="news-menu"> | {{ $item->tenmenu }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p>Không có bài viết gợi ý.</p>
                    @endforelse
                </div>
            </div>


        </div>

        <!-- div3 -->
        <div class="div3">
            <h2 class="section-title">{{ $menu->name ?? 'Không rõ menu' }} liên quan</h2>

            <div class="related-grid">
                @forelse ($relatedPosts as $rel)
                    @php
                        $relImage = $rel->image_url;
                        $defaultImage = asset('images/system/Rectangle_3897.jpg');

                        if (empty($relImage)) {
                            $relImage = $defaultImage;
                        } else {
                            $relImage = asset($relImage);
                        }
                        $relatedUrl = $rel->menuslug . '/' . $rel->slug;
                    @endphp

                    <div class="news-item">
                        <!-- 🔗 LINK ẢNH -->
                        <a href="{{ route('page.show', $relatedUrl) }}" class="news-thumb">
                            <img src="{{ $relImage }}" alt="{{ $rel->tieude }}">
                        </a>

                        <div class="news-content">
                            <h4>
                                <!-- 🔗 LINK TIÊU ĐỀ -->
                                <a href="{{ route('page.show', $relatedUrl) }}">
                                    {{ $rel->tieude }}
                                </a>
                            </h4>

                            <p>{{ Str::limit(strip_tags($rel->tomtat), 80) }}</p>

                            <div class="news-date">
                                <i class="fas fa-clock"></i>
                                {{ \Carbon\Carbon::parse($rel->ngaydang)->format('d/m/Y') }}
                            </div>
                        </div>
                    </div>
                @empty
                    <p>Không có {{ $menu->name ?? 'Không rõ menu' }} liên quan.</p>
                @endforelse
            </div>
        </div>



        <!-- div4 -->
        <div class="div4">
            <h2 class="section-title">{{ $menu->name ?? 'Không rõ menu' }} Nổi Bật</h2>

            <div class="featured-grid">
                @forelse ($featuredPosts as $feat)
                    @php
                        $featImage = $feat->image_url;
                        $defaultImage = asset('images/system/Rectangle_3897.jpg');

                        if (empty($featImage)) {
                            $featImage = $defaultImage;
                        } else {
                            $featImage = asset($featImage);
                        }

                        $featuredUrl = $feat->menuslug . '/' . $feat->slug;
                    @endphp

                    <div class="news-item">
                        <!-- 🔗 LINK ẢNH -->
                        <a href="{{ route('page.show', $featuredUrl) }}" class="news-thumb">
                            <img src="{{ $featImage }}" alt="{{ $feat->tieude }}">
                        </a>

                        <div class="news-content">
                            <h4>
                                <!-- 🔗 LINK TIÊU ĐỀ -->
                                <a href="{{ route('page.show', $featuredUrl) }}">
                                    {{ $feat->tieude }}
                                </a>
                            </h4>

                            <p>{{ Str::limit(strip_tags($feat->tomtat), 80) }}</p>

                            <div class="news-date">
                                <i class="fas fa-clock"></i>
                                {{ \Carbon\Carbon::parse($feat->ngaydang)->format('d/m/Y') }}
                            </div>
                        </div>
                    </div>
                @empty
                    <p>Không có {{ $menu->name ?? 'Không rõ menu' }} nổi bật.</p>
                @endforelse
            </div>
        </div>


    </div>

@endsection


@section('js')
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
                sessionStorage.setItem('viewed_' + postId, true);
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
