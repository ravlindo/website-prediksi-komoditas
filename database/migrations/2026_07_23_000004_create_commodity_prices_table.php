<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commodity_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commodity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('market_id')->constrained()->cascadeOnDelete();
            $table->date('price_date');
            $table->unsignedInteger('price');
            $table->string('source')->default('Input manual');
            $table->timestamps();
            $table->unique(['commodity_id', 'market_id', 'price_date']);
            $table->index(['price_date', 'market_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commodity_prices');
    }
};
