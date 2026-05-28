<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('gemini_api_key_encrypted')->nullable()->after('remember_token');
            $table->string('preferred_difficulty', 10)->default('junior')->after('gemini_api_key_encrypted');
            $table->unsignedTinyInteger('daily_goal')->default(10)->after('preferred_difficulty');
            $table->unsignedInteger('streak_count')->default(0)->after('daily_goal');
            $table->timestamp('last_studied_at')->nullable()->after('streak_count');
            $table->string('theme', 10)->default('system')->after('last_studied_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'gemini_api_key_encrypted',
                'preferred_difficulty',
                'daily_goal',
                'streak_count',
                'last_studied_at',
                'theme',
            ]);
        });
    }
};
