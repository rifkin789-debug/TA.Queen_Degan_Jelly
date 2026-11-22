<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buah', function (Blueprint $table) {
            $table->id();
            $table->string('nama_buah')->unique();
            $table->unsignedBigInteger('harga_porsi');
            $table->unsignedInteger('stok_porsi')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->index(['aktif', 'stok_porsi']);
        });

        Schema::create('paket_voucher', function (Blueprint $table) {
            $table->id();
            $table->string('nama_paket');
            $table->unsignedBigInteger('harga');
            $table->unsignedInteger('jumlah_batok');
            $table->unsignedInteger('maks_buah')->default(4);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->index(['aktif', 'harga']);
        });

        Schema::create('voucher', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_paket')->nullable()->constrained('paket_voucher')->nullOnDelete();
            $table->string('nama_paket');
            $table->unsignedBigInteger('harga');
            $table->unsignedInteger('jumlah_batok');
            $table->unsignedInteger('maks_buah');
            $table->string('nama_pengirim');
            $table->string('nomor_hp_pengirim', 30);
            $table->string('nama_penerima')->nullable();
            $table->string('pesan_ucapan', 200)->nullable();
            $table->string('kode_voucher', 14)->nullable()->unique();
            $table->string('status')->default('Menunggu Pembayaran');
            $table->timestamp('berlaku_sampai')->nullable();
            $table->timestamp('dipakai_pada')->nullable();
            $table->timestamps();
            $table->index('id_paket');
            $table->index('nomor_hp_pengirim');
            $table->index(['status', 'berlaku_sampai']);
        });

        Schema::create('pesanan_buah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pesanan')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('id_buah')->nullable()->constrained('buah')->nullOnDelete();
            $table->string('nama_buah');
            $table->unsignedBigInteger('harga_porsi');
            $table->unsignedInteger('jumlah_porsi');
            $table->timestamps();
            $table->unique(['id_pesanan', 'id_buah']);
            $table->index('id_buah');
        });

        Schema::create('profil_usaha', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('harga_dasar_custom')->default(0);
            $table->unsignedInteger('maks_buah_custom')->default(4);
            $table->unsignedInteger('masa_berlaku_voucher_hari')->default(30);
            $table->string('qris_image_path')->nullable();
            $table->timestamps();
        });

        DB::table('profil_usaha')->insert([
            'id' => 1,
            'harga_dasar_custom' => 0,
            'maks_buah_custom' => 4,
            'masa_berlaku_voucher_hari' => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->text('address')->nullable()->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('type')->default('Katalog');
            $table->string('fulfillment_method')->default('delivery');
            $table->unsignedInteger('jumlah_batok')->default(1);
            $table->foreignId('id_voucher')->nullable()->constrained('voucher')->nullOnDelete();
            $table->index(['type', 'created_at']);
            $table->index('id_voucher');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('proof_path')->nullable();
            $table->timestamp('proof_uploaded_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->index(['transaction_status', 'expires_at']);
        });
    }

    public function down(): void
    {
        if (DB::table('orders')->whereNull('user_id')->exists()
            || DB::table('orders')->whereNull('address')->exists()) {
            throw new RuntimeException('Migrasi tidak dapat dibatalkan selama masih ada pesanan tamu atau ambil sendiri.');
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['transaction_status', 'expires_at']);
            $table->dropColumn(['proof_path', 'proof_uploaded_at', 'expires_at']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['id_voucher']);
            $table->dropIndex(['type', 'created_at']);
            $table->dropIndex(['id_voucher']);
            $table->dropColumn(['type', 'fulfillment_method', 'jumlah_batok', 'id_voucher']);
            $table->foreignId('user_id')->nullable(false)->change();
            $table->text('address')->nullable(false)->change();
        });

        Schema::dropIfExists('pesanan_buah');
        Schema::dropIfExists('voucher');
        Schema::dropIfExists('paket_voucher');
        Schema::dropIfExists('buah');
        Schema::dropIfExists('profil_usaha');
    }
};
