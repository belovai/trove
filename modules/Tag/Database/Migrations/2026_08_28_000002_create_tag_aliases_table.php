<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tag_aliases', function (Blueprint $table) {
            $table->id();
            $table->string('alias_name')->unique();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->timestamps();

            $table->index('tag_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tag_aliases');
    }
};
