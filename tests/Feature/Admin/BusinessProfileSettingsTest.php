<?php

namespace Tests\Feature\Admin;

use App\Models\BusinessProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BusinessProfileSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_fruit_package_and_business_profile_management_pages(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.buah.index'))
            ->assertOk()
            ->assertSee('Kelola Buah');
        $this->actingAs($admin)
            ->get(route('admin.buah.create'))
            ->assertOk()
            ->assertSee('Tambah Buah');
        $this->actingAs($admin)
            ->get(route('admin.paket-voucher.index'))
            ->assertOk()
            ->assertSee('Kelola Paket Voucher');
        $this->actingAs($admin)
            ->get(route('admin.paket-voucher.create'))
            ->assertOk()
            ->assertSee('Tambah Paket Voucher');
        $this->actingAs($admin)
            ->get(route('admin.business-profile.edit'))
            ->assertOk()
            ->assertSee('Pengaturan Usaha');
    }

    public function test_admin_can_update_custom_voucher_and_qris_settings(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->put(route('admin.business-profile.update'), [
            'harga_dasar_custom' => 2500,
            'maks_buah_custom' => 5,
            'masa_berlaku_voucher_hari' => 45,
            'qris_image' => UploadedFile::fake()->createWithContent(
                'qris.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/r7cAAAAASUVORK5CYII='),
            ),
        ]);

        $response->assertRedirect(route('admin.business-profile.edit'));
        $profile = BusinessProfile::query()->findOrFail(1);
        $this->assertSame(2500, $profile->harga_dasar_custom);
        $this->assertSame(5, $profile->maks_buah_custom);
        $this->assertSame(45, $profile->masa_berlaku_voucher_hari);
        Storage::disk('public')->assertExists($profile->qris_image_path);
    }

    public function test_admin_cannot_set_negative_custom_price_or_zero_voucher_expiration(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->put(route('admin.business-profile.update'), [
            'harga_dasar_custom' => -1,
            'maks_buah_custom' => 0,
            'masa_berlaku_voucher_hari' => 0,
        ]);

        $response->assertSessionHasErrors([
            'harga_dasar_custom',
            'maks_buah_custom',
            'masa_berlaku_voucher_hari',
        ]);
        $this->assertDatabaseHas('profil_usaha', [
            'id' => 1,
            'harga_dasar_custom' => 0,
            'maks_buah_custom' => 4,
            'masa_berlaku_voucher_hari' => 30,
        ]);
    }

    public function test_customer_cannot_change_business_profile_settings(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)
            ->put(route('admin.business-profile.update'), [
                'harga_dasar_custom' => 2500,
                'maks_buah_custom' => 5,
                'masa_berlaku_voucher_hari' => 45,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('profil_usaha', ['id' => 1, 'harga_dasar_custom' => 0]);
    }

    public function test_admin_can_update_store_info_and_footer_reflects_changes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->put(route('admin.business-profile.update'), [
            'nama_toko' => 'Degan Jelly Super Mantap',
            'alamat' => 'Jl. Pelabuhan Muncar No. 99, Banyuwangi',
            'nomor_hp' => '089912345678',
            'jam_operasional' => 'Senin - Sabtu: 09:00 - 21:00 WIB',
            'harga_dasar_custom' => 0,
            'maks_buah_custom' => 4,
            'masa_berlaku_voucher_hari' => 30,
        ]);

        $response->assertRedirect(route('admin.business-profile.edit'));
        $profile = BusinessProfile::query()->findOrFail(1);
        $this->assertSame('Degan Jelly Super Mantap', $profile->nama_toko);
        $this->assertSame('Jl. Pelabuhan Muncar No. 99, Banyuwangi', $profile->alamat);
        $this->assertSame('089912345678', $profile->nomor_hp);
        $this->assertSame('Senin - Sabtu: 09:00 - 21:00 WIB', $profile->jam_operasional);

        // Verify customer view renders the updated profile in footer
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Degan Jelly Super Mantap')
            ->assertSee('Jl. Pelabuhan Muncar No. 99, Banyuwangi')
            ->assertSee('089912345678')
            ->assertSee('Senin - Sabtu: 09:00 - 21:00 WIB');
    }

    public function test_admin_can_update_shipping_cost_setting(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->put(route('admin.business-profile.update'), [
            'biaya_ongkir' => 15000,
            'harga_dasar_custom' => 0,
            'maks_buah_custom' => 4,
            'masa_berlaku_voucher_hari' => 30,
        ]);

        $response->assertRedirect(route('admin.business-profile.edit'));
        $profile = BusinessProfile::query()->findOrFail(1);
        $this->assertSame(15000, $profile->biaya_ongkir);
    }
}
