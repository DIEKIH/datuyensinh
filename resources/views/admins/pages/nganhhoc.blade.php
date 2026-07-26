@extends('admins.layouts.app')

@section('title', 'Quản lý Ngành học')

@section('css')
    <link rel="stylesheet" href="/css/admins/nganhhoc.css">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-bs5.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endsection

@section('content')
    <div class="container-fluid">
        <div class="page-header">
            <h1 class="page-title">Danh sách Ngành học</h1>
            <button id="addNganhBtn" class="btn btn-primary">
                Thêm Ngành học +
            </button>
        </div>

        {{-- Bộ lọc --}}
        <div class="filter-bar">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label form-label-sm mb-1">Khoa</label>
                    <select id="filterKhoa" class="form-select form-select-sm">
                        <option value="">-- Tất cả khoa --</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Bậc đào tạo</label>
                    <select id="filterBac" class="form-select form-select-sm">
                        <option value="">-- Tất cả --</option>
                        <option value="Đại học">Đại học</option>
                        <option value="Cao đẳng">Cao đẳng</option>
                        <option value="Thạc sĩ">Thạc sĩ</option>
                        <option value="Tiến sĩ">Tiến sĩ</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button id="btnFilter" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-filter me-1"></i>Lọc
                    </button>
                    <button id="btnResetFilter" class="btn btn-sm btn-outline-secondary ms-1">
                        <i class="fas fa-redo me-1"></i>Reset
                    </button>
                </div>
            </div>
        </div>

        <div class="table-responsive d-flex flex-column">
            <table id="nganhTable" class="display table table-bordered table-striped" style="width:100%">
                <thead>
                    <tr></tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    {{-- ==================== MODAL THÊM / SỬA ==================== --}}
    <div class="modal fade" id="nganhModal" data-bs-focus="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header py-2 px-3">
                    <h5 class="modal-title" id="nganhModalTitle">Tạo mới ngành học</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="nganhForm" novalidate>
                        @csrf
                        <input type="hidden" name="nganhId" id="nganhId" value="">

                        <div class="row">
                            {{-- ===== LEFT COLUMN ===== --}}
                            <div class="col-md-2 left-col">

                                <div class="mb-3">
                                    <label class="form-label">Khoa <sup>*</sup></label>
                                    <select id="khoa_id" name="khoa_id" class="form-select form-select-sm short-dropdown">
                                        <option value="">Chọn khoa</option>
                                    </select>
                                    <sup id="err_khoa_id" style="color:red;font-size:small;font-weight:400;"></sup>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Mã ngành</label>
                                    <input type="text" id="ma_nganh" name="ma_nganh"
                                        class="form-control form-control-sm" placeholder="VD: 7480201">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Loại hình</label>
                                    <select id="loai_hinh" name="loai_hinh"
                                        class="form-select form-select-sm short-dropdown">
                                        <option value="">-- Chọn loại hình --</option>
                                        <option value="Đại học chính quy">Đại học chính quy</option>
                                        <option value="Liên thông">Liên thông</option>
                                        <option value="Vừa làm vừa học">Vừa làm vừa học</option>
                                        <option value="Đào tạo từ xa">Đào tạo từ xa</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Bậc đào tạo</label>
                                    <select id="bac_dao_tao" name="bac_dao_tao"
                                        class="form-select form-select-sm short-dropdown">
                                        <option value="">-- Chọn bậc --</option>
                                        <option value="Đại học">Đại học</option>
                                        <option value="Cao đẳng">Cao đẳng</option>
                                        <option value="Thạc sĩ">Thạc sĩ</option>
                                        <option value="Tiến sĩ">Tiến sĩ</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Thời gian đào tạo</label>
                                    <input type="text" id="thoi_gian" name="thoi_gian"
                                        class="form-control form-control-sm" placeholder="VD: 4 năm">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Số tín chỉ</label>
                                    <input type="number" id="so_tin_chi" name="so_tin_chi" min="0"
                                        class="form-control form-control-sm" placeholder="VD: 130">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Chỉ tiêu</label>
                                    <input type="number" id="chi_tieu" name="chi_tieu" min="0"
                                        class="form-control form-control-sm" placeholder="VD: 100">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Học phí (VNĐ/TC)</label>
                                    <input type="number" id="hoc_phi" name="hoc_phi" min="0"
                                        class="form-control form-control-sm" placeholder="VD: 20000000">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Cơ sở đào tạo</label>
                                    <input type="text" id="co_so" name="co_so"
                                        class="form-control form-control-sm" placeholder="VD: Cơ sở 1 - Q.5">
                                </div>

                            </div>

                            {{-- ===== RIGHT COLUMN ===== --}}
                            <div class="col-md-10 right-col">

                                <div class="mb-3">
                                    <label class="form-label">Tên ngành <sup>*</sup></label>
                                    <input type="text" id="ten_nganh" name="ten_nganh"
                                        class="form-control form-control-sm" placeholder="Nhập tên ngành học">
                                    <sup id="err_ten_nganh"
                                        style="color:red;font-size:small;font-weight:400;position:absolute;top:72px;right:23px;"></sup>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Hình thức xét tuyển</label>
                                    <input type="text" id="hinh_thuc_xet" name="hinh_thuc_xet"
                                        class="form-control form-control-sm"
                                        placeholder="VD: Xét học bạ, Xét điểm thi THPT">
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Ảnh đại diện ngành</label>
                                        <input type="file" class="form-control form-control-sm" id="nganh_image"
                                            name="image" accept="image/*" onchange="previewNganhImage(event)">
                                        <img id="nganhImagePreview" class="img-preview img-fluid mt-2" src="#"
                                            alt="Preview" style="display:none;">
                                        <sup id="err_image" style="color:red;font-size:small;font-weight:400;"></sup>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Tổ hợp xét tuyển</label>
                                        <div class="input-group input-group-sm">
                                            <select id="to_hop_ids" name="to_hop_ids[]"
                                                class="form-select form-control select2-tohop"
                                                style="border:var(--bs-border-width) solid var(--bs-border-color);"
                                                multiple="multiple">
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Tóm tắt</label>
                                    <textarea name="tomtat" id="tomtat_nganh" class="form-control form-control-sm" rows="3"
                                        placeholder="Giới thiệu ngắn về ngành học..."></textarea>
                                </div>
                                <div class="profile-vector-box mt-3">
                                    <div class="pv-head">
                                        <div>
                                            <h6>Hồ sơ gợi ý ngành</h6>
                                            <p>Chấm điểm 0–5 cho từng năng lực/sở thích. Hệ thống dùng điểm này để gợi ý
                                                ngành.</p>
                                        </div>
                                    </div>

                                    @php
                                        $vectors = [
                                            'logic' => 'Tư duy logic',
                                            'data' => 'Phân tích dữ liệu',
                                            'technical' => 'Kỹ thuật',
                                            'creativity' => 'Sáng tạo',
                                            'communication' => 'Giao tiếp',
                                            'management' => 'Quản lý',
                                            'language' => 'Ngôn ngữ',
                                            'legal' => 'Pháp lý',
                                            'experiment' => 'Thí nghiệm',
                                            'fieldwork' => 'Hiện trường',
                                        ];
                                    @endphp

                                    <div class="row g-2">
                                        @foreach ($vectors as $key => $label)
                                            <div class="col-md-6">
                                                <div class="pv-item">

                                                    <div class="pv-label">

                                                        <span>{{ $label }}</span>

                                                        <strong id="pv_text_{{ $key }}">
                                                            0
                                                        </strong>

                                                    </div>

                                                    <input type="range" min="0" max="5" step="1"
                                                        value="0" class="form-range pv-range"
                                                        name="profile_vector[{{ $key }}]"
                                                        id="pv_{{ $key }}" data-key="{{ $key }}">

                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Nội dung chi tiết</label>
                                    <div id="summernoteNganh" name="noidung"></div>
                                </div>

                                <div class="text-end mt-3">
                                    <button type="button" class="btn btn-outline-secondary btn-sm me-2"
                                        onclick="clearNganhForm()">
                                        <i class="fas fa-eraser me-1"></i>Clear
                                    </button>
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="far fa-save me-1"></i> Lưu ngành học
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- ==================== MODAL XEM CHI TIẾT ==================== --}}
    <div class="modal fade" id="nganhDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header py-2 px-3">
                    <h5 class="modal-title" id="detailTitle">Chi tiết ngành học</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailBody"></div>
            </div>
        </div>
    </div>



    <div id="nganhLoadingOverlay">
    <div class="text-center">
        <div class="loading-dots mb-2">
            <span></span><span></span><span></span>
        </div>
        <div id="nganhLoadingText" style="font-size:14px;color:#555;">
            Đang lưu ngành học...
        </div>
    </div>
</div>

@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-bs5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/lang/summernote-vi-VN.min.js"></script>
    <script src="{{ asset('js/admins/nganhhoc.js') }}"></script>
@endsection
