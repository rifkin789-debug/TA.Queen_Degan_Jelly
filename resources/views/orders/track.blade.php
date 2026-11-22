@extends('layouts.app')

@section('title', 'Lihat Pesanan | Queen Degan Jelly')

@section('content')
<div class="container py-5" style="max-width: 580px;">
    <div class="qdj-card p-4 p-md-5">
        <div class="text-center mb-4">
            <span class="badge-qdj-blue mb-2">📦 Pelacakan Real-Time</span>
            <h1 class="h3 fw-bold text-dark">Lihat Pesanan</h1>
            <p class="text-muted small">Pelanggan yang sudah masuk dapat langsung melihat seluruh pesanan tanpa kode. Jika memesan sebagai tamu, Anda dapat mencari pesanan di bawah ini.</p>
        </div>

        @guest
            <div class="p-3 mb-4 rounded-4 text-center" style="background: var(--qdj-primary-soft); border: 1px dashed var(--qdj-primary-border);">
                <p class="small text-dark mb-2"><strong>Sudah memiliki akun?</strong></p>
                <a href="{{ route('login') }}" class="btn btn-sm btn-qdj rounded-pill px-4 py-2">
                    Masuk ke Akun Saya →
                </a>
                <p class="small text-muted mt-2 mb-0">Pesanan akan otomatis muncul di menu <strong>Lihat Pesanan</strong> tanpa perlu memasukkan kode.</p>
            </div>
        @endguest

        @if($errors->has('lookup'))
            <div class="alert alert-danger rounded-4 mb-4">{{ $errors->first('lookup') }}</div>
        @endif

        <form method="POST" action="{{ route('orders.track.show') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark" for="order_code">Kode Pesanan</label>
                <input class="form-control text-uppercase"
                       id="order_code"
                       name="order_code"
                       value="{{ old('order_code') }}"
                       maxlength="40"
                       placeholder="Misal: QDJ-20261110-XXXXXX"
                       required>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-semibold text-dark" for="phone_last4">4 Digit Terakhir Nomor HP</label>
                <input class="form-control"
                       id="phone_last4"
                       name="phone_last4"
                       value="{{ old('phone_last4') }}"
                       inputmode="numeric"
                       pattern="[0-9]{4}"
                       maxlength="4"
                       placeholder="Misal: 7890"
                       required>
            </div>
            <button class="btn btn-qdj w-100 py-3" type="submit">Cari Pesanan</button>
        </form>

        <div class="text-center mt-4 pt-3 border-top">
            <span class="small text-muted">Butuh bantuan langsung?</span>
            @php
                $phoneDisplay = $shopProfile?->nomor_hp ?: '+62 812-3456-7890';
                $cleanPhone = preg_replace('/[^0-9]/', '', $phoneDisplay);
                $waNumber = str_starts_with($cleanPhone, '0') ? ('62' . substr($cleanPhone, 1)) : $cleanPhone;
            @endphp
            <a class="small d-block fw-semibold mt-1" style="color: var(--qdj-green-dark);" href="https://wa.me/{{ $waNumber }}" target="_blank">
                Hubungi Dapur {{ $shopProfile?->nama_toko ?: 'Queen Degan Jelly' }} via WhatsApp →
            </a>
        </div>
    </div>
</div>
@endsection
