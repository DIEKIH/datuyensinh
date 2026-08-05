@extends('admins.pages.admission.layout')

@section('admission_title', 'Chạy chiến dịch chăm sóc Lead')
@section(
    'admission_description',
    'Một lần chạy có thể gửi Email và Messenger cho các Lead đạt điểm tối thiểu.'
)

@section('admission_actions')
    <a href="{{ route('admin.admission-cms.leads') }}"
       class="btn btn-outline-primary">
        <i class="fas fa-users me-1"></i>
        Xem danh sách Lead
    </a>
@endsection

@section('admission_content')
<style>
    .campaign-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.25fr) minmax(300px, .75fr);
        gap: 18px;
    }

    .campaign-card {
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .05);
    }

    .campaign-card-header {
        padding: 16px 18px;
        border-bottom: 1px solid #e5e7eb;
        background: #f8fafc;
    }

    .campaign-card-title {
        margin: 0;
        color: #1f2937;
        font-size: .96rem;
        font-weight: 750;
    }

    .campaign-card-subtitle {
        margin-top: 4px;
        color: #64748b;
        font-size: .76rem;
    }

    .campaign-card-body {
        padding: 18px;
    }

    .campaign-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 15px;
    }

    .campaign-form-grid .form-group {
        margin: 0;
    }

    .campaign-field-full {
        grid-column: 1 / -1;
    }

    .campaign-label {
        display: block;
        margin-bottom: 6px;
        color: #334155;
        font-size: .78rem;
        font-weight: 700;
    }

    .campaign-help {
        margin-top: 5px;
        color: #94a3b8;
        font-size: .68rem;
        line-height: 1.45;
    }

    .campaign-channel-box {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .campaign-channel {
        display: flex;
        align-items: flex-start;
        gap: 9px;
        padding: 12px;
        border: 1px solid #dbe4ee;
        border-radius: 11px;
        background: #f8fafc;
        cursor: pointer;
    }

    .campaign-channel input {
        margin-top: 3px;
    }

    .campaign-channel strong {
        display: block;
        color: #334155;
        font-size: .78rem;
    }

    .campaign-channel small {
        display: block;
        margin-top: 2px;
        color: #64748b;
        font-size: .67rem;
        line-height: 1.4;
    }

    .campaign-submit {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 9px;
        margin-top: 18px;
        padding-top: 16px;
        border-top: 1px solid #e5e7eb;
    }

    .campaign-flow {
        display: grid;
        gap: 10px;
    }

    .campaign-flow-item {
        position: relative;
        padding: 12px 13px 12px 43px;
        border: 1px solid #e2e8f0;
        border-radius: 11px;
        background: #f8fafc;
    }

    .campaign-flow-number {
        position: absolute;
        top: 12px;
        left: 12px;
        width: 23px;
        height: 23px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: #dbeafe;
        color: #1d4ed8;
        font-size: .68rem;
        font-weight: 800;
    }

    .campaign-flow-item strong {
        display: block;
        color: #334155;
        font-size: .77rem;
    }

    .campaign-flow-item span {
        display: block;
        margin-top: 3px;
        color: #64748b;
        font-size: .68rem;
        line-height: 1.45;
    }

    .campaign-result {
        display: none;
        margin-top: 16px;
        padding: 14px;
        border: 1px solid #bbf7d0;
        border-radius: 11px;
        background: #f0fdf4;
        color: #166534;
        font-size: .76rem;
        line-height: 1.55;
    }

    .campaign-result.is-error {
        border-color: #fecaca;
        background: #fef2f2;
        color: #991b1b;
    }

    .campaign-summary {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
        margin-top: 10px;
    }

    .campaign-summary-item {
        padding: 9px;
        border: 1px solid rgba(22, 101, 52, .14);
        border-radius: 9px;
        background: rgba(255, 255, 255, .7);
        text-align: center;
    }

    .campaign-summary-item strong {
        display: block;
        font-size: 1rem;
    }

    .campaign-summary-item span {
        display: block;
        margin-top: 2px;
        font-size: .65rem;
    }

    @media (max-width: 991.98px) {
        .campaign-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 575.98px) {
        .campaign-form-grid,
        .campaign-channel-box {
            grid-template-columns: 1fr;
        }

        .campaign-field-full {
            grid-column: auto;
        }

        .campaign-summary {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="campaign-grid" id="admissionCampaignPage">
    <section class="campaign-card">
        <div class="campaign-card-header">
            <h2 class="campaign-card-title">
                <i class="fas fa-bullhorn me-1 text-primary"></i>
                Thông tin chiến dịch
            </h2>
            <div class="campaign-card-subtitle">
                Laravel lọc Lead theo điểm, sau đó gửi danh sách sang Master Workflow n8n.
            </div>
        </div>

        <div class="campaign-card-body">
            <form id="campaignForm">
                @csrf

                <div class="campaign-form-grid">
                    <div class="form-group">
                        <label class="campaign-label" for="campaignName">
                            Tên chiến dịch *
                        </label>
                        <input id="campaignName"
                               class="form-control"
                               name="campaign_name"
                               maxlength="255"
                               placeholder="Ví dụ: Tư vấn tuyển sinh tháng 8"
                               required>
                    </div>

                    <div class="form-group">
                        <label class="campaign-label" for="minimumScore">
                            Điểm Lead tối thiểu *
                        </label>
                        <input id="minimumScore"
                               class="form-control"
                               name="minimum_score"
                               type="number"
                               min="0"
                               max="100"
                               step="0.01"
                               value="80"
                               required>
                        <div class="campaign-help">
                            Chỉ lấy Lead có điểm lớn hơn hoặc bằng mức này.
                        </div>
                    </div>

                    <div class="form-group campaign-field-full">
                        <label class="campaign-label">
                            Kênh gửi *
                        </label>

                        <div class="campaign-channel-box">
                            <label class="campaign-channel">
                                <input type="checkbox"
                                       name="send_email"
                                       value="1"
                                       checked>
                                <span>
                                    <strong>
                                        <i class="fas fa-envelope me-1"></i>
                                        Email
                                    </strong>
                                    <small>
                                        Chỉ gửi cho Lead có địa chỉ Email hợp lệ.
                                    </small>
                                </span>
                            </label>

                            <label class="campaign-channel">
                                <input type="checkbox"
                                       name="send_messenger"
                                       value="1"
                                       checked>
                                <span>
                                    <strong>
                                        <i class="fab fa-facebook-messenger me-1"></i>
                                        Messenger
                                    </strong>
                                    <small>
                                        Chỉ gửi cho Lead đã có messenger_psid từ cuộc trò chuyện.
                                    </small>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group campaign-field-full">
                        <label class="campaign-label" for="emailSubject">
                            Tiêu đề Email
                        </label>
                        <input id="emailSubject"
                               class="form-control"
                               name="email_subject"
                               maxlength="255"
                               value="[CTUT] Thông tin tuyển sinh dành cho bạn"
                               placeholder="Tiêu đề dùng khi gửi Email">
                        <div class="campaign-help">
                            Có thể dùng biến {name}, {major}, {score}.
                        </div>
                    </div>

                    <div class="form-group campaign-field-full">
                        <label class="campaign-label" for="campaignMessage">
                            Nội dung chiến dịch *
                        </label>
                        <textarea id="campaignMessage"
                                  class="form-control"
                                  name="message"
                                  rows="8"
                                  maxlength="5000"
                                  placeholder="Nhập nội dung cần gửi..."
                                  required></textarea>
                        <div class="campaign-help">
                            Biến hỗ trợ: {name} là tên Lead, {major} là ngành quan tâm,
                            {score} là điểm Lead. Messenger dùng nội dung này;
                            Email được AI viết lại ngắn gọn nhưng không thay đổi thông tin.
                        </div>
                    </div>
                </div>

                <div class="campaign-submit">
                    <button type="reset"
                            class="btn btn-outline-secondary">
                        <i class="fas fa-rotate-left me-1"></i>
                        Đặt lại
                    </button>

                    <button type="submit"
                            class="btn btn-primary"
                            id="runCampaignButton">
                        <i class="fas fa-paper-plane me-1"></i>
                        Chạy chiến dịch
                    </button>
                </div>

                <div class="campaign-result"
                     id="campaignResult"
                     role="status"
                     aria-live="polite"></div>
            </form>
        </div>
    </section>

    <aside class="campaign-card">
        <div class="campaign-card-header">
            <h2 class="campaign-card-title">
                <i class="fas fa-diagram-project me-1 text-success"></i>
                Luồng xử lý
            </h2>
        </div>

        <div class="campaign-card-body">
            <div class="campaign-flow">
                <div class="campaign-flow-item">
                    <span class="campaign-flow-number">1</span>
                    <strong>Lọc Lead trong Laravel</strong>
                    <span>
                        Lead đạt điểm tối thiểu được lấy từ admission_leads.
                    </span>
                </div>

                <div class="campaign-flow-item">
                    <span class="campaign-flow-number">2</span>
                    <strong>Tách người nhận theo kênh</strong>
                    <span>
                        Email và Messenger độc lập; Lead chỉ có một kênh vẫn được gửi.
                    </span>
                </div>

                <div class="campaign-flow-item">
                    <span class="campaign-flow-number">3</span>
                    <strong>Gửi sang Master Workflow</strong>
                    <span>
                        Sự kiện run_lead_campaign dùng chung luồng với lead_became_hot.
                    </span>
                </div>

                <div class="campaign-flow-item">
                    <span class="campaign-flow-number">4</span>
                    <strong>n8n gửi theo từng kênh</strong>
                    <span>
                        Email qua SMTP; Messenger qua node Facebook hiện có.
                    </span>
                </div>
            </div>

            <div class="alert alert-info mt-3 mb-0 small">
                Messenger Hot tự động vẫn hoạt động riêng khi một Lead vừa chuyển
                sang mức Hot. Nút chạy chiến dịch không thay đổi cơ chế đó.
            </div>
        </div>
    </aside>
</div>
@endsection

@section('admission_js')
@php
    $campaignJsVersion = @filemtime(
        public_path('js/admins/admission/campaigns.js')
    ) ?: '1';
@endphp

<script src="{{ asset('js/admins/admission/campaigns.js') }}?v={{ $campaignJsVersion }}"></script>
@endsection
