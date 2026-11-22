<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category')->latest()->paginate(15);

        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();

        return view('admin.products.form', ['product' => new Product, 'categories' => $categories]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = Str::slug($data['name']).'-'.Str::lower(Str::random(5));

        if ($request->hasFile('image')) {
            $data['image'] = $this->storeImage($request);
        } else {
            unset($data['image']);
        }

        $product = Product::create($data);

        if ($product->is_active && $product->stock > 0) {
            $batchCode = 'BATCH-' . now()->format('Ymd') . '-' . Str::upper(Str::random(5));
            Batch::create([
                'product_id' => $product->id,
                'batch_code' => $batchCode,
                'produced_at' => now(),
                'initial_quantity' => $product->stock,
                'remaining_quantity' => $product->stock,
                'notes' => 'Batch produksi awal saat pembuatan produk',
            ]);
        }

        return redirect()->route('admin.produk.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $produk)
    {
        $categories = Category::orderBy('name')->get();

        return view('admin.products.form', ['product' => $produk, 'categories' => $categories]);
    }

    public function update(Request $request, Product $produk)
    {
        $data = $this->validated($request);
        $data['slug'] = $produk->slug;
        $previousImage = $produk->image;

        if ($request->hasFile('image')) {
            $data['image'] = $this->storeImage($request);
        } else {
            unset($data['image']);
        }

        $produk->update($data);

        // Jika produk aktif dan memiliki stok namun batch yang ada sudah kedaluwarsa, buat batch baru
        if ($produk->is_active && $produk->stock > 0 && ! $produk->isAvailable()) {
            $batchCode = 'BATCH-' . now()->format('Ymd') . '-' . Str::upper(Str::random(5));
            Batch::create([
                'product_id' => $produk->id,
                'batch_code' => $batchCode,
                'produced_at' => now(),
                'initial_quantity' => $produk->stock,
                'remaining_quantity' => $produk->stock,
                'notes' => 'Pembaruan batch saat penyesuaian stok produk',
            ]);
        }

        if (isset($data['image']) && $previousImage && ! filter_var($previousImage, FILTER_VALIDATE_URL)) {
            if (! Storage::disk('public')->delete($previousImage)) {
                throw new RuntimeException('Gambar produk lama gagal dihapus dari penyimpanan.');
            }
        }

        return redirect()->route('admin.produk.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $produk)
    {
        $name = $produk->name;

        // Hapus file gambar jika ada dan tersimpan lokal
        if ($produk->image && ! filter_var($produk->image, FILTER_VALIDATE_URL)) {
            Storage::disk('public')->delete($produk->image);
        }

        // Hapus batch terkait terlebih dahulu jika ada
        $produk->batches()->delete();

        // Hapus produk secara permanen dari database
        $produk->delete();

        return redirect()->route('admin.produk.index')->with('success', "Produk \"{$name}\" berhasil dihapus secara permanen.");
    }

    public function toggleStatus(Product $produk)
    {
        $isActive = ! $produk->is_active;
        $produk->update(['is_active' => $isActive]);

        if ($isActive && $produk->stock > 0 && ! $produk->isAvailable()) {
            $batchCode = 'BATCH-' . now()->format('Ymd') . '-' . Str::upper(Str::random(5));
            Batch::create([
                'product_id' => $produk->id,
                'batch_code' => $batchCode,
                'produced_at' => now(),
                'initial_quantity' => $produk->stock,
                'remaining_quantity' => $produk->stock,
                'notes' => 'Batch baru saat produk diaktifkan',
            ]);
        }

        return back()->with(
            'success',
            $isActive ? 'Produk berhasil diaktifkan.' : 'Produk berhasil dinonaktifkan.'
        );
    }

    public function refreshBatch(Product $produk)
    {
        $batchCode = 'BATCH-' . now()->format('Ymd') . '-' . Str::upper(Str::random(5));
        $quantity = max(1, $produk->stock);

        Batch::create([
            'product_id' => $produk->id,
            'batch_code' => $batchCode,
            'produced_at' => now(),
            'initial_quantity' => $quantity,
            'remaining_quantity' => $quantity,
            'notes' => 'Batch disegarkan dari panel admin produk',
        ]);

        return back()->with('success', "Batch produksi untuk {$produk->name} berhasil disegarkan ({$batchCode}). Produk kini berstatus Tersedia.");
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:3000'],
            'price' => ['required', 'integer', 'min:1'],
            'jumlah_batok' => ['nullable', 'integer', 'min:1', 'max:50'],
            'stock' => ['required', 'integer', 'min:0'],
            'shelf_life_hours' => ['nullable', 'integer', 'min:1'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
        ]) + [
            'is_active' => $request->boolean('is_active'),
            'shelf_life_hours' => $request->integer('shelf_life_hours') ?: 96,
            'jumlah_batok' => max(1, $request->integer('jumlah_batok') ?: 1),
        ];
    }

    private function storeImage(Request $request): string
    {
        $path = $request->file('image')->store('products', 'public');

        if ($path === false) {
            throw new RuntimeException('Gambar produk gagal disimpan.');
        }

        return $path;
    }
}
