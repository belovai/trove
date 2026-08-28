<?php

declare(strict_types=1);

namespace Modules\Media\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Media\Contracts\MediaStorage;
use Modules\Media\Contracts\MetadataExtractor;
use Modules\Media\DataObjects\UploadedMediaData;
use Modules\Media\Jobs\GenerateMediaThumbnails;
use Modules\Media\Models\Media;
use Modules\Media\Services\HashIdGenerator;
use Modules\User\Models\User;
use Throwable;

final class StoreUploadedMedia
{
    public function __construct(
        private readonly HashIdGenerator $hashIds,
        private readonly MediaStorage $storage,
        private readonly MetadataExtractor $metadata,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(UploadedMediaData $data): Media
    {
        $contentHash = hash_file('sha256', $data->file->getRealPath());
        $hashId = $this->hashIds->generate();
        $storagePath = $this->storage->storeOriginal($data->file, $hashId);

        try {
            $media = DB::transaction(function () use ($data, $contentHash, $hashId, $storagePath): Media {
                $extracted = $this->metadata->extract($storagePath, $data->file->getMimeType());

                return Media::query()->create([
                    'hash_id' => $hashId,
                    'user_id' => $data->uploader->id,
                    'is_anonymous' => $data->isAnonymous,
                    'title' => $data->title,
                    'description' => $data->description,
                    'source' => $data->source,
                    'visibility' => $data->visibility,
                    'safety_rating' => $data->safetyRating,
                    'original_filename' => $data->file->getClientOriginalName(),
                    'mime_type' => $data->file->getMimeType(),
                    'filesize' => $data->file->getSize(),
                    'width' => $extracted->width,
                    'height' => $extracted->height,
                    'is_animated' => $extracted->isAnimated,
                    'frame_count' => $extracted->frameCount,
                    'content_hash' => $contentHash,
                    'storage_path' => $storagePath,
                    'dominant_color' => $extracted->dominantColor,
                    // Tags are attached by the Tag module. Until it exists,
                    // every item is untagged.
                    'tag_count' => 0,
                ]);
            });
        } catch (Throwable $e) {
            // The file is already on disk at this point. Without this, a failed
            // insert would leave bytes with no record pointing at them.
            $this->storage->delete($hashId);

            throw $e;
        }

        GenerateMediaThumbnails::dispatch($media);

        return $media;
    }

    /**
     * Looks for an existing item with the same bytes, THROUGH THE UPLOADER'S
     * VISIBILITY SCOPE. Never report a match the uploader may not see: doing
     * so reveals the contents of other users' private collections.
     */
    public function findDuplicateFor(string $contentHash, User $uploader): ?Media
    {
        return Media::query()->visibleTo($uploader)
            ->where('content_hash', $contentHash)
            ->first();
    }
}
