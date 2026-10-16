<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('branch')->nullable()->index();
            $table->string('phone', 20)->nullable();
            $table->date('hire_date')->nullable();
            $table->timestamps();
        });

        Schema::create('school_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('section', 50);
            $table->unsignedTinyInteger('grade_level')->index();
            $table->string('academic_year', 20)->index();
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->timestamps();
            $table->unique(['name', 'section', 'academic_year']);
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('student_no', 50)->unique();
            $table->foreignId('school_class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $table->string('parent_name')->nullable();
            $table->string('parent_phone', 20)->nullable();
            $table->date('birth_date')->nullable();
            $table->unsignedInteger('xp_balance')->default(0);
            $table->timestamps();
        });

        // Tek XP defteri: v1'deki dagnik XP sayaclarinin yerine gecer.
        Schema::create('xp_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->integer('delta');
            $table->string('reason', 120);
            $table->nullableMorphs('source');
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['student_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xp_ledger');
        Schema::dropIfExists('students');
        Schema::dropIfExists('school_classes');
        Schema::dropIfExists('teachers');
    }
};
