@if (isset($menus) && $menus->count())
    <ul>
        @foreach ($menus as $menu)
            <li class="nav-item">
                <a href="{{ route('page.show', ['slug' => $menu->slug]) }}" class="nav-link"><i
                        class="typcn typcn-chart-area-outline"></i>
                    {{ $menu->name }}
                </a>
                @if ($menu->children && $menu->children->count())
                    @include('partials.menu', ['menus' => $menu->children])
                @endif
            </li>
        @endforeach
    </ul>
@endif
