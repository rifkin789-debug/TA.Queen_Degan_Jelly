@extends('layouts.app')

@section('title', 'Custom Degan Jelly | Queen Degan Jelly')

@push('styles')
<style>
    .fruit-option { cursor: pointer; transition: all .15s ease; border: 1.5px solid var(--qdj-card-border); border-radius: 14px; background: #fff; }
    .fruit-option:hover { border-color: var(--qdj-green-dark); background-color: var(--qdj-mint-soft); }
    .fruit-option:has(input:checked) { border-color: var(--qdj-green-dark); background-color: var(--qdj-mint); box-shadow: 0 0 0 2px rgba(19, 75, 53, 0.2); }
</style>
@endpush

@section('content')
<div class="container py-4">
    @php($oldFruitIds = is_array(old('buah')) ? old('buah') : [])
    <div class="mb-4">
        <span class="badge-qdj-available mb-2">🥥 Kustomisasi Mandiri</span>
        <h1 class="display-6 fw-bold mb-1 text-dark">Racik Degan Jelly Sendiri</h1>
        <p class="text-muted mb-0">Pilih buah segar untuk setiap batok kelapa muda. Tambahan harga isian dihitung otomatis.</p>
    </div>

    @if($fruits->isEmpty())
        <div class="alert alert-info rounded-4">
            Pilihan buah belum tersedia. Silakan kembali lagi setelah stok buah diperbarui.
        </div>
    @else
        <form method="POST" action="{{ route('custom.store') }}" class="row g-4" id="custom-order-form">
            @csrf
            <div class="col-lg-7">
                <!-- 1. Pilih Buah -->
                <div class="qdj-card p-4 mb-4">
                    <h2 class="h5 fw-bold text-dark mb-1">1. Pilih buah</h2>
                    <p class="small text-muted mb-3">Pilih 1 sampai {{ $maximumFruits }} jenis buah. Setiap jenis dihitung satu porsi per batok.</p>
                    <div class="row g-3">
                        @foreach($fruits as $fruit)
                            <div class="col-md-6">
                                <label class="fruit-option p-3 h-100 d-block">
                                    <span class="d-flex align-items-start gap-2">
                                        <input
                                            class="form-check-input fruit-checkbox mt-1"
                                            type="checkbox"
                                            name="buah[]"
                                            value="{{ $fruit->id }}"
                                            data-price="{{ $fruit->harga_porsi }}"
                                            data-stock="{{ $fruit->stok_porsi }}"
                                            @checked(in_array($fruit->id, $oldFruitIds))
                                        >
                                        <span>
                                            <strong class="d-block text-dark">{{ $fruit->nama_buah }}</strong>
                                            <span class="d-block small text-muted">+Rp{{ number_format($fruit->harga_porsi, 0, ',', '.') }} / porsi</span>
                                            <span class="d-block small text-muted">Stok {{ $fruit->stok_porsi }} porsi</span>
                                        </span>
                                    </span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <div class="small text-danger mt-3" id="fruit-limit-message" aria-live="polite"></div>
                </div>

                <!-- 2. Data Pemesanan -->
                <div class="qdj-card p-4">
                    <h2 class="h5 fw-bold text-dark mb-3">2. Data pemesanan</h2>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark" for="customer_name">Nama</label>
                        <input class="form-control" id="customer_name" name="customer_name" value="{{ old('customer_name') }}" maxlength="120" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark" for="phone">Nomor HP</label>
                        <input class="form-control" id="phone" name="phone" type="tel" value="{{ old('phone') }}" maxlength="30" autocomplete="tel" required>
                    </div>
                    <fieldset class="mb-3">
                        <legend class="form-label small fw-semibold text-dark">Metode pemenuhan</legend>
                        <div class="d-flex gap-4">
                            <div class="form-check">
                                <input class="form-check-input" id="fulfillment_pickup" type="radio" name="fulfillment_method" value="ambil" @checked(old('fulfillment_method', 'ambil') === 'ambil')>
                                <label class="form-check-label small" for="fulfillment_pickup">Ambil sendiri</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" id="fulfillment_delivery" type="radio" name="fulfillment_method" value="antar" @checked(old('fulfillment_method') === 'antar')>
                                <label class="form-check-label small" for="fulfillment_delivery">Antar</label>
                            </div>
                        </div>
                    </fieldset>
                    <div class="mb-3" id="delivery-address-field" style="{{ old('fulfillment_method', 'ambil') === 'ambil' ? 'display: none;' : '' }}">
                        <label class="form-label small fw-semibold text-dark" for="address">Alamat antar</label>
                        <textarea class="form-control" id="address" name="address" rows="3" maxlength="1000">{{ old('address') }}</textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-semibold text-dark" for="notes">Catatan (opsional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2" maxlength="500">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Ringkasan Harga -->
            <div class="col-lg-5">
                <div class="qdj-card p-4 position-sticky" style="top: 5.5rem;">
                    <h2 class="h5 fw-bold text-dark mb-3">Ringkasan harga</h2>
                    <div class="d-flex justify-content-between gap-3 py-2 border-bottom small text-muted">
                        <span>Harga per batok</span>
                        <strong class="text-dark" id="price-per-batok">Rp{{ number_format($basePrice, 0, ',', '.') }}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center gap-3 py-2 border-bottom small text-muted">
                        <span>Jumlah batok</span>
                        <div style="width: 100px;">
                            <input class="form-control form-control-sm text-end" type="number" id="jumlah_batok" name="jumlah_batok" min="1" value="{{ old('jumlah_batok', 1) }}" required>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between gap-3 py-2 border-bottom small text-muted">
                        <span>Subtotal</span>
                        <strong class="text-dark" id="custom-subtotal">Rp0</strong>
                    </div>
                    <div class="d-flex justify-content-between gap-3 py-2 border-bottom small text-muted">
                        <span>Ongkir antar</span>
                        <strong class="text-dark" id="custom-shipping">Rp0</strong>
                    </div>
                    <div class="d-flex justify-content-between gap-3 py-2 border-bottom small text-muted">
                        <span>Kode unik</span>
                        <strong class="text-dark">Rp{{ number_format($paymentCodePreview, 0, ',', '.') }}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center gap-3 py-3 fs-5">
                        <strong class="text-dark">Total bayar</strong>
                        <strong class="fs-4" style="color: var(--qdj-green-dark);" id="custom-total">Rp{{ number_format($paymentCodePreview, 0, ',', '.') }}</strong>
                    </div>
                    <p class="small text-muted mb-4">Harga ditampilkan langsung dan diverifikasi ulang berdasarkan harga serta stok terbaru saat pesanan dibuat.</p>
                    <button class="btn btn-qdj btn-lg w-100 py-3" type="submit">Buat Pesanan</button>
                </div>
            </div>
        </form>
    @endif
</div>
@endsection

@push('scripts')
<script>
(() => {
    const form = document.getElementById('custom-order-form');
    if (!form) return;

    const basePrice = {{ $basePrice }};
    const shippingCost = {{ $shippingCost }};
    const paymentCode = {{ $paymentCodePreview }};
    const maxFruits = {{ $maximumFruits }};

    const fruitCheckboxes = Array.from(form.querySelectorAll('.fruit-checkbox'));
    const fruitLimitMessage = document.getElementById('fruit-limit-message');
    const pricePerBatokElement = document.getElementById('price-per-batok');
    const subtotalElement = document.getElementById('custom-subtotal');
    const shippingElement = document.getElementById('custom-shipping');
    const totalElement = document.getElementById('custom-total');
    const batokInput = document.getElementById('jumlah_batok');
    const addressField = document.getElementById('delivery-address-field');
    const addressTextarea = document.getElementById('address');
    const fulfillmentPickup = document.getElementById('fulfillment_pickup');
    const fulfillmentDelivery = document.getElementById('fulfillment_delivery');

    const formatCurrency = (amount) => 'Rp' + amount.toLocaleString('id-ID');

    const selectedFruits = () => fruitCheckboxes.filter((cb) => cb.checked);

    const updateFulfillmentVisibility = () => {
        const isDelivery = fulfillmentDelivery?.checked;
        if (addressField) {
            addressField.style.display = isDelivery ? 'block' : 'none';
        }
        if (addressTextarea) {
            addressTextarea.required = Boolean(isDelivery);
        }
    };

    const updateCalculations = () => {
        const selected = selectedFruits();
        const fruitsTotalPerBatok = selected.reduce((sum, cb) => sum + Number(cb.dataset.price || 0), 0);
        const perBatokPrice = basePrice + fruitsTotalPerBatok;
        const batokCount = Math.max(1, Number(batokInput?.value || 1));
        const subtotal = perBatokPrice * batokCount;
        const shipping = fulfillmentDelivery?.checked ? shippingCost : 0;
        const total = subtotal + shipping + paymentCode;

        if (pricePerBatokElement) pricePerBatokElement.textContent = formatCurrency(perBatokPrice);
        if (subtotalElement) subtotalElement.textContent = formatCurrency(subtotal);
        if (shippingElement) shippingElement.textContent = formatCurrency(shipping);
        if (totalElement) totalElement.textContent = formatCurrency(total);
    };

    fruitCheckboxes.forEach((cb) => {
        cb.addEventListener('change', () => {
            const selected = selectedFruits();
            if (selected.length > maxFruits) {
                cb.checked = false;
                if (fruitLimitMessage) fruitLimitMessage.textContent = 'Maksimal ' + maxFruits + ' jenis buah pilihan.';
            } else {
                if (fruitLimitMessage) fruitLimitMessage.textContent = '';
            }
            updateCalculations();
        });
    });

    batokInput?.addEventListener('input', updateCalculations);
    fulfillmentPickup?.addEventListener('change', () => {
        updateFulfillmentVisibility();
        updateCalculations();
    });
    fulfillmentDelivery?.addEventListener('change', () => {
        updateFulfillmentVisibility();
        updateCalculations();
    });

    updateFulfillmentVisibility();
    updateCalculations();
})();
</script>
@endpush
