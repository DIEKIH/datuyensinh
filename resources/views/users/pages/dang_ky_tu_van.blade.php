@extends('users.layouts.app')

@section('title', 'Dang ky tu van tuyen sinh')

@section('css')
<style>
    .lead-page { background: #f6f8fb; padding: 42px 0 56px; }
    .lead-wrap { max-width: 1180px; margin: 0 auto; padding: 0 18px; }
    .lead-hero { display: grid; grid-template-columns: minmax(0, 1.1fr) minmax(360px, .9fr); gap: 24px; align-items: stretch; }
    .lead-copy, .lead-form-card, .lead-rag-card { background: #fff; border: 1px solid #e3e8f2; border-radius: 8px; box-shadow: 0 2px 10px rgba(15, 23, 42, .05); }
    .lead-copy { padding: 30px; display: flex; flex-direction: column; justify-content: center; }
    .lead-kicker { color: #005da0; font-weight: 700; text-transform: uppercase; font-size: 13px; margin-bottom: 10px; }
    .lead-title { color: #12213d; font-size: 34px; line-height: 1.18; font-weight: 800; margin-bottom: 14px; }
    .lead-desc { color: #475467; font-size: 16px; line-height: 1.65; margin-bottom: 22px; }
    .lead-points { display: grid; gap: 10px; margin: 0; padding: 0; list-style: none; }
    .lead-points li { display: flex; gap: 10px; color: #26364f; }
    .lead-points i { color: #0d6efd; margin-top: 3px; }
    .lead-form-card { padding: 24px; }
    .lead-form-title { font-size: 20px; font-weight: 800; margin-bottom: 14px; color: #12213d; }
    .lead-source { background: #eef6ff; color: #064f8f; border-radius: 6px; padding: 8px 10px; font-size: 13px; margin-bottom: 14px; }
    .lead-rag-card { padding: 22px; margin-top: 24px; }
    .rag-answer-box { white-space: pre-wrap; min-height: 90px; background: #f8fafc; border: 1px solid #e3e8f2; border-radius: 8px; padding: 12px; color: #26364f; }
    .form-label { font-weight: 600; color: #344054; }
    @media (max-width: 960px) { .lead-hero { grid-template-columns: 1fr; } .lead-title { font-size: 28px; } }
</style>
@endsection

@section('content')
<section class="lead-page">
    <div class="lead-wrap">
        <div class="lead-hero">
            <div class="lead-copy">
                <div class="lead-kicker">Tu van tuyen sinh da kenh</div>
                <h1 class="lead-title">Dang ky tu van va nhan thong tin phu hop voi nganh ban quan tam</h1>
                <p class="lead-desc">
                    Thong tin dang ky se duoc dua ve CMS tuyen sinh tap trung, phan loai theo kenh Facebook, Zalo, website va cham diem uu tien de tu van vien lien he nhanh hon.
                </p>
                <ul class="lead-points">
                    <li><i class="fas fa-check-circle"></i><span>Tu dong ghi nhan nguon chien dich tu link bai dang.</span></li>
                    <li><i class="fas fa-check-circle"></i><span>Ho tro tro ly ao RAG tra cuu quy che tuyen sinh rieng.</span></li>
                    <li><i class="fas fa-check-circle"></i><span>Dong bo du lieu qua n8n den cac kenh thong bao noi bo.</span></li>
                </ul>
            </div>

            <div class="lead-form-card">
                <div class="lead-form-title">Thong tin can tu van</div>
                <div class="lead-source">
                    Nguon: <strong>{{ $utm['source'] ?: 'website' }}</strong>
                    @if (!empty($utm['campaign']))
                        · Chien dich: <strong>{{ $utm['campaign'] }}</strong>
                    @endif
                </div>
                <form id="publicLeadForm">
                    @csrf
                    <input type="hidden" name="utm_source" value="{{ $utm['source'] }}">
                    <input type="hidden" name="utm_medium" value="{{ $utm['medium'] }}">
                    <input type="hidden" name="utm_campaign" value="{{ $utm['campaign'] }}">
                    <input type="hidden" name="utm_content" value="{{ $utm['content'] }}">

                    <div class="mb-3">
                        <label class="form-label">Ho ten</label>
                        <input class="form-control" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">So dien thoai</label>
                        <input class="form-control" name="phone" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input class="form-control" name="email" type="email">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nganh quan tam</label>
                        <input class="form-control" name="intended_major" value="{{ $major }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tinh/thanh</label>
                        <input class="form-control" name="province">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Noi dung can tu van</label>
                        <textarea class="form-control" name="note" rows="3"></textarea>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">
                        <i class="fas fa-paper-plane me-1"></i> Gui dang ky
                    </button>
                    <div id="leadFormMessage" class="mt-3 small"></div>
                    <div id="leadRagResultBox" class="mt-3 p-3 bg-light border rounded text-dark" style="display: none; border-left: 4px solid #0d6efd !important;">
                        <h6 class="fw-bold mb-2 text-primary"><i class="fas fa-robot me-1"></i> Tra loi nhanh tu tro ly ao:</h6>
                        <div id="leadRagResultContent" style="white-space: pre-wrap; font-size: 14px; line-height: 1.5;"></div>
                    </div>
                </form>
            </div>
        </div>

        <div class="lead-rag-card">
            <div class="row g-3 align-items-start">
                <div class="col-lg-5">
                    <h2 class="h5 fw-bold mb-2">Hoi nhanh tro ly tuyen sinh</h2>
                    <p class="text-muted mb-3">Tro ly tra cuu tren kho quy che da nhap trong CMS.</p>
                    <textarea id="publicRagQuestion" class="form-control mb-2" rows="4" placeholder="VD: Dieu kien xet tuyen hoc ba nhu the nao?"></textarea>
                    <button id="publicAskRag" class="btn btn-outline-primary btn-sm" type="button">
                        <i class="fas fa-robot me-1"></i> Hoi tro ly
                    </button>
                </div>
                <div class="col-lg-7">
                    <div id="publicRagAnswer" class="rag-answer-box">Nhap cau hoi de tra cuu thong tin tuyen sinh.</div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('js')
<script src="{{ asset('js/admission-lead-form.js') }}"></script>
@endsection
