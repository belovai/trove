<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Setting\Facades\Settings;
use Tests\TestCase;

final class AppNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_app_name_is_shared_with_every_page(): void
    {
        Settings::set('app.name', 'My Gallery');

        $this->get('/')->assertOk()->assertInertia(
            fn ($page) => $page->where('app_name', 'My Gallery'),
        );
    }

    public function test_the_document_title_uses_the_stored_name(): void
    {
        Settings::set('app.name', 'My Gallery');

        $this->get('/')->assertOk()->assertSee('<title inertia>My Gallery</title>', false);
    }
}
