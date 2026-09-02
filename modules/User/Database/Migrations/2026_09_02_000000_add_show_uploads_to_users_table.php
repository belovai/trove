<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // Defaults to true: this is a gallery, and a user who wants one
            // specific item unattributed already has media.is_anonymous.
            $table->boolean('show_uploads')->default(true)->after('show_unsafe_content');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('show_uploads');
        });
    }
};
