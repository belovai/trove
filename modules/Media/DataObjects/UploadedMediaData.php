<?php

declare(strict_types=1);

namespace Modules\Media\DataObjects;

use Illuminate\Http\UploadedFile;
use Modules\Media\Enums\SafetyRating;
use Modules\Media\Enums\Visibility;
use Modules\User\Models\User;

final readonly class UploadedMediaData
{
    public function __construct(
        public UploadedFile $file,
        public User $uploader,
        public ?string $title,
        public ?string $description,
        public ?string $source,
        public Visibility $visibility,
        public SafetyRating $safetyRating,
        public bool $isAnonymous,
    ) {}
}
