<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckOrganizationAccess
{
    /**
     * Handle an incoming request.
     * Kiểm tra user chỉ có thể truy cập dữ liệu thuộc organization của mình
     * Admin có quyền truy cập tất cả
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        // Admin có quyền truy cập tất cả
        $isAdmin = $user->userRoles()->where('key_code', 'admin')->exists();
        if ($isAdmin) {
            return $next($request);
        }

        // Lấy organization context của user (có thể switch organization)
        $organizationId = $user->getCurrentOrganizationId();
        
        if (!$organizationId) {
            $message = 'Bạn chưa được gắn vào tổ chức nào. Vui lòng liên hệ quản trị viên.';
            
            // Nếu là AJAX request, trả về JSON với thông tin redirect
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'error' => 'no_organization',
                    'redirect' => url()->previous() ?: route('dashboard')
                ], 403);
            }
            
            // Nếu không phải AJAX, redirect về trang trước với flash message
            return redirect()->back()->with('error', $message);
        }

        // Lưu organization_id vào request để sử dụng trong controller
        $request->merge(['user_organization_id' => $organizationId]);

        return $next($request);
    }
}