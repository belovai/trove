<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            // Both cascade, unlike media.user_id which restricts. An upload
            // outlives its account; a vote has no meaning without its voter,
            // and media:prune's hard delete should carry votes with it.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            // 1 or -1. There is deliberately no 0 row: withdrawing a vote
            // deletes it, so the table only ever holds opinions.
            $table->tinyInteger('value');
            $table->timestamps();

            $table->unique(['user_id', 'media_id']);
            // The unique index leads with user_id, so counting an item's votes
            // needs its own.
            $table->index('media_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
