<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $status = $request->query('status', 'all');

        $query = Order::query()
            ->where('user_id', $user->id)
            ->with(['items.product', 'payment', 'customFruits.fruit', 'voucher'])
            ->latest();

        if ($status === 'menunggu_pembayaran') {
            $query->whereIn('order_status', ['menunggu_pembayaran', 'menunggu_verifikasi']);
        } elseif ($status === 'pesanan_diterima') {
            $query->whereIn('order_status', ['pesanan_diterima', 'diterima']);
        } elseif (in_array($status, ['sedang_disiapkan', 'dikirim', 'dibatalkan'], true)) {
            $query->where('order_status', $status);
        }

        $orders = $query->paginate(10)->withQueryString();

        $rawCounts = Order::where('user_id', $user->id)
            ->selectRaw('order_status, count(*) as count')
            ->groupBy('order_status')
            ->pluck('count', 'order_status');

        $counts = [
            'all' => Order::where('user_id', $user->id)->count(),
            'menunggu_pembayaran' => ($rawCounts['menunggu_pembayaran'] ?? 0) + ($rawCounts['menunggu_verifikasi'] ?? 0),
            'sedang_disiapkan' => $rawCounts['sedang_disiapkan'] ?? 0,
            'dikirim' => $rawCounts['dikirim'] ?? 0,
            'pesanan_diterima' => ($rawCounts['pesanan_diterima'] ?? 0) + ($rawCounts['diterima'] ?? 0),
            'dibatalkan' => $rawCounts['dibatalkan'] ?? 0,
        ];

        return view('orders.index', compact('orders', 'counts', 'status'));
    }

    public function show(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        $order->load(['items.product', 'customFruits.fruit', 'statusHistories', 'payment', 'voucher']);

        return view('orders.show', compact('order'));
    }
}
