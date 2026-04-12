<?php

namespace App\Services;

use App\Models\OrganizationEmailSetting;
use Illuminate\Support\Facades\Log;

/**
 * Service: OrganizationMailConfigService
 * 
 * MỤC ĐÍCH:
 * Service quản lý cấu hình email theo từng organization - áp dụng cấu hình email riêng cho mỗi tổ chức
 * hoặc fallback về cấu hình mặc định từ .env nếu không có cấu hình riêng
 * 
 * LUỒNG XỬ LÝ CHÍNH:
 * 1. applyOrganizationMailConfig(): Áp dụng cấu hình email của organization vào mail config → Dùng để gửi email với SMTP riêng
 * 2. getOrganizationEmailSetting(): Lấy cấu hình email của organization → Dùng để hiển thị hoặc kiểm tra
 * 3. hasOrganizationEmailSettings(): Kiểm tra organization có cấu hình email đầy đủ không → Dùng để validate
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Model: OrganizationEmailSetting (bảng organization_email_settings) - Lấy cấu hình email của organization
 * 
 * DỮ LIỆU GHI VÀO:
 * - Config: mail.mailers.smtp.* - Cập nhật cấu hình SMTP trong runtime
 * - Logs: Ghi log quá trình áp dụng cấu hình
 * 
 * LƯU Ý:
 * - Ưu tiên sử dụng organization_email_settings nếu tồn tại và đầy đủ
 * - Nếu không có hoặc thiếu thông tin, sẽ fallback về config từ .env
 * - Cấu hình được áp dụng trong runtime, không thay đổi file config
 */
class OrganizationMailConfigService
{
    /**
     * Áp dụng cấu hình email của organization vào mail config
     * 
     * MỤC ĐÍCH:
     * Áp dụng cấu hình email SMTP riêng của organization vào Laravel mail config để gửi email
     * với thông tin SMTP của organization đó. Nếu không có cấu hình, sẽ dùng config từ .env
     * 
     * INPUT:
     * - organizationId: ID của organization cần áp dụng cấu hình
     * - Database: organization_email_settings
     * 
     * OUTPUT:
     * - bool: true nếu đã áp dụng cấu hình organization, false nếu dùng config từ .env
     * - Config: Cập nhật mail.mailers.smtp.* trong runtime
     * 
     * LUỒNG XỬ LÝ:
     * 1. Tìm cấu hình email của organization trong database
     * 2. Nếu không có cấu hình: return false (dùng .env config)
     * 3. Kiểm tra các trường bắt buộc (host, port, username, password, from_address)
     * 4. Nếu thiếu trường: return false (dùng .env config)
     * 5. Áp dụng cấu hình vào mail config (host, port, encryption, username, password, from)
     * 6. Ghi log và return true
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng organization_email_settings: Lấy cấu hình email của organization
     * 
     * DỮ LIỆU GHI VÀO:
     * - Config mail.mailers.smtp.*: Cập nhật cấu hình SMTP trong runtime
     * - Logs: Ghi log quá trình áp dụng cấu hình
     * 
     * LƯU Ý:
     * - Cấu hình được áp dụng trong runtime, không thay đổi file config
     * - Nếu có lỗi, sẽ fallback về config từ .env
     * - Password sẽ được decrypt tự động bởi accessor của model
     * 
     * @param int $organizationId ID của organization
     * @return bool true nếu đã áp dụng cấu hình organization, false nếu dùng .env config
     */
    /**
     * Chỉ áp dụng SMTP tổ chức khi mail.default là smtp (MAIL_MAILER=smtp).
     * Khi deploy với Resend (server chặn SMTP), mail.default là resend — không áp dụng SMTP tổ chức.
     */
    public function tryApplyOrganizationSmtpForOutgoing(?int $organizationId): bool
    {
        if (! $organizationId || config('mail.default') !== 'smtp') {
            return false;
        }

        return $this->applyOrganizationMailConfig($organizationId);
    }

    public function applyOrganizationMailConfig(int $organizationId): bool
    {
        try {
            $emailSetting = OrganizationEmailSetting::where('organization_id', $organizationId)->first(); // Tìm cấu hình email của organization → Kiểm tra có cấu hình riêng không

            if (!$emailSetting) { // Nếu không có cấu hình email của organization
                Log::debug('No organization email settings found, using .env config', [
                    'organization_id' => $organizationId
                ]); // Ghi log debug → Dùng để theo dõi
                return false; // Trả về false → Laravel sẽ dùng config từ .env
            }

            // Kiểm tra các trường bắt buộc có đầy đủ không
            if (empty($emailSetting->mail_host) || 
                empty($emailSetting->mail_port) || 
                empty($emailSetting->mail_username) || 
                empty($emailSetting->mail_password) || 
                empty($emailSetting->mail_from_address)) { // Nếu thiếu bất kỳ trường nào
                Log::warning('Organization email settings incomplete, using .env config', [
                    'organization_id' => $organizationId,
                    'has_host' => !empty($emailSetting->mail_host),
                    'has_port' => !empty($emailSetting->mail_port),
                    'has_username' => !empty($emailSetting->mail_username),
                    'has_password' => !empty($emailSetting->mail_password),
                    'has_from_address' => !empty($emailSetting->mail_from_address),
                ]); // Ghi log warning → Dùng để debug
                return false; // Trả về false → Dùng config từ .env
            }

            // Áp dụng cấu hình email của organization vào mail config
            config([
                'mail.mailers.smtp.host' => $emailSetting->mail_host, // SMTP host
                'mail.mailers.smtp.port' => $emailSetting->mail_port, // SMTP port
                'mail.mailers.smtp.encryption' => $emailSetting->mail_encryption, // Mã hóa (tls/ssl)
                'mail.mailers.smtp.username' => $emailSetting->mail_username, // Username đăng nhập SMTP
                'mail.mailers.smtp.password' => $emailSetting->mail_password, // Password (sẽ được decrypt tự động bởi accessor)
                'mail.from.address' => $emailSetting->mail_from_address, // Địa chỉ email gửi đi
                'mail.from.name' => config('app.name'), // Tên ứng dụng → Hiển thị trong email
            ]); // Cập nhật cấu hình mail trong runtime → Dùng để gửi email với SMTP của organization

            Log::debug('Organization email settings applied', [
                'organization_id' => $organizationId,
                'host' => $emailSetting->mail_host,
                'port' => $emailSetting->mail_port,
            ]); // Ghi log debug → Dùng để theo dõi

            return true; // Trả về true → Đã áp dụng cấu hình thành công
        } catch (\Exception $e) {
            Log::error('Error applying organization mail config, using .env config: ' . $e->getMessage(), [
                'organization_id' => $organizationId,
                'trace' => $e->getTraceAsString()
            ]); // Ghi log lỗi → Dùng để debug
            return false; // Trả về false → Fallback về config từ .env
        }
    }

    /**
     * Lấy cấu hình email của organization
     * 
     * MỤC ĐÍCH:
     * Lấy cấu hình email SMTP của organization từ database để hiển thị hoặc kiểm tra
     * 
     * INPUT:
     * - organizationId: ID của organization
     * - Database: organization_email_settings
     * 
     * OUTPUT:
     * - OrganizationEmailSetting|null: Model cấu hình email hoặc null nếu không có
     * 
     * LUỒNG XỬ LÝ:
     * 1. Query từ bảng organization_email_settings theo organization_id
     * 2. Lấy bản ghi đầu tiên (nếu có)
     * 3. Trả về model hoặc null
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng organization_email_settings: Lấy cấu hình email của organization
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     * 
     * @param int $organizationId ID của organization
     * @return OrganizationEmailSetting|null Model cấu hình email hoặc null
     */
    public function getOrganizationEmailSetting(int $organizationId): ?OrganizationEmailSetting
    {
        return OrganizationEmailSetting::where('organization_id', $organizationId)->first(); // Tìm cấu hình email của organization → Trả về model hoặc null
    }

    /**
     * Kiểm tra organization có cấu hình email đầy đủ không
     * 
     * MỤC ĐÍCH:
     * Kiểm tra organization có cấu hình email đầy đủ các trường bắt buộc (host, port, username, password, from_address)
     * để xác định có thể dùng cấu hình riêng hay phải dùng config từ .env
     * 
     * INPUT:
     * - organizationId: ID của organization
     * - Database: organization_email_settings
     * 
     * OUTPUT:
     * - bool: true nếu có cấu hình đầy đủ, false nếu không có hoặc thiếu trường
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy cấu hình email của organization
     * 2. Nếu không có cấu hình: return false
     * 3. Kiểm tra các trường bắt buộc: host, port, username, password, from_address
     * 4. Trả về true nếu đầy đủ, false nếu thiếu
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Bảng organization_email_settings: Lấy cấu hình email của organization
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     * 
     * @param int $organizationId ID của organization
     * @return bool true nếu có cấu hình đầy đủ, false nếu không
     */
    public function hasOrganizationEmailSettings(int $organizationId): bool
    {
        $emailSetting = $this->getOrganizationEmailSetting($organizationId); // Lấy cấu hình email của organization → Kiểm tra có tồn tại không
        
        if (!$emailSetting) { // Nếu không có cấu hình
            return false; // Trả về false → Không có cấu hình
        }

        // Kiểm tra tất cả các trường bắt buộc có đầy đủ không
        return !empty($emailSetting->mail_host) && // Host SMTP
               !empty($emailSetting->mail_port) && // Port SMTP
               !empty($emailSetting->mail_username) && // Username
               !empty($emailSetting->mail_password) && // Password
               !empty($emailSetting->mail_from_address); // Địa chỉ email gửi đi → Trả về true nếu đầy đủ, false nếu thiếu
    }
}

