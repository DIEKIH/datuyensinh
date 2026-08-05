<nav class="admission-nav" aria-label="Điều hướng CMS tuyển sinh">
    <a class="admission-nav-link {{ request()->routeIs('admin.admission-cms.dashboard') ? 'active' : '' }}"
        href="{{ route('admin.admission-cms.dashboard') }}">
        <i class="fas fa-chart-pie"></i> Tổng quan
    </a>
    <a class="admission-nav-link {{ request()->routeIs('admin.admission-cms.leads') ? 'active' : '' }}"
        href="{{ route('admin.admission-cms.leads') }}">
        <i class="fas fa-users"></i> Leads
    </a>
    <a class="admission-nav-link {{ request()->routeIs('admin.admission-cms.approvals') ? 'active' : '' }}"
        href="{{ route('admin.admission-cms.approvals') }}">
        <i class="fas fa-user-check"></i> Duyệt AI
    </a>
    <a class="admission-nav-link {{ request()->routeIs('admin.admission-cms.campaigns') ? 'active' : '' }}"
        href="{{ route('admin.admission-cms.campaigns') }}">
        <i class="fas fa-bullhorn"></i> Chiến dịch
    </a>
    <a class="admission-nav-link {{ request()->routeIs('admin.admission-cms.openai') ? 'active' : '' }}"
        href="{{ route('admin.admission-cms.openai') }}">
        <i class="fas fa-brain"></i> OpenAI / RAG
    </a>
    <a class="admission-nav-link {{ request()->routeIs('admin.admission-cms.scoring') ? 'active' : '' }}"
        href="{{ route('admin.admission-cms.scoring') }}">
        <i class="fas fa-sliders-h"></i> Chấm điểm
    </a>
    <a class="admission-nav-link {{ request()->routeIs('admin.admission-cms.toxic') ? 'active' : '' }}"
        href="{{ route('admin.admission-cms.toxic') }}">
        <i class="fas fa-shield-alt"></i> Cảnh báo MXH
    </a>
    <a class="admission-nav-link {{ request()->routeIs('admin.admission-cms.social-posts') ? 'active' : '' }}"
        href="{{ route('admin.admission-cms.social-posts') }}">
        <i class="fas fa-share-alt"></i> Bài đã đăng
    </a>
    <a class="admission-nav-link {{ request()->routeIs('admin.admission-cms.n8n') ? 'active' : '' }}"
        href="{{ route('admin.admission-cms.n8n') }}">
        <i class="fas fa-project-diagram"></i> n8n
    </a>
</nav>
