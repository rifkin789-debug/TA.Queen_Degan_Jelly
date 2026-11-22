<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_status_for_a_paid_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->createOrder($admin, 'paid', 'pesanan_diterima');

        $response = $this->actingAs($admin)->patch(route('admin.orders.status', $order), [
            'order_status' => 'sedang_disiapkan',
            'description' => 'Pesanan sedang disiapkan.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Status pesanan diperbarui.');
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'order_status' => 'sedang_disiapkan',
        ]);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'status' => 'sedang_disiapkan',
            'description' => 'Pesanan sedang disiapkan.',
        ]);
    }

    public function test_unpaid_order_keeps_its_status_and_shows_a_clear_message(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->createOrder($admin, 'pending', 'menunggu_pembayaran');

        $response = $this->actingAs($admin)->patch(route('admin.orders.status', $order), [
            'order_status' => 'pesanan_diterima',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Status pesanan tidak dapat diproses karena pembayaran belum diterima.');
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'order_status' => 'menunggu_pembayaran',
        ]);
        $this->assertDatabaseMissing('order_status_histories', [
            'order_id' => $order->id,
            'status' => 'pesanan_diterima',
        ]);
    }

    public function test_submitting_the_existing_order_status_does_not_create_duplicate_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->createOrder($admin, 'pending', 'menunggu_pembayaran');

        $response = $this->actingAs($admin)->patch(route('admin.orders.status', $order), [
            'order_status' => 'menunggu_pembayaran',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Status pesanan tidak berubah.');
        $this->assertDatabaseCount('order_status_histories', 0);
    }

    public function test_admin_can_cancel_an_unpaid_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->createOrder($admin, 'pending', 'menunggu_pembayaran');

        $response = $this->actingAs($admin)->patch(route('admin.orders.status', $order), [
            'order_status' => 'dibatalkan',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'order_status' => 'dibatalkan',
        ]);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'status' => 'dibatalkan',
        ]);
    }

    public function test_admin_order_form_selects_the_current_pending_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->createOrder($admin, 'pending', 'menunggu_pembayaran');

        $response = $this->actingAs($admin)->get(route('admin.orders.show', $order));

        $response->assertOk();
        $response->assertSee('value="menunggu_pembayaran" selected', false);
        $response->assertSee('Pembayaran belum diterima.');
    }

    private function createOrder(User $user, string $paymentStatus, string $orderStatus): Order
    {
        return Order::create([
            'user_id' => $user->id,
            'order_code' => 'QDJ-TEST-ORDER',
            'customer_name' => 'Pelanggan Uji',
            'phone' => '08123456789',
            'address' => 'Alamat uji',
            'subtotal' => 15000,
            'shipping_cost' => 10000,
            'total' => 25000,
            'payment_status' => $paymentStatus,
            'order_status' => $orderStatus,
        ]);
    }
}
