<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherPackage;
use App\Services\VoucherPurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class VoucherPurchaseFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_voucher_purchase_uses_package_price_and_creates_pending_qris_payment(): void
    {
        $package = $this->createPackage();

        $response = $this->post(route('vouchers.purchase.store'), $this->purchaseData([
            'paket_voucher_id' => $package->id,
            'harga' => 1,
            'total' => 1,
        ]));

        $response->assertRedirect();
        $voucher = Voucher::query()->with('payment')->firstOrFail();
        $this->assertSame(Voucher::STATUS_WAITING_PAYMENT, $voucher->status);
        $this->assertMatchesRegularExpression('/\AQDJ-V-[A-HJ-NP-Z2-9]{8}\z/', $voucher->kode_voucher);
        $this->assertSame(9, $package->fresh()->stok);
        $this->assertSame('Paket Uji', $voucher->nama_paket);
        $this->assertSame(25000, $voucher->harga);
        $this->assertSame(3, $voucher->jumlah_batok);
        $this->assertSame(4, $voucher->maks_buah);
        $this->assertSame(25100, $voucher->payment->gross_amount);
        $this->assertSame(100, $voucher->payment->unique_code);
        $this->assertNull($voucher->payment->order_id);
        $this->assertSame('pending', $voucher->payment->transaction_status);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_inactive_voucher_package_cannot_be_purchased(): void
    {
        $package = $this->createPackage(['aktif' => false]);

        $response = $this->post(route('vouchers.purchase.store'), $this->purchaseData([
            'paket_voucher_id' => $package->id,
        ]));

        $response->assertSessionHasErrors('paket_voucher_id');
        $this->assertDatabaseCount('voucher', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_sold_out_voucher_package_shows_habis_and_cannot_be_purchased(): void
    {
        $package = $this->createPackage(['stok' => 0]);

        $this->get(route('vouchers.purchase'))
            ->assertOk()
            ->assertSee('Habis')
            ->assertSee('Stok Voucher Habis');

        $response = $this->post(route('vouchers.purchase.store'), $this->purchaseData([
            'paket_voucher_id' => $package->id,
        ]));

        $response->assertSessionHasErrors('paket_voucher_id');
        $this->assertDatabaseCount('voucher', 0);
        $this->assertSame(0, $package->fresh()->stok);
    }

    public function test_rejected_voucher_payment_displays_reason_and_allows_reupload(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $voucher = $this->createPendingVoucher();

        $proofUrl = URL::temporarySignedRoute(
            'voucher.payments.proof.store',
            $voucher->payment->expires_at,
            ['voucher' => $voucher->id],
        );
        $showUrl = URL::temporarySignedRoute(
            'voucher.payments.show',
            $voucher->payment->expires_at,
            ['voucher' => $voucher->id],
        );

        $this->post($proofUrl, ['payment_proof' => $this->pngProof()])->assertRedirect();

        $this->actingAs($admin)
            ->patch(route('admin.payments.reject', $voucher->payment), [
                'rejection_reason' => 'Bukti transfer buram, mohon foto ulang.',
            ])
            ->assertSessionHas('success');

        $this->get($showUrl)
            ->assertOk()
            ->assertSee('Bukti Pembayaran Ditolak Admin')
            ->assertSee('Bukti transfer buram, mohon foto ulang.')
            ->assertSee('Kirim Bukti Pembayaran');

        $this->post($proofUrl, ['payment_proof' => $this->pngProof()])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(Voucher::STATUS_WAITING_VERIFICATION, $voucher->fresh()->status);
        $this->assertSame('pending_verification', $voucher->payment->fresh()->transaction_status);
    }

    public function test_signed_voucher_payment_page_and_proof_upload_wait_for_admin_verification(): void
    {
        Storage::fake('local');
        BusinessProfile::query()->findOrFail(1)->update(['qris_image_path' => 'qris/partner.png']);
        $voucher = $this->createPendingVoucher();
        $showUrl = URL::temporarySignedRoute(
            'voucher.payments.show',
            $voucher->payment->expires_at,
            ['voucher' => $voucher->id],
        );

        $this->get($showUrl)
            ->assertOk()
            ->assertSee('Pembayaran Voucher Hadiah')
            ->assertSee('Kode QRIS pembayaran mitra')
            ->assertSee('Paket Uji');

        $proofUrl = URL::temporarySignedRoute(
            'voucher.payments.proof.store',
            $voucher->payment->expires_at,
            ['voucher' => $voucher->id],
        );
        $response = $this->post($proofUrl, [
            'payment_proof' => $this->pngProof(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Bukti pembayaran berhasil dikirim dan menunggu verifikasi admin.');
        $this->assertSame(Voucher::STATUS_WAITING_VERIFICATION, $voucher->fresh()->status);
        $this->assertSame('pending_verification', $voucher->payment->fresh()->transaction_status);
        Storage::disk('local')->assertExists($voucher->payment->fresh()->proof_path);
    }

    public function test_unsigned_voucher_payment_page_is_forbidden(): void
    {
        $voucher = $this->createPendingVoucher();

        $this->get(route('voucher.payments.show', $voucher))
            ->assertForbidden();
    }

    public function test_invalid_format_and_unknown_voucher_codes_use_the_same_error(): void
    {
        $invalidFormat = $this->post(route('vouchers.check.submit'), [
            'kode_voucher' => 'BUKAN-KODE',
            'phone_last4' => '6789',
        ]);
        $unknownCode = $this->post(route('vouchers.check.submit'), [
            'kode_voucher' => 'QDJ-V-ABCDEFGH',
            'phone_last4' => '6789',
        ]);

        $invalidFormat->assertSessionHasErrors([
            'lookup' => 'Kode voucher atau verifikasi tidak cocok.',
        ]);
        $unknownCode->assertSessionHasErrors([
            'lookup' => 'Kode voucher atau verifikasi tidak cocok.',
        ]);
    }

    public function test_voucher_lookup_is_limited_to_five_failed_attempts_per_ip(): void
    {
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $response = $this->post(route('vouchers.check.submit'), [
                'kode_voucher' => 'QDJ-V-ABCDEFGH',
                'phone_last4' => '6789',
            ]);

            $response->assertSessionHasErrors('lookup');
        }
    }

    public function test_admin_activates_verified_voucher_with_random_code_and_validity(): void
    {
        Storage::fake('local');
        $this->travelTo(now()->startOfDay());
        $admin = User::factory()->create(['role' => 'admin']);
        $voucher = $this->createPendingVoucher();
        Storage::disk('local')->put('payment-proofs/bukti.png', 'proof');
        $voucher->update(['status' => Voucher::STATUS_WAITING_VERIFICATION]);
        $voucher->payment->update([
            'proof_path' => 'payment-proofs/bukti.png',
            'proof_uploaded_at' => now(),
            'transaction_status' => 'pending_verification',
        ]);

        $response = $this->actingAs($admin)
            ->patch(route('admin.voucher-payments.activate', $voucher));

        $response->assertRedirect(route('admin.voucher-payments.show', $voucher));
        $activeVoucher = $voucher->fresh();
        $this->assertSame(Voucher::STATUS_ACTIVE, $activeVoucher->status);
        $this->assertMatchesRegularExpression('/\AQDJ-V-[A-HJ-NP-Z2-9]{8}\z/', $activeVoucher->kode_voucher);
        $this->assertSame(now()->addDays(30)->toDateTimeString(), $activeVoucher->berlaku_sampai->toDateTimeString());
        $this->assertSame('paid', $activeVoucher->payment->transaction_status);
    }

    public function test_only_admin_can_open_voucher_payment_verification(): void
    {
        $voucher = $this->createPendingVoucher();

        $this->get(route('admin.voucher-payments.show', $voucher))
            ->assertRedirect(route('admin.login'));

        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer)
            ->get(route('admin.voucher-payments.show', $voucher))
            ->assertForbidden();
    }

    public function test_admin_can_view_a_private_voucher_payment_proof(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $voucher = $this->createPendingVoucher();
        $proofPath = $this->pngProof()->store('payment-proofs', 'local');
        $voucher->payment->update(['proof_path' => $proofPath]);

        $this->actingAs($admin)
            ->get(route('admin.voucher-payments.proof', $voucher))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    public function test_sender_can_check_voucher_and_get_redemption_link_without_exposing_phone(): void
    {
        $voucher = $this->activateVoucher();

        $response = $this->post(route('vouchers.check.submit'), [
            'kode_voucher' => $voucher->kode_voucher,
            'phone_last4' => '6789',
        ]);

        $response->assertOk()
            ->assertSee($voucher->kode_voucher)
            ->assertSee('Nama Pengirim')
            ->assertSee(url('/voucher-tukar/'.$voucher->kode_voucher))
            ->assertDontSee('08123456789');
    }

    public function test_unpaid_voucher_is_cancelled_after_two_hours(): void
    {
        $voucher = $this->createPendingVoucher();

        $this->travel(3)->hours();

        $this->artisan('app:expire-custom-orders')->assertSuccessful();

        $this->assertSame(Voucher::STATUS_CANCELLED, $voucher->fresh()->status);
        $this->assertSame('expired', $voucher->payment->fresh()->transaction_status);
    }

    public function test_active_voucher_becomes_expired_when_checked_after_validity(): void
    {
        $voucher = $this->activateVoucher();

        $this->travel(31)->days();

        $response = $this->post(route('vouchers.check.submit'), [
            'kode_voucher' => $voucher->kode_voucher,
            'phone_last4' => '6789',
        ]);

        $response->assertOk()->assertSee(Voucher::STATUS_EXPIRED);
        $this->assertSame(Voucher::STATUS_EXPIRED, $voucher->fresh()->status);
    }

    private function createPackage(array $overrides = []): VoucherPackage
    {
        return VoucherPackage::query()->create(array_merge([
            'nama_paket' => 'Paket Uji',
            'harga' => 25000,
            'jumlah_batok' => 3,
            'maks_buah' => 4,
            'aktif' => true,
        ], $overrides));
    }

    private function createPendingVoucher(array $overrides = []): Voucher
    {
        $package = $this->createPackage();

        return app(VoucherPurchaseService::class)->create(
            $package->id,
            array_merge($this->purchaseData(), $overrides),
        );
    }

    private function activateVoucher(): Voucher
    {
        $voucher = $this->createPendingVoucher();
        $voucher->update(['status' => Voucher::STATUS_WAITING_VERIFICATION]);
        $voucher->payment->update([
            'proof_uploaded_at' => now(),
            'transaction_status' => 'pending_verification',
        ]);

        return app(VoucherPurchaseService::class)->activate($voucher);
    }

    private function purchaseData(array $overrides = []): array
    {
        return array_merge([
            'nama_pengirim' => 'Nama Pengirim',
            'nomor_hp_pengirim' => '08123456789',
            'nama_penerima' => 'Nama Penerima',
            'pesan_ucapan' => 'Selamat menikmati!',
        ], $overrides);
    }

    private function pngProof(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'bukti.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jL1sAAAAASUVORK5CYII='),
        );
    }
}
