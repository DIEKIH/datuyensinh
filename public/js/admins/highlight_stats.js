// =====================================================================
// highlight_stats.js  –  Quản lý Những con số nổi bật
// =====================================================================

let statsTable;

$(document).ready(function () {
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });
    loadStats();
});

// ─── Load bảng ────────────────────────────────────────────────────────
function loadStats() {
    $.getJSON('/admin/highlight-stats/list', function (res) {
        initTable(res.data ?? []);
    }).fail(function () {
        toastr.error('Không tải được danh sách');
    });
}

function initTable(data) {
    if (statsTable) {
        statsTable.clear().rows.add(data).draw();
        return;
    }
    statsTable = $('#statsTable').DataTable({
        data: data,
        paging: true,
        lengthMenu: [10, 25, 50],
        pageLength: 10,
        language: {
            lengthMenu: 'Hiển thị _MENU_ bản ghi',
            search: 'Tìm kiếm:',
            info: 'Hiển thị _START_ đến _END_ trong _TOTAL_ bản ghi',
            paginate: { first: 'Đầu', last: 'Cuối', next: 'Sau', previous: 'Trước' },
            emptyTable: 'Không có dữ liệu'
        },
        columns: [
            {
                title: 'STT', data: null, width: '45px', className: 'text-center',
                render: (d, t, r, meta) => meta.row + 1
            },
            {
                title: 'Icon', data: 'icon', className: 'text-center', width: '80px',
                render: val => `<i class="${val}" style="font-size:1.6rem;color:#0d6efd;"></i>`
            },
            { title: 'Class Icon', data: 'icon', render: val => `<code>${val}</code>` },
            { title: 'Số lượng', data: 'so_luong', className: 'text-center fw-bold' },
            { title: 'Tiêu đề', data: 'title' },
            { title: 'Thứ tự', data: 'thutu', className: 'text-center', width: '70px' },
            {
                title: 'Thao tác', data: null, orderable: false, className: 'text-center', width: '110px',
                render: (d, t, row) => `
                    <div class="d-flex justify-content-center">
                        <button class="btn btn-sm btn-outline-primary btn-edit me-1" data-id="${row.id}" title="Sửa">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger btn-delete" data-id="${row.id}" title="Xóa">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>`
            }
        ],
        order: [[5, 'asc']]
    });
}

// ─── Preview icon ──────────────────────────────────────────────────────
function previewIcon(val) {
    const el = document.getElementById('iconPreviewEl');
    if (!el) return;
    el.className = val.trim() || 'fas fa-star';
}

// ─── Clear form ────────────────────────────────────────────────────────
function clearStatForm() {
    $('#statForm')[0].reset();
    $('#statId').val('');
    $('#statModalTitle').text('Thêm mới');
    $('sup[id^="err_"]').text('');
    previewIcon('fas fa-star');
}

// ─── Submit form ───────────────────────────────────────────────────────
$('#statForm').on('submit', function (e) {
    e.preventDefault();
    $('sup[id^="err_"]').text('');

    const fd = new FormData(this);

    $.ajax({
        url: '/admin/highlight-stats/store',
        type: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        success: function (res) {
            if (res.status === false && res.errors) {
                $.each(res.errors, function (field, msgs) {
                    $(`#err_${field}`).text('* ' + msgs[0]);
                });
                return;
            }
            toastr.success(res.message);
            bootstrap.Modal.getInstance(document.getElementById('statModal'))?.hide();
            clearStatForm();
            loadStats();
        },
        error: function () { toastr.error('Có lỗi xảy ra'); }
    });
});

// ─── Nút thêm mới ─────────────────────────────────────────────────────
$('#addStatBtn').on('click', function () {
    clearStatForm();
    bootstrap.Modal.getOrCreateInstance(document.getElementById('statModal')).show();
});

// ─── Sửa ──────────────────────────────────────────────────────────────
$(document).on('click', '.btn-edit', function () {
    const id = $(this).data('id');
    $.getJSON(`/admin/highlight-stats/${id}`, function (res) {
        if (res.status !== 'success') { toastr.error('Không tải được dữ liệu'); return; }
        const d = res.data;
        clearStatForm();
        $('#statId').val(d.id);
        $('#statModalTitle').text('Chỉnh sửa');
        $('#stat_icon').val(d.icon);
        $('#stat_so_luong').val(d.so_luong);
        $('#stat_title').val(d.title);
        $('#stat_thutu').val(d.thutu);
        previewIcon(d.icon);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('statModal')).show();
    });
});

// ─── Xóa ──────────────────────────────────────────────────────────────
$(document).on('click', '.btn-delete', function () {
    const id = $(this).data('id');
    Swal.fire({
        title: 'Xác nhận xóa?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonText: 'Hủy',
        confirmButtonText: 'Xóa',
    }).then(result => {
        if (!result.isConfirmed) return;
        $.ajax({
            url: `/admin/highlight-stats/${id}`,
            type: 'DELETE',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                if (res.success) { toastr.success(res.message); loadStats(); }
                else toastr.error(res.message);
            }
        });
    });
});

// ─── Reset modal khi đóng ─────────────────────────────────────────────
$('#statModal').on('hidden.bs.modal', function () {
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open').css('overflow', '').css('padding-right', '');
});