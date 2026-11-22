<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedSmallInteger('unique_code')->nullable();
            $table->timestamp('stock_returned_at')->nullable();
            $table->index(['unique_code', 'payment_status']);
        });

        Schema::table('profil_usaha', function (Blueprint $table) {
            $table->unsignedSmallInteger('kode_pembayaran_berikutnya')->default(100);
        });
    }

    public function down(): void
    {
        if (DB::table('orders')->whereNotNull('unique_code')->exists()) {
            throw new RuntimeException('Migrasi tidak dapat dibatalkan selama kode pembayaran masih tersimpan pada pesanan.');
        }

        Schema::table('profil_usaha', function (Blueprint $table) {
            $table->dropColumn('kode_pembayaran_berikutnya');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['unique_code', 'payment_status']);
            $table->dropColumn(['unique_code', 'stock_returned_at']);
        });
    }
};
