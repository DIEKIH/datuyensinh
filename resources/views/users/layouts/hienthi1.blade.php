<section id="news-archive">
    <div class="container" style="margin-left:0">
        <div class="first-block">
            <div id="ts-breadcrumb">
                <div class="link-breadcrumb">
                    <a href="#">Trang chủ</a><span class="slash">/</span>
                    <a class="last-a">Thông báo</a><span class="slash"></span>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="ts-news-main-title">Thông báo</div>
    </div>

    <!-- Phần hiển thị thông báo -->
    <div class="container">
        <div class="row">
            <div class="col-12 mt-4">
                @foreach ($posts['baiviet_posts'] as $baiviet)
                    @include('users.partials.baiviet', ['baiviet' => $baiviet])
                @endforeach
            </div>
        </div>
    </div>


    <!-- Phân trang -->
    {{-- <div class="archive-pagi d-flex justify-content-center mb-5" id="pagination"></div> --}}
    <div class="archive-pagi d-flex justify-content-center mb-5">
        {{ $posts['baiviet_posts']->links('pagination::bootstrap-4') }}
    </div>
</section>
