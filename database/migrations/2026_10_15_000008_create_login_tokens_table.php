<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // card: personelin bastigi tek kullanimlik giris kartlari | qr: giris ekraninda gosterilen, personelin onayladigi oturum.
        Schema::create('login_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 6);
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->string('poll_hash', 64)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('request_ip', 45)->nullable();
            $table->string('request_ua', 200)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->dateTime('expires_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_tokens');
    }
};
