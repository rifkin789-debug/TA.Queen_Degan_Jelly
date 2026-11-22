@extends('layouts.admin')

@section('title', 'Dasbor Pengelola | Queen Degan Jelly')

@section('admin-content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Ringkasan Toko</h1>
        <p class="text-muted small mb-0">Pantau produk, pesanan, dan pendapatan Queen Degan Jelly.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.batches.index') }}" class="btn btn-qdj-outline">
            Catat Batch
        </a>
        <a href="{{ route('admin.produk.create') }}" class="btn btn-qdj">
            + Tambah Produk
        </a>
    </div>
</div>

<!-- 4 Kotak Ringkasan Utama -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="qdj-card h-100 p-3">
            <div class="text-muted small">Total Produk</div>
            <div class="fs-3 fw-bold text-dark my-1">{{ number_format($totalProducts) }}</div>
            <a class="small text-decoration-none" style="color: var(--qdj-green-dark);" href="{{ route('admin.produk.index') }}">Kelola produk →</a>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="qdj-card h-100 p-3">
            <div class="text-muted small">Total Pesanan</div>
            <div class="fs-3 fw-bold text-dark my-1">{{ number_format($totalOrders) }}</div>
            <a class="small text-decoration-none" style="color: var(--qdj-green-dark);" href="{{ route('admin.orders.index') }}">Lihat pesanan →</a>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="qdj-card h-100 p-3">
            <div class="text-muted small">Menunggu Pembayaran</div>
            <div class="fs-3 fw-bold text-warning my-1">{{ number_format($pendingOrders) }}</div>
            <a class="small text-decoration-none" style="color: var(--qdj-green-dark);" href="{{ route('admin.orders.index') }}">Lihat pesanan →</a>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="qdj-card h-100 p-3" style="background-color: var(--qdj-mint);">
            <div class="text-muted small">Pendapatan Total</div>
            <div class="fs-4 fw-bold text-dark my-1">Rp{{ number_format($totalRevenue, 0, ',', '.') }}</div>
            <span class="small text-muted">Katalog, Custom, dan Voucher</span>
        </div>
    </div>
</div>

<!-- 3 Kotak Rincian Pendapatan Sesuai Tipe Transaksi -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="qdj-card h-100 p-3">
            <div class="text-muted small">Pendapatan Katalog</div>
            <div class="fs-5 fw-bold text-dark my-1">Rp{{ number_format($catalogRevenue, 0, ',', '.') }}</div>
            <span class="small text-muted">Dari pemesanan produk katalog</span>
        </div>
    </div>
    <div class="col-md-4">
        <div class="qdj-card h-100 p-3">
            <div class="text-muted small">Pendapatan Custom</div>
            <div class="fs-5 fw-bold text-dark my-1">Rp{{ number_format($customRevenue, 0, ',', '.') }}</div>
            <span class="small text-muted">Dari pesanan degan jelly racikan mandiri</span>
        </div>
    </div>
    <div class="col-md-4">
        <div class="qdj-card h-100 p-3">
            <div class="text-muted small">Pendapatan Voucher</div>
            <div class="fs-5 fw-bold text-dark my-1">Rp{{ number_format($voucherRevenue, 0, ',', '.') }}</div>
            <span class="small text-muted">Voucher aktif atau dipakai; pesanan penukaran tidak dihitung lagi.</span>
        </div>
    </div>
</div>

<!-- Pesanan Terbaru -->
<div class="qdj-card p-4">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3 pb-2 border-bottom">
        <div>
            <h2 class="h5 fw-bold text-dark mb-0">Pesanan Terbaru</h2>
            <p class="text-muted small mb-0">Aktivitas pesanan terbaru di toko.</p>
        </div>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-qdj-outline">Semua pesanan →</a>
    </div>

    @forelse($latestOrders as $order)
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 border-bottom py-3">
            <div>
                <div class="fw-semibold text-dark d-flex align-items-center gap-2">
                    <span>{{ $order->order_code }}</span>
                    @if($order->type === 'Voucher' || $order->id_voucher)
                        <span class="badge-qdj-voucher">🎁 Dari Voucher</span>
                    @endif
                </div>
                <div class="text-muted small">
                    {{ $order->customer_name }} · Rp{{ number_format($order->total, 0, ',', '.') }} · {{ $order->created_at->diffForHumans() }}
                </div>
            </div>
            <a class="btn btn-sm btn-qdj-outline align-self-start align-self-sm-center" href="{{ route('admin.orders.show', $order) }}">
                Kelola pesanan
            </a>
        </div>
    @empty
        <div class="text-center text-muted py-4">Belum ada pesanan masuk.</div>
    @endforelse
</div>
@endsection
