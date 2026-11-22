<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Fruit;
use App\Models\Product;
use Illuminate\Http\Request;

class StorefrontController extends Controller
{
    public function index()
    {
        // Tampilkan 4 produk pilihan terbaik (1 baris rapi), sisanya dapat dilihat melalui tombol "Lihat Semua"
        $products = Product::where('is_active', true)->latest()->take(4)->get();

        return view('storefront.home', compact('products'));
    }

    public function about()
    {
        return redirect('/#tentang');
    }

    public function catalog(Request $request)
    {
        $query = Product::with('category')->where('is_active', true);
        if ($request->filled('q')) {
            $query->where('name', 'like', '%'.$request->string('q').'%');
        }
        if ($request->filled('category')) {
            $query->where('category_id', $request->integer('category'));
        }
        if ($request->filled('filter')) {
            $filter = strtolower($request->string('filter')->trim());
            if (in_array($filter, ['original', 'rasa', 'paket', 'paket-mix'], true)) {
                $query->whereHas('category', function ($q) use ($filter) {
                    if ($filter === 'original') {
                        $q->where('slug', 'original')->orWhere('name', 'like', '%original%');
                    } elseif ($filter === 'rasa') {
                        $q->where('slug', 'rasa')->orWhere('name', 'like', '%rasa%');
                    } elseif (in_array($filter, ['paket', 'paket-mix'], true)) {
                        $q->whereIn('slug', ['paket', 'paket-mix'])->orWhere('name', 'like', '%paket%');
                    }
                });
            }
        }
        $products = $query->latest()->paginate(12)->withQueryString();
        $categories = Category::orderBy('name')->get();

        return view('storefront.catalog', compact('products', 'categories'));
    }

    public function show(Product $product)
    {
        abort_unless($product->is_active, 404);

        $product->load('batches');
        $fruits = Fruit::where('aktif', true)->orderBy('nama_buah')->get();

        // Ambil batch teraktif / teranyar dengan stok
        $currentBatch = $product->batches()
            ->where('remaining_quantity', '>', 0)
            ->latest('produced_at')
            ->first();

        return view('storefront.show', compact('product', 'fruits', 'currentBatch'));
    }
}
