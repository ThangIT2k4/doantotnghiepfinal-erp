<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\OrganizationEmailSetting;
use App\Traits\ChecksCapabilities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class OrganizationEmailSettingController extends Controller
{
    use ChecksCapabilities;

    /**
     * Update email settings for organization
     */
    public function update(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('contract.lease.view', 'Bạn không có quyền cập nhật cấu hình email.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thuộc tổ chức nào.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'mail_host' => 'nullable|string|max:255',
            'mail_port' => 'nullable|integer|min:1|max:65535',
            'mail_encryption' => 'nullable|string|in:tls,ssl',
            'mail_from_address' => 'nullable|email|max:255',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:255',
        ], [
            'mail_port.integer' => 'Cổng SMTP phải là số.',
            'mail_port.min' => 'Cổng SMTP phải lớn hơn 0.',
            'mail_port.max' => 'Cổng SMTP không được vượt quá 65535.',
            'mail_encryption.in' => 'Mã hóa phải là TLS hoặc SSL.',
            'mail_from_address.email' => 'Địa chỉ email gửi không hợp lệ.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $emailSetting = OrganizationEmailSetting::where('organization_id', $organizationId)->first();

            $data = [
                'organization_id' => $organizationId,
                'mail_host' => $request->mail_host,
                'mail_port' => $request->mail_port,
                'mail_encryption' => $request->mail_encryption,
                'mail_from_address' => $request->mail_from_address,
                'mail_username' => $request->mail_username,
            ];

            // Only update password if provided
            if ($request->filled('mail_password')) {
                $data['mail_password'] = $request->mail_password;
            }

            if ($emailSetting) {
                // Update existing
                if (!$request->filled('mail_password')) {
                    unset($data['mail_password']);
                }
                $emailSetting->update($data);
            } else {
                // Create new
                if (!$request->filled('mail_password')) {
                    $data['mail_password'] = null;
                }
                $emailSetting = OrganizationEmailSetting::create($data);
            }

            Log::info('Organization email settings updated', [
                'organization_id' => $organizationId,
                'updated_by' => $user->id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cấu hình email đã được cập nhật thành công!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating organization email settings: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['mail_password'])
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật cấu hình email. Vui lòng thử lại sau.'
            ], 500);
        }
    }

    /**
     * Test email connection
     */
    public function testConnection(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('contract.lease.view', 'Bạn không có quyền kiểm tra kết nối email.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thuộc tổ chức nào.'
            ], 403);
        }

        try {
            // Get existing email setting - ưu tiên sử dụng organization_email_settings nếu tồn tại
            $emailSetting = OrganizationEmailSetting::where('organization_id', $organizationId)->first();
            
            // Use values from form or fallback to existing settings
            $mailHost = $request->filled('mail_host') ? $request->mail_host : ($emailSetting->mail_host ?? null);
            $mailPort = $request->filled('mail_port') ? $request->mail_port : ($emailSetting->mail_port ?? null);
            $mailEncryption = $request->filled('mail_encryption') ? $request->mail_encryption : ($emailSetting->mail_encryption ?? null);
            $mailFromAddress = $request->filled('mail_from_address') ? $request->mail_from_address : ($emailSetting->mail_from_address ?? null);
            $mailUsername = $request->filled('mail_username') ? $request->mail_username : ($emailSetting->mail_username ?? null);
            $mailPassword = $request->filled('mail_password') ? $request->mail_password : ($emailSetting->mail_password ?? null);

            // Validate required fields
            if (empty($mailHost) || empty($mailPort) || empty($mailFromAddress) || empty($mailUsername) || empty($mailPassword)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vui lòng điền đầy đủ thông tin SMTP hoặc cấu hình email trong hệ thống.'
                ], 422);
            }

            // Temporarily update mail config
            config([
                'mail.mailers.smtp.host' => $mailHost,
                'mail.mailers.smtp.port' => $mailPort,
                'mail.mailers.smtp.encryption' => $mailEncryption,
                'mail.mailers.smtp.username' => $mailUsername,
                'mail.mailers.smtp.password' => $mailPassword,
                'mail.from.address' => $mailFromAddress,
                'mail.from.name' => config('app.name'),
            ]);

            // Try to send a test email
            Mail::raw('Đây là email kiểm tra kết nối SMTP từ hệ thống.', function ($message) use ($mailFromAddress) {
                $message->to($mailFromAddress)
                        ->subject('Kiểm tra kết nối SMTP');
            });

            return response()->json([
                'success' => true,
                'message' => 'Kết nối email thành công! Email kiểm tra đã được gửi đến ' . $mailFromAddress
            ]);

        } catch (\Exception $e) {
            Log::error('Error testing email connection: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'organization_id' => $organizationId,
                'mail_host' => $mailHost ?? null,
            ]);
            
            $errorMessage = $e->getMessage();
            
            // Provide helpful message for Gmail authentication errors
            if (str_contains($errorMessage, 'BadCredentials') || str_contains($errorMessage, 'Username and Password not accepted')) {
                if (str_contains(strtolower($mailHost ?? ''), 'gmail.com')) {
                    $errorMessage = 'Xác thực Gmail thất bại. Vui lòng kiểm tra:' . PHP_EOL . PHP_EOL .
                        '1. Bạn đã bật 2-Step Verification trong tài khoản Google' . PHP_EOL .
                        '2. Bạn đã tạo App Password (không phải mật khẩu thông thường)' . PHP_EOL .
                        '3. Bạn đang sử dụng App Password (16 ký tự) thay vì mật khẩu thông thường' . PHP_EOL . PHP_EOL .
                        'Hướng dẫn tạo App Password:' . PHP_EOL .
                        'https://myaccount.google.com/apppasswords' . PHP_EOL . PHP_EOL .
                        'Lưu ý: App Password là mật khẩu 16 ký tự, không có khoảng trắng.';
                } else {
                    $errorMessage = 'Xác thực SMTP thất bại. Vui lòng kiểm tra lại tên đăng nhập và mật khẩu.';
                }
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Kết nối email thất bại: ' . $errorMessage
            ], 500);
        }
    }
}

