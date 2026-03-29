<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Lease;
use App\Models\Unit;
use App\Models\User;
use App\Models\Organization;
// use App\Models\Property; // Commented out to use fully qualified name
use App\Models\Service;
use App\Models\PaymentCycle;
use App\Models\LeaseServiceSet;
use App\Models\LeaseServiceSetItem;
use App\Traits\ChecksCapabilities;
use App\Traits\FiltersByOwnership;
use App\Services\Subscription\PlanLimitChecker;
use App\Services\ImageService;
use App\Helpers\SequenceGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Controller quản lý Leases (Hợp đồng thuê) trong tổ chức (Contract module)
 * 
 * MỤC ĐÍCH:
 * - Quản lý danh sách leases (hợp đồng thuê giữa tenant và unit)
 * - Lease là hợp đồng thuê một unit cụ thể từ tenant
 * - Manager: Xem tất cả leases trong organization
 * - Agent: Chỉ xem leases được assign cho mình (ownership filtering)
 * - Quản lý thông tin lease: contract_no, tenant, unit, agent, dates, rent_amount, deposit_amount
 * - Quản lý payment cycle, lease service set, invoices, payments, documents
 * - Tính toán statistics: draft, active, expired, terminated, due_for_invoicing
 * - Hỗ trợ filter, search, sort, pagination với HTMX/AJAX
 * - Kiểm tra subscription plan limits khi tạo lease mới
 * - Tạo invoices tự động (cycle invoices, rent invoices)
 * - Hỗ trợ renew lease, terminate lease
 * 
 * LUỒNG XỬ LÝ:
 * 1. index(): Hiển thị danh sách leases với filters (search, status, property, unit, tenant, agent, date range)
 *    - Filter theo organization_id, ownership (agent chỉ xem leases của mình)
 *    - Tính statistics (draft, active, expired, terminated, due_for_invoicing) bằng aggregation
 *    - Tính due_for_invoicing: Kiểm tra active leases có missing invoices cho billing cycles
 *    - Hỗ trợ HTMX/AJAX requests để update table và stats
 *    - Sort theo các fields được phép
 *    - Eager load relationships (unit, property, tenant, agent, paymentCycle, leaseServiceSet)
 * 2. create(): Hiển thị form tạo lease mới
 *    - Load properties, units, tenants, agents, payment cycles, lease service sets
 *    - Generate preview contract number
 *    - Check subscription plan limit (max_leases)
 * 3. store(): Tạo lease mới với validation, check subscription limit
 *    - Validate tất cả fields (unit_id, tenant_id, agent_id, dates, rent_amount, etc.)
 *    - Check subscription plan limit (max_leases)
 *    - Check unit availability (không có lease active khác)
 *    - Generate contract number nếu chưa có
 *    - Create lease với status 'draft' hoặc 'active'
 *    - Update unit status (occupied nếu active)
 *    - Create initial invoice nếu cần
 *    - Sử dụng transaction để đảm bảo data consistency
 * 4. show(): Hiển thị chi tiết lease (unit, property, tenant, agent, invoices, payments, documents, residents)
 * 5. edit(): Hiển thị form edit lease với tất cả data
 * 6. update(): Cập nhật lease (dates, rent_amount, deposit_amount, payment_cycle_id, lease_services_id, etc.)
 *    - Validate và update lease
 *    - Check permission: chỉ update leases của organization
 *    - Handle status changes (draft -> active, active -> terminated/expired)
 *    - Update unit status nếu cần
 * 7. terminate(): Chấm dứt lease trước hạn
 *    - Validate termination_date, termination_reason
 *    - Update lease status = 'terminated'
 *    - Update unit status = 'available'
 *    - Cancel pending invoices nếu cần
 * 8. renew(): Gia hạn lease (tạo lease mới từ lease cũ)
 *    - Validate dates, rent_amount
 *    - Create new lease với data từ lease cũ
 *    - Update old lease status
 * 9. destroy(): Xóa lease (soft delete)
 *    - Check permission: chỉ delete leases của organization
 *    - Soft delete lease và related records
 * 10. createInvoice(): Tạo invoice cho lease (manual)
 * 11. createCycleInvoice(): Tạo cycle invoice tự động cho lease
 *    - Tính toán billing cycle, billing date
 *    - Tạo invoice với items (rent, services)
 *    - Link invoice với lease
 * 12. updateStatus(): API endpoint cập nhật status (AJAX)
 * 13. uploadDocument(): API endpoint upload document cho lease (AJAX)
 * 14. deleteDocument(): API endpoint xóa document (AJAX)
 * 15. getNextContractNumber(): API endpoint lấy contract number tiếp theo (AJAX)
 * 16. getUnits(): API endpoint lấy units của property (AJAX)
 * 17. getPropertyDetails(): API endpoint lấy property details (AJAX)
 * 18. getPropertyPaymentCycle(): API endpoint lấy payment cycle của property (AJAX)
 * 19. getPropertyLeaseServiceSet(): API endpoint lấy lease service set của property (AJAX)
 * 20. deleteResident(): API endpoint xóa resident khỏi lease (AJAX)
 * 
 * ENDPOINTS:
 * - GET /staff/leases: Danh sách leases (hỗ trợ HTMX/AJAX)
 * - GET /staff/leases/create: Form tạo lease
 * - POST /staff/leases: Tạo lease mới
 * - GET /staff/leases/{id}: Chi tiết lease
 * - GET /staff/leases/{id}/edit: Form edit lease
 * - PUT/PATCH /staff/leases/{id}: Cập nhật lease
 * - DELETE /staff/leases/{id}: Xóa lease
 * - POST /staff/leases/{id}/terminate: Chấm dứt lease
 * - POST /staff/leases/{id}/renew: Gia hạn lease
 * - POST /staff/leases/{id}/invoice: Tạo invoice (manual)
 * - POST /staff/leases/{id}/cycle-invoice: Tạo cycle invoice
 * - POST /staff/leases/{id}/status: Cập nhật status (AJAX)
 * - POST /staff/leases/{id}/document: Upload document (AJAX)
 * - DELETE /staff/leases/{id}/document/{documentId}: Xóa document (AJAX)
 * - GET /staff/leases/next-contract-number: Lấy contract number (AJAX)
 * - GET /staff/leases/properties/{propertyId}/units: Lấy units (AJAX)
 * - GET /staff/leases/properties/{propertyId}/details: Lấy property details (AJAX)
 * - GET /staff/leases/properties/{propertyId}/payment-cycle: Lấy payment cycle (AJAX)
 * - GET /staff/leases/properties/{propertyId}/lease-service-set: Lấy lease service set (AJAX)
 * - DELETE /staff/leases/{leaseId}/residents/{residentId}: Xóa resident (AJAX)
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Models: Lease, Unit, Property, User (tenant, agent), Organization, PaymentCycle, LeaseServiceSet, Invoice, Payment, BookingDeposit, Document
 * - Database tables: leases, units, properties, users, user_profiles, payment_cycles, lease_service_sets, invoices, payments, booking_deposits, documents, lease_residents
 * - Request: search, status, property_id, unit_id, tenant_id, agent_id, date_from, date_to, sort_by, sort_order
 * 
 * DỮ LIỆU GHI VÀO:
 * - Database tables: leases, units (status), invoices, documents, lease_residents
 * - Storage: Documents được upload qua ImageService
 * - Không có thay đổi properties, users, payment_cycles, lease_service_sets (chỉ đọc)
 * 
 * TRAITS SỬ DỤNG:
 * - ChecksCapabilities: Kiểm tra capabilities (contract.access, contract.lease.view, contract.lease.create, etc.)
 * - FiltersByOwnership: Filter theo ownership (view_all vs view_own cho agent)
 * 
 * SERVICES SỬ DỤNG:
 * - PlanLimitChecker: Kiểm tra subscription plan limits (max_leases)
 * - ImageService: Upload, delete, get URL cho lease documents
 * - SequenceGenerator: Generate contract numbers
 * 
 * CAPABILITY CHECKING:
 * - contract.access: Quyền truy cập module Contract (required cho tất cả methods)
 * - contract.lease.view: Quyền xem danh sách leases (index, show)
 * - contract.lease.create: Quyền tạo lease (create, store) - chỉ manager
 * - contract.lease.update: Quyền cập nhật lease (edit, update, renew, updateStatus) - chỉ manager
 * - contract.lease.delete: Quyền xóa lease (destroy, terminate) - chỉ manager
 * - contract.lease.invoice: Quyền tạo invoice (createInvoice, createCycleInvoice) - chỉ manager
 * 
 * OWNERSHIP FILTERING:
 * - Manager: Xem tất cả leases trong organization (canViewAll = true)
 * - Agent: Chỉ xem leases được assign cho mình (canViewAll = false, filter theo agent_id)
 * - Sử dụng FiltersByOwnership trait để handle logic
 * 
 * QUERY OPTIMIZATION:
 * - Sử dụng JOINs với units, properties, users để filter và lấy data
 * - Sử dụng indexes: idx_leases_org_deleted_status, idx_leases_agent_id, idx_leases_deleted_at_status
 * - Eager loading relationships để tránh N+1 queries
 * - Tính statistics bằng aggregation (COUNT) thay vì multiple queries
 * - Validate sort fields để prevent SQL injection
 * - Sử dụng whereIn() với agent_id để filter hiệu quả
 * 
 * SUBSCRIPTION LIMITS:
 * - Kiểm tra max_leases limit khi tạo lease mới
 * - Sử dụng PlanLimitChecker để check limits và get current count
 * - Trả về error message với current/limit nếu vượt quá limit
 * 
 * LEASE STATUS:
 * - draft: Hợp đồng đang soạn thảo, chưa có hiệu lực
 * - active: Hợp đồng đang có hiệu lực (start_date <= now <= end_date)
 * - expired: Hợp đồng đã hết hạn (end_date < now)
 * - terminated: Hợp đồng đã bị chấm dứt trước hạn
 * 
 * LEASE RELATIONSHIPS:
 * - Unit: Một lease thuộc về một unit
 * - Property: Một lease có property (qua unit)
 * - Tenant: Một lease có một tenant (User với role 'tenant')
 * - Agent: Một lease có một agent (User với role 'agent')
 * - BookingDeposit: Một lease có thể được tạo từ booking deposit
 * - PaymentCycle: Một lease có payment cycle (hoặc dùng default của property/organization)
 * - LeaseServiceSet: Một lease có lease service set (hoặc dùng default của property/organization)
 * - Invoices: Một lease có nhiều invoices (rent, services, deposits)
 * - Payments: Một lease có nhiều payments (qua invoices)
 * - Documents: Một lease có nhiều documents
 * - Residents: Một lease có nhiều residents (người ở cùng)
 * 
 * CONTRACT NUMBER:
 * - contract_no được generate tự động nếu không được cung cấp
 * - Sử dụng SequenceGenerator để tạo số hợp đồng unique trong organization
 * - Format: L-{YYYY}-{SEQUENCE}
 * 
 * UNIT AVAILABILITY:
 * - Khi tạo lease mới, check unit không có lease active khác
 * - Khi lease active, unit status = 'occupied'
 * - Khi lease terminated/expired, unit status = 'available'
 * 
 * INVOICE GENERATION:
 * - Initial invoice: Tạo khi lease được activate (rent + deposit + services)
 * - Cycle invoices: Tạo tự động theo billing cycle (rent + services)
 * - Manual invoices: Tạo thủ công bởi manager
 * - Invoice items: rent, services (từ lease service set), deposits, fees
 * 
 * DUE_FOR_INVOICING CALCULATION:
 * - Kiểm tra active leases có missing invoices cho billing cycles
 * - Tính toán billing cycle dựa trên payment cycle và start_date
 * - Check nếu invoice đã tồn tại cho cycle hiện tại
 * - Skip cycle 1 (first cycle) - không alert cho first invoice
 * - Chỉ count leases có missing invoices (chưa có invoice cho cycle hiện tại)
 * 
 * PAYMENT CYCLE:
 * - payment_cycle_id: ID của payment cycle (hoặc null để dùng default)
 * - getEffectivePaymentCycle(): Lấy payment cycle từ lease -> property -> organization default
 * - Billing cycle: Số tháng trong chu kỳ thanh toán
 * - Billing day: Ngày thanh toán trong tháng
 * 
 * LEASE SERVICE SET:
 * - lease_services_id: ID của lease service set (hoặc null để dùng default)
 * - getEffectiveLeaseServiceSet(): Lấy lease service set từ lease -> property -> organization default
 * - Services trong set được tính vào invoice (phí dịch vụ)
 * 
 * VALIDATION:
 * - unit_id: required, exists:units, unit không có lease active khác
 * - tenant_id: required, exists:users (role = 'tenant')
 * - agent_id: nullable, exists:users (role = 'agent')
 * - start_date: required, date
 * - end_date: required, date, after:start_date
 * - rent_amount: required, numeric, min:0
 * - deposit_amount: nullable, numeric, min:0
 * - payment_cycle_id: nullable, exists:payment_cycles
 * - lease_services_id: nullable, exists:lease_service_sets
 * 
 * SECURITY:
 * - Manager có quyền quản lý tất cả leases
 * - Agent chỉ có quyền xem leases được assign cho mình
 * - User chỉ có thể update/delete leases của organization
 * - Validate sort fields để prevent SQL injection
 * - Check unit availability trước khi tạo lease
 * 
 * LƯU Ý:
 * - Lease là hợp đồng thuê một unit cụ thể từ tenant
 * - Một unit chỉ nên có một lease active tại một thời điểm
 * - Contract number phải unique trong organization
 * - Invoices được tạo tự động theo billing cycle
 * - Statistics được tính bằng aggregation để tối ưu performance
 * - due_for_invoicing calculation phức tạp, cần check từng active lease
 * - Hỗ trợ HTMX và AJAX requests cho real-time updates
 * - Subscription limits được check trước khi tạo lease mới
 * - Documents được quản lý qua ImageService với public storage
 * - Renew lease tạo lease mới từ lease cũ (không update lease cũ)
 */
class LeaseController extends Controller
{
    use ChecksCapabilities, FiltersByOwnership;
    
    /**
     * PlanLimitChecker instance để kiểm tra subscription plan limits
     * 
     * @var \App\Services\Subscription\PlanLimitChecker
     */
    protected $limitChecker;
    
    /**
     * ImageService instance để upload, delete, get URLs cho documents
     * 
     * @var \App\Services\ImageService
     */
    protected $imageService;

    /**
     * Constructor: Inject PlanLimitChecker và ImageService dependencies
     * 
     * @param \App\Services\Subscription\PlanLimitChecker $limitChecker
     * @param \App\Services\ImageService $imageService
     */
    public function __construct(PlanLimitChecker $limitChecker, ImageService $imageService)
    {
        $this->limitChecker = $limitChecker;
        $this->imageService = $imageService;
    }

    /**
     * Hiển thị danh sách leases với filters, search, sort, pagination
     * 
     * LUỒNG XỬ LÝ:
     * 1. Kiểm tra capabilities: contract.access
     * 2. Lấy organization_id từ getCurrentOrganizationId()
     * 3. Kiểm tra ownership: canViewAll (manager xem tất cả, agent chỉ xem leases của mình)
     * 4. Build query với JOINs (units, properties, users) để lấy unit_code, property_name, tenant_name, agent_name
     * 5. Apply ownership filter: Nếu agent, chỉ lấy leases với agent_id = user->id
     * 6. Tính statistics (draft, active, expired, terminated) bằng aggregation
     * 7. Tính due_for_invoicing: Kiểm tra active leases có missing invoices cho billing cycles
     *    - Load active leases với relationships
     *    - Tính billing cycle dựa trên payment cycle và start_date
     *    - Check nếu invoice đã tồn tại cho cycle hiện tại
     *    - Skip cycle 1 (first cycle) - không alert cho first invoice
     *    - Count leases có missing invoices
     * 8. Apply filters: search, status, property_id, unit_id, tenant_id, agent_id, date range
     * 9. Apply sorting (validate sort fields)
     * 10. Paginate results
     * 11. Eager load relationships (unit, property, tenant, agent, paymentCycle, leaseServiceSet)
     * 12. Check request type (HTMX/AJAX):
     *     - HTMX: Return table partial HTML với stats update
     *     - Normal: Return view với full data
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Auth::user(): User hiện tại
     * - getCurrentOrganizationId(): Organization ID từ middleware/session
     * - Database: leases, units, properties, users, user_profiles, invoices, payment_cycles, lease_service_sets
     * - Request: search, status, property_id, unit_id, tenant_id, agent_id, date_from, date_to, sort_by, sort_order
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có, chỉ đọc dữ liệu
     * 
     * CAPABILITY CHECKING:
     * - contract.access: Quyền truy cập module Contract
     * 
     * OWNERSHIP FILTERING:
     * - Manager: Xem tất cả leases (canViewAll = true)
     * - Agent: Chỉ xem leases được assign cho mình (canViewAll = false, filter theo agent_id)
     * 
     * QUERY OPTIMIZATION:
     * - Sử dụng JOINs với units, properties, users để filter và lấy data
     * - Sử dụng indexes: idx_leases_org_deleted_status, idx_leases_agent_id, idx_leases_deleted_at_status
     * - Eager loading relationships để tránh N+1 queries
     * - Tính statistics bằng aggregation trong một query
     * - Validate sort fields để prevent SQL injection
     * 
     * STATISTICS:
     * - draft, active, expired, terminated: Đếm theo status
     * - due_for_invoicing: Số lượng active leases có missing invoices cho billing cycles
     * 
     * DUE_FOR_INVOICING CALCULATION:
     * - Kiểm tra từng active lease
     * - Tính billing cycle dựa trên payment cycle và start_date
     * - Check nếu invoice đã tồn tại cho cycle hiện tại
     * - Skip cycle 1 (first cycle) - không alert cho first invoice
     * - Count leases có missing invoices
     * 
     * FILTERS:
     * - search: Tìm kiếm theo contract_no, unit code, property name, tenant name, agent name
     * - status: Filter theo status (draft, active, expired, terminated)
     * - property_id: Filter theo property
     * - unit_id: Filter theo unit
     * - tenant_id: Filter theo tenant
     * - agent_id: Filter theo agent
     * - date_from/date_to: Filter theo start_date/end_date
     * 
     * @param \Illuminate\Http\Request $request Request chứa filters, sort, pagination
     * @return \Illuminate\View\View|\Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            
            // Get user's organization from middleware
            $userOrganizationId = $this->getCurrentOrganizationId();
            
            // Check if user has contract.access capability
            $hasContractAccess = $this->checkCapability('contract.access');
            if (!$hasContractAccess) {
                abort(403, 'Bạn không có quyền truy cập module Hợp đồng.');
            }

            // Check if user can view all leases or only own leases
            // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
            $canViewAll = $this->canViewAll('contract.lease');
            
            // Optimized query using JOINs and proper index order
            $query = Lease::select([
                'leases.*',
                'units.code as unit_code',
                'properties.name as property_name',
                'tenant_profiles.full_name as tenant_name',
                'agent_profiles.full_name as agent_name'
            ])
            ->join('units', 'leases.unit_id', '=', 'units.id')
            ->leftJoin('properties', 'units.property_id', '=', 'properties.id')
            ->leftJoin('users as tenant_users', 'leases.tenant_id', '=', 'tenant_users.id')
            ->leftJoin('user_profiles as tenant_profiles', 'tenant_users.id', '=', 'tenant_profiles.user_id')
            ->leftJoin('users as agent_users', 'leases.agent_id', '=', 'agent_users.id')
            ->leftJoin('user_profiles as agent_profiles', 'agent_users.id', '=', 'agent_profiles.user_id')
            ->where('leases.organization_id', $userOrganizationId); // Uses idx_leases_org_deleted_status
            
            // Tự động filter theo ownership nếu agent chỉ có view_own
            if ($this->shouldFilterByOwnership('contract.lease')) {
                $query->where('leases.agent_id', $user->id); // Uses idx_leases_agent_id
            }
            
            // Apply filters in optimal order: organization_id -> deleted_at -> status
            $query->whereNull('leases.deleted_at') // Uses idx_leases_deleted_at_status
                  ->whereNull('units.deleted_at') // Uses idx_units_deleted_at_property
                  ->whereNull('properties.deleted_at'); // Uses idx_properties_deleted_at_org

            // Calculate statistics FIRST from base query (before any filters)
            // Query directly from Lease model to ensure accurate statistics
            $statsQuery = Lease::where('organization_id', $userOrganizationId)
                ->whereNull('deleted_at');
            
            // For agent, only count leases created by them
            if (!$canViewAll) {
                $statsQuery->where('agent_id', $user->id);
            }
            
            // Count by status using database aggregation for accuracy
            $stats = [
                'draft' => (int) (clone $statsQuery)->where('status', 'draft')->count(),
                'active' => (int) (clone $statsQuery)->where('status', 'active')->count(),
                'expired' => (int) (clone $statsQuery)->where('status', 'expired')->count(),
                'terminated' => (int) (clone $statsQuery)->where('status', 'terminated')->count(),
                'due_for_invoicing' => 0,
            ];
            
            // Calculate due_for_invoicing - need to check each active lease
            $today = now();
            $currentDay = $today->day;
            $currentDate = $today->copy()->startOfDay();
            
            $activeLeases = (clone $statsQuery)->where('status', 'active')->get();
            $activeLeases->load([
                'unit.property', 
                'tenant', 
                'agent', 
                'organization',
                'leaseServiceSet.items.service',
                'paymentCycle'
            ]);
            
            foreach ($activeLeases as $lease) {
                $paymentCycle = $lease->getEffectivePaymentCycle();
                $billingDay = $paymentCycle?->billing_day ?? null;
                
                if (!$billingDay) {
                    continue;
                }
                
                $startDate = \Carbon\Carbon::parse($lease->start_date);
                $paymentCycleMonths = $this->getPaymentCycleMonths($lease);
                
                // Calculate which cycle we're in
                $daysSinceStart = $startDate->diffInDays($currentDate, false);
                if ($daysSinceStart < 0) {
                    continue; // Lease hasn't started yet
                }
                
                // Calculate cycle number (starting from 1)
                $cycleNumber = 1;
                $tempDate = $startDate->copy();
                
                while (true) {
                    $cycleStart = $tempDate->copy();
                    $cycleEnd = $cycleStart->copy()->addMonths($paymentCycleMonths)->subDay();
                    
                    // Determine billing date for this cycle (billing day in the first month of the cycle)
                    $billingDate = $cycleStart->copy();
                    $billingDate->day = min($billingDay, $billingDate->daysInMonth);
                    
                    // Check if we've passed the billing date for this cycle
                    if ($currentDate >= $billingDate) {
                        // Skip cycle 1 (first cycle) - don't alert for first invoice
                        if ($cycleNumber > 1) {
                            // Check if invoice exists for this cycle
                            $hasCycleInvoice = \App\Models\Invoice::where('lease_id', $lease->id)
                                ->where(function($q) {
                                    $q->where('invoice_type', 'invoice_cycle')
                                      ->orWhereNull('invoice_type');
                                })
                                ->where('issue_date', '>=', $cycleStart->format('Y-m-d'))
                                ->where('issue_date', '<=', $cycleEnd->format('Y-m-d'))
                                ->whereIn('status', ['draft', 'issued', 'paid', 'overdue'])
                                ->whereNull('deleted_at')
                                ->exists();
                            
                            $hasRentInvoice = \App\Models\Invoice::where('lease_id', $lease->id)
                                ->where('issue_date', '>=', $cycleStart->format('Y-m-d'))
                                ->where('issue_date', '<=', $cycleEnd->format('Y-m-d'))
                                ->whereIn('status', ['draft', 'issued', 'paid', 'overdue'])
                                ->whereNull('deleted_at')
                                ->whereHas('items', function($q) {
                                    $q->where('item_type', 'rent');
                                })
                                ->exists();
                            
                            // If no invoice exists for this cycle, this lease is due
                            if (!($hasCycleInvoice || $hasRentInvoice)) {
                                $stats['due_for_invoicing']++;
                                break; // Found missing invoice, no need to check further cycles
                            }
                        }
                    } else {
                        // Haven't reached billing date for this cycle yet
                        break;
                    }
                    
                    // Move to next cycle
                    $tempDate->addMonths($paymentCycleMonths);
                    $cycleNumber++;
                    
                    // Safety check: don't loop forever, max 100 cycles
                    if ($cycleNumber > 100) {
                        break;
                    }
                    
                    // If we're past the current date, stop checking
                    if ($tempDate > $currentDate) {
                        break;
                    }
                }
            }
            
            // Now apply filters to query for display
            // Search - optimized with JOIN
            $search = $request->input('search');
            if (!empty($search) && trim($search) !== '') {
                $query->where(function($q) use ($search) {
                    $q->where('leases.contract_no', 'like', "%{$search}%")
                      ->orWhere('tenant_profiles.full_name', 'like', "%{$search}%")
                      ->orWhere('tenant_users.email', 'like', "%{$search}%")
                      ->orWhere('tenant_users.phone', 'like', "%{$search}%")
                      ->orWhere('properties.name', 'like', "%{$search}%")
                      ->orWhere('agent_profiles.full_name', 'like', "%{$search}%");
                });
            }

            // Filter by status - uses idx_leases_deleted_at_status
            $status = $request->input('status');
            $dueForInvoicing = $request->input('due_for_invoicing');
            
            // Only apply status filter if not filtering by due_for_invoicing
            if (empty($dueForInvoicing) || $dueForInvoicing != '1') {
                // Check if status parameter exists and is not empty string
                if ($status !== null && $status !== '') {
                    $query->where('leases.status', $status);
                }
            }

            // Filter by property - uses idx_units_property_status through JOIN
            $propertyId = $request->input('property_id');
            if ($propertyId !== null && $propertyId !== '') {
                $query->where('properties.id', $propertyId);
            }

            // Filter by tenant - uses idx_leases_org_tenant_deleted
            $tenantId = $request->input('tenant_id');
            if ($tenantId !== null && $tenantId !== '') {
                $query->where('leases.tenant_id', $tenantId);
            }

            // Filter by agent - uses idx_leases_agent_id
            $agentId = $request->input('agent_id');
            if ($agentId !== null && $agentId !== '') {
                $query->where('leases.agent_id', $agentId);
            }

            // Filter by date range - uses idx_leases_start_end_status
            $dateFrom = $request->input('date_from');
            if ($dateFrom !== null && $dateFrom !== '') {
                $query->whereDate('leases.start_date', '>=', $dateFrom);
            }
            $dateTo = $request->input('date_to');
            if ($dateTo !== null && $dateTo !== '') {
                $query->whereDate('leases.end_date', '<=', $dateTo);
            }

            // Get leases with sorting
            $sortBy = $request->get('sort_by', 'id');
            $sortOrder = $request->get('sort_order', 'desc');
            
            // Validate sort fields
            $allowedSortFields = ['id', 'created_at', 'contract_no', 'start_date', 'end_date', 'rent_amount', 'status'];
            if (!in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'id';
            }
            
            if (!in_array($sortOrder, ['asc', 'desc'])) {
                $sortOrder = 'desc';
            }
            
            // If filtering by due_for_invoicing, need to filter collection after getting results
            // This must be done after all other filters are applied
            if (!empty($dueForInvoicing) && $dueForInvoicing == '1') {
                $today = now();
                $currentDay = $today->day;
                $currentDate = $today->copy()->startOfDay();
                
                // First, ensure we only get active leases
                $query->where('leases.status', 'active');
                
                // Get all matching leases first (before pagination)
                $allMatchingLeases = clone $query;
                $allMatchingLeases = $allMatchingLeases->get();
                $allMatchingLeases->load([
                    'unit.property', 
                    'tenant', 
                    'agent', 
                    'organization',
                    'leaseServiceSet.items.service',
                    'paymentCycle'
                ]);
                
                // Filter leases that are actually due for invoicing
                $dueForInvoicingLeases = $allMatchingLeases->filter(function($lease) use ($currentDate, $currentDay) {
                    if ($lease->status !== 'active') {
                        return false;
                    }
                    
                    $paymentCycle = $lease->getEffectivePaymentCycle();
                    $billingDay = $paymentCycle?->billing_day ?? null;
                    
                    if (!$billingDay) {
                        return false;
                    }
                    
                    $startDate = \Carbon\Carbon::parse($lease->start_date);
                    $paymentCycleMonths = $this->getPaymentCycleMonths($lease);
                    
                    // Check if lease has started
                    $daysSinceStart = $startDate->diffInDays($currentDate, false);
                    if ($daysSinceStart < 0) {
                        return false; // Lease hasn't started yet
                    }
                    
                    // Loop through all cycles to find if any cycle is missing an invoice
                    $cycleNumber = 1;
                    $tempDate = $startDate->copy();
                    
                    while (true) {
                        $cycleStart = $tempDate->copy();
                        $cycleEnd = $cycleStart->copy()->addMonths($paymentCycleMonths)->subDay();
                        
                        // Determine billing date for this cycle
                        $billingDate = $cycleStart->copy();
                        $billingDate->day = min($billingDay, $billingDate->daysInMonth);
                        
                        // Check if we've passed the billing date for this cycle
                        if ($currentDate >= $billingDate) {
                            // Skip cycle 1 (first cycle) - don't alert for first invoice
                            if ($cycleNumber > 1) {
                                // Check if invoice exists for this cycle
                                $hasCycleInvoice = \App\Models\Invoice::where('lease_id', $lease->id)
                                    ->where(function($q) {
                                        $q->where('invoice_type', 'invoice_cycle')
                                          ->orWhereNull('invoice_type');
                                    })
                                    ->where('issue_date', '>=', $cycleStart->format('Y-m-d'))
                                    ->where('issue_date', '<=', $cycleEnd->format('Y-m-d'))
                                    ->whereIn('status', ['draft', 'issued', 'paid', 'overdue'])
                                    ->whereNull('deleted_at')
                                    ->exists();
                                
                                $hasRentInvoice = \App\Models\Invoice::where('lease_id', $lease->id)
                                    ->where('issue_date', '>=', $cycleStart->format('Y-m-d'))
                                    ->where('issue_date', '<=', $cycleEnd->format('Y-m-d'))
                                    ->whereIn('status', ['draft', 'issued', 'paid', 'overdue'])
                                    ->whereNull('deleted_at')
                                    ->whereHas('items', function($q) {
                                        $q->where('item_type', 'rent');
                                    })
                                    ->exists();
                                
                                // If no invoice exists for this cycle, this lease is due
                                if (!($hasCycleInvoice || $hasRentInvoice)) {
                                    return true; // Lease is due for invoicing
                                }
                            }
                        } else {
                            // Haven't reached billing date for this cycle yet
                            break;
                        }
                        
                        // Move to next cycle
                        $tempDate->addMonths($paymentCycleMonths);
                        $cycleNumber++;
                        
                        // Safety check: don't loop forever
                        if ($cycleNumber > 100) {
                            break;
                        }
                        
                        // If we're past the current date, stop checking
                        if ($tempDate > $currentDate) {
                            break;
                        }
                    }
                    
                    return false; // All cycles have invoices or haven't reached billing date yet
                });
                
                // Get IDs of leases due for invoicing
                $dueForInvoicingIds = $dueForInvoicingLeases->pluck('id')->toArray();
                
                // Rebuild query with IDs (if empty, return empty result)
                if (empty($dueForInvoicingIds)) {
                    $query = $query->whereRaw('1 = 0'); // Return no results
                } else {
                    // Reset query and rebuild with filtered IDs
                    $query = Lease::select([
                        'leases.*',
                        'units.code as unit_code',
                        'properties.name as property_name',
                        'tenant_profiles.full_name as tenant_name',
                        'agent_profiles.full_name as agent_name'
                    ])
                    ->join('units', 'leases.unit_id', '=', 'units.id')
                    ->leftJoin('properties', 'units.property_id', '=', 'properties.id')
                    ->leftJoin('users as tenant_users', 'leases.tenant_id', '=', 'tenant_users.id')
                    ->leftJoin('user_profiles as tenant_profiles', 'tenant_users.id', '=', 'tenant_profiles.user_id')
                    ->leftJoin('users as agent_users', 'leases.agent_id', '=', 'agent_users.id')
                    ->leftJoin('user_profiles as agent_profiles', 'agent_users.id', '=', 'agent_profiles.user_id')
                    ->where('leases.organization_id', $userOrganizationId)
                    ->whereNull('leases.deleted_at')
                    ->whereNull('units.deleted_at')
                    ->whereNull('properties.deleted_at')
                    ->whereIn('leases.id', $dueForInvoicingIds);
                    
                    // Re-apply other filters
                    if (!empty($search) && trim($search) !== '') {
                        $query->where(function($q) use ($search) {
                            $q->where('leases.contract_no', 'like', "%{$search}%")
                              ->orWhere('tenant_profiles.full_name', 'like', "%{$search}%")
                              ->orWhere('tenant_users.email', 'like', "%{$search}%")
                              ->orWhere('tenant_users.phone', 'like', "%{$search}%")
                              ->orWhere('properties.name', 'like', "%{$search}%")
                              ->orWhere('agent_profiles.full_name', 'like', "%{$search}%");
                        });
                    }
                    
                    if (!empty($propertyId) && trim($propertyId) !== '') {
                        $query->where('properties.id', $propertyId);
                    }
                    
                    if (!empty($tenantId) && trim($tenantId) !== '') {
                        $query->where('leases.tenant_id', $tenantId);
                    }
                    
                    if (!empty($agentId) && trim($agentId) !== '') {
                        $query->where('leases.agent_id', $agentId);
                    }
                    
                    if (!empty($dateFrom) && trim($dateFrom) !== '') {
                        $query->whereDate('leases.start_date', '>=', $dateFrom);
                    }
                    
                    if (!empty($dateTo) && trim($dateTo) !== '') {
                        $query->whereDate('leases.end_date', '<=', $dateTo);
                    }
                    
                    if (!$canViewAll) {
                        $query->where('leases.agent_id', $user->id);
                    }
                }
            }
            
            $leases = $query->orderBy("leases.{$sortBy}", $sortOrder)->paginate(15);
            
            // Eager load relationships for display
            $leases->load([
                'unit.property', 
                'tenant', 
                'agent', 
                'organization',
                'leaseServiceSet.items.service'
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error loading leases: ' . $e->getMessage(), [
                'exception' => $e->getTraceAsString()
            ]);
            $leases = \App\Models\Lease::query()->paginate(10);
        }

        // Get filter data - ensure variables are always defined
        $properties = collect();
        $tenants = collect();
        $agents = collect();
        
        // Get user's organization from middleware
        $userOrganizationId = $this->getCurrentOrganizationId();
        
        try {
            // Only load properties from user's organization or default organization (3)
            $properties = \App\Models\Property::where(function($q) use ($userOrganizationId) {
                $q->where('organization_id', $userOrganizationId)
                  ->orWhere('organization_id', 3); // Default organization
            })->get();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error loading properties: ' . $e->getMessage());
        }
        
        try {
            // Only load tenants from user's organization or default organization (3)
            $tenants = User::whereHas('userRoles', function($q) {
                $q->where('key_code', 'tenant');
            })->whereHas('organizations', function($q) use ($userOrganizationId) {
                $q->where('organization_id', $userOrganizationId)
                  ->orWhere('organization_id', 3); // Default organization
            })->get();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error loading tenants: ' . $e->getMessage());
        }
        
        try {
            // Load users with contract module access (replaces agent/manager role check)
            $agents = \App\Services\CapabilityService::getUsersWithModuleAccess('contract', $userOrganizationId);
            // Also include users from default organization
            if ($userOrganizationId != 3) {
                $defaultOrgUsers = \App\Services\CapabilityService::getUsersWithModuleAccess('contract', 3);
                $agents = $agents->merge($defaultOrgUsers)->unique('id');
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error loading agents: ' . $e->getMessage());
        }

        // Check if this is an HTMX request
        $isHtmx = $request->header('HX-Request') === 'true';
        
        // Format stats for statistics-cards component
        $statsFormatted = [
            'total' => [
                'value' => (int) ($stats['draft'] ?? 0) + ($stats['active'] ?? 0) + ($stats['expired'] ?? 0) + ($stats['terminated'] ?? 0),
                'label' => 'Tổng cộng',
                'icon' => 'fa-list',
                'color' => 'primary',
                'filter' => '',
            ],
            'draft' => [
                'value' => $stats['draft'] ?? 0,
                'label' => 'Nháp',
                'icon' => 'fa-file-alt',
                'color' => 'warning',
                'filter' => 'draft',
                'filterKey' => 'status',
            ],
            'active' => [
                'value' => $stats['active'] ?? 0,
                'label' => 'Đang hoạt động',
                'icon' => 'fa-check-circle',
                'color' => 'success',
                'filter' => 'active',
                'filterKey' => 'status',
            ],
            'expired' => [
                'value' => $stats['expired'] ?? 0,
                'label' => 'Đã hết hạn',
                'icon' => 'fa-clock',
                'color' => 'secondary',
                'filter' => 'expired',
                'filterKey' => 'status',
            ],
            'terminated' => [
                'value' => $stats['terminated'] ?? 0,
                'label' => 'Đã chấm dứt',
                'icon' => 'fa-times-circle',
                'color' => 'danger',
                'filter' => 'terminated',
                'filterKey' => 'status',
            ],
            'due_for_invoicing' => [
                'value' => $stats['due_for_invoicing'] ?? 0,
                'label' => 'Đến hạn lập hóa đơn',
                'icon' => 'fa-file-invoice',
                'color' => 'info',
                'filter' => 'due_for_invoicing',
                'filterKey' => 'due_for_invoicing',
            ],
        ];
        
        // If HTMX request, return table and stats HTML with hx-swap-oob
        if ($isHtmx) {
            try {
                // Render table partial
                $tableHtml = view('staff.contract.leases.partials.table', [
                    'leases' => $leases,
                    'sortBy' => $sortBy,
                    'sortOrder' => $sortOrder
                ])->render();
                
                // Extract inner HTML from table (remove wrapper div if exists)
                $dom = new \DOMDocument();
                libxml_use_internal_errors(true);
                $dom->loadHTML('<?xml encoding="UTF-8">' . $tableHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                libxml_clear_errors();
                
                $container = $dom->getElementsByTagName('body')->item(0);
                if ($container) {
                    $innerTableHtml = '';
                    foreach ($container->childNodes as $child) {
                        $innerTableHtml .= $dom->saveHTML($child);
                    }
                } else {
                    $innerTableHtml = $tableHtml;
                }
                
                // Determine current filter for stats highlighting
                $currentFilter = '';
                if (request('due_for_invoicing') == '1') {
                    $currentFilter = 'due_for_invoicing';
                } elseif (request('status')) {
                    $currentFilter = request('status');
                }
                
                // Render stats HTML
                $statsHtml = view('staff.components.statistics-cards', [
                    'stats' => $statsFormatted,
                    'currentFilter' => $currentFilter,
                    'filterKey' => 'status',
                    'onFilterClick' => 'htmx-filter',
                    'onClearClick' => 'htmx-clear',
                    'columns' => 6,
                    'action' => route('staff.leases.index'),
                    'tableContainerId' => 'leases-table-container'
                ])->render();
                
                // Combine HTML with hx-swap-oob for stats
                $responseHtml = $innerTableHtml . "\n<div id=\"stats-container\" hx-swap-oob=\"true\">\n" . $statsHtml . "\n</div>";
                
                return response($responseHtml)
                    ->header('HX-Push-Url', $request->fullUrl());
            } catch (\Exception $e) {
                Log::error('LeaseController HTMX Error: ' . $e->getMessage());
                return response('<div class="alert alert-danger">Lỗi khi tải dữ liệu: ' . $e->getMessage() . '</div>', 500);
            }
        }

        return view('staff.contract.leases.index', [
            'leases' => $leases,
            'properties' => $properties,
            'tenants' => $tenants,
            'agents' => $agents,
            'stats' => $stats ?? [
                'draft' => 0,
                'active' => 0,
                'expired' => 0,
                'terminated' => 0,
                'due_for_invoicing' => 0,
            ],
            'sortBy' => $sortBy ?? 'id',
            'sortOrder' => $sortOrder ?? 'desc'
        ]);
    }

    public function create(Request $request)
    {
        // Check capability
        $this->requireCapability('contract.lease.create', 'Bạn không có quyền tạo hợp đồng.');
        // Ensure variables are always defined
        $properties = collect();
        $tenants = collect();
        $agents = collect();
        $services = collect();
        
        // Get user's organization from middleware
        $userOrganizationId = $this->getCurrentOrganizationId();
        
        // Get property_id, unit_id, booking_deposit_id, and lead_id from query parameters
        $propertyId = $request->get('property_id');
        $unitId = $request->get('unit_id');
        $bookingDepositId = $request->get('booking_deposit_id');
        $viewingId = $request->get('viewing_id');
        $selectedLeadId = $request->get('lead_id');
        
        // If viewing_id is provided, get lead_id from viewing
        if ($viewingId && !$selectedLeadId) {
            try {
                $viewing = \App\Models\Viewing::where('id', $viewingId)
                    ->whereHas('property', function($q) use ($userOrganizationId) {
                        $q->where('organization_id', $userOrganizationId);
                    })
                    ->first();
                
                if ($viewing && $viewing->lead_id) {
                    $selectedLeadId = $viewing->lead_id;
                }
                
                // Also pre-fill property_id and unit_id from viewing if not provided
                if (!$propertyId && $viewing && $viewing->property_id) {
                    $propertyId = $viewing->property_id;
                }
                if (!$unitId && $viewing && $viewing->unit_id) {
                    $unitId = $viewing->unit_id;
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Error loading viewing in create: ' . $e->getMessage());
            }
        }
        
        // Nếu có booking_deposit_id từ session hoặc query, load booking deposit để pre-fill
        $selectedBookingDeposit = null;
        if ($bookingDepositId || session()->has('booking_deposit_id_for_lease')) {
            $bookingDepositId = $bookingDepositId ?? session('booking_deposit_id_for_lease');
            session()->forget('booking_deposit_id_for_lease'); // Xóa sau khi lấy
            
            try {
                $selectedBookingDeposit = \App\Models\BookingDeposit::where('id', $bookingDepositId)
                    ->where('organization_id', $userOrganizationId)
                    ->where('payment_status', 'paid')
                    ->whereNull('deleted_at')
                    ->whereDoesntHave('lease')
                    ->with(['unit.property', 'tenantUser', 'lead', 'agent'])
                    ->first();
                
                // Nếu có booking deposit, pre-fill property_id và unit_id
                if ($selectedBookingDeposit) {
                    if (!$propertyId && $selectedBookingDeposit->unit && $selectedBookingDeposit->unit->property) {
                        $propertyId = $selectedBookingDeposit->unit->property_id;
                    }
                    if (!$unitId && $selectedBookingDeposit->unit_id) {
                        $unitId = $selectedBookingDeposit->unit_id;
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Error loading booking deposit in create: ' . $e->getMessage());
            }
        }
        
        try {
            // Only load properties from user's organization or default organization (3)
            $properties = \App\Models\Property::where(function($q) use ($userOrganizationId) {
                $q->where('organization_id', $userOrganizationId)
                  ->orWhere('organization_id', 3); // Default organization
            })->get();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error loading properties in create: ' . $e->getMessage());
        }
        
        // Validate property_id if provided
        if ($propertyId) {
            $propertyId = (int)$propertyId;
            $property = $properties->firstWhere('id', $propertyId);
            if (!$property) {
                // Property not found or user doesn't have access, clear property_id
                $propertyId = null;
            }
        }
        
        // Validate unit_id if provided
        if ($unitId && $propertyId) {
            $unitId = (int)$unitId;
            $unit = Unit::where('id', $unitId)
                ->where('property_id', $propertyId)
                ->whereNull('deleted_at')
                ->first();
            if (!$unit) {
                // Unit not found or doesn't belong to property, clear unit_id
                $unitId = null;
            }
        } else {
            $unitId = null;
        }
        
        try {
            // Only load tenants from user's organization or default organization (3)
            $tenants = User::with('userProfile')
                ->whereHas('userRoles', function($q) {
                    $q->where('key_code', 'tenant');
                })
                ->whereHas('organizations', function($q) use ($userOrganizationId) {
                    $q->where('organization_id', $userOrganizationId)
                      ->orWhere('organization_id', 3); // Default organization
                })
                ->whereNull('deleted_at')
                ->get()
                ->sortBy(function($user) {
                    return $user->userProfile->full_name ?? $user->email ?? '';
                })
                ->values();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error loading tenants in create: ' . $e->getMessage());
        }
        
        // Get all users in organization (managers and agents, EXCLUDE tenants)
        $managers = collect();
        $agents = collect();
        $allUsers = collect();
        
        try {
            // Get staff users only (exclude tenants)
            $allUsers = User::with('userProfile')
                ->whereHas('organizationUsers', function($q) use ($userOrganizationId) {
                    $q->where('organization_id', $userOrganizationId)
                      ->where('status', 'active')
                      ->whereNull('deleted_at');
                })
                ->whereDoesntHave('userRoles', function($q) {
                    $q->where('key_code', 'tenant'); // Exclude tenants
                })
                ->whereNull('deleted_at')
                ->get()
                ->sortBy(function($user) {
                    return $user->userProfile->full_name ?? $user->full_name ?? '';
                })->values();
            
            // Get manager role IDs
            $managerIds = DB::table('organization_users')
                ->join('roles', 'organization_users.role_id', '=', 'roles.id')
                ->where('organization_users.organization_id', $userOrganizationId)
                ->where('organization_users.status', 'active')
                ->whereNull('organization_users.deleted_at')
                ->where('roles.key_code', 'manager')
                ->pluck('organization_users.user_id')
                ->toArray();
            
            // Separate managers and agents
            $managers = $allUsers->filter(function($user) use ($managerIds) {
                return in_array($user->id, $managerIds);
            })->values();
            
            $agents = $allUsers->filter(function($user) use ($managerIds) {
                return !in_array($user->id, $managerIds);
            })->values();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error loading users in create: ' . $e->getMessage());
        }
        
        try {
            // Get services available for this organization (organization-specific + global)
            $services = Service::forOrganization($userOrganizationId)->get();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error loading services in create: ' . $e->getMessage());
        }
        
        // Services are now customizable per lease, not fixed to property
        // No longer auto-load services from property or organization
        // Users can add/remove/edit services freely for each lease
        $defaultLeaseServices = collect();
        $effectiveLeaseServiceSet = null;
        
        // Get leads for selection
        $leads = collect();
        try {
            if ($userOrganizationId) {
                $leads = \App\Models\Lead::where('organization_id', $userOrganizationId)
                    ->whereNull('deleted_at')
                    ->orderBy('created_at', 'desc')
                    ->limit(100) // Limit to recent 100 leads
                    ->get();
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error loading leads in create: ' . $e->getMessage());
        }

        // Get paid booking deposits for selection (chỉ lấy booking chưa có hợp đồng)
        $bookingDeposits = collect();
        try {
            if ($userOrganizationId) {
                $bookingDeposits = \App\Models\BookingDeposit::where('organization_id', $userOrganizationId)
                    ->where('payment_status', 'paid')
                    ->whereNull('deleted_at')
                    ->whereDoesntHave('lease') // Chỉ lấy booking chưa có hợp đồng
                    ->with(['unit.property', 'lead', 'agent', 'tenantUser'])
                    ->orderBy('paid_at', 'desc')
                    ->limit(100) // Limit to recent 100 paid booking deposits
                    ->get();
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error loading booking deposits in create: ' . $e->getMessage());
        }

        // Auto-fill agent based on property assignment
        $defaultAgentId = null;
        if ($propertyId) {
            try {
                $property = \App\Models\Property::with(['assignedUsers' => function($q) {
                    // Get agents assigned to this property (role_key = 'agent')
                    $q->wherePivot('role_key', 'agent')
                      ->orderBy('properties_user.id', 'desc'); // Get latest assignment
                }])->find($propertyId);
                
                if ($property && $property->assignedUsers->isNotEmpty()) {
                    // Get the latest assigned agent (highest ID = most recent)
                    $defaultAgentId = $property->assignedUsers->first()->id;
                } else {
                    // No agent assigned to property → use current user (creator)
                    $defaultAgentId = Auth::id();
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Error loading property agent: ' . $e->getMessage());
                $defaultAgentId = Auth::id(); // Fallback to current user
            }
        } else {
            // No property selected → use current user
            $defaultAgentId = Auth::id();
        }

        // Keep for backward compatibility
        $propertyManagerId = $defaultAgentId;
        $currentUserId = Auth::id();

        // Get payment cycles for dropdown
        $paymentCycles = collect();
        $defaultPaymentCycle = null;
        $suggestedPaymentCycleId = null;
        
        if ($userOrganizationId) {
            // Get default payment cycle for this organization
            $defaultPaymentCycle = PaymentCycle::where('organization_id', $userOrganizationId)
                ->where('is_default', true)
                ->first();
            
            // Get all payment cycles for this organization
            $paymentCycles = PaymentCycle::where('organization_id', $userOrganizationId)
                ->whereNull('deleted_at')
                ->orderBy('is_default', 'desc')
                ->orderBy('name', 'asc')
                ->get();
            
            // Tự động chọn payment cycle theo thứ tự ưu tiên: property -> default -> newest
            if ($propertyId) {
                try {
                    $property = \App\Models\Property::find($propertyId);
                    if ($property) {
                        // Ưu tiên 1: Property có payment_cycle_id riêng
                        if ($property->payment_cycle_id) {
                            $suggestedPaymentCycleId = $property->payment_cycle_id;
                        } else {
                            // Ưu tiên 2: Default của organization
                            if ($defaultPaymentCycle) {
                                $suggestedPaymentCycleId = $defaultPaymentCycle->id;
                            } else {
                                // Ưu tiên 3: Mới nhất (newest)
                                $newestCycle = PaymentCycle::where('organization_id', $userOrganizationId)
                                    ->whereNull('deleted_at')
                                    ->orderBy('created_at', 'desc')
                                    ->first();
                                if ($newestCycle) {
                                    $suggestedPaymentCycleId = $newestCycle->id;
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Error getting suggested payment cycle from property: ' . $e->getMessage());
                }
            } else {
                // Không có property, ưu tiên: default -> newest
                if ($defaultPaymentCycle) {
                    $suggestedPaymentCycleId = $defaultPaymentCycle->id;
                } else {
                    // Lấy mới nhất
                    $newestCycle = PaymentCycle::where('organization_id', $userOrganizationId)
                        ->whereNull('deleted_at')
                        ->orderBy('created_at', 'desc')
                        ->first();
                    if ($newestCycle) {
                        $suggestedPaymentCycleId = $newestCycle->id;
                    }
                }
            }
        }

        // Get lease service sets for dropdown
        $leaseServiceSets = collect();
        $defaultLeaseServiceSet = null;
        $suggestedLeaseServiceSetId = null;
        
        if ($userOrganizationId) {
            // Get default lease service set for this organization
            $defaultLeaseServiceSet = LeaseServiceSet::where('organization_id', $userOrganizationId)
                ->where('is_default', true)
                ->first();
            
            // Get all lease service sets for this organization
            $leaseServiceSets = LeaseServiceSet::where('organization_id', $userOrganizationId)
                ->whereNull('deleted_at')
                ->orderBy('is_default', 'desc')
                ->orderBy('name', 'asc')
                ->get();
            
            // Tự động chọn lease service set theo thứ tự ưu tiên: property -> default -> newest
            if ($propertyId) {
                try {
                    $property = \App\Models\Property::find($propertyId);
                    if ($property) {
                        // Ưu tiên 1: Property có lease_service_set riêng
                        if ($property->lease_services_id) {
                            $suggestedLeaseServiceSetId = $property->lease_services_id;
                        } else {
                            // Ưu tiên 2: Default của organization
                            if ($defaultLeaseServiceSet) {
                                $suggestedLeaseServiceSetId = $defaultLeaseServiceSet->id;
                            } else {
                                // Ưu tiên 3: Mới nhất (newest)
                                $newestSet = LeaseServiceSet::where('organization_id', $userOrganizationId)
                                    ->whereNull('deleted_at')
                                    ->orderBy('created_at', 'desc')
                                    ->first();
                                if ($newestSet) {
                                    $suggestedLeaseServiceSetId = $newestSet->id;
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Error getting suggested lease service set from property: ' . $e->getMessage());
                }
            } else {
                // Không có property, ưu tiên: default -> newest
                if ($defaultLeaseServiceSet) {
                    $suggestedLeaseServiceSetId = $defaultLeaseServiceSet->id;
                } else {
                    // Lấy mới nhất
                    $newestSet = LeaseServiceSet::where('organization_id', $userOrganizationId)
                        ->whereNull('deleted_at')
                        ->orderBy('created_at', 'desc')
                        ->first();
                    if ($newestSet) {
                        $suggestedLeaseServiceSetId = $newestSet->id;
                    }
                }
            }
        }

        return view('staff.contract.leases.create', [
            'properties' => $properties,
            'tenants' => $tenants,
            'managers' => $managers,
            'agents' => $agents,
            'services' => $services,
            'paymentCycles' => $paymentCycles,
            'defaultPaymentCycle' => $defaultPaymentCycle,
            'leaseServiceSets' => $leaseServiceSets,
            'defaultLeaseServiceSet' => $defaultLeaseServiceSet,
            'defaultLeaseServices' => $defaultLeaseServices, // Empty - services are customizable per lease
            'effectiveLeaseServiceSet' => $effectiveLeaseServiceSet, // Null - not used anymore
            'leads' => $leads,
            'bookingDeposits' => $bookingDeposits,
            'propertyId' => $propertyId,
            'unitId' => $unitId,
            'selectedLeadId' => $selectedLeadId,
            'defaultAgentId' => $defaultAgentId,
            'propertyManagerId' => $propertyManagerId,
            'currentUserId' => $currentUserId,
            'selectedBookingDeposit' => $selectedBookingDeposit,
            'selectedBookingDepositId' => $selectedBookingDeposit ? $selectedBookingDeposit->id : null,
            'suggestedLeaseServiceSetId' => $suggestedLeaseServiceSetId,
            'suggestedPaymentCycleId' => $suggestedPaymentCycleId
        ]);
    }

    public function store(Request $request)
    {
        // Check capability
        $this->requireCapability('contract.lease.create', 'Bạn không có quyền tạo hợp đồng.');
        
        // Get organization
        $user = Auth::user();
        $organizationId = session('current_organization_id') ?? \App\Models\OrganizationUser::where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->first()?->organization_id;
        
        if ($organizationId) {
            $organization = \App\Models\Organization::find($organizationId);
            
            if ($organization) {
                // Check subscription limit
                if (!$this->limitChecker->canAddLease($organization)) {
                    $limit = $this->limitChecker->getLimit($organization, 'max_leases');
                    $current = $this->limitChecker->getLeasesCount($organization);
                    
                    if ($request->expectsJson() || $request->ajax()) {
                        return response()->json([
                            'success' => false,
                            'message' => "Bạn đã đạt giới hạn số lượng hợp đồng thuê của gói dịch vụ. Hiện tại: {$current}/{$limit}",
                            'error_type' => 'subscription_limit',
                        ], 403);
                    }
                    
                    return back()->with('error', "Bạn đã đạt giới hạn số lượng hợp đồng thuê của gói dịch vụ. Hiện tại: {$current}/{$limit}");
                }
            }
        }
        
        try {
            
            $validated = $request->validate([
                'unit_id' => 'required|exists:units,id',
                'lead_id' => 'required|exists:leads,id',
                'booking_deposit_id' => [
                    'nullable',
                    'exists:booking_deposits,id',
                    function ($attribute, $value, $fail) use ($organizationId) {
                        if ($value) {
                            // Kiểm tra booking deposit chưa có hợp đồng
                            $hasLease = \App\Models\BookingDeposit::where('id', $value)
                                ->where('organization_id', $organizationId)
                                ->whereHas('lease')
                                ->exists();
                            
                            if ($hasLease) {
                                $fail('Booking deposit này đã được sử dụng để tạo hợp đồng. Vui lòng chọn booking deposit khác.');
                            }
                        }
                    },
                ],
                'agent_id' => 'nullable|exists:users,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'rent_amount' => 'required|numeric|min:0',
                'deposit_amount' => 'nullable|numeric|min:0',
                // Status không được thay đổi từ form, luôn là 'draft' khi tạo mới
                // 'status' => 'required|in:draft,active,terminated,expired',
                'contract_no' => 'nullable|string|max:100|unique:leases,contract_no',
                'signed_at' => 'nullable|date',
                'lease_services_id' => 'nullable|exists:lease_service_sets,id',
                // Payment cycle - only allow selecting existing cycles
                'payment_cycle_id' => 'nullable|exists:payment_cycles,id',
            ], [
                'lease_services_id.exists' => 'Nhóm dịch vụ đã chọn không tồn tại.',
            ]);

            // Tự động sinh mã hợp đồng nếu không được cung cấp
            if (empty($validated['contract_no'])) {
                $validated['contract_no'] = $this->generateContractNumber();
            }

            // Tự động gán agent_id cho agent (không cho phép sửa)
            // Manager có thể gán cho agent khác, Agent phải gán cho chính mình
            $this->enforceAgentId($validated, 'agent_id');

            // Kiểm tra phòng đã có hợp đồng hoạt động
            $hasActiveLease = Lease::where('unit_id', $validated['unit_id'])
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->exists();

            if ($hasActiveLease) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Phòng này đã có hợp đồng hoạt động. Vui lòng chọn phòng khác hoặc chấm dứt hợp đồng hiện tại trước.'
                    ], 422);
                }
                return back()->withInput()->with('error', 'Phòng này đã có hợp đồng hoạt động. Vui lòng chọn phòng khác hoặc chấm dứt hợp đồng hiện tại trước.');
            }

            // Get organization from current user
            $currentUser = Auth::user();
            $organization = \App\Models\OrganizationUser::where('user_id', $currentUser->id)
                ->whereNull('deleted_at')
                ->first()?->organization;
            $organizationId = $organization?->id;

            DB::beginTransaction();
            
            try {
                // Xử lý tạo user từ lead
                $tenantId = null;
                // Lấy lead
                $lead = \App\Models\Lead::find($validated['lead_id']);
                
                if (!$lead) {
                    DB::rollBack();
                    if ($request->expectsJson() || $request->ajax()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Lead không tồn tại.'
                        ], 404);
                    }
                    return back()->withInput()->with('error', 'Lead không tồn tại.');
                }
                
                // Kiểm tra xem lead đã có tenant_id chưa (đã là khách thuê)
                $tenantId = null;
                if ($lead->tenant_id) {
                    // Kiểm tra user có tồn tại và có role tenant
                    $existingUser = User::where('id', $lead->tenant_id)
                        ->whereNull('deleted_at')
                        ->whereHas('userRoles', function($q) {
                            $q->where('key_code', 'tenant');
                        })
                        ->first();
                    
                    if ($existingUser) {
                        // Lead đã là khách thuê → sử dụng tenant_id cũ, KHÔNG tạo mới
                        $tenantId = $lead->tenant_id;
                        
                        // Đảm bảo user đã được gắn vào organization hiện tại
                        $tenantRole = \App\Models\Role::where('key_code', 'tenant')->first();
                        if ($tenantRole) {
                            $orgUser = \App\Models\OrganizationUser::firstOrCreate(
                                [
                                    'organization_id' => $organizationId,
                                    'user_id' => $tenantId,
                                ],
                                [
                                    'role_id' => $tenantRole->id,
                                    'status' => 'active',
                                ]
                            );
                        }
                        
                        // Cập nhật status về converted nếu chưa
                        if ($lead->status !== 'converted') {
                            $lead->update(['status' => 'converted']);
                        }
                        
                        Log::info('Lead already has tenant, reusing existing tenant', [
                            'lead_id' => $lead->id,
                            'tenant_id' => $tenantId,
                            'organization_id' => $organizationId
                        ]);
                    } else {
                        // User không tồn tại hoặc không có role tenant → tạo mới
                        Log::warning('Lead tenant_id exists but user not found or not tenant, creating new user', [
                            'lead_id' => $lead->id,
                            'old_tenant_id' => $lead->tenant_id
                        ]);
                    }
                }
                
                // Nếu chưa có tenant_id hoặc user không hợp lệ → tạo user mới hoặc sử dụng user đã tồn tại
                if (!$tenantId) {
                    // Kiểm tra email và phone đã tồn tại chưa (chỉ kiểm tra non-soft-deleted)
                    $existingUser = null;
                    
                    if ($lead->email) {
                        $existingUser = User::where('email', $lead->email)
                            ->whereNull('deleted_at')
                            ->first();
                    }
                    
                    if (!$existingUser && $lead->phone) {
                        $existingUser = User::where('phone', $lead->phone)
                            ->whereNull('deleted_at')
                            ->first();
                    }
                    
                    // Lấy role tenant
                    $tenantRole = \App\Models\Role::where('key_code', 'tenant')->first();
                    if (!$tenantRole) {
                        DB::rollBack();
                        if ($request->expectsJson() || $request->ajax()) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Không tìm thấy role tenant. Vui lòng liên hệ quản trị viên.'
                            ], 500);
                        }
                        return back()->withInput()->with('error', 'Không tìm thấy role tenant. Vui lòng liên hệ quản trị viên.');
                    }
                    
                    if ($existingUser) {
                        // User đã tồn tại → thêm vào tổ chức hiện tại với role tenant
                        // Kiểm tra xem user đã có trong tổ chức này chưa
                        // Kiểm tra xem user đã có trong organization này chưa
                        // Một user chỉ có thể có 1 role trong 1 organization
                        $existingOrgUser = \App\Models\OrganizationUser::where('organization_id', $organizationId)
                            ->where('user_id', $existingUser->id)
                            ->whereNull('deleted_at')
                            ->first();
                        
                        if (!$existingOrgUser) {
                            // Thêm user vào tổ chức hiện tại với role tenant
                            \App\Models\OrganizationUser::create([
                                'organization_id' => $organizationId,
                                'user_id' => $existingUser->id,
                                'role_id' => $tenantRole->id, // role tenant (id = 5)
                                'status' => 'active',
                            ]);
                            
                            Log::info('Existing user added to organization from lead', [
                                'lead_id' => $lead->id,
                                'user_id' => $existingUser->id,
                                'organization_id' => $organizationId,
                                'role_id' => $tenantRole->id,
                            ]);
                        } else {
                            // User đã có trong tổ chức, đảm bảo role là tenant và cập nhật status
                            $existingOrgUser->update([
                                'role_id' => $tenantRole->id,
                                'status' => 'active',
                            ]);
                            
                            Log::info('Existing user already in organization, updated role and status', [
                                'lead_id' => $lead->id,
                                'user_id' => $existingUser->id,
                                'organization_id' => $organizationId,
                                'role_id' => $tenantRole->id,
                            ]);
                        }
                        
                        // Lấy user_id để ghi vào lead và hợp đồng
                        $tenantId = $existingUser->id;
                        
                        // Cập nhật lead với tenant_id và status = 'converted'
                        $lead->update([
                            'tenant_id' => $tenantId, // Ghi user_id vào lead
                            'status' => 'converted',
                        ]);
                        
                        // Refresh lead để đảm bảo tenant_id được cập nhật
                        $lead->refresh();
                        
                        Log::info('Existing user reused from lead', [
                            'lead_id' => $lead->id,
                            'user_id' => $existingUser->id,
                            'tenant_id' => $tenantId,
                            'lead_tenant_id_after_update' => $lead->tenant_id,
                            'organization_id' => $organizationId
                        ]);
                    } else {
                        // User chưa tồn tại → tạo user mới
                        $newUser = User::create([
                            'email' => $lead->email,
                            'phone' => $lead->phone,
                            'password_hash' => \Illuminate\Support\Facades\Hash::make('12345678'),
                            'status' => 1,
                        ]);
                        
                        // Tạo user profile với full_name từ lead.name
                        \App\Models\UserProfile::create([
                            'user_id' => $newUser->id,
                            'full_name' => $lead->name,
                        ]);
                        
                        // Gắn user vào organization với role tenant
                        // Kiểm tra xem user đã có trong organization này chưa
                        // Một user chỉ có thể có 1 role trong 1 organization
                        $existingOrgUser = \App\Models\OrganizationUser::where('organization_id', $organizationId)
                            ->where('user_id', $newUser->id)
                            ->whereNull('deleted_at')
                            ->first();
                        
                        if (!$existingOrgUser) {
                            \App\Models\OrganizationUser::create([
                                'organization_id' => $organizationId,
                                'user_id' => $newUser->id,
                                'role_id' => $tenantRole->id,
                                'status' => 'active',
                            ]);
                        } else {
                            // Nếu đã tồn tại, cập nhật role và status
                            $existingOrgUser->update([
                                'role_id' => $tenantRole->id,
                                'status' => 'active',
                            ]);
                        }
                        
                        // Cập nhật lead với tenant_id và status = 'converted'
                        $lead->update([
                            'tenant_id' => $newUser->id,
                            'status' => 'converted',
                        ]);
                        
                        // Refresh lead để đảm bảo tenant_id được cập nhật
                        $lead->refresh();
                        
                        $tenantId = $newUser->id;
                        
                        Log::info('User created from lead', [
                            'lead_id' => $lead->id,
                            'user_id' => $newUser->id,
                            'lead_tenant_id_after_update' => $lead->tenant_id,
                            'organization_id' => $organizationId
                        ]);
                    }
                }
            
                // Validate tenant_id exists after lead processing
                if (!$tenantId) {
                    DB::rollBack();
                    if ($request->expectsJson() || $request->ajax()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Không thể tạo tài khoản khách thuê từ lead.'
                        ], 422);
                    }
                    return back()->withInput()->with('error', 'Không thể tạo tài khoản khách thuê từ lead.');
                }

                // Handle payment cycle
                $paymentCycleId = $this->handlePaymentCycle($validated, $organizationId);

                // Handle lease service set - chỉ sử dụng lease_services_id đã chọn (nếu có)
                $leaseServicesId = null;
                if (!empty($validated['lease_services_id'])) {
                    $leaseServiceSet = LeaseServiceSet::where('id', $validated['lease_services_id'])
                        ->where(function($query) use ($organizationId) {
                            $query->where('organization_id', $organizationId)
                                  ->orWhereNull('organization_id');
                        })
                        ->first();
                    
                    if (!$leaseServiceSet) {
                        DB::rollBack();
                        if ($request->expectsJson()) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Nhóm dịch vụ đã chọn không tồn tại hoặc không thuộc tổ chức của bạn.'
                            ], 422);
                        }
                        return back()->withInput()->with('error', 'Nhóm dịch vụ đã chọn không tồn tại hoặc không thuộc tổ chức của bạn.');
                    }
                    
                    $leaseServicesId = $leaseServiceSet->id;
                }

                // Lấy booking deposit nếu có và kiểm tra chưa có hợp đồng
                $bookingDeposit = null;
                if (!empty($validated['booking_deposit_id'])) {
                    $bookingDeposit = \App\Models\BookingDeposit::where('id', $validated['booking_deposit_id'])
                        ->where('organization_id', $organizationId)
                        ->where('payment_status', 'paid')
                        ->whereDoesntHave('lease') // Đảm bảo booking chưa có hợp đồng
                        ->first();
                    
                    // Kiểm tra booking deposit đã có hợp đồng chưa
                    if (!$bookingDeposit) {
                        // Kiểm tra xem booking có tồn tại nhưng đã có hợp đồng
                        $existingBooking = \App\Models\BookingDeposit::where('id', $validated['booking_deposit_id'])
                            ->where('organization_id', $organizationId)
                            ->whereHas('lease')
                            ->first();
                        
                        if ($existingBooking) {
                            DB::rollBack();
                            if ($request->expectsJson() || $request->ajax()) {
                                return response()->json([
                                    'success' => false,
                                    'message' => 'Booking deposit này đã được sử dụng để tạo hợp đồng. Vui lòng chọn booking deposit khác.',
                                    'errors' => ['booking_deposit_id' => ['Booking deposit này đã được sử dụng để tạo hợp đồng.']]
                                ], 422);
                            }
                            return back()->withInput()->withErrors([
                                'booking_deposit_id' => 'Booking deposit này đã được sử dụng để tạo hợp đồng. Vui lòng chọn booking deposit khác.'
                            ]);
                        }
                        
                        // Booking không tồn tại hoặc chưa thanh toán
                        DB::rollBack();
                        if ($request->expectsJson() || $request->ajax()) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Booking deposit không hợp lệ hoặc chưa thanh toán.',
                                'errors' => ['booking_deposit_id' => ['Booking deposit không hợp lệ hoặc chưa thanh toán.']]
                            ], 422);
                        }
                        return back()->withInput()->withErrors([
                            'booking_deposit_id' => 'Booking deposit không hợp lệ hoặc chưa thanh toán.'
                        ]);
                    }
                }

                // Create lease
                // Status luôn là 'draft' khi tạo mới, không lấy từ form
                $lease = Lease::create([
                'organization_id' => $organizationId,
                'unit_id' => $validated['unit_id'],
                'tenant_id' => $tenantId,
                'agent_id' => $validated['agent_id'],
                'booking_id' => $bookingDeposit ? $bookingDeposit->id : null,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'rent_amount' => $validated['rent_amount'],
                'deposit_amount' => $validated['deposit_amount'] ?? 0,
                'status' => 'draft', // Luôn tạo hợp đồng ở trạng thái nháp
                'contract_no' => $validated['contract_no'],
                'signed_at' => $validated['signed_at'],
                'payment_cycle_id' => $paymentCycleId,
                'lease_services_id' => $leaseServicesId,
            ]);

                // Services are now managed through lease_service_sets
                // No need to create individual lease_services records

                // Cập nhật trạng thái phòng dựa trên trạng thái hợp đồng (luôn là 'draft' khi tạo mới)
                $this->updateUnitStatusBasedOnLease($lease, 'draft');

                // BỎ: Không tạo hóa đơn tự động khi tạo hợp đồng
                // Hóa đơn sẽ được tạo thủ công từ trang show hợp đồng
                // if ($bookingDeposit && $bookingDeposit->amount > 0) {
                //     $this->createFirstInvoiceWithDepositDeduction($lease);
                // }

                DB::commit();

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Hợp đồng đã được tạo thành công!',
                        'redirect' => route('staff.leases.show', $lease->id)
                    ]);
                }

                return redirect()->route('staff.leases.show', $lease->id)
                    ->with('success', 'Hợp đồng đã được tạo thành công!');
                    
            } catch (\Exception $e) {
                DB::rollBack();
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Có lỗi xảy ra khi tạo hợp đồng: ' . $e->getMessage()
                    ], 500);
                }

                return back()->withInput()->with('error', 'Có lỗi xảy ra khi tạo hợp đồng: ' . $e->getMessage());
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error in LeaseController@store: ' . json_encode($e->errors()));
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ.',
                    'errors' => $e->errors()
                ], 422);
            }
            
            return back()->withInput()->withErrors($e->errors())->with('error', 'Dữ liệu không hợp lệ.');
        } catch (\Exception $e) {
            Log::error('Error in LeaseController@store: ' . $e->getMessage());
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->withInput()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Get user's organization from middleware
        $userOrganizationId = $this->getCurrentOrganizationId();
        
        // Check if user has contract.access capability
        $hasContractAccess = $this->checkCapability('contract.access');
        if (!$hasContractAccess) {
            abort(403, 'Bạn không có quyền truy cập module Hợp đồng.');
        }
        
        $lease = Lease::with([
            'unit.property.propertyType',
            'unit.property.location',
            'unit.property.location2025',
            'tenant',
            'agent',
            'organization',
            'leaseServiceSet.items.service',
            'residents.user',
            'documents.uploader',
            'bookingDeposit',
            'depositRefunds'
        ])->where('organization_id', $userOrganizationId)->findOrFail($id);

        // For agent, check if lease was created by them
        $canViewAll = $this->canViewAll('contract.lease');
        if (!$canViewAll && $lease->agent_id !== $user->id) {
            abort(403, 'Bạn không có quyền xem hợp đồng này.');
        }

        // Load related invoices, tickets, and ticket deposit logs
        $invoices = \App\Models\Invoice::where('lease_id', $lease->id)
            ->with(['items'])
            ->orderBy('created_at', 'desc')
            ->get();

        $tickets = \App\Models\Ticket::where(function($q) use ($lease) {
                $q->where('lease_id', $lease->id)
                  ->orWhere('unit_id', $lease->unit_id);
            })
            ->with(['logs.actor', 'createdBy', 'assignedTo'])
            ->orderBy('created_at', 'desc')
            ->get();

        $ticketDepositLogs = \App\Models\TicketLog::whereHas('ticket', function($q) use ($lease) {
                $q->where('lease_id', $lease->id);
            })
            ->whereNull('deleted_at')
            ->where('charge_to', 'tenant_deposit')
            ->orderBy('created_at', 'desc')
            ->get();

        // Kiểm tra xem đã có hóa đơn đầu tiên chưa (để hiển thị nút tạo hóa đơn)
        $hasFirstInvoice = \App\Models\Invoice::where('lease_id', $lease->id)
            ->where('status', '!=', 'cancelled')
            ->where(function($query) {
                $query->whereHas('items', function($q) {
                    $q->where('item_type', 'deposit')
                      ->orWhere('item_type', 'rent')
                      ->orWhere('description', 'like', '%chu kỳ đầu%')
                      ->orWhere('description', 'like', '%tháng đầu%');
                });
            })
            ->exists();
        
        // Tính toán thông tin hoàn tiền để hiển thị trong modal
        $depositAmount = (float) ($lease->deposit_amount ?? 0);
        $unpaidInvoices = \App\Models\Invoice::where('lease_id', $lease->id)
            ->whereIn('status', ['issued', 'overdue'])
            ->whereNull('deleted_at')
            ->with(['items'])
            ->get();
        $unpaidTotal = (float) $unpaidInvoices->sum(function ($inv) { return (float) $inv->remaining_amount; });
        $ticketDepositTotal = (float) \App\Models\TicketLog::whereHas('ticket', function($q) use ($lease) {
                $q->where('lease_id', $lease->id);
            })
            ->whereNull('deleted_at')
            ->where('charge_to', 'tenant_deposit')
            ->where('cost_amount', '>', 0)
            ->sum('cost_amount');
        $refundAmount = $depositAmount - $unpaidTotal - $ticketDepositTotal;

        // Get meters for this unit and readings within lease period
        $meters = \App\Models\Meter::where('unit_id', $lease->unit_id)
            ->whereNull('deleted_at')
            ->with(['service', 'readings' => function($q) {
                $q->with('takenBy')->latest('reading_date')->limit(1);
            }])
            ->get();
        
        // Get meter readings within lease period
        $leaseStartDate = $lease->start_date ? \Carbon\Carbon::parse($lease->start_date)->startOfDay() : null;
        $leaseEndDate = $lease->end_date ? \Carbon\Carbon::parse($lease->end_date)->endOfDay() : null;
        
        $meterReadings = collect();
        if ($leaseStartDate && $leaseEndDate) {
            $meterReadings = \App\Models\MeterReading::whereIn('meter_id', $meters->pluck('id'))
                ->whereBetween('reading_date', [$leaseStartDate->format('Y-m-d'), $leaseEndDate->format('Y-m-d')])
                ->with(['meter.service', 'meter.readings', 'takenBy'])
                ->orderBy('reading_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        // Check expiration status - only for active leases, with floor rounding
        if ($lease->status === 'active' && $lease->end_date) {
            $daysUntilExpiry = floor(now()->diffInDays($lease->end_date, false));
            $isExpiringSoon = $daysUntilExpiry >= 0 && $daysUntilExpiry <= 30;
            $isExpired = $daysUntilExpiry < 0;
        } else {
            $daysUntilExpiry = null;
            $isExpiringSoon = false;
            $isExpired = false;
        }

        // Check if there's a cycle that needs an invoice
        $nextUnpaidCycle = null;
        $canCreateCycleInvoice = false;
        if ($lease->status === 'active' && $hasFirstInvoice) {
            $nextUnpaidCycle = $this->getNextUnpaidCycle($lease);
            $canCreateCycleInvoice = $nextUnpaidCycle !== null;
        }

        // Load users for resident dropdown
        $users = collect();
        try {
            $isManager = $this->checkCapability('party.user.view');
            
            // Get excluded user IDs: tenant (chủ hợp đồng) and existing residents
            $excludedUserIds = [];
            
            // Exclude tenant (chủ hợp đồng)
            if ($lease->tenant_id) {
                $excludedUserIds[] = $lease->tenant_id;
            }
            
            // Exclude existing residents
            $existingResidentUserIds = \App\Models\LeaseResident::where('lease_id', $lease->id)
                ->whereNotNull('user_id')
                ->pluck('user_id')
                ->toArray();
            
            $excludedUserIds = array_merge($excludedUserIds, $existingResidentUserIds);
            $excludedUserIds = array_unique($excludedUserIds);
            
            $query = User::join('organization_users', 'users.id', '=', 'organization_users.user_id')
                ->where('organization_users.organization_id', $userOrganizationId)
                ->whereNull('organization_users.deleted_at')
                ->whereNull('users.deleted_at')
                ->select('users.*')
                ->distinct()
                ->with('userProfile');
            
            // Exclude tenant and existing residents
            if (!empty($excludedUserIds)) {
                $query->whereNotIn('users.id', $excludedUserIds);
            }
            
            // For agent, only show tenants from their leases
            if (!$canViewAll) {
                $tenantIds = Lease::where('agent_id', $user->id)
                    ->where('organization_id', $userOrganizationId)
                    ->pluck('tenant_id')
                    ->unique()
                    ->toArray();
                
                if (!empty($tenantIds)) {
                    // Filter out excluded users from tenantIds
                    $availableTenantIds = array_diff($tenantIds, $excludedUserIds);
                    
                    if (!empty($availableTenantIds)) {
                        $query->whereIn('users.id', $availableTenantIds);
                        $users = $query->get()
                            ->sortBy(function($user) {
                                return $user->full_name ?? $user->email ?? $user->phone ?? '';
                            })
                            ->values();
                    }
                }
                // If no available tenantIds, $users remains empty collection
            } else {
                // Manager can see all users (except excluded ones)
                $users = $query->get()
                    ->sortBy(function($user) {
                        return $user->full_name ?? $user->email ?? $user->phone ?? '';
                    })
                    ->values();
            }
        } catch (\Exception $e) {
            Log::error('Error loading users for resident dropdown: ' . $e->getMessage());
        }

        return view('staff.contract.leases.show', compact(
            'lease', 
            'invoices', 
            'tickets', 
            'ticketDepositLogs',
            'daysUntilExpiry',
            'isExpiringSoon',
            'isExpired',
            'hasFirstInvoice',
            'depositAmount',
            'unpaidInvoices',
            'unpaidTotal',
            'ticketDepositTotal',
            'refundAmount',
            'meters',
            'meterReadings',
            'leaseStartDate',
            'leaseEndDate',
            'canCreateCycleInvoice',
            'nextUnpaidCycle',
            'users'
        ));
    }

    /**
     * Download contract PDF
     */
    public function download($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('contract.lease.view', 'Bạn không có quyền xem hợp đồng.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        $lease = Lease::with([
            'organization',
            'unit.property.location',
            'unit.property.location2025',
            'unit.property.propertyType',
            'leaseServiceSet.items.service',
            'paymentCycle',
            'agent',
            'tenant'
        ])->where('organization_id', $organizationId)->findOrFail($id);

        // For agent, check if lease was created by them
        $canViewAll = $this->canViewAll('contract.lease');
        if (!$canViewAll && $lease->agent_id !== $user->id) {
            abort(403, 'Bạn không có quyền tải hợp đồng này.');
        }

        // Generate PDF using facade
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.contract', ['contract' => $lease]);
        
        // Set PDF options
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOption('enable-local-file-access', true);
        
        // Return PDF download
        $filename = 'hop-dong-' . $lease->contract_no . '.pdf';
        return $pdf->download($filename);
    }

    public function edit($id)
    {
        // Check capability
        $this->requireCapability('contract.lease.update', 'Bạn không có quyền cập nhật hợp đồng.');
        // Get user's organization from middleware
        $userOrganizationId = $this->getCurrentOrganizationId();
        
        $lease = Lease::with([
            'unit.property',
            'leaseServiceSet.items.service',
            'paymentCycle',
            'bookingDeposit.lead'
        ])->where('organization_id', $userOrganizationId)->findOrFail($id);

        // Không cho phép chỉnh sửa hợp đồng đang hoạt động
        if ($lease->status === 'active') {
            return redirect()->route('staff.leases.show', $id)
                ->with('warning', 'Không thể chỉnh sửa hợp đồng đang ở trạng thái hoạt động. Vui lòng chuyển về trạng thái nháp (Draft) trước khi chỉnh sửa.');
        }

        // For agent, check if lease was created by them
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $canViewAll = $this->canViewAll('contract.lease');
        if (!$canViewAll && $lease->agent_id !== $user->id) {
            abort(403, 'Bạn không có quyền chỉnh sửa hợp đồng này.');
        }

        // Ensure variables are always defined
        $properties = collect();
        $tenants = collect();
        $managers = collect();
        $agents = collect();
        $services = collect();
        
        // Get user's organization from middleware
        $userOrganizationId = $this->getCurrentOrganizationId();
        
        try {
            // Only load properties from user's organization or default organization (3)
            $properties = \App\Models\Property::where(function($q) use ($userOrganizationId) {
                $q->where('organization_id', $userOrganizationId)
                  ->orWhere('organization_id', 3); // Default organization
            })->get();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error loading properties in edit: ' . $e->getMessage());
        }
        
        try {
            // Only load tenants from user's organization or default organization (3)
            $tenants = User::with('userProfile')
                ->whereHas('userRoles', function($q) {
                    $q->where('key_code', 'tenant');
                })
                ->whereHas('organizations', function($q) use ($userOrganizationId) {
                    $q->where('organization_id', $userOrganizationId)
                      ->orWhere('organization_id', 3); // Default organization
                })
                ->whereNull('deleted_at')
                ->get()
                ->sortBy(function($user) {
                    return $user->userProfile->full_name ?? $user->email ?? '';
                })
                ->values();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error loading tenants in edit: ' . $e->getMessage());
        }
        
        try {
            // Get all users in organization (managers and agents, EXCLUDE tenants)
            $allUsers = User::with('userProfile')
                ->whereHas('organizations', function($q) use ($userOrganizationId) {
                    $q->where('organization_id', $userOrganizationId);
                })
                ->whereDoesntHave('userRoles', function($q) {
                    $q->where('key_code', 'tenant'); // Exclude tenants
                })
                ->whereNull('deleted_at')
                ->get()
                ->sortBy(function($user) {
                    return $user->userProfile->full_name ?? $user->email ?? '';
                })
                ->values();
            
            // Get manager IDs (users with 'manager' role)
            $managerIds = \App\Models\User::whereHas('userRoles', function($q) {
                $q->where('key_code', 'manager');
            })
            ->whereHas('organizations', function($q) use ($userOrganizationId) {
                $q->where('organization_id', $userOrganizationId);
            })
            ->pluck('id')
            ->toArray();
            
            // Separate managers and agents
            $managers = $allUsers->filter(function($user) use ($managerIds) {
                return in_array($user->id, $managerIds);
            })->values();
            
            $agents = $allUsers->filter(function($user) use ($managerIds) {
                return !in_array($user->id, $managerIds);
            })->values();
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error loading agents/managers in edit: ' . $e->getMessage());
        }
        
        try {
            // Get services available for this organization (organization-specific + global)
            $services = Service::forOrganization($userOrganizationId)->get();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error loading services in edit: ' . $e->getMessage());
        }

        // Get payment cycles for dropdown
        $paymentCycles = collect();
        $defaultPaymentCycle = null;
        
        if ($userOrganizationId) {
            // Get default payment cycle for this organization
            $defaultPaymentCycle = PaymentCycle::where('organization_id', $userOrganizationId)
                ->where('is_default', true)
                ->first();
            
            // Get all payment cycles for this organization
            $paymentCycles = PaymentCycle::where('organization_id', $userOrganizationId)
                ->whereNull('deleted_at')
                ->orderBy('is_default', 'desc')
                ->orderBy('name', 'asc')
                ->get();
        }

        // Get lease service sets for dropdown
        $leaseServiceSets = collect();
        $defaultLeaseServiceSet = null;
        
        if ($userOrganizationId) {
            // Get default lease service set for this organization
            $defaultLeaseServiceSet = LeaseServiceSet::where('organization_id', $userOrganizationId)
                ->where('is_default', true)
                ->first();
            
            // Get all lease service sets for this organization
            $leaseServiceSets = LeaseServiceSet::where('organization_id', $userOrganizationId)
                ->whereNull('deleted_at')
                ->orderBy('is_default', 'desc')
                ->orderBy('name', 'asc')
                ->get();
        }

        // Get units for selected property
        $units = Unit::where('property_id', $lease->unit->property_id)->get();

        // Get current lead from lease (through tenant or booking deposit) - get this first
        $selectedLeadId = null;
        $selectedLead = null;
        if ($lease->bookingDeposit && $lease->bookingDeposit->lead_id) {
            $selectedLeadId = $lease->bookingDeposit->lead_id;
            $selectedLead = $lease->bookingDeposit->lead;
        } elseif ($lease->tenant_id) {
            // Tìm lead có tenant_id trùng với lease tenant_id
            $selectedLead = \App\Models\Lead::where('tenant_id', $lease->tenant_id)
                ->where('organization_id', $userOrganizationId)
                ->whereNull('deleted_at')
                ->first();
            if ($selectedLead) {
                $selectedLeadId = $selectedLead->id;
            }
        }

        // Get leads for selection
        $leads = collect();
        try {
            if ($userOrganizationId) {
                $leads = \App\Models\Lead::where('organization_id', $userOrganizationId)
                    ->whereNull('deleted_at')
                    ->orderBy('created_at', 'desc')
                    ->limit(100) // Limit to recent 100 leads
                    ->get();
                
                // Đảm bảo selectedLead có trong danh sách nếu chưa có
                if ($selectedLead && $selectedLeadId && !$leads->contains('id', $selectedLeadId)) {
                    $leads->prepend($selectedLead); // Thêm vào đầu danh sách
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error loading leads in edit: ' . $e->getMessage());
        }

        // Get paid booking deposits for selection (bao gồm cả booking deposit hiện tại của lease nếu có)
        $bookingDeposits = collect();
        $selectedBookingDeposit = null;
        try {
            if ($userOrganizationId) {
                // Lấy booking deposit hiện tại của lease nếu có
                if ($lease->booking_id) {
                    $selectedBookingDeposit = \App\Models\BookingDeposit::where('id', $lease->booking_id)
                        ->where('organization_id', $userOrganizationId)
                        ->whereNull('deleted_at')
                        ->with(['unit.property', 'lead', 'agent', 'tenantUser'])
                        ->first();
                }
                
                // Lấy tất cả booking deposits đã thanh toán (bao gồm cả booking đã có hợp đồng để có thể chọn lại)
                $bookingDeposits = \App\Models\BookingDeposit::where('organization_id', $userOrganizationId)
                    ->where('payment_status', 'paid')
                    ->whereNull('deleted_at')
                    ->with(['unit.property', 'lead', 'agent', 'tenantUser'])
                    ->orderBy('paid_at', 'desc')
                    ->limit(100) // Limit to recent 100 paid booking deposits
                    ->get();
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error loading booking deposits in edit: ' . $e->getMessage());
        }

        // Get current user ID for auto-fill
        $currentUserId = Auth::id();

        return view('staff.contract.leases.edit', [
            'lease' => $lease,
            'properties' => $properties,
            'tenants' => $tenants,
            'managers' => $managers,
            'agents' => $agents,
            'services' => $services,
            'paymentCycles' => $paymentCycles,
            'defaultPaymentCycle' => $defaultPaymentCycle,
            'leaseServiceSets' => $leaseServiceSets,
            'defaultLeaseServiceSet' => $defaultLeaseServiceSet,
            'units' => $units,
            'leads' => $leads,
            'bookingDeposits' => $bookingDeposits,
            'selectedBookingDeposit' => $selectedBookingDeposit,
            'selectedBookingDepositId' => $selectedBookingDeposit ? $selectedBookingDeposit->id : null,
            'selectedLeadId' => $selectedLeadId,
            'currentUserId' => $currentUserId
        ]);
    }

    public function update(Request $request, $id)
    {
        // Check capability
        $this->requireCapability('contract.lease.update', 'Bạn không có quyền cập nhật hợp đồng.');
        try {
            // Get user's organization from middleware
            $userOrganizationId = $this->getCurrentOrganizationId();
            
            $lease = Lease::where('organization_id', $userOrganizationId)->findOrFail($id);

            // Không cho phép cập nhật hợp đồng đang hoạt động
            if ($lease->status === 'active') {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Không thể cập nhật hợp đồng đang ở trạng thái hoạt động. Vui lòng chuyển về trạng thái nháp (Draft) trước khi chỉnh sửa.'
                    ], 400);
                }
                return redirect()->route('staff.leases.show', $id)
                    ->with('warning', 'Không thể cập nhật hợp đồng đang ở trạng thái hoạt động. Vui lòng chuyển về trạng thái nháp (Draft) trước khi chỉnh sửa.');
            }

            // For agent, check if lease was created by them
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $canViewAll = $this->canViewAll('contract.lease');
            if (!$canViewAll && $lease->agent_id !== $user->id) {
                abort(403, 'Bạn không có quyền cập nhật hợp đồng này.');
            }

            // SECURITY CHECK: Prevent organization manipulation
            // Loại bỏ user_organization_id khỏi request vì nó được thêm bởi middleware, không phải từ form
            $requestData = $request->except(['user_organization_id', '_token', '_method']);
            
            $dangerousFields = ['organization_id', 'org_id', 'user_org_id'];
            $suspiciousFields = array_intersect($dangerousFields, array_keys($requestData));
            
            if (!empty($suspiciousFields)) {
                Log::critical('SECURITY ALERT: Attempted lease organization manipulation', [
                    'lease_id' => $id,
                    'user_id' => Auth::id(),
                    'ip_address' => $request->ip(),
                    'suspicious_fields' => $suspiciousFields,
                    'request_data' => $requestData
                ]);
                
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Yêu cầu không hợp lệ. Phát hiện các trường không được phép: ' . implode(', ', $suspiciousFields) . '. Hành động đã được ghi nhận.',
                        'errors' => ['security' => ['Phát hiện các trường không được phép trong request.']]
                    ], 403);
                }
                abort(403, 'Yêu cầu không hợp lệ. Phát hiện các trường không được phép: ' . implode(', ', $suspiciousFields));
            }

            // Normalize data before validation
            // Unformat money inputs (remove dots and non-digit characters)
            if ($request->has('rent_amount')) {
                $request->merge([
                    'rent_amount' => preg_replace('/[^\d]/', '', $request->rent_amount)
                ]);
            }
            
            if ($request->has('deposit_amount') && $request->deposit_amount) {
                $request->merge([
                    'deposit_amount' => preg_replace('/[^\d]/', '', $request->deposit_amount)
                ]);
            }
            
            $validated = $request->validate([
                'lead_id' => 'required|exists:leads,id',
                'booking_deposit_id' => [
                    'nullable',
                    'exists:booking_deposits,id',
                    function ($attribute, $value, $fail) use ($userOrganizationId, $id) {
                        if ($value) {
                            // Kiểm tra booking deposit thuộc organization
                            $bookingDeposit = \App\Models\BookingDeposit::where('id', $value)
                                ->where('organization_id', $userOrganizationId)
                                ->first();
                            
                            if (!$bookingDeposit) {
                                $fail('Booking deposit không tồn tại hoặc không thuộc tổ chức của bạn.');
                            }
                        }
                    },
                ],
                'unit_id' => 'required|exists:units,id',
                'tenant_id' => 'nullable|exists:users,id', // tenant_id có thể null, sẽ được tạo từ lead
                'agent_id' => 'nullable|exists:users,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'rent_amount' => 'required|numeric|min:0',
                'deposit_amount' => 'nullable|numeric|min:0',
                // Status không được thay đổi từ form edit, giữ nguyên status hiện tại
                // 'status' => 'required|in:draft,active,terminated,expired',
                'contract_no' => 'nullable|string|max:100|unique:leases,contract_no,' . $id,
                'signed_at' => 'nullable|date',
                'lease_services_id' => 'nullable|exists:lease_service_sets,id',
                'payment_cycle_id' => 'nullable|exists:payment_cycles,id',
            ], [
                'lead_id.required' => 'Vui lòng chọn lead.',
                'lead_id.exists' => 'Lead đã chọn không tồn tại.',
                'unit_id.required' => 'Vui lòng chọn phòng.',
                'lease_services_id.exists' => 'Nhóm dịch vụ đã chọn không tồn tại.',
                'unit_id.exists' => 'Phòng đã chọn không tồn tại.',
                'tenant_id.exists' => 'Khách thuê đã chọn không tồn tại.',
                'start_date.required' => 'Vui lòng nhập ngày bắt đầu.',
                'start_date.date' => 'Ngày bắt đầu không hợp lệ.',
                'end_date.required' => 'Vui lòng nhập ngày kết thúc.',
                'end_date.date' => 'Ngày kết thúc không hợp lệ.',
                'end_date.after' => 'Ngày kết thúc phải sau ngày bắt đầu.',
                'rent_amount.required' => 'Vui lòng nhập tiền thuê.',
                'rent_amount.numeric' => 'Tiền thuê phải là số.',
                'rent_amount.min' => 'Tiền thuê phải lớn hơn hoặc bằng 0.',
                'deposit_amount.numeric' => 'Tiền cọc phải là số.',
                'deposit_amount.min' => 'Tiền cọc phải lớn hơn hoặc bằng 0.',
                'contract_no.unique' => 'Số hợp đồng đã tồn tại.',
                'signed_at.date' => 'Ngày ký hợp đồng không hợp lệ.',
                'services.*.service_id.required' => 'Vui lòng chọn dịch vụ.',
                'services.*.service_id.exists' => 'Dịch vụ đã chọn không tồn tại.',
                'services.*.price.required' => 'Vui lòng nhập giá dịch vụ.',
                'services.*.price.numeric' => 'Giá dịch vụ phải là số.',
                'services.*.price.min' => 'Giá dịch vụ phải lớn hơn hoặc bằng 0.',
                'lease_services_id.exists' => 'Nhóm dịch vụ đã chọn không tồn tại.',
                'payment_cycle_id.exists' => 'Chu kỳ thanh toán đã chọn không tồn tại.',
            ]);

            // Kiểm tra phòng mới đã có hợp đồng hoạt động (trừ hợp đồng hiện tại)
            if ($validated['unit_id'] != $lease->unit_id) {
                $hasActiveLease = Lease::where('unit_id', $validated['unit_id'])
                    ->where('status', 'active')
                    ->where('id', '!=', $id)
                    ->whereNull('deleted_at')
                    ->exists();

                if ($hasActiveLease) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Phòng này đã có hợp đồng hoạt động. Vui lòng chọn phòng khác hoặc chấm dứt hợp đồng hiện tại trước.'
                        ], 422);
                    }
                    return back()->withInput()->with('error', 'Phòng này đã có hợp đồng hoạt động. Vui lòng chọn phòng khác hoặc chấm dứt hợp đồng hiện tại trước.');
                }
            }

            DB::beginTransaction();

            // Xử lý tenant từ lead_id
            $tenantId = $validated['tenant_id'] ?? null;
            $lead = \App\Models\Lead::find($validated['lead_id']);
            
            if (!$lead) {
                DB::rollBack();
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Lead không tồn tại.'
                    ], 404);
                }
                return back()->withInput()->with('error', 'Lead không tồn tại.');
            }
            
            // Nếu lead đã có tenant_id, sử dụng tenant đó
            if ($lead->tenant_id) {
                $tenantId = $lead->tenant_id;
            } elseif ($validated['tenant_id']) {
                // Nếu có tenant_id từ form, sử dụng tenant đó
                $tenantId = $validated['tenant_id'];
            } else {
                // Nếu không có tenant_id, tạo mới từ lead (tương tự store method)
                // Logic tạo tenant từ lead sẽ được xử lý ở đây nếu cần
                // Tạm thời sử dụng tenant_id hiện tại của lease
                $tenantId = $lease->tenant_id;
            }

            // Xử lý booking_deposit_id
            $bookingId = null;
            if (!empty($validated['booking_deposit_id'])) {
                $bookingDeposit = \App\Models\BookingDeposit::where('id', $validated['booking_deposit_id'])
                    ->where('organization_id', $userOrganizationId)
                    ->whereNull('deleted_at')
                    ->first();
                
                if ($bookingDeposit) {
                    $bookingId = $bookingDeposit->id;
                }
            }

            // Tự động gán agent_id cho agent (không cho phép sửa)
            // Manager có thể gán cho agent khác, Agent phải gán cho chính mình
            $this->enforceAgentId($validated, 'agent_id');

            // Handle payment cycle
            $paymentCycleId = $this->handlePaymentCycle($validated, $userOrganizationId);

            // Update lease
            $lease->update([
                'unit_id' => $validated['unit_id'],
                'tenant_id' => $tenantId,
                'agent_id' => $validated['agent_id'],
                'booking_id' => $bookingId, // Update booking_id
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'rent_amount' => $validated['rent_amount'],
                'deposit_amount' => $validated['deposit_amount'] ?? 0,
                'status' => $lease->status, // Giữ nguyên status hiện tại, không thay đổi từ form
                'contract_no' => $validated['contract_no'],
                'signed_at' => $validated['signed_at'],
                'payment_cycle_id' => $paymentCycleId,
            ]);

            // Handle lease service set - chỉ cập nhật nếu có giá trị
            if (!empty($validated['lease_services_id'])) {
                $leaseServiceSet = LeaseServiceSet::where('id', $validated['lease_services_id'])
                    ->where(function($query) use ($userOrganizationId) {
                        $query->where('organization_id', $userOrganizationId)
                              ->orWhereNull('organization_id');
                    })
                    ->first();
                
                if (!$leaseServiceSet) {
                    DB::rollBack();
                    if ($request->expectsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Nhóm dịch vụ đã chọn không tồn tại hoặc không thuộc tổ chức của bạn.'
                        ], 422);
                    }
                    return back()->withInput()->with('error', 'Nhóm dịch vụ đã chọn không tồn tại hoặc không thuộc tổ chức của bạn.');
                }
                
                // Update lease with service set ID
                $lease->update([
                    'lease_services_id' => $leaseServiceSet->id,
                ]);
            } else {
                // Nếu không có lease_services_id, set về null
                $lease->update([
                    'lease_services_id' => null,
                ]);
            }

            // Cập nhật trạng thái phòng dựa trên trạng thái hợp đồng (giữ nguyên status hiện tại)
            $this->updateUnitStatusBasedOnLease($lease, $lease->status);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Hợp đồng đã được cập nhật thành công!',
                    'redirect' => route('staff.leases.show', $lease->id)
                ]);
            }

            return redirect()->route('staff.leases.show', $lease->id)
                ->with('success', 'Hợp đồng đã được cập nhật thành công!');

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::error('Validation error in LeaseController@update: ' . json_encode($e->errors()));
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ. Vui lòng kiểm tra lại các trường bắt buộc.',
                    'errors' => $e->errors()
                ], 422);
            }
            
            return back()->withInput()->withErrors($e->errors())->with('error', 'Dữ liệu không hợp lệ.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in LeaseController@update: ' . $e->getMessage());
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi cập nhật hợp đồng: ' . $e->getMessage()
                ], 500);
            }

            return back()->withInput()->with('error', 'Có lỗi xảy ra khi cập nhật hợp đồng: ' . $e->getMessage());
        }
    }

    /**
     * Terminate lease (manager) with simplified refund/balance rules
     */
    public function terminate(Request $request, $id)
    {
        // Check capability
        $this->requireCapability('contract.lease.terminate', 'Bạn không có quyền kết thúc hợp đồng.');
        // Get user's organization from middleware
        $userOrganizationId = $this->getCurrentOrganizationId();
        $manager = \Illuminate\Support\Facades\Auth::user();

        $lease = Lease::where('organization_id', $userOrganizationId)->findOrFail($id);

        // For agent, check if lease was created by them
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $canViewAll = $this->canViewAll('contract.lease');
        if (!$canViewAll && $lease->agent_id !== $user->id) {
            abort(403, 'Bạn không có quyền kết thúc hợp đồng này.');
        }

        $request->validate([
            'termination_reason' => 'required|string|max:500',
            'termination_date' => 'required|date|after_or_equal:today',
            'refund_deposit' => 'nullable|boolean',
        ], [
            'termination_reason.required' => 'Vui lòng nhập lý do chấm dứt hợp đồng.',
            'termination_date.required' => 'Vui lòng chọn ngày chấm dứt.',
            'termination_date.after_or_equal' => 'Ngày chấm dứt phải từ hôm nay trở đi.',
        ]);

        try {
            // Validate termination_date >= start_date (constraint chk_lease_dates requires end_date > start_date)
            $terminationDate = \Carbon\Carbon::parse($request->termination_date)->startOfDay();
            $startDate = \Carbon\Carbon::parse($lease->start_date)->startOfDay();
            
            if ($terminationDate->lt($startDate)) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Ngày chấm dứt không thể trước ngày bắt đầu hợp đồng.',
                        'errors' => ['termination_date' => ['Ngày chấm dứt không thể trước ngày bắt đầu hợp đồng.']]
                    ], 422);
                }
                return redirect()->back()
                    ->withErrors(['termination_date' => 'Ngày chấm dứt không thể trước ngày bắt đầu hợp đồng.'])
                    ->withInput();
            }
            
            // Nếu termination_date = start_date, set end_date = start_date + 1 day để thỏa constraint end_date > start_date
            $endDate = $terminationDate;
            if ($terminationDate->eq($startDate)) {
                $endDate = $startDate->copy()->addDay();
            }

            \Illuminate\Support\Facades\DB::beginTransaction();

            // Terminate lease first
            $lease->update([
                'status' => 'terminated',
                'end_date' => $endDate->format('Y-m-d'),
                'termination_date' => $request->termination_date,
                'termination_reason' => $request->termination_reason,
            ]);

            // Update unit status
            $this->updateUnitStatusBasedOnLease($lease, 'terminated');

            // If not refunding deposit, done
            if (!$request->boolean('refund_deposit', false)) {
                \Illuminate\Support\Facades\DB::commit();
                if ($request->expectsJson()) {
                    return response()->json(['success' => true, 'message' => 'Đã chấm dứt hợp đồng. Không hoàn cọc.']);
                }
                return redirect()->route('staff.leases.show', $lease->id)
                    ->with('success', 'Đã chấm dứt hợp đồng. Không hoàn cọc.');
            }

            // Compute net = deposit - unpaid invoices - ticket deposit costs
            $depositAmount = (float) ($lease->deposit_amount ?? 0);

            $unpaidTotal = (float) \App\Models\Invoice::where('lease_id', $lease->id)
                ->whereIn('status', ['issued', 'overdue'])
                ->whereNull('deleted_at')
                ->get()
                ->sum(function ($inv) { return (float) $inv->remaining_amount; });

            $ticketDepositTotal = (float) \App\Models\TicketLog::whereHas('ticket', function($q) use ($lease) {
                    $q->where('lease_id', $lease->id);
                })
                ->whereNull('deleted_at')
                ->where('charge_to', 'tenant_deposit')
                ->where('cost_amount', '>', 0)
                ->sum('cost_amount');

            $net = $depositAmount - $unpaidTotal - $ticketDepositTotal;

            if ($net > 0) {
                // Auto-generate refund_reference first
                $orgId = $lease->organization_id ?? $userOrganizationId;
                $depositRefundTemp = new \App\Models\DepositRefund();
                $depositRefundTemp->organization_id = $orgId;
                $refundReference = $depositRefundTemp->generateRefundReference($orgId);

                // Create deposit refund record FIRST (company must pay to tenant)
                // This must be created before CompanyInvoice to satisfy validation
                $depositRefund = \App\Models\DepositRefund::create([
                    'lease_id' => $lease->id,
                    'organization_id' => $orgId,
                    'unit_id' => $lease->unit_id,
                    'tenant_id' => $lease->tenant_id,
                    'agent_id' => $manager->id,
                    'original_deposit_amount' => $depositAmount,
                    'deducted_amount' => ($unpaidTotal + $ticketDepositTotal),
                    'refund_amount' => $net,
                    'status' => \App\Models\DepositRefund::STATUS_PENDING,
                    'refund_method' => 'bank_transfer',
                    'refund_reference' => $refundReference,
                    'notes' => 'Hoàn cọc tự động khi chấm dứt (Manager)',
                    'deduction_details' => json_encode([
                        'unpaid_invoices' => $unpaidTotal,
                        'ticket_deposit' => $ticketDepositTotal,
                    ]),
                    'created_by' => $manager->id,
                ]);

                // Now create company invoice with deposit_refund_id already set
                // This prevents validation error in CompanyInvoiceObserver
                $ci = new \App\Models\CompanyInvoice();
                $ci->organization_id = $lease->organization_id ?? ($lease->unit->property->organization_id ?? $userOrganizationId);
                $ci->vendor_id = null;
                $ci->user_id = $lease->tenant_id;
                $ci->invoice_type = 'deposit_refund';
                $ci->deposit_refund_id = $depositRefund->id; // Set deposit_refund_id BEFORE saving
                $ci->issue_date = now()->toDateString();
                $ci->due_date = now()->toDateString();
                $ci->status = \App\Models\CompanyInvoice::STATUS_PENDING;
                $items = [];
                if ($depositAmount > 0) {
                    $items[] = [
                        'item_type' => 'deposit',
                        'description' => 'Tiền cọc ban đầu',
                        'quantity' => 1,
                        'unit_price' => $depositAmount,
                        'amount' => $depositAmount,
                        'meta_json' => [ 'lease_id' => $lease->id ],
                    ];
                }
                if ($unpaidTotal > 0) {
                    $items[] = [
                        'item_type' => 'service',
                        'description' => 'Khấu trừ hóa đơn chưa thanh toán',
                        'quantity' => 1,
                        'unit_price' => -$unpaidTotal,
                        'amount' => -$unpaidTotal,
                        'meta_json' => [ 'lease_id' => $lease->id, 'type' => 'unpaid_invoices' ],
                    ];
                }
                if ($ticketDepositTotal > 0) {
                    $items[] = [
                        'item_type' => 'ticket_cost',
                        'description' => 'Khấu trừ ticket trừ cọc',
                        'quantity' => 1,
                        'unit_price' => -$ticketDepositTotal,
                        'amount' => -$ticketDepositTotal,
                        'meta_json' => [ 'lease_id' => $lease->id, 'type' => 'ticket_deposit' ],
                    ];
                }
                $subtotal = array_sum(array_map(fn($i) => (float) $i['amount'], $items));
                $ci->subtotal = $subtotal;
                $ci->tax_amount = 0;
                $ci->discount_amount = 0;
                $ci->total_amount = $subtotal;
                $ci->currency = 'VND';
                $ci->description = 'Hoàn cọc khi chấm dứt hợp đồng';
                $ci->note = 'Manager terminate - Lease #' . $lease->id;
                $ci->created_by = $manager->id;
                $ci->save();
                
                foreach ($items as $item) {
                    \App\Models\CompanyInvoiceItem::create(array_merge($item, [
                        'company_invoice_id' => $ci->id,
                    ]));
                }

                // Deposit refund is already linked to lease via deposit_refunds.lease_id

                \Illuminate\Support\Facades\DB::commit();
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true, 
                        'message' => 'Đã chấm dứt hợp đồng và tạo phiếu hoàn cọc cho khách thuê.',
                        'deposit_refund_id' => $depositRefund->id
                    ]);
                }
                return redirect()->route('staff.deposit-refunds.show', $depositRefund->id)
                    ->with('success', 'Đã chấm dứt hợp đồng và tạo phiếu hoàn cọc cho khách thuê.');
            }

            if ($net < 0) {
                $amountDue = abs($net);
                $inv = \App\Models\Invoice::create([
                    'organization_id' => $lease->organization_id ?? $userOrganizationId,
                    'is_auto_created' => true,
                    'lease_id' => $lease->id,
                    'invoice_no' => \App\Models\Invoice::generateInvoiceNumber($lease->organization_id ?? $userOrganizationId),
                    'issue_date' => now()->toDateString(),
                    'due_date' => now()->addDays(7)->toDateString(),
                    'status' => 'draft',
                    'subtotal' => $amountDue,
                    'tax_amount' => 0,
                    'discount_amount' => 0,
                    'total_amount' => $amountDue,
                    'currency' => 'VND',
                    'note' => 'Bù thêm khi chấm dứt hợp đồng (Manager)',
                    'created_by' => $manager->id,
                ]);
                // Add detailed deduction items: unpaid invoices (+), ticket deposit (+), deposit deduction (-)
                if ($unpaidTotal > 0) {
                    \App\Models\InvoiceItem::create([
                        'invoice_id' => $inv->id,
                        'item_type' => 'service',
                        'description' => 'Cộng các hóa đơn chưa thanh toán',
                        'quantity' => 1,
                        'unit_price' => $unpaidTotal,
                        'amount' => $unpaidTotal,
                        'meta_json' => [ 'lease_id' => $lease->id, 'type' => 'unpaid_invoices' ],
                    ]);
                }
                if ($ticketDepositTotal > 0) {
                    \App\Models\InvoiceItem::create([
                        'invoice_id' => $inv->id,
                        'item_type' => 'ticket_cost',
                        'description' => 'Cộng chi phí ticket trừ cọc',
                        'quantity' => 1,
                        'unit_price' => $ticketDepositTotal,
                        'amount' => $ticketDepositTotal,
                        'meta_json' => [ 'lease_id' => $lease->id, 'type' => 'ticket_deposit' ],
                    ]);
                }
                if ($depositAmount > 0) {
                    \App\Models\InvoiceItem::create([
                        'invoice_id' => $inv->id,
                        'item_type' => 'deposit',
                        'description' => 'Khấu trừ từ tiền cọc',
                        'quantity' => 1,
                        'unit_price' => -$depositAmount,
                        'amount' => -$depositAmount,
                        'meta_json' => [ 'lease_id' => $lease->id, 'type' => 'deposit' ],
                    ]);
                }
                // Cancel old unpaid invoices
                $unpaidInvoices = \App\Models\Invoice::where('lease_id', $lease->id)
                    ->whereIn('status', ['issued', 'overdue'])
                    ->whereNull('deleted_at')
                    ->where('id', '!=', $inv->id)
                    ->get();
                foreach ($unpaidInvoices as $uinv) {
                    $uinv->update(['status' => 'cancelled']);
                }
                \Illuminate\Support\Facades\DB::commit();
                if ($request->expectsJson()) {
                    return response()->json(['success' => true, 'message' => 'Đã chấm dứt hợp đồng, tạo hóa đơn bù và hủy các hóa đơn cũ chưa thanh toán.']);
                }
                return redirect()->route('staff.leases.show', $lease->id)
                    ->with('success', 'Đã chấm dứt hợp đồng, tạo hóa đơn bù và hủy các hóa đơn cũ chưa thanh toán.');
            }

            \Illuminate\Support\Facades\DB::commit();
            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => 'Đã chấm dứt hợp đồng. Không phát sinh hoàn/bù.']);
            }
            return redirect()->route('staff.leases.show', $lease->id)
                ->with('success', 'Đã chấm dứt hợp đồng. Không phát sinh hoàn/bù.');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Manager terminate lease error: ' . $e->getMessage());
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
            }
            return back()->withInput()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        // Check capability
        $this->requireCapability('contract.lease.delete', 'Bạn không có quyền xóa hợp đồng.');
        try {
            // Get user's organization from middleware
            $userOrganizationId = $this->getCurrentOrganizationId();
            
            $lease = Lease::where('organization_id', $userOrganizationId)->findOrFail($id);
            
            // Không cho phép xóa hợp đồng đang hoạt động
            if ($lease->status === 'active') {
                if (request()->expectsJson() || request()->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Không thể xóa hợp đồng đang ở trạng thái hoạt động. Vui lòng chuyển về trạng thái nháp (Draft) hoặc chấm dứt hợp đồng trước khi xóa.'
                    ], 400);
                }
                return redirect()->route('staff.leases.show', $id)
                    ->with('warning', 'Không thể xóa hợp đồng đang ở trạng thái hoạt động. Vui lòng chuyển về trạng thái nháp (Draft) hoặc chấm dứt hợp đồng trước khi xóa.');
            }
            
            // For agent, check if lease was created by them
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $canViewAll = $this->canViewAll('contract.lease');
            if (!$canViewAll && $lease->agent_id !== $user->id) {
                abort(403, 'Bạn không có quyền xóa hợp đồng này.');
            }
            
            DB::beginTransaction();
            
            // Soft delete the lease (trait sẽ tự động set deleted_by)
            $lease->delete();

            // Cập nhật trạng thái phòng khi xóa hợp đồng
            $this->updateUnitStatusAfterLeaseDeletion($lease);

            DB::commit();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Hợp đồng đã được xóa thành công!'
                ]);
            }

            return redirect()->route('staff.leases.index')
                ->with('success', 'Hợp đồng đã được xóa thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting lease: ' . $e->getMessage());
            
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi xóa hợp đồng: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Có lỗi xảy ra khi xóa hợp đồng: ' . $e->getMessage());
        }
    }

    /**
     * Generate contract number with format: HD-{org_id}-{year}-{month}-{sequence}
     * 
     * @return string Contract number
     * @throws \Exception If organization ID cannot be determined
     */
    private function generateContractNumber()
    {
        try {
            // Get organization ID
            $organizationId = $this->getCurrentOrganizationId();
            
            if (!$organizationId) {
                throw new \Exception('Organization ID is required to generate contract number');
            }
            
            $year = date('Y');
            $month = date('m');
            $sequenceKey = SequenceGenerator::buildKey('lease', $organizationId, $year, $month);
            
            $newSequence = SequenceGenerator::getNext($sequenceKey, function() use ($organizationId, $year, $month) {
                // Find max from existing leases
                // Support both old format (HD000123) and new format (HD-1-2025-11-000123)
                $existingLeases = Lease::withTrashed()
                    ->where('organization_id', $organizationId)
                    ->where(function($query) use ($year, $month) {
                        $query->where('contract_no', 'like', 'HD%')
                              ->orWhere('contract_no', 'like', "HD-%-{$year}-{$month}-%");
                    })
                    ->whereNotNull('contract_no')
                    ->pluck('contract_no')
                    ->toArray();
                
                $maxNumber = 0;
                foreach ($existingLeases as $contractNo) {
                    // Parse new format: "HD-1-2025-11-000123" => 123
                    // Parse old format: "HD000123" => 123
                    if (strpos($contractNo, '-') !== false) {
                        // New format: HD-{org_id}-{year}-{month}-{sequence}
                        $parts = explode('-', $contractNo);
                        if (count($parts) >= 5) {
                            $number = (int) preg_replace('/[^0-9]/', '', $parts[4]);
                        } else {
                            $number = 0;
                        }
                    } else {
                        // Old format: HD{sequence}
                        $numberStr = substr($contractNo, 2); // Bỏ "HD"
                        $number = (int) preg_replace('/[^0-9]/', '', $numberStr);
                    }
                    if ($number > $maxNumber) {
                        $maxNumber = $number;
                    }
                }
                return $maxNumber;
            });
            
            // Generate contract number with new format: HD-{org_id}-{year}-{month}-{sequence}
            $contractNumber = "HD-{$organizationId}-{$year}-{$month}-" . str_pad($newSequence, 6, '0', STR_PAD_LEFT);
            
            // Double-check to ensure uniqueness (excluding soft-deleted records)
            $exists = Lease::where('contract_no', $contractNumber)
                ->whereNull('deleted_at')
                ->where('organization_id', $organizationId)
                ->exists();
            
            if ($exists) {
                // If exists, retry with incremented sequence (max 10 retries)
                $maxRetries = 10;
                $retries = 0;
                
                while ($exists && $retries < $maxRetries) {
                    $newSequence++;
                    SequenceGenerator::reset($sequenceKey, $newSequence);
                    
                    $contractNumber = "HD-{$organizationId}-{$year}-{$month}-" . str_pad($newSequence, 6, '0', STR_PAD_LEFT);
                    $exists = Lease::where('contract_no', $contractNumber)
                        ->whereNull('deleted_at')
                        ->where('organization_id', $organizationId)
                        ->exists();
                    $retries++;
                }
                
                if ($exists) {
                    // If still exists after retries, use timestamp fallback
                    Log::warning('Could not generate unique contract number after retries, using timestamp fallback');
                    $contractNumber = "HD-{$organizationId}-{$year}-{$month}-" . str_pad(time() % 1000000, 6, '0', STR_PAD_LEFT);
                }
            }
            
            return $contractNumber;
            
        } catch (\Exception $e) {
            Log::error('Error in generateContractNumber: ' . $e->getMessage());
            // Fallback: return a simple contract number based on timestamp
            $organizationId = $this->getCurrentOrganizationId() ?? 0;
            $year = date('Y');
            $month = date('m');
            return "HD-{$organizationId}-{$year}-{$month}-" . str_pad(time() % 1000000, 6, '0', STR_PAD_LEFT);
        }
    }

    // API method to get next contract number
    public function getNextContractNumber()
    {
        try {
            $contractNumber = $this->generateContractNumber();
            return response()->json([
                'success' => true,
                'contract_no' => $contractNumber
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating contract number: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Có lỗi xảy ra khi sinh mã hợp đồng: ' . $e->getMessage()
            ], 500);
        }
    }

    // API method to get units for a property
    public function getUnits($propertyId, Request $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $organizationId = $this->getCurrentOrganizationId();
            
            // Verify property belongs to organization
            $property = \App\Models\Property::where('id', $propertyId)
                ->where('organization_id', $organizationId)
                ->whereNull('deleted_at')
                ->first();
            
            if (!$property) {
                return response()->json(['error' => 'Bất động sản không tồn tại hoặc bạn không có quyền truy cập'], 404);
            }
            
            // Lấy booking_deposit_id từ query parameter (nếu có)
            $bookingDepositId = $request->get('booking_deposit_id');
            
            // Chỉ hiển thị các phòng trống (available) chưa có hợp đồng thuê đang hoạt động
            // Nếu có booking_deposit_id, cho phép hiển thị phòng thuộc booking deposit đó (kể cả reserved)
            $unitsQuery = Unit::where('property_id', $propertyId)
                ->whereNull('deleted_at')
                ->whereDoesntHave('leases', function($q) {
                    // Loại bỏ phòng có hợp đồng thuê đang hoạt động
                    $q->where('status', 'active')
                      ->whereNull('deleted_at');
                });
            
            // Nếu có booking_deposit_id, cho phép hiển thị phòng thuộc booking deposit đó
            // Ngược lại, loại bỏ phòng có booking deposit đã thanh toán (trừ khi thuộc booking deposit đang chọn)
            if ($bookingDepositId) {
                // Cho phép hiển thị phòng thuộc booking deposit đang chọn (kể cả status reserved)
                // Logic: Unit thuộc booking deposit đang chọn HOẶC unit không có booking deposit đã thanh toán nào
                $unitsQuery->where(function($q) use ($bookingDepositId) {
                    // Cho phép phòng thuộc booking deposit đang chọn (kể cả status reserved)
                    $q->whereHas('bookingDeposits', function($bookingQ) use ($bookingDepositId) {
                        $bookingQ->where('id', $bookingDepositId)
                                ->where('payment_status', 'paid')
                                ->whereNull('deleted_at')
                                ->whereDoesntHave('lease'); // Chưa có hợp đồng
                    })
                    // HOẶC phòng không có booking deposit đã thanh toán nào VÀ status là available
                    ->orWhere(function($subQ) {
                        $subQ->where('status', 'available')
                            ->whereDoesntHave('bookingDeposits', function($bookingQ) {
                                $bookingQ->where('payment_status', 'paid')
                                        ->whereNull('deleted_at')
                                        ->whereDoesntHave('lease'); // Chưa có hợp đồng
                            });
                    });
                });
            } else {
                // Không có booking_deposit_id, chỉ hiển thị phòng available và không có booking deposit đã thanh toán
                $unitsQuery->where('status', 'available')
                    ->whereDoesntHave('bookingDeposits', function($q) {
                        $q->where('payment_status', 'paid')
                          ->whereNull('deleted_at')
                          ->whereDoesntHave('lease'); // Chưa có hợp đồng
                    });
            }
            
            $units = $unitsQuery->orderBy('code')
                ->get()
                ->map(function ($unit) use ($bookingDepositId) {
                    // Kiểm tra xem phòng có thuộc booking deposit đang chọn không
                    $belongsToSelectedBooking = false;
                    if ($bookingDepositId) {
                        $belongsToSelectedBooking = $unit->bookingDeposits()
                            ->where('id', $bookingDepositId)
                            ->where('payment_status', 'paid')
                            ->whereNull('deleted_at')
                            ->exists();
                    }
                    
                    return [
                        'id' => $unit->id,
                        'code' => $unit->code,
                        'floor' => $unit->floor,
                        'area_m2' => $unit->area_m2,
                        'base_rent' => $unit->base_rent,
                        'deposit_amount' => $unit->deposit_amount,
                        'status' => $unit->status,
                        'has_active_lease' => false, // Đã filter ở query rồi
                        'belongs_to_selected_booking' => $belongsToSelectedBooking
                    ];
                })
                ->values();

            return response()->json($units);
        } catch (\Exception $e) {
            Log::error('Error in getUnits: ' . $e->getMessage(), [
                'property_id' => $propertyId,
                'booking_deposit_id' => $request->get('booking_deposit_id'),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Có lỗi xảy ra khi tải dữ liệu phòng: ' . $e->getMessage()], 500);
        }
    }

    // API method to get property details with assigned users
    public function getPropertyDetails($propertyId)
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $organizationId = $this->getCurrentOrganizationId();
            
            // Verify property belongs to organization
            $property = \App\Models\Property::with(['assignedUsers' => function($q) {
                    $q->with('userProfile');
                }])
                ->where('id', $propertyId)
                ->where('organization_id', $organizationId)
                ->whereNull('deleted_at')
                ->first();
            
            if (!$property) {
                return response()->json(['error' => 'Bất động sản không tồn tại hoặc bạn không có quyền truy cập'], 404);
            }
            
            // Format assigned users with role information
            $assignedUsers = $property->assignedUsers->map(function($assignedUser) {
                return [
                    'id' => $assignedUser->id,
                    'full_name' => $assignedUser->userProfile->full_name ?? $assignedUser->full_name ?? 'N/A',
                    'email' => $assignedUser->email,
                    'pivot' => [
                        'role_key' => $assignedUser->pivot->role_key ?? null,
                        'assigned_at' => $assignedUser->pivot->assigned_at ?? null,
                    ]
                ];
            });

            return response()->json([
                'success' => true,
                'property' => [
                    'id' => $property->id,
                    'name' => $property->name,
                    'assigned_users' => $assignedUsers
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getPropertyDetails: ' . $e->getMessage(), [
                'property_id' => $propertyId,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Có lỗi xảy ra khi tải thông tin bất động sản: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Cập nhật trạng thái phòng dựa trên trạng thái hợp đồng
     */
    private function updateUnitStatusBasedOnLease($lease, $leaseStatus)
    {
        $unit = $lease->unit;
        if (!$unit) {
            return;
        }

        switch ($leaseStatus) {
            case 'active':
                // Khi hợp đồng active, phòng chuyển thành occupied
                $unit->update(['status' => 'occupied']);
                break;
                
            case 'terminated':
            case 'expired':
                // Khi hợp đồng kết thúc, kiểm tra xem có hợp đồng active khác không
                $hasOtherActiveLease = Lease::where('unit_id', $unit->id)
                    ->where('status', 'active')
                    ->where('id', '!=', $lease->id)
                    ->whereNull('deleted_at')
                    ->exists();
                
                if (!$hasOtherActiveLease) {
                    // Không có hợp đồng active nào khác, phòng chuyển về available
                    $unit->update(['status' => 'available']);
                }
                break;
                
            case 'draft':
                // Hợp đồng draft không ảnh hưởng đến trạng thái phòng
                // Chỉ cập nhật nếu phòng hiện tại đang occupied và không có hợp đồng active nào khác
                if ($unit->status === 'occupied') {
                    $hasOtherActiveLease = Lease::where('unit_id', $unit->id)
                        ->where('status', 'active')
                        ->where('id', '!=', $lease->id)
                        ->whereNull('deleted_at')
                        ->exists();
                    
                    if (!$hasOtherActiveLease) {
                        $unit->update(['status' => 'available']);
                    }
                }
                break;
        }
    }

    /**
     * Cập nhật trạng thái phòng sau khi xóa hợp đồng
     */
    private function updateUnitStatusAfterLeaseDeletion($deletedLease)
    {
        $unit = $deletedLease->unit;
        if (!$unit) {
            return;
        }

        // Kiểm tra xem còn hợp đồng active nào khác không
        $hasOtherActiveLease = Lease::where('unit_id', $unit->id)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->exists();

        if (!$hasOtherActiveLease) {
            // Không có hợp đồng active nào khác, phòng chuyển về available
            $unit->update(['status' => 'available']);
        }
    }

    /**
     * API method to get property payment cycle settings
     */
    public function getPropertyPaymentCycle($propertyId)
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            
            // Get user's organization
            $organization = $user->organizations()->first();
            
            if (!$organization) {
                return response()->json(['error' => 'Bạn chưa được gán vào tổ chức nào.'], 403);
            }

            // Get property with payment cycle and organization relationships
            $property = \App\Models\Property::with(['paymentCycle', 'organization.defaultPaymentCycle'])
                ->where('organization_id', $organization->id)
                ->where('id', $propertyId)
                ->firstOrFail();

            $effectiveCycle = $property->getEffectivePaymentCycle();
            
            // Nếu không có effective cycle, lấy newest
            $newestCycle = null;
            if (!$effectiveCycle) {
                $newestCycle = PaymentCycle::where('organization_id', $organization->id)
                    ->whereNull('deleted_at')
                    ->orderBy('created_at', 'desc')
                    ->first();
            }

            return response()->json([
                'success' => true,
                'property' => [
                    'id' => $property->id,
                    'name' => $property->name,
                    'payment_cycle' => $property->paymentCycle ? [
                        'id' => $property->paymentCycle->id,
                        'cycle_type' => $property->paymentCycle->cycle_type,
                        'cycle_type_name' => $property->paymentCycle->cycle_type_name,
                        'billing_day' => $property->paymentCycle->billing_day,
                        'custom_months' => $property->paymentCycle->custom_months,
                        'notes' => $property->paymentCycle->notes,
                        'name' => $property->paymentCycle->name,
                    ] : null,
                    'effective_payment_cycle' => $effectiveCycle ? [
                        'id' => $effectiveCycle->id,
                        'cycle_type' => $effectiveCycle->cycle_type,
                        'cycle_type_name' => $effectiveCycle->cycle_type_name,
                        'billing_day' => $effectiveCycle->billing_day,
                        'custom_months' => $effectiveCycle->custom_months,
                        'notes' => $effectiveCycle->notes,
                        'name' => $effectiveCycle->name,
                    ] : ($newestCycle ? [
                        'id' => $newestCycle->id,
                        'cycle_type' => $newestCycle->cycle_type,
                        'cycle_type_name' => $newestCycle->cycle_type_name,
                        'billing_day' => $newestCycle->billing_day,
                        'custom_months' => $newestCycle->custom_months,
                        'notes' => $newestCycle->notes,
                        'name' => $newestCycle->name,
                    ] : null),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting property payment cycle: ' . $e->getMessage());
            return response()->json(['error' => 'Có lỗi xảy ra khi tải cài đặt chu kỳ thanh toán: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API method to get property lease service set
     */
    public function getPropertyLeaseServiceSet($propertyId)
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            
            // Get user's organization
            $organization = $user->organizations()->first();
            
            if (!$organization) {
                return response()->json(['error' => 'Bạn chưa được gán vào tổ chức nào.'], 403);
            }

            // Get property with lease service set and organization relationships
            $property = \App\Models\Property::with(['leaseServiceSet.items.service', 'organization.defaultLeaseServiceSet.items.service'])
                ->where('organization_id', $organization->id)
                ->where('id', $propertyId)
                ->firstOrFail();

            $effectiveSet = $property->getEffectiveLeaseServiceSet();
            
            // Nếu không có effective set, lấy newest
            $newestSet = null;
            if (!$effectiveSet) {
                $newestSet = LeaseServiceSet::with('items.service')
                    ->where('organization_id', $organization->id)
                    ->whereNull('deleted_at')
                    ->orderBy('created_at', 'desc')
                    ->first();
            }

            return response()->json([
                'success' => true,
                'property' => [
                    'id' => $property->id,
                    'name' => $property->name,
                    'lease_service_set' => $property->leaseServiceSet ? [
                        'id' => $property->leaseServiceSet->id,
                        'name' => $property->leaseServiceSet->name,
                        'description' => $property->leaseServiceSet->description,
                        'items' => $property->leaseServiceSet->items->map(function($item) {
                            return [
                                'id' => $item->id,
                                'service_id' => $item->service_id,
                                'price' => $item->price,
                                'service' => $item->service ? [
                                    'id' => $item->service->id,
                                    'name' => $item->service->name,
                                    'key_code' => $item->service->key_code,
                                ] : null,
                            ];
                        }),
                    ] : null,
                    'effective_lease_service_set' => $effectiveSet ? [
                        'id' => $effectiveSet->id,
                        'name' => $effectiveSet->name,
                        'description' => $effectiveSet->description,
                        'items' => $effectiveSet->items->map(function($item) {
                            return [
                                'id' => $item->id,
                                'service_id' => $item->service_id,
                                'price' => $item->price,
                                'service' => $item->service ? [
                                    'id' => $item->service->id,
                                    'name' => $item->service->name,
                                    'key_code' => $item->service->key_code,
                                ] : null,
                            ];
                        }),
                    ] : ($newestSet ? [
                        'id' => $newestSet->id,
                        'name' => $newestSet->name,
                        'description' => $newestSet->description,
                        'items' => $newestSet->items->map(function($item) {
                            return [
                                'id' => $item->id,
                                'service_id' => $item->service_id,
                                'price' => $item->price,
                                'service' => $item->service ? [
                                    'id' => $item->service->id,
                                    'name' => $item->service->name,
                                    'key_code' => $item->service->key_code,
                                ] : null,
                            ];
                        }),
                    ] : null),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting property lease service set: ' . $e->getMessage());
            return response()->json(['error' => 'Có lỗi xảy ra khi tải bộ dịch vụ: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Upload document for lease
     */
    public function uploadDocument(Request $request, $id)
    {
        $this->requireCapability('contract.lease.update', 'Bạn không có quyền thêm tài liệu.');
        
        try {
            $userOrganizationId = $this->getCurrentOrganizationId();
            $lease = Lease::where('organization_id', $userOrganizationId)->findOrFail($id);

            $validated = $request->validate([
                'document' => 'required|file|max:20480|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif',
                'description' => 'nullable|string|max:500',
            ]);

            $file = $request->file('document');
            
            // Sử dụng ImageService để upload
            $uploadedFile = $this->imageService->uploadFile($file, 'leases', 'lease-documents');

            $document = \App\Models\Document::create([
                'owner_type' => Lease::class,
                'owner_id' => $lease->id,
                'file_url' => $uploadedFile['original'],
                'file_name' => $uploadedFile['original_name'],
                'mime_type' => $uploadedFile['mime_type'],
                'file_size' => $uploadedFile['size'],
                'uploaded_by' => Auth::id(),
                'created_at' => now(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tải lên tài liệu thành công!',
                    'document' => $document
                ]);
            }

            return back()->with('success', 'Tải lên tài liệu thành công!');
        } catch (\Exception $e) {
            Log::error('Error uploading document: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi tải lên tài liệu: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Có lỗi xảy ra khi tải lên tài liệu: ' . $e->getMessage());
        }
    }

    /**
     * Delete document from lease
     */
    public function deleteDocument(Request $request, $leaseId, $documentId)
    {
        $this->requireCapability('contract.lease.update', 'Bạn không có quyền xóa tài liệu.');
        
        try {
            $userOrganizationId = $this->getCurrentOrganizationId();
            $lease = Lease::where('organization_id', $userOrganizationId)->findOrFail($leaseId);
            
            $document = \App\Models\Document::where('owner_type', Lease::class)
                ->where('owner_id', $lease->id)
                ->findOrFail($documentId);

            // Delete file from storage
            // Delete file from storage (lưu trực tiếp vào public/storage)
            $fullPath = public_path('storage/' . $document->file_url);
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }

            // Delete document record
            $document->delete();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Xóa tài liệu thành công!'
                ]);
            }

            return back()->with('success', 'Xóa tài liệu thành công!');
        } catch (\Exception $e) {
            Log::error('Error deleting document: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi xóa tài liệu: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Có lỗi xảy ra khi xóa tài liệu: ' . $e->getMessage());
        }
    }

    /**
     * Renew lease - create a new lease from existing one
     */
    public function renew(Request $request, $id)
    {
        $this->requireCapability('contract.lease.create', 'Bạn không có quyền gia hạn hợp đồng.');
        
        try {
            $userOrganizationId = $this->getCurrentOrganizationId();
            $oldLease = Lease::where('organization_id', $userOrganizationId)->findOrFail($id);

            if ($oldLease->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Chỉ có thể gia hạn hợp đồng đang hoạt động.'
                ], 422);
            }

            $validated = $request->validate([
                'new_start_date' => 'required|date|after:' . $oldLease->end_date->format('Y-m-d'),
                'new_end_date' => 'required|date|after:new_start_date',
                'new_rent_amount' => 'nullable|numeric|min:0',
                'renewal_notes' => 'nullable|string|max:1000',
            ]);

            DB::beginTransaction();

            // Expire the old lease
            $oldLease->update([
                'status' => 'expired',
                'end_date' => \Carbon\Carbon::parse($validated['new_start_date'])->subDay()->format('Y-m-d'),
            ]);

            // Create new lease with same information (except dates and optional rent)
            $newLease = Lease::create([
                'organization_id' => $oldLease->organization_id,
                'unit_id' => $oldLease->unit_id,
                'tenant_id' => $oldLease->tenant_id,
                'agent_id' => $oldLease->agent_id,
                'booking_id' => $oldLease->booking_id, // Copy booking_id nếu có
                'start_date' => $validated['new_start_date'],
                'end_date' => $validated['new_end_date'],
                'rent_amount' => $validated['new_rent_amount'] ?? $oldLease->rent_amount,
                'deposit_amount' => $oldLease->deposit_amount,
                'status' => 'active',
                'contract_no' => $this->generateContractNumber(),
                'signed_at' => now(),
                'payment_cycle_id' => $oldLease->payment_cycle_id,
                'lease_services_id' => $oldLease->lease_services_id,
            ]);

            // Services are now managed through lease_service_sets
            // lease_services_id is already copied above

            // Update unit status
            $this->updateUnitStatusBasedOnLease($newLease, 'active');

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Gia hạn hợp đồng thành công!',
                    'redirect' => route('staff.leases.show', $newLease->id)
                ]);
            }

            return redirect()->route('staff.leases.show', $newLease->id)
                ->with('success', 'Gia hạn hợp đồng thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error renewing lease: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi gia hạn hợp đồng: ' . $e->getMessage()
                ], 500);
            }

            return back()->withInput()->with('error', 'Có lỗi xảy ra khi gia hạn hợp đồng: ' . $e->getMessage());
        }
    }

    
    /**
     * Handle payment cycle creation or retrieval
     */
    private function handlePaymentCycle($validated, $organizationId)
    {
        // If payment_cycle_id is provided, use it
        if (!empty($validated['payment_cycle_id'])) {
            $paymentCycle = PaymentCycle::where('id', $validated['payment_cycle_id'])
                ->where(function($query) use ($organizationId) {
                    $query->where('organization_id', $organizationId)
                          ->orWhereNull('organization_id');
                })
                ->first();
            
            if ($paymentCycle) {
                return $paymentCycle->id;
            }
        }

        // Try to get effective payment cycle from property
        if (isset($validated['unit_id'])) {
            $unit = Unit::with('property.paymentCycle')->find($validated['unit_id']);
            if ($unit && $unit->property) {
                $effectiveCycle = $unit->property->getEffectivePaymentCycle();
                if ($effectiveCycle) {
                    return $effectiveCycle->id;
                }
            }
        }

        return null;
    }

    /**
     * Handle lease service set creation or retrieval
     * Similar to handlePaymentCycle: find existing or create new
     */
    private function handleLeaseServiceSet($validated, $organizationId)
    {
        // If lease_services_id is provided, use it
        if (!empty($validated['lease_services_id'])) {
            $leaseServiceSet = LeaseServiceSet::where('id', $validated['lease_services_id'])
                ->where(function($query) use ($organizationId) {
                    $query->where('organization_id', $organizationId)
                          ->orWhereNull('organization_id');
                })
                ->first();
            
            if ($leaseServiceSet) {
                return $leaseServiceSet->id;
            }
        }

        // Try to get effective lease service set from property
        if (isset($validated['unit_id'])) {
            $unit = Unit::with('property.leaseServiceSet')->find($validated['unit_id']);
            if ($unit && $unit->property) {
                $effectiveSet = $unit->property->getEffectiveLeaseServiceSet();
                if ($effectiveSet) {
                    return $effectiveSet->id;
                }
            }
        }

        return null;
    }

    /**
     * Get payment cycle in months
     */
    private function getPaymentCycleMonths($lease)
    {
        $cycle = $lease->getEffectivePaymentCycle();
        
        if (!$cycle) {
            return 1; // Default to monthly
        }

        switch ($cycle->cycle_type) {
            case 'monthly':
                return 1;
            case 'quarterly':
                return 3;
            case 'yearly':
                return 12;
            case 'custom':
                return $cycle->custom_months ?? 1;
            default:
                return 1; // Default to monthly
        }
    }
    
    /**
     * Get payment cycle text in Vietnamese
     */
    private function getPaymentCycleText($cycle)
    {
        if (!$cycle) {
            return 'Chưa cài đặt';
        }

        if (is_string($cycle)) {
            $texts = [
                'monthly' => 'Hàng tháng',
                'quarterly' => 'Hàng quý (3 tháng)',
                'yearly' => 'Hàng năm (12 tháng)',
                'custom' => 'Tùy chỉnh'
            ];
            return $texts[$cycle] ?? $cycle;
        }

        // If it's a PaymentCycle model
        return $cycle->cycle_type_name ?? $cycle->name ?? 'Chưa cài đặt';
    }

    /**
     * Create first invoice with deposit deduction from booking deposit
     * 
     * @param \App\Models\Lease $lease
     * @return \App\Models\Invoice|null
     */
    private function createFirstInvoiceWithDepositDeduction(\App\Models\Lease $lease)
    {
        try {
            // Lấy booking deposit từ lease
            $bookingDeposit = $lease->bookingDeposit;
            
            if (!$bookingDeposit) {
                Log::warning('No booking deposit found for lease', [
                    'lease_id' => $lease->id,
                    'booking_id' => $lease->booking_id
                ]);
                return null;
            }
            
            // Kiểm tra booking deposit đã thanh toán
            if ($bookingDeposit->payment_status !== 'paid') {
                Log::warning('Booking deposit not paid, skipping invoice creation', [
                    'lease_id' => $lease->id,
                    'booking_deposit_id' => $bookingDeposit->id,
                    'payment_status' => $bookingDeposit->payment_status
                ]);
                return null;
            }
            
            Log::info('Creating first invoice with deposit deduction', [
                'lease_id' => $lease->id,
                'booking_id' => $lease->booking_id,
                'booking_deposit_id' => $bookingDeposit->id,
                'deposit_amount' => $bookingDeposit->amount
            ]);

            // Check if invoice already exists (kiểm tra bất kỳ hóa đơn nào có booking_deposit_id hoặc có item deposit deduction)
            $existingInvoice = \App\Models\Invoice::where('lease_id', $lease->id)
                ->where('status', '!=', 'cancelled')
                ->where(function($query) use ($bookingDeposit) {
                    $query->where('booking_deposit_id', $bookingDeposit->id)
                          ->orWhereHas('items', function($q) {
                              $q->where('item_type', 'deposit')
                                ->where('amount', '<', 0);
                          });
                })
                ->first();

            if ($existingInvoice) {
                Log::info('Invoice with deposit deduction already exists', [
                    'lease_id' => $lease->id,
                    'booking_id' => $lease->booking_id,
                    'booking_deposit_id' => $bookingDeposit->id,
                    'existing_invoice_id' => $existingInvoice->id
                ]);
                return $existingInvoice;
            }

            // Calculate dates
            $issueDate = $lease->start_date;
            // Sử dụng invoice_payment_days từ organization
            $dueDate = $this->calculateDueDateForInvoice($lease, $issueDate);
            
            // Calculate totals - chỉ tính tiền cọc và trừ tiền cọc từ booking
            $depositAmount = (float)$lease->deposit_amount ?? 0; // Tiền cọc (không nhân số tháng)
            $depositDeduction = -abs((float)$bookingDeposit->amount); // Negative amount - trừ tiền cọc từ booking
            
            // Kiểm tra invoice_timing từ default payment cycle
            $organization = $lease->organization ?? \App\Models\Organization::find($lease->organization_id);
            $defaultPaymentCycle = $organization ? $organization->defaultPaymentCycle : null;
            $invoiceTiming = $defaultPaymentCycle ? ($defaultPaymentCycle->invoice_timing ?? 'end_of_cycle') : 'end_of_cycle';
            
            $rentTotal = 0;
            $servicesTotal = 0;
            $serviceItems = [];
            $cycleInfo = null;
            
            // Nếu invoice_timing = 'start_of_cycle', tính tiền thuê chu kỳ đầu (KHÔNG tính dịch vụ)
            if ($invoiceTiming === 'start_of_cycle') {
                $cycleInfo = $this->calculatePaymentCycleForInvoice($lease);
                $rentTotal = $cycleInfo['rent_total'] ?? 0;
                // KHÔNG tính dịch vụ trong first invoice
                $servicesTotal = 0;
                $serviceItems = [];
            }
            
            $subtotal = $depositAmount + $depositDeduction + $rentTotal;
            $totalAmount = max(0, $subtotal); // Ensure total is not negative

            // Generate invoice number
            $invoiceNumber = \App\Models\Invoice::generateInvoiceNumber($lease->organization_id);

            // Create invoice
            $invoice = \App\Models\Invoice::create([
                'organization_id' => $lease->organization_id,
                'is_auto_created' => true,
                'lease_id' => $lease->id,
                'booking_deposit_id' => $bookingDeposit->id,
                'invoice_no' => $invoiceNumber,
                'invoice_type' => \App\Models\Invoice::TYPE_FIRST_INVOICE,
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'status' => 'draft',
                'subtotal' => $subtotal,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => $totalAmount,
                'currency' => 'VND',
                'note' => 'Hóa đơn đầu tiên' . ($invoiceTiming === 'start_of_cycle' ? ' (bao gồm tiền thuê chu kỳ đầu)' : '') . ' - Đã trừ tiền cọc đặt chỗ (' . number_format(abs($depositDeduction), 0, ',', '.') . 'đ)',
                'created_by' => Auth::id(),
            ]);

            // Add deposit item (tiền cọc - không nhân số tháng)
            if ($depositAmount > 0) {
                $invoice->items()->create([
                    'item_type' => 'deposit',
                    'description' => 'Tiền cọc - ' . ($lease->unit->property->name ?? '') . ' - ' . ($lease->unit->code ?? ''),
                    'quantity' => 1,
                    'unit_price' => $depositAmount,
                    'amount' => $depositAmount,
                    'meta_json' => [
                        'lease_id' => $lease->id,
                        'type' => 'deposit',
                    ],
                ]);
            }

            // Add deposit deduction item (negative amount - trừ tiền cọc từ booking)
            $invoice->items()->create([
                'item_type' => 'deposit',
                'description' => 'Trừ tiền cọc đặt chỗ đã thanh toán (Booking Deposit #' . $bookingDeposit->reference_number . ')',
                'quantity' => 1,
                'unit_price' => $depositDeduction,
                'amount' => $depositDeduction,
                'meta_json' => [
                    'lease_id' => $lease->id,
                    'booking_deposit_id' => $bookingDeposit->id,
                    'type' => 'deposit_deduction',
                ],
            ]);
            
            // Add rent item if invoice_timing = 'start_of_cycle'
            if ($invoiceTiming === 'start_of_cycle' && $rentTotal > 0 && $cycleInfo) {
                $invoice->items()->create([
                    'item_type' => 'rent',
                    'description' => $cycleInfo['rent_description'] ?? 'Tiền thuê chu kỳ đầu',
                    'quantity' => $cycleInfo['rent_quantity'] ?? 1,
                    'unit_price' => $cycleInfo['rent_unit_price'] ?? $lease->rent_amount,
                    'amount' => $rentTotal,
                    'meta_json' => [
                        'lease_id' => $lease->id,
                        'type' => 'first_cycle_rent',
                    ],
                ]);
            }
            
            // Add service items if invoice_timing = 'start_of_cycle'
            if ($invoiceTiming === 'start_of_cycle' && !empty($serviceItems)) {
                foreach ($serviceItems as $serviceItem) {
                    $invoice->items()->create([
                        'item_type' => 'service',
                        'description' => $serviceItem['description'] ?? 'Dịch vụ chu kỳ đầu',
                        'quantity' => $serviceItem['quantity'] ?? 1,
                        'unit_price' => $serviceItem['unit_price'] ?? 0,
                        'amount' => $serviceItem['amount'] ?? 0,
                        'meta_json' => [
                            'lease_id' => $lease->id,
                            'service_id' => $serviceItem['service_id'] ?? null,
                            'type' => 'first_cycle_service',
                        ],
                    ]);
                }
            }

            // Recalculate totals from items
            $recalculatedSubtotal = $invoice->items()->sum('amount');
            $invoice->update([
                'subtotal' => $recalculatedSubtotal,
                'total_amount' => max(0, $recalculatedSubtotal),
            ]);

            Log::info('First invoice with deposit deduction created successfully', [
                'lease_id' => $lease->id,
                'invoice_id' => $invoice->id,
                'invoice_no' => $invoice->invoice_no,
                'subtotal' => $recalculatedSubtotal,
                'total_amount' => $invoice->total_amount,
                'deposit_deduction' => $depositDeduction
            ]);

            return $invoice;
        } catch (\Exception $e) {
            Log::error('Error creating first invoice with deposit deduction: ' . $e->getMessage(), [
                'lease_id' => $lease->id,
                'booking_deposit_id' => $bookingDeposit->id,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Calculate payment cycle info for invoice creation
     * 
     * @param \App\Models\Lease $lease
     * @return array
     */
    private function calculatePaymentCycleForInvoice(\App\Models\Lease $lease)
    {
        $paymentCycle = $lease->getEffectivePaymentCycle();
        // Sử dụng accessor cycle_months để đảm bảo logic nhất quán
        $cycleMonths = $paymentCycle ? (int)$paymentCycle->cycle_months : 1;
        
        // Fallback nếu cycle_months vẫn null hoặc 0
        if (empty($cycleMonths)) {
            $cycleMonths = 1; // Default 1 month
        }

        // Calculate rent for first cycle
        $rentAmount = (float)$lease->rent_amount;
        $rentTotal = $rentAmount * $cycleMonths;
        $rentQuantity = $cycleMonths;
        $rentUnitPrice = $rentAmount;
        
        $startDate = \Carbon\Carbon::parse($lease->start_date);
        $endDate = $startDate->copy()->addMonths($cycleMonths)->subDay();
        $period = $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y');
        
        $rentDescription = "Tiền thuê " . ($cycleMonths > 1 ? $cycleMonths . " tháng" : "tháng đầu") . " (" . $period . ")";

        // Get services from lease service set
        $serviceItems = [];
        $servicesTotal = 0;
        
        $effectiveServiceSet = $lease->getEffectiveLeaseServiceSet();
        if ($effectiveServiceSet && $effectiveServiceSet->items) {
            foreach ($effectiveServiceSet->items as $item) {
                $serviceAmount = (float)$item->price * $cycleMonths;
                $servicesTotal += $serviceAmount;
                
                $serviceItems[] = [
                    'service_id' => $item->service_id,
                    'description' => ($item->service->name ?? 'Dịch vụ') . " " . ($cycleMonths > 1 ? $cycleMonths . " tháng" : "tháng đầu"),
                    'quantity' => $cycleMonths,
                    'unit_price' => (float)$item->price,
                    'amount' => $serviceAmount,
                ];
            }
        }

        return [
            'rent_total' => $rentTotal,
            'rent_quantity' => $rentQuantity,
            'rent_unit_price' => $rentUnitPrice,
            'rent_description' => $rentDescription,
            'services_total' => $servicesTotal,
            'service_items' => $serviceItems,
            'period' => $period,
            'cycle_months' => $cycleMonths,
        ];
    }

    /**
     * Calculate due date for invoice
     * 
     * @param \App\Models\Lease $lease
     * @param string $issueDate
     * @return string
     */
    /**
     * Calculate due date for invoice
     * 
     * @param \App\Models\Lease $lease
     * @param string $issueDate
     * @param bool $isBookingDeposit - Nếu true, sử dụng logic cũ (cho booking deposits)
     * @return string
     */
    private function calculateDueDateForInvoice(\App\Models\Lease $lease, $issueDate)
    {
        // Tất cả hóa đơn đều sử dụng invoice_payment_days từ default payment cycle
        $organization = $lease->organization ?? \App\Models\Organization::find($lease->organization_id);
        $defaultPaymentCycle = $organization ? $organization->defaultPaymentCycle : null;
        $invoicePaymentDays = $defaultPaymentCycle?->invoice_payment_days ?? 30; // Mặc định 30 ngày
        
        $issue = \Carbon\Carbon::parse($issueDate);
        $dueDate = $issue->copy()->addDays($invoicePaymentDays);
        
        return $dueDate->format('Y-m-d');
    }

    /**
     * Update Lease Service Set
     * Services are now managed through lease_service_sets
     * 
     * @param \App\Models\Lease $lease
     * @param int|null $leaseServicesId
     * @return void
     */
    private function updateLeaseServiceSet(\App\Models\Lease $lease, ?int $leaseServicesId): void
    {
        // Update lease_services_id to point to the selected set
        $lease->update([
            'lease_services_id' => $leaseServicesId,
        ]);
    }

    /**
     * Update lease status
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStatus(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('contract.lease.update', 'Bạn không có quyền thay đổi trạng thái hợp đồng.');
        
        $userOrganizationId = $this->getCurrentOrganizationId();
        
        if (!$userOrganizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        $validated = $request->validate([
            'status' => 'required|in:draft,active,terminated,expired',
        ]);
        
        $lease = Lease::where('organization_id', $userOrganizationId)
            ->findOrFail($id);
        
        $oldStatus = $lease->status;
        $newStatus = $validated['status'];
        
        // Validate status transition
        $allowedTransitions = [
            'draft' => ['active'],
            'active' => ['draft', 'terminated', 'expired'],
            'terminated' => ['active', 'draft'],
            'expired' => ['active', 'draft'],
        ];
        
        if (!in_array($newStatus, $allowedTransitions[$oldStatus] ?? [])) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => "Không thể chuyển từ trạng thái '{$oldStatus}' sang '{$newStatus}'."
                ], 400);
            }
            return back()->with('error', "Không thể chuyển từ trạng thái '{$oldStatus}' sang '{$newStatus}'.");
        }
        
        try {
            DB::beginTransaction();
            
            // Update lease status
            $lease->update([
                'status' => $newStatus
            ]);
            
            // Cập nhật trạng thái phòng
            $this->updateUnitStatusBasedOnLease($lease, $newStatus);
            
            DB::commit();
            
            $statusLabels = [
                'draft' => 'Nháp',
                'active' => 'Hoạt động',
                'terminated' => 'Chấm dứt',
                'expired' => 'Hết hạn',
            ];
            
            $message = "Trạng thái hợp đồng đã được chuyển từ '{$statusLabels[$oldStatus]}' sang '{$statusLabels[$newStatus]}'.";
            
            // Return JSON response for AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'status' => $newStatus,
                    'statusLabel' => $statusLabels[$newStatus] ?? $newStatus
                ]);
            }
            
            return redirect()->route('staff.leases.show', $lease->id)
                ->with('success', $message);
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating lease status: ' . $e->getMessage(), [
                'lease_id' => $lease->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'error' => $e->getTraceAsString()
            ]);
            
            $errorMessage = 'Có lỗi xảy ra khi thay đổi trạng thái hợp đồng: ' . $e->getMessage();
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }
            
            return back()->with('error', $errorMessage);
        }
    }

    /**
     * Create first invoice for lease (manual trigger from show page)
     * 
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function createInvoice(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('billing.invoice.create', 'Bạn không có quyền tạo hóa đơn.');
        
        $userOrganizationId = $this->getCurrentOrganizationId();
        
        if (!$userOrganizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        $validated = $request->validate([
            'invoice_type' => 'required|in:first,cycle,normal',
        ]);
        
        $lease = Lease::where('organization_id', $userOrganizationId)
            ->with(['bookingDeposit', 'organization'])
            ->findOrFail($id);
        
        // Chỉ cho phép tạo hóa đơn nếu hợp đồng đang active
        if ($lease->status !== 'active') {
            return back()->with('error', 'Chỉ có thể tạo hóa đơn cho hợp đồng đang hoạt động.');
        }
        
        $invoiceType = $validated['invoice_type'];
        
        // Kiểm tra xem đã có hóa đơn đầu tiên chưa
        $hasFirstInvoice = \App\Models\Invoice::where('lease_id', $lease->id)
            ->where('status', '!=', 'cancelled')
            ->where(function($query) {
                $query->whereHas('items', function($q) {
                    $q->where('item_type', 'deposit')
                      ->orWhere('item_type', 'rent')
                      ->orWhere('description', 'like', '%chu kỳ đầu%')
                      ->orWhere('description', 'like', '%tháng đầu%');
                });
            })
            ->exists();
        
        // Nếu chọn tạo hóa đơn đầu tiên nhưng đã có rồi
        if ($invoiceType === 'first' && $hasFirstInvoice) {
            return back()->with('error', 'Hóa đơn đầu tiên đã được tạo cho hợp đồng này.');
        }
        
        // Nếu chọn tạo hóa đơn chu kỳ nhưng chưa có hóa đơn đầu tiên
        if ($invoiceType === 'cycle' && !$hasFirstInvoice) {
            return back()->with('error', 'Vui lòng tạo hóa đơn đầu tiên trước khi tạo hóa đơn chu kỳ.');
        }
        
        // Nếu chọn tạo hóa đơn đầu tiên, redirect đến trang create với dữ liệu pre-filled
        if ($invoiceType === 'first') {
            // Tính toán dữ liệu hóa đơn đầu tiên
            $prefillData = $this->calculateFirstInvoiceData($lease);
            
            // Lưu vào session để truyền sang trang create
            session(['first_invoice_prefill' => $prefillData]);
            
            return redirect()->route('staff.invoices.create', ['lease_id' => $lease->id, 'first_invoice' => true])
                ->with('info', 'Thông tin hóa đơn đầu tiên đã được điền sẵn. Vui lòng kiểm tra và tạo hóa đơn.');
        } elseif ($invoiceType === 'cycle') {
            // Tính toán chu kỳ cần tạo hóa đơn (chu kỳ trước chưa được tạo)
            $nextUnpaidCycle = $this->getNextUnpaidCycle($lease);
            
            if (!$nextUnpaidCycle) {
                return back()->with('error', 'Không có chu kỳ nào cần tạo hóa đơn. Tất cả chu kỳ đã được thanh toán hoặc chưa đến hạn.');
            }
            
            // Tính toán dữ liệu hóa đơn chu kỳ
            $prefillData = $this->calculateCycleInvoiceData($lease, $nextUnpaidCycle);
            
            // Lưu vào session để truyền sang trang create
            session(['cycle_invoice_prefill' => $prefillData]);
            
            return redirect()->route('staff.invoices.create', ['lease_id' => $lease->id, 'cycle_invoice' => true])
                ->with('info', "Tạo hóa đơn cho chu kỳ {$nextUnpaidCycle['cycle_number']}: từ {$nextUnpaidCycle['cycle_start']->format('d/m/Y')} đến {$nextUnpaidCycle['cycle_end']->format('d/m/Y')}");
        } else {
            // Tạo hóa đơn thông thường, chỉ fill lease_id
            return redirect()->route('staff.invoices.create', ['lease_id' => $lease->id]);
        }
    }
    
    /**
     * Create first invoice without booking deposit
     * 
     * @param \App\Models\Lease $lease
     * @return \App\Models\Invoice|null
     */
    private function createFirstInvoiceWithoutBooking(\App\Models\Lease $lease)
    {
        try {
            // Kiểm tra invoice_timing từ default payment cycle
            $organization = $lease->organization ?? \App\Models\Organization::find($lease->organization_id);
            $defaultPaymentCycle = $organization ? $organization->defaultPaymentCycle : null;
            $invoiceTiming = $defaultPaymentCycle ? ($defaultPaymentCycle->invoice_timing ?? 'end_of_cycle') : 'end_of_cycle';
            
            // Nếu invoice_timing = 'end_of_cycle' và không có tiền cọc, không cần tạo hóa đơn
            if ($invoiceTiming === 'end_of_cycle' && (!$lease->deposit_amount || $lease->deposit_amount <= 0)) {
                Log::info('Skipping invoice creation - invoice_timing is end_of_cycle and no deposit amount', [
                    'lease_id' => $lease->id,
                    'invoice_timing' => $invoiceTiming,
                    'deposit_amount' => $lease->deposit_amount
                ]);
                return null;
            }
            
            // Calculate dates
            $issueDate = $lease->start_date;
            // Sử dụng invoice_payment_days từ organization
            $dueDate = $this->calculateDueDateForInvoice($lease, $issueDate);
            
            // Calculate totals
            $depositAmount = (float)$lease->deposit_amount ?? 0;
            
            $rentTotal = 0;
            $servicesTotal = 0;
            $cycleInfo = null;
            
            // Chỉ tính tiền thuê nếu invoice_timing = 'start_of_cycle' (KHÔNG tính dịch vụ)
            if ($invoiceTiming === 'start_of_cycle') {
                $cycleInfo = $this->calculatePaymentCycleForInvoice($lease);
                $rentTotal = $cycleInfo['rent_total'] ?? 0;
                // KHÔNG tính dịch vụ trong first invoice
                $servicesTotal = 0;
            }
            
            $totalAmount = $depositAmount + $rentTotal;
            
            // Generate invoice number
            $invoiceNumber = \App\Models\Invoice::generateInvoiceNumber($lease->organization_id);
            
            // Get unit and property information
            $unit = $lease->unit;
            $property = $unit->property;
            
            // Create invoice
            $invoice = \App\Models\Invoice::create([
                'organization_id' => $lease->organization_id,
                'is_auto_created' => false, // Tạo thủ công từ nút
                'lease_id' => $lease->id,
                'invoice_no' => $invoiceNumber,
                'invoice_type' => \App\Models\Invoice::TYPE_FIRST_INVOICE,
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'status' => 'draft',
                'subtotal' => $totalAmount,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => $totalAmount,
                'currency' => 'VND',
                'note' => "Hóa đơn đầu tiên" . ($invoiceTiming === 'start_of_cycle' ? ' (bao gồm tiền cọc và tiền thuê chu kỳ đầu)' : ' (chỉ tiền cọc)') . " cho {$property->name} - {$unit->code}",
                'created_by' => Auth::id(),
            ]);
            
            // Add deposit item (tiền cọc - không nhân số tháng)
            if ($depositAmount > 0) {
                $invoice->items()->create([
                    'item_type' => 'deposit',
                    'description' => 'Tiền cọc - ' . $property->name . ' - ' . $unit->code,
                    'quantity' => 1,
                    'unit_price' => $depositAmount,
                    'amount' => $depositAmount,
                    'meta_json' => [
                        'lease_id' => $lease->id,
                        'type' => 'deposit',
                    ],
                ]);
            }
            
            // Add rent item if invoice_timing = 'start_of_cycle'
            if ($invoiceTiming === 'start_of_cycle' && $rentTotal > 0 && $cycleInfo) {
                $invoice->items()->create([
                    'item_type' => 'rent',
                    'description' => $cycleInfo['rent_description'] ?? 'Tiền thuê chu kỳ đầu',
                    'quantity' => $cycleInfo['rent_quantity'] ?? 1,
                    'unit_price' => $cycleInfo['rent_unit_price'] ?? $lease->rent_amount,
                    'amount' => $rentTotal,
                    'meta_json' => [
                        'lease_id' => $lease->id,
                        'type' => 'first_cycle_rent',
                    ],
                ]);
            }
            
            // KHÔNG fill service items khi invoice_timing = 'start_of_cycle'
            // Dịch vụ sẽ được tính trong các hóa đơn chu kỳ tiếp theo
            
            // Recalculate totals from items
            $recalculatedSubtotal = $invoice->items()->sum('amount');
            $invoice->update([
                'subtotal' => $recalculatedSubtotal,
                'total_amount' => max(0, $recalculatedSubtotal),
            ]);
            
            Log::info('First invoice created manually from lease show page', [
                'lease_id' => $lease->id,
                'invoice_id' => $invoice->id,
                'invoice_no' => $invoiceNumber,
                'booking_id' => $lease->booking_id,
                'invoice_timing' => $invoiceTiming,
                'total_amount' => $recalculatedSubtotal
            ]);
            
            return $invoice;
            
        } catch (\Exception $e) {
            Log::error('Error creating first invoice without booking: ' . $e->getMessage(), [
                'lease_id' => $lease->id,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }
    
    /**
     * Calculate first invoice data for pre-filling form
     * 
     * @param \App\Models\Lease $lease
     * @return array
     */
    private function calculateFirstInvoiceData(\App\Models\Lease $lease)
    {
        // Kiểm tra invoice_timing từ default payment cycle
        $organization = $lease->organization ?? \App\Models\Organization::find($lease->organization_id);
        $defaultPaymentCycle = $organization ? $organization->defaultPaymentCycle : null;
        $invoiceTiming = $defaultPaymentCycle ? ($defaultPaymentCycle->invoice_timing ?? 'end_of_cycle') : 'end_of_cycle';
        
            // Calculate dates
            $issueDate = $lease->start_date;
            // Sử dụng invoice_payment_days từ default payment cycle
            $dueDate = $this->calculateDueDateForInvoice($lease, $issueDate);
        
        // Calculate totals
        $depositAmount = (float)$lease->deposit_amount ?? 0;
        
        $rentTotal = 0;
        $servicesTotal = 0;
        $cycleInfo = null;
        $items = [];
        
        // Chỉ tính tiền thuê nếu invoice_timing = 'start_of_cycle' (KHÔNG tính dịch vụ)
        if ($invoiceTiming === 'start_of_cycle') {
            $cycleInfo = $this->calculatePaymentCycleForInvoice($lease);
            $rentTotal = $cycleInfo['rent_total'] ?? 0;
            // KHÔNG tính dịch vụ trong first invoice
            $servicesTotal = 0;
        }
        
        // Get unit and property information
        $unit = $lease->unit;
        $property = $unit->property;
        
        // Add deposit item (tiền cọc - không nhân số tháng)
        if ($depositAmount > 0) {
            $items[] = [
                'item_type' => 'deposit',
                'description' => 'Tiền cọc - ' . $property->name . ' - ' . $unit->code,
                'quantity' => 1,
                'unit_price' => $depositAmount,
                'amount' => $depositAmount,
            ];
        }
        
        // Add booking deposit deduction if exists
        if ($lease->booking_id && $lease->bookingDeposit && $lease->bookingDeposit->amount > 0) {
            $items[] = [
                'item_type' => 'deposit',
                'description' => 'Trừ tiền cọc đặt chỗ đã thanh toán (Booking Deposit #' . $lease->bookingDeposit->booking_code . ')',
                'quantity' => 1,
                'unit_price' => -$lease->bookingDeposit->amount,
                'amount' => -$lease->bookingDeposit->amount,
            ];
        }
        
        // Add rent item if invoice_timing = 'start_of_cycle'
        if ($invoiceTiming === 'start_of_cycle' && $rentTotal > 0 && $cycleInfo) {
            $items[] = [
                'item_type' => 'rent',
                'description' => $cycleInfo['rent_description'] ?? 'Tiền thuê chu kỳ đầu',
                'quantity' => $cycleInfo['rent_quantity'] ?? 1,
                'unit_price' => $cycleInfo['rent_unit_price'] ?? $lease->rent_amount,
                'amount' => $rentTotal,
            ];
        }
        
        // KHÔNG fill service items khi invoice_timing = 'start_of_cycle'
        // Dịch vụ sẽ được tính trong các hóa đơn chu kỳ tiếp theo
        
        // Calculate total
        $totalAmount = array_sum(array_column($items, 'amount'));
        
        return [
            'lease_id' => $lease->id,
            'booking_deposit_id' => $lease->booking_id ? $lease->bookingDeposit->id : null,
            'issue_date' => $issueDate->format('Y-m-d'),
            'due_date' => $dueDate,
            'status' => 'draft',
            'subtotal' => max(0, $totalAmount),
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => max(0, $totalAmount),
            'currency' => 'VND',
            'note' => "Hóa đơn đầu tiên" . ($invoiceTiming === 'start_of_cycle' ? ' (bao gồm tiền cọc và tiền thuê chu kỳ đầu)' : ' (chỉ tiền cọc)') . " cho {$property->name} - {$unit->code}",
            'items' => $items,
        ];
    }

    /**
     * Create cycle invoice for a lease with meter readings
     */
    public function createCycleInvoice(Request $request, $leaseId)
    {
        try {
            $lease = \App\Models\Lease::with(['unit.property', 'tenant', 'leaseServiceSet.items.service', 'unit.meters'])
                ->findOrFail($leaseId);
            
            // Check if user has access to this lease
            $user = Auth::user();
            $canCreateInvoice = $this->checkCapability('billing.invoice.create');
            if (!$canCreateInvoice && $lease->agent_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền tạo hóa đơn cho hợp đồng này.'
                ], 403);
            }
            
            // Validate cycle dates
            $validated = $request->validate([
                'cycle_start_date' => 'required|date',
                'cycle_end_date' => 'required|date|after:cycle_start_date',
            ]);
            
            $cycleStartDate = \Carbon\Carbon::parse($validated['cycle_start_date']);
            $cycleEndDate = \Carbon\Carbon::parse($validated['cycle_end_date']);
            
            // Calculate invoice details
            // Priority: Lease Cycle > Property Cycle > Organization Default Cycle
            $property = $lease->property;
            
            if ($property) {
                $invoicePaymentDays = $property->getEffectiveInvoicePaymentDays();
            } else {
                $organization = $lease->organization;
                $invoicePaymentDays = $organization ? $organization->getEffectiveInvoicePaymentDays() : 30;
            }
            
            $issueDate = $cycleStartDate;
            // Hạn thanh toán = issue_date + invoice_payment_days
            $dueDate = $issueDate->copy()->addDays($invoicePaymentDays);
            
            // Get payment cycle info
            $cycleInfo = $this->calculateCycleInvoiceWithMeters($lease, $cycleStartDate, $cycleEndDate);
            
            if (!$cycleInfo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể tính toán thông tin chu kỳ.'
                ], 400);
            }
            
            $subtotal = $cycleInfo['rent_total'] + $cycleInfo['services_total'];
            
            // Generate invoice number
            $invoiceNumber = \App\Models\Invoice::generateInvoiceNumber($lease->organization_id);
            
            // Create invoice
            DB::beginTransaction();
            
            $invoice = \App\Models\Invoice::create([
                'organization_id' => $lease->organization_id,
                'is_auto_created' => false,
                'lease_id' => $lease->id,
                'invoice_no' => $invoiceNumber,
                'invoice_type' => \App\Models\Invoice::TYPE_MONTHLY_RENT,
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'status' => 'draft',
                'subtotal' => $subtotal,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => $subtotal,
                'currency' => 'VND',
                'note' => "Hóa đơn chu kỳ từ {$cycleStartDate->format('d/m/Y')} đến {$cycleEndDate->format('d/m/Y')}",
                'created_by' => Auth::id(),
            ]);
            
            // Add rent item
            if ($cycleInfo['rent_total'] > 0) {
                $invoice->items()->create([
                    'item_type' => 'rent',
                    'description' => $cycleInfo['rent_description'],
                    'quantity' => $cycleInfo['rent_quantity'],
                    'unit_price' => $cycleInfo['rent_unit_price'],
                    'amount' => $cycleInfo['rent_total'],
                    'meta_json' => [
                        'lease_id' => $lease->id,
                        'cycle_start' => $cycleStartDate->format('Y-m-d'),
                        'cycle_end' => $cycleEndDate->format('Y-m-d'),
                    ],
                ]);
            }
            
            // Add service items
            foreach ($cycleInfo['service_items'] as $serviceItem) {
                $invoice->items()->create([
                    'item_type' => $serviceItem['item_type'],
                    'description' => $serviceItem['description'],
                    'quantity' => $serviceItem['quantity'],
                    'unit_price' => $serviceItem['unit_price'],
                    'amount' => $serviceItem['amount'],
                    'meta_json' => array_merge($serviceItem['meta_json'] ?? [], [
                        'cycle_start' => $cycleStartDate->format('Y-m-d'),
                        'cycle_end' => $cycleEndDate->format('Y-m-d'),
                    ]),
                ]);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Hóa đơn chu kỳ đã được tạo thành công!',
                'invoice_id' => $invoice->id,
                'redirect' => route('staff.invoices.show', $invoice->id)
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating cycle invoice', [
                'lease_id' => $leaseId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get next unpaid cycle for a lease
     * Chu kỳ tính từ ngày bắt đầu hợp đồng, dựa vào invoice_timing
     * 
     * START_OF_CYCLE: Cycle 1 là first_invoice (có tiền thuê), chu kỳ tiếp theo là Cycle 2
     * END_OF_CYCLE: Cycle 1 là chu kỳ đầu tiên (first_invoice chỉ có tiền cọc), chu kỳ tiếp theo là Cycle 1
     */
    private function getNextUnpaidCycle(\App\Models\Lease $lease)
    {
        try {
            // Priority: Lease Cycle > Property Cycle > Organization Default Cycle
            $property = $lease->property;
            
            if ($property) {
                $invoiceTiming = $property->getEffectiveInvoiceTiming();
            } else {
                $organization = $lease->organization;
                $invoiceTiming = $organization ? $organization->getEffectiveInvoiceTiming() : 'end_of_cycle';
            }
            
            $paymentCycle = $lease->getEffectivePaymentCycle();
            $paymentCycleMonths = $paymentCycle ? (int)$paymentCycle->cycle_months : 1;
            
            // Fallback nếu cycle_months vẫn null hoặc 0
            if (empty($paymentCycleMonths)) {
                $paymentCycleMonths = 1; // Default 1 month
            }
            
            $leaseStartDate = \Carbon\Carbon::parse($lease->start_date);
            $currentDate = \Carbon\Carbon::now();
            
            // Kiểm tra first invoice
            $firstInvoice = \App\Models\Invoice::where('lease_id', $lease->id)
                ->where('invoice_type', \App\Models\Invoice::TYPE_FIRST_INVOICE)
                ->whereIn('status', ['draft', 'issued', 'paid', 'overdue'])
                ->whereNull('deleted_at')
                ->first();
            
            if (!$firstInvoice) {
                return null; // Chưa có first invoice
            }
            
            // Đếm số hóa đơn monthly_rent đã tạo
            $monthlyInvoiceCount = \App\Models\Invoice::where('lease_id', $lease->id)
                ->where('invoice_type', \App\Models\Invoice::TYPE_MONTHLY_RENT)
                ->whereIn('status', ['draft', 'issued', 'paid', 'overdue'])
                ->whereNull('deleted_at')
                ->count();
            
            // Cycle number tiếp theo dựa vào invoice_timing
            if ($invoiceTiming === 'start_of_cycle') {
                // START_OF_CYCLE: Cycle 1 là first_invoice, tiếp theo là Cycle 2, 3, 4...
                $nextCycleNumber = $monthlyInvoiceCount + 2;
                // Cycle 2 bắt đầu từ start_date + paymentCycleMonths
                $multiplier = $nextCycleNumber - 1; // Cycle 2 => multiplier = 1
                $cycleStart = $leaseStartDate->copy()->addMonths($paymentCycleMonths * $multiplier);
            } else {
                // END_OF_CYCLE: Cycle 1 là chu kỳ đầu tiên (first_invoice chỉ có tiền cọc)
                $nextCycleNumber = $monthlyInvoiceCount + 1;
                // Cycle 1 bắt đầu từ start_date
                $multiplier = $nextCycleNumber - 1; // Cycle 1 => multiplier = 0
                $cycleStart = $leaseStartDate->copy()->addMonths($paymentCycleMonths * $multiplier);
            }
            
            // Tính ngày kết thúc chu kỳ
            // Ví dụ: 07/10/2025 + 1 tháng - 1 ngày = 06/11/2025
            $cycleEnd = $cycleStart->copy()->addMonths($paymentCycleMonths)->subDay();
            
            // Debug log
            Log::info('Next unpaid cycle calculated', [
                'lease_id' => $lease->id,
                'lease_start_date' => $leaseStartDate->format('Y-m-d'),
                'invoice_timing' => $invoiceTiming,
                'monthly_invoice_count' => $monthlyInvoiceCount,
                'next_cycle_number' => $nextCycleNumber,
                'multiplier' => $multiplier ?? 0,
                'payment_cycle_months' => $paymentCycleMonths,
                'cycle_start_raw' => $cycleStart->format('Y-m-d H:i:s'),
                'cycle_start' => $cycleStart->format('d/m/Y'),
                'cycle_end_raw' => $cycleEnd->format('Y-m-d H:i:s'),
                'cycle_end' => $cycleEnd->format('d/m/Y'),
            ]);
            
            // Kiểm tra đã đến thời điểm tạo hóa đơn chưa
            if ($currentDate->lt($cycleStart)) {
                return null; // Chưa đến thời điểm tạo
            }
            
            // Kiểm tra trong thời hạn hợp đồng
            if ($lease->end_date) {
                $leaseEndDate = \Carbon\Carbon::parse($lease->end_date);
                if ($cycleStart->gt($leaseEndDate)) {
                    return null; // Vượt quá thời hạn
                }
            }
            
            return [
                'cycle_number' => $nextCycleNumber,
                'cycle_start' => $cycleStart,
                'cycle_end' => $cycleEnd,
                'billing_date' => $cycleStart,
                'payment_cycle_months' => $paymentCycleMonths,
                'invoice_timing' => $invoiceTiming,
            ];
            
        } catch (\Exception $e) {
            Log::error('Error getting next unpaid cycle', [
                'lease_id' => $lease->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Calculate cycle invoice data for prefilling
     * Chu kỳ tính từ ngày tạo hóa đơn lần trước
     */
    private function calculateCycleInvoiceData(\App\Models\Lease $lease, array $cycleInfo)
    {
        try {
            $cycleStartDate = $cycleInfo['cycle_start'];
            $cycleEndDate = $cycleInfo['cycle_end'];
            $paymentCycleMonths = $cycleInfo['payment_cycle_months'];
            
            // Lấy invoice_payment_days để tính hạn thanh toán
            // Priority: Lease Cycle > Property Cycle > Organization Default Cycle
            $property = $lease->property;
            
            if ($property) {
                $invoicePaymentDays = $property->getEffectiveInvoicePaymentDays();
            } else {
                $organization = $lease->organization;
                $invoicePaymentDays = $organization ? $organization->getEffectiveInvoicePaymentDays() : 30;
            }
            
            // Debug log
            Log::info('Calculate cycle invoice data', [
                'lease_id' => $lease->id,
                'cycle_number' => $cycleInfo['cycle_number'],
                'payment_cycle_months' => $paymentCycleMonths,
                'cycle_start_raw' => $cycleStartDate->format('Y-m-d H:i:s'),
                'cycle_start' => $cycleStartDate->format('d/m/Y'),
                'cycle_end_raw' => $cycleEndDate->format('Y-m-d H:i:s'),
                'cycle_end' => $cycleEndDate->format('d/m/Y'),
                'invoice_payment_days' => $invoicePaymentDays,
            ]);
            
            // Issue date = cycle start date (ngày tạo hóa đơn)
            $issueDate = $cycleStartDate;
            
            // Hạn thanh toán = issue_date + invoice_payment_days
            // Ví dụ: issue_date = 07/11/2025, invoice_payment_days = 30 => due_date = 07/12/2025
            $dueDate = $issueDate->copy()->addDays($invoicePaymentDays);
            
            // Get effective service set
            $effectiveSet = $lease->getEffectiveLeaseServiceSet();
            
            // Calculate rent - quantity = số tháng chu kỳ (đảm bảo >= 1)
            $rentAmount = (float)$lease->rent_amount;
            $rentQuantity = max(1, (int)$paymentCycleMonths); // Đảm bảo >= 1
            $rentTotal = $rentAmount * $rentQuantity;
            
            // Debug log rent calculation
            Log::info('Rent calculation', [
                'rent_amount' => $rentAmount,
                'rent_quantity' => $rentQuantity,
                'rent_total' => $rentTotal,
                'issue_date' => $issueDate->format('d/m/Y'),
                'due_date' => $dueDate->format('d/m/Y'),
            ]);
            
            // Calculate services
            $items = [];
            $servicesTotal = 0;
            
            // Add rent item - quantity là số tháng chu kỳ
            $items[] = [
                'item_type' => 'rent',
                'description' => "Tiền thuê tháng (Chu kỳ {$cycleInfo['cycle_number']}: {$cycleStartDate->format('d/m/Y')} - {$cycleEndDate->format('d/m/Y')})",
                'quantity' => (float)$rentQuantity, // Cast to float để đảm bảo fill đúng
                'unit_price' => (float)$rentAmount,
                'amount' => (float)$rentTotal,
            ];
            
            // Add service items
            if ($effectiveSet) {
                foreach ($effectiveSet->items as $item) {
                    $service = $item->service;
                    $servicePrice = (float)$item->price;
                    
                    if ($service->pricing_type === 'per_unit') {
                        // Lấy 2 số đo gần nhất trong thời hạn hợp đồng
                        $meter = $lease->unit->meters()
                            ->where('service_id', $service->id)
                            ->first();
                        
                        if ($meter) {
                            // Lấy trong khoảng thời hạn hợp đồng
                            $leaseStartDate = \Carbon\Carbon::parse($lease->start_date);
                            $leaseEndDate = $lease->end_date ? \Carbon\Carbon::parse($lease->end_date) : \Carbon\Carbon::now()->addYears(10);
                            
                            // Lấy 2 reading gần nhất
                            $latestReadings = \App\Models\MeterReading::where('meter_id', $meter->id)
                                ->whereBetween('reading_date', [$leaseStartDate, $leaseEndDate])
                                ->orderBy('reading_date', 'desc')
                                ->limit(2)
                                ->get();
                            
                            if ($latestReadings->count() >= 2) {
                                $endReading = $latestReadings[0]; // Mới nhất
                                $startReading = $latestReadings[1]; // Trước đó
                                
                                $usage = $endReading->value - $startReading->value;
                                $amount = $usage * $servicePrice;
                                $servicesTotal += $amount;
                                
                                $items[] = [
                                    'item_type' => 'meter',
                                    'description' => "{$service->name} - Từ {$startReading->value} ({$startReading->reading_date->format('d/m/Y')}) đến {$endReading->value} ({$endReading->reading_date->format('d/m/Y')})",
                                    'quantity' => $usage,
                                    'unit_price' => $servicePrice,
                                    'amount' => $amount,
                                ];
                            } elseif ($latestReadings->count() == 1) {
                                // Chỉ có 1 reading, dùng làm placeholder
                                $items[] = [
                                    'item_type' => 'meter',
                                    'description' => "{$service->name} (Chưa đủ 2 số đo)",
                                    'quantity' => 0,
                                    'unit_price' => $servicePrice,
                                    'amount' => 0,
                                ];
                            } else {
                                // Không có reading
                                $items[] = [
                                    'item_type' => 'meter',
                                    'description' => "{$service->name} (Chưa có số đo)",
                                    'quantity' => 0,
                                    'unit_price' => $servicePrice,
                                    'amount' => 0,
                                ];
                            }
                        }
                    } else {
                        // Dịch vụ cố định - quantity = 1 (không nhân với số tháng)
                        $quantity = 1;
                        $amount = $servicePrice * $quantity;
                        $servicesTotal += $amount;
                        
                        $items[] = [
                            'item_type' => 'service',
                            'description' => "{$service->name}",
                            'quantity' => $quantity,
                            'unit_price' => $servicePrice,
                            'amount' => $amount,
                        ];
                    }
                }
            }
            
            $subtotal = $rentTotal + $servicesTotal;
            
            $result = [
                'lease_id' => $lease->id,
                'issue_date' => $issueDate->format('Y-m-d'),
                'due_date' => $dueDate->format('Y-m-d'),
                'status' => 'draft',
                'currency' => 'VND',
                'subtotal' => $subtotal,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => $subtotal,
                'note' => "Hóa đơn chu kỳ {$cycleInfo['cycle_number']} (từ {$cycleStartDate->format('d/m/Y')} đến {$cycleEndDate->format('d/m/Y')})",
                'items' => $items,
                'cycle_start' => $cycleStartDate->format('Y-m-d'),
                'cycle_end' => $cycleEndDate->format('Y-m-d'),
                'cycle_number' => $cycleInfo['cycle_number'],
            ];
            
            // Debug log result
            Log::info('Cycle invoice data calculated', [
                'items_count' => count($items),
                'first_item' => $items[0] ?? null,
                'subtotal' => $subtotal,
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Error calculating cycle invoice data', [
                'lease_id' => $lease->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Calculate cycle invoice with meter readings
     */
    private function calculateCycleInvoiceWithMeters(\App\Models\Lease $lease, $cycleStartDate, $cycleEndDate)
    {
        try {
            $effectiveSet = $lease->getEffectiveLeaseServiceSet();
            $paymentCycle = $lease->getEffectivePaymentCycle();
            // Sử dụng accessor cycle_months để đảm bảo logic nhất quán
            $paymentCycleMonths = $paymentCycle ? (int)$paymentCycle->cycle_months : 1;
            
            // Fallback nếu cycle_months vẫn null hoặc 0
            if (empty($paymentCycleMonths)) {
                $paymentCycleMonths = 1; // Default 1 month
            }
            
            // Calculate rent
            $rentAmount = (float)$lease->rent_amount;
            $rentTotal = $rentAmount * $paymentCycleMonths;
            $rentDescription = "Tiền thuê {$paymentCycleMonths} tháng (" . $cycleStartDate->format('d/m/Y') . " - " . $cycleEndDate->format('d/m/Y') . ")";
            
            // Calculate services
            $serviceItems = [];
            $servicesTotal = 0;
            
            if ($effectiveSet) {
                foreach ($effectiveSet->items as $item) {
                    $service = $item->service;
                    $servicePrice = (float)$item->price;
                    
                    if ($service->pricing_type === 'per_unit') {
                        // Calculate based on meter readings
                        $meter = $lease->unit->meters()
                            ->where('service_id', $service->id)
                            ->first();
                        
                        if ($meter) {
                            // Get readings for this cycle
                            $startReading = \App\Models\MeterReading::where('meter_id', $meter->id)
                                ->where('reading_date', '<=', $cycleStartDate)
                                ->orderBy('reading_date', 'desc')
                                ->first();
                            
                            $endReading = \App\Models\MeterReading::where('meter_id', $meter->id)
                                ->where('reading_date', '>=', $cycleStartDate)
                                ->where('reading_date', '<=', $cycleEndDate)
                                ->orderBy('reading_date', 'desc')
                                ->first();
                            
                            if ($startReading && $endReading) {
                                $usage = $endReading->value - $startReading->value;
                                $amount = $usage * $servicePrice;
                                $servicesTotal += $amount;
                                
                                $serviceItems[] = [
                                    'item_type' => 'meter',
                                    'description' => "{$service->name} ({$usage} {$service->unit_label}) - Từ {$startReading->value} đến {$endReading->value}",
                                    'quantity' => $usage,
                                    'unit_price' => $servicePrice,
                                    'amount' => $amount,
                                    'meta_json' => [
                                        'service_id' => $service->id,
                                        'meter_id' => $meter->id,
                                        'start_reading_id' => $startReading->id,
                                        'end_reading_id' => $endReading->id,
                                        'start_reading' => $startReading->value,
                                        'end_reading' => $endReading->value,
                                        'usage' => $usage,
                                    ],
                                ];
                            } else {
                                // No meter readings, skip or use default
                                Log::warning('No meter readings found for cycle invoice', [
                                    'lease_id' => $lease->id,
                                    'service_id' => $service->id,
                                    'meter_id' => $meter->id,
                                    'cycle_start' => $cycleStartDate->format('Y-m-d'),
                                    'cycle_end' => $cycleEndDate->format('Y-m-d'),
                                ]);
                            }
                        }
                    } else {
                        // Fixed pricing
                        $quantity = $paymentCycleMonths;
                        $amount = $servicePrice * $quantity;
                        $servicesTotal += $amount;
                        
                        $serviceItems[] = [
                            'item_type' => 'service',
                            'description' => "{$service->name} ({$quantity} tháng)",
                            'quantity' => $quantity,
                            'unit_price' => $servicePrice,
                            'amount' => $amount,
                            'meta_json' => [
                                'service_id' => $service->id,
                            ],
                        ];
                    }
                }
            }
            
            return [
                'rent_total' => $rentTotal,
                'rent_description' => $rentDescription,
                'rent_quantity' => $paymentCycleMonths,
                'rent_unit_price' => $rentAmount,
                'services_total' => $servicesTotal,
                'service_items' => $serviceItems,
                'months' => $paymentCycleMonths,
            ];
            
        } catch (\Exception $e) {
            Log::error('Error calculating cycle invoice with meters', [
                'lease_id' => $lease->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Delete a lease resident
     * 
     * @param int $leaseId
     * @param int $residentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteResident($leaseId, $residentId)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Get user's organization from middleware
        $userOrganizationId = $this->getCurrentOrganizationId();
        
        // Check if user has contract.access capability
        $hasContractAccess = $this->checkCapability('contract.access');
        if (!$hasContractAccess) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền truy cập module Hợp đồng.'
            ], 403);
        }
        
        try {
            // Verify lease belongs to organization
            $lease = Lease::where('organization_id', $userOrganizationId)
                ->findOrFail($leaseId);
            
            // For agent, check if lease was created by them
            $canViewAll = $this->canViewAll('contract.lease');
            if (!$canViewAll && $lease->agent_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền xóa người ở của hợp đồng này.'
                ], 403);
            }
            
            // Find and delete resident
            $resident = \App\Models\LeaseResident::where('lease_id', $leaseId)
                ->where('id', $residentId)
                ->firstOrFail();
            
            $residentName = $resident->name;
            $resident->delete();
            
            return response()->json([
                'success' => true,
                'message' => "Đã xóa người ở \"{$residentName}\" thành công."
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy người ở hoặc hợp đồng.'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting lease resident: ' . $e->getMessage(), [
                'lease_id' => $leaseId,
                'resident_id' => $residentId,
                'error' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa người ở: ' . $e->getMessage()
            ], 500);
        }
    }
}