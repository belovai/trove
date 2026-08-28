<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tag_implications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->foreignId('implied_tag_id')->constrained('tags')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tag_id', 'implied_tag_id']);
            // Both directions are indexed: the resolver walks down to expand a
            // tag and up to render its ancestors in the tree view.
            $table->index('tag_id');
            $table->index('implied_tag_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tag_implications');
    }
};
