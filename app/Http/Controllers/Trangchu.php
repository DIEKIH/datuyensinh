<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\VisitorLog;
use Exception;


class Trangchu extends Controller
{

function getMenus(Request $request)
    {
        // Lấy toàn bộ menu, sắp xếp theo thutu (thứ tự hiển thị)
        $allMenus = DB::table('menus')
            ->orderBy('thutu', 'asc')
            ->get();
 
        // Chuyển thành mảng PHP để dễ xử lý
        $menuArray = $allMenus->toArray();
 
        // Đệ quy build cây menu: truyền vào id_cha = 0 để lấy menu gốc
        $tree = $this->buildMenuTree($menuArray, 0);
 
        return response()->json(['data' => $tree]);
    }
 
    /**
     * [THÊM MỚI] Hàm đệ quy build cây menu
     * @param array $menuArray  Toàn bộ menu dạng mảng
     * @param int   $parentId   id_cha cần tìm con (0 = gốc)
     * @return array
     */
    private function buildMenuTree(array $menuArray, int $parentId): array
    {
        $branch = [];
 
        foreach ($menuArray as $menu) {
            if ((int)$menu->id_cha === $parentId) {
                // Tìm các menu con của menu này (đệ quy)
                $children = $this->buildMenuTree($menuArray, (int)$menu->id);
 
                $branch[] = [
                    'id'          => $menu->id,
                    'name'        => $menu->name,
                    'slug'        => $menu->slug,
                    'loaimanhinh' => $menu->loaimanhinh,
                    'thutu'       => $menu->thutu,
                    'children'    => $children,   
                ];
            }
        }
 
        return $branch;
    }
    // Banner
    function banners(Request $request)
    {
        $data = DB::table('banners')->orderBy('order', 'asc')->get();
        if ($data->isEmpty()) {
            return response()->json(['message' => 'Không có banner nào.'], 404);
        } else {
            $json_data['data'] = $data;
            return response()->json($json_data);
            // return response()->json(['data' => $banners], 200);
        }
    }


//     function tintucnoibat(Request $request)
// {
//     $data = DB::table('baiviet')
//         ->leftJoin('menus', 'baiviet.idmenu', '=', 'menus.id')
//         ->select('baiviet.*', 'menus.slug as menu_slug')
//         ->where('baiviet.idmenu', 2)
//         ->where('baiviet.is_featured', 1)
//         
//         ->orderBy('baiviet.ngaydang', 'desc')
//         ->orderBy('baiviet.id', 'desc')
//         ->limit(5)
//         ->get()
//         ->map(function ($item) {
//             $item->image_url = $item->image_url ? asset($item->image_url) : asset('images/system/Rectangle_3897.jpg');
//             return $item;
//         });

//     if ($data->isEmpty()) {
//         return response()->json(['message' => 'Không có tin tức nổi bật.'], 404);
//     }
//     return response()->json(['data' => $data]);
// }

// function tintucnho(Request $request)
// {
//     $data = DB::table('baiviet')
//         ->leftJoin('menus', 'baiviet.idmenu', '=', 'menus.id')
//         ->select('baiviet.*', 'menus.slug as menu_slug')
//         ->where('baiviet.idmenu', 2)
//         ->where('baiviet.is_featured', 0)
//         
//         ->orderBy('baiviet.ngaydang', 'desc')
//         ->limit(5)
//         ->get()
//         ->map(function ($item) {
//             $item->image_url = $item->image_url ? asset($item->image_url) : asset('images/system/Rectangle_3897.jpg');
//             return $item;
//         });

//     if ($data->isEmpty()) {
//         return response()->json(['message' => 'Không có tin tức.'], 404);
//     }
//     return response()->json(['data' => $data]);
// }

function tintucnoibat(Request $request)
{
    $childIds = DB::table('menus')->where('id_cha', 2)->pluck('id')->toArray();
    $allIds = array_merge([2], $childIds);

    $data = DB::table('baiviet')
        ->leftJoin('menus', 'baiviet.idmenu', '=', 'menus.id')
        ->select('baiviet.*', 'menus.slug as menu_slug', 'menus.id_cha as menu_id_cha')
        ->whereIn('baiviet.idmenu', $allIds)
        ->where('baiviet.is_featured', 1)
        
        ->orderBy('baiviet.ngaydang', 'desc')
        ->limit(5)
        ->get()
        ->map(function ($item) {
            if ($item->menu_id_cha != 0) {
                $parent = DB::table('menus')->where('id', $item->menu_id_cha)->first();
                $item->full_menu_slug = $parent ? $parent->slug . '/' . $item->menu_slug : $item->menu_slug;
            } else {
                $item->full_menu_slug = $item->menu_slug;
            }
            $item->image_url = $item->image_url ? asset($item->image_url) : asset('images/system/Rectangle_3897.jpg');
            return $item;
        });

    if ($data->isEmpty()) {
        return response()->json(['message' => 'Không có tin tức nổi bật.'], 404);
    }
    return response()->json(['data' => $data]);
}

function tintucnho(Request $request)
{
    $childIds = DB::table('menus')->where('id_cha', 2)->pluck('id')->toArray();
    $allIds = array_merge([2], $childIds);

    $data = DB::table('baiviet')
        ->leftJoin('menus', 'baiviet.idmenu', '=', 'menus.id')
        ->select('baiviet.*', 'menus.slug as menu_slug', 'menus.id_cha as menu_id_cha')
        ->whereIn('baiviet.idmenu', $allIds)
        ->where('baiviet.is_featured', 0)
        
        ->orderBy('baiviet.ngaydang', 'desc')
        ->limit(5)
        ->get()
        ->map(function ($item) {
            if ($item->menu_id_cha != 0) {
                $parent = DB::table('menus')->where('id', $item->menu_id_cha)->first();
                $item->full_menu_slug = $parent ? $parent->slug . '/' . $item->menu_slug : $item->menu_slug;
            } else {
                $item->full_menu_slug = $item->menu_slug;
            }
            $item->image_url = $item->image_url ? asset($item->image_url) : asset('images/system/Rectangle_3897.jpg');
            return $item;
        });

    if ($data->isEmpty()) {
        return response()->json(['message' => 'Không có tin tức.'], 404);
    }
    return response()->json(['data' => $data]);
}

    // Sự kiện nhỏ (6 sự kiện sắp diễn ra)
    function sukiennho(Request $request)
{
    $data = DB::table('baiviet')
        ->leftJoin('menus', 'baiviet.idmenu', '=', 'menus.id')
        ->where('baiviet.danhmuc', 3)        // ✅ đổi idmenu → danhmuc
        ->orderBy('baiviet.ngaydang', 'desc')
        ->limit(6)
        ->select('baiviet.*', 'menus.slug as menu_slug')
        ->get()
        ->map(function ($item) {
            $item->image_url = $item->image_url ? asset($item->image_url) : null;
            $item->menu_slug = $item->menu_slug ?? 'su-kien';
            return $item;
        });

    if ($data->isEmpty()) {
        return response()->json(['message' => 'Không có sự kiện.'], 404);
    }

    return response()->json(['data' => $data]);
}

    // Thông báo nhỏ (12 thông báo mới nhất)
    function thongbaonho(Request $request)
{
    $data = DB::table('baiviet')
        ->leftJoin('menus', 'baiviet.idmenu', '=', 'menus.id')
        ->select('baiviet.*', 'menus.slug as menu_slug')
        ->where('baiviet.idmenu', 1)
        
        ->orderBy('baiviet.ngaydang', 'desc')
        ->limit(20)
        ->get()
        ->map(function ($item) {
            $item->file_url = $item->file_url ? asset($item->file_url) : null;
            return $item;
        });

    if ($data->isEmpty()) {
        return response()->json(['message' => 'Không có thông báo.'], 404);
    }
    return response()->json(['data' => $data]);
}




    // Nganh nho


public function nganhnho(Request $request)
{
    $data = DB::table('nganhs')
        ->leftJoin('khoads', 'nganhs.khoa_id', '=', 'khoads.id')
        ->select('nganhs.id', 'nganhs.ten_nganh', 'nganhs.ma_nganh', 'nganhs.slug', 'nganhs.image_url', 'khoads.ten_khoa')
        ->orderBy('nganhs.id', 'asc')
        ->get()
        ->map(function ($item) {
            $item->image_url = $item->image_url
                ? asset($item->image_url)
                : asset('images/system/Rectangle_3897.jpg'); // ← dùng ảnh default giống các chỗ khác
            return $item;
        });

    return response()->json(['data' => $data]);
}
    // Hien trang
    function index()
    {
        return view(
            'users.pages.index'
        );
    }


        public function visitorStats()
    {
        $today      = now()->toDateString();
        $weekStart  = now()->startOfWeek()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        return response()->json([
            'hom_nay'   => VisitorLog::where('visited_date', $today)->count(),
            'tuan_nay'  => VisitorLog::where('visited_date', '>=', $weekStart)->count(),
            'thang_nay' => VisitorLog::where('visited_date', '>=', $monthStart)->count(),
            'tong_cong' => VisitorLog::count(),
        ]);
    }




    public function nganhChitiet($slug)
{
    $nganh = DB::table('nganhs')
        ->leftJoin('khoads', 'nganhs.khoa_id', '=', 'khoads.id')
        ->select('nganhs.*', 'khoads.ten_khoa')
        ->where('nganhs.slug', $slug)
        ->orWhere('nganhs.id', $slug)
        ->first();

    if (!$nganh) abort(404);

    if ($nganh->image_url) {
        $nganh->image_url = asset($nganh->image_url);
    }

    // Lấy tổ hợp từ bảng trung gian
    $toHopList = DB::table('nganh_tohop')
        ->join('to_hops', 'nganh_tohop.tohop_id', '=', 'to_hops.id')
        ->where('nganh_tohop.nganh_id', $nganh->id)
        ->orderBy('to_hops.ma_to_hop')
        ->get();

    $nganhLienQuan = DB::table('nganhs')
        ->where('khoa_id', $nganh->khoa_id)
        ->where('id', '!=', $nganh->id)
        ->limit(5)
        ->get();

    return view('users.pages.nganh-chitiet', [
        'nganh'         => $nganh,
        'toHopList'     => $toHopList,
        'nganhLienQuan' => $nganhLienQuan,
    ]);
}

public function highlightStats()
{
    $data = DB::table('highlight_stats')->orderBy('thutu', 'asc')->get();
    return response()->json(['data' => $data]);
}
    
}
