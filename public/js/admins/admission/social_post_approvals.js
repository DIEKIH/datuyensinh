$(function () {
    const csrf = $('meta[name="csrf-token"]').attr('content');

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': csrf }
    });

    let rows = [];
    let selectedId = null;
    let currentPage = 1;
    let lastPage = 1;
    let total = 0;

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function errorMessage(xhr, fallback) {
        if (xhr.responseJSON && xhr.responseJSON.message) {
            return xhr.responseJSON.message;
        }
        return fallback;
    }

    function formatDate(value) {
        if (!value) return '—';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return value;
        return date.toLocaleString('vi-VN');
    }

    function paginatorFrom(response) {
        return response && response.data ? response.data : {};
    }

    function renderList() {
        $('#socialPostApprovalCount').text(total);

        if (!rows.length) {
            $('#socialPostApprovalList').html(
                '<div class="post-approval-state">'
                + '<i class="fas fa-circle-check me-2 text-success"></i>'
                + 'Không có bài viết nào đang chờ duyệt.'
                + '</div>'
            );
            clearPreview();
            return;
        }

        const html = rows.map(function (item) {
            const active = String(item.id) === String(selectedId) ? ' active' : '';
            return '<div class="post-approval-item' + active + '" '
                + 'data-id="' + item.id + '" tabindex="0">'
                + '<div class="post-approval-item-title">'
                + escapeHtml(item.title || 'Bài viết không có tiêu đề')
                + '</div>'
                + '<div class="post-approval-item-caption">'
                + escapeHtml(item.caption || '')
                + '</div>'
                + '<div class="text-muted mt-2" style="font-size:.72rem">'
                + '<i class="fas fa-clock me-1"></i>'
                + escapeHtml(formatDate(item.created_at))
                + '</div>'
                + '</div>';
        }).join('');

        $('#socialPostApprovalList').html(html);
    }

    function updatePagination() {
        $('#socialPostApprovalPageInfo').text(
            total + ' bài · trang ' + currentPage + '/' + lastPage
        );
        $('#socialPostApprovalPrev').prop('disabled', currentPage <= 1);
        $('#socialPostApprovalNext').prop('disabled', currentPage >= lastPage);
    }

    function clearPreview() {
        selectedId = null;
        $('#socialPostApprovalCurrentId').val('');
        $('#socialPostApprovalContent').addClass('d-none');
        $('#socialPostApprovalEmpty').removeClass('d-none');
    }

    function selectItem(id) {
        const item = rows.find(function (row) {
            return String(row.id) === String(id);
        });

        if (!item) {
            clearPreview();
            return;
        }

        selectedId = item.id;
        renderList();

        $('#socialPostApprovalCurrentId').val(item.id);
        $('#socialPostApprovalTitle').text(item.title || 'Bài viết không có tiêu đề');
        $('#socialPostApprovalCreatedAt').text(formatDate(item.created_at));
        $('#socialPostApprovalExecution').text(
            item.execution_id ? 'Execution #' + item.execution_id : 'Không có mã execution'
        );
        $('#socialPostApprovalCaption').val(item.caption || '');
        $('#socialPostApprovalHashtags').val(item.hashtags || '');
        $('#socialPostApprovalReason').val('');

        const platforms = Array.isArray(item.platforms) ? item.platforms : [];
        $('#socialPostApprovalPlatforms').html(
            platforms.map(function (platform) {
                return '<span class="badge bg-primary ms-1">'
                    + escapeHtml(platform)
                    + '</span>';
            }).join('')
        );

        if (item.article_url) {
            $('#socialPostApprovalSource').html(
                '<a href="' + escapeHtml(item.article_url) + '" '
                + 'target="_blank" rel="noopener noreferrer">'
                + '<i class="fas fa-up-right-from-square me-1"></i>'
                + escapeHtml(item.article_url)
                + '</a>'
            );
        } else {
            $('#socialPostApprovalSource').text('Không có liên kết bài viết nguồn.');
        }

        const images = Array.isArray(item.content_images)
            ? item.content_images.filter(Boolean)
            : [];

        if (images.length) {
            $('#socialPostApprovalImages').html(
                images.map(function (url) {
                    return '<a href="' + escapeHtml(url) + '" target="_blank" rel="noopener noreferrer">'
                        + '<img src="' + escapeHtml(url) + '" alt="Ảnh bài viết">'
                        + '</a>';
                }).join('')
            );
            $('#socialPostApprovalImagesBlock').removeClass('d-none');
        } else {
            $('#socialPostApprovalImages').empty();
            $('#socialPostApprovalImagesBlock').addClass('d-none');
        }

        $('#socialPostApprovalEmpty').addClass('d-none');
        $('#socialPostApprovalContent').removeClass('d-none');
    }

    function loadPage(page, silent) {
        page = Number(page || 1);

        if (!silent) {
            $('#socialPostApprovalList').html(
                '<div class="post-approval-state">'
                + '<i class="fas fa-spinner fa-spin me-2"></i>'
                + 'Đang tải bài viết chờ duyệt...'
                + '</div>'
            );
        }

        $.get('/admin/admission-cms/social-post-approvals', {
            status: 'pending',
            page: page
        })
            .done(function (response) {
                const paginator = paginatorFrom(response);
                rows = Array.isArray(paginator.data) ? paginator.data : [];
                currentPage = Number(paginator.current_page || page || 1);
                lastPage = Number(paginator.last_page || 1);
                total = Number(paginator.total || 0);

                if (!rows.length && total > 0 && currentPage > 1) {
                    loadPage(currentPage - 1, silent);
                    return;
                }

                if (!rows.some(function (row) {
                    return String(row.id) === String(selectedId);
                })) {
                    selectedId = rows.length ? rows[0].id : null;
                }

                renderList();
                updatePagination();

                if (selectedId) {
                    selectItem(selectedId);
                }
            })
            .fail(function (xhr) {
                rows = [];
                total = 0;
                renderList();
                updatePagination();
                toastr.error(errorMessage(xhr, 'Không tải được hàng chờ duyệt bài đăng.'));
            });
    }

    function processAction(action, $button) {
        const id = Number($('#socialPostApprovalCurrentId').val() || 0);
        if (!id) {
            toastr.error('Không xác định được bài viết cần xử lý.');
            return;
        }

        const reason = $('#socialPostApprovalReason').val().trim();
        if (action === 'reject' && !reason) {
            toastr.error('Vui lòng nhập lý do từ chối.');
            $('#socialPostApprovalReason').trigger('focus');
            return;
        }

        if (action === 'approve' && !$('#socialPostApprovalCaption').val().trim()) {
            toastr.error('Nội dung bài đăng không được để trống.');
            $('#socialPostApprovalCaption').trigger('focus');
            return;
        }

        const oldHtml = $button.html();
        $('.social-post-approval-action').prop('disabled', true);
        $button.html('<i class="fas fa-spinner fa-spin me-1"></i>Đang xử lý...');

        $.post('/admin/admission-cms/social-post-approvals/' + id + '/action', {
            action: action,
            caption: $('#socialPostApprovalCaption').val(),
            hashtags: $('#socialPostApprovalHashtags').val(),
            reason: reason
        })
            .done(function (response) {
                if (response.success === false) {
                    toastr.error(response.message || 'Không thể xử lý bài viết.');
                    return;
                }

                toastr.success(response.message || 'Đã xử lý bài viết.');
                selectedId = null;
                loadPage(currentPage, true);
            })
            .fail(function (xhr) {
                toastr.error(errorMessage(xhr, 'Không thể xử lý bài viết.'));
            })
            .always(function () {
                $('.social-post-approval-action').prop('disabled', false);
                $button.html(oldHtml);
            });
    }

    $(document).on('click', '.post-approval-item', function () {
        selectItem($(this).data('id'));
    });

    $(document).on('keydown', '.post-approval-item', function (event) {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            selectItem($(this).data('id'));
        }
    });

    $(document).on('click', '.social-post-approval-action', function () {
        processAction($(this).data('action'), $(this));
    });

    $('#reloadSocialPostApprovals').on('click', function () {
        loadPage(currentPage);
    });

    $('#socialPostApprovalPrev').on('click', function () {
        if (currentPage > 1) loadPage(currentPage - 1);
    });

    $('#socialPostApprovalNext').on('click', function () {
        if (currentPage < lastPage) loadPage(currentPage + 1);
    });

    loadPage(1);
});
