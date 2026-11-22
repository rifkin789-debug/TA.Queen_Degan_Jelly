<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BatchController extends Controller
{
    public function index()
    {
        $batches = Batch::with('product')
            ->orderByDesc('produced_at')
            ->paginate(15);

        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('admin.batches.index', compact('batches', 'products'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'produced_at' => ['required', 'date'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $product = Product::findOrFail($data['product_id']);

        $countToday = Batch::whereDate('created_at', today())->count() + 1;
        $batchCode = 'BATCH-' . now()->format('Ymd') . '-' . str_pad((string) $countToday, 3, '0', STR_PAD_LEFT);

        Batch::create([
            'product_id' => $product->id,
            'batch_code' => $batchCode,
            'produced_at' => $data['produced_at'],
            'initial_quantity' => $data['quantity'],
            'remaining_quantity' => $data['quantity'],
            'notes' => $data['notes'] ?? null,
        ]);

        // Tambahkan stok ke produk
        $product->increment('stock', $data['quantity']);

        return redirect()->route('admin.batches.index')->with('success', "Batch {$batchCode} berhasil dicatat dan stok {$product->name} bertambah {$data['quantity']}.");
    }
}
