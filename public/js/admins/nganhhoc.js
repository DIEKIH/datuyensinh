// =====================================================================
// nganhhoc.js  –  Quản lý Ngành học
// =====================================================================

let nganhTable;
let allToHops = [];   // cache danh sách tổ hợp từ DB
let isNganhSaving = false;
const PROFILE_VECTOR_KEYS = [
    'logic',
    'data',
    'technical',
    'creativity',
    'communication',
    'management',
    'language',
    'legal',
    'experiment',
    'fieldwork'
];

function resetProfileVector() {

    PROFILE_VECTOR_KEYS.forEach(function(key){

        $('#pv_'+key).val(0);

        $('#pv_text_'+key).text(0);

    });

}

function fillProfileVector(vector){

    vector = vector || {};

    PROFILE_VECTOR_KEYS.forEach(function(key){

        let value = Number(vector[key] || 0);

        $('#pv_'+key).val(value);

        $('#pv_text_'+key).text(value);

    });

}

$(document).on(
    'input change',
    '.pv-range',
    function(){

        let key=$(this).data('key');

        $('#pv_text_'+key)
            .text($(this).val());

    }
);
// ─── CSRF ─────────────────────────────────────────────────────────────
$(document).ready(function () {
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });
});

// ─── Summernote ────────────────────────────────────────────────────────
function initSummernote() {
    if ($('#summernoteNganh').next('.note-editor').length) return;
    $('#summernoteNganh').summernote({
        height: 500,
        lang: 'vi-VN',
        dialogsInBody: true,
        dialogsFade: true,
        shortcuts: false,
        disableDragAndDrop: true,
        popover: { image: [], link: [], air: [] },
        toolbar: [
            ['style',    ['style']],
            ['font',     ['bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', 'clear']],
            ['fontname', ['fontname']],
            ['fontsize', ['fontsize']],
            ['color',    ['color']],
            ['para',     ['ul', 'ol', 'paragraph']],
            ['height',   ['height']],
            ['table',    ['table']],
            ['insert',   ['link', 'picture', 'video', 'hr']],
            ['view',     ['fullscreen', 'codeview', 'help']],
            ['misc',     ['undo', 'redo']]
        ],
        fontNames: ['Arial', 'Tahoma', 'Times New Roman', 'Courier New', 'Helvetica', 'Verdana'],
        fontSizes: ['8', '9', '10', '11', '12', '14', '16', '18', '24', '36', '48'],
        styleTags: ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'],
        callbacks: {
            onInit: function () {
                var $editable = $(this).next('.note-editor').find('.note-editable');
                if ($editable.length && $editable.text().trim().length === 0) {
                    $editable.html('<p><br></p>');
                }
            },
            onImageUpload: function (files) {
                let editor = $(this);
                editor.summernote('saveRange');
                for (let i = 0; i < files.length; i++) {
                    uploadImageSummernote(files[i], editor);
                }
            }
        }
    });
}

/* Fix lỗi Bootstrap focus khi Summernote mở */
$(document).on('focusin', function (e) {
    if ($(e.target).closest('.note-editor, .note-modal').length) {
        e.stopImmediatePropagation();
    }
});

function uploadImageSummernote(file, editor) {
    const fd = new FormData();
    fd.append('file', file);
    fd.append('_token', $('meta[name="csrf-token"]').attr('content'));
    $.ajax({
        url: '/upload-image',
        type: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        success(res) {
            $(editor).summernote('restoreRange');
            $(editor).summernote('insertImage', res.url);
        },
        error(xhr) {
            toastr.error('Upload ảnh thất bại: ' + (xhr.responseJSON?.message ?? 'Lỗi không xác định'));
        }
    });
}

// ─── Preview ảnh ───────────────────────────────────────────────────────
function previewNganhImage(event) {
    const file = event.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => $('#nganhImagePreview').attr('src', e.target.result).show();
    reader.readAsDataURL(file);
}

// ─── Select2 cho tổ hợp ───────────────────────────────────────────────
// FIX: bỏ dropdownParent để tránh bị clip bởi modal overflow,
//      dùng $.fn.select2.defaults + appendToBody thay thế
function initToHopSelect2() {
    if ($('#to_hop_ids').hasClass('select2-hidden-accessible')) return;

    $('#to_hop_ids').select2({
        placeholder: 'Chọn tổ hợp xét tuyển',
        width: '100%',
        dropdownParent: $('body'),   // ← render ra body, tránh bị clip trong modal
        language: {
            noResults:  () => 'Không tìm thấy tổ hợp',
            searching:  () => 'Đang tìm...',
        }
    });

    // Đảm bảo dropdown Select2 luôn nằm trên modal (z-index modal Bootstrap = 1050)
    $(document).on('select2:open', '#to_hop_ids', function () {
        $('.select2-dropdown').css('z-index', 99999);
    });
}

// ─── Select2 cho các short-dropdown ──────────────────────────────────
function initShortDropdowns() {
    // FIX: cũng render ra body để không bị clip
    $('.short-dropdown').each(function () {
        if ($(this).hasClass('select2-hidden-accessible')) return;
        $(this).select2({
            width: '100%',
            dropdownParent: $('body'),   // ← render ra body
            minimumResultsForSearch: 0
        });
    });

    // Đảm bảo dropdown nằm trên modal
    $(document).on('select2:open', '.short-dropdown', function () {
        $('.select2-dropdown').css('z-index', 99999);
    });
}

// ─── Tổ hợp: load & populate ──────────────────────────────────────────
function loadToHops(selectedIds = []) {
    if (allToHops.length > 0) {
        populateToHopSelect(allToHops, selectedIds);
        return;
    }
    $.getJSON('/admin/nganhhoc/tohop-list', function (res) {
        allToHops = res.data ?? [];
        populateToHopSelect(allToHops, selectedIds);
    });
}

function populateToHopSelect(list, selectedIds = []) {
    const $sel = $('#to_hop_ids');

    // FIX: destroy trước khi thay đổi options để tránh Select2 giữ state cũ
    if ($sel.hasClass('select2-hidden-accessible')) {
        $sel.val(null).trigger('change');   // reset giá trị đang chọn
        $sel.empty();                        // xóa toàn bộ option
    }

    const normalized = selectedIds.map(String);

    list.forEach(t => {
        const isSelected = normalized.includes(String(t.id));
        $sel.append(new Option(
            `${t.ma_to_hop} – ${t.ten_to_hop}`,
            t.id,
            false,
            isSelected   // ← chỉ pre-select đúng những ID được truyền vào
        ));
    });

    $sel.trigger('change');
}

// ─── Load danh sách Khoa ───────────────────────────────────────────────
function loadKhoa() {
    $.getJSON('/admin/nganhhoc/khoa', function (res) {
        const opts = (res.data ?? [])
            .map(k => `<option value="${k.id}">${k.ten_khoa}</option>`)
            .join('');
        $('#khoa_id, #filterKhoa').append(opts);
    });
}

// ─── Hiển thị / xóa lỗi ───────────────────────────────────────────────
function clearErrors() {
    $('sup[id^="err_"]').text('');
    $('.is-invalid-custom').removeClass('is-invalid-custom');
}

function showErrors(errors) {
    clearErrors();
    $.each(errors, (field, msgs) => {
        $(`#err_${field}`).text('* ' + msgs[0]);
        $(`#${field}`).addClass('is-invalid-custom');
    });
}

// ─── Clear form ────────────────────────────────────────────────────────
function clearNganhForm() {
    $('#nganhForm')[0].reset();

    $('#nganhId').val('');
    $('#nganhModalTitle').text('Tạo mới ngành học');
    $('#nganhImagePreview').hide().attr('src', '#');

    // Reset Select2 tổ hợp
    if ($('#to_hop_ids').hasClass('select2-hidden-accessible')) {
        $('#to_hop_ids').val(null).trigger('change');
    }

    // Reset các select2 ngắn
    $('.short-dropdown').each(function () {
        if ($(this).hasClass('select2-hidden-accessible')) {
            $(this).val('').trigger('change');
        } else {
            $(this).val('');
        }
    });

    // Quan trọng: clear Summernote
    if ($('#summernoteNganh').next('.note-editor').length) {
        $('#summernoteNganh').summernote('code', '');
    } else {
        $('#summernoteNganh').html('');
    }

    $('#tomtat_nganh').val('');
    $('#nganh_image').val('');

    resetProfileVector();
    clearErrors();
}

// ─── Format tiền ───────────────────────────────────────────────────────
function formatCurrency(val) {
    if (!val) return '—';
    return Number(val).toLocaleString('vi-VN') + ' đ';
}

// ─── DataTable ─────────────────────────────────────────────────────────
function initTable(data) {
    if (nganhTable) {
        nganhTable.clear().rows.add(data).draw();
        return;
    }
    nganhTable = $('#nganhTable').DataTable({
        data: data,
        scrollY: 'calc(100vh - 380px)',
        scrollCollapse: true,
        paging: true,
        lengthMenu: [10, 25, 50, 100],
        pageLength: 10,
        language: {
            lengthMenu: "Hiển thị &nbsp _MENU_ &nbsp bản ghi",
            search: "Tìm kiếm:",
            info: "Hiển thị _START_ đến _END_ trong tổng _TOTAL_ bản ghi",
            paginate: { first: "Đầu", last: "Cuối", next: "Sau", previous: "Trước" },
            emptyTable: "Không có dữ liệu"
        },
        columns: [
            {
                title: 'STT', data: null,
                render: (d, t, r, meta) => meta.row + 1,
                width: '45px', className: 'text-center'
            },
            { title: 'Tên ngành',  data: 'ten_nganh' },
            { title: 'Mã ngành',   data: 'ma_nganh',   defaultContent: '—', className: 'text-center' },
            { title: 'Khoa',       data: 'ten_khoa',   defaultContent: '—' },
            { title: 'Bậc ĐT',     data: 'bac_dao_tao',defaultContent: '—', className: 'text-center' },
            { title: 'Loại hình',  data: 'loai_hinh',  defaultContent: '—' },
            { title: 'Thời gian',  data: 'thoi_gian',  defaultContent: '—', className: 'text-center' },
            { title: 'Tín chỉ',    data: 'so_tin_chi', defaultContent: '—', className: 'text-center' },
            { title: 'Chỉ tiêu',   data: 'chi_tieu',   defaultContent: '—', className: 'text-center' },
            { title: 'Học phí',    data: 'hoc_phi',    className: 'text-end', render: val => formatCurrency(val) },
            {
                title: 'Thao tác', data: null, orderable: false, className: 'text-center', width: '130px',
                render(d, t, row) {
                    return `
                        <div class="d-flex justify-content-center">
                            <button class="btn btn-sm btn-outline-info btn-view me-1"    data-id="${row.id}" title="Xem"><i class="fas fa-eye"></i></button>
                            <button class="btn btn-sm btn-outline-primary btn-edit me-1" data-id="${row.id}" title="Sửa"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-outline-danger btn-delete"     data-id="${row.id}" title="Xóa"><i class="fas fa-trash-alt"></i></button>
                        </div>`;
                }
            }
        ],
        order: [[0, 'asc']]
    });
}
function getCurrentFilter() {
    return {
        khoa_id: $('#filterKhoa').val() || '',
        bac_dao_tao: $('#filterBac').val() || ''
    };
}

// ─── Load dữ liệu bảng ────────────────────────────────────────────────
function loadNganh(params = {}) {
    let url = '/admin/nganhhoc/list';
    const qs = new URLSearchParams();
    if (params.khoa_id)     qs.append('khoa_id',     params.khoa_id);
    if (params.bac_dao_tao) qs.append('bac_dao_tao', params.bac_dao_tao);
    if (qs.toString()) url += '?' + qs.toString();

    $.getJSON(url, res => initTable(res.data ?? []))
        .fail(() => toastr.error('Không tải được danh sách ngành'));
}

// ─── Submit form ───────────────────────────────────────────────────────
// ─── Submit form ───────────────────────────────────────────────────────
$('#nganhForm').on('submit', function (e) {
    e.preventDefault();

    if (isNganhSaving) return;

    isNganhSaving = true;
    clearErrors();

    const $saveBtn = $('#nganhForm button[type="submit"]');
    const oldSaveHtml = $saveBtn.html();

    $saveBtn.prop('disabled', true).html(`
        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
        Đang lưu...
    `);

    $('#nganhLoadingText').text('Đang lưu ngành học...');
    $('#nganhLoadingOverlay').addClass('show');

    const noidung = $('#summernoteNganh').summernote('code');
    const fd = new FormData(this);
    fd.set('noidung', noidung);

    const token = $('meta[name="csrf-token"]').attr('content');
    if (token) fd.append('_token', token);

    $.ajax({
        url: '/admin/nganhhoc/store',
        type: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        success(res) {
            if (res.status === false && res.errors) {
                showErrors(res.errors);
                toastr.error('Có lỗi trong dữ liệu, vui lòng kiểm tra form.');
                return;
            }

            toastr.success(res.message, 'Thành công');

            let modalEl = document.getElementById('nganhModal');
            let modalInstance = bootstrap.Modal.getInstance(modalEl);

            if (modalInstance) {
                modalInstance.hide();
            } else {
                bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            }

            clearNganhForm();

            loadNganh(getCurrentFilter());
        },
        error() {
            toastr.error('Có lỗi xảy ra khi lưu ngành học');
        },
        complete() {
            $('#nganhLoadingOverlay').removeClass('show');

            $saveBtn.prop('disabled', false).html(oldSaveHtml);

            isNganhSaving = false;
        }
    });
});

// ─── Mở modal sửa ─────────────────────────────────────────────────────
$(document).on('click', '.btn-edit', function () {
    const id = $(this).data('id');
    $.getJSON(`/admin/nganhhoc/${id}`, function (res) {
        if (res.status !== 'success') {
            toastr.error('Không tải được thông tin ngành');
            return;
        }
        const d = res.data;
        
        clearNganhForm();
fillProfileVector(d.profile_vector || {});
        $('#nganhId').val(d.id);
        $('#nganhModalTitle').text('Chỉnh sửa ngành học');
        $('#ten_nganh').val(d.ten_nganh);
        $('#ma_nganh').val(d.ma_nganh);
        $('#khoa_id').val(d.khoa_id).trigger('change');
        $('#loai_hinh').val(d.loai_hinh).trigger('change');
        $('#bac_dao_tao').val(d.bac_dao_tao).trigger('change');
        $('#thoi_gian').val(d.thoi_gian);
        $('#so_tin_chi').val(d.so_tin_chi);
        $('#chi_tieu').val(d.chi_tieu);
        $('#hoc_phi').val(d.hoc_phi);
        $('#hinh_thuc_xet').val(d.hinh_thuc_xet);
        $('#co_so').val(d.co_so);
        $('#tomtat_nganh').val(d.tomtat);
        $('#summernoteNganh').summernote('code', d.noidung ?? '');

        if (d.image_url) $('#nganhImagePreview').attr('src', d.image_url).show();

        // FIX: truyền đúng mảng ID của ngành này, không phải toàn bộ
        const checkedIds = (d.to_hop_ids_arr ?? []).map(String);
        loadToHops(checkedIds);

        $('#nganhModal').modal('show');
    });
});

// ─── Xem chi tiết ─────────────────────────────────────────────────────
$(document).on('click', '.btn-view', function () {
    const id = $(this).data('id');
    $.getJSON(`/admin/nganhhoc/${id}`, function (res) {
        if (res.status !== 'success') return;
        const d = res.data;

        const toHopHtml = d.to_hops && d.to_hops.length
            ? d.to_hops.map(t => `<span class="badge-tohop"><strong>${t.ma_to_hop}</strong> ${t.ten_to_hop}</span>`).join('')
            : '—';

        const imgHtml = d.image_url
            ? `<img src="${d.image_url}" class="img-fluid rounded mb-3" style="max-height:200px;">`
            : '';

        $('#detailTitle').text(d.ten_nganh);
        $('#detailBody').html(`
            ${imgHtml}
            <table class="table table-sm table-bordered">
                <tbody>
                    <tr><th>Mã ngành</th><td>${d.ma_nganh ?? '—'}</td></tr>
                    <tr><th>Khoa</th><td>${d.ten_khoa ?? '—'}</td></tr>
                    <tr><th>Loại hình</th><td>${d.loai_hinh ?? '—'}</td></tr>
                    <tr><th>Bậc đào tạo</th><td>${d.bac_dao_tao ?? '—'}</td></tr>
                    <tr><th>Thời gian</th><td>${d.thoi_gian ?? '—'}</td></tr>
                    <tr><th>Số tín chỉ</th><td>${d.so_tin_chi ?? '—'}</td></tr>
                    <tr><th>Chỉ tiêu</th><td>${d.chi_tieu ?? '—'}</td></tr>
                    <tr><th>Học phí</th><td>${d.hoc_phi ? Number(d.hoc_phi).toLocaleString('vi-VN') + ' đ' : '—'}</td></tr>
                    <tr><th>Hình thức xét tuyển</th><td>${d.hinh_thuc_xet ?? '—'}</td></tr>
                    <tr><th>Cơ sở đào tạo</th><td>${d.co_so ?? '—'}</td></tr>
                    <tr><th>Tổ hợp xét tuyển</th><td>${toHopHtml}</td></tr>
                    <tr><th>Tóm tắt</th><td>${d.tomtat ?? '—'}</td></tr>
                </tbody>
            </table>
            ${d.noidung ? `<div class="mt-2 p-2 border rounded bg-light">${d.noidung}</div>` : ''}
        `);
        $('#nganhDetailModal').modal('show');
    });
});

// ─── Xóa ──────────────────────────────────────────────────────────────
$(document).on('click', '.btn-delete', function () {
    const id = $(this).data('id');
    Swal.fire({
        title: 'Xác nhận xóa?',
        text: 'Hành động này không thể hoàn tác!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonText: 'Hủy',
        confirmButtonText: 'Xóa',
    }).then(result => {
        if (!result.isConfirmed) return;
        $.ajax({
            url: `/admin/nganhhoc/${id}`,
            type: 'DELETE',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success(res) {
                if (res.success) { toastr.success(res.message); loadNganh(getCurrentFilter()); }
                else              toastr.error(res.message);
            }
        });
    });
});

// ─── Nút thêm mới ─────────────────────────────────────────────────────
$('#addNganhBtn').on('click', function () {
    clearNganhForm();
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open').css('overflow', '').css('padding-right', '');
    bootstrap.Modal.getOrCreateInstance(document.getElementById('nganhModal')).show();
});

// ─── Bộ lọc ───────────────────────────────────────────────────────────
$('#btnFilter').on('click', () =>
    loadNganh({ khoa_id: $('#filterKhoa').val(), bac_dao_tao: $('#filterBac').val() })
);
$('#btnResetFilter').on('click', () => {
    $('#filterKhoa, #filterBac').val('');
    loadNganh();
});

// ─── Reset modal khi đóng ─────────────────────────────────────────────
// $('#nganhModal').on('hidden.bs.modal', clearNganhForm);
$('#nganhModal').on('hidden.bs.modal', function () {
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open').css('overflow', '').css('padding-right', '');
});
// ─── Init ─────────────────────────────────────────────────────────────
$(document).ready(function () {
    loadKhoa();
    loadNganh();
    loadToHops();   // pre-load tổ hợp (không truyền selectedIds → không chọn gì)

    $('#nganhModal').on('shown.bs.modal', function () {
        initSummernote();
        initToHopSelect2();
        initShortDropdowns();
        $('#summernoteNganh').summernote('focus');
    });
});

function resetProfileVector() {
    PROFILE_VECTOR_KEYS.forEach(function(key) {
        $('#pv_' + key).val(0);
        $('#pv_text_' + key).text(0);
    });
}

function fillProfileVector(vector) {
    vector = vector || {};

    PROFILE_VECTOR_KEYS.forEach(function(key) {
        const value = Number(vector[key] || 0);
        $('#pv_' + key).val(value);
        $('#pv_text_' + key).text(value);
    });
}

$(document).on('input change', '.pv-range', function() {
    const key = $(this).data('key');
    $('#pv_text_' + key).text($(this).val());
});