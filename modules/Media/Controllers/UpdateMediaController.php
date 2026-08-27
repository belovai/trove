<?php

declare(strict_types=1);

namespace Modules\Media\Controllers;

use Illuminate\Http\RedirectResponse;
use Modules\Media\Actions\UpdateMedia;
use Modules\Media\Enums\SafetyRating;
use Modules\Media\Enums\Visibility;
use Modules\Media\Models\Media;
use Modules\Media\Requests\UpdateMediaRequest;

final class UpdateMediaController
{
    public function __construct(
        private readonly UpdateMedia $updateMedia,
    ) {}

    public function __invoke(UpdateMediaRequest $request, string $media): RedirectResponse
    {
        $item = Media::visibleTo($request->user())->where('hash_id', $media)->firstOrFail();

        abort_unless($request->user()?->can('update', $item), 403);

        $this->updateMedia->handle(
            media: $item,
            title: $request->input('title'),
            description: $request->input('description'),
            source: $request->input('source'),
            visibility: Visibility::from($request->string('visibility')->value()),
            safetyRating: SafetyRating::from($request->string('safety_rating')->value()),
            isAnonymous: $request->boolean('is_anonymous'),
        );

        return redirect()->route('media.show', $item)->with('success', __('media::media.updated'));
    }
}
