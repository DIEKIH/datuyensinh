@extends('admins.pages.admission.layout')

@section('admission_title', 'Danh sách lead')
@section('admission_description', 'Tìm kiếm, phân loại và cập nhật trạng thái chăm sóc thí sinh tiềm năng từ các kênh tương tác.')

@section('admission_actions')
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#leadModal">
        <i class="fas fa-plus me-1"></i> Thêm lead
    </button>
@endsection

@section('admission_content')
<section class="admission-card" id="admissionLeadsPage">
    <div class="admission-card-header">
        <div>
            <h2 class="admission-card-title">Thí sinh tiềm năng</h2>
            <div class="admission-card-subtitle">Facebook ID được hiển thị khi lead đến từ Facebook hoặc Messenger</div>
        </div>
        <div class="admission-filter-group">
            <input id="leadKeyword" class="form-control form-control-sm admission-search"
                placeholder="Tên, số điện thoại, email, ngành, Facebook ID">
            <select id="leadGrade" class="form-select form-select-sm" style="width: 150px;">
                <option value="">Mọi mức điểm</option>
                <option value="hot">Hot</option>
                <option value="warm">Warm</option>
                <option value="cold">Cold</option>
            </select>
            <button id="reloadLeads" class="btn btn-outline-primary btn-sm" title="Tải lại">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table admission-table">
            <thead>
                <tr>
                    <th>Khách hàng</th>
                    <th>Liên hệ</th>
                    <th>Nguồn</th>
                    <th>Ngành / Tỉnh</th>
                    <th>Điểm</th>
                    <th>Trạng thái Sale</th>
                    <th>Tương tác cuối</th>
                </tr>
            </thead>
            <tbody id="leadRows">
                <tr><td colspan="7" class="admission-loading">Đang tải danh sách lead...</td></tr>
            </tbody>
        </table>
    </div>
    <div class="admission-card-body pt-2" id="leadsPagination"></div>
</section>

<div class="modal fade" id="leadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="leadForm">
                @csrf
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">Thêm lead thủ công</h5>
                        <div class="text-muted small">Thông tin có thể bổ sung dần trong quá trình chăm sóc.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Họ tên</label><input name="full_name" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">Kênh</label><input name="channel" class="form-control" value="manual" required></div>
                        <div class="col-md-6"><label class="form-label">Số điện thoại</label><input name="phone" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">Email</label><input name="email" type="email" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">Ngành quan tâm</label><input name="intended_major" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">Tỉnh/Thành</label><input name="province" class="form-control"></div>
                        <div class="col-md-12"><label class="form-label">Nguồn chiến dịch</label><input name="source_campaign" class="form-control"></div>
                        <div class="col-md-12"><label class="form-label">Ghi chú</label><textarea name="note" class="form-control" rows="3"></textarea></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Lưu lead</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="leadDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title">Chi tiết Thí sinh: <strong id="detailLeadName"></strong></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs" id="leadDetailTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" id="score-log-tab" data-bs-toggle="tab" data-bs-target="#score-log" type="button" role="tab">Lịch sử cộng điểm</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="activity-log-tab" data-bs-toggle="tab" data-bs-target="#activity-log" type="button" role="tab">Tương tác</button>
                    </li>
                </ul>
                <div class="tab-content p-3 border border-top-0">
                    <div class="tab-pane fade show active" id="score-log" role="tabpanel">
                        <table class="table table-sm admission-table">
                            <thead>
                                <tr>
                                    <th>Thời gian</th>
                                    <th>Kênh / Nguồn</th>
                                    <th>Thao tác</th>
                                    <th>Tiêu chí thỏa mãn</th>
                                    <th>Điểm cộng</th>
                                </tr>
                            </thead>
                            <tbody id="detailScoreLogs"></tbody>
                        </table>
                    </div>
                    <div class="tab-pane fade" id="activity-log" role="tabpanel">
                        <div id="detailActivities" style="max-height: 400px; overflow-y: auto;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('admission_js')
    <script src="{{ asset('js/admins/admission/leads.js') }}"></script>
@endsection
