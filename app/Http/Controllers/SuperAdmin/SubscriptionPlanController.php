<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\PlanFeature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SubscriptionPlanController extends Controller
{
    /**
     * Display a listing of subscription plans.
     */
    public function index(Request $request)
    {
        $query = SubscriptionPlan::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status);
        }

        // Filter by type
        if ($request->filled('type')) {
            if ($request->type === 'custom') {
                $query->where('is_custom', true);
            } else {
                $query->where('is_custom', false);
            }
        }

        // Sort functionality
        $sortBy = $request->get('sort_by', 'sort_order');
        $sortOrder = $request->get('sort_order', 'asc');
        
        $allowedSortFields = ['name', 'price_monthly', 'price_yearly', 'sort_order', 'created_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $plans = $query->withCount('subscriptions')->paginate(15);

        return view('superadmin.subscription-plans.index', compact('plans'));
    }

    /**
     * Show the form for creating a new plan.
     */
    public function create()
    {
        $availableFeatures = config('subscription.available_features', $this->getDefaultFeatures());
        return view('superadmin.subscription-plans.create', compact('availableFeatures'));
    }

    /**
     * Store a newly created plan.
     */
    public function store(Request $request)
    {
        $availableFeatureKeys = $this->getAvailableFeatureKeys();
        
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50|unique:subscription_plans,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'trial_days' => 'required|integer|min:0',
            'is_active' => 'required|in:0,1',
            'is_custom' => 'nullable|in:0,1',
            'sort_order' => 'required|integer|min:0',
            'features' => 'required|array|min:1',
            'features.*.feature_key' => ['required', 'string', 'in:' . implode(',', $availableFeatureKeys)],
            'features.*.feature_name' => 'required|string',
            'features.*.feature_type' => 'required|in:limit,boolean,json',
            'features.*.feature_value' => 'required',
        ], [
            'features.*.feature_key.in' => 'Tính năng :input không được hỗ trợ trong hệ thống.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            $plan = SubscriptionPlan::create([
                'code' => $request->code,
                'name' => $request->name,
                'description' => $request->description,
                'price_monthly' => $request->price_monthly,
                'price_yearly' => $request->price_yearly,
                'currency' => $request->currency,
                'trial_days' => $request->trial_days,
                'is_active' => (bool) $request->is_active,
                'is_custom' => (bool) ($request->is_custom == '1'),
                'sort_order' => $request->sort_order,
                'metadata' => $request->metadata ?? [],
            ]);

            // Validate feature keys are unique within plan
            $featureKeys = array_column($request->features, 'feature_key');
            if (count($featureKeys) !== count(array_unique($featureKeys))) {
                throw new \Exception('Mỗi gói không thể có nhiều tính năng trùng lặp.');
            }
            
            // Create features
            foreach ($request->features as $featureData) {
                // Validate feature type matches config
                $availableFeatures = config('subscription.available_features', $this->getDefaultFeatures());
                $featureConfig = collect($availableFeatures)->firstWhere('key', $featureData['feature_key']);
                
                if ($featureConfig && $featureConfig['type'] !== $featureData['feature_type']) {
                    throw new \Exception("Tính năng {$featureData['feature_key']} phải có type là {$featureConfig['type']}, không phải {$featureData['feature_type']}.");
                }
                
                $featureValue = $this->prepareFeatureValue(
                    $featureData['feature_type'],
                    $featureData['feature_value']
                );

                PlanFeature::create([
                    'plan_id' => $plan->id,
                    'feature_key' => $featureData['feature_key'],
                    'feature_name' => $featureData['feature_name'],
                    'feature_type' => $featureData['feature_type'],
                    'feature_value' => $featureValue,
                ]);
            }

            DB::commit();

            return redirect()->route('superadmin.subscription-plans.index')
                ->with('success', 'Gói dịch vụ đã được tạo thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified plan.
     */
    public function show(SubscriptionPlan $subscriptionPlan)
    {
        $subscriptionPlan->load(['features', 'subscriptions.organization']);
        
        $activeSubscriptionsCount = $subscriptionPlan->subscriptions()
            ->whereIn('status', ['trial', 'active'])
            ->count();

        return view('superadmin.subscription-plans.show', compact('subscriptionPlan', 'activeSubscriptionsCount'));
    }

    /**
     * Show the form for editing the specified plan.
     */
    public function edit(SubscriptionPlan $subscriptionPlan)
    {
        $subscriptionPlan->load('features');
        $availableFeatures = config('subscription.available_features', $this->getDefaultFeatures());
        
        return view('superadmin.subscription-plans.edit', compact('subscriptionPlan', 'availableFeatures'));
    }

    /**
     * Update the specified plan.
     */
    public function update(Request $request, SubscriptionPlan $subscriptionPlan)
    {
        
        $availableFeatureKeys = $this->getAvailableFeatureKeys();
        
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50|unique:subscription_plans,code,' . $subscriptionPlan->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'trial_days' => 'required|integer|min:0',
            'is_active' => 'required|in:0,1',
            'is_custom' => 'nullable|in:0,1',
            'sort_order' => 'required|integer|min:0',
            'features' => 'required|array|min:1',
            'features.*.feature_key' => ['required', 'string', 'in:' . implode(',', $availableFeatureKeys)],
            'features.*.feature_name' => 'required|string',
            'features.*.feature_type' => 'required|in:limit,boolean,json',
            'features.*.feature_value' => 'required',
        ], [
            'features.*.feature_key.in' => 'Tính năng :input không được hỗ trợ trong hệ thống.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            $subscriptionPlan->update([
                'code' => $request->code,
                'name' => $request->name,
                'description' => $request->description,
                'price_monthly' => $request->price_monthly,
                'price_yearly' => $request->price_yearly,
                'currency' => $request->currency,
                'trial_days' => $request->trial_days,
                'is_active' => (bool) $request->is_active,
                'is_custom' => (bool) ($request->is_custom == '1'),
                'sort_order' => $request->sort_order,
                'metadata' => $request->metadata ?? $subscriptionPlan->metadata,
            ]);

            // Delete existing features and recreate
            // Use delete() instead of delete() to ensure soft delete if needed
            $subscriptionPlan->features()->delete();
            
            // Validate feature keys are unique within plan
            $featureKeys = array_column($request->features, 'feature_key');
            if (count($featureKeys) !== count(array_unique($featureKeys))) {
                throw new \Exception('Mỗi gói không thể có nhiều tính năng trùng lặp.');
            }

            foreach ($request->features as $featureData) {
                // Validate feature type matches config
                $availableFeatures = config('subscription.available_features', $this->getDefaultFeatures());
                $featureConfig = collect($availableFeatures)->firstWhere('key', $featureData['feature_key']);
                
                if ($featureConfig && $featureConfig['type'] !== $featureData['feature_type']) {
                    throw new \Exception("Tính năng {$featureData['feature_key']} phải có type là {$featureConfig['type']}, không phải {$featureData['feature_type']}.");
                }
                
                $featureValue = $this->prepareFeatureValue(
                    $featureData['feature_type'],
                    $featureData['feature_value']
                );

                PlanFeature::create([
                    'plan_id' => $subscriptionPlan->id,
                    'feature_key' => $featureData['feature_key'],
                    'feature_name' => $featureData['feature_name'],
                    'feature_type' => $featureData['feature_type'],
                    'feature_value' => $featureValue,
                ]);
            }

            DB::commit();

            return redirect()->route('superadmin.subscription-plans.index')
                ->with('success', 'Gói dịch vụ đã được cập nhật thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified plan.
     */
    public function destroy(SubscriptionPlan $subscriptionPlan)
    {
        
        try {
            // Check if plan has active subscriptions
            $activeCount = $subscriptionPlan->subscriptions()
                ->whereIn('status', ['trial', 'active'])
                ->count();

            if ($activeCount > 0) {
                return redirect()->back()
                    ->with('error', 'Không thể xóa gói dịch vụ đang có tổ chức sử dụng.');
            }

            DB::beginTransaction();

            $subscriptionPlan->features()->delete();
            $subscriptionPlan->delete();

            DB::commit();

            return redirect()->route('superadmin.subscription-plans.index')
                ->with('success', 'Gói dịch vụ đã được xóa thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Toggle plan status.
     */
    public function toggleStatus(SubscriptionPlan $subscriptionPlan)
    {
        try {
            $subscriptionPlan->update(['is_active' => !$subscriptionPlan->is_active]);

            return redirect()->back()
                ->with('success', 'Trạng thái gói dịch vụ đã được cập nhật!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Duplicate a plan to create custom plan.
     */
    public function duplicate(Request $request, SubscriptionPlan $subscriptionPlan)
    {
        try {
            DB::beginTransaction();

            $newPlan = $subscriptionPlan->replicate();
            $newPlan->code = $subscriptionPlan->code . '_COPY_' . time();
            $newPlan->name = $subscriptionPlan->name . ' (Copy)';
            $newPlan->is_custom = true;
            $newPlan->save();

            // Duplicate features
            foreach ($subscriptionPlan->features as $feature) {
                $newFeature = $feature->replicate();
                $newFeature->plan_id = $newPlan->id;
                $newFeature->save();
            }

            DB::commit();

            return redirect()->route('superadmin.subscription-plans.edit', $newPlan->id)
                ->with('success', 'Gói dịch vụ đã được nhân bản thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Prepare feature value based on type.
     */
    protected function prepareFeatureValue(string $type, $value): array
    {
        switch ($type) {
            case 'limit':
                return ['limit' => (int) $value];
            case 'boolean':
                return ['enabled' => (bool) $value];
            case 'json':
                return is_array($value) ? $value : json_decode($value, true);
            default:
                return ['value' => $value];
        }
    }

    /**
     * Get default available features from config.
     * Falls back to hardcoded list if config is not available.
     */
    protected function getDefaultFeatures(): array
    {
        $configFeatures = config('subscription.available_features', []);
        
        if (!empty($configFeatures)) {
            // Return features from config (only key, name, type)
            return array_map(function ($feature) {
                return [
                    'key' => $feature['key'],
                    'name' => $feature['name'],
                    'type' => $feature['type'],
                ];
            }, $configFeatures);
        }
        
        // Fallback to hardcoded list if config not available
        return [
            ['key' => 'max_properties', 'name' => 'Số lượng bất động sản tối đa', 'type' => 'limit'],
            ['key' => 'max_units', 'name' => 'Số lượng đơn vị tối đa', 'type' => 'limit'],
            ['key' => 'max_users', 'name' => 'Số lượng người dùng tối đa', 'type' => 'limit'],
            ['key' => 'max_leases', 'name' => 'Số lượng hợp đồng thuê tối đa', 'type' => 'limit'],
            ['key' => 'enable_reports', 'name' => 'Báo cáo nâng cao', 'type' => 'boolean'],
            ['key' => 'enable_webhooks', 'name' => 'Webhooks', 'type' => 'boolean'],
        ];
    }

    /**
     * Get available feature keys from config.
     * Used for validation to ensure only valid feature keys are used.
     */
    protected function getAvailableFeatureKeys(): array
    {
        $features = config('subscription.available_features', $this->getDefaultFeatures());
        return array_column($features, 'key');
    }
}

