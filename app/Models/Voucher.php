<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Voucher extends Model
{
    public const STATUS_WAITING_PAYMENT = 'Menunggu Pembayaran';

    public const STATUS_WAITING_VERIFICATION = 'Menunggu Verifikasi';

    public const STATUS_ACTIVE = 'Aktif';

    public const STATUS_USED = 'Dipakai';

    public const STATUS_EXPIRED = 'Kedaluwarsa';

    public const STATUS_CANCELLED = 'Dibatalkan';

    protected $table = 'voucher';

    protected $fillable = [
        'id_paket',
        'nama_paket',
        'harga',
        'jumlah_batok',
        'maks_buah',
        'nama_pengirim',
        'nomor_hp_pengirim',
        'nama_penerima',
        'pesan_ucapan',
        'kode_voucher',
        'status',
        'berlaku_sampai',
        'dipakai_pada',
        'stock_returned_at',
    ];

    protected function casts(): array
    {
        return [
            'harga' => 'integer',
            'jumlah_batok' => 'integer',
            'maks_buah' => 'integer',
            'berlaku_sampai' => 'datetime',
            'dipakai_pada' => 'datetime',
            'stock_returned_at' => 'datetime',
        ];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(VoucherPackage::class, 'id_paket');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function redemptionOrder(): HasOne
    {
        return $this->hasOne(Order::class, 'id_voucher');
    }
}
