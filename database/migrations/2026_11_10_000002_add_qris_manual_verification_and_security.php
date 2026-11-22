<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah nama merchant pada profil usaha
        Schema::table('profil_usaha', function (Blueprint $table) {
            $table->string('merchant_name')->nullable()->default('Queen Degan Jelly')->after('qris_image_path');
        });

        // 2. Tambah kolom keamanan dan verifikasi pada payments
        Schema::table('payments', function (Blueprint $table) {
            $table->string('proof_hash', 64)->nullable()->index()->after('proof_path');
            $table->text('rejection_reason')->nullable()->after('proof_uploaded_at');
            $table->text('customer_notes')->nullable()->after('rejection_reason');
            $table->foreignId('verified_by')->nullable()->after('customer_notes')->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->after('verified_by');
        });

        // 3. Tabel log audit verifikasi pembayaran
        Schema::create('payment_verification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('voucher_id')->nullable()->constrained('voucher')->nullOnDelete();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->string('action'); // 'approved', 'rejected'
            $table->text('reason')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['payment_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_verification_logs');

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropColumn([
                'proof_hash',
                'rejection_reason',
                'customer_notes',
                'verified_by',
                'verified_at',
            ]);
        });

        Schema::table('profil_usaha', function (Blueprint $table) {
            $table->dropColumn('merchant_name');
        });
    }
};
