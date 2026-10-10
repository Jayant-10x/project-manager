<?php

namespace App\Http\Middleware;

use App\Models\User;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CrmTokenAuth {
    public function handle(Request $request, Closure $next): Response {
        $token = $request->input('token')
            ?: $request->header('X-CRM-Token')
                ?: $request->query('token');

        if (!$token) {
            return response()->json([
                'ok' => false,
                'error' => 'AUTH',
                'message' => 'Your session has expired. Please sign in again.'
            ], 401);
        }

        $user = User::query()
            ->where('token', $token)
            ->where('status', 'Active')
            ->first();

        if (!$user) {
            return response()->json([
                'ok' => false,
                'error' => 'AUTH',
                'message' => 'Your session has expired. Please sign in again.'
            ], 401);
        }

        $sessionTtl = config('crm.session_ttl', 604800);
        if ($user->token_at && Carbon::parse($user->token_at)->diffInSeconds(Carbon::now()) > $sessionTtl) {
            return response()->json([
                'ok' => false,
                'error' => 'AUTH',
                'message' => 'Your session has expired. Please sign in again.'
            ], 401);
        }

        // Keep online status updated
        $user->update([
            'online' => true,
            'last_seen' => Carbon::now(),
        ]);

        $request->setUserResolver(fn() => $user);

        return $next($request);
    }
}
