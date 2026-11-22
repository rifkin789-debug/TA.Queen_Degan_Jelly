<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $adminEmail = env('ADMIN_EMAIL', 'admin@deganjelly');
        $adminPassword = env('ADMIN_PASSWORD', 'Deganjelly123');

        // Simpan / update akun admin utama dari .env dengan hash password
        User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'Admin Queen Degan Jelly',
                'password' => \Illuminate\Support\Facades\Hash::make($adminPassword),
                'role' => 'admin',
            ]
        );

        // Pastikan akun admin yang pernah dibuat diperbarui ke password aman yang baru
        User::where('role', 'admin')->update([
            'password' => \Illuminate\Support\Facades\Hash::make($adminPassword),
        ]);

        $catOriginal = Category::updateOrCreate(
            ['slug' => 'original'],
            ['name' => 'Original & Mix', 'description' => 'Varian degan jelly original murni kelapa muda dan custom mix isian buah.']
        );
        $catRasa = Category::updateOrCreate(
            ['slug' => 'rasa'],
            ['name' => 'Rasa', 'description' => 'Varian degan jelly beraroma dan perisa alami.']
        );
        $catPaket = Category::updateOrCreate(
            ['slug' => 'paket'],
            ['name' => 'Paket', 'description' => 'Paket hemat degan jelly istimewa.']
        );

        $categoryMap = [
            'Original' => $catOriginal->id,
            'Original & Mix' => $catOriginal->id,
            'Rasa' => $catRasa->id,
            'Paket' => $catPaket->id,
            'Paket & Mix' => $catPaket->id,
        ];

        $defaultProducts = [
            [
                'name' => 'Degan Jelly 1',
                'category' => 'Original',
                'description' => 'Kelapa muda pilihan dengan isian jelly murni yang lembut, manis alami air kelapa asli Muncar tanpa bahan pengawet.',
                'price' => 12000,
                'image' => '/images/uploads/produk-1794765190-6af9f1868b77a.jpg',
                'shelf_life_hours' => 48,
                'stock' => 30,
                'is_active' => true,
            ],
            [
                'name' => 'Degan Jelly 2',
                'category' => 'Rasa',
                'description' => 'Perpaduan segarnya degan jelly dengan aroma pandan wangi khas tradisional yang menenangkan dan menyegarkan.',
                'price' => 12000,
                'image' => '/images/uploads/produk-1794765218-6af9f1a273cc5.jpg',
                'shelf_life_hours' => 48,
                'stock' => 30,
                'is_active' => true,
            ],
            [
                'name' => 'Degan Jelly 3',
                'category' => 'Paket',
                'description' => 'Paket hemat berbagi kebahagiaan berisi kombinasi degan jelly segar pilihan, pas untuk acara kumpul keluarga.',
                'price' => 20000,
                'jumlah_batok' => 3,
                'image' => '/images/uploads/produk-1794765250-6af9f1c276c5b.jpeg',
                'shelf_life_hours' => 48,
                'stock' => 30,
                'is_active' => true,
            ],
            [
                'name' => 'Degan Jelly 4',
                'category' => 'Paket',
                'description' => 'Paket spesial hantaran Queen Degan Jelly lengkap dengan pilihan buah segar premium untuk momen istimewa.',
                'price' => 25000,
                'jumlah_batok' => 4,
                'image' => '/images/uploads/produk-1794766077-6af9f4fd7f0bf.jpg',
                'shelf_life_hours' => 48,
                'stock' => 0,
                'is_active' => false,
            ],
        ];

        foreach ($defaultProducts as $dp) {
            $slug = \Illuminate\Support\Str::slug($dp['name']);
            $catId = $categoryMap[$dp['category']] ?? $catOriginal->id;

            $prod = Product::firstOrCreate(
                ['slug' => $slug],
                [
                    'category_id' => $catId,
                    'name' => $dp['name'],
                    'description' => $dp['description'],
                    'price' => $dp['price'],
                    'jumlah_batok' => $dp['jumlah_batok'] ?? 1,
                    'stock' => $dp['stock'],
                    'shelf_life_hours' => $dp['shelf_life_hours'],
                    'image' => $dp['image'],
                    'is_active' => $dp['is_active'],
                ]
            );

            if ($prod->stock > 0 && ! \App\Models\Batch::where('product_id', $prod->id)->exists()) {
                \App\Models\Batch::create([
                    'product_id' => $prod->id,
                    'batch_code' => 'BATCH-' . strtoupper(\Illuminate\Support\Str::random(6)),
                    'produced_at' => now()->subHours(6),
                    'initial_quantity' => $prod->stock,
                    'remaining_quantity' => $prod->stock,
                    'notes' => 'Batch produksi awal',
                ]);
            }
        }

        $defaultFruits = [
            ['nama_buah' => 'Anggur', 'harga_porsi' => 3000, 'stok_porsi' => 50, 'aktif' => true],
            ['nama_buah' => 'Leci', 'harga_porsi' => 3000, 'stok_porsi' => 50, 'aktif' => true],
            ['nama_buah' => 'Mangga', 'harga_porsi' => 3000, 'stok_porsi' => 50, 'aktif' => true],
            ['nama_buah' => 'Melon', 'harga_porsi' => 2500, 'stok_porsi' => 50, 'aktif' => true],
            ['nama_buah' => 'Stroberi', 'harga_porsi' => 3500, 'stok_porsi' => 0, 'aktif' => false],
        ];

        foreach ($defaultFruits as $df) {
            \App\Models\Fruit::updateOrCreate(
                ['nama_buah' => $df['nama_buah']],
                [
                    'harga_porsi' => $df['harga_porsi'],
                    'stok_porsi' => $df['stok_porsi'],
                    'aktif' => $df['aktif'],
                ]
            );
        }
    }
}
