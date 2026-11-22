<?php

namespace App\Http\Controllers;

use App\Models\BusinessProfile;
use App\Models\Voucher;
use App\Services\VoucherPurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VoucherPaymentController extends Controller
{
    public function show(Voucher $voucher, VoucherPurchaseService $voucherPurchaseService): View
    {
        $voucherPurchaseService->expireIfDue($voucher->id);
        $voucher->load('payment');

        abort_unless($voucher->payment !== null, 404);

        $payment = $voucher->payment;
        $profile = BusinessProfile::query()->findOrFail(1);
        $qrisImageUrl = $profile->qris_image_path
            ? Storage::disk('public')->url($profile->qris_image_path)
            : null;
        $proofUploadUrl = null;

        if ($voucher->status === Voucher::STATUS_WAITING_PAYMENT
            && in_array($payment->transaction_status, ['pending', 'rejected'], true)
            && $payment->expires_at?->isFuture()) {
            $proofUploadUrl = URL::temporarySignedRoute(
                'voucher.payments.proof.store',
                $payment->expires_at,
                ['voucher' => $voucher->id],
            );
        }

        $order = null;

        return view('payments.qris', compact(
            'order',
            'voucher',
            'payment',
            'qrisImageUrl',
            'proofUploadUrl',
        ));
    }

    public function store(Request $request, Voucher $voucher, VoucherPurchaseService $voucherPurchaseService): RedirectResponse
    {
        $data = $request->validate([
            'payment_proof' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ], [
            'payment_proof.image' => 'Bukti pembayaran harus berupa gambar.',
            'payment_proof.mimes' => 'Bukti pembayaran hanya boleh berformat JPG atau PNG.',
            'payment_proof.max' => 'Ukuran bukti pembayaran maksimal 2 MB.',
        ]);

        $result = DB::transaction(function () use ($data, $voucher, $voucherPurchaseService): string {
            $lockedVoucher = Voucher::query()->lockForUpdate()->findOrFail($voucher->id);
            $payment = $lockedVoucher->payment()->lockForUpdate()->firstOrFail();

            $isFirstUpload = $payment->transaction_status === 'pending' && $payment->proof_uploaded_at === null;
            $isReuploadAfterRejection = $payment->transaction_status === 'rejected';

            if ($lockedVoucher->status !== Voucher::STATUS_WAITING_PAYMENT
                || $payment->provider !== 'qris_static'
                || (! $isFirstUpload && ! $isReuploadAfterRejection)
                || $payment->expires_at === null) {
                throw ValidationException::withMessages([
                    'payment_proof' => 'Voucher tidak lagi menerima bukti pembayaran.',
                ]);
            }

            if ($payment->expires_at->isPast()) {
                $payment->update(['transaction_status' => 'expired']);
                $lockedVoucher->update(['status' => Voucher::STATUS_CANCELLED]);
                $voucherPurchaseService->restoreStockForCancelledVoucher($lockedVoucher);

                return 'expired';
            }

            $path = $data['payment_proof']->store('payment-proofs', 'local');

            if (! is_string($path) || $path === '') {
                abort(500, 'Bukti pembayaran gagal disimpan.');
            }

            if ($payment->proof_path && Storage::disk('local')->exists($payment->proof_path)) {
                Storage::disk('local')->delete($payment->proof_path);
            }

            $payment->update([
                'proof_path' => $path,
                'proof_uploaded_at' => now(),
                'transaction_status' => 'pending_verification',
                'rejection_reason' => null,
            ]);
            $lockedVoucher->update(['status' => Voucher::STATUS_WAITING_VERIFICATION]);

            return 'uploaded';
        }, attempts: 5);

        if ($result === 'expired') {
            return back()->withErrors([
                'payment_proof' => 'Batas pembayaran dua jam telah lewat. Voucher dibatalkan.',
            ]);
        }

        return back()->with('success', 'Bukti pembayaran berhasil dikirim dan menunggu verifikasi admin.');
    }
}
