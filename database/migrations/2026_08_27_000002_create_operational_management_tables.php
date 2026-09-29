<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prediction_runs', function (Blueprint $table) {
            $table->id();
            $table->string('version', 80)->unique();
            $table->string('status', 20)->default('draft');
            $table->date('data_last_date')->nullable();
            $table->dateTime('generated_at')->nullable();
            $table->unsignedInteger('profile_count')->default(0);
            $table->unsignedInteger('prediction_count')->default(0);
            $table->json('status_summary')->nullable();
            $table->string('source_file')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('activated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
        });

        Schema::table('commodity_predictions', function (Blueprint $table) {
            $table->foreignId('prediction_run_id')->nullable()->after('id')->constrained('prediction_runs')->nullOnDelete();
            $table->decimal('naive_mae', 15, 4)->nullable()->after('mae');
            $table->boolean('beats_naive')->nullable()->after('naive_mae');
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name')->nullable();
            $table->string('event', 30);
            $table->string('route_name')->nullable();
            $table->string('method', 10);
            $table->string('path');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
            $table->index(['event', 'created_at']);
        });

        Schema::create('synchronization_runs', function (Blueprint $table) {
            $table->id();
            $table->string('source')->default('SISKAPERBAPO');
            $table->string('status', 20)->default('pending');
            $table->string('trigger', 20)->default('manual');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('processed_count')->default(0);
            $table->unsignedInteger('inserted_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->text('message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('synchronization_runs');
        Schema::dropIfExists('activity_logs');
        Schema::table('commodity_predictions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('prediction_run_id');
            $table->dropColumn(['naive_mae', 'beats_naive']);
        });
        Schema::dropIfExists('prediction_runs');
    }
};
