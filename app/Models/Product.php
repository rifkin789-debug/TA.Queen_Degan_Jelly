<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'jumlah_batok',
        'stock',
        'shelf_life_hours',
        'image',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'jumlah_batok' => 'integer',
            'stock' => 'integer',
            'shelf_life_hours' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class)->latest('produced_at');
    }

    public function latestBatch(): HasOne
    {
        return $this->hasOne(Batch::class)->latestOfMany('produced_at');
    }

    public function isPackage(): bool
    {
        return $this->category && (
            in_array($this->category->slug, ['paket', 'paket-mix'], true) ||
            stripos($this->category->name, 'paket') !== false
        );
    }

    public function packageBatokCount(): int
    {
        return max(1, (int) ($this->jumlah_batok ?: 1));
    }

    /**
     * Sisa kesegaran jam untuk display publik.
     */
    public function currentRemainingHours(): int
    {
        $batch = $this->batches()->where('remaining_quantity', '>', 0)->latest('produced_at')->first();
        if ($batch) {
            return $batch->remainingHours();
        }

        return $this->shelf_life_hours ?: 96;
    }

    /**
     * Status kesegaran saat ini.
     */
    public function currentFreshnessStatus(): string
    {
        $batch = $this->batches()->where('remaining_quantity', '>', 0)->latest('produced_at')->first();
        if ($batch) {
            return $batch->freshnessStatus();
        }

        return 'Sangat Segar';
    }

    /**
     * Cek apakah produk tersedia untuk dipesan di katalog (memiliki stok dan batch segar >= 12 jam).
     */
    public function isAvailable(): bool
    {
        if (! $this->is_active || $this->stock < 1) {
            return false;
        }

        // Jika produk memiliki batch aktif, cek apakah masih ada batch dengan sisa kesegaran >= 12 jam
        $hasBatches = $this->batches()->exists();
        if ($hasBatches) {
            $freshBatchExists = $this->batches()
                ->where('remaining_quantity', '>', 0)
                ->get()
                ->contains(fn (Batch $b) => $b->remainingHours() >= 12);

            return $freshBatchExists;
        }

        return true;
    }

    public function imageUrl(): ?string
    {
        if (! $this->image) {
            return null;
        }

        $scheme = parse_url($this->image, PHP_URL_SCHEME);

        if (in_array($scheme, ['http', 'https'], true)) {
            return $this->image;
        }

        return Storage::disk('public')->url($this->image);
    }
}
