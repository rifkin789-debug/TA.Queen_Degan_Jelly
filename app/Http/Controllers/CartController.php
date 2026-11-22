<?php

namespace App\Http\Controllers;

use App\Models\Fruit;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()
    {
        $cart = session('cart', []);
        $ids = array_keys($cart);
        $products = Product::with('category')->whereIn('id', $ids)->get()->keyBy('id');
        $fruitCatalog = Fruit::all()->keyBy('id');
        $items = [];
        $total = 0;

        foreach ($cart as $id => $row) {
            if (! isset($products[$id])) {
                continue;
            }
            $product = $products[$id];
            $qty = min((int) $row['quantity'], $product->stock);

            $batokFruitsData = $row['batok_buah'] ?? [];
            $batokDetails = [];
            $itemFruitAddons = 0;
            $fruitModels = collect();
            $packageBatok = (int) ($row['package_batok'] ?? $product->packageBatokCount());

            if (! empty($batokFruitsData)) {
                $onePackageAddon = 0;
                for ($i = 1; $i <= $packageBatok; $i++) {
                    $fIds = $batokFruitsData[$i] ?? [];
                    $bFruits = collect();
                    foreach ($fIds as $fId) {
                        if (isset($fruitCatalog[$fId])) {
                            $bFruits->push($fruitCatalog[$fId]);
                            $onePackageAddon += $fruitCatalog[$fId]->harga_porsi;
                        }
                    }
                    $batokDetails[$i] = $bFruits;
                }
                $itemFruitAddons = $onePackageAddon * $qty;
            } else {
                $fruitIds = $row['fruits'] ?? [];
                $fruitModels = ! empty($fruitIds) ? $fruitCatalog->whereIn('id', $fruitIds)->values() : collect();
                $itemFruitAddons = $fruitModels->sum('harga_porsi') * $qty;
            }

            $subtotal = ($product->price * $qty) + $itemFruitAddons;

            $items[] = [
                'product' => $product,
                'quantity' => $qty,
                'subtotal' => $subtotal,
                'fruits' => $fruitModels,
                'batok_details' => $batokDetails,
            ];
            $total += $subtotal;
        }

        return view('cart.index', compact('items', 'total'));
    }

    public function store(Request $request, Product $product)
    {
        abort_unless($product->is_active && $product->stock > 0, 404);

        $catSlug = $product->category?->slug ?? '';
        $catName = strtolower($product->category?->name ?? '');
        $isRasa = ($catSlug === 'rasa' || str_contains($catName, 'rasa'));
        $isPaket = (in_array($catSlug, ['paket', 'paket-mix'], true) || str_contains($catName, 'paket'));
        $isOriginal = ! $isRasa && ! $isPaket;

        $data = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1', 'max:20'],
            'varian' => ['nullable', 'string', 'in:original,mix'],
            'buah' => ['nullable', 'array', 'max:3'],
            'buah.*' => ['integer', 'exists:buah,id'],
            'batok_buah' => ['nullable', 'array'],
            'batok_buah.*' => ['nullable', 'array', 'max:3'],
            'batok_buah.*.*' => ['integer', 'exists:buah,id'],
        ]);

        $qty = (int) ($data['quantity'] ?? 1);
        $cart = session('cart', []);
        $current = (int) ($cart[$product->id]['quantity'] ?? 0);

        abort_if($qty > $product->stock, 422, 'Jumlah melebihi stok.');

        $variant = strtolower($data['varian'] ?? 'original');

        if ($isRasa) {
            $selectedFruits = [];
            $batokFruits = [];
        } elseif ($isPaket && ! empty($data['batok_buah'])) {
            $packageBatok = $product->packageBatokCount();
            $selectedFruits = [];
            $batokFruits = [];
            for ($b = 1; $b <= $packageBatok; $b++) {
                $batokFruits[$b] = $data['batok_buah'][$b] ?? [];
            }
        } else {
            // Aturan Varian Original & Mix:
            // Jika varian = Original -> abaikan buah (tanpa isian)
            // Jika varian = Mix -> minimal 1 buah dan maksimal 3 buah
            if ($variant === 'original') {
                $selectedFruits = [];
            } else {
                $selectedFruits = $data['buah'] ?? [];
                if ($isOriginal && (count($selectedFruits) < 1 || count($selectedFruits) > 3)) {
                    return back()->withErrors(['buah' => 'Untuk varian Mix, silakan pilih minimal 1 dan maksimal 3 jenis buah.']);
                }
            }
            $batokFruits = [];
        }

        $cart[$product->id] = [
            'quantity' => $qty,
            'package_batok' => $isPaket ? $product->packageBatokCount() : 1,
            'fruits' => $selectedFruits,
            'batok_buah' => $batokFruits,
            'varian' => $isOriginal ? $variant : null,
        ];

        session(['cart' => $cart]);

        return redirect()->route('cart.index')->with('success', 'Produk ditambahkan ke keranjang.');
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:20']]);
        abort_if($data['quantity'] > $product->stock, 422, 'Jumlah melebihi stok.');

        $cart = session('cart', []);
        if (isset($cart[$product->id])) {
            $cart[$product->id]['quantity'] = $data['quantity'];
            session(['cart' => $cart]);
        }

        return back()->with('success', 'Keranjang diperbarui.');
    }

    public function destroy(Product $product)
    {
        $cart = session('cart', []);
        unset($cart[$product->id]);
        session(['cart' => $cart]);

        return back()->with('success', 'Produk dihapus dari keranjang.');
    }
}
