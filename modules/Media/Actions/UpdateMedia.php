<?php

declare(strict_types=1);

namespace Modules\Media\Actions;

use Modules\Media\Enums\SafetyRating;
use Modules\Media\Enums\Visibility;
use Modules\Media\Models\Media;

final class UpdateMedia
{
    public function handle(
        Media $media,
        ?string $title,
        ?string $description,
        ?string $source,
        Visibility $visibility,
        SafetyRating $safetyRating,
        bool $isAnonymous,
    ): Media {
        $media->fill([
            'title' => $title === '' ? null : $title,
            'description' => $description === '' ? null : $description,
            'source' => $source === '' ? null : $source,
            'visibility' => $visibility,
            'safety_rating' => $safetyRating,
            'is_anonymous' => $isAnonymous,
        ])->save();

        return $media;
    }
}
