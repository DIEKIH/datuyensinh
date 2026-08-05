@extends('admins.pages.admission.layout')

@section('admission_title', 'Tổng quan tuyển sinh')
@section('admission_description', 'Theo dõi lead, mức độ tiềm năng, nguồn tương tác và hiệu quả xử lý của trợ lý tuyển sinh.')

@section('admission_actions')
    <a href="{{ route('admin.admission-cms.leads') }}" class="btn btn-primary">
        <i class="fas fa-users me-1"></i> Xem danh sách lead
    </a>
@endsection

@section('admission_content')
<div id="admissionDashboardPage">
    <div class="admission-stat-grid">
        <div class="admission-stat">
            <div class="admission-stat-icon"><i class="fas fa-users"></i></div>
            <div class="admission-stat-label">Tổng lead</div>
            <div class="admission-stat-value" data-stat="total_leads">0</div>
        </div>
        <div class="admission-stat">
            <div class="admission-stat-icon"><i class="fas fa-fire"></i></div>
            <div class="admission-stat-label">Lead hot</div>
            <div class="admission-stat-value" data-stat="hot_leads">0</div>
        </div>
        <div class="admission-stat">
            <div class="admission-stat-icon"><i class="fas fa-user-plus"></i></div>
            <div class="admission-stat-label">Lead mới</div>
            <div class="admission-stat-value" data-stat="new_leads">0</div>
        </div>
        <div class="admission-stat">
            <div class="admission-stat-icon"><i class="fas fa-file-alt"></i></div>
            <div class="admission-stat-label">Tài liệu RAG</div>
            <div class="admission-stat-value" data-stat="rag_documents">0</div>
        </div>
        <div class="admission-stat">
            <div class="admission-stat-icon"><i class="fas fa-bolt"></i></div>
            <div class="admission-stat-label">Sự kiện n8n hôm nay</div>
            <div class="admission-stat-value" data-stat="n8n_events">0</div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-7">
            <section class="admission-card h-100">
                <div class="admission-card-header">
                    <div>
                        <h2 class="admission-card-title">Phễu chuyển đổi lead</h2>
                        <div class="admission-card-subtitle">Tổng lead → Warm → Hot</div>
                    </div>
                </div>
                <div class="admission-card-body">
                    <div class="admission-chart"><canvas id="funnelChart"></canvas></div>
                </div>
            </section>
        </div>
        <div class="col-xl-5">
            <section class="admission-card h-100">
                <div class="admission-card-header">
                    <div>
                        <h2 class="admission-card-title">Nguồn tiếp nhận</h2>
                        <div class="admission-card-subtitle">Phân bổ lead theo kênh</div>
                    </div>
                </div>
                <div class="admission-card-body">
                    <div class="admission-chart"><canvas id="sourceChart"></canvas></div>
                </div>
            </section>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <section class="admission-card h-100">
                <div class="admission-card-header">
                    <div>
                        <h2 class="admission-card-title">Hiệu quả trợ lý ảo</h2>
                        <div class="admission-card-subtitle">Tỷ lệ câu hỏi được xử lý mà không cần chuyển tư vấn viên</div>
                    </div>
                </div>
                <div class="admission-card-body">
                    <div class="row align-items-center g-3">
                        <div class="col-md-5">
                            <div class="admission-chart-sm"><canvas id="deflectionChart"></canvas></div>
                        </div>
                        <div class="col-md-7">
                            <div class="admission-kicker">BOT DEFLECTION RATE</div>
                            <div class="admission-percent" id="deflectionRateText">0%</div>
                            <p class="text-muted mb-0">Tỷ lệ được tính từ số ticket đã được AI trả lời trên tổng số ticket tiếp nhận.</p>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-xl-4">
            <section class="admission-card h-100">
                <div class="admission-card-header">
                    <div>
                        <h2 class="admission-card-title">Chăm sóc lead tự động</h2>
                        <div class="admission-card-subtitle">Kích hoạt chiến dịch email nurturing</div>
                    </div>
                </div>
                <div class="admission-card-body d-flex flex-column h-100">
                    <p class="text-muted">Gửi nội dung nhắc hoàn thiện hồ sơ cho các lead phù hợp theo kịch bản đang cấu hình.</p>
                    <button id="runNurtureBtn" class="btn btn-success mt-auto">
                        <i class="fas fa-paper-plane me-1"></i> Chạy chiến dịch
                    </button>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection

@section('admission_js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="{{ asset('js/admins/admission/dashboard.js') }}"></script>
@endsection
