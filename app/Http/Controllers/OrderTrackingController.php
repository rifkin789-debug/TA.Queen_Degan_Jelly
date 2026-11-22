<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\CustomOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class OrderTrackingController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('orders.index');
        }

        return view('orders.track');
    }

    public function show(Request $request, CustomOrderService $customOrderService): View|RedirectResponse
    {
        $data = $request->validate([
            'order_code' => ['required', 'string', 'max:40'],
            'phone_last4' => ['required', 'digits:4'],
        ]);

        $order = Order::query()
            ->where('order_code', $data['order_code'])
            ->first();

        $phoneDigits = $order ? preg_replace('/\D+/', '', $order->phone) : null;
        $phoneMatches = is_string($phoneDigits)
            && strlen($phoneDigits) >= 4
            && hash_equals(substr($phoneDigits, -4), $data['phone_last4']);

        if (! $order || ! $phoneMatches) {
            return back()
                ->withInput()
                ->withErrors(['lookup' => 'Kode pesanan atau 4 digit akhir nomor HP tidak sesuai.']);
        }

        $customOrderService->expireIfDue($order->id);
        $order->load(['items', 'customFruits', 'statusHistories', 'payment']);
        $paymentUrl = null;

        if ($order->payment && in_array($order->payment->transaction_status, ['pending', 'rejected'], true) && $order->payment->expires_at?->isFuture()) {
            $paymentUrl = $order->type === 'Custom'
                ? URL::temporarySignedRoute('payments.qris.show', $order->payment->expires_at, ['order' => $order->id])
                : route('payments.show', $order);
        }

        return view('orders.public-show', compact('order', 'paymentUrl'));
    }
}
