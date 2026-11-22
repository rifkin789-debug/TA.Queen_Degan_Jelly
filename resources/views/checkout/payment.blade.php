@extends('layouts.app')

@section('title', 'Pembayaran QRIS Pesanan ' . $order->order_code . ' | Queen Degan Jelly')

@section('content')
<div class="container py-4">
    <!-- Header -->
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none" style="color: var(--qdj-green-dark);">Beranda</a></li>
                <li class="breadcrumb-item"><a href="{{ route('orders.track') }}" class="text-decoration-none" style="color: var(--qdj-green-dark);">Lihat Pesanan</a></li>
                <li class="breadcrumb-item active" aria-current="page">Pembayaran {{ $order->order_code }}</li>
            </ol>
        </nav>
        <h1 class="h3 fw-bold text-dark mb-1">Pembayaran QRIS Manual</h1>
        <p class="text-muted small">Scan QRIS, transfer sesuai total, lalu unggah bukti transfer untuk diverifikasi oleh pengelola.</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 mb-4" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Kolom Kiri: Ringkasan Pesanan & Status -->
        <div class="col-lg-5">
            <!-- Kartu Pesanan Anda -->
            <div class="qdj-card p-4 mb-4 shadow-sm">
                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                    <div>
                        <span class="text-muted small d-block">Kode Pesanan</span>
                        <strong class="fs-5 text-dark">{{ $order->order_code }}</strong>
                    </div>
                    @if($payment?->transaction_status === 'paid' || $order->payment_status === 'paid')
                        <span class="badge-qdj-fresh-very">Dibayar & Disetujui</span>
                    @elseif($payment?->transaction_status === 'pending_verification' || $order->payment_status === 'pending_verification')
                        <span class="badge-qdj-fresh">Menunggu Verifikasi</span>
                    @elseif($payment?->transaction_status === 'rejected' || $order->payment_status === 'rejected')
                        <span class="badge-qdj-expired">Bukti Ditolak</span>
                    @elseif($payment?->transaction_status === 'expired' || $order->order_status === 'dibatalkan')
                        <span class="badge-qdj-expired">Kedaluwarsa</span>
                    @else
                        <span class="badge-qdj-fresh-warn">Menunggu Pembayaran</span>
                    @endif
                </div>

                <!-- Timer Hitung Mundur -->
                @if($payment && in_array($payment->transaction_status, ['pending', 'rejected']) && $payment->expires_at && $payment->expires_at->isFuture())
                    <div class="d-flex align-items-center gap-3 p-3 rounded-4 mb-4" style="background-color: var(--qdj-yellow-bg); color: var(--qdj-yellow-text); border: 1px solid rgba(229, 168, 36, 0.3);">
                        <div style="font-size: 1.8rem;">⏳</div>
                        <div class="flex-grow-1">
                            <span class="small d-block text-uppercase fw-semibold" style="letter-spacing: 0.05em;">Batas Waktu Pembayaran</span>
                            <strong class="fs-4" id="paymentCountdown">--:--</strong>
                        </div>
                        <span class="small text-muted" id="expiryNotice">60 Menit</span>
                    </div>
                @endif

                <!-- Ringkasan Item -->
                <h2 class="h6 fw-bold mb-3 text-dark">Rincian Belanja</h2>
                <div class="list-group list-group-flush mb-3">
                    @forelse($order->items as $item)
                        <div class="list-group-item px-0 d-flex justify-content-between align-items-center bg-transparent py-2">
                            <div>
                                <span class="fw-medium text-dark">{{ $item->product_name }}</span>
                                <span class="text-muted small d-block">× {{ $item->quantity }} batok</span>
                            </div>
                            <strong class="text-dark">Rp{{ number_format($item->subtotal, 0, ',', '.') }}</strong>
                        </div>
                    @empty
                        @if($order->customFruits && $order->customFruits->count() > 0)
                            <div class="list-group-item px-0 bg-transparent py-2">
                                <span class="fw-medium text-dark">Degan Jelly Custom ({{ $order->jumlah_batok }} batok)</span>
                                <div class="small text-muted mt-1">
                                    Isian: {{ $order->customFruits->pluck('nama_buah')->implode(', ') }}
                                </div>
                            </div>
                        @endif
                    @endforelse
                </div>

                <!-- Rincian Biaya -->
                <div class="border-top pt-3 small">
                    <div class="d-flex justify-content-between py-1 text-muted">
                        <span>Subtotal Produk</span>
                        <span class="text-dark">Rp{{ number_format($order->subtotal, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 text-muted">
                        <span>Biaya Pengiriman</span>
                        <span class="text-dark">Rp{{ number_format($order->shipping_cost, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-3 mt-2 border-top">
                        <span class="fw-bold fs-6 text-dark">Total Tagihan:</span>
                        <span class="fs-4 fw-bold" style="color: var(--qdj-green-dark);">
                            Rp{{ number_format($order->total, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Kartu Alur Status -->
            <div class="qdj-card p-4 shadow-sm">
                <h2 class="h6 fw-bold mb-3 text-dark d-flex align-items-center gap-2">
                    <span>📋</span>
                    <span>Alur Status Pembayaran</span>
                </h2>
                <div class="d-flex flex-column gap-2 small">
                    <div class="d-flex align-items-center gap-2 {{ in_array($order->order_status, ['menunggu_pembayaran', 'menunggu_verifikasi', 'pesanan_diterima', 'sedang_disiapkan', 'dikirim', 'diterima']) ? 'text-success fw-semibold' : 'text-muted' }}">
                        <span>✓</span> 1. Menunggu Pembayaran Pelanggan
                    </div>
                    <div class="d-flex align-items-center gap-2 {{ in_array($order->order_status, ['menunggu_verifikasi', 'pesanan_diterima', 'sedang_disiapkan', 'dikirim', 'diterima']) ? 'text-success fw-semibold' : 'text-muted' }}">
                        <span>✓</span> 2. Bukti Diunggah & Menunggu Verifikasi
                    </div>
                    <div class="d-flex align-items-center gap-2 {{ in_array($order->order_status, ['pesanan_diterima', 'sedang_disiapkan', 'dikirim', 'diterima']) ? 'text-success fw-semibold' : 'text-muted' }}">
                        <span>✓</span> 3. Pembayaran Disetujui (Dibayar)
                    </div>
                    <div class="d-flex align-items-center gap-2 {{ in_array($order->order_status, ['sedang_disiapkan', 'dikirim', 'diterima']) ? 'text-success fw-semibold' : 'text-muted' }}">
                        <span>✓</span> 4. Pesanan Diproses & Dikirim
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Panel QRIS & Form Unggah Bukti -->
        <div class="col-lg-7">
            <!-- QRIS Panel -->
            <div class="qdj-card p-4 mb-4 shadow-sm" style="border-top: 4px solid var(--qdj-green-dark);">
                <div class="text-center pb-3 border-bottom mb-3">
                    <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1 small fw-semibold mb-2">QRIS Standar Nasional</span>
                    <h2 class="h5 fw-bold text-dark mb-1">{{ $profile->merchant_name ?? 'Queen Degan Jelly' }}</h2>
                    <p class="text-muted small mb-0">NMID: ID102026889901 • Muncar, Banyuwangi</p>
                </div>

                <!-- QRIS Image / Code Display -->
                <div class="text-center my-3">
                    <div class="p-3 bg-white d-inline-block border rounded-4 shadow-sm" style="max-width: 280px; width: 100%;">
                        @if($qrisImageUrl)
                            <img src="{{ $qrisImageUrl }}" alt="QRIS {{ $profile->merchant_name ?? 'Queen Degan Jelly' }}" class="img-fluid rounded-3" style="max-height: 250px; object-fit: contain;">
                        @else
                            <!-- Fallback QR Code Mockup jika gambar belum diunggah admin -->
                            <div class="p-3 text-center">
                                <svg width="200" height="200" viewBox="0 0 100 100" fill="none" class="img-fluid">
                                    <rect width="100" height="100" fill="#ffffff" />
                                    <!-- Top Left Finder -->
                                    <rect x="8" y="8" width="26" height="26" rx="2" fill="#134B35" />
                                    <rect x="12" y="12" width="18" height="18" rx="1" fill="#ffffff" />
                                    <rect x="16" y="16" width="10" height="10" fill="#134B35" />
                                    <!-- Top Right Finder -->
                                    <rect x="66" y="8" width="26" height="26" rx="2" fill="#134B35" />
                                    <rect x="70" y="12" width="18" height="18" rx="1" fill="#ffffff" />
                                    <rect x="74" y="16" width="10" height="10" fill="#134B35" />
                                    <!-- Bottom Left Finder -->
                                    <rect x="8" y="66" width="26" height="26" rx="2" fill="#134B35" />
                                    <rect x="12" y="70" width="18" height="18" rx="1" fill="#ffffff" />
                                    <rect x="16" y="74" width="10" height="10" fill="#134B35" />
                                    <!-- Data Points -->
                                    <rect x="40" y="12" width="6" height="6" fill="#134B35" />
                                    <rect x="50" y="12" width="6" height="6" fill="#134B35" />
                                    <rect x="40" y="24" width="6" height="6" fill="#134B35" />
                                    <rect x="50" y="28" width="6" height="6" fill="#134B35" />
                                    <rect x="14" y="44" width="6" height="6" fill="#134B35" />
                                    <rect x="24" y="44" width="6" height="6" fill="#134B35" />
                                    <rect x="34" y="44" width="6" height="6" fill="#134B35" />
                                    <rect x="44" y="44" width="12" height="12" fill="#E5A824" />
                                    <rect x="64" y="44" width="6" height="6" fill="#134B35" />
                                    <rect x="74" y="44" width="6" height="6" fill="#134B35" />
                                    <rect x="84" y="44" width="6" height="6" fill="#134B35" />
                                    <rect x="44" y="64" width="6" height="6" fill="#134B35" />
                                    <rect x="54" y="64" width="6" height="6" fill="#134B35" />
                                    <rect x="64" y="64" width="6" height="6" fill="#134B35" />
                                    <rect x="74" y="74" width="6" height="6" fill="#134B35" />
                                    <rect x="84" y="84" width="6" height="6" fill="#134B35" />
                                </svg>
                                <span class="d-block small text-muted mt-2">QRIS Statis Toko</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Nominal Transfer & Salin -->
                <div class="bg-light p-3 rounded-4 mb-4 text-center">
                    <span class="small text-muted d-block mb-1">Nominal yang harus dibayar:</span>
                    <div class="d-flex align-items-center justify-content-center gap-2">
                        <strong class="fs-3" style="color: var(--qdj-green-dark);">
                            Rp {{ number_format($order->total, 0, ',', '.') }}
                        </strong>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-1" onclick="navigator.clipboard.writeText('{{ $order->total }}'); alert('Nominal disalin: {{ $order->total }}');">
                            📋 Salin
                        </button>
                    </div>
                    <span class="small text-danger d-block mt-1" style="font-size: 0.8rem;">
                        * Pastikan mentransfer tepat hingga digit terakhir agar verifikasi cepat.
                    </span>
                </div>

                <!-- Petunjuk Pembayaran -->
                <div class="small text-muted mb-2">
                    <strong class="text-dark d-block mb-2">Petunjuk Pembayaran:</strong>
                    <ol class="ps-3 mb-0 d-flex flex-column gap-1">
                        <li>Buka aplikasi m-Banking (BCA, Mandiri, BRI, BNI) atau e-Wallet (GoPay, OVO, ShopeePay, DANA).</li>
                        <li>Pilih menu <strong>Scan / Bayar QRIS</strong>.</li>
                        <li>Arahkan kamera ke kode QRIS di atas.</li>
                        <li>Masukkan nominal transfer tepat <strong>Rp {{ number_format($order->total, 0, ',', '.') }}</strong>.</li>
                        <li>Selesaikan transfer dan <strong>simpan bukti pembayaran</strong> (screenshot/struk).</li>
                        <li>Unggah bukti pembayaran pada formulir di bawah ini.</li>
                    </ol>
                </div>
            </div>

            <!-- Form / Status Unggah Bukti Pembayaran -->
            <div class="qdj-card p-4 shadow-sm">
                <h3 class="h6 fw-bold text-dark mb-3">Unggah Bukti Transfer</h3>

                @if($payment?->transaction_status === 'paid' || $order->payment_status === 'paid')
                    <div class="alert alert-success d-flex align-items-center gap-3 rounded-4 mb-0">
                        <span class="fs-2">🎉</span>
                        <div>
                            <strong class="d-block">Pembayaran Berhasil Diverifikasi!</strong>
                            <span class="small">Pengelola telah menyetujui pembayaran Anda. Pesanan sedang disiapkan untuk pengiriman/pengambilan.</span>
                        </div>
                    </div>
                @elseif($payment?->transaction_status === 'pending_verification')
                    <div class="alert alert-info d-flex align-items-center gap-3 rounded-4 mb-3">
                        <span class="fs-2">⏳</span>
                        <div>
                            <strong class="d-block">Bukti Pembayaran Sedang Diverifikasi</strong>
                            <span class="small">Bukti transfer Anda telah diterima pada {{ $payment->proof_uploaded_at?->translatedFormat('d M Y, H:i') ?? 'saat ini' }}. Pengelola akan segera memeriksa dan memvalidasi pembayaran Anda.</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between p-3 rounded-3 bg-light border small">
                        <span>Berkas bukti telah terunggah secara aman.</span>
                        <a href="{{ route('payments.proof', $order) }}" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill">
                            👁️ Lihat Bukti
                        </a>
                    </div>
                @elseif($payment?->transaction_status === 'rejected')
                    <div class="alert alert-danger rounded-4 mb-3">
                        <strong class="d-block mb-1">⚠️ Bukti Pembayaran Ditolak Pengelola</strong>
                        <p class="small mb-2"><strong>Alasan penolakan:</strong> {{ $payment->rejection_reason ?? 'Bukti transfer tidak sesuai nominal atau tidak terbaca.' }}</p>
                        <p class="small mb-0 text-muted">Silakan unggah ulang bukti transfer yang benar di bawah ini.</p>
                    </div>

                    <!-- Form Unggah Ulang Bukti -->
                    <form method="POST" action="{{ route('payments.proof.store', $order) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark">Pilih Berkas Bukti Transfer (JPG / PNG, Maks. 2 MB)</label>
                            <input type="file" name="payment_proof" class="form-control" accept="image/jpeg,image/png" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark">Catatan Tambahan (Opsional)</label>
                            <textarea name="customer_notes" rows="2" class="form-control" placeholder="Contoh: Transfer atas nama Budi via BCA"></textarea>
                        </div>
                        <button type="submit" class="btn btn-qdj w-100 py-2 fs-6">
                            Unggah Ulang Bukti Pembayaran
                        </button>
                    </form>
                @elseif($payment?->transaction_status === 'expired' || $order->order_status === 'dibatalkan')
                    <div class="alert alert-secondary rounded-4 mb-0">
                        <strong>Pesanan Telah Kedaluwarsa</strong>
                        <p class="small mb-0">Batas waktu pembayaran 60 menit telah terlewati. Stok telah dikembalikan ke katalog. Silakan buat pesanan baru jika ingin membeli.</p>
                    </div>
                @else
                    <!-- Form Normal Unggah Bukti -->
                    <form method="POST" action="{{ route('payments.proof.store', $order) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark">Pilih Berkas Bukti Transfer (JPG / PNG, Maks. 2 MB)</label>
                            <input type="file" name="payment_proof" class="form-control" accept="image/jpeg,image/png" required>
                            <div class="form-text small">Pastikan foto struk / tangkapan layar jelas menampilkan nominal, tanggal, dan nomor referensi.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark">Catatan Tambahan (Opsional)</label>
                            <textarea name="customer_notes" rows="2" class="form-control" placeholder="Contoh: Transfer atas nama Siti via GoPay"></textarea>
                        </div>
                        <button type="submit" class="btn btn-qdj w-100 py-2 fs-6">
                            Kirim Bukti Pembayaran
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
@if($payment && $payment->expires_at && $payment->expires_at->isFuture())
<script>
    (function() {
        const expiresAt = new Date("{{ $payment->expires_at->toIso8601String() }}").getTime();
        const countdownEl = document.getElementById('paymentCountdown');
        const noticeEl = document.getElementById('expiryNotice');

        function updateTimer() {
            const now = new Date().getTime();
            const distance = expiresAt - now;

            if (distance <= 0) {
                if (countdownEl) countdownEl.innerText = "00:00";
                if (noticeEl) noticeEl.innerText = "Waktu Habis";
                clearInterval(interval);
                window.location.reload();
                return;
            }

            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            const minStr = minutes < 10 ? '0' + minutes : minutes;
            const secStr = seconds < 10 ? '0' + seconds : seconds;

            if (countdownEl) countdownEl.innerText = minStr + ':' + secStr;
            if (noticeEl) noticeEl.innerText = minStr + ' Menit Tersisa';
        }

        updateTimer();
        const interval = setInterval(updateTimer, 1000);
    })();
</script>
@endif
@endpush
@endsection
