<?php

use Illuminate\Support\Facades\DB;

if (!function_exists('menu_full_slug')) {
    function menu_full_slug($menuId)
    {
        $slugs = [];
        while ($menuId) {
            $menu = DB::table('menus')->where('id', $menuId)->first();
            if (!$menu) break;
            $slugs[] = $menu->slug;
            $menuId = $menu->id_cha;
        }
        return implode('/', array_reverse($slugs));
    }
}
