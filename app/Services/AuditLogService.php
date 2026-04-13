<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Service: AuditLogService
 * 
 * MỤC ĐÍCH:
 * Service quản lý audit trail (lịch sử thay đổi) cho tất cả models trong hệ thống - ghi lại mọi thay đổi
 * (created, updated, deleted) với thông tin chi tiết: ai làm, khi nào, thay đổi gì, trước và sau như thế nào
 * 
 * LUỒNG XỬ LÝ CHÍNH:
 * 1. log(): Ghi audit log cho bất kỳ action nào (created, updated, deleted) → Lưu vào database
 * 2. logCreated(): Wrapper cho log() với action 'created' → Dùng trong model observers
 * 3. logUpdated(): Wrapper cho log() với action 'updated' → Dùng trong model observers
 * 4. logDeleted(): Wrapper cho log() với action 'deleted' → Dùng trong model observers
 * 5. getOrganizationId(): Tự động xác định organization_id từ entity data → Dùng để phân loại logs
 * 6. getLogsForEntity(): Lấy audit logs cho một entity cụ thể → Dùng để hiển thị lịch sử
 * 7. getLogsForOrganization(): Lấy audit logs cho một organization → Dùng để báo cáo
 * 8. getLogsWithFilters(): Lấy audit logs với filters → Dùng để tìm kiếm và lọc
 * 9. getStatistics(): Lấy thống kê audit logs → Dùng để báo cáo
 * 10. cleanupOldLogs(): Dọn dẹp logs cũ → Dùng cho maintenance
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Model: AuditLog (bảng audit_logs) - Lấy audit logs
 * - Các models khác: Để xác định organization_id và entity type
 * 
 * DỮ LIỆU GHI VÀO:
 * - Bảng audit_logs: Ghi lại mọi thay đổi (created, updated, deleted)
 * - Logs: Ghi log lỗi khi không thể ghi audit log
 * 
 * LƯU Ý:
 * - Chỉ ghi log khi có thay đổi thực sự (có changes) hoặc là action created/deleted
 * - Tự động xác định organization_id từ entity data hoặc từ actor's organization
 * - Hỗ trợ nhiều entity types: lease, invoice, ticket, payment, user, etc.
 * - Có xử lý đặc biệt cho các entity types phức tạp: invoiceitem, leaseserviceset, user
 * - Lưu before_json, after_json, changes_json để có thể xem lại chi tiết
 */
class AuditLogService
{
    /**
     * Ghi audit trail cho bất kỳ model nào
     * 
     * MỤC ĐÍCH:
     * Ghi lại lịch sử thay đổi (audit trail) cho bất kỳ entity nào trong hệ thống - lưu thông tin:
     * ai làm (actor_id), khi nào (created_at), thay đổi gì (action, changes), trước và sau như thế nào (before_json, after_json)
     * 
     * INPUT:
     * - action: Loại action (created, updated, deleted)
     * - entityType: Loại entity (lease, invoice, ticket, payment, user, etc.)
     * - entityId: ID của entity
     * - before: Dữ liệu gốc trước khi thay đổi (cho updated/deleted)
     * - after: Dữ liệu mới sau khi thay đổi (cho created/updated)
     * - changes: Mảng các trường đã thay đổi (cho updated)
     * - organizationId: ID của organization (nếu không có sẽ tự động xác định)
     * 
     * OUTPUT:
     * - bool: true nếu ghi thành công, false nếu thất bại
     * - Database: Tạo audit log record mới
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra có thay đổi thực sự không (có changes hoặc là created/deleted) → Chỉ ghi log khi có thay đổi
     * 2. Xác định entity ID (từ parameter hoặc từ after/before data) → Đảm bảo có entity ID
     * 3. Xác định organization_id (từ parameter hoặc tự động từ entity data) → Dùng để phân loại logs
     * 4. Tạo audit log record với thông tin đầy đủ → Lưu vào database
     * 5. Trả về true/false
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Auth::id(): Lấy ID của user đang thực hiện action
     * - request(): Lấy IP address và user agent
     * 
     * DỮ LIỆU GHI VÀO:
     * - Bảng audit_logs: Tạo audit log record mới
     * - Logs: Ghi log warning/error nếu có lỗi
     * 
     * LƯU Ý:
     * - Chỉ ghi log khi có thay đổi thực sự (có changes) hoặc là action created/deleted
     * - Tự động xác định organization_id nếu không được cung cấp
     * - Lưu before_json, after_json, changes_json để có thể xem lại chi tiết
     */
    public function log(
        string $action,
        string $entityType,
        $entityId,
        $before = null,
        $after = null,
        array $changes = [],
        $organizationId = null
    ): bool {
        try {
            if ($action === 'created' || $action === 'deleted' || !empty($changes)) { // Chỉ ghi log khi có thay đổi thực sự → Tránh ghi log không cần thiết
                if (!$entityId) { // Nếu không có entity ID
                    $entityId = $after['id'] ?? ($before['id'] ?? null); // Lấy từ after hoặc before data → Đảm bảo có entity ID
                }
                
                if (!$entityId) { // Nếu vẫn không có entity ID
                    Log::warning('Cannot log audit: entity ID not available', [
                        'action' => $action,
                        'entity_type' => $entityType,
                        'before' => $before ? 'has_data' : 'null',
                        'after' => $after ? 'has_data' : 'null'
                    ]); // Ghi log warning → Để debug
                    return false; // Trả về false → Không thể ghi log
                }
                
                if (!$organizationId) { // Nếu không có organization ID
                    $organizationId = $this->getOrganizationId($entityType, $entityId, $before, $after); // Tự động xác định → Dùng để phân loại logs
                }
                
                AuditLog::create([
                    'actor_id' => Auth::id(), // ID của user đang thực hiện action → Biết ai làm
                    'organization_id' => $organizationId, // ID của organization → Dùng để phân loại logs
                    'action' => $entityType . '_' . $action, // Action dạng "lease_created" → Dễ tìm kiếm
                    'entity_type' => $entityType, // Loại entity → Dùng để filter
                    'entity_id' => $entityId, // ID của entity → Dùng để tìm logs của entity này
                    'before_json' => $before ? json_encode($before) : null, // Dữ liệu trước khi thay đổi → Có thể xem lại
                    'after_json' => $after ? json_encode($after) : null, // Dữ liệu sau khi thay đổi → Có thể xem lại
                    'changes_json' => !empty($changes) ? json_encode($changes) : null, // Các trường đã thay đổi → Dễ xem thay đổi gì
                    'ip_address' => request()?->ip(), // IP address → Dùng để tracking
                    'user_agent' => request()?->userAgent(), // User agent → Dùng để tracking
                    'created_at' => now(), // Thời gian tạo → Biết khi nào thay đổi
                ]); // Tạo audit log record → Lưu vào database
                
                return true; // Trả về true → Ghi log thành công
            }
            
            return false; // Trả về false → Không có thay đổi, không cần ghi log
        } catch (\Exception $e) {
            Log::error('Failed to log audit: ' . $e->getMessage(), [
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'error' => $e->getTraceAsString()
            ]); // Ghi log error → Để debug
            
            return false; // Trả về false → Ghi log thất bại
        }
    }
    
    /**
     * Tự động xác định organization_id từ entity data
     * 
     * MỤC ĐÍCH:
     * Tự động xác định organization_id cho audit log từ entity data - ưu tiên từ after data, sau đó before data,
     * sau đó load model từ database, và cuối cùng lấy từ actor's organization
     * 
     * INPUT:
     * - entityType: Loại entity (lease, invoice, ticket, user, etc.)
     * - entityId: ID của entity
     * - before: Dữ liệu gốc trước khi thay đổi
     * - after: Dữ liệu mới sau khi thay đổi
     * 
     * OUTPUT:
     * - int|null: ID của organization hoặc null nếu không tìm thấy
     * 
     * LUỒNG XỬ LÝ:
     * 1. Ưu tiên lấy từ after data (new state) → Dữ liệu mới nhất
     * 2. Nếu không có, lấy từ before data (old state) → Dữ liệu cũ
     * 3. Nếu không có, load model từ database → Tìm trong database
     * 4. Xử lý các trường hợp đặc biệt: invoiceitem, leaseserviceset, leaseservicesetitem, user
     * 5. Nếu vẫn không có, lấy từ actor's organization → Fallback
     * 6. Trả về organization_id hoặc null
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Các models: Để lấy organization_id từ entity
     * - Bảng organization_users: Để lấy organization từ user
     * 
     * DỮ LIỆU GHI VÀO:
     * - Logs: Ghi log debug nếu không thể load model
     * 
     * LƯU Ý:
     * - Có xử lý đặc biệt cho các entity types phức tạp: invoiceitem (lấy từ invoice), leaseserviceset (lấy từ lease),
     *   leaseservicesetitem (lấy từ lease service set), user (lấy từ organization_users)
     * - Fallback về actor's organization nếu không tìm thấy
     */
    private function getOrganizationId(string $entityType, $entityId, $before = null, $after = null): ?int
    {
        if ($after && isset($after['organization_id'])) { // Ưu tiên lấy từ after data → Dữ liệu mới nhất
            return $after['organization_id'];
        }
        
        if ($before && isset($before['organization_id'])) { // Lấy từ before data → Dữ liệu cũ
            return $before['organization_id'];
        }
        
        try {
            $modelClass = $this->getModelClass($entityType); // Lấy model class từ entity type → Dùng để load model
            if ($modelClass && class_exists($modelClass)) {
                $model = $modelClass::withoutGlobalScopes()->find($entityId); // Load model → Tránh global scopes
                if ($model) {
                    if ($entityType === 'invoiceitem') { // InvoiceItem: lấy organization_id từ invoice → InvoiceItem không có organization_id trực tiếp
                        if (isset($model->invoice_id)) {
                            $invoice = \App\Models\Invoice::find($model->invoice_id);
                            if ($invoice && isset($invoice->organization_id)) {
                                return $invoice->organization_id;
                            }
                        }
                    } elseif ($entityType === 'leaseserviceset') { // LeaseServiceSet: lấy từ model hoặc từ lease → Có thể không có organization_id trực tiếp
                        if (isset($model->organization_id)) {
                            return $model->organization_id;
                        }
                        $lease = \App\Models\Lease::where('lease_services_id', $entityId)->first(); // Lấy từ lease đầu tiên → Fallback
                        if ($lease && isset($lease->organization_id)) {
                            return $lease->organization_id;
                        }
                    } elseif ($entityType === 'leaseservicesetitem') { // LeaseServiceSetItem: lấy từ lease service set hoặc từ lease → Nested relationship
                        if (isset($model->lease_service_set_id)) {
                            $leaseServiceSet = \App\Models\LeaseServiceSet::find($model->lease_service_set_id);
                            if ($leaseServiceSet) {
                                if (isset($leaseServiceSet->organization_id)) {
                                    return $leaseServiceSet->organization_id;
                                }
                                $lease = \App\Models\Lease::where('lease_services_id', $leaseServiceSet->id)->first(); // Lấy từ lease → Fallback
                                if ($lease && isset($lease->organization_id)) {
                                    return $lease->organization_id;
                                }
                            }
                        }
                    } elseif ($entityType === 'user') { // User: lấy từ organization_users pivot table → User có thể thuộc nhiều organizations
                        try {
                            $userOrganization = DB::table('organization_users')
                                ->where('user_id', $entityId)
                                ->where('status', 'active')
                                ->whereNull('deleted_at')
                                ->orderBy('created_at', 'asc')
                                ->first(); // Lấy organization đầu tiên → Ưu tiên organization cũ nhất
                            if ($userOrganization) {
                                return $userOrganization->organization_id;
                            }
                        } catch (\Exception $e) {
                            // Không thể lấy organization, bỏ qua
                        }
                    } elseif (isset($model->organization_id)) { // Các entity types khác: lấy trực tiếp từ model
                        return $model->organization_id;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::debug('AuditLogService getOrganizationId: Could not load model', [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'error' => $e->getMessage()
            ]); // Ghi log debug → Để debug
        }
        
        if (Auth::check()) { // Fallback: lấy từ actor's organization → Đảm bảo luôn có organization_id
            $user = Auth::user();
            try {
                $userOrganization = DB::table('organization_users')
                    ->where('user_id', $user->id)
                    ->where('status', 'active')
                    ->whereNull('deleted_at')
                    ->first(); // Lấy organization của user đang thực hiện action
                if ($userOrganization) {
                    return $userOrganization->organization_id;
                }
            } catch (\Exception $e) {
                // Không thể lấy organization, bỏ qua
            }
        }
        
        return null; // Trả về null nếu không tìm thấy → Có thể không có organization_id
    }
    
    /**
     * Get model class name from entity type
     */
    private function getModelClass(string $entityType): ?string
    {
        $modelMap = [
            'lease' => \App\Models\Lease::class,
            'ticket' => \App\Models\Ticket::class,
            'invoice' => \App\Models\Invoice::class,
            'payment' => \App\Models\Payment::class,
            'viewing' => \App\Models\Viewing::class,
            'bookingdeposit' => \App\Models\BookingDeposit::class,
            'ticketlog' => \App\Models\TicketLog::class,
            'companyinvoice' => \App\Models\CompanyInvoice::class,
            'review' => \App\Models\Review::class,
            'reviewreply' => \App\Models\ReviewReply::class,
            'payrollpayslip' => \App\Models\PayrollPayslip::class,
            'salaryadvance' => \App\Models\SalaryAdvance::class,
            'leaseservice' => \App\Models\LeaseServiceSet::class, // DEPRECATED: Use leaseserviceset instead
            'leaseserviceset' => \App\Models\LeaseServiceSet::class,
            'leaseservicesetitem' => \App\Models\LeaseServiceSetItem::class,
            'masterlease' => \App\Models\MasterLease::class,
            'property' => \App\Models\Property::class,
            'lead' => \App\Models\Lead::class,
            'unit' => \App\Models\Unit::class,
            'document' => \App\Models\Document::class,
            'commission' => \App\Models\Commission::class,
            'commissionevent' => \App\Models\CommissionEvent::class,
            'commissionpolicy' => \App\Models\CommissionPolicy::class,
            'meter' => \App\Models\Meter::class,
            'meterreading' => \App\Models\MeterReading::class,
            'cashoutflow' => \App\Models\CashOutflow::class,
            'vendor' => \App\Models\Vendor::class,
            'service' => \App\Models\Service::class,
            'organization' => \App\Models\Organization::class,
            'organizationbanking' => \App\Models\OrganizationBanking::class,
            'organizationemailsetting' => \App\Models\OrganizationEmailSetting::class,
            'organizationsubscription' => \App\Models\OrganizationSubscription::class,
            'subscriptionplan' => \App\Models\SubscriptionPlan::class,
            'subscriptioninvoice' => \App\Models\SubscriptionInvoice::class,
            'paymentmethod' => \App\Models\PaymentMethod::class,
            'paymentcycle' => \App\Models\PaymentCycle::class,
            'leaseresident' => \App\Models\LeaseResident::class,
            'salarycontract' => \App\Models\SalaryContract::class,
            'payrollcycle' => \App\Models\PayrollCycle::class,
            'depositrefund' => \App\Models\DepositRefund::class,
            'user' => \App\Models\User::class,
        ];
        
        return $modelMap[strtolower($entityType)] ?? null;
    }

    /**
     * Log created event
     */
    public function logCreated($model): bool
    {
        $entityType = $this->getEntityType($model);
        $attributes = $model->getAttributes();
        $organizationId = $attributes['organization_id'] ?? null;
        
        return $this->log('created', $entityType, $model->id, null, $attributes, [], $organizationId);
    }

    /**
     * Log updated event
     */
    public function logUpdated($model, array $dirtyFields = null): bool
    {
        $entityType = $this->getEntityType($model);
        $changes = $dirtyFields ?? $model->getDirty();
        $attributes = $model->getAttributes();
        $organizationId = $attributes['organization_id'] ?? null;
        
        return $this->log(
            'updated',
            $entityType,
            $model->id,
            $model->getOriginal(),
            $attributes,
            $changes,
            $organizationId
        );
    }

    /**
     * Log deleted event
     */
    public function logDeleted($model): bool
    {
        $entityType = $this->getEntityType($model);
        $attributes = $model->getAttributes();
        $organizationId = $attributes['organization_id'] ?? null;
        
        return $this->log('deleted', $entityType, $model->id, $attributes, null, [], $organizationId);
    }

    /**
     * Get entity type from model
     */
    private function getEntityType($model): string
    {
        return strtolower(class_basename($model));
    }

    /**
     * Get audit logs for a specific entity
     */
    public function getLogsForEntity(string $entityType, $entityId, int $perPage = 20, $organizationId = null)
    {
        $query = AuditLog::forEntity($entityType, $entityId)
            ->with(['actor', 'organization']);
        
        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }
        
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }
    
    /**
     * Get audit logs for an organization
     */
    public function getLogsForOrganization($organizationId, int $perPage = 20)
    {
        return AuditLog::where('organization_id', $organizationId)
            ->with(['actor', 'organization'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get audit logs for a lease
     */
    public function getLeaseLogs($leaseId, int $perPage = 20)
    {
        return $this->getLogsForEntity('lease', $leaseId, $perPage);
    }

    /**
     * Get audit logs by action
     */
    public function getLogsByAction(string $action, int $perPage = 20)
    {
        return AuditLog::forAction($action)
            ->with('actor')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get audit logs by actor
     */
    public function getLogsByActor($actorId, int $perPage = 20)
    {
        return AuditLog::forActor($actorId)
            ->with('actor')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get recent audit logs
     */
    public function getRecentLogs(int $limit = 50)
    {
        return AuditLog::with('actor')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get audit logs with filters
     */
    public function getLogsWithFilters(array $filters = [], int $perPage = 20)
    {
        $query = AuditLog::with(['actor', 'organization']);

        if (isset($filters['entity_type'])) {
            $query->where('entity_type', $filters['entity_type']);
        }

        if (isset($filters['entity_id'])) {
            $query->where('entity_id', $filters['entity_id']);
        }

        if (isset($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (isset($filters['actor_id'])) {
            $query->where('actor_id', $filters['actor_id']);
        }

        if (isset($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get summary statistics for audit logs
     */
    public function getStatistics(array $filters = []): array
    {
        $query = AuditLog::query();

        if (isset($filters['entity_type'])) {
            $query->where('entity_type', $filters['entity_type']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return [
            'total_logs' => $query->count(),
            'by_action' => $query->clone()
                ->select('action', DB::raw('count(*) as count'))
                ->groupBy('action')
                ->pluck('count', 'action')
                ->toArray(),
            'by_entity_type' => $query->clone()
                ->select('entity_type', DB::raw('count(*) as count'))
                ->groupBy('entity_type')
                ->pluck('count', 'entity_type')
                ->toArray(),
            'by_actor' => $query->clone()
                ->whereNotNull('actor_id')
                ->select('actor_id', DB::raw('count(*) as count'))
                ->groupBy('actor_id')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->pluck('count', 'actor_id')
                ->toArray(),
        ];
    }

    /**
     * Clean up old audit logs (older than specified days)
     */
    public function cleanupOldLogs(int $days = 365): int
    {
        $cutoffDate = now()->subDays($days);
        
        return AuditLog::where('created_at', '<', $cutoffDate)->delete();
    }
}
