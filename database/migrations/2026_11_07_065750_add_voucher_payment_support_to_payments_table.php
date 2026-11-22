<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedSmallInteger('unique_code')->nullable();
            $table->foreignId('voucher_id')->nullable()->unique()->constrained('voucher')->cascadeOnDelete();
            $table->index(['provider', 'unique_code', 'transaction_status']);
        });

        DB::table('payments')
            ->select(['id', 'order_id'])
            ->orderBy('id')
            ->get()
            ->each(function (object $payment): void {
                $uniqueCode = DB::table('orders')
                    ->where('id', $payment->order_id)
                    ->value('unique_code');

                if ($uniqueCode !== null) {
                    DB::table('payments')
                        ->where('id', $payment->id)
                        ->update(['unique_code' => $uniqueCode]);
                }
            });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('payments')->whereNotNull('voucher_id')->exists()) {
            throw new RuntimeException('Migrasi tidak dapat dibatalkan selama pembayaran voucher masih tersimpan.');
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['voucher_id']);
            $table->dropUnique(['voucher_id']);
            $table->dropIndex(['provider', 'unique_code', 'transaction_status']);
            $table->dropColumn(['voucher_id', 'unique_code']);
            $table->foreignId('order_id')->nullable(false)->change();
        });
    }
};
