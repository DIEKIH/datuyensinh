@extends('admins.pages.admission.layout')

@section('admission_title', 'Duyệt nội dung AI')
@section('admission_description', 'Kiểm tra, chỉnh sửa hoặc từ chối câu trả lời do AI soạn trước khi gửi đến người dùng.')

@section('admission_actions')
    <button id="runNurtureCommand" class="btn btn-warning">
        <i class="fas fa-magic me-1"></i> Chạy nurturing
    </button>

    <button id="reloadApprovals" class="btn btn-outline-primary">
        <i class="fas fa-sync-alt me-1"></i> Làm mới
    </button>
@endsection

@section('admission_content')
<style>
    .approval-page-card {
        /*
         * Không cắt phần chân của workspace khi layout cha
         * đang dùng chiều cao giới hạn.
         */
        overflow: visible;
    }

    .approval-workspace {
        display: grid;
        grid-template-columns: minmax(310px, 36%) minmax(0, 1fr);
        height: calc(100vh - 205px);
        min-height: 480px;
        max-height: 760px;
        overflow: hidden;
        border-top: 1px solid #e5e7eb;
    }

    .approval-list-panel,
    .approval-preview-panel {
        min-width: 0;
        min-height: 0;
        height: 100%;
        overflow: hidden;
        background: #fff;
    }

    .approval-list-panel {
        display: flex;
        flex-direction: column;
        border-right: 1px solid #e5e7eb;
    }

    .approval-panel-heading {
        flex: 0 0 auto;
        min-height: 52px;
        padding: 12px 14px;
        display: flex;
        align-items: center;
        gap: 8px;
        border-bottom: 1px solid #e5e7eb;
        background: #f9fafb;
        color: #374151;
        font-size: .86rem;
        font-weight: 700;
    }

    .approval-panel-count {
        margin-left: auto;
        min-width: 26px;
        height: 22px;
        padding: 0 7px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: #fef3c7;
        color: #92400e;
        font-size: .72rem;
    }

    .approval-list {
        flex: 1 1 auto;
        min-height: 0;
        overflow-x: hidden;
        overflow-y: auto;
        overscroll-behavior: contain;
        scrollbar-gutter: stable;
        background: #f8fafc;
    }

    .approval-list-state {
        padding: 38px 18px;
        text-align: center;
        color: #64748b;
        font-size: .82rem;
    }

    .approval-item {
        margin: 9px 10px;
        padding: 11px 12px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #fff;
        cursor: pointer;
        outline: none;
        transition: border-color .15s, box-shadow .15s, background .15s;
    }

    .approval-item:hover {
        border-color: #93c5fd;
        box-shadow: 0 3px 10px rgba(37, 99, 235, .08);
    }

    .approval-item:focus-visible {
        box-shadow: 0 0 0 3px rgba(59, 130, 246, .2);
    }

    .approval-item.active {
        border-color: #3b82f6;
        background: #eff6ff;
        box-shadow: 0 0 0 1px rgba(59, 130, 246, .12);
    }

    .approval-item-top {
        display: flex;
        align-items: flex-start;
        gap: 9px;
    }

    .approval-avatar {
        width: 32px;
        height: 32px;
        flex: 0 0 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 9px;
        background: #dbeafe;
        color: #2563eb;
    }

    .approval-item-name {
        min-width: 0;
        color: #1f2937;
        font-size: .82rem;
        font-weight: 700;
    }

    .approval-item-contact {
        margin-top: 2px;
        overflow: hidden;
        color: #94a3b8;
        font-size: .67rem;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .approval-channel-badge {
        margin-left: auto;
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        padding: 3px 8px;
        border-radius: 999px;
        background: #e2e8f0;
        color: #475569;
        font-size: .66rem;
        font-weight: 700;
    }

    .approval-item-question {
        margin-top: 9px;
        overflow: hidden;
        color: #475569;
        font-size: .77rem;
        line-height: 1.45;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
    }

    .approval-item-bottom {
        margin-top: 9px;
        display: flex;
        align-items: center;
        gap: 8px;
        color: #94a3b8;
        font-size: .66rem;
    }

    .approval-list-footer {
        position: relative;
        z-index: 5;
        flex: 0 0 auto;
        min-height: 46px;
        padding: 7px 10px;
        display: flex;
        align-items: center;
        gap: 7px;
        border-top: 1px solid #e5e7eb;
        background: #fff;
        box-shadow: 0 -3px 10px rgba(15, 23, 42, .04);
    }

    .approval-page-info {
        margin-right: auto;
        color: #64748b;
        font-size: .69rem;
    }

    .approval-preview-panel {
        display: flex;
        flex-direction: column;
        min-height: 0;
        height: 100%;
        overflow: hidden;
    }

    .approval-preview-empty {
        height: 100%;
        min-height: 420px;
        padding: 30px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: #94a3b8;
    }

    .approval-preview-empty i {
        margin-bottom: 12px;
        color: #cbd5e1;
        font-size: 2.2rem;
    }

    #approvalPreviewContent:not(.d-none) {
        flex: 1 1 auto;
        width: 100%;
        height: auto;
        min-height: 0;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .approval-preview-header {
        flex: 0 0 auto;
        min-height: 66px;
        padding: 11px 15px;
        display: flex;
        align-items: center;
        gap: 12px;
        border-bottom: 1px solid #e5e7eb;
        background: #fff;
    }

    .approval-preview-name {
        color: #1f2937;
        font-size: .94rem;
        font-weight: 750;
    }

    .approval-preview-contact {
        margin-top: 4px;
        display: flex;
        flex-wrap: wrap;
        gap: 5px 12px;
        color: #94a3b8;
        font-size: .69rem;
    }

    .approval-preview-body {
        flex: 1 1 auto;
        min-height: 0;
        overflow-x: hidden;
        overflow-y: auto;
        overscroll-behavior: contain;
        scrollbar-gutter: stable;
        padding: 16px;
        background: #f8fafc;
    }

    .approval-question-box {
        padding: 12px 14px;
        border: 1px solid #bfdbfe;
        border-radius: 10px;
        background: #eff6ff;
        color: #1e3a8a;
        font-size: .84rem;
        line-height: 1.6;
        white-space: pre-wrap;
        word-break: break-word;
    }

    .approval-preview-body textarea {
        resize: vertical;
    }

    .approval-preview-actions {
        position: relative;
        z-index: 6;
        flex: 0 0 auto;
        min-height: 60px;
        padding: 10px 14px;
        display: flex;
        align-items: center;
        gap: 8px;
        border-top: 1px solid #e5e7eb;
        background: #fff;
        box-shadow: 0 -4px 12px rgba(15, 23, 42, .07);
    }

    .approval-preview-actions .btn-approve {
        margin-left: auto;
    }

    @media (max-height: 760px) and (min-width: 992px) {
        .approval-workspace {
            height: calc(100vh - 175px);
            min-height: 430px;
        }

        .approval-preview-body textarea#approvalAiAnswer {
            min-height: 150px;
        }
    }

    @media (max-width: 991.98px) {
        .approval-workspace {
            height: auto;
            min-height: 0;
            max-height: none;
            overflow: visible;
            grid-template-columns: 1fr;
        }

        .approval-list-panel {
            height: 460px;
            max-height: 460px;
            border-right: 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .approval-preview-panel {
            height: 590px;
            min-height: 590px;
        }
    }
</style>

<section class="admission-card approval-page-card" id="admissionApprovalsPage">
    <div class="admission-card-header">
        <div>
            <h2 class="admission-card-title">Hàng chờ Human-in-the-Loop</h2>
            <div class="admission-card-subtitle">
                Chỉ nội dung có trạng thái pending được hiển thị tại đây
            </div>
        </div>
    </div>

    <div class="approval-workspace">
        <aside class="approval-list-panel">
            <div class="approval-panel-heading">
                <i class="fas fa-list-check text-primary"></i>
                Danh sách chờ duyệt
                <span class="approval-panel-count" id="approvalListCount">0</span>
            </div>

            <div class="approval-list" id="approvalList">
                <div class="approval-list-state">
                    <i class="fas fa-spinner fa-spin me-2"></i>
                    Đang tải nội dung chờ duyệt...
                </div>
            </div>

            <div class="approval-list-footer">
                <span class="approval-page-info" id="approvalsPageInfo">0 nội dung</span>

                <button type="button"
                        class="btn btn-sm btn-outline-secondary"
                        id="btnApprovalPrev"
                        title="Trang trước">
                    <i class="fas fa-chevron-left"></i>
                </button>

                <button type="button"
                        class="btn btn-sm btn-outline-secondary"
                        id="btnApprovalNext"
                        title="Trang sau">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </aside>

        <section class="approval-preview-panel">
            <div class="approval-preview-empty" id="approvalPreviewEmpty">
                <i class="fas fa-clipboard-check"></i>
                <div class="fw-semibold mb-1">Chưa chọn nội dung</div>
                <div class="small">
                    Chọn một nội dung ở bên trái để kiểm tra và xử lý.
                </div>
            </div>

            <div class="d-none" id="approvalPreviewContent">
                <input type="hidden" id="approvalCurrentId">

                <div class="approval-preview-header">
                    <div class="min-w-0">
                        <div class="approval-preview-name" id="approvalPreviewName">—</div>
                        <div class="approval-preview-contact">
                            <span>
                                <i class="fas fa-envelope me-1"></i>
                                <span id="approvalPreviewEmail">—</span>
                            </span>
                            <span>
                                <i class="fas fa-phone me-1"></i>
                                <span id="approvalPreviewPhone">—</span>
                            </span>
                        </div>
                    </div>

                    <span class="approval-channel-badge" id="approvalPreviewChannel">—</span>
                </div>

                <div class="approval-preview-body">
                    <div class="mb-3">
                        <div class="fw-bold small mb-1">Câu hỏi của khách hàng</div>
                        <div class="approval-question-box" id="approvalPreviewQuestion"></div>
                    </div>

                    <div class="mb-3">
                        <label class="fw-bold small mb-1" for="approvalAiAnswer">
                            Câu trả lời AI
                        </label>
                        <textarea id="approvalAiAnswer"
                                  class="form-control"
                                  rows="10"
                                  placeholder="Nội dung AI đề xuất..."></textarea>
                    </div>

                    <div class="mb-1">
                        <label class="fw-bold small mb-1" for="approvalAdminFeedback">
                            Ghi chú để AI viết lại
                        </label>
                        <textarea id="approvalAdminFeedback"
                                  class="form-control"
                                  rows="3"
                                  placeholder="Ví dụ: Viết ngắn gọn hơn, bổ sung thời gian tuyển sinh..."></textarea>
                    </div>
                </div>

                <div class="approval-preview-actions">
                    <button type="button"
                            class="btn btn-outline-danger btn-sm btn-approval-action"
                            data-action="reject">
                        <i class="fas fa-times me-1"></i>
                        Từ chối
                    </button>

                    <button type="button"
                            class="btn btn-warning btn-sm btn-approval-action"
                            data-action="rewrite">
                        <i class="fas fa-robot me-1"></i>
                        Viết lại
                    </button>

                    <button type="button"
                            class="btn btn-success btn-sm btn-approval-action btn-approve"
                            data-action="approve">
                        <i class="fas fa-check me-1"></i>
                        Duyệt và gửi
                    </button>
                </div>
            </div>
        </section>
    </div>
</section>
@endsection

@section('admission_js')
    @php
        $approvalsJsVersion = @filemtime(
            public_path('js/admins/admission/approvals.js')
        ) ?: '1';
    @endphp

    <script src="{{ asset('js/admins/admission/approvals.js') }}?v={{ $approvalsJsVersion }}"></script>
@endsection