<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\VisitorLog;
use Illuminate\Support\Facades\Cache;

class TrackVisitor
{
    public function handle(Request $request, Closure $next)
    {
        if (
            !$request->ajax()
            && !$request->is('api/*')
            && !$request->is('admin/*')
            && !$request->is('visitor/*')
        ) {
            $ip = $request->ip();
            $today = now()->toDateString();

            // Mỗi IP chỉ tính 1 lần / ngày
            $cacheKey = 'visitor_' . md5($ip . '_' . $today);

            if (!Cache::has($cacheKey)) {
                VisitorLog::firstOrCreate([
                    'ip' => $ip,
                    'visited_date' => $today,
                ]);

                // Cache đến hết ngày
                Cache::put($cacheKey, true, now()->endOfDay());
            }
        }

        return $next($request);
    }
}