<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Fruit;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryMixAndRasaRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_shows_original_rasa_and_paket_mix_filters(): void
    {
        $catOriginal = Category::create(['name' => 'Original & Mix', 'slug' => 'original']);
        $catRasa = Category::create(['name' => 'Rasa', 'slug' => 'rasa']);
        $catPaket = Category::create(['name' => 'Paket', 'slug' => 'paket']);

        Product::create([
            'category_id' => $catOriginal->id,
            'name' => 'Degan Original Enak',
            'slug' => 'degan-original-enak',
            'price' => 12000,
            'stock' => 10,
            'is_active' => true,
        ]);

        Product::create([
            'category_id' => $catRasa->id,
            'name' => 'Degan Rasa Pandan',
            'slug' => 'degan-rasa-pandan',
            'price' => 14000,
            'stock' => 10,
            'is_active' => true,
        ]);

        Product::create([
            'category_id' => $catPaket->id,
            'name' => 'Paket Degan Berbagi',
            'slug' => 'paket-degan-berbagi',
            'price' => 30000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $this->get(route('products.index', ['filter' => 'original']))
            ->assertOk()
            ->assertSee('Degan Original Enak')
            ->assertDontSee('Degan Rasa Pandan');

        $this->get(route('products.index', ['filter' => 'rasa']))
            ->assertOk()
            ->assertSee('Degan Rasa Pandan')
            ->assertDontSee('Degan Original Enak');

        $this->get(route('products.index', ['filter' => 'paket']))
            ->assertOk()
            ->assertSee('Paket Degan Berbagi')
            ->assertDontSee('Degan Rasa Pandan');
    }

    public function test_rasa_category_product_detail_and_cart_does_not_accept_fruits(): void
    {
        $catRasa = Category::create(['name' => 'Rasa', 'slug' => 'rasa']);
        $fruit = Fruit::create(['nama_buah' => 'Anggur', 'harga_porsi' => 3000, 'stok_porsi' => 10, 'aktif' => true]);

        $product = Product::create([
            'category_id' => $catRasa->id,
            'name' => 'Degan Jelly Pandan',
            'slug' => 'degan-jelly-pandan',
            'price' => 12000,
            'stock' => 10,
            'is_active' => true,
        ]);

        // Halaman detail rasa tidak menampilkan card pilih isian buah
        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Varian Rasa Khas')
            ->assertDontSee('Pilih Isian Buah');

        // Cart store untuk rasa mengabaikan buah
        $this->post(route('cart.store', $product), [
            'quantity' => 2,
            'buah' => [$fruit->id],
        ])->assertRedirect(route('cart.index'));

        $cart = session('cart');
        $this->assertEmpty($cart[$product->id]['fruits']);
    }

    public function test_paket_mix_category_allows_customizing_each_batok_separately(): void
    {
        $catPaket = Category::create(['name' => 'Paket', 'slug' => 'paket']);
        $fruit1 = Fruit::create(['nama_buah' => 'Anggur', 'harga_porsi' => 3000, 'stok_porsi' => 10, 'aktif' => true]);
        $fruit2 = Fruit::create(['nama_buah' => 'Melon', 'harga_porsi' => 2500, 'stok_porsi' => 10, 'aktif' => true]);

        $product = Product::create([
            'category_id' => $catPaket->id,
            'name' => 'Paket Degan 3 Batok',
            'slug' => 'paket-degan-3-batok',
            'price' => 20000,
            'jumlah_batok' => 3,
            'stock' => 10,
            'is_active' => true,
        ]);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Kustomisasi Mix Tiap Batok')
            ->assertSee('Batok Kelapa 1')
            ->assertSee('Batok Kelapa 2')
            ->assertSee('Batok Kelapa 3');

        // Customer beli 1 paket (berisi 3 batok) dengan mix berbeda
        // Batok 1: Anggur (+3000)
        // Batok 2: Melon (+2500)
        // Batok 3: Tanpa buah (+0)
        $this->post(route('cart.store', $product), [
            'quantity' => 1,
            'batok_buah' => [
                1 => [$fruit1->id],
                2 => [$fruit2->id],
                3 => [],
            ],
        ])->assertRedirect(route('cart.index'));

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Mix Tiap Batok')
            ->assertSee('Batok 1:')
            ->assertSee('Anggur')
            ->assertSee('Batok 2:')
            ->assertSee('Melon')
            ->assertSee('Batok 3:')
            ->assertSee('Original Murni')
            ->assertSee('3 batok');

        // Checkout order
        $customer = User::factory()->create(['role' => 'customer']);
        $response = $this->actingAs($customer)->post(route('checkout.store'), [
            'customer_name' => 'Ardiansyah',
            'phone' => '081234567890',
            'address' => 'Jl. Muncar No. 88, Banyuwangi',
        ]);

        $response->assertRedirect();
        $order = Order::latest()->first();
        $this->assertNotNull($order);
        // Total = 20000 (1 paket) + 3000 + 2500 + 10000 (shipping) = 35500
        $this->assertEquals(35500, $order->total);
        $this->assertEquals(3, $order->jumlah_batok);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'quantity' => 1,
        ]);
        $this->assertDatabaseHas('pesanan_buah', [
            'id_pesanan' => $order->id,
            'nama_buah' => 'Anggur',
            'jumlah_porsi' => 1,
        ]);
        $this->assertDatabaseHas('pesanan_buah', [
            'id_pesanan' => $order->id,
            'nama_buah' => 'Melon',
            'jumlah_porsi' => 1,
        ]);
    }

    public function test_admin_can_set_jumlah_batok_when_creating_and_updating_package_product(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $catPaket = Category::create(['name' => 'Paket', 'slug' => 'paket']);

        // Admin create produk paket dengan isi 4 batok
        $this->actingAs($admin)->post(route('admin.produk.store'), [
            'name' => 'Paket Family 4 Degan',
            'category_id' => $catPaket->id,
            'price' => 35000,
            'jumlah_batok' => 4,
            'stock' => 15,
            'is_active' => 1,
            'shelf_life_hours' => 48,
        ])->assertRedirect(route('admin.produk.index'));

        $product = Product::where('name', 'Paket Family 4 Degan')->first();
        $this->assertNotNull($product);
        $this->assertEquals(4, $product->jumlah_batok);

        // Storefront detail produk langsung menampilkan 4 batok kustomisasi
        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Batok Kelapa 1')
            ->assertSee('Batok Kelapa 2')
            ->assertSee('Batok Kelapa 3')
            ->assertSee('Batok Kelapa 4');

        // Admin update isi paket menjadi 5 batok
        $this->actingAs($admin)->put(route('admin.produk.update', $product), [
            'name' => 'Paket Family 5 Degan',
            'category_id' => $catPaket->id,
            'price' => 42000,
            'jumlah_batok' => 5,
            'stock' => 15,
            'is_active' => 1,
            'shelf_life_hours' => 48,
        ])->assertRedirect(route('admin.produk.index'));

        $product->refresh();
        $this->assertEquals(5, $product->jumlah_batok);

        // Storefront detail kini menampilkan 5 batok
        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Batok Kelapa 5');
    }

    public function test_original_and_mix_product_page_and_cart_rules(): void
    {
        $catOriginal = Category::create(['name' => 'Original & Mix', 'slug' => 'original']);
        $fruit1 = Fruit::create(['nama_buah' => 'Anggur', 'harga_porsi' => 3000, 'stok_porsi' => 10, 'aktif' => true]);
        $fruit2 = Fruit::create(['nama_buah' => 'Melon', 'harga_porsi' => 2500, 'stok_porsi' => 10, 'aktif' => true]);
        $fruit3 = Fruit::create(['nama_buah' => 'Nangka', 'harga_porsi' => 3500, 'stok_porsi' => 10, 'aktif' => true]);

        $product = Product::create([
            'category_id' => $catOriginal->id,
            'name' => 'Degan Jelly Original',
            'slug' => 'degan-jelly-original',
            'price' => 15000,
            'stock' => 10,
            'is_active' => true,
        ]);

        // 1. Tampilan default (Original): Terdapat selector varian dan kartu isian tersembunyi
        $response = $this->get(route('products.show', $product));
        $response->assertOk()
            ->assertSee('Degan Jelly Original')
            ->assertSee('btnVariantOriginal')
            ->assertSee('btnVariantMix')
            ->assertSee('d-none" id="fruitCustomizationCard"', false);

        // 2. Tampilan dengan URL parameter ?varian=mix
        $responseMix = $this->get(route('products.show', ['product' => $product, 'varian' => 'mix']));
        $responseMix->assertOk()
            ->assertSee('Degan Jelly Mix')
            ->assertSee('Mix Buah');

        // 3. Tambah ke keranjang varian Original: abaikan buah jika ada yang dikirim
        $this->post(route('cart.store', $product), [
            'quantity' => 1,
            'varian' => 'original',
            'buah' => [$fruit1->id],
        ])->assertRedirect(route('cart.index'));

        $cart = session('cart');
        $this->assertEquals('original', $cart[$product->id]['varian']);
        $this->assertEmpty($cart[$product->id]['fruits']);

        // 4. Tambah ke keranjang varian Mix tanpa buah (0 buah): ditolak dengan pesan error
        $this->from(route('products.show', ['product' => $product, 'varian' => 'mix']))
            ->post(route('cart.store', $product), [
                'quantity' => 1,
                'varian' => 'mix',
                'buah' => [], // 0 buah
            ])
            ->assertSessionHasErrors('buah');

        // 5. Tambah ke keranjang varian Mix dengan 1 buah (kini diperbolehkan 1 - 3 buah): berhasil
        $this->post(route('cart.store', $product), [
            'quantity' => 1,
            'varian' => 'mix',
            'buah' => [$fruit1->id],
        ])->assertRedirect(route('cart.index'));

        $cart = session('cart');
        $this->assertEquals('mix', $cart[$product->id]['varian']);
        $this->assertCount(1, $cart[$product->id]['fruits']);

        // 6. Tambah ke keranjang varian Mix dengan 3 buah: berhasil
        $this->post(route('cart.store', $product), [
            'quantity' => 1,
            'varian' => 'mix',
            'buah' => [$fruit1->id, $fruit2->id, $fruit3->id],
        ])->assertRedirect(route('cart.index'));

        $cart = session('cart');
        $this->assertEquals('mix', $cart[$product->id]['varian']);
        $this->assertCount(3, $cart[$product->id]['fruits']);
    }
}
