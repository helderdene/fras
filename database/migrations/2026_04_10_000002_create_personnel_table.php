<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Run the migrations. */
    public function up(): void
    {
        Schema::create('personnel', function (Blueprint $table) {
            $table->id();
            $table->string('custom_id', 48)->unique();
            $table->string('name', 32);
            $table->tinyInteger('person_type')->default(0); // 0=allow, 1=block
            $table->tinyInteger('gender')->nullable();       // 0=male, 1=female
            $table->date('birthday')->nullable();
            $table->string('id_card', 32)->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('address', 72)->nullable();
            $table->string('photo_path')->nullable();
            $table->string('photo_hash', 32)->nullable();    // MD5
            $table->timestamps();
        });
    }

    /** Reverse the migrations. */
    public function down(): void
    {
        Schema::dropIfExists('personnel');
    }
};
