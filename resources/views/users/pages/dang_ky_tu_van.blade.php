@extends('users.layouts.app')

@section('title', 'Đăng ký tư vấn tuyển sinh')

@section('css')
<link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
    rel="stylesheet"
>

<style>
    :root {
        --lead-primary: #1769aa;
        --lead-primary-dark: #0d4f86;
        --lead-secondary: #2f9ed0;
        --lead-accent: #f59e0b;
        --lead-success: #059669;
        --lead-text: #17324d;
        --lead-muted: #65778a;
        --lead-border: #dbe5ee;
        --lead-surface: #ffffff;
        --lead-soft: #f4f9fc;
        --lead-shadow: 0 24px 60px rgba(28, 74, 112, .14);
    }

    body {
        font-family: 'Inter', sans-serif;
        background: #eef5f9;
    }

    .lead-page {
        position: relative;
        min-height: 100vh;
        padding: 64px 0;
        overflow: hidden;
        background:
            radial-gradient(
                circle at 8% 15%,
                rgba(78, 165, 211, .16),
                transparent 30%
            ),
            radial-gradient(
                circle at 92% 10%,
                rgba(23, 105, 170, .13),
                transparent 28%
            ),
            linear-gradient(135deg, #f7fbfe 0%, #eaf4fa 100%);
    }

    .lead-page::before,
    .lead-page::after {
        content: '';
        position: absolute;
        border-radius: 50%;
        pointer-events: none;
    }

    .lead-page::before {
        width: 360px;
        height: 360px;
        top: -180px;
        right: -100px;
        border: 1px solid rgba(23, 105, 170, .12);
        box-shadow:
            0 0 0 48px rgba(23, 105, 170, .035),
            0 0 0 96px rgba(23, 105, 170, .02);
    }

    .lead-page::after {
        width: 220px;
        height: 220px;
        bottom: -115px;
        left: -80px;
        background: rgba(47, 158, 208, .08);
    }

    .lead-wrap {
        position: relative;
        z-index: 1;
        width: min(1180px, calc(100% - 32px));
        margin: 0 auto;
    }

    .lead-shell {
        display: grid;
        grid-template-columns: minmax(0, .88fr) minmax(560px, 1.12fr);
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, .86);
        border-radius: 28px;
        background: rgba(255, 255, 255, .88);
        box-shadow: var(--lead-shadow);
        backdrop-filter: blur(18px);
    }

    .lead-intro {
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-height: 760px;
        padding: 56px 48px;
        overflow: hidden;
        color: #fff;
        background:
            linear-gradient(
                145deg,
                rgba(13, 79, 134, .97),
                rgba(23, 105, 170, .94) 58%,
                rgba(47, 158, 208, .9)
            );
    }

    .lead-intro::before {
        content: '';
        position: absolute;
        width: 330px;
        height: 330px;
        top: -175px;
        right: -130px;
        border-radius: 50%;
        border: 1px solid rgba(255, 255, 255, .2);
        box-shadow:
            0 0 0 52px rgba(255, 255, 255, .045),
            0 0 0 104px rgba(255, 255, 255, .025);
    }

    .lead-intro::after {
        content: '';
        position: absolute;
        width: 180px;
        height: 180px;
        left: -80px;
        bottom: -70px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .08);
    }

    .lead-intro-content {
        position: relative;
        z-index: 1;
    }

    .lead-kicker {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 22px;
        padding: 7px 12px;
        border: 1px solid rgba(255, 255, 255, .22);
        border-radius: 999px;
        background: rgba(255, 255, 255, .1);
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .65px;
        text-transform: uppercase;
    }

    .lead-title {
        max-width: 530px;
        margin: 0 0 20px;
        font-size: clamp(34px, 4vw, 48px);
        line-height: 1.12;
        font-weight: 800;
        letter-spacing: -1.4px;
    }

    .lead-title span {
        color: #cceeff;
    }

    .lead-desc {
        max-width: 520px;
        margin: 0 0 32px;
        color: rgba(255, 255, 255, .82);
        font-size: 16px;
        line-height: 1.75;
    }

    .lead-points {
        display: grid;
        gap: 13px;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .lead-points li {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 13px 14px;
        border: 1px solid rgba(255, 255, 255, .13);
        border-radius: 13px;
        background: rgba(255, 255, 255, .075);
        color: rgba(255, 255, 255, .92);
        font-size: 14px;
        line-height: 1.5;
    }

    .lead-point-icon {
        width: 26px;
        height: 26px;
        flex: 0 0 26px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-top: 1px;
        border-radius: 8px;
        background: rgba(255, 255, 255, .15);
        color: #dff5ff;
        font-size: 12px;
    }

    .lead-contact-note {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-top: 30px;
        padding-top: 24px;
        border-top: 1px solid rgba(255, 255, 255, .16);
        color: rgba(255, 255, 255, .78);
        font-size: 13px;
        line-height: 1.5;
    }

    .lead-contact-note i {
        font-size: 19px;
        color: #dff5ff;
    }

    .lead-form-panel {
        padding: 38px 42px 42px;
        background: rgba(255, 255, 255, .97);
    }

    .lead-form-heading {
        display: flex;
        align-items: center;
        gap: 13px;
        margin-bottom: 10px;
    }

    .lead-form-icon {
        width: 44px;
        height: 44px;
        flex: 0 0 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 13px;
        color: var(--lead-primary);
        background: #e8f4fb;
        font-size: 18px;
    }

    .lead-form-title {
        margin: 0;
        color: var(--lead-text);
        font-size: 23px;
        font-weight: 800;
        letter-spacing: -.35px;
    }

    .lead-form-subtitle {
        margin: 0 0 26px;
        color: var(--lead-muted);
        font-size: 13px;
        line-height: 1.6;
    }

    .lead-required-note {
        margin-left: auto;
        color: #8a99a8;
        font-size: 11px;
        white-space: nowrap;
    }

    .lead-section {
        margin-bottom: 23px;
        padding-bottom: 3px;
    }

    .lead-section-title {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 14px;
        color: #435b70;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: .45px;
        text-transform: uppercase;
    }

    .lead-section-title::after {
        content: '';
        height: 1px;
        flex: 1;
        background: #e8eef3;
    }

    .lead-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 17px;
    }

    .lead-form-grid .form-group {
        min-width: 0;
    }

    .lead-form-grid > .form-group:only-child,
    .lead-form-grid > .form-group.lead-field-full {
        grid-column: 1 / -1;
    }

    .form-group {
        position: relative;
        margin-bottom: 17px;
    }

    .lead-form-grid .form-group {
        margin-bottom: 0;
    }

    .form-label {
        display: flex;
        align-items: center;
        gap: 5px;
        margin-bottom: 7px;
        color: #3f5366;
        font-size: 13px;
        font-weight: 700;
    }

    .required-mark {
        color: #dc2626;
    }

    .form-control {
        width: 100%;
        min-height: 46px;
        padding: 11px 13px;
        border: 1px solid var(--lead-border);
        border-radius: 11px;
        outline: none;
        background: #fff;
        color: #20384d;
        font-size: 14px;
        transition:
            border-color .18s ease,
            box-shadow .18s ease,
            background .18s ease;
    }

    .form-control::placeholder {
        color: #a4b1bd;
    }

    .form-control:hover {
        border-color: #b7cad9;
    }

    .form-control:focus {
        border-color: var(--lead-primary);
        box-shadow: 0 0 0 4px rgba(23, 105, 170, .1);
    }

    .form-control:disabled {
        cursor: not-allowed;
        color: #8493a2;
        background: #f3f6f8;
    }

    select.form-control {
        cursor: pointer;
    }

    textarea.form-control {
        min-height: 105px;
        resize: vertical;
        line-height: 1.55;
    }

    .dynamic-checkbox-group {
        display: flex;
        flex-wrap: wrap;
        gap: 9px;
        min-height: 46px;
        padding: 10px 11px;
        border: 1px solid var(--lead-border);
        border-radius: 11px;
        background: #fff;
    }

    .dynamic-checkbox-option {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin: 0;
        padding: 5px 8px;
        border-radius: 8px;
        background: #f3f8fb;
        color: #40586d;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
    }

    .dynamic-checkbox-option input {
        accent-color: var(--lead-primary);
    }

    .lead-submit-row {
        margin-top: 7px;
    }

    .btn-submit {
        width: 100%;
        min-height: 50px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
        padding: 13px 18px;
        border: 0;
        border-radius: 12px;
        color: #fff;
        background:
            linear-gradient(
                135deg,
                var(--lead-primary-dark),
                var(--lead-primary) 55%,
                var(--lead-secondary)
            );
        box-shadow: 0 12px 24px rgba(23, 105, 170, .2);
        font-size: 14px;
        font-weight: 800;
        cursor: pointer;
        transition:
            transform .18s ease,
            box-shadow .18s ease,
            opacity .18s ease;
    }

    .btn-submit:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 15px 28px rgba(23, 105, 170, .27);
    }

    .btn-submit:active:not(:disabled) {
        transform: translateY(0);
    }

    .btn-submit:disabled {
        cursor: not-allowed;
        opacity: .72;
        box-shadow: none;
    }

    #leadFormMessage {
        min-height: 21px;
        margin-top: 13px;
        text-align: center;
        font-size: 13px;
        font-weight: 700;
    }

    #leadFormMessage.text-success {
        color: var(--lead-success) !important;
    }

    #leadFormMessage.text-danger {
        color: #dc2626 !important;
    }

    #leadRagResultBox {
        display: none;
        margin-top: 18px;
        padding: 16px 17px;
        border: 1px solid #cce3f0;
        border-radius: 12px;
        background: linear-gradient(135deg, #f2f9fd, #edf7fc);
        animation: leadFadeIn .25s ease-out;
    }

    .lead-ai-result-title {
        display: flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 7px;
        color: var(--lead-primary-dark);
        font-size: 13px;
        font-weight: 800;
    }

    #leadRagResultContent {
        color: #395267;
        font-size: 13px;
        line-height: 1.65;
        white-space: pre-wrap;
    }

    .lead-privacy-note {
        display: flex;
        align-items: flex-start;
        justify-content: center;
        gap: 7px;
        margin-top: 13px;
        color: #8291a0;
        font-size: 11px;
        line-height: 1.45;
        text-align: center;
    }

    .lead-privacy-note i {
        margin-top: 2px;
        color: var(--lead-success);
    }

    @keyframes leadFadeIn {
        from {
            opacity: 0;
            transform: translateY(4px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media (max-width: 1060px) {
        .lead-shell {
            grid-template-columns: 1fr;
        }

        .lead-intro {
            min-height: auto;
            padding: 45px 42px;
        }

        .lead-title {
            max-width: 720px;
        }

        .lead-desc {
            max-width: 720px;
        }

        .lead-points {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .lead-points li {
            height: 100%;
        }
    }

    @media (max-width: 767.98px) {
        .lead-page {
            padding: 28px 0;
        }

        .lead-wrap {
            width: min(100% - 20px, 1180px);
        }

        .lead-shell {
            border-radius: 20px;
        }

        .lead-intro {
            padding: 34px 24px;
        }

        .lead-title {
            font-size: 33px;
        }

        .lead-desc {
            font-size: 14px;
        }

        .lead-points {
            grid-template-columns: 1fr;
        }

        .lead-form-panel {
            padding: 29px 22px 32px;
        }

        .lead-form-grid {
            grid-template-columns: 1fr;
            gap: 15px;
        }

        .lead-form-grid > .form-group {
            grid-column: auto;
        }

        .lead-required-note {
            display: none;
        }
    }

    @media (max-width: 420px) {
        .lead-intro,
        .lead-form-panel {
            padding-left: 18px;
            padding-right: 18px;
        }

        .lead-title {
            font-size: 29px;
        }

        .lead-form-heading {
            align-items: flex-start;
        }
    }
</style>
@endsection

@section('content')
<section class="lead-page">
    <div class="lead-wrap">
        <div class="lead-shell">
            <aside class="lead-intro">
                <div class="lead-intro-content">
                    <div class="lead-kicker">
                        <i class="fas fa-graduation-cap"></i>
                        Tư vấn tuyển sinh
                    </div>

                    <h1 class="lead-title">
                        Đăng ký tư vấn và
                        <span>định hướng ngành học</span>
                    </h1>

                    <p class="lead-desc">
                        Để lại thông tin để đội ngũ tư vấn hỗ trợ giải đáp về
                        ngành đào tạo, phương thức xét tuyển, học phí và các nội
                        dung liên quan đến tuyển sinh.
                    </p>

                    <ul class="lead-points">
                        <li>
                            <span class="lead-point-icon">
                                <i class="fas fa-comments"></i>
                            </span>
                            <span>
                                Tiếp nhận và tổng hợp nhu cầu tư vấn của thí sinh.
                            </span>
                        </li>
                        <li>
                            <span class="lead-point-icon">
                                <i class="fas fa-filter-circle-dollar"></i>
                            </span>
                            <span>
                                Phân loại thông tin để hỗ trợ đúng nội dung quan tâm.
                            </span>
                        </li>
                        <li>
                            <span class="lead-point-icon">
                                <i class="fas fa-shield-halved"></i>
                            </span>
                            <span>
                                Thông tin đăng ký chỉ được sử dụng cho hoạt động tư vấn.
                            </span>
                        </li>
                    </ul>

                    <div class="lead-contact-note">
                        <i class="fas fa-circle-info"></i>
                        <span>
                            Các trường có dấu
                            <strong>(*)</strong>
                            là thông tin bắt buộc.
                        </span>
                    </div>
                </div>
            </aside>

            <main class="lead-form-panel">
                <div class="lead-form-heading">
                    <span class="lead-form-icon">
                        <i class="fas fa-user-pen"></i>
                    </span>

                    <div>
                        <h2 class="lead-form-title">
                            Thông tin đăng ký tư vấn
                        </h2>
                    </div>

                    <span class="lead-required-note">
                        <span class="required-mark">*</span>
                        Bắt buộc
                    </span>
                </div>

                <p class="lead-form-subtitle">
                    Vui lòng nhập thông tin chính xác để bộ phận tư vấn có thể
                    liên hệ và hỗ trợ phù hợp.
                </p>

                <form id="publicLeadForm" novalidate>
                    @csrf

                    <input
                        type="hidden"
                        name="utm_source"
                        value="{{ $utm['source'] ?? '' }}"
                    >
                    <input
                        type="hidden"
                        name="utm_medium"
                        value="{{ $utm['medium'] ?? '' }}"
                    >
                    <input
                        type="hidden"
                        name="utm_campaign"
                        value="{{ $utm['campaign'] ?? '' }}"
                    >
                    <input
                        type="hidden"
                        name="utm_content"
                        value="{{ $utm['content'] ?? '' }}"
                    >

                    <section class="lead-section">
                        <div class="lead-section-title">
                            Thông tin liên hệ
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="leadFullName">
                                Họ và tên
                                <span class="required-mark">*</span>
                            </label>
                            <input
                                id="leadFullName"
                                class="form-control"
                                name="full_name"
                                type="text"
                                autocomplete="name"
                                placeholder="Ví dụ: Nguyễn Văn A"
                                required
                            >
                        </div>

                        <div class="lead-form-grid">
                            <div class="form-group">
                                <label class="form-label" for="leadPhone">
                                    Số điện thoại
                                    <span class="required-mark">*</span>
                                </label>
                                <input
                                    id="leadPhone"
                                    class="form-control"
                                    name="phone"
                                    type="tel"
                                    autocomplete="tel"
                                    placeholder="Ví dụ: 0912345678"
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="leadEmail">
                                    Email
                                </label>
                                <input
                                    id="leadEmail"
                                    class="form-control"
                                    name="email"
                                    type="email"
                                    autocomplete="email"
                                    placeholder="email@example.com"
                                >
                            </div>
                        </div>
                    </section>

                    <section class="lead-section">
                        <div class="lead-section-title">
                            Thông tin học tập
                        </div>

                        <div class="lead-form-grid">
                            <div class="form-group">
                                <label class="form-label" for="selectProvince">
                                    Tỉnh / Thành phố
                                </label>
                                <select
                                    class="form-control"
                                    name="province"
                                    id="selectProvince"
                                >
                                    <option value="">
                                        Đang tải Tỉnh/Thành...
                                    </option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="selectDistrict">
                                    Quận / Huyện
                                </label>
                                <select
                                    class="form-control"
                                    name="district"
                                    id="selectDistrict"
                                    disabled
                                >
                                    <option value="">
                                        -- Chọn Quận/Huyện --
                                    </option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="inputSchool">
                                    Trường THPT
                                </label>
                                <input
                                    id="inputSchool"
                                    class="form-control"
                                    name="high_school"
                                    type="text"
                                    placeholder="Nhập tên trường THPT"
                                >
                            </div>

                            <div class="form-group">
                                <label
                                    class="form-label"
                                    for="leadIntendedMajor"
                                >
                                    Ngành quan tâm
                                </label>
                                <input
                                    id="leadIntendedMajor"
                                    class="form-control"
                                    name="intended_major"
                                    type="text"
                                    value="{{ $major ?? '' }}"
                                    placeholder="Ví dụ: Công nghệ thông tin"
                                >
                            </div>
                        </div>
                    </section>

                    <section
                        class="lead-section"
                        id="dynamicFieldsSection"
                        style="display: none;"
                    >
                        <div class="lead-section-title">
                            Thông tin bổ sung
                        </div>

                        <div
                            id="dynamicFieldsContainer"
                            class="lead-form-grid"
                        ></div>
                    </section>

                    <section class="lead-section">
                        <div class="lead-section-title">
                            Nhu cầu tư vấn
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="leadNote">
                                Nội dung cần tư vấn
                            </label>
                            <textarea
                                id="leadNote"
                                class="form-control"
                                name="note"
                                rows="4"
                                placeholder="Nhập nội dung cần được hỗ trợ..."
                            ></textarea>
                        </div>
                    </section>

                    <div class="lead-submit-row">
                        <button
                            id="leadSubmitButton"
                            class="btn-submit"
                            type="submit"
                        >
                            <i class="fas fa-paper-plane"></i>
                            <span>Gửi đăng ký tư vấn</span>
                        </button>

                        <div
                            id="leadFormMessage"
                            role="status"
                            aria-live="polite"
                        ></div>

                        <div id="leadRagResultBox">
                            <div class="lead-ai-result-title">
                                <i class="fas fa-robot"></i>
                                Trợ lý AI phản hồi
                            </div>
                            <div id="leadRagResultContent"></div>
                        </div>

                        <div class="lead-privacy-note">
                            <i class="fas fa-lock"></i>
                            <span>
                                Thông tin được gửi qua kết nối bảo mật và phục vụ
                                cho hoạt động tư vấn tuyển sinh.
                            </span>
                        </div>
                    </div>
                </form>
            </main>
        </div>
    </div>
</section>
@endsection

@section('js')
@php
    $leadFormJsVersion = @filemtime(
        public_path('js/admission-lead-form.js')
    ) ?: '1';
@endphp

<script src="{{ asset('js/trangchu.js') }}?v={{ filemtime(public_path('js/trangchu.js')) }}"></script>
<script
    src="{{ asset('js/admission-lead-form.js') }}?v={{ $leadFormJsVersion }}"
></script>
@endsection
