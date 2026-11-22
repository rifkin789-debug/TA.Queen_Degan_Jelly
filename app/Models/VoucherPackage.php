<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VoucherPackage extends Model
{
    protected $table = 'paket_voucher';

    protected $fillable = [
        'nama_paket',
        'harga',
        'jumlah_batok',
        'maks_buah',
        'stok',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'harga' => 'integer',
            'jumlah_batok' => 'integer',
            'maks_buah' => 'integer',
            'stok' => 'integer',
            'aktif' => 'boolean',
        ];
    }

    public function isAvailable(): bool
    {
        return $this->aktif && $this->stok > 0;
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class, 'id_paket');
    }
}
