<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderFruit extends Model
{
    protected $table = 'pesanan_buah';

    protected $fillable = ['id_pesanan', 'id_buah', 'nama_buah', 'harga_porsi', 'jumlah_porsi'];

    protected function casts(): array
    {
        return [
            'harga_porsi' => 'integer',
            'jumlah_porsi' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'id_pesanan');
    }

    public function fruit(): BelongsTo
    {
        return $this->belongsTo(Fruit::class, 'id_buah');
    }
}
