$(document).ready(function () {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
});

let isPostSaving = false;

//#region Load tất cả bài viết

$.ajax({
    type: "GET",
    url: "/admin/tatcabaiviet",
    timeout: 10000,
    success: function (res) {
        const data = res.data || [];

        if (data.length) {
            renderBaivietTable(data);
        } else {
            toastr.warning("Không có bài viết nào.");
            if ($.fn.DataTable.isDataTable('#baivietTable')) {
                $('#baivietTable').DataTable().clear().draw();
            }
        }
    },
    error: function (xhr) {
        console.error('Lỗi /admin/tatcabaiviet:', xhr.status, xhr.responseText);
        toastr.error('Không thể tải dữ liệu bài viết.');
    }
});
function renderBaivietTable(baivietArray) {
    $('#baivietTable').DataTable({
        destroy: true,
        data: baivietArray,
        columns: [
            { data: 'id', title: 'ID' },
            { data: 'tieude', title: 'Tiêu đề' },
            // Thêm cột này vào sau
            {
                data: 'tendanhmuc',
                title: 'Phân loại',
            },
            { data: 'tacgia_ten', title: 'Tác giả' },
            {
                data: 'ngaydang',
                title: 'Ngày đăng',
                render: function (data) {
                    if (!data) return '';

                    const date = new Date(data);
                    const day = String(date.getDate()).padStart(2, '0');
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const year = date.getFullYear();

                    return `${day}/${month}/${year}`;
                }
            },
            {
                data: 'status',
                title: 'Trạng thái',
                render: function (data, type, row) {
                    const status = data || 'show';
                    const isEvent = String(row.danhmuc) === '3' || row.tendanhmuc === 'Sự kiện';

                    if (isEvent) {
                        if (status === 'ended') {
                            return '<span>Đã kết thúc</span>';
                        }

                        if (status === 'upcoming') {
                            return '<span">Sắp diễn ra</span>';
                        }

                        if (status === 'show') {
                            return '<span>Đang diễn ra</span>';
                        }

                        return '<span>Đang diễn ra</span>';
                    }

                    return '<span>Đang hiển thị</span>';
                }
            },

            // 👉 Cột thao tác
            {
                data: null,
                title: 'Thao tác',
                orderable: false,
                render: function (data, type, row) {
                    return `
                        <div class="d-flex justify-content-center">
                            <button class="btn btn-sm btn-outline-primary edit-btn me-1" data-id="${row.id}" title="Sửa">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="delete-btn btn btn-sm btn-outline-danger delete-btn" data-id="${row.id}" title="Xóa">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    `;
                }
            }


        ],
        scrollY: '65vh',
        scrollCollapse: true,
        paging: true,
        lengthMenu: [10, 25, 50, 100],
        pageLength: 10,
        fixedHeader: true,
        language: {
            lengthMenu: "Hiển thị &nbsp _MENU_ &nbsp bản ghi",
            search: "Tìm kiếm:",
            info: "Hiển thị _START_ đến _END_ trong tổng _TOTAL_ bản ghi",
            paginate: {
                first: "Đầu",
                last: "Cuối",
                next: "Sau",
                previous: "Trước"
            },
            emptyTable: "Không có dữ liệu"
        }
    });
}
//#endregion



//#region Load bài viết cho modal con
// ✅ Không load trước nữa — sẽ load theo danh mục khi mở modal


// Hàm render DataTable cho modal
function renderLienQuanTable(baivietArray) {
    $('#tableLienQuan').DataTable({
        destroy: true,
        data: baivietArray,
        columns: [

            { data: 'id', title: 'ID' },
            { data: 'tieude', title: 'Tiêu đề' },
            { data: 'tacgia_ten', title: 'Tác giả' },
            {
                data: null,
                orderable: false,
                className: "text-center",
                render: function (data, type, row) {
                    return `<input type="checkbox" class="chk-lienquan" value="${row.id}">`;
                }
            }
        ],
        scrollY: '50vh',
        scrollCollapse: true,
        paging: true,
        lengthMenu: [5, 10, 25, 50],
        pageLength: 10,
        language: {
            lengthMenu: "Hiển thị _MENU_ bản ghi",
            search: "Tìm kiếm:",
            info: "Hiển thị _START_ đến _END_ trong tổng _TOTAL_ bản ghi",
            paginate: {
                first: "Đầu",
                last: "Cuối",
                next: "Sau",
                previous: "Trước"
            },
            emptyTable: "Không có dữ liệu"
        }
    });
}

$('#modalLienQuan').on('shown.bs.modal', function () {
    if ($.fn.DataTable.isDataTable('#tableLienQuan')) {
        $('#tableLienQuan').DataTable().columns.adjust();
    }
});

//#endregion




//#region Load modal cha
$(function () {
    // Summernote
    $('#summernote').summernote({
        height: 500,
        lang: 'vi-VN',
        dialogsInBody: true,
        dialogsFade: true,
        shortcuts: false,
        disableDragAndDrop: true,

        popover: {
            image: [],
            link: [],
            air: []
        },
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', 'clear']],
            ['fontname', ['fontname']],
            ['fontsize', ['fontsize']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['height', ['height']],
            ['table', ['table']],
            ['insert', ['link', 'picture', 'video', 'hr', 'uploadVideo']],
            ['view', ['fullscreen', 'codeview', 'help']],
            ['misc', ['undo', 'redo']]
        ],
        fontNames: ['Arial', 'Tahoma', 'Times New Roman', 'Courier New', 'Helvetica', 'Verdana'],
        fontSizes: ['8', '9', '10', '11', '12', '14', '16', '18', '24', '36', '48'],
        styleTags: ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'],
        disableDragAndDrop: true,
        callbacks: {
            onInit: function () {
                var $editor = $(this).next('.note-editor');
                var $editable = $editor.find('.note-editable');
                if ($editable.length && $editable.text().trim().length === 0) {
                    $editable.html('<p><br></p>');
                }
            },
            onImageUpload: function (files) {

                let editor = $(this);

                editor.summernote('saveRange');

                for (let i = 0; i < files.length; i++) {
                    uploadImage(files[i], editor);
                }

            }

        },
        buttons: {
            uploadVideo: function (context) {
                var ui = $.summernote.ui;
                var button = ui.button({
                    contents: '<i class="fas fa-video"></i> Upload Video',
                    tooltip: 'Upload video từ máy tính',
                    click: function () {
                        var input = document.createElement('input');
                        input.type = 'file';
                        input.accept = 'video/mp4,video/webm,video/ogg';
                        input.onchange = function () {
                            var file = input.files[0];
                            if (!file) return;

                            // Kiểm tra dung lượng 100MB
                            if (file.size > 100 * 1024 * 1024) {
                                toastr.error('Video tối đa 100MB.');
                                return;
                            }

                            var formData = new FormData();
                            formData.append('file', file);
                            formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
                            toastr.info('Đang upload video...');

                            $.ajax({
                                url: '/upload-video',
                                type: 'POST',
                                data: formData,
                                contentType: false,
                                processData: false,
                                success: function (res) {
                                    if (res.url) {
                                        // Tạo div wrapper căn giữa
                                        var wrapperDiv = document.createElement('div');
                                        wrapperDiv.style.cssText = 'text-align: center; margin: 10px 0;';

                                        // Tạo video element
                                        var video = document.createElement('video');
                                        video.controls = true;
                                        video.src = res.url;
                                        video.style.cssText = 'max-width: 50%; width: auto; margin: 0 auto; display: inline-block;';

                                        wrapperDiv.appendChild(video);

                                        // Tạo đoạn văn rỗng phía dưới để gõ tiếp
                                        var pAfter = document.createElement('p');
                                        pAfter.appendChild(document.createElement('br'));

                                        // Lấy editable area và chèn vào
                                        var editable = context.layoutInfo.editable[0];
                                        editable.appendChild(wrapperDiv);
                                        editable.appendChild(pAfter);

                                        // Đặt con trỏ vào pAfter để tiếp tục gõ
                                        editable.focus();
                                        var range = document.createRange();
                                        var sel = window.getSelection();
                                        range.setStart(pAfter, 0);
                                        range.collapse(true);
                                        sel.removeAllRanges();
                                        sel.addRange(range);

                                        toastr.success('Upload video thành công!');
                                    }
                                },
                                error: function () {
                                    toastr.error('Upload video thất bại.');
                                }
                            });
                        };
                        input.click();
                    }
                });
                return button.render();
            }
        },
    });

    // Datepicker ngày đăng
    $('#datetimepicker').datepicker({
        format: 'dd/mm/yyyy',
        autoclose: true,
        todayHighlight: true,
        language: 'vi',
        orientation: 'bottom auto',
        todayBtn: 'linked',
        clearBtn: true,
        container: '#baivietModal',
        templates: {
            leftArrow: '<i class="fas fa-chevron-left"></i>',
            rightArrow: '<i class="fas fa-chevron-right"></i>'
        }
    });

    // datetimepicker-sukien được khởi tạo lazy khi block hiện lần đầu (xem initSuKienPicker)
});


function uploadImage(file, editor) {

    let data = new FormData();
    data.append("file", file);
    data.append("_token", $('meta[name="csrf-token"]').attr('content'));

    $.ajax({
        url: "/upload-image",
        type: "POST",
        data: data,
        contentType: false,
        processData: false,

        success: function (res) {

            editor.summernote('restoreRange');
            editor.summernote('focus');

            let img = document.createElement('img');
            img.src = res.url;

            editor.summernote('insertNode', img);

        }
    });
}


/* Fix lỗi Bootstrap focus */

$(document).on('focusin', function (e) {

    if ($(e.target).closest(".note-editor, .note-modal").length) {

        e.stopImmediatePropagation();

    }

});
$(document).on('shown.bs.modal', '#baivietModal', function () {
    $('#summernote').summernote('focus');
});

// Preview ảnh
function previewImage(event) {
    const imagePreview = document.getElementById('imagePreview');
    const file = event.target.files[0];
    if (file) {
        imagePreview.src = URL.createObjectURL(file);
        imagePreview.style.display = 'block';
    } else {
        imagePreview.style.display = 'none';
        imagePreview.src = '#';
    }
}

// Preview PDF
function previewPDF(event) {
    const pdfPreview = document.getElementById('pdf-preview');
    const file = event.target.files[0];

    if (!file) {
        pdfPreview.innerHTML = "Chưa chọn PDF";
        return;
    }

    const fileReader = new FileReader();
    fileReader.onload = function () {
        const typedarray = new Uint8Array(this.result);

        pdfjsLib.getDocument(typedarray).promise.then(function (pdf) {
            pdf.getPage(1).then(function (page) {
                const viewport = page.getViewport({ scale: 1 });
                const scale = 150 / viewport.height; // scale sao cho chiều cao = 150px
                const scaledViewport = page.getViewport({ scale });

                const canvas = document.createElement("canvas");
                const context = canvas.getContext("2d");
                canvas.height = scaledViewport.height;
                canvas.width = scaledViewport.width;

                page.render({
                    canvasContext: context,
                    viewport: scaledViewport
                }).promise.then(function () {
                    pdfPreview.innerHTML = "";
                    pdfPreview.appendChild(canvas);
                });
            });
        });
    };
    fileReader.readAsArrayBuffer(file);
}


// Clear form
function clearForm(showToast = true) {
    const form = $('#postForm');

    // 1️⃣ Reset toàn bộ input, select, textarea cơ bản
    form[0].reset();

    // 2️⃣ Clear các lỗi <sup> hoặc thông báo
    form.find('sup').text('');

    // 3️⃣ Clear Select2 (tác giả)
    form.find('.select22').val(null).trigger('change');

    // 4️⃣ Clear Summernote
    $('#summernote').summernote('code', '');

    // 5️⃣ Clear vùng contenteditable (tiêu đề)
    $('#titleInput').text('');

    // 6️⃣ Clear ảnh preview
    $('#imagePreview').hide().attr('src', '');

    // 7️⃣ Clear PDF preview
    $('#pdf-preview').text('Chưa chọn PDF');

    // 8️⃣ Clear hidden input (id, liên quan)
    $('#postId').val('');
    $('input[name="lienquan"]').val('');

    currentLienQuan = [];

    // 9️⃣ Reset datetimepicker (nếu có plugin datepicker)
    $('#datetimepicker').val('');

    // 🔟 Reset select menu cấp 1-2-3, loại tin, danh mục
    $('#menu1, #menu2, #menu3, #danhmuc').val('').trigger('change');
    $('#auto_publish').val('1').trigger('change');

    // ✅ Clear block sự kiện
    $('#thoigian_bd, #thoigian_kt, #diadiem').val('');
    $('#sukienStatus').val('').css('color', '');
    $('input[name="thoigian_bd_db"], input[name="thoigian_kt_db"], input[name="sukien_status_db"]').val('');
    $('#blockSuKien').hide();
    if (showToast && typeof toastr !== 'undefined') {
        toastr.info('Form đã được làm mới!');
    }
}

$('#baivietModal').on('hidden.bs.modal', function () {
    if (!isChildModalOpen) {
        clearForm(false);
    }
    // Dọn sạch backdrop mỗi lần modal đóng
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open').css('overflow', '').css('padding-right', '');
});

$('#baivietModal').on('hide.bs.modal', function () {
    if (document.activeElement && this.contains(document.activeElement)) {
        document.activeElement.blur();
    }
});

// Close
function closePopup() {
    window.location.href = '#';
}

$('.short-dropdown').select2({
    width: '100%',
    dropdownParent: $('#baivietModal .modal-body'),
    minimumResultsForSearch: 0 // ẩn thanh tìm kiếm
});

$('#baivietModal .modal-body').on('scroll', function () {
    $('.short-dropdown').each(function () {
        if ($(this).data('short-dropdown')) {
            $(this).data('short-dropdown').dropdown._positionDropdown();
        }
    });
});

// Submit form
// document.getElementById('postForm').addEventListener('submit', function (e) {
//     e.preventDefault();
//     alert("Bài viết đã được lưu!");
// });

//#endregion




// Lưu bài viết
$(document).ready(function () {
    $('#postForm').on('submit', function (e) {
        e.preventDefault();

        if (isPostSaving) return;

        // Đảm bảo lấy đúng giá trị ngày từ datepicker
        let ngaydang = $('#datetimepicker').val();

        if (isSuKien()) {
            let bd = $('#thoigian_bd').val();
            let kt = $('#thoigian_kt').val();
            if (bd && kt && new Date(kt) <= new Date(bd)) {
                toastr.error('Thời gian kết thúc phải sau thời gian bắt đầu.');
                return;
            }
        }

        isPostSaving = true;

        const $saveBtn = $('#postForm button[type="submit"]');
        const oldSaveHtml = $saveBtn.html();

        $saveBtn.prop('disabled', true).html(`
        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
        Đang lưu...
    `);

        $('#loadingText').text('Đang lưu bài viết...');
        $('#loadingOverlay').addClass('show');

        $('sup[id]').text("");

        let form = new FormData(this);
        form.set('tieude', $('#titleInput').text().trim());
        form.set('noidung', $('#summernote').summernote('code'));
        form.set('ngaydang', ngaydang);

        $.ajax({
            url: '/admin/store',
            type: 'POST',
            data: form,
            contentType: false,
            processData: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (res) {
                console.log('Response:', res);

                if (res.status === false && res.errors) {
                    // ====== Hiển thị từng lỗi cụ thể ======
                    if (res.errors['ngaydang']) $('#ngaydang').text('* ' + res.errors['ngaydang'][0]);
                    if (res.errors['tieude']) $('#tieude').text('* ' + res.errors['tieude'][0]);
                    if (res.errors['tomtat']) $('#tomtat').text('* ' + res.errors['tomtat'][0]);
                    if (res.errors['tacgia']) $('#tacgia').text('* ' + res.errors['tacgia'][0]);
                    if (res.errors['menu1']) $('#menucap1').text('* ' + res.errors['menu1'][0]);
                    if (res.errors['danhmuc']) $('#danhmucsup').text('* ' + res.errors['danhmuc'][0]);
                    if (res.errors['image']) $('#imagesup').text('* ' + res.errors['image'][0]);
                    if (res.errors['file']) $('#filesup').text('* ' + res.errors['file'][0]);
                    if (res.errors['diadiem']) $('#diadiemsup').text('* ' + res.errors['diadiem'][0]);
                    if (res.errors['noidung']) $('#noidungsup').text('* ' + res.errors['noidung'][0]);
                    toastr.error('Có lỗi trong dữ liệu, vui lòng kiểm tra form.');
                }
                else if (res.success) {
                    toastr.success(res.message, 'Thành công');

                    // Đóng modal đúng cách
                    let modalEl = document.getElementById('baivietModal');
                    let modalInstance = bootstrap.Modal.getInstance(modalEl);
                    if (modalInstance) {
                        modalInstance.hide();
                    } else {
                        bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                    }

                    // Reset form
                    clearForm(false);

                    // Reload table
                    if (typeof renderBaivietTable === 'function') {
                        $.get('/admin/tatcabaiviet', function (res) {
                            renderBaivietTable(res.data);
                        });
                    }
                }
            },
            error: function (xhr) {
                console.log('Error:', xhr.responseText);
                toastr.error('Không thể lưu bài viết. Kiểm tra console.');
            },
            complete: function () {
                $('#loadingOverlay').removeClass('show');

                $saveBtn.prop('disabled', false).html(oldSaveHtml);

                isPostSaving = false;
            }
        });
    });
});


$('#addBaiVietBtn').on('click', function () {
    clearForm(false);
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open').css('overflow', '').css('padding-right', '');

    bootstrap.Modal.getOrCreateInstance(document.getElementById('baivietModal')).show();
});
// Xóa bài viết


$(document).ready(function () {
    $('#baivietTable').on('click', '.delete-btn', function () {
        let baivietId = $(this).data('id');

        Swal.fire({
            title: 'Xóa bài viết?',
            text: "Bạn có chắc chắn muốn xóa bài viết này?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Xóa',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/admin/baiviet/' + baivietId,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (res) {
                        if (res.success) {
                            toastr.success('Bài viết đã được xóa thành công');
                            // Reload bảng
                            $.get('/admin/tatcabaiviet', function (res) {
                                renderBaivietTable(res.data);
                            });
                        } else {
                            toastr.error('Không thể xóa bài viết');
                        }
                    },
                    error: function (xhr) {
                        console.log(xhr.responseText);
                        toastr.error('Đã xảy ra lỗi khi xóa bài viết');
                    }
                });
            }
        });
    });
});


//#region Load menu

$.ajax({
    type: "GET",
    url: "/admin/menus",
    timeout: 10000,
    success: function (res) {
        loadMenuCap1(res.data || []);
    },
    error: function (xhr) {
        console.error('Lỗi /admin/menus:', xhr.status, xhr.responseText);
        toastr.error("Không tải được menu.");
        loadMenuCap1([]);
    }
});
// Hàm load cấp 1
function loadMenuCap1(data) {
    let $menu1 = $('#menu1');
    $menu1.empty().append('<option value="">Chọn menu cấp 1</option>');
    data.forEach(function (item) {
        let option = new Option(item.name, item.id, false, false);
        $(option).data("children", item.children || []);
        $menu1.append(option);
    });
    $menu1.trigger('change');
}

// Hàm load cấp 2
function loadMenuCap2(data) {
    let $menu2 = $('#menu2');
    $menu2.empty().append('<option value="">Chọn menu cấp 2</option>');
    data.forEach(function (item) {
        let option = new Option(item.name, item.id, false, false);
        $(option).data("children", item.children || []);
        $menu2.append(option);
    });
    $menu2.trigger('change');
}

// Hàm load cấp 3
function loadMenuCap3(data) {
    let $menu3 = $('#menu3');
    $menu3.empty().append('<option value="">Chọn menu cấp 3</option>');
    data.forEach(function (item) {
        let option = new Option(item.name, item.id, false, false);
        $menu3.append(option);
    });
    $menu3.trigger('change');
}

// Xử lý sự kiện thay đổi
$('#menu1').on('change', function () {
    let children = $(this).find("option:selected").data("children") || [];
    loadMenuCap2(children);
    $('#menu3').empty().append('<option value="">Chọn menu cấp 3</option>').trigger('change');
});

$('#menu2').on('change', function () {
    let children = $(this).find("option:selected").data("children") || [];
    loadMenuCap3(children);
});
//#endregion



//#region Load tác giả

$(function () {
    $('.select22').select2({
        placeholder: "Chọn tác giả",
        width: '100%' // đảm bảo select2 chiếm đầy đủ chiều rộng của container
    });
});


$.ajax({
    type: "GET",
    url: "/admin/tacgia/list",
    timeout: 10000,
    success: function (res) {
        loadTacgia(res.data || []);
    },
    error: function (xhr) {
        console.error('Lỗi /admin/tacgia/list:', xhr.status, xhr.responseText);
        toastr.error("Không tải được danh sách tác giả.");
        loadTacgia([]);
    }
});

function loadTacgia(data) {
    let $tg = $('#listtacgia'); // select có name="tacgia[]"
    $tg.empty();

    data.forEach(function (item) {
        let option = new Option(item.ten, item.id, false, false);
        $tg.append(option);
    });

    // Làm mới select2 (nếu có dùng)
    $tg.trigger('change');
}

//#endregion


//#region Load danh mục
$.ajax({
    type: "GET",
    url: "/admin/danhmuc",
    timeout: 10000,
    success: function (res) {
        loadDanhmuc(res.data || []);
    },
    error: function (xhr) {
        console.error('Lỗi /admin/danhmuc:', xhr.status, xhr.responseText);
        toastr.error("Không tải được danh mục.");
        loadDanhmuc([]);
    }
});

function loadDanhmuc(data) {
    let $dm = $('#danhmuc');
    $dm.empty().append('<option value="">Chọn danh mục</option>');
    data.forEach(function (item) {
        let option = new Option(item.tendanhmuc, item.id, false, false);
        $dm.append(option);
    });
    $dm.trigger('change');
}
//#endregion


//#region Sự kiện — hiện/ẩn block và tính trạng thái

// Kiểm tra danh mục có phải sự kiện không (id=3)
function isSuKien() {
    return $('#danhmuc').val() == '3';
}

// datetime-local của HTML5 — không cần khởi tạo plugin

// Tính trạng thái dựa theo thời gian
// Parse "2025-09-24T14:35" (datetime-local) → Date object
function parseSuKienDate(str) {
    if (!str) return null;
    return new Date(str); // datetime-local trả về ISO — new Date() parse trực tiếp được
}

// Chuyển "2025-09-24T14:35" → "2025-09-24 14:35:00" để lưu DB
function toDbFormat(str) {
    if (!str) return '';
    return str.replace('T', ' ') + ':00';
}

function tinhTrangThaiSuKien() {
    let bdVal = $('#thoigian_bd').val(); // dạng "2025-09-24T14:35"
    let ktVal = $('#thoigian_kt').val();
    let $status = $('#sukienStatus');
    let statusDb = '';

    if (!bdVal) {
        $status.val('').css('color', '');
        $('#sukien_status_db').val('');
        return;
    }

    let now = new Date();
    let bd = new Date(bdVal);
    let kt = ktVal ? new Date(ktVal) : null;

    let statusText = '';
    let statusColor = '';
    if (now < bd) {
        statusText = 'Sắp diễn ra';
        statusColor = '#0d6efd';
        statusDb = 'upcoming';
    } else if (!kt || now <= kt) {
        statusText = 'Đang diễn ra';
        statusColor = '#198754';
        statusDb = 'show';
    } else {
        statusText = 'Đã kết thúc';
        statusColor = '#6c757d';
        statusDb = 'ended';
    }

    $status.val(statusText).css('color', statusColor);
    $('#sukien_status_db').val(statusDb);


}



// Thêm vào trong sự kiện #danhmuc change (bên dưới phần isSuKien)
$('#danhmuc').on('change', function () {
    if (isSuKien()) {
        $('#blockSuKien').slideDown(200);
        tinhTrangThaiSuKien();
    } else {
        $('#blockSuKien').slideUp(200);
        $('#thoigian_bd, #thoigian_kt, #diadiem').val('');
        $('#sukienStatus').val('').css('color', '');
    }
});

// Cập nhật trạng thái khi thay đổi thời gian (datetime-local dùng event 'change')
$('#thoigian_bd, #thoigian_kt').on('change', function () {
    tinhTrangThaiSuKien();
});

//#endregion


//#region Load modal con

// Khi mở modal con → ẨN modal cha
let isChildModalOpen = false;
let currentLienQuan = []; // 🔹 biến lưu danh sách bài viết liên quan hiện tại
function syncLienQuanInput() {
    $('input[name="lienquan"]').val(currentLienQuan.join(','));
}

function tickLienQuanVisible() {
    $('.chk-lienquan').each(function () {
        let id = String($(this).val());
        $(this).prop('checked', currentLienQuan.includes(id));
    });
}

$(document).off('change', '.chk-lienquan').on('change', '.chk-lienquan', function () {
    let id = String($(this).val());

    if ($(this).is(':checked')) {
        if (!currentLienQuan.includes(id)) {
            currentLienQuan.push(id);
        }
    } else {
        currentLienQuan = currentLienQuan.filter(item => item !== id);
    }

    syncLienQuanInput();
});
// Khi nhấn sửa bài viết
$('#baivietTable').on('click', '.edit-btn', function () {
    let postId = $(this).data('id');

    $.ajax({
        type: "get",
        url: "/admin/baiviet/" + postId,
        success: function (res) {
            let bv = res.data;

            // --- Reset currentLienQuan trước ---
            currentLienQuan = [];
            $('input[name="lienquan"]').val('');

            // --- Lưu danh sách bài viết liên quan ---
            if (bv.bv_lienquan) {
                currentLienQuan = bv.bv_lienquan.split(',').map(id => id.trim()).filter(Boolean);
                $('input[name="lienquan"]').val(currentLienQuan.join(','));
            }

            // --- Clear preview ---
            $('#imagePreview').hide().attr('src', '');
            $('#pdf-preview').html('');

            // --- Fill các trường ---
            $('#postId').val(bv.id);
            $('#titleInput').html(bv.tieude);
            $('textarea[name="tomtat"]').val(bv.tomtat);
            $('#danhmuc').val(bv.danhmuc).trigger('change');
            $('#is_featured').val(bv.is_featured).trigger('change');
            $('#datetimepicker').val(bv.ngaydang);
            if (bv.auto_publish !== undefined) $('#auto_publish').val(bv.auto_publish).trigger('change');

            if (bv.menu1_id) $('#menu1').val(bv.menu1_id).trigger('change');
            if (bv.menu2_id) $('#menu2').val(bv.menu2_id).trigger('change');
            if (bv.menu3_id) $('#menu3').val(bv.menu3_id).trigger('change');

            if (bv.thoigian_bd || bv.thoigian_kt || bv.diadiem) {
                if (bv.thoigian_bd) {
                    $('#thoigian_bd').val(bv.thoigian_bd.substring(0, 16).replace(' ', 'T'));
                    $('input[name="thoigian_bd_db"]').val(bv.thoigian_bd.substring(0, 16) + ':00');
                }
                if (bv.thoigian_kt) {
                    $('#thoigian_kt').val(bv.thoigian_kt.substring(0, 16).replace(' ', 'T'));
                    $('input[name="thoigian_kt_db"]').val(bv.thoigian_kt.substring(0, 16) + ':00');
                }
                $('#diadiem').val(bv.diadiem || '');
                $('#blockSuKien').show();
                tinhTrangThaiSuKien();
            }

            if (bv.tacgia_ids) {
                let ids = bv.tacgia_ids.split(',');
                $('.select22').val(ids).trigger('change');
            }

            if (bv.image_url) $('#imagePreview').attr('src', bv.image_url).show();
            if (bv.file_url) $('#pdf-preview').html(`<a href="${bv.file_url}" target="_blank">Xem PDF hiện có</a>`);

            $('#summernote').summernote('code', bv.noidung || '');

            $('#baivietModal').modal('show');
        },
        error: function () {
            toastr.error('Không thể tải dữ liệu bài viết.');
        }
    });
});


// 👉 Khi click nút "Bài viết liên quan" — xử lý thủ công, không dùng data-bs-toggle
// (tránh lỗi Bootstrap getInstance null khi modal cha chưa được khởi tạo qua JS)
$('#btnMoLienQuan').on('click', function () {
    // Lấy danh mục đang chọn trong form
    let danhmucId = $('#danhmuc').val();

    if (!danhmucId) {
        toastr.warning('Vui lòng chọn danh mục trước khi chọn bài viết liên quan.');
        return;
    }

    isChildModalOpen = true;

    // 1️⃣ Ẩn modal cha trước
    bootstrap.Modal.getOrCreateInstance(document.getElementById('baivietModal')).hide();

    // 2️⃣ Đợi modal cha ẩn xong rồi load dữ liệu + mở modal con
    $('#baivietModal').one('hidden.bs.modal', function () {
        // Hiện loading trên bảng
        if ($.fn.DataTable.isDataTable('#tableLienQuan')) {
            $('#tableLienQuan').DataTable().clear().draw();
        }

        $('#modalLienQuan').data('from-parent', true);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalLienQuan')).show();

        // 3️⃣ Load bài viết theo danh mục
        $.ajax({
            type: 'GET',
            url: '/admin/baiviet-theo-danhmuc',
            timeout: 10000,
            data: { danhmuc: danhmucId },
            success: function (res) {
                let data = res.data || [];

                let currentId = $('#postId').val();
                if (currentId) {
                    data = data.filter(bv => String(bv.id) !== String(currentId));
                }

                renderLienQuanTable(data);

                setTimeout(function () {
                    tickLienQuanVisible();
                }, 100);
            },
            error: function (xhr) {
                console.error('Lỗi /admin/baiviet-theo-danhmuc:', xhr.status, xhr.responseText);
                toastr.error('Không thể tải bài viết theo danh mục.');
                renderLienQuanTable([]);
            }
        });
    });
});

// Khi modal con mở xong
$('#modalLienQuan').on('shown.bs.modal', function () {

    if ($.fn.DataTable.isDataTable('#tableLienQuan')) {
        $('#tableLienQuan').DataTable().columns.adjust();
    }

    $('#tableLienQuan').off('draw.dt').on('draw.dt', function () {
        tickLienQuanVisible();
    });

});


// Khi modal con đóng
$('#modalLienQuan').on('hidden.bs.modal', function () {
    if ($(this).data('from-parent')) {
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open');
        setTimeout(function () {
            let parentModal = document.getElementById('baivietModal');

            if (parentModal) {
                bootstrap.Modal.getOrCreateInstance(parentModal).show();
            }
        }, 100);
        $(this).data('from-parent', false);
        isChildModalOpen = false;
    }
});


// 👉 Nút "Xác nhận"
$('#confirmLienQuan').on('click', function () {
    syncLienQuanInput();
    closeModalLienQuan();
});

// 👉 Hàm đóng modal con
function closeModalLienQuan() {
    let modalEl = document.getElementById('modalLienQuan');

    if (modalEl) {
        bootstrap.Modal.getOrCreateInstance(modalEl).hide();
    }
}



//#endregion




//#region Mới: Gợi ý tiêu đề và tóm tắt
// CSS cho loading overlay

// Event handler cho nút gợi ý
$('#suggestBtn').on('click', function (e) {
    e.preventDefault();
    const $btn = $(this);

    // Disable button và đổi text
    $btn.prop('disabled', true).text('Đang gợi ý...');

    // Hiện loading overlay
    $('#loadingOverlay').addClass('show');

    // Lấy nội dung từ Summernote (HTML) -> chuyển thành text
    let contentHtml = $('#summernote').summernote('code') || '';
    // Loại bỏ thẻ để model đọc được text thuần
    let contentText = $('<div>').html(contentHtml).text().trim();

    // Kiểm tra nội dung rỗng
    if (!contentText) {
        toastr.warning('Vui lòng nhập nội dung trước khi gợi ý.');
        $('#loadingOverlay').removeClass('show');
        $btn.prop('disabled', false).text('Gợi ý');
        return;
    }

    $.ajax({
        url: '/admin/suggest',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ content: contentText }),
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (res) {
            if (res.success) {
                $('#titleInput').text(res.title || '');
                $('textarea[name="tomtat"]').val(res.summary || '');
                toastr.success('Đã tạo gợi ý tiêu đề và tóm tắt!');
            } else {
                toastr.warning(res.message || 'Không lấy được gợi ý.');
                console.log(res);
            }
        },
        error: function (xhr) {
            let msg = 'Lỗi khi lấy gợi ý.';
            if (xhr.responseJSON && xhr.responseJSON.message)
                msg += '\n' + xhr.responseJSON.message;
            toastr.error(msg);
            console.log(xhr.responseText);
        },


        complete: function () {
            // Ẩn loading overlay
            $('#loadingOverlay').removeClass('show');

            // Enable button và khôi phục text
            $btn.prop('disabled', false).text('Gợi ý');
        }
    });
});
//#endregion