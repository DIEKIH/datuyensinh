@extends('admins.pages.admission.layout')

@section('admission_title', 'OpenAI và kho tri thức RAG')
@section('admission_description', 'Quản lý instructions và các tài liệu đang được gắn với Vector Store của hệ thống tuyển sinh.')

@section('admission_actions')
    <input type="file" id="uploadOpenAiFile" class="d-none" accept=".pdf,.txt,.docx,.json">
    <button id="chooseOpenAiFile" class="btn btn-outline-primary">
        <i class="fas fa-cloud-upload-alt me-1"></i> Tải tài liệu
    </button>
@endsection

@section('admission_content')
<div class="row g-3" id="admissionOpenAiPage">
    <div class="col-xl-6">
        <section class="admission-card h-100">
            <div class="admission-card-header">
                <div>
                    <h2 class="admission-card-title">Kịch bản Prompt</h2>
                    <div class="admission-card-subtitle">Instructions dùng chung cho trợ lý tuyển sinh</div>
                </div>
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
        <section class="admission-card h-100">
            <div class="admission-card-header">
                <div>
                    <h2 class="admission-card-title">Tài liệu Vector Store</h2>
                    <div class="admission-card-subtitle">Danh sách file đang được OpenAI sử dụng</div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table admission-table">
                    <thead><tr><th>Tên file</th><th>Dung lượng</th><th>Thao tác</th></tr></thead>
                    <tbody id="openaiFileRows">
                        <tr><td colspan="3" class="admission-loading">Đang đồng bộ danh sách file...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
@endsection

@section('admission_js')
    <script src="{{ asset('js/admins/admission/openai.js') }}"></script>
@endsection
