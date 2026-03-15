<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Mục đích:
     * 1. Kiểm tra và sửa dữ liệu sai (organization_subscription_id không tồn tại)
     * 2. Đảm bảo foreign key constraint tồn tại và hoạt động đúng
     * 3. Xóa các invoice có organization_subscription_id không hợp lệ
     */
    public function up(): void
    {
        // Kiểm tra xem bảng có tồn tại không
        if (!Schema::hasTable('subscription_invoices')) {
            return;
        }

        // Bước 1: Kiểm tra và sửa dữ liệu sai
        // Tìm các invoice có organization_subscription_id không tồn tại trong organization_subscriptions
        $invalidInvoices = DB::table('subscription_invoices')
            ->leftJoin('organization_subscriptions', 'subscription_invoices.organization_subscription_id', '=', 'organization_subscriptions.id')
            ->whereNull('organization_subscriptions.id')
            ->select('subscription_invoices.id', 'subscription_invoices.organization_subscription_id', 'subscription_invoices.invoice_number')
            ->get();

        if ($invalidInvoices->isNotEmpty()) {
            // Log các invoice không hợp lệ
            \Illuminate\Support\Facades\Log::warning('Found invalid subscription invoices with non-existent organization_subscription_id', [
                'count' => $invalidInvoices->count(),
                'invoices' => $invalidInvoices->map(fn($inv) => [
                    'id' => $inv->id,
                    'organization_subscription_id' => $inv->organization_subscription_id,
                    'invoice_number' => $inv->invoice_number,
                ])->toArray(),
            ]);

            // Xóa các invoice không hợp lệ (soft delete nếu có deleted_at, hard delete nếu không)
            foreach ($invalidInvoices as $invoice) {
                if (Schema::hasColumn('subscription_invoices', 'deleted_at')) {
                    DB::table('subscription_invoices')
                        ->where('id', $invoice->id)
                        ->update(['deleted_at' => now()]);
                } else {
                    DB::table('subscription_invoices')
                        ->where('id', $invoice->id)
                        ->delete();
                }
            }
        }

        // Bước 2: Kiểm tra và thêm foreign key constraint nếu chưa có
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        
        try {
            // Kiểm tra xem foreign key đã tồn tại chưa
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE CONSTRAINT_SCHEMA = ?
                AND TABLE_NAME = 'subscription_invoices'
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                AND CONSTRAINT_NAME LIKE '%organization_subscription_id%'
            ", [$databaseName]);

            $hasForeignKey = !empty($foreignKeys);
            $constraintName = $hasForeignKey ? $foreignKeys[0]->CONSTRAINT_NAME : null;

            if (!$hasForeignKey) {
                // Thêm foreign key constraint
                Schema::table('subscription_invoices', function (Blueprint $table) {
                    $table->foreign('organization_subscription_id')
                        ->references('id')
                        ->on('organization_subscriptions')
                        ->onDelete('cascade')
                        ->onUpdate('cascade');
                });
            } else {
                // Kiểm tra xem constraint có đúng không (cascade delete)
                $constraintInfo = DB::select("
                    SELECT 
                        CONSTRAINT_NAME,
                        DELETE_RULE,
                        UPDATE_RULE
                    FROM information_schema.REFERENTIAL_CONSTRAINTS
                    WHERE CONSTRAINT_SCHEMA = ?
                    AND TABLE_NAME = 'subscription_invoices'
                    AND CONSTRAINT_NAME = ?
                ", [$databaseName, $constraintName]);

                if (!empty($constraintInfo)) {
                    $constraint = $constraintInfo[0];
                    // Nếu không phải CASCADE, sửa lại
                    if ($constraint->DELETE_RULE !== 'CASCADE' || $constraint->UPDATE_RULE !== 'CASCADE') {
                        Schema::table('subscription_invoices', function (Blueprint $table) use ($constraintName) {
                            $table->dropForeign([$constraintName]);
                        });

                        Schema::table('subscription_invoices', function (Blueprint $table) {
                            $table->foreign('organization_subscription_id')
                                ->references('id')
                                ->on('organization_subscriptions')
                                ->onDelete('cascade')
                                ->onUpdate('cascade');
                        });
                    }
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error checking/adding foreign key constraint: ' . $e->getMessage());
            // Tiếp tục với các bước khác
        }

        // Bước 3: Kiểm tra và sửa các invoice có organization_subscription_id bị lệch (+2)
        // Tìm các invoice có organization_subscription_id không tồn tại trong organization_subscriptions
        // và thử tìm subscription gần nhất để sửa
        $misalignedInvoices = DB::table('subscription_invoices')
            ->leftJoin('organization_subscriptions', 'subscription_invoices.organization_subscription_id', '=', 'organization_subscriptions.id')
            ->whereNull('organization_subscriptions.id')
            ->select('subscription_invoices.id as invoice_id', 'subscription_invoices.organization_subscription_id')
            ->get();

        if ($misalignedInvoices->isNotEmpty()) {
            \Illuminate\Support\Facades\Log::warning('Found misaligned subscription invoices (organization_subscription_id does not exist)', [
                'count' => $misalignedInvoices->count(),
            ]);

            // Cố gắng sửa bằng cách tìm subscription gần nhất (trong khoảng ±2)
            foreach ($misalignedInvoices as $invoice) {
                $invalidId = $invoice->organization_subscription_id;
                
                // Thử tìm subscription có ID gần nhất (trong khoảng ±2)
                $possibleSubscription = DB::table('organization_subscriptions')
                    ->whereBetween('id', [$invalidId - 2, $invalidId + 2])
                    ->orderByRaw('ABS(id - ?)', [$invalidId])
                    ->first();

                if ($possibleSubscription) {
                    DB::table('subscription_invoices')
                        ->where('id', $invoice->invoice_id)
                        ->update(['organization_subscription_id' => $possibleSubscription->id]);
                    
                    \Illuminate\Support\Facades\Log::info('Fixed misaligned invoice', [
                        'invoice_id' => $invoice->invoice_id,
                        'old_subscription_id' => $invalidId,
                        'new_subscription_id' => $possibleSubscription->id,
                    ]);
                } else {
                    // Nếu không tìm thấy subscription hợp lệ, xóa invoice
                    if (Schema::hasColumn('subscription_invoices', 'deleted_at')) {
                        DB::table('subscription_invoices')
                            ->where('id', $invoice->invoice_id)
                            ->update(['deleted_at' => now()]);
                    } else {
                        DB::table('subscription_invoices')
                            ->where('id', $invoice->invoice_id)
                            ->delete();
                    }
                    
                    \Illuminate\Support\Facades\Log::warning('Deleted invoice with invalid organization_subscription_id', [
                        'invoice_id' => $invoice->invoice_id,
                        'invalid_subscription_id' => $invalidId,
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Không rollback vì đây là migration sửa lỗi dữ liệu
        // Nếu cần rollback, chỉ cần xóa foreign key constraint
        if (Schema::hasTable('subscription_invoices')) {
            Schema::table('subscription_invoices', function (Blueprint $table) {
                $table->dropForeign(['organization_subscription_id']);
            });
        }
    }
};
