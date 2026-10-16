<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Yonetici bir ogretmenin sinifina hangi oyunlari acar.
        Schema::create('teacher_games', function (Blueprint $table) {
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->string('game_slug', 100);
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->primary(['teacher_id', 'game_slug']);
        });

        // Her (ogrenci, oyun, seviye) icin tek kayit: XP bir kez verilir.
        Schema::create('game_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('game_slug', 100);
            $table->unsignedInteger('level');
            $table->unsignedInteger('best_seconds')->nullable();
            $table->unsignedInteger('best_score')->default(0);
            $table->unsignedInteger('plays')->default(1);
            $table->unsignedInteger('xp_awarded')->default(0);
            $table->timestamps();
            $table->unique(['student_id', 'game_slug', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_progress');
        Schema::dropIfExists('teacher_games');
    }
};
