$(function () {
    'use strict';

    if (!$('#admissionToxicPage').length) return;

    const Cms = window.AdmissionCms;
    let currentStatus = $('#admissionToxicPage').data('status') || 'pending';

    function statusBadge(status) {
        if (status === 'pending') return '<span class="badge bg-danger">Chờ xử lý</span>';
        if (status === 'ignored') return '<span class="badge bg-secondary">Bỏ qua</span>';
        if (status === 'deleted') return '<span class="badge bg-dark">Đã xóa</span>';
        if (status === 'blocked') return '<span class="badge bg-black">Đã block</span>';
        return '<span class="badge bg-secondary">' + Cms.escapeHtml(status) + '</span>';
    }

    function loadToxicComments(page) {
        page = page || 1;
        $('#toxicCommentsBody').html(Cms.tableMessage(6, 'Đang tải cảnh báo...', 'loading'));

        $.get('/admin/admission-cms/toxic-comments', {
            status: currentStatus,
            page: page
        })
            .done(function (response) {
                const paginator = Cms.getPaginator(response);
                const data = paginator.data || [];

                if (!data.length) {
                    $('#toxicCommentsBody').html(Cms.tableMessage(6, 'Không có bình luận trong nhóm này.', 'empty'));
                    $('#toxicPagination').empty();
                    return;
                }

                $('#toxicCommentsBody').html(data.map(function (comment) {
                    const actions = comment.status === 'pending'
                        ? '<div class="d-grid gap-1">' +
                            '<button class="btn btn-outline-secondary btn-sm btn-toxic-action" data-id="' + comment.id + '" data-action="ignore">Bỏ qua</button>' +
                            '<button class="btn btn-outline-danger btn-sm btn-toxic-action" data-id="' + comment.id + '" data-action="delete">Xóa comment</button>' +
                            '<button class="btn btn-dark btn-sm btn-toxic-action" data-id="' + comment.id + '" data-action="block">Block</button>' +
                            '</div>'
                        : '-';

                    return '<tr>' +
                        '<td><span class="badge bg-primary">' + Cms.escapeHtml(comment.platform) + '</span>' +
                            '<div class="text-muted small mt-1">Comment ID: ' + Cms.escapeHtml(comment.comment_id) + '</div></td>' +
                        '<td><strong>' + Cms.escapeHtml(comment.sender_name || 'Không rõ') + '</strong>' +
                            '<div class="text-muted small">ID: ' + Cms.escapeHtml(comment.sender_id || '') + '</div></td>' +
                        '<td><div class="admission-answer">' + Cms.escapeHtml(comment.message || '') + '</div></td>' +
                        '<td><span class="badge bg-warning text-dark mb-2">' + Cms.escapeHtml(comment.sentiment_category || '') + '</span>' +
                            '<div class="small">' + Cms.escapeHtml(comment.ai_reason || '') + '</div><div class="mt-2">' + statusBadge(comment.status) + '</div></td>' +
                        '<td class="text-nowrap">' + Cms.formatDate(comment.created_at) + '</td>' +
                        '<td>' + actions + '</td>' +
                        '</tr>';
                }).join(''));

                Cms.renderPagination(paginator, '#toxicPagination', loadToxicComments);
            })
            .fail(function (xhr) {
                $('#toxicCommentsBody').html(Cms.tableMessage(6, Cms.errorMessage(xhr, 'Không tải được cảnh báo.'), 'error'));
            });
    }

    $('.btn-toxic-filter').on('click', function () {
        currentStatus = $(this).data('status');
        $('.btn-toxic-filter').removeClass('btn-danger btn-secondary').addClass('btn-outline-secondary');
        $(this).removeClass('btn-outline-secondary btn-outline-danger').addClass(currentStatus === 'pending' ? 'btn-danger' : 'btn-secondary');
        loadToxicComments(1);
    });

    $(document).on('click', '.btn-toxic-action', function () {
        const id = $(this).data('id');
        const action = $(this).data('action');
        if (!window.confirm('Thực hiện hành động "' + action + '"?')) return;

        $.post('/admin/admission-cms/toxic-comments/' + id + '/action', { action: action })
            .done(function (response) {
                if (response.success === false) {
                    toastr.error(response.message || 'Không thể xử lý bình luận.');
                    return;
                }
                toastr.success(response.message || 'Đã xử lý bình luận.');
                loadToxicComments(1);
            })
            .fail(function (xhr) { toastr.error(Cms.errorMessage(xhr, 'Không thể xử lý bình luận.')); });
    });

    loadToxicComments(1);
});
