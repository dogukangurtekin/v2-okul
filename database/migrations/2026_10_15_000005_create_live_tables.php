<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->text('text');
            $table->json('options');
            $table->unsignedTinyInteger('correct_index')->default(0);
            $table->unsignedInteger('duration_sec')->default(20);
            $table->unsignedInteger('xp')->default(10);
            $table->boolean('double_xp')->default(false);
            $table->timestamps();
            $table->index(['quiz_id', 'position']);
        });

        // v1'de live_quiz_sessions + competition_rooms + rooms + coding_live_quiz_rooms: tek tabloda.
        Schema::create('live_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('type', 12); // quiz | competition | typing
            $table->foreignId('host_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('school_class_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quiz_id')->nullable()->constrained()->nullOnDelete();
            $table->string('join_code', 8)->unique();
            $table->string('status', 10)->default('lobby')->index(); // lobby | live | finished
            $table->boolean('is_locked')->default(false);
            $table->unsignedInteger('current_index')->default(0);
            $table->json('config')->nullable();
            $table->unsignedBigInteger('started_at_ms')->nullable();
            $table->unsignedBigInteger('ends_at_ms')->nullable();
            $table->unsignedBigInteger('finished_at_ms')->nullable();
            $table->timestamp('xp_granted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('live_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->unsignedInteger('score')->default(0);
            $table->decimal('progress', 5, 2)->default(0);
            $table->unsignedInteger('wpm')->default(0);
            $table->unsignedTinyInteger('accuracy')->default(100);
            $table->unsignedInteger('xp_earned')->default(0);
            $table->unsignedInteger('rank_no')->nullable();
            $table->unsignedBigInteger('joined_at_ms')->nullable();
            $table->unsignedBigInteger('finished_at_ms')->nullable();
            $table->timestamps();
            $table->unique(['live_session_id', 'student_id']);
        });

        Schema::create('live_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('live_participant_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('question_index');
            $table->unsignedTinyInteger('answer_index')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('points')->default(0);
            $table->unsignedBigInteger('answered_at_ms');
            $table->timestamps();
            $table->unique(['live_session_id', 'live_participant_id', 'question_index'], 'live_answer_once');
        });
    }

    public function down(): void
    {
        foreach (['live_answers', 'live_participants', 'live_sessions', 'quiz_questions', 'quizzes'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
