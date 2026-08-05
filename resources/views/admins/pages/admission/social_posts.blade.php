@extends('admins.pages.admission.layout')

@section('admission_title', 'Lịch sử bài đã đăng')
@section('admission_description', 'Tra cứu nội dung, nền tảng, ảnh đại diện và thời điểm các bài viết được workflow tự động ghi nhận.')

@section('admission_actions')
    <button id="reloadSocialPosts" class="btn btn-outline-primary"><i class="fas fa-sync-alt me-1"></i> Làm mới</button>
@endsection

@section('admission_content')
<section class="admission-card" id="admissionSocialPostsPage">
    <div class="admission-card-header">
        <div>
            <h2 class="admission-card-title">Bài đăng mạng xã hội</h2>
            <div class="admission-card-subtitle">Dữ liệu lấy từ bảng social_posts</div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table admission-table">
            <thead><tr><th>Nền tảng</th><th>Tiêu đề / Nội dung</th><th>Hình ảnh</th><th>Thời gian đăng</th></tr></thead>
            <tbody id="socialPostsBody">
                <tr><td colspan="4" class="admission-loading">Đang tải lịch sử bài đăng...</td></tr>
            </tbody>
        </table>
    </div>
    <div class="admission-card-body pt-2" id="socialPostsPagination"></div>
</section>
@endsection

@section('admission_js')
    <script src="{{ asset('js/admins/admission/social_posts.js') }}"></script>
@endsection
