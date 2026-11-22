<?php

namespace App\Services;

use App\Models\Voucher;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class VoucherLookupService
{
    public const ERROR_MESSAGE = 'Kode voucher atau verifikasi tidak cocok.';

    public function find(string $code, ?string $ip, ?string $phoneLast4 = null): ?Voucher
    {
        $rateLimitKey = 'voucher-lookup:'.($ip ?? 'unknown');

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            return null;
        }

        $normalizedCode = Str::upper(trim($code));

        if (! preg_match('/\AQDJ-V-[A-HJ-NP-Z2-9]{8}\z/', $normalizedCode)) {
            return $this->failedLookup($rateLimitKey);
        }

        $voucher = Voucher::query()->where('kode_voucher', $normalizedCode)->first();

        if (! $voucher || ($phoneLast4 !== null && ! $this->phoneMatches($voucher, $phoneLast4))) {
            return $this->failedLookup($rateLimitKey);
        }

        RateLimiter::clear($rateLimitKey);

        return $voucher;
    }

    private function failedLookup(string $rateLimitKey): ?Voucher
    {
        RateLimiter::hit($rateLimitKey, 600);

        return null;
    }

    private function phoneMatches(Voucher $voucher, string $phoneLast4): bool
    {
        $phoneDigits = preg_replace('/\D+/', '', $voucher->nomor_hp_pengirim);

        return is_string($phoneDigits)
            && strlen($phoneDigits) >= 4
            && hash_equals(substr($phoneDigits, -4), $phoneLast4);
    }
}
