@extends('layouts.app')

@section('title', 'Lihat Pesanan | Queen Degan Jelly')

@section('content')
<div class="container py-4 py-md-5" style="max-width: 960px;">
    <!-- Page Header -->
    <div class="mb-4">
        <span class="badge-qdj-available mb-2">📦 Riwayat & Pelacakan Pesanan</span>
        <h1 class="h2 fw-bold text-dark mb-1" style="font-family: var(--qdj-font-heading);">Lihat Pesanan</h1>
        <p class="text-muted small mb-0">Pantau produk yang telah Anda beli dan lacak perkembangan status pengerjaan degan jelly Anda secara real-time.</p>
    </div>

    <!-- Status Filter Tabs -->
    <div class="qdj-status-tabs mb-4">
        <a href="{{ route('orders.index') }}" class="qdj-status-tab {{ (!request()->filled('status') || request('status') === 'all') ? 'active' : '' }}">
            <span>Semua Pesanan</span>
            <span class="tab-count">{{ $counts['all'] ?? 0 }}</span>
        </a>
        <a href="{{ route('orders.index', ['status' => 'menunggu_pembayaran']) }}" class="qdj-status-tab {{ request('status') === 'menunggu_pembayaran' ? 'active' : '' }}">
            <span>⏳ Menunggu Pembayaran</span>
            <span class="tab-count">{{ $counts['menunggu_pembayaran'] ?? 0 }}</span>
        </a>
        <a href="{{ route('orders.index', ['status' => 'sedang_disiapkan']) }}" class="qdj-status-tab {{ request('status') === 'sedang_disiapkan' ? 'active' : '' }}">
            <span>🥣 Sedang Disiapkan</span>
            <span class="tab-count">{{ $counts['sedang_disiapkan'] ?? 0 }}</span>
        </a>
        <a href="{{ route('orders.index', ['status' => 'dikirim']) }}" class="qdj-status-tab {{ request('status') === 'dikirim' ? 'active' : '' }}">
            <span>🛵 Dikirim</span>
            <span class="tab-count">{{ $counts['dikirim'] ?? 0 }}</span>
        </a>
        <a href="{{ route('orders.index', ['status' => 'pesanan_diterima']) }}" class="qdj-status-tab {{ request('status') === 'pesanan_diterima' ? 'active' : '' }}">
            <span>✅ Pesanan Diterima (Disetujui)</span>
            <span class="tab-count">{{ $counts['pesanan_diterima'] ?? 0 }}</span>
        </a>
        <a href="{{ route('orders.index', ['status' => 'dibatalkan']) }}" class="qdj-status-tab {{ request('status') === 'dibatalkan' ? 'active' : '' }}">
            <span>✕ Dibatalkan</span>
            <span class="tab-count">{{ $counts['dibatalkan'] ?? 0 }}</span>
        </a>
    </div>

    <!-- Order Cards List -->
    <div class="d-flex flex-column gap-3">
        @forelse($orders as $order)
            <div class="qdj-card p-3 p-md-4 shadow-sm">
                <!-- Card Header -->
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pb-3 border-bottom">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <strong class="text-dark fs-6">{{ $order->order_code }}</strong>
                        <span class="text-muted small">· {{ $order->created_at->format('d M Y, H:i') }}</span>
                        @if($order->type === 'Voucher' || $order->id_voucher)
                            <span class="badge-qdj-voucher">🎁 Dari Voucher</span>
                        @endif
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="{{ $order->orderStatusBadgeClass() }}">
                            {{ $order->orderStatusIcon() }} {{ $order->orderStatusLabel() }}
                        </span>
                    </div>
                </div>

                <!-- Card Body: Purchased Products -->
                <div class="py-3">
                    @if($order->items->isNotEmpty())
                        <div class="d-flex flex-column gap-3">
                            @foreach($order->items as $item)
                                @php
                                    $thumb = $item->product?->image 
                                        ? asset('storage/' . $item->product->image) 
                                        : asset('images/beranda-hero.jpg');
                                @endphp
                                <div class="d-flex align-items-center gap-3">
                                    <img src="{{ $thumb }}" alt="{{ $item->product_name }}" class="order-product-thumb" onerror="this.src='{{ asset('images/beranda-hero.jpg') }}'">
                                    <div class="flex-grow-1 min-w-0">
                                        <h3 class="h6 fw-bold text-dark mb-1 text-truncate">{{ $item->product_name }}</h3>
                                        <div class="small text-muted d-flex flex-wrap align-items-center gap-2">
                                            <span>Jumlah: <strong>{{ $item->quantity }} batok</strong></span>
                                            <span>·</span>
                                            <span>Rp{{ number_format($item->price, 0, ',', '.') }}/batok</span>
                                        </div>
                                    </div>
                                    <div class="text-end ps-2">
                                        <strong class="text-dark fs-6">Rp{{ number_format($item->subtotal, 0, ',', '.') }}</strong>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @elseif($order->type === 'Custom' || $order->customFruits->isNotEmpty())
                        <div class="d-flex align-items-center gap-3">
                            <img src="{{ asset('images/beranda-hero.jpg') }}" alt="Custom Degan Jelly" class="order-product-thumb">
                            <div class="flex-grow-1 min-w-0">
                                <h3 class="h6 fw-bold text-dark mb-1">Custom Degan Jelly (Racikan Buah Pilihan)</h3>
                                <div class="small text-muted mb-1">
                                    Jumlah: <strong>{{ $order->jumlah_batok ?? 1 }} batok kelapa</strong>
                                </div>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($order->customFruits as $of)
                                        <span class="badge rounded-pill bg-light text-dark border small px-2 py-1">
                                            {{ $of->nama_buah }} ({{ $of->jumlah_porsi }}x)
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                            <div class="text-end ps-2">
                                <strong class="text-dark fs-6">Rp{{ number_format($order->subtotal, 0, ',', '.') }}</strong>
                            </div>
                        </div>
                    @else
                        <div class="small text-muted py-2">
                            Produk Degan Jelly Segar Queen Degan Jelly
                        </div>
                    @endif

                    <!-- Shipping / Fulfillment Info -->
                    <div class="mt-3 pt-2 d-flex flex-wrap align-items-center gap-2 small text-muted">
                        <span class="badge bg-light text-dark border">
                            {{ $order->fulfillment_method === 'delivery' ? '🛵 Pengantaran ke Alamat' : '🏪 Ambil Sendiri di Dapur' }}
                        </span>
                        @if($order->courier)
                            <span>Kurir: <strong class="text-dark">{{ $order->courier }}</strong></span>
                        @endif
                        @if($order->tracking_number)
                            <span>· Resi: <strong class="text-dark">{{ $order->tracking_number }}</strong></span>
                        @endif
                    </div>
                </div>

                <!-- Card Footer: Total & Actions -->
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 pt-3 border-top">
                    <div>
                        <span class="small text-muted d-block">
                            Total Pembelian ({{ $order->jumlah_batok ?? ($order->items->sum('quantity') ?: 1) }} Batok):
                        </span>
                        <strong class="fs-5" style="color: var(--qdj-brown-dark);">
                            Rp{{ number_format($order->total, 0, ',', '.') }}
                        </strong>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2">
                        @if($order->payment_status === 'pending' || $order->payment_status === 'rejected')
                            <a href="{{ route('payments.show', $order) }}" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-2">
                                Bayar via QRIS →
                            </a>
                        @endif
                        <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-qdj rounded-pill px-3 py-2">
                            Lihat Detail & Lacak →
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="qdj-card text-center py-5 px-4 shadow-sm">
                <div class="mb-3 fs-1">🥥</div>
                <h3 class="h5 fw-bold text-dark mb-2">Belum Ada Pesanan Ditemukan</h3>
                @if(request()->filled('status') && request('status') !== 'all')
                    <p class="text-muted small mb-4">Tidak ada pesanan dengan status "{{ ucfirst(str_replace('_', ' ', request('status'))) }}".</p>
                    <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-4 py-2 me-2">
                        Tampilkan Semua Pesanan
                    </a>
                @else
                    <p class="text-muted small mb-4">Anda belum memiliki produk yang dibeli. Silakan jelajahi katalog dan nikmati kesegaran alami degan jelly kelapa muda kami.</p>
                @endif
                <a href="{{ route('products.index') }}" class="btn btn-sm btn-qdj rounded-pill px-4 py-2">
                    Jelajahi Katalog Produk →
                </a>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($orders->hasPages())
        <div class="mt-4 d-flex justify-content-center">
            {{ $orders->links() }}
        </div>
    @endif
</div>
@endsection
