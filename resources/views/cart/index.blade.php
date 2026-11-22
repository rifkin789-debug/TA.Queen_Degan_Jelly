@extends('layouts.app')

@section('title', 'Keranjang Belanja | Queen Degan Jelly')

@section('content')
<div class="container py-4" style="max-width: 900px;">
    <div class="mb-4">
        <h1 class="h3 fw-bold text-dark mb-1">Keranjang Belanja</h1>
        <p class="text-muted small">Periksa pesanan degan jelly sebelum melanjutkan ke checkout.</p>
    </div>

    @if(count($items))
        <div class="qdj-card p-4">
            @foreach($items as $item)
                <div class="row align-items-center py-3 border-bottom g-3">
                    <div class="col-md-5">
                        <strong class="d-block text-dark">{{ $item['product']->name }}</strong>
                        @if(!empty($item['batok_details']))
                            <div class="small mt-1 p-2 rounded-3" style="background: #FAF7F2; border: 1px dashed var(--qdj-border);">
                                <span class="d-block fw-semibold text-dark mb-1" style="font-size: 0.78rem;">Mix Tiap Batok:</span>
                                @foreach($item['batok_details'] as $bNum => $bFruits)
                                    <div class="small text-muted py-0.5">
                                        <strong class="text-dark">Batok {{ $bNum }}:</strong>
                                        @if($bFruits->isNotEmpty())
                                            <span style="color: var(--qdj-green-dark); font-weight: 500;">
                                                {{ $bFruits->pluck('nama_buah')->implode(', ') }} (+Rp{{ number_format($bFruits->sum('harga_porsi'), 0, ',', '.') }})
                                            </span>
                                        @else
                                            <span class="fst-italic text-muted">Original Murni</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @elseif(!empty($item['fruits']) && $item['fruits']->count() > 0)
                            <span class="small d-block" style="color: var(--qdj-green-dark);">
                                + Isian: {{ $item['fruits']->pluck('nama_buah')->implode(', ') }}
                            </span>
                        @endif
                        @if($item['product']->isPackage() && $item['product']->packageBatokCount() > 1)
                            <span class="text-muted small">Rp{{ number_format($item['product']->price, 0, ',', '.') }} / paket ({{ $item['product']->packageBatokCount() }} batok)</span>
                        @else
                            <span class="text-muted small">Rp{{ number_format($item['product']->price, 0, ',', '.') }} / batok</span>
                        @endif
                    </div>

                    <div class="col-md-3">
                        <form method="POST" action="{{ route('cart.update', $item['product']) }}" class="d-flex align-items-center gap-2">
                            @csrf
                            @method('PATCH')
                            <input type="number"
                                   name="quantity"
                                   value="{{ $item['quantity'] }}"
                                   min="1"
                                   max="{{ $item['product']->stock }}"
                                   class="form-control form-control-sm text-center"
                                   style="width: 70px;">
                            <button class="btn btn-sm btn-qdj-outline">Ubah</button>
                        </form>
                    </div>

                    <div class="col-md-2 text-md-end">
                        <strong class="text-dark">Rp{{ number_format($item['subtotal'], 0, ',', '.') }}</strong>
                    </div>

                    <div class="col-md-2 text-md-end">
                        <form method="POST" action="{{ route('cart.destroy', $item['product']) }}">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-qdj-danger" onclick="return confirm('Hapus item ini dari keranjang?')">Hapus</button>
                        </form>
                    </div>
                </div>
            @endforeach

            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center pt-4 mt-2 gap-3">
                <a href="{{ route('products.index') }}" class="btn btn-sm btn-qdj-outline">
                    ← Tambah Produk Lain
                </a>
                <div class="text-sm-end">
                    <div class="small text-muted">Subtotal Produk:</div>
                    <div class="fs-4 fw-bold" style="color: var(--qdj-green-dark);">
                        Rp{{ number_format($total, 0, ',', '.') }}
                    </div>
                    <a class="btn btn-qdj px-4 py-2 mt-2" href="{{ route('checkout.create') }}">
                        Lanjut ke Checkout →
                    </a>
                </div>
            </div>
        </div>
    @else
        <div class="qdj-card p-5 text-center">
            <span style="font-size: 3rem;">🥥</span>
            <h2 class="h5 fw-bold text-dark mt-3">Keranjang Belanja Kosong</h2>
            <p class="text-muted small">Anda belum memilih produk degan jelly segar.</p>
            <a href="{{ route('products.index') }}" class="btn btn-qdj mt-2">
                Buka Katalog Produk
            </a>
        </div>
    @endif
</div>
@endsection
