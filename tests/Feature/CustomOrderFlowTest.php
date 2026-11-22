<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\Fruit;
use App\Models\Order;
use App\Models\User;
use App\Services\CustomOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class CustomOrderFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_form_only_shows_active_fruits_with_stock(): void
    {
        $this->createFruit('Nangka', 1500, 10);
        $this->createFruit('Durian nonaktif', 2000, 10, false);
        $this->createFruit('Alpukat habis', 1200, 0);

        $response = $this->get(route('custom.create'));

        $response->assertOk();
        $response->assertSee('Nangka');
        $response->assertDontSee('Durian nonaktif');
        $response->assertDontSee('Alpukat habis');
        $response->assertSee('Ringkasan harga');
    }

    public function test_custom_order_uses_server_prices_and_reserves_fruit_stock(): void
    {
        config(['shop.shipping_cost' => 10000]);
        BusinessProfile::query()->findOrFail(1)->update(['harga_dasar_custom' => 5000]);
        $nangka = $this->createFruit('Nangka', 1000, 20);
        $alpukat = $this->createFruit('Alpukat', 1500, 20);

        $response = $this->post(route('custom.store'), $this->orderData([
            'buah' => [$nangka->id, $alpukat->id],
            'jumlah_batok' => 3,
            'fulfillment_method' => 'antar',
            'address' => 'Alamat antar',
            'subtotal' => 1,
            'total' => 1,
            'unique_code' => 999,
        ]));

        $response->assertRedirect();

        $order = Order::query()->where('type', 'Custom')->firstOrFail();
        $this->assertSame(22500, $order->subtotal);
        $this->assertSame(10000, $order->shipping_cost);
        $this->assertSame(100, $order->unique_code);
        $this->assertSame(32600, $order->total);
        $this->assertSame(3, $order->jumlah_batok);
        $this->assertNull($order->user_id);
        $this->assertSame(17, $nangka->fresh()->stok_porsi);
        $this->assertSame(17, $alpukat->fresh()->stok_porsi);
        $this->assertDatabaseHas('pesanan_buah', [
            'id_pesanan' => $order->id,
            'id_buah' => $nangka->id,
            'nama_buah' => 'Nangka',
            'harga_porsi' => 1000,
            'jumlah_porsi' => 3,
        ]);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'provider' => 'qris_static',
            'gross_amount' => 32600,
            'transaction_status' => 'pending',
        ]);
    }

    public function test_custom_order_rejects_more_fruits_than_business_limit_without_reserving_stock(): void
    {
        BusinessProfile::query()->findOrFail(1)->update(['maks_buah_custom' => 1]);
        $nangka = $this->createFruit('Nangka', 1000, 10);
        $alpukat = $this->createFruit('Alpukat', 1500, 10);

        $response = $this->post(route('custom.store'), $this->orderData([
            'buah' => [$nangka->id, $alpukat->id],
        ]));

        $response->assertSessionHasErrors('buah');
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(10, $nangka->fresh()->stok_porsi);
        $this->assertSame(10, $alpukat->fresh()->stok_porsi);
    }

    public function test_custom_order_rejects_a_batok_count_above_available_stock(): void
    {
        $nangka = $this->createFruit('Nangka', 1000, 2);

        $response = $this->post(route('custom.store'), $this->orderData([
            'buah' => [$nangka->id],
            'jumlah_batok' => 3,
        ]));

        $response->assertSessionHasErrors('buah');
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(2, $nangka->fresh()->stok_porsi);
    }

    public function test_custom_order_rejects_inactive_fruit_even_if_the_client_submits_its_id(): void
    {
        $nangka = $this->createFruit('Nangka', 1000, 10, false);

        $response = $this->post(route('custom.store'), $this->orderData([
            'buah' => [$nangka->id],
        ]));

        $response->assertSessionHasErrors('buah');
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(10, $nangka->fresh()->stok_porsi);
    }

    public function test_custom_payment_proof_accepts_jpeg_and_waits_for_admin_verification(): void
    {
        Storage::fake('local');
        $nangka = $this->createFruit('Nangka', 1000, 10);
        $order = app(CustomOrderService::class)->create($this->orderData([
            'buah' => [$nangka->id],
        ]));
        $signedUrl = URL::temporarySignedRoute(
            'payments.qris.proof.store',
            now()->addHour(),
            ['order' => $order->id],
        );

        $response = $this->post($signedUrl, [
            'payment_proof' => UploadedFile::fake()->createWithContent(
                'bukti.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jL1sAAAAASUVORK5CYII='),
            ),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Bukti pembayaran berhasil dikirim dan menunggu verifikasi admin.');
        $this->assertSame('pending_verification', $order->payment->fresh()->transaction_status);
        $this->assertSame('pending_verification', $order->fresh()->payment_status);
        Storage::disk('local')->assertExists($order->payment->fresh()->proof_path);
    }

    public function test_custom_payment_proof_rejects_pdf_files(): void
    {
        Storage::fake('local');
        $nangka = $this->createFruit('Nangka', 1000, 10);
        $order = app(CustomOrderService::class)->create($this->orderData([
            'buah' => [$nangka->id],
        ]));
        $signedUrl = URL::temporarySignedRoute(
            'payments.qris.proof.store',
            now()->addHour(),
            ['order' => $order->id],
        );

        $response = $this->post($signedUrl, [
            'payment_proof' => UploadedFile::fake()->create('bukti.jpg', 100, 'application/pdf'),
        ]);

        $response->assertSessionHasErrors('payment_proof');
        $this->assertSame('pending', $order->payment->fresh()->transaction_status);
        $this->assertDatabaseMissing('payments', [
            'order_id' => $order->id,
            'proof_path' => 'payment-proofs/bukti.jpg',
        ]);
    }

    public function test_signed_qris_page_shows_payment_total_and_partner_qr_image(): void
    {
        BusinessProfile::query()->findOrFail(1)->update(['qris_image_path' => 'qris/partner.png']);
        $nangka = $this->createFruit('Nangka', 1000, 10);
        $order = app(CustomOrderService::class)->create($this->orderData([
            'buah' => [$nangka->id],
        ]));
        $signedUrl = URL::temporarySignedRoute(
            'payments.qris.show',
            $order->payment->expires_at,
            ['order' => $order->id],
        );

        $response = $this->get($signedUrl);

        $response->assertOk();
        $response->assertSee($order->order_code);
        $response->assertSee('Kode QRIS pembayaran mitra');
        $response->assertSee('Total bayar');
        $response->assertSee('Kirim Bukti Pembayaran');
    }

    public function test_qris_details_require_a_temporary_signed_url(): void
    {
        $nangka = $this->createFruit('Nangka', 1000, 10);
        $order = app(CustomOrderService::class)->create($this->orderData([
            'buah' => [$nangka->id],
        ]));

        $this->get(route('payments.qris.show', $order))
            ->assertForbidden();
    }

    public function test_custom_order_expires_after_two_hours_and_restores_fruit_stock(): void
    {
        $this->travelTo(now()->startOfDay());
        $nangka = $this->createFruit('Nangka', 1000, 10);
        $order = app(CustomOrderService::class)->create($this->orderData([
            'buah' => [$nangka->id],
            'jumlah_batok' => 2,
        ]));

        $this->travel(3)->hours();

        $this->artisan('app:expire-custom-orders')->assertSuccessful();

        $this->assertSame(10, $nangka->fresh()->stok_porsi);
        $this->assertSame('dibatalkan', $order->fresh()->order_status);
        $this->assertSame('expired', $order->payment->fresh()->transaction_status);
        $this->assertNotNull($order->fresh()->stock_returned_at);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'status' => 'dibatalkan',
        ]);
    }

    public function test_admin_cancellation_restores_custom_fruit_stock_only_once(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $nangka = $this->createFruit('Nangka', 1000, 10);
        $order = app(CustomOrderService::class)->create($this->orderData([
            'buah' => [$nangka->id],
            'jumlah_batok' => 2,
        ]));

        $this->actingAs($admin)
            ->patch(route('admin.orders.status', $order), ['order_status' => 'dibatalkan'])
            ->assertRedirect();
        $this->actingAs($admin)
            ->patch(route('admin.orders.status', $order), ['order_status' => 'dibatalkan'])
            ->assertRedirect();

        $this->assertSame(10, $nangka->fresh()->stok_porsi);
        $this->assertSame('cancelled', $order->payment->fresh()->transaction_status);
        $this->assertNotNull($order->fresh()->stock_returned_at);
        $this->assertDatabaseCount('order_status_histories', 2);
    }

    public function test_public_tracking_requires_the_last_four_phone_digits_and_escapes_customer_content(): void
    {
        $nangka = $this->createFruit('<script>alert(1)</script>', 1000, 10);
        $order = app(CustomOrderService::class)->create($this->orderData([
            'customer_name' => 'Dewi <script>alert(1)</script>',
            'phone' => '+62 812-3456-7890',
            'buah' => [$nangka->id],
        ]));

        $invalidResponse = $this->post(route('orders.track.show'), [
            'order_code' => $order->order_code,
            'phone_last4' => '1234',
        ]);

        $invalidResponse->assertSessionHasErrors('lookup');

        $response = $this->post(route('orders.track.show'), [
            'order_code' => $order->order_code,
            'phone_last4' => '7890',
        ]);

        $response->assertOk();
        $response->assertSee('Dewi &lt;script&gt;alert(1)&lt;/script&gt;', false);
        $response->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
        $response->assertDontSee('<script>alert(1)</script>', false);
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

    private function orderData(array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'Pelanggan Uji',
            'phone' => '08123456789',
            'fulfillment_method' => 'ambil',
            'address' => '',
            'notes' => '',
            'buah' => [],
            'jumlah_batok' => 1,
        ], $overrides);
    }
}
