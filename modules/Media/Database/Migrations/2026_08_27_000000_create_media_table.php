<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Media\Enums\SafetyRating;
use Modules\Media\Enums\Visibility;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('hash_id', 10)->unique();
            // The uploader is always a real account. restrictOnDelete because
            // users soft-delete: their media outlives the account.
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->boolean('is_anonymous')->default(false);
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('source')->nullable();
            $table->string('visibility')->default(Visibility::Public->value);
            $table->string('safety_rating')->default(SafetyRating::Safe->value);
            $table->string('original_filename');
            $table->string('mime_type');
            $table->bigInteger('filesize');
            $table->integer('width');
            $table->integer('height');
            $table->boolean('is_animated')->default(false);
            $table->integer('frame_count')->nullable();
            // Indexed, deliberately NOT unique: duplicates are a policy
            // decision, not a constraint. A unique index would leak the
            // existence of other users' private items.
            $table->string('content_hash', 64);
            $table->string('storage_path');
            $table->json('thumbnails')->nullable();
            $table->string('dominant_color', 7)->nullable();
            $table->integer('tag_count')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index('content_hash');
            $table->index('visibility');
            $table->index('safety_rating');
            $table->index('mime_type');
            $table->index('created_at');
            $table->index(['visibility', 'safety_rating', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
