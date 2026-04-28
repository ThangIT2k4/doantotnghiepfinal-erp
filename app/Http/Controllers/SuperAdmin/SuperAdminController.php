<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SuperAdminController extends Controller
{
    /**
     * Display the super admin dashboard.
     */
    public function index()
    {
        // Clear cache to ensure fresh data (for debugging)
        Cache::forget('superadmin_dashboard_data');
        
        // Get comprehensive dashboard data with caching
        $dashboardData = $this->getDashboardData();
        
        // Log dashboard data for debugging
        Log::info('SuperAdmin Dashboard Data:', [
            'totalOrganizations' => $dashboardData['totalOrganizations'] ?? 0,
            'newOrganizationsThisMonth' => $dashboardData['newOrganizationsThisMonth'] ?? 0,
            'totalUsers' => $dashboardData['totalUsers'] ?? 0,
            'newUsersThisMonth' => $dashboardData['newUsersThisMonth'] ?? 0,
            'monthlyRecurringRevenue' => $dashboardData['monthlyRecurringRevenue'] ?? 0,
            'mrrGrowthRate' => $dashboardData['mrrGrowthRate'] ?? 0,
            'churnRate' => $dashboardData['churnRate'] ?? 0,
            'averageRevenuePerUser' => $dashboardData['averageRevenuePerUser'] ?? 0,
            'customerLifetimeValue' => $dashboardData['customerLifetimeValue'] ?? 0,
            'customerAcquisitionCost' => $dashboardData['customerAcquisitionCost'] ?? 0,
            'ltvCacRatio' => $dashboardData['ltvCacRatio'] ?? 0,
        ]);
        
        // Debug: Check actual database counts
        try {
            $orgCount = DB::table('organizations')->whereNull('deleted_at')->count();
            $userCount = DB::table('users')->whereNull('deleted_at')->count();
            $leaseCount = DB::table('leases')->whereNull('deleted_at')->where('status', 'active')->count();
            $mrrFromLeases = DB::table('leases')->whereNull('deleted_at')->where('status', 'active')->sum('rent_amount');
            
            Log::info('SuperAdmin Database Debug:', [
                'organizations_count' => $orgCount,
                'users_count' => $userCount,
                'active_leases_count' => $leaseCount,
                'mrr_from_leases' => $mrrFromLeases,
            ]);
        } catch (\Exception $e) {
            Log::error('Error checking database counts: ' . $e->getMessage());
        }
        
        return view('superadmin.dashboard', compact('dashboardData'));
    }

    /**
     * Get comprehensive dashboard data.
     */
    private function getDashboardData()
    {
        $cacheKey = 'superadmin_dashboard_data';
        
        return Cache::remember($cacheKey, 300, function () {
            return [
                // Primary SaaS Metrics
                'totalOrganizations' => $this->getTotalOrganizations(),
                'newOrganizationsThisMonth' => $this->getNewOrganizationsThisMonth(),
                'totalUsers' => $this->getTotalUsers(),
                'newUsersThisMonth' => $this->getNewUsersThisMonth(),
                'totalSubscriptions' => $this->getTotalSubscriptions(),
                'activeSubscriptions' => $this->getActiveSubscriptions(),
                'trialSubscriptions' => $this->getTrialSubscriptions(),
                
                // Secondary SaaS Metrics
                'totalSubscriptionPlans' => $this->getTotalSubscriptionPlans(),
                'activeSubscriptionPlans' => $this->getActiveSubscriptionPlans(),
                'totalSubscriptionInvoices' => $this->getTotalSubscriptionInvoices(),
                'paidSubscriptionInvoices' => $this->getPaidSubscriptionInvoices(),
                'pendingSubscriptionInvoices' => $this->getPendingSubscriptionInvoices(),
                
                // Organization Status
                'activeOrganizations' => $this->getActiveOrganizations(),
                'inactiveOrganizations' => $this->getInactiveOrganizations(),
                'newOrganizations' => $this->getNewOrganizations(),
                
                // System Health
                'apiResponseTime' => $this->getApiResponseTime(),
                'systemUptime' => $this->getSystemUptime(),
                'activeSessions' => $this->getActiveSessions(),
                'pageLoadTime' => $this->getPageLoadTime(),
                'memoryUsage' => $this->getMemoryUsage(),
                'cpuUsage' => $this->getCpuUsage(),
                
                // Business Health
                'conversionRate' => $this->getConversionRate(),
                'openSupportTickets' => $this->getOpenSupportTickets(),
                'featureRequests' => $this->getFeatureRequests(),
                'customerSatisfaction' => $this->getCustomerSatisfaction(),
                
                // Recent Activities
                'recentActivities' => $this->getRecentActivities(),
                'topOrganizations' => $this->getTopOrganizations(),
                
                // Chart Data
                'systemGrowthChartData' => $this->getSystemGrowthChartData(),
                'subscriptionGrowthChartData' => $this->getSubscriptionGrowthChartData(),
                'userGrowthChartData' => $this->getUserGrowthChartData(),
            ];
        });
    }

    // Primary SaaS Metrics Methods
    private function getTotalOrganizations()
    {
        try {
            // Exclude soft deleted organizations
            return DB::table('organizations')
                ->whereNull('deleted_at')
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getNewOrganizationsThisMonth()
    {
        try {
            // Exclude soft deleted organizations
            return DB::table('organizations')
                ->whereNull('deleted_at')
                ->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getTotalUsers()
    {
        try {
            // Exclude soft deleted users
            return DB::table('users')
                ->whereNull('deleted_at')
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getNewUsersThisMonth()
    {
        try {
            // Exclude soft deleted users
            return DB::table('users')
                ->whereNull('deleted_at')
                ->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getTotalSubscriptions()
    {
        try {
            return DB::table('organization_subscriptions')
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getActiveSubscriptions()
    {
        try {
            return DB::table('organization_subscriptions')
                ->where('status', 'active')
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getTrialSubscriptions()
    {
        try {
            return DB::table('organization_subscriptions')
                ->where('status', 'trial')
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getTotalSubscriptionPlans()
    {
        try {
            return DB::table('subscription_plans')
                ->whereNull('deleted_at')
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getActiveSubscriptionPlans()
    {
        try {
            return DB::table('subscription_plans')
                ->where('is_active', 1)
                ->whereNull('deleted_at')
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getTotalSubscriptionInvoices()
    {
        try {
            return DB::table('subscription_invoices')
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getPaidSubscriptionInvoices()
    {
        try {
            return DB::table('subscription_invoices')
                ->where('status', 'paid')
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getPendingSubscriptionInvoices()
    {
        try {
            return DB::table('subscription_invoices')
                ->where('status', 'pending')
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getMonthlyRecurringRevenue()
    {
        try {
            // Calculate MRR from actual lease payments only
            // Only use real data from leases, no fallback estimation
            $mrrFromLeases = DB::table('leases')
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->sum('rent_amount');
            
            // Return actual MRR from leases (0 if no active leases)
            return $mrrFromLeases ?? 0;
        } catch (\Exception $e) {
            Log::error('Error calculating MRR: ' . $e->getMessage());
            return 0;
        }
    }

    private function getMrrGrowthRate()
    {
        try {
            $currentMonth = $this->getMonthlyRecurringRevenue();
            $lastMonth = $this->getLastMonthRevenue();
            
            if ($lastMonth == 0) return 0;
            
            return (($currentMonth - $lastMonth) / $lastMonth) * 100;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getLastMonthRevenue()
    {
        try {
            $lastMonth = Carbon::now()->subMonth();
            
            // Calculate MRR from actual lease payments last month only
            // Only use real data from leases, no fallback estimation
            $mrrFromLeases = DB::table('leases')
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->where('created_at', '<=', $lastMonth->endOfMonth())
                ->sum('rent_amount');
            
            // Return actual MRR from leases last month (0 if no active leases)
            return $mrrFromLeases ?? 0;
        } catch (\Exception $e) {
            Log::error('Error calculating last month revenue: ' . $e->getMessage());
            return 0;
        }
    }

    private function getChurnRate()
    {
        try {
            // Exclude soft deleted organizations
            $totalOrgs = DB::table('organizations')
                ->whereNull('deleted_at')
                ->count();
            
            $churnedOrgs = DB::table('organizations')
                ->where('status', 0)
                ->whereNull('deleted_at')
                ->whereMonth('updated_at', Carbon::now()->month)
                ->whereYear('updated_at', Carbon::now()->year)
                ->count();
            
            if ($totalOrgs == 0) return 0;
            
            return ($churnedOrgs / $totalOrgs) * 100;
        } catch (\Exception $e) {
            return 0;
        }
    }

    // Secondary SaaS Metrics Methods
    private function getAverageRevenuePerUser()
    {
        try {
            $mrr = $this->getMonthlyRecurringRevenue();
            $totalUsers = $this->getTotalUsers();
            
            // Only calculate ARPU if we have both MRR and users
            if ($totalUsers == 0 || $mrr == 0) return 0;
            
            return $mrr / $totalUsers;
        } catch (\Exception $e) {
            Log::error('Error calculating ARPU: ' . $e->getMessage());
            return 0;
        }
    }

    private function getCustomerLifetimeValue()
    {
        try {
            $arpu = $this->getAverageRevenuePerUser();
            $churnRate = $this->getChurnRate();
            
            // Only calculate LTV if we have ARPU
            if ($arpu == 0) return 0;
            
            // If no churn, assume 12 months lifetime
            if ($churnRate == 0) return $arpu * 12;
            
            // Calculate LTV based on churn rate
            return $arpu / ($churnRate / 100);
        } catch (\Exception $e) {
            Log::error('Error calculating LTV: ' . $e->getMessage());
            return 0;
        }
    }

    private function getCustomerAcquisitionCost()
    {
        try {
            // Only calculate CAC if we have actual marketing spend data
            // For now, return 0 if we don't have real marketing spend data
            // TODO: Add actual marketing spend tracking to database
            
            // If we have new customers this month, we could calculate CAC
            // But without actual marketing spend data, return 0
            $newCustomers = $this->getNewOrganizationsThisMonth();
            
            if ($newCustomers == 0) return 0;
            
            // Return 0 if we don't have actual marketing spend data
            // Remove hardcoded marketing spend estimation
            return 0;
        } catch (\Exception $e) {
            Log::error('Error calculating CAC: ' . $e->getMessage());
            return 0;
        }
    }

    private function getLtvCacRatio()
    {
        try {
            $ltv = $this->getCustomerLifetimeValue();
            $cac = $this->getCustomerAcquisitionCost();
            
            if ($cac == 0) return 0;
            
            return $ltv / $cac;
        } catch (\Exception $e) {
            return 0;
        }
    }

    // Organization Status Methods
    private function getActiveOrganizations()
    {
        try {
            return DB::table('organizations')
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getInactiveOrganizations()
    {
        try {
            return DB::table('organizations')
                ->where('status', 0)
                ->whereNull('deleted_at')
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getNewOrganizations()
    {
        try {
            return DB::table('organizations')
                ->whereNull('deleted_at')
                ->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    // System Health Methods
    private function getApiResponseTime()
    {
        try {
            // Measure actual API response time
            $startTime = microtime(true);
            DB::table('users')->limit(1)->count(); // Simple query to test
            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000); // Convert to milliseconds
            return max(0, $responseTime); // Ensure non-negative
        } catch (\Exception $e) {
            Log::error('Error calculating API response time: ' . $e->getMessage());
            return 0;
        }
    }

    private function getSystemUptime()
    {
        try {
            // Calculate uptime from system start time if available
            // For now, return actual uptime percentage based on system availability
            // This is a simplified calculation - in production, you'd track actual uptime
            $totalDays = 30; // Assume tracking for last 30 days
            $downtimeMinutes = 0; // Could track actual downtime from logs
            $uptimePercentage = round((($totalDays * 24 * 60 - $downtimeMinutes) / ($totalDays * 24 * 60)) * 100, 1);
            return $uptimePercentage . '%';
        } catch (\Exception $e) {
            Log::error('Error calculating system uptime: ' . $e->getMessage());
            return 'N/A';
        }
    }

    private function getActiveSessions()
    {
        try {
            // Get active sessions (sessions updated in last 30 minutes)
            return DB::table('sessions')
                ->where('last_activity', '>=', now()->subMinutes(30)->timestamp)
                ->count();
        } catch (\Exception $e) {
            Log::error('Error getting active sessions: ' . $e->getMessage());
            return 0;
        }
    }

    private function getPageLoadTime()
    {
        // This would need to be measured on the frontend and sent to backend
        // For now, return 0 as we don't have actual measurement
        // TODO: Implement frontend measurement and store in database
        return 0;
    }

    private function getMemoryUsage()
    {
        try {
            // Get actual PHP memory usage
            $memoryUsage = memory_get_usage(true);
            $memoryLimit = ini_get('memory_limit');
            $memoryLimitBytes = $this->convertToBytes($memoryLimit);
            
            if ($memoryLimitBytes > 0) {
                $percentage = round(($memoryUsage / $memoryLimitBytes) * 100, 1);
                return min(100, max(0, $percentage)); // Clamp between 0 and 100
            }
            
            // Fallback: calculate based on available memory
            return round(($memoryUsage / 1024 / 1024 / 1024) * 100, 1); // Rough estimate in GB
        } catch (\Exception $e) {
            Log::error('Error calculating memory usage: ' . $e->getMessage());
            return 0;
        }
    }

    private function getCpuUsage()
    {
        // PHP doesn't have direct access to CPU usage
        // This would require system-level monitoring or external tools
        // For now, return 0 as we can't measure it directly
        // TODO: Implement system monitoring integration (e.g., New Relic, DataDog)
        return 0;
    }

    /**
     * Convert memory limit string to bytes
     */
    private function convertToBytes($value)
    {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $value = (int) $value;
        
        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }

    // Business Health Methods
    private function getConversionRate()
    {
        try {
            // Calculate conversion rate from leads to leases
            // Conversion rate = (converted leads / total leads) * 100
            $totalLeads = DB::table('leads')
                ->whereNull('deleted_at')
                ->count();
            
            if ($totalLeads == 0) return 0;
            
            // Count converted leads (leads that resulted in leases)
            // We can check leads with status 'converted' or check if lead has associated lease
            $convertedLeads = DB::table('leads')
                ->where('status', 'converted')
                ->whereNull('deleted_at')
                ->count();
            
            // Alternative: Count active leases (which represent converted leads)
            // This gives a more accurate conversion rate
            $activeLeases = DB::table('leases')
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->count();
            
            // Use the higher value to ensure accuracy
            $convertedCount = max($convertedLeads, $activeLeases);
            
            $conversionRate = ($convertedCount / $totalLeads) * 100;
            return round($conversionRate, 1);
        } catch (\Exception $e) {
            Log::error('Error calculating conversion rate: ' . $e->getMessage());
            return 0;
        }
    }

    private function getOpenSupportTickets()
    {
        try {
            // Get open tickets from tickets table (not support_tickets)
            // Tickets with status 'open' or 'in_progress' are considered open
            return DB::table('tickets')
                ->whereIn('status', ['open', 'in_progress'])
                ->whereNull('deleted_at')
                ->count();
        } catch (\Exception $e) {
            // If tickets table doesn't exist, try support_tickets table
            try {
                return DB::table('support_tickets')
                    ->whereIn('status', ['open', 'in_progress'])
                    ->count();
            } catch (\Exception $e2) {
                Log::error('Error getting open support tickets: ' . $e2->getMessage());
                return 0;
            }
        }
    }

    private function getFeatureRequests()
    {
        try {
            // Check if feature_requests table exists
            if (DB::getSchemaBuilder()->hasTable('feature_requests')) {
                return DB::table('feature_requests')
                    ->whereIn('status', ['pending', 'open'])
                    ->count();
            }
            return 0;
        } catch (\Exception $e) {
            Log::error('Error getting feature requests: ' . $e->getMessage());
            return 0;
        }
    }

    private function getCustomerSatisfaction()
    {
        try {
            // Calculate average rating from reviews
            $avgRating = DB::table('reviews')
                ->whereNull('deleted_at')
                ->whereNotNull('overall_rating')
                ->avg('overall_rating');
            
            if ($avgRating) {
                return round($avgRating, 1);
            }
            
            return 0;
        } catch (\Exception $e) {
            Log::error('Error calculating customer satisfaction: ' . $e->getMessage());
            return 0;
        }
    }

    // Recent Activities and Top Organizations
    private function getRecentActivities()
    {
        try {
            // Try to get from audit_logs if exists
            if (DB::getSchemaBuilder()->hasTable('audit_logs')) {
                $activities = DB::table('audit_logs')
                    ->leftJoin('users', 'users.id', '=', 'audit_logs.actor_id')
                    ->leftJoin('user_profiles', 'user_profiles.user_id', '=', 'users.id')
                    ->orderBy('audit_logs.created_at', 'desc')
                    ->limit(5)
                    ->select(
                        'audit_logs.*',
                        DB::raw('COALESCE(user_profiles.full_name, users.email) as actor_name')
                    )
                    ->get();
                
                return $activities->map(function ($activity) {
                    return (object) [
                        'action_type' => $activity->action_type ?? 'unknown',
                        'description' => $activity->description ?? 'Hoạt động hệ thống',
                        'created_at' => \Carbon\Carbon::parse($activity->created_at),
                        'actor_name' => $activity->actor_name ?? 'System',
                    ];
                });
            }
            
            // Fallback: Get recent activities from other tables
            $activities = collect();
            
            // Recent organizations
            $recentOrgs = DB::table('organizations')
                ->orderBy('created_at', 'desc')
                ->limit(3)
                ->get()
                ->map(function ($org) {
                    return (object) [
                        'action_type' => 'created',
                        'description' => "Tổ chức mới: {$org->name}",
                        'created_at' => \Carbon\Carbon::parse($org->created_at),
                        'actor_name' => 'System',
                    ];
                });
            $activities = $activities->merge($recentOrgs);
            
            // Recent users
            $recentUsers = DB::table('users')
                ->leftJoin('user_profiles', 'user_profiles.user_id', '=', 'users.id')
                ->orderBy('users.created_at', 'desc')
                ->limit(2)
                ->select(
                    'users.created_at',
                    DB::raw('COALESCE(user_profiles.full_name, users.email) as user_name')
                )
                ->get()
                ->map(function ($user) {
                    return (object) [
                        'action_type' => 'created',
                        'description' => "Người dùng mới: {$user->user_name}",
                        'created_at' => \Carbon\Carbon::parse($user->created_at),
                        'actor_name' => 'System',
                    ];
                });
            $activities = $activities->merge($recentUsers);
            
            return $activities->sortByDesc('created_at')->take(5);
        } catch (\Exception $e) {
            Log::error('Error getting recent activities: ' . $e->getMessage());
            return collect();
        }
    }

    private function getTopOrganizations()
    {
        try {
            // Exclude soft deleted organizations
            $organizations = DB::table('organizations')
                ->whereNull('deleted_at')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
            
            return $organizations->map(function ($org) {
                // Get users count for this organization
                $usersCount = DB::table('organization_users')
                    ->where('organization_id', $org->id)
                    ->where('status', 'active')
                    ->count();
                
                // Get properties count for this organization
                $propertiesCount = DB::table('properties')
                    ->where('organization_id', $org->id)
                    ->whereNull('deleted_at')
                    ->count();
                
                return (object) [
                    'id' => $org->id,
                    'name' => $org->name,
                    'users_count' => $usersCount,
                    'properties_count' => $propertiesCount,
                    'created_at' => $org->created_at,
                ];
            });
        } catch (\Exception $e) {
            Log::error('Error getting top organizations: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get organization metrics (legacy method).
     */
    private function getOrganizationMetrics()
    {
        try {
            $totalOrgs = DB::table('organizations')->count();
            
            // Active organizations (with recent activity)
            $activeOrgs = DB::table('organizations')
                ->whereExists(function($query) {
                    $query->select(DB::raw(1))
                        ->from('users')
                        ->join('organization_users', 'users.id', '=', 'organization_users.user_id')
                        ->whereColumn('organization_users.organization_id', 'organizations.id')
                        ->where('users.last_login_at', '>=', now()->subDays(30));
                })
                ->count();
            
            // New organizations this month
            $newOrgsThisMonth = DB::table('organizations')
                ->where('created_at', '>=', now()->startOfMonth())
                ->count();
            
            // Organizations with properties
            $orgsWithProperties = DB::table('organizations')
                ->whereExists(function($query) {
                    $query->select(DB::raw(1))
                        ->from('properties')
                        ->whereColumn('properties.organization_id', 'organizations.id');
                })
                ->count();
            
            return [
                'total' => $totalOrgs,
                'active' => $activeOrgs,
                'new_this_month' => $newOrgsThisMonth,
                'with_properties' => $orgsWithProperties,
                'activation_rate' => $totalOrgs > 0 ? round(($activeOrgs / $totalOrgs) * 100, 1) : 0,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting organization metrics: ' . $e->getMessage());
            return [
                'total' => 0,
                'active' => 0,
                'new_this_month' => 0,
                'with_properties' => 0,
                'activation_rate' => 0,
            ];
        }
    }

    /**
     * Get user metrics.
     */
    private function getUserMetrics()
    {
        try {
            $totalUsers = DB::table('users')->count();
            
            // Active users (recent login)
            $activeUsers = DB::table('users')
                ->where('last_login_at', '>=', now()->subDays(30))
                ->count();
            
            // New users this month
            $newUsersThisMonth = DB::table('users')
                ->where('created_at', '>=', now()->startOfMonth())
                ->count();
            
            // Users by role
            $usersByRole = DB::table('users')
                ->join('organization_users', 'users.id', '=', 'organization_users.user_id')
                ->join('roles', 'roles.id', '=', 'organization_users.role_id')
                ->where('organization_users.status', 'active')
                ->select('roles.name', DB::raw('count(*) as count'))
                ->groupBy('roles.id', 'roles.name')
                ->pluck('count', 'name');
            
            return [
                'total' => $totalUsers,
                'active' => $activeUsers,
                'new_this_month' => $newUsersThisMonth,
                'by_role' => $usersByRole,
                'activation_rate' => $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 1) : 0,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting user metrics: ' . $e->getMessage());
            return [
                'total' => 0,
                'active' => 0,
                'new_this_month' => 0,
                'by_role' => [],
                'activation_rate' => 0,
            ];
        }
    }

    /**
     * Get revenue metrics.
     */
    private function getRevenueMetrics()
    {
        try {
            // Total revenue
            $totalRevenue = DB::table('payments')
                ->where('status', 'completed')
                ->sum('amount');
            
            // Monthly Recurring Revenue (MRR)
            $mrr = DB::table('leases')
                ->where('status', 'active')
                ->sum('rent_amount');
            
            // Revenue this month
            $revenueThisMonth = DB::table('payments')
                ->where('status', 'completed')
                ->where('created_at', '>=', now()->startOfMonth())
                ->sum('amount');
            
            // Revenue last month
            $revenueLastMonth = DB::table('payments')
                ->where('status', 'completed')
                ->whereBetween('created_at', [
                    now()->subMonth()->startOfMonth(),
                    now()->subMonth()->endOfMonth()
                ])
                ->sum('amount');
            
            // Revenue growth
            $revenueGrowth = $revenueLastMonth > 0 
                ? round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100, 1)
                : 0;
            
            // Average Revenue Per Organization (ARPO)
            $totalOrgs = DB::table('organizations')->count();
            $arpo = $totalOrgs > 0 ? round($totalRevenue / $totalOrgs, 0) : 0;
            
            return [
                'total' => $totalRevenue,
                'mrr' => $mrr,
                'this_month' => $revenueThisMonth,
                'last_month' => $revenueLastMonth,
                'growth' => $revenueGrowth,
                'arpo' => $arpo,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting revenue metrics: ' . $e->getMessage());
            return [
                'total' => 0,
                'mrr' => 0,
                'this_month' => 0,
                'last_month' => 0,
                'growth' => 0,
                'arpo' => 0,
            ];
        }
    }

    /**
     * Get system metrics.
     */
    private function getSystemMetrics()
    {
        try {
            // Database size
            $dbSize = DB::select("
                SELECT 
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'db_size_mb'
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
            ")[0]->db_size_mb ?? 0;
            
            // Cache hit rate
            $cacheHits = DB::table('cache')->count();
            
            // Active sessions
            $activeSessions = DB::table('sessions')
                ->where('last_activity', '>=', now()->subMinutes(30)->timestamp)
                ->count();
            
            // System health indicators
            $systemHealth = [
                'database_size_mb' => $dbSize,
                'cache_entries' => $cacheHits,
                'active_sessions' => $activeSessions,
                'uptime' => $this->getSystemUptime(),
            ];
            
            return $systemHealth;
        } catch (\Exception $e) {
            Log::error('Error getting system metrics: ' . $e->getMessage());
            return [
                'database_size_mb' => 0,
                'cache_entries' => 0,
                'active_sessions' => 0,
                'uptime' => 'Unknown',
            ];
        }
    }

    /**
     * Get growth metrics.
     */
    private function getGrowthMetrics()
    {
        try {
            // User growth (last 6 months)
            $userGrowth = [];
            for ($i = 5; $i >= 0; $i--) {
                $month = now()->subMonths($i);
                $count = DB::table('users')
                    ->whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->count();
                $userGrowth[] = [
                    'month' => $month->format('M Y'),
                    'count' => $count,
                ];
            }
            
            // Organization growth (last 6 months)
            $orgGrowth = [];
            for ($i = 5; $i >= 0; $i--) {
                $month = now()->subMonths($i);
                $count = DB::table('organizations')
                    ->whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->count();
                $orgGrowth[] = [
                    'month' => $month->format('M Y'),
                    'count' => $count,
                ];
            }
            
            // Revenue growth (last 6 months)
            $revenueGrowth = [];
            for ($i = 5; $i >= 0; $i--) {
                $month = now()->subMonths($i);
                $revenue = DB::table('payments')
                    ->where('status', 'completed')
                    ->whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->sum('amount');
                $revenueGrowth[] = [
                    'month' => $month->format('M Y'),
                    'revenue' => $revenue,
                ];
            }
            
            return [
                'users' => $userGrowth,
                'organizations' => $orgGrowth,
                'revenue' => $revenueGrowth,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting growth metrics: ' . $e->getMessage());
            return [
                'users' => [],
                'organizations' => [],
                'revenue' => [],
            ];
        }
    }


    /**
     * Get MRR Growth Chart Data (last 6 months)
     */
    private function getSystemGrowthChartData()
    {
        try {
            $labels = [];
            $orgsData = [];
            $usersData = [];
            
            for ($i = 5; $i >= 0; $i--) {
                $month = Carbon::now()->subMonths($i);
                $labels[] = $month->format('M Y');
                
                $orgsData[] = DB::table('organizations')
                    ->whereNull('deleted_at')
                    ->where('created_at', '<=', $month->endOfMonth())
                    ->count();
                
                $usersData[] = DB::table('users')
                    ->whereNull('deleted_at')
                    ->where('created_at', '<=', $month->endOfMonth())
                    ->count();
            }
            
            return [
                'labels' => $labels,
                'organizations' => $orgsData,
                'users' => $usersData,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting system growth chart data: ' . $e->getMessage());
            return [
                'labels' => ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6'],
                'organizations' => [0, 0, 0, 0, 0, 0],
                'users' => [0, 0, 0, 0, 0, 0],
            ];
        }
    }

    /**
     * Get Subscription Growth Chart Data (last 6 months)
     */
    private function getSubscriptionGrowthChartData()
    {
        try {
            $labels = [];
            $activeSubscriptionsData = [];
            $trialSubscriptionsData = [];
            
            for ($i = 5; $i >= 0; $i--) {
                $month = Carbon::now()->subMonths($i);
                $labels[] = $month->format('M Y');
                
                $activeSubscriptionsData[] = DB::table('organization_subscriptions')
                    ->where('status', 'active')
                    ->where('created_at', '<=', $month->endOfMonth())
                    ->count();
                
                $trialSubscriptionsData[] = DB::table('organization_subscriptions')
                    ->where('status', 'trial')
                    ->where('created_at', '<=', $month->endOfMonth())
                    ->count();
            }
            
            return [
                'labels' => $labels,
                'active' => $activeSubscriptionsData,
                'trial' => $trialSubscriptionsData,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting subscription growth chart data: ' . $e->getMessage());
            return [
                'labels' => ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6'],
                'active' => [0, 0, 0, 0, 0, 0],
                'trial' => [0, 0, 0, 0, 0, 0],
            ];
        }
    }


    /**
     * Get User Growth Chart Data (last 6 months)
     */
    private function getUserGrowthChartData()
    {
        try {
            $newUsersData = [];
            $retainedUsersData = [];
            
            for ($i = 5; $i >= 0; $i--) {
                $month = Carbon::now()->subMonths($i);
                
                // New users this month
                $newUsers = DB::table('users')
                    ->whereNull('deleted_at')
                    ->whereMonth('created_at', $month->month)
                    ->whereYear('created_at', $month->year)
                    ->count();
                $newUsersData[] = $newUsers;
                
                // Retained users (users who logged in within 30 days of this month)
                $retainedUsers = DB::table('users')
                    ->whereNull('deleted_at')
                    ->where('created_at', '<=', $month->endOfMonth())
                    ->where(function($query) use ($month) {
                        $query->where('last_login_at', '>=', $month->copy()->subDays(30))
                              ->orWhereNotNull('last_login_at');
                    })
                    ->count();
                $retainedUsersData[] = $retainedUsers;
            }
            
            return [
                'newUsers' => $newUsersData,
                'retainedUsers' => $retainedUsersData,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting user growth chart data: ' . $e->getMessage());
            return [
                'newUsers' => [0, 0, 0, 0, 0, 0],
                'retainedUsers' => [0, 0, 0, 0, 0, 0],
            ];
        }
    }

    /**
     * Clear super admin cache.
     */
    public function clearCache()
    {
        Cache::forget('superadmin_dashboard_data');
        Cache::forget('superadmin_saas_metrics');
        
        return response()->json([
            'success' => true,
            'message' => 'Super Admin cache cleared successfully'
        ]);
    }

    /**
     * Display trial leads (leads from trial contact form)
     */
    public function trialLeads(Request $request)
    {
        try {
            // Query trial leads: organization_id = 1 and source = 'trial_contact'
            $query = Lead::where('organization_id', 1)
                ->where('source', 'trial_contact')
                ->whereNull('deleted_at');
            
            // Search functionality
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('note', 'like', "%{$search}%");
                });
            }

            // Filter by status
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Sort
            $sortField = $request->get('sort', 'created_at');
            $sortDirection = $request->get('direction', 'desc');
            $query->orderBy($sortField, $sortDirection);

            $leads = $query->paginate(20);

            // Calculate statistics
            $stats = [
                'total' => Lead::where('organization_id', 1)->where('source', 'trial_contact')->whereNull('deleted_at')->count(),
                'new' => Lead::where('organization_id', 1)->where('source', 'trial_contact')->where('status', 'new')->whereNull('deleted_at')->count(),
                'contacted' => Lead::where('organization_id', 1)->where('source', 'trial_contact')->where('status', 'contacted')->whereNull('deleted_at')->count(),
                'qualified' => Lead::where('organization_id', 1)->where('source', 'trial_contact')->where('status', 'qualified')->whereNull('deleted_at')->count(),
                'converted' => Lead::where('organization_id', 1)->where('source', 'trial_contact')->where('status', 'converted')->whereNull('deleted_at')->count(),
            ];

            return view('superadmin.trial-leads', compact('leads', 'stats'));

        } catch (\Exception $e) {
            Log::error('Error in SuperAdminController@trialLeads: ' . $e->getMessage());
            return redirect()->route('superadmin.dashboard')
                ->with('error', 'Có lỗi xảy ra khi tải danh sách trial leads.');
        }
    }
}
