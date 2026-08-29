<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Modules\User\Models\User;
use Tests\TestCase;

final class SharedVerificationStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_shared_user_carries_the_address_and_its_state(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com', 'email_verified_at' => null]);

        $this->actingAs($user)
            ->get('/posts')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('auth.user.email', 'user@example.com')
                ->where('auth.user.email_verified', false)
            );
    }

    public function test_a_verified_user_is_reported_as_verified(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com', 'email_verified_at' => now()]);

        $this->actingAs($user)
            ->get('/posts')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('auth.user.email_verified', true));
    }
}
