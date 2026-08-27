<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Modules\User\Models\User;
use Tests\TestCase;

final class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_landing_page_is_public(): void
    {
        $this->get('/')->assertOk()->assertInertia(
            fn (AssertableInertia $page) => $page->component('Home')
        );
    }

    public function test_the_landing_page_does_not_list_media(): void
    {
        // The grid lives at /posts now; the landing page only points at it.
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertInertia(fn (AssertableInertia $page) => $page->missing('media'));
    }
}
