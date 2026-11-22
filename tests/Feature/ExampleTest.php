<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Queen Degan Jelly');
        $response->assertDontSee('Masuk Admin');
        $response->assertDontSee('/admin/login');
    }

    public function test_admin_dashboard_displays_product_and_order_sidebar_links(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee(route('admin.produk.index'));
        $response->assertSee(route('admin.orders.index'));
        $response->assertSee('Ringkasan Toko');
        $response->assertSee('Lihat Toko');
        $response->assertSee('Panel Admin');
        // Pastikan menu customer tidak muncul di navbar admin
        $response->assertDontSee('title="Keranjang Belanja"', false);
        $response->assertDontSee('title="Kontak Kami"', false);
    }

    public function test_admin_dashboard_redirects_guests_to_login(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_home_page_contains_tentang_kami_section(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('id="tentang"', false);
        $response->assertSee('Dari dapur rumahan di Muncar');
        $response->assertSee('Ibu Triana Wulandari di Muncar, Banyuwangi');
        $response->assertSee('Jelajahi Katalog Produk');
        $response->assertDontSee('[Cerita Singkat Weww]');
    }

    public function test_tentang_kami_url_redirects_to_home_anchor(): void
    {
        $response = $this->get('/tentang-kami');

        $response->assertRedirect('/#tentang');
    }

    public function test_catalog_displays_only_three_category_pills_and_filters_correctly(): void
    {
        $catOriginal = \App\Models\Category::create(['name' => 'Original', 'slug' => 'original']);
        $catRasa = \App\Models\Category::create(['name' => 'Rasa', 'slug' => 'rasa']);
        $catPaket = \App\Models\Category::create(['name' => 'Paket', 'slug' => 'paket']);

        \App\Models\Product::create([
            'category_id' => $catOriginal->id,
            'name' => 'Degan Original Asli',
            'slug' => 'degan-original-asli',
            'price' => 12000,
            'stock' => 10,
            'is_active' => true,
        ]);

        \App\Models\Product::create([
            'category_id' => $catRasa->id,
            'name' => 'Degan Pandan Wangi',
            'slug' => 'degan-pandan-wangi',
            'price' => 15000,
            'stock' => 10,
            'is_active' => true,
        ]);

        \App\Models\Product::create([
            'category_id' => $catPaket->id,
            'name' => 'Paket Hantaran Degan',
            'slug' => 'paket-hantaran-degan',
            'price' => 50000,
            'stock' => 5,
            'is_active' => true,
        ]);

        // Default: Semua produk tampil dan hanya ada 3 tombol filter: Semua, Rasa, Paket
        $responseAll = $this->get(route('products.index'));
        $responseAll->assertOk();
        $responseAll->assertSee('Semua');
        $responseAll->assertSee('Rasa');
        $responseAll->assertSee('Paket');
        // Tidak boleh ada tombol kategori tambahan berulang (seperti category=ID)
        $responseAll->assertDontSee('category=' . $catOriginal->id);
        $responseAll->assertDontSee('category=' . $catRasa->id);
        $responseAll->assertDontSee('category=' . $catPaket->id);

        $responseAll->assertSee('Degan Original Asli');
        $responseAll->assertSee('Degan Pandan Wangi');
        $responseAll->assertSee('Paket Hantaran Degan');

        // Filter: Paket
        $responsePaket = $this->get(route('products.index', ['filter' => 'paket']));
        $responsePaket->assertOk();
        $responsePaket->assertSee('Paket Hantaran Degan');
        $responsePaket->assertDontSee('Degan Original Asli');
        $responsePaket->assertDontSee('Degan Pandan Wangi');

        // Filter: Rasa
        $responseRasa = $this->get(route('products.index', ['filter' => 'rasa']));
        $responseRasa->assertOk();
        $responseRasa->assertSee('Degan Pandan Wangi');
        $responseRasa->assertDontSee('Degan Original Asli');
        $responseRasa->assertDontSee('Paket Hantaran Degan');
    }

    public function test_footer_contains_google_maps_location_embed_and_link(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Lokasi Kami');
        $response->assertSee('images/peta-lokasi.jpg');
        $response->assertSee('https://maps.app.goo.gl/EPuG3ZhMEgdyooC66');
        $response->assertSee('Buka di Google Maps');
        $response->assertSee('target="_blank"', false);
        $response->assertSee('rel="noopener"', false);
    }

    public function test_navbar_cart_badge_counts_unique_product_items_not_total_quantity(): void
    {
        // 1. Keranjang kosong -> badge disembunyikan
        $this->get('/')
            ->assertOk()
            ->assertDontSee('cart-badge');

        // 2. 1 produk dengan quantity 1 -> badge menampilkan 1
        $this->withSession(['cart' => [
            101 => ['quantity' => 1, 'package_batok' => 1, 'fruits' => [], 'batok_buah' => [], 'varian' => 'original'],
        ]])->get('/')
            ->assertOk()
            ->assertSee('cart-badge')
            ->assertSee('>1</span>', false);

        // 3. 1 produk dengan quantity 2 -> badge tetap menampilkan 1
        $this->withSession(['cart' => [
            101 => ['quantity' => 2, 'package_batok' => 1, 'fruits' => [], 'batok_buah' => [], 'varian' => 'original'],
        ]])->get('/')
            ->assertOk()
            ->assertSee('cart-badge')
            ->assertSee('>1</span>', false);

        // 4. 2 produk berbeda (masing-masing berapa pun jumlahnya) -> badge menampilkan 2
        $this->withSession(['cart' => [
            101 => ['quantity' => 3, 'package_batok' => 1, 'fruits' => [], 'batok_buah' => [], 'varian' => 'original'],
            102 => ['quantity' => 2, 'package_batok' => 1, 'fruits' => [], 'batok_buah' => [], 'varian' => 'original'],
        ]])->get('/')
            ->assertOk()
            ->assertSee('cart-badge')
            ->assertSee('>2</span>', false);
    }

    public function test_navbar_cart_badge_updates_on_cart_store_update_and_destroy(): void
    {
        $category = Category::firstOrCreate(['slug' => 'original'], ['name' => 'Original']);
        $product1 = Product::create([
            'category_id' => $category->id,
            'name' => 'Degan Jelly Melon',
            'slug' => 'degan-jelly-melon',
            'price' => 14000,
            'stock' => 10,
            'is_active' => true,
        ]);
        $product2 = Product::create([
            'category_id' => $category->id,
            'name' => 'Degan Jelly Anggur',
            'slug' => 'degan-jelly-anggur',
            'price' => 14000,
            'stock' => 10,
            'is_active' => true,
        ]);

        // A. Tambah Product 1 (qty 1) -> badge 1
        $response = $this->post(route('cart.store', $product1), [
            'quantity' => 1,
            'varian' => 'original',
        ]);
        $response->assertRedirect(route('cart.index'));
        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('cart-badge')
            ->assertSee('>1</span>', false);

        // B. Ubah qty Product 1 menjadi 2 -> badge tetap 1
        $responseUpdate = $this->patch(route('cart.update', $product1), [
            'quantity' => 2,
        ]);
        $responseUpdate->assertRedirect();
        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('cart-badge')
            ->assertSee('>1</span>', false);

        // C. Tambah Product 2 (qty 1) -> badge menjadi 2
        $responseAdd2 = $this->post(route('cart.store', $product2), [
            'quantity' => 1,
            'varian' => 'original',
        ]);
        $responseAdd2->assertRedirect(route('cart.index'));
        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('cart-badge')
            ->assertSee('>2</span>', false);

        // D. Hapus Product 1 -> badge berkurang menjadi 1
        $responseDelete1 = $this->delete(route('cart.destroy', $product1));
        $responseDelete1->assertRedirect();
        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('cart-badge')
            ->assertSee('>1</span>', false);

        // E. Hapus Product 2 -> keranjang kosong -> badge disembunyikan
        $responseDelete2 = $this->delete(route('cart.destroy', $product2));
        $responseDelete2->assertRedirect();
        $this->get(route('cart.index'))
            ->assertOk()
            ->assertDontSee('cart-badge');
    }
}
