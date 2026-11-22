<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use App\Models\VoucherPackage;
use App\Services\VoucherLookupService;
use App\Services\VoucherPurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class VoucherController extends Controller
{
    public function create(): View
    {
        $packages = VoucherPackage::query()
            ->where('aktif', true)
            ->orderBy('harga')
            ->orderBy('id')
            ->get();

        return view('vouchers.purchase', compact('packages'));
    }

    public function store(Request $request, VoucherPurchaseService $voucherPurchaseService): RedirectResponse
    {
        $data = $request->validate([
            'paket_voucher_id' => ['required', 'integer', 'exists:paket_voucher,id'],
            'nama_pengirim' => ['required', 'string', 'max:120'],
            'nomor_hp_pengirim' => ['required', 'string', 'max:30', 'regex:/^(?=(?:\D*\d){4})\+?[0-9][0-9\s().-]{6,28}[0-9]$/'],
            'nama_penerima' => ['nullable', 'string', 'max:120'],
            'pesan_ucapan' => ['nullable', 'string', 'max:200'],
        ], [
            'nomor_hp_pengirim.regex' => 'Nomor HP pengirim tidak valid.',
        ]);

        $voucher = $voucherPurchaseService->create(
            (int) $data['paket_voucher_id'],
            $data,
        );
        $paymentUrl = URL::temporarySignedRoute(
            'voucher.payments.show',
            $voucher->payment->expires_at,
            ['voucher' => $voucher->id],
        );

        return redirect()->to($paymentUrl);
    }

    public function checkForm(): View
    {
        return view('vouchers.check');
    }

    public function check(
        Request $request,
        VoucherLookupService $voucherLookupService,
        VoucherPurchaseService $voucherPurchaseService,
    ): View|RedirectResponse {
        $data = $request->validate([
            'kode_voucher' => ['required', 'string'],
            'phone_last4' => ['required', 'digits:4'],
        ]);
        $voucher = $voucherLookupService->find(
            $data['kode_voucher'],
            $request->ip(),
            $data['phone_last4'],
        );

        if (! $voucher) {
            return back()
                ->withInput()
                ->withErrors(['lookup' => VoucherLookupService::ERROR_MESSAGE]);
        }

        $voucher = $voucherPurchaseService->expireActiveVoucherIfDue($voucher->id);
        $redemptionUrl = $voucher->kode_voucher
            ? route('vouchers.redeem.show', ['kode' => $voucher->kode_voucher])
            : null;

        return view('vouchers.check', compact('voucher', 'redemptionUrl'));
    }
}
