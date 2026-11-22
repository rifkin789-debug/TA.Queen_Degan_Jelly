@extends('layouts.app')

@section('title', 'Tukar Voucher Gift | Queen Degan Jelly')

@section('content')
<div class="container py-4" style="max-width: 860px;">
    @php($oldFruitIds = is_array(old('buah')) ? old('buah') : [])

    @if(isset($error) && $error)
        <div class="alert alert-warning rounded-4 mb-4">{{ $error }}</div>
        @if(! isset($voucher) || ! $voucher)
            <div class="text-center">
                <a class="btn btn-qdj-outline" href="{{ route('vouchers.redeem.enter') }}">Masukkan kode voucher</a>
            </div>
        @endif
    @endif

    @if(isset($voucher) && $voucher)
        <!-- Greeting Card Dari Pengirim -->
        <div class="qdj-card p-4 mb-4" style="background-color: var(--qdj-mint); border-color: rgba(19, 75, 53, 0.2);">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="badge-qdj-voucher">🎁 Voucher Hadiah</span>
                <span class="small fw-semibold text-muted">{{ $voucher->jumlah_batok }} Batok Degan Jelly</span>
            </div>
            <h1 class="h4 fw-bold text-dark mb-1">Voucher Hadiah</h1>
            <p class="mb-2 text-dark">
                Dari: <strong>{{ $voucher->nama_pengirim }}</strong>
            </p>
            @if($voucher->pesan_ucapan)
                <div class="p-3 bg-white rounded-3 fst-italic small text-dark my-2 shadow-sm">
                    "{{ $voucher->pesan_ucapan }}"
                </div>
            @endif
            <div class="small text-muted mt-2">
                * Voucher ini sudah dibayar penuh oleh pengirim. Anda tidak perlu membayar biaya apa pun!
            </div>
        </div>
    @endif

    @if(isset($voucher) && $voucher && $voucher->status === \App\Models\Voucher::STATUS_ACTIVE)
        @if($fruits->isEmpty())
            <div class="alert alert-info rounded-4">Belum ada buah dengan stok yang cukup untuk voucher ini.</div>
        @else
            <form method="POST" action="{{ route('vouchers.redeem.store', ['kode' => $voucher->kode_voucher]) }}" id="voucher-redemption-form">
                @csrf

                <!-- 1. Pilih Buah (Kustomisasi) -->
                <div class="qdj-card p-4 mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <h2 class="h5 fw-bold text-dark mb-0">Pilih Isian Buah (Untuk {{ $voucher->jumlah_batok }} Batok)</h2>
                        <span class="small text-muted" id="voucherFruitCounter">0 / {{ $voucher->maks_buah }} dipilih</span>
                    </div>
                    <p class="small text-muted mb-3">
                        Pilih 1 sampai {{ $voucher->maks_buah }} jenis buah favorit Anda. Semua buah di bawah tersedia segar.
                    </p>

                    <div class="d-flex flex-wrap gap-2" id="voucherChipsContainer">
                        @foreach($fruits as $fruit)
                            <label class="fruit-chip {{ in_array($fruit->id, $oldFruitIds) ? 'selected' : '' }}" data-id="{{ $fruit->id }}">
                                <input class="form-check-input d-none voucher-fruit-checkbox"
                                       type="checkbox"
                                       name="buah[]"
                                       value="{{ $fruit->id }}"
                                       @checked(in_array($fruit->id, $oldFruitIds))>
                                <span>{{ $fruit->nama_buah }}</span>
                                <span class="badge bg-light text-muted border" style="font-size: 0.72rem;">Tersedia</span>
                            </label>
                        @endforeach
                    </div>
                    <div class="small text-danger mt-3 d-none" id="voucher-fruit-limit" aria-live="polite">
                        Maksimal {{ $voucher->maks_buah }} jenis buah pilihan telah tercapai.
                    </div>
                </div>

                <!-- 2. Data Penerima & Pengiriman -->
                <div class="qdj-card p-4 mb-4">
                    <h2 class="h5 fw-bold text-dark mb-3">Data Penerima & Pengiriman</h2>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark" for="customer_name">Nama Lengkap</label>
                            <input class="form-control" id="customer_name" name="customer_name" value="{{ old('customer_name') }}" maxlength="120" placeholder="Nama Anda" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark" for="phone">Nomor WhatsApp</label>
                            <input class="form-control" id="phone" name="phone" type="tel" value="{{ old('phone') }}" maxlength="30" placeholder="08xxxxxxxxxx" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark d-block">Metode Pemenuhan</label>
                        <div class="d-flex gap-4">
                            <div class="form-check">
                                <input class="form-check-input" id="fulfillment_pickup" type="radio" name="fulfillment_method" value="ambil" @checked(old('fulfillment_method', 'ambil') === 'ambil') required>
                                <label class="form-check-label small" for="fulfillment_pickup">Ambil Sendiri di Dapur Muncar</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" id="fulfillment_delivery" type="radio" name="fulfillment_method" value="antar" @checked(old('fulfillment_method') === 'antar')>
                                <label class="form-check-label small" for="fulfillment_delivery">Diantar ke Alamat (Area Mitra)</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3" id="delivery-address-group" style="{{ old('fulfillment_method', 'ambil') === 'ambil' ? 'display: none;' : '' }}">
                        <label class="form-label small fw-semibold text-dark" for="address">Alamat Antar <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="address" name="address" rows="3" maxlength="1000" placeholder="Masukkan alamat lengkap pengantaran">{{ old('address') }}</textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-dark" for="notes">Catatan Tambahan (Opsional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2" maxlength="500" placeholder="Contoh: Tolong taruh di depan pintu.">{{ old('notes') }}</textarea>
                    </div>

                    <div class="p-3 rounded-4 bg-light d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <span class="small text-muted d-block">Total Pembayaran Anda:</span>
                            <span class="fs-4 fw-bold text-success">Rp 0 (GRATIS)</span>
                        </div>
                        <span class="badge-qdj-available">Sudah Dibayar Pengirim</span>
                    </div>

                    <button class="btn btn-qdj w-100 py-3 fs-6" type="submit">
                        Tukar Voucher dan Buat Pesanan
                    </button>
                </div>
            </form>
        @endif
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const maxFruits = {{ isset($voucher) && $voucher ? $voucher->maks_buah : 3 }};
    const chips = document.querySelectorAll('#voucherChipsContainer .fruit-chip');
    const counter = document.getElementById('voucherFruitCounter');
    const limitAlert = document.getElementById('voucher-fruit-limit');

    function updateCounter() {
        const checked = document.querySelectorAll('.voucher-fruit-checkbox:checked').length;
        if (counter) counter.textContent = `${checked} / ${maxFruits} dipilih`;
    }

    chips.forEach(chip => {
        chip.addEventListener('click', function () {
            const checkbox = this.querySelector('input[type="checkbox"]');
            if (!checkbox) return;

            const selectedCount = document.querySelectorAll('.voucher-fruit-checkbox:checked').length;

            if (!checkbox.checked) {
                if (selectedCount >= maxFruits) {
                    if (limitAlert) limitAlert.classList.remove('d-none');
                    return;
                }
                checkbox.checked = true;
                this.classList.add('selected');
                if (limitAlert) limitAlert.classList.add('d-none');
            } else {
                checkbox.checked = false;
                this.classList.remove('selected');
                if (limitAlert) limitAlert.classList.add('d-none');
            }

            updateCounter();
        });
    });

    updateCounter();

    // Toggle visibilitas kolom alamat sesuai metode pemenuhan (ambil di tempat / diantar)
    const fulfillmentPickup = document.getElementById('fulfillment_pickup');
    const fulfillmentDelivery = document.getElementById('fulfillment_delivery');
    const deliveryAddressGroup = document.getElementById('delivery-address-group');
    const addressInput = document.getElementById('address');

    function toggleAddressVisibility() {
        if (!deliveryAddressGroup) return;
        const isDelivery = fulfillmentDelivery && fulfillmentDelivery.checked;
        if (isDelivery) {
            deliveryAddressGroup.style.display = 'block';
            if (addressInput) {
                addressInput.setAttribute('required', 'required');
            }
        } else {
            deliveryAddressGroup.style.display = 'none';
            if (addressInput) {
                addressInput.removeAttribute('required');
                addressInput.value = '';
            }
        }
    }

    if (fulfillmentPickup) {
        fulfillmentPickup.addEventListener('change', toggleAddressVisibility);
    }
    if (fulfillmentDelivery) {
        fulfillmentDelivery.addEventListener('change', toggleAddressVisibility);
    }

    toggleAddressVisibility();
});
</script>
@endpush
