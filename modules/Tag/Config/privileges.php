<?php

declare(strict_types=1);

use Modules\User\Enums\UserRank;

return [
    // Tagging any item you can see. Open by convention: collaborative tagging
    // is what makes a shallow taxonomy usable.
    'edit' => UserRank::Regular,
    // Aliases, implications, descriptions, category assignment, merge, delete.
    // These affect the whole collection, not one item.
    'manage' => UserRank::Moderator,
    // Categories themselves, and taxonomy import.
    'admin' => UserRank::Administrator,
];
