<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subject_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('schedules')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->date('date');
            $table->time('check_in_time');
            $table->string('status')->default('hadir'); // hadir, terlambat, izin, sakit, alpa
            $table->string('verified_by')->default('ai_face'); // ai_face, manual_teacher
            $table->string('photo_path')->nullable();
            $table->float('similarity_score')->nullable();
            $table->timestamps();

            $table->unique(['schedule_id', 'student_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_attendances');
    }
};
