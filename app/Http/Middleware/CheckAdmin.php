<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->session()->get('user');

        if (!$user || $user['role'] !== 'admin') {
            return redirect('/login')->with('error', 'Bạn không có quyền truy cập!');
        }

        return $next($request);
    }
}
