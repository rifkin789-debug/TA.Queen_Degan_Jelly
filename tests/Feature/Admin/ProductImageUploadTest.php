<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_product_with_uploaded_image(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.produk.store'), [
            'name' => 'Degan Jelly Mango',
            'description' => 'Rasa mangga.',
            'price' => 18000,
            'stock' => 12,
            'is_active' => true,
            'image' => $this->fakePng('mango.png'),
        ]);

        $response->assertRedirect(route('admin.produk.index'));
        $product = Product::where('name', 'Degan Jelly Mango')->firstOrFail();
        $this->assertModelExists($product);
        Storage::disk('public')->assertExists($product->image);
    }

    public function test_admin_can_replace_product_image_and_old_upload_is_removed(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $oldImage = 'products/old-image.jpg';
        Storage::disk('public')->put($oldImage, 'old image');
        $product = Product::create([
            'name' => 'Degan Jelly Original',
            'slug' => 'degan-jelly-original',
            'price' => 15000,
            'stock' => 10,
            'image' => $oldImage,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.produk.update', $product), [
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'stock' => $product->stock,
            'is_active' => true,
            'image' => $this->fakePng('replacement.png'),
        ]);

        $response->assertRedirect(route('admin.produk.index'));
        $product->refresh();
        $this->assertNotSame($oldImage, $product->image);
        Storage::disk('public')->assertMissing($oldImage);
        Storage::disk('public')->assertExists($product->image);
    }

    public function test_edit_without_new_image_keeps_existing_product_image(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $image = 'products/existing-image.jpg';
        Storage::disk('public')->put($image, 'existing image');
        $product = Product::create([
            'name' => 'Degan Jelly Original',
            'slug' => 'degan-jelly-original',
            'price' => 15000,
            'stock' => 10,
            'image' => $image,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.produk.update', $product), [
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'stock' => $product->stock,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.produk.index'));
        $this->assertSame($image, $product->fresh()->image);
        Storage::disk('public')->assertExists($image);
    }

    public function test_admin_cannot_upload_a_non_image_product_file(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.produk.store'), [
            'name' => 'Produk Tanpa Gambar',
            'price' => 15000,
            'stock' => 10,
            'is_active' => true,
            'image' => UploadedFile::fake()->create('document.pdf', 20, 'application/pdf'),
        ]);

        $response->assertSessionHasErrors('image');
        $this->assertDatabaseMissing('products', ['name' => 'Produk Tanpa Gambar']);
        $this->assertSame([], Storage::disk('public')->allFiles('products'));
    }

    private function fakePng(string $filename): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $filename,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a0y8AAAAASUVORK5CYII=')
        );
    }
}
