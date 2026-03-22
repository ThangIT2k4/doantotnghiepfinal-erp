<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Auth;

/**
 * Service: ErpModuleService
 * 
 * MỤC ĐÍCH:
 * Service quản lý cấu hình và truy cập các ERP modules - kiểm tra quyền truy cập module, lấy danh sách modules,
 * và map legacy capabilities sang ERP module capabilities mới
 * 
 * LUỒNG XỬ LÝ CHÍNH:
 * 1. getModules(): Lấy tất cả cấu hình ERP modules từ config → Dùng để hiển thị danh sách modules
 * 2. getModule(): Lấy cấu hình một module cụ thể → Dùng để kiểm tra thông tin module
 * 3. getModuleCapabilities(): Lấy danh sách capabilities của module → Dùng để kiểm tra quyền
 * 4. userHasModuleAccess(): Kiểm tra user có quyền truy cập module không → Dùng để kiểm tra quyền
 * 5. getUserAccessibleModules(): Lấy danh sách modules mà user có quyền truy cập → Dùng để hiển thị menu
 * 6. mapLegacyCapability(): Map legacy capability sang ERP module capability mới → Dùng để backward compatibility
 * 7. getCurrentUserModules(): Lấy modules của user hiện tại → Dùng để hiển thị menu cho user đang đăng nhập
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Config: erp_modules (config/erp_modules.php) - Cấu hình các ERP modules
 * - Database: organization_users, roles, capabilities (qua CapabilityService) - Kiểm tra quyền user
 * 
 * DỮ LIỆU GHI VÀO:
 * - Không có (chỉ đọc)
 * 
 * LƯU Ý:
 * - Modules được định nghĩa trong config/erp_modules.php
 * - Quyền truy cập module được kiểm tra qua CapabilityService với format "{moduleKey}.access"
 * - Hỗ trợ map legacy capabilities sang ERP module capabilities mới để backward compatibility
 */
class ErpModuleService
{
    /**
     * Lấy tất cả cấu hình ERP modules
     * 
     * MỤC ĐÍCH:
     * Lấy tất cả cấu hình các ERP modules từ config file để hiển thị danh sách modules hoặc kiểm tra thông tin
     * 
     * INPUT:
     * - Config: erp_modules (config/erp_modules.php)
     * 
     * OUTPUT:
     * - array: Mảng cấu hình tất cả ERP modules
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy cấu hình từ config('erp_modules')
     * 2. Trả về mảng cấu hình (hoặc mảng rỗng nếu không có)
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Config erp_modules: Lấy cấu hình tất cả ERP modules
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     */
    public static function getModules(): array
    {
        return Config::get('erp_modules', []); // Lấy cấu hình ERP modules từ config → Trả về mảng cấu hình
    }

    /**
     * Lấy cấu hình một module cụ thể
     * 
     * MỤC ĐÍCH:
     * Lấy cấu hình của một ERP module cụ thể theo moduleKey để kiểm tra thông tin module
     * 
     * INPUT:
     * - moduleKey: Key của module cần lấy (ví dụ: 'asset', 'crm', 'billing')
     * - Config: erp_modules
     * 
     * OUTPUT:
     * - array|null: Cấu hình module hoặc null nếu không tồn tại
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy tất cả modules từ config
     * 2. Tìm module theo moduleKey
     * 3. Trả về cấu hình module hoặc null
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Config erp_modules: Lấy cấu hình module
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     */
    public static function getModule(string $moduleKey): ?array
    {
        $modules = self::getModules(); // Lấy tất cả modules → Tìm module cụ thể
        return $modules[$moduleKey] ?? null; // Trả về cấu hình module hoặc null → Dùng để kiểm tra module có tồn tại không
    }

    /**
     * Lấy danh sách capabilities của module
     * 
     * MỤC ĐÍCH:
     * Lấy danh sách capabilities (quyền) của một ERP module để kiểm tra quyền hoặc hiển thị danh sách quyền
     * 
     * INPUT:
     * - moduleKey: Key của module cần lấy capabilities
     * - Config: erp_modules
     * 
     * OUTPUT:
     * - array: Mảng các capabilities của module (hoặc mảng rỗng nếu không có)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy cấu hình module
     * 2. Lấy trường 'capabilities' từ cấu hình
     * 3. Trả về mảng capabilities hoặc mảng rỗng
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Config erp_modules: Lấy cấu hình module và capabilities
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     */
    public static function getModuleCapabilities(string $moduleKey): array
    {
        $module = self::getModule($moduleKey); // Lấy cấu hình module → Lấy capabilities
        return $module['capabilities'] ?? []; // Trả về mảng capabilities hoặc mảng rỗng → Dùng để kiểm tra quyền
    }

    /**
     * Kiểm tra user có quyền truy cập module không
     * 
     * MỤC ĐÍCH:
     * Kiểm tra user có quyền truy cập một ERP module cụ thể không thông qua capability "{moduleKey}.access"
     * 
     * INPUT:
     * - userId: ID của user cần kiểm tra
     * - orgId: Organization ID (optional)
     * - moduleKey: Key của module cần kiểm tra
     * - Database: organization_users, roles, capabilities (qua CapabilityService)
     * 
     * OUTPUT:
     * - bool: true nếu user có quyền, false nếu không
     * 
     * LUỒNG XỬ LÝ:
     * 1. Tạo capability key theo format "{moduleKey}.access"
     * 2. Kiểm tra user có capability này không qua CapabilityService
     * 3. Trả về kết quả
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Database: organization_users, roles, capabilities (qua CapabilityService::userHas)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     */
    public static function userHasModuleAccess(int $userId, ?int $orgId, string $moduleKey): bool
    {
        $capability = "{$moduleKey}.access"; // Tạo capability key theo format module.access → Dùng để kiểm tra quyền
        return CapabilityService::userHas($userId, $orgId, $capability); // Kiểm tra user có capability không → Trả về true/false
    }

    /**
     * Lấy danh sách modules mà user có quyền truy cập
     * 
     * MỤC ĐÍCH:
     * Lấy danh sách tất cả ERP modules mà user có quyền truy cập để hiển thị menu hoặc filter modules
     * 
     * INPUT:
     * - userId: ID của user
     * - orgId: Organization ID (optional)
     * - Config: erp_modules
     * - Database: organization_users, roles, capabilities (qua CapabilityService)
     * 
     * OUTPUT:
     * - array: Mảng các modules mà user có quyền truy cập
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy tất cả modules từ config
     * 2. Duyệt qua từng module
     * 3. Kiểm tra user có quyền truy cập module không
     * 4. Thêm module vào danh sách nếu có quyền
     * 5. Trả về danh sách modules
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Config erp_modules: Lấy cấu hình tất cả modules
     * - Database: organization_users, roles, capabilities (qua userHasModuleAccess)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     */
    public static function getUserAccessibleModules(int $userId, ?int $orgId): array
    {
        $modules = self::getModules(); // Lấy tất cả modules từ config → Duyệt qua để kiểm tra quyền
        $accessibleModules = []; // Khởi tạo mảng rỗng → Lưu danh sách modules có quyền

        foreach ($modules as $moduleKey => $moduleConfig) { // Duyệt qua từng module
            if (self::userHasModuleAccess($userId, $orgId, $moduleKey)) { // Kiểm tra user có quyền truy cập module không
                $accessibleModules[$moduleKey] = $moduleConfig; // Thêm module vào danh sách → Dùng để hiển thị menu
            }
        }

        return $accessibleModules; // Trả về danh sách modules có quyền → Dùng để hiển thị menu
    }

    /**
     * Map legacy capability sang ERP module capability mới
     * 
     * MỤC ĐÍCH:
     * Chuyển đổi legacy capability (format cũ) sang ERP module capability mới (format module.entity.action)
     * để hỗ trợ backward compatibility với code cũ sử dụng legacy capabilities
     * 
     * INPUT:
     * - legacyCapability: Legacy capability cần map (ví dụ: 'property.view', 'lead.create')
     * 
     * OUTPUT:
     * - string|null: ERP module capability mới (ví dụ: 'asset.property.view', 'crm.lead.create') hoặc null nếu không có mapping
     * 
     * LUỒNG XỬ LÝ:
     * 1. Tìm legacy capability trong mapping table
     * 2. Trả về ERP module capability tương ứng hoặc null
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Không có (hardcoded mapping)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     * 
     * LƯU Ý:
     * - Mapping được hardcode trong method này
     * - Nếu không tìm thấy mapping, trả về null
     * - Dùng để backward compatibility với code cũ
     * 
     * @param string $legacyCapability Legacy capability cần map (ví dụ: 'property.view')
     * @return string|null ERP module capability mới (ví dụ: 'asset.property.view') hoặc null
     */
    public static function mapLegacyCapability(string $legacyCapability): ?string
    {
        $mapping = [
            // Property/Asset mappings - Map property capabilities sang asset module
            'property.view' => 'asset.property.view',
            'property.create' => 'asset.property.create',
            'property.update' => 'asset.property.update',
            'property.delete' => 'asset.property.delete',
            
            // Unit/Asset mappings - Map unit capabilities sang asset module
            'unit.view' => 'asset.unit.view',
            'unit.create' => 'asset.unit.create',
            'unit.update' => 'asset.unit.update',
            'unit.delete' => 'asset.unit.delete',
            
            // Lead/CRM mappings - Map lead capabilities sang crm module
            'lead.view' => 'crm.lead.view',
            'lead.create' => 'crm.lead.create',
            'lead.update' => 'crm.lead.update',
            'lead.delete' => 'crm.lead.delete',
            
            // Viewing/CRM mappings - Map viewing capabilities sang crm.appointment module
            'viewing.view' => 'crm.appointment.view',
            'viewing.create' => 'crm.appointment.create',
            'viewing.update' => 'crm.appointment.update',
            'viewing.delete' => 'crm.appointment.delete',
            
            // Lease/Contract mappings - Map lease capabilities sang contract module
            'lease.view' => 'contract.lease.view',
            'lease.create' => 'contract.lease.create',
            'lease.update' => 'contract.lease.update',
            'lease.delete' => 'contract.lease.delete',
            
            // Invoice/Billing mappings - Map invoice capabilities sang billing module
            'invoice.view' => 'billing.invoice.view',
            'invoice.create' => 'billing.invoice.create',
            'invoice.create_draft' => 'billing.invoice.create',
            'invoice.update' => 'billing.invoice.update',
            'invoice.delete' => 'billing.invoice.delete',
            'invoice.issue' => 'billing.invoice.issue',
            
            // Payment/Billing mappings - Map payment capabilities sang billing module
            'payment.view' => 'billing.payment.view',
            'payment.create' => 'billing.payment.create',
            'payment.update' => 'billing.payment.update',
            'payment.delete' => 'billing.payment.delete',
            
            // Ticket/Work mappings - Map ticket capabilities sang work module
            'ticket.view' => 'work.ticket.view',
            'ticket.create' => 'work.ticket.create',
            'ticket.update' => 'work.ticket.update',
            'ticket.delete' => 'work.ticket.delete',
        ]; // Mapping table hardcoded → Dùng để chuyển đổi legacy capabilities

        return $mapping[$legacyCapability] ?? null; // Tìm mapping hoặc trả về null → Dùng để backward compatibility
    }

    /**
     * Lấy danh sách modules của user hiện tại
     * 
     * MỤC ĐÍCH:
     * Lấy danh sách ERP modules mà user đang đăng nhập có quyền truy cập để hiển thị menu
     * 
     * INPUT:
     * - Session: user đang đăng nhập (qua Auth)
     * - Database: organization_users (qua user->organizations())
     * - Config: erp_modules
     * 
     * OUTPUT:
     * - array: Mảng các modules mà user có quyền truy cập (hoặc mảng rỗng nếu chưa đăng nhập hoặc không có organization)
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra user có đăng nhập không
     * 2. Nếu chưa đăng nhập: return []
     * 3. Lấy organization đầu tiên của user
     * 4. Nếu không có organization: return []
     * 5. Lấy danh sách modules có quyền truy cập
     * 6. Trả về danh sách modules
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Session: user đang đăng nhập (qua Auth::user())
     * - Database: organization_users (qua user->organizations())
     * - Config: erp_modules (qua getUserAccessibleModules)
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     */
    public static function getCurrentUserModules(): array
    {
        if (!Auth::check()) { // Kiểm tra user có đăng nhập không
            return []; // Trả về mảng rỗng → User chưa đăng nhập
        }

        /** @var \App\Models\User $user */
        $user = Auth::user(); // Lấy user đang đăng nhập → Lấy organization
        $orgId = $user->organizations()->first()?->id; // Lấy organization đầu tiên của user → Dùng để kiểm tra quyền

        if (!$orgId) { // Nếu user không thuộc organization nào
            return []; // Trả về mảng rỗng → Không có organization
        }

        return self::getUserAccessibleModules($user->id, $orgId); // Lấy danh sách modules có quyền → Trả về để hiển thị menu
    }
}

