<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cookie;

class LoginController extends Controller
{
    // Hiển thị form login
    public function showLogin(Request $request)
    {
        $email = $request->cookie('remember_email'); // Lấy email từ cookie nếu có
        $password = $request->cookie('remember_password'); // Lấy password từ cookie nếu có (chú ý: chỉ ví dụ, nên mã hóa thực tế)
        return view('login', compact('email', 'password'));
    }

    // Xử lý login
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'password' => 'required'
        ]);

        $user = DB::table('nguoidung')
            ->where('email', $request->email)
            ->first();

        // Kiểm tra password (nếu dùng hash thì dùng Hash::check)
        if (!$user || $request->password !== $user->password) {
            return redirect()->back()->with('error', 'Tên đăng nhập hoặc mật khẩu không đúng!');
        }

        // Lưu session
        $request->session()->put('user', [
            'id' => $user->id,
            'email' => $user->email,
            'role' => $user->role
        ]);

        // Xử lý Remember Me
        if ($request->has('remember_account')) {
            // Lưu cookie 7 ngày
            Cookie::queue('remember_email', $request->email, 60*24*7);
            Cookie::queue('remember_password', $request->password, 60*24*7);
        } else {
            // Xoá cookie nếu không chọn
            Cookie::queue(Cookie::forget('remember_email'));
            Cookie::queue(Cookie::forget('remember_password'));
        }

        // Chuyển hướng theo role
        if ($user->role === 'user') {
            return redirect('/index');
        }
        return redirect('/admin/dashboard');
    }

    // Logout
    public function logout(Request $request)
    {
        $request->session()->forget('user');
        return redirect('/login')->with('success', 'Bạn đã đăng xuất!');
    }
}
