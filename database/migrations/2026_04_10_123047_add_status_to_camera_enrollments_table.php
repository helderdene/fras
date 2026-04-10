<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Run the migrations. */
    public function up(): void
    {
        Schema::table('camera_enrollments', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->after('personnel_id');
            $table->index(['camera_id', 'status']);
            $table->index(['personnel_id', 'status']);
        });
    }

    /** Reverse the migrations. */
    public function down(): void
    {
        Schema::table('camera_enrollments', function (Blueprint $table) {
            $table->dropIndex(['camera_id', 'status']);
            $table->dropIndex(['personnel_id', 'status']);
            $table->dropColumn('status');
        });
    }
};
