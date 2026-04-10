<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Run the migrations. */
    public function up(): void
    {
        Schema::create('camera_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camera_id')->constrained('cameras')->cascadeOnDelete();
            $table->foreignId('personnel_id')->constrained('personnel')->cascadeOnDelete();
            $table->dateTime('enrolled_at')->nullable();
            $table->string('photo_hash', 32)->nullable();
            $table->string('last_error')->nullable();
            $table->timestamps();

            $table->unique(['camera_id', 'personnel_id']);
        });
    }

    /** Reverse the migrations. */
    public function down(): void
    {
        Schema::dropIfExists('camera_enrollments');
    }
};
