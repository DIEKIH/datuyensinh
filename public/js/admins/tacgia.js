$(document).ready(function () {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    })
});


var tacgia_danhsach = $("#tacgia_danhsach").DataTable({
    ajax: {
        type: 'get',
        url: 'tacgia_danhsach',
    },
    columns: [
        { title: "STT", data: 'stt' },
        { title: "Tên tác giả", data: 'ten' },
        {
            title: "Chức năng",
            data: null,
            render: function (data, type, row) {
                return `
                    <i class="fa-regular fa-pen-to-square" onclick="tacgia_load(${row.id})"></i>
                    &nbsp;&nbsp;&nbsp;
                    <i class="fa-solid fa-trash-can text-danger trash" onclick="tacgia_xoa(${row.id})"></i>
                `;
            }
        },
    ],
    columnDefs: [
        { targets: [0, 2], className: 'dt-center' }
    ],
    scrollY: 420,
    language: {
        emptyTable: "Không tìm thấy tác giả",
        info: " _START_ / _END_ trên _TOTAL_",
        paginate: {
            first: "Trang đầu",
            last: "Trang cuối",
            next: "Trang sau",
            previous: "Trang trước",
        },
        search: "Tìm kiếm:",
        loadingRecords: " Đang tải tác giả...",
        lengthMenu: " _MENU_ Tác giả",
        infoEmpty: "",
    },
    retrieve: true,
    paging: true,
    lengthChange: true,
    searching: true,
    ordering: false,
    info: false,
    autoWidth: true,
    responsive: true,
    select: true,
});

function tacgia_them() {
    $('#modal_event').show();
    $('#err_tacgia_ten').val('');
    setTimeout(() => {
        $.ajax({
            type: "get",
            url: "tacgia_them",
            data: {
                tacgia_ten: $('#tacgia_ten').val(),
            },
            success: function (response) {
                $('#modal_event').hide();
                switch (response) {
                    case '1':
                        toastr.success('Thêm thành công');
                        tacgia_danhsach.ajax.reload();
                        tacgia_reset_form()
                        break;
                    case '-1':
                        toastr.error('Lỗi hệ thống');
                        break;
                    case '0':
                        toastr.warning('Thêm thất bại');
                        break;
                    default:
                        for (let key in response) {
                            $('#err_' + key).text(response[key]);
                        }
                        break;
                }
            }
        });
    }, 800);
}

function tacgia_xoa(id) {
    Swal.fire({
        title: "Bạn có chắc chắn?",
        text: "Bạn có chắc chắn muốn xóa tác giả này? Hành động này không thể hoàn tác!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Xóa",
        cancelButtonText: "Hủy"
    }).then((result) => {
        if (result.isConfirmed) {
            $("#modal_event").show();

            $.ajax({
                type: "get",
                url: "tacgia_xoa",
                data: { id: id },
                success: function () {
                    tacgia_danhsach.ajax.reload();
                    $("#modal_event").hide();
                    toastr.success("Xóa tác giả thành công!");
                },
                error: function () {
                    $("#modal_event").hide();
                    toastr.error("Xóa tác giả thất bại!");
                }
            });
        }
    });
}

function tacgia_load(id) {
    $.ajax({
        type: "get",
        url: "tacgia_load",
        data: { id: id },
        success: function (data) {
            $("#update_tacgia_ten").val(data.ten);
            $("#btn_capnhat_tacgia").attr("data-id", id);
            $("#tacgia_capnhat_modal").show();
        }
    });
}

// 👉 Cập nhật tác giả
function tacgia_capnhat() {
    var id = $("#btn_capnhat_tacgia").attr("data-id");
    var ten = $("#update_tacgia_ten").val();
    console.log("ID:", id, "Tên:", ten);
    $.ajax({
        type: "get",
        url: "tacgia_capnhat",
        data: { id: id, update_tacgia_ten: ten },
        success: function (response) {
            switch (response) {
                case 1:
                case '1':
                    toastr.success('Cập nhật thành công');
                    tacgia_danhsach.ajax.reload();
                    $("#tacgia_capnhat_modal").hide();
                    break;
                case 0:
                case '0':
                    toastr.warning('Cập nhật thất bại');
                    break;
                case -1:
                case '-1':
                    toastr.error('Lỗi hệ thống');
                    break;
                default:
                    for (let key in response) {
                        $("#err_" + key).text(response[key]);
                    }
                    break;
            }
        }
    });
}

function tacgia_reset_form() {
    $("#tacgia_ten").val('');
    $("#err_tacgia_ten").text('');
}
// Hàm làm mới form thêm + form cập nhật
function tacgia_lammoi() {
    // Xóa lỗi hiển thị
    $("#err_tacgia_ten").text('');
    $("#err_update_tacgia_ten").text('');

    // Xóa nội dung input
    $("#tacgia_ten").val('');
    $("#update_tacgia_ten").val('');

    // Xóa ID đang được chọn trong modal cập nhật (nếu có)
    $("#btn_capnhat_tacgia").attr("data-id", "");
}

//Hàm đóng modal cập nhật
function close_modal_tacgia() {
    $("#tacgia_capnhat_modal").hide();

    // Gọi luôn làm mới để reset nội dung và lỗi
    tacgia_lammoi();
}
