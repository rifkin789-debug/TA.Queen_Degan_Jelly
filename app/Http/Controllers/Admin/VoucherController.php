<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Models\VoucherPackage;
use App\Services\VoucherPurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VoucherController extends Controller
{
    public function index(Request $request, VoucherPurchaseService $voucherPurchaseService): View
    {
        $statuses = [
            Voucher::STATUS_WAITING_PAYMENT,
            Voucher::STATUS_WAITING_VERIFICATION,
            Voucher::STATUS_ACTIVE,
            Voucher::STATUS_USED,
            Voucher::STATUS_EXPIRED,
            Voucher::STATUS_CANCELLED,
        ];
        $filters = $request->validate([
            'status' => ['nullable', Rule::in($statuses)],
        ]);

        $voucherPurchaseService->expireDueActiveVouchers();

        $vouchers = Voucher::query()
            ->with('payment')
            ->when(
                $filters['status'] ?? null,
                fn ($query, string $status) => $query->where('status', $status),
            )
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $packages = VoucherPackage::query()
            ->orderBy('nama_paket')
            ->get();

        return view('admin.vouchers.index', compact('vouchers', 'statuses', 'packages'));
    }

    public function show(Voucher $voucher, VoucherPurchaseService $voucherPurchaseService): View
    {
        if ($voucher->status === Voucher::STATUS_ACTIVE) {
            $voucher = $voucherPurchaseService->expireActiveVoucherIfDue($voucher->id);
        }

        $voucher->load(['payment', 'redemptionOrder']);

        return view('admin.vouchers.show', compact('voucher'));
    }

    public function cancel(Voucher $voucher, VoucherPurchaseService $voucherPurchaseService): RedirectResponse
    {
        DB::transaction(function () use ($voucher, $voucherPurchaseService): void {
            $lockedVoucher = Voucher::query()->lockForUpdate()->findOrFail($voucher->id);

            if (in_array($lockedVoucher->status, [
                Voucher::STATUS_USED,
                Voucher::STATUS_CANCELLED,
            ], true)) {
                throw ValidationException::withMessages([
                    'voucher' => 'Voucher yang sudah dipakai atau dibatalkan tidak dapat dibatalkan kembali.',
                ]);
            }

            $payment = $lockedVoucher->payment()->lockForUpdate()->first();

            if ($payment !== null && $payment->transaction_status !== 'paid') {
                $payment->update(['transaction_status' => 'cancelled']);
                $voucherPurchaseService->restoreStockForCancelledVoucher($lockedVoucher);
            }

            $lockedVoucher->update(['status' => Voucher::STATUS_CANCELLED]);
        }, attempts: 5);

        return redirect()
            ->route('admin.vouchers.show', $voucher)
            ->with('success', 'Voucher berhasil dibatalkan.');
    }
}
