<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessProfile extends Model
{
    protected $table = 'profil_usaha';

    protected $fillable = [
        'nama_toko',
        'alamat',
        'nomor_hp',
        'jam_operasional',
        'biaya_ongkir',
        'harga_dasar_custom',
        'maks_buah_custom',
        'masa_berlaku_voucher_hari',
        'qris_image_path',
        'merchant_name',
        'kode_pembayaran_berikutnya',
    ];

    protected function casts(): array
    {
        return [
            'biaya_ongkir' => 'integer',
            'harga_dasar_custom' => 'integer',
            'maks_buah_custom' => 'integer',
            'masa_berlaku_voucher_hari' => 'integer',
            'kode_pembayaran_berikutnya' => 'integer',
        ];
    }
}
