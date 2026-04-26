<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AIStatsController extends Controller
{
    /**
     * Return aggregated statistics for the current authenticated user's organization.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $orgId = $user->organization_id ?? $request->session()->get('auth_organization_id');

        $stats = [];

        // Company invoices
        if (class_exists(\App\Models\CompanyInvoice::class)) {
            $q = \App\Models\CompanyInvoice::query();
            if ($orgId) $q->where('organization_id', $orgId);
            $stats['company_invoices_total'] = (int) $q->count();
            $stats['company_invoices_total_amount'] = (float) $q->sum('total_amount');
            $stats['company_invoices_pending'] = (int) $q->where('status', 'pending')->count();
            $stats['company_invoices_unpaid'] = (int) $q->where('status', '!=', 'paid')->where('status', '!=', 'cancelled')->count();
        }

        // Standard invoices
        if (class_exists(\App\Models\Invoice::class)) {
            $q = \App\Models\Invoice::query();
            if ($orgId) $q->where('organization_id', $orgId);
            $stats['invoices_total'] = (int) $q->count();
            $stats['invoices_total_amount'] = (float) $q->sum('total_amount');
            $stats['invoices_unpaid'] = (int) $q->where('status', '!=', 'paid')->where('status', '!=', 'cancelled')->count();
        }

        // Master leases / leases
        if (class_exists(\App\Models\MasterLease::class)) {
            $q = \App\Models\MasterLease::query();
            if ($orgId) $q->where('organization_id', $orgId);
            $stats['master_leases_count'] = (int) $q->count();
        }

        if (class_exists(\App\Models\Lease::class)) {
            $q = \App\Models\Lease::query();
            if ($orgId) $q->where('organization_id', $orgId);
            $stats['leases_count'] = (int) $q->count();
        }

        // Users
        if (class_exists(\App\Models\User::class)) {
            $q = \App\Models\User::query();
            // If users table is multi-org, try to filter by organization_id if column exists
            try {
                if ($orgId && \Schema::hasColumn((new \App\Models\User())->getTable(), 'organization_id')) {
                    $q->where('organization_id', $orgId);
                }
            } catch (\Throwable $e) {
                // ignore schema check errors
            }
            $stats['users_count'] = (int) $q->count();
        }

        // Tickets
        if (class_exists(\App\Models\Ticket::class)) {
            $q = \App\Models\Ticket::query();
            if ($orgId) $q->where('organization_id', $orgId);
            $stats['tickets_count'] = (int) $q->count();
            $stats['tickets_open'] = (int) $q->where('status', 'open')->count();
        }

        // Vendors
        if (class_exists(\App\Models\Vendor::class)) {
            $q = \App\Models\Vendor::query();
            if ($orgId) $q->where('organization_id', $orgId);
            $stats['vendors_count'] = (int) $q->count();
        }

        return response()->json([
            'ok' => true,
            'organization_id' => $orgId,
            'stats' => $stats,
        ]);
    }
}
