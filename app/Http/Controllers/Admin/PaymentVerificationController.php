<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\PaymentVerificationLog;
use App\Models\Voucher;
use App\Services\VoucherPurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PaymentVerificationController extends Controller
{
    /**
     * Tampilkan daftar pembayaran yang menunggu verifikasi.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status', 'pending_verification');

        $query = Payment::query()
            ->with(['order', 'voucher', 'verifier'])
            ->latest('proof_uploaded_at');

        if ($status === 'pending_verification') {
            $query->where('transaction_status', 'pending_verification');
        } elseif ($status === 'paid') {
            $query->where('transaction_status', 'paid');
        } elseif ($status === 'rejected') {
            $query->where('transaction_status', 'rejected');
        }

        $payments = $query->paginate(15)->withQueryString();

        $pendingCount = Payment::where('transaction_status', 'pending_verification')->count();
        $paidCount = Payment::where('transaction_status', 'paid')->count();
        $rejectedCount = Payment::where('transaction_status', 'rejected')->count();

        return view('admin.payments.index', compact('payments', 'status', 'pendingCount', 'paidCount', 'rejectedCount'));
    }

    /**
     * Lihat berkas bukti transfer secara aman dari media privat.
     */
    public function proof(Payment $payment): BinaryFileResponse
    {
        $path = $payment->proof_path;
        abort_unless(is_string($path) && Storage::disk('local')->exists($path), 404, 'Berkas bukti pembayaran tidak ditemukan.');

        $mimeType = Storage::disk('local')->mimeType($path);
        abort_unless(in_array($mimeType, ['image/jpeg', 'image/png'], true), 404);

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => $mimeType,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Setujui pembayaran QRIS.
     */
    public function approve(Request $request, Payment $payment, VoucherPurchaseService $voucherService): RedirectResponse
    {
        $status = DB::transaction(function () use ($request, $payment, $voucherService): string {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($lockedPayment->transaction_status === 'paid') {
                return 'already_paid';
            }

            if ($lockedPayment->transaction_status !== 'pending_verification') {
                return 'not_pending';
            }

            // Jika pembayaran terhubung dengan Voucher Gift, aktifkan terlebih dahulu
            // sebelum memperbarui kolom audit pembayaran agar validasi status tidak bentrok.
            if ($lockedPayment->voucher_id) {
                $voucher = $lockedPayment->voucher()->lockForUpdate()->first();
                if ($voucher) {
                    $voucherService->activate($voucher);
                }
            }

            $lockedPayment->update([
                'transaction_status' => 'paid',
                'rejection_reason' => null,
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
            ]);

            // Jika pembayaran terhubung dengan Pesanan
            if ($lockedPayment->order_id) {
                $order = $lockedPayment->order()->lockForUpdate()->first();
                if ($order) {
                    $order->update([
                        'payment_status' => 'paid',
                        'order_status' => 'sedang_disiapkan',
                    ]);

                    OrderStatusHistory::create([
                        'order_id' => $order->id,
                        'status' => 'sedang_disiapkan',
                        'description' => 'Pembayaran QRIS telah diverifikasi dan disetujui oleh admin. Pesanan mulai diproses.',
                    ]);
                }
            }

            // Catat log audit verifikasi
            PaymentVerificationLog::create([
                'payment_id' => $lockedPayment->id,
                'order_id' => $lockedPayment->order_id,
                'voucher_id' => $lockedPayment->voucher_id,
                'admin_id' => $request->user()->id,
                'action' => 'approved',
                'reason' => 'Pembayaran valid dan sesuai.',
                'ip_address' => $request->ip(),
            ]);

            return 'approved';
        });

        return match ($status) {
            'already_paid' => back()->with('success', 'Pembayaran ini sudah disetujui sebelumnya.'),
            'not_pending' => back()->with('error', 'Pembayaran ini tidak dalam status menunggu verifikasi.'),
            default => back()->with('success', 'Pembayaran berhasil disetujui! Status pesanan/voucher telah diperbarui.'),
        };
    }

    /**
     * Tolak pembayaran QRIS dengan alasan penolakan.
     */
    public function reject(Request $request, Payment $payment): RedirectResponse
    {
        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ], [
            'rejection_reason.required' => 'Alasan penolakan wajib diisi agar pelanggan mengetahui kekurangannya.',
        ]);

        $status = DB::transaction(function () use ($request, $payment, $data): string {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($lockedPayment->transaction_status === 'paid') {
                return 'already_paid';
            }

            if ($lockedPayment->transaction_status !== 'pending_verification') {
                return 'not_pending';
            }

            $lockedPayment->update([
                'transaction_status' => 'rejected',
                'rejection_reason' => $data['rejection_reason'],
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
            ]);

            if ($lockedPayment->order_id) {
                $order = $lockedPayment->order()->lockForUpdate()->first();
                if ($order) {
                    $order->update([
                        'payment_status' => 'rejected',
                        'order_status' => 'dibatalkan',
                    ]);

                    // Kembalikan stok produk jika belum dikembalikan
                    if (! $order->stock_returned_at) {
                        foreach ($order->items as $item) {
                            if ($item->product_id) {
                                \App\Models\Product::whereKey($item->product_id)->increment('stock', $item->quantity);
                            }
                        }
                        foreach ($order->customFruits as $orderFruit) {
                            if ($orderFruit->id_buah) {
                                \App\Models\Fruit::whereKey($orderFruit->id_buah)->increment('stok_porsi', $orderFruit->jumlah_porsi);
                            }
                        }
                        $order->update(['stock_returned_at' => now()]);
                    }

                    OrderStatusHistory::create([
                        'order_id' => $order->id,
                        'status' => 'dibatalkan',
                        'description' => 'Pesanan dibatalkan karena bukti pembayaran ditolak: ' . $data['rejection_reason'],
                    ]);
                }
            }

            if ($lockedPayment->voucher_id) {
                $voucher = $lockedPayment->voucher()->lockForUpdate()->first();
                if ($voucher) {
                    $voucher->update([
                        'status' => Voucher::STATUS_WAITING_PAYMENT,
                    ]);
                }
            }

            PaymentVerificationLog::create([
                'payment_id' => $lockedPayment->id,
                'order_id' => $lockedPayment->order_id,
                'voucher_id' => $lockedPayment->voucher_id,
                'admin_id' => $request->user()->id,
                'action' => 'rejected',
                'reason' => $data['rejection_reason'],
                'ip_address' => $request->ip(),
            ]);

            return 'rejected';
        });

        return match ($status) {
            'already_paid' => back()->with('error', 'Pembayaran yang sudah disetujui tidak dapat ditolak.'),
            'not_pending' => back()->with('error', 'Pembayaran ini tidak dalam status menunggu verifikasi.'),
            default => back()->with('success', 'Pembayaran telah ditolak dan pesanan langsung dibatalkan.'),
        };
    }
}
