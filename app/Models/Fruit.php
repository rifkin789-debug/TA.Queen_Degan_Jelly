<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fruit extends Model
{
    protected $table = 'buah';

    protected $fillable = ['nama_buah', 'harga_porsi', 'stok_porsi', 'aktif'];

    protected function casts(): array
    {
        return [
            'harga_porsi' => 'integer',
            'stok_porsi' => 'integer',
            'aktif' => 'boolean',
        ];
    }

    public function orderFruits(): HasMany
    {
        return $this->hasMany(OrderFruit::class, 'id_buah');
    }
}
