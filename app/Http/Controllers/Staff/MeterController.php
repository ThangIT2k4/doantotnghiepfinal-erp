<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\Unit;
use App\Models\Service;
use App\Models\Lease;
use App\Services\MeterBillingService;
use App\Traits\ChecksCapabilities;
use App\Traits\FiltersByOwnership;
use App\Traits\NotificationTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MeterController extends Controller
{
    use ChecksCapabilities, FiltersByOwnership, NotificationTrait;

    /**
     * Display a listing of meters
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check if user has asset.access capability
        $hasAssetAccess = $this->checkCapability('asset.access');
        if (!$hasAssetAccess) {
            abort(403, 'Bạn không có quyền truy cập module Asset.');
        }
        
        // Check capability - manager can view all, agent can only view meters of assigned properties
        $this->requireCapability('asset.meter.view', 'Bạn không có quyền xem Meters.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Check if user can view all meters or only meters of assigned properties
        // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
        $canViewAll = $this->canViewAll('asset.meter');
        
        // Build base query for statistics (before filters)
        if ($canViewAll) {
            $statsQuery = Meter::whereHas('property', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            })->whereNull('deleted_at');
        } else {
            // Agent sees only meters of assigned properties
            $assignedPropertyIds = $user->assignedProperties()->pluck('id')->toArray();
            if (empty($assignedPropertyIds)) {
                $statsQuery = Meter::whereRaw('1 = 0'); // No results
            } else {
                $statsQuery = Meter::whereHas('property', function($q) use ($organizationId, $assignedPropertyIds) {
                    $q->where('organization_id', $organizationId)
                      ->whereIn('id', $assignedPropertyIds);
                })->whereNull('deleted_at');
            }
        }
        
        // Calculate statistics from base query
        $stats = [
            'total' => (int) (clone $statsQuery)->count(),
            'active' => (int) (clone $statsQuery)->where('status', true)->count(),
            'inactive' => (int) (clone $statsQuery)->where('status', false)->count(),
        ];
        
        // Build query for filtered results - manager sees all, agent sees only meters of assigned properties
        // Check if we need joins for sorting
        $sortBy = $request->get('sort_by', 'id');
        $needsJoin = in_array($sortBy, ['property', 'unit', 'service']);
        
        if ($canViewAll) {
            if ($needsJoin) {
                // Use joins when sorting by related table columns
                $query = Meter::select([
                        'meters.*',
                        'properties.name as property_name',
                        'units.code as unit_code',
                        'services.name as service_name'
                    ])
                    ->leftJoin('properties', 'meters.property_id', '=', 'properties.id')
                    ->leftJoin('units', 'meters.unit_id', '=', 'units.id')
                    ->leftJoin('services', 'meters.service_id', '=', 'services.id')
                    ->where('properties.organization_id', $organizationId)
                    ->whereNull('meters.deleted_at')
                    ->whereNull('properties.deleted_at');
            } else {
                // Use eager loading when not sorting by related columns
                $query = Meter::with(['property', 'unit', 'service', 'readings' => function($q) {
                    $q->latest('reading_date')->limit(1);
                }])
                ->whereHas('property', function($q) use ($organizationId) {
                    $q->where('organization_id', $organizationId);
                })
                ->whereNull('deleted_at');
            }
        } else {
            // Agent sees only meters of assigned properties
            $assignedPropertyIds = $user->assignedProperties()->pluck('id')->toArray();
            
            $query = Meter::select([
                    'meters.*',
                    'properties.name as property_name',
                    'units.code as unit_code',
                    'services.name as service_name'
                ])
                ->leftJoin('properties', 'meters.property_id', '=', 'properties.id')
                ->leftJoin('units', 'meters.unit_id', '=', 'units.id')
                ->leftJoin('services', 'meters.service_id', '=', 'services.id')
                ->where('properties.organization_id', $organizationId)
                ->whereIn('meters.property_id', $assignedPropertyIds)
                ->whereNull('meters.deleted_at')
                ->whereNull('properties.deleted_at')
                ->whereNull('units.deleted_at');
        }

        // Filter by property
        if ($request->filled('property_id')) {
            $query->where('property_id', $request->property_id);
        }

        // Filter by unit
        if ($request->filled('unit_id')) {
            $query->where('unit_id', $request->unit_id);
        }

        // Filter by service
        if ($request->filled('service_id')) {
            $query->where('service_id', $request->service_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            // Handle both '1'/'0' and 'active'/'inactive' formats
            if ($request->status === '1' || $request->status === 'active') {
                $query->where('status', true);
            } elseif ($request->status === '0' || $request->status === 'inactive') {
                $query->where('status', false);
            }
        }

        // Search by serial number
        if ($request->filled('search')) {
            $query->where('serial_no', 'like', '%' . $request->search . '%');
        }

        // Filter by deletion status
        if ($request->filled('deleted')) {
            if ($request->deleted === 'only') {
                $query->onlyTrashed();
            } elseif ($request->deleted === 'with') {
                $query->withTrashed();
            }
        } else {
            if (!$canViewAll) {
                $query->whereNull('meters.deleted_at');
            }
        }

        // Sort (sortBy already retrieved above for needsJoin check)
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Map sort fields to actual database columns
        $sortableFields = [
            'id' => 'meters.id',
            'serial_no' => 'meters.serial_no',
            'property' => 'properties.name',
            'unit' => 'units.code',
            'service' => 'services.name',
            'status' => 'meters.status',
            'created_at' => 'meters.created_at',
            'updated_at' => 'meters.updated_at',
        ];
        
        if (isset($sortableFields[$sortBy])) {
            $query->orderBy($sortableFields[$sortBy], $sortOrder);
        } else {
            $query->orderBy('meters.id', 'desc');
        }

        $meters = $query->paginate(15);
        
        // Eager load relationships for display
        if ($canViewAll && !$needsJoin) {
            // Relationships already loaded via with() for manager when not using joins
        } else {
            // Load relationships when using joins or for agent
            $meters->load(['property', 'unit', 'service', 'deletedBy', 'readings' => function($q) {
                $q->latest('reading_date')->limit(1);
            }]);
        }

        // Get filter options
        $properties = Property::select('id', 'name')->get();
        // Get services available for this organization (organization-specific + global)
        $services = Service::forOrganization($organizationId)
            ->select('id', 'name', 'key_code')
            ->get();

        // Handle HTMX request
        $isHtmx = $request->header('HX-Request') === 'true';
        
        if ($isHtmx) {
            try {
                // Render table content
                $tableHtml = view('staff.asset.meters.partials.table', [
                    'meters' => $meters,
                    'sortBy' => $sortBy,
                    'sortOrder' => $sortOrder
                ])->render();
                
                // Format stats for response
                $statsFormatted = [
                    'total' => [
                        'value' => $stats['total'] ?? 0,
                        'label' => 'Tổng cộng',
                        'icon' => 'fa-list',
                        'color' => 'primary',
                        'filter' => '',
                    ],
                    'active' => [
                        'value' => $stats['active'] ?? 0,
                        'label' => 'Hoạt động',
                        'icon' => 'fa-check-circle',
                        'color' => 'success',
                        'filter' => '1',
                    ],
                    'inactive' => [
                        'value' => $stats['inactive'] ?? 0,
                        'label' => 'Ngừng hoạt động',
                        'icon' => 'fa-pause-circle',
                        'color' => 'warning',
                        'filter' => '0',
                    ],
                ];
                
                $statsHtml = view('staff.components.statistics-cards', [
                    'stats' => $statsFormatted,
                    'currentFilter' => request('status', ''),
                    'filterKey' => 'status',
                    'onFilterClick' => 'htmx-filter',
                    'onClearClick' => 'htmx-clear',
                    'tableContainerId' => 'meters-table-container',
                    'action' => route('staff.meters.index'),
                    'columns' => 3
                ])->render();
                
                // Extract inner HTML from tableHtml (remove the outer wrapper div if exists)
                $innerTableHtml = $tableHtml;
                
                // Try to extract using DOMDocument for better HTML parsing
                if (class_exists('DOMDocument')) {
                    libxml_use_internal_errors(true);
                    $dom = new \DOMDocument();
                    $dom->loadHTML('<?xml encoding="UTF-8">' . $tableHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                    $xpath = new \DOMXPath($dom);
                    $container = $xpath->query('//div[@id="meters-table-container"]')->item(0);
                    if ($container) {
                        $innerHtml = '';
                        foreach ($container->childNodes as $child) {
                            $innerHtml .= $dom->saveHTML($child);
                        }
                        $innerTableHtml = trim($innerHtml);
                    }
                    libxml_clear_errors();
                }
                
                // Fallback to regex if DOMDocument didn't work
                if ($innerTableHtml === $tableHtml) {
                    // Match the opening div with id="meters-table-container" and extract everything inside
                    if (preg_match('/<div[^>]*id=["\']meters-table-container["\'][^>]*>(.*)<\/div>\s*$/s', $tableHtml, $matches)) {
                        $innerTableHtml = trim($matches[1]);
                    }
                }
                
                // Return inner HTML with stats update via hx-swap-oob
                $html = $innerTableHtml . "\n<div id='stats-container' hx-swap-oob='true'>" . $statsHtml . "</div>";
                
                return response($html)
                    ->header('HX-Push-Url', $request->fullUrl());
            } catch (\Exception $e) {
                Log::error('MeterController HTMX Error: ' . $e->getMessage());
                return response('<div class="alert alert-danger">Có lỗi xảy ra khi tải dữ liệu: ' . $e->getMessage() . '</div>', 500);
            }
        }

        // Thêm biến $isManager để tương thích với view
        $isManager = $canViewAll;

        return view('staff.asset.meters.index', compact('meters', 'properties', 'services', 'isManager', 'stats', 'sortBy', 'sortOrder'));
    }

    /**
     * Show the form for creating a new meter
     */
    public function create(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('asset.meter.create', 'Bạn không có quyền tạo Meters.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Check if user can view all meters or only meters of assigned properties
        // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
        $canViewAll = $this->canViewAll('asset.meter');
        
        // Manager can create for any property, agent can only create for assigned properties
        if ($canViewAll) {
            $properties = Property::where('organization_id', $organizationId)->select('id', 'name')->get();
        } else {
            $assignedPropertyIds = $user->assignedProperties()->pluck('id')->toArray();
            $properties = Property::where('organization_id', $organizationId)
                ->whereIn('id', $assignedPropertyIds)
                ->select('id', 'name')
                ->get();
        }
        
        // Get services available for this organization (organization-specific + global)
        $services = Service::forOrganization($organizationId)
            ->select('id', 'name', 'key_code', 'unit_label')
            ->get();
        
        $selectedProperty = null;
        $selectedUnit = null;
        $units = collect();
        
        // Handle property_id from query parameter
        if ($request->filled('property_id')) {
            $propertyId = $request->property_id;
            $selectedProperty = Property::find($propertyId);
            if ($selectedProperty && ($canViewAll || in_array($selectedProperty->id, $assignedPropertyIds ?? []))) {
                $units = Unit::where('property_id', $propertyId)
                    ->select('id', 'code', 'unit_type')
                    ->get();
            }
        }
        
        // Handle unit_id from query parameter
        if ($request->filled('unit_id')) {
            $unitId = $request->unit_id;
            $selectedUnit = Unit::find($unitId);
            if ($selectedUnit) {
                // If property_id not provided, get it from unit
                if (!$selectedProperty) {
                    $selectedProperty = $selectedUnit->property;
                    if ($selectedProperty && ($canViewAll || in_array($selectedProperty->id, $assignedPropertyIds ?? []))) {
                        $units = Unit::where('property_id', $selectedProperty->id)
                            ->select('id', 'code', 'unit_type')
                            ->get();
                    }
                }
                // Verify unit belongs to selected property
                if ($selectedProperty && $selectedUnit->property_id == $selectedProperty->id) {
                    // Unit is valid
                } else {
                    $selectedUnit = null; // Invalid unit for this property
                }
            }
        }

        // Thêm biến $isManager để tương thích với view
        $isManager = $canViewAll;

        return view('staff.asset.meters.create', compact('properties', 'services', 'selectedProperty', 'selectedUnit', 'units', 'isManager'));
    }

    /**
     * Store a newly created meter
     */
    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('asset.meter.create', 'Bạn không có quyền tạo Meters.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Check if user can view all meters or only meters of assigned properties
        // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
        $canViewAll = $this->canViewAll('asset.meter');
        
        // Validation rules differ for manager vs agent
        $validationRules = [
            'property_id' => 'required|exists:properties,id',
            'unit_id' => 'required|exists:units,id',
            'service_id' => 'required|exists:services,id',
            'installed_at' => 'required|date',
            'status' => 'boolean',
        ];
        
        if ($canViewAll) {
            $validationRules['serial_no'] = 'required|string|max:255|unique:meters,serial_no';
        } else {
            $validationRules['serial_no'] = 'required|string|max:255';
        }
        
        $request->validate($validationRules);
        
        // For agent, ensure property is assigned to them
        if (!$canViewAll) {
            $assignedPropertyIds = $user->assignedProperties()->pluck('id')->toArray();
            if (!in_array($request->property_id, $assignedPropertyIds)) {
                abort(403, 'Bạn không có quyền tạo meter cho property này.');
            }
        }

        try {
            DB::beginTransaction();

            $meter = Meter::create([
                'property_id' => $request->property_id,
                'unit_id' => $request->unit_id,
                'service_id' => $request->service_id,
                'serial_no' => $request->serial_no,
                'installed_at' => $request->installed_at,
                'status' => $request->boolean('status', true),
            ]);

            DB::commit();

            return $this->jsonResponse(
                true,
                'Công tơ đo đã được tạo thành công!',
                'Tạo công tơ thành công',
                [],
                route('staff.meters.show', $meter->id)
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Có lỗi xảy ra khi tạo công tơ đo');
        }
    }

    /**
     * Display the specified meter
     */
    public function show(Meter $meter)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check if user has asset.access capability
        $hasAssetAccess = $this->checkCapability('asset.access');
        if (!$hasAssetAccess) {
            abort(403, 'Bạn không có quyền truy cập module Asset.');
        }
        
        // Check capability
        $this->requireCapability('asset.meter.view', 'Bạn không có quyền xem Meters.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Load property relationship to check organization
        $meter->load('property');
        
        // Check if meter's property belongs to organization
        if (!$meter->property) {
            abort(403, 'Meter không có property liên kết.');
        }
        
        $this->checkOrganizationAccess(
            $meter->property->organization_id,
            'Unauthorized access to meter.',
            'meter',
            $meter->id
        );
        
        // Check if user can view all meters or only meters of assigned properties
        // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
        $canViewAll = $this->canViewAll('asset.meter');
        
        // For agent, only allow viewing meters of assigned properties
        if (!$canViewAll) {
            $assignedPropertyIds = $user->assignedProperties()->pluck('id')->toArray();
            if (!in_array($meter->property_id, $assignedPropertyIds)) {
                abort(403, 'Bạn không có quyền xem meter này.');
            }
        }

        $meter->load(['unit', 'service', 'deletedBy', 'readings' => function($q) {
            $q->with('takenBy')->latest('reading_date');
        }]);

        // Get current lease for this unit
        $currentLease = Lease::where('unit_id', $meter->unit_id)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->with(['tenant'])
            ->first();

        // Get billing history for this meter
        $billingHistory = $this->getBillingHistory($meter);

        // Get meter statistics (only for manager)
        $statistics = null;
        if ($canViewAll) {
            $statistics = $this->getMeterStatistics($meter);
        }

        // Thêm biến $isManager để tương thích với view
        $isManager = $canViewAll;

        return view('staff.asset.meters.show', compact('meter', 'currentLease', 'billingHistory', 'statistics', 'isManager'));
    }

    /**
     * Show the form for editing the specified meter
     */
    public function edit(Meter $meter)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('asset.meter.update', 'Bạn không có quyền chỉnh sửa Meters.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Load property relationship to check organization
        $meter->load('property');
        
        // Check if meter's property belongs to organization
        if (!$meter->property) {
            abort(403, 'Meter không có property liên kết.');
        }
        
        $this->checkOrganizationAccess(
            $meter->property->organization_id,
            'Unauthorized access to meter.',
            'meter',
            $meter->id
        );
        
        // Check if user can view all meters or only meters of assigned properties
        // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
        $canViewAll = $this->canViewAll('asset.meter');
        
        // For agent, only allow editing meters of assigned properties
        if (!$canViewAll) {
            $assignedPropertyIds = $user->assignedProperties()->pluck('id')->toArray();
            if (!in_array($meter->property_id, $assignedPropertyIds)) {
                abort(403, 'Bạn không có quyền chỉnh sửa meter này.');
            }
        }

        // Check if property is active (status = 1) - block edit
        if ($meter->property->status == 1) {
            return redirect()->route('staff.meters.show', $meter->id)
                ->with('warning', 'Không thể chỉnh sửa công tơ đo của bất động sản đang ở trạng thái hoạt động. Vui lòng chuyển bất động sản về trạng thái nháp (Tạm ngưng) từ trang chi tiết trước khi chỉnh sửa.');
        }

        $meter->load(['unit', 'service']);
        
        // Manager can edit for any property, agent can only edit for assigned properties
        if ($canViewAll) {
            $properties = Property::where('organization_id', $organizationId)->select('id', 'name')->get();
        } else {
            $assignedPropertyIds = $user->assignedProperties()->pluck('id')->toArray();
            $properties = Property::where('organization_id', $organizationId)
                ->whereIn('id', $assignedPropertyIds)
                ->select('id', 'name')
                ->get();
        }
        
        // Get services available for this organization (organization-specific + global)
        $services = Service::forOrganization($organizationId)
            ->select('id', 'name', 'key_code', 'unit_label')
            ->get();
        $units = Unit::where('property_id', $meter->property_id)
            ->select('id', 'code', 'unit_type')
            ->get();

        // Thêm biến $isManager để tương thích với view
        $isManager = $canViewAll;

        return view('staff.asset.meters.edit', compact('meter', 'properties', 'services', 'units', 'isManager'));
    }

    /**
     * Update the specified meter
     */
    public function update(Request $request, Meter $meter)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('asset.meter.update', 'Bạn không có quyền cập nhật Meters.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Load property relationship to check organization
        $meter->load('property');
        
        // Check if meter's property belongs to organization
        if (!$meter->property) {
            abort(403, 'Meter không có property liên kết.');
        }
        
        $this->checkOrganizationAccess(
            $meter->property->organization_id,
            'Unauthorized access to meter.',
            'meter',
            $meter->id
        );
        
        // Check if user can view all meters or only meters of assigned properties
        // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
        $canViewAll = $this->canViewAll('asset.meter');
        
        // For agent, only allow updating meters of assigned properties
        if (!$canViewAll) {
            $assignedPropertyIds = $user->assignedProperties()->pluck('id')->toArray();
            if (!in_array($meter->property_id, $assignedPropertyIds)) {
                abort(403, 'Bạn không có quyền cập nhật meter này.');
            }
            
            // Ensure new property is also assigned to them
            if ($request->filled('property_id') && $request->property_id !== $meter->property_id) {
                if (!in_array($request->property_id, $assignedPropertyIds)) {
                    abort(403, 'Bạn không có quyền chuyển meter sang property này.');
                }
            }
        }

        // Check if property is active (status = 1) - block all updates
        if ($meter->property->status == 1) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể cập nhật công tơ đo của bất động sản đang ở trạng thái hoạt động. Vui lòng chuyển bất động sản về trạng thái nháp (Tạm ngưng) từ trang chi tiết trước khi chỉnh sửa.'
            ], 422);
        }

        // Validation rules differ for manager vs agent
        $validationRules = [
            'property_id' => 'required|exists:properties,id',
            'unit_id' => 'required|exists:units,id',
            'service_id' => 'required|exists:services,id',
            'installed_at' => 'required|date',
            'status' => 'boolean',
        ];
        
        if ($canViewAll) {
            $validationRules['serial_no'] = 'required|string|max:255|unique:meters,serial_no,' . $meter->id;
        } else {
            $validationRules['serial_no'] = 'required|string|max:255';
        }
        
        $request->validate($validationRules);

        try {
            DB::beginTransaction();

            $meter->update([
                'property_id' => $request->property_id,
                'unit_id' => $request->unit_id,
                'service_id' => $request->service_id,
                'serial_no' => $request->serial_no,
                'installed_at' => $request->installed_at,
                'status' => $request->boolean('status', true),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Công tơ đo đã được cập nhật thành công!',
                'redirect' => route('staff.meters.show', $meter->id),
                'meter' => $meter->load(['property', 'unit', 'service'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Có lỗi xảy ra khi cập nhật công tơ đo');
        }
    }

    /**
     * Remove the specified meter
     */
    public function destroy(Meter $meter)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('asset.meter.delete', 'Bạn không có quyền xóa Meters.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Load property relationship to check organization
        $meter->load('property');
        
        // Check if meter's property belongs to organization
        if (!$meter->property) {
            abort(403, 'Meter không có property liên kết.');
        }
        
        $this->checkOrganizationAccess(
            $meter->property->organization_id,
            'Unauthorized access to meter.',
            'meter',
            $meter->id
        );
        
        // Check if user can view all meters or only meters of assigned properties
        // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
        $canViewAll = $this->canViewAll('asset.meter');
        
        // For agent, only allow deleting meters of assigned properties
        if (!$canViewAll) {
            $assignedPropertyIds = $user->assignedProperties()->pluck('id')->toArray();
            if (!in_array($meter->property_id, $assignedPropertyIds)) {
                abort(403, 'Bạn không có quyền xóa meter này.');
            }
        }

        try {
            DB::beginTransaction();

            // Check if meter has readings
            if ($meter->readings()->count() > 0) {
                return $this->notifyError(
                    'Không thể xóa công tơ đo đã có số liệu đo. Vui lòng xóa tất cả số liệu trước.',
                    'Không thể xóa'
                );
            }

            // Soft delete with user tracking
            $meter->update([
                'deleted_by' => $user->id
            ]);
            $meter->delete();

            DB::commit();

            return $this->notifySuccess(
                'Công tơ đo đã được xóa thành công!',
                'Xóa công tơ thành công'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Có lỗi xảy ra khi xóa công tơ đo');
        }
    }

    /**
     * Restore a soft deleted meter
     */
    public function restore($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('asset.meter.update', 'Bạn không có quyền khôi phục Meters.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        try {
            DB::beginTransaction();
            
            $meter = Meter::withTrashed()->with('property')->findOrFail($id);
            
            // Check if meter's property belongs to organization
            if (!$meter->property || (int) $meter->property->organization_id !== (int) $organizationId) {
                abort(403, 'Unauthorized access to meter.');
            }
            
            // Check if user can view all meters or only meters of assigned properties
            $canViewAll = $this->canViewAll('asset.meter');
            
            // For agent, only allow restoring meters of assigned properties
            if (!$canViewAll) {
                $assignedPropertyIds = $user->assignedProperties()->pluck('id')->toArray();
                if (!in_array($meter->property_id, $assignedPropertyIds)) {
                    abort(403, 'Bạn không có quyền khôi phục meter này.');
                }
            }
            
            if (!$meter->trashed()) {
                return $this->notifyError(
                    'Công tơ đo không ở trạng thái đã xóa.',
                    'Không thể khôi phục'
                );
            }

            $meter->restore();
            $meter->update(['deleted_by' => null]);

            DB::commit();

            return $this->notifySuccess(
                'Công tơ đo đã được khôi phục thành công!',
                'Khôi phục công tơ thành công'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Có lỗi xảy ra khi khôi phục công tơ đo');
        }
    }

    /**
     * Permanently delete a meter
     */
    public function forceDelete($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can force delete
        $this->requireCapability('asset.meter.delete', 'Bạn không có quyền xóa vĩnh viễn Meters.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        try {
            DB::beginTransaction();

            $meter = Meter::withTrashed()->with('property')->findOrFail($id);
            
            // Check if meter's property belongs to organization
            if (!$meter->property || (int) $meter->property->organization_id !== (int) $organizationId) {
                abort(403, 'Unauthorized access to meter.');
            }
            
            if (!$meter->trashed()) {
                return $this->notifyError(
                    'Công tơ đo không ở trạng thái đã xóa.',
                    'Không thể xóa vĩnh viễn'
                );
            }

            // Check if meter has readings
            if ($meter->readings()->count() > 0) {
                return $this->notifyError(
                    'Không thể xóa vĩnh viễn công tơ đo đã có số liệu đo.',
                    'Không thể xóa vĩnh viễn'
                );
            }

            $meter->forceDelete();

            DB::commit();

            return $this->notifySuccess(
                'Công tơ đo đã được xóa vĩnh viễn thành công!',
                'Xóa vĩnh viễn thành công'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Có lỗi xảy ra khi xóa vĩnh viễn công tơ đo');
        }
    }

    /**
     * Update meter status.
     */
    public function updateStatus(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        if (!$this->checkCapability('asset.meter.update')) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền cập nhật trạng thái công tơ đo.'
            ], 403);
        }
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thuộc tổ chức nào.'
            ], 403);
        }

        $meter = Meter::whereHas('property', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            })
            ->findOrFail($id);

        // For agent, check if meter's property is assigned to them
        $canViewAll = $this->canViewAll('asset.meter');
        if (!$canViewAll) {
            /** @var \App\Models\User $currentUser */
            $currentUser = Auth::user();
            $assignedPropertyIds = $currentUser->assignedProperties()->pluck('properties.id');
            if (!$assignedPropertyIds->contains($meter->property_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền cập nhật trạng thái công tơ đo này.'
                ], 403);
            }
        }

        $request->validate([
            'status' => 'required|boolean'
        ]);

        try {
            DB::beginTransaction();
            
            $oldStatus = $meter->status;
            $meter->status = $request->boolean('status');
            $meter->save();

            DB::commit();

            $statusLabels = [
                true => 'Hoạt động',
                false => 'Ngừng hoạt động'
            ];

            return response()->json([
                'success' => true,
                'message' => "Trạng thái công tơ đo đã được chuyển từ '{$statusLabels[$oldStatus]}' sang '{$statusLabels[$meter->status]}'.",
                'meter' => $meter->load(['property', 'unit', 'service'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật trạng thái công tơ đo.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get units for a property (AJAX)
     */
    public function getUnits(Request $request)
    {
        try {
            $propertyId = $request->property_id;
            
            if (!$propertyId) {
                return response()->json(['units' => []]);
            }

            $units = Unit::where('property_id', $propertyId)
                ->select('id', 'code', 'unit_type')
                ->get();

            return response()->json(['units' => $units]);

        } catch (\Exception $e) {
            Log::error('Error loading units for property: ' . $e->getMessage(), [
                'property_id' => $request->property_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Có lỗi xảy ra khi tải danh sách phòng',
                'units' => []
            ], 500);
        }
    }

    /**
     * Get meter statistics (only for manager)
     */
    public function statistics(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check if user has asset.access capability
        $hasAssetAccess = $this->checkCapability('asset.access');
        if (!$hasAssetAccess) {
            abort(403, 'Bạn không có quyền truy cập module Asset.');
        }
        
        // Check capability - only manager can view statistics
        $this->requireCapability('asset.meter.view', 'Bạn không có quyền xem statistics.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }

        $query = Meter::with(['property', 'unit', 'service'])
            ->whereHas('property', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            });

        // Filter by property
        if ($request->filled('property_id')) {
            $query->where('property_id', $request->property_id);
        }

        $meters = $query->get();

        $statistics = [
            'total_meters' => $meters->count(),
            'active_meters' => $meters->where('status', true)->count(),
            'inactive_meters' => $meters->where('status', false)->count(),
            'meters_with_readings' => $meters->filter(function($meter) {
                return $meter->readings()->count() > 0;
            })->count(),
            'meters_without_readings' => $meters->filter(function($meter) {
                return $meter->readings()->count() == 0;
            })->count(),
            'by_service' => $meters->groupBy('service.name')->map(function($group) {
                return $group->count();
            }),
            'by_property' => $meters->groupBy('property.name')->map(function($group) {
                return $group->count();
            })
        ];

        return view('staff.asset.meters.statistics', compact('statistics'));
    }

    /**
     * Get billing history for a meter
     */
    private function getBillingHistory(Meter $meter)
    {
        $billingService = new MeterBillingService();
        return $billingService->getBillingHistory($meter->id);
    }

    /**
     * Get meter statistics
     */
    private function getMeterStatistics(Meter $meter)
    {
        $readings = $meter->readings()->orderBy('reading_date')->get();
        
        if ($readings->count() < 2) {
            return [
                'total_readings' => $readings->count(),
                'average_usage' => 0,
                'last_reading' => $readings->last(),
                'usage_trend' => 'insufficient_data'
            ];
        }

        $totalUsage = 0;
        $usageCount = 0;
        
        for ($i = 1; $i < $readings->count(); $i++) {
            $usage = $readings[$i]->value - $readings[$i-1]->value;
            if ($usage > 0) {
                $totalUsage += $usage;
                $usageCount++;
            }
        }

        $averageUsage = $usageCount > 0 ? $totalUsage / $usageCount : 0;

        // Calculate trend
        $recentUsage = [];
        for ($i = max(0, $readings->count() - 4); $i < $readings->count() - 1; $i++) {
            $usage = $readings[$i + 1]->value - $readings[$i]->value;
            if ($usage > 0) {
                $recentUsage[] = $usage;
            }
        }

        $trend = 'stable';
        if (count($recentUsage) >= 2) {
            $avgRecent = array_sum($recentUsage) / count($recentUsage);
            $avgOlder = $averageUsage;
            
            if ($avgRecent > $avgOlder * 1.1) {
                $trend = 'increasing';
            } elseif ($avgRecent < $avgOlder * 0.9) {
                $trend = 'decreasing';
            }
        }

        return [
            'total_readings' => $readings->count(),
            'average_usage' => round($averageUsage, 2),
            'last_reading' => $readings->last(),
            'usage_trend' => $trend,
            'recent_usage' => $recentUsage
        ];
    }
}
