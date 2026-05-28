<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('taggables');
        Schema::dropIfExists('tags');
    }

    public function down(): void
    {
        throw new RuntimeException(
            'Irreversible: spatie/laravel-tags package was removed in this release. '
            .'To restore the tables, reinstall the package and rerun its migration.'
        );
    }
};
