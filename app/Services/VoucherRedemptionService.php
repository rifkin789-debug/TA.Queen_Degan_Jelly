<?php

namespace App\Services;

use App\Models\Fruit;
use App\Models\Order;
use App\Models\OrderFruit;
use App\Models\OrderStatusHistory;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VoucherRedemptionService
{
    public function __construct(private OrderCodeGenerator $orderCodeGenerator) {}

    /**
     * @param array{
     *     customer_name: string,
     *     phone: string,
     *     fulfillment_method: 'ambil'|'antar',
     *     address?: string|null,
     *     notes?: string|null,
     *     buah: list<int|string>
     * } $data
     */
    public function redeem(int $voucherId, array $data): Order
    {
        $order = DB::transaction(function () use ($voucherId, $data): ?Order {
            $voucher = Voucher::query()->lockForUpdate()->findOrFail($voucherId);

            if ($voucher->status !== Voucher::STATUS_ACTIVE) {
                throw ValidationException::withMessages([
                    'voucher' => 'Voucher sudah tidak dapat ditukarkan.',
                ]);
            }

            if ($voucher->berlaku_sampai === null || $voucher->berlaku_sampai->isPast()) {
                $voucher->update(['status' => Voucher::STATUS_EXPIRED]);

                return null;
            }

            $fruitIds = array_values(array_unique(array_map('intval', $data['buah'])));

            if ($fruitIds === []
                || count($fruitIds) !== count($data['buah'])
                || count($fruitIds) > $voucher->maks_buah) {
                throw ValidationException::withMessages([
                    'buah' => 'Pilih 1 sampai '.$voucher->maks_buah.' jenis buah yang berbeda.',
                ]);
            }

            $fruits = Fruit::query()
                ->whereIn('id', $fruitIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($fruits->count() !== count($fruitIds)
                || $fruits->contains(fn (Fruit $fruit): bool => ! $fruit->aktif)) {
                throw ValidationException::withMessages([
                    'buah' => 'Pilihan buah tidak tersedia. Periksa kembali buah yang dipilih.',
                ]);
            }

            foreach ($fruits as $fruit) {
                if ($fruit->stok_porsi < $voucher->jumlah_batok) {
                    throw ValidationException::withMessages([
                        'buah' => "Stok {$fruit->nama_buah} tidak mencukupi.",
                    ]);
                }
            }

            $claimedRows = Voucher::query()
                ->whereKey($voucher->id)
                ->where('status', Voucher::STATUS_ACTIVE)
                ->where('berlaku_sampai', '>', now())
                ->update([
                    'status' => Voucher::STATUS_USED,
                    'dipakai_pada' => now(),
                    'updated_at' => now(),
                ]);

            if ($claimedRows !== 1) {
                $currentVoucher = Voucher::query()->findOrFail($voucher->id);

                if ($currentVoucher->status === Voucher::STATUS_ACTIVE
                    && ($currentVoucher->berlaku_sampai === null || $currentVoucher->berlaku_sampai->isPast())) {
                    $currentVoucher->update(['status' => Voucher::STATUS_EXPIRED]);

                    return null;
                }

                throw ValidationException::withMessages([
                    'voucher' => 'Voucher sudah tidak dapat ditukarkan.',
                ]);
            }

            $order = Order::query()->create([
                'user_id' => null,
                'order_code' => $this->orderCodeGenerator->generate(),
                'customer_name' => $data['customer_name'],
                'phone' => $data['phone'],
                'address' => $data['fulfillment_method'] === 'antar' ? $data['address'] : null,
                'notes' => $data['notes'] ?? null,
                'subtotal' => 0,
                'shipping_cost' => 0,
                'total' => 0,
                'payment_status' => 'paid',
                'order_status' => 'sedang_disiapkan',
                'type' => 'Voucher',
                'fulfillment_method' => $data['fulfillment_method'],
                'jumlah_batok' => $voucher->jumlah_batok,
                'id_voucher' => $voucher->id,
            ]);

            foreach ($fruits as $fruit) {
                $updatedRows = Fruit::query()
                    ->whereKey($fruit->id)
                    ->where('aktif', true)
                    ->where('stok_porsi', '>=', $voucher->jumlah_batok)
                    ->decrement('stok_porsi', $voucher->jumlah_batok);

                if ($updatedRows !== 1) {
                    throw ValidationException::withMessages([
                        'buah' => "Stok {$fruit->nama_buah} baru saja berubah. Silakan coba kembali.",
                    ]);
                }

                OrderFruit::query()->create([
                    'id_pesanan' => $order->id,
                    'id_buah' => $fruit->id,
                    'nama_buah' => $fruit->nama_buah,
                    'harga_porsi' => $fruit->harga_porsi,
                    'jumlah_porsi' => $voucher->jumlah_batok,
                ]);
            }

            OrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'status' => 'sedang_disiapkan',
                'description' => 'Voucher ditukarkan; pesanan sudah dibayar dan sedang disiapkan.',
            ]);

            return $order->fresh(['customFruits', 'statusHistories', 'voucher']);
        }, attempts: 5);

        if ($order === null) {
            throw ValidationException::withMessages([
                'voucher' => 'Masa berlaku voucher telah berakhir.',
            ]);
        }

        return $order;
    }
}
