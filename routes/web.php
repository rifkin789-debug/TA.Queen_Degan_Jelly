<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\BatchController as AdminBatchController;
use App\Http\Controllers\Admin\BusinessProfileController as AdminBusinessProfileController;
use App\Http\Controllers\Admin\FruitController as AdminFruitController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PaymentVerificationController as AdminPaymentVerificationController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\VoucherController as AdminVoucherController;
use App\Http\Controllers\Admin\VoucherPackageController as AdminVoucherPackageController;
use App\Http\Controllers\Admin\VoucherPaymentController as AdminVoucherPaymentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CustomOrderController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderTrackingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentProofController;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\VoucherPaymentController;
use App\Http\Controllers\VoucherRedemptionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'index'])->name('home');
Route::redirect('/beranda', '/');
Route::redirect('/tentang-kami', '/#tentang')->name('about');
Route::get('/produk', [StorefrontController::class, 'catalog'])->name('products.index');
Route::get('/produk/{product:slug}', [StorefrontController::class, 'show'])->name('products.show');

Route::get('/custom', [CustomOrderController::class, 'create'])->name('custom.create');
Route::post('/custom', [CustomOrderController::class, 'store'])->name('custom.store');
Route::get('/voucher-beli', [VoucherController::class, 'create'])->name('vouchers.purchase');
Route::post('/voucher-beli', [VoucherController::class, 'store'])->name('vouchers.purchase.store');
Route::get('/voucher-cek', [VoucherController::class, 'checkForm'])->name('vouchers.check');
Route::post('/voucher-cek', [VoucherController::class, 'check'])->name('vouchers.check.submit');
Route::get('/voucher-tukar', [VoucherRedemptionController::class, 'enterCodeForm'])->name('vouchers.redeem.enter');
Route::post('/voucher-tukar', [VoucherRedemptionController::class, 'enterCode'])->name('vouchers.redeem.lookup');
Route::get('/voucher-tukar/{kode}', [VoucherRedemptionController::class, 'show'])->name('vouchers.redeem.show');
Route::post('/voucher-tukar/{kode}', [VoucherRedemptionController::class, 'store'])->name('vouchers.redeem.store');
Route::get('/voucher-bayar/{voucher}', [VoucherPaymentController::class, 'show'])
    ->middleware('signed')
    ->name('voucher.payments.show');
Route::post('/voucher-bayar/{voucher}', [VoucherPaymentController::class, 'store'])
    ->middleware('signed')
    ->name('voucher.payments.proof.store');
Route::get('/pembayaran-qris/{order}', [PaymentProofController::class, 'show'])
    ->middleware('signed')
    ->name('payments.qris.show');
Route::post('/pembayaran-qris/{order}', [PaymentProofController::class, 'store'])
    ->middleware('signed')
    ->name('payments.qris.proof.store');
Route::get('/lacak-pesanan', [OrderTrackingController::class, 'create'])->name('orders.track');
Route::get('/lihat-pesanan', function (\Illuminate\Http\Request $request) {
    return $request->user() ? redirect()->route('orders.index') : redirect()->route('orders.track');
})->name('orders.view');
Route::post('/lacak-pesanan', [OrderTrackingController::class, 'show'])
    ->middleware('throttle:10,1')
    ->name('orders.track.show');

Route::get('/keranjang', [CartController::class, 'index'])->name('cart.index');
Route::post('/keranjang/{product}', [CartController::class, 'store'])->name('cart.store');
Route::patch('/keranjang/{product}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/keranjang/{product}', [CartController::class, 'destroy'])->name('cart.destroy');

Route::middleware('auth')->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/pembayaran/{order}', [PaymentController::class, 'show'])->name('payments.show');
    Route::post('/pembayaran/{order}', [PaymentController::class, 'uploadProof'])->name('payments.proof.store');
    Route::get('/pembayaran/{order}/bukti', [PaymentController::class, 'viewProof'])->name('payments.proof');
    Route::get('/pesanan-saya', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/pesanan/{order}', [OrderController::class, 'show'])->name('orders.show');
});

// Admin Auth Routes (URL khusus terpisah dari customer)
Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.store');
Route::post('/admin/keluar', [AdminAuthController::class, 'logout'])->name('admin.logout');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::patch('/produk/{produk}/status', [AdminProductController::class, 'toggleStatus'])->name('produk.status');
    Route::post('/produk/{produk}/segarkan-batch', [AdminProductController::class, 'refreshBatch'])->name('produk.refresh-batch');
    Route::resource('produk', AdminProductController::class)->except(['show']);
    Route::get('/batch-produksi', [AdminBatchController::class, 'index'])->name('batches.index');
    Route::post('/batch-produksi', [AdminBatchController::class, 'store'])->name('batches.store');
    Route::patch('/buah/{buah}/status', [AdminFruitController::class, 'toggleStatus'])->name('buah.status');
    Route::resource('buah', AdminFruitController::class)->except(['show', 'destroy']);
    Route::patch('/paket-voucher/{paket_voucher}/status', [AdminVoucherPackageController::class, 'toggleStatus'])
        ->name('paket-voucher.status');
    Route::patch('/paket-voucher/{paket_voucher}/stok', [AdminVoucherPackageController::class, 'adjustStock'])
        ->name('paket-voucher.stock');
    Route::resource('paket-voucher', AdminVoucherPackageController::class)->except(['show', 'destroy']);
    Route::get('/pembayaran', [AdminPaymentVerificationController::class, 'index'])->name('payments.index');
    Route::get('/pembayaran/{payment}/bukti', [AdminPaymentVerificationController::class, 'proof'])->name('payments.proof');
    Route::patch('/pembayaran/{payment}/setujui', [AdminPaymentVerificationController::class, 'approve'])->name('payments.approve');
    Route::patch('/pembayaran/{payment}/tolak', [AdminPaymentVerificationController::class, 'reject'])->name('payments.reject');
    Route::get('/pengaturan-usaha', [AdminBusinessProfileController::class, 'edit'])->name('business-profile.edit');
    Route::put('/pengaturan-usaha', [AdminBusinessProfileController::class, 'update'])->name('business-profile.update');
    Route::get('/pesanan', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/pesanan/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::patch('/pesanan/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.status');
    Route::get('/voucher', [AdminVoucherController::class, 'index'])->name('vouchers.index');
    Route::get('/voucher/{voucher}', [AdminVoucherController::class, 'show'])->name('vouchers.show');
    Route::patch('/voucher/{voucher}/batalkan', [AdminVoucherController::class, 'cancel'])->name('vouchers.cancel');
    Route::get('/voucher-pembayaran', [AdminVoucherPaymentController::class, 'index'])->name('voucher-payments.index');
    Route::get('/voucher-pembayaran/{voucher}', [AdminVoucherPaymentController::class, 'show'])->name('voucher-payments.show');
    Route::get('/voucher-pembayaran/{voucher}/bukti', [AdminVoucherPaymentController::class, 'proof'])->name('voucher-payments.proof');
    Route::patch('/voucher-pembayaran/{voucher}/aktifkan', [AdminVoucherPaymentController::class, 'activate'])->name('voucher-payments.activate');
});

Route::middleware('guest')->group(function () {
    Route::get('/daftar', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/daftar', [AuthController::class, 'register'])->name('register.store');
    Route::get('/masuk', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/masuk', [AuthController::class, 'login'])->name('login.store');
});

Route::post('/keluar', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
