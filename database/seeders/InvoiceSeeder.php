<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Lease;
use App\Models\User;
use App\Models\Organization;
use Carbon\Carbon;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first organization
        $organization = Organization::first();
        if (!$organization) {
            $this->command->info('No organization found. Please run organization seeder first.');
            return;
        }

        // Get active leases
        $leases = Lease::where('status', 'active')
            ->where('organization_id', $organization->id)
            ->with(['tenant', 'unit.property'])
            ->get();

        if ($leases->isEmpty()) {
            $this->command->info('No active leases found. Please create some leases first.');
            return;
        }

        $this->command->info('Creating sample invoices...');

        foreach ($leases->take(5) as $lease) {
            // Create 2-3 invoices per lease
            $invoiceCount = rand(2, 3);
            
            for ($i = 0; $i < $invoiceCount; $i++) {
                $issueDate = Carbon::now()->subMonths(rand(1, 6))->startOfMonth();
                $dueDate = $issueDate->copy()->addDays(30);
                
                // Generate invoice number
                $invoiceNo = 'HD-' . $issueDate->format('Ym') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
                
                // Create invoice
                $invoice = Invoice::create([
                    'organization_id' => $organization->id,
                    'lease_id' => $lease->id,
                    'invoice_no' => $invoiceNo,
                    'issue_date' => $issueDate,
                    'due_date' => $dueDate,
                    'status' => $this->getRandomStatus(),
                    'subtotal' => $lease->rent_amount,
                    'tax_amount' => 0,
                    'discount_amount' => 0,
                    'total_amount' => $lease->rent_amount,
                    'currency' => 'VND',
                    'note' => 'Hóa đơn tiền thuê phòng tháng ' . $issueDate->format('m/Y'),
                ]);

                // Create invoice items
                $this->createInvoiceItems($invoice, $lease);

                $this->command->info("Created invoice: {$invoice->invoice_no} for lease {$lease->id}");
            }
        }

        $this->command->info('Sample invoices created successfully!');
    }

    private function getRandomStatus()
    {
        $statuses = ['draft', 'issued', 'paid', 'overdue'];
        $weights = [10, 30, 50, 10]; // 10% draft, 30% issued, 50% paid, 10% overdue
        
        $random = rand(1, 100);
        $cumulative = 0;
        
        foreach ($statuses as $index => $status) {
            $cumulative += $weights[$index];
            if ($random <= $cumulative) {
                return $status;
            }
        }
        
        return 'issued';
    }

    private function createInvoiceItems($invoice, $lease)
    {
        // Main rent item
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Tiền thuê phòng',
            'quantity' => 1,
            'unit_price' => $lease->rent_amount,
            'amount' => $lease->rent_amount,
        ]);

        // Random additional items (30% chance)
        if (rand(1, 100) <= 30) {
            $additionalItems = [
                ['description' => 'Tiền điện', 'quantity' => rand(50, 200), 'unit_price' => 3000],
                ['description' => 'Tiền nước', 'quantity' => rand(10, 30), 'unit_price' => 15000],
                ['description' => 'Phí dịch vụ', 'quantity' => 1, 'unit_price' => 100000],
                ['description' => 'Phí internet', 'quantity' => 1, 'unit_price' => 200000],
            ];

            $item = $additionalItems[array_rand($additionalItems)];
            $amount = $item['quantity'] * $item['unit_price'];

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'amount' => $amount,
            ]);

            // Update invoice totals
            $invoice->update([
                'subtotal' => $invoice->subtotal + $amount,
                'total_amount' => $invoice->total_amount + $amount,
            ]);
        }
    }
}