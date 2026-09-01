<?php

declare(strict_types=1);

namespace Modules\Media\Controllers;

use Illuminate\Http\RedirectResponse;
use Modules\Media\Actions\UpdateMedia;
use Modules\Media\Enums\SafetyRating;
use Modules\Media\Enums\Visibility;
use Modules\Media\Models\Media;
use Modules\Media\Requests\UpdateMediaRequest;
use Modules\Tag\Actions\SyncMediaTags;

final class UpdateMediaController
{
    public function __construct(
        private readonly UpdateMedia $updateMedia,
        private readonly SyncMediaTags $syncMediaTags,
    ) {}

    public function __invoke(UpdateMediaRequest $request, string $media): RedirectResponse
    {
        $item = Media::query()->visibleTo($request->user())->where('hash_id', $media)->firstOrFail();

        abort_unless($request->user()?->can('update', $item) ?? false, 403);

        // An absent field keeps its stored value: the two editing surfaces on
        // the media page submit disjoint subsets.
        $this->updateMedia->handle(
            media: $item,
            title: $request->has('title') ? $request->input('title') : $item->title,
            description: $request->has('description') ? $request->input('description') : $item->description,
            source: $request->has('source') ? $request->input('source') : $item->source,
            visibility: $request->has('visibility')
                ? Visibility::from($request->string('visibility')->value())
                : $item->visibility,
            safetyRating: $request->has('safety_rating')
                ? SafetyRating::from($request->string('safety_rating')->value())
                : $item->safety_rating,
            isAnonymous: $request->has('is_anonymous') ? $request->boolean('is_anonymous') : $item->is_anonymous,
        );

        // SyncMediaTags always recomputes the full implied closure, so it must
        // only run when the human tag set was actually submitted.
        if ($request->has('tags')) {
            $this->syncMediaTags->handle($item, $request->resolvedTags()->tagIds, $request->user());
        }

        return redirect()->route('media.show', $item)
            ->with('success', __('media::media.updated'))
            ->with('tag_warnings', $request->has('tags') ? $request->resolvedTags()->warnings : []);
    }
}
