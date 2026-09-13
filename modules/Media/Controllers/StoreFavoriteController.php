<?php

declare(strict_types=1);

namespace Modules\Media\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Media\Actions\ToggleFavorite;
use Modules\Media\Models\Media;

final class StoreFavoriteController
{
    public function __construct(
        private readonly ToggleFavorite $toggleFavorite,
    ) {}

    public function __invoke(Request $request, string $media): RedirectResponse
    {
        $item = Media::query()->visibleTo($request->user())->where('hash_id', $media)->firstOrFail();

        $user = $request->user();

        abort_unless($user !== null && $user->can('favorite', $item), 403);

        $this->toggleFavorite->add($item, $user);

        return back();
    }
}
