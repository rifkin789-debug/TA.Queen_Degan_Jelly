<?php

namespace Tests\Feature\Admin;

use App\Models\Batch;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_batch_index_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::create([
            'name' => 'Degan Jelly Original',
            'slug' => 'degan-jelly-original',
            'price' => 15000,
            'stock' => 10,
            'shelf_life_hours' => 96,
            'is_active' => true,
        ]);

        $batch = Batch::create([
            'product_id' => $product->id,
            'batch_code' => 'BATCH-20261110-001',
            'produced_at' => now()->subHours(10),
            'initial_quantity' => 20,
            'remaining_quantity' => 20,
            'notes' => 'Catatan batch pagi',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.batches.index'));

        $response->assertOk();
        $response->assertSee('Kelola Batch Produksi');
        $response->assertSee('Catat Batch Baru');
        $response->assertSee('BATCH-20261110-001');
        $response->assertSee('Degan Jelly Original');
        $response->assertSee('Sangat Segar');
    }

    public function test_admin_can_store_a_new_production_batch_and_stock_increases(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::create([
            'name' => 'Degan Jelly Melon',
            'slug' => 'degan-jelly-melon',
            'price' => 17000,
            'stock' => 5,
            'shelf_life_hours' => 96,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.batches.store'), [
            'product_id' => $product->id,
            'produced_at' => now()->format('Y-m-d H:i:s'),
            'quantity' => 25,
            'notes' => 'Batch kelapa muda segar',
        ]);

        $response->assertRedirect(route('admin.batches.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('batches', [
            'product_id' => $product->id,
            'initial_quantity' => 25,
            'remaining_quantity' => 25,
        ]);

        // Stok produk harus bertambah dari 5 menjadi 30
        $this->assertSame(30, $product->fresh()->stock);
    }

    public function test_product_availability_considers_batch_freshness(): void
    {
        $product = Product::create([
            'name' => 'Degan Jelly Durian',
            'slug' => 'degan-jelly-durian',
            'price' => 20000,
            'stock' => 10,
            'shelf_life_hours' => 96,
            'is_active' => true,
        ]);

        // Batch diproduksi 90 jam yang lalu -> sisa 6 jam (< 12 jam)
        $batch = Batch::create([
            'product_id' => $product->id,
            'batch_code' => 'BATCH-OLD',
            'produced_at' => now()->subHours(90),
            'initial_quantity' => 10,
            'remaining_quantity' => 10,
        ]);

        $this->assertSame('Kedaluwarsa', $batch->freshnessStatus());
        $this->assertFalse($batch->isAvailableInCatalog());
        $this->assertFalse($product->isAvailable());

        // Buat batch baru yang masih sangat segar
        Batch::create([
            'product_id' => $product->id,
            'batch_code' => 'BATCH-FRESH',
            'produced_at' => now()->subHours(5),
            'initial_quantity' => 15,
            'remaining_quantity' => 15,
        ]);

        $this->assertTrue($product->fresh()->isAvailable());
    }
}
