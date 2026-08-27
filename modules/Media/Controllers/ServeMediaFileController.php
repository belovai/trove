<?php

declare(strict_types=1);

namespace Modules\Media\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\Media\Contracts\MediaStorage;
use Modules\Media\Enums\Visibility;
use Modules\Media\Models\Media;
use Symfony\Component\HttpFoundation\Response;

final class ServeMediaFileController
{
    public function __construct(
        private readonly MediaStorage $storage,
    ) {}

    public function __invoke(Request $request, string $media): Response
    {
        $item = Media::visibleTo($request->user())
            ->where('hash_id', $media)
            ->firstOrFail();

        // Gate::allows() runs MediaPolicy::view, whose $user parameter is
        // nullable, so guests are authorized by the same rules rather than by
        // a second, divergent check here.
        //
        // A 404 rather than a 403: a 403 would confirm that an item with this
        // hash id exists, which is itself information.
        abort_unless(Gate::allows('view', $item), 404);

        $etag = '"'.substr($item->content_hash, 0, 32).'"';

        if ($request->headers->get('If-None-Match') === $etag) {
            return response()->noContent(304, ['ETag' => $etag]);
        }

        return $this->storage->stream(
            $item->storage_path,
            $item->mime_type,
            $this->safeFilename($item),
            [
                'ETag' => $etag,
                // Content under a given hash id never changes, so a public item
                // can be cached hard. Anything else must not be retained by a
                // shared cache at all.
                'Cache-Control' => $item->visibility->equals(Visibility::Public)
                    ? 'public, max-age=31536000, immutable'
                    : 'private, no-store',
            ],
        );
    }

    /**
     * The original filename is user input and reaches a response header. Strip
     * it to plain ASCII so it cannot inject header syntax.
     */
    private function safeFilename(Media $item): string
    {
        $name = preg_replace('/[^A-Za-z0-9._-]/', '_', $item->original_filename);

        return $name === '' || $name === null ? "{$item->hash_id}.bin" : $name;
    }
}
