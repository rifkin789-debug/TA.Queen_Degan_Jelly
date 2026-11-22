@extends('layouts.app')

@section('title', 'Checkout Pengiriman | Queen Degan Jelly')

@section('content')
<div class="container py-4" style="max-width: 720px;">
    <div class="mb-4">
        <h1 class="h3 fw-bold text-dark mb-1">Informasi Pengiriman</h1>
        <p class="text-muted small">Lengkapi alamat pengiriman dan nomor WhatsApp untuk pengantaran pesanan.</p>
    </div>

    <div class="qdj-card p-4">
        <form method="POST" action="{{ route('checkout.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark" for="customer_name">Nama Penerima</label>
                <input class="form-control"
                       id="customer_name"
                       name="customer_name"
                       value="{{ old('customer_name', auth()->user()->name) }}"
                       placeholder="Nama lengkap Anda"
                       required>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark" for="phone">Nomor WhatsApp Aktif</label>
                <input class="form-control"
                       id="phone"
                       name="phone"
                       type="tel"
                       value="{{ old('phone') }}"
                       placeholder="08xxxxxxxxxx"
                       required>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark" for="address">Alamat Lengkap Pengiriman</label>
                <textarea class="form-control"
                          id="address"
                          name="address"
                          rows="3"
                          placeholder="Jalan, nomor rumah, RT/RW, kelurahan, kecamatan, patokan lokasi..."
                          required>{{ old('address') }}</textarea>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-semibold text-dark" for="notes">Catatan Pesanan (Opsional)</label>
                <textarea class="form-control"
                          id="notes"
                          name="notes"
                          rows="2"
                          placeholder="Contoh: Titipkan di pos satpam bila tidak ada orang.">{{ old('notes') }}</textarea>
            </div>

            <div class="p-3 rounded-4 mb-4" style="background-color: var(--qdj-mint); border: 1px solid rgba(19, 75, 53, 0.15);">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <span class="small text-muted">
                        🛵 Pengiriman langsung area Muncar dan mitra Banyuwangi.
                    </span>
                    <span class="badge {{ ($shippingCost ?? 0) === 0 ? 'bg-success' : 'badge-qdj-available' }} px-3 py-2">
                        @if(($shippingCost ?? 0) === 0)
                            🎉 Promo Gratis Ongkir
                        @else
                            Ongkir: Rp{{ number_format($shippingCost ?? 10000, 0, ',', '.') }}
                        @endif
                    </span>
                </div>
                <span class="small text-muted d-block mt-2">
                    Total tagihan dan kode QRIS pembayaran akan ditampilkan pada langkah berikutnya.
                </span>
            </div>

            <div class="d-flex justify-content-between align-items-center pt-2">
                <a href="{{ route('cart.index') }}" class="btn btn-qdj-outline">
                    ← Kembali ke Keranjang
                </a>
                <button class="btn btn-qdj px-4 py-2" type="submit">
                    Lanjut ke Pembayaran →
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
