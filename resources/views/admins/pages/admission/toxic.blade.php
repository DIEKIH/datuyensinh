{{-- @extends('admins.pages.admission.layout')

@section('admission_title', 'Cảnh báo mạng xã hội')
@section(
    'admission_description',
    'Theo dõi các bình luận bị AI đánh dấu là xúc phạm, spam hoặc nội dung cạnh tranh cần cán bộ kiểm tra.'
)

@section('admission_actions')
    <button
        class="btn btn-outline-secondary btn-toxic-filter"
        data-status="all"
    >
        Tất cả
    </button>

    <button
        class="btn btn-outline-danger btn-toxic-filter"
        data-status="pending"
    >
        Chờ xử lý
    </button>
@endsection

@section('admission_content')
<section
    class="admission-card"
    id="admissionToxicPage"
    data-status="pending"
>
    <div class="admission-card-header">
        <div>
            <h2 class="admission-card-title">
                Bình luận cần rà soát
            </h2>

            <div class="admission-card-subtitle">
                Bình luận tiêu cực được yêu cầu ẩn trên Facebook
                trước khi chuyển vào danh sách để cán bộ xem xét.
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table admission-table">
            <thead>
                <tr>
                    <th>Nguồn và bài viết</th>
                    <th>Người đăng</th>
                    <th>Nội dung bình luận</th>
                    <th>Nhận định AI</th>
                    <th>Thời gian</th>
                    <th>Thao tác</th>
                </tr>
            </thead>

            <tbody id="toxicCommentsBody">
                <tr>
                    <td
                        colspan="6"
                        class="admission-loading"
                    >
                        Đang tải cảnh báo...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div
        class="admission-card-body pt-2"
        id="toxicPagination"
    ></div>
</section>
@endsection

@section('admission_js')
    <script
        src="{{ asset('js/admins/admission/toxic.js') }}"
    ></script>
@endsection --}}

@extends('admins.pages.admission.layout')

@section('admission_title', 'Cảnh báo mạng xã hội')

@section(
    'admission_description',
    'Theo dõi các bình luận được AI phát hiện cần cảnh báo và hỗ trợ cán bộ kiểm tra, xử lý trên Facebook.'
)

@section('admission_actions')
    <div class="d-flex flex-wrap gap-2">
        <button
            type="button"
            class="btn btn-secondary btn-toxic-filter active"
            data-status="all"
        >
            <i class="fas fa-list me-1"></i>
            Tất cả
        </button>

        <button
            type="button"
            class="btn btn-outline-danger btn-toxic-filter"
            data-status="pending"
        >
            <i class="fas fa-exclamation-circle me-1"></i>
            Chờ xử lý
        </button>

        <button
            type="button"
            class="btn btn-outline-success btn-toxic-filter"
            data-status="ignored"
        >
            <i class="fas fa-eye me-1"></i>
            Đã bỏ ẩn
        </button>

        <button
            type="button"
            class="btn btn-outline-dark btn-toxic-filter"
            data-status="deleted"
        >
            <i class="fas fa-trash-alt me-1"></i>
            Đã xóa
        </button>
    </div>
@endsection

@section('admission_content')

<style>
    #admissionToxicPage .toxic-comment-content {
        max-width: 430px;
        line-height: 1.55;
        color: #263548;
        word-break: break-word;
    }

    #admissionToxicPage .toxic-user-name {
        color: #172b4d;
        font-weight: 600;
    }

    #admissionToxicPage .toxic-source {
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    #admissionToxicPage .toxic-time {
        min-width: 120px;
        color: #6c757d;
        font-size: 13px;
    }

    #admissionToxicPage .toxic-status-stack {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 6px;
    }

    #admissionToxicPage .table > :not(caption) > * > * {
        vertical-align: middle;
    }

    .toxic-detail-section {
        border: 1px solid #e8edf3;
        border-radius: 10px;
        background: #fff;
        padding: 16px;
        height: 100%;
    }

    .toxic-detail-title {
        margin-bottom: 12px;
        color: #526171;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .4px;
    }

    .toxic-detail-content {
        line-height: 1.65;
        color: #24364b;
        white-space: pre-wrap;
        word-break: break-word;
    }

    .toxic-detail-label {
        margin-bottom: 3px;
        color: #7a8795;
        font-size: 12px;
    }

    .toxic-detail-value {
        color: #263548;
        font-weight: 500;
        word-break: break-word;
    }

    .toxic-modal-meta {
        padding: 10px 12px;
        background: #f7f9fc;
        border-radius: 8px;
    }

    .toxic-facebook-error {
        margin-top: 10px;
        padding: 10px 12px;
        border-radius: 8px;
        background: #fff4f4;
        color: #c0392b;
        font-size: 13px;
        word-break: break-word;
    }

    #toxicDetailModal .modal-dialog {
        max-width: 900px;
    }

    #toxicDetailModal .modal-header {
        border-bottom: 1px solid #eef1f4;
    }

    #toxicDetailModal .modal-footer {
        border-top: 1px solid #eef1f4;
    }
</style>

<section
    class="admission-card"
    id="admissionToxicPage"
    data-status="all"
>
    <div class="admission-card-header">
        <div>
            <h2 class="admission-card-title mb-1">
                Bình luận cần rà soát
            </h2>

            <div class="admission-card-subtitle">
                Các bình luận được AI phát hiện cần cảnh báo sẽ được
                tập trung tại đây để cán bộ kiểm tra và xử lý.
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table admission-table mb-0">
            <thead>
                <tr>
                    <th style="width: 130px;">Nguồn</th>
                    <th style="width: 180px;">Người đăng</th>
                    <th>Nội dung bình luận</th>
                    <th style="width: 160px;">Phân loại</th>
                    <th style="width: 150px;">Trạng thái</th>
                    <th style="width: 130px;">Thời gian</th>
                    <th
                        style="width: 110px;"
                        class="text-center"
                    >
                        Thao tác
                    </th>
                </tr>
            </thead>

            <tbody id="toxicCommentsBody">
                <tr>
                    <td
                        colspan="7"
                        class="admission-loading"
                    >
                        Đang tải cảnh báo...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div
        class="admission-card-body pt-3"
        id="toxicPagination"
    ></div>
</section>

{{-- Modal chi tiết --}}
<div
    class="modal fade"
    id="toxicDetailModal"
    tabindex="-1"
    aria-labelledby="toxicDetailModalLabel"
    aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header">
                <div>
                    <h5
                        class="modal-title mb-1"
                        id="toxicDetailModalLabel"
                    >
                        Chi tiết cảnh báo
                    </h5>

                    <div
                        class="d-flex flex-wrap gap-2"
                        id="toxicModalBadges"
                    ></div>
                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Đóng"
                ></button>
            </div>

            <div class="modal-body">

                <div class="row g-3">

                    <div class="col-md-7">
                        <div class="toxic-detail-section">
                            <div class="toxic-detail-title">
                                Nội dung bình luận
                            </div>

                            <div
                                class="toxic-detail-content"
                                id="toxicModalMessage"
                            ></div>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <div class="toxic-detail-section">
                            <div class="toxic-detail-title">
                                Người đăng
                            </div>

                            <div class="mb-3">
                                <div class="toxic-detail-label">
                                    Họ tên
                                </div>
                                <div
                                    class="toxic-detail-value"
                                    id="toxicModalSenderName"
                                ></div>
                            </div>

                            <div class="mb-3">
                                <div class="toxic-detail-label">
                                    Mã người dùng
                                </div>
                                <div
                                    class="toxic-detail-value"
                                    id="toxicModalSenderId"
                                ></div>
                            </div>

                            <div>
                                <div class="toxic-detail-label">
                                    Thời gian ghi nhận
                                </div>
                                <div
                                    class="toxic-detail-value"
                                    id="toxicModalCreatedAt"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="toxic-detail-section">
                            <div class="toxic-detail-title">
                                Nhận định của AI
                            </div>

                            <div class="mb-3">
                                <div class="toxic-detail-label">
                                    Phân loại
                                </div>
                                <div id="toxicModalCategory"></div>
                            </div>

                            <div>
                                <div class="toxic-detail-label">
                                    Lý do cảnh báo
                                </div>
                                <div
                                    class="toxic-detail-content"
                                    id="toxicModalAiReason"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="toxic-detail-section">
                            <div class="toxic-detail-title">
                                Trạng thái Facebook
                            </div>

                            <div
                                class="mb-3"
                                id="toxicModalHideStatus"
                            ></div>

                            <div>
                                <div class="toxic-detail-label">
                                    Trạng thái xử lý
                                </div>
                                <div id="toxicModalStatus"></div>
                            </div>

                            <div
                                id="toxicModalFacebookError"
                            ></div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="toxic-detail-section">
                            <div class="toxic-detail-title">
                                Bài viết liên quan
                            </div>

                            <div
                                class="toxic-detail-content mb-3"
                                id="toxicModalPostMessage"
                            ></div>

                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="toxic-modal-meta">
                                        <div class="toxic-detail-label">
                                            Mã bài viết
                                        </div>
                                        <div
                                            class="toxic-detail-value"
                                            id="toxicModalPostId"
                                        ></div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="toxic-modal-meta">
                                        <div class="toxic-detail-label">
                                            Mã bình luận
                                        </div>
                                        <div
                                            class="toxic-detail-value"
                                            id="toxicModalCommentId"
                                        ></div>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="mt-3"
                                id="toxicModalPostLink"
                            ></div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="modal-footer">
                <div
                    class="me-auto d-flex gap-2"
                    id="toxicModalActions"
                ></div>

                <button
                    type="button"
                    class="btn btn-light"
                    data-bs-dismiss="modal"
                >
                    Đóng
                </button>
            </div>

        </div>
    </div>
</div>
@endsection

@section('admission_js')
    <script
        src="{{ asset('js/admins/admission/toxic.js') }}"
    ></script>
@endsection