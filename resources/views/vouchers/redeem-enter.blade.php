@extends('layouts.app')

@section('title', 'Tukar Voucher Gift | Queen Degan Jelly')

@section('content')
<div class="container py-5" style="max-width: 600px;">
    <div class="qdj-card p-4 p-md-5">
        <div class="text-center mb-4">
            <span class="badge-qdj-voucher mb-2">🎁 Penukaran Voucher</span>
            <h1 class="h3 fw-bold text-dark">Tukar Voucher Hadiah</h1>
            <p class="text-muted small">Punya kode voucher gift dari teman atau keluarga? Masukkan kode di bawah untuk memilih degan jelly dan isian buah favorit Anda gratis.</p>
        </div>

        @if($errors->has('lookup'))
            <div class="alert alert-danger rounded-4">{{ $errors->first('lookup') }}</div>
        @endif

        <form method="POST" action="{{ route('vouchers.redeem.lookup') }}">
            @csrf
            <div class="mb-4">
                <label class="form-label small fw-semibold text-dark" for="kode_voucher">Kode Voucher</label>
                <input class="form-control text-uppercase py-2 fs-5 text-center fw-bold"
                       id="kode_voucher"
                       name="kode_voucher"
                       value="{{ old('kode_voucher') }}"
                       maxlength="40"
                       placeholder="MISAL: QDJ-V-XXXXXX"
                       autocomplete="off"
                       required>
            </div>

            <button class="btn btn-qdj w-100 py-3 fs-6" type="submit">
                Lanjutkan Penukaran
            </button>
        </form>

        <div class="text-center mt-4 pt-3 border-top">
            <a class="small text-muted text-decoration-none" href="{{ route('vouchers.check') }}">
                Saya pengirim dan ingin cek voucher →
            </a>
        </div>
    </div>
</div>
@endsection
