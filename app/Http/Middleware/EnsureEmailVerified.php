<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailVerified
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        $allowedRoutes = [
            'auth.email-verification',
            'auth.email-verification.verify',
            'auth.email-verification.resend',
            'auth.email-verification.status',
            'logout',
            'logout.get',
        ];

        if ($routeName && in_array($routeName, $allowedRoutes, true)) {
            return $next($request);
        }

        $user = Auth::user();

        if ($user && ! $user->hasVerifiedEmail()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tài khoản chưa xác thực email. Vui lòng xác thực email trước khi tiếp tục.',
                    'redirect_url' => route('auth.email-verification'),
                ], 403);
            }

            return redirect()
                ->route('auth.email-verification')
                ->with('info', 'Tài khoản chưa xác thực email. Vui lòng xác thực email trước khi tiếp tục.');
        }

        return $next($request);
    }
}