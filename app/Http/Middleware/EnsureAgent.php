<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureAgent
{
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $roleKey = (string) ($request->session()->get('auth_role_key') ?? '');
        if ($roleKey !== 'agent') {
            abort(403);
        }

        return $next($request);
    }
}


