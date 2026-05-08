<?php

use App\Helpers\ErrorHelper;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php'
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust nginx reverse proxy to read X-Forwarded-Proto: https
        $middleware->trustProxies(at: '*');

        // EnsureSecureSession chỉ áp dụng cho web routes (có session/cookie),
        // không áp dụng cho API routes (stateless token-based).
        $middleware->web(append: [
            \App\Http\Middleware\EnsureSecureSession::class,
        ]);

        // ForceJsonResponse đảm bảo API luôn trả về JSON thay vì redirect (302).
        // Ngăn Laravel redirect khi validation fail hoặc unauthenticated.
        $middleware->api(append: [
            \App\Http\Middleware\ForceJsonResponse::class,
        ]);

        $middleware->alias([
            'ensure.admin' => \App\Http\Middleware\EnsureAdmin::class,
            'ensure.manager' => \App\Http\Middleware\EnsureManager::class,
            'ensure.agent' => \App\Http\Middleware\EnsureAgent::class,
            'ensure.landlord' => \App\Http\Middleware\EnsureLandlord::class,
            'ensure.tenant' => \App\Http\Middleware\EnsureTenant::class,
            'ensure.capability' => \App\Http\Middleware\EnsureCapability::class,
            'ensure.email.verified' => \App\Http\Middleware\EnsureEmailVerified::class,
            'check.organization' => \App\Http\Middleware\CheckOrganizationAccess::class,
            'csrf.simple' => \App\Http\Middleware\SimpleCsrfHandler::class,
            'subscription.check' => \App\Http\Middleware\CheckSubscriptionLimits::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Xử lý TokenMismatchException (419 Page Expired)
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) {
            \Illuminate\Support\Facades\Log::warning('CSRF token mismatch', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_id' => session()->getId(),
                'has_session' => session()->isStarted(),
                'cookie_domain' => config('session.domain'),
                'cookie_secure' => config('session.secure'),
                'cookie_same_site' => config('session.same_site'),
            ]);
            
            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang và thử lại.',
                    'error' => 'token_mismatch',
                    'reload_required' => true
                ], 419);
            }

            return redirect()->back()
                ->withInput($request->except('_token', 'password', 'password_confirmation'))
                ->with('error', 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang và thử lại.');
        });
        // Helper function để lấy dashboard route theo role
        $getDashboardRoute = function ($roleKey = null) {
            if (!$roleKey) {
                $roleKey = session('auth_role_key');
                
                // Nếu chưa có role trong session, lấy từ database
                if (!$roleKey && \Illuminate\Support\Facades\Auth::check()) {
                    $userId = \Illuminate\Support\Facades\Auth::id();
                    $records = \Illuminate\Support\Facades\DB::table('organization_users')
                        ->join('roles', 'roles.id', '=', 'organization_users.role_id')
                        ->where('organization_users.user_id', $userId)
                        ->where('organization_users.status', 'active')
                        ->select('roles.key_code')
                        ->get();
                    
                    // Ưu tiên admin role nếu user có nhiều roles
                    $adminRecord = $records->firstWhere('key_code', 'admin');
                    if ($adminRecord) {
                        $roleKey = $adminRecord->key_code;
                    } else {
                        $record = $records->sortBy('key_code')->first();
                        $roleKey = $record->key_code ?? null;
                    }
                }
            }

            $routeByRole = [
                'admin' => 'superadmin.dashboard',
                'manager' => 'staff.dashboard',
                'agent' => 'staff.dashboard',
                'landlord' => 'landlord.dashboard',
                'tenant' => 'tenant.dashboard',
            ];

            return $routeByRole[$roleKey] ?? 'dashboard';
        };

        // Xử lý ModelNotFoundException (trước khi nó được convert thành NotFoundHttpException)
        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, \Illuminate\Http\Request $request) use ($getDashboardRoute) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Không tìm thấy dữ liệu yêu cầu.',
                    'error' => 'not_found'
                ], 404);
            }

            // Nếu là route tenant payments status, redirect về payments index với thông báo
            if ($request->is('tenant/payments/status/*')) {
                return redirect()->route('tenant.payments.index')
                    ->with('error', 'Không tìm thấy thông tin thanh toán này.');
            }

            // Nếu user đã đăng nhập, redirect về dashboard của role
            if (\Illuminate\Support\Facades\Auth::check()) {
                $targetRoute = $getDashboardRoute();
                
                return redirect()->route($targetRoute)
                    ->with('error', 'Không tìm thấy dữ liệu yêu cầu.');
            }

            // Nếu chưa đăng nhập, về trang home
            return redirect()->route('home')
                ->with('error', 'Không tìm thấy dữ liệu yêu cầu.');
        });

        // Xử lý 404 - Route không tồn tại
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, \Illuminate\Http\Request $request) use ($getDashboardRoute) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Route không tồn tại.',
                    'error' => 'not_found'
                ], 404);
            }

            // Nếu user đã đăng nhập, redirect về dashboard của role
            if (\Illuminate\Support\Facades\Auth::check()) {
                $targetRoute = $getDashboardRoute();
                
                return redirect()->route($targetRoute)
                    ->with('error', 'Trang bạn tìm kiếm không tồn tại.');
            }

            // Nếu chưa đăng nhập, về trang home
            return redirect()->route('home')
                ->with('error', 'Trang bạn tìm kiếm không tồn tại.');
        });

        // Xử lý 403 - Không có quyền truy cập
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, \Illuminate\Http\Request $request) use ($getDashboardRoute) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Bạn không có quyền truy cập trang này.',
                    'error' => 'forbidden'
                ], 403);
            }

            $fallbackRoute = \Illuminate\Support\Facades\Auth::check() ? $getDashboardRoute() : 'login';
            $fallbackUrl = route($fallbackRoute);
            $previousUrl = url()->previous();
            $currentUrl = $request->fullUrl();
            $backUrl = $previousUrl && $previousUrl !== $currentUrl ? $previousUrl : $fallbackUrl;

            $resolvedMessage = trim((string) $e->getMessage()) !== ''
                ? $e->getMessage()
                : 'Bạn không có quyền truy cập trang này.';

            $isChatLocked = \Illuminate\Support\Str::contains(
                mb_strtolower($resolvedMessage),
                ['chat với ai', 'chat ai', 'enable_chat', 'tính năng chat', 'gói dịch vụ', 'nâng cấp', 'subscription']
            );

            $plans = \App\Models\SubscriptionPlan::query()
                ->active()
                ->with('features')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            return response()->view('errors.forbidden', [
                'message' => $resolvedMessage,
                'plans' => $plans,
                'backUrl' => $backUrl,
                'fallbackUrl' => $fallbackUrl,
                'isChatLocked' => $isChatLocked,
            ], 403);
        });

        // Xử lý AuthorizationException (Laravel authorization)
        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, \Illuminate\Http\Request $request) use ($getDashboardRoute) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'Bạn không có quyền thực hiện hành động này.',
                    'error' => 'forbidden'
                ], 403);
            }

            $fallbackRoute = \Illuminate\Support\Facades\Auth::check() ? $getDashboardRoute() : 'login';
            $fallbackUrl = route($fallbackRoute);
            $previousUrl = url()->previous();
            $currentUrl = $request->fullUrl();
            $backUrl = $previousUrl && $previousUrl !== $currentUrl ? $previousUrl : $fallbackUrl;

            $resolvedMessage = trim((string) $e->getMessage()) !== ''
                ? $e->getMessage()
                : 'Bạn không có quyền thực hiện hành động này.';

            $isChatLocked = \Illuminate\Support\Str::contains(
                mb_strtolower($resolvedMessage),
                ['chat với ai', 'chat ai', 'enable_chat', 'tính năng chat', 'gói dịch vụ', 'nâng cấp', 'subscription']
            );

            $plans = \App\Models\SubscriptionPlan::query()
                ->active()
                ->with('features')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            return response()->view('errors.forbidden', [
                'message' => $resolvedMessage,
                'plans' => $plans,
                'backUrl' => $backUrl,
                'fallbackUrl' => $fallbackUrl,
                'isChatLocked' => $isChatLocked,
            ], 403);
        });

        // Xử lý QueryException (Database query errors) - Tránh lộ thông tin SQL
        $exceptions->render(function (\Illuminate\Database\QueryException $e, \Illuminate\Http\Request $request) use ($getDashboardRoute) {
            // Log chi tiết lỗi để debug (không hiển thị cho user)
            \Illuminate\Support\Facades\Log::error('Database QueryException', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            $safeMessage = ErrorHelper::getSafeErrorMessage($e, 'Có lỗi xảy ra khi xử lý dữ liệu. Vui lòng thử lại sau hoặc liên hệ quản trị viên.');

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $safeMessage,
                    'error' => 'database_error'
                ], 500);
            }

            // Nếu user đã đăng nhập, redirect về dashboard của role
            if (\Illuminate\Support\Facades\Auth::check()) {
                $targetRoute = $getDashboardRoute();
                return redirect()->route($targetRoute)->with('error', $safeMessage);
            }

            // Nếu chưa đăng nhập, về trang home
            return redirect()->route('home')->with('error', $safeMessage);
        });

        // Xử lý PDOException (Database connection errors) - Tránh lộ thông tin kết nối
        $exceptions->render(function (\PDOException $e, \Illuminate\Http\Request $request) use ($getDashboardRoute) {
            // Log chi tiết lỗi để debug (không hiển thị cho user)
            \Illuminate\Support\Facades\Log::error('PDOException', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            $safeMessage = ErrorHelper::getSafeErrorMessage($e, 'Có lỗi xảy ra khi kết nối cơ sở dữ liệu. Vui lòng thử lại sau hoặc liên hệ quản trị viên.');

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $safeMessage,
                    'error' => 'database_connection_error'
                ], 500);
            }

            // Nếu user đã đăng nhập, redirect về dashboard của role
            if (\Illuminate\Support\Facades\Auth::check()) {
                $targetRoute = $getDashboardRoute();
                return redirect()->route($targetRoute)->with('error', $safeMessage);
            }

            // Nếu chưa đăng nhập, về trang home
            return redirect()->route('home')->with('error', $safeMessage);
        });
    })->create();
