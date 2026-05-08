<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\OtpService;
use App\Services\Subscription\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * POST /api/auth/register
     * Đăng ký tài khoản mới, trả về JSON.
     */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'max:255'],
            'password'  => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Kiểm tra email tồn tại
        $existingUser = User::where('email', $data['email'])->whereNull('deleted_at')->first();

        if ($existingUser) {
            if ($existingUser->email_verified_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email này đã được sử dụng.',
                    'errors'  => ['email' => ['Email này đã được sử dụng. Vui lòng đăng nhập.']],
                ], 422);
            }

            // Xóa user chưa verify nếu không có dữ liệu liên quan
            $hasBlocking = DB::table('leases')->where('tenant_id', $existingUser->id)->whereNull('deleted_at')->exists();
            if ($hasBlocking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email đã tồn tại và không thể xóa do có dữ liệu liên quan.',
                    'errors'  => ['email' => ['Vui lòng sử dụng email khác hoặc liên hệ hỗ trợ.']],
                ], 422);
            }

            DB::table('organization_users')->where('user_id', $existingUser->id)->delete();
            DB::table('email_otps')->where('user_id', $existingUser->id)->delete();
            $existingUser->forceDelete();
        }

        // Tạo user
        $user = new User();
        $user->email         = $data['email'];
        $user->password_hash = Hash::make($data['password']);
        $user->status        = 1;
        $user->save();

        \App\Models\UserProfile::create([
            'user_id'   => $user->id,
            'full_name' => $data['full_name'],
        ]);

        // Tạo organization
        DB::beginTransaction();
        try {
            $existingOrg = Organization::where('email', $data['email'])->whereNull('deleted_at')->first();
            if ($existingOrg) {
                throw new \Exception('Email này đã được sử dụng bởi một tổ chức khác.');
            }

            $code = 'ORG_' . Carbon::now()->format('YmdHis') . '_' . substr(md5(uniqid(rand(), true)), 0, 6);
            while (Organization::where('code', $code)->exists()) {
                $code = 'ORG_' . Carbon::now()->format('YmdHis') . '_' . substr(md5(uniqid(rand(), true)), 0, 6);
            }

            $phone = $request->input('phone') ?: '0' . substr(Carbon::now()->timestamp, -9);

            $organization = Organization::create([
                'code'   => $code,
                'name'   => $data['full_name'],
                'email'  => $data['email'],
                'phone'  => $phone,
                'status' => true,
            ]);

            $this->assignDefaultSubscriptionPlan($organization);

            $managerRoleId = DB::table('roles')->where('key_code', 'manager')->value('id');
            if ($managerRoleId) {
                OrganizationUser::create([
                    'user_id'         => $user->id,
                    'organization_id' => $organization->id,
                    'role_id'         => $managerRoleId,
                    'status'          => 'active',
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            try {
                $user->userProfile?->delete();
                $user->delete();
            } catch (\Exception) {}

            Log::error('API register - org creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tạo tổ chức. Vui lòng thử lại.',
            ], 500);
        }

        // Gửi OTP
        $otpService = app(OtpService::class);
        $otpSent    = $otpService->sendEmailVerificationOtp($user, $user->email);

        if (! $otpSent) {
            try {
                $user->userProfile?->delete();
                $user->delete();
            } catch (\Exception) {}

            return response()->json([
                'success' => false,
                'message' => 'Không thể gửi email xác thực. Vui lòng thử lại.',
            ], 500);
        }

        return response()->json([
            'success'           => true,
            'message'           => 'Tài khoản đã được tạo. Vui lòng kiểm tra email và nhập mã OTP.',
            'user_id'           => $user->id,
            'email'             => $user->email,
            'requires_otp'      => true,
        ], 201);
    }

    /**
     * POST /api/auth/verify-otp
     * Xác thực OTP sau đăng ký, trả về token.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer'],
            'otp'     => ['required', 'string', 'size:6'],
        ]);

        $user = User::find($data['user_id']);
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy tài khoản.'], 404);
        }

        if ($user->email_verified_at) {
            return response()->json(['success' => false, 'message' => 'Email đã được xác thực rồi.'], 422);
        }

        $otpService = app(OtpService::class);
        $verified   = $otpService->verifyEmailOtp($user, $data['otp']);

        if (! $verified) {
            return response()->json(['success' => false, 'message' => 'Mã OTP không đúng hoặc đã hết hạn.'], 422);
        }

        $user->email_verified_at = now();
        $user->save();

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Xác thực email thành công.',
            'token'   => $token,
            'user'    => [
                'id'         => $user->id,
                'email'      => $user->email,
                'full_name'  => $user->userProfile?->full_name,
            ],
        ]);
    }

    /**
     * POST /api/auth/login
     * Đăng nhập, trả về token.
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'Thông tin đăng nhập không đúng hoặc tài khoản bị khóa.',
                'errors'  => ['email' => ['Thông tin đăng nhập không đúng hoặc tài khoản bị khóa.']],
            ], 401);
        }

        if (! $user->email_verified_at) {
            return response()->json([
                'success'      => false,
                'message'      => 'Email chưa được xác thực. Vui lòng kiểm tra hộp thư và nhập mã OTP.',
                'requires_otp' => true,
                'user_id'      => $user->id,
            ], 403);
        }

        // Lấy role
        $role = DB::table('organization_users')
            ->join('roles', 'roles.id', '=', 'organization_users.role_id')
            ->where('organization_users.user_id', $user->id)
            ->where('organization_users.status', 'active')
            ->whereNull('organization_users.deleted_at')
            ->select('roles.id as role_id', 'roles.key_code', 'organization_users.organization_id')
            ->orderByRaw("CASE WHEN roles.key_code = 'admin' THEN 0 ELSE 1 END")
            ->first();

        // Xóa token cũ và tạo token mới
        $user->tokens()->delete();
        $token = $user->createToken('api-token', ['*'], now()->addDays(30))->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Đăng nhập thành công.',
            'token'   => $token,
            'user'    => [
                'id'              => $user->id,
                'email'           => $user->email,
                'full_name'       => $user->userProfile?->full_name,
                'role'            => $role?->key_code,
                'organization_id' => $role?->organization_id,
            ],
        ]);
    }

    /**
     * POST /api/auth/logout
     * Thu hồi token hiện tại.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đăng xuất thành công.',
        ]);
    }

    /**
     * POST /api/auth/resend-otp
     * Gửi lại OTP xác thực email.
     */
    public function resendOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer'],
        ]);

        $user = User::find($data['user_id']);
        if (! $user || $user->email_verified_at) {
            return response()->json(['success' => false, 'message' => 'Không hợp lệ.'], 422);
        }

        $otpService = app(OtpService::class);
        $sent       = $otpService->sendEmailVerificationOtp($user, $user->email);

        if (! $sent) {
            return response()->json(['success' => false, 'message' => 'Không thể gửi OTP. Vui lòng thử lại.'], 500);
        }

        return response()->json(['success' => true, 'message' => 'OTP đã được gửi lại.']);
    }

    /**
     * GET /api/auth/me (yêu cầu token)
     * Thông tin user hiện tại.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        $role = DB::table('organization_users')
            ->join('roles', 'roles.id', '=', 'organization_users.role_id')
            ->where('organization_users.user_id', $user->id)
            ->where('organization_users.status', 'active')
            ->whereNull('organization_users.deleted_at')
            ->select('roles.key_code', 'organization_users.organization_id')
            ->orderByRaw("CASE WHEN roles.key_code = 'admin' THEN 0 ELSE 1 END")
            ->first();

        return response()->json([
            'success' => true,
            'user'    => [
                'id'              => $user->id,
                'email'           => $user->email,
                'full_name'       => $user->userProfile?->full_name,
                'role'            => $role?->key_code,
                'organization_id' => $role?->organization_id,
                'email_verified'  => (bool) $user->email_verified_at,
            ],
        ]);
    }

    private function assignDefaultSubscriptionPlan(Organization $organization): void
    {
        try {
            $plan = SubscriptionPlan::where('code', 'FREE')->where('is_active', true)->first()
                ?? SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->first();

            if ($plan) {
                (new SubscriptionService())->assignPlan($organization, $plan, 'monthly', false, false, 'manual');
            }
        } catch (\Exception $e) {
            Log::error('API register - subscription assign failed: ' . $e->getMessage());
        }
    }
}
