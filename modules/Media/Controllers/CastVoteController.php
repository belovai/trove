<?php

declare(strict_types=1);

namespace Modules\Media\Controllers;

use Illuminate\Http\RedirectResponse;
use Modules\Media\Actions\CastVote;
use Modules\Media\Models\Media;
use Modules\Media\Requests\CastVoteRequest;

final class CastVoteController
{
    public function __construct(
        private readonly CastVote $castVote,
    ) {}

    public function __invoke(CastVoteRequest $request, string $media): RedirectResponse
    {
        $item = Media::query()->visibleTo($request->user())->where('hash_id', $media)->firstOrFail();

        $user = $request->user();

        abort_unless($user !== null && $user->can('vote', $item), 403);

        $this->castVote->handle($item, $user, $request->voteValue());

        // Back, not JSON: the page reloads its own props and the component's
        // optimistic state is corrected by the fresh values.
        return back();
    }
}
