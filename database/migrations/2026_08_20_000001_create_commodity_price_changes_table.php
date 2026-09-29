<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commodity_price_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commodity_price_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name');
            $table->string('user_email')->nullable();
            $table->unsignedInteger('old_price');
            $table->unsignedInteger('new_price');
            $table->json('changes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->index(['commodity_price_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commodity_price_changes');
    }
};
