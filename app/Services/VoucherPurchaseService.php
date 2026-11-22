<?php

namespace App\Services;

use App\Models\BusinessProfile;
use App\Models\Payment;
use App\Models\Voucher;
use App\Models\VoucherPackage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VoucherPurchaseService
{
    public function __construct(private QrisPaymentService $qrisPaymentService) {}

    /**
     * @param array{
     *     nama_pengirim: string,
     *     nomor_hp_pengirim: string,
     *     nama_penerima?: string|null,
     *     pesan_ucapan?: string|null
     * } $data
     */
    public function create(int $packageId, array $data): Voucher
    {
        return DB::transaction(function () use ($packageId, $data): Voucher {
            $package = VoucherPackage::query()->lockForUpdate()->findOrFail($packageId);

            if (! $package->aktif) {
                throw ValidationException::withMessages([
                    'paket_voucher_id' => 'Paket voucher tidak tersedia.',
                ]);
            }

            if ($package->stok < 1) {
                throw ValidationException::withMessages([
                    'paket_voucher_id' => 'Stok paket voucher ini sudah habis.',
                ]);
            }

            $decremented = VoucherPackage::query()
                ->whereKey($package->id)
                ->where('stok', '>=', 1)
                ->decrement('stok', 1);

            if ($decremented !== 1) {
                throw ValidationException::withMessages([
                    'paket_voucher_id' => 'Stok paket voucher ini sudah habis.',
                ]);
            }

            $voucher = Voucher::query()->create([
                'id_paket' => $package->id,
                'nama_paket' => $package->nama_paket,
                'harga' => $package->harga,
                'jumlah_batok' => $package->jumlah_batok,
                'maks_buah' => $package->maks_buah,
                'nama_pengirim' => $data['nama_pengirim'],
                'nomor_hp_pengirim' => $data['nomor_hp_pengirim'],
                'nama_penerima' => $data['nama_penerima'] ?? null,
                'pesan_ucapan' => $data['pesan_ucapan'] ?? null,
                'kode_voucher' => $this->generateVoucherCode(),
                'status' => Voucher::STATUS_WAITING_PAYMENT,
            ]);

            $this->qrisPaymentService->createPendingVoucherPayment($voucher, $package->harga);

            return $voucher->fresh('payment');
        }, attempts: 5);
    }

    public function expireDuePayments(): int
    {
        $expiredCount = 0;

        Payment::query()
            ->whereNotNull('voucher_id')
            ->where('provider', 'qris_static')
            ->where('transaction_status', 'pending')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->whereNull('proof_uploaded_at')
            ->orderBy('id')
            ->chunkById(100, function ($payments) use (&$expiredCount): void {
                foreach ($payments as $payment) {
                    if ($this->expireIfDue($payment->voucher_id)) {
                        $expiredCount++;
                    }
                }
            });

        return $expiredCount;
    }

    public function expireIfDue(int $voucherId): bool
    {
        return DB::transaction(function () use ($voucherId): bool {
            $voucher = Voucher::query()->lockForUpdate()->find($voucherId);

            if (! $voucher || $voucher->status !== Voucher::STATUS_WAITING_PAYMENT) {
                return false;
            }

            $payment = $voucher->payment()->lockForUpdate()->first();

            if (! $payment
                || $payment->provider !== 'qris_static'
                || $payment->transaction_status !== 'pending'
                || $payment->proof_uploaded_at !== null
                || $payment->expires_at === null
                || $payment->expires_at->isFuture()) {
                return false;
            }

            $payment->update(['transaction_status' => 'expired']);
            $voucher->update(['status' => Voucher::STATUS_CANCELLED]);
            $this->restoreStockForCancelledVoucher($voucher);

            return true;
        }, attempts: 5);
    }

    public function restoreStockForCancelledVoucher(Voucher $voucher): void
    {
        if ($voucher->id_paket === null || $voucher->stock_returned_at !== null) {
            return;
        }

        $package = VoucherPackage::query()->lockForUpdate()->find($voucher->id_paket);
        if ($package !== null) {
            $package->increment('stok', 1);
        }

        $voucher->update(['stock_returned_at' => now()]);
    }

    public function expireActiveVoucherIfDue(int $voucherId): Voucher
    {
        return DB::transaction(function () use ($voucherId): Voucher {
            $voucher = Voucher::query()->lockForUpdate()->findOrFail($voucherId);

            if ($voucher->status === Voucher::STATUS_ACTIVE
                && $voucher->berlaku_sampai !== null
                && $voucher->berlaku_sampai->isPast()) {
                $voucher->update(['status' => Voucher::STATUS_EXPIRED]);
            }

            return $voucher->fresh();
        }, attempts: 5);
    }

    public function expireDueActiveVouchers(): int
    {
        return Voucher::query()
            ->where('status', Voucher::STATUS_ACTIVE)
            ->whereNotNull('berlaku_sampai')
            ->where('berlaku_sampai', '<=', now())
            ->update(['status' => Voucher::STATUS_EXPIRED]);
    }

    public function activate(Voucher $voucher): Voucher
    {
        return DB::transaction(function () use ($voucher): Voucher {
            $lockedVoucher = Voucher::query()->lockForUpdate()->findOrFail($voucher->id);
            $payment = $lockedVoucher->payment()->lockForUpdate()->firstOrFail();

            if ($lockedVoucher->status !== Voucher::STATUS_WAITING_VERIFICATION
                || $payment->transaction_status !== 'pending_verification'
                || $payment->proof_uploaded_at === null) {
                throw ValidationException::withMessages([
                    'voucher' => 'Voucher tidak sedang menunggu verifikasi pembayaran.',
                ]);
            }

            $profile = BusinessProfile::query()->lockForUpdate()->findOrFail(1);

            $lockedVoucher->update([
                'kode_voucher' => $lockedVoucher->kode_voucher ?: $this->generateVoucherCode(),
                'status' => Voucher::STATUS_ACTIVE,
                'berlaku_sampai' => now()->addDays($profile->masa_berlaku_voucher_hari),
            ]);
            $payment->update(['transaction_status' => 'paid']);

            return $lockedVoucher->fresh(['payment']);
        }, attempts: 5);
    }

    private function generateVoucherCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $suffix = '';

            for ($index = 0; $index < 8; $index++) {
                $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }

            $code = 'QDJ-V-'.$suffix;
        } while (Voucher::query()->where('kode_voucher', $code)->exists());

        return Str::upper($code);
    }
}
