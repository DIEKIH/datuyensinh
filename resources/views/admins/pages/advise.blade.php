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

        .stat-ticket {
            border-left: 4px solid #cbd5e1;
            cursor: default;
        }

        .stat-ticket .stat-icon {
            background: #f1f5f9;
            color: #64748b;
        }

        .stat-ticket.is-empty {
            opacity: .82;
        }

        .stat-ticket.has-ticket {
            border-left-color: #f59e0b;
            cursor: pointer;
            background:
                linear-gradient(135deg, #ffffff 0%, #fffbeb 100%);
            box-shadow: 0 4px 14px rgba(245, 158, 11, .14);
        }

        .stat-ticket.has-ticket:hover {
            box-shadow: 0 7px 20px rgba(245, 158, 11, .2);
        }

        .stat-ticket.has-ticket .stat-icon {
            background: #fef3c7;
            color: #b45309;
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
           SESSION WORKSPACE: LIST + PREVIEW
        ══════════════════════════════════════════ */
        .conversation-workspace {
            display: grid;
            grid-template-columns: minmax(300px, 36%) minmax(0, 1fr);
            height: calc(100vh - 365px);
            min-height: 610px;
            border-top: 1px solid #e5e7eb;
        }

        .conversation-list-panel,
        .conversation-preview-panel {
            min-width: 0;
            background: #fff;
        }

        .conversation-list-panel {
            display: flex;
            flex-direction: column;
            border-right: 1px solid #e5e7eb;
        }

        .conversation-panel-heading {
            min-height: 48px;
            padding: 18px 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
            font-size: .86rem;
            font-weight: 700;
            color: #374151;
        }

        .conversation-panel-count {
            margin-left: auto;
            min-width: 25px;
            height: 22px;
            padding: 0 7px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #e0e7ff;
            color: #3730a3;
            font-size: .72rem;
        }

        .conversation-list {
            flex: 1;
            overflow-y: auto;
            background: #f8fafc;
        }


        .conversation-list-footer,
        .ticket-list-footer {
            min-height: 45px;
            padding: 7px 10px;
            display: flex;
            align-items: center;
            gap: 7px;
            border-top: 1px solid #e5e7eb;
            background: #fff;
        }

        .conversation-list-footer .page-info,
        .ticket-list-footer .page-info {
            margin-right: auto;
            color: #64748b;
            font-size: .69rem;
        }

        /* Ticket workspace hiển thị trực tiếp trên trang */
        .ticket-workspace {
            display: grid;
            grid-template-columns: minmax(310px, 36%) minmax(0, 1fr);
            height: calc(100vh - 365px);
            min-height: 620px;
            border-top: 1px solid #e5e7eb;
        }

        .ticket-list-panel,
        .ticket-preview-panel {
            min-width: 0;
            background: #fff;
        }

        .ticket-list-panel {
            display: flex;
            flex-direction: column;
            border-right: 1px solid #e5e7eb;
        }

        .ticket-list {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            background: #f8fafc;
        }

        .ticket-list-state {
            padding: 34px 18px;
            text-align: center;
            color: #64748b;
            font-size: .82rem;
        }

        .ticket-item {
            margin: 9px 10px;
            padding: 11px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #fff;
            cursor: pointer;
            outline: none;
            transition: border-color .15s, box-shadow .15s, background .15s;
        }

        .ticket-item:hover {
            border-color: #93c5fd;
            box-shadow: 0 3px 10px rgba(37, 99, 235, .08);
        }

        .ticket-item:focus-visible {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, .2);
        }

        .ticket-item.active {
            border-color: #3b82f6;
            background: #eff6ff;
            box-shadow: 0 0 0 1px rgba(59, 130, 246, .12);
        }

        .ticket-item-top {
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }

        .ticket-item-top strong {
            display: block;
            color: #1f2937;
            font-size: .82rem;
        }

        .ticket-item-top small {
            display: block;
            margin-top: 2px;
            color: #94a3b8;
            font-size: .67rem;
        }

        .ticket-item-top .ticket-badge {
            margin-left: auto;
            flex-shrink: 0;
        }

        .ticket-item-question {
            margin-top: 9px;
            color: #475569;
            font-size: .77rem;
            line-height: 1.45;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
        }

        .ticket-item-bottom {
            margin-top: 9px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #94a3b8;
            font-size: .66rem;
        }

        .ticket-preview-panel {
            display: flex;
            flex-direction: column;
            min-height: 0;
            overflow: hidden;
        }

        .ticket-preview-empty {
            height: 100%;
            min-height: 420px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px;
            text-align: center;
            color: #94a3b8;
        }

        .ticket-preview-empty i {
            margin-bottom: 12px;
            font-size: 2.2rem;
            color: #cbd5e1;
        }

        #ticketPreviewContent:not(.d-none) {
            height: 100%;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .ticket-preview-header {
            min-height: 61px;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid #e5e7eb;
            background: #fff;
        }

        .ticket-preview-code {
            color: #1f2937;
            font-size: .92rem;
            font-weight: 700;
        }

        .ticket-preview-meta {
            margin-top: 4px;
            display: flex;
            flex-wrap: wrap;
            gap: 5px 12px;
            color: #94a3b8;
            font-size: .68rem;
        }

        .ticket-preview-body {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            padding: 14px;
            background: #f8fafc;
        }

        .ticket-history-box {
            max-height: 280px;
            overflow-y: auto;
        }

        .ticket-preview-actions {
            padding: 10px 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            border-top: 1px solid #e5e7eb;
            background: #fff;
        }

        .conversation-list-state {
            padding: 34px 18px;
            text-align: center;
            color: #6b7280;
            font-size: .84rem;
        }

        .conversation-item {
            margin: 9px 10px;
            padding: 11px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #fff;
            cursor: pointer;
            outline: none;
            transition: border-color .15s, box-shadow .15s, background .15s;
        }

        .conversation-item:hover {
            border-color: #93c5fd;
            box-shadow: 0 3px 10px rgba(37, 99, 235, .08);
        }

        .conversation-item:focus-visible {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, .2);
        }

        .conversation-item.active {
            border-color: #3b82f6;
            background: #eff6ff;
            box-shadow: 0 0 0 1px rgba(59, 130, 246, .12);
        }

        .conversation-item-top {
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }

        .conversation-item-title {
            display: flex;
            align-items: center;
            gap: 9px;
            min-width: 0;
        }

        .conversation-item-title strong {
            display: block;
            color: #1f2937;
            font-size: .83rem;
        }

        .conversation-item-title small {
            display: block;
            margin-top: 1px;
            color: #9ca3af;
            font-size: .68rem;
        }

        .conversation-avatar {
            width: 31px;
            height: 31px;
            border-radius: 9px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: #dbeafe;
            color: #2563eb;
        }

        .conversation-delete {
            margin-left: auto;
            padding: 3px 5px;
            border: 0;
            background: transparent;
            color: #cbd5e1;
            line-height: 1;
            transition: color .15s;
        }

        .conversation-delete:hover {
            color: #ef4444;
        }

        .conversation-snippet {
            margin-top: 9px;
            color: #64748b;
            font-size: .77rem;
            line-height: 1.45;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .conversation-sender {
            color: #475569;
            font-weight: 700;
        }

        .conversation-item-bottom {
            margin-top: 9px;
            display: flex;
            align-items: center;
            gap: 9px;
            color: #94a3b8;
            font-size: .66rem;
        }

        .conversation-ticket {
            padding: 2px 6px;
            border-radius: 999px;
            background: #e2e8f0;
            color: #475569;
            font-weight: 700;
        }

        .conversation-ticket.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .conversation-preview-panel {
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .session-preview-empty {
            height: 100%;
            min-height: 420px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px;
            text-align: center;
            color: #94a3b8;
        }

        .session-preview-empty i {
            margin-bottom: 12px;
            font-size: 2.2rem;
            color: #cbd5e1;
        }

        #sessionPreviewContent:not(.d-none) {
            height: 100%;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .conversation-preview-header {
            min-height: 58px;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid #e5e7eb;
            background: #fff;
        }

        .conversation-preview-title {
            font-size: .92rem;
            font-weight: 700;
            color: #1f2937;
        }

        .conversation-preview-subtitle {
            margin-top: 2px;
            font-size: .7rem;
            color: #94a3b8;
        }

        .session-meta-grid {
            padding: 10px 14px;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
            border-bottom: 1px solid #e5e7eb;
            background: #f8fafc;
        }

        .session-meta-item {
            min-width: 0;
            padding: 8px 9px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fff;
        }

        .session-meta-label {
            display: block;
            color: #94a3b8;
            font-size: .63rem;
            text-transform: uppercase;
            letter-spacing: .35px;
        }

        .session-meta-value {
            display: block;
            margin-top: 3px;
            overflow: hidden;
            color: #334155;
            font-size: .74rem;
            font-weight: 700;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .conversation-chat-box {
            flex: 1;
            min-height: 0;
            max-height: none;
            overflow-y: auto;
            border: 0;
            border-radius: 0;
            padding: 15px;
            background: #f8fafc;
        }

        @media (max-width: 1199.98px) {
            .conversation-workspace,
            .ticket-workspace {
                grid-template-columns: minmax(270px, 40%) minmax(0, 1fr);
            }

            .session-meta-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 767.98px) {
            .conversation-workspace,
            .ticket-workspace {
                height: auto;
                min-height: 0;
                grid-template-columns: 1fr;
            }

            .conversation-list-panel,
            .ticket-list-panel {
                max-height: 390px;
                border-right: 0;
                border-bottom: 1px solid #e5e7eb;
            }

            .conversation-preview-panel,
            .ticket-preview-panel {
                min-height: 560px;
            }

            .session-meta-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

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
           CHAT BOX
        ══════════════════════════════════════════ */
        .chat-box {
            display: flex; flex-direction: column; gap: 10px;
            max-height: 108vh; overflow-y: auto;
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
           TICKET DETAIL PREVIEW
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
        .content-divider {
            text-align: center; position: relative; margin: 16px 0;
        }
        .content-divider::before {
            content: ''; position: absolute; left: 0; right: 0;
            top: 50%; height: 1px; background: #e5e7eb;
        }
        .content-divider span {
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
           THÔNG BÁO TICKET CHỜ
        ══════════════════════════════════════════ */
        .ticket-pending-notice {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border: 1px solid #fde68a;
            border-radius: 12px;
            background:
                linear-gradient(135deg, #fffbeb 0%, #fff7ed 100%);
            box-shadow: 0 4px 14px rgba(180, 83, 9, .08);
            color: #78350f;
        }

        .ticket-pending-notice.d-none {
            display: none !important;
        }

        .ticket-pending-notice-icon {
            width: 38px;
            height: 38px;
            flex: 0 0 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: #fef3c7;
            color: #b45309;
            font-size: 1rem;
        }

        .ticket-pending-notice-content {
            min-width: 0;
            flex: 1;
        }

        .ticket-pending-notice-title {
            font-size: .82rem;
            font-weight: 750;
            color: #92400e;
            line-height: 1.25;
        }

        .ticket-pending-notice-text {
            margin-top: 2px;
            color: #a16207;
            font-size: .72rem;
            line-height: 1.4;
        }

        .ticket-pending-notice-count {
            display: inline-flex;
            min-width: 24px;
            height: 22px;
            padding: 0 7px;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: #b45309;
            color: #fff;
            font-size: .7rem;
            font-weight: 800;
        }

        .ticket-pending-notice-action {
            flex-shrink: 0;
            border-color: #f59e0b;
            color: #92400e;
            background: rgba(255, 255, 255, .72);
            font-size: .72rem;
            font-weight: 700;
        }

        .ticket-pending-notice-action:hover {
            border-color: #b45309;
            background: #fff;
            color: #78350f;
        }

        @media (max-width: 575.98px) {
            .ticket-pending-notice {
                align-items: flex-start;
                flex-wrap: wrap;
            }

            .ticket-pending-notice-action {
                width: 100%;
            }
        }

        /* "Có ticket" indicator in session row */
        .badge-ticket-count {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 3px 8px; border-radius: 999px;
            font-size: .72rem; font-weight: 700;
            background: #fef3c7; color: #92400e;
        }

        /* ══════════════════════════════════════════
           ANSWER LIBRARY
        ══════════════════════════════════════════ */
        .stat-teal { border-left: 4px solid #0f766e; }
        .stat-teal .stat-icon {
            background: #f0fdfa;
            color: #0f766e;
        }

        .answer-library-control {
            margin-top: 7px;
            padding: 7px 9px;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            background: #f8fafc;
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 2px 7px;
            align-items: start;
            color: #475569;
            font-size: .7rem;
            cursor: pointer;
        }

        .answer-library-control input {
            margin-top: 2px;
        }

        .answer-library-control span {
            font-weight: 700;
        }

        .answer-library-control small {
            grid-column: 2;
            color: #94a3b8;
            font-size: .64rem;
        }

        .answer-library-control.approved {
            border-color: #86efac;
            background: #f0fdf4;
            color: #166534;
        }

        .answer-library-control.warning {
            border-color: #fbbf24;
            background: #fffbeb;
            color: #92400e;
        }

        .answer-library-control.blocked {
            border-color: #fca5a5;
            background: #fef2f2;
            color: #991b1b;
            cursor: not-allowed;
        }

        .answer-library-control.blocked input {
            cursor: not-allowed;
        }

        .library-review-box {
            margin-bottom: 14px;
            padding: 11px 13px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #f8fafc;
            color: #475569;
            font-size: .76rem;
            line-height: 1.45;
        }

        .library-review-box ul {
            margin: 7px 0 0;
            padding-left: 18px;
        }

        .library-review-box li + li {
            margin-top: 3px;
        }

        .library-review-title {
            font-weight: 700;
        }

        .library-review-box.is-ready {
            border-color: #86efac;
            background: #f0fdf4;
            color: #166534;
        }

        .library-review-box.is-warning {
            border-color: #fcd34d;
            background: #fffbeb;
            color: #92400e;
        }

        .library-review-box.is-blocked {
            border-color: #fca5a5;
            background: #fef2f2;
            color: #991b1b;
        }

        .library-workspace {
            display: grid;
            grid-template-columns: minmax(330px, 38%) minmax(0, 1fr);
            height: calc(100vh - 365px);
            min-height: 620px;
            border-top: 1px solid #e5e7eb;
        }

        .library-list-panel,
        .library-preview-panel {
            min-width: 0;
            background: #fff;
        }

        .library-list-panel {
            display: flex;
            flex-direction: column;
            border-right: 1px solid #e5e7eb;
        }

        .library-list {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            background: #f8fafc;
        }

        .library-list-state {
            padding: 34px 18px;
            text-align: center;
            color: #64748b;
            font-size: .82rem;
        }

        .library-list-footer {
            min-height: 45px;
            padding: 7px 10px;
            display: flex;
            align-items: center;
            gap: 7px;
            border-top: 1px solid #e5e7eb;
            background: #fff;
        }

        .library-list-footer .page-info {
            margin-right: auto;
            color: #64748b;
            font-size: .69rem;
        }

        .library-item {
            margin: 9px 10px;
            padding: 11px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #fff;
            cursor: pointer;
            outline: none;
            transition: border-color .15s, box-shadow .15s, background .15s;
        }

        .library-item:hover {
            border-color: #5eead4;
            box-shadow: 0 3px 10px rgba(13, 148, 136, .08);
        }

        .library-item.active {
            border-color: #0d9488;
            background: #f0fdfa;
            box-shadow: 0 0 0 1px rgba(13, 148, 136, .12);
        }

        .library-item-top,
        .library-item-bottom {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .library-source,
        .library-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border-radius: 999px;
            padding: 2px 7px;
            font-size: .66rem;
            font-weight: 700;
        }

        .library-source.ai {
            background: #ede9fe;
            color: #6d28d9;
        }

        .library-source.staff {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .library-status {
            margin-left: auto;
        }

        .library-status.approved {
            background: #dcfce7;
            color: #166534;
        }

        .library-status.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .library-status.inactive {
            background: #e5e7eb;
            color: #475569;
        }

        .library-item-question {
            margin-top: 9px;
            color: #1f2937;
            font-size: .81rem;
            font-weight: 700;
            line-height: 1.45;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .library-item-answer {
            margin-top: 6px;
            color: #64748b;
            font-size: .73rem;
            line-height: 1.45;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
        }

        .library-item-bottom {
            margin-top: 9px;
            color: #94a3b8;
            font-size: .65rem;
        }

        .library-preview-panel {
            display: flex;
            flex-direction: column;
            min-height: 0;
            overflow: hidden;
        }

        #libraryPreviewContent:not(.d-none) {
            width: 100%;
            height: 100%;
            min-height: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .library-preview-empty {
            height: 100%;
            min-height: 420px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px;
            text-align: center;
            color: #94a3b8;
        }

        .library-preview-empty i {
            margin-bottom: 12px;
            font-size: 2.2rem;
        }

        .library-preview-header {
            padding: 13px 16px;
            border-bottom: 1px solid #e5e7eb;
            background: #f8fafc;
        }

        .library-preview-title {
            font-size: .92rem;
            font-weight: 800;
            color: #1f2937;
        }

        .library-preview-meta {
            margin-top: 7px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px 14px;
            color: #64748b;
            font-size: .7rem;
        }

        .library-preview-body {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            padding: 16px;
        }

        .library-preview-body textarea {
            resize: vertical;
            max-height: 360px;
        }

        .library-preview-actions {
            flex: 0 0 auto;
            min-height: 58px;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            border-top: 1px solid #e5e7eb;
            background: #fff;
            box-shadow: 0 -4px 12px rgba(15, 23, 42, .05);
            position: relative;
            z-index: 3;
        }

        .library-preview-actions #btnDeleteLibrary {
            margin-right: auto;
        }

        @media (max-width: 991.98px) {
            .library-workspace {
                grid-template-columns: 1fr;
                height: auto;
                min-height: 0;
            }

            .library-list-panel {
                border-right: 0;
                border-bottom: 1px solid #e5e7eb;
                max-height: 520px;
            }

            .library-preview-panel {
                min-height: 600px;
            }
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
            <div class="stat-card stat-ticket is-empty"
                 id="pendingTicketCard"
                 title="Hiện không có ticket chờ xử lý"
                 aria-disabled="true">
                <div class="stat-icon">
                    <i class="fas fa-inbox"></i>
                </div>
                <div>
                    <div class="stat-value" id="statPendingTickets">0</div>
                    <div class="stat-label" id="pendingTicketLabel">
                        Không có ticket chờ
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="stat-card stat-teal"
                 title="Số câu trả lời đang được chatbot tái sử dụng">
                <div class="stat-icon">
                    <i class="fas fa-book-open"></i>
                </div>
                <div>
                    <div class="stat-value" id="statApprovedAnswers">0</div>
                    <div class="stat-label">Câu trong kho</div>
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
            <button class="cb-tab-btn"
                    data-tab="library"
                    id="tabBtnLibrary">
                <i class="fas fa-book-open"></i> Kho câu hỏi
            </button>
            <button class="cb-tab-btn"
                    data-tab="openai"
                    id="tabBtnOpenai">
                <i class="fas fa-brain"></i> Quản lý OpenAI
            </button>
        </div>

        {{-- ── TAB: SESSIONS ──────────────────────── --}}
        <div class="cb-tab-pane active" id="pane-sessions">
            <div class="cb-card-header"
                 style="border-top:1px solid #e5e7eb;border-radius:0;">
                <span class="cb-card-title">
                    <i class="fas fa-filter me-1 text-muted"></i>Lọc phiên
                </span>

                <input type="date"
                       id="filterDateFrom"
                       class="form-control form-control-sm"
                       style="width:140px;"
                       title="Từ ngày">

                <input type="date"
                       id="filterDateTo"
                       class="form-control form-control-sm"
                       style="width:140px;"
                       title="Đến ngày">

                <input type="text"
                       id="filterIP"
                       class="form-control form-control-sm"
                       style="width:145px;"
                       placeholder="Lọc IP...">

                <button class="btn btn-primary btn-sm" id="btnFilter">
                    <i class="fas fa-search me-1"></i>Lọc
                </button>

                <button class="btn btn-outline-secondary btn-sm"
                        id="btnResetFilter"
                        title="Đặt lại">
                    <i class="fas fa-redo"></i>
                </button>
            </div>

            <div class="conversation-workspace">
                {{-- Block trái: danh sách phiên chat --}}
                <aside class="conversation-list-panel">
                    <div class="conversation-panel-heading">
                        <i class="fas fa-comments text-primary"></i>
                        Danh sách hội thoại
                        <span class="conversation-panel-count"
                              id="sessionListCount">0</span>
                    </div>

                    <div class="conversation-list" id="sessionList">
                        <div class="conversation-list-state">
                            <i class="fas fa-spinner fa-spin me-2"></i>
                            Đang tải phiên chat...
                        </div>
                    </div>

                    <div class="conversation-list-footer">
                        <span class="page-info" id="sessionPageInfo">0 phiên</span>
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary"
                                id="btnSessionPrev">‹</button>
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary"
                                id="btnSessionNext">›</button>
                    </div>
                </aside>

                {{-- Block phải: preview hội thoại --}}
                <section class="conversation-preview-panel">
                    <div class="session-preview-empty"
                         id="sessionPreviewEmpty">
                        <i class="fas fa-comments"></i>
                        <div class="fw-semibold mb-1">
                            Chưa chọn hội thoại
                        </div>
                        <div class="session-preview-empty-text small">
                            Chọn một phiên ở bên trái để xem toàn bộ nội dung.
                        </div>
                    </div>

                    <div class="d-none" id="sessionPreviewContent">
                        <div class="conversation-preview-header">
                            <div class="min-w-0">
                                <div class="conversation-preview-title">
                                    Hội thoại phiên #<span id="metaId">—</span>
                                </div>
                                <div class="conversation-preview-subtitle">
                                    Người dùng:
                                    <strong id="metaUser">Khách</strong>
                                    · IP:
                                    <strong id="metaIP">—</strong>
                                </div>
                            </div>

                            <button type="button"
                                    class="btn btn-outline-danger btn-sm ms-auto"
                                    id="btnDeleteSessionPreview">
                                <i class="fas fa-trash-alt me-1"></i>
                                Xóa phiên
                            </button>
                        </div>

                        <div class="session-meta-grid">
                            <div class="session-meta-item">
                                <span class="session-meta-label">
                                    Mã đối chiếu
                                </span>
                                <strong class="session-meta-value"
                                        id="metaThread"
                                        title="">—</strong>
                            </div>

                            <div class="session-meta-item">
                                <span class="session-meta-label">
                                    Bắt đầu
                                </span>
                                <strong class="session-meta-value"
                                        id="metaStart">—</strong>
                            </div>

                            <div class="session-meta-item">
                                <span class="session-meta-label">
                                    Hoạt động cuối
                                </span>
                                <strong class="session-meta-value"
                                        id="metaLast">—</strong>
                            </div>

                            <div class="session-meta-item">
                                <span class="session-meta-label">
                                    Tin nhắn / Ticket
                                </span>
                                <strong class="session-meta-value">
                                    <span id="metaMessageCount">0</span> tin
                                    ·
                                    <span id="metaTicketCount">0</span> ticket
                                </strong>
                            </div>
                        </div>

                        <div id="chatBox"
                             class="chat-box conversation-chat-box">
                            <div class="text-center text-muted py-5">
                                Đang tải...
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        {{-- ── TAB: TICKETS ───────────────────────── --}}
        <div class="cb-tab-pane" id="pane-tickets">
            <div class="cb-card-header"
                 style="border-top:1px solid #e5e7eb;border-radius:0;">
                <span class="cb-card-title">
                    <i class="fas fa-headset me-1 text-primary"></i>
                    Yêu cầu tư vấn
                </span>

                <select id="ticketStatusFilter"
                        class="form-control form-control-sm"
                        style="width:140px;">
                    <option value="">Tất cả</option>
                    <option value="pending">Đang chờ</option>
                    <option value="answered">Đã trả lời</option>
                    <option value="closed">Đã đóng</option>
                </select>

                <input type="text"
                       id="ticketKeyword"
                       class="form-control form-control-sm"
                       style="width:220px;"
                       placeholder="Tìm mã hoặc câu hỏi...">

                <button class="btn btn-primary btn-sm"
                        id="btnFilterTickets">
                    <i class="fas fa-search me-1"></i>Lọc
                </button>

                <button class="btn btn-outline-secondary btn-sm"
                        id="btnReloadTickets"
                        title="Làm mới">
                    <i class="fas fa-sync"></i>
                </button>
            </div>

            <div id="ticketPendingAlert"
                 class="ticket-pending-notice d-none mx-3 mt-3 mb-0"
                 role="status"
                 aria-live="polite"
                 aria-hidden="true">
                <div class="ticket-pending-notice-icon">
                    <i class="fas fa-headset"></i>
                </div>

                <div class="ticket-pending-notice-content">
                    <div class="ticket-pending-notice-title">
                        Có yêu cầu tư vấn cần xử lý
                    </div>
                    <div class="ticket-pending-notice-text">
                        Hiện còn
                        <span class="ticket-pending-notice-count"
                              id="ticketPendingAlertCount">0</span>
                        ticket chưa được trả lời.
                    </div>
                </div>

                <button type="button"
                        class="btn btn-sm ticket-pending-notice-action"
                        id="btnOpenPendingTickets">
                    Xem ngay
                    <i class="fas fa-arrow-right ms-1"></i>
                </button>
            </div>

            <div class="ticket-workspace">
                {{-- Block trái: danh sách ticket đã phân trang --}}
                <aside class="ticket-list-panel">
                    <div class="conversation-panel-heading">
                        <i class="fas fa-inbox text-primary"></i>
                        Danh sách yêu cầu
                        <span class="conversation-panel-count"
                              id="ticketListCount">0</span>
                    </div>

                    <div class="ticket-list" id="ticketList">
                        <div class="ticket-list-state">
                            <i class="fas fa-spinner fa-spin me-2"></i>
                            Đang tải yêu cầu tư vấn...
                        </div>
                    </div>

                    <div class="ticket-list-footer">
                        <span class="page-info" id="ticketPageInfo">
                            0 ticket
                        </span>
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary"
                                id="btnTicketPrev">‹</button>
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary"
                                id="btnTicketNext">›</button>
                    </div>
                </aside>

                {{-- Block phải: xem và trả lời ticket trực tiếp --}}
                <section class="ticket-preview-panel">
                    <div class="ticket-preview-empty"
                         id="ticketPreviewEmpty">
                        <i class="fas fa-headset"></i>
                        <div class="fw-semibold mb-1">
                            Chưa chọn yêu cầu tư vấn
                        </div>
                        <div class="ticket-preview-empty-text small">
                            Chọn một ticket ở bên trái để xem và trả lời.
                        </div>
                    </div>

                    <div class="d-none" id="ticketPreviewContent">
                        <input type="hidden" id="ticketCurrentId">

                        <div class="ticket-preview-header">
                            <div class="min-w-0">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="ticket-preview-code"
                                          id="ticketPreviewCode">—</span>
                                    <span id="ticketPreviewStatus"></span>
                                </div>
                                <div class="ticket-preview-meta"
                                     id="ticketPreviewSession"></div>
                            </div>
                        </div>

                        <div class="ticket-preview-body">
                            <div class="mb-3">
                                <div class="fw-semibold text-muted small mb-1">
                                    <i class="fas fa-history me-1"></i>
                                    Lịch sử hội thoại
                                </div>
                                <div id="ticketChatBox"
                                     class="chat-box ticket-history-box">
                                    <div class="text-center text-muted py-3">
                                        Đang tải lịch sử...
                                    </div>
                                </div>
                            </div>

                            <div class="content-divider">
                                <span>Nội dung tư vấn</span>
                            </div>

                            <div class="mb-3">
                                <div class="fw-semibold small mb-1">
                                    Câu hỏi người dùng
                                </div>
                                <div class="ticket-box ticket-question-box"
                                     id="ticketPreviewQuestion"></div>
                            </div>

                            <div class="mb-3 d-none" id="ticketBotNoteWrap">
                                <div class="fw-semibold small mb-1">
                                    Ghi chú từ chatbot
                                </div>
                                <div class="ticket-box ticket-note-box"
                                     id="ticketPreviewBotNote"></div>
                            </div>

                            <div class="mb-3 d-none" id="ticketOldAnswerWrap">
                                <div class="fw-semibold small mb-1">
                                    Câu trả lời hiện tại
                                </div>
                                <div class="ticket-box ticket-answer-box"
                                     id="ticketPreviewOldAnswer"></div>
                            </div>

                            <div class="mb-2">
                                <label class="fw-semibold small mb-1"
                                       for="ticketStaffAnswer">
                                    Nội dung trả lời của nhân viên
                                </label>
                                <textarea id="ticketStaffAnswer"
                                          class="form-control"
                                          rows="6"
                                          placeholder="Nhập nội dung trả lời..."></textarea>
                            </div>

                            <div class="form-check mt-3">
                                <input class="form-check-input"
                                       type="checkbox"
                                       id="ticketUseAsSample">
                                <label class="form-check-label small fw-semibold text-primary"
                                       for="ticketUseAsSample">
                                    Cho phép chatbot sử dụng làm câu trả lời mẫu
                                </label>
                                <div class="text-muted mt-1"
                                     style="font-size:.72rem;">
                                    Không chọn nếu câu hỏi hoặc câu trả lời chứa
                                    dữ liệu riêng tư của thí sinh.
                                </div>
                            </div>
                        </div>

                        <div class="ticket-preview-actions">
                            <button type="button"
                                    class="btn btn-outline-danger btn-sm"
                                    id="btnCloseTicket">
                                <i class="fas fa-times me-1"></i>
                                Đóng ticket
                            </button>

                            <span class="text-muted small ms-auto me-2">
                                Phản hồi sẽ tự hiển thị nếu người dùng còn mở chat.
                            </span>

                            <button type="button"
                                    class="btn btn-primary btn-sm"
                                    id="btnSubmitTicketAnswer">
                                <i class="fas fa-paper-plane me-1"></i>
                                Gửi trả lời
                            </button>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        {{-- ── TAB: KHO CÂU HỎI ───────────────────── --}}
        <div class="cb-tab-pane" id="pane-library">
            <div class="cb-card-header"
                 style="border-top:1px solid #e5e7eb;border-radius:0;">
                <span class="cb-card-title">
                    <i class="fas fa-book-open me-1 text-success"></i>
                    Kho câu hỏi - câu trả lời
                </span>

                <select id="librarySourceFilter"
                        class="form-control form-control-sm"
                        style="width:135px;">
                    <option value="">Tất cả nguồn</option>
                    <option value="ai">AI trả lời</option>
                    <option value="staff">Nhân viên trả lời</option>
                </select>

                <input type="text"
                       id="libraryKeyword"
                       class="form-control form-control-sm"
                       style="width:230px;"
                       placeholder="Tìm câu hỏi hoặc câu trả lời...">

                <button class="btn btn-primary btn-sm"
                        id="btnFilterLibrary">
                    <i class="fas fa-search me-1"></i>Lọc
                </button>

                <button class="btn btn-outline-secondary btn-sm"
                        id="btnReloadLibrary"
                        title="Làm mới">
                    <i class="fas fa-sync"></i>
                </button>
            </div>

            <div class="library-workspace">
                <aside class="library-list-panel">
                    <div class="conversation-panel-heading">
                        <i class="fas fa-database text-success"></i>
                        Danh sách câu hỏi
                        <span class="conversation-panel-count"
                              id="libraryListCount">0</span>
                    </div>

                    <div class="library-list" id="libraryList">
                        <div class="library-list-state">
                            <i class="fas fa-spinner fa-spin me-2"></i>
                            Đang tải kho câu hỏi...
                        </div>
                    </div>

                    <div class="library-list-footer">
                        <span class="page-info" id="libraryPageInfo">
                            0 câu
                        </span>
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary"
                                id="btnLibraryPrev">‹</button>
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary"
                                id="btnLibraryNext">›</button>
                    </div>
                </aside>

                <section class="library-preview-panel">
                    <div class="library-preview-empty"
                         id="libraryPreviewEmpty">
                        <i class="fas fa-book-open"></i>
                        <div class="fw-semibold mb-1">
                            Chưa chọn câu hỏi
                        </div>
                        <div class="library-preview-empty-text small">
                            Chọn một câu ở bên trái để xem hoặc chỉnh sửa.
                        </div>
                    </div>

                    <div class="d-none" id="libraryPreviewContent">
                        <input type="hidden" id="libraryCurrentId">

                        <div class="library-preview-header">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <div class="library-preview-title"
                                     id="libraryPreviewTitle">
                                    Câu hỏi mẫu
                                </div>
                                <div id="libraryPreviewSource"></div>
                            </div>
                            <div class="library-preview-meta"
                                 id="libraryPreviewMeta"></div>
                        </div>

                        <div class="library-preview-body">
                            <div id="libraryValidationBox"
                                 class="library-review-box d-none"></div>

                            <div class="mb-3">
                                <label class="fw-semibold small mb-1"
                                       for="libraryPreviewQuestion">
                                    Câu hỏi chuẩn
                                </label>
                                <textarea id="libraryPreviewQuestion"
                                          class="form-control"
                                          rows="5"
                                          placeholder="Nhập câu hỏi..."></textarea>
                                <div class="text-muted mt-1"
                                     style="font-size:.72rem;">
                                    Có thể chỉnh lại câu hỏi cho rõ nghĩa.
                                    Chỉ dữ liệu cá nhân và nội dung ticket mới bị chặn cứng.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="fw-semibold small mb-1"
                                       for="libraryPreviewAnswer">
                                    Câu trả lời dùng lại
                                </label>
                                <textarea id="libraryPreviewAnswer"
                                          class="form-control"
                                          rows="10"
                                          placeholder="Nhập câu trả lời..."></textarea>
                                <div class="text-muted mt-1"
                                     style="font-size:.72rem;">
                                    Câu đã lưu sẽ được dùng lại khi khớp câu hỏi.
                                    Góp ý văn phong không làm mất quyền tái sử dụng.
                                </div>
                            </div>

                        </div>

                        <div class="library-preview-actions">
                            <button type="button"
                                    class="btn btn-outline-danger btn-sm"
                                    id="btnDeleteLibrary">
                                <i class="fas fa-trash-alt me-1"></i>
                                Xóa khỏi kho
                            </button>



                            <button type="button"
                                    class="btn btn-success btn-sm"
                                    id="btnSaveLibrary">
                                <i class="fas fa-save me-1"></i>
                                Lưu thay đổi
                            </button>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        {{-- ── TAB: OPENAI ──────────────────────── --}}
        <div class="cb-tab-pane" id="pane-openai">
            <div class="cb-card-header" style="border-top:1px solid #e5e7eb;border-radius:0;display:flex;justify-content:space-between;align-items:center;">
                <span class="cb-card-title">
                    <i class="fas fa-brain me-1 text-primary"></i>
                    Quản lý OpenAI
                </span>
                <div>
                    <input type="file" id="uploadOpenAiFile" class="d-none" accept=".pdf,.txt,.docx,.json">
                    <button id="chooseOpenAiFile" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-cloud-upload-alt me-1"></i> Tải tài liệu
                    </button>
                </div>
            </div>
            <div class="p-4" style="background-color: #f8f9fc;">
                <div class="row g-3" id="admissionOpenAiPage">
                    <div class="col-xl-6">
                        <section class="admission-card h-100" style="background:#fff;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,.08);padding:20px;">
                            <div class="admission-card-header mb-3">
                                <h2 class="admission-card-title" style="font-size:1.1rem;margin:0;font-weight:600;">Kịch bản Prompt</h2>
                                <div class="admission-card-subtitle text-muted small mt-1">Instructions dùng chung cho trợ lý tuyển sinh</div>
                            </div>
                            <div class="admission-card-body">
                                <textarea id="openaiPrompt" class="form-control mb-3" rows="18" placeholder="Đang tải instructions..."></textarea>
                                <button id="saveOpenAiPrompt" class="btn btn-primary w-100">
                                    <i class="fas fa-save me-1"></i> Lưu và đồng bộ
                                </button>
                            </div>
                        </section>
                    </div>
                    <div class="col-xl-6">
                        <section class="admission-card h-100" style="background:#fff;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,.08);padding:20px;">
                            <div class="admission-card-header mb-3">
                                <h2 class="admission-card-title" style="font-size:1.1rem;margin:0;font-weight:600;">Tài liệu Vector Store</h2>
                                <div class="admission-card-subtitle text-muted small mt-1">Danh sách file đang được OpenAI sử dụng</div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead><tr><th>Tên file</th><th>Dung lượng</th><th>Thao tác</th></tr></thead>
                                    <tbody id="openaiFileRows">
                                        <tr><td colspan="3" class="text-center text-muted py-3">Đang đồng bộ danh sách file...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('js')
    @php
        $adviseJsVersion = @filemtime(
            public_path('js/admins/advise.js')
        ) ?: 1;
    @endphp
    <script src="/js/admins/advise.js?v={{ $adviseJsVersion }}"></script>
    <script src="{{ asset('js/admins/admission/openai.js') }}"></script>
@endsection