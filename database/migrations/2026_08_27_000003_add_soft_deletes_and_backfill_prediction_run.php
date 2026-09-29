<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['categories', 'commodities', 'infographics'] as $name) {
            Schema::table($name, fn (Blueprint $t) => $t->softDeletes());
        }$version = DB::table('commodity_predictions')->value('model_version');
        if ($version) {
            $id = DB::table('prediction_runs')->insertGetId(['version' => $version, 'status' => 'active', 'data_last_date' => DB::table('commodity_predictions')->max('data_last_date'), 'generated_at' => DB::table('commodity_predictions')->max('model_generated_at'), 'profile_count' => DB::table('commodity_prediction_profiles')->count(), 'prediction_count' => DB::table('commodity_predictions')->count(), 'status_summary' => json_encode(DB::table('commodity_predictions')->select('normalized_status', DB::raw('COUNT(*) total'))->groupBy('normalized_status')->pluck('total', 'normalized_status')), 'activated_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
            DB::table('commodity_predictions')->whereNull('prediction_run_id')->update(['prediction_run_id' => $id]);
        }
    }

    public function down(): void
    {
        foreach (['infographics', 'commodities', 'categories'] as $name) {
            Schema::table($name, fn (Blueprint $t) => $t->dropSoftDeletes());
        }
    }
};
