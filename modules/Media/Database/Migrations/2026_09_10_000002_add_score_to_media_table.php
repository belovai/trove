<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            // Denormalized net of the votes table, recalculated by
            // VoteScoreCounter. 0 is correct for every existing row.
            $table->integer('score')->default(0);

            $table->index('score');
            // The score-ordered counterpart of (visibility, safety_rating,
            // created_at) — the same browse path, a different sort.
            $table->index(['visibility', 'safety_rating', 'score']);
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropIndex(['visibility', 'safety_rating', 'score']);
            $table->dropIndex(['score']);
            $table->dropColumn('score');
        });
    }
};
