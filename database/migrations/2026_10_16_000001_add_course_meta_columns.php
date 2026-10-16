<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('category', 80)->nullable()->after('description');
            $table->string('difficulty', 30)->nullable()->after('category');
            $table->json('curriculum')->nullable()->after('difficulty'); // {konu, kazanimlar[], etkinlikler[]}
        });
    }

    public function down(): void
    {
        Schema::table('courses', fn (Blueprint $t) => $t->dropColumn(['category', 'difficulty', 'curriculum']));
    }
};
