<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;


class MenuController extends Controller
{

    public function show($slug)
    {
        try {
            $slugs = explode('/', trim($slug, '/'));
            $lastSlug = end($slugs);

            // DEBUG: Log để xem giá trị
            Log::info('=== DEBUG START ===');
            Log::info('Full slug: ' . $slug);
            Log::info('Slugs array: ' . json_encode($slugs));
            Log::info('Last slug: ' . $lastSlug);

            // --- 1. Kiểm tra xem slug cuối có phải là bài viết không ---
            $post = DB::table('baiviet')
                ->where('slug', $lastSlug)

                ->first();


            Log::info('Post found: ' . ($post ? 'YES (ID: ' . $post->id . ')' : 'NO'));

            if ($post) {
                Log::info('Post menu ID: ' . $post->idmenu);



                // Reload lại $post để views mới nhất hiển thị đúng
                $post = DB::table('baiviet')->where('id', $post->id)->first();


                // Tìm menu của bài viết
                $menu = DB::table('menus')->where('id', $post->idmenu)->first();

                Log::info('Menu found: ' . ($menu ? 'YES (Name: ' . $menu->name . ')' : 'NO'));

                if (!$menu) {
                    throw new \Exception('Menu không tồn tại cho bài viết này');
                }

                // Lấy tác giả
                // Lấy tác giả từ bảng trung gian baiviet_tacgia
                $tacgia = DB::table('baiviet_tacgia')
                    ->join('tacgia', 'tacgia.id', '=', 'baiviet_tacgia.tacgia_id')
                    ->where('baiviet_tacgia.baiviet_id', $post->id)
                    ->pluck('tacgia.ten')
                    ->implode(', ');
                if (empty($tacgia)) {
                    $tacgia = 'Không rõ tác giả';
                }

                // Lấy các bài viết khác cùng menu (kèm tên menu và slug menu)
                $baivietCungMenu = DB::table('baiviet')
                    ->join('menus', 'menus.id', '=', 'baiviet.idmenu')
                    ->select('baiviet.*', 'menus.name as tenmenu', 'menus.slug as menuslug')
                    ->where('baiviet.idmenu', $post->idmenu)
                    ->where('baiviet.id', '!=', $post->id)
                    ->whereNotNull('baiviet.slug')
                    ->orderBy('baiviet.ngaydang', 'desc')
                    ->limit(4)
                    ->get();

                // Bài viết kế tiếp cùng danh mục
                $nextPost = DB::table('baiviet')
                    ->join('menus', 'menus.id', '=', 'baiviet.idmenu')
                    ->select('baiviet.*', 'menus.slug as menuslug')
                    ->where('baiviet.danhmuc', $post->danhmuc)
                    ->where('baiviet.id', '!=', $post->id)
                    ->whereNotNull('baiviet.slug')
                    ->inRandomOrder()
                    ->first();

                // Tin nổi bật
                // $featuredPosts = DB::table('baiviet')
                //     ->join('menus', 'menus.id', '=', 'baiviet.idmenu')
                //     ->select('baiviet.*', 'menus.slug as menuslug')
                //     ->where('baiviet.is_featured', 1)
                //     
                //     ->whereNotNull('baiviet.slug')
                //     ->orderBy('baiviet.ngaydang', 'desc')
                //     ->limit(3)
                //     ->get();

                $featuredPosts = DB::table('baiviet')
                    ->join('menus', 'menus.id', '=', 'baiviet.idmenu')
                    ->select('baiviet.*', 'menus.slug as menuslug')
                    ->where('baiviet.is_featured', 1)
                    ->where('baiviet.idmenu', $menu->id)
                    ->whereNotNull('baiviet.slug')
                    ->orderBy('baiviet.ngaydang', 'desc')
                    ->limit(3)
                    ->get();

                // Tin liên quan
                $relatedPosts = [];
                if (!empty($post->bv_lienquan)) {
                    $relatedIds = explode(',', $post->bv_lienquan);
                    $relatedPosts = DB::table('baiviet')
                        ->join('menus', 'menus.id', '=', 'baiviet.idmenu')
                        ->select('baiviet.*', 'menus.slug as menuslug')
                        ->whereIn('baiviet.id', $relatedIds)
                        ->whereNotNull('baiviet.slug')
                        ->limit(3)
                        ->get();
                }

                Log::info('=== Rendering chitiet view ===');

                return view('users.pages.chitiet', compact(
                    'menu',
                    'post',
                    'tacgia',
                    'nextPost',
                    'featuredPosts',
                    'relatedPosts',
                    'baivietCungMenu'
                ));
            }

            // Nếu không phải bài viết, xử lý menu
            Log::info('Not a post, processing as menu');

            $level = count($slugs);

            // Menu cấp 1
            $menus = DB::table('menus')->where('id_cha', 0)->get();

            $level1 = DB::table('menus')->where('slug', $slugs[0])->first();
            $currentMenu = DB::table('menus')->where('slug', $lastSlug)->first() ?? $level1;

            if (!$level1) {
                Log::error('Level 1 menu not found for slug: ' . $slugs[0]);
                throw new \Exception('Menu không tồn tại');
            }

            Log::info('Level 1 menu: ' . $level1->name . ' (ID: ' . $level1->id . ')');
            Log::info('Loai man hinh: ' . $level1->loaimanhinh);

            $loaimanhinh = $level1->loaimanhinh;

            switch ($loaimanhinh) {
                case '1':
                    Log::info('Processing loaimanhinh = 1');
                    $baiviet_posts = DB::table('baiviet')
                        ->join('danhmuc', 'danhmuc.id', '=', 'baiviet.danhmuc')
                        ->where('baiviet.idmenu', $level1->id)
                        ->whereNotNull('baiviet.slug')
                        ->orderBy('baiviet.ngaydang', 'desc')
                        ->select('baiviet.*', 'danhmuc.tendanhmuc')
                        ->paginate(5);
                    $danhmuc_posts = DB::table('baiviet')
                        ->join('danhmuc', 'danhmuc.id', '=', 'baiviet.danhmuc')
                        ->select('tendanhmuc', 'danhmuc')
                        ->where('idmenu', $level1->id)

                        ->distinct('danhmuc')
                        ->get();

                    $posts = [
                        'baiviet_posts' => $baiviet_posts,
                        'danhmuc_posts' => $danhmuc_posts,
                    ];

                    // Log::info('Total posts found: ' . $baiviet_posts->count());

                    return view('users.menus.shows', compact('level1', 'currentMenu', 'menus', 'posts', 'loaimanhinh', 'slug'));
                    break;

                case '2':
                    $isChild = ($currentMenu && $currentMenu->id !== $level1->id);

                    if ($isChild) {
                        // Menu con: chỉ load bài của menu con đó
                        $query = DB::table('baiviet')
                            ->join('danhmuc', 'danhmuc.id', '=', 'baiviet.danhmuc')
                            ->where('baiviet.idmenu', $currentMenu->id)
                            ->whereNotNull('baiviet.slug')
                            ->orderBy('baiviet.ngaydang', 'desc')
                            ->select('baiviet.*', 'danhmuc.tendanhmuc');
                    } else {
                        // Menu cha: load bài của level1 + tất cả menu con
                        $childIds = DB::table('menus')
                            ->where('id_cha', $level1->id)
                            ->pluck('id')
                            ->toArray();

                        $allIds = array_merge([$level1->id], $childIds);

                        $query = DB::table('baiviet')
                            ->join('danhmuc', 'danhmuc.id', '=', 'baiviet.danhmuc')
                            ->whereIn('baiviet.idmenu', $allIds)

                            ->whereNotNull('baiviet.slug')
                            ->orderBy('baiviet.ngaydang', 'desc')
                            ->select('baiviet.*', 'danhmuc.tendanhmuc');
                    }

                    // $baiviet_posts = $query->paginate(10);
                    $baiviet_posts = $query->get();

                    $danhmuc_posts = DB::table('baiviet')
                        ->join('danhmuc', 'danhmuc.id', '=', 'baiviet.danhmuc')
                        ->select('tendanhmuc', 'danhmuc')
                        ->where('baiviet.idmenu', $isChild ? $currentMenu->id : $level1->id)
                        ->distinct('danhmuc')
                        ->get();

                    $posts = [
                        'baiviet_posts' => $baiviet_posts,
                        'danhmuc_posts' => $danhmuc_posts,
                    ];

                    $menuCha = $level1->id_cha != 0
                        ? DB::table('menus')->where('id', $level1->id_cha)->first()
                        : null;

                    return view('users.menus.shows', compact('level1', 'currentMenu', 'menus', 'posts', 'loaimanhinh', 'slug', 'menuCha'));
                    break;


                case '3':
                    Log::info('Processing loaimanhinh = 3');

                    $baiviet_posts = DB::table('baiviet')
                        ->join('danhmuc', 'danhmuc.id', '=', 'baiviet.danhmuc')
                        ->where('baiviet.idmenu', $level1->id)

                        ->whereNotNull('baiviet.slug')
                        ->orderBy('baiviet.ngaydang', 'desc')
                        ->select('baiviet.*', 'danhmuc.tendanhmuc')
                        ->paginate(20);

                    $posts = [
                        'baiviet_posts' => $baiviet_posts,
                        'danhmuc_posts' => [],
                    ];

                    $ttts_posts = DB::table('baiviet')
                        ->where('idmenu', 5)

                        ->whereNotNull('slug')
                        ->orderBy('ngaydang', 'desc')
                        ->get();

                    $dhcq_posts = DB::table('baiviet')
                        ->where('idmenu', 7)

                        ->whereNotNull('slug')
                        ->orderBy('ngaydang', 'desc')
                        ->get();

                    $vhvl_posts = DB::table('baiviet')
                        ->where('idmenu', 8)

                        ->whereNotNull('slug')
                        ->orderBy('ngaydang', 'desc')
                        ->get();

                    $tintuc_posts = DB::table('baiviet')
                        ->join('danhmuc', 'danhmuc.id', '=', 'baiviet.danhmuc')
                        ->where('baiviet.danhmuc', 2)

                        ->whereNotNull('baiviet.slug')
                        ->orderBy('baiviet.ngaydang', 'desc')
                        ->select('baiviet.*', 'danhmuc.tendanhmuc')
                        ->limit(5)
                        ->get();
                    $highlight_stats = DB::table('highlight_stats')
                        ->orderBy('thutu', 'asc')
                        ->orderBy('id', 'asc')
                        ->limit(3)
                        ->get();

                    $defaultVector = [
                        'logic' => 0,
                        'data' => 0,
                        'technical' => 0,
                        'creativity' => 0,
                        'communication' => 0,
                        'management' => 0,
                        'language' => 0,
                        'legal' => 0,
                        'experiment' => 0,
                        'fieldwork' => 0,
                    ];

                    $quiz_nganhs = DB::table('nganhs')
                        ->leftJoin('khoads', 'khoads.id', '=', 'nganhs.khoa_id')
                        ->select(
                            'nganhs.id',
                            'nganhs.ten_nganh',
                            'nganhs.ma_nganh',
                            'nganhs.slug',
                            'nganhs.loai_hinh',
                            'nganhs.bac_dao_tao',
                            'nganhs.thoi_gian',
                            'nganhs.so_tin_chi',
                            'nganhs.chi_tieu',
                            'nganhs.hoc_phi',
                            'nganhs.hinh_thuc_xet',
                            'nganhs.co_so',
                            'nganhs.image_url',
                            'nganhs.tomtat',
                            'nganhs.profile_vector',
                            'khoads.ten_khoa',
                            'khoads.ma_khoa'
                        )
                        ->where(function ($query) {
                            $query->where('nganhs.loai_hinh', 'ĐH chính quy')
                                ->orWhere('nganhs.loai_hinh', 'Đại học chính quy')
                                ->orWhereNull('nganhs.loai_hinh');
                        })
                        ->orderBy('khoads.ten_khoa')
                        ->orderBy('nganhs.ten_nganh')
                        ->get()
                        ->map(function ($n) use ($defaultVector) {
                            $profileVector = $defaultVector;

                            if (!empty($n->profile_vector)) {
                                $decoded = json_decode($n->profile_vector, true);

                                if (is_array($decoded)) {
                                    foreach ($defaultVector as $key => $value) {
                                        $profileVector[$key] = isset($decoded[$key])
                                            ? (float) $decoded[$key]
                                            : 0;
                                    }
                                }
                            }

                            $tohops = DB::table('nganh_tohop')
                                ->join('to_hops', 'to_hops.id', '=', 'nganh_tohop.tohop_id')
                                ->where('nganh_tohop.nganh_id', $n->id)
                                ->orderBy('to_hops.ma_to_hop')
                                ->pluck('to_hops.ma_to_hop')
                                ->implode(', ');

                            return [
                                'id' => $n->id,
                                'name' => $n->ten_nganh,
                                'khoa' => $n->ten_khoa ?? 'Đang cập nhật',
                                'ma' => $n->ma_nganh,
                                'slug' => $n->slug,
                                'loai_hinh' => $n->loai_hinh,
                                'bac_dao_tao' => $n->bac_dao_tao,
                                'profile_vector' => $profileVector,
                                'chitieu' => $n->chi_tieu,
                                'hocphi' => $n->hoc_phi
                                    ? number_format($n->hoc_phi, 0, ',', '.') . ' đ'
                                    : 'Đang cập nhật',
                                'tohops' => $tohops ?: 'Đang cập nhật',
                                'thoigian' => $n->thoi_gian ?: 'Đang cập nhật',
                                'coso' => $n->co_so ?: 'Đang cập nhật',
                                'tomtat' => $n->tomtat ?: '',
                                'image_url' => $n->image_url ? asset($n->image_url) : null,
                                'url' => url('nganh/' . $n->slug),
                            ];
                        });

                    return view('users.menus.shows', compact(
                        'level1',
                        'currentMenu',
                        'menus',
                        'posts',
                        'loaimanhinh',
                        'slug',
                        'ttts_posts',
                        'dhcq_posts',
                        'vhvl_posts',
                        'tintuc_posts',
                        'quiz_nganhs',
                        'highlight_stats'
                    ));
                    break;

                case '4':
                    Log::info('Processing loaimanhinh = 4');
                    $menu_khoitao = collect([
                        (object)[
                            'id' => -1,
                            'name' => "",
                            'slug' => "",
                        ]
                    ]);

                    //Menu cấp 2
                    $level2 = DB::table('menus')
                        ->where('id_cha', $level1->id)
                        ->orderBy('thutu')
                        ->get();

                    if ($level2->isEmpty()) {
                        $level2 = $menu_khoitao;
                    }

                    $level >= 2 ? $id_level2 = DB::table('menus')->where('slug', $slugs[1])->first()->id : $id_level2 = $level2[0]->id;

                    //Menu cấp 3   
                    $level3 = DB::table('menus')
                        ->where('id_cha', $id_level2)
                        ->orderBy('thutu')
                        ->get();

                    if ($level3->isEmpty()) {
                        $level3 = $menu_khoitao;
                    }

                    if ($level >= 3) {
                        $l3 = DB::table('menus')->where('slug', $slugs[2])->first();
                        $id_level3 = $l3->id;
                        $danhmuc_name = $l3->name;
                    } else {
                        $id_level3 = $level3[0]->id;
                        $danhmuc_name = "";
                    }

                    //BÀI VIẾT
                    $baiviet_posts = DB::table('baiviet')
                        ->join('danhmuc', 'danhmuc.id', '=', 'baiviet.danhmuc')
                        ->where('idmenu', $id_level3)

                        ->whereNotNull('baiviet.slug')
                        ->orderBy('ngaydang', 'desc')
                        ->get();

                    $posts = [
                        'baiviet_posts' => $baiviet_posts,
                        'danhmuc_posts' => '',
                        'danhmuc_name' => $danhmuc_name,
                    ];

                    return view('menus.shows', compact(
                        'menus',
                        'level1',
                        'id_level2',
                        'level2',
                        'id_level3',
                        'level3',
                        'posts',
                        'loaimanhinh'
                    ));
                    break;

                default:
                    Log::error('Invalid loaimanhinh: ' . $loaimanhinh);
                    throw new \Exception('Loại màn hình không hợp lệ');
                    break;
            }
        } catch (\Exception $e) {
            Log::error('=== ERROR ===');
            Log::error('Error message: ' . $e->getMessage());
            Log::error('Error file: ' . $e->getFile());
            Log::error('Error line: ' . $e->getLine());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->view('users.errors.menu-not-found', [
                'error' => $e->getMessage(),
                'slug' => $slug ?? 'unknown'
            ], 404);
        }
    }
}
