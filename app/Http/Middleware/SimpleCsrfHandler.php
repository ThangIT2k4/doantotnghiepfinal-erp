<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SimpleCsrfHandler
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (\Illuminate\Session\TokenMismatchException $e) {
            // Simple redirect back with error message
            return redirect()->back()
                ->with('error', 'Phiên làm việc đã hết hạn. Vui lòng thử lại.')
                ->withInput($request->except('_token'));
        }
    }
}
