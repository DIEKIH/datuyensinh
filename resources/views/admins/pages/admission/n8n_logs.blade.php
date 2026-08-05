@extends('admins.pages.admission.layout')

@section('admission_title', 'Theo dõi n8n')
@section('admission_description', 'Giám sát các sự kiện webhook, trạng thái xử lý và thông báo lỗi gần nhất giữa Laravel với n8n.')

@section('admission_actions')
    <button id="reloadN8nLogs" class="btn btn-outline-primary"><i class="fas fa-sync-alt me-1"></i> Làm mới</button>
@endsection

@section('admission_content')
<section class="admission-card" id="admissionN8nPage">
    <div class="admission-card-header">
        <div>
            <h2 class="admission-card-title">Nhật ký tích hợp</h2>
            <div class="admission-card-subtitle">Endpoint theo dõi: /admin/admission-cms/n8n/logs</div>
        </div>
        <div class="admission-filter-group">
            <input id="n8nKeyword" class="form-control form-control-sm admission-search" placeholder="Workflow, event hoặc message">
            <select id="n8nStatus" class="form-select form-select-sm" style="width: 150px;">
                <option value="">Mọi trạng thái</option>
                <option value="received">Received</option>
                <option value="success">Success</option>
                <option value="failed">Failed</option>
                <option value="error">Error</option>
            </select>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table admission-table">
            <thead><tr><th>Workflow</th><th>Event</th><th>Trạng thái</th><th>Thông báo</th><th>Thời gian</th></tr></thead>
            <tbody id="n8nRows">
                <tr><td colspan="5" class="admission-loading">Đang tải nhật ký n8n...</td></tr>
            </tbody>
        </table>
    </div>
    <div class="admission-card-body pt-2" id="n8nPagination"></div>
</section>
@endsection

@section('admission_js')
    <script src="{{ asset('js/admins/admission/n8n_logs.js') }}"></script>
@endsection
