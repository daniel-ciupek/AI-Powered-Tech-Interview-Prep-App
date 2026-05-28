<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')
                ->constrained('interview_sessions')
                ->cascadeOnDelete();
            $table->string('role', 10);
            $table->text('content');
            $table->unsignedInteger('tokens_used')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['session_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_messages');
    }
};
