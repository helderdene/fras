<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Run the migrations. */
    public function up(): void
    {
        Schema::table('recognition_events', function (Blueprint $table) {
            $table->string('severity')->default('info')->after('scene_image_path');
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete()->after('severity');
            $table->dateTime('acknowledged_at')->nullable()->after('acknowledged_by');
            $table->dateTime('dismissed_at')->nullable()->after('acknowledged_at');

            $table->index('severity');
            $table->index(['is_real_time', 'severity']);
        });
    }

    /** Reverse the migrations. */
    public function down(): void
    {
        Schema::table('recognition_events', function (Blueprint $table) {
            $table->dropIndex(['severity']);
            $table->dropIndex(['is_real_time', 'severity']);
            $table->dropForeign(['acknowledged_by']);
            $table->dropColumn(['severity', 'acknowledged_by', 'acknowledged_at', 'dismissed_at']);
        });
    }
};
