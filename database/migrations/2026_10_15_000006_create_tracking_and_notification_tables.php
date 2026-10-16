<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->string('last_path', 120)->nullable();
            $table->unsignedBigInteger('total_seconds')->default(0);
        });

        Schema::create('student_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('route_name', 120);
            $table->string('label', 200)->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->index(['student_id', 'created_at']);
        });

        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('body');
            $table->string('url')->nullable();
            $table->timestamps();
        });

        Schema::create('app_notification_user', function (Blueprint $table) {
            $table->foreignId('app_notification_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->string('push_status', 20)->nullable(); // sent | failed | no_device
            $table->primary(['app_notification_id', 'user_id']);
            $table->index(['user_id', 'read_at']);
        });

        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('endpoint', 512)->unique();
            $table->text('p256dh');
            $table->text('auth');
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });

        Schema::create('parent_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('channel', 20);
            $table->string('status', 20); // sent | logged | failed | no_phone
            $table->text('detail')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['parent_messages', 'push_subscriptions', 'app_notification_user', 'app_notifications', 'student_activity_logs'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::table('students', fn (Blueprint $t) => $t->dropColumn(['last_seen_at', 'last_path', 'total_seconds']));
    }
};
