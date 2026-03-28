<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Organization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Command: DeleteOrganizationData
 * 
 * MỤC ĐÍCH:
 * Xóa tất cả dữ liệu của một tổ chức được chọn. Command này xóa tất cả dữ liệu liên quan đến organization
 * (properties, leases, invoices, users, etc.) và cuối cùng xóa organization.
 * 
 * LUỒNG XỬ LÝ:
 * 1. Nhận tham số: id hoặc code (organization ID hoặc code)
 * 2. Nhận options: --force (hard delete), --no-confirm (không hỏi xác nhận)
 * 3. Tìm organization theo ID hoặc code
 * 4. Hiển thị thống kê dữ liệu sẽ bị xóa
 * 5. Xác nhận từ người dùng (trừ khi có --no-confirm)
 * 6. Xóa tất cả dữ liệu liên quan theo thứ tự:
 *    - Notifications
 *    - Audit logs
 *    - Invoices, Payments
 *    - Leases, Lease Service Sets
 *    - Tickets, Ticket Logs
 *    - Reviews, Review Replies
 *    - Viewings
 *    - Leads, Booking Deposits
 *    - Properties, Units, Property Types
 *    - Services
 *    - Organization Users, Organization User Capabilities
 *    - Organization Subscriptions, Subscription Invoices
 *    - Organization Email Settings, Organization Banking
 *    - Salary Contracts, Salary Advances, Payroll Cycles
 *    - Commission Events, Commission Policies
 *    - Payment Cycles
 *    - Company Invoices, Deposit Refunds
 *    - Master Leases
 *    - Vendors
 *    - Organization (cuối cùng)
 * 7. Hiển thị kết quả
 * 
 * CÁCH CHẠY:
 * php artisan organization:delete-data {id|code} [--force] [--no-confirm]
 * 
 * Ví dụ:
 * php artisan organization:delete-data 1
 * php artisan organization:delete-data ORG001
 * php artisan organization:delete-data 1 --force --no-confirm
 * 
 * Options:
 * --force: Force delete (hard delete) thay vì soft delete
 * --no-confirm: Xóa mà không hỏi xác nhận
 * 
 * LƯU Ý:
 * - Command này XÓA TẤT CẢ DỮ LIỆU của organization
 * - Không thể hoàn tác sau khi chạy
 * - Chỉ nên dùng trong môi trường development/test hoặc khi thực sự cần xóa organization
 */
class DeleteOrganizationData extends Command
{
    /**
     * Tên và signature của command để gọi từ terminal
     * 
     * Tham số:
     * - {identifier}: Organization ID hoặc code (bắt buộc)
     * 
     * Options:
     * - --force: Force delete (hard delete)
     * - --no-confirm: Skip confirmation
     * 
     * @var string
     */
    protected $signature = 'organization:delete-data 
                            {identifier : Organization ID or code}
                            {--force : Force delete (hard delete)}
                            {--no-confirm : Skip confirmation}';

    /**
     * Mô tả command hiển thị khi chạy php artisan list
     * 
     * @var string
     */
    protected $description = 'Delete all data of a selected organization';

    /**
     * Hàm chính xử lý command
     * 
     * @return int 0 nếu thành công, 1 nếu có lỗi
     */
    public function handle()
    {
        $identifier = $this->argument('identifier');
        $force = $this->option('force');
        $noConfirm = $this->option('no-confirm');

        // Tìm organization theo ID hoặc code
        $organization = Organization::withTrashed()
            ->where('id', $identifier)
            ->orWhere('code', $identifier)
            ->first();

        if (!$organization) {
            $this->error("Organization with ID/code '{$identifier}' does not exist!");
            return 1;
        }

        // Hiển thị thông tin organization
        $this->info("Organization: ID {$organization->id} - {$organization->name} ({$organization->code})");
        
        // Thống kê dữ liệu sẽ bị xóa
        $stats = $this->getOrganizationStats($organization->id);
        
        $this->warn("\n⚠️  Dữ liệu sẽ bị xóa:");
        $this->table(
            ['Loại dữ liệu', 'Số lượng'],
            $stats
        );

        $totalRecords = array_sum(array_column($stats, 1));
        
        if ($totalRecords === 0) {
            $this->info("\nOrganization không có dữ liệu nào.");
        }

        // Xác nhận
        if (!$noConfirm) {
            $deleteType = $force ? 'force delete (hard delete)' : 'soft delete';
            if (!$this->confirm("\n⚠️  Bạn có chắc chắn muốn {$deleteType} tất cả dữ liệu của organization này? (Không thể hoàn tác!)")) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        // Bắt đầu xóa
        $this->info("\n🔄 Bắt đầu xóa dữ liệu...");
        
        DB::beginTransaction();
        try {
            $deletedCounts = $this->deleteOrganizationData($organization->id, $force);
            
            DB::commit();
            
            $this->info("\n✅ Xóa dữ liệu hoàn tất!");
            $this->table(
                ['Loại dữ liệu', 'Đã xóa'],
                $deletedCounts
            );
            
            $totalDeleted = array_sum(array_column($deletedCounts, 1));
            $this->info("Tổng cộng: {$totalDeleted} bản ghi đã được xóa.");
            
            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("\n❌ Lỗi khi xóa dữ liệu: {$e->getMessage()}");
            Log::error('DeleteOrganizationData failed', [
                'organization_id' => $organization->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Lấy thống kê dữ liệu của organization
     * 
     * @param int $organizationId
     * @return array
     */
    private function getOrganizationStats(int $organizationId): array
    {
        $stats = [];
        
        // Đếm từng loại dữ liệu
        $stats[] = ['Properties', DB::table('properties')->where('organization_id', $organizationId)->count()];
        $stats[] = ['Units', DB::table('units')->whereIn('property_id', function($q) use ($organizationId) {
            $q->select('id')->from('properties')->where('organization_id', $organizationId);
        })->count()];
        $stats[] = ['Leases', DB::table('leases')->where('organization_id', $organizationId)->count()];
        $stats[] = ['Invoices', DB::table('invoices')->where('organization_id', $organizationId)->count()];
        $stats[] = ['Payments', DB::table('payments')->whereIn('invoice_id', function($q) use ($organizationId) {
            $q->select('id')->from('invoices')->where('organization_id', $organizationId);
        })->count()];
        $stats[] = ['Tickets', DB::table('tickets')->where('organization_id', $organizationId)->count()];
        $stats[] = ['Leads', DB::table('leads')->where('organization_id', $organizationId)->count()];
        $stats[] = ['Viewings', DB::table('viewings')->where('organization_id', $organizationId)->count()];
        $stats[] = ['Booking Deposits', DB::table('booking_deposits')->where('organization_id', $organizationId)->count()];
        $stats[] = ['Organization Users', DB::table('organization_users')->where('organization_id', $organizationId)->count()];
        $stats[] = ['Services', DB::table('services')->where('organization_id', $organizationId)->count()];
        $stats[] = ['Audit Logs', DB::table('audit_logs')->where('organization_id', $organizationId)->count()];
        $stats[] = ['Notifications', DB::table('notifications')->whereIn('to_user_id', function($q) use ($organizationId) {
            $q->select('user_id')->from('organization_users')->where('organization_id', $organizationId);
        })->count()];
        
        return $stats;
    }

    /**
     * Xóa tất cả dữ liệu của organization
     * 
     * @param int $organizationId
     * @param bool $force
     * @return array
     */
    private function deleteOrganizationData(int $organizationId, bool $force): array
    {
        $deletedCounts = [];
        
        // Lấy danh sách user IDs trong organization
        $userIds = DB::table('organization_users')
            ->where('organization_id', $organizationId)
            ->pluck('user_id')
            ->toArray();
        
        // 1. Xóa Notifications (của users trong organization)
        if (!empty($userIds)) {
            $count = DB::table('notifications')->whereIn('to_user_id', $userIds)->delete();
            $deletedCounts[] = ['Notifications', $count];
            $this->line("  ✓ Đã xóa {$count} notifications");
        }
        
        // 2. Xóa Audit Logs
        $count = DB::table('audit_logs')->where('organization_id', $organizationId)->delete();
        $deletedCounts[] = ['Audit Logs', $count];
        $this->line("  ✓ Đã xóa {$count} audit logs");
        
        // 3. Xóa Payments (trước invoices vì có foreign key)
        $invoiceIds = DB::table('invoices')->where('organization_id', $organizationId)->pluck('id')->toArray();
        if (!empty($invoiceIds)) {
            $count = DB::table('payments')->whereIn('invoice_id', $invoiceIds)->delete();
            $deletedCounts[] = ['Payments', $count];
            $this->line("  ✓ Đã xóa {$count} payments");
        }
        
        // 4. Xóa Invoice Items
        if (!empty($invoiceIds)) {
            $count = DB::table('invoice_items')->whereIn('invoice_id', $invoiceIds)->delete();
            $deletedCounts[] = ['Invoice Items', $count];
            $this->line("  ✓ Đã xóa {$count} invoice items");
        }
        
        // 5. Xóa Invoices
        $count = DB::table('invoices')->where('organization_id', $organizationId)->delete();
        $deletedCounts[] = ['Invoices', $count];
        $this->line("  ✓ Đã xóa {$count} invoices");
        
        // 6. Xóa Ticket Logs (trước tickets)
        $ticketIds = DB::table('tickets')->where('organization_id', $organizationId)->pluck('id')->toArray();
        if (!empty($ticketIds)) {
            $count = DB::table('ticket_logs')->whereIn('ticket_id', $ticketIds)->delete();
            $deletedCounts[] = ['Ticket Logs', $count];
            $this->line("  ✓ Đã xóa {$count} ticket logs");
        }
        
        // 7. Xóa Tickets
        $count = DB::table('tickets')->where('organization_id', $organizationId)->delete();
        $deletedCounts[] = ['Tickets', $count];
        $this->line("  ✓ Đã xóa {$count} tickets");
        
        // 8. Xóa Review Replies (trước reviews)
        $reviewIds = DB::table('reviews')->where('organization_id', $organizationId)->pluck('id')->toArray();
        if (!empty($reviewIds)) {
            $count = DB::table('review_replies')->whereIn('review_id', $reviewIds)->delete();
            $deletedCounts[] = ['Review Replies', $count];
            $this->line("  ✓ Đã xóa {$count} review replies");
        }
        
        // 9. Xóa Reviews
        $count = DB::table('reviews')->where('organization_id', $organizationId)->delete();
        $deletedCounts[] = ['Reviews', $count];
        $this->line("  ✓ Đã xóa {$count} reviews");
        
        // 10. Xóa Viewings
        $count = DB::table('viewings')->where('organization_id', $organizationId)->delete();
        $deletedCounts[] = ['Viewings', $count];
        $this->line("  ✓ Đã xóa {$count} viewings");
        
        // 11. Xóa Booking Deposits
        $count = DB::table('booking_deposits')->where('organization_id', $organizationId)->delete();
        $deletedCounts[] = ['Booking Deposits', $count];
        $this->line("  ✓ Đã xóa {$count} booking deposits");
        
        // 12. Xóa Deposit Refunds
        $count = DB::table('deposit_refunds')->where('organization_id', $organizationId)->delete();
        $deletedCounts[] = ['Deposit Refunds', $count];
        $this->line("  ✓ Đã xóa {$count} deposit refunds");
        
        // 13. Xóa Leads
        $count = DB::table('leads')->where('organization_id', $organizationId)->delete();
        $deletedCounts[] = ['Leads', $count];
        $this->line("  ✓ Đã xóa {$count} leads");
        
        // 14. Xóa Lease Service Set Items (trước lease service sets)
        $leaseServiceSetIds = DB::table('lease_service_sets')->where('organization_id', $organizationId)->pluck('id')->toArray();
        if (!empty($leaseServiceSetIds)) {
            $count = DB::table('lease_service_set_items')->whereIn('lease_service_set_id', $leaseServiceSetIds)->delete();
            $deletedCounts[] = ['Lease Service Set Items', $count];
            $this->line("  ✓ Đã xóa {$count} lease service set items");
        }
        
        // 15. Xóa Lease Service Sets
        $count = DB::table('lease_service_sets')->where('organization_id', $organizationId)->delete();
        $deletedCounts[] = ['Lease Service Sets', $count];
        $this->line("  ✓ Đã xóa {$count} lease service sets");
        
        // 16. Xóa Lease Residents (trước leases)
        $leaseIds = DB::table('leases')->where('organization_id', $organizationId)->pluck('id')->toArray();
        if (!empty($leaseIds)) {
            $count = DB::table('lease_residents')->whereIn('lease_id', $leaseIds)->delete();
            $deletedCounts[] = ['Lease Residents', $count];
            $this->line("  ✓ Đã xóa {$count} lease residents");
        }
        
        // 17. Xóa Leases
        $count = DB::table('leases')->where('organization_id', $organizationId)->delete();
        $deletedCounts[] = ['Leases', $count];
        $this->line("  ✓ Đã xóa {$count} leases");
        
        // 18. Xóa Master Leases
        $count = DB::table('master_leases')->where('organization_id', $organizationId)->delete();
        $deletedCounts[] = ['Master Leases', $count];
        $this->line("  ✓ Đã xóa {$count} master leases");
        
        // 19. Xóa Units (thông qua properties)
        $propertyIds = DB::table('properties')->where('organization_id', $organizationId)->pluck('id')->toArray();
        if (!empty($propertyIds)) {
            $count = DB::table('units')->whereIn('property_id', $propertyIds)->delete();
            $deletedCounts[] = ['Units', $count];
            $this->line("  ✓ Đã xóa {$count} units");
        }
        
        // 20. Xóa Properties
        $count = DB::table('properties')->where('organization_id', $organizationId)->delete();
        $deletedCounts[] = ['Properties', $count];
        $this->line("  ✓ Đã xóa {$count} properties");
        
        // 21. Xóa Property Types
        $count = DB::table('property_types')->where('organization_id', $organizationId)->delete();
        $deletedCounts[] = ['Property Types', $count];
        $this->line("  ✓ Đã xóa {$count} property types");
        
        // 22. Xóa Services
        $count = DB::table('services')->where('organization_id', $organizationId)->delete();
        $deletedCounts[] = ['Services', $count];
        $this->line("  ✓ Đã xóa {$count} services");
        
        // 23. Xóa Organization User Capabilities (trước organization_users)
        $orgUserIds = DB::table('organization_users')->where('organization_id', $organizationId)->pluck('id')->toArray();
        if (!empty($orgUserIds)) {
            $count = DB::table('organization_user_capabilities')->whereIn('organization_user_id', $orgUserIds)->delete();
            $deletedCounts[] = ['Organization User Capabilities', $count];
            $this->line("  ✓ Đã xóa {$count} organization user capabilities");
        }
        
        // 24. Xóa Organization Users
        $count = DB::table('organization_users')->where('organization_id', $organizationId)->delete();
        $deletedCounts[] = ['Organization Users', $count];
        $this->line("  ✓ Đã xóa {$count} organization users");
        
        // 25. Xóa Subscription Invoices (trước organization_subscriptions)
        $subIds = DB::table('organization_subscriptions')->where('organization_id', $organizationId)->pluck('id')->toArray();
        if (!empty($subIds)) {
            $count = DB::table('subscription_invoices')->whereIn('organization_subscription_id', $subIds)->delete();
            $deletedCounts[] = ['Subscription Invoices', $count];
            $this->line("  ✓ Đã xóa {$count} subscription invoices");
        }
        
        // 26. Xóa Organization Subscriptions
        $count = DB::table('organization_subscriptions')->where('organization_id', $organizationId)->delete();
        $deletedCounts[] = ['Organization Subscriptions', $count];
        $this->line("  ✓ Đã xóa {$count} organization subscriptions");
        
        // 27. Xóa Organization Email Settings
        $count = DB::table('organization_email_settings')->where('organization_id', $organizationId)->delete();
        $deletedCounts[] = ['Organization Email Settings', $count];
        $this->line("  ✓ Đã xóa {$count} organization email settings");
        
        // 28. Xóa Organization Banking
        $count = DB::table('organization_banking')->where('organization_id', $organizationId)->delete();
        $deletedCounts[] = ['Organization Banking', $count];
        $this->line("  ✓ Đã xóa {$count} organization banking records");
        
        // 29. Xóa Salary Advances
        $count = DB::table('salary_advances')->where('organization_id', $organizationId)->delete();
        $deletedCounts[] = ['Salary Advances', $count];
        $this->line("  ✓ Đã xóa {$count} salary advances");
        
        // 30. Xóa Payroll Cycles
        $count = DB::table('payroll_cycles')->where('organization_id', $organizationId)->delete();
        $deletedCounts[] = ['Payroll Cycles', $count];
        $this->line("  ✓ Đã xóa {$count} payroll cycles");
        
        // 31. Xóa Salary Contracts
        $count = DB::table('salary_contracts')->where('organization_id', $organizationId)->delete();
        $deletedCounts[] = ['Salary Contracts', $count];
        $this->line("  ✓ Đã xóa {$count} salary contracts");
        
        // 32. Xóa Commission Events
        $count = DB::table('commission_events')->where('organization_id', $organizationId)->delete();
        $deletedCounts[] = ['Commission Events', $count];
        $this->line("  ✓ Đã xóa {$count} commission events");
        
        // 33. Xóa Commission Policies
        $count = DB::table('commission_policies')->where('organization_id', $organizationId)->delete();
        $deletedCounts[] = ['Commission Policies', $count];
        $this->line("  ✓ Đã xóa {$count} commission policies");
        
        // 34. Xóa Payment Cycles
        $count = DB::table('payment_cycles')->where('organization_id', $organizationId)->delete();
        $deletedCounts[] = ['Payment Cycles', $count];
        $this->line("  ✓ Đã xóa {$count} payment cycles");
        
        // 35. Xóa Company Invoices
        $count = DB::table('company_invoices')->where('organization_id', $organizationId)->delete();
        $deletedCounts[] = ['Company Invoices', $count];
        $this->line("  ✓ Đã xóa {$count} company invoices");
        
        // 36. Xóa Vendors
        $count = DB::table('vendors')->where('organization_id', $organizationId)->delete();
        $deletedCounts[] = ['Vendors', $count];
        $this->line("  ✓ Đã xóa {$count} vendors");
        
        // 37. Cuối cùng: Xóa Organization
        $organization = Organization::withTrashed()->find($organizationId);
        if ($organization) {
            if ($force) {
                $organization->forceDelete();
                $this->line("  ✓ Đã force delete organization");
            } else {
                $organization->delete();
                $this->line("  ✓ Đã soft delete organization");
            }
            $deletedCounts[] = ['Organization', 1];
        }
        
        return $deletedCounts;
    }
}

