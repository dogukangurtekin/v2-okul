<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('guest_name')->nullable();
            $table->string('guest_email')->nullable();
            $table->string('source', 10)->default('user')->index(); // user | demo
            $table->string('subject');
            $table->text('message');
            $table->string('category', 20)->default('other');
            $table->string('priority', 10)->default('normal');
            $table->string('status', 10)->default('open')->index(); // open | answered | closed
            $table->timestamp('read_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('archived_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('support_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('message');
            $table->boolean('internal_note')->default(false);
            $table->timestamps();
        });

        Schema::create('student_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('recipient_type', 10); // teacher | admin
            $table->foreignId('recipient_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->text('reply_body')->nullable();
            $table->foreignId('replied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('replied_at')->nullable();
            $table->timestamp('student_read_at')->nullable();
            $table->timestamp('staff_deleted_at')->nullable();
            $table->timestamps();
            $table->index(['recipient_type', 'recipient_user_id', 'replied_at'], 'student_messages_inbox');
        });

        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->string('audience', 10)->default('all'); // all | students | teachers
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 80)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['settings', 'announcements', 'student_messages', 'support_replies', 'support_requests'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
