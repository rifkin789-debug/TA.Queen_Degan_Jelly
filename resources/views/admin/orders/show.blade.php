@extends('layouts.admin')

@section('title', 'Detail Pesanan ' . $order->order_code . ' | Queen Degan Jelly')

@section('admin-content')
<div class="mb-4">
    <a href="{{ route('admin.orders.index') }}" class="text-decoration-none small text-muted">
        ← Kembali ke Kelola Pesanan
    </a>
    <div class="d-flex align-items-center gap-3 mt-1">
        <h1 class="h3 fw-bold text-dark mb-0">Pesanan {{ $order->order_code }}</h1>
        @if($order->type === 'Voucher' || $order->id_voucher)
            <span class="badge-qdj-voucher">🎁 Dari Voucher</span>
        @endif
    </div>
    <p class="text-muted small">Rincian pemesanan pelanggan dan pengaturan status pengerjaan.</p>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <!-- Rincian Data Pesanan -->
        <div class="qdj-card p-4 mb-4">
            <h2 class="h5 fw-bold text-dark mb-3">Informasi Pemesan</h2>

            <div class="row g-3 small">
                <div class="col-sm-6">
                    <span class="text-muted d-block">Tipe pesanan:</span>
                    <strong class="text-dark">{{ $order->type }}</strong>
                </div>
                <div class="col-sm-6">
                    <span class="text-muted d-block">Nama:</span>
                    <strong class="text-dark">{{ $order->customer_name }}</strong>
                </div>
                <div class="col-sm-6">
                    <span class="text-muted d-block">Telepon:</span>
                    <span class="text-dark">{{ $order->phone }}</span>
                </div>
                <div class="col-sm-6">
                    <span class="text-muted d-block">Alamat:</span>
                    <span class="text-dark">{{ $order->address ?: 'Ambil sendiri' }}</span>
                </div>
                <div class="col-sm-6">
                    <span class="text-muted d-block">Pembayaran:</span>
                    <strong class="text-dark">{{ $order->payment_status }}</strong>
                </div>
                <div class="col-sm-6">
                    <span class="text-muted d-block">Total:</span>
                    <strong class="fs-6 text-dark">Rp{{ number_format($order->total, 0, ',', '.') }}</strong>
                </div>

                @if(in_array($order->type, ['Custom', 'Voucher'], true))
                    <div class="col-sm-6">
                        <span class="text-muted d-block">Jumlah batok:</span>
                        <strong class="text-dark">{{ $order->jumlah_batok }}</strong>
                    </div>
                    <div class="col-sm-6">
                        <span class="text-muted d-block">Metode:</span>
                        <span class="text-dark">{{ $order->fulfillment_method === 'ambil' ? 'Ambil sendiri' : 'Antar' }}</span>
                    </div>

                    @if($order->type === 'Voucher' && $order->voucher)
                        <div class="col-12 p-3 rounded-3" style="background-color: var(--qdj-mint);">
                            <span class="small text-muted d-block">Tautan Voucher Hadiah:</span>
                            <strong class="text-dark">{{ $order->voucher->nama_paket }} · {{ $order->voucher->kode_voucher }}</strong>
                            <div class="small text-muted mt-1">Pengirim: {{ $order->voucher->nama_pengirim }}</div>
                        </div>
                    @endif

                    <div class="col-12 mt-3 pt-3 border-top">
                        <h2 class="h5 fw-bold text-dark mb-2">Buah pilihan</h2>
                        <div class="list-group list-group-flush">
                            @forelse($order->customFruits as $orderFruit)
                                <div class="list-group-item px-0 py-1 bg-transparent border-0 d-flex justify-content-between">
                                    <span class="text-dark">{{ $orderFruit->nama_buah }} × {{ $orderFruit->jumlah_porsi }} porsi</span>
                                </div>
                            @empty
                                <p class="text-muted small mb-0">Tidak ada snapshot buah pada pesanan ini.</p>
                            @endforelse
                        </div>
                    </div>
                @else
                    <div class="col-12 mt-3 pt-3 border-top">
                        <h2 class="h5 fw-bold text-dark mb-2">Item</h2>
                        <div class="list-group list-group-flush">
                            @foreach($order->items as $item)
                                <div class="list-group-item px-0 py-1 bg-transparent border-0 d-flex justify-content-between">
                                    <span class="text-dark">{{ $item->product_name }} × {{ $item->quantity }}</span>
                                    <strong class="text-dark">Rp{{ number_format($item->subtotal, 0, ',', '.') }}</strong>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        @if($order->payment && $order->payment->transaction_status === 'pending_verification')
            <div class="qdj-card p-4 shadow-sm mb-4" style="border-top: 4px solid var(--qdj-yellow);">
                <h2 class="h5 fw-bold text-dark mb-2">Verifikasi Bukti Transfer QRIS</h2>
                <p class="small text-muted mb-3">Pelanggan sudah mengunggah bukti transfer. Periksa lalu setujui atau tolak pembayaran.</p>

                @if($order->payment->proof_path)
                    <div class="mb-3 text-center">
                        <a href="{{ route('admin.payments.proof', $order->payment) }}" target="_blank" rel="noopener">
                            <img src="{{ route('admin.payments.proof', $order->payment) }}" alt="Bukti Transfer QRIS" class="img-fluid rounded-3 border shadow-sm" style="max-height: 260px;">
                        </a>
                    </div>
                @endif

                @if($order->payment->customer_notes)
                    <div class="small bg-light p-2 px-3 rounded-3 mb-3">
                        <span class="text-muted">Catatan pelanggan:</span>
                        <strong class="text-dark d-block">{{ $order->payment->customer_notes }}</strong>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.payments.approve', $order->payment) }}" class="mb-2" onsubmit="return confirm('Setujui pembayaran QRIS ini? Status pesanan akan langsung berubah menjadi Diproses.');">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-qdj w-100 py-2">✓ Setujui Pembayaran QRIS</button>
                </form>

                <button type="button" class="btn btn-outline-danger w-100 py-2" data-bs-toggle="modal" data-bs-target="#rejectOrderPaymentModal">
                    ✕ Tolak Bukti Pembayaran
                </button>
            </div>
        @endif

        <!-- Update Status Form -->
        <div class="qdj-card p-4 shadow-sm">
            <h2 class="h5 fw-bold text-dark mb-3">Perbarui Status Pesanan</h2>

            @if($order->payment_status !== 'paid')
                <div class="alert alert-warning small rounded-3 mb-3">
                    <strong>⚠️ Status Pengiriman Terkunci:</strong> Pembayaran belum diterima. Pemrosesan dan pengiriman hanya dapat dilakukan setelah admin menyetujui pembayaran di menu <a href="{{ route('admin.payments.index') }}" class="alert-link">Pembayaran</a>.
                </div>
            @endif

            <form method="POST" action="{{ route('admin.orders.status', $order) }}">
                @csrf
                @method('PATCH')

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark">Status Pesanan</label>
                    <select class="form-select" name="order_status" required>
                        @if($order->order_status === 'menunggu_pembayaran' || $order->order_status === 'menunggu_verifikasi')
                            <option value="{{ $order->order_status }}" selected>
                                {{ $order->order_status === 'menunggu_verifikasi' ? 'Menunggu verifikasi admin' : 'Menunggu pembayaran' }}
                            </option>
                        @endif
                        @foreach([
                            'sedang_disiapkan' => 'Sedang disiapkan',
                            'dikirim' => 'Dikirim',
                            'pesanan_diterima' => 'Pesanan diterima (disetujui)',
                            'dibatalkan' => 'Dibatalkan'
                        ] as $value => $label)
                            <option value="{{ $value }}"
                                    @selected(old('order_status', $order->order_status) === $value || ($value === 'pesanan_diterima' && old('order_status', $order->order_status) === 'diterima'))
                                    @disabled($order->payment_status !== 'paid' && $value !== 'dibatalkan')>
                                {{ $label }} {{ ($order->payment_status !== 'paid' && $value !== 'dibatalkan') ? '(Terkunci - Belum Dibayar)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark">Kurir (opsional)</label>
                    <input class="form-control" name="courier" value="{{ old('courier', $order->courier) }}" placeholder="Contoh: Kurir Toko / GrabExpress">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark">Nomor resi (opsional)</label>
                    <input class="form-control" name="tracking_number" value="{{ old('tracking_number', $order->tracking_number) }}" placeholder="Nomor pelacakan">
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-semibold text-dark">Keterangan update</label>
                    <input class="form-control" name="description" placeholder="Contoh: Produk sudah diserahkan ke kurir">
                </div>

                <button class="btn btn-qdj w-100 py-2" type="submit">
                    Simpan Perubahan
                </button>
            </form>
        </div>
    </div>
</div>

@if($order->payment && $order->payment->transaction_status === 'pending_verification')
    <div class="modal fade" id="rejectOrderPaymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog text-start">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.payments.reject', $order->payment) }}">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header">
                        <h5 class="modal-title h6 fw-bold">Tolak Pembayaran QRIS</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label small fw-semibold text-dark">Alasan Penolakan</label>
                        <textarea name="rejection_reason" class="form-control" rows="3" placeholder="Contoh: Nominal transfer kurang atau bukti transfer tidak terbaca." required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-danger">Tolak Pembayaran</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection
