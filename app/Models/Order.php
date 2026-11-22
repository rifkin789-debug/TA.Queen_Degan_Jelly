<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'order_code', 'customer_name', 'phone', 'address', 'notes',
        'subtotal', 'shipping_cost', 'total', 'payment_status', 'order_status',
        'courier', 'tracking_number', 'midtrans_order_id', 'type', 'fulfillment_method',
        'jumlah_batok', 'id_voucher', 'unique_code', 'stock_returned_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'shipping_cost' => 'integer',
            'total' => 'integer',
            'jumlah_batok' => 'integer',
            'unique_code' => 'integer',
            'stock_returned_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function customFruits(): HasMany
    {
        return $this->hasMany(OrderFruit::class, 'id_pesanan');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest();
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'id_voucher');
    }

    public function orderStatusLabel(): string
    {
        return match ($this->order_status) {
            'menunggu_pembayaran' => 'Menunggu Pembayaran',
            'menunggu_verifikasi' => 'Menunggu Verifikasi',
            'sedang_disiapkan' => 'Sedang Disiapkan (Diproses)',
            'dikirim' => 'Dikirim',
            'pesanan_diterima', 'diterima' => 'Pesanan Diterima (Disetujui)',
            'dibatalkan' => 'Dibatalkan',
            default => ucfirst(str_replace('_', ' ', (string) $this->order_status)),
        };
    }

    public function orderStatusBadgeClass(): string
    {
        return match ($this->order_status) {
            'menunggu_pembayaran', 'menunggu_verifikasi' => 'badge-status-pending',
            'sedang_disiapkan' => 'badge-status-preparing',
            'dikirim' => 'badge-status-shipping',
            'pesanan_diterima', 'diterima' => 'badge-status-accepted',
            'dibatalkan' => 'badge-status-cancelled',
            default => 'badge-qdj-available',
        };
    }

    public function orderStatusIcon(): string
    {
        return match ($this->order_status) {
            'menunggu_pembayaran' => '⏳',
            'menunggu_verifikasi' => '🔍',
            'sedang_disiapkan' => '🥣',
            'dikirim' => '🛵',
            'pesanan_diterima', 'diterima' => '✅',
            'dibatalkan' => '✕',
            default => '📦',
        };
    }

    public function paymentStatusLabel(): string
    {
        return match ($this->payment_status) {
            'pending' => 'Menunggu Pembayaran',
            'pending_verification' => 'Menunggu Verifikasi',
            'paid' => 'Dibayar',
            'rejected' => 'Ditolak',
            'expired' => 'Kedaluwarsa',
            'cancelled' => 'Dibatalkan',
            default => ucfirst(str_replace('_', ' ', (string) $this->payment_status)),
        };
    }
}
