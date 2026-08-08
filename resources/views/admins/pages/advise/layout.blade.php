@extends('admins.layouts.app')

@section('title')
    @yield('advise_title') | Quản lý Tư vấn
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admins/admission_cms.css') }}">
    <style>
        /* Base styles for advise to match cms */
        .admission-page {
            background-color: #f8f9fc;
            min-height: 100vh;
            padding: 24px;
        }
        .admission-topbar {
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        .admission-eyebrow {
            color: #4e73df;
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .admission-heading {
            color: #5a5c69;
            font-size: 1.75rem;
            font-weight: 400;
            margin: 0 0 4px 0;
        }
        .admission-description {
            color: #858796;
            margin: 0;
        }
    </style>
    @yield('advise_css')
@endsection

@section('content')
<div class="admission-page">
    <header class="admission-topbar">
        <div>
            <div class="admission-eyebrow">
                <i class="fas fa-robot"></i>
                Quản lý Tư vấn
            </div>
            <h1 class="admission-heading">@yield('advise_title')</h1>
            <p class="admission-description">@yield('advise_description')</p>
        </div>

        @hasSection('advise_actions')
            <div class="admission-actions">
                @yield('advise_actions')
            </div>
        @endif
    </header>

    @include('admins.pages.advise.partials.nav')

    @yield('advise_content')
</div>
@endsection

@section('js')
    @yield('advise_js')
@endsection
