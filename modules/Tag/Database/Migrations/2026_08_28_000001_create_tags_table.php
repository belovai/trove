<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            // Nullable: an uncategorized tag is valid. Deleting a category
            // reassigns its tags to the default — an application rule in
            // DeleteTagCategory, not a database cascade.
            $table->foreignId('category_id')->nullable()->constrained('tag_categories')->nullOnDelete();
            $table->text('description')->nullable();
            // Denormalized. Counts human pivot rows only, so implied tags
            // never inflate what autocomplete shows.
            $table->integer('usage_count')->default(0);
            $table->timestamps();

            $table->index('category_id');
            $table->index('usage_count');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
