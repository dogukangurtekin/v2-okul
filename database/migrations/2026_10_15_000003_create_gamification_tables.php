<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avatars', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('emoji', 16);
            $table->string('tier', 20)->default('basic'); // basic | rare | epic | premium
            $table->unsignedInteger('price')->default(0);        // harcanabilir XP
            $table->unsignedInteger('required_xp')->default(0);  // kilit: toplam kazanilan XP
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('emoji', 16);
            $table->string('description')->nullable();
            $table->unsignedInteger('xp_threshold')->index();
            $table->timestamps();
        });

        Schema::table('students', function (Blueprint $table) {
            $table->unsignedInteger('xp_total')->default(0)->after('xp_balance'); // hic dusmez: rozet/siralama icin
            $table->foreignId('current_avatar_id')->nullable()->after('xp_total')->constrained('avatars')->nullOnDelete();
        });

        Schema::create('student_avatar', function (Blueprint $table) {
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('avatar_id')->constrained()->cascadeOnDelete();
            $table->timestamp('unlocked_at')->nullable();
            $table->primary(['student_id', 'avatar_id']);
        });

        Schema::create('student_badge', function (Blueprint $table) {
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('badge_id')->constrained()->cascadeOnDelete();
            $table->timestamp('awarded_at')->nullable();
            $table->primary(['student_id', 'badge_id']);
        });

        Schema::create('class_board_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('message_key', 60);
            $table->timestamps();
            $table->index(['school_class_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_board_posts');
        Schema::dropIfExists('student_badge');
        Schema::dropIfExists('student_avatar');
        Schema::table('students', fn (Blueprint $t) => [$t->dropConstrainedForeignId('current_avatar_id'), $t->dropColumn('xp_total')]);
        Schema::dropIfExists('badges');
        Schema::dropIfExists('avatars');
    }
};
