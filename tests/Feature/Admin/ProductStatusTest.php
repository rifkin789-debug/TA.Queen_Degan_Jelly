<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_reactivate_a_disabled_product(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::create([
            'name' => 'Degan Jelly Original',
            'slug' => 'degan-jelly-original',
            'price' => 15000,
            'stock' => 10,
            'is_active' => false,
        ]);

        $response = $this->actingAs($admin)
            ->patch(route('admin.produk.status', $product));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Produk berhasil diaktifkan.');
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'is_active' => true,
        ]);
    }

    public function test_product_list_shows_activate_action_for_disabled_products(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Product::create([
            'name' => 'Degan Jelly Original',
            'slug' => 'degan-jelly-original',
            'price' => 15000,
            'stock' => 10,
            'is_active' => false,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.produk.index'));

        $response->assertOk();
        $response->assertSee('Nonaktif');
        $response->assertSee('Aktifkan');
    }

    public function test_customer_cannot_change_product_status(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $product = Product::create([
            'name' => 'Degan Jelly Original',
            'slug' => 'degan-jelly-original',
            'price' => 15000,
            'stock' => 10,
            'is_active' => false,
        ]);

        $response = $this->actingAs($customer)
            ->patch(route('admin.produk.status', $product));

        $response->assertForbidden();
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_can_permanently_delete_a_product(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::create([
            'name' => 'Degan Jelly Uji Coba',
            'slug' => 'degan-jelly-uji-coba',
            'price' => 15000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->delete(route('admin.produk.destroy', $product));

        $response->assertRedirect(route('admin.produk.index'));
        $response->assertSessionHas('success', 'Produk "Degan Jelly Uji Coba" berhasil dihapus secara permanen.');
        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }
}
