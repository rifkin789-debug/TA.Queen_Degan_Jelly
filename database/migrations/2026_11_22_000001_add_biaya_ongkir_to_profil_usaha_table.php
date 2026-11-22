<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('profil_usaha', function (Blueprint $table) {
            $table->unsignedInteger('biaya_ongkir')->default(10000)->after('jam_operasional');
        });

        DB::table('profil_usaha')->where('id', 1)->update([
            'biaya_ongkir' => (int) config('shop.shipping_cost', 10000),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profil_usaha', function (Blueprint $table) {
            $table->dropColumn('biaya_ongkir');
        });
    }
};
