<?php

namespace App\Services;

use App\Models\MeterReading;
use App\Models\Lease;
use App\Models\LeaseServiceSetItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MeterBillingService
{
    /**
     * Process billing for a meter reading
     */
    public function processBilling(MeterReading $reading)
    {
        try {
            DB::beginTransaction();

            // Get current lease for this unit
            $lease = Lease::where('unit_id', $reading->meter->unit_id)
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->first();

            if (!$lease) {
                Log::info("No active lease found for unit {$reading->meter->unit_id}");
                DB::rollBack();
                return false;
            }

            // Get effective lease service set for this lease
            $leaseServiceSet = $lease->getEffectiveLeaseServiceSet();
            
            if (!$leaseServiceSet) {
                Log::info("No lease service set found for lease {$lease->id}");
                DB::rollBack();
                return false;
            }

            // Get service pricing from lease service set
            $leaseServiceItem = LeaseServiceSetItem::where('lease_service_set_id', $leaseServiceSet->id)
                ->where('service_id', $reading->meter->service_id)
                ->first();

            if (!$leaseServiceItem) {
                Log::info("No service pricing found for lease {$lease->id} and service {$reading->meter->service_id}");
                DB::rollBack();
                return false;
            }

            // Get previous reading for this meter
            $previousReading = MeterReading::where('meter_id', $reading->meter_id)
                ->where('reading_date', '<', $reading->reading_date)
                ->latest('reading_date')
                ->first();

            if (!$previousReading) {
                Log::info("No previous reading found for meter {$reading->meter_id}");
                DB::rollBack();
                return false;
            }

            $usage = $reading->value - $previousReading->value;
            $cost = $usage * $leaseServiceItem->price;

            // Process monthly billing
            $this->processMonthlyBilling($lease, $reading, $usage, $cost, $leaseServiceItem, $previousReading);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error processing billing for reading {$reading->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Process monthly billing for a meter reading
     */
    private function processMonthlyBilling(
        Lease $lease, 
        MeterReading $reading, 
        float $usage, 
        float $cost, 
        LeaseServiceSetItem $leaseServiceItem,
        MeterReading $previousReading
    ) {
        $readingMonth = Carbon::parse($reading->reading_date)->format('Y-m');
        
        // Check if invoice already exists for this month
        $existingInvoice = Invoice::where('lease_id', $lease->id)
            ->whereRaw('DATE_FORMAT(issue_date, "%Y-%m") = ?', [$readingMonth])
            ->first();

        if (!$existingInvoice) {
            // Create new monthly invoice
            $existingInvoice = $this->createMonthlyInvoice($lease, $reading->reading_date);
        }

        // Create or update invoice item for this service
        $this->createOrUpdateInvoiceItem(
            $existingInvoice, 
            $reading, 
            $usage, 
            $cost, 
            $leaseServiceItem, 
            $previousReading
        );

        // Update invoice totals
        $this->updateInvoiceTotals($existingInvoice);
    }

    /**
     * Create monthly invoice
     */
    private function createMonthlyInvoice(Lease $lease, $readingDate)
    {
        $invoiceDate = Carbon::parse($readingDate)->startOfMonth();
        $dueDate = $invoiceDate->copy()->addDays(30);

        return Invoice::create([
            'organization_id' => $lease->organization_id,
            'is_auto_created' => true,
            'lease_id' => $lease->id,
            'invoice_no' => Invoice::generateInvoiceNumber($lease->organization_id),
            'issue_date' => $invoiceDate,
            'due_date' => $dueDate,
            'status' => 'draft', // Luôn tạo hóa đơn ở trạng thái nháp
            'subtotal' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 0,
            'currency' => 'VND',
            'note' => "Hóa đơn tháng {$invoiceDate->format('m/Y')}",
        ]);
    }

    /**
     * Create or update invoice item
     */
    private function createOrUpdateInvoiceItem(
        Invoice $invoice,
        MeterReading $reading,
        float $usage,
        float $cost,
        LeaseServiceSetItem $leaseServiceItem,
        MeterReading $previousReading
    ) {
        $itemKey = "service_{$reading->meter->service_id}_" . Carbon::parse($reading->reading_date)->format('Y-m');

        InvoiceItem::updateOrCreate(
            [
                'invoice_id' => $invoice->id,
                'item_type' => 'service',
            ],
            [
                'description' => $this->generateItemDescription($reading, $usage),
                'quantity' => $usage,
                'unit_price' => $leaseServiceItem->price,
                'amount' => $cost,
                'meta_json' => [
                    'service_id' => $reading->meter->service_id,
                    'meter_id' => $reading->meter_id,
                    'reading_id' => $reading->id,
                    'previous_reading' => $previousReading->value,
                    'current_reading' => $reading->value,
                    'previous_reading_date' => $previousReading->reading_date,
                    'current_reading_date' => $reading->reading_date,
                    'item_key' => $itemKey,
                ]
            ]
        );
    }

    /**
     * Update invoice totals
     */
    private function updateInvoiceTotals(Invoice $invoice)
    {
        $items = $invoice->items;
        $subtotal = $items->sum('amount');
        
        $invoice->update([
            'subtotal' => $subtotal,
            'total_amount' => $subtotal, // Assuming no tax or discount for now
        ]);
    }

    /**
     * Generate item description
     */
    private function generateItemDescription(MeterReading $reading, float $usage)
    {
        $serviceName = $reading->meter->service->name;
        $month = Carbon::parse($reading->reading_date)->format('m/Y');
        $unit = $reading->meter->service->unit_label;
        
        return "{$serviceName} - Tháng {$month} ({$usage} {$unit})";
    }

    /**
     * Get billing history for a meter
     */
    public function getBillingHistory($meterId, $limit = 12)
    {
        return MeterReading::where('meter_id', $meterId)
            ->with(['meter.service'])
            ->selectRaw('
                DATE_FORMAT(reading_date, "%Y-%m") as month,
                MIN(value) as start_reading,
                MAX(value) as end_reading,
                MAX(value) - MIN(value) as `usage`,
                COUNT(*) as reading_count,
                MIN(reading_date) as first_reading_date,
                MAX(reading_date) as last_reading_date
            ')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit($limit)
            ->get()
            ->map(function($item) use ($meterId) {
                // Get service price for this meter
                $lease = Lease::whereHas('unit.meters', function($q) use ($meterId) {
                    $q->where('id', $meterId);
                })
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->first();

                $servicePrice = 0;
                if ($lease) {
                    $meter = \App\Models\Meter::find($meterId);
                    $leaseServiceSet = $lease->getEffectiveLeaseServiceSet();
                    if ($leaseServiceSet) {
                        $leaseServiceItem = \App\Models\LeaseServiceSetItem::where('lease_service_set_id', $leaseServiceSet->id)
                            ->where('service_id', $meter->service_id)
                            ->first();
                        $servicePrice = $leaseServiceItem ? $leaseServiceItem->price : 0;
                    }
                }

                $item->cost = $item->usage * $servicePrice;
                $item->service_price = $servicePrice;
                return $item;
            });
    }

    /**
     * Calculate monthly usage for a meter
     */
    public function calculateMonthlyUsage($meterId, $year, $month)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $readings = MeterReading::where('meter_id', $meterId)
            ->whereBetween('reading_date', [$startDate, $endDate])
            ->orderBy('reading_date')
            ->get();

        if ($readings->count() < 2) {
            return null; // Need at least 2 readings to calculate usage
        }

        $firstReading = $readings->first();
        $lastReading = $readings->last();

        // Get previous month's last reading
        $previousMonthEnd = $startDate->copy()->subDay();
        $previousReading = MeterReading::where('meter_id', $meterId)
            ->where('reading_date', '<=', $previousMonthEnd)
            ->latest('reading_date')
            ->first();

        if (!$previousReading) {
            return null;
        }

        return [
            'month' => $startDate->format('Y-m'),
            'start_reading' => $previousReading->value,
            'end_reading' => $lastReading->value,
            'usage' => $lastReading->value - $previousReading->value,
            'reading_count' => $readings->count(),
            'first_reading_date' => $firstReading->reading_date,
            'last_reading_date' => $lastReading->reading_date,
        ];
    }

    /**
     * Generate monthly billing report
     */
    public function generateMonthlyReport($organizationId, $year, $month)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $invoices = Invoice::where('organization_id', $organizationId)
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->with(['lease.unit.property', 'items'])
            ->get();

        $totalRevenue = $invoices->sum('total_amount');
        $paidInvoices = $invoices->where('status', 'paid');
        $pendingInvoices = $invoices->where('status', 'pending');

        return [
            'period' => $startDate->format('m/Y'),
            'total_invoices' => $invoices->count(),
            'total_revenue' => $totalRevenue,
            'paid_amount' => $paidInvoices->sum('total_amount'),
            'pending_amount' => $pendingInvoices->sum('total_amount'),
            'invoices' => $invoices,
        ];
    }
}
