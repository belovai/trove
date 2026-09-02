<?php

declare(strict_types=1);

namespace Modules\User\Tests\Unit;

use Modules\User\Enums\UserRank;
use PHPUnit\Framework\TestCase;

final class UserRankTest extends TestCase
{
    public function test_ranks_are_ordered_from_restricted_to_administrator(): void
    {
        $levels = array_map(
            static fn (UserRank $rank): int => $rank->level(),
            UserRank::cases(),
        );

        $this->assertSame([1, 2, 3, 4, 5], $levels);
    }

    public function test_a_higher_rank_outranks_a_lower_one(): void
    {
        $this->assertGreaterThan(
            UserRank::Regular->level(),
            UserRank::Moderator->level(),
        );
    }

    public function test_it_compares_cases(): void
    {
        $this->assertTrue(UserRank::Regular->equals(UserRank::Regular));
        $this->assertTrue(UserRank::Regular->notEquals(UserRank::Power));
        $this->assertTrue(UserRank::Power->in([UserRank::Power, UserRank::Moderator]));
    }

    public function test_outranks_is_strict_while_outranks_or_equals_is_not(): void
    {
        $this->assertTrue(UserRank::Moderator->outranks(UserRank::Regular));
        $this->assertFalse(UserRank::Administrator->outranks(UserRank::Administrator));
        $this->assertFalse(UserRank::Regular->outranks(UserRank::Moderator));

        $this->assertTrue(UserRank::Administrator->outranksOrEquals(UserRank::Administrator));
    }
}
