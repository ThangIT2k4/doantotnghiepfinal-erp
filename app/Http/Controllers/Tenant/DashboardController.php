<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Lease;
use App\Models\Invoice;
use App\Models\Viewing;
use App\Models\Ticket;
use App\Models\Notification;
use App\Models\Review;
use App\Models\Payment;

class DashboardController extends Controller
{
    /**
     * Display the tenant dashboard.
     */
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Eager load userProfile to ensure full_name is available
        $user->load('userProfile');
        
        // Clear cache temporarily to ensure fresh data
        $cacheKey = "dashboard_data_tenant_{$user->id}";
        Cache::forget($cacheKey);
        
        // Get dashboard data
        $dashboardData = $this->getDashboardData($user->id);

        // Debug: Log dashboard data to ensure it's being generated
        Log::info('Tenant Dashboard Data', [
            'user_id' => $user->id,
            'has_stats' => isset($dashboardData['stats']),
            'has_quickStats' => isset($dashboardData['quickStats']),
            'stats_keys' => isset($dashboardData['stats']) ? array_keys($dashboardData['stats']) : [],
            'quickStats_keys' => isset($dashboardData['quickStats']) ? array_keys($dashboardData['quickStats']) : [],
            'stats_appointments_total' => $dashboardData['stats']['appointments']['total'] ?? 'NOT_SET',
            'quickStats_completed_appointments' => $dashboardData['quickStats']['completed_appointments'] ?? 'NOT_SET',
            'user_full_name' => $user->full_name ?? 'NOT_SET',
        ]);

        return view('tenant.dashboard', compact('dashboardData', 'user'));
    }

    /**
     * Get all dashboard data with caching.
     */
    private function getDashboardData($tenantId)
    {
        // Cache key with tenant ID
        $cacheKey = "dashboard_data_tenant_{$tenantId}";
        
        // Cache for 5 minutes
        try {
            $data = Cache::remember($cacheKey, 300, function () use ($tenantId) {
                return [
                    'stats' => $this->getStats($tenantId),
                    'currentRental' => $this->getCurrentRental($tenantId),
                    'upcomingEvents' => $this->getUpcomingEvents($tenantId),
                    'recentActivities' => $this->getRecentActivities($tenantId),
                    'quickStats' => $this->getQuickStats($tenantId),
                ];
            });

            // Ensure all required keys exist
            if (!isset($data['stats'])) {
                $data['stats'] = $this->getStats($tenantId);
            }
            if (!isset($data['quickStats'])) {
                $data['quickStats'] = $this->getQuickStats($tenantId);
            }
            if (!isset($data['currentRental'])) {
                $data['currentRental'] = $this->getCurrentRental($tenantId);
            }
            if (!isset($data['upcomingEvents'])) {
                $data['upcomingEvents'] = $this->getUpcomingEvents($tenantId);
            }
            if (!isset($data['recentActivities'])) {
                $data['recentActivities'] = $this->getRecentActivities($tenantId);
            }

            return $data;
        } catch (\Exception $e) {
            Log::error('Error getting dashboard data: ' . $e->getMessage(), [
                'tenant_id' => $tenantId,
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Return fallback data structure
            return [
                'stats' => $this->getStats($tenantId),
                'currentRental' => $this->getCurrentRental($tenantId),
                'upcomingEvents' => $this->getUpcomingEvents($tenantId),
                'recentActivities' => $this->getRecentActivities($tenantId),
                'quickStats' => $this->getQuickStats($tenantId),
            ];
        }
    }

    /**
     * Get key statistics for dashboard.
     */
    private function getStats($tenantId)
    {
        try {
            // Appointments (Viewings) count
            $appointmentsCount = Viewing::where('tenant_id', $tenantId)
                ->whereNull('deleted_at')
                ->count();

            // Appointments this week
            $appointmentsThisWeek = Viewing::where('tenant_id', $tenantId)
                ->whereBetween('schedule_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->whereNull('deleted_at')
                ->count();

            // Get accessible lease IDs (user as tenant or resident)
            $accessibleLeaseIds = Lease::getAccessibleLeaseIds($tenantId);
            
            // Contracts (Leases) count
            $contractsCount = Lease::whereIn('id', $accessibleLeaseIds)
                ->whereNull('deleted_at')
                ->count();

            // Active contracts
            $activeContractsCount = Lease::whereIn('id', $accessibleLeaseIds)
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->count();

            // Invoices count
            $invoicesCount = Invoice::whereIn('lease_id', $accessibleLeaseIds)
                ->whereNull('deleted_at')
                ->count();

            // Invoices this month
            $invoicesThisMonth = Invoice::whereIn('lease_id', $accessibleLeaseIds)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->whereNull('deleted_at')
                ->count();

            // Unread notifications count
            $unreadNotificationsCount = Notification::where('to_user_id', $tenantId)
                ->where('channel_id', 1) // in_app notifications
                ->where('status', 'queued') // unread
                ->count();

            // Notifications today
            $notificationsToday = Notification::where('to_user_id', $tenantId)
                ->where('channel_id', 1)
                ->whereDate('created_at', today())
                ->count();

            // Tickets count
            $ticketsCount = Ticket::whereIn('lease_id', $accessibleLeaseIds)
                ->whereNull('deleted_at')
                ->count();

            // Tickets in progress
            $ticketsInProgress = Ticket::whereIn('lease_id', $accessibleLeaseIds)
                ->whereIn('status', ['open', 'in_progress'])
                ->whereNull('deleted_at')
                ->count();

            // Reviews count
            $reviewsCount = Review::where('tenant_id', $tenantId)
                ->whereNull('deleted_at')
                ->count();

            // Reviews pending (leases without reviews)
            $reviewsPending = Lease::whereIn('id', $accessibleLeaseIds)
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->whereDoesntHave('review')
                ->count();

            return [
                'appointments' => [
                    'total' => $appointmentsCount,
                    'this_week' => $appointmentsThisWeek,
                    'trend' => $appointmentsThisWeek > 0 ? 'up' : 'stable',
                ],
                'contracts' => [
                    'total' => $contractsCount,
                    'active' => $activeContractsCount,
                    'trend' => 'stable',
                ],
                'invoices' => [
                    'total' => $invoicesCount,
                    'this_month' => $invoicesThisMonth,
                    'trend' => $invoicesThisMonth > 0 ? 'down' : 'stable',
                ],
                'notifications' => [
                    'unread' => $unreadNotificationsCount,
                    'today' => $notificationsToday,
                    'trend' => $notificationsToday > 0 ? 'up' : 'stable',
                ],
                'tickets' => [
                    'total' => $ticketsCount,
                    'open' => $ticketsInProgress,
                    'trend' => $ticketsInProgress > 0 ? 'warning' : 'stable',
                ],
                'reviews' => [
                    'total' => $reviewsCount,
                    'pending' => $reviewsPending,
                    'trend' => $reviewsPending > 0 ? 'up' : 'stable',
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Error getting tenant dashboard stats: ' . $e->getMessage());
            return [
                'appointments' => ['total' => 0, 'this_week' => 0, 'trend' => 'stable'],
                'contracts' => ['total' => 0, 'active' => 0, 'trend' => 'stable'],
                'invoices' => ['total' => 0, 'this_month' => 0, 'trend' => 'stable'],
                'notifications' => ['unread' => 0, 'today' => 0, 'trend' => 'stable'],
                'tickets' => ['total' => 0, 'in_progress' => 0, 'trend' => 'stable'],
                'reviews' => ['total' => 0, 'pending' => 0, 'trend' => 'stable'],
            ];
        }
    }

    /**
     * Get current rental information.
     */
    private function getCurrentRental($tenantId)
    {
        try {
            // Get accessible lease IDs (user as tenant or resident)
            $accessibleLeaseIds = Lease::getAccessibleLeaseIds($tenantId);
            
            $currentLease = Lease::with(['unit.property', 'agent'])
                ->whereIn('id', $accessibleLeaseIds)
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->orderBy('start_date', 'desc')
                ->first();

            if (!$currentLease) {
                return null;
            }

            return [
                'lease' => $currentLease,
                'property' => $currentLease->unit->property ?? null,
                'unit' => $currentLease->unit,
                'agent' => $currentLease->agent,
                'rent_amount' => $currentLease->rent_amount,
                'start_date' => $currentLease->start_date,
                'end_date' => $currentLease->end_date,
                'status' => $currentLease->status,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting current rental: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get upcoming events (invoices due, appointments, contract renewals).
     */
    private function getUpcomingEvents($tenantId)
    {
        try {
            $events = [];

            // Get accessible lease IDs (user as tenant or resident)
            $accessibleLeaseIds = Lease::getAccessibleLeaseIds($tenantId);
            
            // Upcoming invoices (due in next 7 days)
            $upcomingInvoices = Invoice::whereIn('lease_id', $accessibleLeaseIds)
                ->whereIn('status', ['issued', 'overdue'])
                ->whereBetween('due_date', [now(), now()->addDays(7)])
                ->whereNull('deleted_at')
                ->orderBy('due_date', 'asc')
                ->limit(3)
                ->get();

            foreach ($upcomingInvoices as $invoice) {
                $events[] = [
                    'type' => 'invoice',
                    'title' => 'Hóa đơn tiền phòng',
                    'description' => 'Đến hạn thanh toán',
                    'date' => $invoice->due_date,
                    'date_text' => $invoice->due_date->format('d/m/Y'),
                    'time_remaining' => now()->diffInDays($invoice->due_date, false),
                    'urgent' => $invoice->due_date->isPast() || $invoice->due_date->isToday(),
                    'action_url' => route('tenant.invoices.index'),
                    'data' => $invoice,
                ];
            }

            // Upcoming appointments (next 7 days)
            $upcomingAppointments = Viewing::with(['property', 'unit'])
                ->where('tenant_id', $tenantId)
                ->where('status', 'confirmed')
                ->whereBetween('schedule_at', [now(), now()->addDays(7)])
                ->whereNull('deleted_at')
                ->orderBy('schedule_at', 'asc')
                ->limit(3)
                ->get();

            foreach ($upcomingAppointments as $appointment) {
                $events[] = [
                    'type' => 'appointment',
                    'title' => 'Lịch xem phòng',
                    'description' => $appointment->property->name ?? 'Phòng trọ',
                    'date' => $appointment->schedule_at,
                    'date_text' => $appointment->schedule_at->format('d/m/Y'),
                    'time' => $appointment->schedule_at->format('H:i'),
                    'time_remaining' => now()->diffInDays($appointment->schedule_at, false),
                    'urgent' => false,
                    'action_url' => route('tenant.appointments'),
                    'data' => $appointment,
                ];
            }

            // Expiring contracts (next 30 days)
            $expiringContracts = Lease::with(['unit.property'])
                ->whereIn('id', $accessibleLeaseIds)
                ->where('status', 'active')
                ->whereBetween('end_date', [now(), now()->addDays(30)])
                ->whereNull('deleted_at')
                ->orderBy('end_date', 'asc')
                ->limit(2)
                ->get();

            foreach ($expiringContracts as $contract) {
                $events[] = [
                    'type' => 'contract_renewal',
                    'title' => 'Gia hạn hợp đồng',
                    'description' => 'Hợp đồng sắp hết hạn',
                    'date' => $contract->end_date,
                    'date_text' => $contract->end_date->format('d/m/Y'),
                    'time_remaining' => now()->diffInDays($contract->end_date, false),
                    'urgent' => $contract->end_date->lte(now()->addDays(7)),
                    'action_url' => route('tenant.contracts.index'),
                    'data' => $contract,
                ];
            }

            // Sort by date
            usort($events, function($a, $b) {
                return $a['date'] <=> $b['date'];
            });

            return array_slice($events, 0, 5); // Return top 5 events
        } catch (\Exception $e) {
            Log::error('Error getting upcoming events: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get recent activities.
     */
    private function getRecentActivities($tenantId)
    {
        try {
            $activities = [];

            // Recent booking deposits
            $recentDeposits = DB::table('booking_deposits')
                ->where('tenant_user_id', $tenantId)
                ->whereNull('deleted_at')
                ->orderBy('created_at', 'desc')
                ->limit(2)
                ->get();

            foreach ($recentDeposits as $deposit) {
                $activities[] = [
                    'type' => 'deposit',
                    'icon' => 'success',
                    'icon_class' => 'fas fa-check',
                    'title' => 'Đặt cọc thành công',
                    'description' => "Bạn đã đặt cọc thành công cho phòng trọ",
                    'time' => Carbon::parse($deposit->created_at)->diffForHumans(),
                    'created_at' => $deposit->created_at,
                ];
            }

            // Recent appointment confirmations
            $recentAppointments = Viewing::with(['property'])
                ->where('tenant_id', $tenantId)
                ->where('status', 'confirmed')
                ->whereNull('deleted_at')
                ->orderBy('updated_at', 'desc')
                ->limit(2)
                ->get();

            foreach ($recentAppointments as $appointment) {
                $activities[] = [
                    'type' => 'appointment',
                    'icon' => 'info',
                    'icon_class' => 'fas fa-calendar',
                    'title' => 'Lịch hẹn được xác nhận',
                    'description' => "Chủ nhà đã xác nhận lịch hẹn xem phòng ngày " . $appointment->schedule_at->format('d/m/Y'),
                    'time' => $appointment->updated_at->diffForHumans(),
                    'created_at' => $appointment->updated_at,
                ];
            }

            // Get accessible lease IDs (user as tenant or resident)
            $accessibleLeaseIds = Lease::getAccessibleLeaseIds($tenantId);
            
            // Upcoming invoice warnings
            $upcomingInvoices = Invoice::whereIn('lease_id', $accessibleLeaseIds)
                ->whereIn('status', ['issued'])
                ->whereBetween('due_date', [now(), now()->addDays(3)])
                ->whereNull('deleted_at')
                ->orderBy('due_date', 'asc')
                ->limit(2)
                ->get();

            foreach ($upcomingInvoices as $invoice) {
                $activities[] = [
                    'type' => 'invoice_warning',
                    'icon' => 'warning',
                    'icon_class' => 'fas fa-exclamation',
                    'title' => 'Hóa đơn sắp đến hạn',
                    'description' => "Hóa đơn tiền phòng tháng " . $invoice->issue_date->format('m/Y') . " sẽ đến hạn vào ngày " . $invoice->due_date->format('d/m/Y'),
                    'time' => now()->diffInDays($invoice->due_date, false) . ' ngày nữa',
                    'created_at' => now(),
                ];
            }

            // Recent tickets
            $recentTickets = Ticket::with(['lease.unit.property'])
                ->whereIn('lease_id', $accessibleLeaseIds)
                ->whereNull('deleted_at')
                ->orderBy('created_at', 'desc')
                ->limit(2)
                ->get();

            foreach ($recentTickets as $ticket) {
                $statusLabel = match($ticket->status) {
                    'open' => 'Đang mở',
                    'in_progress' => 'Đang xử lý',
                    'resolved' => 'Đã giải quyết',
                    'closed' => 'Đã đóng',
                    default => $ticket->status,
                };
                
                $activities[] = [
                    'type' => 'ticket',
                    'icon' => $ticket->status === 'resolved' || $ticket->status === 'closed' ? 'success' : ($ticket->status === 'open' ? 'warning' : 'info'),
                    'icon_class' => 'fas fa-ticket-alt',
                    'title' => 'Ticket: ' . $ticket->title,
                    'description' => "Trạng thái: {$statusLabel}",
                    'time' => $ticket->created_at->diffForHumans(),
                    'created_at' => $ticket->created_at,
                ];
            }

            // Review requests
            $reviewsNeeded = Lease::with(['unit.property'])
                ->whereIn('id', $accessibleLeaseIds)
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->whereDoesntHave('review')
                ->limit(2)
                ->get();

            foreach ($reviewsNeeded as $lease) {
                $activities[] = [
                    'type' => 'review_request',
                    'icon' => 'info',
                    'icon_class' => 'fas fa-star',
                    'title' => 'Yêu cầu đánh giá',
                    'description' => "Bạn có thể đánh giá phòng trọ " . ($lease->unit->property->name ?? ''),
                    'time' => now()->diffInDays($lease->start_date, false) . ' ngày sau khi thuê',
                    'created_at' => $lease->start_date,
                ];
            }

            // Sort by created_at desc
            usort($activities, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });

            return array_slice($activities, 0, 4); // Return top 4 activities
        } catch (\Exception $e) {
            Log::error('Error getting recent activities: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get quick stats.
     */
    private function getQuickStats($tenantId)
    {
        try {
            // Completed appointments
            $completedAppointments = Viewing::where('tenant_id', $tenantId)
                ->where('status', 'done')
                ->whereNull('deleted_at')
                ->count();

            // Average review rating
            $averageRating = Review::where('tenant_id', $tenantId)
                ->whereNull('deleted_at')
                ->avg('overall_rating');

            // Get accessible lease IDs (user as tenant or resident)
            $accessibleLeaseIds = Lease::getAccessibleLeaseIds($tenantId);
            
            // Total paid amount
            $totalPaid = Payment::whereHas('invoice', function($q) use ($accessibleLeaseIds) {
                    $q->whereIn('lease_id', $accessibleLeaseIds);
                })
                ->where('status', 'success')
                ->whereNull('deleted_at')
                ->sum('amount');

            // Total rooms rented
            $totalRoomsRented = Lease::whereIn('id', $accessibleLeaseIds)
                ->whereNull('deleted_at')
                ->distinct('unit_id')
                ->count('unit_id');

            return [
                'completed_appointments' => $completedAppointments,
                'average_rating' => round($averageRating ?? 0, 1),
                'total_paid' => $totalPaid,
                'total_rooms_rented' => $totalRoomsRented,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting quick stats: ' . $e->getMessage());
            return [
                'completed_appointments' => 0,
                'average_rating' => 0,
                'total_paid' => 0,
                'total_rooms_rented' => 0,
            ];
        }
    }

    /**
     * Clear dashboard cache.
     */
    public function clearCache()
    {
        $user = Auth::user();
        $cacheKey = "dashboard_data_tenant_{$user->id}";
        Cache::forget($cacheKey);

        return response()->json(['success' => true, 'message' => 'Dashboard cache cleared successfully']);
    }
}
