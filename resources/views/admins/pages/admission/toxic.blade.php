@extends('admins.pages.admission.layout')

@section('admission_title', 'Cảnh báo mạng xã hội')
@section('admission_description', 'Theo dõi các bình luận bị AI đánh dấu là xúc phạm, spam hoặc nội dung cạnh tranh cần cán bộ kiểm tra.')

@section('admission_actions')
    <button class="btn btn-outline-secondary btn-toxic-filter" data-status="all">Tất cả</button>
    <button class="btn btn-outline-danger btn-toxic-filter" data-status="pending">Chờ xử lý</button>
@endsection

@section('admission_content')
<section class="admission-card" id="admissionToxicPage" data-status="pending">
    <div class="admission-card-header">
        <div>
            <h2 class="admission-card-title">Bình luận cần rà soát</h2>
            <div class="admission-card-subtitle">Thao tác xóa và block hiện phụ thuộc phần tích hợp Facebook Graph API</div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table admission-table">
            <thead><tr><th>Nguồn</th><th>Người đăng</th><th>Nội dung</th><th>Nhận định AI</th><th>Thời gian</th><th>Thao tác</th></tr></thead>
            <tbody id="toxicCommentsBody">
                <tr><td colspan="6" class="admission-loading">Đang tải cảnh báo...</td></tr>
            </tbody>
        </table>
    </div>
    <div class="admission-card-body pt-2" id="toxicPagination"></div>
</section>
@endsection

@section('admission_js')
    <script src="{{ asset('js/admins/admission/toxic.js') }}"></script>
@endsection
