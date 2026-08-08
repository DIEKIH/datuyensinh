$(function () {
    'use strict';

    if (!$('#admissionToxicPage').length) return;

    const Cms = window.AdmissionCms;
    let currentStatus =
        $('#admissionToxicPage').data('status') || 'pending';

    function statusBadge(status) {
        if (status === 'pending') {
            return '<span class="badge bg-danger">Chờ xử lý</span>';
        }

        if (status === 'ignored') {
            return '<span class="badge bg-secondary">Đã bỏ ẩn</span>';
        }

        if (status === 'deleted') {
            return '<span class="badge bg-dark">Đã xóa</span>';
        }

        if (status === 'blocked') {
            return '<span class="badge bg-black">Đã block</span>';
        }

        return '<span class="badge bg-secondary">'
            + Cms.escapeHtml(status || '')
            + '</span>';
    }

    function hideInformation(comment) {
        const hideStatus =
            String(comment.facebook_hide_status || 'unknown');

        if (hideStatus === 'hidden') {
            return '<div class="mt-2">'
                + '<span class="badge bg-success">'
                + 'Đã ẩn tự động trên Facebook'
                + '</span>'
                + '</div>';
        }

        if (hideStatus === 'failed') {
            const error = Cms.escapeHtml(
                comment.facebook_hide_error
                    || 'Facebook không trả chi tiết lỗi.'
            );

            return '<div class="mt-2">'
                + '<span class="badge bg-danger">'
                + 'Ẩn tự động thất bại'
                + '</span>'
                + '<div class="small text-danger mt-1">'
                + error
                + '</div>'
                + '</div>';
        }

        return '<div class="mt-2">'
            + '<span class="badge bg-secondary">'
            + 'Chưa xác định trạng thái ẩn'
            + '</span>'
            + '</div>';
    }

    function postInformation(comment) {
        const postId = Cms.escapeHtml(comment.post_id || '');
        const postMessage = Cms.escapeHtml(
            comment.post_message
                || 'Không lấy được mô tả bài viết.'
        );
        const postUrl = String(comment.post_url || '').trim();

        let html = '';

        if (postId) {
            html += '<div class="small mt-2">'
                + '<strong>Post ID:</strong> '
                + postId
                + '</div>';
        }

        html += '<div class="text-muted small mt-1">'
            + postMessage
            + '</div>';

        if (postUrl) {
            html += '<a class="btn btn-outline-primary '
                + 'btn-sm mt-2" '
                + 'href="' + Cms.escapeHtml(postUrl) + '" '
                + 'target="_blank" '
                + 'rel="noopener noreferrer">'
                + '<i class="fab fa-facebook me-1"></i>'
                + 'Mở bài viết trên Facebook'
                + '</a>';
        }

        return html;
    }

    function actionButtons(comment) {
        if (comment.status !== 'pending') {
            return '-';
        }

        let html = '<div class="d-grid gap-1">';

        /*
         * Chỉ hiện nút Bỏ ẩn khi n8n xác nhận
         * bình luận đã được ẩn trên Facebook.
         */
        if (comment.facebook_hide_status === 'hidden') {
            html += '<button '
                + 'class="btn btn-outline-success '
                + 'btn-sm btn-toxic-action" '
                + 'data-id="' + comment.id + '" '
                + 'data-action="ignore">'
                + 'Bỏ ẩn (Cho hiển thị lại)'
                + '</button>';
        }

        html += '<button '
            + 'class="btn btn-outline-danger '
            + 'btn-sm btn-toxic-action" '
            + 'data-id="' + comment.id + '" '
            + 'data-action="delete">'
            + 'Xóa vĩnh viễn trên FB'
            + '</button>'
            + '</div>';

        return html;
    }

    function loadToxicComments(page) {
        page = page || 1;

        $('#toxicCommentsBody').html(
            Cms.tableMessage(
                6,
                'Đang tải cảnh báo...',
                'loading'
            )
        );

        $.get('/admin/admission-cms/toxic-comments', {
            status: currentStatus,
            page: page
        })
            .done(function (response) {
                const paginator = Cms.getPaginator(response);
                const data = paginator.data || [];

                if (!data.length) {
                    $('#toxicCommentsBody').html(
                        Cms.tableMessage(
                            6,
                            'Không có bình luận trong nhóm này.',
                            'empty'
                        )
                    );
                    $('#toxicPagination').empty();
                    return;
                }

                $('#toxicCommentsBody').html(
                    data.map(function (comment) {
                        return '<tr>'
                            + '<td>'
                            + '<span class="badge bg-primary">'
                            + Cms.escapeHtml(
                                comment.platform || ''
                            )
                            + '</span>'
                            + '<div class="text-muted small mt-1">'
                            + 'Comment ID: '
                            + Cms.escapeHtml(
                                comment.comment_id || ''
                            )
                            + '</div>'
                            + postInformation(comment)
                            + '</td>'
                            + '<td><strong>'
                            + Cms.escapeHtml(
                                comment.sender_name || 'Không rõ'
                            )
                            + '</strong>'
                            + '<div class="text-muted small">'
                            + 'ID: '
                            + Cms.escapeHtml(
                                comment.sender_id || ''
                            )
                            + '</div></td>'
                            + '<td><div class="admission-answer">'
                            + Cms.escapeHtml(
                                comment.message || ''
                            )
                            + '</div></td>'
                            + '<td>'
                            + '<span class="badge bg-warning '
                            + 'text-dark mb-2">'
                            + Cms.escapeHtml(
                                comment.sentiment_category || ''
                            )
                            + '</span>'
                            + '<div class="small">'
                            + Cms.escapeHtml(
                                comment.ai_reason || ''
                            )
                            + '</div>'
                            + hideInformation(comment)
                            + '<div class="mt-2">'
                            + statusBadge(comment.status)
                            + '</div>'
                            + '</td>'
                            + '<td class="text-nowrap">'
                            + Cms.formatDate(comment.created_at)
                            + '</td>'
                            + '<td>'
                            + actionButtons(comment)
                            + '</td>'
                            + '</tr>';
                    }).join('')
                );

                Cms.renderPagination(
                    paginator,
                    '#toxicPagination',
                    loadToxicComments
                );
            })
            .fail(function (xhr) {
                $('#toxicCommentsBody').html(
                    Cms.tableMessage(
                        6,
                        Cms.errorMessage(
                            xhr,
                            'Không tải được cảnh báo.'
                        ),
                        'error'
                    )
                );
            });
    }

    $('.btn-toxic-filter').on('click', function () {
        currentStatus = $(this).data('status');

        $('.btn-toxic-filter')
            .removeClass('btn-danger btn-secondary')
            .addClass('btn-outline-secondary');

        $(this)
            .removeClass(
                'btn-outline-secondary btn-outline-danger'
            )
            .addClass(
                currentStatus === 'pending'
                    ? 'btn-danger'
                    : 'btn-secondary'
            );

        loadToxicComments(1);
    });

    $(document).on(
        'click',
        '.btn-toxic-action',
        function () {
            const id = $(this).data('id');
            const action = $(this).data('action');
            const actionLabel =
                action === 'delete'
                    ? 'xóa vĩnh viễn'
                    : 'bỏ ẩn';

            if (
                !window.confirm(
                    'Bạn có chắc muốn '
                    + actionLabel
                    + ' bình luận này?'
                )
            ) {
                return;
            }

            $.post(
                '/admin/admission-cms/toxic-comments/'
                    + id
                    + '/action',
                {
                    action: action
                }
            )
                .done(function (response) {
                    if (response.success === false) {
                        toastr.error(
                            response.message
                                || 'Không thể xử lý bình luận.'
                        );
                        return;
                    }

                    toastr.success(
                        response.message
                            || 'Đã xử lý bình luận.'
                    );

                    loadToxicComments(1);
                })
                .fail(function (xhr) {
                    toastr.error(
                        Cms.errorMessage(
                            xhr,
                            'Không thể xử lý bình luận.'
                        )
                    );
                });
        }
    );

    loadToxicComments(1);
});
