<?php

declare(strict_types=1);

namespace Modules\Media\Enums;

use Illuminate\Database\Eloquent\Builder;
use Modules\Media\Models\Media;

/**
 * The listing's ordering axis. It carries the ordering itself rather than
 * leaving it to the controller, so every listing page that grows a sort gets
 * the same one — including the secondary keys.
 *
 * This is the URL parameter (?sort=score). The `sort:score` search-string form
 * arrives with the Search module, against the same column.
 */
enum MediaSort: string
{
    case Newest = 'newest';
    case Oldest = 'oldest';
    case Score = 'score';

    /**
     * @param  Builder<Media>  $query
     */
    public function apply(Builder $query): void
    {
        match ($this) {
            self::Newest => $query->latest(),
            self::Oldest => $query->oldest(),
            // The secondary key is not decoration: items sharing a score would
            // otherwise reorder between pages, and pagination would silently
            // drop and repeat rows.
            self::Score => $query->orderByDesc('score')->orderByDesc('created_at'),
        };
    }
}
