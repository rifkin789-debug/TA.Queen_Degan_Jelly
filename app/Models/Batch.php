<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Batch extends Model
{
    protected $fillable = [
        'product_id',
        'batch_code',
        'produced_at',
        'initial_quantity',
        'remaining_quantity',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'produced_at' => 'datetime',
            'initial_quantity' => 'integer',
            'remaining_quantity' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function hoursPassed(): int
    {
        if (! $this->produced_at) {
            return 0;
        }

        return max(0, (int) $this->produced_at->diffInHours(now()));
    }

    public function remainingHours(): int
    {
        $shelfLife = $this->product?->shelf_life_hours ?? 96;

        return max(0, $shelfLife - $this->hoursPassed());
    }

    public function freshnessPercentage(): int
    {
        $shelfLife = max(1, $this->product?->shelf_life_hours ?? 96);
        $percentage = (int) round(($this->remainingHours() / $shelfLife) * 100);

        return min(100, max(0, $percentage));
    }

    public function freshnessStatus(): string
    {
        $remaining = $this->remainingHours();

        if ($remaining < 12) {
            return 'Kedaluwarsa';
        }

        if ($remaining < 24) {
            return 'Segera Dikonsumsi';
        }

        if ($remaining < 48) {
            return 'Segar';
        }

        return 'Sangat Segar';
    }

    public function isAvailableInCatalog(): bool
    {
        return $this->remainingHours() >= 12 && $this->remaining_quantity > 0;
    }
}
