<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $pageTitle = $__env->yieldContent('title', $title ?? 'CTUT | Tuyển sinh');
    @endphp

    <title>{{ $pageTitle }}</title>
    <link rel="shortcut icon" href="{{ asset('images/system/logo.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('images/system/logo.png') }}">
    {{-- <title>@yield('title', '')</title> --}}
    {{-- <link rel="shortcut icon" href="{{ asset('images/favicon.png') }}"> --}}

    {{-- CSS chung --}}
    <link rel="stylesheet" href="{{ asset('css/bootstrap/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('css/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/bootstrap/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/bootstrap/css2.css') }}">
    <link rel="stylesheet" href="{{ asset('css/bootstrap/dataTables.dataTables.min.css') }}">
    <link rel="stylesheet" href="/css/admins/admin_dashboard.css">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
    

    <style>
    .dashboard-container {
        display: flex !important;
        min-height: 100vh;
    }

    .sidebar {
        position: sticky !important;
        top: 0 !important;
        height: 100vh !important;
        overflow-y: auto !important;
        overflow-x: hidden !important;
        flex-shrink: 0 !important;
    }

    .sidebar::-webkit-scrollbar { width: 4px; }
    .sidebar::-webkit-scrollbar-thumb {
        background: rgba(255,255,255,0.25);
        border-radius: 4px;
    }
    .sidebar::-webkit-scrollbar-track { background: transparent; }

    .main-content {
        flex: 1 !important;
        min-width: 0 !important;
        /* XÓA margin-left nếu admin_dashboard.css đang set cứng */
        margin-left: 0 !important;
        overflow-y: auto;
        height: 100vh;
    }
</style>
    {{-- CSS riêng của từng trang --}}
    @yield('css')
</head>

<body>
    <div class="dashboard-container">
        {{-- @include('admins.layouts.header') --}}
        @include('admins.layouts.sidebar')

        <main class="main-content">
            {{-- <header class="top-bar">
                <div class="menu-toggle">
                    <i class="fas fa-bars"></i>
                </div>

                <div class="user-menu">
                    <i class="fas fa-bell" style="font-size: 1.2rem; color: var(--text-secondary);"></i>
                    <span class="nav-badge" style="margin-left: 0;">3</span>
                    <div class="user-avatar">XP</div>
                    <span style="font-weight: 400; font-size: 14px;">X'eriya Ponald</span>
                </div>
            </header> --}}
            @yield('content')
        </main>

        {{-- @include('admins.layouts.footer') --}}
    </div>


    {{-- JS chung --}}

    <!-- jQuery -->
    <script src="{{ asset('js/bootstrap/jquery-3.6.0.min.js') }}"></script>

    <!-- Bootstrap -->
    <script src="{{ asset('js/bootstrap/bootstrap.bundle.min.js') }}"></script>

    <!-- DataTables -->
    <script src="{{ asset('js/bootstrap/dataTables.min.js') }}"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <script>
        toastr.options = {
            "closeButton": true,
            "progressBar": false,
            "positionClass": "toast-top-right",
            "timeOut": "2000"
        };
    </script>

    {{-- JS riêng của từng trang --}}
    <script>
        $(document).ready(function() {
            $('.menu-toggle').on('click', function() {
                $('.sidebar').toggleClass('active');
            });
        });
    </script>
    @yield('js')

</body>

</html>
