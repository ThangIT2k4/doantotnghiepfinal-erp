<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionInvoice;
use App\Models\OrganizationSubscription;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionInvoiceController extends Controller
{
    protected $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Display a listing of subscription invoices.
     */
    public function index(Request $request)
    {
        $query = SubscriptionInvoice::with(['subscription.plan', 'subscription.organization']);

        // Search by invoice number or organization name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('subscription.organization', function($orgQuery) use ($search) {
                      $orgQuery->where('name', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by organization
        if ($request->filled('organization_id')) {
            $query->whereHas('subscription', function($q) use ($request) {
                $q->where('organization_id', $request->organization_id);
            });
        }

        // Filter by plan
        if ($request->filled('plan_id')) {
            $query->whereHas('subscription', function($q) use ($request) {
                $q->where('plan_id', $request->plan_id);
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        $allowedSortFields = ['invoice_number', 'amount', 'status', 'due_date', 'created_at', 'paid_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $invoices = $query->paginate(15);

        // Get filter options
        $organizations = \App\Models\Organization::active()->orderBy('name')->get();
        $plans = \App\Models\SubscriptionPlan::active()->orderBy('name')->get();

        return view('superadmin.subscription-invoices.index', compact('invoices', 'organizations', 'plans'));
    }

    /**
     * Display the specified invoice.
     */
    public function show(SubscriptionInvoice $subscriptionInvoice)
    {
        $subscriptionInvoice->load([
            'subscription.plan', 
            'subscription.organization',
            'documents' => function($q) {
                // Load tất cả documents, view sẽ filter ảnh
                $q->orderBy('sort_order')
                  ->orderBy('created_at');
            }
        ]);
        
        return view('superadmin.subscription-invoices.show', compact('subscriptionInvoice'));
    }

    /**
     * Show the form for editing the specified invoice.
     */
    public function edit(SubscriptionInvoice $subscriptionInvoice)
    {
        $subscriptionInvoice->load(['subscription.plan', 'subscription.organization']);
        
        return view('superadmin.subscription-invoices.edit', compact('subscriptionInvoice'));
    }

    /**
     * Update the specified invoice.
     */
    public function update(Request $request, SubscriptionInvoice $subscriptionInvoice)
    {
        $request->validate([
            'status' => 'required|in:pending,paid,failed,refunded',
            'payment_method' => 'nullable|string|max:255',
            'gateway_transaction_id' => 'nullable|string|max:255',
            'paid_at' => 'nullable|date',
            'due_date' => 'nullable|date',
        ]);

        try {
            DB::beginTransaction();

            $oldStatus = $subscriptionInvoice->status;
            $newStatus = $request->status;

            // Cập nhật invoice
            $subscriptionInvoice->update([
                'status' => $newStatus,
                'payment_method' => $request->payment_method ?? $subscriptionInvoice->payment_method,
                'gateway_transaction_id' => $request->gateway_transaction_id ?? $subscriptionInvoice->gateway_transaction_id,
                'paid_at' => $newStatus === 'paid' ? ($request->paid_at ?: now()) : null,
                'due_date' => $request->due_date ?? $subscriptionInvoice->due_date,
            ]);

            // Observer sẽ tự động kích hoạt subscription khi status chuyển sang paid

            DB::commit();

            return redirect()->route('superadmin.subscription-invoices.show', $subscriptionInvoice)
                ->with('success', 'Hóa đơn đã được cập nhật thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating subscription invoice: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Mark invoice as paid (quick action)
     */
    public function markAsPaid(Request $request, SubscriptionInvoice $subscriptionInvoice)
    {
        if ($subscriptionInvoice->status === 'paid') {
            return redirect()->back()
                ->with('error', 'Hóa đơn này đã được thanh toán.');
        }

        try {
            DB::beginTransaction();

            $subscriptionInvoice->update([
                'status' => 'paid',
                'paid_at' => now(),
                'payment_method' => $request->payment_method ?? $subscriptionInvoice->payment_method ?? 'manual',
                'gateway_transaction_id' => $request->gateway_transaction_id ?? $subscriptionInvoice->gateway_transaction_id,
            ]);

            // Observer sẽ tự động kích hoạt subscription khi status chuyển sang paid

            DB::commit();

            return redirect()->back()
                ->with('success', 'Hóa đơn đã được đánh dấu là đã thanh toán và subscription đã được kích hoạt!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error marking invoice as paid: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }


    /**
     * Remove the specified invoice (soft delete).
     */
    public function destroy(SubscriptionInvoice $subscriptionInvoice)
    {
        try {
            // Chỉ cho phép xóa invoice ở trạng thái pending hoặc failed
            if (!in_array($subscriptionInvoice->status, ['pending', 'failed'])) {
                return redirect()->back()
                    ->with('error', 'Chỉ có thể xóa hóa đơn ở trạng thái chờ thanh toán hoặc thất bại.');
            }

            $subscriptionInvoice->delete();

            return redirect()->route('superadmin.subscription-invoices.index')
                ->with('success', 'Hóa đơn đã được xóa thành công!');

        } catch (\Exception $e) {
            Log::error('Error deleting subscription invoice: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }
}

