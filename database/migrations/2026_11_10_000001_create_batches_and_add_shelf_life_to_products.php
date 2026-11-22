<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('shelf_life_hours')->default(96)->after('stock');
        });

        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('batch_code', 50)->unique();
            $table->dateTime('produced_at');
            $table->unsignedInteger('initial_quantity');
            $table->unsignedInteger('remaining_quantity');
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'produced_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batches');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('shelf_life_hours');
        });
    }
};
