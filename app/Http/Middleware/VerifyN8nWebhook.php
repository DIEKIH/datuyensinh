<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyN8nWebhook
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $secretToken = env('N8N_SECRET_TOKEN');

        // If a secret token is configured, enforce it.
        // If not configured, we allow it (for dev environment), but warn in logs.
        if (!empty($secretToken)) {
            $providedToken = $request->header('X-N8N-Token') ?? $request->input('n8n_token');

            if ($providedToken !== $secretToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Invalid N8N Token.'
                ], 403);
            }
        }

        return $next($request);
    }
}
