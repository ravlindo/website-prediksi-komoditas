<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commodity_prediction_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commodity_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('valid_price_count')->default(0);
            $table->decimal('missing_rate', 10, 6)->default(0);
            $table->unsignedInteger('unique_price_count')->default(0);
            $table->decimal('coefficient_variation', 14, 8)->nullable();
            $table->string('series_type', 40);
            $table->string('availability_status', 40);
            $table->string('model_version', 40);
            $table->date('data_last_date');
            $table->timestamps();
        });

        Schema::create('commodity_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commodity_id')->constrained()->cascadeOnDelete();
            $table->date('target_date');
            $table->unsignedTinyInteger('horizon_days');
            $table->decimal('predicted_price', 15, 2);
            $table->decimal('lower_bound', 15, 2);
            $table->decimal('upper_bound', 15, 2);
            $table->string('model_name', 80);
            $table->decimal('mae', 15, 4)->nullable();
            $table->decimal('rmse', 15, 4)->nullable();
            $table->decimal('mape', 10, 4)->nullable();
            $table->decimal('smape', 10, 4)->nullable();
            $table->decimal('accuracy_score', 6, 2)->nullable();
            $table->string('original_status', 40);
            $table->string('normalized_status', 40);
            $table->string('status_reason', 255)->nullable();
            $table->boolean('interval_adjusted')->default(false);
            $table->string('model_version', 40);
            $table->date('data_last_date');
            $table->dateTime('model_generated_at')->nullable();
            $table->timestamps();
            $table->unique(['commodity_id', 'horizon_days', 'model_version', 'data_last_date'], 'prediction_version_unique');
            $table->index(['normalized_status', 'horizon_days']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commodity_predictions');
        Schema::dropIfExists('commodity_prediction_profiles');
    }
};
