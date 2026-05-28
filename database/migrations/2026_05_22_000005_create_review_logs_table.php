<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repetition_id')->constrained()->cascadeOnDelete();
            $table->smallInteger('quality');
            $table->decimal('ease_before', 4, 2);
            $table->decimal('ease_after', 4, 2);
            $table->unsignedInteger('interval_before');
            $table->unsignedInteger('interval_after');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_logs');
    }
};
