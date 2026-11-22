<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('jumlah_batok')->default(1)->after('price');
        });

        // Set default batok count for package products
        DB::table('products')
            ->where('name', 'like', '%Degan Jelly 3%')
            ->update(['jumlah_batok' => 3]);

        DB::table('products')
            ->where('name', 'like', '%Degan Jelly 4%')
            ->update(['jumlah_batok' => 4]);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('jumlah_batok');
        });
    }
};
