<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\SepayBank;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Controller quản lý Profile của Staff (Manager và Agent)
 * 
 * MỤC ĐÍCH:
 * - Cho phép staff xem và cập nhật thông tin profile của chính họ
 * - Quản lý thông tin cơ bản: full_name, email, phone, password
 * - Quản lý thông tin KYC: dob, gender, id_number, id_issued_at, id_card_place, address, tax_code, note
 * - Quản lý thông tin banking: sepay_bank_id, account_number, account_holder_name, branch_name, branch_code, swift_code, banking_notes
 * - Hỗ trợ đổi email với OTP verification
 * 
 * LUỒNG XỬ LÝ:
 * 1. show(): Hiển thị profile hiện tại của user (chỉ đọc)
 * 2. edit(): Hiển thị form edit với danh sách banks để chọn
 * 3. update(): Cập nhật profile với validation và OTP verification cho email change
 *    - Validate tất cả fields (basic info, KYC, banking)
 *    - Kiểm tra email change: Nếu email thay đổi, cần OTP verification trước
 *    - Verify current password nếu đổi password
 *    - Update user (email, phone, password) và user_profile (KYC, banking)
 *    - Sử dụng transaction để đảm bảo data consistency
 * 4. checkEmail(): API endpoint kiểm tra email có available không (AJAX)
 * 5. sendEmailChangeOtp(): Gửi OTP đến email mới để xác thực
 *    - Kiểm tra email khác email hiện tại
 *    - Kiểm tra email chưa được sử dụng
 *    - Rate limiting: 1 phút giữa các requests
 *    - Gửi OTP với expiry 2 phút
 * 6. verifyEmailChangeOtp(): Xác thực OTP và cập nhật email ngay lập tức
 *    - Verify OTP code qua OtpService
 *    - Kiểm tra OTP email khớp với email request
 *    - Double-check email availability
 *    - Update email trong database
 * 
 * ENDPOINTS:
 * - GET /staff/profile: Hiển thị profile
 * - GET /staff/profile/edit: Hiển thị form edit
 * - POST /staff/profile: Cập nhật profile
 * - POST /staff/profile/check-email: Kiểm tra email available (AJAX)
 * - POST /staff/profile/send-email-change-otp: Gửi OTP đổi email (AJAX)
 * - POST /staff/profile/verify-email-change-otp: Xác thực OTP và cập nhật email (AJAX)
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Auth::user(): User hiện tại
 * - user->userProfile: Relationship để lấy user profile
 * - Models: SepayBank (để lấy danh sách banks và bank info)
 * - Database tables: users, user_profiles, email_otps, sepay_banks
 * 
 * DỮ LIỆU GHI VÀO:
 * - Database tables: users (email, phone, password_hash), user_profiles (KYC info, banking info)
 * - Không có thay đổi SepayBank, chỉ đọc
 * 
 * SERVICES SỬ DỤNG:
 * - OtpService: Gửi và verify OTP cho email change
 * 
 * VALIDATION:
 * - Basic info: full_name (required), email (required, email, unique), phone (nullable, unique), password (nullable, min:8, confirmed)
 * - KYC info: dob (nullable, date, before:today), gender (nullable, in:male,female,other), id_number (nullable, max:50), etc.
 * - Banking info: sepay_bank_id (nullable, exists:sepay_banks), account_number (nullable, max:50), etc.
 * 
 * SECURITY:
 * - Chỉ cho phép user cập nhật profile của chính họ (không có user_id parameter)
 * - Email change cần OTP verification để đảm bảo user sở hữu email mới
 * - Password change cần verify current password
 * - Rate limiting cho OTP requests (1 phút)
 * - Email uniqueness check (excluding soft-deleted users)
 * 
 * LƯU Ý:
 * - Email change flow: checkEmail() -> sendEmailChangeOtp() -> verifyEmailChangeOtp() -> update() (email đã được update trong verifyEmailChangeOtp)
 * - Trong update(), nếu email thay đổi, cần kiểm tra OTP đã được verify (isEmailChangeVerified)
 * - Profile data được filter để loại bỏ null/empty values (trừ full_name)
 * - Sử dụng DB::table() để update user để tránh model events
 * - Transaction được sử dụng để đảm bảo data consistency
 */
class ProfileController extends Controller
{
    /**
     * OtpService instance để gửi và verify OTP
     * 
     * @var \App\Services\OtpService
     */
    protected $otpService;

    /**
     * Constructor: Inject OtpService dependency
     * 
     * @param \App\Services\OtpService $otpService
     */
    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Hiển thị profile của user hiện tại (chỉ đọc)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy user hiện tại từ Auth
     * 2. Lấy userProfile từ relationship
     * 3. Nếu userProfile có sepay_bank_id, lấy thông tin SepayBank
     * 4. Trả về view 'staff.profile.show' với dữ liệu user, userProfile, sepayBank
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Auth::user(): User hiện tại
     * - user->userProfile: Relationship để lấy user profile
     * - SepayBank::find(): Lấy thông tin bank nếu có sepay_bank_id
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có, chỉ đọc dữ liệu
     * 
     * SECURITY:
     * - Chỉ hiển thị profile của chính user (không có user_id parameter)
     * 
     * @return \Illuminate\View\View View hiển thị profile
     */
    public function show()
    {
        // Lấy user hiện tại từ Auth
        $user = Auth::user();
        // Lấy userProfile từ relationship (có thể null nếu chưa tạo)
        $userProfile = $user->userProfile;
        
        // Lấy thông tin SepayBank nếu userProfile có sepay_bank_id
        $sepayBank = null;
        if ($userProfile && $userProfile->sepay_bank_id) {
            $sepayBank = SepayBank::find($userProfile->sepay_bank_id);
        }
        
        // Trả về view với dữ liệu user, userProfile, sepayBank
        return view('staff.profile.show', compact('user', 'userProfile', 'sepayBank'));
    }

    /**
     * Hiển thị form edit profile của user hiện tại
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy user hiện tại từ Auth
     * 2. Lấy userProfile từ relationship
     * 3. Lấy danh sách tất cả supported banks (SepayBank::supported()) để hiển thị trong dropdown
     * 4. Lấy thông tin SepayBank hiện tại nếu có sepay_bank_id
     * 5. Trả về view 'staff.profile.edit' với dữ liệu user, userProfile, banks, sepayBank
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Auth::user(): User hiện tại
     * - user->userProfile: Relationship để lấy user profile
     * - SepayBank::supported(): Lấy danh sách banks được hỗ trợ (scope supported)
     * - SepayBank::find(): Lấy thông tin bank hiện tại nếu có
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có, chỉ đọc dữ liệu
     * 
     * SECURITY:
     * - Chỉ cho phép edit profile của chính user (không có user_id parameter)
     * 
     * @return \Illuminate\View\View View hiển thị form edit
     */
    public function edit()
    {
        // Lấy user hiện tại từ Auth
        $user = Auth::user();
        // Lấy userProfile từ relationship
        $userProfile = $user->userProfile;
        
        // Lấy danh sách tất cả supported banks để hiển thị trong dropdown
        // Sử dụng scope supported() để chỉ lấy banks được hỗ trợ, sắp xếp theo name
        $banks = SepayBank::supported()->orderBy('name')->get();
        
        // Lấy thông tin SepayBank hiện tại nếu userProfile có sepay_bank_id
        $sepayBank = null;
        if ($userProfile && $userProfile->sepay_bank_id) {
            $sepayBank = SepayBank::find($userProfile->sepay_bank_id);
        }
        
        // Trả về view với dữ liệu user, userProfile, banks, sepayBank
        return view('staff.profile.edit', compact('user', 'userProfile', 'banks', 'sepayBank'));
    }

    /**
     * Cập nhật profile của user hiện tại
     * 
     * LUỒNG XỬ LÝ:
     * 1. Validate tất cả input fields (basic info, KYC, banking)
     * 2. Bắt đầu database transaction
     * 3. Reload user từ database để lấy email mới nhất (nếu đã được update qua OTP)
     * 4. Kiểm tra email change:
     *    - Nếu email thay đổi, kiểm tra OTP đã được verify (isEmailChangeVerified)
     *    - Nếu chưa verify, rollback và trả về error
     *    - Double-check email availability (excluding soft-deleted)
     * 5. Prepare user update data (email, phone, password nếu có)
     * 6. Nếu đổi password:
     *    - Verify current password nếu có
     *    - Hash password mới
     * 7. Update user trong database (sử dụng DB::table() để tránh model events)
     * 8. Reload user từ database
     * 9. Get or create user profile
     * 10. Prepare profile update data (KYC info, banking info)
     * 11. Filter profile data: Loại bỏ null/empty values (trừ full_name)
     * 12. Update profile
     * 13. Commit transaction
     * 14. Trả về JSON success response với redirect URL
     * 15. Nếu có lỗi, rollback transaction, log error và trả về error response
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Request: Tất cả input fields (full_name, email, phone, password, KYC info, banking info)
     * - Auth::user(): User hiện tại
     * - Database: users, user_profiles (để reload và check)
     * - OtpService::isEmailChangeVerified(): Kiểm tra OTP đã verify chưa
     * 
     * DỮ LIỆU GHI VÀO:
     * - Database table: users (email, phone, password_hash)
     * - Database table: user_profiles (full_name, KYC info, banking info)
     * 
     * VALIDATION:
     * - Basic info: full_name (required), email (required, email), phone (nullable, unique), password (nullable, min:8, confirmed)
     * - KYC info: dob (nullable, date, before:today), gender (nullable, in:male,female,other), etc.
     * - Banking info: sepay_bank_id (nullable, exists:sepay_banks), account_number (nullable, max:50), etc.
     * 
     * SECURITY:
     * - Chỉ cho phép update profile của chính user (không có user_id parameter)
     * - Email change cần OTP verification trước (isEmailChangeVerified)
     * - Password change cần verify current password
     * - Email uniqueness check (excluding soft-deleted users)
     * 
     * TRANSACTION:
     * - Sử dụng DB transaction để đảm bảo data consistency
     * - Rollback nếu có bất kỳ lỗi nào
     * 
     * @param \Illuminate\Http\Request $request Request chứa dữ liệu cập nhật
     * @return \Illuminate\Http\JsonResponse JSON response với success/error message
     */
    public function update(Request $request)
    {
        // Lấy user hiện tại từ Auth
        $user = Auth::user();

        try {
            // Validate tất cả input fields với custom error messages
            $request->validate([
                // Basic user info
                'full_name' => 'required|string|max:255',
                'email' => [
                    'required',
                    'email',
                    'max:255',
                ],
                'phone' => [
                    'nullable',
                    'string',
                    'max:30',
                    Rule::unique('users')->whereNull('deleted_at')->ignore($user->id)
                ],
            'current_password' => 'nullable|string',
            'password' => 'nullable|string|min:8|confirmed',
            
            // KYC profile info
            'dob' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other',
            'id_number' => 'nullable|string|max:50',
            'id_issued_at' => 'nullable|date|before_or_equal:today',
            'id_card_place' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'tax_code' => 'nullable|string|max:50',
            'note' => 'nullable|string|max:1000',
            
            // Banking information
            'sepay_bank_id' => 'nullable|exists:sepay_banks,id',
            'account_number' => 'nullable|string|max:50',
            'account_holder_name' => 'nullable|string|max:255',
            'branch_name' => 'nullable|string|max:255',
            'branch_code' => 'nullable|string|max:50',
            'swift_code' => 'nullable|string|max:50',
            'banking_notes' => 'nullable|string|max:1000',
        ], [
            // Basic validation messages
            'full_name.required' => 'Vui lòng nhập họ và tên.',
            'full_name.max' => 'Họ và tên không được vượt quá 255 ký tự.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'email.unique' => 'Email này đã được sử dụng.',
            'phone.unique' => 'Số điện thoại này đã được sử dụng.',
            'phone.max' => 'Số điện thoại không được vượt quá 30 ký tự.',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            
            // KYC validation messages
            'dob.date' => 'Ngày sinh không hợp lệ.',
            'dob.before' => 'Ngày sinh phải trước ngày hiện tại.',
            'gender.in' => 'Giới tính không hợp lệ.',
            'id_number.max' => 'Số CMND/CCCD không được vượt quá 50 ký tự.',
            'id_issued_at.date' => 'Ngày cấp CMND/CCCD không hợp lệ.',
            'id_issued_at.before_or_equal' => 'Ngày cấp CMND/CCCD không được sau ngày hiện tại.',
            'id_card_place.max' => 'Nơi cấp CMND/CCCD không được vượt quá 255 ký tự.',
            'address.max' => 'Địa chỉ không được vượt quá 500 ký tự.',
            'tax_code.max' => 'Mã số thuế không được vượt quá 50 ký tự.',
            'note.max' => 'Ghi chú không được vượt quá 1000 ký tự.',
            
            // Banking validation messages
            'sepay_bank_id.exists' => 'Ngân hàng không hợp lệ.',
            'account_number.max' => 'Số tài khoản không được vượt quá 50 ký tự.',
            'account_holder_name.max' => 'Tên chủ tài khoản không được vượt quá 255 ký tự.',
            'branch_name.max' => 'Tên chi nhánh không được vượt quá 255 ký tự.',
            'branch_code.max' => 'Mã chi nhánh không được vượt quá 50 ký tự.',
            'swift_code.max' => 'Mã SWIFT không được vượt quá 50 ký tự.',
            'banking_notes.max' => 'Ghi chú ngân hàng không được vượt quá 1000 ký tự.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ. Vui lòng kiểm tra lại thông tin.',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            // Bắt đầu database transaction để đảm bảo data consistency
            DB::beginTransaction();

            // Reload user từ database để lấy email mới nhất
            // (Email có thể đã được update qua verifyEmailChangeOtp() trước đó)
            $user = Auth::user();
            $user = \App\Models\User::find($user->id);
            
            // Kiểm tra xem email có thay đổi không (so sánh sau khi trim)
            $emailChanged = trim($request->email) !== trim($user->email);
            
            // Nếu email thay đổi, cần kiểm tra OTP đã được verify chưa
            if ($emailChanged) {
                // Kiểm tra email đã được verify qua OTP chưa (check trong database)
                // OtpService sẽ kiểm tra trong email_otps table với type = 'email_change'
                $isEmailVerified = $this->otpService->isEmailChangeVerified($user->id, $request->email);
                
                // Nếu chưa verify, rollback và trả về error
                if (!$isEmailVerified) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Email mới cần được xác thực bằng OTP trước khi cập nhật. Vui lòng gửi và xác thực OTP trước.',
                        'errors' => ['email' => ['Email mới cần được xác thực bằng OTP.']]
                    ], 422);
                }
                
                // Double-check email availability (excluding soft-deleted users)
                // Để đảm bảo email không bị sử dụng bởi user khác giữa lúc verify OTP và update
                $exists = DB::table('users')
                    ->where('email', trim($request->email))
                    ->whereNull('deleted_at')
                    ->where('id', '!=', $user->id)
                    ->exists();
                
                // Nếu email đã được sử dụng, rollback và trả về error
                if ($exists) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Email này đã được sử dụng bởi tài khoản khác.',
                        'errors' => ['email' => ['Email này đã được sử dụng.']]
                    ], 422);
                }
            }

            // Chuẩn bị dữ liệu update cho user (chỉ email và phone)
            // Password sẽ được xử lý riêng nếu có
            $updateData = [
                'email' => $request->email,
                'phone' => $request->phone,
            ];

            // Nếu có password mới, xử lý đổi password
            if ($request->filled('password')) {
                // Nếu có current_password, verify current password trước
                if ($request->filled('current_password')) {
                    // Sử dụng Hash::check() để verify password
                    if (!Hash::check($request->current_password, $user->password_hash)) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Mật khẩu hiện tại không đúng.',
                            'errors' => ['current_password' => ['Mật khẩu hiện tại không đúng.']]
                        ], 422);
                    }
                }

                // Hash password mới và thêm vào updateData
                $updateData['password_hash'] = Hash::make($request->password);
            }

            // Update user trong database sử dụng DB facade
            // Sử dụng DB::table() thay vì Eloquent để tránh model events
            DB::table('users')
                ->where('id', $user->id)
                ->update($updateData);

            // Reload user từ database sau khi update
            $user = \App\Models\User::find($user->id);

            // Lấy hoặc tạo user profile nếu chưa có
            $profile = $user->userProfile;
            if (!$profile) {
                // Tạo user profile mới nếu chưa tồn tại
                $profile = \App\Models\UserProfile::create(['user_id' => $user->id]);
            }
            
            // Chuẩn bị dữ liệu update cho profile
            // Bao gồm: KYC info (full_name, dob, gender, id_number, etc.) và banking info
            $profileData = [
                'full_name' => $request->full_name,
                'dob' => $request->dob,
                'gender' => $request->gender,
                'id_number' => $request->id_number,
                'id_issued_at' => $request->id_issued_at,
                'id_card_place' => $request->id_card_place,
                'address' => $request->address,
                'tax_code' => $request->tax_code,
                'note' => $request->note,
                // Banking information
                'sepay_bank_id' => $request->sepay_bank_id,
                'account_number' => $request->account_number,
                'account_holder_name' => $request->account_holder_name,
                'branch_name' => $request->branch_name,
                'branch_code' => $request->branch_code,
                'swift_code' => $request->swift_code,
                'banking_notes' => $request->banking_notes,
            ];

            // Loại bỏ các giá trị null/empty nhưng giữ lại full_name (required)
            // Sử dụng array_filter với ARRAY_FILTER_USE_BOTH để có thể check cả key và value
            $profileData = array_filter($profileData, function($value, $key) {
                return ($value !== null && $value !== '') || $key === 'full_name';
            }, ARRAY_FILTER_USE_BOTH);

            // Update profile với dữ liệu đã filter
            $profile->update($profileData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật thông tin thành công!',
                'redirect' => route('staff.profile.show')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error updating profile in ProfileController', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => Auth::id(),
                'request_data' => $request->except(['password', 'password_confirmation', 'current_password'])
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật thông tin. Vui lòng thử lại.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Check if email is available (not used by other users, excluding soft-deleted).
     */
    public function checkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $user = Auth::user();
        $email = $request->email;

        // Check if email is the same as current email
        if ($email === $user->email) {
            return response()->json([
                'success' => true,
                'available' => true,
                'message' => 'Email này là email hiện tại của bạn.'
            ]);
        }

        // Check if email exists and is not soft-deleted
        $exists = DB::table('users')
            ->where('email', $email)
            ->whereNull('deleted_at')
            ->where('id', '!=', $user->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => true,
                'available' => false,
                'message' => 'Email này đã được sử dụng bởi tài khoản khác.'
            ]);
        }

        return response()->json([
            'success' => true,
            'available' => true,
            'message' => 'Email có thể sử dụng.'
        ]);
    }

    /**
     * Send OTP for email change.
     */
    public function sendEmailChangeOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $user = Auth::user();
        $newEmail = $request->email;

        // Check if email is the same as current email
        if ($newEmail === $user->email) {
            return response()->json([
                'success' => false,
                'message' => 'Email mới phải khác email hiện tại.'
            ], 422);
        }

        // Check if email is available (not used by other users, excluding soft-deleted)
        $exists = DB::table('users')
            ->where('email', $newEmail)
            ->whereNull('deleted_at')
            ->where('id', '!=', $user->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Email này đã được sử dụng bởi tài khoản khác.'
            ], 422);
        }

        // Check if user has a recent OTP request (within 1 minute)
        $recentOtp = \App\Models\EmailOtp::where('user_id', $user->id)
            ->where('type', 'email_change')
            ->where('email', $newEmail)
            ->where('created_at', '>', now()->subMinute())
            ->first();

        if ($recentOtp) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng đợi ít nhất 1 phút trước khi yêu cầu mã OTP mới.'
            ], 429);
        }

        // Send OTP with 10 minutes expiry
        $success = $this->otpService->sendEmailChangeOtp($user, $newEmail, 10);

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Mã OTP đã được gửi đến email mới của bạn. Vui lòng kiểm tra hộp thư.'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Không thể gửi mã OTP. Vui lòng thử lại sau.'
            ], 500);
        }
    }

    /**
     * Verify OTP for email change.
     */
    public function verifyEmailChangeOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'otp_code' => 'required|string|size:6',
        ]);

        $user = Auth::user();
        $newEmail = $request->email;
        $otpCode = $request->otp_code;

        // Verify OTP
        try {
            $result = $this->otpService->verifyEmailChangeOtp($user->id, $otpCode);

            if (!$result || !isset($result['success']) || !$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Mã OTP không hợp lệ hoặc đã hết hạn.'
                ], 422);
            }

            // Check if the OTP email matches the requested email
            if (!isset($result['otp']) || !$result['otp']) {
                Log::error('OTP result missing otp object', [
                    'user_id' => $user->id,
                    'result' => $result
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi xử lý OTP. Vui lòng thử lại.'
                ], 500);
            }

            $otp = $result['otp'];
            if ($otp->email !== $newEmail) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mã OTP không khớp với email đã yêu cầu.'
                ], 422);
            }
        } catch (\Exception $e) {
            Log::error('Error verifying email change OTP', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xác thực OTP. Vui lòng thử lại.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }

        // Double-check email availability (excluding soft-deleted) before updating
        $exists = DB::table('users')
            ->where('email', $newEmail)
            ->whereNull('deleted_at')
            ->where('id', '!=', $user->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Email này đã được sử dụng bởi tài khoản khác.'
            ], 422);
        }

        // Update email immediately after OTP verification (like tenant does)
        try {
            DB::beginTransaction();
            
            DB::table('users')
                ->where('id', $user->id)
                ->update(['email' => $newEmail]);
            
            DB::commit();
            
            Log::info('Email updated after OTP verification', [
                'user_id' => $user->id,
                'new_email' => $newEmail
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating email after OTP verification', [
                'user_id' => $user->id,
                'new_email' => $newEmail,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Không thể cập nhật email. Vui lòng thử lại.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Xác thực OTP thành công! Email đã được cập nhật.'
        ]);
    }
}

