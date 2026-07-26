<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PostController extends Controller
{
    public function show($slug)
{
    $post = DB::table('baiviet')->where('slug', $slug)->first();

    if (!$post) {
        abort(404);
    }

    // Tác giả từ bảng trung gian
    $tacgia = DB::table('baiviet_tacgia')
        ->join('tacgia', 'tacgia.id', '=', 'baiviet_tacgia.tacgia_id')
        ->where('baiviet_tacgia.baiviet_id', $post->id)
        ->pluck('tacgia.ten')
        ->implode(', ');

    // Menu hiện tại
    $menu = DB::table('menus')->where('id', $post->idmenu)->first();

    // Bài viết kế tiếp (random cùng menu, có thêm menuslug)
    $nextPost = DB::table('baiviet')
        ->leftJoin('menus', 'baiviet.idmenu', '=', 'menus.id')
        ->select('baiviet.*', 'menus.slug as menuslug')
        ->where('baiviet.idmenu', $post->idmenu)
        ->where('baiviet.id', '!=', $post->id)
        
        ->inRandomOrder()
        ->first();

    // Bài viết cùng menu (sidebar)
    $baivietCungMenu = DB::table('baiviet')
        ->leftJoin('menus', 'baiviet.idmenu', '=', 'menus.id')
        ->select(
            'baiviet.*',
            'menus.slug as menuslug',
            'menus.name as tenmenu'
        )
        ->where('baiviet.idmenu', $post->idmenu)
        ->where('baiviet.id', '!=', $post->id)
        
        ->orderBy('baiviet.ngaydang', 'desc')
        ->limit(5)
        ->get();

    // Bài viết nổi bật (is_featured = 1, có menuslug)
    $featuredPosts = DB::table('baiviet')
        ->leftJoin('menus', 'baiviet.idmenu', '=', 'menus.id')
        ->select(
            'baiviet.*',
            'menus.slug as menuslug'
        )
        ->where('baiviet.is_featured', 1)
        
        ->orderBy('baiviet.ngaydang', 'desc')
        ->limit(3)
        ->get();

    // Bài viết liên quan (cùng menu, có menuslug)
    $relatedPosts = DB::table('baiviet')
        ->leftJoin('menus', 'baiviet.idmenu', '=', 'menus.id')
        ->select(
            'baiviet.*',
            'menus.slug as menuslug'
        )
        ->where('baiviet.idmenu', $post->idmenu)
        ->where('baiviet.id', '!=', $post->id)
        
        ->orderBy('baiviet.ngaydang', 'desc')
        ->limit(3)
        ->get();

    return view('users.pages.chitiet', compact(
        'post',
        'tacgia',
        'menu',
        'nextPost',
        'baivietCungMenu',
        'featuredPosts',
        'relatedPosts'
    ));
}


    public function chitiet()
    {
        return view('users.pages.chitiet');
    }
}
