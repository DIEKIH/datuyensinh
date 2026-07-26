@extends('admins.layouts.app')

@section('title', 'Quản lý Con số nổi bật')

@section('css')
    <link rel="stylesheet" href="/css/admins/nganhhoc.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .icon-preview { font-size: 2rem; color: var(--bs-primary, #0d6efd); }
        .icon-input-group { display: flex; gap: 8px; align-items: center; }
        .icon-input-group input { flex: 1; }

        #statsTable_wrapper .dt-layout-row:last-child {
            position: fixed;
            width: calc(100% - 250px);
            bottom: 0;
            left: 250px;
            background: #fff;
            padding: 8px 16px;
            z-index: 999;
            border-top: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="page-header">
        <h1 class="page-title">Những con số nổi bật</h1>
        <button id="addStatBtn" class="btn btn-primary">Thêm mới +</button>
    </div>

    <div class="table-responsive d-flex flex-column">
        <table id="statsTable" class="display table table-bordered table-striped" style="width:100%">
            <thead><tr></tr></thead>
            <tbody></tbody>
        </table>
    </div>
</div>

{{-- MODAL THÊM / SỬA --}}
<div class="modal fade" id="statModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header py-2 px-3">
                <h5 class="modal-title" id="statModalTitle">Thêm mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="statForm" novalidate>
                    @csrf
                    <input type="hidden" name="statId" id="statId" value="">

                    <div class="mb-3">
                        <label class="form-label">Icon (FontAwesome class) <sup>*</sup></label>
                        <div class="icon-input-group">
                            <input type="text" id="stat_icon" name="icon"
                                class="form-control form-control-sm"
                                placeholder="VD: fas fa-users"
                                oninput="previewIcon(this.value)">
                            <span class="icon-preview"><i id="iconPreviewEl" class="fas fa-star"></i></span>
                        </div>
                        <sup id="err_icon" style="color:red;font-size:small;font-weight:400;"></sup>
                        <div class="mt-1 text-muted" style="font-size:12px">
                            Tra icon tại <a href="https://fontawesome.com/icons" target="_blank">fontawesome.com/icons</a>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Số lượng <sup>*</sup></label>
                        <input type="text" id="stat_so_luong" name="so_luong"
                            class="form-control form-control-sm"
                            placeholder="VD: 20,000 hoặc 15 TỶ+">
                        <sup id="err_so_luong" style="color:red;font-size:small;font-weight:400;"></sup>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tiêu đề <sup>*</sup></label>
                        <input type="text" id="stat_title" name="title"
                            class="form-control form-control-sm"
                            placeholder="VD: Sinh viên đang theo học">
                        <sup id="err_title" style="color:red;font-size:small;font-weight:400;"></sup>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Thứ tự</label>
                        <input type="number" id="stat_thutu" name="thutu" min="0"
                            class="form-control form-control-sm" placeholder="0">
                    </div>

                    <div class="text-end mt-3">
                        <button type="button" class="btn btn-outline-secondary btn-sm me-2"
                            onclick="clearStatForm()">
                            <i class="fas fa-eraser me-1"></i>Clear
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="far fa-save me-1"></i> Lưu
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
    <script src="{{ asset('js/admins/highlight_stats.js') }}"></script>
@endsection