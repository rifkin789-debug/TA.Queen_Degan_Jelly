@extends('layouts.admin')

@section('title', 'Verifikasi Pembayaran | Panel Admin Queen Degan Jelly')

@section('admin-content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h4 fw-bold text-dark mb-1">Verifikasi Pembayaran QRIS</h1>
        <p class="text-muted small mb-0">Periksa bukti transfer pelanggan dan setujui untuk memproses pesanan.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('admin.business-profile.edit') }}" class="btn btn-sm btn-qdj-outline">
            ⚙️ Pengaturan QRIS
        </a>
    </div>
</div>

<!-- Tabs Status Filter -->
<div class="d-flex gap-2 mb-4 border-bottom pb-2">
    <a href="{{ route('admin.payments.index', ['status' => 'pending_verification']) }}"
       class="btn btn-sm {{ $status === 'pending_verification' ? 'btn-qdj' : 'btn-outline-secondary' }} rounded-pill px-3">
        Menunggu Verifikasi
        <span class="badge {{ $status === 'pending_verification' ? 'bg-warning text-dark' : 'bg-secondary' }} rounded-pill ms-1">
            {{ $pendingCount }}
        </span>
    </a>
    <a href="{{ route('admin.payments.index', ['status' => 'paid']) }}"
       class="btn btn-sm {{ $status === 'paid' ? 'btn-qdj' : 'btn-outline-secondary' }} rounded-pill px-3">
        Telah Disetujui ({{ $paidCount }})
    </a>
    <a href="{{ route('admin.payments.index', ['status' => 'rejected']) }}"
       class="btn btn-sm {{ $status === 'rejected' ? 'btn-qdj' : 'btn-outline-secondary' }} rounded-pill px-3">
        Ditolak ({{ $rejectedCount }})
    </a>
</div>

<!-- Tabel Pembayaran -->
<div class="qdj-card overflow-hidden shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-mint">
                <tr>
                    <th scope="col" style="width: 100px;">Bukti</th>
                    <th scope="col">Kode & Tipe</th>
                    <th scope="col">Pelanggan</th>
                    <th scope="col">Total Bayar</th>
                    <th scope="col">Waktu Unggah</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-end" style="width: 190px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $p)
                    <tr>
                        <!-- Bukti Thumbnail -->
                        <td>
                            @if($p->proof_path)
                                <a href="{{ route('admin.payments.proof', $p) }}" target="_blank" title="Klik untuk perbesar">
                                    <img src="{{ route('admin.payments.proof', $p) }}" alt="Bukti Transfer" class="rounded-3 border shadow-sm" style="width: 68px; height: 68px; object-fit: cover;">
                                </a>
                            @else
                                <span class="badge bg-light text-muted border">Belum ada</span>
                            @endif
                        </td>

                        <!-- Kode & Tipe -->
                        <td>
                            @if($p->order)
                                <a href="{{ route('admin.orders.show', $p->order) }}" class="fw-bold text-dark text-decoration-none">
                                    {{ $p->order->order_code }}
                                </a>
                                <span class="d-block small text-muted">Pesanan Produk ({{ $p->order->items->count() }} item)</span>
                            @elseif($p->voucher)
                                <a href="{{ route('admin.vouchers.show', $p->voucher) }}" class="fw-bold text-dark text-decoration-none">
                                    {{ $p->voucher->kode_voucher ?? ('Voucher #' . $p->voucher->id) }}
                                </a>
                                <span class="d-block small text-muted">🎁 Beli Voucher Gift ({{ $p->voucher->nama_paket }})</span>
                            @else
                                <span class="fw-bold text-dark">#{{ $p->id }}</span>
                            @endif
                        </td>

                        <!-- Pelanggan -->
                        <td>
                            <strong class="d-block text-dark small">
                                {{ $p->order?->customer_name ?? $p->voucher?->nama_pengirim ?? 'Pelanggan' }}
                            </strong>
                            <span class="small text-muted d-block">
                                {{ $p->order?->phone ?? $p->voucher?->nomor_hp_pengirim ?? '-' }}
                            </span>
                            @if($p->customer_notes)
                                <span class="small text-muted fst-italic d-block mt-1">
                                    "{{ Str::limit($p->customer_notes, 40) }}"
                                </span>
                            @endif
                        </td>

                        <!-- Total Bayar -->
                        <td>
                            <strong class="fs-6 text-dark" style="color: var(--qdj-green-dark) !important;">
                                Rp{{ number_format($p->gross_amount, 0, ',', '.') }}
                            </strong>
                        </td>

                        <!-- Waktu Unggah -->
                        <td class="small text-muted">
                            {{ $p->proof_uploaded_at ? $p->proof_uploaded_at->translatedFormat('d M Y, H:i') : '-' }}
                        </td>

                        <!-- Status -->
                        <td>
                            @if($p->transaction_status === 'paid')
                                <span class="badge-qdj-fresh-very">Disetujui</span>
                            @elseif($p->transaction_status === 'rejected')
                                <span class="badge-qdj-expired">Ditolak</span>
                                @if($p->rejection_reason)
                                    <span class="d-block small text-muted mt-1" style="font-size: 0.75rem;">
                                        {{ Str::limit($p->rejection_reason, 30) }}
                                    </span>
                                @endif
                            @elseif($p->transaction_status === 'pending_verification')
                                <span class="badge-qdj-fresh">Menunggu Verifikasi</span>
                            @else
                                <span class="badge-qdj-fresh-warn">{{ $p->transaction_status }}</span>
                            @endif
                        </td>

                        <!-- Aksi -->
                        <td class="text-end">
                            @if($p->transaction_status === 'pending_verification')
                                <div class="d-flex align-items-center justify-content-end gap-1">
                                    <!-- Setujui -->
                                    <form method="POST" action="{{ route('admin.payments.approve', $p) }}" onsubmit="return confirm('Apakah Anda yakin ingin MENYETUJUI pembayaran ini?');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-qdj py-1 px-2" style="font-size: 0.8rem;">
                                            ✓ Setujui
                                        </button>
                                    </form>

                                    <!-- Tolak (Buka Modal) -->
                                    <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2" style="font-size: 0.8rem;" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $p->id }}">
                                        ✕ Tolak
                                    </button>
                                </div>
                            @else
                                @if($p->proof_path)
                                    <a href="{{ route('admin.payments.proof', $p) }}" target="_blank" class="btn btn-sm btn-outline-secondary py-1 px-2" style="font-size: 0.8rem;">
                                        Lihat Bukti
                                    </a>
                                @else
                                    <span class="small text-muted">—</span>
                                @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            Tidak ada data pembayaran pada status ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginasi -->
    @if($payments->hasPages())
        <div class="p-3 border-top">
            {{ $payments->links() }}
        </div>
    @endif
</div>

<!-- Modal Tolak (diletakkan di luar card/table agar tidak terpotong overflow-hidden) -->
@foreach($payments as $p)
    @if($p->transaction_status === 'pending_verification')
        <div class="modal fade" id="rejectModal{{ $p->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog text-start">
                <div class="modal-content">
                    <form method="POST" action="{{ route('admin.payments.reject', $p) }}">
                        @csrf
                        @method('PATCH')
                        <div class="modal-header">
                            <h5 class="modal-title h6 fw-bold">Tolak Pembayaran</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="small text-muted mb-2">
                                Masukkan alasan penolakan agar pelanggan mengetahui apa yang perlu diperbaiki saat mengunggah ulang bukti transfer:
                            </p>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-dark">Alasan Penolakan</label>
                                <textarea name="rejection_reason" class="form-control" rows="3" placeholder="Contoh: Nominal transfer kurang dari total tagihan, atau foto bukti buram." required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-sm btn-danger">Tolak Pembayaran</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endforeach
@endsection
