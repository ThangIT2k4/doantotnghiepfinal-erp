<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        // 1. Check session first (fast path)
        $roleKey = (string) ($request->session()->get('auth_role_key') ?? '');
        if ($roleKey === 'admin') {
            return $next($request);
        }

        // 2. Fallback: check database if session is missing or incorrect
        $hasAdminRole = DB::table('organization_users')
            ->join('roles', 'roles.id', '=', 'organization_users.role_id')
            ->where('organization_users.user_id', Auth::id())
            ->where('organization_users.status', 'active')
            ->whereNull('organization_users.deleted_at')
            ->where('roles.key_code', 'admin')
            ->exists();

        // 3. Update session if admin role found in database
        if ($hasAdminRole) {
            $roleRecord = DB::table('organization_users')
                ->join('roles', 'roles.id', '=', 'organization_users.role_id')
                ->where('organization_users.user_id', Auth::id())
                ->where('organization_users.status', 'active')
                ->whereNull('organization_users.deleted_at')
                ->where('roles.key_code', 'admin')
                ->select('roles.id', 'roles.key_code')
                ->first();
            
            if ($roleRecord) {
                $request->session()->put('auth_role_id', $roleRecord->id);
                $request->session()->put('auth_role_key', $roleRecord->key_code);
            }
            
            return $next($request);
        }

        // 4. Deny access if user doesn't have admin role
        abort(403);
    }
}


