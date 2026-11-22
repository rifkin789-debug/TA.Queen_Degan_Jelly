@extends('layouts.app')

@section('title', 'Pembayaran QRIS | Queen Degan Jelly')

@section('content')
<div class="container py-4">
    <div class="row g-4 justify-content-center">
        <!-- Kolom Kiri: Rincian Pesanan / Voucher -->
        <div class="col-lg-6">
            <div class="qdj-card p-4 mb-4">
                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                    <h1 class="h5 fw-bold text-dark mb-0">{{ $voucher ? 'Pembayaran Voucher Hadiah' : 'Pembayaran Pesanan Custom' }}</h1>
                    @if($payment?->transaction_status === 'paid')
                        <span class="badge-qdj-available">Sudah Dibayar</span>
                    @elseif($payment?->transaction_status === 'pending_verification')
                        <span class="badge-qdj-fresh">Menunggu Verifikasi</span>
                    @elseif($payment?->transaction_status === 'rejected')
                        <span class="badge-qdj-out">Bukti Ditolak</span>
                    @elseif($payment?->transaction_status === 'expired')
                        <span class="badge-qdj-out">Kedaluwarsa</span>
                    @else
                        <span class="badge-qdj-fresh-warn">Menunggu Pembayaran</span>
                    @endif
                </div>

                @if($voucher)
                    <div class="mb-3">
                        <span class="small text-muted d-block">Paket Voucher</span>
                        <strong class="fs-6 text-dark">{{ $voucher->nama_paket }}</strong>
                    </div>
                    <div class="mb-3">
                        <span class="small text-muted d-block">Nama Pengirim</span>
                        <span class="text-dark">{{ $voucher->nama_pengirim }}</span>
                    </div>
                    <div class="mb-3">
                        <span class="small text-muted d-block">Status Voucher</span>
                        <span class="badge-qdj-available">{{ $voucher->status }}</span>
                    </div>
                    @if($voucher->kode_voucher)
                        <div class="mb-3">
                            <span class="small text-muted d-block">Kode Voucher</span>
                            <code class="fw-bold fs-6 text-dark bg-light px-2 py-1 rounded">{{ $voucher->kode_voucher }}</code>
                            @if($voucher->status !== \App\Models\Voucher::STATUS_ACTIVE)
                                <span class="d-block small text-muted mt-1">Kode akan aktif otomatis setelah pembayaran disetujui admin.</span>
                            @endif
                        </div>
                    @endif

                    <div class="border-top pt-3 small">
                        <div class="d-flex justify-content-between py-1 text-muted">
                            <span>Harga paket (termasuk ongkir area mitra)</span>
                            <span class="text-dark">Rp{{ number_format($voucher->harga, 0, ',', '.') }}</span>
                        </div>
                        @if($payment && $payment->unique_code)
                            <div class="d-flex justify-content-between py-1 text-muted">
                                <span>Kode unik</span>
                                <span class="text-dark">Rp{{ number_format($payment->unique_code, 0, ',', '.') }}</span>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="mb-3">
                        <span class="small text-muted d-block">Kode Pesanan</span>
                        <strong class="fs-5 text-dark">{{ $order->order_code }}</strong>
                    </div>
                    <div class="mb-3">
                        <span class="small text-muted d-block">Status Pembayaran</span>
                        <span class="badge-qdj-fresh-warn">{{ str_replace('_', ' ', ucfirst($order->payment_status)) }}</span>
                    </div>

                    @if($order->customFruits && $order->customFruits->count() > 0)
                        <h2 class="h6 fw-bold mt-3 mb-2 text-dark">Buah pilihan</h2>
                        <div class="list-group list-group-flush mb-3">
                            @foreach($order->customFruits as $orderFruit)
                                <div class="list-group-item px-0 d-flex justify-content-between align-items-center bg-transparent py-2 border-bottom">
                                    <span class="text-dark small">{{ $orderFruit->nama_buah }} × {{ $orderFruit->jumlah_porsi }} porsi</span>
                                    <strong class="text-dark small">Rp{{ number_format($orderFruit->harga_porsi * $orderFruit->jumlah_porsi, 0, ',', '.') }}</strong>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="border-top pt-3 small">
                        <div class="d-flex justify-content-between py-1 text-muted">
                            <span>Subtotal {{ $order->jumlah_batok }} batok</span>
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
                    </div>
                @endif

                <div class="d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                    <span class="fw-bold fs-6 text-dark">Total bayar</span>
                    <span class="fs-4 fw-bold" style="color: var(--qdj-green-dark);">
                        Rp{{ number_format($voucher ? $payment->gross_amount : $order->total, 0, ',', '.') }}
                    </span>
                </div>
            </div>

            <!-- Kartu Status Pembayaran Otomatis -->
            <div class="qdj-card p-4">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span style="font-size: 1.2rem;">⚡</span>
                    <h2 class="h6 fw-bold mb-0 text-dark">Status Pembayaran Otomatis</h2>
                </div>
                <p class="small text-muted mb-0">
                    Sistem mendeteksi bukti pembayaran dan kode unik verifikasi. Jika masa berlaku pembayaran lewat, sistem otomatis membatalkan transaksi dan mengembalikan stok.
                </p>
            </div>
        </div>

        <!-- Kolom Kanan: Panel QRIS & Unggah Bukti -->
        <div class="col-lg-6">
            <div class="midtrans-panel">
                <div class="midtrans-header d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small text-white-50 text-uppercase" style="font-size: 0.72rem;">Instan Pembayaran</div>
                        <h2 class="h5 fw-bold mb-0 text-white">QRIS Mitra Queen Degan Jelly</h2>
                    </div>
                    <div class="text-end">
                        <span class="small text-white-50 d-block" style="font-size: 0.72rem;">Total Tagihan</span>
                        <strong class="fs-5 text-white">Rp{{ number_format($voucher ? $payment->gross_amount : $order->total, 0, ',', '.') }}</strong>
                    </div>
                </div>

                <div class="p-4">
                    @if(in_array($payment?->transaction_status, ['pending', 'rejected'], true))
                        @if($payment->transaction_status === 'rejected')
                            <div class="alert alert-danger rounded-4 mb-3">
                                <strong class="d-block mb-1">⚠️ Bukti Pembayaran Ditolak Admin</strong>
                                <p class="small mb-0"><strong>Alasan:</strong> {{ $payment->rejection_reason ?? 'Bukti pembayaran tidak sesuai atau tidak terbaca.' }}</p>
                            </div>
                        @endif

                        @if($qrisImageUrl)
                            <div class="text-center my-3">
                                <img class="img-fluid rounded-4 shadow-sm" src="{{ $qrisImageUrl }}" alt="Kode QRIS pembayaran mitra" style="max-height: 280px;">
                            </div>
                        @else
                            <div class="text-center my-3 p-4 bg-light rounded-4">
                                <span class="badge-qdj-available mb-2">QRIS Standar Indonesia</span>
                                <div class="my-2">
                                    <svg width="160" height="160" viewBox="0 0 100 100" fill="none">
                                        <rect width="100" height="100" fill="#ffffff" rx="8" />
                                        <rect x="10" y="10" width="24" height="24" rx="2" fill="#134B35" />
                                        <rect x="14" y="14" width="16" height="16" rx="1" fill="#ffffff" />
                                        <rect x="18" y="18" width="8" height="8" fill="#134B35" />
                                        <rect x="66" y="10" width="24" height="24" rx="2" fill="#134B35" />
                                        <rect x="70" y="14" width="16" height="16" rx="1" fill="#ffffff" />
                                        <rect x="74" y="18" width="8" height="8" fill="#134B35" />
                                        <rect x="10" y="66" width="24" height="24" rx="2" fill="#134B35" />
                                        <rect x="14" y="70" width="16" height="16" rx="1" fill="#ffffff" />
                                        <rect x="18" y="74" width="8" height="8" fill="#134B35" />
                                        <circle cx="50" cy="50" r="8" fill="#E5A824" />
                                    </svg>
                                </div>
                                <p class="small text-muted mb-0">Kode QRIS pembayaran mitra</p>
                            </div>
                        @endif

                        @if($payment->expires_at)
                            <p class="text-center text-muted small">
                                Bayar tepat sesuai total di atas sebelum <strong>{{ $payment->expires_at->format('d/m/Y H:i') }}</strong>.
                            </p>
                        @endif

                        @if($proofUploadUrl)
                            <form method="POST" action="{{ $proofUploadUrl }}" enctype="multipart/form-data" class="mt-4 pt-3 border-top">
                                @csrf
                                <label class="form-label small fw-semibold text-dark" for="payment_proof">
                                    Unggah bukti pembayaran (JPG/PNG, maksimal 2 MB)
                                </label>
                                <input class="form-control mb-3" id="payment_proof" name="payment_proof" type="file" accept=".jpg,.jpeg,.png,image/jpeg,image/png" required>
                                <button class="btn btn-qdj w-100 py-2" type="submit">Kirim Bukti Pembayaran</button>
                            </form>
                        @endif
                    @elseif($payment?->transaction_status === 'pending_verification')
                        <div class="alert alert-info rounded-4">
                            Bukti pembayaran sudah diterima dan sedang menunggu verifikasi admin.
                        </div>
                    @elseif($payment?->transaction_status === 'expired')
                        <div class="alert alert-danger rounded-4">
                            Batas pembayaran dua jam telah lewat.
                            {{ $voucher ? 'Voucher dibatalkan.' : 'Pesanan dibatalkan dan stok buah dikembalikan.' }}
                        </div>
                    @elseif($payment?->transaction_status === 'paid')
                        <div class="alert alert-success rounded-4">
                            Pembayaran telah diverifikasi.
                            @if($voucher && $voucher->kode_voucher)
                                <div class="mt-2">
                                    Kode voucher: <strong>{{ $voucher->kode_voucher }}</strong>
                                </div>
                            @endif
                        </div>
                    @endif

                    <div class="mt-4 d-grid gap-2">
                        @if($voucher)
                            @if($voucher->kode_voucher && $voucher->status === \App\Models\Voucher::STATUS_ACTIVE)
                                <a class="btn btn-qdj" href="{{ route('vouchers.redeem.show', ['kode' => $voucher->kode_voucher]) }}">Buka tautan penukaran</a>
                            @endif
                            <a class="btn btn-qdj-outline" href="{{ route('vouchers.check') }}">Cek voucher</a>
                        @else
                            <a class="btn btn-qdj-outline" href="{{ route('orders.track') }}">Lihat pesanan</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
