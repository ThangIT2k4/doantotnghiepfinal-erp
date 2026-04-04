<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Lease;
use App\Models\PaymentCycle;
use App\Traits\ChecksCapabilities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PaymentCycleSettingController extends Controller
{
    use ChecksCapabilities;
    
    /**
     * Display payment cycle settings index
     */
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can access payment cycle settings
        $this->requireCapability('billing.payment_cycle.view', 'Bạn không có quyền truy cập Payment Cycle Settings.');
        
        // Redirect to system-settings with payment-cycle tab
        return redirect()->route('staff.system-settings.index')->with('active_tab', 'payment-cycle');
    }

    /**
     * Create or update organization payment cycle
     */
    public function updateOrganization(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can update payment cycle settings
        $this->requireCapability('billing.payment_cycle.update', 'Bạn không có quyền cập nhật Payment Cycle Settings.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Get user's organization
        $organization = Organization::find($organizationId);
        
        if (!$organization) {
            abort(404, 'Organization not found.');
        }

        $request->validate([
            'cycle_type' => 'nullable|in:monthly,quarterly,yearly,custom',
            'billing_day' => 'nullable|integer|min:1|max:28',
            'notes' => 'nullable|string|max:1000',
            'custom_months' => 'nullable|required_if:cycle_type,custom|integer|min:1|max:60',
            'payment_cycle_id' => 'nullable|exists:payment_cycles,id',
        ], [
            'cycle_type.in' => 'Chu kỳ thanh toán không hợp lệ.',
            'billing_day.integer' => 'Ngày tạo hóa đơn phải là số nguyên.',
            'billing_day.min' => 'Ngày tạo hóa đơn phải từ 1 đến 28.',
            'billing_day.max' => 'Ngày tạo hóa đơn phải từ 1 đến 28.',
            'notes.max' => 'Ghi chú không được vượt quá 1000 ký tự.',
            'custom_months.required_if' => 'Vui lòng nhập số tháng tùy chỉnh.',
            'custom_months.integer' => 'Số tháng tùy chỉnh phải là số nguyên.',
            'custom_months.min' => 'Số tháng tùy chỉnh phải từ 1 đến 60.',
            'custom_months.max' => 'Số tháng tùy chỉnh phải từ 1 đến 60.',
            'payment_cycle_id.exists' => 'Chu kỳ thanh toán không tồn tại.',
        ]);

        try {
            DB::beginTransaction();

            $paymentCycle = null;

            // If payment_cycle_id is provided, set it as default
            if ($request->payment_cycle_id) {
                $paymentCycle = PaymentCycle::where('id', $request->payment_cycle_id)
                    ->where(function($query) use ($organizationId) {
                        $query->where('organization_id', $organizationId)
                              ->orWhereNull('organization_id');
                    })
                    ->firstOrFail();
                
                // Unset all other default cycles for this organization
                PaymentCycle::where('organization_id', $organizationId)
                    ->where('id', '!=', $paymentCycle->id)
                    ->update(['is_default' => false]);
                
                // Set this cycle as default
                $paymentCycle->update(['is_default' => true]);
                
                // Update invoice_timing and invoice_payment_days in payment cycle
                if ($request->has('invoice_timing')) {
                    $paymentCycle->invoice_timing = $request->invoice_timing;
                }
                
                if ($request->has('invoice_payment_days')) {
                    $paymentCycle->invoice_payment_days = $request->invoice_payment_days;
                }
                
                if ($request->has('payment_due_hours')) {
                    $paymentCycle->payment_due_hours = $request->payment_due_hours;
                }
                
                $paymentCycle->save();
            } elseif ($request->cycle_type) {
                // Unset all other default cycles for this organization
                PaymentCycle::where('organization_id', $organizationId)
                    ->update(['is_default' => false]);
                
                // Create new payment cycle as default (similar to lease_service_set logic)
                $paymentCycle = PaymentCycle::create([
                    'organization_id' => $organizationId,
                    'cycle_type' => $request->cycle_type,
                    'billing_day' => $request->billing_day,
                    'custom_months' => $request->cycle_type === 'custom' ? $request->custom_months : null,
                    'notes' => $request->notes,
                    'is_default' => true,
                    'invoice_timing' => $request->has('invoice_timing') ? $request->invoice_timing : null,
                    'invoice_payment_days' => $request->has('invoice_payment_days') ? $request->invoice_payment_days : null,
                    'payment_due_hours' => $request->has('payment_due_hours') ? $request->payment_due_hours : null,
                ]);
            } else {
                // If no cycle_type or payment_cycle_id provided, ensure there's a default cycle
                $existingDefault = PaymentCycle::where('organization_id', $organizationId)
                    ->where('is_default', true)
                    ->first();
                
                if (!$existingDefault) {
                    // Create default monthly cycle if needed
                    $paymentCycle = PaymentCycle::create([
                        'organization_id' => $organizationId,
                        'cycle_type' => 'monthly',
                        'billing_day' => 1,
                        'notes' => 'Chu kỳ thanh toán mặc định',
                        'is_default' => true,
                    ]);
                } else {
                    $paymentCycle = $existingDefault;
                }
            }

            Log::info('Organization payment cycle settings updated', [
                'organization_id' => $organizationId,
                'payment_cycle_id' => $paymentCycle ? $paymentCycle->id : null,
                'is_default' => $paymentCycle ? $paymentCycle->is_default : null,
                'invoice_timing' => $paymentCycle ? ($paymentCycle->invoice_timing ?? null) : null,
                'invoice_payment_days' => $paymentCycle ? ($paymentCycle->invoice_payment_days ?? null) : null,
                'payment_due_hours' => $paymentCycle ? ($paymentCycle->payment_due_hours ?? null) : null,
                'updated_by' => $user->id,
            ]);

            DB::commit();

            return back()->with('success', 'Đã cập nhật chu kỳ thanh toán mặc định cho tổ chức thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating organization payment cycle settings: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra khi cập nhật cài đặt: ' . $e->getMessage());
        }
    }

    /**
     * Update property payment cycle settings
     */
    public function updateProperty(Request $request, $propertyId)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can update payment cycle settings
        $this->requireCapability('billing.payment_cycle.update', 'Bạn không có quyền cập nhật Payment Cycle Settings.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }

        // Get property
        $property = Property::where('organization_id', $organizationId)
            ->where('id', $propertyId)
            ->firstOrFail();

        $request->validate([
            'cycle_type' => 'nullable|in:monthly,quarterly,yearly,custom',
            'billing_day' => 'nullable|integer|min:1|max:28',
            'notes' => 'nullable|string|max:1000',
            'custom_months' => 'nullable|required_if:cycle_type,custom|integer|min:1|max:60',
            'payment_cycle_id' => 'nullable|exists:payment_cycles,id',
        ], [
            'cycle_type.in' => 'Chu kỳ thanh toán không hợp lệ.',
            'billing_day.integer' => 'Ngày tạo hóa đơn phải là số nguyên.',
            'billing_day.min' => 'Ngày tạo hóa đơn phải từ 1 đến 28.',
            'billing_day.max' => 'Ngày tạo hóa đơn phải từ 1 đến 28.',
            'notes.max' => 'Ghi chú không được vượt quá 1000 ký tự.',
            'custom_months.required_if' => 'Vui lòng nhập số tháng tùy chỉnh.',
            'custom_months.integer' => 'Số tháng tùy chỉnh phải là số nguyên.',
            'custom_months.min' => 'Số tháng tùy chỉnh phải từ 1 đến 60.',
            'custom_months.max' => 'Số tháng tùy chỉnh phải từ 1 đến 60.',
            'payment_cycle_id.exists' => 'Chu kỳ thanh toán không tồn tại.',
        ]);

        try {
            DB::beginTransaction();

            // If payment_cycle_id is provided, use existing cycle
            if ($request->payment_cycle_id) {
                $paymentCycle = PaymentCycle::where('id', $request->payment_cycle_id)
                    ->where(function($query) use ($organizationId) {
                        $query->where('organization_id', $organizationId)
                              ->orWhereNull('organization_id');
                    })
                    ->firstOrFail();
                
                $property->payment_cycle_id = $paymentCycle->id;
                $property->save();
            } elseif ($request->cycle_type) {
                // Create new payment cycle for property
                $paymentCycle = PaymentCycle::create([
                    'organization_id' => $organizationId,
                        'cycle_type' => $request->cycle_type,
                        'billing_day' => $request->billing_day,
                    'custom_months' => $request->cycle_type === 'custom' ? $request->custom_months : null,
                    'notes' => $request->notes,
                    'is_default' => false,
                ]);

                $property->payment_cycle_id = $paymentCycle->id;
                $property->save();
            } else {
                // Remove payment cycle (set to null)
                $property->payment_cycle_id = null;
                $property->save();
            }

            Log::info('Property payment cycle settings updated', [
                'property_id' => $property->id,
                'organization_id' => $organizationId,
                'payment_cycle_id' => $property->payment_cycle_id,
                'updated_by' => $user->id,
            ]);

            DB::commit();

            return back()->with('success', 'Đã cập nhật cài đặt chu kỳ thanh toán bất động sản thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating property payment cycle settings: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra khi cập nhật cài đặt: ' . $e->getMessage());
        }
    }

    /**
     * Update lease payment cycle settings
     */
    public function updateLease(Request $request, $leaseId)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can update payment cycle settings
        $this->requireCapability('billing.payment_cycle.update', 'Bạn không có quyền cập nhật Payment Cycle Settings.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }

        // Get lease
        $lease = Lease::where('organization_id', $organizationId)
            ->where('id', $leaseId)
            ->firstOrFail();

        $request->validate([
            'cycle_type' => 'nullable|in:monthly,quarterly,yearly,custom',
            'billing_day' => 'nullable|integer|min:1|max:28',
            'notes' => 'nullable|string|max:1000',
            'custom_months' => 'nullable|required_if:cycle_type,custom|integer|min:1|max:60',
            'payment_cycle_id' => 'nullable|exists:payment_cycles,id',
        ], [
            'cycle_type.in' => 'Chu kỳ thanh toán không hợp lệ.',
            'billing_day.integer' => 'Ngày tạo hóa đơn phải là số nguyên.',
            'billing_day.min' => 'Ngày tạo hóa đơn phải từ 1 đến 28.',
            'billing_day.max' => 'Ngày tạo hóa đơn phải từ 1 đến 28.',
            'notes.max' => 'Ghi chú không được vượt quá 1000 ký tự.',
            'custom_months.required_if' => 'Vui lòng nhập số tháng tùy chỉnh.',
            'custom_months.integer' => 'Số tháng tùy chỉnh phải là số nguyên.',
            'custom_months.min' => 'Số tháng tùy chỉnh phải từ 1 đến 60.',
            'custom_months.max' => 'Số tháng tùy chỉnh phải từ 1 đến 60.',
            'payment_cycle_id.exists' => 'Chu kỳ thanh toán không tồn tại.',
        ]);

        try {
            DB::beginTransaction();

            // If payment_cycle_id is provided, use existing cycle
            if ($request->payment_cycle_id) {
                $paymentCycle = PaymentCycle::where('id', $request->payment_cycle_id)
                    ->where(function($query) use ($organizationId) {
                        $query->where('organization_id', $organizationId)
                              ->orWhereNull('organization_id');
                    })
                    ->firstOrFail();
                
                $lease->payment_cycle_id = $paymentCycle->id;
                $lease->save();
            } elseif ($request->cycle_type) {
                // Create new payment cycle for lease
                $paymentCycle = PaymentCycle::create([
                    'organization_id' => $organizationId,
                        'cycle_type' => $request->cycle_type,
                        'billing_day' => $request->billing_day,
                    'custom_months' => $request->cycle_type === 'custom' ? $request->custom_months : null,
                    'notes' => $request->notes,
                    'is_default' => false,
                ]);

                $lease->payment_cycle_id = $paymentCycle->id;
                $lease->save();
            } else {
                // Remove payment cycle (set to null)
                $lease->payment_cycle_id = null;
                $lease->save();
            }

            Log::info('Lease payment cycle settings updated', [
                'lease_id' => $lease->id,
                'organization_id' => $organizationId,
                'payment_cycle_id' => $lease->payment_cycle_id,
                'updated_by' => $user->id,
            ]);

            DB::commit();

            return back()->with('success', 'Đã cập nhật cài đặt chu kỳ thanh toán hợp đồng thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating lease payment cycle settings: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra khi cập nhật cài đặt: ' . $e->getMessage());
        }
    }

    /**
     * Get leases for a property
     */
    public function getPropertyLeases($propertyId)
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            
            // Check capability
            $hasBillingAccess = $this->checkCapability('billing.access');
            if (!$hasBillingAccess) {
                return response()->json(['success' => false, 'error' => 'Bạn không có quyền truy cập.'], 403);
            }
            
            $organizationId = $this->getCurrentOrganizationId();
            
            if (!$organizationId) {
                return response()->json(['success' => false, 'error' => 'Bạn không thuộc tổ chức nào.'], 403);
            }

            // Get property with payment cycle and organization
            $property = Property::where('organization_id', $organizationId)
                ->where('id', $propertyId)
                ->with(['paymentCycle', 'organization'])
                ->firstOrFail();

        // Get leases for this property with payment cycles
        $leases = Lease::where('organization_id', $organizationId)
            ->whereHas('unit', function($query) use ($propertyId) {
                $query->where('property_id', $propertyId);
            })
            ->with(['unit', 'tenant', 'paymentCycle'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($lease) {
                $cycle = $lease->paymentCycle;
                return [
                    'id' => $lease->id,
                    'contract_no' => $lease->contract_no,
                    'unit_code' => $lease->unit->code ?? 'N/A',
                    'tenant_name' => $lease->tenant->full_name ?? 'N/A',
                    'status' => $lease->status,
                    'payment_cycle' => $cycle ? [
                        'id' => $cycle->id,
                        'cycle_type' => $cycle->cycle_type,
                        'cycle_type_name' => $cycle->cycle_type_name,
                        'billing_day' => $cycle->billing_day,
                        'custom_months' => $cycle->custom_months,
                        'notes' => $cycle->notes,
                        'name' => $cycle->name,
                    ] : null,
                    'created_at' => $lease->created_at->format('d/m/Y'),
                ];
            });

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
                'effective_payment_cycle' => $property->getEffectivePaymentCycle() ? [
                    'id' => $property->getEffectivePaymentCycle()->id,
                    'cycle_type' => $property->getEffectivePaymentCycle()->cycle_type,
                    'cycle_type_name' => $property->getEffectivePaymentCycle()->cycle_type_name,
                    'billing_day' => $property->getEffectivePaymentCycle()->billing_day,
                    'custom_months' => $property->getEffectivePaymentCycle()->custom_months,
                    'notes' => $property->getEffectivePaymentCycle()->notes,
                    'name' => $property->getEffectivePaymentCycle()->name,
                ] : null,
            ],
            'leases' => $leases
        ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Không tìm thấy bất động sản.'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in getPropertyLeases: ' . $e->getMessage(), [
                'property_id' => $propertyId,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Có lỗi xảy ra khi tải dữ liệu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Apply organization payment cycle to all properties
     */
    public function applyToProperties(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can apply settings
        $this->requireCapability('billing.payment_cycle.update', 'Bạn không có quyền cập nhật Payment Cycle Settings.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }
        
        // Get user's organization
        $organization = Organization::find($organizationId);
        
        if (!$organization) {
            abort(404, 'Organization not found.');
        }

        $request->validate([
            'apply_to_properties' => 'required|boolean',
        ]);

        if (!$request->apply_to_properties) {
            return back()->with('warning', 'Vui lòng xác nhận áp dụng cài đặt cho tất cả bất động sản.');
        }

        try {
            DB::beginTransaction();

            // Get default payment cycle for organization
            $defaultPaymentCycle = PaymentCycle::where('organization_id', $organizationId)
                ->where('is_default', true)
                ->first();

            // If organization doesn't have default payment cycle, create one
            if (!$defaultPaymentCycle) {
                $defaultPaymentCycle = PaymentCycle::create([
                    'organization_id' => $organizationId,
                    'cycle_type' => 'monthly',
                    'billing_day' => 1,
                    'notes' => 'Chu kỳ thanh toán mặc định',
                    'is_default' => true,
                ]);

                Log::info('Default payment cycle created for organization', [
                    'organization_id' => $organizationId,
                    'payment_cycle_id' => $defaultPaymentCycle->id,
                ]);
            }

            // Update all properties in organization to use default payment cycle
            $updatedCount = Property::where('organization_id', $organizationId)
                ->update([
                    'payment_cycle_id' => $defaultPaymentCycle->id,
                ]);

            Log::info('Organization payment cycle settings applied to properties', [
                'organization_id' => $organizationId,
                'payment_cycle_id' => $defaultPaymentCycle->id,
                'updated_by' => $user->id,
                'updated_properties_count' => $updatedCount,
            ]);

            DB::commit();

            return back()->with('success', "Đã áp dụng cài đặt chu kỳ thanh toán cho {$updatedCount} bất động sản thành công!");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error applying organization settings to properties: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra khi áp dụng cài đặt: ' . $e->getMessage());
        }
    }

    /**
     * Apply property payment cycle to all leases
     */
    public function applyToLeases(Request $request, $propertyId)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability - only manager can apply settings
        $this->requireCapability('billing.payment_cycle.update', 'Bạn không có quyền cập nhật Payment Cycle Settings.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không thuộc tổ chức nào.'
                ], 403);
            }
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }

        // Get property
        $property = Property::where('organization_id', $organizationId)
            ->where('id', $propertyId)
            ->with('paymentCycle')
            ->firstOrFail();

        // Get effective payment cycle for property
        $effectiveCycle = $property->getEffectivePaymentCycle();
        
        if (!$effectiveCycle) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bất động sản này chưa có cài đặt chu kỳ thanh toán. Vui lòng cài đặt chu kỳ thanh toán trước.'
                ], 422);
            }
            return back()->with('error', 'Bất động sản này chưa có cài đặt chu kỳ thanh toán. Vui lòng cài đặt chu kỳ thanh toán trước.');
        }

        try {
            DB::beginTransaction();

            // Get all active leases for this property
            $leases = Lease::where('organization_id', $organizationId)
                ->whereHas('unit', function($query) use ($propertyId) {
                    $query->where('property_id', $propertyId);
                })
                ->whereNull('deleted_at')
                ->get();

            $updatedCount = 0;

            foreach ($leases as $lease) {
                // Update lease with property's payment cycle
                $lease->update([
                    'payment_cycle_id' => $effectiveCycle->id,
                ]);
                $updatedCount++;
            }

            Log::info('Property payment cycle settings applied to leases', [
                'property_id' => $propertyId,
                'organization_id' => $organizationId,
                'payment_cycle_id' => $effectiveCycle->id,
                'updated_by' => $user->id,
                'updated_leases_count' => $updatedCount,
            ]);

            DB::commit();

            $message = "Đã áp dụng cài đặt chu kỳ thanh toán cho {$updatedCount} hợp đồng thuê thành công!";

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'updated_count' => $updatedCount
                ]);
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error applying property settings to leases: ' . $e->getMessage());
            
            $errorMessage = 'Có lỗi xảy ra khi áp dụng cài đặt: ' . $e->getMessage();
            
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
     * Get property payment cycle (API endpoint for AJAX)
     */
    public function getPropertyPaymentCycle($propertyId)
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            
            $organizationId = $this->getCurrentOrganizationId();
            
            if (!$organizationId) {
                return response()->json(['error' => 'Bạn không thuộc tổ chức nào.'], 403);
            }

            // Get property with payment cycle
            $property = Property::where('organization_id', $organizationId)
                ->where('id', $propertyId)
                ->with('paymentCycle')
                ->firstOrFail();

            $effectiveCycle = $property->getEffectivePaymentCycle();

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
                    ] : null,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting property payment cycle: ' . $e->getMessage());
            return response()->json(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Store a new payment cycle
     */
    public function storeCycle(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('billing.payment_cycle.create', 'Bạn không có quyền tạo Payment Cycle Settings.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return response()->json(['error' => 'Bạn không thuộc tổ chức nào.'], 403);
        }

        $request->validate([
            'cycle_type' => 'required|in:monthly,quarterly,yearly,custom',
            'billing_day' => 'nullable|integer|min:1|max:28',
            'notes' => 'nullable|string|max:1000',
            'custom_months' => 'nullable|required_if:cycle_type,custom|integer|min:1|max:60',
            'is_default' => 'nullable|boolean',
            'invoice_timing' => 'nullable|in:start_of_cycle,end_of_cycle',
            'invoice_payment_days' => 'nullable|integer|min:1|max:365',
            'payment_due_hours' => 'nullable|integer|min:0',
        ], [
            'cycle_type.required' => 'Vui lòng chọn loại chu kỳ.',
            'cycle_type.in' => 'Chu kỳ thanh toán không hợp lệ.',
            'billing_day.integer' => 'Ngày tạo hóa đơn phải là số nguyên.',
            'billing_day.min' => 'Ngày tạo hóa đơn phải từ 1 đến 28.',
            'billing_day.max' => 'Ngày tạo hóa đơn phải từ 1 đến 28.',
            'notes.max' => 'Ghi chú không được vượt quá 1000 ký tự.',
            'custom_months.required_if' => 'Vui lòng nhập số tháng tùy chỉnh.',
            'custom_months.integer' => 'Số tháng tùy chỉnh phải là số nguyên.',
            'custom_months.min' => 'Số tháng tùy chỉnh phải từ 1 đến 60.',
            'custom_months.max' => 'Số tháng tùy chỉnh phải từ 1 đến 60.',
            'invoice_timing.in' => 'Tính tiền hóa đơn không hợp lệ.',
            'invoice_payment_days.integer' => 'Số ngày thanh toán phải là số nguyên.',
            'invoice_payment_days.min' => 'Số ngày thanh toán phải từ 1 đến 365.',
            'invoice_payment_days.max' => 'Số ngày thanh toán phải từ 1 đến 365.',
            'payment_due_hours.integer' => 'Thời gian chờ thanh toán phải là số nguyên.',
            'payment_due_hours.min' => 'Thời gian chờ thanh toán phải lớn hơn hoặc bằng 0.',
        ]);

        try {
            DB::beginTransaction();

            // If is_default is true, unset all other default cycles
            if ($request->is_default) {
                PaymentCycle::where('organization_id', $organizationId)
                    ->update(['is_default' => false]);
            }

            // Create new payment cycle
            $paymentCycle = PaymentCycle::create([
                'organization_id' => $organizationId,
                'cycle_type' => $request->cycle_type,
                'billing_day' => $request->billing_day,
                'custom_months' => $request->cycle_type === 'custom' ? $request->custom_months : null,
                'notes' => $request->notes,
                'is_default' => $request->is_default ?? false,
                'invoice_timing' => $request->invoice_timing,
                'invoice_payment_days' => $request->invoice_payment_days,
                'payment_due_hours' => $request->payment_due_hours,
            ]);

            Log::info('Payment cycle created', [
                'payment_cycle_id' => $paymentCycle->id,
                'organization_id' => $organizationId,
                'created_by' => $user->id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đã tạo chu kỳ thanh toán thành công.',
                'paymentCycle' => $paymentCycle
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating payment cycle: ' . $e->getMessage());
            return response()->json(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get a payment cycle by ID
     */
    public function getCycle($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('billing.payment_cycle.view', 'Bạn không có quyền xem Payment Cycle Settings.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return response()->json(['error' => 'Bạn không thuộc tổ chức nào.'], 403);
        }

        $paymentCycle = PaymentCycle::where('id', $id)
            ->where(function($query) use ($organizationId) {
                $query->where('organization_id', $organizationId)
                      ->orWhereNull('organization_id');
            })
            ->firstOrFail();

        // Get usage statistics
        $propertiesCount = Property::where('organization_id', $organizationId)
            ->where('payment_cycle_id', $paymentCycle->id)
            ->count();
        
        $leasesCount = Lease::where('organization_id', $organizationId)
            ->where('payment_cycle_id', $paymentCycle->id)
            ->count();

        $paymentCycle->properties_count = $propertiesCount;
        $paymentCycle->leases_count = $leasesCount;
        $paymentCycle->total_usage = $propertiesCount + $leasesCount;

        return response()->json([
            'success' => true,
            'paymentCycle' => $paymentCycle
        ]);
    }

    /**
     * Update a payment cycle
     */
    public function updateCycle(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('billing.payment_cycle.update', 'Bạn không có quyền cập nhật Payment Cycle Settings.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return response()->json(['error' => 'Bạn không thuộc tổ chức nào.'], 403);
        }

        $paymentCycle = PaymentCycle::where('id', $id)
            ->where('organization_id', $organizationId)
            ->firstOrFail();

        $request->validate([
            'cycle_type' => 'required|in:monthly,quarterly,yearly,custom',
            'billing_day' => 'nullable|integer|min:1|max:28',
            'notes' => 'nullable|string|max:1000',
            'custom_months' => 'nullable|required_if:cycle_type,custom|integer|min:1|max:60',
            'is_default' => 'nullable|boolean',
            'invoice_timing' => 'nullable|in:start_of_cycle,end_of_cycle',
            'invoice_payment_days' => 'nullable|integer|min:1|max:365',
            'payment_due_hours' => 'nullable|integer|min:0',
        ], [
            'cycle_type.required' => 'Vui lòng chọn loại chu kỳ.',
            'cycle_type.in' => 'Chu kỳ thanh toán không hợp lệ.',
            'billing_day.integer' => 'Ngày tạo hóa đơn phải là số nguyên.',
            'billing_day.min' => 'Ngày tạo hóa đơn phải từ 1 đến 28.',
            'billing_day.max' => 'Ngày tạo hóa đơn phải từ 1 đến 28.',
            'notes.max' => 'Ghi chú không được vượt quá 1000 ký tự.',
            'custom_months.required_if' => 'Vui lòng nhập số tháng tùy chỉnh.',
            'custom_months.integer' => 'Số tháng tùy chỉnh phải là số nguyên.',
            'custom_months.min' => 'Số tháng tùy chỉnh phải từ 1 đến 60.',
            'custom_months.max' => 'Số tháng tùy chỉnh phải từ 1 đến 60.',
            'invoice_timing.in' => 'Tính tiền hóa đơn không hợp lệ.',
            'invoice_payment_days.integer' => 'Số ngày thanh toán phải là số nguyên.',
            'invoice_payment_days.min' => 'Số ngày thanh toán phải từ 1 đến 365.',
            'invoice_payment_days.max' => 'Số ngày thanh toán phải từ 1 đến 365.',
            'payment_due_hours.integer' => 'Thời gian chờ thanh toán phải là số nguyên.',
            'payment_due_hours.min' => 'Thời gian chờ thanh toán phải lớn hơn hoặc bằng 0.',
        ]);

        try {
            DB::beginTransaction();

            // If is_default is true, unset all other default cycles
            if ($request->is_default) {
                PaymentCycle::where('organization_id', $organizationId)
                    ->where('id', '!=', $paymentCycle->id)
                    ->update(['is_default' => false]);
            }

            // Update payment cycle
            $paymentCycle->update([
                'cycle_type' => $request->cycle_type,
                'billing_day' => $request->billing_day,
                'custom_months' => $request->cycle_type === 'custom' ? $request->custom_months : null,
                'notes' => $request->notes,
                'is_default' => $request->is_default ?? false,
                'invoice_timing' => $request->invoice_timing,
                'invoice_payment_days' => $request->invoice_payment_days,
                'payment_due_hours' => $request->payment_due_hours,
            ]);

            Log::info('Payment cycle updated', [
                'payment_cycle_id' => $paymentCycle->id,
                'organization_id' => $organizationId,
                'updated_by' => $user->id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật chu kỳ thanh toán thành công.',
                'paymentCycle' => $paymentCycle->fresh()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating payment cycle: ' . $e->getMessage());
            return response()->json(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete a payment cycle
     */
    public function destroyCycle($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('billing.payment_cycle.delete', 'Bạn không có quyền xóa Payment Cycle Settings.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return response()->json(['error' => 'Bạn không thuộc tổ chức nào.'], 403);
        }

        $paymentCycle = PaymentCycle::where('id', $id)
            ->where('organization_id', $organizationId)
            ->firstOrFail();

        // Check if cycle is being used
        $usedInProperties = Property::where('payment_cycle_id', $paymentCycle->id)->count();
        $usedInLeases = Lease::where('payment_cycle_id', $paymentCycle->id)->count();

        if ($usedInProperties > 0 || $usedInLeases > 0) {
            return response()->json([
                'error' => 'Không thể xóa chu kỳ thanh toán này vì đang được sử dụng bởi ' . 
                          ($usedInProperties + $usedInLeases) . ' ' . 
                          ($usedInProperties > 0 ? 'bất động sản' : '') . 
                          ($usedInProperties > 0 && $usedInLeases > 0 ? ' và ' : '') . 
                          ($usedInLeases > 0 ? 'hợp đồng thuê' : '') . '.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $paymentCycle->delete();

            Log::info('Payment cycle deleted', [
                'payment_cycle_id' => $id,
                'organization_id' => $organizationId,
                'deleted_by' => $user->id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đã xóa chu kỳ thanh toán thành công.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting payment cycle: ' . $e->getMessage());
            return response()->json(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete unused payment cycles
     */
    public function deleteUnusedCycles(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('billing.payment_cycle.delete', 'Bạn không có quyền xóa Payment Cycle Settings.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return response()->json(['error' => 'Bạn không thuộc tổ chức nào.'], 403);
        }

        try {
            DB::beginTransaction();

            // Get all payment cycles for this organization
            $allCycles = PaymentCycle::where('organization_id', $organizationId)->get();
            
            $deletedCount = 0;
            $skippedCount = 0;

            foreach ($allCycles as $cycle) {
                // Skip default cycles
                if ($cycle->is_default) {
                    $skippedCount++;
                    continue;
                }

                // Check if cycle is being used
                $usedInProperties = Property::where('payment_cycle_id', $cycle->id)->count();
                $usedInLeases = Lease::where('payment_cycle_id', $cycle->id)->count();

                // If not used, delete it
                if ($usedInProperties == 0 && $usedInLeases == 0) {
                    $cycle->delete();
                    $deletedCount++;
                } else {
                    $skippedCount++;
                }
            }

            Log::info('Unused payment cycles deleted', [
                'organization_id' => $organizationId,
                'deleted_count' => $deletedCount,
                'skipped_count' => $skippedCount,
                'deleted_by' => $user->id,
            ]);

            DB::commit();

            $message = $deletedCount > 0 
                ? "Đã xóa {$deletedCount} chu kỳ thanh toán không sử dụng thành công!"
                : "Không có chu kỳ nào cần xóa.";

            return response()->json([
                'success' => true,
                'message' => $message,
                'deleted_count' => $deletedCount,
                'skipped_count' => $skippedCount
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting unused payment cycles: ' . $e->getMessage());
            return response()->json(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
        }
    }
}

