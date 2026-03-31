<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Lease;
use App\Models\Invoice;
use App\Models\MeterReading;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ContractController extends Controller
{
    /**
     * Display a listing of the tenant's contracts
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Get accessible lease IDs (user as tenant or resident)
        $accessibleLeaseIds = Lease::getAccessibleLeaseIds($user->id);
        
        // Optimized query using JOINs and proper index order
        $query = Lease::select([
            'leases.*',
            'units.code as unit_code',
            'properties.name as property_name'
        ])
        ->join('units', 'leases.unit_id', '=', 'units.id')
        ->leftJoin('properties', 'units.property_id', '=', 'properties.id')
        ->leftJoin('locations', 'properties.location_id', '=', 'locations.id')
        ->leftJoin('locations_2025', 'properties.location_id_2025', '=', 'locations_2025.id')
        ->whereIn('leases.id', $accessibleLeaseIds) // Uses idx_leases_org_tenant_deleted
        ->whereNull('leases.deleted_at') // Uses idx_leases_deleted_at_status
        ->whereNull('units.deleted_at') // Uses idx_units_deleted_at_property
        ->whereNull('properties.deleted_at'); // Uses idx_properties_deleted_at_org

        // Apply search filter - optimized with JOIN
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('properties.name', 'like', "%{$search}%")
                  ->orWhere('units.code', 'like', "%{$search}%") // Tìm theo tên phòng
                  ->orWhere('locations.street', 'like', "%{$search}%")
                  ->orWhere('locations.district', 'like', "%{$search}%")
                  ->orWhere('locations.ward', 'like', "%{$search}%")
                  ->orWhere('locations.city', 'like', "%{$search}%")
                  ->orWhere('locations_2025.street', 'like', "%{$search}%")
                  ->orWhere('locations_2025.ward', 'like', "%{$search}%")
                  ->orWhere('locations_2025.city', 'like', "%{$search}%");
            });
        }

        // Apply status filter - uses idx_leases_deleted_at_status
        if ($request->filled('status') && $request->status !== 'all') {
            $status = $request->status;
            if ($status === 'active') {
                $query->where('leases.status', 'active');
            } elseif ($status === 'expiring') {
                $query->where('leases.status', 'active') // Uses idx_leases_start_end_status
                      ->where('leases.end_date', '<=', Carbon::now()->addDays(30))
                      ->where('leases.end_date', '>', Carbon::now());
            } elseif ($status === 'expired') {
                $query->where(function($q) {
                    $q->where('leases.status', 'expired')
                      ->orWhere('leases.end_date', '<', Carbon::now()); // Uses idx_leases_start_end_status
                });
            }
        }

        $contracts = $query->latest('leases.start_date')->paginate(10);
        
        // Eager load relationships for display
        $contracts->load([
            'unit.property.location',
            'unit.property.location2025',
            'unit.property.propertyType',
            'invoices' => function($q) {
                $q->latest('issue_date');
            },
            'leaseServiceSet.items.service',
            'agent'
        ]);

        // Calculate statistics
        $stats = $this->calculateContractStats($user->id);

        // Check if HTMX request
        $isHtmx = $request->header('HX-Request') === 'true' || $request->header('Hx-Request') === 'true';
        
        if ($isHtmx) {
            // Return only the contracts list partial for HTMX
            return response()
                ->view('tenant.contract.partials.contracts-list', compact('contracts'))
                ->header('HX-Push-Url', $request->fullUrl());
        }

        return view('tenant.contract.index', compact('contracts', 'stats'));
    }

    /**
     * Display the specified contract
     */
    public function show($id)
    {
        $user = Auth::user();
        
        // Get accessible lease IDs (user as tenant or resident)
        $accessibleLeaseIds = Lease::getAccessibleLeaseIds($user->id);
        
        // Optimized query - verify ownership first
        $contract = Lease::where('id', $id)
        ->whereIn('id', $accessibleLeaseIds) // Uses idx_leases_org_tenant_deleted
        ->whereNull('deleted_at') // Uses idx_leases_deleted_at_status
        ->firstOrFail();
        
        // Eager load relationships for display
        $contract->load([
            'unit.property.location',
            'unit.property.location2025',
            'unit.property.propertyType',
            'unit.meters.service',
            'unit.meters.readings' => function($q) {
                $q->latest('reading_date');
            },
            'invoices.items',
            'leaseServiceSet.items.service',
            'residents',
            'agent',
            'tenant'
        ]);

        // Get meter readings summary (last 5 readings)
        $meterReadingsSummary = $this->getMeterReadingsSummary($contract->unit_id, 5);

        // Get all meter readings for the contract period
        $meterReadingsHistory = $this->getMeterReadingsHistory($contract->unit_id, $contract->start_date);

        // Optimized query for invoices
        $invoices = Invoice::where('lease_id', $contract->id) // Uses idx_invoices_org_lease_deleted
            ->whereNull('deleted_at') // Uses idx_invoices_deleted_at_status
            ->latest('issue_date') // Uses idx_invoices_issue_date
            ->paginate(10);

        // Calculate remaining days
        $remainingDays = Carbon::now()->diffInDays($contract->end_date, false);
        $isExpired = $contract->end_date < Carbon::now();
        $isExpiring = !$isExpired && $remainingDays <= 30;

        return view('tenant.contract.show', compact(
            'contract',
            'meterReadingsSummary',
            'meterReadingsHistory',
            'invoices',
            'remainingDays',
            'isExpired',
            'isExpiring'
        ));
    }

    /**
     * Download contract PDF
     */
    public function download($id)
    {
        $user = Auth::user();
        
        // Get accessible lease IDs (user as tenant or resident)
        $accessibleLeaseIds = Lease::getAccessibleLeaseIds($user->id);
        
        // Optimized query - verify ownership first
        $contract = Lease::where('id', $id)
        ->whereIn('id', $accessibleLeaseIds) // Uses idx_leases_org_tenant_deleted
        ->whereNull('deleted_at') // Uses idx_leases_deleted_at_status
        ->firstOrFail();
        
        // Eager load relationships for display
        $contract->load([
            'organization',
            'unit.property.location',
            'unit.property.location2025',
            'unit.property.propertyType',
            'leaseServiceSet.items.service',
            'paymentCycle',
            'agent',
            'tenant'
        ]);

        // Generate PDF using facade
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.contract', compact('contract'));
        
        // Set PDF options
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOption('enable-local-file-access', true);
        
        // Return PDF download
        $filename = 'hop-dong-' . $contract->contract_no . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Calculate contract statistics
     */
    private function calculateContractStats($tenantId)
    {
        // Optimized stats queries using indexes
        $now = Carbon::now();
        $thirtyDaysFromNow = $now->copy()->addDays(30);

        // Get accessible lease IDs (user as tenant or resident)
        $accessibleLeaseIds = Lease::getAccessibleLeaseIds($tenantId);
        
        // Uses idx_leases_org_tenant_deleted and idx_leases_start_end_status
        $baseQuery = Lease::whereIn('id', $accessibleLeaseIds) // Uses idx_leases_org_tenant_deleted
            ->whereNull('deleted_at'); // Uses idx_leases_deleted_at_status

        $active = (clone $baseQuery)
            ->where('status', 'active') // Uses idx_leases_deleted_at_status
            ->where('end_date', '>', $now) // Uses idx_leases_start_end_status
            ->count();

        $expiring = (clone $baseQuery)
            ->where('status', 'active') // Uses idx_leases_deleted_at_status
            ->where('end_date', '<=', $thirtyDaysFromNow) // Uses idx_leases_start_end_status
            ->where('end_date', '>', $now) // Uses idx_leases_start_end_status
            ->count();

        $expired = (clone $baseQuery)
            ->where(function($q) use ($now) {
                $q->where('status', 'expired')
                  ->orWhere('end_date', '<', $now); // Uses idx_leases_start_end_status
            })
            ->count();

        $total = (clone $baseQuery)->count();

        return [
            'active' => $active,
            'expiring' => $expiring,
            'expired' => $expired,
            'total' => $total
        ];
    }

    /**
     * Get meter readings summary
     */
    private function getMeterReadingsSummary($unitId, $limit = 5)
    {
        return MeterReading::with(['meter.service'])
            ->whereHas('meter', function($q) use ($unitId) {
                $q->where('unit_id', $unitId);
            })
            ->latest('reading_date')
            ->limit($limit)
            ->get()
            ->groupBy('meter.service.name');
    }

    /**
     * Get meter readings history
     */
    private function getMeterReadingsHistory($unitId, $startDate)
    {
        return MeterReading::with(['meter.service'])
            ->whereHas('meter', function($q) use ($unitId) {
                $q->where('unit_id', $unitId);
            })
            ->where('reading_date', '>=', $startDate)
            ->latest('reading_date')
            ->get()
            ->groupBy('meter.service.name');
    }

    /**
     * Get address from location (old format)
     */
    private function getLocationAddress($location)
    {
        if (!$location) {
            return null;
        }

        $addressParts = [];
        
        if ($location->street) {
            $addressParts[] = $location->street;
        }
        
        if ($location->ward) {
            $addressParts[] = $location->ward;
        }
        
        if ($location->district) {
            $addressParts[] = $location->district;
        }
        
        if ($location->city) {
            $addressParts[] = $location->city;
        }
        
        if ($location->country && $location->country !== 'Vietnam') {
            $addressParts[] = $location->country;
        }

        return !empty($addressParts) ? implode(', ', $addressParts) : null;
    }

    /**
     * Get address from location2025 (new format)
     */
    private function getLocation2025Address($location2025)
    {
        if (!$location2025) {
            return null;
        }

        $addressParts = [];
        
        if ($location2025->street) {
            $addressParts[] = $location2025->street;
        }
        
        if ($location2025->ward) {
            $addressParts[] = $location2025->ward;
        }
        
        if ($location2025->city) {
            $addressParts[] = $location2025->city;
        }
        
        if ($location2025->country && $location2025->country !== 'Vietnam') {
            $addressParts[] = $location2025->country;
        }

        return !empty($addressParts) ? implode(', ', $addressParts) : null;
    }
}
