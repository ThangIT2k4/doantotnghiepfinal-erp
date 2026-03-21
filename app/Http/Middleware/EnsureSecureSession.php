<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware để đảm bảo session cookie được set đúng cho HTTPS
 * Tự động detect HTTPS và set secure cookie
 */
class EnsureSecureSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Chỉ chạy nếu SESSION_SECURE_COOKIE chưa được set trong .env
        // Nếu đã set trong .env, không override để tránh redirect loop
        $envSecureCookie = env('SESSION_SECURE_COOKIE');
        
        if ($envSecureCookie === null) {
            // Auto-detect HTTPS - chỉ dựa vào request thực tế, không force redirect
            $isSecure = $request->isSecure();
            
            // Check proxy headers only if request is already secure or behind trusted proxy
            if (!$isSecure) {
                $forwardedProto = $request->header('X-Forwarded-Proto');
                $forwardedSsl = $request->header('X-Forwarded-Ssl');
                
                // Chỉ trust proxy headers nếu request đã secure hoặc có header rõ ràng
                if ($forwardedProto === 'https' || $forwardedSsl === 'on') {
                    $isSecure = true;
                }
            }
            
            // Set secure cookie config dynamically - chỉ set một lần per request
            if (config('session.secure') === null) {
                config(['session.secure' => $isSecure]);
            }
            
            // Nếu dùng HTTPS, có thể cần same_site=none cho cross-site requests
            if ($isSecure && config('session.same_site') === 'lax') {
                // Giữ 'lax' cho same-site, chỉ đổi nếu thực sự cần cross-site
                // config(['session.same_site' => 'none']); // Uncomment nếu cần
            }
        } else {
            // Nếu đã set trong .env, sử dụng giá trị đó
            config(['session.secure' => $envSecureCookie === 'true' || $envSecureCookie === true]);
        }
        
        return $next($request);
    }
}

