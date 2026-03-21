<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureManager
{
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $roleKey = (string) ($request->session()->get('auth_role_key') ?? '');
        if ($roleKey !== 'manager') {
            abort(403);
        }

        return $next($request);
    }
}


