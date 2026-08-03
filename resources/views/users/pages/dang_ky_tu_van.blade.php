@extends('users.layouts.app')

@section('title', 'Đăng Ký Tư Vấn Tuyển Sinh')

@section('css')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    :root {
        --primary: #2563eb;
        --primary-hover: #1d4ed8;
        --secondary: #0ea5e9;
        --dark: #0f172a;
        --light: #f8fafc;
        --gray: #64748b;
        --border: #e2e8f0;
        --glass-bg: rgba(255, 255, 255, 0.95);
    }
    body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; }
    
    .lead-page {
        position: relative;
        padding: 80px 0;
        background: linear-gradient(135deg, #eff6ff 0%, #e0f2fe 100%);
        min-height: 100vh;
        overflow: hidden;
    }
    
    /* Decorative Background Elements */
    .lead-page::before {
        content: '';
        position: absolute;
        top: -100px;
        right: -100px;
        width: 400px;
        height: 400px;
        background: radial-gradient(circle, rgba(37,99,235,0.15) 0%, rgba(255,255,255,0) 70%);
        border-radius: 50%;
        z-index: 0;
    }
    
    .lead-wrap {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        position: relative;
        z-index: 1;
    }
    
    .lead-hero {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 60px;
        align-items: center;
    }
    
    /* Left Column - Copy */
    .lead-copy {
        animation: fadeUp 0.8s ease-out;
    }
    .lead-kicker {
        display: inline-block;
        padding: 6px 14px;
        background: rgba(37, 99, 235, 0.1);
        color: var(--primary);
        font-weight: 700;
        font-size: 14px;
        border-radius: 20px;
        margin-bottom: 20px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .lead-title {
        color: var(--dark);
        font-size: 42px;
        line-height: 1.2;
        font-weight: 800;
        margin-bottom: 20px;
        letter-spacing: -1px;
    }
    .lead-title span {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .lead-desc {
        color: var(--gray);
        font-size: 18px;
        line-height: 1.7;
        margin-bottom: 40px;
    }
    
    .lead-points {
        list-style: none;
        padding: 0;
        margin: 0 0 40px 0;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    .lead-points li {
        display: flex;
        align-items: center;
        gap: 16px;
        color: #334155;
        font-size: 16px;
        font-weight: 500;
        padding: 12px 16px;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .lead-points li:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.08);
    }
    .lead-points i {
        color: #10b981;
        font-size: 20px;
    }
    
    /* Right Column - Form */
    .lead-form-card {
        background: var(--glass-bg);
        backdrop-filter: blur(16px);
        border: 1px solid rgba(255, 255, 255, 0.4);
        border-radius: 24px;
        padding: 40px;
        box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.15);
        animation: fadeUp 0.8s ease-out 0.2s backwards;
    }
    
    .lead-form-title {
        font-size: 24px;
        font-weight: 800;
        color: var(--dark);
        margin-bottom: 24px;
        text-align: center;
    }
    
    .lead-source {
        background: #f1f5f9;
        color: var(--gray);
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 13px;
        margin-bottom: 24px;
        text-align: center;
        border: 1px dashed var(--border);
    }
    
    /* Form Inputs */
    .form-group {
        margin-bottom: 20px;
        position: relative;
    }
    .form-label {
        font-weight: 600;
        color: #475569;
        font-size: 14px;
        margin-bottom: 8px;
        display: block;
    }
    .form-control {
        width: 100%;
        padding: 14px 16px;
        border: 1px solid var(--border);
        border-radius: 12px;
        font-size: 15px;
        color: #1e293b;
        background: #fff;
        transition: all 0.3s ease;
    }
    .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        outline: none;
    }
    textarea.form-control {
        resize: vertical;
        min-height: 100px;
    }
    
    .row-2-cols {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    
    /* Submit Button */
    .btn-submit {
        width: 100%;
        padding: 16px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: #fff;
        border: none;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 700;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin-top: 10px;
    }
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.4);
    }
    .btn-submit:active {
        transform: translateY(0);
    }
    
    /* RAG Result Box */
    #leadRagResultBox {
        margin-top: 24px;
        padding: 20px;
        background: linear-gradient(to right, rgba(37,99,235,0.05), rgba(14,165,233,0.05));
        border-left: 4px solid var(--primary);
        border-radius: 0 12px 12px 0;
        display: none;
        animation: fadeIn 0.4s ease-out;
    }
    #leadRagResultContent {
        color: #334155;
        font-size: 15px;
        line-height: 1.6;
    }
    
    /* Animations */
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    /* Responsive */
    @media (max-width: 992px) {
        .lead-hero {
            grid-template-columns: 1fr;
            gap: 40px;
        }
        .lead-title {
            font-size: 32px;
        }
        .lead-form-card {
            padding: 30px 20px;
        }
    }
    @media (max-width: 576px) {
        .row-2-cols {
            grid-template-columns: 1fr;
            gap: 0;
        }
    }
    
    /* RAG Ask Card */
    .rag-ask-card {
        margin-top: 60px;
        background: #fff;
        border-radius: 24px;
        padding: 40px;
        box-shadow: 0 20px 40px -10px rgba(0,0,0,0.08);
        border: 1px solid var(--border);
        animation: fadeUp 0.8s ease-out 0.4s backwards;
    }
    .rag-ask-grid {
        display: grid;
        grid-template-columns: 400px 1fr;
        gap: 40px;
    }
    .rag-answer-box {
        background: #f8fafc;
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 24px;
        height: 100%;
        min-height: 200px;
        color: #475569;
        font-size: 15px;
        line-height: 1.7;
    }
    @media (max-width: 992px) {
        .rag-ask-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

@section('content')
<section class="lead-page">
    <div class="lead-wrap">
        <div class="lead-hero">
            <!-- Left Copy -->
            <div class="lead-copy">
                <div class="lead-kicker">Tư vấn tuyển sinh thông minh</div>
                <h1 class="lead-title">Đăng ký tư vấn và <span>định hướng tương lai</span> cùng chuyên gia</h1>
                <p class="lead-desc">
                    Để lại thông tin, hệ thống AI của chúng tôi sẽ phân tích và xếp lịch tư vấn với chuyên gia phù hợp nhất, giải đáp mọi thắc mắc về ngành nghề, học phí và lộ trình học tập.
                </p>
                <ul class="lead-points">
                    <li><i class="fas fa-check-circle"></i><span>Trợ lý ảo AI túc trực 24/7 trả lời tức thì</span></li>
                    <li><i class="fas fa-check-circle"></i><span>Tự động phân loại ưu tiên dựa trên hồ sơ</span></li>
                    <li><i class="fas fa-check-circle"></i><span>Bảo mật thông tin thí sinh 100%</span></li>
                </ul>
            </div>

            <!-- Right Form -->
            <div class="lead-form-card">
                <div class="lead-form-title">Điền thông tin đăng ký</div>
                
                <form id="publicLeadForm">
                    @csrf
                    <input type="hidden" name="utm_source" value="{{ $utm['source'] ?? '' }}">
                    <input type="hidden" name="utm_medium" value="{{ $utm['medium'] ?? '' }}">
                    <input type="hidden" name="utm_campaign" value="{{ $utm['campaign'] ?? '' }}">
                    <input type="hidden" name="utm_content" value="{{ $utm['content'] ?? '' }}">

                    <div class="form-group">
                        <label class="form-label">Họ và tên *</label>
                        <input class="form-control" name="full_name" placeholder="Ví dụ: Nguyễn Văn A" required>
                    </div>
                    
                    <div class="row-2-cols">
                        <div class="form-group">
                            <label class="form-label">Số điện thoại *</label>
                            <input class="form-control" name="phone" placeholder="09xxxxxxx" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input class="form-control" name="email" type="email" placeholder="email@example.com">
                        </div>
                    </div>
                    


                    <div class="row-2-cols">
                        <div class="form-group">
                            <label class="form-label">Tỉnh / Thành phố</label>
                            <select class="form-control" name="province" id="selectProvince">
                                <option value="">-- Chọn Tỉnh/Thành --</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Quận / Huyện</label>
                            <select class="form-control" name="district" id="selectDistrict" disabled>
                                <option value="">-- Chọn Quận/Huyện --</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row-2-cols">
                        <div class="form-group">
                            <label class="form-label">Trường THPT</label>
                            <input class="form-control" name="high_school" id="inputSchool" placeholder="Nhập tên Trường THPT">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Ngành quan tâm</label>
                            <input class="form-control" name="intended_major" value="{{ $major ?? '' }}" placeholder="VD: Công nghệ thông tin">
                        </div>
                    </div>

                    <div id="dynamicFieldsContainer" class="row-2-cols"></div>

                    <div class="form-group">
                        <label class="form-label">Nội dung cần tư vấn</label>
                        <textarea class="form-control" name="note" placeholder="Bạn muốn hỏi thêm về học phí, ký túc xá hay điểm chuẩn?"></textarea>
                    </div>

                    <button class="btn-submit" type="submit">
                        Gửi thông tin <i class="fas fa-arrow-right"></i>
                    </button>
                    
                    <div id="leadFormMessage" class="mt-3 text-center small fw-bold"></div>
                    
                    <div id="leadRagResultBox">
                        <h6 class="fw-bold mb-2 text-primary" style="font-size: 15px;"><i class="fas fa-robot me-1"></i> Trợ lý AI phản hồi:</h6>
                        <div id="leadRagResultContent"></div>
                    </div>
                </form>
            </div>
        </div>

        <!-- RAG Ask Section -->
        <div class="rag-ask-card">
            <div class="rag-ask-grid">
                <div>
                    <h2 class="lead-title" style="font-size: 28px; margin-bottom: 12px;">Hỏi nhanh<br><span>Trợ lý AI</span></h2>
                    <p class="text-muted mb-4" style="font-size: 15px;">Không tiện để lại thông tin? Hãy hỏi thẳng trợ lý ảo của chúng tôi, AI đã được đào tạo bằng quy chế tuyển sinh mới nhất.</p>
                    <textarea id="publicRagQuestion" class="form-control mb-3" rows="4" placeholder="VD: Điều kiện xét tuyển học bạ ngành IT là gì?"></textarea>
                    <button id="publicAskRag" class="btn-submit" type="button" style="padding: 12px;">
                        <i class="fas fa-sparkles"></i> Tìm câu trả lời
                    </button>
                </div>
                <div>
                    <div id="publicRagAnswer" class="rag-answer-box d-flex align-items-center justify-content-center text-muted">
                        <div class="text-center">
                            <i class="fas fa-comment-dots fa-3x mb-3 text-light"></i>
                            <p class="mb-0">Câu trả lời sẽ hiển thị ở đây...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('js')
<script src="{{ asset('js/admission-lead-form.js') }}"></script>
@endsection
