<?php

declare(strict_types=1);

namespace Modules\User\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\User\Enums\UserRank;
use Modules\User\Models\User;

/**
 * @extends Factory<User>
 */
final class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'username' => Str::lower($this->faker->unique()->userName()),
            'display_name' => null,
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => 'password',
            'rank' => UserRank::Regular,
            'locale' => null,
            'default_safety_filter' => 'safe',
        ];
    }

    public function administrator(): self
    {
        return $this->state(fn (): array => ['rank' => UserRank::Administrator]);
    }

    public function moderator(): self
    {
        return $this->state(fn (): array => ['rank' => UserRank::Moderator]);
    }

    public function restricted(): self
    {
        return $this->state(fn (): array => ['rank' => UserRank::Restricted]);
    }

    public function banned(string $reason = 'Spam'): self
    {
        return $this->state(fn (): array => [
            'banned_at' => now(),
            'ban_reason' => $reason,
        ]);
    }

    public function withoutEmail(): self
    {
        return $this->state(fn (): array => [
            'email' => null,
            'email_verified_at' => null,
        ]);
    }
}
