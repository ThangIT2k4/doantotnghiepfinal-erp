<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SubscriptionPlan;
use App\Models\Lead;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class IndexController extends Controller
{
    /**
     * Display the home page (index.blade.php) with subscription plans
     */
    public function index()
    {
        try {
            // Get active subscription plans (non-custom, sorted by sort_order)
            $subscriptionPlans = SubscriptionPlan::where('is_active', true)
                ->where('is_custom', false)
                ->orderBy('sort_order', 'asc')
                ->orderBy('price_monthly', 'asc')
                ->get()
                ->map(function ($plan) {
                    return [
                        'id' => $plan->id,
                        'code' => $plan->code,
                        'name' => $plan->name,
                        'description' => $plan->description,
                        'price_monthly' => $plan->price_monthly,
                        'price_yearly' => $plan->price_yearly,
                        'currency' => $plan->currency,
                        'trial_days' => $plan->trial_days,
                        'features' => $plan->features()->get()->map(function ($feature) {
                            // Get formatted value based on feature type
                            $value = null;
                            if ($feature->feature_type === 'limit') {
                                $limit = $feature->feature_value['limit'] ?? 0;
                                if ($limit == -1) {
                                    $value = '∞';
                                } else {
                                    $value = $limit;
                                }
                            } elseif ($feature->feature_type === 'boolean') {
                                $enabled = $feature->feature_value['enabled'] ?? false;
                                if (!$enabled) {
                                    // Skip disabled boolean features
                                    return null;
                                }
                                $value = null; // Don't show value for enabled boolean features, just show the name
                            } else {
                                // For JSON type, try to format nicely
                                $value = is_array($feature->feature_value) 
                                    ? json_encode($feature->feature_value, JSON_UNESCAPED_UNICODE)
                                    : $feature->feature_value;
                            }
                            
                            return [
                                'key' => $feature->feature_key,
                                'name' => $feature->feature_name,
                                'value' => $value,
                                'type' => $feature->feature_type,
                            ];
                        })->filter(function ($feature) {
                            // Filter out null values (disabled boolean features)
                            return $feature !== null;
                        }),
                    ];
                });

            return view('index', compact('subscriptionPlans'));
        } catch (\Exception $e) {
            // Log error if needed
            if (config('app.debug')) {
                Log::error('Error in IndexController@index: ' . $e->getMessage());
            }
            
            // Return view with empty plans in case of error
            return view('index', [
                'subscriptionPlans' => collect([])
            ]);
        }
    }

    /**
     * Handle trial contact form submission
     */
    public function submitTrialContact(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:150',
                'phone' => 'required|string|max:30',
                'email' => 'required|email|max:150',
                'plan_interest' => 'nullable|string|max:255',
                'note' => 'nullable|string|max:1000',
            ], [
                'name.required' => 'Vui lòng nhập họ và tên',
                'phone.required' => 'Vui lòng nhập số điện thoại',
                'email.required' => 'Vui lòng nhập email',
                'email.email' => 'Email không hợp lệ',
            ]);

            if ($validator->fails()) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Vui lòng kiểm tra lại thông tin',
                        'errors' => $validator->errors()
                    ], 422);
                }
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            // Create lead with organization_id = 1 (main organization)
            $lead = Lead::create([
                'organization_id' => 1,
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'source' => 'trial_contact',
                'status' => 'new',
                'note' => $this->formatNote($request->plan_interest, $request->note),
            ]);

            Log::info('Trial contact lead created', [
                'lead_id' => $lead->id,
                'name' => $lead->name,
                'email' => $lead->email,
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Cảm ơn bạn đã liên hệ! Chúng tôi sẽ liên hệ lại sớm nhất có thể.',
                ]);
            }

            return redirect()->back()
                ->with('success', 'Cảm ơn bạn đã liên hệ! Chúng tôi sẽ liên hệ lại sớm nhất có thể.');

        } catch (\Exception $e) {
            Log::error('Error submitting trial contact form: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra. Vui lòng thử lại sau.'
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra. Vui lòng thử lại sau.')
                ->withInput();
        }
    }

    /**
     * Format note from plan interest and additional note
     */
    private function formatNote($planInterest, $note)
    {
        $parts = [];
        
        if ($planInterest) {
            $parts[] = "Gói quan tâm: {$planInterest}";
        }
        
        if ($note) {
            $parts[] = "Ghi chú: {$note}";
        }
        
        return !empty($parts) ? implode("\n", $parts) : null;
    }
}

