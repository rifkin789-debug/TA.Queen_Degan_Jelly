<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Services\VoucherPurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VoucherPaymentController extends Controller
{
    public function index(): View
    {
        $vouchers = Voucher::query()
            ->with('payment')
            ->where('status', Voucher::STATUS_WAITING_VERIFICATION)
            ->latest()
            ->paginate(20);

        return view('admin.voucher-payments.index', compact('vouchers'));
    }

    public function show(Voucher $voucher): View
    {
        $voucher->load('payment');

        return view('admin.voucher-payments.show', compact('voucher'));
    }

    public function proof(Voucher $voucher): BinaryFileResponse
    {
        $payment = $voucher->payment()->firstOrFail();
        $path = $payment->proof_path;

        abort_unless(is_string($path) && Storage::disk('local')->exists($path), 404);

        $mimeType = Storage::disk('local')->mimeType($path);
        abort_unless(in_array($mimeType, ['image/jpeg', 'image/png'], true), 404);

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => $mimeType,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function activate(
        Voucher $voucher,
        VoucherPurchaseService $voucherPurchaseService,
    ): RedirectResponse {
        $activatedVoucher = $voucherPurchaseService->activate($voucher);

        return redirect()
            ->route('admin.voucher-payments.show', $activatedVoucher)
            ->with('success', 'Pembayaran diverifikasi dan voucher telah diaktifkan.');
    }
}
