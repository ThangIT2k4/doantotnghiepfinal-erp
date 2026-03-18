<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\SubscriptionPlan;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Carbon\Carbon;
use Exception;

/**
 * Class: GoogleController
 * 
 * MỤC ĐÍCH:
 * Controller xử lý đăng nhập và đăng ký bằng Google OAuth.
 * Controller này quản lý toàn bộ flow authentication cho user đăng nhập/đăng ký bằng Google account.
 * 
 * LUỒNG XỬ LÝ:
 * 1. redirectToGoogle(): Redirect user đến Google OAuth page
 * 2. handleGoogleCallback(): Xử lý callback từ Google
 *    - Lấy thông tin user từ Google
 *    - Kiểm tra user đã tồn tại với Google ID chưa
 *    - Nếu user tồn tại: Login và tạo organization nếu chưa có
 *    - Nếu user chưa tồn tại: Kiểm tra email đã tồn tại chưa
 *      - Nếu email đã tồn tại và verified: Update Google ID và login
 *      - Nếu email đã tồn tại nhưng chưa verified: Verify email, update Google ID và login
 *      - Nếu email chưa tồn tại: Tạo user mới, profile, organization, subscription
 *    - Lưu role và organization vào session
 *    - Redirect đến dashboard tương ứng với role
 * 3. storeSessionData(): Lưu role và organization vào session
 * 4. resolvePrimaryRole(): Xác định role chính của user
 * 5. assignDefaultSubscriptionPlan(): Gán subscription plan mặc định cho organization
 * 
 * ENDPOINTS:
 * - GET /auth/google: Redirect đến Google OAuth
 * - GET /auth/google/callback: Xử lý callback từ Google
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Google OAuth: User info (id, email, name, avatar)
 * - Bảng users: Kiểm tra user tồn tại (google_id, email)
 * - Bảng organizations: Kiểm tra email tồn tại
 * - Bảng organization_users: Lấy roles của user
 * - Bảng roles: Lấy role_id cho manager
 * - Bảng subscription_plans: Lấy FREE plan
 * 
 * DỮ LIỆU GHI VÀO:
 * - Bảng users: Tạo user mới, cập nhật google_id, email_verified_at
 * - Bảng user_profiles: Tạo/cập nhật profile với full_name, avatar
 * - Bảng organizations: Tạo organization mới
 * - Bảng organization_users: Gán user vào organization với role manager
 * - Bảng organization_subscriptions: Tạo subscription với FREE plan
 * - Session: auth_role_id, auth_role_key, auth_organization_id, auth_organization_name
 * 
 * MODELS/SERVICES SỬ DỤNG:
 * - User: Quản lý user accounts
 * - UserProfile: Quản lý user profiles
 * - Organization: Quản lý organizations
 * - OrganizationUser: Quản lý user-organization relationships
 * - SubscriptionPlan: Quản lý subscription plans
 * - SubscriptionService: Xử lý subscription logic
 * - Socialite: Laravel Socialite để xử lý OAuth
 * 
 * GOOGLE OAUTH FLOW:
 * 1. User click "Login with Google"
 * 2. Redirect đến Google OAuth page
 * 3. User authorize application
 * 4. Google redirect về /auth/google/callback với authorization code
 * 5. Exchange code lấy access token và user info
 * 6. Xử lý login/register với thông tin từ Google
 * 
 * LƯU Ý:
 * - Google users được auto-verify email (email_verified_at = now())
 * - User đăng ký bằng Google sẽ có organization mới với FREE plan (active, không trial)
 * - Nếu user đã tồn tại với email nhưng chưa có Google ID: Update Google ID để có thể login bằng Google sau
 * - Organization code được tạo theo format: ORG_YYYYMMDDHHmmss_random6chars
 * - Session chứa thông tin role và organization để access control
 * - Tất cả errors đều được log và redirect về trang login với error message
 */
class GoogleController extends Controller
{
    /**
     * Redirect user đến Google OAuth page
     * 
     * LUỒNG XỬ LÝ:
     * 1. Tạo Google OAuth driver
     * 2. Set redirect URL từ config
     * 3. Redirect user đến Google OAuth authorization page
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Config: services.google.redirect (redirect URL sau khi authorize)
     * 
     * GOOGLE OAUTH:
     * - User sẽ được redirect đến Google để authorize application
     * - Sau khi authorize, Google sẽ redirect về /auth/google/callback
     * 
     * @return \Symfony\Component\HttpFoundation\RedirectResponse Redirect đến Google OAuth page
     */
    public function redirectToGoogle()
    {
        /**
         * Tạo Google OAuth driver
         * 
         * Socialite::driver('google') sẽ:
         * - Load Google OAuth configuration từ config/services.php
         * - Tạo GoogleProvider instance
         */
        /** @var \Laravel\Socialite\Two\GoogleProvider $driver */
        $driver = Socialite::driver('google');
        
        /**
         * Set redirect URL và redirect user đến Google OAuth page
         * 
         * redirectUrl(): Set redirect URL sau khi Google authorize
         * redirect(): Redirect user đến Google OAuth authorization page
         */
        return $driver
            ->redirectUrl(config('services.google.redirect'))
            ->redirect();
    }

    /**
     * Xử lý callback từ Google OAuth
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy thông tin user từ Google (id, email, name, avatar)
     * 2. Kiểm tra user đã tồn tại với Google ID chưa:
     *    - Nếu có: Login và tạo organization nếu chưa có
     * 3. Nếu chưa có Google ID: Kiểm tra email đã tồn tại chưa:
     *    - Nếu email đã tồn tại và verified: Update Google ID, profile, tạo organization nếu chưa có, login
     *    - Nếu email đã tồn tại nhưng chưa verified: Verify email, update Google ID, profile, tạo organization nếu chưa có, login
     *    - Nếu email chưa tồn tại: Tạo user mới, profile, organization, subscription, login
     * 4. Lưu role và organization vào session
     * 5. Redirect đến dashboard tương ứng với role
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Google OAuth: User info (id, email, name, avatar)
     * - Bảng users: Kiểm tra user tồn tại (google_id, email)
     * - Bảng organizations: Kiểm tra email tồn tại
     * - Bảng organization_users: Lấy roles của user
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng users: Tạo/cập nhật user (google_id, email_verified_at)
     * - Bảng user_profiles: Tạo/cập nhật profile (full_name, avatar)
     * - Bảng organizations: Tạo organization mới (nếu chưa có)
     * - Bảng organization_users: Gán user vào organization (nếu chưa có)
     * - Bảng organization_subscriptions: Tạo subscription với FREE plan (nếu chưa có)
     * - Session: auth_role_id, auth_role_key, auth_organization_id, auth_organization_name
     * 
     * ERROR HANDLING:
     * - Nếu có exception: Log error và redirect về trang login với error message
     * - Nếu tạo organization thất bại: Rollback và log error (user vẫn có thể login)
     * 
     * @return \Illuminate\Http\RedirectResponse Redirect đến dashboard tương ứng với role hoặc trang login với lỗi
     */
    public function handleGoogleCallback()
    {
        try {
            /**
             * Lấy thông tin user từ Google OAuth
             * 
             * Socialite sẽ:
             * - Exchange authorization code lấy access token
             * - Gọi Google API lấy user info
             * - Trả về GoogleUser object với: id, email, name, avatar, etc.
             */
            /** @var \Laravel\Socialite\Two\GoogleProvider $driver */
            $driver = Socialite::driver('google');
            $googleUser = $driver
                ->redirectUrl(config('services.google.redirect'))
                ->user();
            
            /**
             * Kiểm tra user đã tồn tại với Google ID chưa
             * 
             * Tìm user có google_id = Google user ID
             * Nếu tìm thấy: User đã đăng ký/login bằng Google trước đó
             */
            $user = User::where('google_id', $googleUser->id)->first();
            
            /**
             * Xử lý trường hợp user đã tồn tại với Google ID
             */
            if ($user) {
                /**
                 * Kiểm tra user đã có organization chưa
                 * 
                 * Nếu chưa có: Tạo organization mới cho user
                 * Nếu đã có: Không cần tạo organization
                 */
                $hasOrganization = $user->organizations()->exists();
                
                /**
                 * Nếu user chưa có organization: Tạo organization mới
                 * 
                 * Sử dụng database transaction để đảm bảo tính nhất quán
                 */
                if (!$hasOrganization) {
                    /**
                     * Bắt đầu transaction để tạo organization
                     */
                    DB::beginTransaction();
                    try {
                        /**
                         * Kiểm tra email đã tồn tại trong organizations chưa
                         * 
                         * Mỗi organization phải có email duy nhất
                         */
                        $existingOrg = Organization::where('email', $googleUser->email)
                            ->whereNull('deleted_at')
                            ->first();
                        
                        /**
                         * Nếu email đã tồn tại trong organization: Throw exception
                         */
                        if ($existingOrg) {
                            throw new \Exception('Email này đã được sử dụng bởi một tổ chức khác.');
                        }
                        
                        /**
                         * Tạo organization code duy nhất
                         * 
                         * Format: ORG_YYYYMMDDHHmmss_random6chars
                         * Sử dụng timestamp và random string để tránh trùng lặp
                         */
                        $organizationCode = 'ORG_' . Carbon::now()->format('YmdHis') . '_' . substr(md5(uniqid(rand(), true)), 0, 6);
                        
                        /**
                         * Đảm bảo code là duy nhất (tối đa 5 lần thử)
                         */
                        $codeExists = Organization::where('code', $organizationCode)->exists();
                        $attempts = 0;
                        while ($codeExists && $attempts < 5) {
                            $organizationCode = 'ORG_' . Carbon::now()->format('YmdHis') . '_' . substr(md5(uniqid(rand(), true)), 0, 6);
                            $codeExists = Organization::where('code', $organizationCode)->exists();
                            $attempts++;
                        }
                        
                        /**
                         * Nếu vẫn trùng sau 5 lần: Throw exception
                         */
                        if ($codeExists) {
                            throw new \Exception('Không thể tạo mã tổ chức duy nhất. Vui lòng thử lại.');
                        }
                        
                        /**
                         * Tạo số điện thoại mặc định (required field)
                         * 
                         * Format: '0' + 9 chữ số cuối của timestamp
                         */
                        $phone = '0' . substr(Carbon::now()->timestamp, -9);
                        
                        /**
                         * Tạo organization mới
                         * 
                         * - code: Organization code duy nhất
                         * - name: Tên organization = name từ Google
                         * - email: Email từ Google
                         * - phone: Số điện thoại mặc định
                         * - status: true (active)
                         */
                        $organization = Organization::create([
                            'code' => $organizationCode,
                            'name' => $googleUser->name,
                            'email' => $googleUser->email,
                            'phone' => $phone,
                            'status' => true,
                        ]);

                        /**
                         * Gán subscription plan mặc định (FREE plan) cho organization mới
                         */
                        $this->assignDefaultSubscriptionPlan($organization);

                        /**
                         * Gán user vào organization mới với vai trò manager
                         */
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
                            /**
                             * Nếu không tìm thấy manager role: Log warning
                             */
                            Log::warning('Manager role not found when creating organization for existing Google ID user', [
                                'user_id' => $user->id,
                                'organization_id' => $organization->id,
                            ]);
                        }

                        /**
                         * Commit transaction: Tất cả thay đổi đã thành công
                         */
                        DB::commit();

                        /**
                         * Log thông tin organization đã tạo thành công
                         */
                        Log::info('New organization created for existing Google ID user', [
                            'user_id' => $user->id,
                            'organization_id' => $organization->id,
                            'organization_code' => $organizationCode,
                            'organization_name' => $googleUser->name,
                        ]);

                    } catch (\Exception $e) {
                        /**
                         * Xử lý lỗi khi tạo organization: Rollback transaction
                         * 
                         * Lưu ý: Không throw exception, chỉ log error
                         * User vẫn có thể login, chỉ là chưa có organization
                         */
                        DB::rollBack();
                        Log::error('Error creating organization for existing Google ID user: ' . $e->getMessage(), [
                            'user_id' => $user->id,
                            'email' => $googleUser->email,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        
                        /**
                         * Không throw exception: User vẫn có thể login
                         * Organization có thể được tạo sau
                         */
                    }
                }
                
                /**
                 * User đã tồn tại: Login user
                 */
                Auth::login($user);
            } else {
                /**
                 * User chưa tồn tại với Google ID: Kiểm tra email đã tồn tại chưa
                 * 
                 * Tìm user có email = Google email (chỉ kiểm tra user chưa bị xóa)
                 */
                $existingUser = User::where('email', $googleUser->email)
                    ->whereNull('deleted_at')
                    ->first();
                
                /**
                 * Xử lý trường hợp user đã tồn tại với email
                 */
                if ($existingUser) {
                    /**
                     * Trường hợp 1: User đã tồn tại và email đã được verify
                     * 
                     * - Update Google ID để user có thể login bằng Google sau
                     * - Update profile với thông tin từ Google
                     * - Tạo organization nếu chưa có
                     * - Login user
                     */
                    if ($existingUser->email_verified_at) {
                        /**
                         * Update Google ID để user có thể login bằng Google sau
                         */
                        $existingUser->google_id = $googleUser->id;
                        $existingUser->save();
                        
                        /**
                         * Update hoặc tạo user profile với thông tin từ Google
                         * 
                         * getOrCreateProfile(): Tạo profile nếu chưa có, hoặc lấy profile hiện có
                         * Update: full_name, avatar từ Google
                         */
                        $profile = $existingUser->getOrCreateProfile();
                        $profile->update([
                            'full_name' => $googleUser->name,
                            'avatar' => $googleUser->avatar,
                        ]);
                        
                        /**
                         * Kiểm tra user đã có organization chưa
                         * 
                         * Nếu chưa có: Tạo organization mới
                         */
                        $hasOrganization = $existingUser->organizations()->exists();
                        
                        /**
                         * Nếu user chưa có organization: Tạo organization mới
                         * 
                         * Logic tạo organization tương tự như trên (kiểm tra email, tạo code, etc.)
                         */
                        if (!$hasOrganization) {
                            /**
                             * Tạo organization mới cho user (tương tự đăng ký bình thường)
                             * 
                             * Sử dụng database transaction để đảm bảo tính nhất quán
                             */
                            DB::beginTransaction();
                            try {
                                /**
                                 * Kiểm tra email đã tồn tại trong organizations chưa
                                 */
                                $existingOrg = Organization::where('email', $googleUser->email)
                                    ->whereNull('deleted_at')
                                    ->first();
                                
                                if ($existingOrg) {
                                    throw new \Exception('Email này đã được sử dụng bởi một tổ chức khác.');
                                }
                                
                                /**
                                 * Tạo organization code duy nhất (tương tự như trên)
                                 */
                                $organizationCode = 'ORG_' . Carbon::now()->format('YmdHis') . '_' . substr(md5(uniqid(rand(), true)), 0, 6);
                                
                                /**
                                 * Đảm bảo code là duy nhất (tối đa 5 lần thử)
                                 */
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
                                
                                /**
                                 * Tạo số điện thoại mặc định
                                 */
                                $phone = '0' . substr(Carbon::now()->timestamp, -9);
                                
                                /**
                                 * Tạo organization mới
                                 */
                                $organization = Organization::create([
                                    'code' => $organizationCode,
                                    'name' => $googleUser->name,
                                    'email' => $googleUser->email,
                                    'phone' => $phone,
                                    'status' => true,
                                ]);

                                /**
                                 * Gán subscription plan mặc định
                                 */
                                $this->assignDefaultSubscriptionPlan($organization);

                                /**
                                 * Gán user vào organization với vai trò manager
                                 */
                                $managerRoleId = DB::table('roles')->where('key_code', 'manager')->value('id');
                                
                                if ($managerRoleId) {
                                    OrganizationUser::create([
                                        'user_id' => $existingUser->id,
                                        'organization_id' => $organization->id,
                                        'role_id' => $managerRoleId,
                                        'status' => 'active',
                                    ]);
                                } else {
                                    Log::warning('Manager role not found when creating organization for existing verified Google user', [
                                        'user_id' => $existingUser->id,
                                        'organization_id' => $organization->id,
                                    ]);
                                }

                                /**
                                 * Commit transaction
                                 */
                                DB::commit();

                                /**
                                 * Log thông tin organization đã tạo
                                 */
                                Log::info('New organization created for existing verified Google user', [
                                    'user_id' => $existingUser->id,
                                    'organization_id' => $organization->id,
                                    'organization_code' => $organizationCode,
                                    'organization_name' => $googleUser->name,
                                ]);

                            } catch (\Exception $e) {
                                /**
                                 * Xử lý lỗi: Rollback transaction
                                 * 
                                 * Không throw exception: User vẫn có thể login
                                 */
                                DB::rollBack();
                                Log::error('Error creating organization for existing verified Google user: ' . $e->getMessage(), [
                                    'user_id' => $existingUser->id,
                                    'email' => $googleUser->email,
                                    'error' => $e->getMessage(),
                                    'trace' => $e->getTraceAsString()
                                ]);
                            }
                        }
                        
                        /**
                         * Set user để login sau
                         */
                        $user = $existingUser;
                        
                        /**
                         * Log thông tin update Google ID thành công
                         */
                        Log::info('Google login: Updated existing verified user with Google ID', [
                            'user_id' => $user->id,
                            'email' => $googleUser->email,
                            'has_organization' => $hasOrganization,
                        ]);
                    } else {
                        /**
                         * Trường hợp 2: User đã tồn tại nhưng email chưa được verify
                         * 
                         * - Verify email (email_verified_at = now())
                         * - Update Google ID
                         * - Update profile với thông tin từ Google
                         * - Clean up pending OTPs (không cần nữa vì email đã verify)
                         * - Tạo organization nếu chưa có
                         * - Login user
                         */
                        
                        /**
                         * Update Google ID và verify email
                         * 
                         * Google users được auto-verify email vì Google đã verify email cho user
                         */
                        $existingUser->google_id = $googleUser->id;
                        $existingUser->email_verified_at = now(); // Verify email cho Google users
                        $existingUser->save();
                        
                        /**
                         * Update hoặc tạo user profile với thông tin từ Google
                         */
                        $profile = $existingUser->getOrCreateProfile();
                        $profile->update([
                            'full_name' => $googleUser->name,
                            'avatar' => $googleUser->avatar,
                        ]);
                        
                        /**
                         * Clean up các OTPs đang pending vì email đã được verify
                         * 
                         * Không cần OTPs nữa vì email đã được Google verify
                         */
                        DB::table('email_otps')->where('user_id', $existingUser->id)->delete();
                        
                        /**
                         * Kiểm tra user đã có organization chưa
                         */
                        $hasOrganization = $existingUser->organizations()->exists();
                        
                        /**
                         * Nếu user chưa có organization: Tạo organization mới
                         * 
                         * Logic tạo organization tương tự như trên
                         */
                        if (!$hasOrganization) {
                            /**
                             * Tạo organization mới cho user (tương tự đăng ký bình thường)
                             */
                            DB::beginTransaction();
                            try {
                                /**
                                 * Kiểm tra email đã tồn tại trong organizations chưa
                                 */
                                $existingOrg = Organization::where('email', $googleUser->email)
                                    ->whereNull('deleted_at')
                                    ->first();
                                
                                if ($existingOrg) {
                                    throw new \Exception('Email này đã được sử dụng bởi một tổ chức khác.');
                                }
                                
                                /**
                                 * Tạo organization code duy nhất
                                 */
                                $organizationCode = 'ORG_' . Carbon::now()->format('YmdHis') . '_' . substr(md5(uniqid(rand(), true)), 0, 6);
                                
                                /**
                                 * Đảm bảo code là duy nhất
                                 */
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
                                
                                /**
                                 * Tạo số điện thoại mặc định
                                 */
                                $phone = '0' . substr(Carbon::now()->timestamp, -9);
                                
                                /**
                                 * Tạo organization mới
                                 */
                                $organization = Organization::create([
                                    'code' => $organizationCode,
                                    'name' => $googleUser->name,
                                    'email' => $googleUser->email,
                                    'phone' => $phone,
                                    'status' => true,
                                ]);

                                /**
                                 * Gán subscription plan mặc định
                                 */
                                $this->assignDefaultSubscriptionPlan($organization);

                                /**
                                 * Gán user vào organization với vai trò manager
                                 */
                                $managerRoleId = DB::table('roles')->where('key_code', 'manager')->value('id');
                                
                                if ($managerRoleId) {
                                    // Kiểm tra xem user đã có trong organization chưa
                                    $existingOrgUser = OrganizationUser::where('organization_id', $organization->id)
                                        ->where('user_id', $existingUser->id)
                                        ->whereNull('deleted_at')
                                        ->first();
                                    
                                    if (!$existingOrgUser) {
                                        OrganizationUser::create([
                                            'user_id' => $existingUser->id,
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
                                    Log::warning('Manager role not found when creating organization for existing Google user', [
                                        'user_id' => $existingUser->id,
                                        'organization_id' => $organization->id,
                                    ]);
                                }

                                /**
                                 * Commit transaction
                                 */
                                DB::commit();

                                /**
                                 * Log thông tin organization đã tạo
                                 */
                                Log::info('New organization created for existing Google user (after verification)', [
                                    'user_id' => $existingUser->id,
                                    'organization_id' => $organization->id,
                                    'organization_code' => $organizationCode,
                                    'organization_name' => $googleUser->name,
                                ]);

                            } catch (\Exception $e) {
                                /**
                                 * Xử lý lỗi: Rollback transaction
                                 * 
                                 * Không throw exception: User vẫn có thể login
                                 */
                                DB::rollBack();
                                Log::error('Error creating organization for existing Google user: ' . $e->getMessage(), [
                                    'user_id' => $existingUser->id,
                                    'email' => $googleUser->email,
                                    'error' => $e->getMessage(),
                                    'trace' => $e->getTraceAsString()
                                ]);
                            }
                        }
                        
                        /**
                         * Set user để login sau
                         */
                        $user = $existingUser;
                        
                        /**
                         * Log thông tin verify email và update Google ID thành công
                         */
                        Log::info('Google login: Verified existing unverified user and updated with Google ID', [
                            'user_id' => $user->id,
                            'email' => $googleUser->email,
                            'has_organization' => $hasOrganization,
                        ]);
                    }
                } else {
                    /**
                     * Trường hợp 3: User chưa tồn tại (email và Google ID đều chưa có)
                     * 
                     * - Tạo user mới với Google ID
                     * - Auto-verify email (email_verified_at = now())
                     * - Tạo user profile với thông tin từ Google
                     * - Tạo organization mới
                     * - Gán subscription plan mặc định
                     * - Gán role manager
                     * - Login user
                     */
                    
                    /**
                     * Tạo user mới với thông tin từ Google
                     * 
                     * - email: Email từ Google
                     * - google_id: Google user ID
                     * - status: 1 (active)
                     * - email_verified_at: now() (auto-verify email cho Google users)
                     */
                    $user = User::create([
                        'email' => $googleUser->email,
                        'google_id' => $googleUser->id,
                        'status' => 1,
                        'email_verified_at' => now(), // Auto-verify email cho Google users
                    ]);
                    
                    /**
                     * Tạo user profile với thông tin từ Google
                     * 
                     * - full_name: Tên từ Google
                     * - avatar: Avatar URL từ Google
                     */
                    UserProfile::create([
                        'user_id' => $user->id,
                        'full_name' => $googleUser->name,
                        'avatar' => $googleUser->avatar,
                    ]);
                    
                    /**
                     * Tạo organization mới cho user đăng ký với Google
                     * 
                     * Sử dụng database transaction để đảm bảo tính nhất quán
                     * Nếu tạo organization thất bại: Xóa user và profile
                     */
                    DB::beginTransaction();
                    try {
                        /**
                         * Kiểm tra email đã tồn tại trong organizations chưa
                         */
                        $existingOrg = Organization::where('email', $googleUser->email)
                            ->whereNull('deleted_at')
                            ->first();
                        
                        if ($existingOrg) {
                            throw new \Exception('Email này đã được sử dụng bởi một tổ chức khác.');
                        }
                        
                        /**
                         * Tạo organization code duy nhất
                         * 
                         * Format: ORG_YYYYMMDDHHmmss_random6chars
                         */
                        $organizationCode = 'ORG_' . Carbon::now()->format('YmdHis') . '_' . substr(md5(uniqid(rand(), true)), 0, 6);
                        
                        /**
                         * Đảm bảo code là duy nhất (tối đa 5 lần thử)
                         */
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
                        
                        /**
                         * Tạo số điện thoại mặc định
                         */
                        $phone = '0' . substr(Carbon::now()->timestamp, -9);
                        
                        /**
                         * Tạo organization mới
                         */
                        $organization = Organization::create([
                            'code' => $organizationCode,
                            'name' => $googleUser->name,
                            'email' => $googleUser->email,
                            'phone' => $phone,
                            'status' => true,
                        ]);

                        /**
                         * Gán subscription plan mặc định (FREE plan)
                         */
                        $this->assignDefaultSubscriptionPlan($organization);

                        /**
                         * Gán user vào organization với vai trò manager
                         */
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
                            Log::warning('Manager role not found when creating organization for new Google user', [
                                'user_id' => $user->id,
                                'organization_id' => $organization->id,
                            ]);
                        }

                        /**
                         * Commit transaction
                         */
                        DB::commit();

                        /**
                         * Log thông tin organization đã tạo
                         */
                        Log::info('New organization created for Google registered user', [
                            'user_id' => $user->id,
                            'organization_id' => $organization->id,
                            'organization_code' => $organizationCode,
                            'organization_name' => $googleUser->name,
                        ]);

                    } catch (\Exception $e) {
                        /**
                         * Xử lý lỗi khi tạo organization: Rollback transaction
                         */
                        DB::rollBack();
                        
                        /**
                         * Log error
                         */
                        Log::error('Error creating organization for new Google user: ' . $e->getMessage(), [
                            'user_id' => $user->id,
                            'email' => $googleUser->email,
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
                            Log::error('Error cleaning up user after organization creation failure: ' . $deleteException->getMessage());
                        }
                        
                        /**
                         * Throw exception để hiển thị lỗi cho user
                         */
                        throw new Exception('Có lỗi xảy ra khi tạo tổ chức. Vui lòng thử lại.');
                    }
                }
                
                /**
                 * Login user sau khi xử lý tất cả các trường hợp
                 */
                Auth::login($user);
            }
            
            /**
             * Lưu role và organization vào session
             * 
             * storeSessionData() sẽ:
             * - Xác định role chính của user
             * - Lưu role_id, role_key vào session
             * - Lưu organization_id, organization_name vào session
             * - Regenerate session
             */
            $this->storeSessionData($user);
            
            /**
             * Redirect đến dashboard tương ứng với role
             * 
             * Mapping role key -> route:
             * - admin: superadmin.dashboard
             * - manager: staff.dashboard (unified staff dashboard)
             * - agent: staff.dashboard (unified staff dashboard)
             * - landlord: landlord.dashboard
             * - tenant: tenant.dashboard
             * - default: dashboard (nếu không match)
             */
            $role = $this->resolvePrimaryRole($user);
            $roleKey = $role['key_code'] ?? null;
            $routeByRole = [
                'admin' => 'superadmin.dashboard',
            'manager' => 'staff.dashboard', // Unified staff dashboard
            'agent' => 'staff.dashboard', // Unified staff dashboard
                'landlord' => 'landlord.dashboard',
                'tenant' => 'tenant.dashboard',
            ];
            $target = $routeByRole[$roleKey] ?? 'dashboard';
            
            /**
             * Redirect đến dashboard tương ứng
             */
            return redirect()->route($target);
            
        } catch (Exception $e) {
            /**
             * Xử lý exception: Log error và redirect về trang login với error message
             * 
             * Tất cả errors đều được log để debug
             */
            Log::error('Google OAuth error: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('login')
                ->withErrors(['email' => 'Có lỗi xảy ra khi đăng nhập với Google. Vui lòng thử lại.']);
        }
    }
    
    /**
     * Lưu role và organization vào session
     * 
     * LUỒNG XỬ LÝ:
     * 1. Xác định role chính của user
     * 2. Lưu role_id và role_key vào session
     * 3. Lấy organization đầu tiên của user
     * 4. Lưu organization_id và organization_name vào session
     * 5. Regenerate session để tránh session fixation
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - User: Lấy organizations
     * - resolvePrimaryRole(): Lấy role chính
     * 
     * DỮ LIỆU GHI VÀO:
     * - Session: auth_role_id, auth_role_key, auth_organization_id, auth_organization_name
     * 
     * SECURITY:
     * - Session regeneration để tránh session fixation attack
     * 
     * @param User $user User cần lưu session data
     * @return void
     */
    private function storeSessionData(User $user)
    {
        /**
         * Xác định role chính của user
         */
        $role = $this->resolvePrimaryRole($user);
        
        /**
         * Lưu role vào session nếu có
         */
        if ($role) {
            session()->put('auth_role_id', $role['id']);
            session()->put('auth_role_key', $role['key_code']);
        }
        
        /**
         * Lưu thông tin organization vào session
         * 
         * Lấy organization đầu tiên của user (user có thể có nhiều organizations)
         */
        try {
            $organization = $user->organizations()->first();
            if ($organization) {
                session()->put('auth_organization_id', $organization->id);
                session()->put('auth_organization_name', $organization->name);
            }
        } catch (Exception $e) {
            /**
             * User có thể không có organizations
             * Không cần xử lý lỗi, chỉ cần bỏ qua
             */
        }
        
        /**
         * Regenerate session để tránh session fixation attack
         */
        session()->regenerate();
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
                 * - Đăng ký mới từ Google → active luôn gói FREE
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
                Log::info('Default subscription plan assigned to new organization (Google user, active, no trial)', [
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
                Log::warning('No active subscription plan found to assign to new organization (Google user)', [
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
            Log::error('Error assigning default subscription plan to organization (Google user): ' . $e->getMessage(), [
                'organization_id' => $organization->id,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}

