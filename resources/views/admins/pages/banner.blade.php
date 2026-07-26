@extends('admins.layouts.app')
@section('title', 'Quản lý Banner')
@section('content')
<div class="container-fluid py-3">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0 fw-semibold">Quản lý Banner</h5>
        <button class="btn btn-primary btn-sm" onclick="moModalThem()">
            <i class="fas fa-plus me-1"></i> Thêm banner
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table id="bannerTable" class="table table-bordered table-striped mb-0" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th class="text-center">Ảnh preview</th>
                        <th>Thứ tự</th>
                        <th class="text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal thêm / sửa --}}
<div class="modal fade" id="bannerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header py-2 px-3">
                <h5 class="modal-title" id="bannerModalTitle">Thêm banner</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="bannerForm">
                    <input type="hidden" id="bannerId" name="bannerId" value="">

                    <div class="mb-3">
                        <label class="form-label">Ảnh banner <sup class="text-danger">*</sup></label>
                        <input type="file" class="form-control form-control-sm" id="bannerImage"
                            name="image" accept="image/*" onchange="previewBannerImage(event)">
                        <sup id="bannerImageError" class="text-danger"></sup>
                        <div class="mt-2">
                            <img id="bannerPreview" src="#" alt="Preview"
                                class="img-fluid rounded" style="max-height:180px; display:none;">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Thứ tự</label>
                        <input type="number" class="form-control form-control-sm"
                            id="bannerOrder" name="order" value="1" min="1">
                    </div>
                </form>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="luuBanner()">
                    <i class="far fa-save me-1"></i> Lưu
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
$(document).ready(function () {
    console.log('DOM ready - banner page'); 
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    loadBanners();
});

function loadBanners() {
    $.ajax({
        url: '/admin/tatcabanner',
        type: 'GET',
        success: function (res) {
            console.log('Banner data:', res.data);
            renderBannerTable(res.data || []);
        },
        error: function () {
            toastr.error('Không thể tải danh sách banner.');
        }
    });
}

function renderBannerTable(data) {
    if ($.fn.DataTable.isDataTable('#bannerTable')) {
        $('#bannerTable').DataTable().destroy();
    }

    $('#bannerTable').DataTable({
        data: data,
        columns: [
            { data: 'id', title: 'ID', width: '50px' },
            {
                data: 'image_url',
                title: 'Ảnh preview',
                orderable: false,
                className: 'text-center align-middle',
                render: function (val) {
    return val
        ? `<img src="${val}" style="height:60px;object-fit:cover;border-radius:4px;">`
        : '<span class="text-muted">Không có ảnh</span>';
}
            },
            { data: 'order', title: 'Thứ tự', width: '80px' },
            {
                data: null,
                title: 'Thao tác',
                orderable: false,
                className: 'text-center',
                width: '120px',
                render: function (data, type, row) {
                    return `
                        <button class="btn btn-sm btn-outline-primary me-1" onclick="suaBanner(${row.id})" title="Sửa">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="xoaBanner(${row.id})" title="Xóa">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    `;
                }
            }
        ],
        order: [[2, 'asc']],
        paging: false,
        searching: false,
        info: false,
        language: { emptyTable: 'Chưa có banner nào' }
    });
}

// Mở modal thêm mới
function moModalThem() {
    $('#bannerModalTitle').text('Thêm banner');
    $('#bannerForm')[0].reset();
    $('#bannerId').val('');
    $('#bannerPreview').hide().attr('src', '#');
    $('#bannerImageError').text('');
    bootstrap.Modal.getOrCreateInstance(document.getElementById('bannerModal')).show();
}

// Mở modal sửa
function suaBanner(id) {
    $.ajax({
        url: '/admin/tatcabanner',
        type: 'GET',
        success: function (res) {
            const banner = res.data.find(b => b.id == id);
            if (!banner) return toastr.error('Không tìm thấy banner.');

            $('#bannerModalTitle').text('Sửa banner');
            $('#bannerId').val(banner.id);
            $('#bannerOrder').val(banner.order);
            $('#bannerImageError').text('');

            if (banner.image_url) {
                $('#bannerPreview').attr('src', banner.image_url).show();
            } else {
                $('#bannerPreview').hide().attr('src', '#');
            }

            bootstrap.Modal.getOrCreateInstance(document.getElementById('bannerModal')).show();
        }
    });
}

// Lưu banner
function luuBanner() {
    $('#bannerImageError').text('');

    let form = new FormData(document.getElementById('bannerForm'));

    $.ajax({
        url: '/admin/banner/store',
        type: 'POST',
        data: form,
        contentType: false,
        processData: false,
        success: function (res) {
            if (res.status === false && res.errors) {
                if (res.errors.image) $('#bannerImageError').text('* ' + res.errors.image[0]);
                toastr.error('Có lỗi, vui lòng kiểm tra lại.');
            } else if (res.success) {
                toastr.success(res.message);
                bootstrap.Modal.getOrCreateInstance(document.getElementById('bannerModal')).hide();
                loadBanners();
            }
        },
        error: function () {
            toastr.error('Không thể lưu banner.');
        }
    });
}

// Xóa banner
function xoaBanner(id) {
    Swal.fire({
        title: 'Xóa banner?',
        text: 'Bạn có chắc muốn xóa banner này?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Xóa',
        cancelButtonText: 'Hủy'
    }).then(result => {
        if (result.isConfirmed) {
            $.ajax({
                url: '/admin/banner/' + id,
                type: 'DELETE',
                success: function (res) {
                    if (res.success) {
                        toastr.success(res.message);
                        
                        loadBanners();
                    }
                },
                error: function () {
                    toastr.error('Không thể xóa banner.');
                }
            });
        }
    });
}

// Preview ảnh
function previewBannerImage(event) {
    const file = event.target.files[0];
    if (file) {
        $('#bannerPreview').attr('src', URL.createObjectURL(file)).show();
    } else {
        $('#bannerPreview').hide().attr('src', '#');
    }
}
</script>
@endsection