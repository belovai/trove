<?php

declare(strict_types=1);

namespace Modules\User\Controllers;

use App\Support\SettingsSections;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\User\Enums\UserRank;
use Modules\User\Models\User;

final class IndexUsersController
{
    public function __invoke(Request $request): Response
    {
        abort_unless($request->user()?->can('viewAny', User::class) ?? false, 403);

        $search = $request->string('search')->trim()->toString();
        $rank = UserRank::tryFrom($request->string('rank')->toString());
        $status = $request->string('status')->toString();

        $users = User::query()
            ->when($search !== '', fn (Builder $query) => $query->where(
                fn (Builder $inner) => $inner
                    ->where('username', 'like', "%{$search}%")
                    ->orWhere('display_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"),
            ))
            ->when($rank !== null, fn (Builder $query) => $query->where('rank', $rank))
            ->when($status === 'banned', fn (Builder $query) => $query->whereNotNull('banned_at'))
            ->when($status === 'active', fn (Builder $query) => $query->whereNull('banned_at'))
            ->withCount(['media' => fn (Builder $query) => $query->whereNull('deleted_at')])
            ->orderBy('username')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'username' => $user->username,
                'display_name' => $user->displayName(),
                'email' => $user->email,
                'rank' => $user->rank->value,
                'is_banned' => $user->isBanned(),
                'ban_reason' => $user->ban_reason,
                'registered_at' => $user->created_at?->toIso8601String(),
                'uploads' => $user->media_count,
            ]);

        return Inertia::render('settings/Users', [
            'sections' => SettingsSections::for($request->user()),
            'current' => 'users',
            'users' => $users,
            'filters' => [
                'search' => $search === '' ? null : $search,
                'rank' => $rank?->value,
                'status' => $status === '' ? null : $status,
            ],
            'ranks' => array_map(static fn (UserRank $rank): string => $rank->value, UserRank::cases()),
        ]);
    }
}
