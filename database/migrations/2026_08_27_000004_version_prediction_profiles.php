<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commodity_prediction_profiles', function (Blueprint $table) {
            $table->index('commodity_id', 'prediction_profiles_commodity_fk_index');
        });
        Schema::table('commodity_prediction_profiles', function (Blueprint $table) {
            $table->dropUnique(['commodity_id']);
            $table->foreignId('prediction_run_id')->nullable()->after('id')->constrained('prediction_runs')->nullOnDelete();
            $table->unique(['commodity_id', 'prediction_run_id'], 'prediction_profile_run_unique');
        });
        $run = DB::table('prediction_runs')->where('status', 'active')->value('id');
        if ($run) {
            DB::table('commodity_prediction_profiles')->whereNull('prediction_run_id')->update(['prediction_run_id' => $run]);
        }
    }

    public function down(): void
    {
        Schema::table('commodity_prediction_profiles', function (Blueprint $table) {
            $table->dropUnique('prediction_profile_run_unique');
            $table->dropConstrainedForeignId('prediction_run_id');
            $table->unique('commodity_id');
        });
        Schema::table('commodity_prediction_profiles', function (Blueprint $table) {
            $table->dropIndex('prediction_profiles_commodity_fk_index');
        });
    }
};
