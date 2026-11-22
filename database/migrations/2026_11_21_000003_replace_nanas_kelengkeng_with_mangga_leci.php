<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ganti Kelengkeng dengan Leci
        $kelengkeng = DB::table('buah')->where('nama_buah', 'Kelengkeng')->first();
        if ($kelengkeng) {
            DB::table('buah')->where('id', $kelengkeng->id)->update([
                'nama_buah' => 'Leci',
                'harga_porsi' => 3000,
                'stok_porsi' => 50,
                'aktif' => true,
                'updated_at' => now(),
            ]);
        } else {
            DB::table('buah')->updateOrInsert(
                ['nama_buah' => 'Leci'],
                ['harga_porsi' => 3000, 'stok_porsi' => 50, 'aktif' => true, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        // Ganti Nanas dengan Mangga
        $nanas = DB::table('buah')->where('nama_buah', 'Nanas')->first();
        if ($nanas) {
            DB::table('buah')->where('id', $nanas->id)->update([
                'nama_buah' => 'Mangga',
                'harga_porsi' => 3000,
                'stok_porsi' => 50,
                'aktif' => true,
                'updated_at' => now(),
            ]);
        } else {
            DB::table('buah')->updateOrInsert(
                ['nama_buah' => 'Mangga'],
                ['harga_porsi' => 3000, 'stok_porsi' => 50, 'aktif' => true, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        DB::table('buah')->where('nama_buah', 'Leci')->update(['nama_buah' => 'Kelengkeng', 'harga_porsi' => 3000]);
        DB::table('buah')->where('nama_buah', 'Mangga')->update(['nama_buah' => 'Nanas', 'harga_porsi' => 2000]);
    }
};
