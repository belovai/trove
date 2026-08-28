<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            // human / implied / ai. Makes implication removal correct, lets
            // the UI dim derived tags, and is the seam auto-tagging enters by.
            $table->string('source');
            $table->foreignId('tagged_by')->nullable()->constrained('users')->nullOnDelete();
            // No updated_at: rows are created and deleted, never edited.
            $table->timestamp('created_at')->nullable();

            $table->unique(['media_id', 'tag_id']);
            $table->index(['tag_id', 'media_id']);
            $table->index(['media_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_tag');
    }
};
