$(document).ready(function () {
    'use strict';

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': getCsrfToken() } });

    // ═══════════════════════════════════════════════════════
    // GLOBAL STATE
    // ═══════════════════════════════════════════════════════
    let currentSessionId   = null;
    let ticketCurrentPage  = 1;
    let ticketLastPage     = 1;
    let lastPendingTicketCount = 0;
    let pollingInterval    = null;

    // Polling riêng cho ticket mới, giúp admin thấy thông báo gần như tức thì
    let ticketPollingInterval = null;
    let lastSeenPendingTicketId = null;
    let isCheckingPendingTickets = false;

    // ═══════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════
    // function getCsrfToken() {
    //     return $('meta[name="csrf-token"]').attr('content')
    //         || $('input[name="_token"]').first().val() || '';
    // }

    function getCsrfToken() {
        const token = $('meta[name="csrf-token"]').attr('content');

        if (token) {
            return token;
        }

        return $('input[name="_token"]').first().val() || '';
    }

    function formatDatetime(str) {
        if (!str) return '—';
        const d = new Date(str);
        if (isNaN(d)) return str;
        const p = n => String(n).padStart(2, '0');
        return `${p(d.getDate())}/${p(d.getMonth()+1)}/${d.getFullYear()} ${p(d.getHours())}:${p(d.getMinutes())}`;
    }

    function escapeHtml(text) {
        return String(text || '')
            .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
    }

    function escapeHtmlWithBreaks(text) {
        return escapeHtml(text).replace(/\n/g,'<br>');
    }

    function toastSuccess(m) { typeof toastr !== 'undefined' ? toastr.success(m) : alert(m); }
    function toastError(m)   { typeof toastr !== 'undefined' ? toastr.error(m)   : alert(m); }
    function toastWarning(m,t) {
        typeof toastr !== 'undefined' ? toastr.warning(m, t||'') : alert(m);
    }

    function showModal(selector) {
        const el = document.querySelector(selector);
        if (!el) return;
        if (typeof bootstrap !== 'undefined') {
            (bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el)).show();
        } else if ($.fn.modal) { $(selector).modal('show'); }
    }

    function hideModal(selector) {
        const el = document.querySelector(selector);
        if (!el) return;
        if (typeof bootstrap !== 'undefined') {
            (bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el)).hide();
        } else if ($.fn.modal) { $(selector).modal('hide'); }
        setTimeout(function() {
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open').css('padding-right','');
            $(selector).removeClass('show').hide()
                .attr('aria-hidden','true').removeAttr('aria-modal');
        }, 250);
    }

    function hideTicketModal() {
        hideModal('#ticketDetailModal');
    }

    function reloadCurrentSessions() {
        loadSessions({
            date_from: $('#filterDateFrom').val(),
            date_to:   $('#filterDateTo').val(),
            ip:        $('#filterIP').val()
        });
    }

    // ═══════════════════════════════════════════════════════
    // 1. LOAD STATS
    // ═══════════════════════════════════════════════════════
    // function loadStats() {
    //     $.get('/admin/advise/stats', function(res) {
    //         $('#statSessions').text(Number(res.total_sessions||0).toLocaleString());
    //         $('#statMessages').text(Number(res.total_messages||0).toLocaleString());
    //         $('#statUserMsg').text(Number(res.user_messages||0).toLocaleString());
    //         $('#statBotMsg').text(Number(res.bot_messages||0).toLocaleString());
    //         updateTicketStats(res);
    //     });
    // }

    function loadStats() {
        $.get('/admin/advise/stats', function (res) {
            $('#statSessions').text(Number(res.total_sessions || 0).toLocaleString());
            $('#statMessages').text(Number(res.total_messages || 0).toLocaleString());
            $('#statUserMsg').text(Number(res.user_messages || 0).toLocaleString());
            $('#statBotMsg').text(Number(res.bot_messages || 0).toLocaleString());
            $('#statVoice').text(Number(res.voice_messages || 0).toLocaleString());

            updateTicketStatsFromStatsResponse(res);
        }).fail(function () {
            console.error('Không thể tải stats');
            toastError('Không thể tải thống kê');
        });
    }





    function updatePendingTicketUI(pendingTickets) {
        pendingTickets = Number(pendingTickets || 0);

        $('#statPendingTickets').text(pendingTickets.toLocaleString());
        $('#ticketPendingAlertCount').text(pendingTickets);

        if (pendingTickets > 0) {
            $('#tabTicketBadge').text(pendingTickets).removeClass('d-none');
            $('#pendingTicketCard').addClass('has-ticket');
            $('#ticketPendingAlert').removeClass('d-none');
        } else {
            $('#tabTicketBadge').addClass('d-none');
            $('#pendingTicketCard').removeClass('has-ticket');
            $('#ticketPendingAlert').addClass('d-none');
        }
    }

    function updateTicketStatsFromStatsResponse(res) {
        const pendingTickets = Number(res.pending_tickets || 0);

        updatePendingTicketUI(pendingTickets);

        if (pendingTickets > lastPendingTicketCount) {
            toastWarning(
                'Có ' + pendingTickets + ' yêu cầu tư vấn đang chờ xử lý.',
                'Ticket tư vấn'
            );
        }

        lastPendingTicketCount = pendingTickets;
    }





    // ═══════════════════════════════════════════════════════
    // 2. SESSION TABLE
    // ═══════════════════════════════════════════════════════
    function loadSessions(params) {
        $.get('/admin/advise/sessions', params||{}, function(res) {
            renderTable(res.data||[]);
        }).fail(function() { toastError('Không thể tải dữ liệu phiên chat.'); });
    }


    function renderTable(data) {
        if ($.fn.DataTable.isDataTable('#adviseTable')) {
            $('#adviseTable').DataTable().destroy();
        }

        $('#adviseTable').DataTable({
            data: data,
            columns: [
                {
                    data: 'id',
                    title: 'ID',
                    width: '50px'
                },
                {
                    data: 'ip_address',
                    title: 'IP',
                    render: function (d) {
                        return `<span class="badge bg-secondary">${escapeHtml(d || '—')}</span>`;
                    }
                },
                {
                    data: 'user_name',
                    title: 'Người dùng',
                    render: function (d) {
                        return d
                            ? `<span class="badge bg-primary">${escapeHtml(d)}</span>`
                            : `<span class="text-muted fst-italic">Khách</span>`;
                    }
                },
                {
                    data: 'message_count',
                    title: 'Tin nhắn',
                    className: 'text-center',
                    render: function (d, t, row) {
                        return `
                            <span class="badge bg-success me-1" title="Người dùng">
                                <i class="fas fa-user me-1"></i>${Number(row.user_message_count || 0)}
                            </span>
                            <span class="badge bg-info text-dark" title="Bot">
                                <i class="fas fa-robot me-1"></i>${Number(row.bot_message_count || 0)}
                            </span>
                        `;
                    }
                },
                {
                    data: 'started_at',
                    title: 'Bắt đầu',
                    render: function (d) {
                        return formatDatetime(d);
                    }
                },
                {
                    data: 'last_active_at',
                    title: 'Hoạt động cuối',
                    render: function (d) {
                        return formatDatetime(d);
                    }
                },
                {
                    data: 'thread_id',
                    title: 'Thread ID',
                    render: function (d) {
                        return d
                            ? `<span class="text-truncate d-inline-block" style="max-width:120px;" title="${escapeHtml(d)}">${escapeHtml(d)}</span>`
                            : '<span class="text-muted">—</span>';
                    }
                },
                {
                    data: null,
                    title: 'Thao tác',
                    orderable: false,
                    className: 'text-center',
                    render: function (d, t, row) {
                        return `
                            <div class="d-flex justify-content-center gap-1">
                                <button class="btn btn-sm btn-outline-primary btn-view-session"
                                        data-id="${row.id}" title="Xem chi tiết">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger btn-delete-session"
                                        data-id="${row.id}" title="Xóa">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        `;
                    }
                }
            ],
            // scrollY: '55vh',
            // scrollCollapse: true,
            scrollY: 'calc(100vh - 420px)',   /* tự tính theo chiều cao màn hình */
            scrollCollapse: true,
            scrollX: true,  
            paging: true,
            lengthMenu: [10, 25, 50, 100],
            pageLength: 10,
            order: [[0, 'desc']],
            language: {
                lengthMenu: 'Hiển thị &nbsp _MENU_ &nbsp bản ghi',
                search: 'Tìm kiếm:',
                info: 'Hiển thị _START_ đến _END_ trong tổng _TOTAL_ bản ghi',
                paginate: {
                    first: 'Đầu',
                    last: 'Cuối',
                    next: 'Sau',
                    previous: 'Trước'
                },
                emptyTable: 'Không có dữ liệu'
            }
        });
    }



    // function renderTable(data) {
    //     if ($.fn.DataTable.isDataTable('#adviseTable')) {
    //         $('#adviseTable').DataTable().destroy();
    //     }

    //     $('#adviseTable').DataTable({
    //         data: data,
    //         columns: [
    //             { data: 'id', title: 'Session ID', width: '70px',
    //               render: d => `<span class="fw-bold text-secondary">#${d}</span>` },
    //             { data: 'ip_address', title: 'IP',
    //               render: d => `<span class="badge bg-secondary">${escapeHtml(d||'—')}</span>` },
    //             { data: 'user_name', title: 'Người dùng',
    //               render: d => d
    //                 ? `<span class="badge bg-primary">${escapeHtml(d)}</span>`
    //                 : `<span class="text-muted fst-italic">Khách</span>` },
    //             { data: 'message_count', title: 'Tin nhắn', className: 'text-center',
    //               render: (d,t,row) => `
    //                 <span class="badge bg-success me-1" title="Người dùng">
    //                     <i class="fas fa-user me-1"></i>${Number(row.user_message_count||0)}
    //                 </span>
    //                 <span class="badge bg-info text-dark" title="Bot">
    //                     <i class="fas fa-robot me-1"></i>${Number(row.bot_message_count||0)}
    //                 </span>` },
    //             // Cột "Có ticket" — hiển thị số ticket pending của session này
    //             { data: null, title: 'Có ticket', className: 'text-center',
    //               orderable: false,
    //               render: (d,t,row) => {
    //                 if (row.ticket_pending_count > 0) {
    //                     return `<span class="badge-ticket-count">
    //                         <i class="fas fa-bell"></i>${row.ticket_pending_count} chờ
    //                     </span>`;
    //                 }
    //                 return '<span class="text-muted">—</span>';
    //               }
    //             },
    //             { data: 'started_at',     title: 'Bắt đầu',       render: d => formatDatetime(d) },
    //             { data: 'last_active_at', title: 'Hoạt động cuối', render: d => formatDatetime(d) },
    //             { data: 'thread_id', title: 'Thread ID',
    //               render: d => d
    //                 ? `<span class="text-truncate d-inline-block" style="max-width:110px;" title="${escapeHtml(d)}">${escapeHtml(d)}</span>`
    //                 : '<span class="text-muted">—</span>' },
    //             { data: null, title: 'Thao tác', orderable: false, className: 'text-center',
    //               render: (d,t,row) => `
    //                 <div class="d-flex justify-content-center gap-1">
    //                     <button class="btn btn-sm btn-outline-primary btn-view-session" data-id="${row.id}" title="Xem chi tiết">
    //                         <i class="fas fa-eye"></i>
    //                     </button>
    //                     <button class="btn btn-sm btn-outline-danger btn-delete-session" data-id="${row.id}" title="Xóa">
    //                         <i class="fas fa-trash-alt"></i>
    //                     </button>
    //                 </div>` }
    //         ],
    //         scrollY: '55vh', scrollCollapse: true,
    //         paging: true, lengthMenu: [10,25,50,100], pageLength: 10,
    //         order: [[0,'desc']],
    //         language: {
    //             lengthMenu: 'Hiển thị _MENU_ bản ghi', search: 'Tìm kiếm:',
    //             info: 'Hiển thị _START_ đến _END_ trong _TOTAL_ bản ghi',
    //             paginate: { first:'Đầu', last:'Cuối', next:'Sau', previous:'Trước' },
    //             emptyTable: 'Không có dữ liệu'
    //         }
    //     });
    // }

    // ═══════════════════════════════════════════════════════
    // 3. FILTER SESSIONS
    // ═══════════════════════════════════════════════════════
    $('#btnFilter').on('click', reloadCurrentSessions);
    $('#btnResetFilter').on('click', function() {
        $('#filterDateFrom,#filterDateTo,#filterIP').val('');
        loadSessions();
    });

    // ═══════════════════════════════════════════════════════
    // 4. SESSION DETAIL MODAL
    // ═══════════════════════════════════════════════════════
    $(document).on('click', '.btn-view-session', function() {
        currentSessionId = $(this).data('id');
        openSessionModal(currentSessionId);
    });

    function openSessionModal(id) {
        $('#chatBox').html('<div class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin me-2"></i>Đang tải...</div>');
        $('#metaId,#metaThread,#metaIP,#metaStart,#metaLast,#metaUser').text('—');
        showModal('#sessionModal');

        $.get('/admin/advise/sessions/'+id, function(res) {
            if (res.status !== 'success') { toastError('Không thể tải chi tiết phiên.'); return; }
            fillMeta(res.session);
            renderChat(res.messages, '#chatBox');
        }).fail(function() { toastError('Lỗi máy chủ.'); });
    }

    function fillMeta(s) {
        $('#metaId').text(s.id||'—');
        $('#metaThread').text(s.thread_id||'—').attr('title', s.thread_id||'');
        $('#metaIP').text(s.ip_address||'—');
        $('#metaStart').text(formatDatetime(s.started_at));
        $('#metaLast').text(formatDatetime(s.last_active_at));
        $('#metaUser').text(s.user_name||'Khách');
    }

    function renderChat(messages, boxSelector) {
        const $box = $(boxSelector);
        if (!messages || messages.length === 0) {
            $box.html('<div class="text-center text-muted py-4">Không có tin nhắn nào.</div>');
            return;
        }
        let html = '';
        messages.forEach(function(msg) {
            const isUser = msg.role === 'user';
            const icon   = isUser ? '<i class="fas fa-user"></i>' : '<i class="fas fa-robot"></i>';
            const voice  = msg.input_type === 'voice'
                ? ' <span class="badge bg-warning text-dark" style="font-size:.6rem"><i class="fas fa-microphone"></i></span>'
                : '';
            html += `
            <div class="chat-message ${isUser?'chat-user':'chat-bot'}">
                <div class="chat-avatar">${icon}</div>
                <div class="chat-bubble-wrap">
                    <div class="chat-meta">
                        <span class="chat-role">${isUser?'Người dùng':'Bot'}${voice}</span>
                        <span class="chat-time">${formatDatetime(msg.sent_at)}</span>
                        <button class="btn-msg-delete" data-id="${msg.id}" title="Xóa">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="chat-bubble">${escapeHtmlWithBreaks(msg.content)}</div>
                </div>
            </div>`;
        });
        $box.html(html);
        const el = document.querySelector(boxSelector);
        if (el) el.scrollTop = el.scrollHeight;
    }

    // ═══════════════════════════════════════════════════════
    // 5. DELETE MESSAGE
    // ═══════════════════════════════════════════════════════
    $(document).on('click', '.btn-msg-delete', function() {
        const msgId = $(this).data('id');
        const $msg  = $(this).closest('.chat-message');
        Swal.fire({
            title:'Xóa tin nhắn?', text:'Tin nhắn sẽ bị xóa vĩnh viễn.',
            icon:'warning', showCancelButton:true,
            confirmButtonColor:'#ef4444', cancelButtonColor:'#6b7280',
            confirmButtonText:'Xóa', cancelButtonText:'Hủy'
        }).then(function(r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url:'/admin/advise/messages/'+msgId, type:'DELETE',
                success: function(res) {
                    if (res.success) {
                        $msg.fadeOut(250, function(){ $(this).remove(); });
                        toastSuccess('Đã xóa tin nhắn.');
                        loadStats();
                    } else { toastError(res.message||'Không thể xóa.'); }
                },
                error: function() { toastError('Lỗi máy chủ.'); }
            });
        });
    });

    // ═══════════════════════════════════════════════════════
    // 6. DELETE SESSION
    // ═══════════════════════════════════════════════════════
    $('#btnDeleteSession').on('click', function() {
        if (!currentSessionId) return;
        confirmDeleteSession(currentSessionId, function(){ hideModal('#sessionModal'); });
    });

    $(document).on('click', '.btn-delete-session', function() {
        confirmDeleteSession($(this).data('id'), null);
    });

    function confirmDeleteSession(id, afterSuccess) {
        Swal.fire({
            title:'Xóa phiên chat?', text:'Toàn bộ tin nhắn sẽ bị xóa.',
            icon:'warning', showCancelButton:true,
            confirmButtonColor:'#ef4444', cancelButtonColor:'#6b7280',
            confirmButtonText:'Xóa', cancelButtonText:'Hủy'
        }).then(function(r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url:'/admin/advise/sessions/'+id, type:'DELETE',
                success: function(res) {
                    if (res.success) {
                        toastSuccess(res.message||'Đã xóa phiên.');
                        loadSessions(); loadStats();
                        if (typeof afterSuccess === 'function') afterSuccess();
                    } else { toastError(res.message||'Không thể xóa.'); }
                },
                error: function() { toastError('Lỗi máy chủ.'); }
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
        const pending = Number(res.pending_tickets || 0);

        updatePendingTicketUI(pending);

        if (pending > lastPendingTicketCount) {
            toastWarning('Có ' + pending + ' yêu cầu tư vấn đang chờ.', 'Ticket mới');
        }

        lastPendingTicketCount = pending;
    }

    // ═══════════════════════════════════════════════════════
    // 8. LOAD TICKETS
    // ═══════════════════════════════════════════════════════
    function loadTickets(page, options) {
        options = options || {};
        const silent = options.silent === true;

        page = page || 1;
        ticketCurrentPage = page;

        const status  = $('#ticketStatusFilter').val() || '';
        const keyword = $('#ticketKeyword').val() || '';

        if (!silent) {
            $('#ticketTableBody').html('<tr><td colspan="7" class="text-center text-muted py-4">Đang tải...</td></tr>');
        }

        $.get('/admin/advise/tickets', { page, status, keyword }, function(res) {
            if (!res.success) {
                if (!silent) toastError('Không thể tải danh sách ticket');
                return;
            }

            const pg = res.data || {};

            ticketCurrentPage = pg.current_page || 1;
            ticketLastPage    = pg.last_page || 1;

            renderTicketRows(pg.data || []);

            $('#ticketPageInfo').text(
                'Trang ' + ticketCurrentPage + ' / ' + ticketLastPage +
                ' — Tổng ' + Number(pg.total || 0).toLocaleString() + ' ticket'
            );

            $('#btnTicketPrev').prop('disabled', ticketCurrentPage <= 1);
            $('#btnTicketNext').prop('disabled', ticketCurrentPage >= ticketLastPage);
        }).fail(function() {
            if (!silent) {
                $('#ticketTableBody').html('<tr><td colspan="7" class="text-center text-danger py-4">Không thể tải.</td></tr>');
            }
        });
    }

    function renderTicketRows(tickets) {
        if (!tickets.length) {
            $('#ticketTableBody').html('<tr><td colspan="7" class="text-center text-muted py-4">Chưa có yêu cầu nào.</td></tr>');
            return;
        }
        let html = '';
        tickets.forEach(function(t) {
            html += `<tr>
                <td class="fw-bold text-secondary">${t.id}</td>
                <td>
                    <strong class="d-block">${escapeHtml(t.ticket_code||'')}</strong>
                    <span class="text-muted small">Session #${t.session_id||''}</span>
                </td>
                <td>
                    <div class="ticket-q-text" title="${escapeHtml(t.question||'')}">
                        ${escapeHtml(t.question||'')}
                    </div>
                </td>
                <td>${ticketBadge(t.status)}</td>
                <td>${t.admin_name ? escapeHtml(t.admin_name) : '<span class="text-muted">—</span>'}</td>
                <td>
                    <div class="small">${t.created_at_text||''}</div>
                    ${t.answered_at_text ? '<div class="small text-success">↩ '+escapeHtml(t.answered_at_text)+'</div>' : ''}
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-primary btn-ticket-detail" data-id="${t.id}">
                        <i class="fas fa-reply me-1"></i>Xem / Trả lời
                    </button>
                </td>
            </tr>`;
        });
        $('#ticketTableBody').html(html);
    }

    // ═══════════════════════════════════════════════════════
    // 9. TICKET DETAIL MODAL (gộp chat history)
    // ═══════════════════════════════════════════════════════
    function openTicketDetail(id) {
        // Reset
        $('#ticketChatBox').html('<div class="text-center text-muted py-3 small"><i class="fas fa-spinner fa-spin me-1"></i>Đang tải lịch sử...</div>');

        $.get('/admin/advise/tickets/'+id, function(res) {
            if (!res.success) { toastError(res.message||'Không tìm thấy ticket'); return; }
            const t = res.data||{};

            $('#ticketCurrentId').val(t.id||'');
            $('#ticketModalCode').text('Mã tra cứu: '+(t.ticket_code||''));
            $('#ticketModalStatus').html(ticketBadge(t.status));

            $('#ticketModalSession').html(`
                <div><strong>Session #${t.session_id||''}</strong> &nbsp;|&nbsp; IP: ${escapeHtml(t.ip_address||'—')}</div>
                <div>Thread: ${escapeHtml(t.thread_id||'—')}</div>
                <div>Tạo lúc: ${escapeHtml(t.created_at_text||'—')}</div>
                <div>Admin: ${t.admin_name ? escapeHtml(t.admin_name) : '—'}</div>
                <div>Gửi về chat: ${escapeHtml(t.delivered_at_text||'Chưa gửi')}</div>
            `);

            $('#ticketModalQuestion').text(t.question||'');

            if (t.bot_note) {
                $('#ticketBotNoteWrap').removeClass('d-none');
                $('#ticketModalBotNote').text(t.bot_note);
            } else { $('#ticketBotNoteWrap').addClass('d-none'); }

            if (t.staff_answer) {
                $('#ticketOldAnswerWrap').removeClass('d-none');
                $('#ticketModalOldAnswer').text(t.staff_answer);
            } else { $('#ticketOldAnswerWrap').addClass('d-none'); }

            $('#ticketStaffAnswer').val(t.staff_answer||'');
            
            // Đặt trạng thái cho checkbox is_public dựa trên dữ liệu cũ của ticket
            $('#ticketIsPublic').prop('checked', t.is_public == 1);

            const isClosed = t.status === 'closed';
            $('#ticketStaffAnswer,#btnSubmitTicketAnswer,#btnCloseTicket,#ticketIsPublic').prop('disabled', isClosed);

            showModal('#ticketDetailModal');

            // Load toàn bộ chat history của session đó
            if (t.session_id) {
                $.get('/admin/advise/sessions/'+t.session_id, function(sr) {
                    if (sr.status === 'success') {
                        renderChat(sr.messages, '#ticketChatBox');
                    } else {
                        $('#ticketChatBox').html('<div class="text-muted small text-center py-2">Không tải được lịch sử.</div>');
                    }
                }).fail(function() {
                    $('#ticketChatBox').html('<div class="text-muted small text-center py-2">Lỗi tải lịch sử.</div>');
                });
            } else {
                $('#ticketChatBox').html('<div class="text-muted small text-center py-2">Không có phiên liên kết.</div>');
            }
        }).fail(function() { toastError('Không thể tải chi tiết ticket'); });
    }

    function submitTicketAnswer() {
        const id       = $('#ticketCurrentId').val();
        const answer   = $('#ticketStaffAnswer').val().trim();
        const isPublic = $('#ticketIsPublic').is(':checked') ? 1 : 0;
        if (!id)     { toastError('Không xác định được ticket'); return; }
        if (!answer) { toastWarning('Vui lòng nhập nội dung trả lời'); $('#ticketStaffAnswer').focus(); return; }

        $('#btnSubmitTicketAnswer').prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i> Đang gửi...');

        $.ajax({
            url: '/admin/advise/tickets/'+id+'/answer', type:'POST',
            data: { staff_answer: answer, is_public: isPublic, _token: getCsrfToken() },
            success: function(res) {
                if (!res.success) { toastError(res.message||'Lỗi gửi'); return; }
                toastSuccess(res.message||'Đã gửi trả lời');
                loadTickets(ticketCurrentPage); loadStats();
                hideModal('#ticketDetailModal');
            },
            error: function(xhr) {
                toastError((xhr.responseJSON&&xhr.responseJSON.message)||'Lỗi gửi');
            },
            complete: function() {
                $('#btnSubmitTicketAnswer').prop('disabled',false).html('<i class="fas fa-paper-plane me-1"></i>Gửi trả lời');
            }
        });
    }

    function closeTicket() {
        const id = $('#ticketCurrentId').val();
        if (!id) { toastError('Không xác định được ticket'); return; }
        if (!confirm('Bạn có chắc muốn đóng yêu cầu này?')) return;

        $('#btnCloseTicket').prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i> Đang đóng...');

        $.ajax({
            url: '/admin/advise/tickets/'+id+'/close', type:'POST',
            data: { _token: getCsrfToken() },
            success: function(res) {
                if (!res.success) { toastError(res.message||'Lỗi'); return; }
                toastSuccess(res.message||'Đã đóng ticket');
                loadTickets(ticketCurrentPage); loadStats();
                hideModal('#ticketDetailModal');
            },
            error: function() { toastError('Không thể đóng ticket'); },
            complete: function() {
                $('#btnCloseTicket').prop('disabled',false).html('<i class="fas fa-times me-1"></i>Đóng ticket');
            }
        });
    }

    // ═══════════════════════════════════════════════════════
    // 10. TICKET EVENTS
    // ═══════════════════════════════════════════════════════
    $(document).on('click', '.btn-ticket-detail', function() { openTicketDetail($(this).data('id')); });
    $(document).on('click', '#btnReloadTickets',   function() { loadTickets(ticketCurrentPage); });
    $(document).on('click', '#btnFilterTickets',   function() { loadTickets(1); });
    $(document).on('change','#ticketStatusFilter', function() { loadTickets(1); });
    $(document).on('keydown','#ticketKeyword', function(e){ if(e.key==='Enter'){e.preventDefault();loadTickets(1);} });
    $(document).on('click','#btnTicketPrev', function(){ if(ticketCurrentPage>1) loadTickets(ticketCurrentPage-1); });
    $(document).on('click','#btnTicketNext', function(){ if(ticketCurrentPage<ticketLastPage) loadTickets(ticketCurrentPage+1); });
    $(document).on('click','#btnSubmitTicketAnswer', submitTicketAnswer);
    $(document).on('click','#btnCloseTicket', closeTicket);

    // Khi bấm qua tab ticket thì tải lại danh sách để bảo đảm thấy dữ liệu mới nhất
    $(document).on('click', '#tabBtnTickets, [data-tab="tickets"]', function () {
        loadTickets(1, { silent: true });
    });


    $(document).on('click', '#pendingTicketCard', function () {
        $('#ticketStatusFilter').val('pending');
        loadTickets(1);

        const section = document.getElementById('adviseTicketSection');

        if (section) {
            section.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });

    

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
            const pendingTickets = pg.data || [];
            const pendingTotal = Number(pg.total || pendingTickets.length || 0);

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

                // Không làm phiền khi admin đang mở modal xem/trả lời
                if ($('.modal.show').length === 0) {
                    reloadCurrentSessions();

                    const currentStatus = $('#ticketStatusFilter').val() || '';

                    // Nếu đang lọc tất cả hoặc pending thì cập nhật luôn bảng ticket,
                    // kể cả khi tab ticket đang ẩn. Như vậy khi bấm qua tab sẽ thấy ticket mới ngay,
                    // không cần reload trang.
                    if (currentStatus === '' || currentStatus === 'pending') {
                        loadTickets(1, { silent: true });
                    }
                }
            }
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
        pollingInterval = setInterval(function() {
            if ($('.modal.show').length === 0) {
                loadStats();
                reloadCurrentSessions();

                if ($('#pane-tickets').hasClass('active')) {
                    loadTickets(ticketCurrentPage || 1, { silent: true });
                }
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
    loadSessions();
    loadTickets(1);
    startPolling();

    window.loadStats    = loadStats;
    window.loadSessions = loadSessions;
    window.loadTickets  = loadTickets;
});
