@extends('layouts.app')

@section('title', 'Katalog Produk | Queen Degan Jelly')

@section('content')
<!-- Header Katalog (Gaya Editorial Artisanal Chocolate Monggo) -->
<div class="container py-4">
    <div class="text-center max-w-xl mx-auto mb-4">
        <span class="qdj-kicker">PILIHAN KESEGARAN ALAMI</span>
        <h1 class="display-6 fw-bold mb-2" style="color: var(--qdj-brown-dark);">Katalog Produk</h1>
        <div class="qdj-divider"><span>◆</span></div>
        <p class="text-muted" style="max-width: 600px; margin: 0 auto;">Pilihan degan jelly segar murni dengan jelly lembut dan kelapa muda pilihan dari Muncar.</p>
    </div>

    <!-- Filter Pill: Semua, Original & Mix, Rasa, Paket -->
    <div class="d-flex flex-wrap justify-content-center gap-2 mb-4">
        <a href="{{ route('products.index') }}"
           class="filter-chip {{ (!request()->filled('category') && !request()->filled('filter')) ? 'active' : '' }}">
            Semua
        </a>
        <a href="{{ route('products.index', ['filter' => 'original']) }}"
           class="filter-chip {{ request('filter') === 'original' ? 'active' : '' }}">
            Original & Mix
        </a>
        <a href="{{ route('products.index', ['filter' => 'rasa']) }}"
           class="filter-chip {{ request('filter') === 'rasa' ? 'active' : '' }}">
            Rasa
        </a>
        <a href="{{ route('products.index', ['filter' => 'paket']) }}"
           class="filter-chip {{ in_array(request('filter'), ['paket', 'paket-mix'], true) ? 'active' : '' }}">
            Paket
        </a>
    </div>

    <!-- Grid Kartu Produk 3 Kolom -->
    <div class="row g-4">
        @forelse($products as $product)
            @php($available = $product->isAvailable())
            <div class="col-12 col-md-6 col-lg-4">
                <div class="qdj-card h-100 d-flex flex-column p-3">
                    <!-- Foto & Badge -->
                    <div class="position-relative mb-3 overflow-hidden rounded-4" style="height: 220px; background-color: var(--qdj-mint);">
                        @if($product->imageUrl())
                            <img src="{{ $product->imageUrl() }}"
                                 alt="{{ $product->name }}"
                                 class="w-100 h-100"
                                 style="object-fit: cover;">
                        @else
                            <img src="{{ asset('images/tentang-kami.jpg') }}"
                                 alt="{{ $product->name }}"
                                 class="w-100 h-100"
                                 style="object-fit: cover;">
                        @endif

                        <!-- Badge Tersedia / Habis -->
                        <div class="position-absolute top-0 end-0 m-2">
                            @if($available)
                                <span class="badge-qdj-available">Tersedia</span>
                            @else
                                <span class="badge-qdj-out">Habis</span>
                            @endif
                        </div>
                    </div>

                    <!-- Informasi Produk -->
                    <div class="d-flex flex-column flex-grow-1">
                        <div class="d-flex justify-content-between align-items-baseline mb-1">
                            <h2 class="h5 fw-bold mb-0 text-dark">{{ $product->name }}</h2>
                        </div>

                        <p class="text-muted small mb-3 flex-grow-1" style="min-height: 40px; line-height: 1.6;">
                            {{ Str::limit($product->description ?? 'Degan jelly lembut alami disajikan langsung di batok kelapa muda segar.', 85) }}
                        </p>

                        <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                            <div>
                                <span class="small text-muted d-block">Harga</span>
                                <span class="fw-bold fs-5" style="color: var(--qdj-brown-primary);">
                                    Rp{{ number_format($product->price, 0, ',', '.') }}
                                </span>
                            </div>

                            <!-- Tombol Pesan / Stok Habis -->
                            @if($available)
                                <a href="{{ route('products.show', $product) }}" class="btn btn-qdj px-4">
                                    Pesan
                                </a>
                            @else
                                <button type="button" class="btn btn-qdj-disabled px-4" disabled>
                                    Stok habis
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 py-5 text-center">
                <div class="qdj-card p-5 mx-auto" style="max-width: 500px;">
                    <span style="font-size: 3rem;">🥥</span>
                    <h3 class="h5 fw-bold mt-3">Produk Tidak Ditemukan</h3>
                    <p class="text-muted small">Belum ada produk aktif yang cocok dengan pencarian atau filter Anda.</p>
                    <a href="{{ route('products.index') }}" class="btn btn-qdj-outline mt-2">Reset Filter</a>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Paginasi -->
    <div class="mt-4 pt-3 d-flex justify-content-center">
        {{ $products->links() }}
    </div>
</div>
@endsection
