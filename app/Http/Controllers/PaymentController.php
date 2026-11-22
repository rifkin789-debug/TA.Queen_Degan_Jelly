<?php

namespace App\Http\Controllers;

use App\Models\BusinessProfile;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PaymentController extends Controller
{
    /**
     * Tampilkan halaman pembayaran QRIS untuk pesanan.
     */
    public function show(Order $order): View
    {
        // Otorisasi: Hanya pemilik pesanan atau admin yang dapat melihat
        abort_unless(
            auth()->check() && (auth()->user()->isAdmin() || auth()->id() === $order->user_id),
            403,
            'Anda tidak memiliki akses ke pesanan ini.'
        );

        $order->load(['items', 'customFruits', 'payment']);
        $payment = $order->payment;

        // Cek kedaluwarsa pesanan jika belum dibayar & belum ada bukti
        if ($payment && $payment->transaction_status === 'pending' && $payment->expires_at && $payment->expires_at->isPast()) {
            DB::transaction(function () use ($order, $payment) {
                $payment->update(['transaction_status' => 'expired']);
                $order->update([
                    'payment_status' => 'expired',
                    'order_status' => 'dibatalkan',
                ]);

                // Kembalikan stok produk jika belum dikembalikan
                if (! $order->stock_returned_at) {
                    foreach ($order->items as $item) {
                        if ($item->product_id) {
                            Product::whereKey($item->product_id)->increment('stock', $item->quantity);
                        }
                    }
                    $order->update(['stock_returned_at' => now()]);
                }
            });
            $payment->refresh();
            $order->refresh();
        }

        $profile = BusinessProfile::query()->find(1) ?? new BusinessProfile();
        $qrisImageUrl = $profile->qris_image_path
            ? Storage::disk('public')->url($profile->qris_image_path)
            : null;

        return view('checkout.payment', compact('order', 'payment', 'profile', 'qrisImageUrl'));
    }

    /**
     * Unggah bukti transfer QRIS manual dengan proteksi anti-duplikasi hash dan private storage.
     */
    public function uploadProof(Request $request, Order $order): RedirectResponse
    {
        // Otorisasi: Hanya pemilik pesanan yang dapat mengunggah
        abort_unless(
            auth()->check() && (auth()->user()->isAdmin() || auth()->id() === $order->user_id),
            403,
            'Anda tidak memiliki izin mengunggah bukti untuk pesanan ini.'
        );

        $data = $request->validate([
            'payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:2048'],
            'customer_notes' => ['nullable', 'string', 'max:500'],
        ], [
            'payment_proof.required' => 'Bukti pembayaran wajib diunggah.',
            'payment_proof.file' => 'Bukti pembayaran harus berupa berkas berkas yang valid.',
            'payment_proof.mimes' => 'Bukti pembayaran hanya boleh berformat JPG atau PNG.',
            'payment_proof.max' => 'Ukuran berkas bukti pembayaran maksimal 2 MB.',
        ]);

        $file = $request->file('payment_proof');

        // Validasi MIME type sebenarnya
        $realMime = $file->getMimeType();
        if (! in_array($realMime, ['image/jpeg', 'image/png'], true)) {
            throw ValidationException::withMessages([
                'payment_proof' => 'Tipe berkas tidak diizinkan. Hanya gambar JPG atau PNG yang diperbolehkan.',
            ]);
        }

        // Hitung SHA-256 hash berkas untuk mencegah bukti transfer ganda/duplikat
        $fileHash = hash_file('sha256', $file->getRealPath());

        $existingDuplicate = Payment::where('proof_hash', $fileHash)
            ->where('order_id', '!=', $order->id)
            ->whereIn('transaction_status', ['pending_verification', 'paid'])
            ->exists();

        if ($existingDuplicate) {
            throw ValidationException::withMessages([
                'payment_proof' => 'Bukti transfer ini terdeteksi sudah pernah digunakan pada pesanan lain. Harap unggah bukti transfer yang asli dan baru.',
            ]);
        }

        DB::transaction(function () use ($order, $file, $fileHash, $data) {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            $payment = $lockedOrder->payment()->lockForUpdate()->firstOrFail();

            if (! in_array($payment->transaction_status, ['pending', 'rejected'], true)) {
                throw ValidationException::withMessages([
                    'payment_proof' => 'Pesanan saat ini tidak dapat menerima unggahan bukti pembayaran.',
                ]);
            }

            if ($payment->expires_at && $payment->expires_at->isPast()) {
                throw ValidationException::withMessages([
                    'payment_proof' => 'Batas waktu pembayaran pesanan ini telah berakhir.',
                ]);
            }

            // Simpan berkas di storage privat (bukan folder publik yang bisa ditebak)
            $extension = $file->getClientOriginalExtension() ?: 'jpg';
            $uniqueFilename = Str::uuid()->toString() . '.' . $extension;
            $path = $file->storeAs('payment-proofs', $uniqueFilename, 'local');

            if (! $path) {
                abort(500, 'Gagal menyimpan bukti pembayaran ke media privat.');
            }

            // Hapus berkas bukti lama jika ada
            if ($payment->proof_path && Storage::disk('local')->exists($payment->proof_path)) {
                Storage::disk('local')->delete($payment->proof_path);
            }

            $payment->update([
                'proof_path' => $path,
                'proof_hash' => $fileHash,
                'customer_notes' => $data['customer_notes'] ?? null,
                'proof_uploaded_at' => now(),
                'transaction_status' => 'pending_verification',
                'rejection_reason' => null, // reset alasan jika unggah ulang
            ]);

            $lockedOrder->update([
                'payment_status' => 'pending_verification',
                'order_status' => 'menunggu_verifikasi',
            ]);

            OrderStatusHistory::create([
                'order_id' => $lockedOrder->id,
                'status' => 'menunggu_verifikasi',
                'description' => 'Bukti transfer QRIS diunggah pelanggan, menunggu verifikasi pengelola.',
            ]);
        });

        return back()->with('success', 'Bukti pembayaran berhasil diunggah! Pengelola akan segera memverifikasi pesanan Anda.');
    }

    /**
     * Tampilkan berkas bukti privat dengan otorisasi ketat.
     */
    public function viewProof(Order $order): BinaryFileResponse
    {
        abort_unless(
            auth()->check() && (auth()->user()->isAdmin() || auth()->id() === $order->user_id),
            403,
            'Akses berkas ditolak.'
        );

        $payment = $order->payment()->firstOrFail();
        $path = $payment->proof_path;

        abort_unless(is_string($path) && Storage::disk('local')->exists($path), 404, 'Berkas bukti tidak ditemukan.');

        $mimeType = Storage::disk('local')->mimeType($path);
        abort_unless(in_array($mimeType, ['image/jpeg', 'image/png'], true), 404);

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => $mimeType,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
