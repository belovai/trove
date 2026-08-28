<?php

declare(strict_types=1);

namespace Modules\Tag\Actions;

use Modules\Tag\Models\TagAlias;

final class DeleteAlias
{
    public function handle(TagAlias $alias): void
    {
        $alias->delete();
    }
}
