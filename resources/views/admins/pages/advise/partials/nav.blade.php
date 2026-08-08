<nav class="admission-nav">
    <a href="{{ route('admin.advise.index') }}"
       class="admission-nav-link {{ request()->routeIs('admin.advise.index') ? 'active' : '' }}">
       <i class="fas fa-list-ul"></i> Phiên chat
    </a>
    <a href="{{ route('admin.advise.tickets') }}"
       class="admission-nav-link {{ request()->routeIs('admin.advise.tickets') ? 'active' : '' }}">
       <i class="fas fa-headset"></i> Yêu cầu tư vấn
    </a>
    <a href="{{ route('admin.advise.library') }}"
       class="admission-nav-link {{ request()->routeIs('admin.advise.library') ? 'active' : '' }}">
       <i class="fas fa-book-open"></i> Kho câu hỏi
    </a>
    <a href="{{ route('admin.admission-cms.openai') }}"
       class="admission-nav-link {{ request()->routeIs('admin.admission-cms.openai') ? 'active' : '' }}">
       <i class="fas fa-brain"></i> Quản lý OpenAI
    </a>
</nav>
