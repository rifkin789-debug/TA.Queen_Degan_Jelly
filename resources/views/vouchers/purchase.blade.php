@extends('layouts.app')

@section('title', 'Beli Voucher Gift | Queen Degan Jelly')

@section('content')
<div class="container py-4" style="max-width: 900px;">
    <!-- Header -->
    <div class="text-center mb-4">
        <span class="badge-qdj-voucher mb-2">🎁 Voucher Gift & Kustomisasi</span>
        <h1 class="display-6 fw-bold mb-2 text-dark">Beli Voucher Gift</h1>
        <p class="text-muted" style="max-width: 650px; margin: 0 auto;">
            Hadiahkan kesegaran Queen Degan Jelly untuk orang tersayang! Anda membayar penuh sekarang, dan si penerima bebas memilih varian serta isian buah favoritnya tanpa biaya tambahan.
        </p>
    </div>

    <!-- Info Box Aturan Bisnis Voucher -->
    <div class="p-3 mb-4 rounded-4" style="background-color: var(--qdj-blue-soft); border: 1px solid var(--qdj-blue-border);">
        <div class="d-flex align-items-start gap-3">
            <span class="fs-4">🎁</span>
            <div class="small">
                <strong class="d-block text-dark mb-1">Sudah Termasuk Kustomisasi Isian Buah</strong>
                <span class="text-muted">
                    Voucher gift sudah mencakup harga degan jelly, pilihan isian buah segar (hingga 3 jenis), serta pengiriman. Penerima tinggal memasukkan kode voucher, memilih buah kesukaannya, dan menikmati kiriman Anda.
                </span>
            </div>
        </div>
    </div>

    @if($packages->isEmpty())
        <div class="qdj-card p-5 text-center">
            <p class="text-muted mb-0">Paket voucher belum tersedia saat ini. Silakan cek kembali nanti.</p>
        </div>
    @else
        <form method="POST" action="{{ route('vouchers.purchase.store') }}">
            @csrf

            <!-- 1. Pilih Paket Voucher -->
            @php($firstAvailable = $packages->first(fn ($pkg) => $pkg->stok > 0))
            <div class="mb-4">
                <h2 class="h5 fw-bold text-dark mb-3">1. Pilih Paket Voucher</h2>
                <div class="row g-3">
                    @foreach($packages as $package)
                        @php($isSoldOut = $package->stok < 1)
                        <div class="col-md-4">
                            <label class="qdj-card p-4 h-100 d-flex flex-column {{ $isSoldOut ? 'opacity-75 bg-light' : '' }}" style="cursor: {{ $isSoldOut ? 'not-allowed' : 'pointer' }};">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <div class="form-check p-0 mb-0">
                                        <input class="form-check-input ms-0 me-2"
                                               type="radio"
                                               name="paket_voucher_id"
                                               value="{{ $package->id }}"
                                               @checked(!$isSoldOut && old('paket_voucher_id', $firstAvailable?->id) == $package->id)
                                               @disabled($isSoldOut)
                                               required>
                                        <strong class="text-dark">{{ $package->nama_paket }}</strong>
                                    </div>
                                    @if($isSoldOut)
                                        <span class="badge-qdj-out">Habis</span>
                                    @else
                                        <span class="badge-qdj-available">Stok: {{ $package->stok }}</span>
                                    @endif
                                </div>
                                <div class="fs-4 fw-bold my-2" style="color: {{ $isSoldOut ? '#6B7280' : 'var(--qdj-green-dark)' }};">
                                    Rp{{ number_format($package->harga, 0, ',', '.') }}
                                </div>
                                <div class="small text-muted mt-auto pt-2 border-top">
                                    🥥 {{ $package->jumlah_batok }} batok kelapa<br>
                                    🍇 Bebas pilih {{ $package->maks_buah }} isian buah
                                    @if($isSoldOut)
                                        <div class="text-danger fw-semibold mt-1">Stok voucher habis</div>
                                    @endif
                                </div>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>

            @if(!$firstAvailable)
                <div class="alert alert-warning rounded-4 mb-4 small">
                    Semua stok paket voucher saat ini sedang habis. Silakan cek kembali nanti.
                </div>
            @endif

            <!-- 2. Data Pengirim & Ucapan -->
            <div class="qdj-card p-4 mb-4">
                <h2 class="h5 fw-bold text-dark mb-3">2. Data Pengirim & Pesan Ucapan</h2>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark" for="nama_pengirim">Nama Pengirim</label>
                        <input class="form-control" id="nama_pengirim" name="nama_pengirim" value="{{ old('nama_pengirim') }}" maxlength="120" placeholder="Nama Anda" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark" for="nomor_hp_pengirim">Nomor WhatsApp Pengirim</label>
                        <input class="form-control" id="nomor_hp_pengirim" name="nomor_hp_pengirim" type="tel" value="{{ old('nomor_hp_pengirim') }}" maxlength="30" placeholder="08xxxxxxxxxx" required>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark" for="nama_penerima">Nama Penerima (Opsional)</label>
                        <input class="form-control" id="nama_penerima" name="nama_penerima" value="{{ old('nama_penerima') }}" maxlength="120" placeholder="Nama teman / keluarga">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark" for="pesan_ucapan">Pesan Ucapan Hadiah (Opsional)</label>
                        <textarea class="form-control" id="pesan_ucapan" name="pesan_ucapan" rows="2" maxlength="200" placeholder="Contoh: Selamat ulang tahun! Semoga suka degan jelly segarnya.">{{ old('pesan_ucapan') }}</textarea>
                    </div>
                </div>

                <div class="pt-3 border-top d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3">
                    <span class="small text-muted">
                        🔒 Pembayaran via QRIS dengan verifikasi admin
                    </span>
                    <button class="btn btn-qdj px-5 py-2" type="submit" @disabled(!$firstAvailable)>
                        {{ $firstAvailable ? 'Lanjut ke Pembayaran QRIS' : 'Stok Voucher Habis' }}
                    </button>
                </div>
            </div>
        </form>
    @endif
</div>
@endsection
