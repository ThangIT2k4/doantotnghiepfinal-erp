<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\UserProfile;
use App\Models\SepayBank;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{

    /**
     * Display the user's profile.
     */
    public function index()
    {
        $user = Auth::user();
        $user->load(['userProfile.sepayBank', 'companyInvoices' => function($q) {
            $q->orderBy('created_at', 'desc')->limit(10);
        }, 'cashOutflows' => function($q) {
            $q->orderBy('paid_at', 'desc')->limit(10);
        }]);
        $userProfile = $user->userProfile;
        return view('tenant.profile.index', compact('user', 'userProfile'));
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        // Validate request
        $request->validate([
            // Basic user info
            'full_name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->whereNull('deleted_at')->ignore($user->id)
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
            'address' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:1000',
            
            // Banking information
            'sepay_bank_id' => 'nullable|exists:sepay_banks,id',
            'account_number' => 'nullable|string|max:50',
            'account_holder_name' => 'nullable|string|max:255',
            'branch_name' => 'nullable|string|max:255',
            'branch_code' => 'nullable|string|max:20',
            'swift_code' => 'nullable|string|max:20',
            'banking_notes' => 'nullable|string',
            'tax_code' => 'nullable|string|max:50',
            'id_card_number' => 'nullable|string|max:20',
            'id_card_issue_date' => 'nullable|date',
            'id_card_issue_place' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date|before:today',
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
            'address.max' => 'Địa chỉ không được vượt quá 255 ký tự.',
            'note.max' => 'Ghi chú không được vượt quá 1000 ký tự.',
            
            // Banking validation messages
            'sepay_bank_id.exists' => 'Ngân hàng không hợp lệ.',
            'account_number.max' => 'Số tài khoản không được vượt quá 50 ký tự.',
            'account_holder_name.max' => 'Tên chủ tài khoản không được vượt quá 255 ký tự.',
            'branch_name.max' => 'Tên chi nhánh không được vượt quá 255 ký tự.',
            'branch_code.max' => 'Mã chi nhánh không được vượt quá 20 ký tự.',
            'swift_code.max' => 'Mã SWIFT không được vượt quá 20 ký tự.',
            'tax_code.max' => 'Mã số thuế không được vượt quá 50 ký tự.',
            'id_card_number.max' => 'Số CMND/CCCD không được vượt quá 20 ký tự.',
            'id_card_issue_place.max' => 'Nơi cấp không được vượt quá 255 ký tự.',
        ]);

        try {
            // Prepare user update data (email, phone only)
            $updateData = [
                'email' => $request->email,
                'phone' => $request->phone,
            ];

            // Update password if provided
            if ($request->filled('password')) {
                // Verify current password if provided
                if ($request->filled('current_password')) {
                    if (!Hash::check($request->current_password, $user->password_hash)) {
                        return back()->withErrors(['current_password' => 'Mật khẩu hiện tại không đúng.']);
                    }
                }

                $updateData['password_hash'] = Hash::make($request->password);
            }

            // Update user using DB facade
            DB::table('users')
                ->where('id', $user->id)
                ->update($updateData);

            // Get or create user profile
            $profile = $user->getOrCreateProfile();
            
            // Prepare profile update data (including full_name)
            $profileData = [
                'full_name' => $request->full_name,
                'dob' => $request->dob ?? $request->birth_date,
                'gender' => $request->gender,
                'id_number' => $request->id_number ?? $request->id_card_number,
                'id_issued_at' => $request->id_issued_at ?? $request->id_card_issue_date,
                'id_card_place' => $request->id_card_issue_place,
                'address' => $request->address,
                'note' => $request->note,
                // Banking information
                'sepay_bank_id' => $request->sepay_bank_id,
                'account_number' => $request->account_number,
                'account_holder_name' => $request->account_holder_name,
                'branch_name' => $request->branch_name,
                'branch_code' => $request->branch_code,
                'swift_code' => $request->swift_code,
                'banking_notes' => $request->banking_notes,
                'tax_code' => $request->tax_code,
            ];

            // Remove null values but keep full_name
            $profileData = array_filter($profileData, function($value, $key) {
                return ($value !== null && $value !== '') || $key === 'full_name';
            }, ARRAY_FILTER_USE_BOTH);

            // Update profile
            $profile->update($profileData);

            return redirect()->route('tenant.profile')
                ->with('success', 'Cập nhật thông tin thành công.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Có lỗi xảy ra khi cập nhật thông tin. Vui lòng thử lại.']);
        }
    }

    /**
     * Show the form for editing the user's profile.
     */
    public function edit()
    {
        $user = Auth::user();
        $userProfile = $user->userProfile;
        $sepayBanks = SepayBank::where('supported', 1)->orderBy('name')->get();
        return view('tenant.profile.edit', compact('user', 'userProfile', 'sepayBanks'));
    }

    /**
     * Send OTP for email verification in profile.
     */
    public function sendEmailVerificationOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255'
        ]);

        $user = Auth::user();
        $newEmail = $request->email;

        // Check if email is already in use by another user
        $existingUser = DB::table('users')
            ->where('email', $newEmail)
            ->where('id', '!=', $user->id)
            ->first();

        if ($existingUser) {
            return response()->json([
                'success' => false,
                'message' => 'Email này đã được sử dụng bởi tài khoản khác.'
            ]);
        }

        // Check if email is already verified
        $otpService = app(OtpService::class);
        $isEmailVerified = $otpService->isEmailVerified($user->id, $newEmail, 'email_verification');
        
        if ($isEmailVerified) {
            return response()->json([
                'success' => false,
                'message' => 'Email này đã được xác thực.'
            ]);
        }

        // Send OTP
        $otpSent = $otpService->sendEmailVerificationOtp($user, $newEmail);

        if ($otpSent) {
            return response()->json([
                'success' => true,
                'message' => 'Mã OTP đã được gửi đến email mới. Vui lòng kiểm tra hộp thư.'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Không thể gửi mã OTP. Vui lòng thử lại.'
            ]);
        }
    }

    /**
     * Check email verification status.
     */
    public function checkEmailVerificationStatus(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255'
        ]);

        $user = Auth::user();
        $otpService = app(OtpService::class);
        $status = $otpService->getEmailVerificationStatus($user->id, $request->email);

        return response()->json($status);
    }
}
