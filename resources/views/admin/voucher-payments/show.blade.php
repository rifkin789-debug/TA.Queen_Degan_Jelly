@extends('layouts.admin')

@section('title', 'Tinjau Pembayaran Voucher | Queen Degan Jelly')

@section('admin-content')
<div class="mb-4">
    <a href="{{ route('admin.voucher-payments.index') }}" class="text-decoration-none small text-muted">
        ← Kembali ke Verifikasi Pembayaran
    </a>
    <h1 class="h3 fw-bold text-dark mt-1 mb-1">Tinjau Pembayaran Voucher</h1>
    <p class="text-muted small mb-0">{{ $voucher->nama_paket }} · {{ $voucher->status }}</p>
</div>

<div class="qdj-card p-4 mb-4" style="max-width: 800px;">
    <div class="row g-3 small mb-4">
        <div class="col-md-6">
            <span class="text-muted d-block">Paket:</span>
            <strong class="text-dark fs-6">{{ $voucher->nama_paket }}</strong>

            <span class="text-muted d-block mt-2">Nama pengirim:</span>
            <strong class="text-dark">{{ $voucher->nama_pengirim }}</strong>

            <span class="text-muted d-block mt-2">Nomor HP pengirim:</span>
            <span class="text-dark">{{ $voucher->nomor_hp_pengirim }}</span>

            <span class="text-muted d-block mt-2">Nama penerima:</span>
            <span class="text-dark">{{ $voucher->nama_penerima ?: 'Belum diisi' }}</span>

            <span class="text-muted d-block mt-2">Pesan ucapan:</span>
            <span class="text-dark">{{ $voucher->pesan_ucapan ?: 'Tidak ada' }}</span>
        </div>
        <div class="col-md-6">
            <span class="text-muted d-block">Harga paket:</span>
            <span class="text-dark">Rp{{ number_format($voucher->harga, 0, ',', '.') }}</span>

            <span class="text-muted d-block mt-2">Kode unik:</span>
            <span class="text-dark">Rp{{ number_format($voucher->payment?->unique_code ?? 0, 0, ',', '.') }}</span>

            <span class="text-muted d-block mt-2">Total dibayar:</span>
            <strong class="text-dark fs-5" style="color: var(--qdj-green-dark);">Rp{{ number_format($voucher->payment?->gross_amount ?? $voucher->harga, 0, ',', '.') }}</strong>

            <span class="text-muted d-block mt-2">Status voucher:</span>
            <span class="badge-qdj-available">{{ $voucher->status }}</span>
        </div>
    </div>

    @if($voucher->payment?->proof_path)
        <div class="pt-3 border-top mb-4">
            <h2 class="h5 fw-bold text-dark mb-2">Bukti pembayaran</h2>
            <a href="{{ route('admin.voucher-payments.proof', $voucher) }}" target="_blank" rel="noopener" class="d-inline-block">
                <img class="img-fluid rounded-4 border shadow-sm" src="{{ route('admin.voucher-payments.proof', $voucher) }}" alt="Bukti pembayaran voucher" style="max-height: 480px;">
            </a>
        </div>
    @else
        <div class="alert alert-warning rounded-3 small">Bukti pembayaran belum tersedia.</div>
    @endif

    @if($voucher->status === \App\Models\Voucher::STATUS_WAITING_VERIFICATION
        && $voucher->payment?->transaction_status === 'pending_verification')
        <form method="POST" action="{{ route('admin.voucher-payments.activate', $voucher) }}" class="mt-4 pt-3 border-top">
            @csrf
            @method('PATCH')
            <button class="btn btn-qdj px-4 py-2" type="submit">Verifikasi dan Aktifkan Voucher</button>
        </form>
    @elseif($voucher->status === \App\Models\Voucher::STATUS_ACTIVE)
        <div class="alert alert-success rounded-4 mt-3">
            Voucher aktif sampai {{ $voucher->berlaku_sampai?->format('d/m/Y H:i') }}.
        </div>
        <p class="mb-1">Kode voucher: <strong>{{ $voucher->kode_voucher }}</strong></p>
        <p class="mb-0 small">Tautan penukaran: <a class="fw-semibold" style="color: var(--qdj-green-dark);" href="{{ route('vouchers.redeem.show', ['kode' => $voucher->kode_voucher]) }}">{{ route('vouchers.redeem.show', ['kode' => $voucher->kode_voucher]) }}</a></p>
    @endif
</div>

<a class="btn btn-qdj-outline" href="{{ route('admin.voucher-payments.index') }}">← Kembali ke daftar</a>
@endsection
