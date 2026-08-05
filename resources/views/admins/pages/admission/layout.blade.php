@extends('admins.layouts.app')

@section('title')
    @yield('admission_title') | CMS tuyển sinh
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admins/admission_cms.css') }}">
    @yield('admission_css')
@endsection

@section('content')
<div class="admission-page">
    <header class="admission-topbar">
        <div>
            <div class="admission-eyebrow">
                <i class="fas fa-user-graduate"></i>
                CMS tuyển sinh
            </div>
            <h1 class="admission-heading">@yield('admission_title')</h1>
            <p class="admission-description">@yield('admission_description')</p>
        </div>

        @hasSection('admission_actions')
            <div class="admission-actions">
                @yield('admission_actions')
            </div>
        @endif
    </header>

    @include('admins.pages.admission.partials.nav')

    @yield('admission_content')
</div>
@endsection

@section('js')
    <script src="{{ asset('js/admins/admission/common.js') }}"></script>
    @yield('admission_js')
@endsection
