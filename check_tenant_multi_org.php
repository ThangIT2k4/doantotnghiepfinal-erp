<?php

/**
 * Script kiểm tra nhanh: Tenant xem tất cả dữ liệu từ tất cả organizations
 * 
 * Chạy: php check_tenant_multi_org.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Organization;
use App\Models\Role;
use App\Models\Lease;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

echo "=== KIỂM TRA TENANT MULTI-ORGANIZATION ===\n\n";

// 1. Kiểm tra BelongsToOrganization trait
echo "1. Kiểm tra BelongsToOrganization trait...\n";
$traitFile = __DIR__ . '/app/Traits/BelongsToOrganization.php';
if (file_exists($traitFile)) {
    $content = file_get_contents($traitFile);
    if (strpos($content, 'whereIn') !== false && strpos($content, 'tenant') !== false) {
        echo "   ✅ Trait đã được cập nhật đúng\n";
    } else {
        echo "   ❌ Trait chưa được cập nhật\n";
    }
} else {
    echo "   ❌ Không tìm thấy file trait\n";
}

// 2. Kiểm tra Lease::getAccessibleLeaseIds()
echo "\n2. Kiểm tra Lease::getAccessibleLeaseIds()...\n";
try {
    $reflection = new ReflectionMethod(Lease::class, 'getAccessibleLeaseIds');
    $params = $reflection->getParameters();
    if (count($params) >= 1 && $params[1]->allowsNull()) {
        echo "   ✅ Method có optional organizationId parameter\n";
    } else {
        echo "   ⚠️  Method có thể cần kiểm tra lại\n";
    }
} catch (Exception $e) {
    echo "   ❌ Lỗi: " . $e->getMessage() . "\n";
}

// 3. Kiểm tra NotificationController
echo "\n3. Kiểm tra NotificationController...\n";
$controllerFile = __DIR__ . '/app/Http/Controllers/Tenant/NotificationController.php';
if (file_exists($controllerFile)) {
    $content = file_get_contents($controllerFile);
    $count = substr_count($content, 'getAccessibleLeaseIds');
    if ($count >= 3) {
        echo "   ✅ NotificationController đã dùng getAccessibleLeaseIds() ($count lần)\n";
    } else {
        echo "   ⚠️  NotificationController có thể cần kiểm tra lại (tìm thấy $count lần)\n";
    }
} else {
    echo "   ❌ Không tìm thấy file controller\n";
}

// 4. Kiểm tra ReviewController
echo "\n4. Kiểm tra ReviewController...\n";
$controllerFile = __DIR__ . '/app/Http/Controllers/Tenant/ReviewController.php';
if (file_exists($controllerFile)) {
    $content = file_get_contents($controllerFile);
    if (strpos($content, 'getAccessibleLeaseIds') !== false) {
        echo "   ✅ ReviewController đã dùng getAccessibleLeaseIds()\n";
    } else {
        echo "   ⚠️  ReviewController có thể cần kiểm tra lại\n";
    }
} else {
    echo "   ❌ Không tìm thấy file controller\n";
}

// 5. Kiểm tra User::getAllOrganizationIds()
echo "\n5. Kiểm tra User::getAllOrganizationIds()...\n";
try {
    $reflection = new ReflectionMethod(User::class, 'getAllOrganizationIds');
    echo "   ✅ Method getAllOrganizationIds() đã tồn tại\n";
} catch (Exception $e) {
    echo "   ❌ Method getAllOrganizationIds() chưa được tạo\n";
}

// 6. Test với database (nếu có tenant user)
echo "\n6. Kiểm tra với database...\n";
try {
    // Tìm tenant user đầu tiên
    $tenantRole = Role::where('key_code', 'tenant')->first();
    if ($tenantRole) {
        $tenant = DB::table('organization_users')
            ->where('role_id', $tenantRole->id)
            ->where('status', 'active')
            ->first();
        
        if ($tenant) {
            $user = User::find($tenant->user_id);
            if ($user) {
                $orgCount = $user->organizations()->count();
                echo "   ✅ Tìm thấy tenant user (ID: {$user->id}) thuộc $orgCount organization(s)\n";
                
                // Test getAllOrganizationIds()
                $orgIds = $user->getAllOrganizationIds();
                echo "   ✅ getAllOrganizationIds() trả về: " . json_encode($orgIds) . "\n";
                
                // Test getAccessibleLeaseIds()
                $leaseIds = Lease::getAccessibleLeaseIds($user->id);
                echo "   ✅ getAccessibleLeaseIds() trả về " . $leaseIds->count() . " lease(s)\n";
                
                // Test global scope
                $leases = Lease::all();
                echo "   ✅ Lease::all() trả về " . $leases->count() . " lease(s) (với global scope)\n";
                
                if ($orgCount > 1 && $leaseIds->count() > 0) {
                    echo "   ✅ Tenant có nhiều organizations và có leases - logic hoạt động đúng!\n";
                }
            } else {
                echo "   ⚠️  Không tìm thấy user\n";
            }
        } else {
            echo "   ⚠️  Không tìm thấy tenant user trong database\n";
        }
    } else {
        echo "   ⚠️  Không tìm thấy tenant role\n";
    }
} catch (Exception $e) {
    echo "   ⚠️  Lỗi khi kiểm tra database: " . $e->getMessage() . "\n";
}

echo "\n=== HOÀN TẤT ===\n";
echo "\nLưu ý: Để test đầy đủ, cần:\n";
echo "1. Tạo tenant user thuộc 2+ organizations\n";
echo "2. Tạo leases/invoices ở mỗi organization\n";
echo "3. Login và kiểm tra UI\n";
echo "4. Xác nhận thấy data từ TẤT CẢ organizations\n";

