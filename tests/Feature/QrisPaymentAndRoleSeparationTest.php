<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QrisPaymentAndRoleSeparationTest extends TestCase
{
    use RefreshDatabase;

    private function fakeJpeg(string $name = 'bukti.jpg'): UploadedFile
    {
        $bytes = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=');

        return UploadedFile::fake()->createWithContent($name, $bytes);
    }

    public function test_root_shows_storefront_home_without_admin_login_link(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Queen Degan Jelly');
        $response->assertDontSee('Masuk Admin');
        $response->assertDontSee('/admin/login');

        $this->get('/admin/login')->assertOk()->assertSee('Masuk Panel Admin');
        $this->get('/masuk')->assertOk()->assertDontSee('/admin/login');
    }

    public function test_beranda_redirects_to_root_home(): void
    {
        $response = $this->get('/beranda');

        $response->assertRedirect('/');
    }

    public function test_customer_cannot_enter_admin_portal_via_admin_login(): void
    {
        $customer = User::factory()->create([
            'email' => 'customer@test.com',
            'password' => bcrypt('Secret123!'),
            'role' => 'customer',
        ]);

        $response = $this->post(route('admin.login.store'), [
            'email' => 'customer@test.com',
            'password' => 'Secret123!',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_can_login_via_admin_portal(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('AdminPass123!'),
            'role' => 'admin',
        ]);

        $response = $this->post(route('admin.login.store'), [
            'email' => 'admin@test.com',
            'password' => 'AdminPass123!',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_customer_accessing_admin_routes_gets_403(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($customer)->get(route('admin.dashboard'));

        $response->assertForbidden();
    }

    public function test_guest_is_redirected_to_login_when_accessing_checkout(): void
    {
        $response = $this->get(route('checkout.create'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'Silakan masuk terlebih dahulu untuk melanjutkan checkout.');
    }

    public function test_order_creation_redirects_to_qris_manual_payment(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $category = Category::create([
            'name' => 'Degan Original',
            'slug' => 'degan-original',
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Degan Jelly Original',
            'slug' => 'degan-jelly-original',
            'price' => 15000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $cart = [
            $product->id => [
                'quantity' => 2,
                'fruits' => [],
            ],
        ];

        $response = $this->actingAs($customer)
            ->withSession(['cart' => $cart])
            ->post(route('checkout.store'), [
                'customer_name' => 'Budi Santoso',
                'phone' => '081234567890',
                'address' => 'Jl. Muncar No. 10, Banyuwangi',
                'notes' => 'Tolong buat agak manis',
            ]);

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertEquals(40000, $order->total); // (15000 * 2) + 10000 ongkir
        $this->assertEquals('menunggu_pembayaran', $order->order_status);

        $payment = $order->payment;
        $this->assertNotNull($payment);
        $this->assertEquals('qris_static', $payment->provider);
        $this->assertEquals('pending', $payment->transaction_status);

        $response->assertRedirect(route('payments.show', $order));
    }

    public function test_customer_can_upload_payment_proof(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::create([
            'user_id' => $customer->id,
            'order_code' => 'QDJ-TEST-001',
            'customer_name' => 'Budi Santoso',
            'phone' => '081234567890',
            'address' => 'Jl. Muncar',
            'subtotal' => 15000,
            'shipping_cost' => 10000,
            'total' => 25000,
            'payment_status' => 'pending',
            'order_status' => 'menunggu_pembayaran',
        ]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'provider' => 'qris_static',
            'gross_amount' => $order->total,
            'transaction_status' => 'pending',
            'expires_at' => now()->addMinutes(60),
        ]);

        $fakeImage = $this->fakeJpeg('bukti_transfer.jpg');

        $response = $this->actingAs($customer)
            ->post(route('payments.proof.store', $order), [
                'payment_proof' => $fakeImage,
                'customer_notes' => 'Transfer via BCA atas nama Budi',
            ]);

        $response->assertSessionHas('success');

        $payment->refresh();
        $this->assertEquals('pending_verification', $payment->transaction_status);
        $this->assertNotNull($payment->proof_path);
        $this->assertNotNull($payment->proof_hash);
        $this->assertEquals('Transfer via BCA atas nama Budi', $payment->customer_notes);

        $order->refresh();
        $this->assertEquals('menunggu_verifikasi', $order->order_status);
    }

    public function test_duplicate_payment_proof_hash_is_rejected(): void
    {
        $customer1 = User::factory()->create(['role' => 'customer']);
        $order1 = Order::create([
            'user_id' => $customer1->id,
            'order_code' => 'QDJ-TEST-001',
            'customer_name' => 'User 1',
            'phone' => '08123456789',
            'subtotal' => 15000,
            'shipping_cost' => 10000,
            'total' => 25000,
            'payment_status' => 'pending_verification',
            'order_status' => 'menunggu_verifikasi',
        ]);
        $payment1 = Payment::create([
            'order_id' => $order1->id,
            'provider' => 'qris_static',
            'gross_amount' => 25000,
            'transaction_status' => 'pending_verification',
            'proof_hash' => 'hash_terdaftar_sebelumnya_123456789',
        ]);

        $customer2 = User::factory()->create(['role' => 'customer']);
        $order2 = Order::create([
            'user_id' => $customer2->id,
            'order_code' => 'QDJ-TEST-002',
            'customer_name' => 'User 2',
            'phone' => '08123456789',
            'subtotal' => 15000,
            'shipping_cost' => 10000,
            'total' => 25000,
            'payment_status' => 'pending',
            'order_status' => 'menunggu_pembayaran',
        ]);
        $payment2 = Payment::create([
            'order_id' => $order2->id,
            'provider' => 'qris_static',
            'gross_amount' => 25000,
            'transaction_status' => 'pending',
            'expires_at' => now()->addMinutes(60),
        ]);

        $fakeFile = $this->fakeJpeg('bukti.jpg');
        $hashOfFile = hash_file('sha256', $fakeFile->getRealPath());
        $payment1->update(['proof_hash' => $hashOfFile]);

        $response = $this->actingAs($customer2)
            ->post(route('payments.proof.store', $order2), [
                'payment_proof' => $fakeFile,
            ]);

        $response->assertSessionHasErrors('payment_proof');
    }

    public function test_admin_can_approve_payment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::create([
            'user_id' => $customer->id,
            'order_code' => 'QDJ-TEST-003',
            'customer_name' => 'User Test',
            'phone' => '08123456789',
            'subtotal' => 15000,
            'shipping_cost' => 10000,
            'total' => 25000,
            'payment_status' => 'pending_verification',
            'order_status' => 'menunggu_verifikasi',
        ]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'provider' => 'qris_static',
            'gross_amount' => $order->total,
            'transaction_status' => 'pending_verification',
        ]);

        $response = $this->actingAs($admin)
            ->patch(route('admin.payments.approve', $payment));

        $response->assertSessionHas('success');

        $payment->refresh();
        $this->assertEquals('paid', $payment->transaction_status);
        $this->assertEquals($admin->id, $payment->verified_by);
        $this->assertNotNull($payment->verified_at);

        $order->refresh();
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals('sedang_disiapkan', $order->order_status);

        // Customer dapat melihat status terbaru (Diproses) di Pesanan Saya & Lacak Pesanan
        $this->actingAs($customer)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Diproses');

        $this->actingAs($customer)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee('Diproses');

        $this->post(route('orders.track.show'), [
            'order_code' => $order->order_code,
            'phone_last4' => '6789',
        ])->assertOk()->assertSee('Diproses');
    }

    public function test_admin_can_reject_payment_with_reason(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::create([
            'user_id' => $customer->id,
            'order_code' => 'QDJ-TEST-004',
            'customer_name' => 'User Test',
            'phone' => '08123456789',
            'subtotal' => 15000,
            'shipping_cost' => 10000,
            'total' => 25000,
            'payment_status' => 'pending_verification',
            'order_status' => 'menunggu_verifikasi',
        ]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'provider' => 'qris_static',
            'gross_amount' => $order->total,
            'transaction_status' => 'pending_verification',
        ]);

        $response = $this->actingAs($admin)
            ->patch(route('admin.payments.reject', $payment), [
                'rejection_reason' => 'Nominal transfer kurang Rp 5.000 dari total tagihan.',
            ]);

        $response->assertSessionHas('success');

        $payment->refresh();
        $this->assertEquals('rejected', $payment->transaction_status);
        $this->assertEquals('Nominal transfer kurang Rp 5.000 dari total tagihan.', $payment->rejection_reason);

        $order->refresh();
        $this->assertEquals('rejected', $order->payment_status);
        $this->assertEquals('dibatalkan', $order->order_status);

        // Customer melihat alasan penolakan di detail pesanan & halaman pembayaran
        $this->actingAs($customer)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Nominal transfer kurang Rp 5.000 dari total tagihan.');
    }

    public function test_admin_can_approve_voucher_payment_via_payment_verification_page(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $package = \App\Models\VoucherPackage::create([
            'nama_paket' => 'Paket Hemat',
            'harga' => 15000,
            'jumlah_batok' => 2,
            'maks_buah' => 3,
            'stok' => 5,
            'aktif' => true,
        ]);

        $voucher = app(\App\Services\VoucherPurchaseService::class)->create($package->id, [
            'nama_pengirim' => 'Reyy',
            'nomor_hp_pengirim' => '081234567890',
            'nama_penerima' => 'Mami',
        ]);

        Storage::disk('local')->put('payment-proofs/voucher-proof.jpg', 'proof');
        $voucher->update(['status' => \App\Models\Voucher::STATUS_WAITING_VERIFICATION]);
        $voucher->payment->update([
            'proof_path' => 'payment-proofs/voucher-proof.jpg',
            'proof_uploaded_at' => now(),
            'transaction_status' => 'pending_verification',
        ]);

        $response = $this->actingAs($admin)
            ->patch(route('admin.payments.approve', $voucher->payment));

        $response->assertSessionHas('success');
        $response->assertSessionHasNoErrors();

        $voucher->refresh();
        $this->assertSame(\App\Models\Voucher::STATUS_ACTIVE, $voucher->status);
        $this->assertNotNull($voucher->kode_voucher);
        $this->assertSame('paid', $voucher->payment->fresh()->transaction_status);
        $this->assertSame($admin->id, $voucher->payment->fresh()->verified_by);
    }
}
