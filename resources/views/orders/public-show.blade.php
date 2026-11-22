@extends('layouts.app')

@section('title', 'Detail Pesanan ' . $order->order_code . ' | Queen Degan Jelly')

@section('content')
<div class="container py-4">
    <div class="mb-4">
        <div class="d-flex align-items-center gap-2 mb-1">
            <h1 class="h3 fw-bold text-dark mb-0">Detail Pesanan</h1>
            @if($order->type === 'Voucher' || $order->id_voucher)
                <span class="badge-qdj-voucher">🎁 Dari Voucher</span>
            @endif
        </div>
        <p class="text-muted small">Kode Pesanan: <strong class="text-dark">{{ $order->order_code }}</strong></p>
    </div>

    @if($order->payment_status === 'pending_verification')
        <div class="alert alert-info d-flex align-items-center gap-3 rounded-4 mb-4">
            <span class="fs-2">⏳</span>
            <div>
                <strong class="d-block">Bukti Pembayaran Sedang Diverifikasi Admin</strong>
                <span class="small">Bukti transfer QRIS Anda telah diterima dan sedang ditinjau. Halaman ini akan otomatis diperbarui setelah disetujui.</span>
            </div>
        </div>
    @elseif($order->payment_status === 'rejected')
        <div class="alert alert-danger rounded-4 mb-4">
            <strong class="d-block mb-1">⚠️ Bukti Pembayaran Ditolak Pengelola</strong>
            <p class="small mb-2"><strong>Alasan:</strong> {{ $order->payment?->rejection_reason ?? 'Bukti pembayaran tidak sesuai atau tidak terbaca.' }}</p>
            <a href="{{ $paymentUrl ?? route('payments.show', $order) }}" class="btn btn-sm btn-danger rounded-pill">
                Unggah Ulang Bukti Transfer →
            </a>
        </div>
    @elseif($order->payment_status === 'paid')
        <div class="alert alert-success d-flex align-items-center gap-3 rounded-4 mb-4">
            <span class="fs-2">✅</span>
            <div>
                <strong class="d-block">Pembayaran Berhasil Diverifikasi!</strong>
                <span class="small">Pengelola telah menyetujui pembayaran pesanan Anda. Pesanan sedang disiapkan.</span>
            </div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="qdj-card p-4 mb-4">
                <h2 class="h5 fw-bold text-dark mb-3">Ringkasan pesanan</h2>
                @if(in_array($order->type, ['Custom', 'Voucher'], true))
                    <p class="mb-2 text-muted small">{{ $order->jumlah_batok }} batok · {{ $order->fulfillment_method === 'ambil' ? 'Ambil sendiri' : 'Antar' }}</p>
                    @if($order->type === 'Voucher')
                        <div class="p-2 px-3 rounded-3 mb-3 small" style="background-color: var(--qdj-mint);">
                            Dibayar dengan voucher hadiah.
                        </div>
                    @endif
                    <div class="list-group list-group-flush mb-3">
                        @foreach($order->customFruits as $orderFruit)
                            <div class="list-group-item px-0 d-flex justify-content-between align-items-center bg-transparent py-2 border-bottom">
                                <span class="text-dark">{{ $orderFruit->nama_buah }} × {{ $orderFruit->jumlah_porsi }} porsi</span>
                                @if($order->type !== 'Voucher')
                                    <strong class="text-dark">Rp{{ number_format($orderFruit->harga_porsi * $orderFruit->jumlah_porsi, 0, ',', '.') }}</strong>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="list-group list-group-flush mb-3">
                        @foreach($order->items as $item)
                            <div class="list-group-item px-0 d-flex justify-content-between align-items-center bg-transparent py-2 border-bottom">
                                <span class="text-dark">{{ $item->product_name }} × {{ $item->quantity }}</span>
                                <strong class="text-dark">Rp{{ number_format($item->subtotal, 0, ',', '.') }}</strong>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if($order->type !== 'Voucher')
                    <div class="border-top pt-3 small">
                        <div class="d-flex justify-content-between py-1 text-muted">
                            <span>Subtotal</span>
                            <span class="text-dark">Rp{{ number_format($order->subtotal, 0, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 text-muted">
                            <span>Ongkir</span>
                            <span class="text-dark">Rp{{ number_format($order->shipping_cost, 0, ',', '.') }}</span>
                        </div>
                        @if($order->unique_code)
                            <div class="d-flex justify-content-between py-1 text-muted">
                                <span>Kode unik</span>
                                <span class="text-dark">Rp{{ number_format($order->unique_code, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        <div class="d-flex justify-content-between align-items-center pt-2 mt-2 border-top">
                            <span class="fw-bold fs-6 text-dark">Total</span>
                            <span class="fs-4 fw-bold" style="color: var(--qdj-green-dark);">Rp{{ number_format($order->total, 0, ',', '.') }}</span>
                        </div>
                    </div>
                @else
                    <p class="fw-bold text-success mt-3 mb-0">Tidak ada pembayaran tambahan.</p>
                @endif
            </div>

            <!-- Riwayat Status Timeline -->
            <div class="qdj-card p-4">
                <h2 class="h5 fw-bold text-dark mb-3">Riwayat status</h2>
                <ol class="list-group list-group-flush">
                    @forelse($order->statusHistories as $history)
                        @php
                            $historyLabels = [
                                'menunggu_pembayaran' => 'Menunggu Pembayaran',
                                'menunggu_verifikasi' => 'Menunggu Verifikasi',
                                'sedang_disiapkan' => 'Sedang Disiapkan (Diproses)',
                                'dikirim' => 'Dikirim',
                                'pesanan_diterima' => 'Pesanan Diterima (Disetujui)',
                                'diterima' => 'Pesanan Diterima (Disetujui)',
                                'dibatalkan' => 'Dibatalkan',
                            ];
                        @endphp
                        <li class="list-group-item px-0 py-2 border-bottom bg-transparent">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong class="text-dark">{{ $historyLabels[$history->status] ?? ucfirst(str_replace('_', ' ', $history->status)) }}</strong>
                                <span class="small text-muted">{{ $history->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                            <div class="small text-muted mt-1">{{ $history->description }}</div>
                        </li>
                    @empty
                        <li class="list-group-item px-0 border-0 bg-transparent text-muted small">Belum ada pembaruan status.</li>
                    @endforelse
                </ol>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="qdj-card p-4">
                <h2 class="h5 fw-bold text-dark mb-3">Status dan penerima</h2>
                <div class="mb-3">
                    <span class="small text-muted d-block">Status pembayaran:</span>
                    <span class="badge-qdj-available">{{ $order->paymentStatusLabel() }}</span>
                </div>
                <div class="mb-3">
                    <span class="small text-muted d-block">Status pesanan:</span>
                    <span class="badge rounded-pill bg-light text-dark border px-3 py-1">{{ $order->orderStatusLabel() }}</span>
                </div>
                <hr>
                <div class="small">
                    <span class="text-muted d-block">Penerima:</span>
                    <strong class="d-block text-dark">{{ $order->customer_name }}</strong>
                    <span class="text-muted d-block">{{ $order->phone }}</span>
                    <span class="text-dark d-block mt-2">{{ $order->address ?: 'Ambil sendiri' }}</span>
                </div>

                @if($paymentUrl)
                    <div class="mt-4 pt-3 border-top">
                        <a class="btn btn-qdj w-100 py-2" href="{{ $paymentUrl }}">Lanjutkan Pembayaran QRIS</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
