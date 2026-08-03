$(document).ready(function () {
    'use strict';

    // ═══════════════════════════════════════════════════════
    // GLOBAL STATE
    // ═══════════════════════════════════════════════════════
    let currentSessionId = null;
    let sessionCurrentPage = 1;
    let sessionLastPage = 1;
    let sessionTotal = 0;
    const sessionPerPage = 8;

    let currentTicketId = null;
    let ticketCurrentPage = 1;
    let ticketLastPage = 1;
    const ticketPerPage = 8;

    let currentLibraryId = null;
    let libraryCurrentPage = 1;
    let libraryLastPage = 1;
    let libraryTotal = 0;
    const libraryPerPage = 8;

    let lastPendingTicketCount = null;
    let pollingInterval = null;
    let ticketPollingInterval = null;
    let lastSeenPendingTicketId = null;
    let isCheckingPendingTickets = false;

    // ═══════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════
    function getCsrfToken() {
        const token = $('meta[name="csrf-token"]').attr('content');

        if (token) {
            return token;
        }

        return $('input[name="_token"]').first().val() || '';
    }

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': getCsrfToken()
        }
    });

    function formatDatetime(str) {
        if (!str) {
            return '—';
        }

        const date = new Date(str);

        if (isNaN(date.getTime())) {
            return str;
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

    function escapeHtml(text) {
        return String(text || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function escapeHtmlWithBreaks(text) {
        return escapeHtml(text).replace(/\n/g, '<br>');
    }

    function normalizeLibraryReview(review) {
        review = review || {};

        return {
            can_save: review.can_save !== false,
            hard_block: review.hard_block === true,
            reuse_ready: review.reuse_ready === true,
            blocking_reasons: Array.isArray(review.blocking_reasons)
                ? review.blocking_reasons
                : [],
            warnings: Array.isArray(review.warnings)
                ? review.warnings
                : []
        };
    }

    function reviewMessage(review) {
        review = normalizeLibraryReview(review);

        if (review.blocking_reasons.length) {
            return review.blocking_reasons.join(' ');
        }

        if (review.reuse_ready) {
            return review.warnings.length
                ? 'Đã sẵn sàng dùng lại. Gợi ý: '
                    + review.warnings.join(' ')
                : 'Nội dung đã sẵn sàng để chatbot tái sử dụng.';
        }

        if (review.warnings.length) {
            return review.warnings.join(' ');
        }

        return 'Đã lưu trong kho.';
    }

    function renderLibraryReviewBox(review) {
        review = normalizeLibraryReview(review);
        const $box = $('#libraryValidationBox');

        if (!$box.length) {
            return;
        }

        if (review.blocking_reasons.length) {
            $box
                .removeClass('is-ready is-warning')
                .addClass('is-blocked')
                .html(
                    '<div class="library-review-title">'
                    + '<i class="fas fa-circle-xmark me-1"></i>'
                    + 'Chưa thể lưu</div>'
                    + '<ul>'
                    + review.blocking_reasons.map(function (item) {
                        return '<li>' + escapeHtml(item) + '</li>';
                    }).join('')
                    + '</ul>'
                )
                .removeClass('d-none');
            return;
        }

        if (review.warnings.length) {
            const ready = review.reuse_ready;

            $box
                .removeClass('is-ready is-blocked is-warning')
                .addClass(ready ? 'is-ready' : 'is-warning')
                .html(
                    '<div class="library-review-title">'
                    + '<i class="fas '
                    + (ready
                        ? 'fa-circle-check'
                        : 'fa-triangle-exclamation')
                    + ' me-1"></i>'
                    + (ready
                        ? 'Đã lưu và sẵn sàng dùng lại'
                        : 'Đã lưu, nhưng chưa dùng tự động')
                    + '</div>'
                    + '<ul>'
                    + review.warnings.map(function (item) {
                        return '<li>' + escapeHtml(item) + '</li>';
                    }).join('')
                    + '</ul>'
                    + '<small>'
                    + (ready
                        ? 'Các góp ý trên không ngăn chatbot lấy câu trả lời này.'
                        : 'Chỉ nội dung rác hoặc không đúng chủ đề mới bị tạm ngừng dùng tự động.')
                    + '</small>'
                )
                .removeClass('d-none');
            return;
        }

        $box
            .removeClass('is-warning is-blocked')
            .addClass('is-ready')
            .html(
                '<div class="library-review-title">'
                + '<i class="fas fa-circle-check me-1"></i>'
                + 'Sẵn sàng tái sử dụng</div>'
            )
            .removeClass('d-none');
    }

    function toastSuccess(message) {
        if (typeof toastr !== 'undefined') {
            toastr.success(message);
            return;
        }

        alert(message);
    }

    function toastError(message) {
        if (typeof toastr !== 'undefined') {
            toastr.error(message);
            return;
        }

        alert(message);
    }

    function toastWarning(message, title) {
        if (typeof toastr !== 'undefined') {
            toastr.warning(message, title || '');
            return;
        }

        alert(message);
    }

    function reloadCurrentSessions(options) {
        options = options || {};

        loadSessions({
            page: options.page || sessionCurrentPage || 1,
            per_page: sessionPerPage,
            date_from: $('#filterDateFrom').val(),
            date_to: $('#filterDateTo').val(),
            ip: $('#filterIP').val()
        }, options);
    }

    // ═══════════════════════════════════════════════════════
    // 1. LOAD STATS
    // ═══════════════════════════════════════════════════════
    function loadStats() {
        $.get('/admin/advise/stats', function (res) {
            $('#statSessions').text(
                Number(res.total_sessions || 0).toLocaleString()
            );
            $('#statMessages').text(
                Number(res.total_messages || 0).toLocaleString()
            );
            $('#statUserMsg').text(
                Number(res.user_messages || 0).toLocaleString()
            );
            $('#statBotMsg').text(
                Number(res.bot_messages || 0).toLocaleString()
            );
            $('#statVoice').text(
                Number(res.voice_messages || 0).toLocaleString()
            );
            $('#statApprovedAnswers').text(
                Number(res.approved_answers || 0).toLocaleString()
            );

            updateTicketStatsFromStatsResponse(res);
        }).fail(function () {
            console.error('Không thể tải thống kê.');
            toastError('Không thể tải thống kê.');
        });
    }

    function normalizePendingCount(value) {
        const count = Number(value);

        if (!Number.isFinite(count) || count <= 0) {
            return 0;
        }

        return Math.floor(count);
    }

    function updatePendingTicketUI(value) {
        const pendingTickets = normalizePendingCount(value);
        const hasPending = pendingTickets > 0;

        $('#statPendingTickets').text(
            pendingTickets.toLocaleString()
        );

        $('#pendingTicketLabel').text(
            hasPending
                ? 'Ticket chờ xử lý'
                : 'Không có ticket chờ'
        );

        $('#ticketPendingAlertCount').text(
            pendingTickets.toLocaleString()
        );

        $('#tabTicketBadge')
            .text(pendingTickets)
            .toggleClass('d-none', !hasPending);

        $('#pendingTicketCard')
            .toggleClass('has-ticket', hasPending)
            .toggleClass('is-empty', !hasPending)
            .attr(
                'title',
                hasPending
                    ? 'Nhấn để xem ticket đang chờ xử lý'
                    : 'Hiện không có ticket chờ xử lý'
            )
            .attr('aria-disabled', hasPending ? 'false' : 'true');

        $('#ticketPendingAlert')
            .toggleClass('d-none', !hasPending)
            .attr('aria-hidden', hasPending ? 'false' : 'true');

        if (!hasPending) {
            $('#ticketPendingAlert').stop(true, true);
        }

        return pendingTickets;
    }

    function updateTicketStatsFromStatsResponse(res) {
        const pendingTickets = updatePendingTicketUI(
            res.pending_tickets
        );

        /*
         * Không phát thông báo ở lần tải thống kê đầu tiên.
         * Thông báo ticket mới chỉ do checkNewPendingTickets() xử lý,
         * tránh báo lặp hoặc báo ticket cũ.
         */
        if (lastPendingTicketCount === null) {
            lastPendingTicketCount = pendingTickets;
        }
    }


    // ═══════════════════════════════════════════════════════
    // 2. DANH SÁCH PHIÊN + PREVIEW HỘI THOẠI
    // ═══════════════════════════════════════════════════════
    let sessionRows = [];

    function loadSessions(params, options) {
        params = params || {};
        options = options || {};

        const requestedPage = Number(params.page || sessionCurrentPage || 1);
        params.page = requestedPage;
        params.per_page = params.per_page || sessionPerPage;

        if (!options.silent) {
            $('#sessionList').html(
                '<div class="conversation-list-state">'
                + '<i class="fas fa-spinner fa-spin me-2"></i>'
                + 'Đang tải phiên chat...'
                + '</div>'
            );
        }

        $.get('/admin/advise/sessions', params, function (res) {
            if (res.success === false) {
                toastError(res.message || 'Không thể tải phiên chat.');
                return;
            }

            const pagination = res.data || {};
            sessionRows = Array.isArray(pagination.data)
                ? pagination.data
                : [];

            sessionCurrentPage = Number(pagination.current_page || 1);
            sessionLastPage = Number(pagination.last_page || 1);
            sessionTotal = Number(pagination.total || 0);

            renderSessionList(sessionRows);
            updateSessionPagination();

            if (!sessionRows.length) {
                currentSessionId = null;
                clearSessionPreview(
                    'Chưa có phiên chat phù hợp với điều kiện lọc.'
                );
                return;
            }

            const selectedStillExists = sessionRows.some(function (row) {
                return String(row.id) === String(currentSessionId);
            });

            if (!selectedStillExists) {
                currentSessionId = sessionRows[0].id;
                options.reloadPreview = true;
            }

            markSelectedSession(currentSessionId);

            if (
                options.reloadPreview
                || $('#sessionPreviewContent').hasClass('d-none')
            ) {
                loadSessionPreview(currentSessionId);
            }
        }).fail(function () {
            sessionRows = [];
            sessionTotal = 0;
            $('#sessionListCount').text('0');
            $('#sessionList').html(
                '<div class="conversation-list-state text-danger">'
                + '<i class="fas fa-circle-exclamation me-2"></i>'
                + 'Không thể tải danh sách phiên chat.'
                + '</div>'
            );
            updateSessionPagination();
            clearSessionPreview('Không thể tải dữ liệu phiên chat.');
            toastError('Không thể tải dữ liệu phiên chat.');
        });
    }

    function updateSessionPagination() {
        $('#sessionListCount').text(sessionTotal.toLocaleString());
        $('#sessionPageInfo').text(
            sessionTotal > 0
                ? 'Trang ' + sessionCurrentPage + '/' + sessionLastPage
                    + ' · ' + sessionTotal.toLocaleString() + ' phiên'
                : '0 phiên'
        );

        $('#btnSessionPrev').prop('disabled', sessionCurrentPage <= 1);
        $('#btnSessionNext').prop(
            'disabled',
            sessionCurrentPage >= sessionLastPage || sessionTotal === 0
        );
    }

    function renderSessionList(data) {
        if (!data.length) {
            $('#sessionList').html(
                '<div class="conversation-list-state">'
                + '<i class="fas fa-comments me-2"></i>'
                + 'Chưa có phiên chat.'
                + '</div>'
            );
            return;
        }

        let html = '';

        data.forEach(function (row) {
            const lastMessage = String(
                row.last_message || 'Chưa có tin nhắn'
            ).replace(/\s+/g, ' ').trim();

            const sender = row.last_message_role === 'assistant'
                ? 'Bot'
                : (row.last_message_role === 'user' ? 'Người dùng' : '');

            const pendingCount = Number(row.pending_ticket_count || 0);
            const ticketCount = Number(row.ticket_count || 0);

            html += `
                <div class="conversation-item"
                     data-id="${row.id}"
                     tabindex="0"
                     role="button"
                     aria-label="Xem phiên chat ${row.id}">
                    <div class="conversation-item-top">
                        <div class="conversation-item-title">
                            <span class="conversation-avatar">
                                <i class="fas fa-comment-dots"></i>
                            </span>
                            <span>
                                <strong>Phiên #${row.id}</strong>
                                <small>${escapeHtml(
                                    row.ip_address || 'Không có IP'
                                )}</small>
                            </span>
                        </div>
                        <button type="button"
                                class="conversation-delete btn-delete-session"
                                data-id="${row.id}"
                                title="Xóa phiên">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>

                    <div class="conversation-snippet">
                        ${sender
                            ? '<span class="conversation-sender">'
                                + escapeHtml(sender) + ':</span> '
                            : ''}
                        ${escapeHtml(lastMessage)}
                    </div>

                    <div class="conversation-item-bottom">
                        <span>
                            <i class="fas fa-message me-1"></i>
                            ${Number(row.message_count || 0)} tin
                        </span>
                        ${ticketCount > 0
                            ? `<span class="${pendingCount > 0
                                ? 'conversation-ticket pending'
                                : 'conversation-ticket'}">
                                <i class="fas fa-headset me-1"></i>
                                ${pendingCount > 0
                                    ? pendingCount + ' chờ'
                                    : ticketCount + ' ticket'}
                               </span>`
                            : ''}
                        <span class="ms-auto">
                            ${formatDatetime(
                                row.last_message_at || row.last_active_at
                            )}
                        </span>
                    </div>
                </div>
            `;
        });

        $('#sessionList').html(html);
    }

    function markSelectedSession(id) {
        $('.conversation-item').removeClass('active');
        $('.conversation-item[data-id="' + id + '"]').addClass('active');
    }

    function clearSessionPreview(message) {
        $('#sessionPreviewContent').addClass('d-none');
        $('#sessionPreviewEmpty')
            .removeClass('d-none')
            .find('.session-preview-empty-text')
            .text(message || 'Chọn một phiên ở bên trái để xem hội thoại.');
    }

    function showSessionPreviewLoading() {
        $('#sessionPreviewEmpty').addClass('d-none');
        $('#sessionPreviewContent').removeClass('d-none');
        $('#metaId,#metaThread,#metaIP,#metaStart,#metaLast,#metaUser')
            .text('—');
        $('#metaMessageCount,#metaTicketCount').text('0');
        $('#chatBox').html(
            '<div class="text-center text-muted py-5">'
            + '<i class="fas fa-spinner fa-spin me-2"></i>'
            + 'Đang tải hội thoại...'
            + '</div>'
        );
    }

    function loadSessionPreview(id) {
        if (!id) {
            clearSessionPreview();
            return;
        }

        currentSessionId = id;
        markSelectedSession(id);
        showSessionPreviewLoading();

        $.get('/admin/advise/sessions/' + id, function (res) {
            if (res.status !== 'success') {
                clearSessionPreview('Không thể tải chi tiết phiên chat.');
                toastError(res.message || 'Không thể tải chi tiết phiên.');
                return;
            }

            fillMeta(res.session || {});
            renderChat(res.messages || [], '#chatBox');
        }).fail(function () {
            clearSessionPreview('Không thể tải chi tiết phiên chat.');
            toastError('Lỗi máy chủ khi tải hội thoại.');
        });
    }

    function fillMeta(session) {
        $('#metaId').text(session.id || '—');
        $('#metaThread')
            .text(session.thread_id || '—')
            .attr('title', session.thread_id || '');
        $('#metaIP').text(session.ip_address || '—');
        $('#metaStart').text(formatDatetime(session.started_at));
        $('#metaLast').text(formatDatetime(session.last_active_at));
        $('#metaUser').text(session.user_name || 'Khách');
        $('#metaMessageCount').text(
            Number(session.message_count || 0).toLocaleString()
        );
        $('#metaTicketCount').text(
            Number(session.ticket_count || 0).toLocaleString()
        );

        if (Number(session.pending_ticket_count || 0) > 0) {
            $('#metaTicketCount')
                .addClass('text-warning')
                .attr(
                    'title',
                    session.pending_ticket_count + ' ticket đang chờ'
                );
        } else {
            $('#metaTicketCount')
                .removeClass('text-warning')
                .removeAttr('title');
        }
    }

    function renderChat(messages, boxSelector) {
        const $box = $(boxSelector);

        if (!messages || messages.length === 0) {
            $box.html(
                '<div class="text-center text-muted py-5">'
                + '<i class="fas fa-comment-slash d-block mb-2"></i>'
                + 'Phiên này chưa có tin nhắn.'
                + '</div>'
            );
            return;
        }

        let html = '';

        messages.forEach(function (msg) {
            const isUser = msg.role === 'user';
            const icon = isUser
                ? '<i class="fas fa-user"></i>'
                : '<i class="fas fa-robot"></i>';
            const voice = msg.input_type === 'voice'
                ? ' <span class="badge bg-warning text-dark" '
                    + 'style="font-size:.6rem">'
                    + '<i class="fas fa-microphone"></i></span>'
                : '';

            const libraryId = Number(msg.answer_library_id || 0);
            const canUseAsLibrary =
                Number(msg.can_answer_library || 0) === 1;
            const isInLibrary = libraryId > 0;
            const useCount = Number(msg.answer_use_count || 0);
            const review = normalizeLibraryReview(
                msg.answer_library_review
            );
            const isBlocked = !review.can_save;
            const hasWarnings = review.warnings.length > 0;

            let libraryControl = '';

            if (!isUser && canUseAsLibrary) {
                const controlClass = isBlocked
                    ? ' blocked'
                    : (
                        isInLibrary || review.reuse_ready
                            ? ' approved'
                            : (hasWarnings ? ' warning' : '')
                    );

                libraryControl = `
                    <label class="answer-library-control${controlClass}">
                        <input type="checkbox"
                               class="answer-library-toggle"
                               data-library-id="${libraryId}"
                               data-message-id="${Number(msg.id || 0)}"
                               ${isInLibrary ? 'checked' : ''}
                               ${isBlocked ? 'disabled' : ''}>
                        <span>
                            ${isInLibrary
                                ? 'Đang có trong kho câu hỏi'
                                : (isBlocked
                                    ? 'Chưa thể lưu vào kho'
                                    : 'Lưu câu trả lời này vào kho')}
                        </span>
                        <small>
                            ${useCount > 0
                                ? 'Đã tái sử dụng ' + useCount + ' lần. '
                                : ''}
                            ${escapeHtml(reviewMessage(review))}
                        </small>
                    </label>
                `;
            }

            html += `
                <div class="chat-message ${isUser ? 'chat-user' : 'chat-bot'}">
                    <div class="chat-avatar">${icon}</div>
                    <div class="chat-bubble-wrap">
                        <div class="chat-meta">
                            <span class="chat-role">
                                ${isUser ? 'Người dùng' : 'Bot'}${voice}
                            </span>
                            <span class="chat-time">
                                ${formatDatetime(msg.sent_at)}
                            </span>
                            <button class="btn-msg-delete"
                                    data-id="${msg.id}"
                                    title="Xóa tin nhắn">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="chat-bubble">
                            ${escapeHtmlWithBreaks(msg.content)}
                        </div>
                        ${libraryControl}
                    </div>
                </div>
            `;
        });

        $box.html(html);

        const element = document.querySelector(boxSelector);
        if (element) {
            element.scrollTop = element.scrollHeight;
        }
    }

    function updateLibraryMembership(
        libraryId,
        messageId,
        addToLibrary,
        $checkbox
    ) {
        $checkbox.prop('disabled', true);

        const target = libraryId > 0
            ? 'library-' + libraryId
            : 'message-' + messageId;

        $.ajax({
            url: '/admin/advise/tickets/' + target + '/answer',
            type: 'POST',
            data: {
                library_action: libraryId > 0
                    ? 'approval'
                    : 'toggle_ai',
                approved: addToLibrary ? 1 : 0,
                _token: getCsrfToken()
            },
            success: function (res) {
                if (!res.success) {
                    $checkbox.prop('checked', !addToLibrary);
                    toastError(
                        res.message || 'Không thể cập nhật kho câu hỏi.'
                    );
                    return;
                }

                const savedReview = res.data
                    && res.data.review
                    ? normalizeLibraryReview(res.data.review)
                    : null;

                if (savedReview && !savedReview.reuse_ready) {
                    toastWarning(
                        res.message || reviewMessage(savedReview),
                        'Kho câu hỏi'
                    );
                } else {
                    toastSuccess(
                        res.message || 'Đã cập nhật kho câu hỏi.'
                    );
                }

                loadStats();

                if (currentSessionId) {
                    loadSessionPreview(currentSessionId);
                }

                if ($('#pane-library').hasClass('active')) {
                    loadAnswerLibrary(
                        libraryCurrentPage || 1,
                        { silent: true, reloadPreview: true }
                    );
                }
            },
            error: function (xhr) {
                $checkbox.prop('checked', !addToLibrary);
                const response = xhr.responseJSON || {};
                const reasons = response.data
                    && Array.isArray(response.data.blocking_reasons)
                    ? response.data.blocking_reasons.join(' ')
                    : '';

                toastError(
                    reasons
                    || response.message
                    || 'Không thể cập nhật kho câu hỏi.'
                );
            },
            complete: function () {
                $checkbox.prop('disabled', false);
            }
        });
    }

    $(document).on('change', '.answer-library-toggle', function () {
        const $checkbox = $(this);
        const libraryId = Number(
            $checkbox.data('library-id') || 0
        );
        const messageId = Number(
            $checkbox.data('message-id') || 0
        );

        if (!libraryId && !messageId) {
            $checkbox.prop('checked', false);
            return;
        }

        updateLibraryMembership(
            libraryId,
            messageId,
            $checkbox.is(':checked'),
            $checkbox
        );
    });

    // ═══════════════════════════════════════════════════════
    // 3. LỌC VÀ PHÂN TRANG PHIÊN
    // ═══════════════════════════════════════════════════════
    $('#btnFilter').on('click', function () {
        currentSessionId = null;
        sessionCurrentPage = 1;
        reloadCurrentSessions({ page: 1, reloadPreview: true });
    });

    $('#btnResetFilter').on('click', function () {
        $('#filterDateFrom,#filterDateTo,#filterIP').val('');
        currentSessionId = null;
        sessionCurrentPage = 1;
        reloadCurrentSessions({ page: 1, reloadPreview: true });
    });

    $('#btnSessionPrev').on('click', function () {
        if (sessionCurrentPage > 1) {
            currentSessionId = null;
            reloadCurrentSessions({
                page: sessionCurrentPage - 1,
                reloadPreview: true
            });
        }
    });

    $('#btnSessionNext').on('click', function () {
        if (sessionCurrentPage < sessionLastPage) {
            currentSessionId = null;
            reloadCurrentSessions({
                page: sessionCurrentPage + 1,
                reloadPreview: true
            });
        }
    });

    // ═══════════════════════════════════════════════════════
    // 4. CHỌN PHIÊN VÀ XEM PREVIEW
    // ═══════════════════════════════════════════════════════
    $(document).on('click', '.conversation-item', function (event) {
        if ($(event.target).closest('.btn-delete-session').length) {
            return;
        }

        loadSessionPreview($(this).data('id'));
    });

    $(document).on('keydown', '.conversation-item', function (event) {
        if (event.key !== 'Enter' && event.key !== ' ') {
            return;
        }

        event.preventDefault();
        loadSessionPreview($(this).data('id'));
    });

    // ═══════════════════════════════════════════════════════
    // 5. XÓA TIN NHẮN
    // ═══════════════════════════════════════════════════════
    $(document).on('click', '.btn-msg-delete', function () {
        const msgId = $(this).data('id');

        Swal.fire({
            title: 'Xóa tin nhắn?',
            text: 'Tin nhắn sẽ bị xóa vĩnh viễn.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Xóa',
            cancelButtonText: 'Hủy'
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            $.ajax({
                url: '/admin/advise/messages/' + msgId,
                type: 'DELETE',
                success: function (res) {
                    if (!res.success) {
                        toastError(res.message || 'Không thể xóa.');
                        return;
                    }

                    toastSuccess('Đã xóa tin nhắn.');
                    loadStats();
                    reloadCurrentSessions({ reloadPreview: true });
                },
                error: function () {
                    toastError('Lỗi máy chủ.');
                }
            });
        });
    });

    // ═══════════════════════════════════════════════════════
    // 6. XÓA PHIÊN
    // ═══════════════════════════════════════════════════════
    $('#btnDeleteSessionPreview').on('click', function () {
        if (currentSessionId) {
            confirmDeleteSession(currentSessionId);
        }
    });

    $(document).on('click', '.btn-delete-session', function (event) {
        event.preventDefault();
        event.stopPropagation();
        confirmDeleteSession($(this).data('id'));
    });

    function confirmDeleteSession(id) {
        Swal.fire({
            title: 'Xóa phiên chat?',
            text: 'Toàn bộ tin nhắn và dữ liệu phiên sẽ bị xóa.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Xóa',
            cancelButtonText: 'Hủy'
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            $.ajax({
                url: '/admin/advise/sessions/' + id,
                type: 'DELETE',
                success: function (res) {
                    if (!res.success) {
                        toastError(res.message || 'Không thể xóa.');
                        return;
                    }

                    if (String(currentSessionId) === String(id)) {
                        currentSessionId = null;
                    }

                    toastSuccess(res.message || 'Đã xóa phiên.');
                    loadStats();
                    reloadCurrentSessions({ reloadPreview: true });
                },
                error: function () {
                    toastError('Lỗi máy chủ.');
                }
            });
        });
    }

    // ═══════════════════════════════════════════════════════
    // 7. TICKET STATS
    // ═══════════════════════════════════════════════════════
    function ticketBadge(status) {
        if (status === 'pending')  return '<span class="ticket-badge tb-pending">Đang chờ</span>';
        if (status === 'answered') return '<span class="ticket-badge tb-answered">Đã trả lời</span>';
        return '<span class="ticket-badge tb-closed">Đã đóng</span>';
    }

    function updateTicketStats(res) {
        lastPendingTicketCount = updatePendingTicketUI(
            res.pending_tickets
        );
    }


    // ═══════════════════════════════════════════════════════
    // 8. DANH SÁCH TICKET + PREVIEW/TRẢ LỜI
    // ═══════════════════════════════════════════════════════
    let ticketRows = [];

    function loadTickets(page, options) {
        options = options || {};
        const silent = options.silent === true;

        page = Number(page || ticketCurrentPage || 1);
        ticketCurrentPage = page;

        const status = $('#ticketStatusFilter').val() || '';
        const keyword = $('#ticketKeyword').val() || '';

        if (!silent) {
            $('#ticketList').html(
                '<div class="ticket-list-state">'
                + '<i class="fas fa-spinner fa-spin me-2"></i>'
                + 'Đang tải yêu cầu tư vấn...'
                + '</div>'
            );
        }

        $.get('/admin/advise/tickets', {
            page: page,
            per_page: ticketPerPage,
            status: status,
            keyword: keyword
        }, function (res) {
            if (!res.success) {
                toastError(res.message || 'Không thể tải danh sách ticket.');
                return;
            }

            const pagination = res.data || {};
            ticketRows = Array.isArray(pagination.data)
                ? pagination.data
                : [];

            ticketCurrentPage = Number(pagination.current_page || 1);
            ticketLastPage = Number(pagination.last_page || 1);

            renderTicketList(ticketRows);
            updateTicketPagination(pagination);

            if (!ticketRows.length) {
                currentTicketId = null;
                clearTicketPreview(
                    'Chưa có yêu cầu tư vấn phù hợp với điều kiện lọc.'
                );
                return;
            }

            const selectedStillExists = ticketRows.some(function (ticket) {
                return String(ticket.id) === String(currentTicketId);
            });

            if (!selectedStillExists) {
                currentTicketId = ticketRows[0].id;
                options.reloadPreview = true;
            }

            markSelectedTicket(currentTicketId);

            if (
                options.reloadPreview
                || $('#ticketPreviewContent').hasClass('d-none')
            ) {
                loadTicketPreview(currentTicketId);
            }
        }).fail(function () {
            if (!silent) {
                $('#ticketList').html(
                    '<div class="ticket-list-state text-danger">'
                    + '<i class="fas fa-circle-exclamation me-2"></i>'
                    + 'Không thể tải yêu cầu tư vấn.'
                    + '</div>'
                );
            }

            clearTicketPreview('Không thể tải dữ liệu ticket.');
        });
    }

    function updateTicketPagination(pagination) {
        const total = Number(pagination.total || 0);

        $('#ticketListCount').text(total.toLocaleString());
        $('#ticketPageInfo').text(
            total > 0
                ? 'Trang ' + ticketCurrentPage + '/' + ticketLastPage
                    + ' · ' + total.toLocaleString() + ' ticket'
                : '0 ticket'
        );

        $('#btnTicketPrev').prop('disabled', ticketCurrentPage <= 1);
        $('#btnTicketNext').prop(
            'disabled',
            ticketCurrentPage >= ticketLastPage || total === 0
        );
    }

    function renderTicketList(tickets) {
        if (!tickets.length) {
            $('#ticketList').html(
                '<div class="ticket-list-state">'
                + '<i class="fas fa-inbox me-2"></i>'
                + 'Chưa có yêu cầu tư vấn.'
                + '</div>'
            );
            return;
        }

        let html = '';

        tickets.forEach(function (ticket) {
            const question = String(ticket.question || '')
                .replace(/\s+/g, ' ')
                .trim();

            html += `
                <div class="ticket-item"
                     data-id="${ticket.id}"
                     tabindex="0"
                     role="button"
                     aria-label="Xem ticket ${escapeHtml(
                         ticket.ticket_code || ticket.id
                     )}">
                    <div class="ticket-item-top">
                        <div class="min-w-0">
                            <strong>${escapeHtml(
                                ticket.ticket_code || ('Ticket #' + ticket.id)
                            )}</strong>
                            <small>Phiên #${ticket.session_id || '—'}</small>
                        </div>
                        ${ticketBadge(ticket.status)}
                    </div>

                    <div class="ticket-item-question">
                        ${escapeHtml(question || 'Không có nội dung câu hỏi')}
                    </div>

                    <div class="ticket-item-bottom">
                        <span>
                            <i class="fas fa-clock me-1"></i>
                            ${escapeHtml(ticket.created_at_text || '—')}
                        </span>
                        <span class="ms-auto">
                            ${ticket.admin_name
                                ? '<i class="fas fa-user-check me-1"></i>'
                                    + escapeHtml(ticket.admin_name)
                                : '<i class="fas fa-user-clock me-1"></i>Chưa xử lý'}
                        </span>
                    </div>
                </div>
            `;
        });

        $('#ticketList').html(html);
    }

    function markSelectedTicket(id) {
        $('.ticket-item').removeClass('active');
        $('.ticket-item[data-id="' + id + '"]').addClass('active');
    }

    function clearTicketPreview(message) {
        $('#ticketPreviewContent').addClass('d-none');
        $('#ticketPreviewEmpty')
            .removeClass('d-none')
            .find('.ticket-preview-empty-text')
            .text(message || 'Chọn một ticket ở bên trái để xem chi tiết.');
    }

    function showTicketPreviewLoading() {
        $('#ticketPreviewEmpty').addClass('d-none');
        $('#ticketPreviewContent').removeClass('d-none');
        $('#ticketPreviewCode').text('Đang tải...');
        $('#ticketPreviewStatus').html('');
        $('#ticketPreviewSession').text('');
        $('#ticketChatBox').html(
            '<div class="text-center text-muted py-4">'
            + '<i class="fas fa-spinner fa-spin me-2"></i>'
            + 'Đang tải lịch sử hội thoại...'
            + '</div>'
        );
        $('#ticketPreviewQuestion').text('');
        $('#ticketPreviewBotNote').text('');
        $('#ticketPreviewOldAnswer').text('');
        $('#ticketStaffAnswer').val('');
        $('#ticketUseAsSample').prop('checked', false);
    }

    function loadTicketPreview(id) {
        if (!id) {
            clearTicketPreview();
            return;
        }

        currentTicketId = id;
        markSelectedTicket(id);
        showTicketPreviewLoading();

        $.get('/admin/advise/tickets/' + id, function (res) {
            if (!res.success) {
                clearTicketPreview(res.message || 'Không tìm thấy ticket.');
                return;
            }

            const ticket = res.data || {};

            $('#ticketCurrentId').val(ticket.id || '');
            $('#ticketPreviewCode').text(
                ticket.ticket_code || ('Ticket #' + ticket.id)
            );
            $('#ticketPreviewStatus').html(ticketBadge(ticket.status));
            $('#ticketPreviewSession').html(
                '<span><i class="fas fa-comments me-1"></i>Phiên #'
                + escapeHtml(ticket.session_id || '—') + '</span>'
                + '<span><i class="fas fa-network-wired me-1"></i>'
                + escapeHtml(ticket.ip_address || '—') + '</span>'
                + '<span><i class="fas fa-clock me-1"></i>'
                + escapeHtml(ticket.created_at_text || '—') + '</span>'
                + '<span><i class="fas fa-user-check me-1"></i>'
                + escapeHtml(ticket.admin_name || 'Chưa có admin') + '</span>'
            );

            $('#ticketPreviewQuestion').text(ticket.question || '');

            if (ticket.bot_note) {
                $('#ticketBotNoteWrap').removeClass('d-none');
                $('#ticketPreviewBotNote').text(ticket.bot_note);
            } else {
                $('#ticketBotNoteWrap').addClass('d-none');
            }

            if (ticket.staff_answer) {
                $('#ticketOldAnswerWrap').removeClass('d-none');
                $('#ticketPreviewOldAnswer').text(ticket.staff_answer);
            } else {
                $('#ticketOldAnswerWrap').addClass('d-none');
            }

            $('#ticketStaffAnswer').val(ticket.staff_answer || '');
            $('#ticketUseAsSample').prop(
                'checked',
                Number(ticket.answer_is_approved || 0) === 1
            );

            const isClosed = ticket.status === 'closed';
            $('#ticketStaffAnswer,#ticketUseAsSample')
                .prop('disabled', isClosed);
            $('#btnSubmitTicketAnswer')
                .prop('disabled', isClosed)
                .toggleClass('d-none', isClosed);
            $('#btnCloseTicket')
                .prop('disabled', isClosed)
                .toggleClass('d-none', isClosed);

            if (ticket.session_id) {
                $.get(
                    '/admin/advise/sessions/' + ticket.session_id,
                    function (sessionResponse) {
                        if (sessionResponse.status === 'success') {
                            renderChat(
                                sessionResponse.messages || [],
                                '#ticketChatBox'
                            );
                            return;
                        }

                        $('#ticketChatBox').html(
                            '<div class="text-muted text-center py-3">'
                            + 'Không tải được lịch sử hội thoại.'
                            + '</div>'
                        );
                    }
                ).fail(function () {
                    $('#ticketChatBox').html(
                        '<div class="text-muted text-center py-3">'
                        + 'Lỗi tải lịch sử hội thoại.'
                        + '</div>'
                    );
                });
            } else {
                $('#ticketChatBox').html(
                    '<div class="text-muted text-center py-3">'
                    + 'Ticket không liên kết với phiên chat.'
                    + '</div>'
                );
            }
        }).fail(function () {
            clearTicketPreview('Không thể tải chi tiết ticket.');
            toastError('Không thể tải chi tiết ticket.');
        });
    }

    function submitTicketAnswer() {
        const id = $('#ticketCurrentId').val();
        const answer = $('#ticketStaffAnswer').val().trim();
        const useAsSample = $('#ticketUseAsSample').is(':checked') ? 1 : 0;

        if (!id) {
            toastError('Không xác định được ticket.');
            return;
        }

        if (!answer) {
            toastWarning('Vui lòng nhập nội dung trả lời.');
            $('#ticketStaffAnswer').focus();
            return;
        }

        $('#btnSubmitTicketAnswer')
            .prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin"></i> Đang gửi...');

        $.ajax({
            url: '/admin/advise/tickets/' + id + '/answer',
            type: 'POST',
            data: {
                staff_answer: answer,
                use_as_sample: useAsSample,
                _token: getCsrfToken()
            },
            success: function (res) {
                if (!res.success) {
                    toastError(res.message || 'Không thể gửi trả lời.');
                    return;
                }

                toastSuccess(res.message || 'Đã gửi trả lời.');
                loadStats();
                checkNewPendingTickets();
                loadTickets(ticketCurrentPage, {
                    silent: true,
                    reloadPreview: true
                });
            },
            error: function (xhr) {
                toastError(
                    xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'Không thể gửi trả lời.'
                );
            },
            complete: function () {
                $('#btnSubmitTicketAnswer')
                    .prop('disabled', false)
                    .html(
                        '<i class="fas fa-paper-plane me-1"></i>'
                        + 'Gửi trả lời'
                    );
            }
        });
    }

    function closeTicket() {
        const id = $('#ticketCurrentId').val();

        if (!id) {
            toastError('Không xác định được ticket.');
            return;
        }

        Swal.fire({
            title: 'Đóng yêu cầu tư vấn?',
            text: 'Ticket sẽ chuyển sang trạng thái đã đóng.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Đóng ticket',
            cancelButtonText: 'Hủy'
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            $('#btnCloseTicket')
                .prop('disabled', true)
                .html('<i class="fas fa-spinner fa-spin"></i> Đang đóng...');

            $.ajax({
                url: '/admin/advise/tickets/' + id + '/close',
                type: 'POST',
                data: { _token: getCsrfToken() },
                success: function (res) {
                    if (!res.success) {
                        toastError(res.message || 'Không thể đóng ticket.');
                        return;
                    }

                    toastSuccess(res.message || 'Đã đóng ticket.');
                    loadStats();
                    checkNewPendingTickets();
                    loadTickets(ticketCurrentPage, {
                        silent: true,
                        reloadPreview: true
                    });
                },
                error: function () {
                    toastError('Không thể đóng ticket.');
                },
                complete: function () {
                    $('#btnCloseTicket')
                        .prop('disabled', false)
                        .html('<i class="fas fa-times me-1"></i>Đóng ticket');
                }
            });
        });
    }

    // ═══════════════════════════════════════════════════════
    // 9. KHO CÂU HỎI - CÂU TRẢ LỜI
    // ═══════════════════════════════════════════════════════
    function clearLibraryPreview(message) {
        currentLibraryId = null;
        $('#libraryPreviewContent').addClass('d-none');
        $('#libraryPreviewEmpty')
            .removeClass('d-none')
            .find('.library-preview-empty-text')
            .text(
                message
                || 'Chọn một câu hỏi ở bên trái để xem và chỉnh sửa.'
            );
    }

    function showLibraryPreviewLoading() {
        $('#libraryPreviewEmpty').addClass('d-none');
        $('#libraryPreviewContent').removeClass('d-none');
        $('#libraryPreviewQuestion').val('');
        $('#libraryPreviewAnswer').val('');
        $('#libraryPreviewMeta').html(
            '<span><i class="fas fa-spinner fa-spin me-1"></i>'
            + 'Đang tải...</span>'
        );
    }

    function markSelectedLibrary(id) {
        $('.library-item').removeClass('active');
        $('.library-item[data-id="' + id + '"]').addClass('active');
    }

    function updateLibraryPagination() {
        $('#libraryListCount').text(libraryTotal.toLocaleString());
        $('#libraryPageInfo').text(
            libraryTotal > 0
                ? 'Trang ' + libraryCurrentPage + '/'
                    + libraryLastPage + ' · '
                    + libraryTotal.toLocaleString() + ' câu'
                : '0 câu'
        );

        $('#btnLibraryPrev').prop(
            'disabled',
            libraryCurrentPage <= 1
        );
        $('#btnLibraryNext').prop(
            'disabled',
            libraryCurrentPage >= libraryLastPage
                || libraryTotal === 0
        );
    }

    function renderAnswerLibraryList(rows) {
        if (!rows.length) {
            $('#libraryList').html(
                '<div class="library-list-state">'
                + '<i class="fas fa-book-open me-2"></i>'
                + 'Chưa có câu hỏi phù hợp.'
                + '</div>'
            );
            return;
        }

        let html = '';

        rows.forEach(function (row) {
            const sourceLabel = row.source_type === 'staff'
                ? 'Nhân viên'
                : 'AI';

            html += `
                <div class="library-item"
                     data-id="${row.id}"
                     tabindex="0"
                     role="button"
                     aria-label="Xem câu hỏi ${row.id}">
                    <div class="library-item-top">
                        <span class="library-source
                                     ${row.source_type === 'staff'
                                         ? 'staff'
                                         : 'ai'}">
                            <i class="fas
                                      ${row.source_type === 'staff'
                                          ? 'fa-user-check'
                                          : 'fa-robot'}"></i>
                            ${sourceLabel}
                        </span>
                    </div>

                    <div class="library-item-question">
                        ${escapeHtml(row.question || '')}
                    </div>

                    <div class="library-item-answer">
                        ${escapeHtml(
                            String(row.answer || '')
                                .replace(/\s+/g, ' ')
                                .trim()
                        )}
                    </div>

                    <div class="library-item-bottom">
                        <span>
                            <i class="fas fa-rotate me-1"></i>
                            ${Number(row.use_count || 0)} lần dùng
                        </span>
                        <span class="ms-auto">
                            ${escapeHtml(
                                row.updated_at_text
                                || row.created_at_text
                                || ''
                            )}
                        </span>
                    </div>
                </div>
            `;
        });

        $('#libraryList').html(html);
    }

    function loadAnswerLibrary(page, options) {
        options = options || {};
        page = Number(page || libraryCurrentPage || 1);

        if (!options.silent) {
            $('#libraryList').html(
                '<div class="library-list-state">'
                + '<i class="fas fa-spinner fa-spin me-2"></i>'
                + 'Đang tải kho câu hỏi...'
                + '</div>'
            );
        }

        $.get('/admin/advise/sessions', {
            answer_library: 1,
            page: page,
            per_page: libraryPerPage,
            source_type: $('#librarySourceFilter').val() || '',
            keyword: $('#libraryKeyword').val() || ''
        }, function (res) {
            if (!res.success) {
                toastError(
                    res.message || 'Không thể tải kho câu trả lời.'
                );
                return;
            }

            const pagination = res.data || {};
            const rows = Array.isArray(pagination.data)
                ? pagination.data
                : [];

            libraryCurrentPage = Number(
                pagination.current_page || 1
            );
            libraryLastPage = Number(
                pagination.last_page || 1
            );
            libraryTotal = Number(pagination.total || 0);

            renderAnswerLibraryList(rows);
            updateLibraryPagination();

            if (!rows.length) {
                clearLibraryPreview(
                    'Chưa có câu hỏi phù hợp với điều kiện lọc.'
                );
                return;
            }

            const selectedStillExists = rows.some(function (row) {
                return String(row.id) === String(currentLibraryId);
            });

            if (!selectedStillExists) {
                currentLibraryId = rows[0].id;
                options.reloadPreview = true;
            }

            markSelectedLibrary(currentLibraryId);

            if (
                options.reloadPreview
                || $('#libraryPreviewContent').hasClass('d-none')
            ) {
                loadAnswerLibraryPreview(currentLibraryId);
            }
        }).fail(function (xhr) {
            libraryTotal = 0;
            updateLibraryPagination();
            clearLibraryPreview('Không thể tải kho câu trả lời.');

            const message = xhr.responseJSON
                && xhr.responseJSON.message
                ? xhr.responseJSON.message
                : 'Không thể tải kho câu trả lời.';

            console.error('[Answer Library]', {
                status: xhr.status,
                response: xhr.responseText
            });

            toastError(message);
        });
    }

    function loadAnswerLibraryPreview(id) {
        if (!id) {
            clearLibraryPreview();
            return;
        }

        currentLibraryId = id;
        markSelectedLibrary(id);
        showLibraryPreviewLoading();

        $.get(
            '/admin/advise/sessions/library-' + id,
            function (res) {
                if (!res.success) {
                    clearLibraryPreview(
                        res.message || 'Không tìm thấy câu trả lời.'
                    );
                    return;
                }

                const row = res.data || {};

                $('#libraryCurrentId').val(row.id || '');
                $('#libraryPreviewTitle').text(
                    'Câu hỏi mẫu #' + (row.id || '—')
                );
                $('#libraryPreviewSource').html(
                    '<span class="library-source '
                    + (row.source_type === 'staff' ? 'staff' : 'ai')
                    + '"><i class="fas '
                    + (row.source_type === 'staff'
                        ? 'fa-user-check'
                        : 'fa-robot')
                    + ' me-1"></i>'
                    + escapeHtml(row.source_label || row.source_type)
                    + '</span>'
                );

                $('#libraryPreviewMeta').html(
                    '<span><i class="fas fa-clock me-1"></i>'
                    + escapeHtml(row.created_at_text || '—')
                    + '</span>'
                    + '<span><i class="fas fa-rotate me-1"></i>'
                    + Number(row.use_count || 0)
                    + ' lần tái sử dụng</span>'
                    + (row.ticket_code
                        ? '<span><i class="fas fa-ticket me-1"></i>'
                            + escapeHtml(row.ticket_code)
                            + '</span>'
                        : '')
                    + (row.approved_by_name
                        ? '<span><i class="fas fa-user-check me-1"></i>'
                            + escapeHtml(row.approved_by_name)
                            + '</span>'
                        : '')
                );

                $('#libraryPreviewQuestion').val(row.question || '');
                $('#libraryPreviewAnswer').val(row.answer || '');
                renderLibraryReviewBox(row.library_review || {});
            }
        ).fail(function () {
            clearLibraryPreview(
                'Không thể tải chi tiết câu trả lời.'
            );
            toastError('Không thể tải chi tiết câu trả lời.');
        });
    }

    function saveAnswerLibrary() {
        const id = Number($('#libraryCurrentId').val() || 0);
        const question = $('#libraryPreviewQuestion').val().trim();
        const answer = $('#libraryPreviewAnswer').val().trim();

        if (!id) {
            toastError('Không xác định được câu trả lời.');
            return;
        }

        if (!question || !answer) {
            toastWarning(
                'Câu hỏi và câu trả lời không được để trống.'
            );
            return;
        }

        $('#btnSaveLibrary')
            .prop('disabled', true)
            .html(
                '<i class="fas fa-spinner fa-spin me-1"></i>'
                + 'Đang lưu...'
            );

        $.ajax({
            url: '/admin/advise/tickets/library-' + id + '/answer',
            type: 'POST',
            data: {
                library_action: 'update',
                question: question,
                answer: answer,
                _token: getCsrfToken()
            },
            success: function (res) {
                if (!res.success) {
                    toastError(
                        res.message || 'Không thể cập nhật kho câu hỏi.'
                    );
                    return;
                }

                const review = res.data
                    && res.data.review
                    ? normalizeLibraryReview(res.data.review)
                    : null;

                if (review) {
                    renderLibraryReviewBox(review);
                }

                if (review && !review.reuse_ready) {
                    toastWarning(
                        res.message || reviewMessage(review),
                        'Đã lưu'
                    );
                } else {
                    toastSuccess(res.message || 'Đã cập nhật.');
                }

                loadStats();
                loadAnswerLibrary(
                    libraryCurrentPage,
                    { silent: true, reloadPreview: true }
                );

                if (currentSessionId) {
                    loadSessionPreview(currentSessionId);
                }
            },
            error: function (xhr) {
                toastError(
                    xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'Không thể cập nhật kho câu hỏi.'
                );
            },
            complete: function () {
                $('#btnSaveLibrary')
                    .prop('disabled', false)
                    .html(
                        '<i class="fas fa-save me-1"></i>'
                        + 'Lưu thay đổi'
                    );
            }
        });
    }

    function deleteAnswerLibrary() {
        const id = Number($('#libraryCurrentId').val() || 0);

        if (!id) {
            toastError('Không xác định được câu trả lời.');
            return;
        }

        Swal.fire({
            title: 'Xóa khỏi kho câu hỏi?',
            text: 'Câu trả lời này sẽ không còn được chatbot tái sử dụng.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Xóa',
            cancelButtonText: 'Hủy'
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            $.ajax({
                url: '/admin/advise/sessions/library-' + id,
                type: 'DELETE',
                success: function (res) {
                    if (!res.success) {
                        toastError(
                            res.message || 'Không thể xóa câu trả lời.'
                        );
                        return;
                    }

                    toastSuccess(res.message || 'Đã xóa.');
                    currentLibraryId = null;
                    loadStats();
                    loadAnswerLibrary(
                        libraryCurrentPage,
                        { reloadPreview: true }
                    );

                    if (currentSessionId) {
                        loadSessionPreview(currentSessionId);
                    }
                },
                error: function () {
                    toastError('Không thể xóa câu trả lời.');
                }
            });
        });
    }

    $(document).on('click', '.library-item', function () {
        loadAnswerLibraryPreview($(this).data('id'));
    });

    $(document).on('keydown', '.library-item', function (event) {
        if (event.key !== 'Enter' && event.key !== ' ') {
            return;
        }

        event.preventDefault();
        loadAnswerLibraryPreview($(this).data('id'));
    });

    $(document).on('click', '#btnFilterLibrary', function () {
        currentLibraryId = null;
        loadAnswerLibrary(1, { reloadPreview: true });
    });

    $(document).on(
        'change',
        '#librarySourceFilter',
        function () {
            currentLibraryId = null;
            loadAnswerLibrary(1, { reloadPreview: true });
        }
    );

    $(document).on('keydown', '#libraryKeyword', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            currentLibraryId = null;
            loadAnswerLibrary(1, { reloadPreview: true });
        }
    });

    $(document).on('click', '#btnReloadLibrary', function () {
        loadAnswerLibrary(
            libraryCurrentPage,
            { reloadPreview: true }
        );
    });

    $(document).on('click', '#btnLibraryPrev', function () {
        if (libraryCurrentPage > 1) {
            currentLibraryId = null;
            loadAnswerLibrary(
                libraryCurrentPage - 1,
                { reloadPreview: true }
            );
        }
    });

    $(document).on('click', '#btnLibraryNext', function () {
        if (libraryCurrentPage < libraryLastPage) {
            currentLibraryId = null;
            loadAnswerLibrary(
                libraryCurrentPage + 1,
                { reloadPreview: true }
            );
        }
    });

    $(document).on(
        'input',
        '#libraryPreviewQuestion,#libraryPreviewAnswer',
        function () {
            $('#libraryValidationBox')
                .removeClass('is-ready is-blocked')
                .addClass('is-warning')
                .html(
                    '<div class="library-review-title">'
                    + '<i class="fas fa-pen me-1"></i>'
                    + 'Nội dung đã thay đổi</div>'
                    + '<small>Nhấn Lưu thay đổi để hệ thống đánh giá lại.</small>'
                )
                .removeClass('d-none');
        }
    );

    $(document).on('click', '#btnSaveLibrary', saveAnswerLibrary);
    $(document).on('click', '#btnDeleteLibrary', deleteAnswerLibrary);

    // ═══════════════════════════════════════════════════════
    // 9. TICKET EVENTS VÀ TAB
    // ═══════════════════════════════════════════════════════
    $(document).on('click', '.ticket-item', function () {
        loadTicketPreview($(this).data('id'));
    });

    $(document).on('keydown', '.ticket-item', function (event) {
        if (event.key !== 'Enter' && event.key !== ' ') {
            return;
        }

        event.preventDefault();
        loadTicketPreview($(this).data('id'));
    });

    $(document).on('click', '#btnReloadTickets', function () {
        loadTickets(ticketCurrentPage, { reloadPreview: true });
    });

    $(document).on('click', '#btnFilterTickets', function () {
        currentTicketId = null;
        loadTickets(1, { reloadPreview: true });
    });

    $(document).on('change', '#ticketStatusFilter', function () {
        currentTicketId = null;
        loadTickets(1, { reloadPreview: true });
    });

    $(document).on('keydown', '#ticketKeyword', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            currentTicketId = null;
            loadTickets(1, { reloadPreview: true });
        }
    });

    $(document).on('click', '#btnTicketPrev', function () {
        if (ticketCurrentPage > 1) {
            currentTicketId = null;
            loadTickets(ticketCurrentPage - 1, { reloadPreview: true });
        }
    });

    $(document).on('click', '#btnTicketNext', function () {
        if (ticketCurrentPage < ticketLastPage) {
            currentTicketId = null;
            loadTickets(ticketCurrentPage + 1, { reloadPreview: true });
        }
    });

    $(document).on(
        'click',
        '#btnSubmitTicketAnswer',
        submitTicketAnswer
    );
    $(document).on('click', '#btnCloseTicket', closeTicket);

    function activateTab(tabName) {
        $('.cb-tab-btn').removeClass('active');
        $('.cb-tab-pane').removeClass('active');
        $('.cb-tab-btn[data-tab="' + tabName + '"]').addClass('active');
        $('#pane-' + tabName).addClass('active');

        if (tabName === 'tickets') {
            loadTickets(ticketCurrentPage || 1, { silent: true });
        }

        if (tabName === 'library') {
            loadAnswerLibrary(
                libraryCurrentPage || 1,
                { silent: true }
            );
        }
    }

    $(document).on('click', '.cb-tab-btn', function () {
        activateTab($(this).data('tab'));
    });

    function openPendingTickets() {
        const pendingTickets = normalizePendingCount(
            $('#statPendingTickets').text().replace(/[^0-9]/g, '')
        );

        if (pendingTickets <= 0) {
            return;
        }

        $('#ticketStatusFilter').val('pending');
        currentTicketId = null;
        activateTab('tickets');
        loadTickets(1, { reloadPreview: true });
    }

    $(document).on(
        'click',
        '#pendingTicketCard.has-ticket,#btnOpenPendingTickets',
        openPendingTickets
    );

    // ═══════════════════════════════════════════════════════
    // 11. CHECK TICKET MỚI GẦN REALTIME
    // ═══════════════════════════════════════════════════════
    function getNewestTicket(tickets) {
        if (!tickets || !tickets.length) return null;

        let newest = tickets[0];

        tickets.forEach(function (t) {
            if (Number(t.id || 0) > Number(newest.id || 0)) {
                newest = t;
            }
        });

        return newest;
    }

    function checkNewPendingTickets() {
        if (isCheckingPendingTickets) return;

        isCheckingPendingTickets = true;

        $.get('/admin/advise/tickets', {
            page: 1,
            status: 'pending',
            keyword: ''
        }, function (res) {
            if (!res.success) return;

            const pg = res.data || {};
            const pendingTickets = Array.isArray(pg.data)
                ? pg.data
                : [];
            const pendingTotal = normalizePendingCount(pg.total);

            const newestTicket = getNewestTicket(pendingTickets);
            const newestId = newestTicket ? Number(newestTicket.id || 0) : 0;

            updatePendingTicketUI(pendingTotal);

            // Lần đầu mở trang: chỉ ghi nhận mốc, không báo ồ ạt ticket cũ
            if (lastSeenPendingTicketId === null) {
                lastSeenPendingTicketId = newestId;
                lastPendingTicketCount = pendingTotal;
                return;
            }

            // Có ticket pending mới
            if (newestId > lastSeenPendingTicketId) {
                lastSeenPendingTicketId = newestId;
                lastPendingTicketCount = pendingTotal;

                const code = newestTicket && newestTicket.ticket_code
                    ? ' - ' + newestTicket.ticket_code
                    : '';

                toastWarning(
                    'Có ticket tư vấn mới' + code + '. Tổng đang chờ: ' + pendingTotal + '.',
                    'Ticket mới'
                );

                reloadCurrentSessions({ silent: true });

                const currentStatus = $('#ticketStatusFilter').val() || '';

                if (currentStatus === '' || currentStatus === 'pending') {
                    loadTickets(1, { silent: true });
                }
            }

            lastPendingTicketCount = pendingTotal;
        }).always(function () {
            isCheckingPendingTickets = false;
        });
    }

    // ═══════════════════════════════════════════════════════
    // 11. POLLING
    // ═══════════════════════════════════════════════════════
    function startPolling() {
        stopPolling();

        // Kiểm tra ticket mới ngay khi mở trang
        checkNewPendingTickets();

        // Poll nhẹ mỗi 5 giây để hiện ticket mới gần như tức thì
        ticketPollingInterval = setInterval(function () {
            checkNewPendingTickets();
        }, 5000);

        // Poll tổng thể vẫn giữ 60 giây để tránh nặng server
        pollingInterval = setInterval(function () {
            loadStats();
            reloadCurrentSessions({ silent: true });

            if ($('#pane-tickets').hasClass('active')) {
                loadTickets(ticketCurrentPage || 1, { silent: true });
            }

            if ($('#pane-library').hasClass('active')) {
                loadAnswerLibrary(
                    libraryCurrentPage || 1,
                    { silent: true }
                );
            }
        }, 60000);
    }

    function stopPolling() {
        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
        }

        if (ticketPollingInterval) {
            clearInterval(ticketPollingInterval);
            ticketPollingInterval = null;
        }
    }

    $(window).on('beforeunload', stopPolling);

    // ═══════════════════════════════════════════════════════
    // 12. INIT
    // ═══════════════════════════════════════════════════════
    loadStats();
    reloadCurrentSessions({ page: 1, reloadPreview: true });
    loadTickets(1);
    startPolling();

    window.loadStats = loadStats;
    window.loadSessions = loadSessions;
    window.loadTickets = loadTickets;
    window.loadAnswerLibrary = loadAnswerLibrary;
});