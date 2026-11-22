@extends('layouts.admin')

@section('title', 'Verifikasi Pembayaran Voucher | Queen Degan Jelly')

@section('admin-content')
<div class="mb-4">
    <h1 class="h3 fw-bold text-dark mb-1">Verifikasi Pembayaran Voucher</h1>
    <p class="text-muted small">Tinjau bukti transfer pembayaran pembelian voucher gift dari pelanggan.</p>
</div>

<div class="qdj-card overflow-hidden">
    @if($vouchers->isEmpty())
        <div class="p-4 text-center text-muted">Tidak ada voucher yang menunggu verifikasi pembayaran.</div>
    @else
        <div class="table-responsive">
            <table class="table admin-table align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">Paket</th>
                        <th scope="col">Pengirim</th>
                        <th scope="col">Total dibayar</th>
                        <th scope="col">Dibuat</th>
                        <th scope="col" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($vouchers as $voucher)
                        <tr>
                            <td>
                                <strong class="text-dark">{{ $voucher->nama_paket }}</strong>
                            </td>
                            <td>
                                <span class="fw-semibold text-dark">{{ $voucher->nama_pengirim }}</span>
                            </td>
                            <td>
                                <strong class="text-dark">Rp{{ number_format($voucher->payment?->gross_amount ?? $voucher->harga, 0, ',', '.') }}</strong>
                            </td>
                            <td>
                                <span class="small text-muted">{{ $voucher->created_at->format('d/m/Y H:i') }}</span>
                            </td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-qdj" href="{{ route('admin.voucher-payments.show', $voucher) }}">
                                    Tinjau
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($vouchers->hasPages())
            <div class="p-3 border-top d-flex justify-content-end">
                {{ $vouchers->links() }}
            </div>
        @endif
    @endif
</div>
@endsection
