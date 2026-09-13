<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            // Both cascade, same reasoning as votes: a favorite has no
            // meaning without its owner or its item, and media:prune's hard
            // delete should carry favorites with it.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            // No updated_at: a favorite has no state to update, only exist
            // or not.
            $table->timestamp('created_at')->nullable();

            $table->unique(['user_id', 'media_id']);
            // The unique index leads with user_id, so a "who favorited this"
            // lookup needs its own — unused today, same reasoning as votes.
            $table->index('media_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
