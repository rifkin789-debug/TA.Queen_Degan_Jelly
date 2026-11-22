@extends('layouts.admin')

@section('title', 'Kelola Paket Voucher | Queen Degan Jelly')

@section('admin-content')
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Kelola Paket Voucher</h1>
        <p class="text-muted small mb-0">Atur pilihan paket voucher gift. Harga paket sudah termasuk ongkir antar area mitra.</p>
    </div>
    <a class="btn btn-qdj" href="{{ route('admin.paket-voucher.create') }}">
        + Tambah Paket Voucher
    </a>
</div>

<div class="qdj-card overflow-hidden">
    @if($packages->isEmpty())
        <div class="p-4 text-center text-muted">Belum ada paket voucher.</div>
    @else
        <div class="table-responsive">
            <table class="table admin-table align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">Nama paket</th>
                        <th scope="col">Harga</th>
                        <th scope="col">Jumlah batok</th>
                        <th scope="col">Maks. jenis buah</th>
                        <th scope="col">Stok Voucher</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($packages as $package)
                        <tr>
                            <td>
                                <strong class="text-dark">{{ $package->nama_paket }}</strong>
                            </td>
                            <td>
                                <strong class="text-dark">Rp{{ number_format($package->harga, 0, ',', '.') }}</strong>
                            </td>
                            <td>
                                <span class="fw-semibold text-dark">{{ number_format($package->jumlah_batok) }} batok</span>
                            </td>
                            <td>
                                <span class="small text-muted">{{ number_format($package->maks_buah) }} jenis buah</span>
                            </td>
                            <td class="text-nowrap">
                                <div class="d-inline-flex align-items-center gap-1">
                                    <form method="POST" action="{{ route('admin.paket-voucher.stock', $package) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="delta" value="-1">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary px-2 py-0" title="Kurangi 1 stok" @disabled($package->stok <= 0)>−</button>
                                    </form>
                                    @if($package->stok > 0)
                                        <span class="badge-qdj-available px-2">{{ number_format($package->stok) }}</span>
                                    @else
                                        <span class="badge-qdj-out px-2">Habis (0)</span>
                                    @endif
                                    <form method="POST" action="{{ route('admin.paket-voucher.stock', $package) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="delta" value="1">
                                        <button type="submit" class="btn btn-sm btn-outline-success px-2 py-0" title="Tambah 1 stok">+</button>
                                    </form>
                                </div>
                            </td>
                            <td>
                                @if($package->aktif)
                                    <span class="badge-qdj-available">Aktif</span>
                                @else
                                    <span class="badge-qdj-out">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="d-inline-flex gap-2">
                                    <a class="btn btn-sm btn-qdj-outline" href="{{ route('admin.paket-voucher.edit', $package) }}">Ubah</a>
                                    <form method="POST" action="{{ route('admin.paket-voucher.status', $package) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-sm {{ $package->aktif ? 'btn-outline-danger' : 'btn-outline-success' }}" type="submit">
                                            {{ $package->aktif ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($packages->hasPages())
            <div class="p-3 border-top d-flex justify-content-end">
                {{ $packages->links() }}
            </div>
        @endif
    @endif
</div>
@endsection
