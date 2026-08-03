@extends('admins.layouts.app')

@section('title', 'CMS tuyen sinh')

@section('css')
<style>
    .admission-shell { padding: 22px; background: #f5f7fb; min-height: 100vh; }
    .admission-header { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 18px; }
    .admission-title { margin: 0; font-size: 24px; font-weight: 700; color: #172033; }
    .stat-grid { display: grid; grid-template-columns: repeat(5, minmax(140px, 1fr)); gap: 12px; margin-bottom: 16px; }
    .stat-card, .panel { background: #fff; border: 1px solid #e3e8f2; border-radius: 8px; box-shadow: 0 1px 2px rgba(15, 23, 42, .04); }
    .stat-card { padding: 14px; }
    .stat-label { font-size: 12px; color: #667085; text-transform: uppercase; }
    .stat-value { font-size: 26px; font-weight: 700; color: #111827; }
    .panel { padding: 16px; margin-bottom: 16px; }
    .panel-title { font-size: 16px; font-weight: 700; margin-bottom: 12px; color: #172033; }
    .tabs-line { display: flex; gap: 8px; margin-bottom: 16px; border-bottom: 1px solid #e3e8f2; }
    .tabs-line button { border: 0; background: transparent; padding: 10px 12px; color: #475467; font-weight: 600; }
    .tabs-line button.active { color: #0d6efd; border-bottom: 2px solid #0d6efd; }
    .lead-score { min-width: 58px; display: inline-block; padding: 4px 8px; border-radius: 999px; font-weight: 700; text-align: center; }
    .lead-score.hot { background: #fee2e2; color: #991b1b; }
    .lead-score.warm { background: #fef3c7; color: #92400e; }
    .lead-score.cold { background: #e0f2fe; color: #075985; }
    .table td { vertical-align: middle; }
    .rag-answer { white-space: pre-wrap; background: #f8fafc; border: 1px solid #e3e8f2; border-radius: 8px; padding: 12px; min-height: 90px; }
    .hidden { display: none !important; }
    @media (max-width: 1100px) { .stat-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 700px) { .admission-header { align-items: flex-start; flex-direction: column; } .stat-grid { grid-template-columns: 1fr; } }
</style>
@endsection

@section('content')
<div class="admission-shell">
    <div class="admission-header">
        <div>
            <h1 class="admission-title">CMS tuyen sinh tap trung</h1>
            <div class="text-muted">Tiep nhan lead, cham diem, quan ly quy che RAG va giam sat n8n.</div>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#leadModal">
            <i class="fas fa-plus me-1"></i> Them lead
        </button>
    </div>

    <div class="stat-grid" id="admissionStats">
        <div class="stat-card"><div class="stat-label">Tong lead</div><div class="stat-value" data-stat="total_leads">0</div></div>
        <div class="stat-card"><div class="stat-label">Lead hot</div><div class="stat-value" data-stat="hot_leads">0</div></div>
        <div class="stat-card"><div class="stat-label">Lead moi</div><div class="stat-value" data-stat="new_leads">0</div></div>
        <div class="stat-card"><div class="stat-label">Tai lieu RAG</div><div class="stat-value" data-stat="rag_documents">0</div></div>
        <div class="stat-card"><div class="stat-label">Su kien n8n hom nay</div><div class="stat-value" data-stat="n8n_events">0</div></div>
    </div>

    <div class="tabs-line">
        <button class="active" data-tab-target="dashboardTab">Dashboard (Báo Cáo)</button>
        <button data-tab-target="leadsTab">Leads</button>
        <button data-tab-target="approvalTab" class="position-relative">
            Duyệt AI <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"><span class="visually-hidden">New alerts</span></span>
        </button>
        <button data-tab-target="openaiTab">Quản lý OpenAI</button>
        <button data-tab-target="scoringTab">Tiêu chí Chấm điểm</button>
        <button data-tab-target="toxicTab" class="text-danger fw-bold"><i class="fas fa-exclamation-triangle"></i> Cảnh báo MXH</button>
        <button data-tab-target="socialPostTab" class="text-primary fw-bold"><i class="fas fa-share-square"></i> Bài đã đăng</button>
        <button data-tab-target="n8nTab">n8n logs</button>
    </div>

    <section id="dashboardTab" class="tab-panel">
        <div class="row g-3 mb-3">
            <div class="col-lg-7">
                <div class="panel h-100">
                    <div class="panel-title">Phễu Chuyển Đổi (Conversion Funnel)</div>
                    <div style="position: relative; height: 250px; width: 100%;">
                        <canvas id="funnelChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="panel h-100">
                    <div class="panel-title">Nguồn Khách (Traffic Sources)</div>
                    <div style="position: relative; height: 250px; width: 100%;">
                        <canvas id="sourceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="panel">
                    <div class="panel-title">Tỉ lệ Trợ Lý Ảo Tự Động Xử Lý (Bot Deflection Rate)</div>
                    <div class="d-flex align-items-center gap-4">
                        <div style="flex: 0 0 250px; position: relative; height: 150px;">
                            <canvas id="deflectionChart"></canvas>
                        </div>
                        <div style="flex: 1">
                            <h2 class="text-primary mb-1 fw-bold" style="font-size: 3rem;" id="deflectionRateText">0%</h2>
                            <p class="text-muted mb-0" style="font-size: 1.1rem;">
                                Đây là tỉ lệ các câu hỏi được AI tự động giải quyết hoàn toàn mà không cần tư vấn viên can thiệp. 
                                Một tỉ lệ cao (trên 80%) chứng tỏ hệ thống RAG đang làm việc cực kỳ hiệu quả, giúp tiết kiệm hàng trăm giờ làm việc cho nhân sự!
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="panel h-100 d-flex flex-column justify-content-center text-center">
                    <div class="panel-title text-start">Email Marketing Tự Động (Nurturing)</div>
                    <div class="mb-3 text-muted text-start">
                        Gửi Email nhắc nhở làm hồ sơ cho các Lead tạo cách đây 3 ngày.
                    </div>
                    <button id="runNurtureBtn" class="btn btn-success btn-lg mt-auto">
                        <i class="fas fa-paper-plane me-2"></i> Chạy Chiến Dịch Ngay
                    </button>
                </div>
            </div>
        </div>
    </section>

    <section id="leadsTab" class="tab-panel hidden">
        <div class="panel">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                <div class="panel-title mb-0">Danh sach thi sinh tiem nang</div>
                <div class="d-flex flex-wrap gap-2">
                    <input id="leadKeyword" class="form-control form-control-sm" style="width:220px" placeholder="Ten, SĐT, email, nganh">
                    <select id="leadGrade" class="form-select form-select-sm" style="width:140px">
                        <option value="">Moi diem</option>
                        <option value="hot">Hot</option>
                        <option value="warm">Warm</option>
                        <option value="cold">Cold</option>
                    </select>
                    <button id="reloadLeads" class="btn btn-outline-primary btn-sm"><i class="fas fa-sync-alt"></i></button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Khách hàng</th>
                            <th>Liên hệ</th>
                            <th>Nguồn</th>
                            <th>Ngành / Tỉnh</th>
                            <th>Điểm</th>
                            <th>Trạng thái Sale</th>
                            <th>Tương tác cuối</th>
                        </tr>
                    </thead>
                    <tbody id="leadRows">
                        <tr><td colspan="7" class="text-center text-muted">Đang tải...</td></tr>
                    </tbody>
                </table>
            </div>
            <div id="leadsPagination"></div>
        </div>
    </section>

    <section id="approvalTab" class="tab-panel hidden">
        <div class="panel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="panel-title mb-0">Hàng Chờ Duyệt (Human-in-the-Loop)</div>
                <div>
                    <button id="runNurtureCommand" class="btn btn-warning btn-sm me-2"><i class="fas fa-magic"></i> Chạy Kịch Bản Nuôi Dưỡng AI (n8n)</button>
                    <button id="reloadApprovals" class="btn btn-outline-primary btn-sm"><i class="fas fa-sync-alt"></i> Làm mới</button>
                </div>
            </div>
            <div id="approvalList" style="max-height: 600px; overflow-y: auto; padding-right: 10px;">
                <div class="text-center"><i class="fas fa-spinner fa-spin"></i> Đang tải...</div>
            </div>
            <div id="approvalsPagination"></div>
        </div>
    </section>

    <section id="openaiTab" class="tab-panel hidden">
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="panel">
                    <div class="panel-title">Kịch bản Prompt (Instructions)</div>
                    <textarea id="openaiPrompt" class="form-control mb-2" rows="16" placeholder="Đang tải dữ liệu từ OpenAI..."></textarea>
                    <button id="saveOpenAiPrompt" class="btn btn-primary btn-sm w-100"><i class="fas fa-save me-1"></i> Lưu & Đồng bộ lên OpenAI</button>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="panel">
                    <div class="panel-title d-flex justify-content-between align-items-center">
                        <span>Danh sách File trên OpenAI</span>
                        <input type="file" id="uploadOpenAiFile" class="d-none" accept=".pdf,.txt,.docx">
                        <button class="btn btn-sm btn-outline-primary" onclick="$('#uploadOpenAiFile').click()">+ Upload File</button>
                    </div>
                    <div class="table-responsive mt-3">
                        <table class="table table-bordered table-striped">
                            <thead><tr><th>Tên file</th><th>Dung lượng</th><th>Hành động</th></tr></thead>
                            <tbody id="openaiFileRows">
                                <tr><td colspan="3" class="text-center text-muted">Đang tải...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="toxicTab" class="tab-panel hidden">
        <div class="panel">
            <div class="panel-title text-danger"><i class="fas fa-exclamation-triangle"></i> Cảnh báo Comment Tiêu cực/Tục tĩu từ AI</div>
            <div class="d-flex mb-3 gap-2">
                <button class="btn btn-sm btn-outline-secondary" onclick="loadToxicComments('all')">Tất cả</button>
                <button class="btn btn-sm btn-outline-danger" onclick="loadToxicComments('pending')">Chờ xử lý</button>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Nguồn</th>
                            <th>Người đăng</th>
                            <th>Nội dung vi phạm</th>
                            <th>Nhận định của AI</th>
                            <th>Thời gian</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="toxicCommentsBody">
                        <tr><td colspan="6" class="text-center">Đang tải dữ liệu...</td></tr>
                    </tbody>
                </table>
                <div id="toxicPagination" class="d-flex justify-content-end mt-3"></div>
            </div>
        </div>
    </section>

    <section id="socialPostTab" class="tab-panel hidden">
        <div class="panel">
            <div class="panel-title text-primary"><i class="fas fa-share-square"></i> Lịch sử Đăng bài Tự động</div>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Nền tảng</th>
                            <th>Tiêu đề / Nội dung</th>
                            <th>Hình ảnh</th>
                            <th>Thời gian đăng</th>
                        </tr>
                    </thead>
                    <tbody id="socialPostsBody">
                        <tr><td colspan="4" class="text-center">Đang tải dữ liệu...</td></tr>
                    </tbody>
                </table>
                <div id="socialPostsPagination" class="d-flex justify-content-end mt-3"></div>
            </div>
        </div>
    </section>

    <section id="n8nTab" class="tab-panel hidden">
        <div class="panel">
            <div class="panel-title">Su kien n8n gan nhat</div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead><tr><th>Workflow</th><th>Event</th><th>Status</th><th>Message</th><th>Time</th></tr></thead>
                    <tbody id="n8nRows"></tbody>
                </table>
            </div>
            <div class="text-muted small">
                Endpoint: <code>/api/n8n/leads</code>, <code>/api/n8n/rag/answer</code>, <code>/api/n8n/notifications</code>
            </div>
        </div>
    </section>

    <section id="scoringTab" class="tab-panel hidden">
        <div class="panel">
            <div class="panel-title d-flex justify-content-between align-items-center">
                <span>Danh sách Tiêu chí Chấm điểm</span>
                <div>
                    <button class="btn btn-primary btn-sm me-2" data-bs-toggle="modal" data-bs-target="#criterionModal" id="btnAddNewCrit">
                        <i class="fas fa-plus me-1"></i> Thêm Tiêu chí
                    </button>
                    <button id="reloadCriteria" class="btn btn-sm btn-outline-primary"><i class="fas fa-sync-alt"></i></button>
                </div>
            </div>
            <div class="table-responsive mt-3">
                <table class="table table-bordered table-striped">
                    <thead><tr><th>Mã</th><th>Tên</th><th>Trường so sánh</th><th>Type & Form</th><th>Toán tử</th><th>Giá trị</th><th>Điểm</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
                    <tbody id="criteriaRows">
                        <tr><td colspan="9" class="text-center text-muted">Đang tải...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="leadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="leadForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Thêm lead thủ công</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2"><input name="full_name" class="form-control form-control-sm" placeholder="Họ tên"></div>
                    <div class="mb-2 d-flex gap-2"><input name="phone" class="form-control form-control-sm" placeholder="Số điện thoại"><input name="email" class="form-control form-control-sm" placeholder="Email"></div>
                    <div class="mb-2 d-flex gap-2"><input name="channel" class="form-control form-control-sm" value="manual" required><input name="source_campaign" class="form-control form-control-sm" placeholder="Campaign"></div>
                    <div class="mb-2 d-flex gap-2"><input name="intended_major" class="form-control form-control-sm" placeholder="Ngành quan tâm"><input name="province" class="form-control form-control-sm" placeholder="Tỉnh/Thành"></div>
                    <div class="mb-2"><textarea name="note" class="form-control form-control-sm" rows="3" placeholder="Ghi chú"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary btn-sm">Lưu lead</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="criterionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="criterionForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="critModalTitle">Thêm Tiêu chí Chấm điểm</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="crit_id">
                    <div class="mb-2"><input id="crit_code" class="form-control form-control-sm" placeholder="Mã tiêu chí (VD: SCORE_MATH)" required></div>
                    <div class="mb-2"><input id="crit_name" class="form-control form-control-sm" placeholder="Tên tiêu chí" required></div>
                    <div class="mb-2">
                        <input id="crit_category" class="form-control form-control-sm" placeholder="Nhóm tiêu chí (Tùy chọn, VD: Tương tác, Học lực)" list="categoryList">
                        <datalist id="categoryList">
                            <option value="Chung">
                            <option value="Thông tin cá nhân">
                            <option value="Nhu cầu học tập">
                            <option value="Tương tác mạng xã hội">
                        </datalist>
                    </div>
                    <div class="mb-2">
                        <input id="crit_field" class="form-control form-control-sm" placeholder='Trường dữ liệu (Bắt đầu bằng "custom." nếu hiện trên form public)' required>
                    </div>
                    <div class="mb-2">
                        <select id="crit_input_type" class="form-select form-select-sm">
                            <option value="text">Văn bản (Text)</option>
                            <option value="number">Số (Number)</option>
                            <option value="select">Danh sách (Select)</option>
                            <option value="checkbox">Hộp chọn (Checkbox)</option>
                            <option value="date">Ngày tháng (Date)</option>
                        </select>
                    </div>
                    <div class="mb-2" id="crit_options_wrap" style="display: none;">
                        <textarea id="crit_options" class="form-control form-control-sm" rows="3" placeholder='Nhập các lựa chọn cách nhau bằng dấu phẩy. VD: Nam, Nữ, Khác'></textarea>
                    </div>
                    <div class="mb-2 d-flex gap-3 align-items-center">
                        <label class="form-check-label d-flex align-items-center gap-1"><input type="checkbox" id="crit_show_public" class="form-check-input m-0"> Hiện Form Public</label>
                        <label class="form-check-label d-flex align-items-center gap-1"><input type="checkbox" id="crit_is_required" class="form-check-input m-0"> Bắt buộc nhập</label>
                    </div>
                    <div class="mb-2">
                        <select id="crit_op" class="form-select form-select-sm">
                            <option value="=">Bằng (=)</option>
                            <option value=">">Lớn hơn (>)</option>
                            <option value=">=">Lớn hơn hoặc bằng (>=)</option>
                            <option value="<">Nhỏ hơn (<)</option>
                            <option value="<=">Nhỏ hơn hoặc bằng (<=)</option>
                            <option value="contains">Chứa chuỗi</option>
                            <option value="has_value">Có dữ liệu</option>
                        </select>
                    </div>
                    <div class="mb-2"><input id="crit_val" class="form-control form-control-sm" placeholder="Giá trị so sánh (VD: 20)"></div>
                    <div class="mb-2 d-flex gap-2">
                        <input id="crit_score" type="number" class="form-control form-control-sm" placeholder="Điểm cộng (VD: 10)" required>
                        <input id="crit_priority" type="number" class="form-control form-control-sm" placeholder="Ưu tiên (VD: 1)">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i> Lưu Tiêu chí</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="{{ asset('js/admins/admission_cms.js') }}"></script>
@endsection
