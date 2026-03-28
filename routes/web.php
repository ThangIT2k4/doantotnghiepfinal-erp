<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\User;











use App\Http\Controllers\Auth\EmailAuthController;
use App\Http\Controllers\IndexController;


/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
// Main landing page - index.blade.php
Route::get('/', [IndexController::class, 'index'])->name('home');
Route::post('/trial/contact', [IndexController::class, 'submitTrialContact'])->name('trial.contact.submit');

// Documentation routes - disabled
/*
Route::prefix('docs')->name('docs.')->group(function () {
    Route::get('/', [\App\Http\Controllers\DocumentationController::class, 'index'])->name('index');
    Route::get('/search', [\App\Http\Controllers\DocumentationController::class, 'search'])->name('search');
    Route::get('/{section}', [\App\Http\Controllers\DocumentationController::class, 'show'])->name('section');
    Route::get('/{section}/{file}', [\App\Http\Controllers\DocumentationController::class, 'show'])->name('show');
});
*/

// Old public routes - temporarily disabled
// Route::post('/search', [\App\Http\Controllers\HomeController::class, 'search'])->name('search');


// Public property routes - accessible without authentication
Route::get('/properties', [\App\Http\Controllers\PropertyController::class, 'index'])->name('property.index');
Route::get('/properties/{id}', [\App\Http\Controllers\PropertyController::class, 'show'])->name('property.show');

// Guest Payment routes - cho lead/guest thanh toán booking deposit
Route::prefix('guest/payment')->name('guest.payment.')->group(function () {
    Route::get('/{invoice}', [\App\Http\Controllers\GuestPaymentController::class, 'show'])->name('show');
    Route::post('/{invoice}/cash', [\App\Http\Controllers\GuestPaymentController::class, 'processCashPayment'])->name('cash');
    Route::post('/{invoice}/bank_qr', [\App\Http\Controllers\GuestPaymentController::class, 'processBankQrPayment'])->name('bank_qr');
    Route::post('/{invoice}/sepay', [\App\Http\Controllers\GuestPaymentController::class, 'processSepayPayment'])->name('sepay');
    Route::get('/{invoice}/status/{payment}', [\App\Http\Controllers\GuestPaymentController::class, 'checkPaymentStatus'])->name('status');
});

// Test routes - chỉ dùng trong development
Route::prefix('test-booking-deposit-email')->name('test.booking-deposit-email.')->group(function () {
    Route::get('/', [\App\Http\Controllers\TestBookingDepositEmailController::class, 'testInvoiceEmailForLead'])->name('test');
    Route::post('/create-test-data', [\App\Http\Controllers\TestBookingDepositEmailController::class, 'createTestData'])->name('create-test-data');
    Route::post('/issue-invoice', [\App\Http\Controllers\TestBookingDepositEmailController::class, 'testIssueInvoice'])->name('issue-invoice');
});


Route::get('/demo/preloader', function () {
    return view('demo.preloader');
})->name('demo.preloader');

Route::get('/demo/notifications', function () {
    return view('demo.notifications');
})->name('demo.notifications');

Route::get('/demo/button-preview', [\App\Http\Controllers\Staff\ButtonPreviewController::class, 'index'])
    ->middleware('auth')
    ->name('demo.button-preview');


Route::get('/news', function () {
    return view('news.index');
})->name('news.index');

Route::get('/contact', function () {
    return view('contact');
})->name('contact');

// Chat Routes
Route::prefix('chat')->name('chat.')->middleware('auth')->group(function () {
    Route::get('/', [\App\Http\Controllers\ChatController::class, 'index'])->name('index');
    Route::post('/send', [\App\Http\Controllers\ChatController::class, 'sendMessage'])->name('send');
    Route::post('/clear-cache', [\App\Http\Controllers\ChatController::class, 'clearCache'])->name('clear-cache');
});

Route::get('/detail/{id?}', function ($id = 1) {
    return view('detail', compact('id'));
})->name('detail');

// Property detail route
Route::get('/property/{id}', [\App\Http\Controllers\HomeController::class, 'propertyDetail'])->name('property.detail');

/*
|--------------------------------------------------------------------------
| Booking Routes (Public - No authentication required)
|--------------------------------------------------------------------------
*/
Route::prefix('tenant')->name('tenant.')->group(function () {
    Route::get('/booking/{id?}/{unit_id?}', [\App\Http\Controllers\ViewingController::class, 'booking'])->name('booking');
    Route::post('/booking/{id?}', [\App\Http\Controllers\ViewingController::class, 'store'])->name('booking.store');
});

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::get('/login', [EmailAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [EmailAuthController::class, 'login'])->name('login.store');
Route::get('/register', [EmailAuthController::class, 'showRegister'])->name('register');
Route::post('/register', [EmailAuthController::class, 'register'])->name('register.store');
Route::post('/logout', [EmailAuthController::class, 'logout'])->name('logout');
Route::get('/logout', [EmailAuthController::class, 'logout'])->name('logout.get');

// Email Verification Routes
Route::get('/email-verification', [\App\Http\Controllers\Auth\EmailVerificationController::class, 'show'])->name('auth.email-verification');
Route::post('/email-verification/verify', [\App\Http\Controllers\Auth\EmailVerificationController::class, 'verify'])->name('auth.email-verification.verify');
Route::post('/email-verification/resend', [\App\Http\Controllers\Auth\EmailVerificationController::class, 'resend'])->name('auth.email-verification.resend');
Route::get('/email-verification/status', [\App\Http\Controllers\Auth\EmailVerificationController::class, 'status'])->name('auth.email-verification.status');

// Password Reset Routes
Route::get('/forgot-password', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'showForgotPasswordForm'])->name('password.forgot');
Route::post('/forgot-password', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetOtp'])->name('password.forgot.submit');
Route::get('/forgot-password/otp', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'showOtpForm'])->name('password.forgot-otp');
Route::post('/forgot-password/verify-otp', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'verifyOtp'])->name('password.verify-otp');
Route::post('/forgot-password/resend-otp', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'resendOtp'])->name('password.resend-otp');
Route::get('/forgot-password/otp-status', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'getOtpStatus'])->name('password.otp-status');
Route::get('/reset-password', [\App\Http\Controllers\Auth\ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [\App\Http\Controllers\Auth\ResetPasswordController::class, 'resetPassword'])->name('password.reset.submit');

// Google OAuth Routes
Route::get('/auth/google', [\App\Http\Controllers\Auth\GoogleController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [\App\Http\Controllers\Auth\GoogleController::class, 'handleGoogleCallback'])->name('auth.google.callback');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    // Default dashboard resolver (redirects to role-specific dashboard)
    Route::get('/dashboard', function () {
        $roleKey = session('auth_role_key');
        if (! $roleKey && \Illuminate\Support\Facades\Auth::check()) {
            $userId = \Illuminate\Support\Facades\Auth::id();
            $record = \Illuminate\Support\Facades\DB::table('organization_users')
                ->join('roles', 'roles.id', '=', 'organization_users.role_id')
                ->where('organization_users.user_id', $userId)
                ->where('organization_users.status', 'active')
                ->orderBy('roles.id')
                ->select('roles.key_code')
                ->first();
            $roleKey = $record->key_code ?? null;
        }

        $routeByRole = [
            'admin' => 'superadmin.dashboard',
            'manager' => 'staff.dashboard',
            'agent' => 'staff.dashboard',
            'landlord' => 'landlord.dashboard',
            'tenant' => 'tenant.dashboard',
        ];

        $target = $routeByRole[$roleKey] ?? 'home';
        return redirect()->route($target);
    })->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | ADMIN Routes (ensure.admin)
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')->name('admin.')->middleware('ensure.admin')->group(function () {
        // Dashboard
       
    });

    /*
    |--------------------------------------------------------------------------
    | SUPER ADMIN Routes (superadmin)
    |--------------------------------------------------------------------------
    */
    Route::prefix('superadmin')->name('superadmin.')->middleware(['auth', 'ensure.admin'])->group(function () {
        // Dashboard
        Route::get('/dashboard', [\App\Http\Controllers\SuperAdmin\SuperAdminController::class, 'index'])->name('dashboard');
        Route::post('/clear-cache', [\App\Http\Controllers\SuperAdmin\SuperAdminController::class, 'clearCache'])->name('clear-cache');
        
        // Trial Leads Management
        Route::get('/trial-leads', [\App\Http\Controllers\SuperAdmin\SuperAdminController::class, 'trialLeads'])->name('trial-leads');
        
        // Organizations Management
        Route::get('/organizations', [\App\Http\Controllers\SuperAdmin\OrganizationController::class, 'index'])->name('organizations.index');
        Route::get('/organizations/create', [\App\Http\Controllers\SuperAdmin\OrganizationController::class, 'create'])->name('organizations.create');
        Route::post('/organizations', [\App\Http\Controllers\SuperAdmin\OrganizationController::class, 'store'])->name('organizations.store');
        Route::get('/organizations/{organization}', [\App\Http\Controllers\SuperAdmin\OrganizationController::class, 'show'])->name('organizations.show');
        Route::get('/organizations/{organization}/edit', [\App\Http\Controllers\SuperAdmin\OrganizationController::class, 'edit'])->name('organizations.edit');
        Route::put('/organizations/{organization}', [\App\Http\Controllers\SuperAdmin\OrganizationController::class, 'update'])->name('organizations.update');
        Route::delete('/organizations/{organization}', [\App\Http\Controllers\SuperAdmin\OrganizationController::class, 'destroy'])->name('organizations.destroy');
        Route::post('/organizations/{organization}/toggle-status', [\App\Http\Controllers\SuperAdmin\OrganizationController::class, 'toggleStatus'])->name('organizations.toggle-status');
        
        // Users Management
        Route::get('/users', [\App\Http\Controllers\SuperAdmin\UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [\App\Http\Controllers\SuperAdmin\UserController::class, 'create'])->name('users.create');
        Route::post('/users', [\App\Http\Controllers\SuperAdmin\UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}', [\App\Http\Controllers\SuperAdmin\UserController::class, 'show'])->name('users.show');
        Route::get('/users/{user}/edit', [\App\Http\Controllers\SuperAdmin\UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [\App\Http\Controllers\SuperAdmin\UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [\App\Http\Controllers\SuperAdmin\UserController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{user}/toggle-status', [\App\Http\Controllers\SuperAdmin\UserController::class, 'toggleStatus'])->name('users.toggle-status');
        
        // Subscription Plans Management
        Route::resource('subscription-plans', \App\Http\Controllers\SuperAdmin\SubscriptionPlanController::class)->parameters([
            'subscription-plans' => 'subscriptionPlan'
        ]);
        Route::post('/subscription-plans/{subscriptionPlan}/toggle-status', [\App\Http\Controllers\SuperAdmin\SubscriptionPlanController::class, 'toggleStatus'])->name('subscription-plans.toggle-status');
        Route::post('/subscription-plans/{subscriptionPlan}/duplicate', [\App\Http\Controllers\SuperAdmin\SubscriptionPlanController::class, 'duplicate'])->name('subscription-plans.duplicate');
        
        // Organization Subscriptions Management
        Route::get('/subscriptions', [\App\Http\Controllers\SuperAdmin\OrganizationSubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::get('/organizations/{organization}/subscription', [\App\Http\Controllers\SuperAdmin\OrganizationSubscriptionController::class, 'show'])->name('organizations.subscription.show');
        Route::get('/organizations/{organization}/subscription/assign', [\App\Http\Controllers\SuperAdmin\OrganizationSubscriptionController::class, 'assignPlan'])->name('organizations.subscription.assign');
        Route::post('/organizations/{organization}/subscription/assign', [\App\Http\Controllers\SuperAdmin\OrganizationSubscriptionController::class, 'storeAssignment'])->name('organizations.subscription.store');
        Route::post('/organizations/{organization}/subscription/cancel', [\App\Http\Controllers\SuperAdmin\OrganizationSubscriptionController::class, 'cancelSubscription'])->name('organizations.subscription.cancel');
        Route::post('/organizations/{organization}/subscription/extend', [\App\Http\Controllers\SuperAdmin\OrganizationSubscriptionController::class, 'extendSubscription'])->name('organizations.subscription.extend');
        Route::post('/organizations/{organization}/subscription/activate', [\App\Http\Controllers\SuperAdmin\OrganizationSubscriptionController::class, 'activateSubscription'])->name('organizations.subscription.activate');
        Route::post('/organizations/{organization}/subscription/renew', [\App\Http\Controllers\SuperAdmin\OrganizationSubscriptionController::class, 'renewSubscription'])->name('organizations.subscription.renew');
        Route::get('/organizations/{organization}/subscription/invoices', [\App\Http\Controllers\SuperAdmin\OrganizationSubscriptionController::class, 'invoices'])->name('organizations.subscription.invoices');
        
        // Subscription Invoices Management
        Route::resource('subscription-invoices', \App\Http\Controllers\SuperAdmin\SubscriptionInvoiceController::class)->parameters([
            'subscription-invoices' => 'subscriptionInvoice'
        ]);
        Route::post('/subscription-invoices/{subscriptionInvoice}/mark-paid', [\App\Http\Controllers\SuperAdmin\SubscriptionInvoiceController::class, 'markAsPaid'])->name('subscription-invoices.mark-paid');
        
        // Company Invoices Management
        Route::resource('company-invoices', \App\Http\Controllers\SuperAdmin\CompanyInvoiceController::class)->parameters([
            'company-invoices' => 'companyInvoice'
        ])->only(['index', 'show']);
        
        // Revenue Analytics
        // Route::get('/revenue', [\App\Http\Controllers\SuperAdmin\RevenueController::class, 'index'])->name('revenue.index');
        
        // System Management
        // Route::get('/system/health', [\App\Http\Controllers\SuperAdmin\SystemController::class, 'health'])->name('system.health');
        
        // Support
        // Route::get('/support/tickets', [\App\Http\Controllers\SuperAdmin\SupportController::class, 'tickets'])->name('support.tickets');
        
        // SePay Management
        Route::prefix('sepay')->name('sepay.')->group(function () {
            Route::get('/', [\App\Http\Controllers\SuperAdmin\SepayManagementController::class, 'index'])->name('index');
            Route::get('/{id}', [\App\Http\Controllers\SuperAdmin\SepayManagementController::class, 'show'])->name('show');
            Route::get('/export/transactions', [\App\Http\Controllers\SuperAdmin\SepayManagementController::class, 'export'])->name('export');
            Route::get('/settlement/report', [\App\Http\Controllers\SuperAdmin\SepayManagementController::class, 'settlementReport'])->name('settlement');
        });
        
        // Trash Management (Soft Deleted Records)
        Route::prefix('trash')->name('trash.')->group(function () {
            Route::get('/', [\App\Http\Controllers\SuperAdmin\TrashController::class, 'index'])->name('index');
            Route::post('/{table}/{id}/restore', [\App\Http\Controllers\SuperAdmin\TrashController::class, 'restore'])->name('restore');
            Route::delete('/{table}/{id}/force-delete', [\App\Http\Controllers\SuperAdmin\TrashController::class, 'forceDelete'])->name('force-delete');
            Route::post('/{table}/restore-multiple', [\App\Http\Controllers\SuperAdmin\TrashController::class, 'restoreMultiple'])->name('restore-multiple');
            Route::delete('/{table}/force-delete-multiple', [\App\Http\Controllers\SuperAdmin\TrashController::class, 'forceDeleteMultiple'])->name('force-delete-multiple');
        });
    });

    // API routes for properties (accessible without complex middleware)
    Route::prefix('api/properties')->middleware('auth')->group(function () {
        Route::get('/{propertyId}/units', [\App\Http\Controllers\Staff\LeaseController::class, 'getUnits'])->name('api.properties.units.simple');
    });

    // API routes for tenants
    Route::prefix('api')->middleware('auth')->group(function () {
        Route::get('/tenants', [\App\Http\Controllers\Staff\BookingDepositController::class, 'getTenants'])->name('api.tenants');
    });

    /*
    |--------------------------------------------------------------------------
    | MANAGER Routes - REMOVED (merged into Staff)
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | AGENT Routes - REMOVED (merged into Staff)
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | LANDLORD Routes (ensure.landlord)
    |--------------------------------------------------------------------------
    */
    Route::prefix('landlord')->name('landlord.')->middleware('ensure.landlord')->group(function () {
            // Dashboard
        
    });

    /*
    |--------------------------------------------------------------------------
    | TENANT Routes (ensure.tenant)
    |--------------------------------------------------------------------------
    */

    Route::prefix('tenant')->name('tenant.')->middleware(['ensure.tenant'])->group(function () {
        // Organization switching routes
        Route::get('/organizations', [\App\Http\Controllers\Tenant\OrganizationController::class, 'list'])->name('organizations.list');
        Route::post('/organizations/switch', [\App\Http\Controllers\Tenant\OrganizationController::class, 'switch'])->name('organizations.switch');
        
        // Dashboard
        Route::get('/dashboard', [\App\Http\Controllers\Tenant\DashboardController::class, 'index'])->name('dashboard');
        Route::post('/dashboard/clear-cache', [\App\Http\Controllers\Tenant\DashboardController::class, 'clearCache'])->name('dashboard.clear-cache');
        
        // Profile (includes banking information)
        Route::get('/profile', [\App\Http\Controllers\Tenant\ProfileController::class, 'index'])->name('profile');
        Route::get('/profile/edit', [\App\Http\Controllers\Tenant\ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [\App\Http\Controllers\Tenant\ProfileController::class, 'update'])->name('profile.update');
        
        // OTP Verification
        Route::get('/profile/otp-verification', [\App\Http\Controllers\Tenant\OtpController::class, 'showVerificationPage'])->name('profile.otp-verification');
        Route::post('/profile/otp/send', [\App\Http\Controllers\Tenant\OtpController::class, 'sendEmailVerification'])->name('profile.otp.send');
        Route::post('/profile/otp/verify', [\App\Http\Controllers\Tenant\OtpController::class, 'verifyOtp'])->name('profile.otp.verify');
        Route::post('/profile/otp/resend', [\App\Http\Controllers\Tenant\OtpController::class, 'resendOtp'])->name('profile.otp.resend');
        Route::get('/profile/otp/status', [\App\Http\Controllers\Tenant\OtpController::class, 'getOtpStatus'])->name('profile.otp.status');
        Route::post('/profile/otp/check-verification', [\App\Http\Controllers\Tenant\OtpController::class, 'checkEmailVerificationStatus'])->name('profile.otp.check-verification');
        Route::post('/profile/email/send-verification', [\App\Http\Controllers\Tenant\ProfileController::class, 'sendEmailVerificationOtp'])->name('profile.email.send-verification');
        Route::post('/profile/email/check-verification', [\App\Http\Controllers\Tenant\ProfileController::class, 'checkEmailVerificationStatus'])->name('profile.email.check-verification');
        // Appointments - moved from public viewings to tenant-specific
        Route::get('/appointments', [\App\Http\Controllers\ViewingController::class, 'appointments'])->name('appointments');
        Route::get('/appointments/{id}', [\App\Http\Controllers\ViewingController::class, 'show'])->name('appointments.show');
        Route::get('/appointments/{id}/edit', [\App\Http\Controllers\ViewingController::class, 'edit'])->name('appointments.edit');
        Route::get('/appointments/{id}/edit-data', [\App\Http\Controllers\ViewingController::class, 'getForEdit'])->name('appointments.edit-data');
        Route::post('/appointments/{id}/cancel', [\App\Http\Controllers\ViewingController::class, 'cancel'])->name('appointments.cancel');
        Route::put('/appointments/{id}/update', [\App\Http\Controllers\ViewingController::class, 'update'])->name('appointments.update');
        Route::put('/appointments/{id}/status', [\App\Http\Controllers\ViewingController::class, 'updateStatus'])->name('appointments.update-status');

        // Deposit
        Route::get('/deposit/{id?}', function ($id = 1) {
            return view('tenant.deposit', compact('id'));
        })->name('deposit');

        Route::post('/deposit/{id?}', function ($id = 1) {
            return response()->json(['success' => true, 'message' => 'Thanh toán thành công!', 'transaction_id' => 'DP' . date('Ymd') . rand(10, 99)]);
        })->name('deposit.store');

        // Contracts
        Route::get('/contracts', [\App\Http\Controllers\Tenant\ContractController::class, 'index'])->name('contracts.index');
        Route::get('/contracts/{id}', [\App\Http\Controllers\Tenant\ContractController::class, 'show'])->name('contracts.show');
        Route::get('/contracts/{id}/download', [\App\Http\Controllers\Tenant\ContractController::class, 'download'])->name('contracts.download');

        // Invoices
        Route::get('/invoices', [\App\Http\Controllers\Tenant\InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/{id}', [\App\Http\Controllers\Tenant\InvoiceController::class, 'show'])->name('invoices.show');
        Route::post('/invoices/{id}/pay', [\App\Http\Controllers\Tenant\InvoiceController::class, 'pay'])->name('invoices.pay');
        Route::get('/invoices/{id}/download', [\App\Http\Controllers\Tenant\InvoiceController::class, 'download'])->name('invoices.download');
        Route::get('/invoices/export', [\App\Http\Controllers\Tenant\InvoiceController::class, 'export'])->name('invoices.export');

        // Tickets
        Route::get('/tickets', [\App\Http\Controllers\Tenant\TicketController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/create', [\App\Http\Controllers\Tenant\TicketController::class, 'create'])->name('tickets.create');
        Route::post('/tickets', [\App\Http\Controllers\Tenant\TicketController::class, 'store'])->name('tickets.store');
        Route::get('/tickets/{id}', [\App\Http\Controllers\Tenant\TicketController::class, 'show'])->name('tickets.show');
        Route::get('/tickets/{id}/edit', [\App\Http\Controllers\Tenant\TicketController::class, 'edit'])->name('tickets.edit');
        Route::put('/tickets/{id}', [\App\Http\Controllers\Tenant\TicketController::class, 'update'])->name('tickets.update');
        Route::delete('/tickets/{id}', [\App\Http\Controllers\Tenant\TicketController::class, 'destroy'])->name('tickets.destroy');
        
        // API endpoints for tickets
        Route::prefix('api/tickets')->group(function () {
            Route::get('/leases/{leaseId}/units', [\App\Http\Controllers\Tenant\TicketController::class, 'getUnitsByLease']);
        });

        // Maintenance
        Route::get('/maintenance', function () {
            return view('tenant.maintenance');
        })->name('maintenance');

        Route::post('/maintenance', function () {
            return response()->json(['success' => true, 'message' => 'Yêu cầu sửa chữa đã được tạo thành công!', 'request_id' => 'YC' . rand(100, 999)]);
        })->name('maintenance.store');

        // Reviews (without edit functionality)
        Route::get('/reviews', [\App\Http\Controllers\Tenant\ReviewController::class, 'index'])->name('reviews.index');
        Route::get('/reviews/create', [\App\Http\Controllers\Tenant\ReviewController::class, 'create'])->name('reviews.create');
        Route::post('/reviews', [\App\Http\Controllers\Tenant\ReviewController::class, 'store'])->name('reviews.store');
        Route::get('/reviews/{id}', [\App\Http\Controllers\Tenant\ReviewController::class, 'show'])->name('reviews.show');
        Route::delete('/reviews/{id}', [\App\Http\Controllers\Tenant\ReviewController::class, 'destroy'])->name('reviews.destroy');
        Route::post('/reviews/{id}/reply', [\App\Http\Controllers\Tenant\ReviewController::class, 'storeReply'])->name('reviews.reply');
        Route::get('/api/reviews/reviewable-leases', [\App\Http\Controllers\Tenant\ReviewController::class, 'getReviewableLeases']);
        

        // Notifications
        Route::get('/notifications', [\App\Http\Controllers\Tenant\NotificationController::class, 'index'])->name('notifications');
        Route::get('/notifications/unread-count', [\App\Http\Controllers\Tenant\NotificationController::class, 'getUnreadCount'])->name('notifications.unread-count');
        Route::get('/notifications/recent', [\App\Http\Controllers\Tenant\NotificationController::class, 'getRecent'])->name('notifications.recent.index');
        Route::post('/notifications/{id}/mark-read', [\App\Http\Controllers\Tenant\NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
        Route::post('/notifications/mark-all-read', [\App\Http\Controllers\Tenant\NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
        Route::get('/notifications/{id}', [\App\Http\Controllers\Tenant\NotificationController::class, 'show'])->name('notifications.show.index');
        Route::delete('/notifications/{id}', [\App\Http\Controllers\Tenant\NotificationController::class, 'destroy'])->name('notifications.destroy');
        Route::get('/notifications/settings/preferences', [\App\Http\Controllers\Tenant\NotificationController::class, 'getSettings'])->name('notifications.settings.index');
        Route::post('/notifications/settings/preferences', [\App\Http\Controllers\Tenant\NotificationController::class, 'updateSettings'])->name('notifications.settings.update');

        // Rooms listing - REMOVED FOR NOW
        // Will be re-implemented later when needed

        // News
        Route::get('/news', function () {
            return view('tenant.news.index');
        })->name('news.index');

        // Contact
        Route::get('/contact', function () {
            return view('tenant.contact');
        })->name('contact');

        Route::post('/contact', function () {
            return response()->json(['success' => true, 'message' => 'Tin nhắn đã được gửi thành công!']);
        })->name('contact.store');

        // Payments
        Route::get('/payments', [\App\Http\Controllers\Tenant\TenantPaymentController::class, 'index'])->name('payments.index');
        
        // Payment Methods
        Route::get('/payments/methods/{invoiceId}', [\App\Http\Controllers\Tenant\TenantPaymentController::class, 'showPaymentMethods'])->name('payments.methods');
        
        // Payment Status
        Route::get('/payments/status/{paymentId}', function($paymentId) {
            $user = \Illuminate\Support\Facades\Auth::user();
            
            // Sử dụng cùng logic như index() - kiểm tra qua invoice.lease.tenant_id
            $payment = \App\Models\Payment::with(['invoice.lease.unit.property', 'method'])
                ->where('id', $paymentId)
                ->whereHas('invoice.lease', function($query) use ($user) {
                    $query->where('tenant_id', $user->id);
                })
                ->first();
            
            if (!$payment) {
                return redirect()->route('tenant.payments.index')
                    ->with('error', 'Không tìm thấy thông tin thanh toán này hoặc bạn không có quyền truy cập.');
            }
            
            return view('tenant.payments.status', compact('payment'));
        })->name('payments.status');
        
        // Payment API endpoints (for AJAX calls)
        Route::prefix('api/payments')->name('payments.api.')->group(function () {
            // Check payment status (JSON response)
            Route::get('/status/{paymentId}', [\App\Http\Controllers\Tenant\TenantPaymentController::class, 'checkPaymentStatus'])->name('status');
            
            // Cash payment
            Route::post('/cash/{invoiceId}', [\App\Http\Controllers\Tenant\TenantPaymentController::class, 'processCashPayment'])->name('cash');
            
            // Bank QR payment (chuyển khoản trực tuyến - ngân hàng tổ chức)
            Route::post('/bank_qr/{invoiceId}', [\App\Http\Controllers\Tenant\TenantPaymentController::class, 'processBankQrPayment'])->name('bank_qr');
            
            // Sepay payment (chuyển khoản qua SePay - tự động cập nhật)
            Route::post('/sepay/{invoiceId}', [\App\Http\Controllers\Tenant\TenantPaymentController::class, 'processSepayPayment'])->name('sepay');
            
            // Get bank config
            Route::get('/bank-config', [\App\Http\Controllers\Tenant\TenantPaymentController::class, 'getBankConfig'])->name('bank-config');
            
            // Test QR URL
            Route::get('/test-qr', [\App\Http\Controllers\Tenant\TenantPaymentController::class, 'testQRUrl'])->name('test-qr');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Legacy compatibility routes (backward compatibility with old /profile)
    |--------------------------------------------------------------------------
    */
    Route::get('/profile', function () {
        return redirect()->route('tenant.profile');
    });


    

    Route::get('/contracts', function () {
        return redirect()->route('tenant.contracts.index');
    });

    Route::get('/invoices', function () {
        return redirect()->route('tenant.invoices.index');
    });

    Route::get('/maintenance', function () {
        return redirect()->route('tenant.maintenance');
    });

    Route::get('/reviews', function () {
        return redirect()->route('tenant.reviews');
    });

    Route::get('/notifications', function () {
        return redirect()->route('tenant.notifications');
    });
});

/*
|--------------------------------------------------------------------------
| Viewing Routes (Public)
|--------------------------------------------------------------------------
*/
Route::prefix('viewings')->name('viewings.')->group(function () {
    Route::post('/store', [\App\Http\Controllers\ViewingController::class, 'store'])->name('store');
    Route::get('/available-slots', [\App\Http\Controllers\ViewingController::class, 'getAvailableSlots'])->name('available-slots');
});

/*
|--------------------------------------------------------------------------
| Authenticated Viewing Routes (Legacy - redirect to appointments for consistency)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('viewings')->name('viewings.')->group(function () {
    // Redirect legacy routes to appointments
    Route::get('/my-viewings', function () {
        return redirect()->route('tenant.appointments');
    })->name('my-viewings');
    
    Route::get('/{id}', function ($id) {
        return redirect()->route('tenant.appointments.show', $id);
    })->name('show');
    
    // Keep cancel and update routes for backward compatibility
    Route::post('/{id}/cancel', [\App\Http\Controllers\ViewingController::class, 'cancel'])->name('cancel');
    Route::put('/{id}/update', [\App\Http\Controllers\ViewingController::class, 'update'])->name('update');
});

/*
|--------------------------------------------------------------------------
| Booking Routes
|--------------------------------------------------------------------------
*/
// Redirect old booking routes to tenant booking routes
Route::get('/booking/{id?}', function ($id = 1) {
    return redirect()->route('tenant.booking', $id);
})->name('booking.redirect');





/*
|--------------------------------------------------------------------------
| Image Upload API Routes
|--------------------------------------------------------------------------
*/
Route::prefix('api/images')->name('api.images.')->middleware('auth')->group(function () {
    Route::post('/upload', [\App\Http\Controllers\Api\ImageController::class, 'upload'])->name('upload');
    Route::post('/upload-multiple', [\App\Http\Controllers\Api\ImageController::class, 'uploadMultiple'])->name('upload-multiple');
    Route::delete('/delete', [\App\Http\Controllers\Api\ImageController::class, 'delete'])->name('delete');
    Route::get('/url', [\App\Http\Controllers\Api\ImageController::class, 'getUrl'])->name('url');
    Route::get('/stats', [\App\Http\Controllers\Api\ImageController::class, 'stats'])->name('stats');
    Route::post('/validate', [\App\Http\Controllers\Api\ImageController::class, 'validate'])->name('validate');
});

/*
|--------------------------------------------------------------------------
| STAFF Routes (unified Manager and Agent)
|--------------------------------------------------------------------------
| Routes cho Staff (Manager và Agent) - hợp nhất theo ERP modules
| Sử dụng capability-based middleware thay vì role-based
*/
Route::prefix('staff')->name('staff.')->middleware(['auth', 'check.organization'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [\App\Http\Controllers\Staff\DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/clear-cache', [\App\Http\Controllers\Staff\DashboardController::class, 'clearCache'])->name('dashboard.clear-cache');
    Route::get('/dashboard/revenue-chart', [\App\Http\Controllers\Staff\DashboardController::class, 'getRevenueChartData'])->name('dashboard.revenue-chart');
    
    // Profile Management (Personal Account)
    Route::get('/profile', [\App\Http\Controllers\Staff\ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [\App\Http\Controllers\Staff\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [\App\Http\Controllers\Staff\ProfileController::class, 'update'])->name('profile.update');
    
    // Email Change OTP
    Route::post('/profile/email/check', [\App\Http\Controllers\Staff\ProfileController::class, 'checkEmail'])->name('profile.email.check');
    Route::post('/profile/email/send-otp', [\App\Http\Controllers\Staff\ProfileController::class, 'sendEmailChangeOtp'])->name('profile.email.send-otp');
    Route::post('/profile/email/verify-otp', [\App\Http\Controllers\Staff\ProfileController::class, 'verifyEmailChangeOtp'])->name('profile.email.verify-otp');
    
    // ERP Modules
    Route::get('/modules', [\App\Http\Controllers\Staff\ErpModuleController::class, 'index'])->name('modules.index');
    Route::get('/modules/{module}', [\App\Http\Controllers\Staff\ErpModuleController::class, 'show'])->name('modules.show');
    
    // Asset Module - Units
    // Custom routes must be defined BEFORE resource route to avoid conflicts
    Route::get('/units/{unitId}/maintenance-history', [\App\Http\Controllers\Staff\TicketController::class, 'getUnitMaintenanceHistory'])->name('units.maintenance-history');
    Route::get('/units/{id}/statistics', [\App\Http\Controllers\Staff\UnitController::class, 'statistics'])->name('units.statistics');
    Route::post('/units/store-single', [\App\Http\Controllers\Staff\UnitController::class, 'storeSingle'])->name('units.store-single');
    Route::post('/units/store-bulk', [\App\Http\Controllers\Staff\UnitController::class, 'storeBulk'])->name('units.store-bulk');
    Route::post('/units/{id}/update-status', [\App\Http\Controllers\Staff\UnitController::class, 'updateStatus'])->name('units.update-status');
    Route::resource('units', \App\Http\Controllers\Staff\UnitController::class);
    
    // Asset Module - Properties
    Route::post('/properties/{id}/update-status', [\App\Http\Controllers\Staff\PropertyController::class, 'updateStatus'])->name('properties.update-status');
    Route::resource('properties', \App\Http\Controllers\Staff\PropertyController::class);
    Route::get('/properties/{id}/statistics', [\App\Http\Controllers\Staff\PropertyController::class, 'statistics'])->name('properties.statistics');
    
    // API endpoints for geo data (cascading dropdowns)
    Route::prefix('api/geo')->group(function () {
        Route::get('/districts/{provinceCode}', [\App\Http\Controllers\Staff\PropertyController::class, 'getDistricts']);
        Route::get('/wards/{districtCode}', [\App\Http\Controllers\Staff\PropertyController::class, 'getWards']);
        Route::get('/wards-2025/{provinceCode}', [\App\Http\Controllers\Staff\PropertyController::class, 'getWards2025']);
    });
    
    // API endpoints for properties (units)
    Route::prefix('api/properties')->group(function () {
        Route::get('/{propertyId}/units', [\App\Http\Controllers\Staff\LeaseController::class, 'getUnits'])->name('api.properties.units');
        Route::get('/{propertyId}/payment-cycle', [\App\Http\Controllers\Staff\LeaseController::class, 'getPropertyPaymentCycle'])->name('api.properties.payment-cycle');
        Route::get('/{propertyId}/lease-service-set', [\App\Http\Controllers\Staff\LeaseController::class, 'getPropertyLeaseServiceSet'])->name('api.properties.lease-service-set');
        Route::get('/{propertyId}', [\App\Http\Controllers\Staff\LeaseController::class, 'getPropertyDetails'])->name('api.properties.details');
    });
    
    // Contract Module - Leases
    // Custom routes must be defined BEFORE resource route to avoid conflicts
    Route::get('/api/leases/next-contract-number', [\App\Http\Controllers\Staff\LeaseController::class, 'getNextContractNumber'])->name('api.leases.next-contract-number');
    Route::get('/leases/{id}/download', [\App\Http\Controllers\Staff\LeaseController::class, 'download'])->name('leases.download');
    Route::post('/leases/{id}/renew', [\App\Http\Controllers\Staff\LeaseController::class, 'renew'])->name('leases.renew');
    Route::post('/leases/{id}/terminate', [\App\Http\Controllers\Staff\LeaseController::class, 'terminate'])->name('leases.terminate');
    Route::post('/leases/{id}/update-status', [\App\Http\Controllers\Staff\LeaseController::class, 'updateStatus'])->name('leases.update-status');
    Route::post('/leases/{id}/create-invoice', [\App\Http\Controllers\Staff\LeaseController::class, 'createInvoice'])->name('leases.create-invoice');
    Route::post('/leases/{id}/documents', [\App\Http\Controllers\Staff\LeaseController::class, 'uploadDocument'])->name('leases.documents.upload');
    Route::delete('/leases/{leaseId}/documents/{documentId}', [\App\Http\Controllers\Staff\LeaseController::class, 'deleteDocument'])->name('leases.documents.delete');
    Route::delete('/leases/{leaseId}/residents/{residentId}', [\App\Http\Controllers\Staff\LeaseController::class, 'deleteResident'])->name('leases.residents.delete');
    Route::resource('leases', \App\Http\Controllers\Staff\LeaseController::class);
    
    // Billing Module - Invoices
    Route::resource('invoices', \App\Http\Controllers\Staff\InvoiceController::class);
    Route::get('/invoices/{invoice}/download', [\App\Http\Controllers\Staff\InvoiceController::class, 'download'])->name('invoices.download');
    Route::post('/invoices/{invoice}/mark-paid', [\App\Http\Controllers\Staff\InvoiceController::class, 'markAsPaid'])->name('invoices.mark-paid');
    Route::post('/invoices/{invoice}/issue', [\App\Http\Controllers\Staff\InvoiceController::class, 'issueInvoice'])->name('invoices.issue');
    Route::post('/invoices/{invoice}/update-status', [\App\Http\Controllers\Staff\InvoiceController::class, 'updateStatus'])->name('invoices.update-status');
    
    // API endpoints for invoices
    Route::prefix('api/invoices')->group(function () {
        Route::get('/leases/{leaseId}/details', [\App\Http\Controllers\Staff\InvoiceController::class, 'getLeaseDetails'])->name('api.invoices.leases.details');
    });
    
    // Billing Module - Payments
    Route::resource('payments', \App\Http\Controllers\Staff\PaymentController::class);
    Route::get('payments/invoices/{invoiceId}/details', [\App\Http\Controllers\Staff\PaymentController::class, 'getInvoiceDetails'])->name('payments.invoice-details');
    Route::post('payments/{payment}/mark-as-paid', [\App\Http\Controllers\Staff\PaymentController::class, 'markAsPaid'])->name('payments.mark-as-paid');
    Route::post('payments/{payment}/update-status', [\App\Http\Controllers\Staff\PaymentController::class, 'updateStatus'])->name('payments.update-status');
    Route::get('payments-statistics', [\App\Http\Controllers\Staff\PaymentController::class, 'statistics'])->name('payments.statistics');
    
    // Contract Module - Booking Deposits
    // Custom routes must be defined BEFORE resource route to avoid conflicts
    Route::get('/booking-deposits/statistics', [\App\Http\Controllers\Staff\BookingDepositController::class, 'statistics'])->name('booking-deposits.statistics');
    Route::post('/booking-deposits/{id}/approve', [\App\Http\Controllers\Staff\BookingDepositController::class, 'approve'])->name('booking-deposits.approve');
    Route::post('/booking-deposits/{id}/mark-paid', [\App\Http\Controllers\Staff\BookingDepositController::class, 'markPaid'])->name('booking-deposits.mark-paid');
    Route::post('/booking-deposits/{id}/cancel', [\App\Http\Controllers\Staff\BookingDepositController::class, 'cancel'])->name('booking-deposits.cancel');
    Route::post('/booking-deposits/{id}/refund', [\App\Http\Controllers\Staff\BookingDepositController::class, 'refund'])->name('booking-deposits.refund');
    Route::post('/booking-deposits/{id}/update-status', [\App\Http\Controllers\Staff\BookingDepositController::class, 'updateStatus'])->name('booking-deposits.update-status');
    Route::post('/booking-deposits/{id}/create-invoice', [\App\Http\Controllers\Staff\BookingDepositController::class, 'createInvoice'])->name('booking-deposits.create-invoice');
    Route::post('/booking-deposits/{id}/create-lease', [\App\Http\Controllers\Staff\BookingDepositController::class, 'createLease'])->name('booking-deposits.create-lease');
    Route::resource('booking-deposits', \App\Http\Controllers\Staff\BookingDepositController::class);
    
    // Contract Module - Booking Deposit Settings
    Route::prefix('booking-deposit-settings')->name('booking-deposit-settings.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Staff\BookingDepositSettingController::class, 'index'])->name('index');
        Route::post('/update-payment-due-hours', [\App\Http\Controllers\Staff\BookingDepositSettingController::class, 'updatePaymentDueHours'])->name('update-payment-due-hours');
    });
    
    // Settings Module - System Settings (Tổng hợp các cài đặt)
    Route::get('/system-settings', [\App\Http\Controllers\Staff\SystemSettingsController::class, 'index'])->name('system-settings.index');
    Route::post('/settings/organization/update-name', [\App\Http\Controllers\Staff\SystemSettingsController::class, 'updateOrganizationName'])->name('settings.organization.update-name');
    Route::get('/system-settings/filter-properties', [\App\Http\Controllers\Staff\SystemSettingsController::class, 'filterProperties'])->name('system-settings.filter-properties');
    
    // Settings Module - Organization Banking
    Route::prefix('organization-email-settings')->name('organization-email-settings.')->group(function () {
        Route::put('/', [\App\Http\Controllers\Staff\OrganizationEmailSettingController::class, 'update'])->name('update');
        Route::post('/test-connection', [\App\Http\Controllers\Staff\OrganizationEmailSettingController::class, 'testConnection'])->name('test-connection');
    });

    Route::prefix('organization-banking')->name('organization-banking.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Staff\OrganizationBankingController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Staff\OrganizationBankingController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Staff\OrganizationBankingController::class, 'store'])->name('store');
        Route::get('/{id}', [\App\Http\Controllers\Staff\OrganizationBankingController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [\App\Http\Controllers\Staff\OrganizationBankingController::class, 'edit'])->name('edit');
        Route::put('/{id}', [\App\Http\Controllers\Staff\OrganizationBankingController::class, 'update'])->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\Staff\OrganizationBankingController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/set-default', [\App\Http\Controllers\Staff\OrganizationBankingController::class, 'setDefault'])->name('set-default');
    });
    
    // Work Module - Tickets
    // API endpoints for tickets (must be before resource route)
    Route::prefix('api/tickets')->group(function () {
        Route::get('/units/{unitId}/leases', [\App\Http\Controllers\Staff\TicketController::class, 'getLeases'])->name('api.tickets.leases');
        Route::get('/properties/{propertyId}/manager', [\App\Http\Controllers\Staff\TicketController::class, 'getPropertyManager'])->name('api.tickets.property-manager');
    });
    // Custom routes for tickets (must be before resource route)
    Route::post('/tickets/{id}/logs', [\App\Http\Controllers\Staff\TicketController::class, 'addLog'])->name('tickets.logs');
    Route::post('/tickets/{id}/documents/upload', [\App\Http\Controllers\Staff\TicketController::class, 'uploadDocument'])->name('tickets.documents.upload');
    Route::delete('/tickets/{ticketId}/documents/{documentId}', [\App\Http\Controllers\Staff\TicketController::class, 'deleteDocument'])->name('tickets.documents.delete');
    Route::resource('tickets', \App\Http\Controllers\Staff\TicketController::class);
    
    // CRM Module - Viewings (Appointments)
    // Custom routes must be defined BEFORE resource route to avoid conflicts
    Route::get('/viewings/calendar', [\App\Http\Controllers\Staff\ViewingController::class, 'calendar'])->name('viewings.calendar');
    Route::get('/viewings/today', [\App\Http\Controllers\Staff\ViewingController::class, 'today'])->name('viewings.today');
    Route::get('/viewings/statistics', [\App\Http\Controllers\Staff\ViewingController::class, 'statistics'])->name('viewings.statistics');
    Route::post('/viewings/{id}/confirm', [\App\Http\Controllers\Staff\ViewingController::class, 'confirm'])->name('viewings.confirm');
    Route::post('/viewings/{id}/cancel', [\App\Http\Controllers\Staff\ViewingController::class, 'cancel'])->name('viewings.cancel');
    Route::post('/viewings/{id}/mark-done', [\App\Http\Controllers\Staff\ViewingController::class, 'markDone'])->name('viewings.mark-done');
    Route::resource('viewings', \App\Http\Controllers\Staff\ViewingController::class);
    
    // CRM Module - Leads
    // Custom routes must be defined BEFORE resource route to avoid conflicts
        Route::get('/leads/statistics', [\App\Http\Controllers\Staff\LeadController::class, 'statistics'])->name('leads.statistics');
        Route::post('/leads/{id}/status', [\App\Http\Controllers\Staff\LeadController::class, 'updateStatus'])->name('leads.update-status');
        Route::resource('leads', \App\Http\Controllers\Staff\LeadController::class);
    
    // CRM Module - Reviews
    // Custom routes must be defined BEFORE resource route to avoid conflicts
    Route::get('/reviews/statistics', [\App\Http\Controllers\Staff\ReviewController::class, 'statistics'])->name('reviews.statistics');
    Route::post('/reviews/{id}/reply', [\App\Http\Controllers\Staff\ReviewController::class, 'addReply'])->name('reviews.reply');
    
    // Trash Management (Soft Deleted Records) - Manager only
    Route::prefix('trash')->name('trash.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Staff\TrashController::class, 'index'])->name('index');
        Route::post('/{table}/{id}/restore', [\App\Http\Controllers\Staff\TrashController::class, 'restore'])->name('restore');
        Route::delete('/{table}/{id}/force-delete', [\App\Http\Controllers\Staff\TrashController::class, 'forceDelete'])->name('force-delete');
        Route::post('/{table}/restore-multiple', [\App\Http\Controllers\Staff\TrashController::class, 'restoreMultiple'])->name('restore-multiple');
        Route::delete('/{table}/force-delete-multiple', [\App\Http\Controllers\Staff\TrashController::class, 'forceDeleteMultiple'])->name('force-delete-multiple');
    });
    Route::post('/reviews/{id}/store-reply', [\App\Http\Controllers\Staff\ReviewController::class, 'storeReply'])->name('reviews.store-reply');
    Route::resource('reviews', \App\Http\Controllers\Staff\ReviewController::class);
    
    // Party Module - Tenants
    // Custom routes must be defined BEFORE resource route to avoid conflicts
    Route::get('/tenants/statistics', [\App\Http\Controllers\Staff\TenantController::class, 'statistics'])->name('tenants.statistics');
    Route::post('/tenants/{id}/update-status', [\App\Http\Controllers\Staff\TenantController::class, 'updateStatus'])->name('tenants.update-status');
    Route::post('/tenants/add-resident/{leaseId}', [\App\Http\Controllers\Staff\TenantController::class, 'addResident'])->name('tenants.add-resident');
    Route::get('/api/users/search', [\App\Http\Controllers\Staff\TenantController::class, 'searchUsers'])->name('api.users.search');
    Route::resource('tenants', \App\Http\Controllers\Staff\TenantController::class);
    
    // Party Module - User Banking
    Route::resource('user-banking', \App\Http\Controllers\Staff\UserBankingController::class)->parameters(['user-banking' => 'user_banking']);
    Route::get('/api/user-banking', [\App\Http\Controllers\Staff\UserBankingController::class, 'getUsers'])->name('api.user-banking');
    Route::get('/api/user-banking/{user}/bank-info', [\App\Http\Controllers\Staff\UserBankingController::class, 'getBankInfo'])->name('api.user-banking.bank-info');
    
    // Party Module - Staff Management
    Route::resource('staff', \App\Http\Controllers\Staff\StaffController::class);
    Route::get('/staff/{id}/salary-contracts', [\App\Http\Controllers\Staff\StaffController::class, 'getSalaryContracts'])->name('staff.salary-contracts');
    Route::get('/staff/{id}/commission-events', [\App\Http\Controllers\Staff\StaffController::class, 'getCommissionEvents'])->name('staff.commission-events');
    Route::post('/staff/{id}/assign-properties', [\App\Http\Controllers\Staff\StaffController::class, 'assignProperties'])->name('staff.assign-properties');
    Route::post('/staff/{id}/toggle-status', [\App\Http\Controllers\Staff\StaffController::class, 'toggleStatus'])->name('staff.toggle-status');
    
    // Finance Module - Commission Events
    Route::resource('commission-events', \App\Http\Controllers\Staff\CommissionEventController::class);
    Route::post('/commission-events/{commissionEvent}/approve', [\App\Http\Controllers\Staff\CommissionEventController::class, 'approve'])->name('commission-events.approve');
    Route::post('/commission-events/{commissionEvent}/mark-as-paid', [\App\Http\Controllers\Staff\CommissionEventController::class, 'markAsPaid'])->name('commission-events.mark-as-paid');
    Route::post('/commission-events/{commissionEvent}/update-status', [\App\Http\Controllers\Staff\CommissionEventController::class, 'updateStatus'])->name('commission-events.update-status');
    Route::get('/api/commission-events/leases-by-agent', [\App\Http\Controllers\Staff\CommissionEventController::class, 'getLeasesByAgent'])->name('api.commission-events.leases-by-agent');
    
    // Finance Module - Commission Policies
    Route::resource('commission-policies', \App\Http\Controllers\Staff\CommissionPolicyController::class);
    Route::post('/commission-policies/{commissionPolicy}/toggle-status', [\App\Http\Controllers\Staff\CommissionPolicyController::class, 'toggleStatus'])->name('commission-policies.toggle-status');
    
    // Contract Module - Deposit Refunds
    Route::resource('deposit-refunds', \App\Http\Controllers\Staff\DepositRefundController::class);
    Route::post('/deposit-refunds/{id}/approve', [\App\Http\Controllers\Staff\DepositRefundController::class, 'approve'])->name('deposit-refunds.approve');
    Route::post('/deposit-refunds/{id}/mark-paid', [\App\Http\Controllers\Staff\DepositRefundController::class, 'markPaid'])->name('deposit-refunds.mark-paid');
    Route::post('/deposit-refunds/{id}/cancel', [\App\Http\Controllers\Staff\DepositRefundController::class, 'cancel'])->name('deposit-refunds.cancel');
    Route::get('/deposit-refunds-statistics', [\App\Http\Controllers\Staff\DepositRefundController::class, 'statistics'])->name('deposit-refunds.statistics');
    
    // Finance Module - Payroll Payslips
    Route::resource('payroll-payslips', \App\Http\Controllers\Staff\PayrollPayslipController::class);
    Route::post('/payroll-payslips/{payrollPayslip}/mark-as-paid', [\App\Http\Controllers\Staff\PayrollPayslipController::class, 'markAsPaid'])->name('payroll-payslips.mark-as-paid');
    Route::post('/payroll-payslips/{payrollPayslip}/recalculate', [\App\Http\Controllers\Staff\PayrollPayslipController::class, 'recalculate'])->name('payroll-payslips.recalculate');
    Route::get('/payroll-payslips/{payrollPayslip}/create-company-invoice', [\App\Http\Controllers\Staff\PayrollPayslipController::class, 'createCompanyInvoice'])->name('payroll-payslips.create-company-invoice');
    Route::post('/payroll-payslips/{payrollPayslip}/store-company-invoice', [\App\Http\Controllers\Staff\PayrollPayslipController::class, 'storeCompanyInvoice'])->name('payroll-payslips.store-company-invoice');
    
    // Finance Module - Payroll Cycles
    Route::resource('payroll-cycles', \App\Http\Controllers\Staff\PayrollCycleController::class);
    Route::post('/payroll-cycles/{payrollCycle}/lock', [\App\Http\Controllers\Staff\PayrollCycleController::class, 'lock'])->name('payroll-cycles.lock');
    Route::post('/payroll-cycles/{payrollCycle}/update-status', [\App\Http\Controllers\Staff\PayrollCycleController::class, 'updateStatus'])->name('payroll-cycles.update-status');
    Route::get('/payroll-cycles/{payrollCycle}/preview-payslips', [\App\Http\Controllers\Staff\PayrollCycleController::class, 'previewPayslips'])->name('payroll-cycles.preview-payslips');
    Route::post('/payroll-cycles/{payrollCycle}/create-from-preview', [\App\Http\Controllers\Staff\PayrollCycleController::class, 'createFromPreview'])->name('payroll-cycles.create-from-preview');
    Route::post('/payroll-cycles/{payrollCycle}/sync-commission-events', [\App\Http\Controllers\Staff\PayrollCycleController::class, 'syncCommissionEvents'])->name('payroll-cycles.sync-commission-events');
    Route::post('/payroll-cycles/{payrollCycle}/generate-payslips', [\App\Http\Controllers\Staff\PayrollCycleController::class, 'generatePayslips'])->name('payroll-cycles.generate-payslips');
    
    // Diagnostic Tools (for production debugging without terminal access)
    Route::get('/diagnostic/logs', [\App\Http\Controllers\Staff\DiagnosticController::class, 'viewLogs'])->name('diagnostic.logs');
    Route::get('/diagnostic/notification-logs', [\App\Http\Controllers\Staff\DiagnosticController::class, 'viewNotificationLogs'])->name('diagnostic.notification-logs');
    Route::get('/diagnostic/payslip-errors', [\App\Http\Controllers\Staff\DiagnosticController::class, 'getPayslipErrors'])->name('diagnostic.payslip-errors');
    
    // Party Module - Salary Contracts
    Route::resource('salary-contracts', \App\Http\Controllers\Staff\SalaryContractController::class);
    Route::post('/salary-contracts/{salaryContract}/terminate', [\App\Http\Controllers\Staff\SalaryContractController::class, 'terminate'])->name('salary-contracts.terminate');
    Route::post('/salary-contracts/{salaryContract}/activate', [\App\Http\Controllers\Staff\SalaryContractController::class, 'activate'])->name('salary-contracts.activate');
    Route::post('/salary-contracts/{salaryContract}/update-status', [\App\Http\Controllers\Staff\SalaryContractController::class, 'updateStatus'])->name('salary-contracts.update-status');
    
    // Finance Module - Salary Advances
    Route::resource('salary-advances', \App\Http\Controllers\Staff\SalaryAdvanceController::class);
    Route::post('/salary-advances/{salaryAdvance}/approve', [\App\Http\Controllers\Staff\SalaryAdvanceController::class, 'approve'])->name('salary-advances.approve');
    Route::post('/salary-advances/{salaryAdvance}/reject', [\App\Http\Controllers\Staff\SalaryAdvanceController::class, 'reject'])->name('salary-advances.reject');
    Route::post('/salary-advances/{salaryAdvance}/add-repayment', [\App\Http\Controllers\Staff\SalaryAdvanceController::class, 'addRepayment'])->name('salary-advances.add-repayment');
    
    // Asset Module - Properties
    Route::resource('properties', \App\Http\Controllers\Staff\PropertyController::class);
    
    // Asset Module - Meters
    // Custom routes must be defined BEFORE resource route to avoid conflicts
    Route::get('/meters/get-units', [\App\Http\Controllers\Staff\MeterController::class, 'getUnits'])->name('meters.get-units');
    Route::get('/meters/statistics', [\App\Http\Controllers\Staff\MeterController::class, 'statistics'])->name('meters.statistics');
    Route::post('/meters/{id}/restore', [\App\Http\Controllers\Staff\MeterController::class, 'restore'])->name('meters.restore');
    Route::delete('/meters/{id}/force-delete', [\App\Http\Controllers\Staff\MeterController::class, 'forceDelete'])->name('meters.force-delete');
    Route::post('/meters/{id}/update-status', [\App\Http\Controllers\Staff\MeterController::class, 'updateStatus'])->name('meters.update-status');
    Route::resource('meters', \App\Http\Controllers\Staff\MeterController::class);
    
    // Asset Module - Meter Readings
    // Custom routes must be defined BEFORE resource route to avoid conflicts
    Route::get('/meter-readings/get-last-reading', [\App\Http\Controllers\Staff\MeterReadingController::class, 'getLastReading'])->name('meter-readings.get-last-reading');
    Route::get('/meter-readings/statistics', [\App\Http\Controllers\Staff\MeterReadingController::class, 'statistics'])->name('meter-readings.statistics');
    Route::resource('meter-readings', \App\Http\Controllers\Staff\MeterReadingController::class);
    
    // Asset Module - Property Types
    // Custom routes must be defined BEFORE resource route to avoid conflicts
    Route::post('/property-types/{id}/update-status', [\App\Http\Controllers\Staff\PropertyTypeController::class, 'updateStatus'])->name('property-types.update-status');
    Route::post('/property-types/{id}/restore', [\App\Http\Controllers\Staff\PropertyTypeController::class, 'restore'])->name('property-types.restore');
    Route::delete('/property-types/{id}/force-delete', [\App\Http\Controllers\Staff\PropertyTypeController::class, 'forceDelete'])->name('property-types.force-delete');
    Route::post('/property-types/delete-unused', [\App\Http\Controllers\Staff\PropertyTypeController::class, 'deleteUnusedPropertyTypes'])->name('property-types.delete-unused');
    Route::resource('property-types', \App\Http\Controllers\Staff\PropertyTypeController::class);
    Route::get('/api/property-types/options', [\App\Http\Controllers\Staff\PropertyTypeController::class, 'getOptions'])->name('property-types.options');
    
    // Notifications (Global feature - personal notifications for each user)
    Route::get('/notifications', [\App\Http\Controllers\Staff\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [\App\Http\Controllers\Staff\NotificationController::class, 'getUnreadCount'])->name('notifications.unread-count');
    Route::get('/notifications/recent', [\App\Http\Controllers\Staff\NotificationController::class, 'getRecent'])->name('notifications.recent');
    Route::post('/notifications/{id}/mark-read', [\App\Http\Controllers\Staff\NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::post('/notifications/mark-all-read', [\App\Http\Controllers\Staff\NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::get('/notifications/{id}', [\App\Http\Controllers\Staff\NotificationController::class, 'show'])->name('notifications.show');
    Route::delete('/notifications/{id}', [\App\Http\Controllers\Staff\NotificationController::class, 'destroy'])->name('notifications.destroy');
    
    // Finance Module - Vendors
    Route::resource('vendors', \App\Http\Controllers\Staff\VendorController::class);
    Route::post('/vendors/{vendor}/update-status', [\App\Http\Controllers\Staff\VendorController::class, 'updateStatus'])->name('vendors.update-status');
    Route::get('/api/vendors', [\App\Http\Controllers\Staff\VendorController::class, 'getVendors'])->name('api.vendors');
    Route::get('/api/vendors/{vendor}/bank-info', [\App\Http\Controllers\Staff\VendorController::class, 'getBankInfo'])->name('api.vendors.bank-info');
    
    // Party Module - Users
    Route::get('/users', [\App\Http\Controllers\Staff\UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [\App\Http\Controllers\Staff\UserController::class, 'create'])->name('users.create');
    Route::post('/users', [\App\Http\Controllers\Staff\UserController::class, 'store'])->name('users.store');
    Route::get('/users/{id}', [\App\Http\Controllers\Staff\UserController::class, 'show'])->name('users.show');
    Route::get('/users/{id}/edit', [\App\Http\Controllers\Staff\UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{id}', [\App\Http\Controllers\Staff\UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{id}', [\App\Http\Controllers\Staff\UserController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{id}/update-status', [\App\Http\Controllers\Staff\UserController::class, 'updateStatus'])->name('users.update-status');
    
    // Finance Module - Company Invoices
    Route::resource('company-invoices', \App\Http\Controllers\Staff\CompanyInvoiceController::class);
    Route::post('/company-invoices/{companyInvoice}/mark-paid', [\App\Http\Controllers\Staff\CompanyInvoiceController::class, 'markAsPaid'])->name('company-invoices.mark-paid');
    Route::post('/company-invoices/{companyInvoice}/approve', [\App\Http\Controllers\Staff\CompanyInvoiceController::class, 'approve'])->name('company-invoices.approve');
    Route::post('/company-invoices/{companyInvoice}/cancel', [\App\Http\Controllers\Staff\CompanyInvoiceController::class, 'cancel'])->name('company-invoices.cancel');
    Route::post('/company-invoices/{companyInvoice}/mark-overdue', [\App\Http\Controllers\Staff\CompanyInvoiceController::class, 'markOverdue'])->name('company-invoices.mark-overdue');
    Route::post('/company-invoices/{companyInvoice}/update-status', [\App\Http\Controllers\Staff\CompanyInvoiceController::class, 'updateStatus'])->name('company-invoices.update-status');
    Route::post('/company-invoices/bulk-action', [\App\Http\Controllers\Staff\CompanyInvoiceController::class, 'bulkAction'])->name('company-invoices.bulk-action');
    Route::get('/company-invoices-statistics', [\App\Http\Controllers\Staff\CompanyInvoiceController::class, 'statistics'])->name('company-invoices.statistics');
    Route::get('/api/company-invoices/source-data', [\App\Http\Controllers\Staff\CompanyInvoiceController::class, 'getSourceData'])->name('api.company-invoices.source-data');
    Route::get('/company-invoices/{companyInvoice}/payment', [\App\Http\Controllers\Staff\CompanyInvoiceController::class, 'payment'])->name('company-invoices.payment');
    Route::post('/company-invoices/{companyInvoice}/payment', [\App\Http\Controllers\Staff\CompanyInvoiceController::class, 'processPayment'])->name('company-invoices.process-payment');
    Route::post('/api/company-invoices/{companyInvoice}/payment/cash', [\App\Http\Controllers\Staff\CompanyInvoiceController::class, 'processCashPayment'])->name('api.company-invoices.payment.cash');
    Route::post('/api/company-invoices/{companyInvoice}/payment/sepay', [\App\Http\Controllers\Staff\CompanyInvoiceController::class, 'processSepayPayment'])->name('api.company-invoices.payment.sepay');
    Route::get('/company-invoices/{companyInvoice}/payment-status/{paymentId}', [\App\Http\Controllers\Staff\CompanyInvoiceController::class, 'paymentStatus'])->name('company-invoices.payment-status');
    
    // Finance Module - Cash Outflows
    Route::resource('cash-outflows', \App\Http\Controllers\Staff\CashOutflowController::class);
    Route::post('/cash-outflows/{cashOutflow}/mark-success', [\App\Http\Controllers\Staff\CashOutflowController::class, 'markAsSuccess'])->name('cash-outflows.mark-success');
    Route::post('/cash-outflows/{cashOutflow}/mark-failed', [\App\Http\Controllers\Staff\CashOutflowController::class, 'markAsFailed'])->name('cash-outflows.mark-failed');
    Route::post('/cash-outflows/{cashOutflow}/reverse', [\App\Http\Controllers\Staff\CashOutflowController::class, 'reverse'])->name('cash-outflows.reverse');
    Route::post('/cash-outflows/{cashOutflow}/update-status', [\App\Http\Controllers\Staff\CashOutflowController::class, 'updateStatus'])->name('cash-outflows.update-status');
    Route::post('/cash-outflows/bulk-action', [\App\Http\Controllers\Staff\CashOutflowController::class, 'bulkAction'])->name('cash-outflows.bulk-action');
    Route::get('/cash-outflows-statistics', [\App\Http\Controllers\Staff\CashOutflowController::class, 'statistics'])->name('cash-outflows.statistics');
    Route::get('/api/cash-outflows/company-invoice/{companyInvoiceId}', [\App\Http\Controllers\Staff\CashOutflowController::class, 'getCompanyInvoiceInfo'])->name('api.cash-outflows.company-invoice');
    
    // Party Module - Capabilities Management
    Route::get('/capabilities', [\App\Http\Controllers\Staff\CapabilityController::class, 'index'])->name('capabilities.index');
    Route::get('/users/{userId}/capabilities', [\App\Http\Controllers\Staff\CapabilityController::class, 'userCapabilities'])->name('users.capabilities');
    Route::post('/users/{userId}/capabilities/grant', [\App\Http\Controllers\Staff\CapabilityController::class, 'grant'])->name('users.capabilities.grant');
    Route::post('/users/{userId}/capabilities/revoke', [\App\Http\Controllers\Staff\CapabilityController::class, 'revoke'])->name('users.capabilities.revoke');
    Route::post('/users/{userId}/capabilities/remove-override', [\App\Http\Controllers\Staff\CapabilityController::class, 'removeOverride'])->name('users.capabilities.remove-override');
    Route::post('/users/{userId}/capabilities/bulk', [\App\Http\Controllers\Staff\CapabilityController::class, 'bulkUpdate'])->name('users.capabilities.bulk');
    Route::get('/api/capabilities/users', [\App\Http\Controllers\Staff\CapabilityController::class, 'getUsersWithCapability'])->name('api.capabilities.users');
    
    // Contract Module - Master Leases
    Route::resource('master-leases', \App\Http\Controllers\Staff\MasterLeaseController::class);
    Route::get('/master-leases/{masterLease}/units', [\App\Http\Controllers\Staff\MasterLeaseController::class, 'getUnits'])->name('master-leases.units');
    Route::post('/master-leases/{masterLease}/check-unit', [\App\Http\Controllers\Staff\MasterLeaseController::class, 'checkUnit'])->name('master-leases.check-unit');
    Route::patch('/master-leases/{masterLease}/status', [\App\Http\Controllers\Staff\MasterLeaseController::class, 'updateStatus'])->name('master-leases.update-status');
    
    // Finance Module - Financial Management
    Route::get('/financial-management', [\App\Http\Controllers\Staff\FinancialManagementController::class, 'index'])->name('financial-management.index');
    Route::get('/financial-management/cash-flow-forecast', [\App\Http\Controllers\Staff\FinancialManagementController::class, 'cashFlowForecast'])->name('financial-management.cash-flow-forecast');
    Route::get('/financial-management/expense-tracking', [\App\Http\Controllers\Staff\FinancialManagementController::class, 'expenseTracking'])->name('financial-management.expense-tracking');
    Route::get('/financial-management/payment-reminders', [\App\Http\Controllers\Staff\FinancialManagementController::class, 'paymentReminders'])->name('financial-management.payment-reminders');
    Route::post('/financial-management/invoices/{invoice}/send-reminder', [\App\Http\Controllers\Staff\FinancialManagementController::class, 'sendInvoiceReminder'])->name('financial-management.invoices.send-reminder');
    Route::post('/financial-management/company-invoices/{companyInvoice}/send-reminder', [\App\Http\Controllers\Staff\FinancialManagementController::class, 'sendCompanyInvoiceReminder'])->name('financial-management.company-invoices.send-reminder');
    Route::get('/financial-management/reconciliation', [\App\Http\Controllers\Staff\FinancialManagementController::class, 'reconciliationReports'])->name('financial-management.reconciliation');
    Route::get('/financial-management/tax-reports', [\App\Http\Controllers\Staff\FinancialManagementController::class, 'taxReports'])->name('financial-management.tax-reports');
    
    // Billing Module - Payment Cycle Settings
    Route::prefix('payment-cycle-settings')->name('payment-cycle-settings.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Staff\PaymentCycleSettingController::class, 'index'])->name('index');
        Route::put('/organization', [\App\Http\Controllers\Staff\PaymentCycleSettingController::class, 'updateOrganization'])->name('organization.update');
        Route::put('/property/{propertyId}', [\App\Http\Controllers\Staff\PaymentCycleSettingController::class, 'updateProperty'])->name('property.update');
        Route::put('/lease/{leaseId}', [\App\Http\Controllers\Staff\PaymentCycleSettingController::class, 'updateLease'])->name('lease.update');
        Route::get('/property/{propertyId}/leases', [\App\Http\Controllers\Staff\PaymentCycleSettingController::class, 'getPropertyLeases'])->name('property.leases');
        Route::post('/property/{propertyId}/apply-to-leases', [\App\Http\Controllers\Staff\PaymentCycleSettingController::class, 'applyToLeases'])->name('property.apply-to-leases');
        Route::post('/apply-to-properties', [\App\Http\Controllers\Staff\PaymentCycleSettingController::class, 'applyToProperties'])->name('apply-to-properties');
        // Payment cycle management routes
        Route::post('/cycles', [\App\Http\Controllers\Staff\PaymentCycleSettingController::class, 'storeCycle'])->name('cycles.store');
        Route::get('/cycles/{id}', [\App\Http\Controllers\Staff\PaymentCycleSettingController::class, 'getCycle'])->name('cycles.show');
        Route::put('/cycles/{id}', [\App\Http\Controllers\Staff\PaymentCycleSettingController::class, 'updateCycle'])->name('cycles.update');
        Route::delete('/cycles/{id}', [\App\Http\Controllers\Staff\PaymentCycleSettingController::class, 'destroyCycle'])->name('cycles.destroy');
        Route::post('/cycles/delete-unused', [\App\Http\Controllers\Staff\PaymentCycleSettingController::class, 'deleteUnusedCycles'])->name('cycles.delete-unused');
    });
    
    // Billing Module - Services Management
    Route::resource('services', \App\Http\Controllers\Staff\ServiceController::class)->names([
        'index' => 'services.index',
        'create' => 'services.create',
        'store' => 'services.store',
        'show' => 'services.show',
        'edit' => 'services.edit',
        'update' => 'services.update',
        'destroy' => 'services.destroy',
    ]);
    Route::post('/services/delete-unused', [\App\Http\Controllers\Staff\ServiceController::class, 'deleteUnusedServices'])->name('services.delete-unused');
    
    // Billing Module - Lease Service Settings
    Route::prefix('lease-service-settings')->name('lease-service-settings.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Staff\LeaseServiceSettingController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Staff\LeaseServiceSettingController::class, 'store'])->name('store');
        // More specific routes must come before general {id} routes
        Route::post('/sets', [\App\Http\Controllers\Staff\LeaseServiceSettingController::class, 'storeSet'])->name('sets.store');
        Route::get('/sets/{id}', [\App\Http\Controllers\Staff\LeaseServiceSettingController::class, 'getSet'])->name('sets.show');
        Route::put('/sets/{id}', [\App\Http\Controllers\Staff\LeaseServiceSettingController::class, 'updateSet'])->name('sets.update');
        Route::delete('/sets/{id}', [\App\Http\Controllers\Staff\LeaseServiceSettingController::class, 'destroySet'])->name('sets.destroy');
        Route::post('/sets/delete-unused', [\App\Http\Controllers\Staff\LeaseServiceSettingController::class, 'deleteUnusedSets'])->name('sets.delete-unused');
        Route::put('/organization', [\App\Http\Controllers\Staff\LeaseServiceSettingController::class, 'updateOrganization'])->name('organization.update');
        Route::get('/property/{propertyId}/leases', [\App\Http\Controllers\Staff\LeaseServiceSettingController::class, 'getPropertyLeases'])->name('property.leases');
        Route::put('/property/{propertyId}', [\App\Http\Controllers\Staff\LeaseServiceSettingController::class, 'updateProperty'])->name('property.update');
        Route::post('/apply-to-properties', [\App\Http\Controllers\Staff\LeaseServiceSettingController::class, 'applyToProperties'])->name('apply-to-properties');
        // General routes for individual service items
        Route::put('/{id}', [\App\Http\Controllers\Staff\LeaseServiceSettingController::class, 'update'])->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\Staff\LeaseServiceSettingController::class, 'destroy'])->name('destroy');
    });
    
    // Billing Module - Sepay Management
    Route::prefix('sepay')->name('sepay.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Staff\SepayController::class, 'index'])->name('index');
        Route::get('/transactions', [\App\Http\Controllers\Staff\SepayController::class, 'transactions'])->name('transactions');
        Route::get('/{id}', [\App\Http\Controllers\Staff\SepayController::class, 'show'])->name('show');
        Route::get('/settings', [\App\Http\Controllers\Staff\SepayController::class, 'settings'])->name('settings');
        Route::post('/settings', [\App\Http\Controllers\Staff\SepayController::class, 'updateSettings'])->name('settings.update');
        Route::post('/{id}/retry', [\App\Http\Controllers\Staff\SepayController::class, 'retry'])->name('retry');
        Route::get('/export', [\App\Http\Controllers\Staff\SepayController::class, 'export'])->name('export');
    });
    
    // Billing Module - Webhook Logs
    Route::prefix('webhook-logs')->name('webhook-logs.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Staff\WebhookLogController::class, 'index'])->name('index');
        Route::get('/{id}', [\App\Http\Controllers\Staff\WebhookLogController::class, 'show'])->name('show');
        Route::post('/{id}/retry', [\App\Http\Controllers\Staff\WebhookLogController::class, 'retry'])->name('retry');
    });
    
    // Finance Module - Excel Export
    Route::prefix('excel-export')->name('excel-export.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Staff\ExcelExportController::class, 'index'])->name('index');
        Route::post('/preview', [\App\Http\Controllers\Staff\ExcelExportController::class, 'preview'])->name('preview');
        Route::get('/properties', [\App\Http\Controllers\Staff\ExcelExportController::class, 'exportProperties'])->name('properties');
        Route::get('/units', [\App\Http\Controllers\Staff\ExcelExportController::class, 'exportUnits'])->name('units');
        Route::get('/invoices', [\App\Http\Controllers\Staff\ExcelExportController::class, 'exportInvoices'])->name('invoices');
        Route::get('/payments', [\App\Http\Controllers\Staff\ExcelExportController::class, 'exportPayments'])->name('payments');
        Route::get('/leases', [\App\Http\Controllers\Staff\ExcelExportController::class, 'exportLeases'])->name('leases');
        Route::get('/payroll', [\App\Http\Controllers\Staff\ExcelExportController::class, 'exportPayroll'])->name('payroll');
        Route::get('/company-invoices', [\App\Http\Controllers\Staff\ExcelExportController::class, 'exportCompanyInvoices'])->name('company-invoices');
        Route::get('/cash-outflows', [\App\Http\Controllers\Staff\ExcelExportController::class, 'exportCashOutflows'])->name('cash-outflows');
    });
    
    // Subscription Management (Staff)
    Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Staff\SubscriptionController::class, 'index'])->name('index');
        Route::get('/register/{subscriptionPlan}', [\App\Http\Controllers\Staff\SubscriptionController::class, 'register'])->name('register');
        Route::post('/register/{subscriptionPlan}', [\App\Http\Controllers\Staff\SubscriptionController::class, 'store'])->name('store');
        Route::get('/payment/{invoice}', [\App\Http\Controllers\Staff\SubscriptionController::class, 'payment'])->name('payment');
        Route::post('/payment/{invoice}/confirm', [\App\Http\Controllers\Staff\SubscriptionController::class, 'confirmPayment'])->name('payment.confirm');
        Route::prefix('invoices')->name('invoices.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Staff\SubscriptionController::class, 'invoices'])->name('index');
            Route::get('/{invoice}', [\App\Http\Controllers\Staff\SubscriptionController::class, 'showInvoice'])->name('show');
        });
    });
    
    // Subscription Payment API
    Route::prefix('api/subscriptions')->name('api.subscriptions.')->group(function () {
        Route::post('/payment/{invoice}/cash', [\App\Http\Controllers\Staff\SubscriptionController::class, 'processCashPayment'])->name('payment.cash');
        Route::post('/payment/{invoice}/sepay', [\App\Http\Controllers\Staff\SubscriptionController::class, 'processSepayPayment'])->name('payment.sepay');
    });
    
    // Redirect old manager and agent routes to staff routes
    Route::get('/redirect', function () {
        $roleKey = session('auth_role_key', '');
        if ($roleKey === 'manager') {
            return redirect()->route('staff.dashboard');
        } elseif ($roleKey === 'agent') {
            return redirect()->route('staff.dashboard');
        }
        return redirect()->route('home');
    })->name('redirect');
});

/*
|--------------------------------------------------------------------------
| Backward Compatibility Routes
|--------------------------------------------------------------------------
| Manager and Agent routes have been merged into Staff routes
*/

