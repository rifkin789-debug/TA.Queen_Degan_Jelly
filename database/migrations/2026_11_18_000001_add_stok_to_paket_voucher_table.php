<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('paket_voucher', 'stok')) {
            Schema::table('paket_voucher', function (Blueprint $table) {
                $table->unsignedInteger('stok')->default(10)->after('maks_buah');
            });
        }

        if (! Schema::hasColumn('voucher', 'stock_returned_at')) {
            Schema::table('voucher', function (Blueprint $table) {
                $table->timestamp('stock_returned_at')->nullable()->after('dipakai_pada');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('voucher', 'stock_returned_at')) {
            Schema::table('voucher', function (Blueprint $table) {
                $table->dropColumn('stock_returned_at');
            });
        }

        if (Schema::hasColumn('paket_voucher', 'stok')) {
            Schema::table('paket_voucher', function (Blueprint $table) {
                $table->dropColumn('stok');
            });
        }
    }
};
