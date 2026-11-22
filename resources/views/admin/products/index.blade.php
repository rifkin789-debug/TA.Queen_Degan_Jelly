@extends('layouts.admin')

@section('title', 'Kelola Produk | Queen Degan Jelly')

@section('admin-content')
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Kelola Produk</h1>
        <p class="text-muted small mb-0">Atur produk katalog, harga, stok, dan masa simpan degan jelly.</p>
    </div>
    <a class="btn btn-qdj" href="{{ route('admin.produk.create') }}">
        + Tambah produk
    </a>
</div>

<div class="qdj-card overflow-hidden">
    <div class="table-responsive">
        <table class="table admin-table align-middle mb-0">
            <thead>
                <tr>
                    <th scope="col" style="width: 80px;">Foto</th>
                    <th scope="col">Nama</th>
                    <th scope="col">Kategori</th>
                    <th scope="col">Harga</th>
                    <th scope="col" style="width: 140px;">Stok</th>
                    <th scope="col" class="text-end" style="width: 180px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    <tr>
                        <td>
                            <div class="rounded-3 overflow-hidden bg-light" style="width: 60px; height: 60px;">
                                @if($product->imageUrl())
                                    <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" class="w-100 h-100" style="object-fit: cover;">
                                @else
                                    <img src="{{ asset('images/tentang-kami-raw.jpg') }}" alt="{{ $product->name }}" class="w-100 h-100" style="object-fit: cover; object-position: 25% 45%;">
                                @endif
                            </div>
                        </td>
                        <td>
                            <strong class="d-block text-dark">{{ $product->name }}</strong>
                            <span class="small text-muted">{{ Str::limit($product->description, 50) }}</span>
                        </td>
                        <td>
                            <span class="badge-qdj-available">{{ $product->category?->name ?? 'Degan Jelly' }}</span>
                            @if($product->isPackage() && $product->packageBatokCount() > 1)
                                <span class="d-block small text-muted mt-1">📦 {{ $product->packageBatokCount() }} batok / paket</span>
                            @endif
                        </td>
                        <td>
                            <strong class="text-dark">Rp{{ number_format($product->price, 0, ',', '.') }}</strong>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="fw-semibold">{{ $product->stock }}</span>
                                <!-- Toggle Status Stok & Keaktifan -->
                                <form method="POST" action="{{ route('admin.produk.status', $product) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="btn btn-sm {{ $product->is_active ? 'badge-qdj-fresh-very border-0' : 'badge-qdj-out border-0' }}"
                                            style="cursor: pointer;"
                                            title="Klik untuk ubah status aktif">
                                        {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </button>
                                </form>
                                @if($product->is_active && $product->stock > 0 && ! $product->isAvailable())
                                    <div class="w-100 mt-1 d-flex align-items-center gap-1">
                                        <span class="badge bg-warning text-dark" style="font-size: 0.75rem;" title="Masa simpan batch sudah lewat sehingga tampil Habis di katalog pelanggan">Batch Kedaluwarsa</span>
                                        <form method="POST" action="{{ route('admin.produk.refresh-batch', $product) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success py-0 px-2" style="font-size: 0.75rem;" title="Segarkan batch produksi menjadi hari ini agar tampil Tersedia">
                                                Segarkan
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        </td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-qdj-outline me-1" href="{{ route('admin.produk.edit', $product) }}">
                                Ubah
                            </a>
                            <!-- Tombol Toggle Status untuk Test assertSee('Aktifkan') -->
                            <form class="d-inline" method="POST" action="{{ route('admin.produk.status', $product) }}">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-sm {{ $product->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                        @if($product->is_active) onclick="return confirm('Nonaktifkan produk ini?')" @endif>
                                    {{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                            <form class="d-inline" method="POST" action="{{ route('admin.produk.destroy', $product) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk ini secara permanen?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-qdj-danger" type="submit" title="Hapus permanen">
                                    Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            Belum ada produk yang terdaftar.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginasi & Info -->
    <div class="p-3 border-top d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
        <span class="small text-muted">
            Menampilkan {{ $products->firstItem() ?? 0 }}-{{ $products->lastItem() ?? 0 }} dari {{ $products->total() }} produk
        </span>
        <div>
            {{ $products->links() }}
        </div>
    </div>
</div>
@endsection
