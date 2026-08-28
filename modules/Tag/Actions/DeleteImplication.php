<?php

declare(strict_types=1);

namespace Modules\Tag\Actions;

use Modules\Tag\Models\Tag;
use Modules\Tag\Models\TagImplication;

final class DeleteImplication
{
    public function handle(Tag $tag, Tag $implied): void
    {
        TagImplication::query()->where('tag_id', $tag->id)
            ->where('implied_tag_id', $implied->id)
            ->delete();
    }
}
