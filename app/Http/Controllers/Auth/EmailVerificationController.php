<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Class: EmailVerificationController
 * 
 * MỤC ĐÍCH:
 * Controller xử lý xác thực email sau khi user đăng ký.
 * Controller này hiển thị form nhập OTP và xử lý việc verify email, sau đó login user và redirect đến dashboard.
 * 
 * LUỒNG XỬ LÝ:
 * 1. show(): Hiển thị form xác thực email
 *    - Lấy user_id từ session
 *    - Kiểm tra user tồn tại
 *    - Hiển thị form nhập OTP
 * 2. verify(): Xác thực OTP code
 *    - Validate OTP code
 *    - Verify OTP qua OtpService
 *    - Đánh dấu email đã verified
 *    - Clear session
 *    - Login user
 *    - Lưu role và organization vào session
 *    - Redirect đến dashboard tương ứng với role
 * 3. resend(): Gửi lại OTP
 *    - Lấy user_id từ session
 *    - Gọi OtpService->resendEmailVerificationOtp()
 *    - Trả về kết quả
 * 4. status(): Lấy trạng thái OTP (còn hiệu lực không)
 *    - Lấy user_id từ session
 *    - Gọi OtpService->getOtpStatus()
 *    - Trả về status
 * 5. storeSessionData(): Lưu role và organization vào session
 * 6. resolvePrimaryRole(): Xác định role chính của user
 * 
 * ENDPOINTS:
 * - GET /auth/email-verification: Hiển thị form xác thực email
 * - POST /auth/email-verification: Xác thực OTP code
 * - POST /auth/email-verification/resend: Gửi lại OTP
 * - GET /auth/email-verification/status: Lấy trạng thái OTP
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Session: pending_email_verification_user_id
 * - Request: otp_code
 * - Bảng users: Tìm user theo ID
 * - Bảng email_otps: Verify OTP
 * - Bảng organization_users: Lấy roles của user
 * - Bảng organizations: Lấy organization của user
 * 
 * DỮ LIỆU GHI VÀO:
 * - Bảng users: Cập nhật email_verified_at (qua markEmailAsVerified())
 * - Bảng email_otps: Cập nhật is_used = true, verified_at (qua OtpService)
 * - Session: auth_role_id, auth_role_key, auth_organization_id, auth_organization_name
 * - Session: Clear pending_email_verification_user_id
 * 
 * SERVICE SỬ DỤNG:
 * - OtpService: Xử lý OTP logic (verify, resend, get status)
 * 
 * FLOW:
 * 1. User đăng ký → EmailAuthController->register() → Gửi OTP → Lưu user_id vào session
 * 2. User vào trang email verification → show() → Hiển thị form
 * 3. User nhập OTP → verify() → Verify OTP → Login user → Redirect đến dashboard
 * 
 * LƯU Ý:
 * - OTP được gửi từ EmailAuthController->register() hoặc resend()
 * - OTP expiry time: 2 phút (theo OtpService)
 * - Sau khi verify thành công: User được login tự động và redirect đến dashboard
 * - Session chứa thông tin role và organization để access control
 */
class EmailVerificationController extends Controller
{
    /**
     * OtpService instance
     * 
     * Service này xử lý logic OTP (verify, resend, get status)
     */
    protected $otpService;

    /**
     * Constructor: Inject OtpService
     * 
     * @param OtpService $otpService Service xử lý OTP
     */
    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Hiển thị form xác thực email
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy user_id từ session (pending_email_verification_user_id)
     * 2. Nếu không có user_id: Redirect về trang đăng ký với lỗi
     * 3. Tìm user theo ID
     * 4. Nếu không tìm thấy user: Redirect về trang đăng ký với lỗi
     * 5. Hiển thị form xác thực email với thông tin user
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Session: pending_email_verification_user_id
     * - Bảng users: Tìm user theo ID
     * 
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse View form xác thực email hoặc redirect với lỗi
     */
    public function show()
    {
        /**
         * Lấy user_id từ session
         * 
         * Session key: pending_email_verification_user_id
         * Được set từ EmailAuthController->register() sau khi gửi OTP
         */
        $userId = session('pending_email_verification_user_id');
        
        /**
         * Nếu không có user_id: Session đã hết hạn
         * Redirect về trang đăng ký với lỗi
         */
        if (!$userId) {
            return redirect()->route('register')
                ->withErrors(['error' => 'Phiên xác thực email đã hết hạn. Vui lòng đăng ký lại.']);
        }

        /**
         * Tìm user theo ID
         * 
         * Nếu không tìm thấy: Redirect về trang đăng ký với lỗi
         */
        $user = User::find($userId);
        if (!$user) {
            return redirect()->route('register')
                ->withErrors(['error' => 'Tài khoản không tồn tại. Vui lòng đăng ký lại.']);
        }

        /**
         * Hiển thị form xác thực email với thông tin user
         */
        return view('auth.email-verification', compact('user'));
    }

    /**
     * Xác thực OTP code
     * 
     * LUỒNG XỬ LÝ:
     * 1. Validate OTP code (6 chữ số)
     * 2. Lấy user_id từ session
     * 3. Tìm user theo ID
     * 4. Verify OTP qua OtpService
     * 5. Nếu verify thành công:
     *    - Đánh dấu email đã verified (markEmailAsVerified())
     *    - Clear session
     *    - Login user
     *    - Regenerate session
     *    - Lưu role và organization vào session
     *    - Xác định redirect URL theo role
     *    - Trả về response thành công với redirect URL
     * 6. Nếu verify thất bại: Trả về lỗi
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Request: otp_code
     * - Session: pending_email_verification_user_id
     * - Bảng users: Tìm user theo ID
     * - Bảng email_otps: Verify OTP
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng users: Cập nhật email_verified_at (qua markEmailAsVerified())
     * - Bảng email_otps: Cập nhật is_used = true, verified_at (qua OtpService)
     * - Session: auth_role_id, auth_role_key, auth_organization_id, auth_organization_name
     * - Session: Clear pending_email_verification_user_id
     * 
     * VALIDATION:
     * - otp_code: required, string, size 6
     * 
     * SECURITY:
     * - Session regeneration sau khi login để tránh session fixation attack
     * 
     * @param Request $request HTTP request chứa otp_code
     * @return \Illuminate\Http\JsonResponse JSON response với kết quả verify OTP
     */
    public function verify(Request $request)
    {
        /**
         * Validate OTP code từ request
         * 
         * - otp_code: Bắt buộc, string, phải có 6 chữ số
         */
        $validator = Validator::make($request->all(), [
            'otp_code' => 'required|string|size:6',
        ], [
            'otp_code.required' => 'Mã OTP là bắt buộc.',
            'otp_code.size' => 'Mã OTP phải có 6 chữ số.',
        ]);

        /**
         * Nếu validation thất bại: Trả về 422 với errors
         */
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $validator->errors()
            ], 422);
        }

        /**
         * Lấy user_id từ session
         * 
         * Nếu không có: Session đã hết hạn
         */
        $userId = session('pending_email_verification_user_id');
        
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Phiên xác thực email đã hết hạn. Vui lòng đăng ký lại.'
            ], 400);
        }

        /**
         * Tìm user theo ID
         * 
         * Nếu không tìm thấy: Trả về 400
         */
        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản không tồn tại. Vui lòng đăng ký lại.'
            ], 400);
        }

        /**
         * Verify OTP qua OtpService
         * 
         * OtpService->verifyOtp() sẽ:
         * - Tìm OTP record với user_id, otp_code, type = 'email_verification'
         * - Kiểm tra OTP chưa expired (expires_at > now)
         * - Kiểm tra OTP chưa sử dụng (is_used = false)
         * - Cập nhật is_used = true, verified_at = now()
         * - Trả về ['success' => bool, 'message' => string]
         */
        $result = $this->otpService->verifyOtp($user->id, $request->otp_code, 'email_verification');

        /**
         * Nếu verify thành công: Đánh dấu email verified, login user và redirect
         */
        if ($result['success']) {
            /**
             * Đánh dấu email đã được verified
             * 
             * markEmailAsVerified() sẽ:
             * - Cập nhật email_verified_at = now()
             * - Lưu vào database
             */
            $user->markEmailAsVerified();
            
            /**
             * Clear session
             * 
             * Xóa pending_email_verification_user_id vì đã verify xong
             */
            session()->forget('pending_email_verification_user_id');
            
            /**
             * Login user
             * 
             * Auth::login() sẽ:
             * - Set user làm authenticated user
             * - Lưu user vào session
             */
            Auth::login($user);
            
            /**
             * Regenerate session để tránh session fixation attack
             */
            $request->session()->regenerate();

            /**
             * Lưu role và organization vào session
             * 
             * storeSessionData() sẽ:
             * - Xác định role chính của user
             * - Lưu role_id, role_key vào session
             * - Lưu organization_id, organization_name vào session
             */
            $this->storeSessionData($user);

            /**
             * Xác định redirect URL theo role
             * 
             * Mapping role key -> route:
             * - admin: superadmin.dashboard
             * - manager: staff.dashboard
             * - agent: staff.dashboard
             * - landlord: landlord.dashboard
             * - tenant: tenant.dashboard
             * - default: dashboard (nếu không match)
             */
            $role = $this->resolvePrimaryRole($user);
            $roleKey = $role['key_code'] ?? null;
            $routeByRole = [
                'admin' => 'superadmin.dashboard',
                'manager' => 'staff.dashboard',
                'agent' => 'staff.dashboard',
                'landlord' => 'landlord.dashboard',
                'tenant' => 'tenant.dashboard',
            ];
            $targetRoute = $routeByRole[$roleKey] ?? 'dashboard';
            $redirectUrl = route($targetRoute);

            /**
             * Trả về response thành công với redirect URL
             */
            return response()->json([
                'success' => true,
                'message' => 'Xác thực email thành công! Đang chuyển hướng...',
                'redirect_url' => $redirectUrl
            ]);
        } else {
            /**
             * Nếu verify thất bại: Trả về lỗi
             */
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);
        }
    }

    /**
     * Gửi lại OTP xác thực email
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy user_id từ session
     * 2. Kiểm tra user_id có tồn tại không
     * 3. Tìm user theo ID
     * 4. Gọi OtpService->resendEmailVerificationOtp() để gửi lại OTP
     * 5. Trả về kết quả
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Session: pending_email_verification_user_id
     * - Bảng users: Tìm user theo ID
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng email_otps: Tạo OTP mới
     * - Email: Gửi OTP code mới qua email
     * 
     * MỤC ĐÍCH:
     * - Cho phép user yêu cầu gửi lại OTP nếu không nhận được hoặc đã hết hạn
     * 
     * @param Request $request HTTP request
     * @return \Illuminate\Http\JsonResponse JSON response với kết quả gửi lại OTP
     */
    public function resend(Request $request)
    {
        /**
         * Lấy user_id từ session
         * 
         * Nếu không có: Session đã hết hạn
         */
        $userId = session('pending_email_verification_user_id');
        
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Phiên xác thực email đã hết hạn. Vui lòng đăng ký lại.'
            ], 400);
        }

        /**
         * Tìm user theo ID
         * 
         * Nếu không tìm thấy: Trả về 400
         */
        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản không tồn tại. Vui lòng đăng ký lại.'
            ], 400);
        }

        /**
         * Gửi lại OTP qua OtpService
         * 
         * OtpService->resendEmailVerificationOtp() sẽ:
         * - Tạo OTP mới (6 chữ số)
         * - Lưu vào bảng email_otps với type = 'email_verification'
         * - Set expires_at = now() + 2 phút
         * - Gửi email chứa OTP code
         * - Trả về ['success' => bool, 'message' => string]
         */
        $result = $this->otpService->resendEmailVerificationOtp($user, $user->email);

        /**
         * Trả về kết quả
         * 
         * - 200 nếu thành công
         * - 400 nếu thất bại
         */
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Lấy trạng thái OTP (còn hiệu lực không)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy user_id từ session
     * 2. Nếu không có user_id: Trả về has_valid_otp = false
     * 3. Gọi OtpService->getOtpStatus() để lấy trạng thái OTP
     * 4. Trả về status
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Session: pending_email_verification_user_id
     * - OtpService: Lấy trạng thái OTP (còn hiệu lực, thời gian hết hạn, etc.)
     * 
     * DỮ LIỆU TRẢ VỀ:
     * - has_valid_otp: boolean (còn OTP hợp lệ không)
     * - expires_at: datetime (thời gian hết hạn)
     * - message: string (thông báo)
     * 
     * MỤC ĐÍCH:
     * - Cho phép frontend kiểm tra OTP còn hiệu lực không
     * - Hiển thị countdown timer cho user
     * - Tự động redirect nếu OTP hết hạn
     * 
     * @return \Illuminate\Http\JsonResponse JSON response với trạng thái OTP
     */
    public function status()
    {
        /**
         * Lấy user_id từ session
         * 
         * Nếu không có: Session đã hết hạn
         */
        $userId = session('pending_email_verification_user_id');
        
        /**
         * Nếu không có user_id: Trả về has_valid_otp = false
         */
        if (!$userId) {
            return response()->json([
                'has_valid_otp' => false,
                'message' => 'Phiên xác thực email đã hết hạn.'
            ]);
        }

        /**
         * Lấy trạng thái OTP qua OtpService
         * 
         * OtpService->getOtpStatus() sẽ:
         * - Tìm OTP chưa expired và chưa sử dụng
         * - Trả về has_valid_otp, expires_at, message, etc.
         */
        $status = $this->otpService->getOtpStatus($userId, 'email_verification');
        
        /**
         * Trả về status
         */
        return response()->json($status);
    }

    /**
     * Lưu role và organization vào session
     * 
     * LUỒNG XỬ LÝ:
     * 1. Xác định role chính của user
     * 2. Lưu role_id và role_key vào session
     * 3. Lấy organization đầu tiên của user
     * 4. Lưu organization_id và organization_name vào session
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - User: Lấy organizations
     * - resolvePrimaryRole(): Lấy role chính
     * 
     * DỮ LIỆU GHI VÀO:
     * - Session: auth_role_id, auth_role_key, auth_organization_id, auth_organization_name
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
        } catch (\Exception $e) {
            /**
             * User có thể không có organizations
             * Không cần xử lý lỗi, chỉ cần bỏ qua
             */
        }
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
         */
        $records = DB::table('organization_users')
            ->join('roles', 'roles.id', '=', 'organization_users.role_id')
            ->where('organization_users.user_id', $user->id)
            ->where('organization_users.status', 'active')
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
}