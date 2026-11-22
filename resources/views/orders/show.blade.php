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
                <span class="small">Bukti transfer QRIS Anda telah diterima dan sedang ditinjau oleh pengelola.</span>
            </div>
        </div>
    @elseif($order->payment_status === 'rejected' || $order->order_status === 'dibatalkan')
        <div class="alert alert-danger rounded-4 mb-4">
            <strong class="d-block mb-1">✕ Pesanan Dibatalkan</strong>
            @if($order->payment?->rejection_reason)
                <p class="small mb-0"><strong>Alasan penolakan bukti transfer:</strong> {{ $order->payment->rejection_reason }}</p>
            @else
                <p class="small mb-0">Pesanan ini telah dibatalkan.</p>
            @endif
        </div>
    @elseif($order->payment_status === 'paid')
        <div class="alert alert-success d-flex align-items-center gap-3 rounded-4 mb-4">
            <span class="fs-2">✅</span>
            <div>
                <strong class="d-block">Pembayaran Disetujui — {{ $order->orderStatusLabel() }}</strong>
                <span class="small">Pembayaran QRIS Anda telah diverifikasi oleh admin.</span>
            </div>
        </div>
    @elseif($order->payment_status === 'pending')
        <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-2 rounded-4 mb-4">
            <span class="small">Pesanan ini menunggu pembayaran QRIS.</span>
            <a href="{{ route('payments.show', $order) }}" class="btn btn-sm btn-qdj rounded-pill">Bayar via QRIS →</a>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="qdj-card p-4 mb-4">
                <h2 class="h5 fw-bold text-dark mb-3">Produk</h2>
                <div class="list-group list-group-flush mb-3">
                    @forelse($order->items as $item)
                        <div class="list-group-item px-0 d-flex justify-content-between align-items-center bg-transparent py-2 border-bottom">
                            <div>
                                <span class="fw-medium text-dark">{{ $item->product_name }}</span>
                                <span class="small text-muted d-block">× {{ $item->quantity }} batok</span>
                            </div>
                            <strong class="text-dark">Rp{{ number_format($item->subtotal, 0, ',', '.') }}</strong>
                        </div>
                    @empty
                        @if($order->type === 'Custom' || $order->customFruits->isNotEmpty())
                            <div class="list-group-item px-0 py-2 border-bottom bg-transparent">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="fw-medium text-dark">Custom Degan Jelly</span>
                                        <span class="small text-muted d-block">Jumlah: {{ $order->jumlah_batok ?? 1 }} batok kelapa</span>
                                    </div>
                                    <strong class="text-dark">Rp{{ number_format($order->subtotal, 0, ',', '.') }}</strong>
                                </div>
                                <div class="mt-2 d-flex flex-wrap gap-1">
                                    @foreach($order->customFruits as $of)
                                        <span class="badge rounded-pill bg-light text-dark border small px-2 py-1">
                                            {{ $of->nama_buah }} ({{ $of->jumlah_porsi }}x)
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforelse
                </div>

                <div class="border-top pt-3 small">
                    <div class="d-flex justify-content-between py-1 text-muted">
                        <span>Subtotal</span>
                        <span class="text-dark">Rp{{ number_format($order->subtotal, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 text-muted">
                        <span>Ongkir</span>
                        <span class="text-dark">Rp{{ number_format($order->shipping_cost, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-2 mt-2 border-top">
                        <span class="fw-bold fs-6 text-dark">Total</span>
                        <span class="fs-4 fw-bold" style="color: var(--qdj-green-dark);">
                            Rp{{ number_format($order->total, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Tracking Status -->
            <div class="qdj-card p-4">
                <h2 class="h5 fw-bold text-dark mb-3">Tracking Status Pesanan</h2>
                <ol class="list-group list-group-flush mb-3">
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

                @if($order->courier)
                    <p class="mt-2 mb-1 small text-muted">Kurir: <strong class="text-dark">{{ $order->courier }}</strong></p>
                @endif
                @if($order->tracking_number)
                    <p class="small text-muted mb-0">Nomor resi: <strong class="text-dark">{{ $order->tracking_number }}</strong></p>
                @endif
            </div>
        </div>

        <div class="col-lg-5">
            <div class="qdj-card p-4">
                <h2 class="h5 fw-bold text-dark mb-3">Informasi Pembayaran</h2>
                <div class="mb-3">
                    <span class="small text-muted d-block">Status Pesanan:</span>
                    <span class="{{ $order->orderStatusBadgeClass() }}">{{ $order->orderStatusIcon() }} {{ $order->orderStatusLabel() }}</span>
                </div>
                <div class="mb-3">
                    <span class="small text-muted d-block">Status Pembayaran:</span>
                    <strong class="text-dark">{{ $order->paymentStatusLabel() }}</strong>
                </div>
                <div class="mb-3">
                    <span class="small text-muted d-block">Metode:</span>
                    <span class="text-dark">{{ $order->payment?->provider === 'qris_static' ? 'QRIS Manual' : ($order->payment?->payment_type ?? 'QRIS Manual') }}</span>
                </div>

                <hr>

                <h2 class="h5 fw-bold text-dark mb-3">Pengiriman</h2>
                <div class="small">
                    <strong class="d-block text-dark">{{ $order->customer_name }}</strong>
                    <span class="text-muted d-block">{{ $order->phone }}</span>
                    <p class="text-dark mt-2 mb-0">{{ $order->address }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
