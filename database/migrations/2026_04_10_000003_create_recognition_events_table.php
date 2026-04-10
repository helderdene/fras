<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Run the migrations. */
    public function up(): void
    {
        Schema::create('recognition_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camera_id')->constrained('cameras');
            $table->foreignId('personnel_id')->nullable()->constrained('personnel')->nullOnDelete();
            $table->string('custom_id')->nullable()->index();
            $table->string('camera_person_id')->nullable();
            $table->bigInteger('record_id');
            $table->tinyInteger('verify_status');        // 0-3
            $table->tinyInteger('person_type');           // 0 or 1
            $table->float('similarity');                  // 0-100
            $table->boolean('is_real_time');
            $table->string('name_from_camera')->nullable();
            $table->string('facesluice_id')->nullable();
            $table->string('id_card')->nullable();
            $table->string('phone')->nullable();
            $table->tinyInteger('is_no_mask');            // 0-2
            $table->json('target_bbox')->nullable();      // [x1,y1,x2,y2]
            $table->dateTime('captured_at');
            $table->string('face_image_path')->nullable();
            $table->string('scene_image_path')->nullable();
            $table->json('raw_payload');
            $table->timestamps();

            $table->index(['camera_id', 'captured_at']);
            $table->index(['person_type', 'verify_status']);
        });
    }

    /** Reverse the migrations. */
    public function down(): void
    {
        Schema::dropIfExists('recognition_events');
    }
};
