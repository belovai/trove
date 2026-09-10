<?php

declare(strict_types=1);

namespace Modules\Media\Enums;

/**
 * A vote is a ranking signal, not a sentiment: up means "this belongs higher
 * in the gallery". The two cases are the two summands of media.score.
 */
enum VoteValue: int
{
    case Up = 1;
    case Down = -1;
}
