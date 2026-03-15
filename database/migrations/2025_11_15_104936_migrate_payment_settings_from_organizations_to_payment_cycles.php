<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Migrate payment settings từ organizations sang payment_cycles.
     * Với mỗi organization:
     * - Nếu có default payment cycle: update settings vào đó
     * - Nếu có payment cycle mới nhất: set làm default và update settings
     * - Nếu không có payment cycle nào: tạo mới với settings từ organization
     */
    public function up(): void
    {
        // Check if organizations table still has the old columns
        $hasOldColumns = Schema::hasColumn('organizations', 'payment_due_hours') 
            && Schema::hasColumn('organizations', 'invoice_timing')
            && Schema::hasColumn('organizations', 'invoice_payment_days');
        
        if (!$hasOldColumns) {
            Log::info('Old payment columns do not exist in organizations table. Skipping migration.');
            return;
        }
        
        // Get all organizations with payment settings
        $organizations = DB::table('organizations')
            ->whereNull('deleted_at')
            ->select('id', 'payment_due_hours', 'invoice_timing', 'invoice_payment_days')
            ->get();
        
        $migratedCount = 0;
        
        foreach ($organizations as $org) {
            // Check if organization has any payment settings to migrate
            if ($org->payment_due_hours === null && $org->invoice_timing === null && $org->invoice_payment_days === null) {
                continue;
            }
            
            // Try to find default payment cycle
            $defaultCycle = DB::table('payment_cycles')
                ->where('organization_id', $org->id)
                ->where('is_default', true)
                ->whereNull('deleted_at')
                ->first();
            
            if ($defaultCycle) {
                // Update existing default payment cycle
                DB::table('payment_cycles')
                    ->where('id', $defaultCycle->id)
                    ->update([
                        'payment_due_hours' => $org->payment_due_hours ?? 4320,
                        'invoice_timing' => $org->invoice_timing ?? 'end_of_cycle',
                        'invoice_payment_days' => $org->invoice_payment_days ?? 30,
                        'updated_at' => now(),
                    ]);
                
                Log::info("Updated default payment cycle for organization {$org->id}");
                $migratedCount++;
            } else {
                // No default cycle, check if there's any cycle we can set as default
                $latestCycle = DB::table('payment_cycles')
                    ->where('organization_id', $org->id)
                    ->whereNull('deleted_at')
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                if ($latestCycle) {
                    // Set latest cycle as default and update settings
                    DB::table('payment_cycles')
                        ->where('id', $latestCycle->id)
                        ->update([
                            'is_default' => true,
                            'payment_due_hours' => $org->payment_due_hours ?? 4320,
                            'invoice_timing' => $org->invoice_timing ?? 'end_of_cycle',
                            'invoice_payment_days' => $org->invoice_payment_days ?? 30,
                            'updated_at' => now(),
                        ]);
                    
                    Log::info("Set latest payment cycle as default for organization {$org->id}");
                    $migratedCount++;
                } else {
                    // No payment cycles exist, create a default one
                    DB::table('payment_cycles')->insert([
                        'organization_id' => $org->id,
                        'cycle_type' => 'monthly',
                        'billing_day' => 1,
                        'notes' => 'Chu kỳ thanh toán mặc định (migrated from organizations)',
                        'name' => 'Hàng tháng - Ngày 1',
                        'is_default' => true,
                        'payment_due_hours' => $org->payment_due_hours ?? 4320,
                        'invoice_timing' => $org->invoice_timing ?? 'end_of_cycle',
                        'invoice_payment_days' => $org->invoice_payment_days ?? 30,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    Log::info("Created default payment cycle for organization {$org->id}");
                    $migratedCount++;
                }
            }
        }
        
        Log::info("Migrated payment settings for {$migratedCount} organizations");
    }

    /**
     * Reverse the migrations.
     * 
     * Migrate settings back từ payment_cycles sang organizations (nếu columns vẫn còn tồn tại)
     */
    public function down(): void
    {
        // Check if organizations table has the columns to restore data to
        $hasColumns = Schema::hasColumn('organizations', 'payment_due_hours') 
            && Schema::hasColumn('organizations', 'invoice_timing')
            && Schema::hasColumn('organizations', 'invoice_payment_days');
        
        if (!$hasColumns) {
            Log::info('Target columns do not exist in organizations table. Cannot rollback.');
            return;
        }
        
        // Get all organizations with default payment cycles
        $organizations = DB::table('organizations')
            ->whereNull('deleted_at')
            ->select('id')
            ->get();
        
        foreach ($organizations as $org) {
            $defaultCycle = DB::table('payment_cycles')
                ->where('organization_id', $org->id)
                ->where('is_default', true)
                ->whereNull('deleted_at')
                ->first();
            
            if ($defaultCycle) {
                DB::table('organizations')
                    ->where('id', $org->id)
                    ->update([
                        'payment_due_hours' => $defaultCycle->payment_due_hours,
                        'invoice_timing' => $defaultCycle->invoice_timing,
                        'invoice_payment_days' => $defaultCycle->invoice_payment_days,
                        'updated_at' => now(),
                    ]);
            }
        }
        
        Log::info('Rolled back payment settings to organizations table');
    }
};
