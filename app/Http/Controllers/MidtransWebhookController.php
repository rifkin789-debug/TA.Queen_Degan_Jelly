<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Midtrans\Config as MidtransConfig;
use Midtrans\Notification;

class MidtransWebhookController extends Controller
{
    public function handle(Request $request)
    {
        MidtransConfig::$serverKey = config('services.midtrans.server_key');
        MidtransConfig::$isProduction = (bool) config('services.midtrans.is_production');

        try {
            $notification = new Notification;
            $orderCode = $notification->order_id;
            $transactionStatus = $notification->transaction_status;
            $fraudStatus = $notification->fraud_status ?? null;
            $paymentType = $notification->payment_type ?? null;
            $transactionId = $notification->transaction_id ?? null;

            DB::transaction(function () use ($orderCode, $transactionStatus, $fraudStatus, $paymentType, $transactionId, $request) {
                $order = Order::where('order_code', $orderCode)->lockForUpdate()->firstOrFail();
                $payment = Payment::where('order_id', $order->id)->firstOrFail();

                $paymentStatus = match (true) {
                    $transactionStatus === 'capture' && $fraudStatus === 'accept' => 'paid',
                    $transactionStatus === 'settlement' => 'paid',
                    $transactionStatus === 'pending' => 'pending',
                    in_array($transactionStatus, ['deny', 'cancel'], true) => 'failed',
                    $transactionStatus === 'expire' => 'expired',
                    $transactionStatus === 'refund' => 'refunded',
                    default => $payment->transaction_status,
                };

                $payment->update([
                    'provider_transaction_id' => $transactionId,
                    'transaction_status' => $paymentStatus,
                    'payment_type' => $paymentType,
                    'payload' => $request->all(),
                ]);
                $order->update(['payment_status' => $paymentStatus]);

                if ($paymentStatus === 'paid' && $order->order_status === 'menunggu_pembayaran') {
                    $order->update(['order_status' => 'pesanan_diterima']);
                    OrderStatusHistory::create([
                        'order_id' => $order->id,
                        'status' => 'pesanan_diterima',
                        'description' => 'Pembayaran berhasil dan pesanan diterima.',
                    ]);
                }
            });

            return response()->json(['message' => 'OK']);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Notification processing failed'], 500);
        }
    }
}
