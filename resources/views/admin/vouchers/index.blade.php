@extends('layouts.admin')

@section('title', 'Kelola Voucher Gift | Queen Degan Jelly')

@section('admin-content')
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Kelola Voucher Gift</h1>
        <p class="text-muted small mb-0">Atur stok voucher per paket. Kode voucher unik dibuat otomatis saat pelanggan membeli voucher.</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-qdj-outline" href="{{ route('admin.voucher-payments.index') }}">
            Verifikasi Pembayaran
        </a>
        <a class="btn btn-qdj" href="{{ route('admin.paket-voucher.index') }}">
            Paket Voucher
        </a>
    </div>
</div>

<!-- Pengaturan Stok Voucher per Paket -->
<div class="qdj-card p-3 p-md-4 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h2 class="h6 fw-bold text-dark mb-0">Stok Voucher per Paket</h2>
            <span class="small text-muted">Tambah atau kurangi jumlah stok tersedia. Stok berkurang otomatis saat ada pembelian.</span>
        </div>
        <a class="btn btn-sm btn-qdj-outline" href="{{ route('admin.paket-voucher.create') }}">+ Tambah Paket</a>
    </div>

    @if($packages->isEmpty())
        <p class="small text-muted mb-0">Belum ada paket voucher. Tambahkan paket terlebih dahulu.</p>
    @else
        <div class="row g-3">
            @foreach($packages as $pkg)
                <div class="col-md-6 col-xl-4">
                    <div class="border rounded-4 p-3 h-100 d-flex flex-column justify-content-between bg-light">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <div>
                                <strong class="text-dark d-block">{{ $pkg->nama_paket }}</strong>
                                <span class="small text-muted">Rp{{ number_format($pkg->harga, 0, ',', '.') }} · {{ $pkg->jumlah_batok }} batok</span>
                            </div>
                            @if(!$pkg->aktif)
                                <span class="badge-qdj-out">Nonaktif</span>
                            @elseif($pkg->stok > 0)
                                <span class="badge-qdj-available">Stok: {{ $pkg->stok }}</span>
                            @else
                                <span class="badge-qdj-out">Habis</span>
                            @endif
                        </div>
                        <div class="d-flex align-items-center justify-content-between gap-2 pt-2 border-top">
                            <div class="d-inline-flex align-items-center gap-1">
                                <form method="POST" action="{{ route('admin.paket-voucher.stock', $pkg) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="delta" value="-1">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary px-2 py-0" @disabled($pkg->stok <= 0)>−1</button>
                                </form>
                                <form method="POST" action="{{ route('admin.paket-voucher.stock', $pkg) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="delta" value="1">
                                    <button type="submit" class="btn btn-sm btn-outline-success px-2 py-0">+1</button>
                                </form>
                                <form method="POST" action="{{ route('admin.paket-voucher.stock', $pkg) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="delta" value="5">
                                    <button type="submit" class="btn btn-sm btn-outline-success px-2 py-0">+5</button>
                                </form>
                            </div>
                            <form method="POST" action="{{ route('admin.paket-voucher.stock', $pkg) }}" class="d-inline-flex align-items-center gap-1">
                                @csrf
                                @method('PATCH')
                                <input type="number" name="stok" min="0" value="{{ $pkg->stok }}" class="form-control form-control-sm" style="width: 70px;" aria-label="Atur stok {{ $pkg->nama_paket }}">
                                <button type="submit" class="btn btn-sm btn-qdj py-0 px-2">Set</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<!-- Filter Bar -->
<div class="qdj-card p-3 mb-4">
    <form class="row g-2 align-items-end" method="GET" action="{{ route('admin.vouchers.index') }}">
        <div class="col-sm-8 col-md-5">
            <label class="form-label small fw-semibold text-dark" for="status">Filter Status Voucher</label>
            <select class="form-select form-select-sm" id="status" name="status">
                <option value="">Semua status</option>
                @foreach($statuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <button class="btn btn-sm btn-qdj" type="submit">Filter</button>
            <a class="btn btn-sm btn-qdj-outline" href="{{ route('admin.vouchers.index') }}">Reset</a>
        </div>
    </form>
</div>

<div class="qdj-card overflow-hidden">
    @if($vouchers->isEmpty())
        <div class="p-4 text-center text-muted">
            Tidak ada voucher pada filter ini.
        </div>
    @else
        <div class="table-responsive">
            <table class="table admin-table align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">Paket</th>
                        <th scope="col">Pengirim</th>
                        <th scope="col">Kode voucher</th>
                        <th scope="col">Status</th>
                        <th scope="col">Pembayaran</th>
                        <th scope="col">Dibuat</th>
                        <th scope="col" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($vouchers as $voucher)
                        <tr>
                            <td>
                                <strong class="text-dark">{{ $voucher->nama_paket }}</strong>
                                <span class="small text-muted d-block">{{ $voucher->jumlah_batok }} batok kelapa</span>
                            </td>
                            <td>
                                <span class="fw-semibold text-dark">{{ $voucher->nama_pengirim }}</span>
                                <span class="small text-muted d-block">{{ $voucher->nomor_hp_pengirim }}</span>
                            </td>
                            <td>
                                @if($voucher->kode_voucher)
                                    <code class="fw-bold fs-6 text-dark bg-light px-2 py-1 rounded">{{ $voucher->kode_voucher }}</code>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($voucher->status === \App\Models\Voucher::STATUS_ACTIVE)
                                    <span class="badge-qdj-available">Aktif</span>
                                @elseif($voucher->status === \App\Models\Voucher::STATUS_USED)
                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-1">Sudah Ditukar</span>
                                @elseif($voucher->status === \App\Models\Voucher::STATUS_EXPIRED)
                                    <span class="badge-qdj-out">Kedaluwarsa</span>
                                @else
                                    <span class="badge-qdj-fresh-warn">{{ $voucher->status }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="small text-muted">{{ $voucher->payment?->transaction_status ?: '—' }}</span>
                            </td>
                            <td>
                                <span class="small text-muted">{{ $voucher->created_at->format('d/m/Y H:i') }}</span>
                            </td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-qdj-outline" href="{{ route('admin.vouchers.show', $voucher) }}">
                                    Detail
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
