// $(function () {
//     'use strict';

//     if (!$('#admissionToxicPage').length) return;

//     const Cms = window.AdmissionCms;
//     let currentStatus =
//         $('#admissionToxicPage').data('status') || 'pending';

//     function statusBadge(status) {
//         if (status === 'pending') {
//             return '<span class="badge bg-danger">Chờ xử lý</span>';
//         }

//         if (status === 'ignored') {
//             return '<span class="badge bg-secondary">Đã bỏ ẩn</span>';
//         }

//         if (status === 'deleted') {
//             return '<span class="badge bg-dark">Đã xóa</span>';
//         }

//         if (status === 'blocked') {
//             return '<span class="badge bg-black">Đã block</span>';
//         }

//         return '<span class="badge bg-secondary">'
//             + Cms.escapeHtml(status || '')
//             + '</span>';
//     }

//     function hideInformation(comment) {
//         const hideStatus =
//             String(comment.facebook_hide_status || 'unknown');

//         if (hideStatus === 'hidden') {
//             return '<div class="mt-2">'
//                 + '<span class="badge bg-success">'
//                 + 'Đã ẩn tự động trên Facebook'
//                 + '</span>'
//                 + '</div>';
//         }

//         if (hideStatus === 'failed') {
//             const error = Cms.escapeHtml(
//                 comment.facebook_hide_error
//                     || 'Facebook không trả chi tiết lỗi.'
//             );

//             return '<div class="mt-2">'
//                 + '<span class="badge bg-danger">'
//                 + 'Ẩn tự động thất bại'
//                 + '</span>'
//                 + '<div class="small text-danger mt-1">'
//                 + error
//                 + '</div>'
//                 + '</div>';
//         }

//         return '<div class="mt-2">'
//             + '<span class="badge bg-secondary">'
//             + 'Chưa xác định trạng thái ẩn'
//             + '</span>'
//             + '</div>';
//     }

//     function postInformation(comment) {
//         const postId = Cms.escapeHtml(comment.post_id || '');
//         const postMessage = Cms.escapeHtml(
//             comment.post_message
//                 || 'Không lấy được mô tả bài viết.'
//         );
//         const postUrl = String(comment.post_url || '').trim();

//         let html = '';

//         if (postId) {
//             html += '<div class="small mt-2">'
//                 + '<strong>Post ID:</strong> '
//                 + postId
//                 + '</div>';
//         }

//         html += '<div class="text-muted small mt-1">'
//             + postMessage
//             + '</div>';

//         if (postUrl) {
//             html += '<a class="btn btn-outline-primary '
//                 + 'btn-sm mt-2" '
//                 + 'href="' + Cms.escapeHtml(postUrl) + '" '
//                 + 'target="_blank" '
//                 + 'rel="noopener noreferrer">'
//                 + '<i class="fab fa-facebook me-1"></i>'
//                 + 'Mở bài viết trên Facebook'
//                 + '</a>';
//         }

//         return html;
//     }

//     function actionButtons(comment) {
//         if (comment.status !== 'pending') {
//             return '-';
//         }

//         let html = '<div class="d-grid gap-1">';

//         /*
//          * Chỉ hiện nút Bỏ ẩn khi n8n xác nhận
//          * bình luận đã được ẩn trên Facebook.
//          */
//         if (comment.facebook_hide_status === 'hidden') {
//             html += '<button '
//                 + 'class="btn btn-outline-success '
//                 + 'btn-sm btn-toxic-action" '
//                 + 'data-id="' + comment.id + '" '
//                 + 'data-action="ignore">'
//                 + 'Bỏ ẩn (Cho hiển thị lại)'
//                 + '</button>';
//         }

//         html += '<button '
//             + 'class="btn btn-outline-danger '
//             + 'btn-sm btn-toxic-action" '
//             + 'data-id="' + comment.id + '" '
//             + 'data-action="delete">'
//             + 'Xóa vĩnh viễn trên FB'
//             + '</button>'
//             + '</div>';

//         return html;
//     }

//     function loadToxicComments(page) {
//         page = page || 1;

//         $('#toxicCommentsBody').html(
//             Cms.tableMessage(
//                 6,
//                 'Đang tải cảnh báo...',
//                 'loading'
//             )
//         );

//         $.get('/admin/admission-cms/toxic-comments', {
//             status: currentStatus,
//             page: page
//         })
//             .done(function (response) {
//                 const paginator = Cms.getPaginator(response);
//                 const data = paginator.data || [];

//                 if (!data.length) {
//                     $('#toxicCommentsBody').html(
//                         Cms.tableMessage(
//                             6,
//                             'Không có bình luận trong nhóm này.',
//                             'empty'
//                         )
//                     );
//                     $('#toxicPagination').empty();
//                     return;
//                 }

//                 $('#toxicCommentsBody').html(
//                     data.map(function (comment) {
//                         return '<tr>'
//                             + '<td>'
//                             + '<span class="badge bg-primary">'
//                             + Cms.escapeHtml(
//                                 comment.platform || ''
//                             )
//                             + '</span>'
//                             + '<div class="text-muted small mt-1">'
//                             + 'Comment ID: '
//                             + Cms.escapeHtml(
//                                 comment.comment_id || ''
//                             )
//                             + '</div>'
//                             + postInformation(comment)
//                             + '</td>'
//                             + '<td><strong>'
//                             + Cms.escapeHtml(
//                                 comment.sender_name || 'Không rõ'
//                             )
//                             + '</strong>'
//                             + '<div class="text-muted small">'
//                             + 'ID: '
//                             + Cms.escapeHtml(
//                                 comment.sender_id || ''
//                             )
//                             + '</div></td>'
//                             + '<td><div class="admission-answer">'
//                             + Cms.escapeHtml(
//                                 comment.message || ''
//                             )
//                             + '</div></td>'
//                             + '<td>'
//                             + '<span class="badge bg-warning '
//                             + 'text-dark mb-2">'
//                             + Cms.escapeHtml(
//                                 comment.sentiment_category || ''
//                             )
//                             + '</span>'
//                             + '<div class="small">'
//                             + Cms.escapeHtml(
//                                 comment.ai_reason || ''
//                             )
//                             + '</div>'
//                             + hideInformation(comment)
//                             + '<div class="mt-2">'
//                             + statusBadge(comment.status)
//                             + '</div>'
//                             + '</td>'
//                             + '<td class="text-nowrap">'
//                             + Cms.formatDate(comment.created_at)
//                             + '</td>'
//                             + '<td>'
//                             + actionButtons(comment)
//                             + '</td>'
//                             + '</tr>';
//                     }).join('')
//                 );

//                 Cms.renderPagination(
//                     paginator,
//                     '#toxicPagination',
//                     loadToxicComments
//                 );
//             })
//             .fail(function (xhr) {
//                 $('#toxicCommentsBody').html(
//                     Cms.tableMessage(
//                         6,
//                         Cms.errorMessage(
//                             xhr,
//                             'Không tải được cảnh báo.'
//                         ),
//                         'error'
//                     )
//                 );
//             });
//     }

//     $('.btn-toxic-filter').on('click', function () {
//         currentStatus = $(this).data('status');

//         $('.btn-toxic-filter')
//             .removeClass('btn-danger btn-secondary')
//             .addClass('btn-outline-secondary');

//         $(this)
//             .removeClass(
//                 'btn-outline-secondary btn-outline-danger'
//             )
//             .addClass(
//                 currentStatus === 'pending'
//                     ? 'btn-danger'
//                     : 'btn-secondary'
//             );

//         loadToxicComments(1);
//     });

//     $(document).on(
//         'click',
//         '.btn-toxic-action',
//         function () {
//             const id = $(this).data('id');
//             const action = $(this).data('action');
//             const actionLabel =
//                 action === 'delete'
//                     ? 'xóa vĩnh viễn'
//                     : 'bỏ ẩn';

//             if (
//                 !window.confirm(
//                     'Bạn có chắc muốn '
//                     + actionLabel
//                     + ' bình luận này?'
//                 )
//             ) {
//                 return;
//             }

//             $.post(
//                 '/admin/admission-cms/toxic-comments/'
//                     + id
//                     + '/action',
//                 {
//                     action: action
//                 }
//             )
//                 .done(function (response) {
//                     if (response.success === false) {
//                         toastr.error(
//                             response.message
//                                 || 'Không thể xử lý bình luận.'
//                         );
//                         return;
//                     }

//                     toastr.success(
//                         response.message
//                             || 'Đã xử lý bình luận.'
//                     );

//                     loadToxicComments(1);
//                 })
//                 .fail(function (xhr) {
//                     toastr.error(
//                         Cms.errorMessage(
//                             xhr,
//                             'Không thể xử lý bình luận.'
//                         )
//                     );
//                 });
//         }
//     );

//     loadToxicComments(1);
// });


$(function () {
    'use strict';

    if (!$('#admissionToxicPage').length) return;

    const Cms = window.AdmissionCms;

    let currentStatus =
        $('#admissionToxicPage').data('status') || 'all';

    let currentPage = 1;

    const commentsById = {};

    function text(value, fallback) {
        value = String(value || '').trim();

        if (value) {
            return Cms.escapeHtml(value);
        }

        return Cms.escapeHtml(fallback || '');
    }

    function truncate(value, length) {
        value = String(value || '').trim();

        if (!value) {
            return '';
        }

        if (value.length <= length) {
            return value;
        }

        return value.substring(0, length).trim() + '...';
    }

    function safeUrl(value) {
        value = String(value || '').trim();

        if (!/^https?:\/\//i.test(value)) {
            return '';
        }

        return Cms.escapeHtml(value);
    }

    function statusBadge(status) {
        status = String(status || '').toLowerCase();

        if (status === 'pending') {
            return '<span class="badge bg-danger">'
                + 'Chờ xử lý'
                + '</span>';
        }

        if (status === 'ignored') {
            return '<span class="badge bg-success">'
                + 'Đã bỏ ẩn'
                + '</span>';
        }

        if (status === 'deleted') {
            return '<span class="badge bg-dark">'
                + 'Đã xóa'
                + '</span>';
        }

        if (status === 'blocked') {
            return '<span class="badge bg-dark">'
                + 'Đã chặn'
                + '</span>';
        }

        return '<span class="badge bg-secondary">'
            + text(status, 'Chưa xác định')
            + '</span>';
    }

    function categoryBadge(category) {
        const value = String(category || '').trim();
        const normalized = value.toLowerCase();

        let css = 'bg-warning text-dark';

        if (
            normalized.includes('toxic')
            || normalized.includes('xúc phạm')
            || normalized.includes('insult')
        ) {
            css = 'bg-danger';
        } else if (
            normalized.includes('spam')
        ) {
            css = 'bg-warning text-dark';
        } else if (
            normalized.includes('cạnh tranh')
            || normalized.includes('compet')
        ) {
            css = 'bg-info text-dark';
        } else if (
            normalized.includes('normal')
            || normalized.includes('bình thường')
        ) {
            css = 'bg-success';
        }

        return '<span class="badge ' + css + '">'
            + text(value, 'Chưa phân loại')
            + '</span>';
    }

    function facebookHideBadge(status) {
        status = String(status || 'unknown');

        if (status === 'hidden') {
            return '<span class="badge bg-success">'
                + '<i class="fas fa-eye-slash me-1"></i>'
                + 'Đã ẩn trên Facebook'
                + '</span>';
        }

        if (status === 'failed') {
            return '<span class="badge bg-danger">'
                + '<i class="fas fa-exclamation-triangle me-1"></i>'
                + 'Ẩn thất bại'
                + '</span>';
        }

        return '<span class="badge bg-secondary">'
            + 'Chưa xác định trạng thái ẩn'
            + '</span>';
    }

    function rowActions(comment) {
        return '<button '
            + 'type="button" '
            + 'class="btn btn-outline-primary btn-sm '
            + 'btn-toxic-detail" '
            + 'data-id="' + comment.id + '">'
            + '<i class="fas fa-eye me-1"></i>'
            + 'Chi tiết'
            + '</button>';
    }

    function renderRow(comment) {
        commentsById[String(comment.id)] = comment;

        return '<tr>'

            + '<td>'
            + '<div class="toxic-source">'
            + '<span class="badge bg-primary">'
            + '<i class="fab fa-facebook-f me-1"></i>'
            + text(comment.platform, 'Facebook')
            + '</span>'
            + '</div>'
            + '</td>'

            + '<td>'
            + '<div class="toxic-user-name">'
            + text(comment.sender_name, 'Không rõ')
            + '</div>'
            + '</td>'

            + '<td>'
            + '<div class="toxic-comment-content">'
            + text(
                truncate(comment.message, 180),
                'Không có nội dung.'
            )
            + '</div>'
            + '</td>'

            + '<td>'
            + categoryBadge(comment.sentiment_category)
            + '</td>'

            + '<td>'
            + '<div class="toxic-status-stack">'
            + statusBadge(comment.status)
            + facebookHideBadge(
                comment.facebook_hide_status
            )
            + '</div>'
            + '</td>'

            + '<td>'
            + '<div class="toxic-time">'
            + Cms.formatDate(comment.created_at)
            + '</div>'
            + '</td>'

            + '<td class="text-center">'
            + rowActions(comment)
            + '</td>'

            + '</tr>';
    }

    function modalActions(comment) {
        if (comment.status !== 'pending') {
            return '';
        }

        let html = '';

        if (comment.facebook_hide_status === 'hidden') {
            html += '<button '
                + 'type="button" '
                + 'class="btn btn-outline-success '
                + 'btn-toxic-action" '
                + 'data-id="' + comment.id + '" '
                + 'data-action="ignore">'
                + '<i class="fas fa-eye me-1"></i>'
                + 'Bỏ ẩn'
                + '</button>';
        }

        html += '<button '
            + 'type="button" '
            + 'class="btn btn-danger btn-toxic-action" '
            + 'data-id="' + comment.id + '" '
            + 'data-action="delete">'
            + '<i class="fas fa-trash-alt me-1"></i>'
            + 'Xóa trên Facebook'
            + '</button>';

        return html;
    }

    function showModal(comment) {
        if (!comment) {
            return;
        }

        $('#toxicModalBadges').html(
            '<span class="badge bg-primary">'
                + '<i class="fab fa-facebook-f me-1"></i>'
                + text(comment.platform, 'Facebook')
                + '</span>'
                + categoryBadge(comment.sentiment_category)
                + statusBadge(comment.status)
        );

        $('#toxicModalMessage').html(
            text(comment.message, 'Không có nội dung bình luận.')
        );

        $('#toxicModalSenderName').html(
            text(comment.sender_name, 'Không rõ')
        );

        $('#toxicModalSenderId').html(
            text(comment.sender_id, 'Không có')
        );

        $('#toxicModalCreatedAt').html(
            Cms.formatDate(comment.created_at)
        );

        $('#toxicModalCategory').html(
            categoryBadge(comment.sentiment_category)
        );

        $('#toxicModalAiReason').html(
            text(
                comment.ai_reason,
                'AI không cung cấp lý do chi tiết.'
            )
        );

        $('#toxicModalHideStatus').html(
            facebookHideBadge(
                comment.facebook_hide_status
            )
        );

        $('#toxicModalStatus').html(
            statusBadge(comment.status)
        );

        const facebookError =
            String(comment.facebook_hide_error || '').trim();

        if (facebookError) {
            $('#toxicModalFacebookError').html(
                '<div class="toxic-facebook-error">'
                + '<strong>Lỗi Facebook:</strong><br>'
                + text(facebookError)
                + '</div>'
            );
        } else {
            $('#toxicModalFacebookError').empty();
        }

        $('#toxicModalPostMessage').html(
            text(
                comment.post_message,
                'Không lấy được nội dung bài viết.'
            )
        );

        $('#toxicModalPostId').html(
            text(comment.post_id, 'Không có')
        );

        $('#toxicModalCommentId').html(
            text(comment.comment_id, 'Không có')
        );

        const postUrl = safeUrl(comment.post_url);

        if (postUrl) {
            $('#toxicModalPostLink').html(
                '<a '
                + 'class="btn btn-outline-primary btn-sm" '
                + 'href="' + postUrl + '" '
                + 'target="_blank" '
                + 'rel="noopener noreferrer">'
                + '<i class="fab fa-facebook me-1"></i>'
                + 'Mở bài viết trên Facebook'
                + '</a>'
            );
        } else {
            $('#toxicModalPostLink').empty();
        }

        $('#toxicModalActions').html(
            modalActions(comment)
        );

        openDetailModal();
    }

    function openDetailModal() {
        const element =
            document.getElementById('toxicDetailModal');

        /*
         * Bootstrap 5.
         */
        if (
            window.bootstrap
            && window.bootstrap.Modal
        ) {
            const modal =
                window.bootstrap.Modal.getOrCreateInstance(
                    element
                );

            modal.show();
            return;
        }

        /*
         * Bootstrap 4 / jQuery.
         */
        if (
            $.fn.modal
            && typeof $('#toxicDetailModal').modal === 'function'
        ) {
            $('#toxicDetailModal').modal('show');
        }
    }

    function closeDetailModal() {
        const element =
            document.getElementById('toxicDetailModal');

        if (
            window.bootstrap
            && window.bootstrap.Modal
        ) {
            const modal =
                window.bootstrap.Modal.getInstance(element);

            if (modal) {
                modal.hide();
            }

            return;
        }

        if (
            $.fn.modal
            && typeof $('#toxicDetailModal').modal === 'function'
        ) {
            $('#toxicDetailModal').modal('hide');
        }
    }

    function loadToxicComments(page) {
        currentPage = page || 1;

        $('#toxicCommentsBody').html(
            Cms.tableMessage(
                7,
                'Đang tải cảnh báo...',
                'loading'
            )
        );

        $.get(
            '/admin/admission-cms/toxic-comments',
            {
                status: currentStatus,
                page: currentPage
            }
        )
            .done(function (response) {
                const paginator = Cms.getPaginator(response);
                const data = paginator.data || [];

                Object.keys(commentsById).forEach(
                    function (key) {
                        delete commentsById[key];
                    }
                );

                if (!data.length) {
                    $('#toxicCommentsBody').html(
                        Cms.tableMessage(
                            7,
                            'Không có bình luận trong nhóm này.',
                            'empty'
                        )
                    );

                    $('#toxicPagination').empty();
                    return;
                }

                $('#toxicCommentsBody').html(
                    data.map(renderRow).join('')
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
                        7,
                        Cms.errorMessage(
                            xhr,
                            'Không tải được cảnh báo.'
                        ),
                        'error'
                    )
                );
            });
    }

    $('.btn-toxic-filter').on(
        'click',
        function () {
            currentStatus =
                String($(this).data('status') || 'all');

            $('.btn-toxic-filter')
                .removeClass(
                    'btn-secondary '
                    + 'btn-danger '
                    + 'btn-success '
                    + 'btn-dark '
                    + 'active'
                )
                .addClass('btn-outline-secondary');

            const $button = $(this);

            $button.removeClass(
                'btn-outline-secondary '
                + 'btn-outline-danger '
                + 'btn-outline-success '
                + 'btn-outline-dark'
            );

            if (currentStatus === 'pending') {
                $button.addClass('btn-danger');
            } else if (currentStatus === 'ignored') {
                $button.addClass('btn-success');
            } else if (currentStatus === 'deleted') {
                $button.addClass('btn-dark');
            } else {
                $button.addClass('btn-secondary');
            }

            $button.addClass('active');

            loadToxicComments(1);
        }
    );

    $(document).on(
        'click',
        '.btn-toxic-detail',
        function () {
            const id = String($(this).data('id'));

            showModal(
                commentsById[id]
            );
        }
    );

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

            const message =
                action === 'delete'
                    ? 'Bình luận sẽ bị xóa khỏi Facebook '
                        + 'và không thể khôi phục. '
                        + 'Bạn có chắc muốn tiếp tục?'
                    : 'Bạn có chắc muốn bỏ ẩn và cho '
                        + 'bình luận này hiển thị lại?';

            if (!window.confirm(message)) {
                return;
            }

            const $button = $(this);

            $button.prop('disabled', true);

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
                            || (
                                'Đã '
                                + actionLabel
                                + ' bình luận.'
                            )
                    );

                    closeDetailModal();

                    loadToxicComments(currentPage);
                })
                .fail(function (xhr) {
                    toastr.error(
                        Cms.errorMessage(
                            xhr,
                            'Không thể xử lý bình luận.'
                        )
                    );
                })
                .always(function () {
                    $button.prop('disabled', false);
                });
        }
    );

    loadToxicComments(1);
});