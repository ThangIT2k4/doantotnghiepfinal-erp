<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Lease;
use App\Models\PaymentCycle;
use App\Models\LeaseServiceSet;
use App\Models\OrganizationBanking;
use App\Models\OrganizationEmailSetting;
use App\Models\Service;
use App\Traits\ChecksCapabilities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SystemSettingsController extends Controller
{
    use ChecksCapabilities;
    
    /**
     * Display system settings index with tabs
     */
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('contract.lease.view', 'Bạn không có quyền truy cập cài đặt hệ thống.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Get user's organization
        $organization = Organization::find($organizationId);
        
        if (!$organization) {
            abort(404, 'Organization not found.');
        }

        // Get data for booking deposit tab
        $bookingDepositData = [
            'organization' => $organization
        ];

        // Get data for payment cycle tab
        $defaultPaymentCycle = PaymentCycle::where('organization_id', $organizationId)
            ->where('is_default', true)
            ->first();
        
        // Get payment cycles for filter
        $paymentCyclesForFilter = PaymentCycle::where('organization_id', $organizationId)
            ->orWhereNull('organization_id')
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();
        
        // Get properties - filter by payment_cycle_id if provided
        $cycleIdFilter = request()->get('payment_cycle_id');
        $propertiesQuery = Property::where('organization_id', $organizationId)
            ->where('status', 1)
            ->with('paymentCycle');
        
        // If payment_cycle_id filter is provided, filter properties by that cycle
        if ($cycleIdFilter) {
            $propertiesQuery->where(function($query) use ($cycleIdFilter, $defaultPaymentCycle) {
                // Properties with this cycle directly assigned
                $query->where('payment_cycle_id', $cycleIdFilter);
                
                // If filtering by default cycle, also include properties without payment_cycle_id
                // (they use the default cycle)
                if ($defaultPaymentCycle && $cycleIdFilter == $defaultPaymentCycle->id) {
                    $query->orWhereNull('payment_cycle_id');
                }
            });
        }
        
        $properties = $propertiesQuery->orderBy('name')->get();
        
        $availableCycles = PaymentCycle::where('organization_id', $organizationId)
            ->orWhereNull('organization_id')
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();
        // Get organization's payment cycles only (for management tab) with usage statistics
        $paymentCycles = PaymentCycle::where('organization_id', $organizationId)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($cycle) use ($organizationId) {
                // Count properties using this cycle
                $propertiesCount = Property::where('organization_id', $organizationId)
                    ->where('payment_cycle_id', $cycle->id)
                    ->count();
                
                // Count leases using this cycle
                $leasesCount = Lease::where('organization_id', $organizationId)
                    ->where('payment_cycle_id', $cycle->id)
                    ->count();
                
                $cycle->properties_count = $propertiesCount;
                $cycle->leases_count = $leasesCount;
                $cycle->total_usage = $propertiesCount + $leasesCount;
                
                return $cycle;
            });
        $paymentCycleOptions = [
            'monthly' => 'Hàng tháng',
            'quarterly' => 'Hàng quý',
            'yearly' => 'Hàng năm',
            'custom' => 'Tùy chỉnh (nhập số tháng)'
        ];
        $paymentCycleData = [
            'organization' => $organization,
            'defaultPaymentCycle' => $defaultPaymentCycle,
            'properties' => $properties,
            'availableCycles' => $availableCycles,
            'paymentCycles' => $paymentCycles,
            'paymentCycleOptions' => $paymentCycleOptions,
            'paymentCyclesForFilter' => $paymentCyclesForFilter,
            'selectedCycleId' => $cycleIdFilter
        ];

        // Get data for lease service tab
        $defaultLeaseServiceSet = LeaseServiceSet::where('organization_id', $organizationId)
            ->where('is_default', true)
            ->with(['items.service'])
            ->first();
        $leaseServiceSets = LeaseServiceSet::where('organization_id', $organizationId)
            ->with(['items.service'])
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get()
            ->map(function($set) use ($organizationId) {
                // Count properties using this set
                $propertiesCount = Property::where('organization_id', $organizationId)
                    ->where('lease_services_id', $set->id)
                    ->count();
                
                // Count leases using this set
                $leasesCount = Lease::where('organization_id', $organizationId)
                    ->where('lease_services_id', $set->id)
                    ->count();
                
                $set->properties_count = $propertiesCount;
                $set->leases_count = $leasesCount;
                $set->total_usage = $propertiesCount + $leasesCount;
                
                return $set;
            });
        // Get services available for this organization (organization-specific + global)
        $services = Service::forOrganization($organizationId)->orderBy('name')->get();
        $leaseServiceData = [
            'organization' => $organization,
            'defaultLeaseServiceSet' => $defaultLeaseServiceSet,
            'leaseServiceSets' => $leaseServiceSets,
            'properties' => $properties,
            'services' => $services
        ];

        // Get data for organization banking tab
        $bankingAccounts = OrganizationBanking::with('sepayBank')
            ->where('organization_id', $organizationId)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
        $sepayBanks = \App\Models\SepayBank::supported()->orderBy('name')->get();
        $organizationBankingData = [
            'bankingAccounts' => $bankingAccounts,
            'sepayBanks' => $sepayBanks
        ];

        // Get data for organization email tab
        $emailSetting = OrganizationEmailSetting::where('organization_id', $organizationId)->first();
        $organizationEmailData = [
            'organization' => $organization,
            'emailSetting' => $emailSetting
        ];

        // Get data for services tab
        // Get services available for this organization (organization-specific + global)
        $services = Service::forOrganization($organizationId)
            ->with('organization')
            ->orderBy('name')
            ->paginate(20);
        $servicesData = [
            'services' => $services
        ];

        // Get data for organization tab
        $organizationData = [
            'organization' => $organization
        ];

        return view('staff.settings.system-settings.index', [
            'organization' => $organization,
            'bookingDepositData' => $bookingDepositData,
            'paymentCycleData' => $paymentCycleData,
            'leaseServiceData' => $leaseServiceData,
            'organizationBankingData' => $organizationBankingData,
            'organizationEmailData' => $organizationEmailData,
            'organizationData' => $organizationData,
            'servicesData' => $servicesData,
        ]);
    }

    /**
     * Update organization name
     */
    public function updateOrganizationName(Request $request)
    {
        // Check capability - only manager can update organization name
        $this->requireCapability('settings.organization.update', 'Bạn không có quyền cập nhật tên tổ chức.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thuộc tổ chức nào.'
            ], 403);
        }
        
        $request->validate([
            'name' => 'required|string|max:255|min:2',
        ]);
        
        try {
            $organization = Organization::findOrFail($organizationId);
            
            $organization->update([
                'name' => $request->name
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Tên tổ chức đã được cập nhật thành công!',
                'data' => [
                    'name' => $organization->name
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật tên tổ chức: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Filter properties by payment cycle (HTMX endpoint)
     */
    public function filterProperties(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('contract.lease.view', 'Bạn không có quyền truy cập cài đặt hệ thống.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Get user's organization
        $organization = Organization::find($organizationId);
        
        if (!$organization) {
            abort(404, 'Organization not found.');
        }

        // Get payment cycles for filter
        $paymentCyclesForFilter = PaymentCycle::where('organization_id', $organizationId)
            ->orWhereNull('organization_id')
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();
        
        // Get default payment cycle for this organization
        $defaultPaymentCycle = PaymentCycle::where('organization_id', $organizationId)
            ->where('is_default', true)
            ->first();
        
        // Get payment_cycle_id filter
        $cycleIdFilter = $request->get('payment_cycle_id');
        
        // Get properties - filter by payment_cycle_id if provided
        $propertiesQuery = Property::where('organization_id', $organizationId)
            ->where('status', 1)
            ->with('paymentCycle');
        
        // If payment_cycle_id filter is provided, filter properties by that cycle
        if ($cycleIdFilter) {
            $propertiesQuery->where(function($query) use ($cycleIdFilter, $defaultPaymentCycle) {
                // Properties with this cycle directly assigned
                $query->where('payment_cycle_id', $cycleIdFilter);
                
                // If filtering by default cycle, also include properties without payment_cycle_id
                // (they use the default cycle)
                if ($defaultPaymentCycle && $cycleIdFilter == $defaultPaymentCycle->id) {
                    $query->orWhereNull('payment_cycle_id');
                }
            });
        }
        
        $properties = $propertiesQuery->orderBy('name')->get();
        
        return view('staff.settings.system-settings.tabs.partials.properties-list', [
            'properties' => $properties,
            'paymentCyclesForFilter' => $paymentCyclesForFilter,
            'selectedCycleId' => $cycleIdFilter
        ]);
    }
}

