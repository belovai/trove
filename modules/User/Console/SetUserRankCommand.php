<?php

declare(strict_types=1);

namespace Modules\User\Console;

use Modules\User\Actions\ChangeUserRank;
use Modules\User\Enums\UserRank;

final class SetUserRankCommand extends UserCommand
{
    protected $signature = 'user:rank
        {username : The account to change}
        {rank : One of restricted, regular, power, moderator, administrator}';

    protected $description = 'Set a user rank.';

    public function handle(ChangeUserRank $changeUserRank): int
    {
        $user = $this->findUser();

        if ($user === null) {
            return self::FAILURE;
        }

        $rank = UserRank::tryFrom((string) $this->argument('rank'));

        if ($rank === null) {
            $ranks = implode(', ', array_column(UserRank::cases(), 'value'));
            $this->components->error("Unknown rank. Expected one of: {$ranks}.");

            return self::FAILURE;
        }

        $changeUserRank->handle($user, $rank);

        $this->components->info("{$user->username} is now {$rank->value}.");

        return self::SUCCESS;
    }
}
