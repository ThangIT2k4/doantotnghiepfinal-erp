<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\SubscriptionPlan;
use App\Models\OrganizationSubscription;
use App\Services\Subscription\SubscriptionService;
use App\Services\Subscription\PlanLimitChecker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrganizationSubscriptionController extends Controller
{
    protected $subscriptionService;
    protected $limitChecker;

    public function __construct(
        SubscriptionService $subscriptionService,
        PlanLimitChecker $limitChecker
    ) {
        $this->subscriptionService = $subscriptionService;
        $this->limitChecker = $limitChecker;
    }

    /**
     * Display a listing of all subscriptions.
     */
    public function index(Request $request)
    {
        $query = OrganizationSubscription::with(['organization', 'plan']);

        // Search by organization name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('organization', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by plan
        if ($request->filled('plan_id')) {
            $query->where('plan_id', $request->plan_id);
        }

        // Filter by payment cycle
        if ($request->filled('payment_cycle')) {
            $query->where('payment_cycle', $request->payment_cycle);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        $query->orderBy($sortBy, $sortOrder);

        $subscriptions = $query->paginate(15);
        $plans = SubscriptionPlan::active()->get();

        return view('superadmin.organization-subscriptions.index', compact('subscriptions', 'plans'));
    }

    /**
     * Show subscription details for an organization.
     */
    public function show(Organization $organization)
    {
        $organization->load(['activeSubscription.plan.features', 'activeSubscription.invoices']);
        
        $subscription = $organization->activeSubscription;
        $usageStats = [];
        
        if ($subscription) {
            $usageStats = $this->limitChecker->getUsageWithLimits($organization);
        }

        $allSubscriptions = $organization->subscriptions()
            ->with('plan')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('superadmin.organization-subscriptions.show', compact(
            'organization',
            'subscription',
            'usageStats',
            'allSubscriptions'
        ));
    }

    /**
     * Show form to assign a plan to organization.
     */
    public function assignPlan(Organization $organization)
    {
        $plans = SubscriptionPlan::active()->with('features')->orderBy('sort_order')->get();
        $currentSubscription = $organization->activeSubscription;

        return view('superadmin.organization-subscriptions.assign', compact(
            'organization',
            'plans',
            'currentSubscription'
        ));
    }

    /**
     * Store plan assignment.
     */
    public function storeAssignment(Request $request, Organization $organization)
    {
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|exists:subscription_plans,id',
            'payment_cycle' => 'required|in:monthly,yearly',
            'payment_gateway' => 'required|in:vnpay,momo,sepay,manual',
            'auto_renew' => 'boolean',
            'start_trial' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $plan = SubscriptionPlan::findOrFail($request->plan_id);

            if (!$plan->is_active) {
                return redirect()->back()
                    ->with('error', 'Gói dịch vụ này hiện không khả dụng.')
                    ->withInput();
            }

            // Admin gán gói: nếu chọn start_trial = true, bỏ qua kiểm tra canUseTrial()
            // Admin có quyền gán trial cho bất kỳ organization nào
            $startTrial = $request->boolean('start_trial', false);
            $forceTrial = $startTrial; // Admin gán gói → force trial nếu chọn
            
            $subscription = $this->subscriptionService->assignPlan(
                $organization,
                $plan,
                $request->payment_cycle,
                $request->boolean('auto_renew', false),
                $startTrial,
                $request->payment_gateway,
                $forceTrial // Bỏ qua kiểm tra canUseTrial() khi admin gán gói
            );

            return redirect()->route('superadmin.organizations.subscription.show', $organization->id)
                ->with('success', 'Đã gán gói dịch vụ cho tổ chức thành công!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Cancel a subscription.
     */
    public function cancelSubscription(Request $request, Organization $organization)
    {
        $subscription = $organization->activeSubscription;

        if (!$subscription) {
            return redirect()->back()
                ->with('error', 'Tổ chức không có gói dịch vụ nào đang hoạt động.');
        }

        $validator = Validator::make($request->all(), [
            'cancel_immediately' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        try {
            $immediately = $request->boolean('cancel_immediately', false);
            
            $this->subscriptionService->cancelSubscription($subscription, $immediately);

            $message = $immediately 
                ? 'Đã hủy gói dịch vụ ngay lập tức.'
                : 'Đã đặt lịch hủy gói dịch vụ vào cuối chu kỳ hiện tại.';

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Extend subscription manually.
     */
    public function extendSubscription(Request $request, Organization $organization)
    {
        $subscription = $organization->activeSubscription;

        if (!$subscription) {
            return redirect()->back()
                ->with('error', 'Tổ chức không có gói dịch vụ nào đang hoạt động.');
        }

        $validator = Validator::make($request->all(), [
            'extend_days' => 'required|integer|min:1|max:365',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        try {
            $this->subscriptionService->extendSubscription($subscription, $request->extend_days);

            return redirect()->back()
                ->with('success', "Đã gia hạn gói dịch vụ thêm {$request->extend_days} ngày.");

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Activate subscription (convert from trial or reactivate).
     */
    public function activateSubscription(Request $request, Organization $organization)
    {
        $subscription = $organization->activeSubscription;

        if (!$subscription) {
            return redirect()->back()
                ->with('error', 'Tổ chức không có gói dịch vụ nào.');
        }

        try {
            $this->subscriptionService->activateSubscription($subscription);

            return redirect()->back()
                ->with('success', 'Đã kích hoạt gói dịch vụ thành công!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Renew subscription.
     */
    public function renewSubscription(Request $request, Organization $organization)
    {
        $subscription = $organization->subscription;

        if (!$subscription) {
            return redirect()->back()
                ->with('error', 'Tổ chức không có gói dịch vụ nào.');
        }

        try {
            $this->subscriptionService->renewSubscription($subscription);

            return redirect()->back()
                ->with('success', 'Đã gia hạn gói dịch vụ thành công!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Show invoices for a subscription.
     */
    public function invoices(Organization $organization)
    {
        $subscription = $organization->subscription;

        if (!$subscription) {
            return redirect()->back()
                ->with('error', 'Tổ chức không có gói dịch vụ nào.');
        }

        $invoices = $subscription->invoices()
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('superadmin.organization-subscriptions.invoices', compact(
            'organization',
            'subscription',
            'invoices'
        ));
    }
}

