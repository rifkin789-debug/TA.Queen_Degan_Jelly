<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Services\CustomOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::latest()->paginate(20);

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        $order->load(['items', 'customFruits', 'user', 'payment', 'voucher', 'statusHistories']);

        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(
        Request $request,
        Order $order,
        CustomOrderService $customOrderService,
    ): RedirectResponse {
        $data = $request->validate([
            'order_status' => ['required', Rule::in([
                'menunggu_pembayaran', 'menunggu_verifikasi', 'pesanan_diterima', 'sedang_disiapkan', 'dikirim', 'diterima', 'dibatalkan',
            ])],
            'courier' => ['nullable', 'string', 'max:80'],
            'tracking_number' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $result = DB::transaction(function () use ($data, $order, $customOrderService): string {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($data['order_status'] === $lockedOrder->order_status) {
                return 'unchanged';
            }

            if ($lockedOrder->payment_status !== 'paid' && $data['order_status'] !== 'dibatalkan') {
                return 'unpaid';
            }

            if ($data['order_status'] === 'dibatalkan') {
                $customOrderService->restoreStockForCancelledOrder($lockedOrder);

                $payment = $lockedOrder->payment()->lockForUpdate()->first();

                if ($lockedOrder->type === 'Custom'
                    && $payment?->provider === 'qris_static'
                    && $payment->transaction_status !== 'paid') {
                    $payment->update(['transaction_status' => 'cancelled']);
                    $lockedOrder->update(['payment_status' => 'cancelled']);
                }
            }

            $lockedOrder->update([
                'order_status' => $data['order_status'],
                'courier' => $data['courier'] ?? null,
                'tracking_number' => $data['tracking_number'] ?? null,
            ]);

            OrderStatusHistory::create([
                'order_id' => $lockedOrder->id,
                'status' => $data['order_status'],
                'description' => ($data['description'] ?? null) ?: str_replace('_', ' ', ucfirst($data['order_status'])),
            ]);

            return 'updated';
        });

        return match ($result) {
            'unchanged' => back()->with('success', 'Status pesanan tidak berubah.'),
            'unpaid' => back()
                ->withInput()
                ->with('error', 'Status pesanan tidak dapat diproses karena pembayaran belum diterima.'),
            default => back()->with('success', 'Status pesanan diperbarui.'),
        };
    }
}
