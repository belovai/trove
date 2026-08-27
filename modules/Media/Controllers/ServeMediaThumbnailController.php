<?php

declare(strict_types=1);

namespace Modules\Media\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\Media\Contracts\MediaStorage;
use Modules\Media\Enums\ThumbnailSize;
use Modules\Media\Enums\Visibility;
use Modules\Media\Models\Media;
use Symfony\Component\HttpFoundation\Response;

final class ServeMediaThumbnailController
{
    public function __construct(
        private readonly MediaStorage $storage,
    ) {}

    public function __invoke(Request $request, string $media, string $size): Response
    {
        $thumbnailSize = ThumbnailSize::tryFrom($size);

        abort_if($thumbnailSize === null, 404);

        $item = Media::visibleTo($request->user())
            ->where('hash_id', $media)
            ->firstOrFail();

        abort_unless(Gate::allows('view', $item), 404);

        $path = $item->thumbnails[$thumbnailSize->value] ?? null;

        abort_if($path === null || !$this->storage->exists($path), 404);

        return $this->storage->stream($path, 'image/webp', "{$item->hash_id}-{$thumbnailSize->value}.webp", [
            'Cache-Control' => $item->visibility->equals(Visibility::Public)
                ? 'public, max-age=31536000, immutable'
                : 'private, no-store',
        ]);
    }
}
