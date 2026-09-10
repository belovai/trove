<?php

declare(strict_types=1);

use Modules\User\Enums\UserRank;

return [
    'upload' => UserRank::Regular,
    'vote' => UserRank::Regular,
    'favorite' => UserRank::Regular,
    'moderate' => UserRank::Moderator,
];
