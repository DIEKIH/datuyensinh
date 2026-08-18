@extends('admins.pages.admission.layout')

@section('admission_title', 'Theo dõi n8n')
@section(
    'admission_description',
    'Theo dõi trạng thái hoạt động và các lỗi phát sinh trong các luồng tự động hóa n8n.'
)

@section('admission_actions')
    <button
        id="reloadN8nLogs"
        class="btn btn-outline-primary"
        type="button"
    >
        <i class="fas fa-sync-alt me-1"></i>
        Làm mới
    </button>
@endsection

@section('admission_content')
<section
    class="admission-card"
    id="admissionN8nPage"
>
    <div class="admission-card-header">
        <div>
            <h2 class="admission-card-title">
                Nhật ký hoạt động n8n
            </h2>

            <div class="admission-card-subtitle">
                Theo dõi luồng xử lý thành công và các lỗi phát sinh từ n8n
            </div>
        </div>

        <div class="admission-filter-group">
            <input
                id="n8nKeyword"
                class="form-control form-control-sm admission-search"
                placeholder="Tìm tên luồng, sự kiện, node hoặc nội dung lỗi"
            >

            <select
                id="n8nStatus"
                class="form-select form-select-sm"
                style="width: 160px;"
            >
                <option value="">
                    Tất cả trạng thái
                </option>

                <option value="ok">
                    Thành công
                </option>

                <option value="error">
                    Lỗi
                </option>
            </select>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table admission-table">
            <thead>
                <tr>
                    <th>Luồng n8n</th>
                    <th>Sự kiện</th>
                    <th>Kết quả</th>
                    <th>Chi tiết</th>
                    <th>Thời gian</th>
                </tr>
            </thead>

            <tbody id="n8nRows">
                <tr>
                    <td
                        colspan="5"
                        class="admission-loading"
                    >
                        Đang tải nhật ký n8n...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div
        class="admission-card-body pt-2"
        id="n8nPagination"
    ></div>
</section>
@endsection

@section('admission_js')
    <script
        src="{{ asset('js/admins/admission/n8n_logs.js') }}"
    ></script>
@endsection