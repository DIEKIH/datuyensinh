{{-- THÊM TRONG NHÓM MENU CMS TUYỂN SINH --}}
<a href="{{ route('admin.admission-cms.campaigns') }}"
   class="{{ request()->routeIs('admin.admission-cms.campaigns*') ? 'active' : '' }}">
    <i class="fas fa-bullhorn"></i>
    <span>Chiến dịch Lead</span>
</a>
