@extends('layouts.app')

@section('title', 'Voucher Berhasil Ditukar | Queen Degan Jelly')

@section('content')
<div class="container py-5" style="max-width: 680px;">
    <div class="qdj-card p-4 p-md-5 text-center">
        <div style="font-size: 3.5rem;">🎉</div>
        <h1 class="display-6 fw-bold mb-2 text-dark">Voucher berhasil ditukar</h1>
        <p class="text-muted">Pesanan Anda sudah dibayar dan sedang disiapkan oleh tim dapur Queen Degan Jelly.</p>

        <div class="p-3 my-4 rounded-4 bg-light border text-center">
            <span class="small text-muted d-block text-uppercase">Kode Pesanan Anda:</span>
            <strong class="fs-3 text-dark">{{ $order->order_code }}</strong>
        </div>

        <p class="small text-muted mb-4">
            Simpan kode tersebut. Untuk melacak status pengerjaan dan pengiriman pesanan, gunakan kode pesanan dan 4 digit terakhir nomor HP penerima Anda.
        </p>

        <div class="d-flex justify-content-center gap-2">
            <a class="btn btn-qdj px-4 py-2" href="{{ route('orders.track') }}">
                Lihat Pesanan
            </a>
            <a class="btn btn-qdj-outline px-4 py-2" href="{{ route('home') }}">
                Kembali ke Beranda
            </a>
        </div>
    </div>
</div>
@endsection
