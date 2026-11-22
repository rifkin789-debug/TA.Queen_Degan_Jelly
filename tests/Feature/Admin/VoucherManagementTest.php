<?php

namespace Tests\Feature\Admin;

use App\Models\Payment;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoucherManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_a_voucher_and_cancel_it_without_reversing_verified_payment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $voucher = $this->createVoucher(Voucher::STATUS_ACTIVE);
        $payment = Payment::query()->create([
            'voucher_id' => $voucher->id,
            'provider' => 'qris_static',
            'gross_amount' => 45003,
            'unique_code' => 103,
            'transaction_status' => 'paid',
            'payment_type' => 'qris',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.vouchers.show', $voucher))
            ->assertOk()
            ->assertSee($voucher->nama_pengirim)
            ->assertSee($voucher->kode_voucher);

        $this->actingAs($admin)
            ->patch(route('admin.vouchers.cancel', $voucher))
            ->assertRedirect(route('admin.vouchers.show', $voucher));

        $this->assertDatabaseHas('voucher', [
            'id' => $voucher->id,
            'status' => Voucher::STATUS_CANCELLED,
        ]);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'transaction_status' => 'paid',
        ]);
    }

    public function test_admin_can_cancel_a_voucher_waiting_for_payment_verification(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $voucher = $this->createVoucher(Voucher::STATUS_WAITING_VERIFICATION);
        $payment = Payment::query()->create([
            'voucher_id' => $voucher->id,
            'provider' => 'qris_static',
            'gross_amount' => 45003,
            'unique_code' => 103,
            'transaction_status' => 'pending_verification',
            'payment_type' => 'qris',
            'proof_path' => 'payment-proofs/voucher-test.png',
            'proof_uploaded_at' => now(),
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.vouchers.cancel', $voucher))
            ->assertRedirect(route('admin.vouchers.show', $voucher));

        $this->assertDatabaseHas('voucher', [
            'id' => $voucher->id,
            'status' => Voucher::STATUS_CANCELLED,
        ]);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'transaction_status' => 'cancelled',
        ]);
    }

    public function test_admin_cannot_cancel_a_voucher_that_has_already_been_used(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $voucher = $this->createVoucher(Voucher::STATUS_USED);

        $response = $this->actingAs($admin)
            ->patch(route('admin.vouchers.cancel', $voucher));

        $response->assertSessionHasErrors('voucher');
        $this->assertDatabaseHas('voucher', [
            'id' => $voucher->id,
            'status' => Voucher::STATUS_USED,
        ]);
    }

    public function test_admin_voucher_list_expires_active_vouchers_past_their_validity(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $voucher = $this->createVoucher(Voucher::STATUS_ACTIVE);
        $voucher->update(['berlaku_sampai' => now()->subSecond()]);

        $this->actingAs($admin)
            ->get(route('admin.vouchers.index'))
            ->assertOk()
            ->assertSee(Voucher::STATUS_EXPIRED);

        $this->assertDatabaseHas('voucher', [
            'id' => $voucher->id,
            'status' => Voucher::STATUS_EXPIRED,
        ]);
    }

    public function test_customer_cannot_view_or_cancel_a_voucher_in_admin(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $voucher = $this->createVoucher(Voucher::STATUS_ACTIVE);

        $this->actingAs($customer)
            ->get(route('admin.vouchers.show', $voucher))
            ->assertForbidden();

        $this->actingAs($customer)
            ->patch(route('admin.vouchers.cancel', $voucher))
            ->assertForbidden();

        $this->assertDatabaseHas('voucher', [
            'id' => $voucher->id,
            'status' => Voucher::STATUS_ACTIVE,
        ]);
    }

    private function createVoucher(string $status): Voucher
    {
        return Voucher::query()->create([
            'nama_paket' => 'Paket Uji',
            'harga' => 45000,
            'jumlah_batok' => 2,
            'maks_buah' => 4,
            'nama_pengirim' => 'Pengirim Uji',
            'nomor_hp_pengirim' => '08123456789',
            'kode_voucher' => 'QDJ-V-ABCDEFGH',
            'status' => $status,
            'berlaku_sampai' => now()->addDays(30),
        ]);
    }
}
