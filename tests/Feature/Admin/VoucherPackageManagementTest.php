<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\VoucherPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoucherPackageManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_disable_a_voucher_package(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.paket-voucher.store'), [
                'nama_paket' => 'Paket Ceria',
                'harga' => 45000,
                'jumlah_batok' => 2,
                'maks_buah' => 3,
                'aktif' => '1',
            ])
            ->assertRedirect(route('admin.paket-voucher.index'));

        $package = VoucherPackage::query()->where('nama_paket', 'Paket Ceria')->firstOrFail();
        $this->assertSame(45000, $package->harga);
        $this->assertTrue($package->aktif);

        $this->actingAs($admin)
            ->put(route('admin.paket-voucher.update', $package), [
                'nama_paket' => 'Paket Ceria Plus',
                'harga' => 55000,
                'jumlah_batok' => 3,
                'maks_buah' => 4,
            ])
            ->assertRedirect(route('admin.paket-voucher.index'));

        $this->assertDatabaseHas('paket_voucher', [
            'id' => $package->id,
            'nama_paket' => 'Paket Ceria Plus',
            'harga' => 55000,
            'jumlah_batok' => 3,
            'maks_buah' => 4,
            'aktif' => false,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.paket-voucher.status', $package))
            ->assertRedirect();

        $this->assertDatabaseHas('paket_voucher', ['id' => $package->id, 'aktif' => true]);
    }

    public function test_admin_cannot_create_a_voucher_package_without_valid_positive_values(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.paket-voucher.store'), [
            'nama_paket' => 'Paket Tidak Valid',
            'harga' => 0,
            'jumlah_batok' => 0,
            'maks_buah' => 0,
        ]);

        $response->assertSessionHasErrors(['harga', 'jumlah_batok', 'maks_buah']);
        $this->assertDatabaseMissing('paket_voucher', ['nama_paket' => 'Paket Tidak Valid']);
    }

    public function test_admin_can_adjust_voucher_package_stock_up_and_down(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $package = VoucherPackage::query()->create([
            'nama_paket' => 'Paket Stok Uji',
            'harga' => 30000,
            'jumlah_batok' => 2,
            'maks_buah' => 3,
            'stok' => 5,
            'aktif' => true,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.paket-voucher.stock', $package), ['delta' => 5])
            ->assertRedirect();
        $this->assertSame(10, $package->fresh()->stok);

        $this->actingAs($admin)
            ->patch(route('admin.paket-voucher.stock', $package), ['delta' => -3])
            ->assertRedirect();
        $this->assertSame(7, $package->fresh()->stok);

        $this->actingAs($admin)
            ->patch(route('admin.paket-voucher.stock', $package), ['stok' => 0])
            ->assertRedirect();
        $this->assertSame(0, $package->fresh()->stok);

        // Tidak boleh kurang dari 0
        $this->actingAs($admin)
            ->patch(route('admin.paket-voucher.stock', $package), ['delta' => -5])
            ->assertRedirect();
        $this->assertSame(0, $package->fresh()->stok);
    }
}
