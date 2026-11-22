<?php

namespace App\Services;

use App\Models\BusinessProfile;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class QrisPaymentService
{
    public function previewUniqueCode(): int
    {
        $profile = BusinessProfile::query()->findOrFail(1);

        return $this->findAvailableCode($profile, null);
    }

    public function createPendingPayment(Order $order, int $amountBeforeCode, ?int $preferredCode = null): Payment
    {
        $profile = BusinessProfile::query()->lockForUpdate()->findOrFail(1);
        $uniqueCode = $this->findAvailableCode($profile, $preferredCode);
        $total = $amountBeforeCode + $uniqueCode;
        $expiresAt = now()->addHours(2);

        $profile->update([
            'kode_pembayaran_berikutnya' => $uniqueCode === 999 ? 100 : $uniqueCode + 1,
        ]);
        $order->update([
            'unique_code' => $uniqueCode,
            'total' => $total,
        ]);

        return $order->payment()->create([
            'provider' => 'qris_static',
            'unique_code' => $uniqueCode,
            'gross_amount' => $total,
            'transaction_status' => 'pending',
            'payment_type' => 'qris',
            'expires_at' => $expiresAt,
        ]);
    }

    public function createPendingVoucherPayment(Voucher $voucher, int $amountBeforeCode): Payment
    {
        $profile = BusinessProfile::query()->lockForUpdate()->findOrFail(1);
        $uniqueCode = $this->findAvailableCode($profile, null);
        $total = $amountBeforeCode + $uniqueCode;
        $expiresAt = now()->addHours(2);

        $profile->update([
            'kode_pembayaran_berikutnya' => $uniqueCode === 999 ? 100 : $uniqueCode + 1,
        ]);

        return Payment::query()->create([
            'voucher_id' => $voucher->id,
            'provider' => 'qris_static',
            'unique_code' => $uniqueCode,
            'gross_amount' => $total,
            'transaction_status' => 'pending',
            'payment_type' => 'qris',
            'expires_at' => $expiresAt,
        ]);
    }

    private function findAvailableCode(BusinessProfile $profile, ?int $preferredCode): int
    {
        $startCode = max(100, min(999, $profile->kode_pembayaran_berikutnya));
        $candidates = [];

        if ($preferredCode !== null && $preferredCode >= 100 && $preferredCode <= 999) {
            $candidates[] = $preferredCode;
        }

        for ($offset = 0; $offset < 900; $offset++) {
            $candidates[] = 100 + (($startCode - 100 + $offset) % 900);
        }

        foreach (array_unique($candidates) as $candidate) {
            $codeIsInUse = Payment::query()
                ->where('provider', 'qris_static')
                ->where(function (Builder $query): void {
                    $query->where(function (Builder $pending): void {
                        $pending->where('transaction_status', 'pending')
                            ->where('expires_at', '>', now());
                    })->orWhere(function (Builder $verification): void {
                        $verification->where('transaction_status', 'pending_verification')
                            ->whereNotNull('proof_uploaded_at');
                    });
                })
                ->where('unique_code', $candidate)
                ->exists();

            if (! $codeIsInUse) {
                return $candidate;
            }
        }

        throw ValidationException::withMessages([
            'payment' => 'Semua kode pembayaran sedang digunakan. Silakan coba beberapa saat lagi.',
        ]);
    }
}
