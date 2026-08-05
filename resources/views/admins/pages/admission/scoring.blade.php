@extends('admins.pages.admission.layout')

@section('admission_title', 'Tiêu chí chấm điểm lead')
@section('admission_description', 'Quản lý các điều kiện cộng điểm, trường dữ liệu, toán tử và mức ưu tiên dùng để phân loại Cold, Warm, Hot.')

@section('admission_actions')
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#criterionModal" id="btnAddNewCrit">
        <i class="fas fa-plus me-1"></i> Thêm tiêu chí
    </button>
    <button id="reloadCriteria" class="btn btn-outline-primary">
        <i class="fas fa-sync-alt"></i>
    </button>
@endsection

@section('admission_content')
<section class="admission-card" id="admissionScoringPage">
    <div class="admission-card-header">
        <div>
            <h2 class="admission-card-title">Danh sách tiêu chí</h2>
            <div class="admission-card-subtitle">Các tiêu chí đang bật sẽ tham gia quá trình chấm điểm lead</div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table admission-table">
            <thead>
                <tr>
                    <th>Mã</th><th>Tên</th><th>Nhóm</th><th>Trường dữ liệu</th><th>Kiểu</th>
                    <th>Toán tử</th><th>Giá trị</th><th>Điểm</th><th>Trạng thái</th><th>Thao tác</th>
                </tr>
            </thead>
            <tbody id="criteriaRows">
                <tr><td colspan="10" class="admission-loading">Đang tải tiêu chí...</td></tr>
            </tbody>
        </table>
    </div>
</section>

<div class="modal fade" id="criterionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="criterionForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="critModalTitle">Thêm tiêu chí chấm điểm</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="crit_id">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Mã tiêu chí</label><input id="crit_code" class="form-control" required></div>
                        <div class="col-md-8"><label class="form-label">Tên tiêu chí</label><input id="crit_name" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Nhóm tiêu chí</label><input id="crit_category" class="form-control" list="categoryList"><datalist id="categoryList"><option value="Chung"><option value="Thông tin cá nhân"><option value="Nhu cầu học tập"><option value="Tương tác mạng xã hội"></datalist></div>
                        <div class="col-md-6"><label class="form-label">Trường dữ liệu</label><input id="crit_field" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label">Kiểu nhập</label><select id="crit_input_type" class="form-select"><option value="text">Văn bản</option><option value="number">Số</option><option value="select">Danh sách</option><option value="checkbox">Hộp chọn</option><option value="date">Ngày</option></select></div>
                        <div class="col-md-4"><label class="form-label">Toán tử</label><select id="crit_op" class="form-select"><option value="=">Bằng (=)</option><option value=">">Lớn hơn (&gt;)</option><option value=">=">Lớn hơn hoặc bằng (&gt;=)</option><option value="&lt;">Nhỏ hơn (&lt;)</option><option value="&lt;=">Nhỏ hơn hoặc bằng (&lt;=)</option><option value="contains">Chứa chuỗi</option><option value="has_value">Có dữ liệu</option></select></div>
                        <div class="col-md-4"><label class="form-label">Giá trị so sánh</label><input id="crit_val" class="form-control"></div>
                        <div class="col-md-6" id="crit_options_wrap" style="display:none;"><label class="form-label">Các lựa chọn, cách nhau dấu phẩy</label><textarea id="crit_options" class="form-control" rows="2"></textarea></div>
                        <div class="col-md-3"><label class="form-label">Điểm cộng</label><input id="crit_score" type="number" class="form-control" required></div>
                        <div class="col-md-3"><label class="form-label">Ưu tiên</label><input id="crit_priority" type="number" class="form-control" value="0"></div>
                        <div class="col-12 d-flex gap-4">
                            <label class="form-check"><input type="checkbox" id="crit_show_public" class="form-check-input"> <span class="form-check-label">Hiện trên form public</span></label>
                            <label class="form-check"><input type="checkbox" id="crit_is_required" class="form-check-input"> <span class="form-check-label">Bắt buộc nhập</span></label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Lưu tiêu chí</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('admission_js')
    <script src="{{ asset('js/admins/admission/scoring.js') }}"></script>
@endsection
