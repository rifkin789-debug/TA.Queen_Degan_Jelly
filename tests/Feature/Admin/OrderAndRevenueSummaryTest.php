<?php

namespace Tests\Feature\Admin;

use App\Models\Fruit;
use App\Models\Order;
use App\Models\OrderFruit;
use App\Models\Payment;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderAndRevenueSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_separates_paid_catalog_custom_and_voucher_revenue(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->createOrder('QDJ-CATALOG-1', 'Katalog', 10000);
        $this->createOrder('QDJ-CUSTOM-1', 'Custom', 12000);
        $this->createOrder('QDJ-VOUCHER-ORDER-1', 'Voucher', 30000);

        $activeVoucher = $this->createVoucher(Voucher::STATUS_ACTIVE, 'QDJ-V-ACTIVE01');
        $usedVoucher = $this->createVoucher(Voucher::STATUS_USED, 'QDJ-V-USED0001');
        $expiredVoucher = $this->createVoucher(Voucher::STATUS_EXPIRED, 'QDJ-V-EXPIRED1');
        $this->createVoucherPayment($activeVoucher, 45003);
        $this->createVoucherPayment($usedVoucher, 55004);
        $this->createVoucherPayment($expiredVoucher, 65005);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('Pendapatan Katalog')
            ->assertSee('Rp10.000')
            ->assertSee('Pendapatan Custom')
            ->assertSee('Rp12.000')
            ->assertSee('Pendapatan Voucher')
            ->assertSee('Rp100.007')
            ->assertSee('Rp122.007');
    }

    public function test_admin_order_detail_shows_voucher_fruit_snapshot(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $fruit = Fruit::query()->create([
            'nama_buah' => 'Nangka',
            'harga_porsi' => 1500,
            'stok_porsi' => 5,
            'aktif' => true,
        ]);
        $voucher = $this->createVoucher(Voucher::STATUS_USED, 'QDJ-V-USED0002');
        $order = $this->createOrder('QDJ-VOUCHER-ORDER-2', 'Voucher', 0, $voucher->id);
        OrderFruit::query()->create([
            'id_pesanan' => $order->id,
            'id_buah' => $fruit->id,
            'nama_buah' => 'Nangka',
            'harga_porsi' => 1500,
            'jumlah_porsi' => 2,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.orders.show', $order));

        $response->assertOk()
            ->assertSee('Voucher')
            ->assertSee('Nangka × 2 porsi');
    }

    public function test_admin_order_detail_shows_custom_fruit_snapshots(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $fruit = Fruit::query()->create([
            'nama_buah' => 'Alpukat',
            'harga_porsi' => 1000,
            'stok_porsi' => 5,
            'aktif' => true,
        ]);
        $order = $this->createOrder('QDJ-CUSTOM-DETAIL', 'Custom', 2000);
        OrderFruit::query()->create([
            'id_pesanan' => $order->id,
            'id_buah' => $fruit->id,
            'nama_buah' => 'Alpukat',
            'harga_porsi' => 1000,
            'jumlah_porsi' => 2,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.orders.show', $order));

        $response->assertOk()
            ->assertSee('Custom')
            ->assertSee('Alpukat × 2 porsi');
    }

    public function test_cancelling_a_redeemed_voucher_order_restores_fruit_stock_once(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $fruit = Fruit::query()->create([
            'nama_buah' => 'Alpukat',
            'harga_porsi' => 1000,
            'stok_porsi' => 3,
            'aktif' => true,
        ]);
        $voucher = $this->createVoucher(Voucher::STATUS_USED, 'QDJ-V-USED0003');
        $order = $this->createOrder('QDJ-VOUCHER-ORDER-3', 'Voucher', 0, $voucher->id);
        $order->update(['order_status' => 'sedang_disiapkan', 'stock_returned_at' => null]);
        OrderFruit::query()->create([
            'id_pesanan' => $order->id,
            'id_buah' => $fruit->id,
            'nama_buah' => 'Alpukat',
            'harga_porsi' => 1000,
            'jumlah_porsi' => 2,
        ]);

        $this->actingAs($admin)->patch(route('admin.orders.status', $order), [
            'order_status' => 'dibatalkan',
        ])->assertRedirect();

        $this->assertSame(5, $fruit->fresh()->stok_porsi);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'order_status' => 'dibatalkan',
        ]);
        $this->assertNotNull($order->fresh()->stock_returned_at);
        $this->assertDatabaseHas('voucher', [
            'id' => $voucher->id,
            'status' => Voucher::STATUS_USED,
        ]);
    }

    private function createOrder(string $code, string $type, int $total, ?int $voucherId = null): Order
    {
        return Order::query()->create([
            'user_id' => null,
            'order_code' => $code,
            'customer_name' => 'Pelanggan Uji',
            'phone' => '08123456789',
            'subtotal' => $total,
            'shipping_cost' => 0,
            'total' => $total,
            'payment_status' => 'paid',
            'order_status' => 'pesanan_diterima',
            'type' => $type,
            'fulfillment_method' => 'ambil',
            'jumlah_batok' => 1,
            'id_voucher' => $voucherId,
        ]);
    }

    private function createVoucher(string $status, string $code): Voucher
    {
        return Voucher::query()->create([
            'nama_paket' => 'Paket Uji',
            'harga' => 45000,
            'jumlah_batok' => 2,
            'maks_buah' => 4,
            'nama_pengirim' => 'Pengirim Uji',
            'nomor_hp_pengirim' => '08123456789',
            'kode_voucher' => $code,
            'status' => $status,
            'berlaku_sampai' => now()->addDays(30),
        ]);
    }

    private function createVoucherPayment(Voucher $voucher, int $grossAmount): void
    {
        Payment::query()->create([
            'voucher_id' => $voucher->id,
            'provider' => 'qris_static',
            'gross_amount' => $grossAmount,
            'transaction_status' => 'paid',
            'payment_type' => 'qris',
        ]);
    }
}
