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
            $table->string('nama_toko')->nullable()->default('Queen Degan Jelly')->after('id');
            $table->text('alamat')->nullable()->after('nama_toko');
            $table->string('nomor_hp', 50)->nullable()->default('+62 812-3456-7890')->after('alamat');
            $table->string('jam_operasional', 100)->nullable()->default('Senin - Sabtu: 08:00 - 17:00 WIB')->after('nomor_hp');
        });

        DB::table('profil_usaha')->where('id', 1)->update([
            'nama_toko' => 'Queen Degan Jelly',
            'alamat' => "Muncar, Banyuwangi\nJawa Timur, Indonesia",
            'nomor_hp' => '+62 812-3456-7890',
            'jam_operasional' => 'Senin - Sabtu: 08:00 - 17:00 WIB',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profil_usaha', function (Blueprint $table) {
            $table->dropColumn(['nama_toko', 'alamat', 'nomor_hp', 'jam_operasional']);
        });
    }
};
