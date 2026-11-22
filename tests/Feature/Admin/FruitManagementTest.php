<?php

namespace Tests\Feature\Admin;

use App\Models\Fruit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FruitManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_disable_a_fruit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.buah.store'), [
                'nama_buah' => 'Nangka',
                'harga_porsi' => 1500,
                'stok_porsi' => 12,
                'aktif' => '1',
            ])
            ->assertRedirect(route('admin.buah.index'));

        $fruit = Fruit::query()->where('nama_buah', 'Nangka')->firstOrFail();
        $this->assertSame(1500, $fruit->harga_porsi);
        $this->assertSame(12, $fruit->stok_porsi);
        $this->assertTrue($fruit->aktif);

        $this->actingAs($admin)
            ->put(route('admin.buah.update', $fruit), [
                'nama_buah' => 'Nangka Madu',
                'harga_porsi' => 1800,
                'stok_porsi' => 8,
            ])
            ->assertRedirect(route('admin.buah.index'));

        $this->assertDatabaseHas('buah', [
            'id' => $fruit->id,
            'nama_buah' => 'Nangka Madu',
            'harga_porsi' => 1800,
            'stok_porsi' => 8,
            'aktif' => false,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.buah.status', $fruit))
            ->assertRedirect();

        $this->assertDatabaseHas('buah', ['id' => $fruit->id, 'aktif' => true]);
    }

    public function test_admin_cannot_save_negative_fruit_stock(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.buah.store'), [
            'nama_buah' => 'Alpukat',
            'harga_porsi' => 1000,
            'stok_porsi' => -1,
        ]);

        $response->assertSessionHasErrors('stok_porsi');
        $this->assertDatabaseMissing('buah', ['nama_buah' => 'Alpukat']);
    }

    public function test_customer_cannot_create_a_fruit(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)
            ->post(route('admin.buah.store'), [
                'nama_buah' => 'Nangka',
                'harga_porsi' => 1500,
                'stok_porsi' => 12,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('buah', ['nama_buah' => 'Nangka']);
    }
}
