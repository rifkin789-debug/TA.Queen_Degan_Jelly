<?php

namespace App\Services;

use App\Models\BusinessProfile;
use App\Models\Fruit;
use App\Models\Order;
use App\Models\OrderFruit;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomOrderService
{
    public function __construct(
        private QrisPaymentService $qrisPaymentService,
        private OrderCodeGenerator $orderCodeGenerator,
    ) {}

    /**
     * @param array{
     *     customer_name: string,
     *     phone: string,
     *     fulfillment_method: 'ambil'|'antar',
     *     address?: string|null,
     *     notes?: string|null,
     *     buah: list<int|string>,
     *     jumlah_batok: int|string
     * } $data
     */
    public function create(array $data, ?int $preferredPaymentCode = null): Order
    {
        return DB::transaction(function () use ($data, $preferredPaymentCode): Order {
            $profile = BusinessProfile::query()->lockForUpdate()->findOrFail(1);
            $fruitIds = array_values(array_unique(array_map('intval', $data['buah'])));

            if ($fruitIds === []) {
                throw ValidationException::withMessages([
                    'buah' => 'Pilih minimal satu jenis buah.',
                ]);
            }

            if (count($fruitIds) > $profile->maks_buah_custom) {
                throw ValidationException::withMessages([
                    'buah' => 'Jumlah buah melebihi batas pilihan untuk pesanan custom.',
                ]);
            }

            $fruits = Fruit::query()
                ->whereIn('id', $fruitIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($fruits->count() !== count($fruitIds) || $fruits->contains(fn (Fruit $fruit): bool => ! $fruit->aktif)) {
                throw ValidationException::withMessages([
                    'buah' => 'Pilihan buah tidak tersedia. Periksa kembali buah yang dipilih.',
                ]);
            }

            $batokCount = (int) $data['jumlah_batok'];

            if ($batokCount < 1) {
                throw ValidationException::withMessages([
                    'jumlah_batok' => 'Jumlah batok minimal satu.',
                ]);
            }

            foreach ($fruits as $fruit) {
                if ($fruit->stok_porsi < $batokCount) {
                    throw ValidationException::withMessages([
                        'buah' => "Stok {$fruit->nama_buah} tidak mencukupi untuk jumlah batok tersebut.",
                    ]);
                }
            }

            $pricePerBatok = $profile->harga_dasar_custom + $fruits->sum('harga_porsi');
            $subtotal = $pricePerBatok * $batokCount;
            $shippingCost = $data['fulfillment_method'] === 'antar'
                ? ($profile->biaya_ongkir ?? (int) config('shop.shipping_cost', 10000))
                : 0;

            $order = Order::create([
                'user_id' => null,
                'order_code' => $this->orderCodeGenerator->generate(),
                'customer_name' => $data['customer_name'],
                'phone' => $data['phone'],
                'address' => $data['fulfillment_method'] === 'antar' ? $data['address'] : null,
                'notes' => $data['notes'] ?? null,
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'total' => $subtotal + $shippingCost,
                'payment_status' => 'pending',
                'order_status' => 'menunggu_pembayaran',
                'type' => 'Custom',
                'fulfillment_method' => $data['fulfillment_method'],
                'jumlah_batok' => $batokCount,
            ]);

            foreach ($fruits as $fruit) {
                $updatedRows = Fruit::query()
                    ->whereKey($fruit->id)
                    ->where('aktif', true)
                    ->where('stok_porsi', '>=', $batokCount)
                    ->decrement('stok_porsi', $batokCount);

                if ($updatedRows !== 1) {
                    throw ValidationException::withMessages([
                        'buah' => "Stok {$fruit->nama_buah} baru saja berubah. Silakan periksa ulang pilihan Anda.",
                    ]);
                }

                OrderFruit::create([
                    'id_pesanan' => $order->id,
                    'id_buah' => $fruit->id,
                    'nama_buah' => $fruit->nama_buah,
                    'harga_porsi' => $fruit->harga_porsi,
                    'jumlah_porsi' => $batokCount,
                ]);
            }

            $this->qrisPaymentService->createPendingPayment(
                $order,
                $subtotal + $shippingCost,
                $preferredPaymentCode,
            );

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => 'menunggu_pembayaran',
                'description' => 'Pesanan custom dibuat dan menunggu pembayaran QRIS.',
            ]);

            return $order->fresh(['payment', 'customFruits']);
        }, attempts: 5);
    }

    public function expireDueOrders(): int
    {
        $expiredCount = 0;

        Payment::query()
            ->where('provider', 'qris_static')
            ->whereNotNull('order_id')
            ->where('transaction_status', 'pending')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->whereNull('proof_uploaded_at')
            ->orderBy('id')
            ->chunkById(100, function ($payments) use (&$expiredCount): void {
                foreach ($payments as $payment) {
                    if ($this->expireOrder($payment->order_id)) {
                        $expiredCount++;
                    }
                }
            });

        return $expiredCount;
    }

    public function expireIfDue(int $orderId): bool
    {
        return $this->expireOrder($orderId);
    }

    public function restoreStockForCancelledOrder(Order $order): void
    {
        if (! in_array($order->type, ['Custom', 'Voucher'], true) || $order->stock_returned_at !== null) {
            return;
        }

        OrderFruit::query()
            ->where('id_pesanan', $order->id)
            ->whereNotNull('id_buah')
            ->orderBy('id_buah')
            ->get()
            ->each(function (OrderFruit $orderFruit): void {
                $fruit = Fruit::query()->lockForUpdate()->find($orderFruit->id_buah);

                if ($fruit !== null) {
                    $fruit->increment('stok_porsi', $orderFruit->jumlah_porsi);
                }
            });

        $order->update(['stock_returned_at' => now()]);
    }

    private function expireOrder(int $orderId): bool
    {
        return DB::transaction(function () use ($orderId): bool {
            $order = Order::query()->lockForUpdate()->find($orderId);

            if (! $order || $order->type !== 'Custom' || $order->order_status !== 'menunggu_pembayaran') {
                return false;
            }

            $payment = $order->payment()->lockForUpdate()->first();

            if (! $payment
                || $payment->provider !== 'qris_static'
                || $payment->transaction_status !== 'pending'
                || $payment->proof_uploaded_at !== null
                || $payment->expires_at === null
                || $payment->expires_at->isFuture()) {
                return false;
            }

            $payment->update(['transaction_status' => 'expired']);
            $order->update([
                'payment_status' => 'expired',
                'order_status' => 'dibatalkan',
            ]);
            $this->restoreStockForCancelledOrder($order);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => 'dibatalkan',
                'description' => 'Pesanan otomatis dibatalkan karena melewati batas pembayaran 2 jam.',
            ]);

            return true;
        }, attempts: 5);
    }
}
