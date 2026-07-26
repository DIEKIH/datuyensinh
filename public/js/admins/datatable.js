// Hàm khởi tạo DataTable chung
function initDataTable(selector, data, columns) {
    $(selector).DataTable({
        destroy: true,       // reset bảng cũ trước khi load mới
        data: data,          // dữ liệu
        columns: columns,    // cấu hình cột

        // =========================
        // CẤU HÌNH CHUNG
        // =========================
        paging: true,
        pageLength: 10,
        searching: true,
        ordering: true,
        responsive: true,
        order: [[0, 'asc']],  // sort mặc định theo cột đầu tiên

        language: {
            search: "Tìm kiếm:",
            lengthMenu: "Hiển thị _MENU_ bản ghi",
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
