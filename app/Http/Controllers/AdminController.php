<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ChatbotAnswerLibraryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

use Exception;

class AdminController extends Controller
{


    public function dashboard()
    {
        return view('admins.pages.dashboard');
    }

    public function baiviet()
    {
        return view('admins.pages.baiviet');
    }


    public function tatcabaiviet(Request $request)
    {
        try {
            $data = DB::table('baiviet')
                ->leftJoin('danhmuc', 'danhmuc.id', '=', 'baiviet.danhmuc')
                ->select('baiviet.*', 'danhmuc.tendanhmuc')
                ->orderBy('baiviet.ngaydang', 'desc')
                ->get();

            foreach ($data as $bv) {
                $bv->tacgia_ten = DB::table('baiviet_tacgia')
                    ->join('tacgia', 'tacgia.id', '=', 'baiviet_tacgia.tacgia_id')
                    ->where('baiviet_tacgia.baiviet_id', $bv->id)
                    ->pluck('tacgia.ten')
                    ->implode(', ');
            }

            if ($data->isEmpty()) {
                return response()->json(['message' => 'Không có bài viết nào.'], 404);
            }

            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function getBaiviet($id)
    {
        try {
            $baiviet = DB::table('baiviet')
                ->leftJoin('menus as current_menu', 'baiviet.idmenu', '=', 'current_menu.id')
                ->select('baiviet.*', 'current_menu.id as current_menu_id', 'current_menu.id_cha as current_menu_parent')
                ->where('baiviet.id', $id)
                ->first();

            if (!$baiviet) {
                return response()->json(['status' => 'error', 'message' => 'Không tìm thấy bài viết', 'data' => null], 404);
            }

            // --- Xử lý tác giả từ bảng trung gian ---
            $tacgiaList = DB::table('baiviet_tacgia')
                ->join('tacgia', 'tacgia.id', '=', 'baiviet_tacgia.tacgia_id')
                ->where('baiviet_tacgia.baiviet_id', $baiviet->id)
                ->get(['tacgia.id', 'tacgia.ten']);

            $baiviet->tacgia_ten = $tacgiaList->pluck('ten')->implode(', ');
            $baiviet->tacgia_ids = $tacgiaList->pluck('id')->implode(',');

            // --- Xử lý menu 1, 2, 3 ---
            $menu1_id = $menu2_id = $menu3_id = null;
            if ($baiviet->current_menu_id) {
                if ($baiviet->current_menu_parent == 0) {
                    $menu1_id = $baiviet->current_menu_id;
                } else {
                    $parent = DB::table('menus')->where('id', $baiviet->current_menu_parent)->first();
                    if ($parent && $parent->id_cha == 0) {
                        $menu1_id = $parent->id;
                        $menu2_id = $baiviet->current_menu_id;
                    } else if ($parent) {
                        $grandparent = DB::table('menus')->where('id', $parent->id_cha)->first();
                        if ($grandparent) {
                            $menu1_id = $grandparent->id;
                            $menu2_id = $parent->id;
                            $menu3_id = $baiviet->current_menu_id;
                        }
                    }
                }
            }

            // --- Link ảnh và file ---
            if ($baiviet->image_url) $baiviet->image_url = asset($baiviet->image_url);
            if ($baiviet->file_url)  $baiviet->file_url  = asset($baiviet->file_url);

            // --- Bài viết liên quan ---
            $baiviet->bv_lienquan_data = [];
            if (!empty($baiviet->bv_lienquan)) {
                $ids = array_filter(explode(',', $baiviet->bv_lienquan));
                $baiviet->bv_lienquan_data = DB::table('baiviet')
                    ->whereIn('id', $ids)
                    ->select('id', 'tieude', 'image_url', 'ngaydang')
                    ->get()
                    ->map(function ($item) {
                        if ($item->image_url) $item->image_url = asset($item->image_url);
                        return $item;
                    });
            }

            // --- Gán menu ---
            $baiviet->menu1_id = $menu1_id;
            $baiviet->menu2_id = $menu2_id;
            $baiviet->menu3_id = $menu3_id;

            // --- Format ngày đăng ---
            if ($baiviet->ngaydang) {
                $baiviet->ngaydang = \Carbon\Carbon::parse($baiviet->ngaydang)->format('d/m/Y');
            }

            return response()->json(['status' => 'success', 'message' => 'Lấy bài viết thành công', 'data' => $baiviet]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }







    public function getMenus()
    {
        // Lấy tất cả menus
        $menus = DB::table('menus')->get();

        // Đệ quy build tree
        $menuTree = $this->buildTree($menus);

        return response()->json([
            'success' => true,
            'data' => $menuTree
        ]);
    }


    public function buildTree($menus, $parentId = 0)
    {
        $branch = [];

        foreach ($menus as $menu) {
            if ($menu->id_cha == $parentId) {
                $children = $this->buildTree($menus, $menu->id);

                $item = [
                    'id' => $menu->id,
                    'name' => $menu->name,
                    'slug' => $menu->slug,
                    'loaimanhinh' => $menu->loaimanhinh,
                    'thutu' => $menu->thutu,
                ];

                if ($children) {
                    $item['children'] = $children;
                }

                $branch[] = $item;
            }
        }

        return $branch;
    }


    public function getDanhmuc()
    {
        // Lấy tất cả danh mục
        $danhmuc = DB::table('danhmuc')->get();

        return response()->json([
            'success' => true,
            'data' => $danhmuc
        ]);
    }

    public function getTacgia()
    {
        // Lấy tất cả tác giả
        $tacgia = DB::table('tacgia')->get();

        return response()->json([
            'success' => true,
            'data' => $tacgia
        ]);
    }

    private function normalizePublicPath(?string $path): ?string
    {
        if (!$path) return null;

        $path = trim($path);
        if ($path === '') return null;

        // Nếu là URL đầy đủ: http://domain.com/uploads/abc.jpg
        if (Str::startsWith($path, ['http://', 'https://'])) {
            $path = parse_url($path, PHP_URL_PATH) ?: '';
        }

        $path = str_replace('\\', '/', $path);
        $path = ltrim($path, '/');

        // Chống xóa nhầm file ngoài thư mục cho phép
        if (strpos($path, '..') !== false) {
            return null;
        }

        // Chỉ cho phép xóa trong các thư mục upload của bạn
        $allowedFolders = [
            'images/baiviet/',
            'pdf/',
            'uploads/',
            'uploads/videos/',
        ];

        foreach ($allowedFolders as $folder) {
            if (Str::startsWith($path, $folder)) {
                return $path;
            }
        }

        return null;
    }

    private function deletePublicFile(?string $path): void
    {
        $path = $this->normalizePublicPath($path);

        if (!$path) return;

        $fullPath = public_path($path);

        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }

    private function extractFilesFromHtml(?string $html): array
    {
        if (!$html) return [];

        $files = [];

        preg_match_all('/<(img|video|source)[^>]+src=["\']([^"\']+)["\']/i', $html, $matches);

        foreach ($matches[2] ?? [] as $src) {
            $path = $this->normalizePublicPath($src);

            if ($path) {
                $files[] = $path;
            }
        }

        return array_values(array_unique($files));
    }

    private function fileUsedByOtherPosts(string $path, ?int $exceptId = null): bool
    {
        $query = DB::table('baiviet')
            ->where(function ($q) use ($path) {
                $q->where('image_url', $path)
                    ->orWhere('file_url', $path)
                    ->orWhere('noidung', 'like', '%' . $path . '%');
            });

        if ($exceptId) {
            $query->where('id', '<>', $exceptId);
        }

        return $query->exists();
    }

    private function deleteUnusedContentFiles(?string $oldHtml, ?string $newHtml, ?int $postId = null): void
    {
        $oldFiles = $this->extractFilesFromHtml($oldHtml);
        $newFiles = $this->extractFilesFromHtml($newHtml);

        $removedFiles = array_diff($oldFiles, $newFiles);

        foreach ($removedFiles as $file) {
            if (!$this->fileUsedByOtherPosts($file, $postId)) {
                $this->deletePublicFile($file);
            }
        }
    }

    public function store(Request $request)
    {

        Log::info('Nội dung bài viết:', ['noidung' => $request->noidung]);
        // Validate cơ bản
        if (!$request->has('postId')) {
            $postId = 0;
        } else {
            $postId = $request->postId;
        }

        $baiviet_old = null;
        if ($postId > 0) {
            $baiviet_old = DB::table('baiviet')->where('id', $postId)->first();
        }

        $imageRule = 'required|image|mimes:jpg,jpeg,png,gif';

        if ($request->filled('postId')) {
            $baiviet_old = DB::table('baiviet')->find($request->postId);
            // Nếu bài cũ có ảnh rồi → không bắt buộc upload lại
            if ($baiviet_old && $baiviet_old->image_url) {
                $imageRule = 'nullable|image|mimes:jpg,jpeg,png,gif';
            }
        }

        $isThongBao = $request->danhmuc == 1;
        $isSuKien   = $request->danhmuc == 3;

        // Lấy nội dung text thuần để check rỗng
        $noidungRaw = strip_tags($request->noidung ?? '');

        $validator = Validator::make($request->all(), [
            'tieude' => 'required|string|max:255|regex:/^[\p{L}\p{N}\s.,:;!?\/()&\-]+$/u',
            'tomtat' => $isThongBao
                ? 'nullable|string|max:1000'
                : 'required|string|max:1000|regex:/^[\p{L}\p{N}\s.,:;!?\/()&\-]+$/u',
            'noidung'  => 'nullable|string',
            'ngaydang' => 'required|string|min:1',
            'image'    => $isThongBao
                ? 'nullable|image|mimes:jpg,jpeg,png,gif'
                : $imageRule,
            'file'     => $isThongBao
                ? ($baiviet_old && $baiviet_old->file_url ? 'nullable|mimes:pdf|max:10240' : 'required|mimes:pdf|max:10240')
                : 'nullable|mimes:pdf|max:10240',
            'tacgia'   => $isThongBao ? 'nullable|array' : 'required|array|min:1',
            'menu1'    => 'required',
            'danhmuc'  => 'required',
            'diadiem'  => $isSuKien
                ? 'required|string|max:1000|regex:/^[\pL\pN\s.,:;!?\/()-]+$/u'
                : 'nullable|string|max:1000',
        ], [
            'tieude.required'   => 'Tiêu đề không được để trống',
            'tieude.max'        => 'Tiêu đề tối đa 255 ký tự',
            'tieude.regex'      => 'Tiêu đề chứa ký tự không hợp lệ',
            'tomtat.required'   => 'Tóm tắt không được để trống',
            'tomtat.max'        => 'Tóm tắt tối đa 1000 ký tự',
            'tomtat.regex'      => 'Tóm tắt chứa ký tự không hợp lệ',
            'ngaydang.required' => 'Ngày đăng không được để trống',
            'ngaydang.min'      => 'Ngày đăng không được để trống',
            'image.required'    => 'Ảnh không được để trống',
            'image.image'       => 'File tải lên phải là ảnh',
            'image.mimes'       => 'Ảnh phải có định dạng: jpg, jpeg, png, gif',
            'file.required'     => 'Thông báo bắt buộc phải có file PDF',
            'file.mimes'        => 'File tải lên phải có định dạng: pdf',
            'file.max'          => 'Kích thước file tối đa là 10MB',
            'tacgia.required'   => 'Tác giả không được để trống',
            'tacgia.min'        => 'Phải chọn ít nhất 1 tác giả',
            'menu1.required'    => 'Bắt buộc chọn menu cấp 1',
            'danhmuc.required'  => 'Bắt buộc chọn danh mục',
            'diadiem.required'  => 'Địa điểm không được để trống',
            'diadiem.max'       => 'Địa điểm tối đa 1000 ký tự',
            'diadiem.regex'     => 'Địa điểm chứa ký tự không hợp lệ',
        ]);

        // Validate noidung rỗng thủ công (vì Summernote gửi <p><br></p> khi rỗng)
        if (!$isThongBao && empty(trim($noidungRaw))) {
            $validator->after(function ($validator) {
                $validator->errors()->add('noidung', 'Nội dung không được để trống');
            });
        }

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 200);
        }

        // Chuyển đổi ngày từ d/m/Y sang Y-m-d
        try {
            $ngaydang = \Carbon\Carbon::createFromFormat('d/m/Y', $request->ngaydang)
                ->format('Y-m-d');
        } catch (\Exception $e) {
            $ngaydang = $baiviet_old->ngaydang ?? now()->format('Y-m-d');
        }

        // Khởi tạo biến để lưu đường dẫn file tạm
        $tempImagePath = null;
        $tempFilePath = null;
        $oldImagePath = $baiviet_old->image_url ?? null;
        $oldFilePath = $baiviet_old->file_url ?? null;
        $oldContent = $baiviet_old->noidung ?? null;

        DB::beginTransaction();

        try {
            Log::info('DEBUG sukien', [
                'thoigian_bd'     => $request->thoigian_bd,
                'thoigian_bd_db'  => $request->thoigian_bd_db,
                'thoigian_kt'     => $request->thoigian_kt,
                'thoigian_kt_db'  => $request->thoigian_kt_db,
                'sukien_status_db' => $request->sukien_status_db,
                'danhmuc'         => $request->danhmuc,
            ]);

            // ===== XỬ LÝ UPLOAD ẢNH =====
            $imagePath = $oldImagePath; // ← THÊM DÒNG NÀY

            if ($request->hasFile('image')) {
                $imageName = time() . '_' . $request->file('image')->getClientOriginalName();
                $destinationPath = public_path('images/baiviet');

                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                $request->file('image')->move($destinationPath, $imageName);
                $tempImagePath = 'images/baiviet/' . $imageName;
                $imagePath = $tempImagePath;
            }

            // ===== XỬ LÝ UPLOAD FILE PDF =====
            $filePath = $oldFilePath; // ← THÊM DÒNG NÀY

            if ($request->hasFile('file')) {
                $fileName = time() . '_' . $request->file('file')->getClientOriginalName();
                $destinationPath = public_path('pdf');

                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                $request->file('file')->move($destinationPath, $fileName);
                $tempFilePath = 'pdf/' . $fileName;
                $filePath = $tempFilePath;
            }



            // ===== XÁC ĐỊNH IDMENU =====
            $idmenu = null;
            if (!empty($request->menu3)) {
                $idmenu = $request->menu3;
            } elseif (!empty($request->menu2)) {
                $idmenu = $request->menu2;
            } else {
                $idmenu = $request->menu1;
            }

            $bvlq = $request->lienquan ? implode(',', explode(',', $request->lienquan)) : null;

            // ===== INSERT/UPDATE DATABASE =====
            // ===== INSERT/UPDATE DATABASE =====
            DB::table('baiviet')->updateOrInsert(
                ["id" => $postId],
                [
                    'idmenu'      => $idmenu,
                    'danhmuc'     => $request->danhmuc,
                    'tieude'      => $request->tieude,
                    'slug'        => Str::slug($request->tieude),
                    'noidung'     => $request->noidung,
                    'tomtat'      => $request->tomtat,
                    // 'tacgia'   => $tacgia_str, ← đã xóa cột này rồi, bỏ đi
                    'ngaydang'    => $ngaydang,
                    'image_url'   => $imagePath,
                    'file_url'    => $filePath,
                    'is_featured' => $request->is_featured ?? 0,
                    'auto_publish'=> $request->auto_publish ?? 1,
                    'new'         => 1,
                    'views'       => 0,
                    'bv_lienquan' => $bvlq,
                    'thoigian_bd' => $request->thoigian_bd
                        ? str_replace('T', ' ', $request->thoigian_bd) . ':00'
                        : null,
                    'thoigian_kt' => $request->thoigian_kt
                        ? str_replace('T', ' ', $request->thoigian_kt) . ':00'
                        : null,
                    'diadiem'     => $request->diadiem ?: null,
                    'status'      => ($request->danhmuc == 3 && in_array($request->sukien_status_db, ['upcoming', 'show', 'ended']))
                        ? $request->sukien_status_db
                        : 'show',
                ]
            );

            // ===== LẤY ID BÀI VIẾT =====
            if ($postId == 0) {
                $baiviet_id = DB::getPdo()->lastInsertId();
            } else {
                $baiviet_id = $postId;
            }


            // ===== ĐỒNG BỘ BẢNG TRUNG GIAN baiviet_tacgia =====
            DB::table('baiviet_tacgia')->where('baiviet_id', $baiviet_id)->delete();

            if (!empty($request->tacgia)) {
                $tacgiaRows = array_map(function ($tacgia_id) use ($baiviet_id) {
                    return [
                        'baiviet_id' => $baiviet_id,
                        'tacgia_id'  => $tacgia_id,
                    ];
                }, $request->tacgia);

                DB::table('baiviet_tacgia')->insert($tacgiaRows);
            }
            // ===== XÓA FILE CŨ SAU KHI LƯU THÀNH CÔNG =====
            // Đổi thành
            DB::commit();

            $newLienQuan = $bvlq ? array_filter(explode(',', $bvlq)) : [];

            // Với mỗi bài được chọn liên quan → thêm bài hiện tại vào lienquan của nó
            foreach ($newLienQuan as $targetId) {
                $targetId = (int) trim($targetId);
                if ($targetId <= 0 || $targetId == $baiviet_id) continue;

                $target = DB::table('baiviet')->where('id', $targetId)->first();
                if (!$target) continue;

                // Lấy danh sách lienquan hiện tại của bài kia
                $targetLienQuan = $target->bv_lienquan
                    ? array_filter(explode(',', $target->bv_lienquan))
                    : [];

                $targetLienQuan = array_map('trim', $targetLienQuan);

                // Thêm bài hiện tại vào nếu chưa có
                if (!in_array((string) $baiviet_id, $targetLienQuan)) {
                    $targetLienQuan[] = (string) $baiviet_id;
                    DB::table('baiviet')
                        ->where('id', $targetId)
                        ->update(['bv_lienquan' => implode(',', $targetLienQuan)]);
                }
            }

            // Với các bài bị bỏ chọn → xóa bài hiện tại khỏi lienquan của nó
            $oldLienQuan = $baiviet_old && $baiviet_old->bv_lienquan
                ? array_filter(array_map('trim', explode(',', $baiviet_old->bv_lienquan)))
                : [];

            $removedIds = array_diff($oldLienQuan, $newLienQuan);

            foreach ($removedIds as $targetId) {
                $targetId = (int) trim($targetId);
                if ($targetId <= 0) continue;

                $target = DB::table('baiviet')->where('id', $targetId)->first();
                if (!$target || !$target->bv_lienquan) continue;

                $targetLienQuan = array_filter(array_map('trim', explode(',', $target->bv_lienquan)));

                // Xóa bài hiện tại khỏi danh sách
                $targetLienQuan = array_values(array_filter($targetLienQuan, function ($id) use ($baiviet_id) {
                    return (int) $id !== $baiviet_id;
                }));

                DB::table('baiviet')
                    ->where('id', $targetId)
                    ->update(['bv_lienquan' => !empty($targetLienQuan) ? implode(',', $targetLienQuan) : null]);
            }

            // Dispatch Webhook to N8N if this is a NEW or UPDATED article
            $newBaiviet = \App\Models\Baiviet::find($baiviet_id);
            if ($newBaiviet) {
                \App\Jobs\SendArticleToN8n::dispatch($newBaiviet);
            }

            // Xóa ảnh đại diện cũ nếu có upload ảnh mới
            if ($tempImagePath && $oldImagePath) {
                if (!$this->fileUsedByOtherPosts($oldImagePath, $baiviet_id)) {
                    $this->deletePublicFile($oldImagePath);
                }
            }

            // Xóa PDF cũ nếu có upload PDF mới
            if ($tempFilePath && $oldFilePath) {
                if (!$this->fileUsedByOtherPosts($oldFilePath, $baiviet_id)) {
                    $this->deletePublicFile($oldFilePath);
                }
            }

            // Xóa ảnh/video trong Summernote nếu người dùng đã xóa khỏi nội dung khi sửa
            $this->deleteUnusedContentFiles($oldContent, $request->noidung, $baiviet_id);

            return response()->json([
                'success' => true,
                'message' => 'Lưu bài viết thành công!',
                'id' => $baiviet_id
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            // ===== XÓA FILE MỚI NẾU CÓ LỖI =====
            if ($tempImagePath && file_exists(public_path($tempImagePath))) {
                unlink(public_path($tempImagePath));
            }

            if ($tempFilePath && file_exists(public_path($tempFilePath))) {
                unlink(public_path($tempFilePath));
            }

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        } finally {
            // Có thể thêm các tác vụ cleanup hoặc logging ở đây nếu cần
        }
    }




    public function destroy($id)
    {
        $baiviet = DB::table('baiviet')->where('id', $id)->first();

        if (!$baiviet) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy bài viết để xóa.'
            ]);
        }

        $imagePath = $baiviet->image_url;
        $filePath  = $baiviet->file_url;
        $contentFiles = $this->extractFilesFromHtml($baiviet->noidung ?? '');

        DB::beginTransaction();

        try {
            // Xóa bài hiện tại khỏi bv_lienquan của các bài khác
            $otherPosts = DB::table('baiviet')
                ->where(function ($q) use ($id) {
                    $q->where('bv_lienquan', (string) $id)
                        ->orWhere('bv_lienquan', 'like', $id . ',%')
                        ->orWhere('bv_lienquan', 'like', '%,' . $id)
                        ->orWhere('bv_lienquan', 'like', '%,' . $id . ',%');
                })
                ->where('id', '<>', $id)
                ->get();

            foreach ($otherPosts as $target) {
                $targetLienQuan = array_filter(array_map('trim', explode(',', $target->bv_lienquan)));

                $targetLienQuan = array_values(array_filter($targetLienQuan, function ($tid) use ($id) {
                    return (int) $tid !== (int) $id;
                }));

                DB::table('baiviet')
                    ->where('id', $target->id)
                    ->update([
                        'bv_lienquan' => !empty($targetLienQuan) ? implode(',', $targetLienQuan) : null
                    ]);
            }

            // Xóa bảng trung gian tác giả
            DB::table('baiviet_tacgia')
                ->where('baiviet_id', $id)
                ->delete();

            // Xóa bài viết
            DB::table('baiviet')
                ->where('id', $id)
                ->delete();

            DB::commit();

            // Sau khi DB xóa thành công mới xóa file vật lý
            if ($imagePath && !$this->fileUsedByOtherPosts($imagePath)) {
                $this->deletePublicFile($imagePath);
            }

            if ($filePath && !$this->fileUsedByOtherPosts($filePath)) {
                $this->deletePublicFile($filePath);
            }

            foreach ($contentFiles as $contentFile) {
                if (!$this->fileUsedByOtherPosts($contentFile)) {
                    $this->deletePublicFile($contentFile);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Xóa bài viết và file liên quan thành công!'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa bài viết: ' . $e->getMessage()
            ], 500);
        }
    }




    public function uploadImage(Request $request)
    {
        if ($request->hasFile('file')) {
            $fileName = time() . '_' . $request->file('file')->getClientOriginalName();
            $destinationPath = public_path('uploads');

            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $request->file('file')->move($destinationPath, $fileName);

            return response()->json([
                'url' => asset('uploads/' . $fileName)
            ]);
        }
    }


    public function baivietTheoDanhmuc(Request $request)
    {
        $danhmucId = $request->danhmuc;

        $query = DB::table('baiviet')->orderBy('ngaydang', 'desc');

        if ($danhmucId) {
            $query->where('danhmuc', $danhmucId);
        }

        $data = $query->get();

        foreach ($data as $bv) {
            $bv->tacgia_ten = DB::table('baiviet_tacgia')
                ->join('tacgia', 'tacgia.id', '=', 'baiviet_tacgia.tacgia_id')
                ->where('baiviet_tacgia.baiviet_id', $bv->id)
                ->pluck('tacgia.ten')
                ->implode(', ');
        }

        return response()->json(['data' => $data]);
    }









































    function tacgia()
    {
        return view('admins.pages.tacgia');
    }

    function tacgia_danhsach()
    {
        $data = DB::table('tacgia')->orderBy('id', 'desc')->get();

        foreach ($data as $index => $item) {
            $item->stt = $index + 1;
        }

        return response()->json(['data' => $data]);
    }

    function tacgia_them(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'tacgia_ten' => 'required|regex:/^[\p{L}0-9 _-]+$/u',
            ],
            [
                'tacgia_ten.required' => 'Tên tác giả không được để trống',
                'tacgia_ten.regex' => 'Tên tác giả không chứa ký tự đặc biệt',
            ]
        );

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        try {
            DB::beginTransaction();
            $inserted = DB::table('tacgia')->insert([
                'ten' => $request->input('tacgia_ten'),
            ]);

            if ($inserted) {
                DB::commit();
                return 1;
            }

            DB::rollback();
            return 0;
        } catch (Exception $e) {
            DB::rollback();
            return -1;
        }
    }

    function tacgia_xoa(Request $request)
    {
        $id = $request->input('id');

        try {
            DB::beginTransaction();
            $deleted = DB::table('tacgia')->where('id', $id)->delete();

            if ($deleted) {
                DB::commit();
                return response()->json(['status' => 'success']);
            }

            DB::rollback();
            return response()->json(['status' => 'error']);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    function tacgia_load(Request $request)
    {
        try {
            $data = DB::table('tacgia')->where('id', $request->input('id'))->first();
            if (!$data) {
                return response()->json(['status' => 'error', 'message' => 'Không tìm thấy tác giả']);
            }
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }


    public function tacgia_capnhat(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'update_tacgia_ten' => 'required|regex:/^[\p{L}0-9 _-]+$/u',
            ],
            [
                'update_tacgia_ten.required' => 'Tên tác giả không được để trống',
                'update_tacgia_ten.regex' => 'Tên tác giả không chứa ký tự đặc biệt',
            ]
        );

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        $id = $request->input('id');

        try {
            DB::beginTransaction();
            $updated = DB::table('tacgia')
                ->where('id', $id)
                ->update([
                    'ten' => $request->input('update_tacgia_ten'),
                ]);

            // ✅ Cho phép commit nếu không lỗi, dù giá trị không thay đổi
            if ($updated !== false) {
                DB::commit();
                return 1;
            }

            DB::rollback();
            return 0;
        } catch (Exception $e) {
            DB::rollback();
            dd($e->getMessage());
        }
    }




    public function banner()
    {
        return view('admins.pages.banner');
    }

    public function tatcabanner()
    {
        $data = DB::table('banners')->orderBy('order', 'asc')->get();
        return response()->json(['data' => $data]);
    }

    public function bannerStore(Request $request)
    {
        $id = $request->bannerId;
        $oldBanner = $id ? DB::table('banners')->find($id) : null;

        $imageRule = $oldBanner ? 'nullable|image|mimes:jpg,jpeg,png,gif,webp'
            : 'required|image|mimes:jpg,jpeg,png,gif,webp';

        $validator = Validator::make($request->all(), [
            'image'  => $imageRule,
            'order'  => 'nullable|integer',
        ], [
            'image.required' => 'Vui lòng chọn ảnh banner',
            'image.image'    => 'File phải là ảnh',
            'image.mimes'    => 'Ảnh phải là jpg, jpeg, png, gif, webp',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()]);
        }

        $imagePath = $oldBanner->image_url ?? null;

        if ($request->hasFile('image')) {
            $imageName = time() . '_' . $request->file('image')->getClientOriginalName();
            $dest = $_SERVER['DOCUMENT_ROOT'] . '/images/banner';
            if (!file_exists($dest)) mkdir($dest, 0755, true);
            $request->file('image')->move($dest, $imageName);
            $newImagePath = '/images/banner/' . $imageName;

            // Xóa ảnh cũ
            if ($oldBanner && $oldBanner->image_url && file_exists($_SERVER['DOCUMENT_ROOT'] . $oldBanner->image_url)) {
                unlink($_SERVER['DOCUMENT_ROOT'] . $oldBanner->image_url);
            }
            $imagePath = $newImagePath;
        }

        DB::table('banners')->updateOrInsert(
            ['id' => $id ?: 0],
            [
                'image_url' => $imagePath,
                'order'     => $request->order ?? 1,
            ]
        );

        return response()->json(['success' => true, 'message' => $id ? 'Cập nhật thành công!' : 'Thêm banner thành công!']);
    }

    public function bannerDestroy($id)
    {
        $banner = DB::table('banners')->find($id);
        if ($banner && $banner->image_url && file_exists($_SERVER['DOCUMENT_ROOT'] . $banner->image_url)) {
            unlink($_SERVER['DOCUMENT_ROOT'] . $banner->image_url);
        }
        DB::table('banners')->delete($id);
        return response()->json(['success' => true, 'message' => 'Xóa banner thành công!']);
    }









































    // =====================================================================
    // Thêm vào AdminController.php
    // =====================================================================
    // Route bổ sung thêm vào group admin trong web.php:
    //
    //   Route::get('/nganhhoc/tohop-list', [AdminController::class, 'nganhhocToHopList']);
    //
    // (Đặt TRƯỚC Route::get('/nganhhoc/{id}', ...) để không bị bắt nhầm)
    // =====================================================================


    // ─── Trang view ───────────────────────────────────────────────────
    public function nganhhoc()
    {
        return view('admins.pages.nganhhoc');
    }

    // ─── Danh sách ngành (có filter) ──────────────────────────────────────
    public function nganhhocList(Request $request)
    {
        $query = DB::table('nganhs')
            ->leftJoin('khoads', 'nganhs.khoa_id', '=', 'khoads.id')
            ->select('nganhs.*', 'khoads.ten_khoa')
            ->orderBy('nganhs.id', 'desc');

        if ($request->filled('khoa_id'))     $query->where('nganhs.khoa_id', $request->khoa_id);
        if ($request->filled('bac_dao_tao')) $query->where('nganhs.bac_dao_tao', $request->bac_dao_tao);

        return response()->json(['data' => $query->get()]);
    }

    // ─── Danh sách khoa (dropdown) ────────────────────────────────────────
    public function nganhhocKhoa()
    {
        return response()->json([
            'data' => DB::table('khoads')->orderBy('ten_khoa')->get(),
        ]);
    }

    // ─── Danh sách TẤT CẢ tổ hợp (để render select2) ────────────────────
    // Route: GET /admin/nganhhoc/tohop-list
    public function nganhhocToHopList()
    {
        $data = DB::table('to_hops')
            ->select('id', 'ma_to_hop', 'ten_to_hop')
            ->orderBy('ma_to_hop')
            ->get();

        return response()->json(['data' => $data]);
    }

    // ─── Chi tiết 1 ngành ─────────────────────────────────────────────────
    public function getNganhhoc($id)
    {
        $nganh = DB::table('nganhs')
            ->leftJoin('khoads', 'nganhs.khoa_id', '=', 'khoads.id')
            ->select('nganhs.*', 'khoads.ten_khoa')
            ->where('nganhs.id', $id)
            ->first();

        if (!$nganh) {
            return response()->json(['status' => 'error', 'message' => 'Không tìm thấy ngành'], 404);
        }

        if ($nganh->image_url) {
            $nganh->image_url = asset($nganh->image_url);
        }
        $nganh->profile_vector = $nganh->profile_vector
            ? json_decode($nganh->profile_vector, true)
            : [];

        // ── Lấy tổ hợp qua bảng nganh_tohop ─────────────────────────────
        $toHops = DB::table('nganh_tohop')
            ->join('to_hops', 'nganh_tohop.tohop_id', '=', 'to_hops.id')
            ->where('nganh_tohop.nganh_id', $id)
            ->select('to_hops.id', 'to_hops.ma_to_hop', 'to_hops.ten_to_hop')
            ->orderBy('to_hops.ma_to_hop')
            ->get();

        // to_hop_ids_arr: mảng ID để JS dùng khi populate select2 lúc edit
        $nganh->to_hop_ids_arr = $toHops->pluck('id')->map(function ($v) {
            return (string) $v;
        })->values()->toArray();
        $nganh->to_hops        = $toHops;


        return response()->json(['status' => 'success', 'data' => $nganh]);
    }

    // ─── Lưu (thêm mới / cập nhật) ───────────────────────────────────────
    public function nganhhocStore(Request $request)
    {
        $nganhId  = (int) $request->input('nganhId', 0);
        $nganhOld = $nganhId > 0 ? DB::table('nganhs')->find($nganhId) : null;

        $validator = Validator::make($request->all(), [
            'ten_nganh'     => 'required|string|max:255',
            'khoa_id'       => 'required|integer|exists:khoads,id',
            'ma_nganh'      => 'nullable|string|max:50',
            'loai_hinh'     => 'nullable|string|max:100',
            'bac_dao_tao'   => 'nullable|string|max:50',
            'thoi_gian'     => 'nullable|string|max:20',
            'so_tin_chi'    => 'nullable|integer|min:0|max:500',
            'chi_tieu'      => 'nullable|integer|min:0|max:9999',
            'hoc_phi'       => 'nullable|integer|min:0',
            'hinh_thuc_xet' => 'nullable|string|max:255',
            'co_so'         => 'nullable|string|max:255',
            'tomtat'        => 'nullable|string|max:2000',
            'noidung'       => 'nullable|string',
            'profile_vector' => 'nullable|array',
            'profile_vector.logic' => 'nullable|integer|min:0|max:5',
            'profile_vector.data' => 'nullable|integer|min:0|max:5',
            'profile_vector.technical' => 'nullable|integer|min:0|max:5',
            'profile_vector.creativity' => 'nullable|integer|min:0|max:5',
            'profile_vector.communication' => 'nullable|integer|min:0|max:5',
            'profile_vector.management' => 'nullable|integer|min:0|max:5',
            'profile_vector.language' => 'nullable|integer|min:0|max:5',
            'profile_vector.legal' => 'nullable|integer|min:0|max:5',
            'profile_vector.experiment' => 'nullable|integer|min:0|max:5',
            'profile_vector.fieldwork' => 'nullable|integer|min:0|max:5',
            'image'         => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
            'to_hop_ids'    => 'nullable|array',
            'to_hop_ids.*'  => 'integer|exists:to_hops,id',
        ], [
            'ten_nganh.required' => 'Tên ngành không được để trống',
            'khoa_id.required'   => 'Vui lòng chọn khoa',
            'khoa_id.exists'     => 'Khoa không hợp lệ',
            'image.image'        => 'File tải lên phải là ảnh',
            'image.mimes'        => 'Ảnh phải có định dạng: jpg, jpeg, png, gif, webp',
            'image.max'          => 'Kích thước ảnh tối đa 5MB',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()]);
        }

        // ── Xử lý ảnh ────────────────────────────────────────────────────
        $imagePath   = $nganhOld->image_url ?? null;
        $tempImgPath = null;

        if ($request->hasFile('image')) {
            $imgName = time() . '_' . $request->file('image')->getClientOriginalName();
            $dest    = $_SERVER['DOCUMENT_ROOT'] . '/images/nganh';
            if (!file_exists($dest)) mkdir($dest, 0755, true);
            $request->file('image')->move($dest, $imgName);
            $tempImgPath = 'images/nganh/' . $imgName;
            $imagePath   = $tempImgPath;
        }

        // ── Slug ─────────────────────────────────────────────────────────
        $slug = Str::slug($request->ten_nganh);
        $slugExist = DB::table('nganhs')
            ->where('slug', $slug)
            ->when($nganhId > 0, function ($q) use ($nganhId) {
                return $q->where('id', '!=', $nganhId);
            })
            ->exists();
        if ($slugExist) $slug = $slug . '-' . time();

        // ── Danh sách tổ hợp được chọn ───────────────────────────────────
        $selectedIds = array_values(array_unique(array_filter(
            array_map('intval', $request->input('to_hop_ids', []))
        )));

        $vectorKeys = [
            'logic',
            'data',
            'technical',
            'creativity',
            'communication',
            'management',
            'language',
            'legal',
            'experiment',
            'fieldwork',
        ];

        $profileInput = $request->input('profile_vector', []);
        $profileVector = [];

        foreach ($vectorKeys as $key) {
            $value = isset($profileInput[$key]) ? (int) $profileInput[$key] : 0;
            $profileVector[$key] = max(0, min(5, $value));
        }
        DB::beginTransaction();
        try {
            // ── Upsert bản ghi ngành ─────────────────────────────────────
            DB::table('nganhs')->updateOrInsert(

                ['id' => $nganhId ?: 0],
                [
                    'khoa_id'       => $request->khoa_id       ?: null,
                    'ten_nganh'     => $request->ten_nganh,
                    'ma_nganh'      => $request->ma_nganh       ?: null,
                    'slug'          => $slug,
                    'loai_hinh'     => $request->loai_hinh      ?: null,
                    'bac_dao_tao'   => $request->bac_dao_tao    ?: null,
                    'thoi_gian'     => $request->thoi_gian      ?: null,
                    'so_tin_chi'    => $request->so_tin_chi     ?: null,
                    'chi_tieu'      => $request->chi_tieu       ?: null,
                    'hoc_phi'       => $request->hoc_phi        ?: null,
                    'hinh_thuc_xet' => $request->hinh_thuc_xet ?: null,
                    'co_so'         => $request->co_so          ?: null,
                    'tomtat'        => $request->tomtat         ?: null,
                    'noidung'       => $request->noidung        ?: null,
                    'profile_vector' => json_encode($profileVector, JSON_UNESCAPED_UNICODE),
                    'image_url'     => $imagePath,
                    'updated_at'    => now(),
                ]
            );

            $savedId = ($nganhId > 0) ? $nganhId : (int) DB::getPdo()->lastInsertId();

            // ── Đồng bộ bảng nganh_tohop (xóa cũ → insert mới) ──────────
            DB::table('nganh_tohop')->where('nganh_id', $savedId)->delete();

            if (!empty($selectedIds)) {
                $rows = array_map(function ($tohopId) use ($savedId) {
                    return array(
                        'nganh_id'   => $savedId,
                        'tohop_id'   => $tohopId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    );
                }, $selectedIds);

                DB::table('nganh_tohop')->insert($rows);
            }

            // ── Xóa ảnh cũ nếu đã upload ảnh mới ────────────────────────
            if ($tempImgPath && $nganhOld && $nganhOld->image_url) {
                $oldFile = $_SERVER['DOCUMENT_ROOT'] . '/' . $nganhOld->image_url;
                if (file_exists($oldFile)) unlink($oldFile);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'status'  => true,
                'message' => $nganhId > 0 ? 'Cập nhật ngành học thành công!' : 'Thêm ngành học thành công!',
                'id'      => $savedId,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($tempImgPath && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $tempImgPath)) {
                unlink($_SERVER['DOCUMENT_ROOT'] . '/' . $tempImgPath);
            }
            return response()->json(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }

    // ─── Xóa ngành ────────────────────────────────────────────────────────
    public function nganhhocDestroy($id)
    {
        try {
            $nganh = DB::table('nganhs')->find($id);
            if (!$nganh) {
                return response()->json(['success' => false, 'message' => 'Không tìm thấy ngành']);
            }

            // ON DELETE CASCADE trên FK nganh_tohop.nganh_id sẽ tự xóa các dòng liên quan
            DB::table('nganhs')->where('id', $id)->delete();

            if ($nganh->image_url) {
                $f = $_SERVER['DOCUMENT_ROOT'] . '/' . $nganh->image_url;
                if (file_exists($f)) unlink($f);
            }

            return response()->json(['success' => true, 'message' => 'Xóa ngành học thành công!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }





















    public function advise()
    {
        return view('admins.pages.advise');
    }

    public function adviseSessions(Request $request)
    {
        /*
         * Kho câu hỏi dùng chung route sessions đã tồn tại từ trước.
         * Cách này vẫn hoạt động ngay cả khi máy chủ còn route cache cũ.
         */
        if ((int) $request->input('answer_library', 0) === 1) {
            return $this->adviseAnswerLibrary($request);
        }

        $query = DB::table('chatbot_sessions as s')
            ->select(
                's.id',
                's.session_key',
                's.thread_id',
                's.ip_address',
                's.user_agent',
                's.started_at',
                's.last_active_at',
                's.ended_at',
                DB::raw('NULL as user_name'),
                DB::raw(
                    '(SELECT COUNT(*) '
                    . 'FROM chatbot_messages m '
                    . 'WHERE m.session_id = s.id) as message_count'
                ),
                DB::raw(
                    '(SELECT COUNT(*) '
                    . 'FROM chatbot_messages m '
                    . 'WHERE m.session_id = s.id '
                    . 'AND m.role = "user") as user_message_count'
                ),
                DB::raw(
                    '(SELECT COUNT(*) '
                    . 'FROM chatbot_messages m '
                    . 'WHERE m.session_id = s.id '
                    . 'AND m.role = "assistant") as bot_message_count'
                ),
                DB::raw(
                    '(SELECT LEFT(m.content, 240) '
                    . 'FROM chatbot_messages m '
                    . 'WHERE m.session_id = s.id '
                    . 'ORDER BY m.sent_at DESC, m.id DESC '
                    . 'LIMIT 1) as last_message'
                ),
                DB::raw(
                    '(SELECT m.role '
                    . 'FROM chatbot_messages m '
                    . 'WHERE m.session_id = s.id '
                    . 'ORDER BY m.sent_at DESC, m.id DESC '
                    . 'LIMIT 1) as last_message_role'
                ),
                DB::raw(
                    '(SELECT m.sent_at '
                    . 'FROM chatbot_messages m '
                    . 'WHERE m.session_id = s.id '
                    . 'ORDER BY m.sent_at DESC, m.id DESC '
                    . 'LIMIT 1) as last_message_at'
                ),
                DB::raw(
                    '(SELECT COUNT(*) '
                    . 'FROM chatbot_tickets t '
                    . 'WHERE t.session_id = s.id) as ticket_count'
                ),
                DB::raw(
                    '(SELECT COUNT(*) '
                    . 'FROM chatbot_tickets t '
                    . 'WHERE t.session_id = s.id '
                    . 'AND t.status = "pending") as pending_ticket_count'
                )
            )
            ->orderBy('s.last_active_at', 'desc')
            ->orderBy('s.id', 'desc');

        if ($request->filled('date_from')) {
            $query->whereDate(
                's.started_at',
                '>=',
                $request->date_from
            );
        }

        if ($request->filled('date_to')) {
            $query->whereDate(
                's.started_at',
                '<=',
                $request->date_to
            );
        }

        if ($request->filled('ip')) {
            $query->where(
                's.ip_address',
                'like',
                '%' . $request->ip . '%'
            );
        }

        $perPage = (int) $request->input('per_page', 8);
        $perPage = max(5, min($perPage, 20));

        $sessions = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'data' => collect($sessions->items())->values(),
                'current_page' => $sessions->currentPage(),
                'last_page' => $sessions->lastPage(),
                'per_page' => $sessions->perPage(),
                'total' => $sessions->total(),
            ],
        ]);
    }

    public function adviseSessionDetail($id)
    {
        /*
         * Dùng route /admin/advise/sessions/{id} hiện có để xem
         * chi tiết một bản ghi trong kho câu hỏi.
         */
        $idText = (string) $id;

        if (strpos($idText, 'library-') === 0) {
            $libraryId = (int) substr($idText, strlen('library-'));

            return $this->adviseAnswerLibraryShow($libraryId);
        }

        $session = DB::table('chatbot_sessions')
            ->where('id', $id)
            ->first();

        if (!$session) {
            return response()->json([
                'status' => 'error',
                'message' => 'Không tìm thấy phiên chat.',
            ], 404);
        }

        $session->user_name = null;
        $session->message_count = DB::table('chatbot_messages')
            ->where('session_id', $id)
            ->count();
        $session->user_message_count = DB::table('chatbot_messages')
            ->where('session_id', $id)
            ->where('role', 'user')
            ->count();
        $session->bot_message_count = DB::table('chatbot_messages')
            ->where('session_id', $id)
            ->where('role', 'assistant')
            ->count();
        $session->ticket_count = DB::table('chatbot_tickets')
            ->where('session_id', $id)
            ->count();
        $session->pending_ticket_count = DB::table('chatbot_tickets')
            ->where('session_id', $id)
            ->where('status', 'pending')
            ->count();

        $messages = DB::table('chatbot_messages as m')
            ->leftJoin(
                'chatbot_messages as parent_user',
                'parent_user.id',
                '=',
                'm.reply_to_id'
            )
            ->leftJoin(
                'chatbot_answer_library as al',
                function ($join) {
                    $join->on('al.source_id', '=', 'm.id')
                        ->where('al.source_type', '=', 'ai')
                        ->where('al.is_approved', '=', 1)
                        ->where('al.is_active', '=', 1);
                }
            )
            ->where('m.session_id', $id)
            ->select(
                'm.*',
                'parent_user.content as reply_question',
                'al.id as answer_library_id',
                'al.use_count as answer_use_count'
            )
            ->orderBy('m.sent_at', 'asc')
            ->orderBy('m.id', 'asc')
            ->get();

        $answerLibraryService = app(
            ChatbotAnswerLibraryService::class
        );

        $messages = $messages->map(function ($message) use (
            $answerLibraryService
        ) {
            $message->can_answer_library =
                $message->role === 'assistant' ? 1 : 0;

            $message->answer_library_review =
                $message->role === 'assistant'
                    ? $answerLibraryService->reviewForLibrary(
                        $message->reply_question,
                        $message->content
                    )
                    : null;

            return $message;
        })->values();

        return response()->json([
            'status'   => 'success',
            'session'  => $session,
            'messages' => $messages,
        ]);
    }

    public function adviseDestroySession($id)
    {
        $idText = (string) $id;

        if (strpos($idText, 'library-') === 0) {
            $libraryId = (int) substr($idText, strlen('library-'));

            return $this->adviseAnswerLibraryDestroy($libraryId);
        }

        try {
            DB::beginTransaction();

            DB::table('chatbot_answer_library')
                ->where('session_id', $id)
                ->where('is_approved', 0)
                ->delete();

            DB::table('chatbot_answer_library')
                ->where('session_id', $id)
                ->where('is_approved', 1)
                ->update([
                    'session_id' => null,
                    'user_message_id' => null,
                    'assistant_message_id' => null,
                    'updated_at' => now(),
                ]);

            DB::table('chatbot_messages')
                ->where('session_id', $id)
                ->delete();

            $deleted = DB::table('chatbot_sessions')
                ->where('id', $id)
                ->delete();

            if ($deleted) {
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Xóa session thành công!',
                ]);
            }

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy session.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function adviseDestroyMessage($id)
    {
        try {
            DB::beginTransaction();

            $library = DB::table('chatbot_answer_library')
                ->where('source_type', 'ai')
                ->where('source_id', $id)
                ->first();

            if ($library) {
                if ((int) $library->is_approved === 1) {
                    DB::table('chatbot_answer_library')
                        ->where('id', $library->id)
                        ->update([
                            'session_id' => null,
                            'user_message_id' => null,
                            'assistant_message_id' => null,
                            'updated_at' => now(),
                        ]);
                } else {
                    DB::table('chatbot_answer_library')
                        ->where('id', $library->id)
                        ->delete();
                }
            }

            DB::table('chatbot_answer_library')
                ->where('user_message_id', $id)
                ->update([
                    'user_message_id' => null,
                    'updated_at' => now(),
                ]);

            $deleted = DB::table('chatbot_messages')
                ->where('id', $id)
                ->delete();

            DB::commit();

            return $deleted
                ? response()->json([
                    'success' => true,
                    'message' => 'Xóa tin nhắn thành công!',
                ])
                : response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy tin nhắn.',
                ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function adviseStats()
    {
        return response()->json([
            'total_sessions' => DB::table('chatbot_sessions')->count(),
            'total_messages' => DB::table('chatbot_messages')->count(),
            'user_messages' => DB::table('chatbot_messages')
                ->where('role', 'user')
                ->count(),
            'bot_messages' => DB::table('chatbot_messages')
                ->where('role', 'assistant')
                ->count(),
            'voice_messages' => DB::table('chatbot_messages')
                ->where('input_type', 'voice')
                ->count(),

            'pending_tickets' => DB::table('chatbot_tickets')
                ->where('status', 'pending')
                ->count(),
            'answered_tickets' => DB::table('chatbot_tickets')
                ->where('status', 'answered')
                ->count(),
            'closed_tickets' => DB::table('chatbot_tickets')
                ->where('status', 'closed')
                ->count(),

            'approved_answers' => DB::table(
                'chatbot_answer_library'
            )
                ->where('is_approved', 1)
                ->where('is_active', 1)
                ->count(),
        ]);
    }

    public function adviseTickets(Request $request)
    {
        $query = DB::table('chatbot_tickets as t')
            ->leftJoin('nguoidung as nd', 'nd.id', '=', 't.answered_by')
            ->leftJoin('chatbot_sessions as s', 's.id', '=', 't.session_id')
            ->leftJoin(
                'chatbot_answer_library as al',
                function ($join) {
                    $join->on('al.source_id', '=', 't.id')
                        ->where('al.source_type', '=', 'staff')
                        ->where('al.is_approved', '=', 1)
                        ->where('al.is_active', '=', 1);
                }
            )
            ->select(
                't.id',
                't.session_id',
                't.thread_id',
                't.ticket_code',
                't.question',
                't.bot_note',
                't.status',
                't.staff_answer',
                't.answered_by',
                't.answered_at',
                't.delivered_at',
                't.created_at',
                't.updated_at',
                'nd.ten_nguoi_dung as admin_name',
                's.session_key',
                's.ip_address',
                'al.id as answer_library_id',
                'al.is_approved as answer_is_approved',
                'al.is_active as answer_is_active',
                'al.use_count as answer_use_count'
            );

        /*
         * Lọc đúng trạng thái ticket.
         *
         * Trước đây tham số status từ giao diện bị bỏ qua, nên request
         * status=pending vẫn trả về toàn bộ ticket. JavaScript lấy tổng
         * số đó làm số ticket chờ và hiển thị cảnh báo sai.
         */
        $status = trim((string) $request->input('status', ''));

        if (in_array(
            $status,
            ['pending', 'answered', 'closed'],
            true
        )) {
            $query->where('t.status', $status);
        }

        if ($request->filled('keyword')) {
            $keyword = trim((string) $request->keyword);

            $query->where(function ($q) use ($keyword) {
                $q->where('t.ticket_code', 'like', '%' . $keyword . '%')
                    ->orWhere('t.question', 'like', '%' . $keyword . '%')
                    ->orWhere('t.staff_answer', 'like', '%' . $keyword . '%')
                    ->orWhere('t.thread_id', 'like', '%' . $keyword . '%');
            });
        }

        $perPage = (int) $request->input('per_page', 8);
        $perPage = max(5, min($perPage, 20));

        $tickets = $query
            ->orderByRaw("FIELD(t.status, 'pending', 'answered', 'closed')")
            ->orderByDesc('t.created_at')
            ->paginate($perPage);

        $items = collect($tickets->items())->map(function ($item) {
            $item->created_at_text = $item->created_at
                ? date('d/m/Y H:i', strtotime($item->created_at))
                : '';

            $item->answered_at_text = $item->answered_at
                ? date('d/m/Y H:i', strtotime($item->answered_at))
                : '';

            $item->delivered_at_text = $item->delivered_at
                ? date('d/m/Y H:i', strtotime($item->delivered_at))
                : '';

            return $item;
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $items,
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
            ],
        ]);
    }

    public function adviseTicketShow($id)
    {
        $ticket = DB::table('chatbot_tickets as t')
            ->leftJoin('nguoidung as nd', 'nd.id', '=', 't.answered_by')
            ->leftJoin('chatbot_sessions as s', 's.id', '=', 't.session_id')
            ->leftJoin(
                'chatbot_answer_library as al',
                function ($join) {
                    $join->on('al.source_id', '=', 't.id')
                        ->where('al.source_type', '=', 'staff')
                        ->where('al.is_approved', '=', 1)
                        ->where('al.is_active', '=', 1);
                }
            )
            ->select(
                't.id',
                't.session_id',
                't.user_message_id',
                't.bot_message_id',
                't.thread_id',
                't.ticket_code',
                't.question',
                't.bot_note',
                't.status',
                't.staff_answer',
                't.answered_by',
                't.answered_at',
                't.delivered_at',
                't.created_at',
                't.updated_at',
                'nd.ten_nguoi_dung as admin_name',
                's.session_key',
                's.ip_address',
                's.user_agent',
                's.started_at',
                's.last_active_at',
                'al.id as answer_library_id',
                'al.is_approved as answer_is_approved',
                'al.is_active as answer_is_active',
                'al.use_count as answer_use_count'
            )
            ->where('t.id', $id)
            ->first();

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy ticket.',
            ], 404);
        }

        $ticket->created_at_text = $ticket->created_at
            ? date('d/m/Y H:i', strtotime($ticket->created_at))
            : '';

        $ticket->answered_at_text = $ticket->answered_at
            ? date('d/m/Y H:i', strtotime($ticket->answered_at))
            : '';

        $ticket->delivered_at_text = $ticket->delivered_at
            ? date('d/m/Y H:i', strtotime($ticket->delivered_at))
            : '';

        return response()->json([
            'success' => true,
            'data' => $ticket,
        ]);
    }

    public function adviseTicketAnswer(Request $request, $id)
    {
        /*
         * Tái sử dụng route trả lời ticket đã có để cập nhật kho.
         * Không phụ thuộc route answer-library mới.
         */
        $idText = (string) $id;

        if (strpos($idText, 'message-') === 0) {
            $messageId = (int) substr(
                $idText,
                strlen('message-')
            );

            $request->validate([
                'approved' => 'required|integer|in:0,1',
            ]);

            $adminId = $this->currentAdminId();

            if (!$adminId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không xác định được admin đang đăng nhập.',
                ], 403);
            }

            $message = DB::table('chatbot_messages as assistant_message')
                ->join(
                    'chatbot_messages as user_message',
                    'user_message.id',
                    '=',
                    'assistant_message.reply_to_id'
                )
                ->where('assistant_message.id', $messageId)
                ->where('assistant_message.role', 'assistant')
                ->where('user_message.role', 'user')
                ->select(
                    'assistant_message.id',
                    'assistant_message.session_id',
                    'assistant_message.content as answer',
                    'user_message.id as user_message_id',
                    'user_message.content as question'
                )
                ->first();

            if (!$message) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy cặp hỏi đáp AI.',
                ], 404);
            }

            try {
                $service = app(
                    ChatbotAnswerLibraryService::class
                );

                if ((int) $request->approved === 1) {
                    $result = $service->storeAiAnswer(
                        $message->session_id,
                        $message->user_message_id,
                        $message->id,
                        $message->question,
                        $message->answer,
                        $adminId
                    );

                    $review = isset($result['review'])
                        ? $result['review']
                        : null;

                    $messageText = $review
                        && $review['reuse_ready']
                            ? 'Đã lưu vào kho và sẵn sàng tái sử dụng.'
                            : 'Đã lưu vào kho. Câu này chỉ chưa được dùng tự động nếu là nội dung rác hoặc không đúng chủ đề.';
                } else {
                    $result = $service->removeAiAnswer(
                        $message->id
                    );

                    $messageText = 'Đã xóa câu trả lời khỏi kho.';
                }

                return response()->json([
                    'success' => true,
                    'message' => $messageText,
                    'data' => $result,
                ]);
            } catch (\InvalidArgumentException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }
        }

        if (strpos($idText, 'library-') === 0) {
            $libraryId = (int) substr($idText, strlen('library-'));
            $action = trim((string) $request->input(
                'library_action'
            ));

            if ($action === 'approval') {
                return $this->adviseAnswerLibraryApproval(
                    $request,
                    $libraryId
                );
            }

            if ($action === 'update') {
                return $this->adviseAnswerLibraryUpdate(
                    $request,
                    $libraryId
                );
            }

            return response()->json([
                'success' => false,
                'message' => 'Thao tác kho câu hỏi không hợp lệ.',
            ], 422);
        }

        $request->validate([
            'staff_answer' => 'required|string|max:10000',
            'use_as_sample' => 'nullable|integer|in:0,1',
        ], [
            'staff_answer.required' => 'Vui lòng nhập nội dung trả lời.',
        ]);

        $adminId = $this->currentAdminId();

        if (!$adminId) {
            return response()->json([
                'success' => false,
                'message' => 'Không xác định được admin đang đăng nhập.',
            ], 403);
        }

        $ticket = DB::table('chatbot_tickets')
            ->where('id', $id)
            ->first();

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy ticket.',
            ], 404);
        }

        $answer = trim((string) $request->staff_answer);
        $useAsSample = (int) $request->input('use_as_sample', 0);

        DB::table('chatbot_tickets')
            ->where('id', $id)
            ->update([
                'status'       => 'answered',
                'staff_answer' => $answer,
                'answered_by'  => $adminId,
                'answered_at'  => now(),
                'delivered_at' => null,
                'updated_at'   => now(),
            ]);

        $libraryResult = app(
            ChatbotAnswerLibraryService::class
        )->storeStaffAnswer(
            $ticket->id,
            $ticket->session_id,
            $ticket->user_message_id,
            $ticket->question,
            $answer,
            $useAsSample === 1,
            $adminId
        );

        $message = 'Đã trả lời yêu cầu tư vấn. '
            . 'Nếu người hỏi còn mở khung chat, phản hồi sẽ tự hiển thị.';

        if ($libraryResult['approval_blocked']) {
            $message .= ' Câu hỏi có dấu hiệu chứa dữ liệu cá nhân nên '
                . 'không được đưa vào kho câu trả lời mẫu.';
        } elseif ($libraryResult['is_in_library']) {
            $message .= ' Câu trả lời đã được thêm vào kho.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'answer_library' => $libraryResult,
        ]);
    }

    public function adviseTicketClose($id)
    {
        $ticket = DB::table('chatbot_tickets')
            ->where('id', $id)
            ->first();

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy ticket.',
            ], 404);
        }

        DB::table('chatbot_tickets')
            ->where('id', $id)
            ->update([
                'status' => 'closed',
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã đóng yêu cầu tư vấn.',
        ]);
    }

    public function adviseAnswerLibrary(Request $request)
    {
        $query = DB::table('chatbot_answer_library as al')
            ->leftJoin(
                'nguoidung as approved_user',
                'approved_user.id',
                '=',
                'al.approved_by'
            )
            ->leftJoin(
                'chatbot_tickets as t',
                't.id',
                '=',
                'al.ticket_id'
            )
            ->leftJoin(
                'chatbot_sessions as s',
                's.id',
                '=',
                'al.session_id'
            )
            ->select(
                'al.id',
                'al.source_type',
                'al.source_id',
                'al.session_id',
                'al.user_message_id',
                'al.assistant_message_id',
                'al.ticket_id',
                'al.question',
                'al.answer',
                'al.is_approved',
                'al.is_active',
                'al.approved_by',
                'al.approved_at',
                'al.use_count',
                'al.last_used_at',
                'al.created_at',
                'al.updated_at',
                'approved_user.ten_nguoi_dung as approved_by_name',
                't.ticket_code',
                's.ip_address'
            )
            ->where('al.is_approved', 1)
            ->where('al.is_active', 1);

        if ($request->filled('source_type')) {
            $query->where('al.source_type', $request->source_type);
        }

        if ($request->filled('keyword')) {
            $keyword = trim((string) $request->keyword);

            $query->where(function ($q) use ($keyword) {
                $q->where('al.question', 'like', '%' . $keyword . '%')
                    ->orWhere('al.answer', 'like', '%' . $keyword . '%')
                    ->orWhere('t.ticket_code', 'like', '%' . $keyword . '%');
            });
        }

        $perPage = (int) $request->input('per_page', 8);
        $perPage = max(5, min($perPage, 20));

        $records = $query
            ->orderByDesc('al.updated_at')
            ->orderByDesc('al.id')
            ->paginate($perPage);

        $items = collect($records->items())->map(function ($item) {
            $item->source_label = $item->source_type === 'staff'
                ? 'Nhân viên'
                : 'AI';

            $item->created_at_text = $item->created_at
                ? date('d/m/Y H:i', strtotime($item->created_at))
                : '';

            $item->updated_at_text = $item->updated_at
                ? date('d/m/Y H:i', strtotime($item->updated_at))
                : '';

            $item->approved_at_text = $item->approved_at
                ? date('d/m/Y H:i', strtotime($item->approved_at))
                : '';

            $item->last_used_at_text = $item->last_used_at
                ? date('d/m/Y H:i', strtotime($item->last_used_at))
                : '';

            return $item;
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $items,
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'per_page' => $records->perPage(),
                'total' => $records->total(),
            ],
        ]);
    }

    public function adviseAnswerLibraryShow($id)
    {
        $record = DB::table('chatbot_answer_library as al')
            ->leftJoin(
                'nguoidung as approved_user',
                'approved_user.id',
                '=',
                'al.approved_by'
            )
            ->leftJoin(
                'chatbot_tickets as t',
                't.id',
                '=',
                'al.ticket_id'
            )
            ->leftJoin(
                'chatbot_sessions as s',
                's.id',
                '=',
                'al.session_id'
            )
            ->select(
                'al.*',
                'approved_user.ten_nguoi_dung as approved_by_name',
                't.ticket_code',
                's.ip_address'
            )
            ->where('al.id', $id)
            ->where('al.is_approved', 1)
            ->where('al.is_active', 1)
            ->first();

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy câu trả lời trong kho.',
            ], 404);
        }

        $record->source_label = $record->source_type === 'staff'
            ? 'Nhân viên'
            : 'AI';

        $record->created_at_text = $record->created_at
            ? date('d/m/Y H:i', strtotime($record->created_at))
            : '';

        $record->updated_at_text = $record->updated_at
            ? date('d/m/Y H:i', strtotime($record->updated_at))
            : '';

        $record->approved_at_text = $record->approved_at
            ? date('d/m/Y H:i', strtotime($record->approved_at))
            : '';

        $record->last_used_at_text = $record->last_used_at
            ? date('d/m/Y H:i', strtotime($record->last_used_at))
            : '';

        $record->library_review = app(
            ChatbotAnswerLibraryService::class
        )->reviewForLibrary(
            $record->question,
            $record->answer
        );

        return response()->json([
            'success' => true,
            'data' => $record,
        ]);
    }

    public function adviseAnswerLibraryUpdate(Request $request, $id)
    {
        $request->validate([
            'question' => 'required|string|max:12000',
            'answer' => 'required|string|max:30000',
        ]);

        $adminId = $this->currentAdminId();

        if (!$adminId) {
            return response()->json([
                'success' => false,
                'message' => 'Không xác định được admin đang đăng nhập.',
            ], 403);
        }

        try {
            $result = app(
                ChatbotAnswerLibraryService::class
            )->updateEntry(
                $id,
                $request->question,
                $request->answer,
                $adminId
            );

            $review = isset($result['review'])
                ? $result['review']
                : null;

            return response()->json([
                'success' => true,
                'message' => $review && $review['reuse_ready']
                    ? 'Đã lưu thay đổi và sẵn sàng tái sử dụng.'
                    : 'Đã lưu thay đổi. Hệ thống chỉ tạm không dùng tự động nếu nội dung không đúng chủ đề hoặc là dữ liệu rác.',
                'data' => $result,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function adviseAnswerLibraryApproval(Request $request, $id)
    {
        $request->validate([
            'approved' => 'required|integer|in:0,1',
        ]);

        $adminId = $this->currentAdminId();

        if (!$adminId) {
            return response()->json([
                'success' => false,
                'message' => 'Không xác định được admin đang đăng nhập.',
            ], 403);
        }

        try {
            $result = app(
                ChatbotAnswerLibraryService::class
            )->setApproval(
                $id,
                (int) $request->approved === 1,
                $adminId
            );

            return response()->json([
                'success' => true,
                'message' => $result['is_in_library']
                    ? 'Đã thêm câu trả lời vào kho.'
                    : 'Đã xóa câu trả lời khỏi kho.',
                'data' => $result,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function adviseAnswerLibraryDestroy($id)
    {
        $deleted = DB::table('chatbot_answer_library')
            ->where('id', $id)
            ->delete();

        return $deleted
            ? response()->json([
                'success' => true,
                'message' => 'Đã xóa câu trả lời khỏi kho.',
            ])
            : response()->json([
                'success' => false,
                'message' => 'Không tìm thấy câu trả lời trong kho.',
            ], 404);
    }

    private function currentAdminId()
    {
        if (auth()->check()) {
            return auth()->id();
        }

        $sessionIdKeys = [
            'nguoidung_id',
            'id_nguoidung',
            'admin_id',
            'id_admin',
            'user_id',
            'id_user',
            'id',
        ];

        foreach ($sessionIdKeys as $key) {
            $value = session()->get($key);

            if (!empty($value)) {
                return $value;
            }
        }

        $sessionEmailKeys = [
            'email',
            'admin_email',
            'nguoidung_email',
            'email_admin',
        ];

        foreach ($sessionEmailKeys as $key) {
            $email = session()->get($key);

            if (!empty($email)) {
                $adminId = DB::table('nguoidung')
                    ->where('email', $email)
                    ->where('role', 'admin')
                    ->value('id');

                if ($adminId) {
                    return $adminId;
                }
            }
        }

        $sessionUserKeys = [
            'admin',
            'nguoidung',
            'user',
            'taikhoan',
        ];

        foreach ($sessionUserKeys as $key) {
            $value = session()->get($key);

            if (is_array($value) && !empty($value['id'])) {
                return $value['id'];
            }

            if (is_object($value) && !empty($value->id)) {
                return $value->id;
            }
        }

        return DB::table('nguoidung')
            ->where('role', 'admin')
            ->orderBy('id')
            ->value('id');
    }

    public function adviseDestroyAll()
    {
        try {
            DB::beginTransaction();

            DB::table('chatbot_answer_library')
                ->where('is_approved', 0)
                ->delete();

            DB::table('chatbot_answer_library')
                ->where('is_approved', 1)
                ->update([
                    'session_id' => null,
                    'user_message_id' => null,
                    'assistant_message_id' => null,
                    'updated_at' => now(),
                ]);

            DB::table('chatbot_messages')->delete();
            DB::table('chatbot_sessions')->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đã xóa dữ liệu hội thoại; '
                    . 'các câu mẫu đã duyệt vẫn được giữ lại.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage(),
            ], 500);
        }
    }


    public function dashboardStats()
    {
        $today = now()->toDateString();

        $daily = DB::table('visitor_logs')
            ->select('visited_date', DB::raw('COUNT(*) as count'))
            ->where('visited_date', '>=', now()->subDays(6)->toDateString())
            ->groupBy('visited_date')
            ->orderBy('visited_date', 'asc')
            ->get();

        return response()->json([
            'today'  => DB::table('visitor_logs')->where('visited_date', $today)->count(),
            'total'  => DB::table('visitor_logs')->count(),
            'daily'  => $daily,
        ]);
    }












    // mới

    public function suggest(Request $request)
    {
        $request->validate([
            'content' => 'required|string'
        ]);

        $content = trim($request->input('content'));
        $content = Str::limit($content, 6000, '');

        $apiKey = env('GEMINI_API_KEY');

        if (empty($apiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Gemini API key not configured.'
            ], 500);
        }

        $prompt = "
Bạn là trợ lý viết tiêu đề và tóm tắt bài viết.

Chỉ trả về JSON hợp lệ:

{
\"title\": \"...\",
\"summary\": \"...\"
}

Quy tắc:
- title tối đa 20 từ
- summary 1-2 câu
- không thêm chữ nào ngoài JSON

Nội dung:
{$content}
";

        try {

            $response = Http::timeout(60)->post(
                "https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent?key={$apiKey}",
                [
                    "contents" => [
                        [
                            "parts" => [
                                ["text" => $prompt]
                            ]
                        ]
                    ],
                    "generationConfig" => [
                        "temperature" => 0.3
                    ]
                ]
            );

            if (!$response->successful()) {

                Log::error('Gemini API error: ' . $response->body());

                return response()->json([
                    'success' => false,
                    'message' => 'Gemini API error',
                    'detail' => $response->body()
                ], 500);
            }

            $json = $response->json();

            $raw = data_get($json, 'candidates.0.content.parts.0.text', '');

            $parsed = json_decode($raw, true);

            if (json_last_error() !== JSON_ERROR_NONE) {

                if (preg_match('/\{.*\}/s', $raw, $matches)) {
                    $parsed = json_decode($matches[0], true);
                } else {

                    Log::warning('Gemini raw response: ' . $raw);

                    return response()->json([
                        'success' => false,
                        'message' => 'Không parse được JSON từ Gemini',
                        'raw' => $raw
                    ], 500);
                }
            }

            $title = mb_substr(trim($parsed['title'] ?? ''), 0, 500);
            $summary = mb_substr(trim($parsed['summary'] ?? ''), 0, 1000);

            return response()->json([
                'success' => true,
                'title' => $title,
                'summary' => $summary
            ]);
        } catch (\Exception $e) {

            Log::error('Gemini exception: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi gọi Gemini: ' . $e->getMessage()
            ], 500);
        }
    }


    // ─── HIGHLIGHT STATS ─────────────────────────────────────────────────
    public function highlightStats()
    {
        return view('admins.pages.highlight_stats');
    }

    public function highlightStatsList()
    {
        $data = DB::table('highlight_stats')->orderBy('thutu', 'asc')->get();
        return response()->json(['data' => $data]);
    }

    public function highlightStatsStore(Request $request)
    {
        $id = (int) $request->input('statId', 0);

        $validator = Validator::make($request->all(), [
            'icon'      => 'required|string|max:100',
            'so_luong'  => 'required|string|max:50',
            'title'     => 'required|string|max:255',
            'thutu'     => 'nullable|integer|min:0',
        ], [
            'icon.required'     => 'Icon không được để trống',
            'so_luong.required' => 'Số lượng không được để trống',
            'title.required'    => 'Tiêu đề không được để trống',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()]);
        }

        DB::table('highlight_stats')->updateOrInsert(
            ['id' => $id ?: 0],
            [
                'icon'     => $request->icon,
                'so_luong' => $request->so_luong,
                'title'    => $request->title,
                'thutu'    => $request->thutu ?? 0,
            ]
        );

        $savedId = $id > 0 ? $id : (int) DB::getPdo()->lastInsertId();

        return response()->json([
            'success' => true,
            'message' => $id > 0 ? 'Cập nhật thành công!' : 'Thêm thành công!',
            'id'      => $savedId,
        ]);
    }

    public function highlightStatsGet($id)
    {
        $row = DB::table('highlight_stats')->find($id);
        if (!$row) return response()->json(['status' => 'error', 'message' => 'Không tìm thấy'], 404);
        return response()->json(['status' => 'success', 'data' => $row]);
    }

    public function highlightStatsDestroy($id)
    {
        DB::table('highlight_stats')->where('id', $id)->delete();
        return response()->json(['success' => true, 'message' => 'Xóa thành công!']);
    }





    // Trong AdminController.php
    public function uploadVideo(Request $request)
    {
        if ($request->hasFile('file')) {
            $request->validate([
                'file' => 'required|mimetypes:video/mp4,video/webm,video/ogg|max:102400', // max 100MB
            ]);

            $fileName = time() . '_' . $request->file('file')->getClientOriginalName();
            $destinationPath = $_SERVER['DOCUMENT_ROOT'] . '/uploads/videos';

            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $request->file('file')->move($destinationPath, $fileName);

            $videoUrl = asset('uploads/videos/' . $fileName);

            // Trả về đúng format HTML để Summernote chèn vào editor
            return response()->json([
                'url' => $videoUrl,
                'html' => '<video controls style="max-width: 100%; height: auto;"><source src="' . $videoUrl . '" type="video/mp4">Trình duyệt của bạn không hỗ trợ video.</video>'
            ]);
        }

        return response()->json(['error' => 'Không có file'], 400);
    }
}









/////////////////////////////////ngành