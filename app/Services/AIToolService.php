<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Lease;
use App\Models\OrganizationUser;
use App\Models\Property;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Ticket;
use App\Models\Vendor;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Công cụ gọi từ AI (Gemini function calling) — map đúng schema ZoroRMS (Lease, Unit, …).
 */
class AIToolService
{
    public function getToolDefinitions(): array
    {
        return [
            [
                'name' => 'search_tenants',
                'description' => 'Tìm người thuê (tenant) trong tổ chức hiện tại theo tên, email hoặc số điện thoại. Gọi công cụ này trước khi tạo hợp đồng nếu người dùng không cho ID.',
                'parameters' => [
                    'query' => ['required' => true, 'description' => 'Chuỗi tìm kiếm (tên, email, SĐT)'],
                    'limit' => ['required' => false, 'description' => 'Số kết quả tối đa (mặc định 10)'],
                ],
            ],
            [
                'name' => 'search_units',
                'description' => 'Tìm phòng/căn (unit) theo mã phòng hoặc tên bất động sản. Dùng để lấy unit_id trước khi tạo hợp đồng thuê.',
                'parameters' => [
                    'query' => ['required' => true, 'description' => 'Mã phòng, tên property, hoặc từ khóa'],
                    'limit' => ['required' => false, 'description' => 'Số kết quả tối đa (mặc định 10)'],
                ],
            ],
            [
                'name' => 'search_leads',
                'description' => 'Tìm lead (khách CRM) theo tên, email, SĐT. Hữu ích khi hợp đồng gắn với lead.',
                'parameters' => [
                    'query' => ['required' => true, 'description' => 'Chuỗi tìm kiếm'],
                    'limit' => ['required' => false, 'description' => 'Số kết quả tối đa (mặc định 10)'],
                ],
            ],
            [
                'name' => 'get_properties',
                'description' => 'Danh sách bất động sản của tổ chức (lọc theo loại hoặc giới hạn số bản ghi).',
                'parameters' => [
                    'filter_type' => ['required' => false, 'description' => 'Lọc theo loại property_type_id hoặc tên loại nếu có'],
                    'limit' => ['required' => false, 'description' => 'Giới hạn (mặc định 15)'],
                ],
            ],
            [
                'name' => 'get_org_stats',
                'description' => 'Lấy các thống kê tổng hợp cho tổ chức hiện tại. Dùng để lấy doanh thu theo khoảng thời gian (this_month, last_month, ytd, custom).',
                'parameters' => [
                    'period' => ['required' => false, 'description' => "Khoảng thời gian: 'this_month', 'last_month', 'ytd', or 'custom'"],
                    'from' => ['required' => false, 'description' => 'Ngày bắt đầu khi period=custom (YYYY-MM-DD)'],
                    'to' => ['required' => false, 'description' => 'Ngày kết thúc khi period=custom (YYYY-MM-DD)'],
                    'metric' => ['required' => false, 'description' => "Metric muốn lấy: 'revenue' (mặc định), 'invoices', 'payments', 'expenses' (tiền công ty thanh toán/chi phí)"],
                ],
            ],
            [
                'name' => 'list_units_for_property',
                'description' => 'Liệt kê các unit thuộc một property_id.',
                'parameters' => [
                    'property_id' => ['required' => true, 'description' => 'ID bất động sản'],
                ],
            ],
            [
                'name' => 'create_lease_contract',
                'description' => 'Tạo hợp đồng thuê (Lease) nháp trong hệ thống. Bắt buộc có tenant_id và unit_id đã xác định (dùng search_tenants / search_units). Ngày định dạng YYYY-MM-DD. Tiền là số (VNĐ).',
                'parameters' => [
                    'tenant_id' => ['required' => true, 'description' => 'ID user người thuê'],
                    'unit_id' => ['required' => true, 'description' => 'ID phòng/căn'],
                    'start_date' => ['required' => true, 'description' => 'Ngày bắt đầu YYYY-MM-DD'],
                    'end_date' => ['required' => true, 'description' => 'Ngày kết thúc YYYY-MM-DD'],
                    'rent_amount' => ['required' => true, 'description' => 'Tiền thuê (một kỳ / tháng theo cấu hình — giống màn tạo lease)'],
                    'deposit_amount' => ['required' => false, 'description' => 'Tiền cọc'],
                ],
            ],
            [
                'name' => 'get_invoices',
                'description' => 'Lấy danh sách hóa đơn thu (Invoices) của tổ chức. Có thể lọc theo trạng thái.',
                'parameters' => [
                    'status' => ['required' => false, 'description' => "Trạng thái hóa đơn: 'pending', 'paid', 'overdue', 'cancelled'"],
                    'limit' => ['required' => false, 'description' => 'Số kết quả tối đa (mặc định 10)'],
                ],
            ],
            [
                'name' => 'get_tickets',
                'description' => 'Lấy danh sách yêu cầu hỗ trợ/bảo trì (Tickets).',
                'parameters' => [
                    'status' => ['required' => false, 'description' => "Trạng thái: 'open', 'in_progress', 'resolved', 'closed', 'cancelled'"],
                    'limit' => ['required' => false, 'description' => 'Số kết quả tối đa (mặc định 10)'],
                ],
            ],
            [
                'name' => 'get_leases',
                'description' => 'Lấy danh sách hợp đồng thuê phòng (Leases).',
                'parameters' => [
                    'status' => ['required' => false, 'description' => "Trạng thái: 'active', 'expired', 'draft', 'terminated'"],
                    'limit' => ['required' => false, 'description' => 'Số kết quả tối đa (mặc định 10)'],
                ],
            ],
            [
                'name' => 'get_vendors',
                'description' => 'Lấy danh sách nhà cung cấp/đối tác (Vendors) hoặc tìm kiếm theo tên.',
                'parameters' => [
                    'query' => ['required' => false, 'description' => 'Tên nhà cung cấp cần tìm'],
                    'limit' => ['required' => false, 'description' => 'Số kết quả tối đa (mặc định 10)'],
                ],
            ],
            [
                'name' => 'search_staff',
                'description' => 'Tìm kiếm thông tin nhân viên trong tổ chức.',
                'parameters' => [
                    'query' => ['required' => false, 'description' => 'Tên hoặc email nhân viên'],
                    'limit' => ['required' => false, 'description' => 'Số kết quả tối đa (mặc định 10)'],
                ],
            ],
            [
                'name' => 'analyze_building_health',
                'description' => 'Dành cho Quản lý. Phân tích sức khỏe tòa nhà (tổng hợp từ Tickets và Reviews điểm thấp) để tìm nguyên nhân cốt lõi.',
                'parameters' => [
                    'property_id' => ['required' => true, 'description' => 'ID của tòa nhà/bất động sản'],
                    'period' => ['required' => false, 'description' => "Khoảng thời gian: 'this_month', 'last_month', 'this_week', 'this_year'"],
                ],
            ],
            [
                'name' => 'get_my_invoices',
                'description' => 'Dành cho Khách thuê. Lấy danh sách hóa đơn của chính khách thuê đang chat.',
                'parameters' => [
                    'status' => ['required' => false, 'description' => "Trạng thái hóa đơn: 'pending', 'paid', 'overdue'"],
                ],
            ],
            [
                'name' => 'get_my_leases',
                'description' => 'Dành cho Khách thuê. Lấy thông tin hợp đồng thuê hiện tại của khách thuê đang chat.',
                'parameters' => [
                ],
            ],
            [
                'name' => 'create_my_ticket',
                'description' => 'Dành cho Khách thuê. Tạo yêu cầu hỗ trợ/bảo trì (Ticket) gửi cho ban quản lý.',
                'parameters' => [
                    'title' => ['required' => true, 'description' => 'Tiêu đề ngắn gọn về vấn đề'],
                    'description' => ['required' => true, 'description' => 'Chi tiết vấn đề khách đang gặp phải'],
                    'priority' => ['required' => false, 'description' => "Mức độ ưu tiên: 'low', 'medium', 'high', 'urgent'"],
                ],
            ],
        ];
    }

    /**
     * Payload tools cho Gemini REST (functionDeclarations).
     */
    public function getGeminiToolsPayload(): array
    {
        return [[
            'functionDeclarations' => $this->buildGeminiFunctionDeclarations(),
        ]];
    }

    private function buildGeminiFunctionDeclarations(): array
    {
        $out = [];
        foreach ($this->getToolDefinitions() as $tool) {
            $properties = [];
            $required = [];
            foreach ($tool['parameters'] as $name => $param) {
                $properties[$name] = [
                    'type' => 'string',
                    'description' => $param['description'] ?? '',
                ];
                if (!empty($param['required'])) {
                    $required[] = $name;
                }
            }
            $out[] = [
                'name' => $tool['name'],
                'description' => $tool['description'],
                'parameters' => [
                    'type' => 'object',
                    'properties' => empty($properties) ? (object)[] : $properties,
                    'required' => $required,
                ],
            ];
        }

        return $out;
    }

    public function executeTool(string $toolName, array $args): array
    {
        try {
            Log::info("AIToolService: {$toolName}", $args);

            return match ($toolName) {
                'search_tenants' => $this->searchTenants($args),
                'search_units' => $this->searchUnits($args),
                'search_leads' => $this->searchLeads($args),
                'get_properties' => $this->getProperties($args),
                'list_units_for_property' => $this->listUnitsForProperty($args),
                'create_lease_contract' => $this->createLeaseContract($args),
                'create_rental_contract' => $this->createLeaseContract([
                    'tenant_id' => $args['tenant_id'] ?? 0,
                    'unit_id' => (int) ($args['unit_id'] ?? $args['room_id'] ?? 0),
                    'start_date' => $args['start_date'] ?? '',
                    'end_date' => $args['end_date'] ?? '',
                    'rent_amount' => $args['rent_amount'] ?? $args['monthly_rent'] ?? 0,
                    'deposit_amount' => $args['deposit_amount'] ?? 0,
                ]),
                'get_rooms' => $this->listUnitsForProperty([
                    'property_id' => (int) ($args['property_id'] ?? 0),
                ]),
                'get_org_stats' => $this->getOrgStats($args),
                'get_invoices' => $this->getInvoices($args),
                'get_tickets' => $this->getTickets($args),
                'get_leases' => $this->getLeases($args),
                'get_vendors' => $this->getVendors($args),
                'search_staff' => $this->searchStaff($args),
                'analyze_building_health' => $this->analyzeBuildingHealth($args),
                'get_my_invoices' => $this->getMyInvoices($args),
                'get_my_leases' => $this->getMyLeases($args),
                'create_my_ticket' => $this->createMyTicket($args),
                default => ['success' => false, 'error' => "Tool không hỗ trợ: {$toolName}"],
            };
        } catch (Exception $e) {
            Log::error("AIToolService error: {$toolName}", ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function organizationId(): int
    {
        $user = auth()->user();
        if (!$user) {
            throw new Exception('Chưa đăng nhập.');
        }
        $orgId = $user->getCurrentOrganizationId();
        if (!$orgId) {
            throw new Exception('Không xác định được tổ chức (organization). Chọn tổ chức trước khi dùng chat.');
        }

        return (int) $orgId;
    }

    private function searchTenants(array $args): array
    {
        $q = trim((string) ($args['query'] ?? ''));
        if ($q === '') {
            return ['success' => false, 'error' => 'Thiếu query'];
        }
        $limit = min(20, max(1, (int) ($args['limit'] ?? 10)));
        $orgId = $this->organizationId();

        $tenantRoleId = Role::where('key_code', 'tenant')->value('id');
        if (!$tenantRoleId) {
            return ['success' => false, 'error' => 'Cấu hình role tenant thiếu.'];
        }

        $query = User::query()
            ->whereHas('organizationUsers', function ($qu) use ($orgId, $tenantRoleId) {
                $qu->where('organization_id', $orgId)
                    ->where('role_id', $tenantRoleId)
                    ->whereNull('deleted_at');
            })
            ->where(function ($qu) use ($q) {
                $qu->where('email', 'like', '%'.$q.'%')
                    ->orWhere('phone', 'like', '%'.$q.'%')
                    ->orWhereHas('userProfile', function ($p) use ($q) {
                        $p->where('full_name', 'like', '%'.$q.'%');
                    });
            })
            ->with('userProfile')
            ->limit($limit)
            ->get();

        $rows = $query->map(function (User $u) {
            return [
                'tenant_id' => $u->id,
                'email' => $u->email,
                'phone' => $u->phone,
                'full_name' => $u->userProfile->full_name ?? $u->full_name ?? '',
            ];
        })->values()->all();

        return [
            'success' => true,
            'count' => count($rows),
            'tenants' => $rows,
        ];
    }

    private function searchUnits(array $args): array
    {
        $q = trim((string) ($args['query'] ?? ''));
        if ($q === '') {
            return ['success' => false, 'error' => 'Thiếu query'];
        }
        $limit = min(20, max(1, (int) ($args['limit'] ?? 10)));
        $orgId = $this->organizationId();

        $units = Unit::query()
            ->whereHas('property', function ($p) use ($orgId) {
                $p->where('organization_id', $orgId);
            })
            ->with('property:id,name')
            ->where(function ($uq) use ($q) {
                $uq->where('code', 'like', '%'.$q.'%')
                    ->orWhereHas('property', function ($p) use ($q) {
                        $p->where('name', 'like', '%'.$q.'%');
                    });
            })
            ->limit($limit)
            ->get();

        $rows = $units->map(function (Unit $unit) {
            return [
                'unit_id' => $unit->id,
                'unit_code' => $unit->code,
                'property_id' => $unit->property_id,
                'property_name' => $unit->property->name ?? '',
                'status' => $unit->status,
                'base_rent' => $unit->base_rent,
            ];
        })->values()->all();

        return [
            'success' => true,
            'count' => count($rows),
            'units' => $rows,
        ];
    }

    private function searchLeads(array $args): array
    {
        $q = trim((string) ($args['query'] ?? ''));
        if ($q === '') {
            return ['success' => false, 'error' => 'Thiếu query'];
        }
        $limit = min(20, max(1, (int) ($args['limit'] ?? 10)));
        $orgId = $this->organizationId();

        $leads = Lead::where('organization_id', $orgId)
            ->where(function ($lq) use ($q) {
                $lq->where('name', 'like', '%'.$q.'%')
                    ->orWhere('email', 'like', '%'.$q.'%')
                    ->orWhere('phone', 'like', '%'.$q.'%');
            })
            ->limit($limit)
            ->get(['id', 'name', 'email', 'phone', 'status', 'tenant_id']);

        return [
            'success' => true,
            'count' => $leads->count(),
            'leads' => $leads->toArray(),
        ];
    }

    private function getProperties(array $args): array
    {
        $orgId = $this->organizationId();
        $query = Property::where('organization_id', $orgId);
        $limit = min(30, max(1, (int) ($args['limit'] ?? 15)));

        $properties = $query->orderBy('name')->limit($limit)->get(['id', 'name', 'property_type_id', 'status']);

        return [
            'success' => true,
            'count' => $properties->count(),
            'properties' => $properties->toArray(),
        ];
    }

    private function listUnitsForProperty(array $args): array
    {
        $propertyId = (int) ($args['property_id'] ?? 0);
        if ($propertyId < 1) {
            return ['success' => false, 'error' => 'property_id không hợp lệ'];
        }
        $orgId = $this->organizationId();

        $property = Property::where('organization_id', $orgId)->where('id', $propertyId)->first();
        if (!$property) {
            return ['success' => false, 'error' => 'Không tìm thấy bất động sản trong tổ chức của bạn.'];
        }

        $units = Unit::where('property_id', $propertyId)
            ->orderBy('code')
            ->get(['id', 'code', 'status', 'base_rent', 'floor']);

        return [
            'success' => true,
            'property_id' => $propertyId,
            'property_name' => $property->name,
            'units' => $units->toArray(),
        ];
    }

    private function createLeaseContract(array $args): array
    {
        $orgId = $this->organizationId();
        $tenantId = (int) ($args['tenant_id'] ?? 0);
        $unitId = (int) ($args['unit_id'] ?? 0);
        $start = $args['start_date'] ?? null;
        $end = $args['end_date'] ?? null;
        $rent = $args['rent_amount'] ?? null;
        $deposit = isset($args['deposit_amount']) ? (float) $args['deposit_amount'] : 0.0;

        if ($tenantId < 1 || $unitId < 1 || !$start || !$end || $rent === null || $rent === '') {
            return ['success' => false, 'error' => 'Thiếu tenant_id, unit_id, start_date, end_date hoặc rent_amount.'];
        }

        $unit = Unit::with('property')->find($unitId);
        if (!$unit || !$unit->property || (int) $unit->property->organization_id !== $orgId) {
            return ['success' => false, 'error' => 'Phòng/căn không thuộc tổ chức hiện tại hoặc không tồn tại.'];
        }

        $tenantRoleId = Role::where('key_code', 'tenant')->value('id');
        $inOrg = OrganizationUser::where('organization_id', $orgId)
            ->where('user_id', $tenantId)
            ->where('role_id', $tenantRoleId)
            ->whereNull('deleted_at')
            ->exists();
        if (!$inOrg) {
            return ['success' => false, 'error' => 'Người dùng không phải tenant trong tổ chức này. Dùng search_tenants để lấy đúng tenant_id.'];
        }

        $hasBlocking = Lease::where('unit_id', $unitId)
            ->whereIn('status', ['draft', 'active'])
            ->whereNull('deleted_at')
            ->exists();
        if ($hasBlocking) {
            return [
                'success' => false,
                'error' => 'Phòng đã có hợp đồng nháp/chờ/active. Kiểm tra trên giao diện hoặc kết thúc hợp đồng cũ trước.',
            ];
        }

        $rentAmount = is_numeric($rent) ? (float) $rent : (float) str_replace([',', ' '], '', (string) $rent);

        /** @var \App\Models\User $staff */
        $staff = auth()->user();

        $lease = DB::transaction(function () use ($orgId, $unitId, $tenantId, $start, $end, $rentAmount, $deposit, $staff) {
            return Lease::create([
                'organization_id' => $orgId,
                'unit_id' => $unitId,
                'tenant_id' => $tenantId,
                'agent_id' => $staff->id,
                'start_date' => $start,
                'end_date' => $end,
                'rent_amount' => $rentAmount,
                'deposit_amount' => $deposit,
                'status' => 'draft',
            ]);
        });

        $url = route('staff.leases.show', $lease->id);

        return [
            'success' => true,
            'message' => 'Đã tạo hợp đồng thuê ở trạng thái nháp.',
            'lease_id' => $lease->id,
            'open_url' => $url,
            'summary' => [
                'unit_id' => $unitId,
                'tenant_id' => $tenantId,
                'start_date' => (string) $lease->start_date,
                'end_date' => (string) $lease->end_date,
                'rent_amount' => $lease->rent_amount,
            ],
        ];
    }

    /**
     * Trả về các thống kê cho tổ chức hiện tại theo khoảng thời gian.
     * Supported args: period (this_month, last_month, ytd, custom), from, to, metric
     */
    private function getOrgStats(array $args): array
    {
        $orgId = $this->organizationId();

        $period = $args['period'] ?? 'this_month';
        $metric = $args['metric'] ?? 'revenue';

        $from = null;
        $to = null;
        $now = now();

        if ($period === 'last_month') {
            $from = $now->copy()->subMonthNoOverflow()->startOfMonth();
            $to = $now->copy()->subMonthNoOverflow()->endOfMonth();
        } elseif ($period === 'ytd') {
            $from = $now->copy()->startOfYear();
            $to = $now;
        } elseif ($period === 'custom') {
            $from = !empty($args['from']) ? \Carbon\Carbon::parse($args['from'])->startOfDay() : null;
            $to = !empty($args['to']) ? \Carbon\Carbon::parse($args['to'])->endOfDay() : null;
        } else { // default this_month
            $from = $now->copy()->startOfMonth();
            $to = $now;
        }

        try {
            if ($metric === 'payments' || $metric === 'revenue') {
                // Sum successful payments in period for organization
                $paymentsQuery = \App\Models\Payment::query()
                    ->where('status', \App\Models\Payment::STATUS_SUCCESS)
                    ->whereHas('invoice.lease.property', function ($q) use ($orgId) {
                        $q->where('organization_id', $orgId);
                    });

                if ($from) {
                    $paymentsQuery->where('paid_at', '>=', $from);
                }
                if ($to) {
                    $paymentsQuery->where('paid_at', '<=', $to);
                }

                $totalRevenue = (float) $paymentsQuery->sum('amount');
                $paymentsCount = (int) $paymentsQuery->count();

                return [
                    'success' => true,
                    'metric' => 'revenue',
                    'period' => $period,
                    'from' => $from ? (string) $from : null,
                    'to' => $to ? (string) $to : null,
                    'total_revenue' => $totalRevenue,
                    'payments_count' => $paymentsCount,
                ];
            }

            if ($metric === 'invoices') {
                $invoicesQuery = \App\Models\Invoice::query()
                    ->where('organization_id', $orgId);

                if ($from) {
                    $invoicesQuery->where('issue_date', '>=', $from->toDateString());
                }
                if ($to) {
                    $invoicesQuery->where('issue_date', '<=', $to->toDateString());
                }

                $count = $invoicesQuery->count();
                $sum = (float) $invoicesQuery->sum('total_amount');

                return [
                    'success' => true,
                    'metric' => 'invoices',
                    'period' => $period,
                    'from' => $from ? (string) $from : null,
                    'to' => $to ? (string) $to : null,
                    'invoices_count' => $count,
                    'invoices_total' => $sum,
                ];
            }

            if ($metric === 'expenses') {
                $expensesQuery = \App\Models\CashOutflow::byOrganization($orgId)
                    ->where('status', \App\Models\CashOutflow::STATUS_SUCCESS);

                if ($from) {
                    $expensesQuery->where('paid_at', '>=', $from);
                }
                if ($to) {
                    $expensesQuery->where('paid_at', '<=', $to);
                }

                $totalExpenses = (float) $expensesQuery->sum('amount');
                $expensesCount = (int) $expensesQuery->count();

                return [
                    'success' => true,
                    'metric' => 'expenses',
                    'period' => $period,
                    'from' => $from ? (string) $from : null,
                    'to' => $to ? (string) $to : null,
                    'total_expenses' => $totalExpenses,
                    'expenses_count' => $expensesCount,
                ];
            }

            return ['success' => false, 'error' => 'Metric không hỗ trợ: ' . $metric];
        } catch (Exception $e) {
            Log::error('getOrgStats error', ['err' => $e->getMessage(), 'args' => $args]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function getInvoices(array $args): array
    {
        $orgId = $this->organizationId();
        $limit = min(20, max(1, (int) ($args['limit'] ?? 10)));
        $status = $args['status'] ?? null;

        $query = Invoice::where('organization_id', $orgId);
        
        if ($status) {
            if ($status === 'overdue') {
                $query->where('due_date', '<', now())->whereNotIn('status', ['paid', 'cancelled']);
            } else {
                $query->where('status', $status);
            }
        }

        $invoices = $query->with('lease.unit')->orderBy('issue_date', 'desc')->limit($limit)->get();

        $rows = $invoices->map(function ($invoice) {
            return [
                'id' => $invoice->id,
                'invoice_no' => $invoice->invoice_no,
                'status' => $invoice->status,
                'total_amount' => $invoice->total_amount,
                'paid_amount' => $invoice->paid_amount ?? 0,
                'issue_date' => (string) $invoice->issue_date,
                'due_date' => (string) $invoice->due_date,
                'unit' => $invoice->lease->unit->code ?? null,
            ];
        })->values()->all();

        return [
            'success' => true,
            'count' => count($rows),
            'invoices' => $rows,
        ];
    }

    private function getTickets(array $args): array
    {
        $orgId = $this->organizationId();
        $limit = min(20, max(1, (int) ($args['limit'] ?? 10)));
        $status = $args['status'] ?? null;

        $query = Ticket::where('organization_id', $orgId);
        
        if ($status) {
            $query->where('status', $status);
        } else {
            // Default to active tickets if not specified
            $query->whereNotIn('status', ['closed', 'cancelled']);
        }

        $tickets = $query->with('unit')->orderBy('created_at', 'desc')->limit($limit)->get();

        $rows = $tickets->map(function ($ticket) {
            return [
                'id' => $ticket->id,
                'title' => $ticket->title,
                'status' => $ticket->status,
                'priority' => $ticket->priorityRelation->key_code ?? 'medium',
                'unit' => $ticket->unit->code ?? null,
                'created_at' => (string) $ticket->created_at,
            ];
        })->values()->all();

        return [
            'success' => true,
            'count' => count($rows),
            'tickets' => $rows,
        ];
    }

    private function getLeases(array $args): array
    {
        $orgId = $this->organizationId();
        $limit = min(20, max(1, (int) ($args['limit'] ?? 10)));
        $status = $args['status'] ?? null;

        $query = Lease::where('organization_id', $orgId);
        
        if ($status) {
            $query->where('status', $status);
        }

        $leases = $query->with('unit', 'tenant')->orderBy('created_at', 'desc')->limit($limit)->get();

        $rows = $leases->map(function ($lease) {
            return [
                'id' => $lease->id,
                'status' => $lease->status,
                'unit' => $lease->unit->code ?? null,
                'tenant_name' => $lease->tenant->full_name ?? null,
                'start_date' => (string) $lease->start_date,
                'end_date' => (string) $lease->end_date,
                'rent_amount' => $lease->rent_amount,
            ];
        })->values()->all();

        return [
            'success' => true,
            'count' => count($rows),
            'leases' => $rows,
        ];
    }

    private function getVendors(array $args): array
    {
        $orgId = $this->organizationId();
        $limit = min(20, max(1, (int) ($args['limit'] ?? 10)));
        $q = trim((string) ($args['query'] ?? ''));

        $query = Vendor::where('organization_id', $orgId);
        
        if ($q !== '') {
            $query->where('name', 'like', '%' . $q . '%')
                  ->orWhere('email', 'like', '%' . $q . '%')
                  ->orWhere('phone', 'like', '%' . $q . '%');
        }

        $vendors = $query->orderBy('name')->limit($limit)->get();

        $rows = $vendors->map(function ($vendor) {
            return [
                'id' => $vendor->id,
                'name' => $vendor->name,
                'email' => $vendor->email,
                'phone' => $vendor->phone,
                'contact_person' => $vendor->contact_person,
            ];
        })->values()->all();

        return [
            'success' => true,
            'count' => count($rows),
            'vendors' => $rows,
        ];
    }

    private function searchStaff(array $args): array
    {
        $orgId = $this->organizationId();
        $limit = min(20, max(1, (int) ($args['limit'] ?? 10)));
        $q = trim((string) ($args['query'] ?? ''));

        // Filter out tenants to only get staff
        $tenantRoleId = Role::where('key_code', 'tenant')->value('id');

        $query = User::whereHas('organizationUsers', function ($qu) use ($orgId, $tenantRoleId) {
            $qu->where('organization_id', $orgId)
               ->whereNull('deleted_at');
            
            if ($tenantRoleId) {
                $qu->where('role_id', '!=', $tenantRoleId);
            }
        });

        if ($q !== '') {
            $query->where(function ($qu) use ($q) {
                $qu->where('email', 'like', '%' . $q . '%')
                   ->orWhere('phone', 'like', '%' . $q . '%')
                   ->orWhere('name', 'like', '%' . $q . '%')
                   ->orWhereHas('userProfile', function ($p) use ($q) {
                       $p->where('full_name', 'like', '%' . $q . '%');
                   });
            });
        }

        $staff = $query->with('userProfile', 'organizationUsers.role')->limit($limit)->get();

        $rows = $staff->map(function ($u) use ($orgId) {
            $orgUser = $u->organizationUsers->where('organization_id', $orgId)->first();
            $roleName = $orgUser && $orgUser->role ? $orgUser->role->name : 'Unknown';
            
            return [
                'id' => $u->id,
                'email' => $u->email,
                'phone' => $u->phone,
                'full_name' => $u->userProfile->full_name ?? $u->name ?? '',
                'role' => $roleName,
            ];
        })->values()->all();

        return [
            'success' => true,
            'count' => count($rows),
            'staff' => $rows,
        ];
    }

    private function analyzeBuildingHealth(array $args): array
    {
        $orgId = $this->organizationId();
        $propertyId = (int) ($args['property_id'] ?? 0);
        
        if ($propertyId <= 0) {
            return ['success' => false, 'error' => 'Vui lòng cung cấp property_id hợp lệ.'];
        }

        // Get tickets
        $tickets = Ticket::where('organization_id', $orgId)
            ->where('property_id', $propertyId)
            ->where('created_at', '>=', now()->subDays(30))
            ->get(['title', 'description', 'status']);

        // Get bad reviews
        $reviews = \App\Models\Review::where('organization_id', $orgId)
            ->whereHas('unit', function($q) use ($propertyId) {
                $q->where('property_id', $propertyId);
            })
            ->where('overall_rating', '<', 4)
            ->where('created_at', '>=', now()->subDays(30))
            ->get(['title', 'content', 'overall_rating', 'highlights']);

        return [
            'success' => true,
            'message' => 'Vui lòng phân tích dữ liệu thô sau đây và tóm tắt nguyên nhân cốt lõi khiến khách hàng không hài lòng. Đưa ra cảnh báo cho quản lý.',
            'raw_tickets_30_days' => $tickets->toArray(),
            'raw_bad_reviews_30_days' => $reviews->toArray(),
        ];
    }

    private function getMyInvoices(array $args): array
    {
        $user = auth()->user();
        $status = $args['status'] ?? null;

        if (!$user) {
            return ['success' => false, 'error' => 'Chưa đăng nhập.'];
        }

        $query = Invoice::whereHas('lease', function($q) use ($user) {
            $q->where('tenant_id', $user->id);
        });

        if ($status) {
            if ($status === 'overdue') {
                $query->where('due_date', '<', now())->whereNotIn('status', ['paid', 'cancelled']);
            } else {
                $query->where('status', $status);
            }
        }

        $invoices = $query->with('lease.unit.property')->orderBy('due_date', 'desc')->limit(10)->get();
        
        $rows = $invoices->map(function ($invoice) {
            return [
                'id' => $invoice->id,
                'invoice_no' => $invoice->invoice_no,
                'status' => $invoice->status,
                'total_amount' => $invoice->total_amount,
                'paid_amount' => $invoice->paid_amount ?? 0,
                'due_date' => (string) $invoice->due_date,
                'unit' => $invoice->lease->unit->code ?? null,
                'property' => $invoice->lease->unit->property->name ?? null,
            ];
        })->values()->all();

        return [
            'success' => true,
            'invoices' => $rows,
        ];
    }

    private function getMyLeases(array $args): array
    {
        $user = auth()->user();
        
        if (!$user) {
            return ['success' => false, 'error' => 'Chưa đăng nhập.'];
        }

        $leases = Lease::where('tenant_id', $user->id)
            ->with('unit.property')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $rows = $leases->map(function ($lease) {
            return [
                'id' => $lease->id,
                'status' => $lease->status,
                'start_date' => (string) $lease->start_date,
                'end_date' => (string) $lease->end_date,
                'rent_amount' => $lease->rent_amount,
                'unit' => $lease->unit->code ?? null,
                'property' => $lease->unit->property->name ?? null,
            ];
        })->values()->all();

        return [
            'success' => true,
            'leases' => $rows,
        ];
    }

    private function createMyTicket(array $args): array
    {
        $user = auth()->user();
        $orgId = $user->getCurrentOrganizationId();

        if (!$user || !$orgId) {
            return ['success' => false, 'error' => 'Chưa đăng nhập hoặc không xác định được tổ chức.'];
        }

        $title = $args['title'] ?? 'Yêu cầu hỗ trợ từ khách thuê';
        $description = $args['description'] ?? '';
        $priorityCode = $args['priority'] ?? 'medium';

        // Lấy hợp đồng active gần nhất của user trong tổ chức này
        $lease = Lease::where('tenant_id', $user->id)
            ->where('organization_id', $orgId)
            ->whereIn('status', ['active'])
            ->first();

        if (!$lease) {
            return ['success' => false, 'error' => 'Bạn không có hợp đồng thuê nào đang hoạt động để tạo ticket.'];
        }

        $priorityId = \App\Models\TicketPriority::where('key_code', $priorityCode)->value('id');

        $ticket = Ticket::create([
            'organization_id' => $orgId,
            'property_id' => $lease->unit->property_id,
            'unit_id' => $lease->unit_id,
            'lease_id' => $lease->id,
            'created_by' => $user->id,
            'title' => $title,
            'description' => $description,
            'status' => 'open',
            'priority_id' => $priorityId,
        ]);

        return [
            'success' => true,
            'message' => 'Đã tạo ticket thành công.',
            'ticket_id' => $ticket->id,
            'ticket_title' => $ticket->title,
        ];
    }
}
