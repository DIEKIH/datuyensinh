@extends('admins.pages.admission.layout')

@section('admission_title', 'Duyệt bài đăng')
@section(
    'admission_description',
    'Kiểm tra, chỉnh sửa hoặc từ chối nội dung mạng xã hội trước khi n8n tiếp tục đăng bài.'
)

@section('admission_actions')
    <button id="reloadSocialPostApprovals" class="btn btn-outline-primary">
        <i class="fas fa-sync-alt me-1"></i>
        Làm mới
    </button>
@endsection

@section('admission_content')
<style>
    .post-approval-card { overflow: hidden; }
    .post-approval-workspace {
        display: grid;
        grid-template-columns: minmax(310px, 36%) minmax(0, 1fr);
        height: calc(100vh - 295px);
        min-height: 620px;
        border-top: 1px solid #e5e7eb;
    }
    .post-approval-list-panel,
    .post-approval-preview-panel { min-width: 0; background: #fff; }
    .post-approval-list-panel {
        display: flex;
        flex-direction: column;
        border-right: 1px solid #e5e7eb;
    }
    .post-approval-heading {
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
    .post-approval-count {
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
    .post-approval-list {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        background: #f8fafc;
    }
    .post-approval-state {
        padding: 38px 18px;
        text-align: center;
        color: #64748b;
        font-size: .82rem;
    }
    .post-approval-item {
        margin: 9px 10px;
        padding: 11px 12px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #fff;
        cursor: pointer;
        transition: border-color .15s, box-shadow .15s, background .15s;
    }
    .post-approval-item:hover {
        border-color: #93c5fd;
        box-shadow: 0 3px 10px rgba(37, 99, 235, .08);
    }
    .post-approval-item.active {
        border-color: #3b82f6;
        background: #eff6ff;
        box-shadow: 0 0 0 1px rgba(59, 130, 246, .12);
    }
    .post-approval-item-title {
        display: -webkit-box;
        overflow: hidden;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
        color: #111827;
        font-size: .88rem;
        font-weight: 700;
    }
    .post-approval-item-caption {
        margin-top: 5px;
        display: -webkit-box;
        overflow: hidden;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
        color: #64748b;
        font-size: .78rem;
    }
    .post-approval-list-footer {
        min-height: 50px;
        padding: 8px 12px;
        display: flex;
        align-items: center;
        gap: 6px;
        border-top: 1px solid #e5e7eb;
        background: #fff;
    }
    .post-approval-page-info {
        margin-right: auto;
        color: #64748b;
        font-size: .76rem;
    }
    .post-approval-preview-panel {
        position: relative;
        min-height: 0;
        overflow-y: auto;
    }
    .post-approval-empty {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #94a3b8;
        text-align: center;
    }
    .post-approval-empty > i {
        margin-bottom: 12px;
        font-size: 2.4rem;
    }
    .post-approval-preview-header {
        padding: 15px 18px;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        border-bottom: 1px solid #e5e7eb;
    }
    .post-approval-preview-title {
        color: #111827;
        font-size: 1rem;
        font-weight: 700;
    }
    .post-approval-preview-meta {
        margin-top: 4px;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        color: #64748b;
        font-size: .76rem;
    }
    .post-approval-preview-body { padding: 18px; }
    .post-approval-source {
        padding: 10px 12px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #f8fafc;
        overflow-wrap: anywhere;
    }
    .post-approval-images {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 10px;
    }
    .post-approval-images img {
        width: 100%;
        height: 120px;
        object-fit: cover;
        border: 1px solid #e5e7eb;
        border-radius: 9px;
        background: #f1f5f9;
    }
    .post-approval-actions {
        position: sticky;
        bottom: 0;
        padding: 12px 18px;
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        border-top: 1px solid #e5e7eb;
        background: rgba(255, 255, 255, .96);
        backdrop-filter: blur(6px);
    }
    @media (max-width: 991.98px) {
        .post-approval-workspace {
            display: block;
            height: auto;
            min-height: 0;
        }
        .post-approval-list-panel { border-right: 0; }
        .post-approval-list { max-height: 390px; }
        .post-approval-preview-panel { min-height: 560px; }
    }
</style>

<section class="admission-card post-approval-card" id="socialPostApprovalsPage">
    <div class="admission-card-header">
        <div>
            <h2 class="admission-card-title">Hàng chờ duyệt bài đăng</h2>
            <div class="admission-card-subtitle">
                Chỉ bài có trạng thái pending được đưa vào danh sách này.
            </div>
        </div>
    </div>

    <div class="post-approval-workspace">
        <aside class="post-approval-list-panel">
            <div class="post-approval-heading">
                <i class="fas fa-list-check text-primary"></i>
                Danh sách chờ duyệt
                <span class="post-approval-count" id="socialPostApprovalCount">0</span>
            </div>

            <div class="post-approval-list" id="socialPostApprovalList">
                <div class="post-approval-state">
                    <i class="fas fa-spinner fa-spin me-2"></i>
                    Đang tải bài viết chờ duyệt...
                </div>
            </div>

            <div class="post-approval-list-footer">
                <span class="post-approval-page-info" id="socialPostApprovalPageInfo">0 bài</span>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="socialPostApprovalPrev">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="socialPostApprovalNext">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </aside>

        <section class="post-approval-preview-panel">
            <div class="post-approval-empty" id="socialPostApprovalEmpty">
                <i class="fas fa-newspaper"></i>
                <div class="fw-semibold mb-1">Chưa chọn bài viết</div>
                <div class="small">Chọn một bài ở bên trái để kiểm tra và xử lý.</div>
            </div>

            <div class="d-none" id="socialPostApprovalContent">
                <input type="hidden" id="socialPostApprovalCurrentId">

                <div class="post-approval-preview-header">
                    <div class="min-w-0">
                        <div class="post-approval-preview-title" id="socialPostApprovalTitle">—</div>
                        <div class="post-approval-preview-meta">
                            <span><i class="fas fa-clock me-1"></i><span id="socialPostApprovalCreatedAt">—</span></span>
                            <span><i class="fas fa-diagram-project me-1"></i><span id="socialPostApprovalExecution">—</span></span>
                        </div>
                    </div>
                    <div id="socialPostApprovalPlatforms"></div>
                </div>

                <div class="post-approval-preview-body">
                    <div class="mb-3">
                        <div class="fw-bold small mb-1">Bài viết nguồn</div>
                        <div class="post-approval-source" id="socialPostApprovalSource"></div>
                    </div>

                    <div class="mb-3 d-none" id="socialPostApprovalImagesBlock">
                        <div class="fw-bold small mb-2">Ảnh đính kèm</div>
                        <div class="post-approval-images" id="socialPostApprovalImages"></div>
                    </div>

                    <div class="mb-3">
                        <label class="fw-bold small mb-1" for="socialPostApprovalCaption">
                            Nội dung đăng
                        </label>
                        <textarea id="socialPostApprovalCaption" class="form-control" rows="11"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="fw-bold small mb-1" for="socialPostApprovalHashtags">
                            Hashtag
                        </label>
                        <textarea id="socialPostApprovalHashtags" class="form-control" rows="2"></textarea>
                    </div>

                    <div class="mb-1">
                        <label class="fw-bold small mb-1" for="socialPostApprovalReason">
                            Lý do từ chối
                        </label>
                        <textarea
                            id="socialPostApprovalReason"
                            class="form-control"
                            rows="3"
                            placeholder="Chỉ nhập khi từ chối bài viết."
                        ></textarea>
                    </div>
                </div>

                <div class="post-approval-actions">
                    <button type="button" class="btn btn-outline-danger btn-sm social-post-approval-action" data-action="reject">
                        <i class="fas fa-times me-1"></i>
                        Từ chối
                    </button>
                    <button type="button" class="btn btn-success btn-sm social-post-approval-action" data-action="approve">
                        <i class="fas fa-check me-1"></i>
                        Duyệt và đăng
                    </button>
                </div>
            </div>
        </section>
    </div>
</section>
@endsection

@section('admission_js')
    @php
        $socialPostApprovalsJsVersion = @filemtime(
            public_path('js/admins/admission/social_post_approvals.js')
        ) ?: '1';
    @endphp

    <script src="{{ asset('js/admins/admission/social_post_approvals.js') }}?v={{ $socialPostApprovalsJsVersion }}"></script>
@endsection
