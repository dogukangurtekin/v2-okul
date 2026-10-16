<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            // v1'de unique(student, course, date) course NULL iken tekrarlari engellemiyordu; 0 = genel gunluk yoklama.
            $table->unsignedBigInteger('course_key')->default(0);
            $table->date('date')->index();
            $table->string('status', 10); // present | absent | late | excused
            $table->string('note')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['student_id', 'date', 'course_key']);
            $table->index(['status', 'date']);
        });

        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->string('exam_type', 10); // quiz | midterm | final | project
            $table->string('title', 120)->nullable();
            $table->decimal('score', 5, 2);
            $table->date('exam_date');
            $table->string('term', 40)->index();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['student_id', 'term']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
        Schema::dropIfExists('attendances');
    }
};
