<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Lease;
use App\Models\OrganizationUser;
use App\Models\Property;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
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
                    'metric' => ['required' => false, 'description' => "Metric muốn lấy: 'revenue' (mặc định), 'invoices', 'payments'"],
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
                    'properties' => $properties,
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

            return ['success' => false, 'error' => 'Metric không hỗ trợ: ' . $metric];
        } catch (Exception $e) {
            Log::error('getOrgStats error', ['err' => $e->getMessage(), 'args' => $args]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
