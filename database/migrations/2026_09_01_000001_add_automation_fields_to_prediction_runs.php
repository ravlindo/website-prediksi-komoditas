<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prediction_runs', function (Blueprint $table) {
            $table->string('trigger_type', 20)->default('manual')->after('status');
            $table->unsignedTinyInteger('progress')->default(0)->after('trigger_type');
            $table->string('current_stage', 120)->nullable()->after('progress');
            $table->text('error_message')->nullable()->after('notes');
            $table->timestamp('queued_at')->nullable()->after('error_message');
            $table->timestamp('started_at')->nullable()->after('queued_at');
            $table->timestamp('finished_at')->nullable()->after('started_at');
            $table->unsignedInteger('runtime_seconds')->nullable()->after('finished_at');
            $table->index(['status', 'data_last_date'], 'prediction_runs_status_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('prediction_runs', function (Blueprint $table) {
            $table->dropIndex('prediction_runs_status_date_index');
            $table->dropColumn([
                'trigger_type', 'progress', 'current_stage', 'error_message',
                'queued_at', 'started_at', 'finished_at', 'runtime_seconds',
            ]);
        });
    }
};
