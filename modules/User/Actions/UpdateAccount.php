<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Modules\Media\Enums\SafetyRating;
use Modules\User\Models\User;

final class UpdateAccount
{
    public function handle(
        User $user,
        ?string $displayName,
        ?string $locale,
        ?SafetyRating $defaultSafetyFilter = null,
    ): User {
        $user->fill([
            // An empty string means "use my username", same as null.
            'display_name' => $displayName === '' ? null : $displayName,
            'locale' => $locale,
        ]);

        // Absent means "leave it alone" — there is no "no filter" state.
        if ($defaultSafetyFilter !== null) {
            $user->default_safety_filter = $defaultSafetyFilter;
        }

        $user->save();

        return $user;
    }
}
