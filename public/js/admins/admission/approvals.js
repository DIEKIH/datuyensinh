$(function () {
    'use strict';

    if (!$('#admissionApprovalsPage').length) {
        return;
    }

    const Cms = window.AdmissionCms;

    let approvalRows = [];
    let currentApprovalId = null;
    let currentPage = 1;
    let lastPage = 1;
    let totalApprovals = 0;
    let hasNextPage = false;

    function formatDatetime(value) {
        if (!value) {
            return '—';
        }

        const date = new Date(value);

        if (Number.isNaN(date.getTime())) {
            return value;
        }

        const pad = function (number) {
            return String(number).padStart(2, '0');
        };

        return pad(date.getDate())
            + '/'
            + pad(date.getMonth() + 1)
            + '/'
            + date.getFullYear()
            + ' '
            + pad(date.getHours())
            + ':'
            + pad(date.getMinutes());
    }

    function getItemName(item) {
        return item.customer_name || item.full_name || 'Chưa có tên';
    }

    function getItemEmail(item) {
        return item.customer_email || item.email || 'Chưa có email';
    }

    function getItemPhone(item) {
        return item.customer_phone || item.phone || 'Chưa có số điện thoại';
    }

    function getItemQuestion(item) {
        return item.question || item.note || '';
    }

    /*
     * Chuẩn hóa nhiều kiểu phản hồi phân trang:
     * - Laravel paginator trực tiếp;
     * - API Resource: data + meta + links;
     * - response.data chứa paginator;
     * - danh sách mảng không kèm metadata.
     */
    function normalizePaginator(response) {
        const cmsPaginator = Cms.getPaginator(response) || {};
        const responseData = response && response.data
            ? response.data
            : {};
        const nestedPaginator = (
            responseData
            && !Array.isArray(responseData)
        ) ? responseData : {};

        const meta = cmsPaginator.meta
            || response.meta
            || nestedPaginator.meta
            || {};

        let rows = [];

        if (Array.isArray(cmsPaginator.data)) {
            rows = cmsPaginator.data;
        } else if (Array.isArray(nestedPaginator.data)) {
            rows = nestedPaginator.data;
        } else if (Array.isArray(responseData)) {
            rows = responseData;
        } else if (Array.isArray(response)) {
            rows = response;
        }

        const page = Number(
            cmsPaginator.current_page
            || meta.current_page
            || nestedPaginator.current_page
            || response.current_page
            || 1
        );

        const explicitLastPage = Number(
            cmsPaginator.last_page
            || meta.last_page
            || nestedPaginator.last_page
            || response.last_page
            || 0
        );

        const nextUrl = cmsPaginator.next_page_url
            || meta.next_page_url
            || nestedPaginator.next_page_url
            || (
                response.links
                && response.links.next
            )
            || null;

        const canLoadNext = Boolean(nextUrl)
            || (
                explicitLastPage > 0
                && page < explicitLastPage
            );

        const resolvedLastPage = explicitLastPage > 0
            ? explicitLastPage
            : (canLoadNext ? page + 1 : page);

        const rawTotal = cmsPaginator.total
            ?? meta.total
            ?? nestedPaginator.total
            ?? response.total
            ?? null;

        const resolvedTotal = rawTotal === null
            ? rows.length
            : Math.max(Number(rawTotal || 0), rows.length);

        return {
            data: rows,
            current_page: page,
            last_page: resolvedLastPage,
            total: resolvedTotal,
            has_next_page: canLoadNext
        };
    }

    function renderApprovalList(rows) {
        if (!rows.length) {
            $('#approvalList').html(
                '<div class="approval-list-state">'
                + '<i class="fas fa-check-circle text-success d-block mb-2" '
                + 'style="font-size:32px"></i>'
                + 'Không có nội dung nào đang chờ duyệt.'
                + '</div>'
            );
            return;
        }

        const html = rows.map(function (item) {
            const question = String(getItemQuestion(item))
                .replace(/\s+/g, ' ')
                .trim();

            return `
                <div class="approval-item"
                     data-id="${Number(item.id)}"
                     tabindex="0"
                     role="button"
                     aria-label="Xem nội dung chờ duyệt ${Number(item.id)}">
                    <div class="approval-item-top">
                        <span class="approval-avatar">
                            <i class="fas fa-user"></i>
                        </span>

                        <div class="min-w-0">
                            <div class="approval-item-name">
                                ${Cms.escapeHtml(getItemName(item))}
                            </div>
                            <div class="approval-item-contact">
                                ${Cms.escapeHtml(getItemEmail(item))}
                            </div>
                        </div>

                        <span class="approval-channel-badge">
                            ${Cms.escapeHtml(item.channel || 'Không rõ')}
                        </span>
                    </div>

                    <div class="approval-item-question">
                        ${Cms.escapeHtml(question || 'Không có nội dung câu hỏi')}
                    </div>

                    <div class="approval-item-bottom">
                        <span>
                            <i class="fas fa-clock me-1"></i>
                            ${Cms.escapeHtml(
                                item.created_at_text
                                || formatDatetime(item.created_at)
                            )}
                        </span>
                        <span class="ms-auto">
                            <i class="fas fa-hourglass-half me-1"></i>
                            Chờ duyệt
                        </span>
                    </div>
                </div>
            `;
        }).join('');

        $('#approvalList').html(html).scrollTop(0);
    }

    function updatePagination() {
        $('#approvalListCount').text(totalApprovals.toLocaleString());
        $('#approvalsPageInfo').text(
            totalApprovals > 0
                ? 'Trang ' + currentPage + '/' + lastPage
                    + ' · ' + totalApprovals.toLocaleString() + ' nội dung'
                : '0 nội dung'
        );

        $('#btnApprovalPrev').prop('disabled', currentPage <= 1);
        $('#btnApprovalNext').prop(
            'disabled',
            totalApprovals === 0
                || (!hasNextPage && currentPage >= lastPage)
        );
    }

    function markSelectedApproval(id) {
        $('.approval-item').removeClass('active');
        $('.approval-item[data-id="' + id + '"]').addClass('active');
    }

    function clearApprovalPreview(message) {
        currentApprovalId = null;
        $('#approvalCurrentId').val('');
        $('#approvalPreviewContent').addClass('d-none');
        $('#approvalPreviewEmpty')
            .removeClass('d-none')
            .find('.small')
            .text(
                message
                || 'Chọn một nội dung ở bên trái để kiểm tra và xử lý.'
            );
    }

    function showApprovalPreview(item) {
        if (!item) {
            clearApprovalPreview();
            return;
        }

        currentApprovalId = Number(item.id);
        markSelectedApproval(currentApprovalId);

        $('#approvalCurrentId').val(currentApprovalId);
        $('#approvalPreviewName').text(getItemName(item));
        $('#approvalPreviewEmail').text(getItemEmail(item));
        $('#approvalPreviewPhone').text(getItemPhone(item));
        $('#approvalPreviewChannel').text(item.channel || 'Không rõ');
        $('#approvalPreviewQuestion').text(getItemQuestion(item));
        $('#approvalAiAnswer').val(item.ai_answer || '');
        $('#approvalAdminFeedback').val(item.admin_feedback || '');

        $('#approvalPreviewEmpty').addClass('d-none');
        $('#approvalPreviewContent').removeClass('d-none');
        $('.approval-preview-body').scrollTop(0);
    }

    function selectApproval(id) {
        const item = approvalRows.find(function (row) {
            return String(row.id) === String(id);
        });

        showApprovalPreview(item || null);
    }

    function loadApprovals(page, options) {
        options = options || {};
        page = Number(page || 1);

        if (!options.silent) {
            $('#approvalList').html(
                '<div class="approval-list-state">'
                + '<i class="fas fa-spinner fa-spin me-2"></i>'
                + 'Đang tải nội dung chờ duyệt...'
                + '</div>'
            );
        }

        $.get('/admin/admission-cms/approvals', { page: page })
            .done(function (response) {
                const paginator = normalizePaginator(response);

                approvalRows = paginator.data;
                currentPage = paginator.current_page;
                lastPage = paginator.last_page;
                totalApprovals = paginator.total;
                hasNextPage = paginator.has_next_page;

                if (
                    !approvalRows.length
                    && totalApprovals > 0
                    && currentPage > 1
                ) {
                    loadApprovals(currentPage - 1, options);
                    return;
                }

                renderApprovalList(approvalRows);
                updatePagination();

                if (!approvalRows.length) {
                    clearApprovalPreview(
                        'Không có nội dung nào đang chờ duyệt.'
                    );
                    return;
                }

                const selectedStillExists = approvalRows.some(function (row) {
                    return String(row.id) === String(currentApprovalId);
                });

                if (!selectedStillExists) {
                    currentApprovalId = approvalRows[0].id;
                }

                selectApproval(currentApprovalId);
            })
            .fail(function (xhr) {
                approvalRows = [];
                totalApprovals = 0;
                hasNextPage = false;
                updatePagination();

                $('#approvalList').html(
                    '<div class="approval-list-state text-danger">'
                    + '<i class="fas fa-circle-exclamation me-2"></i>'
                    + Cms.escapeHtml(
                        Cms.errorMessage(
                            xhr,
                            'Không tải được hàng chờ duyệt.'
                        )
                    )
                    + '</div>'
                );

                clearApprovalPreview('Không thể tải dữ liệu chờ duyệt.');
            });
    }

    function processApprovalAction(action, $button) {
        const id = Number($('#approvalCurrentId').val() || 0);

        if (!id) {
            toastr.error('Không xác định được nội dung cần xử lý.');
            return;
        }

        const oldHtml = $button.html();

        $button
            .prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin me-1"></i>Đang xử lý...');

        $.post('/admin/admission-cms/approvals/' + id + '/action', {
            action: action,
            ai_answer: $('#approvalAiAnswer').val(),
            admin_feedback: $('#approvalAdminFeedback').val()
        })
            .done(function (response) {
                if (response.success === false) {
                    toastr.error(
                        response.message || 'Không thể xử lý nội dung.'
                    );
                    return;
                }

                toastr.success(response.message || 'Đã xử lý nội dung.');

                if (action === 'rewrite') {
                    const newAnswer = response.new_answer || '';
                    $('#approvalAiAnswer').val(newAnswer);

                    const row = approvalRows.find(function (item) {
                        return String(item.id) === String(id);
                    });

                    if (row) {
                        row.ai_answer = newAnswer;
                    }

                    return;
                }

                currentApprovalId = null;
                loadApprovals(currentPage, { silent: true });
            })
            .fail(function (xhr) {
                toastr.error(
                    Cms.errorMessage(xhr, 'Không thể xử lý nội dung.')
                );
            })
            .always(function () {
                $button.prop('disabled', false).html(oldHtml);
            });
    }

    $(document).on('click', '.approval-item', function () {
        selectApproval($(this).data('id'));
    });

    $(document).on('keydown', '.approval-item', function (event) {
        if (event.key !== 'Enter' && event.key !== ' ') {
            return;
        }

        event.preventDefault();
        selectApproval($(this).data('id'));
    });

    $(document).on('click', '.btn-approval-action', function () {
        processApprovalAction($(this).data('action'), $(this));
    });

    $('#btnApprovalPrev').on('click', function () {
        if (currentPage > 1) {
            currentApprovalId = null;
            loadApprovals(currentPage - 1);
        }
    });

    $('#btnApprovalNext').on('click', function () {
        if (hasNextPage || currentPage < lastPage) {
            currentApprovalId = null;
            loadApprovals(currentPage + 1);
        }
    });

    $('#reloadApprovals').on('click', function () {
        loadApprovals(currentPage || 1);
    });


    loadApprovals(1);
});
