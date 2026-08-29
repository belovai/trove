<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Media\Enums\Visibility;
use Modules\Setting\Facades\Settings;
use Modules\User\Models\User;
use Tests\TestCase;

final class CreateMediaPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_page_prefills_the_system_default_visibility(): void
    {
        Settings::set('media.default_visibility', Visibility::Unlisted);

        $this->actingAs(User::factory()->create(['default_visibility' => null]))
            ->get('/upload')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('default_visibility', 'unlisted'));
    }

    public function test_the_users_own_default_overrides_the_system_default(): void
    {
        Settings::set('media.default_visibility', Visibility::Public);

        $this->actingAs(User::factory()->create(['default_visibility' => Visibility::Private]))
            ->get('/upload')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('default_visibility', 'private'));
    }
}
