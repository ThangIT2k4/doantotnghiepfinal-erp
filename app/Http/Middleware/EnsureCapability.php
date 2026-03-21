<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\CapabilityService;

class EnsureCapability
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $capability  Required capability to access
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $capability)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $organizationId = $user->organizations()->first()?->id;

        if (!$organizationId) {
            abort(403, 'Bạn cần tham gia một tổ chức trước khi sử dụng tính năng này.');
        }

        // Check capability
        if (!CapabilityService::userHas($user->id, $organizationId, $capability)) {
            abort(403, 'Bạn không có quyền truy cập tính năng này.');
        }

        return $next($request);
    }
}

