<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Organization;
use App\Models\SubscriptionPlan;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use App\Models\OrganizationUser;
use App\Services\OtpService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;

/**
 * Controller: EmailAuthController
 * 
 * MỤC ĐÍCH:
 * Controller xử lý authentication bằng email/password (login và register).
 * Controller này quản lý việc đăng nhập, đăng ký, tạo organization, và gán subscription cho user mới.
 * 
 * LUỒNG XỬ LÝ:
 * 1. showLogin(): Hiển thị form đăng nhập
 * 2. login(): Xử lý đăng nhập
 *    - Validate credentials
 *    - Authenticate user (chỉ cho phép active users)
 *    - Resolve role và lưu vào session
 *    - Lưu organization info vào session
 *    - Redirect theo role
 * 3. showRegister(): Hiển thị form đăng ký
 * 4. register(): Xử lý đăng ký
 *    - Validate input
 *    - Kiểm tra email đã tồn tại chưa
 *    - Xử lý user cũ chưa verify email
 *    - Tạo user mới và user profile
 *    - Tạo organization mới
 *    - Gán subscription plan (trial)
 *    - Gửi OTP xác thực email, redirect /email-verification (không đăng nhập trước khi verify)
 * 
 * ENDPOINTS:
 * - GET /login: Hiển thị form đăng nhập
 * - POST /login: Xử lý đăng nhập
 * - GET /register: Hiển thị form đăng ký
 * - POST /register: Xử lý đăng ký
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Request: email, password, full_name, remember
 * - Bảng users: Kiểm tra user tồn tại, authenticate
 * - Bảng organizations: Tạo organization mới
 * - Bảng subscription_plans: Lấy trial plan
 * 
 * DỮ LIỆU GHI VÀO:
 * - Bảng users: Tạo user mới
 * - Bảng user_profiles: Tạo user profile
 * - Bảng organizations: Tạo organization mới
 * - Bảng organization_users: Gán user vào organization
 * - Bảng organization_subscriptions: Tạo trial subscription
 * - Session: auth_role_id, auth_role_key, auth_organization_id, auth_organization_name
 * 
 * SERVICES SỬ DỤNG:
 * - SubscriptionService: Tạo subscription cho organization mới
 * 
 * AUTHENTICATION:
 * - Chỉ cho phép active users (status = 1) đăng nhập
 * - Password được hash bằng bcrypt
 * - Session được regenerate sau khi login thành công
 * 
 * ROLE-BASED REDIRECTION:
 * - admin -> superadmin.dashboard
 * - manager/agent -> staff.dashboard
 * - landlord -> landlord.dashboard
 * - tenant -> tenant.dashboard
 * 
 * LƯU Ý:
 * - User chưa verify email có thể bị xóa nếu không có related records
 * - Organization code được tạo theo format: ORG_YYYYMMDDHHMMSS_random
 * - Trial subscription được tự động gán cho organization mới
 * - Email verification được gửi sau khi đăng ký thành công
 */
class EmailAuthController extends Controller
{
    /**
     * Khớp logic route GET /dashboard: chỉ coi là đã gán vai trò khi có key hợp lệ trong organization_users.
     */
    private function resolveAuthRoleKey(): ?string
    {
        $roleKey = session('auth_role_key');
        if (! $roleKey && Auth::check()) {
            $userId = Auth::id();
            $record = DB::table('organization_users')
                ->join('roles', 'roles.id', '=', 'organization_users.role_id')
                ->where('organization_users.user_id', $userId)
                ->where('organization_users.status', 'active')
                ->orderBy('roles.id')
                ->select('roles.key_code')
                ->first();
            $roleKey = $record->key_code ?? null;
        }

        return $roleKey;
    }

    /**
     * Sau khi đã đăng nhập: regenerate session, lưu role/org, redirect dashboard.
     */
    private function finalizeAuthenticatedSession(Request $request, User $user): RedirectResponse
    {
        $request->session()->regenerate();

        $role = $this->resolvePrimaryRole($user);

        if ($role) {
            $request->session()->put('auth_role_id', $role['id']);
            $request->session()->put('auth_role_key', $role['key_code']);
        }

        try {
            $organization = $user->organizations()->first();
            if ($organization) {
                $request->session()->put('auth_organization_id', $organization->id);
                $request->session()->put('auth_organization_name', $organization->name);
            }
        } catch (\Exception $e) {
            // User có thể chưa có organization relationship
        }

        $roleKey = is_array($role) ? ($role['key_code'] ?? null) : null;
        $routeByRole = [
            'admin' => 'superadmin.dashboard',
            'manager' => 'staff.dashboard',
            'agent' => 'staff.dashboard',
            'landlord' => 'landlord.dashboard',
            'tenant' => 'tenant.dashboard',
        ];
        $target = $routeByRole[$roleKey] ?? 'dashboard';

        return redirect()->route($target);
    }

    /**
     * Hiển thị form đăng nhập
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra user đã đăng nhập chưa
     * 2. Nếu đã đăng nhập và có vai trò hợp lệ: redirect thẳng tới dashboard theo role (giống /dashboard)
     * 3. Nếu session đăng nhập nhưng không có vai trò: đăng xuất và hiển thị form (tránh vòng redirect /login → /dashboard → trang chủ)
     * 4. Nếu chưa đăng nhập: Hiển thị form đăng nhập
     * 
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse View login hoặc redirect
     */
    public function showLogin(Request $request)
    {
        if (Auth::check()) {
            // $user = Auth::user();
            // if ($user && ! $user->hasVerifiedEmail()) {
            //     return redirect()
            //         ->route('auth.email-verification')
            //         ->with('info', 'Tài khoản chưa xác thực email. Vui lòng xác thực email trước khi tiếp tục.');
            // }

            $roleKey = $this->resolveAuthRoleKey();
            $routeByRole = [
                'admin' => 'superadmin.dashboard',
                'manager' => 'staff.dashboard',
                'agent' => 'staff.dashboard',
                'landlord' => 'landlord.dashboard',
                'tenant' => 'tenant.dashboard',
            ];
            $target = $routeByRole[$roleKey] ?? null;
            if ($target !== null) {
                return redirect()->route($target);
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('info', 'Phiên đăng nhập không hợp lệ hoặc tài khoản chưa được gán vai trò. Vui lòng đăng nhập lại.');
        }

        return view('auth.login');
    }

    /**
     * Xử lý đăng nhập
     * 
     * LUỒNG XỬ LÝ CHI TIẾT:
     * 1. Validate credentials (email, password)
     * 2. Authenticate user (chỉ cho phép active users, status = 1)
     * 3. Nếu authentication thất bại: Trả về lỗi
     * 4. Regenerate session để tránh session fixation
     * 5. Resolve role và lưu vào session
     * 6. Lưu organization info vào session
     * 7. Redirect theo role key
     * 
     * VALIDATION:
     * - email: required, email format
     * - password: required, string
     * 
     * AUTHENTICATION:
     * - Chỉ cho phép active users (status = 1)
     * - Remember me: Nếu user chọn "remember me", session sẽ kéo dài hơn
     * 
     * @param Request $request HTTP request chứa email, password, remember
     * @return \Illuminate\Http\RedirectResponse Redirect đến dashboard tương ứng
     */
    public function login(Request $request)
    {
        /**
         * Validate credentials từ request
         * 
         * $request->validate([...]) - Validate input data từ request
         *   - validate() là method của Request để validate data
         *   - Nếu validation thất bại, tự động redirect back với errors
         *   - Nếu validation thành công, trả về validated data
         * 
         * Validation rules:
         * - 'email' => ['required', 'email'] - Bắt buộc, phải là email format
         *   - required: Field bắt buộc phải có
         *   - email: Phải là email format hợp lệ
         * - 'password' => ['required', 'string'] - Bắt buộc, phải là string
         *   - required: Field bắt buộc phải có
         *   - string: Phải là string type
         * 
         * $credentials - Biến lưu validated data
         *   - Array chứa: ['email' => '...', 'password' => '...']
         *   - Sẽ được sử dụng để authenticate
         */
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        /**
         * Authenticate user (chỉ cho phép active users)
         * 
         * Auth::attempt([...], $remember) - Thử authenticate user với credentials
         *   - attempt() là method của Auth facade
         *   - Tham số 1: Array chứa credentials để authenticate
         *     - 'email' => $credentials['email'] - Email từ validated data
         *     - 'password' => $credentials['password'] - Password từ validated data (plain text)
         *     - 'status' => 1 - Chỉ cho phép active users (status = 1)
         *       - Lý do: Không cho phép inactive users đăng nhập
         *   - Tham số 2: $remember - Có remember user không
         *     - (bool) $request->boolean('remember') - Convert remember checkbox thành boolean
         *     - boolean('remember') là method của Request để lấy boolean value
         *     - Nếu remember = true, session sẽ kéo dài hơn (default: 2 weeks)
         *     - Nếu remember = false, session sẽ expire khi browser close
         *   - attempt() sẽ:
         *     1. Tìm user theo email và status = 1
         *     2. Verify password (so sánh với password_hash trong database)
         *     3. Nếu thành công: Login user và trả về true
         *     4. Nếu thất bại: Trả về false
         * 
         * $attempt - Biến lưu kết quả authentication
         *   - true nếu authentication thành công
         *   - false nếu authentication thất bại
         */
        $attempt = Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'status' => 1,
        ], (bool) $request->boolean('remember'));

        /**
         * Kiểm tra authentication có thất bại không
         * 
         * if (! $attempt) - Kiểm tra xem authentication có thất bại không
         *   - ! là NOT operator, đảo ngược giá trị boolean
         *   - Nếu $attempt = false, !false = true, vào block if
         *   - Nếu $attempt = true, !true = false, không vào block if
         */
        if (! $attempt) {
            /**
             * Trả về lỗi nếu authentication thất bại
             * 
             * back() - Tạo redirect response về trang trước
             *   - back() là helper function của Laravel
             *   - Trả về RedirectResponse về URL trước đó
             * 
             * ->withInput($request->only('email')) - Giữ lại email input để hiển thị lại
             *   - withInput() là method của RedirectResponse để flash input data vào session
             *   - $request->only('email') là method để lấy chỉ field 'email' từ request
             *   - Giúp user không phải nhập lại email
             * 
             * ->withErrors(['email' => '...']) - Thêm error message vào session
             *   - withErrors() là method của RedirectResponse để flash errors vào session
             *   - Array chứa errors: ['field' => 'error message']
             *   - Errors sẽ được hiển thị trong view qua $errors variable
             *   - 'Thông tin đăng nhập không đúng hoặc tài khoản bị khóa.' - Error message tiếng Việt
             * 
             * Lý do: Không cho biết chính xác lỗi gì (email sai hay password sai) để bảo mật
             */
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Thông tin đăng nhập không đúng hoặc tài khoản bị khóa.']);
        }

        /** @var User $user */
        $user = Auth::user();

        // if (! $user->hasVerifiedEmail()) {
        //     $pendingId = $user->id;
        //     Auth::logout();
        //     $request->session()->put('pending_email_verification_user_id', $pendingId);

        //     return redirect()->route('auth.email-verification')
        //         ->with('info', 'Tài khoản chưa xác thực email. Vui lòng nhập mã OTP hoặc bấm Gửi lại mã.');
        // }

        return $this->finalizeAuthenticatedSession($request, $user);
    }

    /**
     * Hiển thị form đăng ký
     * 
     * LUỒNG XỬ LÝ:
     * 1. Clear pending email verification session
     * 2. Hiển thị form đăng ký
     * 
     * @param Request $request HTTP request
     * @return \Illuminate\View\View View register form
     */
    public function showRegister(Request $request)
    {
        /**
         * Clear pending email verification session khi user truy cập trang đăng ký
         * 
         * $request->session()->forget('pending_email_verification_user_id') - Xóa session key
         *   - session() là method của Request để lấy session instance
         *   - forget() là method của Session để xóa một key khỏi session
         *   - 'pending_email_verification_user_id' là key chứa user ID đang chờ verify email
         * 
         * Lý do: Khi user truy cập trang đăng ký, clear session cũ để tránh conflict
         * - User có thể đã bắt đầu verify email nhưng chưa hoàn thành
         * - Khi quay lại trang đăng ký, clear session cũ để bắt đầu lại
         */
        $request->session()->forget('pending_email_verification_user_id');
        
        /**
         * Hiển thị form đăng ký
         * 
         * view('auth.register') - Tạo view response từ template 'auth.register'
         *   - view() là helper function của Laravel
         *   - 'auth.register' là path đến view file (resources/views/auth/register.blade.php)
         *   - Trả về View instance
         */
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Check if email already exists (chỉ kiểm tra user chưa bị xóa)
        $existingUser = User::where('email', $data['email'])
            ->whereNull('deleted_at')
            ->first();
        
        if ($existingUser) {
            // If user exists and email is verified, show error
            if ($existingUser->email_verified_at) {
                return back()->withErrors(['email' => 'Email này đã được sử dụng. Vui lòng sử dụng email khác hoặc đăng nhập.']);
            }
            
            // If user exists but email is not verified, check if user can be safely deleted
            // Check for related records that would prevent deletion (especially leases with RESTRICT constraint)
            $hasLeases = DB::table('leases')
                ->where('tenant_id', $existingUser->id)
                ->whereNull('deleted_at')
                ->exists();
            
            if ($hasLeases) {
                // User has lease records that prevent deletion
                return back()->withErrors(['email' => 'Email này đã được sử dụng và không thể xóa do có dữ liệu liên quan. Vui lòng sử dụng email khác hoặc liên hệ hỗ trợ.']);
            }
            
            // Check for other RESTRICT constraints that might prevent deletion
            $hasRestrictConstraints = (
                DB::table('commission_events')
                    ->where('agent_id', $existingUser->id)
                    ->whereNull('deleted_at')
                    ->exists() ||
                DB::table('documents')
                    ->where('uploaded_by', $existingUser->id)
                    ->whereNull('deleted_at')
                    ->exists() ||
                DB::table('company_invoices')
                    ->where('created_by', $existingUser->id)
                    ->whereNull('deleted_at')
                    ->exists() ||
                DB::table('payments')
                    ->where('payer_user_id', $existingUser->id)
                    ->whereNull('deleted_at')
                    ->exists() ||
                DB::table('tickets')
                    ->where('created_by', $existingUser->id)
                    ->whereNull('deleted_at')
                    ->exists() ||
                DB::table('viewings')
                    ->where('agent_id', $existingUser->id)
                    ->whereNull('deleted_at')
                    ->exists() ||
                DB::table('meter_readings')
                    ->where('taken_by', $existingUser->id)
                    ->whereNull('deleted_at')
                    ->exists() ||
                DB::table('ticket_logs')
                    ->where('actor_id', $existingUser->id)
                    ->exists() ||
                DB::table('notifications')
                    ->where('to_user_id', $existingUser->id)
                    ->exists()
            );
            
            if ($hasRestrictConstraints) {
                // User has records with RESTRICT constraints that prevent deletion
                return back()->withErrors(['email' => 'Email này đã được sử dụng và không thể xóa do có dữ liệu liên quan. Vui lòng sử dụng email khác hoặc liên hệ hỗ trợ.']);
            }
            
            // If user exists but email is not verified and has no blocking records, delete the old user
            // Also clean up related records
            DB::table('organization_users')->where('user_id', $existingUser->id)->delete();
            DB::table('email_otps')->where('user_id', $existingUser->id)->delete();
            $existingUser->forceDelete();
        }

        $user = new User();
        $user->email = $data['email'];
        $user->password_hash = Hash::make($data['password']);
        $user->status = 1;
        $user->save();

        // Create user profile with full_name
        \App\Models\UserProfile::create([
            'user_id' => $user->id,
            'full_name' => $data['full_name'],
        ]);

        // Tạo organization mới cho user đăng ký
        DB::beginTransaction();
        try {
            // Kiểm tra xem email đã tồn tại trong organizations chưa
            $existingOrg = Organization::where('email', $data['email'])
                ->whereNull('deleted_at')
                ->first();
            
            if ($existingOrg) {
                throw new \Exception('Email này đã được sử dụng bởi một tổ chức khác.');
            }
            
            // Tạo organization code: ORG_Datetime (ví dụ: ORG_20250112143025)
            // Thêm microseconds để tránh trùng lặp nếu nhiều user đăng ký cùng lúc
            $organizationCode = 'ORG_' . Carbon::now()->format('YmdHis') . '_' . substr(md5(uniqid(rand(), true)), 0, 6);
            
            // Đảm bảo code là duy nhất
            $codeExists = Organization::where('code', $organizationCode)->exists();
            $attempts = 0;
            while ($codeExists && $attempts < 5) {
                $organizationCode = 'ORG_' . Carbon::now()->format('YmdHis') . '_' . substr(md5(uniqid(rand(), true)), 0, 6);
                $codeExists = Organization::where('code', $organizationCode)->exists();
                $attempts++;
            }
            
            if ($codeExists) {
                throw new \Exception('Không thể tạo mã tổ chức duy nhất. Vui lòng thử lại.');
            }
            
            // Tạo organization mới với name = full_name của user
            // Thêm phone mặc định nếu không có (required field)
            // Sử dụng phone từ request hoặc tạo số mặc định dựa trên timestamp
            $phone = $request->input('phone');
            if (empty($phone)) {
                // Tạo số điện thoại mặc định: 0 + 9 chữ số cuối của timestamp
                $phone = '0' . substr(Carbon::now()->timestamp, -9);
            }
            
            $organization = Organization::create([
                'code' => $organizationCode,
                'name' => $data['full_name'],
                'email' => $data['email'],
                'phone' => $phone,
                'status' => true,
            ]);

            // Gán subscription plan mặc định (FREE plan) cho organization mới
            $this->assignDefaultSubscriptionPlan($organization);

            // Gán user vào organization mới với vai trò manager
            $managerRoleId = DB::table('roles')->where('key_code', 'manager')->value('id');
            
            if ($managerRoleId) {
                // Kiểm tra xem user đã có trong organization chưa (an toàn)
                $existingOrgUser = OrganizationUser::where('organization_id', $organization->id)
                    ->where('user_id', $user->id)
                    ->whereNull('deleted_at')
                    ->first();
                
                if (!$existingOrgUser) {
                    OrganizationUser::create([
                        'user_id' => $user->id,
                        'organization_id' => $organization->id,
                        'role_id' => $managerRoleId,
                        'status' => 'active',
                    ]);
                } else {
                    // Nếu đã tồn tại, cập nhật role
                    $existingOrgUser->update([
                        'role_id' => $managerRoleId,
                        'status' => 'active',
                    ]);
                }
            } else {
                Log::warning('Manager role not found when creating organization for new user', [
                    'user_id' => $user->id,
                    'organization_id' => $organization->id,
                ]);
            }

            DB::commit();

            Log::info('New organization created for registered user', [
                'user_id' => $user->id,
                'organization_id' => $organization->id,
                'organization_code' => $organizationCode,
                'organization_name' => $data['full_name'],
            ]);

        } catch (\Exception $e) {
            /**
             * Xử lý lỗi khi tạo organization: Rollback transaction
             */
            DB::rollBack();
            
            /**
             * Log error với thông tin chi tiết
             */
            Log::error('Error creating organization for new user: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'email' => $data['email'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            /**
             * Nếu tạo organization thất bại: Xóa user và profile để đảm bảo tính nhất quán
             * 
             * User không thể tồn tại mà không có organization
             */
            try {
                if ($user->userProfile) {
                    $user->userProfile->delete();
                }
                $user->delete();
            } catch (\Exception $deleteException) {
                /**
                 * Nếu xóa user thất bại: Log error (không throw exception)
                 */
                Log::error('Error cleaning up user after organization creation failure: ' . $deleteException->getMessage());
            }
            
            /**
             * Xác định thông báo lỗi cụ thể dựa trên error message
             * 
             * Kiểm tra các loại lỗi:
             * - Duplicate entry (1062): Email hoặc code đã tồn tại
             * - Cannot be null (1048): Thiếu thông tin bắt buộc (phone, etc.)
             * - Custom exception: Email đã được sử dụng
             */
            $errorMessage = 'Có lỗi xảy ra khi tạo tổ chức. Vui lòng thử lại.';
            
            /**
             * Kiểm tra lỗi duplicate entry (MySQL error code 1062)
             */
            if (str_contains($e->getMessage(), 'Duplicate entry') || str_contains($e->getMessage(), '1062')) {
                if (str_contains($e->getMessage(), 'email')) {
                    $errorMessage = 'Email này đã được sử dụng bởi một tổ chức khác. Vui lòng sử dụng email khác.';
                } elseif (str_contains($e->getMessage(), 'code')) {
                    $errorMessage = 'Mã tổ chức đã tồn tại. Vui lòng thử lại.';
                }
            } elseif (str_contains($e->getMessage(), 'cannot be null') || str_contains($e->getMessage(), '1048')) {
                /**
                 * Kiểm tra lỗi cannot be null (MySQL error code 1048)
                 */
                if (str_contains($e->getMessage(), 'phone')) {
                    $errorMessage = 'Số điện thoại là bắt buộc. Vui lòng cung cấp số điện thoại.';
                } else {
                    $errorMessage = 'Thiếu thông tin bắt buộc. Vui lòng kiểm tra lại.';
                }
            } elseif (str_contains($e->getMessage(), 'Email này đã được sử dụng')) {
                /**
                 * Sử dụng error message từ custom exception
                 */
                $errorMessage = $e->getMessage();
            }
            
            /**
             * Trả về lỗi với error message cụ thể
             */
            return back()->withErrors(['email' => $errorMessage])->withInput();
        }

        /**
         * Gửi OTP xác thực email — user chỉ đăng nhập sau khi verify (EmailVerificationController).
         */
        $otpService = app(OtpService::class);
        $sent = $otpService->sendEmailVerificationOtp($user, $user->email);
        if (! $sent) {
            Log::warning('Registration: OTP email failed to send', ['user_id' => $user->id, 'email' => $user->email]);
        }

        $request->session()->put('pending_email_verification_user_id', $user->id);

        return redirect()
            ->route('auth.email-verification')
            ->with(
                $sent ? 'success' : 'warning',
                $sent
                    ? 'Đăng ký thành công. Kiểm tra email và nhập mã OTP để kích hoạt tài khoản.'
                    : 'Đăng ký thành công nhưng chưa gửi được email OTP. Vui lòng bấm Gửi lại mã trên trang xác thực.'
            );
    }

    /**
     * Xử lý đăng xuất
     * 
     * LUỒNG XỬ LÝ:
     * 1. Logout user (clear authentication)
     * 2. Invalidate session (xóa tất cả session data)
     * 3. Regenerate CSRF token (tránh CSRF attack)
     * 4. Clear organization information từ session
     * 5. Redirect đến trang home
     * 
     * SECURITY:
     * - Session invalidation để đảm bảo session không thể sử dụng lại
     * - CSRF token regeneration để tránh CSRF attack
     * 
     * @param Request $request HTTP request
     * @return \Illuminate\Http\RedirectResponse Redirect đến trang home
     */
    public function logout(Request $request)
    {
        /**
         * Logout user: Clear authentication state
         */
        Auth::logout();
        
        /**
         * Invalidate session: Xóa tất cả session data
         */
        $request->session()->invalidate();
        
        /**
         * Regenerate CSRF token: Tạo token mới để tránh CSRF attack
         */
        $request->session()->regenerateToken();
        
        /**
         * Clear organization information từ session
         * 
         * Xóa các session keys:
         * - auth_organization_id
         * - auth_organization_name
         */
        $request->session()->forget(['auth_organization_id', 'auth_organization_name']);
        
        /**
         * Redirect đến trang home
         */
        return redirect()->route('home');
    }

    /**
     * Xác định role chính của user
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy tất cả roles active của user từ bảng organization_users
     * 2. Nếu không có role: Trả về null
     * 3. Nếu có admin role: Ưu tiên trả về admin role
     * 4. Nếu không có admin: Trả về role có ID nhỏ nhất
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng organization_users: Lấy roles của user
     * - Bảng roles: Lấy thông tin role (id, key_code)
     * 
     * LOGIC:
     * - User có thể có nhiều roles trong nhiều organizations
     * - Ưu tiên admin role nếu có
     * - Nếu không có admin, lấy role đầu tiên (ID nhỏ nhất)
     * 
     * @param User $user User cần xác định role
     * @return array|null Array chứa ['id' => int, 'key_code' => string] hoặc null nếu không có role
     */
    private function resolvePrimaryRole(User $user): ?array
    {
        /**
         * Lấy tất cả roles active của user
         * 
         * Join organization_users với roles để lấy thông tin role
         * Điều kiện:
         * - user_id = $user->id
         * - status = 'active' (chỉ lấy role active)
         * - deleted_at IS NULL (chỉ lấy role chưa bị xóa)
         */
        $records = DB::table('organization_users')
            ->join('roles', 'roles.id', '=', 'organization_users.role_id')
            ->where('organization_users.user_id', $user->id)
            ->where('organization_users.status', 'active')
            ->whereNull('organization_users.deleted_at')
            ->select('roles.id', 'roles.key_code')
            ->get();

        /**
         * Nếu không có role: Trả về null
         */
        if ($records->isEmpty()) {
            return null;
        }

        /**
         * Ưu tiên admin role nếu user có nhiều roles
         * 
         * Admin role có quyền cao nhất, nên ưu tiên sử dụng
         */
        $adminRole = $records->firstWhere('key_code', 'admin');
        if ($adminRole) {
            return ['id' => (int) $adminRole->id, 'key_code' => (string) $adminRole->key_code];
        }

        /**
         * Nếu không có admin: Lấy role có ID nhỏ nhất (role đầu tiên)
         * 
         * Sắp xếp theo ID và lấy role đầu tiên
         */
        $record = $records->sortBy('id')->first();

        return ['id' => (int) $record->id, 'key_code' => (string) $record->key_code];
    }

    /**
     * Gán subscription plan mặc định (FREE) cho organization mới tạo
     * 
     * LUỒNG XỬ LÝ:
     * 1. Tìm FREE plan trong bảng subscription_plans
     * 2. Nếu không có FREE plan: Lấy plan có sort_order thấp nhất
     * 3. Nếu tìm thấy plan: Gọi SubscriptionService->assignPlan()
     * 4. Tạo subscription với status 'active' (không trial)
     * 5. Log thông tin subscription đã tạo
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng subscription_plans: Tìm FREE plan hoặc plan có sort_order thấp nhất
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng organization_subscriptions: Tạo subscription với FREE plan
     * 
     * LƯU Ý:
     * - Subscription được tạo với status 'active' (không trial)
     * - Payment cycle: 'monthly'
     * - Auto renew: false
     * - Payment gateway: 'manual'
     * - Nếu không tìm thấy plan: Log warning nhưng không throw exception
     * 
     * @param Organization $organization Organization cần gán subscription plan
     * @return void
     */
    private function assignDefaultSubscriptionPlan(Organization $organization): void
    {
        try {
            /**
             * Tìm FREE plan (plan mặc định)
             * 
             * Điều kiện:
             * - code = 'FREE'
             * - is_active = true
             */
            $defaultPlan = SubscriptionPlan::where('code', 'FREE')
                ->where('is_active', true)
                ->first();

            /**
             * Nếu không có FREE plan: Lấy plan có sort_order thấp nhất
             * 
             * Fallback: Lấy plan đầu tiên trong danh sách (sort_order thấp nhất)
             */
            if (!$defaultPlan) {
                $defaultPlan = SubscriptionPlan::where('is_active', true)
                    ->orderBy('sort_order', 'asc')
                    ->first();
            }

            /**
             * Nếu tìm thấy plan: Gán cho organization
             */
            if ($defaultPlan) {
                /**
                 * Tạo SubscriptionService instance
                 */
                $subscriptionService = new SubscriptionService();
                
                /**
                 * Gán plan mặc định với status 'active' (không trial)
                 * 
                 * Tham số:
                 * - organization: Organization cần gán plan
                 * - defaultPlan: Plan mặc định (FREE)
                 * - 'monthly': Payment cycle (hàng tháng)
                 * - false: auto_renew (không tự động gia hạn)
                 * - false: startTrial = false → tạo subscription với status 'active' ngay (không trial)
                 * - 'manual': Payment gateway (thanh toán thủ công)
                 * 
                 * Lý do không trial:
                 * - Đăng ký mới từ trang đăng ký hoặc Google → active luôn gói FREE
                 * - Không cho dùng thử khi đăng ký mới
                 */
                $subscriptionService->assignPlan(
                    $organization,
                    $defaultPlan,
                    'monthly', // payment cycle
                    false, // auto_renew
                    false, // startTrial = false → tạo subscription với status 'active' ngay
                    'manual' // payment_gateway
                );

                /**
                 * Log thông tin subscription đã tạo thành công
                 */
                Log::info('Default subscription plan assigned to new organization (active, no trial)', [
                    'organization_id' => $organization->id,
                    'plan_id' => $defaultPlan->id,
                    'plan_code' => $defaultPlan->code,
                    'plan_name' => $defaultPlan->name,
                    'status' => 'active', // Active ngay, không trial
                    'trial_days' => $defaultPlan->trial_days,
                ]);
            } else {
                /**
                 * Nếu không tìm thấy plan: Log warning
                 * 
                 * Organization vẫn được tạo nhưng không có subscription plan
                 * Cần xử lý sau để gán plan cho organization
                 */
                Log::warning('No active subscription plan found to assign to new organization', [
                    'organization_id' => $organization->id,
                ]);
            }
        } catch (\Exception $e) {
            /**
             * Xử lý exception: Log error nhưng không throw
             * 
             * Lý do không throw:
             * - Organization đã được tạo thành công
             * - Subscription plan có thể được gán sau
             * - Không muốn rollback organization creation vì lỗi subscription
             */
            Log::error('Error assigning default subscription plan to organization: ' . $e->getMessage(), [
                'organization_id' => $organization->id,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}


