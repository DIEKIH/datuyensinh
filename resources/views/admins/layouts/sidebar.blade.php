<style>
    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        overflow-y: auto;
        overflow-x: hidden;
        z-index: 1000;
    }

    /* Ẩn scrollbar nhưng vẫn cuộn được */
    .sidebar::-webkit-scrollbar {
        width: 4px;
    }
    .sidebar::-webkit-scrollbar-thumb {
        background: rgba(255,255,255,0.2);
        border-radius: 4px;
    }
    .sidebar::-webkit-scrollbar-track {
        background: transparent;
    }
</style>

<nav class="sidebar">
    <div class="sidebar-header">
        <a href="#" class="logo">
            CTUT
        </a>
    </div>

    <div class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-item">
                <a href="{{ url('/admin/dashboard') }}"
                    class="nav-link {{ request()->is('admin/dashboard') ? 'active' : '' }}">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </div>

            <div class="nav-item">
                <a href="{{ url('admin/baiviet') }}"
                    class="nav-link {{ request()->is('admin/baiviet*') ? 'active' : '' }}">
                    <i class="fas fa-newspaper"></i> Bài viết
                </a>
            </div>
            <div class="nav-item">
                <a href="{{ url('admin/tacgia') }}"
                    class="nav-link {{ request()->is('admin/tacgia*') ? 'active' : '' }}">
                    <i class="fas fa-user-edit"></i> Tác giả
                </a>
            </div>
            <div class="nav-item">
                <a href="{{ url('admin/banner') }}"
                    class="nav-link {{ request()->is('admin/banner*') ? 'active' : '' }}">
                    <i class="fas fa-images"></i> Banner
                </a>
            </div>
            <div class="nav-item">
                <a href="{{ url('admin/nganhhoc') }}"
                    class="nav-link {{ request()->is('admin/nganhhoc*') ? 'active' : '' }}">
                    <i class="fas fa-graduation-cap"></i> Ngành học
                </a>
            </div>
            <div class="nav-item">
                <a href="{{ url('admin/advise') }}"
                    class="nav-link {{ request()->is('admin/advise*') ? 'active' : '' }}">
                    <i class="fas fa-robot"></i> Tư vấn
                </a>
            </div>

            <div class="nav-item">
                <a href="{{ url('admin/admission-cms') }}"
                    class="nav-link {{ request()->is('admin/admission-cms*') ? 'active' : '' }}">
                    <i class="fas fa-user-graduate"></i> CMS tuyển sinh
                </a>
            </div>

            <div class="nav-item">
                <a href="{{ url('admin/highlight-stats') }}"
                    class="nav-link {{ request()->is('admin/highlight-stats*') ? 'active' : '' }}">
                    <i class="fas fa-chart-bar"></i> Con số nổi bật
                </a>
            </div>

            
            {{-- <div class="nav-item">
                <a href="#" class="nav-link">
                    <i class="fas fa-box"></i>
                    Products
                </a>
            </div>
            <div class="nav-item">
                <a href="#" class="nav-link">
                    <i class="fas fa-tags"></i>
                    Categories
                </a>
            </div>
            <div class="nav-item">
                <a href="#" class="nav-link">
                    <i class="fas fa-users"></i>
                    Customers
                </a>
            </div>
            <div class="nav-item">
                <a href="#" class="nav-link">
                    <i class="fas fa-chart-bar"></i>
                    Reports
                </a>
            </div>
            <div class="nav-item">
                <a href="#" class="nav-link">
                    <i class="fas fa-ticket-alt"></i>
                    Coupons
                </a>
            </div>
            <div class="nav-item">
                <a href="#" class="nav-link">
                    <i class="fas fa-inbox"></i>
                    Inbox
                </a>
            </div> --}}
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Authentication</div>
            @if (session()->has('user'))
                <div class="nav-item">
                    <a href="{{ url('/logout') }}" class="nav-link"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                    <form id="logout-form" action="{{ url('/logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                </div>
            @else
                <div class="nav-item">
                    <a href="{{ url('/login') }}" class="nav-link">
                        <i class="fas fa-sign-in-alt"></i>
                        Sign In
                    </a>
                </div>
            @endif
        </div>
    </div>
</nav>
