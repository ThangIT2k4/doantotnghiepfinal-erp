<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\Subscription\PlanLimitChecker;

class CheckSubscriptionLimits
{
    protected $limitChecker;

    public function __construct(PlanLimitChecker $limitChecker)
    {
        $this->limitChecker = $limitChecker;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $limitType = null)
    {
        // Get organization from authenticated user
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        // Get organization from session or user's first organization
        $organizationId = session('current_organization_id') ?? $this->getUserOrganization($user);
        
        if (!$organizationId) {
            return $this->denyAccess($request, 'Bạn không thuộc tổ chức nào.');
        }

        $organization = \App\Models\Organization::find($organizationId);
        
        if (!$organization) {
            return $this->denyAccess($request, 'Tổ chức không tồn tại.');
        }

        // Check if organization has active subscription
        if (!$organization->hasActiveSubscription()) {
            return $this->denyAccess($request, 'Tổ chức chưa có gói dịch vụ hoặc gói đã hết hạn.');
        }

        // If specific limit type is provided, check it
        if ($limitType) {
            $canProceed = $this->checkSpecificLimit($organization, $limitType);
            
            if (!$canProceed) {
                return $this->denyAccess(
                    $request, 
                    "Bạn đã đạt giới hạn {$this->getLimitLabel($limitType)} của gói dịch vụ hiện tại."
                );
            }
        }

        return $next($request);
    }

    /**
     * Check specific limit type.
     */
    protected function checkSpecificLimit($organization, string $limitType): bool
    {
        switch ($limitType) {
            case 'property':
                return $this->limitChecker->canAddProperty($organization);
            case 'unit':
                return $this->limitChecker->canAddUnit($organization);
            case 'user':
                return $this->limitChecker->canAddUser($organization);
            case 'lease':
                return $this->limitChecker->canAddLease($organization);
            default:
                return true;
        }
    }

    /**
     * Get limit label for error message.
     */
    protected function getLimitLabel(string $limitType): string
    {
        $labels = [
            'property' => 'số lượng bất động sản',
            'unit' => 'số lượng đơn vị',
            'user' => 'số lượng người dùng',
            'lease' => 'số lượng hợp đồng thuê',
        ];

        return $labels[$limitType] ?? $limitType;
    }

    /**
     * Deny access with appropriate response.
     */
    protected function denyAccess(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'error_type' => 'subscription_limit',
            ], 403);
        }

        return redirect()->back()
            ->with('error', $message)
            ->with('subscription_limit_exceeded', true);
    }

    /**
     * Get user's organization ID.
     */
    protected function getUserOrganization($user)
    {
        $orgUser = \Illuminate\Support\Facades\DB::table('organization_users')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        return $orgUser ? $orgUser->organization_id : null;
    }
}

