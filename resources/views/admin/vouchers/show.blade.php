@extends('layouts.admin')

@section('title', 'Detail Voucher Gift | Queen Degan Jelly')

@section('admin-content')
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-start gap-3 mb-4">
    <div>
        <a href="{{ route('admin.vouchers.index') }}" class="text-decoration-none small text-muted">
            ← Kembali ke Kelola Voucher
        </a>
        <h1 class="h3 fw-bold text-dark mt-1 mb-1">Detail Voucher</h1>
        <p class="text-muted small mb-0">{{ $voucher->nama_paket }} · {{ $voucher->status }}</p>
    </div>
    <a class="btn btn-qdj-outline" href="{{ route('admin.vouchers.index') }}">Kembali ke daftar</a>
</div>

<!-- Informasi Voucher -->
<div class="qdj-card p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
        <h2 class="h5 fw-bold text-dark mb-0">Informasi voucher</h2>
        @if($voucher->status === \App\Models\Voucher::STATUS_ACTIVE)
            <span class="badge-qdj-available">Aktif</span>
        @elseif($voucher->status === \App\Models\Voucher::STATUS_USED)
            <span class="badge-qdj-available">Sudah Ditukar</span>
        @elseif($voucher->status === \App\Models\Voucher::STATUS_EXPIRED)
            <span class="badge-qdj-out">{{ \App\Models\Voucher::STATUS_EXPIRED }}</span>
        @else
            <span class="badge-qdj-fresh-warn">{{ $voucher->status }}</span>
        @endif
    </div>

    <div class="row g-3 small">
        <div class="col-md-6">
            <span class="text-muted d-block">Nama pengirim:</span>
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
            <strong class="text-dark">Rp{{ number_format($voucher->harga, 0, ',', '.') }}</strong>

            <span class="text-muted d-block mt-2">Status:</span>
            <strong class="text-dark">{{ $voucher->status }}</strong>

            <span class="text-muted d-block mt-2">Kode voucher:</span>
            <strong class="text-dark">{{ $voucher->kode_voucher ?: 'Belum dibuat' }}</strong>

            <span class="text-muted d-block mt-2">Berlaku sampai:</span>
            <span class="text-dark">{{ $voucher->berlaku_sampai?->format('d/m/Y H:i') ?: '—' }}</span>
        </div>
    </div>

    @if($voucher->kode_voucher && $voucher->status === \App\Models\Voucher::STATUS_ACTIVE)
        <div class="mt-4 pt-3 border-top small">
            <span class="text-muted d-block mb-1">Tautan penukaran:</span>
            <a class="fw-semibold" style="color: var(--qdj-green-dark);" href="{{ route('vouchers.redeem.show', ['kode' => $voucher->kode_voucher]) }}">
                {{ route('vouchers.redeem.show', ['kode' => $voucher->kode_voucher]) }}
            </a>
        </div>
    @endif
</div>

<!-- Informasi Pembayaran -->
<div class="qdj-card p-4 mb-4">
    <h2 class="h5 fw-bold text-dark mb-3">Pembayaran</h2>
    <div class="small">
        <span class="text-muted d-block">Status:</span>
        <strong class="text-dark">{{ $voucher->payment?->transaction_status ?: 'Belum tersedia' }}</strong>

        <span class="text-muted d-block mt-2">Total:</span>
        <strong class="text-dark fs-6">Rp{{ number_format($voucher->payment?->gross_amount ?? $voucher->harga, 0, ',', '.') }}</strong>
    </div>

    @if($voucher->payment?->proof_path)
        <div class="mt-3">
            <a class="btn btn-sm btn-qdj-outline" href="{{ route('admin.voucher-payments.proof', $voucher) }}" target="_blank" rel="noopener">
                Lihat bukti pembayaran ↗
            </a>
        </div>
    @endif

    @if($voucher->status === \App\Models\Voucher::STATUS_WAITING_VERIFICATION)
        <div class="mt-3">
            <a class="btn btn-qdj" href="{{ route('admin.voucher-payments.show', $voucher) }}">
                Tinjau dan verifikasi pembayaran
            </a>
        </div>
    @endif
</div>

<!-- Pesanan Penukaran -->
@if($voucher->redemptionOrder)
    <div class="qdj-card p-4 mb-4" style="background-color: var(--qdj-mint);">
        <h2 class="h5 fw-bold text-dark mb-2">Pesanan penukaran</h2>
        <p class="mb-0 small">
            <a class="fw-bold" style="color: var(--qdj-green-dark);" href="{{ route('admin.orders.show', $voucher->redemptionOrder) }}">
                {{ $voucher->redemptionOrder->order_code }}
            </a>
            · Status: {{ $voucher->redemptionOrder->order_status }}
        </p>
    </div>
@endif

<!-- Batalkan Voucher -->
@if(!in_array($voucher->status, [\App\Models\Voucher::STATUS_USED, \App\Models\Voucher::STATUS_CANCELLED], true))
    <div class="qdj-card p-4 border-danger">
        <h2 class="h5 fw-bold text-danger mb-1">Batalkan voucher</h2>
        <p class="text-muted small mb-3">Voucher yang dibatalkan tidak dapat digunakan. Status pembayaran yang sudah diverifikasi tidak berubah.</p>
        <form method="POST" action="{{ route('admin.vouchers.cancel', $voucher) }}" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan voucher ini?')">
            @csrf
            @method('PATCH')
            <button class="btn btn-qdj-danger" type="submit">Batalkan Voucher</button>
        </form>
    </div>
@endif
@endsection
