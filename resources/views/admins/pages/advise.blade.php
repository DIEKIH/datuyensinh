@extends('admins.layouts.app')

@section('title', 'Quản lý Advise')

@section('css')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/admins/baiviet.css">
    <style>
        /* ══════════════════════════════════════════
           STATS CARDS
        ══════════════════════════════════════════ */
        .stat-card {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px 18px;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 1px 4px rgba(0,0,0,.08);
            transition: box-shadow .2s, transform .2s;
        }
        .stat-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,.12); transform: translateY(-1px); }

        .stat-blue   { border-left: 4px solid #3b82f6; }
        .stat-blue   .stat-icon { background: #eff6ff; color: #3b82f6; }
        .stat-green  { border-left: 4px solid #22c55e; }
        .stat-green  .stat-icon { background: #f0fdf4; color: #22c55e; }
        .stat-orange { border-left: 4px solid #f97316; }
        .stat-orange .stat-icon { background: #fff7ed; color: #f97316; }
        .stat-purple { border-left: 4px solid #a855f7; }
        .stat-purple .stat-icon { background: #faf5ff; color: #a855f7; }
        .stat-amber  { border-left: 4px solid #f59e0b; cursor: pointer; }
        .stat-amber  .stat-icon { background: #fffbeb; color: #f59e0b; }

        .stat-icon {
            width: 42px; height: 42px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; flex-shrink: 0;
        }
        .stat-value { font-size: 1.5rem; font-weight: 700; line-height: 1; color: #111827; }
        .stat-label { font-size: .72rem; color: #6b7280; margin-top: 3px; }

        #pendingTicketCard.has-ticket {
            animation: ticketPulse 1.5s ease-in-out infinite;
        }
        @keyframes ticketPulse {
            0%,100% { box-shadow: 0 1px 4px rgba(0,0,0,.08); }
            50%      { box-shadow: 0 0 0 4px rgba(245,158,11,.2); }
        }

        /* ══════════════════════════════════════════
           PAGE HEADER
        ══════════════════════════════════════════ */
        .page-header {
            display: flex; align-items: center;
            justify-content: space-between; margin-bottom: 1.25rem;
        }
        .page-title { font-size: 1.3rem; font-weight: 700; margin: 0; color: #111827; }

        /* ══════════════════════════════════════════
           TAB NAVIGATION
        ══════════════════════════════════════════ */
        .cb-tabs {
            display: flex;
            gap: 0;
            border-bottom: 2px solid #e5e7eb;
            margin-bottom: 0;
        }
        .cb-tab-btn {
            padding: 10px 22px;
            font-size: .875rem;
            font-weight: 600;
            color: #6b7280;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            cursor: pointer;
            transition: color .15s, border-color .15s;
            display: flex; align-items: center; gap: 7px;
        }
        .cb-tab-btn:hover { color: #374151; }
        .cb-tab-btn.active { color: #3b82f6; border-bottom-color: #3b82f6; }
        .cb-tab-badge {
            background: #ef4444; color: #fff;
            font-size: .65rem; font-weight: 700;
            padding: 1px 6px; border-radius: 999px;
            line-height: 1.4;
        }
        .cb-tab-pane { display: none; }
        .cb-tab-pane.active { display: block; }

        /* ══════════════════════════════════════════
           SESSION TABLE CARD
        ══════════════════════════════════════════ */
        .cb-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            overflow: visible;       /* ← ĐỔI THÀNH visible */
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
        }
        
        .cb-card-header {
            display: flex; align-items: center;
            gap: 10px; padding: 12px 16px;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            flex-wrap: wrap;
        }
        .cb-card-title { font-weight: 600; font-size: .9rem; color: #374151; margin-right: auto; }

        /* ══════════════════════════════════════════
           TICKET TABLE
        ══════════════════════════════════════════ */
        .ticket-badge {
            display: inline-flex; align-items: center;
            padding: 3px 9px; border-radius: 999px;
            font-size: .72rem; font-weight: 700; white-space: nowrap;
        }
        .tb-pending  { background: #fef3c7; color: #92400e; }
        .tb-answered { background: #dcfce7; color: #166534; }
        .tb-closed   { background: #e5e7eb; color: #4b5563; }

        .ticket-q-text {
            max-width: 360px; white-space: nowrap;
            overflow: hidden; text-overflow: ellipsis;
            font-size: .82rem;
        }

        /* ══════════════════════════════════════════
           CHAT BOX (inside modal)
        ══════════════════════════════════════════ */
        .chat-box {
            display: flex; flex-direction: column; gap: 10px;
            max-height: 62vh; overflow-y: auto;
            padding: 12px; background: #f8fafc;
            border-radius: 8px; border: 1px solid #e5e7eb;
        }
        .chat-message { display: flex; align-items: flex-start; gap: 9px; }
        .chat-message.chat-user { flex-direction: row-reverse; }
        .chat-avatar {
            width: 32px; height: 32px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: .8rem; color: #fff; flex-shrink: 0;
        }
        .chat-user .chat-avatar { background: #3b82f6; }
        .chat-bot  .chat-avatar { background: #9ca3af; }
        .chat-bubble-wrap { max-width: 72%; }
        .chat-meta {
            display: flex; align-items: center; gap: 5px;
            margin-bottom: 3px; font-size: .68rem; color: #9ca3af;
        }
        .chat-message.chat-user .chat-meta { flex-direction: row-reverse; }
        .chat-role { font-weight: 600; color: #6b7280; }
        .chat-bubble {
            padding: 8px 12px; border-radius: 12px;
            font-size: .82rem; line-height: 1.55;
            white-space: pre-wrap; word-break: break-word;
        }
        .chat-user .chat-bubble {
            background: #3b82f6; color: #fff;
            border-top-right-radius: 3px;
        }
        .chat-bot .chat-bubble {
            background: #fff; color: #1f2937;
            border: 1px solid #e5e7eb;
            border-top-left-radius: 3px;
        }
        .btn-msg-delete {
            background: none; border: none; padding: 0 2px;
            color: #ef4444; opacity: 0; cursor: pointer;
            font-size: .7rem; transition: opacity .15s; line-height: 1;
        }
        .chat-message:hover .btn-msg-delete { opacity: 1; }

        /* ══════════════════════════════════════════
           TICKET DETAIL INSIDE MODAL
        ══════════════════════════════════════════ */
        .ticket-box {
            border-radius: 8px; padding: 10px 12px;
            font-size: .83rem; line-height: 1.6;
            white-space: pre-wrap; word-break: break-word;
        }
        .ticket-question-box { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e3a8a; }
        .ticket-note-box     { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }
        .ticket-answer-box   { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }

        /* divider between chat and answer area */
        .modal-divider {
            text-align: center; position: relative; margin: 16px 0;
        }
        .modal-divider::before {
            content: ''; position: absolute; left: 0; right: 0;
            top: 50%; height: 1px; background: #e5e7eb;
        }
        .modal-divider span {
            position: relative; background: #fff;
            padding: 0 10px; font-size: .75rem;
            color: #9ca3af; font-weight: 600; text-transform: uppercase; letter-spacing: .5px;
        }

        /* DataTables pagination fix */
        /* #adviseTable_wrapper .dt-layout-row:last-child {
            position: fixed;
            width: calc(100% - 250px);
            bottom: 0; left: 0; right: 0;
            background: #fff;
            padding: 8px 16px;
            z-index: 999;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-left: 250px;
        } */

        /* THÊM — cho phép cuộn ngang bảng, không chặn cuộn dọc trang */
        #pane-sessions .table-responsive {
            overflow-x: auto;
            overflow-y: visible;
        }

        /* THÊM VÀO — pagination bình thường, không fixed */
        #adviseTable_wrapper .dt-layout-row:last-child {
            padding: 8px 16px;
            border-top: 1px solid #e5e7eb;
            background: #f9fafb;
        }

        /* ══════════════════════════════════════════
           PENDING ALERT BANNER
        ══════════════════════════════════════════ */
        #ticketPendingAlert {
            border-radius: 8px; font-size: .85rem;
        }

        /* "Có ticket" indicator in session row */
        .badge-ticket-count {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 3px 8px; border-radius: 999px;
            font-size: .72rem; font-weight: 700;
            background: #fef3c7; color: #92400e;
        }
    </style>
@endsection

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-robot me-2 text-primary"></i>Quản lý Advise
        </h1>
    </div>

    {{-- ── Stats row (5 cards đồng hàng) ──────────────────── --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md">
            <div class="stat-card stat-blue">
                <div class="stat-icon"><i class="fas fa-comments"></i></div>
                <div>
                    <div class="stat-value" id="statSessions">—</div>
                    <div class="stat-label">Tổng phiên chat</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="stat-card stat-green">
                <div class="stat-icon"><i class="fas fa-envelope"></i></div>
                <div>
                    <div class="stat-value" id="statMessages">—</div>
                    <div class="stat-label">Tổng tin nhắn</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="stat-card stat-orange">
                <div class="stat-icon"><i class="fas fa-user"></i></div>
                <div>
                    <div class="stat-value" id="statUserMsg">—</div>
                    <div class="stat-label">Tin người dùng</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="stat-card stat-purple">
                <div class="stat-icon"><i class="fas fa-robot"></i></div>
                <div>
                    <div class="stat-value" id="statBotMsg">—</div>
                    <div class="stat-label">Tin bot</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="stat-card stat-amber" id="pendingTicketCard" title="Nhấn để xem ticket chờ">
                <div class="stat-icon"><i class="fas fa-bell"></i></div>
                <div>
                    <div class="stat-value" id="statPendingTickets">0</div>
                    <div class="stat-label">Ticket chờ xử lý</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Tab container ──────────────────────────────────── --}}
    <div class="cb-card">
        {{-- Tab nav --}}
        <div class="cb-tabs px-3 pt-2">
            <button class="cb-tab-btn active" data-tab="sessions">
                <i class="fas fa-list-ul"></i> Phiên chat
            </button>
            <button class="cb-tab-btn" data-tab="tickets" id="tabBtnTickets">
                <i class="fas fa-headset"></i> Yêu cầu tư vấn
                <span class="cb-tab-badge d-none" id="tabTicketBadge">0</span>
            </button>
        </div>

        {{-- ── TAB: SESSIONS ──────────────────────── --}}
        <div class="cb-tab-pane active" id="pane-sessions">
            {{-- Filter bar --}}
            <div class="cb-card-header" style="border-top:1px solid #e5e7eb; border-radius:0;">
                <span class="cb-card-title"><i class="fas fa-filter me-1 text-muted"></i>Lọc phiên</span>
                <input type="date" id="filterDateFrom" class="form-control form-control-sm" style="width:140px;" title="Từ ngày">
                <input type="date" id="filterDateTo"   class="form-control form-control-sm" style="width:140px;" title="Đến ngày">
                <input type="text" id="filterIP"       class="form-control form-control-sm" style="width:130px;" placeholder="Lọc IP...">
                <button class="btn btn-primary btn-sm" id="btnFilter">
                    <i class="fas fa-search me-1"></i>Lọc
                </button>
                <button class="btn btn-outline-secondary btn-sm" id="btnResetFilter" title="Đặt lại">
                    <i class="fas fa-redo"></i>
                </button>
            </div>

            <div class="table-responsive">
                <table id="adviseTable" class="display table table-bordered table-striped mb-0" style="width:100%">
                    <thead><tr></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        {{-- ── TAB: TICKETS ───────────────────────── --}}
        <div class="cb-tab-pane" id="pane-tickets">
            <div class="cb-card-header" style="border-top:1px solid #e5e7eb; border-radius:0;">
                <span class="cb-card-title">
                    <i class="fas fa-headset me-1 text-primary"></i>Yêu cầu tư vấn
                </span>

                <select id="ticketStatusFilter" class="form-control form-control-sm" style="width:140px;">
                    <option value="">Tất cả</option>
                    <option value="pending">Đang chờ</option>
                    <option value="answered">Đã trả lời</option>
                    <option value="closed">Đã đóng</option>
                </select>

                <input type="text" id="ticketKeyword" class="form-control form-control-sm"
                       style="width:210px;" placeholder="Tìm mã, câu hỏi, thread...">

                <button class="btn btn-primary btn-sm" id="btnFilterTickets">
                    <i class="fas fa-search me-1"></i>Lọc
                </button>
                <button class="btn btn-outline-secondary btn-sm" id="btnReloadTickets" title="Làm mới">
                    <i class="fas fa-sync"></i>
                </button>
            </div>

            <div id="ticketPendingAlert" class="alert alert-warning d-none mx-3 mt-3 mb-0 py-2">
                <i class="fas fa-bell me-1"></i>
                Có <strong id="ticketPendingAlertCount">0</strong> yêu cầu tư vấn đang chờ trả lời.
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                    <thead class="table-light">
                        <tr>
                            <th style="width:55px;">ID</th>
                            <th style="width:155px;">Mã tra cứu</th>
                            <th>Câu hỏi</th>
                            <th style="width:110px;">Trạng thái</th>
                            <th style="width:140px;">Admin trả lời</th>
                            <th style="width:135px;">Thời gian</th>
                            <th style="width:110px;" class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="ticketTableBody">
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Đang tải...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top" style="background:#f9fafb;">
                <div class="text-muted small" id="ticketPageInfo"></div>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary" id="btnTicketPrev">‹ Trước</button>
                    <button class="btn btn-sm btn-outline-secondary" id="btnTicketNext">Sau ›</button>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- ═══════════════════════════════════════════════
     MODAL: CHI TIẾT PHIÊN CHAT
════════════════════════════════════════════════ --}}
<div class="modal fade" id="sessionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2 px-3 bg-white border-bottom">
                <h5 class="modal-title fw-semibold">
                    <i class="fas fa-comment-dots me-2 text-primary"></i>Chi tiết phiên chat
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="p-3 mb-3 rounded bg-light border small">
                    <div class="row g-2">
                        <div class="col-md-4"><span class="text-muted">Session ID: </span><strong id="metaId">—</strong></div>
                        <div class="col-md-4"><span class="text-muted">Thread ID: </span><strong id="metaThread" class="text-truncate d-inline-block" style="max-width:200px;vertical-align:bottom;"></strong></div>
                        <div class="col-md-4"><span class="text-muted">IP: </span><strong id="metaIP">—</strong></div>
                        <div class="col-md-4"><span class="text-muted">Bắt đầu: </span><strong id="metaStart">—</strong></div>
                        <div class="col-md-4"><span class="text-muted">Hoạt động cuối: </span><strong id="metaLast">—</strong></div>
                        <div class="col-md-4"><span class="text-muted">Người dùng: </span><strong id="metaUser">—</strong></div>
                    </div>
                </div>
                <div id="chatBox" class="chat-box">
                    <div class="text-center text-muted py-4">Đang tải...</div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button class="btn btn-outline-danger btn-sm" id="btnDeleteSession">
                    <i class="fas fa-trash-alt me-1"></i>Xóa phiên này
                </button>
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════
     MODAL: TICKET — gộp chat history + trả lời
════════════════════════════════════════════════ --}}
<div class="modal fade" id="ticketDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header py-2 px-4" style="background:#3b82f6;">
                <div>
                    <h5 class="modal-title text-white mb-0 fw-semibold">
                        <i class="fas fa-headset me-2"></i>Yêu cầu tư vấn
                    </h5>
                    <small class="text-white opacity-75" id="ticketModalCode"></small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body px-4 py-3">
                <input type="hidden" id="ticketCurrentId">

                {{-- Row: status + meta --}}
                <div class="d-flex align-items-start gap-3 mb-3 flex-wrap">
                    <div>
                        <div class="text-muted small mb-1 fw-semibold">Trạng thái</div>
                        <div id="ticketModalStatus"></div>
                    </div>
                    <div class="ms-auto small text-muted" id="ticketModalSession" style="line-height:1.7;text-align:right;"></div>
                </div>

                {{-- Chat history (toàn phiên) --}}
                <div class="mb-2">
                    <div class="fw-semibold text-muted small mb-1">
                        <i class="fas fa-history me-1"></i>Lịch sử hội thoại trong phiên
                    </div>
                    <div id="ticketChatBox" class="chat-box">
                        <div class="text-center text-muted py-3 small">Đang tải lịch sử...</div>
                    </div>
                </div>

                <div class="modal-divider"><span>Nội dung tư vấn</span></div>

                {{-- Câu hỏi gốc --}}
                <div class="mb-3">
                    <div class="fw-semibold small mb-1">Câu hỏi người dùng</div>
                    <div class="ticket-box ticket-question-box" id="ticketModalQuestion"></div>
                </div>

                {{-- Bot note --}}
                <div class="mb-3 d-none" id="ticketBotNoteWrap">
                    <div class="fw-semibold small mb-1">Ghi chú từ chatbot</div>
                    <div class="ticket-box ticket-note-box" id="ticketModalBotNote"></div>
                </div>

                {{-- Câu trả lời cũ --}}
                <div class="mb-3 d-none" id="ticketOldAnswerWrap">
                    <div class="fw-semibold small mb-1">Câu trả lời hiện tại</div>
                    <div class="ticket-box ticket-answer-box" id="ticketModalOldAnswer"></div>
                </div>

                {{-- Input trả lời --}}
                <div class="mb-2">
                    <label class="fw-semibold small mb-1">Nhập câu trả lời của nhân viên</label>
                    <textarea id="ticketStaffAnswer" class="form-control mb-2" rows="5"
                              placeholder="Nhập nội dung trả lời cho người hỏi..."></textarea>
                </div>
                
                {{-- Checkbox Công khai / Dùng cho RAG --}}
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="ticketIsPublic">
                    <label class="form-check-label small fw-semibold text-primary" for="ticketIsPublic">
                        <i class="fas fa-share-alt me-1"></i>Cho phép chatbot dùng làm câu trả lời mẫu (Công khai)
                    </label>
                    <div class="text-muted" style="font-size: 0.72rem; margin-top: 2px;">
                        Chỉ tích chọn nếu đây là câu trả lời chung (học phí, thủ tục...). Bỏ tích nếu câu hỏi chứa thông tin riêng tư để tránh lộ dữ liệu cá nhân của thí sinh.
                    </div>
                </div>

                <div class="text-muted" style="font-size:.75rem;">
                    Sau khi gửi, nếu người hỏi còn mở khung chat, câu trả lời sẽ tự hiển thị.
                </div>
            </div>

            <div class="modal-footer py-2 px-4">
                <button type="button" class="btn btn-outline-danger btn-sm me-auto" id="btnCloseTicket">
                    <i class="fas fa-times me-1"></i>Đóng ticket
                </button>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnSubmitTicketAnswer">
                    <i class="fas fa-paper-plane me-1"></i>Gửi trả lời
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
    <script src="{{ asset('js/admins/advise.js') }}"></script>
    <script>
    // ── TAB SWITCHING ──────────────────────────────────────────────
    document.querySelectorAll('.cb-tab-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.cb-tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.cb-tab-pane').forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            document.getElementById('pane-' + this.dataset.tab).classList.add('active');
        });
    });

    // Click pending card → jump to ticket tab
    document.getElementById('pendingTicketCard').addEventListener('click', function() {
        document.querySelectorAll('.cb-tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.cb-tab-pane').forEach(p => p.classList.remove('active'));
        document.querySelector('[data-tab="tickets"]').classList.add('active');
        document.getElementById('pane-tickets').classList.add('active');
        if (typeof loadTickets === 'function') {
            document.getElementById('ticketStatusFilter').value = 'pending';
            loadTickets(1);
        }
    });
    </script>
@endsection
