<?php

declare(strict_types=1);

namespace Modules\User\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Media\Models\Media;
use Modules\Media\Support\MediaCardPayload;
use Modules\User\Models\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ShowProfileController
{
    public function __invoke(Request $request, User $user): Response
    {
        /** @var User|null $viewer */
        $viewer = $request->user();
        $moderates = $viewer !== null && Gate::forUser($viewer)->allows('media.moderate');

        // A banned account is hidden from ordinary visitors, but not from the
        // people who moderate it. Its items stay reachable at their own URLs:
        // this hides a profile, it is not a content takedown.
        if ($user->banned_at !== null && !$moderates) {
            throw new NotFoundHttpException;
        }

        // The switch governs what other people see. The owner keeps their own
        // list, and moderators see it for moderation.
        $isOwner = $viewer !== null && $viewer->id === $user->id;
        $listVisible = $user->show_uploads || $isOwner || $moderates;

        $uploads = $listVisible ? $this->uploads($user, $viewer, $isOwner || $moderates) : null;

        return Inertia::render('users/Profile', [
            'profile' => [
                'username' => $user->username,
                'display_name' => $user->displayName(),
                'avatar_url' => $user->avatarUrl(),
                'rank' => $user->rank->value,
                'rank_label' => $user->rank->label(),
                'registered_at' => $user->created_at?->toIso8601String(),
                // Counted from the same query that builds the grid, so the
                // number and the list can never disagree.
                'upload_count' => $uploads?->total() ?? $this->query($user, $viewer)->count(),
                'is_banned' => $user->banned_at !== null,
            ],
            'uploads' => $uploads,
            // Moderator-only: this view differs from the public one, and must
            // not be mistaken for it.
            'notices' => [
                'uploads_hidden' => $moderates && !$user->show_uploads,
                'has_anonymous' => $moderates && $this->query($user, $viewer)->where('is_anonymous', true)->exists(),
            ],
        ]);
    }

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function uploads(User $user, ?User $viewer, bool $withAnonymousFlag): LengthAwarePaginator
    {
        return $this->query($user, $viewer)
            ->latest()
            ->paginate(60)
            ->withQueryString()
            ->through(fn (Media $item): array => MediaCardPayload::for($item, $withAnonymousFlag));
    }

    /**
     * The one query behind the grid, the count and the moderator notice.
     *
     * listable(), not ownUnlisted(): unlisted items stay out of every
     * listing, the owner's own profile included. The browse page's unlisted
     * toggle is where those are found.
     *
     * @return Builder<Media>
     */
    private function query(User $user, ?User $viewer): Builder
    {
        return Media::query()
            ->attributedTo($user, $viewer)
            ->visibleTo($viewer)
            ->listable()
            ->withinSafetyFilter($viewer);
    }
}
