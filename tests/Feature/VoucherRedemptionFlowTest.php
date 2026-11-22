<?php

namespace Tests\Feature;

use App\Models\Fruit;
use App\Models\Order;
use App\Models\Voucher;
use App\Models\VoucherPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\VoucherPurchaseService;
use Tests\TestCase;

class VoucherRedemptionFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_redemption_page_shows_only_sender_greeting_and_available_fruit_choices(): void
    {
        $voucher = $this->createActiveVoucher();
        $availableFruit = $this->createFruit('Nangka', 1200, 6);
        $inactiveFruit = $this->createFruit('Durian nonaktif', 2000, 9, false);
        $lowStockFruit = $this->createFruit('Alpukat stok rendah', 900, 2);

        $response = $this->get(route('vouchers.redeem.show', ['kode' => $voucher->kode_voucher]));

        $response->assertOk()
            ->assertSee('Nama Pengirim')
            ->assertSee('Nikmati hadiahnya!')
            ->assertSee('Nangka')
            ->assertDontSee('08123456789')
            ->assertDontSee('Nama Rahasia Penerima')
            ->assertDontSee('Durian nonaktif')
            ->assertDontSee('Alpukat stok rendah');
    }

    public function test_recipient_can_enter_a_code_and_open_the_redemption_page(): void
    {
        $voucher = $this->createActiveVoucher();

        $response = $this->post(route('vouchers.redeem.lookup'), [
            'kode_voucher' => $voucher->kode_voucher,
        ]);

        $response->assertRedirect(route('vouchers.redeem.show', ['kode' => $voucher->kode_voucher]));
    }

    public function test_redemption_page_escapes_sender_name_and_greeting(): void
    {
        $voucher = $this->createActiveVoucher();
        $voucher->update([
            'nama_pengirim' => '<script>alert(1)</script>',
            'pesan_ucapan' => '<img src=x onerror=alert(1)>',
        ]);

        $response = $this->get(route('vouchers.redeem.show', ['kode' => $voucher->kode_voucher]));

        $response->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertSee('&lt;img src=x onerror=alert(1)&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('<img src=x onerror=alert(1)>', false);
    }

    public function test_redemption_creates_paid_order_snapshots_fruit_and_decrements_stock(): void
    {
        $voucher = $this->createActiveVoucher();
        $nangka = $this->createFruit('Nangka', 1200, 10);
        $alpukat = $this->createFruit('Alpukat', 900, 10);

        $response = $this->post(
            route('vouchers.redeem.store', ['kode' => $voucher->kode_voucher]),
            $this->redemptionData([
                'buah' => [$nangka->id, $alpukat->id],
                'fulfillment_method' => 'antar',
                'address' => 'Jl. Contoh 123',
                'notes' => 'Tanpa es batu',
            ]),
        );

        $response->assertOk()->assertSee('Voucher berhasil ditukar');
        $order = Order::query()->where('type', 'Voucher')->firstOrFail();
        $this->assertMatchesRegularExpression('/\AQDJ-\d{8}-[A-Z0-9]{10}\z/', $order->order_code);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('sedang_disiapkan', $order->order_status);
        $this->assertSame('Voucher', $order->type);
        $this->assertSame('antar', $order->fulfillment_method);
        $this->assertSame('Jl. Contoh 123', $order->address);
        $this->assertSame(3, $order->jumlah_batok);
        $this->assertSame(0, $order->total);
        $this->assertSame($voucher->id, $order->id_voucher);
        $this->assertSame(Voucher::STATUS_USED, $voucher->fresh()->status);
        $this->assertSame(7, $nangka->fresh()->stok_porsi);
        $this->assertSame(7, $alpukat->fresh()->stok_porsi);
        $this->assertDatabaseHas('pesanan_buah', [
            'id_pesanan' => $order->id,
            'id_buah' => $nangka->id,
            'nama_buah' => 'Nangka',
            'harga_porsi' => 1200,
            'jumlah_porsi' => 3,
        ]);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'status' => 'sedang_disiapkan',
        ]);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_redeemed_order_can_be_tracked_with_recipient_phone_suffix(): void
    {
        $voucher = $this->createActiveVoucher();
        $nangka = $this->createFruit('Nangka', 1200, 10);
        $order = $this->redeem($voucher, [$nangka->id]);

        $response = $this->post(route('orders.track.show'), [
            'order_code' => $order->order_code,
            'phone_last4' => '5432',
        ]);

        $response->assertOk()
            ->assertSee($order->order_code)
            ->assertSee('Nangka')
            ->assertSee('Dibayar dengan voucher hadiah.');
    }

    public function test_voucher_cannot_be_redeemed_twice_or_reserve_stock_twice(): void
    {
        $voucher = $this->createActiveVoucher();
        $nangka = $this->createFruit('Nangka', 1200, 10);

        $this->post(
            route('vouchers.redeem.store', ['kode' => $voucher->kode_voucher]),
            $this->redemptionData(['buah' => [$nangka->id]]),
        )->assertOk();

        $secondResponse = $this->post(
            route('vouchers.redeem.store', ['kode' => $voucher->kode_voucher]),
            $this->redemptionData(['buah' => [$nangka->id]]),
        );

        $secondResponse->assertSessionHasErrors('voucher');
        $this->assertSame(Voucher::STATUS_USED, $voucher->fresh()->status);
        $this->assertSame(7, $nangka->fresh()->stok_porsi);
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_too_many_fruit_choices_leave_voucher_and_stock_unchanged(): void
    {
        $voucher = $this->createActiveVoucher(['maks_buah' => 1]);
        $nangka = $this->createFruit('Nangka', 1200, 10);
        $alpukat = $this->createFruit('Alpukat', 900, 10);

        $response = $this->post(
            route('vouchers.redeem.store', ['kode' => $voucher->kode_voucher]),
            $this->redemptionData(['buah' => [$nangka->id, $alpukat->id]]),
        );

        $response->assertSessionHasErrors('buah');
        $this->assertSame(Voucher::STATUS_ACTIVE, $voucher->fresh()->status);
        $this->assertSame(10, $nangka->fresh()->stok_porsi);
        $this->assertSame(10, $alpukat->fresh()->stok_porsi);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_inactive_fruit_and_insufficient_stock_are_rejected_without_using_voucher(): void
    {
        $voucher = $this->createActiveVoucher();
        $inactiveFruit = $this->createFruit('Durian nonaktif', 2000, 10, false);
        $lowStockFruit = $this->createFruit('Alpukat stok rendah', 900, 2);

        $inactiveResponse = $this->post(
            route('vouchers.redeem.store', ['kode' => $voucher->kode_voucher]),
            $this->redemptionData(['buah' => [$inactiveFruit->id]]),
        );
        $lowStockResponse = $this->post(
            route('vouchers.redeem.store', ['kode' => $voucher->kode_voucher]),
            $this->redemptionData(['buah' => [$lowStockFruit->id]]),
        );

        $inactiveResponse->assertSessionHasErrors('buah');
        $lowStockResponse->assertSessionHasErrors('buah');
        $this->assertSame(Voucher::STATUS_ACTIVE, $voucher->fresh()->status);
        $this->assertSame(10, $inactiveFruit->fresh()->stok_porsi);
        $this->assertSame(2, $lowStockFruit->fresh()->stok_porsi);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_delivery_redemption_requires_an_address(): void
    {
        $voucher = $this->createActiveVoucher();
        $nangka = $this->createFruit('Nangka', 1200, 10);

        $response = $this->post(
            route('vouchers.redeem.store', ['kode' => $voucher->kode_voucher]),
            $this->redemptionData([
                'buah' => [$nangka->id],
                'fulfillment_method' => 'antar',
                'address' => '',
            ]),
        );

        $response->assertSessionHasErrors('address');
        $this->assertSame(Voucher::STATUS_ACTIVE, $voucher->fresh()->status);
        $this->assertSame(10, $nangka->fresh()->stok_porsi);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_expired_voucher_is_marked_expired_and_cannot_be_redeemed(): void
    {
        $voucher = $this->createActiveVoucher();
        $nangka = $this->createFruit('Nangka', 1200, 10);

        $this->travel(31)->days();

        $response = $this->get(route('vouchers.redeem.show', ['kode' => $voucher->kode_voucher]));

        $response->assertOk()->assertSee('Masa berlaku voucher telah berakhir.');
        $this->assertSame(Voucher::STATUS_EXPIRED, $voucher->fresh()->status);
        $this->assertSame(10, $nangka->fresh()->stok_porsi);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_redemption_and_sender_lookup_share_failed_code_attempt_limit(): void
    {
        $voucher = $this->createActiveVoucher();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('vouchers.check.submit'), [
                'kode_voucher' => 'QDJ-V-ABCDEFGH',
                'phone_last4' => '6789',
            ])->assertSessionHasErrors('lookup');
        }

        $response = $this->get(route('vouchers.redeem.show', ['kode' => $voucher->kode_voucher]));

        $response->assertOk()
            ->assertSee('Kode voucher atau verifikasi tidak cocok.')
            ->assertDontSee('Nama Pengirim');
        $this->assertSame(Voucher::STATUS_ACTIVE, $voucher->fresh()->status);
    }

    private function createActiveVoucher(array $packageOverrides = []): Voucher
    {
        $package = VoucherPackage::query()->create(array_merge([
            'nama_paket' => 'Paket Uji',
            'harga' => 25000,
            'jumlah_batok' => 3,
            'maks_buah' => 2,
            'aktif' => true,
        ], $packageOverrides));
        $voucher = app(VoucherPurchaseService::class)->create($package->id, [
            'nama_pengirim' => 'Nama Pengirim',
            'nomor_hp_pengirim' => '08123456789',
            'nama_penerima' => 'Nama Rahasia Penerima',
            'pesan_ucapan' => 'Nikmati hadiahnya!',
        ]);
        $voucher->update(['status' => Voucher::STATUS_WAITING_VERIFICATION]);
        $voucher->payment->update([
            'proof_uploaded_at' => now(),
            'transaction_status' => 'pending_verification',
        ]);

        return app(VoucherPurchaseService::class)->activate($voucher);
    }

    private function createFruit(string $name, int $price, int $stock, bool $active = true): Fruit
    {
        return Fruit::query()->create([
            'nama_buah' => $name,
            'harga_porsi' => $price,
            'stok_porsi' => $stock,
            'aktif' => $active,
        ]);
    }

    private function redeem(Voucher $voucher, array $fruitIds): Order
    {
        $this->post(
            route('vouchers.redeem.store', ['kode' => $voucher->kode_voucher]),
            $this->redemptionData(['buah' => $fruitIds]),
        )->assertOk();

        return Order::query()->where('id_voucher', $voucher->id)->firstOrFail();
    }

    private function redemptionData(array $overrides = []): array
    {
        return array_merge([
            'buah' => [],
            'customer_name' => 'Nama Penerima',
            'phone' => '08198765432',
            'fulfillment_method' => 'ambil',
            'address' => '',
            'notes' => '',
        ], $overrides);
    }
}
