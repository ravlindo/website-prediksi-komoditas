<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commodity_prices', function (Blueprint $table) {
            $table->softDeletes();
            $table->string('deleted_by')->nullable()->after('deleted_at');
            $table->string('deleted_ip', 45)->nullable()->after('deleted_by');
            $table->uuid('deletion_batch')->nullable()->after('deleted_ip')->index();
        });
    }

    public function down(): void
    {
        Schema::table('commodity_prices', function (Blueprint $table) {
            $table->dropIndex(['deletion_batch']);
            $table->dropColumn(['deleted_at', 'deleted_by', 'deleted_ip', 'deletion_batch']);
        });
    }
};
