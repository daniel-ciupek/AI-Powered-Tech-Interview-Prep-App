<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Wipe pre-existing rows: their hashes were computed without a user
        // dimension and would collide with the new key scheme.
        DB::table('ai_response_cache')->delete();

        Schema::table('ai_response_cache', function (Blueprint $table) {
            $table->foreignId('user_id')->after('id')->constrained()->cascadeOnDelete();
            $table->dropUnique(['prompt_hash']);
            $table->unique(['user_id', 'prompt_hash']);
        });
    }

    public function down(): void
    {
        Schema::table('ai_response_cache', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'prompt_hash']);
            $table->dropConstrainedForeignId('user_id');
            $table->unique('prompt_hash');
        });
    }
};
