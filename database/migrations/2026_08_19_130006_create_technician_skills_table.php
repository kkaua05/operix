<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technician_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technician_id')->constrained()->cascadeOnDelete();
            $table->string('skill');
            $table->string('proficiency_level')->nullable();
            $table->timestamps();

            $table->unique(['technician_id', 'skill']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technician_skills');
    }
};
