@extends('layouts.app')

@section('title', 'Cek Status Voucher | Queen Degan Jelly')

@section('content')
<div class="container py-5" style="max-width: 720px;">
    <div class="qdj-card p-4 p-md-5">
        <div class="text-center mb-4">
            <span class="badge-qdj-voucher mb-2">🔍 Verifikasi Voucher</span>
            <h1 class="h3 fw-bold text-dark">Cek Voucher Hadiah</h1>
            <p class="text-muted small">Masukkan kode voucher dan 4 digit terakhir nomor HP pengirim untuk memeriksa status dan mengambil tautan penukaran.</p>
        </div>

        <form method="POST" action="{{ route('vouchers.check.submit') }}" class="mb-4">
            @csrf
            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark" for="kode_voucher">Kode voucher</label>
                <input class="form-control text-uppercase" id="kode_voucher" name="kode_voucher" value="{{ old('kode_voucher') }}" maxlength="40" placeholder="Contoh: QDJ-V-XXXXXX" autocomplete="off" required>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-semibold text-dark" for="phone_last4">4 digit terakhir nomor HP pengirim</label>
                <input class="form-control" id="phone_last4" name="phone_last4" value="{{ old('phone_last4') }}" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" placeholder="Misal: 1234" required>
            </div>
            <button class="btn btn-qdj w-100 py-2" type="submit">Lihat Voucher</button>
        </form>

        @if(isset($voucher))
            <div class="border-top pt-4">
                <h2 class="h5 fw-bold text-dark mb-3">Informasi voucher</h2>
                <div class="p-3 rounded-4 bg-light mb-3">
                    <p class="mb-1">Status: <strong>{{ $voucher->status }}</strong></p>
                    @if($voucher->status === \App\Models\Voucher::STATUS_ACTIVE)
                        <p class="mb-1">Nama pengirim: {{ $voucher->nama_pengirim }}</p>
                        @if($voucher->pesan_ucapan)
                            <p class="mb-1">Pesan: {{ $voucher->pesan_ucapan }}</p>
                        @endif
                        <p class="mb-1">Kode voucher: <strong>{{ $voucher->kode_voucher }}</strong></p>
                        <p class="mb-3">Berlaku sampai: {{ $voucher->berlaku_sampai?->format('d/m/Y H:i') }}</p>

                        <label class="form-label small fw-semibold text-dark" for="redemption-url">Tautan penukaran</label>
                        <div class="input-group">
                            <input class="form-control" id="redemption-url" value="{{ $redemptionUrl }}" readonly>
                            <button class="btn btn-outline-secondary" id="copy-redemption-url" type="button">Salin tautan</button>
                        </div>
                        <div class="small mt-2" id="copy-status" aria-live="polite"></div>
                    @elseif($voucher->status === \App\Models\Voucher::STATUS_EXPIRED)
                        <p class="mb-0 text-danger">Masa berlaku voucher telah berakhir.</p>
                    @elseif($voucher->status === \App\Models\Voucher::STATUS_USED)
                        <p class="mb-0 text-muted">Voucher ini sudah digunakan.</p>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    const copyButton = document.getElementById('copy-redemption-url');
    const redemptionUrl = document.getElementById('redemption-url');
    const copyStatus = document.getElementById('copy-status');

    copyButton?.addEventListener('click', () => {
        if (!navigator.clipboard || !navigator.clipboard.writeText) {
            redemptionUrl.select();
            copyStatus.textContent = 'Penyalinan otomatis tidak tersedia. Salin tautan yang sudah dipilih secara manual.';
            return;
        }

        navigator.clipboard.writeText(redemptionUrl.value).then(() => {
            copyStatus.textContent = 'Tautan berhasil disalin.';
        }).catch(() => {
            redemptionUrl.select();
            copyStatus.textContent = 'Penyalinan otomatis gagal. Salin tautan yang sudah dipilih secara manual.';
        });
    });
</script>
@endpush
