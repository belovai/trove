<?php

declare(strict_types=1);

namespace Modules\Media\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Media\Enums\VoteValue;
use Modules\Media\Models\Media;
use Modules\Media\Models\Vote;
use Modules\Media\Services\VoteScoreCounter;
use Modules\User\Models\User;

/**
 * The single writer of the votes table. Cast, switch and withdraw are one
 * operation because they are one user gesture: a null $value is a withdrawal,
 * and updateOrCreate covers both the first vote and a change of mind.
 *
 * Authorization is the caller's job — MediaPolicy::vote() decides who may be
 * here at all.
 */
final class CastVote
{
    public function __construct(
        private readonly VoteScoreCounter $counter,
    ) {}

    /**
     * @return int the item's new score
     */
    public function handle(Media $media, User $user, ?VoteValue $value): int
    {
        return DB::transaction(function () use ($media, $user, $value): int {
            $identity = ['media_id' => $media->id, 'user_id' => $user->id];

            if ($value === null) {
                Vote::query()->where($identity)->delete();
            } else {
                // updateOrCreate rather than an insert: a double-clicked
                // button either collapses into one row or loses the race on
                // the composite unique, and the recalculation below is
                // idempotent either way.
                Vote::query()->updateOrCreate($identity, ['value' => $value]);
            }

            return $this->counter->recalculate($media);
        });
    }
}
