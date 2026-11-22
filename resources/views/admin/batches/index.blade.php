@extends('layouts.admin')

@section('title', 'Kelola Batch Produksi | Queen Degan Jelly')

@section('admin-content')
<div class="mb-4">
    <h1 class="h3 fw-bold text-dark mb-1">Kelola Batch Produksi</h1>
    <p class="text-muted small">Catat dan pantau kesegaran batch produksi degan jelly harian dapur Muncar.</p>
</div>

<div class="row g-4 align-items-start">
    <!-- Kolom Kiri: Form Catat Batch Baru -->
    <div class="col-lg-4">
        <div class="qdj-card p-4">
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="fs-4">📋</span>
                <h2 class="h5 fw-bold text-dark mb-0">Catat Batch Baru</h2>
            </div>
            <p class="text-muted small mb-3">
                Tambahkan batch produksi baru. Jumlah batch akan otomatis menambah stok produk terkait.
            </p>

            <form method="POST" action="{{ route('admin.batches.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark" for="product_id">Produk</label>
                    <select class="form-select" id="product_id" name="product_id" required>
                        <option value="">Pilih Produk...</option>
                        @foreach($products as $prod)
                            <option value="{{ $prod->id }}" @selected(old('product_id') == $prod->id)>
                                {{ $prod->name }} (Stok saat ini: {{ $prod->stock }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark" for="produced_at">Waktu Produksi</label>
                    <input type="datetime-local"
                           class="form-control"
                           id="produced_at"
                           name="produced_at"
                           value="{{ old('produced_at', now()->format('Y-m-d\TH:i')) }}"
                           required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark" for="quantity">Jumlah Produksi (Batok)</label>
                    <input type="number"
                           min="1"
                           class="form-control"
                           id="quantity"
                           name="quantity"
                           value="{{ old('quantity', 30) }}"
                           placeholder="30"
                           required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark" for="notes">Catatan Batch (Opsional)</label>
                    <textarea class="form-control"
                              id="notes"
                              name="notes"
                              rows="2"
                              placeholder="Misal: Batch pagi, panenan kelapa Muncar Barat">{{ old('notes') }}</textarea>
                </div>

                <div class="p-3 rounded-4 mb-3" style="background-color: var(--qdj-mint); border: 1px solid rgba(19, 75, 53, 0.15);">
                    <div class="small text-muted">
                        <strong class="d-block text-dark mb-1">ℹ️ Kebijakan Kesegaran:</strong>
                        Batch dengan sisa kesegaran kurang dari 12 jam otomatis keluar dari katalog publik untuk menjamin kepuasan pelanggan.
                    </div>
                </div>

                <button class="btn btn-qdj w-100 py-2" type="submit">
                    Simpan Batch
                </button>
            </form>
        </div>
    </div>

    <!-- Kolom Kanan: Tabel Batch Produksi -->
    <div class="col-lg-8">
        <div class="qdj-card overflow-hidden">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                <h2 class="h5 fw-bold text-dark mb-0">Daftar Batch Produksi</h2>
                <span class="small text-muted">Total: {{ $batches->total() }} Batch</span>
            </div>

            <div class="table-responsive">
                <table class="table admin-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Batch</th>
                            <th scope="col">Produk</th>
                            <th scope="col">Waktu Produksi</th>
                            <th scope="col">Jumlah</th>
                            <th scope="col">Sisa Stok</th>
                            <th scope="col">Kesegaran</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($batches as $batch)
                            @php
                                $status = $batch->freshnessStatus();
                                $remaining = $batch->remainingHours();
                            @endphp
                            <tr>
                                <td>
                                    <strong class="text-dark small d-block">{{ $batch->batch_code }}</strong>
                                    @if($batch->notes)
                                        <span class="text-muted small" style="font-size: 0.75rem;">{{ $batch->notes }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="fw-semibold text-dark">{{ $batch->product?->name ?? 'Produk Dihapus' }}</span>
                                </td>
                                <td>
                                    <span class="small text-muted">
                                        {{ $batch->produced_at->format('d/m/Y H:i') }}
                                    </span>
                                </td>
                                <td>
                                    <span class="small">{{ $batch->initial_quantity }}</span>
                                </td>
                                <td>
                                    <span class="fw-bold {{ $batch->remaining_quantity > 0 ? 'text-dark' : 'text-danger' }}">
                                        {{ $batch->remaining_quantity }}
                                    </span>
                                </td>
                                <td>
                                    @if($status === 'Sangat Segar')
                                        <span class="badge-qdj-fresh-very">Sangat Segar ({{ $remaining }}j)</span>
                                    @elseif($status === 'Segar')
                                        <span class="badge-qdj-fresh">Segar ({{ $remaining }}j)</span>
                                    @elseif($status === 'Segera Dikonsumsi')
                                        <span class="badge-qdj-fresh-warn">Segera Dikonsumsi ({{ $remaining }}j)</span>
                                    @else
                                        <span class="badge-qdj-expired">Ditarik (< 12j)</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    Belum ada batch produksi yang dicatat.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginasi -->
            @if($batches->hasPages())
                <div class="p-3 border-top d-flex justify-content-end">
                    {{ $batches->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
