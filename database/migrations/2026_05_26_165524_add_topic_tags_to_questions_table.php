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
        Schema::table('questions', function (Blueprint $table) {
            $table->jsonb('topic_tags')->default('[]')->after('source');
        });

        DB::statement('CREATE INDEX questions_topic_tags_gin ON questions USING GIN (topic_tags jsonb_path_ops)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS questions_topic_tags_gin');

        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('topic_tags');
        });
    }
};
