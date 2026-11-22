<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Midtrans\Config as MidtransConfig;
use Midtrans\Snap;

class CheckoutController extends Controller
{
    public function create()
    {
        if (empty(session('cart', []))) {
            return redirect()->route('cart.index')->with('error', 'Keranjang masih kosong.');
        }

        $profile = \App\Models\BusinessProfile::query()->find(1);
        $shippingCost = $profile?->biaya_ongkir ?? (int) config('shop.shipping_cost', 10000);

        return view('checkout.create', compact('shippingCost'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $cart = session('cart', []);
        abort_if(empty($cart), 422, 'Keranjang kosong.');

        $order = DB::transaction(function () use ($cart, $data, $request) {
            $fruitCatalog = \App\Models\Fruit::all()->keyBy('id');
            $subtotal = 0;
            $lines = [];
            $totalBatok = 0;
            $orderFruitsUsed = [];

            foreach ($cart as $productId => $row) {
                $product = Product::whereKey($productId)->lockForUpdate()->firstOrFail();
                $quantity = (int) $row['quantity'];
                abort_if(! $product->is_active || $quantity < 1 || $quantity > $product->stock, 422, 'Stok produk tidak mencukupi.');

                $packageBatok = (int) ($row['package_batok'] ?? $product->packageBatokCount());
                $batokFruitsData = $row['batok_buah'] ?? [];
                $batokDetails = [];
                $lineAddons = 0;
                $fruits = collect();

                if (! empty($batokFruitsData)) {
                    $onePackageAddon = 0;
                    for ($i = 1; $i <= $packageBatok; $i++) {
                        $fIds = $batokFruitsData[$i] ?? [];
                        $bFruits = collect();
                        foreach ($fIds as $fId) {
                            if (isset($fruitCatalog[$fId])) {
                                $bFruits->push($fruitCatalog[$fId]);
                                $onePackageAddon += $fruitCatalog[$fId]->harga_porsi;
                                $orderFruitsUsed[$fId] = ($orderFruitsUsed[$fId] ?? 0) + $quantity;
                            }
                        }
                        $batokDetails[$i] = $bFruits;
                    }
                    $lineAddons = $onePackageAddon * $quantity;
                } else {
                    $fruitIds = $row['fruits'] ?? [];
                    $fruits = ! empty($fruitIds) ? $fruitCatalog->whereIn('id', $fruitIds)->values() : collect();
                    $lineAddons = $fruits->sum('harga_porsi') * $quantity;
                    foreach ($fruits as $f) {
                        $orderFruitsUsed[$f->id] = ($orderFruitsUsed[$f->id] ?? 0) + $quantity;
                    }
                }

                $lineTotal = ($product->price * $quantity) + $lineAddons;
                $unitPrice = (int) round($lineTotal / $quantity);
                $subtotal += $lineTotal;
                $totalBatok += ($packageBatok * $quantity);

                $lines[] = [$product, $quantity, $unitPrice, $lineTotal, $fruits, $batokDetails, $packageBatok];
            }

            $profile = \App\Models\BusinessProfile::query()->find(1);
            $shipping = $profile?->biaya_ongkir ?? (int) config('shop.shipping_cost', 10000);
            $order = Order::create([
                'user_id' => $request->user()->id,
                'order_code' => 'QDJ-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'customer_name' => $data['customer_name'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'notes' => $data['notes'] ?? null,
                'subtotal' => $subtotal,
                'shipping_cost' => $shipping,
                'total' => $subtotal + $shipping,
                'jumlah_batok' => $totalBatok,
                'payment_status' => 'pending',
                'order_status' => 'menunggu_pembayaran',
            ]);

            foreach ($lines as [$product, $quantity, $unitPrice, $lineTotal, $fruits, $batokDetails, $packageBatok]) {
                if (! empty($batokDetails)) {
                    $mixDesc = [];
                    foreach ($batokDetails as $bNum => $bFruits) {
                        $fNames = $bFruits->isNotEmpty() ? $bFruits->pluck('nama_buah')->implode(', ') : 'Original';
                        $mixDesc[] = "Batok {$bNum}: {$fNames}";
                    }
                    $itemName = $product->name . ' (' . implode(' | ', $mixDesc) . ')';
                } else {
                    $itemName = $product->name . ($fruits->isNotEmpty() ? ' (Isian: ' . $fruits->pluck('nama_buah')->implode(', ') . ')' : '');
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $itemName,
                    'price' => $unitPrice,
                    'quantity' => $quantity,
                    'subtotal' => $lineTotal,
                ]);

                // Reservasi stok paket
                $product->decrement('stock', $quantity);
            }

            // Simpan snapshot isian buah pada pesanan (unique id_pesanan, id_buah)
            foreach ($orderFruitsUsed as $fId => $count) {
                if (isset($fruitCatalog[$fId])) {
                    $fruit = $fruitCatalog[$fId];
                    \App\Models\OrderFruit::create([
                        'id_pesanan' => $order->id,
                        'id_buah' => $fruit->id,
                        'nama_buah' => $fruit->nama_buah,
                        'harga_porsi' => $fruit->harga_porsi,
                        'jumlah_porsi' => $count,
                    ]);

                    if ($fruit->stok_porsi >= $count) {
                        $fruit->decrement('stok_porsi', $count);
                    }
                }
            }

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => 'menunggu_pembayaran',
                'description' => 'Pesanan dibuat, menunggu pembayaran.',
            ]);

            return $order;
        });

        Payment::firstOrCreate(
            ['order_id' => $order->id],
            [
                'provider' => 'qris_static',
                'gross_amount' => $order->total,
                'transaction_status' => 'pending',
                'expires_at' => now()->addMinutes(60),
            ]
        );

        session()->forget('cart');

        return redirect()->route('payments.show', $order)
            ->with('success', 'Pesanan berhasil dibuat. Silakan lakukan pembayaran via QRIS dan unggah bukti transfer.');
    }
}
