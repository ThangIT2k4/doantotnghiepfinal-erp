<?php

namespace App\Http\Controllers\Staff;

use App\Helpers\ErrorHelper;
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
use App\Services\ImageService;
use Carbon\Carbon;

class MeterReadingController extends Controller
{
    use ChecksCapabilities, FiltersByOwnership, NotificationTrait;

    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Display a listing of meter readings
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
        
        // Check capability - manager can view all, agent can only view readings of assigned properties
        $this->requireCapability('asset.meter_reading.view', 'Bạn không có quyền xem Meter Readings.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Check if user can view all meter readings or only readings of assigned properties
        // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
        $canViewAll = $this->canViewAll('asset.meter_reading');
        
        // Build base query for statistics (before filters)
        if ($canViewAll) {
            $statsQuery = MeterReading::whereHas('meter.property', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            });
        } else {
            // Agent sees only readings of meters of assigned properties
            $assignedPropertyIds = $user->assignedProperties()->pluck('id')->toArray();
            if (empty($assignedPropertyIds)) {
                $statsQuery = MeterReading::whereRaw('1 = 0'); // No results
            } else {
                $statsQuery = MeterReading::whereHas('meter.property', function($q) use ($organizationId, $assignedPropertyIds) {
                    $q->where('organization_id', $organizationId)
                      ->whereIn('id', $assignedPropertyIds);
                });
            }
        }
        
        // Get services available for this organization (organization-specific + global)
        $allServices = Service::forOrganization($organizationId)
            ->select('id', 'name', 'key_code')
            ->get();
        
        // Calculate statistics from base query
        $stats = [
            'total' => (int) (clone $statsQuery)->count(),
        ];
        
        // Count by service
        foreach ($allServices as $service) {
            $serviceCount = (clone $statsQuery)->whereHas('meter', function($q) use ($service) {
                $q->where('service_id', $service->id);
            })->count();
            $stats['service_' . $service->id] = (int) $serviceCount;
        }
        
        // Build query for filtered results - manager sees all, agent sees only readings of meters of assigned properties
        if ($canViewAll) {
            $query = MeterReading::with(['meter.property', 'meter.unit', 'meter.service', 'takenBy'])
                ->whereHas('meter.property', function($q) use ($organizationId) {
                    $q->where('organization_id', $organizationId);
                });
        } else {
            // Agent sees only readings of meters of assigned properties
            $assignedPropertyIds = $user->assignedProperties()->pluck('id')->toArray();
            
            $query = MeterReading::select([
                    'meter_readings.*',
                    'meters.serial_no as meter_serial_no',
                    'properties.name as property_name',
                    'units.code as unit_code',
                    'services.name as service_name'
                ])
                ->join('meters', 'meter_readings.meter_id', '=', 'meters.id')
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
            if ($canViewAll) {
                $query->whereHas('meter', function($q) use ($request) {
                    $q->where('property_id', $request->property_id);
                });
            } else {
                $query->where('properties.id', $request->property_id);
            }
        }

        // Filter by unit
        if ($request->filled('unit_id') && $canViewAll) {
            $query->whereHas('meter', function($q) use ($request) {
                $q->where('unit_id', $request->unit_id);
            });
        }

        // Filter by service
        if ($request->filled('service_id')) {
            if ($canViewAll) {
                $query->whereHas('meter', function($q) use ($request) {
                    $q->where('service_id', $request->service_id);
                });
            } else {
                $query->where('services.id', $request->service_id);
            }
        }

        // Filter by meter
        if ($request->filled('meter_id')) {
            if ($canViewAll) {
                $query->where('meter_id', $request->meter_id);
            } else {
                $query->where('meter_readings.meter_id', $request->meter_id);
            }
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('reading_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('reading_date', '<=', $request->date_to);
        }

        // Filter by taken by
        if ($request->filled('taken_by') && $canViewAll) {
            $query->where('taken_by', $request->taken_by);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'reading_date');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Map sort fields to actual database columns
        $sortableFields = [
            'id' => 'meter_readings.id',
            'reading_date' => 'meter_readings.reading_date',
            'value' => 'meter_readings.value',
            'meter' => 'meters.serial_no',
            'property' => 'properties.name',
            'unit' => 'units.code',
            'service' => 'services.name',
            'created_at' => 'meter_readings.created_at',
            'updated_at' => 'meter_readings.updated_at',
        ];
        
        if (isset($sortableFields[$sortBy])) {
            $query->orderBy($sortableFields[$sortBy], $sortOrder);
        } else {
            $query->orderBy('meter_readings.reading_date', 'desc');
        }

        $readings = $query->paginate($canViewAll ? 15 : 20);
        
        // Eager load relationships for display (for agent)
        if (!$canViewAll) {
            $readings->load(['meter.property', 'meter.unit', 'meter.service', 'takenBy']);
        }

        // Get filter options
        $properties = Property::where('organization_id', $organizationId)
            ->select('id', 'name')
            ->get();
        // Get services available for this organization (organization-specific + global)
        $services = Service::forOrganization($organizationId)
            ->select('id', 'name', 'key_code')
            ->get();
        
        if ($canViewAll) {
            $meters = Meter::with(['property', 'unit', 'service'])
                ->whereHas('property', function($q) use ($organizationId) {
                    $q->where('organization_id', $organizationId);
                })
                ->get();
        } else {
            $assignedPropertyIds = $user->assignedProperties()->pluck('id')->toArray();
            $meters = Meter::with(['property', 'unit', 'service'])
                ->whereHas('property', function($q) use ($organizationId) {
                    $q->where('organization_id', $organizationId);
                })
                ->whereIn('property_id', $assignedPropertyIds)
                ->select('id', 'property_id', 'unit_id', 'service_id', 'serial_no')
                ->get();
        }

        // Handle HTMX request
        $isHtmx = $request->header('HX-Request') === 'true';
        
        if ($isHtmx) {
            try {
                // Render table content
                $tableHtml = view('staff.asset.meter-readings.partials.table', [
                    'readings' => $readings,
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
                ];
                
                // Add service statistics
                foreach ($allServices as $service) {
                    $serviceKey = 'service_' . $service->id;
                    if (isset($stats[$serviceKey]) && $stats[$serviceKey] > 0) {
                        $statsFormatted[$serviceKey] = [
                            'value' => $stats[$serviceKey] ?? 0,
                            'label' => $service->name,
                            'icon' => 'fa-circle',
                            'color' => 'info',
                            'filter' => (string)$service->id,
                            'filterKey' => 'service_id',
                        ];
                    }
                }
                
                $statsHtml = view('staff.components.statistics-cards', [
                    'stats' => $statsFormatted,
                    'currentFilter' => request('service_id', ''),
                    'filterKey' => 'service_id',
                    'onFilterClick' => 'htmx-filter',
                    'onClearClick' => 'htmx-clear',
                    'tableContainerId' => 'meter-readings-table-container',
                    'action' => route('staff.meter-readings.index'),
                    'columns' => count($statsFormatted) > 4 ? 6 : 4
                ])->render();
                
                // Extract inner HTML from tableHtml (remove the outer wrapper div if exists)
                $innerTableHtml = $tableHtml;
                
                // Try to extract using DOMDocument for better HTML parsing
                if (class_exists('DOMDocument')) {
                    libxml_use_internal_errors(true);
                    $dom = new \DOMDocument();
                    $dom->loadHTML('<?xml encoding="UTF-8">' . $tableHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                    $xpath = new \DOMXPath($dom);
                    $container = $xpath->query('//div[@id="meter-readings-table-container"]')->item(0);
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
                    // Match the opening div with id="meter-readings-table-container" and extract everything inside
                    if (preg_match('/<div[^>]*id=["\']meter-readings-table-container["\'][^>]*>(.*)<\/div>\s*$/s', $tableHtml, $matches)) {
                        $innerTableHtml = trim($matches[1]);
                    }
                }
                
                // Return inner HTML with stats update via hx-swap-oob
                $html = $innerTableHtml . "\n<div id='stats-container' hx-swap-oob='true'>" . $statsHtml . "</div>";
                
                return response($html)
                    ->header('HX-Push-Url', $request->fullUrl());
            } catch (\Exception $e) {
                Log::error('MeterReadingController HTMX Error: ' . $e->getMessage());
                $safeMessage = ErrorHelper::getSafeErrorMessage($e, 'Có lỗi xảy ra khi tải dữ liệu. Vui lòng thử lại sau.');
                return response('<div class="alert alert-danger">' . $safeMessage . '</div>', 500);
            }
        }

        // Thêm biến $isManager để tương thích với view
        $isManager = $canViewAll;

        return view('staff.asset.meter-readings.index', compact('readings', 'properties', 'services', 'meters', 'isManager', 'stats', 'allServices', 'sortBy', 'sortOrder'));
    }

    /**
     * Show the form for creating a new meter reading
     */
    public function create(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('asset.meter_reading.create', 'Bạn không có quyền tạo Meter Readings.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Check if user can view all meter readings or only readings of assigned properties
        // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
        $canViewAll = $this->canViewAll('asset.meter_reading');
        
        // Get properties for filter
        if ($canViewAll) {
            $properties = Property::where('organization_id', $organizationId)
                ->whereNull('deleted_at')
                ->select('id', 'name')
                ->orderBy('name')
                ->get();
        } else {
            $assignedPropertyIds = $user->assignedProperties()->pluck('id')->toArray();
            $properties = Property::where('organization_id', $organizationId)
                ->whereIn('id', $assignedPropertyIds)
                ->whereNull('deleted_at')
                ->select('id', 'name')
                ->orderBy('name')
                ->get();
        }
        
        // Get units for filter (based on selected property)
        $units = collect();
        $selectedProperty = null;
        $selectedUnit = null;
        
        if ($request->filled('property_id')) {
            $propertyId = $request->property_id;
            $selectedProperty = Property::find($propertyId);
            if ($selectedProperty && ($canViewAll || in_array($selectedProperty->id, $assignedPropertyIds ?? []))) {
                $units = Unit::where('property_id', $propertyId)
                    ->whereNull('deleted_at')
                    ->select('id', 'code', 'unit_type')
                    ->orderBy('code')
                    ->get();
            }
        }
        
        if ($request->filled('unit_id')) {
            $unitId = $request->unit_id;
            $selectedUnit = Unit::find($unitId);
            if ($selectedUnit) {
                // If property_id not provided, get it from unit
                if (!$selectedProperty) {
                    $selectedProperty = $selectedUnit->property;
                    if ($selectedProperty && ($canViewAll || in_array($selectedProperty->id, $assignedPropertyIds ?? []))) {
                        $units = Unit::where('property_id', $selectedProperty->id)
                            ->whereNull('deleted_at')
                            ->select('id', 'code', 'unit_type')
                            ->orderBy('code')
                            ->get();
                    }
                }
            }
        }
        
        // Build meters query with filters
        $metersQuery = Meter::with(['property', 'unit', 'service'])
            ->whereHas('property', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            })
            ->where('status', true);
        
        if (!$canViewAll) {
            $assignedPropertyIds = $user->assignedProperties()->pluck('id')->toArray();
            $metersQuery->whereIn('property_id', $assignedPropertyIds);
        }
        
        // Filter by property
        if ($request->filled('property_id')) {
            $metersQuery->where('property_id', $request->property_id);
        }
        
        // Filter by unit
        if ($request->filled('unit_id')) {
            $metersQuery->where('unit_id', $request->unit_id);
        }
        
        $meters = $metersQuery->get();

        $selectedMeter = null;
        $lastReading = null;

        if ($request->filled('meter_id')) {
            $selectedMeter = Meter::with(['property', 'unit', 'service'])->find($request->meter_id);
            
            // For agent, ensure meter belongs to assigned properties
            if (!$canViewAll && $selectedMeter) {
                $assignedPropertyIds = $user->assignedProperties()->pluck('id')->toArray();
                if (!in_array($selectedMeter->property_id, $assignedPropertyIds)) {
                    abort(403, 'Bạn không có quyền tạo reading cho meter này.');
                }
            }
            
            if ($selectedMeter) {
                $lastReading = $selectedMeter->readings()->latest('reading_date')->first();
            }
        }

        // Thêm biến $isManager để tương thích với view
        $isManager = $canViewAll;

        return view('staff.asset.meter-readings.create', compact('meters', 'selectedMeter', 'lastReading', 'isManager', 'properties', 'units', 'selectedProperty', 'selectedUnit'));
    }

    /**
     * Store a newly created meter reading
     */
    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('asset.meter_reading.create', 'Bạn không có quyền tạo Meter Readings.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Check if user can view all meter readings or only readings of assigned properties
        // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
        $canViewAll = $this->canViewAll('asset.meter_reading');
        
        $request->validate([
            'meter_id' => 'required|exists:meters,id',
            'reading_date' => 'required|date',
            'value' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'note' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $meter = Meter::with('property')->findOrFail($request->meter_id);
            
            // Check if meter's property belongs to organization
            if (!$meter->property) {
                abort(403, 'Meter không có property liên kết.');
            }
            
            $this->checkOrganizationAccess(
                $meter->property->organization_id,
                'Meter không thuộc tổ chức của bạn.',
                'meter',
                $meter->id
            );
            
            // For agent, ensure meter belongs to assigned properties
            if (!$canViewAll) {
                $assignedPropertyIds = $user->assignedProperties()->pluck('id')->toArray();
                if (!in_array($meter->property_id, $assignedPropertyIds)) {
                    abort(403, 'Bạn không có quyền tạo reading cho meter này.');
                }
            }

            // Check if reading already exists for this date (Agent version has this check)
            $existingReading = $meter->readings()
                ->whereDate('reading_date', $request->reading_date)
                ->first();

            if ($existingReading) {
                return $this->notifyError(
                    'Đã tồn tại số liệu đo cho ngày này. Vui lòng chọn ngày khác hoặc cập nhật số liệu hiện có.',
                    'Số liệu đã tồn tại'
                );
            }

            // Check if reading value is valid (should be greater than or equal to last reading)
            $lastReading = $meter->readings()
                ->latest('reading_date')
                ->first();

            if ($lastReading && $request->value < $lastReading->value) {
                return $this->notifyError(
                    'Giá trị đo không được nhỏ hơn lần đo trước đó (' . $lastReading->value . ')',
                    'Giá trị đo không hợp lệ'
                );
            }

            // Handle image upload
            $imageUrl = null;
            if ($request->hasFile('image')) {
                try {
                    $uploadedImage = $this->imageService->uploadImage($request->file('image'), 'meter-readings');
                    $imageUrl = $uploadedImage['original'];
                } catch (\Exception $e) {
                    Log::error('Error uploading meter reading image: ' . $e->getMessage());
                    $safeMessage = ErrorHelper::getSafeErrorMessage($e, 'Lỗi khi upload ảnh. Vui lòng thử lại sau.');
                    return $this->jsonResponse(
                        false,
                        $safeMessage,
                        null,
                        500
                    );
                }
            }

            $reading = MeterReading::create([
                'organization_id' => $organizationId,
                'meter_id' => $request->meter_id,
                'reading_date' => $request->reading_date,
                'value' => $request->value,
                'image_url' => $imageUrl,
                'taken_by' => $user->id,
                'note' => $request->note,
            ]);

            // Calculate usage and create billing if needed (Agent version has this)
            if (!$canViewAll) {
                $billingService = new MeterBillingService();
                $billingService->processBilling($reading);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Số liệu đo đã được ghi nhận thành công!',
                'redirect' => route('staff.meter-readings.show', $reading->id),
                'reading' => $reading->load(['meter.property', 'meter.unit', 'meter.service', 'takenBy'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Có lỗi xảy ra khi ghi nhận số liệu đo');
        }
    }

    /**
     * Display the specified meter reading
     */
    public function show(MeterReading $meterReading)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check if user has asset.access capability
        $hasAssetAccess = $this->checkCapability('asset.access');
        if (!$hasAssetAccess) {
            abort(403, 'Bạn không có quyền truy cập module Asset.');
        }
        
        // Check capability
        $this->requireCapability('asset.meter_reading.view', 'Bạn không có quyền xem Meter Readings.');
        
        // Load meter and property relationship to check organization
        $meterReading->load('meter.property');
        
        // Check if reading's meter's property belongs to organization
        if (!$meterReading->meter || !$meterReading->meter->property) {
            abort(403, 'Meter reading không có meter hoặc property liên kết.');
        }
        
        $this->checkOrganizationAccess(
            $meterReading->meter->property->organization_id,
            'Unauthorized access to meter reading.',
            'meter_reading',
            $meterReading->id
        );
        
        
        // Check if user can view all meter readings or only readings of assigned properties
        // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
        $canViewAll = $this->canViewAll('asset.meter_reading');
        
        // For agent, only allow viewing readings of meters of assigned properties
        if (!$canViewAll) {
            $assignedPropertyIds = $user->assignedProperties()->pluck('id')->toArray();
            if (!in_array($meterReading->meter->property_id, $assignedPropertyIds)) {
                abort(403, 'Bạn không có quyền xem reading này.');
            }
        }

        $meterReading->load(['meter.property', 'meter.unit', 'meter.service', 'takenBy']);

        // Get previous reading for comparison
        $previousReading = MeterReading::where('meter_id', $meterReading->meter_id)
            ->where('reading_date', '<', $meterReading->reading_date)
            ->latest('reading_date')
            ->first();

        // Get next reading for comparison
        $nextReading = MeterReading::where('meter_id', $meterReading->meter_id)
            ->where('reading_date', '>', $meterReading->reading_date)
            ->oldest('reading_date')
            ->first();

        // Calculate usage (Agent version has this)
        $usage = $previousReading ? $meterReading->value - $previousReading->value : 0;

        // Get current lease and pricing (Agent version has this)
        $currentLease = null;
        $servicePrice = 0;
        $cost = 0;
        
        if (!$canViewAll) {
            $currentLease = Lease::where('unit_id', $meterReading->meter->unit_id)
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->with('leaseServiceSet.items')
                ->first();

            if ($currentLease) {
                $leaseServiceSet = $currentLease->getEffectiveLeaseServiceSet();
                if ($leaseServiceSet) {
                    $serviceItem = $leaseServiceSet->items()
                        ->where('service_id', $meterReading->meter->service_id)
                        ->first();
                    if ($serviceItem) {
                        $servicePrice = $serviceItem->price;
                    }
                }
            }

            $cost = $usage * $servicePrice;
        }

        // Thêm biến $isManager để tương thích với view
        $isManager = $canViewAll;

        return view('staff.asset.meter-readings.show', compact(
            'meterReading', 
            'previousReading', 
            'nextReading',
            'usage',
            'servicePrice',
            'cost',
            'isManager'
        ));
    }

    /**
     * Show the form for editing the specified meter reading
     */
    public function edit(MeterReading $meterReading)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('asset.meter_reading.update', 'Bạn không có quyền chỉnh sửa Meter Readings.');
        
        // Load meter and property relationship to check organization
        $meterReading->load('meter.property');
        
        // Check if reading's meter's property belongs to organization
        if (!$meterReading->meter || !$meterReading->meter->property) {
            abort(403, 'Meter reading không có meter hoặc property liên kết.');
        }
        
        $this->checkOrganizationAccess(
            $meterReading->meter->property->organization_id,
            'Unauthorized access to meter reading.',
            'meter_reading',
            $meterReading->id
        );
        
        $organizationId = $this->getCurrentOrganizationId();
        
        // Check if user can view all meter readings or only readings of assigned properties
        // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
        $canViewAll = $this->canViewAll('asset.meter_reading');
        
        // For agent, only allow editing readings of meters of assigned properties
        if (!$canViewAll) {
            $assignedPropertyIds = $user->assignedProperties()->pluck('id')->toArray();
            if (!in_array($meterReading->meter->property_id, $assignedPropertyIds)) {
                abort(403, 'Bạn không có quyền chỉnh sửa reading này.');
            }
        }

        $meterReading->load(['meter.property', 'meter.unit', 'meter.service']);
        
        // Manager can edit for any meter, agent can only edit for meters of assigned properties
        $meters = null;
        if ($canViewAll) {
            $meters = Meter::with(['property', 'unit', 'service'])
                ->whereHas('property', function($q) use ($organizationId) {
                    $q->where('organization_id', $organizationId);
                })
                ->where('status', true)
                ->get();
        }

        // Get previous reading for comparison
        $previousReading = MeterReading::where('meter_id', $meterReading->meter_id)
            ->where('reading_date', '<', $meterReading->reading_date)
            ->latest('reading_date')
            ->first();

        // Get next reading for comparison
        $nextReading = MeterReading::where('meter_id', $meterReading->meter_id)
            ->where('reading_date', '>', $meterReading->reading_date)
            ->oldest('reading_date')
            ->first();

        // Thêm biến $isManager để tương thích với view
        $isManager = $canViewAll;

        return view('staff.asset.meter-readings.edit', compact('meterReading', 'meters', 'previousReading', 'nextReading', 'isManager'));
    }

    /**
     * Update the specified meter reading
     */
    public function update(Request $request, MeterReading $meterReading)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('asset.meter_reading.update', 'Bạn không có quyền cập nhật Meter Readings.');
        
        // Load meter and property relationship to check organization
        $meterReading->load('meter.property');
        
        // Check if reading's meter's property belongs to organization
        if (!$meterReading->meter || !$meterReading->meter->property) {
            abort(403, 'Meter reading không có meter hoặc property liên kết.');
        }
        
        $this->checkOrganizationAccess(
            $meterReading->meter->property->organization_id,
            'Unauthorized access to meter reading.',
            'meter_reading',
            $meterReading->id
        );
        
        
        // Check if user can view all meter readings or only readings of assigned properties
        // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
        $canViewAll = $this->canViewAll('asset.meter_reading');
        
        // For agent, only allow updating readings of meters of assigned properties
        if (!$canViewAll) {
            $assignedPropertyIds = $user->assignedProperties()->pluck('id')->toArray();
            if (!in_array($meterReading->meter->property_id, $assignedPropertyIds)) {
                abort(403, 'Bạn không có quyền cập nhật reading này.');
            }
        }

        // Validation rules differ for manager vs agent
        $validationRules = [
            'reading_date' => 'required|date',
            'value' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'note' => 'nullable|string|max:1000',
        ];
        
        if ($canViewAll) {
            $validationRules['meter_id'] = 'required|exists:meters,id';
        }
        
        $request->validate($validationRules);

        try {
            DB::beginTransaction();

            // Check if reading already exists for this date (excluding current reading) - Agent version has this
            if (!$canViewAll) {
                $existingReading = MeterReading::where('meter_id', $meterReading->meter_id)
                    ->whereDate('reading_date', $request->reading_date)
                    ->where('id', '!=', $meterReading->id)
                    ->first();

                if ($existingReading) {
                    return $this->notifyError(
                        'Đã tồn tại số liệu đo cho ngày này. Vui lòng chọn ngày khác.',
                        'Ngày đã tồn tại'
                    );
                }
            }

            // Check if reading value is valid
            $canViewAll = $this->canViewAll('asset.meter_reading');
            $lastReading = MeterReading::where('meter_id', $canViewAll ? $request->meter_id : $meterReading->meter_id)
                ->where('id', '!=', $meterReading->id)
                ->latest('reading_date')
                ->first();

            if ($lastReading && $request->value < $lastReading->value) {
                return $this->notifyError(
                    'Giá trị đo không được nhỏ hơn lần đo trước đó (' . $lastReading->value . ')',
                    'Giá trị đo không hợp lệ'
                );
            }

            $data = [
                'reading_date' => $request->reading_date,
                'value' => $request->value,
                'note' => $request->note,
            ];
            
            if ($canViewAll) {
                $data['meter_id'] = $request->meter_id;
            }

            // Handle image upload
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($meterReading->image_url) {
                    try {
                        $this->imageService->deleteImage($meterReading->image_url);
                    } catch (\Exception $e) {
                        Log::error('Error deleting old meter reading image: ' . $e->getMessage());
                        // Continue with upload even if deletion fails
                    }
                }
                
                try {
                    $uploadedImage = $this->imageService->uploadImage($request->file('image'), 'meter-readings');
                    $data['image_url'] = $uploadedImage['original'];
                } catch (\Exception $e) {
                    Log::error('Error uploading meter reading image: ' . $e->getMessage());
                    $safeMessage = ErrorHelper::getSafeErrorMessage($e, 'Lỗi khi upload ảnh. Vui lòng thử lại sau.');
                    return $this->jsonResponse(
                        false,
                        $safeMessage,
                        null,
                        500
                    );
                }
            }

            $meterReading->update($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Số liệu đo đã được cập nhật thành công!',
                'redirect' => route('staff.meter-readings.show', $meterReading->id),
                'reading' => $meterReading->load(['meter.property', 'meter.unit', 'meter.service', 'takenBy'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Có lỗi xảy ra khi cập nhật số liệu đo');
        }
    }

    /**
     * Remove the specified meter reading
     */
    public function destroy(MeterReading $meterReading)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('asset.meter_reading.delete', 'Bạn không có quyền xóa Meter Readings.');
        
        // Load meter and property relationship to check organization
        $meterReading->load('meter.property');
        
        // Check if reading's meter's property belongs to organization
        if (!$meterReading->meter || !$meterReading->meter->property) {
            abort(403, 'Meter reading không có meter hoặc property liên kết.');
        }
        
        $this->checkOrganizationAccess(
            $meterReading->meter->property->organization_id,
            'Unauthorized access to meter reading.',
            'meter_reading',
            $meterReading->id
        );
        
        
        // Check if user can view all meter readings or only readings of assigned properties
        // Uses FiltersByOwnership trait method which handles: view_all > view_own > view (backward compatibility)
        $canViewAll = $this->canViewAll('asset.meter_reading');
        
        // For agent, only allow deleting readings of meters of assigned properties
        if (!$canViewAll) {
            $assignedPropertyIds = $user->assignedProperties()->pluck('id')->toArray();
            if (!in_array($meterReading->meter->property_id, $assignedPropertyIds)) {
                abort(403, 'Bạn không có quyền xóa reading này.');
            }
        }

        try {
            DB::beginTransaction();

            // Delete image if exists
            if ($meterReading->image_url) {
                try {
                    $this->imageService->deleteImage($meterReading->image_url);
                } catch (\Exception $e) {
                    Log::error('Error deleting meter reading image: ' . $e->getMessage());
                    // Continue with deletion even if image deletion fails
                }
            }

            $meterReading->delete();

            DB::commit();

            return $this->notifySuccess(
                'Số liệu đo đã được xóa thành công!',
                'Xóa số liệu thành công'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Có lỗi xảy ra khi xóa số liệu đo');
        }
    }

    /**
     * Get last reading for a meter (AJAX)
     */
    public function getLastReading(Request $request)
    {
        try {
            $meterId = $request->meter_id;
            
            if (!$meterId) {
                return response()->json(['lastReading' => null]);
            }

            $lastReading = MeterReading::with('takenBy')
                ->where('meter_id', $meterId)
                ->latest('reading_date')
                ->first();

            if (!$lastReading) {
                return response()->json(['lastReading' => null]);
            }

            // Format response to ensure relationship is included
            $response = [
                'id' => $lastReading->id,
                'meter_id' => $lastReading->meter_id,
                'reading_date' => $lastReading->reading_date->format('Y-m-d'),
                'value' => $lastReading->value,
                'note' => $lastReading->note,
                'taken_by' => $lastReading->takenBy ? [
                    'id' => $lastReading->takenBy->id,
                    'name' => $lastReading->takenBy->name,
                ] : null,
            ];

            return response()->json(['lastReading' => $response]);

        } catch (\Exception $e) {
            Log::error('Error loading last reading for meter: ' . $e->getMessage(), [
                'meter_id' => $request->meter_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Có lỗi xảy ra khi tải số liệu đo cuối cùng',
                'lastReading' => null
            ], 500);
        }
    }

    /**
     * Get meter readings statistics (only for manager)
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
        $this->requireCapability('asset.meter_reading.view', 'Bạn không có quyền xem statistics.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }

        $query = MeterReading::with(['meter.property', 'meter.unit', 'meter.service'])
            ->whereHas('meter.property', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            });

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('reading_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('reading_date', '<=', $request->date_to);
        }

        $readings = $query->get();

        $statistics = [
            'total_readings' => $readings->count(),
            'readings_this_month' => $readings->where('reading_date', '>=', now()->startOfMonth())->count(),
            'readings_last_month' => $readings->whereBetween('reading_date', [
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth()
            ])->count(),
            'by_service' => $readings->groupBy('meter.service.name')->map(function($group) {
                return $group->count();
            }),
            'by_property' => $readings->groupBy('meter.property.name')->map(function($group) {
                return $group->count();
            }),
            'by_taken_by' => $readings->groupBy('takenBy.name')->map(function($group) {
                return $group->count();
            })
        ];

        return view('staff.asset.meter-readings.statistics', compact('statistics'));
    }

}
