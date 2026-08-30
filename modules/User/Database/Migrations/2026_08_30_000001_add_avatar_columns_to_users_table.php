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
            $table->string('avatar_source')->default('letter')->after('show_unsafe_content');
            // Only set when avatar_source is "upload"; null otherwise.
            $table->string('avatar_path')->nullable()->after('avatar_source');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar_source', 'avatar_path']);
        });
    }
};
