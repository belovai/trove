<?php

declare(strict_types=1);

namespace Modules\Media\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Media\Actions\DeleteMedia;
use Modules\Media\Models\Media;

final class DestroyMediaController
{
    public function __construct(
        private readonly DeleteMedia $deleteMedia,
    ) {}

    public function __invoke(Request $request, string $media): RedirectResponse
    {
        $item = Media::query()->visibleTo($request->user())->where('hash_id', $media)->firstOrFail();

        abort_unless($request->user()?->can('delete', $item), 403);

        $this->deleteMedia->handle($item);

        return redirect()->route('media.index')->with('success', __('media::media.deleted'));
    }
}
