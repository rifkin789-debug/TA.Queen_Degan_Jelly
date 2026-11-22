<?php

use App\Models\Category;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Category::where('slug', 'original')->update([
            'name' => 'Original & Mix',
            'description' => 'Varian degan jelly original murni kelapa muda dan custom mix isian buah.',
        ]);

        Category::whereIn('slug', ['paket', 'paket-mix'])->update([
            'name' => 'Paket',
            'slug' => 'paket',
            'description' => 'Paket hemat degan jelly istimewa.',
        ]);
    }

    public function down(): void
    {
        Category::where('slug', 'original')->update([
            'name' => 'Original',
            'description' => 'Varian degan jelly original murni kelapa muda.',
        ]);

        Category::where('slug', 'paket')->update([
            'name' => 'Paket & Mix',
            'description' => 'Paket hemat dan mix varian degan jelly istimewa.',
        ]);
    }
};
