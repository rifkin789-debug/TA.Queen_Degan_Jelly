<?php

namespace App\Http\Controllers;

use App\Models\BusinessProfile;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Services\CustomOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PaymentProofController extends Controller
{
    public function show(Order $order, CustomOrderService $customOrderService): View
    {
        abort_unless($order->type === 'Custom', 404);

        $customOrderService->expireIfDue($order->id);
        $order->load(['payment', 'customFruits']);
        $profile = BusinessProfile::query()->findOrFail(1);
        $payment = $order->payment;
        $qrisImageUrl = $profile->qris_image_path
            ? Storage::disk('public')->url($profile->qris_image_path)
            : null;
        $proofUploadUrl = null;

        if (in_array($payment?->transaction_status, ['pending', 'rejected'], true)
            && $payment->expires_at?->isFuture()) {
            $proofUploadUrl = URL::temporarySignedRoute(
                'payments.qris.proof.store',
                $payment->expires_at,
                ['order' => $order->id],
            );
        }

        $voucher = null;

        return view('payments.qris', compact(
            'order',
            'voucher',
            'payment',
            'qrisImageUrl',
            'proofUploadUrl',
        ));
    }

    public function store(
        Request $request,
        Order $order,
        CustomOrderService $customOrderService,
    ): RedirectResponse {
        abort_unless($order->type === 'Custom', 404);

        $data = $request->validate([
            'payment_proof' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ], [
            'payment_proof.image' => 'Bukti pembayaran harus berupa gambar.',
            'payment_proof.mimes' => 'Bukti pembayaran hanya boleh berformat JPG atau PNG.',
            'payment_proof.max' => 'Ukuran bukti pembayaran maksimal 2 MB.',
        ]);

        $customOrderService->expireIfDue($order->id);

        DB::transaction(function () use ($data, $order): void {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            $payment = $lockedOrder->payment()->lockForUpdate()->firstOrFail();

            $isFirstUpload = $payment->transaction_status === 'pending' && $payment->proof_uploaded_at === null;
            $isReuploadAfterRejection = $payment->transaction_status === 'rejected';

            if ($lockedOrder->type !== 'Custom'
                || $lockedOrder->order_status !== 'menunggu_pembayaran'
                || $payment->provider !== 'qris_static'
                || (! $isFirstUpload && ! $isReuploadAfterRejection)
                || $payment->expires_at === null
                || $payment->expires_at->isPast()) {
                throw ValidationException::withMessages([
                    'payment_proof' => 'Pesanan tidak lagi menerima bukti pembayaran.',
                ]);
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
            $lockedOrder->update([
                'payment_status' => 'pending_verification',
                'order_status' => 'menunggu_verifikasi',
            ]);

            OrderStatusHistory::create([
                'order_id' => $lockedOrder->id,
                'status' => 'menunggu_verifikasi',
                'description' => 'Bukti pembayaran QRIS diterima dan menunggu verifikasi admin.',
            ]);
        }, attempts: 5);

        return back()->with('success', 'Bukti pembayaran berhasil dikirim dan menunggu verifikasi admin.');
    }
}
