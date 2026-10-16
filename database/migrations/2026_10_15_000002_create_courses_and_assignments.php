<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->string('name')->index();
            $table->string('code', 30)->unique();
            $table->text('description')->nullable();
            $table->string('cover_path')->nullable();
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // v1'deki lesson_payload JSON blogunun yerine: her slayt ayri satir.
        Schema::create('course_slides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->string('type', 20)->default('text'); // text | image | video | code | quiz
            $table->string('title')->nullable();
            $table->json('content')->nullable();
            $table->timestamps();
            $table->index(['course_id', 'position']);
        });

        Schema::create('course_school_class', function (Blueprint $table) {
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_class_id')->constrained()->cascadeOnDelete();
            $table->primary(['course_id', 'school_class_id']);
        });

        Schema::create('course_favorites', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'course_id']);
        });

        Schema::create('course_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('slide_index')->default(0);
            $table->json('state')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('xp_awarded')->default(0);
            $table->timestamps();
            $table->unique(['student_id', 'course_id']);
        });

        // v1'de course_homeworks + game_assignments ayri idi; tek tabloda birlestirildi.
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->string('type', 10); // course | game
            $table->foreignId('course_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('game_slug', 100)->nullable()->index();
            $table->string('title', 160);
            $table->text('details')->nullable();
            $table->date('due_date')->nullable();
            $table->unsignedInteger('level_from')->nullable();
            $table->unsignedInteger('level_to')->nullable();
            $table->unsignedInteger('xp_reward')->default(10);
            $table->string('attachment_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('assignment_school_class', function (Blueprint $table) {
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_class_id')->constrained()->cascadeOnDelete();
            $table->primary(['assignment_id', 'school_class_id']);
        });

        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('reached_level')->nullable();
            $table->unsignedInteger('completion_seconds')->default(0);
            $table->unsignedInteger('xp_awarded')->default(0);
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->unique(['assignment_id', 'student_id']);
        });
    }

    public function down(): void
    {
        foreach (['assignment_submissions', 'assignment_school_class', 'assignments', 'course_progress', 'course_favorites', 'course_school_class', 'course_slides', 'courses'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
