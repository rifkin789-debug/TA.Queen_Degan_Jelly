@extends('layouts.admin')

@section('title', 'Kelola Buah & Isian | Queen Degan Jelly')

@section('admin-content')
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Kelola Buah</h1>
        <p class="text-muted small mb-0">Atur harga per porsi dan stok buah untuk kustomisasi isian dan voucher gift.</p>
    </div>
    <a class="btn btn-qdj" href="{{ route('admin.buah.create') }}">
        + Tambah Buah
    </a>
</div>

<div class="qdj-card overflow-hidden">
    @if($fruits->isEmpty())
        <div class="p-4 text-center text-muted">Belum ada data buah.</div>
    @else
        <div class="table-responsive">
            <table class="table admin-table align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">Nama buah</th>
                        <th scope="col">Harga per porsi</th>
                        <th scope="col">Stok porsi</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($fruits as $fruit)
                        <tr>
                            <td>
                                <strong class="text-dark">{{ $fruit->nama_buah }}</strong>
                            </td>
                            <td>
                                <strong class="text-dark">Rp{{ number_format($fruit->harga_porsi, 0, ',', '.') }}</strong>
                            </td>
                            <td>
                                <span class="fw-semibold {{ $fruit->stok_porsi > 0 ? 'text-dark' : 'text-danger' }}">
                                    {{ number_format($fruit->stok_porsi) }}
                                </span>
                            </td>
                            <td>
                                @if($fruit->aktif)
                                    <span class="badge-qdj-available">Aktif</span>
                                @else
                                    <span class="badge-qdj-out">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="d-inline-flex gap-2">
                                    <a class="btn btn-sm btn-qdj-outline" href="{{ route('admin.buah.edit', $fruit) }}">Ubah</a>
                                    <form method="POST" action="{{ route('admin.buah.status', $fruit) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-sm {{ $fruit->aktif ? 'btn-outline-danger' : 'btn-outline-success' }}" type="submit">
                                            {{ $fruit->aktif ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($fruits->hasPages())
            <div class="p-3 border-top d-flex justify-content-end">
                {{ $fruits->links() }}
            </div>
        @endif
    @endif
</div>
@endsection
